# actualizacion-empleados-dispositivo

## Purpose

Actualizar los datos de empleados (nombre, privilegio, password) directamente en el dispositivo biométrico sin necesidad de re-enrolar la huella, buscando al empleado por su ID registrado (zkteco_id) y propagando los cambios desde la BD al dispositivo.

### Requirement: Actualización de nombre en dispositivo

El sistema SHALL actualizar el nombre completo del empleado en el dispositivo biométrico cuando se modifique en la base de datos, usando `$zk->setUser()` con el mismo ID pero sin re-enrolar la huella, preservando la instancia del SDK conectada.

#### Scenario: Nombre actualizado en dispositivo

- **WHEN** se modifica el nombre de un empleado en el sistema y se envía POST a `/biometricos/actualizar-empleado` con dispositivo_id y empleado_id
- **THEN** el sistema conecta al dispositivo, busca al empleado por zkteco_id, llama a `setUser()` con el nuevo nombre, y retorna success=true

#### Scenario: Actualización preserva la instancia del SDK

- **WHEN** se conecta el dispositivo vía `connectDevice()`
- **THEN** la instancia del SDK (`$zk`) se conserva en `connectedDevices[id]` (no se sobrescribe con un booleano) y `setUser()` opera contra el dispositivo real

#### Scenario: Nombre no actualizado por dispositivo desconectado

- **WHEN** se intenta actualizar un empleado en un dispositivo que no responde al sondeo UDP
- **THEN** el sistema retorna error indicando que el dispositivo no está disponible en ≤1s

### Requirement: Búsqueda por ID registrado

El sistema SHALL buscar al empleado en el dispositivo usando `zkteco_id` (padded a 5 dígitos) como identificador único, verificando que exista antes de actualizar.

#### Scenario: Empleado encontrado por zkteco_id

- **WHEN** se busca un empleado con zkteco_id válido en el dispositivo
- **THEN** el sistema localiza el usuario en el dispositivo y procede con la actualización

### Requirement: Propagación de cambios desde BD

El sistema SHALL leer los datos actualizados desde la tabla `empleados` y enviarlos al dispositivo, actualizando nombre, password (RFC truncado) y privilegio.

#### Scenario: Propagación exitosa

- **WHEN** se ejecuta `updateEmployeeOnDevice($deviceId, $employeeId)`
- **THEN** el sistema obtiene nombre, apellido y RFC del empleado desde la BD y llama a `$zk->setUser()` con los valores actualizados

### Requirement: Actualización masiva

El sistema SHALL permitir actualizar todos los empleados registrados en un dispositivo en una sola operación.

#### Scenario: Actualización masiva exitosa

- **WHEN** se envía POST a `/biometricos/actualizar-empleados-masivo` con dispositivo_id
- **THEN** el sistema itera sobre todos los empleados con huella registrada y actualiza sus datos en el dispositivo

### Requirement: Log de operaciones

El sistema SHALL registrar cada actualización en `log_dispositivos` con tipo_operacion='actualizacion', resultado, empleado_id y dispositivo_id.

#### Scenario: Log de actualización

- **WHEN** se actualiza un empleado en el dispositivo
- **THEN** el sistema registra la operación en el log con estado exitoso/fallido
