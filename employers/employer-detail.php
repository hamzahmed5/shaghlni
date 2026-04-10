<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

$db  = get_db();
$uid = (int)get_param('id');

if (!$uid) { header('Location: ' . site_url('employers/browse.php')); exit; }

$emp = get_employer_profile($uid);
if (!$emp) { header('Location: ' . site_url('404.php')); exit; }

// Open jobs
$jobs = $db->prepare("SELECT j.*, ep.company_name, ep.logo, ep.city AS company_city
                       FROM jobs j
                       LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
                       WHERE j.employer_id = :uid AND j.status='active' AND j.expires_at>=CURDATE()
                       ORDER BY j.created_at DESC LIMIT 6");
$jobs->execute([':uid' => $uid]);
$jobs = $jobs->fetchAll();

// Social links
$socials = $db->prepare("SELECT platform, url FROM employer_social_links WHERE user_id = :uid");
$socials->execute([':uid' => $uid]);
$socials = $socials->fetchAll();

$candidate_profile_id = null;
if (is_logged_in() && current_role() === 'candidate') {
    $cp = get_candidate_profile(current_user_id());
    if ($cp) $candidate_profile_id = $cp['id'];
}

$page_title = clean($emp['company_name']) . ' – Company Profile';
include __DIR__ . '/../includes/header.php';
?>

<!-- Company Banner -->
<div class="company-banner" style="background:var(--bg-light);border-bottom:1px solid var(--border);padding-bottom:0;">
    <div class="company-banner-bg" style="height:200px;background:linear-gradient(135deg,var(--primary) 0%,#0053a4 100%);position:relative;overflow:hidden;">
        <?php if ($emp['banner']): ?>
            <img src="<?= upload_url($emp['banner']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
        <?php endif; ?>
    </div>
    <div class="container" style="position:relative;">
        <div class="company-profile-header">
            <div class="company-logo-wrap">
                <?php if ($emp['logo']): ?>
                    <img src="<?= upload_url($emp['logo']) ?>" alt="<?= clean($emp['company_name']) ?>">
                <?php else: ?>
                    <div class="logo-placeholder-xl"><?= strtoupper(substr($emp['company_name'],0,2)) ?></div>
                <?php endif; ?>
            </div>
            <div class="company-profile-info">
                <h1><?= clean($emp['company_name']) ?></h1>
                <div class="company-profile-meta">
                    <?php if ($emp['industry']): ?><span><?= clean($emp['industry']) ?></span><?php endif; ?>
                    <?php if ($emp['city']): ?><span><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg> <?= clean($emp['city']) ?></span><?php endif; ?>
                    <?php if ($emp['website']): ?><a href="<?= clean($emp['website']) ?>" target="_blank" style="color:var(--primary);"><?= clean($emp['website']) ?></a><?php endif; ?>
                </div>
            </div>
            <div class="company-profile-actions">
                <a href="<?= site_url('jobs/find-job.php') ?>" class="btn btn-primary">View Open Jobs (<?= count($jobs) ?>)</a>
                <?php foreach ($socials as $s): ?>
                    <a href="<?= clean($s['url']) ?>" target="_blank" class="btn btn-icon" title="<?= clean($s['platform']) ?>">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<section style="padding:40px 0 64px;">
    <div class="container">
        <div class="job-detail-layout">

            <!-- Left -->
            <div class="job-detail-main">

                <?php if ($emp['description']): ?>
                <div class="job-section">
                    <h2 class="job-section-title">About the Company</h2>
                    <div class="job-content"><?= nl2br(clean($emp['description'])) ?></div>
                </div>
                <?php endif; ?>

                <!-- Open Jobs -->
                <?php if (!empty($jobs)): ?>
                <div class="job-section">
                    <h2 class="job-section-title">Open Positions (<?= count($jobs) ?>)</h2>
                    <div class="job-cards-grid" style="grid-template-columns:1fr 1fr;">
                        <?php foreach ($jobs as $j): ?>
                            <?= render_job_card($j, true, $candidate_profile_id) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="job-section">
                    <div class="empty-state" style="padding:32px 0;">
                        <p>No open positions at the moment.</p>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Right -->
            <aside class="job-detail-sidebar">
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">Company Info</h3>
                    <ul class="sidebar-info-list">
                        <?php if ($emp['founded_year']): ?>
                        <li><span class="info-label">Founded</span><span><?= clean($emp['founded_year']) ?></span></li>
                        <?php endif; ?>
                        <?php if ($emp['company_size']): ?>
                        <li><span class="info-label">Company Size</span><span><?= clean($emp['company_size']) ?></span></li>
                        <?php endif; ?>
                        <?php if ($emp['industry']): ?>
                        <li><span class="info-label">Industry</span><span><?= clean($emp['industry']) ?></span></li>
                        <?php endif; ?>
                        <?php if ($emp['city']): ?>
                        <li><span class="info-label">Location</span><span><?= clean($emp['city']) ?>, Jordan</span></li>
                        <?php endif; ?>
                        <?php if ($emp['website']): ?>
                        <li><span class="info-label">Website</span><a href="<?= clean($emp['website']) ?>" target="_blank" style="color:var(--primary);font-size:13px;word-break:break-all;"><?= clean($emp['website']) ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if (!empty($socials)): ?>
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">Follow Us</h3>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <?php foreach ($socials as $s): ?>
                            <a href="<?= clean($s['url']) ?>" target="_blank" class="btn btn-outline btn-sm"><?= clean($s['platform']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
