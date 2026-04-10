<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

if (is_logged_in()) redirect(SITE_URL . '/index.php');

$sent   = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('csrf_token'))) {
        $errors[] = 'Invalid request.';
    } else {
        $email = sanitize_email(post('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $db   = get_db();
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Delete old tokens
                $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
                $token   = generate_token();
                $expires = date('Y-m-d H:i:s', time() + RESET_TOKEN_LIFETIME * 60);
                $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')
                   ->execute([$user['id'], $token, $expires]);
                $reset_url = SITE_URL . '/auth/reset-password.php?token=' . $token;
                $body = '<h2>Reset your password</h2>
                    <p>We received a request to reset the password for your ' . SITE_NAME . ' account.</p>
                    <a href="' . $reset_url . '" class="btn">Reset Password</a>
                    <p style="font-size:13px;color:#767F8C;">Or copy this link: <a href="' . $reset_url . '">' . $reset_url . '</a></p>
                    <p style="font-size:13px;color:#767F8C;">This link expires in ' . RESET_TOKEN_LIFETIME . ' minutes. If you did not request a reset, you can ignore this email.</p>';
                send_mail($email, 'Reset your ' . SITE_NAME . ' password', $body);
            }
            // Always show success (prevents user enumeration)
            $sent = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> &mdash; Forgot Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/auth.css">
</head>
<body>
<div class="auth-centered-page">
    <div class="auth-centered-box">
        <a href="<?= SITE_URL ?>/index.php" class="auth-logo" style="justify-content:center;margin-bottom:28px;">
            <svg width="26" height="26" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="6" fill="#0A65CC"/><path d="M9 13h14v9a2 2 0 01-2 2H11a2 2 0 01-2-2v-9z" fill="white"/><path d="M6 13h20v2H6v-2z" fill="white" opacity="0.6"/><path d="M13 10a1 1 0 011-1h4a1 1 0 011 1v3h-6v-3z" fill="white"/></svg>
            <?= SITE_NAME ?>
        </a>

        <?php if ($sent): ?>
            <div style="text-align:center;">
                <div style="width:64px;height:64px;background:#E7F0FA;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                    <svg width="32" height="32" fill="none" stroke="#0A65CC" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <h2>Check your email</h2>
                <p>If an account with that email exists, we've sent a password reset link. Check your inbox and follow the instructions.</p>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-primary btn-block" style="margin-top:20px;">Back to Sign In</a>
            </div>
        <?php else: ?>
            <h2>Forgot Password</h2>
            <p>Enter the email address associated with your account and we'll send you a link to reset your password.</p>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger"><?= clean($err) ?></div>
            <?php endforeach; ?>

            <form method="POST" class="auth-form" novalidate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email"
                           value="<?= clean(post('email')) ?>" required autocomplete="email">
                </div>
                <button type="submit" class="btn-auth">Send Reset Link</button>
            </form>

            <p style="text-align:center;margin-top:20px;font-size:13px;color:#767F8C;">
                Remember your password? <a href="<?= SITE_URL ?>/auth/login.php" style="color:#0A65CC;">Sign In</a>
            </p>
        <?php endif; ?>
    </div>
</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
