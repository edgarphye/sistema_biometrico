<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== ESTRUCTURA TABLA RETARDOS ===\n";

$db = new Database();
$pdo = $db->getConnection();

$stmt = $pdo->prepare('DESCRIBE retardos');
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '- ' . $row['Field'] . ' (' . $row['Type'] . ') ' . $row['Null'] . ' ' . $row['Key'] . ')' . "\n";
}
?>