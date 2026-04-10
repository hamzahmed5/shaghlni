<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db  = get_db();
$uid = current_user_id();
$ep  = get_employer_profile($uid);

if (!$ep) redirect(site_url('employer/setup.php'));

// Edit mode
$edit_id = (int)get_param('edit');
$job     = null;
$benefits = [];
if ($edit_id) {
    $jst = $db->prepare("SELECT * FROM jobs WHERE id=:id AND employer_id=:uid");
    $jst->execute([':id'=>$edit_id,':uid'=>$uid]);
    $job = $jst->fetch();
    if ($job) {
        $bst = $db->prepare("SELECT benefit FROM job_benefits WHERE job_id=:jid");
        $bst->execute([':jid'=>$edit_id]);
        $benefits = $bst->fetchAll(PDO::FETCH_COLUMN);
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));

    $title          = clean(post('title'));
    $category       = clean(post('category'));
    $job_type       = clean(post('job_type'));
    $location       = clean(post('location'));
    $exp_level      = clean(post('exp_level'));
    $edu_level      = clean(post('edu_level'));
    $description    = clean(post('description'));
    $requirements   = clean(post('requirements'));
    $responsibilities = clean(post('responsibilities'));
    $salary_min     = (int)post('salary_min');
    $salary_max     = (int)post('salary_max');
    $salary_type    = clean(post('salary_type'));
    $vacancy_count  = max(1,(int)post('vacancy_count'));
    $expires_at     = clean(post('expires_at'));
    $benefits_raw   = array_filter(array_map('trim', explode("\n", post('benefits'))));
    $status         = clean(post('status')) ?: 'active';

    if (!$title)       $errors[] = 'Job title is required.';
    if (!$description) $errors[] = 'Job description is required.';
    if (!$location)    $errors[] = 'Location is required.';
    if (!$expires_at)  $errors[] = 'Expiry date is required.';

    if (empty($errors)) {
        $data = [
            ':uid'=>$uid, ':title'=>$title, ':cat'=>$category, ':jtype'=>$job_type,
            ':loc'=>$location, ':exp'=>$exp_level, ':edu'=>$edu_level,
            ':desc'=>$description, ':req'=>$requirements, ':resp'=>$responsibilities,
            ':smin'=>$salary_min, ':smax'=>$salary_max, ':stype'=>$salary_type,
            ':vac'=>$vacancy_count, ':exp_at'=>$expires_at, ':status'=>$status,
        ];

        if ($edit_id && $job) {
            $db->prepare("UPDATE jobs SET title=:title,category=:cat,job_type=:jtype,location=:loc,experience_level=:exp,education_level=:edu,description=:desc,requirements=:req,responsibilities=:resp,salary_min=:smin,salary_max=:smax,salary_type=:stype,vacancy_count=:vac,expires_at=:exp_at,status=:status WHERE id=:jid AND employer_id=:uid")
               ->execute(array_merge($data, [':jid'=>$edit_id]));
            $job_id = $edit_id;
            $db->prepare("DELETE FROM job_benefits WHERE job_id=:jid")->execute([':jid'=>$job_id]);
        } else {
            $db->prepare("INSERT INTO jobs (employer_id,title,category,job_type,location,experience_level,education_level,description,requirements,responsibilities,salary_min,salary_max,salary_type,vacancy_count,expires_at,status) VALUES (:uid,:title,:cat,:jtype,:loc,:exp,:edu,:desc,:req,:resp,:smin,:smax,:stype,:vac,:exp_at,:status)")
               ->execute($data);
            $job_id = (int)$db->lastInsertId();
        }

        // Benefits
        $ins_b = $db->prepare("INSERT INTO job_benefits (job_id, benefit) VALUES (:jid, :b)");
        foreach (array_slice($benefits_raw, 0, 10) as $b) {
            $ins_b->execute([':jid'=>$job_id, ':b'=>clean($b)]);
        }

        flash('success', $edit_id ? 'Job updated successfully.' : 'Job posted successfully.');
        redirect(site_url('employer/my-jobs.php'));
    }
}

