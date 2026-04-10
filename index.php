<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';
start_session();

$db = get_db();

// Stats
$total_jobs       = (int)$db->query('SELECT COUNT(*) FROM jobs WHERE status = "active"')->fetchColumn();
$total_companies  = (int)$db->query('SELECT COUNT(*) FROM employer_profiles')->fetchColumn();
$total_candidates = (int)$db->query('SELECT COUNT(*) FROM candidate_profiles WHERE is_public = 1')->fetchColumn();
$new_jobs         = (int)$db->query('SELECT COUNT(*) FROM jobs WHERE status = "active" AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();

// Featured jobs (8 most recent active jobs)
$featured_jobs = $db->query(
    'SELECT j.*, ep.company_name, ep.logo, ep.industry
     FROM jobs j
     LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
     WHERE j.status = "active"
     ORDER BY j.is_featured DESC, j.created_at DESC
     LIMIT 8'
)->fetchAll();

// Top companies (6 with most open jobs)
$top_companies = $db->query(
    'SELECT ep.*, u.full_name,
            (SELECT COUNT(*) FROM jobs WHERE employer_id = ep.user_id AND status = "active") AS open_jobs
     FROM employer_profiles ep
     JOIN users u ON u.id = ep.user_id
     ORDER BY open_jobs DESC
     LIMIT 6'
)->fetchAll();

// Popular vacancies (distinct job titles by frequency)
$popular_vacancies = $db->query(
    'SELECT title, COUNT(*) as cnt FROM jobs WHERE status = "active" GROUP BY title ORDER BY cnt DESC LIMIT 8'
)->fetchAll();

// Categories with job counts
$categories = $db->query(
    'SELECT category AS industry, COUNT(*) as cnt
     FROM jobs
     WHERE status = "active" AND category IS NOT NULL AND category != ""
     GROUP BY category ORDER BY cnt DESC LIMIT 8'
)->fetchAll();

// Latest jobs — exclude IDs already shown in featured section to avoid duplicates
$_fids = array_column($featured_jobs, 'id') ?: [0];
$_fid_list = implode(',', array_map('intval', $_fids));
$latest_jobs = $db->query(
    "SELECT j.*, ep.company_name, ep.logo
     FROM jobs j
     LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
     WHERE j.status = 'active' AND j.id NOT IN ($_fid_list)
     ORDER BY j.created_at DESC
     LIMIT 6"
)->fetchAll();

// Featured candidates for Employer section — only complete profiles
$featured_candidates = $db->query(
    'SELECT cp.*, u.full_name, u.avatar,
            GROUP_CONCAT(cs.skill_name ORDER BY cs.id SEPARATOR ", ") AS skills
     FROM candidate_profiles cp
     JOIN users u ON u.id = cp.user_id
     LEFT JOIN candidate_skills cs ON cs.candidate_profile_id = cp.id
     WHERE cp.is_public = 1 AND u.is_verified = 1
       AND cp.current_position IS NOT NULL AND cp.current_position != ""
       AND cp.preferred_field IS NOT NULL AND cp.preferred_field != ""
       AND cp.experience_level IS NOT NULL AND cp.experience_level != ""
     GROUP BY cp.id
     HAVING COUNT(cs.id) > 0
     ORDER BY cp.updated_at DESC
     LIMIT 6'
)->fetchAll();

// Candidate field summary — for employer "Browse by Field" strip
$candidate_fields = $db->query(
    'SELECT preferred_field AS field, COUNT(*) AS cnt
     FROM candidate_profiles
     WHERE is_public = 1 AND preferred_field IS NOT NULL AND preferred_field != ""
     GROUP BY preferred_field ORDER BY cnt DESC LIMIT 8'
)->fetchAll();

// Jobs by city — for city spotlight section
$jobs_by_city = $db->query(
    "SELECT location AS city, COUNT(*) AS cnt
     FROM jobs
     WHERE status = 'active' AND location != '' AND location != 'Remote'
     GROUP BY location ORDER BY cnt DESC LIMIT 8"
)->fetchAll();

// Logged-in candidate profile id (for save/apply state)
$candidate_profile_id = null;
if (is_candidate() && is_logged_in()) {
    $cp = get_candidate_profile(current_user_id());
    $candidate_profile_id = $cp ? (int)$cp['id'] : null;
}

// Recommended jobs — only for logged-in candidates (ML pre-computed or field+city fallback)
$recommended_jobs = [];
if ($candidate_profile_id) {
    $recommended_jobs = get_recommended_jobs($candidate_profile_id, 6);
}

// SVG icons for categories (no emojis)
$cat_icons = [
    'IT' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
        'bg'    => '#E7F0FA', 'color' => '#0A65CC',
    ],
    'Finance' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>',
        'bg'    => '#E7F4EA', 'color' => '#0BA02C',
    ],
    'HR' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'bg'    => '#F0EBFF', 'color' => '#7C5FE0',
    ],
    'Marketing' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
        'bg'    => '#FFF0EB', 'color' => '#FF6636',
    ],
    'Design' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>',
        'bg'    => '#FAE9E9', 'color' => '#E05151',
    ],
    'Engineering' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M20 12h2M2 12h2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41"/></svg>',
        'bg'    => '#E7F0FA', 'color' => '#0A65CC',
    ],
    'Sales' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
        'bg'    => '#FFF8E5', 'color' => '#F5A623',
    ],
    'Customer Service' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.63a19.79 19.79 0 01-3.07-8.63A2 2 0 012.18 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.18 6.18l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>',
        'bg'    => '#E5F6FA', 'color' => '#0BACDA',
    ],
    'Education' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
        'bg'    => '#E7F4EA', 'color' => '#0BA02C',
    ],
    'Health Care' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 3-4-6-3 3H2"/></svg>',
        'bg'    => '#FAE9E9', 'color' => '#E05151',
    ],
    'Accounting' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>',
        'bg'    => '#E7F0FA', 'color' => '#0A65CC',
    ],
    'Legal' => [
        'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22V12M12 12L4 6M12 12l8-6M4 6v12l8 4 8-4V6"/></svg>',
        'bg'    => '#F0EBFF', 'color' => '#7C5FE0',
    ],
];

