# notas-malas

## Purpose
Calcular notas malas por acumulación de retardos cada quincena según la fórmula AEFCM, evaluar sanciones automáticas por umbrales (suspensión, oficio, terminación) y gestionar límites anuales.

## Requirements

### Requirement: Cálculo de notas malas
El sistema SHALL calcular y mostrar notas malas por periodo (mes completo, 1ª quincena: días 1-15, o 2ª quincena: días 16-fin del mes) a partir de dos fuentes: los retardos NO justificados (`justificado = 0`) del periodo y los retardos justificados que EXCEDEN los 2 permitidos por quincena, identificados por los registros de la tabla `notas_malas` que referencian su `retardo_id`. El conteo SHALL calcularse sobre los retardos directamente, sin sumar la tabla `notas_malas`, aplicando la fórmula: total = floor(menores_sin_justificar/2) + menores_justificados_en_exceso_con_registro + (mayores + faltas). Cada registro de `notas_malas` sobre un menor justificado en exceso ya representa un par completo (2 menores = 1 nota) y SHALL contar como 1 nota mala sin volver a dividirse. El emparejamiento de retardos menores sin justificar SHALL hacerse dentro del periodo seleccionado (el mes completo o la quincena elegida), sin fragmentarse por quincena cuando se consulta el mes completo, de modo que 2 menores sin justificar del mismo periodo sumen 1 nota mala aun en quincenas distintas. Los retardos mayores (21-30 min) cuentan 1 nota cada uno; pasando de los 30 minutos el registro se trata como falta y también cuenta como nota mayor, aunque esté clasificado como menor/mayor en BD. El conjunto de incidencias se determina por la fecha del retardo (`retardos.fecha`), no por el campo `notas_malas.periodo`.

#### Scenario: Cálculo quincenal
- **WHEN** se consulta el módulo de notas malas con un mes y una quincena (1ª o 2ª)
- **THEN** el sistema muestra las notas malas del periodo considerando los retardos sin justificar del periodo y los justificados en exceso con nota registrada

#### Scenario: Menores en quincenas distintas
- **WHEN** un empleado acumula 2 retardos menores sin justificar en el mes, uno en cada quincena
- **THEN** el sistema los empareja como 1 nota mala (el emparejamiento es sobre el periodo completo, no por quincena)

#### Scenario: Retardo mayor por minutos
- **WHEN** un retardo tiene más de 30 minutos de retraso, aunque esté registrado como menor
- **THEN** el sistema lo trata como falta y cuenta como 1 nota mala (nota mayor)

#### Scenario: Menores justificados en exceso
- **WHEN** un empleado justifica más de 2 retardos en la misma quincena y los excedentes son menores con nota registrada en `notas_malas`
- **THEN** cada registro de `notas_malas` sobre un menor en exceso cuenta como 1 nota mala (cada registro representa un par de menores excedentes)

#### Scenario: Retardos con justificación vencida
- **WHEN** un retardo sin justificar tiene su ventana de justificación vencida
- **THEN** el sistema lo incluye en el cálculo de notas malas del periodo correspondiente

### Requirement: Creación de nota mala
El sistema SHALL crear registro en notas_malas cuando se detecte acumulación que amerite nota.

#### Scenario: Creación automática
- **WHEN** el cálculo genera al menos 1 nota mala
- **THEN** el sistema crea registros en notas_malas con tipo, cantidad, periodo

### Requirement: Evaluación de sanciones por notas malas
El sistema SHALL evaluar si 1 o más notas malas (1-4) requieren oficio y 5+ requieren suspensión, según las reglas de `Retardo::calcularNotasMalas` y `Retardo::evaluarYSancionar`.

#### Scenario: Suspensión por 5 notas malas
- **WHEN** un empleado alcanza 5 notas malas
- **THEN** el sistema crea una sanción de suspensión por 1 día

### Requirement: Límite anual de suspensiones
El sistema SHALL limitar a 7 suspensiones por año; al exceder, genera propuesta de terminación.

#### Scenario: Terminación por 7 suspensiones
- **WHEN** un empleado acumula 7 suspensiones en un año
- **THEN** el sistema genera propuesta de terminación

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

### Requirement: Acciones de entrega en la tarjeta del empleado
El sistema SHALL mostrar en la tarjeta de cada empleado del módulo de notas malas las acciones de entrega del oficio según su estado: botón "Marcar entregado" cuando el oficio existe y no está entregado, y badge "Entregado" con fecha más botón "Deshacer entrega" cuando sí está entregado.

#### Scenario: Tarjeta con oficio sin entregar
- **WHEN** el empleado tiene oficio generado con `entregado = 0`
- **THEN** la tarjeta muestra el botón "Marcar entregado"

#### Scenario: Tarjeta con oficio entregado
- **WHEN** el empleado tiene oficio generado con `entregado = 1`
- **THEN** la tarjeta muestra el badge "Entregado" con la fecha de entrega y el botón "Deshacer entrega"

#### Scenario: Sin oficio generado
- **WHEN** el empleado no tiene oficio generado
- **THEN** la tarjeta no muestra acciones de entrega
