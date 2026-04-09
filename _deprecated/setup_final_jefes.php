<?php
/**
 * Script definitivo para crear jefes y asignar empleados
 */

require_once 'config.php';
require_once 'models/Database.php';

echo "=== CONFIGURACIÓN DEFINITIVA DE JEFES Y EMPLEADOS ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Limpiar datos existentes
    echo "1. Limpiando datos existentes...\n";
    
    // Desactivar temporalmente claves foráneas
    $pdo->exec("SET foreign_key_checks = 0");
    
    // Eliminar jefes duplicados o problemáticos
    $pdo->exec("DELETE FROM usuarios WHERE rol IN ('jefe', 'supervisor') AND nombre_completo = ''");
    
    // Asignar jefe a todos los empleados sin jefe
    $jefeAdminId = 1; // Asumir que el admin tiene ID 1
    
    // 2. Crear usuarios jefe si no existen
    echo "\n2. Creando usuarios jefe...\n";
    
    $jefesDisponibles = [
        ['username' => 'jefeventas', 'nombre_completo' => 'Jefe Ventas'],
        ['username' => 'jefesistemas', 'nombre_completo' => 'Jefe Sistemas'],
        ['username' => 'jeferrhh', 'nombre_completo' => 'Jefe Recursos Humanos']
    ];
    
    $jefesCreados = [];
    foreach ($jefesDisponibles as $jefeData) {
        // Verificar si ya existe
        $sql = "SELECT id FROM usuarios WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jefeData['username']]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existente) {
            $sql = "INSERT INTO usuarios (username, password, email, nombre_completo, rol, activo) 
                    VALUES (?, ?, ?, ?, 'jefe', 1)";
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                $jefeData['username'],
                password_hash('123456', PASSWORD_DEFAULT),
                $jefeData['username'] . '@test.com',
                $jefeData['nombre_completo']
            ]);
            
            if ($resultado) {
                $jefeId = $pdo->lastInsertId();
                $jefesCreados[] = ['id' => $jefeId, 'nombre' => $jefeData['nombre_completo']];
                echo "   ✅ Jefe creado: {$jefeData['nombre_completo']} (ID: $jefeId)\n";
            }
        } else {
            $jefesCreados[] = ['id' => $existente['id'], 'nombre' => $jefeData['nombre_completo']];
            echo "   ℹ️  Jefe ya existe: {$jefeData['nombre_completo']} (ID: {$existente['id']})\n";
        }
    }
    
    echo "   📊 Total jefes disponibles: " . count($jefesCreados) . "\n";
    
    // 3. Actualizar empleados para que tengan jefe
    echo "\n3. Asignando jefes a empleados...\n";
    
    // Obtener todos los empleados
    $sql = "SELECT id, nombre, apellido, area FROM empleados ORDER BY id LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $asignacionesRealizadas = 0;
    foreach ($empleados as $index => $empleado) {
        // Asignar jefe según el área
        $jefeAsignado = $jefesCreados[0]; // Por ahora, todos al primer jefe
        
        if (count($jefesCreados) > 1) {
            // Distribuir según el índice
            $jefeAsignado = $jefesCreados[$index % count($jefesCreados)];
        }
        
        $sql = "UPDATE empleados SET jefe_directo_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([$jefeAsignado['id'], $empleado['id']]);
        
        if ($resultado) {
            $asignacionesRealizadas++;
            echo "   ✅ {$empleado['nombre']} {$empleado['apellido']} -> {$jefeAsignado['nombre']}\n";
        }
    }
    
    // 4. Crear incidencias de prueba para validación
    echo "\n4. Creando incidencias de prueba...\n";
    
    // Crear retardos para algunos empleados
    $empleadosParaIncidencias = array_slice($empleados, 0, min(10, count($empleados)));
    $incidenciasCreadas = 0;
    
    foreach ($empleadosParaIncidencias as $empleado) {
        // Crear 2-3 incidencias por empleado
        $numIncidencias = rand(2, 3);
        
        for ($i = 0; $i < $numIncidencias; $i++) {
            $fecha = date('Y-m-d', strtotime("-" . ($i + 2) . " days"));
            $minutosRetardo = rand(10, 45);
            
            $sql = "INSERT INTO retardos 
                    (empleado_id, fecha, minutos_retardo, tipo_retraso, categoria_principal, 
                     hora_entrada, hora_salida, fecha_asistencia, dia_semana, tipo_registro,
                     hora_registro, motivo_detalle, requiere_validacion_jefe, estado_validacion, 
                     justificado, horario_id, motivo_justificacion, aprobado_por)
                    VALUES (?, ?, ?, ?, 'retardo', ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $datos = [
                $empleado['id'],
                $fecha,
                $minutosRetardo,
                $minutosRetardo <= 15 ? 'menor' : ($minutosRetardo <= 30 ? 'medio' : 'mayor'),
                '08:' . str_pad(rand(10, 30), 2, '0', STR_PAD_LEFT) . ':00',
                '17:' . str_pad(rand(30, 50), 2, '0', STR_PAD_LEFT) . ':00',
                $fecha,
                date('l', strtotime($fecha)),
                'entrada',
                date('H:i:s'),
                'Retardo por ' . ($minutosRetardo <= 15 ? 'tráfico' : ($minutosRetardo <= 30 ? 'reunión' : 'emergencia')),
                ($minutosRetardo > 15 ? 1 : 0),
                ($minutosRetardo > 15 ? 'pendiente' : 'aprobado'),
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null
            ];

            $resultado = $stmt->execute($datos);
            
            if ($resultado) {
                $incidenciasCreadas++;
            }
        }
    }
    
    // 5. Verificar configuración final
    echo "\n5. Verificando configuración final...\n";
    
    // Contar jefes
    $sql = "SELECT COUNT(*) as total FROM usuarios WHERE rol IN ('jefe', 'supervisor') AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $totalJefes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar empleados con jefe
    $sql = "SELECT COUNT(*) as total FROM empleados WHERE jefe_directo_id IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $empleadosConJefe = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar incidencias pendientes
    $sql = "SELECT COUNT(*) as total FROM retardos WHERE estado_validacion = 'pendiente'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $incidenciasPendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Reactivar claves foráneas
    $pdo->exec("SET foreign_key_checks = 1");
    
    echo "\n📊 ESTADÍSTICAS FINALES:\n";
    echo "   👥 Jefes activos: $totalJefes\n";
    echo "   👥 Empleados con jefe: $empleadosConJefe\n";
    echo "   ⏳ Incidencias pendientes: $incidenciasPendientes\n";
    echo "   📋 Asignaciones realizadas: $asignacionesRealizadas\n";
    echo "   📋 Incidencias creadas: $incidenciasCreadas\n";
    
    // 6. Credenciales para prueba
    echo "\n🔑 CREDENCIALES PARA PRUEBA:\n";
    foreach ($jefesCreados as $jefe) {
        echo "   👤 Usuario: {$jefe['nombre']}\n";
        echo "   🔑 Contraseña: 123456\n";
        echo "   🏢 Rol: Jefe\n";
        echo "   📋 ID: {$jefe['id']}\n";
        echo "   ────────────────────────\n";
    }
    
    echo "\n🎯 INSTRUCCIONES DE USO:\n";
    echo "   1. Inicie sesión con cualquiera de los jefes listados arriba\n";
    echo "   2. Acceda a: /validaciones\n";
    echo "   3. Verá sus empleados en la sección superior\n";
    echo "   4. Haga clic en los empleados para ver sus incidencias\n";
    echo "   5. Procese las validaciones individualmente o masivamente\n";
    
    echo "\n✅ CONFIGURACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "🚀 El sistema de validación por jefes está listo para uso\n";
    
} catch (Exception $e) {
    echo "❌ Error en la configuración: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Reactivar claves foráneas en caso de error
    try {
        $pdo->exec("SET foreign_key_checks = 1");
    } catch (Exception $e) {
        // Ignorar error
    }
}
?>