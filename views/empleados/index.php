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
    .card-header-custom {
        background-color: #9F2241;
        color: white;
    }
    .btn-custom {
        background-color: #691C32;
        color: white;
    }
    .btn-custom:hover {
        background-color: #501526;
        color: white;
    }
    input[readonly].form-control {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
    .avatar-circle {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border: 3px solid #f8f9fa;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .emp-card {
        transition: all 0.3s ease;
    }
    .emp-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(151, 34, 65, 0.15) !important;
    }
    
    /* Modal de justificación - asegurar que esté al frente */
    #modalJustificacion {
        z-index: 1070 !important;
    }
    #modalJustificacion.show {
        z-index: 1070 !important;
    }
    #modalJustificacion .modal-dialog {
        z-index: 1071 !important;
    }
    
    /* Estilos elegantes para tabs del modal empleado */
    .empleado-tabs {
        background: linear-gradient(135deg, #f8f7f5 0%, #f0ede8 100%);
        border-bottom: 2px solid var(--pantone-primary);
        padding: 8px 8px 0 8px;
    }
    .empleado-tabs .nav-link {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 10px 12px;
        border: none;
        border-radius: 8px 8px 0 0;
        color: var(--pantone-gray-dark);
        background: transparent;
        transition: all 0.3s ease;
        position: relative;
        margin-bottom: -2px;
        z-index: 1;
    }
    .empleado-tabs .nav-link i {
        font-size: 0.85rem;
        margin-right: 4px;
    }
    .empleado-tabs .nav-link:hover {
        color: var(--pantone-primary);
        background: rgba(151, 34, 65, 0.08);
    }
    .empleado-tabs .nav-link.active {
        color: var(--pantone-primary);
        background: white;
        box-shadow: 0 -2px 10px rgba(151, 34, 65, 0.1);
        border-top: 3px solid var(--pantone-primary);
    }
    .empleado-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--pantone-primary);
    }
    .empleado-tabs .nav-link.active i {
        color: var(--pantone-primary);
    }
    
    /* Colores específicos por pestaña */
    #general-tab.active { border-top-color: #9F2241; }
    #general-tab.active i { color: #9F2241 !important; }
    
    #asistencia-tab.active { border-top-color: #235B4E; }
    #asistencia-tab.active i { color: #235B4E !important; }
    
    #retardos-tab.active { border-top-color: #BC955C; }
    #retardos-tab.active i { color: #BC955C !important; }
    
    #comisiones-tab.active { border-top-color: #BC955C; }
    #comisiones-tab.active i { color: #BC955C !important; }
    
    #diaseconomicos-tab.active { border-top-color: #235B4E; }
    #diaseconomicos-tab.active i { color: #235B4E !important; }
    
    #licenciasmedicas-tab.active { border-top-color: #235B4E; }
    #licenciasmedicas-tab.active i { color: #235B4E !important; }
    
    #sanciones-tab.active { border-top-color: #691C32; }
    #sanciones-tab.active i { color: #691C32 !important; }
    
    #ciclos-tab.active { border-top-color: #9F2241; }
    #ciclos-tab.active i { color: #9F2241 !important; }
    
    /* Hover states */
    #general-tab:hover i { color: #9F2241; }
    #asistencia-tab:hover i { color: #235B4E; }
    #retardos-tab:hover i { color: #BC955C; }
    #comisiones-tab:hover i { color: #BC955C; }
    #diaseconomicos-tab:hover i { color: #235B4E; }
    #licenciasmedicas-tab:hover i { color: #235B4E; }
    #sanciones-tab:hover i { color: #691C32; }
    #ciclos-tab:hover i { color: #9F2241; }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .emp-card {
            margin-bottom: 15px;
        }
    }
    
    @media (max-width: 768px) {
        .empleado-tabs {
            display: flex;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding: 6px 6px 0 6px;
        }
        .empleado-tabs .nav-link {
            font-size: 0.7rem;
            padding: 10px 8px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .empleado-tabs .nav-link i {
            display: inline-block;
            font-size: 0.9rem;
            margin-right: 3px;
            margin-bottom: 0;
        }
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        .row.mb-4 {
            margin-bottom: 15px !important;
        }
        .col-md-6 {
            margin-bottom: 10px;
        }
        .text-end {
            text-align: left !important;
        }
        /* Modal employee - full width on mobile */
        .modal-dialog {
            max-width: 98%;
            margin: 10px auto;
        }
        .modal-body {
            padding: 12px;
        }
        /* Modal fullscreen en móviles */
        .modal-fullscreen {
            width: 100% !important;
            max-width: 100% !important;
            height: 100vh !important;
            margin: 0 !important;
            border-radius: 0 !important;
        }
        .modal-fullscreen .modal-content {
            height: 100vh !important;
            max-height: 100vh !important;
            border-radius: 0 !important;
        }
        /* Accordion sections */
        .seccion-header {
            font-size: 0.85rem;
            padding: 10px 12px;
        }
        .seccion-header i {
            margin-right: 8px;
            width: 20px;
        }
        .accordion-button {
            font-size: 0.85rem;
            padding: 10px 12px;
        }
        .accordion-button::after {
            width: 16px;
            height: 16px;
            background-size: 16px;
        }
        .modal-fullscreen {
            width: 100%;
            max-width: 100%;
            height: 100%;
            margin: 0;
        }
    }
    
    @media (max-width: 576px) {
        .seccion-header {
            font-size: 0.8rem;
            padding: 8px 10px;
        }
        .accordion-button {
            font-size: 0.8rem;
            padding: 8px 10px;
        }
        .empleado-tabs .nav-link {
            font-size: 0.65rem;
            padding: 8px 6px;
        }
        .empleado-tabs .nav-link i {
            font-size: 0.8rem;
        }
        .card {
            border-radius: 8px;
        }
        .card-body {
            padding: 12px;
        }
        h2 {
            font-size: 1.25rem;
        }
        .btn {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        .input-group-text {
            padding: 6px 10px;
        }
        /* Tablas del modal */
        .table {
            font-size: 0.75rem;
        }
        .table th, .table td {
            padding: 6px 4px;
        }
    }
</style>
<?php 
require_once 'helpers/permisos_helper.php';
require_once 'models/Usuario.php';
$rolActual = strtolower((string)($_SESSION['rol'] ?? ''));
$puedeGestionarCiclos = in_array($rolActual, ['admin', 'superadmin'], true);
$esAdmin = $puedeGestionarCiclos;
$esUsuario = $rolActual === 'usuario';
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0"><i class="fas fa-users me-2"></i>Directorio de Empleados</h2>
            <p class="text-muted">Gestión y control de personal</p>
        </div>
        <div class="col-md-6 text-end">
            <?php if (tienePermiso('empleados_crear')): ?>
            <button class="btn btn-outline-secondary me-2" onclick="abrirModalMarcaciones()" title="Marcaciones - Modificar Justificaciones">
                <i class="fas fa-clock me-2"></i>Marcaciones
            </button>
            <a href="<?= BASE_URL ?>/empleados/create" class="btn btn-custom">
                <i class="fas fa-user-plus me-2"></i>Nuevo Empleado
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de Búsqueda - Solo visible para admin/superadmin -->
    <?php if (!$esUsuario): ?>
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>/empleados" class="row g-3 align-items-center">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" 
                               placeholder="Buscar por nombre, apellido, área o RFC..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grid de Empleados -->
    <div class="row g-4">
        <?php if (empty($empleados)): ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h4>No se encontraron empleados</h4>
                    <p>Intenta con otros términos de búsqueda.</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($empleados as $emp): ?>
                <?php 
                    $status = $emp['status_asistencia'] ?? '';
                    $statusColor = '#98989A';
                    $borderColor = '#98989A';
                    if ($status === 'presente') {
                        $statusColor = '#235B4E';
                        $borderColor = '#235B4E';
                    } elseif ($status === 'salida') {
                        $statusColor = '#BC955C';
                        $borderColor = '#BC955C';
                    }
                ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 emp-card" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: all 0.3s ease;">
                        <div style="height: 6px; background: linear-gradient(90deg, <?= $borderColor ?> 0%, <?= $borderColor ?>80 100%);"></div>
                        <div class="card-body text-center pt-4 pb-2" style="position: relative;">
                            <div class="position-relative d-inline-block">
                                <?php if (!empty($emp['foto_cara']) && file_exists($emp['foto_cara'])): ?>
                                    <img src="<?= BASE_URL . '/' . $emp['foto_cara'] ?>" class="rounded-circle" style="width: 90px; height: 90px; object-fit: cover; border: 3px solid <?= $borderColor ?>; box-shadow: 0 4px 12px <?= $borderColor ?>30;">
                                <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 90px; height: 90px; background: linear-gradient(135deg, #f8f7f5 0%, #e8e6e2 100%); border: 3px solid <?= $borderColor ?>;">
                                        <i class="fas fa-user" style="font-size: 2.5rem; color: #98989A;"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="position-absolute bottom-0 end-0" style="width: 16px; height: 16px; background-color: <?= $statusColor ?>; border: 3px solid white; border-radius: 50%;"></span>
                            </div>
                            
                            <h5 class="card-title mt-3 mb-1" style="color: #691C32; font-weight: 600; font-size: 1.1rem;">
                                <?= htmlspecialchars($emp['nombre']) ?> <?= htmlspecialchars($emp['apellido']) ?>
                                <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">ID: <?= $emp['id'] ?></span>
                            </h5>
                            <p class="mb-2" style="color: #235B4E; font-weight: 500; font-size: 0.85rem; text-transform: uppercase;"><i class="fas fa-building me-1"></i><?= htmlspecialchars($emp['area'] ?? 'Sin Área') ?></p>
                            
                            <div class="d-flex justify-content-center gap-2 mt-2">
                                <span class="badge" style="background-color: #f8f7f5; color: #6F7271; border: 1px solid #d0d0d2; padding: 6px 10px; font-size: 0.75rem;">
                                    <i class="fas fa-id-card me-1"></i><?= htmlspecialchars($emp['rfc'] ?? 'S/RFC') ?>
                                </span>
                            </div>
                            
                            <div class="mt-3 mb-2">
                                <?php if ($status === 'presente'): ?>
                                    <span class="badge" style="background-color: #235B4E; color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem;">
                                        <i class="fas fa-check-circle me-1"></i>Entrada: <?= $emp['hora_entrada'] ?? '--:--' ?>
                                    </span>
                                <?php elseif ($status === 'salida'): ?>
                                    <span class="badge" style="background-color: #BC955C; color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem;">
                                        <i class="fas fa-clock me-1"></i>Salida Reg.
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: #98989A; color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem;">
                                        <i class="fas fa-times-circle me-1"></i>Ausente
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 pb-3 px-3">
                            <div class="d-grid gap-2">
                                <button onclick="verEmpleado(<?php echo intval($emp['id'] ?? 0); ?>)" class="btn btn-sm" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: white; border: none; border-radius: 25px; padding: 10px; font-weight: 500;">
                                    <i class="fas fa-eye me-2"></i>Detalles
                                </button>
                                <?php if (!$esUsuario): ?>
                                <button onclick="abrirModalEditar(<?php echo intval($emp['id'] ?? 0); ?>)" class="btn btn-sm" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: white; border: none; border-radius: 25px; padding: 10px; font-weight: 500;">
                                    <i class="fas fa-edit me-2"></i>Editar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
    </div>
</div>

<!-- Modal de Detalles del Empleado -->
<div class="modal fade" id="empleadoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content" style="height: 100vh; max-height: 100vh;">
            <div class="modal-header card-header-custom">
                <h5 class="modal-title text-white" id="modalEmpleadoNombre">Cargando...</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="overflow-y: auto;">
                <style>
                    .seccion-header, .accordion-item { display: none !important; }
                    #empleadoTabs { 
                        display: flex !important; 
                        flex-wrap: nowrap;
                        overflow-x: auto;
                        white-space: nowrap;
                    }
                    #empleadoTabs .nav-item {
                        display: inline-block;
                        flex-shrink: 0;
                    }
                    #empleadoTabs .nav-link {
                        display: inline-block;
                        white-space: nowrap;
                    }
                    .seccion-header {
                        background: #f8f9fa;
                        border: none;
                        border-bottom: 1px solid #e9ecef;
                        padding: 12px 20px;
                        color: #691C32;
                        font-weight: 600;
                        font-size: 0.9rem;
                        transition: all 0.3s ease;
                    }
                    .seccion-header:hover {
                        background: #e9ecef;
                    }
                    .seccion-header:not(.collapsed) {
                        background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
                        color: white;
                        box-shadow: 0 2px 8px rgba(157, 34, 65, 0.3);
                    }
                    .seccion-header:not(.collapsed) i {
                        transform: scale(1.1);
                    }
                    .seccion-icon {
                        color: #9F2241;
                        margin-right: 10px;
                        transition: transform 0.3s ease;
                    }
                    .seccion-header:not(.collapsed) .seccion-icon {
                        color: white;
                    }
                    .seccion-tab {
                        background: transparent;
                        border: none;
                        border-bottom: 2px solid transparent;
                        color: #691C32;
                        font-weight: 600;
                        padding: 10px 15px;
                        font-size: 0.85rem;
                    }
                    .seccion-tab:hover {
                        background: #f8f9fa;
                        border-bottom-color: #e9ecef;
                    }
                    .seccion-tab.active {
                        background: transparent;
                        border-bottom-color: #691C32;
                        color: #691C32;
                    }
                </style>
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs border-bottom mb-0" role="tablist" id="empleadoTabs">
                    <li class="nav-item">
                        <button class="nav-link active seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">
                            <i class="fas fa-user seccion-icon"></i>General
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-asistencia" type="button">
                            <i class="fas fa-calendar-check seccion-icon"></i>Asistencia
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-retardos" type="button">
                            <i class="fas fa-clock seccion-icon"></i>Retardos
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-comisiones" type="button">
                            <i class="fas fa-briefcase seccion-icon"></i>Comisiones
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-incidencias" type="button">
                            <i class="fas fa-exclamation-triangle seccion-icon"></i>Incidencias
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-vacaciones" type="button">
                            <i class="fas fa-umbrella-beach seccion-icon"></i>Vacaciones
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-licencias" type="button">
                            <i class="fas fa-user-md seccion-icon"></i>Licencias
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-eco" type="button">
                            <i class="fas fa-calendar-day seccion-icon"></i>Días Eco.
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-cuidados" type="button">
                            <i class="fas fa-child seccion-icon"></i>Cuidados
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-constancias" type="button">
                            <i class="fas fa-file-signature seccion-icon"></i>Constancias
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-sanciones" type="button">
                            <i class="fas fa-gavel seccion-icon"></i>Sanciones
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link seccion-tab" data-bs-toggle="tab" data-bs-target="#tab-ciclos" type="button">
                            <i class="fas fa-school seccion-icon"></i>Ciclos
                        </button>
                    </li>
                </ul>
                <!-- Tab Content -->
                <div class="tab-content p-0" id="empleadoTabContent">
<div class="tab-pane fade show active" id="tab-general"><div class="p-3" id="general"></div></div>
                    <div class="tab-pane fade" id="tab-asistencia"><div class="table-responsive" id="asistencia"></div></div>
                    <div class="tab-pane fade" id="tab-retardos"><div class="p-3" id="retardos"></div></div>
                    <div class="tab-pane fade" id="tab-comisiones"><div class="p-3" id="comisiones"></div></div>
                    <div class="tab-pane fade" id="tab-incidencias"><div class="p-3" id="tabla-incidencias"></div></div>
                    <div class="tab-pane fade" id="tab-vacaciones"><div class="p-3" id="vacaciones"></div></div>
                    <div class="tab-pane fade" id="tab-licencias"><div class="p-3" id="licenciasmedicas"></div></div>
                    <div class="tab-pane fade" id="tab-eco"><div class="p-3" id="diaseconomicos"></div></div>
<div class="tab-pane fade" id="tab-cuidados"><div class="p-3" id="cuidados-maternos"></div></div>
                    <div class="tab-pane fade" id="tab-constancias"><div class="p-3" id="constancias"></div></div>
                    <div class="tab-pane fade" id="tab-sanciones"><div class="p-3" id="sanciones"></div></div>
                    <div class="tab-pane fade" id="tab-ciclos"><div class="p-3" id="ciclos"><div id="ciclos-content"></div></div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btnEditarEmpleado" class="btn btn-custom">Editar</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Asignación de Ciclos -->
<div class="modal fade" id="modalAsignarCiclo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Asignar Ciclo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAsignarCiclo">
                    <input type="hidden" id="ciclo_empleado_id" name="empleado_id">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Ciclo</label>
                        <select class="form-select form-select-sm" id="ciclo_select" name="ciclo_id" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control form-control-sm" id="ciclo_fecha_inicio" name="fecha_inicio" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control form-control-sm" id="ciclo_fecha_fin" name="fecha_fin" required>
                    </div>
                    <button type="submit" class="btn btn-sm" style="background-color: #9F2241; color: white;">
                        <i class="fas fa-save me-1"></i>Asignar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Justificación -->
<div class="modal fade" id="modalJustificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-pantone-primary text-white">
                <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Justificación de Incidencia</h5>
                <div>
                    <button type="button" class="btn btn-outline-light btn-sm me-1" onclick="mostrarFundamentos()" title="Ver fundamentos de la norma">
                        <i class="fas fa-info-circle me-1"></i>Fundamentos
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <form id="form-justificacion">
                    <input type="hidden" id="just_empleado_id" name="empleado_id">
                    <input type="hidden" id="just_retardo_id" name="retardo_id">
                    <input type="hidden" id="just_tipo_original" name="tipo_original">
                    <input type="hidden" id="just_tipo_retardo_original" name="tipo_retardo_original">
                    
                    <!-- Tipo de Incidencia (mostrado con opción de cambio) -->
                    <div class="row mb-3" id="row-tipo-incidencia">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Tipo de Incidencia</label>
                            <select class="form-select" id="tipo_justificacion" name="tipo_justificacion" onchange="cambiarTipoJustificacion()">
                                <option value="">Seleccione...</option>
                            </select>
                            <div id="tipo_incidencia_seleccionado" class="alert alert-primary d-none">
                                <i class="fas fa-check-circle me-2"></i>
                                <span id="tipo_incidencia_label"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Contenedor de opciones del catálogo -->
                    <div id="opciones_justificacion" class="row mb-3">
                        <!-- Las opciones se cargan dinámicamente aquí -->
                    </div>

                    <!-- Sección de datos del retardo (solo lectura) -->
                    <div id="seccion-datos-retardo" class="mb-4 d-none">
                        <div class="alert alert-light border" style="font-size: 1.1rem;">
                            <div class="row">
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <label class="form-label text-muted mb-1"><small>Fecha del Retardo</small></label>
                                    <div class="fw-bold" id="just_fecha_display">-</div>
                                    <input type="hidden" id="just_fecha" name="fecha">
                                </div>
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <label class="form-label text-muted mb-1"><small>Hora de Entrada</small></label>
                                    <div class="fw-bold text-primary" id="just_hora_entrada_display">-</div>
                                    <input type="hidden" id="just_hora_entrada" name="hora_entrada">
                                </div>
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <label class="form-label text-muted mb-1"><small>Minutos de Retardo <span class="text-info">(después de tolerancia)</span></small></label>
                                    <div class="fw-bold text-danger" id="just_minutos_display">-</div>
                                    <input type="hidden" id="just_minutos" name="minutos">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted mb-1"><small>Tipo de Retardo</small></label>
                                    <div class="fw-bold" id="just_tipo_retardo_display">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fundamentos Normativos (solo lectura - información para el empleado) -->
                    <div class="mb-3" id="seccion-fundamentos">
                        <div class="alert alert-info mb-0" style="font-size: 1rem;">
                            <label class="form-label fw-bold mb-2">
                                <i class="fas fa-info-circle me-1"></i>Fundamentos Normativos (Referencia)
                            </label>
                            <div id="just_fundamentos_display" class="text-dark" style="line-height: 1.6;">
                                Seleccione el tipo de incidencia para ver los fundamentos normativos.
                            </div>
                            <input type="hidden" id="just_fundamentos" name="fundamentos">
                        </div>
                    </div>

                    <!-- Lugar (solo para comisiones) -->
                    <div class="mb-3" id="seccion-lugar" style="display: none;">
                        <label for="just_lugar" class="form-label fw-bold">
                            <i class="fas fa-map-marker-alt me-1"></i>Lugar de la Comisión
                        </label>
                        <input type="text" class="form-control" id="just_lugar" name="lugar" 
                            placeholder="Indique el lugar donde se realizará la comisión...">
                    </div>

                    <!-- Fechas de Comisión (solo para comisión de todo el día) -->
                    <div class="mb-3" id="seccion-fechas-comision" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="just_fecha_inicio_comision" class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha a Justificar
                                </label>
                                <input type="date" class="form-control" id="just_fecha_inicio_comision" name="fecha_inicio_comision" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Motivo (solo para comisiones) -->
                    <div class="mb-3" id="seccion-motivo" style="display: none;">
                        <label for="just_motivo" class="form-label fw-bold">
                            <i class="fas fa-comment me-1"></i>Motivo de la Comisión
                        </label>
                        <textarea class="form-control" id="just_motivo" name="motivo" rows="2" 
                            placeholder="Describa el motivo de la comisión..."></textarea>
                    </div>

                    <!-- Documento de soporte (no aplica para retardos) -->
                    <div class="mb-3" id="seccion-soporte" style="display: none;">
                        <label for="just_soporte" class="form-label fw-bold">
                            <i class="fas fa-file-pdf me-1"></i>Documento de Soporte
                        </label>
                        <input type="file" class="form-control" id="just_soporte" name="soporte" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Formatos aceptados: PDF, JPG, PNG</small>
                    </div>

                    <!-- Sección día económico -->
                    <div class="mb-3" id="seccion-dia-economico" style="display: none;">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="just_dias_economicos" class="form-label fw-bold">
                                    <i class="fas fa-calendar-day me-1"></i>Días Solicitados
                                </label>
                                <select class="form-select" id="just_dias_economicos" name="dias_solicitados" onchange="actualizarCamposDiaEconomico()">
                                    <option value="1">1 día</option>
                                    <option value="2">2 días</option>
                                    <option value="3">3 días</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3" id="div_fecha_inicio_eco">
<label for="just_fecha_inicio_eco" class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha de Inicio
                                </label>
                                <input type="date" class="form-control" id="just_fecha_inicio_eco" name="fecha_inicio_eco" onchange="validarFechaInicioDiaEconomico()">
                                <div class="form-text text-danger" id="fecha_inicio_error" style="display: none;"></div>
                            </div>
                            <div class="col-md-4 mb-3" id="div_fecha_fin_eco" style="display: none;">
                                <label for="just_fecha_fin_eco" class="form-label fw-bold">
                                    <i class="fas fa-calendar-check me-1"></i>Fecha Fin
                                </label>
                                <input type="date" class="form-control" id="just_fecha_fin_eco" name="fecha_fin_eco" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Licencia Médica -->
                    <div class="mb-3" id="seccion-licencia-medica" style="display: none;">
                        <!-- Información de Antigüedad -->
                        <div id="info-antiguedad-licencia" class="alert alert-info mb-3" style="display: none;">
                            <!-- Se llenará dinámicamente con la antigüedad del empleado -->
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="just_folio_licencia" class="form-label fw-bold">
                                    <i class="fas fa-file-alt me-1"></i>No. de Folio
                                </label>
                                <input type="text" class="form-control" id="just_folio_licencia" name="folio_licencia" 
                                    placeholder="Número de folio de la licencia">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="just_dias_otorgados" class="form-label fw-bold">
                                    <i class="fas fa-calendar-check me-1"></i>Días Otorgados
                                </label>
                                <input type="number" class="form-control" id="just_dias_otorgados" name="dias_otorgados" 
                                    min="1" max="60" placeholder="Número de días" oninput="calcularFechaFinLicencia()">
                            </div>
                            <div class="col-md-4 mb-3">
<label for="just_fecha_inicio_licencia" class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha de Inicio
                                </label>
                                <input type="date" class="form-control" id="just_fecha_inicio_licencia" name="fecha_inicio_licencia" onchange="calcularFechaFinLicencia()">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="just_fecha_fin_licencia" class="form-label fw-bold">
                                    <i class="fas fa-calendar-check me-1"></i>Fecha Fin
                                </label>
                                <input type="date" class="form-control" id="just_fecha_fin_licencia" name="fecha_fin_licencia" readonly>
                                <small class="text-muted">Se calcula automáticamente</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="just_diagnostico" class="form-label fw-bold">
                                    <i class="fas fa-stethoscope me-1"></i>Diagnóstico
                                </label>
                                <input type="text" class="form-control" id="just_diagnostico" name="diagnostico" 
                                    placeholder="Describa el diagnóstico médico...">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-pantone-success" onclick="guardarJustificacion()">
                    <i class="fas fa-save me-1"></i> Guardar Justificación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Fundamentos Normativos -->
<div class="modal fade" id="modalFundamentos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-pantone-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-balance-scale me-2"></i>Fundamentos de Incidencias</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 1.05rem; line-height: 1.9;">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Nota:</strong> Estos fundamentos se usarán para justificar las incidencias según la normatividad aplicable.
                </div>

                <div class="mb-4 p-3 bg-light rounded">
                    <h6 class="text-pantone-primary fw-bold mb-3"><i class="fas fa-clock me-2"></i>RETARDO MENOR</h6>
                    <p class="mb-1"><strong>(Art. 37 y 80 Inciso a) del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.)</strong></p>
                    <p class="text-muted mb-0">Entre minuto 11 y minuto 20, después de su hora de entrada.</p>
                </div>

                <div class="mb-4 p-3 bg-light rounded">
                    <h6 class="text-pantone-primary fw-bold mb-3"><i class="fas fa-clock me-2"></i>RETARDO MAYOR</h6>
                    <p class="mb-1"><strong>(Art. 37 y 80 Inciso a) del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.)</strong></p>
                    <p class="text-muted mb-0">Entre minuto 21 y minuto 30, después de su hora de entrada.</p>
                </div>

                <div class="mb-4 p-3 bg-light rounded">
                    <h6 class="text-pantone-primary fw-bold mb-3"><i class="fas fa-coins me-2"></i>DÍAS ECONÓMICOS</h6>
                    <p class="mb-1"><strong>Criterios Operativos para el Otorgamiento de las Licencias con goce de sueldo previstas en el Art. 52 fracción III del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.</strong></p>
                    <p class="text-muted mb-0">Solicitarlos con un mínimo de un día hábiles de anticipación.</p>
                </div>

                <div class="mb-4 p-3 bg-light rounded">
                    <h6 class="text-pantone-primary fw-bold mb-3"><i class="fas fa-user-md me-2"></i>LIC. MÉDICA Y/O CONSTANCIA DE TIEMPO</h6>
                    <p class="mb-1"><strong>Art. 111 de la Ley Federal de los Trabajadores al Servicio del Estado y 52 del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.</strong></p>
                    <p class="text-muted mb-0">Documento expedido por el ISSSTE. Anexar Original.</p>
                </div>

                <div class="mb-4 p-3 bg-light rounded">
                    <h6 class="text-pantone-primary fw-bold mb-3"><i class="fas fa-umbrella-beach me-2"></i>VACACIONES</h6>
                    <p class="text-muted mb-0">Según calendario oficial y políticas internas.</p>
                </div>

                <div class="mb-4 p-3 bg-light rounded">
                    <h6 class="text-pantone-primary fw-bold mb-3"><i class="fas fa-baby me-2"></i>CUIDADOS MATERNOS Y/O PATERNOS</h6>
                    <p class="mb-1"><strong>Art 21.2 del Manual de Normas para la Administración de Recursos Humanos en la Secretaría de Educación Pública.</strong></p>
                    <p class="text-muted mb-0">Documento expedido por el ISSSTE en Original.</p>
                </div>

                <div class="mb-0 p-3 bg-light rounded">
                    <h6 class="text-pantone-primary fw-bold mb-3"><i class="fas fa-briefcase me-2"></i>COMISIÓN</h6>
                    <p class="mb-1"><strong>Especificar: Tipo, Lugar y Motivo, con base en el:</strong></p>
                    <p class="mb-1"><strong>ART. 2 - "DISPOSICIONES EN MATERIA DE CONTROL" - BASE 9</strong></p>
                    <p class="mb-1"><strong>NORMAS GENERALES, PRINCIPIOS Y ELEMENTOS DE CONTROL INTERNO, PUNTO 12 DEL MANUAL ADMINISTRATIVO DE APLICACIÓN GENERAL EN MATERIA DE CONTROL INTERNO.</strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-pantone-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-check me-1"></i> Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Empleado -->
