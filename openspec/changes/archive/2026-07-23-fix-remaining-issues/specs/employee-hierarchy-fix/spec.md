## ADDED Requirements

### Requirement: Asignación masiva de jefes directos
El sistema SHALL asignar jefes directos a empleados sin jefe_directo_id usando el catálogo de mandos existente.

#### Scenario: Asignación por área
- **WHEN** se ejecuta el script de migración de jerarquía
- **THEN** el sistema asigna jefe_directo_id a empleados según su área y catalogos_mandos

#### Scenario: Empleados sin área asignada
- **WHEN** un empleado no tiene área definida
- **THEN** el sistema registra en bitácora que requiere revisión manual

### Requirement: Validación de jerarquía
El sistema SHALL validar que la jerarquía asignada no ciclos ni jefes inactivos.

#### Scenario: Detección de ciclos
- **WHEN** la migración detecta un ciclo en la jerarquía
- **THEN** el sistema omite esa asignación y registra error

#### Scenario: Jefe inactivo
- **WHEN** un jefe tiene activo=0
- **THEN** el sistema busca jefe alternativo en la misma área
