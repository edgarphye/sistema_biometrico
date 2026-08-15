# Diseño: Marca de entrega de oficios de notas malas

## Context

Los oficios "ATENTA NOTA" se generan en `services/OficioNotasMalasService.php` y se persisten en `documentos_generados` (modelo `DocumentoGenerado` en `models/PlantillaDocumento.php`) con tipo `oficio_notas_malas`. La vista del módulo (`controllers/NotasMalasController.php::index`) consulta los documentos del periodo y muestra en la tarjeta de cada empleado el estado "Oficio generado" con acciones Ver/Descargar/Regenerar, pero no existe concepto de "entregado al empleado".

Se usa un framework MVC propio con PHP 8.4, Bootstrap 5.3, jQuery 3.7 y CSRF token (header `X-CSRF-Token`). Las rutas se definen en `routes.php` con `$r->addRoute(...)`.

## Goals / Non-Goals

**Goals:**
- Registrar la entrega de un oficio al empleado: booleano `entregado`, `fecha_entrega` y `entregado_por`.
- Permitir marcar como entregado y deshacer la entrega desde la tarjeta del empleado en `/notas-malas`.
- Mostrar un badge verde "Entregado" con fecha una vez marcado.
- Mantener las reglas de negocio existentes del oficio (quién genera, folio, incidencias) intactas.

**Non-Goals:**
- No modificar el contenido del .docx ni del modelo del oficio (solo estado de entrega en el registro).
- No implementar acuse de firma electrónica ni captura de firma del empleado.
- No agregar notificaciones ni workflows de aprobación.
- No aplicar la marca a otros tipos de `documentos_generados` (solo `oficio_notas_malas`).

## Decisions

### 1. Persistir en `documentos_generados` (no tabla nueva)

Se agregan columnas `entregado TINYINT(1) NOT NULL DEFAULT 0`, `fecha_entrega DATETIME NULL`, `entregado_por INT NULL` a la tabla existente.

**Por qué**: el estado de entrega es un atributo del documento; no requiere entidad nueva ni histórico adicional. Simple y consistente con la persistencia actual.
**Alternativa descartada**: tabla `entrega_documentos` con FK — permite histórico múltiple pero agrega complejidad innecesaria para una marca binaria con fecha.

### 2. Endpoints dedicados en `NotasMalasController`

- `POST /notas-malas/oficio/{id}/marcar-entregado` — marca entregado con fecha `NOW()` y `entregado_por = $_SESSION['user_id']`.
- `POST /notas-malas/oficio/{id}/desmarcar-entregado` — revierte a `entregado = 0` y limpia `fecha_entrega`/`entregado_por`.

Ambos validan CSRF, roles (`superadmin`, `admin`, `rh`) y que el documento exista y sea de tipo `oficio_notas_malas`. Respuesta JSON `{success, entregado, fecha_entrega}`.

**Por qué**: son mutaciones explícitas del estado de entrega; separar marcar/desmarcar evita ambigüedad de toggle.
**Alternativa descartada**: un solo endpoint con `action=entregar/desmarcar` — funciona pero complica la lectura y el manejo de errores.

### 3. Métodos en `DocumentoGenerado`

- `marcarEntregado($id, $usuario_id)` → `UPDATE documentos_generados SET entregado=1, fecha_entrega=NOW(), entregado_por=? WHERE id=? AND tipo_documento='oficio_notas_malas'`.
- `desmarcarEntregado($id)` → `UPDATE ... SET entregado=0, fecha_entrega=NULL, entregado_por=NULL WHERE id=?`.
- El SELECT del índice ya trae `SELECT *`, por lo que los nuevos campos llegan sin cambios a la vista.

### 4. UI en la tarjeta del empleado

En `index()` de `NotasMalasController`, dentro del bloque `$estadoOficio` cuando existe oficio:
- Si `oficio.entregado == 1`: badge verde `<span class="badge bg-success">Entregado</span>` con fecha formateada + botón "Deshacer entrega" (outline-warning).
- Si `entregado == 0`: botón "Marcar entregado" (outline-success) que llama al endpoint por AJAX con CSRF.

El JS reutiliza el patrón existente de `postGenerar` (deshabilitar botón mientras carga, `$.ajax` con `X-CSRF-Token`, `alert` y `location.reload()` al éxito).

## Risks / Trade-offs

- **Entrega marcada por error** → Se incluye "Deshacer entrega", limpio y reversible, con confirmación `confirm()`.
- **Documento regenerado** (nuevo folio) tras una entrega → Al regenerar se elimina el registro anterior (`DELETE` en `generarOficio`), por lo que el nuevo documento nace sin entregar. Comportamiento aceptable y coherente: el nuevo folio es un documento distinto. Se documenta en la spec.
- **Registros antiguos** → Las nuevas columnas tienen default `0`/`NULL`; los oficios previos aparecerán como "no entregados", lo cual es correcto (sin fecha de entrega conocida).
- **Sin constraints de FK** → `entregado_por` es INT nullable; se registra el `user_id` de sesión sin FK. Suficiente para trazabilidad; se documenta como trade-off.

## Migration Plan

- **Up**: `ALTER TABLE documentos_generados ADD COLUMN entregado TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN fecha_entrega DATETIME NULL, ADD COLUMN entregado_por INT NULL;`
- **Down**: `ALTER TABLE documentos_generados DROP COLUMN entregado, DROP COLUMN fecha_entrega, DROP COLUMN entregado_por;`
- Se ejecuta como script SQL manual o dentro de la migración del proyecto (el repo maneja migraciones en BD).
- **Rollback**: revertir las rutas, métodos del controlador y botones del frontend; la migración es reversible.
