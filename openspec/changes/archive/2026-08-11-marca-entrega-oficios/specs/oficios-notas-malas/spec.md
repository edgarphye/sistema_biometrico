# oficios-notas-malas (delta)

## Purpose
Delta de la capacidad `oficios-notas-malas` para incorporar el estado de entrega del oficio al empleado.

## ADDED Requirements

### Requirement: Registro de entrega del oficio
El sistema SHALL registrar la entrega de un oficio "ATENTA NOTA" al empleado en `documentos_generados` mediante los campos `entregado`, `fecha_entrega` y `entregado_por`, a través del endpoint `POST /notas-malas/oficio/{id}/marcar-entregado`. El documento que se marca debe existir y ser de tipo `oficio_notas_malas`.

#### Scenario: Entrega registrada
- **WHEN** un usuario RH envía la petición para marcar entregado el oficio de un empleado
- **THEN** el sistema guarda la fecha/hora y el usuario y responde con el estado actualizado

#### Scenario: Documento de otro tipo
- **WHEN** se intenta marcar un documento de `documentos_generados` que no es `oficio_notas_malas`
- **THEN** el sistema rechaza la petición con error y no modifica el registro

### Requirement: Deshacer entrega del oficio
El sistema SHALL revertir la entrega de un oficio mediante `POST /notas-malas/oficio/{id}/desmarcar-entregado`, limpiando los datos de entrega del documento.

#### Scenario: Revertir entrega
- **WHEN** un usuario con permisos confirma deshacer la entrega
- **THEN** el sistema limpia `entregado`, `fecha_entrega` y `entregado_por` del documento

### Requirement: Regeneración reinicia el estado de entrega
El sistema SHALL crear el oficio regenerado con `entregado = 0` y sin datos de entrega, dado que el documento anterior se elimina en la regeneración.

#### Scenario: Regenerar oficio entregado
- **WHEN** se regenera un oficio previamente entregado
- **THEN** el nuevo registro de `documentos_generados` nace como no entregado
