"""
import_dataset.py — Idempotent CSV importer for both datasets.

Imports:
  business_freelance_dataset.csv  → employer_profiles + jobs
  youth_coworkers_dataset.csv     → candidate_profiles

Usage:
  python import_dataset.py --db_host localhost --db_name jobs_platform \
         --db_user root --db_pass "" \
         --business_csv ../../business_freelance_dataset.csv \
         --youth_csv    ../../youth_coworkers_dataset.csv \
         --batch 500

Idempotency:
  employer_profiles.business_code  is UNIQUE → skip on duplicate
  candidate_profiles.person_code   is UNIQUE → skip on duplicate
  users.email (import_BUS_*/import_PERS_*)   is UNIQUE → skip on duplicate
  jobs: one job per employer_profile; skipped if employer already has jobs
"""

import argparse
import csv
import hashlib
import logging
import sys
import time
from pathlib import Path

# ── Logging ───────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s %(levelname)s %(message)s',
    datefmt='%H:%M:%S',
)
log = logging.getLogger(__name__)


# ── DB helpers ────────────────────────────────────────────────────────────────

def get_conn(host, port, name, user, pw):
    try:
        import pymysql
        return pymysql.connect(
            host=host, port=int(port), db=name,
            user=user, password=pw,
            charset='utf8mb4',
            autocommit=False,
        )
    except ImportError:
        log.error("pymysql not installed. Run: pip install pymysql")
        sys.exit(1)


def batch_insert(conn, sql, rows, batch_size=500):
    """Execute many rows in batches. Returns (inserted, skipped)."""
    inserted = 0
    skipped  = 0
    cur = conn.cursor()
    for i in range(0, len(rows), batch_size):
        chunk = rows[i:i + batch_size]
        for row in chunk:
            try:
                cur.execute(sql, row)
                inserted += 1
            except Exception:
                skipped += 1
        conn.commit()
        log.info(f"  ... {min(i + batch_size, len(rows))}/{len(rows)}")
    cur.close()
    return inserted, skipped


# ── Business / Employer import ────────────────────────────────────────────────

