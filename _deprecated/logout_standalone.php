<?php
// logout_standalone.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: login_standalone.php');
exit;
?>