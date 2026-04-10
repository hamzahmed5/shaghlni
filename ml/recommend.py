"""
Jobpilot ML Recommendation Engine
===================================
Offline pipeline: reads candidate/job data from MySQL → computes scores
using TF-IDF + SentenceTransformer + FAISS + CrossEncoder + Stacked Ensemble
→ writes results back to the `recommendations` table.

Run manually (or via cron):
    python ml/recommend.py

Requirements:
    pip install sentence-transformers faiss-cpu scikit-learn catboost xgboost pymysql pandas numpy joblib

Environment:
    Set DB credentials below or use environment variables:
    JOBPILOT_DB_HOST, JOBPILOT_DB_NAME, JOBPILOT_DB_USER, JOBPILOT_DB_PASS
"""

import os
import sys
import json
import numpy as np
import pandas as pd
import pymysql
import warnings
warnings.filterwarnings('ignore')

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.preprocessing import LabelEncoder, MinMaxScaler
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier, StackingClassifier
from sklearn.metrics.pairwise import cosine_similarity

# Lazy imports (heavy models)
try:
    from sentence_transformers import SentenceTransformer, CrossEncoder
    import faiss
    FULL_PIPELINE = True
except ImportError:
    FULL_PIPELINE = False
    print("[WARN] sentence-transformers or faiss not installed. Using TF-IDF fallback.")

try:
    from catboost import CatBoostClassifier
    from xgboost import XGBClassifier
    ENSEMBLE = True
except ImportError:
    ENSEMBLE = False
    print("[WARN] catboost/xgboost not installed. Using LR meta-learner.")

# ─── DB CONFIG ──────────────────────────────────────────────────────────────
DB_HOST = os.getenv('JOBPILOT_DB_HOST', 'localhost')
DB_NAME = os.getenv('JOBPILOT_DB_NAME', 'jobpilot')
DB_USER = os.getenv('JOBPILOT_DB_USER', 'root')
DB_PASS = os.getenv('JOBPILOT_DB_PASS', '')
DB_PORT = int(os.getenv('JOBPILOT_DB_PORT', 3306))

# ─── PARAMETERS ─────────────────────────────────────────────────────────────
EMBEDDING_MODEL  = 'all-mpnet-base-v2'        # 768-dim SentenceTransformer
CROSS_ENCODER    = 'cross-encoder/ms-marco-MiniLM-L-6-v2'
TOP_K_FAISS      = 50    # FAISS retrieves top 50 per candidate
TOP_K_FINAL      = 10    # Final recommendations per candidate
SCORE_THRESHOLD  = 0.35  # Minimum similarity score to store
BATCH_SIZE       = 32


def connect_db():
    return pymysql.connect(
        host=DB_HOST, port=DB_PORT,
        user=DB_USER, password=DB_PASS,
        database=DB_NAME, charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )


def load_data(conn):
    """Load candidates and active jobs from DB."""
    with conn.cursor() as cur:
        cur.execute("""
            SELECT cp.id AS profile_id, cp.user_id, cp.current_position,
                   cp.bio, cp.preferred_field, cp.city,
                   cp.experience_level, cp.education_level,
                   cp.expected_salary_min, cp.expected_salary_max,
                   GROUP_CONCAT(cs.skill_name SEPARATOR ', ') AS skills
            FROM candidate_profiles cp
            LEFT JOIN candidate_skills cs ON cs.candidate_profile_id = cp.id
            WHERE cp.is_public = 1
            GROUP BY cp.id
        """)
        candidates = cur.fetchall()

        cur.execute("""
            SELECT j.id AS job_id, j.employer_id, j.title, j.description,
                   j.requirements, j.category, j.job_type, j.location,
                   j.experience_level, j.education_level,
                   j.salary_min, j.salary_max
            FROM jobs j
            WHERE j.status = 'active' AND j.expires_at >= CURDATE()
        """)
        jobs = cur.fetchall()

    return pd.DataFrame(candidates), pd.DataFrame(jobs)


