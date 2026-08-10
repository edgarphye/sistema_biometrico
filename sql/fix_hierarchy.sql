-- Script: fix_hierarchy.sql
-- Descripción: Asignar jefes directos a empleados sin jefe_directo_id
-- Fecha: 2026-07-23

-- Primero, creamos una tabla temporal con la jerarquía del catálogo de mandos
CREATE TEMPORARY TABLE IF NOT EXISTS tmp_jefes AS
SELECT 
    cm.clave_area,
    cm.nombre_mando,
    cm.clave_depto,
    e.id as jefe_id
FROM catalogos_mandos cm
LEFT JOIN empleados e ON (
    e.activo = 1 
    AND (
        cm.nombre_mando LIKE CONCAT('%', e.nombre, '%', e.apellido, '%')
        OR cm.nombre_mando LIKE CONCAT('%', e.apellido, '%', e.nombre, '%')
    )
)
WHERE cm.activo = 1;

-- Asignar jefes directos a empleados basado en clave_depto
UPDATE empleados e
INNER JOIN tmp_jefes tj ON e.clave_depto = tj.clave_area
SET e.jefe_directo_id = tj.jefe_id
WHERE e.activo = 1 
  AND e.jefe_directo_id IS NULL
  AND tj.jefe_id IS NOT NULL;

-- Para empleados sin clave_depto, intentar asignar por área
UPDATE empleados e
INNER JOIN tmp_jefes tj ON e.area = tj.clave_area
SET e.jefe_directo_id = tj.jefe_id
WHERE e.activo = 1 
  AND e.jefe_directo_id IS NULL
  AND tj.jefe_id IS NOT NULL;

-- Registrar empleados que no pudieron ser asignados
SELECT 
    e.id,
    e.nombre,
    e.apellido,
    e.area,
    e.clave_depto,
    'Sin jefe asignado - requiere revisión manual' as motivo
FROM empleados e
WHERE e.activo = 1 
  AND e.jefe_directo_id IS NULL;

-- Limpiar tabla temporal
DROP TEMPORARY TABLE IF EXISTS tmp_jefes;

-- Verificar resultados
SELECT 
    COUNT(*) as total_empleados,
    SUM(CASE WHEN jefe_directo_id IS NOT NULL THEN 1 ELSE 0 END) as con_jefe,
    SUM(CASE WHEN jefe_directo_id IS NULL THEN 1 ELSE 0 END) as sin_jefe
FROM empleados 
WHERE activo = 1;
