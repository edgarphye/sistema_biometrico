<?php
// Analizar qué empleados realmente existen en el archivo
$file = __DIR__ . '/data/1_attlog_backup.dat';
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$ids_encontrados = [];
$total_lineas = count($lines);

echo "Analizando IDs de empleados en archivo original...\n";
echo "Total líneas: $total_lineas\n";
echo str_repeat("=", 50) . "\n";

for ($i = 0; $i < min(100, count($lines)); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    // Extraer primer campo numérico
    $parts = preg_split('/\s+/', $line);
    if (!empty($parts)) {
        $id = trim($parts[0]);
        if (is_numeric($id) && !in_array($id, $ids_encontrados)) {
            $ids_encontrados[] = $id;
            if (count($ids_encontrados) <= 20) { // Mostrar primeros 20
                echo "ID: $id\n";
            }
        }
    }
}

echo "\nTotal IDs únicos en primeras 100 líneas: " . count($ids_encontrados) . "\n";
echo "Primeros 20 IDs: " . implode(', ', array_slice($ids_encontrados, 0, 20)) . "\n";

// Analizar todo el archivo para ver distribución
$all_ids = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $parts = preg_split('/\s+/', $line);
    if (!empty($parts)) {
        $id = trim($parts[0]);
        if (is_numeric($id)) {
            if (!isset($all_ids[$id])) {
                $all_ids[$id] = 0;
            }
            $all_ids[$id]++;
        }
    }
}

echo "\n=== ESTADÍSTICAS COMPLETAS ===\n";
echo "Total IDs únicos en todo el archivo: " . count($all_ids) . "\n";

// Mostrar IDs con más registros
arsort($all_ids);
$top_10 = array_slice($all_ids, 0, 10, true);
echo "\nTop 10 empleados con más registros:\n";
foreach ($top_10 as $id => $count) {
    echo "ID $id: $count registros\n";
}

// Verificar si alguno coincide con nuestros IDs mapeados
$ids_mapeo_numericos = ['118', '119', '120', '121', '122', '123', '124', '125', '126', '127'];
$coincidencias = array_intersect($ids_mapeo_numericos, array_keys($all_ids));

echo "\n=== COINCIDENCIAS CON MAPEO ===\n";
echo "IDs mapeados que existen en archivo: " . implode(', ', $coincidencias) . "\n";
echo "Total coincidencias: " . count($coincidencias) . "\n";

if (empty($coincidencias)) {
    echo "\n⚠️  ADVERTENCIA: Ninguno de los IDs mapeados existe en el archivo DAT\n";
    echo "   Posibles causas:\n";
    echo "   - Los IDs en la base de datos están en formato EMP#### pero en archivo son numéricos\n";
    echo "   - Los empleados mapeados no tienen registros en este período\n";
    echo "   - El archivo corresponde a otro conjunto de empleados\n";
}
?>