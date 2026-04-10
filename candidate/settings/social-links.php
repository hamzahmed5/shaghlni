<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('candidate');

$db  = get_db();
$uid = current_user_id();

$platforms = ['LinkedIn','GitHub','Twitter','Portfolio','Facebook','Behance','Dribbble'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));
    $db->prepare("DELETE FROM candidate_social_links WHERE user_id = :uid")->execute([':uid'=>$uid]);
    $ins = $db->prepare("INSERT INTO candidate_social_links (user_id, platform, url) VALUES (:uid,:platform,:url)");
    foreach ($platforms as $p) {
        $url = trim(post(strtolower($p)));
        if ($url) {
            $ins->execute([':uid'=>$uid, ':platform'=>$p, ':url'=>clean($url)]);
        }
    }
    flash('success', 'Social links updated.');
    redirect(site_url('candidate/settings/social-links.php'));
}

$links = $db->prepare("SELECT platform, url FROM candidate_social_links WHERE user_id = :uid");
$links->execute([':uid' => $uid]);
$links_map = [];
foreach ($links->fetchAll() as $l) $links_map[$l['platform']] = $l['url'];

$page_title = 'Settings – Social Links';
include __DIR__ . '/../../includes/header_dashboard.php';
include __DIR__ . '/../../includes/sidebar_candidate.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Settings</h1>
    </div>
    <?= render_flash() ?>

    <div class="settings-tabs">
        <a href="<?= site_url('candidate/settings/personal.php') ?>"    class="settings-tab">Personal Info</a>
        <a href="<?= site_url('candidate/settings/profile.php') ?>"     class="settings-tab">Profile</a>
        <a href="<?= site_url('candidate/settings/social-links.php') ?>" class="settings-tab active">Social Links</a>
        <a href="<?= site_url('candidate/settings/account.php') ?>"     class="settings-tab">Account</a>
    </div>

    <form method="POST" class="settings-form">
        <?= csrf_field() ?>

        <?php foreach ($platforms as $p):
            $lower = strtolower($p);
            $icons = ['linkedin'=>'<path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
                      'github'=>'<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22"/>',
                      'twitter'=>'<path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>',
                      'portfolio'=>'<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
                      'facebook'=>'<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>',
                      'behance'=>'<path d="M1 6h14v2H1zM1 12h14v2H1zM8 19a7 7 0 100-14 7 7 0 000 14z"/>',
                      'dribbble'=>'<circle cx="12" cy="12" r="10"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"/>'];
        ?>
        <div class="form-group" style="display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:var(--bg-light);border-radius:8px;flex-shrink:0;">
                <svg width="18" height="18" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24">
                    <?= $icons[$lower] ?? '<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>' ?>
                </svg>
            </div>
            <div style="flex:1;">
                <label class="form-label" style="margin-bottom:4px;"><?= $p ?></label>
                <input type="url" name="<?= $lower ?>" class="form-control" value="<?= clean($links_map[$p] ?? '') ?>" placeholder="https://<?= $lower === 'portfolio' ? 'yoursite.com' : ($lower . '.com/username') ?>">
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
