<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

$db      = get_db();
$email   = get_param('email');
$token   = get_param('token');
$resend  = get_param('resend');
$success = false;
$error   = '';

// Auto-verify if token provided in URL
if ($token) {
    $stmt = $db->prepare(
        'SELECT ev.user_id, ev.expires_at, u.email
         FROM email_verifications ev
         JOIN users u ON u.id = ev.user_id
         WHERE ev.token = ?'
    );
    $stmt->execute([$token]);
    $record = $stmt->fetch();

    if (!$record) {
        $error = 'Invalid or expired verification link.';
    } elseif (strtotime($record['expires_at']) < time()) {
        $error = 'This verification link has expired. Please request a new one.';
    } else {
        $db->prepare('UPDATE users SET is_verified = 1 WHERE id = ?')->execute([$record['user_id']]);
        $db->prepare('DELETE FROM email_verifications WHERE token = ?')->execute([$token]);
        $success = true;
        $email   = $record['email'];
    }
}

if ($resend && $email) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND is_verified = 0');
    $stmt->execute([sanitize_email($email)]);
    $user = $stmt->fetch();
    if ($user) {
        $db->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$user['id']]);
        $new_token = generate_token();
        $expires   = date('Y-m-d H:i:s', time() + VERIFY_TOKEN_LIFETIME * 60);
        $db->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)')->execute([$user['id'], $new_token, $expires]);
        $verify_url = SITE_URL . '/auth/verify-email.php?email=' . urlencode($email) . '&token=' . $new_token;
        $body = '<h2>Verify your email address</h2>
            <p>You requested a new verification link for your ' . SITE_NAME . ' account.</p>
            <a href="' . $verify_url . '" class="btn">Verify Email Address</a>
            <p style="font-size:13px;color:#767F8C;">Or copy this link: <a href="' . $verify_url . '">' . $verify_url . '</a></p>
            <p style="font-size:13px;color:#767F8C;">This link expires in 24 hours.</p>';
        send_mail($email, 'Verify your ' . SITE_NAME . ' account', $body);
        $resent_msg = 'A new verification link has been sent to your email.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> &mdash; Email Verification</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/auth.css">
</head>
<body>
<div class="verify-page">
    <div class="verify-box">
        <div class="verify-icon">
            <?php if ($success): ?>
                <svg width="36" height="36" fill="none" stroke="#0BA02C" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            <?php elseif ($error): ?>
                <svg width="36" height="36" fill="none" stroke="#E05151" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?php else: ?>
                <svg width="36" height="36" fill="none" stroke="#0A65CC" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <h2>Email Verified!</h2>
            <p>Your email address has been successfully verified. You can now sign in to your account.</p>
            <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-primary btn-block btn-lg">Sign In</a>
        <?php elseif ($error): ?>
            <h2>Verification Failed</h2>
            <p><?= clean($error) ?></p>
            <?php if ($email): ?>
                <a href="<?= SITE_URL ?>/auth/verify-email.php?resend=1&email=<?= urlencode($email) ?>" class="btn btn-outline btn-block">Resend Verification Email</a>
            <?php endif; ?>
            <p style="margin-top:16px;"><a href="<?= SITE_URL ?>/auth/login.php" style="color:#0A65CC;">Back to Sign In</a></p>
        <?php else: ?>
            <h2>Check your email</h2>
            <p>
                We sent a verification link to
                <?php if ($email): ?>
                    <span class="email-highlight"><?= clean($email) ?></span>
                <?php else: ?>
                    your email address
                <?php endif; ?>.
                Click the link in that email to verify your account.
            </p>

            <?php if (!empty($resent_msg)): ?>
                <div class="alert alert-success" style="text-align:left;"><?= clean($resent_msg) ?></div>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-primary btn-block">Back to Sign In</a>
            <p class="verify-resend">
                Didn't receive the email?
                <a href="<?= SITE_URL ?>/auth/verify-email.php?resend=1&email=<?= urlencode($email) ?>">Resend</a>
            </p>
        <?php endif; ?>

        <p style="margin-top:24px; font-size:13px; color:#9199A3;">
            <a href="<?= SITE_URL ?>/index.php" style="color:#9199A3;">Return to Homepage</a>
        </p>
    </div>
</div>
</body>
</html>
