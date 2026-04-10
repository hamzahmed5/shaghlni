<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
start_session();
http_response_code(404);
$page_title = '404 – Page Not Found';
include __DIR__ . '/includes/header.php';
?>

<section style="padding:100px 0;text-align:center;">
    <div class="container">
        <div style="max-width:480px;margin:0 auto;">
            <div style="font-size:120px;font-weight:800;color:var(--primary);line-height:1;margin-bottom:16px;">404</div>
            <h2 style="font-size:28px;font-weight:700;color:var(--text-primary);margin-bottom:12px;">Page Not Found</h2>
            <p style="font-size:15px;color:var(--text-muted);margin-bottom:32px;line-height:1.7;">
                Sorry, the page you are looking for doesn't exist or has been moved.
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="<?= site_url() ?>" class="btn btn-primary">Go to Homepage</a>
                <a href="<?= site_url('jobs/find-job.php') ?>" class="btn btn-outline">Browse Jobs</a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