<div class="modal fade" id="modalEditarEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Editar Empleado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarEmpleado" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_empleado_id">
                    <input type="hidden" name="_token" id="edit_csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Primer Apellido</label>
                            <input type="text" class="form-control" name="primer_apellido" id="edit_primer_apellido" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Segundo Apellido</label>
                            <input type="text" class="form-control" name="segundo_apellido" id="edit_segundo_apellido">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre(s)</label>
                            <input type="text" class="form-control" name="nombres" id="edit_nombres" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RFC</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="rfc" id="edit_rfc" required>
                                <button class="btn btn-pantone-secondary" type="button" id="edit_generarCodigosBtn">
                                    <i class="fas fa-magic"></i> Generar
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CURP</label>
                            <input type="text" class="form-control" name="curp" id="edit_curp">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sexo</label>
                            <select class="form-select" name="sexo" id="edit_sexo">
                                <option value="">Seleccionar...</option>
                                <option value="H">Hombre</option>
                                <option value="M">Mujer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" id="edit_fecha_nacimiento">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Entidad Federativa</label>
                            <select class="form-select" name="entidad_federativa" id="edit_entidad_federativa">
                                <option value="">Seleccionar...</option>
                                <option value="AS">Aguascalientes</option>
                                <option value="BC">Baja California</option>
                                <option value="BS">Baja California Sur</option>
                                <option value="CC">Campeche</option>
                                <option value="CL">Coahuila</option>
                                <option value="CM">Colima</option>
                                <option value="CS">Chiapas</option>
                                <option value="CH">Chihuahua</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="DG">Durango</option>
                                <option value="GT">Guanajuato</option>
                                <option value="GR">Guerrero</option>
                                <option value="HG">Hidalgo</option>
                                <option value="JC">Jalisco</option>
                                <option value="MC">México</option>
                                <option value="MN">Michoacán</option>
                                <option value="MS">Morelos</option>
                                <option value="NT">Nayarit</option>
                                <option value="NL">Nuevo León</option>
                                <option value="OC">Oaxaca</option>
                                <option value="PL">Puebla</option>
                                <option value="QT">Querétaro</option>
                                <option value="QR">Quintana Roo</option>
                                <option value="SP">San Luis Potosí</option>
                                <option value="SL">Sinaloa</option>
                                <option value="SR">Sonora</option>
                                <option value="TC">Tabasco</option>
                                <option value="TS">Tamaulipas</option>
                                <option value="TL">Tlaxcala</option>
                                <option value="VZ">Veracruz</option>
                                <option value="YN">Yucatán</option>
                                <option value="ZS">Zacatecas</option>
                                <option value="NE">Nacido en el Extranjero</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jefe Directo (Mando)</label>
                            <select class="form-select" name="jefe_directo_id" id="edit_jefe_directo_id">
                                <option value="">Seleccionar...</option>
                                <?php 
                                require_once __DIR__ . '/../../models/Catalogo.php';
                                $catalogo = new Catalogo();
                                $mandos = $catalogo->getAllMandos();
                                foreach ($mandos as $mando): 
                                ?>
                                <option value="<?= htmlspecialchars($mando['clave_area']) ?>" 
                                    data-area="<?= htmlspecialchars($mando['area']) ?>"
                                    data-clave="<?= htmlspecialchars($mando['clave_area']) ?>">
                                    <?= htmlspecialchars($mando['nombre_mando']) ?> - <?= htmlspecialchars($mando['area']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="clave_depto" id="edit_clave_depto_hidden">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Área</label>
                            <input type="text" class="form-control" name="area" id="edit_area" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Clave Área</label>
                            <input type="text" class="form-control" id="edit_clave_depto_display" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jerarquía</label>
                            <select class="form-select" name="jerarquia" id="edit_jerarquia">
                                <option value="Empleado">Empleado</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Gerente">Gerente</option>
                                <option value="Director">Director</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estatus</label>
                            <select class="form-select" name="activo" id="edit_activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto</label>
                            <input type="file" class="form-control" name="foto_cara" id="edit_foto_cara" accept="image/*">
                            <div class="mt-2 d-flex gap-3 align-items-center">
                                <div id="edit_foto-actual"></div>
                                <div id="edit_image-preview" style="display:none;">
                                    <img id="edit_preview-img" src="" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarEdicion" style="background-color: #9F2241; border-color: #9F2241;"><i class="fas fa-save me-1"></i> Actualizar</button>
            </div>
        </div>
    </div>
</div>

<script>

let tipoJustificacionSeleccionada = '';
let diasEconomicosDisponibles = 0;
let puedeSolicitarDiasEco = false;
let tipoJustificacionCatalogoId = null;
let tiposCatalogoOpciones = [];
let tipoRetardoActual = ''; // Variable global para guardar el tipo de retardo

function normalizarTextoTipo(valor) {
    return (valor || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function mostrarError(mensaje) {
    const modalHtml = `
        <div class="modal fade" id="modalError" tabindex="-1" aria-labelledby="modalErrorLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalErrorLabel">
                            <i class="fas fa-exclamation-circle me-2"></i>Error
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${mensaje}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const existingModal = document.getElementById('modalError');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('modalError'));
    modal.show();
    
    document.getElementById('modalError').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function mapearTipoCatalogo(tipoCatalogo, tipoIncidencia) {
    const tipoDb = normalizarTextoTipo(tipoCatalogo?.tipo_incidencia || '');
    const nombreTipo = normalizarTextoTipo(tipoCatalogo?.nombre || '');
    const filtroEspecifico = window.tipoRetardoFiltro;
    
    if (!tipoDb && !nombreTipo) {
        return '';
    }
    
    // Si hay un filtro específico (ya sea retardo_menor, retardo_mayor, comision_entrada, etc.)
    if (filtroEspecifico && filtroEspecifico !== 'excluir_retardos') {
        // Para retardos: solo mostrar el tipo específico de retardo
        if (filtroEspecifico === 'retardo_menor') {
            if (nombreTipo.includes('retardo menor')) return 'retardo_menor';
            return '';
        }
        if (filtroEspecifico === 'retardo_mayor') {
            if (nombreTipo.includes('retardo mayor')) return 'retardo_mayor';
            return '';
        }
        if (filtroEspecifico === 'tolerancia') {
            if (nombreTipo.includes('tolerancia')) return 'tolerancia';
            return '';
        }
        if (filtroEspecifico === 'falta') {
            if (nombreTipo.includes('falta')) return 'falta';
            return '';
        }
        // Para comisiones: solo mostrar el tipo específico
        if (filtroEspecifico === 'comision_entrada') {
            if (tipoDb === 'comision_entrada' || nombreTipo.includes('entrada')) return 'comision_entrada';
            return '';
        }
        if (filtroEspecifico === 'comision_salida') {
            if (tipoDb === 'comision_salida' || nombreTipo.includes('salida')) return 'comision_salida';
            return '';
        }
        if (filtroEspecifico === 'comision_todo_dia') {
            if (tipoDb === 'comision_todo_dia' || nombreTipo.includes('todo') || nombreTipo.includes('día')) return 'comision_todo_dia';
            return '';
        }
    }
    
    // Para retardo (sin filtro específico): mostrar todos los tipos de retardo
    if (tipoIncidencia === 'retardo' || tipoDb === 'retardo' || tipoDb === 'retardo_menor' || tipoDb === 'retardo_mayor') {
        if (tipoDb === 'retardo_menor' || tipoDb === 'retardo_mayor' || tipoDb === 'tolerancia' || tipoDb === 'falta' || tipoDb === 'retardo') {
            return tipoDb;
        }
        if (nombreTipo.includes('retardo menor')) return 'retardo_menor';
        if (nombreTipo.includes('retardo mayor')) return 'retardo_mayor';
        if (nombreTipo.includes('tolerancia')) return 'tolerancia';
        if (nombreTipo.includes('falta')) return 'falta';
        if (nombreTipo.includes('retardo')) return 'retardo';
        return '';
    }
    
    // Para incidencias por definir: mostrar todos los tipos disponibles EXCEPTO retardo
    if (tipoIncidencia === 'por_definir' || tipoIncidencia === 'comision_todo_dia' || tipoIncidencia === 'comision_entrada' || tipoIncidencia === 'comision_salida') {
        // Excluir tipos de retardo y falta para incidencias
        if (tipoDb === 'retardo' || tipoDb === 'retardo_menor' || tipoDb === 'retardo_mayor' || tipoDb === 'tolerancia' || tipoDb === 'falta') {
            return '';
        }
        return tipoDb;
    }
    
    // Para otros tipos (dia_economico, licencia_medica, vacaciones, cuidados_parentales)
    if (['dia_economico', 'licencia_medica', 'vacaciones', 'cuidados_parentales'].includes(tipoDb)) {
        return tipoDb;
    }
    
    // Para otros tipos de incidencias especiales
    if (['PDSEP-SNTE', 'CLIDDA', 'EYR', 'FUMIGACION', 'cuidados_maternos', 'cuidados_paternos', 'permiso_fallecimiento'].includes(tipoDb)) {
        return tipoDb;
    }
    
    // Para cualquier tipo que no sea retardo - mostrar en el catálogo
    if (tipoDb && tipoDb !== 'retardo' && tipoDb !== 'retardo_menor' && tipoDb !== 'retardo_mayor') {
        return tipoDb;
    }
    
    return '';
}

function construirOpcionesCatalogo(tiposCatalogo, tipoIncidencia) {
    const opciones = [];

    (tiposCatalogo || []).forEach(tipo => {
        const clave = mapearTipoCatalogo(tipo, tipoIncidencia);
        if (!clave) return;
        
        opciones.push({
            clave,
            tipo_id: tipo.id,
            nombre: tipo.nombre || clave,
            fundamento: (tipo.descripcion || '').toString().trim()
        });
    });

    return opciones;
}

function abrirModalJustificarIncidencia(asistenciaId, diasDisp = 0, puedeSolicitar = false, tipoIncidenciaOriginal = null, tipoAsistencia = null) {
    // Usar el tipo de asistencia del registro para determinar qué opciones mostrar
    // Si hay un tipo específico (comision_entrada, comision_salida, licencia_medica, etc.), usarlo como filtro
    if (tipoAsistencia) {
        window.tipoRetardoFiltro = tipoAsistencia;
    } else if (tipoIncidenciaOriginal) {
        window.tipoRetardoFiltro = tipoIncidenciaOriginal;
    } else {
        // Por defecto, excluimos retardos para incidencias - mostrar cualquier otro tipo
        window.tipoRetardoFiltro = 'excluir_retardos';
    }
    abrirModalJustificar(asistenciaId, diasDisp, puedeSolicitar, 'incidencia');
}

function abrirModalJustificarRetardo(retardoId, diasDisp = 0, puedeSolicitar = false, tipoRetardo = null) {
    console.log('abrirModalJustificarRetardo called with:', retardoId, 'tipoRetardo:', tipoRetardo);
    if (!retardoId) {
        alert('ID de retardo no válido');
        return;
    }
    // Pasar el tipo de retardo como filtro
    abrirModalJustificar(retardoId, diasDisp, puedeSolicitar, 'retardo', tipoRetardo);
}

function abrirModalJustificar(id, diasDisp, puedeSolicitar, origen, tipoRetardo = null) {
    console.log('abrirModalJustificar called with id:', id, 'origen:', origen, 'tipoRetardo:', tipoRetardo);
    
    // Guardar el tipo de retardo para usarlo en el filtro
    window.tipoRetardoFiltro = tipoRetardo;
    
    if (!id) {
        alert('ID no proporcionado');
        return;
    }
    
    diasEconomicosDisponibles = diasDisp;
    puedeSolicitarDiasEco = puedeSolicitar;
    
    const baseUrl = window.BASE_URL || '<?php echo defined("BASE_URL") ? rtrim(BASE_URL, "/") : "/sistema_biometrico"; ?>';
    const esRetardo = origen === 'retardo';
    
    const url = esRetardo 
        ? baseUrl + '/justificaciones/get-retardo/' + id
        : baseUrl + '/justificaciones/get-asistencia-ajax/' + id + '?origen=incidencia';
    
    console.log('Fetching URL:', url);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const asistencia = data.asistencia || data.retardo;
                
                // REMOVIDO: Validación que bloqueaba registros normales
                // Ahora siempre abre el modal para permitir ver/editar
                
                // Obtener el tipo de incidencia del servidor
                const tipoIncidenciaServer = data.tipo_incidencia || 'retardo';
                
                // Proteger acceso a elementos que pueden no existir
                const asistenciaIdEl = document.getElementById('asistencia_id_eco');
                const empleadoIdEl = document.getElementById('just_empleado_id');
                const fechaAsistenciaEl = document.getElementById('fecha_asistencia_eco');
                const tipoIncidenciaEl = document.getElementById('tipo_incidencia_original');
                const tipoOriginalEl = document.getElementById('just_tipo_original');
                
                if (asistenciaIdEl) asistenciaIdEl.value = asistencia.id;
                if (empleadoIdEl) empleadoIdEl.value = asistencia.empleado_id;
                if (fechaAsistenciaEl) fechaAsistenciaEl.value = asistencia.fecha;
                
                // Establecer retardo_id para el formulario (tanto para retardo como incidencia)
                const retardoIdEl = document.getElementById('just_retardo_id');
                if (retardoIdEl) {
                    retardoIdEl.value = asistencia.id;
                }
                
                // Establecer tipo_original
                if (tipoOriginalEl) {
                    tipoOriginalEl.value = origen; // 'retardo' o 'incidencia'
                }
                
                // Determinar el tipo de retardo del servidor
                let tipoRetardoServer = '';
                if (esRetardo) {
                    tipoRetardoServer = asistencia?.tipo || asistencia?.tipo_retraso || tipoIncidenciaServer;
                }
                
                // Guardar en variable global
                tipoRetardoActual = tipoRetardoServer;
                
                // Mostrar información del retardo si aplica
                if (esRetardo) {
                    const fechaDisplayEl = document.getElementById('just_fecha_display');
                    const horaDisplayEl = document.getElementById('just_hora_entrada_display');
                    const minutosDisplayEl = document.getElementById('just_minutos_display');
                    const tipoDisplayEl = document.getElementById('just_tipo_retardo_display');
                    
                    if (fechaDisplayEl) fechaDisplayEl.textContent = asistencia.fecha || '-';
                    if (horaDisplayEl) horaDisplayEl.textContent = asistencia.hora_entrada || '-';
                    if (minutosDisplayEl) minutosDisplayEl.textContent = (asistencia.minutos_retardo || asistencia.minutos || '0') + ' min';
                    if (tipoDisplayEl) {
                        let tipoLabel = tipoRetardoServer || 'No clasificado';
                        if (tipoLabel === 'tolerancia') tipoLabel = 'Tolerancia (0-10 min)';
                        else if (tipoLabel === 'retardo_menor') tipoLabel = 'Retardo Menor (11-20 min)';
                        else if (tipoLabel === 'retardo_mayor') tipoLabel = 'Retardo Mayor (21-30 min)';
                        else if (tipoLabel === 'falta') tipoLabel = 'Falta (31+ min)';
                        tipoDisplayEl.textContent = tipoLabel;
                    }
                    
                    // Actualizar el campo hidden de tipo_retardo_original
                    const tipoRetrasoOriginalInput = document.getElementById('just_tipo_retardo_original');
                    if (tipoRetrasoOriginalInput) {
                        tipoRetrasoOriginalInput.value = tipoRetardoServer;
                    }
                }
                
                const tipoIncidencia = tipoIncidenciaServer;
                
                if (tipoIncidenciaEl) tipoIncidenciaEl.value = tipoIncidencia;
                
                // Construir opciones desde catálogo de tipos de justificación
                const opcionesDiv = document.getElementById('opciones_justificacion');
                const datalist = document.getElementById('lista_tipos_justificacion');
                let opcionesHTML = '';
                let datalistHTML = '';
                const opcionesCatalogo = construirOpcionesCatalogo(data.tipos_justificacion || [], tipoIncidencia);
                tiposCatalogoOpciones = opcionesCatalogo;
                
                console.log('tipos_justificacion del servidor:', data.tipos_justificacion);
                console.log('tipoIncidencia:', tipoIncidencia);
                console.log('opcionesCatalogo:', opcionesCatalogo);

                opcionesCatalogo.forEach(op => {
                    let icono = 'fa-file-alt';
                    let ayuda = 'Tipo de justificación del catálogo';
                    let deshabilitado = '';
                    let claseLabel = '';

                    if (op.clave === 'retardo' || op.clave === 'retardo_menor' || op.clave === 'retardo_mayor') {
                        icono = 'fa-clock';
                        if (op.clave === 'retardo_menor') {
                            ayuda = 'Retardo menor (11-20 min)';
                        } else if (op.clave === 'retardo_mayor') {
                            ayuda = 'Retardo mayor (21-30 min)';
                        } else {
                            ayuda = 'Justificar retardo';
                        }
                    } else if (op.clave === 'falta') {
                        icono = 'fa-user-times';
                        ayuda = 'Falta (31+ min) - Sanción automática';
                    } else if (op.clave === 'tolerancia') {
                        icono = 'fa-check-circle';
                        ayuda = 'Tolerancia (0-10 min) - No se registra';
                    } else if (op.clave === 'comision_entrada') {
                        icono = 'fa-sign-in-alt';
                        ayuda = 'Justificar hora de entrada';
                    } else if (op.clave === 'comision_salida') {
                        icono = 'fa-sign-out-alt';
                        ayuda = 'Justificar hora de salida';
                    } else if (op.clave === 'comision_todo_dia') {
                        icono = 'fa-briefcase';
                        ayuda = 'Sin entrada ni salida';
                    } else if (op.clave === 'dia_economico') {
                        icono = 'fa-calendar-check';
                        ayuda = `${diasEconomicosDisponibles} días disponibles`;
                        if (!puedeSolicitarDiasEco || diasEconomicosDisponibles <= 0) {
                            deshabilitado = 'disabled';
                            claseLabel = 'text-muted';
                        }
                    } else if (op.clave === 'licencia_medica') {
                        icono = 'fa-user-md';
                        ayuda = 'Enfermedad o accidente';
                    } else if (op.clave === 'vacaciones') {
                        icono = 'fa-umbrella-beach';
                        ayuda = 'Solicitud de vacaciones';
                    } else if (op.clave === 'cuidados_parentales') {
                        icono = 'fa-baby';
                        ayuda = 'Cuidados maternos/paternos';
                    }

                    // Solo agregar al datalist para búsqueda (no generar tarjetas)
                    datalistHTML += `<option value="${op.nombre}" data-clave="${op.clave}" data-id="${op.tipo_id}"></option>`;
                    
                    // No generar opciones HTML en el grid
                    opcionesHTML = '';
                });
                
                if (datalist) datalist.innerHTML = datalistHTML;
                
                // Llenar el select con las opciones del catálogo
                const selectEl = document.getElementById('tipo_justificacion');
                const tipoAsistenciaRegistro = window.tipoRetardoFiltro || tipoIncidencia;
                console.log('tipoAsistenciaRegistro para pre-seleccionar:', tipoAsistenciaRegistro);
                
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">Seleccione...</option>';
                    
                    // SEGMENTAR OPCIONES SEGÚN TIPO DE REGISTRO
                    let opcionesFiltradas = [];
                    
                    if (esRetardo) {
                        // Para RETARDO: solo mostrar opciones de retardo
                        opcionesFiltradas = [
                            { clave: 'tolerancia', nombre: 'Tolerancia (0-10 min)' },
                            { clave: 'retardo_menor', nombre: 'Retardo menor (11-20 min)' },
                            { clave: 'retardo_mayor', nombre: 'Retardo mayor (21-30 min)' },
                            { clave: 'falta', nombre: 'Falta (31+ min)' }
                        ];
                    } else {
                        // Para INCIDENCIA: mostrar solo justificaciones (sin retardo)
                        opcionesFiltradas = [
                            { clave: 'comision_entrada', nombre: 'Comisión Entrada' },
                            { clave: 'comision_salida', nombre: 'Comisión Salida' },
                            { clave: 'comision_todo_dia', nombre: 'Comisión Día Completo' },
                            { clave: 'dia_economico', nombre: 'Día Económico' },
                            { clave: 'licencia_medica', nombre: 'Licencia Médica' },
                            { clave: 'vacaciones', nombre: 'Vacaciones' },
                            { clave: 'cuidados_parentales', nombre: 'Cuidados Parentales' }
                        ];
                        
                        // Si hay opciones del catálogo que no sean retardo, agregarlas
                        if (opcionesCatalogo && opcionesCatalogo.length > 0) {
                            opcionesCatalogo.forEach(op => {
                                // Excluir tipos de retardo para incidencias
                                if (!['retardo', 'retardo_menor', 'retardo_mayor', 'falta', 'tolerancia'].includes(op.clave)) {
                                    // Evitar duplicados
                                    if (!opcionesFiltradas.find(o => o.clave === op.clave)) {
                                        opcionesFiltradas.push({ clave: op.clave, nombre: op.nombre });
                                    }
                                }
                            });
                        }
                    }
                    
                    // Agregar las opciones al select
                    opcionesFiltradas.forEach(op => {
                        const option = document.createElement('option');
                        option.value = op.clave;
                        option.textContent = op.nombre;
                        option.dataset.tipoId = op.tipo_id;
                        selectEl.appendChild(option);
                    });
                    
                    // Solo para INCIDENCIA: permitir cambio de tipo
                    if (!esRetardo) {
                        selectEl.disabled = false;
                    } else {
                        selectEl.disabled = true;
                    }
                        
                        // Seleccionar el tipo automáticamente según el tipo de incidencia
                    // PRIORIDAD: 1) tipoAsistenciaRegistro (del clic en tabla), 2) tipo_asistencia del servidor (si ya tiene justificación), 3) tipoIncidencia, 4) hora_entrada/salida
                    let tipoAAutomatico = window.tipoRetardoFiltro || asistencia.tipo_asistencia || tipoIncidencia;
                    
                    console.log('tipoAAutomatico calculado:', tipoAAutomatico, 'from window.tipoRetardoFiltro:', window.tipoRetardoFiltro);
                    
                    // Para INCIDENCIAS: determinar tipo según horas (sin considerar retardo)
                    // Solo comisiones y justificaciones - NO retardos
                    if (!esRetardo) {
                        const horaEntrada = asistencia.hora_entrada || '';
                        const horaSalida = asistencia.hora_salida || '';
                        const tieneJustificacion = asistencia.tipo_justificacion_id;
                        
                        // Si ya tiene justificación, usar el tipo del servidor para poder editarlo
                        if (tieneJustificacion && asistencia.tipo_asistencia) {
                            tipoAAutomatico = asistencia.tipo_asistencia;
                            console.log('Tipo existente del servidor:', tipoAAutomatico);
                        } else {
                            // Si NO tiene justificación, calcular según horas
                            if (!horaEntrada && !horaSalida) {
                                tipoAAutomatico = 'comision_todo_dia';
                            } else if (horaEntrada && !horaSalida) {
                                tipoAAutomatico = 'comision_salida';
                            } else if (!horaEntrada && horaSalida) {
                                tipoAAutomatico = 'comision_entrada';
                            } else if (horaEntrada && horaSalida) {
                                // Si tiene ambas horas - es registro normal, no abrir modal
                                // Esto ya se maneja en la validación al inicio
                                tipoAAutomatico = 'normal';
                            }
                            console.log('Tipo determinado para incidencia:', tipoAAutomatico, 'horaEntrada:', horaEntrada, 'horaSalida:', horaSalida);
                        }
                    }
                    
                    // Para RETARDOS: determinar tipo según minutos de retardo
                    if (esRetardo) {
                        const minutosRetardo = parseInt(asistencia.minutos_retardo || asistencia.minutos || 0);
                        
                        // Usar el tipo del servidor si es válido, si no calcular según minutos
                        if (tipoRetardoServer && tipoRetardoServer !== 'null' && tipoRetardoServer !== '') {
                            tipoAAutomatico = tipoRetardoServer;
                        } else {
                            // Calcular tipo según minutos de retardo
                            if (minutosRetardo <= 0) {
                                tipoAAutomatico = 'tolerancia';
                            } else if (minutosRetardo >= 1 && minutosRetardo <= 10) {
                                tipoAAutomatico = 'tolerancia';
                            } else if (minutosRetardo >= 11 && minutosRetardo <= 20) {
                                tipoAAutomatico = 'retardo_menor';
                            } else if (minutosRetardo >= 21 && minutosRetardo <= 30) {
                                tipoAAutomatico = 'retardo_mayor';
                            } else {
                                // 31 minutos o más
                                tipoAAutomatico = 'falta';
                            }
                            console.log('Tipo de retardo calculado por minutos:', tipoAAutomatico, 'minutos:', minutosRetardo);
                        }
                        
                        // Guardar en variable global para usar en fundamentos
                        tipoRetardoActual = tipoAAutomatico;
                        
                        // Actualizar el campo hidden de tipo_retardo_original
                        const tipoRetrasoOriginalInput = document.getElementById('just_tipo_retardo_original');
                        if (tipoRetrasoOriginalInput) {
                            tipoRetrasoOriginalInput.value = tipoAAutomatico;
                        }
                        
                        // Para retardos, hacer el dropdown de solo lectura (no permitir cambio)
                        selectEl.disabled = true;
                    } else {
                        selectEl.disabled = false;
                    }
                    
                    // Buscar si el tipo existe en las opciones
                    let tipoEncontrado = false;
                    if (tipoAAutomatico) {
                        for (let i = 0; i < selectEl.options.length; i++) {
                            if (selectEl.options[i].value === tipoAAutomatico) {
                                selectEl.selectedIndex = i;
                                tipoEncontrado = true;
                                break;
                            }
                        }
                    }
                    
                    // Si no se encontró, intentar encontrar un tipo similar
                    if (!tipoEncontrado && selectEl.options.length > 1) {
                        // Para retardo, buscar retardo_menor o retardo_mayor primero
                        for (let i = 0; i < selectEl.options.length; i++) {
                            const optVal = selectEl.options[i].value;
                            if (tipoAAutomatico === 'retardo_menor' && optVal === 'retardo_menor') {
                                selectEl.selectedIndex = i;
                                tipoEncontrado = true;
                                break;
                            }
                            if (tipoAAutomatico === 'retardo_mayor' && optVal === 'retardo_mayor') {
                                selectEl.selectedIndex = i;
                                tipoEncontrado = true;
                                break;
                            }
                            if (tipoAAutomatico === 'tolerancia' && optVal === 'tolerancia') {
                                selectEl.selectedIndex = i;
                                tipoEncontrado = true;
                                break;
                            }
                            if (tipoAAutomatico === 'falta' && optVal === 'falta') {
                                selectEl.selectedIndex = i;
                                tipoEncontrado = true;
                                break;
                            }
                        }
                    }
                    
                    // Si aún no se encontró, seleccionar la primera opción válida
                    if (!tipoEncontrado && selectEl.options.length > 1) {
                        selectEl.selectedIndex = 1; // Saltar "Seleccione..."
                    }
                    
                    // Forzar la actualización de la UI
                    setTimeout(() => {
                        const tipoSeleccionado = selectEl.value;
                        if (tipoSeleccionado) {
                            // Establecer las variables globales
                            tipoJustificacionSeleccionada = tipoSeleccionado;
                            const selectedOpt = selectEl.options[selectEl.selectedIndex];
                            tipoJustificacionCatalogoId = selectedOpt?.dataset?.tipoId || null;
                            
                            // Establecer fecha automáticamente según el tipo de justificación usando la fecha del registro
                            const fechaRegistro = asistencia.fecha || '';
                            
                            // Comisiones
                            if (tipoSeleccionado === 'comision_entrada' || tipoSeleccionado === 'comision_salida' || tipoSeleccionado === 'comision_todo_dia') {
                                document.getElementById('just_fecha_inicio_comision').value = fechaRegistro;
                            }
                            // Día económico
                            else if (tipoSeleccionado === 'dia_economico') {
                                document.getElementById('just_fecha_inicio_eco').value = fechaRegistro;
                            }
                            // Licencias y cuidados
                            else if (tipoSeleccionado.includes('licencia') || tipoSeleccionado.includes('cuidados')) {
                                document.getElementById('just_fecha_inicio_licencia').value = fechaRegistro;
                            }
                            // Otros tipos: usar campo de comisión como defaults
                            else if (fechaRegistro) {
                                document.getElementById('just_fecha_inicio_comision').value = fechaRegistro;
                            }
                            
                            // Llamar a la función para actualizar la UI
                            if (typeof cambiarTipoJustificacion === 'function') {
                                cambiarTipoJustificacion();
                            }
                        }
                    }, 200);
                }
                
                // Ocultar las tarjetas de opciones y solo dejar el dropdown
                if (opcionesDiv) {
                    opcionesDiv.classList.add('d-none');
                    opcionesDiv.innerHTML = '';
                }
                
                // No generar opciones HTML en el grid
                opcionesHTML = '';
                
                // Agregar evento para buscar en el input
                const busquedaInput = document.getElementById('busqueda_tipo_justificacion');
                if (busquedaInput) {
                    busquedaInput.addEventListener('input', function() {
                        const valor = this.value.toLowerCase();
                        if (!datalist) return;
                        const opciones = datalist.querySelectorAll('option');
                        let encontrado = null;
                        
                        opciones.forEach(opt => {
                            if (opt.value.toLowerCase() === valor) {
                                encontrado = {
                                    clave: opt.dataset.clave,
                                    id: opt.dataset.id
                                };
                            }
                        });
                        
                        if (encontrado) {
                            seleccionarJustificacion(encontrado.clave, parseInt(encontrado.id));
                        }
                    });
                    
                    busquedaInput.addEventListener('change', function() {
                        const valor = this.value.toLowerCase();
                        if (!datalist) return;
                        const opciones = datalist.querySelectorAll('option');
                        let encontrado = null;
                        
                        opciones.forEach(opt => {
                            if (opt.value.toLowerCase().includes(valor)) {
                                encontrado = {
                                    clave: opt.dataset.clave,
                                    id: opt.dataset.id,
                                    nombre: opt.value
                                };
                            }
                        });
                        
                        if (encontrado && valor.length > 0) {
                            this.value = encontrado.nombre;
                            seleccionarJustificacion(encontrado.clave, parseInt(encontrado.id));
                        }
                    });
                } // ends if (busquedaInput)
                
                // Resetear campos - proteger elementos que pueden no existir
                const busquedaInputEl = document.getElementById('busqueda_tipo_justificacion');
                const tipoSelEl = document.getElementById('tipo_justificacion_seleccionado');
                const btnGuardarEl = document.getElementById('btnGuardarJustificacion');
                const tipoCatalogoIdEl = document.getElementById('tipo_justificacion_catalogo_id');
                const fundamentoEl = document.getElementById('fundamento_tipo_seleccionado');
                
                if (busquedaInputEl) busquedaInputEl.value = '';
                if (tipoSelEl) tipoSelEl.value = '';
                
                const camposDiaEco = document.getElementById('campos_dia_economico');
                const camposLicencia = document.getElementById('campos_licencia_medica');
                const camposComision = document.getElementById('campos_comision');
                const validacionResult = document.getElementById('validacion_resultado');
                
                if (camposDiaEco) camposDiaEco.classList.add('d-none');
                if (camposLicencia) camposLicencia.classList.add('d-none');
                if (camposComision) camposComision.classList.add('d-none');
                if (validacionResult) validacionResult.className = 'alert d-none';
                if (btnGuardarEl) btnGuardarEl.disabled = true;
                
                // Limpiar radios seleccionados
                document.querySelectorAll('input[name="tipo_justificacion"]').forEach(r => r.checked = false);
                if (tipoCatalogoIdEl) tipoCatalogoIdEl.value = '';
                if (fundamentoEl) fundamentoEl.value = '';
                tipoJustificacionCatalogoId = null;
                
                // Establecer fecha mínima para día económico
                const manana = new Date();
                manana.setDate(manana.getDate() + 1);
                const fechaInput = document.getElementById('fecha_dia_economico');
                if (fechaInput) fechaInput.min = manana.toISOString().split('T')[0];
                
                // Limpiar mensaje de error anterior
                const tipoIncidenciaDiv = document.getElementById('tipo_incidencia_seleccionado');
                if (tipoIncidenciaDiv) {
                    tipoIncidenciaDiv.classList.add('d-none');
                    tipoIncidenciaDiv.className = 'alert alert-primary d-none';
                }
                
                const modalEl = document.getElementById('modalJustificacion');
                if (modalEl) {
                    // Ocultar backdrop del modal de empleado temporalmente
                    const empleadoModalBackdrop = document.querySelector('.modal-backdrop');
                    if (empleadoModalBackdrop) {
                        empleadoModalBackdrop.style.display = 'none';
                    }
                    
                    modalEl.style.zIndex = '1070';
                    const modal = new bootstrap.Modal(modalEl, {
                        backdrop: true,
                        keyboard: true
                    });
                    modal.show();
                    
                    // Restaurar backdrop del modal de empleado al cerrar justificación
                    modalEl.addEventListener('hidden.bs.modal', function() {
                        if (empleadoModalBackdrop) {
                            empleadoModalBackdrop.style.display = '';
                        }
                    });
                }
                
                // Llamar a cambiarTipoJustificacion para mostrar los campos correctos según el tipo
                if (tipoJustificacionSeleccionada) {
                    setTimeout(() => {
                        cambiarTipoJustificacion();
                    }, 100);
                }
            } else {
                mostrarError('Error al cargar datos: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al obtener datos de la asistencia');
        });
}

function seleccionarJustificacion(tipo, tipoId = null) {
    tipoJustificacionSeleccionada = tipo;
    tipoJustificacionCatalogoId = tipoId;
    const inputTipoId = document.getElementById('tipo_justificacion_catalogo_id');
    if (inputTipoId) {
        inputTipoId.value = tipoId || '';
    }
    
    // Actualizar el select
    const selectEl = document.getElementById('tipo_justificacion');
    if (selectEl) {
        selectEl.value = tipo;
    }
    
    // Actualizar radios si existen
    const radioInput = document.querySelector(`input[value="${tipo}"]`);
    if (radioInput) radioInput.checked = true;
    
    // Ocultar todos los campos - proteger elementos que pueden no existir
    const cDiaEco = document.getElementById('campos_dia_economico');
    const cLicencia = document.getElementById('campos_licencia_medica');
    const cComision = document.getElementById('campos_comision');
    if (cDiaEco) cDiaEco.classList.add('d-none');
    if (cLicencia) cLicencia.classList.add('d-none');
    if (cComision) cComision.classList.add('d-none');
    
    // Reiniciar atributos required por tipo
    const campos = [
        'dias_solicitados', 'fecha_dia_economico', 'motivo_dia_economico',
        'folio_licencia', 'dias_licencia', 'fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia',
        'lugar_comision', 'motivo_comision', 'tipo_cuidados'
    ];
    campos.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.removeAttribute('required');
    });
    
    // Mostrar campos según tipo
    const esRetardoTipo = tipo === 'retardo' || tipo === 'retardo_menor' || tipo === 'retardo_mayor' || tipo === 'tolerancia' || tipo === 'falta';
    
    // Mostrar/ocultar sección de datos del retardo
    const seccionDatosRetardo = document.getElementById('seccion-datos-retardo');
    if (seccionDatosRetardo) {
        if (esRetardoTipo) {
            seccionDatosRetardo.classList.remove('d-none');
        } else {
            seccionDatosRetardo.classList.add('d-none');
        }
    }
    
    if (tipo === 'dia_economico') {
        const cDiaEcoMostrar = document.getElementById('campos_dia_economico');
        if (cDiaEcoMostrar) cDiaEcoMostrar.classList.remove('d-none');
        ['dias_solicitados', 'fecha_dia_economico', 'motivo_dia_economico'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.setAttribute('required', 'required');
        });
    } else if (tipo === 'licencia_medica' || tipo === 'vacaciones' || tipo === 'cuidados_parentales') {
        const cLicMostrar = document.getElementById('campos_licencia_medica');
        if (cLicMostrar) cLicMostrar.classList.remove('d-none');
        const wrapFolio = document.getElementById('wrap_folio_licencia');
        const wrapDias = document.getElementById('wrap_dias_licencia');
        const wrapCuidados = document.getElementById('wrap_tipo_cuidados');
        const lblDiag = document.querySelector('label[for="diagnostico_licencia"], #diagnostico_licencia')?.closest('.col-md-6')?.querySelector('label');
        if (tipo === 'licencia_medica') {
            if (wrapFolio) wrapFolio.classList.remove('d-none');
            if (wrapDias) wrapDias.classList.remove('d-none');
            if (wrapCuidados) wrapCuidados.classList.add('d-none');
            if (lblDiag) lblDiag.textContent = 'Diagnóstico *';
            ['folio_licencia', 'dias_licencia', 'fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.setAttribute('required', 'required');
            });
            
            // Mostrar información de antigüedad para licencia médica
            mostrarAntiguedadLicencia();
        } else if (tipo === 'vacaciones') {
            if (wrapFolio) wrapFolio.classList.add('d-none');
            if (wrapDias) wrapDias.classList.add('d-none');
            if (wrapCuidados) wrapCuidados.classList.add('d-none');
            if (lblDiag) lblDiag.textContent = 'Motivo *';
            ['fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.setAttribute('required', 'required');
            });
        } else {
            if (wrapFolio) wrapFolio.classList.add('d-none');
            if (wrapDias) wrapDias.classList.add('d-none');
            if (wrapCuidados) wrapCuidados.classList.remove('d-none');
            if (lblDiag) lblDiag.textContent = 'Motivo *';
            ['tipo_cuidados', 'fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.setAttribute('required', 'required');
            });
        }
    } else if (tipo.startsWith('comision')) {
        const wrapFolio = document.getElementById('wrap_folio_licencia');
        const wrapDias = document.getElementById('wrap_dias_licencia');
        const wrapCuidados = document.getElementById('wrap_tipo_cuidados');
        if (wrapFolio) wrapFolio.classList.remove('d-none');
        if (wrapDias) wrapDias.classList.remove('d-none');
        if (wrapCuidados) wrapCuidados.classList.add('d-none');
        ['folio_licencia', 'dias_licencia', 'fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.removeAttribute('required');
        });
        const cComisionMostrar = document.getElementById('campos_comision');
        if (cComisionMostrar) cComisionMostrar.classList.remove('d-none');
        ['lugar_comision', 'motivo_comision'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.setAttribute('required', 'required');
        });
    }

    actualizarFundamentoPorTipo(tipo, tipoId);
    actualizarDatosRequeridos(tipo);
    
    validarJustificacion();
}

function obtenerTipoCatalogoSeleccionado(tipo, tipoId) {
    if (tipoId) {
        const porId = (tiposCatalogoOpciones || []).find(t => Number(t.tipo_id) === Number(tipoId));
        if (porId) return porId;
    }
    return (tiposCatalogoOpciones || []).find(t => t.clave === tipo) || null;
}

function actualizarFundamentoPorTipo(tipo, tipoId) {
    const tipoCatalogo = obtenerTipoCatalogoSeleccionado(tipo, tipoId);
    const fundamento = (tipoCatalogo?.fundamento || '').trim();
    
    const fundamentoEl = document.getElementById('fundamento_tipo_seleccionado');
    if (fundamentoEl) fundamentoEl.value = fundamento;

    if (tipo.startsWith('comision') && fundamento) {
        const campo = document.getElementById('fundamento_comision');
        if (campo) campo.value = fundamento;
    }
    if (tipo === 'licencia_medica' && fundamento) {
        const campo = document.getElementById('fundamento_licencia');
        if (campo) campo.value = fundamento;
    }
}

function actualizarDatosRequeridos(tipo) {
    const box = document.getElementById('datos_requeridos_tipo');
    if (!box) return;

    let titulo = '';
    let items = [];
    if (tipo === 'dia_economico') {
        titulo = 'Datos requeridos para Día Económico';
        items = ['Días solicitados', 'Fecha', 'Motivo'];
    } else if (tipo === 'licencia_medica') {
        titulo = 'Datos requeridos para Licencia Médica';
        items = ['Folio de licencia', 'Días otorgados', 'Fecha inicio', 'Fecha fin', 'Diagnóstico'];
    } else if (tipo === 'vacaciones') {
        titulo = 'Datos requeridos para Vacaciones';
        items = ['Fecha inicio', 'Fecha fin', 'Motivo'];
    } else if (tipo === 'cuidados_parentales') {
        titulo = 'Datos requeridos para Cuidados Maternos/Paternos';
        items = ['Tipo de cuidado (materno/paterno)', 'Fecha inicio', 'Fecha fin', 'Motivo'];
    } else if (tipo.startsWith('comision')) {
        titulo = 'Datos requeridos para Comisión';
        items = ['Lugar de la comisión', 'Motivo de la comisión'];
    } else {
        box.className = 'alert alert-secondary d-none';
        box.innerHTML = '';
        return;
    }

    const tipoCatalogo = obtenerTipoCatalogoSeleccionado(tipo, tipoJustificacionCatalogoId);
    const fundamento = (tipoCatalogo?.fundamento || '').trim();

    box.className = 'alert alert-secondary';
    box.innerHTML = `<strong>${titulo}</strong><br>${items.join(' | ')}`;
    if (fundamento) {
        box.innerHTML += `<hr class="my-2"><strong>Fundamento:</strong><br>${fundamento}`;
    }
}

function validarJustificacion() {
    const resultado = document.getElementById('validacion_resultado');
    const btnGuardar = document.getElementById('btnGuardarJustificacion');
    
    if (!resultado || !btnGuardar) return;
    
    let valido = true;
    let errores = [];
    
    if (!tipoJustificacionSeleccionada) {
        valido = false;
    } else if (tipoJustificacionSeleccionada === 'dia_economico') {
        const diasEl = document.getElementById('dias_solicitados');
        const fechaEl = document.getElementById('fecha_dia_economico');
        const motivoEl = document.getElementById('motivo_dia_economico');
        
        const dias = diasEl?.value || '';
        const fecha = fechaEl?.value || '';
        const motivo = motivoEl?.value || '';
        
        if (!dias || !fecha || !motivo) {
            valido = false;
            errores.push('Complete todos los campos');
        } else {
            const fechaObj = new Date(fecha);
            const diaSemana = fechaObj.getDay();
            if (diaSemana === 5) errores.push('No se puede solicitar en viernes');
            if (diaSemana === 1) errores.push('No se puede solicitar en lunes');
        }
    } else if (tipoJustificacionSeleccionada === 'licencia_medica') {
        const folioEl = document.getElementById('folio_licencia');
        const diasEl = document.getElementById('dias_licencia');
        const fechaIniEl = document.getElementById('fecha_inicio_licencia');
        const fechaFinEl = document.getElementById('fecha_fin_licencia');
        const diagEl = document.getElementById('diagnostico_licencia');
        
        const folio = folioEl?.value || '';
        const dias = diasEl?.value || '';
        const fechaIni = fechaIniEl?.value || '';
        const fechaFin = fechaFinEl?.value || '';
        const diagnostico = diagEl?.value || '';
        
        if (!folio || !dias || !fechaIni || !fechaFin || !diagnostico) {
            valido = false;
            errores.push('Complete todos los campos de la licencia médica');
        }
    } else if (tipoJustificacionSeleccionada === 'vacaciones') {
        const fechaIniEl = document.getElementById('fecha_inicio_licencia');
        const fechaFinEl = document.getElementById('fecha_fin_licencia');
        const motivoEl = document.getElementById('diagnostico_licencia');
        
        const fechaIni = fechaIniEl?.value || '';
        const fechaFin = fechaFinEl?.value || '';
        const motivo = motivoEl?.value || '';
        
        if (!fechaIni || !fechaFin || !motivo) {
            valido = false;
            errores.push('Complete fecha inicio, fecha fin y motivo de vacaciones');
        }
    } else if (tipoJustificacionSeleccionada === 'cuidados_parentales') {
        const tipoEl = document.getElementById('tipo_cuidados');
        const fechaIniEl = document.getElementById('fecha_inicio_licencia');
        const fechaFinEl = document.getElementById('fecha_fin_licencia');
        const motivoEl = document.getElementById('diagnostico_licencia');
        
        const tipoCuidados = tipoEl?.value || '';
        const fechaIni = fechaIniEl?.value || '';
        const fechaFin = fechaFinEl?.value || '';
        const motivo = motivoEl?.value || '';
        
        if (!tipoCuidados || !fechaIni || !fechaFin || !motivo) {
            valido = false;
            errores.push('Complete tipo de cuidado, fechas y motivo');
        }
    } else if (tipoJustificacionSeleccionada.startsWith('comision')) {
        const lugarEl = document.getElementById('lugar_comision');
        const motivoEl = document.getElementById('motivo_comision');
        
        const lugar = lugarEl?.value || '';
        const motivo = motivoEl?.value || '';
        
        if (!lugar || !motivo) {
            valido = false;
            errores.push('Complete lugar y motivo de la comisión');
        }
    }
    
    if (errores.length > 0) {
        resultado.className = 'alert alert-danger';
        resultado.innerHTML = errores.join('<br>');
    } else {
        resultado.className = 'alert d-none';
    }
    
    btnGuardar.disabled = !valido;
}

function guardarJustificacionIncidencia() {
    const formData = new FormData();
    formData.append('asistencia_id', document.getElementById('asistencia_id_eco').value);
    formData.append('empleado_id', document.getElementById('empleado_id_eco').value);
    formData.append('tipo_justificacion', tipoJustificacionSeleccionada);
    if (tipoJustificacionCatalogoId) {
        formData.append('tipo_justificacion_id', tipoJustificacionCatalogoId);
    }
    const fundamentoTipo = document.getElementById('fundamento_tipo_seleccionado')?.value || '';
    if (fundamentoTipo) {
        formData.append('fundamento_tipo', fundamentoTipo);
    }
    
    if (tipoJustificacionSeleccionada === 'dia_economico') {
        formData.append('fecha', document.getElementById('fecha_dia_economico').value);
        formData.append('dias_solicitados', document.getElementById('dias_solicitados').value);
        formData.append('motivo', document.getElementById('motivo_dia_economico').value);
    } else if (tipoJustificacionSeleccionada === 'licencia_medica' || tipoJustificacionSeleccionada === 'vacaciones' || tipoJustificacionSeleccionada === 'cuidados_parentales') {
        const diasLicencia = parseInt(document.getElementById('dias_licencia').value) || 0;
        const empleadoId = document.getElementById('empleado_id_eco').value;
        
        // Validar días disponibles para licencia médica
        if (tipoJustificacionSeleccionada === 'licencia_medica' && diasLicencia > 0) {
            fetch(`<?= BASE_URL ?>/empleados/datos-completos/${empleadoId}`)
                .then(res => res.json())
                .then(data => {
                    const ctrl = data.control_licencias || {};
                    const fullTotal = ctrl.full || 0;
                    const halfTotal = ctrl.half || 0;
                    const fullUsados = ctrl.full_usados || 0;
                    const halfUsados = ctrl.half_usados || 0;
                    const fullRestantes = Math.max(0, fullTotal - fullUsados);
                    const halfRestantes = Math.max(0, halfTotal - halfUsados);
                    const totalRestantes = fullRestantes + halfRestantes;
                    
                    let mensaje = '';
                    if (diasLicencia > totalRestantes) {
                        mensaje = `⚠️ ADVERTENCIA: Está solicitando ${diasLicencia} días pero solo tiene ${totalRestantes} días disponibles.\n\n` +
                            `• Sueldo íntegro: ${fullRestantes} días\n` +
                            `• Medio sueldo: ${halfRestantes} días\n\n` +
                            `¿Desea continuar?`;
                    } else if (diasLicencia > fullRestantes) {
                        const diasMedio = diasLicencia - fullRestantes;
                        mensaje = `ℹ️ NOTA: De los ${diasLicencia} días solicitados:\n\n` +
                            `• ${fullRestantes} día(s) con SUELDO ÍNTEGRO\n` +
                            `• ${diasMedio} día(s) con MEDIO SUELDO\n\n` +
                            `¿Desea continuar?`;
                    } else {
                        mensaje = `✅ Se registrarán ${diasLicencia} días con SUELDO ÍNTEGRO.\n\n¿Desea continuar?`;
                    }
                    
                    if (!confirm(mensaje)) {
                        return;
                    }
                    guardarIncidenciaConValidacion();
                })
                .catch(e => {
                    console.error('Error:', e);
                    guardarIncidenciaConValidacion();
                });
            return;
        }
        
        function guardarIncidenciaConValidacion() {
            formData.append('folio_licencia', document.getElementById('folio_licencia').value);
            formData.append('dias_otorgados', document.getElementById('dias_licencia').value);
            formData.append('fecha_inicio_licencia', document.getElementById('fecha_inicio_licencia').value);
            formData.append('fecha_fin_licencia', document.getElementById('fecha_fin_licencia').value);
            formData.append('diagnostico', document.getElementById('diagnostico_licencia').value);
            formData.append('tipo_cuidados', document.getElementById('tipo_cuidados')?.value || '');
            formData.append('fundamento_legal', document.getElementById('fundamento_licencia').value);
            
            fetch('<?php echo rtrim(BASE_URL, '/'); ?>/justificaciones/guardar-incidencia', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Incidencia justificada correctamente');
                    const modalEl = document.getElementById('modalJustificacion');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    window.location.reload();
                } else {
                    mostrarError('Error: ' + (data.error || 'No se pudo guardar'));
                }
            })
            .catch(error => {
                mostrarError('Error de conexión: ' + error.message);
            });
        }
    } else if (tipoJustificacionSeleccionada.startsWith('comision')) {
        formData.append('lugar_comision', document.getElementById('just_lugar').value);
        formData.append('motivo_comision', document.getElementById('just_motivo').value);
        
        const fundamentoEl = document.getElementById('fundamento_tipo_seleccionado');
        if (fundamentoEl && fundamentoEl.value) {
            formData.append('fundamento_tipo', fundamentoEl.value);
        }
        
        const fechaInicio = document.getElementById('just_fecha_inicio_comision').value;
        const fechaFin = document.getElementById('just_fecha_fin_comision').value;
        if (fechaInicio) formData.append('fecha_inicio_comision', fechaInicio);
        if (fechaFin) formData.append('fecha_fin_comision', fechaFin);
    }
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/justificaciones/guardar-incidencia', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Incidencia justificada correctamente');
            const modalEl = document.getElementById('modalJustificacion');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            window.location.reload();
        } else {
            mostrarError('Error: ' + (data.error || 'No se pudo guardar'));
        }
    })
    .catch(error => {
        mostrarError('Error de conexión: ' + error.message);
    });
}

function formatDate(dateStr) {

    if (!dateStr) return '-';
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
}

function mostrarToast(mensaje, tipo = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container);
    }
    
    const bgClass = tipo === 'success' ? 'bg-success' : tipo === 'error' ? 'bg-danger' : 'bg-info';
    const iconClass = tipo === 'success' ? 'fa-check-circle' : tipo === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white ${bgClass} border-0 mb-2`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${iconClass} me-2"></i>${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// Funciones de validación de fechas para filtros de asistencia
function validarFechaInicio(input, fechaFinId) {
    if (!input.value) return;
    
    const fechaInicio = new Date(input.value);
    const fechaFinInput = document.getElementById(fechaFinId);
    
    if (fechaFinInput && fechaFinInput.value) {
        const fin = new Date(fechaFinInput.value);
        if (fechaInicio > fin) {
            // Si inicio > fin, ajustar fin al último día del mes de inicio
            const ultimoDiaMes = new Date(fechaInicio.getFullYear(), fechaInicio.getMonth() + 1, 0);
            fechaFinInput.value = ultimoDiaMes.toISOString().split('T')[0];
        }
    }
}

function validarFechaFin(input, fechaInicioId) {
    if (!input.value) return;
    
    const fechaFin = new Date(input.value);
    const year = fechaFin.getFullYear();
    const month = fechaFin.getMonth();
    
    // Obtener el último día del mes
    const ultimoDiaMes = new Date(year, month + 1, 0).getDate();
    const diaIngresado = fechaFin.getDate();
    
    // Si el día ingresado es mayor que el último día del mes, corregir
    if (diaIngresado > ultimoDiaMes) {
        input.value = new Date(year, month, ultimoDiaMes).toISOString().split('T')[0];
        console.log('Fecha corregida al último día del mes:', input.value);
    }
    
    // También validar que fecha fin no sea menor a fecha inicio
    const fechaInicioInput = document.getElementById(fechaInicioId);
    if (fechaInicioInput && fechaInicioInput.value) {
        const inicio = new Date(fechaInicioInput.value);
        if (fechaFin < inicio) {
            // Si fin < inicio, ajustar fin al último día del mes de inicio
            const ultimoDiaMesInicio = new Date(inicio.getFullYear(), inicio.getMonth() + 1, 0);
            input.value = ultimoDiaMesInicio.toISOString().split('T')[0];
        }
    }
}

async function verEmpleado(id) {
    window.empleadoId = id;
    window.empleadoIdGlobal = id;
    const esAdmin = <?php echo $esAdmin ? 'true' : 'false'; ?>;
    
    // Verificar que existe el modal
    const modalEl = document.getElementById('empleadoModal');
    if (!modalEl) {
        // Si no existe el modal, redirigir a la página de detalle del empleado
        window.location.href = `<?= BASE_URL ?>/empleados/show/${id}`;
        return;
    }
    
    const bootstrapModal = new bootstrap.Modal(modalEl);
    bootstrapModal.show();
    
    const modalNombreEl = document.getElementById('modalEmpleadoNombre');
    const generalEl = document.getElementById('general');
    const asistenciaEl = document.getElementById('asistencia');
    const retardosEl = document.getElementById('retardos');
    const comisionesEl = document.getElementById('comisiones');
    const incidenciasEl = document.getElementById('tabla-incidencias');
    const vacacionesEl = document.getElementById('vacaciones');
    const licenciasEl = document.getElementById('licenciasmedicas');
    const cuidadosMatEl = document.getElementById('cuidados-maternos');
    const cuidadosPatEl = document.getElementById('cuidados-paternos');
    const constanciasEl = document.getElementById('constancias');
    const diasEcoEl = document.getElementById('diaseconomicos');
    const licenciasMedicasEl = document.getElementById('licenciasmedicas');
    const sancionesEl = document.getElementById('sanciones');
    const ciclosEl = document.getElementById('ciclos');
    
    // Mostrar spinner de carga en cada pestaña
    if (generalEl) generalEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (asistenciaEl) asistenciaEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (retardosEl) retardosEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (comisionesEl) comisionesEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (incidenciasEl) incidenciasEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (vacacionesEl) vacacionesEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (licenciasEl) licenciasEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (cuidadosMatEl) cuidadosMatEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (cuidadosPatEl) cuidadosPatEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (constanciasEl) constanciasEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (diasEcoEl) diasEcoEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (licenciasMedicasEl) licenciasMedicasEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    if (sancionesEl) sancionesEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    const ciclosContentEl = document.getElementById('ciclos-content');
    if (ciclosContentEl) ciclosContentEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    
    try {
        const response = await fetch(`<?= BASE_URL ?>/empleados/datos-completos/${id}`);
        
        // Verificar si la respuesta es JSON válida
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('La respuesta no es JSON. Content-Type:', contentType);
            const text = await response.text();
            console.error('Respuesta del servidor:', text.substring(0, 500));
            if (generalEl) generalEl.innerHTML = '<div class="alert alert-danger">Error: El servidor devolvió una respuesta no válida</div>';
            return;
        }
        
        const data = await response.json();
        
        console.log('Datos recibidos del servidor:', data);

        if (data.error) {
            if (generalEl) generalEl.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            return;
        }

        // Debug: mostrar datos recibidos
        console.log('Vacaciones:', data.vacaciones);
        console.log('Licencias:', data.licencias_medicas);
        console.log('Cuidados Maternos:', data.cuidados_maternos);
        console.log('Cuidados Paternos:', data.cuidados_paternos);
        console.log('Constancias:', data.constancias_tiempo);
        console.log('Plazas:', data.plazas);
        console.log('Plazas Horas Sumadas:', data.plazas_horas_sumadas);

        // Colores Pantone
        const PANTONE = {
            primary: '#9F2241',
            primaryDark: '#691C32',
            primaryLight: '#b84a6b',
            secondary: '#235B4E',
            secondaryDark: '#10312B',
            secondaryLight: '#3a7d6a',
            accent: '#DDC9A3',
            accentDark: '#BC955C',
            danger: '#691C32',
            dangerLight: '#a32a47',
            success: '#235B4E',
            successLight: '#3a7d6a',
            warning: '#BC955C',
            warningLight: '#d4b07a',
            gray: '#98989A',
            grayDark: '#6F7271',
            grayLight: '#d0d0d2',
            bgLight: '#f8f7f5',
            textPrimary: '#2d2d2d',
            textSecondary: '#98989A',
            textMuted: '#6F7271',
            border: '#d0d0d2'
        };

        if(modalNombreEl) modalNombreEl.textContent = `${data.nombre} ${data.apellido}`;
        const btnEditar = document.getElementById('btnEditarEmpleado');
        if(btnEditar) btnEditar.href = `<?= BASE_URL ?>/empleados/edit/${id}`;
        
        // Render General tab
        if(generalEl) {
            const statusColor = data.activo ? PANTONE.success : PANTONE.danger;
            const statusBg = data.activo ? PANTONE.success + '15' : PANTONE.danger + '15';
            let jefeHtml = data.jefe_directo ? `<span style="color: ${PANTONE.secondary}; font-weight: 500;">${data.jefe_directo.nombre_mando}</span>` : '<span class="text-muted">No asignado</span>';
            
            generalEl.innerHTML = `
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <div class="position-relative d-inline-block">
                            ${data.foto_cara ? 
                                `<img src="<?= BASE_URL ?>/${data.foto_cara}" class="img-fluid rounded-circle shadow-sm" style="max-width: 150px; border: 4px solid ${PANTONE.primary}20;">` : 
                                `<div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; background: linear-gradient(135deg, ${PANTONE.primary}20, ${PANTONE.secondary}20);">
                                    <i class="fas fa-user" style="font-size: 4rem; color: ${PANTONE.gray};"></i>
                                </div>`
                            }
                        </div>
                        <div class="mt-3">
                            <span class="badge" style="background-color: ${statusColor}; color: white; padding: 8px 20px; font-size: 0.9rem; border-radius: 20px;">
                                <i class="fas ${data.activo ? 'fa-check-circle' : 'fa-times-circle'} me-1"></i>${data.activo ? 'Activo' : 'Inactivo'}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h4 class="mb-1" style="color: ${PANTONE.primaryDark}; font-weight: 600;">${data.nombre} ${data.apellido}</h4>
                        <p class="mb-3" style="color: ${PANTONE.grayDark};">
                            <i class="fas fa-building me-2" style="color: ${PANTONE.secondary};"></i>${data.area || 'Sin área'} 
                            <span class="mx-2">|</span> 
                            <i class="fas fa-sitemap me-2" style="color: ${PANTONE.secondary};"></i>${data.jerarquia || 'Sin jerarquía'}
                        </p>
                        
                        <div class="row g-3">
                            <div class="col-6 col-md-4">
                                <div class="p-3 rounded" style="background-color: ${PANTONE.bgLight}; border-left: 3px solid ${PANTONE.primary};">
                                    <small class="d-block" style="color: ${PANTONE.gray}; font-size: 0.7rem; text-transform: uppercase;">RFC</small>
                                    <span style="color: ${PANTONE.textPrimary}; font-weight: 500;">${data.rfc || '-'}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-3 rounded" style="background-color: ${PANTONE.bgLight}; border-left: 3px solid ${PANTONE.secondary};">
                                    <small class="d-block" style="color: ${PANTONE.gray}; font-size: 0.7rem; text-transform: uppercase;">CURP</small>
                                    <span style="color: ${PANTONE.textPrimary}; font-weight: 500;">${data.curp || '-'}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-3 rounded" style="background-color: ${PANTONE.bgLight}; border-left: 3px solid ${PANTONE.accentDark};">
                                    <small class="d-block" style="color: ${PANTONE.gray}; font-size: 0.7rem; text-transform: uppercase;">Jefe Directo</small>
                                    <span style="color: ${PANTONE.textPrimary};">${jefeHtml}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-3 rounded" style="background-color: ${PANTONE.primary}10;">
                                    <small class="d-block" style="color: ${PANTONE.primary}; font-size: 0.7rem; text-transform: uppercase;">Fecha de Ingreso</small>
                                    <span style="color: ${PANTONE.primaryDark}; font-weight: 500;">${data.fecha_ingreso ? (() => { const parts = data.fecha_ingreso.split('-'); return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : '-'; })() : '-'}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-3 rounded" style="background-color: ${PANTONE.secondary}10;">
                                    <small class="d-block" style="color: ${PANTONE.secondary}; font-size: 0.7rem; text-transform: uppercase;">ID Empleado</small>
                                    <span style="color: ${PANTONE.secondaryDark}; font-weight: 600;">#${data.id}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-3 rounded" style="background-color: ${data.activo ? PANTONE.success : PANTONE.danger}10;">
                                    <small class="d-block" style="color: ${data.activo ? PANTONE.success : PANTONE.danger}; font-size: 0.7rem; text-transform: uppercase;">Estado</small>
                                    <span style="color: ${data.activo ? PANTONE.successDark : PANTONE.danger}; font-weight: 600;">${data.activo ? 'Activo' : 'Inactivo'}</span>
                                </div>
                            </div>
                        </div>
                        
                                ${(data.plazas && data.plazas.filter(p => p.HORAS && p.HORAS > 0).length > 0) ? `
                        <div class="mt-4">
                            <h6 class="mb-3" style="color: ${PANTONE.primaryDark}; font-weight: 600;">
                                <i class="fas fa-briefcase me-2" style="color: ${PANTONE.primary};"></i>Plazas
                                <span class="badge ms-2" style="background-color: ${PANTONE.primary}15; color: ${PANTONE.primary};">${data.plazas.filter(p => p.HORAS && p.HORAS > 0).length} plaza(s)</span>
                                ${data.plazas_horas_semanales > 0 ? `<span class="badge ms-1" style="background-color: ${PANTONE.secondary}15; color: ${PANTONE.secondary};">${parseFloat(data.plazas_horas_semanales).toFixed(2)} hrs/sem</span>` : ''}
                                ${data.plazas_horas_mensuales > 0 ? `<span class="badge ms-1" style="background-color: ${PANTONE.warning}15; color: ${PANTONE.warning};">${Math.round(data.plazas_horas_mensuales * 100) / 100} hrs/mes</span>` : ''}
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden; font-size: 0.85rem;">
                                    <thead style="background-color: ${PANTONE.primary}; color: white;">
                                        <tr>
                                            <th style="padding: 10px;">Plaza</th>
                                            <th style="padding: 10px;">Hrs/Sem</th>
                                            <th style="padding: 10px;">Hrs/Mes</th>
                                            <th style="padding: 10px;">Puesto</th>
                                            <th style="padding: 10px;">Categoría</th>
                                            <th style="padding: 10px;">Nivel</th>
                                            <th style="padding: 10px;">CCT</th>
                                            <th style="padding: 10px;">Clasificación</th>
                                            <th style="padding: 10px;">Movimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.plazas.filter(p => p.HORAS && p.HORAS > 0).map(plaza => `
                                        <tr>
                                            <td style="padding: 10px;">${plaza.PLAZA || '-'}</td>
                                            <td style="padding: 10px; font-weight: 600;">${plaza.HORAS ? parseFloat(plaza.HORAS).toFixed(2) : '-'}</td>
                                            <td style="padding: 10px; font-weight: 600;">${plaza.HORAS_MENSUALES ? plaza.HORAS_MENSUALES.toFixed(2) : '-'}</td>
                                            <td style="padding: 10px;">${plaza.puesto || '-'}</td>
                                            <td style="padding: 10px;">${plaza.cat_puesto || '-'}</td>
                                            <td style="padding: 10px;">${plaza.niv_puesto || '-'}</td>
                                            <td style="padding: 10px;">${plaza.CCT || '-'}</td>
                                            <td style="padding: 10px;">${plaza.Clasificacion || '-'}</td>
                                            <td style="padding: 10px;">${plaza.mot_mov || '-'}</td>
                                        </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        // Render Asistencia tab
        if(asistenciaEl) {
            const recientes = data.asistencias ? data.asistencias.slice(0, 15) : [];
            let asisHtml = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0" style="color: ${PANTONE.secondaryDark};">
                        <i class="fas fa-calendar-check me-2" style="color: ${PANTONE.secondary};"></i>Registros de Asistencia
                    </h6>
                    <span class="badge" style="background-color: ${PANTONE.primary}15; color: ${PANTONE.primary};">${recientes.length} registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: ${PANTONE.secondary}; color: white;">
                            <tr>
                                <th style="padding: 12px;"><i class="fas fa-calendar-alt me-1"></i>Fecha</th>
                                <th style="padding: 12px;"><i class="fas fa-sign-in-alt me-1"></i>Entrada</th>
                                <th style="padding: 12px;"><i class="fas fa-sign-out-alt me-1"></i>Salida</th>
                                <th style="padding: 12px;"><i class="fas fa-tag me-1"></i>Tipo</th>
                                <th style="padding: 12px;"><i class="fas fa-user-check me-1"></i>Validación Jefe</th>
                            </tr>
                        </thead>
                        <tbody>`;
            if(recientes.length > 0) {
                recientes.forEach(a => {
                    const tipoColor = a.tipo_asistencia === 'normal' ? PANTONE.secondary : PANTONE.warning;
                    
                    // Determinar estado de validación
                    let validacionHtml = '-';
                    if (a.requiere_validacion_jefe == 1 || a.requerio_validacion == 1) {
                        if (a.validado_por_jefe == 1 || a.estado_validacion === 'aprobada') {
                            // Validada - mostrar fecha
                            const fechaValidacion = a.fecha_aprobacion || a.fecha_validacion || '-';
                            validacionHtml = `<span class="badge" style="background-color: ${PANTONE.success}15; color: ${PANTONE.success};"><i class="fas fa-check me-1"></i>${fechaValidacion}</span>`;
                        } else if (a.estado_validacion === 'rechazada') {
                            validacionHtml = `<span class="badge" style="background-color: ${PANTONE.danger}15; color: ${PANTONE.danger};"><i class="fas fa-times me-1"></i>Rechazada</span>`;
                        } else {
                            validacionHtml = `<span class="badge" style="background-color: ${PANTONE.warning}15; color: ${PANTONE.warning};"><i class="fas fa-clock me-1"></i>Pendiente</span>`;
                        }
                    } else {
                        validacionHtml = `<span class="badge" style="background-color: ${PANTONE.gray}15; color: ${PANTONE.gray};"><i class="fas fa-minus me-1"></i>No requiere</span>`;
                    }
                    
                    asisHtml += `
                        <tr style="border-bottom: 1px solid ${PANTONE.border}50;">
                            <td style="padding: 12px;"><strong>${a.fecha}</strong></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${PANTONE.primary}15; color: ${PANTONE.primaryDark};">${a.hora_entrada || '-'}</span></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${PANTONE.danger}15; color: ${PANTONE.danger};">${a.hora_salida || '-'}</span></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${tipoColor}15; color: ${tipoColor};">${a.tipo_asistencia || 'Normal'}</span></td>
                            <td style="padding: 12px;">${validacionHtml}</td>
                        </tr>`;
                });
            } else {
                asisHtml += `
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; color: ${PANTONE.gray};"></i>
                            <p class="mt-2 mb-0" style="color: ${PANTONE.grayDark};">Sin registros de asistencia</p>
                        </td>
                    </tr>`;
            }
            asistenciaEl.innerHTML = asisHtml + '</tbody></table></div>';
        }

        // Render Retardos tab
        if(retardosEl) {
            const retardosData = (data.retardos || []).sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
            let retHtml = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0" style="color: ${PANTONE.warningDark || PANTONE.warning};">
                            <i class="fas fa-clock me-2" style="color: ${PANTONE.warning};"></i>Historial de Retardos
                        </h6>
                        <button class="btn btn-sm" onclick="mostrarFundamentoRetardos()" title="Ver fundamento legal" style="background-color: ${PANTONE.warning}; color: white; border: none; padding: 4px 8px; font-size: 0.75rem;">
                            <i class="fas fa-balance-scale me-1"></i> Fundamento Legal
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge" style="background-color: ${PANTONE.success}15; color: ${PANTONE.success};">${retardosData.filter(r => r.justificado).length} Justificados</span>
                        <span class="badge" style="background-color: ${PANTONE.warning}15; color: ${PANTONE.warning};">${retardosData.filter(r => !r.justificado).length} Pendientes</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: ${PANTONE.warning}; color: ${PANTONE.textPrimary};">
                            <tr>
                                <th style="padding: 12px;"><i class="fas fa-calendar-alt me-1"></i>Fecha</th>
                                <th style="padding: 12px;"><i class="fas fa-sign-in-alt me-1"></i>Hora Entrada</th>
                                <th style="padding: 12px;"><i class="fas fa-stopwatch me-1"></i>Minutos Retraso</th>
                                <th style="padding: 12px;"><i class="fas fa-sort-amount-up me-1"></i>Tipo</th>
                                <th style="padding: 12px;"><i class="fas fa-check-circle me-1"></i>Estado</th>
                                <th style="padding: 12px;"><i class="fas fa-cog me-1"></i>Acción</th>
                            </tr>
                        </thead>
                        <tbody>`;
            if(retardosData.length > 0) {
                console.log('Retardos data sample:', retardosData[0]);
                retardosData.forEach(r => {
                    let tipoLabel = r.tipo || 'No clasificado';
                    let tipoColor = PANTONE.gray;
                    if (tipoLabel === 'tolerancia') { tipoLabel = 'Tolerancia (0-10 min)'; tipoColor = PANTONE.success; }
                    else if (tipoLabel === 'retardo_menor') { tipoLabel = 'Menor (11-20 min)'; tipoColor = PANTONE.warning; }
                    else if (tipoLabel === 'retardo_mayor') { tipoLabel = 'Mayor (21-30 min)'; tipoColor = PANTONE.danger; }
                    else if (tipoLabel === 'falta') { tipoLabel = 'Falta (31+ min)'; tipoColor = PANTONE.danger; }
                    
                    const estadoColor = r.justificado ? PANTONE.success : PANTONE.warning;
                    const estadoBg = r.justificado ? PANTONE.success + '15' : PANTONE.warning + '15';
                    const horaEntrada = r.hora_entrada ? r.hora_entrada.substring(0,5) : '--';
                    const minutos = r.minutos_retardo || r.minutos || 0;
                    
                    retHtml += `
                        <tr style="border-bottom: 1px solid ${PANTONE.border}50;">
                            <td style="padding: 12px;"><strong>${r.fecha || '-'}</strong></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${PANTONE.success}15; color: ${PANTONE.success};">${horaEntrada}</span></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${tipoColor}15; color: ${tipoColor}; font-size: 0.85rem;">${minutos} min</span></td>
                            <td style="padding: 12px;"><span style="color: ${tipoColor}; font-weight: 500;">${tipoLabel}</span></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${estadoBg}; color: ${estadoColor};">${r.justificado ? 'Justificado' : 'Pendiente'}</span></td>
                            <td style="padding: 12px;">
                                ${!r.justificado ? `<button class="btn btn-sm" style="background-color: ${PANTONE.primary}; color: white; border: none;" onclick="abrirModalJustificarRetardo(${r.id})"><i class="fas fa-edit me-1"></i>Justificar</button>` : '<span class="text-muted"><i class="fas fa-check"></i></span>'}
                            </td>
                        </tr>`;
                });
            } else {
                retHtml += `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-check-circle" style="font-size: 2rem; color: ${PANTONE.success};"></i>
                            <p class="mt-2 mb-0" style="color: ${PANTONE.grayDark};">Sin retardos registrados</p>
                        </td>
                    </tr>`;
            }
            retardosEl.innerHTML = retHtml + '</tbody></table></div>';
        }

        // Render Comisiones Reales tab (from tabla comisiones)
        if (comisionesEl) {
            const comisionesRealesData = (data.comisiones_reales || []).sort((a, b) => new Date(b.fecha_inicio || 0) - new Date(a.fecha_inicio || 0));
            let comHtml = '';
            
            if (comisionesRealesData.length > 0) {
                comHtml += `
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: #9F2241; color: white;">
                            <tr>
                                <th style="padding: 10px;"><i class="fas fa-calendar-alt me-1"></i>Inicio</th>
                                <th style="padding: 10px;"><i class="fas fa-calendar-check me-1"></i>Fin</th>
                                <th style="padding: 10px;"><i class="fas fa-align-left me-1"></i>Descripción</th>
                                <th style="padding: 10px;"><i class="fas fa-toggle-on me-1"></i>Estatus</th>
                                <th style="padding: 10px;"><i class="fas fa-user-check me-1"></i>Revisor</th>
                                <th style="padding: 10px; width: 60px;"><i class="fas fa-tools me-1"></i></th>
                            </tr>
                        </thead>
                        <tbody>`;
                comisionesRealesData.forEach(c => {
                    const fechaInicio = c.fecha_inicio ? new Date(c.fecha_inicio).toLocaleDateString('es-MX') : '-';
                    const fechaFin = c.fecha_fin ? new Date(c.fecha_fin).toLocaleDateString('es-MX') : '-';
                    const descripcion = c.descripcion || 'Sin descripción';
                    const estatus = c.estatus || 'pendiente';
                    const aprobador = c.nombre_aprobador || '-';
                    
                    let estatusBadge = '';
                    if (estatus === 'aprobada' || estatus === 'aprobado') {
                        estatusBadge = `<span class="badge" style="background-color: ${PANTONE.success}20; color: ${PANTONE.success};"><i class="fas fa-check me-1"></i>Aprobada</span>`;
                    } else if (estatus === 'rechazada' || estatus === 'rechazado') {
                        estatusBadge = `<span class="badge" style="background-color: ${PANTONE.danger}20; color: ${PANTONE.danger};"><i class="fas fa-times me-1"></i>Rechazada</span>`;
                    } else {
                        estatusBadge = `<span class="badge" style="background-color: ${PANTONE.warning}20; color: ${PANTONE.warning};"><i class="fas fa-clock me-1"></i>Pendiente</span>`;
                    }
                    
                    comHtml += `
                        <tr style="border-bottom: 1px solid ${PANTONE.border}50;">
                            <td style="padding: 10px;"><strong>${fechaInicio}</strong></td>
                            <td style="padding: 10px;"><strong>${fechaFin}</strong></td>
                            <td style="padding: 10px;"><small>${descripcion.substring(0, 50)}${descripcion.length > 50 ? '...' : ''}</small></td>
                            <td style="padding: 10px;">${estatusBadge}</td>
                            <td style="padding: 10px;"><small>${aprobador}</small></td>
                            <td style="padding: 10px; text-align: center;">
                                ${esAdmin ? `<button class="btn btn-sm btn-outline-primary" onclick='editarComision(${JSON.stringify(c).replace(/'/g, "\\'")})' title="Editar comisión"><i class="fas fa-edit"></i></button>` : ''}
                            </td>
                        </tr>`;
                });
                comHtml += '</tbody></table></div>';
            } else {
                comHtml += `
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-check" style="font-size: 2rem; color: ${PANTONE.gray};"></i>
                    <p class="mt-2 mb-0" style="color: ${PANTONE.grayDark};">No hay comisiones registradas</p>
                </div>`;
            }
            comisionesEl.innerHTML = comHtml;
        }

        // Render Incidencias/Justificaciones tab (from asistencia table with tipo_justificacion_id)
        if (incidenciasEl) {
            // CEJILLA INCIDENCIAS: mostrar por_definir + registros con justificación
            let incidenciasData = [];
            if (data.asistencias && Array.isArray(data.asistencias)) {
                incidenciasData = data.asistencias.filter(a => 
                    (a.tipo_asistencia === 'por_definir' || a.tipo_justificacion_id) &&
                    a.tipo_asistencia !== 'con_retardo'
                );
            }
            
            let justificacionesData = incidenciasData.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
            
            if (typeof justificacionesData === 'string') {
                const trimmed = justificacionesData.trim();
                if (!trimmed.startsWith('[') && !trimmed.startsWith('{')) {
                    justificacionesData = [];
                }
            }
            
            let esValido = Array.isArray(justificacionesData) && justificacionesData.length > 0;
            if (!esValido) {
                justificacionesData = [];
            }
            
            let incHtml = '';
            
            if (justificacionesData.length > 0 && typeof justificacionesData[0] === 'object') {
                justificacionesData.forEach(j => {
                    if (!j || typeof j !== 'object') return;
                    
                    const tipoJustificacion = j.tipo_justificacion_nombre || j.tipo_incidencia || 'Sin tipo';
                    const tipoAsistencia = j.tipo_asistencia || 'por_definir';
                    const horaEntrada = j.hora_entrada || '-';
                    const horaSalida = j.hora_salida || '-';
                    const estado = j.estado_validacion || 'pendiente';
                    const estadoLabel = estado === 'aprobada' || estado === 'aprobado' ? 'Aprobada' : 
                                       estado === 'rechazada' || estado === 'rechazado' ? 'Rechazada' : 'Pendiente';
                    const estadoColor = estado === 'aprobada' || estado === 'aprobado' ? PANTONE.success : 
                                       estado === 'rechazada' || estado === 'rechazado' ? PANTONE.danger : PANTONE.warning;
                    
                    incHtml += `
                        <tr>
                            <td><strong>${j.fecha || '-'}</strong></td>
                            <td><span class="badge bg-secondary">${horaEntrada}</span></td>
                            <td><span class="badge bg-secondary">${horaSalida}</span></td>
                            <td><span class="badge bg-info">${tipoAsistencia}</span></td>
                            <td><span class="badge bg-primary">${tipoJustificacion}</span></td>
                            <td><span class="badge" style="background-color: ${estadoColor}20; color: ${estadoColor};">${estadoLabel}</span></td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificarIncidencia(${j.id}, 0, false, null, '${j.tipo_asistencia}')"><i class="fas fa-eye"></i> Ver</button></td>
                        </tr>`;
                });
            } else {
                incHtml = ` <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-inbox" style="font-size: 2rem; color: ${PANTONE.gray};"></i>
                            <p class="mt-2 mb-0" style="color: ${PANTONE.grayDark};">Sin justificaciones registradas</p>
                        </td>
                    </tr>`;
            }
            
            // Insertar siempre en el contenedor de incidencias
            if (incidenciasEl) {
                incidenciasEl.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Tipo Asistencia</th><th>Tipo Justificación</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>${incHtml}</tbody>
                        </table>
                    </div>`;
            }
        }

        // Render Vacaciones tab
        if (vacacionesEl) {
            const vacacionesData = (data.vacaciones || []).sort((a, b) => new Date(b.fecha_inicio || 0) - new Date(a.fecha_inicio || 0));
            let vacHtml = `
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: ${PANTONE.primary}; color: white;">
                            <tr><th>Inicio</th><th>Fin</th><th>Días</th><th>Motivo</th><th>Estado</th><th style="width:50px;"></th></tr>
                        </thead>
                        <tbody>`;
            if (vacacionesData.length > 0) {
                vacacionesData.forEach(v => {
                    const estado = v.estatus || 'pendiente';
                    const estadoLabel = estado === 'aprobada' ? 'Aprobada' : estado === 'rechazada' ? 'Rechazada' : 'Pendiente';
                    const estadoColor = estado === 'aprobada' ? PANTONE.success : estado === 'rechazada' ? PANTONE.danger : PANTONE.warning;
                    vacHtml += `<tr><td>${v.fecha_inicio || '-'}</td><td>${v.fecha_fin || '-'}</td><td>${v.dias_solicitados || 0}</td><td>${v.motivo || '-'}</td><td><span class="badge" style="background-color: ${estadoColor}20; color: ${estadoColor};">${estadoLabel}</span></td><td>${esAdmin ? `<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick='editarRegistroTab(${JSON.stringify(v).replace(/'/g, "\\'")}, "vacaciones")' title="Editar"><i class="fas fa-edit"></i></button>` : ''}</td></tr>`;
                });
            } else {
                vacHtml += '<tr><td colspan="6" class="text-center py-4 text-muted">Sin vacaciones registradas</td></tr>';
            }
            vacacionesEl.innerHTML = vacHtml + '</tbody></table></div>';
        }

        // Render Licencias Médicas tab (already exists, using data.licencias_medicas)
        if (licenciasEl) {
            const licenciasData = (data.licencias_medicas || []).sort((a, b) => new Date(b.fecha_inicio || 0) - new Date(a.fecha_inicio || 0));
            let licHtml = `
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: ${PANTONE.secondary}; color: white;">
                            <tr><th>Inicio</th><th>Fin</th><th>Días</th><th>Diagnóstico</th><th>Estado</th><th style="width:50px;"></th></tr>
                        </thead>
                        <tbody>`;
            if (licenciasData.length > 0) {
                licenciasData.forEach(l => {
                    const estado = l.estatus || 'pendiente';
                    const estadoLabel = estado === 'aprobada' ? 'Aprobada' : estado === 'rechazada' ? 'Rechazada' : 'Pendiente';
                    const estadoColor = estado === 'aprobada' ? PANTONE.success : estado === 'rechazada' ? PANTONE.danger : PANTONE.warning;
                    licHtml += `<tr><td>${l.fecha_inicio || '-'}</td><td>${l.fecha_fin || '-'}</td><td>${l.dias_otorgados || 0}</td><td>${l.diagnostico || '-'}</td><td><span class="badge" style="background-color: ${estadoColor}20; color: ${estadoColor};">${estadoLabel}</span></td><td>${esAdmin ? `<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick='editarRegistroTab(${JSON.stringify(l).replace(/'/g, "\\'")}, "licencias_medicas")' title="Editar"><i class="fas fa-edit"></i></button>` : ''}</td></tr>`;
                });
            } else {
                licHtml += '<tr><td colspan="6" class="text-center py-4 text-muted">Sin licencias médicas</td></tr>';
            }
            licenciasEl.innerHTML = licHtml + '</tbody></table></div>';
        }

        // Render Cuidados Maternos tab
        if (cuidadosMatEl) {
            const cuidadosMatData = (data.cuidados_maternos || []).sort((a, b) => new Date(b.fecha_inicio || 0) - new Date(a.fecha_inicio || 0));
            let cmHtml = `
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: ${PANTONE.accent}; color: ${PANTONE.textPrimary};">
                            <tr><th>Inicio</th><th>Fin</th><th>Días</th><th>Motivo</th><th>Estado</th><th style="width:50px;"></th></tr>
                        </thead>
                        <tbody>`;
            if (cuidadosMatData.length > 0) {
                cuidadosMatData.forEach(c => {
                    const estado = c.estatus || 'pendiente';
                    const estadoLabel = estado === 'aprobada' ? 'Aprobada' : estado === 'rechazada' ? 'Rechazada' : 'Pendiente';
                    const estadoColor = estado === 'aprobada' ? PANTONE.success : estado === 'rechazada' ? PANTONE.danger : PANTONE.warning;
                    cmHtml += `<tr><td>${c.fecha_inicio || '-'}</td><td>${c.fecha_fin || '-'}</td><td>${c.dias_solicitados || 0}</td><td>${c.motivo || '-'}</td><td><span class="badge" style="background-color: ${estadoColor}20; color: ${estadoColor};">${estadoLabel}</span></td><td>${esAdmin ? `<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick='editarRegistroTab(${JSON.stringify(c).replace(/'/g, "\\'")}, "cuidados_maternos")' title="Editar"><i class="fas fa-edit"></i></button>` : ''}</td></tr>`;
                });
            } else {
                cmHtml += '<tr><td colspan="6" class="text-center py-4 text-muted">Sin cuidados maternos</td></tr>';
            }
            cuidadosMatEl.innerHTML = cmHtml + '</tbody></table></div>';
        }

        // Render Cuidados Paternos tab
        if (cuidadosPatEl) {
            const cuidadosPatData = (data.cuidados_paternos || []).sort((a, b) => new Date(b.fecha_inicio || 0) - new Date(a.fecha_inicio || 0));
            let cpHtml = `
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: ${PANTONE.accent}; color: ${PANTONE.textPrimary};">
                            <tr><th>Inicio</th><th>Fin</th><th>Días</th><th>Motivo</th><th>Estado</th><th style="width:50px;"></th></tr>
                        </thead>
                        <tbody>`;
            if (cuidadosPatData.length > 0) {
                cuidadosPatData.forEach(c => {
                    const estado = c.estatus || 'pendiente';
                    const estadoLabel = estado === 'aprobada' ? 'Aprobada' : estado === 'rechazada' ? 'Rechazada' : 'Pendiente';
                    const estadoColor = estado === 'aprobada' ? PANTONE.success : estado === 'rechazada' ? PANTONE.danger : PANTONE.warning;
                    cpHtml += `<tr><td>${c.fecha_inicio || '-'}</td><td>${c.fecha_fin || '-'}</td><td>${c.dias_solicitados || 0}</td><td>${c.motivo || '-'}</td><td><span class="badge" style="background-color: ${estadoColor}20; color: ${estadoColor};">${estadoLabel}</span></td><td>${esAdmin ? `<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick='editarRegistroTab(${JSON.stringify(c).replace(/'/g, "\\'")}, "cuidados_paternos")' title="Editar"><i class="fas fa-edit"></i></button>` : ''}</td></tr>`;
                });
            } else {
                cpHtml += '<tr><td colspan="6" class="text-center py-4 text-muted">Sin cuidados paternos</td></tr>';
            }
            cuidadosPatEl.innerHTML = cpHtml + '</tbody></table></div>';
        }

        // Render Constancias tab
        if (constanciasEl) {
            const constanciasData = data.constancias_tiempo || [];
            let ctHtml = `
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: ${PANTONE.gray}; color: white;">
                            <tr><th>Inicio</th><th>Fin</th><th>Días</th><th>Tipo</th><th>Estado</th><th style="width:50px;"></th></tr>
                        </thead>
                        <tbody>`;
            if (constanciasData.length > 0) {
                constanciasData.forEach(c => {
                    const estado = c.estatus || 'pendiente';
                    const estadoLabel = estado === 'aprobada' ? 'Aprobada' : estado === 'rechazada' ? 'Rechazada' : 'Pendiente';
                    const estadoColor = estado === 'aprobada' ? PANTONE.success : estado === 'rechazada' ? PANTONE.danger : PANTONE.warning;
                    ctHtml += `<tr><td>${c.fecha_inicio || '-'}</td><td>${c.fecha_fin || '-'}</td><td>${c.dias_solicitados || 0}</td><td>${c.tipo_constancia || '-'}</td><td><span class="badge" style="background-color: ${estadoColor}20; color: ${estadoColor};">${estadoLabel}</span></td><td>${esAdmin ? `<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick='editarRegistroTab(${JSON.stringify(c).replace(/'/g, "\\'")}, "constancias_tiempo")' title="Editar"><i class="fas fa-edit"></i></button>` : ''}</td></tr>`;
                });
            } else {
                ctHtml += '<tr><td colspan="6" class="text-center py-4 text-muted">Sin constancias</td></tr>';
            }
            constanciasEl.innerHTML = ctHtml + '</tbody></table></div>';
        }

        // Render Días Económicos tab
        if (diasEcoEl) {
            const diasData = data.dias_economicos || [];
            
            // Calcular días usados en el periodo actual (16 julio - 15 julio) según calendario escolar
            const today = new Date();
            const currentYear = today.getFullYear();
            const currentMonth = today.getMonth() + 1;
            const currentDay = today.getDate();
            let periodoInicio, periodoFin;
            // Periodo escolar: 16 julio año anterior - 15 julio año actual
            if (currentMonth >= 7 && currentDay >= 16) {
                periodoInicio = `${currentYear}-07-16`;
                periodoFin = `${currentYear + 1}-07-15`;
            } else {
                periodoInicio = `${currentYear - 1}-07-16`;
                periodoFin = `${currentYear}-07-15`;
            }
            
            let diasUsados = 0;
            let diasPendientes = 0;
            let diasLimite = 9;
            
            const diasAprobados = [];
            const diasNoAprobados = [];
            
            diasData.forEach(d => {
                if (d.estatus === 'aprobado' && d.fecha >= periodoInicio && d.fecha <= periodoFin) {
                    diasUsados += parseInt(d.dias_solicitados) || 0;
                    diasAprobados.push(d);
                } else {
                    diasNoAprobados.push(d);
                    if (d.estatus === 'pendiente') {
                        diasPendientes += parseInt(d.dias_solicitados) || 0;
                    }
                }
            });
            
            // Calcular días disfrutados (solo los primeros 9)
            diasAprobados.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
            let diasContador = 0;
            let diasDisfrutados = 0;
            diasAprobados.forEach(d => {
                if (diasContador < diasLimite) {
                    const diasRestantes = diasLimite - diasContador;
                    diasDisfrutados += Math.min(parseInt(d.dias_solicitados) || 0, diasRestantes);
                    diasContador += parseInt(d.dias_solicitados) || 0;
                }
            });
            
            const diasDisponibles = Math.max(0, diasLimite - diasDisfrutados);
            
            let ecoHtml = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0" style="color: ${PANTONE.secondaryDark};">
                        <i class="fas fa-calendar-day me-2" style="color: ${PANTONE.secondary};"></i>Días Económicos
                    </h6>
                    <button class="btn btn-sm" onclick="mostrarFundamentoDiasEconomicos()" title="Ver fundamento legal" style="background-color: ${PANTONE.secondary}; color: white; border: none;">
                        <i class="fas fa-balance-scale me-1"></i> Fundamento Legal
                    </button>
                </div>
                
                <!-- Resumen de Días Económicos -->
                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <div class="text-white rounded p-3 text-center" style="background-color: #9F2241 !important;">
                            <h2 class="mb-0 text-white fw-bold">9</h2>
                            <small class="text-white-50">Aplicables</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-white rounded p-3 text-center" style="background-color: #235B4E !important;">
                            <h2 class="mb-0 text-white fw-bold">${diasDisfrutados}</h2>
                            <small class="text-white-50">Disfrutados</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-white rounded p-3 text-center" style="background-color: ${diasDisponibles > 0 ? '#28a745' : '#dc3545'} !important;">
                            <h2 class="mb-0 text-white fw-bold">${diasDisponibles}</h2>
                            <small class="text-white-50">Disponibles</small>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Historial de Solicitudes</h6>
                        <span class="badge" style="background-color: #235B4E;">${diasAprobados.length + diasNoAprobados.length}</span>
                    </div>
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden; border: 2px solid #235B4E;">
                        <thead style="background-color: #235B4E; color: white;">
                            <tr>
                                <th style="padding: 10px;"><i class="fas fa-calendar me-1"></i>Fecha</th>
                                <th style="padding: 10px;"><i class="fas fa-calendar-plus me-1"></i>Días</th>
                                <th style="padding: 10px;"><i class="fas fa-info-circle me-1"></i>Modalidad</th>
                                <th style="padding: 10px;"><i class="fas fa-check-circle me-1"></i>Estatus</th>
                                <th style="padding: 10px; width: 60px;"><i class="fas fa-tools me-1"></i></th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            // Combinar todos los registros para mostrar
            const todosLosRegistros = [...diasAprobados, ...diasNoAprobados];
            todosLosRegistros.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
            
            if(todosLosRegistros.length > 0) {
                todosLosRegistros.forEach(d => {
                    let modalidadTexto = 'N/A';
                    if (d.modalidad === 'A') modalidadTexto = '3 días';
                    else if (d.modalidad === 'B') modalidadTexto = '2 días';
                    else if (d.modalidad === 'C') modalidadTexto = '1 día';
                    
                    let estatusTexto = d.estatus || 'pendiente';
                    let badgeColor = '#28a745';
                    let bgColor = '';
                    if (d.estatus === 'pendiente') {
                        badgeColor = '#BC955C';
                        bgColor = '#fff3cd';
                    } else if (d.estatus === 'rechazado') {
                        badgeColor = '#dc3545';
                        bgColor = '#f8d7da';
                    }
                    
                    ecoHtml += `
                        <tr style="${bgColor ? 'background-color: ' + bgColor : ''}">
                            <td style="padding: 10px;"><strong>${d.fecha || '-'}</strong></td>
                            <td style="padding: 10px;"><span class="badge" style="background-color: ${badgeColor}; color: white;">${d.dias_solicitados || 0}</span></td>
                            <td style="padding: 10px;"><small>${modalidadTexto}</small></td>
                            <td style="padding: 10px;"><span class="badge" style="background-color: ${badgeColor}; color: white; text-transform: capitalize;">${estatusTexto}</span></td>
                            <td style="padding: 10px; text-align: center;">
                                ${esAdmin ? `<button class="btn btn-sm btn-outline-success" onclick='editarDiaEconomico(${JSON.stringify(d).replace(/'/g, "\\'")})' title="Editar día económico"><i class="fas fa-edit"></i></button>` : ''}
                            </td>
                        </tr>`;
                });
            } else {
                ecoHtml += `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted">
                            <i class="fas fa-inbox me-2"></i>Sin solicitudes registradas
                        </td>
                    </tr>`;
            }
            ecoHtml += '</tbody></table></div>';
            
            // Mensaje si no hay días disponibles
            if (diasDisponibles === 0) {
                ecoHtml += `
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>No hay días económicos disponibles.</strong> Ya se utilizaron los 9 días del periodo.
                </div>`;
            }
            
            diasEcoEl.innerHTML = ecoHtml;
        }

        // Render Licencias Médicas tab
        if (licenciasMedicasEl) {
            window.licenciasMedicasData = data.licencias_medicas || [];
            window.controlLicenciasData = data.control_licencias || null;
            
            let licenciasHtml = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0" style="color: #235B4E;">
                        <i class="fas fa-user-md me-2" style="color: #235B4E;"></i>Licencias Médicas
                    </h6>
                    <button class="btn btn-sm" onclick="mostrarFundamentoLicenciasMedicas()" title="Ver fundamento legal" style="background-color: #235B4E; color: white; border: none;">
                        <i class="fas fa-balance-scale me-1"></i> Fundamento Legal
                    </button>
                </div>
                <div id="licenciasmedicas-content"></div>`;
            licenciasMedicasEl.innerHTML = licenciasHtml;
            filtrarLicenciasMedicas();
        }

        // Render Sanciones tab
        if (sancionesEl) {
            const sancionesData = data.sanciones || [];
            let sanHtml = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0" style="color: ${PANTONE.danger};">
                        <i class="fas fa-gavel me-2" style="color: ${PANTONE.danger};"></i>Sanciones del Año
                    </h6>
                    <span class="badge" style="background-color: ${PANTONE.danger}15; color: ${PANTONE.danger};">${sancionesData.length} sanciones</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="border-radius: 8px; overflow: hidden;">
                        <thead style="background-color: ${PANTONE.danger}; color: white;">
                            <tr>
                                <th style="padding: 12px;"><i class="fas fa-calendar-alt me-1"></i>Fecha</th>
                                <th style="padding: 12px;"><i class="fas fa-tag me-1"></i>Tipo</th>
                                <th style="padding: 12px;"><i class="fas fa-align-left me-1"></i>Motivo</th>
                                <th style="width:50px; padding: 12px;"></th>
                            </tr>
                        </thead>
                        <tbody>`;
            if(sancionesData.length > 0) {
                sancionesData.forEach(s => {
                    let tipoColor = PANTONE.danger;
                    if (s.tipo === 'amonestacion') tipoColor = PANTONE.warning;
                    else if (s.tipo === 'suspension') tipoColor = PANTONE.danger;
                    else if (s.tipo === 'multa') tipoColor = PANTONE.primary;
                    
                    sanHtml += `
                        <tr style="border-bottom: 1px solid ${PANTONE.border}50;">
                            <td style="padding: 12px;"><strong>${s.fecha_inicio || '-'}</strong></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${tipoColor}15; color: ${tipoColor}; text-transform: capitalize;">${s.tipo || '-'}</span></td>
                            <td style="padding: 12px;">${s.motivo || '-'}</td>
                            <td style="padding: 12px;">${esAdmin ? `<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick='editarRegistroTab(${JSON.stringify(s).replace(/'/g, "\\'")}, "sanciones")' title="Editar"><i class="fas fa-edit"></i></button>` : ''}</td>
                        </tr>`;
                });
            } else {
                sanHtml += `
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="fas fa-check-circle" style="font-size: 2rem; color: ${PANTONE.success};"></i>
                            <p class="mt-2 mb-0" style="color: ${PANTONE.grayDark};">Sin sanciones este año</p>
                        </td>
                    </tr>`;
            }
            sancionesEl.innerHTML = sanHtml + '</tbody></table></div>';
        }

        // Render Ciclos tab
        const puedeGestionarCiclos = <?php echo $puedeGestionarCiclos ? 'true' : 'false'; ?>;
        console.log('Ciclos - puedeGestionarCiclos:', puedeGestionarCiclos, 'ciclosEl:', !!ciclosEl, 'data.ciclos_asignados:', data.ciclos_asignados?.length);
        
        if (ciclosEl) {
            const ciclosContent = ciclosEl.querySelector('#ciclos-content') || ciclosEl;
            if (ciclosContent) {
                const ciclosAsignados = data.ciclos_asignados || [];
                
                // Colores Pantone
                const PANTONE = {
                    primary: '#9F2241',
                    primaryDark: '#691C32',
                    secondary: '#235B4E',
                    secondaryDark: '#10312B',
                    accent: '#DDC9A3',
                    accentDark: '#BC955C',
                    danger: '#691C32',
                    success: '#235B4E',
                    warning: '#BC955C',
                    gray: '#98989A',
                    grayDark: '#6F7271'
                };
                
                if (ciclosAsignados.length > 0) {
                    let html = `<div class="ciclos-container">`;
                    
                    ciclosAsignados.forEach((ciclo, index) => {
                        const tipoCiclo = ciclo.tipo_ciclo || 'Fijo';
                        const nombreCiclo = ciclo.nombre_ciclo || ciclo.nombre_horario || 'Sin nombre';
                        const fechaInicio = ciclo.fecha_inicio ? new Date(ciclo.fecha_inicio).toLocaleDateString('es-MX') : 'N/A';
                        const fechaFin = ciclo.fecha_fin ? new Date(ciclo.fecha_fin).toLocaleDateString('es-MX') : 'Indefinido';
                        const horasRequeridas = (ciclo.horas_requeridas && parseFloat(ciclo.horas_requeridas) > 0) ? parseFloat(ciclo.horas_requeridas).toFixed(1) : '-';
                        const horasTrabajadas = (ciclo.horas_trabajadas && parseFloat(ciclo.horas_trabajadas) > 0) ? parseFloat(ciclo.horas_trabajadas).toFixed(1) : '-';
                        
                        // Colores según tipo de ciclo
                        const tipoColor = tipoCiclo === 'Combinado' ? PANTONE.warning : PANTONE.primary;
                        const esVigente = !ciclo.fecha_fin || new Date(ciclo.fecha_fin) >= new Date();
                        const estadoColor = esVigente ? PANTONE.success : PANTONE.gray;
                        
                        // Card del ciclo
                        html += `
                            <div class="card mb-3 ciclo-card" style="border-left: 4px solid ${tipoColor}; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1" style="color: ${PANTONE.primaryDark}; font-weight: 600;">
                                                <i class="fas fa-clock me-2" style="color: ${tipoColor};"></i>${nombreCiclo}
                                            </h6>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <span class="badge" style="background-color: ${tipoColor}; color: white; font-size: 0.75rem;">
                                                    ${tipoCiclo}
                                                </span>
                                                <span class="badge" style="background-color: ${estadoColor}; color: white; font-size: 0.75rem;">
                                                    ${esVigente ? 'Vigente' : 'Finalizado'}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary" onclick="verDetalleCiclo(${index})" title="Ver detalle" style="border-color: ${PANTONE.gray};">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="eliminarCicloAsignado(${ciclo.id || ciclo.empleado_horario_id})" title="Eliminar" style="border-color: ${PANTONE.danger}; color: ${PANTONE.danger};">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-2 mt-1">
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 rounded" style="background-color: ${PANTONE.secondary}15;">
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">VIGENCIA</small>
                                                <small class="fw-bold" style="color: ${PANTONE.secondaryDark};">${fechaInicio}</small>
                                                <small class="text-muted"> - </small>
                                                <small class="fw-bold" style="color: ${PANTONE.secondaryDark};">${fechaFin}</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 rounded" style="background-color: ${PANTONE.primary}15;">
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">HRS REQUERIDAS</small>
                                                <span class="fw-bold" style="color: ${PANTONE.primaryDark}; font-size: 1.1rem;">${horasRequeridas}</span>
                                                <small class="text-muted">hrs/mes</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 rounded" style="background-color: ${PANTONE.success}15;">
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">HRS TRABAJADAS</small>
                                                <span class="fw-bold" style="color: ${PANTONE.success}; font-size: 1.1rem;">${horasTrabajadas}</span>
                                                <small class="text-muted">hrs/mes</small>
                                            </div>
                                        </div>
                                            <div class="col-6 col-md-3">
                                            <div class="p-2 rounded" style="background-color: ${PANTONE.accentDark}20;">
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">CUMPLIMIENTO</small>
                                                ${(horasRequeridas !== '-' && horasTrabajadas !== '-' && parseFloat(horasRequeridas) > 0 && parseFloat(horasTrabajadas) >= 0) ? `
                                                    <span class="fw-bold" style="color: ${parseFloat(horasTrabajadas) >= parseFloat(horasRequeridas) ? PANTONE.success : PANTONE.warning}; font-size: 1.1rem;">
                                                        ${Math.min(100, Math.round((parseFloat(horasTrabajadas) / parseFloat(horasRequeridas)) * 100))}%
                                                    </span>
                                                ` : '<span class="text-muted">-</span>'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detalle del ciclo (expandible) -->
                            <div id="ciclo-details-${index}" style="display: none;" class="mb-3">
                                <div class="card" style="border: 1px solid ${PANTONE.gray}30; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                    <div class="card-header py-2" style="background-color: ${PANTONE.secondary}10; border-bottom: 2px solid ${PANTONE.secondary};">
                                        <h6 class="mb-0" style="color: ${PANTONE.secondaryDark};">
                                            <i class="fas fa-calendar-alt me-2"></i>Horario Detallado por Día
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0" style="font-size: 0.85rem;">
                                                <thead style="background-color: ${PANTONE.gray}15;">
                                                    <tr>
                                                        <th style="color: ${PANTONE.grayDark}; width: 20%; padding: 10px;">Día</th>
                                                        <th style="color: ${PANTONE.grayDark}; padding: 10px;">Horario Asignado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;
                        
                        if (ciclo.bloques && ciclo.bloques.length > 0) {
                            const diasOrdenados = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                            const bloquesAgrupados = {};
                            
                            ciclo.bloques.forEach(b => {
                                const dia = b.dia_nombre || 'Día';
                                if (!bloquesAgrupados[dia]) {
                                    bloquesAgrupados[dia] = [];
                                }
                                bloquesAgrupados[dia].push(b);
                            });
                            
                            diasOrdenados.forEach(dia => {
                                if (bloquesAgrupados[dia]) {
                                    const bloquesDia = bloquesAgrupados[dia];
                                    const colorBase = bloquesDia[0].color_horario || PANTONE.secondary;
                                    const bgColor = colorBase + '12';
                                    const borderColor = colorBase;
                                    
                                    const horariosHtml = bloquesDia.map(b => {
                                        const color = b.color_horario || PANTONE.secondary;
                                        return `<span class="badge me-1 mb-1" style="background-color: ${color}; color: white; padding: 6px 10px; font-size: 0.8rem;">
                                            <i class="far fa-clock me-1"></i>${b.hora_inicio ? b.hora_inicio.substring(0,5) : '-'} - ${b.hora_fin ? b.hora_fin.substring(0,5) : '-'}
                                            ${b.nombre_horario ? '<span class="opacity-75">' + b.nombre_horario + '</span>' : ''}
                                        </span>`;
                                    }).join('');
                                    
                                    html += `
                                        <tr>
                                            <td style="background-color: ${bgColor}; border-left: 4px solid ${borderColor}; padding: 10px;">
                                                <strong style="color: ${PANTONE.grayDark};">${dia}</strong>
                                            </td>
                                            <td style="padding: 10px;">${horariosHtml}</td>
                                        </tr>`;
                                } else {
                                    html += `
                                        <tr>
                                            <td style="background-color: ${PANTONE.gray}10; border-left: 4px solid ${PANTONE.gray}30; padding: 10px;">
                                                <strong style="color: ${PANTONE.grayDark};">${dia}</strong>
                                            </td>
                                            <td style="color: ${PANTONE.gray}; font-style: italic; padding: 10px;">Sin asignar</td>
                                        </tr>`;
                                }
                            });
                        } else {
                            html += `
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">Sin detalles de horario</td>
                                </tr>`;
                        }
                        
                        html += `
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                    });
                    
                    html += `</div>`;
                    
                    html += `
                        <div class="mt-3 text-center">
                            <button class="btn btn-sm" style="background-color: ${PANTONE.primary}; color: white; border: none; padding: 10px 25px; border-radius: 20px; box-shadow: 0 2px 8px ${PANTONE.primary}40;" onclick="abrirModalAsignarCiclo()">
                                <i class="fas fa-plus-circle me-2"></i>Asignar Nuevo Ciclo
                            </button>
                        </div>`;
                    
                    ciclosContent.innerHTML = html;
                } else {
                    ciclosContent.innerHTML = `
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-calendar-times" style="font-size: 3rem; color: ${PANTONE.gray};"></i>
                            </div>
                            <h6 style="color: ${PANTONE.grayDark};">No hay ciclos asignados</h6>
                            <p class="text-muted mb-3">Asigna un ciclo o horario al empleado</p>
                            <button class="btn" style="background-color: ${PANTONE.primary}; color: white; border: none; padding: 10px 25px; border-radius: 20px;" onclick="abrirModalAsignarCiclo()">
                                <i class="fas fa-plus me-2"></i>Asignar Ciclo
                            </button>
                        </div>`;
                }
            }
            const btnActualizar = ciclosEl.querySelector('#btn-actualizar-ciclos');
            if (btnActualizar) {
                btnActualizar.disabled = false;
                btnActualizar.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Actualizar';
            }
        }
    } catch (error) {
        console.error('Error al cargar datos del empleado:', error);
        if(generalEl) generalEl.innerHTML = `<div class="alert alert-danger">Error al cargar datos.</div>`;
    }
}

async function guardarJustificacion() {
    const form = document.getElementById('form-justificacion');
    const select = document.getElementById('tipo_justificacion');
    if (!form || !select) {
        alert('Error: No se encontró el formulario de justificación');
        return;
    }
    
    const tipo = select.value;
    const fundamentosEl = document.getElementById('just_fundamentos');
    const retardoIdEl = document.getElementById('just_retardo_id');
    const empleadoIdEl = document.getElementById('just_empleado_id');
    const tipoOriginalEl = document.getElementById('just_tipo_original');
    
    const fundamentos = fundamentosEl ? fundamentosEl.value.trim() : '';
    const retardoId = retardoIdEl ? retardoIdEl.value : '';
    const empleadoId = empleadoIdEl ? empleadoIdEl.value : '';
    const tipoOriginal = tipoOriginalEl ? tipoOriginalEl.value : '';
    
    console.log('DEBUG guardarJustificacion - empleadoIdEl:', empleadoIdEl, 'empleadoId:', empleadoId);
    
    console.log('DEBUG guardarJustificacion - tipoOriginal:', tipoOriginal, 'retardoId:', retardoId, 'esIncidencia:', tipoOriginal === 'incidencia');
    
    const esRetardo = tipo === 'retardo';
    const esComision = ['comision_entrada', 'comision_salida', 'comision_dia'].includes(tipo);
    const esDiaEconomico = tipo === 'dia_economico';
    const esLicenciaMedica = tipo === 'licencia_medica' || tipo === 'constancia_tiempo';
    const esIncidencia = tipoOriginal === 'incidencia';
    
    if (esRetardo && !retardoId && !esIncidencia) {
        alert('Para justificar retardos, seleccione un retardo de la lista.');
        return;
    }
    
    if ((esComision || esDiaEconomico) && !retardoId && !esIncidencia) {
        if (confirm('¿Desea registrar una nueva ' + (esDiaEconomico ? 'solicitud de día económico' : 'comisión') + '?\n\nSe abrirá el formulario correspondiente.')) {
            window.location.href = '<?= BASE_URL ?>/comisiones/create?empleado_id=' + empleadoId;
        }
        return;
    }
    
    if (esComision) {
        const lugar = document.getElementById('just_lugar').value.trim();
        if (!lugar) {
            alert('Por favor, indique el lugar de la comisión.');
            document.getElementById('just_lugar').focus();
            return;
        }
        
        // Para comision_entrada y comision_salida no es obligatorio fundamentos
    }
    
    if (esDiaEconomico) {
        const diasSolicitados = parseInt(document.getElementById('just_dias_economicos').value) || 0;
        const fechaEconomico = document.getElementById('just_fecha_economico').value;
        
        if (!fechaEconomico) {
            alert('Por favor, seleccione la fecha del día económico.');
            document.getElementById('just_fecha_economico').focus();
            return;
        }
        
        // Validar que el empleado tenga días disponibles
        if (window.empleadoData && window.empleadoData.dias_economicos) {
            const diasData = window.empleadoData.dias_economicos || [];
            const today = new Date();
            const currentYear = today.getFullYear();
            const currentMonth = today.getMonth() + 1;
            const currentDay = today.getDate();
            
            let periodoInicio, periodoFin;
            if (currentMonth >= 7 && currentDay >= 16) {
                periodoInicio = `${currentYear}-07-16`;
                periodoFin = `${currentYear + 1}-07-15`;
            } else {
                periodoInicio = `${currentYear - 1}-07-16`;
                periodoFin = `${currentYear}-07-15`;
            }
            
            // Calcular días usados
            let diasUsados = 0;
            diasData.forEach(d => {
                if (d.estatus === 'aprobado' && d.fecha >= periodoInicio && d.fecha <= periodoFin) {
                    diasUsados += parseInt(d.dias_solicitados) || 0;
                }
            });
            
            // Solo contar los primeros 9 días
            if (diasUsados >= 9) {
                alert('No es posible solicitar días económicos.\n\nEl empleado ya utilizó los 9 días económicos del periodo (16 julio - 15 julio).\n\nDías usados: ' + diasUsados);
                return;
            }
            
            const diasDisponibles = 9 - diasUsados;
            if (diasSolicitados > diasDisponibles) {
                alert('No es posible solicitar ' + diasSolicitados + ' días económicos.\n\nSolo quedan ' + diasDisponibles + ' días disponibles en el periodo actual.');
                return;
            }
        }
    }
    
    if (esLicenciaMedica) {
        const folio = document.getElementById('just_folio_licencia').value.trim();
        const diasOtorgados = document.getElementById('just_dias_otorgados').value;
        const fechaInicioLicencia = document.getElementById('just_fecha_inicio_licencia').value;
        const fechaFinLicencia = document.getElementById('just_fecha_fin_licencia').value;
        const diagnostico = document.getElementById('just_diagnostico').value.trim();
        
        if (!folio) {
            alert('Por favor, ingrese el número de folio de la licencia.');
            document.getElementById('just_folio_licencia').focus();
            return;
        }
        
        if (!diasOtorgados) {
            alert('Por favor, ingrese los días otorgados.');
            document.getElementById('just_dias_otorgados').focus();
            return;
        }
        
        // Validar que no exceda los días disponibles
        const diasSolicitados = parseInt(diasOtorgados);
        try {
            const response = await fetch(`<?= BASE_URL ?>/empleados/datos-completos/${empleadoId}`);
            const data = await response.json();
            const ctrl = data.control_licencias || {};
            
            const fullTotal = ctrl.full || 0;
            const halfTotal = ctrl.half || 0;
            const fullUsados = ctrl.full_usados || 0;
            const halfUsados = ctrl.half_usados || 0;
            
            const fullRestantes = Math.max(0, fullTotal - fullUsados);
            const halfRestantes = Math.max(0, halfTotal - halfUsados);
            const totalRestantes = fullRestantes + halfRestantes;
            
            if (diasSolicitados > totalRestantes) {
                const mensaje = `⚠️ ADVERTENCIA: Está solicitando ${diasSolicitados} días pero solo tiene ${totalRestantes} días disponibles.\n\n` +
                    `• Sueldo íntegro disponibles: ${fullRestantes} días\n` +
                    `• Medio sueldo disponibles: ${halfRestantes} días\n\n` +
                    `Se registrará(n) ${Math.min(diasSolicitados, fullRestantes)} día(s) con sueldo íntegro y ${Math.max(0, diasSolicitados - fullRestantes)} día(s) con medio sueldo o sin goce.\n\n` +
                    `¿Desea continuar?`;
                
                if (!confirm(mensaje)) {
                    return;
                }
            } else if (diasSolicitados > fullRestantes) {
                const diasMedioSueldo = diasSolicitados - fullRestantes;
                const mensaje = `ℹ️ NOTA: De los ${diasSolicitados} días solicitados:\n\n` +
                    `• ${fullRestantes} día(s) serán con SUELDO ÍNTEGRO\n` +
                    `• ${diasMedioSueldo} día(s) serán con MEDIO SUELDO\n\n` +
                    `¿Desea continuar?`;
                
                if (!confirm(mensaje)) {
                    return;
                }
            } else {
                const mensaje = `✅ Se registrarán ${diasSolicitados} días con SUELDO ÍNTEGRO.\n\n¿Desea continuar?`;
                if (!confirm(mensaje)) {
                    return;
                }
            }
        } catch (e) {
            console.error('Error al validar días:', e);
        }
        
        if (!fechaInicioLicencia) {
            alert('Por favor, seleccione la fecha de inicio de la licencia.');
            document.getElementById('just_fecha_inicio_licencia').focus();
            return;
        }
        
        if (!diagnostico) {
            alert('Por favor, ingrese el diagnóstico médico.');
            document.getElementById('just_diagnostico').focus();
            return;
        }
    }
    
    const formData = new FormData(form);
    
    // Agregar token CSRF
    const csrfToken = document.getElementById('csrf_token')?.value || '';
    formData.append('csrf_token', csrfToken);
    
    if (fundamentos) {
        formData.append('fundamentos', fundamentos);
    }
    
    // Agregar campos de comisión si aplica (todos los tipos)
    if (esComision) {
        const lugar = document.getElementById('just_lugar').value;
        const motivo = document.getElementById('just_motivo').value;
        
        if (!lugar && (tipo === 'comision_entrada' || tipo === 'comision_salida' || tipo === 'comision_todo_dia')) {
            alert('Por favor, indique el lugar de la comisión.');
            document.getElementById('just_lugar').focus();
            return;
        }
        
        if (!motivo && (tipo === 'comision_entrada' || tipo === 'comision_salida' || tipo === 'comision_todo_dia')) {
            alert('Por favor, indique el motivo de la comisión.');
            document.getElementById('just_motivo').focus();
            return;
        }
        
        formData.append('lugar_comision', lugar);
        formData.append('motivo_comision', motivo);
        
        // Para comisión de todo el día, requerir fechas
        if (tipo === 'comision_todo_dia' || tipo === 'comision_dia') {
            const fechaInicioComision = document.getElementById('just_fecha_inicio_comision').value;
            const fechaFinComision = document.getElementById('just_fecha_fin_comision').value;
            
            if (!fechaInicioComision) {
                alert('Por favor, seleccione la fecha de inicio de la comisión.');
                document.getElementById('just_fecha_inicio_comision').focus();
                return;
            }
            
            if (!fechaFinComision) {
                alert('Por favor, seleccione la fecha fin de la comisión.');
                document.getElementById('just_fecha_fin_comision').focus();
                return;
            }
            
            formData.append('fecha_inicio_comision', fechaInicioComision);
            formData.append('fecha_fin_comision', fechaFinComision);
        } else {
            // Para comision_entrada y comision_salida, usar la fecha de la incidencia
            const fechaAsistenciaEl = document.getElementById('fecha_asistencia_eco');
            if (fechaAsistenciaEl && fechaAsistenciaEl.value) {
                formData.append('fecha_inicio_comision', fechaAsistenciaEl.value);
                formData.append('fecha_fin_comision', fechaAsistenciaEl.value);
            }
        }
    }
    
    // Agregar campos de día económico si aplica
    if (esDiaEconomico) {
        const diasSolicitados = document.getElementById('just_dias_economicos').value;
        const fechaInicio = document.getElementById('just_fecha_inicio_eco').value;
        const fechaFin = document.getElementById('just_fecha_fin_eco').value;
        
        if (!fechaInicio) {
            alert('Por favor, seleccione la fecha de inicio del día económico.');
            return;
        }
        
        if (diasSolicitados >= 2 && !fechaFin) {
            alert('Por favor, seleccione la fecha de inicio.');
            return;
        }
        
        formData.append('dias_solicitados', diasSolicitados);
        formData.append('fecha_inicio', fechaInicio);
        formData.append('fecha_fin', fechaFin);
        formData.append('motivo', motivo);
    }
    
    // Agregar campos de licencia médica si aplica
    if (esLicenciaMedica) {
        const folio = document.getElementById('just_folio_licencia').value;
        const diasOtorgados = document.getElementById('just_dias_otorgados').value;
        const fechaInicioLicencia = document.getElementById('just_fecha_inicio_licencia').value;
        const fechaFinLicencia = document.getElementById('just_fecha_fin_licencia').value;
        const diagnostico = document.getElementById('just_diagnostico').value;
        
        formData.append('folio_licencia', folio);
        formData.append('dias_otorgados', diasOtorgados);
        formData.append('fecha_inicio_licencia', fechaInicioLicencia);
        formData.append('fecha_fin_licencia', fechaFinLicencia);
        formData.append('diagnostico', diagnostico);
    }
    
    // Determinar endpoint según tipo
    let endpoint = '<?= BASE_URL ?>/justificaciones/ajax-justificar';
    
    console.log('DEBUG endpoint - esIncidencia:', esIncidencia, 'retardoId:', retardoId);
    
    if (esIncidencia && retardoId) {
        endpoint = '<?= BASE_URL ?>/justificaciones/actualizar-incidencia';
        formData.append('incidencia_id', retardoId);
        formData.append('asistencia_id', retardoId); // Para guardarIncidencia
        formData.append('empleado_id', empleadoId); // Para guardarIncidencia
        formData.append('tipo_justificacion', tipo);
        console.log('DEBUG: Usando actualizar-incidencia');
    } else if (retardoId && esRetardo) {
        // Si es un retardo existente, usar ajax-justificar
        console.log('DEBUG: Usando ajax-justificar (retardo)');
    } else if (!retardoId && !esIncidencia) {
        endpoint = '<?= BASE_URL ?>/justificaciones/ajax-justificar';
        console.log('DEBUG: Usando ajax-justificar (default)');
    }
    
    console.log('DEBUG: Endpoint final:', endpoint);
    
    // Debug: mostrar contenido de formData
    for (let [key, value] of formData.entries()) {
        console.log('DEBUG formData:', key, value);
    }
    
    fetch(endpoint, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-Token': document.getElementById('csrf_token')?.value || ''
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Justificación guardada correctamente');
            const modalEl = document.getElementById('modalJustificacion');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            
            if (window.empleadoId) {
                verEmpleado(window.empleadoId);
            }
        } else {
            // Mostrar error en el formulario
            const tipoIncidenciaDiv = document.getElementById('tipo_incidencia_seleccionado');
            if (tipoIncidenciaDiv) {
                tipoIncidenciaDiv.className = 'alert alert-danger mt-3';
                tipoIncidenciaDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + (data.error || 'Error al guardar justificación');
                tipoIncidenciaDiv.classList.remove('d-none');
            } else {
                mostrarError(data.error || 'Error al guardar justificación');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al procesar la solicitud');
    });
}

function recargarCiclos() {
    const btn = document.getElementById('btn-actualizar-ciclos');
    const contenido = document.getElementById('ciclos-content');
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';
    }
    
    if (typeof window.empleadoId !== 'undefined' && window.empleadoId) {
        verEmpleado(window.empleadoId);
    } else {
        console.error('No hay empleado seleccionado');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Actualizar';
        }
    }
}

function abrirModalAsignarCiclo() {
    const modalEl = document.getElementById('modalAsignarCiclo');
    if (!modalEl) {
        alert('No se encontró el modal de asignación de ciclos');
        return;
    }
    
    const empleadoIdInput = document.getElementById('ciclo_empleado_id');
    if (empleadoIdInput) {
        empleadoIdInput.value = window.empleadoId;
    }
    
    const fechaInicioInput = document.getElementById('ciclo_fecha_inicio');
    if (fechaInicioInput) {
        fechaInicioInput.value = new Date().toISOString().split('T')[0];
    }
    
    cargarCiclosDisponibles();
    
    const bootstrapModal = new bootstrap.Modal(modalEl);
    bootstrapModal.show();
}

async function cargarCiclosDisponibles() {
    const select = document.getElementById('ciclo_select');
    if (!select) return;
    
    try {
        const response = await fetch(`<?= BASE_URL ?>/ciclos/json`);
        const result = await response.json();
        
        select.innerHTML = '<option value="">Seleccionar...</option>';
        
        if (result.success && Array.isArray(result.data)) {
            result.data.forEach(ciclo => {
                const option = document.createElement('option');
                option.value = ciclo.id;
                option.textContent = `${ciclo.nombre} (${ciclo.num_ciclo || 'N/A'})`;
                select.appendChild(option);
            });
        } else if (Array.isArray(result)) {
            result.forEach(ciclo => {
                const option = document.createElement('option');
                option.value = ciclo.id;
                option.textContent = `${ciclo.nombre} (${ciclo.num_ciclo || 'N/A'})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando ciclos:', error);
    }
}

