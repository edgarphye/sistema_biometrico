<?php
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Contar registros en asistencia
    $result = $pdo->query('SELECT COUNT(*) as total FROM asistencia')->fetch();
    echo 'Total registros en asistencia: ' . $result['total'] . PHP_EOL;

    // Contar dispositivos
    $result2 = $pdo->query('SELECT COUNT(*) as total FROM dispositivos_biometricos')->fetch();
    echo 'Total dispositivos: ' . $result2['total'] . PHP_EOL;

    // Mostrar últimos 5 registros
    echo PHP_EOL . 'Últimos 5 registros de asistencia:' . PHP_EOL;
    $stmt = $pdo->query('SELECT a.id, e.nombre, e.apellido, e.area, DATE(a.timestamp) as fecha, TIME(a.timestamp) as hora, a.tipo, a.dispositivo_id, a.tipo_biometria, a.calidad_verificacion FROM asistencia a JOIN empleados e ON a.empleado_id = e.id ORDER BY a.id DESC LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Empleado: {$row['nombre']} {$row['apellido']}, Área: {$row['area']}, Fecha: {$row['fecha']}, Hora: {$row['hora']}, Tipo: {$row['tipo']}, Dispositivo: {$row['dispositivo_id']}, Biometría: {$row['tipo_biometria']}, Calidad: {$row['calidad_verificacion']}" . PHP_EOL;
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
