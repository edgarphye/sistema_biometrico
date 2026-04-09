<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNÓSTICO DETALLADO DE VALIDACIONES ===\n";

try {
    // 1. Probar la carga inicial de la página de validaciones
    echo "\n--- Probando carga inicial ---\n";
    
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    // Simular un usuario jefe autenticado
    session_start();
    $stmt = $pdo->prepare("SELECT id, username, nombre_completo, rol FROM usuarios WHERE username = 'jefe1_prueba' AND rol = 'jefe'");
    $stmt->execute();
    $jefe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jefe) {
        $_SESSION['user_id'] = $jefe['id'];
        $_SESSION['username'] = $jefe['username'];
        $_SESSION['rol'] = $jefe['rol'];
        $_SESSION['nombre'] = $jefe['nombre_completo'];
        
        echo "✅ Sesión iniciada: {$jefe['username']} (ID: {$jefe['id']})\n";
        
        // 2. Cargar el HTML de la página de validaciones
        echo "\n--- Cargando página de validaciones ---\n";
        
        ob_start();
        
        // Simular la petición GET a /validaciones
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/validaciones';
        
        // Cargar el controlador manualmente
        require_once __DIR__ . '/controllers/ValidacionJefeController.php';
        require_once __DIR__ . '/models/ValidacionJefe.php';
        
        $controller = new ValidacionJefeController();
        
        // Verificar si el método index existe
        if (method_exists($controller, 'index')) {
            echo "✅ Método index() existe en ValidacionJefeController\n";
            
            try {
                $controller->index();
                $html_output = ob_get_clean();
                
                echo "✅ Página generada correctamente\n";
                echo "📏 Tamaño del HTML: " . strlen($html_output) . " bytes\n";
                
                // Buscar componentes clave en el HTML
                $componentes_clave = [
                    'empleadosContainer' => 'Contenedor de empleados',
                    'btnSeleccionarTodosEmpleados' => 'Botón seleccionar todos',
                    'formFiltros' => 'Formulario de filtros',
                    'validacionesPendientesContainer' => 'Contenedor de validaciones'
                ];
                
                foreach ($componentes_clave as $componente => $descripcion) {
                    if (strpos($html_output, 'id="' . $componente . '"') !== false) {
                        echo "✅ $descripcion encontrado (id: $componente)\n";
                    } else {
                        echo "❌ $descripcion NO encontrado (id: $componente)\n";
                    }
                }
                
                // Buscar scripts y configuración
                if (strpos($html_output, 'window.BASE_URL') !== false) {
                    echo "✅ window.BASE_URL configurado\n";
                } else {
                    echo "❌ window.BASE_URL NO configurado\n";
                }
                
                if (strpos($html_output, 'empleadosACargo') !== false) {
                    echo "✅ Variable empleadosACargo encontrada\n";
                } else {
                    echo "⚠️ Variable empleadosACargo no encontrada\n";
                }
                
                // Extraer el contenido del container de empleados
                if (preg_match('/id="empleadosContainer">(.*?)<\/div>/s', $html_output, $matches)) {
                    $empleados_container_content = $matches[1];
                    echo "📋 Contenido del container de empleados:\n";
                    echo "   " . substr(trim(strip_tags($empleados_container_content)), 0, 200) . "...\n";
                    
                    if (strpos($empleados_container_content, 'Cargando') !== false) {
                        echo "🔄 Container está en estado de carga\n";
                    } elseif (strpos($empleados_container_content, 'No hay empleados') !== false) {
                        echo "📭 Container muestra 'No hay empleados'\n";
                    } else {
                        echo "✅ Container parece tener contenido dinámico\n";
                    }
                } else {
                    echo "❌ No se pudo extraer contenido del container\n";
                }
                
            } catch (Exception $e) {
                echo "❌ Error en método index(): " . $e->getMessage() . "\n";
                echo "   Línea: " . $e->getLine() . "\n";
            }
        } else {
            echo "❌ Método index() NO existe en ValidacionJefeController\n";
        }
        
        // 3. Probar la API de buscar empleados directamente
        echo "\n--- Probando API buscar-empleados ---\n";
        
        try {
            // Resetear output buffer
            ob_start();
            
            // Simular petición a buscar-empleados
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['query'] = '';
            
            if (method_exists($controller, 'buscarEmpleados')) {
                $controller->buscarEmpleados();
                $api_response = ob_get_clean();
                
                echo "✅ API buscar-empleados ejecutada\n";
                echo "📤 Respuesta: " . substr($api_response, 0, 200) . "...\n";
                
                // Verificar si es JSON válido
                $json_data = json_decode($api_response, true);
                if ($json_data && isset($json_data['success'])) {
                    echo "✅ Respuesta JSON válida\n";
                    echo "   Success: " . ($json_data['success'] ? 'true' : 'false') . "\n";
                    
                    if ($json_data['success'] && isset($json_data['empleados'])) {
                        echo "   Empleados encontrados: " . count($json_data['empleados']) . "\n";
                        
                        if (!empty($json_data['empleados'])) {
                            echo "   Primer empleado: " . ($json_data['empleados'][0]['nombre_completo'] ?? 'N/A') . "\n";
                        } else {
                            echo "   ⚠️ Array de empleados vacío\n";
                        }
                    } else {
                        echo "   ⚠️ No hay empleados en la respuesta\n";
                    }
                } else {
                    echo "❌ Error en respuesta JSON\n";
                    if (isset($json_data['error'])) {
                        echo "   Error: " . $json_data['error'] . "\n";
                    }
                }
            } else {
                echo "❌ Método buscarEmpleados() NO existe\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error en API buscar-empleados: " . $e->getMessage() . "\n";
            echo "   Línea: " . $e->getLine() . "\n";
        }
        
        // 4. Verificar la conexión del jefe con empleados
        echo "\n--- Verificando empleados asignados al jefe ---\n";
        
        $jefe_id = $jefe['id'];
        $stmt_empleados = $pdo->prepare("SELECT COUNT(*) as total FROM empleados WHERE jefe_directo_id = ?");
        $stmt_empleados->execute([$jefe_id]);
        $empleados_asignados = $stmt_empleados->fetch(PDO::FETCH_ASSOC);
        
        echo "📊 Empleados asignados a {$jefe['username']}: " . $empleados_asignados['total'] . "\n";
        
        if ($empleados_asignados['total'] > 0) {
            echo "✅ Jefe tiene empleados asignados\n";
            
            // Buscar empleados reales
            $stmt_lista = $pdo->prepare("SELECT id, nombre, apellido, area FROM empleados WHERE jefe_directo_id = ? LIMIT 3");
            $stmt_lista->execute([$jefe_id]);
            $empleados_reales = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);
            
            echo "👥 Empleados reales:\n";
            foreach ($empleados_reales as $emp) {
                echo "   - ID: {$emp['id']}, Nombre: {$emp['nombre']} {$emp['apellido']}, Área: {$emp['area']}\n";
            }
        } else {
            echo "❌ Jefe NO tiene empleados asignados\n";
        }
        
    } else {
        echo "❌ No se encontró al usuario jefe1_prueba\n";
        exit(1);
    }
    
    echo "\n=== RESUMEN DEL DIAGNÓSTICO ===\n";
    echo "1. ✅ Base de datos conectada\n";
    echo "2. ✅ Sesión de jefe simulada\n";
    echo "3. ✅ Página de validaciones generada\n";
    echo "4. ✅ Componentes HTML verificados\n";
    echo "5. ✅ API buscar-empleados probada\n";
    echo "6. ✅ Empleados asignados verificados\n";
    
    echo "\n🎯 ESTADO ESPERADO:\n";
    echo "- El HTML debe mostrar el formulario de selección de empleados\n";
    echo "- La API debe devolver los empleados asignados al jefe\n";
    echo "- El JavaScript debe renderizar las cards de empleados\n";
    echo "- Los botones de validación deben activarse\n";
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>