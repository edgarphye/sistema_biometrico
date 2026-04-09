<?php
require_once __DIR__ . '/models/ZKTecoLogProcessor.php';

/**
 * Interfaz de comandos para procesamiento de logs ZKTeco
 */
class ZKTecoCLI {
    private $processor;
    
    public function __construct() {
        $this->processor = new ZKTecoLogProcessor();
    }
    
    public function ejecutar($comando, $parametros = []) {
        switch ($comando) {
            case 'analizar':
                $this->analizarArchivo();
                break;
                
            case 'procesar':
                $this->procesarArchivo($parametros);
                break;
                
            case 'reporte':
                $this->generarReporte();
                break;
                
            case 'configurar':
                $this->configurarHorarios($parametros);
                break;
                
            case 'ayuda':
                $this->mostrarAyuda();
                break;
                
            default:
                echo "Comando desconocido: {$comando}\n\n";
                $this->mostrarAyuda();
        }
    }
    
    private function analizarArchivo() {
        echo "=== ANÁLISIS DE ARCHIVO DE LOGS ZKTeco ===\n";
        $this->processor->generarReporte();
    }
    
    private function procesarArchivo($parametros) {
        $archivo = $parametros['archivo'] ?? null;
        
        echo "=== PROCESAMIENTO DE ARCHIVO ZKTeco ===\n";
        
        try {
            if ($archivo) {
                $this->processor = new ZKTecoLogProcessor($archivo);
            }
            
            $resultado = $this->processor->procesarArchivoCompleto();
            
            echo "\n=== RESUMEN DE PROCESAMIENTO ===\n";
            echo "Registros totales: {$resultado['total']}\n";
            echo "Registros procesados: {$resultado['procesados']}\n";
            echo "Registros insertados: {$resultado['insertados']}\n";
            echo "Errores encontrados: {$resultado['errores']}\n";
            
            if ($resultado['insertados'] > 0) {
                echo "\n✓ Proceso completado exitosamente\n";
                echo "✓ {$resultado['insertados']} registros insertados en la base de datos\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Error durante el procesamiento: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
    
    private function generarReporte() {
        $this->processor->generarReporte();
    }
    
    private function configurarHorarios($parametros) {
        $entrada = $parametros['entrada'] ?? '09:00:00';
        $salida = $parametros['salida'] ?? '17:00:00';
        $tolerancia = $parametros['tolerancia'] ?? 15;
        
        $this->processor->configurarHorarios($entrada, $salida, $tolerancia);
        
        echo "=== CONFIGURACIÓN DE HORARIOS ===\n";
        echo "Hora de entrada: {$entrada}\n";
        echo "Hora de salida: {$salida}\n";
        echo "Tolerancia (min): {$tolerancia}\n";
        echo "✓ Configuración actualizada\n";
    }
    
    private function mostrarAyuda() {
        echo "=== ZKTeco LOG PROCESSOR ===\n";
        echo "\nUso:\n";
        echo "  php zk_processor.php <comando> [opciones]\n\n";
        echo "Comandos disponibles:\n";
        echo "  analizar                      - Analiza el archivo de logs\n";
        echo "  procesar                      - Procesa el archivo completo\n";
        echo "  reporte                       - Genera un reporte detallado\n";
        echo "  configurar                    - Configura horarios\n";
        echo "  ayuda                         - Muestra esta ayuda\n\n";
        echo "Opciones para 'configurar':\n";
        echo "  --entrada=HH:MM:SS           - Hora de entrada (defecto: 09:00:00)\n";
        echo "  --salida=HH:MM:SS            - Hora de salida (defecto: 17:00:00)\n";
        echo "  --tolerancia=N                - Tolerancia en minutos (defecto: 15)\n\n";
        echo "Opciones para 'procesar':\n";
        echo "  --archivo=/ruta/al/archivo   - Ruta al archivo .attlog\n\n";
        echo "Ejemplos:\n";
        echo "  php zk_processor.php analizar\n";
        echo "  php zk_processor.php procesar --archivo=/path/to/attlog.dat\n";
        echo "  php zk_processor.php configurar --entrada=08:30:00 --salida=16:30:00 --tolerancia=10\n";
    }
}

// Ejecución principal
if (basename(__FILE__) === 'zk_cli.php') {
    // Obtener parámetros de línea de comandos
    $comando = $argv[1] ?? 'ayuda';
    
    $parametros = [];
    for ($i = 2; $i < $argc; $i++) {
        if (strpos($argv[$i], '--') === 0) {
            $parts = explode('=', $argv[$i], 2);
            if (count($parts) === 2) {
                $parametros[str_replace('--', '', $parts[0])] = $parts[1];
            }
        }
    }
    
    $cli = new ZKTecoCLI();
    $cli->ejecutar($comando, $parametros);
}