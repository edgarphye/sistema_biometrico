-- Agrega permiso_fallecimiento al enum de tipo_incidencia en incidencias_no_validadas
ALTER TABLE incidencias_no_validadas
    MODIFY COLUMN tipo_incidencia ENUM(
        'retardo','comision_entrada','comision_salida','comision_todo_dia',
        'comision_entrada_otros','dia_economico','licencia_medica','PDSEP-SNTE',
        'EYR','CLIDDA','DE','F','vacaciones','cuidados_paternos','cuidados_maternos',
        'falta','constancia_tiempo','permiso_fallecimiento'
    ) NOT NULL DEFAULT 'retardo';