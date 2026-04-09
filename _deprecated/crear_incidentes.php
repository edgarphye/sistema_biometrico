<?php
require_once __DIR__ . '/config.php';

echo "=== CREAR INCIDENTES DE PRUEBA CORRECTAMENTE ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Conectado a la base de datos\n";
    
    // Limpiar incidentes anteriores
    echo "\n--- Limpiando incidentes anteriores ---\n";
    $pdo->exec("DELETE FROM retardos WHERE empleado_id IN (SELECT id FROM empleados WHERE nombre NOT LIKE '%Jefe%Prueba')");
    echo "✅ Incidentes anteriores eliminados\n";
    
    // Obtener todos los empleados excepto los jefes
    $stmt = $pdo->query("SELECT id, nombre, area FROM empleados WHERE nombre NOT LIKE '%Jefe%Prueba' AND activo = 1");
    $empleados = $stmt->fetchAll();
    
    echo "✅ Encontrados " . count($empleados) . " empleados regulares\n";
    
    $incidentes_creados = 0;
    $fecha_hoy = date('Y-m-d');
    $fecha_ayer = date('Y-m-d', strtotime('-1 day'));
    
    // Días de la semana en español para el enum
    $dias_espanol = [
        'Monday' => 'lunes',
        'Tuesday' => 'martes', 
        'Wednesday' => 'miércoles',
        'Thursday' => 'jueves',
        'Friday' => 'viernes',
        'Saturday' => 'sábado',
        'Sunday' => 'domingo'
    ];
    
    foreach ($empleados as $empleado) {
        // Crear un retardo para hoy
        try {
            $minutos_retardo = rand(5, 60);
            $hora_entrada = '09:' . str_pad(rand(5, 45), 2, '0', STR_PAD_LEFT);
            $dia_semana = $dias_espanol[date('l', strtotime($fecha_hoy))];
            
            $stmt = $pdo->prepare("
                INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_registro, categoria_principal, 
                                     requiere_validacion_jefe, estado_validacion, hora_entrada, fecha_asistencia, dia_semana)
                VALUES (?, ?, ?, 'entrada', 'retardo', 1, 'pendiente', ?, ?, ?)
            ");
            
            $stmt->execute([
                $empleado['id'],
                $fecha_hoy,
                $minutos_retardo,
                $hora_entrada,
                $fecha_hoy,
                $dia_semana
            ]);
            
            $incidentes_creados++;
            echo "✅ Retardo creado: {$empleado['nombre']} - {$minutos_retardo} minutos (hora: $hora_entrada)\n";
            
        } catch (Exception $e) {
            echo "❌ Error creando retardo para {$empleado['nombre']}: " . $e->getMessage() . "\n";
        }
        
        // Crear una falta para ayer (solo para algunos empleados)
        if (rand(0, 1) == 0) {
            try {
                $dia_semana_ayer = $dias_espanol[date('l', strtotime($fecha_ayer))];
                
                $stmt = $pdo->prepare("
                    INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_registro, categoria_principal, 
                                         requiere_validacion_jefe, estado_validacion, fecha_asistencia, dia_semana)
                    VALUES (?, ?, ?, 'ambos', 'ausencia', 1, 'pendiente', ?, ?)
                ");
                
                $stmt->execute([
                    $empleado['id'],
                    $fecha_ayer,
                    480, // 8 horas = falta completa
                    $fecha_ayer,
                    $dia_semana_ayer
                ]);
                
                $incidentes_creados++;
                echo "✅ Falta creada: {$empleado['nombre']} - Día completo\n";
                
            } catch (Exception $e) {
                echo "❌ Error creando falta para {$empleado['nombre']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Crear algunas salidas tempranas
    foreach (array_slice($empleados, 0, 5) as $empleado) {
        try {
            $fecha_salida_temprana = date('Y-m-d', strtotime('-2 days'));
            $dia_semana = $dias_espanol[date('l', strtotime($fecha_salida_temprana))];
            
            $stmt = $pdo->prepare("
                INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_registro, categoria_principal, 
                                     requiere_validacion_jefe, estado_validacion, hora_salida, fecha_asistencia, dia_semana)
                VALUES (?, ?, ?, 'salida', 'ausencia', 1, 'pendiente', ?, ?, ?)
            ");
            
            $stmt->execute([
                $empleado['id'],
                $fecha_salida_temprana,
                120, // 2 horas de salida temprana
                '16:00', // Salida temprana a las 4 PM
                $fecha_salida_temprana,
                $dia_semana
            ]);
            
            $incidentes_creados++;
            echo "✅ Salida temprana creada: {$empleado['nombre']} - 2 horas anticipadas\n";
            
        } catch (Exception $e) {
            echo "❌ Error creando salida temprana para {$empleado['nombre']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== RESUMEN DE INCIDENTES ===\n";
    echo "✅ Incidentes totales creados: $incidentes_creados\n";
    echo "✅ Empleados con incidentes: " . count($empleados) . "\n";
    
    // Verificar cuántos incidentes pendientes hay por jefe
    echo "\n--- Incidentes por departamento ---\n";
    $stmt = $pdo->query("
        SELECT e.area, COUNT(r.id) as incidentes_pendientes
        FROM retardos r
        JOIN empleados e ON r.empleado_id = e.id
        WHERE r.estado_validacion = 'pendiente'
        GROUP BY e.area
    ");
    
    while ($row = $stmt->fetch()) {
        echo "📊 {$row['area']}: {$row['incidentes_pendientes']} incidentes pendientes\n";
    }
    
    echo "\n✅ Configuración de incidentes completada\n";
    echo "🚀 El sistema está listo para probar las validaciones\n";
    
    echo "\n=== PRÓXIMOS PASOS ===\n";
    echo "1. Iniciar sesión como: jefe1_prueba, jefe2_prueba o jefe3_prueba\n";
    echo "2. Contraseña: jefe123\n";
    echo "3. Hacer clic en 'Validaciones' en el menú\n";
    echo "4. Ver los empleados y procesar sus incidentes\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>