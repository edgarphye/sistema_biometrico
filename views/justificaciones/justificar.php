<?php include 'views/layout.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-check"></i> Justificar Retardo</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <strong>Detalles del Retardo:</strong><br>
                        Empleado: <?php echo htmlspecialchars($retardo['nombre'] . ' ' . $retardo['apellido']); ?><br>
                        Fecha: <?php echo date('d/m/Y', strtotime($retardo['fecha'])); ?><br>
                        Minutos de retardo: <?php echo $retardo['minutos_retardo']; ?><br>
                        Tipo: <span class="badge badge-<?php echo $retardo['tipo'] == 'mayor' ? 'danger' : 'warning'; ?>">
                            <?php echo ucfirst($retardo['tipo']); ?>
                        </span>
                    </div>

                    <?php require_once 'helpers/Csrf.php'; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        <div class="form-group">
                            <label for="tipo_justificacion_id">Tipo de Justificación</label>
                            <select class="form-control" id="tipo_justificacion_id" name="tipo_justificacion_id">
                                <option value="">Seleccionar tipo...</option>
                                <?php foreach ($tipos_justificacion as $tipo): ?>
                                    <option value="<?php echo $tipo['id']; ?>">
                                        <?php echo htmlspecialchars($tipo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="motivo">Motivo de la Justificación</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="4" required
                                      placeholder="Describa detalladamente el motivo de la justificación..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="soporte">Adjuntar Soporte (PDF, JPG, PNG) <small class="text-muted">(opcional)</small></label>
                            <input type="file" class="form-control-file" id="soporte" name="soporte" accept=".pdf,image/*">
                            <small class="form-text text-muted">Tamaño máximo recomendado: 5MB. Mantén confidencial cualquier dato sensible.</small>

                            <?php if (!empty($retardo['soporte'])): ?>
                                <p class="mt-2">Soporte actual: <a href="<?php echo BASE_URL . '/' . $retardo['soporte']; ?>" target="_blank">Ver/Descargar</a></p>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Justificar Retardo
                        </button>
                        <a href="<?php echo BASE_URL; ?>/justificaciones" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
