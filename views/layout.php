<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema Biométrico</title>
<link rel="icon" type="image/svg+xml" href="<?php echo rtrim(BASE_URL, '/'); ?>/favicon.svg">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/dataTables.bootstrap5.min.css">
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/chart.umd.min.js"></script>
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/jquery-3.6.0.min.js"></script>
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/modals.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/empleados.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/colores-pantone.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/database.css" rel="stylesheet">
    
    <!-- Cache control headers -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Scripts de Bootstrap y jQuery cargados en el head para asegurar disponibilidad -->
   
    
    <script<?= SecurityHelper::nonceAttr() ?>>
        // Prevenir inicialización automática de modales hasta que Bootstrap esté completamente cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que Bootstrap esté disponible
            function waitForBootstrap() {
                if (typeof bootstrap !== 'undefined') {
                    console.log('Bootstrap cargado correctamente');
                } else {
                    console.log('Bootstrap no cargado, esperando...');
                    setTimeout(waitForBootstrap, 100);
                }
            }
            waitForBootstrap();
        });
    </script>
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/jquery.dataTables.min.js"></script>
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/dataTables.bootstrap5.min.js"></script>

    <style<?= SecurityHelper::nonceAttr() ?>>
        /* Variables CSS con colores Pantone Institucionales Exactos */
        :root {
            --pantone-vino: #9F2241;
            --pantone-vino-oscuro: #691C32;
            --pantone-verde: #235B4E;
            --pantone-verde-oscuro: #10312B;
            --pantone-dorado: #DDC9A3;
            --pantone-dorado-oscuro: #BC955C;
            --pantone-gris: #98989A;
            --pantone-gris-oscuro: #6F7271;
            --pantone-white: #FFFFFF;
            --pantone-success: #2E7D32;
            --pantone-danger: #C62828;
            --pantone-warning: #F57C00;
            --pantone-slate: #455A64;
            --pantone-gray: #ECEFF1;
        }

        /* Tema Claro */
        body {
            background-color: #F5F7FA;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 50%, #4a1424 100%);
            z-index: 1000;
            transition: transform 0.3s ease, background 0.3s ease;
            overflow-y: auto;
            box-shadow: 4px 0 25px rgba(159, 34, 65, 0.25);
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'%3E%3Cpath d='M0 40L40 0H20L0 20M40 40V20L20 40'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }

        .sidebar.collapsed {
            transform: translateX(-250px);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 14px 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 10px;
            margin: 6px 12px;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
            z-index: -1;
        }

        .sidebar .nav-link:hover {
            color: white;
            transform: translateX(6px);
        }

        .sidebar .nav-link:hover::before {
            width: 100%;
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
            border-left: 4px solid var(--pantone-dorado);
            box-shadow: 0 4px 15px rgba(221, 201, 163, 0.35);
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        /* Botones de control */
        .sidebar-toggle {
            display: none;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(159, 34, 65, 0.35);
        }

        /* Botón de tema */
        .theme-toggle {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1050;
            background: linear-gradient(135deg, var(--pantone-verde) 0%, var(--pantone-verde-oscuro) 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(35, 91, 78, 0.35);
        }

        .theme-toggle:hover {
            transform: scale(1.15) rotate(15deg);
            box-shadow: 0 8px 25px rgba(35, 91, 78, 0.45);
        }

        /* Top Bar */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(90deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1030;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
        }
        .top-bar-left {
            display: flex;
            align-items: center;
        }
        .top-bar-left .sidebar-toggle, 
        .top-bar-left .mobile-menu-btn {
            display: flex;
        }
        @media (min-width: 768px) {
            .top-bar-left .sidebar-toggle,
            .top-bar-left .mobile-menu-btn {
                display: none;
            }
        }
        .top-bar-collapse {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 8px;
        }
        .top-bar-collapse:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }
        
        /* Mobile Menu Toggle (Hamburger) */
        .mobile-menu-btn {
            display: none;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 8px;
        }
        
        .top-bar-brand {
            color: white;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
        }
        .top-bar-center {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Usuario en barra superior - Desktop */
        .top-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.12);
            padding: 6px 20px 6px 6px;
            border-radius: 25px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            width: auto;
            min-width: 350px;
        }
        .top-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--pantone-vino-oscuro);
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 3px 12px rgba(221, 201, 163, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
            flex-shrink: 0;
        }
        .top-user-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .top-user-name {
            color: white;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.3;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .top-user-role {
            color: var(--pantone-dorado);
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .top-user-area {
            margin-top: 4px;
        }
        .top-user-area .badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            color: var(--pantone-vino-oscuro);
            box-shadow: 0 2px 6px rgba(221, 201, 163, 0.3);
        }
        .top-user-info:hover {
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Botones de la barra */
        .top-bar-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
        }
        .theme-toggle-btn {
            background: rgba(255, 255, 255, 0.15);
        }
        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }
        .top-bar-logout {
            background: rgba(220, 53, 69, 0.8);
        }
        .top-bar-logout:hover {
            background: #dc3545;
            transform: scale(1.1);
        }

        .dropdown-perfil .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(159, 34, 65, 0.1) 0%, rgba(35, 91, 78, 0.1) 100%);
            transform: translateX(5px);
        }

        /* Dark theme for top bar */
        body.dark-theme .top-bar {
            background: linear-gradient(90deg, var(--pantone-vino-oscuro) 0%, #4a1525 100%);
        }

        /* Main content */
        .main-content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 25px;
            transition: margin-left 0.3s ease, width 0.3s ease;
            min-height: calc(100vh - 60px);
            background: #F5F7FA;
            padding-bottom: 80px;
            overflow-x: hidden;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }

        .main-content.expanded {
            margin-left: 0;
            width: 100%;
        }
        
        /* Footer ajustado */
        .site-footer {
            position: fixed;
            bottom: 0;
            left: 250px;
            right: 0;
            z-index: 100;
            transition: left 0.3s ease;
        }
        
        .site-footer.expanded {
            left: 0;
        }

        /* Tarjetas */
        .card {
            background: white;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--pantone-vino) 0%, var(--pantone-verde) 50%, var(--pantone-dorado) 100%);
        }

        .card-header {
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            color: white;
            border-radius: 16px 16px 0 0 !important;
            padding: 18px 24px;
            border: none;
            font-weight: 600;
        }

        .card-body {
            padding: 24px;
            background: white;
        }

        /* Botones */
        .btn-primary {
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(159, 34, 65, 0.25);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--pantone-vino-oscuro) 0%, #4a1424 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(159, 34, 65, 0.35);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--pantone-verde) 0%, var(--pantone-verde-oscuro) 100%);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(35, 91, 78, 0.25);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--pantone-verde-oscuro) 0%, #0a1f1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(35, 91, 78, 0.35);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            color: var(--pantone-vino-oscuro);
            box-shadow: 0 4px 12px rgba(221, 201, 163, 0.25);
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, var(--pantone-dorado-oscuro) 0%, #8B6914 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(221, 201, 163, 0.35);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--pantone-danger) 0%, #B71C1C 100%);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(198, 40, 40, 0.25);
        }

        .btn-outline-primary {
            color: var(--pantone-vino);
            border: 2px solid var(--pantone-vino);
            border-radius: 10px;
            font-weight: 600;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--pantone-vino);
            color: white;
        }

        /* Formularios */
        .form-control {
            border: 2px solid var(--pantone-gray);
            border-radius: 10px;
            padding: 14px 18px;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus {
            border-color: var(--pantone-vino);
            box-shadow: 0 0 0 4px rgba(159, 34, 65, 0.15);
        }

        .form-label {
            color: var(--pantone-slate);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-select {
            border: 2px solid var(--pantone-gray);
            border-radius: 10px;
            padding: 14px 18px;
        }

        .form-select:focus {
            border-color: var(--pantone-vino);
            box-shadow: 0 0 0 4px rgba(159, 34, 65, 0.15);
        }

        /* Badges */
        .badge {
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .badge-primary {
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
        }

        .badge-success {
            background: linear-gradient(135deg, var(--pantone-verde) 0%, var(--pantone-verde-oscuro) 100%);
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            color: var(--pantone-vino-oscuro);
        }

        .badge-danger {
            background: linear-gradient(135deg, var(--pantone-danger) 0%, #B71C1C 100%);
        }

        /* Tablas */
        .table thead th {
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            color: white;
            border: none;
            padding: 16px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 16px;
            border-bottom: 1px solid var(--pantone-gray);
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background: linear-gradient(135deg, rgba(159, 34, 65, 0.05) 0%, rgba(35, 91, 78, 0.05) 100%);
        }

        /* Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 18px 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .alert-success {
            background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
            border-left: 5px solid var(--pantone-verde);
            color: var(--pantone-verde-oscuro);
        }

        .alert-warning {
            background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%);
            border-left: 5px solid var(--pantone-dorado);
            color: #6a5210;
        }

        .alert-danger {
            background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
            border-left: 5px solid var(--pantone-danger);
            color: #B71C1C;
        }

        .alert-info {
            background: linear-gradient(135deg, #FCE4EC 0%, #F8BBD9 100%);
            border-left: 5px solid var(--pantone-vino);
            color: var(--pantone-vino-oscuro);
        }

        /* Información de usuario */
        .user-info {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            margin: 0 12px;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--pantone-vino-oscuro);
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(221, 201, 163, 0.4);
        }

        .user-mini-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--pantone-vino-oscuro);
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 3px 10px rgba(221, 201, 163, 0.35);
        }

        /* Overlay para móvil */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(159, 34, 65, 0.6);
            z-index: 1099;
            display: none;
            backdrop-filter: blur(4px);
        }
        
        .sidebar-overlay.show {
            display: block;
        }

        /* Botón fijo de expansión */
        .sidebar-expand-fixed {
            position: fixed;
            top: 50%;
            left: 15px;
            z-index: 1049;
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            width: 42px;
            height: 42px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(159, 34, 65, 0.25);
        }

        .sidebar-expand-fixed:hover {
            transform: translateY(-50%) scale(1.15);
            box-shadow: 0 8px 25px rgba(159, 34, 65, 0.35);
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 3px solid var(--pantone-gray);
        }

        .nav-tabs .nav-link {
            color: var(--pantone-slate);
            border: none;
            border-radius: 10px 10px 0 0;
            padding: 14px 22px;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: -3px;
        }

        .nav-tabs .nav-link:hover {
            color: var(--pantone-vino);
            background: var(--pantone-gray);
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(159, 34, 65, 0.3);
        }

        /* Modales */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 22px 28px;
            border: none;
        }

        .modal-header.verde {
            background: linear-gradient(135deg, var(--pantone-verde) 0%, var(--pantone-verde-oscuro) 100%);
        }

        .modal-header.dorado {
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            color: var(--pantone-vino-oscuro);
        }

        .modal-body {
            padding: 28px;
            background: white;
        }

        .modal-footer {
            padding: 18px 28px;
            background: var(--pantone-gray);
            border-radius: 0 0 20px 20px;
        }

        /* Dropdown */
        .dropdown-menu {
            border: none;
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            padding: 10px;
        }

        .dropdown-item {
            border-radius: 10px;
            padding: 12px 18px;
            color: var(--pantone-slate);
            transition: all 0.3s;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(159, 34, 65, 0.25);
        }

        /* Pagination */
        .page-link {
            border: none;
            border-radius: 10px;
            margin: 0 4px;
            color: var(--pantone-slate);
            font-weight: 500;
            transition: all 0.3s;
            padding: 10px 16px;
        }

        .page-link:hover {
            background: var(--pantone-gray);
            color: var(--pantone-vino);
            box-shadow: 0 4px 12px rgba(159, 34, 65, 0.15);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
            box-shadow: 0 4px 15px rgba(159, 34, 65, 0.35);
        }

        /* ===================== TEMA OSCURO ===================== */
        
        body.dark-theme {
            background-color: #1a1a1a;
            color: #E0E0E0;
        }

        body.dark-theme .sidebar {
            background: linear-gradient(180deg, var(--pantone-verde-oscuro) 0%, #0a1f1c 100%);
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.4);
        }

        body.dark-theme .sidebar-toggle {
            background: linear-gradient(135deg, var(--pantone-verde) 0%, var(--pantone-verde-oscuro) 100%);
        }

        body.dark-theme .theme-toggle {
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            color: var(--pantone-vino-oscuro);
            box-shadow: 0 4px 15px rgba(221, 201, 163, 0.35);
        }

        body.dark-theme .card {
            background-color: #2d2d2d;
            border: 1px solid #3d3d3d;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            color: #E0E0E0;
        }

        body.dark-theme .card-header {
            background: linear-gradient(135deg, var(--pantone-vino-oscuro) 0%, #4a1424 100%);
            border-bottom: 3px solid var(--pantone-dorado);
        }

        body.dark-theme .card-body {
            background-color: #2d2d2d;
        }

        /* Tablas en tema oscuro */
        body.dark-theme .table thead th {
            background: linear-gradient(135deg, var(--pantone-vino-oscuro) 0%, #4a1424 100%);
        }

        body.dark-theme .table tbody td {
            background-color: #2d2d2d !important;
            color: #E0E0E0 !important;
            border-color: #3d3d3d !important;
        }

        body.dark-theme .table-hover tbody tr:hover td {
            background-color: #353535 !important;
        }

        /* Formularios en tema oscuro */
        body.dark-theme .form-label {
            color: var(--pantone-dorado) !important;
        }

        body.dark-theme .form-control,
        body.dark-theme .form-select {
            background-color: #1a1a1a;
            border: 2px solid #3d3d3d;
            color: #E0E0E0;
            border-radius: 10px;
        }

        body.dark-theme .form-control:focus,
        body.dark-theme .form-select:focus {
            border-color: var(--pantone-dorado);
            box-shadow: 0 0 0 4px rgba(221, 201, 163, 0.2);
        }

        body.dark-theme .form-control::placeholder {
            color: var(--pantone-gris-oscuro);
        }

        /* Alerts en tema oscuro */
        body.dark-theme .alert {
            background-color: #2d2d2d;
            border-color: #3d3d3d;
        }

        body.dark-theme .alert-success {
            border-left-color: var(--pantone-verde);
            background: linear-gradient(135deg, #1a2e28 0%, #122220 100%);
        }

        body.dark-theme .alert-warning {
            border-left-color: var(--pantone-dorado);
            background: linear-gradient(135deg, #3d2e1f 0%, #2a2115 100%);
        }

        body.dark-theme .alert-danger {
            border-left-color: var(--pantone-danger);
            background: linear-gradient(135deg, #3d1f1f 0%, #2a1515 100%);
        }

        /* Modales en tema oscuro */
        body.dark-theme .modal-content {
            background-color: #2d2d2d;
            border: 1px solid #3d3d3d;
        }

        body.dark-theme .modal-header {
            background: linear-gradient(135deg, var(--pantone-vino-oscuro) 0%, #4a1424 100%);
            border-bottom: 3px solid var(--pantone-dorado);
        }

        body.dark-theme .modal-body {
            background-color: #2d2d2d;
        }

        body.dark-theme .modal-footer {
            background-color: #252525;
            border-top-color: #3d3d3d;
        }

        /* Dropdown en tema oscuro */
        body.dark-theme .dropdown-menu {
            background-color: #2d2d2d;
            border: 1px solid #3d3d3d;
        }

        body.dark-theme .dropdown-item:hover {
            background: linear-gradient(135deg, var(--pantone-vino-oscuro) 0%, #4a1424 100%);
        }

        /* Pagination en tema oscuro */
        body.dark-theme .page-link {
            background-color: #2d2d2d;
            border-color: #3d3d3d;
            color: #E0E0E0;
        }

        body.dark-theme .page-link:hover {
            background-color: var(--pantone-verde-oscuro);
            color: white;
        }

        body.dark-theme .page-item.active .page-link {
            background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
            color: var(--pantone-vino-oscuro);
        }

        body.dark-theme .text-muted {
            color: var(--pantone-gris) !important;
        }
        
        body.dark-theme .text-secondary {
            color: var(--pantone-gris-oscuro) !important;
        }

        /* Tarjetas de estadísticas en tema oscuro */
        body.dark-theme .card.bg-warning {
            background: linear-gradient(135deg, #3d2e1f 0%, #2a2115 100%) !important;
            border: 1px solid var(--pantone-dorado-oscuro);
        }
        body.dark-theme .card.bg-warning .text-dark {
            color: var(--pantone-dorado) !important;
        }
        body.dark-theme .card.bg-info {
            background: linear-gradient(135deg, #1a3a4a 0%, #0f2530 100%) !important;
            border: 1px solid var(--pantone-verde-oscuro);
        }
        body.dark-theme .card.bg-danger {
            background: linear-gradient(135deg, #3d1f1f 0%, #2a1515 100%) !important;
            border: 1px solid #b71c1c;
        }
        body.dark-theme .card.bg-primary {
            background: linear-gradient(135deg, #1a2a4a 0%, #0f1a30 100%) !important;
            border: 1px solid #1565c0;
        }
        body.dark-theme .card.bg-success {
            background: linear-gradient(135deg, #1a3a28 0%, #0f2518 100%) !important;
            border: 1px solid var(--pantone-verde-oscuro);
        }
        body.dark-theme .card .text-white {
            color: var(--pantone-dorado) !important;
        }
        
        /* Tarjetas de Notas Malas con gradientes institucionales - Tema Oscuro */
        body.dark-theme .card[style*="linear-gradient(135deg, #E8D5D9"] {
            background: linear-gradient(135deg, #2d1f22 0%, #1a1215 100%) !important;
            border-left: 4px solid #9F2241 !important;
        }
        body.dark-theme .card[style*="linear-gradient(135deg, #E8D5D9"] h2,
        body.dark-theme .card[style*="linear-gradient(135deg, #E8D5D9"] small,
        body.dark-theme .card[style*="linear-gradient(135deg, #E8D5D9"] i {
            color: #E8D5D9 !important;
        }
        
        body.dark-theme .card[style*="linear-gradient(135deg, #D5E5DE"] {
            background: linear-gradient(135deg, #1a2e22 0%, #0f1a15 100%) !important;
            border-left: 4px solid #235B4E !important;
        }
        body.dark-theme .card[style*="linear-gradient(135deg, #D5E5DE"] h2,
        body.dark-theme .card[style*="linear-gradient(135deg, #D5E5DE"] small,
        body.dark-theme .card[style*="linear-gradient(135deg, #D5E5DE"] i {
            color: #D5E5DE !important;
        }
        
        body.dark-theme .card[style*="linear-gradient(135deg, #E8D5D5"] {
            background: linear-gradient(135deg, #2e1f1f 0%, #1a1212 100%) !important;
            border-left: 4px solid #C62828 !important;
        }
        body.dark-theme .card[style*="linear-gradient(135deg, #E8D5D5"] h2,
        body.dark-theme .card[style*="linear-gradient(135deg, #E8D5D5"] small,
        body.dark-theme .card[style*="linear-gradient(135deg, #E8D5D5"] i {
            color: #E8D5D5 !important;
        }
        
        body.dark-theme .card[style*="linear-gradient(135deg, #EDE4D3"] {
            background: linear-gradient(135deg, #2e2820 0%, #1a1812 100%) !important;
            border-left: 4px solid #BC955C !important;
        }
        body.dark-theme .card[style*="linear-gradient(135deg, #EDE4D3"] h2,
        body.dark-theme .card[style*="linear-gradient(135deg, #EDE4D3"] small,
        body.dark-theme .card[style*="linear-gradient(135deg, #EDE4D3"] i {
            color: #EDE4D3 !important;
        }
        
        /* Tarjetas de Usuarios - Tema Oscuro */
        body.dark-theme .card[style*="linear-gradient(135deg, #D5E8E3"] {
            background: linear-gradient(135deg, #1a2e28 0%, #0f1a15 100%) !important;
            border-left: 4px solid #235B4E !important;
        }
        body.dark-theme .card[style*="linear-gradient(135deg, #D5E8E3"] h4,
        body.dark-theme .card[style*="linear-gradient(135deg, #D5E8E3"] small,
        body.dark-theme .card[style*="linear-gradient(135deg, #D5E8E3"] i {
            color: #D5E8E3 !important;
        }
        
        body.dark-theme .card[style*="linear-gradient(135deg, #EAEAEA"] {
            background: linear-gradient(135deg, #2e2e2e 0%, #1a1a1a 100%) !important;
            border-left: 4px solid #6F7271 !important;
        }
        body.dark-theme .card[style*="linear-gradient(135deg, #EAEAEA"] h4,
        body.dark-theme .card[style*="linear-gradient(135deg, #EAEAEA"] small,
        body.dark-theme .card[style*="linear-gradient(135deg, #EAEAEA"] i {
            color: #EAEAEA !important;
        }

        /* ===================== RESPONSIVE ===================== */
        
        /* Mobile styles - Sidebar visible by default on desktop */
        .sidebar {
            transform: translateX(0);
        }
        
        @media (max-width: 768px) {
            .main-content { 
                padding: 15px; 
                margin-left: 0 !important;
                margin-top: 60px;
                width: 100% !important;
            }
            .sidebar { 
                position: fixed;
                top: 0;
                left: 0;
                transform: translateX(-100%);
                width: 280px;
                max-width: 85vw;
                height: 100vh;
                z-index: 1100;
            }
            .sidebar.show { 
                transform: translateX(0);
            }
            .sidebar.collapsed { 
                transform: translateX(-100%);
            }
            .main-content.expanded { 
                margin-left: 0 !important;
            }
            .theme-toggle { right: 70px; top: 12px; }
            .card { margin-bottom: 15px; }
            .modal-dialog { margin: 10px; max-width: calc(100% - 20px); }
            .top-bar {
                padding: 0 10px;
                height: 55px;
            }
            .top-bar-left {
                gap: 6px;
            }
            .top-bar-right {
                gap: 6px;
            }
            .mobile-menu-btn, .top-bar-collapse {
                display: flex;
            }
            .top-bar-brand {
                font-size: 13px;
                max-width: 150px;
            }
            .top-user-info {
                padding: 4px 8px 4px 4px;
            }
            /* Sidebar nav items */
            .sidebar .nav-link {
                padding: 12px 16px;
                font-size: 14px;
            }
            .sidebar .nav-link i {
                width: 24px;
                text-align: center;
            }
        }

        /* Tablet styles (768px - 1024px) */
        @media (min-width: 768px) and (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }
            .sidebar.collapsed {
                transform: translateX(-220px);
            }
            .main-content {
                margin-left: 220px;
            }
            .main-content.expanded {
                margin-left: 0;
            }
            .sidebar .nav-link {
                padding: 12px 15px;
                font-size: 14px;
            }
            .top-bar-brand {
                font-size: 15px;
            }
            .card-body {
                padding: 1rem;
            }
            .table {
                font-size: 14px;
            }
        }

        @media (max-width: 576px) {
            .top-bar {
                padding: 0 4px !important;
                height: 50px !important;
                min-height: 50px !important;
            }
            div.top-bar, div.top-bar-left, div.top-bar-right {
                display: flex;
                align-items: center;
            }
            .top-bar-left {
                gap: 2px !important;
            }
            .top-bar-right {
                gap: 2px !important;
                padding-left: 2px !important;
            }
            .mobile-menu-btn, .top-bar-collapse, .top-bar-btn {
                width: 26px !important;
                height: 26px !important;
                padding: 0 !important;
                min-width: 26px !important;
                flex-shrink: 0;
            }
            .top-bar-brand {
                font-size: 10px !important;
                max-width: 70px !important;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .top-bar-brand i {
                display: none;
            }
            .top-user-info {
                padding: 2px 4px 2px 2px;
                gap: 4px;
                border-radius: 20px;
                min-width: auto;
            }
            .top-user-avatar {
                width: 22px;
                height: 22px;
                font-size: 8px;
                flex-shrink: 0;
            }
            .top-user-details {
                display: none !important;
            }
            .theme-toggle-btn, .top-bar-logout {
                width: 26px !important;
                height: 26px !important;
                min-width: 26px !important;
            }
            .theme-toggle-btn i, .top-bar-logout i {
                font-size: 10px !important;
            }
            .main-content {
                padding: 8px;
                margin-top: 50px;
            }
            .card {
                border-radius: 8px;
                margin-bottom: 8px;
            }
            .card-header {
                padding: 8px 10px;
                font-size: 13px;
            }
            .card-body {
                padding: 12px;
            }
            .btn {
                padding: 8px 12px;
                font-size: 13px;
            }
            .form-control {
                font-size: 14px;
                padding: 8px 10px;
            }
            .table {
                font-size: 12px;
            }
            .table th, .table td {
                padding: 8px 4px;
            }
            .modal-content {
                border-radius: 10px;
            }
            .notification {
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    </style>
    <?php 
    require_once 'controllers/AuthController.php';
    require_once 'helpers/permisos_helper.php';
    require_once 'models/Usuario.php';
    ?>
</head>
<body>
    <?php if (AuthController::isLoggedIn()): ?>
    
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn" title="Menú">
                <i class="fas fa-bars"></i>
            </button>
            <button class="top-bar-btn top-bar-collapse" id="sidebarCollapse" title="Colapsar Menú">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="top-bar-btn top-bar-collapse" id="sidebarExpand" title="Mostrar Menú" style="display: none;">
                <i class="fas fa-chevron-right"></i>
            </button>
            <span class="top-bar-brand"><i class="fas fa-fingerprint me-2"></i><?php echo APP_NAME; ?></span>
        </div>
        <div class="top-bar-right">
            <?php 
            // Obtener información extendida del usuario
            $userDisplayName = $_SESSION['username'] ?? 'Usuario';
            $userRoleDisplay = '';
            $userArea = '';
            $userFullName = '';
            
            $rol = strtolower($_SESSION['rol'] ?? '');
            
            // Obtener nombre completo del usuario
            if (!class_exists('Database')) {
                require_once __DIR__ . '/models/Database.php';
            }
            
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            if (isset($_SESSION['user_id'])) {
                $stmtUser = $pdo->prepare("
                    SELECT u.nombre_completo, u.empleado_id, u.rol,
                           e.area, e.clave_depto
                    FROM usuarios u
                    LEFT JOIN empleados e ON u.empleado_id = e.id
                    WHERE u.id = ?
                ");
                $stmtUser->execute([$_SESSION['user_id']]);
                $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                
                if ($userData) {
                    // Usar nombre completo si existe, sino username
                    $userDisplayName = !empty($userData['nombre_completo']) 
                        ? $userData['nombre_completo'] 
                        : ($_SESSION['username'] ?? 'Usuario');
                    
                    // Si tiene área asignada, mostrarla
                    if (!empty($userData['area'])) {
                        $userArea = $userData['area'];
                        // Truncar área si es muy larga
                        if (strlen($userArea) > 25) {
                            $userArea = substr($userArea, 0, 22) . '...';
                        }
                    }
                }
            }
            
            // Etiqueta del rol
            switch ($rol) {
                case 'superadmin':
                    $userRoleDisplay = 'Superadministrador';
                    break;
                case 'admin':
                    $userRoleDisplay = 'Administrador';
                    break;
                case 'mando':
                    $userRoleDisplay = 'Mando';
                    break;
                case 'jefe':
                    $userRoleDisplay = 'Jefe de Área';
                    break;
                default:
                    $userRoleDisplay = ucfirst($rol ?: 'Usuario');
            }
            
            // Obtener iniciales para el avatar
            $iniciales = 'U';
            if (!empty($userDisplayName)) {
                $partes = explode(' ', $userDisplayName);
                if (count($partes) >= 2) {
                    $iniciales = strtoupper(substr($partes[0], 0, 1) . substr(end($partes), 0, 1));
                } else {
                    $iniciales = strtoupper(substr($userDisplayName, 0, 2));
                }
            }
            ?>
            <div class="dropdown">
                <div class="top-user-info" data-bs-toggle="dropdown" data-bs-auto-close="outside" style="cursor: pointer;">
                    <div class="top-user-avatar">
                        <?php echo $iniciales; ?>
                    </div>
                    <div class="top-user-details">
                        <span class="top-user-name" title="<?php echo htmlspecialchars($userDisplayName); ?>">
                            <?php echo htmlspecialchars($userDisplayName); ?>
                        </span>
                        <span class="top-user-role">
                            <?php echo $userRoleDisplay; ?>
                        </span>
                        <?php if (!empty($userArea)): ?>
                        <span class="top-user-area">
                            <span class="badge">
                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($userArea); ?>
                            </span>
                        </span>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-chevron-down text-white ms-2" style="font-size: 0.7rem;"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end dropdown-perfil" style="min-width: 280px; margin-top: 10px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: none; padding: 8px;">
                    <li class="px-3 py-2 border-bottom mb-2">
                        <div class="d-flex align-items-center">
                            <div class="top-user-avatar me-3" style="width: 45px; height: 45px; font-size: 1.1rem;">
                                <?php echo $iniciales; ?>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size: 0.95rem; color: var(--pantone-vino);"><?php echo htmlspecialchars($userDisplayName); ?></div>
                                <small class="text-muted"><?php echo $userRoleDisplay; ?></small>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center py-2 rounded" href="javascript:void(0)" id="btnMiPerfil" onclick="abrirMiPerfil()" style="transition: all 0.2s;">
                            <i class="fas fa-user-circle text-primary me-3" style="width: 20px;"></i>
                            <span class="fw-medium">Mi Perfil</span>
                            <i class="fas fa-chevron-right ms-auto text-muted" style="font-size: 0.7rem;"></i>
                        </a>
                    </li>
                    <li class="border-top mt-2 pt-2">
                    <li class="border-top mt-2 pt-2">
                        <a class="dropdown-item d-flex align-items-center py-2 rounded text-danger" href="<?php echo rtrim(BASE_URL, '/'); ?>/logout" style="transition: all 0.2s;">
                            <i class="fas fa-sign-out-alt me-3" style="width: 20px;"></i>
                            <span class="fw-medium">Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </div>
            <button class="top-bar-btn theme-toggle-btn" id="themeToggleTop" title="Cambiar Tema">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-sticky">
            <div class="p-3">
                <h5 class="text-white mb-0 text-center"><?php echo APP_NAME; ?></h5>
            </div>
            <?php
                $rol = $_SESSION['rol'] ?? 'invitado';
                
                if (!empty($_SESSION['permisos'])) {
                    $todosPermisos = $_SESSION['permisos'];
                } else {
                    require_once 'models/Usuario.php';
                    $usuarioModel = new Usuario();
                    $permisosUsuario = [];
                    if (isset($_SESSION['user_id'])) {
                        $permisosUsuario = $usuarioModel->getPermisos($_SESSION['user_id']);
                    }
                    $permisosBase = Usuario::getPermisosPorRol($rol);
                    $todosPermisos = array_merge($permisosBase, $permisosUsuario);
                }
                
                $menuItems = [
                    ['path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'permiso' => 'dashboard'],
                    ['path' => '/', 'label' => 'Inicio', 'icon' => 'fa-home', 'permiso' => 'inicio'],
                    ['path' => '/empleados', 'label' => 'Empleados', 'icon' => 'fa-users', 'permiso' => 'empleados'],
                    ['path' => '/asistencia', 'label' => 'Asistencia', 'icon' => 'fa-clock', 'permiso' => 'asistencia'],
                    ['path' => '/marcaciones', 'label' => 'Marcaciones', 'icon' => 'fa-stopwatch', 'permiso' => 'marcaciones'],
                    ['path' => '/mis-validaciones', 'label' => 'Mis Validaciones', 'icon' => 'fa-check-double', 'permiso' => 'mis_validaciones'],
                    ['path' => '/horarios', 'label' => 'Horarios', 'icon' => 'fa-calendar-alt', 'permiso' => 'horarios'],
                    ['path' => '/ciclos', 'label' => 'Ciclos', 'icon' => 'fa-sync', 'permiso' => 'ciclos'],
                    ['path' => '/validaciones', 'label' => 'Validaciones', 'icon' => 'fa-user-check', 'permiso' => 'validaciones'],
                   ['path' => '/reportes/excel', 'label' => 'Reportes Excel', 'icon' => 'fa-file-excel', 'permiso' => 'reportes_excel'],
                    ['path' => '/resumen-justificaciones', 'label' => 'Resumen Justificaciones', 'icon' => 'fa-clipboard-check', 'permiso' => 'resumen_justificaciones'],
                    ['path' => '/analisis-predictivo', 'label' => 'Análisis Predictivo', 'icon' => 'fa-brain', 'permiso' => 'analisis_predictivo'],
                    ['path' => '/agent-ia', 'label' => 'Agent IA', 'icon' => 'fa-network-wired', 'permiso' => 'analisis_predictivo'],
                    ['path' => '/ai', 'label' => 'AI Dashboard', 'icon' => 'fa-robot', 'permiso' => 'analisis_predictivo'],
                    ['path' => '/biometricos', 'label' => 'Dispositivos Biométricos', 'icon' => 'fa-fingerprint', 'permiso' => 'biometricos'],
        ['path' => '/biometricos/gestionar', 'label' => 'Gestionar Biométrico', 'icon' => 'fa-sliders-h', 'permiso' => 'biometricos_gestionar'],
                    ['path' => '/database', 'label' => 'Base de Datos', 'icon' => 'fa-database', 'permiso' => 'database'],
                    ['path' => '/catalogos', 'label' => 'Catálogos', 'icon' => 'fa-sitemap', 'permiso' => 'catalogos'],
                    ['path' => '/permisos-menu', 'label' => 'Permisos de Menú', 'icon' => 'fa-shield-halved', 'permiso' => 'configurar_menu'],
                    ['path' => '/configuracion', 'label' => 'Configuración', 'icon' => 'fa-gear', 'permiso' => 'configuracion'],
                    ['path' => '/notas-malas', 'label' => 'Notas Malas', 'icon' => 'fa-exclamation-triangle', 'permiso' => 'usuarios'],
                    ['path' => '/usuarios', 'label' => 'Usuarios', 'icon' => 'fa-user-cog', 'permiso' => 'usuarios'],
                    ['path' => '/logs', 'label' => 'Logs de Errores', 'icon' => 'fa-file-alt', 'permiso' => 'admin'],
                ];
                
                $menuConfig = [];
                if (isset($_SESSION['user_id'])) {
                    $pdo = $db->getConnection();
                    $stmt = $pdo->prepare("SELECT menu_path, visible FROM menu_config WHERE usuario_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $menuConfig[$row['menu_path']] = (bool)$row['visible'];
                    }
                }
                
                $menuFiltrado = array_filter($menuItems, function($item) use ($todosPermisos, $rol, $menuConfig) {
                    if ($rol === 'superadmin') return true;
                    
                    $tienePermisoRol = in_array($item['permiso'], $todosPermisos);
                    $configurado = isset($menuConfig[$item['path']]);
                    $configVisible = $configurado ? $menuConfig[$item['path']] : true;
                    
                    if ($configurado) {
                        return $configVisible;
                    }
                    
                    return $tienePermisoRol && $configVisible;
                });

                $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
                $currentPath = parse_url($currentUri, PHP_URL_PATH) ?: '/';
                $basePath = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '/', '/');
                if ($basePath === '') {
                    $basePath = '/';
                }

                if ($basePath !== '/' && strpos($currentPath, $basePath) === 0) {
                    $routePath = substr($currentPath, strlen($basePath));
                    $routePath = $routePath === '' ? '/' : $routePath;
                } else {
                    $routePath = $currentPath;
                }
                ?>
                <ul class="nav flex-column">
                    <?php foreach ($menuFiltrado as $item): 
                        $itemPath = $item['path'];
                        if ($itemPath === '/') {
                            $isActive = ($routePath === '/' || $routePath === '');
                        } else {
                            $isActive = ($routePath === $itemPath) || (strpos($routePath, $itemPath . '/') === 0);
                        }
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?php echo rtrim(BASE_URL, '/') . $item['path']; ?>">
                            <i class="fas <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
                            <?php if($item['label'] == 'Validaciones'): ?>
                            <span class="badge bg-danger ms-1" id="menuPendientesCount" style="display: none; min-width: 20px; font-size: 0.75rem; padding: 2px 6px;">0</span>
                            <?php endif; ?>
                            <?php if($item['label'] == 'Mis Validaciones'): ?>
                            <span class="badge bg-danger ms-1" id="misValidacionesPendientesCount" style="display: none; min-width: 20px; font-size: 0.75rem; padding: 2px 6px;">0</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Token CSRF global para JavaScript -->
    <meta name="csrf-token" content="<?php 
        try {
            echo htmlspecialchars(Csrf::getToken() ?? Csrf::generateToken());
        } catch (Exception $e) {
            echo 'csrf-token';
        }
    ?>">
    <!-- Token CSRF alternativo para JavaScript -->
    <input type="hidden" id="csrf_token" value="<?php 
        try {
            echo htmlspecialchars(Csrf::getToken() ?? Csrf::generateToken());
        } catch (Exception $e) {
            echo 'csrf-token';
        }
    ?>">
    
    <!-- Configuración global para JavaScript -->
    <script<?= SecurityHelper::nonceAttr() ?>>
        window.BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
        window.empleadosConfig = {
            baseUrl: '<?php echo rtrim(BASE_URL, '/'); ?>',
            currentPage: 1,
            currentLimit: 20,
            currentSearch: '',
            currentArea: ''
        };
    </script>
    
    <!-- Main content -->
    <main class="main-content<?php echo AuthController::isLoggedIn() ? '' : ' expanded'; ?>" id="mainContent">
        <?php 
        if (isset($content)) {
            echo $content;
        } else {
            // Para la página de configuración, incluir el contenido específico
            if (strpos($_SERVER['REQUEST_URI'], 'configuracion') !== false) {
                include __DIR__ . '/configuracion/configuracion_content.php';
            }
        }
        ?>
    </main>
    <!-- Modal de Perfil -->
    <?php
    if (AuthController::isLoggedIn()):
        require_once __DIR__ . '/../controllers/PerfilController.php';
        require_once __DIR__ . '/../models/Usuario.php';
        require_once __DIR__ . '/../models/Empleado.php';
        
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $usuarioModel = new Usuario();
            $empleadoModel = new Empleado();
            $usuario = $usuarioModel->getById($userId);
            $empleado = null;
            if (!empty($usuario['empleado_id'])) {
                $empleado = $empleadoModel->getById($usuario['empleado_id']);
            }
            
            $perfilController = new PerfilController();
            $modalHtml = $perfilController->renderPerfilModal($usuario, $empleado);
            echo $modalHtml;
        }
    endif;
    ?>
    
    <script<?= SecurityHelper::nonceAttr() ?>>
    function abrirMiPerfil() {
        console.log('Ejecutando abrirMiPerfil');
        var btnPerfil = document.querySelector('#btnMiPerfil');
        if (btnPerfil) {
            var dropdown = btnPerfil.closest('.dropdown');
            if (dropdown) {
                dropdown.classList.remove('show');
                var toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle) {
                    toggle.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                }
                var menu = dropdown.querySelector('.dropdown-menu');
                if (menu) menu.classList.remove('show');
            }
        }
        
        var modal = document.getElementById('perfilModal');
        console.log('perfilModal:', modal);
        if (modal) {
            modal.style.display = 'flex';
            console.log('Modal mostrado');
        } else {
            console.log('Modal NO encontrado');
        }
    }
    </script>
    
    <script<?= SecurityHelper::nonceAttr() ?>>
        document.addEventListener('DOMContentLoaded', function() {
            function saveThemePreference(theme) {
                localStorage.setItem('theme', theme);
            }

function loadThemePreference() {
                // Por defecto siempre tema claro
                const saved = localStorage.getItem('theme');
                // Solo usar tema oscuro si está explícitamente guardado
                return saved === 'dark' ? 'dark' : 'light';
            }

            function applyTheme(theme) {
                if (theme === 'dark') {
                    $('body').addClass('dark-theme');
                    $('#themeToggle i').removeClass('fa-moon').addClass('fa-sun');
                    $('#themeToggleTop i').removeClass('fa-moon').addClass('fa-sun');
                } else {
                    $('body').removeClass('dark-theme');
                    $('#themeToggle i').removeClass('fa-sun').addClass('fa-moon');
                    $('#themeToggleTop i').removeClass('fa-sun').addClass('fa-moon');
                }
            }

            const savedTheme = loadThemePreference();
            applyTheme(savedTheme);

            $('#themeToggle').click(function() {
                const currentTheme = $('body').hasClass('dark-theme') ? 'dark' : 'light';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                applyTheme(newTheme);
                saveThemePreference(newTheme);
            });

            $('#themeToggleTop').click(function() {
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
            const mobileMenuBtn = $('#mobileMenuBtn');

            const sidebarToggleBar = $('#sidebarToggleBar');
            
            // Mobile menu toggle functionality
            mobileMenuBtn.click(function() {
                sidebar.toggleClass('show');
                sidebarOverlay.toggleClass('show');
            });

            sidebarToggle.click(function() {
                sidebar.toggleClass('show');
            });

            sidebarToggleBar.click(function() {
                sidebar.toggleClass('show');
                sidebarOverlay.toggleClass('show');
            });

            sidebarOverlay.click(function() {
                sidebar.removeClass('show');
                sidebarOverlay.removeClass('show');
            });

            sidebarCollapse.click(function() {
                sidebar.addClass('collapsed');
                mainContent.addClass('expanded');
                $('.site-footer').addClass('expanded');
                sidebarCollapse.hide();
                sidebarExpand.show();
            });

            sidebarExpand.click(function() {
                sidebar.removeClass('collapsed');
                mainContent.removeClass('expanded');
                $('.site-footer').removeClass('expanded');
                sidebarExpand.hide();
                sidebarCollapse.show();
            });

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

            $('.nav-link').click(function() {
                if ($(window).width() < 768) {
                    sidebar.removeClass('show');
                    sidebarOverlay.removeClass('show');
                }
            });
        });
        
        // Script para actualización del contador de validaciones pendientes
        if (document.getElementById('menuPendientesCount')) {
            const script = document.createElement('script');
            script.src = '/views/validaciones/contador_menu.js?v=' + new Date().getTime();
            script.async = true;
            script.onerror = function() {
                console.warn('No se pudo cargar el script del contador de validaciones');
            };
            document.head.appendChild(script);
        }
    </script>
    
    <!-- Footer con Derechos Reservados -->
    <footer class="site-footer<?php echo AuthController::isLoggedIn() ? '' : ' expanded'; ?>" style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); color: #ffffff; padding: 15px 20px; border-top: 3px solid #9F2241;">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <h6 style="font-weight: 700; color: #BC955C; margin-bottom: 3px; font-size: 0.9rem;">
                        <i class="fas fa-fingerprint me-2"></i>Sistema Biométrico de Control de Asistencia
                    </h6>
                    <small style="color: #888888;">&copy; <?php echo date('Y'); ?> - Todos los derechos reservados</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small style="color: #cccccc;">
                        <strong>Edgar Phye Parga</strong> - Ingeniero en Desarrollo de Software
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/error-logger.js"></script>
</body>
</html>
