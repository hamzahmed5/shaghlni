<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

$db    = get_db();
$token = get_param('token');
$done  = false;
$errors = [];

// Validate token
$record = null;
if ($token) {
    $stmt = $db->prepare(
        'SELECT pr.user_id, pr.expires_at FROM password_resets pr WHERE pr.token = ? AND pr.used = 0'
    );
    $stmt->execute([$token]);
    $record = $stmt->fetch();
    if (!$record || strtotime($record['expires_at']) < time()) {
        $record = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $record) {
    if (!verify_csrf(post('csrf_token'))) {
        $errors[] = 'Invalid request.';
    } else {
        $password = post('password');
        $confirm  = post('confirm_password');
        if (strlen($password) < 8)   $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)   $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hashed, $record['user_id']]);
            $db->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')->execute([$token]);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> &mdash; Reset Password</title>
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

        <?php if ($done): ?>
            <div style="text-align:center;">
                <div style="width:64px;height:64px;background:#E7F4EA;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                    <svg width="32" height="32" fill="none" stroke="#0BA02C" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h2>Password Reset!</h2>
                <p>Your password has been successfully updated. You can now sign in with your new password.</p>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-primary btn-block" style="margin-top:20px;">Sign In</a>
            </div>
        <?php elseif (!$record): ?>
            <div style="text-align:center;">
                <h2>Invalid Link</h2>
                <p>This password reset link is invalid or has expired. Please request a new one.</p>
                <a href="<?= SITE_URL ?>/auth/forgot-password.php" class="btn btn-primary btn-block" style="margin-top:20px;">Request New Link</a>
            </div>
        <?php else: ?>
            <h2>Reset Password</h2>
            <p>Enter your new password below.</p>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger"><?= clean($err) ?></div>
            <?php endforeach; ?>

            <form method="POST" class="auth-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= clean($token) ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="New password" required autocomplete="new-password">
                        <button type="button" class="password-toggle">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required autocomplete="new-password">
                        <button type="button" class="password-toggle">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-auth">Set New Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
