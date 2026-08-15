# Marca de entrega de oficios de notas malas

## Why

Cuando RH genera un oficio "ATENTA NOTA" por notas malas (retardos y faltas no justificadas), no hay forma de saber si ya fue entregado al empleado. Esto genera incertidumbre administrativa: se puede reincidir en la entrega del mismo documento o no existir constancia de que el empleado lo recibió. Se necesita marcar cuándo un oficio ya fue entregado, para trazabilidad del proceso disciplinario.

## What Changes

- Agregar a `documentos_generados` (tipo `oficio_notas_malas`) campos de entrega: `entregado` (booleano), `fecha_entrega` (datetime) y `entregado_por` (id de usuario).
- En la vista del módulo de notas malas (`/notas-malas`), agregar el botón **"Marcar entregado"** en la tarjeta del empleado cuando el oficio esté generado y no entregado.
- Al marcar entregado, registrar fecha/hora y el usuario en sesión; mostrar un badge verde **"Entregado"** con la fecha de entrega.
- Permitir deshacer la entrega (botón cambia a "Deshacer entrega") para corregir errores.
- Nuevo endpoint `POST /notas-malas/oficio/{id}/marcar-entregado` (y su contraparte para deshacer) con validación CSRF y de permisos (superadmin, admin, rh).

## Capabilities

### New Capabilities
- `marca-entrega-oficios`: Seguimiento del estado de entrega de los oficios "ATENTA NOTA" por notas malas, con registro de fecha y usuario que realiza la entrega.

### Modified Capabilities
- `oficios-notas-malas`: Se agrega la capacidad de marcar y visualizar la entrega del oficio al empleado (nuevo estado de entrega sobre el oficio ya generado).
- `notas-malas`: La vista del módulo incorpora el estado de entrega del oficio en la tarjeta del empleado.

## Impact

- **BD**: migración que agrega columnas `entregado`, `fecha_entrega`, `entregado_por` a `documentos_generados`.
- **Backend**: `models/PlantillaDocumento.php` (modelo `DocumentoGenerado`), `controllers/NotasMalasController.php` (nuevos endpoints), `services/OficioNotasMalasService.php` (si se requiere lógica de consulta).
- **Frontend**: vista `index` de `NotasMalasController` (HTML/JS de la tarjeta del empleado).
- **Rutas**: `routes.php` agrega la nueva ruta POST.
- **Sin dependencias nuevas**: se reutilizan PDO, CSRF y Bootstrap/jQuery existentes.
