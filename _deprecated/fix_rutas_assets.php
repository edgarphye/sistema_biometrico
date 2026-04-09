<?php
// Corrección de rutas de assets JavaScript

echo "=== 🔧 CORRECCIÓN DE RUTAS DE ASSETS ===\n\n";

// 1. Eliminar archivo duplicado
$duplicado = __DIR__ . '/assets/js/empleados.js';
$correcto = __DIR__ . '/public/js/empleados.js';

if (file_exists($duplicado)) {
    echo "1. Eliminando archivo duplicado: assets/js/empleados.js\n";
    unlink($duplicado);
    echo "   ✓ Archivo duplicado eliminado\n";
} else {
    echo "1. Archivo duplicado no encontrado\n";
}

// 2. Verificar que el archivo correcto exista
if (file_exists($correcto)) {
    echo "2. Verificando archivo correcto: public/js/empleados.js\n";
    echo "   ✓ Archivo correcto encontrado\n";
    
    // Verificar contenido de BASE_URL
    $contenido = file_get_contents($correcto);
    if (strpos($contenido, 'window.location.origin') !== false) {
        echo "3. Corrigiendo BASE_URL en empleados.js\n";
        
        $contenido_corregido = str_replace(
            "baseUrl: window.location.origin + '/sistema_biometrico',",
            "baseUrl: window.location.origin,",
            $contenido
        );
        
        file_put_contents($correcto, $contenido_corregido);
        echo "   ✓ BASE_URL corregida\n";
    } else {
        echo "3. BASE_URL ya está correcta\n";
    }
} else {
    echo "2. ⚠ Archivo correcto no encontrado\n";
}

// 3. Crear directorio public/js si no existe
$directorio = dirname($correcto);
if (!is_dir($directorio)) {
    echo "4. Creando directorio: $directorio\n";
    mkdir($directorio, 0755, true);
    echo "   ✓ Directorio creado\n";
}

// 4. Verificar configuración nginx
echo "\n5. Verificando configuración nginx...\n";
$nginx_config = __DIR__ . '/nginx.conf';
if (file_exists($nginx_config)) {
    $contenido_nginx = file_get_contents($nginx_config);
    if (strpos($contenido_nginx, 'location /assets/') !== false) {
        echo "   ✓ Configuración nginx para /assets/ encontrada\n";
    } else {
        echo "   ⚠ Configuración nginx para /assets/ no encontrada\n";
    }
}

// 5. Verificar archivos JavaScript críticos
$archivos_js = [
    'public/js/empleados.js',
    'views/validaciones/contador_menu.js',
    'assets/js/validaciones.js'
];

echo "\n6. Verificando archivos JavaScript críticos:\n";
foreach ($archivos_js as $archivo) {
    if (file_exists($archivo)) {
        $size = filesize($archivo);
        echo "   ✓ $archivo ($size bytes)\n";
    } else {
        echo "   ⚠ $archivo no encontrado\n";
    }
}

echo "\n✅ CORRECCIONES DE ASSETS APLICADAS\n";
echo "📋 ACCIONES MANUALES REQUERIDAS:\n";
echo "   1. Reiniciar nginx: nginx -s reload\n";
echo "   2. Limpiar caché del navegador\n";
echo "   3. Probar acceso a: http://localhost/biometrico/public/js/empleados.js\n";
?>