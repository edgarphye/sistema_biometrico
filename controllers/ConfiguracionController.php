<?php
// Cargar configuración primero
        if (!defined('DB_HOST')) {
            require_once __DIR__ . '/../config.php';
        }
        
        require_once __DIR__ . '/BaseController.php';
        require_once __DIR__ . '/../models/ZKTecoUniversalParser.php'; // Asegurar existencia
        require_once __DIR__ . '/../models/ZKTecoAsistenciaInserter.php'; // Asegurar existencia

/**
 * Controller de Configuración con Sistema ZKTeco Completo
 * Maneja carga de archivos .DAT con mapeo dinámico e inserción inteligente
 */
class ConfiguracionController extends BaseController
{
    private $asistenciaModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }
    
    // Configuración por defecto del procesador ZKTeco
    private $configuracionZKTeco = [
        'procesar_fines_semana' => false,
        'procesar_registros_fallidos' => true,  // 🔥 CAMBIADO: procesar resultado=0
        'ajuste_horario_nocturno' => true,
        'mantener_fecha_original' => false,     // Nueva opción: mantener fecha exacta del archivo .dat
        'calidad_minima' => null,
        'tipos_verificacion_permitidos' => null,
        'validar_campos_requeridos' => true,
        'crear_empleados_faltantes' => false,
        'proteger_registros_validados' => true,  // No modificar registros ya justificados y validados
        'idioma' => 'es'
    ];
    
    /**
     * Página principal de configuración
     */
    public function index()
    {
        // Verificar sesión antes de requireAuth
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado - Sesión requerida'], 401);
            exit;
        }
        
        // Cargar helper CSRF si no está disponible
        if (!class_exists('Csrf')) {
            require_once __DIR__ . '/../helpers/Csrf.php';
        }
        
        // Establecer variables para el layout
        $title = 'Configuración del Sistema';
        
        // FIX: El método Csrf::generate() no está definido, lo que causa un error fatal.
        // Se implementa la generación del token directamente aquí para solucionar el error,
        // manteniendo la compatibilidad con el método Csrf::validate() existente.
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf_token = $_SESSION['csrf_token'];
        $content = '
<div class="container-fluid py-4">
    <h2 class="mb-4 text-primary"><i class="fas fa-cogs me-2"></i>Configuración del Sistema</h2>
    
    <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">Carga Biométrica</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">Mantenimiento</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Historial</button>
        </li>
    </ul>

    <div class="tab-content" id="configTabsContent">
        <!-- Tab Carga -->
        <div class="tab-pane fade show active" id="upload" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Carga de Archivo Biométrico (.DAT)</h5>
                </div>
                <div class="card-body">
                    <form id="upload-zk-form" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
                        <div class="mb-3">
                            <label for="zk_file" class="form-label">Seleccionar archivo .dat o .txt</label>
                            <input class="form-control" type="file" id="zk_file" name="zk_file" accept=".dat,.txt" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="mantener_fecha_original" name="mantener_fecha_original">
                            <label class="form-check-label" for="mantener_fecha_original">
                                Mantener fecha original del archivo (no ajustar por horario nocturno)
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="procesar_fines_semana" name="procesar_fines_semana">
                            <label class="form-check-label" for="procesar_fines_semana">
                                Procesar registros de fines de semana
                            </label>
                        </div>
                        <button type="submit" class="btn btn-pantone-primary" id="btn-upload">
                            <i class="fas fa-upload me-2"></i>Cargar y Procesar
                        </button>
                    </form>
                    <div id="upload-results" class="mt-4"></div>
                </div>
            </div>
        </div>

        <!-- Tab Mantenimiento -->
        <div class="tab-pane fade" id="maintenance" role="tabpanel">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Recálculo de Retardos</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Recalcula los retardos y faltas basándose en los horarios asignados y los registros de asistencia existentes.</p>
                            <form id="recalc-form">
                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Fecha Inicio</label>
                                        <input type="date" class="form-control" name="fecha_inicio" value="' . date('Y-m-01') . '" required>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" class="form-control" name="fecha_fin" value="' . date('Y-m-t') . '" required>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-warning" id="btn-recalc">
                                    <i class="fas fa-calculator me-2"></i>Recalcular Retardos
                                </button>
                            </form>
                            <div id="recalc-results" class="mt-3"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Corrección de Asistencia</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Unifica registros de entrada/salida y corrige fechas para turnos nocturnos.</p>
                            <form id="correct-form">
                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Fecha Inicio</label>
                                        <input type="date" class="form-control" name="fecha_inicio" value="' . date('Y-m-01') . '" required>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" class="form-control" name="fecha_fin" value="' . date('Y-m-t') . '" required>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="corregir_fechas" value="1" checked>
                                    <label class="form-check-label">Corregir fechas (horario nocturno)</label>
                                </div>
                                <button type="button" class="btn btn-info text-white" id="btn-correct">
                                    <i class="fas fa-magic me-2"></i>Corregir Asistencia
                                </button>
                            </form>
                            <div id="correct-results" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Limpieza de Logs</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Elimina logs antiguos de procesamiento y dispositivos para liberar espacio en la base de datos.</p>
                            <form id="clean-logs-form">
                                <div class="mb-3">
                                    <label class="form-label">Eliminar logs anteriores a:</label>
                                    <select class="form-select" name="dias">
                                        <option value="30">30 días</option>
                                        <option value="60" selected>60 días</option>
                                        <option value="90">90 días</option>
                                        <option value="180">6 meses</option>
                                        <option value="365">1 año</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-danger" id="btn-clean-logs">
                                    <i class="fas fa-trash-alt me-2"></i>Limpiar Logs
                                </button>
                            </form>
                            <div id="clean-logs-results" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Historial -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Historial de Procesamientos</h5>
                    <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-history">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="history-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Archivo</th>
                                    <th>Tamaño</th>
                                    <th>Estado</th>
                                    <th>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center">Cargando historial...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById("upload-zk-form").addEventListener("submit", function(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const resultsDiv = document.getElementById("upload-results");
    const uploadBtn = document.getElementById("btn-upload");

    uploadBtn.disabled = true;
    uploadBtn.innerHTML = \'<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...\';
    resultsDiv.innerHTML = \'<div class="alert alert-info">Procesando archivo, por favor espere... Esto puede tardar varios minutos.</div>\';

    fetch("' . BASE_URL . '/configuracion/uploadZkteco", {
        method: "POST",
        body: formData
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                return { success: false, error: `El servidor devolvió una respuesta inesperada. Detalles: ${text}` };
            }
        });
    })
    .then(data => {
        if (data.success) {
            let html = \'<div class="alert alert-success">\' + data.message + \'</div>\';
            
            if (data.html_errores) {
                html += data.html_errores;
            }
            
            resultsDiv.innerHTML = html;
            loadHistory();
        } else {
            resultsDiv.innerHTML = \'<div class="alert alert-danger"><strong>Error:</strong> \' + (data.error || "Ocurrió un error desconocido.") + \'</div>\';
        }
    })
    .catch(error => {
        console.error("Error en fetch:", error);
        resultsDiv.innerHTML = \'<div class="alert alert-danger"><strong>Error de Conexión:</strong> \' + error.message + \'. Verifique la consola para más detalles.</div>\';
    })
    .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = \'<i class="fas fa-upload me-2"></i>Cargar y Procesar\';
    });
});

// Script para Recálculo
document.getElementById("btn-recalc").addEventListener("click", function() {
    const btn = this;
    const form = document.getElementById("recalc-form");
    const resultsDiv = document.getElementById("recalc-results");
    const formData = new FormData(form);
    
    btn.disabled = true;
    btn.innerHTML = \'<span class="spinner-border spinner-border-sm"></span> Calculando...\';
    resultsDiv.innerHTML = \'<div class="alert alert-info">Recalculando retardos...</div>\';
    
    const params = new URLSearchParams(formData);
    
    fetch("' . BASE_URL . '/configuracion/calcularRetardos?" + params.toString())
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = \'<div class="alert alert-success">\' + data.mensaje + \'</div>\';
            if (data.resumen) {
                html += \'<ul class="mb-0">\';
                html += \'<li>Registros analizados: \' + data.resumen.registros_analizados + \'</li>\';
                html += \'<li>Retardos insertados: \' + data.resumen.retardos_insertados + \'</li>\';
                html += \'<li>Faltas detectadas: \' + data.resumen.faltas + \'</li>\';
                html += \'</ul>\';
            }
            resultsDiv.innerHTML = html;
        } else {
            resultsDiv.innerHTML = \'<div class="alert alert-danger">Error: \' + data.error + \'</div>\';
        }
    })
    .catch(err => resultsDiv.innerHTML = \'<div class="alert alert-danger">Error de conexión</div>\')
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = \'<i class="fas fa-calculator me-2"></i>Recalcular Retardos\';
    });
});

