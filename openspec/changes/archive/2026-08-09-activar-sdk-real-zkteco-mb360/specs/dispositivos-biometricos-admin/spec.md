## MODIFIED Requirements

### Requirement: Prueba de conexión
El sistema SHALL probar conexión con dispositivo vía SDK real (jmrashed/zkteco sobre UDP 4370), validando primero el alcance con un sondeo UDP corto (≤1s).

#### Scenario: Test de conexión exitoso
- **WHEN** se ejecuta test de conexión a IP:puerto válidos y accesibles
- **THEN** el sistema sondea UDP (≤1s), conecta vía SDK y retorna estado de conexión exitosa

#### Scenario: Test de conexión con dispositivo inaccesible
- **WHEN** el dispositivo no responde al sondeo UDP en ≤1s
- **THEN** el sistema retorna error claro sin esperar el timeout de ~60s del SDK

### Requirement: Monitoreo de estado
El sistema SHALL mostrar estado de cada dispositivo (conectado/desconectado), última sincronización, y logs de operaciones, consultando el estado con sondeo UDP corto y cacheándolo por 10s para no re-probar cada dispositivo en cada solicitud del dashboard.

#### Scenario: Estado del dispositivo
- **WHEN** se consulta /dispositivos/{id}/status
- **THEN** el sistema retorna estado actual y última sincronización

#### Scenario: Estado con caché de 10 segundos
- **WHEN** el estado de un dispositivo fue consultado hace menos de 10s
- **THEN** el sistema reutiliza el estado cacheado en `data/biometrico_status` sin re-sondear el dispositivo

#### Scenario: Dispositivo inaccesible en el dashboard
- **WHEN** un dispositivo no responde al sondeo UDP
- **THEN** el sistema lo muestra como offline sin bloquear el dashboard

### Requirement: Administración de dispositivos
El sistema SHALL permitir CRUD de dispositivos biométricos y renderizar su gestión/visualización aunque el dispositivo esté apagado, sin fallar la página.

#### Scenario: Gestión con dispositivo apagado
- **WHEN** se abre /biometricos/gestionar/{id} o /biometricos/ver/{id} y la conexión falla
- **THEN** el sistema muestra la configuración del catálogo con estado "desconectado" y el error, en lugar de un error 500