function guardarCicloAsignado() {
    const form = document.getElementById('formAsignarCiclo');
    if (!form) {
        alert('No se encontró el formulario');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('empleado_id', window.empleadoId);
    formData.append('csrf_token', document.getElementById('csrf_token')?.value || '');
    
    // Calcular fecha_fin automática: día anterior al nuevo ciclo
    const nuevoCicloId = form.get('ciclo_id');
    const nuevaFechaInicio = form.get('fecha_inicio');
    
    if (nuevoCicloId && nuevaFechaInicio) {
        // Buscar el ciclo seleccionado para obtener su fecha_inicio
        const select = document.getElementById('ciclo_select');
        const selectedOption = select?.options?.[select.selectedIndex];
        
        // Si hay una fecha_fin proporcionada, usarla; si no, calcular como día anterior al nuevo ciclo
        const fechaFinInput = form.get('fecha_fin');
        if (!fechaFinInput || fechaFinInput === '') {
            // Calcular un día antes de la nueva fecha de inicio
            const fechaInicioObj = new Date(nuevaFechaInicio);
            fechaInicioObj.setDate(fechaInicioObj.getDate() - 1);
            const fechaFinCalculada = fechaInicioObj.toISOString().split('T')[0];
            formData.set('fecha_fin', fechaFinCalculada);
        }
    }
    
    fetch(`<?= BASE_URL ?>/horarios/asignar-ciclo-empleado`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ciclo asignado correctamente');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAsignarCiclo'));
            if (modal) modal.hide();
            recargarCiclos();
        } else {
            alert('Error: ' + (data.message || 'Error al asignar ciclo'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al asignar ciclo');
    });
}

