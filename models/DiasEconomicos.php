<?php
require_once 'Database.php';

class DiasEconomicos {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Solicitar días económicos
     * Reglas:
     * - Modalidad A: 3 días continuos → 1 mes de espera (desde el 3er día)
     * - Modalidad B: 2 días continuos → 15 días de espera (desde el 2do día)  
     * - Modalidad C: 1 día → 1 semana de espera (desde el día usado)
     * - Employee debe tener más de 6 meses en la dependencia
     * - Solicitar con 1 día de anticipación mínimo
     */
    public function solicitar($data) {
        // Validar reglas de días económicos
        $validacion = $this->validarSolicitud($data);
        if (!$validacion['valido']) {
            return ['success' => false, 'error' => $validacion['error']];
        }

        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO dias_economicos (
                empleado_id, fecha, motivo, estatus, solicitado_por,
                fecha_solicitud, dias_solicitados, modalidad, tipo,
                aprobado_por, fecha_aprobacion, justificado,
                requiere_evidencia, fecha_limite_validacion, created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([
            $data['empleado_id'],
            $data['fecha'],
            $data['motivo'] ?? null,
            $data['estatus'] ?? 'pendiente',
            $data['solicitado_por'] ?? null,
            date('Y-m-d H:i:s'), // fecha_solicitud
            $data['dias_solicitados'],
            $data['modalidad'] ?? null,
            $data['tipo'] ?? 'individual',
            $data['aprobado_por'] ?? null,
            $data['aprobado_por'] ? date('Y-m-d H:i:s') : null,
            0, // justificado
            $data['requiere_evidencia'] ?? 0,
            $data['fecha_limite_validacion'] ?? null
        ]);
        
        $insertId = $this->db->getConnection()->lastInsertId();
        if ($insertId) {
            $this->recalcularRegistrosEmpleado($data['empleado_id']);
        }
        
        return ['success' => $result, 'id' => $insertId];
    }

    /**
     * Validar solicitud según reglas completas
     */
    private function validarSolicitud($data) {
        $empleado_id = $data['empleado_id'];
        $dias = (int)($data['dias_solicitados'] ?? 1);
        $tipo = $data['tipo'] ?? 'individual';
        $fecha_solicitada = $data['fecha'] ?? null;

        if ($dias < 1 || $dias > 3) {
            return ['valido' => false, 'error' => 'Solo se permiten solicitudes de 1, 2 o 3 días económicos.'];
        }

        // 0. Validar plaza de confianza
        if ($this->esPlazaConfianza($empleado_id)) {
            return ['valido' => false, 'error' => 'Las plazas de confianza no pueden solicitar días económicos.'];
        }

        // 1. Verificar Antigüedad: más de 6 meses en la dependencia
        $antiguedad = $this->getAntiguedadEmpleado($empleado_id);
        if ($antiguedad < 181) { // más de 6 meses y 1 día
            return ['valido' => false, 'error' => "El empleado debe tener más de 6 meses y 1 día de antigüedad en la dependencia. Actualmente tiene $antiguedad días."];
        }

        // 2. Verificar anticipación: mínimo 1 día de anticipación
        if ($fecha_solicitada) {
            $dias_anticipacion = (strtotime($fecha_solicitada) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
            if ($dias_anticipacion < 1) {
                return ['valido' => false, 'error' => 'La solicitud debe realizarse con al menos 1 día de anticipación.'];
            }

            // No se permiten solicitudes para lunes o viernes
            $dia_semana = (int)date('w', strtotime($fecha_solicitada)); // 0=Dom, 1=Lun, 5=Vie
            if ($dia_semana === 1 || $dia_semana === 5) {
                return ['valido' => false, 'error' => 'No se pueden solicitar días económicos en lunes ni viernes.'];
            }
        }

        // 3. Verificar días disponibles en el periodo actual
        $disponibles = $this->getDiasDisponibles($empleado_id);
        if ($dias > $disponibles) {
            return ['valido' => false, 'error' => "Solo tiene $disponibles días económicos disponibles en el periodo actual."];
        }

        // 4. Validar reglas de espera según modalidad
        // Calcular fecha fin según días solicitados
        $fecha_inicio = $fecha_solicitada ?? date('Y-m-d');
        $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' + ' . ($dias - 1) . ' days'));
        
        $validacion_espera = $this->validarPeriodoEspera($empleado_id, $dias, $fecha_inicio);
        if (!$validacion_espera['valido']) {
            return $validacion_espera;
        }

        return ['valido' => true];
    }

