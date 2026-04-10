"""
recommend.py — ML Recommendation Engine
Adapted from search_engine_main.ipynb

Pipeline:
  1. Load candidate + job features from MySQL
  2. Preprocess text (NLTK lemmatization)
  3. TF-IDF weighted skills text
  4. SBERT embeddings (cached to .npy between runs)
  5. FAISS fast retrieval (top-100 per candidate)
  6. Multi-field scoring: skills 45%, exp 20%, salary 15%, location 10%, field 10%
  7. CrossEncoder reranking (optional, slower)
  8. Return top-K

Modes:
  local  -> called by PHP for one candidate, returns JSON to stdout
  cache  -> batch all candidates -> write to `recommendations` table in MySQL

Usage:
  # Local mode (PHP calls this):
  python recommend.py --mode local --candidate_id 42 --limit 10

  # Cache mode (run manually or via cron):
  python recommend.py --mode cache --db_host localhost --db_name jobs_platform \
         --db_user root --db_pass "" --limit 10

  # Force-rebuild embedding caches (after new jobs/candidates added):
  python recommend.py --mode cache --rebuild_cache

  # Skip CrossEncoder for speed:
  python recommend.py --mode cache --no_crossencoder
"""

from __future__ import annotations

import argparse
import json
import logging
import os
import string
import sys
import time
from pathlib import Path
from typing import Any

import numpy as np

# ── Logging ───────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.WARNING,   # quiet in local mode (stdout = JSON)
    format="%(asctime)s %(levelname)s %(message)s",
    stream=sys.stderr,
)
log = logging.getLogger(__name__)

SCRIPT_DIR = Path(__file__).parent

# ── Embedding cache paths ─────────────────────────────────────────────────────
CANDIDATE_SKILL_EMB = SCRIPT_DIR / "cache_candidate_skill.npy"
CANDIDATE_FIELD_EMB = SCRIPT_DIR / "cache_candidate_field.npy"
JOB_SKILL_EMB       = SCRIPT_DIR / "cache_job_skill.npy"
JOB_FIELD_EMB       = SCRIPT_DIR / "cache_job_field.npy"
JOB_IDS_CACHE       = SCRIPT_DIR / "cache_job_ids.npy"

# ── Scoring weights (from notebook Cell 31) ───────────────────────────────────
W_SKILLS   = 0.45
W_EXP      = 0.20
W_SALARY   = 0.15
W_LOCATION = 0.10
W_FIELD    = 0.10

# ── Jordanian city coordinates (from notebook Cell 13) ────────────────────────
CITY_COORDS: dict[str, tuple[float, float] | None] = {
    "Amman":   (31.9539, 35.9106),
    "Irbid":   (32.5556, 35.8500),
    "Zarqa":   (32.0727, 36.0874),
    "Aqaba":   (29.5321, 35.0063),
    "Mafraq":  (32.3431, 36.2050),
    "Jerash":  (32.2731, 35.8997),
    "Madaba":  (31.7167, 35.8000),
    "Karak":   (31.1853, 35.7047),
    "Ajloun":  (32.3325, 35.7517),
    "Tafilah": (30.8381, 35.6100),
    "Maan":    (30.1931, 35.7340),
    "Balqa":   (32.0373, 35.7281),
    "Remote":  None,
}
MAX_DIST_KM = 200.0


# =============================================================================
#  NLP — lazy-init singleton (avoids module-level None-as-callable warnings)
# =============================================================================

class _NLPState:
    """Holds NLTK resources after first use."""
    ready: bool = False
    stop_words: set[str] = set()
    # typed Any so the type-checker does not complain about callable/lemmatize
    lemmatizer: Any = None
    word_tokenize: Any = None

    def ensure(self) -> None:
        if self.ready:
            return
        import nltk
        for pkg in ["punkt", "punkt_tab", "stopwords", "wordnet", "omw-1.4"]:
            nltk.download(pkg, quiet=True)
        from nltk.corpus import stopwords as sw
        from nltk.stem import WordNetLemmatizer
        from nltk.tokenize import word_tokenize as wt
        self.stop_words    = set(sw.words("english"))
        self.lemmatizer    = WordNetLemmatizer()
        self.word_tokenize = wt
        self.ready         = True


