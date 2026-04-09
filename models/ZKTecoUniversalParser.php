<?php
require_once __DIR__ . '/ZKTecoFormatDetector.php';

/**
 * Parser Universal ZKTeco
 * Procesa cualquier formato de archivo .DAT ZKTeco sin perder registros
 */
class ZKTecoUniversalParser {
    
    private $formatDetector;
    private $formatoInfo;
    private $mapeoCampos;
    private $estadisticas;
    
    private $mapeoEmpleados = [];
    
    // Configuración de reglas ZKTeco
    private $configuracion = [
        'procesar_fines_semana' => false,
        'procesar_registros_fallidos' => true,
        'ajuste_horario_nocturno' => true,
        'mantener_fecha_original' => false,
        'calidad_minima' => null,
        'tipos_verificacion_permitidos' => null
    ];
    
    public function __construct($configuracion = []) {
        $this->formatDetector = new ZKTecoFormatDetector();
        $this->configuracion = array_merge($this->configuracion, $configuracion);
        $this->inicializarEstadisticas();
        $this->cargarMapeoEmpleados();
    }
    
    /**
     * Carga el mapeo de empleados desde la columna zkteo_id en tabla empleados
     */
    private function cargarMapeoEmpleados() {
        try {
            require_once __DIR__ . '/Database.php';
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT id, zkteo_id FROM empleados WHERE zkteo_id IS NOT NULL AND zkteo_id != ''");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->mapeoEmpleados[$row['zkteo_id']] = (int)$row['id'];
            }
        } catch (Exception $e) {
            error_log("Error cargando mapeo empleados: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene el mapeo de empleados
     */
    public function getMapeoEmpleados() {
        return $this->mapeoEmpleados;
    }
    
    /**
     * Procesa completamente un archivo .DAT
     * @param string $filePath Ruta del archivo
     * @return array Resultados del procesamiento
     */
    public function procesarArchivo($filePath) {
        try {
            // Fase 1: Detectar formato
            $this->formatoInfo = $this->formatDetector->detectarFormato($filePath);
            $validacion = $this->formatDetector->validarFormato($this->formatoInfo);
            
            if (!$validacion['valido']) {
                return [
                    'exito' => false,
                    'error' => 'Formato no válido: ' . $validacion['razon'],
                    'formato_detectado' => $this->formatoInfo
                ];
            }
            
            // Fase 2: Crear mapa de campos
            $this->mapeoCampos = $this->crearMapeoCampos();
            
            // Fase 3: Procesar registros
            $resultados = $this->procesarRegistros($filePath);
            
            return [
                'exito' => true,
                'formato_detectado' => $this->formatoInfo,
                'mapeo_campos' => $this->mapeoCampos,
                'estadisticas' => $this->estadisticas,
                'registros' => $resultados['registros'],
                'errores' => $resultados['errores']
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'error' => $e->getMessage(),
                'formato_detectado' => $this->formatoInfo ?? null
            ];
        }
    }
    
    /**
     * Crea el mapa de campos basado en el formato detectado
     */
    private function crearMapeoCampos() {
        $mapaCampos = [];
        $mapaTipos = $this->formatoInfo['mapa_tipos'];
        
        foreach ($mapaTipos as $posicion => $tipo) {
            $mapaCampos[$tipo] = $posicion;
        }
        
        // Mapeo específico por formato si es necesario
        switch ($this->formatoInfo['formato']) {
            case ZKTecoFormatDetector::FORMATO_SIMPLE:
                $mapaCampos = $this->mapeoFormatoSimple($mapaCampos);
                break;
            case ZKTecoFormatDetector::FORMATO_ESTANDAR:
                $mapaCampos = $this->mapeoFormatoEstandar($mapaCampos);
                break;
            case ZKTecoFormatDetector::FORMATO_EXTENDIDO:
                $mapaCampos = $this->mapeoFormatoExtendido($mapaCampos);
                break;
        }
        
        return $mapaCampos;
    }
    
    /**
     * Mapeo para formato simple
     */
    private function mapeoFormatoSimple($mapaBase) {
        // Para formato simple, asegurar posiciones conocidas
        if ($this->formatoInfo['conteo_campos'] == 3) {
            return [
                'empleado_id' => 0,
                'fecha' => 1,
                'hora' => 2,
                'accion' => null,    // Se determinará por lógica
                'verificacion' => null,
                'resultado' => null,
                'dispositivo_id' => null
            ];
        }
        
        return $mapaBase;
    }
    
    /**
     * Mapeo para formato estándar ZKTeco
     */
    private function mapeoFormatoEstandar($mapaBase) {
        // Asegurar mapeo para formato estándar de 6 campos
        return [
            'empleado_id' => 0,
            'datetime' => 1,
            'tipo' => 2,
            'verificacion' => 3,
            'resultado' => 4,
            'dispositivo_id' => 5,
            'fecha' => 'datetime', // Se procesará desde datetime
            'hora' => 'datetime',  // Se procesará desde datetime
            'accion' => 'tipo'    // Se determinará desde tipo/resultado
        ];
    }
    
    /**
     * Mapeo para formato extendido
     */
    private function mapeoFormatoExtendido($mapaBase) {
        // Para formato extendido, usar mapeo base con ajustes
        if (!isset($mapaBase['datetime']) && isset($mapaBase['fecha']) && isset($mapaBase['hora'])) {
            $mapaBase['datetime'] = $mapaBase['fecha'];
            $mapaBase['accion'] = $mapaBase['hora'] ?? null;
        }
        
        return $mapaBase;
    }
    
    /**
     * Procesa todos los registros del archivo
     */
    private function procesarRegistros($filePath) {
        $registros = [];
        $errores = [];
        $lineaNumero = 0;
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("No se puede abrir el archivo: $filePath");
        }
        
        while (($linea = fgets($handle)) !== false) {
            $lineaNumero++;
            $this->estadisticas['registros_leidos']++;
            
            try {
                $registro = $this->procesarLinea($linea, $lineaNumero);
                
                if ($registro) {
                    $registros[] = $registro;
                    $this->estadisticas['registros_procesados']++;
                } else {
                    $this->estadisticas['registros_omitidos']++;
                }
                
            } catch (Exception $e) {
                $errores[] = [
                    'linea' => $lineaNumero,
                    'contenido' => trim($linea),
                    'error' => $e->getMessage()
                ];
                $this->estadisticas['registros_error']++;
            }
        }
        
        fclose($handle);
        
        return [
            'registros' => $registros,
            'errores' => $errores
        ];
    }
    
    /**
     * Procesa una línea individual del archivo
     */
    private function procesarLinea($linea, $lineaNumero) {
        $linea = trim($linea);
        if (empty($linea)) {
            return null;
        }
        
        // El archivo tiene espacios múltiples al inicio y tabs para los demás campos
        // Primero separar por tabs, si no funciona usar espacios
        if (strpos($linea, "\t") !== false) {
            $campos = explode("\t", $linea);
        } else {
            // Usar múltiples espacios como separador
            $campos = preg_split('/\s{2,}/', $linea);
        }
        
        $registroRaw = $this->extraerDatosCampos($campos);
        
        if (!$registroRaw) {
            throw new Exception("No se pudieron extraer datos de la línea");
        }
        
        // DEBUG: Mostrar primeros registros
        if ($lineaNumero <= 3) {
            error_log("DEBUG Línea $lineaNumero - datos extraídos: " . json_encode($registroRaw));
        }
        
        // Aplicar reglas ZKTeco
        $registroProcesado = $this->aplicarReglasZKTeco($registroRaw, $lineaNumero);
        
        return $registroProcesado;
    }
    
    /**
     * Extrae datos de los campos según el mapeo detectado
     */
    private function extraerDatosCampos($campos) {
        $datos = [];
        
        // Primera pasada: extraer campos directos
        foreach ($this->mapeoCampos as $campo => $posicion) {
            if ($posicion === null || is_string($posicion)) {
                // Omitir referencias y nulos en la primera pasada
                continue;
            } elseif (isset($campos[$posicion])) {
                $valor = trim($campos[$posicion]);
                
                // Conversiones específicas por tipo
                switch ($campo) {
                    case 'empleado_id':
                    case 'dispositivo_id':
                        $datos[$campo] = is_numeric($valor) ? (int)$valor : null;
                        break;
                    case 'accion':
                    case 'resultado':
                    case 'tipo':
                    case 'verificacion':
                        $datos[$campo] = is_numeric($valor) ? (int)$valor : null;
                        break;
                    default:
                        $datos[$campo] = $valor;
                }
            } else {
                $datos[$campo] = null;
            }
        }
        
        // Procesar datetime si existe (antes de procesar referencias)
        if (isset($datos['datetime'])) {
            $datetime = $datos['datetime'];
            if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})$/', $datetime, $matches)) {
                $datos['fecha'] = $matches[1];
                $datos['hora'] = $matches[2];
            }
        }
        
        // Segunda pasada: procesar referencias especiales (no fecha/hora)
        foreach ($this->mapeoCampos as $campo => $posicion) {
            if ($posicion === null) {
                $datos[$campo] = null;
            } elseif (is_string($posicion) && $posicion !== 'datetime' && isset($datos[$posicion])) {
                // Referencia a otro campo (excepto datetime que ya procesamos)
                $datos[$campo] = $datos[$posicion];
            } elseif (is_string($posicion) && !isset($datos[$campo])) {
                // Referencia a campo que no se pudo extraer
                $datos[$campo] = null;
            }
        }
        
        return $datos;
    }
    
    /**
     * Aplica las reglas de negocio ZKTeco
     */
    private function aplicarReglasZKTeco($registro, $lineaNumero) {
        // Guardar el ID original del dispositivo ZK
        $zkEmpleadoId = $registro['empleado_id'];
        
        // Aplicar mapeo de empleado ZK a ID del sistema
        $registro['empleado_id'] = $this->obtenerEmpleadoId($zkEmpleadoId);
        
        // Regla 1: Validar datos mínimos
        if (!$registro['empleado_id'] || !$registro['fecha'] || !$registro['hora']) {
            $this->estadisticas['omitidos_datos_incompletos']++;
            error_log("DEBUG: Registro omitido - datos incompletos: emp={$registro['empleado_id']}, fecha={$registro['fecha']}, hora={$registro['hora']}");
            return null;
        }
        
        // Regla 2: Validar formato de fecha y hora
        if (!$this->validarFechaHora($registro['fecha'], $registro['hora'])) {
            $this->estadisticas['omitidos_formato_invalido']++;
            error_log("DEBUG: Registro omitido - formato inválido: {$registro['fecha']} {$registro['hora']}");
            return null;
        }
        
        // Regla 3: Fines de semana
        if (!$this->configuracion['procesar_fines_semana']) {
            $diaSemana = date('w', strtotime($registro['fecha']));
            if ($diaSemana == 0 || $diaSemana == 6) { // Domingo o Sábado
                $this->estadisticas['omitidos_fin_semana']++;
                return null;
            }
        }
        
        // Regla 4: Horario nocturno (00:00-04:00)
        $fechaFinal = $registro['fecha'];
        $horaFinal = $registro['hora'];
        
        // Solo ajustar si NO se especifica mantener fecha original
        if (!$this->configuracion['mantener_fecha_original'] && $this->configuracion['ajuste_horario_nocturno']) {
            if ($horaFinal >= '00:00:00' && $horaFinal <= '04:00:00') {
                // Usar DateTime para evitar problemas de timezone
                $dt = DateTime::createFromFormat('Y-m-d', $registro['fecha']);
                $dt->modify('-1 day');
                $fechaFinal = $dt->format('Y-m-d');
                $this->estadisticas['ajustes_nocturnos']++;
            }
        }
        
        // Regla 5: Determinar acción si no está explícita
        $accion = $registro['accion'];
        if ($accion === null) {
            $accion = $this->determinarAccionPorContexto($registro, $lineaNumero);
        }
        
        // Regla 6: Filtrar por resultado si está configurado
        if (!$this->configuracion['procesar_registros_fallidos']) {
            if (isset($registro['resultado']) && $registro['resultado'] == 0) {
                $this->estadisticas['omitidos_resultado_fallo']++;
                error_log("DEBUG: Registro omitido - resultado fallo: {$registro['resultado']}");
                return null;
            }
        }
        
        // Regla 7: Mapear ZKTeco-ID a empleado_id
        $empleadoIdMapeado = $this->mapearEmpleadoId($registro['empleado_id']);
        if (!$empleadoIdMapeado) {
            $this->estadisticas['omitidos_sin_mapeo']++;
            error_log("DEBUG: Registro omitido - sin mapeo ZK-ID {$registro['empleado_id']}");
            return null;
        }
        
        // Crear registro final
        $registroFinal = [
            'linea_origen' => $lineaNumero,
            'zk_empleado_id' => $registro['empleado_id'],
            'empleado_id' => $empleadoIdMapeado,
            'fecha' => $fechaFinal,
            'hora' => $horaFinal,
            'accion' => $accion,
            'dispositivo_id' => $registro['dispositivo_id'] ?? null,
            'verificacion' => $registro['verificacion'] ?? null,
            'resultado' => $registro['resultado'] ?? null,
            'datetime_original' => isset($registro['datetime']) ? $registro['datetime'] : ($fechaFinal . ' ' . $horaFinal),
            'formato_detectado' => $this->formatoInfo['formato'],
            'metadata_raw' => $registro
        ];
        
        $this->estadisticas['registros_validos']++;
        return $registroFinal;
    }
    
    /**
     * Valida formato de fecha y hora
     */
    private function validarFechaHora($fecha, $hora) {
        // Validar fecha (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        
        // Validar hora (HH:MM:SS)
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
            return false;
        }
        
        // Validar que sean fechas/horas válidas - usar formato de fecha explícito para evitar timezone
        $timestamp = DateTime::createFromFormat('Y-m-d H:i:s', $fecha . ' ' . $hora);
        return $timestamp !== false;
    }
    
    /**
     * Determina la acción basada en el contexto
     */
    private function determinarAccionPorContexto($registro, $lineaNumero) {
        // Por defecto, asumir entrada (0) si no se puede determinar
        return 0;
    }
    
    /**
     * Mapea ZKTeco-ID a empleado_id
     */
    private function mapearEmpleadoId($zkEmpleadoId) {
        return $this->mapeoEmpleados[$zkEmpleadoId] ?? $zkEmpleadoId;
    }
    
    /**
     * Inicializa las estadísticas
     */
    private function inicializarEstadisticas() {
        $this->estadisticas = [
            'registros_leidos' => 0,
            'registros_procesados' => 0,
            'registros_validos' => 0,
            'registros_omitidos' => 0,
            'registros_error' => 0,
            'omitidos_datos_incompletos' => 0,
            'omitidos_formato_invalido' => 0,
            'omitidos_fin_semana' => 0,
            'omitidos_resultado_fallo' => 0,
            'omitidos_sin_mapeo' => 0,
            'ajustes_nocturnos' => 0,
            'empleados_unicos' => 0,
            'fecha_inicio' => null,
            'fecha_fin' => null
        ];
    }
    
    /**
     * Actualiza estadísticas finales
     */
    private function actualizarEstadisticasFinales($registros) {
        if (empty($registros)) {
            return;
        }
        
        $empleadosUnicos = array_unique(array_column($registros, 'empleado_id'));
        $this->estadisticas['empleados_unicos'] = count($empleadosUnicos);
        
        $fechas = array_column($registros, 'fecha');
        $this->estadisticas['fecha_inicio'] = min($fechas);
        $this->estadisticas['fecha_fin'] = max($fechas);
    }
    
    /**
     * Establece el mapeo de empleados
     */
    public function setMapeoEmpleados($mapeo) {
        $this->mapeoEmpleados = $mapeo;
    }
    
    /**
     * Establece la configuración
     */
    public function setConfiguracion($configuracion) {
        $this->configuracion = array_merge($this->configuracion, $configuracion);
    }
    
    /**
     * Obtiene estadísticas detalladas
     */
    public function getEstadisticas() {
        return $this->estadisticas;
    }
    
    /**
     * Obtiene el ID del empleado del sistema a partir del ID ZK
     * @param int $zkEmpleadoId ID del empleado en el dispositivo ZK
     * @return int ID del empleado en el sistema
     */
    private function obtenerEmpleadoId($zkEmpleadoId) {
        if (isset($this->mapeoEmpleados[$zkEmpleadoId])) {
            return $this->mapeoEmpleados[$zkEmpleadoId];
        }
        // Si no hay mapeo, usar el ID directo
        return $zkEmpleadoId;
    }
}