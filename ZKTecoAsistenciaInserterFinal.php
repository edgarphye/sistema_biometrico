<?php
/**
 * Procesador ZKTeco mejorado que inserta en ambas tablas: asistencia y retardos
 * Aplica reglas de hora específicas para determinar entrada/salida y retardos
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/ZKTecoMappingManager.php';

class ZKTecoAsistenciaInserterFinal {
    private $db;
    private $mappingManager;
    
    // Horarios de trabajo
    const ENTRADA_INICIO = '07:00:00';
    const ENTRADA_FIN = '09:30:00';
    const SALIDA_ALMUERZO_INICIO = '12:00:00';
    const SALIDA_ALMUERZO_FIN = '14:00:00';
    const SALIDA_FINAL_INICIO = '17:00:00';
    const SALIDA_FINAL_FIN = '19:00:00';
    const TOLERANCIA_RETARDO = 10; // minutos
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mappingManager = new ZKTecoMappingManager();
    }
    
    /**
     * Inserta un registro completo del archivo DAT en ambas tablas
     */
    public function insertarRegistroCompleto($registro) {
        try {
            // Obtener información del empleado
            $empleado = $this->obtenerEmpleadoPorZKId($registro['empleado_id']);
            if (!$empleado) {
                return ['success' => false, 'message' => "Empleado no encontrado: {$registro['empleado_id']}"];
            }
            
            // Determinar tipo de registro según hora
            $tipo_registro = $this->determinarTipoRegistro($registro['hora']);
            
            // Insertar en tabla asistencia
            $resultado_asistencia = $this->insertarEnAsistencia($empleado, $registro, $tipo_registro);
            
            // Insertar en tabla retardos si aplica
            $resultado_retardo = $this->insertarEnRetardos($empleado, $registro, $tipo_registro);
            
            return [
                'success' => true,
                'message' => "Registro procesado: {$empleado['nombre']} - {$tipo_registro['tipo']}",
                'asistencia' => $resultado_asistencia,
                'retardo' => $resultado_retardo
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Error: " . $e->getMessage()];
        }
    }
    
    /**
     * Determina el tipo de registro según la hora
     */
    private function determinarTipoRegistro($hora) {
        $hora_obj = DateTime::createFromFormat('H:i:s', $hora);
        $hora_num = (int)$hora_obj->format('H');
        $minuto_num = (int)$hora_obj->format('i');
        
        // Reglas específicas por hora
        if ($hora_num >= 5 && $hora_num < 12) {
            // 5:00-11:59: Entrada matutina
            $tipo = 'entrada_mañana';
            $campo_destino = 'hora_entrada';
            $es_entrada = true;
        } elseif ($hora_num >= 12 && $hora_num < 14) {
            // 12:00-13:59: Salida almuerzo
            $tipo = 'salida_almuerzo';
            $campo_destino = 'hora_salida';
            $es_entrada = false;
        } elseif ($hora_num >= 14 && $hora_num < 17) {
            // 14:00-16:59: Reingreso
            $tipo = 'reingreso_almuerzo';
            $campo_destino = 'hora_entrada';
            $es_entrada = true;
        } elseif ($hora_num >= 17 && $hora_num < 23) {
            // 17:00-22:59: Salida final
            $tipo = 'salida_final';
            $campo_destino = 'hora_salida';
            $es_entrada = false;
        } else {
            // 23:00-4:59: Fuera de horario normal
            $tipo = 'fuera_horario';
            $campo_destino = 'hora_salida';
            $es_entrada = false;
        }
        
        // Detectar retardo
        $es_retardo = $this->detectarRetardo($hora, $es_entrada);
        
        return [
            'tipo' => $tipo,
            'campo_destino' => $campo_destino,
            'es_entrada' => $es_entrada,
            'es_retardo' => $es_retardo,
            'minutos_retardo' => $this->calcularMinutosRetardo($hora)
        ];
    }
    
    /**
     * Inserta o actualiza registro en tabla asistencia
     */
    private function insertarEnAsistencia($empleado, $registro, $tipo_registro) {
        // Verificar si existe registro para el día
        $sql_check = "SELECT id, hora_entrada, hora_salida, tipo_asistencia FROM asistencia 
                     WHERE empleado_id = ? AND fecha = ?";
        $stmt_check = $this->db->getConnection()->prepare($sql_check);
        $stmt_check->execute([$empleado['id'], $registro['fecha']]);
        $existente = $stmt_check->fetch();
        
        if ($existente) {
            // Verificar si es una comisión registrada - NO sobrescribir
            $tiposComision = ['comision', 'comision_todo_dia', 'comision_entrada', 'comision_salida'];
            if (in_array($existente['tipo_asistencia'], $tiposComision)) {
                return [
                    'success' => true,
                    'message' => 'Registro de comisión existente, no se sobrescribe',
                    'action' => 'skipped_commission'
                ];
            }
            
            // Actualizar registro existente
            return $this->actualizarAsistencia($existente, $registro, $tipo_registro, $empleado);
        } else {
            // Insertar nuevo registro
            return $this->insertarNuevaAsistencia($empleado, $registro, $tipo_registro);
        }
    }
    
    /**
     * Inserta nuevo registro en asistencia
     */
    private function insertarNuevaAsistencia($empleado, $registro, $tipo_registro) {
        $sql = "INSERT INTO asistencia (
            empleado_id, zk_empleado_id_original, fecha, {$tipo_registro['campo_destino']},
            dispositivo_id, tipo_asistencia, requerio_validacion,
            tipo_biometria, calidad_verificacion, metadata_dispositivo,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        
        $tipo_asistencia = $this->determinarTipoAsistencia($tipo_registro);
        $requiere_validacion = $tipo_registro['es_retardo'] ? 1 : 0;
        
        $stmt->execute([
            $empleado['id'],
            $registro['empleado_id'],
            $registro['fecha'],
            $registro['hora'],
            $registro['dispositivo_id'] ?? 1,
            $tipo_asistencia,
            $requiere_validacion,
            'huella',
            $registro['verificacion'] ?? 1,
            json_encode([
                'zk_tipo' => $registro['tipo'] ?? 1,
                'zk_resultado' => $registro['resultado'] ?? 0,
                'procesado_en' => date('Y-m-d H:i:s')
            ])
        ]);
        
        return [
            'accion' => 'insertado',
            'id' => $this->db->getConnection()->lastInsertId(),
            'tipo_registro' => $tipo_registro['tipo']
        ];
    }
    
    /**
     * Actualiza registro existente en asistencia
     */
    private function actualizarAsistencia($existente, $registro, $tipo_registro, $empleado) {
        $campo = $tipo_registro['campo_destino'];
        
        // Verificar que no tenga ya ese campo
        if ($existente[$campo] !== null) {
            return [
                'accion' => 'omitido_duplicado',
                'motivo' => "Ya existe {$campo}: {$existente[$campo]}",
                'id' => $existente['id'],
                'tipo_registro' => $tipo_registro['tipo']
            ];
        }
        
        $sql = "UPDATE asistencia SET 
                    {$campo} = ?, dispositivo_id = ?, calidad_verificacion = ?, 
                    metadata_dispositivo = ?, updated_at = NOW()
                 WHERE id = ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $registro['hora'],
            $registro['dispositivo_id'] ?? 1,
            $registro['verificacion'] ?? 1,
            json_encode([
                'zk_tipo' => $registro['tipo'] ?? 1,
                'zk_resultado' => $registro['resultado'] ?? 0,
                'actualizado_en' => date('Y-m-d H:i:s')
            ]),
            $existente['id']
        ]);
        
        // Si ahora tiene ambas horas, calcular tiempo trabajado
        if ($existente['hora_entrada'] !== null && $existente['hora_salida'] !== null) {
            $this->calcularTiempoTrabajado($existente['id']);
        }
        
            return [
                'accion' => 'actualizado',
                'id' => $existente['id'],
                'campo_actualizado' => $campo,
                'valor' => $registro['hora'],
                'tipo_registro' => $tipo_registro['tipo']
            ];
    }
    
    /**
     * Inserta en tabla retardos si aplica
     */
    private function insertarEnRetardos($empleado, $registro, $tipo_registro) {
        // Solo insertar retardos para entradas que llegaron tarde
        if (!$tipo_registro['es_entrada'] || !$tipo_registro['es_retardo']) {
            return ['accion' => 'no_aplica'];
        }
        
        // Verificar si ya existe retardo para ese día
        $sql_check = "SELECT id FROM retardos 
                     WHERE empleado_id = ? AND fecha = ?";
        $stmt_check = $this->db->getConnection()->prepare($sql_check);
        $stmt_check->execute([$empleado['id'], $registro['fecha']]);
        
        if ($stmt_check->fetch()) {
            return ['accion' => 'omitido_ya_existe'];
        }
        
        $sql = "INSERT INTO retardos (
            empleado_id, fecha, hora_entrada, hora_registro, fecha_asistencia,
            dia_semana, tipo_registro, unique_dia_empleado, minutos_retardo,
            tipo_retraso, categoria_principal, requiere_validacion_jefe,
            estado_validacion, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        
        $dia_semana = $this->getDiaSemana($registro['fecha']);
        $unique_dia_empleado = $empleado['id'] . '_' . str_replace('-', '', $registro['fecha']);
        $tipo_retraso = $this->determinarTipoRetraso($tipo_registro['minutos_retardo']);
        
        $stmt->execute([
            $empleado['id'],
            $registro['fecha'],
            $registro['hora'],
            $registro['hora'],
            $registro['fecha'],
            $dia_semana,
            'entrada',
            $unique_dia_empleado,
            $tipo_registro['minutos_retardo'],
            $tipo_retraso,
            'retardo',
            1, // requiere_validacion_jefe
            'pendiente'
        ]);
        
        return [
            'accion' => 'insertado',
            'id' => $this->db->getConnection()->lastInsertId(),
            'minutos_retardo' => $tipo_registro['minutos_retardo'],
            'tipo_retraso' => $tipo_retraso
        ];
    }
    
    /**
     * Detecta si hay retardo según hora y tipo
     */
    private function detectarRetardo($hora, $es_entrada) {
        if (!$es_entrada) {
            return false;
        }
        
        $hora_obj = DateTime::createFromFormat('H:i:s', $hora);
        $hora_referencia = DateTime::createFromFormat('H:i:s', self::ENTRADA_FIN);
        
        return $hora_obj > $hora_referencia;
    }
    
    /**
     * Calcula minutos de retardo
     */
    private function calcularMinutosRetardo($hora) {
        $hora_obj = DateTime::createFromFormat('H:i:s', $hora);
        $hora_limite = DateTime::createFromFormat('H:i:s', self::ENTRADA_FIN);
        $hora_limite->add(new DateInterval('PT' . self::TOLERANCIA_RETARDO . 'M'));
        
        if ($hora_obj <= $hora_limite) {
            return 0;
        }
        
        $diferencia = $hora_obj->getTimestamp() - $hora_limite->getTimestamp();
        return (int)($diferencia / 60);
    }
    
    /**
     * Determina tipo de asistencia según registro
     */
    private function determinarTipoAsistencia($tipo_registro) {
        if ($tipo_registro['es_retardo']) {
            return 'con_retardo';
        }
        return 'normal';
    }
    
    /**
     * Determina tipo de retraso según minutos
     * - Tolerancia: 0-10 minutos (no se registra)
     * - Retardo menor: 11-20 minutos
     * - Retardo mayor: 21-30 minutos
     * - Falta: 31+ minutos
     */
    private function determinarTipoRetraso($minutos) {
        if ($minutos >= 0 && $minutos <= 10) {
            return 'tolerancia'; // No se registra como retardo
        } elseif ($minutos >= 11 && $minutos <= 20) {
            return 'retardo_menor';
        } elseif ($minutos >= 21 && $minutos <= 30) {
            return 'retardo_mayor';
        } elseif ($minutos >= 31) {
            return 'falta';
        }
        return 'normal';
    }
    
    /**
     * Obtiene día de la semana en español
     */
    private function getDiaSemana($fecha) {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $dia_num = date('w', strtotime($fecha));
        return $dias[$dia_num];
    }
    
    /**
     * Calcula tiempo trabajado para registro completo
     */
    private function calcularTiempoTrabajado($asistencia_id) {
        $sql = "SELECT hora_entrada, hora_salida FROM asistencia WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$asistencia_id]);
        $registro = $stmt->fetch();
        
        if ($registro && $registro['hora_entrada'] && $registro['hora_salida']) {
            $entrada = DateTime::createFromFormat('H:i:s', $registro['hora_entrada']);
            $salida = DateTime::createFromFormat('H:i:s', $registro['hora_salida']);
            $minutos = ($salida->getTimestamp() - $entrada->getTimestamp()) / 60;
            
            // Aquí podríamos actualizar un campo de minutos_trabajados si existiera
        }
    }
    
    /**
     * Obtiene información del empleado por ID ZKTeco
     */
    private function obtenerEmpleadoPorZKId($zk_id) {
        $sql = "SELECT e.id, e.nombre, e.apellido, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo
                FROM empleados e
                JOIN zkteo_empleado_mapeo zm ON zm.empleado_id = e.id
                WHERE zm.zkteo_id = ? AND zm.activo = TRUE
                LIMIT 1";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$zk_id]);
        
        return $stmt->fetch();
    }
}