<?php
// Included after $currentAdmin = requireAdmin(); and after $page is known.
$currentAdmin = $currentAdmin ?? ['name' => ''];

function navLink(string $href, string $label, string $page, string $current): string {
    $class = $page === $current ? 'active' : '';
    return "<a class=\"$class\" href=\"$href\">$label</a>";
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="../logo.png">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — nara admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <span class="logo">nara<span style="font-size:11px;vertical-align:top;">®</span></span>
            <span class="role">Owner dashboard — <?= htmlspecialchars($currentAdmin['name']) ?></span>
            <nav class="admin-nav">
                <?= navLink('index.php', 'Dashboard', 'dashboard', $page) ?>
                <?= navLink('products.php', 'Products', 'products', $page) ?>
                <?= navLink('orders.php', 'Orders', 'orders', $page) ?>
                <a class="logout" href="logout.php">Log out</a>
            </nav>
        </aside>
        <main class="admin-main">
