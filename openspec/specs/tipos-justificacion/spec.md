# tipos-justificacion

## Purpose
Administrar el catálogo de tipos de justificación parametrizables por incidencia, con 17 tipos pre-seed y configuración de requisitos de aprobación y documento.


### Requirement: Catálogo de tipos de justificación
El sistema SHALL administrar un catálogo de tipos de justificación con nombre, descripcion, tipo_incidencia, requiere_aprobacion, requiere_documento, activo.

#### Scenario: Listado de tipos
- **WHEN** se consulta /tipos-justificacion
- **THEN** el sistema retorna el catálogo de tipos activos

### Requirement: Tipos pre-seed
El sistema SHALL incluir 17+ tipos pre-seed: retardo, retardo_menor, retardo_mayor, general, comision, comision_entrada, comision_salida, comision_todo_dia, dia_economico, licencia_medica, vacaciones, cuidados_parentales, constancia_tiempo, PDSEP-SNTE, CLIDDA, EYR, DE, F, falta, permiso_fallecimiento, normal, por_definir.

#### Scenario: Tipos disponibles
- **WHEN** se inicializa la BD
- **THEN** los tipos pre-seed están disponibles en tipos_justificacion

### Requirement: Parametrización por incidencia
El sistema SHALL asociar cada tipo de justificación a un tipo_incidencia específico.

#### Scenario: Filtro por incidencia
- **WHEN** se listan justificaciones por tipo_incidencia
- **THEN** el sistema filtra según el tipo asociado

### Sub-componentes relacionados
- **permiso-fallecimiento** — Tipo de incidencia `permiso_fallecimiento` para ausencias por fallecimiento familiar, gestionado como subtipo dentro del catálogo de `tipos_justificacion`
- **cuidados-parentales** — Tipos `cuidados_parentales`, `cuidados_maternos`, `cuidados_paternos` para licencias parentales, gestionados como subtipos dentro del catálogo de `tipos_justificacion`
