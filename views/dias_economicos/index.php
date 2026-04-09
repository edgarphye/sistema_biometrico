<?php include 'layout.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-calendar-day me-2"></i>
                    Días Económicos
                </h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSolicitarDias">
                    <i class="fas fa-plus me-1"></i>
                    Solicitar Día Económico
                </button>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= count($diasEconomicos ?? []) ?></h4>
                                    <p class="mb-0">Total Solicitudes</p>
                                </div>
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= count(array_filter($diasEconomicos ?? [], fn($d) => ($d['estatus'] ?? 'pendiente') === 'pendiente')) ?></h4>
                                    <p class="mb-0">Pendientes</p>
                                </div>
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= count(array_filter($diasEconomicos ?? [], fn($d) => ($d['estatus'] ?? 'pendiente') === 'aprobado')) ?></h4>
                                    <p class="mb-0">Aprobados</p>
                                </div>
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= count(array_filter($diasEconomicos ?? [], fn($d) => ($d['estatus'] ?? 'pendiente') === 'rechazado')) ?></h4>
                                    <p class="mb-0">Rechazados</p>
                                </div>
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filtrosForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Empleado</label>
                            <select class="form-select" name="empleado_id">
                                <option value="">Todos los empleados</option>
                                <?php foreach ($empleados ?? [] as $empleado): ?>
                                    <option value="<?= $empleado['id'] ?>">
                                        <?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estatus</label>
                            <select class="form-select" name="estatus">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Mes</label>
                            <input type="month" class="form-control" name="mes" value="<?= date('Y-m') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary" onclick="exportarExcel()">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="exportarPDF()">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla Principal -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaDiasEconomicos">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Empleado</th>
                                    <th>Fecha</th>
                                    <th>Motivo</th>
                                    <th>Estatus</th>
                                    <th>Solicitado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($diasEconomicos ?? [])): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                            <br>
                                            No hay solicitudes de días económicos registradas
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($diasEconomicos as $dia): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?= $dia['id'] ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($dia['nombre'] . ' ' . $dia['apellido']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($dia['rfc'] ?? '') ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= date('d/m/Y', strtotime($dia['fecha'])) ?></div>
                                                <small class="text-muted">
                                                    <?= $this->getDiaSemana(date('w', strtotime($dia['fecha']))) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($dia['motivo'] ?: 'No especificado') ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'pendiente' => 'warning',
                                                    'aprobado' => 'success',
                                                    'rechazado' => 'danger'
                                                ];
                                                $estatus = $dia['estatus'] ?? 'pendiente';
                                                $icon = [
                                                    'pendiente' => 'clock',
                                                    'aprobado' => 'check-circle',
                                                    'rechazado' => 'times-circle'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $badgeClass[$estatus] ?>">
                                                    <i class="fas fa-<?= $icon[$estatus] ?> me-1"></i>
                                                    <?= ucfirst($estatus) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($dia['fecha_solicitud'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-info" onclick="verDetalles(<?= $dia['id'] ?>)" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if (($dia['estatus'] ?? 'pendiente') === 'pendiente' && ($_SESSION['rol'] ?? '') === 'admin'): ?>
                                                        <button class="btn btn-sm btn-outline-success" onclick="aprobarDia(<?= $dia['id'] ?>)" title="Aprobar">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="rechazarDia(<?= $dia['id'] ?>)" title="Rechazar">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Solicitar Días -->
<div class="modal fade" id="modalSolicitarDias" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Solicitar Días Económicos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formSolicitarDias">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">

                    <div class="mb-3">
                        <label class="form-label">Tipo de Solicitud</label>
                        <select class="form-select" name="tipo" id="tipo_solicitud" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="individual">Día Individual</option>
                            <option value="continuos">Días Continuos</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Días Solicitados</label>
                        <input type="number" class="form-control" name="dias_solicitados" id="dias_solicitados" min="1" max="3" required>
                        <div class="form-text" id="info-dias">
                            Seleccione el tipo de solicitud primero
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <strong>Días disponibles:</strong> <span id="dias-disponibles"><?php echo $dias_disponibles ?? 0; ?></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="solicitarDias()"><i class="fas fa-paper-plane me-1"></i> Solicitar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Actualizar información según tipo de solicitud
    $('#tipo_solicitud').change(function() {
        var tipo = $(this).val();
        var maxDias = tipo === 'continuos' ? 3 : 1;
        $('#dias_solicitados').attr('max', maxDias);

        if (tipo === 'continuos') {
            $('#info-dias').html('Máximo 3 días continuos. Periodo de espera: 1 mes para 3 días, 15 días para 2 días, 1 semana para 1 día.');
        } else {
            $('#info-dias').html('1 día individual. Periodo de espera: 1 semana desde último uso.');
        }
    });

    // Validar días disponibles
    $('#dias_solicitados').change(function() {
        var solicitados = parseInt($(this).val()) || 0;
        var disponibles = parseInt($('#dias-disponibles').text()) || 0;

        if (solicitados > disponibles) {
            alert('No tiene suficientes días disponibles.');
            $(this).val(disponibles);
        }
    });
});

function solicitarDias() {
    var formData = new FormData(document.getElementById('formSolicitarDias'));

    $.ajax({
        url: '<?php echo rtrim(BASE_URL, '/'); ?>/dias-economicos/solicitar',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#modalSolicitarDias').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error al procesar la solicitud');
        }
    });
}

function cancelarSolicitud(id) {
    if (confirm('¿Está seguro de cancelar esta solicitud?')) {
        $.post('<?php echo rtrim(BASE_URL, '/'); ?>/dias-economicos/cancelar', {
            id: id,
            csrf_token: '<?php echo htmlspecialchars(Csrf::token()); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../layout.php'; ?>
