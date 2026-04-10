<?php
/**
 * UserModel — users table + profile creation on registration.
 */

class UserModel extends BaseModel
{
    // ── Lookup ───────────────────────────────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        return $this->queryOne(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [$email]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->queryOne(
            'SELECT id, role, full_name, email, phone, status, created_at
               FROM users WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    // ── Creation ─────────────────────────────────────────────────────────────

    /**
     * Create a user row and the matching profile row in one transaction.
     * Returns the new user ID.
     */
    public function createWithProfile(
        string $fullName,
        string $email,
        string $passwordHash,
        string $role,
        array  $profileData = []
    ): int {
        $this->db->beginTransaction();

        try {
            $userId = (int) $this->insert(
                'INSERT INTO users (role, full_name, email, password_hash)
                 VALUES (?, ?, ?, ?)',
                [$role, $fullName, $email, $passwordHash]
            );

            if ($role === 'candidate') {
                $this->insert(
                    'INSERT INTO candidate_profiles (user_id, location, primary_skills)
                     VALUES (?, ?, ?)',
                    [
                        $userId,
                        $profileData['location']       ?? null,
                        $profileData['primary_skills'] ?? null,
                    ]
                );
            } elseif ($role === 'employer') {
                $companyName = $profileData['company_name'] ?? $fullName;
                $this->insert(
                    'INSERT INTO employer_profiles (user_id, company_name, industry, location)
                     VALUES (?, ?, ?, ?)',
                    [
                        $userId,
                        $companyName,
                        $profileData['industry'] ?? null,
                        $profileData['location'] ?? null,
                    ]
                );
            }

            $this->db->commit();
            return $userId;

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function emailExists(string $email): bool
    {
        $row = $this->queryOne(
            'SELECT id FROM users WHERE email = ? LIMIT 1',
            [$email]
        );
        return $row !== null;
    }

    // ── Password ─────────────────────────────────────────────────────────────

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $this->execute(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [$hash, $userId]
        );
    }
}