function verDetalleCiclo(index) {
    const details = document.getElementById('ciclo-details-' + index);
    if (details) {
        details.style.display = details.style.display === 'none' ? 'block' : 'none';
    }
}

function mostrarFundamentoLicenciasMedicas() {
    const fundamento = `
        <div class="text-start" style="font-size: 0.9rem;">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Base Legal:</strong> Art. 111 de la Ley Federal de los Trabajadores al Servicio del Estado y Art. 52 del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.
            </div>
            
            <div class="alert alert-warning mb-3">
                <i class="fas fa-file-medical me-2"></i>
                <strong>Documento requerido:</strong> Expedido por ISSSTE (Anexar Original)
            </div>
            
            <h6 class="mb-3" style="color: #235B4E; font-weight: 600;">
                <i class="fas fa-balance-scale me-2"></i>Fundamentos Legales
            </h6>
            
            <div class="mb-3 p-3 bg-light rounded">
                <strong style="color: #235B4E;">Artículo 111 - Ley Federal de los Trabajadores al Servicio del Estado</strong>
                <p class="text-muted small mb-0">
                    Los trabajadores que sufran enfermedades profesionales o accidentes de trabajo, tendrán derecho a que se les proporcione asistencia médica y medicinal gratuita.
                </p>
            </div>
            
            <div class="mb-3 p-3 bg-light rounded">
                <strong style="color: #235B4E;">Artículo 52 - Reglamento de las Condiciones Generales de Trabajo del Personal de la S.E.P.</strong>
                <p class="text-muted small mb-0">
                    Conservación del puesto: Durante las enfermedades profesionales y los accidentes de trabajo, la relación de trabajo queda suspendida, conservando el trabajador todas sus prestaciones.
                </p>
            </div>
            
            <h6 class="mb-3" style="color: #235B4E; font-weight: 600;">
                <i class="fas fa-calendar-check me-2"></i>Días de Licencia por Antigüedad
            </h6>
            
            <table class="table table-sm table-bordered mb-3">
                <thead class="table-light">
                    <tr>
                        <th>Antigüedad del trabajador</th>
                        <th>Días con sueldo íntegro</th>
                        <th>Días con medio sueldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Menos de 1 año</td><td>15 días</td><td>15 días</td></tr>
                    <tr><td>De 1 a 5 años</td><td>30 días</td><td>30 días</td></tr>
                    <tr><td>De 5 a 10 años</td><td>45 días</td><td>45 días</td></tr>
                    <tr><td>De 10 años en adelante</td><td>60 días</td><td>60 días</td></tr>
                </tbody>
            </table>
            
            <div class="mb-3 p-3 bg-light rounded">
                <strong style="color: #235B4E;">Orden de uso de licencias:</strong>
                <ol class="mb-0 mt-2">
                    <li>Primero: <strong>60 días con goce de sueldo</strong></li>
                    <li>Segundo: <strong>60 días con medio sueldo</strong></li>
                    <li>Tercero: <strong>Hasta 52 semanas sin goce de sueldo</strong> (si continua la enfermedad)</li>
                </ol>
            </div>
            
            <div class="mb-3">
                <strong style="color: #235B4E;">Regla de Reinicio Anual:</strong>
                <p class="text-muted small mb-1">
                    La licencia médica será continua o discontinua, <strong>una sola vez cada año</strong> contado a partir del momento en que ingreso a la SEP. 
                    Al cumplir un año más de servicio, se iniciarán el cómputo para otorgar licencia con goce de sueldo.
                </p>
             
            </div>
            
            <div class="mb-3">
                <strong style="color: #235B4E;">Requisitos para validación de Licencias Médicas:</strong>
                <ul class="text-muted small" style="padding-left: 1.2rem;">
                    <li>Presentación de certificado médico oficial (expedido por ISSSTE)</li>
                    <li>Notificación dentro de las primeras 48 horas y entregar su original hasta 3 dias habiles</li>
                    <li>Aval del jefe inmediato para ausencias mayores a 3 días</li>
                </ul>
            </div>
            
            <div class="mb-3 p-3 bg-warning bg-opacity-10 rounded border-start border-warning">
                <strong style="color: #856404;">Datos requeridos para solicitar licencia:</strong>
                <ul class="mb-0 mt-2 small">
                    <li><strong>A.</strong> No. de folio (expedido por ISSSTE)</li>
                    <li><strong>B.</strong> Días otorgados</li>
                    <li><strong>C.</strong> Fecha de inicio</li>
                    <li><strong>D.</strong> Fecha fin</li>
                    <li><strong>E.</strong> Diagnóstico</li>
                    <li><strong>F.</strong> Fundamento: Art. 111 Ley Federal de los Trabajadores al Servicio del Estado y Art. 52 del Reglamento de las Condiciones Generales de Trabajo del Personal de la S.E.P.</li>
                    <li><strong>G.</strong> Entregar documento original al Depto. de Recursos Humanos al terminar la licencia</li>
                </ul>
            </div>
            
            <div class="alert alert-warning small mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>Importante:</strong> 3 días posteriores a la licencia médica requieren justificación adicional.
            </div>
        </div>
    `;
    
    document.getElementById('modalFundamentos').querySelector('.modal-title').innerHTML = '<i class="fas fa-user-md me-2"></i>Licencias Médicas - Fundamento Legal';
    document.getElementById('modalFundamentos').querySelector('.modal-body').innerHTML = fundamento;
    const modal = new bootstrap.Modal(document.getElementById('modalFundamentos'));
    modal.show();
}

