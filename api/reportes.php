<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

header('Content-Type: application/json; charset=utf-8');

$tipo = $_GET['tipo_reporte'] ?? '';
$empleado_id = $_GET['empleado_id'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$db = Database::getInstance();
$conn = $db->getConnection();

$resultados = [];

try {
    switch ($tipo) {
        case 'falta':
        case 'vacaciones':
        case 'licencia_medica':
        case 'dia_economico':
        case 'comision_todo_dia':
        case 'constancia_tiempo':
        case 'PDSEP-SNTE':
        case 'EYR':
        case 'CLIDDA':
        case 'cuidados_maternos':
            $resultados = generarReporteAsistencia($conn, $empleado_id, $fecha_inicio, $fecha_fin, $tipo);
            break;
        case 'retardos':
            $resultados = generarReporteRetardos($conn, $empleado_id, $fecha_inicio, $fecha_fin);
            break;
        case 'resumen':
            $resultados = generarReporteResumen($conn);
            break;
        default:
            $resultados = generarReporteAsistencia($conn, $empleado_id, $fecha_inicio, $fecha_fin);
            break;
    }

    echo json_encode([
        'success' => true,
        'tipo' => $tipo,
        'datos' => $resultados
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function generarReporteResumen($conn) {
    $stmt = $conn->query("SELECT tipo_asistencia, COUNT(*) as total FROM asistencia GROUP BY tipo_asistencia ORDER BY total DESC");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $resultados = [];
    foreach ($tipos as $t) {
        $resultados[] = [
            'tipo' => $t['tipo_asistencia'],
            'total' => (int)$t['total']
        ];
    }
    
    return $resultados;
}

function generarReporteAsistencia($conn, $empleado_id, $fecha_inicio, $fecha_fin, $tipo_asistencia = null) {
    $sql = "SELECT a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, a.tipo_asistencia,
                   e.nombre, e.apellido
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    if ($tipo_asistencia) {
        $sql .= " AND a.tipo_asistencia = ?";
        $params[] = $tipo_asistencia;
    }
    
    if ($empleado_id) {
        $sql .= " AND a.empleado_id = ?";
        $params[] = $empleado_id;
    }
    
    if ($fecha_inicio) {
        $sql .= " AND a.fecha >= ?";
        $params[] = $fecha_inicio;
    }
    
    if ($fecha_fin) {
        $sql .= " AND a.fecha <= ?";
        $params[] = $fecha_fin;
    }
    
    $sql .= " ORDER BY e.apellido, e.nombre, a.fecha LIMIT 1000";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $resultados = [];
    foreach ($registros as $r) {
        $resultados[] = [
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'empleado_id' => $r['empleado_id'],
            'fecha' => $r['fecha'],
            'hora_entrada' => $r['hora_entrada'] ?: '-',
            'hora_salida' => $r['hora_salida'] ?: '-',
            'tipo' => $r['tipo_asistencia']
        ];
    }
    
    return $resultados;
}

function generarReporteRetardos($conn, $empleado_id, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT r.empleado_id, r.fecha, r.hora_entrada, r.hora_salida, r.minutos_retardo,
                   e.nombre, e.apellido
            FROM retardos r
            JOIN empleados e ON r.empleado_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    if ($empleado_id) {
        $sql .= " AND r.empleado_id = ?";
        $params[] = $empleado_id;
    }
    
    if ($fecha_inicio) {
        $sql .= " AND r.fecha >= ?";
        $params[] = $fecha_inicio;
    }
    
    if ($fecha_fin) {
        $sql .= " AND r.fecha <= ?";
        $params[] = $fecha_fin;
    }
    
    $sql .= " ORDER BY e.apellido, e.nombre, r.fecha LIMIT 1000";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $resultados = [];
    foreach ($registros as $r) {
        $resultados[] = [
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'empleado_id' => $r['empleado_id'],
            'fecha' => $r['fecha'],
            'hora_entrada' => $r['hora_entrada'] ?: '-',
            'hora_salida' => $r['hora_salida'] ?: '-',
            'minutos_retardo' => $r['minutos_retardo'],
            'tipo' => 'retardo'
        ];
    }
    
    return $resultados;
}