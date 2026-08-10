<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verify authentication
$pdoCheck = new PDO('mysql:host=localhost;dbname=sistema_biometrico', 'root', 'root');

$sessionId = $_COOKIE['PHPSESSID'] ?? session_id();

if (empty($sessionId)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Sesión expirada',
        'redirect' => '/login',
        'message' => 'Por favor, inicia sesión nuevamente'
    ]);
    exit;
}

$stmt = $pdoCheck->prepare("SELECT data FROM sessions WHERE id = ?");
$stmt->execute([$sessionId]);
$sessionData = $stmt->fetchColumn();

if (!$sessionData || strpos($sessionData, 'user_id') === false) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Sesión expirada',
        'redirect' => '/login',
        'message' => 'Por favor, inicia sesión nuevamente'
    ]);
    exit;
}

session_decode($sessionData);
$_SESSION['user_id'] = $_SESSION['user_id'] ?? null;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = ($page - 1) * $limit;

$estado = $_GET['estado'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$empleado_id = $_GET['empleado_id'] ?? '';
$tipo_registro = $_GET['tipo_registro'] ?? '';

$conn = Database::getInstance()->getConnection();

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];

if ($fecha_inicio) {
    $where .= " AND fecha >= ?";
    $params[] = $fecha_inicio;
}
if ($fecha_fin) {
    $where .= " AND fecha <= ?";
    $params[] = $fecha_fin;
}
if ($departamento) {
    $where .= " AND clave_depto = ?";
    $params[] = $departamento;
}
if ($empleado_id) {
    $where .= " AND empleado_id = ?";
    $params[] = $empleado_id;
}

// Build estado filter
$estadoFilter = "";
if ($estado === 'justificado') {
    $estadoFilter = "AND justificado = 1";
} elseif ($estado === 'pendiente') {
    $estadoFilter = "AND justificado = 0";
}

