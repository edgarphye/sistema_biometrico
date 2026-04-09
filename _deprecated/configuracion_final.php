<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== RESETEO Y CONFIGURACIÓN LIMPIA ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "1. Limpiando datos existentes...\n";
    
    // Limpiar todas las tablas relacionadas
    $pdo->exec("DELETE FROM retardos");
    $pdo->exec("DELETE FROM validaciones_jefe");
    $pdo->exec("DELETE FROM empleados WHERE 1=1");
    $pdo->exec("DELETE FROM usuarios WHERE rol IN ('jefe', 'supervisor') AND id != 1");
    
    echo "   ✅ Tablas limpiadas\n";
    
    echo "\n2. Creando usuarios jefe...\n";
    
    // Crear usuarios jefe
    $jefes = [
        ['username' => 'jefe1', 'nombre_completo' => 'Jefe Área Ventas', 'email' => 'jefe1@test.com'],
        ['username' => 'jefe2', 'nombre_completo' => 'Jefe Área Sistemas', 'email' => 'jefe2@test.com'],
        ['username' => 'jefe3', 'nombre_completo' => 'Jefe Área RRHH', 'email' => 'jefe3@test.com']
    ];
    
    foreach ($jefes as $jefe) {
        $sql = "INSERT INTO usuarios (username, password, email, nombre_completo, rol, activo) 
                VALUES (?, ?, ?, ?, 'jefe', 1)";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $jefe['username'],
            password_hash('123456', PASSWORD_DEFAULT),
            $jefe['email'],
            $jefe['nombre_completo']
        ]);
        
        if ($resultado) {
            echo "   ✅ {$jefe['nombre_completo']} (ID: {$pdo->lastInsertId()})\n";
        }
    }
    
    // Obtener IDs de jefes
    $stmt = $pdo->query("SELECT id, nombre_completo FROM usuarios WHERE rol = 'jefe' AND activo = 1 ORDER BY id");
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n3. Creando empleados...\n";
    
    $empleados = [
        ['nombre' => 'Carlos', 'apellido' => 'Gómez López', 'area' => 'Ventas', 'jerarquia' => 'Analista'],
        ['nombre' => 'Ana María', 'apellido' => 'Rodríguez Martínez', 'area' => 'Sistemas', 'jerarquia' => 'Coordinador'],
        ['nombre' => 'José Luis', 'apellido' => 'Hernández Silva', 'area' => 'Recursos Humanos', 'jerarquia' => 'Especialista'],
        ['nombre' => 'Patricia', 'apellido' => 'Mendoza Torres', 'area' => 'Ventas', 'jerarquia' => 'Analista'],
        ['nombre' => 'Roberto', 'apellido' => 'Díaz Castro', 'area' => 'Producción', 'jerarquia' => 'Técnico'],
        ['nombre' => 'Laura', 'apellido' => 'Sánchez Ramírez', 'area' => 'Sistemas', 'jerarquia' => 'Analista'],
        ['nombre' => 'Miguel Ángel', 'apellido' => 'Flores Vargas', 'area' => 'Recursos Humanos', 'jerarquia' => 'Coordinador'],
        ['nombre' => 'Carmen', 'apellido' => 'Reyes Morales', 'area' => 'Contabilidad', 'jerarquia' => 'Supervisor']
    ];
    
    foreach ($empleados as $empleado) {
        $jefeAsignado = $jefes[rand(0, count($jefes) - 1)];
        
        $sql = "INSERT INTO empleados (nombre, apellido, area, jerarquia, activo, jefe_directo_id, 
                                          fecha_nacimiento, sexo, entidad_federativa) 
                VALUES (?, ?, ?, ?, 1, ?, 
                        '1985-05-15', ?, 'DF')";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $empleado['nombre'],
            $empleado['apellido'],
            $empleado['area'],
            $empleado['jerarquia'],
            $jefeAsignado['id'],
            $empleado['sexo'] = 'H',
            $empleado['entidad_federativa'] = 'DF'
        ]);
        
        if ($resultado) {
            $empleadoId = $pdo->lastInsertId();
            echo "   ✅ {$empleado['nombre']} {$empleado['apellido']} -> {$jefeAsignado['nombre_completo']} (ID: $empleadoId)\n";
        }
    }
    
    echo "\n4. Creando incidencias de prueba...\n";
    
    // Obtener empleados creados
    $stmt = $pdo->query("SELECT id, nombre, apellido FROM empleados ORDER BY id LIMIT 8");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($empleados as $empleado) {
        // Crear 2-3 incidencias por empleado
        for ($i = 1; $i <= 2; $i++) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $minutos = rand(10, 45);
            
            $sql = "INSERT INTO retardos 
                    (empleado_id, fecha, minutos_retardo, tipo_retraso, categoria_principal, 
                     hora_entrada, hora_salida, fecha_asistencia, dia_semana, tipo_registro,
                     requiere_validacion_jefe, estado_validacion, motivo_detalle)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $empleado['id'],
                $fecha,
                $minutos,
                $minutos <= 15 ? 'menor' : 'mayor',
                'retardo',
                '08:00',
                '17:00',
                $fecha,
                date('l', strtotime($fecha)),
                'entrada',
                $minutos > 15 ? 1 : 0,
                'pendiente',
                'Retardo de prueba #' . $i
            ]);
        }
    }
    
    echo "\n5. Verificando configuración...\n";
    
    // Verificar estadísticas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'jefe'");
    $totalJefes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleados WHERE jefe_directo_id IS NOT NULL");
    $empleadosConJefe = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM retardos WHERE estado_validacion = 'pendiente'");
    $incidenciasPendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
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
    
    echo "\n✅ CONFIGURACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "🚀 Sistema de validación por jefes listo para prueba\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>