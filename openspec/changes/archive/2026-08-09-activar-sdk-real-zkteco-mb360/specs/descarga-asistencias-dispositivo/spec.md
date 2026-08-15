## MODIFIED Requirements

### Requirement: Descarga masiva de asistencias
El sistema SHALL conectar al dispositivo biométrico mediante el SDK real preservado en la instancia conectada, descargar todos los registros de asistencia mediante `$zk->getAttendance()`, y procesarlos para inserción en la BD.

#### Scenario: Descarga contra dispositivo inaccesible
- **WHEN** el dispositivo no responde al sondeo UDP
- **THEN** el sistema retorna error claro de dispositivo no accesible en ≤1s, sin bloquear el timeout de ~60s del SDK
