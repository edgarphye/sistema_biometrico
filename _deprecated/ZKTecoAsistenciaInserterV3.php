<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/ZKTecoMappingManager.php';

/**
 * Procesador de logs de ZKTeco con lógica mejorada para entrada/salida
 * Basado en estructura DAT: 2603	2025-01-21 15:00:07	1	0	1	0
 */
class ZKTecoAsistenciaInserterV3 {
    private $db;
    private $mappingManager;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mappingManager = new ZKTecoMappingManager();
    }
    
    /**
     * Inserta un registro completo desde archivo DAT
     * Formato DAT: usuario_id	fecha_hora	tipo	campo2	campo3	campo4
     * Ejemplo: 2603	2025-01-21 15:00:07	1	0	1	0
     */
    public function insertarRegistroCompleto($empleado_id, $fecha, $hora_registro, $dispositivo_id, 
                                            $zk_empleado_id, $accion_zkteco, $verificacion, $resultado) {
        try {
            // 1. Determinar si es entrada o salida según la hora
            $tipo_registro = $this->determinarTipoRegistroPorHora($hora_registro, $fecha);
            
            // 2. Obtener información del empleado
            $empleado = $this->obtenerDatosEmpleado($empleado_id);
            if (!$empleado) {
                throw new Exception("Empleado no encontrado: ID $empleado_id");
            }
            
            // 3. Determinar en qué campo insertar (hora_entrada u hora_salida)
            if ($tipo_registro['es_entrada']) {
                return $this->insertarComoEntrada($empleado, $fecha, $hora_registro, $dispositivo_id, 
                                                 $zk_empleado_id, $accion_zkteco, $verificacion, $resultado, $tipo_registro);
            } else {
                return $this->insertarComoSalida($empleado, $fecha, $hora_registro, $dispositivo_id, 
                                               $zk_empleado_id, $accion_zkteco, $verificacion, $resultado, $tipo_registro);
            }
            
        } catch (Exception $e) {
            throw new Exception("Error insertando registro completo: " . $e->getMessage());
        }
    }
    
    /**
     * Determina si el registro es entrada o salida basado en la hora del día
     */
    private function determinarTipoRegistroPorHora($hora, $fecha) {
        $hora_obj = DateTime::createFromFormat('H:i:s', $hora);
        $hora_num = (int)$hora_obj->format('H');
        $minutos = (int)$hora_obj->format('i');
        
        $dia_semana = date('N', strtotime($fecha));
        $es_finde = ($dia_semana == 6 || $dia_semana == 7);
        
        // Lógica principal de entrada/salida
        if ($hora_num >= 5 && $hora_num < 12) {
            // 5:00 AM - 11:59 AM: Es entrada
            return [
                'es_entrada' => true,
                'tipo' => 'entrada_mañana',
                'descripcion' => 'Entrada de la mañana',
                'horario' => 'matutino',
                'es_fin_semana' => $es_finde
            ];
        } elseif ($hora_num >= 12 && $hora_num < 14) {
            // 12:00 PM - 1:59 PM: Es salida de almuerzo
            return [
                'es_entrada' => false,
                'tipo' => 'salida_almuerzo',
                'descripcion' => 'Salida para almuerzo',
                'horario' => 'almuerzo',
                'es_fin_semana' => $es_finde
            ];
        } elseif ($hora_num >= 14 && $hora_num < 17) {
            // 2:00 PM - 4:59 PM: Es reingreso de almuerzo
            return [
                'es_entrada' => true,
                'tipo' => 'reingreso_almuerzo',
                'descripcion' => 'Reingreso después de almuerzo',
                'horario' => 'tarde',
                'es_fin_semana' => $es_finde
            ];
        } elseif ($hora_num >= 17 && $hora_num < 23) {
            // 5:00 PM - 10:59 PM: Es salida final
            return [
                'es_entrada' => false,
                'tipo' => 'salida_final',
                'descripcion' => 'Salida del día',
                'horario' => 'nocturno',
                'es_fin_semana' => $es_finde
            ];
        } else {
            // Casos especiales (11:00 PM - 4:59 AM)
            return [
                'es_entrada' => false,
                'tipo' => 'fuera_horario',
                'descripcion' => 'Fuera de horario laboral',
                'horario' => 'especial',
                'es_fin_semana' => $es_finde
            ];
        }
    }
    
    /**
     * Inserta el registro como entrada en el campo hora_entrada
     */
    private function insertarComoEntrada($empleado, $fecha, $hora_registro, $dispositivo_id, 
                                        $zk_empleado_id, $accion_zkteco, $verificacion, $resultado, $tipo_registro) {
        
        // Verificar si ya existe registro para esta fecha
        $sql_check = "SELECT id, hora_entrada, hora_salida FROM asistencia 
                     WHERE empleado_id = ? AND fecha = ? 
                     ORDER BY created_at DESC LIMIT 1";
        $stmt_check = $this->db->getConnection()->prepare($sql_check);
        $stmt_check->execute([$empleado['id'], $fecha]);
        $existente = $stmt_check->fetch();
        
        if ($existente) {
            // Si existe registro con hora_salida vacía, completar como entrada
            if ($existente['hora_entrada'] === null && $existente['hora_salida'] !== null) {
                return $this->completarRegistroExistente($empleado, $existente, $fecha, $hora_registro, 
                                                        $dispositivo_id, $zk_empleado_id, $accion_zkteco, 
                                                        $verificacion, $resultado, 'entrada');
            }
            
            // Si ya tiene entrada, verificar si necesita salida
            if ($existente['hora_entrada'] !== null && $existente['hora_salida'] === null) {
                return [
                    'success' => false,
                    'message' => "Empleado {$empleado['nombre_completo']} ya tiene entrada pero necesita salida en $fecha: {$existente['hora_entrada']}",
                    'tipo' => 'requiere_salida'
                ];
            }
            
            // Si ya tiene ambas horas, es un duplicado real
            if ($existente['hora_entrada'] !== null && $existente['hora_salida'] !== null) {
                return [
                    'success' => false,
                    'message' => "Empleado {$empleado['nombre_completo']} ya tiene registro completo en $fecha: {$existente['hora_entrada']} - {$existente['hora_salida']}",
                    'tipo' => 'duplicado_completo'
                ];
            }
        }
        
        // Calcular datos automáticos
        $datos_calculados = $this->calcularDatosAutomaticos($empleado['id'], $fecha, $hora_registro, true);
        
        // Preparar SQL para inserción como entrada
        $sql = "INSERT INTO asistencia (
            empleado_id, fecha, hora_entrada, dispositivo_id, 
            zk_empleado_id_original, tipo_asistencia, requerio_validacion,
            tipo_biometria, calidad_verificacion, metadata_dispositivo,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        
        $current_time = date('Y-m-d H:i:s');
        $stmt->execute([
            $empleado['id'],
            $fecha,
            $hora_registro,
            $dispositivo_id,
            $zk_empleado_id,
            $datos_calculados['tipo_asistencia'],
            $datos_calculados['requiere_validacion_jefe'],
            'huella',
            $verificacion,
            json_encode(['accion_zkteco' => $accion_zkteco, 'resultado' => $resultado]),
            $current_time
        ]);
        
        // Verificar si hay retardo y registrar en tabla retardos
        if ($datos_calculados['es_retardo']) {
            $this->registrarRetardo($empleado, $fecha, $hora_registro, $datos_calculados);
        }
        
        return [
            'success' => true,
            'message' => "Entrada registrada: {$empleado['nombre_completo']} - $fecha $hora_registro",
            'tipo' => 'entrada_registrada',
            'id_asistencia' => $this->db->getConnection()->lastInsertId(),
            'datos_calculados' => $datos_calculados
        ];
    }
    
    /**
     * Completa un registro existente que falta una de las horas
     */
    private function completarRegistroExistente($empleado, $registro_existente, $fecha, $hora_registro, 
                                               $dispositivo_id, $zk_empleado_id, $accion_zkteco, 
                                               $verificacion, $resultado, $tipo_completar) {
        
        try {
            if ($tipo_completar === 'entrada') {
                // Completar hora_entrada faltante
                $sql_update = "UPDATE asistencia SET 
                    hora_entrada = ?, dispositivo_id = ?, 
                    calidad_verificacion = ?, metadata_dispositivo = ?,
                    zk_empleado_id_original = ?
                WHERE id = ?";
                
                $stmt_update = $this->db->getConnection()->prepare($sql_update);
                $stmt_update->execute([
                    $hora_registro, $dispositivo_id, 
                    $verificacion, json_encode(['accion_zkteco' => $accion_zkteco, 'resultado' => $resultado]),
                    $zk_empleado_id, $registro_existente['id']
                ]);
                
                return [
                    'success' => true,
                    'message' => "Entrada completada: {$empleado['nombre_completo']} - $fecha $hora_registro (Ya tenía salida: {$registro_existente['hora_salida']})",
                    'tipo' => 'entrada_completada',
                    'id_asistencia' => $registro_existente['id']
                ];
                
            } else {
                // Completar hora_salida faltante
                $sql_update = "UPDATE asistencia SET 
                    hora_salida = ?, dispositivo_id = ?, 
                    calidad_verificacion = ?, metadata_dispositivo = ?
                WHERE id = ?";
                
                $stmt_update = $this->db->getConnection()->prepare($sql_update);
                $stmt_update->execute([
                    $hora_registro, $dispositivo_id, 
                    $verificacion, json_encode(['accion_zkteco' => $accion_zkteco, 'resultado' => $resultado]),
                    $registro_existente['id']
                ]);
                
                // Calcular minutos trabajados
                $minutos_trabajados = $this->calcularMinutosTrabajados($registro_existente['hora_entrada'], $hora_registro);
                
                return [
                    'success' => true,
                    'message' => "Salida completada: {$empleado['nombre_completo']} - $fecha $hora_registro (Minutos trabajados: $minutos_trabajados)",
                    'tipo' => 'salida_completada',
                    'id_asistencia' => $registro_existente['id'],
                    'minutos_trabajados' => $minutos_trabajados
                ];
            }
            
        } catch (Exception $e) {
            throw new Exception("Error completando registro existente: " . $e->getMessage());
        }
    }
    
    /**
     * Inserta el registro como salida en el campo hora_salida
     */
    private function insertarComoSalida($empleado, $fecha, $hora_registro, $dispositivo_id, 
                                      $zk_empleado_id, $accion_zkteco, $verificacion, $resultado, $tipo_registro) {
        
        // Buscar registro de entrada para completar con la salida
        $sql_entrada = "SELECT id, hora_entrada, hora_salida FROM asistencia 
                       WHERE empleado_id = ? AND fecha = ? 
                       ORDER BY created_at DESC LIMIT 1";
        $stmt_entrada = $this->db->getConnection()->prepare($sql_entrada);
        $stmt_entrada->execute([$empleado['id'], $fecha]);
        $registro_entrada = $stmt_entrada->fetch();
        
        if (!$registro_entrada) {
            // Si no hay entrada, crear un nuevo registro solo con salida
            $sql_insert = "INSERT INTO asistencia (
                empleado_id, fecha, hora_salida, dispositivo_id, 
                zk_empleado_id_original, tipo_asistencia, requerio_validacion,
                tipo_biometria, calidad_verificacion, metadata_dispositivo,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $datos_calculados = $this->calcularDatosAutomaticos($empleado['id'], $fecha, $hora_registro, false);
            $stmt_insert = $this->db->getConnection()->prepare($sql_insert);
            $current_time = date('Y-m-d H:i:s');
            $stmt_insert->execute([
                $empleado['id'], $fecha, $hora_registro, $dispositivo_id,
                $zk_empleado_id, $datos_calculados['tipo_asistencia'],
                $datos_calculados['requiere_validacion_jefe'],
                'huella', $verificacion,
                json_encode(['accion_zkteco' => $accion_zkteco, 'resultado' => $resultado]),
                $current_time
            ]);
            
            return [
                'success' => true,
                'message' => "Salida registrada (sin entrada previa): {$empleado['nombre_completo']} - $fecha $hora_registro",
                'tipo' => 'salida_sola',
                'id_asistencia' => $this->db->getConnection()->lastInsertId()
            ];
        }
        
        // Si existe registro pero solo con salida (sin entrada), completar como entrada
        if ($registro_entrada['hora_entrada'] === null && $registro_entrada['hora_salida'] !== null) {
            return $this->completarRegistroExistente($empleado, $registro_entrada, $fecha, $hora_registro, 
                                                    $dispositivo_id, $zk_empleado_id, $accion_zkteco, 
                                                    $verificacion, $resultado, 'entrada');
        }
        
        // Si ya tiene ambas horas, es un duplicado real
        if ($registro_entrada['hora_entrada'] !== null && $registro_entrada['hora_salida'] !== null) {
            return [
                'success' => false,
                'message' => "Empleado {$empleado['nombre_completo']} ya tiene registro completo en $fecha: {$registro_entrada['hora_entrada']} - {$registro_entrada['hora_salida']}",
                'tipo' => 'duplicado_completo'
            ];
        }
        
        // Calcular minutos trabajados y datos finales
        $minutos_trabajados = $this->calcularMinutosTrabajados($registro_entrada['hora_entrada'], $hora_registro);
        $horas_extras = $this->calcularHorasExtras($hora_registro, $empleado['id']);
        
        // Actualizar registro existente con la salida
        $sql_update = "UPDATE asistencia SET 
            hora_salida = ?, dispositivo_id = ?, 
            calidad_verificacion = ?, metadata_dispositivo = ?
        WHERE id = ?";
        
        $stmt_update = $this->db->getConnection()->prepare($sql_update);
        $stmt_update->execute([
            $hora_registro, $dispositivo_id, 
            $verificacion, json_encode(['accion_zkteco' => $accion_zkteco, 'resultado' => $resultado]),
            $registro_entrada['id']
        ]);
        
        return [
            'success' => true,
            'message' => "Salida registrada y día completado: {$empleado['nombre_completo']} - $fecha $hora_registro (Min trabajados: $minutos_trabajados)",
            'tipo' => 'salida_completada',
            'id_asistencia' => $registro_entrada['id'],
            'minutos_trabajados' => $minutos_trabajados,
            'horas_extras' => $horas_extras
        ];
    }
    
    /**
     * Calcula todos los datos automáticos para un registro
     */
    private function calcularDatosAutomaticos($empleado_id, $fecha, $hora_registro, $es_entrada) {
        // Obtener horario del empleado
        $horario = $this->obtenerHorarioEmpleado($empleado_id);
        
        // Determinar tipo de asistencia según la estructura de la tabla
        $tipo_asistencia = 'normal'; // Por defecto
        
        $hora_obj = DateTime::createFromFormat('H:i:s', $hora_registro);
        $hora_num = (int)$hora_obj->format('H');
        
        if ($es_entrada && $hora_num > 9) {
            $tipo_asistencia = 'con_retardo';
        } elseif (!$es_entrada && $hora_num < 17) {
            $tipo_asistencia = 'con_ausencia';
        }
        
        // Detectar si requiere validación
        $requiere_validacion_jefe = ($tipo_asistencia === 'con_retardo') ? 1 : 0;
        
        // Detectar retardo
        $retardo_info = $this->detectarRetardo($hora_registro, $es_entrada, $horario);
        
        return [
            'tipo_asistencia' => $tipo_asistencia,
            'requiere_validacion_jefe' => $requiere_validacion_jefe,
            'minutos_tardia' => $retardo_info['minutos_tardia'],
            'es_retardo' => $retardo_info['es_retardo']
        ];
    }
    
    /**
     * Obtiene datos del empleado
     */
    private function obtenerDatosEmpleado($empleado_id) {
        $sql = "SELECT id, nombre, apellido, CONCAT(nombre, ' ', apellido) as nombre_completo, 
                       rfc, area, puesto 
                FROM empleados WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$empleado_id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene el horario del empleado
     */
    private function obtenerHorarioEmpleado($empleado_id) {
        $sql = "SELECT h.id, h.hora_entrada, h.hora_salida, h.tolerancia_minutos as tolerancia 
                FROM horarios_laborales h 
                JOIN horarios_empleados he ON he.horario_id = h.id 
                JOIN empleados e ON e.id = he.empleado_id 
                WHERE e.id = ? AND h.activo = 1
                LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$empleado_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result;
        }
        
        // Horario por defecto
        return ['id' => 1, 'hora_entrada' => '09:00:00', 'hora_salida' => '17:00:00', 'tolerancia' => 15];
    }
    
    /**
     * Determina el tipo de asistencia según hora y contexto
     */
    private function determinarTipoAsistencia($hora, $es_entrada, $horario) {
        $hora_obj = DateTime::createFromFormat('H:i:s', $hora);
        $hora_num = (int)$hora_obj->format('H');
        
        if ($es_entrada) {
            if ($hora_num < 9) return 'entrada_temprana';
            if ($hora_num >= 9 && $hora_num <= 10) return 'entrada_normal';
            if ($hora_num > 10) return 'entrada_tardia';
        } else {
            if ($hora_num >= 17 && $hora_num <= 19) return 'salida_normal';
            if ($hora_num > 19) return 'salida_extra';
            if ($hora_num >= 12 && $hora_num < 14) return 'salida_almuerzo';
        }
        
        return 'otro';
    }
    
    /**
     * Determina la categoría principal
     */
    private function determinarCategoriaPrincipal($tipo_asistencia) {
        $categorias = [
            'entrada_temprana' => 'regular',
            'entrada_normal' => 'regular',
            'entrada_tardia' => 'retardo',
            'salida_normal' => 'regular',
            'salida_extra' => 'extra',
            'salida_almuerzo' => 'almuerzo',
            'otro' => 'otro'
        ];
        
        return $categorias[$tipo_asistencia] ?? 'otro';
    }
    
    /**
     * Determina si requiere validación del jefe
     */
    private function requiereValidacionJefe($tipo_asistencia, $hora, $horario) {
        $hora_obj = DateTime::createFromFormat('H:i:s', $hora);
        $horario_entrada = DateTime::createFromFormat('H:i:s', $horario['hora_entrada']);
        
        if ($tipo_asistencia === 'entrada_tardia') {
            // Si llega después de 15 minutos de tolerancia
            $diferencia = $hora_obj->getTimestamp() - $horario_entrada->getTimestamp();
            return $diferencia > ($horario['tolerancia'] + 15) * 60;
        }
        
        if ($tipo_asistencia === 'salida_extra') {
            // Salidas después de las 8 PM requieren validación
            return (int)$hora_obj->format('H') >= 20;
        }
        
        return false;
    }
    
    /**
     * Detecta si hay retardo y calcula minutos
     */
    private function detectarRetardo($hora_registro, $es_entrada, $horario) {
        $datos_base = [
            'es_retardo' => false,
            'minutos_tardia' => 0,
            'tipo_justificacion_id' => null,
            'motivo_justificacion' => null,
            'evidencia_adjunta' => null,
            'motivo_detalle' => null
        ];
        
        if (!$es_entrada) return $datos_base;
        
        $hora_registro_obj = DateTime::createFromFormat('H:i:s', $hora_registro);
        $horario_entrada_obj = DateTime::createFromFormat('H:i:s', $horario['hora_entrada']);
        
        $diferencia_segundos = $hora_registro_obj->getTimestamp() - $horario_entrada_obj->getTimestamp();
        $minutos_tardia = max(0, floor($diferencia_segundos / 60));
        
        $tolerancia_total = $horario['tolerancia'];
        if ($minutos_tardia <= $tolerancia_total) {
            return $datos_base;
        }
        
        $minutos_retardo_real = $minutos_tardia - $tolerancia_total;
        
        return [
            'es_retardo' => $minutos_retardo_real > 0,
            'minutos_tardia' => $minutos_tardia,
            'tipo_justificacion_id' => 1,
            'motivo_justificacion' => 'Retardo detectado automáticamente',
            'evidencia_adjunta' => null,
            'motivo_detalle' => "Llegada {$minutos_retardo_real} minutos después del horario permitido ({$minutos_tardia} totales)"
        ];
    }
    
    /**
     * Calcula minutos trabajados entre entrada y salida
     */
    private function calcularMinutosTrabajados($hora_entrada, $hora_salida) {
        $entrada = DateTime::createFromFormat('H:i:s', $hora_entrada);
        $salida = DateTime::createFromFormat('H:i:s', $hora_salida);
        
        $diferencia = $salida->getTimestamp() - $entrada->getTimestamp();
        return max(0, round($diferencia / 60));
    }
    
    /**
     * Calcula minutos trabajados desde la base de datos
     */
    private function calcularMinutosTrabajadosDesdeDB($empleado_id, $fecha, $hora_salida) {
        $sql = "SELECT hora_entrada FROM asistencia 
                WHERE empleado_id = ? AND fecha = ? AND hora_entrada IS NOT NULL";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$empleado_id, $fecha]);
        $registro = $stmt->fetch();
        
        if ($registro && $registro['hora_entrada']) {
            return $this->calcularMinutosTrabajados($registro['hora_entrada'], $hora_salida);
        }
        
        return 0;
    }
    
    /**
     * Calcula horas extras basadas en la hora de salida
     */
    private function calcularHorasExtras($hora_salida, $empleado_id) {
        $salida = DateTime::createFromFormat('H:i:s', $hora_salida);
        $hora_num = (int)$salida->format('H');
        
        // Después de las 8 PM se consideran horas extras
        if ($hora_num >= 20) {
            $minutos_extra = ($hora_num - 20) * 60 + (int)$salida->format('i');
            return max(0, $minutos_extra);
        }
        
        return 0;
    }
    
    /**
     * Registra retardo en la tabla retardos
     */
    private function registrarRetardo($empleado, $fecha, $hora_registro, $datos_calculados) {
        try {
            // Verificar si ya existe el retardo
            $sql_check = "SELECT id FROM retardos 
                         WHERE empleado_id = ? AND fecha = ? AND estado_validacion = 'pendiente'";
            $stmt_check = $this->db->getConnection()->prepare($sql_check);
            $stmt_check->execute([$empleado['id'], $fecha]);
            
            if ($stmt_check->fetch()) {
                return; // Ya existe, no duplicar
            }
            
            $sql_insert = "INSERT INTO retardos (
                empleado_id, fecha, hora_registro, minutos_retardo, tipo_retraso,
                estado_validacion, requiere_validacion_jefe, hora_entrada,
                tipo_justificacion_id, motivo_justificacion, evidencia_adjunta, motivo_detalle,
                created_at
            ) VALUES (?, ?, ?, ?, 'retardo_menor', 'pendiente', 1, ?, ?, ?, ?, ?, NOW())";
            
            $stmt_insert = $this->db->getConnection()->prepare($sql_insert);
            $stmt_insert->execute([
                $empleado['id'],
                $fecha,
                $hora_registro,
                $datos_calculados['minutos_tardia'],
                $hora_registro, // hora_entrada
                1, // tipo_justificacion_id
                $datos_calculados['motivo_justificacion'],
                $datos_calculados['evidencia_adjunta'],
                $datos_calculados['motivo_detalle']
            ]);
            
            echo "✓ Retardo registrado para {$empleado['nombre_completo']}: {$datos_calculados['minutos_tardia']} minutos\n";
            
        } catch (Exception $e) {
            echo "⚠️ Error registrando retardo: " . $e->getMessage() . "\n";
        }
    }
}