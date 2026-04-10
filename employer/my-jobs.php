<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db  = get_db();
$uid = current_user_id();
$ep  = get_employer_profile($uid);

// Handle delete
if (isset($_GET['delete'])) {
    verify_csrf(get_param('csrf'));
    $db->prepare("DELETE FROM jobs WHERE id=:id AND employer_id=:uid")->execute([':id'=>(int)$_GET['delete'],':uid'=>$uid]);
    flash('success','Job deleted.');
    redirect(site_url('employer/my-jobs.php'));
}

// Handle expire
if (isset($_GET['expire'])) {
    verify_csrf(get_param('csrf'));
    $db->prepare("UPDATE jobs SET status='expired' WHERE id=:id AND employer_id=:uid")->execute([':id'=>(int)$_GET['expire'],':uid'=>$uid]);
    flash('success','Job marked as expired.');
    redirect(site_url('employer/my-jobs.php'));
}

$status_filter = get_param('status');
$current_page  = max(1,(int)(get_param('page') ?: 1));
$per_page      = 10;

$where  = ["j.employer_id = :uid"];
$params = [':uid'=>$uid];
if ($status_filter) { $where[] = "j.status = :status"; $params[':status'] = $status_filter; }

$where_sql = 'WHERE ' . implode(' AND ', $where);

$count_stmt = $db->prepare("SELECT COUNT(*) FROM jobs j $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pagination = paginate($total, $per_page, $current_page);

$stmt = $db->prepare("SELECT j.*, (SELECT COUNT(*) FROM applications WHERE job_id=j.id) AS app_count
                      FROM jobs j $where_sql ORDER BY j.created_at DESC
                      LIMIT :limit OFFSET :offset");
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':limit',$per_page,PDO::PARAM_INT);
$stmt->bindValue(':offset',$pagination['offset'],PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll();

$page_title = 'My Jobs';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">My Jobs</h1>
        <a href="<?= site_url('employer/post-job.php') ?>" class="btn btn-primary">Post a Job</a>
    </div>

    <?= render_flash() ?>

    <div class="filter-tabs">
        <a href="<?= site_url('employer/my-jobs.php') ?>"           class="filter-tab <?= !$status_filter ? 'active' : '' ?>">All</a>
        <a href="?status=active"  class="filter-tab <?= $status_filter==='active'  ? 'active' : '' ?>">Active</a>
        <a href="?status=draft"   class="filter-tab <?= $status_filter==='draft'   ? 'active' : '' ?>">Draft</a>
        <a href="?status=expired" class="filter-tab <?= $status_filter==='expired' ? 'active' : '' ?>">Expired</a>
    </div>

    <?php if (empty($jobs)): ?>
        <div class="empty-state">
            <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
            <h3>No jobs found</h3>
            <a href="<?= site_url('employer/post-job.php') ?>" class="btn btn-primary">Post a Job</a>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Job Title</th><th>Type</th><th>Applications</th><th>Posted</th><th>Expires</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('jobs/job-detail.php?id='.$j['id']) ?>" class="table-job-title"><?= clean($j['title']) ?></a>
                            <div class="table-job-location"><?= clean($j['location']) ?></div>
                        </td>
                        <td><span class="job-type-badge <?= job_type_class($j['job_type']) ?>"><?= $j['job_type'] ?></span></td>
                        <td>
                            <a href="<?= site_url('employer/applications.php?job_id='.$j['id']) ?>" style="font-weight:600;color:var(--primary);"><?= $j['app_count'] ?></a>
                        </td>
                        <td><?= date('M j, Y', strtotime($j['created_at'])) ?></td>
                        <td><?= date('M j, Y', strtotime($j['expires_at'])) ?></td>
                        <td><span class="status-badge status-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                        <td>
                            <div class="action-dropdown">
                                <button class="action-dropdown-btn">•••</button>
                                <div class="action-dropdown-menu">
                                    <a href="<?= site_url('employer/post-job.php?edit='.$j['id']) ?>">Edit Job</a>
                                    <a href="<?= site_url('employer/applications.php?job_id='.$j['id']) ?>">View Applications</a>
                                    <?php if ($j['status']==='active'): ?>
                                    <a href="?expire=<?= $j['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('Mark as expired?')">Mark Expired</a>
                                    <?php endif; ?>
                                    <a href="?delete=<?= $j['id'] ?>&csrf=<?= csrf_token() ?>" style="color:var(--danger);" onclick="return confirm('Delete this job? This cannot be undone.')">Delete Job</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pagination($pagination, site_url('employer/my-jobs.php'), ['status'=>$status_filter]) ?>
    <?php endif; ?>

</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
