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
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['form_errors'])): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Errores de validación:</h6>
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['form_errors'] as $field => $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['form_errors']); ?>
                    <?php endif; ?>

                    <?php require_once 'helpers/Csrf.php'; ?>
                    <form method="POST">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        <div class="form-group">
                            <label for="empleado_id">Empleado</label>
                            <select name="empleado_id" id="empleado_id" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($empleados as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_SESSION['form_data']['empleado_id']) && $_SESSION['form_data']['empleado_id'] == $emp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select name="tipo" id="tipo" class="form-control">
                                <option value="suspension" <?php echo (isset($_SESSION['form_data']['tipo']) && $_SESSION['form_data']['tipo'] == 'suspension') ? 'selected' : ''; ?>>Suspensión</option>
                                <option value="amonestacion" <?php echo (isset($_SESSION['form_data']['tipo']) && $_SESSION['form_data']['tipo'] == 'amonestacion') ? 'selected' : ''; ?>>Amonestación</option>
                                <option value="nota_mala" <?php echo (isset($_SESSION['form_data']['tipo']) && $_SESSION['form_data']['tipo'] == 'nota_mala') ? 'selected' : ''; ?>>Nota Mala</option>
                                <option value="descuento" <?php echo (isset($_SESSION['form_data']['tipo']) && $_SESSION['form_data']['tipo'] == 'descuento') ? 'selected' : ''; ?>>Descuento</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_inicio">Fecha inicio</label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($_SESSION['form_data']['fecha_inicio'] ?? date('Y-m-d')); ?>">
                        </div>

                        <div class="form-group">
                            <label for="dias">Días</label>
                            <input type="number" name="dias" id="dias" class="form-control" value="<?php echo htmlspecialchars($_SESSION['form_data']['dias'] ?? 1); ?>" min="0" max="365">
                        </div>

                        <div class="form-group">
                            <label for="motivo">Motivo</label>
                            <textarea name="motivo" id="motivo" class="form-control" rows="4" placeholder="Describa detalladamente el motivo de la sanción (mínimo 10 caracteres)" required><?php echo htmlspecialchars($_SESSION['form_data']['motivo'] ?? ''); ?></textarea>
                        </div>

                        <button class="btn btn-primary" type="submit" style="background-color: #9F2241; border-color: #9F2241;">Crear</button>
                        <a href="<?php echo BASE_URL; ?>/sanciones" class="btn btn-secondary" style="border-color: #6F7271; color: #6F7271;">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
