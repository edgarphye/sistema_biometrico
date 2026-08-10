# horarios-laborales

## Purpose
Administrar horarios laborales con asignación por día de semana y por períodos específicos a empleados, resolución del horario aplicable por fecha y clasificación de retardos según minutos de retraso.


### Requirement: CRUD de horarios laborales
El sistema SHALL permitir crear, leer, actualizar y desactivar horarios con: nombre, hora_entrada, hora_salida, tolerancia_minutos (default 10), descripcion, color, sede, activo, inicio/fin marcaje entrada/salida, debe_marcar_entrada, debe_marcar_salida, cuenta_dia_trabajo, cuenta_minutos.

#### Scenario: Creación de horario exitosa
- **WHEN** se envía POST a /horarios/crear con datos válidos
- **THEN** el sistema crea el horario en horarios_laborales

### Requirement: Asignación de horario por día de semana
El sistema SHALL asignar horarios a empleados por día de semana (lunes a domingo) y sede mediante la tabla horarios_empleados.

#### Scenario: Asignación de horario a empleado
- **WHEN** se asigna un horario a un empleado para un día específico
- **THEN** el sistema crea registro en horarios_empleados con unique_empleado_dia_sede

### Requirement: Asignación directa por fechas
El sistema SHALL asignar horarios a empleados con fechas específicas de inicio/fin mediante empleado_horarios.

#### Scenario: Asignación por período
- **WHEN** se asigna horario con fecha_inicio y fecha_fin
- **THEN** el sistema registra en empleado_horarios con el período

### Requirement: Consulta de horario por fecha
El sistema SHALL determinar el horario aplicable a un empleado en una fecha específica, verificando ciclo, asignación fija y default.

#### Scenario: Consulta de horario del día
- **WHEN** se solicita horario para empleado+fecha
- **THEN** el sistema retorna el horario aplicable según prioridad: ciclo > asignación fija > default

### Requirement: Clasificación de retardos contra horario
El sistema SHALL clasificar retardos según los minutos de retraso contra el horario asignado: 0-10 tolerancia, 11-20 retardo_menor, 21-30 retardo_mayor, 31+ falta.

#### Scenario: Clasificación retardo menor
- **WHEN** empleado llega 15 minutos tarde
- **THEN** sistema clasifica como retardo_menor

#### Scenario: Clasificación falta
- **WHEN** empleado llega 35 minutos tarde
- **THEN** sistema clasifica como falta

### Requirement: Asignación de horario_id a retardos
El sistema SHALL asignar horario_id a todos los retardos para mantener integridad referencial.

#### Scenario: Migración de retardos existentes
- **WHEN** se ejecuta fix_retardo_horarios.sql
- **THEN** el sistema asigna horario_id=1 (matutino) a los 604 retardos sin horario

#### Scenario: Retardos con horario asignado
- **WHEN** se verifica integridad
- **THEN** el 100% de retardos tienen horario_id asignado

### Sub-componentes relacionados
- **clasificador-horarios** — Motor de clasificación inteligente de horarios (`models/HorarioClassifier.php`, 316 lines) que analiza horarios asignados, calcula resúmenes (horas totales, entrada/salida), detecta tipos de horario y maneja clasificación basada en ciclos. Extiende la lógica de resolución de horario más allá del CRUD básico
