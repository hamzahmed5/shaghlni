<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login();

$user        = current_user();
$role        = current_role();
$user_id     = current_user_id();
$notif_count = count_notifications($user_id);

if ($role === 'candidate') {
    $profile = get_candidate_profile($user_id);
} else {
    $profile = get_employer_profile($user_id);
}

$avatar = $user['avatar'] ?? null;
$display_name = $user['full_name'] ?? $user['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? clean($page_title) . ' — ' . SITE_NAME : SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
    <?php if (isset($extra_css)): foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/<?= $css ?>">
    <?php endforeach; endif; ?>
</head>
<body class="dashboard-page">

<div class="dashboard-layout">

    <header class="dashboard-header">
        <a href="<?= SITE_URL ?>/index.php" class="dh-logo">
            <svg width="30" height="30" viewBox="0 0 32 32" fill="none">
                <rect width="32" height="32" rx="6" fill="#0A65CC"/>
                <path d="M9 13h14v9a2 2 0 01-2 2H11a2 2 0 01-2-2v-9z" fill="white"/>
                <path d="M6 13h20v2H6v-2z" fill="white" opacity="0.6"/>
                <path d="M13 10a1 1 0 011-1h4a1 1 0 011 1v3h-6v-3z" fill="white"/>
            </svg>
            <?= SITE_NAME ?>
        </a>

        <div class="dh-search">
            <svg class="dh-search-icon" width="16" height="16" fill="none" stroke="#767F8C" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" placeholder="Job title, keyword, company" id="dash-search-input">
        </div>

        <div class="dh-actions">
            <?php if ($role === 'employer'): ?>
                <a href="<?= SITE_URL ?>/employer/post-job.php" class="btn-post-job">Post A Jobs</a>
            <?php endif; ?>
            <div class="dh-notification" id="notif-btn">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                <?php if ($notif_count > 0): ?>
                    <span class="dh-notification-badge"><?= $notif_count ?></span>
                <?php endif; ?>
            </div>
            <?php if ($avatar): ?>
                <img src="<?= upload_url('avatars/' . $avatar) ?>" class="dh-avatar" alt="<?= clean($display_name) ?>">
            <?php else: ?>
                <div class="dh-avatar-placeholder dh-avatar"><?= strtoupper(substr($display_name, 0, 1)) ?></div>
            <?php endif; ?>
        </div>
    </header>

    <div class="dashboard-body">
