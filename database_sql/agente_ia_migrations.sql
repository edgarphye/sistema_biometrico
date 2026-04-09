-- =====================================================
-- AGENTE IA DE GESTIÓN DE ASISTENCIA E INCIDENCIAS
-- Extensión del modelo de datos
-- =====================================================

-- Tabla: reglas_negocio (Motor de reglas configurables)
CREATE TABLE IF NOT EXISTS reglas_negocio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo ENUM('horario', 'tolerancia', 'retardo', 'falta', 'sancion', 'justificacion', 'validacion') NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    condicion_json JSON NOT NULL,
    accion_json JSON NOT NULL,
    prioridad INT DEFAULT 0,
    version INT DEFAULT 1,
    activa BOOLEAN DEFAULT TRUE,
    editable BOOLEAN DEFAULT TRUE,
    requiere_aprobacion BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    INDEX idx_tipo (tipo),
    INDEX idx_categoria (categoria),
    INDEX idx_activa (activa)
);

-- Tabla: bitacora_agente (Trazabilidad de decisiones)
CREATE TABLE IF NOT EXISTS bitacora_agente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    empleado_id INT NULL,
    tipo_incidencia VARCHAR(50) NOT NULL,
    incidencia_id INT NULL,
    incidencia_tipo VARCHAR(50) NULL,
    clasificacion VARCHAR(50) NOT NULL,
    detalle TEXT,
    regla_aplicada VARCHAR(100) NULL,
    decision VARCHAR(50) NOT NULL,
    justificada BOOLEAN NULL,
    validada_por_jefe BOOLEAN NULL,
    evidencia_json JSON,
    recomendaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha),
    INDEX idx_empleado (empleado_id),
    INDEX idx_clasificacion (clasificacion),
    INDEX idx_decision (decision)
);

-- Tabla: plantillas_documentos (Para generar oficios)
CREATE TABLE IF NOT EXISTS plantillas_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('oficio', 'nota_mala', 'llamado_atencion', 'suspension', 'termino', 'reporte', 'otro') NOT NULL,
    contenido TEXT NOT NULL,
    variables_json JSON,
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo)
);

-- Tabla: documentos_generados (Documentos creados)
CREATE TABLE IF NOT EXISTS documentos_generados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo_documento VARCHAR(50) NOT NULL,
    plantilla_id INT NULL,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT,
    archivo_path VARCHAR(500),
    periodo DATE NOT NULL,
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generado_por INT NULL,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha_generacion (fecha_generacion)
);

-- Tabla: incidencias_no_validadas (Incidencias sin validación de jefe)
CREATE TABLE IF NOT EXISTS incidencias_no_validadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo_incidencia VARCHAR(50) NOT NULL,
    incidencia_id INT NOT NULL,
    fecha DATE NOT NULL,
    dias_pendientes INT DEFAULT 0,
    estado ENUM('pendiente', 'en_proceso', 'validada', 'rechazada', 'caducada') DEFAULT 'pendiente',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha)
);

-- Tabla: anomalias_detectadas (Registro de anomalías)
CREATE TABLE IF NOT EXISTS anomalias_detectadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo_anomalia ENUM('registro_fuera_rango', 'horario_inconsistente', 'dispositivo_invalido', 'patron_atipico', 'horario_no_asignado', 'otro') NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_deteccion DATE NOT NULL,
    hora_deteccion TIME NOT NULL,
    datos_json JSON,
    evaluada BOOLEAN DEFAULT FALSE,
    falsa_alarma BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_tipo (tipo_anomalia),
    INDEX idx_fecha (fecha_deteccion)
);

-- Tabla: metricas_kpi (KPIs del sistema)
CREATE TABLE IF NOT EXISTS metricas_kpi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kpi_nombre VARCHAR(100) NOT NULL,
    periodo DATE NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    meta DECIMAL(10,2),
    empleado_id INT NULL,
    area VARCHAR(100),
    datos_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kpi (kpi_nombre),
    INDEX idx_periodo (periodo),
    INDEX idx_empleado (empleado_id)
);

-- Tabla: configuraciones_agente (Configuración del agente)
CREATE TABLE IF NOT EXISTS configuraciones_agente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    tipo ENUM('string', 'int', 'float', 'boolean', 'json') DEFAULT 'string',
    descripcion TEXT,
    categoria VARCHAR(50),
    editable BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave),
    INDEX idx_categoria (categoria)
);

-- Insertar configuraciones por defecto
INSERT IGNORE INTO configuraciones_agente (clave, valor, tipo, descripcion, categoria) VALUES
('tolerancia_minutos', '10', 'int', 'Minutos de tolerancia para entrada', 'horario'),
('retardo_menor_min', '11', 'int', 'Minutos mínimos para retardo menor', 'retardo'),
('retardo_mayor_min', '21', 'int', 'Minutos mínimos para retardo mayor', 'retardo'),
('falta_minutos', '31', 'int', 'Minutos que constituyen falta', 'retardo'),
('max_retardos_quincena', '2', 'int', 'Máximo retardos justificados por quincena', 'sancion'),
('notas_malas_oficio', '1', 'int', 'Notas malas mínimas para oficio', 'sancion'),
('notas_malas_suspension', '5', 'int', 'Notas malas para suspensión', 'sancion'),
('suspensiones_termino', '7', 'int', 'Suspensiones para término de nombramiento', 'sancion'),
('dias_justificacion_retardo', '15', 'int', 'Días para justificar retardo', 'justificacion'),
('dias_justificacion_comision', '2', 'int', 'Días hábiles para justificar comisión', 'justificacion'),
('procesamiento_automatico', 'true', 'boolean', 'Habilitar procesamiento automático', 'agente'),
('generar_documentos_auto', 'false', 'boolean', 'Generar documentos automáticamente', 'agente'),
('detectar_anomalias', 'true', 'boolean', 'Detectar anomalías automáticamente', 'agente'),
('zona_horaria', 'America/Mexico_City', 'string', 'Zona horaria del sistema', 'sistema');