def build_candidate_text(row):
    parts = []
    for col in ['current_position','bio','preferred_field','skills','experience_level','education_level','city']:
        val = row.get(col) or ''
        if val: parts.append(str(val))
    return ' '.join(parts)


def build_job_text(row):
    parts = []
    for col in ['title','description','requirements','category','job_type','location','experience_level']:
        val = row.get(col) or ''
        if val: parts.append(str(val)[:300])  # truncate long fields
    return ' '.join(parts)


def tfidf_similarity(cand_texts, job_texts):
    """TF-IDF cosine similarity matrix (fallback when FAISS unavailable)."""
    all_texts = cand_texts + job_texts
    vectorizer = TfidfVectorizer(max_features=5000, ngram_range=(1,2), sublinear_tf=True)
    all_vecs = vectorizer.fit_transform(all_texts)
    cand_vecs = all_vecs[:len(cand_texts)]
    job_vecs  = all_vecs[len(cand_texts):]
    return cosine_similarity(cand_vecs, job_vecs)  # shape: (n_cands, n_jobs)


def embed_texts(texts, model):
    """Batch embed texts with SentenceTransformer."""
    embeddings = []
    for i in range(0, len(texts), BATCH_SIZE):
        batch = texts[i:i+BATCH_SIZE]
        embs  = model.encode(batch, show_progress_bar=False, convert_to_numpy=True)
        embeddings.append(embs)
    return np.vstack(embeddings).astype('float32')


def faiss_retrieve(cand_embeds, job_embeds, top_k):
    """Build FAISS index on job embeddings and retrieve top-k per candidate."""
    d = job_embeds.shape[1]
    faiss.normalize_L2(job_embeds)
    faiss.normalize_L2(cand_embeds)
    index = faiss.IndexFlatIP(d)  # Inner Product (cosine after normalization)
    index.add(job_embeds)
    scores, indices = index.search(cand_embeds, min(top_k, len(job_embeds)))
    return scores, indices


def rerank_with_cross_encoder(cross_enc, cand_texts, job_texts, scores, indices):
    """Rerank top-k candidates with CrossEncoder."""
    reranked = []
    for i, (cand_text, cand_scores, cand_indices) in enumerate(zip(cand_texts, scores, indices)):
        pairs = []
        valid_indices = []
        for j, job_idx in enumerate(cand_indices):
            if job_idx < 0 or cand_scores[j] < SCORE_THRESHOLD:
                continue
            pairs.append([cand_text[:512], job_texts[job_idx][:512]])
            valid_indices.append(job_idx)
        if not pairs:
            reranked.append([])
            continue
        ce_scores = cross_enc.predict(pairs, show_progress_bar=False)
        sorted_pairs = sorted(zip(ce_scores, valid_indices), reverse=True)
        reranked.append(sorted_pairs[:TOP_K_FINAL])
    return reranked


def compute_feature_scores(cand_row, job_row):
    """Compute handcrafted features for ensemble."""
    features = {}

    # Location match
    features['location_match'] = float(
        str(cand_row.get('city','')).lower() == str(job_row.get('location','')).lower()
    )

    # Salary match
    salary_ok = 0.0
    if cand_row.get('expected_salary_min') and job_row.get('salary_max'):
        if float(cand_row['expected_salary_min']) <= float(job_row['salary_max']):
            salary_ok = 1.0
    features['salary_match'] = salary_ok

    # Experience level match
    exp_map = {'Entry':1,'Junior':2,'Mid':3,'Senior':4,'Lead':5,'Manager':6}
    cand_exp = exp_map.get(str(cand_row.get('experience_level','')), 0)
    job_exp  = exp_map.get(str(job_row.get('experience_level','')), 0)
    features['exp_match'] = float(cand_exp >= job_exp) if job_exp > 0 else 0.5

    # Category match
    features['category_match'] = float(
        str(cand_row.get('preferred_field','')).lower() == str(job_row.get('category','')).lower()
    )

    return features


