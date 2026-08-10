## ADDED Requirements

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
