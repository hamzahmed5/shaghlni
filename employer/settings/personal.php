<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db   = get_db();
$uid  = current_user_id();
$user = current_user();
$ep   = get_employer_profile($uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));
    $full_name = clean(post('full_name'));
    $phone     = clean(post('phone'));
    $title     = clean(post('job_title'));

    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $up = handle_upload('avatar', UPLOAD_PATH . 'avatars/', ['image/jpeg','image/png','image/webp']);
        if ($up) $avatar = 'avatars/' . basename($up);
    }

    $db->prepare("UPDATE users SET full_name=:fn,phone=:ph,avatar=:av WHERE id=:id")
       ->execute([':fn'=>$full_name,':ph'=>$phone,':av'=>$avatar,':id'=>$uid]);

    if ($ep) {
        $db->prepare("UPDATE employer_profiles SET phone=:ph WHERE user_id=:uid")->execute([':ph'=>$phone,':uid'=>$uid]);
    }

    $_SESSION['user'] = $db->query("SELECT * FROM users WHERE id=$uid")->fetch();
    flash('success','Personal info updated.');
    redirect(site_url('employer/settings/personal.php'));
}

$page_title = 'Settings – Personal Info';
include __DIR__ . '/../../includes/header_dashboard.php';
include __DIR__ . '/../../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header"><h1 class="dash-page-title">Settings</h1></div>
    <?= render_flash() ?>

    <div class="settings-tabs">
        <a href="<?= site_url('employer/settings/personal.php') ?>"    class="settings-tab active">Personal Info</a>
        <a href="<?= site_url('employer/settings/profile.php') ?>"     class="settings-tab">Company Profile</a>
        <a href="<?= site_url('employer/settings/social-links.php') ?>" class="settings-tab">Social Links</a>
        <a href="<?= site_url('employer/settings/account.php') ?>"     class="settings-tab">Account</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="settings-form">
        <?= csrf_field() ?>
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
                    <p style="margin:0;font-size:13px;">Upload profile photo</p>
                </div>
                <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none;"
                    onchange="if(this.files[0]){var r=new FileReader();r.onload=function(e){var p=document.getElementById('avatarPreview');if(p.tagName==='IMG'){p.src=e.target.result;}else{var i=document.createElement('img');i.id='avatarPreview';i.src=e.target.result;i.style.cssText=p.style.cssText;p.replaceWith(i);}};r.readAsDataURL(this.files[0]);}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= clean($user['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?= clean($user['phone'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