$page_title = 'Find a Job That Suits Your Interest & Skills';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-text">
                <p class="hero-label"><?= t('home.hero_label') ?></p>
                <h1><?= t('home.hero_title') ?></h1>
                <p><?= t('home.hero_desc') ?></p>

                <form action="<?= SITE_URL ?>/jobs/find-job.php" method="GET" class="hero-search">
                    <div class="hero-search-input">
                        <svg width="18" height="18" fill="none" stroke="#767F8C" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        <input type="text" name="keyword" placeholder="Job title, keyword or company" value="<?= clean(get_param('keyword')) ?>">
                    </div>
                    <div class="hero-search-location">
                        <svg width="16" height="16" fill="none" stroke="#767F8C" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        <select name="location">
                            <option value=""><?= t('home.all_locations') ?></option>
                            <?php foreach (JO_CITIES as $city): ?>
                                <option value="<?= $city ?>"><?= $city ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-search"><?= t('home.find_job_btn') ?></button>
                </form>

                <div class="popular-searches">
                    <span class="popular-label"><?= t('home.popular_searches') ?></span>
                    <?php
                    $tags = ['PHP Developer', 'UI Designer', 'Financial Analyst', 'DevOps', 'React Native', 'Data Analyst'];
                    foreach ($tags as $tag): ?>
                        <a href="<?= SITE_URL ?>/jobs/find-job.php?keyword=<?= urlencode($tag) ?>" class="search-tag"><?= clean($tag) ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat-item">
                        <div class="hero-stat-icon">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                        </div>
                        <div>
                            <span class="hero-stat-number"><?= number_format($total_jobs) ?>+</span>
                            <span class="hero-stat-label"><?= t('home.live_jobs') ?></span>
                        </div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-icon">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <span class="hero-stat-number"><?= number_format($total_companies) ?>+</span>
                            <span class="hero-stat-label"><?= t('home.companies') ?></span>
                        </div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-icon">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 10-16 0"/></svg>
                        </div>
                        <div>
                            <span class="hero-stat-number"><?= number_format($total_candidates) ?>+</span>
                            <span class="hero-stat-label"><?= t('home.candidates') ?></span>
                        </div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-icon">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                        </div>
                        <div>
                            <span class="hero-stat-number"><?= number_format($new_jobs) ?></span>
                            <span class="hero-stat-label"><?= t('home.new_jobs') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-illustration">
                <svg width="380" height="320" viewBox="0 0 380 320" fill="none" xmlns="http://www.w3.org/2000/svg" class="hero-svg">
                    <rect x="40" y="60" width="300" height="200" rx="16" class="svg-bg-soft"/>
                    <rect x="70" y="90" width="240" height="140" rx="8" class="svg-card" stroke-width="1"/>
                    <rect x="90" y="115" width="100" height="12" rx="4" class="svg-line-dark"/>
                    <rect x="90" y="135" width="160" height="8" rx="3" class="svg-line-light"/>
                    <rect x="90" y="150" width="130" height="8" rx="3" class="svg-line-light"/>
                    <rect x="90" y="175" width="60" height="24" rx="4" fill="#0A65CC"/>
                    <circle cx="290" cy="110" r="32" fill="#0A65CC" opacity="0.1"/>
                    <circle cx="290" cy="110" r="20" fill="#0A65CC" opacity="0.2"/>
                    <circle cx="290" cy="110" r="12" fill="#0A65CC"/>
                    <path d="M284 110l4 4 8-8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </div>
