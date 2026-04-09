<?php
/**
 * Script para crear empleados de prueba y asignarles jefes
 */

require_once 'config.php';
require_once 'models/Database.php';

echo "=== CREANDO EMPLEADOS DE PRUEBA CON JEFES ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Obtener jefes disponibles
    echo "1. Obteniendo jefes disponibles...\n";
    
    $sql = "SELECT id, nombre_completo 
            FROM usuarios 
            WHERE rol IN ('jefe', 'admin', 'supervisor') 
            AND activo = 1
            ORDER BY id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($jefes)) {
        echo "   ⚠️  No hay jefes disponibles. Use setup_jefes_prueba.php primero.\n";
        exit;
    }
    
    echo "   ✅ Jefes disponibles: " . count($jefes) . "\n";
    foreach ($jefes as $jefe) {
        echo "      - ID: {$jefe['id']}, Nombre: {$jefe['nombre_completo']}\n";
    }
    
    // 2. Crear empleados de prueba
    echo "\n2. Creando empleados de prueba...\n";
    
    $areasDisponibles = ['Ventas', 'Sistemas', 'Recursos Humanos', 'Contabilidad', 'Producción'];
    $jerarquias = ['Analista', 'Coordinador', 'Especialista', 'Supervisor', 'Técnico'];
    
    // RFC y CURP simplificados para empleados de prueba
    $empleadosPrueba = [
        [
            'nombre' => 'Carlos', 'apellido' => 'Gómez López',
            'rfc' => 'GOLC800101HDFXXX00', 'curp' => 'GOLC800101HDFXXX00',
            'area' => 'Ventas', 'jerarquia' => 'Analista',
            'sexo' => 'H', 'fecha_nacimiento' => '1980-01-15', 'entidad_federativa' => 'DF'
        ],
        [
            'nombre' => 'Ana María', 'apellido' => 'Rodríguez Martínez',
            'rfc' => 'ROMA800201MJEXXX00', 'curp' => 'ROMA800201MJEXXX00',
            'area' => 'Sistemas', 'jerarquia' => 'Coordinador',
            'sexo' => 'M', 'fecha_nacimiento' => '1985-02-20', 'entidad_federativa' => 'DF'
        ],
        [
            'nombre' => 'José Luis', 'apellido' => 'Hernández Silva',
            'rfc' => 'HESJ850303HDFXXX00', 'curp' => 'HESJ850303HDFXXX00',
            'area' => 'Recursos Humanos', 'jerarquia' => 'Especialista',
            'sexo' => 'H', 'fecha_nacimiento' => '1978-03-10', 'entidad_federativa' => 'NL'
        ],
        [
            'nombre' => 'Patricia', 'apellido' => 'Mendoza Torres',
            'rfc' => 'MEPT840404MJEXXX00', 'curp' => 'MEPT840404MJEXXX00',
            'area' => 'Contabilidad', 'jerarquia' => 'Supervisor',
            'sexo' => 'M', 'fecha_nacimiento' => '1982-04-25', 'entidad_federativa' => 'DF'
        ],
        [
            'nombre' => 'Roberto', 'apellido' => 'Díaz Castro',
            'rfc' => 'DIER850505HDFXXX00', 'curp' => 'DIER850505HDFXXX00',
            'area' => 'Producción', 'jerarquia' => 'Técnico',
            'sexo' => 'H', 'fecha_nacimiento' => '1979-05-18', 'entidad_federativa' => 'JA'
        ],
        [
            'nombre' => 'Laura', 'apellido' => 'Sánchez Ramírez',
            'rfc' => 'SALR860606MJEXXX00', 'curp' => 'SALR860606MJEXXX00',
            'area' => 'Ventas', 'jerarquia' => 'Analista',
            'sexo' => 'M', 'fecha_nacimiento' => '1984-06-30', 'entidad_federativa' => 'DF'
        ],
        [
            'nombre' => 'Miguel Ángel', 'apellido' => 'Flores Vargas',
            'rfc' => 'FLOM870707HDFXXX00', 'curp' => 'FLOM870707HDFXXX00',
            'area' => 'Sistemas', 'jerarquia' => 'Coordinador',
            'sexo' => 'H', 'fecha_nacimiento' => '1981-07-12', 'entidad_federativa' => 'DF'
        ],
        [
            'nombre' => 'Carmen', 'apellido' => 'Reyes Morales',
            'rfc' => 'REYC880808MJEXXX00', 'curp' => 'REYC880808MJEXXX00',
            'area' => 'Recursos Humanos', 'jerarquia' => 'Especialista',
            'sexo' => 'M', 'fecha_nacimiento' => '1983-08-22', 'entidad_federativa' => 'GT'
        ]
    ];
    
    $empleadosCreados = [];
    foreach ($empleadosPrueba as $empleadoData) {
        $sql = "INSERT INTO empleados 
                (nombre, apellido, rfc, curp, area, jerarquia, sexo, 
                 fecha_nacimiento, entidad_federativa, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $empleadoData['nombre'],
            $empleadoData['apellido'],
            $empleadoData['rfc'],
            $empleadoData['curp'],
            $empleadoData['area'],
            $empleadoData['jerarquia'],
            $empleadoData['sexo'],
            $empleadoData['fecha_nacimiento'],
            $empleadoData['entidad_federativa']
        ]);
        
        if ($resultado) {
            $empleadoId = $pdo->lastInsertId();
            $empleadoData['id'] = $empleadoId;
            $empleadosCreados[] = $empleadoData;
            echo "   ✅ Empleado creado: {$empleadoData['nombre']} {$empleadoData['apellido']} (ID: $empleadoId)\n";
        }
    }
    
    echo "\n   ✅ Total empleados creados: " . count($empleadosCreados) . "\n";
    
    // 3. Asignar jefes a los empleados
    echo "\n3. Asignando jefes a empleados...\n";
    
    $asignaciones = 0;
    foreach ($empleadosCreados as $index => $empleado) {
        $jefeAsignado = $jefes[$index % count($jefes)];
        
        $sql = "UPDATE empleados SET jefe_directo_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([$jefeAsignado['id'], $empleado['id']]);
        
        if ($resultado) {
            $asignaciones++;
            echo "   ✅ {$empleado['nombre']} {$empleado['apellido']} -> {$jefeAsignado['nombre_completo']}\n";
        }
    }
    
    // 4. Crear incidencias de prueba
    echo "\n4. Creando incidencias de prueba para validación...\n";
    
    $incidenciasCreadas = 0;
    foreach ($empleadosCreados as $empleado) {
        // Crear 2-3 incidencias por empleado con diferentes fechas
        $numIncidencias = rand(2, 3);
        
        for ($i = 0; $i < $numIncidencias; $i++) {
            $fecha = date('Y-m-d', strtotime("-" . ($i + 1) . " days"));
            $minutosRetardo = rand(5, 45);
            
            $tipoRetraso = $minutosRetardo <= 15 ? 'menor' : 
                           ($minutosRetardo <= 30 ? 'mayor' : 'mayor');
            
            $sql = "INSERT INTO retardos 
                    (empleado_id, fecha, minutos_retardo, tipo_retraso, categoria_principal, 
                     hora_entrada, hora_salida, fecha_asistencia, dia_semana, tipo_registro,
                     requiere_validacion_jefe, estado_validacion, motivo_detalle)
                    VALUES (?, ?, ?, ?, 'retardo', ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                $empleado['empleado_id'],
                $fecha,
                $minutosRetardo,
                $tipoRetraso,
                '08:' . str_pad(rand(10, 30), 2, '0', STR_PAD_LEFT) . ':00',
                '17:' . str_pad(rand(30, 50), 2, '0', STR_PAD_LEFT) . ':00',
                $fecha,
                date('l', strtotime($fecha)),
                'entrada',
                $minutosRetardo > 15 ? 1 : 0,
                $minutosRetardo > 15 ? 'pendiente' : 'aprobado',
                'Retardo por ' . ($minutosRetardo <= 15 ? 'tráfico' : ($minutosRetardo <= 30 ? 'reunión' : 'emergencia'))
            ]);
            
            if ($resultado) {
                $incidenciasCreadas++;
            }
        }
    }
    
    echo "\n   ✅ Total incidencias creadas: $incidenciasCreadas\n";
    
    // 5. Verificar resultados finales
    echo "\n5. Verificando configuración final...\n";
    
    // Contar empleados con jefe
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleados WHERE jefe_directo_id IS NOT NULL");
    $empleadosConJefe = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar incidencias pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM retardos WHERE estado_validacion = 'pendiente'");
    $incidenciasPendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Obtener distribución por jefe
    $sql = "SELECT u.nombre_completo as jefe_nombre, COUNT(e.id) as empleados_a_cargo
            FROM empleados e
            INNER JOIN usuarios u ON e.jefe_directo_id = u.id
            GROUP BY u.id, u.nombre_completo
            ORDER BY empleados_a_cargo DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $distribucionJefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 Estadísticas finales:\n";
    echo "   👥 Empleados con jefe asignado: $empleadosConJefe\n";
    echo "   ⏳ Incidencias pendientes: $incidenciasPendientes\n";
    echo "\n   📋 Distribución por jefe:\n";
    foreach ($distribucionJefes as $dist) {
        echo "      - {$dist['jefe_nombre']}: {$dist['empleados_a_cargo']} empleados\n";
    }
    
    echo "\n🎉 Sistema de validación configurado exitosamente\n";
    echo "\n🔑 Credenciales para prueba:\n";
    foreach ($jefes as $jefe) {
        echo "   📧 Usuario jefe: {$jefe['nombre_completo']} | Contraseña: 123456\n";
    }
    
    echo "\n📋 Pasos para probar:\n";
    echo "   1. Inicie sesión como uno de los jefes listados arriba\n";
    echo "   2. Acceda a /validaciones\n";
    echo "   3. Verá la lista de empleados a su cargo en cards\n";
    echo "   4. Seleccione uno o más empleados (click en el card)\n";
    echo "   5. Verá sus incidencias en la tabla inferior\n";
    echo "   6. Procese las validaciones individuales o masivas\n";
    echo "   7. Puede aprobar, rechazar o solicitar información adicional\n";
    
} catch (Exception $e) {
    echo "❌ Error en la configuración: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>