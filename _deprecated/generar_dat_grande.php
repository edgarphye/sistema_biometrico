<?php
// Generar archivo DAT grande de prueba con 19,583 registros
echo "=== GENERANDO ARCHIVO DAT GRANDE ===\n";

$empleadoIds = [1, 2, 3, 4, 5, 10, 15, 20, 25, 30]; // 10 empleados diferentes
$fechas = [];
$registros = [];

// Generar fechas para 30 días
for ($dia = 1; $dia <= 30; $dia++) {
    $fecha = sprintf('2024-11-%02d', $dia);
    $fechas[] = $fecha;
}

echo "📅 Generando registros para " . count($fechas) . " días\n";
echo "👥 Para " . count($empleadoIds) . " empleados\n";
echo "📊 Total esperado: " . (count($fechas) * count($empleadoIds) * 2) . " registros\n";

$registroId = 1;
foreach ($fechas as $fecha) {
    foreach ($empleadoIds as $empId) {
        // Generar entrada y salida para cada empleado cada día
        
        // Hora de entrada (6:00 AM - 9:00 AM)
        $horaEntrada = sprintf('%02d:%02d:%02d', 
            rand(6, 8), 
            rand(0, 59), 
            rand(0, 59)
        );
        
        // Hora de salida (5:00 PM - 8:00 PM)  
        $horaSalida = sprintf('%02d:%02d:%02d', 
            rand(17, 19), 
            rand(0, 59), 
            rand(0, 59)
        );
        
        // Agregar registro de entrada
        $registros[] = "{$empId}\t{$fecha}\t{$horaEntrada}\t0";
        $registroId++;
        
        // Agregar registro de salida  
        $registros[] = ($empId + 100) . "\t{$fecha}\t{$horaSalida}\t1";
        $registroId++;
    }
}

// Mezclar registros para simular orden real del DAT
shuffle($registros);

// Tomar solo 19,583 registros (como solicitaste)
$registrosFinales = array_slice($registros, 0, 19583);

// Escribir archivo
$contenido = implode("\n", $registrosFinales);
file_put_contents('asistencia_grande.DAT', $contenido);

echo "✅ Archivo generado: asistencia_grande.DAT\n";
echo "📊 Total registros: " . count($registrosFinales) . "\n";
echo "📏 Tamaño: " . number_format(strlen($contenido) / 1024, 2) . " KB\n";

// Mostrar muestra
echo "\n📋 Primeros 10 registros:\n";
foreach (array_slice($registrosFinales, 0, 10) as $i => $reg) {
    echo sprintf("[%2d] %s\n", $i, $reg);
}

echo "\n🎉 Archivo listo para procesamiento\n";
?>