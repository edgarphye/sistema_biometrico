<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../helpers/RequestValidator.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

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
        'comision_entrada' => ['requiere_aprobacion' => true, 'limite_dias' => 1],
        'comision_salida' => ['requiere_aprobacion' => true, 'limite_dias' => 1],
        'comision_todo_dia' => ['requiere_aprobacion' => true, 'limite_dias' => 30],
        'comision_dia' => ['requiere_aprobacion' => true, 'limite_dias' => 30],
        'otros' => ['requiere_aprobacion' => true, 'limite_dias' => 15]
    ];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        // Validar datos de entrada
        $errors = RequestValidator::validateAsistenciaData($data);
        if (!empty($errors)) {
            throw new Exception("Datos de comisión inválidos: " . implode(', ', $errors));
        }
        
        // Validar según normas AEFCM
        $this->validarComisionAEFCM($data);
        
        // Sanitizar datos
        $sanitized = RequestValidator::sanitizeAndValidate($data);

        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO comisiones (empleado_id, descripcion, monto, fecha_asignacion, fecha_vencimiento, tipo_comision, requiere_aprobacion, aprobado_por, fecha_aprobacion, evidencia_adjunta, fecha_inicio, fecha_fin)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sanitized['empleado_id'],
            SecurityHelper::escape($sanitized['descripcion']),
            SecurityHelper::sanitizeFloat($sanitized['monto'] ?? 0, 0),
            $sanitized['fecha_asignacion'] ?? date('Y-m-d'),
            $sanitized['fecha_vencimiento'] ?? null,
            $sanitized['tipo_comision'] ?? 'otros',
            $sanitized['requiere_aprobacion'] ?? true,
            $sanitized['aprobado_por'] ?? null,
            $sanitized['fecha_aprobacion'] ?? null,
            $sanitized['evidencia_adjunta'] ?? null,
            $sanitized['fecha_inicio'] ?? $sanitized['fecha_asignacion'] ?? date('Y-m-d'),
            $sanitized['fecha_fin'] ?? $sanitized['fecha_vencimiento'] ?? null
        ]);
        
        return $this->db->getConnection()->lastInsertId();
    }

    public function getByEmpleado($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT 
                c.*,
                vj.estado AS estado_validacion_jefe,
                vj.motivo_validacion AS motivo_validacion_jefe,
                vj.comentarios_adicionales AS comentarios_validacion_jefe,
                vj.fecha_validacion AS fecha_validacion_jefe,
                uj.nombre_completo AS jefe_validador_nombre
            FROM comisiones c
            LEFT JOIN (
                SELECT v.*
                FROM validaciones_jefe v
                INNER JOIN (
                    SELECT incidencia_id, MAX(id) AS max_id
                    FROM validaciones_jefe
                    WHERE tipo_incidencia = 'comision'
                    GROUP BY incidencia_id
                ) x ON x.max_id = v.id
            ) vj ON vj.incidencia_id = c.id
            LEFT JOIN usuarios uj ON uj.id = vj.jefe_id
            WHERE c.empleado_id = ?
            ORDER BY c.fecha_asignacion DESC
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    public function getVencidas($empleado_id = null) {
        try {
            $query = "SELECT * FROM comisiones WHERE fecha_vencimiento < CURDATE() AND estatus != 'aprobada'";
            $params = [];

            if ($empleado_id !== null) {
                $query .= " AND empleado_id = ?";
                $params[] = $empleado_id;
            }

            $query .= " ORDER BY fecha_vencimiento ASC";

            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function justificarComision($id, $aprobado_por = null, $motivo = null) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE comisiones
            SET estatus = 'aprobada', aprobado_por = ?, motivo_aprobacion = ?, fecha_aprobacion = CURRENT_TIMESTAMP
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
        $query = "SELECT SUM(monto) as total FROM comisiones";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($mes && $anio) {
            $query .= (empty($params) ? " WHERE" : " AND") . " MONTH(fecha_asignacion) = ? AND YEAR(fecha_asignacion) = ?";
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
?>
