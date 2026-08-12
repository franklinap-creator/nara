<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
require __DIR__ . '/../db.php';
securityHeaders();

$currentAdmin = requireAdmin();
$page = 'orders';
$pageTitle = 'Orders';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    requireCsrf();
    updateOrderStatus((int)$_POST['id'], $_POST['status']);
    header('Location: orders.php?' . ($_GET['status'] ?? '' ? 'status=' . urlencode($_GET['status']) : '')); exit;
}

$statuses = ['All', 'pending', 'paid', 'shipped', 'delivered', 'cancelled'];
$activeStatus = $_GET['status'] ?? 'All';
$list = orders($activeStatus);

require __DIR__ . '/partials.php';
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Fulfillment</p>
        <h1>Orders</h1>
    </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:24px;">
    <?php foreach ($statuses as $s): ?>
    <a class="button <?= $activeStatus === $s ? '' : 'ghost' ?>" style="text-transform:capitalize;padding:9px 16px;"
        href="orders.php<?= $s !== 'All' ? '?status=' . urlencode($s) : '' ?>"><?= $s ?></a>
    <?php endforeach; ?>
</div>

<?php if (!$list): ?>
<div class="empty-state">No orders in this view yet.</div>
<?php else: ?>
<table class="admin-table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Delivery address</th>
            <th>Products</th>
            <th>Total</th>
            <th>Status</th>
            <th>Placed</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($list as $o): ?>
        <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= htmlspecialchars($o['customer_name']) ?><br><span
                    style="color:var(--muted);font-size:11px;"><?= htmlspecialchars($o['email']) ?></span></td>
            <td style="max-width:220px;"><?= htmlspecialchars($o['address']) ?></td>
            <td>
                <details class="order-products">
                    <summary><?= count($o['products']) ?> item<?= count($o['products']) === 1 ? '' : 's' ?></summary>
                    <div class="order-products-list">
                        <?php foreach ($o['products'] as $item): ?>
                        <div class="order-product">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="">
                            <div>
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <span><?= htmlspecialchars($item['category']) ?> · Qty: <?= (int)$item['quantity'] ?></span>
                            </div>
                            <b><?= money((int)$item['price'] * (int)$item['quantity']) ?></b>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            </td>
            <td><?= money((int)$o['total']) ?></td>
            <td>
                <form method="post" style="display:flex;gap:8px;align-items:center;"><?= csrfField() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                    <select name="status" class="status-select" onchange="this.form.submit()">
                        <?php foreach (['pending','paid','shipped','delivered','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </td>
            <td><?= date('d M, H:i', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