function eliminarCicloAsignado(empleadoHorarioId) {
    if (!empleadoHorarioId) {
        alert('ID de asignación no válido');
        return;
    }
    
    if (!confirm('¿Está seguro de eliminar esta asignación de ciclo?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', empleadoHorarioId);
    
    fetch(`<?= BASE_URL ?>/horarios/eliminar-asignacion`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Asignación eliminada correctamente');
            recargarCiclos();
        } else {
            alert('Error: ' + (data.message || 'Error al eliminar asignación'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar asignación');
    });
}

function mostrarFundamentoDiasEconomicos() {
    const fundamento = `
        <div class="text-start" style="font-size: 0.9rem;">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Base Legal:</strong> Artículo 52 fracción III del Reglamento de las Condiciones Generales de Trabajo del Personal de la S.E.P.
            </div>
            
            <h6 class="mb-3" style="color: #235B4E; font-weight: 600;">
                <i class="fas fa-gavel me-2"></i>Fundamento Legal
            </h6>
            
            <div class="mb-3 p-3 bg-light rounded">
                <strong style="color: #235B4E;">Artículo 52 Fracción III - RCGTP SEP</strong>
                <p class="text-muted small mb-0">
                    LICENCIAS CON GOCE DE SUELDO: A los trabajadores que tengan más de seis meses de servicios se les concederá licencia con goce de sueldo hasta por nueve días en el período de vigencia del contrato, por motivo de exámenes médicos prenupciales o postnatales, cambio de domicile y defunción de familiares en primer grado.
                </p>
            </div>
            
            <h6 class="mb-3" style="color: #235B4E; font-weight: 600;">
                <i class="fas fa-calendar-check me-2"></i>Días Económicos por Período
            </h6>
            
            <div class="mb-3 p-3 bg-light rounded">
                <p class="text-muted small mb-2">
                    <strong>Período:</strong> 16 de Julio al 15 de Julio del siguiente año (Calendario Escolar SEP)
                </p>
                <p class="text-muted small mb-0">
                    <strong>Límite máximo:</strong> 9 días económicos por período escolar
                </p>
            </div>
            
            <h6 class="mb-3" style="color: #235B4E; font-weight: 600;">
                <i class="fas fa-clock me-2"></i>Modalidades de Uso
            </h6>
            
            <div class="mb-3 p-3 bg-light rounded">
                <table class="table table-sm table-bordered mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Modalidad</th>
                            <th>Días</th>
                            <th>Tiempo de espera</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>A</strong></td><td>3 días</td><td>30 días naturales (despues del 3er día)</td></tr>
                        <tr><td><strong>B</strong></td><td>2 días</td><td>15 días naturales (despues del 2do día)</td></tr>
                        <tr><td><strong>C</strong></td><td>1 día</td><td>7 días naturales</td></tr>
                    </tbody>
                </table>            </div>
            
            <div class="mb-3">
                <strong style="color: #235B4E;">Requisitos para solicitar:</strong>
                <ul class="text-muted small" style="padding-left: 1.2rem;">
                    <li>Antigüedad mayor a 6 meses en la dependencia</li>
                    <li>Solicitar con al menos 1 día de anticipación</li>
                    <li> No se pueden disfrutar en viernes ni lunes continuos</li>
                    <li>Aplica solo para PAAE y Docentes</li>
                </ul>
            </div>
            
            <div class="mb-3 p-3 bg-warning bg-opacity-10 rounded border-start border-warning">
                <strong style="color: #856404;">Restricciones:</strong>
                <ul class="mb-0 mt-2 small">
                    <li>Máximo 9 días económicos por período escolar</li>
                    <li>No se pueden disfrutar en dias sueltos (solo modalidad A, B)</li>
                    <li>Las plazas de confianza no tienen derecho a días económicos</li>
                </ul>
            </div>
            
            <div class="alert alert-warning small mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>Importante:</strong> El período escolar es del 16 de Julio al 15 de Julio del año siguiente.
            </div>
        </div>
    `;
    
    document.getElementById('modalFundamentos').querySelector('.modal-title').innerHTML = '<i class="fas fa-calendar-day me-2"></i>Días Económicos - Fundamento Legal';
    document.getElementById('modalFundamentos').querySelector('.modal-body').innerHTML = fundamento;
    const modal = new bootstrap.Modal(document.getElementById('modalFundamentos'));
    modal.show();
}

function mostrarFundamentos() {
    const modal = new bootstrap.Modal(document.getElementById('modalFundamentos'));
    modal.show();
}

function mostrarFundamentoRetardos() {
    const fundamento = `
        <div class="text-start" style="font-size: 0.9rem;">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Base Legal:</strong> Art. 37 y 80 Inciso a) del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.
            </div>
            
            <h6 class="mb-3" style="color: #BC955C; font-weight: 600;">
                <i class="fas fa-gavel me-2"></i>Clasificación de Retardos
            </h6>
            
            <div class="mb-3 p-3 bg-light rounded">
                <table class="table table-sm table-bordered mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo de Retardo</th>
                            <th>Rango de Minutos</th>
                            <th>Consecuencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>Puntual</strong></td><td>0 min</td><td>Sin consecuencia</td></tr>
                        <tr><td><strong>Tolerancia</strong></td><td>1 - 10 min</td><td>Sin consecuencia</td></tr>
                        <tr><td><strong>Retardo Menor</strong></td><td>11 - 20 min</td><td>Nota mala</td></tr>
                        <tr><td><strong>Retardo Mayor</strong></td><td>21 - 30 min</td><td>Nota mala + Oficio</td></tr>
                        <tr><td><strong>Falta</strong></td><td>31+ min</td><td>Sanción automática</td></tr>
                    </tbody>
                </table>
            </div>
            
            <h6 class="mb-3" style="color: #BC955C; font-weight: 600;">
                <i class="fas fa-exclamation-triangle me-2"></i>Reglas de Notas Malas
            </h6>
            
            <div class="mb-3 p-3 bg-light rounded">
                <ul class="text-muted small mb-0" style="padding-left: 1.2rem;">
                    <li><strong>2 retardos menores = 1 nota mala</strong></li>
                    <li><strong>1 retardo mayor = 1 nota mala</strong></li>
                    <li><strong>1-4 notas malas = Oficio al empleado</strong></li>
                    <li><strong>5 notas malas = Suspensión de 1 día</strong></li>
                    <li><strong>7 suspensiones/año = Terminación de nombramiento</strong></li>
                </ul>
            </div>
            
            <h6 class="mb-3" style="color: #BC955C; font-weight: 600;">
                <i class="fas fa-calendar-check me-2"></i>Control por Quincenas
            </h6>
            
            <div class="mb-3 p-3 bg-light rounded">
                <p class="text-muted small mb-2">
                    El sistema controla los retardos por quincenas:
                </p>
                <ul class="text-muted small mb-0" style="padding-left: 1.2rem;">
                    <li><strong>Quincena 1:</strong> Días 1 al 15</li>
                    <li><strong>Quincena 2:</strong> Días 16 al 31</li>
                </ul>
            </div>
            
            <div class="alert alert-warning small mb-0">
                <i class="fas fa-lightbulb me-1"></i>
                <strong>Nota:</strong> Los retardos se calculan desde la hora de entrada del horario asignado.
            </div>
        </div>
    `;
    
    document.getElementById('modalFundamentos').querySelector('.modal-title').innerHTML = '<i class="fas fa-clock me-2"></i>Retardos - Fundamento Legal';
    document.getElementById('modalFundamentos').querySelector('.modal-body').innerHTML = fundamento;
    const modal = new bootstrap.Modal(document.getElementById('modalFundamentos'));
    modal.show();
}

let tiposJustificacionCache = [];

async function cargarTiposJustificacion(filtroTipo = null) {
    const select = document.getElementById('tipo_justificacion');
    
    // Siempre renderizar las opciones con el filtro actual
    if (tiposJustificacionCache.length > 0) {
        renderOpcionesSelect(filtroTipo);
        await new Promise(r => setTimeout(r, 10));
        return Promise.resolve(tiposJustificacionCache);
    }
    
    try {
        const response = await fetch('<?= BASE_URL ?>/api/tipos-justificacion');
        const data = await response.json();
        if (data.success) {
            tiposJustificacionCache = data.data || [];
            renderOpcionesSelect(filtroTipo);
        }
    } catch (error) {
        console.error('Error cargando tipos de justificación:', error);
    }
    return Promise.resolve(tiposJustificacionCache);
}

function renderOpcionesSelect(filtroTipo = null) {
    const select = document.getElementById('tipo_justificacion');
    select.innerHTML = '<option value="">Seleccione...</option>';
    
    // Determinar el tipo de filtro
    const esExcluirRetardos = filtroTipo === 'excluir_retardos';
    const esFiltroRetardoMenor = filtroTipo === 'retardo_menor';
    const esFiltroRetardoMayor = filtroTipo === 'retardo_mayor';
    const esFiltroRetardo = esFiltroRetardoMenor || esFiltroRetardoMayor;
    
    tiposJustificacionCache.forEach(tipo => {
        const tipoInc = tipo.tipo_incidencia || '';
        const nombre = (tipo.nombre || '').toLowerCase();
        const esRetardo = tipoInc === 'retardo';
        const esRetardoMenor = esRetardo && nombre.includes('menor');
        const esRetardoMayor = esRetardo && nombre.includes('mayor');
        
        // Para incidencias: excluir retardo menor y retardo mayor
        if (esExcluirRetardos) {
            if (esRetardo) {
                return; // Excluir ambos tipos de retardo
            }
        }
        
        // Para retardos: solo mostrar retardo menor o retardo mayor según corresponda
        if (esFiltroRetardo) {
            if (esFiltroRetardoMenor && !esRetardoMenor) return;
            if (esFiltroRetardoMayor && !esRetardoMayor) return;
            if (!esRetardo) return; // Solo mostrar retardos
        }
        
        // Si no hay filtro específico, excluir retardo (para nuevos)
        if (!esExcluirRetardos && !esFiltroRetardo && filtroTipo === null) {
            if (esRetardo) return;
        }
        
        const option = document.createElement('option');
        option.value = tipoInc;
        option.dataset.tipoId = tipo.id;
        option.textContent = tipo.nombre;
        select.appendChild(option);
    });
}

// Actualizar campos de fecha según días seleccionados
function actualizarCamposDiaEconomico() {
    const diasSelect = document.getElementById('just_dias_economicos');
    const divFechaFin = document.getElementById('div_fecha_fin_eco');
    const fechaFinInput = document.getElementById('just_fecha_fin_eco');
    const fechaInicioInput = document.getElementById('just_fecha_inicio_eco');
    const fechaFinError = document.getElementById('fecha_fin_error');
    
    if (!diasSelect) return;
    
    const dias = parseInt(diasSelect.value) || 1;
    
    // Siempre limpiar fecha fin al cambiar días solicitados
    if (fechaFinInput) {
        fechaFinInput.value = '';
    }
    if (fechaFinError) {
        fechaFinError.style.display = 'none';
        fechaFinError.textContent = '';
    }
    
    // Limpiar también la fecha inicio para evitar inconsistencias
    if (fechaInicioInput) {
        fechaInicioInput.value = '';
    }
    
    if (divFechaFin) {
        if (dias >= 2) {
            divFechaFin.style.display = 'block';
        } else {
            divFechaFin.style.display = 'none';
        }
    }
}

function actualizarDiasComision() {
    const fechaInicioEl = document.getElementById('just_fecha_inicio_comision');
    const fechaFinEl = document.getElementById('just_fecha_fin_comision');
    const display = document.getElementById('just_dias_comision_display');
    const inputDias = document.getElementById('just_dias_comision');
    
    if (!fechaInicioEl || !fechaFinEl) return;
    
    const fechaInicio = fechaInicioEl.value;
    const fechaFin = fechaFinEl.value;
    
    if (!fechaInicio || !fechaFin) {
        if (display) display.textContent = '';
        if (inputDias) inputDias.value = 0;
        return;
    }
    
    const ini = new Date(fechaInicio);
    const fin = new Date(fechaFin);
    
    if (fin < ini) {
        if (display) display.innerHTML = '<span class="text-danger">La fecha fin no puede ser menor a la fecha inicio</span>';
        if (inputDias) inputDias.value = 0;
        return;
    }
    
    const diffTime = Math.abs(fin - ini);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 para incluir el día de inicio
    
    if (display) display.innerHTML = '<strong class="text-primary">' + diffDays + ' día(s) de comisión</strong>';
    if (inputDias) inputDias.value = diffDays;
}

// Validar fecha inicio para día económico
function validarFechaInicioDiaEconomico() {
    const fechaInicioInput = document.getElementById('just_fecha_inicio_eco');
    const fechaError = document.getElementById('fecha_inicio_error');
    const diasSelect = document.getElementById('just_dias_economicos');
    const dias = parseInt(diasSelect.value) || 1;
    const fechaInicio = fechaInicioInput.value;
    
    if (fechaError) {
        fechaError.style.display = 'none';
        fechaError.textContent = '';
    }
    
    if (!fechaInicio) {
        return true;
    }
    
    // Crear fecha correctamente
    const [year, month, day] = fechaInicio.split('-').map(Number);
    const fecha = new Date(year, month - 1, day);
    const diaSemana = fecha.getDay();
    
    // No iniciar en sábado (6) ni domingo (0) para cualquier cantidad de días
    if (diaSemana === 0 || diaSemana === 6) {
        if (fechaError) {
            fechaError.textContent = 'No puede iniciar en fin de semana';
            fechaError.style.display = 'block';
        }
        fechaInicioInput.value = '';
        return false;
    }
    
    if (dias >= 2) {
        // Para 2+ días: No iniciar en viernes
        if (diaSemana === 5) {
            if (fechaError) {
                fechaError.textContent = 'No se puede iniciar en viernes';
                fechaError.style.display = 'block';
            }
            fechaInicioInput.value = '';
            return false;
        }
        
        // Para 3 días: No iniciar en jueves (Jue+Vie+Sab no válido)
        if (dias >= 3 && diaSemana === 4) {
            if (fechaError) {
                fechaError.textContent = 'No se puede iniciar en jueves para 3 días';
                fechaError.style.display = 'block';
            }
            fechaInicioInput.value = '';
            return false;
        }
    }
    
    // Si es válido, actualizar fecha fin
    actualizarFechaFinDiaEconomico();
    return true;
}

// Actualizar fecha fin automáticamente
function actualizarFechaFinDiaEconomico() {
    const fechaInicioInput = document.getElementById('just_fecha_inicio_eco');
    const fechaFinInput = document.getElementById('just_fecha_fin_eco');
    const diasSelect = document.getElementById('just_dias_economicos');
    
    if (!fechaInicioInput || !fechaFinInput || !diasSelect) return;
    
    const dias = parseInt(diasSelect.value) || 1;
    const fechaInicio = fechaInicioInput.value;
    
    if (fechaInicio && dias >= 2) {
        // Crear fecha correctamente evitando problemas de timezone
        const [year, month, day] = fechaInicio.split('-').map(Number);
        let fecha = new Date(year, month - 1, day);
        
        // Sumar días laborables (dias - 1 porque el primer día ya es el de inicio)
        for (let i = 1; i < dias; i++) {
            fecha.setDate(fecha.getDate() + 1);
            const diaSemana = fecha.getDay();
            // Si es sábado (6), avanzar al lunes (1)
            if (diaSemana === 6) {
                fecha.setDate(fecha.getDate() + 2);
            }
            // Si es domingo (0), avanzar al lunes (1)
            if (diaSemana === 0) {
                fecha.setDate(fecha.getDate() + 1);
            }
        }
        
        // Formatear a YYYY-MM-DD
        const y = fecha.getFullYear();
        const m = String(fecha.getMonth() + 1).padStart(2, '0');
        const d = String(fecha.getDate()).padStart(2, '0');
        fechaFinInput.value = `${y}-${m}-${d}`;
    } else {
        fechaFinInput.value = '';
    }
}

// Calcular fecha fin para licencia médica
function calcularFechaFinLicencia() {
    const fechaInicioInput = document.getElementById('just_fecha_inicio_licencia');
    const fechaFinInput = document.getElementById('just_fecha_fin_licencia');
    const diasInput = document.getElementById('just_dias_otorgados');
    
    if (!fechaInicioInput || !fechaFinInput || !diasInput) return;
    
    const dias = parseInt(diasInput.value) || 0;
    const fechaInicio = fechaInicioInput.value;
    
    // Validar formato YYYY-MM-DD
    if (!fechaInicio || dias <= 0) {
        fechaFinInput.value = '';
        return;
    }
    
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateRegex.test(fechaInicio)) {
        return;
    }
    
    const [year, month, day] = fechaInicio.split('-').map(Number);
    
    // Validar que los valores sean correctos
    if (year < 1900 || year > 2100 || month < 1 || month > 12 || day < 1 || day > 31) {
        return;
    }
    
    // Crear fecha usando formato ISO para evitar problemas de timezone
    const fecha = new Date(year, month - 1, day);
    
    // Verificar que la fecha es válida
    if (isNaN(fecha.getTime())) {
        return;
    }
    
    // Sumar los días otorgados - 1 (porque el primer día cuenta como día 1)
    fecha.setDate(fecha.getDate() + (dias - 1));
    
    // Formatear a YYYY-MM-DD
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    fechaFinInput.value = `${y}-${m}-${d}`;
}

// Función para mostrar la antigüedad del empleado en licencia médica
async function mostrarAntiguedadLicencia() {
    const infoDiv = document.getElementById('info-antiguedad-licencia');
    if (!infoDiv) return;
    
    const empleadoId = document.getElementById('just_empleado_id')?.value;
    if (!empleadoId) {
        infoDiv.style.display = 'none';
        return;
    }
    
    try {
        const response = await fetch(`<?= BASE_URL ?>/empleados/datos-completos/${empleadoId}`);
        const data = await response.json();
        
        const fechaIngreso = data.fecha_ingreso;
        if (!fechaIngreso) {
            infoDiv.style.display = 'none';
            return;
        }
        
        // Calcular antigüedad
        const ingreso = new Date(fechaIngreso);
        const hoy = new Date();
        let anios = hoy.getFullYear() - ingreso.getFullYear();
        let meses = hoy.getMonth() - ingreso.getMonth();
        
        if (meses < 0) {
            anios--;
            meses += 12;
        }
        
        // Días máximos según antigüedad
        let diasMaximos = 15;
        if (anios >= 1 && anios < 5) diasMaximos = 30;
        else if (anios >= 5 && anios < 10) diasMaximos = 45;
        else if (anios >= 10) diasMaximos = 60;
        
        const fechaParts = fechaIngreso.split('-');
        const fechaFormateada = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;
        
        const aniosTexto = anios === 1 ? '1 año' : (anios + ' años');
        const mesesTexto = meses === 1 ? '1 mes' : (meses + ' meses');
        
        infoDiv.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fas fa-calendar me-1"></i>Fecha de Ingreso:</strong> ${fechaFormateada}
                </div>
                <div class="col-md-6">
                    <strong><i class="fas fa-clock me-1"></i>Antigüedad:</strong> ${aniosTexto}, ${mesesTexto}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <strong><i class="fas fa-calendar-alt me-1"></i>Días máximos según antigüedad:</strong> 
                    <span class="badge" style="background: linear-gradient(135deg, #235B4E, #10312B); color: white;">${diasMaximos} días</span>
                    <small class="text-muted ms-2">(según Art. 30 del Reglamento)</small>
                </div>
            </div>
        `;
        infoDiv.style.display = 'block';
        
        // Actualizar el valor máximo del campo días
        const diasInput = document.getElementById('just_dias_otorgados');
        if (diasInput) {
            diasInput.max = diasMaximos;
        }
        
    } catch (error) {
        console.error('Error al obtener antigüedad:', error);
        infoDiv.style.display = 'none';
    }
}

function cambiarTipoJustificacion() {
    // Limpiar mensaje de error anterior
    const tipoIncidenciaDiv = document.getElementById('tipo_incidencia_seleccionado');
    if (tipoIncidenciaDiv) {
        tipoIncidenciaDiv.classList.add('d-none');
        tipoIncidenciaDiv.className = 'alert alert-primary d-none';
        tipoIncidenciaDiv.innerHTML = '';
    }
    
    const select = document.getElementById('tipo_justificacion');
    let tipo = select?.value || tipoJustificacionSeleccionada;
    
    if (!tipo) {
        tipo = tipoJustificacionSeleccionada || 'retardo';
    }
    
    // Ocultar todas las secciones primero
    const seccionesOcultar = [
        'seccion-datos-retardo', 'seccion-fundamentos', 'seccion-motivo', 
        'seccion-soporte', 'seccion-lugar', 'seccion-fechas-comision',
        'seccion-dia-economico', 'seccion-licencia-medica'
    ];
    seccionesOcultar.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
    
    // Buscar el tipo_id en las opciones del select
    const selectedOption = select?.options?.[select?.selectedIndex];
    const tipoId = selectedOption?.dataset?.tipoId || tipoJustificacionCatalogoId;
    
    // Actualizar tipo_retraso_original hidden si es retardo
    const tipoRetrasoOriginalInput = document.getElementById('just_tipo_retardo_original');
    if (tipoRetrasoOriginalInput) {
        if (tipo === 'retardo_menor' || tipo === 'retardo_mayor' || tipo === 'tolerancia' || tipo === 'falta') {
            tipoRetrasoOriginalInput.value = tipo;
        }
    }
    
    // Llamar a seleccionarJustificacion para que actualice todo
    seleccionarJustificacion(tipo, tipoId);
    
    const rowTipoIncidencia = document.getElementById('row-tipo-incidencia');
    const seccionDatosRetardo = document.getElementById('seccion-datos-retardo');
    const seccionFundamentos = document.getElementById('seccion-fundamentos');
    const seccionMotivo = document.getElementById('seccion-motivo');
    const seccionSoporte = document.getElementById('seccion-soporte');
    const seccionLugar = document.getElementById('seccion-lugar');
    
    const fundamentosDisplay = document.getElementById('just_fundamentos_display');
    const fundamentosInput = document.getElementById('just_fundamentos');
    
    // Determinar el tipo de retardo (usar variable global como fallback)
    const tipoRetrasoInput = document.getElementById('just_tipo_retardo_original');
    let tipoRetrasoOriginal = tipoRetrasoInput?.value || tipoRetardoActual || '';
    
    // Si el tipo es retardo_menor o retardo_mayor, usar ese valor
    if (tipo === 'retardo_menor') tipoRetrasoOriginal = 'retardo_menor';
    if (tipo === 'retardo_mayor') tipoRetrasoOriginal = 'retardo_mayor';
    if (tipo === 'tolerancia') tipoRetrasoOriginal = 'tolerancia';
    if (tipo === 'falta') tipoRetrasoOriginal = 'falta';
    
    const esRetardo = tipo === 'retardo' || tipo === 'retardo_menor' || tipo === 'retardo_mayor' || tipo === 'tolerancia' || tipo === 'falta';
    const esComisionEntrada = tipo === 'comision_entrada';
    const esComisionSalida = tipo === 'comision_salida';
    const esComisionDia = tipo === 'comision_dia' || tipo === 'comision_todo_dia';
    const esComision = esComisionEntrada || esComisionSalida || esComisionDia;
    const esDiaEconomico = tipo === 'dia_economico';
    const esLicenciaMedica = tipo === 'licencia_medica' || tipo === 'constancia_tiempo';
    
    // Para retardo: mostrar datos en modo lectura, fundamentos como información
    if (esRetardo) {
        rowTipoIncidencia.style.display = 'block';
        seccionDatosRetardo.style.display = 'block';
        seccionFundamentos.style.display = 'block';
        seccionMotivo.style.display = 'none';
        seccionSoporte.style.display = 'none';
        seccionLugar.style.display = 'none';
        
        // Ocultar sección de día económico
        const seccionDiaEco = document.getElementById('seccion-dia-economico');
        if (seccionDiaEco) seccionDiaEco.style.display = 'none';
        
        // Ocultar sección de licencia médica
        const seccionLicenciaMedica = document.getElementById('seccion-licencia-medica');
        if (seccionLicenciaMedica) seccionLicenciaMedica.style.display = 'none';
        
        // Mostrar tipo específico de retardo y fundamentos como información
        let tipoLabel = 'Retardo';
        let fundamentosTexto = '';
        
        if (tipoRetrasoOriginal === 'tolerancia') {
            tipoLabel = 'Tolerancia (0-10 min)';
            fundamentosTexto = '<strong>(Art. 37 y 80 Inciso a) del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.)</strong><br>De 0 a 10 minutos después de su hora de entrada - No se registra.';
        } else if (tipoRetrasoOriginal === 'retardo_menor') {
            tipoLabel = 'Retardo Menor (11-20 min)';
            fundamentosTexto = '<strong>(Art. 37 y 80 Inciso a) del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.)</strong><br>Entre minuto 11 y minuto 20, después de su hora de entrada.';
        } else if (tipoRetrasoOriginal === 'retardo_mayor') {
            tipoLabel = 'Retardo Mayor (21-30 min)';
            fundamentosTexto = '<strong>(Art. 37 y 80 Inciso a) del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.)</strong><br>Entre minuto 21 y minuto 30, después de su hora de entrada.';
        } else if (tipoRetrasoOriginal === 'falta') {
            tipoLabel = 'Falta (31+ min)';
            fundamentosTexto = '<strong>(Art. 37 y 80 Inciso a) del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.)</strong><br>A partir del minuto 31, después de su hora de entrada - Sanción automática.';
        } else {
            fundamentosTexto = '<strong>(Art. 37 y 80 Inciso a) del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.)</strong>';
        }
        
        document.getElementById('just_tipo_retardo_display').textContent = tipoLabel;
        fundamentosDisplay.innerHTML = fundamentosTexto;
        fundamentosInput.value = fundamentosTexto.replace(/<[^>]*>/g, ''); // Guardar sin HTML
    }
    // Para comisión: mostrar fundamentos como información
    else if (esComision) {
        rowTipoIncidencia.style.display = 'block';
        seccionDatosRetardo.style.display = 'none';
        seccionFundamentos.style.display = 'block';
        seccionMotivo.style.display = 'block';
        seccionSoporte.style.display = 'none';
        seccionLugar.style.display = 'block';
        
        // Mostrar sección de fechas para todos los tipos de comisión
        const seccionFechasComision = document.getElementById('seccion-fechas-comision');
        if (seccionFechasComision) {
            if (esComision) {
                seccionFechasComision.style.display = 'block';
                // Establecer fecha por defecto si no está establecida
                const fechaInput = document.getElementById('just_fecha_inicio_comision');
                const fechaActual = new Date().toISOString().split('T')[0];
                if (fechaInput && !fechaInput.value) {
                    fechaInput.value = fechaActual;
                }
            } else {
                seccionFechasComision.style.display = 'none';
            }
        }
        
        // Ocultar sección de día económico
        const seccionDiaEcoCom = document.getElementById('seccion-dia-economico');
        if (seccionDiaEcoCom) seccionDiaEcoCom.style.display = 'none';
        
        // Ocultar sección de licencia médica
        const seccionLicenciaMedicaCom = document.getElementById('seccion-licencia-medica');
        if (seccionLicenciaMedicaCom) seccionLicenciaMedicaCom.style.display = 'none';
        
        let tipoLabel = '';
        let fundamentosTexto = '';
        
        if (esComisionEntrada) {
            tipoLabel = 'Comisión de Entrada';
        } else if (esComisionSalida) {
            tipoLabel = 'Comisión de Salida';
        } else if (esComisionDia) {
            tipoLabel = 'Comisión Día Completo';
        }
        
        fundamentosTexto = '<strong>ART. 2 - "DISPOSICIONES EN MATERIA DE CONTROL" - BASE 9</strong><br>' +
            'NORMAS GENERALES, PRINCIPIOS Y ELEMENTOS DE CONTROL INTERNO, PUNTO 12 DEL MANUAL ADMINISTRATIVO DE APLICACIÓN GENERAL EN MATERIA DE CONTROL INTERNO.<br>' +
            '<em>Especifique: Tipo, Lugar y Motivo de la comisión.</em>';
        
        document.getElementById('just_tipo_retardo_display').textContent = tipoLabel;
        fundamentosDisplay.innerHTML = fundamentosTexto;
        fundamentosInput.value = fundamentosTexto.replace(/<[^>]*>/g, '');
    }
    // Para día económico: mostrar fundamentos como información
    else if (esDiaEconomico) {
        rowTipoIncidencia.style.display = 'block';
        seccionDatosRetardo.style.display = 'none';
        seccionFundamentos.style.display = 'block';
        seccionMotivo.style.display = 'none';
        seccionSoporte.style.display = 'none';
        seccionLugar.style.display = 'none';
        
        // Mostrar sección de día económico
        const seccionDiaEco = document.getElementById('seccion-dia-economico');
        if (seccionDiaEco) {
            seccionDiaEco.style.display = 'block';
        }
        
        // Ocultar sección de licencia médica
        const seccionLicenciaMedicaEco = document.getElementById('seccion-licencia-medica');
        if (seccionLicenciaMedicaEco) seccionLicenciaMedicaEco.style.display = 'none';
        
        document.getElementById('just_tipo_retardo_display').textContent = 'Día Económico';
        
        const fundamentosTexto = '<strong>Criterios Operativos para el Otorgamiento de las Licencias con goce de sueldo previstas en el Art. 52 fracción III del Reglamento de las Condiciones Generales de Trabajo del Personal de S.E.P.</strong><br>Solicitarlos con un mínimo de un día hábil de anticipación.<br><br>' +
            '<strong>Modalidades:</strong><br>' +
            '- Modalidad A: 3 días continuos → 30 días de espera<br>' +
            '- Modalidad B: 2 días continuos → 15 días de espera<br>' +
            '- Modalidad C: 1 día → 7 dias de espera<br><br>' +
            '<strong>Restricciones:</strong><br>' +
            '- El empleado debe tener más de 6 meses de antigüedad<br>' +
            '- Las plazas de confianza NO aplican para esta modalidad<br>';
        
        fundamentosDisplay.innerHTML = fundamentosTexto;
        fundamentosInput.value = fundamentosTexto.replace(/<[^>]*>/g, '');
    }
    // Para licencia médica: mostrar fundamentos con tabla de días permitidos
    else if (esLicenciaMedica) {
        rowTipoIncidencia.style.display = 'block';
        seccionDatosRetardo.style.display = 'none';
        seccionFundamentos.style.display = 'block';
        seccionMotivo.style.display = 'none';
        seccionSoporte.style.display = 'none';
        seccionLugar.style.display = 'none';
        
        // Ocultar sección de día económico
        const seccionDiaEco = document.getElementById('seccion-dia-economico');
        if (seccionDiaEco) seccionDiaEco.style.display = 'none';
        
        // Mostrar sección de licencia médica
        const seccionLicenciaMedica = document.getElementById('seccion-licencia-medica');
        if (seccionLicenciaMedica) seccionLicenciaMedica.style.display = 'block';
        
        // Llamar a la función para mostrar antigüedad
        mostrarAntiguedadLicencia();
        
        // Mostrar el tipo correcto según la selección
        const tipoLabel = tipo === 'constancia_tiempo' ? 'Constancia de Tiempo' : 'Licencia Médica';
        document.getElementById('just_tipo_retardo_display').textContent = tipoLabel;
        
        // Calcular antigüedad del empleado y mostrar información completa
        const empleadoId = document.getElementById('just_empleado_id').value;
        
        // Función para calcular antigüedad y días
        const calcularAntiguedad = async (empId) => {
            try {
                const response = await fetch(`<?= BASE_URL ?>/empleados/datos-completos/${empId}`);
                const data = await response.json();
                
                // Usar datos del servidor (control_licencias) si están disponibles
                const control = data.control_licencias || {};
                const fechaIngreso = data.fecha_ingreso;
                
                if (fechaIngreso) {
                    // Formatear fecha de ingreso
                    const fechaParts = fechaIngreso.split('-');
                    const fechaIngresoStr = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;
                    
                    // Usar datos del servidor para antigüedad
                    const anios = control.anios || 0;
                    const meses = control.meses || 0;
                    
                    // Usar datos del servidor para días
                    const diasFull = control.full || 15;
                    const diasHalf = control.half || 15;
                    const fullUsados = control.full_usados || 0;
                    const halfUsados = control.half_usados || 0;
                    const sinSueldoUsados = control.sin_sueldo_usados || 0;
                    
                    const fullRestantes = Math.max(0, diasFull - fullUsados);
                    const halfRestantes = Math.max(0, diasHalf - halfUsados);
                    
                    const aniosTexto = anios === 1 ? '1 año' : (anios + ' años');
                    const mesesTexto = meses === 1 ? '1 mes' : (meses + ' meses');
                    const anioControl = control.anio_actual || new Date().getFullYear();
                    const reinicio = control.reinicio_ciclo ? '<span class="badge bg-success ms-1">Ciclo reiniciado</span>' : '';
                    
                    return `<div class="alert alert-info mt-2 mb-2" style="font-size: 0.9rem;">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <strong>Fecha de ingreso:</strong> ${fechaIngresoStr}<br>
                                <strong>Antigüedad:</strong> ${aniosTexto}, ${mesesTexto}
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Año de control: ${anioControl} ${reinicio}</small>
                            </div>
                        </div>
                        <div class="row text-center mb-2">
                            <div class="col-6">
                                <div class="border rounded p-2 bg-success-subtle">
                                    <strong class="text-success fs-5">${diasFull}</strong><br>
                                    <small><strong>Total Permitido</strong></small><br>
                                    <small class="text-success">Sueldo Íntegro</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 bg-warning-subtle">
                                    <strong class="text-warning fs-5">${diasHalf}</strong><br>
                                    <small><strong>Total Permitido</strong></small><br>
                                    <small class="text-warning">Medio Sueldo</small>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center mb-2">
                            <div class="col-4">
                                <div class="border rounded p-2 ${fullUsados > 0 ? 'bg-info-subtle' : 'bg-light'}">
                                    <strong>${fullUsados}</strong><br>
                                    <small>Usados</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 ${halfUsados > 0 ? 'bg-info-subtle' : 'bg-light'}">
                                    <strong>${halfUsados}</strong><br>
                                    <small>Usados</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 ${sinSueldoUsados > 0 ? 'bg-secondary-subtle' : 'bg-light'}">
                                    <strong>${sinSueldoUsados}</strong><br>
                                    <small>Sin Goce</small>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border rounded p-2 ${fullRestantes > 0 ? 'bg-success-subtle' : (fullUsados > 0 ? 'bg-danger-subtle' : 'bg-light')}">
                                    <strong class="fs-5">${fullRestantes}</strong><br>
                                    <small>Restantes</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 ${halfRestantes > 0 ? 'bg-warning-subtle' : (halfUsados > 0 ? 'bg-danger-subtle' : 'bg-light')}">
                                    <strong class="fs-5">${halfRestantes}</strong><br>
                                    <small>Restantes</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 text-center">
                            <span class="badge bg-success">Restantes: ${fullRestantes} días íntegro</span>
                            <span class="badge bg-warning text-dark">Restantes: ${halfRestantes} días medio</span>
                        </div>
                    </div>`;
                }
            } catch (e) {
                console.error('Error al obtener antigüedad:', e);
            }
            return '';
        };
        
        // Obtener antigüedad y construir fundamentos
        calcularAntiguedad(empleadoId).then(antiguedadHtml => {
            const fundamentosTexto = '<strong>ART. 30 - LICENCIA POR ENFERMEDAD NO PROFESIONAL</strong><br>' +
                '<em>Del Reglamento de las Condiciones Generales de Trabajo del Personal de la S.E.P.</em><br><br>' +
                '<strong>Días permitidos según antigüedad:</strong><br>' +
                '<table class="table table-sm table-bordered mt-2" style="font-size: 0.85rem;">' +
                '<thead class="table-light"><tr><th>Antigüedad</th><th>Días con sueldo íntegro</th><th>Días con medio sueldo</th></tr></thead>' +
                '<tbody>' +
                '<tr><td>Menos de 1 año</td><td>15 días</td><td>15 días</td></tr>' +
                '<tr><td>1-5 años</td><td>30 días</td><td>30 días</td></tr>' +
                '<tr><td>5-10 años</td><td>45 días</td><td>45 días</td></tr>' +
                '<tr><td>10+ años</td><td>60 días</td><td>60 días</td></tr>' +
                '</tbody></table>' +
                '<br><strong>Documentación requerida:</strong><br>' +
                '- Certificado médico con diagnóstico<br>' +
                '<br><strong style="color: #721c24;">IMPORTANTE:</strong> Terminada la licencia médica, el documento original se debe entregar al Depto. de Recursos Humanos.';
            
            fundamentosDisplay.innerHTML = antiguedadHtml + fundamentosTexto;
            fundamentosInput.value = fundamentosTexto.replace(/<[^>]*>/g, '');
        });
    }
    // Ocultar día económico y licencia médica si es otro tipo
    else {
        // Ocultar sección de datos del retardo
        const seccionDatosRetardo = document.getElementById('seccion-datos-retardo');
        if (seccionDatosRetardo) {
            seccionDatosRetardo.style.display = 'none';
        }
        
        const seccionDiaEco = document.getElementById('seccion-dia-economico');
        if (seccionDiaEco) {
            seccionDiaEco.style.display = 'none';
        }
        
        const seccionLicenciaMedica = document.getElementById('seccion-licencia-medica');
        if (seccionLicenciaMedica) {
            seccionLicenciaMedica.style.display = 'none';
        }
        
        // Mostrar fundamentos genéricos para otros tipos de justificación
        rowTipoIncidencia.style.display = 'block';
        seccionDatosRetardo.style.display = 'none';
        seccionFundamentos.style.display = 'block';
        seccionMotivo.style.display = 'none';
        seccionSoporte.style.display = 'none';
        seccionLugar.style.display = 'none';
        
        // Mostrar sección de fechas para comisión (como campo genérico)
        const seccionFechasComision = document.getElementById('seccion-fechas-comision');
        if (seccionFechasComision) {
            seccionFechasComision.style.display = 'block';
        }
        
        // Determinar label según el tipo
        let tipoLabel = '';
        let fundamentosTexto = '';
        
        if (tipo === 'vacaciones') {
            tipoLabel = 'Vacaciones';
            fundamentosTexto = '<strong>ART. 42 - VACACIONES</strong><br>' +
                'Del Reglamento de las Condiciones Generales de Trabajo del Personal de la S.E.P.<br><br>' +
                '<strong>Periodo vacacional:</strong> Se otorgará conforme al calendario oficial.<br>' +
                '<strong>Documentación requerida:</strong> Solicitud de vacaciones con validación del jefe inmediato.';
        } else if (tipo === 'cuidados_maternos' || tipo === 'cuidados_paternos') {
            tipoLabel = tipo === 'cuidados_maternos' ? 'Cuidados Maternos' : 'Cuidados Paternos';
            fundamentosTexto = '<strong>ART. 52 - LICENCIA POR CUIDADOS MATERNOS/PATERNOS</strong><br>' +
                'Del Reglamento de las Condiciones Generales de Trabajo del Personal de la S.E.P.<br><br>' +
                '<strong>Requisitos:</strong><br>' +
                '- Solicitud con mínimo 15 días de anticipación<br>' +
                '- Presentar documentación que acredite el nacimiento/adopción<br>' +
                '- Máximo 60 días naturales';
        } else if (tipo === 'PDSEP-SNTE') {
            tipoLabel = 'Programa Deportivo SEP-SNTE';
            fundamentosTexto = '<strong>PROGRAMA DEPORTIVO SEP-SNTE</strong><br>' +
                'Programa de actividades deportivas y recreativas.<br><br>' +
                '<strong>Documentación requerida:</strong> Constancia de participación oficial.';
        } else if (tipo === 'CLIDDA') {
            tipoLabel = 'CLIDDA';
            fundamentosTexto = '<strong>CLIDDA</strong><br>' +
                'Comité de Laboral, Investigación y Desarrollo Académico.<br><br>' +
                '<strong>Documentación requerida:</strong> Constancia oficial del CLIDDA.';
        } else if (tipo === 'EYR') {
            tipoLabel = 'ESTÍMULOS Y RECOMPENSAS';
            fundamentosTexto = '<strong>ESTÍMULOS Y RECOMPENSAS</strong><br>' +
                'Reconocimiento por desempeño laboral excepcional.<br><br>' +
                '<strong>Documentación requerida:</strong> Constancia de reconocimiento oficial.';
        } else if (tipo === 'FUMIGACION') {
            tipoLabel = 'FUMIGACIÓN';
            fundamentosTexto = '<strong>FUMIGACIÓN</strong><br>' +
                'Permiso por servicio de fumigación en el hogar.<br><br>' +
                '<strong>Documentación requerida:</strong> Comprobante del servicio.';
        } else if (tipo === 'permiso_fallecimiento') {
            tipoLabel = 'PERMISO POR FALLECIMIENTO';
            fundamentosTexto = '<strong>ART. 37 - PERMISO POR FALLECIMIENTO</strong><br>' +
                'Del Reglamento de las Condiciones Generales de Trabajo del Personal de la S.E.P.<br><br>' +
                '<strong>Días Permitidos:</strong><br>' +
                '- Cónyuge, padre o hijo: 5 días<br>' +
                '- Hermanos, abuelos o nietos: 3 días<br>' +
                '- Otros familiares: 1 día<br><br>' +
                '<strong>Documentación requerida:</strong> Acta de defunción y documentación que acredite el parentesco.';
        } else {
            // Mostrar la fecha pero sin fundamentos específicos
            tipoLabel = tipo ? tipo.replace(/_/g, ' ').toUpperCase() : 'Otro';
            fundamentosTexto = '<strong>Justificación especial</strong><br>' +
                'Seleccione la fecha a justificar y complete los datos requeridos.';
        }
        
        document.getElementById('just_tipo_retardo_display').textContent = tipoLabel;
        fundamentosDisplay.innerHTML = fundamentosTexto;
        fundamentosInput.value = fundamentosTexto.replace(/<[^>]*>/g, '');
    }
}

// Función para filtrar asistencia en el modal del empleado
async function filtrarAsistenciaModal(empleadoId, fechaInicio, fechaFin) {
    const tbody = document.getElementById('modal_asistencias_tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm"></div> Cargando>';
    
    try {
        let url = `<?= BASE_URL ?>/empleados/datos-completos/${empleadoId}`;
        const params = [];
        if (fechaInicio) params.push(`fecha_inicio=${fechaInicio}`);
        if (fechaFin) params.push(`fecha_fin=${fechaFin}`);
        if (params.length > 0) url += '?' + params.join('&');
        
        console.log('Fetching:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('Data received:', data.asistencias?.length, 'records');
        
        // Rebuild the entire attendance tab content
        const asistenciaDiv = document.getElementById('asistencia');
        if (!asistenciaDiv) {
            console.error('Asistencia div not found');
            return;
        }
        
        // Calculate dates fresh
        const currentYear = new Date().getFullYear();
        const currentMonth = new Date().getMonth() + 1;
        const thisMonthStart = `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
        const thisMonthEnd = new Date(currentYear, currentMonth, 0).toISOString().split('T')[0];
        
        const prevMonth = new Date(currentYear, currentMonth - 1, 1);
        const prevMonthStart = `${prevMonth.getFullYear()}-${String(prevMonth.getMonth() + 1).padStart(2, '0')}-01`;
        const prevMonthEnd = new Date(prevMonth.getFullYear(), prevMonth.getMonth() + 1, 0).toISOString().split('T')[0];
        
        // Determine active button class - solo uno puede estar activo
        let activeClass = 'btn-outline-primary';
        if (!fechaInicio && !fechaFin) {
            activeClass = 'btn-primary';
        } else if (fechaInicio === thisMonthStart && fechaFin === thisMonthEnd) {
            activeClass = 'btn-primary';
        } else if (fechaInicio === prevMonthStart && fechaFin === prevMonthEnd) {
            activeClass = 'btn-primary';
        }
        
        let thisMonthClass = (!fechaInicio && !fechaFin) ? 'btn-primary' : ((fechaInicio === thisMonthStart && fechaFin === thisMonthEnd) ? 'btn-primary' : 'btn-outline-primary');
        let prevMonthClass = (!fechaInicio && !fechaFin) ? 'btn-outline-primary' : ((fechaInicio === prevMonthStart && fechaFin === prevMonthEnd) ? 'btn-primary' : 'btn-outline-primary');
        let allClass = (!fechaInicio && !fechaFin) ? 'btn-primary' : 'btn-outline-primary';
        
        let asisHtml = `
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Registros de Asistencia - ID: <strong>${empleadoId}</strong></h6>
                    <a href="<?= BASE_URL ?>/empleados/${empleadoId}?tab=asistencia" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i> Ver en Página Completa
                    </a>
                </div>
                <div class="row g-2">
                    <div class="col-auto">
                        <label class="form-label form-label-sm mb-0">Desde:</label>
                        <input type="date" id="asistencia_fecha_inicio" class="form-control form-control-sm" value="${fechaInicio || ''}" onchange="validarFechaInicio(this, 'asistencia_fecha_fin')">
                    </div>
                    <div class="col-auto">
                        <label class="form-label form-label-sm mb-0">Hasta:</label>
                        <input type="date" id="asistencia_fecha_fin" class="form-control form-control-sm" value="${fechaFin || ''}" onchange="validarFechaFin(this, 'asistencia_fecha_inicio')">
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button type="button" class="btn btn-primary btn-sm" onclick="filtrarAsistenciaModal(${empleadoId}, document.getElementById('asistencia_fecha_inicio').value, document.getElementById('asistencia_fecha_fin').value)">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="filtrarAsistenciaModal(${empleadoId}, '', '')">
                            Limpiar
                        </button>
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <span class="text-muted small">Total: ${data.asistencias?.length || 0} registros</span>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped">
                    <thead><tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Tipo</th></tr></thead>
                    <tbody id="modal_asistencias_tbody">`;
        
        if (data.asistencias && data.asistencias.length > 0) {
            data.asistencias.forEach(a => {
                asisHtml += `<tr><td>${a.fecha || '-'}</td><td>${a.hora_entrada ? a.hora_entrada.substring(0,5) : '--'}</td><td>${a.hora_salida ? a.hora_salida.substring(0,5) : '--'}</td><td>${getTipoAsistenciaLabel(a.tipo_asistencia)}</td></tr>`;
            });
        } else {
            asisHtml += '<tr><td colspan="4" class="text-center">Sin registros</td></tr>';
        }
        
        asistenciaDiv.innerHTML = asisHtml + '</tbody></table></div>';
        
    } catch (error) {
        console.error('Error filtrando asistencia:', error);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar datos</td></tr>';
        }
    }
}