$page_title = $edit_id ? 'Edit Job' : 'Post a Job';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title"><?= $edit_id ? 'Edit Job' : 'Post a New Job' ?></h1>
    </div>

    <?= render_flash() ?>
    <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', $errors) ?></div><?php endif; ?>

    <form method="POST" class="settings-form">
        <?= csrf_field() ?>

        <div class="settings-card">
            <h3 class="settings-card-title">Basic Information</h3>
            <div class="form-group">
                <label class="form-label">Job Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= clean($job['title'] ?? '') ?>" required placeholder="e.g. Senior PHP Developer">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">Select Category</option>
                        <?php foreach (JOB_CATEGORIES as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($job['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Job Type</label>
                    <select name="job_type" class="form-select">
                        <?php foreach (JOB_TYPES as $t): ?>
                            <option value="<?= $t ?>" <?= ($job['job_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Location <span class="required">*</span></label>
                    <select name="location" class="form-select">
                        <option value="">Select City</option>
                        <?php foreach (JO_CITIES as $c): ?>
                            <option value="<?= $c ?>" <?= ($job['location'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vacancies</label>
                    <input type="number" name="vacancy_count" class="form-control" value="<?= (int)($job['vacancy_count'] ?? 1) ?>" min="1">
                </div>
            </div>
        </div>

        <div class="settings-card">
            <h3 class="settings-card-title">Requirements</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Experience Level</label>
                    <select name="exp_level" class="form-select">
                        <option value="">Any</option>
                        <?php foreach (EXP_LEVELS as $l): ?>
                            <option value="<?= $l ?>" <?= ($job['experience_level'] ?? '') === $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Education Level</label>
                    <select name="edu_level" class="form-select">
                        <option value="">Any</option>
                        <?php foreach (EDU_LEVELS as $l): ?>
                            <option value="<?= $l ?>" <?= ($job['education_level'] ?? '') === $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <h3 class="settings-card-title">Salary</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Salary Min (JOD)</label>
                    <input type="number" name="salary_min" class="form-control" value="<?= (int)($job['salary_min'] ?? 0) ?>" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Salary Max (JOD)</label>
                    <input type="number" name="salary_max" class="form-control" value="<?= (int)($job['salary_max'] ?? 0) ?>" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Salary Type</label>
                    <select name="salary_type" class="form-select">
                        <?php foreach (['Monthly','Yearly','Hourly','Project-based'] as $st): ?>
                            <option value="<?= $st ?>" <?= ($job['salary_type'] ?? 'Monthly') === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <h3 class="settings-card-title">Job Details</h3>
            <div class="form-group">
                <label class="form-label">Job Description <span class="required">*</span></label>
                <textarea name="description" class="form-control" rows="7" required placeholder="Full job description..."><?= clean($job['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Requirements</label>
                <textarea name="requirements" class="form-control" rows="5" placeholder="List the requirements..."><?= clean($job['requirements'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Responsibilities</label>
                <textarea name="responsibilities" class="form-control" rows="5" placeholder="List the responsibilities..."><?= clean($job['responsibilities'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Benefits <span style="font-size:12px;color:var(--text-muted);">(one per line)</span></label>
                <textarea name="benefits" class="form-control" rows="5" placeholder="Health Insurance&#10;Annual Bonus&#10;Remote Work Option"><?= clean(implode("\n", $benefits)) ?></textarea>
            </div>
        </div>

        <div class="settings-card">
            <h3 class="settings-card-title">Publishing</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Expiry Date <span class="required">*</span></label>
                    <input type="date" name="expires_at" class="form-control"
                        value="<?= clean($job['expires_at'] ?? date('Y-m-d', strtotime('+30 days'))) ?>"
                        min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"  <?= ($job['status'] ?? '') === 'active'  ? 'selected' : '' ?>>Active</option>
                        <option value="draft"   <?= ($job['status'] ?? '') === 'draft'   ? 'selected' : '' ?>>Save as Draft</option>
                        <option value="expired" <?= ($job['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary"><?= $edit_id ? 'Update Job' : 'Post Job' ?></button>
            <a href="<?= site_url('employer/my-jobs.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