// Script para Corrección
document.getElementById("btn-correct").addEventListener("click", function() {
    const btn = this;
    const form = document.getElementById("correct-form");
    const resultsDiv = document.getElementById("correct-results");
    const formData = new FormData(form);
    
    btn.disabled = true;
    btn.innerHTML = \'<span class="spinner-border spinner-border-sm"></span> Corrigiendo...\';
    resultsDiv.innerHTML = \'<div class="alert alert-info">Corrigiendo asistencia...</div>\';
    
    const params = new URLSearchParams(formData);
    
    fetch("' . BASE_URL . '/configuracion/corregirAsistencia?" + params.toString())
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = \'<div class="alert alert-success">\' + data.mensaje + \'</div>\';
            if (data.resumen) {
                html += \'<ul class="mb-0">\';
                html += \'<li>Registros analizados: \' + data.resumen.registros_analizados + \'</li>\';
                html += \'<li>Registros corregidos: \' + data.resumen.corregidos + \'</li>\';
                html += \'</ul>\';
            }
            resultsDiv.innerHTML = html;
        } else {
            resultsDiv.innerHTML = \'<div class="alert alert-danger">Error: \' + data.error + \'</div>\';
        }
    })
    .catch(err => resultsDiv.innerHTML = \'<div class="alert alert-danger">Error de conexión</div>\')
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = \'<i class="fas fa-magic me-2"></i>Corregir Asistencia\';
    });
});

// Script para Limpieza de Logs
document.getElementById("btn-clean-logs").addEventListener("click", function() {
    if(!confirm("¿Está seguro de eliminar los logs antiguos? Esta acción no se puede deshacer.")) return;

    const btn = this;
    const form = document.getElementById("clean-logs-form");
    const resultsDiv = document.getElementById("clean-logs-results");
    const formData = new FormData(form);
    
    btn.disabled = true;
    btn.innerHTML = \'<span class="spinner-border spinner-border-sm"></span> Limpiando...\';
    resultsDiv.innerHTML = \'<div class="alert alert-info">Limpiando logs...</div>\';
    
    const params = new URLSearchParams(formData);
    
    fetch("' . BASE_URL . '/configuracion/limpiarLogs?" + params.toString(), {
        headers: {
            "X-Requested-With": "XMLHttpRequest"
        }
    })
    .then(response => {
        if (response.status === 401) {
            window.location.href = "' . BASE_URL . '/login";
            return Promise.resolve({ success: false, error: "Sesión expirada" });
        }
        return response.clone().text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                return { success: false, error: "Respuesta inesperada del servidor: " + text };
            }
        });
    })
    .then(data => {
        if (data.success) {
            let html = \'<div class="alert alert-success">\' + data.mensaje + \'</div>\';
            if (data.detalles) {
                html += \'<ul class="mb-0">\';
                html += \'<li>Logs de procesamiento eliminados: \' + data.detalles.zkteco_logs + \'</li>\';
                html += \'<li>Logs de dispositivos eliminados: \' + data.detalles.device_logs + \'</li>\';
                html += \'</ul>\';
            }
            resultsDiv.innerHTML = html;
        } else {
            resultsDiv.innerHTML = \'<div class="alert alert-danger">Error: \' + (data.error || "Error desconocido") + \'</div>\';
        }
    })
    .catch(err => {
        console.error(err);
        resultsDiv.innerHTML = \'<div class="alert alert-danger">Error de conexión: \' + err.message + \'</div>\';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = \'<i class="fas fa-trash-alt me-2"></i>Limpiar Logs\';
    });
});

