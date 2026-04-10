<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
$page_title = 'Blog';
$db = get_db();
$page = max(1, (int)(get_param('page') ?: 1));
$total = (int)$db->query('SELECT COUNT(*) FROM blog_posts WHERE status = "published"')->fetchColumn();
$p     = paginate($total, BLOG_PER_PAGE, $page);
$posts = $db->prepare('SELECT bp.*, u.full_name AS author_name FROM blog_posts bp LEFT JOIN users u ON u.id = bp.author_id WHERE bp.status = "published" ORDER BY bp.created_at DESC LIMIT ? OFFSET ?');
$posts->execute([BLOG_PER_PAGE, $p['offset']]);
$posts = $posts->fetchAll();

// Fallback sample posts if empty
if (empty($posts)) {
    $posts = [
        ['id'=>1,'title'=>'How to Write a Winning Resume in 2024','excerpt'=>'Your resume is your first impression. Learn the key elements that make recruiters stop and take notice.','category'=>'Career Tips','published_at'=>date('Y-m-d H:i:s', strtotime('-5 days')),'author_name'=>'Editorial Team','slug'=>'how-to-write-winning-resume'],
        ['id'=>2,'title'=>'Top 10 In-Demand Skills for Tech Jobs','excerpt'=>'The tech industry is evolving fast. These are the skills employers are looking for most this year.','category'=>'Industry Insights','published_at'=>date('Y-m-d H:i:s', strtotime('-10 days')),'author_name'=>'Editorial Team','slug'=>'top-10-in-demand-skills'],
        ['id'=>3,'title'=>'How to Ace Your Next Job Interview','excerpt'=>'Preparation is the key to confidence. Here are proven strategies to help you perform your best.','category'=>'Interview Tips','published_at'=>date('Y-m-d H:i:s', strtotime('-15 days')),'author_name'=>'Editorial Team','slug'=>'how-to-ace-job-interview'],
        ['id'=>4,'title'=>'Remote Work: Pros, Cons, and How to Thrive','excerpt'=>'Remote work is here to stay. Discover how to maximize productivity and maintain work-life balance.','category'=>'Work Life','published_at'=>date('Y-m-d H:i:s', strtotime('-20 days')),'author_name'=>'Editorial Team','slug'=>'remote-work-pros-cons'],
        ['id'=>5,'title'=>'Employer Branding: Why It Matters in Hiring','excerpt'=>'Your company culture and brand reputation directly impact the quality of candidates you attract.','category'=>'For Employers','published_at'=>date('Y-m-d H:i:s', strtotime('-25 days')),'author_name'=>'Editorial Team','slug'=>'employer-branding-hiring'],
        ['id'=>6,'title'=>'Salary Negotiation Tips That Actually Work','excerpt'=>'Negotiating your salary doesn\'t have to be scary. Use these data-backed tactics to get what you\'re worth.','category'=>'Career Tips','published_at'=>date('Y-m-d H:i:s', strtotime('-30 days')),'author_name'=>'Editorial Team','slug'=>'salary-negotiation-tips'],
    ];
    $p['total'] = count($posts);
}
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Blog &amp; Articles</h1>
        <?= breadcrumb([['label'=>'Home','url'=>site_url()],['label'=>'Blog','url'=>'']]) ?>
    </div>
</div>

<section style="padding:64px 0;">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;">
            <?php foreach ($posts as $post):
                $cat_colors = ['Career Tips'=>'#E7F0FA', 'Interview Tips'=>'#E7F4EA', 'Industry Insights'=>'#F0EBFF', 'Work Life'=>'#FFF0EB', 'For Employers'=>'#FFF8E5'];
                $cat_text   = ['Career Tips'=>'#0A65CC', 'Interview Tips'=>'#0BA02C', 'Industry Insights'=>'#7C5FE0', 'Work Life'=>'#FF6636', 'For Employers'=>'#F5A623'];
                $bg  = $cat_colors[$post['category'] ?? ''] ?? '#F1F2F4';
                $tc  = $cat_text[$post['category'] ?? '']  ?? '#767F8C';
            ?>
            <article style="background:#fff;border:1px solid var(--border);border-radius:8px;overflow:hidden;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
                <div style="height:180px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;">
                    <svg width="48" height="48" fill="none" stroke="<?= $tc ?>" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div style="padding:20px;">
                    <?php if ($post['category'] ?? ''): ?>
                        <span style="display:inline-block;padding:3px 10px;background:<?= $bg ?>;color:<?= $tc ?>;border-radius:4px;font-size:11px;font-weight:600;margin-bottom:10px;"><?= clean($post['category']) ?></span>
                    <?php endif; ?>
                    <h3 style="font-size:15px;font-weight:700;color:var(--text-primary);line-height:1.4;margin-bottom:8px;">
                        <a href="<?= SITE_URL ?>/pages/blog-single.php?id=<?= (int)$post['id'] ?>" style="color:inherit;"><?= clean($post['title']) ?></a>
                    </h3>
                    <p style="font-size:13px;color:var(--text-muted);line-height:1.7;margin-bottom:14px;"><?= clean(substr($post['excerpt'] ?? '', 0, 120)) ?>...</p>
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--text-light);">
                        <span><?= clean($post['author_name'] ?? 'Editorial Team') ?></span>
                        <span><?= date('M j, Y', strtotime($post['published_at'] ?? 'now')) ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?= render_pagination($p, SITE_URL . '/pages/blog.php') ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
