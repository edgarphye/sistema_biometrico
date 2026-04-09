<?php
require_once dirname(__DIR__, 2) . '/config.php';

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);

$esAjax = isset($_GET['ajax']);

if ($esAjax) {
    header('Content-Type: application/json');
    
    $tipoRegistro = $_GET['tipo_registro'] ?? '';
    $departamento = $_GET['departamento'] ?? '';
    $empleadoId = $_GET['empleado_id'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $fechaInicio = $_GET['fecha_inicio'] ?? '';
    $fechaFin = $_GET['fecha_fin'] ?? '';
    
    $whereRetardos = "WHERE 1=1";
    $whereComisiones = "WHERE 1=1 AND c.estatus != 'rechazada'";
    $whereLicencias = "WHERE 1=1 AND lm.estatus != 'rechazada'";
    $whereDiasEco = "WHERE 1=1 AND de.estatus != 'rechazado'";
    $whereAusencias = "WHERE 1=1 AND a.activo = 1";
    $whereVacaciones = "WHERE 1=1";
    $whereCuidados = "WHERE 1=1";
    $whereFaltas = "WHERE 1=1";
    $whereConstancias = "WHERE 1=1";
    
    if ($fechaInicio) {
        $whereRetardos .= " AND r.fecha >= '$fechaInicio'";
        $whereComisiones .= " AND c.fecha_inicio >= '$fechaInicio'";
        $whereLicencias .= " AND lm.fecha_inicio >= '$fechaInicio'";
        $whereDiasEco .= " AND de.fecha >= '$fechaInicio'";
        $whereAusencias .= " AND a.fecha_inicio >= '$fechaInicio'";
        $whereVacaciones .= " AND v.fecha_inicio >= '$fechaInicio'";
        $whereCuidados .= " AND cm.fecha_inicio >= '$fechaInicio'";
        $whereFaltas .= " AND f.fecha >= '$fechaInicio'";
        $whereConstancias .= " AND ct.fecha_inicio >= '$fechaInicio'";
    }
    if ($fechaFin) {
        $whereRetardos .= " AND r.fecha <= '$fechaFin'";
        $whereComisiones .= " AND c.fecha_fin <= '$fechaFin'";
        $whereLicencias .= " AND lm.fecha_fin <= '$fechaFin'";
        $whereDiasEco .= " AND de.fecha <= '$fechaFin'";
        $whereAusencias .= " AND a.fecha_fin <= '$fechaFin'";
        $whereVacaciones .= " AND v.fecha_fin <= '$fechaFin'";
        $whereCuidados .= " AND cm.fecha_fin <= '$fechaFin'";
        $whereFaltas .= " AND f.fecha <= '$fechaFin'";
        $whereConstancias .= " AND ct.fecha_fin <= '$fechaFin'";
    }
    if ($empleadoId) {
        $whereRetardos .= " AND r.empleado_id = $empleadoId";
        $whereComisiones .= " AND c.empleado_id = $empleadoId";
        $whereLicencias .= " AND lm.empleado_id = $empleadoId";
        $whereDiasEco .= " AND de.empleado_id = $empleadoId";
        $whereAusencias .= " AND a.empleado_id = $empleadoId";
        $whereVacaciones .= " AND v.empleado_id = $empleadoId";
        $whereCuidados .= " AND cm.empleado_id = $empleadoId";
        $whereFaltas .= " AND f.empleado_id = $empleadoId";
        $whereConstancias .= " AND ct.empleado_id = $empleadoId";
    }
    if ($departamento) {
        $whereRetardos .= " AND e.clave_depto = '$departamento'";
        $whereComisiones .= " AND e.clave_depto = '$departamento'";
        $whereLicencias .= " AND e.clave_depto = '$departamento'";
        $whereDiasEco .= " AND e.clave_depto = '$departamento'";
        $whereAusencias .= " AND e.clave_depto = '$departamento'";
        $whereVacaciones .= " AND e.clave_depto = '$departamento'";
        $whereCuidados .= " AND e.clave_depto = '$departamento'";
        $whereFaltas .= " AND e.clave_depto = '$departamento'";
        $whereConstancias .= " AND e.clave_depto = '$departamento'";
    }
    
    $sqlRetardos = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto, 
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, r.fecha, r.hora_entrada, r.hora_salida,
        r.tipo_registro as registro_tipo, r.minutos_retardo, r.tipo_retraso, r.justificado, r.estado_validacion, 
        r.motivo_justificacion, tj.nombre as justificacion_tipo, r.fecha_aprobacion,
        'Retardo' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        NULL as diaeco_dias, NULL as ausencia_tipo, NULL as ausencia_fecha_ini, NULL as ausencia_fecha_fin,
        NULL as sancion_tipo, NULL as sancion_dias, NULL as sancion_estatus,
        r.categoria_principal as detalle_extra
        FROM retardos r 
        INNER JOIN empleados e ON r.empleado_id = e.id 
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id 
        LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
        $whereRetardos";
    
    $sqlComisiones = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, c.fecha_inicio as fecha, NULL as hora_entrada, NULL as hora_salida,
        'comision' as registro_tipo, 0 as minutos_retardo, c.tipo_comision as tipo_retraso,
        CASE WHEN c.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
        c.estatus as estado_validacion, c.descripcion as motivo_justificacion,
        'Comisión' as justificacion_tipo, c.fecha_aprobacion,
        'Comisión' as tipo_registro,
        c.descripcion as comision_lugar, c.fecha_inicio as comision_fecha_ini, c.fecha_fin as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        NULL as diaeco_dias, NULL as ausencia_tipo, NULL as ausencia_fecha_ini, NULL as ausencia_fecha_fin,
        NULL as sancion_tipo, NULL as sancion_dias, NULL as sancion_estatus,
        CONCAT('Monto: ', COALESCE(c.monto, 'N/A'), ' | Tipo: ', COALESCE(c.tipo_comision, 'N/A')) as detalle_extra
        FROM comisiones c
        INNER JOIN empleados e ON c.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $whereComisiones";
    
    $sqlLicencias = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, lm.fecha_inicio as fecha, NULL as hora_entrada, NULL as hora_salida,
        'licencia' as registro_tipo, 0 as minutos_retardo, 'licencia_medica' as tipo_retraso,
        CASE WHEN lm.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
        lm.estatus as estado_validacion, lm.diagnostico as motivo_justificacion,
        'Licencia Médica' as justificacion_tipo, lm.created_at as fecha_aprobacion,
        'Licencia Médica' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        lm.diagnostico as licencia_diagnostico, lm.dias_otorgados as licencia_dias, lm.folio as licencia_folio,
        NULL as diaeco_dias, lm.fecha_inicio as ausencia_fecha_ini, lm.fecha_fin as ausencia_fecha_fin,
        NULL as sancion_tipo, NULL as sancion_dias, NULL as sancion_estatus,
        CONCAT(
            'Días Otorgados: ', COALESCE(lm.dias_otorgados, 0),
            ' | Permitidos Full: ', COALESCE(lm.dias_permitidos_full, 0),
            ' | Usados Full: ', COALESCE(lm.dias_full, 0),
            ' | Permitidos Half: ', COALESCE(lm.dias_permitidos_half, 0),
            ' | Usados Half: ', COALESCE(lm.dias_half, 0),
            ' | Sin Sueldo: ', COALESCE(lm.dias_sin_sueldo, 0),
            ' | Tipo: ', COALESCE(lm.tipo_sueldo, 'N/A'),
            ' | Folio: ', COALESCE(lm.folio, 'N/A')
        ) as detalle_extra
        FROM licencias_medicas lm
        INNER JOIN empleados e ON lm.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $whereLicencias";
    
    $sqlDiasEco = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, de.fecha, NULL as hora_entrada, NULL as hora_salida,
        'dia_economico' as registro_tipo, 0 as minutos_retardo, 'dia_economico' as tipo_retraso,
        CASE WHEN de.estatus = 'aprobado' THEN 1 ELSE 0 END as justificado,
        de.estatus as estado_validacion, de.motivo as motivo_justificacion,
        'Día Económico' as justificacion_tipo, de.fecha_aprobacion as fecha_aprobacion,
        'Día Económico' as tipo_registro,
        NULL as comision_lugar, de.fecha_solicitud as comision_fecha_ini, de.fecha_limite_validacion as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        de.dias_solicitados as diaeco_dias, de.modalidad as ausencia_tipo, NULL as ausencia_fecha_ini, NULL as ausencia_fecha_fin,
        NULL as sancion_tipo, NULL as sancion_dias, de.estatus as sancion_estatus,
        CONCAT(
            'Modalidad: ', 
            CASE de.modalidad WHEN 'A' THEN 'A (3 días - espera 1 mes)' WHEN 'B' THEN 'B (2 días - espera 15 días)' WHEN 'C' THEN 'C (1 día - espera 1 semana)' ELSE COALESCE(de.modalidad, 'N/A') END,
            ' | Periodo: ', DATE_FORMAT(de.periodo_inicio, '%b %Y'), ' - ', DATE_FORMAT(de.periodo_fin, '%b %Y'),
            ' | Usados: ', COALESCE(de.dias_usados_periodo, 0), '/9',
            ' | Restantes: ', COALESCE(de.dias_restantes_periodo, 9),
            CASE 
                WHEN de.cumple_antiguedad = 0 THEN ' | ⚠️ Menos de 6 meses antigüedad'
                WHEN de.es_plaza_confianza = 1 THEN ' | ⚠️ Plaza de confianza'
                WHEN de.validacion_dia_semana LIKE '%No permitido%' THEN ' | ⚠️ Día no válido'
                ELSE ''
            END
        ) as detalle_extra
        FROM dias_economicos de
        INNER JOIN empleados e ON de.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $whereDiasEco";
    
    $sqlAusencias = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, a.fecha_inicio as fecha, NULL as hora_entrada, NULL as hora_salida,
        'ausencia' as registro_tipo, 0 as minutos_retardo, a.tipo as tipo_retraso, 0 as justificado,
        'pendiente' as estado_validacion, a.motivo as motivo_justificacion,
        'Ausencia' as justificacion_tipo, NULL as fecha_aprobacion,
        'Ausencia' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        NULL as diaeco_dias, a.tipo_ausencia as ausencia_tipo, a.fecha_inicio as ausencia_fecha_ini, a.fecha_fin as ausencia_fecha_fin,
        NULL as sancion_tipo, NULL as sancion_dias, NULL as sancion_estatus,
        CONCAT('Tipo: ', COALESCE(a.tipo_ausencia, 'N/A'), ' | Fecha Fin: ', COALESCE(a.fecha_fin, 'N/A')) as detalle_extra
        FROM ausencias a
        INNER JOIN empleados e ON a.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $whereAusencias";
    
    $sqlVacaciones = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, v.fecha_inicio as fecha, NULL as hora_entrada, NULL as hora_salida,
        'vacacion' as registro_tipo, 0 as minutos_retardo, 'vacacion' as tipo_retraso,
        CASE WHEN v.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
        v.estatus as estado_validacion, v.motivo as motivo_justificacion,
        'Vacaciones' as justificacion_tipo, v.fecha_aprobacion,
        'Vacaciones' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        NULL as diaeco_dias, NULL as ausencia_tipo, v.fecha_inicio as ausencia_fecha_ini, v.fecha_fin as ausencia_fecha_fin,
        NULL as sancion_tipo, v.dias_solicitados as sancion_dias, NULL as sancion_estatus,
        CONCAT('Días Solicitados: ', COALESCE(v.dias_solicitados, 0), ' | Fecha Fin: ', COALESCE(v.fecha_fin, 'N/A')) as detalle_extra
        FROM vacaciones v
        INNER JOIN empleados e ON v.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $whereVacaciones";
    
    $sqlCuidados = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, cm.fecha_inicio as fecha, NULL as hora_entrada, NULL as hora_salida,
        'cuidado' as registro_tipo, 0 as minutos_retardo, 'cuidado_materno' as tipo_retraso,
        CASE WHEN cm.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
        cm.estatus as estado_validacion, cm.motivo as motivo_justificacion,
        'Cuidados Maternos/Paternos' as justificacion_tipo, cm.fecha_aprobacion,
        'Cuidados' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        NULL as diaeco_dias, cm.tipo_cuidado as ausencia_tipo, cm.fecha_inicio as ausencia_fecha_ini, cm.fecha_fin as ausencia_fecha_fin,
        NULL as sancion_tipo, cm.dias_solicitados as sancion_dias, cm.parentesco as sancion_estatus,
        CONCAT('Tipo Cuidado: ', COALESCE(cm.tipo_cuidado, 'N/A'), ' | Parentesco: ', COALESCE(cm.parentesco, 'N/A'), ' | Días: ', COALESCE(cm.dias_solicitados, 0)) as detalle_extra
        FROM cuidados_maternos cm
        INNER JOIN empleados e ON cm.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $whereCuidados";
    
    $sqlFaltas = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, f.fecha, NULL as hora_entrada, NULL as hora_salida,
        'falta' as registro_tipo, f.minutos_retardo, f.tipo_falta as tipo_retraso,
        CASE WHEN f.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
        f.estatus as estado_validacion, f.motivo as motivo_justificacion,
        'Falta' as justificacion_tipo, f.fecha_aprobacion,
        'Falta' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        NULL as diaeco_dias, f.tipo_falta as ausencia_tipo, f.fecha as ausencia_fecha_ini, f.fecha as ausencia_fecha_fin,
        f.tipo_falta as sancion_tipo, f.minutos_retardo as sancion_dias, f.evidencia as sancion_estatus,
        CONCAT('Tipo Falta: ', COALESCE(f.tipo_falta, 'N/A'), ' | Minutos: ', COALESCE(f.minutos_retardo, 0), ' | Evidencia: ', COALESCE(f.evidencia, 'N/A')) as detalle_extra
        FROM faltas f
        INNER JOIN empleados e ON f.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $whereFaltas";
    
    $sqlConstancias = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, ct.fecha_inicio as fecha, NULL as hora_entrada, NULL as hora_salida,
        'constancia' as registro_tipo, 0 as minutos_retardo, 'constancia_tiempo' as tipo_retraso,
        CASE WHEN ct.estatus = 'aprobada' THEN 1 ELSE 0 END as justificado,
        ct.estatus as estado_validacion, ct.motivo as motivo_justificacion,
        'Constancia de Tiempo' as justificacion_tipo, ct.fecha_aprobacion,
        'Constancia' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, ct.folio_constancia as licencia_folio,
        ct.dias_solicitados as diaeco_dias, ct.tipo_constancia as ausencia_tipo, ct.fecha_inicio as ausencia_fecha_ini, ct.fecha_fin as ausencia_fecha_fin,
        ct.institucion_emisora as sancion_tipo, ct.dias_solicitados as sancion_dias, ct.estatus as sancion_estatus,
        CONCAT('Folio: ', COALESCE(ct.folio_constancia, 'N/A'), ' | Institución: ', COALESCE(ct.institucion_emisora, 'N/A'), ' | Tipo: ', COALESCE(ct.tipo_constancia, 'N/A')) as detalle_extra
        FROM constancias_tiempo ct
        INNER JOIN empleados e ON ct.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $whereConstancias";
    
    $wherePorDefinir = "WHERE a.tipo_asistencia = 'por_definir'";
    $whereSanciones = "WHERE 1=1";
    
    if ($fechaInicio) {
        $wherePorDefinir .= " AND a.fecha >= '$fechaInicio'";
        $whereSanciones .= " AND s.fecha_inicio >= '$fechaInicio'";
    }
    if ($fechaFin) {
        $wherePorDefinir .= " AND a.fecha <= '$fechaFin'";
        $whereSanciones .= " AND s.fecha_inicio <= '$fechaFin'";
    }
    if ($empleadoId) {
        $wherePorDefinir .= " AND a.empleado_id = $empleadoId";
        $whereSanciones .= " AND s.empleado_id = $empleadoId";
    }
    if ($departamento) {
        $wherePorDefinir .= " AND e.clave_depto = '$departamento'";
        $whereSanciones .= " AND e.clave_depto = '$departamento'";
    }
    
    $sqlPorDefinir = "SELECT DISTINCT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, a.fecha, a.hora_entrada, a.hora_salida,
        'por_definir' as registro_tipo, 0 as minutos_retardo, 'por_definir' as tipo_retraso,
        0 as justificado, a.estado_validacion, a.estado_validacion as motivo_justificacion,
        'Por Definir' as justificacion_tipo, NULL as fecha_aprobacion,
        'Por Definir' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        NULL as diaeco_dias, NULL as ausencia_tipo, NULL as ausencia_fecha_ini, NULL as ausencia_fecha_fin,
        NULL as sancion_tipo, NULL as sancion_dias, NULL as sancion_estatus,
        CONCAT('Hora Entrada: ', COALESCE(a.hora_entrada, 'N/A'), ' | Hora Salida: ', COALESCE(a.hora_salida, 'N/A')) as detalle_extra
        FROM asistencia a
        INNER JOIN empleados e ON a.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        $wherePorDefinir";
    
    $sqlSanciones = "SELECT e.id, e.nombre, e.apellido, e.rfc, e.clave_depto, e.puesto,
        CONCAT(je.nombre, ' ', je.apellido) as nombre_jefe, 
        s.fecha_inicio as fecha, 
        COALESCE(r.hora_entrada, a.hora_entrada) as hora_entrada, 
        COALESCE(r.hora_salida, a.hora_salida) as hora_salida,
        'sancion' as registro_tipo, 
        COALESCE(r.minutos_retardo, 0) as minutos_retardo, 
        s.tipo_retardo as tipo_retraso,
        0 as justificado, s.estatus as estado_validacion, s.motivo as motivo_justificacion,
        'Sanción' as justificacion_tipo, NULL as fecha_aprobacion,
        'Sanción' as tipo_registro,
        NULL as comision_lugar, NULL as comision_fecha_ini, NULL as comision_fecha_fin,
        NULL as licencia_diagnostico, NULL as licencia_dias, NULL as licencia_folio,
        NULL as diaeco_dias, s.tipo_sancion as ausencia_tipo, s.fecha_inicio as ausencia_fecha_ini, 
        CASE WHEN s.dias > 0 THEN DATE_ADD(s.fecha_inicio, INTERVAL s.dias DAY) ELSE s.fecha_inicio END as ausencia_fecha_fin,
        s.tipo_sancion as sancion_tipo, s.dias as sancion_dias, s.estatus as sancion_estatus,
        CONCAT('Tipo Sanción: ', COALESCE(s.tipo_sancion, 'N/A'), ' | Días: ', COALESCE(s.dias, 0), ' | Origen: ', COALESCE(s.tipo_retardo, 'N/A'), ' | Minutos Retardo: ', COALESCE(r.minutos_retardo, 0)) as detalle_extra
        FROM sanciones s
        INNER JOIN empleados e ON s.empleado_id = e.id
        LEFT JOIN empleados je ON e.jefe_directo_id = je.id
        LEFT JOIN retardos r ON r.empleado_id = s.empleado_id AND r.fecha = s.fecha_inicio
        LEFT JOIN asistencia a ON a.empleado_id = s.empleado_id AND a.fecha = s.fecha_inicio
        $whereSanciones";
    
    $datos = array_merge(
        $pdo->query($sqlRetardos)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlComisiones)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlLicencias)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlDiasEco)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlAusencias)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlVacaciones)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlCuidados)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlFaltas)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlConstancias)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlPorDefinir)->fetchAll(PDO::FETCH_ASSOC),
        $pdo->query($sqlSanciones)->fetchAll(PDO::FETCH_ASSOC)
    );
    
    usort($datos, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    if ($tipoRegistro) {
        $datos = array_filter($datos, fn($d) => $d['tipo_registro'] === $tipoRegistro);
    }
    
    if ($estado === 'justificado') {
        $datos = array_filter($datos, fn($d) => $d['justificado'] == 1);
    } elseif ($estado === 'pendiente') {
        $datos = array_filter($datos, fn($d) => $d['justificado'] == 0);
    }
    
    $datos = array_values($datos);
    
    $stats = [
        'total' => count($datos),
        'justificados' => count(array_filter($datos, fn($d) => $d['justificado'] == 1)),
        'pendientes' => count(array_filter($datos, fn($d) => $d['justificado'] == 0)),
        'retardos' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Retardo')),
        'comisiones' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Comisión')),
        'licencias' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Licencia Médica')),
        'diasEco' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Día Económico')),
        'ausencias' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Ausencia')),
        'vacaciones' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Vacaciones')),
        'cuidados' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Cuidados')),
        'faltas' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Falta')),
        'constancias' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Constancia')),
        'porDefinir' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Por Definir')),
        'sanciones' => count(array_filter($datos, fn($d) => $d['tipo_registro'] === 'Sanción'))
    ];
    
    echo json_encode(['datos' => $datos, 'stats' => $stats]);
    exit;
}

