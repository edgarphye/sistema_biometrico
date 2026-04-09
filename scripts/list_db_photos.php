<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';

try {
    $db = new PDO('mysql:host=localhost;dbname=sistema_biometrico;charset=utf8mb4', 'root', 'root');
    $stmt = $db->query('SELECT foto_cara FROM empleados WHERE foto_cara IS NOT NULL AND foto_cara != ""');
    $photos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $photos[] = $row['foto_cara'];
    }
    foreach ($photos as $photo) {
        echo $photo . PHP_EOL;
    }
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
