<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== ELIMINANDO retardo_medio DE LA TABLA RETARDOS ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Actualizar registros que tienen retardo_menor o retardo_medio a retardo_menor
    $pdo->exec("UPDATE retardos SET tipo_retraso = 'retardo_menor' WHERE tipo_retraso = 'retardo_medio'");
    echo "✅ Registros actualizados\n";
    
    // También actualizo los que tienen 'mayor' a 'retardo_mayor'
    $pdo->exec("UPDATE retardos SET tipo_retraso = 'retardo_mayor' WHERE tipo_retraso = 'mayor'");
    echo "✅ Registros 'mayor' convertidos a 'retardo_mayor'\n";
    
    // Actualizo los que tienen 'menor' a 'retardo_menor'
    $pdo->exec("UPDATE retardos SET tipo_retraso = 'retardo_menor' WHERE tipo_retraso = 'menor'");
    echo "✅ Registros 'menor' convertidos a 'retardo_menor'\n";
    
    // Actualizo los que tienen 'normal' a 'retardo_menor'
    $pdo->exec("UPDATE retardos SET tipo_retraso = 'retardo_menor' WHERE tipo_retraso = 'normal'");
    echo "✅ Registros 'normal' convertidos a 'retardo_menor'\n";
    
    echo "\n🎉 Tipos de retardo actualizados correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