$deptos = $pdo->query("SELECT DISTINCT clave_depto FROM empleados WHERE clave_depto IS NOT NULL ORDER BY clave_depto")->fetchAll(PDO::FETCH_COLUMN);
$empleados = $pdo->query("SELECT id, nombre, apellido FROM empleados ORDER BY apellido, nombre LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
$fechaIniDefault = date('Y-m-01');
$fechaFinDefault = date('Y-m-t');
?>
<style>
    .report-container { padding: 0; }
    .report-header {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
        color: white; padding: 1.5rem; border-radius: 16px; margin-bottom: 1.5rem;
    }
    .report-header h4 { color: white; }
    .report-header p { color: rgba(255,255,255,0.85); }
    .btn-generar {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
        color: white; border: none; padding: 0.75rem 2rem;
        border-radius: 10px; font-weight: 600; font-size: 1rem;
        transition: all 0.3s ease;
    }
    .btn-generar:hover {
        background: linear-gradient(135deg, var(--pantone-vino-oscuro) 0%, var(--pantone-vino) 100%);
        color: white; transform: translateY(-2px); box-shadow: 0 5px 20px rgba(159, 34, 65, 0.3);
    }
    .btn-excel {
        background: linear-gradient(135deg, var(--pantone-verde) 0%, var(--pantone-verde-oscuro) 100%);
        color: white; border: none; padding: 0.75rem 2rem;
        border-radius: 10px; font-weight: 600; font-size: 1rem;
    }
    .btn-excel:hover { background: linear-gradient(135deg, var(--pantone-verde-oscuro) 0%, var(--pantone-verde) 100%); color: white; }
    .table-container { max-height: 500px; overflow-y: auto; }
    .badge-retardo { background-color: var(--pantone-dorado-oscuro); color: white; }
    .badge-comision { background-color: #17a2b8; color: white; }
    .badge-licencia { background-color: #6f42c1; color: white; }
    .badge-sancion { background-color: var(--pantone-vino); color: white; }
    .badge-justificado { background-color: var(--pantone-verde); color: white; }
    .badge-pendiente { background-color: var(--pantone-dorado-oscuro); color: white; }
    .badge-vacaciones { background-color: #00897B; color: white; }
    .badge-cuidados { background-color: #EC407A; color: white; }
    .badge-falta { background-color: #E53935; color: white; }
    .badge-constancia { background-color: #5E35B1; color: white; }
    .badge-secondary { background-color: #78909C; color: white; }
    .depto-badge {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
        color: white; padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.65rem;
    }
    .stat-card {
        background: white; padding: 1rem; border-radius: 10px; text-align: center;
        border-left: 4px solid var(--pantone-vino);
    }
    .stat-card h3 { color: var(--pantone-vino); margin: 0; font-weight: bold; }
    .stat-card p { margin: 3px 0 0 0; color: #666; font-size: 0.8rem; }
    .stat-verde { border-left-color: var(--pantone-verde); }
    .stat-verde h3 { color: var(--pantone-verde); }
    .hidden { display: none; }
</style>

<div class="report-container">
    <div class="report-header">
        <h4 class="mb-1"><i class="fas fa-file-excel me-3"></i>Reportes Excel - Control de Incidencias</h4>
        <p class="mb-0"><i class="fas fa-info-circle me-2"></i>Seleccione los filtros y genere el reporte</p>
    </div>

    <form id="reporteForm">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros del Reporte</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-tag me-1"></i>Tipo de Registro:</label>
                        <select class="form-select" name="tipo_registro" id="tipoRegistro">
                            <option value="">Todos</option>
                            <option value="Retardo">Retardos</option>
                            <option value="Comisión">Comisiones</option>
                            <option value="Licencia Médica">Licencias Médicas</option>
                            <option value="Día Económico">Días Económicos</option>
                            <option value="Ausencia">Ausencias</option>
                            <option value="Vacaciones">Vacaciones</option>
                            <option value="Cuidados">Cuidados Maternos/Paternos</option>
                            <option value="Falta">Faltas</option>
                            <option value="Constancia">Constancias de Tiempo</option>
                            <option value="Por Definir">Por Definir</option>
                            <option value="Sanción">Sanciones</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-building me-1"></i>Departamento:</label>
                        <select class="form-select" name="departamento" id="departamento">
                            <option value="">Todos</option>
                            <?php foreach ($deptos as $depto): ?>
                                <option value="<?php echo htmlspecialchars($depto); ?>"><?php echo htmlspecialchars($depto); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-user me-1"></i>Empleado:</label>
                        <select class="form-select" name="empleado_id" id="empleadoId">
                            <option value="">Todos</option>
                            <?php foreach ($empleados as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['apellido'] . ' ' . $emp['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-check-circle me-1"></i>Estado:</label>
                        <select class="form-select" name="estado" id="estado">
                            <option value="">Todos</option>
                            <option value="justificado">Justificados</option>
                            <option value="pendiente">Pendientes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Fecha Inicio:</label>
                        <input type="date" class="form-control" name="fecha_inicio" id="fechaInicio" value="<?php echo $fechaIniDefault; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Fecha Fin:</label>
                        <input type="date" class="form-control" name="fecha_fin" id="fechaFin" value="<?php echo $fechaFinDefault; ?>">
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-generar" id="btnGenerar" onclick="generarReporte()">
                            <i class="fas fa-search me-2"></i>Generar Reporte
                        </button>
                        <button type="button" class="btn btn-excel" id="btnExportar" onclick="exportarExcel()" disabled>
                            <i class="fas fa-file-excel me-2"></i>Exportar Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div id="resultadosContainer" class="hidden">
        <div class="row g-3 mb-4" id="statsRow"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Resultados <span id="contador" class="badge bg-light text-dark ms-2">0</span></h5>
                <span>Mostrando: <span id="mostrando">0</span></span>
            </div>
            <div class="table-container">
                <table class="table table-hover" id="tablaReporte">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Empleado</th>
                            <th>RFC</th>
                            <th>Depto</th>
                            <th>Puesto</th>
                            <th>Fecha</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th>Tipo</th>
                            <th>Subtipo</th>
                            <th>Min.</th>
                            <th>Just.</th>
                            <th>Tipo Justificación</th>
                            <th>Motivo/Observaciones</th>
                            <th>Detalles Específicos</th>
                            <th>Estado</th>
                            <th>Jefe</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyResultados"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="mensajeInicial" class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-file-invoice fa-4x mb-4" style="color: var(--pantone-dorado-oscuro);"></i>
            <h5>Seleccione los filtros y haga clic en "Generar Reporte"</h5>
            <p class="text-muted">El reporte incluirá: Retardos, Comisiones, Licencias Médicas, Días Económicos, Ausencias, Vacaciones, Cuidados Maternos/Paternos, Faltas y Constancias de Tiempo</p>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    let datosReporte = [];
    
    function generarReporte() {
        const btn = document.getElementById('btnGenerar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando...';
        
        const params = new URLSearchParams({
            ajax: '1',
            tipo_registro: document.getElementById('tipoRegistro').value,
            departamento: document.getElementById('departamento').value,
            empleado_id: document.getElementById('empleadoId').value,
            estado: document.getElementById('estado').value,
            fecha_inicio: document.getElementById('fechaInicio').value,
            fecha_fin: document.getElementById('fechaFin').value
        });
        
        document.getElementById('mensajeInicial').classList.add('hidden');
        
        // Use the new API endpoint
        fetch('/sistema_biometrico/api/reportes_excel.php?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                datosReporte = data.datos;
                mostrarResultados(data);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search me-2"></i>Generar Reporte';
            })
            .catch(err => {
                console.error('Error fetch:', err);
                alert('Error al generar el reporte: ' + err.message);
                alert('Error al generar el reporte: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search me-2"></i>Generar Reporte';
            });
    }
    
    function mostrarResultados(data) {
        document.getElementById('resultadosContainer').classList.remove('hidden');
        document.getElementById('btnExportar').disabled = false;
        
        const stats = data.stats;
        document.getElementById('statsRow').innerHTML = `
            <div class="col-md-2"><div class="stat-card"><h3>${stats.total}</h3><p>Total</p></div></div>
            <div class="col-md-2"><div class="stat-card stat-verde"><h3>${stats.justificados}</h3><p>Justificados</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.pendientes}</h3><p>Pendientes</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.retardos}</h3><p>Retardos</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.comisiones}</h3><p>Comisiones</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.licencias}</h3><p>Licencias</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.diasEco}</h3><p>Días Eco.</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.ausencias}</h3><p>Ausencias</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.vacaciones || 0}</h3><p>Vacaciones</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.cuidados || 0}</h3><p>Cuidados</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.faltas || 0}</h3><p>Faltas</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.constancias || 0}</h3><p>Constancias</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.porDefinir || 0}</h3><p>Por Definir</p></div></div>
            <div class="col-md-2"><div class="stat-card"><h3>${stats.sanciones || 0}</h3><p>Sanciones</p></div></div>
        `;
        
        const tbody = document.getElementById('tbodyResultados');
        tbody.innerHTML = '';
        
        if (data.datos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="17" class="text-center py-4 text-muted">No se encontraron registros</td></tr>';
            document.getElementById('contador').textContent = 0;
            document.getElementById('mostrando').textContent = 0;
            return;
        }
        
        data.datos.forEach((row, idx) => {
            const tipoBadge = getTipoBadge(row.tipo_registro);
            const justBadge = row.justificado == 1 ? 'badge-justificado' : 'badge-pendiente';
            const minClass = (row.minutos_retardo || 0) > 30 ? 'text-danger' : ((row.minutos_retardo || 0) > 0 ? 'text-warning' : '');
            const detalles = getDetalles(row);
            const horaEntrada = row.hora_entrada ? row.hora_entrada.substring(0,5) : '-';
            const horaSalida = row.hora_salida ? row.hora_salida.substring(0,5) : '-';
            const registroTipo = getRegistroTipoLabel(row.registro_tipo);
            
            tbody.innerHTML += `
                <tr>
                    <td>${idx + 1}</td>
                    <td><strong>${row.apellido} ${row.nombre}</strong></td>
                    <td><code>${row.rfc || 'N/A'}</code></td>
                    <td><span class="depto-badge">${row.clave_depto || 'N/A'}</span></td>
                    <td><small>${row.puesto || 'N/A'}</small></td>
                    <td>${formatDate(row.fecha)}</td>
                    <td class="fw-bold">${horaEntrada}</td>
                    <td class="fw-bold">${horaSalida}</td>
                    <td><span class="badge ${tipoBadge}">${row.tipo_registro}</span><br><small class="text-muted">${registroTipo}</small></td>
                    <td><small>${(row.tipo_retraso || 'N/A').replace(/_/g, ' ')}</small></td>
                    <td class="text-center fw-bold ${minClass}">${row.minutos_retardo > 0 ? row.minutos_retardo : '-'}</td>
                    <td><span class="badge ${justBadge}">${row.justificado == 1 ? 'Sí' : 'No'}</span></td>
                    <td><small>${row.justificacion_tipo || '-'}</small></td>
                    <td><small>${row.motivo_justificacion || '-'}</small></td>
                    <td><small>${detalles}</small></td>
                    <td><span class="badge ${getEstadoBadge(row.estado_validacion)}">${formatEstado(row.estado_validacion)}</span></td>
                    <td><small>${row.nombre_jefe || 'Sin asignar'}</small></td>
                </tr>
            `;
        });
        
        document.getElementById('contador').textContent = data.datos.length;
        document.getElementById('mostrando').textContent = data.datos.length;
    }
    
    function getTipoBadge(tipo) {
        const badges = {
            'Retardo': 'badge-retardo',
            'Comisión': 'badge-comision',
            'Licencia Médica': 'badge-licencia',
            'Día Económico': 'badge-comision',
            'Ausencia': 'badge-licencia',
            'Vacaciones': 'badge-vacaciones',
            'Cuidados': 'badge-cuidados',
            'Falta': 'badge-falta',
            'Constancia': 'badge-constancia',
            'Por Definir': 'badge-pendiente',
            'Sanción': 'badge-sancion'
        };
        return badges[tipo] || 'badge-pendiente';
    }
    
    function getRegistroTipoLabel(tipo) {
        const labels = {
            'entrada': 'Solo Entrada',
            'salida': 'Solo Salida',
            'ambos': 'Entrada y Salida',
            'comision': 'Comisión',
            'licencia': 'Licencia',
            'dia_economico': 'Día Económico',
            'ausencia': 'Ausencia',
            'vacacion': 'Vacaciones',
            'cuidado': 'Cuidados Maternos/Paternos',
            'falta': 'Falta',
            'constancia': 'Constancia de Tiempo'
        };
        return labels[tipo] || tipo || '-';
    }
    
    function getDetalles(row) {
        if (row.tipo_registro === 'Comisión') {
            return `Lugar: ${row.comision_lugar || '-'} | Fechas: ${formatDate(row.comision_fecha_ini)} - ${formatDate(row.comision_fecha_fin)}`;
        }
        if (row.tipo_registro === 'Licencia Médica') {
            return `Diagnóstico: ${row.licencia_diagnostico || '-'} | Días: ${row.licencia_dias || '-'} | Folio: ${row.licencia_folio || '-'}`;
        }
        if (row.tipo_registro === 'Día Económico') {
            return `Días Solicitados: ${row.diaeco_dias || '-'}`;
        }
        if (row.tipo_registro === 'Ausencia') {
            return `Tipo: ${row.ausencia_tipo || '-'} | Fechas: ${formatDate(row.ausencia_fecha_ini)} - ${formatDate(row.ausencia_fecha_fin)}`;
        }
        if (row.tipo_registro === 'Vacaciones') {
            return `Días: ${row.sancion_dias || '-'} | Fechas: ${formatDate(row.ausencia_fecha_ini)} - ${formatDate(row.ausencia_fecha_fin)}`;
        }
        if (row.tipo_registro === 'Cuidados') {
            return `Tipo: ${row.ausencia_tipo || '-'} | Parentesco: ${row.sancion_estatus || '-'} | Fechas: ${formatDate(row.ausencia_fecha_ini)} - ${formatDate(row.ausencia_fecha_fin)}`;
        }
        if (row.tipo_registro === 'Falta') {
            return `Tipo: ${row.tipo_retraso || '-'} | Minutos: ${row.minutos_retardo || '-'} | Evidencia: ${row.sancion_estatus || '-'}`;
        }
        if (row.tipo_registro === 'Constancia') {
            return `Tipo: ${row.ausencia_tipo || '-'} | Folio: ${row.licencia_folio || '-'} | Institucion: ${row.sancion_tipo || '-'} | Fechas: ${formatDate(row.ausencia_fecha_ini)} - ${formatDate(row.ausencia_fecha_fin)}`;
        }
        if (row.tipo_registro === 'Por Definir') {
            return `Observaciones: ${row.motivo_justificacion || 'Sin observación'}`;
        }
        if (row.tipo_registro === 'Sanción') {
            return `Tipo Sanción: ${row.sancion_tipo || '-'} | Días: ${row.sancion_dias || '-'} | Fechas: ${formatDate(row.ausencia_fecha_ini)} - ${formatDate(row.ausencia_fecha_fin)}`;
        }
        return '-';
    }
    
    function formatDate(date) {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('es-MX');
    }
    
    function formatEstado(estado) {
        if (!estado) return 'Pendiente';
        const e = estado.toLowerCase();
        if (e.includes('aprobad')) return 'Aprobado';
        if (e.includes('rechazad')) return 'Rechazado';
        if (e.includes('activa')) return 'Activa';
        if (e.includes('cumplida')) return 'Cumplida';
        if (e.includes('pendiente por')) return 'Pendiente por Jefe';
        return 'Pendiente';
    }
    
    function getEstadoBadge(estado) {
        if (!estado) return 'badge-secondary';
        const e = estado.toLowerCase();
        if (e.includes('aprobad')) return 'badge-justificado';
        if (e.includes('rechazad')) return 'badge-sancion';
        if (e.includes('pendiente por')) return 'badge-pendiente';
        return 'badge-secondary';
    }
    
    async function exportarExcel() {
        if (datosReporte.length === 0) {
            alert('No hay datos para exportar');
            return;
        }
        
        const stats = {
            total: datosReporte.length,
            justificados: datosReporte.filter(r => r.justificado == 1).length,
            pendientes: datosReporte.filter(r => r.justificado == 0).length,
            retardos: datosReporte.filter(r => r.tipo_registro === 'Retardo').length,
            comisiones: datosReporte.filter(r => r.tipo_registro === 'Comisión').length,
            licencias: datosReporte.filter(r => r.tipo_registro === 'Licencia Médica').length,
            diasEco: datosReporte.filter(r => r.tipo_registro === 'Día Económico').length,
            ausencias: datosReporte.filter(r => r.tipo_registro === 'Ausencia').length,
            vacaciones: datosReporte.filter(r => r.tipo_registro === 'Vacaciones').length,
            cuidados: datosReporte.filter(r => r.tipo_registro === 'Cuidados').length,
            faltas: datosReporte.filter(r => r.tipo_registro === 'Falta').length,
            constancias: datosReporte.filter(r => r.tipo_registro === 'Constancia').length,
            porDefinir: datosReporte.filter(r => r.tipo_registro === 'Por Definir').length,
            sanciones: datosReporte.filter(r => r.tipo_registro === 'Sanción').length
        };
        
        const fechaGeneracion = new Date().toLocaleDateString('es-MX', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        
        const wb = new ExcelJS.Workbook();
        
        // ===== HOJA ÚNICA CON TODA LA INFORMACIÓN =====
        const ws = wb.addWorksheet('Reporte Incidencias');
        
        // Definir columnas
        ws.columns = [
            { width: 8 },   // #
            { width: 28 }, // Empleado
            { width: 14 }, // RFC
            { width: 12 }, // Depto
            { width: 22 }, // Puesto
            { width: 12 }, // Fecha
            { width: 10 }, // Hora Ent
            { width: 10 }, // Hora Sal
            { width: 18 }, // Tipo Registro
            { width: 20 }, // Detalle Registro
            { width: 18 }, // Subtipo
            { width: 8 },  // Min
            { width: 10 }, // Just
            { width: 20 }, // Tipo Justificación
            { width: 35 }, // Motivo/Observaciones
            { width: 40 }, // Detalles Específicos
            { width: 16 }, // Estado
            { width: 22 }  // Jefe Directo
        ];
        
        // FILA 1: Título principal
        ws.mergeCells('A1:R1');
        ws.getCell('A1').value = 'REPORTE DE INCIDENCIAS - CONTROL DE ASISTENCIA Y JUSTIFICACIONES';
        ws.getCell('A1').style = {
            font: { bold: true, size: 18, color: 'FFFFFF' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: '9F2241' },
            alignment: { horizontal: 'center', vertical: 'middle' }
        };
        ws.getRow(1).height = 35;
        
        // FILA 2: Fecha de generación
        ws.mergeCells('A2:R2');
        ws.getCell('A2').value = 'Fecha de Generación: ' + fechaGeneracion;
        ws.getCell('A2').style = {
            font: { size: 11, italic: true, color: '555555' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: 'F5F5F5' },
            alignment: { horizontal: 'center' }
        };
        
        // FILA 3: Resumen general
        ws.mergeCells('A3:R3');
        ws.getCell('A3').value = '📊 RESUMEN: Total: ' + stats.total + ' | ✓ Justificados: ' + stats.justificados + ' | ⏳ Pendientes: ' + stats.pendientes;
        ws.getCell('A3').style = {
            font: { size: 12, bold: true, color: 'FFFFFF' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: '235B4E' },
            alignment: { horizontal: 'center' }
        };
        
        // FILA 4: Distribución por tipo
        const tiposIncidencia = [
            ['Retardos', stats.retardos],
            ['Comisiones', stats.comisiones],
            ['Licencias Médicas', stats.licencias],
            ['Días Económicos', stats.diasEco],
            ['Ausencias', stats.ausencias],
            ['Vacaciones', stats.vacaciones],
            ['Cuidados Maternos/Paternos', stats.cuidados],
            ['Faltas', stats.faltas],
            ['Constancias de Tiempo', stats.constancias],
            ['Por Definir', stats.porDefinir],
            ['Sanciones', stats.sanciones]
        ];
        
        ws.mergeCells('A4:R4');
        let resumenTexto = '📋 DISTRIBUCIÓN: ';
        tiposIncidencia.forEach(t => {
            if (t[1] > 0) resumenTexto += t[0] + ': ' + t[1] + ' | ';
        });
        ws.getCell('A4').value = resumenTexto.slice(0, -3);
        ws.getCell('A4').style = {
            font: { size: 10, color: '333333' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: 'E8F5E9' },
            alignment: { horizontal: 'center' }
        };
        
        // FILA 5: Vacía
        ws.getRow(5).height = 10;
        
        // FILA 6: Encabezados de la tabla
        const headers = ['#', 'Empleado', 'RFC', 'Depto', 'Puesto', 'Fecha', 
            'Hora Ent', 'Hora Sal', 'Tipo Registro', 'Detalle Registro',
            'Subtipo', 'Min', 'Just', 'Tipo Justificación',
            'Motivo/Observaciones', 'Detalles Específicos', 'Estado', 'Jefe Directo'];
        
        headers.forEach((h, i) => {
            ws.getCell(6, i + 1).value = h;
            ws.getCell(6, i + 1).style = {
                font: { bold: true, color: 'FFFFFF', size: 10 },
                fill: { type: 'pattern', pattern: 'solid', fgColor: '691C32' },
                alignment: { horizontal: 'center', vertical: 'middle' }
            };
        });
        ws.getRow(6).height = 22;
        
        // FILAS 7+: Datos
        const tipoColors = {
            'Retardo': 'FFF3E0',
            'Comisión': 'E3F2FD',
            'Licencia Médica': 'F3E5F5',
            'Día Económico': 'E8F5E9',
            'Ausencia': 'FFEBEE',
            'Vacaciones': 'E0F2F1',
            'Cuidados': 'FCE4EC',
            'Falta': 'FFCDD2',
            'Constancia': 'E1F5FE',
            'Por Definir': 'FFF8E1',
            'Sanción': 'F8D7DA'
        };
        
        datosReporte.forEach((row, idx) => {
            const rowNum = 7 + idx;
            const bgColor = tipoColors[row.tipo_registro] || (idx % 2 === 0 ? 'FFFFFF' : 'F5F5F5');
            const justBg = row.justificado == 1 ? 'D4EDDA' : 'FFF3CD';
            const justColor = row.justificado == 1 ? '235B4E' : '856404';
            
            const datos = [
                idx + 1,
                row.apellido + ' ' + row.nombre,
                row.rfc || '',
                row.clave_depto || '',
                row.puesto || '',
                formatDate(row.fecha),
                row.hora_entrada ? row.hora_entrada.substring(0,5) : '-',
                row.hora_salida ? row.hora_salida.substring(0,5) : '-',
                row.tipo_registro,
                getRegistroTipoLabel(row.registro_tipo),
                (row.tipo_retraso || '').replace(/_/g, ' '),
                row.minutos_retardo || '-',
                row.justificado == 1 ? 'Sí ✓' : 'No ✗',
                row.justificacion_tipo || '-',
                row.motivo_justificacion || '-',
                getDetalles(row),
                formatEstado(row.estado_validacion),
                row.nombre_jefe || 'Sin asignar'
            ];
            
            datos.forEach((val, colIdx) => {
                const cell = ws.getCell(rowNum, colIdx + 1);
                cell.value = val;
                const isJustCol = colIdx === 12 || colIdx === 16;
                cell.style = {
                    fill: { type: 'pattern', pattern: 'solid', fgColor: isJustCol ? justBg : bgColor },
                    font: { 
                        size: 9, 
                        bold: colIdx === 0 || colIdx === 11, 
                        color: isJustCol ? justColor : '000000' 
                    },
                    alignment: { 
                        horizontal: (colIdx === 0 || colIdx === 6 || colIdx === 7 || colIdx === 11 || colIdx === 12 || colIdx === 16) ? 'center' : 'left' 
                    },
                    border: {
                        top: { style: 'thin', color: { argb: 'DDDDDD' } },
                        bottom: { style: 'thin', color: { argb: 'DDDDDD' } },
                        left: { style: 'thin', color: { argb: 'DDDDDD' } },
                        right: { style: 'thin', color: { argb: 'DDDDDD' } }
                    }
                };
            });
        });
        
        // Fila final con totales
        const footerRow = 7 + datosReporte.length;
        ws.mergeCells('A' + footerRow + ':E' + footerRow);
        ws.getCell(footerRow, 1).value = 'TOTAL REGISTROS';
        ws.getCell(footerRow, 1).style = {
            font: { bold: true, size: 11, color: 'FFFFFF' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: '9F2241' },
            alignment: { horizontal: 'center' }
        };
        
        ws.getCell(footerRow, 6).value = stats.total;
        ws.getCell(footerRow, 6).style = {
            font: { bold: true, size: 12, color: 'FFFFFF' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: '9F2241' },
            alignment: { horizontal: 'center' }
        };
        
        ws.mergeCells('G' + footerRow + ':L' + footerRow);
        ws.getCell(footerRow, 7).value = 'Justificados: ' + stats.justificados;
        ws.getCell(footerRow, 7).style = {
            font: { bold: true, size: 10, color: 'FFFFFF' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: '235B4E' },
            alignment: { horizontal: 'center' }
        };
        
        ws.mergeCells('M' + footerRow + ':R' + footerRow);
        ws.getCell(footerRow, 13).value = 'Pendientes: ' + stats.pendientes;
        ws.getCell(footerRow, 13).style = {
            font: { bold: true, size: 10, color: 'FFFFFF' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: 'BC955C' },
            alignment: { horizontal: 'center' }
        };
        
        // Pie de página
        const pieRow = footerRow + 1;
        ws.mergeCells('A' + pieRow + ':R' + pieRow);
        ws.getCell(pieRow, 1).value = '📄 Documento generado el ' + new Date().toLocaleDateString('es-MX') + ' a las ' + new Date().toLocaleTimeString('es-MX') + ' | Sistema Biométrico';
        ws.getCell(pieRow, 1).style = {
            font: { size: 9, italic: true, color: '666666' },
            fill: { type: 'pattern', pattern: 'solid', fgColor: 'F0F0F0' },
            alignment: { horizontal: 'center' }
        };
        
        // Agregar gráfica de pastel
        const chartCanvas = document.createElement('canvas');
        chartCanvas.width = 900;
        chartCanvas.height = 450;
        document.body.appendChild(chartCanvas);
        
        const chartColors = ['#9F2241', '#17A2B8', '#6F42C1', '#00897B', '#FD7E14', '#20C997', '#EC407A', '#E53935', '#5E35B1', '#FFC107', '#6C757D'];
        
        const chartImage = await new Promise((resolve) => {
            const ctx = chartCanvas.getContext('2d');
            const chartJS = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: tiposIncidencia.filter(t => t[1] > 0).map(t => t[0] + ': ' + t[1]),
                    datasets: [{
                        data: tiposIncidencia.filter(t => t[1] > 0).map(t => t[1]),
                        backgroundColor: chartColors,
                        borderColor: '#FFFFFF',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: false,
                    animation: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'DISTRIBUCIÓN DE INCIDENCIAS POR TIPO',
                            font: { size: 16, weight: 'bold' },
                            color: '#333',
                            padding: 15
                        },
                        legend: {
                            display: true,
                            position: 'right',
                            labels: { font: { size: 11 }, padding: 12 }
                        }
                    }
                }
            });
            setTimeout(() => {
                resolve(chartCanvas.toDataURL('image/png'));
                chartJS.destroy();
            }, 100);
        });
        
        document.body.removeChild(chartCanvas);
        
        const imageId = wb.addImage({
            base64: chartImage,
            extension: 'png'
        });
        
        const chartRow = pieRow + 2;
        ws.addImage(imageId, 'A' + chartRow + ':R' + (chartRow + 18));
        
        // Guardar archivo
        const buffer = await wb.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Reporte_Incidencias_' + new Date().toISOString().slice(0,10) + '.xlsx';
        link.click();
    }
</script>
