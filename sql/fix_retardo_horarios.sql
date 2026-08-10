-- Script: fix_retardo_horarios.sql
-- Descripción: Asignar horario_id a retardos existentes
-- Fecha: 2026-07-23

-- Primero, verificamos la estructura de la tabla retardos
DESCRIBE retardos;

-- Asignar horario matutino (id=1) a todos los retardos sin horario_id
-- Nota: La tabla empleados no tiene columna horario_id, usamos horario por defecto
UPDATE retardos 
SET horario_id = 1
WHERE horario_id IS NULL;

-- Verificar resultados
SELECT 
    COUNT(*) as total_retardos,
    SUM(CASE WHEN horario_id IS NOT NULL THEN 1 ELSE 0 END) as con_horario,
    SUM(CASE WHEN horario_id IS NULL THEN 1 ELSE 0 END) as sin_horario
FROM retardos;

-- Mostrar distribución de horarios en retardos
SELECT 
    horario_id,
    COUNT(*) as cantidad
FROM retardos
GROUP BY horario_id
ORDER BY horario_id;
