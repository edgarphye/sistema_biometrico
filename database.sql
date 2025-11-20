-- Esquema de base de datos para Sistema Biométrico

CREATE DATABASE IF NOT EXISTS sistema_biometrico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_biometrico;

-- Tabla de empleados
CREATE TABLE empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    rfc VARCHAR(13) UNIQUE NOT NULL,
    curp VARCHAR(18) UNIQUE NOT NULL,
    area VARCHAR(100) NOT NULL,
    jerarquia VARCHAR(50) NOT NULL,
    huella_dactilar BLOB, -- Almacenar datos de huella
    foto_cara VARCHAR(255), -- Ruta del archivo de foto
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de asistencia
CREATE TABLE asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo ENUM('entrada', 'salida') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dispositivo_id TINYINT NOT NULL, -- ID del dispositivo biométrico (1-10)
    tipo_biometria ENUM('huella', 'cara') NOT NULL, -- Tipo de verificación usada
    datos_biometricos JSON, -- Datos biométricos capturados (template, calidad, etc.)
    calidad_verificacion TINYINT, -- Calidad de la verificación (0-100)
    metadata_dispositivo JSON, -- Información del dispositivo (temperatura, firmware, etc.)
    tiempo_procesamiento DECIMAL(5,3), -- Tiempo en segundos para procesar verificación
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de horarios laborales (catálogo)
CREATE TABLE horarios_laborales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    hora_entrada TIME NOT NULL,
    hora_salida TIME NOT NULL,
    tolerancia_minutos INT DEFAULT 9,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de horarios por empleado (1-2 horarios por empleado)
CREATE TABLE horarios_empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    horario_id INT NOT NULL,
    dia_semana ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id),
    UNIQUE KEY unique_empleado_dia (empleado_id, dia_semana)
);

-- Tabla de tipos de justificación
CREATE TABLE tipos_justificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    requiere_aprobacion BOOLEAN DEFAULT TRUE,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de retardos
CREATE TABLE retardos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    fecha DATE NOT NULL,
    minutos_retardo INT NOT NULL,
    tipo ENUM('menor', 'mayor') NOT NULL,
    horario_id INT NULL, -- Horario específico que se aplicó
    justificado BOOLEAN DEFAULT FALSE,
    tipo_justificacion_id INT NULL,
    motivo_justificacion TEXT,
    aprobado_por INT NULL, -- ID del usuario que aprobó
    fecha_aprobacion TIMESTAMP NULL,
    soporte VARCHAR(255) NULL,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id),
    FOREIGN KEY (tipo_justificacion_id) REFERENCES tipos_justificacion(id),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)
);

-- Tabla de sanciones (suspensiones, amonestaciones, propuestas de terminación)
CREATE TABLE sanciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo ENUM('suspension','amonestacion','terminacion_propuesta') NOT NULL,
    fecha_inicio DATE NOT NULL,
    dias INT DEFAULT 0,
    motivo TEXT,
    creado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

-- Tabla de comisiones
CREATE TABLE comisiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_asignacion DATE NOT NULL,
    fecha_vencimiento DATE,
    justificada BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de ausencias
CREATE TABLE ausencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    tipo ENUM('enfermedad', 'vacaciones', 'permiso', 'otro') NOT NULL,
    justificada BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de usuarios (para acceso al sistema)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Hash de contraseña
    rol ENUM('admin', 'usuario') DEFAULT 'usuario',
    empleado_id INT,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de configuración de dispositivos biométricos
CREATE TABLE dispositivos_biometricos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id TINYINT NOT NULL UNIQUE, -- ID único del dispositivo (1-10)
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('huella', 'cara', 'dual') DEFAULT 'dual',
    marca VARCHAR(50),
    modelo VARCHAR(50),
    ip_address VARCHAR(15),
    puerto INT DEFAULT 4370,
    usuario VARCHAR(50),
    password VARCHAR(255), -- Hash de contraseña
    activo BOOLEAN DEFAULT TRUE,
    configuracion JSON, -- Configuración específica del dispositivo
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_conexion TIMESTAMP NULL,
    estado ENUM('conectado', 'desconectado', 'error') DEFAULT 'desconectado'
);

-- Tabla de logs de dispositivos biométricos
CREATE TABLE logs_dispositivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id TINYINT NOT NULL,
    tipo_evento ENUM('conexion', 'desconexion', 'error', 'mantenimiento', 'verificacion', 'registro') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    empleado_id INT NULL, -- NULL si no hay empleado asociado
    tipo_biometria ENUM('huella', 'cara') NULL,
    resultado ENUM('exitoso', 'fallido', 'error') NOT NULL,
    mensaje TEXT, -- Detalles del evento
    metadata JSON, -- Información adicional del evento
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos_biometricos(dispositivo_id)
);

-- Índices para mejorar rendimiento
CREATE INDEX idx_asistencia_empleado ON asistencia(empleado_id);
CREATE INDEX idx_asistencia_fecha ON asistencia(timestamp);
CREATE INDEX idx_asistencia_dispositivo ON asistencia(dispositivo_id);
CREATE INDEX idx_asistencia_tipo_biometria ON asistencia(tipo_biometria);
CREATE INDEX idx_logs_dispositivo ON logs_dispositivos(dispositivo_id);
CREATE INDEX idx_logs_fecha ON logs_dispositivos(timestamp);
CREATE INDEX idx_logs_empleado ON logs_dispositivos(empleado_id);
CREATE INDEX idx_retardos_empleado ON retardos(empleado_id);
CREATE INDEX idx_retardos_fecha ON retardos(fecha);
CREATE INDEX idx_retardos_tipo ON retardos(tipo);
CREATE INDEX idx_horarios_empleados_empleado ON horarios_empleados(empleado_id);
CREATE INDEX idx_horarios_empleados_dia ON horarios_empleados(dia_semana);
CREATE INDEX idx_horarios_laborales_activo ON horarios_laborales(activo);
CREATE INDEX idx_comisiones_empleado ON comisiones(empleado_id);
CREATE INDEX idx_ausencias_empleado ON ausencias(empleado_id);
CREATE INDEX idx_dispositivos_id ON dispositivos_biometricos(dispositivo_id);
CREATE INDEX idx_dispositivos_activo ON dispositivos_biometricos(activo);
CREATE INDEX idx_dispositivos_estado ON dispositivos_biometricos(estado);