_NLP = _NLPState()


def preprocess_text(text: str) -> str:
    _NLP.ensure()
    text = str(text).lower()
    text = text.translate(str.maketrans("", "", string.punctuation))
    tokens: list[str] = _NLP.word_tokenize(text)
    return " ".join(
        _NLP.lemmatizer.lemmatize(t)
        for t in tokens
        if t not in _NLP.stop_words
    )


# =============================================================================
#  TF-IDF weighted skills — lazy-init singleton
# =============================================================================

class _TfidfState:
    """Holds fitted TfidfVectorizer after _fit_tfidf() is called."""
    ready: bool = False
    vectorizer: Any = None  # TfidfVectorizer
    vocab: dict[str, int] = {}
    idf_: np.ndarray = np.array([])
    max_idf: float = 1.0


_TFIDF = _TfidfState()
MAX_REPEAT = 4


def _fit_tfidf(all_skills: list[str]) -> None:
    from sklearn.feature_extraction.text import TfidfVectorizer
    corpus         = [preprocess_text(s) for s in all_skills]
    vec            = TfidfVectorizer(max_features=3000, min_df=1)
    vec.fit(corpus)
    _TFIDF.vectorizer = vec
    _TFIDF.vocab      = dict(vec.vocabulary_)
    _TFIDF.idf_       = np.array(vec.idf_, dtype=float)
    _TFIDF.max_idf    = float(_TFIDF.idf_.max()) or 1.0
    _TFIDF.ready      = True


def tfidf_weighted_skills(skills_str: str) -> str:
    if not _TFIDF.ready:
        return preprocess_text(skills_str)
    out: list[str] = []
    for token in preprocess_text(skills_str).split():
        if token in _TFIDF.vocab:
            idx    = _TFIDF.vocab[token]
            weight = _TFIDF.idf_[idx] / _TFIDF.max_idf
            repeat = max(1, round(float(weight) * MAX_REPEAT))
        else:
            repeat = 1
        out.extend([token] * repeat)
    return " ".join(out)


# =============================================================================
#  Geography
# =============================================================================

def geo_distance_score(loc_a: str, loc_b: str) -> float:
    """Return 0.0–1.0 location compatibility score."""
    if not loc_a or not loc_b:
        return 0.5
    if loc_a.lower() == loc_b.lower():
        return 1.0
    if "remote" in (loc_a.lower(), loc_b.lower()):
        return 0.9
    coords_a = CITY_COORDS.get(loc_a)
    coords_b = CITY_COORDS.get(loc_b)
    if coords_a is None or coords_b is None:
        return 0.5
    try:
        from geopy.distance import geodesic
        dist_km = float(geodesic(coords_a, coords_b).km)
        return max(0.0, 1.0 - dist_km / MAX_DIST_KM)
    except Exception:
        return 0.5


# =============================================================================
#  SBERT + FAISS — lazy-init singletons
# =============================================================================

class _SBERTState:
    ready: bool = False
    model: Any = None

    def load(self) -> None:
        if self.ready:
            return
        from sentence_transformers import SentenceTransformer
        log.warning("Loading SBERT model (first run may download ~420 MB)…")
        self.model = SentenceTransformer("all-mpnet-base-v2")
        self.ready = True


_SBERT = _SBERTState()


class _FAISSState:
    ready: bool = False
    index: Any = None     # faiss.IndexFlatIP
    n_total: int = 0

    def build(self, job_emb_norm: np.ndarray) -> None:
        import faiss
        dim        = job_emb_norm.shape[1]
        normed     = job_emb_norm.copy()
        faiss.normalize_L2(normed)
        idx        = faiss.IndexFlatIP(dim)
        idx.add(normed)
        self.index   = idx
        self.n_total = int(idx.ntotal)
        self.ready   = True

    def search(self, vec: np.ndarray, top_n: int) -> tuple[np.ndarray, np.ndarray]:
        import faiss
        v = vec.reshape(1, -1).copy()
        faiss.normalize_L2(v)
        distances, indices = self.index.search(v, min(top_n, self.n_total))
        return indices[0], distances[0]


