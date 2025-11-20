<?php
require_once 'Database.php';
require_once 'LogDispositivo.php';

class Asistencia {
    private $db;
    private $logDispositivo;

    public function __construct() {
        $this->db = new Database();
        $this->logDispositivo = new LogDispositivo();
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

        $result = $stmt->execute([
            $empleado_id,
            $dispositivo_id,
            $tipo_biometria,
            $datos_biometricos ? json_encode($datos_biometricos) : null,
            $calidad_verificacion,
            $metadata_dispositivo ? json_encode($metadata_dispositivo) : null,
            $tiempo_procesamiento
        ]);

        // Log del evento
        if ($result) {
            $this->logDispositivo->logEvent(
                $dispositivo_id,
                'verificacion',
                'exitoso',
                'Entrada registrada exitosamente',
                [
                    'tipo_asistencia' => 'entrada',
                    'calidad_verificacion' => $calidad_verificacion,
                    'tiempo_procesamiento' => $tiempo_procesamiento
                ],
                $empleado_id,
                $tipo_biometria
            );

            // Evaluar retardo/ausencia basados en horario
            $hora_actual = date('H:i:s');
            $fecha_actual = date('Y-m-d');

            $retardoInfo = $this->calcularRetardo($hora_actual, $empleado_id, $fecha_actual);

            // Si la diferencia excede tolerancia + 20 minutos -> marcar ausencia (falta)
            $diferencia_minutos = $retardoInfo['minutos'];
            $tolerancia = $retardoInfo['tolerancia'];

            if ($diferencia_minutos > ($tolerancia + 20)) {
                // Marcar ausencia en ausencias (tipo 'otro') si no existe licencia
                require_once __DIR__ . '/Ausencia.php';
                $ausenciaModel = new Ausencia();
                $ausenciaModel->create([
                    'empleado_id' => $empleado_id,
                    'fecha_inicio' => $fecha_actual,
                    'fecha_fin' => $fecha_actual,
                    'tipo' => 'otro'
                ]);
            } else {
                // Si hay retardo menor o mayor, registrar en tabla retardos
                if ($retardoInfo['tipo'] !== 'sin_retardo' && $retardoInfo['minutos'] > 0) {
                    require_once __DIR__ . '/Retardo.php';
                    $retModel = new Retardo();
                    $retModel->registrarRetardo($empleado_id, $fecha_actual, $retardoInfo['minutos'], $retardoInfo['tipo'], $retardoInfo['horario_id']);

                    // Aplicar regla AEFCM y luego verificar sanción por acumulación
                    $this->aplicarReglasAEFCM($empleado_id, date('n'), date('Y'));
                    // Intentar crear suspensión si corresponde
                    $retModel->crearSuspensionSiCorresponde($empleado_id, date('n'), date('Y'), $_SESSION['user_id'] ?? null);
                }
            }
        }

        return $result;
    }

