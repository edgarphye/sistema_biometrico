<?php
require_once __DIR__ . '/Database.php';

class LicenciaMedica {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        try {
            $pdo = $this->db->getConnection();
            
            $sql = "INSERT INTO licencias_medicas (
                empleado_id, antiguedad_dias, dias_permitidos_full, dias_permitidos_half,
                dias_full, dias_half, dias_sin_sueldo,
                incidencia_id, asistencia_ids, folio, dias_otorgados,
                fecha_inicio, fecha_fin, diagnostico, institucion, numero_constancia, fundamentos,
                tipo_sueldo, estatus
            ) VALUES (
                :empleado_id, :antiguedad_dias, :dias_permitidos_full, :dias_permitidos_half,
                :dias_full, :dias_half, :dias_sin_sueldo,
                :incidencia_id, :asistencia_ids, :folio, :dias_otorgados,
                :fecha_inicio, :fecha_fin, :diagnostico, :institucion, :numero_constancia, :fundamentos,
                :tipo_sueldo, :estatus
            )";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindValue(':empleado_id', $data['empleado_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':antiguedad_dias', $data['antiguedad_dias'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':dias_permitidos_full', $data['dias_permitidos_full'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':dias_permitidos_half', $data['dias_permitidos_half'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':dias_full', $data['dias_full'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':dias_half', $data['dias_half'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':dias_sin_sueldo', $data['dias_sin_sueldo'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':incidencia_id', $data['incidencia_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':asistencia_ids', $data['asistencia_ids'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':folio', $data['folio'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':dias_otorgados', $data['dias_otorgados'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_inicio', $data['fecha_inicio'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin', $data['fecha_fin'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':diagnostico', $data['diagnostico'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':institucion', $data['institucion'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':numero_constancia', $data['numero_constancia'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':fundamentos', $data['fundamentos'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_sueldo', $data['tipo_sueldo'] ?? 'full', PDO::PARAM_STR);
            $stmt->bindValue(':estatus', 'pendiente', PDO::PARAM_STR);
            
