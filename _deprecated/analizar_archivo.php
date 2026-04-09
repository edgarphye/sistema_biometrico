<?php
// Leer y analizar el archivo 1_attlog.dat
$file = __DIR__ . '/data/1_attlog.dat';
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

echo "Análisis de primeras 20 líneas:\n";
echo str_repeat("=", 50) . "\n";

$ids_numericos = [];
for ($i = 0; $i < min(20, count($lines)); $i++) {
    $line = $lines[$i];
    
    // Mostrar línea original y su longitud
    echo "Línea " . ($i+1) . " (longitud: " . strlen($line) . "): '" . $line . "'\n";
    
    // Intentar diferentes métodos de separación
    $parts_tab = explode("\t", $line);
    $parts_space = explode(" ", $line);
    
    echo "  - Partes con tab: " . count($parts_tab) . "\n";
    echo "  - Partes con espacio: " . count($parts_space) . "\n";
    
    // Extraer primer ID numérico
    if (!empty($parts_tab) && count($parts_tab) > 1) {
        $id_limpio = trim($parts_tab[0]);
        echo "  - ID (tab): '$id_limpio'\n";
        if (is_numeric($id_limpio)) {
            $ids_numericos[] = $id_limpio;
        }
    } elseif (!empty($parts_space) && count($parts_space) > 1) {
        $id_limpio = trim($parts_space[0]);
        echo "  - ID (espacio): '$id_limpio'\n";
        if (is_numeric($id_limpio)) {
            $ids_numericos[] = $id_limpio;
        }
    }
    echo "\n";
}

$ids_unicos = array_unique($ids_numericos);
echo "IDs únicos encontrados: " . implode(', ', $ids_unicos) . "\n";
?>