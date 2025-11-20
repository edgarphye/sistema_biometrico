<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Tema Claro (por defecto) */
        body {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        body.dark-theme {
            background-color: #1a1a1a;
            color: #e9ecef;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background-color: #343a40;
            z-index: 1000;
            transition: transform 0.3s ease, background-color 0.3s ease;
            overflow-y: auto;
        }
        body.dark-theme .sidebar {
            background-color: #2d3436;
        }
        .sidebar.collapsed {
            transform: translateX(-250px);
        }
        .sidebar .nav-link {
            color: #ffffff;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
            color: #ffffff;
        }
        body.dark-theme .sidebar .nav-link:hover {
            background-color: #636e72;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        .main-content.expanded {
            margin-left: 0;
        }

        /* Botones de control */
        .sidebar-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1050;
            background-color: #343a40;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        body.dark-theme .sidebar-toggle {
            background-color: #2d3436;
        }
        .sidebar-toggle:hover {
            background-color: #495057;
        }
        body.dark-theme .sidebar-toggle:hover {
            background-color: #636e72;
        }

        /* Botón de tema */
        .theme-toggle {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1050;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-toggle:hover {
            background-color: #5a6268;
            transform: scale(1.1);
        }

        /* Información de usuario */
        .user-info {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid #495057;
        }
        body.dark-theme .user-info {
            border-top-color: #636e72;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #495057;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }
        body.dark-theme .user-avatar {
            background-color: #636e72;
        }

        /* Overlay para móvil */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        /* Tema oscuro para elementos principales */
        body.dark-theme .card {
            background-color: #2d3436;
            border-color: #636e72;
            color: #e9ecef;
        }
        body.dark-theme .card-header {
            background-color: #34495e;
            border-color: #636e72;
        }
        body.dark-theme .table {
            color: #e9ecef;
        }
        body.dark-theme .table-hover tbody tr:hover {
            background-color: rgba(255,255,255,0.05);
        }
        body.dark-theme .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255,255,255,0.03);
        }
        body.dark-theme .table-striped tbody tr:nth-of-type(even) {
            background-color: rgba(255,255,255,0.01);
        }
        body.dark-theme .table-hover tbody tr:hover {
            background-color: rgba(255,255,255,0.08);
        }
        body.dark-theme .alert {
            background-color: #34495e;
            border-color: #636e72;
            color: #e9ecef;
        }
        body.dark-theme .jumbotron {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #e9ecef;
        }

        /* Mejoras para badges en modo oscuro */
        body.dark-theme .badge {
            color: #ffffff !important;
        }
        body.dark-theme .badge-success {
            background-color: #28a745 !important;
            color: #ffffff !important;
        }
        body.dark-theme .badge-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }
        body.dark-theme .badge-danger {
            background-color: #dc3545 !important;
            color: #ffffff !important;
        }
        body.dark-theme .badge-info {
            background-color: #17a2b8 !important;
            color: #ffffff !important;
        }
        body.dark-theme .badge-secondary {
            background-color: #6c757d !important;
            color: #ffffff !important;
        }
        body.dark-theme .badge-light {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }

        /* Texto pequeño en modo oscuro */
        body.dark-theme small {
            color: #adb5bd !important;
        }

        /* Mini avatar del usuario */
        .user-mini-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        body.dark-theme .user-mini-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Botón fijo de expansión */
        .sidebar-expand-fixed {
            position: fixed;
            top: 50%;
            left: 15px;
            z-index: 1049;
            background-color: #343a40;
            color: white;
            border: none;
            border-radius: 5px;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
        }
        body.dark-theme .sidebar-expand-fixed {
            background-color: #2d3436;
        }
        .sidebar-expand-fixed:hover {
            background-color: #495057;
            transform: translateY(-50%) scale(1.1);
        }
        body.dark-theme .sidebar-expand-fixed:hover {
            background-color: #636e72;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }
            .sidebar.collapsed {
                transform: translateX(-250px);
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.expanded {
                margin-left: 0;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-overlay.show {
                display: block;
            }
            .theme-toggle {
                right: 70px; /* Ajustar posición en móvil */
            }
        }
    </style>
</head>
<body>
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" title="Cambiar Tema">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle d-md-none" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Fixed Expand Button for Desktop -->
    <button class="sidebar-expand-fixed d-none d-md-block" id="sidebarExpandFixed" title="Expandir Sidebar" style="display: none;">
        <i class="fas fa-chevron-right"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-sticky">
            <div class="d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center">
                    <?php
                    require_once 'controllers/AuthController.php';
                    if (AuthController::isLoggedIn()): ?>
                        <div class="user-mini-avatar me-2">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <h5 class="text-white mb-0"><?php echo APP_NAME; ?></h5>
                </div>
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-outline-light d-md-block d-none me-2" id="sidebarCollapse" title="Colapsar Sidebar">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-light d-md-block d-none" id="sidebarExpand" title="Expandir Sidebar" style="display: none;">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/empleados">
                        <i class="fas fa-users"></i> Empleados
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/asistencia">
                        <i class="fas fa-clock"></i> Asistencia
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/reportes">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/biometricos">
                        <i class="fas fa-fingerprint"></i> Dispositivos Biométricos
                    </a>
                </li>
            </ul>

            <?php
            require_once 'controllers/AuthController.php';
            if (AuthController::isLoggedIn()): ?>
            <div class="user-info">
                <div class="d-flex align-items-center mb-2">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="text-white small"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div class="text-white-50 small"><?php echo htmlspecialchars($_SESSION['rol']); ?></div>
                    </div>
                </div>
                <a class="btn btn-outline-danger btn-sm w-100" href="<?php echo BASE_URL; ?>/logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main content -->
    <main class="main-content" id="mainContent">
        <?php if (isset($content)) echo $content; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Función para guardar preferencia de tema
            function saveThemePreference(theme) {
                localStorage.setItem('theme', theme);
            }

            // Función para cargar preferencia de tema
            function loadThemePreference() {
                return localStorage.getItem('theme') || 'light';
            }

            // Función para aplicar tema
            function applyTheme(theme) {
                if (theme === 'dark') {
                    $('body').addClass('dark-theme');
                    $('#themeToggle i').removeClass('fa-moon').addClass('fa-sun');
                } else {
                    $('body').removeClass('dark-theme');
                    $('#themeToggle i').removeClass('fa-sun').addClass('fa-moon');
                }
            }

            // Cargar tema guardado
            const savedTheme = loadThemePreference();
            applyTheme(savedTheme);

            // Toggle tema
            $('#themeToggle').click(function() {
                const currentTheme = $('body').hasClass('dark-theme') ? 'dark' : 'light';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                applyTheme(newTheme);
                saveThemePreference(newTheme);
            });

            const sidebar = $('#sidebar');
            const mainContent = $('#mainContent');
            const sidebarToggle = $('#sidebarToggle');
            const sidebarCollapse = $('#sidebarCollapse');
            const sidebarExpand = $('#sidebarExpand');
            const sidebarOverlay = $('#sidebarOverlay');

            // Toggle sidebar on mobile
            sidebarToggle.click(function() {
                sidebar.toggleClass('show');
                sidebarOverlay.toggleClass('show');
            });

            // Close sidebar when clicking overlay
            sidebarOverlay.click(function() {
                sidebar.removeClass('show');
                sidebarOverlay.removeClass('show');
            });

            // Collapse sidebar on desktop
            sidebarCollapse.click(function() {
                sidebar.addClass('collapsed');
                mainContent.addClass('expanded');
                sidebarCollapse.hide();
                sidebarExpand.show();
                $('#sidebarExpandFixed').show();
            });

            // Expand sidebar on desktop
            sidebarExpand.click(function() {
                sidebar.removeClass('collapsed');
                mainContent.removeClass('expanded');
                sidebarExpand.hide();
                sidebarCollapse.show();
                $('#sidebarExpandFixed').hide();
            });

            // Fixed expand button
            $('#sidebarExpandFixed').click(function() {
                sidebar.removeClass('collapsed');
                mainContent.removeClass('expanded');
                sidebarExpand.hide();
                sidebarCollapse.show();
                $(this).hide();
            });

            // Auto-expand sidebar on hover (solo cuando está colapsado)
            sidebar.hover(
                function() {
                    if ($(this).hasClass('collapsed')) {
                        $(this).removeClass('collapsed');
                        mainContent.removeClass('expanded');
                    }
                },
                function() {
                    if (!sidebarCollapse.is(':visible')) {
                        $(this).addClass('collapsed');
                        mainContent.addClass('expanded');
                    }
                }
            );

            // Close sidebar on mobile when clicking a nav link
            $('.nav-link').click(function() {
                if ($(window).width() < 768) {
                    sidebar.removeClass('show');
                    sidebarOverlay.removeClass('show');
                }
            });
        });
    </script>
</body>
</html>
