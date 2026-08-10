## ADDED Requirements

### Requirement: Sistema de migraciones
El sistema SHALL gestionar cambios de esquema de BD mediante Phinx 0.16 con migraciones en migrations/.

#### Scenario: Ejecución de migración
- **WHEN** se ejecuta migrate
- **THEN** Phinx aplica las migraciones pendientes en orden

### Requirement: Migraciones SQL y PHP
El sistema SHALL soportar migraciones en formato .sql (raw) y .php (Phinx).

#### Scenario: Migración SQL ejecutada
- **WHEN** se ejecuta migración SQL
- **THEN** el contenido SQL se ejecuta directamente sobre la BD

### Requirement: Seed de datos
El sistema SHALL permitir seed de datos iniciales (tipos de justificación, usuarios, etc.).

#### Scenario: Seed ejecutado
- **WHEN** se ejecuta seed
- **THEN** los datos iniciales se insertan en la BD
