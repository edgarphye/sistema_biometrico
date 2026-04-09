<?php
// install_assets.php

header('Content-Type: text/plain; charset=utf-8');

echo "=== SCRIPT DE CREACIÓN DE ASSETS ===\n\n";

$rootDir = __DIR__;
$cssDir = $rootDir . '/assets/css';
$jsDir = $rootDir . '/assets/js';

// 1. Crear directorios si no existen
if (!is_dir($cssDir)) mkdir($cssDir, 0755, true);
if (!is_dir($jsDir)) mkdir($jsDir, 0755, true);
echo "✅ Directorios 'assets/css' y 'assets/js' verificados.\n";

// 2. Contenido de los archivos CSS y JS
$filesToCreate = [
    $cssDir . '/modals.css' => "/* Estilos para modales */\n.modal-header .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }",
    $cssDir . '/empleados.css' => "/* Estilos para la sección de empleados */\n.card-empleado:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); }",
    $cssDir . '/colores-pantone.css' => "
/* Colores Pantone */
.bg-pantone-primary { background-color: #972241 !important; }
.text-pantone-primary { color: #972241 !important; }
.border-pantone-primary { border-color: #972241 !important; }
.btn-outline-pantone-primary { color: #972241; border-color: #972241; }
.btn-outline-pantone-primary:hover { background-color: #972241; color: white; }
.badge-pantone-info { background-color: #17a2b8; color: white; }
.badge-pantone-gray { background-color: #6c757d; color: white; }",
    $jsDir . '/error-logger.js' => "console.log('Error logger inicializado.');"
];

// 3. Crear los archivos
foreach ($filesToCreate as $path => $content) {
    if (!file_exists($path)) {
        file_put_contents($path, trim($content));
        echo "✅ Creado: " . str_replace($rootDir, '', $path) . "\n";
    } else {
        echo "ℹ️  Ya existe: " . str_replace($rootDir, '', $path) . "\n";
    }
}

echo "\n🎉 Proceso completado. Los archivos CSS y JS necesarios han sido creados.\n";
echo "Ahora puedes recargar la página de tu aplicación. Los errores 404 y de tipo MIME deberían estar solucionados.\n";
echo "Este script (install_assets.php) puede ser eliminado de forma segura.\n";

?>