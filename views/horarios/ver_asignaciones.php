<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-12">
            <h2><i class="fas fa-calendar-alt"></i> Asignaciones de Horarios</h2>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="empleado_id" class="form-label">Empleado</label>
                    <select class="form-control" id="empleado_id" name="empleado_id" onchange="this.form.submit()">
                        <option value="">Seleccionar empleado</option>
                        <?php foreach ($empleados as $e): ?>
                            <option value="<?= (int)$e['id'] ?>" <?= isset($_GET['empleado_id']) && (int)$_GET['empleado_id'] === (int)$e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre'] . ' ' . ($e['apellido'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="<?= BASE_URL ?>/horarios/asignar" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Asignación
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($empleado): ?>
        <div class="card">
            <div class="card-header">
                <h5>Asignaciones de: <?= htmlspecialchars($empleado['nombre'] . ' ' . ($empleado['apellido'] ?? '')) ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($asignaciones)): ?>
                    <p class="text-muted">Este empleado no tiene asignaciones de horario.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Horario</th>
                                    <th>Día</th>
                                    <th>Hora Entrada</th>
                                    <th>Hora Salida</th>
                                    <th>Activo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($asignaciones as $asignacion): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($asignacion['horario_nombre'] ?? $asignacion['nombre'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($asignacion['dia_semana'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($asignacion['hora_entrada'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($asignacion['hora_salida'] ?? '') ?></td>
                                        <td>
                                            <?php if (!empty($asignacion['activo'])): ?>
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
    <?php endif; ?>
</div>
