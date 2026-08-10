<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, num_empleado, nombre, apellido, area, puesto 
        FROM empleados 
        WHERE activo = 1 
        ORDER BY nombre, apellido
        LIMIT 100");
    $stmt->execute();
    
    $empleados = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'empleados' => $empleados
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}