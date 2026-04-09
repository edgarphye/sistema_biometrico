<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

/**
 * Script para limpiar y normalizar archivo 1_attlog.dat
 * Convierte IDs numéricos a formato EMP#### y filtra solo empleados mapeados
 */

// Conectar a la base de datos
$db = Database::getInstance();

// Obtener todos los ZKTeco IDs mapeados
$sql = "SELECT zkteo_id FROM zkteo_empleado_mapeo WHERE activo = TRUE";
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute();
$mapeo_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Convertir a formato numérico para comparación
$ids_numericos = [];
foreach ($mapeo_ids as $id_emp) {
    // Extraer número de EMP#### -> ####
    if (preg_match('/EMP(\d+)/', $id_emp, $matches)) {
        $ids_numericos[] = $matches[1];
    }
}

echo "Empleados mapeados encontrados: " . count($ids_numericos) . "\n";
echo "IDs numéricos: " . implode(', ', array_slice($ids_numericos, 0, 10)) . "...\n\n";

// Rutas de archivos
$input_file = __DIR__ . '/data/1_attlog.dat';
$output_file = __DIR__ . '/data/1_attlog_cleaned.dat';
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
$empleados_encontrados = [];
$lineas_omitidas = 0;

echo "Procesando archivo...\n";

while (($line = fgets($input_handle)) !== false) {
    $total_lineas++;
    
    // Limpiar línea: quitar espacios al inicio y final
    $line = trim($line);
    if (empty($line)) continue;
    
    // Dividir por tabs (funciona incluso si hay espacios mezclados)
    $parts = preg_split('/\s+/', $line);
    if (count($parts) < 6) {
        $lineas_omitidas++;
        continue;
    }
    
    $empleado_id = trim($parts[0]);
    
    // Verificar si el empleado está mapeado
    if (in_array($empleado_id, $ids_numericos)) {
        // Convertir a formato EMP####
        $emp_formateado = 'EMP' . str_pad($empleado_id, 4, '0', STR_PAD_LEFT);
        $parts[0] = $emp_formateado;
        
        // Reconstruir línea con tabs
        $line_procesada = implode("\t", $parts);
        fwrite($output_handle, $line_procesada . "\n");
        
        $lineas_procesadas++;
        $empleados_encontrados[] = $emp_formateado;
    } else {
        $lineas_omitidas++;
    }
    
    // Progreso cada 1000 líneas
    if ($total_lineas % 1000 == 0) {
        echo "Progreso: $total_lineas líneas procesadas...\n";
    }
}

fclose($input_handle);
fclose($output_handle);

// Estadísticas finales
$empleados_unicos = array_unique($empleados_encontrados);
echo "\n=== RESUMEN DEL PROCESAMIENTO ===\n";
echo "Total de líneas leídas: $total_lineas\n";
echo "Líneas procesadas y válidas: $lineas_procesadas\n";
echo "Líneas omitidas (empleado no mapeado): $lineas_omitidas\n";
echo "Empleados únicos encontrados: " . count($empleados_unicos) . "\n";
echo "Empleados únicos: " . implode(', ', array_slice($empleados_unicos, 0, 10)) . "...\n";
echo "\nArchivo limpio creado: $output_file\n";

// Reemplazar archivo original
if (rename($output_file, $input_file)) {
    echo "Archivo original actualizado con el formato limpio.\n";
} else {
    echo "ADVERTENCIA: No se pudo reemplazar el archivo original.\n";
    echo "Use manualmente: copy \"$output_file\" \"$input_file\"\n";
}

echo "\nProceso completado exitosamente.\n";
?>