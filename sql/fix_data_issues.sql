-- ============================================================
-- SCRIPT DE CORRECCIÓN DE DATOS - Sistema Biométrico
-- Fecha: 2026-07-23
-- Descripción: Corrige problemas de integridad de datos
-- ============================================================

USE sistema_biometrico;

-- ============================================================
-- 1. ASIGNAR HORARIOS A EMPLEADOS SIN HORARIO
-- ============================================================
-- Horario por defecto: matutino (id=1) 09:00-16:00
-- Se asigna a empleados activos que no tienen horario ni ciclo

INSERT INTO empleado_horarios (empleado_id, horario_id, fecha_inicio, tipo_horario, created_at, updated_at)
SELECT 
    e.id as empleado_id,
    1 as horario_id,  -- matutino
    COALESCE(e.fecha_ingreso, '2026-01-01') as fecha_inicio,
    'FIJO' as tipo_horario,
    NOW() as created_at,
    NOW() as updated_at
FROM empleados e
WHERE e.activo = 1
AND e.id NOT IN (SELECT DISTINCT empleado_id FROM empleado_horarios)
AND e.id NOT IN (SELECT DISTINCT empleado_id FROM empleados_ciclos);

-- ============================================================
-- 2. LLENAR TABLA DE DÍAS FESTIVOS 2026
-- ============================================================
-- Fuente: Calendario oficial México 2026

INSERT INTO dias_festivos (fecha, nombre, descripcion, created_at) VALUES
-- Enero
('2026-01-01', 'Año Nuevo', 'Feriado nacional - Año Nuevo', NOW()),
('2026-01-05', 'Día de los Santos Reyes', 'Feriado nacional - Santos Reyes', NOW()),

-- Febrero
('2026-02-02', 'Día de la Constitución', 'Feriado nacional - Constitución (primer lunes febrero)', NOW()),
('2026-02-09', 'Día de la Constitución (puente)', 'Puente por día de la Constitución', NOW()),

-- Marzo
('2026-03-16', 'Día de Benito Juárez', 'Feriado nacional - Benito Juárez (tercer lunes marzo)', NOW()),
('2026-03-23', 'Día de Benito Juárez (puente)', 'Puente por día de Benito Juárez', NOW()),

-- Abril
('2026-04-02', 'Jueves Santo', 'Feriado nacional - Jueves Santo', NOW()),
('2026-04-03', 'Viernes Santo', 'Feriado nacional - Viernes Santo', NOW()),

-- Mayo
('2026-05-01', 'Día del Trabajo', 'Feriado nacional - Día del Trabajo', NOW()),
('2026-05-05', 'Batalla de Puebla', 'Feriado nacional - Cinco de Mayo', NOW()),
('2026-05-10', 'Día de las Madres', 'Día de las Madres (no feriado pero importante)', NOW()),
('2026-05-15', 'Día del Maestro', 'Día del Maestro', NOW()),
('2026-05-25', 'Día del Niño', 'Día del Niño (no feriado pero importante)', NOW()),

-- Septiembre
('2026-09-16', 'Día de la Independencia', 'Feriado nacional - Independencia', NOW()),
('2026-09-21', 'Día de la Independencia (puente)', 'Puente por día de la Independencia', NOW()),

-- Octubre
('2026-10-12', 'Día de la Raza', 'Feriado nacional - Día de la Raza (segundo lunes octubre)', NOW()),
('2026-10-19', 'Día de la Raza (puente)', 'Puente por día de la Raza', NOW()),

-- Noviembre
('2026-11-02', 'Día de los Muertos', 'Feriado nacional - Día de los Muertos', NOW()),
('2026-11-16', 'Día de la Revolución', 'Feriado nacional - Revolución (tercer lunes noviembre)', NOW()),
('2026-11-23', 'Día de la Revolución (puente)', 'Puente por día de la Revolución', NOW()),

-- Diciembre
('2026-12-12', 'Día de la Virgen de Guadalupe', 'Feriado nacional - Virgen de Guadalupe', NOW()),
('2026-12-25', 'Navidad', 'Feriado nacional - Navidad', NOW())

ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ============================================================
-- 3. ASIGNAR SEDE POR DEFECTO A HORARIOS SIN SEDE
-- ============================================================
-- Todos los horarios sin sede se les asigna 'SEDE_A' (principal)

UPDATE horarios_laborales 
SET sede = 'SEDE_A' 
WHERE sede IS NULL OR sede = '';

-- ============================================================
-- 4. VERIFICAR RESULTADOS
-- ============================================================
-- Ejecutar estas consultas para verificar

-- Empleados con horarios asignados
SELECT 
    (SELECT COUNT(*) FROM empleados WHERE activo = 1) as total_empleados,
    (SELECT COUNT(DISTINCT empleado_id) FROM empleado_horarios) as con_horarios,
    (SELECT COUNT(*) FROM empleados WHERE activo = 1 
     AND id NOT IN (SELECT DISTINCT empleado_id FROM empleado_horarios)
     AND id NOT IN (SELECT DISTINCT empleado_id FROM empleados_ciclos)) as sin_horarios;

-- Días festivos insertados
SELECT COUNT(*) as total_dias_festivos FROM dias_festivos;

-- Horarios con sede
SELECT COUNT(*) as horarios_con_sede FROM horarios_laborales WHERE sede IS NOT NULL AND sede != '';
