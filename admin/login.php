<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
require __DIR__ . '/../db.php';
securityHeaders();

if (isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $found = loginAllowed($ip, $email) ? adminByEmail($email) : null;

    if ($found && password_verify($password, $found['password_hash'])) {
        clearLoginFailures($ip, $email);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $found['id'];
        header('Location: index.php'); exit;
    }
    recordLoginFailure($ip, $email);
    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign in — nara admin</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-shell">
        <div class="login-card">
            <span class="logo">nara<span style="font-size:11px;vertical-align:top;">®</span></span>
            <p class="eyebrow">Owner dashboard</p>
            <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post"><?= csrfField() ?>
                <label>Email
                    <input type="email" name="email" required autofocus>
                </label>
                <label>Password
                    <input type="password" name="password" required>
                </label>
                <button class="button" type="submit" style="width:100%;justify-content:center;">Sign in</button>
            </form>
        </div>
    </div>
</body>

</html>
