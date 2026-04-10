<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('candidate');

$db   = get_db();
$uid  = current_user_id();
$user = current_user();
$cp   = get_candidate_profile($uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));

    $full_name       = clean(post('full_name'));
    $current_position= clean(post('current_position'));
    $city            = clean(post('city'));
    $experience_level= clean(post('experience_level'));
    $education_level = clean(post('education_level'));
    $phone           = clean(post('phone'));
    $website         = clean(post('website'));

    // Avatar upload
    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $new_avatar = handle_upload('avatar', UPLOAD_PATH . 'avatars/', ['image/jpeg','image/png','image/webp']);
        if ($new_avatar) $avatar = 'avatars/' . basename($new_avatar);
    }

    // CV upload
    if (!empty($_FILES['cv_file']['name'])) {
        $new_cv = handle_upload('cv_file', UPLOAD_PATH . 'cvs/', ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        if ($new_cv) {
            $cv_name = clean(post('cv_label')) ?: 'My Resume';
            // Deactivate old default
            $db->prepare("UPDATE candidate_cvs SET is_default=0 WHERE candidate_profile_id=:cid")->execute([':cid'=>$cp['id']]);
            $db->prepare("INSERT INTO candidate_cvs (candidate_profile_id, label, file_path, is_default) VALUES (:cid,:label,:path,1)")
               ->execute([':cid'=>$cp['id'],':label'=>$cv_name,':path'=>'cvs/' . basename($new_cv)]);
        }
    }

    // Update user
    $db->prepare("UPDATE users SET full_name=:fn, phone=:ph, avatar=:av WHERE id=:id")
       ->execute([':fn'=>$full_name,':ph'=>$phone,':av'=>$avatar,':id'=>$uid]);

    // Upsert candidate profile
    if ($cp) {
        $db->prepare("UPDATE candidate_profiles SET current_position=:cp,city=:city,experience_level=:exp,education_level=:edu,website=:web WHERE user_id=:uid")
           ->execute([':cp'=>$current_position,':city'=>$city,':exp'=>$experience_level,':edu'=>$education_level,':web'=>$website,':uid'=>$uid]);
    } else {
        $db->prepare("INSERT INTO candidate_profiles (user_id,current_position,city,experience_level,education_level,website) VALUES (:uid,:cp,:city,:exp,:edu,:web)")
           ->execute([':uid'=>$uid,':cp'=>$current_position,':city'=>$city,':exp'=>$experience_level,':edu'=>$education_level,':web'=>$website]);
    }

    // Refresh session user
    $updated = $db->prepare("SELECT * FROM users WHERE id=:id");
    $updated->execute([':id'=>$uid]);
    $_SESSION['user'] = $updated->fetch();

    flash('success', 'Personal information updated successfully.');
    redirect(site_url('candidate/settings/personal.php'));
}

$cp = get_candidate_profile($uid); // refresh
$user = current_user();

// Uploaded CVs
$cvs = $db->prepare("SELECT * FROM candidate_cvs WHERE candidate_profile_id = :cid ORDER BY is_default DESC");
$cvs->execute([':cid' => $cp['id'] ?? 0]);
$cvs = $cvs->fetchAll();

