## Why

RH genera manualmente los oficios "ATENTA NOTA" por notas malas (ver `docs/notas malas ABRIL 2026 laura.docx`, 18 oficios DGIFA/CA/RH-XXX/2026). Hoy el módulo `/notas-malas` solo calcula incidencias en vivo desde `retardos` y no produce estos documentos, y el generador existente (`GeneradorDocumentosService`) produce un formato genérico que no corresponde al oficial. Se necesita que el sistema genere automáticamente estos oficios en Word (.docx) a partir de la tabla `notas_malas` y de los retardos que no fueron justificados en su momento (1ª o 2ª quincena del mes).

## What Changes

- **Nuevo módulo de generación de oficios "ATENTA NOTA"** en `/notas-malas`: listado unificado por mes + quincena (1ª/2ª) que combina la tabla `notas_malas` con los retardos sin justificar (`retardos.justificado = 0`), con botones "Generar oficio" por empleado y "Generar todos".
- **Asociación correcta de notas_malas → retardos**: el detalle se determina por la fecha del retardo (`retardos.fecha` dentro del rango de quincena) y por `notas_malas.retardo_id`; nunca por `notas_malas.periodo` (que hoy guarda la fecha de creación). Se excluyen las notas de tipo `inasistencia`, `comision_rechazada`, `falta_retardo_rechazado` y `otro` del oficio por retardo.
- **Normalización de datos existentes**: migración que corrige `notas_malas.periodo` según la fecha del retardo referenciado.
- **Generación en Word (.docx) con PhpWord** replicando el formato del documento original: membrete SEP/AEFCM, número de oficio, destinatario, fundamento legal, tablas por inciso (a = retardo menor, b = retardo mayor) con MES/DÍA/HORA, advertencia de sanciones y firma.
- **Dos variantes de cuerpo**: oficio simple (1-4 notas) y oficio con suspensión (5+ notas) con fundamento del Art. 80 inciso d) y día de suspensión programado.
- **Numeración secuencial de oficios** por año con prefijo configurable (ej. `DGIFA/CA/RH-XXX/2026`).
- **Nueva tabla `config_oficios`** para: prefijo de numeración, último folio por año, nombre/cargo del firmante (Jefe de RH), iniciales y miembro de imagen del membrete.
- **Nuevo catálogo `claves_presupuestales`**: un empleado puede tener una o varias claves presupuestales, relacionadas por `empleado_id` (que identifica al RFC). El oficio las muestra unidas con ` / ` como en el documento de referencia.
- **Registro en `documentos_generados`** al generar cada oficio, con el archivo .docx generado.
- Persistencia del estado de generación para evitar duplicados (no regenerar el mismo oficio del mismo empleado/periodo a menos que se indique).

## Capabilities

### New Capabilities
- `oficios-notas-malas`: Generación de oficios "ATENTA NOTA" en Word (.docx) por notas malas, incluyendo numeración secuencial, membrete, variante con suspensión y persistencia en `documentos_generados`.

### Modified Capabilities
- `notas-malas`: Se modifica el requerimiento de creación/evaluación para que el módulo considere tanto la tabla `notas_malas` como los retardos no justificados de la quincena seleccionada, y exponga la generación del oficio.
- `exportacion-pdf-word`: Se modifica el requerimiento de exportación a Word para incluir el formato oficial "ATENTA NOTA" de notas malas como tipo de documento.
- `empleados`: Se agrega el catálogo de claves presupuestales del empleado (uno-a-muchos).

## Impact

- **Base de datos**: nueva tabla `claves_presupuestales` (empleado_id, clave), nueva tabla `config_oficios`, `UPDATE` de normalización de `notas_malas.periodo` (join a `retardos` por `retardo_id`).
- **Código afectado**:
  - `controllers/NotasMalasController.php` — nuevo flujo de consulta por quincena y acciones de generación.
  - `services/GeneradorDocumentosService.php` — nueva lógica de oficio ATENTA NOTA en Word.
  - `models/PlantillaDocumento.php` / `DocumentoGenerado` — guardado de archivos .docx.
  - `models/Empleado.php` — CRUD del campo `clave_presupuestal`.
  - `views/empleados/edit.php`, `views/empleados/index.php` — campo nuevo en UI.
  - `views/layout.php` — sin cambios mayores (el módulo ya está en el menú).
  - `routes.php` — nuevas rutas de generación/descarga.
- **Dependencias**: PhpWord (ya instalado, `phpoffice/phpword`).
- **Archivos**: nuevo servicio `services/OficioNotasMalasService.php`; plantilla de membrete extraída de `docs/notas malas ABRIL 2026 laura.docx` a `assets/images/membrete_sep.png`.
