<?php include 'views/layout.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-plus"></i> Crear Nuevo Horario Laboral</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/sistema_biometrico/horarios/create">
                        <div class="form-group">
                            <label for="nombre">Nombre del Horario *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="hora_entrada">Hora de Entrada *</label>
                                <input type="time" class="form-control" id="hora_entrada" name="hora_entrada" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="hora_salida">Hora de Salida *</label>
                                <input type="time" class="form-control" id="hora_salida" name="hora_salida" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tolerancia_minutos">Tolerancia (minutos)</label>
                            <input type="number" class="form-control" id="tolerancia_minutos" name="tolerancia_minutos"
                                   value="9" min="0" max="60">
                            <small class="form-text text-muted">Minutos de tolerancia para considerar retardo menor</small>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Horario
                        </button>
                        <a href="/sistema_biometrico/horarios" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
