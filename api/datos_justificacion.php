<?php
require_once 'Database.php';
require_once 'Csrf.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$db = new Database();

try {
    // Obtener empleado_id y fecha de los parámetros
    $empleado_id = $_GET['empleado_id'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $tipo = $_GET['tipo'] ?? 'asistencia'; // 'asistencia' o 'retardos'
    
    if (empty($empleado_id)) {
        throw new Exception('Empleado ID requerido');
    }
    
    $conn = $db->getConnection();
    
    // Obtener datos de asistencia para la fecha
    if ($tipo === 'asistencia') {
        $stmt = $conn->prepare("SELECT a.*, 
                   tj.id as tipo_justificacion_id, 
                   tj.nombre as tipo_justificacion_nombre,
                   tj.requiere_aprobacion
            FROM asistencia a
            LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
            WHERE a.empleado_id = ? AND a.fecha = ?
        ");
        $stmt->execute([$empleado_id, $fecha]);
        $registro = $stmt->fetch();
        
        if ($registro) {
            // Obtener tipos de justificación disponibles según el tipo de asistencia
            $tipo_asistencia = $registro['tipo_asistencia'] ?? 'normal';
            $tiposStmt = $conn->prepare("
                SELECT * FROM tipos_justificacion 
                WHERE activo = 1 
                ORDER BY nombre
            ");
            $tiposStmt->execute();
            $tipos_justificacion = $tiposStmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'tipo' => 'asistencia',
                'registro' => $registro,
                'tipos_justificacion' => $tipos_justificacion,
                'puede_modificar' => true
            ]);
        } else {
            // No hay registro, pero obtenemos los tipos disponibles
            $tiposStmt = $conn->prepare("SELECT * FROM tipos_justificacion WHERE activo = 1 ORDER BY nombre");
            $tiposStmt->execute();
            $tipos_justificacion = $tiposStmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'tipo' => 'asistencia',
                'registro' => null,
                'tipos_justificacion' => $tipos_justificacion,
                'puede_modificar' => false,
                'mensaje' => 'No hay registro de asistencia para esta fecha'
            ]);
        }
    } 
    // Obtener datos de retardo para la fecha
    elseif ($tipo === 'retardos') {
        $stmt = $conn->prepare("
            SELECT r.*, 
                   tj.id as tipo_justificacion_id, 
                   tj.nombre as tipo_justificacion_nombre,
                   tj.requiere_aprobacion
            FROM retardos r
            LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
            WHERE r.empleado_id = ? AND r.fecha = ?
        ");
        $stmt->execute([$empleado_id, $fecha]);
        $registro = $stmt->fetch();
        
        if ($registro) {
            $tiposStmt = $conn->prepare("SELECT * FROM tipos_justificacion WHERE activo = 1 ORDER BY nombre");
            $tiposStmt->execute();
            $tipos_justificacion = $tiposStmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'tipo' => 'retardos',
                'registro' => $registro,
                'tipos_justificacion' => $tipos_justificacion,
                'puede_modificar' => true
            ]);
        } else {
            $tiposStmt = $conn->prepare("SELECT * FROM tipos_justificacion WHERE activo = 1 ORDER BY nombre");
            $tiposStmt->execute();
            $tipos_justificacion = $tiposStmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'tipo' => 'retardos',
                'registro' => null,
                'tipos_justificacion' => $tipos_justificacion,
                'puede_modificar' => false,
                'mensaje' => 'No hay registro de retardo para esta fecha'
            ]);
        }
    } else {
        throw new Exception('Tipo inválido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}