// Exponer funciones globalmente
// Función para mostrar el tipo de asistencia correctamente
function getTipoAsistenciaLabel(tipo) {
    if (!tipo || tipo === 'normal') return 'Normal';
    if (tipo === 'por_definir') return 'Por definir';
    if (tipo === 'con_comision') return 'Comisión';
    if (tipo === 'con_retardo') return 'Retardo';
    if (tipo === 'con_ausencia') return 'Ausencia';
    if (tipo === 'licencia_medica') return 'Licencia Médica';
    if (tipo === 'constancia_tiempo') return 'Constancia de Tiempo';
    if (tipo === 'dia_economico') return 'Día Económico';
    if (tipo === 'comision_entrada') return 'Comisión Entrada';
    if (tipo === 'comision_salida') return 'Comisión Salida';
    if (tipo === 'comision_todo_dia') return 'Comisión Día';
    if (tipo === 'vacaciones') return 'Vacaciones';
    if (tipo === 'cuidados_maternos') return 'Cuidados Maternos';
    if (tipo === 'PDSEP-SNTE') return 'Programa Deportivo SEP-SNTE';
    if (tipo === 'CLIDDA') return 'CLIDDA';
    if (tipo === 'EYR') return 'ESTIMULOS Y RECOMPENSAS';
    if (tipo === 'cuidados_paternos') return 'Cuidados Paternos';
    if (tipo === 'falta') return 'Falta';
    if (tipo === 'comision_entrada_otros') return 'COMISIÓN ENTRADA/OTROS';
    if (tipo === 'DE') return 'Desalojo de Edificio';
    if (tipo === 'F') return 'FUMIGACIÓN';
    if (tipo === 'sin_registro') return 'Sin Registro';
    return 'Normal';
}

