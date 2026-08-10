# auditoria-logs

## Purpose
Registrar y auditar modificaciones en justificaciones y retardos, validar cambios por jefe o RH, y mantener un log estructurado de errores del sistema para depuración.


### Requirement: Seguimiento de modificaciones
El sistema SHALL registrar cambios en justificaciones y retardos mediante modificaciones_justificacion con tabla_origen, registro_id, empleado_id, campo_modificado, valor_anterior, valor_nuevo, modificado_por, motivo_modificacion, estatus pendiente/aprobado/rechazado.

#### Scenario: Modificación registrada
- **WHEN** se modifica un campo de una justificación
- **THEN** el sistema crea registro en modificaciones_justificacion

### Requirement: Validación de cambios
El sistema SHALL requerir validación (jefe o RH) para cambios en registros de asistencia y retardos.

#### Scenario: Cambio pendiente de validación
- **WHEN** se modifica un registro de asistencia
- **THEN** el sistema marca estatus=pendiente hasta que sea validado

### Requirement: Log de errores del sistema
El sistema SHALL registrar errores PHP, excepciones y errores fatales en logs/php_errors_YYYY-MM-DD.log con formato [fecha] [nivel] mensaje in archivo:línea.

#### Scenario: Error registrado
- **WHEN** ocurre un error PHP
- **THEN** el sistema escribe en el log con detalle y stack trace
