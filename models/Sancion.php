<?php
require_once 'Database.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../helpers/RequestValidator.php';

class Sancion {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }
    public function create($data, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        
        // Validar datos de entrada
        $validated_id = SecurityHelper::sanitizeInt($data['empleado_id'], 1);
        if (!$validated_id) {
            throw new Exception('ID de empleado inválido');
        }
        
        // Validar tipo
        $tipos_validos = ['suspension', 'amonestacion', 'nota_mala', 'descuento'];
        $tipo = $data['tipo_sancion'] ?? $data['tipo'] ?? '';
        if (!in_array($tipo, $tipos_validos)) {
            throw new Exception('Tipo de sanción inválido');
        }
        
        // Validar fecha
        $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_inicio']);
        if (!$fecha) {
            throw new Exception('Fecha de inicio inválida');
        }
        
        $stmt = $pdo->prepare(
            "INSERT INTO sanciones (empleado_id, tipo_sancion, tipo_retardo, fecha_inicio, dias, motivo, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $validated_id,
            SecurityHelper::escape($tipo),
            SecurityHelper::escape($data['tipo_retardo'] ?? null),
            $data['fecha_inicio'],
            SecurityHelper::sanitizeInt($data['dias'] ?? 0, 0),
            SecurityHelper::escape($data['motivo'] ?? ''),
            SecurityHelper::sanitizeInt($data['creado_por'] ?? null, 1)
        ]);
    }

    public function getByEmpleado($empleado_id, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("SELECT * FROM sanciones WHERE empleado_id = ? ORDER BY fecha_inicio DESC");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    public function getByEmpleadoYear($empleado_id, $anio, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("SELECT * FROM sanciones WHERE empleado_id = ? AND YEAR(fecha_inicio) = ? ORDER BY fecha_inicio DESC");
        $stmt->execute([$empleado_id, $anio]);
        return $stmt->fetchAll();
    }

    public function countSuspensionesYear($empleado_id, $anio, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sanciones WHERE empleado_id = ? AND tipo = 'suspension' AND YEAR(fecha_inicio) = ?");
        $stmt->execute([$empleado_id, $anio]);
        $r = $stmt->fetch();
        return intval($r['total'] ?? 0);
    }

    public function getAll($pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("SELECT s.*, e.nombre, e.apellido FROM sanciones s JOIN empleados e ON s.empleado_id = e.id ORDER BY s.fecha_inicio DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id, $pdo = null) {
        $validated_id = SecurityHelper::sanitizeInt($id, 1);
        if (!$validated_id) {
            return false;
        }
        
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("SELECT s.*, e.nombre, e.apellido FROM sanciones s JOIN empleados e ON s.empleado_id = e.id WHERE s.id = ?");
        $stmt->execute([$validated_id]);
        return $stmt->fetch();
    }

    public function update($id, $data, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("UPDATE sanciones SET empleado_id = ?, tipo = ?, fecha_inicio = ?, dias = ?, motivo = ?, creado_por = ?, modified_by = ?, fecha_modificacion = NOW() WHERE id = ?");
        return $stmt->execute([
            $data['empleado_id'],
            $data['tipo'],
            $data['fecha_inicio'],
            $data['dias'] ?? 0,
            $data['motivo'] ?? null,
            $data['creado_por'] ?? null,
            $data['modified_by'] ?? ($data['creado_por'] ?? null),
            $id
        ]);
    }

    public function delete($id, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        $stmt = $pdo->prepare("DELETE FROM sanciones WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getSancionesAcumuladasQuincena($empleado_id, $fecha, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }
        
        $dia = date('d', strtotime($fecha));
        $mes = date('m', strtotime($fecha));
        $anio = date('Y', strtotime($fecha));

        $quincena_inicio = ($dia <= 15) ? 1 : 16;
        $quincena_fin = ($dia <= 15) ? 15 : cal_days_in_month(CAL_GREGORIAN, $mes, $anio);

        $fecha_inicio = "$anio-$mes-$quincena_inicio";
        $fecha_fin = "$anio-$mes-$quincena_fin";

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total FROM sanciones 
             WHERE empleado_id = ? 
             AND tipo = 'nota_mala' 
             AND fecha_inicio BETWEEN ? AND ?"
        );
        $stmt->execute([$empleado_id, $fecha_inicio, $fecha_fin]);
        $result = $stmt->fetch();
        return intval($result['total'] ?? 0);
    }

    public function getSancionesAcumuladasMes($empleado_id, $fecha, $pdo = null) {
        if (!($pdo instanceof \PDO)) {
            $pdo = $this->db->getConnection();
        }

        $mes = date('m', strtotime($fecha));
        $anio = date('Y', strtotime($fecha));

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total FROM sanciones 
             WHERE empleado_id = ? 
             AND tipo = 'nota_mala' 
             AND MONTH(fecha_inicio) = ? 
             AND YEAR(fecha_inicio) = ?"
        );
        $stmt->execute([$empleado_id, $mes, $anio]);
        $result = $stmt->fetch();
        return intval($result['total'] ?? 0);
    }
}
?>