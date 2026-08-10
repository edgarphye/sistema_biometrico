## ADDED Requirements

### Requirement: CRUD de sanciones
El sistema SHALL permitir crear, leer, actualizar y eliminar sanciones con tipo (suspension, amonestacion, nota_mala, descuento, acta_administrativa), empleado_id, fecha_inicio, dias, motivo, creado_por, estatus (activa, cumplida, cancelada).

#### Scenario: Creación de sanción
- **WHEN** se envía POST a /sanciones/crear con datos válidos
- **THEN** el sistema crea registro en sanciones

### Requirement: Catálogo de tipos
El sistema SHALL soportar tipos: suspension (días sin laborar), amonestacion (llamado de atención), nota_mala (acumulación de retardos), descuento (económico), acta_administrativa (formal).

#### Scenario: Sanción por tipo
- **WHEN** se crea sanción de tipo suspensión con 3 días
- **THEN** el sistema registra dias=3 y estatus=activa

### Requirement: Seguimiento de estado
El sistema SHALL permitir cambiar estatus de sanciones (activa, cumplida, cancelada).

#### Scenario: Cumplimiento de sanción
- **WHEN** se marca una sanción como cumplida
- **THEN** el sistema actualiza estatus y fecha_modificacion

### Requirement: Consulta histórica por empleado
El sistema SHALL listar sanciones por empleado y año.

#### Scenario: Historial de sanciones
- **WHEN** se consulta /sanciones/empleado/{id}
- **THEN** el sistema retorna todas las sanciones del empleado
