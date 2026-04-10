<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

$db = get_db();
$page_title = 'Browse Employers';

$keyword  = trim(get_param('keyword'));
$industry = trim(get_param('industry'));
$city     = trim(get_param('city'));
$current_page = max(1, (int)(get_param('page') ?: 1));
$per_page = 12;

$where  = ["u.role = 'employer'", "u.is_verified = 1"];
$params = [];

if ($keyword) {
    $where[]  = "(ep.company_name LIKE :kw OR ep.industry LIKE :kw2)";
    $params[':kw']  = "%$keyword%";
    $params[':kw2'] = "%$keyword%";
}
if ($industry) {
    $where[]  = "ep.industry = :ind";
    $params[':ind'] = $industry;
}
if ($city) {
    $where[]  = "ep.city LIKE :city";
    $params[':city'] = "%$city%";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$count_sql = "SELECT COUNT(*) FROM employer_profiles ep JOIN users u ON u.id = ep.user_id $where_sql";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pagination = paginate($total, $per_page, $current_page);

$sql = "SELECT ep.*, u.id AS uid, u.email,
        (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = ep.user_id AND j.status='active' AND j.expires_at>=CURDATE()) AS open_jobs
        FROM employer_profiles ep
        JOIN users u ON u.id = ep.user_id
        $where_sql
        ORDER BY open_jobs DESC, ep.company_name ASC
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$employers = $stmt->fetchAll();

// Industries for filter
$industries = $db->query("SELECT DISTINCT industry FROM employer_profiles WHERE industry IS NOT NULL AND industry != '' ORDER BY industry")->fetchAll(PDO::FETCH_COLUMN);

// Cities for filter
$cities = $db->query("SELECT DISTINCT city FROM employer_profiles WHERE city IS NOT NULL AND city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1 class="page-banner-title">Browse Employers</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Employers','url'=>'']]) ?>
    </div>
</div>

<section style="padding:40px 0 64px;">
    <div class="container">

        <!-- Search Bar -->
        <form method="GET" class="find-job-search-bar" style="margin-bottom:32px;">
            <div class="find-search-input">
                <svg width="18" height="18" fill="none" stroke="#767F8C" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="keyword" value="<?= clean($keyword) ?>" placeholder="Company name or industry">
            </div>
            <div class="find-search-sep"></div>
            <select name="industry" class="form-select" style="border:none;flex:1;padding:0 12px;font-size:14px;background:transparent;">
                <option value="">All Industries</option>
                <?php foreach ($industries as $ind): ?>
                    <option value="<?= clean($ind) ?>" <?= $industry === $ind ? 'selected' : '' ?>><?= clean($ind) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="find-search-sep"></div>
            <select name="city" class="form-select" style="border:none;flex:1;padding:0 12px;font-size:14px;background:transparent;">
                <option value="">All Cities</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?= clean($c) ?>" <?= $city === $c ? 'selected' : '' ?>><?= clean($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary find-search-btn">Search</button>
        </form>

        <!-- Count -->
        <div style="margin-bottom:20px;font-size:14px;color:var(--text-muted);">
            Showing <strong><?= $pagination['offset'] + 1 ?>–<?= min($pagination['offset'] + $per_page, $total) ?></strong> of <strong><?= $total ?></strong> employers
        </div>

        <!-- Grid -->
        <?php if (empty($employers)): ?>
            <div class="empty-state">
                <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                <h3>No employers found</h3>
                <p>Try adjusting your search.</p>
            </div>
        <?php else: ?>
            <div class="employer-cards-grid">
                <?php foreach ($employers as $emp): ?>
                <div class="employer-card">
                    <a href="<?= site_url('employers/employer-detail.php?id=' . $emp['uid']) ?>">
                        <?php if ($emp['logo']): ?>
                            <img src="<?= upload_url($emp['logo']) ?>" alt="<?= clean($emp['company_name']) ?>" class="employer-card-logo">
                        <?php else: ?>
                            <div class="logo-placeholder employer-card-logo-placeholder"><?= strtoupper(substr($emp['company_name'],0,2)) ?></div>
                        <?php endif; ?>
                    </a>
                    <div class="employer-card-info">
                        <a href="<?= site_url('employers/employer-detail.php?id=' . $emp['uid']) ?>" class="employer-card-name"><?= clean($emp['company_name']) ?></a>
                        <?php if ($emp['industry']): ?><div class="employer-card-industry"><?= clean($emp['industry']) ?></div><?php endif; ?>
                        <?php if ($emp['city']): ?><div class="employer-card-city"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg> <?= clean($emp['city']) ?></div><?php endif; ?>
                    </div>
                    <a href="<?= site_url('jobs/find-job.php?employer_id=' . $emp['uid']) ?>" class="employer-card-jobs-badge"><?= (int)$emp['open_jobs'] ?> Open Jobs</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?= render_pagination($pagination, site_url('employers/browse.php'), ['keyword'=>$keyword,'industry'=>$industry,'city'=>$city]) ?>
        <?php endif; ?>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
