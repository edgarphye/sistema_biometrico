# empleados

## Purpose
Gestionar el catálogo de empleados con operaciones CRUD, generación automática de RFC y CURP, carga de foto, asignación de jefe directo, búsqueda y filtrado, edición rápida desde el dashboard biométrico y vista de perfil completo.


### Requirement: CRUD de empleados
El sistema SHALL permitir crear, leer, actualizar y desactivar (soft delete) empleados con los campos: id, zkteo_id (varchar 50), nombre, apellido, rfc (UNIQUE), curp (UNIQUE), email, telefono, area, area_fisica, puesto, jerarquia, jefe_directo_id (self-ref FK), sexo, fecha_nacimiento, fecha_ingreso, entidad_federativa, foto_cara, activo, plaza_confianza, clave_depto. Los campos zkteo_id y jefe_directo_id SHALL ser obligatorios para empleados activos. El formato de zkteo_id es EMP-{id}.

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

#### Scenario: 457 empleados con zkteo_id asignado
- **WHEN** se ejecuta fix_biometric_linking.sql
- **THEN** el sistema asigna zkteo_id = EMP-{id} a todos los empleados activos

#### Scenario: Empleado sin jefe directo
- **WHEN** se crea un empleado sin jefe_directo_id
- **THEN** el sistema retorna error indicando jefe_directo_id requerido

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

#### Scenario: Asignación masiva de jefes
- **WHEN** se ejecuta script de migración de jerarquía
- **THEN** el sistema asigna jefes a empleados sin jefe_directo_id

### Requirement: Búsqueda y filtrado
El sistema SHALL permitir buscar empleados por nombre, área, jerarquía, con paginación y exportación.

#### Scenario: Búsqueda por área
- **WHEN** se filtra empleados por área
- **THEN** el sistema retorna solo los empleados de esa área

### Requirement: Edición rápida vía AJAX desde dashboard biométrico
El sistema SHALL permitir editar datos básicos del empleado (nombre, apellido, área, puesto, jerarquía, clave_depto) directamente desde el dashboard biométrico sin navegar a la vista completa de empleados.

#### Scenario: Carga de datos del empleado vía AJAX
- **WHEN** se hace clic en "Editar" en la tabla de empleados del dashboard biométrico
- **THEN** el sistema envía GET a /empleados/{id} con header X-Requested-With: XMLHttpRequest y retorna JSON con datos del empleado

#### Scenario: Edición de datos del empleado desde modal
- **WHEN** se envía POST a /empleados/edit con datos actualizados desde el modal del dashboard biométrico
- **THEN** el sistema valida CSRF, actualiza el registro en empleados y retorna success=true

#### Scenario: Filtrado de empleados con huella en dashboard
- **WHEN** se carga el dashboard /biometricos
- **THEN** el sistema pasa $empleadosConHuella (empleados activos con huella_dactilar no nula) a la vista para mostrar en tabla con búsqueda y filtros por área/jerarquía

### Requirement: Vista de perfil completo
El sistema SHALL mostrar perfil del empleado con datos personales, asistencia, horarios, retardos, comisiones, ausencias.

#### Scenario: Consulta de perfil
- **WHEN** se navega a /empleados/{id}
- **THEN** el sistema muestra todos los datos agregados del empleado

### Requirement: Claves presupuestales del empleado
El sistema SHALL almacenar una o varias claves presupuestales por empleado en la tabla `claves_presupuestales`, cada fila con `empleado_id` (identificador único que corresponde al RFC del empleado) y `clave`. El sistema SHALL permitir agregar, editar y eliminar claves en el formulario de alta/edición del empleado, y SHALL unirlas con ` / ` al mostrarlas en documentos oficiales (oficios de notas malas).

#### Scenario: Alta o edición de claves presupuestales
- **WHEN** un usuario con permisos crea o edita un empleado
- **THEN** el sistema permite capturar, agregar y eliminar una o más claves presupuestales para ese empleado

#### Scenario: Empleado con varias claves
- **WHEN** un empleado tiene más de una clave presupuestal registrada
- **THEN** el sistema las almacena como registros separados y las muestra unidas con ` / ` en los documentos oficiales

#### Scenario: Uso en oficio de notas malas
- **WHEN** se genera un oficio de notas malas para un empleado con claves presupuestales registradas
- **THEN** el oficio muestra la línea de clave presupuestal con todas las claves del empleado separadas por ` / `

### Sub-componentes relacionados
- **rfc-curp-generacion** (`openspec/specs/rfc-curp-generacion/`) — Spec dedicado para la generación de RFC y CURP con algoritmo SAT
- **constancias-tiempo** — Las constancias de tiempo se gestionan como parte del perfil del empleado y justificaciones, usando el modelo `ConstanciaTiempo` (folio, institución, tipo, rango de fechas)
