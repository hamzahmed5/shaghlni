<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

$db  = get_db();
$id  = (int)get_param('id');

if (!$id) { header('Location: ' . site_url('jobs/find-job.php')); exit; }

$job = $db->prepare("SELECT j.*, ep.company_name, ep.logo, ep.banner, ep.website, ep.city AS company_city,
                      ep.company_size, ep.industry, ep.founded_year, ep.description AS company_desc,
                      ep.user_id AS employer_uid
                     FROM jobs j
                     LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
                     WHERE j.id = :id AND j.status = 'active'");
$job->execute([':id' => $id]);
$job = $job->fetch();

if (!$job) { header('Location: ' . site_url('404.php')); exit; }

// Benefits
$benefits = $db->prepare("SELECT benefit FROM job_benefits WHERE job_id = :jid");
$benefits->execute([':jid' => $id]);
$benefits = $benefits->fetchAll(PDO::FETCH_COLUMN);

// Related jobs
$related = $db->prepare("SELECT j.*, ep.company_name, ep.logo, ep.city AS company_city
                         FROM jobs j
                         LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
                         WHERE j.id != :jid AND j.status='active' AND j.expires_at>=CURDATE()
                           AND (j.category = :cat OR j.location = :loc)
                         ORDER BY j.created_at DESC LIMIT 3");
$related->execute([':jid'=>$id, ':cat'=>$job['category'], ':loc'=>$job['location']]);
$related = $related->fetchAll();

// Candidate state
$candidate_profile_id = null;
$already_applied      = false;
$job_saved            = false;
if (is_logged_in() && current_role() === 'candidate') {
    $cp = get_candidate_profile(current_user_id());
    if ($cp) {
        $candidate_profile_id = $cp['id'];
        $already_applied      = has_applied($cp['id'], $id);
        $job_saved            = is_job_saved($cp['id'], $id);
    }
}

$page_title = clean($job['title']) . ' – ' . clean($job['company_name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1 class="page-banner-title">Job Details</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Find Job','url'=>site_url('jobs/find-job.php')],['label'=>clean($job['title']),'url'=>'']]) ?>
    </div>
</div>

<section style="padding:40px 0 64px;">
    <div class="container">

        <!-- Job Header Card -->
        <div class="job-detail-header-card">
            <div class="job-detail-logo-wrap">
                <?php if ($job['logo']): ?>
                    <img src="<?= upload_url($job['logo']) ?>" alt="<?= clean($job['company_name']) ?>">
                <?php else: ?>
                    <div class="logo-placeholder-lg"><?= strtoupper(substr($job['company_name'], 0, 2)) ?></div>
                <?php endif; ?>
            </div>
            <div class="job-detail-header-info">
                <h1 class="job-detail-title"><?= clean($job['title']) ?></h1>
                <div class="job-detail-meta">
                    <a href="<?= site_url('employers/employer-detail.php?id=' . $job['employer_uid']) ?>" class="job-detail-company">
                        <?= clean($job['company_name']) ?>
                    </a>
                    <span class="job-detail-sep">•</span>
                    <span><?= clean($job['location']) ?></span>
                    <span class="job-detail-sep">•</span>
                    <span><?= time_ago($job['created_at']) ?></span>
                </div>
                <div class="job-detail-badges">
                    <span class="job-type-badge <?= job_type_class($job['job_type']) ?>"><?= $job['job_type'] ?></span>
                    <span class="job-detail-salary"><?= format_salary($job['salary_min'], $job['salary_max'], $job['salary_currency'] ?? 'JOD', $job['salary_type'] ?? 'Monthly') ?></span>
                    <?php if (days_remaining($job['expires_at']) <= 7): ?>
                        <span class="badge-urgent">Closing Soon</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="job-detail-actions">
                <?php if (is_logged_in() && current_role() === 'candidate'): ?>
                    <?php if ($already_applied): ?>
                        <button class="btn btn-outline" disabled>Already Applied</button>
                    <?php else: ?>
                        <button class="btn btn-primary" onclick="openApplyModal()">Apply Now</button>
                    <?php endif; ?>
                    <button class="btn btn-icon bookmark-btn <?= $job_saved ? 'saved' : '' ?>"
                            data-job-id="<?= $id ?>"
                            title="<?= $job_saved ? 'Saved' : 'Save Job' ?>">
                        <svg width="18" height="18" fill="<?= $job_saved ? 'var(--primary)' : 'none' ?>" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                    </button>
                <?php elseif (!is_logged_in()): ?>
                    <a href="<?= site_url('auth/login.php') ?>?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary">Apply Now</a>
                <?php else: ?>
                    <a href="<?= site_url('employer/post-job.php') ?>" class="btn btn-primary">Post a Job</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="job-detail-layout">

            <!-- Left: Description -->
            <div class="job-detail-main">

                <!-- Overview Grid -->
                <div class="job-overview-grid">
                    <div class="job-overview-item">
                        <div class="overview-icon"><svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                        <div><div class="overview-label">Date Posted</div><div class="overview-value"><?= date('M j, Y', strtotime($job['created_at'])) ?></div></div>
                    </div>
                    <div class="job-overview-item">
                        <div class="overview-icon"><svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                        <div><div class="overview-label">Deadline</div><div class="overview-value"><?= date('M j, Y', strtotime($job['expires_at'])) ?></div></div>
                    </div>
                    <div class="job-overview-item">
                        <div class="overview-icon"><svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                        <div><div class="overview-label">Location</div><div class="overview-value"><?= clean($job['location']) ?></div></div>
                    </div>
                    <div class="job-overview-item">
                        <div class="overview-icon"><svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
                        <div><div class="overview-label">Salary</div><div class="overview-value"><?= format_salary($job['salary_min'], $job['salary_max'], $job['salary_currency'] ?? 'JOD', '') ?></div></div>
                    </div>
                    <div class="job-overview-item">
                        <div class="overview-icon"><svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/></svg></div>
                        <div><div class="overview-label">Job Type</div><div class="overview-value"><?= clean($job['job_type']) ?></div></div>
                    </div>
                    <div class="job-overview-item">
                        <div class="overview-icon"><svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
                        <div><div class="overview-label">Experience</div><div class="overview-value"><?= clean($job['experience_level']) ?></div></div>
                    </div>
                </div>

                <!-- Description -->
                <div class="job-section">
                    <h2 class="job-section-title">Job Description</h2>
                    <div class="job-content"><?= nl2br(clean($job['description'])) ?></div>
                </div>

                <?php if (!empty($job['requirements'])): ?>
                <div class="job-section">
                    <h2 class="job-section-title">Requirements</h2>
                    <div class="job-content"><?= nl2br(clean($job['requirements'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($job['responsibilities'])): ?>
                <div class="job-section">
                    <h2 class="job-section-title">Responsibilities</h2>
                    <div class="job-content"><?= nl2br(clean($job['responsibilities'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($benefits)): ?>
                <div class="job-section">
                    <h2 class="job-section-title">Benefits</h2>
                    <div class="benefits-grid">
                        <?php foreach ($benefits as $b): ?>
                            <div class="benefit-item">
                                <svg width="16" height="16" fill="none" stroke="var(--primary)" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                <?= clean($b) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Share -->
                <div class="job-section">
                    <h2 class="job-section-title">Share this Job</h2>
                    <div class="share-links">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/jobs/job-detail.php?id=' . $id) ?>" target="_blank" class="share-btn share-fb">Facebook</a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/jobs/job-detail.php?id=' . $id) ?>&text=<?= urlencode($job['title']) ?>" target="_blank" class="share-btn share-tw">Twitter</a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(SITE_URL . '/jobs/job-detail.php?id=' . $id) ?>" target="_blank" class="share-btn share-li">LinkedIn</a>
                        <button onclick="copyLink()" class="share-btn share-copy">Copy Link</button>
                    </div>
                </div>

            </div><!-- /.job-detail-main -->

            <!-- Right: Company + Job Info -->
            <aside class="job-detail-sidebar">

                <!-- About Company -->
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">About Company</h3>
                    <?php if ($job['logo']): ?>
                        <img src="<?= upload_url($job['logo']) ?>" alt="" style="width:56px;height:56px;object-fit:contain;border:1px solid var(--border);border-radius:8px;margin-bottom:12px;">
                    <?php else: ?>
                        <div class="logo-placeholder" style="width:56px;height:56px;font-size:18px;margin-bottom:12px;"><?= strtoupper(substr($job['company_name'],0,2)) ?></div>
                    <?php endif; ?>
                    <h4 style="font-size:16px;font-weight:600;margin-bottom:4px;"><?= clean($job['company_name']) ?></h4>
                    <?php if ($job['company_city']): ?><p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;"><?= clean($job['company_city']) ?></p><?php endif; ?>
                    <?php if ($job['company_desc']): ?><p style="font-size:14px;color:var(--text-secondary);line-height:1.6;"><?= clean(substr($job['company_desc'],0,200)) ?>...</p><?php endif; ?>
                    <a href="<?= site_url('employers/employer-detail.php?id=' . $job['employer_uid']) ?>" class="btn btn-outline btn-sm" style="margin-top:12px;width:100%;text-align:center;">View Company</a>
                </div>

                <!-- Job Info -->
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">Job Info</h3>
                    <ul class="sidebar-info-list">
                        <?php if ($job['industry']): ?>
                        <li><span class="info-label">Industry</span><span><?= clean($job['industry']) ?></span></li>
                        <?php endif; ?>
                        <li><span class="info-label">Job Level</span><span><?= clean($job['experience_level']) ?></span></li>
                        <li><span class="info-label">Education</span><span><?= clean($job['education_level'] ?? 'N/A') ?></span></li>
                        <?php if ($job['vacancy_count']): ?>
                        <li><span class="info-label">Vacancies</span><span><?= (int)$job['vacancy_count'] ?></span></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Apply CTA -->
                <?php if (is_logged_in() && current_role() === 'candidate' && !$already_applied): ?>
                <div class="sidebar-card" style="text-align:center;">
                    <p style="font-size:14px;color:var(--text-secondary);margin-bottom:16px;">Interested in this job?</p>
                    <button onclick="openApplyModal()" class="btn btn-primary" style="width:100%;">Apply Now</button>
                </div>
                <?php endif; ?>

            </aside>
        </div>

        <!-- Related Jobs -->
        <?php if (!empty($related)): ?>
        <div style="margin-top:56px;">
            <h2 class="section-title" style="margin-bottom:24px;">Related Jobs</h2>
            <div class="job-cards-grid">
                <?php foreach ($related as $rj): ?>
                    <?= render_job_card($rj, true, $candidate_profile_id) ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- Apply Modal -->
<?php if (is_logged_in() && current_role() === 'candidate' && !$already_applied): ?>
<div id="applyModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Apply for: <?= clean($job['title']) ?></h3>
            <button class="modal-close" onclick="closeApplyModal()">×</button>
        </div>
        <form method="POST" action="<?= site_url('actions/apply-job.php') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="job_id" value="<?= $id ?>">
            <div class="form-group">
                <label class="form-label">Cover Letter</label>
                <textarea name="cover_letter" class="form-control" rows="5" placeholder="Tell the employer why you're a great fit..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Attach Resume (optional – uses your profile CV if not uploaded)</label>
                <div class="upload-area" onclick="document.getElementById('resumeFile').click()">
                    <svg width="32" height="32" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p>Click to upload or drag and drop</p>
                    <span id="resumeFileName" style="font-size:13px;color:var(--primary);"></span>
                </div>
                <input type="file" id="resumeFile" name="resume" accept=".pdf,.doc,.docx" style="display:none;"
                    onchange="document.getElementById('resumeFileName').textContent = this.files[0] ? this.files[0].name : ''">
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
                <button type="button" class="btn btn-outline" onclick="closeApplyModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openApplyModal()  { document.getElementById('applyModal').style.display='flex'; }
function closeApplyModal() { document.getElementById('applyModal').style.display='none'; }

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        alert('Link copied to clipboard!');
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeApplyModal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
