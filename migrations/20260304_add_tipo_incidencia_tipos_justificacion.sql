-- Agrega mapeo explícito de catálogo para incidencias (sin heurística por nombre en frontend).
ALTER TABLE tipos_justificacion
    ADD COLUMN IF NOT EXISTS tipo_incidencia ENUM(
        'retardo',
        'general',
        'comision',
        'comision_entrada',
        'comision_salida',
        'comision_todo_dia',
        'dia_economico',
        'licencia_medica',
        'vacaciones',
        'cuidados_parentales'
    ) NOT NULL DEFAULT 'general' AFTER descripcion;

CREATE INDEX IF NOT EXISTS idx_tipos_justificacion_tipo_incidencia
    ON tipos_justificacion (tipo_incidencia, activo);

-- Mapeo inicial basado en catálogo existente.
UPDATE tipos_justificacion
SET tipo_incidencia = 'comision'
WHERE LOWER(TRIM(nombre)) = 'comisión'
   OR LOWER(TRIM(nombre)) = 'comision';

UPDATE tipos_justificacion
SET tipo_incidencia = 'dia_economico'
WHERE LOWER(TRIM(nombre)) = 'día económico'
   OR LOWER(TRIM(nombre)) = 'dia económico'
   OR LOWER(TRIM(nombre)) = 'dia economico';

UPDATE tipos_justificacion
SET tipo_incidencia = 'licencia_medica'
WHERE LOWER(TRIM(nombre)) = 'licencia médica'
   OR LOWER(TRIM(nombre)) = 'licencia medica';

UPDATE tipos_justificacion
SET tipo_incidencia = 'retardo'
WHERE LOWER(TRIM(nombre)) IN (
    'nómina',
    'nomina',
    'ausencia justificada',
    'médico',
    'medico',
    'personal',
    'transporte',
    'familiar'
);

INSERT INTO tipos_justificacion (nombre, descripcion, requiere_aprobacion, requiere_documento, activo, tipo_incidencia)
SELECT 'Vacaciones', 'Registro de vacaciones del empleado', 1, 0, 1, 'vacaciones'
WHERE NOT EXISTS (SELECT 1 FROM tipos_justificacion WHERE LOWER(TRIM(nombre)) = 'vacaciones');

INSERT INTO tipos_justificacion (nombre, descripcion, requiere_aprobacion, requiere_documento, activo, tipo_incidencia)
SELECT 'Cuidados maternos y/o paternos', 'Registro de cuidados maternos o paternos', 1, 0, 1, 'cuidados_parentales'
WHERE NOT EXISTS (
    SELECT 1 FROM tipos_justificacion 
    WHERE LOWER(TRIM(nombre)) IN ('cuidados maternos y/o paternos', 'cuidados maternos', 'cuidados paternos')
);
