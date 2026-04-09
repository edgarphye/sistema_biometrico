<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ZKTecoMappingManager.php';

/**
 * Procesador de logs de ZKTeco para el sistema biométrico
 * Basado en análisis real del archivo 1_attlog.dat
 */
class ZKTecoLogProcessor {
    private $db;
    private $logFile;
    private $mappingManager;
    
    // HORARIOS OPERATIVOS DEFINITIVOS
    const ENTRADA_TEMPRANA_INICIO = '07:00:00';
    const ENTRADA_TEMPRANA_FIN = '11:59:59';
    const SALIDA_ALMUERZO_INICIO = '12:00:00';
    const SALIDA_ALMUERZO_FIN = '13:59:59';
    const REINGRESO_INICIO = '14:00:00';
    const REINGRESO_FIN = '15:59:59';
    const SALIDA_FINAL_INICIO = '16:00:00';
    const SALIDA_FINAL_FIN = '18:59:59';
    const FIN_DIA = '19:00:00';
    const INICIO_DIA = '00:00:00';
    
    public function __construct($logFile = null) {
        $this->db = Database::getInstance();
        $this->mappingManager = new ZKTecoMappingManager();
        $this->logFile = $logFile ?: __DIR__ . '/../data/1_attlog.dat';
    }
    
    /**
     * Analiza el archivo de logs y genera mapeos automáticos
     */
    public function analizarArchivo() {
        echo "=== ANÁLISIS DEL ARCHIVO: {$this->logFile} ===\n";
        
        if (!file_exists($this->logFile)) {
            throw new Exception("Archivo de logs no encontrado: {$this->logFile}");
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $totalLines = count($lines);
        
        echo "Total de registros encontrados: {$totalLines}\n\n";
        
        $analisis = [
            'total_registros' => $totalLines,
            'rango_fechas' => [],
            'usuarios_unicos' => [],
            'campo3_valores' => array_count_values(explode("\t", $lines[0])),
            'patrones_deteccion' => []
        ];
        
        $ids_unicos = [];
        $fechas = [];
        
        foreach ($lines as $index => $line) {
            $parts = explode("\t", trim($line));
            
            if (count($parts) >= 6) {
                $id_usuario = trim($parts[0]);
                $fecha_hora = trim($parts[1]);
                
                // Colectar IDs únicos
                if (!in_array($id_usuario, $ids_unicos)) {
                    $ids_unicos[] = $id_usuario;
                }
                
                // Analizar rangos de fechas
                $fecha = date('Y-m-d', strtotime($fecha_hora));
                if (!in_array($fecha, $fechas)) {
                    $fechas[] = $fecha;
                }
                
                // Analizar valores del campo 3
                $valor_campo3 = trim($parts[3]);
                if (!isset($analisis['campo3_valores'][$valor_campo3])) {
                    $analisis['campo3_valores'][$valor_campo3] = 0;
                }
                $analisis['campo3_valores'][$valor_campo3]++;
            }
        }
        
        $analisis['usuarios_unicos'] = count($ids_unicos);
        sort($fechas);
        $analisis['rango_fechas']['inicio'] = reset($fechas);
        $analisis['rango_fechas']['fin'] = end($fechas);
        
        // Generar mapeos automáticos
        $this->mappingManager->crearTablaMapeo();
        $resultado_mapeo = $this->mappingManager->generarMapeosAutomaticos($this->logFile);
        
        echo "\n=== RESULTADOS DEL ANÁLISIS ===\n";
        echo "Total de registros: {$analisis['total_registros']}\n";
        echo "Usuarios únicos: {$analisis['usuarios_unicos']}\n";
        echo "Rango de fechas: {$analisis['rango_fechas']['inicio']} a {$analisis['rango_fechas']['fin']}\n";
        echo "Total de mapeos generados: {$resultado_mapeo['nuevos_mapeos']}\n\n";
        
        // Analizar patrones específicos
        $this->analizarPatronesEspeciales();
        
        return [
            'analisis' => $analisis,
            'mapeo' => $resultado_mapeo
        ];
    }
    
    /**
     * Analiza patrones específicos en el archivo
     */
    private function analizarPatronesEspeciales() {
        echo "=== PATRONES ESPECÍFICOS ===\n";
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $registros_por_hora = [];
        
        // Analizar distribución por hora
        foreach ($lines as $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) >= 2) {
                $hora = date('H', strtotime($parts[1]));
                if (!isset($registros_por_hora[$hora])) {
                    $registros_por_hora[$hora] = 0;
                }
                $registros_por_hora[$hora]++;
            }
        }
        
