<?php include __DIR__ . '/../layout.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Detalles del Horario</h5>
                    <a href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/horarios" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold text-muted">Nombre del Horario:</div>
                        <div class="col-sm-8 fs-5"><?php echo htmlspecialchars($horario['nombre']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold text-muted">Horario:</div>
                        <div class="col-sm-8">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-3 p-2">
                                    <i class="fas fa-sign-in-alt me-1"></i>Entrada: <?php echo htmlspecialchars($horario['hora_entrada']); ?>
                                </span>
                                <span class="badge bg-danger p-2">
                                    <i class="fas fa-sign-out-alt me-1"></i>Salida: <?php echo htmlspecialchars($horario['hora_salida']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold text-muted">Tolerancia:</div>
                        <div class="col-sm-8">
                            <span class="text-dark fw-bold"><?php echo htmlspecialchars($horario['tolerancia_minutos']); ?> minutos</span>
                            <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i>Tiempo permitido después de la hora de entrada antes de considerar retardo menor.</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold text-muted">Sede:</div>
                        <div class="col-sm-8">
                            <?php if (!empty($horario['sede'])): ?>
                                <span class="badge bg-info text-dark"><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($horario['sede']); ?></span>
                            <?php else: ?>
                                <span class="text-muted fst-italic">No asignada (Aplica a todas)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold text-muted">Descripción:</div>
                        <div class="col-sm-8">
                            <div class="p-2 bg-light rounded border">
                                <?php echo !empty($horario['descripcion']) ? nl2br(htmlspecialchars($horario['descripcion'])) : '<span class="text-muted fst-italic">Sin descripción</span>'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold text-muted">Estado:</div>
                        <div class="col-sm-8">
                            <?php if ($horario['activo']): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Inactivo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-end">
                    <a href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/horarios/edit/<?php echo $horario['id']; ?>" class="btn btn-warning me-2">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash-alt me-1"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el horario <strong><?php echo htmlspecialchars($horario['nombre']); ?></strong>?</p>
                <p class="text-danger small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>Esta acción desactivará el horario y no podrá ser asignado a nuevos empleados.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/horarios/delete/<?php echo $horario['id']; ?>" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Eliminar</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>