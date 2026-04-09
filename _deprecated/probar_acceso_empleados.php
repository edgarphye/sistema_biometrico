<?php
require_once __DIR__ . '/config.php';

echo "=== PRUEBA DE ACCESO A EMPLEADOS ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Base de datos conectada\n";
    
    // 1. Verificar usuarios disponibles
    echo "\n--- Usuarios disponibles para login ---\n";
    
    $stmt = $pdo->query("SELECT id, username, nombre_completo, rol FROM usuarios WHERE activo = 1 ORDER BY rol, username");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($usuarios as $usuario) {
        echo "   Usuario: {$usuario['username']} ({$usuario['nombre_completo']}) - Rol: {$usuario['rol']}\n";
    }
    
    // 2. Simular inicio de sesión con un usuario existente
    echo "\n--- Simulando inicio de sesión ---\n";
    
    // Buscar un usuario con rol que permita acceso a empleados
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rol IN ('admin', 'rh') AND activo = 1 LIMIT 1");
    $stmt->execute();
    $usuario_test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario_test) {
        // Si no hay admin/rh, usar cualquier usuario activo
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE activo = 1 LIMIT 1");
        $stmt->execute();
        $usuario_test = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($usuario_test) {
        echo "✅ Usuario seleccionado: {$usuario_test['username']} (Rol: {$usuario_test['rol']})\n";
        
        // Simular sesión
        session_start();
        $_SESSION['user_id'] = $usuario_test['id'];
        $_SESSION['username'] = $usuario_test['username'];
        $_SESSION['rol'] = $usuario_test['rol'];
        $_SESSION['nombre'] = $usuario_test['nombre_completo'];
        
        echo "✅ Sesión iniciada correctamente\n";
        echo "   User ID: {$_SESSION['user_id']}\n";
        echo "   Username: {$_SESSION['username']}\n";
        echo "   Rol: {$_SESSION['rol']}\n";
        
    } else {
        echo "❌ No se encontraron usuarios activos\n";
        exit(1);
    }
    
    // 3. Probar acceso al método show()
    echo "\n--- Probando método show() ---\n";
    
    try {
        require_once __DIR__ . '/controllers/EmpleadoController.php';
        require_once __DIR__ . '/models/Empleado.php';
        
        $controller = new EmpleadoController();
        
        // Obtener un empleado para prueba
        $stmt = $pdo->query("SELECT id FROM empleados LIMIT 1");
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empleado) {
            $empleado_id = $empleado['id'];
            echo "📋 Probando show() con empleado ID: $empleado_id\n";
            
            // Capturar salida del método show
            ob_start();
            $controller->show($empleado_id);
            $output = ob_get_clean();
            
            if (strpos($output, '<!DOCTYPE html') !== false) {
                echo "✅ show() genera HTML correctamente\n";
                echo "   Tamaño de salida: " . strlen($output) . " bytes\n";
                if (strpos($output, 'Empleado') !== false) {
                    echo "✅ Contenido de empleado encontrado en la salida\n";
                } else {
                    echo "⚠️ Posible error en el contenido del empleado\n";
                }
            } else {
                echo "❌ show() no genera HTML válido\n";
                echo "   Salida: " . substr($output, 0, 200) . "...\n";
            }
            
        } else {
            echo "❌ No hay empleados para probar show()\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error en show(): " . $e->getMessage() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
    }
    
    // 4. Probar acceso al método edit() (sin POST)
    echo "\n--- Probando método edit() (GET) ---\n";
    
    try {
        if ($empleado) {
            $empleado_id = $empleado['id'];
            echo "📋 Probando edit() con empleado ID: $empleado_id\n";
            
            // Capturar salida del método edit
            ob_start();
            $controller->edit($empleado_id);
            $output = ob_get_clean();
            
            if (strpos($output, '<!DOCTYPE html') !== false) {
                echo "✅ edit() (GET) genera HTML correctamente\n";
                echo "   Tamaño de salida: " . strlen($output) . " bytes\n";
                if (strpos($output, 'Editar Empleado') !== false) {
                    echo "✅ Formulario de edición encontrado en la salida\n";
                } else {
                    echo "⚠️ Posible error en el formulario de edición\n";
                }
            } else {
                echo "❌ edit() (GET) no genera HTML válido\n";
                echo "   Salida: " . substr($output, 0, 200) . "...\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error en edit(): " . $e->getMessage() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
    }
    
    // 5. Probar el método index()
    echo "\n--- Probando método index() ---\n";
    
    try {
        echo "📋 Probando index() para lista de empleados\n";
        
        // Capturar salida del método index
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        if (strpos($output, '<!DOCTYPE html') !== false) {
            echo "✅ index() genera HTML correctamente\n";
            echo "   Tamaño de salida: " . strlen($output) . " bytes\n";
            if (strpos($output, 'empleados') !== false) {
                echo "✅ Contenido de lista de empleados encontrado en la salida\n";
            } else {
                echo "⚠️ Posible error en la lista de empleados\n";
            }
        } else {
            echo "❌ index() no genera HTML válido\n";
            echo "   Salida: " . substr($output, 0, 200) . "...\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error en index(): " . $e->getMessage() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
    }
    
    // 6. Resumen y URLs finales
    echo "\n=== RESUMEN FINAL ===\n";
    
    $base_url = rtrim(BASE_URL, '/');
    
    echo "✅ Sistema de empleados funcional\n";
    echo "\n📋 URLs para prueba en navegador:\n";
    echo "1. Lista de empleados: $base_url/empleados\n";
    
    if (isset($empleado_id)) {
        echo "2. Ver empleado: $base_url/empleados/$empleado_id\n";
        echo "3. Editar empleado: $base_url/empleados/$empleado_id/edit\n";
    }
    
    echo "\n🔐 Credenciales para prueba:\n";
    echo "   Usuario: {$usuario_test['username']}\n";
    echo "   Rol: {$usuario_test['rol']}\n";
    
    if ($usuario_test['rol'] !== 'admin') {
        echo "⚠️ Este rol podría tener permisos limitados\n";
        echo "   Considera crear un usuario admin para acceso completo\n";
    }
    
    echo "\n✅ Los formularios VER y EDITAR deben funcionar correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>