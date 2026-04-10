<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db  = get_db();
$uid = current_user_id();
$platforms = ['LinkedIn','Twitter','Facebook','Instagram','YouTube'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));
    $db->prepare("DELETE FROM employer_social_links WHERE user_id=:uid")->execute([':uid'=>$uid]);
    $ins = $db->prepare("INSERT INTO employer_social_links (user_id,platform,url) VALUES (:uid,:p,:url)");
    foreach ($platforms as $p) {
        $url = trim(post(strtolower($p)));
        if ($url) $ins->execute([':uid'=>$uid,':p'=>$p,':url'=>clean($url)]);
    }
    flash('success','Social links updated.');
    redirect(site_url('employer/settings/social-links.php'));
}

$links_raw = $db->prepare("SELECT platform, url FROM employer_social_links WHERE user_id=:uid");
$links_raw->execute([':uid'=>$uid]);
$links = [];
foreach ($links_raw->fetchAll() as $l) $links[$l['platform']] = $l['url'];

$page_title = 'Settings – Social Links';
include __DIR__ . '/../../includes/header_dashboard.php';
include __DIR__ . '/../../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header"><h1 class="dash-page-title">Settings</h1></div>
    <?= render_flash() ?>

    <div class="settings-tabs">
        <a href="<?= site_url('employer/settings/personal.php') ?>"    class="settings-tab">Personal Info</a>
        <a href="<?= site_url('employer/settings/profile.php') ?>"     class="settings-tab">Company Profile</a>
        <a href="<?= site_url('employer/settings/social-links.php') ?>" class="settings-tab active">Social Links</a>
        <a href="<?= site_url('employer/settings/account.php') ?>"     class="settings-tab">Account</a>
    </div>

    <form method="POST" class="settings-form">
        <?= csrf_field() ?>
        <?php foreach ($platforms as $p): ?>
        <div class="form-group">
            <label class="form-label"><?= $p ?></label>
            <input type="url" name="<?= strtolower($p) ?>" class="form-control" value="<?= clean($links[$p] ?? '') ?>" placeholder="https://<?= strtolower($p) ?>.com/...">
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
