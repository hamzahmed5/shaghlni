<?php
/**
 * JobModel — jobs table with employer profile join.
 */

class JobModel extends BaseModel
{
    // ── List with filters + pagination ────────────────────────────────────────

    /**
     * Return paginated jobs with optional filters.
     *
     * Filters (all optional, passed as query params):
     *   search, location, job_type, industry, salary_min, salary_max
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $page  = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;

        [$where, $params] = $this->buildFilters($filters);

        $sql = "
            SELECT
                j.id, j.job_title, j.industry, j.job_type, j.location,
                j.description,
                j.required_experience_years, j.required_skills,
                j.salary_min_jod, j.salary_max_jod,
                j.status, j.application_deadline, j.created_at,
                ep.company_name, ep.logo_path, ep.business_type
            FROM jobs j
            JOIN employer_profiles ep ON ep.id = j.employer_profile_id
            WHERE j.status = 'active' {$where}
            ORDER BY j.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        return $this->normalizeJobs($this->query($sql, $params));
    }

    /**
     * Total count for pagination (same filters, no limit).
     */
    public function countAll(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);

        $sql = "
            SELECT COUNT(*) AS total
            FROM jobs j
            JOIN employer_profiles ep ON ep.id = j.employer_profile_id
            WHERE j.status = 'active' {$where}
        ";

        $row = $this->queryOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }

    // ── Single job detail ─────────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $row = $this->queryOne(
            "SELECT
                j.*,
                ep.company_name, ep.logo_path, ep.business_type,
                ep.industry AS employer_industry, ep.about AS company_about,
                ep.website, ep.location AS employer_location, ep.company_size,
                u.email AS employer_email
             FROM jobs j
             JOIN employer_profiles ep ON ep.id = j.employer_profile_id
             JOIN users u ON u.id = ep.user_id
             WHERE j.id = ? LIMIT 1",
            [$id]
        );
        return $row ? $this->normalizeJob($row) : null;
    }

    // ── Employer-scoped queries ───────────────────────────────────────────────

    public function findByEmployerProfile(int $employerProfileId): array
    {
        return $this->normalizeJobs($this->query(
            "SELECT j.*,
                (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS application_count
             FROM jobs j
             WHERE j.employer_profile_id = ?
             ORDER BY j.created_at DESC",
            [$employerProfileId]
        ));
    }

    public function findByIdAndEmployer(int $jobId, int $employerProfileId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM jobs WHERE id = ? AND employer_profile_id = ? LIMIT 1',
            [$jobId, $employerProfileId]
        );
    }

    // ── Create job ────────────────────────────────────────────────────────────

    public function create(int $employerProfileId, array $data): int
    {
        return (int) $this->insert(
            "INSERT INTO jobs
                (employer_profile_id, job_title, industry, description, requirements,
                 required_experience_years, required_skills,
                 salary_min_jod, salary_max_jod, job_type, location,
                 status, application_deadline)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $employerProfileId,
                $data['job_title'],
                $data['industry']                  ?? null,
                $data['description']               ?? null,
                $data['requirements']              ?? null,
                (int) ($data['required_experience_years'] ?? 0),
                $data['required_skills']           ?? null,
                $data['salary_min_jod']            ?? null,
                $data['salary_max_jod']            ?? null,
                $data['job_type']                  ?? null,
                $data['location']                  ?? null,
                $data['status']                    ?? 'active',
                $data['application_deadline']      ?? null,
            ]
        );
    }

    // ── Update job ────────────────────────────────────────────────────────────

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'job_title', 'industry', 'description', 'requirements',
            'required_experience_years', 'required_skills',
            'salary_min_jod', 'salary_max_jod', 'job_type',
            'location', 'status', 'application_deadline',
        ];
        $fields = array_intersect_key($data, array_flip($allowed));
        if (empty($fields)) {
            return false;
        }
        $set    = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($fields)));
        $params = array_values($fields);
        $params[] = $id;

        $affected = $this->execute("UPDATE jobs SET {$set} WHERE id = ?", $params);
        return $affected > 0;
    }

    // ── Delete job ────────────────────────────────────────────────────────────

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM jobs WHERE id = ?', [$id]);
    }

    // ── Recommended (by job IDs) ──────────────────────────────────────────────

    /**
     * Return full job rows for a list of IDs, preserving order.
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = $this->query(
            "SELECT j.*, ep.company_name, ep.logo_path, ep.business_type
             FROM jobs j
             JOIN employer_profiles ep ON ep.id = j.employer_profile_id
             WHERE j.id IN ({$placeholders}) AND j.status = 'active'",
            $ids
        );

        // Re-sort to match original ID order
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['id']] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($indexed[$id])) {
                $ordered[] = $indexed[$id];
            }
        }

        return $this->normalizeJobs($ordered);
    }

    // ── Field normalizer (adds frontend-expected aliases) ────────────────────
    //
    // Frontend renderJobCard() reads: title, company, company_logo, type, salary
    // DB columns are:               job_title, company_name, logo_path, job_type, salary_min/max_jod
    // We add both so nothing breaks on either side.

    private function normalizeJob(array $row): array
    {
        $row['title']        = $row['job_title']    ?? null;
        $row['company']      = $row['company_name'] ?? null;
        $row['company_logo'] = $row['logo_path']    ?? null;
        $row['type']         = $row['job_type']     ?? null;

        $min = (float) ($row['salary_min_jod'] ?? 0);
        $max = (float) ($row['salary_max_jod'] ?? 0);
        if ($min > 0 && $max > 0) {
            $row['salary'] = number_format($min, 0) . ' – ' . number_format($max, 0) . ' JOD';
        } elseif ($max > 0) {
            $row['salary'] = 'Up to ' . number_format($max, 0) . ' JOD';
        } elseif ($min > 0) {
            $row['salary'] = 'From ' . number_format($min, 0) . ' JOD';
        } else {
            $row['salary'] = null;
        }

        return $row;
    }

    private function normalizeJobs(array $rows): array
    {
        return array_map([$this, 'normalizeJob'], $rows);
    }

    // ── Filter builder ────────────────────────────────────────────────────────

    private function buildFilters(array $filters): array
    {
        $where  = '';
        $params = [];

        if (!empty($filters['search'])) {
            $where   .= " AND (j.job_title LIKE ? OR j.required_skills LIKE ? OR j.description LIKE ?)";
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['location'])) {
            $where   .= ' AND j.location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }

        if (!empty($filters['job_type'])) {
            $where   .= ' AND j.job_type = ?';
            $params[] = $filters['job_type'];
        }

        if (!empty($filters['industry'])) {
            $where   .= ' AND j.industry LIKE ?';
            $params[] = '%' . $filters['industry'] . '%';
        }

        if (isset($filters['salary_min']) && $filters['salary_min'] !== '') {
            $where   .= ' AND j.salary_max_jod >= ?';
            $params[] = (float) $filters['salary_min'];
        }

        if (isset($filters['salary_max']) && $filters['salary_max'] !== '') {
            $where   .= ' AND j.salary_min_jod <= ?';
            $params[] = (float) $filters['salary_max'];
        }

        return [$where, $params];
    }
}
