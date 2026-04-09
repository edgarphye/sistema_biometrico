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

$section = $phpWord->addSection(['marginTop'=>1200,'marginBottom'=>1200,'marginLeft'=>1200,'marginRight'=>1200]);

$section->addText('Propuesta Integral Profesional', ['bold'=>true,'size'=>16], ['alignment'=>Jc::CENTER,'spaceAfter'=>120]);
$section->addTitle('Conversión a Ejecutable (.exe), Arquitectura y Proceso de Desarrollo', 1);
$section->addText('Proyecto: Sistema Biométrico de Asistencia', ['size'=>12], ['alignment'=>Jc::CENTER, 'spaceAfter'=>30]);
$section->addText('Fecha: 05 de marzo de 2026', ['italic'=>true,'size'=>11], ['alignment'=>Jc::CENTER, 'spaceAfter'=>260]);

$section->addTitle('1. Resumen Ejecutivo', 2);
$section->addText('Sí es viable operar el sistema como aplicación instalable en Windows (.exe) sin depender de instalación manual de Nginx/PHP.');
$section->addText('La estrategia recomendada es empaquetado con runtime embebido y proceso formal de desarrollo basado en arquitectura y SDLC.');

$section->addTitle('2. Recomendación Técnica Principal', 2);
$section->addListItem('No compilar PHP “puro” a EXE como estrategia principal.', 0);
$section->addListItem('Sí empaquetar aplicación + runtime (servidor/PHP/dependencias) en instalador corporativo.', 0);
$section->addListItem('Mantener trazabilidad de reglas de negocio en BD y backend.', 0);

$section->addTitle('3. Arquitectura Objetivo', 2);
$section->addListItem('Presentación: interfaz web/desktop embebida.', 0);
$section->addListItem('Aplicación: controladores y casos de uso.', 0);
$section->addListItem('Dominio: reglas de asistencia, incidencias, validación de jefes.', 0);
$section->addListItem('Persistencia: modelos, migraciones y catálogos explícitos.', 0);
$section->addListItem('Operación: logs, respaldos, recuperación y actualización.', 0);

$section->addTitle('4. Proceso de Generación y Desarrollo (SDLC)', 2);
$section->addTitle('4.1 Análisis', 3);
$section->addListItem('Requerimientos por rol y criterios de aceptación.', 0);
$section->addListItem('Mapa de riesgos técnicos/operativos.', 0);

$section->addTitle('4.2 Diseño', 3);
$section->addListItem('Diseño de arquitectura y contratos entre capas.', 0);
$section->addListItem('Diseño de catálogo de justificaciones por tipo_incidencia.', 0);

$section->addTitle('4.3 Construcción', 3);
$section->addListItem('Implementación incremental con migraciones versionadas.', 0);
$section->addListItem('Control de acceso por rol en UI y backend.', 0);

$section->addTitle('4.4 Pruebas', 3);
$section->addListItem('Unitarias de reglas críticas.', 0);
$section->addListItem('Funcionales E2E del flujo completo.', 0);
$section->addListItem('Regresión y pruebas de permisos.', 0);

$section->addTitle('4.5 Despliegue y Operación', 3);
$section->addListItem('Despliegue por fases con respaldo y rollback.', 0);
$section->addListItem('Monitoreo post-liberación y hardening.', 0);

$section->addTitle('5. Plan de Implementación para .exe', 2);
$section->addText('Fase 1 (1-2 semanas): preparación de configuración y dependencias.');
$section->addText('Fase 2 (2-3 semanas): empaquetado y bootstrap del instalador.');
$section->addText('Fase 3 (1-2 semanas): pruebas UAT, rendimiento, actualización y recuperación.');
$section->addText('Fase 4 (1 semana): salida controlada a producción y estabilización.');

$section->addTitle('6. Riesgos y Mitigaciones', 2);
$section->addListItem('Conflicto de puertos: detección automática y fallback.', 0);
$section->addListItem('Antivirus/EDR: firma de código y política TI.', 0);
$section->addListItem('BD local: respaldos automáticos y restauración asistida.', 0);
$section->addListItem('Cambios de reglas: gobernanza y pruebas de regresión.', 0);

$section->addTitle('7. Entregables', 2);
$section->addListItem('Documento funcional y técnico.', 0);
$section->addListItem('Código, migraciones y scripts de despliegue.', 0);
$section->addListItem('Instalador .exe (piloto y productivo).', 0);
$section->addListItem('Evidencia de pruebas y manual operativo.', 0);

$section->addTitle('8. Conclusión', 2);
$section->addText('La conversión a .exe es viable y recomendable bajo un enfoque de empaquetado profesional con arquitectura clara y proceso SDLC disciplinado.');
$section->addText('Este enfoque reduce dependencia operativa, mejora soporte y permite evolución controlada del sistema.');

$outFile = __DIR__ . '/../docs/Propuesta_Integral_EXE_Arquitectura_y_Desarrollo.docx';
IOFactory::createWriter($phpWord, 'Word2007')->save($outFile);
echo $outFile . PHP_EOL;
