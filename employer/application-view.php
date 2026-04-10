<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db  = get_db();
$uid = current_user_id();
$app_id = (int)get_param('id');

if (!$app_id) redirect(site_url('employer/applications.php'));

$app = $db->prepare("SELECT a.*, j.title AS job_title, j.id AS job_id,
                      u.full_name AS candidate_name, u.email AS candidate_email, u.avatar, u.phone,
                      cp.current_position, cp.city AS candidate_city, cp.bio, cp.experience_level,
                      cp.education_level, cp.expected_salary_min, cp.expected_salary_max, cp.id AS cp_id
                     FROM applications a
                     JOIN jobs j ON j.id=a.job_id
                     JOIN candidate_profiles cp ON cp.id=a.candidate_profile_id
                     JOIN users u ON u.id=cp.user_id
                     WHERE a.id=:id AND j.employer_id=:uid");
$app->execute([':id'=>$app_id,':uid'=>$uid]);
$app = $app->fetch();
if (!$app) redirect(site_url('employer/applications.php'));

// Skills
$skills = $db->prepare("SELECT skill_name FROM candidate_skills WHERE candidate_profile_id=:cid");
$skills->execute([':cid'=>$app['cp_id']]);
$skills = $skills->fetchAll(PDO::FETCH_COLUMN);

// CV
$cv = $db->prepare("SELECT * FROM candidate_cvs WHERE candidate_profile_id=:cid AND is_default=1 LIMIT 1");
$cv->execute([':cid'=>$app['cp_id']]);
$cv = $cv->fetch();

// Handle status update
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(post('csrf_token'));
    $new_status = clean(post('status'));
    $db->prepare("UPDATE applications SET status=:s WHERE id=:id")->execute([':s'=>$new_status,':id'=>$app_id]);
    add_notification($app['cp_id'], 'application', "Your application for \"" . $app['job_title'] . "\" has been updated to: " . $new_status, site_url('candidate/applied-jobs.php'));
    flash('success','Status updated.');
    redirect(site_url('employer/application-view.php?id='.$app_id));
}

$page_title = 'Application – ' . clean($app['candidate_name']);
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <div>
            <a href="<?= site_url('employer/applications.php?job_id='.$app['job_id']) ?>" style="font-size:13px;color:var(--text-muted);">← Back to Applications</a>
            <h1 class="dash-page-title" style="margin-top:4px;">Application Review</h1>
        </div>
    </div>

    <?= render_flash() ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

        <!-- Main -->
        <div>
            <!-- Candidate Header -->
            <div class="sidebar-card" style="display:flex;gap:20px;align-items:flex-start;">
                <?php if ($app['avatar']): ?>
                    <img src="<?= upload_url($app['avatar']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                <?php else: ?>
                    <div class="avatar-placeholder" style="width:80px;height:80px;font-size:28px;flex-shrink:0;"><?= strtoupper(substr($app['candidate_name'],0,2)) ?></div>
                <?php endif; ?>
                <div style="flex:1;">
                    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;"><?= clean($app['candidate_name']) ?></h2>
                    <?php if ($app['current_position']): ?><div style="font-size:14px;color:var(--text-secondary);"><?= clean($app['current_position']) ?></div><?php endif; ?>
                    <div style="display:flex;gap:16px;margin-top:8px;font-size:13px;color:var(--text-muted);">
                        <?php if ($app['candidate_city']): ?><span><?= clean($app['candidate_city']) ?></span><?php endif; ?>
                        <?php if ($app['candidate_email']): ?><span><?= clean($app['candidate_email']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="<?= site_url('candidates/candidate-detail.php?id='.$app['cp_id']) ?>" class="btn btn-outline btn-sm">View Profile</a>
                    <?php if ($cv): ?><a href="<?= upload_url($cv['file_path']) ?>" target="_blank" class="btn btn-primary btn-sm">Download CV</a><?php endif; ?>
                </div>
            </div>

            <!-- Cover Letter -->
            <?php if ($app['cover_letter']): ?>
            <div class="sidebar-card" style="margin-top:16px;">
                <h3 class="sidebar-card-title">Cover Letter</h3>
                <p style="font-size:14px;color:var(--text-secondary);line-height:1.7;"><?= nl2br(clean($app['cover_letter'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Resume attachment -->
            <?php if ($app['resume_path']): ?>
            <div class="sidebar-card" style="margin-top:16px;">
                <h3 class="sidebar-card-title">Attached Resume</h3>
                <a href="<?= upload_url($app['resume_path']) ?>" target="_blank" class="btn btn-outline">Download Attached Resume</a>
            </div>
            <?php endif; ?>

            <!-- Skills -->
            <?php if (!empty($skills)): ?>
            <div class="sidebar-card" style="margin-top:16px;">
                <h3 class="sidebar-card-title">Skills</h3>
                <div class="skills-tags">
                    <?php foreach ($skills as $s): ?><span class="skill-tag"><?= clean($s) ?></span><?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bio -->
            <?php if ($app['bio']): ?>
            <div class="sidebar-card" style="margin-top:16px;">
                <h3 class="sidebar-card-title">About Candidate</h3>
                <p style="font-size:14px;color:var(--text-secondary);line-height:1.7;"><?= nl2br(clean($app['bio'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <aside>
            <div class="sidebar-card">
                <h3 class="sidebar-card-title">Application Info</h3>
                <ul class="sidebar-info-list">
                    <li><span class="info-label">Job</span><span><?= clean($app['job_title']) ?></span></li>
                    <li><span class="info-label">Applied</span><span><?= date('M j, Y', strtotime($app['applied_at'])) ?></span></li>
                    <li><span class="info-label">Experience</span><span><?= clean($app['experience_level'] ?? 'N/A') ?></span></li>
                    <li><span class="info-label">Education</span><span><?= clean($app['education_level'] ?? 'N/A') ?></span></li>
                    <?php if ($app['expected_salary_min'] || $app['expected_salary_max']): ?>
                    <li><span class="info-label">Expected Salary</span><span><?= format_salary($app['expected_salary_min'] ?? 0, $app['expected_salary_max'] ?? 0, 'JOD', '') ?></span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Status Update -->
            <div class="sidebar-card" style="margin-top:16px;">
                <h3 class="sidebar-card-title">Update Status</h3>
                <form method="POST">
                    <?= csrf_field() ?>
                    <select name="status" class="form-select" style="margin-bottom:12px;">
                        <?php foreach (['pending','reviewed','shortlisted','interviewed','hired','rejected'] as $s): ?>
                            <option value="<?= $s ?>" <?= $app['status']===$s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Update Status</button>
                </form>
                <div style="margin-top:10px;text-align:center;">
                    <span class="status-badge status-<?= $app['status'] ?>" style="font-size:13px;"><?= ucfirst($app['status']) ?></span>
                </div>
            </div>
        </aside>
    </div>
</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
