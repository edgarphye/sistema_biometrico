<?php
/**
 * Script simplificado para crear base de datos de testing
 */

$testConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'root',
    'database' => 'sistema_biometrico_test'
];

echo "🚀 Configurando base de datos de testing...\n";

try {
    $pdo = new PDO("mysql:host={$testConfig['host']}", $testConfig['user'], $testConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Eliminar y recrear base de datos
    $pdo->exec("DROP DATABASE IF EXISTS {$testConfig['database']}");
    $pdo->exec("CREATE DATABASE {$testConfig['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$testConfig['database']}");
    
    echo "✅ Base de datos creada\n";
    
    // Crear tablas una por una
    $tables = [
        "CREATE TABLE empleados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) NOT NULL,
            rfc VARCHAR(13) UNIQUE NOT NULL,
            email VARCHAR(100),
            telefono VARCHAR(20),
            area VARCHAR(50),
            puesto VARCHAR(50),
            activo TINYINT(1) DEFAULT 1,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            rol ENUM('admin', 'user', 'viewer') DEFAULT 'user',
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE dias_economicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            fecha DATE NOT NULL,
            motivo TEXT,
            estatus ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
            solicitado_por INT,
            fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            aprobado_por INT,
            fecha_aprobacion TIMESTAMP NULL,
            comentarios_aprobacion TEXT,
            motivo_rechazo TEXT
        )",
        
        "CREATE TABLE asistencia (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            fecha DATE NOT NULL,
            hora_entrada TIME,
            hora_salida TIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE retardos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            fecha DATE NOT NULL,
            minutos_retraso INT NOT NULL,
            tipo_retraso ENUM('menor', 'mayor') NOT NULL,
            justificado TINYINT(1) DEFAULT 0
        )",
        
        "CREATE TABLE sanciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            tipo_sancion ENUM('amonestacion', 'suspension', 'otro') NOT NULL,
            motivo TEXT NOT NULL,
            fecha_inicio DATE NOT NULL,
            dias INT DEFAULT 0,
            estatus ENUM('activa', 'cumplida', 'cancelada') DEFAULT 'activa'
        )"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    
    echo "✅ Tablas creadas\n";
    
    // Insertar datos de prueba
    $pdo->exec("INSERT INTO empleados (nombre, apellido, rfc, email, area, puesto) VALUES 
        ('Juan', 'Pérez', 'PEJU800101HDF', 'juan.perez@test.com', 'TI', 'Desarrollador'),
        ('María', 'González', 'GOMA750123MDF', 'maria.gonzalez@test.com', 'RH', 'Gerente')");
    
    $pdo->exec("INSERT INTO usuarios (username, password, email, rol) VALUES 
        ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@test.com', 'admin'),
        ('user', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@test.com', 'user')");
    
    $pdo->exec("INSERT INTO dias_economicos (empleado_id, fecha, motivo, estatus) VALUES 
        (1, '2025-12-15', 'Asuntos personales', 'pendiente'),
        (2, '2025-12-20', 'Cita médica', 'aprobado')");
    
    echo "✅ Datos de prueba insertados\n";
    
    // Verificar
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tablas: " . implode(', ', $tables) . "\n";
    
    $empleados = $pdo->query("SELECT COUNT(*) FROM empleados")->fetchColumn();
    $usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    echo "👥 Empleados: $empleados, Usuarios: $usuarios\n";
    
    echo "🎉 Base de datos de testing lista!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}