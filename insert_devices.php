<?php
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    for($i=1; $i<=10; $i++) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO dispositivos_biometricos (dispositivo_id, nombre, tipo, estado) VALUES (?, ?, ?, ?)');
        $stmt->execute([$i, 'Dispositivo ' . $i, 'dual', 'conectado']);
    }

    echo 'Dispositivos insertados exitosamente';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
