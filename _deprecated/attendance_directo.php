<?php
// attendance_directo.php - Versión directa sin frameworks

session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: login_final.php');
    exit;
}

require_once 'config.php';
require_once 'models/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Parámetros simples
$modo = $_GET['modo'] ?? 'hoy';
$limite = $_GET['limite'] ?? 100;

$sql = "SELECT a.*, 
               CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, e.area, e.rfc
        FROM asistencia a
        JOIN empleados e ON a.empleado_id = e.id
        WHERE 1=1 ORDER BY a.fecha DESC, a.hora_entrada DESC
        LIMIT $limite";

$stmt = $stmt = $conn->prepare($sql);
$stmt->execute();
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia - Sistema Biométrico</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        .estado { padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .completo { background: #d4edda; color: white; }
        .en_curso { background: #fff3cd; color: #000; }
        .pendiente { background: #fff3cd; color: #000; }
        .btn { padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>📋 Registros de Asistencia (<?= $modo ?>)</h2>
    
    <?php if (!empty($asistencias)): ?>
        <table>
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Área</th>
                    <th>Fecha</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asistencias as $asistencia): ?>
                    <tr>
                        <td><?= htmlspecialchars($asistencia['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($asistencia['area']) ?></td>
                        <td><?= $asistencia['fecha'] ?></td>
                        <td><?= $asistencia['hora_entrada'] ?? '-' ?></td>
                        <td><?= $asistencia['hora_salida'] ?? '-' ?></td>
                        <td>
                            <?php if ($asistencia['hora_salida']): ?>
                                <span class="estado completo">✅</span>
                            <?php elseif ($asistencia['hora_entrada']): ?>
                                <span class="estado en_curso">⏰</span>
                            <?php else: ?>
                                <span class="estado pendiente">⏳</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; margin: 50px;">
            No hay registros de asistencia para mostrar en el modo: <?= $modo ?>
        </p>
    <?php endif; ?>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="?modo=<?= $modo === 'hoy' ? 'historial' : 'hoy' ?>" class="btn">
            <?= $modo === 'hoy' ? '📊 Ver Histórico' : '📅 Ver Hoy' ?>
        </a>
        <?php if ($modo !== 'completo'): ?>
            <a href="?limite=<?= $limite * 2 ?>&modo=<?= $modo ?>" class="btn">
            Cargar más registros (+<?= $limite ?>)
        </a>
        <?php endif; ?>
    </div>
</body>
</html>