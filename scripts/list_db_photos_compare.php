<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';

try {
    $db = new PDO('mysql:host=localhost;dbname=sistema_biometrico;charset=utf8mb4', 'root', 'root');
    $stmt = $db->query('SELECT foto_cara FROM empleados WHERE foto_cara IS NOT NULL AND foto_cara != ""');
    $db_photos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $db_photos[] = basename($row['foto_cara']);
    }

    $dir_photos = scandir(__DIR__ . '/../uploads/fotos_empleados');
    $files_photos = array_filter($dir_photos, function ($f) {
        return preg_match('/^empleado_.*\.(png|jpeg|jpg)$/i', $f);
    });

    $missing_in_dir = array_diff($db_photos, $files_photos);
    $extra_in_dir = array_diff($files_photos, $db_photos);

    echo "Fotos referenciadas en BD pero NO existen en directorio:\n";
    foreach ($missing_in_dir as $m) {
        echo "- $m\n";
    }

    echo "\nFotos en directorio pero NO referenciadas en BD:\n";
    foreach ($extra_in_dir as $e) {
        echo "- $e\n";
    }
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
