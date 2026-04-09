-- =====================================================
-- ÍNDICES CRÍTICOS DE PERFORMANCE - SISTEMA BIOMÉTRICO
-- Creado: 5 de Enero de 2026
-- Propósito: Optimizar queries críticas para alto volumen
-- =====================================================

-- Índice compuesto para asistencia (empleado + timestamp)
-- Optimiza: SELECT * FROM asistencia WHERE empleado_id = ? ORDER BY timestamp DESC
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_timestamp 
ON asistencia(empleado_id, timestamp DESC);

-- Índice compuesto para asistencia por dispositivo + timestamp  
-- Optimiza: Reports por dispositivo y fechas
CREATE INDEX IF NOT EXISTS idx_asistencia_dispositivo_timestamp 
ON asistencia(dispositivo_id, timestamp DESC);

-- Índice compuesto para asistencia por tipo biométrico + timestamp
-- Optimiza: Filtrado por tipo de verificación biométrica
CREATE INDEX IF NOT EXISTS idx_asistencia_tipo_timestamp 
ON asistencia(tipo_biometria, timestamp DESC);

-- Índice compuesto para empleados por área + estado activo
-- Optimiza: Listado de empleados por área activos
CREATE INDEX IF NOT EXISTS idx_empleados_area_activo 
ON empleados(area, activo);

-- Índice compuesto para retardos por empleado + fecha
-- Optimiza: Historial de retardos de un empleado
CREATE INDEX IF NOT EXISTS idx_retardos_empleado_fecha 
ON retardos(empleado_id, fecha DESC);

-- Índice compuesto para comisiones por empleado + fecha vencimiento
-- Optimiza: Control de comisiones próximas a vencer
CREATE INDEX IF NOT EXISTS idx_comisiones_empleado_vencimiento 
ON comisiones(empleado_id, fecha_vencimiento DESC);

-- Índice compuesto para dispositivos por sede + estado activo
-- Optimiza: Listado de dispositivos por sede
CREATE INDEX IF NOT EXISTS idx_dispositivos_sede_activo 
ON dispositivos_biometricos(sede, activo);

-- Índice compuesto para ausencias por empleado + fecha
-- Optimiza: Historial de ausencias de un empleado
CREATE INDEX IF NOT EXISTS idx_ausencias_empleado_fecha 
ON ausencias(empleado_id, fecha DESC);

-- Índice compuesto para sanciones por empleado + año
-- Optimiza: Sanciones de un empleado en año específico
CREATE INDEX IF NOT EXISTS idx_sanciones_empleado_anio 
ON sanciones(empleado_id, anio);

-- Índice compuesto para horarios por sede + estado activo
-- Optimiza: Horarios disponibles por sede
CREATE INDEX IF NOT EXISTS idx_horarios_sede_activo 
ON horarios_laborales(sede, activo);

-- Índice compuesto para bloques de ciclo por ciclo + día + hora
-- Optimiza: Consulta de bloques horarios específicos
CREATE INDEX IF NOT EXISTS idx_bloques_ciclo_dia_hora 
ON bloques_ciclo(ciclo_id, dia_semana, hora_inicio);

-- Índice para usuarios por rol + estado activo
-- Optimiza: Listado de usuarios por rol
CREATE INDEX IF NOT EXISTS idx_usuarios_rol_activo 
ON usuarios(rol, activo);

-- =====================================================
-- ÍNDICES ESPECIALES PARA PERFORMANCE DE BÚSQUEDA
-- =====================================================

-- Fulltext index para búsqueda de empleados por nombre completo
-- Optimiza: Búsqueda rápida de empleados
CREATE FULLTEXT INDEX IF NOT EXISTS idx_empleados_nombre_completo 
ON empleados(nombre, apellido);

-- Fulltext index para búsqueda en justificaciones de retardos
-- Optimiza: Búsqueda de justificaciones
CREATE FULLTEXT INDEX IF NOT EXISTS idx_retardos_justificacion 
ON retardos(justificacion);

-- =====================================================
-- ÍNDICES DE FECHAS PARA REPORTES TEMPORALES
-- =====================================================

-- Índice de fecha extraído de timestamp para asistencia
-- Optimiza: Reportes diarios/semanales/mensuales
CREATE INDEX IF NOT EXISTS idx_asistencia_fecha 
ON asistencia((DATE(timestamp)));

-- Índice compuesto para reportes mensuales por empleado y año-mes
-- Optimiza: Reportes de asistencia mensual
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_ano_mes 
ON asistencia(empleado_id, (YEAR(timestamp)), (MONTH(timestamp)));

-- =====================================================
-- ESTADÍSTICAS DE CREACIÓN DE ÍNDICES
-- =====================================================

-- Mostrar todos los índices creados para verificación
SHOW INDEX FROM asistencia;
SHOW INDEX FROM empleados;
SHOW INDEX FROM retardos;
SHOW INDEX FROM comisiones;
SHOW INDEX FROM ausencias;
SHOW INDEX FROM sanciones;
SHOW INDEX FROM dispositivos_biometricos;
SHOW INDEX FROM horarios_laborales;
SHOW INDEX FROM bloques_ciclo;
SHOW INDEX FROM usuarios;

-- =====================================================
-- NOTAS DE PERFORMANCE ESPERADA
-- =====================================================

/*
IMPROVEMENTS ESPERADOS:
- SELECT * FROM asistencia WHERE empleado_id = ? ORDER BY timestamp DESC: 70-80% más rápido
- SELECT * FROM empleados WHERE area = ? AND activo = 1: 60-70% más rápido
- SELECT * FROM retardos WHERE empleado_id = ? AND fecha >= ? ORDER BY fecha DESC: 65-75% más rápido
- Consultas de reportes por fechas: 50-60% más rápido

ESPACIO ADICIONAL REQUERIDO: ~2-5MB por cada 10,000 registros

MONITOREAR EL TAMAÑO DE ÍNDICES:
- SELECT table_name, 
         ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
         index_length / 1024 / 1024 AS 'Index Size (MB)'
  FROM information_schema.tables 
 WHERE table_schema = 'sistema_biometrico'
 ORDER BY (data_length + index_length) DESC;
*/