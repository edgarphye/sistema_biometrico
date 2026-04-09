<?php include 'views/layout.php'; ?>
<?php require_once 'helpers/Csrf.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-plus"></i> Crear Nuevo Horario Laboral</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
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

                    <form method="POST" action="/sistema_biometrico/horarios/create">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        <div class="form-group">
                            <label for="nombre">Nombre del Horario *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($_SESSION['form_data']['nombre'] ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="hora_entrada">Hora de Entrada *</label>
                                <input type="time" class="form-control" id="hora_entrada" name="hora_entrada" required value="<?php echo htmlspecialchars($_SESSION['form_data']['hora_entrada'] ?? ''); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="hora_salida">Hora de Salida *</label>
                                <input type="time" class="form-control" id="hora_salida" name="hora_salida" required value="<?php echo htmlspecialchars($_SESSION['form_data']['hora_salida'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tolerancia_minutos">Tolerancia (minutos)</label>
                            <input type="number" class="form-control" id="tolerancia_minutos" name="tolerancia_minutos"
                                    value="<?php echo htmlspecialchars($_SESSION['form_data']['tolerancia_minutos'] ?? 9); ?>" min="0" max="60">
                            <small class="form-text text-muted">Minutos de tolerancia para considerar retardo menor</small>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($_SESSION['form_data']['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Horario
                        </button>
                        <a href="/sistema_biometrico/horarios" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    </form>
                    
                    <script>
                    // Validar que la hora de salida sea posterior a la de entrada
                    document.getElementById('hora_salida').addEventListener('change', function() {
                        const entrada = document.getElementById('hora_entrada').value;
                        const salida = this.value;
                        
                        if (entrada && salida) {
                            const [hEntrada, mEntrada] = entrada.split(':').map(Number);
                            const [hSalida, mSalida] = salida.split(':').map(Number);
                            
                            const totalMinutosEntrada = hEntrada * 60 + mEntrada;
                            const totalMinutosSalida = hSalida * 60 + mSalida;
                            
                            if (totalMinutosSalida <= totalMinutosEntrada) {
                                this.setCustomValidity('La hora de salida debe ser posterior a la hora de entrada');
                            } else {
                                this.setCustomValidity('');
                            }
                        }
                    });
                    
                    // Validar también al cambiar la hora de entrada
                    document.getElementById('hora_entrada').addEventListener('change', function() {
                        document.getElementById('hora_salida').dispatchEvent(new Event('change'));
                    });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
