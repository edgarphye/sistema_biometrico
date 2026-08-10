## Context

El módulo `/notas-malas` hoy calcula incidencias en vivo desde `retardos` (sin leer la tabla `notas_malas`) y no genera documentos. Existe `GeneradorDocumentosService::generarOficioNotasMalas` con un formato genérico basado en plantillas de BD que no corresponde al oficio oficial "ATENTA NOTA" (documento de referencia: `docs/notas malas ABRIL 2026 laura.docx`, 18 oficios DGIFA/CA/RH-XXX/2026). RH produce estos oficios manualmente y necesita automatizarlos en Word.

Stack: PHP 8.4 (MVC custom), MariaDB, Bootstrap 5.3, jQuery, PhpWord (`phpoffice/phpword` ya instalado). El layout requiere la variable `$content`. El módulo ya existe en el menú con permiso `usuarios`.

## Goals / Non-Goals

**Goals:**
- Generar oficios "ATENTA NOTA" en .docx (PhpWord) con el formato oficial (membrete, folio, destinatario, cuerpo legal, tablas por inciso a/b, advertencia, firma).
- Fuente unificada: tabla `notas_malas` + retardos sin justificar por quincena (1ª: 1-15, 2ª: 16-fin).
- Variante con suspensión para 5+ notas malas.
- Numeración secuencial por año, persistencia en `documentos_generados` y prevención de duplicados.
- Configuración editable de folio/firma/membrete en `config_oficios`.
- Campo `clave_presupuestal` en `empleados` y su edición en UI.

**Non-Goals:**
- Generar PDF (fuera de alcance; el usuario eligió Word). El módulo actual de PDF (`dompdf`) no se toca.
- Notificación automática al empleado por correo al generarse el oficio.
- Firmas electrónicas/escaneadas.
- Regeneración de oficios históricos del docx de referencia (los datos actuales de BD difieren; se generan a partir de la BD actual).

## Decisions

### 1. Nuevo servicio `services/OficioNotasMalasService.php`
Clase dedicada que concentra: consulta unificada de incidencias, cálculo de notas, generación del .docx, foliado y persistencia. Se evita inflar `NotasMalasController` y se reutiliza desde rutas API y masivas.
- `getIncidenciasPeriodo($mes, $anio, $quincena)`: devuelve por empleado el conjunto unificado de incidencias del periodo con total de notas (fórmula `floor(menores/2) + mayores + faltas`).

El conjunto unificado de incidencias se calcula con una sola consulta sobre `retardos`:

```sql
SELECT DISTINCT r.*
FROM retardos r
WHERE r.empleado_id = ?
  AND r.fecha BETWEEN :inicio AND :fin
  AND r.tipo_retraso IN ('retardo_menor','retardo_mayor','falta')
  AND ( r.justificado = 0
     OR EXISTS (SELECT 1 FROM notas_malas nm
                WHERE nm.retardo_id = r.id
                  AND nm.tipo IN ('retardo_menor','retardo_mayor')) )
```

Esto cubre ambos orígenes: (a) retardos sin justificar (generan nota por fórmula) y (b) retardos justificados que sí generaron nota por exceder el límite de 2 por quincena o por rechazo (presentes en `notas_malas`). Los retardos menores van al inciso a) y los mayores/faltas al inciso b). Las notas `inasistencia`/`comision_rechazada`/`otro` quedan fuera del oficio por retardo.
- `generarOficio($empleado_id, $mes, $anio, $quincena, $regenerar = false)`: construye y guarda el .docx, folia y persiste.
- `generarMasivo($mes, $anio, $quincena)`: genera un .docx por empleado y devuelve ZIP.

**Alternativa considerada:** extender `GeneradorDocumentosService`. Se descartó por cohesión: el servicio actual se acopla a plantillas de BD; el formato ATENTA NOTA es fijo y estructurado, no plantillable.

### 2. Generación .docx con PhpWord y membrete en el header
- Se crea `Section` y se agrega `addHeader()` con la imagen del membrete (extraída del docx original a `assets/images/membrete_sep.png`) dimensionada ~209pt x 43pt centrada, igual que el original.
- Cuerpo del documento replicando el orden del docx de referencia: línea "ATENTA NOTA {PREFIJO}-{FOLIO}/{AÑO}", fecha, destinatario (nombre + ID, filiación RFC, clave presupuestal, área), "Presente.", párrafo legal, tablas `MES|DÍA|HORA` por inciso (a/b, lado a lado si ambos), párrafo de advertencia, cierre, "ATENTAMENTE", firma del Jefe de RH e iniciales.
- Contador de notas con formato 2 dígitos (`01`, `02`, ...) e incisos citados según existan menores y/o mayores.

**Alternativa considerada:** reutilizar `plantillas_documentos`. Se descartó: el oficio requiere tablas dinámicas, membrete y folio; manejarlo con reemplazo de variables sería frágil.

