<?php
require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/Usuario.php';

$db = new Database();
$usuarioModel = new Usuario();

// Verificar si el usuario admin ya existe
$existingAdmin = $usuarioModel->getByUsername('admin');
if ($existingAdmin) {
    echo 'El usuario admin ya existe.' . PHP_EOL;
} else {
    // Crear usuario admin
    $adminData = [
        'username' => 'admin',
        'password' => 'admin123',
        'rol' => 'admin',
        'empleado_id' => null
    ];

    if ($usuarioModel->create($adminData)) {
        echo 'Usuario admin creado exitosamente.' . PHP_EOL;
    } else {
        echo 'Error al crear usuario admin.' . PHP_EOL;
    }
}

// Verificar si el usuario regular ya existe
$existingUser = $usuarioModel->getByUsername('usuario');
if ($existingUser) {
    echo 'El usuario regular ya existe.' . PHP_EOL;
} else {
    // Crear usuario regular
    $userData = [
        'username' => 'usuario',
        'password' => 'usuario123',
        'rol' => 'usuario',
        'empleado_id' => null
    ];

    if ($usuarioModel->create($userData)) {
        echo 'Usuario regular creado exitosamente.' . PHP_EOL;
    } else {
        echo 'Error al crear usuario regular.' . PHP_EOL;
    }
}

// Verificar si el usuario administrador adicional ya existe
$existingSuperAdmin = $usuarioModel->getByUsername('superadmin');
if ($existingSuperAdmin) {
    echo 'El usuario superadmin ya existe.' . PHP_EOL;
} else {
    // Crear usuario administrador adicional
    $superAdminData = [
        'username' => 'superadmin',
        'password' => 'super123',
        'rol' => 'admin',
        'empleado_id' => null
    ];

    if ($usuarioModel->create($superAdminData)) {
        echo 'Usuario superadmin creado exitosamente.' . PHP_EOL;
    } else {
        echo 'Error al crear usuario superadmin.' . PHP_EOL;
    }
}
?>