</section>

<!-- Most Popular Vacancies -->
<?php if (!empty($popular_vacancies)): ?>
<section style="padding:32px 0 24px; border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= t('home.popular_vacancies') ?></h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px 0;">
            <?php foreach ($popular_vacancies as $v): ?>
                <a href="<?= SITE_URL ?>/jobs/find-job.php?keyword=<?= urlencode($v['title']) ?>"
                   style="font-size:13px;color:var(--text-secondary);padding:4px 0;text-decoration:none;">
                    <?= clean($v['title']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- How Jobpilot Works -->
<section class="bg-light">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= sprintf(t('home.how_works'), SITE_NAME) ?></h2>
        </div>
        <div class="how-works-grid">
            <div class="how-step">
                <div class="step-icon">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <p class="step-number"><?= t('home.step_01') ?></p>
                <h3 class="step-title"><?= t('home.step_1_title') ?></h3>
                <p class="step-desc"><?= t('home.step_1_desc') ?></p>
            </div>
            <div class="how-step">
                <div class="step-icon active">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <p class="step-number"><?= t('home.step_02') ?></p>
                <h3 class="step-title"><?= t('home.step_2_title') ?></h3>
                <p class="step-desc"><?= t('home.step_2_desc') ?></p>
            </div>
            <div class="how-step">
                <div class="step-icon">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                </div>
                <p class="step-number"><?= t('home.step_03') ?></p>
                <h3 class="step-title"><?= t('home.step_3_title') ?></h3>
                <p class="step-desc"><?= t('home.step_3_desc') ?></p>
            </div>
            <div class="how-step">
                <div class="step-icon">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <p class="step-number"><?= t('home.step_04') ?></p>
                <h3 class="step-title"><?= t('home.step_4_title') ?></h3>
                <p class="step-desc"><?= t('home.step_4_desc') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Popular Categories -->
<?php if (!empty($categories)): ?>
<section>
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= t('home.popular_category') ?></h2>
            <a href="<?= SITE_URL ?>/jobs/find-job.php" class="section-link"><?= t('home.see_all_categories') ?></a>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $cat):
                $raw  = $cat['industry'];
                // Strip leading emoji characters so old DB values like "💻 IT" resolve to clean keys
                $slug = trim(preg_replace('/^[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE00}-\x{FEFF}]+\s*/u', '', $raw));
                $meta = $cat_icons[$slug] ?? [
                    'svg'   => '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>',
                    'bg'    => '#F1F2F4',
                    'color' => '#474C54',
                ];
            ?>
                <a href="<?= SITE_URL ?>/jobs/find-job.php?category=<?= urlencode($raw) ?>" class="category-card">
                    <div class="category-icon" style="background:<?= $meta['bg'] ?>;color:<?= $meta['color'] ?>;">
                        <?= $meta['svg'] ?>
                    </div>
                    <div>
                        <div class="category-name"><?= clean($slug) ?></div>
                        <div class="category-count"><?= number_format($cat['cnt']) ?> <?= t('home.open_positions') ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Jobs -->
<?php if (!empty($featured_jobs)): ?>
<section class="bg-light">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= t('home.featured_job') ?></h2>
            <a href="<?= SITE_URL ?>/jobs/find-job.php" class="section-link"><?= t('home.see_all_jobs') ?></a>
        </div>
        <div class="jobs-grid">
            <?php foreach ($featured_jobs as $job): ?>
                <?= render_job_card($job, true, $candidate_profile_id) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Recommended for You — shown only to logged-in candidates -->
