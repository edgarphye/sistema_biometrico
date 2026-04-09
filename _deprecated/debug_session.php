<?php
header('Content-Type: text/plain');

echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "\nSession Data:\n";
    print_r($_SESSION);
}

echo "\n\nPOST Data:\n";
print_r($_POST);

echo "\n\nCookies:\n";
print_r($_COOKIE);
