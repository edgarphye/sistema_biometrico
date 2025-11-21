<?php
require_once 'Database.php';

class Retardo {
    private $db;

    public function __construct($db = null) {
        if ($db instanceof Database) {
            $this->db = $db;
        } else {
            $this->db = new Database();
        }
    }

    public function getById($id, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("SELECT * FROM retardos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
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
            "INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo, horario_id, justificado, soporte)
            VALUES (?, ?, ?, ?, ?, 0, NULL)"
        );

        return $stmt->execute([$empleado_id, $fecha, $minutos, $tipo, $horario_id]);
    }

    public function getByEmpleado($empleado_id, $fecha_inicio = null, $fecha_fin = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $query = "SELECT * FROM retardos";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($fecha_inicio && $fecha_fin) {
            $query .= ($params ? " AND" : " WHERE") . " fecha BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $query .= " ORDER BY fecha DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function justificarRetardo($id, $tipo_justificacion_id = null, $motivo = null, $aprobado_por = null, $soporte = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare(
            "UPDATE retardos SET
                justificado = 1,
                tipo_justificacion_id = ?,
                motivo_justificacion = ?,
                aprobado_por = ?,
                fecha_aprobacion = NOW(),
                soporte = ?
            WHERE id = ?"
        );
        return $stmt->execute([$tipo_justificacion_id, $motivo, $aprobado_por, $soporte, $id]);
    }

    public function getTotalRetardos($empleado_id, $mes = null, $anio = null, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $query = "SELECT COUNT(*) as total, tipo FROM retardos";
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

        $query .= " GROUP BY tipo";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
            WHERE empleado_id = ? AND tipo = 'menor' AND justificado = 0
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
                $stmt = $pdo->prepare("UPDATE retardos SET tipo = 'mayor' WHERE id = ?");
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
            WHERE r.justificado = 0
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
}
?>
