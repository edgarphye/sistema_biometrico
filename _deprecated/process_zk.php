<?php
// Mapeo ZKTeco-ID a Empleado-ID
$zkMapping = [
    1 => 1234,  // Juan Pérez
    2 => 5678,  // María García
    3 => 9012,  // Carlos López
    4 => 3456,  // Ana Martínez
    5 => 7890,  // Luis Rodríguez
    15 => 1111, // Carmen Sánchez
    21 => 2222, // Pedro Díaz
    26 => 3333, // Laura Fernández
    28 => 4444, // Miguel Torres
    31 => 5555, // Isabel Ruiz
    32 => 6666, // Javier Herrera
    36 => 7777, // Elena Morales
    37 => 8888, // Roberto Vargas
    39 => 9999, // Patricia Castro
    46 => 1010, // Diego Ortega
    54 => 1212, // Silvia Navarro
    55 => 1313, // Andrés Guerrero
    57 => 1414, // Martha Ríos
    59 => 1515, // Eduardo Mendoza
    62 => 1616, // Claudia Flores
];

// Procesar registro según reglas
function processRecord($record) {
    global $zkMapping;
    
    $empleadoId = $record['empleado_id'];
    $fecha = $record['fecha'];
    $hora = $record['hora'];
    $accion = $record['accion'];
    
    // Regla 1: Sábados y domingos omitir todos los registros
    $dayOfWeek = date('w', strtotime($fecha));
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        return null;
    }
    
    // Regla 2: Solo insertar registros de acción "0" (Check-in)
    if ($accion != 0) {
        return null;
    }
    
    // Regla 3: Si el tiempo es entre 00:00:00 y 04:00:00, marcar como día anterior
    $fechaFinal = $fecha;
    if ($hora >= '00:00:00' && $hora <= '04:00:00') {
        $fechaFinal = date('Y-m-d', strtotime($fecha . ' -1 day'));
    }
    
    return [
        'empleado_id' => $zkMapping[$empleadoId] ?? null,
        'fecha' => $fechaFinal,
        'hora' => $hora,
        'accion' => $accion
    ];
}

// Simulación de base de datos
class DatabaseSimulator {
    private $registros = [];
    
    public function insert($asistencia) {
        $this->registros[] = $asistencia;
        echo "✓ Insertado: Empleado {$asistencia['empleado_id']} - {$asistencia['fecha']} {$asistencia['hora']}\n";
    }
    
    public function getEstadisticas() {
        $total = count($this->registros);
        $porEmpleado = [];
        
        foreach ($this->registros as $reg) {
            $empId = $reg['empleado_id'];
            $porEmpleado[$empId] = ($porEmpleado[$empId] ?? 0) + 1;
        }
        
        return [
            'total' => $total,
            'porEmpleado' => $porEmpleado,
            'empleados' => count($porEmpleado)
        ];
    }
}

// Procesador principal
class ZKTecoFileProcessor {
    private $db;
    
    public function __construct() {
        $this->db = new DatabaseSimulator();
    }
    
    private function parseLine($line) {
        $parts = explode("\t", trim($line));
        if (count($parts) >= 3) {
            return [
                'empleado_id' => (int)$parts[0],
                'fecha' => $parts[1],
                'hora' => $parts[2],
                'accion' => (int)($parts[3] ?? 0)
            ];
        }
        return null;
    }
    
    public function processFile($filePath) {
        echo "🔄 Iniciando procesamiento del archivo...\n";
        
        if (!file_exists($filePath)) {
            echo "❌ Archivo no encontrado: $filePath\n";
            return false;
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $procesados = 0;
        $omitidos = 0;
        $sinMapeo = 0;
        
        foreach ($lines as $line) {
            $record = $this->parseLine($line);
            if (!$record) continue;
            
            $procesados++;
            
            $processed = processRecord($record);
            
            if (!$processed) {
                $omitidos++;
                continue;
            }
            
            if (!$processed['empleado_id']) {
                $sinMapeo++;
                echo "⚠️  ZKTeco-ID {$record['empleado_id']} sin mapeo a empleado\n";
                continue;
            }
            
            $this->db->insert($processed);
        }
        
        echo "\n📊 Estadísticas del procesamiento:\n";
        echo "Total líneas procesadas: $procesados\n";
        echo "Registros omitidos (reglas): $omitidos\n";
        echo "Sin mapeo ZKTeco-ID: $sinMapeo\n";
        echo "Registros insertados: " . count($this->db->getEstadisticas()['porEmpleado']) . "\n";
        
        $stats = $this->db->getEstadisticas();
        echo "Empleados únicos: {$stats['empleados']}\n";
        
        echo "\n📋 Registros por empleado:\n";
        arsort($stats['porEmpleado']);
        foreach ($stats['porEmpleado'] as $empId => $count) {
            echo "  Empleado $empId: $count registros\n";
        }
        
        return true;
    }
}

// Ejecución principal
$processor = new ZKTecoFileProcessor();
$filePath = __DIR__ . '/20241106.TXT';

$result = $processor->processFile($filePath);

if ($result) {
    echo "\n✅ Proceso completado exitosamente.\n";
} else {
    echo "\n❌ Error en el proceso.\n";
}
?>