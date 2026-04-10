<?php
/**
 * EmployerProfileModel — employer_profiles table.
 */

class EmployerProfileModel extends BaseModel
{
    // ── Profile ───────────────────────────────────────────────────────────────

    public function findByUserId(int $userId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM employer_profiles WHERE user_id = ? LIMIT 1',
            [$userId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->queryOne(
            'SELECT * FROM employer_profiles WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Return all profile columns for an employer, joined with user full_name and email.
     */
    public function getProfile(int $employerProfileId): ?array
    {
        return $this->queryOne(
            "SELECT ep.*, u.full_name, u.email
             FROM employer_profiles ep
             JOIN users u ON u.id = ep.user_id
             WHERE ep.id = ?
             LIMIT 1",
            [$employerProfileId]
        );
    }

    /**
     * Update allowed profile fields. Only columns present in $data are touched.
     * Allowed: company_name, industry, location, website, company_about, company_size, business_type.
     * Returns true on success (or when $data is empty — caller skips DB call instead).
     */
    public function updateProfile(int $employerProfileId, array $data): bool
    {
        $allowed = ['company_name', 'industry', 'location', 'website', 'company_about', 'company_size', 'business_type', 'founded_year'];
        $set     = [];
        $params  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[]    = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($set)) {
            return true;
        }

        $params[] = $employerProfileId;

        $affected = $this->execute(
            'UPDATE employer_profiles SET ' . implode(', ', $set) . ' WHERE id = ?',
            $params
        );

        return $affected >= 0;
    }

    // ── Unified update (whitelist-based) ─────────────────────────────────────

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'company_name', 'industry', 'company_size', 'founded_year',
            'website', 'description', 'location', 'phone',
            'linkedin_url', 'twitter_url', 'facebook_url',
        ];
        $fields = array_intersect_key($data, array_flip($allowed));
        if (empty($fields)) return false;
        $set  = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
        $vals = array_values($fields);
        $vals[] = $id;
        $stmt = \DB::get()->prepare("UPDATE employer_profiles SET $set WHERE id = ?");
        $stmt->execute($vals);
        return $stmt->rowCount() > 0;
    }

    // ── Public employer listing ───────────────────────────────────────────────

    public function getAll(int $page = 1, int $limit = 12): array
    {
        $offset = ($page - 1) * $limit;
        return $this->query(
            "SELECT ep.id, ep.company_name, ep.industry, ep.location,
                    ep.company_size, ep.logo_path, ep.about, ep.website,
                    ep.business_type,
                    COUNT(j.id) AS job_count
             FROM employer_profiles ep
             LEFT JOIN jobs j ON j.employer_profile_id = ep.id AND j.status = 'active'
             WHERE ep.company_name IS NOT NULL AND ep.company_name != ''
             GROUP BY ep.id
             ORDER BY job_count DESC, ep.id ASC
             LIMIT {$limit} OFFSET {$offset}",
            []
        );
    }

    public function countAll(): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM employer_profiles
             WHERE company_name IS NOT NULL AND company_name != ''",
            []
        );
        return (int) ($row['total'] ?? 0);
    }

    public function findPublicById(int $id): ?array
    {
        return $this->queryOne(
            "SELECT ep.id, ep.company_name, ep.industry, ep.location,
                    ep.company_size, ep.logo_path, ep.about, ep.website,
                    ep.business_type,
                    COUNT(j.id) AS job_count
             FROM employer_profiles ep
             LEFT JOIN jobs j ON j.employer_profile_id = ep.id AND j.status = 'active'
             WHERE ep.id = ?
             GROUP BY ep.id
             LIMIT 1",
            [$id]
        );
    }

    // ── Stats for dashboard ───────────────────────────────────────────────────

    public function countActiveJobs(int $employerProfileId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM jobs
             WHERE employer_profile_id = ? AND status = 'active'",
            [$employerProfileId]
        );
        return (int) ($row['total'] ?? 0);
    }

    public function countTotalJobs(int $employerProfileId): int
    {
        $row = $this->queryOne(
            'SELECT COUNT(*) AS total FROM jobs WHERE employer_profile_id = ?',
            [$employerProfileId]
        );
        return (int) ($row['total'] ?? 0);
    }
}
