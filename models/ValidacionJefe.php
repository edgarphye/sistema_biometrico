<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../helpers/RequestValidator.php';

/**
 * Modelo para gestión de validaciones por jefes
 * Maneja el flujo completo de aprobación/rechazo de incidencias
 */
class ValidacionJefe {
    private $db;
    
    public function __construct($db = null) {
        if ($db instanceof Database) {
            $this->db = $db;
        } else {
            $this->db = Database::getInstance();
        }
    }
    
    /**
     * Obtener validaciones pendientes para un jefe específico
     * @param int $jefeId ID del usuario jefe
     * @param array $filters Filtros adicionales (empleado_id, area, fecha_inicio, fecha_fin)
     * @return array Lista de validaciones pendientes
     */
    public function getPendientesPorJefe($jefeId, $filters = []) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT v.*, 
                    e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                    e.area as empleado_area, e.jerarquia as empleado_jerarquia,
                    u.nombre_completo as jefe_nombre,
                    CASE 
                        WHEN v.tipo_incidencia = 'retardo' THEN 
                            (SELECT CONCAT('Retardo de ', r.minutos_retardo, ' minutos - ', COALESCE(r.motivo_justificacion, 'Sin motivo'))
                             FROM retardos r WHERE r.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'comision' THEN 
                            COALESCE(
                                (SELECT CONCAT('Comisión: ', c.descripcion) FROM comisiones c WHERE c.id = v.incidencia_id),
                                (SELECT CONCAT(
                                    CASE r.tipo_retraso 
                                        WHEN 'comision_entrada' THEN 'Comisión de entrada'
                                        WHEN 'comision_salida' THEN 'Comisión de salida'
                                        WHEN 'comision_todo_dia' THEN 'Comisión todo el día'
                                        ELSE 'Comisión'
                                    END,
                                    ': ',
                                    COALESCE(r.motivo_justificacion, 'Sin especificar lugar/motivo')
                                ) FROM retardos r WHERE r.id = v.incidencia_id)
                            )
                        WHEN v.tipo_incidencia = 'dia_economico' THEN 
                            (SELECT CONCAT('Día económico: ', de.motivo) 
                             FROM dias_economicos de WHERE de.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'ausencia' THEN 
                            (SELECT CONCAT('Ausencia: ', a.motivo) 
                             FROM ausencias a WHERE a.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'constancia_tiempo' THEN 
                            (SELECT CONCAT('Constancia de tiempo: ', ct.motivo) 
                             FROM constancias_tiempo ct WHERE ct.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'licencia_medica' THEN 
                            (SELECT CONCAT('Licencia médica: ', COALESCE(lm.diagnostico, 'Sin diagnóstico')) 
                             FROM licencias_medicas lm WHERE lm.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'justificacion' THEN 
                            (SELECT CONCAT('Justificación: ', j.motivo) 
                             FROM justificaciones j WHERE j.id = v.incidencia_id)
                    END as descripcion_incidencia,
                    CASE v.tipo_incidencia
                        WHEN 'retardo' THEN (SELECT fecha FROM retardos WHERE id = v.incidencia_id)
                        WHEN 'comision' THEN COALESCE(
                            (SELECT fecha_inicio FROM comisiones WHERE id = v.incidencia_id),
                            (SELECT fecha FROM retardos WHERE id = v.incidencia_id)
                        )
                        WHEN 'dia_economico' THEN (SELECT fecha FROM dias_economicos WHERE id = v.incidencia_id)
                        WHEN 'ausencia' THEN (SELECT fecha_inicio FROM ausencias WHERE id = v.incidencia_id)
                        WHEN 'constancia_tiempo' THEN (SELECT fecha_inicio FROM constancias_tiempo WHERE id = v.incidencia_id)
                        WHEN 'licencia_medica' THEN (SELECT fecha_inicio FROM licencias_medicas WHERE id = v.incidencia_id)
                        WHEN 'justificacion' THEN (SELECT fecha_inicio FROM justificaciones WHERE id = v.incidencia_id)
                    END as fecha_incidencia
                FROM validaciones_jefe v
                INNER JOIN empleados e ON v.empleado_id = e.id
                INNER JOIN usuarios u ON v.jefe_id = u.id
                WHERE v.jefe_id = ? AND v.estado = 'pendiente'";
        
        $params = [$jefeId];
        
        // Aplicar filtros adicionales
        if (!empty($filters['empleado_id'])) {
            $sql .= " AND v.empleado_id = ?";
            $params[] = $filters['empleado_id'];
        }
        
        if (!empty($filters['area'])) {
            $sql .= " AND e.area = ?";
            $params[] = $filters['area'];
        }
        
        if (!empty($filters['fecha_inicio'])) {
            $sql .= " AND v.fecha_solicitud >= ?";
            $params[] = $filters['fecha_inicio'];
        }
        
        if (!empty($filters['fecha_fin'])) {
            $sql .= " AND v.fecha_solicitud <= ?";
            $params[] = $filters['fecha_fin'];
        }
        
        if (!empty($filters['tipo_incidencia'])) {
            $sql .= " AND v.tipo_incidencia = ?";
            $params[] = $filters['tipo_incidencia'];
        }
        
        $sql .= " ORDER BY v.fecha_solicitud ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear nueva validación para una incidencia
     * @param array $data Datos de la validación
     * @return int ID de la validación creada
     */
    public function crear($data) {
        // Validar datos
        $errors = $this->validarDatosCreacion($data);
        if (!empty($errors)) {
            throw new Exception('Datos inválidos: ' . implode(', ', $errors));
        }
        
        $pdo = $this->db->getConnection();
        
        $sql = "INSERT INTO validaciones_jefe 
                (incidencia_id, tipo_incidencia, empleado_id, jefe_id, estado, 
                 motivo_validacion, evidencia_requerida, fecha_limite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            $data['incidencia_id'],
            $data['tipo_incidencia'],
            $data['empleado_id'],
            $data['jefe_id'],
            $data['estado'] ?? 'pendiente',
            $data['motivo_validacion'] ?? null,
            $data['evidencia_requerida'] ?? false,
            $data['fecha_limite'] ?? null
        ]);
        
        if ($result) {
            return $pdo->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Procesar validación (aprobar/rechazar/requerir info)
     * @param int $validacionId ID de la validación
     * @param array $decision Datos de la decisión
     * @return bool Resultado de la operación
     */
    public function procesarDecision($validacionId, $decision) {
        $pdo = $this->db->getConnection();
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        try {
            // Actualizar tipo de incidencia si se solicitó cambio
            if (!empty($decision['nuevo_tipo_incidencia'])) {
                $validacionActual = $this->getById($validacionId);
                if ($validacionActual) {
                    $this->actualizarTipoIncidencia($validacionActual['incidencia_id'], $validacionId, $decision['nuevo_tipo_incidencia']);
                }
            }

            // Actualizar validación
            $sql = "UPDATE validaciones_jefe 
                    SET estado = ?, motivo_validacion = ?, comentarios_adicionales = ?, 
                        evidencia_recibida = ?, fecha_validacion = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $decision['estado'], // 'aprobado', 'rechazado', 'requiere_info'
                $decision['motivo'],
                $decision['comentarios'] ?? null,
                $decision['evidencia_recibida'] ?? false,
                $validacionId
            ]);
            
            // Actualizar incidencia original según tipo
            // La variable $decision ya contiene 'aprobado_por' con el ID del jefe
            // que se pasará a los métodos de actualización de incidencias.
            $validacion = $this->getById($validacionId);
            
            switch ($validacion['tipo_incidencia']) {
                case 'retardo':
                    $this->actualizarRetardo($validacion['incidencia_id'], $decision);
                    $this->actualizarAsistenciaPorValidacion($validacion, $decision);
                    break;
                case 'comision':
                    $this->actualizarComision($validacion['incidencia_id'], $decision);
                    $this->actualizarAsistenciaPorValidacion($validacion, $decision);
                    break;
                case 'dia_economico':
                    $this->actualizarDiaEconomico($validacion['incidencia_id'], $decision);
                    $this->actualizarAsistenciaPorValidacion($validacion, $decision);
                    break;
                case 'ausencia':
                    $this->actualizarAusencia($validacion['incidencia_id'], $decision);
                    $this->actualizarAsistenciaPorValidacion($validacion, $decision);
                    break;
                case 'constancia_tiempo':
                    $this->actualizarConstanciaTiempo($validacion['incidencia_id'], $decision);
                    $this->actualizarAsistenciaPorValidacion($validacion, $decision);
                    break;
                case 'licencia_medica':
                    $this->actualizarLicenciaMedica($validacion['incidencia_id'], $decision);
                    $this->actualizarAsistenciaPorValidacion($validacion, $decision);
                    break;
                case 'justificacion':
                    $this->actualizarJustificacion($validacion['incidencia_id'], $decision);
                    $this->actualizarAsistenciaPorValidacion($validacion, $decision);
                    break;
            }
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Actualizar el tipo de incidencia (Retardo/Comisión)
     */
    private function actualizarTipoIncidencia($incidenciaId, $validacionId, $nuevoTipo) {
        $pdo = $this->db->getConnection();
        
        // Actualizar tipo_incidencia en validaciones_jefe
        // Determinar el tipo general basado en el subtipo
        if (strpos($nuevoTipo, 'comision') !== false) {
            $nuevoTipoGeneral = 'comision';
        } elseif (strpos($nuevoTipo, 'vacaciones') !== false) {
            $nuevoTipoGeneral = 'vacaciones';
        } elseif (strpos($nuevoTipo, 'ausencia') !== false) {
            $nuevoTipoGeneral = 'ausencia';
        } elseif (strpos($nuevoTipo, 'justificacion') !== false) {
            $nuevoTipoGeneral = 'justificacion';
        } elseif (strpos($nuevoTipo, 'licencia') !== false || strpos($nuevoTipo, 'medica') !== false) {
            $nuevoTipoGeneral = 'licencia_medica';
        } else {
            $nuevoTipoGeneral = 'retardo';
        }
        
        $sqlVal = "UPDATE validaciones_jefe SET tipo_incidencia = ? WHERE id = ?";
        $stmtVal = $pdo->prepare($sqlVal);
        $stmtVal->execute([$nuevoTipoGeneral, $validacionId]);
    }
    
    /**
     * Obtener estadísticas de validaciones para un jefe
     * @param int $jefeId ID del jefe
     * @param array $periodo Período (mes, anio)
     * @return array Estadísticas
     */
/**
     * Obtener estadísticas de validaciones por jefe/mando
     * @param int $userId ID del usuario logueado
     * @param array $periodo Período (mes, año)
     * @return array Estadísticas
     */
    public function getEstadisticasPorJefe($userId, $periodo = []) {
        $pdo = $this->db->getConnection();
        
        // Obtener el empleado_id asociado al usuario actual
        $stmt = $pdo->prepare("SELECT empleado_id, rol FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || !$usuario['empleado_id']) {
            return [
                'total' => 0,
                'pendientes' => 0,
                'aprobados' => 0,
                'rechazados' => 0
            ];
        }
        
        $jefeEmpleadoId = $usuario['empleado_id'];
        
        // Obtener IDs de subordinados (directos + catalogos_mandos)
        $subordinadoIds = [];
        
        $stmtD = $pdo->prepare("SELECT id FROM empleados WHERE jefe_directo_id = ? AND activo = 1");
        $stmtD->execute([$jefeEmpleadoId]);
        $subordinadoIds = $stmtD->fetchAll(PDO::FETCH_COLUMN);
        
        $stmtU = $pdo->prepare("SELECT username FROM usuarios WHERE empleado_id = ?");
        $stmtU->execute([$jefeEmpleadoId]);
        $username = $stmtU->fetchColumn();
        if ($username) {
            $stmtM = $pdo->prepare("
                SELECT id FROM catalogos_mandos 
                WHERE LOWER(nombre_mando) LIKE LOWER(CONCAT('%', ?, '%')) AND activo = 1
            ");
            $stmtM->execute([$username]);
            $mandoIds = $stmtM->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($mandoIds)) {
                $ph = implode(',', array_fill(0, count($mandoIds), '?'));
                $stmtME = $pdo->prepare("SELECT id FROM empleados WHERE jefe_directo_clave IN ($ph) AND activo = 1");
                $stmtME->execute($mandoIds);
                $subordinadoIds = array_merge($subordinadoIds, $stmtME->fetchAll(PDO::FETCH_COLUMN));
                $subordinadoIds = array_values(array_unique($subordinadoIds));
            }
        }
        
        $inPlaceholders = $subordinadoIds ? implode(',', array_fill(0, count($subordinadoIds), '?')) : '0';
        
        // Estadísticas de RETARDOS - incluye todos los estados
        $sqlRetardos = "SELECT 
                    estado_validacion,
                    COUNT(*) as total
                FROM retardos r
                INNER JOIN empleados e ON r.empleado_id = e.id
                WHERE e.id IN ($inPlaceholders)";
        
        $paramsRetardos = $subordinadoIds;
        
        // Estadísticas de INCIDENCIAS (asistencia)
        $sqlAsistencia = "SELECT 
                    estado_validacion,
                    COUNT(*) as total
                FROM asistencia a
                INNER JOIN empleados e ON a.empleado_id = e.id
                WHERE e.id IN ($inPlaceholders)
                AND a.tipo_asistencia NOT IN ('normal', 'por_definir')";
        
        $paramsAsistencia = $subordinadoIds;
        
        // Filtro por período si se especifica
        if (!empty($periodo['mes']) && !empty($periodo['anio'])) {
            $sqlRetardos .= " AND MONTH(r.fecha) = ? AND YEAR(r.fecha) = ?";
            $sqlAsistencia .= " AND MONTH(a.fecha) = ? AND YEAR(a.fecha) = ?";
            $paramsRetardos[] = $periodo['mes'];
            $paramsRetardos[] = $periodo['anio'];
            $paramsAsistencia[] = $periodo['mes'];
            $paramsAsistencia[] = $periodo['anio'];
        }
        
        $sqlRetardos .= " GROUP BY estado_validacion";
        $sqlAsistencia .= " GROUP BY estado_validacion";
        
        $stmtRetardos = $pdo->prepare($sqlRetardos);
        $stmtRetardos->execute($paramsRetardos);
        
        $statsRetardos = [
            'pendientes' => 0,
            'aprobados' => 0,
            'rechazados' => 0,
            'total' => 0
        ];
        while ($row = $stmtRetardos->fetch(PDO::FETCH_ASSOC)) {
            $estado = strtolower($row['estado_validacion'] ?? 'pendiente');
            $total = (int)$row['total'];
            $statsRetardos['total'] += $total;
            
            if (strpos($estado, 'pendiente') !== false || $estado === '' || $estado === null) {
                $statsRetardos['pendientes'] += $total;
            } elseif ($estado === 'aprobado') {
                $statsRetardos['aprobados'] += $total;
            } elseif ($estado === 'rechazado') {
                $statsRetardos['rechazados'] += $total;
            }
        }
        
        $stmtAsistencia = $pdo->prepare($sqlAsistencia);
        $stmtAsistencia->execute($paramsAsistencia);
        
        $statsAsistencia = [
            'pendientes' => 0,
            'aprobados' => 0,
            'rechazados' => 0,
            'total' => 0
        ];
        while ($row = $stmtAsistencia->fetch(PDO::FETCH_ASSOC)) {
            $estado = strtolower($row['estado_validacion'] ?? 'pendiente');
            $total = (int)$row['total'];
            $statsAsistencia['total'] += $total;
            
            if (strpos($estado, 'pendiente') !== false || $estado === '' || $estado === null) {
                $statsAsistencia['pendientes'] += $total;
            } elseif ($estado === 'aprobado') {
                $statsAsistencia['aprobados'] += $total;
            } elseif ($estado === 'rechazado') {
                $statsAsistencia['rechazados'] += $total;
            }
        }
        
        // Combinar estadísticas
        return [
            'total' => $statsRetardos['total'] + $statsAsistencia['total'],
            'pendientes' => $statsRetardos['pendientes'] + $statsAsistencia['pendientes'],
            'aprobados' => $statsRetardos['aprobados'] + $statsAsistencia['aprobados'],
            'rechazados' => $statsRetardos['rechazados'] + $statsAsistencia['rechazados']
        ];
    }
    
    /**
     * Obtener historial de validaciones de un empleado
     * @param int $empleadoId ID del empleado
     * @param int $limit Límite de resultados
     * @return array Historial de validaciones
     */
    public function getHistorialPorEmpleado($empleadoId, $limit = 50) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT v.*, u.nombre_completo as jefe_nombre,
                    CASE 
                        WHEN v.tipo_incidencia = 'retardo' THEN 
                            (SELECT CONCAT('Retardo de ', r.minutos_retardo, ' minutos') 
                             FROM retardos r WHERE r.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'comision' THEN 
                            (SELECT CONCAT('Comisión: ', c.descripcion, ' - $', 'N/A') 
                             FROM comisiones c WHERE c.id = v.incidencia_id)
                    END as descripcion_incidencia
                FROM validaciones_jefe v
                INNER JOIN usuarios u ON v.jefe_id = u.id
                WHERE v.empleado_id = ?
                ORDER BY v.fecha_solicitud DESC
                LIMIT " . (int)$limit . "";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empleadoId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener validación por ID
     */
    public function getById($id) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT v.*, 
                    e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                    e.area as empleado_area,
                    COALESCE(e.jerarquia, e.puesto, '') as empleado_jerarquia,
                    COALESCE(
                        (SELECT cm.nombre_mando FROM catalogos_mandos cm 
                         INNER JOIN usuarios u ON cm.nombre_mando LIKE CONCAT('%', u.username, '%') 
                         WHERE u.empleado_id = v.jefe_id AND cm.activo = 1 LIMIT 1),
                        CONCAT(je.nombre, ' ', je.apellido)
                    ) as jefe_nombre
                FROM validaciones_jefe v
                INNER JOIN empleados e ON v.empleado_id = e.id
                LEFT JOIN empleados je ON v.jefe_id = je.id
                WHERE v.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualizar incidencia de retardo
     * Si se rechaza: convierte a falta y crea sanción automática
     * Si se aprueba: marca como justificado
     * Si requiere_info: marca como pendiente esperando información
     * VERSIÓN MEJORADA con reglas de negocio completas
     */
    private function actualizarRetardo($retardoId, $decision) {
        $pdo = $this->db->getConnection();
        
        $estado = $decision['estado'];
        $justificado = ($estado === 'aprobado') ? 1 : 0;
        $comentarios = $decision['comentarios'] ?? null;
        
        // Obtener datos del retardo
        $stmtRetardo = $pdo->prepare("SELECT * FROM retardos WHERE id = ?");
        $stmtRetardo->execute([$retardoId]);
        $retardo = $stmtRetardo->fetch(PDO::FETCH_ASSOC);
        
        if (!$retardo) {
            error_log("actualizarRetardo: Retardo $retardoId no encontrado");
            return false;
        }
        
        $empleadoId = $retardo['empleado_id'];
        $tipoRetardo = $retardo['tipo_retraso'] ?? 'retardo';
        $minutosRetardo = $retardo['minutos_retardo'] ?? 0;
        
            // Si se rechaza, convertir a falta y crear sanción automática
        if ($estado === 'rechazado') {
            $motivoRechazo = $decision['motivo'] ?? 'Rechazado por el jefe';
            
            // Determinar la consecuencia según los minutos de retardo
            // Según documento "Lógica de Incidencias":
            // - Tolerancia: 1-10 min (sin consecuencia)
            // - Retardo menor: 11-20 min (2 retardos menores = 1 nota mala)
            // - Retardo mayor: 21-30 min (1 retardo mayor = 1 nota mala)
            // - >30: falta inmediata
            if ($minutosRetardo > 30) {
                $consecuencia = 'falta';
                $tipoNotaMala = 'inasistencia';
                $tipoRetardoNuevo = 'falta';
            } elseif ($minutosRetardo > 20) {
                $consecuencia = 'retardo_mayor';
                $tipoNotaMala = 'retardo_mayor';
                $tipoRetardoNuevo = 'retardo_mayor';
            } elseif ($minutosRetardo > 10) {
                $consecuencia = 'retardo_menor';
                $tipoNotaMala = 'retardo_menor';
                $tipoRetardoNuevo = 'retardo_menor';
            } else {
                $consecuencia = 'tolerancia';
                $tipoNotaMala = null;
                $tipoRetardoNuevo = 'normal';
            }
            
            // Construir motivo de sanción solo si no es tolerancia
            $motivoSancion = "Retardo rechazado por jefe directo ({$minutosRetardo} min). ";
            if (!empty($decision['motivo'])) {
                $motivoSancion .= "Motivo del rechazo: " . $decision['motivo'] . ". ";
            }
            if (!empty($decision['comentarios'])) {
                $motivoSancion .= "Comentario: " . $decision['comentarios'];
            }
            
            // Solo generar nota mala si NO es tolerancia
            if ($tipoNotaMala !== null) {
                $stmtSancion = $pdo->prepare("
                    INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)
                    VALUES (?, ?, ?, 1, ?, ?)
                ");
                $stmtSancion->execute([
                    $empleadoId,
                    $retardoId,
                    $tipoNotaMala,
                    date('Y-m-01'),
                    $motivoSancion
                ]);
                
                $notaMalaId = $pdo->lastInsertId();
                
                // Auditoría
                require_once __DIR__ . '/../services/AuditService.php';
                $audit = AuditService::getInstance();
                $audit->logNotaMala($notaMalaId, $empleadoId, $tipoNotaMala, $motivoSancion);
                
                // Push notification
                require_once __DIR__ . '/../services/PushNotificationService.php';
                $push = PushNotificationService::getInstance();
                $push->notificarNotaMala($empleadoId, $tipoNotaMala, $motivoSancion);
            }
            
            // Actualizar el retardo según el tipo
            $stmtUpdate = $pdo->prepare("
                UPDATE retardos 
                SET justificado = 0, 
                    aprobado_por = ?, 
                    fecha_aprobacion = CURRENT_TIMESTAMP, 
                    estado_validacion = 'rechazado',
                    tipo_retraso = ?,
                    motivo_justificacion = CONCAT(COALESCE(motivo_justificacion, ''), ' | RECHAZADO: ', ?)
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $decision['aprobado_por'],
                $tipoRetardoNuevo,
                $motivoSancion,
                $retardoId
            ]);
            
            // Registrar en log
            $tipoLog = $tipoNotaMala ?? 'tolerancia (sin nota mala)';
            error_log("SANCION: Retardo ID $retardoId rechazado por jefe. Tipo: $tipoLog ($minutosRetardo min). Empleado ID: $empleadoId");
            
            // Si es retardo menor (11-20 min), verificar acumulación (2 retardo menor = 1 nota mala)
            if ($consecuencia === 'retardo_menor') {
                $this->verificarAcumulacionRetardosMenores($empleadoId, $retardoId);
            }
            
            // Verificar acumulación general de retardos (>2 retardos = nota mala)
            $this->verificarAcumulacionGeneralRetardos($empleadoId, $retardoId);
            
            // Verificar límite de 2 retardos por quincena (premio de puntualidad)
            $this->verificarLimiteQuincenalRetardos($empleadoId);
            
            // Verificar notas malas del mes para sanciones (4 = sanción, 5 = suspensión)
            $this->verificarNotasMalasMes($empleadoId);
            
            return true;
        }
        
        // Flujo para aprobado o requiere_info
        $estadoValidacion = ($estado === 'aprobado') ? 'aprobado' : 'pendiente';
        
        $sql = "UPDATE retardos 
                SET justificado = ?, 
                    aprobado_por = ?, 
                    fecha_aprobacion = CURRENT_TIMESTAMP, 
                    estado_validacion = ?,
                    motivo_justificacion = COALESCE(CONCAT(motivo_justificacion, ' | Validación: ', ?), ?)
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $comentarioValidacion = $estado === 'aprobado' 
            ? ('Aprobado por jefe. ' . ($comentarios ?? ''))
            : ('Solicitud de información: ' . ($comentarios ?? '') . '. Motivo: ' . ($decision['motivo'] ?? ''));
        
        return $stmt->execute([
            $justificado, 
            $decision['aprobado_por'], 
            $estadoValidacion,
            $comentarioValidacion,
            $comentarioValidacion,
            $retardoId
        ]);
    }
    
    /**
     * Verificar acumulación de retardos menores en quincena
     * 2 retardos menores en misma quincena = 1 nota mala
     */
    private function verificarAcumulacionRetardosMenores($empleadoId, $retardoIdActual) {
        $pdo = $this->db->getConnection();
        
        // Determinar quincena actual
        $dia = date('j');
        $quincena = $dia <= 15 ? 1 : 2;
        
        // Buscar retardos menores NO justificados en la misma quincena
        $stmtBuscar = $pdo->prepare("
            SELECT id, fecha, minutos_retardo, tipo_retraso 
            FROM retardos 
            WHERE empleado_id = ? 
            AND id != ?
            AND tipo_retraso = 'retardo_menor'
            AND (justificado = 0 OR estado_validacion = 'rechazado')
            AND DATE_FORMAT(fecha, '%Y-%m-%d') >= ?
            AND DATE_FORMAT(fecha, '%Y-%m-%d') < DATE_ADD(?, INTERVAL 15 DAY)
            ORDER BY fecha ASC
        ");
        
        // Calcular fecha inicio de quincena
        $fechaInicioQuincena = ($quincena === 1) 
            ? date('Y-m-01') 
            : date('Y-m-16');
        
        $stmtBuscar->execute([$empleadoId, $retardoIdActual, $fechaInicioQuincena, $fechaInicioQuincena]);
        $retardosMenores = $stmtBuscar->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar retardos menores en esta quincena (incluyendo el actual si aplica)
        $totalRetardosMenores = count($retardosMenores);
        
        // También contar retardos menores rechazados previamente en el mismo período
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM notas_malas 
            WHERE empleado_id = ? 
            AND tipo = 'retardo_menor'
            AND periodo = ?
        ");
        $stmtCount->execute([$empleadoId, date('Y-m-01')]);
        $notasPrevias = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $notasPreviasCount = (int)($notasPrevias['total'] ?? 0);
        
        // Calcular retardos pendientes que podrían formar nota mala
        // Por cada nota mala hay 2 retardos menores
        $retardosFormandoNotas = $notasPreviasCount * 2;
        $retardosRestantes = $totalRetardosMenores - $retardosFormandoNotas;
        
        // Si tenemos 2 o más retardos menores restantes, generar nota mala
        if ($totalRetardosMenores >= 2) {
            $motivo = "Acumulación de retardos menores en quincena ($totalRetardosMenores retardos menores). ";
            $motivo .= "Esta nota mala se genera automáticamente conforme a las reglas institucionales: ";
            $motivo .= "2 retardos menores = 1 nota mala.";
            
            // Insertar nota mala por acumulación
            $stmtNota = $pdo->prepare("
                INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)
                VALUES (?, ?, 'retardo_menor', 1, ?, ?)
            ");
            $stmtNota->execute([
                $empleadoId,
                $retardoIdActual,
                date('Y-m-01'),
                $motivo
            ]);
            
            // Marcar los retardos menores relacionados como "procesados" o "convertidos"
            $stmtMarcar = $pdo->prepare("
                UPDATE retardos 
                SET justificado = 0,
                    estado_validacion = 'rechazado',
                    motivo_justificacion = CONCAT(COALESCE(motivo_justificacion, ''), ' | CONVERTIDO A NOTA MALA por acumulación')
                WHERE id = ?
            ");
            
            // Marcar todos los retardo menores de esta quincena
            foreach ($retardosMenores as $rm) {
                $stmtMarcar->execute([$rm['id']]);
            }
            
            // También marcar el retardo actual si es retardo menor
            $stmtMarcarActual = $pdo->prepare("
                UPDATE retardos 
                SET justificado = 0,
                    estado_validacion = 'rechazado',
                    motivo_justificacion = CONCAT(COALESCE(motivo_justificacion, ''), ' | CONVERTIDO A NOTA MALA por acumulación')
                WHERE id = ? AND tipo_retraso = 'retardo_menor'
            ");
            $stmtMarcarActual->execute([$retardoIdActual]);
            
            error_log("ACUMULACIÓN: Empleado $empleadoId tiene $totalRetardosMenores retardos menores en quincena. Generada nota mala por acumulación.");
        }
        
        return true;
    }
    
    /**
     * Verificar acumulación general de retardos
     * Más de 2 retardos (menores, mayores o combinados) sin justificar = nota mala
     */
    private function verificarAcumulacionGeneralRetardos($empleadoId, $retardoIdActual) {
        $pdo = $this->db->getConnection();
        
        // Determinar quincena actual
        $dia = date('j');
        $quincena = $dia <= 15 ? 1 : 2;
        $fechaInicioQuincena = ($quincena === 1) ? date('Y-m-01') : date('Y-m-16');
        
        // Contar TODOS los retardos no justificados en la quincena (menores + mayores)
        $stmtTotal = $pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN tipo_retraso = 'retardo_menor' THEN 1 ELSE 0 END) as menores,
                   SUM(CASE WHEN tipo_retraso = 'retardo_mayor' THEN 1 ELSE 0 END) as mayores
            FROM retardos 
            WHERE empleado_id = ? 
            AND id != ?
            AND tipo_retraso IN ('retardo_menor', 'retardo_mayor')
            AND (justificado = 0 OR estado_validacion = 'rechazado')
            AND DATE_FORMAT(fecha, '%Y-%m-%d') >= ?
            AND DATE_FORMAT(fecha, '%Y-%m-%d') < DATE_ADD(?, INTERVAL 15 DAY)
        ");
        
        $stmtTotal->execute([$empleadoId, $retardoIdActual, $fechaInicioQuincena, $fechaInicioQuincena]);
        $totales = $stmtTotal->fetch(PDO::FETCH_ASSOC);
        
        $totalRetardos = (int)($totales['total'] ?? 0);
        $menores = (int)($totales['menores'] ?? 0);
        $mayores = (int)($totales['mayores'] ?? 0);
        
        // Si hay más de 2 retardos (combinados), generar nota mala por acumulación
        if ($totalRetardos > 2) {
            $motivo = "Acumulación de retardos en quincena: $menores menores + $mayores mayores = $totalRetardos total. ";
            $motivo .= "Más de 2 retardos sin justificar generan nota mala según reglas institucionales.";
            
            $stmtNota = $pdo->prepare("
                INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)
                VALUES (?, ?, 'otro', 1, ?, ?)
            ");
            $stmtNota->execute([
                $empleadoId,
                $retardoIdActual,
                date('Y-m-01'),
                $motivo
            ]);
            
            error_log("ACUMULACIÓN GENERAL: Empleado $empleadoId tiene $totalRetardos retardos en quincena ($menores menores + $mayores mayores). Generada nota mala por acumulación.");
        }
        
        return true;
    }
    
    /**
     * Verificar límite de 2 retardos por quincena (premio de puntualidad)
     * Si se sobrepasa, generar alerta de pérdida de premio
     */
    private function verificarLimiteQuincenalRetardos($empleadoId) {
        $pdo = $this->db->getConnection();
        
        // Determinar quincena actual
        $dia = date('j');
        $quincena = $dia <= 15 ? 1 : 2;
        $fechaInicioQuincena = ($quincena === 1) ? date('Y-m-01') : date('Y-m-16');
        
        // Contar TODOS los retardos en la quincena (incluyendo los ya rechazados)
        $stmtTotal = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM retardos 
            WHERE empleado_id = ? 
            AND tipo_retraso IN ('retardo_menor', 'retardo_mayor', 'retardo')
            AND DATE_FORMAT(fecha, '%Y-%m-%d') >= ?
            AND DATE_FORMAT(fecha, '%Y-%m-%d') < DATE_ADD(?, INTERVAL 15 DAY)
        ");
        
        $stmtTotal->execute([$empleadoId, $fechaInicioQuincena, $fechaInicioQuincena]);
        $totales = $stmtTotal->fetch(PDO::FETCH_ASSOC);
        $totalRetardos = (int)($totales['total'] ?? 0);
        
        // Si se sobrepasan los 2 retardos, generar alerta de pérdida de puntualidad
        if ($totalRetardos > 2) {
            $quincenaTexto = ($quincena === 1) ? "primera" : "segunda";
            
            // Crear alerta de pérdida de premio de puntualidad
            $stmtAlerta = $pdo->prepare("
                INSERT INTO alertas_tempranas (empleado_id, tipo, nivel, mensaje, datos_json, leida, created_at)
                VALUES (?, 'advertencia', 'alta', ?, ?, 0, NOW())
            ");
            
            $mensaje = "⚠️ <strong>Pérdida de Premio de Puntualidad</strong>: ";
            $mensaje .= "Has acumulado <strong>{$totalRetardos} retardos</strong> en la {$quincenaTexto} quincena. ";
            $mensaje .= "El límite permitido es de 2 retardos por quincena. ";
            $mensaje .= "Ya no tienes derecho al premio de puntualidad de esta quincena.";
            
            $datosJson = json_encode([
                'tipo_alerta' => 'perdida_puntualidad',
                'quincena' => $quincena,
                'total_retardos' => $totalRetardos,
                'limite' => 2,
                'fecha' => date('Y-m-d')
            ]);
            
            $stmtAlerta->execute([$empleadoId, $mensaje, $datosJson]);
            
            error_log("PUNTUALIDAD: Empleado $empleadoId ha perdido el premio de puntualidad. Total retardos en quincena: $totalRetardos");
        }
        
        return true;
    }
    
    /**
     * Verificar notas malas del mes para sanciones
     * 4 notas malas sin justificar = sanción
     * 5 notas malas = 1 día de suspensión
     * 7 suspensiones en el año = despido
     */
    public function verificarNotasMalasMes($empleadoId) {
        $pdo = $this->db->getConnection();
        
        // Contar notas malas sin justificar en el mes actual
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM notas_malas 
            WHERE empleado_id = ?
            AND MONTH(periodo) = MONTH(CURRENT_DATE)
            AND YEAR(periodo) = YEAR(CURRENT_DATE)
        ");
        
        $stmtCount->execute([$empleadoId]);
        $resultado = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $totalNotas = (int)($resultado['total'] ?? 0);
        
        // 4 notas = sanción, 5 notas = suspensión
        if ($totalNotas >= 4) {
            $stmtAlerta = $pdo->prepare("
                INSERT INTO alertas_tempranas (empleado_id, tipo, nivel, mensaje, datos_json, leida, created_at)
                VALUES (?, 'critica', 'urgente', ?, ?, 0, NOW())
            ");
            
            if ($totalNotas >= 5) {
                $mensaje = "🛑 <strong>Alerta de Suspensión</strong>: ";
                $mensaje .= "Has acumulado <strong>{$totalNotas} notas malas</strong> en el mes. ";
                $mensaje .= "Conforme a las reglas institucionales, 5 notas malas generan <strong>1 día de suspensión</strong>. ";
                $mensaje .= "Favor de contactar a Recursos Humanos.";
                
                $tipoSancion = 'suspension';
            } else {
                $mensaje = "⚠️ <strong>Alerta de Sanción</strong>: ";
                $mensaje .= "Has acumulado <strong>{$totalNotas} notas malas</strong> en el mes. ";
                $mensaje .= "Al llegar a 4 notas malas sin justificar se genera sanción. ";
                $mensaje .= "Favor de justificar tus retardos o contacta a Recursos Humanos.";
                
                $tipoSancion = 'sancion';
            }
            
            $datosJson = json_encode([
                'tipo_alerta' => $tipoSancion,
                'total_notas_malas' => $totalNotas,
                'mes' => date('Y-m'),
                'limite_sancion' => 4,
                'limite_suspension' => 5
            ]);
            
            $stmtAlerta->execute([$empleadoId, $mensaje, $datosJson]);
            
            // Push notification para sanción/suspensión
            require_once __DIR__ . '/../services/PushNotificationService.php';
            $push = PushNotificationService::getInstance();
            $push->notificarSancion($empleadoId, $tipoSancion, strip_tags($mensaje));
            
            error_log("SANCION: Empleado $empleadoId tiene $totalNotas notas malas en el mes. Tipo: $tipoSancion");
        }
        
        // Verificar suspensiones en el año (7 suspensiones = despido)
        $this->verificarSuspensionesAnuales($empleadoId);
        
        return true;
    }
    
    /**
     * Verificar suspensiones anuales para despido
     * 7 suspensiones en el año = despido del puesto
     */
    private function verificarSuspensionesAnuales($empleadoId) {
        $pdo = $this->db->getConnection();
        
        // Contar notas malas que generaron suspensión en el año (5+ notas)
        $stmtSuspensiones = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM notas_malas 
            WHERE empleado_id = ?
            AND YEAR(periodo) = YEAR(CURRENT_DATE)
            AND (
                tipo = 'inasistencia' 
                OR tipo = 'falta_retardo_rechazado'
                OR cantidad >= 1
            )
        ");
        
        $stmtSuspensiones->execute([$empleadoId]);
        $resultado = $stmtSuspensiones->fetch(PDO::FETCH_ASSOC);
        $totalSuspensiones = (int)($resultado['total'] ?? 0);
        
        // Contar alertas de suspensión ya registradas este año
        $stmtAlertas = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM alertas_tempranas 
            WHERE empleado_id = ?
            AND YEAR(created_at) = YEAR(CURRENT_DATE)
            AND datos_json LIKE '%\"tipo_alerta\":\"suspension\"%'
        ");
        
        $stmtAlertas->execute([$empleadoId]);
        $resultadoAlertas = $stmtAlertas->fetch(PDO::FETCH_ASSOC);
        $alertasSuspension = (int)($resultadoAlertas['total'] ?? 0);
        
        // Si hay 7 o más suspensiones generadas, alertar de despido
        if ($alertasSuspension >= 7) {
            $stmtAlerta = $pdo->prepare("
                INSERT INTO alertas_tempranas (empleado_id, tipo, nivel, mensaje, datos_json, leida, created_at)
                VALUES (?, 'critica', 'urgente', ?, ?, 0, NOW())
            ");
            
            $mensaje = "🛑 <strong>ALERTA CRÍTICA - DESPIDO</strong>: ";
            $mensaje .= "Has acumulado <strong>{$alertasSuspension} suspensiones</strong> en el año. ";
            $mensaje .= "Conforme a las reglas institucionales, <strong>7 suspensiones en el año generan despido del puesto</strong>. ";
            $mensaje .= "Favor de contactar a Recursos Humanos de inmediato.";
            
            $datosJson = json_encode([
                'tipo_alerta' => 'despido',
                'total_suspensiones' => $alertasSuspension,
                'año' => date('Y'),
                'limite_despido' => 7
            ]);
            
            $stmtAlerta->execute([$empleadoId, $mensaje, $datosJson]);
            
            // Push notification crítica para despido
            require_once __DIR__ . '/../services/PushNotificationService.php';
            $push = PushNotificationService::getInstance();
            $push->notificarAlertaCritica($empleadoId, 'despido', strip_tags($mensaje));
            
            error_log("DESPIDO: Empleado $empleadoId ha acumulado $alertasSuspension suspensiones en el año. Se genera alerta de despido.");
        }
        
        return true;
    }
    
    /**
     * Actualizar incidencia de comisión
     * Si se rechaza: crea una falta y registra en notas_malas
     * Si se aprueba: marca como justificada
     * Si requiere_info: marca como pendiente esperando información
     */
    private function actualizarComision($comisionId, $decision) {
        $pdo = $this->db->getConnection();
        $estado = $decision['estado'];
        
        // Si se rechaza, crear falta y sanción
        if ($estado === 'rechazado') {
            // Verificar si viene de un retardo
            $stmtCheckRetardo = $pdo->prepare("SELECT id, empleado_id, fecha, motivo_justificacion, tipo_retraso FROM retardos WHERE id = ?");
            $stmtCheckRetardo->execute([$comisionId]);
            $retardo = $stmtCheckRetardo->fetch();
            
            if ($retardo) {
                // Crear nota mala por rechazo de comisión
                $motivoSancion = "Comisión rechazada por jefe directo. ";
                if (!empty($decision['motivo'])) {
                    $motivoSancion .= "Motivo del rechazo: " . $decision['motivo'] . ". ";
                }
                if (!empty($decision['comentarios'])) {
                    $motivoSancion .= "Comentario: " . $decision['comentarios'];
                }
                
                // Insertar en tabla de notas_malas
                $stmtSancion = $pdo->prepare("
                    INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)
                    VALUES (?, ?, 'comision_rechazada', 1, ?, ?)
                ");
                $stmtSancion->execute([
                    $retardo['empleado_id'],
                    $comisionId,
                    date('Y-m-01'),
                    $motivoSancion
                ]);
                
                // Actualizar el retardo como rechazado (convertido a falta)
                $stmtUpdateRetardo = $pdo->prepare("
                    UPDATE retardos 
                    SET justificado = 0, 
                        aprobado_por = ?, 
                        fecha_aprobacion = CURRENT_TIMESTAMP, 
                        estado_validacion = 'rechazado',
                        motivo_justificacion = CONCAT(COALESCE(motivo_justificacion, ''), ' | FALTA: ', ?)
                    WHERE id = ?
                ");
                $stmtUpdateRetardo->execute([
                    $decision['aprobado_por'],
                    $motivoSancion,
                    $comisionId
                ]);
                
                // Crear registro en tabla comisiones si no existe
                $stmtCheckComision = $pdo->prepare("SELECT id FROM comisiones WHERE id = ?");
                $stmtCheckComision->execute([$comisionId]);
                if (!$stmtCheckComision->fetch()) {
                    $stmtInsertComision = $pdo->prepare("
                        INSERT INTO comisiones 
                        (empleado_id, descripcion, fecha_inicio, fecha_fin, estatus, requiere_evidencia, requiere_aprobacion, aprobado_por, fecha_aprobacion, motivo_aprobacion, fecha_asignacion)
                        VALUES (?, ?, ?, ?, 'rechazada', 0, 0, ?, NOW(), ?, NOW())
                    ");
                    $stmtInsertComision->execute([
                        $retardo['empleado_id'],
                        $retardo['motivo_justificacion'] ?? 'Comisión rechazada',
                        $retardo['fecha'],
                        $retardo['fecha'],
                        $decision['aprobado_por'],
                        $motivoSancion
                    ]);
                }
                
                // Actualizar asistencia como FALTA por rechazo
                $stmtUpdateAsistencia = $pdo->prepare("
                    UPDATE asistencia 
                    SET tipo_asistencia = 'falta'
                    WHERE empleado_id = ? AND fecha = ?
                ");
                $stmtUpdateAsistencia->execute([
                    $retardo['empleado_id'],
                    $retardo['fecha']
                ]);
                
                error_log("SANCION: Comisión ID $comisionId rechazada - convertida a falta. Empleado ID: {$retardo['empleado_id']}");
                return true;
            }
        }
        
        // Flujo normal para aprobado o requiere_info
        $estadoComision = match($estado) {
            'aprobado' => 'aprobada',
            'rechazado' => 'rechazada',
            'requiere_info' => 'pendiente',
            default => 'pendiente'
        };
        
        // Primero verificar si este ID existe en la tabla comisiones
        $stmtCheck = $pdo->prepare("SELECT id FROM comisiones WHERE id = ?");
        $stmtCheck->execute([$comisionId]);
        $existeEnComisiones = $stmtCheck->fetch();
        
        // Si no existe en comisiones, verificar si viene de un retardo
        if (!$existeEnComisiones) {
            $stmtCheckRetardo = $pdo->prepare("SELECT id, empleado_id, fecha, motivo_justificacion, tipo_retraso FROM retardos WHERE id = ?");
            $stmtCheckRetardo->execute([$comisionId]);
            $retardo = $stmtCheckRetardo->fetch();
            
            // Si viene de un retardo, crear el registro en comisiones con el estado correspondiente.
            if ($retardo) {
                $descripcion = $retardo['motivo_justificacion'] ?? 'Comisión registrada desde retardo';
                
                // Crear registro en tabla comisiones
                $sqlInsert = "INSERT INTO comisiones 
                    (empleado_id, descripcion, fecha_inicio, fecha_fin, estatus, requiere_evidencia, requiere_aprobacion, aprobado_por, fecha_aprobacion, motivo_aprobacion, fecha_asignacion)
                    VALUES (?, ?, ?, ?, ?, 0, 0, ?, NOW(), ?, NOW())";
                
                $stmtInsert = $pdo->prepare($sqlInsert);
                $comentarioValidacion = $estado === 'aprobado'
                    ? ('Aprobado por jefe. ' . ($decision['comentarios'] ?? ''))
                    : ('Solicitud de información: ' . ($decision['comentarios'] ?? '') . '. Motivo: ' . ($decision['motivo'] ?? ''));
                
                $stmtInsert->execute([
                    $retardo['empleado_id'],
                    $descripcion,
                    $retardo['fecha'],
                    $retardo['fecha'],
                    $estadoComision,
                    $decision['aprobado_por'],
                    $comentarioValidacion
                ]);
                
                // Obtener ID del retardo y actualizar la asistencia
                $retardoId = $retardo['id'];
                $empleadoId = $retardo['empleado_id'];
                $fechaRetardo = $retardo['fecha'];
                
                // Determinar tipo de asistencia según el tipo de retardo
                $tipoAsistencia = 'comision_todo_dia';
                if (!empty($retardo['tipo_retraso'])) {
                    $tipoRetardo = strtolower($retardo['tipo_retraso']);
                    if ($tipoRetardo === 'comision_entrada') {
                        $tipoAsistencia = 'comision_entrada';
                    } elseif ($tipoRetardo === 'comision_salida') {
                        $tipoAsistencia = 'comision_salida';
                    } elseif ($tipoRetardo === 'comision_todo_dia' || $tipoRetardo === 'comision') {
                        $tipoAsistencia = 'comision_todo_dia';
                    }
                }
                
                // Actualizar la tabla asistencia
                $stmtUpdateAsistencia = $pdo->prepare("
                    UPDATE asistencia 
                    SET tipo_asistencia = ?, 
                        hora_entrada = NULL, 
                        hora_salida = NULL
                    WHERE empleado_id = ? AND fecha = ?
                ");
                $stmtUpdateAsistencia->execute([
                    $tipoAsistencia,
                    $empleadoId,
                    $fechaRetardo
                ]);
                
                // Actualizar el retardo para indicar que ya tiene comisión creada
                $stmtUpdateRetardo = $pdo->prepare("UPDATE retardos SET requiere_validacion_jefe = 0 WHERE id = ?");
                $stmtUpdateRetardo->execute([$comisionId]);
                
                return true;
            }
        }
        
        // Flujo normal: actualizar la tabla comisiones directamente
        $comentarioValidacion = $estado === 'aprobado'
            ? ('Aprobado por jefe. ' . ($decision['comentarios'] ?? ''))
            : ('Rechazado por jefe. Motivo: ' . ($decision['motivo'] ?? '') . '. Comentarios: ' . ($decision['comentarios'] ?? ''));
        
        // Primero buscar la comisión para obtener datos del empleado y fecha
        $stmtGetComision = $pdo->prepare("SELECT * FROM comisiones WHERE id = ?");
        $stmtGetComision->execute([$comisionId]);
        $comision = $stmtGetComision->fetch();
        
        $sql = "UPDATE comisiones 
                SET estatus = ?, aprobado_por = ?, fecha_aprobacion = CURRENT_TIMESTAMP, motivo_aprobacion = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$estadoComision, $decision['aprobado_por'], $comentarioValidacion, $comisionId]);
        
        // Actualizar la tabla asistencia según el estado
        if ($comision) {
            $empleadoId = $comision['empleado_id'];
            $fechaInicio = $comision['fecha_inicio'];
            $fechaFin = $comision['fecha_fin'];
            $tipoComision = $comision['tipo_comision'] ?? 'comision_todo_dia';
            
            if ($estado === 'aprobado') {
                // Actualizar a tipo comisión correspondiente
                $stmtUpdateAsistencia = $pdo->prepare("
                    UPDATE asistencia 
                    SET tipo_asistencia = ?, 
                        hora_entrada = NULL, 
                        hora_salida = NULL
                    WHERE empleado_id = ? AND fecha BETWEEN ? AND ?
                ");
                $stmtUpdateAsistencia->execute([
                    $tipoComision,
                    $empleadoId,
                    $fechaInicio,
                    $fechaFin
                ]);
            } elseif ($estado === 'rechazado') {
                // Actualizar a tipo falta por rechazo
                $stmtUpdateAsistencia = $pdo->prepare("
                    UPDATE asistencia 
                    SET tipo_asistencia = 'falta'
                    WHERE empleado_id = ? AND fecha BETWEEN ? AND ?
                ");
                $stmtUpdateAsistencia->execute([
                    $empleadoId,
                    $fechaInicio,
                    $fechaFin
                ]);
                error_log("COMISION RECHAZADA: ID $comisionId - Asistencia actualizada a tipo: falta");
            }
        }
        
        return $result;
    }
    
    /**
     * Actualizar incidencia de día económico
     */
    private function actualizarDiaEconomico($diaEconomicoId, $decision) {
        $pdo = $this->db->getConnection();
        
        $estatus = match($decision['estado']) {
            'aprobado' => 'aprobado',
            'rechazado' => 'rechazado',
            'requiere_info' => 'pendiente',
            default => 'pendiente'
        };
        $justificado = ($decision['estado'] === 'aprobado') ? 1 : 0;
        $comentarios = $decision['comentarios'] ?? null;
        $motivo = $decision['motivo'] ?? null;
        
        $sql = "UPDATE dias_economicos 
                SET estatus = ?, justificado = ?, aprobado_por = ?, fecha_aprobacion = CURRENT_TIMESTAMP,
                    comentarios_aprobacion = ?, motivo_rechazo = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([$estatus, $justificado, $decision['aprobado_por'], $comentarios, $motivo, $diaEconomicoId]);
    }
    
    /**
     * Actualizar incidencia de ausencia
     */
    private function actualizarAusencia($ausenciaId, $decision) {
        $pdo = $this->db->getConnection();

        // La tabla ausencias no tiene columnas de estado/aprobación.
        // Solo persistimos una nota de trazabilidad en motivo; el estado formal vive en validaciones_jefe.
        $estadoTexto = match($decision['estado']) {
            'aprobado' => 'aprobado',
            'rechazado' => 'rechazado',
            'requiere_info' => 'requiere_info',
            default => 'pendiente'
        };
        $motivo = trim((string)($decision['motivo'] ?? ''));
        $comentarios = trim((string)($decision['comentarios'] ?? ''));
        $nota = "\n[Validación jefe: {$estadoTexto}]";
        if ($motivo !== '') {
            $nota .= " Motivo: {$motivo}.";
        }
        if ($comentarios !== '') {
            $nota .= " Comentario: {$comentarios}.";
        }

        $sql = "UPDATE ausencias
                SET motivo = TRIM(CONCAT(COALESCE(motivo, ''), ?))
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$nota, $ausenciaId]);
    }
    
    /**
     * Actualizar incidencia de constancia de tiempo
     */
    private function actualizarConstanciaTiempo($constanciaId, $decision) {
        $pdo = $this->db->getConnection();

        $estatus = match($decision['estado']) {
            'aprobado' => 'aprobada',
            'rechazado' => 'rechazada',
            'requiere_info' => 'pendiente',
            default => 'pendiente'
        };

        $sql = "UPDATE constancias_tiempo
                SET estatus = ?, aprobado_por = ?, fecha_aprobacion = CURRENT_TIMESTAMP,
                    motivo = TRIM(CONCAT(COALESCE(motivo, ''), '\n[Validación jefe: ', ?, ']'))
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $nota = $estatus . (!empty($decision['comentarios']) ? ' - ' . $decision['comentarios'] : '');
        return $stmt->execute([$estatus, $decision['aprobado_por'], $nota, $constanciaId]);
    }

    private function actualizarLicenciaMedica($licenciaId, $decision) {
        $pdo = $this->db->getConnection();

        $estatus = match($decision['estado']) {
            'aprobado' => 'aprobada',
            'rechazado' => 'rechazada',
            'requiere_info' => 'pendiente',
            default => 'pendiente'
        };

        $sql = "UPDATE licencias_medicas
                SET estatus = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$estatus, $licenciaId]);
    }

    private function actualizarJustificacion($justificacionId, $decision) {
        $pdo = $this->db->getConnection();

        $estatus = match($decision['estado']) {
            'aprobado' => 'aprobada',
            'rechazado' => 'rechazada',
            'requiere_info' => 'pendiente',
            default => 'pendiente'
        };

        $sql = "UPDATE justificaciones
                SET estatus = ?, aprobado_por = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$estatus, $decision['aprobado_por'], $justificacionId]);
    }

    /**
     * Actualizar la tabla asistencia con el resultado de la validación
     * para todos los tipos de incidencia incluyendo retardos
     */
    private function actualizarAsistenciaPorValidacion($validacion, $decision) {
        $pdo = $this->db->getConnection();
        $tipo = $validacion['tipo_incidencia'];
        $empleadoId = $validacion['empleado_id'];
        $incidenciaId = $validacion['incidencia_id'];

        $estadoTexto = $decision['estado'] === 'aprobado' ? 'aprobado' : ($decision['estado'] === 'rechazado' ? 'rechazado' : 'pendiente');

        $fechaInicio = null;
        $fechaFin = null;

        switch ($tipo) {
            case 'retardo':
                $stmt = $pdo->prepare("SELECT fecha, empleado_id FROM retardos WHERE id = ?");
                $stmt->execute([$incidenciaId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $fechaInicio = $row['fecha'];
                    $fechaFin = $row['fecha'];
                }
                break;

            case 'comision':
                $stmt = $pdo->prepare("SELECT fecha_inicio, fecha_fin FROM comisiones WHERE id = ?");
                $stmt->execute([$incidenciaId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $fechaInicio = $row['fecha_inicio'];
                    $fechaFin = $row['fecha_fin'];
                }
                break;

            case 'dia_economico':
                $stmt = $pdo->prepare("SELECT fecha FROM dias_economicos WHERE id = ?");
                $stmt->execute([$incidenciaId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $fechaInicio = $row['fecha'];
                    $fechaFin = $row['fecha'];
                }
                break;

            case 'ausencia':
                $stmt = $pdo->prepare("SELECT fecha_inicio, fecha_fin FROM ausencias WHERE id = ?");
                $stmt->execute([$incidenciaId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $fechaInicio = $row['fecha_inicio'];
                    $fechaFin = $row['fecha_fin'];
                }
                break;

            case 'constancia_tiempo':
                $stmt = $pdo->prepare("SELECT fecha_inicio, fecha_fin FROM constancias_tiempo WHERE id = ?");
                $stmt->execute([$incidenciaId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $fechaInicio = $row['fecha_inicio'];
                    $fechaFin = $row['fecha_fin'];
                }
                break;

            case 'licencia_medica':
                $stmt = $pdo->prepare("SELECT fecha_inicio, fecha_fin FROM licencias_medicas WHERE id = ?");
                $stmt->execute([$incidenciaId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $fechaInicio = $row['fecha_inicio'];
                    $fechaFin = $row['fecha_fin'];
                }
                break;

            case 'justificacion':
                $stmt = $pdo->prepare("SELECT fecha_inicio, fecha_fin FROM justificaciones WHERE id = ?");
                $stmt->execute([$incidenciaId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $fechaInicio = $row['fecha_inicio'];
                    $fechaFin = $row['fecha_fin'];
                }
                break;
        }

        if ($fechaInicio && $fechaFin) {
            $sql = "UPDATE asistencia
                    SET estado_validacion = ?,
                        validado_por_jefe = ?,
                        fecha_aprobacion = NOW()
                    WHERE empleado_id = ?
                    AND fecha BETWEEN ? AND ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$estadoTexto, $decision['aprobado_por'], $empleadoId, $fechaInicio, $fechaFin]);
        }
    }

    /**
     * Validar datos para creación de validación
     */
    private function validarDatosCreacion($data) {
        $errors = [];
        
        if (empty($data['incidencia_id'])) {
            $errors[] = 'El ID de incidencia es obligatorio';
        }
        
        if (empty($data['tipo_incidencia'])) {
            $errors[] = 'El tipo de incidencia es obligatorio';
        } elseif (!in_array($data['tipo_incidencia'], ['retardo', 'comision', 'dia_economico', 'ausencia', 'constancia_tiempo', 'licencia_medica', 'vacaciones', 'justificacion'])) {
            $errors[] = 'Tipo de incidencia no válido';
        }
        
        if (empty($data['empleado_id'])) {
            $errors[] = 'El ID del empleado es obligatorio';
        }
        
        if (empty($data['jefe_id'])) {
            $errors[] = 'El ID del jefe es obligatorio';
        }
        
        return $errors;
    }
    
    /**
     * Obtener incidencias que requieren validación automática
     * @return array Lista de incidencias para crear validaciones
     */
    public function getIncidenciasParaValidacion() {
        $pdo = $this->db->getConnection();
        
        $incidencias = [];
        
        // Retardos que requieren validación
        $sqlRetardos = "SELECT r.id as incidencia_id, 'retardo' as tipo_incidencia, 
                           r.empleado_id, e.jefe_directo_id as jefe_id,
                           CONCAT('Retardo de ', r.minutos_retardo, ' minutos') as motivo_validacion,
                           CASE 
                               WHEN r.minutos_retardo > 60 THEN TRUE
                               ELSE FALSE
                           END as evidencia_requerida
                        FROM retardos r
                        INNER JOIN empleados e ON r.empleado_id = e.id
                        WHERE r.justificado = 0 
                        AND r.id NOT IN (
                            SELECT incidencia_id FROM validaciones_jefe 
                            WHERE tipo_incidencia = 'retardo'
                        )";
        
        $stmt = $pdo->prepare($sqlRetardos);
        $stmt->execute();
        $incidencias = array_merge($incidencias, $stmt->fetchAll(PDO::FETCH_ASSOC));
        
        // Comisiones que requieren validación
        $sqlComisiones = "SELECT c.id as incidencia_id, 'comision' as tipo_incidencia,
                             c.empleado_id, e.jefe_directo_id as jefe_id,
                             CONCAT('Comisión: ', c.descripcion, ' - $', 'N/A') as motivo_validacion,
                             CASE 
                                 WHEN 'N/A' > 1000 THEN TRUE
                                 ELSE FALSE
                             END as evidencia_requerida
                          FROM comisiones c
                          INNER JOIN empleados e ON c.empleado_id = e.id
                          WHERE c.estatus = 'solicitada'
                          AND c.id NOT IN (
                              SELECT incidencia_id FROM validaciones_jefe 
                              WHERE tipo_incidencia = 'comision'
                          )";
        
        $stmt = $pdo->prepare($sqlComisiones);
        $stmt->execute();
        $incidencias = array_merge($incidencias, $stmt->fetchAll(PDO::FETCH_ASSOC));
        
        // Justificaciones que requieren validación
        $sqlJustificaciones = "SELECT j.id as incidencia_id, 'justificacion' as tipo_incidencia,
                                  j.empleado_id, e.jefe_directo_id as jefe_id,
                                  CONCAT('Justificación: ', j.motivo) as motivo_validacion,
                                  CASE WHEN j.tipo_justificacion_id IS NOT NULL THEN TRUE ELSE FALSE END as evidencia_requerida
                               FROM justificaciones j
                               INNER JOIN empleados e ON j.empleado_id = e.id
                               WHERE j.estatus = 'pendiente'
                               AND j.id NOT IN (
                                   SELECT incidencia_id FROM validaciones_jefe 
                                   WHERE tipo_incidencia = 'justificacion'
                               )";
        
        $stmt = $pdo->prepare($sqlJustificaciones);
        $stmt->execute();
        $incidencias = array_merge($incidencias, $stmt->fetchAll(PDO::FETCH_ASSOC));
        
        return $incidencias;
    }
    
    /**
     * Procesar incidencias pendientes y crear validaciones automáticamente
     * @return array Resultado del procesamiento
     */
    public function procesarIncidenciasPendientes() {
        $incidencias = $this->getIncidenciasParaValidacion();
        $procesadas = 0;
        $errores = [];
        
        foreach ($incidencias as $incidencia) {
            try {
                // Verificar que el empleado tenga jefe asignado
                if (empty($incidencia['jefe_id'])) {
                    $errores[] = "Empleado {$incidencia['empleado_id']} sin jefe asignado";
                    continue;
                }
                
                // Calcular fecha límite (48 horas)
                $fechaLimite = date('Y-m-d H:i:s', strtotime('+48 hours'));
                
                $this->crear([
                    'incidencia_id' => $incidencia['incidencia_id'],
                    'tipo_incidencia' => $incidencia['tipo_incidencia'],
                    'empleado_id' => $incidencia['empleado_id'],
                    'jefe_id' => $incidencia['jefe_id'],
                    'motivo_validacion' => $incidencia['motivo_validacion'],
                    'evidencia_requerida' => $incidencia['evidencia_requerida'],
                    'fecha_limite' => $fechaLimite
                ]);
                
                $procesadas++;
                
            } catch (Exception $e) {
                $errores[] = "Error procesando incidencia {$incidencia['incidencia_id']}: " . $e->getMessage();
            }
        }
        
        return [
            'total_incidencias' => count($incidencias),
            'procesadas' => $procesadas,
            'errores' => $errores
        ];
    }

    // ========================================================================
    // SISTEMA DE CONVERSACIÓN BIDIRECCIONAL (JEFE ↔ EMPLEADO)
    // ========================================================================

    /**
     * Agregar un mensaje a una validación
     * @param int $validacionId ID de la validación
     * @param string $remitenteTipo jefe|empleado|sistema
     * @param int $remitenteId ID del usuario que envía
     * @param string $mensaje Contenido del mensaje
     * @param string $tipoMensaje info_request|info_response|decision|notificacion|sistema
     * @param string|null $archivoAdjunto Ruta del archivo adjunto
     * @return int ID del mensaje creado
     */
    public function agregarMensaje($validacionId, $remitenteTipo, $remitenteId, $mensaje, $tipoMensaje = 'info_response', $archivoAdjunto = null) {
        $pdo = $this->db->getConnection();
        
        // Obtener nombre del remitente
        $nombre = '';
        if ($remitenteTipo === 'jefe' || $remitenteTipo === 'empleado') {
            $stmt = $pdo->prepare("
                SELECT COALESCE(u.nombre_completo, CONCAT(e.nombre, ' ', e.apellido), 'Usuario') as nombre
                FROM usuarios u
                LEFT JOIN empleados e ON u.empleado_id = e.id
                WHERE u.id = ?
            ");
            $stmt->execute([$remitenteId]);
            $nombre = $stmt->fetchColumn() ?: 'Usuario';
        } elseif ($remitenteTipo === 'sistema') {
            $nombre = 'Sistema';
        }
        
        $sql = "INSERT INTO validacion_mensajes 
                (validacion_id, remitente_tipo, remitente_id, remitente_nombre, mensaje, tipo_mensaje, archivo_adjunto, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$validacionId, $remitenteTipo, $remitenteId, $nombre, $mensaje, $tipoMensaje, $archivoAdjunto]);
        $msgId = $pdo->lastInsertId();
        
        // Actualizar la validación con el último mensaje
        $esperandoRespuesta = ($remitenteTipo === 'jefe' && $tipoMensaje === 'info_request') ? 'empleado'
                            : (($remitenteTipo === 'empleado' && $tipoMensaje === 'info_response') ? 'jefe' : null);
        
        $stmtUpd = $pdo->prepare("
            UPDATE validaciones_jefe 
            SET ultimo_mensaje_id = ?,
                esperando_respuesta_de = ?,
                notificacion_leida_empleado = ?,
                notificacion_leida_jefe = ?
            WHERE id = ?
        ");
        $stmtUpd->execute([
            $msgId,
            $esperandoRespuesta,
            ($remitenteTipo === 'empleado' || $remitenteTipo === 'sistema') ? 1 : 0,
            ($remitenteTipo === 'jefe' || $remitenteTipo === 'sistema') ? 1 : 0,
            $validacionId
        ]);
        
        return $msgId;
    }
    
    /**
     * Obtener todos los mensajes de una validación
     * @param int $validacionId ID de la validación
     * @return array Lista de mensajes ordenados por fecha
     */
    public function obtenerMensajes($validacionId) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT vm.*, 
                       COALESCE(vm.remitente_nombre, 'Usuario') as nombre_remitente
                FROM validacion_mensajes vm
                WHERE vm.validacion_id = ?
                ORDER BY vm.created_at ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$validacionId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener validaciones para un empleado (panel del empleado)
     * @param int $empleadoId ID del empleado
     * @param array $filters Filtros (estado, fecha_inicio, fecha_fin)
     * @return array Lista de validaciones
     */
    public function getValidacionesEmpleado($empleadoId, $filters = []) {
        $pdo = $this->db->getConnection();
        
        $empleadoId = (int)$empleadoId;
        
        // 1. Obtener registros de validaciones_jefe (solo retroardos hasta ahora)
        $sql = "SELECT v.*, 
                    u.nombre_completo as jefe_nombre,
                    CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre_completo,
                    e.area as empleado_area,
                    e.jerarquia as empleado_jerarquia,
                    v.ultimo_mensaje_id,
                    v.esperando_respuesta_de,
                    v.notificacion_leida_empleado,
                    vm.mensaje as ultimo_mensaje_texto,
                    vm.remitente_tipo as ultimo_mensaje_remitente,
                    vm.remitente_nombre as ultimo_mensaje_remitente_nombre,
                    vm.created_at as ultimo_mensaje_fecha,
                    CASE 
                        WHEN v.tipo_incidencia = 'retardo' THEN 
                            (SELECT CONCAT('Retardo de ', r.minutos_retardo, ' minutos - ', COALESCE(r.motivo_justificacion, 'Sin motivo'))
                             FROM retardos r WHERE r.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'comision' THEN 
                            COALESCE(
                                (SELECT CONCAT('Comisión: ', c.descripcion) FROM comisiones c WHERE c.id = v.incidencia_id),
                                (SELECT CONCAT('Comisión: ', COALESCE(r.motivo_justificacion, 'Sin especificar')) FROM retardos r WHERE r.id = v.incidencia_id)
                            )
                        WHEN v.tipo_incidencia = 'dia_economico' THEN 
                            (SELECT CONCAT('Día económico: ', de.motivo) 
                             FROM dias_economicos de WHERE de.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'ausencia' THEN 
                            (SELECT CONCAT('Ausencia: ', a.motivo) 
                             FROM ausencias a WHERE a.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'constancia_tiempo' THEN 
                            (SELECT CONCAT('Constancia de tiempo: ', ct.motivo) 
                             FROM constancias_tiempo ct WHERE ct.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'licencia_medica' THEN 
                            (SELECT CONCAT('Licencia médica: ', COALESCE(lm.diagnostico, 'Sin diagnóstico')) 
                             FROM licencias_medicas lm WHERE lm.id = v.incidencia_id)
                        WHEN v.tipo_incidencia = 'justificacion' THEN 
                            (SELECT CONCAT('Justificación: ', j.motivo) 
                             FROM justificaciones j WHERE j.id = v.incidencia_id)
                        ELSE v.tipo_incidencia
                    END as descripcion_incidencia,
                    CASE v.tipo_incidencia
                        WHEN 'retardo' THEN (SELECT fecha FROM retardos WHERE id = v.incidencia_id)
                        WHEN 'comision' THEN COALESCE(
                            (SELECT fecha_inicio FROM comisiones WHERE id = v.incidencia_id),
                            (SELECT fecha FROM retardos WHERE id = v.incidencia_id)
                        )
                        WHEN 'dia_economico' THEN (SELECT fecha FROM dias_economicos WHERE id = v.incidencia_id)
                        WHEN 'ausencia' THEN (SELECT fecha_inicio FROM ausencias WHERE id = v.incidencia_id)
                        WHEN 'constancia_tiempo' THEN (SELECT fecha_inicio FROM constancias_tiempo WHERE id = v.incidencia_id)
                        WHEN 'licencia_medica' THEN (SELECT fecha_inicio FROM licencias_medicas WHERE id = v.incidencia_id)
                        WHEN 'justificacion' THEN (SELECT fecha_inicio FROM justificaciones WHERE id = v.incidencia_id)
                        ELSE v.fecha_solicitud
                    END as fecha_incidencia
                FROM validaciones_jefe v
                INNER JOIN empleados e ON v.empleado_id = e.id
                LEFT JOIN usuarios u ON v.jefe_id = u.empleado_id
                LEFT JOIN validacion_mensajes vm ON v.ultimo_mensaje_id = vm.id
                WHERE v.empleado_id = ?";
        
        $params = [$empleadoId];
        
        // Filtro por estado (solo para validaciones_jefe)
        if (!empty($filters['estado'])) {
            if (in_array($filters['estado'], ['pendientes', 'requiere_info'])) {
                $sql .= " AND v.estado IN ('pendiente', 'requiere_info')";
            } elseif ($filters['estado'] !== 'todos') {
                $sql .= " AND v.estado = ?";
                $params[] = $filters['estado'];
            }
        } else {
            $sql .= " AND v.estado IN ('pendiente', 'requiere_info', 'aprobado', 'rechazado')";
        }
        
        // Filtro por fecha (solo para validaciones_jefe)
        if (!empty($filters['fecha_inicio'])) {
            $sql .= " AND v.fecha_solicitud >= ?";
            $params[] = $filters['fecha_inicio'];
        }
        if (!empty($filters['fecha_fin'])) {
            $sql .= " AND v.fecha_solicitud <= ?";
            $params[] = $filters['fecha_fin'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $validaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Obtener incidencias de otras tablas que NO están en validaciones_jefe
        $incidenciasExternas = $this->getIncidenciasExternasEmpleado($pdo, $empleadoId, $filters);
        
        // 3. Combinar y ordenar
        $todas = array_merge($validaciones, $incidenciasExternas);
        
        usort($todas, function($a, $b) {
            $aPendiente = in_array($a['estado'] ?? '', ['pendiente', 'requiere_info']) ? 0 : 1;
            $bPendiente = in_array($b['estado'] ?? '', ['pendiente', 'requiere_info']) ? 0 : 1;
            if ($aPendiente != $bPendiente) return $aPendiente - $bPendiente;
            $fechaA = $a['fecha_solicitud'] ?? $a['fecha_incidencia'] ?? '';
            $fechaB = $b['fecha_solicitud'] ?? $b['fecha_incidencia'] ?? '';
            return strcmp($fechaB, $fechaA);
        });
        
        return $todas;
    }
    
    /**
     * Obtener incidencias aprobadas/rechazadas de tablas externas (sin registro en validaciones_jefe)
     */
    private function getIncidenciasExternasEmpleado($pdo, $empleadoId, $filters = []) {
        $empleadoId = (int)$empleadoId;
        $incidencias = [];
        
        // IDs ya existentes en validaciones_jefe para evitar duplicados
        $stmtIds = $pdo->prepare("SELECT CONCAT(tipo_incidencia, '_', incidencia_id) as vj_key, incidencia_id FROM validaciones_jefe WHERE empleado_id = ?");
        $stmtIds->execute([$empleadoId]);
        $rowsVj = $stmtIds->fetchAll(PDO::FETCH_ASSOC);
        $idsExistentes = [];
        $incidenciaIdsExistentes = [];
        foreach ($rowsVj as $rowVj) {
            $idsExistentes[] = $rowVj['vj_key'];
            $incidenciaIdsExistentes[] = $rowVj['incidencia_id'];
        }
        
        $estadoFilter = $filters['estado'] ?? '';
        
        // Helper para mapear estado
        $mapEstado = function($estatus) {
            $estatus = strtolower($estatus);
            if (in_array($estatus, ['aprobada', 'aprobado', 'autorizada'])) return 'aprobado';
            if (in_array($estatus, ['rechazada', 'rechazado', 'cancelada'])) return 'rechazado';
            if ($estatus === 'pendiente') return 'pendiente';
            return $estatus;
        };
        
        // Helper para filtrar por estado
        $pasaFiltro = function($estadoNorm) use ($estadoFilter) {
            if (empty($estadoFilter) || $estadoFilter === 'todos') return true;
            if ($estadoFilter === 'pendientes') return in_array($estadoNorm, ['pendiente', 'requiere_info']);
            return $estadoNorm === $estadoFilter;
        };
        
        // 2a. COMISIONES
        $stmt = $pdo->prepare("
            SELECT c.id, c.empleado_id, c.fecha_inicio, c.fecha_fin, c.tipo_comision,
                   c.estatus, c.descripcion, c.created_at, c.fecha_aprobacion,
                   u.nombre_completo as aprobado_por_nombre
            FROM comisiones c
            LEFT JOIN usuarios u ON c.aprobado_por = u.id
            WHERE c.empleado_id = ? AND c.estatus IS NOT NULL
        ");
        $stmt->execute([$empleadoId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = 'comision_' . $row['id'];
            if (in_array($key, $idsExistentes)) continue;
            if (in_array($row['id'], $incidenciaIdsExistentes)) continue;
            $estadoNorm = $mapEstado($row['estatus']);
            if (!$pasaFiltro($estadoNorm)) continue;
            $incidencias[] = [
                'id' => -(100000 + $row['id']),
                'incidencia_id' => $row['id'],
                'tipo_incidencia' => 'comision',
                'empleado_id' => $empleadoId,
                'jefe_id' => null,
                'estado' => $estadoNorm,
                'jefe_nombre' => $row['aprobado_por_nombre'] ?? 'N/A',
                'empleado_nombre_completo' => null,
                'empleado_area' => null,
                'empleado_jerarquia' => null,
                'descripcion_incidencia' => 'Comisión: ' . ($row['descripcion'] ?? 'Sin descripción'),
                'fecha_incidencia' => $row['fecha_inicio'],
                'fecha_solicitud' => $row['created_at'],
                'ultimo_mensaje_id' => null,
                'esperando_respuesta_de' => null,
                'notificacion_leida_empleado' => 1,
                'ultimo_mensaje_texto' => null,
                'ultimo_mensaje_remitente' => null,
                'ultimo_mensaje_remitente_nombre' => null,
                'ultimo_mensaje_fecha' => null,
            ];
        }
        
        // 2b. JUSTIFICACIONES
        $stmt = $pdo->prepare("
            SELECT j.id, j.empleado_id, j.fecha_inicio, j.tipo_justificacion,
                   j.estatus, j.motivo, j.created_at,
                   u.nombre_completo as aprobado_por_nombre
            FROM justificaciones j
            LEFT JOIN usuarios u ON j.aprobado_por = u.id
            WHERE j.empleado_id = ? AND j.estatus IS NOT NULL
        ");
        $stmt->execute([$empleadoId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = 'justificacion_' . $row['id'];
            if (in_array($key, $idsExistentes)) continue;
            if (in_array($row['id'], $incidenciaIdsExistentes)) continue;
            $estadoNorm = $mapEstado($row['estatus']);
            if (!$pasaFiltro($estadoNorm)) continue;
            $incidencias[] = [
                'id' => -(200000 + $row['id']),
                'incidencia_id' => $row['id'],
                'tipo_incidencia' => 'justificacion',
                'empleado_id' => $empleadoId,
                'jefe_id' => null,
                'estado' => $estadoNorm,
                'jefe_nombre' => $row['aprobado_por_nombre'] ?? 'N/A',
                'empleado_nombre_completo' => null,
                'empleado_area' => null,
                'empleado_jerarquia' => null,
                'descripcion_incidencia' => 'Justificación: ' . ($row['motivo'] ?? 'Sin motivo'),
                'fecha_incidencia' => $row['fecha_inicio'],
                'fecha_solicitud' => $row['created_at'],
                'ultimo_mensaje_id' => null,
                'esperando_respuesta_de' => null,
                'notificacion_leida_empleado' => 1,
                'ultimo_mensaje_texto' => null,
                'ultimo_mensaje_remitente' => null,
                'ultimo_mensaje_remitente_nombre' => null,
                'ultimo_mensaje_fecha' => null,
            ];
        }
        
        // 2d. VACACIONES
        $stmt = $pdo->prepare("
            SELECT v.id, v.empleado_id, v.fecha_inicio, v.fecha_fin, v.dias_solicitados,
                   v.estatus, v.created_at, v.fecha_aprobacion,
                   u.nombre_completo as aprobado_por_nombre
            FROM vacaciones v
            LEFT JOIN usuarios u ON v.aprobado_por = u.id
            WHERE v.empleado_id = ? AND v.estatus IS NOT NULL
        ");
        $stmt->execute([$empleadoId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = 'vacaciones_' . $row['id'];
            if (in_array($key, $idsExistentes)) continue;
            if (in_array($row['id'], $incidenciaIdsExistentes)) continue;
            $estadoNorm = $mapEstado($row['estatus']);
            if (!$pasaFiltro($estadoNorm)) continue;
            $incidencias[] = [
                'id' => -(400000 + $row['id']),
                'incidencia_id' => $row['id'],
                'tipo_incidencia' => 'vacaciones',
                'empleado_id' => $empleadoId,
                'jefe_id' => null,
                'estado' => $estadoNorm,
                'jefe_nombre' => $row['aprobado_por_nombre'] ?? 'N/A',
                'empleado_nombre_completo' => null,
                'empleado_area' => null,
                'empleado_jerarquia' => null,
                'descripcion_incidencia' => 'Vacaciones: ' . $row['fecha_inicio'] . ' al ' . ($row['fecha_fin'] ?? '') . ' (' . ($row['dias_solicitados'] ?? '?') . ' días)',
                'fecha_incidencia' => $row['fecha_inicio'],
                'fecha_solicitud' => $row['created_at'] ?? $row['fecha_aprobacion'],
                'ultimo_mensaje_id' => null,
                'esperando_respuesta_de' => null,
                'notificacion_leida_empleado' => 1,
                'ultimo_mensaje_texto' => null,
                'ultimo_mensaje_remitente' => null,
                'ultimo_mensaje_remitente_nombre' => null,
                'ultimo_mensaje_fecha' => null,
            ];
        }
        
        return $incidencias;
    }
    
    /**
     * Marcar mensajes como leídos
     * @param int $validacionId ID de la validación
     * @param string $tipo empleado|jefe
     */
    public function marcarComoLeido($validacionId, $tipo = 'empleado') {
        $pdo = $this->db->getConnection();
        
        $columna = ($tipo === 'jefe') ? 'notificacion_leida_jefe' : 'notificacion_leida_empleado';
        
        $sql = "UPDATE validaciones_jefe SET $columna = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$validacionId]);
        
        // Marcar mensajes no leídos como leídos
        $sqlMsg = "UPDATE validacion_mensajes 
                   SET leido = 1, leido_en = NOW() 
                   WHERE validacion_id = ? AND leido = 0";
        $stmtMsg = $pdo->prepare($sqlMsg);
        $stmtMsg->execute([$validacionId]);
    }
    
    /**
     * Obtener contador de notificaciones no leídas para un empleado
     * @param int $empleadoId ID del empleado
     * @return int Cantidad de notificaciones no leídas
     */
    public function getContadorNoLeidasEmpleado($empleadoId) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT COUNT(*) as total
                FROM validaciones_jefe v
                WHERE v.empleado_id = ?
                AND v.notificacion_leida_empleado = 0
                AND (v.estado = 'requiere_info' OR v.esperando_respuesta_de = 'empleado')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empleadoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Obtener contador de notificaciones no leídas para un jefe
     * @param int $jefeId ID del empleado (jefe)
     * @return int Cantidad de notificaciones no leídas
     */
    public function getContadorNoLeidasJefe($jefeId) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT COUNT(*) as total
                FROM validaciones_jefe v
                WHERE v.jefe_id = ?
                AND v.notificacion_leida_jefe = 0
                AND v.esperando_respuesta_de = 'jefe'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jefeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['total'] ?? 0);
    }

    /**
     * Registrar evidencia adjunta para un mensaje
     */
    public function guardarAdjunto($validacionId, $mensajeId, $archivoOriginal, $archivoPath, $tipoMime, $tamano) {
        $pdo = $this->db->getConnection();
        
        $sql = "INSERT INTO validacion_adjuntos (mensaje_id, validacion_id, archivo_original, archivo_path, tipo_mime, tamano, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$mensajeId, $validacionId, $archivoOriginal, $archivoPath, $tipoMime, $tamano]);
    }

    /**
     * Obtener adjuntos de un mensaje
     */
    public function getAdjuntosPorMensaje($mensajeId) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT * FROM validacion_adjuntos WHERE mensaje_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mensajeId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener adjuntos de una validación
     */
    public function getAdjuntosPorValidacion($validacionId) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT * FROM validacion_adjuntos WHERE validacion_id = ? ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$validacionId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cargar incidencias pendientes y ya validadas de empleados para el panel del empleado
     */
    public function getIncidenciasEmpleadoParaValidacion($empleadoId) {
        $pdo = $this->db->getConnection();
        
        // Retardos del empleado con su estado de validación
        $sql = "SELECT r.id, r.fecha, r.minutos_retardo, r.tipo_retraso, r.justificado,
                       r.motivo_justificacion, r.estado_validacion, r.requiere_validacion_jefe,
                       'retardo' as origen
                FROM retardos r
                WHERE r.empleado_id = ?
                ORDER BY r.fecha DESC
                LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empleadoId]);
        $retardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Asistencias con incidencias
        $sqlA = "SELECT a.id, a.fecha, a.tipo_asistencia, a.estado_validacion, a.requiere_validacion_jefe,
                        a.observaciones, 'asistencia' as origen
                 FROM asistencia a
                 WHERE a.empleado_id = ? AND a.tipo_asistencia NOT IN ('normal', 'por_definir')
                 ORDER BY a.fecha DESC
                 LIMIT 50";
        
        $stmtA = $pdo->prepare($sqlA);
        $stmtA->execute([$empleadoId]);
        $asistencias = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'retardos' => $retardos,
            'asistencias' => $asistencias
        ];
    }
}


?>
