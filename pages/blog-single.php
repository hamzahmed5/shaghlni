<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
$db = get_db();
$id = (int)get_param('id');
$post = null;
if ($id) {
    $stmt = $db->prepare('SELECT bp.*, u.full_name AS author_name FROM blog_posts bp LEFT JOIN users u ON u.id = bp.author_id WHERE bp.id = ? AND bp.status = "published"');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
}
// Fallback sample content
if (!$post) {
    $post = [
        'id'=>$id??1,'title'=>'How to Write a Winning Resume in 2024','excerpt'=>'Your resume is your first impression. Learn the key elements that make recruiters stop and take notice.','category'=>'Career Tips',
        'content'=>'<p>Your resume is often the first thing a recruiter sees. In today\'s competitive job market, you have approximately 6 seconds to make an impression.</p><h3>1. Keep It Concise</h3><p>Limit your resume to one or two pages. Focus on your most relevant experience and achievements. Recruiters don\'t have time to read lengthy documents.</p><h3>2. Tailor for Each Application</h3><p>Customize your resume for each job you apply to. Mirror the language used in the job description and highlight the skills they\'re looking for.</p><h3>3. Use Action Verbs</h3><p>Start each bullet point with a strong action verb: "Led," "Developed," "Increased," "Managed." This creates a more dynamic and impressive read.</p><h3>4. Quantify Your Achievements</h3><p>Numbers stand out. Instead of "Improved sales performance," write "Increased sales by 35% in Q3 2023." Specific metrics make your accomplishments tangible.</p><h3>5. Include Keywords</h3><p>Many companies use Applicant Tracking Systems (ATS) to filter resumes. Include relevant keywords from the job posting to ensure your resume passes through automated screening.</p>',
        'published_at'=>date('Y-m-d H:i:s', strtotime('-5 days')),'author_name'=>'Editorial Team','slug'=>'how-to-write-winning-resume',
        'image'=>null,
    ];
}
$page_title = $post['title'];

// Related posts
$related = $db->prepare('SELECT id, title, category, published_at FROM blog_posts WHERE status = "published" AND id != ? ORDER BY RAND() LIMIT 3');
$related->execute([$post['id']]);
$related = $related->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-banner">
    <div class="container">
        <h1 style="font-size:22px;"><?= clean($post['title']) ?></h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Blog','url'=>site_url('pages/blog.php')],['label'=>$post['title'],'url'=>'']]) ?>
    </div>
</div>

<section style="padding:40px 0 64px;">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 300px;gap:40px;align-items:start;">
            <!-- Main content -->
            <article>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;font-size:13px;color:var(--text-muted);">
                    <?php if ($post['category'] ?? ''): ?>
                        <span style="padding:3px 10px;background:var(--primary-light);color:var(--primary);border-radius:4px;font-size:11px;font-weight:600;"><?= clean($post['category']) ?></span>
                    <?php endif; ?>
                    <span><?= date('M j, Y', strtotime($post['published_at'] ?? 'now')) ?></span>
                    <span>By <?= clean($post['author_name'] ?? 'Editorial Team') ?></span>
                </div>

                <!-- Featured image placeholder -->
                <div style="height:320px;background:var(--bg-light);border-radius:8px;margin-bottom:28px;display:flex;align-items:center;justify-content:center;">
                    <svg width="64" height="64" fill="none" stroke="var(--border)" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>

                <div class="job-content" style="max-width:none;">
                    <?= $post['content'] ?? '' ?>
                </div>

                <!-- Share -->
                <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);display:flex;align-items:center;gap:12px;">
                    <span style="font-size:13px;color:var(--text-muted);">Share:</span>
                    <div class="share-links">
                        <a href="#" title="Facebook"><svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a>
                        <a href="#" title="Twitter"><svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg></a>
                        <a href="#" title="LinkedIn"><svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg></a>
                    </div>
                </div>
            </article>

            <!-- Sidebar -->
            <aside style="position:sticky;top:88px;">
                <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:20px;margin-bottom:20px;">
                    <h4 style="font-size:15px;font-weight:700;color:var(--text-primary);margin-bottom:16px;">Recent Posts</h4>
                    <?php if (empty($related)): ?>
                        <p style="font-size:13px;color:var(--text-muted);">No other posts yet.</p>
                    <?php else: ?>
                        <?php foreach ($related as $r): ?>
                            <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border);">
                                <a href="<?= SITE_URL ?>/pages/blog-single.php?id=<?= (int)$r['id'] ?>" style="font-size:13px;font-weight:600;color:var(--text-primary);line-height:1.4;"><?= clean($r['title']) ?></a>
                                <div style="font-size:12px;color:var(--text-muted);margin-top:3px;"><?= date('M j, Y', strtotime($r['published_at'] ?? 'now')) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="background:var(--primary);border-radius:8px;padding:24px;color:#fff;text-align:center;">
                    <h4 style="font-size:16px;font-weight:700;margin-bottom:10px;">Get Job Alerts</h4>
                    <p style="font-size:13px;opacity:.85;margin-bottom:16px;">Never miss an opportunity. Set up alerts for your dream job.</p>
                    <a href="<?= SITE_URL ?>/candidate/job-alerts.php" class="btn btn-white btn-block">Create Alert</a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
