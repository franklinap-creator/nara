<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
require __DIR__ . '/../db.php';
securityHeaders();

$currentAdmin = requireAdmin();
$page = 'dashboard';
$pageTitle = 'Dashboard';
$stats = dashboardStats();
$recentOrders = array_slice(orders(), 0, 5);

require __DIR__ . '/partials.php';
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Overview</p>
        <h1>Good to see you, <?= htmlspecialchars(explode(' ', $currentAdmin['name'])[0]) ?>.</h1>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card <?= $stats['pending_orders'] > 0 ? 'alert' : '' ?>">
        <div class="label">Pending orders</div>
        <div class="value"><?= $stats['pending_orders'] ?></div>
    </div>
    <div class="stat-card <?= $stats['low_stock'] > 0 ? 'alert' : '' ?>">
        <div class="label">Low stock (&le;5 left)</div>
        <div class="value"><?= $stats['low_stock'] ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Revenue today</div>
        <div class="value"><?= money($stats['today_revenue']) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Products live</div>
        <div class="value"><?= $stats['total_products'] ?></div>
    </div>
</div>

<div class="page-head">
    <div>
        <p class="eyebrow">Latest activity</p>
        <h1 style="font-size:24px;">Recent orders</h1>
    </div>
    <a class="button ghost" href="orders.php">View all orders</a>
</div>

<?php if (!$recentOrders): ?>
<div class="empty-state">No orders yet.</div>
<?php else: ?>
<table class="admin-table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th>Placed</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= htmlspecialchars($o['customer_name']) ?></td>
            <td><?= money((int)$o['total']) ?></td>
            <td><span class="badge-status <?= $o['status'] ?>"><?= htmlspecialchars($o['status']) ?></span></td>
            <td><?= date('d M, H:i', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
