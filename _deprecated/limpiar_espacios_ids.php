<?php
/**
 * Script para limpiar solo los espacios de la primera columna del archivo DAT
 * Mantiene todos los registros, solo elimina espacios iniciales del ID de empleado
 */

// Rutas de archivos
$input_file = __DIR__ . '/data/1_attlog_backup.dat';
$output_file = __DIR__ . '/data/1_attlog.dat';
$backup_file = __DIR__ . '/data/1_attlog_backup_' . date('Y-m-d_H-i-s') . '.dat';

if (!file_exists($input_file)) {
    echo "ERROR: Archivo de entrada no encontrado: $input_file\n";
    exit(1);
}

// Crear backup
if (!copy($input_file, $backup_file)) {
    echo "ERROR: No se pudo crear backup\n";
    exit(1);
}

echo "Backup creado: $backup_file\n";

// Procesar archivo
$input_handle = fopen($input_file, 'r');
$output_handle = fopen($output_file, 'w');

if (!$input_handle || !$output_handle) {
    echo "ERROR: No se pudieron abrir los archivos\n";
    exit(1);
}

$total_lineas = 0;
$lineas_procesadas = 0;

echo "Limpiando espacios de la primera columna...\n";

while (($line = fgets($input_handle)) !== false) {
    $total_lineas++;
    
    if (empty(trim($line))) {
        continue;
    }
    
    // Dividir la línea por tabs
    $parts = explode("\t", $line);
    
    if (count($parts) >= 1) {
        // Limpiar solo la primera columna (ID de empleado)
        $parts[0] = trim($parts[0]);
        
        // Reconstruir la línea con tabs
        $line_procesada = implode("\t", $parts);
        
        // Asegurar que termina con salto de línea
        if (!str_ends_with($line_procesada, "\n")) {
            $line_procesada .= "\n";
        }
        
        fwrite($output_handle, $line_procesada);
        $lineas_procesadas++;
    }
    
    // Progreso cada 2000 líneas
    if ($total_lineas % 2000 == 0) {
        echo "Progreso: $total_lineas líneas procesadas...\n";
    }
}

fclose($input_handle);
fclose($output_handle);

echo "\n=== RESUMEN ===\n";
echo "Total de líneas leídas: $total_lineas\n";
echo "Líneas procesadas: $lineas_procesadas\n";
echo "Archivo limpio creado: $output_file\n";

// Verificar el resultado
echo "\n=== VERIFICACIÓN ===\n";
echo "Primeras 5 líneas después de limpiar:\n";
$verify_handle = fopen($output_file, 'r');
for ($i = 0; $i < 5 && ($line = fgets($verify_handle)) !== false; $i++) {
    echo "Línea " . ($i+1) . ": '" . trim($line) . "'\n";
    $parts = explode("\t", $line);
    if (count($parts) >= 1) {
        echo "  - ID limpio: '$parts[0]'\n";
    }
}
fclose($verify_handle);

echo "\n✅ ¡Proceso completado! Todos los registros conservados, solo se limpiaron espacios de IDs.\n";
?>