<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
unset($_SESSION['admin_id']);
session_regenerate_id(true);
header('Location: login.php');
exit;
