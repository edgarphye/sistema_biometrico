# descarga-asistencias-dispositivo

## Purpose

Descargar todos los registros de asistencia (marcaciones) desde el dispositivo biométrico ZKTeco, procesarlos e insertarlos en la tabla `asistencia`, con detección de duplicados, clasificación entrada/salida y registro de auditoría.

### Requirement: Descarga masiva de asistencias

El sistema SHALL conectar al dispositivo biométrico, descargar todos los registros de asistencia mediante `$zk->getAttendance()`, y procesarlos para inserción en la BD.

#### Scenario: Descarga exitosa

- **WHEN** se envía POST a `/biometricos/descargar-asistencias` con dispositivo_id
- **THEN** el sistema conecta al dispositivo, obtiene todos los registros vía `getAttendance()`, los procesa y retorna cantidad de registros importados

#### Scenario: Descarga sin registros nuevos

- **WHEN** se ejecuta descarga y no hay registros nuevos
- **THEN** el sistema retorna 0 registros procesados

### Requirement: Procesamiento de registros

El sistema SHALL procesar cada registro de asistencia: buscar empleado por zkteco_id, clasificar como entrada/salida según hora del día y registrar en `asistencia` evitando duplicados.

#### Scenario: Registro procesado

- **WHEN** se descarga un registro de asistencia válido
- **THEN** el sistema busca empleado por zkteco_id, determina si es entrada o salida, y lo inserta en la tabla `asistencia`

#### Scenario: Duplicado ignorado

- **WHEN** ya existe un registro para el mismo empleado+fecha+tipo
- **THEN** el sistema omite el duplicado y continúa con el siguiente

### Requirement: Resolución de empleado por zkteco_id

El sistema SHALL resolver el empleado_id a partir del zkteco_id usando 3 niveles: columna `empleados.zkteo_id`, tabla `zkteo_empleado_mapeo`, y fallback por CAST a CHAR.

#### Scenario: Empleado resuelto por zkteco_id

- **WHEN** se procesa un registro con zkteco_id conocido
- **THEN** el sistema encuentra el empleado_id correspondiente

#### Scenario: Empleado no encontrado

- **WHEN** el zkteco_id no existe en la BD
- **THEN** el sistema omite el registro y lo marca como error en el log

### Requirement: Registro en log_dispositivos

El sistema SHALL registrar la operación de descarga en `log_dispositivos` con total de registros descargados, procesados, duplicados y errores.

#### Scenario: Log de descarga

- **WHEN** se completa la descarga
- **THEN** el sistema crea un registro en log_dispositivos con las estadísticas de la operación

### Requirement: Endpoint para descarga desde vista

El sistema SHALL exponer POST `/biometricos/descargar-asistencias/{id}` para descarga desde la vista de detalle del dispositivo.

#### Scenario: Descarga desde vista de dispositivo

- **WHEN** se hace clic en "Descargar Asistencias" en la vista del dispositivo
- **THEN** el sistema ejecuta la descarga y muestra el resultado en la interfaz
