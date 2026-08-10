-- Script: check_integrity.sql
-- Descripción: Verificar integridad referencial de la base de datos
-- Fecha: 2026-07-23

-- Verificar empleados sin jefe directo (debería estar vacío después de migración)
SELECT 
    'Empleados sin jefe_directo_id' as verificacion,
    COUNT(*) as total,
    CASE WHEN COUNT(*) > 0 THEN 'REVISAR' ELSE 'OK' END as estado
FROM empleados 
WHERE activo = 1 AND jefe_directo_id IS NULL;

-- Verificar empleados sin zkteo_id (debería estar vacío después de migración)
SELECT 
    'Empleados sin zkteo_id' as verificacion,
    COUNT(*) as total,
    CASE WHEN COUNT(*) > 0 THEN 'REVISAR' ELSE 'OK' END as estado
FROM empleados 
WHERE activo = 1 AND (zkteo_id IS NULL OR zkteo_id = '');

-- Verificar retardos sin horario_id (debería estar vacío después de migración)
SELECT 
    'Retardos sin horario_id' as verificacion,
    COUNT(*) as total,
    CASE WHEN COUNT(*) > 0 THEN 'REVISAR' ELSE 'OK' END as estado
FROM retardos 
WHERE horario_id IS NULL;

-- Verificar asistencia sin validar por RH (puede tener registros pendientes)
SELECT 
    'Asistencia sin validar por RH' as verificacion,
    COUNT(*) as total,
    CASE WHEN COUNT(*) > 1000 THEN 'REVISAR' ELSE 'OK' END as estado
FROM asistencia 
WHERE validado_por_rh IS NULL OR validado_por_rh = '';

-- Verificar empleados con jefe_directo_id inválido
SELECT 
    'Empleados con jefe_directo_id inválido' as verificacion,
    COUNT(*) as total,
    CASE WHEN COUNT(*) > 0 THEN 'ERROR' ELSE 'OK' END as estado
FROM empleados e
LEFT JOIN empleados j ON e.jefe_directo_id = j.id
WHERE e.activo = 1 
  AND e.jefe_directo_id IS NOT NULL 
  AND j.id IS NULL;

-- Verificar retardos con horario_id inválido
SELECT 
    'Retardos con horario_id inválido' as verificacion,
    COUNT(*) as total,
    CASE WHEN COUNT(*) > 0 THEN 'ERROR' ELSE 'OK' END as estado
FROM retardos r
LEFT JOIN horarios_laborales h ON r.horario_id = h.id
WHERE r.horario_id IS NOT NULL 
  AND h.id IS NULL;

-- Verificar duplicados de zkteo_id
SELECT 
    'Empleados con zkteo_id duplicado' as verificacion,
    COUNT(*) as total,
    CASE WHEN COUNT(*) > 0 THEN 'ERROR' ELSE 'OK' END as estado
FROM (
    SELECT zkteo_id, COUNT(*) as cnt
    FROM empleados
    WHERE activo = 1 AND zkteo_id IS NOT NULL
    GROUP BY zkteo_id
    HAVING COUNT(*) > 1
) duplicates;

-- Resumen de integridad
SELECT 
    'RESUMEN DE INTEGRIDAD' as titulo,
    (SELECT COUNT(*) FROM empleados WHERE activo=1) as total_empleados,
    (SELECT COUNT(*) FROM retardos) as total_retardos,
    (SELECT COUNT(*) FROM asistencia) as total_asistencia;