// Script para Historial
function loadHistory() {
    const tbody = document.querySelector("#history-table tbody");
    const baseUrl = "' . BASE_URL . '";
    fetch(baseUrl + "/configuracion/getRecentHistory?t=" + new Date().getTime())
    .then(response => response.json())
    .then(data => {
        console.log("Historia data:", data);
        if (data.success && data.procesamientos) {
            tbody.innerHTML = "";
            if (data.procesamientos.length === 0) {
                tbody.innerHTML = "<tr><td colspan=\'5\' class=\'text-center\'>No hay historial disponible</td></tr>";
                return;
            }
            data.procesamientos.forEach(proc => {
                const row = "<tr>" +
                    "<td>" + (proc.fecha_inicio || proc.created_at || "N/A") + "</td>" +
                    "<td>" + (proc.nombre_archivo || proc.archivo_nombre || "Sin nombre") + "</td>" +
                    "<td>" + (proc.tamano_formateado || proc.archivo_tamano || "N/A") + "</td>" +
                    "<td><span class=\"badge bg-" + (proc.estado_clase || (proc.estado === "exito" ? "success" : "danger")) + "\">" + (proc.exito == 1 || proc.estado === "exito" ? "Exito" : "Error") + "</span></td>" +
                    "<td><button class=\"btn btn-sm btn-link\" onclick=\"mostrarDetallesHistorial(" + proc.id + ")\">Ver</button></td>" +
                "</tr>";
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = "<tr><td colspan=\'5\' class=\'text-center text-danger\'>Error al cargar historial</td></tr>";
        }
    })
    .catch(err => {
        console.error("Error:", err);
        tbody.innerHTML = "<tr><td colspan=\'5\' class=\'text-center text-danger\'>Error de conexion</td></tr>";
    });
}

document.getElementById("btn-refresh-history").addEventListener("click", loadHistory);
document.addEventListener("DOMContentLoaded", loadHistory);

// Función para mostrar detalles de un procesamiento del historial
function mostrarDetallesHistorial(id) {
    console.log("mostrarDetallesHistorial called with id:", id);
    fetch("' . BASE_URL . '/configuracion/getProcesamientoDetalle?id=" + id + "&t=" + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.procesamiento) {
                const p = data.procesamiento;
                const detailedResults = document.getElementById("detailedResults");
                
                const fileName = p.nombre_archivo || p.archivo_nombre || "Sin nombre";
                const fileSize = p.tamano_formateado || p.archivo_tamano || "N/A";
                const isSuccess = p.exito == 1 || p.estado === "exito";
                const dateStr = p.fecha_inicio || p.created_at || p.creado_at || p.fecha_ejecucion;
                const registros = p.registros_leidos || p.registros_procesados || 0;
                
                let html = "<div class=\"row\">";
                html += "<div class=\"col-12\"><p><strong>Archivo:</strong> " + fileName + "</p></div>";
                html += "<div class=\"col-12\"><p><strong>Fecha:</strong> " + dateStr + "</p></div>";
                html += "<div class=\"col-12\"><p><strong>Registros:</strong> " + registros + "</p></div>";
                html += "<div class=\"col-12\"><p><strong>Estado:</strong> " + (isSuccess ? "Exitoso" : "Error") + "</p></div>";
                html += "</div>";
                
                if (detailedResults) {
                    detailedResults.innerHTML = html;
                }
                
                const modal = new bootstrap.Modal(document.getElementById("resultsModal"));
                modal.show();
            } else {
                alert("No se pudieron cargar los detalles");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Error al cargar los detalles: " + error.message);
        });
}
</script>

<!-- Modal para mostrar detalles del historial -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i>Detalles del Procesamiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailedResults">
                <p>Cargando...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
';
        // Incluir el layout que automáticamente cargará el contenido de configuración
        include __DIR__ . '/../views/layout.php';
    }
    
    /**
     * Procesa archivo ZKTeco .DAT
     */
    public function uploadZkteco()
    {
        // Verificar sesión antes de requireAuth
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado - Sesión requerida'], 401);
            exit;
        }
        
        // Añadir validación de seguridad CSRF que faltaba.
        if (!class_exists('Csrf')) {
            require_once __DIR__ . '/../helpers/Csrf.php';
        }
        $csrf = $_POST['csrf_token'] ?? '';
        // Se asume que el método validate() existe, ya que es usado en otros controladores.
        if (!\Csrf::validate($csrf)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token de seguridad inválido. Por favor, recargue la página.'], 403);
            return;
        }

        if (!isset($_FILES['zk_file'])) {
            $this->jsonResponse(['error' => 'No se ha seleccionado ningún archivo'], 400);
            return;
        }
        
        $file = $_FILES['zk_file'];
        
        // Leer opciones de configuración del request
        $mantenerFechaOriginal = isset($_POST['mantener_fecha_original']) && $_POST['mantener_fecha_original'] == '1';
        $procesarFinesSemana = isset($_POST['procesar_fines_semana']) && $_POST['procesar_fines_semana'] == '1';
        
        // Actualizar configuración del parser con las opciones seleccionadas
        $configuracionParser = $this->configuracionZKTeco;
        $configuracionParser['mantener_fecha_original'] = $mantenerFechaOriginal;
        $configuracionParser['procesar_fines_semana'] = $procesarFinesSemana;
        
        try {
            // Iniciar log de procesamiento
            $logId = $this->iniciarLogProcesamiento($file);
            
            // Validar archivo
            $validacion = $this->validarArchivo($file);
            if (!$validacion['valido']) {
                $this->actualizarLogProcesamiento($logId, false, $validacion['error'], null);
                $this->jsonResponse(['error' => $validacion['error']], 400);
                return;
            }
            
            // Fase 1: Procesar archivo con parser universal
            $parser = new ZKTecoUniversalParser($configuracionParser);
            $this->cargarMapeoEmpleados($parser);
            
            $resultadoParser = $parser->procesarArchivo($file['tmp_name']);
            
            if (!$resultadoParser['exito']) {
                $this->actualizarLogProcesamiento($logId, false, $resultadoParser['error'], $resultadoParser);
                $this->jsonResponse(['error' => 'Error en procesamiento: ' . $resultadoParser['error']], 500);
                return;
            }
            
            // Fase 2: Insertar registros en base de datos
            $inserter = new ZKTecoAsistenciaInserter([
                'crear_empleados_automaticos' => true,
                'evitar_duplicados' => true,
                'proteger_registros_validados' => true
            ]);
            
            // Cargar mapeo de empleados también al inserter
            $mapeo = $this->obtenerMapeoEmpleados();
            $inserter->setMapeoEmpleados($mapeo);
            
            $resultadoInsercion = $inserter->insertarRegistros($resultadoParser['registros']); // Esto ahora calcula retardos
            
            // Fase 2.5: Detectar y registrar fechas sin asistencia del rango del archivo
            $fechasProcesadas = [];
            $rangoFechas = ['fecha_min' => null, 'fecha_max' => null];
            
            if (!empty($resultadoParser['registros'])) {
                $fechas = [];
                foreach ($resultadoParser['registros'] as $reg) {
                    if (!empty($reg['fecha'])) {
                        $fechas[] = $reg['fecha'];
                    }
                }
                $fechasProcesadas = array_unique($fechas);
                
                // Calcular rango de fechas del archivo
                if (!empty($fechas)) {
                    $rangoFechas['fecha_min'] = min($fechas);
                    $rangoFechas['fecha_max'] = max($fechas);
                }
            }
            
            // Pasar rango de fechas para detectar días sin asistencia
            $resultadoSinAsistencia = $inserter->registrarFechasSinAsistencia($fechasProcesadas, $rangoFechas);
            
            // Fase 3: Guardar formato detectado para futuros procesamientos
            $this->guardarFormatoDetectado($resultadoParser['formato_detectado']);
            
            // Fase 4: Actualizar log final
            $metadataFinal = [
                'formato_detectado' => $resultadoParser['formato_detectado'],
                'mapeo_campos' => $resultadoParser['mapeo_campos'],
                'estadisticas_parser' => $resultadoParser['estadisticas'] ?? [],
                'estadisticas_insercion' => $resultadoInsercion['estadisticas'] ?? [],
                'fechas_sin_asistencia' => $resultadoSinAsistencia
            ];
            
            $this->actualizarLogProcesamiento($logId, $resultadoInsercion['exito'], null, $metadataFinal);
            
            // Preparar resumen de errores de inserción por tipo
            $erroresInsercion = $resultadoInsercion['estadisticas']['errores_detallados'] ?? [];
            $resumenErrores = [];
            if (!empty($erroresInsercion)) {
                foreach ($erroresInsercion as $error) {
                    $tipo = $error['tipo'] ?? 'desconocido';
                    if (!isset($resumenErrores[$tipo])) {
                        $resumenErrores[$tipo] = 0;
                    }
                    $resumenErrores[$tipo]++;
                }
            }

            // Preparar respuesta exitosa
            $respuesta = [
                'success' => true,
                'message' => 'Archivo ZKTeco procesado correctamente',
                'estadisticas_generales' => [
                    'nombre_archivo' => $file['name'],
                    'tamano' => $this->formatearBytes($file['size']),
                    'formato_detectado' => $this->formatoDetectadoLegible($resultadoParser['formato_detectado'] ?? []),
                    'confianza_formato' => ($resultadoParser['formato_detectado']['confianza'] ?? 0) . '%',
                    'registros_leidos' => $resultadoParser['estadisticas']['registros_leidos'] ?? 0,
                    'registros_validos' => $resultadoParser['estadisticas']['registros_validos'] ?? 0,
                    'registros_omitidos' => $resultadoParser['estadisticas']['registros_omitidos'] ?? 0,
                    'empleados_unicos' => $resultadoParser['estadisticas']['empleados_unicos'] ?? 0,
                    'periodo_procesado' => 'No disponible',
                    'resumen_insercion' => [
                        'insertados' => $resultadoInsercion['estadisticas']['insertados'] ?? 0,
                        'actualizados' => $resultadoInsercion['estadisticas']['actualizados'] ?? 0,
                        'errores' => $resultadoInsercion['estadisticas']['errores'] ?? 0,
                        'tiempo_procesamiento' => $resultadoInsercion['estadisticas']['tiempo_total_procesamiento'] ?? 0,
                        'resumen_errores' => $resumenErrores
                    ],
                    'fechas_sin_asistencia' => [
                        'fechas_ciclo' => $resultadoSinAsistencia['fechas_detectadas'] ?? 0,
                        'registros_sin_entrada_salida' => $resultadoSinAsistencia['registros_insertados'] ?? 0,
                        'empleados_afectados' => count($resultadoSinAsistencia['empleados_sin_registro'] ?? [])
                    ]
                ],
                'detalle_errores' => array_slice($resultadoParser['errores'] ?? [], 0, 10), // Primeros 10 errores del parser
                'detalle_errores_insercion' => array_slice($erroresInsercion, 0, 50), // Aumentamos a 50 para más detalle
                'total_errores' => count($resultadoParser['errores'] ?? []),
                'total_errores_insercion' => $resultadoInsercion['estadisticas']['errores'] ?? 0,
                'tiempo_total_procesamiento' => round($resultadoInsercion['estadisticas']['tiempo_total_procesamiento'] ?? 0, 2) . ' segundos',
                'detalles_procesados' => $resultadoInsercion['detalles'] ?? [],
                'html_errores' => $this->generarReporteHtmlErrores($resultadoInsercion['estadisticas'], $erroresInsercion)
            ];
            
            $this->jsonResponse($respuesta);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'procesarDat']);
            $this->jsonResponse(['error' => 'Error del sistema: ' . $e->getMessage()], 500);
        }
    }
    
    // Métodos privados helpers para procesamiento
    private function iniciarLogProcesamiento($file) {
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        // Calcular SHA256 del archivo para detectar duplicados
        $sha256 = hash_file('sha256', $file['tmp_name']);
        
        $stmt = $conn->prepare("
            INSERT INTO zkteco_procesamiento_logs 
            (archivo_nombre, archivo_tamano, archivo_sha256, exito, fecha_ejecucion, creado_at)
            VALUES (?, ?, ?, 0, NOW(), NOW())
        ");
        
        $stmt->execute([
            $file['name'],
            $file['size'],
            $sha256
        ]);
        
        return $conn->lastInsertId();
    }
    
    private function validarArchivo($file) {
        $allowedExtensions = ['dat', 'txt'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            return [
                'valido' => false,
                'error' => 'Tipo de archivo no permitido. Solo se aceptan archivos .dat y .txt'
            ];
        }
        
        if ($file['size'] > 50 * 1024 * 1024) { // 50MB
            return [
                'valido' => false,
                'error' => 'El archivo es demasiado grande. Máximo permitido: 50MB'
            ];
        }
        
        return ['valido' => true];
    }
    
    private function cargarMapeoEmpleados($parser) {
        $mapeo = $this->obtenerMapeoEmpleados();
        $parser->setMapeoEmpleados($mapeo);
    }
    
    private function obtenerMapeoEmpleados() {
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        // Obtener mapeos desde la columna zkteo_id en tabla empleados
        $stmt = $conn->query("
            SELECT id, zkteo_id 
            FROM empleados 
            WHERE zkteo_id IS NOT NULL AND zkteo_id != ''
        ");
        
        $mapeo = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mapeo[$row['zkteo_id']] = (int)$row['id'];
        }
        
        return $mapeo;
    }
    
    private function guardarFormatoDetectado($formatoDetectado) {
        if (empty($formatoDetectado) || !isset($formatoDetectado['nombre'])) {
            return; // No guardar si no hay formato detectado válido
        }
        
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO zkteco_formatos 
            (dispositivo, formato, conteo_campos, mapa_tipos, confianza, ultimo_uso)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            confianza = VALUES(confianza),
            ultimo_uso = VALUES(ultimo_uso),
            veces_usado = veces_usado + 1
        ");
        
        $campos = $formatoDetectado['campos'] ?? [];
        $stmt->execute([
            'ZKTeco',
            $formatoDetectado['nombre'],
            count($campos),
            json_encode($campos),
            $formatoDetectado['confianza'] ?? 0
        ]);
    }
    
    private function actualizarLogProcesamiento($logId, $exito, $error = null, $metadata = null) {
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        // Determinar estado
        $estado = $exito ? 'exito' : 'error';
        if ($error) {
            $estado = 'error';
        } elseif ($exito) {
            $estado = 'exito';
        }
        
        // Obtener registros procesdos del metadata
        $registrosProcesados = 0;
        if ($metadata && isset($metadata['estadisticas_insercion']['registros_procesados'])) {
            $registrosProcesados = $metadata['estadisticas_insercion']['registros_procesados'];
        } elseif ($metadata && isset($metadata['estadisticas_parser']['registros_procesados'])) {
            $registrosProcesados = $metadata['estadisticas_parser']['registros_procesados'];
        }
        
        $stmt = $conn->prepare("
            UPDATE zkteco_procesamiento_logs 
            SET estado = ?, 
                exito = ?, 
                errores = ?, 
                metadata = ?,
                registros_procesados = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $estado,
            $exito ? 1 : 0,
            $error,
            json_encode($metadata),
            $registrosProcesados,
            $logId
        ]);
    }
    
    private function formatearBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function formatoDetectadoLegible($formato) {
        if (!$formato || !is_array($formato)) {
            return 'No detectado';
        }
        
        $nombreFormato = $formato['nombre'] ?? 'desconocido';
        
        $nombres = [
            'zkteco_6_campos' => 'Estándar ZKTeco (6 campos)',
            'zkteco_7_campos' => 'Extendido ZKTeco (7 campos)',
            'zkteco_8_campos' => 'Completo ZKTeco (8 campos)',
            'generico' => 'Formato genérico'
        ];
        
        return $nombres[$nombreFormato] ?? $nombreFormato;
    }
    
    private function formatearPeriodo($estadisticas) {
        if (empty($estadisticas['fecha_inicio']) || empty($estadisticas['fecha_fin'])) {
            return 'No disponible';
        }
        
        $inicio = new DateTime($estadisticas['fecha_inicio']);
        $fin = new DateTime($estadisticas['fecha_fin']);
        
        return $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y');
    }
    
    /**
     * Genera un reporte HTML amigable de los errores para mostrar en la interfaz
     */
    private function generarReporteHtmlErrores($estadisticas, $erroresDetallados) {
        if (empty($estadisticas['errores'])) {
            return null;
        }

        $html = '<div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">';
        $html .= '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Atención: Problemas durante la carga</h4>';
        $html .= '<p>El archivo se procesó, pero <strong>' . $estadisticas['errores'] . '</strong> registros no pudieron ser insertados.</p>';
        $html .= '<hr>';
        
        // Resumen por tipo
        $resumen = [];
        foreach ($erroresDetallados as $error) {
            $tipo = is_array($error) ? ($error['tipo'] ?? 'general') : 'general';
            if (!isset($resumen[$tipo])) $resumen[$tipo] = 0;
            $resumen[$tipo]++;
        }

        $html .= '<div class="row mb-3">';
        foreach ($resumen as $tipo => $cantidad) {
            $nombreTipo = ucfirst(str_replace('_', ' ', $tipo));
            $icono = 'fa-bug';
            $clase = 'secondary';
            
            if ($tipo == 'empleado_no_encontrado') {
                $nombreTipo = 'Empleados No Encontrados';
                $icono = 'fa-user-slash';
                $clase = 'danger';
            }

            $html .= '<div class="col-md-6 mb-2">';
            $html .= '<div class="d-flex align-items-center border rounded p-2 bg-white">';
            $html .= '<div class="flex-shrink-0 text-' . $clase . '"><i class="fas ' . $icono . ' fa-2x"></i></div>';
            $html .= '<div class="flex-grow-1 ms-3">';
            $html .= '<h6 class="mb-0">' . $cantidad . '</h6>';
            $html .= '<small class="text-muted">' . $nombreTipo . '</small>';
            $html .= '</div></div></div>';
        }
        $html .= '</div>';

        // Tabla de detalles (Colapsable)
        $html .= '<button class="btn btn-sm btn-outline-dark mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#detallesErroresCollapse">';
        $html .= '<i class="fas fa-list"></i> Ver lista detallada de errores</button>';
        
        $html .= '<div class="collapse" id="detallesErroresCollapse"><div class="card card-body bg-light p-0">';
        $html .= '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
        $html .= '<table class="table table-sm table-striped table-bordered mb-0" style="font-size: 0.85rem;">';
        $html .= '<thead class="table-dark sticky-top"><tr><th>ZK ID</th><th>Error</th><th>Sugerencia</th></tr></thead><tbody>';
        
        $limite = 100; // Mostrar máximo 100 errores para no saturar el DOM
        $contador = 0;
        
        foreach ($erroresDetallados as $error) {
            if ($contador >= $limite) break;
            
            if (is_array($error)) {
                $zkId = $error['zk_empleado_id'] ?? '-';
                $mensaje = $error['mensaje'] ?? 'Error desconocido';
                $sugerencia = $error['sugerencia'] ?? '';
                
                $html .= "<tr><td class='text-center fw-bold'>{$zkId}</td><td>{$mensaje}</td><td><small class='text-muted'>{$sugerencia}</small></td></tr>";
            } else {
                $html .= "<tr><td colspan='3'>" . htmlspecialchars($error) . "</td></tr>";
            }
            $contador++;
        }
        
        if (count($erroresDetallados) > $limite) {
            $restantes = count($erroresDetallados) - $limite;
            $html .= "<tr><td colspan='3' class='text-center text-muted fst-italic'>... y {$restantes} errores más ...</td></tr>";
        }
        
        $html .= '</tbody></table></div></div></div>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';

        return $html;
    }
    
    /**
     * Obtiene historial de procesamientos para AJAX
     */
    public function getRecentHistory() {
        try {
            $this->requireAuth();
            
            require_once __DIR__ . '/../models/Database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Verificar si la tabla existe
            $tableExists = $conn->query("SHOW TABLES LIKE 'zkteco_procesamiento_logs'")->rowCount() > 0;
            if (!$tableExists) {
                $this->jsonResponse([
                    'success' => true,
                    'procesamientos' => []
                ]);
                return;
            }
            
            // Obtener las columnas de la tabla
            $columnsStmt = $conn->query("SHOW COLUMNS FROM zkteco_procesamiento_logs");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $stmt = $conn->query("
                SELECT * FROM zkteco_procesamiento_logs 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            
            $procesamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear para frontend
            foreach ($procesamientos as &$procesamiento) {
                // Obtener valores de los campos
                $archivoNombre = '';
                $archivoTamano = '';
                $estado = 'desconocido';
                $detalles = '';
                $createdAt = '';
                $fechaEjecucion = '';
                $registrosProcesados = 0;
                
                foreach ($columns as $col) {
                    switch ($col) {
                        case 'archivo_nombre':
                            $archivoNombre = $procesamiento[$col] ?? '';
                            break;
                        case 'archivo_tamano':
                            $archivoTamano = $procesamiento[$col] ?? '';
                            break;
                        case 'estado':
                            $estado = $procesamiento[$col] ?? 'desconocido';
                            break;
                        case 'detalles':
                            $detalles = $procesamiento[$col] ?? '';
                            break;
                        case 'created_at':
                            $createdAt = $procesamiento[$col] ?? '';
                            break;
                        case 'fecha_ejecucion':
                            $fechaEjecucion = $procesamiento[$col] ?? '';
                            break;
                        case 'registros_procesados':
                            $registrosProcesados = $procesamiento[$col] ?? 0;
                            break;
                    }
                }
                
                $procesamiento['archivo_nombre'] = $archivoNombre;
                $procesamiento['archivo_tamano'] = $archivoTamano;
                $procesamiento['estado'] = $estado;
                $procesamiento['detalles'] = $detalles;
                $procesamiento['created_at'] = $createdAt;
                $procesamiento['fecha_ejecucion'] = $fechaEjecucion;
                $procesamiento['registros_procesados'] = $registrosProcesados;
                $procesamiento['exito'] = ($estado === 'exito') ? 1 : 0;
                $procesamiento['estado_clase'] = ($estado === 'exito') ? 'success' : 'danger';
                
                // Alias para compatibilidad con JavaScript
                $procesamiento['nombre_archivo'] = $archivoNombre;
                $procesamiento['tamano_formateado'] = $archivoTamano;
                $procesamiento['fecha_inicio'] = $createdAt ?: $fechaEjecucion;
            }
            
            $this->jsonResponse([
                'success' => true,
                'procesamientos' => $procesamientos
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Obtiene los detalles de un procesamiento específico
     */
    public function getProcesamientoDetalle() {
        try {
            $this->requireAuth();
            
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'error' => 'ID no proporcionado']);
                return;
            }
            
            require_once __DIR__ . '/../models/Database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT * FROM zkteco_procesamiento_logs 
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            $procesamiento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$procesamiento) {
                $this->jsonResponse(['success' => false, 'error' => 'Procesamiento no encontrado']);
                return;
            }
            
            // Formatear datos
            $procesamiento['tamano_formateado'] = $procesamiento['archivo_tamano'] ?? 'N/A';
            $procesamiento['exito'] = ($procesamiento['estado'] === 'exito') ? 1 : 0;
            $procesamiento['error'] = $procesamiento['detalles'] ?? '';
            $procesamiento['nombre_archivo'] = $procesamiento['archivo_nombre'] ?? '';
            $procesamiento['registros_leidos'] = $procesamiento['registros_procesados'] ?? 0;
            $procesamiento['registros_validos'] = $procesamiento['registros_procesados'] ?? 0;
            $procesamiento['formato_detectado'] = 'N/A';
            
            $this->jsonResponse([
                'success' => true,
                'procesamiento' => $procesamiento
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Calcula retardos para todos los empleados basándose en sus horarios
     * Usa el calendario laboral del sistema operativo (lunes-viernes)
     * Registra retardos Y faltas (días sin asistencia)
     */
    public function calcularRetardos()
    {
        try {
            require_once __DIR__ . '/../models/Database.php';
            require_once __DIR__ . '/../models/EmpleadoHorarios.php';
            require_once __DIR__ . '/../models/HorarioLaboral.php';

            $db = new Database();
            $conn = $db->getConnection();
            $empleadoHorarios = new EmpleadoHorarios();
            $horarioLaboral = new HorarioLaboral();

            // Obtener parámetros
            $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');

            // Verificar sesión
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
                return;
            }

            // 1. Eliminar retardos existentes en el período para evitar duplicados
            $stmtDel = $conn->prepare("DELETE FROM retardos WHERE fecha BETWEEN ? AND ?");
            $stmtDel->execute([$fecha_inicio, $fecha_fin]);
            $eliminados = $stmtDel->rowCount();

            // 2. Marcar asistencias incompletas como por_definir (sin entrada o sin salida)
            $tipoAsistenciaPorDefinir = 'por_definir';
            $stmtTipoAsistencia = $conn->query("SHOW COLUMNS FROM asistencia LIKE 'tipo_asistencia'");
            $colTipoAsistencia = $stmtTipoAsistencia ? $stmtTipoAsistencia->fetch(PDO::FETCH_ASSOC) : null;
            $tipoAsistenciaDef = $colTipoAsistencia['Type'] ?? '';
            if (strpos($tipoAsistenciaDef, "'por_definir'") === false && strpos($tipoAsistenciaDef, "'por_defnir'") !== false) {
                // Compatibilidad con esquemas históricos donde el enum quedó con typo.
                $tipoAsistenciaPorDefinir = 'por_defnir';
            }

            $stmtPorDefinir = $conn->prepare(
                "UPDATE asistencia
                 SET tipo_asistencia = ?, updated_at = NOW()
                 WHERE fecha BETWEEN ? AND ?
                   AND (
                        hora_entrada IS NULL OR
                        hora_salida IS NULL
                   )"
            );
            $stmtPorDefinir->execute([$tipoAsistenciaPorDefinir, $fecha_inicio, $fecha_fin]);
            $marcadosPorDefinir = $stmtPorDefinir->rowCount();

            // 3. Obtener solo registros completos (entrada y salida) para cálculo de retardos
            $stmtAsistencia = $conn->prepare(
                "SELECT id, empleado_id, fecha, hora_entrada, hora_salida, zk_empleado_id_original 
                 FROM asistencia 
                 WHERE fecha BETWEEN ? AND ?
                   AND hora_entrada IS NOT NULL
                   AND hora_salida IS NOT NULL"
            );
            $stmtAsistencia->execute([$fecha_inicio, $fecha_fin]);
            $asistencias = $stmtAsistencia->fetchAll(PDO::FETCH_ASSOC);

            $total_insertados = 0;
            $total_sin_retardo = 0;
            $total_tolerancia = 0;
            $total_faltas = 0; // Este enfoque no calculará faltas, se enfoca en retardos
            $total_menor = 0;
            $total_mayor = 0;
            $errores = [];

            // 4. Iterar sobre cada registro completo de asistencia para calcular el retardo
            foreach ($asistencias as $asis) {
                try {
                    // a. Obtener el horario específico para ese empleado y esa fecha
                    $horario = $empleadoHorarios->getHorarioPorFecha($asis['empleado_id'], $asis['fecha']);

                    // Si no hay horario, no se puede calcular el retardo
                    if (empty($horario) || empty($horario['hora_entrada'])) {
                        continue;
                    }

                    // b. Calcular minutos de diferencia contra la hora de entrada programada
                    $horaEntradaProgramada = $horario['hora_entrada'];
                    $horaEntradaRegistradaTimestamp = strtotime($asis['hora_entrada']);

                    $minutos_diff = floor(($horaEntradaRegistradaTimestamp - strtotime($horaEntradaProgramada)) / 60);
                    if ($minutos_diff < 0) {
                        $minutos_diff = 0;
                    }

                    // b.2. Clasificar el retardo usando la lógica centralizada del modelo HorarioLaboral
                    $clasificacion = $horarioLaboral->clasificarRetardo($minutos_diff);

                    if (!$clasificacion['es_retardo']) {
                        // Es puntual o está dentro de la tolerancia
                        $conn->prepare("UPDATE asistencia SET tipo_asistencia = 'normal', updated_at = NOW() WHERE id = ?")
                            ->execute([$asis['id']]);
                        if ($clasificacion['tipo'] === 'tolerancia') {
                            $total_tolerancia++;
                        }
                        $total_sin_retardo++;
                        continue;
                    }

                    if ($clasificacion['tipo'] === 'falta') {
                        // 31+ minutos = FALTA
                        $total_faltas++;
                        
                        // Marcar asistencia como falta (por_definir) para que aparezca como incidencia
                        $conn->prepare("UPDATE asistencia SET tipo_asistencia = 'por_definir', updated_at = NOW() WHERE id = ?")
                            ->execute([$asis['id']]);
                        
                        // Las faltas (31+ min) SÍ se insertan en retardos para contar en notas malas
                        $stmtInsFalta = $conn->prepare(
                            "INSERT INTO retardos (empleado_id, zk_empleado_id_original, fecha, hora_entrada, hora_salida, minutos_retardo, tipo_retraso, justificado, asistencia_id, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, 'falta', 0, ?, NOW())"
                        );
                        $stmtInsFalta->execute([
                            $asis['empleado_id'],
                            $asis['zk_empleado_id_original'],
                            $asis['fecha'],
                            substr($asis['hora_entrada'], 0, 5),
                            $asis['hora_salida'] ? substr($asis['hora_salida'], 0, 5) : null,
                            $minutos_diff,
                            $asis['id']
                        ]);
                        continue;
                    }
                    
                    // Es retardo_menor o retardo_mayor
                    $tipo = 'retardo_' . $clasificacion['tipo']; // 'retardo_menor' o 'retardo_mayor'
                    if ($clasificacion['tipo'] === 'menor') $total_menor++;
                    if ($clasificacion['tipo'] === 'mayor') $total_mayor++;
                    
                    // Marcar asistencia completa con retardo
                    $conn->prepare("UPDATE asistencia SET tipo_asistencia = 'con_retardo', updated_at = NOW() WHERE id = ?")
                        ->execute([$asis['id']]);
                    
                    // Cortar horas a 5 caracteres (HH:MM)
                    $hora_entrada_cortada = substr($asis['hora_entrada'], 0, 5);
                    $hora_salida_cortada = $asis['hora_salida'] ? substr($asis['hora_salida'], 0, 5) : null;

                    $stmtIns = $conn->prepare(
                        "INSERT INTO retardos 
                        (empleado_id, zk_empleado_id_original, fecha, hora_entrada, hora_salida, minutos_retardo, tipo_retraso, justificado, asistencia_id, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())"
                    );
                    $stmtIns->execute([
                        $asis['empleado_id'],
                        $asis['zk_empleado_id_original'],
                        $asis['fecha'],
                        $hora_entrada_cortada,
                        $hora_salida_cortada,
                        $minutos_diff,
                        $tipo,
                        $asis['id']
                    ]);
                    $total_insertados++;
                } catch (Exception $e) {
                    $errores[] = "Error procesando asistencia ID {$asis['id']}: " . $e->getMessage();
                }
            }
            
            // Lógica para manejar faltas (comision_todo_dia) - opcional pero recomendado
            // Esta parte es más compleja, la dejaremos para una futura mejora si se requiere.

            // 4. Obtener estadísticas finales
            $stmtStats = $conn->query("
                SELECT tipo_retraso, COUNT(*) as total 
                FROM retardos 
                WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
                GROUP BY tipo_retraso
            ");
            $stats = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

            // Contar sanciones creadas por faltas
            $stmtSancionesCount = $conn->prepare("
                SELECT COUNT(*) as total FROM sanciones 
                WHERE fecha_inicio BETWEEN ? AND ? AND tipo_retardo = 'falta_31_minutos'
            ");
            $stmtSancionesCount->execute([$fecha_inicio, $fecha_fin]);
            $sancionesCreadas = $stmtSancionesCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Recálculo de retardos completado',
                'resumen' => [
                    'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
                    'registros_incompletos_marcados_por_definir' => $marcadosPorDefinir,
                    'registros_analizados' => count($asistencias),
                    'eliminados' => $eliminados,
                    'tolerancia_0_10' => $total_tolerancia,
                    'a_tiempo' => $total_sin_retardo,
                    'retardos_menores' => $total_menor,
                    'retardos_mayores' => $total_mayor,
                    'faltas' => $total_faltas,
                    'retardos_insertados' => $total_insertados,
                    'sanciones_creadas' => $sancionesCreadas,
                    'detalle' => $stats
                ],
                'errores' => $errores
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Corrige registros de asistencia basándose en horarios de ciclos, bloques_ciclos y horarios_laborales
     * Unifica entradas y salidas por empleado + fecha
     */
    public function corregirAsistencia()
    {
        try {
            require_once __DIR__ . '/../models/Database.php';
            require_once __DIR__ . '/../models/EmpleadoHorarios.php';
            require_once __DIR__ . '/../models/HorarioLaboral.php';

            $db = new Database();
            $conn = $db->getConnection();
            $conn->beginTransaction();
            
            $horarioLaboral = new HorarioLaboral();

            // Obtener parámetros
            $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
            $corregir_fechas = isset($_GET['corregir_fechas']) && $_GET['corregir_fechas'] == '1';

            // Verificar sesión
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
                return;
            }

            // 1. Obtener todos los registros de asistencia en el rango
            $stmtAsistencia = $conn->prepare(
                "SELECT a.id, a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, 
                        a.zk_empleado_id_original, NULL AS datetime_original,
                        CONCAT(e.nombre, ' ', e.apellido) AS numero_empleado
                 FROM asistencia a
                 LEFT JOIN empleados e ON a.empleado_id = e.id
                 WHERE a.fecha BETWEEN ? AND ?
                 ORDER BY a.empleado_id, a.fecha, a.hora_entrada"
            );
            $stmtAsistencia->execute([$fecha_inicio, $fecha_fin]);
            $asistencias = $stmtAsistencia->fetchAll(PDO::FETCH_ASSOC);

            $corregidos = 0;
            $errores = [];
            $detalle = [];

            // 2. Fase 1: CORRECCIÓN DE FECHAS (horario nocturno 00:00-04:00)
            if ($corregir_fechas) {
                foreach ($asistencias as $asis) {
                    if (!$asis['hora_entrada']) continue;
                    
                    $horaEntrada = substr($asis['hora_entrada'], 0, 5);
                    
                    // Si la hora está entre 00:00 y 04:00, verificar si debe mover al día anterior
                    if ($horaEntrada >= '00:00' && $horaEntrada <= '04:00') {
                        $fechaAnterior = date('Y-m-d', strtotime($asis['fecha'] . ' -1 day'));
                        
                        // Verificar si la fecha anterior es fin de semana (sábado=6, domingo=0)
                        $diaSemanaAnterior = date('w', strtotime($fechaAnterior));
                        $esFinDeSemanaAnterior = ($diaSemanaAnterior == 0 || $diaSemanaAnterior == 6);
                        
                        // Si es fin de semana, NO mover el registro
                        if ($esFinDeSemanaAnterior) {
                            $nombreDiaAnterior = ($diaSemanaAnterior == 0) ? 'Domingo' : 'Sábado';
                            $detalle[] = [
                                'empleado' => $asis['numero_empleado'],
                                'fecha' => $asis['fecha'],
                                'hora_entrada' => $asis['hora_entrada'],
                                'estado' => 'sin_cambio',
                                'mensaje' => "Hora temprana (00:00-04:00) pero {$nombreDiaAnterior} anterior - se mantiene fecha"
                            ];
                            continue;
                        }
                        
                        // Verificar si hay registro de entrada en fecha anterior (día laborable)
                        $stmtVerificar = $conn->prepare(
                            "SELECT id FROM asistencia 
                             WHERE empleado_id = ? AND fecha = ? AND hora_entrada IS NOT NULL
                             LIMIT 1"
                        );
                        $stmtVerificar->execute([$asis['empleado_id'], $fechaAnterior]);
                        $registroAnterior = $stmtVerificar->fetch();

                        if (!$registroAnterior) {
                            $detalle[] = [
                                'empleado' => $asis['numero_empleado'],
                                'fecha' => $asis['fecha'],
                                'hora_entrada' => $asis['hora_entrada'],
                                'estado' => 'sin_cambio',
                                'mensaje' => 'Hora temprana (00:00-04:00) pero sin registro de entrada en día anterior - se mantiene fecha'
                            ];
                        } else {
                            // Hay registro de entrada en día anterior laborable
                            // Mover este registro a fecha anterior
                            $conn->prepare("UPDATE asistencia SET fecha = ? WHERE id = ?")
                                ->execute([$fechaAnterior, $asis['id']]);
                            
                            $detalle[] = [
                                'empleado' => $asis['numero_empleado'],
                                'fecha' => $asis['fecha'],
                                'hora_entrada' => $asis['hora_entrada'],
                                'estado' => 'fecha_corregida',
                                'mensaje' => "Fecha corregida: {$asis['fecha']} -> {$fechaAnterior}"
                            ];
                            $corregidos++;
                        }
                    }
                }
            }

            // 3. Fase 2: Recargar registros después de corrección de fechas
            $stmtAsistencia = $conn->prepare(
                "SELECT a.id, a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, 
                        a.zk_empleado_id_original, NULL AS datetime_original,
                        CONCAT(e.nombre, ' ', e.apellido) AS numero_empleado
                 FROM asistencia a
                 LEFT JOIN empleados e ON a.empleado_id = e.id
                 WHERE a.fecha BETWEEN ? AND ?
                 ORDER BY a.empleado_id, a.fecha, a.hora_entrada"
            );
            $stmtAsistencia->execute([$fecha_inicio, $fecha_fin]);
            $asistencias = $stmtAsistencia->fetchAll(PDO::FETCH_ASSOC);

            // 4. Agrupar por empleado y fecha para unificar entradas/salidas
            $agrupados = [];
            foreach ($asistencias as $asis) {
                $key = $asis['empleado_id'] . '_' . $asis['fecha'];
                if (!isset($agrupados[$key])) {
                    $agrupados[$key] = [
                        'empleado_id' => $asis['empleado_id'],
                        'fecha' => $asis['fecha'],
                        'numero_empleado' => $asis['numero_empleado'],
                        'hora_entrada' => $asis['hora_entrada'],
                        'hora_salida' => $asis['hora_salida'],
                        'ids' => [$asis['id']],
                        'datetime_original' => $asis['datetime_original']
                    ];
                } else {
                    $agrupados[$key]['ids'][] = $asis['id'];
                    // Mantener la entrada más temprana y salida más tardía
                    if ($asis['hora_entrada'] && (!$agrupados[$key]['hora_entrada'] || $asis['hora_entrada'] < $agrupados[$key]['hora_entrada'])) {
                        $agrupados[$key]['hora_entrada'] = $asis['hora_entrada'];
                    }
                    if ($asis['hora_salida'] && (!$agrupados[$key]['hora_salida'] || $asis['hora_salida'] > $agrupados[$key]['hora_salida'])) {
                        $agrupados[$key]['hora_salida'] = $asis['hora_salida'];
                    }
                }
            }

            // 5. Procesar cada grupo (empleado + fecha) para unificar
            $idsGlobalesEliminar = [];
            foreach ($agrupados as $key => $reg) {
                try {
                    // Unificar: actualizar el primer registro con entrada y salida combinadas
                    if (count($reg['ids']) > 1) {
                        // Obtener el horario del empleado para esta fecha (solo si es necesario unificar)
                        $horario = $horarioLaboral->getHorarioPorFecha($reg['empleado_id'], $reg['fecha']);
                        
                        if (!$horario) {
                            $detalle[] = [
                                'empleado' => $reg['numero_empleado'],
                                'fecha' => $reg['fecha'],
                                'estado' => 'sin_horario',
                                'mensaje' => 'No se encontró horario para el empleado en esta fecha'
                            ];
                            continue;
                        }

                        $idPrincipal = $reg['ids'][0];
                        $idsEliminar = array_slice($reg['ids'], 1);

                        // Actualizar registro principal con entrada más temprana y salida más tardía
                        $stmtUpdate = $conn->prepare(
                            "UPDATE asistencia SET hora_entrada = ?, hora_salida = ? WHERE id = ?"
                        );
                        $stmtUpdate->execute([
                            $reg['hora_entrada'],
                            $reg['hora_salida'],
                            $idPrincipal
                        ]);

                        // Acumular IDs para eliminación masiva
                        if (!empty($idsEliminar)) {
                            $idsGlobalesEliminar = array_merge($idsGlobalesEliminar, $idsEliminar);
                        }

                        $detalle[] = [
                            'empleado' => $reg['numero_empleado'],
                            'fecha' => $reg['fecha'],
                            'hora_entrada' => $reg['hora_entrada'],
                            'hora_salida' => $reg['hora_salida'],
                            'horario' => $horario['hora_entrada'] . '-' . $horario['hora_salida'],
                            'estado' => 'unificado',
                            'mensaje' => 'Unificados ' . count($reg['ids']) . ' registros en uno solo (entrada: ' . substr($reg['hora_entrada'], 0, 5) . ', salida: ' . ($reg['hora_salida'] ? substr($reg['hora_salida'], 0, 5) : '-') . ')'
                        ];
                        $corregidos++;
                    }

                } catch (Exception $e) {
                    $errores[] = "Error en {$reg['empleado_id']}/{$reg['fecha']}: " . $e->getMessage();
                }
            }

            // Ejecutar eliminación masiva por lotes
            if (!empty($idsGlobalesEliminar)) {
                $chunks = array_chunk($idsGlobalesEliminar, 1000);
                foreach ($chunks as $chunk) {
                    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                    $conn->prepare("DELETE FROM asistencia WHERE id IN ($placeholders)")->execute($chunk);
                }
            }

            $conn->commit();

            $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Corrección de asistencia completada',
                'resumen' => [
                    'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
                    'registros_analizados' => count($asistencias),
                    'registros_agrupados' => count($agrupados),
                    'corregidos' => $corregidos,
                    'corregir_fechas' => $corregir_fechas
                ],
                'detalle' => array_slice($detalle, 0, 50),
                'errores' => $errores
            ]);

        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Procesamiento Completo: Ejecuta todas las tareas necesarias en secuencia
     * 1. Carga archivo ZKTeco
     * 2. Corrige fechas (horario nocturno 00:00-04:00)
     * 3. Unifica entradas/salidas por empleado+fecha
     * 4. Calcula retardos (tolerancia, menor, mayor, falta)
     * 5. Evalúa sanciones (oficios, suspensiones, términos)
     */
    public function procesarCompleto()
    {
        try {
            require_once __DIR__ . '/../models/Database.php';
            require_once __DIR__ . '/../models/EmpleadoHorarios.php';
            require_once __DIR__ . '/../models/HorarioLaboral.php';
            require_once __DIR__ . '/../models/Retardo.php';

            $db = new Database();
            $conn = $db->getConnection();

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
                return;
            }

            if (!isset($_FILES['zk_file']) || $_FILES['zk_file']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonResponse(['success' => false, 'error' => 'No se ha seleccionado ningún archivo válido'], 400);
                return;
            }

            $file = $_FILES['zk_file'];
            $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-t');
            $corregir_fechas = isset($_POST['corregir_fechas']) && $_POST['corregir_fechas'] == '1';
            $evaluar_sanciones = isset($_POST['evaluar_sanciones']) && $_POST['evaluar_sanciones'] == '1';

            $resultado = [
                'success' => true,
                'tareas' => [],
                'tiempo_total' => 0,
                'resumen_general' => []
            ];

            $inicioTotal = microtime(true);

            // =============================================
            // TAREA 1: Cargar archivo ZKTeco
            // =============================================
            $tarea1Inicio = microtime(true);
            
            $configParser = array_merge($this->configuracionZKTeco, [
                'procesar_fines_semana' => true,
                'mantener_fecha_original' => false,
                'crear_empleados_faltantes' => true
            ]);

            $parser = new ZKTecoUniversalParser($configParser);
            $this->cargarMapeoEmpleados($parser);
            $resultadoParser = $parser->procesarArchivo($file['tmp_name']);

            if (!$resultadoParser['exito']) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error en Carga ZKTeco: ' . $resultadoParser['error']
                ], 500);
                return;
            }

            $inserter = new ZKTecoAsistenciaInserter([
                'crear_empleados_automaticos' => true,
                'evitar_duplicados' => true,
                'proteger_registros_validados' => true
            ]);
            $mapeo = $this->obtenerMapeoEmpleados();
            $inserter->setMapeoEmpleados($mapeo);
            $resultadoInsercion = $inserter->insertarRegistros($resultadoParser['registros']);

            $tarea1Fin = microtime(true);

            $resultado['tareas'][] = [
                'nombre' => '1. Carga de Archivo ZKTeco',
                'exito' => true,
                'tiempo' => round($tarea1Fin - $tarea1Inicio, 2),
                'detalles' => [
                    'formato_detectado' => $resultadoParser['formato_detectado']['nombre'] ?? 'desconocido',
                    'registros_leidos' => $resultadoParser['estadisticas']['registros_leidos'] ?? 0,
                    'registros_validos' => $resultadoParser['estadisticas']['registros_validos'] ?? 0,
                    'insertados' => $resultadoInsercion['estadisticas']['insertados'] ?? 0,
                    'actualizados' => $resultadoInsercion['estadisticas']['actualizados'] ?? 0,
                    'errores' => $resultadoInsercion['estadisticas']['errores'] ?? 0
                ]
            ];

            // =============================================
            // TAREA 2: Corregir Asistencia (fechas y unificación)
            // =============================================
            $tarea2Inicio = microtime(true);
            $conn->beginTransaction();
            
            $horarioLaboral = new HorarioLaboral();

            $stmtAsistencia = $conn->prepare(
                "SELECT a.id, a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, 
                        CONCAT(e.nombre, ' ', e.apellido) AS numero_empleado
                 FROM asistencia a
                 LEFT JOIN empleados e ON a.empleado_id = e.id
                 WHERE a.fecha BETWEEN ? AND ?
                 ORDER BY a.empleado_id, a.fecha, a.hora_entrada"
            );
            $stmtAsistencia->execute([$fecha_inicio, $fecha_fin]);
            $asistencias = $stmtAsistencia->fetchAll(PDO::FETCH_ASSOC);

            $corregidosFecha = 0;
            $corregidosUnificados = 0;
            $sinEntrada = 0;
            $sinSalida = 0;
            $sinAmbos = 0;

            // Corrección de fechas (horario nocturno 00:00-04:00)
            if ($corregir_fechas) {
                foreach ($asistencias as $asis) {
                    if (!$asis['hora_entrada']) continue;
                    $horaEntrada = substr($asis['hora_entrada'], 0, 5);
                    if ($horaEntrada >= '00:00' && $horaEntrada <= '04:00') {
                        $fechaAnterior = date('Y-m-d', strtotime($asis['fecha'] . ' -1 day'));
                        $diaSemanaAnterior = date('w', strtotime($fechaAnterior));
                        if ($diaSemanaAnterior == 0 || $diaSemanaAnterior == 6) continue;
                        
                        $stmtVerificar = $conn->prepare(
                            "SELECT id FROM asistencia WHERE empleado_id = ? AND fecha = ? AND hora_entrada IS NOT NULL LIMIT 1"
                        );
                        $stmtVerificar->execute([$asis['empleado_id'], $fechaAnterior]);
                        if ($stmtVerificar->fetch()) {
                            $conn->prepare("UPDATE asistencia SET fecha = ? WHERE id = ?")
                                ->execute([$fechaAnterior, $asis['id']]);
                            $corregidosFecha++;
                        }
                    }
                }
            }

            // Recargar después de corrección de fechas
            $stmtAsistencia->execute([$fecha_inicio, $fecha_fin]);
            $asistencias = $stmtAsistencia->fetchAll(PDO::FETCH_ASSOC);

            // Agrupar por empleado+fecha para unificar
            $agrupados = [];
            foreach ($asistencias as $asis) {
                $key = $asis['empleado_id'] . '_' . $asis['fecha'];
                if (!isset($agrupados[$key])) {
                    $agrupados[$key] = [
                        'empleado_id' => $asis['empleado_id'],
                        'fecha' => $asis['fecha'],
                        'hora_entrada' => $asis['hora_entrada'],
                        'hora_salida' => $asis['hora_salida'],
                        'ids' => [$asis['id']]
                    ];
                } else {
                    $agrupados[$key]['ids'][] = $asis['id'];
                    if ($asis['hora_entrada'] && (!$agrupados[$key]['hora_entrada'] || $asis['hora_entrada'] < $agrupados[$key]['hora_entrada'])) {
                        $agrupados[$key]['hora_entrada'] = $asis['hora_entrada'];
                    }
                    if ($asis['hora_salida'] && (!$agrupados[$key]['hora_salida'] || $asis['hora_salida'] > $agrupados[$key]['hora_salida'])) {
                        $agrupados[$key]['hora_salida'] = $asis['hora_salida'];
                    }
                }
                
                // Contar tipos de incidencias
                if (empty($asis['hora_entrada']) && empty($asis['hora_salida'])) $sinAmbos++;
                elseif (empty($asis['hora_entrada'])) $sinEntrada++;
                elseif (empty($asis['hora_salida'])) $sinSalida++;
            }

            $idsEliminar = [];
            foreach ($agrupados as $reg) {
                if (count($reg['ids']) > 1) {
                    $idPrincipal = $reg['ids'][0];
                    $idsEliminar = array_merge($idsEliminar, array_slice($reg['ids'], 1));
                    $conn->prepare("UPDATE asistencia SET hora_entrada = ?, hora_salida = ? WHERE id = ?")
                        ->execute([$reg['hora_entrada'], $reg['hora_salida'], $idPrincipal]);
                    $corregidosUnificados++;
                }
            }

            if (!empty($idsEliminar)) {
                foreach (array_chunk($idsEliminar, 1000) as $chunk) {
                    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                    $conn->prepare("DELETE FROM asistencia WHERE id IN ($placeholders)")->execute($chunk);
                }
            }
            $conn->commit();
            $tarea2Fin = microtime(true);

            $resultado['tareas'][] = [
                'nombre' => '2. Corrección de Asistencia',
                'exito' => true,
                'tiempo' => round($tarea2Fin - $tarea2Inicio, 2),
                'detalles' => [
                    'registros_analizados' => count($asistencias),
                    'registros_agrupados' => count($agrupados),
                    'fechas_corregidas' => $corregidosFecha,
                    'registros_unificados' => $corregidosUnificados,
                    'sin_entrada' => $sinEntrada,
                    'sin_salida' => $sinSalida,
                    'sin_entrada_salida' => $sinAmbos
                ]
            ];

            // =============================================
            // TAREA 3: Calcular Retardos
            // =============================================
            $tarea3Inicio = microtime(true);

            $stmtDel = $conn->prepare("DELETE FROM retardos WHERE fecha BETWEEN ? AND ?");
            $stmtDel->execute([$fecha_inicio, $fecha_fin]);
            $eliminados = $stmtDel->rowCount();

            // Marcar registros incompletos como por_definir
            $stmtPorDefinir = $conn->prepare(
                "UPDATE asistencia SET tipo_asistencia = 'por_definir', updated_at = NOW()
                 WHERE fecha BETWEEN ? AND ? AND (hora_entrada IS NULL OR hora_salida IS NULL)"
            );
            $stmtPorDefinir->execute([$fecha_inicio, $fecha_fin]);

            // Obtener solo registros completos para cálculo de retardos
            $stmtAsistencia = $conn->prepare(
                "SELECT id, empleado_id, fecha, hora_entrada, hora_salida, zk_empleado_id_original 
                 FROM asistencia 
                 WHERE fecha BETWEEN ? AND ? AND hora_entrada IS NOT NULL AND hora_salida IS NOT NULL"
            );
            $stmtAsistencia->execute([$fecha_inicio, $fecha_fin]);
            $asistenciasCompletas = $stmtAsistencia->fetchAll(PDO::FETCH_ASSOC);

            $empleadoHorarios = new EmpleadoHorarios();
            $total_a_tiempo = 0;
            $total_tolerancia = 0;
            $total_menor = 0;
            $total_mayor = 0;
            $total_faltas = 0;
            $total_retardos_insertados = 0;

            foreach ($asistenciasCompletas as $asis) {
                $horario = $empleadoHorarios->getHorarioPorFecha($asis['empleado_id'], $asis['fecha']);
                if (empty($horario) || empty($horario['hora_entrada'])) continue;

                $horaEntradaProgramada = $horario['hora_entrada'];
                $minutos_diff = floor((strtotime($asis['hora_entrada']) - strtotime($horaEntradaProgramada)) / 60);
                if ($minutos_diff < 0) $minutos_diff = 0;

                $clasificacion = $horarioLaboral->clasificarRetardo($minutos_diff);

                if (!$clasificacion['es_retardo']) {
                    if ($clasificacion['tipo'] === 'tolerancia') $total_tolerancia++;
                    $total_a_tiempo++;
                    $conn->prepare("UPDATE asistencia SET tipo_asistencia = 'normal', updated_at = NOW() WHERE id = ?")
                        ->execute([$asis['id']]);
                    continue;
                }

                if ($clasificacion['tipo'] === 'falta') {
                    $total_faltas++;
                    // Marcar como por_definir para justificación
                    $conn->prepare("UPDATE asistencia SET tipo_asistencia = 'por_definir', updated_at = NOW() WHERE id = ?")
                        ->execute([$asis['id']]);
                    
                    // Las faltas (31+ min) SÍ se insertan en retardos para contar en notas malas
                    $stmtInsFalta = $conn->prepare(
                        "INSERT INTO retardos (empleado_id, zk_empleado_id_original, fecha, hora_entrada, hora_salida, minutos_retardo, tipo_retraso, justificado, asistencia_id, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, 'falta', 0, ?, NOW())"
                    );
                    $stmtInsFalta->execute([
                        $asis['empleado_id'],
                        $asis['zk_empleado_id_original'],
                        $asis['fecha'],
                        substr($asis['hora_entrada'], 0, 5),
                        $asis['hora_salida'] ? substr($asis['hora_salida'], 0, 5) : null,
                        $minutos_diff,
                        $asis['id']
                    ]);
                    continue;
                }

                $tipo = 'retardo_' . $clasificacion['tipo'];
                if ($clasificacion['tipo'] === 'menor') $total_menor++;
                if ($clasificacion['tipo'] === 'mayor') $total_mayor++;

                $conn->prepare("UPDATE asistencia SET tipo_asistencia = 'con_retardo', updated_at = NOW() WHERE id = ?")
                    ->execute([$asis['id']]);

                $stmtIns = $conn->prepare(
                    "INSERT INTO retardos (empleado_id, zk_empleado_id_original, fecha, hora_entrada, hora_salida, minutos_retardo, tipo_retraso, justificado, asistencia_id, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())"
                );
                $stmtIns->execute([
                    $asis['empleado_id'],
                    $asis['zk_empleado_id_original'],
                    $asis['fecha'],
                    substr($asis['hora_entrada'], 0, 5),
                    $asis['hora_salida'] ? substr($asis['hora_salida'], 0, 5) : null,
                    $minutos_diff,
                    $tipo,
                    $asis['id']
                ]);
                $total_retardos_insertados++;
            }
            $tarea3Fin = microtime(true);

            $resultado['tareas'][] = [
                'nombre' => '3. Cálculo de Retardos',
                'exito' => true,
                'tiempo' => round($tarea3Fin - $tarea3Inicio, 2),
                'detalles' => [
                    'registros_analizados' => count($asistenciasCompletas),
                    'eliminados' => $eliminados,
                    'a_tiempo' => $total_a_tiempo,
                    'dentro_tolerancia' => $total_tolerancia,
                    'retardos_menores' => $total_menor,
                    'retardos_mayores' => $total_mayor,
                    'faltas_31_min' => $total_faltas,
                    'retardos_insertados' => $total_retardos_insertados
                ]
            ];

            // =============================================
            // TAREA 4: Evaluar Sanciones (Opcional)
            // =============================================
            $tarea4Inicio = microtime(true);
            $sancionesEvaluadas = ['oficios' => 0, 'suspensiones' => 0, 'terminos' => 0, 'notas_malas' => 0];

            if ($evaluar_sanciones) {
                $retardoModel = new Retardo();
                
                // Obtener empleados con retardos en el período
                $stmtEmpleados = $conn->prepare(
                    "SELECT DISTINCT empleado_id FROM retardos WHERE fecha BETWEEN ? AND ?"
                );
                $stmtEmpleados->execute([$fecha_inicio, $fecha_fin]);
                $empleadosConRetardos = $stmtEmpleados->fetchAll(PDO::FETCH_ASSOC);

                foreach ($empleadosConRetardos as $emp) {
                    $mes = (int)date('m', strtotime($fecha_inicio));
                    $anio = (int)date('Y', strtotime($fecha_inicio));
                    
                    $resultadoSancion = $retardoModel->evaluarYSancionar($emp['empleado_id'], $mes, $anio);
                    
                    $notas = $retardoModel->calcularNotasMalas($emp['empleado_id'], $mes, $anio);
                    $sancionesEvaluadas['notas_malas'] += $notas['notas_malas'];
                    
                    if ($resultadoSancion['tipo'] === 'oficio') {
                        $sancionesEvaluadas['oficios']++;
                    } elseif ($resultadoSancion['tipo'] === 'suspension') {
                        $sancionesEvaluadas['suspensiones']++;
                    } elseif ($resultadoSancion['tipo'] === 'termino') {
                        $sancionesEvaluadas['terminos']++;
                    }
                }
            }
            $tarea4Fin = microtime(true);

            $resultado['tareas'][] = [
                'nombre' => '4. Evaluación de Sanciones',
                'exito' => true,
                'tiempo' => round($tarea4Fin - $tarea4Inicio, 2),
                'detalles' => $sancionesEvaluadas
            ];

            // Resumen general
            $finTotal = microtime(true);
            $resultado['tiempo_total'] = round($finTotal - $inicioTotal, 2);
            $resultado['resumen_general'] = [
                'archivo_procesado' => $file['name'],
                'tamano' => $this->formatearBytes($file['size']),
                'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
                'total_registros_nuevos' => $resultadoInsercion['estadisticas']['insertados'] ?? 0,
                'total_retardos' => $total_retardos_insertados,
                'total_faltas' => $total_faltas,
                'sanciones_evaluadas' => $sancionesEvaluadas
            ];

            $this->jsonResponse($resultado);

        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            $this->jsonResponse(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Limpia logs antiguos de la base de datos
     */
    public function limpiarLogs()
    {
        // Verificar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
            return;
        }

        $dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 60;
        if ($dias < 1) $dias = 30;

        try {
            require_once __DIR__ . '/../models/Database.php';
            $db = new Database();
            $conn = $db->getConnection();

            // Limpiar zkteco_procesamiento_logs
            $stmt = $conn->prepare("DELETE FROM zkteco_procesamiento_logs WHERE creado_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->execute([$dias]);
            $deletedZk = $stmt->rowCount();

            // Limpiar logs_dispositivos (si existe la tabla)
            $deletedDev = 0;
            try {
                $stmtDev = $conn->prepare("DELETE FROM logs_dispositivos WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmtDev->execute([$dias]);
                $deletedDev = $stmtDev->rowCount();
            } catch (Exception $e) {
                // Ignorar error si la tabla no existe o falla
            }

            $this->jsonResponse([
                'success' => true,
                'mensaje' => "Limpieza completada exitosamente.",
                'detalles' => [
                    'zkteco_logs' => $deletedZk,
                    'device_logs' => $deletedDev
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
