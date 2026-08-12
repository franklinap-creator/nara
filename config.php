<?php
declare(strict_types=1);

// XAMPP can load the key from config.local.php; production can use an
// environment variable. The local file should never be committed to Git.
$localConfig = __DIR__ . DIRECTORY_SEPARATOR . 'config.local.php';
if (is_file($localConfig)) require $localConfig;
if (!defined('PAYSTACK_SECRET_KEY')) {
    $environmentKey = getenv('PAYSTACK_SECRET_KEY') ?: ($_SERVER['PAYSTACK_SECRET_KEY'] ?? '');
    define('PAYSTACK_SECRET_KEY', (string)$environmentKey);
}
define('PAYSTACK_CALLBACK_URL', (string)(getenv('PAYSTACK_CALLBACK_URL') ?: 'http://localhost/e_com/payment-callback.php'));

function paystackRequest(string $endpoint, array $payload = []): array
{
    $ch = curl_init('https://api.paystack.co' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => $payload !== [],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache',
        ],
        CURLOPT_POSTFIELDS => $payload !== [] ? json_encode($payload) : null,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $error !== '') {
        throw new RuntimeException('Payment provider connection failed: ' . $error);
    }
    $response = json_decode($raw, true);
    if (!is_array($response)) throw new RuntimeException('Invalid payment provider response.');
    return $response;
}

function paymentIsConfigured(): bool
{
    return PAYSTACK_SECRET_KEY !== '';
}
