# ciclos-trabajo

## Purpose
Administrar ciclos de trabajo con bloques de horario por día de semana, asignación a empleados y resolución del horario aplicable según el ciclo y la fecha consultada.


### Requirement: CRUD de ciclos
El sistema SHALL permitir crear ciclos con nombre, fecha_inicio, num_ciclo, unidad_ciclo (Semana/Mes) y activo.

#### Scenario: Creación de ciclo
- **WHEN** se envía POST a /ciclos/crear con datos válidos
- **THEN** el sistema crea el ciclo en tabla ciclos

### Requirement: Gestión de bloques de ciclo
El sistema SHALL administrar bloques dentro de cada ciclo, asociando día_semana (0=lunes..6=domingo), hora_inicio, hora_fin, horario_id y semana_numero.

#### Scenario: Creación de bloque
- **WHEN** se agrega un bloque a un ciclo
- **THEN** el sistema crea registro en bloques_ciclo

### Requirement: Asignación de ciclos a empleados
El sistema SHALL asignar empleados a ciclos con fechas de inicio/fin mediante empleados_ciclos.

#### Scenario: Asignación de empleado a ciclo
- **WHEN** se asigna un empleado a un ciclo
- **THEN** el sistema crea registro en empleados_ciclos

### Requirement: Resolución de horario desde ciclo
El sistema SHALL resolver el horario de un empleado a partir de su ciclo asignado para una fecha y día de semana específicos.

#### Scenario: Horario resuelto desde ciclo
- **WHEN** se consulta horario de empleado asignado a ciclo
- **THEN** el sistema retorna el horario del bloque correspondiente al día
