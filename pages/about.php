<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
$page_title = 'About Us';
$db = get_db();
$total_jobs       = (int)$db->query('SELECT COUNT(*) FROM jobs WHERE status = "active"')->fetchColumn();
$total_companies  = (int)$db->query('SELECT COUNT(*) FROM employer_profiles')->fetchColumn();
$total_candidates = (int)$db->query('SELECT COUNT(*) FROM candidate_profiles')->fetchColumn();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>About</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'About','url'=>'']]) ?>
    </div>
</div>

<section style="padding:64px 0 48px;">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 320px;gap:64px;align-items:center;">
            <div>
                <p style="font-size:13px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Our Mission</p>
                <h2 style="font-size:36px;font-weight:700;color:var(--text-primary);line-height:1.2;margin-bottom:20px;">
                    Connecting Jordan's talent<br>with the right opportunities.
                </h2>
                <p style="font-size:15px;color:var(--text-muted);line-height:1.8;margin-bottom:16px;">
                    <?= SITE_NAME ?> was founded with a single purpose: to bridge the gap between talented candidates and the companies that need them. We believe every person deserves a job that matches their skills, passion, and life goals.
                </p>
                <p style="font-size:15px;color:var(--text-muted);line-height:1.8;">
                    Our platform uses advanced AI-powered matching to connect qualified candidates with the right opportunities — saving time for both job seekers and employers.
                </p>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div style="text-align:center;padding:20px;background:var(--bg-light);border-radius:8px;">
                    <div style="font-size:28px;font-weight:700;color:var(--primary);"><?= number_format($total_jobs) ?></div>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">Live Jobs</div>
                </div>
                <div style="text-align:center;padding:20px;background:var(--bg-light);border-radius:8px;">
                    <div style="font-size:28px;font-weight:700;color:var(--success);"><?= number_format($total_companies) ?></div>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">Companies</div>
                </div>
                <div style="text-align:center;padding:20px;background:var(--bg-light);border-radius:8px;">
                    <div style="font-size:28px;font-weight:700;color:var(--purple);"><?= number_format($total_candidates) ?></div>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">Candidates</div>
                </div>
                <div style="text-align:center;padding:20px;background:var(--bg-light);border-radius:8px;">
                    <div style="font-size:28px;font-weight:700;color:var(--warning);">98%</div>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">Match Rate</div>
                </div>
            </div>
        </div>

        <!-- Industries served -->
        <div style="display:flex;align-items:center;gap:40px;flex-wrap:wrap;margin-top:48px;padding-top:40px;border-top:1px solid var(--border);">
            <span style="font-size:13px;font-weight:600;color:var(--text-muted);">Industries we serve:</span>
            <?php foreach(['IT & Tech','Finance','Engineering','Health Care','Design','Education','Marketing','Legal'] as $industry): ?>
                <span style="font-size:14px;font-weight:600;color:var(--text-muted);"><?= $industry ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="bg-light" style="padding:64px 0;">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="background:var(--primary);border-radius:8px;height:180px;"></div>
                <div style="background:var(--bg-light);border-radius:8px;height:180px;margin-top:24px;border:1px solid var(--border);"></div>
                <div style="background:var(--bg-light);border-radius:8px;height:180px;border:1px solid var(--border);"></div>
                <div style="background:#18191C;border-radius:8px;height:180px;margin-top:-24px;"></div>
            </div>
            <div>
                <p style="font-size:13px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Our mission</p>
                <h2 style="font-size:32px;font-weight:700;color:var(--text-primary);line-height:1.25;margin-bottom:16px;">
                    Our mission is to help people<br>to find the perfect job.
                </h2>
                <p style="font-size:14px;color:var(--text-muted);line-height:1.8;margin-bottom:24px;">
                    We are committed to transforming the hiring experience by combining technology with human expertise. Our platform gives candidates the visibility they need and gives employers the tools to find their next great hire — quickly and efficiently.
                </p>
                <a href="<?= SITE_URL ?>/jobs/find-job.php" class="btn btn-primary">Browse Jobs &#8594;</a>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section style="padding:64px 0;">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">What our people says</h2>
        </div>
        <div class="testimonials-row">
            <?php
            $testimonials = [
                ['name'=>'Rami Al-Yousef','role'=>'Senior PHP Developer, Amman','text'=>'Jobpilot made my job search straightforward. Within two weeks I had interviews with three leading tech companies in Amman. The skill matching is accurate.','stars'=>5],
                ['name'=>'Lina Haddad','role'=>'HR Manager, HealthCare Jordan','text'=>'As an employer, the quality of applicants we receive through Jobpilot is noticeably higher. The platform saves our HR team significant time every hiring cycle.','stars'=>5],
                ['name'=>'Nour Al-Saleh','role'=>'UI/UX Designer, Amman','text'=>'The candidate dashboard is clean and the job alerts are relevant. I landed a position that matched my portfolio and salary expectations within a month.','stars'=>5],
            ];
            foreach ($testimonials as $t): ?>
                <div class="testimonial-card">
                    <div class="stars"><?= str_repeat('&#9733;', (int)$t['stars']) ?></div>
                    <p class="testimonial-text"><?= clean($t['text']) ?></p>
                    <div class="testimonial-author">
                        <div style="width:40px;height:40px;border-radius:50%;background:var(--bg-light);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);"><?= strtoupper(substr($t['name'],0,1)) ?></div>
                        <div>
                            <div class="author-name"><?= clean($t['name']) ?></div>
                            <div class="author-role"><?= clean($t['role']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section style="padding:0 0 64px;">
    <div class="container">
        <div class="cta-section">
            <div class="cta-candidate">
                <h2 class="cta-title" style="color:var(--text-primary);">Become a Candidate</h2>
                <p class="cta-desc" style="color:var(--text-muted);opacity:1;">Create your free profile and access thousands of job opportunities across all industries.</p>
                <a href="<?= SITE_URL ?>/auth/register.php?role=candidate" class="btn btn-primary">Register Now &#8594;</a>
            </div>
            <div class="cta-employer">
                <h2 class="cta-title">Become an Employer</h2>
                <p class="cta-desc">Post your open positions and find the right talent fast with our AI-powered matching.</p>
                <a href="<?= SITE_URL ?>/auth/register.php?role=employer" class="btn btn-white">Register Now &#8594;</a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
