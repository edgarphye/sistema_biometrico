## ADDED Requirements

### Requirement: CRUD de empleados
El sistema SHALL permitir crear, leer, actualizar y desactivar (soft delete) empleados con los campos: id, zkteo_id (varchar 50), nombre, apellido, rfc (UNIQUE), curp (UNIQUE), email, telefono, area, area_fisica, puesto, jerarquia, jefe_directo_id (self-ref FK), sexo, fecha_nacimiento, fecha_ingreso, entidad_federativa, foto_cara, activo, plaza_confianza, clave_depto.

#### Scenario: Creación de empleado exitosa
- **WHEN** se envía POST a /empleados/crear con todos los campos requeridos
- **THEN** el sistema crea el registro en la tabla empleados y retorna success=true

#### Scenario: RFC duplicado
- **WHEN** se crea un empleado con un RFC ya existente
- **THEN** el sistema retorna error indicando RFC duplicado

#### Scenario: Eliminación lógica
- **WHEN** se elimina un empleado
- **THEN** el sistema marca activo=0 en lugar de borrar físicamente

### Requirement: Generación automática de RFC
El sistema SHALL generar RFC automáticamente a partir de nombre, apellido y fecha de nacimiento usando el helper RfcCurpHelper.

#### Scenario: RFC generado correctamente
- **WHEN** se ingresan nombre, apellido y fecha de nacimiento y se solicita generar RFC
- **THEN** el sistema retorna el RFC calculado según algoritmo SAT

### Requirement: Generación automática de CURP
El sistema SHALL generar CURP automáticamente a partir de datos del empleado.

#### Scenario: CURP generado correctamente
- **WHEN** se ingresan datos completos del empleado y se solicita generar CURP
- **THEN** el sistema retorna la CURP calculada

### Requirement: Carga de foto
El sistema SHALL permitir subir foto de empleado en formato imagen.

#### Scenario: Subida de foto exitosa
- **WHEN** se envía POST a /empleados/subir-foto con archivo imagen
- **THEN** el sistema guarda la ruta en empleados.foto_cara y retorna success=true

### Requirement: Asignación de jefe directo
El sistema SHALL permitir asignar un jefe directo a cada empleado mediante FK autorreferenciada empleados.jefe_directo_id.

#### Scenario: Asignación de jefe
- **WHEN** se actualiza un empleado con jefe_directo_id válido
- **THEN** el sistema guarda la relación y permite consultas jerárquicas

### Requirement: Búsqueda y filtrado
El sistema SHALL permitir buscar empleados por nombre, área, jerarquía, con paginación y exportación.

#### Scenario: Búsqueda por área
- **WHEN** se filtra empleados por área
- **THEN** el sistema retorna solo los empleados de esa área

### Requirement: Vista de perfil completo
El sistema SHALL mostrar perfil del empleado con datos personales, asistencia, horarios, retardos, comisiones, ausencias.

#### Scenario: Consulta de perfil
- **WHEN** se navega a /empleados/{id}
- **THEN** el sistema muestra todos los datos agregados del empleado
