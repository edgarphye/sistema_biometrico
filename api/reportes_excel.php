<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verify authentication using session database
$pdoCheck = new PDO('mysql:host=localhost;dbname=sistema_biometrico', 'root', 'root');

// Get session ID from cookie
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

// Restore session variables
session_decode($sessionData);
$_SESSION['user_id'] = $_SESSION['user_id'] ?? null;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100; // Reduced from 5000 to 100
$offset = ($page - 1) * $limit;

$estado = $_GET['estado'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$empleado_id = $_GET['empleado_id'] ?? '';
$tipo_registro = $_GET['tipo_registro'] ?? '';

$conn = Database::getInstance()->getConnection();

// Helper function to add filters
function addFilters($sql, &$params, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, $fecha_col) {
    if ($fecha_inicio) {
        $sql .= " AND {$fecha_col} >= ?";
        $params[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $sql .= " AND {$fecha_col} <= ?";
        $params[] = $fecha_fin;
    }
    if ($departamento) {
        $sql .= " AND e.clave_depto = ?";
        $params[] = $departamento;
    }
    if ($empleado_id) {
        $sql .= " AND e.id = ?";
        $params[] = $empleado_id;
    }
    
    return $sql;
}

try {
    $datos = [];
    $total = 0;
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
    
    // RETARDOS
    $paramsRetardos = [];
    $sqlRetardos = "SELECT r.*, 
                    e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                    tj.nombre as tipo_justificacion_nombre,
                    (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                 FROM retardos r
                 JOIN empleados e ON r.empleado_id = e.id
                 LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
                 WHERE r.empleado_id IS NOT NULL";
    $sqlRetardos = addFilters($sqlRetardos, $paramsRetardos, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'r.fecha');
    
    if ($estado === 'justificado') {
        $sqlRetardos .= " AND r.justificado = 1";
    } elseif ($estado === 'pendiente') {
        $sqlRetardos .= " AND r.justificado = 0";
    }
    
    $sqlRetardos .= " ORDER BY e.nombre, e.apellido, r.fecha DESC";
    
    $stmt = $conn->prepare($sqlRetardos);
    $stmt->execute($paramsRetardos);
    
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
            'minutos_retardo' => (int)$r['minutos_retardo'],
            'justificado' => (bool)$r['justificado'],
            'tipo_registro' => 'Retardo',
            'tipo_retraso' => $r['tipo_retraso'] ?? 'normal',
            'estado_validacion' => $r['estado_validacion'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => $r['tipo_justificacion_nombre'] ?? '',
            'motivo_justificacion' => $r['motivo'] ?? ''
        ];
        $retardos++;
        $total++;
    }
    
    // COMISIONES
    $paramsComisiones = [];
    $sqlComisiones = "SELECT c.*, 
                        e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                        (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                     FROM comisiones c
                     JOIN empleados e ON c.empleado_id = e.id
                     WHERE c.estatus != 'rechazada'";
    $sqlComisiones = addFilters($sqlComisiones, $paramsComisiones, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'c.fecha_inicio');
    
    $sqlComisiones .= " ORDER BY e.nombre, e.apellido, c.fecha_inicio DESC";
    
    $stmt = $conn->prepare($sqlComisiones);
    $stmt->execute($paramsComisiones);
    
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
            'fecha' => $r['fecha_inicio'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Comisión',
            'tipo_retraso' => 'comision',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => $r['motivo'] ?? '',
            'comision_lugar' => $r['lugar'] ?? '',
            'comision_fecha_ini' => $r['fecha_inicio'] ?? '',
            'comision_fecha_fin' => $r['fecha_fin'] ?? ''
        ];
        $comisiones++;
        $total++;
    }
    
    // LICENCIAS MÉDICAS
    $paramsLicencias = [];
    $sqlLicencias = "SELECT lm.*, 
                        e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                        (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                     FROM licencias_medicas lm
                     JOIN empleados e ON lm.empleado_id = e.id
                     LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
                     WHERE lm.estatus != 'rechazada'";
    $sqlLicencias = addFilters($sqlLicencias, $paramsLicencias, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'lm.fecha_inicio');
    
    $sqlLicencias .= " ORDER BY e.nombre, e.apellido, lm.fecha_inicio DESC";
    
    $stmt = $conn->prepare($sqlLicencias);
    $stmt->execute($paramsLicencias);
    
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
            'fecha' => $r['fecha_inicio'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Licencia Médica',
            'tipo_retraso' => 'licencia',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => $r['diagnostico'] ?? '',
            'licencia_diagnostico' => $r['diagnostico'] ?? '',
            'licencia_dias' => (int)($r['dias_otorgados'] ?? 0),
            'licencia_folio' => $r['folio'] ?? ''
        ];
        $licencias++;
        $total++;
    }
    
    // DÍAS ECONÓMICOS
    $paramsDiasEco = [];
    $sqlDiasEco = "SELECT de.*, 
                      e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                      (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                   FROM dias_economicos de
                   JOIN empleados e ON de.empleado_id = e.id
                   WHERE de.estatus != 'rechazado'";
    $sqlDiasEco = addFilters($sqlDiasEco, $paramsDiasEco, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'de.fecha');
    
    $sqlDiasEco .= " ORDER BY e.nombre, e.apellido, de.fecha DESC";
    
    $stmt = $conn->prepare($sqlDiasEco);
    $stmt->execute($paramsDiasEco);
    
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
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobado',
            'tipo_registro' => 'Día Económico',
            'tipo_retraso' => 'dia_economico',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => $r['motivo'] ?? '',
            'diaeco_dias' => (int)($r['dias_solicitados'] ?? 0)
        ];
        $diasEco++;
        $total++;
    }
    
    // AUSENCIAS
    $paramsAusencias = [];
    $sqlAusencias = "SELECT a.*, 
                       e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                       (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                    FROM ausencias a
                    JOIN empleados e ON a.empleado_id = e.id
                    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
                    WHERE a.activo = 1";
    $sqlAusencias = addFilters($sqlAusencias, $paramsAusencias, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'a.fecha_inicio');
    
    $sqlAusencias .= " ORDER BY e.nombre, e.apellido, a.fecha_inicio DESC";
    
    $stmt = $conn->prepare($sqlAusencias);
    $stmt->execute($paramsAusencias);
    
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
            'fecha' => $r['fecha_inicio'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Ausencia',
            'tipo_retraso' => 'ausencia',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => $r['tipo_ausencia'] ?? '',
            'ausencia_tipo' => $r['tipo_ausencia'] ?? '',
            'ausencia_fecha_ini' => $r['fecha_inicio'] ?? '',
            'ausencia_fecha_fin' => $r['fecha_fin'] ?? ''
        ];
        $ausencias++;
        $total++;
    }
    
    // VACACIONES
    $paramsVacaciones = [];
    $sqlVacaciones = "SELECT v.*, 
                        e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                        (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                     FROM vacaciones v
                     JOIN empleados e ON v.empleado_id = e.id
                     LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id";
    $sqlVacaciones = addFilters($sqlVacaciones, $paramsVacaciones, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'v.fecha_inicio');
    
    $sqlVacaciones .= " ORDER BY e.nombre, e.apellido, v.fecha_inicio DESC";
    
    $stmt = $conn->prepare($sqlVacaciones);
    $stmt->execute($paramsVacaciones);
    
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
            'fecha' => $r['fecha_inicio'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Vacaciones',
            'tipo_retraso' => 'vacaciones',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => '',
            'sancion_dias' => (int)($r['dias'] ?? 0),
            'sancion_tipo' => 'Vacaciones',
            'ausencia_fecha_ini' => $r['fecha_inicio'] ?? '',
            'ausencia_fecha_fin' => $r['fecha_fin'] ?? ''
        ];
        $vacaciones++;
        $total++;
    }
    
    // CUIDADOS
    $paramsCuidados = [];
    $sqlCuidados = "SELECT cm.*, 
                       e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                       (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                    FROM cuidados_maternos cm
                    JOIN empleados e ON cm.empleado_id = e.id
                    LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id";
    $sqlCuidados = addFilters($sqlCuidados, $paramsCuidados, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'cm.fecha_inicio');
    
    $sqlCuidados .= " ORDER BY e.nombre, e.apellido, cm.fecha_inicio DESC";
    
    $stmt = $conn->prepare($sqlCuidados);
    $stmt->execute($paramsCuidados);
    
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
            'fecha' => $r['fecha_inicio'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobado',
            'tipo_registro' => 'Cuidados',
            'tipo_retraso' => 'cuidados',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => $r['tipo_ausencia'] ?? '',
            'sancion_dias' => (int)($r['dias'] ?? 0),
            'sancion_tipo' => 'Cuidados Maternos/Paternos',
            'ausencia_tipo' => $r['tipo_ausencia'] ?? '',
            'ausencia_fecha_ini' => $r['fecha_inicio'] ?? '',
            'ausencia_fecha_fin' => $r['fecha_fin'] ?? ''
        ];
        $cuidados++;
        $total++;
    }
    
    // FALTAS
    $paramsFaltas = [];
    $sqlFaltas = "SELECT f.*, 
                     e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                     (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                  FROM faltas f
                  JOIN empleados e ON f.empleado_id = e.id
                  LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id";
    $sqlFaltas = addFilters($sqlFaltas, $paramsFaltas, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'f.fecha');
    
    if ($estado === 'justificado') {
        $sqlFaltas .= " AND f.estatus = 'aprobada'";
    } elseif ($estado === 'pendiente') {
        $sqlFaltas .= " AND f.estatus = 'pendiente'";
    }
    
    $sqlFaltas .= " ORDER BY e.nombre, e.apellido, f.fecha DESC";
    
    $stmt = $conn->prepare($sqlFaltas);
    $stmt->execute($paramsFaltas);
    
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
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => (int)($r['minutos_retardo'] ?? 0),
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Falta',
            'tipo_retraso' => $r['tipo_retraso'] ?? 'normal',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => $r['justificacion_tipo'] ?? '',
            'motivo_justificacion' => $r['motivo'] ?? '',
            'sancion_dias' => (int)($r['dias'] ?? 0),
            'sancion_tipo' => 'Falta',
            'sancion_estatus' => $r['estatus'] ?? 'pendiente'
        ];
        $faltas++;
        $total++;
    }
    
    // CONSTANCIAS
    $paramsConstancias = [];
    $sqlConstancias = "SELECT ct.*, 
                          e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                          (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                       FROM constancias_tiempo ct
                       JOIN empleados e ON ct.empleado_id = e.id
                       LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id";
    $sqlConstancias = addFilters($sqlConstancias, $paramsConstancias, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'ct.fecha_inicio');
    
    if ($estado === 'justificado') {
        $sqlConstancias .= " AND ct.estatus = 'aprobada'";
    } elseif ($estado === 'pendiente') {
        $sqlConstancias .= " AND ct.estatus = 'pendiente'";
    }
    
    $sqlConstancias .= " ORDER BY e.nombre, e.apellido, ct.fecha_inicio DESC";
    
    $stmt = $conn->prepare($sqlConstancias);
    $stmt->execute($paramsConstancias);
    
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
            'fecha' => $r['fecha_inicio'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Constancia',
            'tipo_retraso' => 'constancia',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => '',
            'sancion_tipo' => 'Constancia de Tiempo',
            'sancion_estatus' => $r['estatus'] ?? 'pendiente',
            'licencia_folio' => $r['folio'] ?? '',
            'ausencia_fecha_ini' => $r['fecha_inicio'] ?? '',
            'ausencia_fecha_fin' => $r['fecha_fin'] ?? ''
        ];
        $constancias++;
        $total++;
    }
    
    // POR DEFINIR
    $paramsPorDefinir = [];
    $sqlPorDefinir = "SELECT a.*, 
                         e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                         (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                      FROM asistencia a
                      JOIN empleados e ON a.empleado_id = e.id
                      LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id
                      WHERE a.tipo_asistencia = 'por_definir'";
    $sqlPorDefinir = addFilters($sqlPorDefinir, $paramsPorDefinir, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 'a.fecha');
    
    $sqlPorDefinir .= " ORDER BY e.nombre, e.apellido, a.fecha DESC";
    
    $stmt = $conn->prepare($sqlPorDefinir);
    $stmt->execute($paramsPorDefinir);
    
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
            'minutos_retardo' => 0,
            'justificado' => false,
            'tipo_registro' => 'Por Definir',
            'tipo_retraso' => 'por_definir',
            'estado_validacion' => 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => $r['observaciones'] ?? ''
        ];
        $porDefinir++;
        $total++;
    }
    
    // SANCIONES
    $paramsSanciones = [];
    $sqlSanciones = "SELECT s.*, 
                        e.id as emp_id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
                        (SELECT CONCAT(e2.apellido, ' ', e2.nombre) FROM empleados e2 WHERE e2.id = e.jefe_directo_id) as jefe_nombre
                     FROM sanciones s
                     JOIN empleados e ON s.empleado_id = e.id
                     LEFT JOIN empleados e2 ON e.jefe_directo_id = e2.id";
    $sqlSanciones = addFilters($sqlSanciones, $paramsSanciones, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, 's.fecha_inicio');
    
    $sqlSanciones .= " ORDER BY e.nombre, e.apellido, s.fecha_inicio DESC";
    
    $stmt = $conn->prepare($sqlSanciones);
    $stmt->execute($paramsSanciones);
    
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
            'fecha' => $r['fecha_inicio'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'cumplida',
            'tipo_registro' => 'Sanción',
            'tipo_retraso' => $r['tipo_retardo'] ?? 'sancion',
            'estado_validacion' => $r['estatus'] ?? 'pendiente',
            'jefe_directo_id' => $r['jefe_directo_id'] ?? null,
            'jefe_nombre' => $r['jefe_nombre'] ?? 'Sin asignar',
            'justificacion_tipo' => '',
            'motivo_justificacion' => $r['motivo'] ?? '',
            'sancion_dias' => (int)($r['dias'] ?? 0),
            'sancion_tipo' => $r['tipo_sancion'] ?? '',
            'sancion_estatus' => $r['estatus'] ?? 'pendiente',
            'ausencia_fecha_ini' => $r['fecha_inicio'] ?? '',
            'ausencia_fecha_fin' => ($r['dias'] ?? 0) > 0 
                ? date('Y-m-d', strtotime($r['fecha_inicio'] . ' + ' . (int)$r['dias'] . ' days')) 
                : ($r['fecha_inicio'] ?? '')
        ];
        $sanciones++;
        $total++;
    }
    
    // Calcular justificados y pendientes
    $justificados = count(array_filter($datos, fn($d) => $d['justificado'] === true));
    $pendientes = $total - $justificados;
    
    // Filtrar por tipo_registro si se especifica
    if ($tipo_registro) {
        $datos = array_values(array_filter($datos, fn($d) => $d['tipo_registro'] === $tipo_registro));
        $total = count($datos);
    }
    
    // Ordenar por fecha descendente
    usort($datos, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    // Paginación
    $datosPaginados = array_slice($datos, $offset, $limit);
    
    echo json_encode([
        'success' => true,
        'datos' => $datosPaginados,
        'stats' => [
            'total' => $total,
            'justificados' => $justificados,
            'pendientes' => $pendientes,
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
            'total' => $total,
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
