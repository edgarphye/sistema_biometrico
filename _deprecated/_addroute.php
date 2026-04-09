<?php
$file = 'index.php';
$content = file_get_contents($file);

$oldText = "        break;\n    case '/empleados/create':\n        require_once 'controllers/EmpleadoController.php';\n        \$controller = new EmpleadoController();\n        \$controller->create();\n        break;";

$newText = "        break;\n    case (preg_match('/^\\/empleados\\/datos-completos\\/(\\d+)\$/', \$request, \$matches) ? true : false):\n        require_once 'controllers/EmpleadoController.php';\n        \$controller = new EmpleadoController();\n        \$controller->datosCompletos(\$matches[1]);\n        break;\n    case '/empleados/create':\n        require_once 'controllers/EmpleadoController.php';\n        \$controller = new EmpleadoController();\n        \$controller->create();\n        break;";

if (strpos($content, $oldText) !== false) {
    $content = str_replace($oldText, $newText, $content);
    file_put_contents($file, $content);
    echo "Ruta agregada\n";
} else {
    echo "Error: patrón no encontrado\n";
}
?>
