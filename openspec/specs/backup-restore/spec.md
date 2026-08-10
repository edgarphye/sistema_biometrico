# backup-restore

## Purpose
Realizar backups completos y diferenciales de la base de datos con compresión GZIP y verificación SHA256, restaurar con integridad comprobada y gestionar limpieza automática de respaldos antiguos.


### Requirement: Backup de base de datos
El sistema SHALL realizar backups completos y diferenciales de la BD con mysqldump, compresión GZIP y verificación SHA256.

#### Scenario: Backup completo
- **WHEN** se ejecuta backup.sh
- **THEN** el sistema genera archivo .sql.gz con hash SHA256

### Requirement: Restauración de backup
El sistema SHALL restaurar backups con verificación de integridad previa.

#### Scenario: Restauración exitosa
- **WHEN** se ejecuta restore con backup válido
- **THEN** el sistema restaura la BD al estado del backup

### Requirement: Gestión de backups
El sistema SHALL mantener backups organizados por fecha, con limpieza automática de backups antiguos y metadata tracking.

#### Scenario: Limpieza automática
- **WHEN** se ejecuta backup programado
- **THEN** el sistema elimina backups anteriores al período de retención configurado
