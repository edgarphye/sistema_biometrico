<?php
/**
 * Script para asignar jefes a empleados de prueba
 * Esto permitirá que el sistema de validación funcione correctamente
 */

require_once 'config.php';
require_once 'models/Database.php';

echo "=== ASIGNANDO JEFES A EMPLEADOS DE PRUEBA ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Verificar si hay usuarios con rol de jefe
    echo "1. Buscando usuarios con rol de jefe...\n";
    
    $sql = "SELECT id, username, nombre_completo 
            FROM usuarios 
            WHERE rol IN ('jefe', 'admin', 'supervisor') 
            AND activo = 1 
            ORDER BY id 
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($jefes)) {
        echo "   ⚠️  No se encontraron usuarios con rol de jefe. Creando jefes de prueba...\n";
        
        // Crear jefes de prueba
        $jefesPrueba = [
            ['username' => 'jefe1', 'nombre_completo' => 'Juan Pérez Jefe'],
            ['username' => 'jefe2', 'nombre_completo' => 'María García Jefa'],
            ['username' => 'jefe3', 'nombre_completo' => 'Carlos López Jefe']
        ];
        
        foreach ($jefesPrueba as $jefeData) {
            $sql = "INSERT INTO usuarios (username, password, email, nombre_completo, rol, activo) 
                    VALUES (?, ?, ?, ?, 'jefe', 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $jefeData['username'],
                password_hash('123456', PASSWORD_DEFAULT), // Contraseña temporal
                $jefeData['username'] . '@test.com',
                $jefeData['nombre_completo']
            ]);
            
            $jefeId = $pdo->lastInsertId();
            echo "   ✅ Jefe creado: {$jefeData['nombre_completo']} (ID: $jefeId)\n";
        }
        
        // Volver a obtener jefes
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "   ✅ Jefes encontrados: " . count($jefes) . "\n";
    
    foreach ($jefes as $jefe) {
        echo "      - {$jefe['nombre_completo']} (ID: {$jefe['id']})\n";
    }
    
    // 2. Obtener empleados que no tienen jefe asignado
    echo "\n2. Buscando empleados sin jefe asignado...\n";
    
    $sql = "SELECT id, nombre, apellido, area, jerarquia 
            FROM empleados 
            WHERE jefe_directo_id IS NULL 
            ORDER BY RAND() 
            LIMIT 15";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $empleadosSinJefe = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   ✅ Empleados sin jefe: " . count($empleadosSinJefe) . "\n";
    
    // 3. Asignar jefes a los empleados
    echo "\n3. Asignando jefes a empleados...\n";
    
    $asignaciones = 0;
    foreach ($empleadosSinJefe as $index => $empleado) {
        $jefeAsignado = $jefes[$index % count($jefes)];
        
        $sql = "UPDATE empleados SET jefe_directo_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([$jefeAsignado['id'], $empleado['id']]);
        
        if ($resultado) {
            $asignaciones++;
            echo "   ✅ {$empleado['nombre']} {$empleado['apellido']} -> {$jefeAsignado['nombre_completo']}\n";
        }
    }
    
    echo "\n📊 Resultado de asignaciones:\n";
    echo "   Total jefes: " . count($jefes) . "\n";
    echo "   Empleados asignados: $asignaciones\n";
    
    // 4. Crear algunas incidencias de prueba
    echo "\n4. Creando incidencias de prueba...\n";
    
    // Obtener algunos empleados con jefe asignado
    $sql = "SELECT e.id as empleado_id, e.nombre, e.apellido, e.jefe_directo_id
            FROM empleados e
            WHERE e.jefe_directo_id IS NOT NULL
            ORDER BY RAND() 
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $empleadosConJefe = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $incidenciasCreadas = 0;
    foreach ($empleadosConJefe as $empleado) {
        // Crear un retardo de prueba
        $fecha = date('Y-m-d', strtotime("-" . rand(1, 5) . " days"));
        $minutosRetardo = rand(10, 60);
        
        $sql = "INSERT INTO retardos 
                (empleado_id, fecha, minutos_retardo, tipo_retraso, categoria_principal, 
                 requiere_validacion_jefe, estado_validacion)
                VALUES (?, ?, ?, 'mayor', 'retardo', ?, 'pendiente')";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $empleado['empleado_id'],
            $fecha,
            $minutosRetardo,
            $minutosRetardo > 30 ? 1 : 0
        ]);
        
        if ($resultado) {
            $incidenciasCreadas++;
            echo "   ✅ Retardo creado: {$empleado['nombre']} {$empleado['apellido']} ({$minutosRetardo} min)\n";
        }
    }
    
    echo "\n📈 Resumen final:\n";
    echo "   ✅ Jefes disponibles: " . count($jefes) . "\n";
    echo "   ✅ Empleados con jefe asignado: $asignaciones\n";
    echo "   ✅ Incidencias de prueba creadas: $incidenciasCreadas\n";
    
    // 5. Estadísticas finales
    echo "\n5. Verificando estructura final...\n";
    
    // Contar empleados con jefe
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleados WHERE jefe_directo_id IS NOT NULL");
    $empleadosConJefe = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar incidencias pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vista_validacion_completa WHERE estado_validacion_jefe = 'pendiente'");
    $incidenciasPendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "   👥 Empleados con jefe: $empleadosConJefe\n";
    echo "   ⏳ Incidencias pendientes: $incidenciasPendientes\n";
    
    echo "\n🎉 Configuración completada exitosamente\n";
    echo "\n📋 Para probar el sistema:\n";
    echo "   1. Inicie sesión como uno de los jefes:\n";
    foreach ($jefes as $jefe) {
        echo "      - Usuario: {$jefe['username']} | Contraseña: 123456\n";
    }
    echo "   2. Acceda a: /validaciones\n";
    echo "   3. Verá la lista de empleados a su cargo\n";
    echo "   4. Seleccione los empleados para validar\n";
    echo "   5. Procese las incidencias pendientes\n";
    
} catch (Exception $e) {
    echo "❌ Error en la configuración: " . $e->getMessage() . "\n";
}
?>