<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNÓSTICO DE EMPLEADOS VER/EDITAR ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Base de datos conectada\n";
    
    // 1. Verificar que existen los archivos
    echo "\n--- Verificando archivos ---\n";
    
    $archivos_requeridos = [
        'views/empleados/index.php' => 'Lista de empleados',
        'views/empleados/show.php' => 'Ver empleado',
        'views/empleados/edit.php' => 'Editar empleado',
        'views/empleados/create.php' => 'Crear empleado',
        'controllers/EmpleadoController.php' => 'Controlador de empleados',
        'models/Empleado.php' => 'Modelo de empleados'
    ];
    
    foreach ($archivos_requeridos as $archivo => $descripcion) {
        if (file_exists(__DIR__ . '/' . $archivo)) {
            echo "✅ $descripcion: $archivo\n";
        } else {
            echo "❌ $descripcion: $archivo (NO EXISTE)\n";
        }
    }
    
    // 2. Verificar empleados en la base de datos
    echo "\n--- Verificando empleados en BD ---\n";
    
    $stmt = $pdo->query("SELECT id, nombre, apellido, area, activo FROM empleados ORDER BY id LIMIT 5");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($empleados)) {
        echo "❌ No hay empleados en la base de datos\n";
    } else {
        echo "✅ Empleados encontrados:\n";
        foreach ($empleados as $emp) {
            echo "   ID: {$emp['id']} - {$emp['nombre']} {$emp['apellido']} ({$emp['area']}) - " . ($emp['activo'] ? 'Activo' : 'Inactivo') . "\n";
        }
    }
    
    // 3. Probar el modelo Empleado
    echo "\n--- Probando modelo Empleado ---\n";
    
    try {
        require_once __DIR__ . '/models/Empleado.php';
        $empleadoModel = new Empleado();
        
        // Probar getById
        if (!empty($empleados)) {
            $test_id = $empleados[0]['id'];
            $empleado = $empleadoModel->getById($test_id);
            
            if ($empleado) {
                echo "✅ Modelo getById() funciona para ID: $test_id\n";
                echo "   Nombre: " . ($empleado['nombre'] ?? 'N/A') . "\n";
                echo "   Apellido: " . ($empleado['apellido'] ?? 'N/A') . "\n";
                echo "   Área: " . ($empleado['area'] ?? 'N/A') . "\n";
            } else {
                echo "❌ Modelo getById() no funciona\n";
            }
        }
        
        // Probar getAllPaginated
        $empleados_paginados = $empleadoModel->getAllPaginated(1, 5, '', '', '');
        if ($empleados_paginados) {
            echo "✅ Modelo getAllPaginated() funciona\n";
            echo "   Encontrados: " . count($empleados_paginados['data'] ?? []) . " empleados\n";
        } else {
            echo "❌ Modelo getAllPaginated() no funciona\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error en modelo Empleado: " . $e->getMessage() . "\n";
    }
    
    // 4. Verificar rutas
    echo "\n--- Verificando rutas ---\n";
    
    $routes_content = file_get_contents(__DIR__ . '/routes.php');
    
    $rutas_empleados = [
        "/empleados/{id:\\d+}" => "Ver empleado",
        "/empleados/{id:\\d+}/edit" => "Editar empleado GET",
        "/empleados/edit" => "Editar empleado POST"
    ];
    
    foreach ($rutas_empleados as $ruta => $descripcion) {
        if (strpos($routes_content, $ruta) !== false) {
            echo "✅ $descripcion: $ruta\n";
        } else {
            echo "❌ $descripcion: $ruta (NO ENCONTRADA)\n";
        }
    }
    
    // 5. Simular URLs de prueba
    echo "\n--- URLs de prueba ---\n";
    
    $base_url = rtrim(BASE_URL, '/');
    
    if (!empty($empleados)) {
        $test_id = $empleados[0]['id'];
        echo "📋 URLs para empleado ID: $test_id\n";
        echo "   Ver: $base_url/empleados/$test_id\n";
        echo "   Editar: $base_url/empleados/$test_id/edit\n";
        echo "   Lista: $base_url/empleados\n";
    }
    
    // 6. Verificar problemas comunes
    echo "\n--- Posibles problemas ---\n";
    
    // Verificar si hay sesión iniciada
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo "⚠️ No hay sesión iniciada - podría redirigir al login\n";
    } else {
        echo "✅ Sesión iniciada - Usuario ID: " . $_SESSION['user_id'] . "\n";
    }
    
    // Verificar permisos
    if (isset($_SESSION['rol'])) {
        echo "✅ Rol del usuario: " . $_SESSION['rol'] . "\n";
        if ($_SESSION['rol'] === 'empleado' && !in_array($_SESSION['rol'], ['admin', 'rh'])) {
            echo "⚠️ El rol podría no tener permisos suficientes\n";
        }
    } else {
        echo "❌ No hay rol definido en la sesión\n";
    }
    
    // 7. Revisar archivos de layout
    echo "\n--- Verificando layout ---\n";
    
    $layout_file = __DIR__ . '/views/layout.php';
    if (file_exists($layout_file)) {
        echo "✅ Layout existe\n";
    } else {
        echo "❌ Layout no existe\n";
    }
    
    echo "\n=== RECOMENDACIONES ===\n";
    echo "1. Para VER un empleado:\n";
    echo "   - URL: $base_url/empleados/ID_DEL_EMPLEADO\n";
    echo "   - Ejemplo: $base_url/empleados/121\n";
    echo "\n2. Para EDITAR un empleado:\n";
    echo "   - URL: $base_url/empleados/ID_DEL_EMPLEADO/edit\n";
    echo "   - Ejemplo: $base_url/empleados/121/edit\n";
    echo "\n3. Para LISTAR empleados:\n";
    echo "   - URL: $base_url/empleados\n";
    echo "\n4. Si los formularios no se muestran:\n";
    echo "   - Verificar que estés logueado\n";
    echo "   - Revisar la consola de desarrollador (F12)\n";
    echo "   - Verificar que no haya errores de PHP\n";
    echo "   - Revisar que los archivos .php tengan los permisos correctos\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>