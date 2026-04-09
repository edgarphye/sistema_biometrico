<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5000;
$offset = ($page - 1) * $limit;

$estado = $_GET['estado'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$empleado_id = $_GET['empleado_id'] ?? '';
$tipo_registro = $_GET['tipo_registro'] ?? '';

$conn = Database::getInstance()->getConnection();

function addFilters($sql, $params, $fecha_inicio, $fecha_fin, $departamento, $empleado_id, $fecha_col) {
    $where = "WHERE 1=1";
    $newParams = [];
    
    if ($fecha_inicio) {
        $where .= " AND {$fecha_col} >= ?";
        $newParams[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $where .= " AND {$fecha_col} <= ?";
        $newParams[] = $fecha_fin;
    }
    if ($departamento) {
        $where .= " AND e.clave_depto = ?";
        $newParams[] = $departamento;
    }
    if ($empleado_id) {
        $where .= " AND {$params} = ?";
        $newParams[] = $empleado_id;
    }
    
    return str_replace('WHERE 1=1', $where, $sql);
}

try {
    $datos = [];
    
    $paramsRetardos = [];
    $whereRetardos = "WHERE 1=1";
    if ($fecha_inicio) {
        $whereRetardos .= " AND r.fecha >= ?";
        $paramsRetardos[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereRetardos .= " AND r.fecha <= ?";
        $paramsRetardos[] = $fecha_fin;
    }
    if ($departamento) {
        $whereRetardos .= " AND e.clave_depto = ?";
        $paramsRetardos[] = $departamento;
    }
    if ($empleado_id) {
        $whereRetardos .= " AND r.empleado_id = ?";
        $paramsRetardos[] = $empleado_id;
    }
    if ($estado === 'justificado') {
        $whereRetardos .= " AND r.justificado = 1";
    } elseif ($estado === 'pendiente') {
        $whereRetardos .= " AND r.justificado = 0";
    }
    $sqlRetardos = "SELECT r.id, r.empleado_id, r.fecha, r.hora_entrada, r.hora_salida, r.minutos_retardo, r.justificado,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM retardos r
        JOIN empleados e ON r.empleado_id = e.id
        $whereRetardos
        ORDER BY r.fecha DESC";
    $stmt = $conn->prepare($sqlRetardos);
    $stmt->execute($paramsRetardos);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => $r['hora_entrada'] ?: '-',
            'hora_salida' => $r['hora_salida'] ?: '-',
            'minutos_retardo' => (int)$r['minutos_retardo'],
            'justificado' => (bool)$r['justificado'],
            'tipo_registro' => 'Retardo'
        ];
    }
    
    $paramsComisiones = [];
    $whereComisiones = "WHERE 1=1 AND c.estatus != 'rechazada'";
    if ($fecha_inicio) {
        $whereComisiones .= " AND c.fecha_inicio >= ?";
        $paramsComisiones[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereComisiones .= " AND c.fecha_fin <= ?";
        $paramsComisiones[] = $fecha_fin;
    }
    if ($departamento) {
        $whereComisiones .= " AND e.clave_depto = ?";
        $paramsComisiones[] = $departamento;
    }
    if ($empleado_id) {
        $whereComisiones .= " AND c.empleado_id = ?";
        $paramsComisiones[] = $empleado_id;
    }
    if ($estado === 'justificado') {
        $whereComisiones .= " AND c.estatus = 'aprobada'";
    } elseif ($estado === 'pendiente') {
        $whereComisiones .= " AND c.estatus = 'pendiente'";
    }
    $sqlComisiones = "SELECT c.id, c.empleado_id, c.fecha_inicio as fecha, c.descripcion, c.estatus,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM comisiones c
        JOIN empleados e ON c.empleado_id = e.id
        $whereComisiones
        ORDER BY c.fecha_inicio DESC";
    $stmt = $conn->prepare($sqlComisiones);
    $stmt->execute($paramsComisiones);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Comisión'
        ];
    }
    
    $paramsLicencias = [];
    $whereLicencias = "WHERE 1=1 AND lm.estatus != 'rechazada'";
    if ($fecha_inicio) {
        $whereLicencias .= " AND lm.fecha_inicio >= ?";
        $paramsLicencias[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereLicencias .= " AND lm.fecha_fin <= ?";
        $paramsLicencias[] = $fecha_fin;
    }
    if ($departamento) {
        $whereLicencias .= " AND e.clave_depto = ?";
        $paramsLicencias[] = $departamento;
    }
    if ($empleado_id) {
        $whereLicencias .= " AND lm.empleado_id = ?";
        $paramsLicencias[] = $empleado_id;
    }
    if ($estado === 'justificado') {
        $whereLicencias .= " AND lm.estatus = 'aprobada'";
    } elseif ($estado === 'pendiente') {
        $whereLicencias .= " AND lm.estatus = 'pendiente'";
    }
    $sqlLicencias = "SELECT lm.id, lm.empleado_id, lm.fecha_inicio as fecha, lm.diagnostico, lm.estatus, lm.dias_otorgados,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM licencias_medicas lm
        JOIN empleados e ON lm.empleado_id = e.id
        $whereLicencias
        ORDER BY lm.fecha_inicio DESC";
    $stmt = $conn->prepare($sqlLicencias);
    $stmt->execute($paramsLicencias);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Licencia Médica'
        ];
    }
    
    $paramsDiasEco = [];
    $whereDiasEco = "WHERE 1=1 AND de.estatus != 'rechazado'";
    if ($fecha_inicio) {
        $whereDiasEco .= " AND de.fecha >= ?";
        $paramsDiasEco[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereDiasEco .= " AND de.fecha <= ?";
        $paramsDiasEco[] = $fecha_fin;
    }
    if ($departamento) {
        $whereDiasEco .= " AND e.clave_depto = ?";
        $paramsDiasEco[] = $departamento;
    }
    if ($empleado_id) {
        $whereDiasEco .= " AND de.empleado_id = ?";
        $paramsDiasEco[] = $empleado_id;
    }
    if ($estado === 'justificado') {
        $whereDiasEco .= " AND de.estatus = 'aprobado'";
    } elseif ($estado === 'pendiente') {
        $whereDiasEco .= " AND de.estatus = 'pendiente'";
    }
    $sqlDiasEco = "SELECT de.id, de.empleado_id, de.fecha, de.motivo, de.estatus, de.dias_solicitados,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM dias_economicos de
        JOIN empleados e ON de.empleado_id = e.id
        $whereDiasEco
        ORDER BY de.fecha DESC";
    $stmt = $conn->prepare($sqlDiasEco);
    $stmt->execute($paramsDiasEco);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobado',
            'tipo_registro' => 'Día Económico'
        ];
    }
    
    $paramsAusencias = [];
    $whereAusencias = "WHERE 1=1 AND a.activo = 1";
    if ($fecha_inicio) {
        $whereAusencias .= " AND a.fecha_inicio >= ?";
        $paramsAusencias[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereAusencias .= " AND a.fecha_fin <= ?";
        $paramsAusencias[] = $fecha_fin;
    }
    if ($departamento) {
        $whereAusencias .= " AND e.clave_depto = ?";
        $paramsAusencias[] = $departamento;
    }
    if ($empleado_id) {
        $whereAusencias .= " AND a.empleado_id = ?";
        $paramsAusencias[] = $empleado_id;
    }
    $sqlAusencias = "SELECT a.id, a.empleado_id, a.fecha_inicio as fecha, a.motivo, a.tipo_ausencia,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM ausencias a
        JOIN empleados e ON a.empleado_id = e.id
        $whereAusencias
        ORDER BY a.fecha_inicio DESC";
    $stmt = $conn->prepare($sqlAusencias);
    $stmt->execute($paramsAusencias);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => false,
            'tipo_registro' => 'Ausencia'
        ];
    }
    
    $paramsFaltasAsistencia = [];
    $whereFaltasAsistencia = "WHERE a.tipo_asistencia = 'falta'";
    
    if ($fecha_inicio) {
        $whereFaltasAsistencia .= " AND a.fecha >= ?";
        $paramsFaltasAsistencia[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereFaltasAsistencia .= " AND a.fecha <= ?";
        $paramsFaltasAsistencia[] = $fecha_fin;
    }
    if ($departamento) {
        $whereFaltasAsistencia .= " AND e.clave_depto = ?";
        $paramsFaltasAsistencia[] = $departamento;
    }
    if ($empleado_id) {
        $whereFaltasAsistencia .= " AND a.empleado_id = ?";
        $paramsFaltasAsistencia[] = $empleado_id;
    }
    
    $sqlFaltasAsistencia = "SELECT a.id, a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, a.estado_validacion, a.tipo_asistencia, a.validado_por_jefe, a.tipo_justificacion_id,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM asistencia a
        JOIN empleados e ON a.empleado_id = e.id
        $whereFaltasAsistencia
        ORDER BY a.fecha DESC";
    $stmt = $conn->prepare($sqlFaltasAsistencia);
    $stmt->execute($paramsFaltasAsistencia);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $tieneTipoJustificacion = !empty($r['tipo_justificacion_id']);
        $tieneValidacionJefe = !empty($r['validado_por_jefe']);
        $justificado = $tieneTipoJustificacion || $tieneValidacionJefe;
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => $r['hora_entrada'] ? substr($r['hora_entrada'], 0, 5) : '-',
            'hora_salida' => $r['hora_salida'] ? substr($r['hora_salida'], 0, 5) : '-',
            'minutos_retardo' => 0,
            'justificado' => $justificado,
            'tipo_registro' => 'Falta'
        ];
    }

    // ALL other tipos from asistencia
    $paramsAllAsistencia = [];
    $whereAllAsistencia = "WHERE a.tipo_asistencia NOT IN ('falta')";
    
    if ($fecha_inicio) {
        $whereAllAsistencia .= " AND a.fecha >= ?";
        $paramsAllAsistencia[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereAllAsistencia .= " AND a.fecha <= ?";
        $paramsAllAsistencia[] = $fecha_fin;
    }
    if ($departamento) {
        $whereAllAsistencia .= " AND e.clave_depto = ?";
        $paramsAllAsistencia[] = $departamento;
    }
    if ($empleado_id) {
        $whereAllAsistencia .= " AND a.empleado_id = ?";
        $paramsAllAsistencia[] = $empleado_id;
    }
    
    $tipoRegistroMap = [
        'normal' => 'Normal',
        'por_definir' => 'Por Definir',
        'comision_todo_dia' => 'Comisión',
        'vacaciones' => 'Vacaciones',
        'con_retardo' => 'Retardo',
        'comision_entrada' => 'Comisión',
        'comision_salida' => 'Comisión',
        'dia_economico' => 'Día Económico',
        'licencia_medica' => 'Licencia Médica',
        'constancia_tiempo' => 'Constancia',
        'EYR' => 'EYR',
        'CLIDDA' => 'CLIDDA',
        'cuidados_maternos' => 'Cuidados',
    ];
    
    $sqlAllAsistencia = "SELECT a.id, a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, a.estado_validacion, a.tipo_asistencia, a.validado_por_jefe,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM asistencia a
        JOIN empleados e ON a.empleado_id = e.id
        $whereAllAsistencia
        ORDER BY a.fecha DESC";
    $stmt = $conn->prepare($sqlAllAsistencia);
    $stmt->execute($paramsAllAsistencia);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $justificado = !empty($r['validado_por_jefe']);
        $tipoRegistro = $tipoRegistroMap[$r['tipo_asistencia']] ?? $r['tipo_asistencia'];
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => $r['hora_entrada'] ? substr($r['hora_entrada'], 0, 5) : '-',
            'hora_salida' => $r['hora_salida'] ? substr($r['hora_salida'], 0, 5) : '-',
            'minutos_retardo' => 0,
            'justificado' => $justificado,
            'tipo_registro' => $tipoRegistro
        ];
    }
    
    // Note: EYR and CLIDDA are now included in the all-asistencia query above
    
    $paramsVacaciones = [];
    $whereVacaciones = "WHERE 1=1";
    if ($fecha_inicio) {
        $whereVacaciones .= " AND v.fecha_inicio >= ?";
        $paramsVacaciones[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereVacaciones .= " AND v.fecha_fin <= ?";
        $paramsVacaciones[] = $fecha_fin;
    }
    if ($departamento) {
        $whereVacaciones .= " AND e.clave_depto = ?";
        $paramsVacaciones[] = $departamento;
    }
    if ($empleado_id) {
        $whereVacaciones .= " AND v.empleado_id = ?";
        $paramsVacaciones[] = $empleado_id;
    }
    if ($estado === 'justificado') {
        $whereVacaciones .= " AND v.estatus = 'aprobada'";
    } elseif ($estado === 'pendiente') {
        $whereVacaciones .= " AND v.estatus = 'pendiente'";
    }
    $sqlVacaciones = "SELECT v.id, v.empleado_id, v.fecha_inicio as fecha, v.motivo, v.estatus, v.dias_solicitados,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM vacaciones v
        JOIN empleados e ON v.empleado_id = e.id
        $whereVacaciones
        ORDER BY v.fecha_inicio DESC";
    $stmt = $conn->prepare($sqlVacaciones);
    $stmt->execute($paramsVacaciones);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Vacaciones'
        ];
    }
    
    $paramsCuidados = [];
    $whereCuidados = "WHERE 1=1";
    if ($fecha_inicio) {
        $whereCuidados .= " AND cm.fecha_inicio >= ?";
        $paramsCuidados[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereCuidados .= " AND cm.fecha_fin <= ?";
        $paramsCuidados[] = $fecha_fin;
    }
    if ($departamento) {
        $whereCuidados .= " AND e.clave_depto = ?";
        $paramsCuidados[] = $departamento;
    }
    if ($empleado_id) {
        $whereCuidados .= " AND cm.empleado_id = ?";
        $paramsCuidados[] = $empleado_id;
    }
    if ($estado === 'justificado') {
        $whereCuidados .= " AND cm.estatus = 'aprobada'";
    } elseif ($estado === 'pendiente') {
        $whereCuidados .= " AND cm.estatus = 'pendiente'";
    }
    $sqlCuidados = "SELECT cm.id, cm.empleado_id, cm.fecha_inicio as fecha, cm.motivo, cm.estatus, cm.dias_solicitados,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM cuidados_maternos cm
        JOIN empleados e ON cm.empleado_id = e.id
        $whereCuidados
        ORDER BY cm.fecha_inicio DESC";
    $stmt = $conn->prepare($sqlCuidados);
    $stmt->execute($paramsCuidados);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Cuidados'
        ];
    }
    
    $paramsFaltas = [];
    $whereFaltas = "WHERE 1=1";
    if ($fecha_inicio) {
        $whereFaltas .= " AND f.fecha >= ?";
        $paramsFaltas[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereFaltas .= " AND f.fecha <= ?";
        $paramsFaltas[] = $fecha_fin;
    }
    if ($departamento) {
        $whereFaltas .= " AND e.clave_depto = ?";
        $paramsFaltas[] = $departamento;
    }
    if ($empleado_id) {
        $whereFaltas .= " AND f.empleado_id = ?";
        $paramsFaltas[] = $empleado_id;
    }
    if ($estado === 'justificado') {
        $whereFaltas .= " AND f.estatus = 'aprobada'";
    } elseif ($estado === 'pendiente') {
        $whereFaltas .= " AND f.estatus = 'pendiente'";
    }
    $sqlFaltas = "SELECT f.id, f.empleado_id, f.fecha, f.motivo, f.estatus, f.minutos_retardo,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM faltas f
        JOIN empleados e ON f.empleado_id = e.id
        $whereFaltas
        ORDER BY f.fecha DESC";
    $stmt = $conn->prepare($sqlFaltas);
    $stmt->execute($paramsFaltas);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => (int)$r['minutos_retardo'],
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Falta'
        ];
    }
    
    $paramsConstancias = [];
    $whereConstancias = "WHERE 1=1";
    if ($fecha_inicio) {
        $whereConstancias .= " AND ct.fecha_inicio >= ?";
        $paramsConstancias[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereConstancias .= " AND ct.fecha_fin <= ?";
        $paramsConstancias[] = $fecha_fin;
    }
    if ($departamento) {
        $whereConstancias .= " AND e.clave_depto = ?";
        $paramsConstancias[] = $departamento;
    }
    if ($empleado_id) {
        $whereConstancias .= " AND ct.empleado_id = ?";
        $paramsConstancias[] = $empleado_id;
    }
    if ($estado === 'justificado') {
        $whereConstancias .= " AND ct.estatus = 'aprobada'";
    } elseif ($estado === 'pendiente') {
        $whereConstancias .= " AND ct.estatus = 'pendiente'";
    }
    $sqlConstancias = "SELECT ct.id, ct.empleado_id, ct.fecha_inicio as fecha, ct.motivo, ct.estatus, ct.dias_solicitados,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM constancias_tiempo ct
        JOIN empleados e ON ct.empleado_id = e.id
        $whereConstancias
        ORDER BY ct.fecha_inicio DESC";
    $stmt = $conn->prepare($sqlConstancias);
    $stmt->execute($paramsConstancias);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'aprobada',
            'tipo_registro' => 'Constancia'
        ];
    }
    
    $paramsPorDefinir = [];
    $wherePorDefinir = "WHERE a.tipo_asistencia = 'por_definir'";
    if ($fecha_inicio) {
        $wherePorDefinir .= " AND a.fecha >= ?";
        $paramsPorDefinir[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $wherePorDefinir .= " AND a.fecha <= ?";
        $paramsPorDefinir[] = $fecha_fin;
    }
    if ($departamento) {
        $wherePorDefinir .= " AND e.clave_depto = ?";
        $paramsPorDefinir[] = $departamento;
    }
    if ($empleado_id) {
        $wherePorDefinir .= " AND a.empleado_id = ?";
        $paramsPorDefinir[] = $empleado_id;
    }
    $sqlPorDefinir = "SELECT a.id, a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, a.estado_validacion,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM asistencia a
        JOIN empleados e ON a.empleado_id = e.id
        $wherePorDefinir
        ORDER BY a.fecha DESC";
    $stmt = $conn->prepare($sqlPorDefinir);
    $stmt->execute($paramsPorDefinir);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => $r['hora_entrada'] ?: '-',
            'hora_salida' => $r['hora_salida'] ?: '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estado_validacion'] === 'aprobado',
            'tipo_registro' => 'Por Definir'
        ];
    }
    
    $paramsSanciones = [];
    $whereSanciones = "WHERE 1=1";
    if ($fecha_inicio) {
        $whereSanciones .= " AND s.fecha_inicio >= ?";
        $paramsSanciones[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $whereSanciones .= " AND s.fecha_inicio <= ?";
        $paramsSanciones[] = $fecha_fin;
    }
    if ($departamento) {
        $whereSanciones .= " AND e.clave_depto = ?";
        $paramsSanciones[] = $departamento;
    }
    if ($empleado_id) {
        $whereSanciones .= " AND s.empleado_id = ?";
        $paramsSanciones[] = $empleado_id;
    }
    $sqlSanciones = "SELECT s.id, s.empleado_id, s.fecha_inicio as fecha, s.motivo, s.estatus, s.tipo_sancion, s.dias,
        e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto
        FROM sanciones s
        JOIN empleados e ON s.empleado_id = e.id
        $whereSanciones
        ORDER BY s.fecha_inicio DESC";
    $stmt = $conn->prepare($sqlSanciones);
    $stmt->execute($paramsSanciones);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $datos[] = [
            'id' => $r['id'],
            'empleado_id' => $r['empleado_id'],
            'empleado' => $r['apellido'] . ' ' . $r['nombre'],
            'fecha' => $r['fecha'],
            'hora_entrada' => '-',
            'hora_salida' => '-',
            'minutos_retardo' => 0,
            'justificado' => $r['estatus'] === 'cumplida',
            'tipo_registro' => 'Sanción'
        ];
    }
    
    usort($datos, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    if ($tipo_registro) {
        $datos = array_values(array_filter($datos, fn($d) => $d['tipo_registro'] === $tipo_registro));
    }
    
    $total = count($datos);
    $justificados = count(array_filter($datos, fn($d) => $d['justificado'] === true));
    $pendientes = $total - $justificados;
    
    $retardos = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Retardo'));
    $comisiones = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Comisión'));
    $licencias = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Licencia Médica'));
    $diasEco = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Día Económico'));
    $ausencias = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Ausencia'));
    $vacaciones = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Vacaciones'));
    $cuidados = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Cuidados'));
    $faltas = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Falta'));
    $constancias = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Constancia'));
    $porDefinir = count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Por Definir'));
    
    $datosPaginados = array_slice($datos, $offset, $limit);
    
    echo json_encode([
        'success' => true,
        'tipo' => 'reporte_incidencias',
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
            'porDefinir' => $porDefinir
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