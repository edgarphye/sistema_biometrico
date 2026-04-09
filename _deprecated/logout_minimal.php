<?php
// logout_minimal.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: login_minimal.php');
exit;
?>