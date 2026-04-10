<?php
/**
 * ApplicationModel — applications table.
 */

class ApplicationModel extends BaseModel
{
    // ── Create ────────────────────────────────────────────────────────────────

    public function create(
        int     $jobId,
        int     $candidateProfileId,
        ?int    $cvId        = null,
        ?string $coverLetter = null
    ): int {
        return (int) $this->insert(
            "INSERT INTO applications (job_id, candidate_profile_id, cv_id, cover_letter)
             VALUES (?, ?, ?, ?)",
            [$jobId, $candidateProfileId, $cvId, $coverLetter]
        );
    }

    // ── Duplicate check ───────────────────────────────────────────────────────

    public function exists(int $jobId, int $candidateProfileId): bool
    {
        $row = $this->queryOne(
            'SELECT id FROM applications WHERE job_id = ? AND candidate_profile_id = ? LIMIT 1',
            [$jobId, $candidateProfileId]
        );
        return $row !== null;
    }

    // ── Candidate view ────────────────────────────────────────────────────────

    public function findByCandidate(int $candidateProfileId): array
    {
        return $this->query(
            "SELECT
                a.id, a.status, a.cover_letter, a.created_at,
                j.id AS job_id, j.job_title, j.job_type, j.location,
                j.salary_min_jod, j.salary_max_jod,
                ep.company_name, ep.logo_path
             FROM applications a
             JOIN jobs j           ON j.id  = a.job_id
             JOIN employer_profiles ep ON ep.id = j.employer_profile_id
             WHERE a.candidate_profile_id = ?
             ORDER BY a.created_at DESC",
            [$candidateProfileId]
        );
    }

    public function countByCandidate(int $candidateProfileId): int
    {
        $row = $this->queryOne(
            'SELECT COUNT(*) AS total FROM applications WHERE candidate_profile_id = ?',
            [$candidateProfileId]
        );
        return (int) ($row['total'] ?? 0);
    }

    // ── Employer view ─────────────────────────────────────────────────────────

    public function findByEmployerProfile(int $employerProfileId): array
    {
        return $this->query(
            "SELECT
                a.id, a.status, a.cover_letter, a.created_at,
                j.id AS job_id, j.job_title,
                cp.id AS candidate_profile_id,
                u.full_name AS candidate_name,
                u.email     AS candidate_email,
                cp.years_of_experience, cp.primary_skills, cp.location AS candidate_location,
                cv.file_path AS cv_path, cv.original_name AS cv_filename
             FROM applications a
             JOIN jobs j                ON j.id  = a.job_id
             JOIN employer_profiles ep  ON ep.id = j.employer_profile_id
             JOIN candidate_profiles cp ON cp.id = a.candidate_profile_id
             JOIN users u               ON u.id  = cp.user_id
             LEFT JOIN cvs cv           ON cv.id = a.cv_id
             WHERE ep.id = ?
             ORDER BY a.created_at DESC",
            [$employerProfileId]
        );
    }

    public function findByIdAndEmployer(int $id, int $employerProfileId): ?array
    {
        return $this->queryOne(
            "SELECT
                a.id, a.status, a.cover_letter, a.created_at,
                j.id AS job_id, j.job_title,
                cp.id AS candidate_profile_id,
                u.full_name AS candidate_name,
                u.email     AS candidate_email,
                cp.primary_skills, cp.location AS candidate_location,
                cv.file_path AS cv_path, cv.original_name AS cv_filename
             FROM applications a
             JOIN jobs j                ON j.id  = a.job_id
             JOIN employer_profiles ep  ON ep.id = j.employer_profile_id
             JOIN candidate_profiles cp ON cp.id = a.candidate_profile_id
             JOIN users u               ON u.id  = cp.user_id
             LEFT JOIN cvs cv           ON cv.id = a.cv_id
             WHERE a.id = ? AND ep.id = ?",
            [$id, $employerProfileId]
        );
    }

    public function countByEmployerProfile(int $employerProfileId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             WHERE j.employer_profile_id = ?",
            [$employerProfileId]
        );
        return (int) ($row['total'] ?? 0);
    }

    public function countNewByEmployerProfile(int $employerProfileId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             WHERE j.employer_profile_id = ? AND a.status = 'pending'",
            [$employerProfileId]
        );
        return (int) ($row['total'] ?? 0);
    }

    // ── Status update ─────────────────────────────────────────────────────────

    public function updateStatus(int $id, string $status): bool
    {
        $valid = ['pending', 'reviewing', 'shortlisted', 'rejected', 'hired'];
        if (!in_array($status, $valid, true)) {
            return false;
        }
        $affected = $this->execute(
            'UPDATE applications SET status = ? WHERE id = ?',
            [$status, $id]
        );
        return $affected > 0;
    }
}