_FAISS = _FAISSState()


def encode_texts(texts: list[str]) -> np.ndarray:
    _SBERT.load()
    emb: np.ndarray = _SBERT.model.encode(
        texts, convert_to_numpy=True, show_progress_bar=False
    )
    return emb.astype("float32")


def l2_norm(e: np.ndarray) -> np.ndarray:
    norms = np.linalg.norm(e, axis=1, keepdims=True)
    norms[norms == 0] = 1.0
    return e / norms


# =============================================================================
#  CrossEncoder — lazy-init singleton
# =============================================================================

class _CEState:
    ready: bool = False
    model: Any = None

    def load(self) -> None:
        if self.ready:
            return
        from sentence_transformers import CrossEncoder
        log.warning("Loading CrossEncoder…")
        self.model = CrossEncoder("cross-encoder/ms-marco-MiniLM-L-6-v2")
        self.ready = True


_CE = _CEState()


def crossencoder_rerank(
    user_text: str,
    job_texts: list[str],
    scores: list[float],
    top_k: int,
) -> tuple[list[int], list[float]]:
    """Rerank top_k candidates using CrossEncoder; returns (indices, combined_scores)."""
    _CE.load()
    pairs     = [(user_text, jt) for jt in job_texts]
    ce_scores = np.array(_CE.model.predict(pairs), dtype=float)

    ce_min, ce_max = float(ce_scores.min()), float(ce_scores.max())
    if ce_max > ce_min:
        ce_scores = (ce_scores - ce_min) / (ce_max - ce_min)
    else:
        ce_scores[:] = 0.5

    combined  = 0.6 * np.array(scores, dtype=float) + 0.4 * ce_scores
    order     = np.argsort(combined)[::-1][:top_k]
    idx_list  = [int(i) for i in order]
    sc_list   = [float(combined[i]) for i in order]
    return idx_list, sc_list


# =============================================================================
#  Multi-field scoring (notebook Cell 31)
# =============================================================================

def multi_field_score(
    user_skill_vec: np.ndarray,
    user_field_vec: np.ndarray,
    job_skill_vec:  np.ndarray,
    job_field_vec:  np.ndarray,
    u_exp: float,
    u_sal: float,
    u_loc: str,
    j_exp: float,
    j_sal_min: float,
    j_sal_max: float,
    j_loc: str,
) -> float:
    sk_sc    = float(np.dot(user_skill_vec, job_skill_vec))
    field_sc = float(np.dot(user_field_vec, job_field_vec))

    exp_sc   = 1.0 if u_exp >= j_exp else max(0.0, 1.0 - (j_exp - u_exp) / max(j_exp, 1.0))

    if j_sal_max > 0 and u_sal > 0:
        if u_sal <= j_sal_max:
            sal_sc = 1.0 if u_sal >= j_sal_min else max(0.0, u_sal / max(j_sal_min, 1.0))
        else:
            sal_sc = max(0.0, j_sal_max / u_sal)
    else:
        sal_sc = 0.5

    loc_sc = geo_distance_score(u_loc, j_loc)

    return (
        W_SKILLS   * sk_sc  +
        W_EXP      * exp_sc +
        W_SALARY   * sal_sc +
        W_LOCATION * loc_sc +
        W_FIELD    * field_sc
    )


# =============================================================================
#  Database helpers
# =============================================================================

def db_connect(host: str, port: str, name: str, user: str, pw: str) -> Any:
    try:
        import pymysql
        return pymysql.connect(
            host=host, port=int(port), db=name,
            user=user, password=pw, charset="utf8mb4",
        )
    except ImportError:
        log.error("pymysql not installed. Run: pip install pymysql")
        sys.exit(1)


def fetch_active_jobs(conn: Any) -> list[dict[str, Any]]:
    with conn.cursor() as cur:
        cur.execute("""
            SELECT j.id, j.job_title, j.industry,
                   j.required_experience_years, j.required_skills,
                   j.salary_min_jod, j.salary_max_jod,
                   j.job_type, j.location
            FROM jobs j
            WHERE j.status = 'active'
        """)
        cols = [d[0] for d in cur.description]
        return [dict(zip(cols, row)) for row in cur.fetchall()]