<?php if (!empty($recommended_jobs)): ?>
<section>
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title"><?= t('home.recommended_jobs') ?></h2>
                <p style="color:var(--text-muted);font-size:14px;margin-top:4px;"><?= t('home.recommended_desc') ?></p>
            </div>
            <a href="<?= SITE_URL ?>/jobs/find-job.php" class="section-link"><?= t('home.see_all_matches') ?></a>
        </div>
        <div class="jobs-grid">
            <?php foreach ($recommended_jobs as $job): ?>
                <?= render_job_card($job, true, $candidate_profile_id) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Top Companies -->
<?php if (!empty($top_companies)): ?>
<section>
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= t('home.top_companies') ?></h2>
            <a href="<?= SITE_URL ?>/employers/browse.php" class="section-link"><?= t('home.see_all_companies') ?></a>
        </div>
        <div class="employers-grid">
            <?php foreach ($top_companies as $company):
                $logo = !empty($company['logo'])
                    ? upload_url('logos/' . $company['logo'])
                    : asset('images/company-placeholder.png');
            ?>
                <div class="employer-card">
                    <img src="<?= $logo ?>" alt="<?= clean($company['company_name']) ?>" class="employer-logo">
                    <div class="employer-name"><?= clean($company['company_name']) ?></div>
                    <?php if (!empty($company['industry'])): ?>
                        <div class="employer-industry"><?= clean($company['industry']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($company['city'])): ?>
                        <div class="employer-location">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                            <?= clean($company['city']) ?>
                        </div>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/employers/employer-detail.php?id=<?= (int)$company['id'] ?>" class="btn-open-positions">
                        <?= number_format($company['open_jobs']) ?> <?= t('home.open_positions') ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Jobs by City -->
<?php if (!empty($jobs_by_city)): ?>
<section>
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title"><?= t('home.jobs_by_city') ?></h2>
                <p style="color:var(--text-muted);font-size:14px;margin-top:4px;"><?= t('home.jobs_by_city_desc') ?></p>
            </div>
            <a href="<?= SITE_URL ?>/jobs/find-job.php" class="section-link"><?= t('home.browse_by_city') ?></a>
        </div>
        <div class="city-grid">
            <?php foreach ($jobs_by_city as $c): ?>
                <a href="<?= SITE_URL ?>/jobs/find-job.php?location=<?= urlencode($c['city']) ?>" class="city-card">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                        <circle cx="12" cy="9" r="2.5" fill="currentColor" stroke="none"/>
                    </svg>
                    <div class="city-name"><?= clean($c['city']) ?></div>
                    <div class="city-count"><?= number_format($c['cnt']) ?> <?= t('home.open_positions') ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Job Seeker Section: Latest Opportunities -->
