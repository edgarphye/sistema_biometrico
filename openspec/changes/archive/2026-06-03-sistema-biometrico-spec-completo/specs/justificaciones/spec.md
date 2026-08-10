## ADDED Requirements

### Requirement: Registro de justificación
El sistema SHALL permitir registrar justificaciones para retardos, faltas e incidencias con empleado_id, tipo_justificacion, motivo, fecha_inicio, fecha_fin, estatus (pendiente, aprobada, rechazada) y evidencia adjunta.

#### Scenario: Creación de justificación
- **WHEN** se envía POST a /justificaciones/justificar con datos válidos
- **THEN** el sistema crea registro en justificaciones y actualiza el retardo/asistencia asociado

### Requirement: Flujo de aprobación
El sistema SHALL gestionar aprobación/rechazo de justificaciones con registro de aprobado_por y fecha_aprobacion.

#### Scenario: Aprobación de justificación
- **WHEN** un administrador o jefe aprueba una justificación
- **THEN** el sistema actualiza estatus a aprobada y registra aprobado_por

### Requirement: Adjuntar evidencias
El sistema SHALL permitir adjuntar archivos de evidencia (soporte) a las justificaciones.

#### Scenario: Subida de evidencia
- **WHEN** se envía archivo junto con la justificación
- **THEN** el sistema guarda en uploads/soportes y asigna ruta al campo soporte

### Requirement: Seguimiento de modificaciones
El sistema SHALL registrar cambios a justificaciones en modificaciones_justificacion con tabla_origen, registro_id, campo_modificado, valor_anterior, valor_nuevo, modificado_por.

#### Scenario: Modificación registrada
- **WHEN** se modifica una justificación existente
- **THEN** el sistema crea registro en modificaciones_justificacion
