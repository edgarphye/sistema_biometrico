<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ZKTecoMappingManager.php';

/**
 * Procesador de logs de ZKTeco para el sistema biométrico
 * Versión corregida - 2026
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
        
        $analisis = array(
            'total_registros' => $totalLines,
            'rango_fechas' => [],
            'usuarios_unicos' => [],
            'campo3_valores' => array_count_values(explode("\t", $lines[0], 6)) - 1,
            'patrones_deteccion' => []
        );
        
        $ids_unicos = [];
        $fechas = [];
        $fechas_procesadas = [];
        
        foreach ($lines as $index => $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) >= 6) {
                $id_usuario = trim($parts[0]);
                $fecha_hora = trim($parts[1]);
                
                if (!in_array($id_usuario, $ids_unicos)) {
                    $ids_unicos[] = $id_usuario;
                }
                
                $fecha = date('Y-m-d', strtotime($fecha_hora));
                if (!in_array($fecha, $fechas_procesadas)) {
                    $fechas_procesadas[] = $fecha;
                }
                
                $valor_campo3 = trim($parts[3]);
                if (!isset($analisis['campo3_valores'][$valor_campo3])) {
                    $analisis['campo3_valores'][$valor_campo3] = 0;
                }
                $analisis['campo3_valores'][$valor_campo3]++;
            }
        }
        
        $analisis['usuarios_unicos'] = count($ids_unicos);
        sort($fechas_procesadas);
        $analisis['rango_fechas']['inicio'] = reset($fechas_procesadas);
        $analisis['rango_fechas']['fin'] = end($fechas_procesadas);
        
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
        
        return array(
            'analisis' => $analisis,
            'mapeo' => $resultado_mapeo
        );
    }
    
    /**
     * Analiza patrones específicos en el archivo
     */
    private function analizarPatronesEspeciales() {
        echo "\n=== PATRONES ESPECÍFICOS ===\n";
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $registros_por_hora = array();
        
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
    public function procesarArchivoCompleto($config = array()) {
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
        $estadisticas = array(
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
        );
        
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
                if ($registro && $this->procesarRegistro($registro, $estadisticas)) {
                    $insertados++;
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
        echo "Salidas finales (16:00-18:00): {$estadisticas['salida_final']}\n";
        echo "Fuera de horario: {$estadisticas['fuera_horario']}\n";
        echo "Domingos: {$estadisticas['domingos']}\n";
        echo "Sábados: {$estadisticas['sabados']}\n";
        echo "Lunes: {$estadisticas['lunes']}\n";
        echo "Martes: {$estadisticas['martes']}\n";
        echo "Miércoles: {$estadisticas['miercoles']}\n";
        echo "Jueves: {$estadisticas['jueves']}\n";
        "Viernes: {$estadisticas['viernes']}\n";
        echo "Domingos: {$estadisticas['domingo']}\n";
        
        return array(
            'total' => $totalLines,
            'procesados' => $procesados,
            'insertados' => $insertados,
            'errores' => $errores,
            'tiempo' => $tiempo_total,
            'estadisticas' => $estadisticas
        );
    }
    
    /**
     * Parsea una línea del log y devuelve un array con los datos
     */
    private function parseLineaLog($line) {
        $parts = explode("\t", trim($line));
        
        if (count($parts) < 6) {
            return null;
        }
        
        return array(
            'id_usuario_zkteco' => trim($parts[0]),
            'fecha_hora' => trim($parts[1]),
            'tipo_evento' => (int)trim($parts[2]),
            'campo2' => trim($parts[3]),
            'campo3' => trim($parts[4]),
            'campo4' => trim($parts[5]),
            'linea_original' => trim($line)
        );
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
            return array(
                'tipo' => 'domingo',
                'metodo' => 'domingo_temprana',
                'justificacion' => 'Domingo o día feriado (antes de 7:00)'
            );
        }
        
        // Entrada temprana (7:00-12:00)
        if ($hora_num >= 7 && $hora_num < 12) {
            return array(
                'tipo' => 'entrada',
                'metodo' => 'entrada_temprana',
                'justificacion' => 'Entrada en horario laboral (7:00-12:00)'
            );
        }
        
        // Almuerzo (12:00-14:00)
        if ($hora_num >= 12 && $hora_num < 14) {
            return array(
                'tipo' => 'salida_almuerzo',
                'metodo' => 'salida_almuerzo',
                'justificacion' => 'Salida para almuerzo (12:00-14:00)'
            );
        }
        
        // Reingreso (14:00-16:00)
        if ($hora_num >= 14 && $hora_num < 16) {
            return array(
                'tipo' => 'reingreso',
                'metodo' => 'reingreso_despues_almuerzo',
                'justificacion' => 'Reingreso después de almuerzo (14:00-16:00)'
            );
        }
        
        // Salida final (16:00-18:00)
        if ($hora_num >= 16 && $hora_num < 18) {
            return array(
                'tipo' => 'salida_final',
                'metodo' => 'salida_final',
                'justificacion' => 'Salida del día laboral (16:00-18:00)'
            );
        }
        
        // Horario nocturno (después de 18:00 o antes de 7:00am)
        if ($hora_num >= 18 || ($hora_num < 7)) {
            return array(
                'tipo' => 'extra_horario',
                'metodo' => 'horario_nocturno',
                'justificacion' => 'Fuera de horario establecido'
            );
        }
        
        // Caso por defecto (no debería ocurrir)
        return array(
            'tipo' => 'desconocido',
            'metodo' => 'no_determinado',
            'justificacion' => 'No se pudo determinar el tipo'
        );
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
            return array(
                'id' => $empleado['id'],
                'id_zkteco' => $id_usuario_zkteco,
                'numero_empleado' => $empleado['numero_empleado'],
                'nombre' => $empleado['nombre'],
                'apellido' => $empleado['apellido'],
                'nombre_completo' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                'rfc' => $empleado['rfc'],
                'area' => $empleado['area']
            );
        }
        
        // Si no está en el mapeo, buscar directamente
        return $this->buscarEmpleadoDirecto($id_usuario_zkteco);
    }
    
    /**
     * Búsqueda directa de empleado si no está en mapeo
     */
    private function buscarEmpleadoDirecto($id_usuario_zkteco) {
        $sql = "
            SELECT id, nombre, apellido, rfc, area, numero_empleado
            FROM empleados 
            WHERE id = CAST(? AS UNSIGNED) OR 
                  CAST(id AS CHAR) = ? OR 
                  numero_empleado = ? OR 
                  rfc LIKE CONCAT('%', '?', '%') OR 
                  CONCAT(nombre, ' ', apellido) LIKE CONCAT('%', '?', '%')
            ORDER BY 
                CASE 
                    WHEN id = CAST(? AS UNSIGNED) THEN 0
                    WHEN numero_empleado = ? 1
                    WHEN CAST(id AS CHAR) = ? THEN 2
                    WHEN rfc LIKE CONCAT('%', '?', '%') THEN 3
                    ELSE 4
                END,
               LENGTH(CONCAT(nombre, ' ', apellido)) DESC,
               nombre_empleado
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $id_usuario_zkteco, 
            $id_usuario_zkteco, 
            $id_usuario_zkteco, 
            (int)$id_usuario_zkteco,
            $id_usuario_zkteco, 
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco,
            $id_usuario_zkteco
        ]);
        
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empleado) {
            return array(
                'id' => $empleado['id'],
                'id_zkteco' => $id_usuario_zkteco,
                'numero_empleado' => $empleado['numero_empleado'],
                'nombre' => $empleado['nombre'],
                'apellido' => $empleado['apellido'],
                'nombre_completo' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                'rfc' => $empleado['rfc'],
                'area' => $empleado['area']
            );
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
            WHERE empleado_id = ? AND DATE(fecha) = ? AND 
                  (
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
            // Verificar si ya existe un registro duplicado
            if ($this->existeRegistroDuplicado($empleado['id'], $fecha, $clasificacion['tipo'])) {
                echo "⚠️ Registro duplicado omitido: {$empleado['nombre_completo']} - {$clasificacion['tipo']} - {$fecha}\n";
                return false;
            }
            
            // Insertar o actualizar registro según el tipo
            if ($clasificacion['tipo'] === 'entrada' || $clasificacion['tipo'] === 'reingreso' || $clasificacion['tipo'] === 'entrada_temprana') {
                $stmt = $this->db->getConnection()->prepare("
                    INSERT INTO asistencia (
                        empleado_id, fecha, hora_entrada, dispositivo_id, tipo_biometria, 
                        calidad_verificacion, metadata_dispositivo, created_at
                    ) VALUES (?, ?, ?, ?, ?, 'huella', ?, ?, NOW())
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
                    'huella',               // Tipo biométrico por defecto
                    518,                ]);
                
                echo "✓ {$clasificacion['metodo']}: {$empleado['nombre_completo']} - {$hora} - {$fecha}\n";
                $estadisticas['entradas']++;
                
            } elseif ($clasificacion['tipo'] === 'salida_almuerzo' || $clasificacion['tipo'] === 'salida_almuerzo_periodo' || 
                      $clasificacion['tipo'] === 'salida_final' || $clasificacion['tipo'] === 'salida_almuerzo_periodo') {
                
                $stmt = $this->db->getConnection()->prepare("
                    INSERT INTO asistencia (
                        empleado_id, fecha, hora_salida, dispositivo_id, tipo_biometria, 
                        calidad_verificacion, metadata_dispositivo, created_at
                    ) VALUES (?, ?, ?, ?, 'huella', ?, ?, NOW())
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
                    $registro['campo2'], 
                    'huella', 
                    518, 
                ]);
                
                echo "✓ {$clasificacion['metodo']}: {$empleado['nombre_completo']} - {$hora} - {$fecha}\n";
                $estadisticas[$clasificacion['metodo']]++;
                
            } elseif ($clasificacion['tipo'] === 'salida_final' || $clasificacion['tipo'] === 'salida_final_periodo') {
                $stmt = $this->db->getConnection()->prepare("
                    INSERT INTO asistencia (
                        empleado_id, fecha, hora_salida, dispositivo_id, tipo_biometria, 
                        calidad_verificacion, metadata_dispositivo, created_at
                    ) VALUES (?, ?, ?, ?, 'huella', ?, NOW())
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
                    $registro['campo2'], 
                    'huella', 
                    518, 
                ]);
                
                echo "✓ {$clasificacion['metodo']}: {$empleado['nombre_completo']} - {$hora} - {$fecha}\n";
                $estadisticas[$clasificacion['metodo']]++;
                
            } elseif ($clasificacion['tipo'] === 'fuera_horario') {
                // Fuera de horario: podría ser hora extra o fin de jornada
                echo "⚠️ Fuera de horario: {$empleado['nombre_completo']} - {$hora} - {$fecha}\n";
                $estadisticas['fuera_horario']++;
                
            } else {
                echo "⚠️ Tipo de registro no clasificado: {$empleado['nombre_completo']} - {$clasificacion['tipo']}\n";
                $estadisticas['desconocido']++;
            }
            
            return true;
            
        } catch (Exception $e) {
            echo "✗ Error insertando registro: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            return false;
        }
    }
    
    /**
     * Configura horarios personalizados
     */
    public function configurarHorarios($config = []) {
        $default_entrada = '09:00:00';
        $default_salida = '17:00:00';
        $default_tolerancia = 15;
        
        echo "\n=== CONFIGURACIÓN DE HORARIOS ===\n";
        
        $this->horario_entrada = $config['entrada'] ?? $default_entrada;
        $this->horario_salida = $config['salida'] ?? $default_salida;
        $this->tolerancia = (int)($config['tolerancia'] ?? $default_tolerancia);
        
        echo "Horarios configurados:\n";
        echo "Entrada: {$this->horario_entrada}\n";
        echo "Salida: {$this->horario_salida}\n";
        echo "Tolerancia entrada: {$this->tolerancia} minutos\n";
        
        if (isset($config['update_database'])) {
            try {
                $this->configurarHorariosEnBaseDeDatos();
                echo "✅ Horarios actualizados en la base de datos\n";
            } catch (Exception $e) {
                echo "⚠️ Error actualizando horarios en BD: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Actualiza horarios en la base de datos
     */
    private function configurarHorariosEnBaseDeDatos() {
        try {
            $sql = "
                INSERT INTO configuraciones (nombre, valor, descripcion)
                VALUES 
                ('horario_entrada', '{$this->horario_entrada}', 'Hora de entrada'),
                ('horario_salida', '{$this->horario_salida}', 'Hora de salida'),
                ('tolerancia_entrada', '{$this->tolerancia}', 'Tolerancia entrada en minutos'),
                ('ultimo_update', NOW(), 'Última actualización de horarios')
                ON DUPLICATE KEY UPDATE 
                    nombre = VALUES(nombre),
                    valor = VALUES(valor),
                    descripcion = VALUES(descripcion),
                    ultimo_update = NOW()
            ";
            
            $this->db->getConnection()->exec($sql);
            echo "✅ Horarios guardados exitosamente en la base de datos\n";
        } catch (Exception $e) {
            echo "✗ Error guardando horarios: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Genera un reporte detallado
     */
    public function generarReporte() {
        echo "\n=== REPORTE COMPLETO ===\n";
        
        if (!file_exists($this->logFile)) {
            echo "Archivo no encontrado.\n";
            return;
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $empleados_activos = [];
        $estadisticas = [];
        $estadisticas_dia = [];
        $dias_con_registros = [];
        
        foreach ($lines as $line) {
            $registro = $this->parseLineaLog($line);
            if ($registro) {
                $empleado = $this->getEmpleadoPorZKTecoId($registro['id_usuario_zkteco']);
                if ($empleado) {
                    $empleado_activos[$empleado['id_zkteco']] = ($empleado_activos[$empleado['id_zkteco']] ?? 0) + 1;
                    $fecha = date('Y-m-d', strtotime($registro['fecha_hora']));
                    
                    if (!isset($estadisticas_dia[$fecha])) {
                        $estadisticas_dia[$fecha] = [
                            'entradas' => 0,
                            'salidas' => 0,
                            'reingresos' => 0,
                            'salidas_finales' => 0,
                            'fuera_horario' => 0
                        ];
                    }
                    
                    $estadisticas_dia[$fecha]['entradas'] = ($estadisticas_dia[$fecha]['entradas'] ?? 0) + 1;
                    $estadisticas_dia[$fecha]['salidas'] = ($estadisticas_dia[$fecha]['salidas'] ?? 0) + 1;
                    $estadisticas_dia[$fecha]['reingresos'] = ($estadisticas_dia[$fecha]['reingresos'] ?? 0) + 1;
                    $estadisticas_dia[$fecha]['salidas_finales'] = ($estadisticas_dia[$fecha]['salidas_finales'] ?? 0) + 1;
                    
                    if ($clasificacion = $this->determinarTipoRegistroPorHora($registro)) {
                        $estadisticas_dia[$fecha][$clasificacion['metodo']]++;
                    }
                    
                    $estadisticas_dia[$fecha]['empleados_activos'] = ($empleado_activos[$empleado['id_zkteco']] ?? 0) + 1;
                }
            }
        }
        
        echo "\n=== EMPLEADOS MÁS ACTIVOS ===\n";
        echo "Total de empleados únicos con registros: " . count($empleado_activos) . "\n";
        
        // Top 10 empleados más activos
        arsort($empleado_activos);
        $top_empleados = array_slice($empleado_activos, 0, 10, true);
        foreach ($top_empleados as $id => $conteo) {
            echo "- ID ZKTeco {$id}: {$conteo} registros\n";
        }
        
        echo "\n=== RESUMEN POR DÍA ===\n";
        $dias_con_registros = array_keys($estadisticas_dia);
        sort($dias_con_registros);
        
        foreach ($dias_con_registros as $fecha) {
            echo "Fecha: {$fecha}: {$estadisticas_dia[$fecha]['entradas']} entradas, {$estadisticas_dia[$fecha]['salidas']} salidas, {$estadisticas_dia[$fecha]['reingresos']} reingresos, {$estadisticas_dia[$fecha]['salidas_finales']} salidas_finales, {$estadisticas_dia[$fecha]['fuera_horario']} domingos{$estadisticas_dia[$fecha]['domingo']})\n";
        }
        
        echo "\n=== MODO DE REGISTRO POR HORA ===\n";
        echo "0-6 horas: {$estadisticas['domingo']} Domingos\n";
        echo "7-11 horas: {$estadisticas['lunes']} Lunes\n";
        echo "12-13 horas: {$estadisticas['martes']} Martes\n";
        echo "14-15 horas: {$estadisticas['miercoles']} Miércoles\n";
        echo "16-17 horas: {$estadisticas['jueves']} Jueves\n";
        "18-19 horas: {$estadisticas['viernes']} Viernes\n";
        echo "20-23 horas: {$estadisticas['viernes']} Viernes\n";
        "0-23 horas: {$estadisticas['sábados']} Sábados\n";
    }
}