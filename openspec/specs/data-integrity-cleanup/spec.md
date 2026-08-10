# data-integrity-cleanup

## Purpose
Identificar, corregir y validar datos huérfanos e integridad referencial en la base de datos, incluyendo limpieza de registros vacíos de asistencia y clasificación de registros parciales.

### Requirement: Limpieza de datos huérfanos
El sistema SHALL identificar y corregir datos huérfanos en la base de datos.

#### Scenario: Detección de registros huérfanos
- **WHEN** se ejecuta el script de limpieza
- **THEN** el sistema identifica registros sin relación válida

#### Scenario: Corrección automática
- **WHEN** un dato huérfano tiene corrección posible
- **THEN** el sistema aplica la corrección y registra en bitácora

### Requirement: Limpieza de registros vacíos de asistencia
El sistema SHALL eliminar registros de asistencia completamente vacíos generados durante migración.

#### Scenario: Eliminación de registros por_definir sin datos
- **WHEN** existen registros con tipo_asistencia='por_definir', hora_entrada=NULL y hora_salida=NULL
- **THEN** el sistema elimina estos 2,113 registros inválidos

#### Scenario: Clasificación de registros parciales
- **WHEN** un registro tiene solo hora_salida sin hora_entrada
- **THEN** el sistema mantiene el registro con tipo_asistencia='por_definir' para revisión manual

#### Scenario: Auto-validación de faltas confirmadas
- **WHEN** registros tienen tipo_asistencia='falta' con datos consistentes
- **THEN** el sistema cambia validado_por_rh a 'MIGRATION_AUTO'

### Requirement: Validación de integridad
El sistema SHALL validar integridad referencial después de limpieza.

#### Scenario: Verificación post-limpieza
- **WHEN** se completa la limpieza
- **THEN** el sistema ejecuta queries de integridad y reporta estado

#### Scenario: Estado final de asistencia
- **WHEN** se verifica el estado post-limpieza
- **THEN** el sistema tiene 10,187 auto-validados, 270 en revisión manual, 1,782 validados por RH
