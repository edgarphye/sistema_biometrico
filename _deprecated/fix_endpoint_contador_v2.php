<?php
// Agregar endpoint faltante para contador de validaciones

echo "=== 🔌 AGREGANDO ENDPOINT FALTANTE ===\n\n";

// Ruta al archivo de rutas
$routes_file = __DIR__ . '/routes.php';

if (!file_exists($routes_file)) {
    echo "❌ Error: Archivo routes.php no encontrado\n";
    exit(1);
}

echo "1. Analizando routes.php actual...\n";

// Leer el contenido actual
$contenido_actual = file_get_contents($routes_file);

// Verificar si el endpoint ya existe
$endpoint_buscado = "obtener-contador-pendientes";
if (strpos($contenido_actual, $endpoint_buscado) !== false) {
    echo "   ⚠ Endpoint '$endpoint_buscado' ya existe\n";
} else {
    echo "   ✓ Endpoint '$endpoint_buscado' no existe, se agregará\n";
}

// Buscar el patrón para agregar el nuevo endpoint
$posicion_insercion = strpos($contenido_actual, "// Endpoint para validar");
if ($posicion_insercion === false) {
    echo "   ⚠ No se encontró una buena posición para insertar el endpoint\n";
    exit(1);
}

// Nuevo endpoint a agregar
$nuevo_endpoint = <<<'PHP'
        // Endpoint para obtener contador de validaciones pendientes
        $r->addRoute('GET', '/validaciones/obtener-contador-pendientes', ['ValidacionJefeController', 'obtenerContadorPendientes']);
PHP;

// Insertar el nuevo endpoint antes de los validaciones existentes
$contenido_modificado = substr($contenido_actual, 0, $posicion_insercion) . 
                      $nuevo_endpoint . 
                      substr($contenido_actual, $posicion_insercion);

// Hacer backup del archivo original
$backup_file = $routes_file . '.backup_' . date('Y-m-d_H-i-s');
copy($routes_file, $backup_file);
echo "2. Backup creado: $backup_file\n";

// Escribir el contenido modificado
if (file_put_contents($routes_file, $contenido_modificado)) {
    echo "3. ✓ Endpoint agregado exitosamente\n";
} else {
    echo "3. ❌ Error al escribir en routes.php\n";
    exit(1);
}

// 4. Verificar si el controller existe
$controller_file = __DIR__ . '/controllers/ValidacionJefeController.php';
if (file_exists($controller_file)) {
    echo "4. ✓ ValidacionJefeController.php encontrado\n";
    
    // Verificar si el método obtenerContadorPendientes existe
    $contenido_controller = file_get_contents($controller_file);
    if (strpos($contenido_controller, 'function obtenerContadorPendientes') === false) {
        echo "5. Agregando método obtenerContadorPendientes al controller...\n";
        
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
        
        // Encontrar la posición para insertar el método
        $posicion_clase = strrpos($contenido_controller, '}');
        if ($posicion_clase !== false) {
            $contenido_controller_modificado = substr($contenido_controller, 0, $posicion_clase) . 
                                              $nuevo_metodo . "\n" . 
                                              substr($contenido_controller, $posicion_clase);
            
            $backup_controller = $controller_file . '.backup_' . date('Y-m-d_H-i-s');
            copy($controller_file, $backup_controller);
            
            file_put_contents($controller_file, $contenido_controller_modificado);
            echo "   ✓ Método agregado al controller\n";
        }
    } else {
        echo "5. ✓ Método obtenerContadorPendientes ya existe\n";
    }
} else {
    echo "4. ❌ ValidacionJefeController.php no encontrado\n";
}

// 6. Verificar todos los endpoints de validaciones
echo "\n6. Verificando endpoints de validaciones en routes.php:\n";
$lines = file($routes_file);
foreach ($lines as $line_num => $line) {
    if (strpos($line, '/validaciones/') !== false && strpos($line, 'addRoute') !== false) {
        echo "   Línea " . ($line_num + 1) . ": " . trim($line) . "\n";
    }
}

echo "\n✅ ENDPOINT AGREGADO CORRECTAMENTE\n";
echo "📋 ACCIONES MANUALES REQUERIDAS:\n";
echo "   1. Reiniciar servidor web\n";
echo "   2. Probar endpoint: curl http://localhost/biometrico/validaciones/obtener-contador-pendientes\n";
echo "   3. Verificar que el contador_menu.js ya no muestre errores\n";
?>