# oficios-notas-malas

## Purpose
Generar oficios "ATENTA NOTA" en Word (.docx) por acumulación de notas malas, con folio secuencial, variante de suspensión y configuración de membrete/firma, permitiendo visualizar y descargar el oficio dentro del sistema.

## Requirements

### Requirement: Generación de oficio ATENTA NOTA en Word
El sistema SHALL generar oficios "ATENTA NOTA" en formato Word (.docx) usando PhpWord para empleados con notas malas en el periodo seleccionado, replicando el formato oficial del documento `docs/notas malas ABRIL 2026 laura.docx` (membrete SEP/AEFCM, número de oficio, fecha y lugar, destinatario, cuerpo legal, tablas de incidencias, advertencia y firma).

#### Scenario: Generación individual
- **WHEN** un usuario RH selecciona un empleado con notas malas en el periodo y solicita generar oficio
- **THEN** el sistema genera un archivo .docx descargable con el formato ATENTA NOTA completo y registra el documento en `documentos_generados`

#### Scenario: Generación masiva
- **WHEN** un usuario RH solicita generar oficios para todos los empleados con notas malas del periodo
- **THEN** el sistema genera un .docx por empleado y los entrega agrupados en un archivo ZIP

### Requirement: Selección de incidencias para el oficio
El sistema SHALL construir el detalle del oficio a partir de los retardos del periodo (quincena 1ª: días 1-15, 2ª: días 16-fin, o mes completo) que generan notas malas, determinados por `retardos.fecha` en el rango del periodo y por `notas_malas.retardo_id` referenciando un retardo del periodo. Solo se incluyen retardos de tipo `retardo_menor`, `retardo_mayor` y `falta`; los retardos menores se listan en la tabla del inciso a) y los mayores/faltas en el inciso b). Un retardo menor/mayor con más de 30 minutos de retraso SHALL tratarse como falta e ir en el inciso b). Los retardos justificados SHALL incluirse solo cuando cuentan con nota registrada. El total de notas malas del oficio SHALL calcularse con la misma regla del módulo: floor(menores_sin_justificar/2) + menores_justificados_en_exceso_con_registro + (mayores + faltas).

#### Scenario: Retardos del periodo
- **WHEN** se genera el oficio de un empleado para una quincena
- **THEN** el oficio lista todos los retardos sin justificar de la quincena y los justificados que cuentan con nota registrada, en las tablas por inciso

#### Scenario: Nota fuera del periodo
- **WHEN** un registro de `notas_malas` referencia un retardo fuera del rango de la quincena seleccionada
- **THEN** el oficio no lo incluye en ese documento

#### Scenario: Retardo de más de 30 minutos
- **WHEN** un retardo registrado como menor/mayor tiene más de 30 minutos de retraso
- **THEN** el oficio lo lista en la tabla del inciso b) como falta y cuenta como nota mayor

### Requirement: Numeración secuencial de oficios
El sistema SHALL asignar a cada oficio un folio consecutivo por año con formato configurable (por defecto `DGIFA/CA/RH-###/AÑO`), persistiendo el último folio usado en `config_oficios` para no repetir números.

#### Scenario: Folio consecutivo
- **WHEN** se genera un oficio en un año dado
- **THEN** el sistema usa el siguiente folio de la secuencia del año y lo persiste

### Requirement: Variante de oficio con suspensión
El sistema SHALL generar la variante de oficio con suspensión cuando un empleado acumula 5 o más notas malas en el mes, con un día de suspensión por cada 5 notas (floor(notas/5)), incluyendo el fundamento del Art. 80 inciso d) y los días programados de suspensión. Los días SHALL programarse únicamente en martes, miércoles o jueves, con máximo 1 por semana, en semanas distintas y nunca en días consecutivos. El párrafo SHALL usar singular/plural según el número de días programados y escribir las fechas con el mes en minúsculas.

#### Scenario: Suspensión por 5+ notas malas
- **WHEN** un empleado acumula 5 o más notas malas en el mes
- **THEN** el oficio generado incluye el párrafo de suspensión con fundamento (Art. 80 inciso d)) y el/los día(s) programado(s)

#### Scenario: Suspensión de varios días
- **WHEN** un empleado acumula 10 o más notas malas en el mes (2+ días de suspensión)
- **THEN** el oficio programa los días en semanas distintas (martes, miércoles o jueves) y el texto usa plural

### Requirement: Persistencia y prevención de duplicados
El sistema SHALL registrar cada oficio generado en `documentos_generados` (empleado, tipo, periodo, folio y ruta del archivo) y SHALL impedir generar nuevamente el mismo oficio del mismo empleado y periodo a menos que el usuario lo solicite explícitamente (regenerar).

#### Scenario: No duplicar oficio
- **WHEN** ya existe un oficio del mismo empleado y periodo sin regeneración solicitada
- **THEN** el sistema no genera un nuevo documento y notifica que ya existe

### Requirement: Configuración del oficio
El sistema SHALL administrar en la tabla `config_oficios`: prefijo del folio, último folio por año, nombre y cargo del firmante (Jefe de RH), iniciales del documento y ruta del membrete.

#### Scenario: Configuración editable
- **WHEN** un usuario administrador actualiza la configuración de oficios
- **THEN** los siguientes oficios generados usan la nueva configuración

### Requirement: Visualización del oficio en un modal del sistema
El sistema SHALL mostrar el oficio generado dentro de un modal del propio módulo de notas malas para que el usuario pueda verlo y descargarlo sin salir del sistema. La vista previa SHALL renderizarse como HTML autocontenido a partir del MISMO modelo compartido (`construirModelo`) que genera el .docx, garantizando paridad exacta de contenido, con las imágenes del membrete referenciadas por URL del servidor. El modelo SHALL persistirse en `documentos_generados.contenido` (JSON) y reconstruirse en la vista previa desde ese JSON, o recalculándose desde la BD cuando el registro es antiguo. La descarga SHALL entregar el archivo Word (.docx) original editable, ya que el número de oficio se asigna en un paso posterior.

#### Scenario: Abrir vista previa del oficio
- **WHEN** el usuario RH hace clic en el botón "Ver" de un empleado con oficio generado
- **THEN** se abre un modal con el oficio renderizado (membrete, folio, destinatario, tablas MES/DÍA/HORA, cuerpo legal, advertencia o suspensión y firma) cargado desde `GET /notas-malas/oficio/{id}/preview`

#### Scenario: Descargar oficio en Word
- **WHEN** el usuario hace clic en "Descargar Word" dentro del modal
- **THEN** el sistema entrega el .docx original como adjunto, editable en Word para asignar el número de oficio posteriormente

#### Scenario: Paridad entre vista previa y Word
- **WHEN** se genera un oficio
- **THEN** la vista previa HTML y el .docx comparten el mismo modelo, por lo que el contenido mostrado en el modal es idéntico al documento descargable

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

**Sub-componentes relacionados:**
- **notas-malas** (`openspec/specs/notas-malas/`) — Cálculo de notas malas por quincena y origen unificado que alimenta la generación de oficios
- **exportacion-pdf-word** (`openspec/specs/exportacion-pdf-word/`) — Generación de documentos Word con PhpWord y el formato ATENTA NOTA
