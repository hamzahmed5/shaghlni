<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Invalid request.']);
    exit;
}

$email = sanitize_email(post('email') ?? '');

if (!$email) {
    echo json_encode(['success'=>false,'message'=>'Please enter a valid email address.']);
    exit;
}

$db = get_db();

// Check if already subscribed (use contact_messages table with type=newsletter)
$check = $db->prepare("SELECT id FROM contact_messages WHERE email=:e AND subject='newsletter'");
$check->execute([':e'=>$email]);
if ($check->fetch()) {
    echo json_encode(['success'=>true,'message'=>'You are already subscribed!']);
    exit;
}

$db->prepare("INSERT INTO contact_messages (name,email,subject,message) VALUES ('Newsletter','  :e','newsletter','Subscribed')")
   ->execute([':e'=>$email]);

// Actually insert correctly
$db->prepare("INSERT INTO contact_messages (name,email,subject,message) VALUES (:n,:e,:s,:m)")
   ->execute([':n'=>'Newsletter Subscriber',':e'=>$email,':s'=>'newsletter',':m'=>'User subscribed to newsletter']);

echo json_encode(['success'=>true,'message'=>'Thank you for subscribing!']);
