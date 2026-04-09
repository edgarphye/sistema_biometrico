-- ============================================================
-- SCRIPT DE MIGRACIÓN COMPLETO
-- Sistema Biométrico - Historial de Horarios y Validaciones
-- Fecha: 2026-02-16
-- ============================================================

-- NOTA: Ejecutar en orden secuencial

-- ============================================================
-- PARTE 1: CAMPOS CALCULADOS PARA HISTORIAL DE HORARIOS
-- ============================================================

-- 1.1 Agregar campo semana_numero a bloques_ciclo (si no existe)
ALTER TABLE bloques_ciclo 
ADD COLUMN IF NOT EXISTS semana_numero TINYINT DEFAULT 1 AFTER dia_semana;

-- 1.2 Agregar campos calculados a empleado_horarios
ALTER TABLE empleado_horarios 
ADD COLUMN IF NOT EXISTS hora_entrada TIME NULL AFTER fecha_fin,
ADD COLUMN IF NOT EXISTS hora_salida TIME NULL AFTER hora_entrada,
ADD COLUMN IF NOT EXISTS total_horas_semanales DECIMAL(5,2) NULL AFTER hora_salida,
ADD COLUMN IF NOT EXISTS tipo_horario ENUM('FIJO', 'COMBINADO', 'ROTATIVO') NULL AFTER total_horas_semanales,
ADD COLUMN IF NOT EXISTS detalle_json JSON NULL AFTER tipo_horario;

-- 1.3 Crear índice para optimizar consultas
CREATE INDEX IF NOT EXISTS idx_empleado_horarios_fecha ON empleado_horarios(empleado_id, fecha_inicio, fecha_fin);

-- ============================================================
-- PARTE 2: VISTA DE VALIDACIONES COMPLETA
-- ============================================================

-- 2.1 Crear vista_validacion_completa
DROP VIEW IF EXISTS vista_validacion_completa;

CREATE OR REPLACE VIEW vista_validacion_completa AS
SELECT 
    r.id,
    r.empleado_id,
    r.fecha as fecha_incidencia,
    r.minutos_retardo,
    r.tipo_registro,
    r.categoria_principal,
    r.requiere_validacion_jefe,
    r.estado_validacion as estado_validacion_jefe,
    r.evidencia_adjunta,
    r.motivo_detalle,
    r.hora_entrada,
    r.hora_salida,
    r.fecha_asistencia,
    r.dia_semana,
    r.created_at as fecha_solicitud,
    r.fecha_aprobacion as fecha_validacion,
    CASE 
        WHEN r.minutos_retardo <= 5 THEN 1
        WHEN r.minutos_retardo <= 15 THEN 2
        WHEN r.minutos_retardo <= 30 THEN 3
        ELSE 4
    END as prioridad_atencion,
    'retardo' as tipo_incidencia,
    r.id as incidencia_id,
    e.nombre as empleado_nombre,
    e.apellido as empleado_apellido,
    e.area as empleado_area,
    e.jerarquia as empleado_jerarquia,
    u.nombre_completo as jefe_nombre,
    vj.id as validacion_id,
    vj.estado as validacion_estado,
    vj.comentarios_adicionales as validacion_comentarios,
    vj.fecha_validacion as validacion_fecha
FROM retardos r
INNER JOIN empleados e ON r.empleado_id = e.id
LEFT JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
LEFT JOIN validaciones_jefe vj ON r.id = vj.incidencia_id AND vj.tipo_incidencia = 'retardo'
WHERE r.requiere_validacion_jefe = 1 OR r.estado_validacion IN ('pendiente', 'aprobado', 'rechazado', 'requiere_info');

-- ============================================================
-- PARTE 3: DATOS DE PRUEBA - JEFES Y EMPLEADOS
-- ============================================================

-- 3.1 Insertar usuarios con rol de jefe (si no existen)
INSERT IGNORE INTO usuarios (id, username, password, email, nombre_completo, rol, activo) VALUES
(20, 'jefe1', '$2y$12$5Z2LTLufaDCSt2L7A66H8uZTZZmgTwm7SltlP73KsqXAvjqI8qqJW', 'jefe1@test.com', 'Jefe Área Ventas', 'jefe', 1),
(21, 'jefe2', '$2y$12$5Z2LTLufaDCSt2L7A66H8uZTZZmgTwm7SltlP73KsqXAvjqI8qqJW', 'jefe2@test.com', 'Jefe Área Sistemas', 'jefe', 1),
(22, 'jefe3', '$2y$12$5Z2LTLufaDCSt2L7A66H8uZTZZmgTwm7SltlP73KsqXAvjqI8qqJW', 'jefe3@test.com', 'Jefe Área RRHH', 'jefe', 1),
(118, 'jefe1_prueba', '$2y$12$5Z2LTLufaDCSt2L7A66H8uZTZZmgTwm7SltlP73KsqXAvjqI8qqJW', 'jefe1_prueba@empresa.com', 'Jefe Ventas Prueba', 'jefe', 1),
(119, 'jefe2_prueba', '$2y$12$5Z2LTLufaDCSt2L7A66H8uZTZZmgTwm7SltlP73KsqXAvjqI8qqJW', 'jefe2_prueba@empresa.com', 'Jefe Ingeniería Prueba', 'jefe', 1),
(120, 'jefe3_prueba', '$2y$12$5Z2LTLufaDCSt2L7A66H8uZTZZmgTwm7SltlP73KsqXAvjqI8qqJW', 'jefe3_prueba@empresa.com', 'Jefe RRHH Prueba', 'jefe', 1);

