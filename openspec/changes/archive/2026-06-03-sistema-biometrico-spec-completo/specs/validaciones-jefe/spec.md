## ADDED Requirements

### Requirement: Flujo de validación jefe-subordinado
El sistema SHALL gestionar validaciones de jefe inmediato para retardos, comisiones, días económicos y ausencias, con comunicación bidireccional (jefe <-> empleado).

#### Scenario: Validación pendiente creada
- **WHEN** se registra una incidencia que requiere validación
- **THEN** el sistema crea registro en validaciones_jefe con estado pendiente para el jefe directo

#### Scenario: Aprobación por jefe
- **WHEN** el jefe aprueba una validación pendiente
- **THEN** el sistema actualiza la validación y propaga el cambio a la incidencia origen

### Requirement: Comunicación bidireccional
El sistema SHALL permitir conversación entre jefe y empleado con mensajes, archivos adjuntos y estados de lectura.

#### Scenario: Jefe solicita información
- **WHEN** el jefe selecciona "requiere_info" y envía mensaje
- **THEN** el sistema crea validacion_mensajes y marca esperando_respuesta_de=empleado

#### Scenario: Empleado responde con evidencia
- **WHEN** el empleado responde al requerimiento de información
- **THEN** el sistema marca esperando_respuesta_de=jefe y notifica al jefe

### Requirement: Contador de pendientes en tiempo real
El sistema SHALL mostrar contador de validaciones pendientes en el menú con actualización cada 30 segundos.

#### Scenario: Badge actualizado
- **WHEN** hay validaciones pendientes
- **THEN** el sidebar muestra badge con número y animación pulse

### Requirement: Validación masiva
El sistema SHALL permitir selección múltiple y aprobación/rechazo masivo.

#### Scenario: Aprobación masiva
- **WHEN** se seleccionan múltiples validaciones y se aprueban
- **THEN** el sistema procesa todas en lote con una sola transacción

### Requirement: Auto-procesamiento
El sistema SHALL procesar automáticamente validaciones vencidas según reglas configurables.

#### Scenario: Auto-procesamiento
- **WHEN** una validación excede su fecha_limite
- **THEN** el sistema aplica regla de vencimiento configurada

### Requirement: Historial por empleado
El sistema SHALL mostrar historial completo de validaciones de cada empleado.

#### Scenario: Consulta de historial
- **WHEN** se consulta historial de validaciones de un empleado
- **THEN** el sistema retorna todas las validaciones con estado, fechas y mensajes
