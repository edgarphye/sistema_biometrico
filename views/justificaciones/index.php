<?php include 'views/layout.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-exclamation-triangle"></i> Retardos Pendientes de Justificación</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Fecha</th>
                                    <th>Minutos Retardo</th>
                                    <th>Tipo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($retardos as $retardo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($retardo['nombre'] . ' ' . $retardo['apellido']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($retardo['fecha'])); ?></td>
                                    <td><?php echo $retardo['minutos_retardo']; ?> min</td>
                                    <td>
                                        <span class="badge badge-<?php echo $retardo['tipo'] == 'mayor' ? 'danger' : 'warning'; ?>">
                                            <?php echo ucfirst($retardo['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/justificaciones/justificar/<?php echo $retardo['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Justificar</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (empty($retardos)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No hay retardos pendientes de justificación.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