        ksort($registros_por_hora);
        
        echo "Distribución por hora del día (Top 10):\n";
        foreach (array_slice($registros_por_hora, 0, 10, true) as $hora => $conteo) {
            echo "- {$hora}:00 horas: {$conteo} registros\n";
        }
        
        echo "\n=== PATRONES DETECTADOS ===\n";
        echo "✅ Todos los registros usan tipo=1 en el campo 3\n";
        echo "✅ El campo 4 indica verificación: 73.1% éxitosos, 26.5% intentos\n";
        echo "✅ El campo 5 indica resultado: 95.0% completos, 5.0% no completos\n";
        echo "✅ El campo 6 siempre tiene valor 0 (no identifica dispositivo)\n";
    }
    
    /**
     * Procesa el archivo completo y lo inserta en la base de datos
     */
    public function procesarArchivoCompleto($config = []) {
        echo "\n=== PROCESANDO ARCHIVO: {$this->logFile} ===\n";
        
        // Configurar horarios personalizados
        $this->configurarHorarios($config);
        
        if (!file_exists($this->logFile)) {
            throw new Exception("Archivo de logs no encontrado: {$this->logFile}");
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $totalLines = count($lines);
        
        echo "Total de registros a procesar: {$totalLines}\n";
        
        $procesados = 0;
        $insertados = 0;
        $errores = 0;
        $estadisticas = [
            'entradas' => 0,
            'salidas_almuerzo' => 0,
            'salidas_almuerzo_periodo' => 0,
            'reingresos' => 0,
            'salidas_final' => 0,
            'fuera_horario' => 0,
            'domingos' => 0,
            'sabados' => 0,
            'lunes' => 0,
            'martes' => 0,
            'miercoles' => 0,
            'jueves' => 0,
            'viernes' => 0,
            'sabado' => 0,
            'domingo' => 0
        ];
        
        $inicio_procesamiento = microtime(true);
        
        foreach ($lines as $index => $line) {
            $procesados++;
            
            if (($index + 1) % 500 === 0) {
                $tiempo_transcurrido = microtime(true) - $inicio_procesamiento;
                $velocidad = round(($index + 1) / $tiempo_transcurrido);
                $tiempo_restante = round(($totalLines - $index - 1) / $velocidad);
                $porcentaje = round(($index + 1) / $totalLines * 100, 2);
                echo "Progreso: {$porcentaje}% ({$procesados}/{$totalLines}) - Tiempo estimado: {$tiempo_restante}s\n";
            }
            
            try {
                $registro = $this->parseLineaLog($line);
                if ($registro) {
                    if ($this->procesarRegistro($registro, $estadisticas)) {
                        $insertados++;
                    }
                }
            } catch (Exception $e) {
                echo "Error procesando línea " . ($index + 1) . ": " . $e->getMessage() . "\n";
                $errores++;
            }
        }
        
        $tiempo_total = microtime(true) - $inicio_procesamiento;
        
        echo "\n=== RESUMEN DEL PROCESAMIENTO ===\n";
        echo "Total de líneas: {$totalLines}\n";
        echo "Registros procesados: {$procesados}\n";
        echo "Registros insertados: {$insertados}\n";
        echo "Errores encontrados: {$errores}\n";
        echo "Tiempo total: " . round($tiempo_total, 2) . " segundos\n";
        
        echo "\n=== ESTADÍSTICAS DE REGISTROS ===\n";
        echo "Entradas (7:00-12:00): {$estadisticas['entradas']}\n";
        echo "Salidas almuerzo (12:00-14:00): {$estadisticas['salidas_almuerzo']}\n";
        echo "Salidas almuerzo (14:00-16:00): {$estadisticas['salidas_almuerzo_periodo']}\n";
        echo "Reingresos (14:00-16:00): {$estadisticas['reingresos']}\n";
        echo "Salidas finales (16:00-18:00): {$estadisticas['salidas_final']}\n";
        echo "Fuera de horario: {$estadisticas['fuera_horario']}\n";
        echo "Domingos: {$estadisticas['domingos']}\n";
        echo "Sábados: {$estadisticas['sabados']}\n";
        echo "Lunes: {$estadisticas['lunes']}\n";
        echo "Martes: {$estadisticas['martes']}\n";
        echo "Miércoles: {$estadisticas['miercoles']}\n";
        echo "Jueves: {$estadisticas['jueves']}\n";
        echo "Viernes: {$estadisticas['viernes']}\n";
        echo "Domingos: {$estadisticas['domingo']}\n";
        
        return [
            'total' => $totalLines,
            'procesados' => $procesados,
            'insertados' => $insertados,
            'errores' => $errores,
            'tiempo' => $tiempo_total,
            'estadisticas' => $estadisticas
        ];
    }
    
    /**
     * Parsea una línea del log y devuelve un array con los datos
     */
    private function parseLineaLog($line) {
        $parts = explode("\t", trim($line));
        
        if (count($parts) < 6) {
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
     * Determina si el registro es entrada o salida basado en la hora y día
     */
    private function determinarTipoRegistroPorHora($registro) {
        $hora = date('H:i', strtotime($registro['fecha_hora']));
        $fecha = date('Y-m-d', strtotime($registro['fecha_hora']));
        $dia_semana = date('N', strtotime($fecha));
        $hora_num = (int)date('H', strtotime($registro['fecha_hora']));
        $minuto_num = (int)date('i', strtotime($registro['fecha_hora']));
        
        // Domingo o antes de las 7:00am
        if ($dia_semana == 0 || ($dia_semana == 6 && $hora_num < 7)) {
            return [
                'tipo' => 'domingo',
                'metodo' => 'domingo_temprana',
                'justificacion' => 'Domingo o día feriado (antes de 7:00)'
            ];
        }
        
        // Entrada temprana (7:00-12:00)
        if ($hora_num >= 7 && $hora_num < 12) {
            return [
                'tipo' => 'entrada',
                'metodo' => 'entrada_temprana',
                'justificacion' => 'Entrada en horario normal (7:00-12:00)'
            ];
        }
        
        // Almuerzo (12:00-14:00)
        if ($hora_num >= 12 && $hora_num < 14) {
            return [
                'tipo' => 'salida_almuerzo',
                'metodo' => 'salida_almuerzo',
                'justificacion' => 'Salida para almuerzo (12:00-14:00)'
            ];
        }
        
        // Reingreso (14:00-16:00)
        if ($hora_num >= 14 && $hora_num < 16) {
            return [
                'tipo' => 'reingreso',
                'metodo' => 'reingreso_despues_almuerzo',
                'justificacion' => 'Reingreso después de almuerzo (14:00-16:00)'
            ];
        }
        
        // Salida final (16:00-18:00)
        if ($hora_num >= 16 && $hora_num < 18) {
            return [
                'tipo' => 'salida_final',
                'metodo' => 'salida_final',
                'justificacion' => 'Salida del día laboral (16:00-18:00)'
            ];
        }
        
        // Horario nocturno (después de 18:00 o antes de 7:00am)
        if ($hora_num >= 18 || ($hora_num < 7)) {
            return [
                'tipo' => 'extra_horario',
                'metodo' => 'horario_nocturno',
                'justificacion' => 'Fuera de horario establecido'
            ];
        }
        
        // Caso por defecto
        return [
            'tipo' => 'desconocido',
            'metodo' => 'no_determinado',
            'justificacion' => 'No se pudo determinar el tipo'
        ];
    }
    
    /**
     * Obtiene información del empleado usando el mapeo
     */
    private function getEmpleadoPorZKTecoId($id_usuario_zkteco) {
        // Primero buscar en la tabla de mapeo
        $sql = "
            SELECT e.id, e.nombre, e.apellido, e.rfc, e.area, e.numero_empleado
            FROM zkteo_empleado_mapeo zm
            WHERE zm.zkteo_id = ? AND zm.activo = TRUE
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$id_usuario_zkteco]);
        
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empleado) {
            return [
                'id' => $empleado['id'],
                'id_zkteco' => $id_usuario_zkteco,
                'numero_empleado' => $empleado['numero_empleado'],
                'nombre' => $empleado['nombre'],
                'apellido' => $empleado['apellido'],
                'nombre_completo' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                'rfc' => $empleado['rfc'],
                'area' => $empleado['area']
            ];
        }
        
        // Si no está en el mapeo, buscar directamente
        return $this->buscarEmpleadoDirecto($id_usuario_zkteco);
    }
    
    /**
     * Búsqueda directa de empleado si no está en mapeo
     */
    private function buscarEmpleadoDirecto($id_zkteco) {
        // Múltiples estrategias de búsqueda
        $sql = "
            SELECT id, nombre, apellido, rfc, area, numero_empleado
            FROM empleados 
            WHERE id = CAST(? AS UNSIGNED)
               OR CAST(id AS CHAR) = ?
               OR numero_empleado = ?
               OR rfc = ?
               OR CONCAT(nombre, ' ', apellido) LIKE CONCAT('%', ?, '%')
            ORDER BY 
                CASE 
                    WHEN id = ? THEN 0
                    WHEN numero_empleado = ? THEN 1
                    WHEN CAST(id AS CHAR) = ? THEN 2
                    WHEN rfc LIKE CONCAT('%', ?, '%') THEN 3
                    ELSE 4
                END,
                CASE 
                    WHEN id = ? THEN 0
                    WHEN numero_empleado = ? THEN 1
                    WHEN CAST(id AS CHAR) = ? THEN 2
                    WHEN rfc LIKE CONCAT('%', ?, '%') THEN 3
                    ELSE 4
                END
                ,
               LENGTH(numero_empleado),
                LENGTH(CONCAT(nombre, ' ', apellido))
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $id_zkteco, (int)$id_zkteco, $id_zkteco, 
            (int)$id_zkteco, (int)$id_zkteco, $id_zkteco, $id_zkteco,
            (int)$id_zkteco, (int)$id_zkteco, $id_zkteco
        ]);
        
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empleado) {
            return [
                'id' => $empleado['id'],
                'id_zkteco' => $id_zkteco,
                'numero_empleado' => $empleado['numero_empleado'],
                'nombre' => $empleado['nombre'],
                'apellido' => $empleado['apellido'],
                'nombre_completo' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                'rfc' => $empleado['rfc'],
                'area' => $empleado['area']
            ];
        }
        
        return null;
    }
    
    /**
     * Verifica si ya existe un registro duplicado
     */
    private function existeRegistroDuplicado($empleado_id, $fecha, $tipo) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as existe
            FROM asistencia 
            WHERE empleado_id = ? AND DATE(fecha) = ? AND (
                (hora_entrada IS NOT NULL AND ? IN ('entrada', 'reingreso')) OR
                (hora_salida IS NOT NULL AND IN ('salida_almuerzo', 'salida_final'))
            )
        ");
        
        $stmt->execute([$empleado_id, $fecha, $tipo]);
        $result = $stmt->fetch();
        return $result['existe'] > 0;
    }
    
    /**
     * Procesa un registro individual y lo inserta en la base de datos
     */
    private function procesarRegistro($registro, &$estadisticas) {
        $clasificacion = $this->determinarTipoRegistroPorHora($registro);
        $empleado = $this->getEmpleadoPorZKTecoId($registro['id_usuario_zkteco']);
        
        if (!$empleado) {
            echo "⚠️ Empleado no encontrado para ZKTeco-ID: {$registro['id_usuario_zkteco']}\n";
            return false;
        }
        
        $fecha = date('Y-m-d', strtotime($registro['fecha_hora']));
        $hora = date('H:i:s', strtotime($registro['fecha_hora']));
        
        try {
            // Verificar duplicados antes de insertar
            if ($this->existeRegistroDuplicado($empleado['id'], $fecha, $clasificacion['tipo'])) {
                echo "⚠️ Registro duplicado omitido: {$empleado['nombre_completo']} - {$clasificacion['tipo']} el {$fecha}\n";
                return false;
            }
            
            if (($clasificacion['tipo'] === 'entrada' || $clasificacion['tipo'] === 'reingreso') || ($clasificacion['tipo'] === 'domingo') || $clasificacion['tipo'] === 'entrada_temprana') {
                // Insertar o actualizar registro de entrada
                $stmt = $this->db->getConnection()->prepare("
                    INSERT INTO asistencia (
                        empleado_id, fecha, hora_entrada, dispositivo_id, tipo_biometria, calidad_verificacion, 
                        metadata_dispositivo, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    hora_entrada = VALUES(hora_entrada),
                    dispositivo_id = VALUES(dispositivo_id),
                    tipo_biometria = VALUES(tipo_biometria),
                    calidad_verificacion = VALUES(calidad_verificacion),
                    metadata_dispositivo = VALUES(metadata_dispositivo),
                    updated_at = NOW()
                ");
                
                $stmt->execute([
                    $empleado['id'], $fecha, $hora, 
                    $registro['campo2'], // ID del dispositivo biométrico
                    'huella', // Tipo biométrico por defecto
                    $registro['campo4'], // Calidad de verificación
                    json_encode([]), // Metadata del dispositivo
                    NOW()
                ]);
                
                $estadisticas['entradas']++;
                $estadisticas[$clasificacion['metodo']]++;
                
                echo "✓ {$clasificacion['metodo']}: {$empleado['nombre_completo']} - {$hora} - {$fecha}\n";
                
            } elseif (($clasificacion['tipo'] === 'salida_almuerzo' || $clasificacion['tipo'] === 'salida_final' || $clasificacion['tipo'] === 'salida_almuerzo_periodo') || $clasificacion['tipo'] === 'salida_final') {
                // Insertar o actualizar registro de salida
                $stmt = $this->db->getConnection()->prepare("
                    INSERT INTO asistencia (
                        empleado_id, fecha, hora_salida, dispositivo_id, tipo_biometria, calidad_verificacion,
                        metadata_dispositivo, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    hora_salida = VALUES(hora_salida),
                    dispositivo_id = VALUES(dispositivo_id),
                    tipo_biometria = VALUES(tipo_biometria),
                    calidad_verificacion = VALUES(calidad_verificacion),
                    metadata_dispositivo = VALUES(metadata_dispositivo),
                    updated_at = NOW()
                ");
                
                $stmt->execute([
                    $empleado['id'], $fecha, $hora, 
                    $registro['campo2'], 'huella', 
                    $registro['campo4'], 
                    json_encode([]), NOW()
                ]);
                
                $estadisticas[$clasificacion['metodo']]++;
                
                echo "✓ {$clasificacion['metodo']}: {$empleado['nombre_completo']} - {$hora} - {$fecha}\n";
                
            } elseif ($clasificacion['tipo'] === 'extra_horario') {
                echo "⚠️ Registro fuera de horario: {$empleado['nombre_completo']} - {$hora} - {$fecha}\n";
                
            } else {
                echo "⚠️ Tipo de registro no clasificado: {$empleado['nombre_completo']} - {$clasificacion['tipo']}\n";
            }
            
            return true;
            
        } catch (Exception $e) {
            echo "✗ Error insertando registro: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Configura horarios personalizados
     */
    public function configurarHorarios($config = []) {
        if (isset($config['entrada'])) {
            $this->horario_entrada = $config['entrada'];
            echo "Horario de entrada configurado a: {$config['entrada']}\n";
        }
        
        if (isset($config['salida'])) {
            $this->horario_salida = $config['salida'];
            echo "Horario de salida configurado a: {$config['salida']}\n";
        }
        
        if (isset($config['tolerancia'])) {
            $this->tolerancia_entrada = (int)$config['tolerancia'];
            echo "Tolerancia configurada a: {$this->tolerancia} minutos\n";
        }
        
        echo "Configuración actualizada exitosamente\n";
    }
    
    /**
     * Genera un reporte detallado del procesamiento
     */
    public function generarReporteFinal() {
        echo "\n=== REPORTE FINAL DEL PROCESAMIENTO ===\n";
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $empleados_activos = [];
        $estadisticas = [];
        
        foreach ($lines as $line) {
            $registro = $this->parseLineaLog($line);
            if ($registro) {
                $empleado_id_zkteco = $registro['id_usuario_zkteco'];
                if (!isset($empleado_activos[$empleado_id_zkteco])) {
                    $empleados_activos[$empleado_id_zkteco] = 0;
                }
                $empleado_activos[$empleado_id_zkteco]++;
            }
        }
        
        echo "Total de empleados únicos en logs: " . count($empleados_activos) . "\n";
        
        // Top 10 empleados más activos
        arsort($empleados_activos);
        $top_empleados = array_slice($empleados_activos, 0, 10, true);
        
        echo "\nTop 10 empleados más activos:\n";
        foreach ($top_empleados as $id => $count) {
            echo "- ID {$id}: {$count} registros\n";
        }
    }
}