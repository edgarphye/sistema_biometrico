-- Base de datos de testing para Sistema Biométrico
-- Crear base de datos y tablas para pruebas automatizadas

-- Crear base de datos de testing
CREATE DATABASE IF NOT EXISTS sistema_biometrico_test 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE sistema_biometrico_test;

-- Tabla de empleados (datos de prueba)
CREATE TABLE IF NOT EXISTS empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    rfc VARCHAR(13) UNIQUE NOT NULL,
    curp VARCHAR(18) UNIQUE,
    email VARCHAR(100),
    telefono VARCHAR(20),
    area VARCHAR(50),
    puesto VARCHAR(50),
    activo TINYINT(1) DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    foto_cara TEXT,
    foto_huella TEXT,
    INDEX idx_empleado_nombre (nombre),
    INDEX idx_empleado_activo (activo),
    INDEX idx_empleado_rfc (rfc)
);

-- Tabla de usuarios (para autenticación)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    rol ENUM('admin', 'user', 'viewer') DEFAULT 'user',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    INDEX idx_usuario_username (username),
    INDEX idx_usuario_email (email),
    INDEX idx_usuario_rol (rol)
);

-- Tabla de asistencia
CREATE TABLE IF NOT EXISTS asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME,
    hora_salida TIME,
    dispositivo_entrada VARCHAR(50),
    dispositivo_salida VARCHAR(50),
    tipo_registro ENUM('entrada', 'salida', 'ambos') DEFAULT 'ambos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_asistencia_empleado (empleado_id),
    INDEX idx_asistencia_fecha (fecha),
    INDEX idx_asistencia_empleado_fecha (empleado_id, fecha)
);

-- Tabla de días económicos
CREATE TABLE IF NOT EXISTS dias_economicos (
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
    motivo_rechazo TEXT,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (solicitado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_dias_economicos_empleado (empleado_id),
    INDEX idx_dias_economicos_estatus (estatus),
    INDEX idx_dias_economicos_fecha (fecha)
);

-- Tabla de retardos
CREATE TABLE IF NOT EXISTS retardos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    fecha DATE NOT NULL,
    minutos_retraso INT NOT NULL,
    tipo_retraso ENUM('menor', 'mayor') NOT NULL,
    justificado TINYINT(1) DEFAULT 0,
    motivo_justificacion TEXT,
    fecha_justificacion TIMESTAMP NULL,
    aprobado_por INT,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_retardos_empleado (empleado_id),
    INDEX idx_retardos_fecha (fecha),
    INDEX idx_retardos_tipo (tipo_retraso)
);

-- Tabla de sanciones
CREATE TABLE IF NOT EXISTS sanciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo_sancion ENUM('amonestacion', 'suspension', 'otro') NOT NULL,
    motivo TEXT NOT NULL,
    fecha_sancion DATE NOT NULL,
    estatus ENUM('activa', 'cumplida', 'cancelada') DEFAULT 'activa',
    creado_por INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_sanciones_empleado (empleado_id),
    INDEX idx_sanciones_estatus (estatus),
    INDEX idx_sanciones_fecha (fecha_sancion)
);

-- Tabla de dispositivos biométricos
CREATE TABLE IF NOT EXISTS dispositivos_biometricos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ip VARCHAR(15) NOT NULL,
    puerto INT DEFAULT 4370,
    modelo VARCHAR(50),
    numero_serie VARCHAR(50),
    ubicacion VARCHAR(100),
    estatus ENUM('activo', 'inactivo', 'mantenimiento') DEFAULT 'activo',
    fecha_instalacion DATE,
    ultimo_sincronizacion TIMESTAMP NULL,
    creado_por INT,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_dispositivos_estatus (estatus),
    INDEX idx_dispositivos_ip (ip)
);

-- Insertar datos de prueba
INSERT INTO empleados (nombre, apellido, rfc, email, telefono, area, puesto) VALUES
('Juan', 'Pérez', 'PEJU800101HDFXXX01', 'juan.perez@test.com', '5551234567', 'TI', 'Desarrollador'),
('María', 'González', 'GOMA750123MDFXXX02', 'maria.gonzalez@test.com', '5559876543', 'RH', 'Gerente'),
('Pedro', 'López', 'LOPE900715HDFXXX03', 'pedro.lopez@test.com', '5554567890', 'Finanzas', 'Analista'),
('Ana', 'Martínez', 'MARA850320HDFXXX04', 'ana.martinez@test.com', '5552345678', 'Ventas', 'Ejecutiva'),
('Luis', 'Rodríguez', 'ROLU821205HDFXXX05', 'luis.rodriguez@test.com', '5558765432', 'Operaciones', 'Supervisor');

INSERT INTO usuarios (username, password, email, rol) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@test.com', 'admin'),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@test.com', 'user'),
('viewer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer@test.com', 'viewer');

INSERT INTO dispositivos_biometricos (nombre, ip, puerto, modelo, ubicacion, estatus, fecha_instalacion) VALUES
('Dispositivo Principal', '192.168.1.100', 4370, 'TA100C', 'Entrada Principal', 'activo', '2025-01-01'),
('Dispositivo Secundario', '192.168.1.101', 4370, 'TA100C', 'Salida Principal', 'activo', '2025-01-01');

INSERT INTO dias_economicos (empleado_id, fecha, motivo, estatus, solicitado_por) VALUES
(1, '2025-12-15', 'Asuntos personales', 'pendiente', 1),
(2, '2025-12-20', 'Cita médica', 'aprobado', 1),
(3, '2025-12-25', 'Viaje familiar', 'rechazado', 1);

INSERT INTO retardos (empleado_id, fecha, minutos_retraso, tipo_retraso, justificado) VALUES
(1, '2025-12-01', 15, 'menor', 0),
(2, '2025-12-02', 45, 'mayor', 1),
(3, '2025-12-03', 25, 'menor', 0);

INSERT INTO sanciones (empleado_id, tipo_sancion, motivo, fecha_sancion, estatus, creado_por) VALUES
(3, 'amonestacion', 'Retardo mayor recurrente', '2025-12-05', 'activa', 1);

-- Confirmar creación
SELECT 'Base de datos de testing creada exitosamente' as mensaje;