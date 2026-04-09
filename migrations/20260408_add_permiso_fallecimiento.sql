-- Agrega tipo de justificación para permiso por fallecimiento
ALTER TABLE tipos_justificacion
    MODIFY COLUMN tipo_incidencia ENUM(
        'retardo',
        'general',
        'comision',
        'comision_entrada',
        'comision_salida',
        'comision_todo_dia',
        'dia_economico',
        'licencia_medica',
        'vacaciones',
        'cuidados_parentales',
        'permiso_fallecimiento'
    ) NOT NULL DEFAULT 'general';

INSERT INTO tipos_justificacion (nombre, descripcion, requiere_aprobacion, requiere_documento, activo, tipo_incidencia)
SELECT 'Permiso por fallecimiento', 'Permiso por fallecimiento de familiar', 1, 1, 1, 'permiso_fallecimiento'
WHERE NOT EXISTS (SELECT 1 FROM tipos_justificacion WHERE LOWER(TRIM(nombre)) = 'permiso por fallecimiento');