<?php include 'views/layout.php'; ?>
<?php require_once 'helpers/Csrf.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-tools"></i> Mantenimiento de Horarios Laborales</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Tabla de horarios (izquierda) -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-list"></i> Horarios Existentes</h5>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="nuevoHorario()">
                                        <i class="fas fa-plus"></i> Nuevo Horario
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Hora Ent</th>
                                                    <th>Hora Sal</th>
                                                    <th>Tolerancia</th>
                                                    <th>Inicio Marc/Ent</th>
                                                    <th>Fin Marc/Ent</th>
                                                    <th>Inicio Marc/Sal</th>
                                                    <th>Fin Marc/Sal</th>
                                                    <th>Color</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($horarios as $horario): ?>
                                                <tr class="horario-row <?php echo ($horarioSeleccionado && $horarioSeleccionado['id'] == $horario['id']) ? 'table-active' : ''; ?>"
                                                    onclick="seleccionarHorario(<?php echo $horario['id']; ?>)"
                                                    style="cursor: pointer;">
                                                    <td><?php echo htmlspecialchars($horario['nombre']); ?></td>
                                                    <td><?php echo htmlspecialchars($horario['hora_entrada']); ?></td>
                                                    <td><?php echo htmlspecialchars($horario['hora_salida']); ?></td>
                                                    <td><?php echo htmlspecialchars($horario['tolerancia_minutos']); ?> min</td>
                                                    <td><?php echo htmlspecialchars($horario['inicio_marcaje_entrada'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($horario['fin_marcaje_entrada'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($horario['inicio_marcaje_salida'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($horario['fin_marcaje_salida'] ?? '-'); ?></td>
                                                    <td>
                                                        <div style="width: 20px; height: 20px; background-color: <?php echo htmlspecialchars($horario['color'] ?? '#007bff'); ?>; border-radius: 3px;"></div>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-warning btn-sm" onclick="editarHorario(<?php echo $horario['id']; ?>); event.stopPropagation();">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarHorario(<?php echo $horario['id']; ?>); event.stopPropagation();">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de edición (derecha) -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-edit"></i> Editor de Horario</h5>
                                </div>
                                <div class="card-body">
                                    <form id="horarioForm" method="POST" action="/sistema_biometrico/horarios/procesar-mantenimiento">
                                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                                        <input type="hidden" name="action" id="actionInput" value="create">
                                        <input type="hidden" name="id" id="horarioIdInput" value="">

                                        <div class="form-group">
                                            <label for="nombre">Nombre del Horario *</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                                   value="<?php echo htmlspecialchars($horarioSeleccionado['nombre'] ?? ''); ?>">
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="hora_entrada">Hora de Entrada *</label>
                                                <input type="time" class="form-control" id="hora_entrada" name="hora_entrada" required
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['hora_entrada'] ?? '09:00'); ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="hora_salida">Hora de Salida *</label>
                                                <input type="time" class="form-control" id="hora_salida" name="hora_salida" required
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['hora_salida'] ?? '17:00'); ?>">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="tolerancia_minutos">Tolerancia Ent (min)</label>
                                                <input type="number" class="form-control" id="tolerancia_minutos" name="tolerancia_minutos"
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['tolerancia_minutos'] ?? 10); ?>" min="0" max="60">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="tolerancia_salida">Tolerancia Sal (min)</label>
                                                <input type="number" class="form-control" id="tolerancia_salida" name="tolerancia_salida"
                                                       value="0" min="0" max="60">
                                            </div>
                                        </div>

                                        <hr>
                                        <h6>Márgenes de Marcaje</h6>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="inicio_marcaje_entrada">Inicio Marc/Ent</label>
                                                <input type="time" class="form-control" id="inicio_marcaje_entrada" name="inicio_marcaje_entrada"
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['inicio_marcaje_entrada'] ?? '05:00'); ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="fin_marcaje_entrada">Fin Marc/Ent</label>
                                                <input type="time" class="form-control" id="fin_marcaje_entrada" name="fin_marcaje_entrada"
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['fin_marcaje_entrada'] ?? '09:31'); ?>">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="inicio_marcaje_salida">Inicio Marc/Sal</label>
                                                <input type="time" class="form-control" id="inicio_marcaje_salida" name="inicio_marcaje_salida"
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['inicio_marcaje_salida'] ?? '17:00'); ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="fin_marcaje_salida">Fin Marc/Sal</label>
                                                <input type="time" class="form-control" id="fin_marcaje_salida" name="fin_marcaje_salida"
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['fin_marcaje_salida'] ?? '23:00'); ?>">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="cuenta_dia_trabajo">Cuenta Día Trabajo</label>
                                                <input type="number" class="form-control" id="cuenta_dia_trabajo" name="cuenta_dia_trabajo"
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['cuenta_dia_trabajo'] ?? 1); ?>" min="0" max="1">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="cuenta_minutos">Cuenta Minutos</label>
                                                <input type="number" class="form-control" id="cuenta_minutos" name="cuenta_minutos"
                                                       value="<?php echo htmlspecialchars($horarioSeleccionado['cuenta_minutos'] ?? 0); ?>" min="0">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="color">Color</label>
                                            <input type="color" class="form-control" id="color" name="color"
                                                   value="<?php echo htmlspecialchars($horarioSeleccionado['color'] ?? '#007bff'); ?>">
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="debe_marcar_entrada" name="debe_marcar_entrada"
                                                   <?php echo ($horarioSeleccionado['debe_marcar_entrada'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="debe_marcar_entrada">Debe marcar entrada</label>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="debe_marcar_salida" name="debe_marcar_salida"
                                                   <?php echo ($horarioSeleccionado['debe_marcar_salida'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="debe_marcar_salida">Debe marcar salida</label>
                                        </div>

                                        <div class="form-group">
                                            <label for="descripcion">Descripción</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($horarioSeleccionado['descripcion'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="btn-group w-100">
                                            <button type="submit" class="btn btn-success" id="guardarBtn">
                                                <i class="fas fa-save"></i> Guardar
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="eliminarHorarioActual()">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">
                                                <i class="fas fa-eraser"></i> Limpiar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function seleccionarHorario(id) {
    window.location.href = '/sistema_biometrico/horarios/mantenimiento?edit=' + id;
}

function nuevoHorario() {
    window.location.href = '/sistema_biometrico/horarios/mantenimiento';
}

function editarHorario(id) {
    window.location.href = '/sistema_biometrico/horarios/mantenimiento?edit=' + id;
}

function eliminarHorario(id) {
    if (confirm('¿Está seguro de eliminar este horario?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/sistema_biometrico/horarios/procesar-mantenimiento';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = '<?php echo htmlspecialchars(Csrf::token()); ?>';
        form.appendChild(tokenInput);

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
    }
}

function eliminarHorarioActual() {
    const horarioId = document.getElementById('horarioIdInput').value;
    if (horarioId) {
        eliminarHorario(horarioId);
    } else {
        alert('No hay horario seleccionado para eliminar');
    }
}

function limpiarFormulario() {
    document.getElementById('horarioForm').reset();
    document.getElementById('actionInput').value = 'create';
    document.getElementById('horarioIdInput').value = '';
    document.getElementById('guardarBtn').innerHTML = '<i class="fas fa-save"></i> Guardar';
}

// Cargar datos del horario seleccionado
<?php if ($horarioSeleccionado): ?>
document.getElementById('actionInput').value = 'update';
document.getElementById('horarioIdInput').value = '<?php echo $horarioSeleccionado['id']; ?>';
document.getElementById('guardarBtn').innerHTML = '<i class="fas fa-save"></i> Actualizar';
<?php endif; ?>
</script>

</body>
</html>
