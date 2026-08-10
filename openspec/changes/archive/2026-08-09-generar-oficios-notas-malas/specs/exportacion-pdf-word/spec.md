## MODIFIED Requirements

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
