<?php
require_once 'config.php';

$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

try {
    $db->exec("ALTER TABLE retardos ADD COLUMN validado_por_jefe TINYINT(1) DEFAULT 0");
    echo "Columna validado_por_jefe agregada\n";
} catch (Exception $e) {
    echo "Columna ya existe o error: " . $e->getMessage() . "\n";
}

echo "Listo!\n";
