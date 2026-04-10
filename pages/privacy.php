<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
$page_title = 'Privacy Policy';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1 class="page-banner-title">Privacy Policy</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Privacy Policy','url'=>'']]) ?>
    </div>
</div>

<section style="padding:60px 0;">
    <div class="container">
        <div style="max-width:800px;margin:0 auto;">
            <div class="job-content">

                <p style="color:var(--text-muted);margin-bottom:32px;">Last updated: <?= date('F j, Y') ?></p>

                <p>At <?= SITE_NAME ?>, we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and share information about you when you use our Platform.</p>

                <h2>1. Information We Collect</h2>
                <h3>Account Information</h3>
                <p>When you register, we collect your full name, email address, username, and password (stored securely hashed).</p>

                <h3>Profile Information</h3>
                <p>Candidates may provide: resume/CV, work experience, education, skills, location, profile photo, salary expectations, and social media links.</p>
                <p>Employers may provide: company name, industry, company size, location, website, logo, banner, and contact information.</p>

                <h3>Usage Data</h3>
                <p>We automatically collect information about how you use the Platform, including pages visited, search queries, jobs viewed, and applications submitted.</p>

                <h3>Communications</h3>
                <p>We store messages you send through the Platform, including job applications and contact form submissions.</p>

                <h2>2. How We Use Your Information</h2>
                <ul>
                    <li>To provide, maintain, and improve the Platform.</li>
                    <li>To match candidates with relevant job opportunities using AI/ML algorithms.</li>
                    <li>To process applications and facilitate communication between candidates and employers.</li>
                    <li>To send you notifications about jobs, applications, and platform updates.</li>
                    <li>To enforce our Terms and Conditions.</li>
                    <li>To analyze usage patterns and improve our services.</li>
                </ul>

                <h2>3. Information Sharing</h2>
                <p>We do not sell your personal information. We share information only in these circumstances:</p>
                <ul>
                    <li><strong>With employers:</strong> When you apply for a job, your application information is shared with that employer.</li>
                    <li><strong>With candidates:</strong> Your company profile is visible to candidates browsing the Platform.</li>
                    <li><strong>Service providers:</strong> We may share information with trusted third parties who help us operate the Platform.</li>
                    <li><strong>Legal requirements:</strong> We may disclose information if required by law or to protect our rights.</li>
                </ul>

                <h2>4. Data Security</h2>
                <p>We implement industry-standard security measures including HTTPS encryption, password hashing, and CSRF protection. However, no method of transmission over the internet is 100% secure.</p>

                <h2>5. Data Retention</h2>
                <p>We retain your information for as long as your account is active or as needed to provide services. You may request deletion of your account and associated data at any time.</p>

                <h2>6. Your Rights</h2>
                <p>You have the right to:</p>
                <ul>
                    <li>Access the personal information we hold about you.</li>
                    <li>Correct inaccurate information.</li>
                    <li>Request deletion of your account and data.</li>
                    <li>Opt out of marketing communications.</li>
                    <li>Export your data in a portable format.</li>
                </ul>

                <h2>7. Cookies</h2>
                <p>We use cookies to maintain your session and remember your preferences. You can disable cookies in your browser settings, but this may affect Platform functionality.</p>

                <h2>8. Children's Privacy</h2>
                <p>The Platform is not intended for users under 16 years of age. We do not knowingly collect information from children.</p>

                <h2>9. Changes to This Policy</h2>
                <p>We may update this Privacy Policy from time to time. We will notify you of significant changes via email or a prominent notice on the Platform.</p>

                <h2>10. Contact Us</h2>
                <p>For privacy concerns or to exercise your rights, please <a href="<?= site_url('pages/contact.php') ?>">contact us</a> or email us at privacy@<?= strtolower(str_replace(' ', '', SITE_NAME)) ?>.jo.</p>

            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
