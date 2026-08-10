# exportacion-pdf-word

## Purpose
Generar documentos PDF y Word para reportes de asistencia, constancias, oficios de sanción y notificaciones, utilizando plantillas con variables reemplazables.


### Requirement: Exportación a PDF
El sistema SHALL generar documentos PDF usando Dompdf para reportes de asistencia, constancias y documentos oficiales.

#### Scenario: Exportación de reporte a PDF
- **WHEN** se solicita exportar reporte a PDF
- **THEN** el sistema genera PDF descargable con los datos del reporte

### Requirement: Exportación a Word
El sistema SHALL generar documentos Word usando PhpWord para oficios, notificaciones y cartas. Incluye el formato oficial "ATENTA NOTA" de notas malas, con membrete SEP/AEFCM, numeración de folio, tablas por inciso (80 inciso a) retardo menor, 80 inciso b) retardo mayor) y variante con suspensión para 5+ notas malas.

#### Scenario: Generación de oficio
- **WHEN** se solicita generar oficio de sanción o notas malas
- **THEN** el sistema genera documento .docx con los datos del empleado, sanción y tablas de incidencias

#### Scenario: Formato ATENTA NOTA
- **WHEN** se genera un oficio de notas malas
- **THEN** el documento .docx replica la estructura oficial: encabezado con membrete, folio, destinatario, fundamento legal, tablas MES/DÍA/HORA por inciso, advertencia y firma del Jefe de RH

#### Scenario: Descarga como documento Word editable
- **WHEN** el usuario descarga un oficio generado desde el sistema
- **THEN** el sistema entrega el archivo .docx original (no una imagen ni PDF), editable en Word para asignar el número de oficio en un paso posterior

### Requirement: Plantillas de documentos
El sistema SHALL administrar plantillas de documentos en plantillas_documentos con variables reemplazables, tipos (oficio, constancia, reporte, notificacion) y estado activo/inactivo.

#### Scenario: Plantilla con variables
- **WHEN** se genera un documento usando plantilla
- **THEN** el sistema reemplaza las variables {{nombre}}, {{fecha}}, etc. con los datos reales

#### Scenario: Administración de plantillas
- **WHEN** se accede a la gestión de plantillas
- **THEN** el sistema permite crear, editar y desactivar plantillas con contenido HTML y variables

### Sub-componentes relacionados
- **plantillas-documentos** — Gestión de plantillas con modelo `PlantillaDocumento` (157 lines), almacenamiento de contenido HTML, tipos parametrizables y variables reemplazables. Es la capa de almacenamiento y administración que alimenta a la generación de PDF/Word
