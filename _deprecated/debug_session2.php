<?php
error_log("=== DEBUG SESSION ===");
error_log("session.save_path: " . session_save_path());
error_log("session_id: " . session_id());
error_log("session_status: " . session_status());
error_log("COOKIE: " . print_r($_COOKIE, true));
error_log("=====================");

header('Content-Type: text/plain');

echo "Session Save Path: " . session_save_path() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Cookies: " . print_r($_COOKIE, true) . "\n";
