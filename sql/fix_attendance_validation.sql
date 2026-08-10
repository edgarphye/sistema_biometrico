-- Script: fix_attendance_validation.sql
-- Descripción: Validar registros de asistencia pendientes por RH
-- Fecha: 2026-07-23

-- Primero, verificamos la estructura de la tabla asistencia
DESCRIBE asistencia;

-- Crear tabla temporal con estadísticas de validación
CREATE TEMPORARY TABLE IF NOT EXISTS tmp_estadisticas AS
SELECT 
    COUNT(*) as total_registros,
    SUM(CASE WHEN validado_por_rh IS NULL OR validado_por_rh = '' THEN 1 ELSE 0 END) as sin_validar,
    SUM(CASE WHEN validado_por_rh IS NOT NULL AND validado_por_rh != '' THEN 1 ELSE 0 END) as validados
FROM asistencia;

-- Mostrar estadísticas iniciales
SELECT * FROM tmp_estadisticas;

-- Validar registros que tienen marcación completa (entrada y salida)
UPDATE asistencia 
SET 
    validado_por_rh = 'MIGRATION_AUTO',
    fecha_aprobacion_rh = NOW(),
    estado_validacion_rh = 'VALIDADO_AUTOMATICAMENTE'
WHERE (validado_por_rh IS NULL OR validado_por_rh = '')
  AND hora_entrada IS NOT NULL 
  AND hora_salida IS NOT NULL
  AND tipo_asistencia IS NOT NULL
  AND tipo_asistencia != 'sin_registro';

-- Marcar registros con inconsistencias para revisión manual
UPDATE asistencia 
SET 
    validado_por_rh = 'REVISION_MANUAL',
    estado_validacion_rh = 'REQUIERE_REVISION'
WHERE (validado_por_rh IS NULL OR validado_por_rh = '')
  AND (
      hora_entrada IS NULL 
      OR hora_salida IS NULL 
      OR tipo_asistencia IS NULL 
      OR tipo_asistencia = 'sin_registro'
  );

-- Limpiar tabla temporal
DROP TEMPORARY TABLE IF EXISTS tmp_estadisticas;

-- Verificar resultados finales
SELECT 
    COUNT(*) as total_registros,
    SUM(CASE WHEN validado_por_rh = 'MIGRATION_AUTO' THEN 1 ELSE 0 END) as validados_automaticamente,
    SUM(CASE WHEN validado_por_rh = 'REVISION_MANUAL' THEN 1 ELSE 0 END) as para_revision_manual,
    SUM(CASE WHEN validado_por_rh IS NULL OR validado_por_rh = '' THEN 1 ELSE 0 END) as sin_validar
FROM asistencia;

-- Mostrar registros que requieren revisión manual (primeros 20)
SELECT 
    id,
    empleado_id,
    fecha,
    hora_entrada,
    hora_salida,
    tipo_asistencia,
    validado_por_rh,
    estado_validacion_rh
FROM asistencia
WHERE validado_por_rh = 'REVISION_MANUAL'
ORDER BY fecha DESC
LIMIT 20;
