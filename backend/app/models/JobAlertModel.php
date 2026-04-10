<?php
/**
 * JobAlertModel — job_alerts table.
 *
 * Each alert belongs to a candidate_profile. All mutations are scoped to
 * the owning candidate so no cross-user leakage is possible.
 */

class JobAlertModel extends BaseModel
{
    // ── Read ──────────────────────────────────────────────────────────────────

    public function findByCandidate(int $cpId): array
    {
        return $this->query(
            'SELECT * FROM job_alerts WHERE candidate_profile_id = ? ORDER BY created_at DESC',
            [$cpId]
        );
    }

    /** Ownership-scoped lookup — returns null if alert doesn't belong to cpId. */
    public function findByIdAndCandidate(int $id, int $cpId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM job_alerts WHERE id = ? AND candidate_profile_id = ? LIMIT 1',
            [$id, $cpId]
        );
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(int $cpId, array $data): int
    {
        return (int) $this->insert(
            'INSERT INTO job_alerts
                (candidate_profile_id, title, keywords, location, job_type, frequency, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)',
            [
                $cpId,
                $data['title'],
                $data['keywords']  ?? null,
                $data['location']  ?? null,
                $data['job_type']  ?? null,
                $data['frequency'] ?? 'daily',
            ]
        );
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(int $id, array $data): void
    {
        $allowed = ['title', 'keywords', 'location', 'job_type', 'frequency', 'is_active'];
        $fields  = array_intersect_key($data, array_flip($allowed));
        if (empty($fields)) {
            return;
        }
        $set    = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($fields)));
        $params = array_values($fields);
        $params[] = $id;

        $this->execute("UPDATE job_alerts SET {$set} WHERE id = ?", $params);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM job_alerts WHERE id = ?', [$id]);
    }
}
