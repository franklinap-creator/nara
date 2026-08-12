<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
securityHeaders();

$reference = $_GET['reference'] ?? '';
$pending = $_SESSION['pending_order'] ?? null;

// The reference in the URL must match the one we generated and stored in
// session during payment-init.php — this stops someone from hitting this
// URL directly with a made-up or stale reference.
if (!$reference || !$pending || ($pending['reference'] ?? '') !== $reference) {
    die('Invalid or expired payment session.');
}

$ch = curl_init('https://api.paystack.co/transaction/verify/' . urlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY
    ]
]);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (
    ($response['status'] ?? false) === true &&
    ($response['data']['status'] ?? '') === 'success'
) {
    // Confirm the amount Paystack actually received matches what we expected
    // to charge. Without this, a tampered request could pay less than the
    // real total and still be treated as a successful order.
    $paidKobo = (int)($response['data']['amount'] ?? 0);
    $expectedKobo = (int)round($pending['amount'] * 100);

    if ($paidKobo !== $expectedKobo) {
        die('Payment amount mismatch. Please contact support with reference ' . htmlspecialchars($reference) . '.');
    }

    $items = cartItems();
    if (!$items) {
        die('Your cart is empty — nothing to save. Please contact support with reference ' . htmlspecialchars($reference) . '.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name,email,address,total,status) VALUES (?,?,?,?,?)");
    $stmt->execute([$pending['name'], $pending['email'], $pending['address'], $pending['amount'], 'paid']);
    $orderId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)');
    $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
    foreach ($items as $item) {
        $itemStmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
        $stockStmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
        if ($stockStmt->rowCount() !== 1) {
            $pdo->rollBack();
            die('One or more products are no longer available in the requested quantity. Please contact support with reference ' . htmlspecialchars($reference) . '.');
        }
    }
    $pdo->commit();

    // Only clear the cart and pending order now that everything is safely saved.
    $_SESSION['cart'] = [];
    unset($_SESSION['pending_order']);

    header('Location: index.php?page=success&order=' . $orderId . '&name=' . urlencode($pending['name']));
    exit;
}

die('Payment was not successful.');
