<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ZKTecoMappingManager.php';

/**
 * Procesador de logs de ZKTeco para el sistema biométrico
 * ANÁLISIS REAL: TODOS los registros tienen tipo=1, por lo tanto
 * la clasificación debe basarse únicamente en la hora del evento
 */
class ZKTecoLogProcessor {
    private $db;
    private $logFile;
    private $mappingManager;
    private $columnasEmpleadoCache = [];
    
    // HORARIOS OPERATIVOS DEFINITIVOS
    const ENTRADA_TEMPRANA_INICIO = '07:00:00';
    const ENTRADA_TEMPRANA_FIN = '11:59:59';
    
    const SALIDA_ALMUERZO_INICIO = '12:00:00';
    const SALIDA_ALMUERZO_FIN = '13:59:59';
    
    const REINGRESO_INICIO = '14:00:00';
    const REINGRESO_FIN = '15:59:59';
    
    const SALIDA_FINAL_INICIO = '16:00:00';
    const SALIDA_FINAL_FIN = '18:59:59';
    
    const DIA_LABORAL_INICIO = '07:00:00';
    const DIA_LABORAL_FIN = '18:59:59';
    
    public function __construct($logFile = null) {
        $this->db = new Database();
        $this->mappingManager = new ZKTecoMappingManager();
        $this->logFile = $logFile ?: __DIR__ . '/../data/1_attlog.dat';
    }
    
