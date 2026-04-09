<?php
// Corrección de seguridad crítica - Autenticación

require_once 'config.php';
require_once 'models/Database.php';

echo "=== 🔒 CORRECCIÓN DE SEGURIDAD ===\n\n";

$db = Database::getInstance();

// 1. Verificar usuarios admin
echo "1. Verificando usuarios administradores...\n";
$sql_admin = "SELECT id, username, rol FROM usuarios WHERE rol IN ('admin', 'supervisor', 'rh')";
$stmt_admin = $db->getConnection()->prepare($sql_admin);
$stmt_admin->execute();
$admins = $stmt_admin->fetchAll(PDO::FETCH_ASSOC);

foreach ($admins as $admin) {
    echo "   - {$admin['username']}: {$admin['rol']}\n";
}
echo "   ✓ Administradores encontrados: " . count($admins) . "\n";

// 2. Cambiar contraseña por defecto del admin
echo "\n2. Cambiando contraseña por defecto del admin...\n";
$nuevo_hash = password_hash('Sistema2026!@#$', PASSWORD_DEFAULT);
$sql_pass = "UPDATE usuarios SET password = ? WHERE username = 'admin'";
$stmt_pass = $db->getConnection()->prepare($sql_pass);
$stmt_pass->execute([$nuevo_hash]);
echo "   ✓ Contraseña de admin actualizada\n";

// 3. Verificar usuarios sin roles definidos
echo "\n3. Verificando usuarios con roles...\n";
$sql_roles = "SELECT id, username, rol FROM usuarios";
$stmt_roles = $db->getConnection()->prepare($sql_roles);
$stmt_roles->execute();
$usuarios = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

foreach ($usuarios as $usuario) {
    $rol_actual = $usuario['rol'] ?? 'sin_rol';
    echo "   - {$usuario['username']}: {$rol_actual}\n";
}

// 4. Crear usuarios de prueba si no existen
echo "\n4. Verificando usuarios de prueba...\n";
$usuarios_prueba = [
    ['jefe1', 'Jefe1@2026!', 'admin', 'Jefe Ventas'],
    ['jefe2', 'Jefe2@2026!', 'admin', 'Jefe Ingeniería'],
    ['jefe3', 'Jefe3@2026!', 'admin', 'Jefe RRHH']
];

foreach ($usuarios_prueba as $datos) {
    $sql_check = "SELECT id FROM usuarios WHERE username = ?";
    $stmt_check = $db->getConnection()->prepare($sql_check);
    $stmt_check->execute([$datos[0]]);
    
    if (!$stmt_check->fetch()) {
        $hash = password_hash($datos[1], PASSWORD_DEFAULT);
        $sql_insert = "INSERT INTO usuarios (username, password, rol, email, nombre_completo) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $db->getConnection()->prepare($sql_insert);
        $stmt_insert->execute([$datos[0], $hash, $datos[2], $datos[0] . '@sistema.local', $datos[3]]);
        echo "   ✓ Creado usuario: {$datos[0]} ({$datos[3]})\n";
    } else {
        echo "   ⚠ Usuario ya existe: {$datos[0]}\n";
    }
}

// 5. Configurar CSP más restrictivo para producción
echo "\n5. Configuración de seguridad recomendada:\n";
echo "   - Deshabilitar 2FA bypass en AuthController.php:21\n";
echo "   - Activar CSRF para todos los endpoints\n";
echo "   - Implementar rate limiting\n";
echo "   - Configurar CORS específico por dominio\n";

echo "\n✅ CORRECCIONES DE SEGURIDAD APLICADAS\n";
echo "📋 ACCIONES MANUALES REQUERIDAS:\n";
echo "   1. Verificar AuthController.php línea 21\n";
echo "   2. Actualizar contraseña en navegador\n";
echo "   3. Probar login con nuevo usuario: admin / Sistema2026!@#$\n";
?>