// Use UNION to get all records efficiently
$sql = "
SELECT * FROM (
    SELECT r.id, r.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           r.fecha, r.hora_entrada, r.hora_salida, r.minutos_retardo, 
           r.justificado, 'Retardo' as tipo_registro, r.tipo_retraso, 
           r.estado_validacion, r.jefe_directo_id, 
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           tj.nombre as justificacion_tipo, r.motivo as motivo_justificacion,
           '' as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, '' as licencia_diagnostico, 0 as licencia_dias, 
           '' as licencia_folio, 0 as diaeco_dias, '' as ausencia_tipo, 
           '' as ausencia_fecha_ini, '' as ausencia_fecha_fin,
           '' as sancion_tipo, 0 as sancion_dias, '' as sancion_estatus,
           r.fecha as fecha_original
    FROM retardos r
    JOIN empleados e ON r.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
    WHERE r.justificado = 0 AND r.empleado_id IS NOT NULL $estadoFilter
    
    UNION ALL
    
    SELECT c.id, c.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           c.fecha_inicio as fecha, '-' as hora_entrada, '-' as hora_salida, 0 as minutos_retardo,
           CASE WHEN c.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado, 
           'Comisión' as tipo_registro, 'comision' as tipo_retraso,
           c.estatus as estado_validacion, c.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           '' as justificacion_tipo, c.descripcion as motivo_justificacion,
           'comision' as registro_tipo, c.lugar as comision_lugar, 
           c.fecha_inicio as comision_fecha_ini, c.fecha_fin as comision_fecha_fin,
           '' as licencia_diagnostico, 0 as licencia_dias, '' as licencia_folio,
           0 as diaeco_dias, '' as ausencia_tipo, '' as ausencia_fecha_ini, 
           '' as ausencia_fecha_fin, '' as sancion_tipo, 0 as sancion_dias, 
           '' as sancion_estatus, c.fecha_inicio as fecha_original
    FROM comisiones c
    JOIN empleados e ON c.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    WHERE c.estatus != 'rechazada' $estadoFilter
    
    UNION ALL
    
    SELECT lm.id, lm.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           lm.fecha_inicio as fecha, '-' as hora_entrada, '-' as hora_salida, 0 as minutos_retardo,
           CASE WHEN lm.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
           'Licencia Médica' as tipo_registro, 'licencia' as tipo_retraso,
           lm.estatus as estado_validacion, lm.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           '' as justificacion_tipo, lm.diagnostico as motivo_justificacion,
           'licencia' as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, lm.diagnostico as licencia_diagnostico, 
           lm.dias_otorgados as licencia_dias, lm.folio as licencia_folio,
           0 as diaeco_dias, '' as ausencia_tipo, '' as ausencia_fecha_ini, 
           '' as ausencia_fecha_fin, '' as sancion_tipo, 0 as sancion_dias, 
           '' as sancion_estatus, lm.fecha_inicio as fecha_original
    FROM licencias_medicas lm
    JOIN empleados e ON lm.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    WHERE lm.estatus != 'rechazada' $estadoFilter
    
    UNION ALL
    
    SELECT de.id, de.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           de.fecha as fecha, '-' as hora_entrada, '-' as hora_salida, 0 as minutos_retardo,
           CASE WHEN de.estatus = 'aprobado' THEN 1 ELSE 0 END as justificado,
           'Día Económico' as tipo_registro, 'dia_economico' as tipo_retraso,
           de.estatus as estado_validacion, de.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           '' as justificacion_tipo, de.motivo as motivo_justificacion,
           'dia_economico' as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, '' as licencia_diagnostico, 0 as licencia_dias, 
           '' as licencia_folio, de.dias_solicitados as diaeco_dias, 
           '' as ausencia_tipo, '' as ausencia_fecha_ini, '' as ausencia_fecha_fin,
           '' as sancion_tipo, 0 as sancion_dias, '' as sancion_estatus,
           de.fecha as fecha_original
    FROM dias_economicos de
    JOIN empleados e ON de.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    WHERE de.estatus != 'rechazado' $estadoFilter
    
    UNION ALL
    
    SELECT a.id, a.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           a.fecha_inicio as fecha, '-' as hora_entrada, '-' as hora_salida, 0 as minutos_retardo,
           CASE WHEN a.validado_por_jefe IS NOT NULL THEN 1 ELSE 0 END as justificado,
           'Ausencia' as tipo_registro, 'ausencia' as tipo_retraso,
           a.validado_por_jefe as estado_validacion, a.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           '' as justificacion_tipo, a.motivo as motivo_justificacion,
           a.tipo_ausencia as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, '' as licencia_diagnostico, 0 as licencia_dias, 
           '' as licencia_folio, 0 as diaeco_dias,
           a.tipo_ausencia as ausencia_tipo, a.fecha_inicio as ausencia_fecha_ini, 
           a.fecha_fin as ausencia_fecha_fin,
           '' as sancion_tipo, 0 as sancion_dias, '' as sancion_estatus,
           a.fecha_inicio as fecha_original
    FROM ausencias a
    JOIN empleados e ON a.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    WHERE a.activo = 1 $estadoFilter
    
    UNION ALL
    
    SELECT v.id, v.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           v.fecha_inicio as fecha, '-' as hora_entrada, '-' as hora_salida, 0 as minutos_retardo,
           CASE WHEN v.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
           'Vacaciones' as tipo_registro, 'vacaciones' as tipo_retraso,
           v.estatus as estado_validacion, v.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           '' as justificacion_tipo, v.motivo as motivo_justificacion,
           'vacaciones' as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, '' as licencia_diagnostico, 0 as licencia_dias, 
           '' as licencia_folio, 0 as diaeco_dias,
           '' as ausencia_tipo, '' as ausencia_fecha_ini, '' as ausencia_fecha_fin,
           '' as sancion_tipo, v.dias as sancion_dias, '' as sancion_estatus,
           v.fecha_inicio as fecha_original
    FROM vacaciones v
    JOIN empleados e ON v.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    WHERE 1=1 $estadoFilter
    
    UNION ALL
    
    SELECT cm.id, cm.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           cm.fecha_inicio as fecha, '-' as hora_entrada, '-' as hora_salida, 0 as minutos_retardo,
           CASE WHEN cm.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
           'Cuidados' as tipo_registro, 'cuidados' as tipo_retraso,
           cm.estatus as estado_validacion, cm.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           '' as justificacion_tipo, cm.motivo as motivo_justificacion,
           'cuidados' as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, '' as licencia_diagnostico, 0 as licencia_dias, 
           '' as licencia_folio, 0 as diaeco_dias,
           '' as ausencia_tipo, '' as ausencia_fecha_ini, '' as ausencia_fecha_fin,
           cm.tipo_sancion as sancion_tipo, cm.dias as sancion_dias, cm.estatus as sancion_estatus,
           cm.fecha_inicio as fecha_original
    FROM cuidados_maternos cm
    JOIN empleados e ON cm.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    WHERE 1=1 $estadoFilter
    
    UNION ALL
    
    SELECT f.id, f.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           f.fecha as fecha, f.hora_entrada, f.hora_salida, f.minutos_retardo,
           CASE WHEN f.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
           'Falta' as tipo_registro, 'falta' as tipo_retraso,
           f.estatus as estado_validacion, f.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           tj.nombre as justificacion_tipo, f.motivo as motivo_justificacion,
           'falta' as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, '' as licencia_diagnostico, 0 as licencia_dias, 
           '' as licencia_folio, 0 as diaeco_dias,
           '' as ausencia_tipo, '' as ausencia_fecha_ini, '' as ausencia_fecha_fin,
           '' as sancion_tipo, f.minutos_retardo as sancion_dias, f.estatus as sancion_estatus,
           f.fecha as fecha_original
    FROM faltas f
    JOIN empleados e ON f.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    LEFT JOIN tipos_justificacion tj ON f.tipo_justificacion_id = tj.id
    WHERE 1=1 $estadoFilter
    
    UNION ALL
    
    SELECT ct.id, ct.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           ct.fecha_inicio as fecha, '-' as hora_entrada, '-' as hora_salida, 0 as minutos_retardo,
           CASE WHEN ct.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
           'Constancia' as tipo_registro, 'constancia' as tipo_retraso,
           ct.estatus as estado_validacion, ct.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           '' as justificacion_tipo, ct.motivo as motivo_justificacion,
           'constancia' as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, '' as licencia_diagnostico, 0 as licencia_dias, 
           '' as licencia_folio, 0 as diaeco_dias,
           '' as ausencia_tipo, '' as ausencia_fecha_ini, '' as ausencia_fecha_fin,
           ct.tipo_constancia as sancion_tipo, ct.dias as sancion_dias, ct.estatus as sancion_estatus,
           ct.fecha_inicio as fecha_original
    FROM constancias_tiempo ct
    JOIN empleados e ON ct.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    WHERE 1=1 $estadoFilter
    
    UNION ALL
    
    SELECT a.id, a.empleado_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
           a.fecha as fecha, a.hora_entrada, a.hora_salida, 0 as minutos_retardo,
           0 as justificado,
           'Por Definir' as tipo_registro, 'por_definir' as tipo_retraso,
           a.estado_validacion, a.jefe_directo_id,
           e2.nombre as jefe_nombre, e2.apellido as jefe_apellido,
           tj.nombre as justificacion_tipo, a.motivo as motivo_justificacion,
           a.tipo_asistencia as registro_tipo, '' as comision_lugar, '' as comision_fecha_ini, 
           '' as comision_fecha_fin, '' as licencia_diagnostico, 0 as licencia_dias, 
           '' as licencia_folio, 0 as diaeco_dias,
           '' as ausencia_tipo, '' as ausencia_fecha_ini, '' as ausencia_fecha_fin,
           '' as sancion_tipo, 0 as sancion_dias, '' as sancion_estatus,
           a.fecha as fecha_original
    FROM asistencia a
    JOIN empleados e ON a.empleado_id = e.id
    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
    LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
    WHERE a.tipo_asistencia = 'por_definir'
) as t
$where
ORDER BY fecha_original DESC
LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
";

