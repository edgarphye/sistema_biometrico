<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

$phpWord = new PhpWord();
$phpWord->setDefaultFontName('Calibri');
$phpWord->setDefaultFontSize(11);

$phpWord->addTitleStyle(1, ['bold' => true, 'size' => 22], ['alignment' => Jc::CENTER, 'spaceAfter' => 260]);
$phpWord->addTitleStyle(2, ['bold' => true, 'size' => 15], ['spaceBefore' => 220, 'spaceAfter' => 140]);
$phpWord->addTitleStyle(3, ['bold' => true, 'size' => 12], ['spaceBefore' => 140, 'spaceAfter' => 100]);

$section = $phpWord->addSection([
    'marginTop' => 1200,
    'marginBottom' => 1200,
    'marginLeft' => 1200,
    'marginRight' => 1200,
]);

$section->addText('Documento Técnico Profesional', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);
$section->addTitle('Proceso de Generación y Desarrollo del Software', 1);
$section->addText('Basado en Arquitectura de Software y SDLC', ['size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
$section->addText('Proyecto: Sistema Biométrico de Asistencia', ['size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
$section->addText('Fecha: 05 de marzo de 2026', ['italic' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 260]);

$section->addTitle('1. Propósito', 2);
$section->addText('Definir un proceso formal de generación y desarrollo del sistema, alineado con principios de arquitectura de software, calidad técnica, trazabilidad funcional y operación productiva.');

$section->addTitle('2. Principios de Arquitectura', 2);
$section->addListItem('Separación de responsabilidades: presentación, aplicación, dominio y persistencia.', 0);
$section->addListItem('Evolutividad: diseño orientado a cambios de reglas de negocio (incidencias, validaciones, sanciones).', 0);
$section->addListItem('Observabilidad: logs funcionales/técnicos y trazabilidad por evento.', 0);
$section->addListItem('Seguridad por diseño: control de acceso por rol, validación de entrada y protección CSRF.', 0);
$section->addListItem('Integridad de datos: transacciones y consistencia entre asistencia, incidencias y validaciones.', 0);

$section->addTitle('3. Arquitectura Objetivo (Lógica)', 2);
$section->addListItem('Capa de Presentación: vistas PHP + JS para operación diaria.', 0);
$section->addListItem('Capa de Aplicación: controladores con orquestación de casos de uso.', 0);
$section->addListItem('Capa de Dominio: reglas de negocio en modelos y servicios.', 0);
$section->addListItem('Capa de Persistencia: acceso a BD y migraciones versionadas.', 0);
$section->addListItem('Capa de Integración: importación biométrica y procesamiento de archivos DAT.', 0);

$section->addTitle('4. SDLC Propuesto (Proceso de Desarrollo)', 2);

$section->addTitle('4.1 Planeación y Análisis', 3);
$section->addListItem('Levantamiento de requerimientos por rol (admin, superadmin, jefe, usuario).', 0);
$section->addListItem('Definición de historias de usuario y criterios de aceptación.', 0);
$section->addListItem('Mapa de riesgos técnicos, operativos y de cumplimiento.', 0);

$section->addTitle('4.2 Diseño Arquitectónico y Técnico', 3);
$section->addListItem('Modelado de entidades y relaciones clave (asistencia, retardos, incidencias, validaciones).', 0);
$section->addListItem('Diseño de contratos entre frontend, controladores y modelos.', 0);
$section->addListItem('Definición de catálogos y mapeos explícitos en BD (sin heurísticas por texto).', 0);

$section->addTitle('4.3 Construcción', 3);
$section->addListItem('Desarrollo por módulos funcionales con ramas controladas.', 0);
$section->addListItem('Convenciones de código, validaciones de seguridad y manejo de errores uniforme.', 0);
$section->addListItem('Migraciones incrementales para cambios de esquema.', 0);

$section->addTitle('4.4 Verificación y Pruebas', 3);
$section->addListItem('Pruebas unitarias de reglas críticas (clasificación, validación, sanciones).', 0);
$section->addListItem('Pruebas funcionales de extremo a extremo (captura -> clasificación -> justificación -> validación jefe).', 0);
$section->addListItem('Pruebas de regresión sobre asistencia, retardos e incidencias.', 0);
$section->addListItem('Pruebas de roles y permisos (acceso visual + backend).', 0);

$section->addTitle('4.5 Despliegue y Operación', 3);
$section->addListItem('Promoción controlada a UAT y producción.', 0);
$section->addListItem('Respaldo previo a cada despliegue y plan de rollback.', 0);
$section->addListItem('Monitoreo post-liberación con métricas de salud y errores.', 0);

$section->addTitle('5. Proceso de Generación de Entregables', 2);
$section->addListItem('Entregable 1: Documento funcional y criterios de aceptación.', 0);
$section->addListItem('Entregable 2: Diseño técnico (arquitectura, datos, endpoints, roles).', 0);
$section->addListItem('Entregable 3: Código fuente + migraciones + scripts de soporte.', 0);
$section->addListItem('Entregable 4: Evidencia de pruebas y checklist UAT.', 0);
$section->addListItem('Entregable 5: Manual operativo y plan de mantenimiento.', 0);

$section->addTitle('6. Gobierno del Desarrollo', 2);
$section->addListItem('Control de cambios con versionado semántico.', 0);
$section->addListItem('Revisión técnica obligatoria para cambios de reglas de negocio.', 0);
$section->addListItem('Trazabilidad requisito -> implementación -> prueba -> despliegue.', 0);
$section->addListItem('Bitácora de decisiones arquitectónicas (ADR).', 0);

$section->addTitle('7. Calidad de Software (Gate de Aprobación)', 2);
$section->addText('Cada liberación debe cumplir:');
$section->addListItem('Integridad funcional validada contra criterios de aceptación.', 0);
$section->addListItem('Sin errores críticos en logs ni fallos de esquema.', 0);
$section->addListItem('Permisos por rol comprobados en frontend y backend.', 0);
$section->addListItem('Documentación técnica y operativa actualizada.', 0);

$section->addTitle('8. Roadmap de Implementación (Sugerido)', 2);
$section->addText('Semana 1-2: Análisis detallado, catálogo de reglas, diseño arquitectónico.');
$section->addText('Semana 3-5: Desarrollo modular y migraciones controladas.');
$section->addText('Semana 6: QA integral, UAT y correcciones.');
$section->addText('Semana 7: Despliegue productivo, estabilización y transferencia operativa.');

$section->addTitle('9. Conclusión Técnica', 2);
$section->addText('La sostenibilidad del sistema depende de un proceso disciplinado de desarrollo: arquitectura explícita, reglas versionadas, pruebas por rol y despliegues controlados.');
$section->addText('La adopción del proceso propuesto reduce errores operativos, facilita auditoría y mejora la capacidad de evolución del software en escenarios institucionales.');

$section->addTextBreak(2);
$section->addText('Documento elaborado para dirección técnica, operación y toma de decisiones de evolución del sistema.', ['italic' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

$outFile = __DIR__ . '/../docs/Proceso_Generacion_y_Desarrollo_Arquitectura_Software.docx';
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($outFile);

echo $outFile . PHP_EOL;
