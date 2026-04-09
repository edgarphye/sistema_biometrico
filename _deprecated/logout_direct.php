<?php
// logout_simple.php - Logout simple

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: login_direct.php');
exit;
?>