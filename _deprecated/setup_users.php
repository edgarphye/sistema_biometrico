<?php
require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/Usuario.php';

$db = new Database();
$conn = $db->getConnection();

// Verificar si la tabla usuarios existe
$stmt = $conn->query('SHOW TABLES LIKE "usuarios"');
if ($stmt->rowCount() == 0) {
    echo "Creando tabla usuarios...\n";
    $conn->exec('
        CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol VARCHAR(50) DEFAULT "usuario",
            empleado_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');
} else {
    // Si la tabla ya existe, intentamos ampliar la columna rol para evitar errores de truncamiento (Data truncated)
    try {
        $conn->exec('ALTER TABLE usuarios MODIFY COLUMN rol VARCHAR(50) DEFAULT "usuario"');
    } catch (Exception $e) {
        // Continuar si no se puede alterar (ej. permisos o bloqueo), el script intentará insertar de todos modos
    }
}

$usuario = new Usuario();

// Crear usuarios de prueba si no existen
if (!$usuario->getByUsername('admin')) {
    $usuario->create([
        'username' => 'admin',
        'password' => 'admin123',
        'rol' => 'admin'
    ]);
    echo "Usuario admin creado\n";
}

if (!$usuario->getByUsername('usuario')) {
    $usuario->create([
        'username' => 'usuario',
        'password' => 'password123',
        'rol' => 'usuario'
    ]);
    echo "Usuario usuario creado\n";
}

echo "Base de datos verificada correctamente\n";
?>