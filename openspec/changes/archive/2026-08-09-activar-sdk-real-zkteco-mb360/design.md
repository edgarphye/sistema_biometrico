## Context

El sistema venía corriendo toda la capa biométrica en **modo simulación** (`BiometricSimulation`): "actualizar empleado en dispositivo" y "descargar asistencias" nunca operaron contra el reloj físico. Al inspeccionar el código se encontró además:

1. **`BiometricSDK::connectDevice` sobrescribía la conexión**: `$this->connectedDevices[$deviceId] = $result` donde `$result` era el array de info (y en un estado previo `true`), pisando la instancia real del SDK que guarda `ZKTecoSDK::connectDeviceImplementation` (`$this->connectedDevices[$deviceId] = $this->zkInstance`). Consecuencia: `setUser()`/`getAttendance()` fallaban porque `$zk` no era el objeto SDK.
2. **El SDK jmrashed/zkteco bloquea ~60s** por intento UDP contra dispositivos apagados/inaccesibles, lo que hacía inutilizable el dashboard de dispositivos.
3. **Tres módulos rotos (error 500)**: `AgenteIAController` (referencia a clase `Configuracion` inexistente), `HuellaController`/`views/huellas` (queries contra columnas que no existen: `estado` en `dispositivos_biometricos`, y `zkteo_id` inventado `EMP-{id}`), y `ZKTecoController` (require de `ZKTecoLogProcessor` desde `controllers/` cuando vive en `models/`).

Stack: PHP 8.4 (MVC custom), MariaDB, SDK `jmrashed/zkteco` (UDP puerto 4370). El reloj objetivo es un **ZKTeco MB360**. La librería PHP `sockets` debe estar habilitada para el probe UDP y el SDK.

## Goals / Non-Goals

**Goals:**
- Operar la capa biométrica en modo **real** por defecto (`BIOMETRIC_MODE=sdk`) contra dispositivos físicos ZKTeco.
- Que `actualizar-empleado` (`setUser`) y `descargar-asistencias` (`getAttendance`) funcionen contra el dispositivo, preservando la instancia del SDK conectada.
- Respuesta rápida (<~1s) y clara cuando el dispositivo es inaccesible (probe UDP) y caché de estado para el dashboard.
- Reparar los 3 módulos rotos.

**Non-Goals:**
- Configurar la IP/puerto reales del MB360 en BD (pendiente de operación, no de código — ver Decisions 6).
- Re-enrolar huellas ni verificación biométrica en vivo desde el navegador.
- Cambios de esquema de BD.

## Decisions

### 1. Modo real por defecto en `BiometricFactory` + `config.php`
`BiometricFactory` ahora resuelve el modo con fallback a `'sdk'` (`getenv('BIOMETRIC_MODE') ?: (defined('BIOMETRIC_MODE') ? BIOMETRIC_MODE : 'sdk')`), y `config.php` define `BIOMETRIC_MODE = getenv('BIOMETRIC_MODE') ?: 'sdk'`. La simulación sigue disponible si se configura explícitamente (`BIOMETRIC_MODE=simulation`). Se evita activarla silenciosamente otra vez.

### 2. `BiometricSDK::connectDevice` ya no pisa la instancia del SDK
Se cambia a: solo guardar en `connectedDevices[$deviceId]` si la implementación no lo hizo (`if (!isset(...))`). La subclase `ZKTecoSDK` almacena el objeto `$this->zkInstance`; el marcador del padre ya no lo reemplaza por el array de información. Esto restaura `setUser()`/`getAttendance()` (actualizar empleado / descargar asistencia).

### 3. Probe UDP corto previo a la conexión
`ZKTecoSDK::probeDeviceUdp()` abre un socket UDP propio, envía el header `CMD_CONNECT` (vía `Util::createHeader`) con `SO_RCVTIMEO` de ≤1s y considera accesible si recibe respuesta. Se usa:
- en `connectDeviceImplementation` (antes de crear la instancia del SDK),
- en `getDeviceStatusImplementation` (para marcar offline sin intentar el SDK).

Si la extensión `sockets` no está cargada o `Util` no existe, retorna `true` (deja que la conexión real decida). Esto evita los ~60s de bloqueo del SDK jmrashed contra dispositivos inaccesibles.

### 4. Caché de estado de dispositivos (10s)
`getDeviceStatusImplementation` persiste el estado online/offline de cada dispositivo en `data/biometrico_status/device_{id}.json` con marca de tiempo `_ts` (métodos `guardarEstadoCacheado`/`leerEstadoCacheado`, TTL 10s). El dashboard reusa el estado cacheado y no re-sondea cada dispositivo en cada request. Para dispositivos conectados, la consulta de estado además reporta `device_time`, `user_count` y `record_count` desde el SDK.

### 5. `BiometricosController` tolerante a fallos de conexión
Nuevo helper `obtenerDispositivo($id)`: intenta `conectarDispositivo()`; si lanza excepción, devuelve la configuración del catálogo con `status='desconectado'`, `type`/`dispositivo_id` y `error` — de modo que `gestionar` y `ver` renderizan en vez de dar 500. La respuesta del SDK ahora incluye `type` y `dispositivo_id`.

### 6. Pendiente de operación (no de código)
Para operar contra el reloj físico hace falta registrar la IP/puerto reales del **MB360** en `dispositivos_biometricos` (`ip_address`, `puerto`). Sin ello, el probe UDP y la conexión fallan con error claro ("Dispositivo no accesible / no configurado").

### 7. Fixes de módulos rotos
- `AgenteIAController`: se elimina la referencia a la clase `Configuracion` inexistente (`configModel` no usado).
- `HuellaController` + `views/huellas/index.php`: se consulta `dispositivos_biometricos.activo` (no `estado`) y el `zkteo_id` se resuelve desde la tabla real `zkteo_empleado_mapeo` (en vez de fabricar `EMP-{id}`).
- `ZKTecoController`: `require` de `ZKTecoLogProcessor` apunta a `models/ZKTecoLogProcessor.php` (estaba en `controllers/`).

## Verificación

- `php -l` sin errores en los 9 archivos modificados.
- `php -r "var_dump((new BiometricFactory())->getImplementationInfo());"` retorna `type=sdk`, `name=ZKTecoSDK`.
- Con un dispositivo inaccesible: `getDeviceStatus()` marca `offline` en <1s (probe UDP) y escribe caché en `data/biometrico_status/`.
- Con un dispositivo accesible: `connectDevice` deja `connectedDevices[id]` como instancia SDK y `updateEmployeeOnDevice`/`downloadAttendanceFromDevice` operan (actualizar empleado / descargar asistencia) registrando en `log_dispositivos`.
- Endpoints `/biometricos` (dashboard), `/biometricos/gestionar/{id}` y `/biometricos/ver/{id}` renderizan aunque el dispositivo esté apagado.
- `/agente-ia/dashboard`, `/huellas` y `/zktecologs` dejan de dar 500.
