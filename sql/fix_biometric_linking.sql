-- Script: fix_biometric_linking.sql
-- Descripción: Asignar zkteo_id a empleados sin vinculación biométrica
-- Fecha: 2026-07-23

-- Primero, verificamos si hay dispositivos configurados
SELECT COUNT(*) as dispositivos_configurados FROM dispositivos_biometricos;

-- Asignar zkteo_id basado en el ID del empleado (formato: EMP-{id})
UPDATE empleados 
SET zkteo_id = CONCAT('EMP-', id)
WHERE activo = 1 
  AND (zkteo_id IS NULL OR zkteo_id = '');

-- Para empleados con ID numérico alto, usar formato secuencial
UPDATE empleados 
SET zkteo_id = CONCAT('ZK-', LPAD(id, 4, '0'))
WHERE activo = 1 
  AND zkteo_id LIKE 'EMP-%'
  AND id > 1000;

-- Registrar empleados actualizados
SELECT 
    id,
    nombre,
    apellido,
    zkteo_id,
    'Asignado automáticamente' as metodo
FROM empleados 
WHERE activo = 1 
  AND zkteo_id LIKE 'EMP-%' OR zkteo_id LIKE 'ZK-%';

-- Verificar resultados
SELECT 
    COUNT(*) as total_empleados,
    SUM(CASE WHEN zkteo_id IS NOT NULL AND zkteo_id != '' THEN 1 ELSE 0 END) as con_zkteo,
    SUM(CASE WHEN zkteo_id IS NULL OR zkteo_id = '' THEN 1 ELSE 0 END) as sin_zkteo
FROM empleados 
WHERE activo = 1;
