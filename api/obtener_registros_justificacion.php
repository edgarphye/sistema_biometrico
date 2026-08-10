<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

    $empleado_id = $_GET['empleado_id'] ?? null;
    $fecha = $_GET['fecha'] ?? null;
    $tipo = $_GET['tipo'] ?? 'all';
    
    if (empty($empleado_id)) {
        throw new Exception('Empleado ID requerido');
    }
    
    $conn = $db->getConnection();
    $resultados = [];
    
    // 1. Obtener retardos del empleado
    if ($tipo === 'all' || $tipo === 'retardos') {
        $sqlRetardos = "
            SELECT 
                r.id,
                r.empleado_id,
                r.fecha,
                r.minutos_retraso as minutos,
                r.tipo_retraso as tipo,
                r.justificado,
                r.tipo_justificacion_id,
                tj.nombre as tipo_justificacion_nombre,
                r.motivo_justificacion,
                r.aprobado_por,
                r.fecha_aprobacion,
                r.modificado_por,
                r.fecha_modificacion,
                'retardos' as tabla,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado
            FROM retardos r
            LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
            LEFT JOIN empleados e ON r.empleado_id = e.id
            WHERE r.empleado_id = ?
        ";
        $params = [$empleado_id];
        
        if ($fecha) {
            $sqlRetardos .= " AND r.fecha = ?";
            $params[] = $fecha;
        }
        
        $sqlRetardos .= " ORDER BY r.fecha DESC LIMIT 50";
        
        $stmt = $conn->prepare($sqlRetardos);
        $stmt->execute($params);
        $resultados['retardos'] = $stmt->fetchAll();
    }
    
    // 2. Obtener asistencia del empleado
    if ($tipo === 'all' || $tipo === 'asistencia') {
        $sqlAsistencia = "
            SELECT 
                a.id,
                a.empleado_id,
                a.fecha,
                a.hora_entrada,
                a.hora_salida,
                a.tipo_asistencia,
                a.tipo_justificacion_id,
                tj.nombre as tipo_justificacion_nombre,
                a.motivo_justificacion,
                a.modificado_por,
                a.fecha_modificacion,
                'asistencia' as tabla,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado
            FROM asistencia a
            LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
            LEFT JOIN empleados e ON a.empleado_id = e.id
            WHERE a.empleado_id = ?
        ";
        $params = [$empleado_id];
        
        if ($fecha) {
            $sqlAsistencia .= " AND a.fecha = ?";
            $params[] = $fecha;
        }
        
        $sqlAsistencia .= " ORDER BY a.fecha DESC LIMIT 50";
        
        $stmt = $conn->prepare($sqlAsistencia);
        $stmt->execute($params);
        $resultados['asistencia'] = $stmt->fetchAll();
    }
    
    // 3. Obtener días económicos del empleado
    if ($tipo === 'all' || $tipo === 'dias_economicos') {
        $sqlDiasEco = "
            SELECT 
                d.id,
                d.empleado_id,
                d.fecha,
                d.motivo,
                d.estatus,
                d.modalidad,
                d.modificado_por,
                d.fecha_modificacion,
                d.validado_por,
                d.fecha_validacion,
                'dias_economicos' as tabla,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado
            FROM dias_economicos d
            LEFT JOIN empleados e ON d.empleado_id = e.id
            WHERE d.empleado_id = ?
        ";
        $params = [$empleado_id];
        
        if ($fecha) {
            $sqlDiasEco .= " AND d.fecha = ?";
            $params[] = $fecha;
        }
        
        $sqlDiasEco .= " ORDER BY d.fecha DESC LIMIT 50";
        
        $stmt = $conn->prepare($sqlDiasEco);
        $stmt->execute($params);
        $resultados['dias_economicos'] = $stmt->fetchAll();
    }

    // 4. Obtener licencias (ausencias)
    if ($tipo === 'all' || $tipo === 'licencias') {
        $sqlLicencias = "
            SELECT 
                a.id,
                a.empleado_id,
                a.fecha_inicio as fecha,
                a.fecha_fin,
                a.tipo,
                a.motivo,
                a.estatus as estatus,
                a.activo,
                a.created_at,
                'licencias' as tabla,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado
            FROM ausencias a
            LEFT JOIN empleados e ON a.empleado_id = e.id
            WHERE a.empleado_id = ?
        ";
        $params = [$empleado_id];
        
        if ($fecha) {
            $sqlLicencias .= " AND (a.fecha_inicio <= ? AND a.fecha_fin >= ?)";
            $params[] = $fecha;
            $params[] = $fecha;
        }
        
        $sqlLicencias .= " ORDER BY a.fecha_inicio DESC LIMIT 50";
        
        $stmt = $conn->prepare($sqlLicencias);
        $stmt->execute($params);
        $resultados['licencias'] = $stmt->fetchAll();
    }

    // 5. Obtener vacaciones
    if ($tipo === 'all' || $tipo === 'vacaciones') {
        $sqlVacaciones = "
            SELECT 
                v.id,
                v.empleado_id,
                v.fecha_inicio as fecha,
                v.fecha_fin,
                v.dias,
                v.estatus,
                v.periodo,
                v.aprobado_por,
                'vacaciones' as tabla,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado
            FROM vacaciones v
            LEFT JOIN empleados e ON v.empleado_id = e.id
            WHERE v.empleado_id = ?
        ";
        $params = [$empleado_id];
        
        if ($fecha) {
            $sqlVacaciones .= " AND (v.fecha_inicio <= ? AND v.fecha_fin >= ?)";
            $params[] = $fecha;
            $params[] = $fecha;
        }
        
        $sqlVacaciones .= " ORDER BY v.fecha_inicio DESC LIMIT 50";
        
        $stmt = $conn->prepare($sqlVacaciones);
        $stmt->execute($params);
        $resultados['vacaciones'] = $stmt->fetchAll();
    }

    // 6. Obtener justificaciones
    if ($tipo === 'all' || $tipo === 'permisos') {
        $sqlPermisos = "
            SELECT 
                j.id,
                j.empleado_id,
                j.fecha_inicio as fecha,
                j.fecha_fin,
                j.tipo_justificacion,
                j.motivo,
                j.estatus,
                j.aprobado_por,
                j.created_at,
                'permisos' as tabla,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_empleado
            FROM justificaciones j
            LEFT JOIN empleados e ON j.empleado_id = e.id
            WHERE j.empleado_id = ?
        ";
        $params = [$empleado_id];
        
        if ($fecha) {
            $sqlPermisos .= " AND (j.fecha_inicio <= ? AND j.fecha_fin >= ?)";
            $params[] = $fecha;
            $params[] = $fecha;
        }
        
        $sqlPermisos .= " ORDER BY j.fecha_inicio DESC LIMIT 50";
        
        $stmt = $conn->prepare($sqlPermisos);
        $stmt->execute($params);
        $resultados['permisos'] = $stmt->fetchAll();
    }
    
    // 4. Obtener tipos de justificación disponibles
    $stmt = $conn->prepare("SELECT * FROM tipos_justificacion WHERE activo = 1 ORDER BY nombre");
    $stmt->execute();
    $resultados['tipos_justificacion'] = $stmt->fetchAll();
    
    // 5. Obtener info del empleado
    $stmt = $conn->prepare("SELECT id, num_empleado, nombre, apellido, area FROM empleados WHERE id = ?");
    $stmt->execute([$empleado_id]);
    $resultados['empleado'] = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'datos' => $resultados
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}