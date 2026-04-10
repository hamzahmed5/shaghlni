<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
$page_title = 'Pricing Plans';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1 class="page-banner-title">Pricing Plans</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Pricing','url'=>'']]) ?>
    </div>
</div>

<section style="padding:64px 0;">
    <div class="container">
        <div class="section-header" style="text-align:center;margin-bottom:48px;">
            <h2 class="section-title">Choose the Right Plan for Your Hiring Needs</h2>
            <p class="section-desc">Simple, transparent pricing. Upgrade or cancel anytime.</p>
        </div>

        <div class="pricing-grid" style="max-width:960px;margin:0 auto;">

            <?php
            $plans = [
                [
                    'name'      => 'Basic',
                    'price'     => 19,
                    'period'    => 'month',
                    'color'     => 'var(--text-primary)',
                    'popular'   => false,
                    'features'  => [
                        '5 active job posts',
                        'Standard job visibility',
                        'Application management',
                        'Email support',
                        'Basic analytics',
                        'Candidate search (limited)',
                    ],
                    'disabled'  => [],
                ],
                [
                    'name'      => 'Standard',
                    'price'     => 39,
                    'period'    => 'month',
                    'color'     => 'var(--primary)',
                    'popular'   => true,
                    'features'  => [
                        '15 active job posts',
                        'Featured job visibility',
                        'Application management',
                        'Priority email support',
                        'Advanced analytics',
                        'Unlimited candidate search',
                        'Highlighted company profile',
                        'Job alerts distribution',
                    ],
                    'disabled'  => [],
                ],
                [
                    'name'      => 'Premium',
                    'price'     => 59,
                    'period'    => 'month',
                    'color'     => '#7C5FE0',
                    'popular'   => false,
                    'features'  => [
                        'Unlimited active job posts',
                        'Featured + Urgent badges',
                        'Full application management',
                        'Dedicated account manager',
                        'Full analytics & reports',
                        'Unlimited candidate search',
                        'Premium company profile',
                        'Job alerts distribution',
                        'AI-powered matching priority',
                        'CV database access',
                    ],
                    'disabled'  => [],
                ],
            ];
            foreach ($plans as $plan):
            ?>
            <div class="pricing-card <?= $plan['popular'] ? 'pricing-card-popular' : '' ?>">
                <?php if ($plan['popular']): ?>
                    <div class="pricing-popular-badge">Most Popular</div>
                <?php endif; ?>
                <div class="pricing-plan-name"><?= $plan['name'] ?></div>
                <div class="pricing-price">
                    <span class="price-currency">$</span>
                    <span class="price-amount"><?= $plan['price'] ?></span>
                    <span class="price-period">/<?= $plan['period'] ?></span>
                </div>
                <div class="pricing-divider"></div>
                <ul class="pricing-features">
                    <?php foreach ($plan['features'] as $feat): ?>
                        <li>
                            <svg width="16" height="16" fill="none" stroke="<?= $plan['popular'] ? '#fff' : 'var(--primary)' ?>" stroke-width="2.5" viewBox="0 0 24 24">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <?= $feat ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (is_logged_in() && is_employer()): ?>
                    <a href="<?= site_url('employer/plans-billing.php') ?>" class="btn <?= $plan['popular'] ? 'btn-white' : 'btn-primary' ?> btn-block pricing-btn">
                        Get Started
                    </a>
                <?php else: ?>
                    <a href="<?= site_url('auth/register.php') ?>?role=employer" class="btn <?= $plan['popular'] ? 'btn-white' : 'btn-primary' ?> btn-block pricing-btn">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        </div>

        <!-- FAQ Section -->
        <div style="max-width:700px;margin:64px auto 0;">
            <h3 style="text-align:center;font-size:22px;font-weight:700;margin-bottom:32px;">Frequently Asked Questions</h3>

            <?php
            $faqs = [
                ['q'=>'Can I upgrade or downgrade my plan?',
                 'a'=>'Yes, you can change your plan at any time from your billing settings. Upgrades take effect immediately; downgrades take effect at the next billing cycle.'],
                ['q'=>'Is there a free trial?',
                 'a'=>'We offer a 7-day free trial for the Standard plan. No credit card required to start.'],
                ['q'=>'What payment methods do you accept?',
                 'a'=>'We accept major credit cards (Visa, Mastercard) and local payment methods available in Jordan.'],
                ['q'=>'What happens if I exceed my job post limit?',
                 'a'=>'You will be notified when you are near your limit. You can upgrade your plan or purchase additional post credits.'],
                ['q'=>'Can I cancel anytime?',
                 'a'=>'Yes. You can cancel your subscription at any time. Your access continues until the end of the paid period.'],
            ];
            foreach ($faqs as $i => $faq):
            ?>
            <div class="faq-item" style="border-bottom:1px solid var(--border);<?= $i === 0 ? 'border-top:1px solid var(--border);' : '' ?>">
                <button class="faq-question" onclick="toggleFaq(this)" style="width:100%;text-align:left;background:none;border:none;padding:18px 0;font-size:15px;font-weight:600;color:var(--text-primary);cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
                    <?= $faq['q'] ?>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="faq-icon" style="flex-shrink:0;transition:transform 0.2s;"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="faq-answer" style="display:none;padding-bottom:16px;font-size:14px;color:var(--text-secondary);line-height:1.7;"><?= $faq['a'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- CTA -->
<section style="background:var(--primary);padding:64px 0;text-align:center;">
    <div class="container">
        <h2 style="color:#fff;font-size:28px;font-weight:700;margin-bottom:12px;">Ready to Find Your Next Great Hire?</h2>
        <p style="color:rgba(255,255,255,0.85);margin-bottom:28px;font-size:16px;">Join <?= SITE_NAME ?> and access thousands of qualified candidates across Jordan.</p>
        <a href="<?= site_url('auth/register.php') ?>?role=employer" class="btn btn-white" style="padding:12px 32px;font-size:15px;">Get Started Free</a>
    </div>
</section>

<script>
function toggleFaq(btn) {
    var answer = btn.nextElementSibling;
    var icon   = btn.querySelector('.faq-icon');
    var isOpen = answer.style.display !== 'none';
    answer.style.display = isOpen ? 'none' : 'block';
    icon.style.transform = isOpen ? '' : 'rotate(180deg)';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
