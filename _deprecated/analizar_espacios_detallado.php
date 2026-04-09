<?php
/**
 * Script para analizar el archivo DAT caracter por caracter
 * Identificar exactamente qué tipo de espacios hay antes de la primera columna
 */

$file = __DIR__ . '/data/1_attlog.dat';
$handle = fopen($file, 'rb');

if (!$handle) {
    echo "No se pudo abrir el archivo\n";
    exit(1);
}

echo "Análisis detallado de primeras 5 líneas:\n";
echo str_repeat("=", 60) . "\n";

for ($line_num = 0; $line_num < 5; $line_num++) {
    echo "\n--- LÍNEA " . ($line_num + 1) . " ---\n";
    
    $line = '';
    $char_num = 0;
    
    // Leer caracteres uno por uno hasta encontrar un dígito
    while (($char = fgetc($handle)) !== false && $char_num < 50) {
        $line .= $char;
        $char_num++;
        
        // Mostrar cada carácter con su valor ASCII
        $ascii = ord($char);
        
        if ($ascii >= 48 && $ascii <= 57) { // Es un dígito
            echo "Posición $char_num: '$char' (ASCII: $ascii) ← INICIO DEL ID\n";
            // Leer el resto de la línea
            while (($char = fgetc($handle)) !== false && $char !== "\n" && $char !== "\r") {
                $line .= $char;
            }
            break;
        } else {
            // Es un espacio o caracter especial
            $space_type = '';
            if ($ascii == 9) $space_type = '[TAB]';
            elseif ($ascii == 32) $space_type = '[SPACE]';
            else $space_type = '[OTHER]';
            
            echo "Posición $char_num: '$char' (ASCII: $ascii) $space_type\n";
        }
    }
    
    echo "Línea completa: '$line'\n";
    echo "Longitud: " . strlen($line) . " caracteres\n";
    
    // Saltar al final de la línea si es necesario
    if ($char !== "\n" && $char !== "\r") {
        while (($char = fgetc($handle)) !== false && $char !== "\n") {
            // Consumir hasta el final de la línea
        }
    }
}

fclose($handle);

echo "\n=== ANÁLISIS GENERAL ===\n";
echo "Revisando primeras 20 líneas para patrones de espacios:\n";

$handle = fopen($file, 'rb');
$space_patterns = [];

for ($i = 0; $i < 20; $i++) {
    $spaces_at_start = '';
    $char_count = 0;
    
    while (($char = fgetc($handle)) !== false && $char_count < 20) {
        $char_count++;
        $ascii = ord($char);
        
        if ($ascii >= 48 && $ascii <= 57) { // Dígito encontrado
            break;
        } else {
            $spaces_at_start .= $char;
        }
    }
    
    // Consumir el resto de la línea
    while (($char = fgetc($handle)) !== false && $char !== "\n" && $char !== "\r") {
        // Consumir hasta el final
    }
    
    if (!isset($space_patterns[$spaces_at_start])) {
        $space_patterns[$spaces_at_start] = 0;
    }
    $space_patterns[$spaces_at_start]++;
    
    // Mostrar patrón
    $pattern_hex = '';
    for ($j = 0; $j < strlen($spaces_at_start); $j++) {
        $pattern_hex .= sprintf('%02X ', ord($spaces_at_start[$j]));
    }
    echo "Línea " . ($i + 1) . ": '$spaces_at_start' -> Hex: [$pattern_hex]\n";
}

fclose($handle);

echo "\n=== PATRONES DE ESPACIOS ENCONTRADOS ===\n";
foreach ($space_patterns as $pattern => $count) {
    $pattern_hex = '';
    for ($j = 0; $j < strlen($pattern); $j++) {
        $pattern_hex .= sprintf('%02X ', ord($pattern[$j]));
    }
    echo "Patrón: '$pattern' (Hex: [$pattern_hex]) -> $count veces\n";
}

echo "\nLongitudes de patrones:\n";
foreach ($space_patterns as $pattern => $count) {
    echo "- " . strlen($pattern) . " caracteres: $count veces\n";
}
?>