<?php
require_once 'config.php';
require_once 'models/Database.php';

$db = new Database();
$pdo = $db->getConnection();

try {
    $pdo->exec("ALTER TABLE empleados ADD COLUMN jefe_directo_clave VARCHAR(50) NULL AFTER jefe_directo_id");
    echo "Columna jefe_directo_clave agregada correctamente";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
