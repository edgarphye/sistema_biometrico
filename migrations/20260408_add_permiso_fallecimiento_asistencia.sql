-- Agrega permiso_fallecimiento al enum de tipo_asistencia en asistencia
ALTER TABLE asistencia
    MODIFY COLUMN tipo_asistencia ENUM(
        'normal','por_definir','con_retardo','con_ausencia','sin_registro',
        'licencia_medica','dia_economico','comision_entrada','comision_entrada_otros',
        'comision_salida','comision_todo_dia','CLIDDA','PDSEP-SNTE','EYR','DE','F',
        'vacaciones','cuidados_paternos','cuidados_maternos','constancia_tiempo',
        'falta','permiso_fallecimiento'
    ) DEFAULT NULL;