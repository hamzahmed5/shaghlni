<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
$page_title = 'Terms & Conditions';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1 class="page-banner-title">Terms &amp; Conditions</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Terms & Conditions','url'=>'']]) ?>
    </div>
</div>

<section style="padding:60px 0;">
    <div class="container">
        <div style="max-width:800px;margin:0 auto;">
            <div class="job-content">

                <p style="color:var(--text-muted);margin-bottom:32px;">Last updated: <?= date('F j, Y') ?></p>

                <h2>1. Acceptance of Terms</h2>
                <p>By accessing and using <?= SITE_NAME ?> ("the Platform"), you accept and agree to be bound by these Terms and Conditions. If you do not agree, please do not use our services.</p>

                <h2>2. Description of Service</h2>
                <p><?= SITE_NAME ?> is an AI-powered job matching platform that connects job seekers (Candidates) with employers across Jordan. We provide tools for posting jobs, searching for candidates, and managing applications.</p>

                <h2>3. User Accounts</h2>
                <p>To use certain features of the Platform, you must register for an account. You agree to:</p>
                <ul>
                    <li>Provide accurate, current, and complete information during registration.</li>
                    <li>Maintain and promptly update your account information.</li>
                    <li>Keep your password secure and confidential.</li>
                    <li>Notify us immediately of any unauthorized use of your account.</li>
                    <li>Be responsible for all activities under your account.</li>
                </ul>

                <h2>4. Candidate Responsibilities</h2>
                <p>As a Candidate, you agree to:</p>
                <ul>
                    <li>Provide truthful information in your profile, resume, and applications.</li>
                    <li>Only apply for positions you are genuinely interested in and qualified for.</li>
                    <li>Respect the communication preferences of employers.</li>
                    <li>Not misrepresent your qualifications or experience.</li>
                </ul>

                <h2>5. Employer Responsibilities</h2>
                <p>As an Employer, you agree to:</p>
                <ul>
                    <li>Post only genuine job opportunities that comply with applicable laws.</li>
                    <li>Not discriminate based on race, gender, religion, age, disability, or any protected characteristic.</li>
                    <li>Respond to candidates in a timely and professional manner.</li>
                    <li>Keep posted job information accurate and up to date.</li>
                    <li>Comply with all applicable labor laws and regulations in Jordan.</li>
                </ul>

                <h2>6. Prohibited Activities</h2>
                <p>You may not:</p>
                <ul>
                    <li>Post false, misleading, or fraudulent information.</li>
                    <li>Spam, harass, or abuse other users.</li>
                    <li>Scrape, crawl, or use automated tools to access the Platform without permission.</li>
                    <li>Use the Platform for any illegal purpose.</li>
                    <li>Post content that is offensive, defamatory, or violates third-party rights.</li>
                    <li>Attempt to gain unauthorized access to any part of the Platform.</li>
                </ul>

                <h2>7. Subscription Plans and Payments</h2>
                <p>Employer subscription plans are billed monthly or annually as selected. All fees are non-refundable except as required by law. We reserve the right to change pricing with 30 days' notice.</p>

                <h2>8. Intellectual Property</h2>
                <p>All content on the Platform, including text, graphics, logos, and software, is the property of <?= SITE_NAME ?> or its licensors. You may not reproduce, distribute, or create derivative works without our written permission.</p>

                <h2>9. Privacy</h2>
                <p>Your use of the Platform is also governed by our <a href="<?= site_url('pages/privacy.php') ?>">Privacy Policy</a>, which is incorporated into these Terms by reference.</p>

                <h2>10. Disclaimer of Warranties</h2>
                <p>The Platform is provided "as is" without warranties of any kind. We do not guarantee the accuracy, completeness, or usefulness of any information on the Platform, nor do we guarantee employment outcomes.</p>

                <h2>11. Limitation of Liability</h2>
                <p><?= SITE_NAME ?> shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the Platform, including but not limited to loss of data, profits, or business opportunities.</p>

                <h2>12. Changes to Terms</h2>
                <p>We reserve the right to modify these Terms at any time. We will notify registered users of significant changes via email. Your continued use of the Platform after changes constitutes acceptance of the new Terms.</p>

                <h2>13. Governing Law</h2>
                <p>These Terms shall be governed by and construed in accordance with the laws of the Hashemite Kingdom of Jordan. Any disputes shall be resolved in the courts of Amman, Jordan.</p>

                <h2>14. Contact Us</h2>
                <p>If you have questions about these Terms, please <a href="<?= site_url('pages/contact.php') ?>">contact us</a>.</p>

            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
