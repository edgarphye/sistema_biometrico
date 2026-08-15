# biometric-integration

## Purpose
Integrar el sistema con dispositivos biométricos ZKTeco para sincronización de empleados, captura y verificación de huellas, lectura de archivos .dat, administración de múltiples dispositivos y dashboard biométrico.


### Requirement: Conexión con dispositivos ZKTeco
El sistema SHALL conectarse a dispositivos biométricos ZKTeco mediante el SDK real `jmrashed/zkteco` sobre UDP puerto 4370, activado por defecto (`BIOMETRIC_MODE=sdk`). Antes de la conexión completa realiza un sondeo UDP corto (≤1s) para detectar dispositivos inaccesibles sin bloquear ~60s.

#### Scenario: Conexión vía SDK real exitosa
- **WHEN** se ejecuta test de conexión a dispositivo con IP y puerto válidos y accesibles
- **THEN** el sistema sondea UDP (≤1s), crea la instancia del SDK, conecta y retorna estado de conexión exitosa con `type` y `dispositivo_id`

#### Scenario: Dispositivo inaccesible detectado rápido
- **WHEN** el dispositivo no responde al sondeo UDP en ≤1s
- **THEN** el sistema retorna error claro de conexión sin esperar el timeout de ~60s del SDK

### Requirement: Sincronización de empleados
El sistema SHALL sincronizar la lista de empleados entre la BD y los dispositivos biométricos.

#### Scenario: Sincronización exitosa
- **WHEN** se ejecuta sincronización de empleados
- **THEN** el sistema envía/recibe lista de empleados del dispositivo

### Requirement: Vinculación de empleados con dispositivos
El sistema SHALL vincular empleados con dispositivos biométricos usando zkteo_id.

#### Scenario: Asignación de zkteo_id
- **WHEN** se ejecuta el script de vinculación biométrica
- **THEN** el sistema asigna zkteo_id = EMP-{id} a empleados según dispositivo configurado

#### Scenario: 457 empleados vinculados
- **WHEN** se completa la vinculación
- **THEN** todos los empleados activos tienen zkteo_id asignado

#### Scenario: Empleado sin dispositivo asignado
- **WHEN** un empleado no tiene dispositivo configurado
- **THEN** el sistema asigna dispositivo por defecto según área

### Requirement: Sincronización con dispositivo
El sistema SHALL sincronizar datos de empleados con dispositivos ZKTeco.

#### Scenario: Envío de datos a dispositivo
- **WHEN** se envían empleados al dispositivo
- **THEN** el sistema registra éxito/fallo por empleado

#### Scenario: Dispositivo offline
- **WHEN** el dispositivo no responde
- **THEN** el sistema marca la sincronización como pendiente

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
El sistema SHALL soportar modo simulación para desarrollo sin dispositivos físicos, pero SOLO cuando se configura explícitamente (`BIOMETRIC_MODE=simulation`). Por defecto opera en modo SDK real.

#### Scenario: Simulación explícita
- **WHEN** `BIOMETRIC_MODE=simulation` está configurado
- **THEN** `BiometricFactory` retorna instancia de `BiometricSimulation`

#### Scenario: Modo real por defecto
- **WHEN** no se configura `BIOMETRIC_MODE`
- **THEN** `BiometricFactory` retorna `ZKTecoSDK` (modo real) y no simulación

### Requirement: Gestión de múltiples dispositivos
El sistema SHALL administrar múltiples dispositivos biométricos configurados en la tabla dispositivos_biometricos.

#### Scenario: Listado de dispositivos
- **WHEN** se consulta /dispositivos
- **THEN** el sistema lista todos los dispositivos con su estado y última sincronización

### Requirement: Dashboard biométrico con listado de empleados
El sistema SHALL mostrar en /biometricos un dashboard con métricas, dispositivos, logs recientes y una tabla de empleados con huella dactilar registrada que permita búsqueda, filtrado y edición rápida.

#### Scenario: Tabla de empleados con huella
- **WHEN** se carga /biometricos
- **THEN** el sistema renderiza tabla con empleados activos que tienen huella_dactilar, con columnas: nombre, área, puesto, jerarquía, y botón de edición

#### Scenario: Búsqueda y filtros en tabla de empleados
- **WHEN** el usuario escribe en el campo de búsqueda o selecciona filtro por área/jerarquía
- **THEN** el sistema filtra las filas de la tabla en tiempo real (client-side) y actualiza el contador de resultados

### Requirement: Logs de operaciones biométricas
El sistema SHALL registrar todas las operaciones biométricas en log_dispositivos y logs_dispositivo_zk.

#### Scenario: Registro de operación
- **WHEN** se ejecuta una operación biométrica
- **THEN** el sistema registra tipo_operacion, estado, tiempo_procesamiento, errores

### Sub-componentes relacionados
- **zkteco-parser-pipeline** (`openspec/specs/zkteco-parser-pipeline/`) — Spec dedicado para el pipeline de procesamiento de archivos .dat con detección de formato, mapeo dinámico de columnas, parsing universal e inserción inteligente (7 modelos: UniversalParser, FormatDetector, Mapper, MappingManager, Inserters, LogProcessor)
- **actualizacion-empleados-dispositivo** (`openspec/specs/actualizacion-empleados-dispositivo/`) — Spec dedicado para actualizar datos de empleados (nombre, privilegio) en el dispositivo biométrico sin re-enrolar huella
- **descarga-asistencias-dispositivo** (`openspec/specs/descarga-asistencias-dispositivo/`) — Spec dedicado para descarga masiva de registros de asistencia desde el dispositivo biométrico
