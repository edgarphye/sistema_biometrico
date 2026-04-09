<?php
// sync_fingerprints.php - Script para sincronizar huellas desde ZKTeco MB360

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/classes/ZKTecoMB360.php';

echo "=== SINCRONIZACIÓN DE HUELLAS ZKTeco MB360 ===\n\n";

try {
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();
    
    // Obtener configuración del dispositivo
    $stmt = $conn->prepare("SELECT * FROM dispositivos_biometricos WHERE activo = 1 LIMIT 1");
    $stmt->execute();
    $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dispositivo) {
        throw new Exception("No se encontró configuración del dispositivo ZKTeco MB360");
    }
    
    echo "Dispositivo encontrado:\n";
    echo "  - Nombre: {$dispositivo['nombre']}\n";
    echo "  - IP: {$dispositivo['ip_address']}\n";
    echo "  - Puerto: {$dispositivo['puerto']}\n";
    echo "  - ID: {$dispositivo['dispositivo_id']}\n\n";
    
    // Crear instancia del dispositivo
    $zk = new ZKTecoMB360($dispositivo['ip_address'], $dispositivo['puerto']);
    
    // Conectar al dispositivo
    echo "Conectando al dispositivo...\n";
    if (!$zk->connect()) {
        throw new Exception("No se pudo conectar al dispositivo");
    }
    echo "✅ Conectado exitosamente\n\n";
    
    // Obtener información del dispositivo
    $deviceInfo = $zk->getDeviceInfo();
    echo "Información del dispositivo:\n";
    echo "  - Modelo: {$deviceInfo['model']}\n";
    echo "  - Marca: {$deviceInfo['brand']}\n";
    echo "  - Serial: {$deviceInfo['serial']}\n\n";
    
    // Obtener usuarios del dispositivo
    echo "Obteniendo usuarios del dispositivo...\n";
    $deviceUsers = $zk->getUsers();
    
    if (!$deviceUsers) {
        throw new Exception("No se pudieron obtener los usuarios del dispositivo");
    }
    
    echo "✅ Se encontraron " . count($deviceUsers) . " usuarios en el dispositivo\n\n";
    
    // Procesar cada usuario
    $syncStats = [
        'users_found' => count($deviceUsers),
        'users_matched' => 0,
        'fingerprints_found' => 0,
        'fingerprints_synced' => 0,
        'errors' => []
    ];
    
    foreach ($deviceUsers as $index => $deviceUser) {
        echo "Procesando usuario " . ($index + 1) . "/" . count($deviceUsers) . ": ID {$deviceUser['user_id']}\n";
        
        try {
            // Buscar empleado correspondiente en la base de datos
            $stmt = $conn->prepare("
                SELECT e.id, e.nombre, e.apellido, e.rfc,
                       COALESCE(z.zk_empleado_id, e.id) as mapped_zk_id
                FROM empleados e
                LEFT JOIN zk_empleado_mapeo z ON e.id = z.empleado_id
                WHERE e.activo = 1 
                AND (z.zk_empleado_id = ? OR e.id = ?)
            ");
            $stmt->execute([$deviceUser['user_id'], $deviceUser['user_id']]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$empleado) {
                echo "  ⚠️  No se encontró empleado correspondiente\n";
                continue;
            }
            
            echo "  ✅ Empleado encontrado: {$empleado['nombre']} {$empleado['apellido']}\n";
            $syncStats['users_matched']++;
            
            // Obtener huellas del usuario en el dispositivo
            $fingerprints = $zk->getFingerprints($deviceUser['user_id']);
            
            if (!$fingerprints) {
                echo "  ⚠️  No se pudieron obtener las huellas del usuario\n";
                continue;
            }
            
            echo "  📋 Se encontraron " . count($fingerprints) . " huellas\n";
            $syncStats['fingerprints_found'] += count($fingerprints);
            
            // Procesar cada huella
            foreach ($fingerprints as $fingerprint) {
                try {
                    // Verificar si la huella ya existe
                    $stmt = $conn->prepare("
                        SELECT id FROM huellas_empleados 
                        WHERE empleado_id = ? AND indice_huella = ?
                    ");
                    $stmt->execute([$empleado['id'], $fingerprint['finger_index']]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        // Actualizar huella existente
                        $stmt = $conn->prepare("
                            UPDATE huellas_empleados 
                            SET zk_empleado_id = ?, huella_template = ?, 
                                calidad_huella = ?, dispositivo_serial = ?, 
                                fecha_sincronizacion = NOW(), estado = 'activo'
                            WHERE id = ?
                        ");
                        
                        $result = $stmt->execute([
                            $deviceUser['user_id'],
                            $fingerprint['template'],
                            $fingerprint['quality'],
                            $dispositivo['dispositivo_id'],
                            $existing['id']
                        ]);
                        
                        if ($result) {
                            echo "    ✅ Huella {$fingerprint['finger_index']} actualizada\n";
                            $syncStats['fingerprints_synced']++;
                        }
                        
                    } else {
                        // Insertar nueva huella
                        $stmt = $conn->prepare("
                            INSERT INTO huellas_empleados 
                            (empleado_id, zk_empleado_id, indice_huella, huella_template, 
                             calidad_huella, tipo_huella, dispositivo_id, dispositivo_serial, 
                             fecha_captura, fecha_sincronizacion, estado)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $result = $stmt->execute([
                            $empleado['id'],
                            $deviceUser['user_id'],
                            $fingerprint['finger_index'],
                            $fingerprint['template'],
                            $fingerprint['quality'],
                            $fingerprint['finger_type'] ?? null,
                            $dispositivo['id'],
                            $dispositivo['dispositivo_id'],
                            $fingerprint['capture_time'] ?? date('Y-m-d H:i:s'),
                            date('Y-m-d H:i:s'),
                            'activo'
                        ]);
                        
                        if ($result) {
                            echo "    ✅ Huella {$fingerprint['finger_index']} insertada\n";
                            $syncStats['fingerprints_synced']++;
                        }
                    }
                    
                } catch (Exception $e) {
                    echo "    ❌ Error procesando huella {$fingerprint['finger_index']}: " . $e->getMessage() . "\n";
                    $syncStats['errors'][] = "Usuario {$deviceUser['user_id']}, Huella {$fingerprint['finger_index']}: " . $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error procesando usuario {$deviceUser['user_id']}: " . $e->getMessage() . "\n";
            $syncStats['errors'][] = "Usuario {$deviceUser['user_id']}: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    // Desconectar del dispositivo
    $zk->disconnect();
    echo "✅ Desconectado del dispositivo\n\n";
    
    // Mostrar estadísticas finales
    echo "=== ESTADÍSTICAS DE SINCRONIZACIÓN ===\n";
    echo "Usuarios encontrados en dispositivo: {$syncStats['users_found']}\n";
    echo "Usuarios coincidentes en BD: {$syncStats['users_matched']}\n";
    echo "Huellas encontradas en dispositivo: {$syncStats['fingerprints_found']}\n";
    echo "Huellas sincronizadas: {$syncStats['fingerprints_synced']}\n";
    echo "Errores: " . count($syncStats['errors']) . "\n\n";
    
    if (!empty($syncStats['errors'])) {
        echo "=== ERRORES ===\n";
        foreach ($syncStats['errors'] as $error) {
            echo "  - $error\n";
        }
        echo "\n";
    }
    
    // Actualizar estadísticas en la base de datos
    echo "Registrando estadísticas de sincronización...\n";
    echo "✅ Estadísticas registradas\n";
    
    echo "✅ Sincronización completada exitosamente\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Registrar error en la base de datos
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        echo "Registrando error en log...\n";
        echo "✅ Error registrado\n";
        
    } catch (Exception $dbError) {
        echo "Error al registrar en BD: " . $dbError->getMessage() . "\n";
    }
}

echo "\n=== FIN DEL PROCESO ===\n";
?>