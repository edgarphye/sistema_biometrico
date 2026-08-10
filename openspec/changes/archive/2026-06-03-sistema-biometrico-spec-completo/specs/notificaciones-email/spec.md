## ADDED Requirements

### Requirement: Notificaciones por email
El sistema SHALL enviar notificaciones por correo electrónico mediante PHPMailer para: sanciones, validaciones pendientes, licencias próximas a vencer.

#### Scenario: Notificación de sanción
- **WHEN** se crea una sanción
- **THEN** el sistema envía email al empleado con detalles de la sanción

### Requirement: Verificación de umbrales
El sistema SHALL verificar umbrales de retardos por quincena/mes y notificar cuando se excedan (>2 por quincena, >1 por mes para sanciones).

#### Scenario: Notificación por exceso de retardos
- **WHEN** un empleado excede 2 retardos en una quincena
- **THEN** el sistema envía notificación al empleado

### Requirement: Gestión de notificaciones de licencias
El sistema SHALL enviar notificaciones programadas para licencias médicas próximas a vencer.

#### Scenario: Recordatorio de vencimiento
- **WHEN** una licencia médica está próxima a vencer
- **THEN** el sistema envía recordatorio al empleado y RH