def import_business(conn, csv_path: Path, batch_size: int) -> None:
    log.info(f"=== Importing employers + jobs from {csv_path.name} ===")
    cur = conn.cursor()

    with csv_path.open(encoding='utf-8', newline='') as f:
        reader = csv.DictReader(f)
        rows   = list(reader)

    log.info(f"  Rows in CSV: {len(rows)}")

    user_rows    = []
    profile_rows = []
    job_rows_map = []   # (business_code, job_data)

    # Fake password hash for dataset users (bcrypt of "dataset_import_only")
    # Users from dataset cannot log in — no real password
    FAKE_HASH = '$2y$12$dataset.import.only.hash.placeholder.xxxxxxxxxxxxxxxx'

    for row in rows:
        biz_id    = row['business_id'].strip()
        biz_name  = (row['business_name'] or biz_id).strip()
        email     = f"import_{biz_id.lower()}@dataset.local"
        biz_type  = row.get('business_type', '').strip()
        industry  = row.get('industry',      '').strip()
        location  = row.get('location',      '').strip()

        user_rows.append((
            'employer', biz_name, email, FAKE_HASH,
        ))
        profile_rows.append((
            email, biz_id, biz_name, biz_type, industry, location,
        ))
        job_rows_map.append({
            'business_code': biz_id,
            'job_title':     row.get('job_title',                  '').strip(),
            'industry':      industry,
            'required_experience_years': int(float(row.get('required_experience_years', 0) or 0)),
            'required_skills':           row.get('required_skills', '').strip(),
            'salary_min_jod':            _to_decimal(row.get('salary_min_jod')),
            'salary_max_jod':            _to_decimal(row.get('salary_max_jod')),
            'job_type':                  row.get('job_type',       '').strip(),
            'location':                  location,
        })

    # ── 1. Insert users ───────────────────────────────────────────────────────
    log.info("  Step 1/3: Inserting employer users …")
    ins, sk = batch_insert(
        conn,
        """INSERT IGNORE INTO users (role, full_name, email, password_hash)
           VALUES (%s, %s, %s, %s)""",
        user_rows, batch_size
    )
    log.info(f"  Users: {ins} inserted, {sk} skipped (already exist)")

    # ── 2. Insert employer_profiles ───────────────────────────────────────────
    log.info("  Step 2/3: Inserting employer_profiles …")
    # We need user_id for each profile — fetch by email
    profile_insert_rows = []
    for email, biz_id, biz_name, biz_type, industry, location in profile_rows:
        cur.execute('SELECT id FROM users WHERE email = %s LIMIT 1', (email,))
        u = cur.fetchone()
        if u:
            profile_insert_rows.append((
                u[0], biz_id, biz_name, biz_type, industry, location,
            ))

    ins, sk = batch_insert(
        conn,
        """INSERT IGNORE INTO employer_profiles
               (user_id, business_code, company_name, business_type, industry, location)
           VALUES (%s, %s, %s, %s, %s, %s)""",
        profile_insert_rows, batch_size
    )
    log.info(f"  Employer profiles: {ins} inserted, {sk} skipped")

    # ── 3. Insert jobs ────────────────────────────────────────────────────────
    log.info("  Step 3/3: Inserting jobs …")
    job_rows = []
    for jd in job_rows_map:
        cur.execute(
            """SELECT ep.id FROM employer_profiles ep
               WHERE ep.business_code = %s LIMIT 1""",
            (jd['business_code'],)
        )
        ep = cur.fetchone()
        if not ep:
            continue
        ep_id = ep[0]

        # Idempotent: skip if this employer already has a job
        cur.execute(
            'SELECT id FROM jobs WHERE employer_profile_id = %s LIMIT 1',
            (ep_id,)
        )
        if cur.fetchone():
            continue

        job_rows.append((
            ep_id,
            jd['job_title'],
            jd['industry'],
            jd['required_experience_years'],
            jd['required_skills'],
            jd['salary_min_jod'],
            jd['salary_max_jod'],
            jd['job_type'],
            jd['location'],
        ))

    ins, sk = batch_insert(
        conn,
        """INSERT INTO jobs
               (employer_profile_id, job_title, industry,
                required_experience_years, required_skills,
                salary_min_jod, salary_max_jod, job_type, location)
           VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
        job_rows, batch_size
    )
    log.info(f"  Jobs: {ins} inserted, {sk} already existed")
    cur.close()


# ── Youth / Candidate import ──────────────────────────────────────────────────

def import_youth(conn, csv_path: Path, batch_size: int) -> None:
    log.info(f"=== Importing candidates from {csv_path.name} ===")
    cur = conn.cursor()

    with csv_path.open(encoding='utf-8', newline='') as f:
        reader = csv.DictReader(f)
        rows   = list(reader)

    log.info(f"  Rows in CSV: {len(rows)}")

    FAKE_HASH = '$2y$12$dataset.import.only.hash.placeholder.xxxxxxxxxxxxxxxx'

    user_rows = []
    for row in rows:
        pid   = row['person_id'].strip()
        email = f"import_{pid.lower()}@dataset.local"
        user_rows.append(('candidate', pid, email, FAKE_HASH))

    # ── 1. Insert users ───────────────────────────────────────────────────────
    log.info("  Step 1/2: Inserting candidate users …")
    ins, sk = batch_insert(
        conn,
        """INSERT IGNORE INTO users (role, full_name, email, password_hash)
           VALUES (%s, %s, %s, %s)""",
        user_rows, batch_size
    )
    log.info(f"  Users: {ins} inserted, {sk} skipped")

    # ── 2. Insert candidate_profiles ──────────────────────────────────────────
    log.info("  Step 2/2: Inserting candidate_profiles …")
    profile_rows = []
    for row in rows:
        pid   = row['person_id'].strip()
        email = f"import_{pid.lower()}@dataset.local"

        cur.execute('SELECT id FROM users WHERE email = %s LIMIT 1', (email,))
        u = cur.fetchone()
        if not u:
            continue

        profile_rows.append((
            u[0],
            pid,
            _to_int(row.get('age')),
            (row.get('education_level') or '').strip(),
            _to_int(row.get('years_of_experience')),
            (row.get('primary_skills')      or '').strip(),
            (row.get('employment_status')   or '').strip(),
            (row.get('preferred_job_field') or '').strip(),
            _to_decimal(row.get('salary_expectation_jod')),
            (row.get('location')            or '').strip(),
            'Yes' if str(row.get('willing_to_relocate', '')).strip().lower() == 'yes' else 'No',
        ))

    ins, sk = batch_insert(
        conn,
        """INSERT IGNORE INTO candidate_profiles
               (user_id, person_code, age, education_level,
                years_of_experience, primary_skills, employment_status,
                preferred_job_field, salary_expectation_jod,
                location, willing_to_relocate)
           VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
        profile_rows, batch_size
    )
    log.info(f"  Candidate profiles: {ins} inserted, {sk} skipped")
    cur.close()


# ── Helpers ───────────────────────────────────────────────────────────────────

def _to_decimal(val):
    try:
        return float(val) if val not in (None, '', 'nan') else None
    except (ValueError, TypeError):
        return None

def _to_int(val):
    try:
        return int(float(val)) if val not in (None, '', 'nan') else None
    except (ValueError, TypeError):
        return None


# ── Main ──────────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description='Import CSV datasets into MySQL')
    parser.add_argument('--db_host',      default='localhost')
    parser.add_argument('--db_port',      default='3306')
    parser.add_argument('--db_name',      default='jobs_platform')
    parser.add_argument('--db_user',      default='root')
    parser.add_argument('--db_pass',      default='')
    parser.add_argument('--business_csv', default='../../business_freelance_dataset.csv')
    parser.add_argument('--youth_csv',    default='../../youth_coworkers_dataset.csv')
    parser.add_argument('--batch',        type=int, default=500,
                        help='Rows per commit batch (default 500)')
    parser.add_argument('--only',         choices=['business', 'youth'],
                        help='Import only one dataset')
    args = parser.parse_args()

    conn = get_conn(args.db_host, args.db_port, args.db_name, args.db_user, args.db_pass)
    log.info(f"Connected to {args.db_name}@{args.db_host}")

    t0 = time.time()

    if args.only != 'youth':
        biz_path = Path(args.business_csv)
        if not biz_path.exists():
            log.error(f"File not found: {biz_path}")
        else:
            import_business(conn, biz_path, args.batch)

    if args.only != 'business':
        youth_path = Path(args.youth_csv)
        if not youth_path.exists():
            log.error(f"File not found: {youth_path}")
        else:
            import_youth(conn, youth_path, args.batch)

    conn.close()
    elapsed = time.time() - t0
    log.info(f"=== Done in {elapsed:.1f}s ===")


if __name__ == '__main__':
    main()
