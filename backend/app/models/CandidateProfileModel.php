<?php
/**
 * CandidateProfileModel — candidate_profiles, favorites, cvs tables.
 */

class CandidateProfileModel extends BaseModel
{
    // ── Profile ───────────────────────────────────────────────────────────────

    public function findByUserId(int $userId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM candidate_profiles WHERE user_id = ? LIMIT 1',
            [$userId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->queryOne(
            'SELECT * FROM candidate_profiles WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    // ── Favorites ─────────────────────────────────────────────────────────────

    public function getFavorites(int $candidateProfileId): array
    {
        return $this->query(
            "SELECT
                f.id AS favorite_id, f.created_at AS favorited_at,
                j.id AS job_id, j.job_title, j.job_type, j.location,
                j.salary_min_jod, j.salary_max_jod, j.required_skills, j.status,
                ep.company_name, ep.logo_path
             FROM favorites f
             JOIN jobs j            ON j.id  = f.job_id
             JOIN employer_profiles ep ON ep.id = j.employer_profile_id
             WHERE f.candidate_profile_id = ?
             ORDER BY f.created_at DESC",
            [$candidateProfileId]
        );
    }

    public function countFavorites(int $candidateProfileId): int
    {
        $row = $this->queryOne(
            'SELECT COUNT(*) AS total FROM favorites WHERE candidate_profile_id = ?',
            [$candidateProfileId]
        );
        return (int) ($row['total'] ?? 0);
    }

    public function isFavorite(int $candidateProfileId, int $jobId): bool
    {
        $row = $this->queryOne(
            'SELECT id FROM favorites WHERE candidate_profile_id = ? AND job_id = ? LIMIT 1',
            [$candidateProfileId, $jobId]
        );
        return $row !== null;
    }

    public function addFavorite(int $candidateProfileId, int $jobId): void
    {
        if (!$this->isFavorite($candidateProfileId, $jobId)) {
            $this->insert(
                'INSERT INTO favorites (candidate_profile_id, job_id) VALUES (?, ?)',
                [$candidateProfileId, $jobId]
            );
        }
    }

    public function removeFavorite(int $candidateProfileId, int $jobId): void
    {
        $db = \DB::get();
        $db->prepare('DELETE FROM favorites WHERE candidate_profile_id = ? AND job_id = ?')
           ->execute([$candidateProfileId, $jobId]);
    }

    // ── CVs ───────────────────────────────────────────────────────────────────

    public function getCvs(int $candidateProfileId): array
    {
        return $this->query(
            'SELECT * FROM cvs WHERE candidate_profile_id = ? ORDER BY is_default DESC, created_at DESC',
            [$candidateProfileId]
        );
    }

    public function countCvs(int $candidateProfileId): int
    {
        $row = $this->queryOne(
            'SELECT COUNT(*) AS total FROM cvs WHERE candidate_profile_id = ?',
            [$candidateProfileId]
        );
        return (int) ($row['total'] ?? 0);
    }

    public function getDefaultCv(int $candidateProfileId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM cvs WHERE candidate_profile_id = ? AND is_default = 1 LIMIT 1',
            [$candidateProfileId]
        );
    }

    // ── Profile update ────────────────────────────────────────────────────────

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'headline', 'bio', 'location', 'education_level',
            'preferred_job_field', 'primary_skills', 'years_of_experience',
            'willing_to_relocate',
        ];
        $fields = array_intersect_key($data, array_flip($allowed));
        if (empty($fields)) return false;
        $set  = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
        $vals = array_values($fields);
        $vals[] = $id;
        $stmt = \DB::get()->prepare("UPDATE candidate_profiles SET $set WHERE id = ?");
        $stmt->execute($vals);
        return $stmt->rowCount() > 0;
    }

    public function getCvList(int $candidateProfileId): array
    {
        $stmt = \DB::get()->prepare(
            'SELECT id, file_path, original_name, uploaded_at
             FROM candidate_cvs
             WHERE candidate_profile_id = ?
             ORDER BY uploaded_at DESC'
        );
        $stmt->execute([$candidateProfileId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Insert a CV record. If it is the first CV for this candidate, mark it default.
     */
    public function addCv(int $candidateProfileId, string $filePath, string $originalName): int
    {
        $isFirst   = $this->countCvs($candidateProfileId) === 0 ? 1 : 0;
        $cvId      = (int) $this->insert(
            'INSERT INTO cvs (candidate_profile_id, file_path, original_name, is_default) VALUES (?, ?, ?, ?)',
            [$candidateProfileId, $filePath, $originalName, $isFirst]
        );
        return $cvId;
    }
}
