<?php
require_once __DIR__ . '/Database.php';

class Asistencia {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getConnection() {
        return $this->db->getConnection();
    }

    public function registrarEntrada($empleado_id, $dispositivo_id, $tipo_biometria = null, $datos_biometricos = null, $calidad_verificacion = null, $metadata_dispositivo = null, $tiempo_procesamiento = null) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO asistencia
            (empleado_id, tipo, dispositivo_id, tipo_biometria, datos_biometricos, calidad_verificacion, metadata_dispositivo, tiempo_procesamiento)
            VALUES (?, 'entrada', ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $empleado_id,
            $dispositivo_id,
            $tipo_biometria,
            $datos_biometricos ? json_encode($datos_biometricos) : null,
            $calidad_verificacion,
            $metadata_dispositivo ? json_encode($metadata_dispositivo) : null,
            $tiempo_procesamiento
        ]);
    }

    public function registrarSalida($empleado_id, $dispositivo_id, $tipo_biometria = null, $datos_biometricos = null, $calidad_verificacion = null, $metadata_dispositivo = null, $tiempo_procesamiento = null) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO asistencia
            (empleado_id, tipo, dispositivo_id, tipo_biometria, datos_biometricos, calidad_verificacion, metadata_dispositivo, tiempo_procesamiento)
            VALUES (?, 'salida', ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $empleado_id,
            $dispositivo_id,
            $tipo_biometria,
            $datos_biometricos ? json_encode($datos_biometricos) : null,
            $calidad_verificacion,
            $metadata_dispositivo ? json_encode($metadata_dispositivo) : null,
            $tiempo_procesamiento
        ]);
    }
    
    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT a.*, e.nombre, e.apellido, e.rfc, e.area, e.puesto
            FROM asistencia a
            LEFT JOIN empleados e ON a.empleado_id = e.id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByEmpleado($empleado_id, $fecha_inicio = null, $fecha_fin = null) {
        $query = "
            SELECT 
                MIN(a.id) as id,
                a.empleado_id,
                a.fecha,
                MIN(a.hora_entrada) as hora_entrada,
                MAX(a.hora_salida) as hora_salida,
                MAX(a.tipo_asistencia) as tipo_asistencia,
                MAX(a.dispositivo_id) as dispositivo_id,
                MAX(a.tipo_biometria) as tipo_biometria,
                MAX(a.created_at) as created_at,
                MAX(a.requiere_validacion_jefe) as requiere_validacion_jefe,
                MAX(a.requerio_validacion) as requerio_validacion,
                MAX(a.validado_por_jefe) as validado_por_jefe,
                MAX(a.fecha_aprobacion) as fecha_aprobacion,
                MAX(a.estado_validacion) as estado_validacion,
                MAX(a.tipo_justificacion_id) as tipo_justificacion_id,
                tj.nombre as tipo_justificacion_nombre,
                e.nombre, 
                e.apellido, 
                e.rfc
            FROM asistencia a
            LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
            JOIN empleados e ON a.empleado_id = e.id
        ";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE a.empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($fecha_inicio && $fecha_fin) {
            $query .= ($params ? " AND" : " WHERE") . " a.fecha BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $query .= " GROUP BY a.empleado_id, a.fecha, e.nombre, e.apellido, e.rfc, a.requiere_validacion_jefe, a.requerio_validacion, a.validado_por_jefe, a.fecha_aprobacion, a.estado_validacion, a.tipo_justificacion_id, tj.nombre";
    
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        
        // Recalcular tipo_asistencia basado en el horario del empleado
        require_once __DIR__ . '/HorarioLaboral.php';
        $horarioLaboral = new HorarioLaboral();
        $result = $this->clasificarAsistencias($empleado_id, $result, $horarioLaboral);
        
        return $result;
    }
    
    /**
     * Clasifica las assistencias en tiempo real basándose en el horario del empleado
     */
    private function clasificarAsistencias($empleado_id, $asistencias, $horarioLaboral) {
        foreach ($asistencias as &$asis) {
            // Si tiene justificación aprobada, no recalcular
            if (!empty($asis['tipo_justificacion_id']) && 
                ($asis['estado_validacion'] === 'aprobada' || $asis['estado_validacion'] === 'aprobado')) {
                continue;
            }
            
            // Si no tiene hora de entrada, no se puede clasificar
            if (empty($asis['hora_entrada'])) {
                continue;
            }
            
            // Obtener el horario del empleado para esta fecha
            $horario = $horarioLaboral->getHorarioPorFecha($empleado_id, $asis['fecha']);
            
            if ($horario && !empty($horario['hora_entrada'])) {
                $horaEsperada = $horario['hora_entrada'];
                $horaReal = $asis['hora_entrada'];
                
                // Calcular minutos de retraso
                $diff = strtotime($horaReal) - strtotime($horaEsperada);
                $minutosRetraso = floor($diff / 60);
                
                // Clasificar
                if ($minutosRetraso <= 0) {
                    // A tiempo
                    $asis['tipo_asistencia'] = 'normal';
                    $asis['minutos_retraso'] = 0;
                } elseif ($minutosRetraso <= 10) {
                    // Tolerancia - считается normal
                    $asis['tipo_asistencia'] = 'normal';
                    $asis['minutos_retraso'] = $minutosRetraso;
                } elseif ($minutosRetraso <= 20) {
                    // Retardo menor
                    $asis['tipo_asistencia'] = 'con_retardo';
                    $asis['minutos_retraso'] = $minutosRetraso;
                    $asis['tipo_retraso'] = 'retardo_menor';
                } elseif ($minutosRetraso <= 30) {
                    // Retardo mayor
                    $asis['tipo_asistencia'] = 'con_retardo';
                    $asis['minutos_retraso'] = $minutosRetraso;
                    $asis['tipo_retraso'] = 'retardo_mayor';
                } else {
                    // Falta
                    $asis['tipo_asistencia'] = 'falta';
                    $asis['minutos_retraso'] = $minutosRetraso;
                    $asis['tipo_retraso'] = 'falta';
                }
            }
        }
        return $asistencias;
    }
    
    /**
     * Obtiene las asistencias con tipo "por_definir" para justificar
     * Incluye estado de validación si existe
     */
    public function getByEmpleadoPorDefinir($empleado_id) {
        $query = "
            SELECT 
                a.*, 
                e.nombre, 
                e.apellido, 
                e.rfc, 
                e.plaza_confianza, 
                e.fecha_ingreso,
                vj.estado AS estado_validacion_jefe,
                vj.motivo_validacion AS motivo_validacion_jefe,
                vj.comentarios_adicionales AS comentarios_validacion_jefe,
                vj.fecha_validacion AS fecha_validacion_jefe,
                uj.nombre_completo AS jefe_validador_nombre
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            LEFT JOIN (
                SELECT v.*
                FROM validaciones_jefe v
                INNER JOIN (
                    SELECT incidencia_id, tipo_incidencia, MAX(id) AS max_id
                    FROM validaciones_jefe
                    GROUP BY incidencia_id, tipo_incidencia
                ) x ON x.max_id = v.id
            ) vj ON vj.incidencia_id = a.id AND vj.tipo_incidencia = 'asistencia'
            LEFT JOIN usuarios uj ON uj.id = vj.jefe_id
            WHERE a.empleado_id = ? 
            AND a.tipo_asistencia = 'por_definir'
            ORDER BY a.fecha DESC
        ";
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene TODOS los registros combinados de asistencia Y retardos
     * Muestra toda la información de incidencias en una sola vista
     */
    public function getAllIncidenciasComplete($empleado_id) {
        $pdo = $this->db->getConnection();
        $resultados = [];

        // 1. OBTENER TODOS LOS REGISTROS DE ASISTENCIA
        $stmtAsistencia = $pdo->prepare("
            SELECT 
                a.id,
                a.empleado_id,
                a.fecha,
                a.hora_entrada,
                a.hora_salida,
                a.tipo_asistencia,
                a.estado_validacion,
                a.validado_por_jefe,
                a.fecha_aprobacion,
                a.requiere_validacion_jefe,
                'asistencia' AS fuente,
                a.id AS asistencia_id,
                NULL AS retardo_id,
                NULL AS minutos_retardo,
                NULL AS tipo_retraso,
                NULL AS motivo_justificacion,
                vj.estado AS estado_validacion_jefe,
                vj.motivo_validacion AS motivo_validacion_jefe,
                vj.comentarios_adicionales AS comentarios_validacion_jefe,
                vj.fecha_validacion AS fecha_validacion_jefe,
                uj.nombre_completo AS jefe_validador_nombre
            FROM asistencia a
            LEFT JOIN (
                SELECT v.*
                FROM validaciones_jefe v
                INNER JOIN (
                    SELECT incidencia_id, tipo_incidencia, MAX(id) AS max_id
                    FROM validaciones_jefe
                    GROUP BY incidencia_id, tipo_incidencia
                ) x ON x.max_id = v.id
            ) vj ON vj.incidencia_id = a.id AND vj.tipo_incidencia = 'asistencia'
            LEFT JOIN usuarios uj ON uj.id = vj.jefe_id
            WHERE a.empleado_id = ?
            ORDER BY a.fecha DESC
        ");
        $stmtAsistencia->execute([$empleado_id]);
        $resultados = array_merge($resultados, $stmtAsistencia->fetchAll());

        // 2. OBTENER TODOS LOS RETARDOS
        $stmtRetardos = $pdo->prepare("
            SELECT 
                r.id,
                r.empleado_id,
                r.fecha,
                r.hora_entrada,
                r.hora_salida,
                r.minutos_retardo,
                r.tipo_retraso,
                r.justificado,
                r.motivo_justificacion,
                r.tipo_justificacion_id,
                tj.nombre AS tipo_justificacion_nombre,
                'retardos' AS fuente,
                r.asistencia_id,
                r.id AS retardo_id,
                r.tipo_asistencia,
                vj.estado AS estado_validacion_jefe,
                vj.motivo_validacion AS motivo_validacion_jefe,
                vj.comentarios_adicionales AS comentarios_validacion_jefe,
                vj.fecha_validacion AS fecha_validacion_jefe,
                uj.nombre_completo AS jefe_validador_nombre
            FROM retardos r
            LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
            LEFT JOIN (
                SELECT v.*
                FROM validaciones_jefe v
                INNER JOIN (
                    SELECT incidencia_id, tipo_incidencia, MAX(id) AS max_id
                    FROM validaciones_jefe
                    GROUP BY incidencia_id, tipo_incidencia
                ) x ON x.max_id = v.id
            ) vj ON vj.incidencia_id = r.id AND vj.tipo_incidencia = 'retardo'
            LEFT JOIN usuarios uj ON uj.id = vj.jefe_id
            WHERE r.empleado_id = ?
            ORDER BY r.fecha DESC
        ");
        $stmtRetardos->execute([$empleado_id]);
        $resultados = array_merge($resultados, $stmtRetardos->fetchAll());

        // Ordenar por fecha descendente
        usort($resultados, function($a, $b) {
            $fa = strtotime($a['fecha'] ?? '1970-01-01');
            $fb = strtotime($b['fecha'] ?? '1970-01-01');
            if ($fa === $fb) {
                return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
            }
            return $fb <=> $fa;
        });

        return $resultados;
    }

    /**
     * Alias para compatibilidad hacia atrás
     */
    public function getAllByEmpleadoWithJustificaciones($empleado_id) {
        return $this->getAllIncidenciasComplete($empleado_id);
    }

    public function getByDispositivo($dispositivo_id, $fecha_inicio = null, $fecha_fin = null) {
        $query = "
            SELECT a.*, e.nombre, e.apellido, e.rfc
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            WHERE a.dispositivo_id = ?
        ";
        $params = [$dispositivo_id];

        if ($fecha_inicio && $fecha_fin) {
            $query .= " AND DATE(a.created_at) BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $query .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT a.*, e.nombre, e.apellido, e.rfc
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Actualizar un registro de asistencia
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $values[] = $value;
        }
        $values[] = $id;
        
        $sql = "UPDATE asistencia SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Obtiene estadísticas de asistencia por dispositivo
     */
    public function getEstadisticasPorDispositivo($fecha_inicio = null, $fecha_fin = null) {
        $query = "
            SELECT
                dispositivo_id,
                COUNT(*) as total_registros,
                SUM(CASE WHEN hora_entrada IS NOT NULL AND hora_salida IS NULL THEN 1 ELSE 0 END) as solo_entrada,
                SUM(CASE WHEN hora_entrada IS NOT NULL AND hora_salida IS NOT NULL THEN 1 ELSE 0 END) as registros_completos,
                SUM(CASE WHEN tipo_biometria = 'huella' THEN 1 ELSE 0 END) as verificaciones_huella,
                SUM(CASE WHEN tipo_biometria = 'cara' THEN 1 ELSE 0 END) as verificaciones_cara
            FROM asistencia
            WHERE 1=1
        ";
        $params = [];

        if ($fecha_inicio && $fecha_fin) {
            $query .= " AND fecha BETWEEN ? AND ?";
            $params = [$fecha_inicio, $fecha_fin];
        }

        $query .= " GROUP BY dispositivo_id ORDER BY dispositivo_id";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene registros de asistencia con filtros avanzados
     */
    public function getAsistenciaFiltrada($filtros = []) {
        $query = "
            SELECT a.*, e.nombre, e.apellido, e.rfc, e.area
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['dispositivo_id'])) {
            $query .= " AND a.dispositivo_id = ?";
            $params[] = $filtros['dispositivo_id'];
        }

        if (!empty($filtros['tipo_biometria'])) {
            $query .= " AND a.tipo_biometria = ?";
            $params[] = $filtros['tipo_biometria'];
        }

        if (!empty($filtros['tipo_asistencia'])) {
            // Adaptar filtro para el esquema sin columna 'tipo'
            if ($filtros['tipo_asistencia'] === 'entrada') {
                $query .= " AND a.hora_entrada IS NOT NULL";
            } elseif ($filtros['tipo_asistencia'] === 'salida') {
                $query .= " AND a.hora_salida IS NOT NULL";
            }
        }

        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $query .= " AND DATE(a.created_at) BETWEEN ? AND ?";
            $params[] = $filtros['fecha_inicio'];
            $params[] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['empleado_id'])) {
            $query .= " AND a.empleado_id = ?";
            $params[] = $filtros['empleado_id'];
        }

        $query .= " ORDER BY a.created_at DESC";

        if (!empty($filtros['limit'])) {
            $query .= " LIMIT " . (int)$filtros['limit'];
        }

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene registros de asistencia incluyendo datos de retardos
     * Usado para exportación Excel donde se necesita info de retardos
     */
    public function getAsistenciaConRetardos($filtros = []) {
        $query = "
            SELECT a.*,
                   e.nombre, e.apellido, e.rfc, e.area,
                   r.id as retardo_id,
                   r.minutos_retardo,
                   r.tipo_retraso,
                   r.justificado,
                   r.motivo_justificacion,
                   r.estado_validacion as retardo_estado_validacion,
                   r.fecha_aprobacion as retardo_fecha_aprobacion
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            LEFT JOIN retardos r ON a.empleado_id = r.empleado_id AND a.fecha = r.fecha
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['dispositivo_id'])) {
            $query .= " AND a.dispositivo_id = ?";
            $params[] = $filtros['dispositivo_id'];
        }

        if (!empty($filtros['tipo_biometria'])) {
            $query .= " AND a.tipo_biometria = ?";
            $params[] = $filtros['tipo_biometria'];
        }

        if (!empty($filtros['tipo_asistencia'])) {
            if ($filtros['tipo_asistencia'] === 'entrada') {
                $query .= " AND a.hora_entrada IS NOT NULL";
            } elseif ($filtros['tipo_asistencia'] === 'salida') {
                $query .= " AND a.hora_salida IS NOT NULL";
            }
        }

        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $query .= " AND DATE(a.created_at) BETWEEN ? AND ?";
            $params[] = $filtros['fecha_inicio'];
            $params[] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['empleado_id'])) {
            $query .= " AND a.empleado_id = ?";
            $params[] = $filtros['empleado_id'];
        }

        $query .= " ORDER BY a.created_at DESC";

        if (!empty($filtros['limit'])) {
            $query .= " LIMIT " . (int)$filtros['limit'];
        }

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene empleados con asistencia en un período específico
     */
    public function getEmpleadosConAsistencia($fecha_inicio, $fecha_fin, $area = null) {
        $query = "
            SELECT DISTINCT
                e.id,
                e.nombre,
                e.apellido,
                e.area,
                a.fecha as fecha,
                a.hora_entrada as hora_entrada,
                a.hora_salida as hora_salida
            FROM empleados e
            JOIN asistencia a ON e.id = a.empleado_id
            WHERE DATE(a.created_at) BETWEEN ? AND ?
        ";
        $params = [$fecha_inicio, $fecha_fin];

        if ($area) {
            $query .= " AND e.area = ?";
            $params[] = $area;
        }

        $query .= " GROUP BY e.id, e.nombre, e.apellido, e.area, DATE(a.created_at)
                   ORDER BY DATE(a.created_at) DESC, e.nombre ASC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Calcula si hay retardo basado en la hora de entrada
     * @param string $hora_entrada Hora de entrada (formato H:i:s)
     * @param int $empleado_id ID del empleado
     * @param string $fecha Fecha (formato Y-m-d)
     * @return array Información del retardo calculado
     * @deprecated Usar AsistenciaService::calcularRetardo para aplicar reglas de negocio completas
     */
    public function calcularRetardo($hora_entrada, $empleado_id, $fecha) {
        require_once __DIR__ . '/HorarioLaboral.php';
        
        // Obtener información del empleado para determinar sede
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM empleados WHERE id = ? AND activo = 1
        ");
        $stmt->execute([$empleado_id]);
        $empleado = $stmt->fetch();
        
        if (!$empleado) {
            return [
                'tiene_retardo' => false,
                'minutos' => 0,
                'tipo' => 'sin_registro',
                'motivo' => 'Empleado no encontrado o inactivo'
            ];
        }
        
        // Obtener horario aplicable para esta fecha
        $horarioModel = new HorarioLaboral();
        $horario = $horarioModel->getHorarioPorFecha($empleado_id, $fecha, $empleado['sede'] ?? null);
        
        if (!$horario) {
            // Si no hay horario definido, usar horario por defecto
            $hora_oficial = '09:00:00';
            $tolerancia = 10; // 10 minutos por defecto
        } else {
            $hora_oficial = $horario['hora_entrada'];
            $tolerancia = $horario['tolerancia_minutos'] ?? 10;
        }
        
        // Convertir tiempos a timestamps para cálculo
        $fecha_entrada = $fecha . ' ' . $hora_entrada;
        $timestamp_entrada = strtotime($fecha_entrada);
        $timestamp_oficial = strtotime($fecha . ' ' . $hora_oficial);
        $timestamp_limite = $timestamp_oficial + ($tolerancia * 60); // Límite con tolerancia
        
        // Calcular minutos de retardo desde hora oficial (sin tolerancia en el conteo)
        $diferencia_segundos = $timestamp_entrada - $timestamp_oficial;
        $minutos_retardo = max(0, floor($diferencia_segundos / 60));
        
        // Determinar tipo de retardo según reglas SEP
        // Tolerancia: 0-10 min (no se registra)
        // Retardo Menor: 11-20 min
        // Retardo Mayor: 21-30 min
        // Falta: 31+ min
        if ($minutos_retardo >= 0 && $minutos_retardo <= 10) {
            $tipo_retardo = 'tolerancia';
            $motivo = 'Tolerancia (0-10 min)';
        } elseif ($minutos_retardo >= 11 && $minutos_retardo <= 20) {
            $tipo_retardo = 'retardo_menor';
            $motivo = 'Retardo Menor (11-20 min)';
        } elseif ($minutos_retardo >= 21 && $minutos_retardo <= 30) {
            $tipo_retardo = 'retardo_mayor';
            $motivo = 'Retardo Mayor (21-30 min)';
        } elseif ($minutos_retardo >= 31) {
            $tipo_retardo = 'falta';
            $motivo = 'Falta (31+ min)';
        } else {
            $tipo_retardo = 'sin_retardo';
            $motivo = 'Puntual';
        }
        
        return [
            'tiene_retardo' => $minutos_retardo > 10,
            'minutos' => $minutos_retardo,
            'tipo' => $tipo_retardo,
            'motivo' => $motivo,
            'hora_entrada' => $hora_entrada,
            'hora_oficial' => $hora_oficial,
            'tolerancia' => $tolerancia,
            'horario_id' => $horario['id'] ?? null,
            'timestamp_entrada' => $timestamp_entrada,
            'timestamp_oficial' => $timestamp_oficial,
            'timestamp_limite' => $timestamp_limite
        ];
    }
}
?>
