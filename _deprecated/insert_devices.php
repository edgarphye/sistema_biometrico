<?php
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $baseIp = '192.168.1.';
    for($i=1; $i<=10; $i++) {
        $stmt = $pdo->prepare('INSERT INTO dispositivos_biometricos (dispositivo_id, nombre, tipo, ip_address, puerto, activo, sede) VALUES (?, ?, ?, ?, ?, 1, "SEDE_A") ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), ip_address=VALUES(ip_address), puerto=VALUES(puerto), activo=1, sede="SEDE_A"');
        $stmt->execute([$i, 'Dispositivo ' . $i, 'dual', $baseIp . (100 + $i), 4370]);
    }

    echo 'Dispositivos insertados exitosamente';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
