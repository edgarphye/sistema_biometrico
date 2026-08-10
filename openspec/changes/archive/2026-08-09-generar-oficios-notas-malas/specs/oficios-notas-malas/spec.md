## ADDED Requirements

### Requirement: Generación de oficio ATENTA NOTA en Word
El sistema SHALL generar oficios "ATENTA NOTA" en formato Word (.docx) usando PhpWord para empleados con notas malas en el periodo seleccionado, replicando el formato oficial del documento `docs/notas malas ABRIL 2026 laura.docx` (membrete SEP/AEFCM, número de oficio, fecha y lugar, destinatario, cuerpo legal, tablas de incidencias, advertencia y firma).

#### Scenario: Generación individual
- **WHEN** un usuario RH selecciona un empleado con notas malas en el periodo y solicita generar oficio
- **THEN** el sistema genera un archivo .docx descargable con el formato ATENTA NOTA completo y registra el documento en `documentos_generados`

#### Scenario: Generación masiva
- **WHEN** un usuario RH solicita generar oficios para todos los empleados con notas malas del periodo
- **THEN** el sistema genera un .docx por empleado y los entrega agrupados en un archivo ZIP

### Requirement: Selección de incidencias para el oficio
El sistema SHALL construir el detalle del oficio a partir de los retardos del periodo (quincena 1ª: días 1-15, 2ª: días 16-fin) que generan notas malas, determinados por `retardos.fecha` en el rango del periodo y por `notas_malas.retardo_id` referenciando un retardo del periodo. Solo se incluyen retardos de tipo `retardo_menor`, `retardo_mayor` y `falta`; los retardos menores se listan en la tabla del inciso a) y los mayores/faltas en el inciso b).

#### Scenario: Retardos del periodo
- **WHEN** se genera el oficio de un empleado para una quincena
- **THEN** el oficio lista todos los retardos sin justificar de la quincena y los justificados que cuentan con nota registrada, en las tablas por inciso

#### Scenario: Nota fuera del periodo
- **WHEN** un registro de `notas_malas` referencia un retardo fuera del rango de la quincena seleccionada
- **THEN** el oficio no lo incluye en ese documento

### Requirement: Numeración secuencial de oficios
El sistema SHALL asignar a cada oficio un folio consecutivo por año con formato configurable (por defecto `DGIFA/CA/RH-###/AÑO`), persistiendo el último folio usado en `config_oficios` para no repetir números.

#### Scenario: Folio consecutivo
- **WHEN** se genera un oficio en un año dado
- **THEN** el sistema usa el siguiente folio de la secuencia del año y lo persiste

### Requirement: Variante de oficio con suspensión
El sistema SHALL generar la variante de oficio con suspensión cuando un empleado acumula 5 o más notas malas en el mes, incluyendo el fundamento del Art. 80 inciso d) y el día programado de suspensión (martes, miércoles o jueves; máximo 1 por semana).

#### Scenario: Suspensión por 5+ notas malas
- **WHEN** un empleado acumula 5 o más notas malas en el mes
- **THEN** el oficio generado incluye el párrafo de suspensión de 1 día con fundamento y día programado

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
El sistema SHALL mostrar el oficio generado dentro de un modal del propio módulo de notas malas para que el usuario pueda verlo y descargarlo sin salir del sistema. La vista previa SHALL renderizarse como HTML autocontenido (imagen del membrete embebida en base64) generado a partir del .docx con LibreOffice (filtro `HTML:EmbedImages`), cacheándose en `uploads/oficios/{YYYY}/.preview/`. La descarga SHALL entregar el archivo Word (.docx) original editable, ya que el número de oficio se asigna en un paso posterior.

#### Scenario: Abrir vista previa del oficio
- **WHEN** el usuario RH hace clic en el botón "Ver" de un empleado con oficio generado
- **THEN** se abre un modal con el oficio renderizado (membrete, folio, destinatario, tablas MES/DÍA/HORA, cuerpo legal, advertencia o suspensión y firma) cargado desde `GET /notas-malas/oficio/{id}/preview`

#### Scenario: Descargar oficio en Word
- **WHEN** el usuario hace clic en "Descargar Word" dentro del modal
- **THEN** el sistema entrega el .docx original como adjunto, editable en Word para asignar el número de oficio posteriormente

#### Scenario: Regeneración de la vista previa
- **WHEN** el archivo .docx del oficio cambia (regeneración del documento)
- **THEN** el sistema regenera la vista previa HTML al detectar que el .docx es más reciente que la versión cacheada
