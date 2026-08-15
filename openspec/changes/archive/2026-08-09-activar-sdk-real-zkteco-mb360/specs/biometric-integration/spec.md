## MODIFIED Requirements

### Requirement: Conexión con dispositivos ZKTeco
El sistema SHALL conectarse a dispositivos biométricos ZKTeco mediante el SDK real `jmrashed/zkteco` sobre UDP puerto 4370, activado por defecto (`BIOMETRIC_MODE=sdk`). Antes de la conexión completa realiza un sondeo UDP corto (≤1s) para detectar dispositivos inaccesibles sin bloquear ~60s.

#### Scenario: Conexión vía SDK real exitosa
- **WHEN** se ejecuta test de conexión a dispositivo con IP y puerto válidos y accesibles
- **THEN** el sistema sondea UDP (≤1s), crea la instancia del SDK, conecta y retorna estado de conexión exitosa con `type` y `dispositivo_id`

#### Scenario: Dispositivo inaccesible detectado rápido
- **WHEN** el dispositivo no responde al sondeo UDP en ≤1s
- **THEN** el sistema retorna error claro de conexión sin esperar el timeout de ~60s del SDK

### Requirement: Modo simulación
El sistema SHALL soportar modo simulación para desarrollo sin dispositivos físicos, pero SOLO cuando se configura explícitamente (`BIOMETRIC_MODE=simulation`). Por defecto opera en modo SDK real.

#### Scenario: Simulación explícita
- **WHEN** `BIOMETRIC_MODE=simulation` está configurado
- **THEN** `BiometricFactory` retorna instancia de `BiometricSimulation`

#### Scenario: Modo real por defecto
- **WHEN** no se configura `BIOMETRIC_MODE`
- **THEN** `BiometricFactory` retorna `ZKTecoSDK` (modo real) y no simulación
