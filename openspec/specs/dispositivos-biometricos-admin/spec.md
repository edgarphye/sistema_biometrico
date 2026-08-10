# dispositivos-biometricos-admin

## Purpose
Administrar el catálogo de dispositivos biométricos con operaciones CRUD, prueba de conexión vía SDK y TCP socket, sincronización de empleados y monitoreo de estado en tiempo real.


### Requirement: Administración de dispositivos
El sistema SHALL permitir CRUD de dispositivos biométricos con: dispositivo_id, nombre, ip, puerto (default 4370), modelo, ubicacion, activo, ultimo_sincronismo, tipo, sede, capacidades (JSON), configuracion_adicional (JSON), notas.

#### Scenario: Creación de dispositivo
- **WHEN** se envía POST a /dispositivos/crear con datos válidos
- **THEN** el sistema crea el dispositivo en dispositivos_biometricos

### Requirement: Prueba de conexión
El sistema SHALL probar conexión con dispositivo vía SDK y TCP socket.

#### Scenario: Test de conexión exitoso
- **WHEN** se ejecuta test de conexión a IP:puerto válidos
- **THEN** el sistema retorna estado de conexión exitosa

### Requirement: Sincronización
El sistema SHALL sincronizar empleados y datos entre la BD y el dispositivo.

#### Scenario: Sincronización de empleados
- **WHEN** se ejecuta syncEmployees()
- **THEN** el sistema envía lista de empleados activos al dispositivo

### Requirement: Monitoreo de estado
El sistema SHALL mostrar estado de cada dispositivo (conectado/desconectado), última sincronización, y logs de operaciones.

#### Scenario: Estado del dispositivo
- **WHEN** se consulta /dispositivos/{id}/status
- **THEN** el sistema retorna estado actual y última sincronización
