<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-chart-line"></i> Dashboard Agente IA</h2>
            <p class="text-muted">
                Reporte del período: <?= htmlspecialchars($periodo['inicio']) ?> al <?= htmlspecialchars($periodo['fin']) ?>
            </p>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Empleados</h5>
                    <h3><?= $reporte['total_empleados'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Asistencias</h5>
                    <h3><?= $reporte['total_asistencias'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5>Retardos</h5>
                    <h3><?= $reporte['total_retardos'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Faltas</h5>
                    <h3><?= $reporte['total_faltas'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Resumen de Incidencias</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Retardos Menores</td>
                                <td><?= $reporte['retardos_menores'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td>Retardos Mayores</td>
                                <td><?= $reporte['retardos_mayores'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td>Faltas</td>
                                <td><?= $reporte['faltas'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td>Comisiones</td>
                                <td><?= $reporte['comisiones'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td>Días Económicos</td>
                                <td><?= $reporte['dias_economicos'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td>Justificaciones</td>
                                <td><?= $reporte['justificaciones'] ?? 0 ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Alertas Recientes</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($reporte['alertas'])): ?>
                        <ul class="list-group">
                            <?php foreach ($reporte['alertas'] as $alerta): ?>
                                <li class="list-group-item list-group-item-<?= $alerta['tipo'] ?? 'info' ?>">
                                    <?= htmlspecialchars($alerta['mensaje'] ?? '') ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Sin alertas en el período</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
