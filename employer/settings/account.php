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
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));
    $action = post('action');

    if ($action === 'change_email') {
        $new_email = sanitize_email(post('new_email'));
        $password  = post('current_password');
        if (!$new_email) $errors[] = 'Valid email required.';
        elseif (!password_verify($password, $user['password'])) $errors[] = 'Current password incorrect.';
        else {
            $check = $db->prepare("SELECT id FROM users WHERE email=:e AND id!=:id");
            $check->execute([':e'=>$new_email,':id'=>$uid]);
            if ($check->fetch()) $errors[] = 'Email already in use.';
            else {
                $db->prepare("UPDATE users SET email=:e WHERE id=:id")->execute([':e'=>$new_email,':id'=>$uid]);
                $_SESSION['user']['email'] = $new_email;
                flash('success','Email updated.');
                redirect(site_url('employer/settings/account.php'));
            }
        }
    }

    if ($action === 'change_password') {
        $current = post('current_password');
        $new     = post('new_password');
        $confirm = post('confirm_password');
        if (!password_verify($current, $user['password'])) $errors[] = 'Current password incorrect.';
        elseif (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
        elseif ($new !== $confirm) $errors[] = 'Passwords do not match.';
        else {
            $db->prepare("UPDATE users SET password=:p WHERE id=:id")->execute([':p'=>password_hash($new, PASSWORD_DEFAULT),':id'=>$uid]);
            flash('success','Password changed.');
            redirect(site_url('employer/settings/account.php'));
        }
    }

    if ($action === 'delete_account') {
        $password = post('delete_password');
        if (!password_verify($password, $user['password'])) $errors[] = 'Password incorrect. Account not deleted.';
        else {
            $db->prepare("UPDATE users SET email=CONCAT('deleted_',id,'_',email),is_active=0 WHERE id=:id")->execute([':id'=>$uid]);
            session_destroy();
            header('Location: ' . site_url()); exit;
        }
    }
}

$page_title = 'Settings – Account';
include __DIR__ . '/../../includes/header_dashboard.php';
include __DIR__ . '/../../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header"><h1 class="dash-page-title">Settings</h1></div>
    <?= render_flash() ?>
    <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', $errors) ?></div><?php endif; ?>

    <div class="settings-tabs">
        <a href="<?= site_url('employer/settings/personal.php') ?>"    class="settings-tab">Personal Info</a>
        <a href="<?= site_url('employer/settings/profile.php') ?>"     class="settings-tab">Company Profile</a>
        <a href="<?= site_url('employer/settings/social-links.php') ?>" class="settings-tab">Social Links</a>
        <a href="<?= site_url('employer/settings/account.php') ?>"     class="settings-tab active">Account</a>
    </div>

    <div class="settings-card">
        <h3 class="settings-card-title">Change Email</h3>
        <form method="POST">
            <?= csrf_field() ?><input type="hidden" name="action" value="change_email">
            <div class="form-row">
                <div class="form-group"><label class="form-label">New Email</label><input type="email" name="new_email" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            </div>
            <button type="submit" class="btn btn-primary">Update Email</button>
        </form>
    </div>

    <div class="settings-card">
        <h3 class="settings-card-title">Change Password</h3>
        <form method="POST">
            <?= csrf_field() ?><input type="hidden" name="action" value="change_password">
            <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required minlength="8"></div>
                <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>

    <div class="settings-card" style="border-color:var(--danger);">
        <h3 class="settings-card-title" style="color:var(--danger);">Delete Account</h3>
        <p style="font-size:14px;color:var(--text-secondary);margin-bottom:16px;">This will permanently remove your company profile and all posted jobs.</p>
        <button class="btn btn-outline" style="color:var(--danger);border-color:var(--danger);" onclick="document.getElementById('deleteModal').style.display='flex'">Delete Account</button>
    </div>
</div>

<div id="deleteModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header"><h3 style="color:var(--danger);">Delete Account</h3><button class="modal-close" onclick="document.getElementById('deleteModal').style.display='none'">×</button></div>
        <form method="POST">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete_account">
            <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="delete_password" class="form-control" required></div>
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:var(--danger);border-color:var(--danger);">Delete</button>
            </div>
        </form>
    </div>
</div>

</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
