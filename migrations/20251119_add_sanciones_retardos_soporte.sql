-- Migration: Add soporte column to retardos and create sanciones table if missing
-- Run with: mysql -u <user> -p < database.sql OR use the commands below

-- Add 'soporte' column to 'retardos' if not exists (MySQL 8+)
ALTER TABLE retardos
    ADD COLUMN IF NOT EXISTS soporte VARCHAR(255) NULL;

-- Create 'sanciones' table if not exists
CREATE TABLE IF NOT EXISTS sanciones (
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

-- Add indexes if not present
CREATE INDEX IF NOT EXISTS idx_sanciones_empleado ON sanciones(empleado_id);