    /**
     * Validar periodo de espera según reglas:
     * Modalidad A (3 días): 1 mes = 30 días desde el 3er día
     * Modalidad B (2 días): 15 días desde el 2do día
     * Modalidad C (1 día): 1 semana = 7 días desde el día usado
     */
    private function validarPeriodoEspera($empleado_id, $dias_solicitados, $fecha_inicio_solicitada) {
        $ultimo_uso = $this->getUltimoUsoAprobado($empleado_id);
        
        if (!$ultimo_uso) {
            return ['valido' => true];
        }

        $fecha_nueva = strtotime($fecha_inicio_solicitada ?: date('Y-m-d'));
        $fecha_ultimo = strtotime($ultimo_uso['fecha']);
        $dias_transcurridos = floor(($fecha_nueva - $fecha_ultimo) / (60 * 60 * 24));
        
        // Determinar espera requerida según la modalidad del último disfrute
        // Se cuenta desde el último día usado del registro anterior.
        $dias_ultimo_disfrute = (int)($ultimo_uso['dias'] ?? 1);
        if ($dias_ultimo_disfrute >= 3) {
            // Modalidad A: 3 días → 1 mes (30 días) de espera
            $dias_espera = 30;
            $modalidad = 'A (último disfrute de 3 días)';
        } elseif ($dias_ultimo_disfrute == 2) {
            // Modalidad B: 2 días → 15 días de espera
            $dias_espera = 15;
            $modalidad = 'B (último disfrute de 2 días)';
        } else {
            // Modalidad C: 1 día → 1 semana (7 días) de espera
            $dias_espera = 7;
            $modalidad = 'C (último disfrute de 1 día)';
        }

        if ($dias_transcurridos < $dias_espera) {
            $faltan = $dias_espera - $dias_transcurridos;
            return [
                'valido' => false, 
                'error' => "Modalidad $modalidad: Debe esperar $dias_espera días desde el último día económico usado. ".
                           "Han transcurrido $dias_transcurridos días. Faltan $faltan días más."
            ];
        }

        return ['valido' => true];
    }

    private function esPlazaConfianza($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT plaza_confianza
            FROM empleados
            WHERE id = ?
        ");
        $stmt->execute([$empleado_id]);
        $empleado = $stmt->fetch();

        if (!$empleado) {
            return false;
        }

        $valor = $empleado['plaza_confianza'] ?? null;
        if (is_numeric($valor)) {
            return ((int)$valor) === 1;
        }

        $normalizado = strtolower(trim((string)$valor));
        return in_array($normalizado, ['1', 'si', 'sí', 'true', 'confianza'], true);
    }

    /**
     * Obtener antigüedad del empleado en días
     */
    private function getAntiguedadEmpleado($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT fecha_ingreso 
            FROM empleados 
            WHERE id = ?
        ");
        $stmt->execute([$empleado_id]);
        $empleado = $stmt->fetch();
        
        $fecha_ingreso = $empleado['fecha_ingreso'] ?? null;
        
        if (!$fecha_ingreso) {
            return 0; // Si no hay fecha, no puede solicitar
        }
        
        return floor((strtotime(date('Y-m-d')) - strtotime($fecha_ingreso)) / (60 * 60 * 24));
    }

    /**
     * Obtener último uso aprobado de días económicos
     */
    private function getUltimoUsoAprobado($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT fecha, fecha_aprobacion, dias_solicitados
            FROM dias_economicos
            WHERE empleado_id = ? 
            AND (estatus = 'aprobado' OR justificado = 1)
            ORDER BY fecha DESC
            LIMIT 1
        ");
        $stmt->execute([$empleado_id]);
        $resultado = $stmt->fetch();
        
        if (!$resultado) {
            return null;
        }
        
        // Calcular el último día del periodo anterior (fecha + días solicitados - 1)
        $ultimo_dia = date('Y-m-d', strtotime($resultado['fecha'] . ' + ' . ($resultado['dias_solicitados'] - 1) . ' days'));
        
        return [
            'fecha' => $ultimo_dia,
            'dias' => $resultado['dias_solicitados']
        ];
    }

