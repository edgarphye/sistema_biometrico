<?php
// Sistema mejorado para manejar fechas de cualquier año y conteo de registros
class ZKTecoAsistenciaInserterMejorado {
    private $db;
    private $configuracion;
    private $estadisticas;
    
    private $configuracionDefecto = [
        'evitar_duplicados' => true,
        'batch_size' => 1000,
        'verificar_existencia_rapida' => true,
        'tiempo_max_procesamiento' => 1800,
        'manejar_multiples_anos' => true  // Nueva opción
    ];
    
    public function __construct($configuracion = []) {
        require_once 'models/Database.php';
        $this->db = new Database();
        $this->configuracion = array_merge($this->configuracionDefecto, $configuracion);
        $this->inicializarEstadisticas();
    }
    
    private function inicializarEstadisticas() {
        $this->estadisticas = [
            'total_registros' => 0,
            'registros_procesados' => 0,
            'registros_insertados' => 0,
            'registros_actualizados' => 0,
            'registros_omitidos' => 0,
            'errores' => 0,
            'tiempo_inicio' => microtime(true),
            'empleados_unicos' => 0,
            'dias_procesados' => 0,
            'estadisticas_por_ano' => [],  // Nuevo: estadísticas por año
            'ano_inicial' => null,
            'ano_final' => null,
            'total_anos' => 0
        ];
    }
    
