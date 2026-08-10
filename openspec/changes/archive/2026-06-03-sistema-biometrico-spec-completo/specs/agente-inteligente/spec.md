## ADDED Requirements

### Requirement: Procesamiento automático de incidencias
El sistema SHALL procesar automáticamente incidencias (retardos, faltas, anomalías) usando reglas de negocio configuradas y registrando decisiones en bitacora_agente.

#### Scenario: Procesamiento de período
- **WHEN** se ejecuta el agente IA para un período
- **THEN** evalúa todas las incidencias pendientes, aplica reglas y registra decisiones

### Requirement: Clasificación de incidencias
El sistema SHALL clasificar incidencias como justificadas, no justificadas, pendientes de validación según reglas.

#### Scenario: Clasificación automática
- **WHEN** una incidencia cumple criterios de justificación automática
- **THEN** el agente la clasifica como justificada sin intervención humana

### Requirement: Recomendaciones
El sistema SHALL generar recomendaciones basadas en el análisis de incidencias y patrones.

#### Scenario: Recomendación generada
- **WHEN** se completa el análisis de un empleado
- **THEN** el agente genera recomendaciones de acción

### Requirement: Dashboard del agente
El sistema SHALL proveer dashboard con estadísticas de procesamiento, decisiones tomadas, incidencias pendientes.

#### Scenario: Dashboard del agente
- **WHEN** se accede a /agente-ia/dashboard
- **THEN** muestra métricas de procesamiento, reglas aplicadas, decisiones

### Requirement: Auditoría de decisiones
El sistema SHALL registrar en bitacora_agente todas las decisiones del agente con fecha, empleado, tipo_incidencia, clasificacion, decision, regla_aplicada, recomendaciones.

#### Scenario: Auditoría de decisión
- **WHEN** se consulta la bitácora de un empleado
- **THEN** muestra todas las decisiones del agente con detalle
