<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db  = get_db();
$uid = current_user_id();
$ep  = get_employer_profile($uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));
    $company_name = clean(post('company_name'));
    $industry     = clean(post('industry'));
    $company_size = clean(post('company_size'));
    $founded_year = (int)post('founded_year');
    $description  = clean(post('description'));
    $city         = clean(post('city'));
    $address      = clean(post('address'));
    $website      = clean(post('website'));

    $logo = $ep['logo'] ?? null;
    if (!empty($_FILES['logo']['name'])) {
        $up = handle_upload('logo', UPLOAD_PATH . 'logos/', ['image/jpeg','image/png','image/webp','image/svg+xml']);
        if ($up) $logo = 'logos/' . basename($up);
    }
    $banner = $ep['banner'] ?? null;
    if (!empty($_FILES['banner']['name'])) {
        $up = handle_upload('banner', UPLOAD_PATH . 'banners/', ['image/jpeg','image/png','image/webp']);
        if ($up) $banner = 'banners/' . basename($up);
    }

    if ($ep) {
        $db->prepare("UPDATE employer_profiles SET company_name=:cn,industry=:ind,company_size=:cs,founded_year=:fy,description=:desc,city=:city,address=:addr,website=:web,logo=:logo,banner=:banner WHERE user_id=:uid")
           ->execute([':cn'=>$company_name,':ind'=>$industry,':cs'=>$company_size,':fy'=>$founded_year,':desc'=>$description,':city'=>$city,':addr'=>$address,':web'=>$website,':logo'=>$logo,':banner'=>$banner,':uid'=>$uid]);
    }

    flash('success','Company profile updated.');
    redirect(site_url('employer/settings/profile.php'));
}

$page_title = 'Settings – Company Profile';
include __DIR__ . '/../../includes/header_dashboard.php';
include __DIR__ . '/../../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header"><h1 class="dash-page-title">Settings</h1></div>
    <?= render_flash() ?>

    <div class="settings-tabs">
        <a href="<?= site_url('employer/settings/personal.php') ?>"    class="settings-tab">Personal Info</a>
        <a href="<?= site_url('employer/settings/profile.php') ?>"     class="settings-tab active">Company Profile</a>
        <a href="<?= site_url('employer/settings/social-links.php') ?>" class="settings-tab">Social Links</a>
        <a href="<?= site_url('employer/settings/account.php') ?>"     class="settings-tab">Account</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="settings-form">
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Company Logo</label>
                <div class="upload-area" style="padding:12px;" onclick="document.getElementById('logoInput').click()">
                    <?php if ($ep && $ep['logo']): ?><img src="<?= upload_url($ep['logo']) ?>" style="width:60px;height:60px;object-fit:contain;margin-bottom:8px;"><?php endif; ?>
                    <p style="font-size:13px;margin:0;">Upload Logo (PNG/JPG/SVG)</p>
                </div>
                <input type="file" id="logoInput" name="logo" accept="image/*" style="display:none;">
            </div>
            <div class="form-group">
                <label class="form-label">Company Banner</label>
                <div class="upload-area" style="padding:12px;" onclick="document.getElementById('bannerInput').click()">
                    <?php if ($ep && $ep['banner']): ?><img src="<?= upload_url($ep['banner']) ?>" style="width:100%;height:50px;object-fit:cover;margin-bottom:8px;"><?php endif; ?>
                    <p style="font-size:13px;margin:0;">Upload Banner (1200×300px)</p>
                </div>
                <input type="file" id="bannerInput" name="banner" accept="image/*" style="display:none;">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" class="form-control" value="<?= clean($ep['company_name'] ?? '') ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Industry</label>
                <input type="text" name="industry" class="form-control" value="<?= clean($ep['industry'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Company Size</label>
                <select name="company_size" class="form-select">
                    <option value="">Select</option>
                    <?php foreach (['1-10','11-50','51-200','201-500','501-1000','1000+'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($ep['company_size'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?> employees</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Founded Year</label>
                <input type="number" name="founded_year" class="form-control" value="<?= (int)($ep['founded_year'] ?? 0) ?: '' ?>" min="1900" max="<?= date('Y') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">City</label>
                <select name="city" class="form-select">
                    <option value="">Select City</option>
                    <?php foreach (JO_CITIES as $c): ?>
                        <option value="<?= $c ?>" <?= ($ep['city'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Street Address</label>
            <input type="text" name="address" class="form-control" value="<?= clean($ep['address'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control" value="<?= clean($ep['website'] ?? '') ?>" placeholder="https://...">
        </div>

        <div class="form-group">
            <label class="form-label">Company Description</label>
            <textarea name="description" class="form-control" rows="6"><?= clean($ep['description'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
