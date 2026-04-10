<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();

$errors = []; $sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf(post('csrf_token'))) {
    $name    = trim(post('name'));
    $email   = sanitize_email(post('email'));
    $subject = trim(post('subject'));
    $message = trim(post('message'));
    if (!$name)    $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (!$subject) $errors[] = 'Subject is required.';
    if (!$message) $errors[] = 'Message is required.';
    if (empty($errors)) {
        $db = get_db();
        $db->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)')->execute([$name,$email,$subject,$message]);
        $sent = true;
    }
}
$page_title = 'Contact Us';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Contact</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Contact','url'=>'']]) ?>
    </div>
</div>

<section style="padding:64px 0;">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 420px;gap:64px;align-items:start;">
            <div>
                <p style="font-size:13px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Who we are</p>
                <h2 style="font-size:36px;font-weight:700;color:var(--text-primary);line-height:1.2;margin-bottom:16px;">
                    We care about<br>customer services
                </h2>
                <p style="font-size:14px;color:var(--text-muted);line-height:1.8;margin-bottom:24px;">
                    Want to chat? We'd love to hear from you! Get in touch with our Customer Success Team to inquire about speaking events, advertising rates, or just say hello.
                </p>
                <a href="mailto:<?= SITE_EMAIL ?>" class="btn btn-outline">Email Support</a>
            </div>

            <div style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:28px;">
                <h3 style="font-size:18px;font-weight:700;color:var(--text-primary);margin-bottom:20px;">Get in Touch</h3>
                <?php if ($sent): ?>
                    <div class="alert alert-success">Thank you! Your message has been sent. We'll get back to you shortly.</div>
                <?php else: ?>
                    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= clean($e) ?></div><?php endforeach; ?>
                    <form method="POST" novalidate>
                        <?= csrf_field() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" class="form-control" name="name" placeholder="Name" value="<?= clean(post('name')) ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control" name="email" placeholder="Email" value="<?= clean(post('email')) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="subject" placeholder="Subjects" value="<?= clean(post('subject')) ?>">
                        </div>
                        <div class="form-group">
                            <textarea class="form-control" name="message" rows="5" placeholder="Message" style="min-height:130px;"><?= clean(post('message')) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            Send Message
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Map placeholder -->
<div style="background:var(--bg-light);height:320px;width:100%;display:flex;align-items:center;justify-content:center;border-top:1px solid var(--border);">
    <p style="color:var(--text-muted);font-size:14px;">Map — <?= SITE_ADDRESS ?></p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
