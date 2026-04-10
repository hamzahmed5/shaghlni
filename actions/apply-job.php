<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

if (!is_logged_in() || current_role() !== 'candidate') {
    redirect(site_url('auth/login.php'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(site_url('jobs/find-job.php'));
}

verify_csrf(post('csrf_token'));

$db     = get_db();
$uid    = current_user_id();
$job_id = (int)post('job_id');
$cover  = clean(post('cover_letter'));

if (!$job_id) {
    flash('error', 'Invalid job.');
    redirect(site_url('jobs/find-job.php'));
}

// Verify job exists and is active
$job = $db->prepare("SELECT id, employer_id, title FROM jobs WHERE id=:id AND status='active' AND expires_at>=CURDATE()");
$job->execute([':id'=>$job_id]);
$job = $job->fetch();

if (!$job) {
    flash('error', 'This job is no longer available.');
    redirect(site_url('jobs/find-job.php'));
}

$cp = get_candidate_profile($uid);
if (!$cp) {
    flash('error', 'Please complete your profile before applying.');
    redirect(site_url('candidate/settings/personal.php'));
}

// Check duplicate
if (has_applied($cp['id'], $job_id)) {
    flash('error', 'You have already applied for this job.');
    redirect(site_url('jobs/job-detail.php?id=' . $job_id));
}

// Resume upload
$resume_path = null;
if (!empty($_FILES['resume']['name'])) {
    $up = handle_upload('resume', UPLOAD_PATH . 'resumes/', ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    if ($up) $resume_path = 'resumes/' . basename($up);
}

$db->prepare("INSERT INTO applications (candidate_profile_id, job_id, cover_letter, resume_path, status, applied_at) VALUES (:cid,:jid,:cl,:rp,'pending',NOW())")
   ->execute([':cid'=>$cp['id'],':jid'=>$job_id,':cl'=>$cover,':rp'=>$resume_path]);

// Notify employer
add_notification(
    $job['employer_id'],
    'application',
    "New application received for: " . $job['title'],
    site_url('employer/applications.php?job_id=' . $job_id)
);

flash('success', 'Application submitted successfully! Good luck!');
redirect(site_url('jobs/job-detail.php?id=' . $job_id));
