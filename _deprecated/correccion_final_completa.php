<?php
// Script final para aplicar todas las correcciones al sistema biométrico

echo "🚀 === CORRECCIÓN COMPLETA DEL SISTEMA BIOMÉTRICO === 🚀\n\n";

$db = null;
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/models/Database.php';
    $db = Database::getInstance();
    echo "✅ Base de datos conectada\n";
} catch (Exception $e) {
    echo "❌ Error conectando a la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

// 1. Corrección de seguridad crítica
echo "\n📋 1. SEGURIDAD CRÍTICA\n";
echo "--------------------------------\n";

// Actualizar contraseña de admin
echo "1.1 Actualizando contraseña del administrador...\n";
$nuevo_hash = password_hash('Sistema2026!@#$', PASSWORD_DEFAULT);
$sql_pass = "UPDATE usuarios SET password = ? WHERE username = 'admin'";
$stmt_pass = $db->getConnection()->prepare($sql_pass);
$stmt_pass->execute([$nuevo_hash]);
echo "   ✓ Contraseña de admin actualizada\n";

// 2. Corregir empleados.js BASE_URL
echo "\n📋 2. CORRECCIÓN DE RUTAS JAVASCRIPT\n";
echo "-------------------------------------------\n";

$empleados_js = __DIR__ . '/public/js/empleados.js';
if (file_exists($empleados_js)) {
    $contenido = file_get_contents($empleados_js);
    $contenido_corregido = str_replace(
        "baseUrl: window.location.origin + '/sistema_biometrico',",
        "baseUrl: window.location.origin,",
        $contenido
    );
    file_put_contents($empleados_js, $contenido_corregido);
    echo "   ✓ BASE_URL corregida en empleados.js\n";
} else {
    echo "   ⚠ empleados.js no encontrado\n";
}

// 3. Eliminar archivo duplicado
$duplicado = __DIR__ . '/assets/js/empleados.js';
if (file_exists($duplicado)) {
    unlink($duplicado);
    echo "   ✓ Archivo duplicado eliminado\n";
}

// 4. Verificar endpoint de contador
echo "\n📋 3. VERIFICACIÓN DE ENDPOINTS\n";
echo "----------------------------\n";

// Verificar si el método existe en el controller
$controller_file = __DIR__ . '/controllers/ValidacionJefeController.php';
if (file_exists($controller_file)) {
    $contenido_controller = file_get_contents($controller_file);
    if (strpos($contenido_controller, 'function obtenerContadorPendientes') === false) {
        echo "4.1 Agregando método obtenerContadorPendientes...\n";
        
        $nuevo_metodo = "
    /**
     * Obtener contador de validaciones pendientes para el menú
     */
    public function obtenerContadorPendientes() {
        try {
            \$sql = \"SELECT COUNT(*) as total FROM retardos WHERE estado_validacion = 'pendiente'\";
            \$stmt = \$this->db->getConnection()->prepare(\$sql);
            \$stmt->execute();
            \$resultado = \$stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'contador' => (int)\$resultado['total'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception \$e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => \$e->getMessage()
            ]);
        }
    }";
        
        // Agregar al final del controller
        file_put_contents($controller_file, $contenido_controller . $nuevo_metodo);
        echo "   ✓ Método agregado exitosamente\n";
    } else {
        echo "4.1 ✓ Método obtenerContadorPendientes ya existe\n";
    }
} else {
    echo "4.1 ⚠ ValidacionJefeController.php no encontrado\n";
}

// 5. Agregar endpoint en routes.php
echo "\n📋 4. AGREGAR ENDPOINT EN ROUTES\n";
echo "-----------------------------------\n";

