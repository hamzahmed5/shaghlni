<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

function sc_active(string ...$pages): string {
    global $current_page;
    return in_array($current_page, $pages) ? 'active' : '';
}

// Count job alerts for badge
$alert_count = 0;
if (isset($candidate_id)) {
    $db   = get_db();
    $stmt = $db->prepare('SELECT COUNT(*) FROM job_alerts WHERE candidate_id = ? AND is_active = 1');
    $stmt->execute([$candidate_id ?? 0]);
    $alert_count = (int)$stmt->fetchColumn();
}
?>
<aside class="dashboard-sidebar">
    <p class="sidebar-section-label">Candidate Dashboard</p>
    <ul class="sidebar-nav">
        <li>
            <a href="<?= SITE_URL ?>/candidate/dashboard.php" class="<?= sc_active('dashboard') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Overview
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/candidate/applied-jobs.php" class="<?= sc_active('applied-jobs') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                Applied Jobs
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/candidate/favorite-jobs.php" class="<?= sc_active('favorite-jobs') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                Favorite Jobs
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/candidate/job-alerts.php" class="<?= sc_active('job-alerts') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                Job Alert
                <?php if ($alert_count > 0): ?>
                    <span class="sidebar-badge"><?= $alert_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/candidate/settings/personal.php" class="<?= (strpos($_SERVER['PHP_SELF'], '/settings/') !== false) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                Settings
            </a>
        </li>
    </ul>

    <div class="sidebar-logout">
        <a href="<?= SITE_URL ?>/auth/logout.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Log-out
        </a>
    </div>
</aside>
