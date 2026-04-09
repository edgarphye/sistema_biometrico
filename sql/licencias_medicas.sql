-- Tabla para licencias médicas
CREATE TABLE IF NOT EXISTS licencias_medicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    incidencia_id INT DEFAULT NULL,
    folio VARCHAR(50) NOT NULL,
    dias_otorgados INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    diagnostico TEXT,
    fundamentos TEXT,
    tipo_sueldo ENUM('full', 'half') DEFAULT 'full',
    estatus ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
    creado_por INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha_inicio (fecha_inicio),
    INDEX idx_estatus (estatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
