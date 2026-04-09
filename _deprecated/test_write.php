<?php
// Test de escritura
$testFile = 'C:\\tools\\nginx\\tmp\\test_' . time() . '.txt';
$result = file_put_contents($testFile, 'test content');
echo "Test file: $testFile\n";
echo "Result: $result\n";

if ($result !== false) {
    unlink($testFile);
    echo "Write test passed\n";
} else {
    echo "Write test failed\n";
}

// Session test
session_start();
$_SESSION['test'] = 'hello';
echo "Session ID: " . session_id() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";

$sessionFile = session_save_path() . '/sess_' . session_id();
echo "Session file: $sessionFile\n";
echo "File exists: " . (file_exists($sessionFile) ? 'yes' : 'no') . "\n";