    /**
     * Obtener días disponibles en el periodo actual
     * Periodo: 16 julio - 15 julio siguiente (9 días)
     */
    public function getDiasDisponibles($empleado_id) {
        // Periodo escolar: 16 julio año anterior - 15 julio año actual
        $periodoInfo = $this->getPeriodoActual();
        $periodo_inicio = $periodoInfo['inicio'];
        $periodo_fin = $periodoInfo['fin'];

        $stmt = $this->db->getConnection()->prepare("
            SELECT id, fecha, dias_solicitados, estatus
            FROM dias_economicos
            WHERE empleado_id = ? 
            AND fecha BETWEEN ? AND ? 
            AND estatus = 'aprobado'
            ORDER BY fecha ASC
        ");
        $stmt->execute([$empleado_id, $periodo_inicio, $periodo_fin]);
        $diasEco = $stmt->fetchAll();

        // Calcular solo los primeros 9 días
        $diasUsados = 0;
        foreach ($diasEco as $de) {
            if ($diasUsados < 9) {
                $diasRestantes = 9 - $diasUsados;
                $diasUsados += min((int)$de['dias_solicitados'], $diasRestantes);
            }
        }

        return max(0, 9 - $diasUsados);
    }

    /**
     * Obtener información del periodo actual (16 julio año anterior - 15 julio año actual)
     * Según calendario escolar
     */
    public function getPeriodoActual() {
        $mes = (int)date('n');
        $anio = (int)date('Y');
        
        // Periodo escolar: 16 julio año anterior - 15 julio año actual
        if ($mes >= 7 && $mes <= 12) {
            // Julio a Diciembre del año actual = periodo escolar actual (16 julio actual - 15 julio siguiente)
            return [
                'inicio' => date('Y-07-16'),
                'fin' => date('Y-07-15', strtotime('+1 year')),
                'dias_totales' => 9,
                'periodo' => '16 Julio ' . $anio . ' - 15 Julio ' . ($anio + 1)
            ];
        } else {
            // Enero a Junio = sigue siendo el periodo escolar anterior (16 julio anterior - 15 julio actual)
            return [
                'inicio' => date('Y-07-16', strtotime('-1 year')),
                'fin' => date('Y-07-15'),
                'dias_totales' => 9,
                'periodo' => '16 Julio ' . ($anio - 1) . ' - 15 Julio ' . $anio
            ];
        }
    }

    /**
     * Obtener solicitudes por empleado
     */
    public function getByEmpleado($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dias_economicos
            WHERE empleado_id = ?
            ORDER BY fecha_solicitud DESC
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todas las solicitudes
     */
    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT de.*, e.nombre, e.apellido, e.rfc
            FROM dias_economicos de
            JOIN empleados e ON de.empleado_id = e.id
            ORDER BY de.fecha_solicitud DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener empleados para dropdown
     */
    public function getEmpleados() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT id, nombre, apellido, rfc, area, fecha_ingreso
            FROM empleados
            WHERE activo = 1
            ORDER BY nombre, apellido
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Verificar si existe solicitud para una fecha
     */
    public function existeSolicitud($empleado_id, $fecha) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as count
            FROM dias_economicos
            WHERE empleado_id = ? AND fecha = ? AND estatus != 'rechazado'
        ");
        $stmt->execute([$empleado_id, $fecha]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Crear nueva solicitud
     */
    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO dias_economicos (
                empleado_id, fecha, motivo, estatus, solicitado_por, 
                fecha_solicitud, dias_solicitados, modalidad, tipo,
                aprobado_por, fecha_aprobacion, justificado,
                comentarios_aprobacion, motivo_rechazo,
                requiere_evidencia, fecha_limite_validacion, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([
            $data['empleado_id'],
            $data['fecha'],
            $data['motivo'] ?? null,
            $data['estatus'] ?? 'pendiente',
            $data['solicitado_por'] ?? null,
            $data['fecha_solicitud'] ?? date('Y-m-d H:i:s'),
            $data['dias_solicitados'] ?? 1,
            $data['modalidad'] ?? null,
            $data['tipo'] ?? 'individual',
            $data['aprobado_por'] ?? null,
            $data['fecha_aprobacion'] ?? null,
            $data['justificado'] ?? 0,
            $data['comentarios_aprobacion'] ?? null,
            $data['motivo_rechazo'] ?? null,
            $data['requiere_evidencia'] ?? 0,
            $data['fecha_limite_validacion'] ?? null
        ]);
        
        if ($result) {
            $this->recalcularRegistrosEmpleado($data['empleado_id']);
        }
        
        return $result;
    }

    /**
     * Actualizar solicitud
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $values[] = $value;
        }
        $values[] = $id;
        
        $sql = "UPDATE dias_economicos SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Aprobar solicitud
     */
    public function aprobar($id, $aprobado_por, $comentarios = null) {
        $stmtEmp = $this->db->getConnection()->prepare("SELECT empleado_id FROM dias_economicos WHERE id = ?");
        $stmtEmp->execute([$id]);
        $row = $stmtEmp->fetch();
        $empleado_id = $row ? $row['empleado_id'] : null;
        
        $stmt = $this->db->getConnection()->prepare("
            UPDATE dias_economicos
            SET aprobado_por = ?, fecha_aprobacion = ?, justificado = 1, estatus = 'aprobado', comentarios_aprobacion = ?
            WHERE id = ?
        ");
        $result = $stmt->execute([$aprobado_por, date('Y-m-d'), $comentarios, $id]);
        
        if ($result && $empleado_id) {
            $this->recalcularRegistrosEmpleado($empleado_id);
            $this->actualizarAsistenciaExcedentes($empleado_id);
        }
        
        return $result;
    }
    
    /**
     * Rechazar solicitud y marcar en asistencia como incidencia
     */
    public function rechazar($id, $motivo = null) {
        $stmtEmp = $this->db->getConnection()->prepare("SELECT empleado_id, fecha, dias_solicitados FROM dias_economicos WHERE id = ?");
        $stmtEmp->execute([$id]);
        $row = $stmtEmp->fetch();
        $empleado_id = $row ? $row['empleado_id'] : null;
        $fecha_inicio = $row ? $row['fecha'] : null;
        $dias_solicitados = $row ? $row['dias_solicitados'] : 0;
        
        $stmt = $this->db->getConnection()->prepare("
            UPDATE dias_economicos
            SET justificado = 0, motivo_rechazo = ?, estatus = 'rechazado'
            WHERE id = ?
        ");
        $result = $stmt->execute([$motivo, $id]);
        
        if ($result && $empleado_id && $fecha_inicio) {
            // Marcar todos los días del día económico rechazado como incidencia (por_definir)
            $this->marcarDiasEconomicoRechazadoComoIncidencia($empleado_id, $fecha_inicio, $dias_solicitados, $motivo);
        }
        
        return $result;
    }
    
    /**
     * Marcar los días de un día económico rechazado como incidencia en asistencia
     */
    private function marcarDiasEconomicoRechazadoComoIncidencia($empleado_id, $fecha_inicio, $dias_solicitados, $motivo_rechazo = null) {
        $pdo = $this->db->getConnection();
        
        // Calcular todas las fechas del período del día económico
        $fechas = [];
        $fecha = new DateTime($fecha_inicio);
        for ($i = 0; $i < $dias_solicitados; $i++) {
            $fechas[] = $fecha->format('Y-m-d');
            $fecha->modify('+1 day');
        }
        
        foreach ($fechas as $fecha) {
            // Verificar si ya existe un registro de asistencia
            $stmtCheck = $pdo->prepare("SELECT id, tipo_asistencia FROM asistencia WHERE empleado_id = ? AND fecha = ?");
            $stmtCheck->execute([$empleado_id, $fecha]);
            $asistencia = $stmtCheck->fetch();
            
            if ($asistencia) {
                // Actualizar el registro existente como "por_definir" para que justifique
                $stmtUpdate = $pdo->prepare("
                    UPDATE asistencia 
                    SET tipo_asistencia = 'por_definir',
                        requerio_validacion = 1,
                        hora_entrada = NULL,
                        hora_salida = NULL
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$asistencia['id']]);
            } else {
                // Crear un nuevo registro de asistencia como "por_definir"
                $stmtInsert = $pdo->prepare("
                    INSERT INTO asistencia (empleado_id, fecha, tipo_asistencia, hora_entrada, hora_salida, requerio_validacion, created_at)
                    VALUES (?, ?, 'por_definir', NULL, NULL, 1, NOW())
                ");
                $stmtInsert->execute([$empleado_id, $fecha]);
            }
        }
        
        return ['fechas_marcadas' => $fechas];
    }
    
    /**
     * Actualizar asistencia para marcar días excedentes como "por_definir"
     * Los primeros 9 días aprobados son días económicos legítimos
     * Los días 10+ deben marcarse como "por_definir" para justificar
     */
    public function actualizarAsistenciaExcedentes($empleado_id) {
        $pdo = $this->db->getConnection();
        
        // Obtener el periodo actual (agosto - julio)
        $periodoInfo = $this->getPeriodoActual();
        $periodoInicio = $periodoInfo['inicio'];
        $periodoFin = $periodoInfo['fin'];
        $limite = $periodoInfo['dias_totales']; // 9 días
        
        // Obtener todos los días económicos aprobados ordenados por fecha
        $stmt = $pdo->prepare("
            SELECT id, fecha, dias_solicitados 
            FROM dias_economicos 
            WHERE empleado_id = ? 
            AND estatus = 'aprobado'
            AND fecha >= ? 
            AND fecha <= ?
            ORDER BY fecha ASC, id ASC
        ");
        $stmt->execute([$empleado_id, $periodoInicio, $periodoFin]);
        $diasEconomicos = $stmt->fetchAll();
        
        // Calcular todos los días de días económicos aprobados
        $diasEconomicosFechas = [];
        foreach ($diasEconomicos as $de) {
            $fechaInicio = new DateTime($de['fecha']);
            for ($i = 0; $i < $de['dias_solicitados']; $i++) {
                $diasEconomicosFechas[] = $fechaInicio->format('Y-m-d');
                $fechaInicio->modify('+1 day');
            }
        }
        
        // Identificar días excedentes (los que sobrepasen el límite de 9)
        $diasExcedentes = array_slice($diasEconomicosFechas, $limite);
        
        // Primero, restaurar todos los días que fueron marcados como excedentes
        // (cambiar de "por_definir" a "dia_economico" si ya no son excedentes)
        // Solo restauramos los que tienen estado "excedente" - usamos requerio_validacion como flag
        $stmtRestore = $pdo->prepare("
            UPDATE asistencia 
            SET tipo_asistencia = 'dia_economico',
                requerio_validacion = 0
            WHERE empleado_id = ? 
            AND fecha >= ?
            AND fecha <= ?
            AND tipo_asistencia = 'por_definir'
            AND requerio_validacion = 1
        ");
        $stmtRestore->execute([$empleado_id, $periodoInicio, $periodoFin]);
        
        // Ahora marcar los días excedentes como "por_definir" SIN entrada ni salida
        foreach ($diasExcedentes as $fecha) {
            // Verificar si ya existe un registro de asistencia para esa fecha
            $stmtCheck = $pdo->prepare("SELECT id, tipo_asistencia FROM asistencia WHERE empleado_id = ? AND fecha = ?");
            $stmtCheck->execute([$empleado_id, $fecha]);
            $asistencia = $stmtCheck->fetch();
            
            if ($asistencia) {
                // Actualizar el registro existente - QUITAR horas de entrada/salida y marcar como por_definir
                $stmtUpdate = $pdo->prepare("
                    UPDATE asistencia 
                    SET tipo_asistencia = 'por_definir',
                        requerio_validacion = 1,
                        hora_entrada = NULL,
                        hora_salida = NULL
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$asistencia['id']]);
            } else {
                // Crear un nuevo registro de asistencia como "por_definir" SIN entrada ni salida
                $stmtInsert = $pdo->prepare("
                    INSERT INTO asistencia (empleado_id, fecha, tipo_asistencia, hora_entrada, hora_salida, requerio_validacion, created_at)
                    VALUES (?, ?, 'por_definir', NULL, NULL, 1, NOW())
                ");
                $stmtInsert->execute([$empleado_id, $fecha]);
            }
        }
        
        return [
            'dias_excedentes' => count($diasExcedentes),
            'fechas_excedentes' => $diasExcedentes
        ];
    }
    
    /**
     * Obtener solicitudes pendientes
     */
    public function getPendientesAprobacion() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT de.*, e.nombre, e.apellido, e.fecha_ingreso
            FROM dias_economicos de
            JOIN empleados e ON de.empleado_id = e.id
            WHERE de.estatus = 'pendiente'
            ORDER BY de.fecha_solicitud DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener días económicos disponibles con detalles del periodo
     */
    public function getDiasDisponiblesDetallado($empleado_id) {
        $disponibles = $this->getDiasDisponibles($empleado_id);
        $periodo = $this->getPeriodoActual();
        
        // Obtener información de antigüedad
        $stmt = $this->db->getConnection()->prepare("
            SELECT fecha_ingreso FROM empleados WHERE id = ?
        ");
        $stmt->execute([$empleado_id]);
        $emp = $stmt->fetch();
        $fecha_ingreso = $emp['fecha_ingreso'] ?? null;
        $antiguedad = $fecha_ingreso ? floor((strtotime(date('Y-m-d')) - strtotime($fecha_ingreso)) / (60 * 60 * 24)) : 0;
        $puede_solicitar = $antiguedad >= 180;
        
        return [
            'disponibles' => $disponibles,
            'periodo' => $periodo,
            'antiguedad_dias' => $antiguedad,
            'puede_solicitar' => $puede_solicitar,
            'mensaje_antiguedad' => $puede_solicitar 
                ? "Antigüedad: $antiguedad días (cumple requisito de 6 meses)"
                : "Antigüedad: $antiguedad días (falta " . (180 - $antiguedad) . " días para cumplir 6 meses)"
        ];
    }

    /**
     * Recalcular y actualizar todos los campos calculados de dias_economicos
     */
    public function recalcularTodosLosRegistros() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT DISTINCT empleado_id FROM dias_economicos
        ");
        $stmt->execute();
        $empleados = $stmt->fetchAll();
        
        $total_actualizados = 0;
        
        foreach ($empleados as $emp) {
            $actualizados = $this->recalcularRegistrosEmpleado($emp['empleado_id']);
            $total_actualizados += $actualizados;
        }
        
        return ['actualizados' => $total_actualizados];
    }
    
    /**
     * Recalcular campos para todos los registros de un empleado
     */
    public function recalcularRegistrosEmpleado($empleado_id) {
        $pdo = $this->db->getConnection();
        
        $stmtEmp = $pdo->prepare("
            SELECT fecha_ingreso, plaza_confianza 
            FROM empleados WHERE id = ?
        ");
        $stmtEmp->execute([$empleado_id]);
        $empleado = $stmtEmp->fetch();
        
        if (!$empleado) return 0;
        
        $fecha_ingreso = $empleado['fecha_ingreso'] ?? null;
        $antiguedad = $fecha_ingreso ? floor((strtotime(date('Y-m-d')) - strtotime($fecha_ingreso)) / (60 * 60 * 24)) : 0;
        $cumple_antiguedad = $antiguedad >= 180 ? 1 : 0;
        
        $valor = $empleado['plaza_confianza'] ?? null;
        if (is_numeric($valor)) {
            $es_confianza = ((int)$valor) === 1;
        } else {
            $normalizado = strtolower(trim((string)$valor));
            $es_confianza = in_array($normalizado, ['1', 'si', 'sí', 'true', 'confianza'], true);
        }
        $es_plaza_confianza = $es_confianza ? 1 : 0;
        
        $stmt = $pdo->prepare("SELECT * FROM dias_economicos WHERE empleado_id = ? ORDER BY fecha ASC");
        $stmt->execute([$empleado_id]);
        $registros = $stmt->fetchAll();
        
        $actualizados = 0;
        $acumulado_periodo = [];
        
        foreach ($registros as $reg) {
            $fecha = $reg['fecha'];
            $periodo = $this->determinarPeriodo($fecha);
            $periodo_inicio = $periodo['inicio'];
            $periodo_fin = $periodo['fin'];
            
            if (!isset($acumulado_periodo[$periodo_inicio])) {
                $stmtUsados = $pdo->prepare("
                    SELECT SUM(dias_solicitados) as total
                    FROM dias_economicos
                    WHERE empleado_id = ? 
                    AND fecha >= ? 
                    AND fecha <= ?
                    AND estatus = 'aprobado'
                    AND id < ?
                ");
                $stmtUsados->execute([$empleado_id, $periodo_inicio, $periodo_fin, $reg['id']]);
                $usadosResult = $stmtUsados->fetch();
                $acumulado_periodo[$periodo_inicio] = (int)($usadosResult['total'] ?? 0);
            }
            
            $dias_usados = $acumulado_periodo[$periodo_inicio];
            
            if ($reg['estatus'] === 'aprobado') {
                $dias_usados += $reg['dias_solicitados'];
            }
            
            $dias_restantes = max(0, 9 - $dias_usados);
            
            $stmtUltimo = $pdo->prepare("
                SELECT fecha, dias_solicitados FROM dias_economicos
                WHERE empleado_id = ? 
                AND estatus = 'aprobado'
                AND fecha < ?
                ORDER BY fecha DESC LIMIT 1
            ");
            $stmtUltimo->execute([$empleado_id, $fecha]);
            $ultimo = $stmtUltimo->fetch();
            
            $ultimo_tipo_dias = null;
            $fecha_ultimo_disfrute = null;
            $fecha_puede_solicitar = null;
            
            if ($ultimo) {
                $ultimo_tipo_dias = $ultimo['dias_solicitados'];
                $ultimo_dia = date('Y-m-d', strtotime($ultimo['fecha'] . ' + ' . ($ultimo['dias_solicitados'] - 1) . ' days'));
                $fecha_ultimo_disfrute = $ultimo_dia;
                
                if ($ultimo['dias_solicitados'] >= 3) {
                    $fecha_puede_solicitar = date('Y-m-d', strtotime($ultimo_dia . ' + 30 days'));
                } elseif ($ultimo['dias_solicitados'] == 2) {
                    $fecha_puede_solicitar = date('Y-m-d', strtotime($ultimo_dia . ' + 15 days'));
                } else {
                    $fecha_puede_solicitar = date('Y-m-d', strtotime($ultimo_dia . ' + 7 days'));
                }
            }
            
            $dia_semana = date('w', strtotime($fecha));
            $validacion_dia_semana = null;
            if ($dia_semana === 0) $validacion_dia_semana = 'domingo';
            elseif ($dia_semana === 6) $validacion_dia_semana = 'sabado';
            elseif ($dia_semana === 1) $validacion_dia_semana = 'lunes';
            elseif ($dia_semana === 5) $validacion_dia_semana = 'viernes';
            else $validacion_dia_semana = 'valido';
            
            $updateStmt = $pdo->prepare("
                UPDATE dias_economicos SET
                    dias_usados_periodo = ?,
                    dias_restantes_periodo = ?,
                    ultimo_tipo_dias = ?,
                    fecha_ultimo_disfrute = ?,
                    fecha_puede_solicitar = ?,
                    periodo_inicio = ?,
                    periodo_fin = ?,
                    cumple_antiguedad = ?,
                    es_plaza_confianza = ?,
                    validacion_dia_semana = ?
                WHERE id = ?
            ");
            $updateStmt->execute([
                $dias_usados,
                $dias_restantes,
                $ultimo_tipo_dias,
                $fecha_ultimo_disfrute,
                $fecha_puede_solicitar,
                $periodo_inicio,
                $periodo_fin,
                $cumple_antiguedad,
                $es_plaza_confianza,
                $validacion_dia_semana,
                $reg['id']
            ]);
            $actualizados++;
            
            if ($reg['estatus'] === 'aprobado') {
                $acumulado_periodo[$periodo_inicio] += $reg['dias_solicitados'];
            }
        }
        
        return $actualizados;
    }
    
    /**
     * Determinar el periodo anual para una fecha (16 julio - 15 julio)
     * Según calendario escolar
     */
    private function determinarPeriodo($fecha) {
        $mes = (int)date('n', strtotime($fecha));
        $dia = (int)date('j', strtotime($fecha));
        $anio = (int)date('Y', strtotime($fecha));
        
        // Si la fecha es >= 16 de julio, pertenece al periodo que inicia ese 16 de julio
        // Si la fecha es < 16 de julio, pertenece al periodo que terminó el 15 de julio anterior
        if ($mes >= 7 && $dia >= 16) {
            return [
                'inicio' => date('Y-07-16', strtotime($fecha)),
                'fin' => date('Y-07-15', strtotime('+1 year', strtotime($fecha)))
            ];
        } else {
            return [
                'inicio' => date('Y-07-16', strtotime('-1 year', strtotime($fecha))),
                'fin' => date('Y-07-15', strtotime($fecha))
            ];
        }
    }
}
?>