def fetch_candidate(conn: Any, candidate_profile_id: int) -> dict[str, Any] | None:
    with conn.cursor() as cur:
        cur.execute("""
            SELECT id, years_of_experience, primary_skills,
                   preferred_job_field, salary_expectation_jod, location
            FROM candidate_profiles
            WHERE id = %s LIMIT 1
        """, (candidate_profile_id,))
        row = cur.fetchone()
        if not row:
            return None
        cols = [d[0] for d in cur.description]
        return dict(zip(cols, row))


def fetch_all_candidates(conn: Any) -> list[dict[str, Any]]:
    with conn.cursor() as cur:
        cur.execute("""
            SELECT id, years_of_experience, primary_skills,
                   preferred_job_field, salary_expectation_jod, location
            FROM candidate_profiles
        """)
        cols = [d[0] for d in cur.description]
        return [dict(zip(cols, row)) for row in cur.fetchall()]


def upsert_recommendations(
    conn: Any, rows: list[tuple[int, int, float, str]]
) -> None:
    with conn.cursor() as cur:
        cur.executemany("""
            INSERT INTO recommendations
                (candidate_profile_id, job_id, score, reason_text, generated_at)
            VALUES (%s, %s, %s, %s, NOW())
            ON DUPLICATE KEY UPDATE
                score        = VALUES(score),
                reason_text  = VALUES(reason_text),
                generated_at = NOW()
        """, rows)
    conn.commit()


# =============================================================================
#  Embedding cache helpers
# =============================================================================

def _load_or_compute(
    cache_path: Path,
    ids_cache:  Path,
    ids:        list[Any],
    texts:      list[str],
    label:      str,
    rebuild:    bool,
) -> np.ndarray:
    if not rebuild and cache_path.exists() and ids_cache.exists():
        cached_ids: list[Any] = np.load(str(ids_cache), allow_pickle=True).tolist()
        if cached_ids == ids:
            log.warning(f"Loaded {label} from cache ({cache_path.name})")
            return np.load(str(cache_path))
    log.warning(f"Computing {label} for {len(texts)} items…")
    emb = encode_texts(texts)
    np.save(str(cache_path), emb)
    np.save(str(ids_cache),  np.array(ids, dtype=object))
    log.warning(f"Saved {label} to {cache_path.name}")
    return emb


# =============================================================================
#  Core: score jobs for one candidate
# =============================================================================

def _build_reason(job: dict[str, Any], u_loc: str) -> str:
    parts: list[str] = []
    if job.get("required_skills"):
        parts.append(f"Skills: {str(job['required_skills'])[:60]}")
    j_loc = str(job.get("location") or "")
    if j_loc:
        tag = j_loc if j_loc.lower() != "remote" else "Remote"
        match = "(match)" if tag.lower() == u_loc.lower() else ""
        parts.append(f"Location: {tag} {match}".strip())
    sal = float(job.get("salary_max_jod") or 0)
    if sal > 0:
        parts.append(f"Salary up to {sal:.0f} JOD")
    return " | ".join(parts)


