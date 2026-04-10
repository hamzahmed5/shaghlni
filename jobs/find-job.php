<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

$db = get_db();
$page_title = 'Find Job';

// --- Filters from GET ---
$keyword   = trim(get_param('keyword'));
$location  = trim(get_param('location'));
$category  = trim(get_param('category'));
$job_type  = trim(get_param('job_type'));
$exp_level = trim(get_param('exp_level'));
$salary_min= (int)get_param('salary_min');
$salary_max= (int)get_param('salary_max');
$sort      = get_param('sort') ?: 'newest';
$current_page = max(1, (int)(get_param('page') ?: 1));
$per_page  = 9;

// --- Build query ---
$where  = ["j.status = 'active'", "j.expires_at >= CURDATE()"];
$params = [];

if ($keyword) {
    $where[]  = "(j.title LIKE :kw OR j.description LIKE :kw2 OR ep.company_name LIKE :kw3)";
    $params[':kw']  = "%$keyword%";
    $params[':kw2'] = "%$keyword%";
    $params[':kw3'] = "%$keyword%";
}
if ($location) {
    $where[]  = "j.location LIKE :loc";
    $params[':loc'] = "%$location%";
}
if ($category) {
    $where[]  = "j.category = :cat";
    $params[':cat'] = $category;
}
if ($job_type) {
    $where[]  = "j.job_type = :jtype";
    $params[':jtype'] = $job_type;
}
if ($exp_level) {
    $where[]  = "j.experience_level = :exp";
    $params[':exp'] = $exp_level;
}
if ($salary_min > 0) {
    $where[]  = "j.salary_max >= :smin";
    $params[':smin'] = $salary_min;
}
if ($salary_max > 0) {
    $where[]  = "j.salary_min <= :smax";
    $params[':smax'] = $salary_max;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Count
$count_sql = "SELECT COUNT(*) FROM jobs j
              LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
              $where_sql";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_jobs = (int)$count_stmt->fetchColumn();

$pagination = paginate($total_jobs, $per_page, $current_page);

$order_sql = match($sort) {
    'salary_high'  => 'j.salary_max DESC',
    'salary_low'   => 'j.salary_min ASC',
    default        => 'j.created_at DESC',
};

$jobs_sql = "SELECT j.*, ep.company_name, ep.logo, ep.city AS company_city
             FROM jobs j
             LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
             $where_sql
             ORDER BY $order_sql
             LIMIT :limit OFFSET :offset";

$jobs_stmt = $db->prepare($jobs_sql);
foreach ($params as $k => $v) $jobs_stmt->bindValue($k, $v);
$jobs_stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$jobs_stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$jobs_stmt->execute();
$jobs = $jobs_stmt->fetchAll();

// Category counts for sidebar
$cat_counts = $db->query("SELECT category, COUNT(*) as cnt FROM jobs WHERE status='active' AND expires_at>=CURDATE() GROUP BY category ORDER BY cnt DESC")->fetchAll();

// Job type counts
$type_counts_raw = $db->query("SELECT job_type, COUNT(*) as cnt FROM jobs WHERE status='active' AND expires_at>=CURDATE() GROUP BY job_type")->fetchAll();
$type_counts = [];
foreach ($type_counts_raw as $r) $type_counts[$r['job_type']] = $r['cnt'];

// Cities
$cities_raw = $db->query("SELECT location, COUNT(*) as cnt FROM jobs WHERE status='active' AND expires_at>=CURDATE() GROUP BY location ORDER BY cnt DESC LIMIT 8")->fetchAll();

// Candidate profile (for bookmark check)
$candidate_profile_id = null;
if (is_logged_in() && current_role() === 'candidate') {
    $cp = get_candidate_profile(current_user_id());
    if ($cp) $candidate_profile_id = $cp['id'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1 class="page-banner-title">Find Job</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Find Job','url'=>'']]) ?>
    </div>
</div>

<section style="padding:40px 0 64px;">
    <div class="container">

        <!-- Search Bar -->
        <form method="GET" action="" class="find-job-search-bar">
            <div class="find-search-input">
                <svg width="18" height="18" fill="none" stroke="#767F8C" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="keyword" value="<?= clean($keyword) ?>" placeholder="Job title, keywords, or company">
            </div>
            <div class="find-search-sep"></div>
            <div class="find-search-input">
                <svg width="18" height="18" fill="none" stroke="#767F8C" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <input type="text" name="location" value="<?= clean($location) ?>" placeholder="City or location">
            </div>
            <button type="submit" class="btn btn-primary find-search-btn">Find Job</button>
        </form>

        <div class="find-job-layout">

            <!-- Sidebar Filters -->
            <aside class="find-job-sidebar">

                <!-- Category -->
                <div class="filter-group">
                    <div class="filter-group-title">Category</div>
                    <?php foreach ($cat_counts as $cat): ?>
                    <label class="filter-check-label">
                        <input type="checkbox" class="filter-checkbox" name="filter_cat" value="<?= clean($cat['category']) ?>"
                            data-filter="category" <?= $category === $cat['category'] ? 'checked' : '' ?>>
                        <span class="filter-check-text"><?= clean($cat['category']) ?></span>
                        <span class="filter-check-count">(<?= $cat['cnt'] ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Job Type -->
                <div class="filter-group">
                    <div class="filter-group-title">Job Type</div>
                    <?php foreach (JOB_TYPES as $type): ?>
                    <label class="filter-check-label">
                        <input type="checkbox" class="filter-checkbox" name="filter_type" value="<?= $type ?>"
                            data-filter="job_type" <?= $job_type === $type ? 'checked' : '' ?>>
                        <span class="filter-check-text"><?= $type ?></span>
                        <span class="filter-check-count">(<?= $type_counts[$type] ?? 0 ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Experience Level -->
                <div class="filter-group">
                    <div class="filter-group-title">Experience Level</div>
                    <?php foreach (EXP_LEVELS as $level): ?>
                    <label class="filter-check-label">
                        <input type="checkbox" class="filter-checkbox" name="filter_exp" value="<?= $level ?>"
                            data-filter="exp_level" <?= $exp_level === $level ? 'checked' : '' ?>>
                        <span class="filter-check-text"><?= $level ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Salary Range -->
                <div class="filter-group">
                    <div class="filter-group-title">Salary Range (JOD/mo)</div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="number" id="salary_min_input" placeholder="Min" min="0"
                            value="<?= $salary_min ?: '' ?>"
                            style="width:80px;padding:8px;border:1px solid var(--border);border-radius:4px;font-size:13px;">
                        <span style="color:var(--text-muted);">–</span>
                        <input type="number" id="salary_max_input" placeholder="Max" min="0"
                            value="<?= $salary_max ?: '' ?>"
                            style="width:80px;padding:8px;border:1px solid var(--border);border-radius:4px;font-size:13px;">
                    </div>
                </div>

                <!-- Location -->
                <div class="filter-group">
                    <div class="filter-group-title">Location</div>
                    <?php foreach ($cities_raw as $city): ?>
                    <label class="filter-check-label">
                        <input type="checkbox" class="filter-checkbox" name="filter_city" value="<?= clean($city['location']) ?>"
                            data-filter="location" <?= $location === $city['location'] ? 'checked' : '' ?>>
                        <span class="filter-check-text"><?= clean($city['location']) ?></span>
                        <span class="filter-check-count">(<?= $city['cnt'] ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <button onclick="applyFilters()" class="btn btn-primary" style="width:100%;margin-top:8px;">Apply Filters</button>
                <a href="<?= site_url('jobs/find-job.php') ?>" class="btn btn-outline" style="width:100%;margin-top:8px;text-align:center;">Clear Filters</a>

            </aside>

            <!-- Job Grid -->
            <div class="find-job-content">

                <!-- Sort / Count bar -->
                <div class="find-job-topbar">
                    <div class="find-job-count">
                        Showing <strong><?= $pagination['offset'] + 1 ?>–<?= min($pagination['offset'] + $per_page, $total_jobs) ?></strong> of <strong><?= $total_jobs ?></strong> jobs
                        <?php if ($keyword || $location || $category): ?>
                            <?php if ($keyword): ?><span class="search-tag"><?= clean($keyword) ?> <a href="#" onclick="clearParam('keyword')">×</a></span><?php endif; ?>
                            <?php if ($location): ?><span class="search-tag"><?= clean($location) ?> <a href="#" onclick="clearParam('location')">×</a></span><?php endif; ?>
                            <?php if ($category): ?><span class="search-tag"><?= clean($category) ?> <a href="#" onclick="clearParam('category')">×</a></span><?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <label style="font-size:13px;color:var(--text-muted);">Sort by:</label>
                        <select onchange="sortJobs(this.value)" style="padding:7px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;">
                            <option value="newest"     <?= $sort==='newest'     ? 'selected' : '' ?>>Newest First</option>
                            <option value="salary_high"<?= $sort==='salary_high'? 'selected' : '' ?>>Salary: High to Low</option>
                            <option value="salary_low" <?= $sort==='salary_low' ? 'selected' : '' ?>>Salary: Low to High</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($jobs)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <h3>No jobs found</h3>
                        <p>Try adjusting your search or filters.</p>
                        <a href="<?= site_url('jobs/find-job.php') ?>" class="btn btn-primary">Clear Search</a>
                    </div>
                <?php else: ?>
                    <div class="job-cards-grid">
                        <?php foreach ($jobs as $job): ?>
                            <?= render_job_card($job, true, $candidate_profile_id) ?>
                        <?php endforeach; ?>
                    </div>

                    <?= render_pagination($pagination, site_url('jobs/find-job.php'), [
                        'keyword'   => $keyword,
                        'location'  => $location,
                        'category'  => $category,
                        'job_type'  => $job_type,
                        'exp_level' => $exp_level,
                        'salary_min'=> $salary_min ?: '',
                        'salary_max'=> $salary_max ?: '',
                        'sort'      => $sort,
                    ]) ?>
                <?php endif; ?>

            </div><!-- /.find-job-content -->

        </div><!-- /.find-job-layout -->
    </div>
</section>

<script>
// Current URL params
var params = new URLSearchParams(window.location.search);

function applyFilters() {
    // Category
    var cats = document.querySelectorAll('[data-filter="category"]:checked');
    params.delete('category');
    if (cats.length) params.set('category', cats[0].value);

    // Job type
    var types = document.querySelectorAll('[data-filter="job_type"]:checked');
    params.delete('job_type');
    if (types.length) params.set('job_type', types[0].value);

    // Exp
    var exps = document.querySelectorAll('[data-filter="exp_level"]:checked');
    params.delete('exp_level');
    if (exps.length) params.set('exp_level', exps[0].value);

    // Salary
    var smin = document.getElementById('salary_min_input').value;
    var smax = document.getElementById('salary_max_input').value;
    params.delete('salary_min'); params.delete('salary_max');
    if (smin) params.set('salary_min', smin);
    if (smax) params.set('salary_max', smax);

    // Location
    var locs = document.querySelectorAll('[data-filter="location"]:checked');
    params.delete('location');
    if (locs.length) params.set('location', locs[0].value);

    params.delete('page');
    window.location.search = params.toString();
}

function sortJobs(val) {
    params.set('sort', val);
    params.delete('page');
    window.location.search = params.toString();
}

function clearParam(key) {
    params.delete(key);
    params.delete('page');
    window.location.search = params.toString();
}

// Make filter checkboxes single-select within group
document.querySelectorAll('.filter-checkbox').forEach(function(cb) {
    cb.addEventListener('change', function() {
        if (this.checked) {
            var filter = this.dataset.filter;
            document.querySelectorAll('[data-filter="' + filter + '"]').forEach(function(other) {
                if (other !== cb) other.checked = false;
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
