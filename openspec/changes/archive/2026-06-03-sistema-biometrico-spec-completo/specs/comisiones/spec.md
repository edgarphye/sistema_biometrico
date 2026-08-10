## ADDED Requirements

### Requirement: Registro de comisiones
El sistema SHALL permitir registrar comisiones con empleado_id, descripcion, monto, fecha_inicio, fecha_fin, estatus, tipo_comision, requiere_evidencia.

#### Scenario: Creación de comisión
- **WHEN** se envía POST a /comisiones/crear con datos válidos
- **THEN** el sistema crea registro en comisiones con estatus pendiente

### Requirement: Límites AEFCM
El sistema SHALL validar límite mensual de $3,000 MXN por empleado según reglas AEFCM.

#### Scenario: Excede límite mensual
- **WHEN** una nueva comisión excede el límite de $3,000 en el mes
- **THEN** el sistema rechaza la comisión con error de límite excedido

### Requirement: Tipos de comisión AEFCM
El sistema SHALL soportar tipos: viaticos, gastos_representacion, transporte, hospedaje, alimentacion, comision_entrada, comision_salida, comision_todo_dia, comision_dia, otros.

#### Scenario: Comisión por tipo
- **WHEN** se selecciona tipo comision_entrada
- **THEN** el sistema permite registrar solo la entrada como comisión

### Requirement: Validación de días
El sistema SHALL validar límites de días por tipo de comisión (7 a 30 días según tipo AEFCM).

#### Scenario: Días excedidos
- **WHEN** una comisión excede los días permitidos para su tipo
- **THEN** el sistema rechaza la solicitud

### Requirement: Aprobación de comisiones
El sistema SHALL gestionar aprobación con flujo pendiente -> aprobada/rechazada.

#### Scenario: Aprobación de comisión
- **WHEN** se aprueba una comisión pendiente
- **THEN** el sistema actualiza estatus a aprobada y registra datos de aprobación