    public function registrarSalida($empleado_id, $dispositivo_id, $tipo_biometria = null, $datos_biometricos = null, $calidad_verificacion = null, $metadata_dispositivo = null, $tiempo_procesamiento = null) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO asistencia
            (empleado_id, tipo, dispositivo_id, tipo_biometria, datos_biometricos, calidad_verificacion, metadata_dispositivo, tiempo_procesamiento)
            VALUES (?, 'salida', ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $empleado_id,
            $dispositivo_id,
            $tipo_biometria,
            $datos_biometricos ? json_encode($datos_biometricos) : null,
            $calidad_verificacion,
            $metadata_dispositivo ? json_encode($metadata_dispositivo) : null,
            $tiempo_procesamiento
        ]);

        // Log del evento
        if ($result) {
            $this->logDispositivo->logEvent(
                $dispositivo_id,
                'verificacion',
                'exitoso',
                'Salida registrada exitosamente',
                [
                    'tipo_asistencia' => 'salida',
                    'calidad_verificacion' => $calidad_verificacion,
                    'tiempo_procesamiento' => $tiempo_procesamiento
                ],
                $empleado_id,
                $tipo_biometria
            );
        }

        return $result;
    }

    public function getByEmpleado($empleado_id, $fecha_inicio = null, $fecha_fin = null) {
        $query = "
            SELECT a.*, e.nombre, e.apellido, e.rfc
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
        ";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE a.empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($fecha_inicio && $fecha_fin) {
            $query .= ($params ? " AND" : " WHERE") . " DATE(a.timestamp) BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $query .= " ORDER BY a.timestamp DESC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
            $query .= " AND DATE(a.timestamp) BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $query .= " ORDER BY a.timestamp DESC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT a.*, e.nombre, e.apellido, e.rfc
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            ORDER BY a.timestamp DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Calcula el retardo basado en el horario del empleado
     * Aplica normas AEFCM: dos retardos menores = uno mayor
     */
    public function calcularRetardo($hora_entrada, $empleado_id, $fecha) {
        require_once 'Horario.php';
        $horarioModel = new Horario();

        // Obtener horario aplicable para el empleado en esta fecha
        $horario = $horarioModel->getHorarioEmpleadoFecha($empleado_id, $fecha);

        if (!$horario) {
            // Si no hay horario asignado, usar horario por defecto
            $hora_oficial = '09:00:00';
            $tolerancia = TOLERANCE_MINUTES;
        } else {
            $hora_oficial = $horario['hora_entrada'];
            $tolerancia = $horario['tolerancia_minutos'];
        }

        $hora_oficial_timestamp = strtotime($hora_oficial);
        $hora_entrada_timestamp = strtotime($hora_entrada);

        $diferencia_minutos = ($hora_entrada_timestamp - $hora_oficial_timestamp) / 60;

        // Determinar tipo de retardo
        $tipo = 'sin_retardo';
        $minutos_retardo = 0;

        if ($diferencia_minutos > 0) {
            $minutos_retardo = (int)$diferencia_minutos;

            if ($diferencia_minutos <= $tolerancia) {
                $tipo = 'menor';
            } else {
                $tipo = 'mayor';
            }
        }

        return [
            'tipo' => $tipo,
            'minutos' => $minutos_retardo,
            'hora_oficial' => $hora_oficial,
            'tolerancia' => $tolerancia,
            'horario_id' => $horario ? $horario['id'] : null
        ];
    }

    /**
     * Aplica reglas AEFCM para acumulación de retardos
     * Dos retardos menores en el mes = uno mayor
     */
    public function aplicarReglasAEFCM($empleado_id, $mes, $anio) {
        require_once 'Retardo.php';
        $retardoModel = new Retardo();

        // Obtener retardos menores del mes
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as cantidad_menores
            FROM retardos
            WHERE empleado_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? AND tipo = 'menor' AND justificado = 0
        ");
        $stmt->execute([$empleado_id, $mes, $anio]);
        $result = $stmt->fetch();

        $cantidad_menores = $result['cantidad_menores'];

        // Si hay 2 o más retardos menores, convertir el exceso en mayores
        if ($cantidad_menores >= 2) {
            $pares_completos = floor($cantidad_menores / 2);
            $retardos_a_convertir = $pares_completos;

            // Marcar retardos menores como justificados (según norma AEFCM)
            $stmt = $this->db->getConnection()->prepare("
                UPDATE retardos SET justificado = 1, motivo_justificacion = 'Aplicación automática de norma AEFCM (2 menores = 1 mayor)'
                WHERE empleado_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? AND tipo = 'menor' AND justificado = 0
                ORDER BY fecha ASC
                LIMIT ?
            ");
            $stmt->execute([$empleado_id, $mes, $anio, $retardos_a_convertir * 2]);

            // Crear retardo mayor equivalente
            for ($i = 0; $i < $retardos_a_convertir; $i++) {
                $fecha_retardo = date('Y-m-d', strtotime("$anio-$mes-01 + $i days"));
                $retardoModel->registrarRetardo($empleado_id, $fecha_retardo, 30, 'mayor'); // 30 min = mayor
            }
        }
    }

    /**
     * Obtiene estadísticas de asistencia por dispositivo
     */
    public function getEstadisticasPorDispositivo($fecha_inicio = null, $fecha_fin = null) {
        $query = "
            SELECT
                dispositivo_id,
                COUNT(*) as total_registros,
                SUM(CASE WHEN tipo = 'entrada' THEN 1 ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo = 'salida' THEN 1 ELSE 0 END) as salidas,
                SUM(CASE WHEN tipo_biometria = 'huella' THEN 1 ELSE 0 END) as verificaciones_huella,
                SUM(CASE WHEN tipo_biometria = 'cara' THEN 1 ELSE 0 END) as verificaciones_cara,
                AVG(calidad_verificacion) as calidad_promedio,
                AVG(tiempo_procesamiento) as tiempo_promedio_procesamiento
            FROM asistencia
            WHERE 1=1
        ";
        $params = [];

        if ($fecha_inicio && $fecha_fin) {
            $query .= " AND DATE(timestamp) BETWEEN ? AND ?";
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
            $query .= " AND a.tipo = ?";
            $params[] = $filtros['tipo_asistencia'];
        }

        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $query .= " AND DATE(a.timestamp) BETWEEN ? AND ?";
            $params[] = $filtros['fecha_inicio'];
            $params[] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['empleado_id'])) {
            $query .= " AND a.empleado_id = ?";
            $params[] = $filtros['empleado_id'];
        }

        $query .= " ORDER BY a.timestamp DESC";

        if (!empty($filtros['limit'])) {
            $query .= " LIMIT ?";
            $params[] = $filtros['limit'];
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
                DATE(a.timestamp) as fecha,
                MIN(CASE WHEN a.tipo = 'entrada' THEN TIME(a.timestamp) END) as hora_entrada,
                MAX(CASE WHEN a.tipo = 'salida' THEN TIME(a.timestamp) END) as hora_salida
            FROM empleados e
            JOIN asistencia a ON e.id = a.empleado_id
            WHERE DATE(a.timestamp) BETWEEN ? AND ?
        ";
        $params = [$fecha_inicio, $fecha_fin];

        if ($area) {
            $query .= " AND e.area = ?";
            $params[] = $area;
        }

        $query .= " GROUP BY e.id, e.nombre, e.apellido, e.area, DATE(a.timestamp)
                   ORDER BY DATE(a.timestamp) DESC, e.nombre ASC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
