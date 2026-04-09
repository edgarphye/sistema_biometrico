<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/EmpleadoHorarios.php';
require_once __DIR__ . '/HorarioLaboral.php';
require_once __DIR__ . '/Ausencia.php';

/**
 * Clase para insertar registros de asistencia desde archivos ZKTeco
 * Versión mejorada que maneja retardos y validaciones
 */
class ZKTecoAsistenciaInserter {
    private $db;
    private $configuracion = [];
    private $totalRetardosInsertados = 0; // 🔥 NUEVO: Contador de retardos insertados
    private $estadisticasInsercion = [];
    private $validEmployeeIds = null;
    private $empleadoHorarios;
    private $horarioLaboral;
    private $ausenciaModel;
    private $mapeoEmpleados = []; // Mapeo de zk_empleado_id => empleado_id
    private $detallesProcesados = []; // Detalles de registros procesados
    
    public function __construct($configuracion = [], $db = null) {
        $this->db = $db ?? new Database();
        $this->empleadoHorarios = new EmpleadoHorarios();
        $this->horarioLaboral = new HorarioLaboral();
        $this->ausenciaModel = new Ausencia();
        $this->configuracion = array_merge([
            'procesar_fines_semana' => false,
            'procesar_registros_fallidos' => true,
            'ajuste_horario_nocturno' => true,
            'calidad_minima' => null,
            'tipos_verificacion_permitidos' => null,
            'evitar_duplicados' => false,
            'verificar_existencia_rapida' => false,
            'crear_empleados_automaticos' => true,
            'proteger_registros_validados' => true // No modificar registros ya justificados y validados
        ], $configuracion);
        
        $this->inicializarEstadisticas();
    }
    
    /**
     * Establece el mapeo de empleados ZK a IDs del sistema
     * @param array $mapeo Array con clave zk_empleado_id y valor empleado_id
     */
    public function setMapeoEmpleados($mapeo) {
        $this->mapeoEmpleados = $mapeo;
    }
    
    /**
     * Convierte un ID de empleado ZK al ID del sistema
     * @param int $zkEmpleadoId ID del empleado en el dispositivo ZK
     * @return int|null ID del empleado en el sistema o null si no existe mapeo
     */
    private function obtenerEmpleadoId($zkEmpleadoId) {
        if (isset($this->mapeoEmpleados[$zkEmpleadoId])) {
            return $this->mapeoEmpleados[$zkEmpleadoId];
        }
        // Si no hay mapeo, usar el ID directo (compatibilidad hacia atrás)
        return $zkEmpleadoId;
    }
    
    private function inicializarEstadisticas() {
        $this->estadisticasInsercion = [
            'registros_leidos' => 0,
            'registros_procesados' => 0,
            'insertados' => 0,
            'actualizados' => 0,
            'omitidos_duplicados' => 0,
            'omitidos_protegidos' => 0,
            'errores' => 0,
            'errores_detallados' => [],
            'timeout' => false,
            'tiempo_inicio' => microtime(true)
        ];
    }
    
