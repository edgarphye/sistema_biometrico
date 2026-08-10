-- Esquema de base de datos para Sistema Biométrico

CREATE DATABASE IF NOT EXISTS sistema_biometrico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_biometrico;

-- Tabla de Empleados
CREATE TABLE IF NOT EXISTS empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    rfc VARCHAR(13) UNIQUE,
    curp VARCHAR(18) UNIQUE,
    email VARCHAR(100),
    telefono VARCHAR(20),
    area VARCHAR(50),
    puesto VARCHAR(50),
    jerarquia VARCHAR(50),
    sexo CHAR(1),
    fecha_nacimiento DATE,
    entidad_federativa VARCHAR(2),
    huella_dactilar TEXT,
    foto_cara VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    nombre_completo VARCHAR(100),
    rol ENUM('admin', 'user', 'viewer', 'rh', 'supervisor') DEFAULT 'user',
    empleado_id INT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL
);

-- Tabla de Horarios Laborales (Con soporte multi-sede)
CREATE TABLE IF NOT EXISTS horarios_laborales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    hora_entrada TIME NOT NULL,
    hora_salida TIME NOT NULL,
    tolerancia_minutos INT DEFAULT 10,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    sede VARCHAR(100) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_horario_sede (sede)
);

-- Tabla de Horarios por Empleado (Con soporte multi-sede)
CREATE TABLE IF NOT EXISTS horarios_empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    horario_id INT NOT NULL,
    dia_semana ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    sede VARCHAR(100) NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id),
    UNIQUE KEY unique_empleado_dia_sede (empleado_id, dia_semana, sede)
);

-- Tabla de Dispositivos Biométricos
CREATE TABLE IF NOT EXISTS dispositivos_biometricos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id TINYINT NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('huella', 'cara', 'dual') NOT NULL DEFAULT 'dual',
    ip_address VARCHAR(15) NULL,
    puerto SMALLINT DEFAULT 4370,
    activo BOOLEAN DEFAULT TRUE,
    sede VARCHAR(100) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Asistencia
CREATE TABLE IF NOT EXISTS asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME,
    hora_salida TIME,
    dispositivo_id TINYINT NULL,
    tipo_biometria ENUM('huella', 'cara') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de Tipos de Justificación
CREATE TABLE IF NOT EXISTS tipos_justificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    requiere_aprobacion BOOLEAN DEFAULT TRUE,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de Retardos (sincronizada con la BD real vía migraciones)
CREATE TABLE IF NOT EXISTS retardos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    zk_empleado_id_original VARCHAR(50) NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME NULL,
    hora_salida TIME NULL,
    fecha_asistencia DATE NULL,
    asistencia_id INT NULL,
    dia_semana ENUM('lunes','martes','miércoles','jueves','viernes','sábado','domingo') NULL,
    tipo_registro ENUM('entrada','salida','ambos') DEFAULT 'entrada',
    unique_dia_empleado VARCHAR(50) NULL,
    minutos_retardo INT NOT NULL DEFAULT 0,
    tipo_retraso ENUM('normal','retardo_menor','retardo_mayor','falta','comision_entrada','comision_salida','comision_todo_dia','dia_economico','ausencia') NOT NULL,
    tipo_asistencia ENUM('entrada','salida','completa') DEFAULT 'entrada',
    categoria_principal ENUM('asistencia_normal','retardo','comision','dia_economico','ausencia') DEFAULT 'asistencia_normal',
    hora_registro TIME NULL,
    motivo_detalle TEXT NULL,
    requiere_validacion_jefe TINYINT(1) DEFAULT 0,
    evidencia_adjunta VARCHAR(255) NULL,
    estado_validacion VARCHAR(50) NULL,
    justificado TINYINT(1) DEFAULT 0,
    horario_id INT NULL,
    tipo_justificacion_id INT NULL,
    motivo_justificacion TEXT NULL,
    aprobado_por INT NULL,
    fecha_aprobacion TIMESTAMP NULL,
    soporte VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validado_por_jefe TINYINT(1) DEFAULT 0,
    justificacion_bloqueada TINYINT(1) DEFAULT 0,
    INDEX idx_retardos_fecha (fecha),
    INDEX idx_retardos_tipo (tipo_retraso),
    INDEX idx_retardos_dia_empleado (unique_dia_empleado),
    INDEX idx_retardos_fecha_empleado (fecha, empleado_id),
    INDEX idx_retardos_tipo_estado (tipo_retraso, estado_validacion),
    INDEX idx_retardos_empleado (empleado_id),
    INDEX idx_retardos_horario (horario_id),
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id),
    FOREIGN KEY (tipo_justificacion_id) REFERENCES tipos_justificacion(id),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)
);

-- Tabla de Sanciones
CREATE TABLE IF NOT EXISTS sanciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo_sancion ENUM('amonestacion', 'suspension', 'acta_administrativa', 'otro') NOT NULL,
    motivo TEXT NOT NULL,
    fecha_sancion DATE NOT NULL,
    estatus ENUM('activa', 'cumplida', 'cancelada') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de Justificaciones (General)
CREATE TABLE IF NOT EXISTS justificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo_justificacion VARCHAR(50) NOT NULL,
    motivo TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estatus ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
    aprobado_por INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)
);

-- Tabla de Comisiones
CREATE TABLE IF NOT EXISTS comisiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estatus ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
    tipo_comision VARCHAR(50) DEFAULT 'otros',
    requiere_aprobacion BOOLEAN DEFAULT TRUE,
    aprobado_por INT NULL,
    fecha_aprobacion TIMESTAMP NULL,
    motivo_aprobacion TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)
);

-- Tabla de Ausencias (Licencias medicas, etc)
CREATE TABLE IF NOT EXISTS ausencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo ENUM('medica', 'personal', 'vacaciones', 'otro') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    fecha_limite_justificacion DATE NULL,
    motivo TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de Notificaciones de Licencias
CREATE TABLE IF NOT EXISTS notificaciones_licencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio DATETIME NOT NULL,
    estado ENUM('pendiente', 'enviada', 'fallida') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de Días Económicos
CREATE TABLE IF NOT EXISTS dias_economicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    fecha DATE NOT NULL,
    motivo TEXT,
    estatus ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
);

-- Tabla de Logs de Dispositivos
CREATE TABLE IF NOT EXISTS logs_dispositivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id TINYINT NOT NULL,
    tipo_evento VARCHAR(50),
    mensaje TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuario admin por defecto (Password: admin123)
INSERT IGNORE INTO usuarios (username, password, email, rol, nombre_completo) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sistema.local', 'admin', 'Administrador');

-- Tabla de Configuración de Menú por Usuario
CREATE TABLE IF NOT EXISTS menu_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    menu_path VARCHAR(100) NOT NULL,
    visible TINYINT(1) DEFAULT 1,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_menu (usuario_id, menu_path)
);
