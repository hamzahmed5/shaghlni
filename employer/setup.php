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

$step = max(1, min(4, (int)(get_param('step') ?: 1)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(post('csrf_token'));
    $s = (int)post('step');

    if ($s === 1) {
        // Company Info
        $company_name = clean(post('company_name'));
        $industry     = clean(post('industry'));
        $company_size = clean(post('company_size'));
        $description  = clean(post('description'));

        // Logo upload
        $logo = $ep['logo'] ?? null;
        if (!empty($_FILES['logo']['name'])) {
            $up = handle_upload('logo', UPLOAD_PATH . 'logos/', ['image/jpeg','image/png','image/webp','image/svg+xml']);
            if ($up) $logo = 'logos/' . basename($up);
        }
        // Banner upload
        $banner = $ep['banner'] ?? null;
        if (!empty($_FILES['banner']['name'])) {
            $up = handle_upload('banner', UPLOAD_PATH . 'banners/', ['image/jpeg','image/png','image/webp']);
            if ($up) $banner = 'banners/' . basename($up);
        }

        if ($ep) {
            $db->prepare("UPDATE employer_profiles SET company_name=:cn,industry=:ind,company_size=:cs,description=:desc,logo=:logo,banner=:banner WHERE user_id=:uid")
               ->execute([':cn'=>$company_name,':ind'=>$industry,':cs'=>$company_size,':desc'=>$description,':logo'=>$logo,':banner'=>$banner,':uid'=>$uid]);
        } else {
            $db->prepare("INSERT INTO employer_profiles (user_id,company_name,industry,company_size,description,logo,banner) VALUES (:uid,:cn,:ind,:cs,:desc,:logo,:banner)")
               ->execute([':uid'=>$uid,':cn'=>$company_name,':ind'=>$industry,':cs'=>$company_size,':desc'=>$description,':logo'=>$logo,':banner'=>$banner]);
        }
        redirect(site_url('employer/setup.php?step=2'));
    }

    if ($s === 2) {
        // Founding Info
        $founded_year = (int)post('founded_year');
        $city         = clean(post('city'));
        $address      = clean(post('address'));
        $website      = clean(post('website'));
        $phone        = clean(post('phone'));

        $db->prepare("UPDATE employer_profiles SET founded_year=:fy,city=:city,address=:addr,website=:web,phone=:ph WHERE user_id=:uid")
           ->execute([':fy'=>$founded_year,':city'=>$city,':addr'=>$address,':web'=>$website,':ph'=>$phone,':uid'=>$uid]);

        // Update user phone
        $db->prepare("UPDATE users SET phone=:ph WHERE id=:id")->execute([':ph'=>$phone,':id'=>$uid]);

        redirect(site_url('employer/setup.php?step=3'));
    }

    if ($s === 3) {
        // Social Links
        $platforms = ['LinkedIn','Twitter','Facebook','Instagram','YouTube'];
        $db->prepare("DELETE FROM employer_social_links WHERE user_id=:uid")->execute([':uid'=>$uid]);
        $ins = $db->prepare("INSERT INTO employer_social_links (user_id,platform,url) VALUES (:uid,:p,:url)");
        foreach ($platforms as $p) {
            $url = trim(post(strtolower($p)));
            if ($url) $ins->execute([':uid'=>$uid,':p'=>$p,':url'=>clean($url)]);
        }
        redirect(site_url('employer/setup.php?step=4'));
    }

    if ($s === 4) {
        // Contact Info – mark setup complete and go to dashboard
        $db->prepare("UPDATE employer_profiles SET setup_complete=1 WHERE user_id=:uid")->execute([':uid'=>$uid]);
        flash('success', 'Company profile setup complete. Welcome to Jobpilot!');
        redirect(site_url('employer/dashboard.php'));
    }
}

$ep = get_employer_profile($uid);

// Social links for step 3
$social_links = [];
$sl = $db->prepare("SELECT platform, url FROM employer_social_links WHERE user_id=:uid");
$sl->execute([':uid'=>$uid]);
foreach ($sl->fetchAll() as $l) $social_links[$l['platform']] = $l['url'];

$page_title = 'Company Setup';
$step_labels = ['Company Info','Founding Info','Social Links','Finish'];
include __DIR__ . '/../includes/header_dashboard.php';
?>
<!-- No sidebar for setup wizard -->
<div class="setup-wizard">
    <div class="setup-wizard-inner">

        <!-- Steps nav -->
        <div class="setup-steps">
            <?php foreach ($step_labels as $i => $label): $n=$i+1; ?>
            <div class="setup-step <?= $n < $step ? 'done' : ($n === $step ? 'active' : '') ?>">
                <div class="setup-step-dot"><?= $n < $step ? '✓' : $n ?></div>
                <div class="setup-step-label"><?= $label ?></div>
                <?php if ($n < 4): ?><div class="setup-step-line"></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="setup-progress-bar"><div style="width:<?= ($step-1)/3*100 ?>%"></div></div>

        <form method="POST" enctype="multipart/form-data" class="setup-form">
            <?= csrf_field() ?>
            <input type="hidden" name="step" value="<?= $step ?>">

            <?php if ($step === 1): ?>
            <h2 class="setup-form-title">Company Information</h2>
            <p style="font-size:14px;color:var(--text-muted);margin-bottom:24px;">Tell candidates about your company.</p>

            <div class="form-group">
                <label class="form-label">Company Name <span class="required">*</span></label>
                <input type="text" name="company_name" class="form-control" value="<?= clean($ep['company_name'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Industry</label>
                    <input type="text" name="industry" class="form-control" value="<?= clean($ep['industry'] ?? '') ?>" placeholder="e.g. Technology, Finance">
                </div>
                <div class="form-group">
                    <label class="form-label">Company Size</label>
                    <select name="company_size" class="form-select">
                        <option value="">Select Size</option>
                        <?php foreach (['1-10','11-50','51-200','201-500','501-1000','1000+'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($ep['company_size'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?> employees</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Company Description</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Describe your company, culture, and mission..."><?= clean($ep['description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Company Logo</label>
                    <div class="upload-area" onclick="document.getElementById('logoInput').click()">
                        <?php if ($ep && $ep['logo']): ?><img src="<?= upload_url($ep['logo']) ?>" style="width:60px;height:60px;object-fit:contain;margin-bottom:8px;"><?php endif; ?>
                        <p style="font-size:13px;">Upload Logo (PNG, JPG, SVG)</p>
                    </div>
                    <input type="file" id="logoInput" name="logo" accept="image/*" style="display:none;">
                </div>
                <div class="form-group">
                    <label class="form-label">Company Banner</label>
                    <div class="upload-area" onclick="document.getElementById('bannerInput').click()">
                        <?php if ($ep && $ep['banner']): ?><img src="<?= upload_url($ep['banner']) ?>" style="width:100%;height:60px;object-fit:cover;margin-bottom:8px;"><?php endif; ?>
                        <p style="font-size:13px;">Upload Banner (1200×300px recommended)</p>
                    </div>
                    <input type="file" id="bannerInput" name="banner" accept="image/*" style="display:none;">
                </div>
            </div>

            <?php elseif ($step === 2): ?>
            <h2 class="setup-form-title">Founding Information</h2>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Founded Year</label>
                    <input type="number" name="founded_year" class="form-control" value="<?= clean($ep['founded_year'] ?? '') ?>" min="1900" max="<?= date('Y') ?>" placeholder="e.g. 2010">
                </div>
                <div class="form-group">
                    <label class="form-label">City</label>
                    <select name="city" class="form-select">
                        <option value="">Select City</option>
                        <?php foreach (JO_CITIES as $c): ?>
                            <option value="<?= $c ?>" <?= ($ep['city'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= clean($ep['address'] ?? '') ?>" placeholder="Full street address">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" value="<?= clean($ep['website'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?= clean($ep['phone'] ?? '') ?>" placeholder="+962 6 XXX XXXX">
                </div>
            </div>

            <?php elseif ($step === 3): ?>
            <h2 class="setup-form-title">Social Media Links</h2>
            <p style="font-size:14px;color:var(--text-muted);margin-bottom:24px;">Optional – Add links to your company's social profiles.</p>
            <?php foreach (['LinkedIn','Twitter','Facebook','Instagram','YouTube'] as $p): ?>
            <div class="form-group">
                <label class="form-label"><?= $p ?></label>
                <input type="url" name="<?= strtolower($p) ?>" class="form-control" value="<?= clean($social_links[$p] ?? '') ?>" placeholder="https://<?= strtolower($p) ?>.com/...">
            </div>
            <?php endforeach; ?>

            <?php elseif ($step === 4): ?>
            <h2 class="setup-form-title">All Done!</h2>
            <div style="text-align:center;padding:32px 0;">
                <div style="width:80px;height:80px;background:#E6F7ED;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                    <svg width="36" height="36" fill="none" stroke="#0BA02C" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h3 style="font-size:20px;font-weight:700;margin-bottom:8px;">Your company profile is ready!</h3>
                <p style="font-size:14px;color:var(--text-muted);margin-bottom:24px;">You can now post jobs and start hiring.</p>
            </div>
            <?php endif; ?>

            <div style="display:flex;justify-content:space-between;margin-top:24px;">
                <?php if ($step > 1 && $step < 4): ?>
                    <a href="?step=<?= $step-1 ?>" class="btn btn-outline">Back</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    <?= $step < 4 ? 'Save & Continue' : 'Go to Dashboard' ?>
                </button>
            </div>
        </form>
    </div>
</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
