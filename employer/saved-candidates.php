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

// Unsave
if (isset($_GET['unsave'])) {
    verify_csrf(get_param('csrf'));
    $db->prepare("DELETE FROM saved_candidates WHERE employer_profile_id=:eid AND candidate_profile_id=:cid")
       ->execute([':eid'=>$ep['id'],':cid'=>(int)$_GET['unsave']]);
    flash('success','Candidate removed from saved list.');
    redirect(site_url('employer/saved-candidates.php'));
}

$current_page = max(1,(int)(get_param('page') ?: 1));
$per_page = 12;

$count_stmt = $db->prepare("SELECT COUNT(*) FROM saved_candidates WHERE employer_profile_id=:eid");
$count_stmt->execute([':eid'=>$ep['id']]);
$total = (int)$count_stmt->fetchColumn();
$pagination = paginate($total, $per_page, $current_page);

$stmt = $db->prepare("SELECT sc.*, cp.id AS cp_id, cp.current_position, cp.city, cp.experience_level,
                       u.full_name, u.avatar, u.id AS user_id
                       FROM saved_candidates sc
                       JOIN candidate_profiles cp ON cp.id=sc.candidate_profile_id
                       JOIN users u ON u.id=cp.user_id
                       WHERE sc.employer_profile_id=:eid
                       ORDER BY sc.saved_at DESC
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':eid',$ep['id'],PDO::PARAM_INT);
$stmt->bindValue(':limit',$per_page,PDO::PARAM_INT);
$stmt->bindValue(':offset',$pagination['offset'],PDO::PARAM_INT);
$stmt->execute();
$candidates = $stmt->fetchAll();

$page_title = 'Saved Candidates';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Saved Candidates</h1>
        <span style="font-size:14px;color:var(--text-muted);"><?= $total ?> saved</span>
    </div>

    <?= render_flash() ?>

    <?php if (empty($candidates)): ?>
        <div class="empty-state">
            <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>No saved candidates</h3>
            <p>Browse candidates and save the ones you're interested in.</p>
            <a href="<?= site_url('candidates/browse.php') ?>" class="btn btn-primary">Browse Candidates</a>
        </div>
    <?php else: ?>
        <div class="candidate-cards-grid">
            <?php foreach ($candidates as $cand): ?>
            <div class="candidate-card">
                <div class="candidate-card-top">
                    <?php if ($cand['avatar']): ?>
                        <img src="<?= upload_url($cand['avatar']) ?>" class="candidate-card-avatar" alt="">
                    <?php else: ?>
                        <div class="avatar-placeholder candidate-card-avatar"><?= strtoupper(substr($cand['full_name'],0,2)) ?></div>
                    <?php endif; ?>
                    <button class="btn-unsave-candidate" title="Remove"
                        onclick="if(confirm('Remove from saved?')) window.location='?unsave=<?= $cand['cp_id'] ?>&csrf=<?= csrf_token() ?>'">×</button>
                </div>
                <div class="candidate-card-info">
                    <h3 class="candidate-card-name">
                        <a href="<?= site_url('candidates/candidate-detail.php?id='.$cand['user_id']) ?>"><?= clean($cand['full_name']) ?></a>
                    </h3>
                    <?php if ($cand['current_position']): ?><div class="candidate-card-title"><?= clean($cand['current_position']) ?></div><?php endif; ?>
                    <?php if ($cand['city']): ?><div class="candidate-card-location"><?= clean($cand['city']) ?></div><?php endif; ?>
                    <?php if ($cand['experience_level']): ?><span class="job-type-badge part-time" style="margin-top:8px;display:inline-block;"><?= clean($cand['experience_level']) ?></span><?php endif; ?>
                </div>
                <a href="<?= site_url('candidates/candidate-detail.php?id='.$cand['user_id']) ?>" class="btn btn-outline btn-sm candidate-card-btn">View Profile</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?= render_pagination($pagination, site_url('employer/saved-candidates.php'), []) ?>
    <?php endif; ?>

</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
