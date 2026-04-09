<?php
require_once __DIR__ . '/config.php';

echo "=== CREAR JEFES Y EMPLEADOS DE PRUEBA ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Conectado a la base de datos\n";
    
    // Primero, eliminar datos de prueba existentes para empezar limpio
    echo "\n--- Limpiando datos de prueba anteriores ---\n";
    $pdo->exec("DELETE FROM validaciones_jefe WHERE empleado_id IN (SELECT id FROM empleados WHERE nombre LIKE 'Empleado%Prueba%' OR nombre LIKE 'Jefe%Prueba%')");
    $pdo->exec("DELETE FROM empleados WHERE nombre LIKE 'Empleado%Prueba%' OR nombre LIKE 'Jefe%Prueba%'");
    $pdo->exec("DELETE FROM usuarios WHERE username LIKE 'jefe%' AND username LIKE '%prueba%'");
    echo "✅ Datos de prueba anteriores eliminados\n";
    
    // Crear usuarios jefes
    echo "\n--- Creando usuarios jefes ---\n";
    $jefes_data = [
        ['username' => 'jefe1_prueba', 'password' => 'jefe123', 'nombre' => 'Jefe Departamento Ventas Prueba', 'email' => 'jefe1@prueba.com', 'rol' => 'jefe', 'departamento' => 'Ventas'],
        ['username' => 'jefe2_prueba', 'password' => 'jefe123', 'nombre' => 'Jefe Departamento Ingeniería Prueba', 'email' => 'jefe2@prueba.com', 'rol' => 'jefe', 'departamento' => 'Ingeniería'],
        ['username' => 'jefe3_prueba', 'password' => 'jefe123', 'nombre' => 'Jefe Departamento Recursos Humanos Prueba', 'email' => 'jefe3@prueba.com', 'rol' => 'jefe', 'departamento' => 'Recursos Humanos']
    ];
    
    $jefes_creados = [];
    
    foreach ($jefes_data as $jefe_info) {
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$jefe_info['username']]);
        
        if ($stmt->rowCount() == 0) {
            // Crear contraseña hash
            $password_hash = password_hash($jefe_info['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (username, password, nombre_completo, email, rol, activo, fecha_creacion) 
                VALUES (?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $jefe_info['username'],
                $password_hash,
                $jefe_info['nombre'],
                $jefe_info['email'],
                $jefe_info['rol']
            ]);
            
            $jefe_id = $pdo->lastInsertId();
            $jefes_creados[] = ['id' => $jefe_id, 'info' => $jefe_info];
            echo "✅ Jefe creado: {$jefe_info['nombre']} (ID: $jefe_id)\n";
        } else {
            $jefe_id = $stmt->fetch()['id'];
            $jefes_creados[] = ['id' => $jefe_id, 'info' => $jefe_info];
            echo "ℹ️ Jefe ya existe: {$jefe_info['nombre']} (ID: $jefe_id)\n";
        }
    }
    
    // Crear empleados y asignar jefes
    echo "\n--- Creando empleados ---\n";
    $empleados_data = [
        ['nombre' => 'Empleado Ana García Prueba', 'departamento' => 'Ventas', 'puesto' => 'Vendedor Senior'],
        ['nombre' => 'Empleado Carlos López Prueba', 'departamento' => 'Ventas', 'puesto' => 'Vendedor Junior'],
        ['nombre' => 'Empleado María Rodríguez Prueba', 'departamento' => 'Ventas', 'puesto' => 'Vendedor'],
        ['nombre' => 'Empleado Pedro Martínez Prueba', 'departamento' => 'Ingeniería', 'puesto' => 'Ingeniero Senior'],
        ['nombre' => 'Empleado Laura Sánchez Prueba', 'departamento' => 'Ingeniería', 'puesto' => 'Ingeniero'],
        ['nombre' => 'Empleado Diego Torres Prueba', 'departamento' => 'Ingeniería', 'puesto' => 'Técnico'],
        ['nombre' => 'Empleado Sofía Ramírez Prueba', 'departamento' => 'Ingeniería', 'puesto' => 'Desarrollador'],
        ['nombre' => 'Empleado Roberto Flores Prueba', 'departamento' => 'Recursos Humanos', 'puesto' => 'RRHH Specialist'],
        ['nombre' => 'Empleado Isabel Castro Prueba', 'departamento' => 'Recursos Humanos', 'puesto' => 'Reclutador'],
        ['nombre' => 'Empleado Miguel Herrera Prueba', 'departamento' => 'Recursos Humanos', 'puesto' => 'Analista']
    ];
    
    foreach ($empleados_data as $index => $empleado_info) {
        // Asignar jefe según el departamento
        $jefe_asignado = null;
        foreach ($jefes_creados as $jefe) {
            if ($jefe['info']['departamento'] === $empleado_info['departamento']) {
                $jefe_asignado = $jefe;
                break;
            }
        }
        
        if ($jefe_asignado) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO empleados (nombre, apellido, area, puesto, email, telefono, activo, jefe_directo_id, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())
                ");
                $stmt->execute([
                    $empleado_info['nombre'],
                    'ApellidoPrueba',
                    $empleado_info['departamento'],
                    $empleado_info['puesto'],
                    'emp' . ($index + 1) . '@prueba.com',
                    '555-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    $jefe_asignado['id']
                ]);
                
                $empleado_id = $pdo->lastInsertId();
                echo "✅ Empleado creado: {$empleado_info['nombre']} → Jefe: {$jefe_asignado['info']['nombre']} (ID: $empleado_id)\n";
                
            } catch (Exception $e) {
                echo "❌ Error creando empleado {$empleado_info['nombre']}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠️ No se encontró jefe para el departamento: {$empleado_info['departamento']}\n";
        }
    }
    
    // Crear incidentes de asistencia para validar
    echo "\n--- Creando incidentes para validación ---\n";
    
    // Obtener todos los empleados creados
    $stmt = $pdo->query("SELECT id, nombre, area FROM empleados WHERE nombre LIKE '%Prueba%'");
    $empleados = $stmt->fetchAll();
    
    $incidentes_creados = 0;
    $fecha_hoy = date('Y-m-d');
    $fecha_ayer = date('Y-m-d', strtotime('-1 day'));
    
    foreach ($empleados as $empleado) {
        // Crear incidente de retardo para hoy
        $stmt = $pdo->prepare("
            INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_registro, categoria_principal, 
                                 requiere_validacion_jefe, estado_validacion, hora_entrada, fecha_asistencia, dia_semana)
            VALUES (?, ?, ?, 'retardo_manual', 'retardo', 1, 'pendiente', ?, ?, ?)
        ");
        
        try {
            $stmt->execute([
                $empleado['id'],
                $fecha_hoy,
                rand(10, 45), // Retardo aleatorio entre 10-45 minutos
                '09:' . str_pad(rand(10, 30), 2, '0', STR_PAD_LEFT), // Hora de entrada
                $fecha_hoy,
                date('l', strtotime($fecha_hoy))
            ]);
            
            $incidentes_creados++;
            echo "✅ Incidente creado para: {$empleado['nombre']} (Retardo: " . rand(10, 45) . " min)\n";
            
        } catch (Exception $e) {
            echo "❌ Error creando incidente para {$empleado['nombre']}: " . $e->getMessage() . "\n";
        }
    }
    
    // Crear algunas faltas también
    foreach (array_slice($empleados, 0, 5) as $empleado) {
        $stmt = $pdo->prepare("
            INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_registro, categoria_principal, 
                                 requiere_validacion_jefe, estado_validacion, fecha_asistencia, dia_semana)
            VALUES (?, ?, ?, 'falta', 'falta_injustificada', 1, 'pendiente', ?, ?)
        ");
        
        try {
            $stmt->execute([
                $empleado['id'],
                $fecha_ayer,
                480, // 8 horas = falta completa
                $fecha_ayer,
                date('l', strtotime($fecha_ayer))
            ]);
            
            $incidentes_creados++;
            echo "✅ Falta creada para: {$empleado['nombre']}\n";
            
        } catch (Exception $e) {
            echo "❌ Error creando falta para {$empleado['nombre']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "✅ Jefes creados: " . count($jefes_creados) . "\n";
    echo "✅ Empleados creados: " . count($empleados) . "\n";
    echo "✅ Incidentes creados: $incidentes_creados\n";
    
    echo "\n=== CREDENCIALES PARA PRUEBA ===\n";
    foreach ($jefes_creados as $jefe) {
        echo "Usuario: {$jefe['info']['username']}\n";
        echo "Contraseña: {$jefe['info']['password']}\n";
        echo "Departamento: {$jefe['info']['departamento']}\n";
        echo "---\n";
    }
    
    echo "\n✅ Configuración completada exitosamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>