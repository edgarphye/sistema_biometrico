## ADDED Requirements

### Requirement: Generación de reportes Excel
El sistema SHALL generar reportes en formato Excel (.xlsx) usando PhpSpreadsheet con datos de asistencia, retardos, justificaciones, comisiones y sanciones.

#### Scenario: Exportación de asistencia
- **WHEN** se solicita exportar asistencia a Excel con filtros
- **THEN** el sistema genera XLSX con columnas: ID, Empleado, Área, Fecha, Hora Entrada, Hora Salida, Tipo, Dispositivo, Biometría, Calidad, Minutos Retardo, Justificado

### Requirement: Filtros avanzados
El sistema SHALL permitir filtrar por fechas, empleado, área, tipo de registro, dispositivo antes de exportar.

#### Scenario: Filtro por rango de fechas
- **WHEN** se selecciona rango de fechas en el reporte
- **THEN** el sistema exporta solo registros dentro del rango

### Requirement: Múltiples formatos
El sistema SHALL generar reportes desde el frontend (api/reportes_excel.php) y desde controladores (ReportesController::exportarExcel).

#### Scenario: Exportación desde vista
- **WHEN** se hace clic en "Exportar Excel" en la vista de reportes
- **THEN** el sistema llama al endpoint y descarga el archivo
