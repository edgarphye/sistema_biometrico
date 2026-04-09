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

$section->addText('Documento Técnico y Ejecutivo', ['bold'=>true,'size'=>16], ['alignment'=>Jc::CENTER,'spaceAfter'=>120]);
$section->addTitle('Propuesta Integral Ampliada', 1);
$section->addText('Conversión a .exe + Arquitectura + Proceso de Desarrollo + Gobierno Técnico', ['size'=>12], ['alignment'=>Jc::CENTER, 'spaceAfter'=>30]);
$section->addText('Proyecto: Sistema Biométrico de Asistencia', ['size'=>12], ['alignment'=>Jc::CENTER, 'spaceAfter'=>30]);
$section->addText('Fecha: 05 de marzo de 2026', ['italic'=>true,'size'=>11], ['alignment'=>Jc::CENTER, 'spaceAfter'=>240]);

$section->addTitle('1. Alcance de esta Propuesta', 2);
$section->addListItem('Estrategia para operar el sistema como aplicación instalable en Windows (.exe).', 0);
$section->addListItem('Arquitectura recomendada para sostenibilidad y escalabilidad.', 0);
$section->addListItem('Proceso de generación y desarrollo basado en SDLC.', 0);
$section->addListItem('Lineamientos de seguridad, roles, calidad y operación productiva.', 0);

$section->addTitle('2. Resumen Ejecutivo', 2);
$section->addText('Sí es viable eliminar la dependencia de instalación manual de Nginx/PHP mediante empaquetado con runtime embebido.');
$section->addText('La recomendación es mantener la base tecnológica actual (PHP + BD), profesionalizando despliegue, arquitectura y ciclo de desarrollo.');

$section->addTitle('3. Decisión Técnica Recomendadada', 2);
$section->addListItem('Estrategia principal: app de escritorio empaquetada con runtime embebido.', 0);
$section->addListItem('Estrategia no recomendada: compilación directa de PHP a binario nativo puro.', 0);
$section->addListItem('Resultado esperado: instalación simple, operación estable y soporte centralizado.', 0);

$section->addTitle('4. Arquitectura de Software', 2);
$section->addTitle('4.1 Principios', 3);
$section->addListItem('Separación de capas: presentación, aplicación, dominio, persistencia.', 0);
$section->addListItem('Reglas explícitas en BD (tipo_incidencia) para evitar heurísticas por texto.', 0);
$section->addListItem('Control de acceso por rol en frontend y backend.', 0);
$section->addListItem('Trazabilidad de decisiones de validación y estado de incidencias.', 0);

$section->addTitle('4.2 Componentes Clave', 3);
$section->addListItem('Asistencia: captura y clasificación base.', 0);
$section->addListItem('Incidencias: por_definir -> tipo específico de justificación.', 0);
$section->addListItem('Validación de jefe: estado, motivo y comentarios con evidencia de decisión.', 0);
$section->addListItem('Destino por tipo: comisiones, días económicos, ausencias/licencias.', 0);

$section->addTitle('5. Proceso de Desarrollo (SDLC)', 2);
$section->addTitle('5.1 Planeación y análisis', 3);
$section->addListItem('Requerimientos por rol: admin, superadmin, jefe, usuario.', 0);
$section->addListItem('Definición de historias y criterios verificables.', 0);

$section->addTitle('5.2 Diseño', 3);
$section->addListItem('Modelo de datos y contratos de API/controlador-vista.', 0);
$section->addListItem('Diseño de catálogos de justificación y criterios por tipo.', 0);

$section->addTitle('5.3 Construcción', 3);
$section->addListItem('Desarrollo incremental por módulo.', 0);
$section->addListItem('Migraciones versionadas y cambios reversibles.', 0);

$section->addTitle('5.4 Pruebas', 3);
$section->addListItem('Unitarias de reglas críticas.', 0);
$section->addListItem('Funcionales end-to-end (captura -> incidencia -> validación jefe).', 0);
$section->addListItem('Pruebas de roles/permisos y regresión.', 0);

$section->addTitle('5.5 Despliegue', 3);
$section->addListItem('UAT, liberación controlada y monitoreo de estabilización.', 0);
$section->addListItem('Backups previos y plan de rollback.', 0);

$section->addTitle('6. Proceso de Generación del .exe', 2);
$section->addListItem('Empaquetado de aplicación + servidor embebido + PHP + dependencias.', 0);
$section->addListItem('Instalador con bootstrap automático de configuración.', 0);
$section->addListItem('Creación de accesos directos y servicio local de ejecución.', 0);
$section->addListItem('Mecanismo de actualización y verificación de integridad.', 0);

$section->addTitle('7. Reglas de Negocio Incluidas (Puntos Comentados)', 2);
$section->addListItem('Tipos de justificación activos: día económico, licencia médica, vacaciones, cuidados maternos/paternos, comisión entrada/salida/todo el día.', 0);
$section->addListItem('Mapeo explícito por tipo_incidencia en base de datos.', 0);
$section->addListItem('Validación de jefe visible en incidencias del empleado (estado, motivo, comentario).', 0);
$section->addListItem('Control de cejilla ciclo/horarios por rol (admin/superadmin visibles, usuario oculto y bloqueado en backend).', 0);

$section->addTitle('8. Seguridad y Gobierno Técnico', 2);
$section->addListItem('Matriz de permisos por rol y endpoint.', 0);
$section->addListItem('Auditoría funcional de cambios críticos.', 0);
$section->addListItem('Política de logs técnicos y funcionales.', 0);
$section->addListItem('Checklist de calidad por release.', 0);

$section->addTitle('9. Riesgos y Mitigación', 2);
$section->addListItem('Riesgo operativo: conflictos de puertos locales -> mitigación: detección y fallback.', 0);
$section->addListItem('Riesgo de seguridad endpoint -> mitigación: validación de rol en backend.', 0);
$section->addListItem('Riesgo de inconsistencia de reglas -> mitigación: catálogo y mapeo explícito.', 0);
$section->addListItem('Riesgo de soporte en campo -> mitigación: instalador estándar y diagnóstico guiado.', 0);

$section->addTitle('10. Entregables', 2);
$section->addListItem('Documento técnico-funcional consolidado.', 0);
$section->addListItem('Código y migraciones versionadas.', 0);
$section->addListItem('Paquete instalable .exe (piloto y productivo).', 0);
$section->addListItem('Manual de operación, respaldo y actualización.', 0);

$section->addTitle('11. Conclusión', 2);
$section->addText('La propuesta integral es viable técnica y operativamente.');
$section->addText('Permite profesionalizar el sistema actual, reducir dependencia manual del stack web y establecer una base sólida de crecimiento con control de calidad y gobernanza.');

$outFile = __DIR__ . '/../docs/Propuesta_Integral_Ampliada_EXE_Arquitectura_Desarrollo.docx';
IOFactory::createWriter($phpWord, 'Word2007')->save($outFile);
echo $outFile . PHP_EOL;
