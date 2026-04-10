<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('candidate');

$db  = get_db();
$uid = current_user_id();
$cp  = get_candidate_profile($uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));
    $bio             = clean(post('bio'));
    $preferred_field = clean(post('preferred_field'));
    $salary_min      = (int)post('salary_min');
    $salary_max      = (int)post('salary_max');
    $work_experience = clean(post('work_experience'));
    $education       = clean(post('education'));
    $is_public       = post('is_public') ? 1 : 0;

    if ($cp) {
        $db->prepare("UPDATE candidate_profiles SET bio=:bio,preferred_field=:pf,expected_salary_min=:smin,expected_salary_max=:smax,work_experience=:we,education=:edu,is_public=:pub WHERE user_id=:uid")
           ->execute([':bio'=>$bio,':pf'=>$preferred_field,':smin'=>$salary_min,':smax'=>$salary_max,':we'=>$work_experience,':edu'=>$education,':pub'=>$is_public,':uid'=>$uid]);
    } else {
        $db->prepare("INSERT INTO candidate_profiles (user_id,bio,preferred_field,expected_salary_min,expected_salary_max,work_experience,education,is_public) VALUES (:uid,:bio,:pf,:smin,:smax,:we,:edu,:pub)")
           ->execute([':uid'=>$uid,':bio'=>$bio,':pf'=>$preferred_field,':smin'=>$salary_min,':smax'=>$salary_max,':we'=>$work_experience,':edu'=>$education,':pub'=>$is_public]);
        $cp = get_candidate_profile($uid);
    }

    // Skills
    $skills_raw = post('skills');
    if ($skills_raw) {
        $skill_list = array_filter(array_map('trim', explode(',', $skills_raw)));
        $db->prepare("DELETE FROM candidate_skills WHERE candidate_profile_id = :cid")->execute([':cid'=>$cp['id']]);
        $ins_skill = $db->prepare("INSERT INTO candidate_skills (candidate_profile_id, skill_name) VALUES (:cid, :skill)");
        foreach (array_slice($skill_list, 0, 30) as $sk) {
            $ins_skill->execute([':cid'=>$cp['id'], ':skill'=>clean($sk)]);
        }
    }

    flash('success', 'Profile updated successfully.');
    redirect(site_url('candidate/settings/profile.php'));
}

$cp = get_candidate_profile($uid);
$existing_skills = $db->prepare("SELECT skill_name FROM candidate_skills WHERE candidate_profile_id=:cid");
$existing_skills->execute([':cid' => $cp['id'] ?? 0]);
$skills_str = implode(', ', $existing_skills->fetchAll(PDO::FETCH_COLUMN));

$page_title = 'Settings – Profile';
include __DIR__ . '/../../includes/header_dashboard.php';
include __DIR__ . '/../../includes/sidebar_candidate.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Settings</h1>
    </div>
    <?= render_flash() ?>

    <div class="settings-tabs">
        <a href="<?= site_url('candidate/settings/personal.php') ?>"   class="settings-tab">Personal Info</a>
        <a href="<?= site_url('candidate/settings/profile.php') ?>"    class="settings-tab active">Profile</a>
        <a href="<?= site_url('candidate/settings/social-links.php') ?>" class="settings-tab">Social Links</a>
        <a href="<?= site_url('candidate/settings/account.php') ?>"    class="settings-tab">Account</a>
    </div>

    <form method="POST" class="settings-form">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Professional Bio</label>
            <textarea name="bio" class="form-control" rows="5" placeholder="Tell employers about yourself..."><?= clean($cp['bio'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Preferred Job Category</label>
                <select name="preferred_field" class="form-select">
                    <option value="">Select Category</option>
                    <?php foreach (JOB_CATEGORIES as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($cp['preferred_field'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Profile Visibility</label>
                <select name="is_public" class="form-select">
                    <option value="1" <?= ($cp['is_public'] ?? 1) ? 'selected' : '' ?>>Public – visible to employers</option>
                    <option value="0" <?= !($cp['is_public'] ?? 1) ? 'selected' : '' ?>>Private – hidden from search</option>
                </select>
            </div>
        </div>

        <!-- Expected Salary -->
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Expected Salary Min (JOD/mo)</label>
                <input type="number" name="salary_min" class="form-control" value="<?= (int)($cp['expected_salary_min'] ?? 0) ?>" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Expected Salary Max (JOD/mo)</label>
                <input type="number" name="salary_max" class="form-control" value="<?= (int)($cp['expected_salary_max'] ?? 0) ?>" min="0">
            </div>
        </div>

        <!-- Skills -->
        <div class="form-group">
            <label class="form-label">Skills <span style="font-size:12px;color:var(--text-muted);">(comma separated)</span></label>
            <input type="text" name="skills" id="skillsInput" class="form-control" value="<?= clean($skills_str) ?>" placeholder="e.g. PHP, MySQL, JavaScript, React">
            <div id="skillTags" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;"></div>
        </div>

        <!-- Work Experience -->
        <div class="form-group">
            <label class="form-label">Work Experience</label>
            <textarea name="work_experience" class="form-control" rows="6" placeholder="Describe your work history..."><?= clean($cp['work_experience'] ?? '') ?></textarea>
        </div>

        <!-- Education -->
        <div class="form-group">
            <label class="form-label">Education</label>
            <textarea name="education" class="form-control" rows="4" placeholder="Describe your educational background..."><?= clean($cp['education'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<script>
// Skill tag preview
var skillInput = document.getElementById('skillsInput');
var tagContainer = document.getElementById('skillTags');
function renderTags() {
    tagContainer.innerHTML = '';
    var skills = skillInput.value.split(',').map(s => s.trim()).filter(Boolean);
    skills.forEach(function(s) {
        var span = document.createElement('span');
        span.className = 'skill-tag';
        span.textContent = s;
        tagContainer.appendChild(span);
    });
}
skillInput.addEventListener('input', renderTags);
renderTags();
</script>

</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
