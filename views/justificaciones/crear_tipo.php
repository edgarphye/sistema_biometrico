<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-plus"></i> Crear Tipo de Justificación</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/justificaciones/crear-tipo">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="100">
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="255"></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="requiere_aprobacion" name="requiere_aprobacion" value="1" checked>
                            <label class="form-check-label" for="requiere_aprobacion">Requiere aprobación</label>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="requiere_documento" name="requiere_documento" value="1">
                            <label class="form-check-label" for="requiere_documento">Requiere documento soporte</label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <a href="<?= BASE_URL ?>/justificaciones/tipos" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
