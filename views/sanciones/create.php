<?php include 'views/layout.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Crear Sanción</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php require_once 'helpers/Csrf.php'; ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        <div class="form-group">
                            <label for="empleado_id">Empleado</label>
                            <select name="empleado_id" id="empleado_id" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($empleados as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select name="tipo" id="tipo" class="form-control">
                                <option value="suspension">Suspensión</option>
                                <option value="amonestacion">Amonestación</option>
                                <option value="terminacion_propuesta">Terminación propuesta</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_inicio">Fecha inicio</label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="dias">Días</label>
                            <input type="number" name="dias" id="dias" class="form-control" value="1">
                        </div>

                        <div class="form-group">
                            <label for="motivo">Motivo</label>
                            <textarea name="motivo" id="motivo" class="form-control" rows="4"></textarea>
                        </div>

                        <button class="btn btn-primary" type="submit">Crear</button>
                        <a href="<?php echo BASE_URL; ?>/sanciones" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
