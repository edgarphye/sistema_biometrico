## ADDED Requirements

### Requirement: Registro de entrada
El sistema SHALL registrar la hora de entrada del empleado con dispositivo_id, tipo_biometria, calidad_verificacion, y metadata del dispositivo.

#### Scenario: Registro exitoso via biométrico
- **WHEN** el empleado marca su huella en el dispositivo
- **THEN** el sistema registra en asistencia con hora_entrada, empleado_id, dispositivo_id

#### Scenario: Registro manual de entrada
- **WHEN** un administrador registra entrada manual
- **THEN** el sistema crea el registro con datos proporcionados

### Requirement: Registro de salida
El sistema SHALL registrar la hora de salida, actualizando el mismo registro de asistencia del día.

#### Scenario: Registro exitoso de salida
- **WHEN** el empleado marca salida
- **THEN** el sistema actualiza asistencia.hora_salida para el registro del día

### Requirement: Clasificación de tipo de asistencia
El sistema SHALL clasificar automáticamente la asistencia según: normal, con_retardo, con_ausencia, sin_registro, licencia_medica, dia_economico, comision_*, vacaciones, permiso_fallecimiento, retardo_menor, retardo_mayor, falta.

#### Scenario: Clasificación automática
- **WHEN** se procesa la asistencia del día
- **THEN** el sistema asigna tipo_asistencia según reglas de negocio contra el horario asignado

### Requirement: Cálculo de horas laborables
El sistema SHALL calcular horas trabajadas entre entrada y salida, restando tiempo no laborable si aplica.

#### Scenario: Cálculo de horas
- **WHEN** se consulta horas trabajadas
- **THEN** el sistema retorna la diferencia entre hora_entrada y hora_salida

### Requirement: Validación jerárquica en asistencia
El sistema SHALL soportar flujo de validación de jefe inmediato y RH en los registros de asistencia.

#### Scenario: Asistencia requiere validación
- **WHEN** un registro de asistencia es marcado como requiere_validacion_jefe
- **THEN** el sistema crea una validación pendiente para el jefe directo

### Requirement: Exportación de asistencia
El sistema SHALL exportar asistencia a Excel y PDF con filtros por fecha, empleado, área, tipo.

#### Scenario: Exportación a Excel
- **WHEN** se solicita exportar asistencia con filtros
- **THEN** el sistema genera archivo XLSX descargable
