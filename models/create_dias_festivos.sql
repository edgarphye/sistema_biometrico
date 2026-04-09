-- Crear tabla de días festivos
CREATE TABLE IF NOT EXISTS dias_festivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar días festivos de prueba (Ejemplo para México 2024-2025)
INSERT INTO dias_festivos (fecha, nombre, descripcion) VALUES 
('2024-01-01', 'Año Nuevo', 'Inicio de año'),
('2024-02-05', 'Día de la Constitución', 'Primer lunes de febrero en conmemoración del 5 de febrero'),
('2024-03-18', 'Natalicio de Benito Juárez', 'Tercer lunes de marzo en conmemoración del 21 de marzo'),
('2024-05-01', 'Día del Trabajo', 'Día internacional de los trabajadores'),
('2024-09-16', 'Día de la Independencia', 'Aniversario del inicio de la guerra de independencia'),
('2024-11-18', 'Revolución Mexicana', 'Tercer lunes de noviembre en conmemoración del 20 de noviembre'),
('2024-12-25', 'Navidad', 'Celebración de Navidad'),
('2025-01-01', 'Año Nuevo', 'Inicio de año'),
('2025-02-03', 'Día de la Constitución', 'Primer lunes de febrero en conmemoración del 5 de febrero'),
('2025-03-17', 'Natalicio de Benito Juárez', 'Tercer lunes de marzo en conmemoración del 21 de marzo'),
('2025-05-01', 'Día del Trabajo', 'Día internacional de los trabajadores'),
('2025-09-16', 'Día de la Independencia', 'Aniversario del inicio de la guerra de independencia'),
('2025-11-17', 'Revolución Mexicana', 'Tercer lunes de noviembre en conmemoración del 20 de noviembre'),
('2025-12-25', 'Navidad', 'Celebración de Navidad'),
('2026-01-01', 'Año Nuevo', 'Inicio de año'),
('2026-02-02', 'Día de la Constitución', 'Primer lunes de febrero en conmemoración del 5 de febrero'),
('2026-03-16', 'Natalicio de Benito Juárez', 'Tercer lunes de marzo en conmemoración del 21 de marzo'),
('2026-05-01', 'Día del Trabajo', 'Día internacional de los trabajadores'),
('2026-09-16', 'Día de la Independencia', 'Aniversario del inicio de la guerra de independencia'),
('2026-11-16', 'Revolución Mexicana', 'Tercer lunes de noviembre en conmemoración del 20 de noviembre'),
('2026-12-25', 'Navidad', 'Celebración de Navidad')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
