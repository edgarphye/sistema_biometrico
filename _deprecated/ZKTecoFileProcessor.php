<?php
require_once __DIR__ . '/ZKTecoAsistenciaInserterV3.php';

/**
 * Procesador de archivos DAT completos
 */
class ZKTecoFileProcessor {
    private $inserter;
    
    public function __construct() {
        $this->inserter = new ZKTecoAsistenciaInserterV3();
    }
    
    /**
     * Procesa un archivo DAT completo
     * Formato: empleado_id	fecha_hora	tipo	dispositivo_id	accion_zkteco	verificacion
     */
    public function procesarArchivoDAT($ruta_archivo) {
        echo "=== PROCESANDO ARCHIVO DAT ===\n";
        echo "Archivo: $ruta_archivo\n";
        
        if (!file_exists($ruta_archivo)) {
            throw new Exception("Archivo no encontrado: $ruta_archivo");
        }
        
        $lines = file($ruta_archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $total_lines = count($lines);
        
        echo "Total de registros: $total_lines\n\n";
        
        $procesados = 0;
        $exitosos = 0;
        $errores = 0;
        $estadisticas = [
            'entradas' => 0,
            'salidas' => 0,
            'retardos' => 0,
            'duplicados' => 0,
            'incompletos' => 0,
            'completados' => 0
        ];
        
        foreach ($lines as $index => $line) {
            $procesados++;
            
            if (($procesados % 100) == 0) {
                $porcentaje = round(($procesados / $total_lines) * 100, 2);
                echo "Progreso: $porcentaje% ($procesados/$total_lines)\n";
            }
            
            try {
                $resultado = $this->procesarLineaDAT(trim($line));
                
                $this->actualizarEstadisticas($resultado, $estadisticas);
                
                if ($resultado['success']) {
                    $exitosos++;
                    if (strpos($resultado['tipo'], 'completada') !== false) {
                        $estadisticas['completados']++;
                    }
                    echo "✓ [{$procesados}] {$resultado['message']}\n";
                } else {
                    if (strpos($resultado['tipo'], 'duplicado') !== false) {
                        $estadisticas['duplicados']++;
                    } elseif (strpos($resultado['tipo'], 'requiere') !== false) {
                        $estadisticas['incompletos']++;
                    } else {
                        $errores++;
                    }
                    echo "⚠ [{$procesados}] {$resultado['message']}\n";
                }
                
            } catch (Exception $e) {
                $errores++;
                echo "✗ [{$procesados}] Error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n=== RESUMEN DEL PROCESAMIENTO ===\n";
        echo "Total de registros: $total_lines\n";
        echo "Procesados: $procesados\n";
        echo "Exitosos: $exitosos\n";
        echo "Errores: $errores\n";
        echo "Duplicados: {$estadisticas['duplicados']}\n";
        echo "Incompletos: {$estadisticas['incompletos']}\n";
        echo "Registros completados: {$estadisticas['completados']}\n";
        echo "Entradas registradas: {$estadisticas['entradas']}\n";
        echo "Salidas registradas: {$estadisticas['salidas']}\n";
        echo "Retardos detectados: {$estadisticas['retardos']}\n";
        
        return [
            'total' => $total_lines,
            'procesados' => $procesados,
            'exitosos' => $exitosos,
            'errores' => $errores,
            'duplicados' => $estadisticas['duplicados'],
            'estadisticas' => $estadisticas
        ];
    }
    
    /**
     * Procesa una línea individual del archivo DAT
     */
    private function procesarLineaDAT($linea) {
        $parts = explode("\t", trim($linea));
        
        if (count($parts) < 6) {
            throw new Exception("Lnea con formato incorrecto: $linea");
        }
        
        // Estructura DAT: 2603	2025-01-21 15:00:07	1	0	1	0
        $empleado_id = trim($parts[0]);
        $fecha_hora = trim($parts[1]);
        $tipo = trim($parts[2]);
        $dispositivo_id = trim($parts[3]);
        $accion_zkteco = trim($parts[4]);
        $verificacion = trim($parts[5]);
        
        // Separar fecha y hora
        $datetime_parts = explode(' ', $fecha_hora);
        if (count($datetime_parts) != 2) {
            throw new Exception("Formato de fecha/hora incorrecto: $fecha_hora");
        }
        
        $fecha = $datetime_parts[0];
        $hora_registro = $datetime_parts[1];
        
        // Procesar el registro completo
        return $this->inserter->insertarRegistroCompleto(
            $empleado_id,
            $fecha,
            $hora_registro,
            $dispositivo_id,
            $empleado_id, // zk_empleado_id es el mismo en este caso
            $accion_zkteco,
            $verificacion,
            $tipo // resultado
        );
    }
    
    /**
     * Actualiza estadísticas según el resultado
     */
    private function actualizarEstadisticas($resultado, &$estadisticas) {
        if (strpos($resultado['tipo'], 'entrada') !== false || strpos($resultado['tipo'], 'completada') !== false) {
            $estadisticas['entradas']++;
        } elseif (strpos($resultado['tipo'], 'salida') !== false) {
            $estadisticas['salidas']++;
        }
        
        // Contar diferentes tipos de duplicados
        if (strpos($resultado['tipo'], 'duplicado') !== false) {
            $estadisticas['duplicados']++;
        } elseif (strpos($resultado['tipo'], 'requiere') !== false) {
            $estadisticas['incompletos']++;
        }
        
        // Verificar si se registró retardo
        if (isset($resultado['datos_calculados']) && $resultado['datos_calculados']['es_retardo']) {
            $estadisticas['retardos']++;
        }
    }
}

// Ejemplo de uso
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $processor = new ZKTecoFileProcessor();
        
        // Ruta del archivo DAT (ajustar según necesidad)
        $archivo_dat = __DIR__ . '/data/1_attlog.dat';
        
        if (isset($argv[1])) {
            $archivo_dat = $argv[1];
        }
        
        $resultado = $processor->procesarArchivoDAT($archivo_dat);
        
        echo "\nProcesamiento completado exitosamente.\n";
        
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}