<?php
/**
 * Script para crear base de datos de testing
 * Sistema Biométrico
 */

// Configuración de testing
$testConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'root',
    'database' => 'sistema_biometrico_test'
];

echo "🚀 Creando base de datos de testing...\n";

try {
    // Conectar sin especificar base de datos
    $pdo = new PDO("mysql:host={$testConfig['host']}", $testConfig['user'], $testConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$testConfig['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos '{$testConfig['database']}' creada\n";
    
    // Seleccionar base de datos
    $pdo->exec("USE {$testConfig['database']}");
    
    // Leer y ejecutar script SQL
    $sqlFile = __DIR__ . '/database/test_database.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Separar sentencias y ejecutar
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    echo "⚠️  Advertencia en sentencia: " . substr($statement, 0, 50) . "...\n";
                    echo "   Error: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "✅ Script SQL ejecutado\n";
    } else {
        echo "❌ Archivo SQL no encontrado: $sqlFile\n";
        exit(1);
    }
    
    // Verificar tablas creadas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tablas creadas: " . implode(', ', $tables) . "\n";
    
    // Verificar datos de prueba
    $empleadosCount = $pdo->query("SELECT COUNT(*) FROM empleados")->fetchColumn();
    $usuariosCount = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    
    echo "👥 Empleados de prueba: $empleadosCount\n";
    echo "👤 Usuarios de prueba: $usuariosCount\n";
    
    echo "🎉 Base de datos de testing configurada exitosamente\n";
    
} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
}