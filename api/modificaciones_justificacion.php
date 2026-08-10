<?php
require_once 'Database.php';
require_once 'Csrf.php';
require_once 'ModificacionJustificacion.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$modificacionModel = new ModificacionJustificacion();

// Solo permitir métodos POST, PUT, DELETE
if ($method !== 'POST' && $method !== 'PUT' && $method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar CSRF para mutaciones
if (in_array($method, ['POST', 'PUT'])) {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }
}

try {
    switch ($method) {
        case 'GET':
            // Obtener lista de modificaciones
            $filtros = [
                'empleado_id' => $_GET['empleado_id'] ?? null,
                'estatus' => $_GET['estatus'] ?? null,
                'tabla_origen' => $_GET['tabla_origen'] ?? null,
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'limit' => $_GET['limit'] ?? 100
            ];
            
            $modificaciones = $modificacionModel->getAll($filtros);
            echo json_encode(['success' => true, 'datos' => $modificaciones]);
            break;
            
        case 'POST':
            // Crear nueva modificación de justificación
            $data = [
                'tabla_origen' => $_POST['tabla_origen'] ?? null,
                'registro_id' => $_POST['registro_id'] ?? null,
                'empleado_id' => $_POST['empleado_id'] ?? null,
                'campo_modificado' => $_POST['campo_modificado'] ?? 'tipo_justificacion_id',
                'valor_anterior' => $_POST['valor_anterior'] ?? null,
                'valor_nuevo' => $_POST['valor_nuevo'] ?? null,
                'modificado_por' => $_POST['modificado_por'] ?? $_SESSION['usuario_id'] ?? null,
                'motivo_modificacion' => $_POST['motivo_modificacion'] ?? null
            ];
            
            // Validaciones
            if (empty($data['tabla_origen']) || empty($data['registro_id']) || empty($data['empleado_id'])) {
                throw new Exception('Faltan campos requeridos');
            }
            
            if (!in_array($data['tabla_origen'], ['asistencia', 'retardos'])) {
                throw new Exception('Tabla de origen inválida');
            }
            
            $resultado = $modificacionModel->create($data);
            echo json_encode(['success' => true, 'id' => $resultado, 'mensaje' => 'Modificación creada correctamente']);
            break;
            
        case 'PUT':
            // Validar/modificar una modificación existente
            $modificacion_id = $_POST['modificacion_id'] ?? null;
            $estatus = $_POST['estatus'] ?? null;  // 'aprobado' o 'rechazado'
            $es_validacion_jefe = isset($_POST['es_validacion_jefe']) && $_POST['es_validacion_jefe'] === '1';
            $user_id = $_POST['user_id'] ?? $_SESSION['usuario_id'] ?? null;
            
            if (empty($modificacion_id) || empty($estatus) || empty($user_id)) {
                throw new Exception('Faltan campos requeridos para validación');
            }
            
            if (!in_array($estatus, ['aprobado', 'rechazado'])) {
                throw new Exception('Estatus inválido');
            }
            
            $modificacionModel->validar($modificacion_id, $user_id, $estatus, $es_validacion_jefe);
            echo json_encode(['success' => true, 'mensaje' => "Modificación $estatus correctamente"]);
            break;
            
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}