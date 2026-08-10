## ADDED Requirements

### Requirement: Solicitud de vacaciones
El sistema SHALL permitir solicitar vacaciones con empleado_id, fecha_inicio, fecha_fin, dias_solicitados, motivo y estatus.

#### Scenario: Solicitud de vacaciones
- **WHEN** se envía POST a /vacaciones/solicitar con datos válidos
- **THEN** el sistema crea registro en vacaciones con estatus pendiente

### Requirement: Aprobación de vacaciones
El sistema SHALL gestionar aprobación/rechazo de solicitudes.

#### Scenario: Aprobación de vacaciones
- **WHEN** se aprueba una solicitud de vacaciones
- **THEN** el sistema actualiza estatus a aprobada y registra aprobado_por, fecha_aprobacion

### Requirement: Actualización de asistencia
El sistema SHALL actualizar los registros de asistencia durante el período vacacional.

#### Scenario: Asistencia marcada como vacaciones
- **WHEN** se aprueban vacaciones para un período
- **THEN** los registros de asistencia en ese período se marcan como tipo vacaciones