    /**
     * Procesa todos los registros del archivo
     */
    public function insertarRegistros($registros) {
        if (!is_array($registros) || empty($registros)) {
            return [
                'exito' => false,
                'error' => 'No se proporcionaron registros para procesar'
            ];
        }
        $this->detallesProcesados = [];
        
        try {
            // 🔥 DEDUPLICACIÓN: Eliminar registros duplicados (mismo empleado, fecha y hora)
            $registros_unicos = [];
            foreach ($registros as $reg) {
                $key = $reg['empleado_id'] . '_' . $reg['fecha'] . '_' . substr($reg['hora'], 0, 5);
                if (!isset($registros_unicos[$key])) {
                    $registros_unicos[$key] = $reg;
                }
            }
            $registros = array_values($registros_unicos);
            
            // Ordenar por empleado, fecha y hora
            usort($registros, function($a, $b) {
                return $a['empleado_id'] . $a['fecha'] . $a['hora'] <=> $b['empleado_id'] . $b['fecha'] . $b['hora'];
            });
            
            // 🔥 FIX: Inicializar contador de registros leídos
            $this->estadisticasInsercion['registros_leidos'] = count($registros);
            
            $batchSize = 100;
            $lotes = array_chunk($registros, $batchSize);
            
            foreach ($lotes as $numeroLote => $lote) {
                if ($this->verificarTiempoMaximo()) {
                    $this->estadisticasInsercion['timeout'] = true;
                    break;
                }
                
                $resultadoLote = $this->procesarLote($lote, $numeroLote + 1);
                $this->actualizarEstadisticasLote($resultadoLote);
                
                // Recolectar detalles de registros insertados/actualizados
                foreach ($resultadoLote['detalles'] as $detalle) {
                    if (($detalle['insertado'] ?? false) || ($detalle['actualizado'] ?? false)) {
                        $this->detallesProcesados[] = $detalle;
                    }
                }
            }
            
            return $this->generarResultadoFinal();
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error en inserción: ' . $e->getMessage(),
                'estadisticas' => $this->estadisticasInsercion
            ];
        }
    }
    
    /**
     * Procesa un lote de registros
     */
    private function procesarLote($lote, $numeroLote) {
        $resultadosLote = [
            'lote' => $numeroLote,
            'registros_procesados' => 0,
            'insertados' => 0,
            'actualizados' => 0,
            'omitidos_duplicados' => 0,
            'omitidos_protegidos' => 0,
            'errores' => 0,
            'errores_detallados' => [],
            'detalles' => []
        ];
        
        foreach ($lote as $registro) {
            try {
                $resultadoRegistro = $this->procesarRegistroIndividual($registro);
                $resultadosLote['detalles'][] = $resultadoRegistro;
                
                // Actualizar contadores
                $resultadosLote['registros_procesados']++;
                if ($resultadoRegistro['insertado']) {
                    $resultadosLote['insertados']++;
                } elseif ($resultadoRegistro['actualizado']) {
                    $resultadosLote['actualizados']++;
                } elseif ($resultadoRegistro['omitido_duplicado']) {
                    $resultadosLote['omitidos_duplicados']++;
                } else {
                    $resultadosLote['errores']++;
                    $resultadosLote['errores_detallados'][] = $resultadoRegistro['error'] ?? 'Error desconocido';
                }
                
            } catch (Exception $e) {
                $resultadosLote['errores']++;
                $resultadosLote['errores_detallados'][] = $e->getMessage();
                error_log("ERROR LOTE: " . $e->getMessage());
            }
        }
        
        return $resultadosLote;
    }
    
    /**
     * Procesa un registro individual
     */
    private function procesarRegistroIndividual($registro) {
        try {
            // Aplicar mapeo de empleado ZK a ID del sistema
            $zkEmpleadoId = $registro['empleado_id'] ?? null;
            $empleadoId = $this->obtenerEmpleadoId($zkEmpleadoId);
            
            // Guardar el ID original para referencia
            $registro['zk_empleado_id'] = $zkEmpleadoId;
            $registro['empleado_id'] = $empleadoId;
            
            // Validar que el empleado exista en la base de datos para evitar errores de FK
            if (!$this->validarExistenciaEmpleado($empleadoId)) {
                // Si no existe el empleado, intentar crearlo o mapearlo
                $empleadoCreado = false;
                
                // Solo crear empleados si está habilitado en la configuración
                if ($this->configuracion['crear_empleados_automaticos']) {
                    $empleadoCreado = $this->crearEmpleadoSiNoExiste($zkEmpleadoId, $empleadoId);
                }
                
                if (!$empleadoCreado) {
                    $this->estadisticasInsercion['errores']++;
                    $errorDetalle = [
                        'tipo' => 'empleado_no_encontrado',
                        'mensaje' => "El empleado con ID de dispositivo '{$zkEmpleadoId}' no existe en el sistema y no se pudo crear automáticamente.",
                        'zk_empleado_id' => $zkEmpleadoId,
                        'sugerencia' => 'Verifique el ID en el archivo .dat o agregue el empleado en la sección de "Mapeo de Empleados Biométricos".'
                    ];
                    return [
                        'exito' => false,
                        'insertado' => false,
                        'actualizado' => false,
                        'omitido_duplicado' => false,
                        'error' => $errorDetalle
                    ];
                }
                // Si se creó el empleado, continuar con la inserción
                $empleadoId = $empleadoCreado;
            // 🔥 IMPORTANTE: Actualizar el ID de empleado en el registro que se va a procesar
            $registro['empleado_id'] = $empleadoId;
            }

            // Verificar si el registro está protegido (ya justificado y validado)
            if ($this->configuracion['proteger_registros_validados']) {
                $registroProtegido = $this->verificarRegistroProtegido($registro['empleado_id'], $registro['fecha']);
                
                if ($registroProtegido) {
                    $this->estadisticasInsercion['omitidos_protegidos']++;
                    return [
                        'exito' => true,
                        'insertado' => false,
                        'actualizado' => false,
                        'omitido_duplicado' => true,
                        'motivo' => 'registro_ya_justificado_y_validado'
                    ];
                }
            }

            if ($this->configuracion['evitar_duplicados']) {
                $existente = $this->verificarRegistroExistentePorDia([
                    'empleado_id' => $empleadoId,
                    'fecha' => $registro['fecha']
                ]);
                
                if ($existente) {
                    $horario = $this->empleadoHorarios->getHorarioPorFecha($empleadoId, $registro['fecha']);
                    $esEntrada = $this->esRegistroEntrada($registro, $existente, $horario);
                    $hora = $registro['hora'];
                    
                    if ($esEntrada && $existente['hora_entrada'] === $hora) {
                        $this->estadisticasInsercion['omitidos_duplicados']++;
                        return [
                            'exito' => true,
                            'insertado' => false,
                            'actualizado' => false,
                            'omitido_duplicado' => true,
                            'motivo' => 'entrada_duplicada'
                        ];
                    }
                    if (!$esEntrada && $existente['hora_salida'] === $hora) {
                        $this->estadisticasInsercion['omitidos_duplicados']++;
                        return [
                            'exito' => true,
                            'insertado' => false,
                            'actualizado' => false,
                            'omitido_duplicado' => true,
                            'motivo' => 'salida_duplicada'
                        ];
                    }
                }
            }
            
            // Insertar nuevo registro (siempre que no sea duplicado exacto)
            $resultadoInsercion = $this->insertarRegistroCompleto($registro);
            
            if ($resultadoInsercion) {
                $datos = is_array($resultadoInsercion) ? $resultadoInsercion : ['id' => $resultadoInsercion];
                
                return array_merge([
                    'exito' => true,
                    'insertado' => ($datos['tipo'] ?? '') === 'insercion',
                    'actualizado' => ($datos['tipo'] ?? '') === 'actualizacion',
                    'omitido_duplicado' => false,
                    'registro_id' => $datos['id'] ?? 0,
                    'accion' => $datos['tipo'] ?? 'insertado'
                ], $datos);
            }
            
            return [
                'exito' => false,
                'insertado' => false,
                'actualizado' => false,
                'omitido_duplicado' => false,
                'error' => 'No se pudo insertar el registro'
            ];
            
            } catch (Exception $e) {
                $errorDetalle = [
                    'tipo' => 'excepcion_procesamiento',
                    'mensaje' => $e->getMessage(),
                    'zk_empleado_id' => $registro['zk_empleado_id'] ?? 'N/A',
                    'empleado_id' => $registro['empleado_id'] ?? 'N/A',
                    'fecha_hora' => ($registro['fecha'] ?? '') . ' ' . ($registro['hora'] ?? ''),
                    'paso' => 'procesarRegistroIndividual'
                ];
                
                error_log("ERROR DETALLADO: " . json_encode($errorDetalle));
                
                // Este error es grave y se añade directamente a las estadísticas principales
                // ya que puede detener el procesamiento del registro.
                $this->estadisticasInsercion['errores_detallados'][] = $errorDetalle;
                
                return [
                    'exito' => false,
                    'insertado' => false,
                    'actualizado' => false,
                    'omitido_duplicado' => false,
                    'error' => $errorDetalle,
                    'registro' => $registro
                ];
            }
    }
    
    /**
     * Inserta un nuevo registro en las tablas asistencia y retardos
     */
    public function insertarRegistroCompleto($registro) {
        try {
            // 1. Datos base del registro ZKTeco
            $datosBase = [
                'empleado_id' => $registro['empleado_id'],
                'fecha' => $registro['fecha'],
                'hora' => $registro['hora'],
                'zk_empleado_id' => $registro['empleado_id'],
                'accion_zkteco' => $registro['accion'] ?? 0,
                'verificacion' => $registro['verificacion'] ?? 0,
                'resultado' => $registro['resultado'] ?? 1,
                'dispositivo_id' => $registro['dispositivo_id'] ?? 0,
                'datetime_original' => $registro['datetime_original'] ?? $registro['datetime'] ?? null
            ];
            
            // 2. Verificar si ya existe registro para este día/empleado (antes de determinar entrada/salida)
            $existente = $this->verificarRegistroExistentePorDia($datosBase);
            
            // 3.1 Obtener horario del empleado para esta fecha
            $horario = $this->empleadoHorarios->getHorarioPorFecha($datosBase['empleado_id'], $datosBase['fecha']);

            // 3. Determinar si es entrada o salida (considerando si ya existe registro y el horario)
            $esEntrada = $this->esRegistroEntrada($registro, $existente, $horario);
            $datosBase['hora_entrada'] = $esEntrada ? $registro['hora'] : null;
            $datosBase['hora_salida'] = !$esEntrada ? $registro['hora'] : null;

            if ($existente) {
                $res = $this->actualizarRegistroExistente($datosBase, $existente);
                if ($res) {
                    return ['id' => $res, 'tipo' => 'actualizacion', 'empleado_id' => $datosBase['empleado_id'], 'fecha' => $datosBase['fecha']];
                }
                return false;
            }
            
            // 4. Insertar nuevo registro en asistencia
            $idAsistencia = $this->insertarEnAsistencia($datosBase);
            
            // 5. Determinar si es retardo e insertar en tabla retardos si corresponde
            $infoRetardo = $this->calcularRetardo($datosBase, $horario);
            if ($idAsistencia && $infoRetardo['es_retardo']) {
                $datosBase['minutos_retardo'] = $infoRetardo['minutos'];
                $datosBase['tipo_retardo'] = $infoRetardo['tipo'];
                
                // Si es falta (31+ minutos), NO insertar en retardos - queda como incidencia
                if ($infoRetardo['tipo'] === 'falta') {
                    // Marcar asistencia como por_definir para justificación
                    $conn = $this->db->getConnection();
                    $conn->prepare("UPDATE asistencia SET tipo_asistencia = 'por_definir', updated_at = NOW() WHERE id = ?")
                        ->execute([$idAsistencia]);
                } else {
                    // Solo insertar en retardos si es menor o mayor (no falta)
                    $this->insertarEnRetardos($datosBase, $idAsistencia);
                }
            }
            
            if ($idAsistencia) {
                return [
                    'id' => $idAsistencia,
                    'tipo' => 'insercion',
                    'es_retardo' => $infoRetardo['es_retardo'] ?? false,
                    'minutos_retardo' => $infoRetardo['minutos'] ?? 0,
                    'empleado_id' => $datosBase['empleado_id'],
                    'fecha' => $datosBase['fecha'],
                    'hora' => $datosBase['hora']
                ];
            }
            return false;
            
        } catch (Exception $e) {
            error_log("Error en insertarRegistroCompleto: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 🎯 DETERMINA SI EL REGISTRO CORRESPONDE A UNA ENTRADA O SALIDA (PÚBLICO PARA PRUEBAS)
     * LÓGICA MEJORADA que usa el horario del empleado.
     */
    public function esRegistroEntrada($registro, $existente = null, $horario = null) {
        $hora = $registro['hora'] ?? '00:00:00';

        // 0. Lógica prioritaria basada en rangos horarios definidos
        // Entrada: 07:00 a 12:00 | Salida: 14:00 a 18:00
        if ($hora >= '07:00:00' && $hora <= '12:00:00') {
            return true; // Es entrada
        }
        if ($hora >= '14:00:00' && $hora <= '18:00:00') {
            return false; // Es salida
        }
        // Si no cae en los rangos, se usan las lógicas de fallback

        // 1. Lógica de secuencia (máxima prioridad)
        if ($existente && is_array($existente)) {
            // Si ya tiene entrada pero no salida -> esta es salida
            if (!empty($existente['hora_entrada']) && empty($existente['hora_salida'])) {
                return false; // Es salida para completar el par
            }
            // Si ya tiene salida pero no entrada -> esta es entrada
            if (empty($existente['hora_entrada']) && !empty($existente['hora_salida'])) {
                return true; // Es entrada para completar el par
            }
            // Si ya tiene ambos, es un nuevo registro -> se tratará como una nueva entrada (ej. reingreso)
        }

        // 2. Lógica basada en el horario del empleado
        if ($horario && isset($horario['hora_entrada']) && isset($horario['hora_salida'])) {
            $hEntrada = strtotime($horario['hora_entrada']);
            $hSalida = strtotime($horario['hora_salida']);
            $hActual = strtotime($hora);

            if ($hEntrada && $hSalida && $hEntrada < $hSalida) {
                // Calcular el punto medio del turno
                $puntoMedio = $hEntrada + (($hSalida - $hEntrada) / 2);

                // Si la hora actual es anterior o igual al punto medio, es una entrada.
                if ($hActual <= $puntoMedio) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        // 3. Lógica de fallback (horarios fijos), si no hay horario asignado
        $partesHora = explode(':', $hora);
        $minutosDia = (int)$partesHora[0] * 60 + (int)($partesHora[1] ?? 0);
        $mediodia = 13 * 60; // 13:00 como punto de corte general

        if ($minutosDia < $mediodia) {
            return true; // Antes de la 1 PM, asumir entrada
        } else {
            return false; // Después de la 1 PM, asumir salida
        }
    }
    
    /**
     * Convierte hora HH:MM:SS a número para comparaciones
     */
    private function horaANumero($hora) {
        $partes = explode(':', $hora);
        return (int)($partes[0] . str_pad($partes[1] ?? '00', 2, '0', STR_PAD_LEFT));
    }
    
    /**
     * Determina si es entrada o salida por secuencia de registros del día
     */
    private function determinarPorSecuencia($registro) {
        $sql = "
            SELECT hora_entrada, hora_salida 
            FROM asistencia 
            WHERE empleado_id = ? AND fecha = ?
            ORDER BY id DESC 
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$registro['empleado_id'], $registro['fecha']]);
        $ultimoRegistro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ultimoRegistro) {
            // No hay registros -> es la primera del día -> entrada
            return true;
        }
        
        // Si ya tiene entrada pero no salida -> es salida
        if ($ultimoRegistro['hora_entrada'] && !$ultimoRegistro['hora_salida']) {
            return false;
        }
        
        // Si ya tiene entrada y salida -> nueva entrada (pudo salir a almorzar)
        return true;
    }
    
    /**
     * Determina si es la primera marca del día para el empleado
     */
    private function esPrimeraMarcaDelDia($registro) {
        $sql = "
            SELECT COUNT(*) as conteo
            FROM asistencia 
            WHERE empleado_id = ? AND fecha = ?
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$registro['empleado_id'], $registro['fecha']]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['conteo'] == 0;
    }
    
    /**
     * Verifica si ya existe un registro para este día/empleado
     */
    private function verificarRegistroExistentePorDia($datosBase) {
        $sql = "
            SELECT id, hora_entrada, hora_salida 
            FROM asistencia 
            WHERE empleado_id = ? AND fecha = ?
            ORDER BY id ASC
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$datosBase['empleado_id'], $datosBase['fecha']]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $registro;
    }
    
    /**
     * Actualiza un registro existente
     */
    private function actualizarRegistroExistente($datosBase, $existente) {
        if (!$existente) {
            return false;
        }
        
        // Actualizar campos según sea entrada o salida
        $actualizaciones = [];
        $valores = [];
        $entradaAgregada = false;
        
        // Actualizar entrada si no existe O si la nueva es anterior (primera entrada del día)
        if ($datosBase['hora_entrada'] && (!$existente['hora_entrada'] || $datosBase['hora_entrada'] < $existente['hora_entrada'])) {
            $actualizaciones[] = "hora_entrada = ?";
            $valores[] = $datosBase['hora_entrada'];
            $entradaAgregada = true;
        }
        
        if ($datosBase['hora_salida'] && (!$existente['hora_salida'] || $datosBase['hora_salida'] > $existente['hora_salida'])) {
                $actualizaciones[] = "hora_salida = ?";
                $valores[] = $datosBase['hora_salida'];
            }
        
        // Recalcular tipo_asistencia
        $nuevaEntrada = $datosBase['hora_entrada'] ?: $existente['hora_entrada'];
        $nuevaSalida = $datosBase['hora_salida'] ?: $existente['hora_salida'];
        
        $nuevoTipo = 'normal';
        if (!$nuevaEntrada && !$nuevaSalida) $nuevoTipo = 'por_definir';
        elseif (!$nuevaEntrada) $nuevoTipo = 'por_definir';
        elseif (!$nuevaSalida) $nuevoTipo = 'por_definir';
        
        $actualizaciones[] = "tipo_asistencia = ?";
        $valores[] = $nuevoTipo;
        
        if (!empty($actualizaciones)) {
            $sql = "
                UPDATE asistencia 
                SET " . implode(', ', $actualizaciones) . ",
                    updated_at = NOW()
                WHERE id = ?
            ";
            
            $valores[] = $existente['id'];
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($valores);
            
            // 🔥 NUEVO: Si se agregó una hora de entrada, calcular/recalcular el retardo.
            if ($entradaAgregada) {
                $horario = $this->empleadoHorarios->getHorarioPorFecha($datosBase['empleado_id'], $datosBase['fecha']);
                $infoRetardo = $this->calcularRetardo($datosBase, $horario);
                
                if ($infoRetardo['es_retardo']) {
                    $datosBase['minutos_retardo'] = $infoRetardo['minutos'];
                    $datosBase['tipo_retardo'] = $infoRetardo['tipo'];
                    // Se llama a insertarEnRetardos, que ahora debería manejar la actualización si ya existe.
                    $this->insertarEnRetardos($datosBase, $existente['id']);
                }
            }

            return $existente['id'];
        }
        
        return false; // No se actualizó nada
    }
    
    /**
     * Inserta en la tabla asistencia
     */
    private function insertarEnAsistencia($datosBase) {
        try {
            // Determinar tipo de asistencia
            $tipoAsistencia = 'normal';
            if (!empty($datosBase['hora_entrada']) && !empty($datosBase['hora_salida'])) {
                $tipoAsistencia = 'normal';
            } elseif (!empty($datosBase['hora_entrada']) && empty($datosBase['hora_salida'])) {
                $tipoAsistencia = 'por_definir';
            } elseif (empty($datosBase['hora_entrada']) && !empty($datosBase['hora_salida'])) {
                $tipoAsistencia = 'por_definir';
            } elseif (empty($datosBase['hora_entrada']) && empty($datosBase['hora_salida'])) {
                $tipoAsistencia = 'por_definir'; // Sin entrada ni salida
            }

            // Preparar metadata con información original del dispositivo
            $metadata = json_encode([
                'accion_zkteco' => $datosBase['accion_zkteco'] ?? null,
                'verificacion' => $datosBase['verificacion'] ?? null,
                'resultado' => $datosBase['resultado'] ?? null,
                'datetime_original' => $datosBase['datetime_original'] ?? null,
                'zk_empleado_id' => $datosBase['zk_empleado_id'] ?? null,
                'tipo_registro' => $tipoAsistencia
            ]);

            $sql = "
                INSERT INTO asistencia (
                    empleado_id, fecha, hora_entrada, hora_salida,
                    tipo_asistencia, dispositivo_id, tipo_biometria, calidad_verificacion, metadata_dispositivo,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
             
            $stmt = $this->db->getConnection()->prepare($sql);
             
            $valores = [
                $datosBase['empleado_id'],
                $datosBase['fecha'],
                $datosBase['hora_entrada'] ?? null,
                $datosBase['hora_salida'] ?? null,
                $tipoAsistencia,
                $datosBase['dispositivo_id'] ?? 1,
                'huella',
                $datosBase['verificacion'] ?? 0,
                $metadata
            ];
            
            $exito = $stmt->execute($valores);
            
            if ($exito) {
                $idAsistencia = $this->db->getConnection()->lastInsertId();
                
                // Verificar si es retardo y guardar en tabla retardos
                if ($this->esRetardo($datosBase, null)) {
                    $this->insertarEnRetardos($datosBase, $idAsistencia);
                }
                
                return $idAsistencia;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("ERROR INSERT ASISTENCIA: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Determina si el registro es un retardo y calcula los minutos
     * Solo aplica para entradas en la mañana (07:00 - 12:00)
     * 
     * @return array ['es_retardo' => bool, 'minutos' => int]
     */
    private function calcularRetardo($datosBase, $horario) {
        $resultado = ['es_retardo' => false, 'minutos' => 0, 'tipo' => 'sin_retardo'];

        if (empty($datosBase['hora_entrada'])) {
            return $resultado;
        }

        if (empty($horario) || !is_array($horario) || empty($horario['hora_entrada'])) {
            return $resultado;
        }

        $horaEntradaRegistrada = $datosBase['hora_entrada'];
        $horaEntradaProgramada = $horario['hora_entrada'];
        $tolerancia = $horario['tolerancia_minutos'] ?? 0;

        $horaProgramadaTimestamp = strtotime($horaEntradaProgramada);
        $horaLimiteTimestamp = $horaProgramadaTimestamp + ($tolerancia * 60);
        $horaEntradaRegistradaTimestamp = strtotime($horaEntradaRegistrada);

        if ($horaEntradaRegistradaTimestamp > $horaLimiteTimestamp) {
            // Calcular minutos de retraso desde la hora programada (sin restar tolerancia)
            // La tolerancia solo afecta si se clasifica o no como retardo, no el conteo de minutos
            $minutosRetraso = floor(($horaEntradaRegistradaTimestamp - $horaProgramadaTimestamp) / 60);

            if ($minutosRetraso <= 0) {
                return $resultado;
            }
            
            $clasificacion = $this->horarioLaboral->clasificarRetardo($minutosRetraso);

            return [
                'es_retardo' => $clasificacion['es_retardo'],
                'minutos' => $minutosRetraso,
                'tipo' => $clasificacion['tipo'],
                'hora_limite' => date('H:i:s', $horaLimiteTimestamp)
            ];
        }

        return $resultado;
    }
    
    /**
     * Determina si el registro es un retardo (compatibilidad)
     */
    private function esRetardo($datosBase, $horario) {
        return $this->calcularRetardo($datosBase, $horario)['es_retardo'];
    }
    
    /**
    /**
     * Inserta en la tabla retardos cuando corresponde
     */
    private function insertarEnRetardos($datosBase, $idAsistencia) {
        try {
            // Obtener el horario real del empleado
            $horarioEmpleado = $this->obtenerHorarioEmpleado($datosBase['empleado_id']);
            $horaEntradaProgramada = $horarioEmpleado['hora_entrada'] ?? '09:00:00';
            $tolerancia = $horarioEmpleado['tolerancia'] ?? 10;
            
            // Calcular minutos de retardo desde la hora oficial (sin tolerancia en el conteo)
            // Los minutos son los minutos completos desde la hora oficial
            $timestampOficial = strtotime($datosBase['fecha'] . ' ' . $horaEntradaProgramada);
            $timestampEntrada = strtotime($datosBase['fecha'] . ' ' . $datosBase['hora_entrada']);
            $timestampLimite = $timestampOficial + ($tolerancia * 60);
            
            // Calcular minutos de retardo (solo minutos completos, sin segundos)
            $minutosRetraso = 0;
            if ($timestampEntrada > $timestampOficial) {
                $minutosRetraso = floor(($timestampEntrada - $timestampOficial) / 60);
            }

            // Determinar tipo según minutos (la tolerancia solo afecta si se registra, no el conteo)
            // Retardo Menor: 11-20 min
            // Retardo Mayor: 21-30 min
            // Falta: 31+ min
            $tipoRetrasoDb = 'tolerancia'; // Default (0-10 min)
            if ($minutosRetraso >= 11 && $minutosRetraso <= 20) {
                $tipoRetrasoDb = 'retardo_menor';
            } elseif ($minutosRetraso >= 21 && $minutosRetraso <= 30) {
                $tipoRetrasoDb = 'retardo_mayor';
            } elseif ($minutosRetraso >= 31) {
                $tipoRetrasoDb = 'falta';
            }
            
            // Si son 0-10 minutos, no insertar (tolerancia)
            if ($minutosRetraso <= 10) {
                return false; // No es retardo, es tolerancia
            }
            
            // Determinar tipo de asistencia
            $tipoAsistencia = 'entrada';
            if (!empty($datosBase['hora_entrada']) && !empty($datosBase['hora_salida'])) {
                $tipoAsistencia = 'completa';
            } elseif (!empty($datosBase['hora_salida'])) {
                $tipoAsistencia = 'salida';
            }
            
            $sql = "
                INSERT INTO retardos (
                    empleado_id, fecha, 
                    hora_entrada, hora_salida,
                    minutos_retardo, tipo_retraso, 
                    tipo_asistencia,
                    justificado, motivo_detalle, requiere_validacion_jefe, 
                    estado_validacion, asistencia_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            
            $valores = [
                $datosBase['empleado_id'],
                $datosBase['fecha'],
                $datosBase['hora_entrada'] ?? null,
                $datosBase['hora_salida'] ?? null,
                $minutosRetraso,
                $tipoRetrasoDb,
                $tipoAsistencia,
                0,
                'Retardo detectado por sistema biométrico',
                1,
                'pendiente por el jefe',
                $idAsistencia
            ];
            
            $exito = $stmt->execute($valores);
            
            if ($exito) {
                $this->incrementarContadorRetardos();
            }
            
            return $exito;
            
        } catch (Exception $e) {
            error_log("ERROR INSERT RETARDOS: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el horario de un empleado
     */
    private function obtenerHorarioEmpleado($empleadoId) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT h.hora_entrada, h.tolerancia_minutos as tolerancia
            FROM horarios_empleados he
            JOIN horarios_laborales h ON he.horario_id = h.id
            WHERE he.empleado_id = ? AND he.activo = 1
            ORDER BY he.dia_semana ASC
            LIMIT 1
        ");
        $stmt->execute([$empleadoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'hora_entrada' => $result['hora_entrada'] ?? '09:00:00',
            'tolerancia' => $result['tolerancia'] ?? 10
        ];
    }
    
    /**
     * Inserta una ausencia automática por falta (retardo > 30 min)
     */
    private function insertarAusenciaAutomatica($datosBase) {
        try {
            // Verificar si ya existe ausencia para esa fecha para evitar duplicados
            $ausenciaExistente = $this->ausenciaModel->getAusenciaActiva($datosBase['empleado_id'], $datosBase['fecha']);
            if ($ausenciaExistente) return true;

            return $this->ausenciaModel->create([
                'empleado_id' => $datosBase['empleado_id'],
                'fecha_inicio' => $datosBase['fecha'],
                'fecha_fin' => $datosBase['fecha'],
                'tipo' => 'falta_injustificada'
            ]);
        } catch (Exception $e) {
            error_log("ERROR INSERT AUSENCIA AUTOMATICA: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Incrementa el contador de retardos insertados
     */
    private function incrementarContadorRetardos() {
        $this->totalRetardosInsertados++;
    }
    
    /**
     * Calcula la diferencia en minutos entre dos horas
     */
    private function calcularMinutosDiferencia($hora1, $hora2) {
        // hora1 = hora límite (09:15)
        // hora2 = hora de entrada (09:30)
        
        $h1 = explode(':', $hora1);
        $h2 = explode(':', $hora2);
        
        $minutos1 = (int)$h1[0] * 60 + (int)$h1[1];
        $minutos2 = (int)$h2[0] * 60 + (int)$h2[1];
        
        $diferencia = $minutos2 - $minutos1;
        
        return max(0, $diferencia);
    }
    
    /**
     * Registra fechas sin asistencia basándose en el rango de fechas del archivo .dat
     * Se ejecuta después de procesar un archivo DAT
     * @param array $fechasProcesadas Fechas que ya tienen registro del archivo
     * @param array $rangoFechas ['fecha_min' => 'YYYY-MM-DD', 'fecha_max' => 'YYYY-MM-DD']
     * @return array Estadísticas de registros sin asistencia
     */
    public function registrarFechasSinAsistencia($fechasProcesadas = [], $rangoFechas = null) {
        $resultado = [
            'fechas_detectadas' => 0,
            'registros_insertados' => 0,
            'empleados_sin_registro' => []
        ];
        
        try {
            // Usar rango de fechas del archivo si se proporciona, sino usar ciclo activo
            if ($rangoFechas && !empty($rangoFechas['fecha_min']) && !empty($rangoFechas['fecha_max'])) {
                $fechaInicio = $rangoFechas['fecha_min'];
                $fechaFin = $rangoFechas['fecha_max'];
            } else {
                // Obtener ciclo activo como fallback
                $stmt = $this->db->getConnection()->query("
                    SELECT id, fecha_inicio, fecha_fin 
                    FROM ciclos 
                    WHERE activo = 1 
                    ORDER BY fecha_inicio DESC 
                    LIMIT 1
                ");
                $ciclo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$ciclo) {
                    return $resultado; // No hay ciclo activo
                }
                
                $fechaInicio = $ciclo['fecha_inicio'];
                $fechaFin = $ciclo['fecha_fin'] ?? date('Y-m-d');
            }
            
            // Obtener todos los empleados activos
            $stmt = $this->db->getConnection()->query("SELECT id FROM empleados WHERE activo = 1");
            $empleados = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Generar todas las fechas del rango (solo días laborables: lunes-viernes)
            $fechasRango = [];
            $fechaActual = strtotime($fechaInicio);
            $fechaFinTime = strtotime($fechaFin);
            
            while ($fechaActual <= $fechaFinTime) {
                $fecha = date('Y-m-d', $fechaActual);
                $diaSemana = date('w', $fechaActual);
                
                // Solo días laborables (lunes-viernes: 1-5)
                if ($diaSemana >= 1 && $diaSemana <= 5) {
                    $fechasRango[] = $fecha;
                }
                $fechaActual = strtotime('+1 day', $fechaActual);
            }
            
            $resultado['fechas_detectadas'] = count($fechasRango);
            $resultado['rango_fechas'] = ['inicio' => $fechaInicio, 'fin' => $fechaFin];
            
            // Obtener fechas que ya tienen registro
            $fechasConRegistro = [];
            if (!empty($empleados)) {
                $inEmpleados = implode(',', array_fill(0, count($empleados), '?'));
                $stmt = $this->db->getConnection()->prepare("
                    SELECT DISTINCT DATE(fecha) as fecha 
                    FROM asistencia 
                    WHERE empleado_id IN ($inEmpleados)
                ");
                $stmt->execute($empleados);
                $fechasConRegistro = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            // Agregar fechas procesadas del archivo actual
            $fechasConRegistro = array_unique(array_merge($fechasConRegistro, $fechasProcesadas));
            
            // Obtener empleados únicos que tienen registros en el archivo
            $empleadosConRegistros = [];
            if (!empty($fechasProcesadas)) {
                $stmt = $this->db->getConnection()->query("
                    SELECT DISTINCT empleado_id 
                    FROM asistencia 
                    WHERE fecha BETWEEN '$fechaInicio' AND '$fechaFin'
                ");
                $empleadosConRegistros = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            // Para cada empleado que tiene registros, detectar fechas sin asistencia dentro del rango
            foreach ($empleadosConRegistros as $empleadoId) {
                // Obtener fechas con registro para este empleado
                $stmt = $this->db->getConnection()->prepare("
                    SELECT DISTINCT DATE(fecha) as fecha 
                    FROM asistencia 
                    WHERE empleado_id = ? AND fecha BETWEEN ? AND ?
                ");
                $stmt->execute([$empleadoId, $fechaInicio, $fechaFin]);
                $fechasEmpleado = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Fechas sin registro dentro del rango
                $fechasFaltantes = array_diff($fechasRango, $fechasEmpleado);
                
                if (!empty($fechasFaltantes)) {
                    $fechasValidas = [];
                    foreach ($fechasFaltantes as $fecha) {
                        // Verificar si el empleado tenía un horario asignado en esa fecha
                        // para evitar generar faltas antes de su fecha de inicio de ciclo/horario
                        $stmtVigencia = $this->db->getConnection()->prepare("
                            SELECT COUNT(*) 
                            FROM empleado_horarios 
                            WHERE empleado_id = ? 
                            AND ? >= fecha_inicio 
                            AND ? <= IFNULL(fecha_fin, '9999-12-31')
                        ");
                        $stmtVigencia->execute([$empleadoId, $fecha, $fecha]);
                        if ($stmtVigencia->fetchColumn() > 0) {
                            $fechasValidas[] = $fecha;
                        }
                    }

                    if (!empty($fechasValidas)) {
                        $resultado['empleados_sin_registro'][$empleadoId] = count($fechasValidas);
                        
                        // Insertar registros de "sin registro" para cada fecha válida
                        foreach ($fechasValidas as $fecha) {
                            $this->insertarRegistroSinAsistencia($empleadoId, $fecha);
                            $resultado['registros_insertados']++;
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("Error en registrarFechasSinAsistencia: " . $e->getMessage());
        }
        
        return $resultado;
    }
    
    /**
     * Inserta un registro de asistencia sin entrada ni salida (comisión de todo el día)
     * @param int $empleadoId ID del empleado
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @param bool $esComision Si true, marca como 'con_comision', si false como 'sin_registro'
     */
    private function insertarRegistroSinAsistencia($empleadoId, $fecha, $esComision = true) {
        try {
            // Verificar si es día festivo antes de marcar falta
            $stmtFestivo = $this->db->getConnection()->prepare("SELECT COUNT(*) FROM dias_festivos WHERE fecha = ?");
            $stmtFestivo->execute([$fecha]);
            if ($stmtFestivo->fetchColumn() > 0) {
                return false;
            }

            // Verificar si ya existe registro para esta fecha
            $stmt = $this->db->getConnection()->prepare("
                SELECT id FROM asistencia 
                WHERE empleado_id = ? AND fecha = ?
            ");
            $stmt->execute([$empleadoId, $fecha]);
            if ($stmt->fetch()) {
                return false; // Ya existe registro
            }
            
            $tipoAsistencia = 'por_definir';
            $mensaje = $esComision 
                ? 'Día sin registro biométrico - Comisión de todo el día' 
                : 'No se encontró registro de entrada/salida en el dispositivo';
            
            $metadata = json_encode([
                'tipo_registro' => 'sin_asistencia',
                'origen' => 'deteccion_automatica',
                'mensaje' => $mensaje,
                'empleado_id' => $empleadoId,
                'fecha' => $fecha
            ]);
            
            $sql = "
                INSERT INTO asistencia (
                    empleado_id, fecha, hora_entrada, hora_salida,
                    tipo_asistencia, dispositivo_id, tipo_biometria, calidad_verificacion, metadata_dispositivo,
                    created_at, updated_at
                ) VALUES (?, ?, NULL, NULL, ?, 0, NULL, 0, ?, NOW(), NOW())
            ";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute([$empleadoId, $fecha, $tipoAsistencia, $metadata]);
            
        } catch (Exception $e) {
            error_log("Error insertando registro sin asistencia: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si un ID de empleado existe en la base de datos
     */
    private function validarExistenciaEmpleado($empleadoId) {
        if ($this->validEmployeeIds === null) {
            try {
                $stmt = $this->db->getConnection()->query("SELECT id FROM empleados");
                $this->validEmployeeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) {
                $this->validEmployeeIds = [];
            }
        }
        return in_array($empleadoId, $this->validEmployeeIds);
    }

    /**
     * Verifica si un registro idéntico ya existe (misma hora)
     */
    private function verificarExistenciaRegistroExacto($registro) {
        $sql = "
            SELECT id 
            FROM asistencia 
            WHERE empleado_id = ? AND fecha = ? AND (
                (hora_entrada = ? AND hora_salida = ?) OR
                (hora_entrada IS NOT NULL AND hora_entrada = ?) OR
                (hora_salida IS NOT NULL AND hora_salida = ?)
            )
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $registro['empleado_id'], 
            $registro['fecha'],
            $registro['hora'], $registro['hora'],
            $registro['hora'], $registro['hora']
        ]);
        return $stmt->fetch();
    }
    
    /**
     * Verifica si un registro ya está justificado y validado (protegido)
     * No modifica registros que ya tienen justificación aprobada en retardos, ausencias, comisiones, etc.
     */
    private function verificarRegistroProtegido($empleadoId, $fecha) {
        $conn = $this->db->getConnection();
        
        // 1. Verificar en la tabla de retardos (cubre retardos e incidencias genéricas)
        $stmtRetardos = $conn->prepare("
            SELECT id FROM retardos 
            WHERE empleado_id = ? AND fecha = ? 
            AND justificado = 1 
            AND estado_validacion = 'aprobado'
            LIMIT 1
        ");
        $stmtRetardos->execute([$empleadoId, $fecha]);
        if ($stmtRetardos->fetch()) {
            return true; // Registro protegido por un retardo justificado y aprobado.
        }
        
        // 2. Verificar en la tabla de ausencias (licencias, vacaciones, etc.)
        // Se busca por un rango de fechas y un estado de aprobación.
        // La tabla 'ausencias' no tiene 'estatus', se debe verificar contra 'validaciones_jefe'.
        $stmtAusencias = $conn->prepare("
            SELECT a.id FROM ausencias a
            JOIN validaciones_jefe vj ON a.id = vj.incidencia_id AND vj.tipo_incidencia = 'ausencia'
            WHERE a.empleado_id = ? 
            AND ? BETWEEN a.fecha_inicio AND a.fecha_fin
            AND vj.estado = 'aprobado'
            LIMIT 1
        ");
        $stmtAusencias->execute([$empleadoId, $fecha]);
        if ($stmtAusencias->fetch()) {
            return true; // Registro protegido por una ausencia aprobada.
        }

        // 3. Verificar en la tabla de días económicos
        $stmtDiasEco = $conn->prepare("
            SELECT id FROM dias_economicos
            WHERE empleado_id = ? AND fecha = ?
            AND justificado = 1
            LIMIT 1
        ");
        $stmtDiasEco->execute([$empleadoId, $fecha]);
        if ($stmtDiasEco->fetch()) {
            return true; // Registro protegido por un día económico aprobado.
        }

        // 4. Verificar en la tabla de comisiones
        $stmtComisiones = $conn->prepare("
            SELECT id FROM comisiones
            WHERE empleado_id = ? AND ? BETWEEN fecha_inicio AND fecha_fin AND estatus = 'aprobada'
            LIMIT 1
        ");
        $stmtComisiones->execute([$empleadoId, $fecha]);
        if ($stmtComisiones->fetch()) {
            return true; // Registro protegido por una comisión aprobada.
        }
        
        return false;
    }
    
    /**
     * Crea un empleado automáticamente si no existe
     * Usa el ID ZK como ID del sistema y nombre genérico
     * @param int $zkEmpleadoId ID del empleado en el dispositivo ZK
     * @param int $empleadoId ID que se intentó usar
     * @return int|false ID del empleado creado o false si falló
     */
    private function crearEmpleadoSiNoExiste($zkEmpleadoId, $empleadoId) {
        try {
            // Primero verificar si hay un mapeo existente
            $stmt = $this->db->getConnection()->prepare("
                SELECT empleado_id FROM zkteo_empleado_mapeo WHERE zkteo_id = ?
            ");
            $stmt->execute([$zkEmpleadoId]);
            $mapeoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($mapeoExistente && $mapeoExistente['empleado_id']) {
                // Verificar si el empleado existe en la tabla empleados
                $stmt = $this->db->getConnection()->prepare("SELECT id FROM empleados WHERE id = ?");
                $stmt->execute([$mapeoExistente['empleado_id']]);
                if ($stmt->fetch()) {
                    // El empleado existe, actualizar el mapeo en memoria
                    $this->mapeoEmpleados[$zkEmpleadoId] = $mapeoExistente['empleado_id'];
                    return $mapeoExistente['empleado_id'];
                }
            }
            
            // Crear empleado automáticamente con datos básicos
            // No se especifica el ID para permitir que la base de datos lo genere (AUTO_INCREMENT)
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO empleados (nombre, apellido, activo, fecha_registro, numero_empleado)
                VALUES (?, ?, 1, NOW(), ?)
            ");
            
            $nombre = "Empleado ZK {$zkEmpleadoId}";
            // Usar el ID de ZK como número de empleado para referencia
            $stmt->execute([$nombre, 'Apellido Pendiente', $zkEmpleadoId]);
            
            // Obtener el ID real del empleado recién creado
            $nuevoEmpleadoId = $this->db->getConnection()->lastInsertId();

            if (!$nuevoEmpleadoId) {
                throw new Exception("No se pudo obtener el ID del nuevo empleado creado para ZK ID: {$zkEmpleadoId}");
            }
            
            // También crear el mapeo
            $stmt = $this->db->getConnection()->prepare("
                INSERT IGNORE INTO zkteo_empleado_mapeo (zkteo_id, empleado_id, nombre_empleado, metodo_creacion)
                VALUES (?, ?, ?, 'automatico')
            ");
            $stmt->execute([$zkEmpleadoId, $nuevoEmpleadoId, $nombre]);
            
            // Actualizar el mapeo en memoria
            $this->mapeoEmpleados[$zkEmpleadoId] = $nuevoEmpleadoId;
            
            // Actualizar la lista de empleados válidos
            $this->validEmployeeIds = null; // Forzar recarga
            
            error_log("INFO: Empleado creado automáticamente: ID {$nuevoEmpleadoId} para ZK ID {$zkEmpleadoId} ({$nombre})");
            
            return $nuevoEmpleadoId;
            
        } catch (Exception $e) {
            error_log("ERROR al crear empleado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si se ha alcanzado el tiempo máximo de procesamiento
     */
    private function verificarTiempoMaximo() {
        $tiempoActual = microtime(true);
        $tiempoMaximo = 300; // 5 minutos
        
        return ($tiempoActual - $this->estadisticasInsercion['tiempo_inicio']) > $tiempoMaximo;
    }
    
    /**
     * Actualiza estadísticas del lote
     */
    private function actualizarEstadisticasLote($resultadoLote) {
        $this->estadisticasInsercion['registros_procesados'] += $resultadoLote['registros_procesados'];
        $this->estadisticasInsercion['insertados'] += $resultadoLote['insertados'];
        $this->estadisticasInsercion['actualizados'] += $resultadoLote['actualizados'];
        $this->estadisticasInsercion['omitidos_duplicados'] += $resultadoLote['omitidos_duplicados'];
        $this->estadisticasInsercion['errores'] += $resultadoLote['errores'];
        if (!empty($resultadoLote['errores_detallados'])) {
            $this->estadisticasInsercion['errores_detallados'] = array_merge($this->estadisticasInsercion['errores_detallados'], $resultadoLote['errores_detallados']);
        }
    }
    
    /**
     * Genera el resultado final del procesamiento
     */
    private function generarResultadoFinal() {
        $tiempoTotal = microtime(true) - $this->estadisticasInsercion['tiempo_inicio'];
        
        $this->estadisticasInsercion['tiempo_total_procesamiento'] = $tiempoTotal;
        
        return [
            'exito' => !$this->estadisticasInsercion['timeout'] && $this->estadisticasInsercion['errores'] == 0,
            'error' => $this->estadisticasInsercion['timeout'] ? 'Timeout de procesamiento' : null,
            'estadisticas' => $this->estadisticasInsercion,
            'detalles' => $this->detallesProcesados
        ];
    }
}