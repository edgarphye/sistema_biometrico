<?php include 'views/layout.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-gavel"></i> Sanciones</h4>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/sanciones/crear" class="btn btn-sm btn-success mr-2">Crear Sanción</a>
                        <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-sm btn-secondary">Volver</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($sanciones)): ?>
                        <p class="text-muted">No hay sanciones registradas.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Empleado</th>
                                        <th>Tipo</th>
                                        <th>Fecha inicio</th>
                                        <th>Días</th>
                                        <th>Motivo</th>
                                        <th>Creado por</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sanciones as $s): ?>
                                        <tr>
                                            <td><?php echo $s['id']; ?></td>
                                            <td><?php echo htmlspecialchars($s['nombre'] . ' ' . $s['apellido']); ?></td>
                                            <td><?php echo ucfirst($s['tipo']); ?></td>
                                            <td><?php echo $s['fecha_inicio']; ?></td>
                                            <td><?php echo intval($s['dias']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($s['motivo'],0,80)); ?></td>
                                            <td><?php echo $s['creado_por'] ?? '-'; ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL . '/sanciones/' . $s['id']; ?>" class="btn btn-sm btn-primary">Ver</a>
                                                <a href="<?php echo BASE_URL . '/sanciones/' . $s['id'] . '/edit'; ?>" class="btn btn-sm btn-info">Editar</a>
                                                <form method="POST" action="<?php echo BASE_URL . '/sanciones/' . $s['id'] . '/delete'; ?>" style="display:inline-block; margin:0;">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar sanción #<?php echo $s['id']; ?>?');">Eliminar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
