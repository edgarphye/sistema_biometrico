<style>
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
        --pantone-danger: #C62828;
        --pantone-warning: #F57C00;
        --pantone-slate: #455A64;
        --pantone-gray: #ECEFF1;
    }
    
    .config-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }
    
    .config-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }
    
    .config-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--pantone-vino) 0%, var(--pantone-verde) 50%, var(--pantone-dorado) 100%);
    }
    
    .config-card .card-header {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
        color: white;
        border-radius: 16px 16px 0 0;
        padding: 18px 24px;
        border: none;
    }
    
    .config-card .card-header h5,
    .config-card .card-header h6 {
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    
    .config-card .card-body {
        padding: 24px;
        background: var(--pantone-white);
    }
    
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
        margin-bottom: 10px;
    }
    
    .form-text {
        color: var(--pantone-gris-oscuro);
        font-size: 0.85rem;
        background: linear-gradient(135deg, #FCE4EC 0%, #E8F5E9 100%);
        padding: 14px 16px;
        border-radius: 10px;
        border-left: 5px solid var(--pantone-vino);
        margin-top: 14px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--pantone-vino) 0%, var(--pantone-vino-oscuro) 100%);
        border: none;
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(159, 34, 65, 0.25);
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, var(--pantone-vino-oscuro) 0%, #4a1424 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(159, 34, 65, 0.35);
    }
    
    .btn-outline-secondary {
        border: 2px solid var(--pantone-gris-oscuro);
        color: var(--pantone-slate);
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 500;
    }
    
    .btn-outline-secondary:hover {
        background: var(--pantone-gray);
        border-color: var(--pantone-vino);
        color: var(--pantone-vino);
    }
    
    .badge-pantone {
        background: linear-gradient(135deg, var(--pantone-dorado) 0%, var(--pantone-dorado-oscuro) 100%);
        color: var(--pantone-vino-oscuro);
        padding: 8px 16px;
        border-radius: 25px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(221, 201, 163, 0.35);
    }
    
    .section-title {
        color: var(--pantone-vino);
        font-weight: 700;
        border-bottom: 3px solid var(--pantone-dorado);
        padding-bottom: 10px;
    }
    
    .stat-card {
        background: white;
        border-radius: 14px;
        padding: 20px;
        border: 2px solid var(--pantone-gray);
    }
    
    .stat-card:hover {
        border-color: var(--pantone-vino);
        box-shadow: 0 8px 25px rgba(159, 34, 65, 0.15);
    }
    
    .stat-card .stat-value {
        color: var(--pantone-vino);
    }
</style>

