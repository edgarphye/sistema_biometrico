<?php
// asistencia_simple.php - Versión simple y funcional

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login_final.php');
    exit;
}

require_once 'config.php';
require_once 'models/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Obtener parámetros
$modo = $_GET['modo'] ?? 'hoy';
$empleado_id = $_GET['empleado_id'] ?? '';
$mostrar_todas = isset($_GET['mostrar_todas']) ? 1 : 0;

// Construir SQL según parámetros
$sql = "SELECT a.*, e.nombre, e.apellido, e.area, e.rfc,
               CONCAT(e.nombre, ' ', e.apellido) as nombre_completo
        FROM asistencia a
        JOIN empleados e ON a.empleado_id = e.id";

$where_conditions = [];
$params = [];

switch ($modo) {
    case 'hoy':
        $where_conditions[] = "DATE(a.fecha) = CURDATE()";
        break;
    case 'historial':
        $where_conditions[] = "a.fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()";
        break;
    case 'completo':
        // Mostrar todas sin límite
        break;
}

if ($empleado_id) {
    $where_conditions[] = "a.empleado_id = ?";
    $params[] = $empleado_id;
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " ORDER BY a.fecha DESC, a.hora_entrada DESC";

if (!$mostrar_todas) {
    $sql .= " LIMIT 100";
}

$stmt = $stmt = $conn->prepare($sql);
$stmt->execute($params);
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$sql_stats = "SELECT 
    COUNT(*) as total_hoy,
    COUNT(CASE WHEN hora_salida IS NOT NULL THEN 1 END) as completos_hoy,
    COUNT(*) as retardos_pendientes
    FROM asistencia WHERE DATE(fecha) = CURDATE()";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->execute();
$stats_hoy = $stmt_stats->fetch();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia - Sistema Biométrico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #9F2241, #764ba2); color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 10px 10px 0 0; }
        .stats { display: flex; gap: 15px; margin-bottom: 20px; }
        .stat { flex: 1; text-align: center; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #9F2241; }
        .stat-label { font-size: 0.9rem; color: #666; margin-top: 5px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .table tr:hover { background: #f5f5f5; }
        .btn { background: #9F2241; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #764ba2; }
        .btn-secondary { background: #6c757d; }
        .time { font-weight: bold; }
        .time-entry { color: #28a745; }
        .time-exit { color: #17a2b8; }
        .badge { padding: 4px 6px; border-radius: 3px; font-size: 0.8rem; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-danger { background: #dc3545; color: white; }
        .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .nav-tab { padding: 10px 20px; background: white; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; }
        .nav-tab.active { background: #9F2241; color: white; }
        .nav-tab:hover { border-color: #9F2241; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="card">
            <div class="card-header">
                <h1>📋 Asistencia</h1>
                <div class="float-end">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="logout.php" class="btn btn-secondary">🚪 Salir</a>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat">
                <div class="stat-number"><?= $stats_hoy['total_hoy'] ?></div>
                <div class="stat-label">Hoy</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $stats_hoy['completos_hoy'] ?></div>
                <div class="stat-label">Completos</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $stats_hoy['retardos_pendientes'] ?></div>
                <div class="stat-label">Retardos</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="?modo=hoy" class="nav-tab <?= $modo === 'hoy' ? 'active' : '' ?>">📅 Hoy</a>
            <a href="?modo=historial" class="nav-tab <?= $modo === 'historial' ? 'active' : '' ?>">📊 Historial (30 días)</a>
            <a href="?modo=completo<?= $empleado_id ? "&empleado_id=$empleado_id" : '' ?>" class="nav-tab <?= $modo === 'completo' ? 'active' : '' ?>">📋 Todas</a>
            <a href="?mostrar_todas=1" class="nav-tab <?= $mostrar_todas ? 'active' : '' ?>">📊 Todas (sin límite)</a>
        </div>

        <!-- Filters -->
        <div class="filters">
            <select onchange="window.location.href='?modo=<?= $modo ?>&empleado_id=' + this.value">
                <option value="">Todos los empleados</option>
                <?php
                $sql_emp = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM empleados WHERE activo = 1 ORDER BY nombre, apellido";
                $stmt_emp = $conn->prepare($sql_emp);
                $stmt_emp->execute();
                while ($emp = $stmt_emp->fetch()) {
                    echo "<option value='{$emp['id']}' " . ($empleado_id == $emp['id'] ? 'selected' : '') . ">" . htmlspecialchars($emp['nombre_completo']) . "</option>";
                }
                ?>
            </select>
        </div>

        <!-- Attendance Table -->
        <div class="card">
            <div class="card-header">
                <h5>📋 Registros <?= $modo === 'historial' ? '(últimos 30 días)' : ($modo === 'completo' ? '(sin límite)' : '') ?></h5>
                <span class="badge"><?= count($asistencias) ?> registros</span>
            </div>
            
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
                                <td><strong><?= htmlspecialchars($asistencia['nombre_completo']) ?></strong></td>
                                <td><?= htmlspecialchars($asistencia['area']) ?></td>
                                <td><?= $asistencia['fecha'] ?></td>
                                <td class="time-entry"><?= substr($asistencia['hora_entrada'] ?? '--:--', 0, 5) ?></td>
                                <td class="time-exit"><?= substr($asistencia['hora_salida'] ?? '--:--', 0, 5) ?></td>
                                <td>
                                    <?php if ($asistencia['hora_salida']): ?>
                                        <span class="badge badge-success">✅</span>
                                    <?php elseif ($asistencia['hora_entrada']): ?>
                                        <span class="badge badge-warning">⏰</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">⏳</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 40px;">
                    <h4>No hay registros de asistencia</h4>
                    <p>Intente con otros filtros o seleccione un modo diferente.</p>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Actualización automática cada 30 segundos para modo 'hoy'
        <?php if ($modo === 'hoy'): ?>
        setTimeout(() => {
            location.reload();
        }, 30000);
        <?php endif; ?>
        
        // Animación simple para las estadísticas
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    if (current < target) {
                        current += increment;
                        stat.textContent = Math.ceil(current);
                    } else {
                        clearInterval(timer);
                    }
                }, 30);
            });
        });
    </script>
</body>
</html>