<?php
require_once 'config.php';
require_once 'controllers/EmpleadoController.php';
$controller = new EmpleadoController();
$controller->delete(5);
echo 'Eliminación ejecutada' . PHP_EOL;
?>
