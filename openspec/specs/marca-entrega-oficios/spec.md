# marca-entrega-oficios

## Purpose
Registrar cuándo un oficio "ATENTA NOTA" por notas malas (retardos y faltas no justificadas) ya fue entregado al empleado, guardando fecha y usuario que realizó la entrega, y permitir revertirla.

## Requirements

### Requirement: Marcado de entrega del oficio
El sistema SHALL permitir marcar como entregado un oficio de tipo `oficio_notas_malas` generado en `documentos_generados`, registrando `entregado = 1`, `fecha_entrega` con la fecha/hora actual y `entregado_por` con el id del usuario en sesión. Solo los roles `superadmin`, `admin` y `rh` SHALL poder marcar la entrega.

#### Scenario: Marcar oficio como entregado
- **WHEN** un usuario RH con permisos hace clic en "Marcar entregado" sobre un oficio generado y no entregado
- **THEN** el sistema registra la entrega con fecha/hora actual y usuario, y responde con el nuevo estado

#### Scenario: Marcar un oficio inexistente
- **WHEN** se intenta marcar entregado un documento que no existe o no es de tipo `oficio_notas_malas`
- **THEN** el sistema responde con error 404 y no modifica ningún registro

#### Scenario: Usuario sin permisos
- **WHEN** un usuario sin rol `superadmin`, `admin` o `rh` intenta marcar entregado un oficio
- **THEN** el sistema rechaza la petición con error 403 y no modifica el registro

### Requirement: Visualización del estado de entrega
El sistema SHALL mostrar en la tarjeta del empleado del módulo de notas malas un badge verde "Entregado" con la fecha de entrega cuando el oficio del periodo esté marcado como entregado, y un botón "Marcar entregado" cuando aún no lo esté.

#### Scenario: Oficio entregado
- **WHEN** el oficio del empleado en el periodo tiene `entregado = 1`
- **THEN** la tarjeta muestra el badge "Entregado" con la fecha de entrega y no muestra el botón "Marcar entregado"

#### Scenario: Oficio generado sin entregar
- **WHEN** el oficio del empleado en el periodo existe con `entregado = 0`
- **THEN** la tarjeta muestra el botón "Marcar entregado" junto a las acciones del oficio

### Requirement: Deshacer la entrega
El sistema SHALL permitir revertir el estado de entrega de un oficio, limpiando `fecha_entrega` y `entregado_por`, cuando el usuario lo solicite explícitamente con confirmación.

#### Scenario: Deshacer entrega
- **WHEN** un usuario con permisos confirma "Deshacer entrega" sobre un oficio entregado
- **THEN** el sistema regresa `entregado = 0`, limpia `fecha_entrega` y `entregado_por`, y la tarjeta vuelve a mostrar el botón "Marcar entregado"

### Requirement: Estado de entrega de documentos regenerados
El sistema SHALL iniciar el estado de entrega en "no entregado" para todo oficio recién generado o regenerado, ya que la regeneración reemplaza el documento por uno con nuevo folio.

#### Scenario: Regeneración de un oficio entregado
- **WHEN** un oficio entregado se regenera (nuevo folio) y se elimina el registro anterior
- **THEN** el nuevo documento nace con `entregado = 0` y sin fecha ni usuario de entrega

#### Scenario: Registros previos al cambio
- **WHEN** existen oficios generados antes de esta funcionalidad
- **THEN** el sistema los muestra como "no entregados" (valor por defecto)