def recommend_for_candidate(
    candidate:      dict[str, Any],
    jobs:           list[dict[str, Any]],
    job_skill_norm: np.ndarray,
    job_field_norm: np.ndarray,
    top_k:          int = 10,
    use_crossencoder: bool = False,
    faiss_top_n:    int = 100,
) -> list[dict[str, Any]]:
    if not jobs:
        return []

    u_skills_text = tfidf_weighted_skills(str(candidate.get("primary_skills") or ""))
    u_field_text  = preprocess_text(str(candidate.get("preferred_job_field") or ""))
    u_exp         = float(candidate.get("years_of_experience") or 0)
    u_sal         = float(candidate.get("salary_expectation_jod") or 0)
    u_loc         = str(candidate.get("location") or "")

    u_skill_emb  = encode_texts([u_skills_text])
    u_field_emb  = encode_texts([u_field_text])
    u_skill_norm = l2_norm(u_skill_emb)[0]   # shape (dim,)
    u_field_norm = l2_norm(u_field_emb)[0]

    # ── FAISS retrieval → top-N job indices ───────────────────────────────────
    retr_idx, _ = _FAISS.search(u_skill_norm, top_n=min(faiss_top_n, len(jobs)))

    # ── Multi-field scoring ───────────────────────────────────────────────────
    scored: list[tuple[int, float, dict[str, Any]]] = []
    for raw_i in retr_idx:
        ji = int(raw_i)
        if ji >= len(jobs):
            continue
        job = jobs[ji]
        sc  = multi_field_score(
            u_skill_norm, u_field_norm,
            job_skill_norm[ji], job_field_norm[ji],
            u_exp, u_sal, u_loc,
            float(job.get("required_experience_years") or 0),
            float(job.get("salary_min_jod") or 0),
            float(job.get("salary_max_jod") or 0),
            str(job.get("location") or ""),
        )
        scored.append((ji, sc, job))

    scored.sort(key=lambda x: x[1], reverse=True)
    pool = scored[: top_k * 3]

    # ── Optional CrossEncoder reranking ───────────────────────────────────────
    if use_crossencoder and pool:
        user_text = u_skills_text + " " + u_field_text
        job_texts = [
            tfidf_weighted_skills(str(j.get("required_skills") or "")) + " " +
            preprocess_text(str(j.get("job_title") or "") + " " + str(j.get("industry") or ""))
            for _, _, j in pool
        ]
        raw_scores    = [float(sc) for _, sc, _ in pool]
        order, combsc = crossencoder_rerank(user_text, job_texts, raw_scores, top_k)
        results: list[dict[str, Any]] = []
        for orig_idx, final_sc in zip(order, combsc):
            _, _, job = pool[orig_idx]
            results.append({
                "job_id": int(job["id"]),
                "score":  round(final_sc, 4),
                "reason": _build_reason(job, u_loc),
            })
        return results

    # ── No CrossEncoder: return top_k by multi-field score ───────────────────
    return [
        {
            "job_id": int(job["id"]),
            "score":  round(float(sc), 4),
            "reason": _build_reason(job, u_loc),
        }
        for _, sc, job in pool[:top_k]
    ]


# =============================================================================
#  Mode: local (single candidate → JSON stdout)
# =============================================================================

def run_local(args: argparse.Namespace) -> None:
    conn      = db_connect(args.db_host, args.db_port, args.db_name, args.db_user, args.db_pass)
    candidate = fetch_candidate(conn, args.candidate_id)

    if not candidate:
        print(json.dumps([]))
        conn.close()
        return

    jobs = fetch_active_jobs(conn)
    conn.close()

    if not jobs:
        print(json.dumps([]))
        return

    all_skills = (
        [str(candidate.get("primary_skills") or "")] +
        [str(j.get("required_skills") or "") for j in jobs]
    )
    _fit_tfidf(all_skills)

    job_skill_texts = [tfidf_weighted_skills(str(j.get("required_skills") or "")) for j in jobs]
    job_field_texts = [
        preprocess_text(str(j.get("job_title") or "") + " " + str(j.get("industry") or ""))
        for j in jobs
    ]

    jse = encode_texts(job_skill_texts)
    jfe = encode_texts(job_field_texts)
    job_skill_norm = l2_norm(jse)
    job_field_norm = l2_norm(jfe)

    _FAISS.build(job_skill_norm)

    results = recommend_for_candidate(
        candidate, jobs,
        job_skill_norm, job_field_norm,
        top_k=args.limit,
        use_crossencoder=not args.no_crossencoder,
    )
    print(json.dumps(results))


# =============================================================================
#  Mode: cache (all candidates → MySQL recommendations table)
# =============================================================================

