# reglas-negocio-ia

## Purpose
Administrar reglas de negocio parametrizables para el motor de procesamiento del agente inteligente, con evaluación por tipo de incidencia, priorización y versionado.


### Requirement: Motor de reglas de negocio
El sistema SHALL administrar reglas de negocio parametrizables en reglas_negocio con nombre, descripcion, tipo (horario, tolerancia, retardo, falta, sancion, justificacion, validacion), categoria, condicion_json, accion_json, prioridad, version, activa, editable, requiere_aprobacion.

#### Scenario: Creación de regla
- **WHEN** se envía POST a /agente-ia/guardar-regla con datos válidos
- **THEN** el sistema crea registro en reglas_negocio

### Requirement: Evaluación de reglas por tipo
El sistema SHALL evaluar reglas según su tipo en el contexto apropiado (horario, tolerancia, retardo, etc.).

#### Scenario: Evaluación de regla de tolerancia
- **WHEN** se evalúa un retardo
- **THEN** el sistema aplica las reglas activas de tipo tolerancia y retardo

### Requirement: Priorización de reglas
El sistema SHALL aplicar reglas en orden de prioridad (mayor número = mayor prioridad) y por versión.

#### Scenario: Regla de alta prioridad
- **WHEN** dos reglas aplican al mismo caso
- **THEN** se aplica la de mayor prioridad
