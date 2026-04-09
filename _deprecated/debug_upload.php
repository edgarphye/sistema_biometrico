<?php
// Debug script para identificar el error 500 exacto
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEPURACIÓN ERROR 500 SUBIDA DAT ===\n";

try {
    // 1. Incluir todas las dependencias necesarias
    echo "1. Cargando dependencias...\n";
    if (file_exists('config.php')) {
        require_once 'config.php';
    } else {
        // Definir constantes manualmente
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'biometrico');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_OPTIONS', []);
        define('BASE_URL', 'http://127.0.0.1/sistema_biometrico/');
        define('APP_NAME', 'Sistema Biométrico');
    }
    echo "   ✓ Configuración cargada\n";
    
    // Simular el entorno de la aplicación
    require_once 'models/Database.php';
    require_once 'models/Asistencia.php';
    require_once 'models/ZKTecoFormatDetector.php';
    require_once 'models/ZKTecoUniversalParser.php';
    require_once 'models/ZKTecoAsistenciaInserter.php';
    echo "   ✓ Modelos cargados\n";
    
    require_once 'controllers/ConfiguracionController.php';
    echo "   ✓ Controlador cargado\n";
    
    // 2. Simular el archivo exacto como lo haría la subida HTTP
    echo "2. Simulando archivo de subida...\n";
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    $file = [
        'tmp_name' => 'C:\\tools\\nginx\\html\\sistema_biometrico\\20241106.DAT',
        'name' => '20241106.DAT',
        'error' => UPLOAD_ERR_OK,
        'size' => filesize('20241106.DAT'),
        'type' => 'application/octet-stream'
    ];
    
    echo "   ✓ Archivo simulado: " . $file['name'] . " (" . $file['size'] . " bytes)\n";
    
    // 3. Crear instancia del controlador
    echo "3. Creando controlador...\n";
    
    // Iniciar sesión para evitar redirección
    if (!session_id()) {
        session_start();
    }
    $_SESSION['user_id'] = 1;
    $_SESSION['loggedin'] = true;
    
    $controller = new ConfiguracionController();
    echo "   ✓ Controlador creado\n";
    
    // 4. Ejecutar el método que causa el error
    echo "4. Ejecutando método uploadZkteco()...\n";
    
    // Capturar cualquier error
    ob_start();
    $_FILES['zk_file'] = $file;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $resultado = $controller->uploadZkteco();
    $output = ob_get_clean();
    
    echo "   ✓ Método ejecutado sin errores fatales\n";
    
    // 5. Analizar el resultado
    echo "5. Analizando resultado...\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n";
    
    if (!empty($output)) {
        echo "Output capturado:\n" . $output . "\n";
    }
    
    echo "\n🎉 PRUEBA COMPLETADA - NO HAY ERROR 500\n";
    echo "El problema podría estar en:\n";
    echo "  • Configuración de nginx/PHP\n";
    echo "  • Límites de subida (upload_max_filesize, post_max_size)\n";
    echo "  • Permisos de archivos\n";
    echo "  • Variables de servidor no simuladas\n";
    
} catch (ParseError $e) {
    echo "❌ ERROR DE SINTAXIS: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
} catch (Error $e) {
    echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEPURACIÓN ===\n";
?>