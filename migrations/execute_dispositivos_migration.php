<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "Ejecutando migración: 20251126_add_dispositivos_biometricos_config.sql\n\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Leer archivo SQL
    $sqlFile = __DIR__ . '/20251126_add_dispositivos_biometricos_config.sql';
    
    if (!file_exists($sqlFile)) {
        die("Error: Archivo de migración no encontrado: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir por punto y coma (excepto dentro de VALUES)
    $statements = [];
    $currentStatement = '';
    $inValues = false;
    
    $lines = explode("\n", $sql);
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Saltar comentarios y líneas vacías
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        
        $currentStatement .= $line . ' ';
        
        // Detectar si estamos dentro de VALUES
        if (stripos($line, 'VALUES') !== false) {
            $inValues = true;
        }
        
        // Si encontramos punto y coma y no estamos en VALUES, o si terminamos VALUES
        if (substr(rtrim($line), -1) === ';') {
            $statements[] = trim($currentStatement);
            $currentStatement = '';
            $inValues = false;
        }
    }
    
    // Ejecutar cada statement
    $pdo->beginTransaction();
    
    foreach ($statements as $i => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            echo "Ejecutando statement " . ($i + 1) . "...\n";
            $pdo->exec($statement);
            echo "✓ OK\n";
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    $pdo->commit();
    
    echo "\n✅ Migración completada exitosamente!\n";
    echo "Tabla 'dispositivos_biometricos' creada con 35 dispositivos.\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "\n❌ Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
?>