### 3. Numeración con `config_oficios`
Nueva tabla clave-valor: `id, prefijo (DGIFA/CA/RH), anio, ultimo_folio, nombre_firmante, cargo_firmante, iniciales (DGNQ*), membrete_path, updated_at`. El folio se reserva con `UPDATE config_oficios SET ultimo_folio = ultimo_folio + 1 WHERE anio = ?` (incremento atómico) y se formatea `{prefijo}-{folio}/{anio}`.

### 4. Datos del oficio
- **Claves presupuestales uno-a-muchos**: nueva tabla `claves_presupuestales (id, empleado_id, clave, created_at)` con FK a `empleados.id` (identificador único del RFC). El formulario de empleado permite agregar/eliminar filas; el oficio une todas las claves con ` / ` (ej. `071152E0220000000371 / 071352E0362190091718`). Si no hay claves, se omite la línea.
- Firma: `config_oficios.nombre_firmante` / `cargo_firmante` (defaults: "JUAN JOSÉ OROZCO PONCE" / "JEFE DEL DEPARTAMENTO DE RECURSOS HUMANOS").
- Día de suspensión programado (variante 5+): siguiente martes/miércoles/jueves a la fecha de generación, con máximo 1 suspensión por semana.

### 5. Persistencia y rutas
- Archivos en `uploads/oficios/{YYYY}/{folio}_{empleado_id}_{periodo}.docx`; se sirven con la ruta existente `GET /uploads/{filepath}` → `SoporteController::serveUpload`.
- Se inserta en `documentos_generados` (tipo `oficio_notas_malas`, `periodo`, `archivo_path`).
- Nuevas rutas en `routes.php`:
  - `POST /notas-malas/generar-oficio` (individual, JSON)
  - `POST /notas-malas/generar-todos` (masivo, JSON con URL del ZIP)
  - `GET /notas-malas/oficio/{id}` (descarga/visualización de un oficio guardado)

### 6. UI en `/notas-malas`
- Filtros existentes + selector de quincena (Todas / 1ª / 2ª) y año/mes.
- Tarjetas por empleado con botón "Generar oficio" y botón global "Generar todos los oficios (ZIP)".
- Las acciones se implementan con jQuery `$.ajax`/`window.location` y los mensajes de éxito/error siguen el patrón de las otras vistas (toast/alert).

## Risks / Trade-offs

- **`notas_malas.retardo_id` está sobrecargado** → en comisiones rechazadas guarda el ID de la comisión, no el del retardo; además `periodo` refleja fecha de creación. → Mitigación: el servicio siempre filtra por `retardos.fecha` y tipo (join por `retardo_id` a `retardos` con `INNER JOIN`), de modo que registros no-retardo no entran al oficio; la migración normaliza `periodo` de los registros válidos.
- **Foliado concurrente** → el `UPDATE ... SET ultimo_folio = ultimo_folio + 1` es atómico en MariaDB; en caso de fallo del documento el folio se consume (aceptable, igual que el proceso manual actual).
- **Datos históricos distintos al docx** → los oficios se generan con la BD actual; si se requieren los documentos exactos de Abril 2026 habrá que regenerarlos a partir de los retardos existentes.
- **Sin claves presupuestales registradas** → se omite la línea en el oficio; se incentiva captura de una o varias en el formulario de empleado.
- **Tamaño del membrete** → se reutiliza la imagen original extraída; si el formato cambia, se actualiza `config_oficios.membrete_path`.
- **Dependencia de PhpWord** → ya instalado; validar que la versión soporte `addHeader()` con imagen (sí, `phpoffice/phpword ^1.4`).

## Migration Plan

1. Ejecutar migración SQL: `CREATE TABLE claves_presupuestales (id INT AUTO_INCREMENT PRIMARY KEY, empleado_id INT NOT NULL, clave VARCHAR(100) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE)` + `CREATE TABLE config_oficios (...)`. Se versiona como `migrations/YYYYMMDD_agregar_oficios_notas_malas.sql`.
2. Normalizar `notas_malas.periodo` existente: `UPDATE notas_malas nm JOIN retardos r ON nm.retardo_id = r.id SET nm.periodo = DATE_FORMAT(r.fecha, '%Y-%m-01')` (solo registros cuyo `retardo_id` existe en `retardos`).
3. Insertar fila inicial en `config_oficios` con defaults (prefijo, folio 0, firmante, iniciales, membrete).
4. Extraer `word/media/image1.png` → `assets/images/membrete_sep.png`.
5. Desplegar servicio, controlador, rutas y vistas.
6. Rollback: quitar rutas/menú; `DROP TABLE config_oficios`; `DROP TABLE claves_presupuestales` (solo si no hay datos que conservar).

## Open Questions

- ¿La numeración debe reiniciarse cada año natural o usar un prefijo por mes? (Se asume año natural, consistente con `-XXX/2026`.)
- ¿Se requiere que el oficio con suspensión genere además el registro en `sanciones`? (Se asume que la sanción ya es creada por la lógica existente de `evaluarYSancionar`; el oficio solo la documenta.)
