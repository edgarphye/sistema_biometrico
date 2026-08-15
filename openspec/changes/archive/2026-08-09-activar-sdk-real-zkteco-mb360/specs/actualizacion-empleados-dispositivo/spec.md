## MODIFIED Requirements

### Requirement: Actualización de nombre en dispositivo
El sistema SHALL actualizar el nombre completo del empleado en el dispositivo biométrico cuando se modifique en la base de datos, usando `$zk->setUser()` con el mismo ID pero sin re-enrolar la huella, preservando la instancia del SDK conectada.

#### Scenario: Actualización preserva la instancia del SDK
- **WHEN** se conecta el dispositivo vía `connectDevice()`
- **THEN** la instancia del SDK (`$zk`) se conserva en `connectedDevices[id]` (no se sobrescribe con un booleano) y `setUser()` opera contra el dispositivo real

#### Scenario: Nombre no actualizado por dispositivo desconectado
- **WHEN** se intenta actualizar un empleado en un dispositivo que no responde al sondeo UDP
- **THEN** el sistema retorna error indicando que el dispositivo no está disponible en ≤1s
