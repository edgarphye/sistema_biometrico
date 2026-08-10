## Why

El sistema biométrico tiene múltiples problemas pendientes que afectan la integridad de datos, seguridad y funcionalidad. Se identificaron 76+ bugs en la sesión anterior, de los cuales 62 fueron corregidos pero quedan 14+ problemas pendientes que incluyen:
- 366 empleados sin jefe directo (80%)
- 262 empleados sin zkteo_id (57%)
- 604 retardos sin horario_id (100%)
- 12,563 registros de asistencia sin validar por RH (88%)
- 0 huellas dactilares registradas
- 0 fotos de empleados
- Tabla de días festivos vacía (ya corregida)
- Controllers con problemas de autenticación y require paths

## What Changes

- **Asignación de jefes directos**: Vincular empleados sin jefe con sus jefes según catálogo de mandos
- **Asignación de zkteo_id**: Vincular empleados con dispositivos biométricos
- **Migración de retardos**: Asignar horario_id a los 604 retardos existentes
- **Validación masiva de asistencia**: Procesar validación pendiente por RH
- **Captura de datos biométricos**: Configurar flujo para huellas y fotos
- **Corrección de controllers restantes**: MenuConfigController, remaining require paths
- **Mejoras de seguridad**: Rate limiting, validación de entrada mejorada

## Capabilities

### New Capabilities
- `employee-hierarchy-fix`: Corrección masiva de jerarquía de empleados (jefes directos)
- `biometric-linking`: Vinculación de empleados con dispositivos biométricos
- `attendance-validation`: Validación masiva de asistencia por RH
- `data-integrity-cleanup`: Limpieza y corrección de datos huérfanos

### Modified Capabilities
- `retardo-management`: Asignación de horario_id a retardos existentes
- `employee-management`: Campos obligatorios y validación mejorada

## Impact

- **Base de datos**: Migraciones SQL para asignar datos faltantes
- **Controllers**: Corrección de MenuConfigController y paths restantes
- **Models**: Mejoras en validación de Empleado.php
- **Services**: Actualización de ZKTecoAsistenciaInserterFinal para manejar retardos sin horario
- **APIs**: Endpoints para validación masiva de asistencia
- **Seguridad**: Rate limiting en endpoints públicos
