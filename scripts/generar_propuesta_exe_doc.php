<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

$phpWord = new PhpWord();
$phpWord->setDefaultFontName('Calibri');
$phpWord->setDefaultFontSize(11);

$phpWord->addTitleStyle(1, ['bold' => true, 'size' => 22], ['alignment' => Jc::CENTER, 'spaceAfter' => 280]);
$phpWord->addTitleStyle(2, ['bold' => true, 'size' => 15], ['spaceBefore' => 220, 'spaceAfter' => 140]);
$phpWord->addTitleStyle(3, ['bold' => true, 'size' => 12], ['spaceBefore' => 140, 'spaceAfter' => 100]);

$section = $phpWord->addSection([
    'marginTop' => 1200,
    'marginBottom' => 1200,
    'marginLeft' => 1200,
    'marginRight' => 1200,
]);

$section->addText('Propuesta Técnica Profesional', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER, 'spaceAfter' => 140]);
$section->addTitle('Estrategia para Convertir el Sistema a Ejecutable (.exe)', 1);
$section->addText('Proyecto: Sistema Biométrico de Asistencia', ['size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
$section->addText('Cliente/Área: Operación de Recursos Humanos', ['size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
$section->addText('Fecha: 05 de marzo de 2026', ['italic' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 280]);

$section->addTitle('1. Resumen Ejecutivo', 2);
$section->addText('Sí es viable distribuir el sistema en formato ejecutable (.exe) para Windows y evitar la dependencia de instalación manual de Nginx y PHP en cada equipo.');
$section->addText('La ruta recomendada no es compilar directamente el código PHP a binario nativo, sino empaquetar la aplicación con runtime embebido (servidor + PHP + base de datos local) y una interfaz de escritorio.');
$section->addText('Este enfoque permite operación offline, instalación simplificada, menor fricción de soporte y control de versiones para despliegues institucionales.');

$section->addTitle('2. Objetivo del Documento', 2);
$section->addListItem('Definir si el sistema actual puede convertirse en .exe.', 0);
$section->addListItem('Comparar alternativas técnicas disponibles.', 0);
$section->addListItem('Proponer una arquitectura objetivo y plan de implementación.', 0);
$section->addListItem('Establecer riesgos, costos operativos y controles de calidad.', 0);

$section->addTitle('3. Alcance', 2);
$section->addListItem('Sistema web actual basado en PHP.', 0);
$section->addListItem('Ejecución en Windows sin dependencia de instalación manual de stack web.', 0);
$section->addListItem('Distribución a usuarios finales mediante instalador.', 0);
$section->addListItem('Operación local con base de datos en equipo del usuario o modo cliente-servidor según política TI.', 0);

$section->addTitle('4. Alternativas Técnicas Evaluadas', 2);

$section->addTitle('4.1 Compilación directa PHP -> EXE', 3);
$section->addText('Factibilidad: limitada para aplicaciones empresariales complejas.');
$section->addListItem('Ventaja: entregable compacto.', 0);
$section->addListItem('Riesgo: alta fragilidad en librerías, extensiones, rutas y mantenimiento.', 0);
$section->addListItem('Conclusión: no recomendada como estrategia principal.', 0);

$section->addTitle('4.2 Empaquetado con runtime embebido (Recomendado)', 3);
$section->addText('Factibilidad: alta y probada en entornos de escritorio corporativo.');
$section->addListItem('Incluye PHP, servidor HTTP local y dependencias dentro del instalador.', 0);
$section->addListItem('El usuario opera el sistema mediante un acceso directo tipo aplicación.', 0);
$section->addListItem('Se elimina la necesidad de instalar Nginx/PHP manualmente.', 0);
$section->addListItem('Permite soporte, actualización y diagnóstico controlado.', 0);

$section->addTitle('4.3 Instalación híbrida cliente-servidor', 3);
$section->addText('Factibilidad: alta en redes institucionales.');
$section->addListItem('El .exe funciona como cliente/interfaz y consume un backend centralizado.', 0);
$section->addListItem('Ventaja: centralización de datos y políticas.', 0);
$section->addListItem('Requisito: conectividad permanente y administración central de infraestructura.', 0);

$section->addTitle('5. Arquitectura Recomendada', 2);
$section->addText('Arquitectura objetivo: "Aplicación de Escritorio con Motor Web Embebido"');
$section->addListItem('Capa 1 - Interfaz: WebView/Electron o lanzador de navegador restringido.', 0);
$section->addListItem('Capa 2 - Aplicación: código PHP actual del sistema biométrico.', 0);
$section->addListItem('Capa 3 - Runtime local: PHP + servidor HTTP embebido (sin Nginx manual).', 0);
$section->addListItem('Capa 4 - Datos: base local (modo standalone) o servidor central (modo corporativo).', 0);
$section->addListItem('Capa 5 - Servicios: respaldo automático, logs y actualización de versión.', 0);

$section->addTitle('6. Diseño de Entrega (.exe)', 2);
$section->addListItem('Instalador único firmado digitalmente.', 0);
$section->addListItem('Creación automática de servicios y accesos directos.', 0);
$section->addListItem('Inicialización de base de datos y migraciones al primer arranque.', 0);
$section->addListItem('Verificador de salud (puertos, escritura de archivos, integridad de BD).', 0);
$section->addListItem('Módulo de actualización controlada por versión.', 0);

$section->addTitle('7. Plan de Implementación Sugerido', 2);
$section->addText('Fase 1 - Preparación técnica (1 a 2 semanas)');
$section->addListItem('Normalizar configuración por variables de entorno.', 0);
$section->addListItem('Definir modo de base de datos (local o central).', 0);
$section->addListItem('Aislar rutas de archivos, logs y respaldos.', 0);

$section->addText('Fase 2 - Empaquetado y bootstrap (2 a 3 semanas)');
$section->addListItem('Integrar runtime embebido e instalador.', 0);
$section->addListItem('Automatizar alta de servicios de inicio/paro.', 0);
$section->addListItem('Construir .exe para entorno de pruebas (UAT).', 0);

$section->addText('Fase 3 - Validación funcional y operativa (1 a 2 semanas)');
$section->addListItem('Pruebas de asistencia, incidencias, validaciones y reportes.', 0);
$section->addListItem('Pruebas de rendimiento, apagado inesperado y recuperación.', 0);
$section->addListItem('Pruebas de instalación limpia y actualización.', 0);

$section->addText('Fase 4 - Salida a producción (1 semana)');
$section->addListItem('Despliegue gradual por área.', 0);
$section->addListItem('Monitoreo de errores y soporte inicial intensivo.', 0);
$section->addListItem('Cierre de estabilización con métricas y acta de aceptación.', 0);

$section->addTitle('8. Riesgos y Mitigaciones', 2);
$section->addListItem('Riesgo: conflicto de puertos locales. Mitigación: detector automático y puerto alterno.', 0);
$section->addListItem('Riesgo: antivirus/EDR bloquea procesos. Mitigación: firma de código y lista blanca TI.', 0);
$section->addListItem('Riesgo: corrupción de BD local. Mitigación: backups automáticos y restauración guiada.', 0);
$section->addListItem('Riesgo: divergencia entre sedes/equipos. Mitigación: control de versiones y políticas de actualización.', 0);

$section->addTitle('9. Recomendación Final', 2);
$section->addText('Sí es totalmente válido migrar el sistema a una distribución .exe para disminuir dependencia operativa de Nginx/PHP instalados manualmente.');
$section->addText('La opción más robusta y mantenible es empaquetar el sistema con runtime embebido e instalador corporativo, manteniendo la lógica actual en PHP y profesionalizando el proceso de despliegue.');

$section->addTitle('10. Próximos Pasos Ejecutivos', 2);
$section->addListItem('Aprobación de arquitectura objetivo (embebida o híbrida).', 0);
$section->addListItem('Definición de política de datos (local vs servidor central).', 0);
$section->addListItem('Arranque de piloto técnico con paquete ejecutable de prueba.', 0);
$section->addListItem('Plan de adopción por áreas con cronograma y responsables.', 0);

$section->addTextBreak(2);
$section->addText('Documento elaborado para toma de decisión técnica y de implementación.', ['italic' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

$outFile = __DIR__ . '/../docs/Propuesta_Tecnica_Conversion_EXE_Sistema_Biometrico.docx';
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($outFile);

echo $outFile . PHP_EOL;
