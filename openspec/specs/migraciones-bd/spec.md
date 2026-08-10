# migraciones-bd

## Purpose
Gestionar cambios de esquema de base de datos mediante Phinx con migraciones SQL y PHP, soporte para seed de datos iniciales, control de versiones del esquema y migraciones de datos puntuales.

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

### Requirement: Migraciones de datos puntuales
El sistema SHALL soportar scripts SQL sueltos en sql/ para migraciones de datos específicas.

#### Scenario: fix_hierarchy_v2.sql
- **WHEN** se ejecuta fix_hierarchy_v2.sql
- **THEN** el sistema asigna jefe_directo_id a todos los empleados activos usando catalogos_mandos

#### Scenario: fix_biometric_linking.sql
- **WHEN** se ejecuta fix_biometric_linking.sql
- **THEN** el sistema asigna zkteo_id = EMP-{id} a empleados sin vinculación biométrica

#### Scenario: fix_retardo_horarios.sql
- **WHEN** se ejecuta fix_retardo_horarios.sql
- **THEN** el sistema asigna horario_id=1 a retardos sin horario

#### Scenario: fix_attendance_validation.sql
- **WHEN** se ejecuta fix_attendance_validation.sql
- **THEN** el sistema clasifica registros de asistencia como MIGRATION_AUTO o REVISION_MANUAL

#### Scenario: Limpieza de registros vacíos
- **WHEN** se ejecuta limpieza post-migración
- **THEN** el sistema elimina 2,113 registros por_definir sin datos y clasifica 270 registros parciales
