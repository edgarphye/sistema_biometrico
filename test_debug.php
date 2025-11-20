<?php
require_once 'config.php';
require_once 'models/Asistencia.php';

$asistencia = new Asistencia();
$result = $asistencia->getEmpleadosConAsistencia('2025-11-15', '2025-11-16');
echo 'Resultados: ' . count($result) . PHP_EOL;
var_dump($result);
