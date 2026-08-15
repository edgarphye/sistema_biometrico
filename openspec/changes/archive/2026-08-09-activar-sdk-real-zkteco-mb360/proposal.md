## Why

El sistema corría la capa biométrica en modo simulación (`BIOMETRIC_MODE=simulation`), por lo que "actualizar empleado" y "descargar asistencias" nunca operaron contra el reloj MB360 físico. Además, tres módulos (agente-ia, huellas, zktecologs) estaban rotos (error 500) y `BiometricSDK::connectDevice` sobrescribía la instancia del SDK rompiendo `setUser()`/`getAttendance()`. Al activar el modo real se detectó que el SDK jmrashed/zkteco bloquea ~60s contra dispositivos inaccesibles, degradando el dashboard.

## What Changes

- **Modo real activado por defecto**: `config.php` define `BIOMETRIC_MODE=sdk` y `BiometricFactory` retorna `ZKTecoSDK` (SDK jmrashed/zkteco sobre UDP puerto 4370) en lugar de `BiometricSimulation`. La simulación queda disponible solo si se configura explícitamente.
- **Fix `BiometricSDK::connectDevice`**: la instancia del SDK ya no se sobrescribe con `true`; se preserva el objeto conectado para que `setUser()` (actualizar empleado) y `getAttendance()` (descargar asistencias) funcionen.
- **Sondeo UDP previo**: en `connectDevice` y en el estado de dispositivos se envía un CMD_CONNECT con timeout ≤1s antes de la conexión completa, evitando los bloqueos de ~60s del SDK ante dispositivos inaccesibles.
- **Caché de estado de dispositivos**: el estado online/offline se cachea 10s en `data/biometrico_status` para que el dashboard no re-pruebe cada dispositivo en cada solicitud.
- **Gestión tolerante a fallos**: `BiometricosController` usa `obtenerDispositivo()` (no revienta si el dispositivo no responde) y devuelve `type`/`dispositivo_id` en las respuestas del SDK.
- **Fixes de módulos rotos**: `AgenteIAController` sin referencia a clase `Configuracion` inexistente; `HuellaController`/`views/huellas` consultan `dispositivos_biometricos.activo` y la tabla real `zkteo_empleado_mapeo` (`zkteo_id`); `ZKTecoController` carga `ZKTecoLogProcessor` desde `models/`.

> OBSERVACIÓN (pendiente de configuración, no de código): falta registrar la IP/puerto reales del MB360 en `dispositivos_biometricos` para que actualizar-empleado y descargar-asistencias operen contra el reloj físico.

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `biometric-integration`: la conexión opera con SDK real jmrashed/zkteco (UDP 4370) activado por defecto; se añade sondeo UDP previo y caché de estado para evitar bloqueos de 60s.
- `descarga-asistencias-dispositivo`: la descarga vía `getAttendance()` opera en modo real y responde rápido (≤1s) cuando el dispositivo es inaccesible.
- `actualizacion-empleados-dispositivo`: `setUser()` preserva la instancia del SDK conectada y reporta error rápido ante dispositivo inaccesible.
- `dispositivos-biometricos-admin`: el monitoreo de estado usa caché de 10s + sondeo UDP y la gestión es tolerante a fallos de conexión.
- `huellas-dactilares`: el listado de dispositivos consulta `dispositivos_biometricos.activo` y resuelve `zkteo_id` desde la tabla real `zkteo_empleado_mapeo`.
- `zkteco-parser-pipeline`: el módulo de logs carga `ZKTecoLogProcessor` desde `models/` (era un require roto en `controllers/`).

## Impact

- **Código**: `config.php`, `controllers/AgenteIAController.php`, `controllers/BiometricosController.php`, `controllers/HuellaController.php`, `controllers/ZKTecoController.php`, `models/biometric/BiometricFactory.php`, `models/biometric/BiometricSDK.php`, `models/biometric/ZKTecoSDK.php`, `views/huellas/index.php`.
- **Configuración**: se debe cargar la IP/puerto reales del MB360 en `dispositivos_biometricos` (tabla existente).
- **Runtime**: aparece el caché `data/biometrico_status` (estado de dispositivos por 10s).
