# marcaciones

## Purpose

Gestionar la visualización, búsqueda y edición de registros de marcación (entrada/salida) de empleados, con corrección de registros, filtros por fecha/empleado/dispositivo y registro de auditoría de modificaciones.

### Requirement: Vista de marcaciones

El sistema SHALL mostrar en `/marcaciones` una vista con tabla de registros de marcación filtrable por fecha, empleado, área y dispositivo.

#### Scenario: Carga de vista de marcaciones

- **WHEN** se navega a `/marcaciones`
- **THEN** el sistema renderiza la vista con tabla de registros y filtros

#### Scenario: Filtro por rango de fechas

- **WHEN** se selecciona un rango de fechas en los filtros
- **THEN** el sistema muestra solo los registros dentro del rango

### Requirement: Consulta de registros vía API

El sistema SHALL proveer endpoint GET `/api/obtener_registros_marcaciones` para consultar registros con filtros y paginación, retornando JSON.

#### Scenario: Consulta de registros

- **WHEN** se envía GET a `/api/obtener_registros_marcaciones` con filtros
- **THEN** el sistema retorna JSON con registros de marcación, paginación y total

### Requirement: Edición de marcaciones

El sistema SHALL permitir editar registros individuales de marcación (hora_entrada, hora_salida) vía POST a `/api/actualizar_registro_marcaciones`.

#### Scenario: Edición de registro

- **WHEN** se envía POST a `/api/actualizar_registro_marcaciones` con datos actualizados
- **THEN** el sistema valida CSRF, actualiza el registro y retorna success=true

#### Scenario: Registro de auditoría

- **WHEN** se modifica un registro de marcación
- **THEN** el sistema registra el cambio en la bitácora de auditoría con usuario, fecha, valores anterior y nuevo

### Requirement: Validación de ediciones

El sistema SHALL requerir permisos de administrador o RH para editar registros de marcación.

#### Scenario: Edición sin permisos

- **WHEN** un usuario sin permisos intenta editar una marcación
- **THEN** el sistema retorna error 403
