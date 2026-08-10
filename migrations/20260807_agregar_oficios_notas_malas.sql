-- Migración: Módulo de generación de oficios "ATENTA NOTA" por notas malas
-- Fecha: 2026-08-07
-- Cambios: claves_presupuestales, config_oficios, normalización de notas_malas.periodo

-- Tabla de claves presupuestales del empleado (uno-a-muchos por empleado_id)
CREATE TABLE IF NOT EXISTS claves_presupuestales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    clave VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_cp_empleado (empleado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración del oficio ATENTA NOTA
CREATE TABLE IF NOT EXISTS config_oficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prefijo VARCHAR(50) NOT NULL DEFAULT 'DGIFA/CA/RH',
    anio SMALLINT NOT NULL,
    ultimo_folio INT NOT NULL DEFAULT 0,
    nombre_firmante VARCHAR(150) DEFAULT 'JUAN JOSE OROZCO PONCE',
    cargo_firmante VARCHAR(200) DEFAULT 'JEFE DEL DEPARTAMENTO DE RECURSOS HUMANOS',
    iniciales VARCHAR(20) DEFAULT 'DGNQ*',
    membrete_path VARCHAR(255) DEFAULT 'assets/images/membrete_sep.png',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_config_anio (anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila inicial de configuración para el año en curso (idempotente)
INSERT IGNORE INTO config_oficios (prefijo, anio, ultimo_folio, nombre_firmante, cargo_firmante, iniciales, membrete_path)
SELECT 'DGIFA/CA/RH', YEAR(CURDATE()), 0, 'JUAN JOSE OROZCO PONCE', 'JEFE DEL DEPARTAMENTO DE RECURSOS HUMANOS', 'DGNQ*', 'assets/images/membrete_sep.png'
WHERE NOT EXISTS (SELECT 1 FROM config_oficios WHERE anio = YEAR(CURDATE()));

-- Columna de quincena en documentos_generados para distinguir oficios del mismo mes
ALTER TABLE documentos_generados ADD COLUMN IF NOT EXISTS quincena TINYINT NOT NULL DEFAULT 0;

-- Normalizar notas_malas.periodo según la fecha del retardo referenciado
-- (solo registros cuyo retardo_id apunta a un retardo real)
UPDATE notas_malas nm
JOIN retardos r ON nm.retardo_id = r.id
SET nm.periodo = DATE_FORMAT(r.fecha, '%Y-%m-01');
