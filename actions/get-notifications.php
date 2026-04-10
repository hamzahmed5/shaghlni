<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success'=>false,'notifications'=>[],'unread'=>0]);
    exit;
}

$db  = get_db();
$uid = current_user_id();

$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id=:uid ORDER BY created_at DESC LIMIT 10");
$notifs->execute([':uid'=>$uid]);
$notifs = $notifs->fetchAll();

$unread = count_notifications($uid);

echo json_encode(['success'=>true,'notifications'=>$notifs,'unread'=>$unread]);
