<?php
$currentUri = $_SERVER['REQUEST_URI'];
$baseUrlPath = parse_url(BASE_URL, PHP_URL_PATH) ?? '/';
$baseUrl = rtrim($baseUrlPath, '/');

// Obtener rol del usuario logueado
$rol = $_SESSION['rol'] ?? 'invitado';
$username = $_SESSION['username'] ?? 'Invitado';

// Debug: Forzar rol para pruebas (comentar en producción)
// $rol = 'jefe';

function isActive($uri, $currentUri, $baseUrl) {
    $fullUri = $baseUrl . $uri;
    if (strpos($currentUri, $fullUri) === 0) {
        if ($fullUri === $baseUrl . '/dashboard' && strlen($currentUri) > strlen($fullUri) && $currentUri !== $fullUri . '/') {
             return '';
        }
        return 'active';
    }
    return '';
}

// Definir menú principal con permisos por rol
$menuItems = [
    ['path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'fa-home', 'roles' => ['admin', 'jefe', 'usuario']],
    ['path' => '/empleados', 'label' => 'Empleados', 'icon' => 'fa-users', 'roles' => ['admin', 'jefe', 'usuario']],
    ['path' => '/asistencia', 'label' => 'Asistencia', 'icon' => 'fa-clock', 'roles' => ['admin', 'jefe', 'usuario']],
    ['path' => '/horarios', 'label' => 'Horarios', 'icon' => 'fa-calendar', 'roles' => ['admin', 'jefe']],
    ['path' => '/ciclos', 'label' => 'Ciclos', 'icon' => 'fa-sync', 'roles' => ['admin', 'jefe']],
    ['path' => '/reportes', 'label' => 'Reportes', 'icon' => 'fa-chart-bar', 'roles' => ['admin', 'jefe', 'usuario']],
    ['path' => '/justificaciones', 'label' => 'Justificaciones', 'icon' => 'fa-clipboard-check', 'roles' => ['admin', 'jefe']],
    ['path' => '/dispositivos', 'label' => 'Dispositivos', 'icon' => 'fa-server', 'roles' => ['admin']],
    ['path' => '/database', 'label' => 'Base de Datos', 'icon' => 'fa-database', 'roles' => ['admin']],
    ['path' => '/logs', 'label' => 'Logs de Errores', 'icon' => 'fa-file-alt', 'roles' => ['admin']],
    ['path' => '/configuracion', 'label' => 'Configuración', 'icon' => 'fa-cog', 'roles' => ['admin']],
];

// Submenú Catálogos (solo admin)
$catalogosItems = [
    ['path' => '/usuarios', 'label' => 'Usuarios', 'icon' => 'fa-user-cog', 'roles' => ['admin']],
];

// Filtrar menú según rol
$menuFiltrado = array_filter($menuItems, function($item) use ($rol) {
    return in_array($rol, $item['roles']);
});

$catalogosFiltrado = array_filter($catalogosItems, function($item) use ($rol) {
    return in_array($rol, $item['roles']);
});

// Debug: verificar rol actual
// error_log("Menu - Rol: " . $rol);

// Verificar si estamos en una ruta de catálogos
$enCatalogos = in_array($rol, ['admin']) && (
    strpos($currentUri, $baseUrl . '/usuarios') === 0 ||
    strpos($currentUri, $baseUrl . '/empleados') === 0 ||
    strpos($currentUri, $baseUrl . '/horarios') === 0 ||
    strpos($currentUri, $baseUrl . '/ciclos') === 0
);

function getUserRoleLabel($rol) {
    return match($rol) {
        'admin' => 'Administrador',
        'jefe' => 'Jefe de Área',
        'usuario' => 'Usuario',
        default => 'Invitado'
    };
}
?>
<style>
    .main-menu { 
        list-style: none; 
        padding: 0; 
        margin: 0; 
        background-color: #f4f4f4; 
        border-bottom: 1px solid #ddd; 
        display: flex; 
        flex-wrap: wrap;
    }
    .main-menu li { margin: 0; position: relative; }
    .main-menu > li > a { 
        display: block; 
        padding: 15px 20px; 
        text-decoration: none; 
        color: #333;
        transition: background-color 0.2s;
        cursor: pointer;
    }
    .main-menu a:hover { 
        background-color: #e9e9e9; 
    }
    .main-menu > li > a.active { 
        background-color: #972241; 
        color: white; 
        font-weight: bold; 
    }
    
    /* Dropdown Catálogos */
    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: white;
        border: 1px solid #ddd;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        min-width: 200px;
        z-index: 1000;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .dropdown-menu.show {
        display: block;
    }
    .dropdown-menu li a {
        display: block;
        padding: 10px 15px;
        text-decoration: none;
        color: #333;
        border-bottom: 1px solid #f0f0f0;
    }
    .dropdown-menu li a:hover {
        background-color: #f4f4f4;
    }
    .dropdown-menu li a.active {
        background-color: #972241;
        color: white;
    }
    .dropdown-toggle::after {
        display: inline-block;
        margin-left: 5px;
        content: "▼";
        font-size: 0.7em;
    }
    
    .user-info {
        padding: 10px 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
    }
    .user-info .badge-rol {
        background: #972241;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
    }
</style>

<div class="user-info">
    <div>
        <i class="fas fa-user-circle me-1"></i>
        <strong><?= htmlspecialchars($username) ?></strong>
        <span class="badge-rol ms-2"><?= getUserRoleLabel($rol) ?></span>
    </div>
    <div>
        <a href="<?= $baseUrl ?>/logout" class="text-danger text-decoration-none">
            <i class="fas fa-sign-out-alt me-1"></i>Salir
        </a>
    </div>
</div>

<nav>
    <ul class="main-menu">
        <?php foreach ($menuFiltrado as $item): ?>
        <li>
            <a href="<?= $baseUrl . $item['path'] ?>" class="<?= isActive($item['path'], $currentUri, $baseUrl) ?>">
                <i class="fas <?= $item['icon'] ?> me-2"></i><?= $item['label'] ?>
            </a>
        </li>
        <?php endforeach; ?>
        
        <?php if (!empty($catalogosFiltrado)): ?>
        <li class="dropdown">
            <a href="#" class="dropdown-toggle <?= $enCatalogos ? 'active' : '' ?>" onclick="event.preventDefault(); this.nextElementSibling.classList.toggle('show');">
                <i class="fas fa-folder-open me-2"></i>Catálogos
            </a>
            <ul class="dropdown-menu <?= $enCatalogos ? 'show' : '' ?>">
                <?php foreach ($catalogosFiltrado as $item): ?>
                <li>
                    <a href="<?= $baseUrl . $item['path'] ?>" class="<?= isActive($item['path'], $currentUri, $baseUrl) ?>">
                        <i class="fas <?= $item['icon'] ?> me-2"></i><?= $item['label'] ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endif; ?>
    </ul>
</nav>
