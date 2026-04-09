<?php include 'views/layout.php'; ?>
<?php require_once 'helpers/Csrf.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm" style="border-color: #d0d0d2;">
                <div class="card-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: #ffffff;">
                    <h4 class="mb-0"><i class="fas fa-sync-alt me-2"></i> Asignar Ciclo Rotativo</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/sistema_biometrico/horarios/asignar-ciclo">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        
                        <div class="form-group mb-3">
                            <label for="empleado_id" class="form-label">Empleado *</label>
                            <select class="form-select" id="empleado_id" name="empleado_id" required>
                                <option value="">Seleccionar empleado...</option>
                                <?php foreach ($empleados as $empleado): ?>
                                    <option value="<?php echo $empleado['id']; ?>">
                                        <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="ciclo_id" class="form-label">Ciclo / Turno Rotativo *</label>
                            <select class="form-select" id="ciclo_id" name="ciclo_id" required>
                                <option value="">Seleccionar ciclo...</option>
                                <?php foreach ($ciclos as $ciclo): ?>
                                    <option value="<?php echo $ciclo['id']; ?>">
                                        <?php echo htmlspecialchars($ciclo['nombre'] . ' (' . $ciclo['num_ciclo'] . ' ' . $ciclo['unidad_ciclo'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted">El ciclo define el patrón de rotación (ej. Matutino -> Vespertino -> Descanso).</div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio del Ciclo *</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required value="<?php echo date('Y-m-d'); ?>">
                            <div class="form-text text-muted">A partir de esta fecha se calculará la rotación.</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/sistema_biometrico/horarios" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-info text-white">
                                <i class="fas fa-save"></i> Guardar Asignación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
