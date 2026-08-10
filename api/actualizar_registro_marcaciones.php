<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    session_start();
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    
    $tipo = $_POST['tipo'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if (!$tipo || !$id) {
        throw new Exception('Tipo e ID son requeridos');
    }
    
    $fecha_modificacion = date('Y-m-d H:i:s');
    $resultado = false;
    
    switch ($tipo) {
        case 'asistencia':
            $sql = "UPDATE asistencia SET 
                tipo_justificacion_id = ?,
                motivo_justificacion = ?,
                validado_por = ?,
                fecha_validacion = ?,
                modificado_por = ?,
                fecha_modificacion = ?
                WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $validado = isset($_POST['validado']) && $_POST['validado'] == '1' ? $usuario_id : null;
            $stmt->execute([
                $_POST['tipo_justificacion_id'] ?: null,
                $_POST['motivo'] ?: null,
                $validado,
                $validado ? $fecha_modificacion : null,
                $usuario_id,
                $fecha_modificacion,
                $id
            ]);
            $resultado = $stmt->rowCount() > 0;
            break;
            
        case 'retardos':
            $sql = "UPDATE retardos SET 
                tipo_justificacion_id = ?,
                motivo_justificacion = ?,
                modificado_por = ?,
                fecha_modificacion = ?
                WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $_POST['tipo_justificacion_id'] ?: null,
                $_POST['motivo'] ?: null,
                $usuario_id,
                $fecha_modificacion,
                $id
            ]);
            $resultado = $stmt->rowCount() > 0;
            break;
            
        case 'dias_eco':
            $sql = "UPDATE dias_economicos SET 
                motivo = ?,
                validado_por = ?,
                fecha_validacion = ?,
                modificado_por = ?,
                fecha_modificacion = ?
                WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $_POST['motivo'] ?: null,
                $usuario_id,
                $fecha_modificacion,
                $usuario_id,
                $fecha_modificacion,
                $id
            ]);
            $resultado = $stmt->rowCount() > 0;
            break;
            
        default:
            throw new Exception('Tipo de registro no soportado: ' . $tipo);
    }
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se realizaron cambios']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}