<?php
require_once __DIR__ . '/config.php';

echo "=== CONFIGURACIÓN INICIAL DEL SISTEMA ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Conexión a la base de datos establecida\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Usuario: " . DB_USER . "\n";
    echo "Base de datos: " . DB_NAME . "\n";
    
    // Verificar si las tablas principales existen
    $tablas_necesarias = ['usuarios', 'empleados', 'retardos'];
    foreach ($tablas_necesarias as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla '$tabla' existe\n";
        } else {
            echo "❌ Tabla '$tabla' NO existe\n";
        }
    }
    
    // Verificar si ya hay datos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarios_existentes = $stmt->fetch()['total'];
    echo "Usuarios existentes: $usuarios_existentes\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleados");
    $empleados_existentes = $stmt->fetch()['total'];
    echo "Empleados existentes: $empleados_existentes\n";
    
    echo "\n=== Creando Estructura de Validación ===\n";
    
    // Crear tabla de validaciones si no existe
    $sql = "
    CREATE TABLE IF NOT EXISTS validaciones_jefe (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL,
        fecha_incidente DATE NOT NULL,
        tipo_incidente ENUM('retardo', 'falta', 'salida_temprana', 'omision_entrada', 'omision_salida') NOT NULL,
        hora_registro TIME,
        motivo_detalle TEXT,
        evidencia_adjunta VARCHAR(500),
        estado_validacion ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
        jefe_validador_id INT,
        fecha_validacion DATETIME,
        comentarios_validacion TEXT,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
        FOREIGN KEY (jefe_validador_id) REFERENCES usuarios(id),
        
        INDEX idx_estado_validacion (estado_validacion),
        INDEX idx_jefe_validador (jefe_validador_id),
        INDEX idx_empleado_fecha (empleado_id, fecha_incidente)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabla 'validaciones_jefe' verificada/creada\n";
    
    // Verificar o agregar columnas faltantes a la tabla retardos
    $columnas_requeridas = [
        'hora_entrada' => 'TIME',
        'hora_salida' => 'TIME', 
        'fecha_asistencia' => 'DATE',
        'dia_semana' => 'VARCHAR(20)',
        'tipo_registro' => 'VARCHAR(50)',
        'categoria_principal' => 'VARCHAR(50)',
        'requiere_validacion_jefe' => 'BOOLEAN DEFAULT FALSE',
        'estado_validacion' => "ENUM('pendiente', 'aprobada', 'rechazada', 'no_requerida') DEFAULT 'no_requerida'",
        'evidencia_adjunta' => 'VARCHAR(500)',
        'motivo_detalle' => 'TEXT'
    ];
    
    foreach ($columnas_requeridas as $columna => $definicion) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM retardos LIKE '$columna'");
            if ($stmt->rowCount() == 0) {
                $sql = "ALTER TABLE retardos ADD COLUMN $columna $definicion";
                $pdo->exec($sql);
                echo "✅ Columna '$columna' agregada a 'retardos'\n";
            } else {
                echo "✅ Columna '$columna' ya existe en 'retardos'\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Error adding column $columna: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Estructura completada ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>