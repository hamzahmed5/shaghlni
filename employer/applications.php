<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db  = get_db();
$uid = current_user_id();

$job_id = (int)get_param('job_id');
$view   = get_param('view') ?: 'list'; // list | kanban

// Get this employer's jobs for filter dropdown
$jobs_list = $db->prepare("SELECT id, title FROM jobs WHERE employer_id=:uid ORDER BY created_at DESC");
$jobs_list->execute([':uid'=>$uid]);
$jobs_list = $jobs_list->fetchAll();

$where  = ["j.employer_id = :uid"];
$params = [':uid'=>$uid];
if ($job_id) { $where[] = "a.job_id = :jid"; $params[':jid'] = $job_id; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare("SELECT a.*, j.title AS job_title, u.full_name AS candidate_name, u.avatar, u.email AS candidate_email,
                       cp.current_position, cp.city AS candidate_city, cp.id AS candidate_profile_id
                       FROM applications a
                       JOIN jobs j ON j.id=a.job_id
                       JOIN candidate_profiles cp ON cp.id=a.candidate_profile_id
                       JOIN users u ON u.id=cp.user_id
                       $where_sql
                       ORDER BY a.applied_at DESC");
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Group by status for kanban
$kanban = ['pending'=>[],'reviewed'=>[],'shortlisted'=>[],'interviewed'=>[],'hired'=>[],'rejected'=>[]];
foreach ($applications as $app) {
    $kanban[$app['status']][] = $app;
}

$page_title = 'Applications';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Applications</h1>
        <div style="display:flex;align-items:center;gap:10px;">
            <!-- Job filter -->
            <select onchange="window.location='?job_id='+this.value+'&view=<?= $view ?>'" style="padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;">
                <option value="">All Jobs</option>
                <?php foreach ($jobs_list as $jl): ?>
                    <option value="<?= $jl['id'] ?>" <?= $job_id===$jl['id'] ? 'selected' : '' ?>><?= clean($jl['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <!-- View toggle -->
            <div style="display:flex;border:1px solid var(--border);border-radius:6px;overflow:hidden;">
                <a href="?job_id=<?= $job_id ?>&view=list"   style="padding:7px 12px;font-size:13px;<?= $view==='list'   ? 'background:var(--primary);color:#fff;' : '' ?>">List</a>
                <a href="?job_id=<?= $job_id ?>&view=kanban" style="padding:7px 12px;font-size:13px;<?= $view==='kanban' ? 'background:var(--primary);color:#fff;' : '' ?>">Kanban</a>
            </div>
        </div>
    </div>

    <?= render_flash() ?>

    <?php if ($view === 'kanban'): ?>
    <!-- Kanban View -->
    <div class="kanban-board">
        <?php
        $kanban_labels = ['pending'=>'Pending','reviewed'=>'Reviewed','shortlisted'=>'Shortlisted','interviewed'=>'Interviewed','hired'=>'Hired','rejected'=>'Rejected'];
        $kanban_colors = ['pending'=>'#767F8C','reviewed'=>'var(--primary)','shortlisted'=>'#FF6636','interviewed'=>'#7C5FE0','hired'=>'#0BA02C','rejected'=>'var(--danger)'];
        foreach ($kanban_labels as $status => $label):
        ?>
        <div class="kanban-col">
            <div class="kanban-col-header" style="border-top:3px solid <?= $kanban_colors[$status] ?>;">
                <span><?= $label ?></span>
                <span class="kanban-count"><?= count($kanban[$status]) ?></span>
            </div>
            <?php foreach ($kanban[$status] as $app): ?>
            <div class="kanban-card" onclick="window.location='<?= site_url('employer/application-view.php?id='.$app['id']) ?>'">
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
                    <?php if ($app['avatar']): ?>
                        <img src="<?= upload_url($app['avatar']) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <div class="avatar-placeholder" style="width:32px;height:32px;font-size:12px;"><?= strtoupper(substr($app['candidate_name'],0,2)) ?></div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:600;font-size:13px;"><?= clean($app['candidate_name']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);"><?= clean($app['current_position'] ?? '') ?></div>
                    </div>
                </div>
                <div style="font-size:12px;color:var(--text-secondary);"><?= clean($app['job_title']) ?></div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= date('M j', strtotime($app['applied_at'])) ?></div>
                <div class="kanban-card-actions" onclick="event.stopPropagation()">
                    <?php foreach (['shortlisted','hired','rejected'] as $ns): ?>
                        <?php if ($ns !== $status): ?>
                        <button onclick="updateAppStatus(<?= $app['id'] ?>, '<?= $ns ?>')" style="font-size:11px;padding:3px 8px;" class="btn btn-outline btn-sm"><?= ucfirst($ns) ?></button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <!-- List View -->
    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>No applications yet</h3>
            <p>Post a job to start receiving applications.</p>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead><tr><th>Candidate</th><th>Job</th><th>Applied</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <?php if ($app['avatar']): ?>
                                    <img src="<?= upload_url($app['avatar']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                <?php else: ?>
                                    <div class="avatar-placeholder" style="width:36px;height:36px;font-size:13px;"><?= strtoupper(substr($app['candidate_name'],0,2)) ?></div>
                                <?php endif; ?>
                                <div>
                                    <a href="<?= site_url('candidates/candidate-detail.php?id='.($app['candidate_profile_id'])) ?>" style="font-weight:600;font-size:14px;color:var(--text-primary);"><?= clean($app['candidate_name']) ?></a>
                                    <div style="font-size:12px;color:var(--text-muted);"><?= clean($app['candidate_city'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= clean($app['job_title']) ?></td>
                        <td><?= date('M j, Y', strtotime($app['applied_at'])) ?></td>
                        <td>
                            <select onchange="updateAppStatus(<?= $app['id'] ?>, this.value)" style="padding:5px 8px;border:1px solid var(--border);border-radius:4px;font-size:12px;">
                                <?php foreach (['pending','reviewed','shortlisted','interviewed','hired','rejected'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $app['status']===$s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="<?= site_url('employer/application-view.php?id='.$app['id']) ?>" class="btn btn-outline btn-sm">View</a>
                                <a href="<?= site_url('candidates/candidate-detail.php?id='.$app['candidate_profile_id']) ?>" class="btn btn-outline btn-sm">Profile</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
function updateAppStatus(id, status) {
    fetch('<?= site_url('actions/update-application-status.php') ?>', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'app_id='+id+'&status='+status+'&csrf=<?= csrf_token() ?>'
    }).then(r=>r.json()).then(d=>{
        if(d.success) location.reload();
        else alert(d.message || 'Error');
    });
}
</script>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
