<?php
require_once 'config.php';
require_once 'models/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Verificar si la tabla empleados existe
$stmt = $conn->query('SHOW TABLES LIKE "empleados"');
if ($stmt->rowCount() == 0) {
    echo 'Creando tabla empleados...\n';
    $conn->exec('
        CREATE TABLE empleados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE,
            telefono VARCHAR(20),
            departamento VARCHAR(100),
            puesto VARCHAR(100),
            salario DECIMAL(10,2),
            fecha_contratacion DATE,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');
    
    // Insertar empleados de prueba
    $conn->exec('
        INSERT INTO empleados (nombre, apellido, email, telefono, departamento, puesto, salario, fecha_contratacion) VALUES
        ("Juan", "Pérez", "juan.perez@empresa.com", "5512345678", "TI", "Desarrollador", 25000.00, "2024-01-15"),
        ("María", "Gómez", "maria.gomez@empresa.com", "5523456789", "RH", "Gerente", 35000.00, "2023-06-01"),
        ("Carlos", "López", "carlos.lopez@empresa.com", "5534567890", "Ventas", "Vendedor", 18000.00, "2024-03-10")
    ');
    
    echo 'Empleados de prueba creados\n';
}

echo 'Tabla empleados verificada correctamente\n';
?>