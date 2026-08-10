## MODIFIED Requirements

### Requirement: CRUD de empleados
El sistema SHALL permitir crear, leer, actualizar y desactivar (soft delete) empleados con los campos: id, zkteo_id (varchar 50), nombre, apellido, rfc (UNIQUE), curp (UNIQUE), email, telefono, area, area_fisica, puesto, jerarquia, jefe_directo_id (self-ref FK), sexo, fecha_nacimiento, fecha_ingreso, entidad_federativa, foto_cara, activo, plaza_confianza, clave_depto. Los campos zkteo_id y jefe_directo_id SHALL ser obligatorios para empleados activos.

#### Scenario: Creación de empleado exitosa
- **WHEN** se envía POST a /empleados/crear con todos los campos requeridos
- **THEN** el sistema crea el registro en la tabla empleados y retorna success=true

#### Scenario: RFC duplicado
- **WHEN** se crea un empleado con un RFC ya existente
- **THEN** el sistema retorna error indicando RFC duplicado

#### Scenario: Eliminación lógica
- **WHEN** se elimina un empleado
- **THEN** el sistema marca activo=0 en lugar de borrar físicamente

#### Scenario: Empleado sin zkteo_id
- **WHEN** se crea un empleado sin zkteo_id
- **THEN** el sistema retorna error indicando zkteo_id requerido

#### Scenario: Empleado sin jefe directo
- **WHEN** se crea un empleado sin jefe_directo_id
- **THEN** el sistema retorna error indicando jefe_directo_id requerido

### Requirement: Asignación de jefe directo
El sistema SHALL permitir asignar un jefe directo a cada empleado mediante FK autorreferenciada empleados.jefe_directo_id.

#### Scenario: Asignación de jefe
- **WHEN** se actualiza un empleado con jefe_directo_id válido
- **THEN** el sistema guarda la relación y permite consultas jerárquicas

#### Scenario: Asignación masiva de jefes
- **WHEN** se ejecuta script de migración de jerarquía
- **THEN** el sistema asigna jefes a empleados sin jefe_directo_id
