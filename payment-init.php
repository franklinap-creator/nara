<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
securityHeaders();

// Guard: someone landing here directly without going through checkout first.
if (!isset($_SESSION['pending_order']) || !cartItems()) {
    header('Location: index.php?page=checkout'); exit;
}
if (!paymentIsConfigured()) {
    http_response_code(500);
    exit('Paystack is not configured. Set PAYSTACK_SECRET_KEY in the Apache/PHP environment, then restart Apache.');
}

$pending = $_SESSION['pending_order'];
$email = $pending['email'];

$subtotal = cartTotal();
$deliveryFee = $subtotal >= 30000 ? 0 : 1800;
$grandTotal = $subtotal + $deliveryFee; // naira
$amount = $grandTotal * 100; // Paystack needs kobo

$reference = 'NARA-' . time() . '-' . random_int(1000, 9999);

// Remember the reference and the exact amount we expect to be paid, so the
// callback can verify Paystack's response matches — not just trust it.
$_SESSION['pending_order']['reference'] = $reference;
$_SESSION['pending_order']['amount'] = $grandTotal;

$data = [
    'email' => $email,
    'amount' => $amount,
    'reference' => $reference,
    'callback_url' => PAYSTACK_CALLBACK_URL,
];

try {
    $response = paystackRequest('/transaction/initialize', $data);
    if (($response['status'] ?? false) && !empty($response['data']['authorization_url'])) {
        header('Location: ' . $response['data']['authorization_url']);
        exit;
    }
    $message = $response['message'] ?? 'Paystack rejected the transaction.';
    http_response_code(502);
    exit('Payment initialization failed: ' . htmlspecialchars((string)$message));
} catch (Throwable $error) {
    http_response_code(502);
    exit('Payment initialization failed: ' . htmlspecialchars($error->getMessage()));
}
