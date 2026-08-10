<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/Encryption.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST DE CIFRADO BIOMÉTRICO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verificar que ENCRYPTION_KEY esté configurada
echo "1. Verificando ENCRYPTION_KEY...\n";
$envKey = getenv('ENCRYPTION_KEY');

if ($envKey === false || $envKey === '') {
    echo "   ✗ ENCRYPTION_KEY NO está configurada en variables de entorno\n";
    echo "   Ejecuta: php setup_encryption_key.php para instrucciones\n\n";
    exit(1);
} else {
    echo "   ✓ ENCRYPTION_KEY está configurada\n";
    echo "   Longitud: " . strlen($envKey) . " caracteres\n\n";
}

echo "2. Probando cifrado/descifrado...\n";

// Datos de prueba (simular huella dactilar)
$datosOriginales = json_encode([
    'template' => base64_encode(random_bytes(256)),
    'quality' => 95,
    'finger_index' => 1,
    'timestamp' => date('Y-m-d H:i:s')
]);

echo "   Datos originales: " . strlen($datosOriginales) . " bytes\n";

// Cifrar
$datosCifrados = Encryption::encrypt($datosOriginales);
echo "   ✓ Cifrado exitoso: " . strlen($datosCifrados) . " bytes\n";

// Descifrar
$datosDescifrados = Encryption::decrypt($datosCifrados);
echo "   ✓ Descifrado exitoso: " . strlen($datosDescifrados) . " bytes\n";

// Verificar integridad
if ($datosOriginales === $datosDescifrados) {
    echo "   ✓ Integridad verificada - Datos idénticos\n\n";
} else {
    echo "   ✗ ERROR: Los datos no coinciden después de descifrado\n\n";
    exit(1);
}

echo "3. Probando con datos biométricos reales...\n";

// Simular huella dactilar real
$huellaSimulada = [
    'algorithm' => 'iso_19794_2',
    'template' => base64_encode(random_bytes(512)),
    'minutiae_points' => 42,
    'quality' => 87,
    'capture_device' => 'ZKTeco F18',
    'timestamp' => time()
];

$huellaJson = json_encode($huellaSimulada);
$huellaCifrada = Encryption::encrypt($huellaJson);
$huellaDescifrada = Encryption::decrypt($huellaCifrada);

echo "   Original:    " . strlen($huellaJson) . " bytes\n";
echo "   Cifrado:     " . strlen($huellaCifrada) . " bytes\n";
echo "   Descifrado:  " . strlen($huellaDescifrada) . " bytes\n";

if ($huellaJson === $huellaDescifrada) {
    echo "   ✓ Huella biométrica cifrada/descifrada correctamente\n\n";
} else {
    echo "   ✗ ERROR en cifrado de huella biométrica\n\n";
    exit(1);
}

echo "4. Probando con múltiples iteraciones...\n";
$iteraciones = 100;
$errores = 0;

for ($i = 0; $i < $iteraciones; $i++) {
    $data = bin2hex(random_bytes(rand(100, 500)));
    $encrypted = Encryption::encrypt($data);
    $decrypted = Encryption::decrypt($encrypted);
    
    if ($data !== $decrypted) {
        $errores++;
    }
}

if ($errores === 0) {
    echo "   ✓ {$iteraciones} iteraciones completadas sin errores\n\n";
} else {
    echo "   ✗ {$errores}/{$iteraciones} iteraciones fallaron\n\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ TODOS LOS TESTS PASARON EXITOSAMENTE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "🔐 El sistema de cifrado está funcionando correctamente.\n";
echo "   Los datos biométricos serán cifrados con AES-256-GCM.\n";
echo "   Compatibilidad: datos legacy AES-256-CBC aún pueden descifrarse.\n\n";

echo "Próximos pasos:\n";
echo "   • Configura ENCRYPTION_KEY permanentemente (ver setup_encryption_key.php)\n";
echo "   • Reinicia Apache/Nginx para que tome la variable de entorno\n";
echo "   • Los nuevos datos biométricos se cifrarán automáticamente\n\n";
?>
