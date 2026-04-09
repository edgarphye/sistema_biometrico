<?php
require_once 'config.php';
require_once 'models/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$reporte = $_GET['reporte'] ?? 'no_justificados';

if ($reporte === 'no_justificados' || $reporte === 'faltas') {
    $sql = "SELECT e.id, e.nombre, e.apellido, e.curp, a.fecha, a.hora_entrada, a.hora_salida
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            WHERE a.tipo_asistencia = 'falta'
            ORDER BY e.apellido, e.nombre, a.fecha";
    $stmt = $conn->query($sql);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tipoReporte = 'REPORTE DE FALTAS NO JUSTIFICADAS';
    $total = count($registros);
} elseif ($reporte === 'retardos') {
    $sql = "SELECT e.id, e.nombre, e.apellido, e.curp, r.fecha, r.hora_entrada, r.hora_salida, r.minutos_retardo
            FROM retardos r
            JOIN empleados e ON r.empleado_id = e.id
            ORDER BY e.apellido, e.nombre, r.fecha";
    $stmt = $conn->query($sql);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tipoReporte = 'REPORTE DE RETARDOS';
    $total = count($registros);
} elseif ($reporte === 'retardos_no_justificados') {
    $sql = "SELECT e.id, e.nombre, e.apellido, e.curp, r.fecha, r.hora_entrada, r.hora_salida, r.minutos_retardo
            FROM retardos r
            JOIN empleados e ON r.empleado_id = e.id
            WHERE r.justificado = 0 OR r.justificado IS NULL
            ORDER BY e.apellido, e.nombre, r.fecha";
    $stmt = $conn->query($sql);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tipoReporte = 'REPORTE DE RETARDOS NO JUSTIFICADOS';
    $total = count($registros);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tipoReporte ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
            color: #333;
        }
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .report-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }
        .report-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .report-header .subtitle {
            font-size: 14px;
            opacity: 0.9;
        }
        .report-meta {
            display: flex;
            justify-content: space-between;
            padding: 20px 40px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        .meta-item {
            text-align: center;
        }
        .meta-item .label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta-item .value {
            font-size: 24px;
            font-weight: 700;
            color: #1e3c72;
        }
        .report-body {
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #343a40;
            color: white;
        }
        th {
            padding: 15px 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        tbody tr:nth-child(even) {
            background: #fafbfc;
        }
        .btn-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e3c72;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(30,60,114,0.4);
            transition: all 0.3s;
        }
        .btn-print:hover {
            background: #2a5298;
            transform: translateY(-2px);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-falta {
            background: #fee;
            color: #c00;
        }
        .status-retardo {
            background: #ffeeba;
            color: #856404;
        }
        @media print {
            body { padding: 0; }
            .btn-print { display: none; }
            .report-container { box-shadow: none; }
        }
        .footer {
            padding: 20px 40px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <h1><?= $tipoReporte ?></h1>
            <div class="subtitle">Sistema Biométrico DGIFA</div>
        </div>
        
        <div class="report-meta">
            <div class="meta-item">
                <div class="label">Total Registros</div>
                <div class="value"><?= number_format($total) ?></div>
            </div>
            <div class="meta-item">
                <div class="label">Fecha Generación</div>
                <div class="value"><?= date('d/m/Y') ?></div>
            </div>
            <div class="meta-item">
                <div class="label">Hora</div>
                <div class="value"><?= date('H:i') ?></div>
            </div>
        </div>
        
        <div class="report-body">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>CURP</th>
                        <th>Fecha</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <?php if ($reporte === 'retardos' || $reporte === 'retardos_no_justificados'): ?>
                        <th>Minutos</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($registros as $r): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['apellido'] . ' ' . $r['nombre']) ?></td>
                        <td><?= htmlspecialchars($r['curp'] ?? 'N/A') ?></td>
                        <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                        <td><?= $r['hora_entrada'] ?: '-' ?></td>
                        <td><?= $r['hora_salida'] ?: '-' ?></td>
                        <?php if ($reporte === 'retardos' || $reporte === 'retardos_no_justificados'): ?>
                        <td><?= $r['minutos_retardo'] ?? '-' ?> min</td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            Reporte generado automáticamente por el Sistema Biométrico DGIFA
        </div>
    </div>
    
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
</body>
</html>
