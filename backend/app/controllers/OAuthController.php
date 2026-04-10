<?php
/**
 * OAuthController
 *
 * Google OAuth 2.0 (Authorization Code + OpenID Connect)
 *   GET /api/auth/google              → redirect to Google consent screen
 *   GET /api/auth/google/callback     → exchange code, create/login user, redirect
 *
 * LinkedIn OpenID Connect
 *   GET /api/auth/linkedin            → redirect to LinkedIn consent screen
 *   GET /api/auth/linkedin/callback   → exchange code, create/login user, redirect
 */

class OAuthController
{
    private UserModel $userModel;
    private array     $cfg;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->cfg       = require BASE_PATH . '/config/oauth.php';
    }

    // ── Google ────────────────────────────────────────────────────────────────

    public function googleRedirect(): void
    {
        $state = $this->generateState();

        $params = http_build_query([
            'client_id'     => $this->cfg['google']['client_id'],
            'redirect_uri'  => $this->cfg['google']['redirect_uri'],
            'response_type' => 'code',
            'scope'         => $this->cfg['google']['scopes'],
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        $this->redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    public function googleCallback(): void
    {
        $this->validateState($_GET['state'] ?? '');

        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $this->failRedirect('Google sign-in was cancelled.');
        }

        // Exchange code for tokens
        $tokenData = $this->httpPost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $this->cfg['google']['client_id'],
            'client_secret' => $this->cfg['google']['client_secret'],
            'redirect_uri'  => $this->cfg['google']['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($tokenData['access_token'])) {
            $this->failRedirect('Google token exchange failed.');
        }

        // Get user info
        $info = $this->httpGet(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            $tokenData['access_token']
        );

        if (empty($info['email'])) {
            $this->failRedirect('Could not retrieve email from Google.');
        }

        $user = $this->findOrCreateUser(
            $info['email'],
            $info['name'] ?? $info['given_name'] ?? 'Google User'
        );

        AuthMiddleware::loginUser($user);
        $this->redirect($this->successUrl($user));
    }

    // ── LinkedIn ──────────────────────────────────────────────────────────────

    public function linkedinRedirect(): void
    {
        $state = $this->generateState();

        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->cfg['linkedin']['client_id'],
            'redirect_uri'  => $this->cfg['linkedin']['redirect_uri'],
            'state'         => $state,
            'scope'         => $this->cfg['linkedin']['scopes'],
        ]);

        $this->redirect('https://www.linkedin.com/oauth/v2/authorization?' . $params);
    }

    public function linkedinCallback(): void
    {
        if (!empty($_GET['error'])) {
            $this->failRedirect('LinkedIn sign-in was cancelled.');
        }

        $this->validateState($_GET['state'] ?? '');

        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $this->failRedirect('LinkedIn sign-in was cancelled.');
        }

        // Exchange code for tokens
        $tokenData = $this->httpPost('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->cfg['linkedin']['redirect_uri'],
            'client_id'     => $this->cfg['linkedin']['client_id'],
            'client_secret' => $this->cfg['linkedin']['client_secret'],
        ]);

        if (empty($tokenData['access_token'])) {
            $this->failRedirect('LinkedIn token exchange failed.');
        }

        // LinkedIn OpenID Connect userinfo endpoint
        $info = $this->httpGet(
            'https://api.linkedin.com/v2/userinfo',
            $tokenData['access_token']
        );

        if (empty($info['email'])) {
            $this->failRedirect('Could not retrieve email from LinkedIn.');
        }

        $fullName = trim(($info['given_name'] ?? '') . ' ' . ($info['family_name'] ?? ''));
        if ($fullName === '') {
            $fullName = $info['name'] ?? 'LinkedIn User';
        }

        $user = $this->findOrCreateUser($info['email'], $fullName);

        AuthMiddleware::loginUser($user);
        $this->redirect($this->successUrl($user));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Find user by email or create a new candidate account.
     */
    private function findOrCreateUser(string $email, string $fullName): array
    {
        $email = strtolower(trim($email));
        $user  = $this->userModel->findByEmail($email);

        if ($user) {
            return $user;
        }

        // Create a new candidate account with a random unusable password
        $randomHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        $userId = $this->userModel->createWithProfile(
            $fullName,
            $email,
            $randomHash,
            'candidate',
            []
        );

        return $this->userModel->findById($userId);
    }

    /**
     * Generate and store a CSRF state token.
     */
    private function generateState(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        return $state;
    }

    /**
     * Validate the CSRF state parameter returned by the provider.
     */
    private function validateState(string $state): void
    {
        $expected = $_SESSION['oauth_state'] ?? '';
        unset($_SESSION['oauth_state']);

        if ($state === '' || !hash_equals($expected, $state)) {
            $this->failRedirect('Invalid OAuth state. Please try again.');
        }
    }

    /**
     * POST request via cURL (form-encoded body).
     */
    private function httpPost(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response ?: '{}', true) ?? [];
    }

    /**
     * GET request via cURL with Bearer token.
     */
    private function httpGet(string $url, string $accessToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response ?: '{}', true) ?? [];
    }

    /**
     * Determine post-login redirect URL based on role.
     */
    private function successUrl(array $user): string
    {
        $role = $user['role'] ?? 'candidate';
        $base = '/jobpilot/frontend/pages/auth/oauth-success.html';
        return $base . '?role=' . urlencode($role);
    }

    /**
     * Redirect browser to a URL (clears JSON content-type set by index.php).
     */
    private function redirect(string $url): void
    {
        header('Content-Type: text/html; charset=utf-8', true);
        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Redirect to login page with an error message in the query string.
     */
    private function failRedirect(string $message): void
    {
        $this->redirect(
            '/jobpilot/frontend/pages/auth/login.html?oauth_error=' . urlencode($message)
        );
    }
}
