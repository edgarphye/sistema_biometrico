<?php
require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/Usuario.php';

echo "=== Creación de Usuario Administrador Global ===\n";

// Configuración del usuario
$username = 'superadmin';
$password = 'AdminGlobal2025!';
$email = 'admin.global@sistema.com';
$nombre = 'Administrador Global';

try {
    $usuarioModel = new Usuario();
    
    // Verificar si el usuario ya existe
    $existing = $usuarioModel->getByUsername($username);
    
    if ($existing) {
        echo "⚠️ El usuario '$username' ya existe. Actualizando permisos y contraseña...\n";
        
        // Actualización directa a la BD para asegurar el hash y el rol
        $db = new Database();
        $pdo = $db->getConnection();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, rol = 'admin', activo = 1 WHERE username = ?");
        if ($stmt->execute([$hash, $username])) {
            echo "✅ Usuario actualizado correctamente.\n";
        } else {
            echo "❌ Error al actualizar el usuario.\n";
        }
    } else {
        $data = [
            'username' => $username,
            'password' => $password, // El modelo Usuario::create se encarga del hash
            'email' => $email,
            'nombre_completo' => $nombre,
            'rol' => 'admin',
            'activo' => 1
        ];
        
        if ($usuarioModel->create($data)) {
            echo "✅ Usuario administrador creado exitosamente.\n";
        } else {
            echo "❌ Error al crear el usuario.\n";
        }
    }
    
    echo "\n📋 Credenciales:\n";
    echo "Usuario: $username\n";
    echo "Contraseña: $password\n";
    
} catch (Exception $e) {
    echo "❌ Error crítico: " . $e->getMessage() . "\n";
}
?>