    /**
     * Procesa registros con soporte para múltiples años
     */
    public function procesarRegistrosMultiAnio($registros) {
        if (empty($registros)) {
            return $this->generarResultadoVacio();
        }
        
        $this->estadisticas['total_registros'] = count($registros);
        
        try {
            // Detectar rangos de años en los datos
            $anosEncontrados = $this->detectarAnosEnDatos($registros);
            $this->estadisticas['ano_inicial'] = min($anosEncontrados);
            $this->estadisticas['ano_final'] = max($anosEncontrados);
            $this->estadisticas['total_anos'] = count($anosEncontrados);
            
            echo "📅 Años detectados: " . implode(', ', $anosEncontrados) . "\n";
            echo "📅 Rango: {$this->estadisticas['ano_inicial']} - {$this->estadisticas['ano_final']}\n";
            
            // Agrupar por empleado y fecha (independientemente del año)
            $registrosAgrupados = $this->agruparPorEmpleadoFecha($registros);
            $this->estadisticas['dias_procesados'] = count($registrosAgrupados);
            
            $resultados = [];
            
            // Procesar cada grupo empleado-fecha
            foreach ($registrosAgrupados as $clave => $grupo) {
                if ($this->verificarTiempoMaximo()) {
                    break;
                }
                
                $resultadoGrupo = $this->procesarGrupoEmpleadoFecha($grupo, $clave);
                $this->actualizarEstadisticasGrupo($resultadoGrupo);
                
                // Actualizar estadísticas por año
                $ano = substr($grupo['fecha'], 0, 4);
                if (!isset($this->estadisticas['estadisticas_por_ano'][$ano])) {
                    $this->estadisticas['estadisticas_por_ano'][$ano] = [
                        'total_dias' => 0,
                        'total_registros' => 0,
                        'total_insertados' => 0,
                        'total_actualizados' => 0
                    ];
                }
                
                $this->estadisticas['estadisticas_por_ano'][$ano]['total_dias']++;
                $this->estadisticas['estadisticas_por_ano'][$ano]['total_registros'] += 
                    count($grupo['entradas']) + count($grupo['salidas']) + count($grupo['otros']);
                
                if ($resultadoGrupo['exito']) {
                    foreach ($resultadoGrupo['acciones_realizadas'] as $accion) {
                        if ($accion['accion'] === 'insertado') {
                            $this->estadisticas['estadisticas_por_ano'][$ano]['total_insertados']++;
                        } elseif (strpos($accion['accion'], 'actualizada') !== false || strpos($accion['accion'], 'mejorada') !== false) {
                            $this->estadisticas['estadisticas_por_ano'][$ano]['total_actualizados']++;
                        }
                    }
                }
                
                $resultados[] = $resultadoGrupo;
            }
            
            return $this->generarResultadoFinal($resultados);
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error en procesamiento multi-año: ' . $e->getMessage(),
                'estadisticas' => $this->estadisticas
            ];
        }
    }
    
    /**
     * Detecta todos los años presentes en los datos
     */
    private function detectarAnosEnDatos($registros) {
        $anos = [];
        foreach ($registros as $registro) {
            $ano = substr($registro['fecha'], 0, 4);
            if (!in_array($ano, $anos)) {
                $anos[] = $ano;
            }
        }
        sort($anos);
        return $anos;
    }
    
    /**
     * Agrupa registros por empleado y fecha (compatible con múltiples años)
     */
    private function agruparPorEmpleadoFecha($registros) {
        $agrupados = [];
        
        foreach ($registros as $registro) {
            $clave = $registro['empleado_id'] . '|' . $registro['fecha'];
            if (!isset($agrupados[$clave])) {
                $agrupados[$clave] = [
                    'empleado_id' => $registro['empleado_id'],
                    'fecha' => $registro['fecha'],
                    'ano' => substr($registro['fecha'], 0, 4),
                    'entradas' => [],
                    'salidas' => [],
                    'otros' => [],
                    'zk_empleado_ids' => []  // Nuevo: guardar IDs ZKTeco originales
                ];
            }
            
            // Guardar ID ZKTeco original
            if (!in_array($registro['zk_empleado_id'], $agrupados[$clave]['zk_empleado_ids'])) {
                $agrupados[$clave]['zk_empleado_ids'][] = $registro['zk_empleado_id'];
            }
            
            if ($registro['accion'] == 0) {
                $agrupados[$clave]['entradas'][] = $registro;
            } elseif ($registro['accion'] == 1) {
                $agrupados[$clave]['salidas'][] = $registro;
            } else {
                $agrupados[$clave]['otros'][] = $registro;
            }
        }
        
        return $agrupados;
    }
    
    /**
     * Extraer fechas originales del grupo
     */
    private function extraerFechasOriginales($grupo) {
        $fechas = [];
        $todos = array_merge($grupo['entradas'], $grupo['salidas'], $grupo['otros']);
        foreach ($todos as $registro) {
            $fechas[] = $registro['fecha'];
        }
        return array_unique($fechas);
    }
    
    /**
     * Procesa un grupo específico de empleado-fecha
     */
    private function procesarGrupoEmpleadoFecha($grupo, $clave) {
        $resultado = [
            'clave' => $clave,
            'empleado_id' => $grupo['empleado_id'],
            'fecha' => $grupo['fecha'],
            'ano' => $grupo['ano'],
            'total_entradas' => count($grupo['entradas']),
            'total_salidas' => count($grupo['salidas']),
            'total_otros' => count($grupo['otros']),
            'acciones_realizadas' => []
        ];
        
        try {
            // Verificar si ya existe registro para este empleado-fecha (independientemente del año)
            $existente = $this->verificarRegistroExistente($grupo['empleado_id'], $grupo['fecha']);
            
            if (!$existente) {
                // No existe - crear nuevo registro
                $resultado['acciones_realizadas'][] = $this->crearNuevoRegistro($grupo);
                $resultado['tipo_accion'] = 'nuevo_registro';
            } else {
                // Existe - actualizar según sea necesario
                $actualizaciones = $this->actualizarRegistroExistente($existente, $grupo);
                $resultado['acciones_realizadas'] = $actualizaciones;
                $resultado['tipo_accion'] = 'actualizacion';
            }
            
            $resultado['exito'] = true;
            
        } catch (Exception $e) {
            $resultado['exito'] = false;
            $resultado['error'] = $e->getMessage();
            // Mostrar error en pantalla para depuración
            echo "❌ ERROR en grupo {$clave}: " . $e->getMessage() . "\n";
        }
        
        return $resultado;
    }
    
    /**
     * Crea un nuevo registro (compatible con cualquier año)
     */
    private function crearNuevoRegistro($grupo) {
        $empleadoId = $grupo['empleado_id'];
        $fecha = $grupo['fecha'];
        
        // Obtener primera entrada y última salida
        $primeraEntrada = null;
        $ultimaSalida = null;
        
        if (!empty($grupo['entradas'])) {
            $primeraEntrada = $this->obtenerPrimeraHora($grupo['entradas']);
        }
        
        if (!empty($grupo['salidas'])) {
            $ultimaSalida = $this->obtenerUltimaHora($grupo['salidas']);
        }
        
        // Insertar nuevo registro con ID ZKTeco original
        $sql = "
            INSERT INTO asistencia (
                empleado_id, fecha, hora_entrada, hora_salida,
                zk_empleado_id_original,
                datos_biometricos,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";
        
        // Preparar datos biométricos con ID ZKTeco
        $datosBiometricos = [
            'zk_empleado_id' => $grupo['zk_empleado_ids'] ?? [],
            'ano_registro' => $grupo['ano'],
            'total_registros_procesados' => count($grupo['entradas']) + count($grupo['salidas']),
            'fechas_originales' => $this->extraerFechasOriginales($grupo),
            'procesamiento' => [
                'primera_entrada' => $primeraEntrada,
                'ultima_salida' => $ultimaSalida,
                'timestamp_procesamiento' => date('Y-m-d H:i:s')
            ]
        ];
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $exito = $stmt->execute([
            $empleadoId, 
            $fecha, 
            $primeraEntrada, 
            $ultimaSalida,
            json_encode($grupo['zk_empleado_ids'] ?? []),
            json_encode($datosBiometricos)
        ]);
        
        if ($exito) {
            $registroId = $this->db->getConnection()->lastInsertId();
            
            return [
                'accion' => 'insertado',
                'registro_id' => $registroId,
                'ano' => $grupo['ano'],
                'detalles' => [
                    'empleado_id' => $empleadoId,
                    'fecha' => $fecha,
                    'hora_entrada' => $primeraEntrada,
                    'hora_salida' => $ultimaSalida,
                    'entradas_procesadas' => count($grupo['entradas']),
                    'salidas_procesadas' => count($grupo['salidas'])
                ]
            ];
        }
        
        throw new Exception('No se pudo insertar el nuevo registro');
    }
    
    /**
     * Actualiza un registro existente
     */
    private function actualizarRegistroExistente($existente, $grupo) {
        $acciones = [];
        $empleadoId = $grupo['empleado_id'];
        $fecha = $grupo['fecha'];
        
        // Actualizar entrada si es necesario
        if (!$existente['hora_entrada'] && !empty($grupo['entradas'])) {
            $primeraEntrada = $this->obtenerPrimeraHora($grupo['entradas']);
            
            $sql = "UPDATE asistencia SET hora_entrada = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            
            if ($stmt->execute([$primeraEntrada, $existente['id']])) {
                $acciones[] = [
                    'accion' => 'entrada_actualizada',
                    'registro_id' => $existente['id'],
                    'ano' => $grupo['ano'],
                    'nueva_hora_entrada' => $primeraEntrada
                ];
            }
        }
        
        // Actualizar salida si es necesario
        if (!$existente['hora_salida'] && !empty($grupo['salidas'])) {
            $ultimaSalida = $this->obtenerUltimaHora($grupo['salidas']);
            
            $sql = "UPDATE asistencia SET hora_salida = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            
            if ($stmt->execute([$ultimaSalida, $existente['id']])) {
                $acciones[] = [
                    'accion' => 'salida_actualizada',
                    'registro_id' => $existente['id'],
                    'ano' => $grupo['ano'],
                    'nueva_hora_salida' => $ultimaSalida
                ];
            }
        }
        
        return $acciones;
    }
    
    /**
     * Verifica si existe un registro para el empleado-fecha
     */
    private function verificarRegistroExistente($empleadoId, $fecha) {
        $sql = "SELECT id, hora_entrada, hora_salida FROM asistencia WHERE empleado_id = ? AND fecha = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$empleadoId, $fecha]);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene la primera hora de un array de registros
     */
    private function obtenerPrimeraHora($registros) {
        if (empty($registros)) return null;
        
        $primera = $registros[0]['hora'];
        foreach ($registros as $registro) {
            if ($registro['hora'] < $primera) {
                $primera = $registro['hora'];
            }
        }
        return $primera;
    }
    
    /**
     * Obtiene la última hora de un array de registros
     */
    private function obtenerUltimaHora($registros) {
        if (empty($registros)) return null;
        
        $ultima = $registros[0]['hora'];
        foreach ($registros as $registro) {
            if ($registro['hora'] > $ultima) {
                $ultima = $registro['hora'];
            }
        }
        return $ultima;
    }
    
    private function verificarTiempoMaximo() {
        $tiempoTranscurrido = microtime(true) - $this->estadisticas['tiempo_inicio'];
        return $tiempoTranscurrido > $this->configuracion['tiempo_max_procesamiento'];
    }
    
    private function actualizarEstadisticasGrupo($resultado) {
        if (!$resultado['exito']) {
            $this->estadisticas['errores']++;
            return;
        }
        
        foreach ($resultado['acciones_realizadas'] as $accion) {
            $this->estadisticas['registros_procesados']++;
            
            if ($accion['accion'] === 'insertado') {
                $this->estadisticas['registros_insertados']++;
            } elseif (strpos($accion['accion'], 'actualizada') !== false || strpos($accion['accion'], 'mejorada') !== false) {
                $this->estadisticas['registros_actualizados']++;
            }
        }
    }
    
    private function generarResultadoVacio() {
        return [
            'exito' => true,
            'mensaje' => 'No hay registros para procesar',
            'estadisticas' => $this->estadisticas
        ];
    }
    
    private function generarResultadoFinal($resultados) {
        $tiempoTotal = microtime(true) - $this->estadisticas['tiempo_inicio'];
        
        // Contar empleados únicos
        $empleadosUnicos = array_unique(array_map(function($r) { 
            return $r['empleado_id']; 
        }, $resultados));
        
        $this->estadisticas['empleados_unicos'] = count($empleadosUnicos);
        $this->estadisticas['tiempo_total'] = $tiempoTotal;
        
        return [
            'exito' => true,
            'mensaje' => 'Procesamiento multi-año completado',
            'estadisticas' => $this->estadisticas,
            'resumen' => [
                'total' => $this->estadisticas['total_registros'],
                'procesados' => $this->estadisticas['registros_procesados'],
                'insertados' => $this->estadisticas['registros_insertados'],
                'actualizados' => $this->estadisticas['registros_actualizados'],
                'omitidos' => $this->estadisticas['registros_omitidos'],
                'errores' => $this->estadisticas['errores'],
                'empleados_unicos' => $this->estadisticas['empleados_unicos'],
                'dias_procesados' => $this->estadisticas['dias_procesados'],
                'rango_anos' => [
                    'ano_inicial' => $this->estadisticas['ano_inicial'],
                    'ano_final' => $this->estadisticas['ano_final'],
                    'total_anos' => $this->estadisticas['total_anos']
                ],
                'estadisticas_por_ano' => $this->estadisticas['estadisticas_por_ano'],
                'tiempo' => number_format($tiempoTotal, 2) . ' segundos'
            ]
        ];
    }
}

echo "✅ Clase ZKTecoAsistenciaInserterMejorado cargada (soporte multi-año)\n";
?>