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

$current_page = max(1, (int)(get_param('page') ?: 1));
$per_page     = 9;

$count_stmt = $db->prepare("SELECT COUNT(*) FROM saved_jobs sj JOIN jobs j ON j.id = sj.job_id WHERE sj.candidate_profile_id = :cid AND j.status='active'");
$count_stmt->execute([':cid' => $cp['id']]);
$total      = (int)$count_stmt->fetchColumn();
$pagination = paginate($total, $per_page, $current_page);

$stmt = $db->prepare("SELECT j.*, ep.company_name, ep.logo, ep.city AS company_city, sj.saved_at
                      FROM saved_jobs sj
                      JOIN jobs j ON j.id = sj.job_id
                      LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
                      WHERE sj.candidate_profile_id = :cid AND j.status='active'
                      ORDER BY sj.saved_at DESC
                      LIMIT :limit OFFSET :offset");
$stmt->bindValue(':cid',    $cp['id'], PDO::PARAM_INT);
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$saved_jobs = $stmt->fetchAll();

$page_title = 'Favorite Jobs';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_candidate.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Favorite Jobs</h1>
        <span style="font-size:14px;color:var(--text-muted);"><?= $total ?> saved job<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <?= render_flash() ?>

    <?php if (empty($saved_jobs)): ?>
        <div class="empty-state">
            <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
            <h3>No saved jobs yet</h3>
            <p>Bookmark jobs you're interested in and they'll appear here.</p>
            <a href="<?= site_url('jobs/find-job.php') ?>" class="btn btn-primary">Browse Jobs</a>
        </div>
    <?php else: ?>
        <div class="job-cards-grid">
            <?php foreach ($saved_jobs as $job): ?>
                <?= render_job_card($job, true, $cp['id']) ?>
            <?php endforeach; ?>
        </div>
        <?= render_pagination($pagination, site_url('candidate/favorite-jobs.php'), []) ?>
    <?php endif; ?>

</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
