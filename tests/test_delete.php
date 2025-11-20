<?php
require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/Empleado.php';

// Crear un empleado de prueba para eliminar
$emp = new Empleado();
$data = [
    'nombre' => 'Test',
    'apellido' => 'Delete',
    'rfc' => 'TESTDELETE' . rand(100, 999), // RFC único
    'curp' => 'TESTDELETE' . rand(10000000, 99999999), // CURP único
    'area' => 'Pruebas',
    'jerarquia' => 'Empleado',
    'foto_cara' => null
];

$result = $emp->create($data);
if ($result) {
    echo 'Empleado de prueba creado para eliminar.' . PHP_EOL;

    // Obtener el ID del empleado creado
    $db = new Database();
    $stmt = $db->getConnection()->prepare('SELECT id FROM empleados WHERE rfc = ?');
    $stmt->execute([$data['rfc']]);
    $empleado = $stmt->fetch();
    if ($empleado) {
        echo 'ID del empleado: ' . $empleado['id'] . PHP_EOL;
        echo 'Ahora puedes probar eliminarlo desde el navegador.' . PHP_EOL;
        echo 'URL: http://localhost:8000/empleados' . PHP_EOL;
        echo '1. Haz click en "Ver" del empleado Test Delete' . PHP_EOL;
        echo '2. Haz click en "Eliminar Empleado"' . PHP_EOL;
        echo '3. Confirma la eliminación' . PHP_EOL;
        echo '4. Deberías ver el mensaje de éxito y la lista actualizada' . PHP_EOL;
    }
} else {
    echo 'Error al crear empleado de prueba.' . PHP_EOL;
}
?>
