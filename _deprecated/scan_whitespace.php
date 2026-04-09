<?php
/**
 * Script para detectar y corregir archivos PHP con espacios en blanco problemáticos.
 * Detecta:
 * 1. Espacios antes de <?php
 * 2. Etiqueta de cierre ?> seguida de espacios/saltos de línea (causa común de errores)
 * 
 * Uso desde navegador: http://localhost/sistema_biometrico/scan_whitespace.php
 * Uso desde consola: php scan_whitespace.php [--fix]
 */

header('Content-Type: text/plain; charset=utf-8');

// Configuración
$directory = __DIR__; // Directorio actual
$isCli = php_sapi_name() === 'cli';
$fix = (isset($_GET['fix']) && $_GET['fix'] == 1) || ($isCli && isset($argv[1]) && $argv[1] === '--fix');

echo "=== ESCANER DE ESPACIOS EN BLANCO PHP ===\n";
echo "Directorio: $directory\n";
if ($fix) echo "MODO: CORRECCIÓN AUTOMÁTICA ACTIVADO\n";
echo str_repeat("-", 50) . "\n";

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$issuesFound = 0;
$filesScanned = 0;

foreach ($regex as $file) {
    $path = $file[0];
    
    // Ignorar este mismo script y carpetas de librerías externas si existen
    if (realpath($path) === __FILE__) continue;
    if (strpos($path, 'vendor') !== false) continue;
    
    $filesScanned++;
    $content = file_get_contents($path);
    $originalContent = $content;
    $hasIssue = false;
    $messages = [];
    
    // 1. Verificar espacios antes de <?php
    if (preg_match('/^\s+<\?php/', $content)) {
        $messages[] = "[INICIO] Espacios antes de <?php";
        $content = ltrim($content);
        $hasIssue = true;
    }
    
    // 2. Verificar etiqueta de cierre al final
    // La recomendación PSR-12 es omitir al final de archivos que solo contienen PHP
    if (preg_match('/\?>\s*$/', $content)) {
        // Verificar si hay espacios después
        if (preg_match('/\?>(\s+)$/', $content)) {
            $messages[] = "[FINAL]  Espacios después de ?> (CRÍTICO)";
            $hasIssue = true;
        } else {
            // Tiene pero sin espacios. No es error crítico, pero se recomienda quitar.
            // $messages[] = "[INFO]   Tiene etiqueta cierre Recomendado quitar)";
        }
        
        // En modo fix, quitamos la etiqueta de cierre para cumplir PSR-12 y evitar problemas futuros
        if ($fix) {
            $content = preg_replace('/\?>\s*$/', '', $content);
            $content = rtrim($content) . "\n"; // Asegurar un solo salto de línea final limpio
        }
    }
    
    if ($hasIssue) {
        $issuesFound++;
        echo "ARCHIVO: " . str_replace($directory, '', $path) . "\n";
        foreach ($messages as $msg) echo "  -> $msg\n";
        
        if ($fix && $content !== $originalContent) {
            file_put_contents($path, $content);
            echo "  -> CORREGIDO\n";
        }
        echo "\n";
    }
}

echo str_repeat("-", 50) . "\n";
echo "Resumen:\n";
echo "Archivos escaneados: $filesScanned\n";
echo "Problemas encontrados: $issuesFound\n";

if ($issuesFound > 0 && !$fix) {
    echo "\nPARA CORREGIR AUTOMÁTICAMENTE:\n";
    if ($isCli) {
        echo "Ejecute: php scan_whitespace.php --fix\n";
    } else {
        echo "Agregue ?fix=1 a la URL: scan_whitespace.php?fix=1\n";
    }
} elseif ($issuesFound == 0) {
    echo "\n¡Todo limpio! No se detectaron problemas de espacios en blanco.\n";
}