// Función para filtrar y renderizar licencias médicas
function filtrarLicenciasMedicas(filtroAnio = 'todos') {
    const container = document.getElementById('licenciasmedicas-content');
    if (!container) return;
    
    const licencias = window.licenciasMedicasData || [];
    const ctrl = window.controlLicenciasData;
    const currentYear = new Date().getFullYear();
    
    // Colores Pantone para licencias médicas
    const PANTONE_LM = {
        primary: '#9F2241',
        primaryDark: '#691C32',
        secondary: '#235B4E',
        secondaryDark: '#10312B',
        secondaryLight: '#3a7d6a',
        warning: '#BC955C',
        warningLight: '#d4b07a',
        danger: '#691C32',
        dangerLight: '#a32a47',
        success: '#235B4E',
        successLight: '#3a7d6a',
        gray: '#98989A',
        grayDark: '#6F7271',
        grayLight: '#d0d0d2',
        bgLight: '#f8f7f5',
        border: '#d0d0d2'
    };
    
    // Calcular antigüedad usando datos del servidor
    let antiguedadHtml = '';
    if (ctrl) {
        const fechaIngreso = ctrl.fecha_ingreso ? new Date(ctrl.fecha_ingreso) : null;
        const fechaIngresoStr = fechaIngreso ? fechaIngreso.toLocaleDateString('es-MX') : 'No disponible';
        
        const anios = ctrl.anios || 0;
        const meses = ctrl.meses || 0;
        
        // Formatear correctamente singular/plural
        const aniosTexto = anios === 1 ? '1 año' : (anios + ' años');
        const mesesTexto = meses === 1 ? '1 mes' : (meses + ' meses');
        
        // Usar datos del servidor para días permitidos
        const diasFull = ctrl.full || 15;
        const diasHalf = ctrl.half || 15;
        
        // Calcular días usados directamente de las licencias del año actual
        const licenciasAnioActual = licencias.filter(lm => {
            const fecha = lm.fecha_inicio ? new Date(lm.fecha_inicio) : null;
            return fecha && fecha.getFullYear() === currentYear;
        });
        
        // Siempre calcular de las licencias si hay licencias registradas
        let fullUsados = 0;
        let halfUsados = 0;
        let sinSueldoUsados = 0;
        
        if (licenciasAnioActual.length > 0) {
            licenciasAnioActual.forEach(lm => {
                fullUsados += parseInt(lm.dias_full || 0);
                halfUsados += parseInt(lm.dias_half || 0);
                sinSueldoUsados += parseInt(lm.dias_sin_sueldo || 0);
            });
        } else if (ctrl) {
            // Si no hay licencias, usar datos del control
            fullUsados = ctrl.full_usados || 0;
            halfUsados = ctrl.half_usados || 0;
            sinSueldoUsados = ctrl.sin_sueldo_usados || 0;
        }
        
        const fullRestantes = Math.max(0, diasFull - fullUsados);
        const halfRestantes = Math.max(0, diasHalf - halfUsados);
        
        let categoriaAntiguedad = 'Menos de 1 año';
        if (anios >= 1 && anios < 5) categoriaAntiguedad = '1 a 4 años';
        else if (anios >= 5 && anios < 10) categoriaAntiguedad = '5 a 9 años';
        else if (anios >= 10) categoriaAntiguedad = '10 años o más';
        
        antiguedadHtml = `
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card" style="border: 2px solid ${PANTONE_LM.secondary}; border-radius: 12px; overflow: hidden;">
                        <div class="card-header" style="background: linear-gradient(135deg, ${PANTONE_LM.secondary} 0%, ${PANTONE_LM.secondaryDark} 100%); color: white;">
                            <h6 class="mb-0"><i class="fas fa-user-clock me-2"></i>Información del Empleado</h6>
                        </div>
                        <div class="card-body" style="background-color: ${PANTONE_LM.bgLight};">
                            <p class="mb-2"><strong>Fecha de ingreso:</strong> <span style="color: ${PANTONE_LM.secondaryDark};">${fechaIngresoStr}</span></p>
                            <p class="mb-2"><strong>Antigüedad:</strong> <span style="color: ${PANTONE_LM.secondaryDark};">${aniosTexto}, ${mesesTexto}</span></p>
                            <p class="mb-0"><strong>Categoría:</strong> <span class="badge" style="background-color: ${PANTONE_LM.secondary}; color: white; padding: 6px 12px; border-radius: 20px;">${categoriaAntiguedad}</span></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card" style="border: 2px solid ${PANTONE_LM.secondary}; border-radius: 12px; overflow: hidden;">
                        <div class="card-header" style="background: linear-gradient(135deg, ${PANTONE_LM.secondary} 0%, ${PANTONE_LM.secondaryDark} 100%); color: white;">
                            <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Días de Licencia Médica - Año ${ctrl.anio_actual || currentYear}</h6>
                        </div>
                        <div class="card-body" style="background-color: ${PANTONE_LM.bgLight};">
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${PANTONE_LM.success}15; border-color: ${PANTONE_LM.success}30 !important;">
                                        <strong class="d-block" style="color: ${PANTONE_LM.success}; font-size: 1.5rem;">${diasFull}</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Total</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.success};">Sueldo Íntegro</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${fullUsados > 0 ? PANTONE_LM.success : PANTONE_LM.grayLight}30; border-color: ${fullUsados > 0 ? PANTONE_LM.success : PANTONE_LM.grayLight}50 !important;">
                                        <strong class="d-block" style="color: ${fullUsados > 0 ? PANTONE_LM.success : PANTONE_LM.grayDark}; font-size: 1.5rem;">${fullUsados}</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Usados</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.success};">Sueldo Íntegro</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${fullRestantes > 0 ? PANTONE_LM.success + '20' : (fullUsados > 0 ? PANTONE_LM.danger + '20' : PANTONE_LM.grayLight + '20')}; border-color: ${fullRestantes > 0 ? PANTONE_LM.success : (fullUsados > 0 ? PANTONE_LM.danger : PANTONE_LM.grayLight)}50 !important;">
                                        <strong class="d-block" style="color: ${fullRestantes > 0 ? PANTONE_LM.success : (fullUsados > 0 ? PANTONE_LM.danger : PANTONE_LM.grayDark)}; font-size: 1.5rem;">${fullRestantes}</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Restantes</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.success};">Sueldo Íntegro</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${PANTONE_LM.warning}20; border-color: ${PANTONE_LM.warning}30 !important;">
                                        <strong class="d-block" style="color: ${PANTONE_LM.warningDark || PANTONE_LM.warning}; font-size: 1.5rem;">${diasHalf}</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Total</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.warning};">Medio Sueldo</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${halfUsados > 0 ? PANTONE_LM.warning + '30' : PANTONE_LM.grayLight + '30'}; border-color: ${halfUsados > 0 ? PANTONE_LM.warning : PANTONE_LM.grayLight}50 !important;">
                                        <strong class="d-block" style="color: ${halfUsados > 0 ? (PANTONE_LM.warningDark || PANTONE_LM.warning) : PANTONE_LM.grayDark}; font-size: 1.5rem;">${halfUsados}</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Usados</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.warning};">Medio Sueldo</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${halfRestantes > 0 ? PANTONE_LM.warning + '20' : (halfUsados > 0 ? PANTONE_LM.danger + '20' : PANTONE_LM.grayLight + '20')}; border-color: ${halfRestantes > 0 ? PANTONE_LM.warning : (halfUsados > 0 ? PANTONE_LM.danger : PANTONE_LM.grayLight)}50 !important;">
                                        <strong class="d-block" style="color: ${halfRestantes > 0 ? (PANTONE_LM.warningDark || PANTONE_LM.warning) : (halfUsados > 0 ? PANTONE_LM.danger : PANTONE_LM.grayDark)}; font-size: 1.5rem;">${halfRestantes}</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Restantes</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.warning};">Medio Sueldo</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${PANTONE_LM.gray}20; border-color: ${PANTONE_LM.gray}30 !important;">
                                        <strong class="d-block" style="color: ${PANTONE_LM.grayDark}; font-size: 1.5rem;">-</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Total</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.gray};">Sin Goce</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${sinSueldoUsados > 0 ? PANTONE_LM.gray + '30' : PANTONE_LM.grayLight + '30'}; border-color: ${sinSueldoUsados > 0 ? PANTONE_LM.gray : PANTONE_LM.grayLight}50 !important;">
                                        <strong class="d-block" style="color: ${sinSueldoUsados > 0 ? PANTONE_LM.grayDark : PANTONE_LM.gray}; font-size: 1.5rem;">${sinSueldoUsados}</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Usados</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.gray};">Sin Goce</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3" style="background-color: ${PANTONE_LM.grayLight}30; border-color: ${PANTONE_LM.grayLight}50 !important;">
                                        <strong class="d-block" style="color: ${PANTONE_LM.gray}; font-size: 1.5rem;">-</strong>
                                        <small class="d-block" style="color: ${PANTONE_LM.grayDark};"><strong>Restantes</strong></small>
                                        <small class="d-block" style="color: ${PANTONE_LM.gray};">Sin Goce</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
    }
    
    // Filtrar licencias por año
    let licenciasFiltradas = licencias;
    if (filtroAnio !== 'todos') {
        licenciasFiltradas = licencias.filter(lm => {
            const fecha = lm.fecha_inicio ? new Date(lm.fecha_inicio) : null;
            return fecha && fecha.getFullYear().toString() === filtroAnio;
        });
    }
    
    // Agrupar licencias por año y mes
    const porAnio = {};
    const porMes = {};
    licenciasFiltradas.forEach(lm => {
        const fecha = lm.fecha_inicio ? new Date(lm.fecha_inicio) : null;
        if (!fecha) return;
        
        const anio = fecha.getFullYear();
        const mes = fecha.getMonth() + 1;
        
        if (!porAnio[anio]) porAnio[anio] = { licencias: [], totalFull: 0, totalHalf: 0, totalSinGoce: 0 };
        porAnio[anio].licencias.push(lm);
        
        // Calcular días por tipo
        const diasFull = parseInt(lm.dias_full || 0);
        const diasHalf = parseInt(lm.dias_half || 0);
        const diasSinGoce = parseInt(lm.dias_sin_sueldo || 0);
        porAnio[anio].totalFull += diasFull;
        porAnio[anio].totalHalf += diasHalf;
        porAnio[anio].totalSinGoce += diasSinGoce;
        
        const keyMes = `${anio}-${mes}`;
        if (!porMes[keyMes]) porMes[keyMes] = { anio, mes, licencias: [], totalDias: 0, totalFull: 0, totalHalf: 0, totalSinGoce: 0 };
        porMes[keyMes].licencias.push(lm);
        porMes[keyMes].totalDias += parseInt(lm.dias_otorgados || lm.dias || 0);
        porMes[keyMes].totalFull += diasFull;
        porMes[keyMes].totalHalf += diasHalf;
        porMes[keyMes].totalSinGoce += diasSinGoce;
    });
    
    // Obtener años disponibles
    const aniosDisponibles = Object.keys(porAnio).sort().reverse();
    const mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    // Generar HTML de licencias
    let licenciasHtml = '';
    
    if (licenciasFiltradas.length === 0) {
        licenciasHtml = `
            <div class="text-center py-5">
                <i class="fas fa-user-md" style="font-size: 3rem; color: ${PANTONE_LM.secondary}40;"></i>
                <p class="mt-3 mb-0" style="color: ${PANTONE_LM.grayDark};">No hay licencias médicas registradas</p>
            </div>`;
    } else {
        // Mostrar licencias por año
        aniosDisponibles.forEach(anio => {
            const datosAnio = porAnio[anio];
            const licenciasAnio = datosAnio.licencias;
            const totalDiasAnio = licenciasAnio.reduce((sum, lm) => sum + parseInt(lm.dias_otorgados || lm.dias || 0), 0);
            
            // Agrupar por mes para este año
            const mesesDelAnio = [...new Set(licenciasAnio.map(lm => {
                const f = lm.fecha_inicio ? new Date(lm.fecha_inicio) : null;
                return f ? f.getMonth() + 1 : 0;
            }))].sort((a, b) => b - a);
            
            licenciasHtml += `
                <div class="card mb-4" style="border-radius: 12px; overflow: hidden; border: 1px solid ${PANTONE_LM.border};">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, ${PANTONE_LM.secondary} 0%, ${PANTONE_LM.secondaryDark} 100%); color: white;">
                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Año ${anio}</h6>
                        <div>
                            <span class="badge me-1" style="background-color: ${PANTONE_LM.success}; color: white;">Íntegro: ${datosAnio.totalFull}</span>
                            <span class="badge me-1" style="background-color: ${PANTONE_LM.warning}; color: white;">Medio: ${datosAnio.totalHalf}</span>
                            <span class="badge" style="background-color: ${PANTONE_LM.gray}; color: white;">Sin Goce: ${datosAnio.totalSinGoce}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">`;
            
            // Mostrar por mes
            mesesDelAnio.forEach(mes => {
                const licenciasMes = licenciasAnio.filter(lm => {
                    const f = lm.fecha_inicio ? new Date(lm.fecha_inicio) : null;
                    return f && f.getMonth() + 1 === mes;
                });
                
                const totalFullMes = licenciasMes.reduce((sum, lm) => sum + parseInt(lm.dias_full || 0), 0);
                const totalHalfMes = licenciasMes.reduce((sum, lm) => sum + parseInt(lm.dias_half || 0), 0);
                const totalSinGoceMes = licenciasMes.reduce((sum, lm) => sum + parseInt(lm.dias_sin_sueldo || 0), 0);
                const totalDiasMes = licenciasMes.reduce((sum, lm) => sum + parseInt(lm.dias_otorgados || lm.dias || 0), 0);
                
                licenciasHtml += `
                        <div class="mb-0">
                            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center" style="background-color: ${PANTONE_LM.bgLight};">
                                <strong style="color: ${PANTONE_LM.secondaryDark};">${mesesNombres[mes]} ${anio}</strong>
                                <div>
                                    <span class="badge me-1" style="background-color: ${PANTONE_LM.success}20; color: ${PANTONE_LM.success};">Íntegro: ${totalFullMes}</span>
                                    <span class="badge me-1" style="background-color: ${PANTONE_LM.warning}20; color: ${PANTONE_LM.warningDark || PANTONE_LM.warning};">Medio: ${totalHalfMes}</span>
                                    <span class="badge" style="background-color: ${PANTONE_LM.gray}20; color: ${PANTONE_LM.grayDark};">Sin Goce: ${totalSinGoceMes}</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead style="background-color: ${PANTONE_LM.secondary}15;">
                                        <tr>
                                            <th style="color: ${PANTONE_LM.secondaryDark}; padding: 12px;">Folio</th>
                                            <th style="color: ${PANTONE_LM.secondaryDark}; padding: 12px;">Fecha Inicio</th>
                                            <th style="color: ${PANTONE_LM.secondaryDark}; padding: 12px;">Fecha Fin</th>
                                            <th style="color: ${PANTONE_LM.secondaryDark}; padding: 12px;">Días</th>
                                            <th style="color: ${PANTONE_LM.secondaryDark}; padding: 12px;">Íntegro</th>
                                            <th style="color: ${PANTONE_LM.secondaryDark}; padding: 12px;">Medio</th>
                                            <th style="color: ${PANTONE_LM.secondaryDark}; padding: 12px;">Sin Goce</th>
                                            <th style="color: ${PANTONE_LM.secondaryDark}; padding: 12px;">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                
                licenciasMes.forEach(lm => {
                    const fechaInicio = lm.fecha_inicio ? new Date(lm.fecha_inicio).toLocaleDateString('es-MX') : '-';
                    const fechaFin = lm.fecha_fin ? new Date(lm.fecha_fin).toLocaleDateString('es-MX') : '-';
                    const dias = lm.dias_otorgados || lm.dias || 0;
                    const diasFull = lm.dias_full || 0;
                    const diasHalf = lm.dias_half || 0;
                    const diasSinGoce = lm.dias_sin_sueldo || 0;
                    const folio = lm.folio || '-';
                    const diagnostico = lm.diagnostico || '';
                    const estado = lm.estatus || lm.estado || 'pendiente';
                    
                    let estadoColor = PANTONE_LM.gray;
                    let estadoBg = PANTONE_LM.gray + '20';
                    if (estado === 'activa' || estado === 'pendiente') { estadoColor = PANTONE_LM.success; estadoBg = PANTONE_LM.success + '20'; }
                    else if (estado === 'vencida') { estadoColor = PANTONE_LM.warning; estadoBg = PANTONE_LM.warning + '20'; }
                    else if (estado === 'cancelada') { estadoColor = PANTONE_LM.danger; estadoBg = PANTONE_LM.danger + '20'; }
                    
                    licenciasHtml += `
                        <tr style="border-bottom: 1px solid ${PANTONE_LM.border}50;">
                            <td style="padding: 12px;"><small>${folio}</small></td>
                            <td style="padding: 12px;">${fechaInicio}</td>
                            <td style="padding: 12px;">${fechaFin}</td>
                            <td style="padding: 12px;"><strong>${dias}</strong></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${PANTONE_LM.success}20; color: ${PANTONE_LM.success};">${diasFull}</span></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${PANTONE_LM.warning}20; color: ${PANTONE_LM.warningDark || PANTONE_LM.warning};">${diasHalf}</span></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${PANTONE_LM.gray}20; color: ${PANTONE_LM.grayDark};">${diasSinGoce}</span></td>
                            <td style="padding: 12px;"><span class="badge" style="background-color: ${estadoBg}; color: ${estadoColor};">${estado}</span></td>
                        </tr>`;
                });
                
                licenciasHtml += `
                                    </tbody>
                                </table>
                            </div>
                        </div>`;
            });
            
            licenciasHtml += `
                    </div>
                </div>`;
        });
    }
    
    // Calcular totales globales
    const totalGlobalFull = licenciasFiltradas.reduce((sum, lm) => sum + parseInt(lm.dias_full || 0), 0);
    const totalGlobalHalf = licenciasFiltradas.reduce((sum, lm) => sum + parseInt(lm.dias_half || 0), 0);
    const totalGlobalSinGoce = licenciasFiltradas.reduce((sum, lm) => sum + parseInt(lm.dias_sin_sueldo || 0), 0);
    
    // Generar select de filtros
    const aniosOpciones = aniosDisponibles.map(a => `<option value="${a}" ${filtroAnio === a ? 'selected' : ''}>${a}</option>`).join('');
    
    const filtrosHtml = `
        <div class="row mb-3 g-3">
            <div class="col-md-4">
                <label class="form-label" style="color: ${PANTONE_LM.secondaryDark};"><strong><i class="fas fa-filter me-1"></i>Filtrar por año:</strong></label>
                <select class="form-select" style="border-color: ${PANTONE_LM.secondary}30;" onchange="filtrarLicenciasMedicas(this.value)">
                    <option value="todos" ${filtroAnio === 'todos' ? 'selected' : ''}>Todos los años</option>
                    ${aniosOpciones}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: ${PANTONE_LM.secondaryDark};"><strong><i class="fas fa-list me-1"></i>Total:</strong></label>
                <div class="form-control-plaintext" style="color: ${PANTONE_LM.secondaryDark}; font-weight: 600;">${licenciasFiltradas.length}</div>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: ${PANTONE_LM.success};"><strong><i class="fas fa-check-circle me-1"></i>Íntegro:</strong></label>
                <div class="form-control-plaintext fw-bold" style="color: ${PANTONE_LM.success}; font-size: 1.2rem;">${totalGlobalFull}</div>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: ${PANTONE_LM.warning};"><strong><i class="fas fa-minus-circle me-1"></i>Medio:</strong></label>
                <div class="form-control-plaintext fw-bold" style="color: ${PANTONE_LM.warningDark || PANTONE_LM.warning}; font-size: 1.2rem;">${totalGlobalHalf}</div>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: ${PANTONE_LM.grayDark};"><strong><i class="fas fa-times-circle me-1"></i>Sin Goce:</strong></label>
                <div class="form-control-plaintext fw-bold" style="color: ${PANTONE_LM.grayDark}; font-size: 1.2rem;">${totalGlobalSinGoce}</div>
            </div>
        </div>`;
    
    container.innerHTML = `
        ${antiguedadHtml}
        ${filtrosHtml}
        ${licenciasHtml}`;
}

// Función para abrir el modal de edición de empleado
async function abrirModalEditar(id) {
    const modalEl = document.getElementById('modalEditarEmpleado');
    if (!modalEl) {
        console.error('Modal de edición no encontrado');
        return;
    }
    
    // Limpiar el formulario
    const form = document.getElementById('formEditarEmpleado');
    form.reset();
    
    // Limpiar previews de foto
    document.getElementById('edit_foto-actual').innerHTML = '';
    document.getElementById('edit_image-preview').style.display = 'none';
    
    // Mostrar loading
    document.getElementById('edit_empleado_id').value = id;
    
    try {
        const response = await fetch(BASE_URL + '/empleados/datos-completos/' + id);
        const data = await response.json();
        
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        // Llenar el formulario con los datos del empleado
        document.getElementById('edit_empleado_id').value = data.id;
        
        // Separar nombre y apellido
        const nombreCompleto = data.nombre || '';
        const apellidoCompleto = data.apellido || '';
        const nombresArray = nombreCompleto.split(' ');
        const primerNombre = nombresArray[0] || '';
        const restanteNombres = nombresArray.slice(1).join(' ') || '';
        
        const apellidoArray = apellidoCompleto.split(' ');
        const primerApellido = apellidoArray[0] || '';
        const segundoApellido = apellidoArray.slice(1).join(' ') || '';
        
        document.getElementById('edit_primer_apellido').value = primerApellido;
        document.getElementById('edit_segundo_apellido').value = segundoApellido;
        document.getElementById('edit_nombres').value = primerNombre + (restanteNombres ? ' ' + restanteNombres : '');
        document.getElementById('edit_rfc').value = data.rfc || '';
        document.getElementById('edit_curp').value = data.curp || '';
        document.getElementById('edit_sexo').value = data.sexo || '';
        document.getElementById('edit_fecha_nacimiento').value = data.fecha_nacimiento || '';
        document.getElementById('edit_entidad_federativa').value = data.entidad_federativa || '';
        
        // Seleccionar el jefe directo en el dropdown usando la clave del catálogo
        const editJefeSelect = document.getElementById('edit_jefe_directo_id');
        if (editJefeSelect && data.jefe_directo_clave) {
            editJefeSelect.value = data.jefe_directo_clave;
        }
        
        document.getElementById('edit_area').value = data.area || '';
        document.getElementById('edit_clave_depto_hidden').value = data.clave_depto || '';
        document.getElementById('edit_clave_depto_display').value = data.clave_depto || '';
        document.getElementById('edit_jerarquia').value = data.jerarquia || 'Empleado';
        document.getElementById('edit_activo').value = data.activo || '1';
        
        // Mostrar foto actual
        if (data.foto_cara) {
            const fotoHtml = `<img src="${BASE_URL}/${data.foto_cara}" class="img-thumbnail" style="max-height: 100px;">`;
            document.getElementById('edit_foto-actual').innerHTML = fotoHtml;
        }
        
        // Auto-fill area when mando is already selected (after setting value)
        if (editJefeSelect) {
            const selectedOption = editJefeSelect.options[editJefeSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.area) {
                document.getElementById('edit_area').value = selectedOption.dataset.area;
                document.getElementById('edit_clave_depto_hidden').value = selectedOption.dataset.clave || '';
                document.getElementById('edit_clave_depto_display').value = selectedOption.dataset.clave || '';
            }
        }
        
        // Mostrar el modal
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        
    } catch (error) {
        console.error('Error al cargar datos del empleado:', error);
        alert('Error al cargar los datos del empleado');
    }
}

// Guardar edición del empleado
document.getElementById('btnGuardarEdicion').addEventListener('click', async function() {
    const form = document.getElementById('formEditarEmpleado');
    const formData = new FormData(form);
    
    // Combinar nombres y apellidos
    const primerApellido = formData.get('primer_apellido') || '';
    const segundoApellido = formData.get('segundo_apellido') || '';
    const nombres = formData.get('nombres') || '';
    
    formData.set('apellido', (primerApellido + ' ' + segundoApellido).trim());
    formData.set('nombre', nombres);
    formData.delete('primer_apellido');
    formData.delete('segundo_apellido');
    formData.delete('nombres');
    
    // Si no se seleccionó foto nueva, eliminar ese campo
    const fotoInput = document.getElementById('edit_foto_cara');
    if (fotoInput && fotoInput.files.length === 0) {
        formData.delete('foto_cara');
    }
    
    const empleadoId = document.getElementById('edit_empleado_id').value;
    const csrfToken = document.getElementById('edit_csrf_token').value;
    formData.append('csrf_token', csrfToken);
    
    try {
        const response = await fetch(BASE_URL + '/empleados/edit', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Cerrar modal
            const modalEditar = bootstrap.Modal.getInstance(document.getElementById('modalEditarEmpleado'));
            if (modalEditar) modalEditar.hide();
            // Mostrar toast de éxito
            mostrarToast('Empleado actualizado correctamente', 'success');
            // Recargar la página para ver los cambios
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Error: ' + (result.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error al guardar:', error);
        alert('Error al guardar los cambios');
    }
});

// Auto-fill area when mando is selected in edit modal
document.getElementById('edit_jefe_directo_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const areaMando = selectedOption.dataset.area || '';
    const claveArea = selectedOption.dataset.clave || '';
    
    if (areaMando) {
        document.getElementById('edit_area').value = areaMando;
    }
    if (claveArea) {
        document.getElementById('edit_clave_depto_hidden').value = claveArea;
        document.getElementById('edit_clave_depto_display').value = claveArea;
    } else {
        document.getElementById('edit_clave_depto_hidden').value = '';
        document.getElementById('edit_clave_depto_display').value = '';
    }
});

// Preview de foto
document.getElementById('edit_foto_cara').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('edit_image-preview');
    const previewImg = document.getElementById('edit_preview-img');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

window.verEmpleado = verEmpleado;
window.abrirModalEditar = abrirModalEditar;
window.abrirModalJustificarRetardo = abrirModalJustificarRetardo;
window.abrirModalJustificarIncidencia = abrirModalJustificarIncidencia;
window.abrirModalJustificar = abrirModalJustificar;

window.guardarJustificacion = guardarJustificacion;
window.mostrarFundamentos = mostrarFundamentos;
window.cambiarTipoJustificacion = cambiarTipoJustificacion;
window.calcularFechaFinLicencia = calcularFechaFinLicencia;
window.mostrarAntiguedadLicencia = mostrarAntiguedadLicencia;
window.filtrarLicenciasMedicas = filtrarLicenciasMedicas;
window.abrirModalAsignarCiclo = abrirModalAsignarCiclo;
window.guardarCicloAsignado = guardarCicloAsignado;
window.formatDate = formatDate;
window.verDetalleCiclo = verDetalleCiclo;
window.eliminarCicloAsignado = eliminarCicloAsignado;
window.recargarCiclos = recargarCiclos;
window.filtrarAsistenciaModal = filtrarAsistenciaModal;
window.getTipoAsistenciaLabel = getTipoAsistenciaLabel;

// Inicializar datos de licencias médicas cuando se cargan
window.actualizarLicenciasMedicasData = function(licencias) {
    window.licenciasMedicasOriginal = licencias || [];
};

// Event listener para el formulario de asignación de ciclos
document.addEventListener('DOMContentLoaded', function() {
    const formAsignarCiclo = document.getElementById('formAsignarCiclo');
    if (formAsignarCiclo) {
        formAsignarCiclo.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarCicloAsignado();
        });
    }
});

// Limpiar campos de día económico cuando se cierre el modal
document.getElementById('modalJustificacion').addEventListener('hidden.bs.modal', function() {
    const seccionDiaEco = document.getElementById('seccion-dia-economico');
    if (seccionDiaEco) {
        seccionDiaEco.style.display = 'none';
    }
    document.getElementById('just_dias_economicos').value = '1';
    document.getElementById('just_fecha_inicio_eco').value = '';
    document.getElementById('just_fecha_fin_eco').value = '';
    const divFechaFin = document.getElementById('div_fecha_fin_eco');
    if (divFechaFin) {
        divFechaFin.style.display = 'none';
    }
});

// Limpiar modal de empleado cuando se cierre
document.getElementById('empleadoModal').addEventListener('hidden.bs.modal', function() {
    // Remover cualquier backdrop orphan
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(bp => bp.remove());
    
    // Remover clase modal-open del body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});

function editarComision(c) {
    document.getElementById('ec_id').value = c.id;
    document.getElementById('ec_empleado_id').value = c.empleado_id;
    document.getElementById('ec_descripcion').value = c.descripcion || '';
    document.getElementById('ec_fecha_inicio').value = c.fecha_inicio || '';
    document.getElementById('ec_fecha_fin').value = c.fecha_fin || '';
    const estatusText = {pendiente: 'Pendiente', aprobada: 'Aprobada', aprobado: 'Aprobada', rechazada: 'Rechazada', rechazado: 'Rechazada'};
    document.getElementById('ec_estatus').value = estatusText[c.estatus] || 'Pendiente';
    document.getElementById('ec_tipo').value = c.tipo_comision || 'comision_todo_dia';
    document.getElementById('ec_errores').classList.add('d-none');
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarComision'));
    modal.show();
}

function guardarEdicionComision() {
    const id = document.getElementById('ec_id').value;
    const empleado_id = document.getElementById('ec_empleado_id').value;
    const descripcion = document.getElementById('ec_descripcion').value.trim();
    const fecha_inicio = document.getElementById('ec_fecha_inicio').value;
    const fecha_fin = document.getElementById('ec_fecha_fin').value;
    const tipo = document.getElementById('ec_tipo').value;
    const erroresDiv = document.getElementById('ec_errores');
    
    if (!descripcion) {
        erroresDiv.textContent = 'La descripción es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    if (!fecha_inicio) {
        erroresDiv.textContent = 'La fecha de inicio es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    erroresDiv.classList.add('d-none');
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/comisiones/actualizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id, empleado_id, descripcion, fecha_inicio, fecha_fin, tipo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarComision'));
            if (modal) modal.hide();
            window.location.reload();
        } else {
            erroresDiv.textContent = data.error || 'Error al guardar';
            erroresDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        erroresDiv.textContent = 'Error de conexión: ' + error.message;
        erroresDiv.classList.remove('d-none');
    });
}

function editarDiaEconomico(d) {
    document.getElementById('ede_id').value = d.id;
    document.getElementById('ede_empleado_id').value = d.empleado_id;
    document.getElementById('ede_fecha').value = d.fecha || '';
    document.getElementById('ede_dias').value = d.dias_solicitados || 1;
    document.getElementById('ede_modalidad').value = d.modalidad || 'A';
    document.getElementById('ede_motivo').value = d.motivo || '';
    const estatusText = {pendiente: 'Pendiente', aprobado: 'Aprobado', rechazado: 'Rechazado'};
    document.getElementById('ede_estatus').value = estatusText[d.estatus] || 'Pendiente';
    document.getElementById('ede_errores').classList.add('d-none');
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarDiaEconomico'));
    modal.show();
}

function guardarEdicionDiaEconomico() {
    const id = document.getElementById('ede_id').value;
    const empleado_id = document.getElementById('ede_empleado_id').value;
    const fecha = document.getElementById('ede_fecha').value;
    const dias_solicitados = document.getElementById('ede_dias').value;
    const modalidad = document.getElementById('ede_modalidad').value;
    const motivo = document.getElementById('ede_motivo').value.trim();
    const erroresDiv = document.getElementById('ede_errores');
    
    if (!fecha) {
        erroresDiv.textContent = 'La fecha es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    if (!motivo) {
        erroresDiv.textContent = 'El motivo es requerido';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    erroresDiv.classList.add('d-none');
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/dias-economicos/actualizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, empleado_id, fecha, dias_solicitados, modalidad, motivo })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarDiaEconomico'));
            if (modal) modal.hide();
            window.location.reload();
        } else {
            erroresDiv.textContent = data.error || 'Error al guardar';
            erroresDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        erroresDiv.textContent = 'Error de conexión: ' + error.message;
        erroresDiv.classList.remove('d-none');
    });
}

function editarRegistroTab(d, tabla) {
    const titulo = {vacaciones:'Vacación', licencias_medicas:'Licencia Médica', cuidados_maternos:'Cuidado Materno', cuidados_paternos:'Cuidado Paterno', constancias_tiempo:'Constancia de Tiempo', sanciones:'Sanción'};
    document.getElementById('modalEditarRegistroTitulo').textContent = 'Editar ' + (titulo[tabla] || 'Registro');
    document.getElementById('er_id').value = d.id;
    document.getElementById('er_empleado_id').value = d.empleado_id;
    document.getElementById('er_tabla').value = tabla;
    document.getElementById('er_fecha_inicio').value = d.fecha_inicio || '';
    document.getElementById('er_fecha_fin').value = d.fecha_fin || '';
    const estatusMap = {pendiente:'Pendiente', aprobada:'Aprobada', aprobado:'Aprobada', rechazada:'Rechazada', rechazado:'Rechazada', activa:'Activa', cumplida:'Cumplida', cancelada:'Cancelada'};
    document.getElementById('er_estatus').value = estatusMap[d.estatus] || 'Pendiente';
    document.getElementById('er_dias').value = d.dias_solicitados || d.dias_otorgados || d.dias || 0;
    document.getElementById('er_motivo').value = d.motivo || '';
    const fieldDiag = document.getElementById('er_field_diagnostico');
    const fieldTipo = document.getElementById('er_field_tipo');
    if (tabla === 'licencias_medicas') {
        fieldDiag.style.display = '';
        document.getElementById('er_label_motivo').textContent = 'Diagnóstico';
        document.getElementById('er_motivo').value = d.diagnostico || '';
        document.getElementById('er_diagnostico').value = d.diagnostico || '';
        document.getElementById('er_dias_full').value = d.dias_full || 0;
        document.getElementById('er_dias_half').value = d.dias_half || 0;
        fieldTipo.style.display = 'none';
    } else {
        fieldDiag.style.display = 'none';
        document.getElementById('er_label_motivo').textContent = 'Motivo';
    }
    if (tabla === 'constancias_tiempo') {
        fieldTipo.style.display = '';
        document.getElementById('er_label_tipo').textContent = 'Tipo Constancia';
        const sel = document.getElementById('er_tipo');
        sel.innerHTML = '<option value="medica">Médica</option><option value="legal">Legal</option><option value="oficial">Oficial</option>';
        sel.value = d.tipo_constancia || 'medica';
    } else if (tabla === 'sanciones') {
        fieldTipo.style.display = '';
        document.getElementById('er_label_tipo').textContent = 'Tipo Sanción';
        const sel = document.getElementById('er_tipo');
        sel.innerHTML = '<option value="amonestacion">Amonestación</option><option value="suspension">Suspensión</option><option value="acta_administrativa">Acta Administrativa</option><option value="otro">Otro</option>';
        sel.value = d.tipo_sancion || d.tipo || 'amonestacion';
    } else {
        fieldTipo.style.display = 'none';
    }
    document.getElementById('er_errores').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalEditarRegistro')).show();
}

function guardarEdicionRegistroTab() {
    const id = document.getElementById('er_id').value;
    const empleado_id = document.getElementById('er_empleado_id').value;
    const tabla = document.getElementById('er_tabla').value;
    const fecha_inicio = document.getElementById('er_fecha_inicio').value;
    const fecha_fin = document.getElementById('er_fecha_fin').value;
    const dias = document.getElementById('er_dias').value;
    const motivo = document.getElementById('er_motivo').value.trim();
    const tipo = document.getElementById('er_tipo').value;
    const diagnostico = document.getElementById('er_diagnostico')?.value?.trim() || '';
    const dias_full = document.getElementById('er_dias_full')?.value || 0;
    const dias_half = document.getElementById('er_dias_half')?.value || 0;
    const erroresDiv = document.getElementById('er_errores');
    if (!fecha_inicio) {
        erroresDiv.textContent = 'La fecha de inicio es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    erroresDiv.classList.add('d-none');
    let payload = { id, empleado_id, tabla, fecha_inicio, fecha_fin, dias, motivo };
    if (tabla === 'licencias_medicas') { payload.diagnostico = diagnostico; payload.dias_full = dias_full; payload.dias_half = dias_half; }
    else if (tabla === 'constancias_tiempo' || tabla === 'sanciones') { payload.tipo = tipo; }
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/api/actualizar-registro-tab', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarRegistro'));
            if (modal) modal.hide();
            window.location.reload();
        } else {
            erroresDiv.textContent = data.error || 'Error al guardar';
            erroresDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        erroresDiv.textContent = 'Error de conexión: ' + error.message;
        erroresDiv.classList.remove('d-none');
    });
}

// Modal para solicitar comisión directa
function abrirModalComision() {
    console.log('abriendo modal comision...');
    // Get current empleado ID from global variable
    const empId = window.empleadoId || window.empleadoIdGlobal;
    console.log('empleadoId:', empId);
    
    if (!empId) {
        alert('Error: No se pudo obtener el ID del empleado');
        return;
    }
    
    const modalHtml = `
    <div class="modal fade" id="modalSolicitarComision" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-briefcase me-2"></i>Solicitar Comisión</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-solicitar-comision">
                        <input type="hidden" id="sc_empleado_id" name="empleado_id" value="${empId}">
                        <input type="hidden" id="sc_asistencia_id" name="asistencia_id" value="0">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Comisión</label>
                            <select class="form-select" id="sc_tipo_comision" name="tipo_justificacion" onchange="actualizarCamposComision()">
                                <option value="comision_todo_dia">Comisión de día completo</option>
                                <option value="comision_entrada">Comisión de entrada</option>
                                <option value="comision_salida">Comisión de salida</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-map-marker-alt me-1"></i>Lugar de la Comisión</label>
                            <input type="text" class="form-control" id="sc_lugar" name="lugar_comision" placeholder="Indique el lugar donde se realizará la comisión" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-comment me-1"></i>Motivo de la Comisión</label>
                            <textarea class="form-control" id="sc_motivo" name="motivo_comision" rows="2" placeholder="Describa el motivo de la comisión" required></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-1"></i>Fecha de Inicio</label>
                                <input type="date" class="form-control" id="sc_fecha_inicio" name="fecha_inicio_comision" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-calendar-check me-1"></i>Fecha de Fin</label>
                                <input type="date" class="form-control" id="sc_fecha_fin" name="fecha_fin_comision" onchange="actualizarDiasComisionDirecta()">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted" id="sc_dias_display"></small>
                        </div>
                        
                        <div class="mb-3" id="seccion-fundamentos-comision">
                            <label class="form-label fw-bold"><i class="fas fa-gavel me-1"></i>Fundamento Normativo</label>
                            <select class="form-select" id="sc_fundamento" name="fundamento_tipo">
                                <option value="">Seleccione (opcional)</option>
                                <option value="Comisión de trabajo - Reunión externa">Comisión de trabajo - Reunión externa</option>
                                <option value="Comisión de trabajo - Capacitación externa">Comisión de trabajo - Capacitación externa</option>
                                <option value="Comisión de trabajo - Supervisión de campo">Comisión de trabajo - Supervisión de campo</option>
                                <option value="Comisión de trabajo - Trámite oficial">Comisión de trabajo - Trámite oficial</option>
                                <option value="Comisión de trabajo - Evento oficial">Comisión de trabajo - Evento oficial</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        
                        <div id="sc_errores" class="alert alert-danger d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarComisionDirecta()">
                        <i class="fas fa-save me-1"></i>Enviar Solicitud
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('modalSolicitarComision');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('sc_fecha_inicio').value = today;
    document.getElementById('sc_fecha_fin').value = today;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitarComision'));
    modal.show();
}

function actualizarCamposComision() {
    const tipo = document.getElementById('sc_tipo_comision').value;
    const fechaInicio = document.getElementById('sc_fecha_inicio');
    const fechaFin = document.getElementById('sc_fecha_fin');
    
    if (tipo === 'comision_todo_dia') {
        fechaInicio.required = true;
        fechaFin.required = true;
    } else {
        fechaInicio.required = true;
        fechaFin.required = false;
        fechaFin.value = fechaInicio.value;
    }
    actualizarDiasComisionDirecta();
}

function actualizarDiasComisionDirecta() {
    const inicio = document.getElementById('sc_fecha_inicio').value;
    const fin = document.getElementById('sc_fecha_fin').value;
    const display = document.getElementById('sc_dias_display');
    
    if (inicio && fin) {
        const d1 = new Date(inicio);
        const d2 = new Date(fin);
        const diff = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
        
        if (diff > 0) {
            display.textContent = `Duración: ${diff} día(s)`;
        } else {
            display.textContent = '';
        }
    }
}

function guardarComisionDirecta() {
    const formData = new FormData();
    formData.append('asistencia_id', '0');
    formData.append('empleado_id', document.getElementById('sc_empleado_id').value);
    formData.append('tipo_justificacion', document.getElementById('sc_tipo_comision').value);
    formData.append('lugar_comision', document.getElementById('sc_lugar').value);
    formData.append('motivo_comision', document.getElementById('sc_motivo').value);
    formData.append('fecha_inicio_comision', document.getElementById('sc_fecha_inicio').value);
    formData.append('fecha_fin_comision', document.getElementById('sc_fecha_fin').value);
    
    const fundamento = document.getElementById('sc_fundamento').value;
    if (fundamento) {
        formData.append('fundamento_tipo', fundamento);
    }
    
    const erroresDiv = document.getElementById('sc_errores');
    
    // Validate
    const lugar = document.getElementById('sc_lugar').value.trim();
    const motivo = document.getElementById('sc_motivo').value.trim();
    const fechaInicio = document.getElementById('sc_fecha_inicio').value;
    
    if (!lugar) {
        erroresDiv.textContent = 'El lugar es requerido';
        erroresDiv.classList.remove('d-none');
        return;
    }
    if (!motivo) {
        erroresDiv.textContent = 'El motivo es requerido';
        erroresDiv.classList.remove('d-none');
        return;
    }
    if (!fechaInicio) {
        erroresDiv.textContent = 'La fecha de inicio es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    erroresDiv.classList.add('d-none');
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/justificaciones/guardar-incidencia', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Comisión solicitada correctamente. Pendiente de aprobación por el jefe.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalSolicitarComision'));
            if (modal) modal.hide();
            // Recargar datos del empleado
            window.location.reload();
        } else {
            erroresDiv.textContent = data.error || 'Error al guardar';
            erroresDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        erroresDiv.textContent = 'Error de conexión: ' + error.message;
        erroresDiv.classList.remove('d-none');
    });
}

