<?php
$cap = json_decode($dispositivo['capacidades'], true);
?>
<style>
    :root {
        --pantone-vino: #9F2241;
        --pantone-vino-dark: #691C32;
        --pantone-oro: #BC955C;
        --pantone-verde: #235B4E;
    }
    .dispositivos-edit-content .card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }
    .dispositivos-edit-content .card-header {
        background: linear-gradient(135deg, var(--pantone-oro) 0%, #9a7a4d 100%);
        padding: 1.25rem 1.5rem;
        border: none;
    }
    .dispositivos-edit-content .card-header h4 {
        color: #ffffff;
        font-weight: 600;
        margin: 0;
    }
    .dispositivos-edit-content .card-body {
        background: linear-gradient(180deg, #fafafa 0%, #ffffff 100%);
        padding: 2rem;
    }
    [data-bs-theme="dark"] .dispositivos-edit-content .card-body {
        background: linear-gradient(180deg, #2d2d2d 0%, #252525 100%);
    }
    .dispositivos-edit-content .form-label {
        color: #333;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    [data-bs-theme="dark"] .dispositivos-edit-content .form-label {
        color: #e0e0e0;
    }
    .dispositivos-edit-content .form-control,
    .dispositivos-edit-content .form-select {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        color: #333;
        background-color: #fff;
    }
    [data-bs-theme="dark"] .dispositivos-edit-content .form-control,
    [data-bs-theme="dark"] .dispositivos-edit-content .form-select {
        border-color: #444;
        background-color: #2d2d2d;
        color: #e0e0e0;
    }
    .dispositivos-edit-content .form-control:focus,
    .dispositivos-edit-content .form-select:focus {
        border-color: var(--pantone-oro);
        box-shadow: 0 0 0 0.25rem rgba(188, 149, 92, 0.3);
    }
    .dispositivos-edit-content .form-text {
        color: #666;
        font-size: 0.85rem;
    }
    [data-bs-theme="dark"] .dispositivos-edit-content .form-text {
        color: #aaa;
    }
    .dispositivos-edit-content .form-check-label {
        color: #333;
    }
    [data-bs-theme="dark"] .dispositivos-edit-content .form-check-label {
        color: #e0e0e0;
    }
    .dispositivos-edit-content .form-check-input:checked {
        background-color: var(--pantone-verde);
        border-color: var(--pantone-verde);
    }
    .dispositivos-edit-content .btn-volver {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        border: none;
        color: #fff;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .dispositivos-edit-content .btn-volver:hover {
        background: linear-gradient(135deg, #5a6268 0%, #3d4246 100%);
        color: #fff;
        transform: translateY(-2px);
    }
    .dispositivos-edit-content .btn-actualizar {
        background: linear-gradient(135deg, var(--pantone-verde) 0%, #1a3f36 100%);
        border: none;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .dispositivos-edit-content .btn-actualizar:hover {
        background: linear-gradient(135deg, #1a3f36 0%, var(--pantone-verde) 100%);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(35, 91, 78, 0.4);
    }
    .dispositivos-edit-content .text-danger {
        color: var(--pantone-vino) !important;
    }
    .dispositivos-edit-content .info-badge {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-dark) 100%);
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 1rem;
    }
</style>

<div class="dispositivos-edit-content">
<div class="container py-4">
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>/dispositivos" class="btn btn-volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card shadow-lg">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-edit me-2"></i> Editar Dispositivo Biométrico</h4>
        </div>
        <div class="card-body">
            <div class="info-badge mb-4">
                <i class="fas fa-fingerprint me-2"></i>Dispositivo #<?php echo $dispositivo['dispositivo_id']; ?>
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>/dispositivos/edit?id=<?php echo $dispositivo['id']; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre del Dispositivo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" 
                               value="<?php echo htmlspecialchars($dispositivo['nombre']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sede/Sucursal <span class="text-danger">*</span></label>
                        <input type="text" name="sede" class="form-control" 
                               value="<?php echo htmlspecialchars($dispositivo['sede']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Dirección IP <span class="text-danger">*</span></label>
                        <input type="text" name="ip_address" class="form-control" 
                               value="<?php echo htmlspecialchars($dispositivo['ip_address']); ?>" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Puerto</label>
                        <input type="number" name="puerto" class="form-control" 
                               value="<?php echo htmlspecialchars($dispositivo['puerto']); ?>" min="1" max="65535">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Estado</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" 
                                   <?php echo $dispositivo['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">
                                <?php echo $dispositivo['activo'] ? '<span class="text-success fw-bold">Activo</span>' : '<span class="text-muted">Inactivo</span>'; ?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de Dispositivo</label>
                        <select name="tipo_dispositivo" class="form-select">
                            <option value="ZKTeco" <?php echo $dispositivo['tipo_dispositivo'] === 'ZKTeco' ? 'selected' : ''; ?>>ZKTeco</option>
                            <option value="Suprema" <?php echo $dispositivo['tipo_dispositivo'] === 'Suprema' ? 'selected' : ''; ?>>Suprema</option>
                            <option value="Anviz" <?php echo $dispositivo['tipo_dispositivo'] === 'Anviz' ? 'selected' : ''; ?>>Anviz</option>
                            <option value="Otro" <?php echo $dispositivo['tipo_dispositivo'] === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="modelo" class="form-control" 
                               value="<?php echo htmlspecialchars($dispositivo['modelo'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fecha de Instalación</label>
                        <input type="date" name="fecha_instalacion" class="form-control" 
                               value="<?php echo htmlspecialchars($dispositivo['fecha_instalacion'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Capacidades Biométricas</label>
                    <div class="border rounded p-3 bg-light">
                        <div class="form-check form-check-inline me-4">
                            <input class="form-check-input" type="checkbox" name="cap_huella" id="cap_huella" 
                                   <?php echo ($cap['huella'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cap_huella">
                                <i class="fas fa-fingerprint me-1"></i> Huella Dactilar
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="cap_cara" id="cap_cara"
                                   <?php echo ($cap['cara'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cap_cara">
                                <i class="fas fa-face-smile me-1"></i> Reconocimiento Facial
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="3"><?php echo htmlspecialchars($dispositivo['notas'] ?? ''); ?></textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="<?php echo BASE_URL; ?>/dispositivos" class="btn btn-volver">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-actualizar">
                        <i class="fas fa-save me-2"></i>Actualizar Dispositivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
