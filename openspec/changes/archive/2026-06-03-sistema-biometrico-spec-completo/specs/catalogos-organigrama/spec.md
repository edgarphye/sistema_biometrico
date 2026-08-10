## ADDED Requirements

### Requirement: Catálogo jerárquico de 3 niveles
El sistema SHALL administrar catálogo organizacional de 3 niveles: direcciones (clave_dir, nombre_direccion), subdirecciones (clave_subdir, clave_direccion FK, nombre_subdir), departamentos (clave_depto, clave_subdireccion FK, nombre_departamento). Cada nivel con activo, created_at, updated_at.

#### Scenario: Creación de dirección
- **WHEN** se envía POST a /catalogos/guardar-direccion con datos válidos
- **THEN** el sistema crea registro en direcciones

#### Scenario: Consulta de subdirecciones por dirección
- **WHEN** se selecciona una dirección
- **THEN** el sistema retorna las subdirecciones asociadas

### Requirement: Catálogo de mandos
El sistema SHALL administrar catálogo de mandos (jefes por área) con clave_area UNIQUE, nombre_mando, area, clave_depto, activo.

#### Scenario: Creación de mando
- **WHEN** se envía POST a /catalogos/guardar-mando
- **THEN** el sistema crea registro en catalogos_mandos

### Requirement: Relaciones jerárquicas
El sistema SHALL permitir relacionar empleados con sus jefes directos a través de empleados.jefe_directo_id y catalogos_mandos.

#### Scenario: Consulta de subordinados
- **WHEN** se consultan empleados a cargo de un jefe
- **THEN** el sistema retorna empleados con jefe_directo_id = id del jefe