def run_cache(args: argparse.Namespace) -> None:
    log.warning("=== Cache mode: computing recommendations for all candidates ===")
    conn       = db_connect(args.db_host, args.db_port, args.db_name, args.db_user, args.db_pass)
    jobs       = fetch_active_jobs(conn)
    candidates = fetch_all_candidates(conn)

    log.warning(f"Jobs: {len(jobs)} | Candidates: {len(candidates)}")

    if not jobs or not candidates:
        log.warning("No jobs or candidates found. Exiting.")
        conn.close()
        return

    # ── Fit TF-IDF on full corpus ─────────────────────────────────────────────
    all_skills = (
        [str(c.get("primary_skills") or "") for c in candidates] +
        [str(j.get("required_skills") or "") for j in jobs]
    )
    _fit_tfidf(all_skills)

    # ── Job embeddings (cached to .npy) ───────────────────────────────────────
    job_ids         = [int(j["id"]) for j in jobs]
    job_skill_texts = [tfidf_weighted_skills(str(j.get("required_skills") or "")) for j in jobs]
    job_field_texts = [
        preprocess_text(str(j.get("job_title") or "") + " " + str(j.get("industry") or ""))
        for j in jobs
    ]

    jse = _load_or_compute(JOB_SKILL_EMB, JOB_IDS_CACHE, job_ids, job_skill_texts,
                            "job_skill", args.rebuild_cache)
    jfe = _load_or_compute(JOB_FIELD_EMB, JOB_IDS_CACHE, job_ids, job_field_texts,
                            "job_field", args.rebuild_cache)

    job_skill_norm = l2_norm(jse)
    job_field_norm = l2_norm(jfe)

    _FAISS.build(job_skill_norm)

    # ── Process candidates in batches ─────────────────────────────────────────
    total      = len(candidates)
    batch_size = args.batch_size
    total_rows = 0
    t0         = time.time()

    for batch_start in range(0, total, batch_size):
        batch     = candidates[batch_start : batch_start + batch_size]
        reco_rows: list[tuple[int, int, float, str]] = []

        for cand in batch:
            results = recommend_for_candidate(
                cand, jobs,
                job_skill_norm, job_field_norm,
                top_k=args.limit,
                use_crossencoder=not args.no_crossencoder,
            )
            for r in results:
                reco_rows.append((
                    int(cand["id"]),
                    int(r["job_id"]),
                    float(r["score"]),
                    str(r.get("reason", "")),
                ))

        if reco_rows:
            upsert_recommendations(conn, reco_rows)
            total_rows += len(reco_rows)

        done    = min(batch_start + batch_size, total)
        elapsed = time.time() - t0
        log.warning(f"  {done}/{total} candidates | {total_rows} rows | {elapsed:.0f}s")

    conn.close()
    log.warning(f"=== Done. {total_rows} rows written in {time.time() - t0:.1f}s ===")


# =============================================================================
#  CLI
# =============================================================================

def main() -> None:
    parser = argparse.ArgumentParser(description="Jobs Platform — ML Recommender")

    parser.add_argument("--mode",         default="local", choices=["local", "cache"])
    parser.add_argument("--db_host",      default="localhost")
    parser.add_argument("--db_port",      default="3306")
    parser.add_argument("--db_name",      default="jobs_platform")
    parser.add_argument("--db_user",      default="root")
    parser.add_argument("--db_pass",      default="")
    parser.add_argument("--candidate_id", type=int, default=0,
                        help="candidate_profiles.id (local mode only)")
    parser.add_argument("--limit",        type=int, default=10,
                        help="Max recommendations per candidate")
    parser.add_argument("--batch_size",   type=int, default=100,
                        help="Candidates per DB write batch (cache mode)")
    parser.add_argument("--rebuild_cache",  action="store_true",
                        help="Force recompute .npy embedding caches")
    parser.add_argument("--no_crossencoder", action="store_true",
                        help="Skip CrossEncoder reranking (faster, slightly less accurate)")

    args = parser.parse_args()

    if args.mode == "local":
        if args.candidate_id <= 0:
            print(json.dumps({"error": "--candidate_id required in local mode"}))
            sys.exit(1)
        run_local(args)
    else:
        logging.getLogger().setLevel(logging.WARNING)
        run_cache(args)


if __name__ == "__main__":
    main()