-- 3.2 Insertar empleados para los chiefs de prueba (si no existen)
INSERT IGNORE INTO empleados (id, nombre, apellido, area, puesto, email, activo, jefe_directo_id) VALUES
(121, 'Ana', 'García', 'Ventas', 'Vendedor', 'ana.garcia@empresa.com', 1, 118),
(122, 'Carlos', 'López', 'Ventas', 'Vendedor', 'carlos.lopez@empresa.com', 1, 118),
(123, 'María', 'Rodríguez', 'Ventas', 'Vendedor', 'maria.rodriguez@empresa.com', 1, 118),
(124, 'Pedro', 'Martínez', 'Ingeniería', 'Desarrollador', 'pedro.martinez@empresa.com', 1, 119),
(125, 'Laura', 'Sánchez', 'Ingeniería', 'Desarrollador', 'laura.sanchez@empresa.com', 1, 119),
(126, 'Diego', 'Torres', 'Ingeniería', 'Desarrollador', 'diego.torres@empresa.com', 1, 119),
(127, 'Sofía', 'Ramírez', 'Ingeniería', 'Desarrollador', 'sofia.ramirez@empresa.com', 1, 119),
(128, 'Roberto', 'Flores', 'RRHH', 'Reclutador', 'roberto.flores@empresa.com', 1, 120),
(129, 'Isabel', 'Castro', 'RRHH', 'Reclutador', 'isabel.castro@empresa.com', 1, 120),
(130, 'Miguel', 'Herrera', 'RRHH', 'Reclutador', 'miguel.herrera@empresa.com', 1, 120);

-- 3.3 Actualizar empleado_id en usuarios de jefe
UPDATE usuarios SET empleado_id = 121 WHERE username = 'jefe1_prueba';
UPDATE usuarios SET empleado_id = 124 WHERE username = 'jefe2_prueba';
UPDATE usuarios SET empleado_id = 128 WHERE username = 'jefe3_prueba';

-- ============================================================
-- PARTE 4: DATOS DE PRUEBA - CICLOS Y HORARIOS
-- ============================================================

-- 4.1 Crear ciclos (si no existen)
INSERT IGNORE INTO ciclos (id, nombre, descripcion, activo) VALUES
(1, 'm01', 'Horario Matutino 07:00-16:00', 1),
(2, 'M02', 'Horario Combinado Miercoles', 1);

-- 4.2 Crear bloques para ciclo m01 (Horario Fijo 07:00-16:00)
INSERT IGNORE INTO bloques_ciclo (ciclo_id, dia_semana, hora_inicio, hora_fin, horario_id, activo, semana_numero) VALUES
(1, 0, '07:00:00', '16:00:00', 2, 1, 1),
(1, 1, '07:00:00', '16:00:00', 2, 1, 1),
(1, 2, '07:00:00', '16:00:00', 2, 1, 1),
(1, 3, '07:00:00', '16:00:00', 2, 1, 1),
(1, 4, '07:00:00', '16:00:00', 2, 1, 1);

-- 4.3 Crear bloques para ciclo M02 (Horario Combinado)
-- Lunes, Martes, Jueves, Viernes: 09:00-16:00
-- Miércoles: 08:00-14:00
INSERT IGNORE INTO bloques_ciclo (ciclo_id, dia_semana, hora_inicio, hora_fin, horario_id, activo, semana_numero) VALUES
(2, 0, '09:00:00', '16:00:00', 1, 1, 1),
(2, 1, '09:00:00', '16:00:00', 1, 1, 1),
(2, 2, '08:00:00', '14:00:00', 3, 1, 1),
(2, 3, '09:00:00', '16:00:00', 1, 1, 1),
(2, 4, '09:00:00', '16:00:00', 1, 1, 1);

-- 4.4 Limpiar registros duplicados en bloques_ciclo (mantener solo el último)
DELETE FROM bloques_ciclo 
WHERE id NOT IN (
    SELECT MAX(id) FROM (
        SELECT id FROM bloques_ciclo 
        GROUP BY ciclo_id, dia_semana, hora_inicio, hora_fin
    ) AS keep
);

