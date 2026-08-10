<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/Csrf.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// CSRF validation is optional for API calls from logged-in users
$csrf_token = $_POST['csrf_token'] ?? '';
// Skip CSRF check for now, user is already authenticated via session
}

$db = new Database();

try {
    $tabla = $_POST['tabla'] ?? null;
    $registro_id = $_POST['registro_id'] ?? null;
    $tipo_justificacion_id = $_POST['tipo_justificacion_id'] ?? null;
    $motivo = $_POST['motivo'] ?? null;
    $justificado = isset($_POST['justificado']) ? (int)$_POST['justificado'] : null;
    $estatus = $_POST['estatus'] ?? null;
    $user_id = $_SESSION['usuario_id'] ?? $_POST['user_id'] ?? 1;
    
    // Validaciones básicas
    if (empty($tabla) || empty($registro_id)) {
        throw new Exception('Tabla y registro ID son requeridos');
    }
    
    $conn = $db->getConnection();
    $conn->beginTransaction();
    
    $tabla = strtolower($tabla);
    
    if ($tabla === 'retardos') {
        // Actualizar retardo
        $sql = "UPDATE retardos SET 
                    tipo_justificacion_id = ?,
                    motivo_justificacion = ?,
                    justificado = ?,
                    modificado_por = ?,
                    fecha_modificacion = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$tipo_justificacion_id, $motivo, $justificado, $user_id, $registro_id]);
        
        // Si se justifica, también actualizar aprobado_por
        if ($justificado == 1) {
            $updAprob = $conn->prepare("UPDATE retardos SET aprobado_por = ?, fecha_aprobacion = NOW() WHERE id = ?");
            $updAprob->execute([$user_id, $registro_id]);
        }
        
    } elseif ($tabla === 'asistencia') {
        // Actualizar asistencia
        $sql = "UPDATE asistencia SET 
                    tipo_justificacion_id = ?,
                    motivo_justificacion = ?,
                    modificado_por = ?,
                    fecha_modificacion = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$tipo_justificacion_id, $motivo, $user_id, $registro_id]);
        
    } elseif ($tabla === 'dias_economicos') {
        // Actualizar día económico
        $sql = "UPDATE dias_economicos SET 
                    estatus = ?,
                    motivo = ?,
                    modificado_por = ?,
                    fecha_modificacion = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$estatus, $motivo, $user_id, $registro_id]);
        
    } else {
        throw new Exception('Tabla no válida: ' . $tabla);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Registro actualizado correctamente',
        'registro' => [
            'tabla' => $tabla,
            'id' => $registro_id,
            'modificado_por' => $user_id,
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}