-- Insertar plantillas de documentos por defecto
INSERT IGNORE INTO plantillas_documentos (nombre, tipo, contenido, activa) VALUES
('Oficio de Notas Malas', 'oficio', '{OFICIO}

ASUNTO: Notificación de notas malas en el período {PERIODO}

{nombre_jefe}
{nombre_empleado}
PRESENTE

Por medio del presente se hace de su conocimiento que el(la) trabajador(a) {nombre_completo} con RFC {rfc}, ha acumulado {cantidad_notas} nota(s) mala(s) durante el período correspondiente a {perido_detallado}, misma(s) que se detalla(n) a continuación:

{DETALLE_INCIDENCIAS}

Este llamado de atención tiene como objetivo que el(trabajador(a) se comprometa a mejorar su asistencia y puntualidad, evitando la recurrencia de estas incidencias.

Se le informa que de acuerdo al Manual de Normas para la Administración de Recursos Humanos en la SEP, las notas malas no justificadas pueden derivar en sanciones administrativas.

Sin otro particular, aprovecho para enviarle un cordial saludo.

ATENTAMENTE
{nombre_autoridad}
{fecha_oficio}', 1),
('Llamado de Atención', 'llamado_atencion', '{OFICIO}

ASUNTO: Llamado de Atención por incidencias en asistencia

{nombre_jefe}
PRESENTE

Por este medio se le informa que el(la) trabajador(a) {nombre_completo} ha acumulado las siguientes incidencias en el período {PERIODO}:

{DETALLE_INCIDENCIAS}

Se le hace saber que debe tomar las medidas necesarias para corregir esta situación.

Se anexa evidencia para su conocimiento.

{fecha_oficio}', 1),
('Reporte de Incidencias No Justificadas', 'reporte', 'REPORTE DE INCIDENCIAS NO JUSTIFICADAS
Período: {PERIODO}
Fecha de generación: {fecha_generacion}
Área: {area}

===========================================
RESUMEN
===========================================
Total incidencias: {total_incidencias}
Retardos no justificados: {total_retardos}
Faltas: {total_faltas}
Incidencias no validadas: {total_no_validadas}

===========================================
DETALLE POR EMPLEADO
===========================================
{DETALLE_EMPLEADOS}

===========================================
NOTAS
===========================================
{notas_adicionales}', 1);

-- Insertar reglas de negocio por defecto
INSERT IGNORE INTO reglas_negocio (nombre, descripcion, tipo, categoria, condicion_json, accion_json, prioridad, activa) VALUES
('Tolerancia de entrada', 'Minutos de tolerancia antes de considerar retardo', 'tolerancia', 'entrada', '{"campo": "minutos_retardo", "operador": "<=", "valor": 10}', '{"accion": "registrar", "clasificacion": "puntual", "justificada": false}', 10, 1),
('Retardo menor', 'Retardo de 11 a 20 minutos', 'retardo', 'entrada', '{"campo": "minutos_retardo", "operador": "entre", "min": 11, "max": 20}', '{"accion": "registrar", "clasificacion": "retardo_menor", "justificada": false}', 20, 1),
('Retardo mayor', 'Retardo de 21 a 30 minutos', 'retardo', 'entrada', '{"campo": "minutos_retardo", "operador": "entre", "min": 21, "max": 30}', '{"accion": "registrar", "clasificacion": "retardo_mayor", "justificada": false}', 30, 1),
('Falta injustificada', 'Más de 30 minutos de retardo', 'falta', 'entrada', '{"campo": "minutos_retardo", "operador": ">", "valor": 30}', '{"accion": "registrar", "clasificacion": "falta", "justificada": false}', 40, 1),
('Justificación retardo quincena', 'Límite para justificar retardos', 'justificacion', 'plazos', '{"campo": "dias_transcurridos", "operador": "<=", "valor": 15}', '{"accion": "permitir", "validacion": "requerida"}', 50, 1),
('Máximo retardos justificables', 'Límite de retardos justificables por quincena', 'sancion', 'limites', '{"campo": "retardos_justificados", "operador": ">=", "valor": 2}', '{"accion": "bloquear", "mensaje": "Límite de retardos justificados alcanzado"}', 60, 1),
('Nota mala por retardo', 'Generar nota mala por retardo no justificado', 'sancion', 'notas', '{"campo": "retardos_no_justificados", "operador": ">=", "valor": 1}', '{"accion": "crear", "tipo": "nota_mala", "cantidad": 1}', 70, 1),
('Suspensión por notas', '5 notas malas = suspensión', 'sancion', 'suspension', '{"campo": "notas_malas", "operador": ">=", "valor": 5}', '{"accion": "crear", "tipo": "suspension", "dias": 1}', 80, 1);

-- Tabla de procesamiento de archivos
CREATE TABLE IF NOT EXISTS archivos_procesados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(20) NOT NULL,
    registros_total INT DEFAULT 0,
    registros_procesados INT DEFAULT 0,
    registros_error INT DEFAULT 0,
    estado ENUM('pendiente', 'procesando', 'completado', 'error') DEFAULT 'pendiente',
    errores_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_estado (estado),
    INDEX idx_fecha (created_at)
);
