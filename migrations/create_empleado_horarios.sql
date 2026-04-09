-- Tabla para el historial de asignación de horarios y ciclos
CREATE TABLE IF NOT EXISTS empleado_horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    horario_id INT NULL COMMENT 'ID del horario fijo si aplica',
    ciclo_id INT NULL COMMENT 'ID del ciclo rotativo si aplica',
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL COMMENT 'NULL indica que es el horario vigente indefinidamente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Claves foráneas
    CONSTRAINT fk_eh_empleado FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    CONSTRAINT fk_eh_horario FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id) ON DELETE SET NULL,
    CONSTRAINT fk_eh_ciclo FOREIGN KEY (ciclo_id) REFERENCES ciclos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
