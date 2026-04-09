<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== CONFIGURACIÓN FINAL DE JEFES Y EMPLEADOS ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "1. Limpiando datos existentes...\n";
    
    $pdo->exec("DELETE FROM retardos");
    $pdo->exec("DELETE FROM validaciones_jefe");
    $pdo->exec("DELETE FROM empleados WHERE 1=1");
    $pdo->exec("DELETE FROM usuarios WHERE rol IN ('jefe', 'supervisor') AND id != 1");
    
    echo "   ✅ Tablas limpiadas\n";
    
    echo "\n2. Creando usuarios jefe...\n";
    
    $jefes = [
        ['username' => 'jefe1', 'nombre_completo' => 'Jefe Área Ventas', 'email' => 'jefe1@test.com'],
        ['username' => 'jefe2', 'nombre_completo' => 'Jefe Área Sistemas', 'email' => 'jefe2@test.com'],
        ['username' => 'jefe3', 'nombre_completo' => 'Jefe Área RRHH', 'email' => 'jefe3@test.com']
    ];
    
    foreach ($jefes as $jefe) {
        $sql = "INSERT INTO usuarios (username, password, email, nombre_completo, rol, activo) VALUES (?, ?, ?, ?, 'jefe', 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $jefe['username'],
            password_hash('123456', PASSWORD_DEFAULT),
            $jefe['email'],
            $jefe['nombre_completo']
        ]);
        echo "   ✅ {$jefe['nombre_completo']} (ID: {$pdo->lastInsertId()})\n";
    }
    
    $stmt = $pdo->query("SELECT id, nombre_completo FROM usuarios WHERE rol = 'jefe' AND activo = 1 ORDER BY id");
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n3. Creando empleados...\n";
    
    $empleados = [
        ['nombre' => 'Carlos', 'apellido' => 'Gómez López', 'area' => 'Ventas'],
        ['nombre' => 'Ana María', 'apellido' => 'Rodríguez Martínez', 'area' => 'Sistemas'],
        ['nombre' => 'José Luis', 'apellido' => 'Hernández Silva', 'area' => 'Recursos Humanos'],
        ['nombre' => 'Patricia', 'apellido' => 'Mendoza Torres', 'area' => 'Ventas'],
        ['nombre' => 'Roberto', 'apellido' => 'Díaz Castro', 'area' => 'Producción'],
        ['nombre' => 'Laura', 'apellido' => 'Sánchez Ramírez', 'area' => 'Sistemas']
    ];
    
    foreach ($empleados as $index => $empleado) {
        $jefeIndex = $index % 3;
        $jefeAsignado = $jefes[$jefeIndex];
        
        $sql = "INSERT INTO empleados (nombre, apellido, area, activo, jefe_directo_id, 
                                          fecha_nacimiento, sexo, entidad_federativa) 
                VALUES (?, ?, ?, 1, ?, '1985-05-15', 'H', 'DF')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $empleado['nombre'],
            $empleado['apellido'],
            $empleado['area'],
            $jefeAsignado['id']
        ]);
        
        echo "   ✅ {$empleado['nombre']} {$empleado['apellido']} -> {$jefeAsignado['nombre_completo']}\n";
    }
    
    echo "\n4. Creando incidencias de prueba...\n";
    
    $stmt = $pdo->query("SELECT id, nombre, apellido, jefe_directo_id FROM empleados ORDER BY id LIMIT 8");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($empleados as $empleado) {
        for ($i = 1; $i <= 2; $i++) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $minutos = rand(10, 45);
            
            $sql = "INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_retraso, categoria_principal, 
                     requiere_validacion_jefe, estado_validacion, motivo_detalle)
                    VALUES (?, ?, 'mayor', 'retardo', 1, 1, 'Retardo de prueba')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $empleado['id'],
                $fecha,
                $minutos
            ]);
            
            echo "   ✅ {$empleado['nombre']} {$empleado['apellido']}: 2 incidencias creadas\n";
        }
    }
    
    echo "\n5. Verificando configuración...\n";
    
    $totalJefes = count($jefes);
    $empleadosConJefe = $pdo->query("SELECT COUNT(*) as total FROM empleados WHERE jefe_directo_id IS NOT NULL")->fetch(PDO::FETCH_ASSOC)['total'];
    $incidenciasPendientes = $pdo->query("SELECT COUNT(*) as total FROM retardos WHERE estado_validacion = 'pendiente'")->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n📊 ESTADÍSTICAS FINALES:\n";
    echo "   👥 Jefes activos: $totalJefes\n";
    echo "   👥 Empleados con jefe: $empleadosConJefe\n";
    echo "   ⏳ Incidencias pendientes: $incidenciasPendientes\n";
    
    echo "\n🎯 CREDENCIALES PARA ACCESO:\n";
    foreach ($jefes as $jefe) {
        echo "   👤 {$jefe['nombre_completo']}\n";
        echo "      Usuario: {$jefe['username']}\n";
        echo "      Contraseña: 123456\n";
        echo "      ID: {$jefe['id']}\n";
        echo "      ───────────────────────\n";
    }
    
    echo "\n✅ CONFIGURACIÓN COMPLETADA\n";
    echo "🚀 Sistema de validación por jefes listo para prueba\n";
    
    echo "\n📋 PASOS PARA PROBAR:\n";
    echo "   1. Iniciar sesión con: jefe1, jefe2 o jefe3 (contraseña: 123456)\n";
    echo "   2. Acceder a: /validaciones\n";
    echo "   3. Verá lista de empleados en cards interactivos\n";
    echo "   4. Haga clic en empleados para ver sus incidencias y validar\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>