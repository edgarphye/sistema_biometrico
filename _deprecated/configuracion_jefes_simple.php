<?php
require_once __DIR__ . '/config.php';

echo "=== CONFIGURACIÓN JEFES Y EMPLEADOS SIMPLIFICADA ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Conectado a la base de datos\n";
    
    // Primero crear empleados jefes en la tabla empleados
    echo "\n--- Creando empleados jefes ---\n";
    
    $jefes_empleados_data = [
        ['nombre' => 'Jefe Ventas Prueba', 'apellido' => 'JefeApell', 'area' => 'Ventas', 'puesto' => 'Jefe de Ventas', 'username' => 'jefe1_prueba'],
        ['nombre' => 'Jefe Ingeniería Prueba', 'apellido' => 'JefeApell', 'area' => 'Ingeniería', 'puesto' => 'Jefe de Ingeniería', 'username' => 'jefe2_prueba'],
        ['nombre' => 'Jefe RRHH Prueba', 'apellido' => 'JefeApell', 'area' => 'Recursos Humanos', 'puesto' => 'Jefe de RRHH', 'username' => 'jefe3_prueba']
    ];
    
    $jefes_ids = [];
    
    foreach ($jefes_empleados_data as $jefe_info) {
        // Primero crear el empleado
        $stmt = $pdo->prepare("
            INSERT INTO empleados (nombre, apellido, area, puesto, email, telefono, activo, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $jefe_info['nombre'],
            $jefe_info['apellido'],
            $jefe_info['area'],
            $jefe_info['puesto'],
            $jefe_info['username'] . '@empresa.com',
            '555-0000'
        ]);
        
        $empleado_id = $pdo->lastInsertId();
        $jefes_ids[$jefe_info['username']] = $empleado_id;
        
        echo "✅ Empleado jefe creado: {$jefe_info['nombre']} (Empleado ID: $empleado_id)\n";
        
        // Luego crear el usuario asociado
        $password_hash = password_hash('jefe123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (username, password, nombre_completo, email, rol, empleado_id, activo, fecha_creacion) 
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $jefe_info['username'],
            $password_hash,
            $jefe_info['nombre'],
            $jefe_info['username'] . '@empresa.com',
            'jefe',
            $empleado_id
        ]);
        
        $usuario_id = $pdo->lastInsertId();
        echo "✅ Usuario jefe creado: {$jefe_info['username']} (Usuario ID: $usuario_id)\n";
    }
    
    // Ahora crear empleados regulares asignados a los jefes
    echo "\n--- Creando empleados regulares ---\n";
    
    $empleados_data = [
        ['nombre' => 'Ana García', 'apellido' => 'García', 'area' => 'Ventas', 'puesto' => 'Vendedora'],
        ['nombre' => 'Carlos López', 'apellido' => 'López', 'area' => 'Ventas', 'puesto' => 'Vendedor'],
        ['nombre' => 'María Rodríguez', 'apellido' => 'Rodríguez', 'area' => 'Ventas', 'puesto' => 'Vendedora'],
        ['nombre' => 'Pedro Martínez', 'apellido' => 'Martínez', 'area' => 'Ingeniería', 'puesto' => 'Ingeniero'],
        ['nombre' => 'Laura Sánchez', 'apellido' => 'Sánchez', 'area' => 'Ingeniería', 'puesto' => 'Ingeniera'],
        ['nombre' => 'Diego Torres', 'apellido' => 'Torres', 'area' => 'Ingeniería', 'puesto' => 'Técnico'],
        ['nombre' => 'Sofía Ramírez', 'apellido' => 'Ramírez', 'area' => 'Ingeniería', 'puesto' => 'Desarrolladora'],
        ['nombre' => 'Roberto Flores', 'apellido' => 'Flores', 'area' => 'Recursos Humanos', 'puesto' => 'Especialista RRHH'],
        ['nombre' => 'Isabel Castro', 'apellido' => 'Castro', 'area' => 'Recursos Humanos', 'puesto' => 'Reclutadora'],
        ['nombre' => 'Miguel Herrera', 'apellido' => 'Herrera', 'area' => 'Recursos Humanos', 'puesto' => 'Analista']
    ];
    
    foreach ($empleados_data as $index => $empleado_info) {
        // Determinar qué jefe corresponde según el área
        $jefe_username = '';
        switch ($empleado_info['area']) {
            case 'Ventas':
                $jefe_username = 'jefe1_prueba';
                break;
            case 'Ingeniería':
                $jefe_username = 'jefe2_prueba';
                break;
            case 'Recursos Humanos':
                $jefe_username = 'jefe3_prueba';
                break;
        }
        
        $jefe_id = $jefes_ids[$jefe_username] ?? null;
        
        if ($jefe_id) {
            $stmt = $pdo->prepare("
                INSERT INTO empleados (nombre, apellido, area, puesto, email, telefono, activo, jefe_directo_id, fecha_registro) 
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())
            ");
            $stmt->execute([
                $empleado_info['nombre'],
                $empleado_info['apellido'],
                $empleado_info['area'],
                $empleado_info['puesto'],
                strtolower(str_replace(' ', '', $empleado_info['nombre'])) . '@empresa.com',
                '555-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                $jefe_id
            ]);
            
            $empleado_id = $pdo->lastInsertId();
            echo "✅ Empleado creado: {$empleado_info['nombre']} → Jefe: $jefe_username (ID: $empleado_id)\n";
        }
    }
    
    echo "\n--- Creando incidentes de prueba ---\n";
    
    // Obtener todos los empleados excepto los jefes
    $stmt = $pdo->query("SELECT id, nombre, area FROM empleados WHERE nombre NOT LIKE '%Jefe%' AND activo = 1");
    $empleados = $stmt->fetchAll();
    
    $incidentes_creados = 0;
    $fecha_hoy = date('Y-m-d');
    $dias_semana = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    
    foreach ($empleados as $empleado) {
        // Crear un retardo para el empleado
        try {
            $minutos_retardo = rand(5, 60);
            $hora_entrada = '09:' . str_pad(rand(5, 45), 2, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_registro, categoria_principal, 
                                     requiere_validacion_jefe, estado_validacion, hora_entrada, fecha_asistencia, dia_semana)
                VALUES (?, ?, ?, 'retardo_manual', 'retardo', 1, 'pendiente', ?, ?, ?)
            ");
            
            $stmt->execute([
                $empleado['id'],
                $fecha_hoy,
                $minutos_retardo,
                $hora_entrada,
                $fecha_hoy,
                $dias_semana[array_rand($dias_semana)]
            ]);
            
            $incidentes_creados++;
            echo "✅ Retardo creado: {$empleado['nombre']} - {$minutos_retardo} minutos\n";
            
        } catch (Exception $e) {
            echo "❌ Error creando incidente para {$empleado['nombre']}: " . $e->getMessage() . "\n";
        }
    }
    
    // Crear algunas faltas también
    foreach (array_slice($empleados, 0, 3) as $empleado) {
        try {
            $fecha_falta = date('Y-m-d', strtotime('-2 days'));
            
            $stmt = $pdo->prepare("
                INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_registro, categoria_principal, 
                                     requiere_validacion_jefe, estado_validacion, fecha_asistencia, dia_semana)
                VALUES (?, ?, ?, 'falta', 'falta_injustificada', 1, 'pendiente', ?, ?)
            ");
            
            $stmt->execute([
                $empleado['id'],
                $fecha_falta,
                480, // 8 horas = falta completa
                $fecha_falta,
                date('l', strtotime($fecha_falta))
            ]);
            
            $incidentes_creados++;
            echo "✅ Falta creada: {$empleado['nombre']}\n";
            
        } catch (Exception $e) {
            echo "❌ Error creando falta para {$empleado['nombre']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== RESUMEN FINAL ===\n";
    echo "✅ Jefes (empleados+usuarios): " . count($jefes_ids) . "\n";
    echo "✅ Empleados regulares: " . count($empleados) . "\n";
    echo "✅ Incidentes creados: $incidentes_creados\n";
    
    echo "\n=== CREDENCIALES DE ACCESO ===\n";
    echo "Los siguientes usuarios pueden acceder al sistema:\n\n";
    
    foreach ($jefes_empleados_data as $jefe_info) {
        echo "👤 {$jefe_info['nombre']}\n";
        echo "   Usuario: {$jefe_info['username']}\n";
        echo "   Contraseña: jefe123\n";
        echo "   Departamento: {$jefe_info['area']}\n";
        echo "   ---\n";
    }
    
    echo "\n✅ Configuración completada exitosamente\n";
    echo "📋 Ahora puedes:\n";
    echo "   1. Iniciar sesión como cualquiera de los jefes\n";
    echo "   2. Ir a la opción 'Validaciones' en el menú\n";
    echo "   3. Ver los empleados asignados y sus incidentes pendientes\n";
    echo "   4. Procesar las validaciones (aprobar/rechazar)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>