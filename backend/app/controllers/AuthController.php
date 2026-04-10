<?php
/**
 * AuthController
 *
 * POST /api/auth/register
 * POST /api/auth/login
 * POST /api/auth/logout
 */

class AuthController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // ── POST /api/auth/register ───────────────────────────────────────────────

    public function register(): void
    {
        $body = Request::body();

        // ── Validate required fields ─────────────────────────────────────────
        $errors = [];

        $fullName = trim($body['full_name'] ?? '');
        $email    = trim($body['email']     ?? '');
        $password = $body['password']        ?? '';
        $role     = $body['role']            ?? '';

        if ($fullName === '') $errors['full_name'] = 'Full name is required.';
        if ($email    === '') $errors['email']     = 'Email is required.';
        if ($password === '') $errors['password']  = 'Password is required.';
        if (!in_array($role, ['candidate', 'employer'], true)) {
            $errors['role'] = 'Role must be candidate or employer.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($errors) {
            Response::error('Validation failed.', 422, $errors);
        }

        // ── Check duplicate ──────────────────────────────────────────────────
        if ($this->userModel->emailExists($email)) {
            Response::error('This email is already registered.', 409);
        }

        // ── Create user + profile ────────────────────────────────────────────
        $profileData = [
            'company_name'  => trim($body['company_name']  ?? ''),
            'industry'      => trim($body['industry']      ?? ''),
            'location'      => trim($body['location']      ?? ''),
            'primary_skills'=> trim($body['primary_skills']?? ''),
        ];

        try {
            $userId = $this->userModel->createWithProfile(
                $fullName,
                strtolower($email),
                $this->userModel->hashPassword($password),
                $role,
                $profileData
            );
        } catch (Throwable $e) {
            Response::error('Registration failed. Please try again.', 500);
        }

        // ── Start session ────────────────────────────────────────────────────
        $user = $this->userModel->findById($userId);
        AuthMiddleware::loginUser($user);

        $csrfToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrfToken;

        $userData = $this->safeUser($user);
        $userData['csrf_token'] = $csrfToken;

        Response::success(
            $userData,
            'Registration successful.',
            201
        );
    }

    // ── POST /api/auth/login ─────────────────────────────────────────────────

    public function login(): void
    {
        $body = Request::body();

        $email    = strtolower(trim($body['email']    ?? ''));
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::error('Email and password are required.', 422);
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            Response::error('Invalid credentials.', 401);
        }

        if ($user['status'] !== 'active') {
            Response::error('Your account has been suspended.', 403);
        }

        AuthMiddleware::loginUser($user);

        $csrfToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrfToken;

        $userData = $this->safeUser($user);
        $userData['csrf_token'] = $csrfToken;

        Response::success($userData, 'Login successful.');
    }

    // ── GET /api/auth/me ─────────────────────────────────────────────────────

    public function me(): void
    {
        $user = AuthMiddleware::requireAuth();
        $full = $this->userModel->findById((int) $user['id']);
        if (!$full) Response::error('User not found.', 404);
        Response::success($this->safeUser($full));
    }

    // ── POST /api/auth/logout ────────────────────────────────────────────────

    public function logout(): void
    {
        AuthMiddleware::requireAuth();
        AuthMiddleware::logoutUser();
        Response::success(null, 'Logged out successfully.');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function safeUser(array $user): array
    {
        unset($user['password_hash']);
        // Add 'name' alias — frontend Session reads user.name for display
        $user['name'] = $user['full_name'];
        return $user;
    }
}
