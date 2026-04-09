<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== 🔄 Ejecutando Migración de Ciclos ===\n\n";

$sqlFile = __DIR__ . '/migrations/20250122_create_ciclos_tables.sql';
$migrationsDir = dirname($sqlFile);

// Asegurar que el directorio existe
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

// Si el archivo SQL no existe, lo creamos con el contenido correcto
if (!file_exists($sqlFile)) {
    echo "📝 Creando archivo de migración: " . basename($sqlFile) . "\n";
    
    $sqlContent = <<<'SQL'
-- Tabla para definir los ciclos (ej. Turno Matutino Semanal)
CREATE TABLE IF NOT EXISTS ciclos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    num_ciclo INT DEFAULT 1,
    unidad_ciclo ENUM('Semana', 'Mes') DEFAULT 'Semana',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para los bloques de horario dentro de un ciclo
CREATE TABLE IF NOT EXISTS bloques_ciclo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ciclo_id INT NOT NULL,
    dia_semana INT NOT NULL COMMENT '0=Lunes, 1=Martes, ..., 6=Domingo',
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    horario_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_bloques_ciclo_ciclo 
        FOREIGN KEY (ciclo_id) 
        REFERENCES ciclos(id) 
        ON DELETE CASCADE,
        
    CONSTRAINT fk_bloques_ciclo_horario 
        FOREIGN KEY (horario_id) 
        REFERENCES horarios_laborales(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para mejorar rendimiento
CREATE INDEX idx_bloques_ciclo_dia ON bloques_ciclo(ciclo_id, dia_semana);
SQL;
    
    if (file_put_contents($sqlFile, $sqlContent)) {
        echo "   ✓ Archivo creado correctamente.\n";
    } else {
        die("   ❌ Error al crear el archivo SQL.\n");
    }
} else {
    echo "📂 Archivo de migración encontrado.\n";
}

// Leer y ejecutar
echo "🚀 Ejecutando sentencias SQL...\n";
$sql = file_get_contents($sqlFile);

try {
    $db = Database::getInstance()->getConnection();
    
    // Separar por ; para ejecución individual
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
            // Mostrar resumen de la sentencia
            $summary = substr(str_replace(["\n", "\r"], " ", $statement), 0, 60);
            echo "   ✓ Ejecutado: $summary...\n";
        }
    }
    
    echo "\n✅ Migración completada con éxito.\n";
    echo "   Las tablas 'ciclos' y 'bloques_ciclo' están listas.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error durante la migración: " . $e->getMessage() . "\n";
}
?>
