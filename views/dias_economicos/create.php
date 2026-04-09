<?php include 'layout.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        Días Económicos
                    </h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#solicitarDiaEconomicoModal">
                        <i class="fas fa-plus me-1"></i>
                        Solicitar Día Económico
                    </button>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Empleado</label>
                            <select class="form-select" id="filtroEmpleado">
                                <option value="">Todos los empleados</option>
                                <?php foreach ($empleados as $empleado): ?>
                                    <option value="<?= $empleado['id'] ?>"><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estatus</label>
                            <select class="form-select" id="filtroEstatus">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Periodo</label>
                            <input type="month" class="form-control" id="filtroPeriodo" value="<?= date('Y-m') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-outline-secondary w-100" onclick="filtrarDiasEconomicos()">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Días Económicos -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="tablaDiasEconomicos">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empleado</th>
                                    <th>Fecha</th>
                                    <th>Motivo</th>
                                    <th>Estatus</th>
                                    <th>Fecha Solicitud</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($diasEconomicos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No hay solicitudes de días económicos registradas
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($diasEconomicos as $dia): ?>
                                        <tr>
                                            <td><?= $dia['id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($dia['nombre'] . ' ' . $dia['apellido']) ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($dia['rfc']) ?></small>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($dia['fecha'])) ?></td>
                                            <td><?= htmlspecialchars($dia['motivo'] ?? 'No especificado') ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'pendiente' => 'warning',
                                                    'aprobado' => 'success',
                                                    'rechazado' => 'danger'
                                                ];
                                                $estatus = $dia['estatus'] ?? 'pendiente';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass[$estatus] ?>">
                                                    <?= ucfirst($estatus) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($dia['fecha_solicitud'])) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-info" onclick="verDetalles(<?= $dia['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($estatus === 'pendiente' && $_SESSION['rol'] === 'admin'): ?>
                                                        <button class="btn btn-sm btn-outline-success" onclick="aprobarDia(<?= $dia['id'] ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="rechazarDia(<?= $dia['id'] ?>)">
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

<!-- Modal Solicitar Día Económico -->
<div class="modal fade" id="solicitarDiaEconomicoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Solicitar Día Económico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSolicitarDiaEconomico">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Empleado</label>
                        <select class="form-select" name="empleado_id" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?= $empleado['id'] ?>">
                                    <?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" class="form-control" name="fecha" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <textarea class="form-control" name="motivo" rows="3" placeholder="Describa el motivo de la solicitud..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Información importante:</strong><br>
                        • Periodo actual: 16 julio 2025 - 15 julio 2026<br>
                        • 12 días económicos disponibles por periodo<br>
                        • Máximo 3 días continuos<br>
                        • 1 semana de espera para días individuales
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #9F2241; border-color: #9F2241;">
                        <i class="fas fa-paper-plane me-1"></i>
                        Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Aprobar/Rechazar -->
<div class="modal fade" id="accionDiaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title" id="accionDiaTitle"><i class="fas fa-tasks me-2"></i>Acción sobre Día Económico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAccionDia">
                <input type="hidden" name="dia_id" id="accionDiaId">
                <input type="hidden" name="accion" id="accionDiaAccion">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Comentarios</label>
                        <textarea class="form-control" name="comentarios" rows="3" placeholder="Agregue comentarios sobre esta decisión..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="accionDiaBtn" style="background-color: #9F2241; border-color: #9F2241;">
                        <i class="fas fa-check me-1"></i> Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funciones JavaScript
function filtrarDiasEconomicos() {
    const empleado = document.getElementById('filtroEmpleado').value;
    const estatus = document.getElementById('filtroEstatus').value;
    const periodo = document.getElementById('filtroPeriodo').value;
    
    // Aquí iría la lógica de filtrado AJAX
    console.log('Filtrando:', { empleado, estatus, periodo });
}

function verDetalles(id) {
    // Aquí iría la lógica para ver detalles
    console.log('Ver detalles del día económico:', id);
}

function aprobarDia(id) {
    document.getElementById('accionDiaTitle').textContent = 'Aprobar Día Económico';
    document.getElementById('accionDiaAccion').value = 'aprobar';
    document.getElementById('accionDiaId').value = id;
    document.getElementById('accionDiaBtn').className = 'btn btn-success';
    document.getElementById('accionDiaBtn').innerHTML = '<i class="fas fa-check me-1"></i>Aprobar';
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('accionDiaModal')).show();
    } else {
        console.error('Bootstrap no disponible para abrir modal');
        alert('Error: Espere a que la página cargue completamente e intente nuevamente.');
    }
}

function rechazarDia(id) {
    document.getElementById('accionDiaTitle').textContent = 'Rechazar Día Económico';
    document.getElementById('accionDiaAccion').value = 'rechazar';
    document.getElementById('accionDiaId').value = id;
    document.getElementById('accionDiaBtn').className = 'btn btn-danger';
    document.getElementById('accionDiaBtn').innerHTML = '<i class="fas fa-times me-1"></i>Rechazar';
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('accionDiaModal')).show();
    } else {
        console.error('Bootstrap no disponible para abrir modal');
        alert('Error: Espere a que la página cargue completamente e intente nuevamente.');
    }
}

// Manejo del formulario de solicitud
document.getElementById('formSolicitarDiaEconomico').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/dias-economicos/solicitar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getInstance(document.getElementById('solicitarDiaEconomicoModal')).hide();
                }
            this.reset();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error del servidor');
    });
});

// Manejo del formulario de acción
document.getElementById('formAccionDia').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const accion = formData.get('accion');
    
    const url = accion === 'aprobar' ? '/dias-economicos/aprobar' : '/dias-economicos/rechazar';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getInstance(document.getElementById('accionDiaModal')).hide();
                }
            this.reset();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error del servidor');
    });
});
</script>

<?php include 'footer.php'; ?>