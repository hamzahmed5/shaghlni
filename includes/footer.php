
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?= SITE_URL ?>/index.php" class="logo footer-logo">
                    <svg width="30" height="30" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="6" fill="#0A65CC"/>
                        <path d="M9 13h14v9a2 2 0 01-2 2H11a2 2 0 01-2-2v-9z" fill="white"/>
                        <path d="M6 13h20v2H6v-2z" fill="white" opacity="0.6"/>
                        <path d="M13 10a1 1 0 011-1h4a1 1 0 011 1v3h-6v-3z" fill="white"/>
                    </svg>
                    <?= SITE_NAME ?>
                </a>
                <address>
                    <?= t('footer.call') ?>: <?= SITE_PHONE ?><br>
                    <?= SITE_ADDRESS ?>
                </address>
            </div>

            <div class="footer-col">
                <h4><?= t('footer.quick_link') ?></h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/pages/about.php"><?= t('footer.about') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/pages/contact.php"><?= t('footer.contact') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/pages/pricing.php"><?= t('footer.pricing') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/pages/blog.php"><?= t('footer.blog') ?></a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4><?= t('footer.candidate') ?></h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/jobs/find-job.php"><?= t('footer.browse_jobs') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/employers/browse.php"><?= t('footer.browse_emp') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/candidate/dashboard.php"><?= t('footer.cand_dash') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/candidate/favorite-jobs.php"><?= t('footer.saved_jobs') ?></a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4><?= t('footer.employers') ?></h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/employer/post-job.php"><?= t('footer.post_job') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/candidates/browse.php"><?= t('footer.browse_cand') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/employer/dashboard.php"><?= t('footer.emp_dash') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/employer/applications.php"><?= t('footer.applications') ?></a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4><?= t('footer.support') ?></h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/pages/faq.php"><?= t('footer.faqs') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/pages/privacy.php"><?= t('footer.privacy') ?></a></li>
                    <li><a href="<?= SITE_URL ?>/pages/terms.php"><?= t('footer.terms') ?></a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&#169; <?= date('Y') ?> <?= SITE_NAME ?>. <?= t('footer.rights') ?></p>
            <div class="footer-bottom-links">
                <a href="<?= SITE_URL ?>/pages/privacy.php"><?= t('footer.privacy') ?></a>
                <a href="<?= SITE_URL ?>/pages/terms.php"><?= t('footer.terms') ?></a>
                <a href="<?= SITE_URL ?>/pages/contact.php"><?= t('footer.contact') ?></a>
            </div>
        </div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extra_js)): foreach ($extra_js as $js): ?>
<script src="<?= SITE_URL ?>/assets/js/<?= $js ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
