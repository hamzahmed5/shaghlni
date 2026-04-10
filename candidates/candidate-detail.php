<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

$db  = get_db();
$uid = (int)get_param('id');

if (!$uid) { header('Location: ' . site_url('candidates/browse.php')); exit; }

$user = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'candidate'");
$user->execute([':id' => $uid]);
$user = $user->fetch();
if (!$user) { header('Location: ' . site_url('404.php')); exit; }

$profile = get_candidate_profile($uid);
if (!$profile || !($profile['is_public'] ?? 1)) { header('Location: ' . site_url('candidates/browse.php')); exit; }

// Skills
$skills = $db->prepare("SELECT skill_name, proficiency FROM candidate_skills WHERE candidate_profile_id = :pid");
$skills->execute([':pid' => $profile['id']]);
$skills = $skills->fetchAll();

// Social links
$socials = $db->prepare("SELECT platform, url FROM candidate_social_links WHERE user_id = :uid");
$socials->execute([':uid' => $uid]);
$socials = $socials->fetchAll();

// CVs
$cvs = $db->prepare("SELECT * FROM candidate_cvs WHERE candidate_profile_id = :pid ORDER BY is_default DESC LIMIT 1");
$cvs->execute([':pid' => $profile['id']]);
$cv = $cvs->fetch();

// Employer save state
$is_saved_cand = false;
$ep = null;
if (is_logged_in() && current_role() === 'employer') {
    $ep = get_employer_profile(current_user_id());
    if ($ep) {
        $saved = $db->prepare("SELECT id FROM saved_candidates WHERE employer_profile_id = :eid AND candidate_profile_id = :cid");
        $saved->execute([':eid'=>$ep['id'], ':cid'=>$profile['id']]);
        $is_saved_cand = (bool)$saved->fetch();
    }
}

$page_title = clean($user['full_name']) . ' – Candidate Profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1 class="page-banner-title">Candidate Profile</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Candidates','url'=>site_url('candidates/browse.php')],['label'=>clean($user['full_name']),'url'=>'']]) ?>
    </div>
</div>

<section style="padding:40px 0 64px;">
    <div class="container">
        <div class="job-detail-layout">

            <!-- Left -->
            <div class="job-detail-main">

                <!-- Profile Header -->
                <div class="sidebar-card" style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= upload_url($user['avatar']) ?>" alt="" style="width:90px;height:90px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    <?php else: ?>
                        <div class="avatar-placeholder" style="width:90px;height:90px;font-size:32px;flex-shrink:0;"><?= strtoupper(substr($user['full_name'],0,2)) ?></div>
                    <?php endif; ?>
                    <div style="flex:1;">
                        <h2 style="font-size:22px;font-weight:700;margin-bottom:4px;"><?= clean($user['full_name']) ?></h2>
                        <?php if ($profile['current_position']): ?><div style="font-size:15px;color:var(--text-secondary);margin-bottom:6px;"><?= clean($profile['current_position']) ?></div><?php endif; ?>
                        <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:var(--text-muted);">
                            <?php if ($profile['city']): ?><span><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg> <?= clean($profile['city']) ?></span><?php endif; ?>
                            <?php if ($profile['experience_level']): ?><span><?= clean($profile['experience_level']) ?></span><?php endif; ?>
                            <?php if ($profile['preferred_field']): ?><span><?= clean($profile['preferred_field']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <?php if (is_logged_in() && current_role() === 'employer' && $ep): ?>
                            <button class="btn btn-primary btn-save-candidate" data-candidate-id="<?= $profile['id'] ?>" style="min-width:140px;">
                                <?= $is_saved_cand ? 'Saved' : 'Save Candidate' ?>
                            </button>
                        <?php elseif (!is_logged_in()): ?>
                            <a href="<?= site_url('auth/login.php') ?>" class="btn btn-primary">Sign In to Connect</a>
                        <?php endif; ?>
                        <?php if ($cv): ?>
                            <a href="<?= upload_url($cv['file_path']) ?>" target="_blank" class="btn btn-outline">Download CV</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bio -->
                <?php if ($profile['bio']): ?>
                <div class="job-section">
                    <h2 class="job-section-title">About</h2>
                    <div class="job-content"><?= nl2br(clean($profile['bio'])) ?></div>
                </div>
                <?php endif; ?>

                <!-- Skills -->
                <?php if (!empty($skills)): ?>
                <div class="job-section">
                    <h2 class="job-section-title">Skills</h2>
                    <div class="skills-tags">
                        <?php foreach ($skills as $s): ?>
                            <span class="skill-tag"><?= clean($s['skill_name']) ?><?php if ($s['proficiency']): ?> <em style="font-size:11px;opacity:0.7;"><?= clean($s['proficiency']) ?></em><?php endif; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Experience -->
                <?php if ($profile['work_experience']): ?>
                <div class="job-section">
                    <h2 class="job-section-title">Work Experience</h2>
                    <div class="job-content"><?= nl2br(clean($profile['work_experience'])) ?></div>
                </div>
                <?php endif; ?>

                <!-- Education -->
                <?php if ($profile['education']): ?>
                <div class="job-section">
                    <h2 class="job-section-title">Education</h2>
                    <div class="job-content"><?= nl2br(clean($profile['education'])) ?></div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Sidebar -->
            <aside class="job-detail-sidebar">
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">Personal Info</h3>
                    <ul class="sidebar-info-list">
                        <?php if ($profile['expected_salary_min'] || $profile['expected_salary_max']): ?>
                        <li><span class="info-label">Expected Salary</span><span><?= format_salary($profile['expected_salary_min'] ?? 0, $profile['expected_salary_max'] ?? 0, 'JOD', 'Monthly') ?></span></li>
                        <?php endif; ?>
                        <?php if ($profile['experience_level']): ?>
                        <li><span class="info-label">Experience</span><span><?= clean($profile['experience_level']) ?></span></li>
                        <?php endif; ?>
                        <?php if ($profile['education_level']): ?>
                        <li><span class="info-label">Education</span><span><?= clean($profile['education_level']) ?></span></li>
                        <?php endif; ?>
                        <?php if ($profile['city']): ?>
                        <li><span class="info-label">Location</span><span><?= clean($profile['city']) ?>, Jordan</span></li>
                        <?php endif; ?>
                        <?php if ($profile['preferred_field']): ?>
                        <li><span class="info-label">Preferred Field</span><span><?= clean($profile['preferred_field']) ?></span></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if (!empty($socials)): ?>
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">Social Links</h3>
                    <div style="display:flex;flex-direction:column;gap:8px;">
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
