-- Tabla para asignar ciclos a empleados
CREATE TABLE IF NOT EXISTS empleados_ciclos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    ciclo_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_empleados_ciclos_empleado
        FOREIGN KEY (empleado_id)
        REFERENCES empleados(id)
        ON DELETE CASCADE,
        
    CONSTRAINT fk_empleados_ciclos_ciclo
        FOREIGN KEY (ciclo_id)
        REFERENCES ciclos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para búsquedas rápidas
CREATE INDEX idx_empleados_ciclos_activo ON empleados_ciclos(empleado_id, activo);