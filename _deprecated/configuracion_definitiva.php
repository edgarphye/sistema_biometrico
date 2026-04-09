<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== CONFIGURACIÓN SIMPLE DE JEFES Y EMPLEADOS ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "1. Creando jefes...\n";
    
    // Crear jefes
    $jefes = [
        ['nombre_completo' => 'Jefe Área Ventas', 'username' => 'jefe1'],
        ['nombre_completo' => 'Jefe Área Sistemas', 'username' => 'jefe2'],
        ['nombre_completo' => 'Jefe Área RRHH', 'username' => 'jefe3']
    ];
    
    foreach ($jefes as $jefe) {
        $sql = "INSERT INTO usuarios (username, password, email, nombre_completo, rol, activo) VALUES (?, ?, ?, 'jefe', 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $jefe['username'],
            password_hash('123456', PASSWORD_DEFAULT),
            $jefe['username'] . '@test.com',
            $jefe['nombre_completo']
        ]);
        echo "   ✅ {$jefe['nombre_completo']} creado (ID: {$pdo->lastInsertId()})\n";
    }
    
    echo "\n2. Creando empleados...\n";
    
    $empleados = [
        ['nombre' => 'Carlos', 'apellido' => 'Gómez López', 'area' => 'Ventas', 'jefe_directo_id' => 17],
        ['nombre' => 'Ana', 'apellido' => 'Rodríguez', 'area' => 'Sistemas', 'jefe_directo_id' => 18],
        ['nombre' => 'José Luis', 'apellido' => 'Hernández', 'area' => 'Recursos Humanos', 'jefe_directo_id' => 19],
        ['nombre' => 'Patricia', 'apellido' => 'Mendoza', 'area' => 'Contabilidad', 'jefe_directo_id' => 17],
        ['nombre' => 'Laura', 'apellido' => 'Sánchez', 'area' => 'Ventas', 'jefe_directo_id' => 17],
        ['nombre' => 'Roberto', 'apellido' => 'Díaz', 'area' => 'Producción', 'jefe_directo_id' => 19],
        ['nombre' => 'Miguel', 'apellido' => 'Flores', 'area' => 'Sistemas', 'jefe_directo_id' => 18]
    ];
    
    foreach ($empleados as $empleado) {
        $sql = "INSERT INTO empleados (nombre, apellido, area, jerarquia, activo, jefe_directo_id, 
                                          fecha_nacimiento, sexo, entidad_federativa) 
                    VALUES (?, ?, ?, ?, 1, ?, '1985-01-15', 'H', 'DF')";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $empleado['nombre'],
            $empleado['apellido'],
            $empleado['area'],
            $empleado['jerarquia'],
            $empleado['jefe_directo_id'],
            $empleado['fecha_nacimiento'],
            $empleado['sexo'],
            $empleado['entidad_federativa']
        ]);
        
        if ($resultado) {
            echo "   ✅ {$empleado['nombre']} {$empleado['apellido']} (ID: {$pdo->lastInsertId()})\n";
        }
    }
    
    echo "\n3. Creando incidencias de prueba...\n";
    
    foreach ($empleados as $empleado) {
        for ($i = 1; $i <= 2; $i++) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $minutos = rand(10, 45);
            
            $sql = "INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_retraso, categoria_principal, 
                     requiere_validacion_jefe, estado_validacion) 
                     VALUES (?, ?, 'mayor', 'retardo', 1, 1, 'pendiente')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$empleado['id'], $fecha, $minutos]);
        }
        
        echo "   ✅ {$empleado['nombre']} {$empleado['apellido']}: 2 incidencias creadas\n";
    }
    
    echo "\n✅ Configuración completada exitosamente\n";
    echo "   👥 Jefes creados: 3\n";
    echo "   👥 Empleados creados: 7\n";
    echo "   ⏳ Incidencias creadas: 14\n";
    echo "\n🔐 Credenciales para prueba:\n";
    
    $stmt = $pdo->query("SELECT id, username, nombre_completo FROM usuarios WHERE rol = 'jefe'");
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($jefes as $jefe) {
        echo "   👤 {$jefe['nombre_completo']} | Usuario: {$jefe['username']} | Contraseña: 123456\n";
    }
    
    echo "\n🚀 Para acceder:\n";
    echo "   1. Iniciar sesión con cualquiera de los jefes\n";
    echo "   2. Acceder a: /validaciones\n";
    echo "   3. Verá sus empleados en la sección superior\n";
    echo "   4. Haga clic en los empleados para seleccionarlos y validar\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>