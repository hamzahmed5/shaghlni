<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

if (is_logged_in()) {
    redirect(current_role() === 'employer'
        ? SITE_URL . '/employer/dashboard.php'
        : SITE_URL . '/candidate/dashboard.php');
}

$db = get_db();
$total_jobs       = (int)$db->query('SELECT COUNT(*) FROM jobs WHERE status = "active"')->fetchColumn();
$total_companies  = (int)$db->query('SELECT COUNT(*) FROM employer_profiles')->fetchColumn();
$total_candidates = (int)$db->query('SELECT COUNT(*) FROM candidate_profiles WHERE is_public = 1')->fetchColumn();

$errors        = [];
$role_required = in_array(get_param('role'), ['candidate', 'employer']) ? get_param('role') : 'candidate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('csrf_token'))) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $role_required = in_array(post('role_required'), ['candidate', 'employer'])
            ? post('role_required') : '';
        $email    = sanitize_email(post('email'));
        $password = post('password');
        $remember = isset($_POST['remember']);

        if (!$role_required) $errors[] = 'Please select an account type.';
        if (!$email)         $errors[] = 'Please enter your email address.';
        if (!$password)      $errors[] = 'Please enter your password.';

        if (empty($errors)) {
            $stmt = $db->prepare(
                'SELECT id, full_name, username, email, password, role, is_verified, avatar
                 FROM users WHERE email = ?'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Invalid credentials.';
            } elseif ($user['role'] !== $role_required) {
                $errors[] = 'This account belongs to a different account type.';
            } elseif (!$user['is_verified']) {
                $errors[] = 'Please verify your email before signing in. <a href="'
                    . SITE_URL . '/auth/verify-email.php?resend=1&email='
                    . urlencode($email) . '">Resend verification email</a>.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['user']    = [
                    'id'        => $user['id'],
                    'full_name' => $user['full_name'],
                    'username'  => $user['username'],
                    'email'     => $user['email'],
                    'role'      => $user['role'],
                    'avatar'    => $user['avatar'],
                ];

                if ($remember) {
                    $token   = generate_token();
                    $expires = date('Y-m-d H:i:s', time() + 2592000);
                    $db->prepare(
                        'INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE token=VALUES(token), expires_at=VALUES(expires_at)'
                    )->execute([$user['id'], $token, $expires]);
                    setcookie('remember_token', $token, time() + 2592000, '/', '', false, true);
                }

                $default_redirect = $user['role'] === 'employer'
                    ? SITE_URL . '/employer/dashboard.php'
                    : SITE_URL . '/candidate/dashboard.php';
                redirect($_GET['redirect'] ?? $default_redirect);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/auth.css">
</head>
<body>
<div class="auth-page">

    <!-- Left: Form -->
    <div class="auth-left">
        <a href="<?= SITE_URL ?>/index.php" class="auth-logo">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                <rect width="32" height="32" rx="6" fill="#0A65CC"/>
                <path d="M9 13h14v9a2 2 0 01-2 2H11a2 2 0 01-2-2v-9z" fill="white"/>
                <path d="M6 13h20v2H6v-2z" fill="white" opacity="0.6"/>
                <path d="M13 10a1 1 0 011-1h4a1 1 0 011 1v3h-6v-3z" fill="white"/>
            </svg>
            <?= SITE_NAME ?>
        </a>

        <h1 class="auth-heading" id="auth-heading">
            <?= $role_required === 'employer' ? 'Sign in as Company' : 'Sign in as Job Seeker' ?>
        </h1>
        <p class="auth-subtitle">
            Don't have an account? <a href="<?= SITE_URL ?>/auth/register.php">Create Account</a>
        </p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= $err ?></div>
        <?php endforeach; ?>
        <?php if ($msg = get_flash('success')): ?>
            <div class="alert alert-success"><?= clean($msg) ?></div>
        <?php endif; ?>

        <!-- Role selector -->
        <p class="auth-role-label">Sign in as</p>
        <div class="auth-role-picker">
            <button type="button"
                    class="auth-role-card <?= $role_required === 'candidate' ? 'active' : '' ?>"
                    data-role="candidate">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="5"/>
                    <path d="M20 21a8 8 0 10-16 0"/>
                </svg>
                <span class="role-card-title">Job Seeker</span>
                <span class="role-card-desc">Looking for work</span>
            </button>
            <button type="button"
                    class="auth-role-card <?= $role_required === 'employer' ? 'active' : '' ?>"
                    data-role="employer">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
                <span class="role-card-title">Company</span>
                <span class="role-card-desc">Hiring talent</span>
            </button>
        </div>

        <form method="POST" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="role_required" id="role-required-input"
                   value="<?= clean($role_required) ?>">

            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email"
                       placeholder="Email address"
                       value="<?= clean(post('email')) ?>"
                       autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password"
                           placeholder="Password"
                           autocomplete="current-password" required>
                    <button type="button" class="password-toggle" aria-label="Show/hide password">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="auth-options">
                <label class="auth-checkbox">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="<?= SITE_URL ?>/auth/forgot-password.php" class="auth-forgot">Forgot password?</a>
            </div>

            <button type="submit" class="btn-auth">
                Sign In
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </button>
        </form>
    </div>

    <!-- Right: Stats panel -->
    <div class="auth-right">
        <div class="auth-right-overlay"></div>
        <div class="auth-right-content">
            <h2><?= number_format($total_jobs) ?>+ jobs available<br>across Jordan right now.</h2>
            <div class="auth-right-stats">
                <div class="auth-stat">
                    <span class="auth-stat-number"><?= number_format($total_jobs) ?>+</span>
                    <span class="auth-stat-label">Live Jobs</span>
                </div>
                <div class="auth-stat">
                    <span class="auth-stat-number"><?= number_format($total_companies) ?>+</span>
                    <span class="auth-stat-label">Companies</span>
                </div>
                <div class="auth-stat">
                    <span class="auth-stat-number"><?= number_format($total_candidates) ?>+</span>
                    <span class="auth-stat-label">Candidates</span>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
(function () {
    var cards   = document.querySelectorAll('.auth-role-card');
    var input   = document.getElementById('role-required-input');
    var heading = document.getElementById('auth-heading');

    var labels = {
        candidate: 'Sign in as Job Seeker',
        employer:  'Sign in as Company'
    };

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            cards.forEach(function (c) { c.classList.remove('active'); });
            card.classList.add('active');
            input.value   = card.dataset.role;
            heading.textContent = labels[card.dataset.role];
        });
    });
})();
</script>
</body>
</html>
