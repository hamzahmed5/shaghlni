<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
require_login(site_url('auth/login.php'));
require_role('employer');

$db  = get_db();
$uid = current_user_id();
$ep  = get_employer_profile($uid);

// Current subscription
$sub = $db->prepare("SELECT es.*, p.name AS plan_name, p.price, p.max_jobs, p.features FROM employer_subscriptions es JOIN plans p ON p.id=es.plan_id WHERE es.employer_id=:uid AND es.status='active' ORDER BY es.starts_at DESC LIMIT 1");
$sub->execute([':uid'=>$uid]);
$sub = $sub->fetch();

// Plans
$plans = $db->query("SELECT * FROM plans WHERE is_active=1 ORDER BY price ASC")->fetchAll();

// Invoices
$invoices = $db->prepare("SELECT i.*, p.name AS plan_name FROM invoices i JOIN plans p ON p.id=i.plan_id WHERE i.employer_id=:uid ORDER BY i.created_at DESC LIMIT 10");
$invoices->execute([':uid'=>$uid]);
$invoices = $invoices->fetchAll();

// Handle plan selection
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(post('csrf_token'));
    $plan_id = (int)post('plan_id');
    $plan_data = null;
    foreach ($plans as $pl) { if ((int)$pl['id']===$plan_id) { $plan_data=$pl; break; } }
    if ($plan_data) {
        // Deactivate old subscription
        $db->prepare("UPDATE employer_subscriptions SET status='cancelled' WHERE employer_id=:uid AND status='active'")->execute([':uid'=>$uid]);
        // Create new subscription
        $db->prepare("INSERT INTO employer_subscriptions (employer_id,plan_id,starts_at,ends_at,status) VALUES (:uid,:pid,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),'active')")
           ->execute([':uid'=>$uid,':pid'=>$plan_id]);
        $sub_id = $db->lastInsertId();
        // Create invoice
        $inv_num = 'INV-'.date('Y').'-'.str_pad($uid,4,'0',STR_PAD_LEFT).'-'.rand(100,999);
        $db->prepare("INSERT INTO invoices (employer_id,plan_id,amount,invoice_number,status) VALUES (:uid,:pid,:amt,:inv,'paid')")
           ->execute([':uid'=>$uid,':pid'=>$plan_id,':amt'=>$plan_data['price'],':inv'=>$inv_num]);
        flash('success','Plan activated! Welcome to the ' . $plan_data['name'] . ' plan.');
        redirect(site_url('employer/plans-billing.php'));
    }
}

$page_title = 'Plans & Billing';
include __DIR__ . '/../includes/header_dashboard.php';
include __DIR__ . '/../includes/sidebar_employer.php';
?>

<div class="dashboard-main">
    <div class="dash-page-header">
        <h1 class="dash-page-title">Plans & Billing</h1>
    </div>

    <?= render_flash() ?>

    <!-- Current Plan -->
    <?php if ($sub): ?>
    <div class="settings-card" style="margin-bottom:24px;">
        <h3 class="settings-card-title">Current Plan</h3>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <div style="font-size:20px;font-weight:700;color:var(--primary);"><?= clean($sub['plan_name']) ?></div>
                <div style="font-size:14px;color:var(--text-muted);margin-top:4px;">
                    Active until <?= date('M j, Y', strtotime($sub['ends_at'])) ?>
                </div>
            </div>
            <div>
                <span class="status-badge status-active">Active</span>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="profile-alert" style="margin-bottom:24px;">
        You are currently on the <strong>Free</strong> plan. Upgrade to post more jobs and unlock features.
    </div>
    <?php endif; ?>

    <!-- Plans Grid -->
    <h2 class="dash-section-title" style="margin-bottom:20px;">Available Plans</h2>
    <div class="pricing-grid">
        <?php foreach ($plans as $plan):
            $is_current = $sub && (int)$sub['plan_id'] === (int)$plan['id'];
            $popular = strtolower($plan['name']) === 'standard';
            $features = json_decode($plan['features'] ?? '[]', true) ?: [];
        ?>
        <div class="pricing-card <?= $popular ? 'pricing-card-popular' : '' ?>">
            <?php if ($popular): ?><div class="pricing-popular-badge">Most Popular</div><?php endif; ?>
            <div class="pricing-plan-name"><?= clean($plan['name']) ?></div>
            <div class="pricing-price">
                <span class="price-currency">$</span>
                <span class="price-amount"><?= (int)$plan['price'] ?></span>
                <span class="price-period">/month</span>
            </div>
            <div class="pricing-divider"></div>
            <ul class="pricing-features">
                <li><svg width="14" height="14" fill="none" stroke="<?= $popular ? '#fff' : 'var(--primary)' ?>" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> <?= (int)$plan['max_jobs'] ?> job posts</li>
                <?php foreach (array_slice($features, 0, 4) as $f): ?>
                <li><svg width="14" height="14" fill="none" stroke="<?= $popular ? '#fff' : 'var(--primary)' ?>" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> <?= clean($f) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($is_current): ?>
                <button class="btn <?= $popular ? 'btn-white' : 'btn-outline' ?> btn-block pricing-btn" disabled>Current Plan</button>
            <?php else: ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                    <button type="submit" class="btn <?= $popular ? 'btn-white' : 'btn-primary' ?> btn-block pricing-btn">
                        <?= $sub ? 'Switch Plan' : 'Get Started' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Invoices -->
    <?php if (!empty($invoices)): ?>
    <div class="dash-section" style="margin-top:40px;">
        <h2 class="dash-section-title">Billing History</h2>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead><tr><th>Invoice #</th><th>Plan</th><th>Amount</th><th>Date</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?= clean($inv['invoice_number']) ?></td>
                        <td><?= clean($inv['plan_name']) ?></td>
                        <td>$<?= number_format($inv['amount'], 2) ?></td>
                        <td><?= date('M j, Y', strtotime($inv['created_at'])) ?></td>
                        <td><span class="status-badge status-active"><?= ucfirst($inv['status']) ?></span></td>
                        <td><button class="btn btn-outline btn-sm" onclick="window.print()">Download</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
