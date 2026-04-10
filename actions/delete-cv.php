<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('candidate');

verify_csrf(get_param('csrf'));

$db  = get_db();
$uid = current_user_id();
$cp  = get_candidate_profile($uid);
$cv_id = (int)get_param('id');

if ($cv_id && $cp) {
    $cv = $db->prepare("SELECT * FROM candidate_cvs WHERE id=:id AND candidate_profile_id=:cid");
    $cv->execute([':id'=>$cv_id,':cid'=>$cp['id']]);
    $cv = $cv->fetch();
    if ($cv) {
        // Delete file
        $file = UPLOAD_PATH . $cv['file_path'];
        if (file_exists($file)) @unlink($file);
        $db->prepare("DELETE FROM candidate_cvs WHERE id=:id")->execute([':id'=>$cv_id]);
        flash('success','CV deleted.');
    }
}

redirect(site_url('candidate/settings/personal.php'));
