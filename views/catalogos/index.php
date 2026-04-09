<?php 
$pageTitle = 'Catálogos Organizacionales';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/Csrf.php';
$csrfToken = Csrf::token() ?? Csrf::generateToken();
?>

<?php ob_start(); ?>

<style>
    :root {
        --pantone-primary: #9F2241;
        --pantone-primary-dark: #691C32;
        --pantone-secondary: #235B4E;
        --pantone-secondary-dark: #10312B;
        --pantone-accent: #DDC9A3;
        --pantone-accent-dark: #BC955C;
        --pantone-danger: #691C32;
        --pantone-gray: #98989A;
        --pantone-gray-dark: #6F7271;
    }
    
    .catalogos-container {
        background-color: #f8f7f5;
        min-height: 100vh;
    }
    
    .catalogos-card {
        border-radius: 16px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    
    .catalogos-header {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
    }
    
    /* Tabs personalizados */
    .catalogos-tabs {
        background: linear-gradient(135deg, #f8f7f5 0%, #f0ede8 100%);
        border-bottom: 2px solid var(--pantone-primary);
        padding: 8px 8px 0 8px;
    }
    
    .catalogos-tabs .nav-link {
        font-size: 0.8rem;
        font-weight: 600;
        padding: 12px 16px;
        border: none;
        border-radius: 10px 10px 0 0;
        color: var(--pantone-gray-dark);
        background: transparent;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .catalogos-tabs .nav-link:hover {
        color: var(--pantone-primary);
        background: rgba(151, 34, 65, 0.08);
    }
    
    .catalogos-tabs .nav-link.active {
        color: white;
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        box-shadow: 0 -2px 10px rgba(151, 34, 65, 0.2);
    }
    
    /* Tablas elegantes */
    .table-custom thead {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
    }
    
    .table-custom th {
        border: none;
        padding: 14px 12px;
        font-weight: 600;
    }
    
    .table-custom td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .table-custom tbody tr:hover {
        background-color: rgba(35, 91, 78, 0.05);
    }
    
    /* Badges */
    .badge-activo {
        background-color: var(--pantone-secondary);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
    }
    
    .badge-inactivo {
        background-color: var(--pantone-gray);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
    }
    
    /* Botones */
    .btn-pantone-primary {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-pantone-primary:hover {
        background: linear-gradient(135deg, var(--pantone-primary-dark) 0%, #501526 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(151, 34, 65, 0.3);
    }
    
    .btn-pantone-secondary {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-pantone-secondary:hover {
        background: linear-gradient(135deg, var(--pantone-secondary-dark) 0%, #0a211d 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(35, 91, 78, 0.3);
    }
    
    .btn-pantone-outline {
        background: transparent;
        color: var(--pantone-primary);
        border: 2px solid var(--pantone-primary);
        border-radius: 25px;
        padding: 8px 18px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-pantone-outline:hover {
        background: var(--pantone-primary);
        color: white;
        transform: translateY(-2px);
    }
    
    /* Modales */
    .modal-custom .modal-content {
        border-radius: 16px;
        overflow: hidden;
        border: none;
    }
    
    .modal-custom .modal-header {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
    }
    
    .modal-custom .modal-header.secondary {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
    }
    
    .modal-custom .modal-header.gold {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
    }
    
    /* Formularios */
    .form-control:focus, .form-select:focus {
        border-color: var(--pantone-primary);
        box-shadow: 0 0 0 0.2rem rgba(151, 34, 65, 0.15);
    }
    
    /* Iconos en tablas */
    .table-icon-edit {
        color: var(--pantone-secondary);
        transition: all 0.2s ease;
    }
    
    .table-icon-edit:hover {
        color: var(--pantone-secondary-dark);
        transform: scale(1.1);
    }
    
    .table-icon-delete {
        color: var(--pantone-danger);
        transition: all 0.2s ease;
    }
    
    .table-icon-delete:hover {
        transform: scale(1.1);
    }
    
    /* Estilos mejorados para Modales */
    .modal-elegante .modal-content {
        border-radius: 20px;
        overflow: hidden;
        border: none;
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    }
    
    .modal-elegante .modal-header {
        padding: 20px 25px;
        border: none;
        position: relative;
    }
    
    .modal-elegante .modal-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
    }
    
    .modal-elegante .modal-header.rojo {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
    }
    
    .modal-elegante .modal-header.verde {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
    }
    
    .modal-elegante .modal-header.dorado {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
    }
    
    .modal-elegante .modal-title {
        font-weight: 600;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .modal-elegante .modal-title i {
        font-size: 1.3rem;
    }
    
    .modal-elegante .modal-body {
        padding: 25px;
        background: #fafafa;
    }
    
    .modal-elegante .form-label {
        font-weight: 600;
        color: #444;
        font-size: 0.9rem;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .modal-elegante .form-label i {
        color: var(--pantone-primary);
        font-size: 0.85rem;
    }
    
    .modal-elegante .form-control,
    .modal-elegante .form-select {
        border-radius: 12px;
        border: 2px solid #e0e0e0;
        padding: 12px 15px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .modal-elegante .form-control:focus,
    .modal-elegante .form-select:focus {
        border-color: var(--pantone-primary);
        box-shadow: 0 0 0 4px rgba(151, 34, 65, 0.1);
        background: white;
    }
    
    .modal-elegante .form-control::placeholder {
        color: #aaa;
    }
    
    .modal-elegante .form-check {
        padding: 10px 0;
        margin: 5px 0;
    }
    
    .modal-elegante .form-check-input {
        width: 50px;
        height: 26px;
        border-radius: 13px;
        cursor: pointer;
        margin-top: 0;
    }
    
    .modal-elegante .form-check-input:checked {
        background-color: var(--pantone-secondary);
        border-color: var(--pantone-secondary);
    }
    
    .modal-elegante .form-check-label {
        font-weight: 500;
        color: #555;
        cursor: pointer;
        margin-left: 10px;
    }
    
    .modal-elegante .modal-footer {
        padding: 15px 25px;
        background: white;
        border-top: 1px solid #eee;
    }
    
    .modal-elegante .btn-cancelar {
        background: #f0f0f0;
        color: #666;
        border: none;
        border-radius: 25px;
        padding: 10px 25px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .modal-elegante .btn-cancelar:hover {
        background: #e0e0e0;
        color: #333;
    }
    
    .modal-elegante .btn-guardar {
        border-radius: 25px;
        padding: 10px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(151, 34, 65, 0.3);
    }
    
    .modal-elegante .btn-guardar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(151, 34, 65, 0.4);
    }
    
    .modal-elegante .btn-guardar.verde {
        box-shadow: 0 4px 15px rgba(35, 91, 78, 0.3);
    }
    
    .modal-elegante .btn-guardar.verde:hover {
        box-shadow: 0 6px 20px rgba(35, 91, 78, 0.4);
    }
    
    .modal-elegante .btn-guardar.dorado {
        box-shadow: 0 4px 15px rgba(188, 149, 92, 0.3);
    }
    
    .modal-elegante .btn-guardar.dorado:hover {
        box-shadow: 0 6px 20px rgba(188, 149, 92, 0.4);
    }
    
    /* Input group con icono */
    .input-group-icon {
        position: relative;
    }
    
    .input-group-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--pantone-gray);
        z-index: 10;
    }
    
    .input-group-icon .form-control {
        padding-left: 42px;
    }
    
    /* Tema oscuro para modales elegantes */
    body.dark-theme .modal-elegante .modal-content {
        background-color: #252538;
        border: 1px solid #3a3a5c;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }
    
    body.dark-theme .modal-elegante .modal-header.rojo {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        border-bottom: 3px solid var(--pantone-primary);
    }
    
    body.dark-theme .modal-elegante .modal-header.verde {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        border-bottom: 3px solid var(--pantone-secondary);
    }
    
    body.dark-theme .modal-elegante .modal-header.dorado {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
        border-bottom: 3px solid var(--pantone-accent-dark);
    }
    
    body.dark-theme .modal-elegante .modal-body {
        background: linear-gradient(180deg, #252538 0%, #1e1e32 100%);
        padding: 25px;
    }
    
    body.dark-theme .modal-elegante .form-label {
        color: var(--pantone-accent) !important;
        font-weight: 600;
    }
    
    body.dark-theme .modal-elegante .form-label i {
        color: var(--pantone-accent);
    }
    
    body.dark-theme .modal-elegante .form-control,
    body.dark-theme .modal-elegante .form-select {
        background-color: #1a1a2e;
        border: 2px solid #3a3a5c;
        color: #e0e0e0;
        border-radius: 12px;
    }
    
    body.dark-theme .modal-elegante .form-control:focus,
    body.dark-theme .modal-elegante .form-select:focus {
        border-color: var(--pantone-primary);
        box-shadow: 0 0 0 4px rgba(151, 34, 65, 0.2);
        background-color: #1a1a2e;
        color: #ffffff;
    }
    
    body.dark-theme .modal-elegante .form-control::placeholder {
        color: #6c6c8a;
    }
    
    body.dark-theme .modal-elegante .form-select option {
        background-color: #252538;
        color: #e0e0e0;
    }
    
    body.dark-theme .modal-elegante .modal-footer {
        background-color: #1e1e32;
        border-top: 1px solid #3a3a5c;
    }
    
    body.dark-theme .modal-elegante .btn-cancelar {
        background: #3a3a5c;
        color: #e0e0e0;
    }
    
    body.dark-theme .modal-elegante .btn-cancelar:hover {
        background: #4a4a6a;
        color: white;
    }
    
    body.dark-theme .modal-elegante .btn-guardar {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        box-shadow: 0 4px 15px rgba(151, 34, 65, 0.4);
    }
    
    body.dark-theme .modal-elegante .btn-guardar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(151, 34, 65, 0.5);
    }
    
    body.dark-theme .modal-elegante .btn-guardar.verde {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        box-shadow: 0 4px 15px rgba(35, 91, 78, 0.4);
    }
    
    body.dark-theme .modal-elegante .btn-guardar.verde:hover {
        box-shadow: 0 6px 20px rgba(35, 91, 78, 0.5);
    }
    
    body.dark-theme .modal-elegante .btn-guardar.dorado {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
        box-shadow: 0 4px 15px rgba(188, 149, 92, 0.4);
    }
    
    body.dark-theme .modal-elegante .btn-guardar.dorado:hover {
        box-shadow: 0 6px 20px rgba(188, 149, 92, 0.5);
    }
    
    body.dark-theme .modal-elegante .text-muted {
        color: #8a8aaa !important;
    }
</style>

<div class="container-fluid py-4 catalogos-container">
    <div class="row">
        <div class="col-12">
            <div class="card catalogos-card">
                <div class="card-header catalogos-header py-3">
                    <h4 class="mb-0"><i class="fas fa-sitemap me-3"></i>Catálogos Organizacionales</h4>
                </div>
                <div class="card-body p-0">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert" style="border-radius: 10px;">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert" style="border-radius: 10px;">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <ul class="nav nav-tabs catalogos-tabs" id="catalogosTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="direcciones-tab" data-bs-toggle="tab" data-bs-target="#direcciones" type="button" role="tab">
                                <i class="fas fa-building me-2"></i> Direcciones
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="subdirecciones-tab" data-bs-toggle="tab" data-bs-target="#subdirecciones" type="button" role="tab">
                                <i class="fas fa-layer-group me-2"></i> Subdirecciones
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="departamentos-tab" data-bs-toggle="tab" data-bs-target="#departamentos" type="button" role="tab">
                                <i class="fas fa-users me-2"></i> Departamentos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="mandos-tab" data-bs-toggle="tab" data-bs-target="#mandos" type="button" role="tab">
                                <i class="fas fa-user-tie me-2"></i> Catálogo de Mandos
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3 p-3" id="catalogosTabsContent">
                        <!-- DIRECCIONES -->
                        <div class="tab-pane fade show active" id="direcciones" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <button class="btn btn-pantone-primary" data-bs-toggle="modal" data-bs-target="#modalDireccion">
                                        <i class="fas fa-plus me-2"></i> Nueva Dirección
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-custom">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-key me-2"></i>Clave</th>
                                            <th><i class="fas fa-key me-2"></i>Clave DIR2</th>
                                            <th><i class="fas fa-building me-2"></i>Nombre</th>
                                            <th><i class="fas fa-toggle-on me-2"></i>Estado</th>
                                            <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($direcciones as $dir): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($dir['clave_dir']) ?></strong></td>
                                            <td><?= htmlspecialchars($dir['clave_dir2'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($dir['nombre_direccion']) ?></td>
                                            <td>
                                                <?php if ($dir['activo']): ?>
                                                    <span class="badge-activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge-inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#modalDireccion"
                                                    data-clave="<?= $dir['clave_dir'] ?>" 
                                                    data-clave2="<?= htmlspecialchars($dir['clave_dir2'] ?? '') ?>"
                                                    data-nombre="<?= htmlspecialchars($dir['nombre_direccion']) ?>"
                                                    data-activo="<?= $dir['activo'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="<?= BASE_URL ?>/catalogos/eliminar-direccion" style="display:inline;">
                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="clave_dir" value="<?= $dir['clave_dir'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta dirección?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- SUBDIRECCIONES -->
                        <div class="tab-pane fade" id="subdirecciones" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <button class="btn btn-pantone-primary" data-bs-toggle="modal" data-bs-target="#modalSubdireccion">
                                        <i class="fas fa-plus me-2"></i> Nueva Subdirección
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-custom">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-key me-2"></i>Clave</th>
                                            <th><i class="fas fa-building me-2"></i>Nombre</th>
                                            <th><i class="fas fa-sitemap me-2"></i>Dirección</th>
                                            <th><i class="fas fa-toggle-on me-2"></i>Estado</th>
                                            <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subdirecciones as $sub): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($sub['clave_subdir']) ?></strong></td>
                                            <td><?= htmlspecialchars($sub['nombre_subdir']) ?></td>
                                            <td><?= htmlspecialchars($sub['nombre_direccion'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($sub['activo']): ?>
                                                    <span class="badge-activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge-inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#modalSubdireccion"
                                                    data-clave="<?= $sub['clave_subdir'] ?>" 
                                                    data-nombre="<?= htmlspecialchars($sub['nombre_subdir']) ?>"
                                                    data-clave-dir="<?= htmlspecialchars($sub['clave_dir'] ?? '') ?>"
                                                    data-activo="<?= $sub['activo'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="<?= BASE_URL ?>/catalogos/eliminar-subdireccion" style="display:inline;">
                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="clave_subdir" value="<?= $sub['clave_subdir'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta subdirección?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- DEPARTAMENTOS -->
                        <div class="tab-pane fade" id="departamentos" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <button class="btn btn-pantone-primary" data-bs-toggle="modal" data-bs-target="#modalDepartamento">
                                        <i class="fas fa-plus me-2"></i> Nuevo Departamento
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-custom">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-key me-2"></i>Clave</th>
                                            <th><i class="fas fa-building me-2"></i>Nombre</th>
                                            <th><i class="fas fa-layer-group me-2"></i>Subdirección</th>
                                            <th><i class="fas fa-toggle-on me-2"></i>Estado</th>
                                            <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($departamentos as $dept): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($dept['clave_depto']) ?></strong></td>
                                            <td><?= htmlspecialchars($dept['nombre_depto']) ?></td>
                                            <td><?= htmlspecialchars($dept['nombre_subdir'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($dept['activo']): ?>
                                                    <span class="badge-activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge-inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#modalDepartamento"
                                                    data-clave="<?= $dept['clave_depto'] ?>" 
                                                    data-nombre="<?= htmlspecialchars($dept['nombre_depto']) ?>"
                                                    data-subdireccion="<?= htmlspecialchars($dept['clave_subdir'] ?? '') ?>"
                                                    data-activo="<?= $dept['activo'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="<?= BASE_URL ?>/catalogos/eliminar-departamento" style="display:inline;">
                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="clave_depto" value="<?= $dept['clave_depto'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar este departamento?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>



                        <!-- CATÁLOGO DE MANDOS -->
                        <div class="tab-pane fade" id="mandos" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <button class="btn btn-pantone-primary" data-bs-toggle="modal" data-bs-target="#modalMando">
                                        <i class="fas fa-plus me-2"></i> Nuevo Mando
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-custom">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-key me-2"></i>Clave Área</th>
                                            <th><i class="fas fa-user-tie me-2"></i>Nombre del Mando</th>
                                            <th><i class="fas fa-users me-2"></i>Departamento</th>
                                            <th><i class="fas fa-th-large me-2"></i>Área</th>
                                            <th><i class="fas fa-toggle-on me-2"></i>Estado</th>
                                            <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($mandos)): ?>
                                            <?php foreach ($mandos as $mando): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($mando['clave_area']) ?></strong></td>
                                                <td><?= htmlspecialchars($mando['nombre_mando']) ?></td>
                                                <td><?= htmlspecialchars($mando['nombre_departamento'] ?? $mando['clave_depto'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($mando['area']) ?></td>
                                                <td>
                                                    <?php if ($mando['activo']): ?>
                                                        <span class="badge-activo">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge-inactivo">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#modalMando"
                                                        data-clave="<?= $mando['clave_area'] ?>" 
                                                        data-nombre="<?= htmlspecialchars($mando['nombre_mando']) ?>"
                                                        data-area="<?= htmlspecialchars($mando['area']) ?>"
                                                        data-clave-depto="<?= htmlspecialchars($mando['clave_depto'] ?? '') ?>"
                                                        data-activo="<?= $mando['activo'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" action="<?= BASE_URL ?>/catalogos/eliminar-mando" style="display:inline;">
                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                        <input type="hidden" name="clave_area" value="<?= $mando['clave_area'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar este mando?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-info-circle me-2"></i>No hay mandos registrados
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dirección -->
<div class="modal fade modal-elegante" id="modalDireccion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header rojo text-white">
                <h5 class="modal-title"><i class="fas fa-building"></i>Dirección</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/catalogos/guardar-direccion">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-key"></i>Clave</label>
                        <input type="text" name="clave_dir" class="form-control" required pattern="[A-Za-z0-9]{1,10}" placeholder="Ej: DIR01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-hashtag"></i>Clave DIR2</label>
                        <input type="text" name="clave_dir2" class="form-control" placeholder="Ej: 001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-building"></i>Nombre</label>
                        <input type="text" name="nombre_direccion" class="form-control" required placeholder="Nombre de la dirección">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" id="activoDir" checked>
                        <label class="form-check-label" for="activoDir">Registro activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-guardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Subdirección -->
<div class="modal fade modal-elegante" id="modalSubdireccion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header verde text-white">
                <h5 class="modal-title"><i class="fas fa-layer-group"></i>Subdirección</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/catalogos/guardar-subdireccion">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-key"></i>Clave</label>
                        <input type="text" name="clave_subdir" class="form-control" required pattern="[A-Za-z0-9]{1,10}" placeholder="Ej: SUB01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-building"></i>Dirección</label>
                        <select name="clave_dir" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($direcciones as $dir): ?>
                            <option value="<?= $dir['clave_dir'] ?>"><?= htmlspecialchars($dir['nombre_direccion']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-tag"></i>Nombre</label>
                        <input type="text" name="nombre_subdir" class="form-control" required placeholder="Nombre de la subdirección">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" id="activoSub" checked>
                        <label class="form-check-label" for="activoSub">Registro activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-guardar verde">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Modal Departamento -->
<div class="modal fade modal-elegante" id="modalDepartamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header dorado text-white">
                <h5 class="modal-title"><i class="fas fa-users"></i>Departamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/catalogos/guardar-departamento">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-key"></i>Clave</label>
                        <input type="text" name="clave_depto" class="form-control" required pattern="[A-Za-z0-9]{1,10}" placeholder="Ej: DEP01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-layer-group"></i>Subdirección</label>
                        <select name="clave_sub" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($subdirecciones as $sub): ?>
                            <option value="<?= $sub['clave_subdir'] ?>"><?= htmlspecialchars($sub['nombre_subdir']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-tag"></i>Nombre</label>
                        <input type="text" name="nombre_departamento" class="form-control" required placeholder="Nombre del departamento">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" id="activoDepto" checked>
                        <label class="form-check-label" for="activoDepto">Registro activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-guardar dorado text-white">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Mando -->
<div class="modal fade modal-elegante" id="modalMando" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header rojo text-white">
                <h5 class="modal-title"><i class="fas fa-user-tie"></i>Mando</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/catalogos/guardar-mando">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i>Clave Área</label>
                            <input type="text" name="clave_area" class="form-control" required pattern="[A-Za-z0-9]{1,20}" placeholder="Ej: DCPSyGR">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-users"></i>Departamento</label>
                            <select name="clave_depto" class="form-select" required id="selectDeptoMando">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($departamentos as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['clave_depto']) ?>" data-clave="<?= htmlspecialchars($dept['clave_depto']) ?>" data-nombre="<?= htmlspecialchars($dept['nombre_departamento'] ?? $dept['nombre_depto']) ?>">
                                    <?= htmlspecialchars($dept['nombre_departamento'] ?? $dept['nombre_depto'] ?? '-') ?> (<?= $dept['clave_depto'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user"></i>Nombre del Mando</label>
                        <input type="text" name="nombre_mando" class="form-control" required placeholder="Ej: JUAREZ BELTRAN OSIRIS VANESSA">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-th-large"></i>Área</label>
                        <input type="text" name="area" class="form-control" placeholder="Se autocompleta al seleccionar departamento">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" id="activoMando" checked>
                        <label class="form-check-label" for="activoMando">Registro activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-guardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Dirección - Editar
    document.querySelectorAll('[data-bs-target="#modalDireccion"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = document.getElementById('modalDireccion');
            const clave = this.dataset.clave;
            const clave2 = this.dataset.clave2 || '';
            const nombre = this.dataset.nombre;
            const activo = this.dataset.activo;
            
            if (clave) {
                modal.querySelector('input[name="clave_dir"]').value = clave;
                modal.querySelector('input[name="clave_dir2"]').value = clave2;
                modal.querySelector('input[name="nombre_direccion"]').value = nombre;
                modal.querySelector('input[name="activo"]').checked = activo == 1;
                modal.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Dirección';
            } else {
                modal.querySelector('form').reset();
                modal.querySelector('.modal-title').innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Dirección';
            }
        });
    });

    // Modal Subdirección - Editar
    document.querySelectorAll('[data-bs-target="#modalSubdireccion"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = document.getElementById('modalSubdireccion');
            const clave = this.dataset.clave;
            const nombre = this.dataset.nombre;
            const direccion = this.dataset.direccion;
            const activo = this.dataset.activo;
            
            if (clave) {
                modal.querySelector('input[name="clave_subdir"]').value = clave;
                modal.querySelector('input[name="nombre_subdir"]').value = nombre;
                modal.querySelector('select[name="clave_dir"]').value = direccion;
                modal.querySelector('input[name="activo"]').checked = activo == 1;
                modal.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Subdirección';
            } else {
                modal.querySelector('form').reset();
                modal.querySelector('.modal-title').innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Subdirección';
            }
        });
    });

    // Modal Departamento - Editar
    document.querySelectorAll('[data-bs-target="#modalDepartamento"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = document.getElementById('modalDepartamento');
            const clave = this.dataset.clave;
            const nombre = this.dataset.nombre;
            const subdireccion = this.dataset.subdireccion;
            const activo = this.dataset.activo;
            
            if (clave) {
                modal.querySelector('input[name="clave_depto"]').value = clave;
                modal.querySelector('input[name="nombre_departamento"]').value = nombre;
                modal.querySelector('select[name="clave_sub"]').value = subdireccion;
                modal.querySelector('input[name="activo"]').checked = activo == 1;
                modal.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Departamento';
            } else {
                modal.querySelector('form').reset();
                modal.querySelector('.modal-title').innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Departamento';
            }
        });
    });

    // Modal Mando - Editar
    document.querySelectorAll('[data-bs-target="#modalMando"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = document.getElementById('modalMando');
            const clave = this.dataset.clave;
            const nombre = this.dataset.nombre;
            const area = this.dataset.area;
            const claveDepto = this.dataset.claveDepto || '';
            const activo = this.dataset.activo;
            
            if (clave) {
                modal.querySelector('input[name="clave_area"]').value = clave;
                modal.querySelector('input[name="nombre_mando"]').value = nombre;
                modal.querySelector('input[name="area"]').value = area || '';
                modal.querySelector('select[name="clave_depto"]').value = claveDepto;
                modal.querySelector('input[name="activo"]').checked = activo == 1;
                modal.querySelector('.modal-title').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Mando';
            } else {
                modal.querySelector('form').reset();
                modal.querySelector('.modal-title').innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Mando';
            }
        });
    });

    // Auto-fill area when department is selected in Mando modal
    const selectDeptoMando = document.getElementById('selectDeptoMando');
    if (selectDeptoMando) {
        selectDeptoMando.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const nombreDepto = selectedOption.dataset.nombre || '';
            const claveDepto = selectedOption.dataset.clave || '';
            const areaInput = document.querySelector('#modalMando input[name="area"]');
            const claveAreaInput = document.querySelector('#modalMando input[name="clave_area"]');
            if (areaInput && nombreDepto) {
                areaInput.value = nombreDepto;
            }
            if (claveAreaInput && claveDepto && !claveAreaInput.value) {
                claveAreaInput.value = claveDepto;
            }
        });
    }

    // Función genérica para cerrar modales correctamente
    function cerrarModalCorrectamente(modalId) {
        setTimeout(() => {
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }
            }
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }, 100);
    }

    // Cerrar modal Mando
    document.querySelectorAll('#modalMando form').forEach(form => {
        form.addEventListener('submit', function() {
            cerrarModalCorrectamente('modalMando');
        });
    });
    document.querySelectorAll('#modalMando [data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            cerrarModalCorrectamente('modalMando');
        });
    });

    // Cerrar modal Dirección
    document.querySelectorAll('#modalDireccion form').forEach(form => {
        form.addEventListener('submit', function() {
            cerrarModalCorrectamente('modalDireccion');
        });
    });
    document.querySelectorAll('#modalDireccion [data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            cerrarModalCorrectamente('modalDireccion');
        });
    });

    // Cerrar modal Subdirección
    document.querySelectorAll('#modalSubdireccion form').forEach(form => {
        form.addEventListener('submit', function() {
            cerrarModalCorrectamente('modalSubdireccion');
        });
    });
    document.querySelectorAll('#modalSubdireccion [data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            cerrarModalCorrectamente('modalSubdireccion');
        });
    });

    // Cerrar modal Departamento
    document.querySelectorAll('#modalDepartamento form').forEach(form => {
        form.addEventListener('submit', function() {
            cerrarModalCorrectamente('modalDepartamento');
        });
    });
    document.querySelectorAll('#modalDepartamento [data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            cerrarModalCorrectamente('modalDepartamento');
        });
    });
});
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