-- 4.5 Asignar ciclos a empleados y calcular campos
-- Asignar M02 a empleado 126
INSERT IGNORE INTO empleado_horarios (id, empleado_id, ciclo_id, fecha_inicio, hora_entrada, hora_salida, total_horas_semanales, tipo_horario) VALUES
(1, 126, 2, '2026-02-06', '08:00:00', '16:00:00', 34.00, 'COMBINADO');

-- Asignar M02 a empleado 119
INSERT IGNORE INTO empleado_horarios (id, empleado_id, ciclo_id, fecha_inicio, hora_entrada, hora_salida, total_horas_semanales, tipo_horario) VALUES
(2, 119, 2, '2026-02-06', '08:00:00', '16:00:00', 34.00, 'COMBINADO');

-- Asignar M02 a empleado 1005
INSERT IGNORE INTO empleado_horarios (id, empleado_id, ciclo_id, fecha_inicio, hora_entrada, hora_salida, total_horas_semanales, tipo_horario) VALUES
(3, 1005, 2, '2026-02-06', '08:00:00', '16:00:00', 34.00, 'COMBINADO');

-- 4.6 Corregir fecha_fin inválida
UPDATE empleado_horarios SET fecha_fin = NULL WHERE fecha_fin < fecha_inicio;

-- 4.7 Actualizar detalle_json para los registros
UPDATE empleado_horarios eh
SET detalle_json = (
    SELECT JSON_ARRAYAGG(
        JSON_OBJECT(
            'dia', dia_orden,
            'hora_inicio', hora_inicio,
            'hora_fin', hora_fin,
            'semana', semana_numero
        )
    )
    FROM (
        SELECT bc.dia_semana as dia_orden, bc.dia_semana, bc.hora_inicio, bc.hora_fin, bc.semana_numero
        FROM bloques_ciclo bc
        WHERE bc.ciclo_id = eh.ciclo_id AND bc.activo = 1
        ORDER BY bc.dia_semana
    ) AS dias_ordenados
)
WHERE eh.ciclo_id IS NOT NULL;

-- ============================================================
-- PARTE 5: DATOS DE PRUEBA - RETARDOS PARA VALIDACIÓN
-- ============================================================

-- 5.1 Insertar retardos de ejemplo para empleados asignados a chiefs
INSERT IGNORE INTO retardos (id, empleado_id, fecha, hora_entrada, minutos_retardo, categoria_principal, requiere_validacion_jefe, estado_validacion, motivo_detalle) VALUES
(5777, 121, '2026-02-10', '08:15:00', 15, 'retardo', 1, 'pendiente', 'Llegó tarde por tráfico'),
(5778, 122, '2026-02-11', '08:30:00', 30, 'retardo', 1, 'pendiente', 'Problemas con el transporte'),
(5779, 123, '2026-02-12', '08:10:00', 10, 'retardo', 1, 'pendiente', 'Reunión previa'),
(5780, 124, '2026-02-13', '08:05:00', 5, 'retardo', 1, 'pendiente', '少量 atraso'),
(5781, 125, '2026-02-14', '08:20:00', 20, 'retardo', 1, 'pendiente', 'Accidente vial');

-- 5.2 Crear registros en validaciones_jefe para los retardos
INSERT IGNORE INTO validaciones_jefe (incidencia_id, tipo_incidencia, empleado_id, jefe_id, estado) VALUES
(5777, 'retardo', 121, 118, 'pendiente'),
(5778, 'retardo', 122, 118, 'pendiente'),
(5779, 'retardo', 123, 118, 'pendiente'),
(5780, 'retardo', 124, 119, 'pendiente'),
(5781, 'retardo', 125, 119, 'pendiente');

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================

SELECT '=== VERIFICACIÓN DE MIGRACIÓN ===' as mensaje;

SELECT 'Usuarios Jefe:' as verificacion, COUNT(*) as total FROM usuarios WHERE rol = 'jefe';

SELECT 'Empleados con jefe directo:' as verificacion, COUNT(*) as total FROM empleados WHERE jefe_directo_id IS NOT NULL;

SELECT 'Ciclos:' as verificacion, COUNT(*) as total FROM ciclos WHERE activo = 1;

SELECT 'Bloques de ciclo:' as verificacion, COUNT(*) as total FROM bloques_ciclo WHERE activo = 1;

SELECT 'Asignaciones de horario:' as verificacion, COUNT(*) as total FROM empleado_horarios;

SELECT 'Retardos con validación:' as verificacion, COUNT(*) as total FROM retardos WHERE requiere_validacion_jefe = 1;

SELECT 'Validaciones pendientes:' as verificacion, COUNT(*) as total FROM validaciones_jefe WHERE estado = 'pendiente';

SELECT 'Vista validacion_completa:' as verificacion, COUNT(*) as total FROM vista_validacion_completa;

SELECT '=== MIGRACIÓN COMPLETADA ===' as mensaje;