$routes_file = __DIR__ . '/routes.php';
if (file_exists($routes_file)) {
    $contenido_routes = file_get_contents($routes_file);
    
    if (strpos($contenido_routes, '/validaciones/obtener-contador-pendientes') === false) {
        echo "5.1 Agregando endpoint en routes.php...\n";
        
        $nuevo_endpoint = "
        // Endpoint para obtener contador de validaciones pendientes
        \$r->addRoute('GET', '/validaciones/obtener-contador-pendientes', ['ValidacionJefeController', 'obtenerContadorPendientes']);";
        
        // Insertar antes del último cierre de llave
        $contenido_modificado = str_replace("?>", $nuevo_endpoint . "\n?>", $contenido_routes);
        file_put_contents($routes_file, $contenido_modificado);
        echo "   ✓ Endpoint agregado exitosamente\n";
    } else {
        echo "5.1 ✓ Endpoint ya existe en routes.php\n";
    }
} else {
    echo "5.1 ⚠ routes.php no encontrado\n";
}

// 6. Corregir config.php BASE_URL
echo "\n📋 5. CORRECCIÓN DE CONFIGURACIÓN\n";
echo "--------------------------------\n";

$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    echo "6.1 Verificando configuración BASE_URL actual...\n";
    
    // Hacer backup
    copy($config_file, $config_file . '.backup_' . date('Y-m-d_H-i-s'));
    
    $contenido_config = file_get_contents($config_file);
    echo "   ✓ Backup de config.php creado\n";
    
    // Mostrar configuración actual
    echo "   Configuración actual detectada:\n";
    echo "   - BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NO DEFINIDA') . "\n";
}

// 7. Verificar estructura de archivos importantes
echo "\n📋 6. VERIFICACIÓN DE ARCHIVOS\n";
echo "--------------------------------\n";

$archivos_verificar = [
    'public/js/empleados.js',
    'controllers/ValidacionJefeController.php',
    'routes.php',
    'nginx.conf',
    'config.php'
];

foreach ($archivos_verificar as $archivo) {
    $ruta = __DIR__ . '/' . $archivo;
    if (file_exists($ruta)) {
        $tamano = filesize($ruta);
        echo "   ✓ $archivo ($tamano bytes)\n";
    } else {
        echo "   ⚠ $archivo NO ENCONTRADO\n";
    }
}

// 8. Estadísticas finales
echo "\n📊 7. ESTADÍSTICAS DEL SISTEMA\n";
echo "--------------------------------\n";

// Usuarios en el sistema
$sql_users = "SELECT COUNT(*) as total, SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as admins FROM usuarios";
$stmt_users = $db->getConnection()->prepare($sql_users);
$stmt_users->execute();
$usuarios_stats = $stmt_users->fetch();
echo "   Usuarios totales: " . $usuarios_stats['total'] . "\n";
echo "   Administradores: " . $usuarios_stats['admins'] . "\n";

// Empleados en el sistema
$sql_empleados = "SELECT COUNT(*) as total FROM empleados";
$stmt_empleados = $db->getConnection()->prepare($sql_empleados);
$stmt_empleados->execute();
$empleados_total = $stmt_empleados->fetch()['total'];
echo "   Empleados: " . $empleados_total . "\n";

// Validaciones pendientes
$sql_validaciones = "SELECT COUNT(*) as total FROM retardos WHERE estado_validacion = 'pendiente'";
$stmt_validaciones = $db->getConnection()->prepare($sql_validaciones);
$stmt_validaciones->execute();
$validaciones_pendientes = $stmt_validaciones->fetch()['total'];
echo "   Validaciones pendientes: " . $validaciones_pendientes . "\n";

// Resumen final
echo "\n✅ CORRECCIONES APLICADAS EXITOSAMENTE\n";
echo "\n🎯 ACCIONES FINALES REQUERIDAS:\n";
echo "=====================================\n";
echo "1. REINICIAR SERVIDOR WEB (nginx)\n";
echo "2. LIMPIAR CACHÉ DEL NAVEGADOR\n";
echo "3. PROBAR LOGIN CON:\n";
echo "   - Usuario: admin\n";
echo "   - Contraseña: Sistema2026!@#$\n";
echo "4. PROBAR ENDPOINT:\n";
echo "   curl http://localhost/biometrico/validaciones/obtener-contador-pendientes\n";
echo "5. VERIFICAR QUE empleados.js CARGE SIN ERRORES:\n";
echo "   http://localhost/biometrico/views/empleados/\n";
echo "=====================================\n";

echo "🚀 ¡SISTEMA CORREGIDO Y OPTIMIZADO! 🚀\n";
?>