    /**
     * Analiza el archivo de logs y determina entradas/salidas
     */
    public function procesarArchivoCompleto() {
        echo "=== PROCESANDO ARCHIVO ZKTeco: {$this->logFile} ===\n";
        
        // Primero, generar mapeos automáticos (si la estructura lo permite)
        $resultadoMapeos = [
            'total_encontrados' => 0,
            'ya_mapeados' => 0,
            'nuevos_mapeos' => 0
        ];
        try {
            $this->mappingManager->crearTablaMapeo();
            $resultadoMapeos = $this->mappingManager->generarMapeosAutomaticos($this->logFile);
        } catch (Exception $e) {
            echo "⚠️ No se pudo generar mapeo automático: {$e->getMessage()}\n";
            echo "⚠️ Se continuará con búsqueda directa por columna biométrica en empleados.\n\n";
        }
        
        echo "Resultados de mapeo:\n";
        echo "- Total IDs encontrados: {$resultadoMapeos['total_encontrados']}\n";
        echo "- Ya mapeados: {$resultadoMapeos['ya_mapeados']}\n";
        echo "- Nuevos mapeos: {$resultadoMapeos['nuevos_mapeos']}\n\n";
        
        if (!file_exists($this->logFile)) {
            throw new Exception("Archivo de logs no encontrado: {$this->logFile}");
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $totalLines = count($lines);
        
        echo "Total de registros para procesar: {$totalLines}\n";
        echo "Iniciando procesamiento...\n\n";
        
        $procesados = 0;
        $insertados = 0;
        $errores = 0;
        $noMapeados = 0;
        $estadisticas = [
            'entradas' => 0,
            'salidas_almuerzo' => 0,
            'reingresos' => 0,
            'salidas_final' => 0,
            'fuera_horario' => 0,
            'sabado_domingo' => 0,
            'domingo' => 0,
            'lunes' => 0,
            'martes' => 0,
            'miercoles' => 0,
            'jueves' => 0,
            'viernes' => 0
        ];
        
        foreach ($lines as $index => $line) {
            try {
                $registro = $this->parseLineaLog($line);
                if ($registro) {
                    $clasificacion = $this->determinarTipoRegistroPorHora($registro);
                    $empleado = $this->getEmpleadoPorZKTecoId($registro['id_usuario_zkteco']);
                    
                    if ($empleado) {
                        if ($this->procesarRegistro($registro, $clasificacion, $empleado)) {
                            $insertados++;
                            
                            // Actualizar estadísticas
                            if ($clasificacion['tipo'] === 'entrada') {
                                $estadisticas['entradas']++;
                            } elseif ($clasificacion['tipo'] === 'salida_almuerzo') {
                                $estadisticas['salidas_almuerzo']++;
                            } elseif ($clasificacion['tipo'] === 'reingreso') {
                                $estadisticas['reingresos']++;
                            } elseif ($clasificacion['tipo'] === 'salida_final' || $clasificacion['tipo'] === 'salida') {
                                $estadisticas['salidas_final']++;
                            } elseif ($clasificacion['tipo'] === 'fuera_horario') {
                                $estadisticas['fuera_horario']++;
                            } elseif ($clasificacion['tipo'] === 'sabado_domingo') {
                                $estadisticas['sabado_domingo']++;
                            } elseif ($clasificacion['tipo'] === 'domingo') {
                                $estadisticas['domingo']++;
                            } else {
                                // Día de semana normal (lunes-viernes)
                                $diaSemana = date('N', strtotime($registro['fecha_hora']));
                                if ($diaSemana >= 1 && $diaSemana <= 5) {
                                    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
                                    $estadisticas[$dias[$diaSemana-1]]++;
                                }
                            }
                        }
                    } else {
                        $noMapeados++;
                        echo "⚠️ Empleado no mapeado: {$registro['id_usuario_zkteco']} (registro " . ($index + 1) . ")\n";
                    }
                    
                    $procesados++;
                }
                
                if (($index + 1) % 1000 === 0) {
                    $porcentaje = round(($index + 1) / $totalLines * 100, 2);
                    echo "Progreso: " . ($index + 1) . " / {$totalLines} ({$porcentaje}%)\n";
                    echo "Insertados: {$insertados} | Errores: {$errores} | Sin mapeo: {$noMapeados}\n";
                }
            } catch (Exception $e) {
                echo "Error procesando línea " . ($index + 1) . ": " . $e->getMessage() . "\n";
                $errores++;
            }
        }
        
        echo "\n=== RESUMEN FINAL ===\n";
        echo "Total de líneas procesadas: {$procesados}\n";
        echo "Registros insertados: {$insertados}\n";
        echo "Empleados sin mapeo: {$noMapeados}\n";
        echo "Errores encontrados: {$errores}\n\n";
        
        // Mostrar estadísticas
        echo "=== ESTADÍSTICAS POR TIPO ===\n";
        echo "Entradas (7-12h): {$estadisticas['entradas']}\n";
        echo "Salidas almuerzo (12-14h): {$estadisticas['salidas_almuerzo']}\n";
        echo "Reingresos (14-16h): {$estadisticas['reingresos']}\n";
        echo "Salidas finales (16-18h): {$estadisticas['salidas_final']}\n";
        echo "Fuera de horario: {$estadisticas['fuera_horario']}\n";
        echo "Domingos: {$estadisticas['domingo']}\n";
        echo "Lunes a Viernes (promedio diario): " . round(array_sum(array_slice($estadisticas, 6, 11)) / 5) . "\n";
        
        return [
            'total' => $totalLines,
            'procesados' => $procesados,
            'insertados' => $insertados,
            'errores' => $errores,
            'sin_mapeo' => $noMapeados,
            'estadisticas' => $estadisticas,
            'resultado_mapeo' => $resultadoMapeos
        ];
    }
    
    /**
     * Parsea una línea del log y devuelve un array con los datos
     * Formato: [ID]\t[Fecha]\t[Tipo]\t[C2]\t[C3]\t[C4]
     */
    private function parseLineaLog($line) {
        $parts = explode("\t", trim($line));
        
        if (count($parts) < 6) {
            echo "Línea mal formada: {$line}\n";
            return null;
        }
        
        return [
            'id_usuario_zkteco' => trim($parts[0]),
            'fecha_hora' => trim($parts[1]),
            'tipo_evento' => (int)trim($parts[2]), // Siempre será 1
            'campo2' => trim($parts[3]),
            'campo3' => trim($parts[4]),
            'campo4' => trim($parts[5]),
            'linea_original' => trim($line)
        ];
    }
    
    /**
     * Determina si el registro es entrada o salida basado en la hora y día de la semana
     * CLASIFICACIÓN OFICIAL:
     * - Entrada: 07:00 a.m. a 12:00 p.m.
     * - Salida: 02:00 p.m. a 06:00 p.m.
     * BASADO EN EL ANÁLISIS REAL: Todos los registros tienen tipo=1
     */
    public function determinarTipoRegistroPorHora($registro) {
        $hora = date('H:i', strtotime($registro['fecha_hora']));
        $fecha = date('Y-m-d', strtotime($registro['fecha_hora']));
        $diaSemana = date('N', strtotime($fecha)); // 1=Lunes, 7=Domingo
        
        $hora_num = (int)date('H', strtotime($registro['fecha_hora']));
        $minuto_num = (int)date('i', strtotime($registro['fecha_hora']));
        
        // SÁBADO Y DOMINGO: Fuera de horario laboral
        if ($diaSemana == 6 || $diaSemana == 7) {
            return [
                'tipo' => 'fin_semana',
                'metodo' => 'sabado_domingo',
                'justificacion' => 'Registro en fin de semana (sin penalización)'
            ];
        }
        
        // HORARIO NORMAL LUNES A VIERNES
        
        // ENTRADA (07:00 - 12:00)
        if ($hora_num >= 7 && $hora_num < 12) {
            return [
                'tipo' => 'entrada',
                'metodo' => 'entrada_laboral',
                'justificacion' => 'Entrada en horario laboral (07:00-12:00)'
            ];
        }
        
        // SALIDA (14:00 - 18:00) - Rango correcto según especificaciones
        if ($hora_num >= 14 && $hora_num < 18) {
            return [
                'tipo' => 'salida',
                'metodo' => 'salida_laboral',
                'justificacion' => 'Salida del día (14:00-18:00)'
            ];
        }
        
        // HORARIO INTERMEDIO (12:00 - 14:00) - Fuera de clasificación
        if ($hora_num >= 12 && $hora_num < 14) {
            return [
                'tipo' => 'horario_intermedio',
                'metodo' => 'horario_intermedio',
                'justificacion' => 'Registro en horario intermedio (12:00-14:00) - No cuenta como entrada/salida'
            ];
        }
        
        // FUERA DE HORARIO LABORAL (antes de 7:00 o después de 18:00)
        if ($hora_num < 7 || $hora_num >= 19) {
            return [
                'tipo' => 'fuera_horario',
                'metodo' => 'fuera_de_horario',
                'justificacion' => 'Fuera del horario establecido (antes de 07:00 o después de 18:00)'
            ];
        }
        
        // CUALQUIER OTRO CASO (no debería ocurrir)
        return [
            'tipo' => 'desconocido',
            'metodo' => 'sin_categoria',
            'justificacion' => 'Horario no categorizable'
        ];
    }
    
    /**
     * Obtiene información del empleado usando el mapeo de ZKTeco
     */
    private function getEmpleadoPorZKTecoId($idUsuario) {
        // Prioridad 1: columna dedicada en empleados.zkteo_id
        if ($this->empleadosTieneColumna('zkteo_id')) {
            $sql = "
                SELECT id, nombre, apellido, rfc, area
                FROM empleados
                WHERE zkteo_id = ? AND activo = 1
                LIMIT 1
            ";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$idUsuario]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($empleado) {
                return $empleado;
            }
        }

        // Prioridad 2: tabla de mapeo auxiliar
        $mapeo = null;
        try {
            $mapeo = $this->mappingManager->getEmpleadoIdPorZKTeco($idUsuario);
        } catch (Exception $e) {
            $mapeo = null;
        }
        
        if ($mapeo && !empty($mapeo['empleado_id'])) {
            // Si ya está mapeado, obtener datos del empleado
            $sql = "
                SELECT id, nombre, apellido, rfc, area
                FROM empleados 
                WHERE id = ? AND activo = 1
                LIMIT 1
            ";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$mapeo['empleado_id']]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Prioridad 3: compatibilidad (id interno como texto/número)
        $sql = "
            SELECT id, nombre, apellido, rfc, area
            FROM empleados 
            WHERE (CAST(id AS CHAR) = ? OR id = CAST(? AS UNSIGNED))
              AND activo = 1
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $idUsuario, (int)$idUsuario
        ]);
        
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empleado) {
            // Crear mapeo para búsquedas futuras (si está disponible)
            try {
                $this->mappingManager->insertarMapeo($idUsuario, $empleado, 'automatico');
            } catch (Exception $e) {
                // Si falla el mapeo auxiliar, no interrumpir la inserción de asistencia.
            }
        }
        
        return $empleado;
    }

    private function empleadosTieneColumna($columna) {
        if (array_key_exists($columna, $this->columnasEmpleadoCache)) {
            return $this->columnasEmpleadoCache[$columna];
        }

        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'empleados'
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$columna]);
        $existe = ((int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
        $this->columnasEmpleadoCache[$columna] = $existe;
        return $existe;
    }
    
    /**
     * Procesa un registro individual y lo inserta en la base de datos
     */
    private function procesarRegistro($registro, $clasificacion, $empleado) {
        $fecha = date('Y-m-d', strtotime($registro['fecha_hora']));
        $hora = date('H:i:s', strtotime($registro['fecha_hora']));
        $diaSemana = date('N', strtotime($fecha));
        
        try {
            // Verificar si ya existe un registro para evitar duplicados en el mismo día
            if ($this->existeRegistroDuplicado($empleado['id'], $fecha, $clasificacion['tipo'])) {
                echo "⚠️ Registro duplicado omitido: {$empleado['nombre']} {$empleado['apellido']} - {$clasificacion['tipo']} el día {$fecha}\n";
                return false;
            }
            
            if ($clasificacion['tipo'] === 'entrada' || $clasificacion['tipo'] === 'reingreso') {
                // Insertar o actualizar registro de entrada
                $stmt = $this->db->getConnection()->prepare("INSERT INTO asistencia (empleado_id, fecha, hora_entrada, dispositivo_id, tipo_biometria, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE hora_entrada = VALUES(hora_entrada),dispositivo_id = VALUES(dispositivo_id),
                    tipo_biometria = VALUES(tipo_biometria), updated_at = NOW()");
                
                $stmt->execute([
                    $empleado['id'],
                    $fecha,
                    $hora,
                    $registro['campo2'], // ID del dispositivo biométrico
                    'huella'
                ]);
                
                echo "✓ {$clasificacion['metodo']}: {$empleado['nombre']} {$empleado['apellido']} - {$hora} - {$fecha}\n";
                
            } elseif ($clasificacion['tipo'] === 'salida_almuerzo' || $clasificacion['tipo'] === 'salida_final' || $clasificacion['tipo'] === 'salida') {
                // Insertar o actualizar registro de salida
                $stmt = $this->db->getConnection()->prepare("
                    INSERT INTO asistencia (empleado_id, fecha, hora_salida, dispositivo_id, tipo_biometria, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    hora_salida = VALUES(hora_salida),
                    dispositivo_id = VALUES(dispositivo_id),
                    tipo_biometria = VALUES(tipo_biometria),
                    updated_at = NOW()
                ");
                
                $stmt->execute([
                    $empleado['id'],
                    $fecha,
                    $hora,
                    $registro['campo2'],
                    'huella'
                ]);
                
                echo "✓ {$clasificacion['metodo']}: {$empleado['nombre']} {$empleado['apellido']} - {$hora} - {$fecha}\n";
                
            } elseif ($clasificacion['tipo'] === 'fuera_horario') {
                // Fuera de horario: podría ser hora extra o registro de fin de semana
                // Solo insertar como nota o omitir según política
                echo "⚠️ Fuera de horario: {$empleado['nombre']} {$empleado['apellido']} - {$hora} - {$fecha}\n";
                return false; // Opcionalmente: return true si se desea registrar de todos modos
            }
            
            return true;
            
        } catch (Exception $e) {
            echo "✗ Error insertando registro: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            return false;
        }
    }
    
    /**
     * Verifica si ya existe un registro duplicado
     */
    private function existeRegistroDuplicado($empleadoId, $fecha, $tipo) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as existe 
            FROM asistencia 
            WHERE empleado_id = ? AND DATE(fecha) = ? AND 
                  (
                      (hora_entrada IS NOT NULL AND ? IN ('entrada', 'reingreso')) OR
                      (hora_salida IS NOT NULL AND ? IN ('salida_almuerzo', 'salida_final'))
                  )
        ");
        
        $stmt->execute([$empleadoId, $fecha, $tipo, $tipo]);
        $result = $stmt->fetch();
        return $result['existe'] > 0;
    }
    
    /**
     * Genera un reporte de análisis del archivo
     */
    public function generarReporte() {
        echo "\n=== ANÁLISIS DEL ARCHIVO DE LOGS ZKTeco ===\n";
        
        if (!file_exists($this->logFile)) {
            echo "Archivo no encontrado.\n";
            return;
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $empleados = [];
        $estadisticasDia = [];
        $rangoFechas = [];
        
        foreach ($lines as $line) {
            $registro = $this->parseLineaLog($line);
            if ($registro) {
                $empleados[$registro['id_usuario_zkteco']] = ($empleados[$registro['id_usuario_zkteco']] ?? 0) + 1;
                $fecha = date('Y-m-d', strtotime($registro['fecha_hora']));
                $estadisticasDia[$fecha] = ($estadisticasDia[$fecha] ?? 0) + 1;
                
                if (empty($rangoFechas)) {
                    $rangoFechas['inicio'] = $fecha;
                    $rangoFechas['fin'] = $fecha;
                } else {
                    if ($fecha < $rangoFechas['inicio']) {
                        $rangoFechas['inicio'] = $fecha;
                    }
                    if ($fecha > $rangoFechas['fin']) {
                        $rangoFechas['fin'] = $fecha;
                    }
                }
            }
        }
        
        echo "Rango de fechas: {$rangoFechas['inicio']} a {$rangoFechas['fin']}\n";
        echo "Total de registros: " . count($lines) . "\n";
        echo "Días con registros: " . count($estadisticasDia) . "\n";
        echo "Empleados únicos: " . count($empleados) . "\n\n";
        
        echo "Top 20 empleados más activos:\n";
        arsort($empleados);
        $topEmpleados = array_slice($empleados, 0, 20, true);
        foreach ($topEmpleados as $id => $count) {
            echo "- ID ZKTeco {$id}: {$count} registros\n";
        }
        
        echo "\nEstadísticas por día de la semana:\n";
        echo "- Lunes: " . ($estadisticasDia[date('Y-m-d', strtotime('2025-01-20'))] ?? 0) . " registros\n";
        echo "- Martes: " . ($estadisticasDia[date('Y-m-d', strtotime('2025-01-21'))] ?? 0) . " registros\n";
        echo "- Miércoles: " . ($estadisticasDia[date('Y-m-d', strtotime('2025-01-22'))] ?? 0) . " registros\n";
        echo "- Jueves: " . ($estadisticasDia[date('Y-m-d', strtotime('2025-01-23'))] ?? 0) . " registros\n";
        echo "- Viernes: " . ($estadisticasDia[date('Y-m-d', strtotime('2025-01-24'))] ?? 0) . " registros\n";
        echo "- Sábados: " . ($estadisticasDia[date('Y-m-d', strtotime('2025-01-25'))] ?? 0) . " registros\n";
        echo "- Domingos: " . ($estadisticasDia[date('Y-m-d', strtotime('2025-01-26'))] ?? 0) . " registros\n";
    }
    
    /**
     * Configura horarios personalizados
     */
    public function configurarHorarios($entrada, $salida, $tolerancia = 15) {
        // Este método podría actualizarse si se necesita más flexibilidad
        echo "Horarios configurados: Entrada 7:00, Salida 17:00, Tolerancia {$tolerancia} min\n";
    }
}
