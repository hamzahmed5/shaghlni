<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
$page_title = 'FAQs';
include __DIR__ . '/../includes/header.php';

$faqs = [
    ['q'=>'How do I create an account?','a'=>'Click "Create Account" in the top navigation. Choose your role (Candidate or Employer), fill in your details, and verify your email address.'],
    ['q'=>'Is it free to apply for jobs?','a'=>'Yes, creating a candidate account and applying for jobs is completely free. There is no charge for job seekers.'],
    ['q'=>'How does the job matching work?','a'=>'Our AI-powered system analyzes your skills, experience, location, and job preferences, then matches you with the most relevant open positions using machine learning algorithms.'],
    ['q'=>'How do I post a job as an employer?','a'=>'Sign up as an employer, complete your company profile setup, select a posting plan, and then use the "Post a Job" form to create your listing.'],
    ['q'=>'Can I save jobs and apply later?','a'=>'Yes. Logged-in candidates can bookmark any job using the save icon and find saved jobs in their Favorite Jobs dashboard section.'],
    ['q'=>'What file formats are accepted for CV uploads?','a'=>'We accept PDF files only for CV uploads. Maximum file size is 5 MB per file, and you can upload multiple CVs.'],
    ['q'=>'How do I set up job alerts?','a'=>'In your candidate dashboard, go to "Job Alert" and create an alert with your preferred keywords, location, category, and email frequency.'],
    ['q'=>'Can employers see my full profile?','a'=>'Employers can view your public profile. Sensitive personal details are only shared once you apply to a position.'],
    ['q'=>'How do I change my subscription plan?','a'=>'Go to your Employer Dashboard > Plans & Billing > Change Plans to upgrade or downgrade your subscription.'],
    ['q'=>'How do I delete my account?','a'=>'Navigate to Dashboard > Settings > Account Settings. Scroll to the Delete Account section and follow the confirmation steps.'],
];
?>
<div class="page-banner">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'FAQs','url'=>'']]) ?>
    </div>
</div>

<section style="padding:64px 0;">
    <div class="container" style="max-width:800px;">
        <div style="display:flex;flex-direction:column;gap:2px;">
            <?php foreach ($faqs as $i => $faq): ?>
            <div class="faq-item" style="background:#fff;border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:8px;">
                <button class="faq-question" onclick="toggleFaq(this)"
                        style="width:100%;text-align:left;padding:18px 20px;background:none;border:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-size:15px;font-weight:600;color:var(--text-primary);font-family:inherit;">
                    <?= clean($faq['q']) ?>
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="faq-icon" style="transition:transform .2s;flex-shrink:0;"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="faq-answer" style="display:none;padding:0 20px 18px;font-size:14px;color:var(--text-muted);line-height:1.8;">
                    <?= clean($faq['a']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:48px;text-align:center;padding:32px;background:var(--bg-light);border-radius:10px;">
            <h3 style="font-size:20px;font-weight:700;color:var(--text-primary);margin-bottom:10px;">Still have questions?</h3>
            <p style="font-size:14px;color:var(--text-muted);margin-bottom:20px;">Can't find the answer you're looking for? Please contact our support team.</p>
            <a href="<?= SITE_URL ?>/pages/contact.php" class="btn btn-primary">Contact Support</a>
        </div>
    </div>
</section>

<script>
function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const icon = btn.querySelector('.faq-icon');
    const open = answer.style.display !== 'none';
    answer.style.display = open ? 'none' : 'block';
    icon.style.transform = open ? '' : 'rotate(180deg)';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
