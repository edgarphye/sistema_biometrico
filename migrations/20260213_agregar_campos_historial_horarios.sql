-- Migración: Agregar campos calculados para historial de horarios
-- Fecha: 2026-02-13
-- Objetivo: Agregar campos para mostrar hora_entrada, hora_salida, total_horas y tipo_horario en el historial
-- IMPORTANTE: Esta migración ya fue ejecutada manualmente, los datos fueron corregidos

-- La migración ya fue aplicada exitosamente
-- Los datos de prueba fueron configurados:
-- - Ciclo M02 (ciclo_id=2): 08:00-16:00, 34 hrs/semana, Combinado
-- - Ciclo m01 (ciclo_id=1): 07:00-16:00, 45 hrs/semana, Fijo

SELECT 'Migración ya aplicada - Datos corregidos' AS status; 
ADD COLUMN IF NOT EXISTS hora_entrada TIME NULL AFTER fecha_fin,
ADD COLUMN IF NOT EXISTS hora_salida TIME NULL AFTER hora_entrada,
ADD COLUMN IF NOT EXISTS total_horas_semanales DECIMAL(5,2) NULL AFTER hora_salida,
ADD COLUMN IF NOT EXISTS tipo_horario ENUM('FIJO', 'COMBINADO', 'ROTATIVO') NULL AFTER total_horas_semanales,
ADD COLUMN IF NOT EXISTS detalle_json JSON NULL AFTER tipo_horario;

-- Crear índice para optimizar consultas por empleado
CREATE INDEX IF NOT EXISTS idx_empleado_horarios_fecha ON empleado_horarios(empleado_id, fecha_inicio, fecha_fin);

-- Actualizar registros existentes con los valores calculados
-- Este UPDATE calculará los valores para todos los registros existentes
UPDATE empleado_horarios eh
SET 
    hora_entrada = (
        SELECT MIN(bc.hora_inicio) 
        FROM bloques_ciclo bc 
        WHERE bc.ciclo_id = eh.ciclo_id
    ),
    hora_salida = (
        SELECT MAX(bc.hora_fin) 
        FROM bloques_ciclo bc 
        WHERE bc.ciclo_id = eh.ciclo_id
    ),
    total_horas_semanales = (
        SELECT SUM(TIMESTAMPDIFF(SECOND, bc.hora_inicio, bc.hora_fin) / 3600)
        FROM bloques_ciclo bc 
        WHERE bc.ciclo_id = eh.ciclo_id
    ),
    tipo_horario = (
        -- Determinar tipo según las reglas de negocio
        CASE 
            WHEN eh.ciclo_id IS NOT NULL THEN
                CASE 
                    WHEN (SELECT COUNT(DISTINCT bc2.semana_numero) FROM bloques_ciclo bc2 WHERE bc2.ciclo_id = eh.ciclo_id) > 1 THEN 'ROTATIVO'
                    WHEN (SELECT COUNT(DISTINCT CONCAT(bc2.hora_inicio, bc2.hora_fin)) FROM bloques_ciclo bc2 WHERE bc2.ciclo_id = eh.ciclo_id) = 1 THEN 'FIJO'
                    ELSE 'COMBINADO'
                END
            WHEN eh.horario_id IS NOT NULL THEN 'FIJO'
            ELSE NULL
        END
    )
WHERE eh.ciclo_id IS NOT NULL OR eh.horario_id IS NOT NULL;

-- Para horarios fijos (no ciclos), calcular desde la tabla horarios_laborales
UPDATE empleado_horarios eh
INNER JOIN horarios_laborales hl ON eh.horario_id = hl.id
SET 
    eh.hora_entrada = hl.hora_entrada,
    eh.hora_salida = hl.hora_salida,
    eh.total_horas_semanales = TIMESTAMPDIFF(SECOND, hl.hora_entrada, hl.hora_salida) / 3600,
    eh.tipo_horario = 'FIJO'
WHERE eh.horario_id IS NOT NULL;

-- Agregar campo semana_numero a bloques_ciclo si no existe (para ciclos rotativos)
-- Esto es necesario para determinar si es rotativo
-- Primero verificar si la columna existe
-- ALTER TABLE bloques_ciclo ADD COLUMN IF NOT EXISTS semana_numero TINYINT DEFAULT 1;

-- Si la columna no existe, crearla
-- ALTER TABLE bloques_ciclo ADD COLUMN semana_numero TINYINT DEFAULT 1 AFTER dia_semana;

-- Verificar resultado
SELECT * FROM empleado_horarios LIMIT 10;
