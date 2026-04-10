<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

header('Content-Type: application/json');

if (!is_logged_in() || current_role() !== 'candidate') {
    echo json_encode(['success'=>false,'message'=>'Login required.','redirect'=>site_url('auth/login.php')]);
    exit;
}

$db     = get_db();
$uid    = current_user_id();
$job_id = (int)($_POST['job_id'] ?? $_GET['job_id'] ?? 0);

if (!$job_id) { echo json_encode(['success'=>false,'message'=>'Invalid job.']); exit; }

$cp = get_candidate_profile($uid);
if (!$cp) { echo json_encode(['success'=>false,'message'=>'Profile not found.']); exit; }

// Check if already saved
$check = $db->prepare("SELECT id FROM saved_jobs WHERE candidate_profile_id=:cid AND job_id=:jid");
$check->execute([':cid'=>$cp['id'],':jid'=>$job_id]);
$existing = $check->fetch();

if ($existing) {
    $db->prepare("DELETE FROM saved_jobs WHERE id=:id")->execute([':id'=>$existing['id']]);
    echo json_encode(['success'=>true,'saved'=>false,'message'=>'Job removed from favorites.']);
} else {
    $db->prepare("INSERT INTO saved_jobs (candidate_profile_id,job_id) VALUES (:cid,:jid)")->execute([':cid'=>$cp['id'],':jid'=>$job_id]);
    echo json_encode(['success'=>true,'saved'=>true,'message'=>'Job saved to favorites.']);
}
