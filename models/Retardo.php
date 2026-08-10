<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../helpers/RequestValidator.php';

class Retardo {
    private $db;

    public function __construct($db = null) {
        if ($db instanceof Database) {
            $this->db = $db;
        } else {
            $this->db = Database::getInstance();
        }
    }

    public function getById($id, $pdo = null) {
        // Validar ID
        $validated_id = SecurityHelper::sanitizeInt($id, 1);
        if (!$validated_id) {
            return false;
        }
        
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("
            SELECT r.*, e.nombre, e.apellido, e.jefe_directo_id 
            FROM retardos r
            LEFT JOIN empleados e ON r.empleado_id = e.id
            WHERE r.id = ?
        ");
        $stmt->execute([$validated_id]);
        $row = $stmt->fetch();
        
        // Asegurar que exista la clave 'tipo' para la vista
        if ($row && !isset($row['tipo']) && isset($row['tipo_retraso'])) {
            $row['tipo'] = $row['tipo_retraso'];
        }
        
        return $row;
    }

    /**
     * Registra un retardo en la tabla `retardos`.
     * Acepta un PDO opcional para ser visible dentro de transacciones de prueba.
     */
    public function registrarRetardo($empleado_id, $fecha, $minutos, $tipo = 'menor', $horario_id = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $stmt = $pdo->prepare(
            "INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_retraso, horario_id, justificado, soporte)
            VALUES (?, ?, ?, ?, ?, 0, NULL)"
        );

        return $stmt->execute([$empleado_id, $fecha, $minutos, $tipo, $horario_id]);
    }

    public function getByEmpleado($empleado_id, $fecha_inicio = null, $fecha_fin = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $query = "SELECT r.*, tj.nombre as tipo_justificacion 
                  FROM retardos r
                  LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE r.empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($fecha_inicio && $fecha_fin) {
            $query .= ($params ? " AND" : " WHERE") . " r.fecha BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        
        // Normalizar tipo_retraso a tipo para compatibilidad con vistas
        foreach ($rows as &$row) {
            if (!isset($row['tipo']) && isset($row['tipo_retraso'])) {
                $row['tipo'] = $row['tipo_retraso'];
            }
        }
        unset($row);
        
        return $rows;
    }

    /**
     * Obtiene SOLO las justificaciones/incidencias del empleado (NO retardos)
     * Incluye: comisiones, días económicos, vacaciones, licencias médicas, cuidados
     */
    public function getIncidenciasByEmpleado($empleado_id, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $justificaciones = [];

        // 1. COMISIONES (de tabla comisiones)
        $stmtComisiones = $pdo->prepare("
            SELECT
                c.id,
                c.empleado_id,
                c.fecha_inicio AS fecha,
                NULL AS hora_entrada,
                NULL AS hora_salida,
                NULL AS minutos_retardo,
                c.tipo_comision AS tipo_incidencia,
                CASE c.estatus WHEN 'aprobada' THEN 1 WHEN 'pendiente' THEN 0 ELSE 0 END AS justificado,
                c.descripcion,
                tj.nombre AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                c.estatus AS estado_validacion_jefe,
                NULL AS motivo_validacion_jefe,
                NULL AS comentarios_validacion_jefe,
                c.fecha_aprobacion AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                c.estatus AS estado_validacion_rh,
                c.fecha_aprobacion AS fecha_validacion_rh,
                'comision' AS fuente,
                c.asistencia_id
            FROM comisiones c
            LEFT JOIN tipos_justificacion tj ON c.tipo_justificacion_id = tj.id
            LEFT JOIN usuarios ua ON ua.id = c.aprobado_por
            WHERE c.empleado_id = ?
            ORDER BY c.fecha_inicio DESC
        ");
        $stmtComisiones->execute([$empleado_id]);
        $justificaciones = array_merge($justificaciones, $stmtComisiones->fetchAll());

        // 2. JUSTIFICACIONES (licencia_medica, dia_economico, constancia_tiempo, etc.)
        $stmtJustificaciones = $pdo->prepare("
            SELECT
                j.id,
                j.empleado_id,
                j.fecha_inicio AS fecha,
                NULL AS hora_entrada,
                NULL AS hora_salida,
                NULL AS minutos_retardo,
                j.tipo_justificacion AS tipo_incidencia,
                CASE j.estatus WHEN 'aprobada' THEN 1 WHEN 'pendiente' THEN 0 ELSE 0 END AS justificado,
                j.motivo,
                tj.nombre AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                j.estatus AS estado_validacion_jefe,
                NULL AS motivo_validacion_jefe,
                NULL AS comentarios_validacion_jefe,
                j.created_at AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                j.estatus AS estado_validacion_rh,
                j.created_at AS fecha_validacion_rh,
                'justificacion' AS fuente,
                j.asistencia_id
            FROM justificaciones j
            LEFT JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
            LEFT JOIN usuarios ua ON ua.id = j.aprobado_por
            WHERE j.empleado_id = ?
            ORDER BY j.fecha_inicio DESC
        ");
        $stmtJustificaciones->execute([$empleado_id]);
        $justificaciones = array_merge($justificaciones, $stmtJustificaciones->fetchAll());

        // 3. VACACIONES
        $stmtVacaciones = $pdo->prepare("
            SELECT
                v.id,
                v.empleado_id,
                v.fecha_inicio AS fecha,
                NULL AS hora_entrada,
                NULL AS hora_salida,
                v.dias_solicitados AS minutos_retardo,
                'vacaciones' AS tipo_incidencia,
                CASE v.estatus WHEN 'aprobada' THEN 1 WHEN 'pendiente' THEN 0 ELSE 0 END AS justificado,
                CONCAT('Vacaciones: ', v.fecha_inicio, ' al ', v.fecha_fin, ' (', v.dias_solicitados, ' días)') AS descripcion,
                tj.nombre AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                v.estatus AS estado_validacion_jefe,
                NULL AS motivo_validacion_jefe,
                NULL AS comentarios_validacion_jefe,
                v.fecha_aprobacion AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                v.estatus AS estado_validacion_rh,
                v.fecha_aprobacion AS fecha_validacion_rh,
                'vacaciones' AS fuente,
                v.asistencia_id
            FROM vacaciones v
            LEFT JOIN tipos_justificacion tj ON v.tipo_justificacion_id = tj.id
            LEFT JOIN usuarios ua ON ua.id = v.aprobado_por
            WHERE v.empleado_id = ?
            ORDER BY v.fecha_inicio DESC
        ");
        $stmtVacaciones->execute([$empleado_id]);
        $justificaciones = array_merge($justificaciones, $stmtVacaciones->fetchAll());

        // 4. CUIDADOS MATERNOS/PATERNOS
        $stmtCuidados = $pdo->prepare("
            SELECT
                cm.id,
                cm.empleado_id,
                cm.fecha_inicio AS fecha,
                NULL AS hora_entrada,
                NULL AS hora_salida,
                cm.dias_solicitados AS minutos_retardo,
                cm.tipo_cuidado AS tipo_incidencia,
                CASE cm.estatus WHEN 'aprobada' THEN 1 WHEN 'pendiente' THEN 0 ELSE 0 END AS justificado,
                CONCAT('Cuidados ', cm.tipo_cuidado, ': ', cm.fecha_inicio, ' al ', cm.fecha_fin, ' (', cm.dias_solicitados, ' días)') AS descripcion,
                tj.nombre AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                cm.estatus AS estado_validacion_jefe,
                NULL AS motivo_validacion_jefe,
                NULL AS comentarios_validacion_jefe,
                cm.fecha_aprobacion AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                cm.estatus AS estado_validacion_rh,
                cm.fecha_aprobacion AS fecha_validacion_rh,
                'cuidados_maternos' AS fuente,
                cm.asistencia_id
            FROM cuidados_maternos cm
            LEFT JOIN tipos_justificacion tj ON cm.tipo_justificacion_id = tj.id
            LEFT JOIN usuarios ua ON ua.id = cm.aprobado_por
            WHERE cm.empleado_id = ?
            ORDER BY cm.fecha_inicio DESC
        ");
        $stmtCuidados->execute([$empleado_id]);
        $justificaciones = array_merge($justificaciones, $stmtCuidados->fetchAll());

        // 5. DÍAS ECONÓMICOS
        $stmtDiasEco = $pdo->prepare("
            SELECT
                de.id,
                de.empleado_id,
                de.fecha,
                NULL AS hora_entrada,
                NULL AS hora_salida,
                de.dias_solicitados AS minutos_retardo,
                'dia_economico' AS tipo_incidencia,
                CASE de.estatus WHEN 'aprobado' THEN 1 WHEN 'pendiente' THEN 0 ELSE 0 END AS justificado,
                COALESCE(de.motivo, CONCAT('Día económico: ', de.fecha)) AS descripcion,
                'Día económico' AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                CASE de.estatus WHEN 'aprobado' THEN 'aprobada' ELSE 'pendiente' END AS estado_validacion_jefe,
                NULL AS motivo_validacion_jefe,
                NULL AS comentarios_validacion_jefe,
                de.fecha_aprobacion AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                CASE de.estatus WHEN 'aprobado' THEN 'aprobada' ELSE 'pendiente' END AS estado_validacion_rh,
                de.fecha_aprobacion AS fecha_validacion_rh,
                'dia_economico' AS fuente,
                de.asistencia_id
            FROM dias_economicos de
            LEFT JOIN usuarios ua ON ua.id = de.aprobado_por
            WHERE de.empleado_id = ?
            ORDER BY de.fecha DESC
        ");
        $stmtDiasEco->execute([$empleado_id]);
        $justificaciones = array_merge($justificaciones, $stmtDiasEco->fetchAll());

        // 6. RETARDOS (de tabla retardos)
        $stmtRetardos = $pdo->prepare("
            SELECT
                r.id,
                r.empleado_id,
                r.fecha,
                r.hora_entrada,
                r.hora_salida,
                r.minutos_retardo,
                r.tipo_retraso AS tipo_incidencia,
                r.tipo_retraso AS tipo_retraso,
                r.justificado,
                r.motivo_justificacion AS descripcion,
                tj.nombre AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                CASE WHEN r.justificado = 1 THEN 'aprobada' ELSE 'pendiente' END AS estado_validacion_jefe,
                NULL AS motivo_validacion_jefe,
                NULL AS comentarios_validacion_jefe,
                r.fecha_aprobacion AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                CASE WHEN r.justificado = 1 THEN 'aprobada' ELSE 'pendiente' END AS estado_validacion_rh,
                r.fecha_aprobacion AS fecha_validacion_rh,
                'retardos' AS fuente,
                r.asistencia_id
            FROM retardos r
            LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
            LEFT JOIN usuarios ua ON ua.id = r.aprobado_por
            WHERE r.empleado_id = ?
            ORDER BY r.fecha DESC
        ");
        $stmtRetardos->execute([$empleado_id]);
        $justificaciones = array_merge($justificaciones, $stmtRetardos->fetchAll());

        // 7. LICENCIAS MÉDICAS (de tabla licencias_medicas)
        $stmtLicencias = $pdo->prepare("
            SELECT
                lm.id,
                lm.empleado_id,
                lm.fecha_inicio AS fecha,
                NULL AS hora_entrada,
                NULL AS hora_salida,
                lm.dias_otorgados AS minutos_retardo,
                'licencia_medica' AS tipo_incidencia,
                CASE lm.estatus WHEN 'aprobada' THEN 1 WHEN 'pendiente' THEN 0 ELSE 0 END AS justificado,
                CONCAT('Licencia médica: ', COALESCE(lm.diagnostico, 'Sin diagnóstico')) AS descripcion,
                'Licencia médica' AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                lm.estatus AS estado_validacion_jefe,
                NULL AS motivo_validacion_jefe,
                NULL AS comentarios_validacion_jefe,
                lm.updated_at AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                lm.estatus AS estado_validacion_rh,
                lm.updated_at AS fecha_validacion_rh,
                'licencia_medica' AS fuente,
                lm.incidencia_id AS asistencia_id
            FROM licencias_medicas lm
            LEFT JOIN usuarios ua ON ua.id = lm.creado_por
            WHERE lm.empleado_id = ?
            ORDER BY lm.fecha_inicio DESC
        ");
        $stmtLicencias->execute([$empleado_id]);
        $justificaciones = array_merge($justificaciones, $stmtLicencias->fetchAll());

        // 8. CONSTANCIAS DE TIEMPO (de tabla constancias_tiempo)
        $stmtConstancias = $pdo->prepare("
            SELECT
                ct.id,
                ct.empleado_id,
                ct.fecha_inicio AS fecha,
                NULL AS hora_entrada,
                NULL AS hora_salida,
                NULL AS minutos_retardo,
                'constancia_tiempo' AS tipo_incidencia,
                CASE ct.estatus WHEN 'aprobada' THEN 1 WHEN 'pendiente' THEN 0 ELSE 0 END AS justificado,
                COALESCE(ct.motivo, 'Constancia de tiempo') AS descripcion,
                'Constancia de tiempo' AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                ct.estatus AS estado_validacion_jefe,
                NULL AS motivo_validacion_jefe,
                NULL AS comentarios_validacion_jefe,
                ct.fecha_aprobacion AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                ct.estatus AS estado_validacion_rh,
                ct.fecha_aprobacion AS fecha_validacion_rh,
                'constancia_tiempo' AS fuente,
                NULL AS asistencia_id
            FROM constancias_tiempo ct
            LEFT JOIN usuarios ua ON ua.id = ct.aprobado_por
            WHERE ct.empleado_id = ?
            ORDER BY ct.fecha_inicio DESC
        ");
        $stmtConstancias->execute([$empleado_id]);
        $justificaciones = array_merge($justificaciones, $stmtConstancias->fetchAll());

        // Ordenar por fecha descendente
        usort($justificaciones, function($a, $b) {
            $fa = strtotime($a['fecha'] ?? '1970-01-01');
            $fb = strtotime($b['fecha'] ?? '1970-01-01');
            if ($fa === $fb) {
                return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
            }
            return $fb <=> $fa;
        });

        return $justificaciones;
    }

    /**
     * Obtiene SOLO los retardos del empleado (NO otras justificaciones)
     */
    public function getRetardosByEmpleado($empleado_id, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $stmt = $pdo->prepare("
            SELECT
                r.id,
                r.empleado_id,
                r.fecha AS fecha,
                r.hora_entrada,
                r.hora_salida,
                r.minutos_retardo,
                r.tipo_retraso AS tipo_incidencia,
                r.justificado,
                r.motivo_justificacion AS descripcion,
                tj.nombre AS tipo_justificacion_nombre,
                1 AS validado_por_jefe,
                CASE WHEN r.justificado = 1 THEN 'aprobada' ELSE 'pendiente' END AS estado_validacion_jefe,
                r.fecha_aprobacion AS fecha_validacion_jefe,
                ua.nombre_completo AS jefe_validador_nombre,
                'RH' AS validado_por_rh,
                CASE WHEN r.justificado = 1 THEN 'aprobada' ELSE 'pendiente' END AS estado_validacion_rh,
                r.fecha_aprobacion AS fecha_validacion_rh,
                'retardo' AS fuente,
                r.asistencia_id
            FROM retardos r
            LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
            LEFT JOIN usuarios ua ON ua.id = r.aprobado_por
            WHERE r.empleado_id = ?
            ORDER BY r.fecha DESC
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    /**
     * Alias para compatibilidad hacia atrás
     */
    public function getIncidenciasAsistenciaByEmpleado($empleado_id, $pdo = null) {
        return $this->getIncidenciasByEmpleado($empleado_id, $pdo);
    }

    /**
     * Alias para compatibilidad hacia atrás
     */
    public function getRetardosConValidacionByEmpleado($empleado_id, $pdo = null) {
        return $this->getRetardosByEmpleado($empleado_id, $pdo);
    }

    public function justificarRetardo($id, $tipo_justificacion_id = null, $motivo = null, $aprobado_por = null, $soporte = null, $pdo = null, $validado_por_jefe = 0) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        
        // 0 = pendiente de validación, 1 = validado, -1 = no requiere validación
        if ($validado_por_jefe === null) {
            $validado_por_jefe = -1; // No requiere validación
        }
        
        $stmt = $pdo->prepare(
            "UPDATE retardos SET
                justificado = 1,
                tipo_justificacion_id = ?,
                motivo_justificacion = ?,
                aprobado_por = ?,
                fecha_aprobacion = NOW(),
                soporte = ?,
                validado_por_jefe = ?
            WHERE id = ?"
        );
        return $stmt->execute([$tipo_justificacion_id, $motivo, $aprobado_por, $soporte, $validado_por_jefe, $id]);
    }

    public function getTotalRetardos($empleado_id, $mes = null, $anio = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $query = "SELECT COUNT(*) as total, tipo_retraso as tipo FROM retardos";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($mes && $anio) {
            $query .= ($params ? " AND" : " WHERE") . " MONTH(fecha) = ? AND YEAR(fecha) = ?";
            $params[] = $mes;
            $params[] = $anio;
        }

        $query .= " GROUP BY tipo_retraso";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        
        // Normalizar tipo_retraso a tipo para compatibilidad con vistas
        foreach ($rows as &$row) {
            if (!isset($row['tipo']) && isset($row['tipo_retraso'])) {
                $row['tipo'] = $row['tipo_retraso'];
            }
        }
        return $rows;
    }

    /**
     * Cuenta las notas (retardos no justificados) en un mes/año
     */
    public function contarNotasMes($empleado_id, $mes, $anio, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total FROM retardos
            WHERE empleado_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? AND justificado = 0"
        );
        $stmt->execute([$empleado_id, $mes, $anio]);
        $r = $stmt->fetch();
        return intval($r['total'] ?? 0);
    }

    /**
     * Si el empleado alcanzó 5 notas en el mes, crear una sanción de suspensión por 1 día
     */
    public function crearSuspensionSiCorresponde($empleado_id, $mes, $anio, $creado_por = null, $pdo = null) {
        $cantidad = $this->contarNotasMes($empleado_id, $mes, $anio, $pdo);
        if ($cantidad >= 5) {
            require_once __DIR__ . '/Sancion.php';
            $sancionModel = new Sancion();

            // Verificar que no exista ya una sanción por este mes
            $sanciones = $sancionModel->getByEmpleadoYear($empleado_id, $anio, $pdo);
            foreach ($sanciones as $s) {
                if ($s['tipo'] === 'suspension' && date('m', strtotime($s['fecha_inicio'])) == str_pad($mes,2,'0',STR_PAD_LEFT)) {
                    return false; // ya existe sanción para este mes
                }
            }

            $fecha_inicio = date('Y-m-d');
            $data = [
                'empleado_id' => $empleado_id,
                'tipo' => 'suspension',
                'fecha_inicio' => $fecha_inicio,
                'dias' => 1,
                'motivo' => 'Suspensión automática por acumulación de 5 notas negativas en el mes',
                'creado_por' => $creado_por
            ];

            return $sancionModel->create($data, $pdo);
        }

        return false;
    }

    /**
     * Aplicar norma AEFCM: dos retardos menores consecutivos se convierten en mayor
     */
    public function aplicarNormaAEFCM($empleado_id, $fecha, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        // Buscar los últimos dos retardos menores no justificados
        $stmt = $pdo->prepare(
            "SELECT id, fecha FROM retardos
            WHERE empleado_id = ? AND tipo_retraso = 'menor' AND justificado = 0
            ORDER BY fecha DESC
            LIMIT 2"
        );
        $stmt->execute([$empleado_id]);
        $retardos = $stmt->fetchAll();

        if (count($retardos) >= 2) {
            // Verificar si son consecutivos (sin días hábiles entre ellos)
            $fecha1 = strtotime($retardos[0]['fecha']);
            $fecha2 = strtotime($retardos[1]['fecha']);

            // Calcular días hábiles entre las fechas
            $dias_diferencia = $this->calcularDiasHabiles($fecha2, $fecha1);

            if ($dias_diferencia <= 1) { // Consecutivos o mismo día
                // Convertir el más reciente a mayor
                $stmt = $pdo->prepare("UPDATE retardos SET tipo_retraso = 'mayor' WHERE id = ?");
                $stmt->execute([$retardos[0]['id']]);

                return true; // Norma aplicada
            }
        }

        return false; // Norma no aplicada
    }

    /**
     * Calcular días hábiles entre dos fechas (excluyendo fines de semana)
     */
    private function calcularDiasHabiles($fecha_inicio, $fecha_fin) {
        $dias = 0;
        $current = $fecha_inicio;

        while ($current <= $fecha_fin) {
            $dia_semana = date('N', $current); // 1=Lunes, 7=Domingo
            if ($dia_semana <= 5) { // Lunes a Viernes
                $dias++;
            }
            $current = strtotime('+1 day', $current);
        }

        return $dias - 1; // Restar 1 porque no contamos el día inicial
    }

    /**
     * Contar retardos justificados en una quincena específica
     * @param int $empleado_id ID del empleado
     * @param string $fecha Fecha del retardo para calcular la quincena
     * @return int Número de retardos justificados en esa quincena
     */
    public function getCountJustificadosQuincena($empleado_id, $fecha, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        
        // Calcular inicio y fin de la quincena
        $dia = (int)date('d', strtotime($fecha));
        $mes = date('m', strtotime($fecha));
        $anio = date('Y', strtotime($fecha));
        
        if ($dia <= 15) {
            $inicio_quincena = "$anio-$mes-01";
            $fin_quincena = "$anio-$mes-15";
        } else {
            $inicio_quincena = "$anio-$mes-16";
            $ultimo_dia = date('t', strtotime($fecha));
            $fin_quincena = "$anio-$mes-$ultimo_dia";
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM retardos 
            WHERE empleado_id = ? 
            AND justificado = 1 
            AND tipo_retraso IN ('retardo_menor', 'retardo_mayor')
            AND fecha >= ? 
            AND fecha <= ?
        ");
        $stmt->execute([$empleado_id, $inicio_quincena, $fin_quincena]);
        $result = $stmt->fetch();
        
        return (int)$result['total'];
    }

    /**
     * Bloquear justificación de un retardo (cuando se convierte en sanción)
     */
    public function bloquearJustificacion($id, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("UPDATE retardos SET justificacion_bloqueada = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Obtener retardos pendientes de justificación
     */
    public function getPendientesJustificacion($empleado_id = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $query = "
            SELECT r.*, e.nombre, e.apellido, tj.nombre as tipo_justificacion
            FROM retardos r
            JOIN empleados e ON r.empleado_id = e.id
            LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
            WHERE r.justificado = 0 AND (r.justificacion_bloqueada IS NULL OR r.justificacion_bloqueada = 0)
        ";
        $params = [];

        if ($empleado_id) {
            $query .= " AND r.empleado_id = ?";
            $params[] = $empleado_id;
        }

        $query .= " ORDER BY r.fecha DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtener retardo por nombre de archivo de soporte (si existe)
     */
    public function getBySoporteFilename($filename, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("SELECT * FROM retardos WHERE soporte LIKE ? LIMIT 1");
        $like = '%' . $filename;
        $stmt->execute([$like]);
        return $stmt->fetch();
    }

    /**
     * Contar retardos acumulados en la quincena actual para un empleado
     * Quincena: del 1 al 15 o del 16 al último día del mes
     */
    public function getRetardosAcumuladosQuincena($empleado_id, $fecha = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $fecha = $fecha ?: date('Y-m-d');
        $dia = (int)date('d', strtotime($fecha));

        // Determinar inicio y fin de la quincena
        if ($dia <= 15) {
            $inicio_quincena = date('Y-m-01', strtotime($fecha));
            $fin_quincena = date('Y-m-15', strtotime($fecha));
        } else {
            $inicio_quincena = date('Y-m-16', strtotime($fecha));
            $fin_quincena = date('Y-m-t', strtotime($fecha)); // Último día del mes
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total FROM retardos
            WHERE empleado_id = ? AND fecha BETWEEN ? AND ? AND justificado = 0"
        );
        $stmt->execute([$empleado_id, $inicio_quincena, $fin_quincena]);
        $result = $stmt->fetch();
        return (int)$result['total'];
    }

    /**
     * Contar retardos JUSTIFICADOS acumulados en la quincena actual para un empleado
     */
    public function getRetardosJustificadosQuincena($empleado_id, $fecha = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $fecha = $fecha ?: date('Y-m-d');
        $dia = (int)date('d', strtotime($fecha));

        if ($dia <= 15) {
            $inicio_quincena = date('Y-m-01', strtotime($fecha));
            $fin_quincena = date('Y-m-15', strtotime($fecha));
        } else {
            $inicio_quincena = date('Y-m-16', strtotime($fecha));
            $fin_quincena = date('Y-m-t', strtotime($fecha));
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total FROM retardos
            WHERE empleado_id = ? AND fecha BETWEEN ? AND ? AND justificado = 1"
        );
        $stmt->execute([$empleado_id, $inicio_quincena, $fin_quincena]);
        $result = $stmt->fetch();
        return (int)$result['total'];
    }

    /**
     * Determinar el tipo de retardo según los minutos de retardo
     * Retardo menor: 11-20 minutos (después de los 10 minutos de tolerancia)
     * Retardo mayor: 21-30 minutos
     * Falta: 31+ minutos
     * @param int $minutos Minutos de retardo
     * @return string|null Tipo de retardo: 'retardo_menor', 'retardo_mayor', 'falta', o null si ≤10
     */
    public function determinarTipoRetardo($minutos) {
        $minutos = (int)$minutos;
        
        if ($minutos > 10 && $minutos <= 20) {
            return 'retardo_menor';
        } elseif ($minutos > 20 && $minutos <= 30) {
            return 'retardo_mayor';
        } elseif ($minutos > 30) {
            return 'falta';
        }
        
        return null;
    }

    /**
     * Calcular notas malas acumuladas en un mes
     * Reglas SEP para cálculo de notas malas:
     * - 4 notas malas sin justificar = sanción (oficio)
     * - 5 notas malas = 1 día de suspensión
     * @param int $empleado_id ID del empleado
     * @param int $mes Mes (1-12)
     * @param int $anio Año
     * @param int $quincena Quincena (1 = 1-15, 2 = 16-31, 0 = todo el mes)
     * @return array ['notas_malas' => int, 'retardos_menores' => int, 'retardos_mayores' => int, ...]
     */
    public function calcularNotasMalas($empleado_id, $mes, $anio, $quincena = 0, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);
        
        // Control por quincena:
        // - Quincena 1: considerar solo fechas del 1 al 15
        // - Quincena 2: considerar solo fechas del 16 al 30
        if ($quincena === 1) {
            $inicio = "$anio-$mes-01";
            $fin = "$anio-$mes-15";
        } elseif ($quincena === 2) {
            $inicio = "$anio-$mes-16";
            $fin = date('Y-m-t', strtotime("$anio-$mes-01"));
        } else {
            $inicio = "$anio-$mes-01";
            $fin = date('Y-m-t', strtotime("$anio-$mes-01"));
        }

        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN tipo_retraso = 'retardo_menor' AND justificado = 0 THEN 1 ELSE 0 END) as menores,
                SUM(CASE WHEN tipo_retraso = 'retardo_mayor' AND justificado = 0 THEN 1 ELSE 0 END) as mayores,
                SUM(CASE WHEN tipo_retraso = 'falta' AND justificado = 0 THEN 1 ELSE 0 END) as faltas
            FROM retardos
            WHERE empleado_id = ? 
            AND fecha BETWEEN ? AND ?
            AND justificado = 0
        ");
        $stmt->execute([$empleado_id, $inicio, $fin]);
        $result = $stmt->fetch();

        $retardos_menores = (int)($result['menores'] ?? 0);
        $retardos_mayores = (int)($result['mayores'] ?? 0);
        $faltas = (int)($result['faltas'] ?? 0);

        // Las faltas (31+ minutos) cuentan como retardo mayor
        $retardos_mayores += $faltas;

        // Total de retardos no justificados en la quincena
        $total_retardos = $retardos_menores + $retardos_mayores;
        
        // Las notas malas se calculan con la fórmula: 2 menores = 1 nota mala, 1 mayor = 1 nota mala
        $notas_por_menores = floor($retardos_menores / 2);
        $notas_por_mayores = $retardos_mayores;
        $notas_malas = $notas_por_menores + $notas_por_mayores;
        
        // Regla: 1-4 notas malas = oficio
        $requiere_oficio = $notas_malas >= 1 && $notas_malas <= 4;
        // Regla: 5+ notas malas = suspensión
        $requiere_suspension = $notas_malas >= 5;
        $requiere_sancion = false;

        return [
            'notas_malas' => $notas_malas,
            'retardos_menores' => $retardos_menores,
            'retardos_mayores' => $retardos_mayores,
            'faltas' => $faltas,
            'total_retardos' => $total_retardos,
            'resto_menores' => $retardos_menores % 2,
            'requiere_oficio' => $requiere_oficio,
            'requiere_sancion' => $requiere_sancion,
            'requiere_suspension' => $requiere_suspension
        ];
    }
    
    /**
     * Evaluar si el empleado pierde el derecho al premio de puntualidad
     * Regla: Máximo 2 retardos por quincena. Si se sobrepasan, pierde derecho al premio.
     * 
     * @param int $empleado_id ID del empleado
     * @param int $mes Mes (1-12)
     * @param int $anio Año
     * @param int $quincena Quincena (1 = 1-15, 2 = 16-31)
     * @return array ['pierde_puntualidad' => bool, 'total_retardos' => int, 'excedentes' => int, 'detalle' => string]
     */
    public function evaluarPremioPuntualidad($empleado_id, $mes, $anio, $quincena) {
        $pdo = $this->db->getConnection();
        
        $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);
        
        // Definir rango de fechas según quincena
        if ($quincena === 1) {
            $inicio = "$anio-$mes-01";
            $fin = "$anio-$mes-15";
            $nombre_quincena = "PRIMERA QUINCENA (1-15)";
        } else {
            $inicio = "$anio-$mes-16";
            $fin = date('Y-m-t', strtotime("$anio-$mes-01"));
            $nombre_quincena = "SEGUNDA QUINCENA (16-30)";
        }
        
        // Contar TODOS los retardos no justificados en la quincena (menores, mayores, faltas)
        $stmt = $pdo->prepare("
            SELECT fecha, hora_entrada, minutos_retardo, tipo_retraso,
                   CASE WHEN justificado = 1 THEN 'Sí' ELSE 'No' END as justificado_texto
            FROM retardos
            WHERE empleado_id = ? 
            AND fecha BETWEEN ? AND ?
            ORDER BY fecha ASC
        ");
        $stmt->execute([$empleado_id, $inicio, $fin]);
        $retardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_retardos = count($retardos);
        $retardos_no_justificados = array_filter($retardos, function($r) {
            return $r['justificado_texto'] === 'No';
        });
        
        $LIMITE_RETARDOS_POR_QUINCENA = 2;
        $excedentes = max(0, $total_retardos - $LIMITE_RETARDOS_POR_QUINCENA);
        $pierde_puntualidad = $total_retardos > $LIMITE_RETARDOS_POR_QUINCENA;
        
        // Construir detalle para el reporte
        $detalle_retardos = [];
        foreach ($retardos as $r) {
            $detalle_retardos[] = sprintf(
                "- %s | Hora: %s | Minutos: %d | Tipo: %s | Justificado: %s",
                $r['fecha'],
                substr($r['hora_entrada'], 0, 5),
                $r['minutos_retardo'],
                strtoupper($r['tipo_retraso']),
                $r['justificado_texto']
            );
        }
        
        $detalle = sprintf(
            "REPORTE DE RETARDOS - %s de %s\n" .
            "Empleado ID: %d\n" .
            "Total retardos en quincena: %d (Límite permitido: %d)\n" .
            "Retardos excedentes: %d\n" .
            "DERECHO A PREMIO DE PUNTUALIDAD: %s\n\n" .
            "DETALLE DE RETARDOS:\n%s",
            $nombre_quincena,
            strftime('%B %Y', strtotime("$anio-$mes-01")),
            $empleado_id,
            $total_retardos,
            $LIMITE_RETARDOS_POR_QUINCENA,
            $excedentes,
            $pierde_puntualidad ? 'NO - SE EXCEDIÓ DEL LÍMITE DE 2 RETARDOS' : 'SÍ',
            implode("\n", $detalle_retardos)
        );
        
        return [
            'pierde_puntualidad' => $pierde_puntualidad,
            'total_retardos' => $total_retardos,
            'excedentes' => $excedentes,
            'limite' => $LIMITE_RETARDOS_POR_QUINCENA,
            'quincena' => $quincena,
            'mes' => $mes,
            'anio' => $anio,
            'nombre_quincena' => $nombre_quincena,
            'detalle' => $detalle,
            'retardos' => $retardos,
            'retardos_no_justificados' => count($retardos_no_justificados)
        ];
    }

    /**
     * Evaluar Regla 3: De 1 a 4 notas malas = oficio de notas malas
     * Regla 4: 5 notas malas = suspensión
     * Regla 5: 7 suspensiones al año = término de nombramiento
     * @param int $empleado_id ID del empleado
     * @param int $mes Mes (1-12)
     * @param int $anio Año
     * @param int $quincena Quincena (1, 2 o 0 para todo el mes)
     * @return array ['sancion_creada' => bool, 'tipo' => string, 'mensaje' => string]
     */
    public function evaluarYSancionar($empleado_id, $mes, $anio, $quincena = 0) {
        $notas = $this->calcularNotasMalas($empleado_id, $mes, $anio, $quincena);
        
        $resultadoReg = $this->registrarNotasMalasPorRetardos($empleado_id, $mes, $anio, $quincena);
        
        // Primero evaluar Regla 5: 7 suspensiones al año = término de nombramiento
        $resultadoRegla5 = $this->evaluarRegla5TerminoNombramiento($empleado_id, $anio);
        if ($resultadoRegla5['sancion_creada']) {
            return $resultadoRegla5;
        }
        
        // Evaluar Regla 4: 5 notas malas = suspensión
        if ($notas['requiere_suspension']) {
            require_once __DIR__ . '/Sancion.php';
            $sancionModel = new Sancion();
            
            $yaTieneSuspension = $this->verificarSancionExistente($empleado_id, $mes, $anio, 'suspension');
            
            if (!$yaTieneSuspension) {
                $sancionData = [
                    'empleado_id' => $empleado_id,
                    'tipo' => 'suspension',
                    'tipo_retardo' => 'notas_malas',
                    'motivo' => "Acumulación de {$notas['notas_malas']} notas malas en el mes. {$notas['retardos_menores']} retardos menores, {$notas['retardos_mayores']} retardos mayores.",
                    'fecha_inicio' => date('Y-m-d'),
                    'dias' => 1,
                    'creado_por' => 1
                ];
                
                try {
                    $sancionModel->create($sancionData);
                    return [
                        'sancion_creada' => true,
                        'tipo' => 'suspension',
                        'mensaje' => "Se generó suspensión de 1 día por {$notas['notas_malas']} notas malas (Regla 4)"
                    ];
                } catch (Exception $e) {
                    return [
                        'sancion_creada' => false,
                        'tipo' => 'suspension',
                        'mensaje' => 'Error al crear suspensión: ' . $e->getMessage()
                    ];
                }
            }
        }
        
        // Evaluar Regla 3: 1-4 notas malas = oficio de notas malas
        if ($notas['requiere_oficio']) {
            return [
                'sancion_creada' => false,
                'tipo' => 'oficio',
                'mensaje' => "El empleado tiene {$notas['notas_malas']} nota(s) mala(s). Se debe generar oficio de notas malas (Regla 3)."
            ];
        }
        
        return [
            'sancion_creada' => false,
            'tipo' => null,
            'mensaje' => 'No se requieren sanciones'
        ];
    }
    
    /**
     * Evaluar Regla 5: 7 suspensiones al año = término de nombramiento
     * @param int $empleado_id ID del empleado
     * @param int $anio Año
     * @return array ['sancion_creada' => bool, 'tipo' => string, 'mensaje' => string]
     */
    private function evaluarRegla5TerminoNombramiento($empleado_id, $anio) {
        $pdo = $this->db->getConnection();
        
        // Contar suspensiones en el año por notas malas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM sanciones 
            WHERE empleado_id = ? 
            AND YEAR(fecha_inicio) = ?
            AND tipo = 'suspension'
            AND (tipo_retardo = 'notas_malas' OR tipo_retardo IS NULL OR tipo_retardo = '')
        ");
        $stmt->execute([$empleado_id, $anio]);
        $result = $stmt->fetch();
        $suspensiones_anio = (int)($result['total'] ?? 0);
        
        // Regla 5: 7 o más suspensiones = término de nombramiento
        if ($suspensiones_anio >= 7) {
            require_once __DIR__ . '/Sancion.php';
            $sancionModel = new Sancion();
            
            $sancionData = [
                'empleado_id' => $empleado_id,
                'tipo' => 'termino',
                'tipo_retardo' => 'notas_malas',
                'motivo' => "Término de nombramiento por acumular {$suspensiones_anio} suspensiones en el año {$anio} (Regla 5: 7 suspensiones = término de nombramiento).",
                'fecha_inicio' => date('Y-m-d'),
                'dias' => 0,
                'creado_por' => 1
            ];
            
            try {
                $sancionModel->create($sancionData);
                return [
                    'sancion_creada' => true,
                    'tipo' => 'termino',
                    'mensaje' => "Se generó término de nombramiento por {$suspensiones_anio} suspensiones en el año (Regla 5)"
                ];
            } catch (Exception $e) {
                return [
                    'sancion_creada' => false,
                    'tipo' => 'termino',
                    'mensaje' => 'Error al crear término: ' . $e->getMessage()
                ];
            }
        }
        
        return [
            'sancion_creada' => false,
            'tipo' => null,
            'mensaje' => 'No se requiere término de nombramiento'
        ];
    }

    /**
     * Verificar si ya existe una sanción para el empleado en el mes
     */
    private function verificarSancionExistente($empleado_id, $mes, $anio, $tipo) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM sanciones 
            WHERE empleado_id = ? 
            AND MONTH(fecha_inicio) = ?
            AND YEAR(fecha_inicio) = ?
            AND tipo = ?
        ");
        $stmt->execute([$empleado_id, $mes, $anio, $tipo]);
        $result = $stmt->fetch();
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Verificar si un retardo/comisión puede ser justificado según la fecha
     * Reglas:
     * - Retardos (regla 6): hasta el día 15 y 30 de cada mes
     * - Sin entrada (regla 7): hasta 2 días hábiles posteriores a la fecha de entrada
     * - Sin salida (regla 8): hasta 2 días hábiles posteriores a la fecha de salida
     * - Sin entrada y salida (regla 9): hasta 2 días hábiles posteriores a la fecha sin registro
     * @param string $fecha_retardo Fecha del retardo/incidencia (Y-m-d)
     * @param string $tipo Tipo: 'retardo_menor', 'retardo_mayor', 'comision_entrada', 'comision_salida', 'comision_todo_dia'
     * @return array ['puede_justificar' => bool, 'mensaje' => string, 'dias_restantes' => int]
     */
    public function puedeJustificar($fecha_retardo, $tipo = 'retardo') {
        $fecha_retardo = date('Y-m-d', strtotime($fecha_retardo));
        $fecha_actual = date('Y-m-d');
        
        $dia_retardo = (int)date('d', strtotime($fecha_retardo));
        $dia_actual = (int)date('d', strtotime($fecha_actual));
        $mes_retardo = (int)date('m', strtotime($fecha_retardo));
        $mes_actual = (int)date('m', strtotime($fecha_actual));
        
        // No se pueden justificar retardos de meses anteriores
        if ($mes_retardo !== $mes_actual) {
            return [
                'puede_justificar' => false,
                'mensaje' => 'El retardo es de un mes anterior y no puede ser justificado.',
                'dias_restantes' => 0
            ];
        }

        // Determinar tipo de incidencia
        $esComisionEntrada = ($tipo === 'comision_entrada');
        $esComisionSalida = ($tipo === 'comision_salida');
        $esComisionTodoDia = ($tipo === 'comision_todo_dia');
        $esComision = $esComisionEntrada || $esComisionSalida || $esComisionTodoDia;
        $esRetardo = in_array($tipo, ['retardo_menor', 'retardo_mayor', 'normal']);
        
if ($esRetardo) {
            // Regla 6: Retardos hasta día 15 y 30
            $quincena_retardo = ($dia_retardo <= 15) ? 1 : 2;
            $limite = ($quincena_retardo === 1) ? '15' : '30';
            $limite_fecha = sprintf('%s-%02d-%02d', date('Y'), $mes_retardo, (int)$limite);
            $mensajeBase = "período: hasta el día $limite";
        } elseif ($esComision) {
            // Reglas 7, 8, 9: 2 días hábiles posteriores a la fecha de incidencia
            $limite = $this->sumarDiasHabiles($fecha_retardo, 2);
            $limite_dia = (int)date('d', strtotime($limite));
            $limite_mes = (int)date('m', strtotime($limite));
            
            if ($esComisionEntrada) {
                $mensajeBase = "período: hasta 2 días hábiles posteriores a la fecha de entrada (límite: $limite_dia/$limite_mes)";
            } elseif ($esComisionSalida) {
                $mensajeBase = "período: hasta 2 días hábiles posteriores a la fecha de salida (límite: $limite_dia/$limite_mes)";
            } else {
                $mensajeBase = "período: hasta 2 días hábiles posteriores a la fecha sin registro (límite: $limite_dia/$limite_mes)";
            }
        } else {
            // Por defecto, regla 6
            $quincena_retardo = ($dia_retardo <= 15) ? 1 : 2;
            $limite = ($quincena_retardo === 1) ? '15' : '30';
            $limite_fecha = sprintf('%s-%02d-%02d', date('Y'), $mes_retardo, (int)$limite);
            $mensajeBase = "período: hasta el día $limite";
        }
        
        // Calcular días restantes
        $dias_restantes = (strtotime($limite_fecha) - strtotime($fecha_actual)) / 86400;
        
        if ($dias_restantes < 0) {
            return [
                'puede_justificar' => false,
                'mensaje' => 'El período de justificación ha terminado (' . $mensajeBase . ').',
                'dias_restantes' => 0
            ];
        }

        return [
            'puede_justificar' => true,
            'mensaje' => "Puede justificar. Días restantes: " . floor($dias_restantes) . " ($mensajeBase)",
            'dias_restantes' => floor($dias_restantes),
            'limite' => $limite,
            'tipo' => $esComision ? 'comision' : 'retardo'
        ];
    }
    
    /**
     * Sumar días hábiles (excluyendo fines de semana) a una fecha
     * @param string $fecha Fecha inicial (Y-m-d)
     * @param int $dias Días hábiles a sumar
     * @return string Fecha resultante (Y-m-d)
     */
    private function sumarDiasHabiles($fecha, $dias) {
        $contador = 0;
        $fecha_actual = strtotime($fecha);
        
        while ($contador < $dias) {
            $fecha_actual = strtotime('+1 day', $fecha_actual);
            $dia_semana = date('N', $fecha_actual); // 1=Lunes, 7=Domingo
            if ($dia_semana <= 5) { // Lunes a viernes
                $contador++;
            }
        }
        
        return date('Y-m-d', $fecha_actual);
    }

    /**
     * Aplicar sanciones según las reglas de negocio
     * - 4 notas malas en el mes = sanción
     * - 5 notas malas en el mes = 1 día de suspensión
     * @param int $empleado_id ID del empleado
     * @param int $mes Mes (1-12)
     * @param int $anio Año
     * @param int $creado_por ID del usuario que crea la sanción
     * @param PDO $pdo Conexión de base de datos
     * @return array ['sancion_creada' => bool, 'tipo' => string, 'notas_malas' => int]
     */
    public function aplicarSancionesPorNotasMalas($empleado_id, $mes, $anio, $creado_por = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $notas = $this->calcularNotasMalas($empleado_id, $mes, $anio, 0, $pdo);
        $notas_malas = $notas['notas_malas'];

        if ($notas_malas < 4) {
            return ['sancion_creada' => false, 'tipo' => null, 'notas_malas' => $notas_malas];
        }

        require_once __DIR__ . '/Sancion.php';
        $sancionModel = new Sancion();

        $sanciones_mes = $sancionModel->getByEmpleadoYear($empleado_id, $anio, $pdo);
        $ya_tiene_suspension = false;
        $ya_tiene_sancion = false;

        foreach ($sanciones_mes as $s) {
            $mes_sancion = (int)date('m', strtotime($s['fecha_inicio']));
            if ($mes_sancion === (int)$mes) {
                if (($s['tipo_sancion'] ?? $s['tipo'] ?? '') === 'suspension') {
                    $ya_tiene_suspension = true;
                }
                if (($s['tipo_sancion'] ?? $s['tipo'] ?? '') === 'nota_mala') {
                    $ya_tiene_sancion = true;
                }
            }
        }

        $mes_nombre = date('F', mktime(0, 0, 0, $mes, 1));

        if ($notas_malas >= 5 && !$ya_tiene_suspension) {
            $sancionData = [
                'empleado_id' => $empleado_id,
                'tipo' => 'suspension',
                'tipo_retardo' => 'notas_malas',
                'motivo' => "Suspensión automática por acumulación de $notas_malas notas malas en $mes_nombre. " . 
                           "Detalle: {$notas['retardos_menores']} retardos menores y {$notas['retardos_mayores']} retardos mayores.",
                'fecha_inicio' => date('Y-m-d'),
                'dias' => 1,
                'creado_por' => $creado_por
            ];
            $sancionModel->create($sancionData, $pdo);
            return ['sancion_creada' => true, 'tipo' => 'suspension', 'notas_malas' => $notas_malas];
        }

        if ($notas_malas >= 4 && !$ya_tiene_sancion && !$ya_tiene_suspension) {
            $sancionData = [
                'empleado_id' => $empleado_id,
                'tipo' => 'nota_mala',
                'tipo_retardo' => 'notas_malas',
                'motivo' => "Sanción automática por acumulación de $notas_malas notas malas en $mes_nombre. " . 
                           "Detalle: {$notas['retardos_menores']} retardos menores y {$notas['retardos_mayores']} retardos mayores.",
                'fecha_inicio' => date('Y-m-d'),
                'dias' => 0,
                'creado_por' => $creado_por
            ];
            $sancionModel->create($sancionData, $pdo);
            return ['sancion_creada' => true, 'tipo' => 'nota_mala', 'notas_malas' => $notas_malas];
        }

        return ['sancion_creada' => false, 'tipo' => null, 'notas_malas' => $notas_malas];
    }

    /**
     * Obtener control quincenal de retardos para un empleado
     * @param int $empleado_id ID del empleado
     * @param string $fecha Fecha de referencia (Y-m-d)
     * @return array
     */
    public function getControlQuincenal($empleado_id, $fecha = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $fecha = $fecha ?: date('Y-m-d');
        $dia = (int)date('d', strtotime($fecha));
        $mes = date('m', strtotime($fecha));
        $anio = date('Y', strtotime($fecha));

        if ($dia <= 15) {
            $inicio_quincena = "$anio-$mes-01";
            $fin_quincena = "$anio-$mes-15";
            $quincena = 1;
        } else {
            $inicio_quincena = "$anio-$mes-16";
            $fin_quincena = date('Y-m-t', strtotime($fecha));
            $quincena = 2;
        }

        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_retardos,
                SUM(CASE WHEN tipo_retraso = 'retardo_menor' AND justificado = 0 THEN 1 ELSE 0 END) as menores_sin_justificar,
                SUM(CASE WHEN tipo_retraso = 'retardo_mayor' AND justificado = 0 THEN 1 ELSE 0 END) as mayores_sin_justificar,
                SUM(CASE WHEN justificado = 1 THEN 1 ELSE 0 END) as justificados
            FROM retardos
            WHERE empleado_id = ? 
            AND fecha BETWEEN ? AND ?
        ");
        $stmt->execute([$empleado_id, $inicio_quincena, $fin_quincena]);
        $result = $stmt->fetch();

        $total = (int)($result['total_retardos'] ?? 0);
        $justificados = (int)($result['justificados'] ?? 0);
        $sin_justificar = $total - $justificados;

        $notas_malas = $this->calcularNotasMalas($empleado_id, (int)$mes, (int)$anio, $quincena, $pdo);

            return [
                'quincena' => $quincena,
                'periodo' => "$inicio_quincena al $fin_quincena",
                'total_retardos' => $total,
                'justificados' => $justificados,
                'sin_justificar' => $sin_justificar,
                'notas_malas_acumuladas' => $notas_malas['notas_malas'],
                'excede_limite' => $sin_justificar > 2,
                'limite' => 2
            ];
    }
    
    /**
     * Registra las notas malas derivadas de retardos no justificados
     * Se ejecuta después de calcular las notas para insertar en la tabla notas_malas
     * @param int $empleado_id ID del empleado
     * @param int $mes Mes (1-12)
     * @param int $anio Año
     * @param int $quincena Quincena (1, 2 o 0 para todo el mes)
     * @return array ['notas_insertadas' => int, 'notas_actualizadas' => int]
     */
    public function registrarNotasMalasPorRetardos($empleado_id, $mes, $anio, $quincena = 0) {
        $pdo = $this->db->getConnection();
        
        $inicio = $quincena == 1 ? "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01" : "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-16";
        $fin = $quincena == 1 ? "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-15" : date("Y-m-t", strtotime("$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01"));
        
        $stmt = $pdo->prepare("
            SELECT id, tipo_retraso, minutos_retardo
            FROM retardos
            WHERE empleado_id = ? 
            AND fecha BETWEEN ? AND ?
            AND justificado = 0
            ORDER BY fecha ASC, id ASC
        ");
        $stmt->execute([$empleado_id, $inicio, $fin]);
        $retardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $retardos_menores = 0;
        $retardos_mayores = 0;
        $notas_insertadas = 0;
        
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) as total FROM notas_malas WHERE retardo_id = ?");
        $stmtInsert = $pdo->prepare("
            INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($retardos as $retardo) {
            $tipo = $retardo['tipo_retraso'];
            $minutos = (int)($retardo['minutos_retardo'] ?? 0);
            
            if ($tipo === 'falta' || $minutos > 30) {
                $tipo_nota = 'retardo_mayor';
                $retardos_mayores++;
            } elseif ($tipo === 'retardo_mayor') {
                $tipo_nota = 'retardo_mayor';
                $retardos_mayores++;
            } else {
                $tipo_nota = 'retardo_menor';
                $retardos_menores++;
            }
            
            $stmtCheck->execute([$retardo['id']]);
            if ((int)($stmtCheck->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0) {
                continue;
            }
            
            $notas_por_tipo = 0;
            if ($tipo_nota === 'retardo_menor') {
                if ($retardos_menores % 2 === 0) {
                    $notas_por_tipo = 1;
                }
            } else {
                $notas_por_tipo = 1;
            }
            
            if ($notas_por_tipo > 0) {
                $motivo = "Acumulación de retardos no justificados: {$retardos_menores} menores, {$retardos_mayores} mayores.";
                $stmtInsert->execute([$empleado_id, $retardo['id'], $tipo_nota, $notas_por_tipo, $inicio, $motivo]);
                $notas_insertadas += $stmtInsert->rowCount();
            }
        }
        
        return [
            'notas_insertadas' => $notas_insertadas,
            'retardos_menores' => $retardos_menores,
            'retardos_mayores' => $retardos_mayores
        ];
    }
}
?>
