<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== CREANDO RETARDO DE PRUEBA ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Obtener un empleado aleatorio
    $stmt = $pdo->query("SELECT id FROM empleados LIMIT 1");
    $empleado = $stmt->fetch();
    
    if (!$empleado) {
        echo "❌ No hay empleados en la base de datos\n";
        exit;
    }
    
    $empleado_id = $empleado['id'];
    $fecha = date('Y-m-d');
    
    // Insertar retardo de prueba
    $stmt = $pdo->prepare("INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_retraso, justificado) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$empleado_id, $fecha, 15, 'retardo_menor']);
    
    $retardo_id = $pdo->lastInsertId();
    echo "✅ Retardo creado con ID: $retardo_id\n";
    echo "   Empleado ID: $empleado_id\n";
    echo "   Fecha: $fecha\n";
    echo "   Minutos: 15\n";
    echo "   Tipo: retardo_menor\n";
    echo "   Estado: Pendiente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
