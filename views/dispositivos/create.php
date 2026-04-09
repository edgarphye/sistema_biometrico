<?php
$old = $_SESSION['old_input'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old_input'], $_SESSION['errors']);
?>
<style>
    :root {
        --pantone-vino: #9F2241;
        --pantone-vino-dark: #691C32;
        --pantone-oro: #BC955C;
        --pantone-verde: #235B4E;
    }
    .dispositivos-create-content .card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }
    .dispositivos-create-content .card-header {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-dark) 100%);
        padding: 1.25rem 1.5rem;
        border: none;
    }
    .dispositivos-create-content .card-header h4 {
        color: #ffffff;
        font-weight: 600;
        margin: 0;
    }
    .dispositivos-create-content .form-label {
        color: #333;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    [data-bs-theme="dark"] .dispositivos-create-content .form-label {
        color: #e0e0e0;
    }
    .dispositivos-create-content .form-control,
    .dispositivos-create-content .form-select {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        color: #333;
        background-color: #fff;
    }
    [data-bs-theme="dark"] .dispositivos-create-content .form-control,
    [data-bs-theme="dark"] .dispositivos-create-content .form-select {
        border-color: #444;
        background-color: #2d2d2d;
        color: #e0e0e0;
    }
    .dispositivos-create-content .form-control:focus,
    .dispositivos-create-content .form-select:focus {
        border-color: var(--pantone-vino);
        box-shadow: 0 0 0 0.25rem rgba(159, 34, 65, 0.25);
    }
    .dispositivos-create-content .form-text {
        color: #666;
        font-size: 0.85rem;
    }
    [data-bs-theme="dark"] .dispositivos-create-content .form-text {
        color: #aaa;
    }
    .dispositivos-create-content .form-check-label {
        color: #333;
    }
    [data-bs-theme="dark"] .dispositivos-create-content .form-check-label {
        color: #e0e0e0;
    }
    .dispositivos-create-content .form-check-input:checked {
        background-color: var(--pantone-vino);
        border-color: var(--pantone-vino);
    }
    .dispositivos-create-content .btn-volver {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        border: none;
        color: #fff;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .dispositivos-create-content .btn-volver:hover {
        background: linear-gradient(135deg, #5a6268 0%, #3d4246 100%);
        color: #fff;
        transform: translateY(-2px);
    }
    .dispositivos-create-content .btn-guardar {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-dark) 100%);
        border: none;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .dispositivos-create-content .btn-guardar:hover {
        background: linear-gradient(135deg, var(--pantone-vino-dark) 0%, var(--pantone-vino) 100%);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(159, 34, 65, 0.4);
    }
    .dispositivos-create-content .text-danger {
        color: var(--pantone-vino) !important;
    }
    .dispositivos-create-content .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border: 1px solid #f5c2c3;
        border-radius: 10px;
        color: #842029;
    }
    [data-bs-theme="dark"] .dispositivos-create-content .alert-danger {
        background: linear-gradient(135deg, #842029 0%, #6c1d24 100%);
        border-color: #842029;
        color: #f8d7da;
    }
</style>

<div class="dispositivos-create-content">
<div class="container py-4">
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>/dispositivos" class="btn btn-volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card shadow">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Agregar Nuevo Dispositivo Biométrico</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/dispositivos/create">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID de Dispositivo <span class="text-danger">*</span></label>
                        <input type="number" name="dispositivo_id" class="form-control" 
                               value="<?php echo htmlspecialchars($old['dispositivo_id'] ?? ''); ?>" required min="1">
                        <div class="form-text">Número único del 1-100</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre del Dispositivo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" 
                               value="<?php echo htmlspecialchars($old['nombre'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sede/Sucursal <span class="text-danger">*</span></label>
                        <input type="text" name="sede" class="form-control" 
                               value="<?php echo htmlspecialchars($old['sede'] ?? ''); ?>" required>
                        <div class="form-text">Ej: Central, Norte, Sur, Sucursal A1</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dirección IP <span class="text-danger">*</span></label>
                        <input type="text" name="ip_address" class="form-control" 
                               value="<?php echo htmlspecialchars($old['ip_address'] ?? ''); ?>" 
                               placeholder="192.168.1.100" required pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Puerto</label>
                        <input type="number" name="puerto" class="form-control" 
                               value="<?php echo htmlspecialchars($old['puerto'] ?? '4370'); ?>" 
                               min="1" max="65535">
                        <div class="form-text">Por defecto: 4370 (ZKTeco)</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de Dispositivo</label>
                        <select name="tipo_dispositivo" class="form-select">
                            <option value="ZKTeco" <?php echo ($old['tipo_dispositivo'] ?? 'ZKTeco') === 'ZKTeco' ? 'selected' : ''; ?>>ZKTeco</option>
                            <option value="Suprema" <?php echo ($old['tipo_dispositivo'] ?? '') === 'Suprema' ? 'selected' : ''; ?>>Suprema</option>
                            <option value="Anviz" <?php echo ($old['tipo_dispositivo'] ?? '') === 'Anviz' ? 'selected' : ''; ?>>Anviz</option>
                            <option value="Otro" <?php echo ($old['tipo_dispositivo'] ?? '') === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="modelo" class="form-control" 
                               value="<?php echo htmlspecialchars($old['modelo'] ?? ''); ?>" 
                               placeholder="Ej: F18, MA300">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Capacidades Biométricas</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="cap_huella" id="cap_huella" 
                                       <?php echo isset($old['cap_huella']) || !isset($old['dispositivo_id']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cap_huella">
                                    <i class="fas fa-fingerprint"></i> Huella Dactilar
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="cap_cara" id="cap_cara"
                                       <?php echo isset($old['cap_cara']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cap_cara">
                                    <i class="fas fa-face-smile"></i> Reconocimiento Facial
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Instalación</label>
                        <input type="date" name="fecha_instalacion" class="form-control" 
                               value="<?php echo htmlspecialchars($old['fecha_instalacion'] ?? date('Y-m-d')); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="3"><?php echo htmlspecialchars($old['notas'] ?? ''); ?></textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo BASE_URL; ?>/dispositivos" class="btn btn-volver">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-guardar">
                        <i class="fas fa-save"></i> Guardar Dispositivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
