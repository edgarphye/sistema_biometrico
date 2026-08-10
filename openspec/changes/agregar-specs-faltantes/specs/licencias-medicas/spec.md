# licencias-medicas

## Purpose

Gestionar licencias médicas con cálculo de días según antigüedad, registro de folio, diagnóstico e institución, división en medios días, sincronización automática con asistencia y notificaciones de vencimiento.

### Requirement: Registro de licencia médica

El sistema SHALL permitir registrar licencias médicas con empleado_id, fecha_inicio, fecha_fin, dias_solicitados, tipo (completo/medio_dia_entrada/medio_dia_salida), folio, diagnostico, institucion_medica, estatus (pendiente/aprobada/rechazada), archivo_adjunto.

#### Scenario: Creación de licencia

- **WHEN** se envía POST a `/licencias/crear` con datos válidos
- **THEN** el sistema crea registro en la tabla `licencias_medicas` con estatus pendiente

### Requirement: Cálculo de días según antigüedad

El sistema SHALL calcular días permitidos según antigüedad del empleado usando las reglas: días completos (`dias_permitidos_full`) y medios días (`dias_permitidos_half`) basados en años de servicio.

#### Scenario: Cálculo por antigüedad

- **WHEN** se solicita una licencia médica
- **THEN** el sistema calcula los días disponibles según la antigüedad del empleado

### Requirement: Aprobación de licencia

El sistema SHALL gestionar aprobación/rechazo de licencias médicas por RH o administrador.

#### Scenario: Aprobación de licencia

- **WHEN** se aprueba una licencia médica
- **THEN** el sistema actualiza estatus a aprobada con aprobado_por y fecha_aprobacion

### Requirement: Sincronización con asistencia

El sistema SHALL actualizar automáticamente los registros de asistencia durante el período de la licencia médica aprobada.

#### Scenario: Asistencia marcada como licencia

- **WHEN** se aprueba una licencia médica
- **THEN** los registros de asistencia en el período se actualizan a tipo licencia_medica

### Requirement: Notificaciones de vencimiento

El sistema SHALL enviar notificaciones in-app y email cuando una licencia médica esté próxima a vencer.

#### Scenario: Notificación pre-vencimiento

- **WHEN** una licencia médica está próxima a vencer (según configuración)
- **THEN** el sistema crea notificación para el empleado y RH

### Requirement: Consulta histórica

El sistema SHALL listar licencias médicas por empleado con filtros por fecha y estatus.

#### Scenario: Historial de licencias

- **WHEN** se consulta `/empleados/{id}/licencias`
- **THEN** el sistema retorna todas las licencias del empleado ordenadas por fecha
