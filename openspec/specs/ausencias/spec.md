# ausencias

## Purpose

Gestionar el registro de ausencias de empleados con tipo, justificación, validación por jefe, cálculo de días y actualización automática de registros de asistencia.

### Requirement: Registro de ausencia

El sistema SHALL permitir registrar ausencias con empleado_id, fecha_inicio, fecha_fin, dias, tipo (justificada/no_justificada/permiso), motivo, estatus y requiere_evidencia.

#### Scenario: Creación de ausencia

- **WHEN** se envía POST a `/ausencias/crear` con datos válidos
- **THEN** el sistema crea registro en la tabla `ausencias` con estatus pendiente

### Requirement: Clasificación de ausencias

El sistema SHALL clasificar ausencias como: justificada (con soporte documental), no_justificada (sin soporte), permiso (autorizado previamente).

#### Scenario: Ausencia justificada

- **WHEN** se registra una ausencia con documento soporte
- **THEN** el sistema clasifica como justificada

#### Scenario: Ausencia no justificada

- **WHEN** se registra una ausencia sin documento soporte
- **THEN** el sistema clasifica como no_justificada

### Requirement: Validación por jefe

El sistema SHALL enviar ausencias a validación del jefe directo cuando el tipo lo requiera.

#### Scenario: Ausencia enviada a validación

- **WHEN** se registra una ausencia que requiere validación
- **THEN** el sistema crea una validación pendiente en `validaciones_jefe` para el jefe directo

### Requirement: Actualización de asistencia

El sistema SHALL actualizar los registros de asistencia durante el período de ausencia.

#### Scenario: Asistencia marcada como ausencia

- **WHEN** se aprueba una ausencia
- **THEN** los registros de asistencia en el período se actualizan según el tipo de ausencia

### Requirement: Consulta de ausencias por empleado

El sistema SHALL listar ausencias por empleado con filtros por fecha y tipo.

#### Scenario: Historial de ausencias

- **WHEN** se consulta `/empleados/{id}/ausencias`
- **THEN** el sistema retorna las ausencias del empleado
