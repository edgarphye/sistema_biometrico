<?php
/**
 * Generador de ENCRYPTION_KEY segura
 * Ejecuta este script para generar una clave de cifrado única
 */

echo "═══════════════════════════════════════════════════════════════\n";
echo "  GENERADOR DE ENCRYPTION_KEY\n";
echo "  Sistema Biométrico - Configuración de Seguridad\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Generar clave segura de 32 bytes
$key = bin2hex(random_bytes(32)); // 64 caracteres hexadecimales

echo "🔐 Clave de cifrado generada:\n\n";
echo "   " . $key . "\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 INSTRUCCIONES PARA CONFIGURAR EN WINDOWS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Opción 1: Variable de Entorno de SISTEMA (Recomendado)\n";
echo "---------------------------------------------------------------\n";
echo "1. Presiona Win + R, escribe: sysdm.cpl y presiona Enter\n";
echo "2. Ve a la pestaña 'Opciones avanzadas'\n";
echo "3. Clic en 'Variables de entorno'\n";
echo "4. En 'Variables del sistema', clic en 'Nueva'\n";
echo "5. Nombre: ENCRYPTION_KEY\n";
echo "6. Valor: " . $key . "\n";
echo "7. Clic en 'Aceptar' y reinicia los servicios (Apache/Nginx)\n\n";

echo "Opción 2: PowerShell (Sesión actual)\n";
echo "---------------------------------------------------------------\n";
echo "Ejecuta en PowerShell como Administrador:\n\n";
echo "   \$env:ENCRYPTION_KEY = \"$key\"\n\n";
echo "NOTA: Esta configuración solo dura hasta cerrar PowerShell\n\n";

echo "Opción 3: CMD (Sesión actual)\n";
echo "---------------------------------------------------------------\n";
echo "Ejecuta en CMD:\n\n";
echo "   set ENCRYPTION_KEY=$key\n\n";
echo "NOTA: Esta configuración solo dura hasta cerrar CMD\n\n";

echo "Opción 4: Variable de Entorno PERMANENTE (CMD)\n";
echo "---------------------------------------------------------------\n";
echo "Ejecuta en CMD como Administrador:\n\n";
echo "   setx ENCRYPTION_KEY \"$key\" /M\n\n";
echo "NOTA: /M = Variable del sistema (requiere permisos admin)\n";
echo "      Reinicia las aplicaciones para que tomen efecto\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ VERIFICACIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Después de configurar, ejecuta:\n";
echo "   php tests/test_encryption.php\n\n";

echo "O verifica con:\n";
echo "   php -r \"echo getenv('ENCRYPTION_KEY');\"\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "⚠️  SEGURIDAD\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "• Guarda esta clave en un lugar seguro (gestor de contraseñas)\n";
echo "• NO la compartas en repositorios públicos (.gitignore)\n";
echo "• Si pierdes la clave, NO podrás descifrar datos existentes\n";
echo "• Usa diferentes claves para desarrollo/producción\n\n";

// Guardar en archivo temporal
$configFile = __DIR__ . '/.encryption_key.txt';
file_put_contents($configFile, $key);
chmod($configFile, 0600); // Solo lectura para el propietario

echo "✓ Clave guardada temporalmente en:\n";
echo "   " . $configFile . "\n\n";
echo "  IMPORTANTE: Elimina este archivo después de configurar la variable.\n\n";

?>
