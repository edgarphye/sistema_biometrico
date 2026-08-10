<?php $error = $error ?? ($_SESSION['error'] ?? null); ?>
<?php $form_data = $_SESSION['form_data'] ?? []; ?>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-edit"></i> Editar Horario: <?= htmlspecialchars($horario['nombre'] ?? '') ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars(is_string($error) ? $error : ($error['message'] ?? 'Error desconocido')) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/horarios/edit">
                        <input type="hidden" name="id" value="<?= (int)($horario['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Horario *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   value="<?= htmlspecialchars($form_data['nombre'] ?? $horario['nombre'] ?? '') ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="hora_entrada" class="form-label">Hora de Entrada *</label>
                                <input type="time" class="form-control" id="hora_entrada" name="hora_entrada" required
                                       value="<?= htmlspecialchars($form_data['hora_entrada'] ?? $horario['hora_entrada'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="hora_salida" class="form-label">Hora de Salida *</label>
                                <input type="time" class="form-control" id="hora_salida" name="hora_salida" required
                                       value="<?= htmlspecialchars($form_data['hora_salida'] ?? $horario['hora_salida'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tolerancia_minutos" class="form-label">Tolerancia (minutos)</label>
                            <input type="number" class="form-control" id="tolerancia_minutos" name="tolerancia_minutos"
                                   value="<?= htmlspecialchars($form_data['tolerancia_minutos'] ?? $horario['tolerancia_minutos'] ?? 10) ?>" min="0" max="60">
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($form_data['descripcion'] ?? $horario['descripcion'] ?? '') ?></textarea>
                        </div>

                        <?php if (isset($sedes)): ?>
                        <div class="mb-3">
                            <label for="sede" class="form-label">Sede</label>
                            <select class="form-control" id="sede" name="sede">
                                <option value="">Seleccionar sede</option>
                                <?php foreach ($sedes as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= ($form_data['sede'] ?? $horario['sede'] ?? '') === $s ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="<?= BASE_URL ?>/horarios" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
