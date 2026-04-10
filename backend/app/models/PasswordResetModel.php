<?php
/**
 * PasswordResetModel — password_reset_tokens table management.
 *
 * Handles secure token generation, lookup, and single-use enforcement
 * for the forgot-password / reset-password flow.
 */

class PasswordResetModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    // ── Table bootstrap ───────────────────────────────────────────────────────

    private function ensureTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id    INT UNSIGNED NOT NULL,
                token      VARCHAR(64)  NOT NULL UNIQUE,
                expires_at DATETIME     NOT NULL,
                used       TINYINT(1)   NOT NULL DEFAULT 0,
                created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token  (token),
                INDEX idx_user   (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
    }

    // ── Token creation ────────────────────────────────────────────────────────

    /**
     * Generate a new reset token for a user.
     *
     * Any previous unused tokens for this user are deleted first so there is
     * always at most one live token per user.
     *
     * @return string  64-character hex token
     */
    public function createToken(int $userId): string
    {
        // Remove any existing (unused) tokens for this user — clean slate
        $this->execute(
            'DELETE FROM password_reset_tokens WHERE user_id = ? AND used = 0',
            [$userId]
        );

        $token = bin2hex(random_bytes(32));  // 64 hex chars, cryptographically secure

        // Use MySQL NOW() + INTERVAL to avoid PHP/MySQL timezone drift
        $this->insert(
            'INSERT INTO password_reset_tokens (user_id, token, expires_at)
             VALUES (?, ?, NOW() + INTERVAL 1 HOUR)',
            [$userId, $token]
        );

        return $token;
    }

    // ── Token lookup ──────────────────────────────────────────────────────────

    /**
     * Find a valid (unused, non-expired) token row.
     *
     * @return array|null  Full row, or null if not found / expired / already used
     */
    public function findValidToken(string $token): ?array
    {
        return $this->queryOne(
            'SELECT * FROM password_reset_tokens
              WHERE token = ?
                AND used = 0
                AND expires_at > NOW()
              LIMIT 1',
            [$token]
        );
    }

    // ── Token invalidation ────────────────────────────────────────────────────

    /**
     * Mark a token as used so it cannot be replayed.
     */
    public function markUsed(int $tokenId): void
    {
        $this->execute(
            'UPDATE password_reset_tokens SET used = 1 WHERE id = ?',
            [$tokenId]
        );
    }
}
