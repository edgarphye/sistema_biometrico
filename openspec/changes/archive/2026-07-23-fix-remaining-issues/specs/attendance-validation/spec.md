## ADDED Requirements

### Requirement: Validación masiva de asistencia
El sistema SHALL validar registros de asistencia pendientes por RH.

#### Scenario: Validación automática
- **WHEN** se ejecuta el script de validación masiva
- **THEN** el sistema marca registros como validados si cumplen reglas básicas

#### Scenario: Registros con inconsistencias
- **WHEN** un registro tiene marcación duplicada o faltante
- **THEN** el sistema lo marca para revisión manual

### Requirement: Reporte de validación
El sistema SHALL generar reporte de validación de asistencia.

#### Scenario: Estadísticas de validación
- **WHEN** se solicita reporte de validación
- **THEN** el sistema muestra total registros, validados, pendientes, inconsistentes
