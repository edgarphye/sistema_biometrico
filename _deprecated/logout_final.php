<?php
// logout_final.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: login_final.php');
exit;
?>