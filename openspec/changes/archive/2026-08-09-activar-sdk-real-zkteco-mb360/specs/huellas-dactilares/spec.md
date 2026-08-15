## MODIFIED Requirements

### Requirement: Captura de huellas
El sistema SHALL capturar huellas dactilares desde dispositivos ZKTeco y almacenar plantillas en huellas_empleados, listando los dispositivos activos mediante la tabla real `dispositivos_biometricos` (campo `activo`) y resolviendo el `zkteo_id` del empleado desde la tabla `zkteo_empleado_mapeo`.

#### Scenario: Listado de dispositivos activos
- **WHEN** se consultan los dispositivos para captura de huella
- **THEN** el sistema filtra por `dispositivos_biometricos.activo` (no por una columna `estado` inexistente)

#### Scenario: Resolución de zkteo_id desde zkteo_empleado_mapeo
- **WHEN** se procesa un empleado para registrar su huella en el dispositivo
- **THEN** el sistema obtiene el `zkteo_id` real desde `zkteo_empleado_mapeo` en lugar de generarlo artificialmente

#### Scenario: Conexión con dispositivo
- **WHEN** se selecciona un dispositivo biométrico
- **THEN** el sistema valida conexión vía POST a /biometricos/test-dispositivo/{deviceId} (con sondeo UDP corto)