            $stmt->execute();
            
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en LicenciaMedica create: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function calcularDiasPermitidos($empleado_id) {
        $pdo = $this->db->getConnection();
        
        // Obtener fecha de ingreso del empleado
        $stmt = $pdo->prepare("SELECT fecha_ingreso FROM empleados WHERE id = ?");
        $stmt->execute([$empleado_id]);
        $emp = $stmt->fetch();
        
        $fecha_ingreso = $emp['fecha_ingreso'] ?? null;
        if (!$fecha_ingreso) {
            return $this->emptyDiasResponse();
        }
        
        // Calcular antigüedad
        $antiguedad = floor((strtotime(date('Y-m-d')) - strtotime($fecha_ingreso)) / (60 * 60 * 24));
        $anios = $antiguedad / 365;
        
        // Determinar días permitidos según antigüedad
        if ($anios < 1) {
            $full = 15;
            $half = 15;
        } elseif ($anios < 5) {
            $full = 30;
            $half = 30;
        } elseif ($anios < 10) {
            $full = 45;
            $half = 45;
        } else {
            $full = 60;
            $half = 60;
        }
        
        // Verificar si es necesario reiniciar el ciclo (cuando mes/día de ingreso coincide con fecha actual)
        $fecha_ingreso_dt = new DateTime($fecha_ingreso);
        $fecha_actual_dt = new DateTime(date('Y-m-d'));
        $mes_dia_ingreso = $fecha_ingreso_dt->format('m-d');
        $mes_dia_actual = $fecha_actual_dt->format('m-d');
        
        $anio_actual = (int)date('Y');
        
        // Si coincide el mes y día, verificar si hay que reiniciar el control del año anterior
        if ($mes_dia_ingreso === $mes_dia_actual) {
            // Crear o actualizar control para el año actual (reinicio)
            $stmtCtrl = $pdo->prepare("
                INSERT INTO control_licencias_medicas (empleado_id, anio, dias_con_sueldo_usados, dias_medio_sueldo_usados, dias_sin_sueldo_usados)
                VALUES (?, ?, 0, 0, 0)
                ON DUPLICATE KEY UPDATE dias_con_sueldo_usados = 0, dias_medio_sueldo_usados = 0, dias_sin_sueldo_usados = 0
            ");
            $stmtCtrl->execute([$empleado_id, $anio_actual]);
        }
        
        // Obtener control de días del año actual
        $stmtCtrl = $pdo->prepare("
            SELECT * FROM control_licencias_medicas WHERE empleado_id = ? AND anio = ?
        ");
        $stmtCtrl->execute([$empleado_id, $anio_actual]);
        $control = $stmtCtrl->fetch();
        
        // Si no existe control para este año, crear uno nuevo
        if (!$control) {
            $stmtInsert = $pdo->prepare("
                INSERT INTO control_licencias_medicas (empleado_id, anio, dias_con_sueldo_usados, dias_medio_sueldo_usados, dias_sin_sueldo_usados)
                VALUES (?, ?, 0, 0, 0)
            ");
            $stmtInsert->execute([$empleado_id, $anio_actual]);
            
            $full_usados = 0;
            $half_usados = 0;
            $sin_sueldo_usados = 0;
        } else {
            $full_usados = (int)$control['dias_con_sueldo_usados'];
            $half_usados = (int)$control['dias_medio_sueldo_usados'];
            $sin_sueldo_usados = (int)$control['dias_sin_sueldo_usados'];
        }
        
        // Calcular años y meses enteros
        $anios_enteros = floor($antiguedad / 365);
        $meses_enteros = floor(($antiguedad % 365) / 30);
        
        return [
            'antiguedad_dias' => $antiguedad,
            'anios' => $anios_enteros,
            'meses' => $meses_enteros,
            'anios_decimal' => round($anios, 1),
            'fecha_ingreso' => $fecha_ingreso,
            'full' => $full,
            'half' => $half,
            'full_usados' => $full_usados,
            'half_usados' => $half_usados,
            'sin_sueldo_usados' => $sin_sueldo_usados,
            'full_restantes' => max(0, $full - $full_usados),
            'half_restantes' => max(0, $half - $half_usados),
            'anio_actual' => $anio_actual,
            'reinicio_ciclo' => ($mes_dia_ingreso === $mes_dia_actual)
        ];
    }
    
    public function actualizarControlDias($empleado_id, $dias_full, $dias_half, $dias_sin_sueldo) {
        $pdo = $this->db->getConnection();
        $anio_actual = (int)date('Y');
        
        // Verificar si existe registro de control
        $stmtCheck = $pdo->prepare("SELECT id FROM control_licencias_medicas WHERE empleado_id = ? AND anio = ?");
        $stmtCheck->execute([$empleado_id, $anio_actual]);
        
        if ($stmtCheck->fetch()) {
            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE control_licencias_medicas 
                SET dias_con_sueldo_usados = dias_con_sueldo_usados + ?,
                    dias_medio_sueldo_usados = dias_medio_sueldo_usados + ?,
                    dias_sin_sueldo_usados = dias_sin_sueldo_usados + ?
                WHERE empleado_id = ? AND anio = ?
            ");
            $stmt->execute([$dias_full, $dias_half, $dias_sin_sueldo, $empleado_id, $anio_actual]);
        } else {
            // Crear nuevo registro
            $stmt = $pdo->prepare("
                INSERT INTO control_licencias_medicas (empleado_id, anio, dias_con_sueldo_usados, dias_medio_sueldo_usados, dias_sin_sueldo_usados)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$empleado_id, $anio_actual, $dias_full, $dias_half, $dias_sin_sueldo]);
        }
    }
    
    public function getControlDias($empleado_id) {
        $pdo = $this->db->getConnection();
        $anio_actual = (int)date('Y');
        
        $stmt = $pdo->prepare("
            SELECT * FROM control_licencias_medicas WHERE empleado_id = ? AND anio = ?
        ");
        $stmt->execute([$empleado_id, $anio_actual]);
        return $stmt->fetch();
    }
    
    private function emptyDiasResponse() {
        return [
            'antiguedad_dias' => 0,
            'anios' => 0,
            'meses' => 0,
            'anios_decimal' => 0,
            'fecha_ingreso' => null,
            'full' => 0,
            'half' => 0,
            'full_usados' => 0,
            'half_usados' => 0,
            'sin_sueldo_usados' => 0,
            'full_restantes' => 0,
            'half_restantes' => 0,
            'anio_actual' => (int)date('Y'),
            'reinicio_ciclo' => false
        ];
    }

    public function getByEmpleado($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM licencias_medicas
            WHERE empleado_id = ?
            ORDER BY fecha_inicio DESC
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM licencias_medicas WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $values[] = $value;
        }
        $values[] = $id;
        
        $sql = "UPDATE licencias_medicas SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare("DELETE FROM licencias_medicas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getDiasDisponibles($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT fecha_ingreso, fecha_alta FROM empleados WHERE id = ?
        ");
        $stmt->execute([$empleado_id]);
        $emp = $stmt->fetch();
        
        $fecha_ingreso = $emp['fecha_ingreso'] ?? $emp['fecha_alta'] ?? null;
        if (!$fecha_ingreso) {
            return ['full' => 0, 'half' => 0, 'antiguedad' => 0];
        }
        
        $antiguedad = floor((strtotime(date('Y-m-d')) - strtotime($fecha_ingreso)) / (60 * 60 * 24));
        $anios = $antiguedad / 365;
        
        if ($anios < 1) {
            $full = 15;
            $half = 15;
        } elseif ($anios < 5) {
            $full = 30;
            $half = 30;
        } elseif ($anios < 10) {
            $full = 45;
            $half = 45;
        } else {
            $full = 60;
            $half = 60;
        }
        
        // Obtener días usados en el año actual
        $anio_actual = date('Y');
        $stmt = $this->db->getConnection()->prepare("
            SELECT SUM(dias_otorgados) as usados, tipo_sueldo
            FROM licencias_medicas
            WHERE empleado_id = ? AND YEAR(fecha_inicio) = ?
            GROUP BY tipo_sueldo
        ");
        $stmt->execute([$empleado_id, $anio_actual]);
        $usados = $stmt->fetchAll();
        
        $usados_full = 0;
        $usados_half = 0;
        foreach ($usados as $u) {
            if ($u['tipo_sueldo'] === 'full') {
                $usados_full = (int)$u['usados'];
            } else {
                $usados_half = (int)$u['usados'];
            }
        }
        
        return [
            'full' => max(0, $full - $usados_full),
            'half' => max(0, $half - $usados_half),
            'antiguedad' => $antiguedad,
            'anios' => round($anios, 1)
        ];
    }
}
