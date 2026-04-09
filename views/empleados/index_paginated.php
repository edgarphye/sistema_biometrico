<?php
if (!defined('BASE_URL')) {
    http_response_code(403);
    die('Direct access not allowed');
}

ob_start();
// Variables de paginación
$page = $page ?? 1;
$limit = $limit ?? 20;
$total = $total ?? 0;
$search = $search ?? '';
$area = $area ?? '';
$jerarquia = $jerarquia ?? '';
$pagination = $pagination ?? [
    'current_page' => 1,
    'per_page' => 20,
    'total' => 0,
    'total_pages' => 1,
    'has_next' => false,
    'has_prev' => false
];

// Si es una petición AJAX, devolver JSON y terminar
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    ob_clean(); // Limpiar buffer iniciado arriba para evitar HTML extra
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'empleados' => $empleados ?? [],
        'pagination' => $pagination,
        'filters' => [
            'search' => $search,
            'area' => $area,
            'jerarquia' => $jerarquia
        ]
    ]);
    exit;
}
?>
<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Empleados</h2>
        <div>
            <button class="btn btn-outline-secondary me-2" id="btn-refresh" title="Refrescar lista"><i class="fas fa-sync-alt"></i></button>
            <a href="<?php echo BASE_URL; ?>/empleados/create" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Empleado</a>
        </div>
    </div>

    <!-- Filtros de búsqueda -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="search-empleados" class="form-control" placeholder="Buscar por nombre, RFC..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select id="filter-area" class="form-select">
                <option value="">Todas las áreas</option>
                <?php
                // Obtener áreas únicas de empleados
                $areas = [];
                if (!empty($empleados) && is_array($empleados)) {
                    foreach ($empleados as $empleado) {
                        if (!empty($empleado['area']) && !in_array($empleado['area'], $areas)) {
                            $areas[] = $empleado['area'];
                        }
                    }
                }
                sort($areas);
                foreach ($areas as $areaOption) {
                    $selected = ($area === $areaOption) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($areaOption) . '" ' . $selected . '>' . htmlspecialchars($areaOption) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <select id="per-page" class="form-select">
                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 por página</option>
                <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20 por página</option>
                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 por página</option>
                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 por página</option>
            </select>
        </div>
        <div class="col-md-1">
            <div class="text-muted small">
                <span class="badge bg-info"><?php echo $total; ?> empleados</span>
            </div>
        </div>
    </div>

    <!-- Loading indicator -->
    <div class="text-center py-5" id="loading-indicator" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2">Cargando empleados...</p>
    </div>

    <!-- Results count and pagination info -->
    <div class="row mb-3" id="results-info" style="display: none;">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">
                    Mostrando <span id="showing-from">1</span> a <span id="showing-to">20</span> 
                    de <span id="total-results">0</span> empleados
                </span>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" id="page-prev" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="btn btn-sm btn-outline-secondary disabled" id="current-page">1</span>
                    <button class="btn btn-sm btn-outline-primary" id="page-next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees grid -->
    <div id="empleados-list">
        <?php if (!empty($empleados) && is_array($empleados)): ?>
            <?php foreach ($empleados as $empleado): ?>
                <div class="card h-100 employee-card border-0 shadow-sm">
                    <div class="card-body text-center p-2 d-flex flex-column">
                        <!-- Status Badge -->
                        <div class="position-absolute top-0 end-0 mt-2 me-2">
                            <span class="badge rounded-pill <?php echo $empleado['activo'] ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $empleado['activo'] ? 'ACT' : 'INA'; ?>
                            </span>
                        </div>

                        <!-- Avatar -->
                        <div class="avatar-wrapper mb-3 mx-auto">
                            <?php if (!empty($empleado['foto_cara'])): ?>
                                <img src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/' . $empleado['foto_cara']); ?>" 
                                     alt="Foto" 
                                     class="avatar-img rounded-circle shadow-sm lazyload"
                                     style="width: 64px; height: 64px; object-fit: cover;"
                                     data-src="<?php echo htmlspecialchars($empleado['foto_cara']); ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle bg-gradient-light d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 64px; height: 64px;">
                                    <i class="fas fa-user text-secondary fs-5"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-dark fw-bold text-truncate" style="font-size: 0.9rem;" title="<?php echo htmlspecialchars(($empleado['nombre'] ?? '') . ' ' . ($empleado['apellido'] ?? '')); ?>">
                                <?php echo htmlspecialchars(($empleado['nombre'] ?? '') . ' ' . ($empleado['apellido'] ?? '')); ?>
                                <span class="badge bg-secondary ms-1" style="font-size: 0.6rem;">ID: <?php echo $empleado['id']; ?></span>
                            </h6>
                            </h6>
                            <small class="text-muted d-block mb-2" style="font-size: 0.75rem;">
                                <?php echo htmlspecialchars($empleado['area'] ?? 'Sin Área'); ?>
                            </small>
                        </div>

                        <!-- Actions -->
                        <div class="mt-auto">
                            <div class="d-flex gap-2 justify-content-center">
                                <button class="btn btn-sm btn-outline-dark ver-empleado" data-id="<?php echo $empleado['id']; ?>" style="font-size: 0.75rem; padding: 4px 10px;">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary editar-empleado" data-id="<?php echo $empleado['id']; ?>" style="font-size: 0.75rem; padding: 4px 10px;">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <?php if (!empty($search) || !empty($area)): ?>
                        No se encontraron empleados con los filtros especificados.
                    <?php else: ?>
                        No hay empleados registrados.
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- No results message (hidden by default) -->
    <div class="col-12 text-center py-5" id="no-results" style="display: none;">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            No se encontraron empleados con los filtros especificados.
        </div>
    </div>

    <!-- Paginación inferior -->
    <div class="row mt-4" id="pagination-bottom">
        <div class="col-12 text-center">
            <nav aria-label="Paginación de empleados">
                <ul class="pagination justify-content-center">
                    <!-- Se generará dinámicamente con JavaScript -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- JavaScript para paginación y filtros -->
<script>
const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
</script>
<script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/empleados.js?v=<?php echo time(); ?>"></script>
<script>
// Inicializar configuración desde variables PHP
if (typeof window.empleadosApp !== 'undefined') {
    window.empleadosApp.config.baseUrl = '<?php echo rtrim(BASE_URL, '/'); ?>';
    window.empleadosApp.config.currentPage = <?php echo $page ?? 1; ?>;
    window.empleadosApp.config.currentLimit = <?php echo $limit ?? 20; ?>;
    window.empleadosApp.config.currentSearch = '<?php echo htmlspecialchars($search ?? ''); ?>';
    window.empleadosApp.config.currentArea = '<?php echo htmlspecialchars($area ?? ''); ?>';
    
    // Cargar estado inicial
    window.empleadosApp.state.empleados = <?php echo json_encode($empleados ?? []); ?>;
    window.empleadosApp.state.pagination = <?php echo json_encode($pagination ?? []); ?>;
}
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>