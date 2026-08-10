# dias-economicos

## Purpose
Administrar solicitudes de días económicos con validación de elegibilidad, límites por ciclo escolar, períodos de espera y aprobación que actualiza automáticamente los registros de asistencia.


### Requirement: Solicitud de días económicos
El sistema SHALL permitir solicitar días económicos con máximo 9 por ciclo escolar (16 julio - 15 julio), 1-3 días continuos, sin lunes ni viernes salvo excepciones.

#### Scenario: Solicitud válida
- **WHEN** un empleado solicita 2 días económicos en martes-miércoles
- **THEN** el sistema crea la solicitud en dias_economicos

#### Scenario: Solicitud en lunes rechazada
- **WHEN** un empleado solicita día económico en lunes
- **THEN** el sistema rechaza la solicitud

### Requirement: Validación de elegibilidad
El sistema SHALL validar: plaza_confianza excluida, antigüedad 6+ meses, aviso 1 día antes, saldo disponible, período de espera cumplido.

#### Scenario: Empleado sin antigüedad
- **WHEN** un empleado con menos de 6 meses solicita día económico
- **THEN** el sistema rechaza por antigüedad insuficiente

### Requirement: Períodos de espera
El sistema SHALL aplicar períodos de espera: 3 días usados = 30 días espera, 2 días = 15 días, 1 día = 7 días.

#### Scenario: Período de espera activo
- **WHEN** un empleado solicitó 3 días y no han pasado 30 días
- **THEN** el sistema rechaza nueva solicitud

### Requirement: Aprobación de días económicos
El sistema SHALL gestionar aprobación que actualiza asistencia para los días solicitados como dia_economico.

#### Scenario: Aprobación exitosa
- **WHEN** se aprueba una solicitud de día económico
- **THEN** el sistema actualiza los registros de asistencia correspondientes a tipo dia_economico
