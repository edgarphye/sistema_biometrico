<?php
// Sistema optimizado para procesamiento masivo de DAT
class ZKTecoAsistenciaInserterOptimizado {
    private $db;
    private $configuracion;
    private $estadisticas;
    
    private $configuracionDefecto = [
        'evitar_duplicados' => true,
        'batch_size' => 500,  // Aumentado para procesamiento masivo
        'verificar_existencia_rapida' => true,
        'tiempo_max_procesamiento' => 600  // 10 minutos
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
            'dias_procesados' => 0
        ];
    }
    
    /**
     * Procesa lote de registros con lógica optimizada de entrada/salida
     */
    public function procesarRegistrosMasivos($registros) {
        if (empty($registros)) {
            return $this->generarResultadoVacio();
        }
        
        $this->estadisticas['total_registros'] = count($registros);
        
        try {
            // Agrupar registros por empleado y fecha para optimización
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
                $resultados[] = $resultadoGrupo;
            }
            
            return $this->generarResultadoFinal($resultados);
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error en procesamiento masivo: ' . $e->getMessage(),
                'estadisticas' => $this->estadisticas
            ];
        }
    }
    
    /**
     * Agrupa registros por empleado y fecha para procesamiento optimizado
     */
    private function agruparPorEmpleadoFecha($registros) {
        $agrupados = [];
        
        foreach ($registros as $registro) {
            $clave = $registro['empleado_id'] . '|' . $registro['fecha'];
            if (!isset($agrupados[$clave])) {
                $agrupados[$clave] = [
                    'empleado_id' => $registro['empleado_id'],
                    'fecha' => $registro['fecha'],
                    'entradas' => [],
                    'salidas' => [],
                    'otros' => []
                ];
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
     * Procesa un grupo específico de empleado-fecha
     */
    private function procesarGrupoEmpleadoFecha($grupo, $clave) {
        $resultado = [
            'clave' => $clave,
            'empleado_id' => $grupo['empleado_id'],
            'fecha' => $grupo['fecha'],
            'total_entradas' => count($grupo['entradas']),
            'total_salidas' => count($grupo['salidas']),
            'total_otros' => count($grupo['otros']),
            'acciones_realizadas' => []
        ];
        
        try {
            // Verificar si ya existe registro para este empleado-fecha
            $existente = $this->verificarRegistroExistente($grupo['empleado_id'], $grupo['fecha']);
            
            if (!$existente) {
                // No existe - crear nuevo registro con primera entrada y última salida
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
        }
        
        return $resultado;
    }
    
    /**
     * Crea un nuevo registro para un empleado-fecha
     */
    private function crearNuevoRegistro($grupo) {
        $empleadoId = $grupo['empleado_id'];
        $fecha = $grupo['fecha'];
        
        // Obtener primera entrada y última salida (si existen)
        $primeraEntrada = null;
        $ultimaSalida = null;
        
        if (!empty($grupo['entradas'])) {
            $primeraEntrada = $this->obtenerPrimeraHora($grupo['entradas']);
        }
        
        if (!empty($grupo['salidas'])) {
            $ultimaSalida = $this->obtenerUltimaHora($grupo['salidas']);
        }
        
        // Insertar nuevo registro
        $sql = "
            INSERT INTO asistencia (
                empleado_id, fecha, hora_entrada, hora_salida, 
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $exito = $stmt->execute([$empleadoId, $fecha, $primeraEntrada, $ultimaSalida]);
        
        if ($exito) {
            $registroId = $this->db->getConnection()->lastInsertId();
            
            return [
                'accion' => 'insertado',
                'registro_id' => $registroId,
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
        
        // Determinar si necesitamos actualizar entrada
        if (!$existente['hora_entrada'] && !empty($grupo['entradas'])) {
            $primeraEntrada = $this->obtenerPrimeraHora($grupo['entradas']);
            
            $sql = "UPDATE asistencia SET hora_entrada = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            
            if ($stmt->execute([$primeraEntrada, $existente['id']])) {
                $acciones[] = [
                    'accion' => 'entrada_actualizada',
                    'registro_id' => $existente['id'],
                    'nueva_hora_entrada' => $primeraEntrada
                ];
            }
        }
        
        // Determinar si necesitamos actualizar salida
        if (!$existente['hora_salida'] && !empty($grupo['salidas'])) {
            $ultimaSalida = $this->obtenerUltimaHora($grupo['salidas']);
            
            $sql = "UPDATE asistencia SET hora_salida = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            
            if ($stmt->execute([$ultimaSalida, $existente['id']])) {
                $acciones[] = [
                    'accion' => 'salida_actualizada',
                    'registro_id' => $existente['id'],
                    'nueva_hora_salida' => $ultimaSalida
                ];
            }
        }
        
        // Si ya tiene ambas horas pero tenemos más datos, podríamos actualizar con mejores horas
        if ($existente['hora_entrada'] && $existente['hora_salida']) {
            $mejorEntrada = $this->obtenerPrimeraHora($grupo['entradas']);
            $mejorSalida = $this->obtenerUltimaHora($grupo['salidas']);
            
            // Actualizar si encontramos mejores horas
            if ($mejorEntrada && $mejorEntrada < $existente['hora_entrada']) {
                $sql = "UPDATE asistencia SET hora_entrada = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $this->db->getConnection()->prepare($sql);
                if ($stmt->execute([$mejorEntrada, $existente['id']])) {
                    $acciones[] = [
                        'accion' => 'entrada_mejorada',
                        'registro_id' => $existente['id'],
                        'anterior' => $existente['hora_entrada'],
                        'nueva' => $mejorEntrada
                    ];
                }
            }
            
            if ($mejorSalida && $mejorSalida > $existente['hora_salida']) {
                $sql = "UPDATE asistencia SET hora_salida = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $this->db->getConnection()->prepare($sql);
                if ($stmt->execute([$mejorSalida, $existente['id']])) {
                    $acciones[] = [
                        'accion' => 'salida_mejorada',
                        'registro_id' => $existente['id'],
                        'anterior' => $existente['hora_salida'],
                        'nueva' => $mejorSalida
                    ];
                }
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
            'mensaje' => 'Procesamiento masivo completado',
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
                'tiempo' => number_format($tiempoTotal, 2) . ' segundos'
            ]
        ];
    }
}

echo "✅ Clase ZKTecoAsistenciaInserterOptimizado cargada\n";
?>