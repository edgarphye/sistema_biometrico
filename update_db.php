<?php
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "Actualizando estructura de la base de datos...\n";

    // Agregar columnas biométricas a la tabla asistencia (solo si no existen)
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN tipo_biometria ENUM("huella", "cara") NOT NULL DEFAULT "huella" AFTER dispositivo_id');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN datos_biometricos JSON NULL AFTER tipo_biometria');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN calidad_verificacion TINYINT NULL AFTER datos_biometricos');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN metadata_dispositivo JSON NULL AFTER calidad_verificacion');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN tiempo_procesamiento DECIMAL(5,3) NULL AFTER metadata_dispositivo');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }

    // Crear tabla de logs de dispositivos
    $conn->exec('
        CREATE TABLE IF NOT EXISTS logs_dispositivos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dispositivo_id TINYINT NOT NULL,
            tipo_evento ENUM("conexion", "desconexion", "error", "mantenimiento", "verificacion") NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            empleado_id INT NULL,
            tipo_biometria ENUM("huella", "cara") NULL,
            resultado ENUM("exitoso", "fallido", "error") NOT NULL,
            mensaje TEXT,
            metadata JSON,
            FOREIGN KEY (empleado_id) REFERENCES empleados(id)
        )
    ');

    // Crear tabla de dispositivos biométricos
    $conn->exec('
        CREATE TABLE IF NOT EXISTS dispositivos_biometricos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dispositivo_id TINYINT NOT NULL UNIQUE,
            nombre VARCHAR(100) NOT NULL,
            tipo ENUM("huella", "cara", "dual") NOT NULL DEFAULT "dual",
            marca VARCHAR(50) NULL,
            modelo VARCHAR(50) NULL,
            ip_address VARCHAR(15) NULL,
            puerto SMALLINT DEFAULT 4370,
            usuario VARCHAR(50) NULL,
            password VARCHAR(255) NULL,
            activo BOOLEAN DEFAULT TRUE,
            configuracion JSON NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultima_conexion TIMESTAMP NULL,
            estado ENUM("conectado", "desconectado", "error") DEFAULT "desconectado"
        )
    ');

    // Agregar foreign key a logs_dispositivos
    try {
        $conn->exec('ALTER TABLE logs_dispositivos ADD CONSTRAINT fk_logs_dispositivo FOREIGN KEY (dispositivo_id) REFERENCES dispositivos_biometricos(dispositivo_id)');
    } catch (Exception $e) {
        // Foreign key ya existe, continuar
    }

    // Crear tabla de horarios laborales
    $conn->exec('
        CREATE TABLE IF NOT EXISTS horarios_laborales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            hora_entrada TIME NOT NULL,
            hora_salida TIME NOT NULL,
            tolerancia_minutos INT DEFAULT 9,
            descripcion TEXT,
            activo BOOLEAN DEFAULT TRUE,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Crear tabla de horarios por empleado
    $conn->exec('
        CREATE TABLE IF NOT EXISTS horarios_empleados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            horario_id INT NOT NULL,
            dia_semana ENUM("lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo") NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (empleado_id) REFERENCES empleados(id),
            FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id),
            UNIQUE KEY unique_empleado_dia (empleado_id, dia_semana)
        )
    ');

    // Crear tabla de tipos de justificación
    $conn->exec('
        CREATE TABLE IF NOT EXISTS tipos_justificacion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            requiere_aprobacion BOOLEAN DEFAULT TRUE,
            activo BOOLEAN DEFAULT TRUE
        )
    ');

    // Agregar columnas a tabla retardos
    try {
        $conn->exec('ALTER TABLE retardos ADD COLUMN horario_id INT NULL AFTER tipo');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE retardos ADD COLUMN tipo_justificacion_id INT NULL AFTER justificado');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE retardos ADD COLUMN motivo_justificacion TEXT NULL AFTER tipo_justificacion_id');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE retardos ADD COLUMN aprobado_por INT NULL AFTER motivo_justificacion');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE retardos ADD COLUMN fecha_aprobacion TIMESTAMP NULL AFTER aprobado_por');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }

    // Agregar columnas a tabla comisiones para normas AEFCM
    try {
        $conn->exec('ALTER TABLE comisiones ADD COLUMN tipo_comision VARCHAR(50) DEFAULT "otros" AFTER fecha_vencimiento');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE comisiones ADD COLUMN requiere_aprobacion BOOLEAN DEFAULT TRUE AFTER tipo_comision');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE comisiones ADD COLUMN aprobado_por INT NULL AFTER requiere_aprobacion');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE comisiones ADD COLUMN fecha_aprobacion TIMESTAMP NULL AFTER aprobado_por');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE comisiones ADD COLUMN motivo_aprobacion TEXT NULL AFTER fecha_aprobacion');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }

    // Agregar foreign keys a retardos
    try {
        $conn->exec('ALTER TABLE retardos ADD CONSTRAINT fk_retardos_horario FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id)');
    } catch (Exception $e) {
        // Foreign key ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE retardos ADD CONSTRAINT fk_retardos_tipo_just FOREIGN KEY (tipo_justificacion_id) REFERENCES tipos_justificacion(id)');
    } catch (Exception $e) {
        // Foreign key ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE retardos ADD CONSTRAINT fk_retardos_aprobado_por FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)');
    } catch (Exception $e) {
        // Foreign key ya existe, continuar
    }

    // Agregar foreign keys a comisiones
    try {
        $conn->exec('ALTER TABLE comisiones ADD CONSTRAINT fk_comisiones_aprobado_por FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)');
    } catch (Exception $e) {
        // Foreign key ya existe, continuar
    }

    // Crear índices
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_asistencia_dispositivo ON asistencia(dispositivo_id)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_asistencia_tipo_biometria ON asistencia(tipo_biometria)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_logs_dispositivo ON logs_dispositivos(dispositivo_id)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_logs_fecha ON logs_dispositivos(timestamp)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_logs_empleado ON logs_dispositivos(empleado_id)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_retardos_fecha ON retardos(fecha)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_retardos_tipo ON retardos(tipo)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_horarios_empleados_empleado ON horarios_empleados(empleado_id)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_horarios_empleados_dia ON horarios_empleados(dia_semana)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_horarios_laborales_activo ON horarios_laborales(activo)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_comisiones_tipo ON comisiones(tipo_comision)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_comisiones_aprobacion ON comisiones(requiere_aprobacion, aprobado_por)');
    $conn->exec('CREATE INDEX IF NOT EXISTS idx_comisiones_fecha_aprobacion ON comisiones(fecha_aprobacion)');

    echo "Base de datos actualizada exitosamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