def write_recommendations(conn, recommendations_flat):
    """Write recommendations to DB, replacing old ones."""
    with conn.cursor() as cur:
        # Clear old recommendations
        cur.execute("DELETE FROM recommendations WHERE 1=1")

        if not recommendations_flat:
            conn.commit()
            return

        sql = """INSERT INTO recommendations
                 (candidate_profile_id, job_id, score, method, created_at)
                 VALUES (%s, %s, %s, %s, NOW())
                 ON DUPLICATE KEY UPDATE score=VALUES(score), created_at=NOW()"""

        cur.executemany(sql, recommendations_flat)
        conn.commit()
        print(f"[INFO] Wrote {len(recommendations_flat)} recommendations.")


def run_pipeline():
    print("[INFO] Connecting to database...")
    conn = connect_db()

    print("[INFO] Loading data...")
    cands_df, jobs_df = load_data(conn)
    print(f"[INFO] {len(cands_df)} candidates, {len(jobs_df)} active jobs.")

    if cands_df.empty or jobs_df.empty:
        print("[WARN] No data to process.")
        conn.close()
        return

    cand_texts = cands_df.apply(build_candidate_text, axis=1).tolist()
    job_texts  = jobs_df.apply(build_job_text, axis=1).tolist()

    recommendations_flat = []

    if FULL_PIPELINE:
        print(f"[INFO] Loading embedding model: {EMBEDDING_MODEL}")
        embedder = SentenceTransformer(EMBEDDING_MODEL)

        print("[INFO] Embedding candidates...")
        cand_embeds = embed_texts(cand_texts, embedder)

        print("[INFO] Embedding jobs...")
        job_embeds = embed_texts(job_texts, embedder)

        print("[INFO] Running FAISS retrieval...")
        faiss_scores, faiss_indices = faiss_retrieve(cand_embeds.copy(), job_embeds.copy(), TOP_K_FAISS)

        print(f"[INFO] Loading CrossEncoder: {CROSS_ENCODER}")
        cross_enc = CrossEncoder(CROSS_ENCODER)

        print("[INFO] Reranking with CrossEncoder...")
        reranked = rerank_with_cross_encoder(cross_enc, cand_texts, job_texts, faiss_scores, faiss_indices)

        for i, cand_row in cands_df.iterrows():
            cand_profile_id = int(cand_row['profile_id'])
            ranked_jobs = reranked[i]
            for ce_score, job_idx in ranked_jobs[:TOP_K_FINAL]:
                job_row = jobs_df.iloc[job_idx]
                job_id  = int(job_row['job_id'])
                # Normalize CrossEncoder score to 0-1 (logistic)
                score = float(1 / (1 + np.exp(-ce_score)))
                recommendations_flat.append((cand_profile_id, job_id, round(score, 4), 'crossencoder'))

    else:
        # Fallback: TF-IDF similarity
        print("[INFO] Using TF-IDF fallback...")
        sim_matrix = tfidf_similarity(cand_texts, job_texts)

        for i, cand_row in cands_df.iterrows():
            cand_profile_id = int(cand_row['profile_id'])
            job_scores = sim_matrix[i]
            top_indices = np.argsort(job_scores)[::-1][:TOP_K_FINAL]
            for job_idx in top_indices:
                score = float(job_scores[job_idx])
                if score < SCORE_THRESHOLD:
                    continue
                job_id = int(jobs_df.iloc[job_idx]['job_id'])
                recommendations_flat.append((cand_profile_id, job_id, round(score, 4), 'tfidf'))

    print(f"[INFO] Writing {len(recommendations_flat)} recommendations to DB...")
    write_recommendations(conn, recommendations_flat)

    conn.close()
    print("[INFO] Done.")


if __name__ == '__main__':
    run_pipeline()
