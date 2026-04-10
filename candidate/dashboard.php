<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('candidate');

$db   = get_db();
$uid  = current_user_id();
$user = current_user();
$cp   = get_candidate_profile($uid);

if (!$cp) {
    // Profile not set up — redirect to settings
    redirect(site_url('candidate/settings/personal.php'));
}

// Stats
$total_applied = (int)$db->prepare("SELECT COUNT(*) FROM applications WHERE candidate_profile_id = :cid")->execute([':cid'=>$cp['id']]) ? $db->query("SELECT COUNT(*) FROM applications WHERE candidate_profile_id = {$cp['id']}")->fetchColumn() : 0;

$stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE candidate_profile_id = :cid");
$stmt->execute([':cid'=>$cp['id']]);
$total_applied = (int)$stmt->fetchColumn();

$stmt2 = $db->prepare("SELECT COUNT(*) FROM saved_jobs WHERE candidate_profile_id = :cid");
$stmt2->execute([':cid'=>$cp['id']]);
$total_saved = (int)$stmt2->fetchColumn();

$stmt3 = $db->prepare("SELECT COUNT(*) FROM job_alerts WHERE candidate_profile_id = :cid AND is_active=1");
$stmt3->execute([':cid'=>$cp['id']]);
$total_alerts = (int)$stmt3->fetchColumn();

$stmt4 = $db->prepare("SELECT COUNT(*) FROM applications WHERE candidate_profile_id = :cid AND status='shortlisted'");
$stmt4->execute([':cid'=>$cp['id']]);
$total_shortlisted = (int)$stmt4->fetchColumn();

// Recent applications
$recent_apps = $db->prepare("SELECT a.*, j.title AS job_title, j.job_type, j.location, ep.company_name, ep.logo
                              FROM applications a
                              JOIN jobs j ON j.id = a.job_id
                              LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
                              WHERE a.candidate_profile_id = :cid
                              ORDER BY a.applied_at DESC LIMIT 5");
$recent_apps->execute([':cid'=>$cp['id']]);
$recent_apps = $recent_apps->fetchAll();

// Recommended jobs
$recommended = get_recommended_jobs($cp['id'], 4);

$page_title = 'Dashboard';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_candidate.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <div>
            <h1 class="dash-page-title">Hello, <?= clean($user['full_name']) ?>!</h1>
            <p style="font-size:14px;color:var(--text-muted);">Here's your job search overview for today.</p>
        </div>
        <a href="<?= site_url('jobs/find-job.php') ?>" class="btn btn-primary">Find a Job</a>
    </div>

    <?= render_flash() ?>

    <!-- Profile Completeness Alert -->
    <?php
    $complete_fields = 0;
    $fields = ['bio','current_position','city','education_level','experience_level'];
    foreach ($fields as $f) if (!empty($cp[$f])) $complete_fields++;
    $completeness = round($complete_fields / count($fields) * 100);
    if ($completeness < 100):
    ?>
    <div class="profile-alert">
        <div>
            <strong>Complete your profile</strong> – Your profile is <?= $completeness ?>% complete. A complete profile gets 3x more views from employers.
        </div>
        <a href="<?= site_url('candidate/settings/personal.php') ?>" class="btn btn-primary btn-sm">Update Profile</a>
    </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:#E8F0FE;">
                <svg width="22" height="22" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-value"><?= $total_applied ?></div>
                <div class="stat-card-label">Applied Jobs</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background:#E6F7ED;">
                <svg width="22" height="22" fill="none" stroke="#0BA02C" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-value"><?= $total_saved ?></div>
                <div class="stat-card-label">Favorite Jobs</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background:#FFF3E0;">
                <svg width="22" height="22" fill="none" stroke="#FF6636" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-value"><?= $total_alerts ?></div>
                <div class="stat-card-label">Job Alerts</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background:#F3F0FF;">
                <svg width="22" height="22" fill="none" stroke="#7C5FE0" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-value"><?= $total_shortlisted ?></div>
                <div class="stat-card-label">Shortlisted</div>
            </div>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="dash-section">
        <div class="section-row">
            <h2 class="dash-section-title">Recent Applications</h2>
            <a href="<?= site_url('candidate/applied-jobs.php') ?>" class="link-more">View All</a>
        </div>
        <?php if (empty($recent_apps)): ?>
            <div class="empty-state" style="padding:40px 0;">
                <p>You haven't applied to any jobs yet.</p>
                <a href="<?= site_url('jobs/find-job.php') ?>" class="btn btn-primary" style="margin-top:12px;">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Applied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_apps as $app): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('jobs/job-detail.php?id=' . $app['job_id']) ?>" class="table-job-title"><?= clean($app['job_title']) ?></a>
                                <div class="table-job-location"><?= clean($app['location']) ?></div>
                            </td>
                            <td><?= clean($app['company_name']) ?></td>
                            <td><span class="job-type-badge <?= job_type_class($app['job_type']) ?>"><?= $app['job_type'] ?></span></td>
                            <td><?= date('M j, Y', strtotime($app['applied_at'])) ?></td>
                            <td><span class="status-badge status-<?= strtolower($app['status']) ?>"><?= ucfirst($app['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recommended Jobs -->
    <?php if (!empty($recommended)): ?>
    <div class="dash-section">
        <div class="section-row">
            <h2 class="dash-section-title">Recommended For You</h2>
            <a href="<?= site_url('jobs/find-job.php') ?>" class="link-more">View All</a>
        </div>
        <div class="job-cards-grid" style="grid-template-columns:repeat(2,1fr);">
            <?php foreach ($recommended as $rj): ?>
                <?= render_job_card($rj, true, $cp['id']) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.dashboard-main -->

</div><!-- /.dashboard-body -->
</div><!-- /.dashboard-layout -->
<?php include __DIR__ . '/../includes/footer.php'; ?>
