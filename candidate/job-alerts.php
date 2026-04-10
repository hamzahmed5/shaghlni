<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('candidate');

$db  = get_db();
$uid = current_user_id();
$cp  = get_candidate_profile($uid);

// Handle create alert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'create') {
    verify_csrf(post('csrf_token'));
    $title    = clean(post('alert_title'));
    $keyword  = clean(post('keyword'));
    $location = clean(post('location'));
    $job_type = clean(post('job_type'));
    $freq     = clean(post('frequency')) ?: 'daily';
    if ($title) {
        $ins = $db->prepare("INSERT INTO job_alerts (candidate_profile_id, title, keyword, location, job_type, frequency, is_active) VALUES (:cid,:t,:kw,:loc,:jt,:freq,1)");
        $ins->execute([':cid'=>$cp['id'],':t'=>$title,':kw'=>$keyword,':loc'=>$location,':jt'=>$job_type,':freq'=>$freq]);
        flash('success', 'Job alert created successfully.');
    }
    redirect(site_url('candidate/job-alerts.php'));
}

// Handle delete
if (isset($_GET['delete'])) {
    verify_csrf(get_param('csrf'));
    $del = $db->prepare("DELETE FROM job_alerts WHERE id = :id AND candidate_profile_id = :cid");
    $del->execute([':id' => (int)$_GET['delete'], ':cid' => $cp['id']]);
    flash('success', 'Alert deleted.');
    redirect(site_url('candidate/job-alerts.php'));
}

// Handle toggle
if (isset($_GET['toggle'])) {
    $tog = $db->prepare("UPDATE job_alerts SET is_active = NOT is_active WHERE id = :id AND candidate_profile_id = :cid");
    $tog->execute([':id' => (int)$_GET['toggle'], ':cid' => $cp['id']]);
    redirect(site_url('candidate/job-alerts.php'));
}

$alerts = $db->prepare("SELECT * FROM job_alerts WHERE candidate_profile_id = :cid ORDER BY created_at DESC");
$alerts->execute([':cid' => $cp['id']]);
$alerts = $alerts->fetchAll();

$page_title = 'Job Alerts';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_candidate.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Job Alerts</h1>
        <button class="btn btn-primary" onclick="document.getElementById('createAlertModal').style.display='flex'">Create Alert</button>
    </div>

    <?= render_flash() ?>

    <?php if (empty($alerts)): ?>
        <div class="empty-state">
            <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
            <h3>No job alerts yet</h3>
            <p>Create a job alert and get notified when matching jobs are posted.</p>
            <button onclick="document.getElementById('createAlertModal').style.display='flex'" class="btn btn-primary" style="margin-top:12px;">Create Alert</button>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Alert Name</th>
                        <th>Keywords</th>
                        <th>Location</th>
                        <th>Frequency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><strong><?= clean($alert['title']) ?></strong></td>
                        <td><?= clean($alert['keyword'] ?: '–') ?></td>
                        <td><?= clean($alert['location'] ?: '–') ?></td>
                        <td><?= ucfirst(clean($alert['frequency'])) ?></td>
                        <td>
                            <span class="status-badge <?= $alert['is_active'] ? 'status-active' : 'status-expired' ?>">
                                <?= $alert['is_active'] ? 'Active' : 'Paused' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:8px;">
                                <a href="?toggle=<?= $alert['id'] ?>" class="btn btn-outline btn-sm"><?= $alert['is_active'] ? 'Pause' : 'Resume' ?></a>
                                <a href="?delete=<?= $alert['id'] ?>&csrf=<?= csrf_token() ?>" class="btn btn-outline btn-sm" style="color:var(--danger);"
                                   onclick="return confirm('Delete this alert?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<!-- Create Alert Modal -->
<div id="createAlertModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Create Job Alert</h3>
            <button class="modal-close" onclick="document.getElementById('createAlertModal').style.display='none'">×</button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label">Alert Name <span class="required">*</span></label>
                <input type="text" name="alert_title" class="form-control" required placeholder="e.g. Frontend Developer in Amman">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Keywords</label>
                    <input type="text" name="keyword" class="form-control" placeholder="e.g. React, PHP">
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" placeholder="e.g. Amman">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Job Type</label>
                    <select name="job_type" class="form-select">
                        <option value="">Any</option>
                        <?php foreach (JOB_TYPES as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="instant">Instant</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('createAlertModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Alert</button>
            </div>
        </form>
    </div>
</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
