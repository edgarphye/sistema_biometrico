<?php
// Generar archivo DAT con exactamente 19,583 registros
echo "=== GENERANDO ARCHIVO 19,583 REGISTROS (INTENTO 2) ===\n";

$empleadoIds = range(1, 100); // 100 empleados
$fechas = [];
$registros = [];
$registroId = 1;

// Generar fechas para 260 días (aprox 1 año sin fines de semana)
for ($dia = 0; $dia < 260; $dia++) {
    $fecha = date('Y-m-d', strtotime("2024-01-01 + $dia days"));
    $diaSemana = date('N', strtotime($fecha));
    if ($diaSemana < 6) { // Solo días de semana
        $fechas[] = $fecha;
    }
}

echo "📅 Generando registros para " . count($fechas) . " días\n";
echo "👥 Para " . count($empleadoIds) . " empleados\n";
echo "📊 Total posible: " . (count($fechas) * count($empleadoIds) * 2) . " registros\n";

foreach ($fechas as $fecha) {
    foreach ($empleadoIds as $empId) {
        // Skip fines de semana (más probable en asistencia real)
        $diaSemana = date('N', strtotime($fecha));
        if ($diaSemana >= 6) {
            continue; // Skip sábados y domingos
        }
        
        // Skip algunos días aleatorios para simular ausencias y vacaciones
        if (rand(1, 100) <= 15) continue; // 15% de ausencia
        
        // Generar entrada
        $horaEntrada = sprintf('%02d:%02d:%02d', 
            rand(6, 9),           // 6-9 AM
            rand(0, 59), 
            rand(0, 59)
        );
        
        // Generar salida
        $horaSalida = sprintf('%02d:%02d:%02d', 
            rand(17, 20),         // 5-8 PM
            rand(0, 59), 
            rand(0, 59)
        );
        
        // Agregar registro de entrada
        $registros[] = $empId . "\t{$fecha}\t{$horaEntrada}\t0";
        $registroId++;
        
        // Agregar registro de salida  
        $registros[] = ($empId + 1000) . "\t{$fecha}\t{$horaSalida}\t1";
        $registroId++;
        
        // Detener si alcanzamos el objetivo exacto
        if ($registroId > 19583) {
            break 2;
        }
    }
}

// Ajustar a exactamente 19,583 registros
$registrosFinales = array_slice($registros, 0, 19583);

// Escribir archivo
$contenido = implode("\n", $registrosFinales);
file_put_contents('asistencia_19583_final.DAT', $contenido);

echo "✅ Archivo generado: asistencia_19583_final.DAT\n";
echo "📊 Total registros: " . count($registrosFinales) . "\n";
echo "📏 Tamaño: " . number_format(strlen($contenido) / 1024, 2) . " KB\n";

// Análisis de datos generados
echo "\n📊 ANÁLISIS DE DATOS GENERADOS:\n";
$empleadosEnArchivo = array_unique(array_map(function($reg) {
    $parts = explode("\t", $reg);
    $zkId = (int)$parts[0];
    return $zkId <= 1000 ? $zkId : $zkId - 1000;
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
echo "   📊 Promedio por empleado: " . round(count($registrosFinales) / count($empleadosEnArchivo), 1) . "\n";

// Mostrar muestra
echo "\n📋 Primeros 5 registros:\n";
foreach (array_slice($registrosFinales, 0, 5) as $i => $reg) {
    echo sprintf("[%2d] %s\n", $i, $reg);
}

echo "\n📋 Últimos 5 registros:\n";
foreach (array_slice($registrosFinales, -5) as $i => $reg) {
    echo sprintf("[%2d] %s\n", count($registrosFinales) - 5 + $i, $reg);
}

echo "\n🎉 Archivo de 19,583 registros listo para procesamiento masivo\n";
?>