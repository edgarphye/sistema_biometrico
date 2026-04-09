<?php ob_start(); ?>
<?php 
require_once 'helpers/permisos_helper.php';
require_once 'models/Usuario.php';
?>
<style>
    /* Correcciones para Modo Oscuro */
    body.dark-theme .btn-primary,
    body.dark-theme .btn-secondary {
        color: #ffffff !important;
    }
    body.dark-theme .btn-outline-warning {
        color: #ffc107;
        border-color: #ffc107;
    }
    body.dark-theme .btn-outline-warning:hover {
        color: #000000;
    }
    body.dark-theme .table-light th {
        background-color: #34495e;
        color: #e9ecef;
        border-color: #444;
    }
    /* Selección de fila con color Pantone */
    .table-horarios tbody tr.selected-row {
        background-color: rgba(151, 34, 65, 0.2) !important;
        border-left: 3px solid #9F2241;
    }
    .table-horarios tbody tr.selected-row td {
        font-weight: 600;
    }
    body.dark-theme .table-horarios tbody tr.selected-row {
        background-color: rgba(151, 34, 65, 0.4) !important;
        border-left: 3px solid #9F2241;
    }
</style>
<div class="container-fluid mt-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> Operación realizada con éxito.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> Error: <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="row">
        <!-- Formulario a la izquierda (lado del menú) -->
        <div class="col-md-4">
            <div class="card shadow-sm">
<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white"><i class="fas fa-clock"></i> Gestión de Horarios</h5>
                    <?php if (tienePermiso('horarios_crear')): ?>
                    <button type="button" class="btn btn-sm btn-light text-primary fw-bold" onclick="resetForm()">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form id="horarioForm" action="<?= BASE_URL ?>/horarios/create" method="POST">
                        <input type="hidden" name="id" id="horario_id">
                        <input type="hidden" name="_token" value="<?= Csrf::token() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre del Horario</label>
                            <input type="text" class="form-control" name="nombre" id="nombre" required placeholder="Ej. Matutino">
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6 mb-3">
                                <label class="form-label">Entrada</label>
                                <input type="time" class="form-control" name="hora_entrada" id="hora_entrada" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Salida</label>
                                <input type="time" class="form-control" name="hora_salida" id="hora_salida" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tolerancia (minutos)</label>
                            <input type="number" class="form-control" name="tolerancia_minutos" id="tolerancia_minutos" value="10" min="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Color Identificador</label>
                            <input type="color" class="form-control form-control-color w-100" name="color" id="color" value="#0d6efd">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="btnGuardar">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                            <button type="button" class="btn btn-secondary" id="btnCancelar" onclick="resetForm()" style="display:none;">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla a la derecha -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Horarios Registrados</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-horarios mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Tolerancia</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
<?php if(isset($horarios) && !empty($horarios)): ?>
                                    <?php foreach($horarios as $h): ?>
                                    <tr onclick='seleccionarHorario(<?= json_encode($h) ?>)' style="cursor: pointer;">
                                        <td>
                                            <span class="badge rounded-pill me-2" style="background-color: <?= htmlspecialchars($h['color'] ?? '#ccc') ?>">&nbsp;</span>
                                            <?= htmlspecialchars($h['nombre']) ?>
                                        </td>
                                        <td><?= substr($h['hora_entrada'], 0, 5) ?></td>
                                        <td><?= substr($h['hora_salida'], 0, 5) ?></td>
<td><?= $h['tolerancia_minutos'] ?> min</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-warning" onclick='event.stopPropagation(); editar(<?= json_encode($h) ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/horarios/delete/<?= $h['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); return confirm('¿Eliminar este horario?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No hay horarios registrados.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function seleccionarHorario(data) {
    // Quitar selección anterior
    document.querySelectorAll('.table-horarios tbody tr').forEach(row => {
        row.classList.remove('selected-row');
    });
    
    // Agregar selección a la fila clickeada
    event.target.closest('tr').classList.add('selected-row');
    
    // Llamar a la función editar
    editar(data);
}

function editar(data) {
    document.getElementById('horario_id').value = data.id;
    document.getElementById('nombre').value = data.nombre;
    document.getElementById('hora_entrada').value = (data.hora_entrada || '').substring(0, 5);
    document.getElementById('hora_salida').value = (data.hora_salida || '').substring(0, 5);
    document.getElementById('tolerancia_minutos').value = data.tolerancia_minutos;
    document.getElementById('color').value = data.color || '#0d6efd';
    
    document.getElementById('horarioForm').action = '<?= BASE_URL ?>/horarios/edit';
    document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-sync"></i> Actualizar';
    document.getElementById('btnCancelar').style.display = 'block';
}

function resetForm() {
    document.getElementById('horarioForm').reset();
    document.getElementById('horario_id').value = '';
    document.getElementById('horarioForm').action = '<?= BASE_URL ?>/horarios/create';
    document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-save"></i> Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    
    // Quitar selección de la tabla
    document.querySelectorAll('.table-horarios tbody tr').forEach(row => {
        row.classList.remove('selected-row');
    });
}
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>