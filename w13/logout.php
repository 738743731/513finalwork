<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear remember me cookie
$cookie_name = 'remember_me_' . md5($_SERVER['REMOTE_ADDR']);
if (isset($_COOKIE[$cookie_name])) {
    setcookie($cookie_name, '', time() - 3600, '/');
}

// Redirect to login page
header("Location: login.php?logged_out=success");
exit();
?>