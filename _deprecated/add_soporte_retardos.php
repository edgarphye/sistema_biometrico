<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== AGREGANDO COLUMNA soporte A RETARDOS ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Agregar columna soporte si no existe
    $pdo->exec("ALTER TABLE retardos ADD COLUMN soporte VARCHAR(255) NULL AFTER fecha_aprobacion");
    echo "✅ Columna 'soporte' agregada\n";
    
    echo "🎉 Listo!\n";
    
} catch (Exception $e) {
    echo "ℹ️  " . $e->getMessage() . "\n";
}
