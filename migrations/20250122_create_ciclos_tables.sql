-- Tabla para definir los ciclos (ej. Turno Matutino Semanal)
CREATE TABLE IF NOT EXISTS ciclos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    num_ciclo INT DEFAULT 1,
    unidad_ciclo ENUM('Semana', 'Mes') DEFAULT 'Semana',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para los bloques de horario dentro de un ciclo
CREATE TABLE IF NOT EXISTS bloques_ciclo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ciclo_id INT NOT NULL,
    dia_semana INT NOT NULL COMMENT '0=Lunes, 1=Martes, ..., 6=Domingo',
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    horario_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_bloques_ciclo_ciclo 
        FOREIGN KEY (ciclo_id) 
        REFERENCES ciclos(id) 
        ON DELETE CASCADE,
        
    CONSTRAINT fk_bloques_ciclo_horario 
        FOREIGN KEY (horario_id) 
        REFERENCES horarios_laborales(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para mejorar rendimiento
CREATE INDEX idx_bloques_ciclo_dia ON bloques_ciclo(ciclo_id, dia_semana);
