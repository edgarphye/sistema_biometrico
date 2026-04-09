<?php
// Leer archivo RAW byte por byte
$file = __DIR__ . '/data/1_attlog.dat';
$handle = fopen($file, 'rb');

if (!$handle) {
    echo "No se pudo abrir el archivo\n";
    exit(1);
}

echo "Análisis de primeras 200 bytes:\n";
echo str_repeat("=", 50) . "\n";

$bytes = fread($handle, 200);
fclose($handle);

// Mostrar en hex y ASCII
for ($i = 0; $i < strlen($bytes); $i++) {
    $byte = ord($bytes[$i]);
    $char = ($byte >= 32 && $byte <= 126) ? $bytes[$i] : '.';
    
    printf("%02X %c ", $byte, $char);
    
    if (($i + 1) % 16 == 0) {
        echo "\n";
    }
}

echo "\n\nAnálisis como texto:\n";
echo str_repeat("=", 30) . "\n";
echo "'" . $bytes . "'\n";

// Intentar leer como líneas normales también
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "\nPrimeras 5 líneas como array:\n";
for ($i = 0; $i < min(5, count($lines)); $i++) {
    $line = $lines[$i];
    echo "Línea " . ($i+1) . ": longitud=" . strlen($line) . ", contenido='" . $line . "'\n";
    
    // Extraer caracteres printable
    $clean = '';
    for ($j = 0; $j < strlen($line); $j++) {
        $c = $line[$j];
        if (ord($c) >= 32 && ord($c) <= 126) {
            $clean .= $c;
        }
    }
    echo "  - Clean: '$clean'\n";
}
?>