<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db   = get_db();
$uid  = current_user_id();
$user = current_user();
$ep   = get_employer_profile($uid);

if (!$ep || !($ep['setup_complete'] ?? 0)) {
    redirect(site_url('employer/setup.php'));
}

// Stats
$st1 = $db->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id=:uid AND status='active'");
$st1->execute([':uid'=>$uid]);
$open_jobs = (int)$st1->fetchColumn();

$st2 = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id=a.job_id WHERE j.employer_id=:uid");
$st2->execute([':uid'=>$uid]);
$total_apps = (int)$st2->fetchColumn();

$st3 = $db->prepare("SELECT COUNT(*) FROM saved_candidates WHERE employer_profile_id=:eid");
$st3->execute([':eid'=>$ep['id']]);
$saved_cands = (int)$st3->fetchColumn();

$st4 = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id=a.job_id WHERE j.employer_id=:uid AND a.status='shortlisted'");
$st4->execute([':uid'=>$uid]);
$shortlisted = (int)$st4->fetchColumn();

// Recent applications
$recent = $db->prepare("SELECT a.*, j.title AS job_title, u.full_name AS candidate_name, u.avatar, cp.current_position
                        FROM applications a
                        JOIN jobs j ON j.id=a.job_id
                        JOIN candidate_profiles cp ON cp.id=a.candidate_profile_id
                        JOIN users u ON u.id=cp.user_id
                        WHERE j.employer_id=:uid
                        ORDER BY a.applied_at DESC LIMIT 8");
$recent->execute([':uid'=>$uid]);
$recent = $recent->fetchAll();

// My active jobs
$my_jobs = $db->prepare("SELECT j.*, (SELECT COUNT(*) FROM applications WHERE job_id=j.id) AS app_count
                         FROM jobs j WHERE j.employer_id=:uid AND j.status='active'
                         ORDER BY j.created_at DESC LIMIT 5");
$my_jobs->execute([':uid'=>$uid]);
$my_jobs = $my_jobs->fetchAll();

$page_title = 'Employer Dashboard';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <div>
            <h1 class="dash-page-title">Hello, <?= clean($user['full_name']) ?>!</h1>
            <p style="font-size:14px;color:var(--text-muted);">Here's your hiring dashboard.</p>
        </div>
        <a href="<?= site_url('employer/post-job.php') ?>" class="btn btn-primary">Post a Job</a>
    </div>

    <?= render_flash() ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:#E8F0FE;">
                <svg width="22" height="22" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-value"><?= $open_jobs ?></div>
                <div class="stat-card-label">Open Jobs</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background:#E6F7ED;">
                <svg width="22" height="22" fill="none" stroke="#0BA02C" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-value"><?= $total_apps ?></div>
                <div class="stat-card-label">Total Applications</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background:#FFF3E0;">
                <svg width="22" height="22" fill="none" stroke="#FF6636" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-value"><?= $saved_cands ?></div>
                <div class="stat-card-label">Saved Candidates</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background:#F3F0FF;">
                <svg width="22" height="22" fill="none" stroke="#7C5FE0" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-value"><?= $shortlisted ?></div>
                <div class="stat-card-label">Shortlisted</div>
            </div>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="dash-section">
        <div class="section-row">
            <h2 class="dash-section-title">Recent Applications</h2>
            <a href="<?= site_url('employer/applications.php') ?>" class="link-more">View All</a>
        </div>
        <?php if (empty($recent)): ?>
            <div class="empty-state" style="padding:32px 0;">
                <p>No applications yet. <a href="<?= site_url('employer/post-job.php') ?>">Post a job</a> to start receiving applications.</p>
            </div>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Candidate</th><th>Job</th><th>Applied</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $app): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php if ($app['avatar']): ?>
                                        <img src="<?= upload_url($app['avatar']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                    <?php else: ?>
                                        <div class="avatar-placeholder" style="width:36px;height:36px;font-size:13px;"><?= strtoupper(substr($app['candidate_name'],0,2)) ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:600;font-size:14px;"><?= clean($app['candidate_name']) ?></div>
                                        <div style="font-size:12px;color:var(--text-muted);"><?= clean($app['current_position'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= clean($app['job_title']) ?></td>
                            <td><?= date('M j, Y', strtotime($app['applied_at'])) ?></td>
                            <td><span class="status-badge status-<?= strtolower($app['status']) ?>"><?= ucfirst($app['status']) ?></span></td>
                            <td>
                                <div class="action-dropdown">
                                    <button class="action-dropdown-btn">•••</button>
                                    <div class="action-dropdown-menu">
                                        <a href="<?= site_url('employer/application-view.php?id=' . $app['id']) ?>">View Application</a>
                                        <a href="#" onclick="updateStatus(<?= $app['id'] ?>, 'shortlisted')">Shortlist</a>
                                        <a href="#" onclick="updateStatus(<?= $app['id'] ?>, 'rejected')" style="color:var(--danger);">Reject</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- My Jobs -->
    <?php if (!empty($my_jobs)): ?>
    <div class="dash-section">
        <div class="section-row">
            <h2 class="dash-section-title">Active Jobs</h2>
            <a href="<?= site_url('employer/my-jobs.php') ?>" class="link-more">View All</a>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead><tr><th>Job Title</th><th>Type</th><th>Applications</th><th>Deadline</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($my_jobs as $j): ?>
                    <tr>
                        <td><a href="<?= site_url('jobs/job-detail.php?id=' . $j['id']) ?>" class="table-job-title"><?= clean($j['title']) ?></a></td>
                        <td><span class="job-type-badge <?= job_type_class($j['job_type']) ?>"><?= $j['job_type'] ?></span></td>
                        <td><?= $j['app_count'] ?></td>
                        <td><?= date('M j, Y', strtotime($j['expires_at'])) ?></td>
                        <td><span class="status-badge status-active">Active</span></td>
                        <td><a href="<?= site_url('employer/applications.php?job_id=' . $j['id']) ?>" class="btn btn-outline btn-sm">Applications</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function updateStatus(appId, status) {
    if (!confirm('Change status to ' + status + '?')) return;
    fetch('<?= site_url('actions/update-application-status.php') ?>', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'app_id=' + appId + '&status=' + status + '&csrf=<?= csrf_token() ?>'
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert(d.message || 'Error');
    });
}
</script>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
