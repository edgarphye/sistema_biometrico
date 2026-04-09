<?php
// Generar archivo DAT con fechas de 2025, 2026 y años mixtos
echo "=== GENERANDO ARCHIVO CON FECHAS 2025-2026 MIXTAS ===\n";

$empleadoIds = [1, 2, 3, 4, 5, 10, 15, 20, 25, 30];
$registros = [];
$registroId = 1;

// Generar fechas de diferentes años
$fechas2025 = [];
$fechas2026 = [];
$fechasOtros = [];

// Fechas 2025 (últimos meses del año)
for ($mes = 10; $mes <= 12; $mes++) {
    for ($dia = 1; $dia <= 25; $dia++) { // Solo 25 días por mes para más variedad
        $fechas2025[] = sprintf('2025-%02d-%02d', $mes, $dia);
    }
}

// Fechas 2026 (primeros meses del año)
for ($mes = 1; $mes <= 6; $mes++) {
    for ($dia = 1; $dia <= 25; $dia++) {
        $fechas2026[] = sprintf('2026-%02d-%02d', $mes, $dia);
    }
}

// Fechas de otros años (2024 para datos históricos)
for ($mes = 6; $mes <= 12; $mes++) {
    for ($dia = 1; $dia <= 20; $dia++) {
        $fechasOtros[] = sprintf('2024-%02d-%02d', $mes, $dia);
    }
}

// Mezclar todas las fechas
$fechasMixtas = array_merge($fechas2025, $fechas2026, $fechasOtros);
shuffle($fechasMixtas);

echo "📅 Fechas 2025: " . count($fechas2025) . "\n";
echo "📅 Fechas 2026: " . count($fechas2026) . "\n";
echo "📅 Fechas otros: " . count($fechasOtros) . "\n";
echo "📅 Total fechas mixtas: " . count($fechasMixtas) . "\n";

// Generar registros
foreach ($fechasMixtas as $fecha) {
    foreach ($empleadoIds as $empId) {
        // Skip fines de semana (simulación realista)
        $diaSemana = date('N', strtotime($fecha));
        if ($diaSemana >= 6) continue;
        
        // Skip algunos días aleatorios (vacaciones, ausencias)
        if (rand(1, 100) <= 20) continue; // 20% de ausencia
        
        // Generar entrada
        $horaEntrada = sprintf('%02d:%02d:%02d', 
            rand(6, 9),
            rand(0, 59), 
            rand(0, 59)
        );
        
        // Generar salida
        $horaSalida = sprintf('%02d:%02d:%02d', 
            rand(17, 20),
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

// Limitar a un número manejable pero significativo
$registrosFinales = array_slice($registros, 0, 5000);

// Escribir archivo
$contenido = implode("\n", $registrosFinales);
file_put_contents('asistencia_anios_mixtos.DAT', $contenido);

echo "✅ Archivo generado: asistencia_anios_mixtos.DAT\n";
echo "📊 Total registros: " . count($registrosFinales) . "\n";
echo "📏 Tamaño: " . number_format(strlen($contenido) / 1024, 2) . " KB\n";

// Análisis de años en el archivo
echo "\n📊 ANÁLISIS DE AÑOS EN EL ARCHIVO:\n";
$anosEnArchivo = [];
foreach ($registrosFinales as $reg) {
    $parts = explode("\t", $reg);
    $fecha = $parts[1];
    $ano = substr($fecha, 0, 4);
    if (!isset($anosEnArchivo[$ano])) {
        $anosEnArchivo[$ano] = 0;
    }
    $anosEnArchivo[$ano]++;
}

foreach ($anosEnArchivo as $ano => $cantidad) {
    echo "   📅 Año $ano: $cantidad registros\n";
}

// Mostrar muestra de diferentes años
echo "\n📋 MUESTRA POR AÑO:\n";
$muestrasPorAno = [];
foreach ($registrosFinales as $reg) {
    $parts = explode("\t", $reg);
    $fecha = $parts[1];
    $ano = substr($fecha, 0, 4);
    if (!isset($muestrasPorAno[$ano]) || count($muestrasPorAno[$ano]) < 3) {
        $muestrasPorAno[$ano][] = $reg;
    }
}

foreach ($muestrasPorAno as $ano => $muestras) {
    echo "\n   📅 AÑO $ano:\n";
    foreach ($muestras as $i => $muestra) {
        $parts = explode("\t", $muestra);
        echo sprintf("      [%d] ZK:%s | %s | %s | %s\n", 
            $i, $parts[0], $parts[1], 
            $parts[3] == '0' ? 'ENTRADA' : 'SALIDA', $parts[2]);
    }
}

echo "\n🎉 Archivo con fechas mixtas listo para procesamiento\n";
?>