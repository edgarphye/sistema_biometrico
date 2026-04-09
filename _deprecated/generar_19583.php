<?php
// Generar archivo DAT con exactamente 19,583 registros
echo "=== GENERANDO ARCHIVO 19,583 REGISTROS ===\n";

$empleadoIds = [1, 2, 3, 4, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55];
$fechas = [];
$registros = [];
$registroId = 1;

// Generar fechas para 90 días (3 meses)
for ($dia = 1; $dia <= 90; $dia++) {
    $fecha = sprintf('2024-08-%02d', $dia);
    if ($dia > 31) {
        $fecha = sprintf('2024-09-%02d', $dia - 31);
    }
    if ($dia > 61) {
        $fecha = sprintf('2024-10-%02d', $dia - 61);
    }
    $fechas[] = $fecha;
}

echo "📅 Generando registros para " . count($fechas) . " días\n";
echo "👥 Para " . count($empleadoIds) . " empleados\n";
echo "📊 Total esperado: " . (count($fechas) * count($empleadoIds) * 2) . " registros\n";

foreach ($fechas as $fecha) {
    foreach ($empleadoIds as $empId) {
        // Skip algunos días aleatorios para simular ausencias
        if (rand(1, 100) <= 5) continue; // 5% de ausencia
        
        // Generar entrada (siempre hay entrada cuando trabajan)
        $horaEntrada = sprintf('%02d:%02d:%02d', 
            rand(6, 9),           // 6-9 AM
            rand(0, 59), 
            rand(0, 59)
        );
        
        // Generar salida (siempre hay salida cuando trabajan)
        $horaSalida = sprintf('%02d:%02d:%02d', 
            rand(17, 20),         // 5-8 PM
            rand(0, 59), 
            rand(0, 59)
        );
        
        // Agregar registro de entrada
        $registros[] = "{$empId}\t{$fecha}\t{$horaEntrada}\t0";
        $registroId++;
        
        // Agregar registro de salida  
        $registros[] = ($empId + 100) . "\t{$fecha}\t{$horaSalida}\t1";
        $registroId++;
        
        // Detener si alcanzamos el objetivo
        if ($registroId > 19583) break 2;
    }
}

// Tomar exactamente 19,583 registros
$registrosFinales = array_slice($registros, 0, 19583);

// Escribir archivo
$contenido = implode("\n", $registrosFinales);
file_put_contents('asistencia_19583.DAT', $contenido);

echo "✅ Archivo generado: asistencia_19583.DAT\n";
echo "📊 Total registros: " . count($registrosFinales) . "\n";
echo "📏 Tamaño: " . number_format(strlen($contenido) / 1024, 2) . " KB\n";

// Mostrar muestra
echo "\n📋 Primeros 10 registros:\n";
foreach (array_slice($registrosFinales, 0, 10) as $i => $reg) {
    echo sprintf("[%2d] %s\n", $i, $reg);
}

echo "\n📋 Últimos 10 registros:\n";
foreach (array_slice($registrosFinales, -10) as $i => $reg) {
    echo sprintf("[%2d] %s\n", count($registrosFinales) - 10 + $i, $reg);
}

// Análisis de datos generados
echo "\n📊 ANÁLISIS DE DATOS GENERADOS:\n";
$empleadosEnArchivo = array_unique(array_map(function($reg) {
    $parts = explode("\t", $reg);
    return $parts[0] <= 100 ? $parts[0] : $parts[0] - 100;
}, $registrosFinales));

$fechasEnArchivo = array_unique(array_map(function($reg) {
    $parts = explode("\t", $reg);
    return $parts[1];
}, $registrosFinales));

$entradas = array_filter($registrosFinales, function($reg) {
    $parts = explode("\t", $reg);
    return $parts[3] == '0';
});

$salidas = array_filter($registrosFinales, function($reg) {
    $parts = explode("\t", $reg);
    return $parts[3] == '1';
});

echo "   👥 Empleados únicos: " . count($empleadosEnArchivo) . "\n";
echo "   📅 Fechas únicas: " . count($fechasEnArchivo) . "\n";
echo "   🚪 Total entradas: " . count($entradas) . "\n";
echo "   🚪 Total salidas: " . count($salidas) . "\n";

echo "\n🎉 Archivo listo para procesamiento masivo\n";
?>