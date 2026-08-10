# rfc-curp-generacion

## Purpose

Generar automáticamente RFC y CURP de empleados según los algoritmos oficiales del SAT y RENAPO, con validación de formato, cálculo de homoclave y verificación de unicidad en la base de datos.

### Requirement: Generación de RFC

El sistema SHALL generar RFC a partir de nombre, apellido paterno, apellido materno y fecha de nacimiento usando el algoritmo oficial del SAT con cálculo de homoclave.

#### Scenario: RFC generado desde formulario

- **WHEN** se ingresan nombre, apellidos y fecha de nacimiento y se solicita generar RFC
- **THEN** el sistema retorna el RFC de 13 caracteres calculado según algoritmo SAT

#### Scenario: RFC con homoclave

- **WHEN** se genera RFC
- **THEN** el sistema calcula e incluye la homoclave de 3 dígitos

### Requirement: Generación de CURP

El sistema SHALL generar CURP a partir de los datos completos del empleado usando el algoritmo oficial RENAPO.

#### Scenario: CURP generado desde formulario

- **WHEN** se ingresan datos completos del empleado y se solicita generar CURP
- **THEN** el sistema retorna la CURP de 18 caracteres calculada

### Requirement: Validación de formato

El sistema SHALL validar que el RFC y CURP generados cumplan con el formato oficial antes de guardar.

#### Scenario: RFC inválido

- **WHEN** el RFC generado no cumple el formato SAT
- **THEN** el sistema muestra error de validación

### Requirement: Verificación de unicidad

El sistema SHALL verificar que el RFC y CURP generados no existan ya en la base de datos (UNIQUE).

#### Scenario: RFC duplicado

- **WHEN** el RFC generado ya existe en la tabla empleados
- **THEN** el sistema muestra error de RFC duplicado

### Requirement: Endpoints específicos

El sistema SHALL exponer rutas `/empleados/generate_rfc` y `/empleados/generate_curp` para generación vía AJAX desde formularios.

#### Scenario: Generación vía AJAX

- **WHEN** se envía GET a `/empleados/generate_rfc` con datos del empleado
- **THEN** el sistema retorna JSON con el RFC calculado
