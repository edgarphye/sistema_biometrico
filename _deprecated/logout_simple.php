<?php
// logout_simple.php - Logout simple
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: direct_login_test.php');
exit;
?>