<?php
require_once __DIR__ . '/../../helpers/Csrf.php';
require_once __DIR__ . '/../../helpers/permisos_helper.php';
require_once __DIR__ . '/../../models/Usuario.php';
$csrfToken = Csrf::generateToken(); 
$_SESSION['csrf_token'] = $csrfToken;

ob_start();
?>

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
    
    .val-container {
        background-color: #f8f7f5;
        min-height: 100vh;
        padding: 20px;
    }
    
    /* Encabezado principal */
    .val-header {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        border-radius: 16px;
        border: none;
    }
    
    .val-header .card-body {
        padding: 20px;
    }
    
    .val-header h4 {
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .val-header h4 i {
        background: linear-gradient(135deg, var(--pantone-accent) 0%, var(--pantone-accent-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    /* Cards */
    .val-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .val-card-header-primary {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
        padding: 15px 20px;
        border: none;
    }
    
    .val-card-header-secondary {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
        padding: 15px 20px;
        border: none;
    }
    
    .val-card-header-accent {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
        color: white;
        padding: 15px 20px;
        border: none;
    }
    
    .val-card-header-light {
        background: linear-gradient(135deg, #f8f7f5 0%, #e8e6e1 100%);
        color: var(--pantone-primary);
        padding: 15px 20px;
        border-bottom: 2px solid var(--pantone-accent);
    }
    
    .btn-val-danger {
        background: linear-gradient(135deg, var(--pantone-danger) 0%, #501526 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-val-danger:hover {
        background: linear-gradient(135deg, #501526 0%, #3a0f1a 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(105, 28, 50, 0.4);
    }
    
    .btn-val-warning {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-val-warning:hover {
        background: linear-gradient(135deg, #8B6914 0%, #6a5210 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(188, 149, 92, 0.4);
    }
    
    /* Botones outline para modal */
    .btn-val-outline-success {
        color: var(--pantone-secondary);
        border: 2px solid var(--pantone-secondary);
        border-radius: 25px;
        padding: 8px 18px;
        font-weight: 500;
        background: transparent;
        transition: all 0.3s ease;
    }
    
    .btn-val-outline-success:hover {
        background: var(--pantone-secondary);
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-val-outline-danger {
        color: var(--pantone-danger);
        border: 2px solid var(--pantone-danger);
        border-radius: 25px;
        padding: 8px 18px;
        font-weight: 500;
        background: transparent;
        transition: all 0.3s ease;
    }
    
    .btn-val-outline-danger:hover {
        background: var(--pantone-danger);
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-val-outline-warning {
        color: var(--pantone-accent-dark);
        border: 2px solid var(--pantone-accent-dark);
        border-radius: 25px;
        padding: 8px 18px;
        font-weight: 500;
        background: transparent;
        transition: all 0.3s ease;
    }
    
    .btn-val-outline-warning:hover {
        background: var(--pantone-accent-dark);
        color: white;
        transform: translateY(-2px);
    }
    
    /* Botones primarios */
    .btn-val-primary {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-val-primary:hover {
        background: linear-gradient(135deg, var(--pantone-primary-dark) 0%, #501526 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(151, 34, 65, 0.4);
    }
    
    .btn-val-secondary {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-val-secondary:hover {
        background: linear-gradient(135deg, var(--pantone-secondary-dark) 0%, #0a211d 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(35, 91, 78, 0.4);
    }
    
    .btn-val-outline {
        background: transparent;
        color: var(--pantone-primary);
        border: 2px solid var(--pantone-primary);
        border-radius: 25px;
        padding: 8px 18px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-val-outline:hover {
        background: var(--pantone-primary);
        color: white;
        transform: translateY(-2px);
    }
    
    /* Estadísticas */
    .val-stat-card {
        border-radius: 16px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .val-stat-card:hover {
        transform: translateY(-3px);
    }
    
    .val-stat-card .stat-number {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .val-stat-pending {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
    }
    
    .val-stat-approved {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
    }
    
    .val-stat-rejected {
        background: linear-gradient(135deg, var(--pantone-danger) 0%, #501526 100%);
        color: white;
    }
    
    /* Badges */
    .badge-val {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    
    .badge-val-pending {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
    }
    
    .badge-val-approved {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
    }
    
    .badge-val-rejected {
        background: linear-gradient(135deg, var(--pantone-danger) 0%, #501526 100%);
        color: white;
    }
    
    .badge-val-info {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
        color: white;
    }
    
    /* Badge para tabla - mejor visibilidad */
    .badge-estado {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-estado-pendiente {
        background-color: rgba(159, 34, 65, 0.15);
        color: var(--pantone-primary);
        border: 1px solid var(--pantone-primary);
    }
    
    .badge-estado-aprobado {
        background-color: rgba(35, 91, 78, 0.15);
        color: var(--pantone-secondary);
        border: 1px solid var(--pantone-secondary);
    }
    
    .badge-estado-rechazado {
        background-color: rgba(105, 28, 50, 0.15);
        color: var(--pantone-danger);
        border: 1px solid var(--pantone-danger);
    }
    
    .badge-estado-info {
        background-color: rgba(188, 149, 92, 0.2);
        color: var(--pantone-accent-dark);
        border: 1px solid var(--pantone-accent-dark);
    }
    
    /* Tabs */
    .val-tabs {
        background: linear-gradient(135deg, #f8f7f5 0%, #f0ede8 100%);
        border-bottom: 3px solid var(--pantone-primary);
        padding: 8px 8px 0 8px;
        border-radius: 10px 10px 0 0;
    }
    
    .val-tabs .nav-link {
        font-size: 0.85rem;
        font-weight: 600;
        padding: 10px 16px;
        border: none;
        border-radius: 10px 10px 0 0;
        color: var(--pantone-gray-dark);
        background: transparent;
        transition: all 0.3s ease;
    }
    
    .val-tabs .nav-link:hover {
        color: var(--pantone-primary);
        background: rgba(151, 34, 65, 0.08);
    }
    
    .val-tabs .nav-link.active {
        color: white;
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        box-shadow: 0 -2px 10px rgba(151, 34, 65, 0.2);
    }
    
    /* Pestañas personalizadas para validaciones */
    .val-tabs-custom {
        background: linear-gradient(135deg, #f8f7f5 0%, #f0ede8 100%);
        border-bottom: 3px solid var(--pantone-primary);
        padding: 0;
        display: flex;
        gap: 5px;
    }
    
    .val-tabs-custom .nav-link {
        font-size: 0.9rem;
        font-weight: 600;
        padding: 12px 20px;
        border: none;
        border-radius: 12px 12px 0 0;
        color: var(--pantone-gray-dark);
        background: transparent;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }
    
    .val-tabs-custom .nav-link:hover {
        color: var(--pantone-primary);
        background: rgba(151, 34, 65, 0.08);
    }
    
    .val-tabs-custom .nav-link.active {
        color: white;
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        box-shadow: 0 -2px 10px rgba(151, 34, 65, 0.2);
    }
    
    .val-tabs-custom .nav-link .badge {
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 10px;
    }
    
    /* Botones de Filtro Elegantes - Colores Pantone */
    .val-filtro-group {
        display: flex;
        gap: 0;
        border-radius: 25px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .val-btn-filtro {
        padding: 8px 16px;
        font-size: 0.8rem;
        font-weight: 600;
        border: none;
        border-radius: 0;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        position: relative;
        overflow: hidden;
    }
    
    .val-btn-filtro::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .val-btn-filtro:hover::before {
        opacity: 1;
    }
    
    .val-btn-filtro:active {
        transform: scale(0.98);
    }
    
    /* Botón Todos - Gris elegante */
    .val-btn-todos {
        background: linear-gradient(135deg, #6F7271 0%, #4a4b4c 100%);
        color: white;
    }
    .val-btn-todos::before {
        background: linear-gradient(135deg, #98989A 0%, #6F7271 100%);
    }
    .val-btn-todos.active {
        background: linear-gradient(135deg, #98989A 0%, #4a4b4c 100%);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.2), 0 0 15px rgba(152, 152, 154, 0.4);
    }
    .val-btn-todos i { color: #E0E0E0; }
    
    /* Botón Retardos - Dorado Pantone */
    .val-btn-retardos {
        background: linear-gradient(135deg, #DDC9A3 0%, #BC955C 100%);
        color: var(--pantone-vino-oscuro);
    }
    .val-btn-retardos::before {
        background: linear-gradient(135deg, #E8DCC4 0%, #DDC9A3 100%);
    }
    .val-btn-retardos.active {
        background: linear-gradient(135deg, #BC955C 0%, #9a7440 100%);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.2), 0 0 15px rgba(188, 149, 92, 0.5);
        color: white;
    }
    .val-btn-retardos i { color: var(--pantone-vino-oscuro); }
    .val-btn-retardos.active i { color: white; }
    
    /* Botón Incidencias - Vino Pantone */
    .val-btn-incidencias {
        background: linear-gradient(135deg, #9F2241 0%, #7a1a32 100%);
        color: white;
    }
    .val-btn-incidencias::before {
        background: linear-gradient(135deg, #B82C50 0%, #9F2241 100%);
    }
    .val-btn-incidencias.active {
        background: linear-gradient(135deg, #C62828 0%, #9F2241 100%);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.2), 0 0 15px rgba(159, 34, 65, 0.5);
    }
    .val-btn-incidencias i { color: #FFCDD2; }
    
    /* Botón Por Justificar - Verde Pantone */
    .val-btn-pendientes {
        background: linear-gradient(135deg, #235B4E 0%, #1a4238 100%);
        color: white;
    }
    .val-btn-pendientes::before {
        background: linear-gradient(135deg, #2E7D5E 0%, #235B4E 100%);
    }
    .val-btn-pendientes.active {
        background: linear-gradient(135deg, #2E7D5E 0%, #1a4238 100%);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.2), 0 0 15px rgba(35, 91, 78, 0.5);
    }
    .val-btn-pendientes i { color: #A5D6A7; }
    
    /* Botón Al Día - Verde Claro */
    .val-btn-sin-pendientes {
        background: linear-gradient(135deg, #2E7D5E 0%, #1B5E3A 100%);
        color: white;
    }
    .val-btn-sin-pendientes::before {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D5E 100%);
    }
    .val-btn-sin-pendientes.active {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D5E 100%);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.2), 0 0 15px rgba(76, 175, 80, 0.5);
    }
    .val-btn-sin-pendientes i { color: #C8E6C9; }
    
    /* Primer y último botón con border-radius */
    .val-btn-filtro:first-child {
        border-radius: 25px 0 0 25px;
    }
    .val-btn-filtro:last-child {
        border-radius: 0 25px 25px 0;
    }
    .val-btn-filtro:only-child {
        border-radius: 25px;
    }
    
    /* Separadores entre botones */
    .val-btn-filtro + .val-btn-filtro {
        border-left: 1px solid rgba(255,255,255,0.2);
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .val-filtro-group {
            margin-bottom: 15px;
        }
    }
    
    @media (max-width: 768px) {
        .val-filtro-group {
            flex-wrap: wrap;
            border-radius: 15px;
        }
        .val-btn-filtro {
            padding: 6px 12px;
            font-size: 0.75rem;
        }
        .val-btn-filtro:first-child {
            border-radius: 15px 0 0 0;
        }
        .val-btn-filtro:nth-child(2) {
            border-radius: 0 15px 0 0;
        }
        .val-btn-filtro:nth-child(3) {
            border-radius: 0 0 0 15px;
        }
        .val-btn-filtro:last-child {
            border-radius: 0 0 15px 0;
        }
        .container-fluid {
            padding: 10px;
        }
        .val-section-title {
            font-size: 1.25rem;
        }
    }
    
    @media (max-width: 576px) {
        .val-btn-filtro {
            flex: 1 1 45%;
            text-align: center;
        }
        .val-btn-filtro:first-child,
        .val-btn-filtro:nth-child(2) {
            border-radius: 15px 15px 0 0;
        }
        .val-btn-filtro:nth-child(3),
        .val-btn-filtro:last-child {
            border-radius: 0 0 15px 15px;
        }
        .card {
            border-radius: 8px;
        }
    }
    
    /* Tablas */
    .val-table thead {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
    }
    
    .val-table th {
        border: none;
        padding: 14px 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .val-table td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #e8e6e1;
        font-size: 0.875rem;
    }
    
    .val-table tbody tr:hover {
        background-color: rgba(35, 91, 78, 0.05);
    }
    
    .val-table .btn-action {
        padding: 4px 8px;
        font-size: 0.75rem;
        border-radius: 15px;
    }
    
    .val-table .badge {
        font-size: 0.7rem;
        padding: 4px 8px;
    }
    
    /* Formularios */
    .val-form-label {
        color: var(--pantone-primary);
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .val-form-control, .val-form-select {
        border-radius: 12px;
        border: 2px solid #e0e0e0;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }
    
    .val-form-control:focus, .val-form-select:focus {
        border-color: var(--pantone-primary);
        box-shadow: 0 0 0 4px rgba(151, 34, 65, 0.15);
    }
    
    /* Empleados cards */
    .empleado-card {
        border-radius: 12px;
        border: 2px solid #e0e0e0;
        padding: 12px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
    }
    
    .empleado-card:hover {
        border-color: var(--pantone-primary);
        box-shadow: 0 4px 15px rgba(151, 34, 65, 0.15);
    }
    
    .empleado-card.selected {
        border-color: var(--pantone-secondary);
        background: linear-gradient(135deg, rgba(35, 91, 78, 0.1) 0%, rgba(16, 49, 43, 0.1) 100%);
    }
    
    /* Modal */
    .val-modal .modal-content {
        border-radius: 20px;
        overflow: hidden;
        border: none;
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    }
    
    .val-modal .modal-header {
        padding: 20px 25px;
        border: none;
    }
    
    .val-modal .modal-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
    }
    
    .val-modal .modal-body {
        padding: 25px;
        background: #fafafa;
    }
    
    .val-modal .modal-footer {
        padding: 15px 25px;
        background: white;
        border-top: 1px solid #eee;
    }
    
    /* ==================== TEMA OSCURO ==================== */
    body.dark-theme .val-container {
        background-color: #1a1a2e;
    }
    
    body.dark-theme .val-card {
        background-color: #252538;
        border: 1px solid #3a3a5c;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    body.dark-theme .val-card-header-light {
        background: linear-gradient(135deg, #1e1e32 0%, #1a1a2e 100%);
        border-bottom-color: var(--pantone-accent);
        color: var(--pantone-accent);
    }
    
    body.dark-theme .val-form-label {
        color: var(--pantone-accent);
    }
    
    body.dark-theme .val-form-control, 
    body.dark-theme .val-form-select {
        background-color: #1a1a2e;
        border-color: #3a3a5c;
        color: #e0e0e0;
    }
    
    body.dark-theme .val-form-control:focus, 
    body.dark-theme .val-form-select:focus {
        border-color: var(--pantone-primary);
        box-shadow: 0 0 0 4px rgba(151, 34, 65, 0.2);
    }
    
    body.dark-theme .val-table thead {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, #1a3d35 100%);
    }
    
    body.dark-theme .val-table td {
        background-color: #252538;
        color: #e0e0e0;
        border-color: #3a3a5c;
    }
    
    body.dark-theme .val-table tbody tr:hover td {
        background-color: #2d2d48;
    }
    
    body.dark-theme .empleado-card {
        background-color: #252538;
        border-color: #3a3a5c;
    }
    
    body.dark-theme .empleado-card:hover {
        border-color: var(--pantone-primary);
    }
    
    body.dark-theme .empleado-card.selected {
        border-color: var(--pantone-secondary);
        background: linear-gradient(135deg, rgba(35, 91, 78, 0.2) 0%, rgba(16, 49, 43, 0.2) 100%);
    }
    
    body.dark-theme .val-modal .modal-content {
        background-color: #252538;
    }
    
    body.dark-theme .val-modal .modal-body {
        background-color: #252538;
    }
    
    body.dark-theme .text-muted {
        color: #8a8aaa !important;
    }
    
    body.dark-theme .val-tabs {
        background: linear-gradient(180deg, #1e1e32 0%, #1a1a2e 100%);
        border-bottom-color: var(--pantone-primary);
    }
    
    body.dark-theme .val-tabs .nav-link {
        color: #8a8aaa;
    }
    
    body.dark-theme .val-tabs .nav-link:hover {
        color: var(--pantone-accent);
        background: rgba(151, 34, 65, 0.15);
    }
    
    body.dark-theme .form-check-label {
        color: #e0e0e0;
    }
    
    /* Estilos para el modal en tema oscuro - Colores Pantone */
    body.dark-theme .val-modal .modal-content {
        background-color: #252538;
        color: #e0e0e0;
        border-color: #3a3a5c;
    }
    body.dark-theme .val-modal .modal-header {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: #ffffff;
    }
    body.dark-theme .val-modal .btn-close {
        filter: invert(1);
    }
    body.dark-theme .val-modal .modal-body {
        background-color: #252538;
    }
    body.dark-theme .val-modal .modal-footer {
        background-color: #1e1e32;
        border-top-color: #3a3a5c;
    }
    body.dark-theme .val-modal .form-label {
        color: var(--pantone-accent);
        font-weight: 600;
    }
    body.dark-theme .val-modal .form-control-plaintext {
        color: #e0e0e0;
        font-weight: 500;
        background-color: #1a1a2e;
        padding: 8px 12px;
        border-radius: 4px;
    }
    body.dark-theme .val-modal .btn-outline-success {
        color: var(--pantone-secondary);
        border-color: var(--pantone-secondary);
    }
    body.dark-theme .val-modal .btn-outline-danger {
        color: var(--pantone-danger);
        border-color: var(--pantone-danger);
    }
    body.dark-theme .val-modal .btn-outline-warning {
        color: var(--pantone-accent-dark);
        border-color: var(--pantone-accent-dark);
    }
    body.dark-theme .val-modal .btn-outline-secondary {
        color: #999999;
        border-color: #666666;
    }
    body.dark-theme .val-modal .btn-val-primary {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
    }
    
    /* Ajuste para el card-body del modal */
    body.dark-theme .val-modal .card {
        background-color: #1e1e32;
        border-color: #3a3a5c;
    }
    body.dark-theme .val-modal .card-header {
        background-color: #1a1a2e !important;
        border-bottom-color: #3a3a5c;
        color: #e0e0e0 !important;
    }
    body.dark-theme .val-modal .card-body {
        background-color: #1e1e32;
    }
    body.dark-theme .val-modal .border-warning {
        border-color: var(--pantone-accent-dark) !important;
    }
    body.dark-theme .val-modal .bg-warning {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%) !important;
    }
    body.dark-theme .val-modal .text-dark {
        color: #e0e0e0 !important;
    }
</style>

<div class="container-fluid val-container">
    <!-- CSRF Token -->
    <?php 
    $csrfToken = Csrf::generateToken(); 
    $_SESSION['csrf_token'] = $csrfToken; // Guardar en sesión para validación
    ?>
    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    
    <!-- Definir BASE_URL para JavaScript -->
    <script>
        // Corregir BASE_URL para entorno web
        let baseUrl = "<?php echo rtrim(BASE_URL, '/'); ?>";
        if (baseUrl === '.' || baseUrl === '') {
            // Detectar automáticamente la ruta base
            const path = window.location.pathname;
            const segments = path.split('/');
            if (segments.length > 2) {
                baseUrl = '/' + segments[1]; // Primer segmento después del dominio
            } else {
                baseUrl = '';
            }
        }
        window.BASE_URL = baseUrl;
        console.log('BASE_URL configurado:', window.BASE_URL);
    </script>
    
    <!-- Scripts necesarios -->
    <script src="/assets/js/validaciones.js?v=2026031807"></script>
    
    <!-- Encabezado principal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card val-card val-header">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h4 class="mb-1">
                                <i class="fas fa-user-check"></i>
                                Validación de Asistencias
                            </h4>
                            <small class="text-white-50">Panel de aprobación para jefes inmediatos</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light btn-sm" id="btnExpandirTodos">
                                <i class="fas fa-expand me-1"></i> Expandir Todos
                            </button>
                            <button type="button" class="btn btn-light btn-sm" id="btnContraerTodos">
                                <i class="fas fa-compress me-1"></i> Contraer Todos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de filtros y estadísticas -->
    <div class="row mb-4 g-3">
        <!-- Filtros principales -->
        <div class="col-md-8">
            <div class="card val-card">
                <div class="card-header val-card-header-secondary">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Filtros de Búsqueda
                    </h5>
                </div>
                <div class="card-body">
                    <form id="formFiltros" class="row g-3">
                        <!-- Área/Departamento -->
                        <div class="col-md-3">
                            <label for="filtroArea" class="form-label val-form-label">Área</label>
                            <select class="form-select val-form-select" id="filtroArea" name="area">
                                <option value="">Todas las áreas</option>
                            </select>
                        </div>
                        
                        <!-- Empleado -->
                        <div class="col-md-3">
                            <label for="filtroEmpleado" class="form-label val-form-label">Empleado</label>
                            <input type="text" class="form-control val-form-control" 
                                   id="filtroEmpleado" name="empleado" 
                                   placeholder="Buscar empleado...">
                        </div>
                        
                        <!-- Tipo de Incidencia -->
                        <div class="col-md-2">
                            <label for="filtroTipo" class="form-label val-form-label">Tipo</label>
                            <select class="form-select val-form-select" id="filtroTipo" name="tipo_incidencia">
                                <option value="">Todos</option>
                                <option value="retardo">Retardos</option>
                                <option value="comision">Comisiones</option>
                                <option value="dia_economico">Días económicos</option>
                                <option value="ausencia">Ausencias</option>
                            </select>
                        </div>
                        
                        <!-- Fecha Inicio -->
                        <div class="col-md-2">
                            <label for="filtroFechaInicio" class="form-label val-form-label">Fecha Inicio</label>
                            <input type="date" class="form-control val-form-control" 
                                   id="filtroFechaInicio" name="fecha_inicio">
                        </div>
                        
                        <!-- Fecha Fin -->
                        <div class="col-md-2">
                            <label for="filtroFechaFin" class="form-label val-form-label">Fecha Fin</label>
                            <input type="date" class="form-control val-form-control" 
                                   id="filtroFechaFin" name="fecha_fin">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Panel de estadísticas -->
        <div class="col-md-4">
            <div class="card val-card">
                <div class="card-header val-card-header-accent">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Estadísticas del Período
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="val-stat-card val-stat-pending rounded">
                                <div class="stat-number" id="statPendientes">0</div>
                                <div class="small">Pendientes</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="val-stat-card val-stat-approved rounded">
                                <div class="stat-number" id="statAprobados">0</div>
                                <div class="small">Aprobados</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="val-stat-card val-stat-rejected rounded">
                                <div class="stat-number" id="statRechazados">0</div>
                                <div class="small">Rechazados</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <small class="text-muted">Tasa de aprobación: </small>
                        <strong id="statTasaAprobacion">0%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas y búsqueda -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (tienePermiso('validaciones_aprobar')): ?>
                    <button type="button" class="btn btn-val-secondary" id="btnAprobarMasivo" disabled>
                        <i class="fas fa-circle-check me-1"></i> Aprobar Seleccionados
                    </button>
                    <?php endif; ?>
                    <?php if (tienePermiso('validaciones_rechazar')): ?>
                    <button type="button" class="btn btn-val-danger" id="btnRechazarMasivo" disabled>
                        <i class="fas fa-circle-xmark me-1"></i> Rechazar Seleccionados
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-val-warning" id="btnSolicitarInfoMasivo" disabled>
                        <i class="fas fa-circle-info me-1"></i> Solicitar Información
                    </button>
                </div>
                
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                        <label class="form-check-label" for="selectAllCheckbox">
                            Seleccionar Todos
                        </label>
                    </div>
                    
                    <button type="button" class="btn btn-val-outline btn-sm" id="btnActualizar">
                        <i class="fas fa-rotate me-1"></i> Actualizar
                    </button>
                    
                    <div class="dropdown">
                        <button class="btn btn-val-outline btn-sm dropdown-toggle" 
                                type="button" id="dropdownReportes" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i> Reportes
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" id="btnDescargarPDF">
                                <i class="fas fa-file-pdf me-2"></i> Descargar PDF
                            </a></li>
                            <li><a class="dropdown-item" href="#" id="btnDescargarExcel">
                                <i class="fas fa-file-excel me-2"></i> Descargar Excel
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de selección de empleados -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card val-card">
                <div class="card-header val-card-header-secondary">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Seleccionar Empleados para Validar
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Lista de empleados a cargo -->
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <h6 class="text-muted mb-0"><i class="fas fa-users me-2" style="color: var(--pantone-primary);"></i>Empleados a mi cargo:</h6>
                                <div class="val-filtro-group" role="group">
                                    <button type="button" class="val-btn-filtro val-btn-todos active" id="btnFiltroTodos" onclick="filtrarEmpleados('todos')">
                                        <i class="fas fa-users me-1"></i>Todos
                                    </button>
                                    <button type="button" class="val-btn-filtro val-btn-pendientes" id="btnFiltroPendientes" onclick="filtrarEmpleados('pendientes')">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Por Justificar
                                    </button>
                                    <button type="button" class="val-btn-filtro val-btn-retardos" id="btnFiltroRetardos" onclick="filtrarEmpleados('retardos')">
                                        <i class="fas fa-clock me-1"></i>Retardos
                                    </button>
                                    <button type="button" class="val-btn-filtro val-btn-incidencias" id="btnFiltroIncidencias" onclick="filtrarEmpleados('incidencias')">
                                        <i class="fas fa-exclamation-circle me-1"></i>Incidencias
                                    </button>
                                    <button type="button" class="val-btn-filtro val-btn-sin-pendientes" id="btnFiltroSinPendientes" onclick="filtrarEmpleados('sin_pendientes')">
                                        <i class="fas fa-check-circle me-1"></i>Al Día
                                    </button>
                                </div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted" id="contadorFiltro">Mostrando todos los empleados</small>
                            </div>
                            <div class="row" id="empleadosContainer">
                                <div class="col-12 text-center py-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando empleados...</span>
                                    </div>
                                    <div class="mt-2 text-muted">Cargando lista de empleados...</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Panel de resumen -->
                        <div class="col-md-4">
                            <div class="card val-card" style="border: 2px solid var(--pantone-secondary);">
                                <div class="card-header val-card-header-light">
                                    <h6 class="mb-0">Resumen de Selección</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="val-stat-card val-stat-pending rounded mb-3">
                                        <div class="stat-number" id="empleadosSeleccionados">0</div>
                                        <div class="small">Seleccionados</div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-val-secondary btn-sm" id="btnValidarSeleccionados" disabled>
                                            <i class="fas fa-check me-1"></i> Validar Seleccionados
                                        </button>
                                        <button type="button" class="btn btn-val-outline btn-sm" id="btnSeleccionarTodosEmpleados">
                                            <i class="fas fa-users me-1"></i> Seleccionar Todos
                                        </button>
                                        <button type="button" class="btn btn-val-outline btn-sm" id="btnLimpiarSeleccion">
                                            <i class="fas fa-xmark me-1"></i> Limpiar Selección
                                        </button>
</div>
</div>

<!-- Script para manejar visibilidad del botón de notificación -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Override de la función mostrarOcultarCamposModal para manejar notificaciones
    const originalMostrarOcultarCamposModal = window.mostrarOcultarCamposModal;
    
    window.mostrarOcultarCamposModal = function() {
        // Llamar a la función original primero
        if (originalMostrarOcultarCamposModal) {
            originalMostrarOcultarCamposModal();
        }
        
        const estado = document.querySelector('input[name="estadoValidacion"]:checked')?.value;
        const divMotivoRechazo = document.getElementById('divMotivoRechazo');
        const esSinSolicitud = window.esIncidenciaSinSolicitud;
        
        // Mostrar/ocultar campos según el estado
        if (divMotivoRechazo) {
            divMotivoRechazo.style.display = estado === 'rechazado' ? 'block' : 'none';
        }
        
        // Para incidencias sin solicitud, siempre mostrar comentarios
        const comentariosDiv = document.getElementById('comentariosAdicionales')?.closest('.col-md-6');
        if (comentariosDiv && esSinSolicitud) {
            comentariosDiv.querySelector('label').innerHTML = '<i class="fas fa-comment text-warning me-1"></i>Mensaje para el empleado:';
            comentariosDiv.querySelector('textarea').placeholder = 'Escriba un mensaje para que el empleado justifique su retraso...';
        }
    };
    
    // Listener para detectar cuando se abre el modal
    const modal = document.getElementById('modalValidacion');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            // Mostrar botón de notificación si es una incidencia sin solicitud
            const btnNotificar = document.getElementById('btnNotificarEmpleado');
            const helpText = document.getElementById('helpTextNotificacion');
            
            if (window.esIncidenciaSinSolicitud) {
                if (btnNotificar) btnNotificar.style.display = '';
                if (helpText) helpText.style.display = '';
            } else {
                if (btnNotificar) btnNotificar.style.display = 'none';
                if (helpText) helpText.style.display = 'none';
            }
            
            // Actualizar campos según el estado
            window.mostrarOcultarCamposModal();
        });
        
        // Resetear cuando se cierra el modal
        modal.addEventListener('hidden.bs.modal', function() {
            window.esIncidenciaSinSolicitud = false;
            
            const btnNotificar = document.getElementById('btnNotificarEmpleado');
            const helpText = document.getElementById('helpTextNotificacion');
            if (btnNotificar) btnNotificar.style.display = 'none';
            if (helpText) helpText.style.display = 'none';
        });
    }
    
    // Event listeners para los radio buttons
    document.querySelectorAll('input[name="estadoValidacion"]').forEach(radio => {
        radio.addEventListener('change', window.mostrarOcultarCamposModal);
    });
});
</script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de incidencias por validar con pestañas -->
    <div class="row">
        <div class="col-12">
            <div class="card val-card">
                <div class="card-header val-card-header-secondary">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">
                            <i class="fas fa-list-check me-2"></i>
                            Validaciones de Empleados Seleccionados
                        </h5>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="soloPendientes" checked>
                                <label class="form-check-label text-white" for="soloPendientes">
                                    Solo pendientes
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pestañas para Incidencias y Retardos -->
                <div class="card-header p-0" style="background: transparent; border-bottom: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs val-tabs-custom" id="validacionesTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-incidencias" data-bs-toggle="tab" data-bs-target="#panel-incidencias" type="button" role="tab" aria-selected="true">
                                    <i class="fas fa-calendar-check me-2"></i>Incidencias
                                    <span class="badge ms-2 bg-light text-dark" id="countIncidencias">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-retardos" data-bs-toggle="tab" data-bs-target="#panel-retardos" type="button" role="tab" aria-selected="false">
                                    <i class="fas fa-clock me-2"></i>Retardos
                                    <span class="badge ms-2 bg-light text-dark" id="countRetardos">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-aprobados" data-bs-toggle="tab" data-bs-target="#panel-aprobados" type="button" role="tab" aria-selected="false">
                                    <i class="fas fa-check-circle me-2"></i>Aprobados
                                    <span class="badge ms-2 bg-success" id="countAprobados">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-rechazados" data-bs-toggle="tab" data-bs-target="#panel-rechazados" type="button" role="tab" aria-selected="false">
                                    <i class="fas fa-times-circle me-2"></i>Rechazados
                                    <span class="badge ms-2 bg-danger" id="countRechazados">0</span>
                                </button>
                            </li>
                        </ul>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="mostrarModalResumenIncidenciasFromData()" title="Ver resumen de incidencias">
                                <i class="fas fa-chart-pie me-1"></i> Resumen Incidencias
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="mostrarModalResumenRetardosFromData()" title="Ver resumen de retardos">
                                <i class="fas fa-stopwatch me-1"></i> Resumen Retardos
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="validacionesTabContent">
                        <!-- Panel de Incidencias -->
                        <div class="tab-pane fade show active" id="panel-incidencias" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover val-table" id="tablaIncidencias">
                                    <thead>
                                        <tr>
                                            <th width="40">
                                                <input type="checkbox" id="selectAllIncidencias" class="form-check-input">
                                            </th>
                                            <th width="80"><i class="fas fa-id-card me-1"></i>ID</th>
                                            <th width="200"><i class="fas fa-user me-1"></i>Nombre</th>
                                            <th width="120"><i class="fas fa-sitemap me-1"></i>Área</th>
                                            <th width="120"><i class="fas fa-calendar me-1"></i>Fecha</th>
                                            <th width="100"><i class="fas fa-clock me-1"></i>Día</th>
                                            <th width="150"><i class="fas fa-exclamation-circle me-1"></i>Tipo</th>
                                            <th width="100"><i class="fas fa-stopwatch me-1"></i>Duración</th>
                                            <th width="80"><i class="fas fa-clock me-1"></i>Horario</th>
                                            <th width="100"><i class="fas fa-paperclip me-1"></i>Evidencia</th>
                                            <th width="80"><i class="fas fa-flag me-1"></i>Prioridad</th>
                                            <th width="120"><i class="fas fa-check-circle me-1"></i>Estado</th>
                                            <th width="120"><i class="fas fa-cogs me-1"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaIncidenciasBody">
                                        <tr>
                                            <td colspan="13" class="text-center py-4">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-circle-info me-2"></i>
                                                    <strong>Seleccione empleados para ver sus incidencias</strong>
                                                    <br><small>Use la lista de empleados arriba para seleccionar los colaboradores que desea validar</small>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Panel de Retardos -->
                        <div class="tab-pane fade" id="panel-retardos" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover val-table" id="tablaRetardos">
                                    <thead>
                                        <tr>
                                            <th width="40">
                                                <input type="checkbox" id="selectAllRetardos" class="form-check-input">
                                            </th>
                                            <th width="80"><i class="fas fa-id-card me-1"></i>ID</th>
                                            <th width="200"><i class="fas fa-user me-1"></i>Nombre</th>
                                            <th width="120"><i class="fas fa-sitemap me-1"></i>Área</th>
                                            <th width="120"><i class="fas fa-calendar me-1"></i>Fecha</th>
                                            <th width="100"><i class="fas fa-clock me-1"></i>Día</th>
                                            <th width="100"><i class="fas fa-sign-in-alt me-1"></i>Entrada</th>
                                            <th width="100"><i class="fas fa-running me-1"></i>Retardo</th>
                                            <th width="130"><i class="fas fa-clock me-1"></i>Horario (Esp/Real)</th>
                                            <th width="100"><i class="fas fa-comment me-1"></i>Motivo</th>
                                            <th width="80"><i class="fas fa-flag me-1"></i>Prioridad</th>
                                            <th width="120"><i class="fas fa-check-circle me-1"></i>Estado</th>
                                            <th width="120"><i class="fas fa-cogs me-1"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRetardosBody">
                                        <tr>
                                            <td colspan="13" class="text-center py-4">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-circle-info me-2"></i>
                                                    <strong>Seleccione empleados para ver sus retardos</strong>
                                                    <br><small>Use la lista de empleados arriba para seleccionar los colaboradores</small>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Panel de Aprobados -->
                        <div class="tab-pane fade" id="panel-aprobados" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover val-table" id="tablaAprobados">
                                    <thead>
                                        <tr>
                                            <th width="40">
                                                <input type="checkbox" id="selectAllAprobados" class="form-check-input">
                                            </th>
                                            <th width="80"><i class="fas fa-id-card me-1"></i>ID</th>
                                            <th width="200"><i class="fas fa-user me-1"></i>Nombre</th>
                                            <th width="120"><i class="fas fa-sitemap me-1"></i>Área</th>
                                            <th width="120"><i class="fas fa-calendar me-1"></i>Fecha</th>
                                            <th width="100"><i class="fas fa-clock me-1"></i>Día</th>
                                            <th width="150"><i class="fas fa-exclamation-circle me-1"></i>Tipo</th>
                                            <th width="100"><i class="fas fa-paperclip me-1"></i>Evidencia</th>
                                            <th width="150"><i class="fas fa-comment me-1"></i>Motivo Aprobación</th>
                                            <th width="120"><i class="fas fa-check-circle me-1"></i>Estado</th>
                                            <th width="120"><i class="fas fa-cogs me-1"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaAprobadosBody">
                                        <tr>
                                            <td colspan="11" class="text-center py-4">
                                                <div class="alert alert-success">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    <strong>No hay incidencias aprobadas</strong>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Panel de Rechazados -->
                        <div class="tab-pane fade" id="panel-rechazados" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover val-table" id="tablaRechazados">
                                    <thead>
                                        <tr>
                                            <th width="40">
                                                <input type="checkbox" id="selectAllRechazados" class="form-check-input">
                                            </th>
                                            <th width="80"><i class="fas fa-id-card me-1"></i>ID</th>
                                            <th width="200"><i class="fas fa-user me-1"></i>Nombre</th>
                                            <th width="120"><i class="fas fa-sitemap me-1"></i>Área</th>
                                            <th width="120"><i class="fas fa-calendar me-1"></i>Fecha</th>
                                            <th width="100"><i class="fas fa-clock me-1"></i>Día</th>
                                            <th width="150"><i class="fas fa-exclamation-circle me-1"></i>Tipo</th>
                                            <th width="100"><i class="fas fa-paperclip me-1"></i>Evidencia</th>
                                            <th width="150"><i class="fas fa-comment me-1"></i>Motivo Rechazo</th>
                                            <th width="120"><i class="fas fa-check-circle me-1"></i>Estado</th>
                                            <th width="120"><i class="fas fa-cogs me-1"></i>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRechazadosBody">
                                        <tr>
                                            <td colspan="11" class="text-center py-4">
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-times-circle me-2"></i>
                                                    <strong>No hay incidencias rechazadas</strong>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Paginación -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Mostrando <span id="registroInicio">0</span> - <span id="registroFin">0</span> 
                            de <span id="registroTotal">0</span> registros
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="paginacion">
                                <!-- Se generará dinámicamente -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de validación detallada -->
<style>
    .val-modal .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }
    .val-modal .modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 3px solid #BC955C;
    }
    .val-modal .modal-body {
        padding: 2rem;
        background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
    }
    .val-modal .card {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: none;
    }
    .val-modal .card-header {
        padding: 1rem 1.5rem;
        border-bottom: 2px solid rgba(0,0,0,0.1);
    }
    .val-modal .card-body {
        padding: 1.5rem;
    }
    .val-info-badge {
        background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
    }
    .val-info-item {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
    }
    .val-info-item label {
        display: block;
        color: #6b7280;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }
    .val-info-item .value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
    }
    .val-btn-decision {
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .val-btn-decision:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .val-historial-item {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border-left: 4px solid #BC955C;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
</style>

<div class="modal fade val-modal" id="modalValidacion" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white rounded-circle p-2">
                        <i class="fas fa-user-check fa-lg" style="color: #9F2241;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Validación de Incidencia</h5>
                        <small class="opacity-75">Revise los detalles y tome una decisión</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalValidacionId">
                <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                
                <!-- Información del empleado -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="val-info-badge">
                            <i class="fas fa-user"></i>
                            <span id="modalEmpleadoInfo"></span>
                            <span class="opacity-50">|</span>
                            <i class="fas fa-id-badge"></i>
                            <span class="badge bg-light text-dark" id="modalEmpleadoId" style="font-size: 0.9rem;"></span>
                            <span class="opacity-50">|</span>
                            <i class="fas fa-building"></i>
                            <span id="modalAreaInfo"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Detalles de la incidencia -->
                <div class="card mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-clipboard-list"></i>
                            <h6 class="mb-0">Detalles de la Incidencia</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <div class="val-info-item">
                                    <label><i class="fas fa-calendar-alt me-1"></i>Fecha</label>
                                    <div class="value" id="modalFechaIncidencia"></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="val-info-item">
                                    <label><i class="fas fa-clock me-1"></i>Día</label>
                                    <div class="value" id="modalDiaSemana"></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="val-info-item">
                                    <label><i class="fas fa-sign-in-alt me-1"></i>Horario</label>
                                    <div class="value" id="modalHorario"></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="val-info-item">
                                    <label><i class="fas fa-stopwatch me-1"></i>Duración</label>
                                    <div class="value" id="modalDuracion"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="val-info-item text-start">
                                    <label><i class="fas fa-tag me-1"></i>Tipo de Incidencia</label>
                                    <div id="modalDescripcion"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Historial del empleado -->
                <div class="card mb-4" style="border-top: 3px solid #235B4E;">
                    <div class="card-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: #ffffff;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-history"></i>
                            <h6 class="mb-0">Historial Reciente del Empleado</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="modalHistorialEmpleado" style="max-height: 200px; overflow-y: auto;">
                            <p class="text-muted text-center">Cargando historial...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Formulario de decisión -->
                <div class="card" style="border-top: 3px solid #BC955C;">
                    <div class="card-header" style="background: linear-gradient(135deg, #BC955C 0%, #9F2241 100%); color: #ffffff;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-gavel"></i>
                            <h6 class="mb-0">Decisión de Validación</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold text-dark">Seleccione su decisión:</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <label class="val-btn-decision btn btn-success flex-fill text-white" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); border: none;">
                                        <input type="radio" class="btn-check" name="estadoValidacion" id="radioAprobar" value="aprobado" required style="display:none;">
                                        <i class="fas fa-check-circle me-2"></i>Aprobar
                                    </label>
                                    
                                    <label class="val-btn-decision btn btn-danger flex-fill text-white" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); border: none;">
                                        <input type="radio" class="btn-check" name="estadoValidacion" id="radioRechazar" value="rechazado" style="display:none;">
                                        <i class="fas fa-times-circle me-2"></i>Rechazar
                                    </label>
                                    
                                    <label class="val-btn-decision btn btn-warning flex-fill text-dark" style="background: linear-gradient(135deg, #BC955C 0%, #a67c3d 100%); border: none;">
                                        <input type="radio" class="btn-check" name="estadoValidacion" id="radioSolicitarInfo" value="requiere_info" style="display:none;">
                                        <i class="fas fa-question-circle me-2"></i>Solicitar Info
                                    </label>
                                    
                                    <label class="val-btn-decision btn btn-warning flex-fill text-dark" style="background: linear-gradient(135deg, #FFC107 0%, #d39e00 100%); border: 2px solid #d39e00; display: none;" id="btnNotificarEmpleado">
                                        <input type="radio" class="btn-check" name="estadoValidacion" id="radioNotificar" value="notificar_empleado" style="display:none;">
                                        <i class="fas fa-bell me-2"></i>Notificar al Empleado
                                    </label>
                                </div>
                                <small class="text-muted" id="helpTextNotificacion" style="display: none;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Al seleccionar "Notificar al Empleado", se le enviará un mensaje para que justifique su retraso.
                                </small>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6" id="divMotivoRechazo" style="display: none;">
                                <label for="motivoRechazo" class="form-label fw-bold text-dark">
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>Motivo del Rechazo:
                                </label>
                                <select class="form-select form-select-lg" id="motivoRechazo">
                                    <option value="">Seleccionar motivo...</option>
                                    <option value="sin_justificante">Sin justificante válido</option>
                                    <option value="fuera_politica">Fuera de políticas de RH</option>
                                    <option value="evidencia_insuficiente">Evidencia insuficiente</option>
                                    <option value="no_aplicable">No aplica para este tipo de incidencia</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="comentariosAdicionales" class="form-label fw-bold text-dark">
                                    <i class="fas fa-comment text-secondary me-1"></i>Comentarios:
                                </label>
                                <textarea class="form-control" id="comentariosAdicionales" rows="2" 
                                          placeholder="Ingrese comentarios adicionales..."></textarea>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12" id="divEvidenciaRecibida" style="display: none;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="evidenciaRecibida" style="width: 1.2em; height: 1.2em;">
                                    <label class="form-check-label fw-bold text-dark ms-2" for="evidenciaRecibida">
                                        <i class="fas fa-paperclip text-primary me-1"></i>Evidencia recibida y verificada
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botón de guardar -->
                <div class="d-grid gap-2 mt-4">
                    <button type="button" class="btn btn-lg text-white" id="btnGuardarDecision" 
                            style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); border: none; border-radius: 12px; padding: 1rem;">
                        <i class="fas fa-save me-2"></i>Guardar Decisión
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal de Resumen de Incidencias -->
<div class="modal fade" id="modalResumenIncidencias" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title">
                    <i class="fas fa-chart-pie me-2"></i>
                    Resumen de Incidencias por Tipo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h3 class="text-primary" id="resumenComisionEntrada">0</h3>
                                <p class="mb-0"><i class="fas fa-sign-in-alt me-1"></i>Comisiones de Entrada</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h3 class="text-primary" id="resumenComisionSalida">0</h3>
                                <p class="mb-0"><i class="fas fa-sign-out-alt me-1"></i>Comisiones de Salida</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h3 class="text-primary" id="resumenComisionDia">0</h3>
                                <p class="mb-0"><i class="fas fa-calendar-day me-1"></i>Comisiones Todo el Día</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-success">
                            <div class="card-body">
                                <h3 class="text-success" id="resumenDiaEconomico">0</h3>
                                <p class="mb-0"><i class="fas fa-coins me-1"></i>Días Económicos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-warning">
                            <div class="card-body">
                                <h3 class="text-warning" id="resumenAusencia">0</h3>
                                <p class="mb-0"><i class="fas fa-user-times me-1"></i>Ausencias</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-info">
                            <div class="card-body">
                                <h3 class="text-info" id="resumenLicencia">0</h3>
                                <p class="mb-0"><i class="fas fa-notes-medical me-1"></i>Licencias Médicas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body">
                                <h3 class="text-secondary" id="resumenVacaciones">0</h3>
                                <p class="mb-0"><i class="fas fa-umbrella-beach me-1"></i>Vacaciones</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border-dark">
                            <div class="card-body">
                                <h3 class="text-dark" id="resumenOtros">0</h3>
                                <p class="mb-0"><i class="fas fa-ellipsis-h me-1"></i>Otros</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <h4>Total Incidencias: <span id="resumenTotalIncidencias" class="badge bg-primary fs-5">0</span></h4>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Resumen de Retardos -->
<div class="modal fade" id="modalResumenRetardos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #BC955C 0%, #9F2241 100%); color: #ffffff;">
                <h5 class="modal-title">
                    <i class="fas fa-stopwatch me-2"></i>
                    Resumen de Retardos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="card border-warning">
                            <div class="card-body">
                                <h2 class="text-warning" id="resumenRetardoMenor">0</h2>
                                <p class="mb-0"><i class="fas fa-exclamation-circle me-1"></i>Retardos Menores</p>
                                <small class="text-muted">(≤ 15 min)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-danger">
                            <div class="card-body">
                                <h2 class="text-danger" id="resumenRetardoMayor">0</h2>
                                <p class="mb-0"><i class="fas fa-exclamation-triangle me-1"></i>Retardos Mayores</p>
                                <small class="text-muted">(> 15 min)</small>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <h4>Total Retardos: <span id="resumenTotalRetardos" class="badge bg-warning text-dark fs-5">0</span></h4>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

