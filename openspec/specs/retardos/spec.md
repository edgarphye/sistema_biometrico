# retardos

## Purpose
Registrar y clasificar automáticamente retardos por tipo (menor, mayor, falta), gestionar su justificación, evaluar acumulación quincenal para cálculo de notas malas y control del bono de puntualidad.


### Requirement: Registro de retardo
El sistema SHALL registrar automáticamente retardos al procesar la asistencia cuando la hora de entrada supera la hora programada más la tolerancia. Los retardos existentes SHALL tener horario_id asignado.

#### Scenario: Registro automático de retardo
- **WHEN** un empleado llega después del inicio de jornada + tolerancia
- **THEN** el sistema crea registro en retardos con minutos_retardo calculados, tipo_asistencia, categoria_principal y horario_id

#### Scenario: Migración de retardos existentes
- **WHEN** se ejecuta el script de migración de retardos
- **THEN** el sistema asigna horario_id a retardos sin horario según fecha y horario del empleado

### Requirement: Clasificación por tipo
El sistema SHALL clasificar cada retardo como: retardo_menor (11-20 min), retardo_mayor (21-30 min), falta (31+ min), comision_*, dia_economico, ausencia.

#### Scenario: Clasificación automática
- **WHEN** se registra un retardo de 15 min
- **THEN** el sistema asigna tipo_retraso=retardo_menor

### Requirement: Justificación de retardo
El sistema SHALL permitir justificar retardos con tipo_justificacion_id, motivo, soporte, y aprobación.

#### Scenario: Justificación exitosa
- **WHEN** se envía POST a /justificaciones/justificar con datos válidos
- **THEN** el sistema actualiza retardo con justificado=1 y datos de justificación

#### Scenario: Bloqueo de justificación
- **WHEN** justificacion_bloqueada=1
- **THEN** el sistema no permite nuevas justificaciones para ese retardo

### Requirement: Evaluación de acumulación
El sistema SHALL evaluar acumulación de retardos para determinar notas malas y sanciones según reglas AEFCM.

#### Scenario: Evaluación quincenal
- **WHEN** se ejecuta recalcular_notas_malas_quincena
- **THEN** el sistema calcula notas malas según fórmula: floor(retardos_menores/2) + (retardos_mayores + faltas). El emparejamiento de menores ocurre dentro del periodo evaluado (mes completo o quincena); las faltas (31+ min) se convierten a retardo_mayor antes del cálculo.

### Requirement: Nota mala automática
El sistema SHALL aplicar regla: 2 retardo_menor = 1 nota mala, 1 retardo_mayor o falta = 1 nota mala.

#### Scenario: Dos retardo_menor generan nota mala
- **WHEN** un empleado acumula 2 retardo_menor en un período
- **THEN** el sistema crea 1 nota mala en notas_malas

### Requirement: Límite de puntualidad
El sistema SHALL evaluar premio de puntualidad: máximo 2 retardos por quincena.

#### Scenario: Pérdida de puntualidad
- **WHEN** un empleado excede 2 retardos en una quincena
- **THEN** el sistema marca pérdida del bono de puntualidad
