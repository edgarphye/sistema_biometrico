-- =============================================
-- CATÁLOGOS: DIRECCIONES, SUBDIRECCIONES Y DEPARTAMENTOS
-- =============================================

-- Tabla de Direcciones (Level 1)
CREATE TABLE IF NOT EXISTS direcciones (
    clave_dir VARCHAR(10) PRIMARY KEY,
    nombre_direccion VARCHAR(150) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Subdirecciones (Level 2) - Pertenece a una Dirección
CREATE TABLE IF NOT EXISTS subdirecciones (
    clave_subdir VARCHAR(10) PRIMARY KEY,
    clave_direccion VARCHAR(10) NOT NULL,
    nombre_subdir VARCHAR(150) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (clave_direccion) REFERENCES direcciones(clave_dir) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Departamentos (Level 3) - Pertenece a una Subdirección
CREATE TABLE IF NOT EXISTS departamentos (
    clave_depto VARCHAR(10) PRIMARY KEY,
    clave_subdireccion VARCHAR(10) NOT NULL,
    nombre_departamento VARCHAR(150) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (clave_subdireccion) REFERENCES subdirecciones(clave_subdir) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campo departamento a empleados (si no existe)
-- ALTER TABLE empleados ADD COLUMN clave_departamento VARCHAR(10) AFTER area;
-- ALTER TABLE empleados ADD FOREIGN KEY (clave_departamento) REFERENCES departamentos(clave_depto) ON DELETE SET NULL;

-- =============================================
-- DATOS DE EJEMPLO
-- =============================================

INSERT INTO direcciones (clave_dir, nombre_direccion) VALUES 
('DIR01', 'Dirección General'),
('DIR02', 'Dirección de Administración'),
('DIR03', 'Dirección de Operaciones'),
('DIR04', 'Dirección de Tecnología')
ON DUPLICATE KEY UPDATE nombre_direccion = VALUES(nombre_direccion);

INSERT INTO subdirecciones (clave_subdir, clave_direccion, nombre_subdir) VALUES 
('SUB01', 'DIR01', 'Secretaría Particular'),
('SUB02', 'DIR01', 'Unidad de Comunicación'),
('SUB03', 'DIR02', 'Recursos Humanos'),
('SUB04', 'DIR02', 'Recursos Materiales'),
('SUB05', 'DIR03', 'Operaciones'),
('SUB06', 'DIR03', 'Mantenimiento'),
('SUB07', 'DIR04', 'Sistemas'),
('SUB08', 'DIR04', 'Infraestructura')
ON DUPLICATE KEY UPDATE nombre_subdir = VALUES(nombre_subdir);

INSERT INTO departamentos (clave_depto, clave_subdireccion, nombre_departamento) VALUES 
('DEP01', 'SUB01', 'Secretaría Particular'),
('DEP02', 'SUB01', 'Protocolo'),
('DEP03', 'SUB02', 'Comunicación Social'),
('DEP04', 'SUB03', 'Nómina'),
('DEP05', 'SUB03', 'Prestaciones'),
('DEP06', 'SUB04', 'Almacén'),
('DEP07', 'SUB04', 'Compras'),
('DEP08', 'SUB05', 'Control de Asistencia'),
('DEP09', 'SUB06', 'Mantenimiento General'),
('DEP10', 'SUB07', 'Desarrollo de Software'),
('DEP11', 'SUB07', 'Soporte Técnico'),
('DEP12', 'SUB08', 'Redes y Telecomunicaciones')
ON DUPLICATE KEY UPDATE nombre_departamento = VALUES(nombre_departamento);

-- Verificar estructura
SELECT 'direcciones' as tabla, COUNT(*) as registros FROM direcciones
UNION ALL
SELECT 'subdirecciones', COUNT(*) FROM subdirecciones
UNION ALL
SELECT 'departamentos', COUNT(*) FROM departamentos;