// Get total count
$sqlCount = "
SELECT COUNT(*) as total FROM (
    SELECT r.id FROM retardos r
    JOIN empleados e ON r.empleado_id = e.id
    WHERE r.justificado = 0 AND r.empleado_id IS NOT NULL $estadoFilter
    
    UNION ALL
    
    SELECT c.id FROM comisiones c
    JOIN empleados e ON c.empleado_id = e.id
    WHERE c.estatus != 'rechazada' $estadoFilter
    
    UNION ALL
    
    SELECT lm.id FROM licencias_medicas lm
    JOIN empleados e ON lm.empleado_id = e.id
    WHERE lm.estatus != 'rechazada' $estadoFilter
    
    UNION ALL
    
    SELECT de.id FROM dias_economicos de
    JOIN empleados e ON de.empleado_id = e.id
    WHERE de.estatus != 'rechazado' $estadoFilter
    
    UNION ALL
    
    SELECT a.id FROM ausencias a
    JOIN empleados e ON a.empleado_id = e.id
    WHERE a.activo = 1 $estadoFilter
    
    UNION ALL
    
    SELECT v.id FROM vacaciones v
    JOIN empleados e ON v.empleado_id = e.id
    WHERE 1=1 $estadoFilter
    
    UNION ALL
    
    SELECT cm.id FROM cuidados_maternos cm
    JOIN empleados e ON cm.empleado_id = e.id
    WHERE 1=1 $estadoFilter
    
    UNION ALL
    
    SELECT f.id FROM faltas f
    JOIN empleados e ON f.empleado_id = e.id
    WHERE 1=1 $estadoFilter
    
    UNION ALL
    
    SELECT ct.id FROM constancias_tiempo ct
    JOIN empleados e ON ct.empleado_id = e.id
    WHERE 1=1 $estadoFilter
    
    UNION ALL
    
    SELECT a.id FROM asistencia a
    JOIN empleados e ON a.empleado_id = e.id
    WHERE a.tipo_asistencia = 'por_definir'
) as t
$where
";

