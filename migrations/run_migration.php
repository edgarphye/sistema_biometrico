<?php
// Simple migration runner that executes a provided SQL file using the project's Database class.
// Usage: php run_migration.php migrations/20251119_add_sanciones_retardos_soporte.sql

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

if ($argc < 2) {
    echo "Usage: php run_migration.php path/to/migration.sql\n";
    exit(1);
}

$sqlFile = $argv[1];
if (!file_exists($sqlFile)) {
    echo "SQL file not found: $sqlFile\n";
    exit(1);
}

$db = new Database();
$pdo = $db->getConnection();

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Failed to read SQL file\n";
    exit(1);
}

$logDir = __DIR__ . '/';
$logFile = $logDir . 'migration_log.txt';

try {
    // Split SQL file into individual statements and execute sequentially.
    // This avoids issues with PDO exec and multi-statement SQL files.
    $statements = preg_split('/;\s*\r?\n/', $sql);
    foreach ($statements as $stmt) {
        $s = trim($stmt);
        if ($s === '') continue;
        // Append semicolon only if not present (some statements may require it)
        try {
            $pdo->exec($s);
        } catch (PDOException $e) {
            // Log specific failed statement and rethrow
            $err = date('c') . " - Statement failed: " . $e->getMessage() . " -- Statement: " . substr($s, 0, 200) . "\n";
            file_put_contents($logFile, $err, FILE_APPEND | LOCK_EX);
            throw $e;
        }
    }

    $msg = date('c') . " - Migration succeeded: $sqlFile\n";
    echo $msg;
    file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $msg = date('c') . " - Migration failed: " . $e->getMessage() . "\n";
    echo $msg;
    file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    exit(1);
}

?>