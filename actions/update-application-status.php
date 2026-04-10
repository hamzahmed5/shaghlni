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

$db     = get_db();
$uid    = current_user_id();
$app_id = (int)($_POST['app_id'] ?? 0);
$status = clean($_POST['status'] ?? '');
$csrf   = $_POST['csrf'] ?? '';

$valid_statuses = ['pending','reviewed','shortlisted','interviewed','hired','rejected'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success'=>false,'message'=>'Invalid status.']);
    exit;
}

// Verify this application belongs to one of the employer's jobs
$app = $db->prepare("SELECT a.id, a.candidate_profile_id, j.title AS job_title
                     FROM applications a
                     JOIN jobs j ON j.id=a.job_id
                     WHERE a.id=:id AND j.employer_id=:uid");
$app->execute([':id'=>$app_id,':uid'=>$uid]);
$app = $app->fetch();

if (!$app) {
    echo json_encode(['success'=>false,'message'=>'Application not found.']);
    exit;
}

$db->prepare("UPDATE applications SET status=:s WHERE id=:id")->execute([':s'=>$status,':id'=>$app_id]);

// Notify candidate
add_notification(
    $app['candidate_profile_id'],
    'application',
    "Your application for \"" . $app['job_title'] . "\" status changed to: " . $status,
    site_url('candidate/applied-jobs.php')
);

echo json_encode(['success'=>true,'message'=>'Status updated to ' . $status]);
