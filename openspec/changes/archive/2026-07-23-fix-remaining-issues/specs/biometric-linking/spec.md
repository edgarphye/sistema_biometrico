## ADDED Requirements

### Requirement: Vinculación de empleados con dispositivos
El sistema SHALL vincular empleados con dispositivos biométricos usando zkteo_id.

#### Scenario: Asignación de zkteo_id
- **WHEN** se ejecuta el script de vinculación biométrica
- **THEN** el sistema asigna zkteo_id a empleados según dispositivo configurado

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