<div class="container mt-4">
    <div class="d-flex align-items-center mb-4">
        <div class="bg-gradient-primary p-3 rounded me-3" style="background: linear-gradient(135deg, var(--pantone-blue) 0%, var(--pantone-blue-light) 100%);">
            <i class="fas fa-cogs text-white fa-2x"></i>
        </div>
        <div>
            <h2 class="mb-0" style="color: var(--pantone-blue); font-weight: 700;">Configuración del Sistema</h2>
            <p class="text-muted mb-0">Administra la carga de archivos y parámetros del sistema biométrico</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card config-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Carga de Archivos ZKTeco (.dat)</h5>
                    <span class="badge-pantone">Sistema Universal</span>
                </div>
                <div class="card-body">
                    <form id="zkUploadForm" enctype="multipart/form-data" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?? Csrf::generateToken() ?>">
                        
                        <div class="form-group mb-3">
                            <label for="zk_file" class="form-label">
                                <i class="fas fa-file-upload me-2" style="color: var(--pantone-gold);"></i>Seleccionar archivo ZKTeco
                            </label>
                            <input type="file" class="form-control" id="zk_file" name="zk_file" accept=".dat,.DAT" required>
                            <div class="form-text">
                                <strong><i class="fas fa-info-circle me-1" style="color: var(--pantone-blue);"></i> Formatos soportados automáticamente:</strong><br>
                                <span class="ms-3">• Formato Simple: ID	FECHA	HORA	[ACCIÓN]</span><br>
                                <span class="ms-3">• Formato Estándar: ID	DATETIME	TYPE	VERIFY	RESULT	DEVICE</span><br>
                                <span class="ms-3">• Formato Extendido: 8+ campos con metadata adicional</span><br>
                                <strong><i class="fas fa-file me-1"></i> Extensión:</strong> .dat (máx. 10MB)
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <div class="progress" id="uploadProgress" style="display: none;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">
                                    <span id="progressText">0%</span>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" id="mantener_fecha_original" name="mantener_fecha_original" value="0">
                        <input type="hidden" id="procesar_fines_semana" name="procesar_fines_semana" value="0">
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="uploadBtn">
                                <i class="fas fa-cogs me-2"></i> Procesar Archivo
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clearBtn">
                                <i class="fas fa-eraser me-2"></i> Limpiar
                            </button>
                        </div>
                    </form>
                    
                    <div id="resultMessages" class="mt-3"></div>
                </div>
            </div>
            
            <div class="card config-card mt-3">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h6 class="mb-0"><i class="fas fa-magic me-2"></i>Sistema de Procesamiento Universal</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);">
                                <h6 style="color: var(--pantone-blue);"><i class="fas fa-search me-2"></i>Detección Automática</h6>
                                <ul class="list-unstyled small mb-0">
                                    <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> Análisis de estructura dinámica</li>
                                    <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> Identificación de tipos de datos</li>
                                    <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> Validación de integridad</li>
                                    <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> Aprendizaje de formatos</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);">
                                <h6 style="color: var(--pantone-success);"><i class="fas fa-database me-2"></i>Inserción Inteligente</h6>
                                <ul class="list-unstyled small mb-0">
                                    <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> Manejo de duplicados</li>
                                    <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> Actualización automática</li>
                                    <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> Procesamiento por lotes</li>
                                    <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> Recuperación de errores</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card config-card mt-3">
                <div class="card-header" style="background: linear-gradient(135deg, var(--pantone-slate) 0%, var(--pantone-slate-light) 100%);">
                    <h6 class="mb-0"><i class="fas fa-list-check me-2"></i>Reglas de Procesamiento ZKTeco</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="section-title" style="font-size: 1rem;"><i class="fas fa-toggle-on me-2" style="color: var(--pantone-success);"></i>Reglas Activas</h6>
                            <ul class="list-unstyled small">
                                <li class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="procesar_fines_semana_check" name="procesar_fines_semana_check">
                                        <label class="form-check-label" for="procesar_fines_semana_check">
                                            <strong>Procesar fines de semana</strong><br>
                                            <span class="text-muted">Incluir sábados y domingos (por defecto se omiten)</span>
                                        </label>
                                    </div>
                                </li>
                                <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> <strong>Registros exitosos:</strong> Solo procesar resultado=1</li>
                                <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> <strong>Horario nocturno:</strong> 00:00-04:00 → día anterior</li>
                                <li><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i> <strong>Mapeo automático:</strong> ZK-ID → empleado_id</li>
                            </ul>
                            <hr>
                            <h6 class="section-title" style="font-size: 1rem; border-color: var(--pantone-blue);"><i class="fas fa-calendar me-2" style="color: var(--pantone-blue);"></i>Opciones de Fecha</h6>
                            <ul class="list-unstyled small">
                                <li>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="mantener_fecha_original_check" name="mantener_fecha_original_check">
                                        <label class="form-check-label" for="mantener_fecha_original_check">
                                            <strong>Mantener fecha original del archivo .dat</strong><br>
                                            <span class="text-muted">No ajustar fechas de 00:00-04:00 al día anterior</span>
                                        </label>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);">
                                <h6 style="color: var(--pantone-warning); font-size: 1rem;"><i class="fas fa-shield-alt me-2"></i>Validaciones</h6>
                                <ul class="list-unstyled small mb-0">
                                    <li><i class="fas fa-shield-alt me-1" style="color: var(--pantone-blue);"></i> <strong>Formato de fecha:</strong> YYYY-MM-DD válido</li>
                                    <li><i class="fas fa-shield-alt me-1" style="color: var(--pantone-blue);"></i> <strong>Formato de hora:</strong> HH:MM:SS válido</li>
                                    <li><i class="fas fa-shield-alt me-1" style="color: var(--pantone-blue);"></i> <strong>Rango de IDs:</strong> 1-99999</li>
                                    <li><i class="fas fa-shield-alt me-1" style="color: var(--pantone-blue);"></i> <strong>Integridad:</strong> Campos consistentes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card para Procesamiento Completo -->
            <div class="card config-card mt-3 border-warning" style="border-width: 3px;">
                <div class="card-header text-white" style="background: linear-gradient(135deg, #7B1FA2 0%, #6A1B9A 100%);">
                    <h5 class="mb-0"><i class="fas fa-play-circle me-2"></i>Procesamiento Completo</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        <i class="fas fa-info-circle me-1" style="color: #7B1FA2;"></i>
                        Ejecuta todas las tareas necesarias en secuencia: Carga ZKTeco → Corrige fechas → Unifica entradas/salidas → Calcula retardos → Evalúa sanciones.
                    </p>
                    <form id="procesamientoCompletoForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?? Csrf::generateToken() ?>">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="zk_file_completo" class="form-label">
                                        <i class="fas fa-file-upload me-2" style="color: var(--pantone-gold);"></i>Seleccionar archivo ZKTeco
                                    </label>
                                    <input type="file" class="form-control" id="zk_file_completo" name="zk_file" accept=".dat,.DAT" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="completo_fecha_inicio" class="form-label">
                                    <i class="fas fa-calendar-alt me-2" style="color: var(--pantone-blue);"></i>Fecha Inicio
                                </label>
                                <input type="date" class="form-control" id="completo_fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-01') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="completo_fecha_fin" class="form-label">
                                    <i class="fas fa-calendar-alt me-2" style="color: var(--pantone-blue);"></i>Fecha Fin
                                </label>
                                <input type="date" class="form-control" id="completo_fecha_fin" name="fecha_fin" value="<?= date('Y-m-t') ?>" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn w-100" id="btnProcesamientoCompleto" style="background: linear-gradient(135deg, #7B1FA2 0%, #6A1B9A 100%); color: white; font-weight: 600;">
                                    <i class="fas fa-rocket me-2"></i>Ejecutar Todo
                                </button>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-check" style="background: #F3E5F5; padding: 10px; border-radius: 8px;">
                                    <input class="form-check-input" type="checkbox" id="completo_corregir_fechas" name="corregir_fechas" value="1" checked>
                                    <label class="form-check-label" for="completo_corregir_fechas">
                                        <strong>Corregir fechas horario nocturno</strong>
                                        <small class="d-block text-muted">00:00-04:00 → día anterior</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check" style="background: #E8F5E9; padding: 10px; border-radius: 8px;">
                                    <input class="form-check-input" type="checkbox" id="completo_evaluar_sanciones" name="evaluar_sanciones" value="1" checked>
                                    <label class="form-check-label" for="completo_evaluar_sanciones">
                                        <strong>Evaluar sanciones automáticas</strong>
                                        <small class="d-block text-muted">Reglas: oficio, suspensión, término</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" id="progressCompleto" style="display: none;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-purple" role="progressbar" style="width: 0%; background: linear-gradient(135deg, #7B1FA2 0%, #6A1B9A 100%);">
                                <span id="progressTextCompleto">0%</span>
                            </div>
                        </div>
                    </form>
                    <div id="resultadoCompleto" class="mt-3"></div>
                </div>
            </div>
            
            <!-- Card para Calcular Retardos -->
            <div class="card config-card mt-3">
                <div class="card-header" style="background: linear-gradient(135deg, var(--pantone-warning) 0%, #FF9800 100%);">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Calcular Retardos y Comisiones</h5>
                </div>
                <div class="card-body">
                    <form id="calcularRetardosForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="fecha_inicio" class="form-label">
                                    <i class="fas fa-calendar-alt me-2" style="color: var(--pantone-blue);"></i>Fecha Inicio
                                </label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="2026-02-01" required>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_fin" class="form-label">
                                    <i class="fas fa-calendar-alt me-2" style="color: var(--pantone-blue);"></i>Fecha Fin
                                </label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="2026-02-28" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn w-100" id="calcularBtn" style="background: linear-gradient(135deg, var(--pantone-warning) 0%, #FF9800 100%); color: white; font-weight: 600;">
                                    <i class="fas fa-calculator me-2"></i>Calcular
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="retardosResult" class="mt-3"></div>
                </div>
            </div>
            
            <!-- Card para Corregir Asistencia -->
            <div class="card config-card mt-3">
                <div class="card-header" style="background: linear-gradient(135deg, #00BCD4 0%, #0097A7 100%);">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Corregir Asistencia</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        <i class="fas fa-info-circle me-1" style="color: var(--pantone-blue);"></i>
                        Unifica entradas y salidas por empleado + fecha. Corrige fechas de horarios nocturnos (00:00-04:00) usando la lógica de ciclos, bloques_ciclos, horarios_laborales y horarios_empleados.
                    </p>
                    <form id="corregirAsistenciaForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="corregir_fecha_inicio" class="form-label">
                                    <i class="fas fa-calendar-alt me-2" style="color: var(--pantone-blue);"></i>Fecha Inicio
                                </label>
                                <input type="date" class="form-control" id="corregir_fecha_inicio" name="corregir_fecha_inicio" value="2026-02-01" required>
                            </div>
                            <div class="col-md-4">
                                <label for="corregir_fecha_fin" class="form-label">
                                    <i class="fas fa-calendar-alt me-2" style="color: var(--pantone-blue);"></i>Fecha Fin
                                </label>
                                <input type="date" class="form-control" id="corregir_fecha_fin" name="corregir_fecha_fin" value="2026-02-28" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check" style="background: #E0F7FA; padding: 12px; border-radius: 8px;">
                                    <input class="form-check-input" type="checkbox" id="corregir_fechas" name="corregir_fechas" value="1">
                                    <label class="form-check-label" for="corregir_fechas">
                                        <strong>Corregir fechas de horario nocturno</strong><br>
                                        <span class="text-muted small">Mover registros de 00:00-04:00 al día anterior si hay asistencia registrada</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn w-100" id="corregirBtn" style="background: linear-gradient(135deg, #00BCD4 0%, #0097A7 100%); color: white; font-weight: 600;">
                                    <i class="fas fa-wrench me-2"></i>Corregir
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="corregirResult" class="mt-3"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card config-card">
                <div class="card-header" style="background: linear-gradient(135deg, var(--pantone-success) 0%, #388E3C 100%);">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Estado del Sistema</h6>
                </div>
                <div class="card-body">
                    <div class="stat-card mb-3">
                        <div class="d-flex justify-content-between align-items-center p-2">
                            <span><i class="fas fa-clock me-2" style="color: var(--pantone-blue);"></i>Último procesamiento:</span>
                            <span id="lastProcess" class="badge" style="background: var(--pantone-gray);">Nunca</span>
                        </div>
                    </div>
                    <div class="stat-card mb-3">
                        <div class="d-flex justify-content-between align-items-center p-2">
                            <span><i class="fas fa-file me-2" style="color: var(--pantone-blue);"></i>Archivos procesados:</span>
                            <span id="processedFiles" class="badge" style="background: var(--pantone-blue); color: white;">0</span>
                        </div>
                    </div>
                    <div class="stat-card mb-3">
                        <div class="d-flex justify-content-between align-items-center p-2">
                            <span><i class="fas fa-list-alt me-2" style="color: var(--pantone-gold);"></i>Formatos detectados:</span>
                            <span id="detectedFormats" class="badge" style="background: var(--pantone-gold); color: white;">0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center p-2">
                            <span><i class="fas fa-users me-2" style="color: var(--pantone-success);"></i>Registros hoy:</span>
                            <span id="todayRecords" class="badge" style="background: var(--pantone-blue-light); color: white;">0</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card config-card mt-3">
                <div class="card-header" style="background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Historial Reciente</h6>
                </div>
                <div class="card-body">
                    <div id="recentHistory">
                        <p class="text-muted small text-center"><i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...</p>
                    </div>
                </div>
            </div>
            
            <div class="card config-card mt-3">
                <div class="card-header" style="background: linear-gradient(135deg, #FF7043 0%, #E64A19 100%);">
                    <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>Ayuda Rápida</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <p><strong><i class="fas fa-lightbulb me-1" style="color: var(--pantone-gold);"></i>¿Cómo funciona?</strong></p>
                        <ol class="small ps-3">
                            <li>Selecciona tu archivo .dat</li>
                            <li>El sistema detecta automáticamente el formato</li>
                            <li>Aplica reglas ZKTeco estándar</li>
                            <li>Inserta en base de datos sin pérdida</li>
                        </ol>
                        
                        <p class="mt-2"><strong><i class="fas fa-check-circle me-1" style="color: var(--pantone-success);"></i>Soporte de formatos:</strong></p>
                        <ul class="small ps-3">
                            <li>✅ Formato simple (3-4 campos)</li>
                            <li>✅ Formato estándar (6 campos)</li>
                            <li>✅ Formato extendido (8+ campos)</li>
                            <li>✅ Cualquier orden de campos</li>
                        </ul>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de resultados detallados -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--pantone-blue) 0%, var(--pantone-blue-light) 100%); color: #ffffff; padding: 20px 24px;">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar me-2"></i> Resultados del Procesamiento de Archivo DAT
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailedResults" style="max-height: 70vh; overflow-y: auto; padding: 24px; background: var(--pantone-white);">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer" style="background-color: #F7FAFC; border-top: 1px solid var(--pantone-gray-dark); padding: 16px 24px;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-color: var(--pantone-slate-light); color: var(--pantone-slate);">Cerrar</button>
                <button type="button" class="btn btn-primary" id="exportResults" style="background: linear-gradient(135deg, var(--pantone-blue) 0%, var(--pantone-blue-light) 100%); border: none;">
                    <i class="fas fa-download me-1"></i> Exportar Reporte
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    console.log('Configuracion content loaded');

    // Definir base URL globalmente antes de cualquier función que la necesite
    // Usar valor por defecto si BASE_URL no está definido
    (function() {
        var baseUrlValue = '<?php echo defined("BASE_URL") ? BASE_URL : ""; ?>';
        if (!baseUrlValue || baseUrlValue === '') {
            baseUrlValue = '/sistema_biometrico';
        }
        window.baseUrl = baseUrlValue.replace(/\/$/, '');
        console.log('baseUrl defined:', window.baseUrl);
    })();

    // Función para mostrar detalles de un procesamiento del historial (global - disponible inmediatamente)
    window.mostrarDetallesHistorial = function(id) {
        console.log('mostrarDetallesHistorial called with id:', id);
        fetch(window.baseUrl + '/configuracion/getProcesamientoDetalle?id=' + id + '&t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.success && data.procesamiento) {
                    const p = data.procesamiento;
                    const modalElement = document.getElementById('resultsModal');
                    const detailedResults = document.getElementById('detailedResults');
                    
                    const fileName = p.nombre_archivo || p.archivo_nombre || 'Sin nombre';
                    const fileSize = p.tamano_formateado || p.archivo_tamano || 'N/A';
                    const isSuccess = p.exito == 1 || p.estado === 'exito';
                    const dateStr = p.fecha_inicio || p.created_at || p.creado_at || p.fecha_ejecucion;
                    const registros = p.registros_leidos || p.registros_procesados || 0;
                    
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-file"></i> Información del Archivo</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr><td class="text-muted">Nombre</td><td><strong>${fileName}</strong></td></tr>
                                            <tr><td class="text-muted">Tamaño</td><td>${fileSize}</td></tr>
                                            <tr><td class="text-muted">Formato</td><td><span class="badge bg-info">${p.formato_detectado || 'N/A'}</span></td></tr>
                                            <tr><td class="text-muted">Registros</td><td>${registros}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header ${isSuccess ? 'bg-success' : 'bg-danger'} text-white">
                                        <h6 class="mb-0"><i class="fas fa-${isSuccess ? 'check-circle' : 'exclamation-circle'}"></i> Estado del Procesamiento</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr><td class="text-muted">Fecha</td><td>${dateStr || 'N/A'}</td></tr>
                                            <tr><td class="text-muted">Estado</td><td><span class="badge bg-${isSuccess ? 'success' : 'danger'}">${isSuccess ? 'Exitoso' : 'Error'}</span></td></tr>
                                            ${p.registros_insertados !== undefined ? `<tr><td class="text-muted">Insertados</td><td>${p.registros_insertados}</td></tr>` : ''}
                                            ${p.registros_actualizados !== undefined ? `<tr><td class="text-muted">Actualizados</td><td>${p.registros_actualizados}</td></tr>` : ''}
                                            ${p.errores !== undefined ? `<tr><td class="text-muted">Errores</td><td>${p.errores}</td></tr>` : ''}
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ${p.mensaje ? `<div class="alert alert-${isSuccess ? 'success' : 'warning'} mt-3"><i class="fas fa-${isSuccess ? 'info-circle' : 'exclamation-triangle'}"></i> ${p.mensaje}</div>` : ''}`;

                    detailedResults.innerHTML = html;
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } else {
                    alert('No se pudieron cargar los detalles');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los detalles: ' + error.message);
            });
    };

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('zkUploadForm');
    const mantenerFechaCheckbox = document.getElementById('mantener_fecha_original_check');
    const mantenerFechaInput = document.getElementById('mantener_fecha_original');
    const procesarFinesSemanaCheckbox = document.getElementById('procesar_fines_semana_check');
    const procesarFinesSemanaInput = document.getElementById('procesar_fines_semana');
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    const progressText = document.getElementById('progressText');
    const resultDiv = document.getElementById('resultMessages');
    const uploadBtn = document.getElementById('uploadBtn');
    const clearBtn = document.getElementById('clearBtn');
    
    // Cargar historial reciente
    cargarHistorial();
    
    // Evento change del checkbox mantener_fecha_original
    if (mantenerFechaCheckbox) {
        mantenerFechaCheckbox.addEventListener('change', function() {
            mantenerFechaInput.value = this.checked ? '1' : '0';
        });
    }
    
    // Evento change del checkbox procesar_fines_semana
    if (procesarFinesSemanaCheckbox) {
        procesarFinesSemanaCheckbox.addEventListener('change', function() {
            procesarFinesSemanaInput.value = this.checked ? '1' : '0';
        });
    }
    
    // Evento de envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        
        // Mostrar progreso
        progressDiv.style.display = 'block';
        progressBar.style.width = '10%';
        progressText.textContent = '10% - Validando archivo...';
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        resultDiv.innerHTML = '';
        
        fetch(baseUrl + '/configuracion/uploadZkteco', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            progressBar.style.width = '50%';
            progressText.textContent = '50% - Procesando datos...';
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.error) {
                    showError(data.error);
                    return;
                }
                processResponse(data);
            } catch (e) {
                console.error('Response not JSON:', text);
                if (text.includes('Error') || text.includes('error')) {
                    showError(text.substring(0, 500));
                } else {
                    showError('Error de respuesta del servidor');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            let errorMsg = error.message || 'Error de conexión';
            if (errorMsg.includes('Body has already been consumed')) {
                errorMsg = 'La sesión expiró. Por favor recarga la página e intenta de nuevo.';
            }
            showError('Error de conexión: ' + errorMsg);
        })
        .finally(() => {
            setTimeout(() => {
                progressDiv.style.display = 'none';
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-cogs"></i> Procesar Archivo';
            }, 1000);
        });
    });
    
    // Botón de limpiar
    clearBtn.addEventListener('click', function() {
        form.reset();
        resultDiv.innerHTML = '';
        document.getElementById('zk_file').value = '';
    });
    
    // Función para procesar respuesta
    function processResponse(data) {
        progressBar.style.width = '90%';
        progressText.textContent = '90% - Generando reporte...';
        
        if (data.success) {
            showSuccess(data);
            actualizarEstadisticas(data);
            cargarHistorial(); // Actualizar historial
        } else {
            showError(data.error || data.message || 'Error desconocido');
        }
        
        progressBar.style.width = '100%';
        progressText.textContent = '100% - Completado';
    }
    
    // Función para mostrar éxito
    function showSuccess(data) {
        const html = `
            <div class="alert alert-success">
                <div class="d-flex align-items-start">
                    <div class="flex-grow-1">
                        <h6><i class="fas fa-check-circle"></i> ${data.message}</h6>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6><i class="fas fa-file"></i> Información del Archivo</h6>
                                <ul class="list-unstyled small">
                                    <li><strong>Nombre:</strong> ${data.estadisticas_generales.nombre_archivo}</li>
                                    <li><strong>Tamaño:</strong> ${data.estadisticas_generales.tamano}</li>
                                    <li><strong>Formato detectado:</strong> ${data.estadisticas_generales.formato_detectado}</li>
                                    <li><strong>Confianza:</strong> ${data.estadisticas_generales.confianza_formato}</li>
                                    <li><strong>Período:</strong> ${data.estadisticas_generales.periodo_procesado}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-chart-bar"></i> Estadísticas de Procesamiento</h6>
                                <ul class="list-unstyled small">
                                    <li><strong>Registros leídos:</strong> ${data.estadisticas_generales.registros_leidos}</li>
                                    <li><strong>Registros válidos:</strong> ${data.estadisticas_generales.registros_validos}</li>
                                    <li><strong>Registros omitidos:</strong> ${data.estadisticas_generales.registros_omitidos}</li>
                                    <li><strong>Empleados únicos:</strong> ${data.estadisticas_generales.empleados_unicos}</li>
                                    <li><strong>Tiempo total:</strong> ${data.estadisticas_generales.tiempo_total_procesamiento}</li>
                                </ul>
                            </div>
                        </div>
                        
                        ${data.total_errores > 0 ? `
                        <div class="mt-3">
                            <h6><i class="fas fa-exclamation-triangle text-warning"></i> Errores Detectados (${data.total_errores})</h6>
                            <div class="small" style="max-height: 200px; overflow-y: auto;">
                                ${data.detalle_errores.map(error => 
                                    `<div class="alert alert-warning py-2">
                                        <strong>Línea ${error.linea}:</strong> ${error.error}
                                    </div>`
                                ).join('')}
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-primary" id="btnVerDetalles" data-data="${encodeURIComponent(JSON.stringify(data))}">
                                <i class="fas fa-search-plus"></i> Ver Detalles Completos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Asignar evento al botón de detalles
        document.getElementById('btnVerDetalles')?.addEventListener('click', function() {
            try {
                const jsonData = decodeURIComponent(this.getAttribute('data-data'));
                const parsedData = JSON.parse(jsonData);
                window.mostrarDetallesCompletos(parsedData);
            } catch(e) {
                console.error('Error al parsear datos:', e);
            }
        });
        
        resultDiv.innerHTML = html;
        
        // Re-asignar evento después de insertar HTML
        document.getElementById('btnVerDetalles')?.addEventListener('click', function() {
            try {
                const jsonData = decodeURIComponent(this.getAttribute('data-data'));
                const parsedData = JSON.parse(jsonData);
                window.mostrarDetallesCompletos(parsedData);
            } catch(e) {
                console.error('Error al parsear datos:', e);
            }
        });
    }
    
    // Función para mostrar error
    function showError(error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-circle"></i> Error</h6>
                <p>${error}</p>
            </div>
        `;
    }
    
    // Función para actualizar estadísticas
    function actualizarEstadisticas(data) {
        document.getElementById('lastProcess').textContent = 'Ahora';
        document.getElementById('lastProcess').className = 'badge bg-success';
        
        const processedFiles = parseInt(document.getElementById('processedFiles').textContent);
        document.getElementById('processedFiles').textContent = processedFiles + 1;
        
        if (data.estadisticas_generales.formato_detectado.includes('Simple')) {
            const formats = parseInt(document.getElementById('detectedFormats').textContent);
            document.getElementById('detectedFormats').textContent = formats + 1;
        }
        
        // Simular registros de hoy (esto podría venir del servidor)
        const todayRecords = parseInt(document.getElementById('todayRecords').textContent);
        document.getElementById('todayRecords').textContent = todayRecords + data.estadisticas_generales.registros_validos;
    }
    
    // Función para cargar historial
    function cargarHistorial() {
        fetch(baseUrl + '/configuracion/getRecentHistory?t=' + new Date().getTime())
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('HTTP ' + response.status + ': ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                const historyDiv = document.getElementById('recentHistory');
                
                console.log('Historial data:', JSON.stringify(data, null, 2));
                
                // Verificar si la respuesta es exitosa y tiene procesamientos
                if (!data.success || !data.procesamientos || data.procesamientos.length === 0) {
                    historyDiv.innerHTML = '<p class="text-muted small">No hay historial reciente</p>';
                    return;
                }
                
                const html = data.procesamientos.map(item => {
                    console.log('Item:', JSON.stringify(item, null, 2));
                    
                    // Mostrar todos los campos disponibles
                    let fileName = 'Sin nombre';
                    let fileSize = 'N/A';
                    let isSuccess = false;
                    let dateStr = '';
                    let detalles = '';
                    
                    // Buscar el nombre del archivo en cualquier campo posible
                    for (const key in item) {
                        if (item[key] && typeof item[key] === 'string') {
                            if (key.includes('archivo') && key.includes('nombre')) {
                                fileName = item[key];
                            }
                            if (key.includes('archivo') && key.includes('tamano')) {
                                fileSize = item[key];
                            }
                            if (key === 'estado') {
                                isSuccess = (item[key] === 'exito');
                            }
                            if (key.includes('fecha') || key === 'created_at') {
                                dateStr = item[key];
                            }
                            if (key === 'detalles' || key === 'error') {
                                detalles = item[key];
                            }
                        }
                    }
                    
                    return `
                    <div class="small mb-2 p-2 border rounded" style="cursor: pointer;" onclick="mostrarDetallesHistorial(${item.id})">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${fileName}</strong>
                            </div>
                            <span class="badge ${isSuccess ? 'bg-success' : 'bg-danger'}">
                                ${isSuccess ? 'Éxito' : 'Error'}
                            </span>
                        </div>
                        <div class="text-muted">
                            ${fileSize} • 
                            ${dateStr ? new Date(dateStr).toLocaleString() : 'N/A'}
                        </div>
                        ${detalles ? `<div class="text-danger small mt-1">${detalles}</div>` : ''}
                    </div>
                `}).join('');
                
                historyDiv.innerHTML = html;
            })
            .catch(error => {
                console.error('Error cargando historial:', error);
                document.getElementById('recentHistory').innerHTML = 
                    '<p class="text-danger small">Error: ' + error.message + '</p>';
            });
    }
    
    // Función para mostrar resultados detallados
    window.mostrarDetallesCompletos = function(data) {
        const modalElement = document.getElementById('resultsModal');
        const detailedResults = document.getElementById('detailedResults');
        
        if (!modalElement || !detailedResults) {
            alert('Modal no encontrado');
            return;
        }
        
        const est = data.estadisticas_generales || {};
        const resumen = est.resumen_insercion || {};
        const fechasSin = est.fechas_sin_asistencia || {};
        
        // Construir HTML con los detalles mejorados
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-file"></i> Información del Archivo</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><td class="text-muted">Nombre</td><td><strong>${est.nombre_archivo || 'N/A'}</strong></td></tr>
                                <tr><td class="text-muted">Tamaño</td><td>${est.tamano || 'N/A'}</td></tr>
                                <tr><td class="text-muted">Formato Detectado</td><td><span class="badge bg-info">${est.formato_detectado || 'N/A'}</span></td></tr>
                                <tr><td class="text-muted">Confianza</td><td>${est.confianza_formato || 'N/A'}%</td></tr>
                                <tr><td class="text-muted">Registros Leídos</td><td><span class="badge bg-secondary">${est.registros_leidos || 0}</span></td></tr>
                                <tr><td class="text-muted">Registros Válidos</td><td><span class="badge bg-success">${est.registros_validos || 0}</span></td></tr>
                                <tr><td class="text-muted">Registros Omitidos</td><td><span class="badge bg-warning">${est.registros_omitidos || 0}</span></td></tr>
                                <tr><td class="text-muted">Empleados Únicos</td><td><span class="badge bg-primary">${est.empleados_unicos || 0}</span></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-clock"></i> Tiempos de Procesamiento</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><td class="text-muted">Tiempo Total</td><td><strong>${data.tiempo_total_procesamiento || 'N/A'} segundos</strong></td></tr>
                                <tr><td class="text-muted">Tiempo Inserción</td><td>${resumen.tiempo_procesamiento || 'N/A'} segundos</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-database"></i> Résumen de Inserción</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-2">
                                    <div class="border rounded p-2">
                                        <div class="h4 text-success mb-0">${resumen.insertados || 0}</div>
                                        <small class="text-muted">Insertados</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="border rounded p-2">
                                        <div class="h4 text-primary mb-0">${resumen.actualizados || 0}</div>
                                        <small class="text-muted">Actualizados</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2">
                                        <div class="h4 text-warning mb-0">${resumen.omitidos_duplicados || 0}</div>
                                        <small class="text-muted">Duplicados</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2">
                                        <div class="h4 text-danger mb-0">${resumen.errores || 0}</div>
                                        <small class="text-muted">Errores</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-calendar-check"></i> Fechas Sin Asistencia</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><td class="text-muted">Fechas del Ciclo</td><td><strong>${fechasSin.fechas_ciclo || 0}</strong></td></tr>
                                <tr><td class="text-muted">Sin Entrada/Salida</td><td>${fechasSin.registros_sin_entrada_salida || 0}</td></tr>
                                <tr><td class="text-muted">Empleados Afectados</td><td>${fechasSin.empleados_afectados || 0}</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header ${data.total_errores > 0 ? 'bg-danger' : 'bg-info'} text-white">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Estado del Procesamiento</h6>
                        </div>
                        <div class="card-body">
                            ${data.total_errores > 0 
                                ? `<div class="alert alert-danger mb-0"><strong>${data.total_errores}</strong> errores encontrados durante el procesamiento</div>`
                                : `<div class="alert alert-success mb-0"><i class="fas fa-check-circle"></i> Procesamiento completado exitosamente</div>`
                            }
                        </div>
                    </div>
                </div>
            </div>
            
            ${data.detalle_errores && data.detalle_errores.length > 0 ? `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="fas fa-list-alt"></i> Detalle de Errores (${data.detalle_errores.length})</h6>
                        </div>
                        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead class="table-dark">
                                    <tr><th>Línea</th><th>Tipo</th><th>Error</th></tr>
                                </thead>
                                <tbody>
                                    ${data.detalle_errores.map(e => `
                                        <tr>
                                            <td>${e.linea || 'N/A'}</td>
                                            <td><span class="badge bg-secondary">${e.tipo || 'general'}</span></td>
                                            <td>${e.error || 'Error desconocido'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
            
            <div class="row mt-3">
                <div class="col-12 text-muted text-center">
                    <small><i class="fas fa-info-circle"></i> Información del procesamiento del archivo ${est.nombre_archivo || 'DAT'}</small>
                </div>
            </div>
        `;
        
        detailedResults.innerHTML = html;
        
        // Actualizar título del modal
        const modalTitle = modalElement.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = `Detalles del Procesamiento - ${est.nombre_archivo || 'Archivo DAT'}`;
        }
        
        // Mostrar modal usando Bootstrap
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Agregar evento para exportar resultados
        document.getElementById('exportResults')?.addEventListener('click', function() {
            const est = data.estadisticas_generales || {};
            const resumen = est.resumen_insercion || {};
            
            let contenido = `REPORTE DE PROCESAMIENTO DE ARCHIVO DAT
=============================================
Fecha: ${new Date().toLocaleString()}
Archivo: ${est.nombre_archivo || 'N/A'}
Tamaño: ${est.tamano || 'N/A'}
Formato: ${est.formato_detectado || 'N/A'}
Confianza: ${est.confianza_formato || 'N/A'}%

ESTADÍSTICAS DE REGISTROS
-------------------------
Registros Leídos: ${est.registros_leidos || 0}
Registros Válidos: ${est.registros_validos || 0}
Registros Omitidos: ${est.registros_omitidos || 0}
Empleados Únicos: ${est.empleados_unicos || 0}

RESUMEN DE INSERCIÓN
-------------------
Insertados: ${resumen.insertados || 0}
Actualizados: ${resumen.actualizados || 0}
Duplicados: ${resumen.omitidos_duplicados || 0}
Errores: ${resumen.errores || 0}

TIEMPOS
-------
Tiempo Total: ${data.tiempo_total_procesamiento || 'N/A'} segundos
Tiempo Inserción: ${resumen.tiempo_procesamiento || 'N/A'} segundos

FECHAS SIN ASISTENCIA
---------------------
Fechas del Ciclo: ${est.fechas_sin_asistencia?.fechas_ciclo || 0}
Sin Entrada/Salida: ${est.fechas_sin_asistencia?.registros_sin_entrada_salida || 0}
Empleados Afectados: ${est.fechas_sin_asistencia?.empleados_afectados || 0}

ESTADO: ${data.total_errores > 0 ? 'CON ERRORES (' + data.total_errores + ')' : 'EXITOSO'}
`;
            
            if (data.detalle_errores && data.detalle_errores.length > 0) {
                contenido += '\nDETALLE DE ERRORES\n-----------------\n';
                data.detalle_errores.forEach(e => {
                    contenido += `Línea: ${e.linea || 'N/A'} - ${e.error || 'Error desconocido'}\n`;
                });
            }
            
            // Crear y descargar archivo
            const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'reporte_procesamiento_' + (est.nombre_archivo || 'dat') + '_' + new Date().toISOString().slice(0,10) + '.txt';
            link.click();
        });
    };
    
    // ==================== PROCESAMIENTO COMPLETO ====================
    const formCompleto = document.getElementById('procesamientoCompletoForm');
    const btnCompleto = document.getElementById('btnProcesamientoCompleto');
    const progressCompleto = document.getElementById('progressCompleto');
    const resultadoCompleto = document.getElementById('resultadoCompleto');
    
    if (formCompleto) {
        formCompleto.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(formCompleto);
            
            btnCompleto.disabled = true;
            btnCompleto.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            if (progressCompleto) progressCompleto.style.display = 'block';
            resultadoCompleto.innerHTML = '<div class="alert alert-info"><i class="fas fa-hourglass-half"></i> Ejecutando procesamiento completo...</div>';
            
            try {
                const response = await fetch(baseUrl + '/configuracion/procesarCompleto', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let html = '<div class="alert alert-success"><h5><i class="fas fa-check-circle"></i> Procesamiento Completo Ejecutado</h5>';
                    html += '<p><strong>Tiempo total:</strong> ' + data.tiempo_total + ' segundos</p>';
                    
                    if (data.resumen_general) {
                        const rg = data.resumen_general;
                        html += '<div class="row mt-3">';
                        html += '<div class="col-md-6">';
                        html += '<table class="table table-sm table-bordered">';
                        html += '<tr><th colspan="2" class="table-secondary">Resumen General</th></tr>';
                        html += '<tr><td><strong>Archivo:</strong></td><td>' + rg.archivo_procesado + '</td></tr>';
                        html += '<tr><td><strong>Período:</strong></td><td>' + rg.periodo.inicio + ' al ' + rg.periodo.fin + '</td></tr>';
                        html += '<tr><td><strong>Registros nuevos:</strong></td><td>' + (rg.total_registros_nuevos || 0) + '</td></tr>';
                        html += '<tr><td><strong>Retardos:</strong></td><td class="text-warning">' + (rg.total_retardos || 0) + '</td></tr>';
                        html += '<tr><td><strong>Faltas:</strong></td><td class="text-danger">' + (rg.total_faltas || 0) + '</td></tr>';
                        html += '</table></div>';
                        
                        if (rg.sanciones_evaluadas) {
                            html += '<div class="col-md-6">';
                            html += '<table class="table table-sm table-bordered">';
                            html += '<tr><th colspan="2" class="table-danger">Sanciones</th></tr>';
                            html += '<tr><td><strong>Notas malas:</strong></td><td>' + (rg.sanciones_evaluadas.notas_malas || 0) + '</td></tr>';
                            html += '<tr><td><strong>Oficios:</strong></td><td class="text-warning">' + (rg.sanciones_evaluadas.oficios || 0) + '</td></tr>';
                            html += '<tr><td><strong>Suspensiones:</strong></td><td class="text-danger">' + (rg.sanciones_evaluadas.suspensiones || 0) + '</td></tr>';
                            html += '</table></div>';
                        }
                        html += '</div>';
                    }
                    
                    html += '<hr><h6><i class="fas fa-tasks"></i> Tareas Ejecutadas:</h6><div class="table-responsive"><table class="table table-sm table-striped">';
                    html += '<thead><tr><th>Tarea</th><th>Estado</th><th>Tiempo</th><th>Detalles</th></tr></thead><tbody>';
                    
                    if (data.tareas) {
                        data.tareas.forEach(function(tarea) {
                            const exitoClass = tarea.exito ? 'table-success' : 'table-danger';
                            html += '<tr class="' + exitoClass + '">';
                            html += '<td><i class="fas ' + (tarea.exito ? 'fa-check-circle text-success' : 'fa-times-circle text-danger') + '"></i> ' + tarea.nombre + '</td>';
                            html += '<td>' + (tarea.exito ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Error</span>') + '</td>';
                            html += '<td>' + tarea.tiempo + 's</td>';
                            html += '<td><small>';
                            if (tarea.detalles) {
                                for (const [key, value] of Object.entries(tarea.detalles)) {
                                    html += key.replace(/_/g, ' ') + ': ' + value + '<br>';
                                }
                            }
                            html += '</small></td></tr>';
                        });
                    }
                    html += '</tbody></table></div></div>';
                    resultadoCompleto.innerHTML = html;
                } else {
                    resultadoCompleto.innerHTML = '<div class="alert alert-danger"><h5><i class="fas fa-exclamation-circle"></i> Error</h5><p>' + (data.error || 'Error desconocido') + '</p></div>';
                }
            } catch (error) {
                resultadoCompleto.innerHTML = '<div class="alert alert-danger"><h5><i class="fas fa-exclamation-circle"></i> Error de Conexión</h5><p>' + error.message + '</p></div>';
            } finally {
                btnCompleto.disabled = false;
                btnCompleto.innerHTML = '<i class="fas fa-rocket"></i> Ejecutar Todo';
                if (progressCompleto) progressCompleto.style.display = 'none';
            }
        });
    }
    

    // ==================== CALCULAR RETARDOS ====================
    const calcForm = document.getElementById('calcularRetardosForm');
    const calcBtn = document.getElementById('calcularBtn');
    const retardosResult = document.getElementById('retardosResult');
    const resultsModalElement = document.getElementById('resultsModal');
    const detailedResults = document.getElementById('detailedResults');
    
    calcForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fecha_inicio = document.getElementById('fecha_inicio').value;
        const fecha_fin = document.getElementById('fecha_fin').value;
        
        calcBtn.disabled = true;
        calcBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calculando...';
        retardosResult.innerHTML = '';
        
        fetch(baseUrl + '/configuracion/calcularRetardos?fecha_inicio=' + fecha_inicio + '&fecha_fin=' + fecha_fin, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            calcBtn.disabled = false;
            calcBtn.innerHTML = '<i class="fas fa-calculator"></i> Calcular';
            
            if (data.success) {
                const r = data.resumen;
                let html = '<div class="alert alert-success">';
                html += '<h6><i class="fas fa-check-circle"></i> ' + data.mensaje + '</h6>';
                html += '<div class="row mt-2">';
                html += '<div class="col-md-6">';
                html += '<table class="table table-sm table-bordered">';
                html += '<tr><td><strong>Período</strong></td><td>' + r.periodo.inicio + ' al ' + r.periodo.fin + '</td></tr>';
                html += '<tr><td><strong>Días Laborables</strong></td><td>' + r.dias_laborables + '</td></tr>';
                html += '<tr><td><strong>Empleados Procesados</strong></td><td>' + r.empleados_procesados + '</td></tr>';
                html += '<tr><td><strong>Registros Eliminados</strong></td><td>' + r.eliminados + '</td></tr>';
                html += '</table>';
                html += '</div>';
                html += '<div class="col-md-6">';
                html += '<table class="table table-sm table-bordered">';
                html += '<tr class="table-success"><td><strong>A Tiempo</strong></td><td><strong>' + r.a_tiempo + '</strong></td></tr>';
                
                // Mostrar cada tipo
                if (r.detalle) {
                    r.detalle.forEach(function(item) {
                        let cls = '';
                        if (item.tipo_retraso === 'retardo_menor') cls = 'table-warning';
                        else if (item.tipo_retraso === 'retardo_mayor') cls = 'table-danger';
                        else if (item.tipo_retraso.includes('comision')) cls = 'table-info';
                        html += '<tr class="' + cls + '"><td><strong>' + item.tipo_retraso + '</strong></td><td><strong>' + item.total + '</strong></td></tr>';
                    });
                }
                
                html += '<tr class="table-dark"><td><strong>Total Insertados</strong></td><td><strong>' + r.retardos_insertados + '</strong></td></tr>';
                html += '</table>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                
                retardosResult.innerHTML = html;
                
                // También mostrar en modal
                let modalHtml = '<h5 class="mb-3"><i class="fas fa-calculator"></i> Resultado del Cálculo de Retardos</h5>';
                modalHtml += html;
                detailedResults.innerHTML = modalHtml;
                const modal = new bootstrap.Modal(resultsModalElement);
                modal.show();
            } else {
                retardosResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error: ' + (data.error || 'Error desconocido') + '</div>';
            }
        })
        .catch(error => {
            calcBtn.disabled = false;
            calcBtn.innerHTML = '<i class="fas fa-calculator"></i> Calcular';
            retardosResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error: ' + error.message + '</div>';
        });
    });
    
    // ==================== CORREGIR ASISTENCIA ====================
    const corregirForm = document.getElementById('corregirAsistenciaForm');
    const corregirBtn = document.getElementById('corregirBtn');
    const corregirResult = document.getElementById('corregirResult');
    
    if (corregirForm) {
        corregirForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fecha_inicio = document.getElementById('corregir_fecha_inicio').value;
            const fecha_fin = document.getElementById('corregir_fecha_fin').value;
            const corregir_fechas = document.getElementById('corregir_fechas').checked ? '1' : '0';
            
            corregirBtn.disabled = true;
            corregirBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Corrigiendo...';
            corregirResult.innerHTML = '';
            
            fetch(baseUrl + '/configuracion/corregirAsistencia?fecha_inicio=' + fecha_inicio + '&fecha_fin=' + fecha_fin + '&corregir_fechas=' + corregir_fechas, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                corregirBtn.disabled = false;
                corregirBtn.innerHTML = '<i class="fas fa-wrench"></i> Corregir';
                
                if (data.success) {
                    const r = data.resumen;
                    let html = '<div class="alert alert-info">';
                    html += '<h6><i class="fas fa-check-circle"></i> ' + data.mensaje + '</h6>';
                    html += '<table class="table table-sm table-bordered mt-2">';
                    html += '<tr><td><strong>Período</strong></td><td>' + r.periodo.inicio + ' al ' + r.periodo.fin + '</td></tr>';
                    html += '<tr><td><strong>Registros Analizados</strong></td><td>' + r.registros_analizados + '</td></tr>';
                    html += '<tr><td><strong>Registros Agrupados</strong></td><td>' + r.registros_agrupados + '</td></tr>';
                    html += '<tr class="table-warning"><td><strong>Registros Corregidos</strong></td><td><strong>' + r.corregidos + '</strong></td></tr>';
                    html += '<tr><td><strong>Corrección de Fechas</strong></td><td>' + (r.corregir_fechas ? 'Activada' : 'Desactivada') + '</td></tr>';
                    html += '</table>';
                    
                    if (data.detalle && data.detalle.length > 0) {
                        html += '<h6 class="mt-3">Detalle de Correcciones:</h6>';
                        html += '<div class="table-responsive" style="max-height: 300px;">';
                        html += '<table class="table table-sm table-striped">';
                        html += '<thead><tr><th>Empleado</th><th>Fecha</th><th>Hora Entrada</th><th>Hora Salida</th><th>Horario</th><th>Mensaje</th></tr></thead>';
                        html += '<tbody>';
                        data.detalle.forEach(function(item) {
                            html += '<tr>';
                            html += '<td>' + (item.empleado || 'N/A') + '</td>';
                            html += '<td>' + item.fecha + '</td>';
                            html += '<td>' + (item.hora_entrada || '-') + '</td>';
                            html += '<td>' + (item.hora_salida || '-') + '</td>';
                            html += '<td>' + (item.horario || '-') + '</td>';
                            html += '<td>' + item.mensaje + '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table></div>';
                    }
                    
                    html += '</div>';
                    corregirResult.innerHTML = html;
                } else {
                    corregirResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error: ' + (data.error || 'Error desconocido') + '</div>';
                }
            })
            .catch(error => {
                corregirBtn.disabled = false;
                corregirBtn.innerHTML = '<i class="fas fa-wrench"></i> Corregir';
                corregirResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error: ' + error.message + '</div>';
            });
        });
    }
});
</script>