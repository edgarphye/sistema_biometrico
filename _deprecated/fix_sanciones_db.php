<?php
// fix_sanciones_db.php - Script de reparación para tabla sanciones
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "🔍 Verificando esquema de tabla 'sanciones'...\n";
    
    // Obtener columnas
    $stmt = $pdo->query("DESCRIBE sanciones");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 1. Corregir fecha_inicio (Causante del Error 500)
    if (!in_array('fecha_inicio', $columns)) {
        if (in_array('fecha_sancion', $columns)) {
            echo "🛠️ Renombrando 'fecha_sancion' a 'fecha_inicio'...\n";
            $pdo->exec("ALTER TABLE sanciones CHANGE fecha_sancion fecha_inicio DATE NOT NULL");
        } else {
            echo "➕ Agregando columna 'fecha_inicio'...\n";
            $pdo->exec("ALTER TABLE sanciones ADD COLUMN fecha_inicio DATE NOT NULL AFTER motivo");
        }
    } else {
        echo "✅ Columna 'fecha_inicio' correcta.\n";
    }
    
    // 2. Agregar columna dias (Requerida por la vista show.php)
    if (!in_array('dias', $columns)) {
        echo "➕ Agregando columna 'dias'...\n";
        $pdo->exec("ALTER TABLE sanciones ADD COLUMN dias INT DEFAULT 0 AFTER fecha_inicio");
    }
    
    echo "\n🎉 Reparación completada. El error 500 debería estar resuelto.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
?>