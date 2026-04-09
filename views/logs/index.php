<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/Csrf.php';

$pageTitle = 'Visor de Logs';
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h4 class="mb-0"><i class="fas fa-file-log me-2"></i>Visor de Logs de Errores JavaScript</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Seleccionar Fecha:</label>
                            <input type="date" class="form-control" id="logDate" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Error:</label>
                            <select class="form-select" id="logType">
                                <option value="">Todos</option>
                                <option value="ERROR">Error</option>
                                <option value="WARN">Advertencia</option>
                                <option value="UNHANDLED">No Manejado</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-primary me-2" onclick="cargarLogs()">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                            <button class="btn btn-success" onclick="analizarConAI()">
                                <i class="fas fa-robot me-1"></i> Analizar con IA
                            </button>
                        </div>
                    </div>

                    <div id="logsContainer">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Cargando logs...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Análisis AI -->
<div class="modal fade" id="modalAnalisisAI" tabindex="-1" aria-labelledby="modalAnalisisAILabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalAnalisisAILabel"><i class="fas fa-robot me-2"></i>Análisis de Errores con IA</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="analisisAIContenido">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success" role="status"></div>
                        <p class="mt-2">Analizando errores con IA...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';

function cargarLogs() {
    const fecha = document.getElementById('logDate').value;
    const tipo = document.getElementById('logType').value;
    const container = document.getElementById('logsContainer');
    
    container.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    
    fetch(BASE_URL + '/logs/leer?fecha=' + fecha + '&tipo=' + tipo)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.logs && data.logs.length > 0) {
                let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
                html += '<thead class="table-dark"><tr><th>Fecha/Hora</th><th>Tipo</th><th>Mensaje</th><th>URL</th><th>Archivo</th><th>Acción</th></tr></thead>';
                html += '<tbody>';
                
                data.logs.forEach(log => {
                    const tipoClass = log.tipo === 'ERROR' ? 'danger' : (log.tipo === 'WARN' ? 'warning' : 'secondary');
                    html += '<tr>';
                    html += '<td><small>' + log.fecha + '</small></td>';
                    html += '<td><span class="badge bg-' + tipoClass + '">' + log.tipo + '</span></td>';
                    html += '<td>' + (log.mensaje.length > 100 ? log.mensaje.substring(0, 100) + '...' : log.mensaje) + '</td>';
                    html += '<td><small class="text-muted">' + (log.url || '-') + '</small></td>';
                    html += '<td><small class="text-muted">' + (log.linea || '-') + '</small></td>';
                    html += '<td><button class="btn btn-sm btn-outline-info" onclick="verDetalleLog(\'' + encodeURIComponent(JSON.stringify(log)) + '\')"><i class="fas fa-eye"></i></button></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                html += '<div class="mt-3"><strong>Total:</strong> ' + data.logs.length + ' errores</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-info">No se encontraron errores para la fecha seleccionada.</div>';
            }
        })
        .catch(error => {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar los logs: ' + error + '</div>';
        });
}

function verDetalleLog(logJson) {
    const log = JSON.parse(decodeURIComponent(logJson));
    let html = '<div class="alert alert-secondary">';
    html += '<h5>Mensaje:</h5><pre class="bg-light p-3">' + log.mensaje + '</pre>';
    if (log.stack) {
        html += '<h5 class="mt-3">Stack Trace:</h5><pre class="bg-light p-3">' + log.stack + '</pre>';
    }
    html += '<p class="mt-3"><strong>URL:</strong> ' + (log.url || 'N/A') + '</p>';
    html += '<p><strong>Fecha:</strong> ' + log.fecha + '</p>';
    html += '</div>';
    
    const modal = new bootstrap.Modal(document.getElementById('modalAnalisisAI'));
    document.getElementById('analisisAIContenido').innerHTML = html;
    modal.show();
}

function analizarConAI() {
    const fecha = document.getElementById('logDate').value;
    const modal = new bootstrap.Modal(document.getElementById('modalAnalisisAI'));
    modal.show();
    
    document.getElementById('analisisAIContenido').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success" role="status"></div><p class="mt-2">Analizando errores con IA...</p></div>';
    
    fetch(BASE_URL + '/logs/analizar-ai', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fecha: fecha })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = '<div class="alert alert-info">' + data.resumen + '</div>';
            html += '<h5>Errores Encontrados:</h5>';
            
            data.errores.forEach((err, index) => {
                html += '<div class="card mb-3">';
                html += '<div class="card-header bg-light"><strong>Error ' + (index + 1) + ':</strong> ' + err.mensaje + '</div>';
                html += '<div class="card-body">';
                html += '<p><strong>Causa probable:</strong> ' + err.causa + '</p>';
                html += '<p><strong>Solución sugerida:</strong> ' + err.solucion + '</p>';
                if (err.archivo) {
                    html += '<p><strong>Archivo:</strong> ' + err.archivo + '</p>';
                }
                if (err.fix_disponible) {
                    html += '<button class="btn btn-success btn-sm" onclick="aplicarFix(' + index + ')"><i class="fas fa-wrench me-1"></i> Aplicar Fix</button>';
                }
                html += '</div></div>';
            });
            
            document.getElementById('analisisAIContenido').innerHTML = html;
            window.erroresAI = data.errores;
        } else {
            document.getElementById('analisisAIContenido').innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Error desconocido') + '</div>';
        }
    })
    .catch(error => {
        document.getElementById('analisisAIContenido').innerHTML = '<div class="alert alert-danger">Error al analizar: ' + error + '</div>';
    });
}

function aplicarFix(index) {
    const error = window.erroresAI[index];
    if (!error || !error.fix || !error.archivo) {
        alert('No hay fix disponible para este error');
        return;
    }
    
    if (!confirm('¿Está seguro de aplicar este fix? Se creará un backup del archivo.')) {
        return;
    }
    
    fetch(BASE_URL + '/logs/aplicar-fix', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            archivo: error.archivo,
            fix: error.fix,
            linea: error.linea
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Fix aplicado correctamente. Backup guardado en: ' + data.backup);
            cargarLogs();
        } else {
            alert('Error al aplicar fix: ' + data.error);
        }
    });
}

// Cargar logs al iniciar
document.addEventListener('DOMContentLoaded', cargarLogs);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
