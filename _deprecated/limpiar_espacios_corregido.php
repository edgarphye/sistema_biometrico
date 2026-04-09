<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

/**
 * Script corregido para limpiar espacios y saltos de línea del archivo DAT
 * Maneja los diferentes patrones detectados: 
 * - 5 espacios: '     2603'
 * - Salto + 5 espacios: '\n     1001'
 * - Salto + 6 espacios: '\n      634'
 */

// Rutas de archivos
$input_file = __DIR__ . '/data/1_attlog.dat';
$output_file = __DIR__ . '/data/1_attlog_cleaned.dat';
$backup_file = __DIR__ . '/data/1_attlog_backup_' . date('Y-m-d_H-i-s') . '.dat';

if (!file_exists($input_file)) {
    echo "ERROR: Archivo no encontrado: $input_file\n";
    exit(1);
}

// Crear backup
if (!copy($input_file, $backup_file)) {
    echo "ERROR: No se pudo crear backup\n";
    exit(1);
}

echo "Backup creado: $backup_file\n";

// Procesar archivo byte por byte
$input_handle = fopen($input_file, 'rb');
$output_handle = fopen($output_file, 'wb');

if (!$input_handle || !$output_handle) {
    echo "ERROR: No se pudieron abrir los archivos\n";
    exit(1);
}

$total_lineas = 0;
$lineas_procesadas = 0;
$bytes_procesados = 0;
$file_size = filesize($input_file);

echo "Procesando archivo (" . number_format($file_size) . " bytes)...\n";

$buffer = '';
$in_line = false;

while (!feof($input_handle)) {
    $char = fgetc($input_handle);
    $bytes_procesados++;
    
    if ($char === false) {
        break;
    }
    
    $ascii = ord($char);
    
    // Detectar inicio de línea (después de salto de línea o inicio del archivo)
    if (!$in_line) {
        // Saltar espacios y saltos de línea hasta encontrar un dígito
        if ($ascii >= 48 && $ascii <= 57) { // Es un dígito (0-9)
            $in_line = true;
            $buffer .= $char;
        }
        // Ignorar espacios (32) y saltos de línea (10, 13)
        continue;
    }
    
    // Estamos en una línea, procesar caracteres
    if ($ascii === 10 || $ascii === 13) { // Fin de línea
        if (!empty(trim($buffer))) {
            fwrite($output_handle, trim($buffer) . "\n");
            $lineas_procesadas++;
        }
        $buffer = '';
        $in_line = false;
        continue;
    }
    
    // Agregar caracter al buffer
    $buffer .= $char;
    
    // Progreso cada 5000 líneas o cada 100KB
    if ($lineas_procesadas % 5000 == 0 || $bytes_procesados % 100000 == 0) {
        $porcentaje = round(($bytes_procesados / $file_size) * 100, 2);
        echo "Progreso: $porcentaje% ($bytes_procesados/$file_size bytes, $lineas_procesadas líneas)\n";
    }
}

// Procesar última línea si existe
if (!empty(trim($buffer))) {
    fwrite($output_handle, trim($buffer) . "\n");
    $lineas_procesadas++;
}

fclose($input_handle);
fclose($output_handle);

// Verificar resultado
echo "\n=== VERIFICACIÓN ===\n";
echo "Primeras 5 líneas procesadas:\n";
$verify_handle = fopen($output_file, 'r');
for ($i = 0; $i < 5 && ($line = fgets($verify_handle)) !== false; $i++) {
    echo "Línea " . ($i+1) . ": '" . trim($line) . "'\n";
    $parts = explode("\t", trim($line));
    echo "  - ID: '$parts[0]'\n";
}
fclose($verify_handle);

echo "\n=== RESUMEN ===\n";
echo "Bytes procesados: " . number_format($bytes_procesados) . "\n";
echo "Líneas procesadas: $lineas_procesadas\n";
echo "Tamaño original: " . number_format($file_size) . " bytes\n";
echo "Tamaño procesado: " . number_format(filesize($output_file)) . " bytes\n";
echo "Reducción: " . round((1 - filesize($output_file)/$file_size) * 100, 2) . "%\n";
echo "\nArchivo limpio creado: $output_file\n";

// Reemplazar original
if (rename($output_file, $input_file)) {
    echo "✅ Archivo original actualizado correctamente.\n";
} else {
    echo "⚠️  No se pudo reemplazar automáticamente. Manualmente:\n";
    echo "   copy \"$output_file\" \"$input_file\"\n";
}

echo "\n🎯 ¡PROCESO COMPLETADO!\n";
echo "   El archivo ahora está limpio y listo para ser procesado.\n";
echo "   Los espacios antes del ID han sido eliminados.\n";
?>