# mantenimiento-sistema

## Purpose
Proveer un panel de mantenimiento con diagnóstico de integridad de base de datos, limpieza de logs, gestión de backups, estadísticas del sistema y configuración avanzada.


### Requirement: Panel de mantenimiento
El sistema SHALL proveer un panel de mantenimiento (maintenance.php) con secciones: Estado del Sistema, Gestión de Backup, Restauración BD, Limpieza/Optimización, Diagnósticos, Configuración Avanzada, Reportes/Estadísticas, Seguridad/Auditoría.

#### Scenario: Estado del sistema
- **WHEN** se accede al panel de mantenimiento
- **THEN** muestra estado de BD, conexiones, espacio en disco, versión del sistema

### Requirement: Diagnóstico de integridad
El sistema SHALL ejecutar diagnósticos de integridad de BD con verificación SHA256 en backups.

#### Scenario: Verificación de integridad
- **WHEN** se ejecuta diagnóstico
- **THEN** el sistema verifica integridad de tablas, índices y datos

### Requirement: Limpieza de logs
El sistema SHALL permitir limpiar logs antiguos según configuración de retención.

#### Scenario: Limpieza programada
- **WHEN** se ejecuta limpieza de logs
- **THEN** el sistema elimina logs anteriores al período configurado

### Requirement: Estadísticas del sistema
El sistema SHALL mostrar estadísticas: cantidad de empleados, usuarios, registros de asistencia, retardos, sanciones, espacio usado.

#### Scenario: Visualización de estadísticas
- **WHEN** se accede a la sección de estadísticas
- **THEN** el sistema muestra las métricas agregadas

### Sub-componentes relacionados
- **cache-sistema** — Sistema de caché centralizado con Redis y fallback a archivo (`helpers/CacheManager.php`), integrado como infraestructura del rendimiento del sistema
- **optimizacion-rendimiento** — Motor de optimización automática con caché de consultas, sugerencias de índices y monitoreo de queries lentas (`helpers/PerformanceOptimizer.php`), integrado como infraestructura del rendimiento del sistema
