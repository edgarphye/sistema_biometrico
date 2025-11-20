<?php
require_once 'config.php';
require_once 'controllers/AsistenciaController.php';

$controller = new AsistenciaController();
$_POST['fecha_inicio'] = '2025-11-15';
$_POST['fecha_fin'] = '2025-11-16';

echo "Testing calcularHorasLaborables...\n";
ob_start(); // Capture output
$controller->calcularHorasLaborables();
$output = ob_get_clean();
echo $output;
echo "\nDone.\n";
