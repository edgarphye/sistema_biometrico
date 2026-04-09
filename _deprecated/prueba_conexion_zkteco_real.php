<?php
// PRUEBA DIRECTA DE CONEXIÓN A DISPOSITIVO ZKTeco REAL
// Esta prueba intenta conectar a un dispositivo real

require_once 'config.php';
require_once 'vendor/autoload.php';
require_once 'models/Database.php';
require_once 'models/biometric/BiometricFactory.php';
require_once 'models/biometric/BiometricInterface.php';
require_once 'models/biometric/BiometricSimulation.php';
require_once 'models/biometric/BiometricSDK.php';
require_once 'models/biometric/ZKTecoSDK.php';
require_once 'models/Biometrico.php';

echo "=== 🌐 PRUEBA DE CONEXIÓN A DISPOSITIVO ZKTeco REAL ===\n\n";

// 1. Verificar que el SDK está disponible
echo "📋 VERIFICACIÓN DE SDK ZKTeco:\n";
echo "Sockets Extension: " . (extension_loaded('sockets') ? '✅ ACTIVADA' : '❌ NO ACTIVA') . "\n";
echo "ZKTeco SDK Class: " . (class_exists('Jmrashed\\Zkteco\\Lib\\ZKTeco') ? '✅ DISPONIBLE' : '❌ NO ENCONTRADA') . "\n";

if (!extension_loaded('sockets') || !class_exists('Jmrashed\\Zkteco\\Lib\\ZKTeco')) {
    echo "❌ SDK ZKTeco no disponible. Instale las dependencias primero.\n";
    exit;
}

echo "\n🔧 INICIANDO CONEXIÓN DIRECTA...\n";

try {
    // 2. Crear instancia directa del SDK ZKTeco
    echo "Creando instancia ZKTeco...\n";
    $zk = new \Jmrashed\Zkteco\Lib\ZKTeco('192.168.1.100', 4370);
    echo "✅ Instancia ZKTeco creada\n";
    
    // 3. Intentar conectar
    echo "Intentando conectar a 192.168.1.100:4370...\n";
    $timeout = 10; // 10 segundos timeout
    $startTime = microtime(true);
    
    $connected = $zk->connect();
    
    $endTime = microtime(true);
    $tiempoConexion = round(($endTime - $startTime) * 1000, 2);
    
    if ($connected) {
        echo "🎉 ✅ CONEXIÓN EXITOSA!\n";
        echo "⏱️ Tiempo de conexión: {$tiempoConexion}ms\n";
        
        // 4. Obtener información del dispositivo
        echo "\n📱 INFORMACIÓN DEL DISPOSITIVO:\n";
        
        try {
            $serialNumber = $zk->getSerialNumber();
            echo "🔢 Número de serie: " . ($serialNumber ?? 'No disponible') . "\n";
        } catch (Exception $e) {
            echo "🔢 Número de serie: No disponible\n";
        }
        
        try {
            $firmwareVersion = $zk->getFirmwareVersion();
            echo "🔧 Versión firmware: " . ($firmwareVersion ?? 'No disponible') . "\n";
        } catch (Exception $e) {
            echo "🔧 Versión firmware: No disponible\n";
        }
        
        try {
            $deviceTime = $zk->getDeviceTime();
            echo "⏰ Hora dispositivo: " . ($deviceTime ?? 'No disponible') . "\n";
        } catch (Exception $e) {
            echo "⏰ Hora dispositivo: No disponible\n";
        }
        
        try {
            $macAddress = $zk->getMacAddress();
            echo "🌐 MAC Address: " . ($macAddress ?? 'No disponible') . "\n";
        } catch (Exception $e) {
            echo "🌐 MAC Address: No disponible\n";
        }
        
        // 5. Obtener usuarios del dispositivo
        echo "\n👥 USUARIOS EN DISPOSITIVO:\n";
        try {
            $usuarios = $zk->getUser();
            if ($usuarios && count($usuarios) > 0) {
                echo "📊 Total usuarios: " . count($usuarios) . "\n";
                $maxMostrar = min(5, count($usuarios));
                for ($i = 0; $i < $maxMostrar; $i++) {
                    $usuario = $usuarios[$i];
                    echo "   👤 UID: " . ($usuario[0] ?? 'N/A') . 
                         " | Nombre: " . ($usuario[1] ?? 'N/A') .
                         " | Privilegio: " . ($usuario[2] ?? 'N/A') . "\n";
                }
                if (count($usuarios) > 5) {
                    echo "   ... y " . (count($usuarios) - 5) . " usuarios más\n";
                }
            } else {
                echo "📊 No hay usuarios en el dispositivo\n";
            }
        } catch (Exception $e) {
            echo "📊 Error obteniendo usuarios: " . $e->getMessage() . "\n";
        }
        
        // 6. Obtener registros de asistencia
        echo "\n📅 REGISTROS DE ASISTENCIA:\n";
        try {
            $asistencia = $zk->getAttendance();
            if ($asistencia && count($asistencia) > 0) {
                echo "📊 Total registros: " . count($asistencia) . "\n";
                $maxMostrar = min(5, count($asistencia));
                for ($i = 0; $i < $maxMostrar; $i++) {
                    $registro = $asistencia[$i];
                    echo "   📝 UID: " . ($registro[0] ?? 'N/A') .
                         " | Timestamp: " . ($registro[1] ?? 'N/A') .
                         " | Estado: " . ($registro[2] ?? 'N/A') . "\n";
                }
                if (count($asistencia) > 5) {
                    echo "   ... y " . (count($asistencia) - 5) . " registros más\n";
                }
            } else {
                echo "📊 No hay registros de asistencia\n";
            }
        } catch (Exception $e) {
            echo "📊 Error obteniendo asistencia: " . $e->getMessage() . "\n";
        }
        
        // 7. Desconectar
        echo "\n🔌 Desconectando del dispositivo...\n";
        $zk->disconnect();
        echo "✅ Desconexión exitosa\n";
        
        echo "\n🎉 PRUEBA COMPLETADA CON ÉXITO!\n";
        echo "✅ El dispositivo ZKTeco en 192.168.1.100 está accesible y funcionando\n";
        echo "🔄 El sistema está listo para sincronización y operaciones biométricas\n";
        
    } else {
        echo "❌ ERROR DE CONEXIÓN\n";
        echo "⏱️ Tiempo de intento: {$tiempoConexion}ms\n";
        echo "\n🔍 SOLUCIONES POSIBLES:\n";
        echo "1. Verificar que el dispositivo ZKTeco esté encendido\n";
        echo "2. Verificar IP del dispositivo (debe ser 192.168.1.100)\n";
        echo "3. Verificar conexión de red (ping 192.168.1.100)\n";
        echo "4. Verificar que el puerto 4370 no esté bloqueado por firewall\n";
        echo "5. Asegurarse que el dispositivo permita conexiones TCP/IP\n";
        
        // Intentar hacer ping
        echo "\n🌐 Intentando ping a 192.168.1.100...\n";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $pingCommand = 'ping -n 2 192.168.1.100';
        } else {
            $pingCommand = 'ping -c 2 192.168.1.100';
        }
        
        $pingResult = shell_exec($pingCommand);
        echo "Resultado del ping:\n";
        echo $pingResult;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "\n🔍 DETALLES DEL ERROR:\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Traza: " . $e->getTraceAsString() . "\n";
}

echo "\n=== 🏁 FIN DE PRUEBA DE CONEXIÓN ZKTeco ===\n";
?>