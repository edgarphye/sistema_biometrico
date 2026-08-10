## MODIFIED Requirements

### Requirement: Registro de retardo
El sistema SHALL registrar automáticamente retardos al procesar la asistencia cuando la hora de entrada supera la hora programada más la tolerancia. Los retardos existentes SHALL tener horario_id asignado.

#### Scenario: Registro automático de retardo
- **WHEN** un empleado llega después del inicio de jornada + tolerancia
- **THEN** el sistema crea registro en retardos con minutos_retardo calculados, tipo_asistencia, categoria_principal y horario_id

#### Scenario: Migración de retardos existentes
- **WHEN** se ejecuta el script de migración de retardos
- **THEN** el sistema asigna horario_id a retardos sin horario según fecha y horario del empleado

### Requirement: Clasificación por tipo
El sistema SHALL clasificar cada retardo como: retardo_menor (11-20 min), retardo_mayor (21-30 min), falta (31+ min), comision_*, dia_economico, ausencia.

#### Scenario: Clasificación automática
- **WHEN** se registra un retardo de 15 min
- **THEN** el sistema asigna tipo_retraso=retardo_menor
