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

$errors = [];
$role   = in_array(get_param('role'), ['candidate', 'employer']) ? get_param('role') : 'candidate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('csrf_token'))) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $full_name = trim(post('full_name'));
        $username  = trim(post('username'));
        $email     = sanitize_email(post('email'));
        $password  = post('password');
        $confirm   = post('confirm_password');
        $role      = in_array(post('role'), ['candidate', 'employer']) ? post('role') : 'candidate';
        $agree     = isset($_POST['agree']);

        if (!$full_name)                           $errors[] = 'Full name is required.';
        if (!$username || strlen($username) < 3)   $errors[] = 'Username must be at least 3 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if (strlen($password) < 8)                 $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)                 $errors[] = 'Passwords do not match.';
        if (!$agree)                                $errors[] = 'You must agree to the Terms of Service.';

        if (empty($errors)) {
            $dup = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
            $dup->execute([$email, $username]);
            if ($dup->fetch()) {
                $errors[] = 'That email or username is already registered.';
            }
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins    = $db->prepare(
                'INSERT INTO users (full_name, username, email, password, role)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([$full_name, $username, $email, $hashed, $role]);
            $user_id = (int)$db->lastInsertId();

            if ($role === 'candidate') {
                $db->prepare('INSERT INTO candidate_profiles (user_id) VALUES (?)')->execute([$user_id]);
            } else {
                $db->prepare('INSERT INTO employer_profiles (user_id, company_name) VALUES (?, "")')->execute([$user_id]);
            }

            $db->prepare('UPDATE users SET is_verified = 1 WHERE id = ?')->execute([$user_id]);
            flash('success', 'Account created successfully. You can now sign in.');
            redirect(SITE_URL . '/auth/login.php?role=' . $role);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> — Create Account</title>
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
            <?= $role === 'employer' ? 'Create a Company Account' : 'Create a Job Seeker Account' ?>
        </h1>
        <p class="auth-subtitle">
            Already have an account? <a href="<?= SITE_URL ?>/auth/login.php">Sign In</a>
        </p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= $err ?></div>
        <?php endforeach; ?>

        <!-- Role selector -->
        <p class="auth-role-label">I am a</p>
        <div class="auth-role-picker">
            <button type="button"
                    class="auth-role-card <?= $role === 'candidate' ? 'active' : '' ?>"
                    data-role="candidate">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="5"/>
                    <path d="M20 21a8 8 0 10-16 0"/>
                </svg>
                <span class="role-card-title">Job Seeker</span>
                <span class="role-card-desc">Looking for work</span>
            </button>
            <button type="button"
                    class="auth-role-card <?= $role === 'employer' ? 'active' : '' ?>"
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
            <input type="hidden" name="role" id="role-input" value="<?= clean($role) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           placeholder="Full name"
                           value="<?= clean(post('full_name')) ?>"
                           autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           placeholder="Username"
                           value="<?= clean(post('username')) ?>"
                           autocomplete="username" required>
                </div>
            </div>

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
                           placeholder="Minimum 8 characters"
                           autocomplete="new-password" required>
                    <button type="button" class="password-toggle" aria-label="Toggle password">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Repeat password"
                           autocomplete="new-password" required>
                    <button type="button" class="password-toggle" aria-label="Toggle password">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <p class="auth-agree">
                <input type="checkbox" name="agree" id="agree" <?= isset($_POST['agree']) ? 'checked' : '' ?>>
                I have read and agree to the
                <a href="<?= SITE_URL ?>/pages/terms.php">Terms of Service</a>
                and <a href="<?= SITE_URL ?>/pages/privacy.php">Privacy Policy</a>.
            </p>

            <button type="submit" class="btn-auth">
                Create Account
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
            <h2>Join thousands of professionals<br>already using <?= SITE_NAME ?>.</h2>
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
    var input   = document.getElementById('role-input');
    var heading = document.getElementById('auth-heading');

    var labels = {
        candidate: 'Create a Job Seeker Account',
        employer:  'Create a Company Account'
    };

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            cards.forEach(function (c) { c.classList.remove('active'); });
            card.classList.add('active');
            input.value         = card.dataset.role;
            heading.textContent = labels[card.dataset.role];
        });
    });
})();
</script>
</body>
</html>
