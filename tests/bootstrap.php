<?php
// Bootstrap for PHPUnit tests: load config and autoloaders
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

// Ensure session not started for CLI tests
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
