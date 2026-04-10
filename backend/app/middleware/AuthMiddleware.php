<?php
/**
 * Authentication & Authorization middleware.
 *
 * Usage in controllers:
 *   AuthMiddleware::requireAuth();           // any logged-in user
 *   AuthMiddleware::requireRole('employer'); // role-specific
 */

class AuthMiddleware
{
    /**
     * Require an active session. Terminates with 401 if not authenticated.
     * Returns the session user array on success.
     */
    public static function requireAuth(): array
    {
        if (empty($_SESSION['user_id'])) {
            Response::unauthorized('You must be logged in to access this resource.');
        }

        return [
            'id'    => $_SESSION['user_id'],
            'role'  => $_SESSION['user_role'],
            'name'  => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'] ?? '',
        ];
    }

    /**
     * Require a specific role. Terminates with 401/403 if not met.
     * Returns the session user array on success.
     */
    public static function requireRole(string $role): array
    {
        $user = self::requireAuth();

        if ($user['role'] !== $role) {
            Response::forbidden("Only {$role}s can access this resource.");
        }

        return $user;
    }

    /**
     * Return current user from session, or null if not logged in.
     */
    public static function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id'    => $_SESSION['user_id'],
            'role'  => $_SESSION['user_role'],
            'name'  => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'] ?? '',
        ];
    }

    /**
     * Store user identity in session (called after successful login/register).
     */
    public static function loginUser(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
    }

    /**
     * Verify CSRF token from X-CSRF-Token request header.
     */
    public static function verifyCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            \Response::error('Invalid CSRF token.', 403);
        }
    }

    /**
     * Destroy session (logout).
     */
    public static function logoutUser(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
