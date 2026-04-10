<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

header('Content-Type: application/json');

if (!is_logged_in() || current_role() !== 'employer') {
    echo json_encode(['success'=>false,'message'=>'Employer login required.']);
    exit;
}

$db    = get_db();
$uid   = current_user_id();
$cid   = (int)($_POST['candidate_id'] ?? 0);

if (!$cid) { echo json_encode(['success'=>false,'message'=>'Invalid candidate.']); exit; }

$ep = get_employer_profile($uid);
if (!$ep) { echo json_encode(['success'=>false,'message'=>'Employer profile not found.']); exit; }

$check = $db->prepare("SELECT id FROM saved_candidates WHERE employer_profile_id=:eid AND candidate_profile_id=:cid");
$check->execute([':eid'=>$ep['id'],':cid'=>$cid]);
$existing = $check->fetch();

if ($existing) {
    $db->prepare("DELETE FROM saved_candidates WHERE id=:id")->execute([':id'=>$existing['id']]);
    echo json_encode(['success'=>true,'saved'=>false,'message'=>'Candidate removed from saved list.']);
} else {
    $db->prepare("INSERT INTO saved_candidates (employer_profile_id,candidate_profile_id) VALUES (:eid,:cid)")->execute([':eid'=>$ep['id'],':cid'=>$cid]);
    echo json_encode(['success'=>true,'saved'=>true,'message'=>'Candidate saved.']);
}
