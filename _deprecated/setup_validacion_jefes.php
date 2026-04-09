<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== CREANDO TABLA VALIDACIONES_JEFE ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Crear tabla de validaciones de jefes
    $sql = "CREATE TABLE IF NOT EXISTS validaciones_jefe (
        id INT AUTO_INCREMENT PRIMARY KEY,
        incidencia_id INT NOT NULL,
        tipo_incidencia ENUM('retardo', 'comision', 'dia_economico', 'ausencia') NOT NULL,
        empleado_id INT NOT NULL,
        jefe_id INT NOT NULL,
        estado ENUM('pendiente', 'aprobado', 'rechazado', 'requiere_info') DEFAULT 'pendiente',
        motivo_validacion TEXT,
        comentarios_adicionales TEXT,
        evidencia_requerida BOOLEAN DEFAULT FALSE,
        evidencia_recibida BOOLEAN DEFAULT FALSE,
        fecha_validacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_limite TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Foreign Keys
        FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
        FOREIGN KEY (jefe_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        
        -- Índices para rendimiento
        INDEX idx_empleado_estado (empleado_id, estado),
        INDEX idx_jefe_pendientes (jefe_id, estado),
        INDEX idx_incidencia_tipo (incidencia_id, tipo_incidencia),
        INDEX idx_fecha_solicitud (fecha_solicitud),
        
        -- Constraint única para evitar duplicados
        UNIQUE KEY unique_validacion (incidencia_id, tipo_incidencia)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabla validaciones_jefe creada correctamente\n";
    
    // Agregar campo jefe_directo_id a tabla empleados si no existe
    try {
        $pdo->exec("ALTER TABLE empleados ADD COLUMN jefe_directo_id INT NULL AFTER jerarquia");
        $pdo->exec("ALTER TABLE empleados ADD CONSTRAINT fk_jefe_directo 
                   FOREIGN KEY (jefe_directo_id) REFERENCES empleados(id) ON DELETE SET NULL");
        echo "✅ Campo jefe_directo_id agregado a tabla empleados\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo jefe_directo_id ya existe en tabla empleados\n";
    }
    
    // Crear tabla de configuración de reglas de validación
    $sql = "CREATE TABLE IF NOT EXISTS reglas_validacion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_regla VARCHAR(100) NOT NULL,
        tipo_incidencia ENUM('retardo', 'comision', 'dia_economico', 'ausencia') NOT NULL,
        condicion VARCHAR(200) NOT NULL,
        valor_condicion VARCHAR(100) NOT NULL,
        accion ENUM('auto_aprobar', 'requiere_validacion', 'auto_rechazar') NOT NULL,
        descripcion TEXT,
        activa BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_tipo_incidencia (tipo_incidencia),
        INDEX idx_activa (activa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabla reglas_validacion creada correctamente\n";
    
    // Insertar reglas por defecto si no existen
    $reglasDefault = [
        [
            'nombre_regla' => 'Retardo menor auto-aprobado',
            'tipo_incidencia' => 'retardo',
            'condicion' => 'minutos_maximos',
            'valor_condicion' => '15',
            'accion' => 'auto_aprobar',
            'descripcion' => 'Retardos de 15 minutos o menos se aprueban automáticamente'
        ],
        [
            'nombre_regla' => 'Retardo medio requiere validación',
            'tipo_incidencia' => 'retardo',
            'condicion' => 'minutos_rango',
            'valor_condicion' => '16-60',
            'accion' => 'requiere_validacion',
            'descripcion' => 'Retardos de 16 a 60 minutos requieren validación del jefe'
        ],
        [
            'nombre_regla' => 'Retardo mayor requiere evidencia',
            'tipo_incidencia' => 'retardo',
            'condicion' => 'minutos_minimos',
            'valor_condicion' => '61',
            'accion' => 'requiere_validacion',
            'descripcion' => 'Retardos mayores a 60 minutos requieren validación con evidencia'
        ],
        [
            'nombre_regla' => 'Comisión alta requiere validación',
            'tipo_incidencia' => 'comision',
            'condicion' => 'monto_minimo',
            'valor_condicion' => '1000',
            'accion' => 'requiere_validacion',
            'descripcion' => 'Comisiones mayores a $1000 requieren validación'
        ]
    ];
    
    foreach ($reglasDefault as $regla) {
        $sql = "INSERT IGNORE INTO reglas_validacion 
                (nombre_regla, tipo_incidencia, condicion, valor_condicion, accion, descripcion) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $regla['nombre_regla'],
            $regla['tipo_incidencia'],
            $regla['condicion'],
            $regla['valor_condicion'],
            $regla['accion'],
            $regla['descripcion']
        ]);
    }
    
    echo "✅ Reglas de validación por defecto insertadas\n";
    echo "\n🎉 Estructura de base de datos para validaciones por jefes creada exitosamente\n";
    
} catch (Exception $e) {
    echo "❌ Error creando tablas: " . $e->getMessage() . "\n";
}
?>