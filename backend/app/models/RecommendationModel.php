<?php
/**
 * RecommendationModel — recommendations table (pre-computed by Python).
 */

class RecommendationModel extends BaseModel
{
    /**
     * Return cached recommendation rows for a candidate, ordered by score desc.
     * Each row includes full job + employer info via JOIN.
     */
    public function findByCandidate(int $candidateProfileId, int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));

        return $this->query(
            "SELECT
                r.score, r.reason_text, r.generated_at,
                j.id, j.id AS job_id, j.job_title, j.job_type, j.location,
                j.description,
                j.required_experience_years, j.required_skills,
                j.salary_min_jod, j.salary_max_jod, j.industry,
                j.status, j.application_deadline,
                ep.company_name, ep.logo_path, ep.business_type
             FROM recommendations r
             JOIN jobs j            ON j.id  = r.job_id
             JOIN employer_profiles ep ON ep.id = j.employer_profile_id
             WHERE r.candidate_profile_id = ?
               AND j.status = 'active'
             ORDER BY r.score DESC
             LIMIT {$limit}",
            [$candidateProfileId]
        );
    }

    /**
     * Check when recommendations were last generated for this candidate.
     * Returns null if none exist yet.
     */
    public function lastGeneratedAt(int $candidateProfileId): ?string
    {
        $row = $this->queryOne(
            'SELECT MAX(generated_at) AS last_at
             FROM recommendations
             WHERE candidate_profile_id = ?',
            [$candidateProfileId]
        );
        return $row['last_at'] ?? null;
    }
}
