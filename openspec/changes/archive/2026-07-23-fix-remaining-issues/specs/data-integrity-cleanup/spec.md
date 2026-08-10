## ADDED Requirements

### Requirement: Limpieza de datos huérfanos
El sistema SHALL identificar y corregir datos huérfanos en la base de datos.

#### Scenario: Detección de registros huérfanos
- **WHEN** se ejecuta el script de limpieza
- **THEN** el sistema identifica registros sin relación válida

#### Scenario: Corrección automática
- **WHEN** un dato huérfano tiene corrección posible
- **THEN** el sistema aplica la corrección y registra en bitácora

### Requirement: Validación de integridad
El sistema SHALL validar integridad referencial después de limpieza.

#### Scenario: Verificación post-limpieza
- **WHEN** se completa la limpieza
- **THEN** el sistema ejecuta queries de integridad y reporta estado