try {
    // Get total
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();
    
    // Get stats
    $justificados = 0;
    $pendientes = 0;
    $retardos = 0;
    $comisiones = 0;
    $licencias = 0;
    $diasEco = 0;
    $ausencias = 0;
    $vacaciones = 0;
    $cuidados = 0;
    $faltas = 0;
    $constancias = 0;
    $porDefinir = 0;
    $sanciones = 0;
    
    $sqlStats = str_replace("SELECT COUNT(*) as total", "SELECT tipo_registro, COUNT(*) as cnt", $sqlCount) . " GROUP BY tipo_registro";
    $stmtStats = $conn->prepare($sqlStats);
    $stmtStats->execute($params);
    foreach ($stmtStats->fetchAll(PDO::FETCH_ASSOC) as $row) {
        switch($row['tipo_registro']) {
            case 'Retardo': $retardos = $row['cnt']; break;
            case 'Comisión': $comisiones = $row['cnt']; break;
            case 'Licencia Médica': $licencias = $row['cnt']; break;
            case 'Día Económico': $diasEco = $row['cnt']; break;
            case 'Ausencia': $ausencias = $row['cnt']; break;
            case 'Vacaciones': $vacaciones = $row['cnt']; break;
            case 'Cuidados': $cuidados = $row['cnt']; break;
            case 'Falta': $faltas = $row['cnt']; break;
            case 'Constancia': $constancias = $row['cnt']; break;
            case 'Por Definir': $porDefinir = $row['cnt']; break;
        }
    }
    
    // Get data
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $datos = [];
    
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'nombre' => $r['nombre'] ?? '',
            'apellido' => $r['apellido'] ?? '',
            'empleado' => ($r['apellido'] ?? '') . ' ' . ($r['nombre'] ?? ''),
            'rfc' => $r['rfc'] ?? '-',
            'clave_depto' => $r['clave_depto'] ?? '-',
            'puesto' => $r['puesto'] ?? '-',
            'fecha' => $r['fecha'],
            'hora_entrada' => $r['hora_entrada'] ?: '-',
            'hora_salida' => $r['hora_salida'] ?: '-',
            'minutos_retardo' => (int)($r['minutos_retardo'] ?? 0),
            'justificado' => (bool)($r['justificado'] ?? false),
            'tipo_registro' => $r['tipo_registro'],
            'tipo_retraso' => $r['tipo_retraso'] ?? 'normal',
            'estado_validacion' => $r['estado_validacion'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => ($r['jefe_apellido'] ?? '') . ' ' . ($r['jefe_nombre'] ?? ''),
            'justificacion_tipo' => $r['justificacion_tipo'] ?? '',
            'motivo_justificacion' => $r['motivo_justificacion'] ?? '',
            'registro_tipo' => $r['registro_tipo'] ?? 'entrada',
            'comision_lugar' => $r['comision_lugar'] ?? '',
            'comision_fecha_ini' => $r['comision_fecha_ini'] ?? '',
            'comision_fecha_fin' => $r['comision_fecha_fin'] ?? '',
            'licencia_diagnostico' => $r['licencia_diagnostico'] ?? '',
            'licencia_dias' => (int)($r['licencia_dias'] ?? 0),
            'licencia_folio' => $r['licencia_folio'] ?? '',
            'diaeco_dias' => (int)($r['diaeco_dias'] ?? 0),
            'ausencia_tipo' => $r['ausencia_tipo'] ?? '',
            'ausencia_fecha_ini' => $r['ausencia_fecha_ini'] ?? '',
            'ausencia_fecha_fin' => $r['ausencia_fecha_fin'] ?? '',
            'sancion_tipo' => $r['sancion_tipo'] ?? '',
            'sancion_dias' => (int)($r['sancion_dias'] ?? 0),
            'sancion_estatus' => $r['sancion_estatus'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'datos' => $datos,
        'stats' => [
            'total' => (int)$total,
            'justificados' => $justificados,
            'pendientes' => $total - $justificados,
            'retardos' => $retardos,
            'comisiones' => $comisiones,
            'licencias' => $licencias,
            'diasEco' => $diasEco,
            'ausencias' => $ausencias,
            'vacaciones' => $vacaciones,
            'cuidados' => $cuidados,
            'faltas' => $faltas,
            'constancias' => $constancias,
            'porDefinir' => $porDefinir,
            'sanciones' => $sanciones
        ],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>