<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

echo "=== Migración: Dispositivos Biométricos ===\n\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Desactivar checks de FK temporalmente
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Verificar si existe
    $exists = $pdo->query("SHOW TABLES LIKE 'dispositivos_biometricos'")->fetch();
    
    if ($exists) {
        echo "ℹ️  Tabla existente encontrada - eliminando...\n";
        $pdo->exec("DROP TABLE IF EXISTS dispositivos_biometricos");
        echo "   ✓ Eliminada\n\n";
    }
    
    echo "1. Creando tabla dispositivos_biometricos...\n";
    
    $pdo->exec("
        CREATE TABLE dispositivos_biometricos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dispositivo_id INT UNIQUE NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            sede VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            puerto INT DEFAULT 4370,
            tipo_dispositivo VARCHAR(50) DEFAULT 'ZKTeco',
            modelo VARCHAR(50),
            firmware_version VARCHAR(20),
            capacidades JSON,
            activo BOOLEAN DEFAULT 1,
            fecha_instalacion DATE,
            ultima_sincronizacion DATETIME,
            configuracion_adicional JSON,
            notas TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_sede (sede),
            INDEX idx_activo (activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "   ✓ Tabla creada\n\n";
    
    echo "2. Insertando dispositivos...\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO dispositivos_biometricos 
        (dispositivo_id, nombre, sede, ip_address, capacidades, fecha_instalacion) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $dispositivos = [];
    
    // 5 principales
    $dispositivos[] = [1, 'Biométrico Sede Central', 'Central', '192.168.1.100'];
    $dispositivos[] = [2, 'Biométrico Zona Norte', 'Norte', '192.168.2.100'];
    $dispositivos[] = [3, 'Biométrico Zona Sur', 'Sur', '192.168.3.100'];
    $dispositivos[] = [4, 'Biométrico Zona Este', 'Este', '192.168.4.100'];
    $dispositivos[] = [5, 'Biométrico Zona Oeste', 'Oeste', '192.168.5.100'];
    
    // 30 sucursales adicionales
    for ($i = 6; $i <= 35; $i++) {
        $letra = chr(65 + (($i - 6) / 2)); // A=6-7, B=8-9, etc.
        $num = (($i - 6) % 2) + 1;
        $dispositivos[] = [$i, "Biométrico Sucursal {$letra}{$num}", "Sucursal {$letra}{$num}", "192.168.{$i}.100"];
    }
    
    foreach ($dispositivos as $idx => $disp) {
        $id = $disp[0];
        $nombre = $disp[1];
        $sede = $disp[2];
        $ip = $disp[3];
        
        $capacidades = json_encode([
            'huella' => true,
            'cara' => ($id % 3 != 0) // Cada 3ro sin reconocimiento facial
        ]);
        
        $mes = str_pad((($id - 1) % 12) + 1, 2, '0', STR_PAD_LEFT);
        $fecha = "2024-{$mes}-01";
        
        $stmt->execute([$id, $nombre, $sede, $ip, $capacidades, $fecha]);
        
        if (($idx + 1) % 10 == 0) {
            echo "   → " . ($idx + 1) . " dispositivos...\n";
        }
    }
    
    echo "   ✓ 35 dispositivos insertados\n\n";
    
    // Reactivar checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Verificar
    $total = $pdo->query("SELECT COUNT(*) FROM dispositivos_biometricos")->fetchColumn();
    $activos = $pdo->query("SELECT COUNT(*) FROM dispositivos_biometricos WHERE activo = 1")->fetchColumn();
    
    echo "✅ ¡Migración completada exitosamente!\n\n";
    echo "📊 Resumen:\n";
    echo "   • Total dispositivos: {$total}\n";
    echo "   • Dispositivos activos: {$activos}\n";
    echo "   • Rango de IPs: 192.168.1.100 - 192.168.35.100\n\n";
    echo "🌐 Acceso:\n";
    echo "   http://localhost/sistema_biometrico/dispositivos\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); // Reactivar en caso de error
    exit(1);
}
?>
