## MODIFIED Requirements

### Requirement: Cálculo de notas malas
El sistema SHALL calcular y mostrar notas malas por quincena (1ª: días 1-15, 2ª: días 16-fin del mes) combinando dos orígenes: los registros de la tabla `notas_malas` y los retardos no justificados (`justificado = 0`) cuya ventana de justificación ya venció (día 15/30), aplicando la fórmula: total = floor(retardos_menores/2) + (retardos_mayores + faltas). Las faltas (31+ min) se convierten a retardo_mayor antes de aplicar la fórmula. El conjunto de incidencias se determina por la fecha del retardo (`retardos.fecha`), no por el campo `notas_malas.periodo`.

#### Scenario: Cálculo quincenal
- **WHEN** se consulta el módulo de notas malas con un mes y una quincena (1ª o 2ª)
- **THEN** el sistema muestra las notas malas del periodo considerando la tabla `notas_malas` y los retardos sin justificar del periodo

#### Scenario: Retardos con justificación vencida
- **WHEN** un retardo sin justificar tiene su ventana de justificación vencida
- **THEN** el sistema lo incluye en el cálculo de notas malas del periodo correspondiente

## ADDED Requirements

### Requirement: Origen unificado en el módulo de notas malas
El sistema SHALL permitir en `/notas-malas` filtrar por mes y quincena (1ª/2ª), y listar por empleado el detalle de notas malas provenientes de la tabla `notas_malas` y de los retardos no justificados, junto con el total de notas del periodo.

#### Scenario: Listado unificado por quincena
- **WHEN** el usuario RH filtra por mes y quincena
- **THEN** el listado agrupa por empleado las notas de ambos orígenes con total de notas y detalle de retardos (fecha, hora, minutos, tipo, justificación)

### Requirement: Selección de incidencias por fecha del retardo
El sistema SHALL relacionar los registros de `notas_malas` con `retardos` a través de `notas_malas.retardo_id` y el rango de fechas de la quincena (`retardos.fecha BETWEEN inicio AND fin`), sin usar `notas_malas.periodo` como criterio de filtro, ya que este campo refleja la fecha de creación del registro y no la del retardo.

#### Scenario: Nota con periodo inconsistente
- **WHEN** un registro de `notas_malas` tiene `periodo` distinto al mes del retardo referenciado
- **THEN** el sistema asocia la nota al periodo del retardo según `retardo_id` y `retardos.fecha`

#### Scenario: Exclusión de notas no-retardo
- **WHEN** una nota de `notas_malas` tiene tipo `inasistencia`, `comision_rechazada`, `falta_retardo_rechazado` u `otro`
- **THEN** el sistema no la incluye en el conteo ni en el detalle de notas malas por retardos

### Requirement: Integridad de datos de notas_malas
El sistema SHALL normalizar los registros existentes de `notas_malas` cuyos `retardo_id` apunten a un retardo real, actualizando `periodo` al primer día del mes de `retardos.fecha`, y SHALL identificar los registros cuyo `retardo_id` no corresponda a un retardo (comisiones rechazadas) para excluirlos del cálculo de notas por retardos.

#### Scenario: Normalización de periodo
- **WHEN** se ejecuta la migración del módulo de oficios
- **THEN** el sistema actualiza `notas_malas.periodo` según la fecha del retardo referenciado

### Requirement: Acción de generación de oficio
El sistema SHALL exponer en el módulo de notas malas las acciones "Generar oficio" (individual) y "Generar todos los oficios" (masivo) que invocan la generación de oficios ATENTA NOTA en Word.

#### Scenario: Generar oficio desde el listado
- **WHEN** el usuario RH hace clic en "Generar oficio" de un empleado
- **THEN** el sistema genera y descarga el oficio .docx del empleado para el periodo seleccionado
