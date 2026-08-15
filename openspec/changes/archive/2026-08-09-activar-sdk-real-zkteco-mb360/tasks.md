## 1. Modo real y fix del SDK

- [x] 1.1 `config.php`: definir `BIOMETRIC_MODE` con fallback `'sdk'` (getenv `BIOMETRIC_MODE ?: 'sdk'`)
- [x] 1.2 `models/biometric/BiometricFactory.php`: resolver modo con fallback `'sdk'` (`getenv('BIOMETRIC_MODE') ?: (defined('BIOMETRIC_MODE') ? BIOMETRIC_MODE : 'sdk')`)
- [x] 1.3 `models/biometric/BiometricSDK.php`: `connectDevice()` no sobrescribe la instancia del SDK ya almacenada por la subclase (`if (!isset($this->connectedDevices[$deviceId]))`)

## 2. Probe UDP y caché de estado

- [x] 2.1 `models/biometric/ZKTecoSDK.php`: implementar `probeDeviceUdp($ip, $port, $timeoutSec=1)` con socket UDP propio, `CMD_CONNECT` y `SO_RCVTIMEO` ≤1s; retornar `true` si `sockets`/`Util` no están disponibles
- [x] 2.2 Usar el probe en `connectDeviceImplementation` antes de crear la instancia del SDK (error "no accesible" rápido)
- [x] 2.3 Usar el probe en `getDeviceStatusImplementation` para marcar `offline` sin intentar el SDK
- [x] 2.4 Caché de estado por dispositivo en `data/biometrico_status/device_{id}.json` con TTL 10s (`guardarEstadoCacheado`/`leerEstadoCacheado`)
- [x] 2.5 Reportar `device_time`/`user_count`/`record_count` en el estado de dispositivos conectados

## 3. Controlador de dispositivos tolerante

- [x] 3.1 `controllers/BiometricosController.php`: helper `obtenerDispositivo($id)` que cae a la configuración del catálogo con `status='desconectado'` y `error` si la conexión falla
- [x] 3.2 Usar `obtenerDispositivo` en `gestionar` y `ver` (antes `conectarDispositivo` directo)
- [x] 3.3 Respuestas del SDK incluyen `type` y `dispositivo_id`

## 4. Fix de módulos rotos

- [x] 4.1 `controllers/AgenteIAController.php`: quitar referencia a clase `Configuracion` inexistente (configModel sin uso)
- [x] 4.2 `controllers/HuellaController.php`: consultar `dispositivos_biometricos.activo` (no `estado`) y resolver `zkteo_id` desde `zkteo_empleado_mapeo`
- [x] 4.3 `views/huellas/index.php`: alinear queries con la tabla real (`activo`, `zkteo_id`)
- [x] 4.4 `controllers/ZKTecoController.php`: corregir require de `ZKTecoLogProcessor` a `models/`

## 5. Verificación

- [x] 5.1 `php -l` sin errores en los 9 archivos modificados
- [x] 5.2 `BiometricFactory::getImplementationInfo()` retorna `type=sdk`, `name=ZKTecoSDK`
- [x] 5.3 Dispositivo inaccesible: `getDeviceStatus()` marca `offline` en <1s y escribe caché en `data/biometrico_status/`
- [x] 5.4 Dispositivo accesible: `connectedDevices[id]` queda como instancia SDK y `updateEmployeeOnDevice`/`downloadAttendanceFromDevice` operan
- [x] 5.5 `/biometricos`, `/biometricos/gestionar/{id}` y `/biometricos/ver/{id}` renderizan con dispositivo apagado
- [x] 5.6 `/agente-ia/dashboard`, `/huellas` y `/zktecologs` dejan de dar 500
