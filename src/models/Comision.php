<?php
namespace App\Models;

use App\Models\Database;
use \DateInterval;
use \DatePeriod;
use \DateTime;
use \Exception;

class Comision {
    private $db;

    // Constantes para normas AEFCM
    const LIMITE_COMISIONES_MENSUAL = 3000.00; // Límite mensual por empleado
    const DIAS_MAXIMOS_COMISION = 30; // Días máximos por comisión
    const TIPOS_COMISION_AEFCM = [
        'viaticos' => ['requiere_aprobacion' => true, 'limite_dias' => 15],
        'gastos_representacion' => ['requiere_aprobacion' => true, 'limite_dias' => 30],
        'transporte' => ['requiere_aprobacion' => false, 'limite_dias' => 7],
        'hospedaje' => ['requiere_aprobacion' => true, 'limite_dias' => 30],
        'alimentacion' => ['requiere_aprobacion' => false, 'limite_dias' => 7],
        'otros' => ['requiere_aprobacion' => true, 'limite_dias' => 15]
    ];

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        // Validar según normas AEFCM
        $this->validarComisionAEFCM($data);

        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO comisiones (empleado_id, descripcion, monto, fecha_asignacion, fecha_vencimiento, tipo_comision, requiere_aprobacion, aprobado_por, fecha_aprobacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['empleado_id'],
            $data['descripcion'],
            $data['monto'],
            $data['fecha_asignacion'],
            $data['fecha_vencimiento'] ?? null,
            $data['tipo_comision'] ?? 'otros',
            $data['requiere_aprobacion'] ?? true,
            $data['aprobado_por'] ?? null,
            $data['fecha_aprobacion'] ?? null
        ]);
    }

    public function getByEmpleado($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM comisiones WHERE empleado_id = ? ORDER BY fecha_asignacion DESC
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    public function getVencidas($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM comisiones
            WHERE empleado_id = ? AND fecha_vencimiento < CURDATE() AND justificada = 0
            ORDER BY fecha_vencimiento ASC
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    public function justificarComision($id, $aprobado_por = null, $motivo = null) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE comisiones
            SET justificada = 1, aprobado_por = ?, motivo_aprobacion = ?, fecha_aprobacion = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$aprobado_por, $motivo, $id]);
    }

    public function aprobarComision($id, $aprobado_por) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE comisiones
            SET aprobado_por = ?, fecha_aprobacion = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$aprobado_por, $id]);
    }

    public function getTotalComisiones($empleado_id, $mes = null, $anio = null) {
        $query = "SELECT SUM(monto) as total FROM comisiones WHERE empleado_id = ?";
        $params = [$empleado_id];

        if ($mes && $anio) {
            $query .= " AND MONTH(fecha_asignacion) = ? AND YEAR(fecha_asignacion) = ?";
            $params[] = $mes;
            $params[] = $anio;
        }

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getComisionesPendientesAprobacion() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT c.*, e.nombre, e.apellido
            FROM comisiones c
            JOIN empleados e ON c.empleado_id = e.id
            WHERE c.requiere_aprobacion = 1 AND c.aprobado_por IS NULL
            ORDER BY c.fecha_asignacion DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function validarLimiteMensual($empleado_id, $monto, $mes, $anio) {
        $total_mes = $this->getTotalComisiones($empleado_id, $mes, $anio);
        return ($total_mes + $monto) <= self::LIMITE_COMISIONES_MENSUAL;
    }

    public function calcularDiasComision($fecha_inicio, $fecha_vencimiento) {
        if (!$fecha_vencimiento) return 0;

        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_vencimiento);

        // Excluir fines de semana
        $dias = 0;
        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $intervalo, $fin->modify('+1 day'));

        foreach ($periodo as $fecha) {
            if ($fecha->format('N') < 6) { // Lunes a Viernes
                $dias++;
            }
        }

        return $dias;
    }

    private function validarComisionAEFCM($data) {
        $tipo = $data['tipo_comision'] ?? 'otros';

        // Verificar que el tipo existe en las normas AEFCM
        if (!isset(self::TIPOS_COMISION_AEFCM[$tipo])) {
            throw new Exception("Tipo de comisión no válido según normas AEFCM");
        }

        $normas = self::TIPOS_COMISION_AEFCM[$tipo];

        // Validar límite mensual
        $mes = date('m', strtotime($data['fecha_asignacion']));
        $anio = date('Y', strtotime($data['fecha_asignacion']));

        if (!$this->validarLimiteMensual($data['empleado_id'], $data['monto'], $mes, $anio)) {
            throw new Exception("El monto excede el límite mensual permitido por AEFCM: $" . self::LIMITE_COMISIONES_MENSUAL);
        }

        // Validar días máximos por tipo de comisión
        if ($data['fecha_vencimiento']) {
            $dias = $this->calcularDiasComision($data['fecha_asignacion'], $data['fecha_vencimiento']);
            if ($dias > $normas['limite_dias']) {
                throw new Exception("Los días de comisión exceden el límite AEFCM para {$tipo}: {$normas['limite_dias']} días");
            }
        }

        // Asignar si requiere aprobación
        $data['requiere_aprobacion'] = $normas['requiere_aprobacion'];
    }

    public function getTiposComisionAEFCM() {
        return self::TIPOS_COMISION_AEFCM;
    }

    public function getLimitesAEFCM() {
        return [
            'limite_mensual' => self::LIMITE_COMISIONES_MENSUAL,
            'dias_maximos' => self::DIAS_MAXIMOS_COMISION,
            'tipos' => self::TIPOS_COMISION_AEFCM
        ];
    }
}
