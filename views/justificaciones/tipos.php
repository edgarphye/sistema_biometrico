<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-tags"></i> Tipos de Justificación</h2>
            <a href="<?= BASE_URL ?>/justificaciones/crear-tipo" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Tipo
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($tipos)): ?>
                <p class="text-muted">No hay tipos de justificación registrados.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Requiere Aprobación</th>
                                <th>Activo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tipos as $tipo): ?>
                                <tr>
                                    <td><?= (int)$tipo['id'] ?></td>
                                    <td><?= htmlspecialchars($tipo['nombre']) ?></td>
                                    <td><?= htmlspecialchars($tipo['descripcion'] ?? '') ?></td>
                                    <td>
                                        <?php if (!empty($tipo['requiere_aprobacion'])): ?>
                                            <span class="badge bg-warning">Sí</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tipo['activo'])): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
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
