<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

header('Content-Type: application/json');
ini_set('memory_limit', '128M');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo no permitido']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $filtro_id = $_GET['filtro_id'] ?? '';
    $filtro_nombre = $_GET['filtro_nombre'] ?? '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, intval($_GET['per_page'])) : 50;
    $offset = ($page - 1) * $perPage;
    
    // Obtener asistencia - SIN LIMIT/OFFSET para poder filtrar correctamente
    // Unir con justificaciones para obtener tipo_justificacion_id y motivo
    $sql1 = "SELECT a.id, a.empleado_id, e.id as num_empleado, e.nombre, e.apellido, e.rfc, e.curp, e.area, e.puesto,
            a.fecha, a.hora_entrada, a.hora_salida, a.tipo_asistencia, 
            COALESCE(j.tipo_justificacion_id, a.tipo_justificacion_id) as tipo_justificacion_id,
            COALESCE(tj.nombre, COALESCE(jj.nombre, '')) as tipo_justificacion_nombre,
            COALESCE(j.motivo, '') as motivo,
            NULL as validado_por,
            'asistencia' as tipo_registro, a.fecha as fecha_orden
            FROM asistencia a 
            INNER JOIN empleados e ON a.empleado_id = e.id
            LEFT JOIN justificaciones j ON j.asistencia_id = a.id AND j.estatus = 'aprobada'
            LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
            LEFT JOIN tipos_justificacion jj ON j.tipo_justificacion_id = jj.id
            WHERE a.fecha BETWEEN ? AND ? 
            ORDER BY a.fecha DESC, e.id ASC";
    
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$fecha_inicio, $fecha_fin]);
    $asistencia = $stmt1->fetchAll();
    
    // Obtener retardos
    $sql2 = "SELECT r.id, r.empleado_id, e.id as num_empleado, e.nombre, e.apellido, e.rfc, e.curp, e.area, e.puesto,
            r.fecha, NULL as hora_entrada, NULL as hora_salida, r.tipo_retraso as tipo_asistencia, r.tipo_justificacion_id,
            COALESCE(tj.nombre, '') as tipo_justificacion_nombre,
            COALESCE(j.motivo, '') as motivo,
            NULL as validado_por,
            'retardos' as tipo_registro, r.fecha as fecha_orden
            FROM retardos r 
            INNER JOIN empleados e ON r.empleado_id = e.id
            LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
            LEFT JOIN justificaciones j ON j.empleado_id = r.empleado_id AND j.fecha_inicio = r.fecha AND j.estatus = 'aprobada'
            WHERE r.fecha BETWEEN ? AND ?";
    
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute([$fecha_inicio, $fecha_fin]);
    $retardos = $stmt2->fetchAll();
    
    // Obtener dias_economicos
    $sql3 = "SELECT d.id, d.empleado_id, e.id as num_empleado, e.nombre, e.apellido, e.rfc, e.curp, e.area, e.puesto,
            d.fecha, NULL as hora_entrada, NULL as hora_salida, 'dia_economico' as tipo_asistencia, NULL as tipo_justificacion_id,
            '' as tipo_justificacion_nombre,
            COALESCE(d.motivo, '') as motivo,
            NULL as validado_por,
            'dias_economicos' as tipo_registro, d.fecha as fecha_orden
            FROM dias_economicos d INNER JOIN empleados e ON d.empleado_id = e.id
            WHERE d.fecha BETWEEN ? AND ?";
    
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute([$fecha_inicio, $fecha_fin]);
    $diasEco = $stmt3->fetchAll();
    
// Obtener justificaciones/permisos
    $sql4 = "SELECT j.id, j.empleado_id, e.id as num_empleado, e.nombre, e.apellido, e.rfc, e.curp, e.area, e.puesto,
            j.fecha_inicio as fecha, NULL as hora_entrada, NULL as hora_salida, 'permiso' as tipo_asistencia, NULL as tipo_justificacion_id,
            COALESCE(j.tipo_justificacion, '') as tipo_justificacion_nombre,
            COALESCE(j.motivo, '') as motivo,
            NULL as validado_por,
            'permisos' as tipo_registro, j.fecha_inicio as fecha_orden
            FROM justificaciones j INNER JOIN empleados e ON j.empleado_id = e.id
            WHERE j.fecha_inicio BETWEEN ? AND ?";
    
    $stmt4 = $conn->prepare($sql4);
    $stmt4->execute([$fecha_inicio, $fecha_fin]);
    $permisos = $stmt4->fetchAll();
    
    // Combinar todos
    $registros = array_merge($asistencia, $retardos, $diasEco, $permisos);
    
// Ordenar por fecha
    usort($registros, function($a, $b) {
        $dateCompare = strcmp($b['fecha_orden'], $a['fecha_orden']);
        if ($dateCompare !== 0) return $dateCompare;
        return ($a['num_empleado'] ?? 0) - ($b['num_empleado'] ?? 0);
    });
    
    // Detectar PRIMERA/DUPLICADO para cada empleado en cada fecha
    $fechasEmpleado = [];
    foreach ($registros as $idx => $reg) {
        if ($reg['tipo_registro'] === 'asistencia' || $reg['tipo_registro'] === 'retardos') {
            $key = $reg['empleado_id'] . '_' . $reg['fecha'];
            if (!isset($fechasEmpleado[$key])) {
                $fechasEmpleado[$key] = [];
            }
            $fechasEmpleado[$key][] = $idx;
        }
    }
    
    foreach ($fechasEmpleado as $key => $indices) {
        if (count($indices) > 1) {
            $registros[$indices[0]]['es_primera_entrada'] = true;
            for ($i = 1; $i < count($indices); $i++) {
                $registros[$indices[$i]]['es_duplicado'] = true;
            }
        } else {
            $registros[$indices[0]]['es_primera_entrada'] = true;
        }
    }
    
    // Aplicar filtros de empleado
    if ($filtro_id || $filtro_nombre) {
        $filtro_id = trim($filtro_id);
        $filtro_nombre = strtolower(trim($filtro_nombre));
        
        $registros = array_filter($registros, function($reg) use ($filtro_id, $filtro_nombre) {
            // Filtro por ID - usar comparación flexible
            if ($filtro_id && isset($reg['empleado_id'])) {
                if ($reg['empleado_id'] != $filtro_id) return false;
            }
            // Filtro por nombre/RFC/CURP
            if ($filtro_nombre) {
                $nombreCompleto = strtolower(($reg['nombre'] ?? '') . ' ' . ($reg['apellido'] ?? ''));
                $rfc = strtolower($reg['rfc'] ?? '');
                $curp = strtolower($reg['curp'] ?? '');
                
                if (strpos($nombreCompleto, $filtro_nombre) === false && 
                    strpos($rfc, $filtro_nombre) === false && 
                    strpos($curp, $filtro_nombre) === false) {
                    return false;
                }
            }
            return true;
        });
        // Reindexar array
        $registros = array_values($registros);
    }
    
    // Contar totales con filtros aplicados
    $total = count($registros);
    $totalPages = ceil($total / $perPage);
    
    // Obtener tipos de justificación para el dropdown
    $tiposJustStmt = $conn->prepare("SELECT id, nombre FROM tipos_justificacion WHERE activo = 1 ORDER BY nombre");
    $tiposJustStmt->execute();
    $tipos_justificacion = $tiposJustStmt->fetchAll();
    
    // Aplicar paginación
    $registros = array_slice($registros, $offset, $perPage);
    
    echo json_encode([
        'success' => true,
        'datos' => [
            'registros' => $registros,
            'tipos_justificacion' => $tipos_justificacion,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'offset' => $offset,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}