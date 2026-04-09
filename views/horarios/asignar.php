<?php include 'views/layout.php'; ?>
<?php require_once 'helpers/Csrf.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-link"></i> Asignar Horarios a Empleados</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/sistema_biometrico/horarios/asignar">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        <div class="form-group">
                            <label for="empleado_id">Empleado *</label>
                            <select class="form-control" id="empleado_id" name="empleado_id" required>
                                <option value="">Seleccionar empleado...</option>
                                <?php foreach ($empleados as $empleado): ?>
                                    <option value="<?php echo $empleado['id']; ?>">
                                        <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido'] . ' - ' . $empleado['area']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="horario_id">Horario *</label>
                            <select class="form-control" id="horario_id" name="horario_id" required>
                                <option value="">Seleccionar horario...</option>
                                <?php foreach ($horarios as $horario): ?>
                                    <option value="<?php echo $horario['id']; ?>">
                                        <?php echo htmlspecialchars($horario['nombre'] . ' (' . $horario['hora_entrada'] . ' - ' . $horario['hora_salida'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="dia_semana">Día de la Semana *</label>
                            <select class="form-control" id="dia_semana" name="dia_semana" required>
                                <option value="">Seleccionar día...</option>
                                <option value="lunes">Lunes</option>
                                <option value="martes">Martes</option>
                                <option value="miercoles">Miércoles</option>
                                <option value="jueves">Jueves</option>
                                <option value="viernes">Viernes</option>
                                <option value="sabado">Sábado</option>
                                <option value="domingo">Domingo</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success" style="background-color: #235B4E; border-color: #235B4E;">
                            <i class="fas fa-link"></i> Asignar Horario
                        </button>
                        <a href="/sistema_biometrico/horarios" class="btn btn-secondary" style="border-color: #6F7271; color: #6F7271;">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Notas Importantes</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Un empleado puede tener hasta 2 horarios diferentes por día</li>
                        <li>Los horarios se asignan por día de la semana</li>
                        <li>Si no hay horario asignado para un día, se usa el horario por defecto (09:00:00)</li>
                        <li>Los retardos se calculan automáticamente según el horario asignado</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
