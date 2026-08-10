## ADDED Requirements

### Requirement: Conexión con dispositivos ZKTeco
El sistema SHALL conectarse a dispositivos biométricos ZKTeco mediante SDK (jmrashed/zkteco), TCP socket directo (puerto 4370) y HTTP API.

#### Scenario: Conexión vía SDK exitosa
- **WHEN** se ejecuta test de conexión a dispositivo con IP y puerto válidos
- **THEN** el sistema retorna estado de conexión exitosa

#### Scenario: Conexión vía TCP socket directo
- **WHEN** se usa ZKTecoMB360 para comunicación directa
- **THEN** el sistema establece conexión socket al puerto 4370 del dispositivo

### Requirement: Sincronización de empleados
El sistema SHALL sincronizar la lista de empleados entre la BD y los dispositivos biométricos.

#### Scenario: Sincronización exitosa
- **WHEN** se ejecuta sincronización de empleados
- **THEN** el sistema envía/recibe lista de empleados del dispositivo

### Requirement: Captura y verificación de huellas
El sistema SHALL capturar y verificar huellas dactilares contra el dispositivo.

#### Scenario: Enrolamiento de huella
- **WHEN** se captura huella de un empleado desde el sistema
- **THEN** el dispositivo almacena la plantilla y el sistema guarda referencia en huellas_empleados

#### Scenario: Verificación de identidad
- **WHEN** un empleado coloca su huella en el dispositivo
- **THEN** el sistema verifica contra la plantilla almacenada y registra la marcación

### Requirement: Lectura de archivos .dat
El sistema SHALL leer y procesar archivos .dat de asistencia generados por dispositivos ZKTeco.

#### Scenario: Procesamiento de archivo .dat
- **WHEN** se sube un archivo .dat al sistema
- **THEN** ZKTecoUniversalParser detecta formato, extrae registros y los inserta en asistencia

### Requirement: Modo simulación
El sistema SHALL soportar modo simulación para desarrollo sin dispositivos físicos.

#### Scenario: Simulación activa
- **WHEN** BIOMETRIC_SIMULATION=true
- **THEN** BiometricFactory retorna instancia de BiometricSimulation

### Requirement: Gestión de múltiples dispositivos
El sistema SHALL administrar múltiples dispositivos biométricos configurados en la tabla dispositivos_biometricos.

#### Scenario: Listado de dispositivos
- **WHEN** se consulta /dispositivos
- **THEN** el sistema lista todos los dispositivos con su estado y última sincronización

### Requirement: Logs de operaciones biométricas
El sistema SHALL registrar todas las operaciones biométricas en log_dispositivos y logs_dispositivo_zk.

#### Scenario: Registro de operación
- **WHEN** se ejecuta una operación biométrica
- **THEN** el sistema registra tipo_operacion, estado, tiempo_procesamiento, errores
