<?php
require_once 'config.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$stmt = $pdo->query("SELECT c.id, c.empleado_id, c.fecha_inicio, c.fecha_fin, c.descripcion, c.estatus, c.tipo_comision FROM comisiones c WHERE c.empleado_id = 23 ORDER BY c.id DESC LIMIT 10");
$comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Comisiones del empleado 23:\n";
print_r($comisiones);
?>
