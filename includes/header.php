<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';
start_session();

$user         = current_user();
$is_logged    = is_logged_in();
$role         = current_role();
$notif_count  = $is_logged ? count_notifications(current_user_id()) : 0;

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

function nav_class(string $page, string $current): string {
    return $current === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="<?= $LANG ?>" dir="<?= $LANG_DIR ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? clean($page_title) . ' — ' . SITE_NAME : SITE_NAME . ' — Job Portal' ?></title>
    <meta name="description" content="<?= isset($page_desc) ? clean($page_desc) : 'Find your perfect job on Jobpilot — the leading recruitment platform.' ?>">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    <?php if (isset($extra_css)): foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/<?= $css ?>">
    <?php endforeach; endif; ?>
    <script>
      // Apply saved theme before page renders to avoid flash
      (function(){
        const t = localStorage.getItem('jp_theme');
        if (t === 'dark') document.documentElement.setAttribute('data-theme','dark');
      })();
    </script>
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a href="<?= SITE_URL ?>/index.php" class="logo">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <rect width="32" height="32" rx="6" fill="#0A65CC"/>
                    <path d="M9 13h14v9a2 2 0 01-2 2H11a2 2 0 01-2-2v-9z" fill="white"/>
                    <path d="M6 13h20v2H6v-2z" fill="white" opacity="0.6"/>
                    <path d="M13 10a1 1 0 011-1h4a1 1 0 011 1v3h-6v-3z" fill="white"/>
                </svg>
                <?= SITE_NAME ?>
            </a>

            <nav class="main-nav" id="main-nav">
                <ul>
                    <li><a href="<?= SITE_URL ?>/index.php" class="<?= nav_class('index', $current_page) ?>"><?= t('nav.home') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/jobs/find-job.php" class="<?= ($current_dir === 'jobs') ? 'active' : '' ?>"><?= t('nav.find_job') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/employers/browse.php" class="<?= ($current_dir === 'employers') ? 'active' : '' ?>"><?= t('nav.find_employers') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/candidates/browse.php" class="<?= ($current_dir === 'candidates') ? 'active' : '' ?>"><?= t('nav.candidates') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/pages/pricing.php" class="<?= nav_class('pricing', $current_page) ?>"><?= t('nav.pricing') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/pages/contact.php" class="<?= nav_class('contact', $current_page) ?>"><?= t('nav.support') ?></a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <!-- Dark mode toggle -->
                <button class="dark-toggle" id="dark-toggle" title="Toggle dark mode" aria-label="Toggle dark mode">
                    <svg class="icon-moon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                    <svg class="icon-sun" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </button>

                <!-- Language switcher -->
                <div class="lang-switcher">
                    <a href="<?= lang_url('en') ?>" class="<?= lang() === 'en' ? 'active' : '' ?>">EN</a>
                    <a href="<?= lang_url('ar') ?>" class="<?= lang() === 'ar' ? 'active' : '' ?>">AR</a>
                </div>

                <?php if ($is_logged): ?>
                    <?php if ($role === 'candidate'): ?>
                        <a href="<?= SITE_URL ?>/candidate/dashboard.php" class="btn btn-outline btn-sm"><?= t('header.dashboard') ?></a>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/employer/dashboard.php" class="btn btn-outline btn-sm"><?= t('header.dashboard') ?></a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-primary btn-sm"><?= t('header.sign_out') ?></a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-outline btn-sm"><?= t('header.sign_in') ?></a>
                    <a href="<?= SITE_URL ?>/employer/post-job.php" class="btn btn-primary btn-sm"><?= t('header.post_job') ?></a>
                <?php endif; ?>
            </div>

            <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
