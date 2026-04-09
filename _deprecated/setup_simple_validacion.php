<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== CONFIGURACIÓN FINAL DE JEFES Y EMPLEADOS ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Crear jefes
    echo "1. Creando usuarios jefes...\n";
    
    $jefes = [
        ['username' => 'jefe1', 'nombre_completo' => 'Jefe Ventas'],
        ['username' => 'jefe2', 'nombre_completo' => 'Jefe Sistemas'],
        ['username' => 'jefe3', 'nombre_completo' => 'Jefe RRHH']
    ];
    
    foreach ($jefes as $jefe) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$jefe['username']]);
        
        if (!$stmt->fetch()) {
            $sql = "INSERT INTO usuarios (username, password, email, nombre_completo, rol, activo) VALUES (?, ?, ?, ?, 'jefe', 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $jefe['username'],
                password_hash('123456', PASSWORD_DEFAULT),
                $jefe['username'] . '@test.com',
                $jefe['nombre_completo']
            ]);
            echo "   ✅ Jefe creado: {$jefe['nombre_completo']}\n";
        }
    }
    
    // 2. Asignar jefes a empleados
    echo "\n2. Asignando jefes a empleados...\n";
    
    // Obtener IDs de jefes
    $stmt = $pdo->prepare("SELECT id, nombre_completo FROM usuarios WHERE rol = 'jefe' AND activo = 1 ORDER BY id");
    $stmt->execute();
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM empleados ORDER BY id LIMIT 15");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($empleados as $index => $empleado) {
        $jefe = $jefes[$index % count($jefes)];
        
        $sql = "UPDATE empleados SET jefe_directo_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jefe['id'], $empleado['id']]);
        
        echo "   ✅ {$empleado['nombre']} {$empleado['apellido']} -> {$jefe['nombre_completo']}\n";
    }
    
    // 3. Crear incidencias de prueba
    echo "\n3. Creando incidencias de prueba...\n";
    
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM empleados WHERE jefe_directo_id IS NOT NULL LIMIT 10");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($empleados as $empleado) {
        $fecha = date('Y-m-d', strtotime("-2 days"));
        $minutos = rand(10, 30);
        
        $sql = "INSERT INTO retardos 
                (empleado_id, fecha, minutos_retardo, tipo_retraso, categoria_principal, 
                 hora_entrada, hora_salida, fecha_asistencia, dia_semana, tipo_registro,
                 requiere_validacion_jefe, estado_validacion, motivo_detalle)
                VALUES (?, ?, ?, 'mayor', 'retardo', 
                 '08:00', '17:00', ?, 'lunes', 'entrada', 
                 1, 'pendiente', 'Retardo por prueba')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empleado['id'], $fecha, $minutos]);
        
        echo "   ✅ Incidencia creada para {$empleado['nombre']} {$empleado['apellido']} ({$minutos} min)\n";
    }
    
    echo "\n✅ Configuración completada exitosamente\n";
    echo "📊 Estadísticas finales:\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'jefe'");
    $totalJefes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Jefes activos: $totalJefes\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleados WHERE jefe_directo_id IS NOT NULL");
    $empleadosConJefe = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Empleados con jefe: $empleadosConJefe\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM retardos WHERE estado_validacion = 'pendiente'");
    $incidenciasPendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Incidencias pendientes: $incidenciasPendientes\n";
    
    echo "\n🎯 Credenciales para prueba:\n";
    $stmt = $pdo->query("SELECT id, nombre_completo FROM usuarios WHERE rol = 'jefe' AND activo = 1 ORDER BY id");
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($jefes as $jefe) {
        echo "   👤 Usuario: {$jefe['nombre_completo']} | Contraseña: 123456 | ID: {$jefe['id']}\n";
    }
    
    echo "\n📋 Para probar:\n";
    echo "   1. Inicie sesión como uno de los jefes\n";
    echo "   2. Acceda a: /validaciones\n";
    echo "   3. Verá sus empleados en la sección superior\n";
    echo "   4. Seleccione empleados para validar\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>