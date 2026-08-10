## ADDED Requirements

### Requirement: Exportación a PDF
El sistema SHALL generar documentos PDF usando Dompdf para reportes de asistencia, constancias y documentos oficiales.

#### Scenario: Exportación de reporte a PDF
- **WHEN** se solicita exportar reporte a PDF
- **THEN** el sistema genera PDF descargable con los datos del reporte

### Requirement: Exportación a Word
El sistema SHALL generar documentos Word usando PhpWord para oficios, notificaciones y cartas.

#### Scenario: Generación de oficio
- **WHEN** se solicita generar oficio de sanción
- **THEN** el sistema genera documento .docx con los datos del empleado y sanción

### Requirement: Plantillas de documentos
El sistema SHALL administrar plantillas de documentos en plantillas_documentos con variables reemplazables.

#### Scenario: Plantilla con variables
- **WHEN** se genera un documento usando plantilla
- **THEN** el sistema reemplaza las variables {{nombre}}, {{fecha}}, etc. con los datos reales
