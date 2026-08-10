-- Agregar columnas de seguimiento de modificaciones a retardos
ALTER TABLE retardos 
ADD COLUMN modificado_por INT NULL,
ADD COLUMN fecha_modificacion TIMESTAMP NULL,
ADD COLUMN motivo_modificacion TEXT NULL,
ADD COLUMN validado_por INT NULL,
ADD COLUMN fecha_validacion TIMESTAMP NULL,
ADD COLUMN es_validacion_jefe TINYINT(1) DEFAULT 0;

-- Agregar columnas de seguimiento a asistencia si no existen
ALTER TABLE asistencia 
ADD COLUMN tipo_asistencia VARCHAR(50) NULL,
ADD COLUMN observaciones TEXT NULL;

-- Agregar columnas de seguimiento a asistencia para justificaciones
ALTER TABLE asistencia 
ADD COLUMN modificado_por INT NULL,
ADD COLUMN fecha_modificacion TIMESTAMP NULL,
ADD COLUMN motivo_modificacion TEXT NULL,
ADD COLUMN validado_por INT NULL,
ADD COLUMN fecha_validacion TIMESTAMP NULL,
ADD COLUMN es_validacion_jefe TINYINT(1) DEFAULT 0;

-- Tabla para registrar cambios de justificación
CREATE TABLE IF NOT EXISTS modificaciones_justificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla_origen ENUM('asistencia', 'retardos') NOT NULL,
    registro_id INT NOT NULL,
    empleado_id INT NOT NULL,
    campo_modificado VARCHAR(50) NOT NULL,
    valor_anterior TEXT,
    valor_nuevo TEXT,
    modificado_por INT NOT NULL,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    motivo_modificacion TEXT,
    validado_por INT NULL,
    fecha_validacion TIMESTAMP NULL,
    es_validacion_jefe TINYINT(1) DEFAULT 0,
    estatus ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id),
    FOREIGN KEY (validado_por) REFERENCES usuarios(id)
);
