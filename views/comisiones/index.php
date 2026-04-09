<?php require_once __DIR__ . '/../../helpers/Csrf.php'; ?>
<div class="container mt-4">
    <h2>Gestión de Comisiones AEFCM</h2>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Límites AEFCM</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Límite Mensual:</strong> $<?php echo number_format($limites['limite_mensual'], 2); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Días Máximos por Comisión:</strong> <?php echo $limites['dias_maximos']; ?> días
                        </div>
                        <div class="col-md-4">
                            <strong>Tipos de Comisión:</strong> <?php echo count($limites['tipos']); ?> categorías
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <a href="<?php echo BASE_URL; ?>/comisiones/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Comisión
            </a>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-secondary" onclick="mostrarLimites()">
                <i class="fas fa-question-circle"></i> Ver Límites por Tipo
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Comisiones Pendientes de Aprobación</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($comisiones)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-check-circle"></i> No hay comisiones pendientes de aprobación.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Monto</th>
                                        <th>Fecha Asignación</th>
                                        <th>Fecha Vencimiento</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comisiones as $comision): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($comision['nombre'] . ' ' . $comision['apellido']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo ucfirst($comision['tipo_comision']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($comision['descripcion']); ?></td>
                                            <td>$<?php echo number_format($comision['monto'], 2); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($comision['fecha_asignacion'])); ?></td>
                                            <td>
                                                <?php echo $comision['fecha_vencimiento'] ? date('d/m/Y', strtotime($comision['fecha_vencimiento'])) : 'N/A'; ?>
                                            </td>
                                            <td>
                                                <?php if ($comision['aprobado_por']): ?>
                                                    <span class="badge bg-success">Aprobada</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pendiente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$comision['aprobado_por']): ?>
                                                    <button class="btn btn-sm btn-success" onclick="aprobarComision(<?php echo $comision['id']; ?>)">
                                                        <i class="fas fa-check"></i> Aprobar
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo $comision['id']; ?>)">
                                                    <i class="fas fa-eye"></i> Ver
                                                </button>
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

<!-- Modal para aprobar comisión -->
<div class="modal fade" id="aprobarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Aprobar Comisión</h5>
                <button type="button" class="close" data-dismiss="modal" style="color: #ffffff;">
                    <span>&times;</span>
                </button>
            </div>
            <form id="aprobarForm">
                <div class="modal-body">
                    <input type="hidden" id="comision_id" name="comision_id">
                    <div class="form-group">
                        <label for="aprobado_por">Aprobado por (ID Usuario):</label>
                        <input type="number" class="form-control" id="aprobado_por" name="aprobado_por" required>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cancelar</button>
                    <button type="submit" class="btn btn-success" style="background-color: #235B4E; border-color: #235B4E;"><i class="fas fa-check me-1"></i> Aprobar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para límites por tipo -->
<div class="modal fade" id="limitesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Límites AEFCM por Tipo de Comisión</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tipo de Comisión</th>
                                <th>Requiere Aprobación</th>
                                <th>Límite de Días</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tiposComision as $tipo => $config): ?>
                                <tr>
                                    <td><?php echo ucfirst($tipo); ?></td>
                                    <td>
                                        <?php if ($config['requiere_aprobacion']): ?>
                                            <span class="badge bg-warning">Sí</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $config['limite_dias']; ?> días</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function aprobarComision(id) {
    document.getElementById('comision_id').value = id;
    $('#aprobarModal').modal('show');
}

function verDetalle(id) {
    // Implementar vista de detalle
    alert('Funcionalidad de detalle próximamente');
}

function mostrarLimites() {
    $('#limitesModal').modal('show');
}

document.getElementById('aprobarForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('_token', '<?php echo htmlspecialchars(Csrf::token()); ?>');

    fetch('<?php echo BASE_URL; ?>/comisiones/aprobar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        $('#aprobarModal').modal('hide');
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al aprobar la comisión');
    });
});
</script>