<?php if (!empty($latest_jobs)): ?>
<section class="bg-light">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title"><?= t('home.latest_jobs') ?></h2>
                <p style="color:var(--text-muted);font-size:14px;margin-top:4px;"><?= t('home.latest_jobs_desc') ?></p>
            </div>
            <a href="<?= SITE_URL ?>/jobs/find-job.php" class="section-link"><?= t('home.browse_all_jobs') ?></a>
        </div>
        <div class="jobs-grid">
            <?php foreach ($latest_jobs as $job): ?>
                <?= render_job_card($job, true, $candidate_profile_id) ?>
            <?php endforeach; ?>
        </div>

        <!-- Category quick filters -->
        <div class="category-quick-filters" style="margin-top:32px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            <span style="font-size:14px;color:var(--text-muted);font-weight:500;"><?= t('home.browse_by_field') ?></span>
            <?php foreach (JOB_CATEGORIES as $jcat): ?>
                <a href="<?= SITE_URL ?>/jobs/find-job.php?category=<?= urlencode($jcat) ?>"
                   style="padding:6px 14px;border:1px solid var(--border);border-radius:20px;font-size:13px;color:var(--text-secondary);text-decoration:none;background:var(--white);">
                    <?= clean($jcat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Employer Section: Talented Candidates -->
<?php if (!empty($featured_candidates)): ?>
<section>
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title"><?= t('home.top_candidates') ?></h2>
                <p style="color:var(--text-muted);font-size:14px;margin-top:4px;"><?= t('home.top_candidates_desc') ?></p>
            </div>
            <a href="<?= SITE_URL ?>/candidates/browse.php" class="section-link"><?= t('home.browse_all_cands') ?></a>
        </div>

        <?php if (!empty($candidate_fields)): ?>
        <div class="field-pills" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:24px;">
            <span style="font-size:13px;font-weight:500;color:var(--text-muted);"><?= t('home.browse_by_field') ?></span>
            <?php foreach ($candidate_fields as $cf): ?>
                <a href="<?= SITE_URL ?>/candidates/browse.php?field=<?= urlencode($cf['field']) ?>"
                   style="padding:5px 14px;border:1px solid var(--border);border-radius:20px;font-size:13px;color:var(--text-secondary);text-decoration:none;background:var(--white);">
                    <?= clean($cf['field']) ?>
                    <span style="color:var(--text-muted);font-size:11px;margin-left:3px;">(<?= (int)$cf['cnt'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="cand-grid">
            <?php foreach ($featured_candidates as $cand):
                $avatar = !empty($cand['avatar'])
                    ? upload_url('avatars/' . $cand['avatar'])
                    : asset('images/candidate-placeholder.png');
                $all_skills    = $cand['skills'] ? explode(', ', $cand['skills']) : [];
                $skills_shown  = array_slice($all_skills, 0, 4);
                $skills_extra  = max(0, count($all_skills) - 4);
            ?>
            <div class="cand-card">
                <div class="cand-card-header">
                    <img src="<?= $avatar ?>" alt="<?= clean($cand['full_name']) ?>" class="cand-avatar">
                    <div class="cand-card-info">
                        <a href="<?= SITE_URL ?>/candidates/candidate-detail.php?id=<?= (int)$cand['id'] ?>"
                           class="cand-card-name"><?= clean($cand['full_name']) ?></a>
                        <?php if (!empty($cand['current_position'])): ?>
                            <div class="cand-card-title"><?= clean($cand['current_position']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cand-card-meta">
                    <?php if (!empty($cand['city'])): ?>
                    <span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        <?= clean($cand['city']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($cand['experience_level'])): ?>
                    <span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?= clean($cand['experience_level']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($cand['preferred_field'])): ?>
                    <span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                        <?= clean($cand['preferred_field']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($skills_shown)): ?>
                <div class="cand-card-skills">
                    <?php foreach ($skills_shown as $skill): ?>
                        <span class="cand-skill"><?= clean($skill) ?></span>
                    <?php endforeach; ?>
                    <?php if ($skills_extra > 0): ?>
                        <span class="cand-skill cand-skill-more">+<?= $skills_extra ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <a href="<?= SITE_URL ?>/candidates/candidate-detail.php?id=<?= (int)$cand['id'] ?>"
                   class="cand-card-btn"><?= t('common.view_profile') ?></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section style="padding:40px 0;">
    <div class="container">
        <div class="cta-section">
            <div class="cta-candidate">
                <h2 class="cta-title" style="color:var(--text-primary);"><?= t('home.become_candidate') ?></h2>
                <p class="cta-desc" style="color:var(--text-muted);opacity:1;"><?= t('home.candidate_desc') ?></p>
                <a href="<?= SITE_URL ?>/auth/register.php?role=candidate" class="btn btn-primary"><?= t('home.register_now') ?></a>
            </div>
            <div class="cta-employer">
                <h2 class="cta-title"><?= t('home.become_employer') ?></h2>
                <p class="cta-desc"><?= t('home.employer_desc') ?></p>
                <a href="<?= SITE_URL ?>/auth/register.php?role=employer" class="btn btn-white"><?= t('home.register_now') ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<div class="newsletter-strip">
    <div class="container">
        <div class="newsletter-inner">
            <div class="newsletter-text">
                <h3><?= t('home.newsletter_title') ?></h3>
                <p><?= t('home.newsletter_desc') ?></p>
            </div>
            <form class="newsletter-form" action="<?= SITE_URL ?>/actions/subscribe-newsletter.php" method="POST">
                <?= csrf_field() ?>
                <input type="email" name="email" placeholder="<?= t('home.email_placeholder') ?>" required>
                <button type="submit" class="btn btn-primary"><?= t('home.subscribe') ?></button>
            </form>
            <div class="newsletter-stats">
                <div>
                    <span class="newsletter-stat-num"><?= number_format($total_jobs) ?>+</span>
                    <span class="newsletter-stat-label"><?= t('home.live_jobs') ?></span>
                </div>
                <div>
                    <span class="newsletter-stat-num"><?= number_format($total_companies) ?>+</span>
                    <span class="newsletter-stat-label"><?= t('home.companies') ?></span>
                </div>
                <div>
                    <span class="newsletter-stat-num"><?= number_format($total_candidates) ?>+</span>
                    <span class="newsletter-stat-label"><?= t('home.candidates') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
