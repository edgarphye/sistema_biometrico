## ADDED Requirements

### Requirement: Cálculo de notas malas
El sistema SHALL calcular notas malas cada quincena según: total = floor(retardos_menores/2) + (retardos_mayores + faltas). Las faltas (31+ min) se convierten a retardo_mayor antes de aplicar la fórmula.

#### Scenario: Cálculo quincenal
- **WHEN** se procesa la quincena
- **THEN** el sistema calcula notas malas para cada empleado según la fórmula

### Requirement: Creación de nota mala
El sistema SHALL crear registro en notas_malas cuando se detecte acumulación que amerite nota.

#### Scenario: Creación automática
- **WHEN** el cálculo genera al menos 1 nota mala
- **THEN** el sistema crea registros en notas_malas con tipo, cantidad, periodo

### Requirement: Evaluación de sanciones por notas malas
El sistema SHALL evaluar si 4+ notas malas requieren oficio y 5+ requieren suspensión.

#### Scenario: Suspensión por 5 notas malas
- **WHEN** un empleado alcanza 5 notas malas
- **THEN** el sistema crea una sanción de suspensión por 1 día

### Requirement: Límite anual de suspensiones
El sistema SHALL limitar a 7 suspensiones por año; al exceder, genera propuesta de terminación.

#### Scenario: Terminación por 7 suspensiones
- **WHEN** un empleado acumula 7 suspensiones en un año
- **THEN** el sistema genera propuesta de terminación
