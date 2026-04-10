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

$status_filter = get_param('status');
$current_page  = max(1, (int)(get_param('page') ?: 1));
$per_page      = 10;

$where  = ["a.candidate_profile_id = :cid"];
$params = [':cid' => $cp['id']];
if ($status_filter) {
    $where[]  = "a.status = :status";
    $params[':status'] = $status_filter;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

$count_stmt = $db->prepare("SELECT COUNT(*) FROM applications a $where_sql");
$count_stmt->execute($params);
$total      = (int)$count_stmt->fetchColumn();
$pagination = paginate($total, $per_page, $current_page);

$stmt = $db->prepare("SELECT a.*, j.title AS job_title, j.job_type, j.location, j.salary_min, j.salary_max,
                       j.salary_currency, j.salary_type, j.expires_at,
                       ep.company_name, ep.logo, ep.city AS company_city
                       FROM applications a
                       JOIN jobs j ON j.id = a.job_id
                       LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
                       $where_sql
                       ORDER BY a.applied_at DESC
                       LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$applications = $stmt->fetchAll();

$statuses = ['pending','reviewed','shortlisted','interviewed','hired','rejected'];
$page_title = 'Applied Jobs';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_candidate.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Applied Jobs</h1>
        <span style="font-size:14px;color:var(--text-muted);"><?= $total ?> application<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <?= render_flash() ?>

    <!-- Status Filter Tabs -->
    <div class="filter-tabs">
        <a href="<?= site_url('candidate/applied-jobs.php') ?>" class="filter-tab <?= !$status_filter ? 'active' : '' ?>">All</a>
        <?php foreach ($statuses as $s): ?>
            <a href="?status=<?= $s ?>" class="filter-tab <?= $status_filter === $s ? 'active' : '' ?>"><?= ucfirst($s) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <h3>No applications found</h3>
            <p><?= $status_filter ? "No " . $status_filter . " applications." : "You haven't applied to any jobs yet." ?></p>
            <a href="<?= site_url('jobs/find-job.php') ?>" class="btn btn-primary">Browse Jobs</a>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Company</th>
                        <th>Type</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('jobs/job-detail.php?id=' . $app['job_id']) ?>" class="table-job-title"><?= clean($app['job_title']) ?></a>
                            <div class="table-job-location"><?= clean($app['location']) ?></div>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <?php if ($app['logo']): ?>
                                    <img src="<?= upload_url($app['logo']) ?>" style="width:32px;height:32px;object-fit:contain;border:1px solid var(--border);border-radius:4px;">
                                <?php endif; ?>
                                <?= clean($app['company_name']) ?>
                            </div>
                        </td>
                        <td><span class="job-type-badge <?= job_type_class($app['job_type']) ?>"><?= $app['job_type'] ?></span></td>
                        <td><?= date('M j, Y', strtotime($app['applied_at'])) ?></td>
                        <td><span class="status-badge status-<?= strtolower($app['status']) ?>"><?= ucfirst($app['status']) ?></span></td>
                        <td>
                            <a href="<?= site_url('jobs/job-detail.php?id=' . $app['job_id']) ?>" class="btn btn-outline btn-sm">View Job</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pagination($pagination, site_url('candidate/applied-jobs.php'), ['status'=>$status_filter]) ?>
    <?php endif; ?>

</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
