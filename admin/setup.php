<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
require __DIR__ . '/../db.php';
securityHeaders();

// Only usable while no admin account exists yet — this is how the very
// first owner login gets created. Once an admin exists, this page refuses
// to run. Delete this file after you've set up your account.
$existingCount = (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($existingCount > 0) {
    die('An admin account already exists. This setup page is now disabled — delete setup.php. To log in, go to login.php.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid name and email.';
    } elseif (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        $error = 'Password must be at least 12 characters and include uppercase, lowercase, and a number.';
    } else {
        $stmt = db()->prepare('INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        header('Location: login.php?created=1'); exit;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/png" href="../logo.png">
    <title>Create owner account — nara admin</title>
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
            <p class="eyebrow">Create the owner account (one-time)</p>
            <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post"><?= csrfField() ?>
                <label>Your name
                    <input name="name" required autofocus>
                </label>
                <label>Email
                    <input type="email" name="email" required>
                </label>
                <label>Password
                    <input type="password" name="password" minlength="12" pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{12,}" required>
                </label>
                <button class="button" type="submit" style="width:100%;justify-content:center;">Create account</button>
            </form>
        </div>
    </div>
</body>

</html>
