<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success'=>false,'message'=>'Login required.']);
    exit;
}

$db  = get_db();
$uid = current_user_id();

$db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=:uid AND is_read=0")->execute([':uid'=>$uid]);

// Get recent notifications
$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id=:uid ORDER BY created_at DESC LIMIT 10");
$notifs->execute([':uid'=>$uid]);
$notifs = $notifs->fetchAll();

echo json_encode(['success'=>true,'notifications'=>$notifs,'unread'=>0]);
