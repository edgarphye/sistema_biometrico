<?php
require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/Empleado.php';

// Crear imagen de prueba (1x1 pixel PNG)
$image = imagecreatetruecolor(1, 1);
imagepng($image, 'test_image.png');
imagedestroy($image);

// Simular subida de archivo
$_FILES['foto_cara'] = [
    'name' => 'test_image.png',
    'type' => 'image/png',
    'tmp_name' => 'test_image.png',
    'error' => UPLOAD_ERR_OK,
    'size' => filesize('test_image.png')
];

$emp = new Empleado();
$data = [
    'nombre' => 'Juan',
    'apellido' => 'Perez',
    'rfc' => 'PEJU850102' . rand(100, 999), // RFC único (13 chars max)
    'curp' => 'PEJU850102HDFRZN' . rand(10, 99), // CURP único (18 chars max)
    'area' => 'Ventas',
    'jerarquia' => 'Empleado',
    'foto_cara' => null
];

// Simular el proceso del controlador
if (isset($_FILES['foto_cara']) && $_FILES['foto_cara']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['foto_cara']['tmp_name'];
    $fileName = $_FILES['foto_cara']['name'];
    $fileSize = $_FILES['foto_cara']['size'];
    $fileType = $_FILES['foto_cara']['type'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($fileType, $allowedTypes) && $fileSize <= 2 * 1024 * 1024) {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('empleado_', true) . '.' . $fileExtension;
        $uploadPath = 'uploads/fotos_empleados/' . $newFileName;

        if (copy($fileTmpPath, $uploadPath)) {
            $data['foto_cara'] = $uploadPath;
            echo 'Foto copiada a: ' . $uploadPath . PHP_EOL;
        }
    }
}

$result = $emp->create($data);
echo 'Empleado creado: ' . ($result ? 'SI' : 'NO') . PHP_EOL;

// Verificar archivos
$files = glob('uploads/fotos_empleados/*');
echo 'Archivos en uploads/fotos_empleados/: ' . count($files) . PHP_EOL;
foreach ($files as $file) {
    echo '  - ' . basename($file) . PHP_EOL;
}

// Verificar BD
$db = new Database();
$stmt = $db->getConnection()->prepare('SELECT id, nombre, apellido, foto_cara FROM empleados WHERE rfc = ?');
$stmt->execute([$data['rfc']]);
$empleado = $stmt->fetch();
if ($empleado) {
    echo 'Empleado en BD:' . PHP_EOL;
    echo '  ID: ' . $empleado['id'] . PHP_EOL;
    echo '  Nombre: ' . $empleado['nombre'] . ' ' . $empleado['apellido'] . PHP_EOL;
    echo '  Foto: ' . ($empleado['foto_cara'] ?: 'NULL') . PHP_EOL;
}

// Limpiar
unlink('test_image.png');
?>
