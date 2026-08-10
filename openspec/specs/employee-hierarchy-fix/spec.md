# employee-hierarchy-fix

## Purpose
Asignar y validar jerarquías de empleados usando el catálogo de mandos existente, estableciendo cadena de mando completa: Director General → Directores → Subdirectores → Jefes de Departamento → Empleados.

### Requirement: Asignación masiva de jefes directos
El sistema SHALL asignar jefes directos a empleados sin jefe_directo_id usando el catálogo de mandos existente.

#### Scenario: Asignación por clave_depto
- **WHEN** se ejecuta el script de migración de jerarquía v2
- **THEN** el sistema asigna jefe_directo_id a empleados basándose en su clave_depto contra catalogos_mandos

#### Scenario: Cadena de mando completa
- **WHEN** se ejecuta la migración
- **THEN** el sistema establece: Director General (id=81) → Directores (DCPSGR, DPIEP, DFACE, DATE) → Subdirectores → Jefes → Empleados

#### Scenario: Empleados con área codificada o NULL
- **WHEN** un empleado tiene área como código (073301A01E06...) o NULL
- **THEN** el sistema asigna al Director General como jefe directo

#### Scenario: 0 empleados sin jefe
- **WHEN** se completa la migración
- **THEN** todos los 457 empleados activos tienen jefe_directo_id asignado

### Requirement: Validación de jerarquía
El sistema SHALL validar que la jerarquía asignada no tenga ciclos ni jefes inactivos.

#### Scenario: Detección de ciclos
- **WHEN** la migración detecta un ciclo en la jerarquía
- **THEN** el sistema omite esa asignación y registra error

#### Scenario: Jefe inactivo
- **WHEN** un jefe tiene activo=0
- **THEN** el sistema busca jefe alternativo en la misma área
