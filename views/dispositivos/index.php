<?php
?>
<style>
    :root {
        --pantone-vino: #9F2241;
        --pantone-vino-dark: #691C32;
        --pantone-oro: #BC955C;
        --pantone-verde: #235B4E;
    }
    .dispositivos-content .card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }
    .dispositivos-content .card-header {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-dark) 100%);
        padding: 1rem 1.5rem;
        border: none;
        color: #fff;
    }
    .dispositivos-content .card-header h5 {
        font-weight: 600;
        margin: 0;
        color: #fff;
    }
    .dispositivos-content .card-header h2 {
        font-weight: 700;
        margin: 0;
        color: #fff;
    }
    .dispositivos-content .card-body {
        padding: 1.5rem;
    }
    .dispositivos-content .page-header {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-dark) 100%);
        padding: 1.5rem 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
    }
    .dispositivos-content .btn-agregar {
        background: linear-gradient(135deg, var(--pantone-verde) 0%, #1a3f36 100%);
        border: none;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .dispositivos-content .btn-agregar:hover {
        background: linear-gradient(135deg, #1a3f36 0%, var(--pantone-verde) 100%);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(35, 91, 78, 0.4);
    }
    .dispositivos-content .form-label {
        color: #333;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    [data-bs-theme="dark"] .dispositivos-content .form-label {
        color: #e0e0e0;
    }
    .dispositivos-content .form-select,
    .dispositivos-content .form-control {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 0.65rem 1rem;
        transition: all 0.3s ease;
    }
    [data-bs-theme="dark"] .dispositivos-content .form-select,
    [data-bs-theme="dark"] .dispositivos-content .form-control {
        border-color: #444;
        background-color: #2d2d2d;
        color: #e0e0e0;
    }
    .dispositivos-content .form-select:focus,
    .dispositivos-content .form-control:focus {
        border-color: var(--pantone-vino);
        box-shadow: 0 0 0 0.25rem rgba(159, 34, 65, 0.15);
    }
    .dispositivos-content .table {
        color: #333;
    }
    [data-bs-theme="dark"] .dispositivos-content .table {
        color: #e0e0e0;
    }
    .dispositivos-content .table thead th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: none;
        font-weight: 600;
        color: #333;
        padding: 1rem;
    }
    [data-bs-theme="dark"] .dispositivos-content .table thead th {
        background: linear-gradient(135deg, #2d2d2d 0%, #252525 100%);
        color: #e0e0e0;
    }
    .dispositivos-content .table tbody tr {
        transition: all 0.2s ease;
    }
    .dispositivos-content .table tbody tr:hover {
        background-color: rgba(159, 34, 65, 0.05);
    }
    [data-bs-theme="dark"] .dispositivos-content .table tbody tr:hover {
        background-color: rgba(159, 34, 65, 0.15);
    }
    .dispositivos-content .table td {
        vertical-align: middle;
        padding: 0.85rem 1rem;
        border-color: #f0f0f0;
    }
    [data-bs-theme="dark"] .dispositivos-content .table td {
        border-color: #333;
    }
    .dispositivos-content .badge {
        padding: 0.4rem 0.75rem;
        border-radius: 20px;
        font-weight: 500;
    }
    .dispositivos-content code {
        background: #f4f4f4;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #333;
    }
    [data-bs-theme="dark"] .dispositivos-content code {
        background: #3d3d3d;
        color: #e0e0e0;
    }
    .dispositivos-content .btn-test {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        border: none;
        color: #fff;
        padding: 0.4rem 0.6rem;
        border-radius: 8px;
    }
    .dispositivos-content .btn-test:hover {
        background: linear-gradient(135deg, #138496 0%, #17a2b8 100%);
        color: #fff;
    }
    .dispositivos-content .btn-sync {
        background: linear-gradient(135deg, var(--pantone-verde) 0%, #1a3f36 100%);
        border: none;
        color: #fff;
        padding: 0.4rem 0.6rem;
        border-radius: 8px;
    }
    .dispositivos-content .btn-sync:hover {
        background: linear-gradient(135deg, #1a3f36 0%, var(--pantone-verde) 100%);
        color: #fff;
    }
    .dispositivos-content .btn-edit {
        background: linear-gradient(135deg, var(--pantone-oro) 0%, #9a7a4d 100%);
        border: none;
        color: #fff;
        padding: 0.4rem 0.6rem;
        border-radius: 8px;
    }
    .dispositivos-content .btn-edit:hover {
        background: linear-gradient(135deg, #9a7a4d 0%, var(--pantone-oro) 100%);
        color: #fff;
    }
    .dispositivos-content .btn-toggle {
        border: none;
        color: #fff;
        padding: 0.4rem 0.6rem;
        border-radius: 8px;
    }
    .dispositivos-content .btn-toggle:hover {
        color: #fff;
    }
    .dispositivos-content .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border: 1px solid #28a745;
        border-radius: 10px;
        color: #155724;
    }
    [data-bs-theme="dark"] .dispositivos-content .alert-success {
        background: linear-gradient(135deg, #155724 0%, #28a745 100%);
        color: #d4edda;
    }
    .dispositivos-content .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border: 1px solid #dc3545;
        border-radius: 10px;
        color: #721c24;
    }
    [data-bs-theme="dark"] .dispositivos-content .alert-danger {
        background: linear-gradient(135deg, #721c24 0%, #dc3545 100%);
        color: #f8d7da;
    }
    .dispositivos-content .stat-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        padding: 1.25rem;
        border: 1px solid #e0e0e0;
    }
    [data-bs-theme="dark"] .dispositivos-content .stat-card {
        background: linear-gradient(135deg, #2d2d2d 0%, #252525 100%);
        border-color: #444;
    }
</style>

<div class="dispositivos-content" id="dispositivosContent">
<div class="container-fluid py-4">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="fas fa-fingerprint me-3"></i>Dispositivos Biométricos</h2>
        <a href="<?php echo BASE_URL; ?>/dispositivos/create" class="btn btn-agregar">
            <i class="fas fa-plus me-2"></i>Agregar Dispositivo
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-building me-2"></i>Filtrar por Sede:</label>
                    <select id="filtro-sede" class="form-select">
                        <option value="">Todas las sedes</option>
                        <?php foreach ($sedes as $sede): ?>
                            <option value="<?php echo htmlspecialchars($sede); ?>"><?php echo htmlspecialchars($sede); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-toggle-on me-2"></i>Filtrar por Estado:</label>
                    <select id="filtro-estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-search me-2"></i>Buscar:</label>
                    <input type="text" id="buscar" class="form-control" placeholder="Nombre, IP, modelo...">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Dispositivos (<?php echo count($dispositivos); ?>)</h5>
            <span class="badge" style="background: rgba(255,255,255,0.2); color: #fff;">
                <?php echo count(array_filter($dispositivos, fn($d) => $d['activo'])); ?> activos
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tabla-dispositivos">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-tag me-1"></i>Nombre</th>
                            <th><i class="fas fa-building me-1"></i>Sede</th>
                            <th><i class="fas fa-network-wired me-1"></i>IP:Puerto</th>
                            <th><i class="fas fa-microchip me-1"></i>Tipo/Modelo</th>
                            <th><i class="fas fa-hand-point-up me-1"></i>Capacidades</th>
                            <th><i class="fas fa-power-off me-1"></i>Estado</th>
                            <th><i class="fas fa-clock me-1"></i>Última Sync</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dispositivos as $disp): 
                            $capacidades = json_decode($disp['capacidades'], true);
                        ?>
                        <tr data-sede="<?php echo htmlspecialchars($disp['sede']); ?>" 
                            data-activo="<?php echo $disp['activo']; ?>">
                            <td><strong><?php echo $disp['dispositivo_id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($disp['nombre']); ?></strong>
                            </td>
                            <td>
                                <span class="badge" style="background: var(--pantone-vino); color: #fff;">
                                    <?php echo htmlspecialchars($disp['sede']); ?>
                                </span>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($disp['ip_address']); ?>:<?php echo $disp['puerto']; ?></code>
                            </td>
                            <td>
                                <span class="text-muted"><?php echo htmlspecialchars($disp['tipo_dispositivo']); ?></span>
                                <?php if ($disp['modelo']): ?>
                                    <br><small class="text-secondary"><?php echo htmlspecialchars($disp['modelo']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($capacidades['huella'] ?? false): ?>
                                    <span class="badge me-1" style="background: var(--pantone-verde); color: #fff;">
                                        <i class="fas fa-fingerprint"></i> Huella
                                    </span>
                                <?php endif; ?>
                                <?php if ($capacidades['cara'] ?? false): ?>
                                    <span class="badge" style="background: var(--pantone-oro); color: #fff;">
                                        <i class="fas fa-face-smile"></i> Cara
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($disp['activo']): ?>
                                    <span class="badge" style="background: var(--pantone-verde);">
                                        <i class="fas fa-check-circle me-1"></i>Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-power-off me-1"></i>Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($disp['ultima_sincronizacion']): ?>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($disp['ultima_sincronizacion'])); ?></small>
                                <?php else: ?>
                                    <small class="text-muted"><i class="fas fa-times me-1"></i>Nunca</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-test test-connection" 
                                            data-id="<?php echo $disp['dispositivo_id']; ?>"
                                            title="Probar Conexión">
                                        <i class="fas fa-plug"></i>
                                    </button>
                                    <button class="btn btn-sm btn-sync sync-device" 
                                            data-id="<?php echo $disp['dispositivo_id']; ?>"
                                            title="Sincronizar">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                    <a href="<?php echo BASE_URL; ?>/dispositivos/edit?id=<?php echo $disp['id']; ?>" 
                                       class="btn btn-sm btn-edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($disp['activo']): ?>
                                        <a href="<?php echo BASE_URL; ?>/dispositivos/toggleStatus?id=<?php echo $disp['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Desactivar"
                                           onclick="return confirm('¿Desactivar este dispositivo?');">
                                            <i class="fas fa-power-off"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>/dispositivos/toggleStatus?id=<?php echo $disp['id']; ?>" 
                                           class="btn btn-sm" style="background: var(--pantone-verde); color: #fff;" title="Activar"
                                           onclick="return confirm('¿Activar este dispositivo?');">
                                            <i class="fas fa-power-off"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Estadísticas por Sede</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($estadisticas as $stat): ?>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <h4 class="mb-1" style="color: var(--pantone-vino);"><?php echo $stat['total']; ?></h4>
                                <p class="mb-2 text-muted fw-bold"><?php echo htmlspecialchars($stat['sede']); ?></p>
                                <span class="badge me-1" style="background: var(--pantone-verde);">
                                    <i class="fas fa-check-circle me-1"></i><?php echo $stat['activos']; ?> activos
                                </span>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-power-off me-1"></i><?php echo $stat['total'] - $stat['activos']; ?> inactivos
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
$(function() {
    $('#filtro-sede, #filtro-estado, #buscar').on('input change', function() {
        var sede = $('#filtro-sede').val().toLowerCase();
        var estado = $('#filtro-estado').val();
        var buscar = $('#buscar').val().toLowerCase();

        $('#tabla-dispositivos tbody tr').each(function() {
            var $row = $(this);
            var sedeRow = $row.data('sede').toLowerCase();
            var activoRow = $row.data('activo').toString();
            var texto = $row.text().toLowerCase();

            var mostrar = true;

            if (sede && sedeRow !== sede) mostrar = false;
            if (estado && activoRow !== estado) mostrar = false;
            if (buscar && texto.indexOf(buscar) === -1) mostrar = false;

            $row.toggle(mostrar);
        });
    });

    $('.test-connection').click(function() {
        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post('<?php echo BASE_URL; ?>/dispositivos/test-connection', {dispositivo_id: id})
            .done(function(response) {
                if (response.success) {
                    alert('✓ Conexión exitosa\n\nEstado: ' + (response.data?.status || 'OK'));
                } else {
                    alert('✗ Error de conexión\n\n' + response.message);
                }
            })
            .fail(function() {
                alert('✗ Error al probar conexión');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-plug"></i>');
            });
    });

    $('.sync-device').click(function() {
        if (!confirm('¿Sincronizar empleados con este dispositivo?')) return;

        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post('<?php echo BASE_URL; ?>/dispositivos/sync', {dispositivo_id: id})
            .done(function(response) {
                if (response.success) {
                    alert('✓ Sincronización completada\n\n' + response.count + ' empleados sincronizados');
                    location.reload();
                } else {
                    alert('✗ Error en sincronización\n\n' + response.message);
                }
            })
            .fail(function() {
                alert('✗ Error al sincronizar');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i>');
            });
    });
});
</script>
