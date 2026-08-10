# ci-cd-pipeline

## Purpose
Automatizar la integración continua y despliegue mediante GitHub Actions con pruebas unitarias, escaneo de seguridad, compilación de assets y despliegue a staging y producción con rollback automático.


### Requirement: Pipeline CI/CD
El sistema SHALL ejecutar pipeline CI/CD en GitHub Actions con jobs: unit-tests (PHP 8.2, PHPUnit + PHPStan + Codecov), security-scan (Composer audit + PHPStan level 5), build-assets (Node 18, minificación), deploy-staging (manual, SSH, post-deploy tests), deploy-production (manual, backup previo, health check, rollback automático).

#### Scenario: Push a main
- **WHEN** se hace push a main
- **THEN** GitHub Actions ejecuta tests, security scan y build

### Requirement: Deploy a staging
El sistema SHALL desplegar a staging con: backup, git pull, composer install, migraciones, cache clear, health check.

#### Scenario: Deploy exitoso
- **WHEN** se ejecuta deploy-staging manualmente
- **THEN** el sistema se despliega a staging y pasa health check

### Requirement: Deploy a producción con rollback
El sistema SHALL desplegar a producción con backup previo de BD, health check con retry y rollback automático en fallo.

#### Scenario: Rollback automático
- **WHEN** el health check falla post-deploy
- **THEN** el sistema ejecuta rollback (git reset + restore BD)

### Requirement: Mantenimiento semanal
El sistema SHALL ejecutar mantenimiento semanal (cron) con actualización de paquetes, reinicio de servicios y generación de reportes.

#### Scenario: Mantenimiento automático
- **WHEN** se ejecuta el cron semanal
- **THEN** el sistema actualiza paquetes, reinicia servicios y genera reportes
