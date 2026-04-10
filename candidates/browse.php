<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

// Only employers can browse candidates
if (is_logged_in() && current_role() === 'candidate') {
    redirect(site_url('candidate/dashboard.php'));
}

$db = get_db();
$page_title = 'Browse Candidates';

$keyword   = trim(get_param('keyword'));
$location  = trim(get_param('location'));
$skill     = trim(get_param('skill'));
$exp_level = trim(get_param('exp_level'));
$current_page = max(1, (int)(get_param('page') ?: 1));
$per_page  = 12;

$where  = ["u.role = 'candidate'", "u.is_verified = 1", "cp.is_public = 1"];
$params = [];

if ($keyword) {
    $where[]  = "(u.full_name LIKE :kw OR cp.current_position LIKE :kw2 OR cp.bio LIKE :kw3)";
    $params[':kw']  = "%$keyword%";
    $params[':kw2'] = "%$keyword%";
    $params[':kw3'] = "%$keyword%";
}
if ($location) {
    $where[]  = "cp.city LIKE :loc";
    $params[':loc'] = "%$location%";
}
if ($exp_level) {
    $where[]  = "cp.experience_level = :exp";
    $params[':exp'] = $exp_level;
}
if ($skill) {
    $where[]  = "cp.id IN (SELECT candidate_profile_id FROM candidate_skills WHERE skill_name LIKE :skill)";
    $params[':skill'] = "%$skill%";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$count_sql = "SELECT COUNT(*) FROM candidate_profiles cp JOIN users u ON u.id = cp.user_id $where_sql";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pagination = paginate($total, $per_page, $current_page);

$sql = "SELECT cp.*, u.full_name, u.avatar
        FROM candidate_profiles cp
        JOIN users u ON u.id = cp.user_id
        $where_sql
        ORDER BY cp.updated_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$candidates = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1 class="page-banner-title">Browse Candidates</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Candidates','url'=>'']]) ?>
    </div>
</div>

<section style="padding:40px 0 64px;">
    <div class="container">

        <!-- Search -->
        <form method="GET" class="find-job-search-bar" style="margin-bottom:32px;">
            <div class="find-search-input">
                <svg width="18" height="18" fill="none" stroke="#767F8C" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="keyword" value="<?= clean($keyword) ?>" placeholder="Name, title, or skill">
            </div>
            <div class="find-search-sep"></div>
            <div class="find-search-input">
                <svg width="18" height="18" fill="none" stroke="#767F8C" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <input type="text" name="location" value="<?= clean($location) ?>" placeholder="City or location">
            </div>
            <div class="find-search-sep"></div>
            <select name="exp_level" style="border:none;flex:1;padding:0 12px;font-size:14px;background:transparent;">
                <option value="">Experience Level</option>
                <?php foreach (EXP_LEVELS as $level): ?>
                    <option value="<?= $level ?>" <?= $exp_level === $level ? 'selected' : '' ?>><?= $level ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary find-search-btn">Search</button>
        </form>

        <div style="margin-bottom:20px;font-size:14px;color:var(--text-muted);">
            Showing <strong><?= $pagination['offset'] + 1 ?>–<?= min($pagination['offset'] + $per_page, $total) ?></strong> of <strong><?= $total ?></strong> candidates
        </div>

        <?php if (empty($candidates)): ?>
            <div class="empty-state">
                <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                <h3>No candidates found</h3>
                <p>Try adjusting your search.</p>
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
                        <?php if (is_logged_in() && current_role() === 'employer'): ?>
                            <button class="btn-save-candidate <?= '' ?>"
                                data-candidate-id="<?= $cand['id'] ?>"
                                title="Save Candidate">
                                <svg width="16" height="16" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="candidate-card-info">
                        <h3 class="candidate-card-name">
                            <a href="<?= site_url('candidates/candidate-detail.php?id=' . $cand['user_id']) ?>"><?= clean($cand['full_name']) ?></a>
                        </h3>
                        <?php if ($cand['current_position']): ?>
                            <div class="candidate-card-title"><?= clean($cand['current_position']) ?></div>
                        <?php endif; ?>
                        <?php if ($cand['city']): ?>
                            <div class="candidate-card-location">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?= clean($cand['city']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($cand['experience_level']): ?>
                            <span class="job-type-badge part-time" style="margin-top:8px;display:inline-block;"><?= clean($cand['experience_level']) ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= site_url('candidates/candidate-detail.php?id=' . $cand['user_id']) ?>" class="btn btn-outline btn-sm candidate-card-btn">View Profile</a>
                </div>
                <?php endforeach; ?>
            </div>

            <?= render_pagination($pagination, site_url('candidates/browse.php'), ['keyword'=>$keyword,'location'=>$location,'exp_level'=>$exp_level,'skill'=>$skill]) ?>
        <?php endif; ?>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