// Modal para solicitar vacaciones
function abrirModalVacaciones() {
    const empId = window.empleadoId || window.empleadoIdGlobal;
    
    if (!empId) {
        alert('Error: No se pudo obtener el ID del empleado');
        return;
    }
    
    const modalHtml = `
    <div class="modal fade" id="modalSolicitarVacaciones" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-umbrella-beach me-2"></i>Solicitar Vacaciones</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-solicitar-vacaciones">
                        <input type="hidden" id="vac_empleado_id" name="empleado_id" value="${empId}">
                        <input type="hidden" id="vac_asistencia_id" name="asistencia_id" value="0">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Período de Vacaciones</label>
                            <select class="form-select" id="vac_periodo" name="periodo" onchange="actualizarFechasPeriodo()">
                                <option value="">Seleccione un período</option>
                                <option value="invierno">Período de Invierno (Navidad)</option>
                                <option value="semana_santa">Período de Semana Santa</option>
                                <option value="verano">Período de Verano</option>
                                <option value="otro">Otro período</option>
                            </select>
                            <small class="text-muted">
                                Invierno: 20 Dic - 5 Ene | Semana Santa: 13-20 Abr | Verano: 16 Jul - 15 Ago
                            </small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-1"></i>Fecha de Inicio</label>
                                <input type="date" class="form-control" id="vac_fecha_inicio" name="fecha_inicio_vacaciones" onchange="calcularDiasVacaciones()" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-calendar-check me-1"></i>Fecha de Fin</label>
                                <input type="date" class="form-control" id="vac_fecha_fin" name="fecha_fin_vacaciones" onchange="calcularDiasVacaciones()" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted" id="vac_dias_display"></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-comment me-1"></i>Motivo / Destino</label>
                            <textarea class="form-control" id="vac_motivo" name="motivo_vacaciones" rows="2" placeholder="Describa el motivo o destino de sus vacaciones" required></textarea>
                        </div>
                        
                        <div id="vac_errores" class="alert alert-danger d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarVacaciones()">
                        <i class="fas fa-save me-1"></i>Enviar Solicitud
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    const existingModal = document.getElementById('modalSolicitarVacaciones');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitarVacaciones'));
    modal.show();
}

function actualizarFechasPeriodo() {
    const periodo = document.getElementById('vac_periodo').value;
    const fechaInicio = document.getElementById('vac_fecha_inicio');
    const fechaFin = document.getElementById('vac_fecha_fin');
    const today = new Date();
    const year = today.getFullYear();
    
    let inicio = '';
    let fin = '';
    
    switch(periodo) {
        case 'invierno':
            inicio = `${year}-12-20`;
            fin = `${year + 1}-01-05`;
            break;
        case 'semana_santa':
            inicio = `${year}-04-13`;
            fin = `${year}-04-20`;
            break;
        case 'verano':
            inicio = `${year}-07-16`;
            fin = `${year}-08-15`;
            break;
    }
    
    if (inicio) {
        fechaInicio.value = inicio;
        fechaFin.value = fin;
        calcularDiasVacaciones();
    }
}

function calcularDiasVacaciones() {
    const inicio = document.getElementById('vac_fecha_inicio').value;
    const fin = document.getElementById('vac_fecha_fin').value;
    const display = document.getElementById('vac_dias_display');
    
    if (inicio && fin) {
        const d1 = new Date(inicio);
        const d2 = new Date(fin);
        const diff = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
        
        if (diff > 0) {
            display.textContent = `Días solicitados: ${diff}`;
        } else {
            display.textContent = '';
        }
    }
}

function guardarVacaciones() {
    const formData = new FormData();
    formData.append('asistencia_id', '0');
    formData.append('empleado_id', document.getElementById('vac_empleado_id').value);
    formData.append('tipo_justificacion', 'vacaciones');
    formData.append('fecha_inicio_vacaciones', document.getElementById('vac_fecha_inicio').value);
    formData.append('fecha_fin_vacaciones', document.getElementById('vac_fecha_fin').value);
    formData.append('motivo', document.getElementById('vac_motivo').value);
    
    const erroresDiv = document.getElementById('vac_errores');
    
    const fechaInicio = document.getElementById('vac_fecha_inicio').value;
    const fechaFin = document.getElementById('vac_fecha_fin').value;
    const motivo = document.getElementById('vac_motivo').value.trim();
    
    if (!fechaInicio) {
        erroresDiv.textContent = 'La fecha de inicio es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    if (!fechaFin) {
        erroresDiv.textContent = 'La fecha de fin es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    if (!motivo) {
        erroresDiv.textContent = 'El motivo es requerido';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    erroresDiv.classList.add('d-none');
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/justificaciones/guardar-incidencia', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Vacaciones solicitadas correctamente. Pendiente de aprobación.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalSolicitarVacaciones'));
            if (modal) modal.hide();
            window.location.reload();
        } else {
            erroresDiv.textContent = data.error || 'Error al guardar';
            erroresDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        erroresDiv.textContent = 'Error de conexión: ' + error.message;
        erroresDiv.classList.remove('d-none');
    });
}

// Modal para solicitar cuidados maternos o paternos
function abrirModalCuidados(tipo) {
    const empId = window.empleadoId || window.empleadoIdGlobal;
    
    if (!empId) {
        alert('Error: No se pudo obtener el ID del empleado');
        return;
    }
    
    const titulo = tipo === 'maternos' ? 'Cuidados Maternos' : 'Cuidados Paternos';
    const tipoJustificacion = tipo === 'maternos' ? 'cuidados_maternos' : 'cuidados_paternos';
    const icono = tipo === 'maternos' ? 'fa-baby' : 'fa-user-friends';
    
    const modalHtml = ` <div class="modal fade" id="modalSolicitarCuidados" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas ${icono} me-2"></i>Solicitar ${titulo}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-solicitar-cuidados">
                        <input type="hidden" id="cuid_empleado_id" name="empleado_id" value="${empId}">
                        <input type="hidden" id="cuid_tipo" name="tipo_cuidados" value="${tipo}">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Según normativa:</strong> Los permisos de cuidados son para atender a hijos menores de edad en caso de enfermedad o accidente. 
                            Se requiere documentación médica que justifique la necesidad del permiso.
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-1"></i>Fecha de Inicio</label>
                                <input type="date" class="form-control" id="cuid_fecha_inicio" name="fecha_inicio_cuidados" onchange="calcularDiasCuidados()" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-calendar-check me-1"></i>Fecha de Fin</label>
                                <input type="date" class="form-control" id="cuid_fecha_fin" name="fecha_fin_cuidados" onchange="calcularDiasCuidados()" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted" id="cuid_dias_display"></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-comment me-1"></i>Motivo / Descripción</label>
                            <textarea class="form-control" id="cuid_motivo" name="motivo_cuidados" rows="2" placeholder="Describa el motivo del permiso de cuidados" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-file-medical me-1"></i>Documento de Soporte (opcional)</label>
                            <input type="file" class="form-control" id="cuid_soporte" name="soporte" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Formato: PDF, JPG o PNG</small>
                        </div>
                        
                        <div id="cuid_errores" class="alert alert-danger d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarCuidados('${tipo}')">
                        <i class="fas fa-save me-1"></i>Enviar Solicitud
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    const existingModal = document.getElementById('modalSolicitarCuidados');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitarCuidados'));
    modal.show();
}

function calcularDiasCuidados() {
    const inicio = document.getElementById('cuid_fecha_inicio').value;
    const fin = document.getElementById('cuid_fecha_fin').value;
    const display = document.getElementById('cuid_dias_display');
    
    if (inicio && fin) {
        const d1 = new Date(inicio);
        const d2 = new Date(fin);
        const diff = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
        
        if (diff > 0) {
            display.textContent = `Días solicitados: ${diff}`;
        } else {
            display.textContent = '';
        }
    }
}

function guardarCuidados(tipo) {
    const formData = new FormData();
    formData.append('asistencia_id', '0');
    formData.append('empleado_id', document.getElementById('cuid_empleado_id').value);
    formData.append('tipo_justificacion', tipo === 'maternos' ? 'cuidados_maternos' : 'cuidados_paternos');
    formData.append('tipo_cuidados', tipo);
    formData.append('fecha_inicio_cuidados', document.getElementById('cuid_fecha_inicio').value);
    formData.append('fecha_fin_cuidados', document.getElementById('cuid_fecha_fin').value);
    formData.append('motivo', document.getElementById('cuid_motivo').value);
    
    const soporteInput = document.getElementById('cuid_soporte');
    if (soporteInput && soporteInput.files.length > 0) {
        formData.append('soporte', soporteInput.files[0]);
    }
    
    const erroresDiv = document.getElementById('cuid_errores');
    
    const fechaInicio = document.getElementById('cuid_fecha_inicio').value;
    const fechaFin = document.getElementById('cuid_fecha_fin').value;
    const motivo = document.getElementById('cuid_motivo').value.trim();
    
    if (!fechaInicio) {
        erroresDiv.textContent = 'La fecha de inicio es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    if (!fechaFin) {
        erroresDiv.textContent = 'La fecha de fin es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    if (!motivo) {
        erroresDiv.textContent = 'El motivo es requerido';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    erroresDiv.classList.add('d-none');
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/justificaciones/guardar-incidencia', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Solicitud de cuidados registrada correctamente. Pendiente de aprobación.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalSolicitarCuidados'));
            if (modal) modal.hide();
            window.location.reload();
        } else {
            erroresDiv.textContent = data.error || 'Error al guardar';
            erroresDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        erroresDiv.textContent = 'Error de conexión: ' + error.message;
        erroresDiv.classList.remove('d-none');
    });
}

// Modal para solicitar Constancia de Tiempo
function abrirModalConstancia() {
    const empId = window.empleadoId || window.empleadoIdGlobal;
    
    if (!empId) {
        alert('Error: No se pudo obtener el ID del empleado');
        return;
    }
    
    const modalHtml = ` <div class="modal fade" id="modalSolicitarConstancia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-medical me-2"></i>Solicitar Constancia de Tiempo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-solicitar-constancia">
                        <input type="hidden" id="const_empleado_id" name="empleado_id" value="${empId}">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Según normativa:</strong> La Constancia de Tiempo es un documento que certifica el tiempo laborado en la institución. 
                            Se utiliza para trámites ante otras dependencias. Puede ser de día completo o por horas (2-3 horas).
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-clock me-1"></i>Tipo de Constancia</label>
                            <select class="form-select" id="const_tipo_duracion" name="tipo_duracion" onchange="cambiarDuracionConstancia()">
                                <option value="dia_completo">Día Completo</option>
                                <option value="horas">Por Horas (2-3 horas)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-file-alt me-1"></i>Tipo de Constancia</label>
                            <select class="form-select" id="const_tipo" name="tipo_constancia">
                                <option value="laboral">Constancia Laboral</option>
                                <option value="antiguedad">Constancia de Antigüedad</option>
                                <option value="servicios">Constancia de Servicios</option>
                                <option value="otra">Otra</option>
                            </select>
                        </div>
                        
                        <div id="seccion_horas_constancia" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-clock me-1"></i>Horas de Constancia</label>
                                    <select class="form-select" id="const_horas" name="horas">
                                        <option value="2">2 Horas</option>
                                        <option value="3">3 Horas</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-clock me-1"></i>Hora de Inicio</label>
                                    <input type="time" class="form-control" id="const_hora_inicio" name="hora_inicio" value="09:00">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-building me-1"></i>Institución Receptora</label>
                            <input type="text" class="form-control" id="const_institucion" name="institucion_emisora" placeholder="Nombre de la institución que solicita la constancia">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-comment me-1"></i>Observaciones</label>
                            <textarea class="form-control" id="const_motivo" name="motivo" rows="2" placeholder="Propósito de la constancia (opcional)"></textarea>
                        </div>
                        
                        <div id="const_errores" class="alert alert-danger d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarConstancia()">
                        <i class="fas fa-save me-1"></i>Enviar Solicitud
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    const existingModal = document.getElementById('modalSolicitarConstancia');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitarConstancia'));
    modal.show();
}

function cambiarDuracionConstancia() {
    const tipoDuracion = document.getElementById('const_tipo_duracion').value;
    const seccionHoras = document.getElementById('seccion_horas_constancia');
    if (tipoDuracion === 'horas') {
        seccionHoras.style.display = 'block';
    } else {
        seccionHoras.style.display = 'none';
    }
}

function guardarConstancia() {
    const formData = new FormData();
    formData.append('asistencia_id', '0');
    formData.append('empleado_id', document.getElementById('const_empleado_id').value);
    formData.append('tipo_justificacion', 'constancia_tiempo');
    formData.append('tipo_constancia', document.getElementById('const_tipo').value);
    formData.append('tipo_duracion', document.getElementById('const_tipo_duracion').value);
    formData.append('horas', document.getElementById('const_horas').value);
    formData.append('hora_inicio', document.getElementById('const_hora_inicio').value);
    formData.append('institucion_emisora', document.getElementById('const_institucion').value);
    formData.append('motivo', document.getElementById('const_motivo').value);
    
    const erroresDiv = document.getElementById('const_errores');
    
    const tipo = document.getElementById('const_tipo').value;
    const institucion = document.getElementById('const_institucion').value.trim();
    
    if (!tipo) {
        erroresDiv.textContent = 'El tipo de constancia es requerido';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    erroresDiv.classList.add('d-none');
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/justificaciones/guardar-incidencia', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Constancia de Tiempo solicitada correctamente.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalSolicitarConstancia'));
            if (modal) modal.hide();
            window.location.reload();
        } else {
            erroresDiv.textContent = data.error || 'Error al guardar';
            erroresDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        erroresDiv.textContent = 'Error de conexión: ' + error.message;
        erroresDiv.classList.remove('d-none');
    });
}

function abrirModalLicenciaMedica() {
    const empId = window.empleadoActualId;
    if (!empId) return;
    
    const modalHtml = ` <div class="modal fade" id="modalSolicitarLicenciaMedica" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-md me-2"></i>Solicitar Licencia Médica</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-solicitar-licencia">
                        <input type="hidden" id="lic_empleado_id" name="empleado_id" value="${empId}">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Según normativa SEP:</strong> La licencia médica requiere constancia de la clínica/ISSSTE.
                            Según antigüedad: hasta 60 días con goce de sueldo, hasta 60 días con medio sueldo.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-file-alt me-1"></i>Folio ISSSTE</label>
                                <input type="text" class="form-control" id="lic_folio" name="folio" placeholder="Número de folio de la licencia ISSSTE">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-calendar me-1"></i>Fecha Inicio</label>
                                <input type="date" class="form-control" id="lic_fecha_inicio" name="fecha_inicio" onchange="calcularFechaFinLicenciaModal()">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-calendar-check me-1"></i>Días Otorgados</label>
                                <input type="number" class="form-control" id="lic_dias" name="dias_otorgados" min="1" max="60" placeholder="Días de licencia" oninput="calcularFechaFinLicenciaModal()">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-calendar-check me-1"></i>Fecha Fin</label>
                                <input type="date" class="form-control" id="lic_fecha_fin" name="fecha_fin" readonly>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-stethoscope me-1"></i>Diagnóstico</label>
                                <input type="text" class="form-control" id="lic_diagnostico" name="diagnostico" placeholder="Diagnóstico médico">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><i class="fas fa-hospital me-1"></i>Institución Emisora</label>
                                <input type="text" class="form-control" id="lic_institucion" name="institucion" placeholder="Hospital o Clínica que expide la constancia">
                                <small class="text-muted">ISSSTE, Clínica UNAM, Hospital particular, etc.</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-file-medical me-1"></i>Constancia de la Clínica/ISSSTE</label>
                            <input type="text" class="form-control" id="lic_constancia" name="constancia_clinica" placeholder="Número de constancia médica de la clínica o ISSSTE">
                            <small class="text-muted">Según normativa: Se requiere constancia médica expedida por el ISSSTE o clínica autorizada</small>
                        </div>
                        
                        <div id="lic_errores" class="alert alert-danger d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarLicenciaMedica()">
                        <i class="fas fa-save me-1"></i>Enviar Solicitud
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    const existingModal = document.getElementById('modalSolicitarLicenciaMedica');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitarLicenciaMedica'));
    modal.show();
}

function calcularFechaFinLicenciaModal() {
    const fechaInicio = document.getElementById('lic_fecha_inicio').value;
    const dias = parseInt(document.getElementById('lic_dias').value) || 0;
    
    if (fechaInicio && dias > 0) {
        const fecha = new Date(fechaInicio);
        fecha.setDate(fecha.getDate() + dias - 1);
        document.getElementById('lic_fecha_fin').value = fecha.toISOString().split('T')[0];
    }
}

function guardarLicenciaMedica() {
    const formData = new FormData();
    formData.append('asistencia_id', '0');
    formData.append('empleado_id', document.getElementById('lic_empleado_id').value);
    formData.append('tipo_justificacion', 'licencia_medica');
    formData.append('folio', document.getElementById('lic_folio').value);
    formData.append('fecha_inicio', document.getElementById('lic_fecha_inicio').value);
    formData.append('fecha_fin', document.getElementById('lic_fecha_fin').value);
    formData.append('dias_otorgados', document.getElementById('lic_dias').value);
    formData.append('diagnostico', document.getElementById('lic_diagnostico').value);
    formData.append('institucion', document.getElementById('lic_institucion').value);
    formData.append('tipo_sueldo', document.getElementById('lic_tipo_sueldo').value);
    formData.append('constancia_clinica', document.getElementById('lic_constancia').value);
    
    const erroresDiv = document.getElementById('lic_errores');
    
    const fechaInicio = document.getElementById('lic_fecha_inicio').value;
    const dias = document.getElementById('lic_dias').value;
    const diagnostico = document.getElementById('lic_diagnostico').value;
    const institucion = document.getElementById('lic_institucion').value;
    const constancia = document.getElementById('lic_constancia').value;
    
    if (!fechaInicio) {
        erroresDiv.textContent = 'La fecha de inicio es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    if (!dias || dias < 1) {
        erroresDiv.textContent = 'El número de días es requerido';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    if (!diagnostico) {
        erroresDiv.textContent = 'El diagnóstico es requerido';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    if (!institucion) {
        erroresDiv.textContent = 'La institución (hospital/clínica) es requerida';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    if (!constancia) {
        erroresDiv.textContent = 'El número de constancia de la clínica/ISSSTE es requerido según normativa';
        erroresDiv.classList.remove('d-none');
        return;
    }
    
    erroresDiv.classList.add('d-none');
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/justificaciones/guardar-incidencia', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Licencia Médica solicitada correctamente. Pendiente de aprobación.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalSolicitarLicenciaMedica'));
            if (modal) modal.hide();
            window.location.reload();
        } else {
            erroresDiv.textContent = data.error || 'Error al guardar';
            erroresDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        erroresDiv.textContent = 'Error de conexión: ' + error.message;
        erroresDiv.classList.remove('d-none');
    });
}

// Mostrar información de comparación plazas vs ciclo
document.addEventListener('DOMContentLoaded', function() {
    const modalComparacion = document.getElementById('modalComparacionPlazasCiclo');
    if (modalComparacion) {
        modalComparacion.addEventListener('shown.bs.modal', function() {
            const bodyEl = document.getElementById('modalComparacionPlazasCicloBody');
            if (bodyEl && bodyEl.querySelector('.spinner-border')) {
                // Mostrar información estática sobre el cálculo
                bodyEl.innerHTML = ` <div class="alert alert-primary mb-3">
                        <i class="fas fa-book me-2"></i><strong>Referencia Normativa:</strong> 
                        Este cálculo se fundamenta en los Lineamientos para la Distribución de Horas de Trabajo 
                        establecidos en el Manual de Normas para la Administración de Recursos Humanos en la SEP 
                        (Versión vigente 2025).
                    </div>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="text-primary mb-2">¿Cómo se calculan las horas de las plazas?</h6>
                            <p class="mb-0">Según la Normativa SEP: Las horas de las plazas son <strong>horas semanales</strong> que se pagan 
                            <strong>quincenalmente</strong>. Para efectos de comparación con los ciclos (que se miden en horas mensuales), 
                            se requiere realizar la conversión correspondiente.</p>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-2">Fórmula de cálculo:</h6>
                    <div class="alert alert-light border mb-3">
                        <strong>Horas Mensuales = Horas Semanales × 4.33</strong><br>
                        <small class="text-muted">Donde 4.33 es el promedio de semanas por mes (52 semanas ÷ 12 meses)</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Para ver la comparación detallada de un empleado específico, abra primero el modal de detalles del empleado 
                        y consulte la sección "Comparación Plazas vs Ciclo" que se muestra automáticamente con los datos del empleado.
                    </div>
                `;
            }
        });
    }
});
</script>

<!-- Modal Cálculo de Horas de Plazas - Bajo Normatividad SEP -->
<div class="modal fade" id="modalCalculoPlazas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Cálculo de Horas de Plaza (Normativa SEP)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-primary mb-3">
                    <i class="fas fa-book me-2"></i><strong>Referencia Normativa:</strong> Este cálculo se fundamenta en los <strong>Lineamientos para la Distribución de Horas de Trabajo</strong> establecidos en el <strong>Manual de Normas para la Administración de Recursos Humanos en la Secretaría de Educación Pública</strong> (Versión vigente 2025), específicamente en la <strong>Sección 4.2 "Cómputo y Conversión de Horas Laborales"</strong> que establece: <em>"Las horas de plaza se computan semanalmente y se pagan de manera quincenal, correspondiendo la conversión a mensual multiplicando por el factor 4.33 semanas por mes"</em>.
                </div>
                
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <p class="mb-2"><strong>¿Cómo se calculan las horas de las plazas?</strong></p>
                        <p class="mb-0">Según la Normativa SEP: Las horas de las plazas son horas semanales que se pagan quincenalmente. Para efectos de comparación con los ciclos (que se miden en horas mensuales), se requiere realizar la conversión correspondiente.</p>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-2">Fórmula de cálculo:</h6>
                <div class="alert alert-light border">
                    <strong>Horas Mensuales = Horas Semanales × 4.33</strong><br>
                    <small class="text-muted">Donde 4.33 es el promedio de semanas por mes (52 semanas ÷ 12 meses)</small>
                </div>
                
                <table class="table table-bordered table-sm mt-3">
                    <thead class="table-light">
                        <tr>
                            <th>Concepto</th>
                            <th>Cálculo</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Horas de Plaza (Semanal)</td>
                            <td>Valor de la tabla plazas</td>
                            <td><strong>35 hrs/sem</strong></td>
                        </tr>
                        <tr>
                            <td>Horas Mensuales</td>
                            <td>35 × 4.33</td>
                            <td><strong>151.55 hrs/mes</strong></td>
                        </tr>
                        <tr>
                            <td>Horas por Día (L-V)</td>
                            <td>35 ÷ 5 días</td>
                            <td><strong>7 hrs/día</strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <h6 class="text-warning mb-2">Comparación con Ciclo:</h6>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Horas de Plaza (Mes)
                        <span class="badge bg-primary rounded-pill">151.55 hrs/mes</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Horas Requeridas del Ciclo (Mes)
                        <span class="badge bg-success rounded-pill">151.6 hrs/mes</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Diferencia
                        <span class="badge bg-success rounded-pill">-0.05 (Coincide)</span>
                    </li>
                </ul>
                
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-check-circle me-1"></i> <strong>Resultado:</strong> Cuando la diferencia es menor a 1 hr/mes, las hrs de la plaza coinciden con las hrs requeridas del ciclo, cumpliendo con la Normativa SEP.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Comisión -->
<div class="modal fade" id="modalEditarComision" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Comisión</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarComision">
                    <input type="hidden" id="ec_id" name="id">
                    <input type="hidden" id="ec_empleado_id" name="empleado_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea class="form-control" id="ec_descripcion" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha Inicio</label>
                            <input type="date" class="form-control" id="ec_fecha_inicio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha Fin</label>
                            <input type="date" class="form-control" id="ec_fecha_fin">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Estatus</label>
                            <input type="text" class="form-control" id="ec_estatus" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo</label>
                            <select class="form-select" id="ec_tipo">
                                <option value="comision_todo_dia">Comisión día completo</option>
                                <option value="comision_entrada">Comisión entrada</option>
                                <option value="comision_salida">Comisión salida</option>
                                <option value="viaticos">Viáticos</option>
                                <option value="gastos_representacion">Gastos representación</option>
                                <option value="transporte">Transporte</option>
                                <option value="hospedaje">Hospedaje</option>
                                <option value="alimentacion">Alimentación</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="ec_errores" class="alert alert-danger d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarEdicionComision()">
                    <i class="fas fa-save me-1"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Día Económico -->
<div class="modal fade" id="modalEditarDiaEconomico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Día Económico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarDiaEconomico">
                    <input type="hidden" id="ede_id" name="id">
                    <input type="hidden" id="ede_empleado_id" name="empleado_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Estatus</label>
                        <input type="text" class="form-control" id="ede_estatus" readonly>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha</label>
                            <input type="date" class="form-control" id="ede_fecha">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Días Solicitados</label>
                            <select class="form-select" id="ede_dias">
                                <option value="1">1 día</option>
                                <option value="2">2 días</option>
                                <option value="3">3 días</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Modalidad</label>
                        <select class="form-select" id="ede_modalidad">
                            <option value="A">A - 3 días</option>
                            <option value="B">B - 2 días</option>
                            <option value="C">C - 1 día</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Motivo</label>
                        <textarea class="form-control" id="ede_motivo" rows="2"></textarea>
                    </div>
                    
                    <div id="ede_errores" class="alert alert-danger d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarEdicionDiaEconomico()">
                    <i class="fas fa-save me-1"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Registro (unificado para Vacaciones, Licencias, Cuidados M/P, Constancias, Sanciones) -->
<div class="modal fade" id="modalEditarRegistro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #4A4A4A 0%, #2C2C2C 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i><span id="modalEditarRegistroTitulo">Editar Registro</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarRegistro">
                    <input type="hidden" id="er_id" name="id">
                    <input type="hidden" id="er_empleado_id" name="empleado_id">
                    <input type="hidden" id="er_tabla" name="tabla">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha Inicio</label>
                            <input type="date" class="form-control" id="er_fecha_inicio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha Fin</label>
                            <input type="date" class="form-control" id="er_fecha_fin">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6" id="er_field_dias">
                            <label class="form-label fw-bold">Días</label>
                            <input type="number" class="form-control" id="er_dias" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Estatus</label>
                            <input type="text" class="form-control" id="er_estatus" readonly>
                        </div>
                    </div>

                    <div class="mb-3" id="er_field_motivo">
                        <label class="form-label fw-bold" id="er_label_motivo">Motivo</label>
                        <textarea class="form-control" id="er_motivo" rows="2"></textarea>
                    </div>

                    <div class="mb-3" id="er_field_tipo" style="display:none;">
                        <label class="form-label fw-bold" id="er_label_tipo">Tipo</label>
                        <select class="form-select" id="er_tipo"></select>
                    </div>

                    <!-- Licencias extra fields -->
                    <div id="er_field_diagnostico" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Diagnóstico</label>
                            <textarea class="form-control" id="er_diagnostico" rows="2"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Días Full</label>
                                <input type="number" class="form-control" id="er_dias_full" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Días Half</label>
                                <input type="number" class="form-control" id="er_dias_half" min="0">
                            </div>
                        </div>
                    </div>

                    <div id="er_errores" class="alert alert-danger d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarEdicionRegistroTab()">
                    <i class="fas fa-save me-1"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Comparación Plazas vs Ciclo -->
<div class="modal fade" id="modalComparacionPlazasCiclo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-balance-scale me-2"></i>Comparación Plazas vs Ciclo - Detalle Completo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalComparacionPlazasCicloBody">
                <!-- El contenido se llena dinámicamente desde empleados.js -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/modal_marcaciones.php'; ?>

<script src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>/assets/js/empleados.js?v=20260406"></script>