$page_title = 'Settings – Personal Info';
include __DIR__ . '/../../includes/header_dashboard.php';
include __DIR__ . '/../../includes/sidebar_candidate.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Settings</h1>
    </div>

    <?= render_flash() ?>

    <!-- Settings Tabs -->
    <div class="settings-tabs">
        <a href="<?= site_url('candidate/settings/personal.php') ?>"   class="settings-tab active">Personal Info</a>
        <a href="<?= site_url('candidate/settings/profile.php') ?>"    class="settings-tab">Profile</a>
        <a href="<?= site_url('candidate/settings/social-links.php') ?>" class="settings-tab">Social Links</a>
        <a href="<?= site_url('candidate/settings/account.php') ?>"    class="settings-tab">Account</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="settings-form">
        <?= csrf_field() ?>

        <!-- Avatar -->
        <div class="settings-avatar-row">
            <div class="settings-avatar-wrap">
                <?php if ($user['avatar']): ?>
                    <img src="<?= upload_url($user['avatar']) ?>" id="avatarPreview" alt="">
                <?php else: ?>
                    <div class="avatar-placeholder" id="avatarPreview" style="width:100%;height:100%;font-size:28px;"><?= strtoupper(substr($user['full_name'],0,2)) ?></div>
                <?php endif; ?>
            </div>
            <div>
                <div class="upload-area" style="width:280px;padding:16px;" onclick="document.getElementById('avatarInput').click()">
                    <p style="margin:0;font-size:13px;">Click to upload profile photo</p>
                    <p style="margin:4px 0 0;font-size:12px;color:var(--text-muted);">JPG, PNG, or WEBP. Max 2MB.</p>
                </div>
                <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none;"
                    onchange="previewAvatar(this)">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= clean($user['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Current Job Title</label>
                <input type="text" name="current_position" class="form-control" value="<?= clean($cp['current_position'] ?? '') ?>" placeholder="e.g. Senior Developer">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?= clean($user['phone'] ?? '') ?>" placeholder="+962 7X XXX XXXX">
            </div>
            <div class="form-group">
                <label class="form-label">City</label>
                <select name="city" class="form-select">
                    <option value="">Select City</option>
                    <?php foreach (JO_CITIES as $c): ?>
                        <option value="<?= $c ?>" <?= ($cp['city'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Experience Level</label>
                <select name="experience_level" class="form-select">
                    <option value="">Select Level</option>
                    <?php foreach (EXP_LEVELS as $l): ?>
                        <option value="<?= $l ?>" <?= ($cp['experience_level'] ?? '') === $l ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Education Level</label>
                <select name="education_level" class="form-select">
                    <option value="">Select Level</option>
                    <?php foreach (EDU_LEVELS as $l): ?>
                        <option value="<?= $l ?>" <?= ($cp['education_level'] ?? '') === $l ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Website / Portfolio</label>
            <input type="url" name="website" class="form-control" value="<?= clean($cp['website'] ?? '') ?>" placeholder="https://...">
        </div>

        <!-- CV Upload -->
        <div class="form-group" style="margin-top:24px;">
            <label class="form-label">Upload CV/Resume</label>
            <div class="upload-area" onclick="document.getElementById('cvInput').click()">
                <svg width="28" height="28" fill="none" stroke="#C8CDD5" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <p>Drop your resume here or <span class="link-primary">browse</span></p>
                <p style="font-size:12px;color:var(--text-muted);">Supports PDF, DOC, DOCX</p>
                <span id="cvFileName" style="font-size:13px;color:var(--primary);"></span>
            </div>
            <input type="file" id="cvInput" name="cv_file" accept=".pdf,.doc,.docx" style="display:none;"
                onchange="document.getElementById('cvFileName').textContent = this.files[0] ? this.files[0].name : ''">
            <input type="text" name="cv_label" class="form-control" style="margin-top:8px;" placeholder="Label for this CV (e.g. 'My Resume 2025')">
        </div>

        <!-- Existing CVs -->
        <?php if (!empty($cvs)): ?>
        <div class="cv-cards">
            <?php foreach ($cvs as $cv): ?>
            <div class="cv-card">
                <svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <div class="cv-card-info">
                    <div class="cv-card-name"><?= clean($cv['label']) ?> <?= $cv['is_default'] ? '<span class="badge-primary" style="font-size:11px;padding:2px 6px;margin-left:6px;">Default</span>' : '' ?></div>
                    <div class="cv-card-date">Uploaded <?= date('M j, Y', strtotime($cv['created_at'])) ?></div>
                </div>
                <div class="cv-card-actions">
                    <a href="<?= upload_url($cv['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">View</a>
                    <a href="<?= site_url('actions/delete-cv.php?id=' . $cv['id'] . '&csrf=' . csrf_token()) ?>" class="btn btn-outline btn-sm" style="color:var(--danger);" onclick="return confirm('Delete this CV?')">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="margin-top:24px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>

    </form>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var prev = document.getElementById('avatarPreview');
            if (prev.tagName === 'IMG') {
                prev.src = e.target.result;
            } else {
                var img = document.createElement('img');
                img.id  = 'avatarPreview';
                img.src = e.target.result;
                img.style.cssText = prev.style.cssText;
                prev.replaceWith(img);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
