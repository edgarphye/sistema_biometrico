<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesador de Logs ZKTeco - Sistema Biométrico</title>
    <link href="<?php echo BASE_URL; ?>/public/css/modals.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 1200px; margin-top: 30px; }
        .status-success { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .log-container { 
            background: #1e1e1e; 
            color: #00ff00; 
            border: 1px solid #444; 
            border-radius: 5px; 
            padding: 15px; 
            max-height: 400px; 
            overflow-y: auto; 
            font-family: 'Courier New', monospace; 
            font-size: 12px;
        }
        .progress-container { margin: 20px 0; display: none; }
        .card { margin-bottom: 20px; }
        .nav-pills .nav-link.active { background-color: #9F2241; }
    </style>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../layout.php'; ?>
    
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2><i class="fas fa-cog"></i> Procesador de Logs ZKTeco</h2>
                    <p class="text-muted">Analiza y procesa archivos de logs del reloj checador ZKTeco</p>
                </div>
            </div>
            
            <!-- Estado del archivo -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-file-alt"></i> Estado del Archivo</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="fileStatus">
                        <div class="col-md-3">
                            <strong>Archivo:</strong> <span id="fileName">Verificando...</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Tamaño:</strong> <span id="fileSize">-</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Registros:</strong> <span id="fileLines">-</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Última modificación:</strong> <span id="fileModified">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel de acciones -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-tools"></i> Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <button type="button" class="btn btn-success w-100" onclick="analizarArchivo()">
                                <i class="fas fa-search"></i> Analizar Archivo
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <button type="button" class="btn btn-primary w-100" onclick="procesarArchivo()">
                                <i class="fas fa-play"></i> Procesar Archivo Completo
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <button type="button" class="btn btn-warning w-100" onclick="generarReporte()">
                                <i class="fas fa-chart-bar"></i> Generar Reporte Detallado
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Configuración -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-cog"></i> Configuración de Horarios</h5>
                </div>
                <div class="card-body">
                    <form id="configForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="entrada" class="form-label">Hora de Entrada</label>
                            <input type="time" class="form-control" id="entrada" value="09:00" required>
                        </div>
                        <div class="col-md-3">
                            <label for="salida" class="form-label">Hora de Salida</label>
                            <input type="time" class="form-control" id="salida" value="17:00" required>
                        </div>
                        <div class="col-md-3">
                            <label for="tolerancia" class="form-label">Tolerancia (min)</label>
                            <input type="number" class="form-control" id="tolerancia" value="15" min="0" max="60" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100" onclick="guardarConfiguracion()">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Subir archivo personalizado -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-upload"></i> Archivo Personalizado</h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-9">
                                <input type="file" class="form-control" id="customFile" name="customFile" 
                                       accept=".dat,.log" placeholder="Seleccione un archivo .dat o .log">
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-outline-success w-100" onclick="document.getElementById('customFile').click()">
                                    <i class="fas fa-folder-open"></i> Examinar
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Si no selecciona un archivo, se usará el archivo predeterminado: data/1_attlog.dat</small>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Consola de salida -->
            <div class="card">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-terminal"></i> Consola de Salida</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-light" onclick="limpiarConsola()">
                            <i class="fas fa-trash"></i> Limpiar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light" onclick="toggleConsola()">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="logContainer" class="log-container" style="height: 300px; display: none;">
                        <div class="text-muted p-3">La consola aparecerá aquí cuando ejecute una acción...</div>
                    </div>
                </div>
            </div>
            
            <!-- Barra de progreso -->
            <div class="progress-container" id="progressContainer">
                <div class="progress mb-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         id="progressBar" style="width: 0%">
                        0%
                    </div>
                </div>
                <div class="text-center" id="progressText">Iniciando...</div>
            </div>
            
            <!-- Resultados -->
            <div class="card" id="resultsCard" style="display: none;">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-check-circle"></i> Resultados</h5>
                </div>
                <div class="card-body" id="resultsBody">
                    <!-- Los resultados se cargarán aquí -->
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal de configuración guardada -->
    <div class="modal fade" id="configModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Configuración Guardada</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="configMessage"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/sistema_biometrico'; ?>';
        let consolaExpandida = false;
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            verificarEstadoArchivo();
            cargarConfiguracion();
        });
        
        // Verificar estado del archivo
        async function verificarEstadoArchivo() {
            try {
                const response = await fetch(`${BASE_URL}/zktecologs/getStatus`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('fileName').textContent = data.status.archivo_existe ? '1_attlog.dat' : 'No encontrado';
                    document.getElementById('fileSize').textContent = data.status.archivo_existe ? formatearBytes(data.status.tamano) : '0 bytes';
                    document.getElementById('fileLines').textContent = data.status.archivo_existe ? data.status.lineas.toLocaleString() : '0';
                    document.getElementById('fileModified').textContent = data.status.ultima_modificacion || 'Desconocido';
                    
                    if (data.status.archivo_existe) {
                        addLog('Archivo de logs detectado: ' + data.status.lineas + ' registros', 'info');
                    } else {
                        addLog('No se encontró el archivo de logs predeterminado', 'error');
                    }
                }
            } catch (error) {
                addLog('Error verificando estado del archivo: ' + error.message, 'error');
            }
        }
        
        // Analizar archivo
        async function analizarArchivo() {
            mostrarConsola();
            limpiarConsola();
            mostrarProgreso();
            
            try {
                const response = await fetch(`${BASE_URL}/zktecologs/analizar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addLog('Análisis completado exitosamente', 'success');
                    mostrarResultados(data.output, 'Análisis de Archivo');
                } else {
                    addLog('Error en análisis: ' + data.error, 'error');
                }
            } catch (error) {
                addLog('Error de red: ' + error.message, 'error');
            } finally {
                ocultarProgreso();
            }
        }
        
        // Procesar archivo completo
        async function procesarArchivo() {
            mostrarConsola();
            limpiarConsola();
            mostrarProgreso();
            
            try {
                const formData = new FormData(document.getElementById('uploadForm'));
                
                const response = await fetch(`${BASE_URL}/zktecologs/procesar`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addLog('Procesamiento completado', 'success');
                    addLog(`Registros insertados: ${data.resultado.insertados}`, 'success');
                    addLog(`Registros procesados: ${data.resultado.procesados}`, 'info');
                    
                    if (data.resultado.errores > 0) {
                        addLog(`Errores encontrados: ${data.resultado.errores}`, 'error');
                    }
                    
                    mostrarResultados(data.output, 'Procesamiento Completo', data.resultado);
                } else {
                    addLog('Error en procesamiento: ' + data.error, 'error');
                }
            } catch (error) {
                addLog('Error de red: ' + error.message, 'error');
            } finally {
                ocultarProgreso();
            }
        }
        
        // Generar reporte
        async function generarReporte() {
            mostrarConsola();
            limpiarConsola();
            mostrarProgreso();
            
            try {
                const response = await fetch(`${BASE_URL}/zktecologs/reporte`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addLog('Reporte generado exitosamente', 'success');
                    mostrarResultados(data.reporte, 'Reporte Detallado');
                } else {
                    addLog('Error generando reporte: ' + data.error, 'error');
                }
            } catch (error) {
                addLog('Error de red: ' + error.message, 'error');
            } finally {
                ocultarProgreso();
            }
        }
        
        // Guardar configuración
        async function guardarConfiguracion() {
            try {
                const formData = new URLSearchParams({
                    entrada: document.getElementById('entrada').value,
                    salida: document.getElementById('salida').value,
                    tolerancia: document.getElementById('tolerancia').value
                });
                
                const response = await fetch(`${BASE_URL}/zktecologs/procesar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('configMessage').innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check"></i> Configuración guardada exitosamente
                        </div>
                    `;
                    
                    const modal = new bootstrap.Modal(document.getElementById('configModal'));
                    modal.show();
                } else {
                    alert('Error guardando configuración: ' + data.error);
                }
            } catch (error) {
                alert('Error de red: ' + error.message);
            }
        }
        
        // Utilidades
        function addLog(mensaje, tipo = 'info') {
            const container = document.getElementById('logContainer');
            const timestamp = new Date().toLocaleTimeString();
            const className = tipo === 'error' ? 'text-danger' : 
                             tipo === 'success' ? 'text-success' : '';
            
            const logEntry = `<div class="${className}">[${timestamp}] ${mensaje}</div>`;
            container.insertAdjacentHTML('beforeend', logEntry);
            container.scrollTop = container.scrollHeight;
        }
        
        function limpiarConsola() {
            document.getElementById('logContainer').innerHTML = '';
        }
        
        function mostrarConsola() {
            document.getElementById('logContainer').style.display = 'block';
        }
        
        function ocultarConsola() {
            document.getElementById('logContainer').style.display = 'none';
        }
        
        function toggleConsola() {
            const container = document.getElementById('logContainer');
            const button = document.querySelector('[onclick="toggleConsola()"] i');
            
            consolaExpandida = !consolaExpandida;
            
            if (consolaExpandida) {
                container.style.height = '500px';
                button.className = 'fas fa-compress';
            } else {
                container.style.height = '300px';
                button.className = 'fas fa-expand';
            }
        }
        
        function mostrarProgreso() {
            document.getElementById('progressContainer').style.display = 'block';
            document.getElementById('resultsCard').style.display = 'none';
        }
        
        function ocultarProgreso() {
            document.getElementById('progressContainer').style.display = 'none';
        }
        
        function mostrarResultados(contenido, titulo, datos = null) {
            const card = document.getElementById('resultsCard');
            const body = document.getElementById('resultsBody');
            
            document.querySelector('#resultsCard h5').innerHTML = `<i class="fas fa-check-circle"></i> ${titulo}`;
            body.innerHTML = `<pre style="white-space: pre-wrap; font-family: monospace;">${contenido}</pre>`;
            
            if (datos) {
                const summary = `
                    <div class="row mt-3">
                        <div class="col-md-3"><strong>Total:</strong> ${datos.total?.toLocaleString() || 0}</div>
                        <div class="col-md-3"><strong>Procesados:</strong> ${datos.procesados?.toLocaleString() || 0}</div>
                        <div class="col-md-3"><strong>Insertados:</strong> <span class="text-success">${datos.insertados?.toLocaleString() || 0}</span></div>
                        <div class="col-md-3"><strong>Errores:</strong> <span class="text-danger">${datos.errores?.toLocaleString() || 0}</span></div>
                    </div>
                `;
                body.insertAdjacentHTML('afterbegin', summary);
            }
            
            card.style.display = 'block';
        }
        
        function formatearBytes(bytes) {
            if (bytes === 0) return '0 bytes';
            const k = 1024;
            const sizes = ['bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function cargarConfiguracion() {
            // Aquí se podría cargar configuración guardada de localStorage o una API
            const config = localStorage.getItem('zktecologs_config');
            if (config) {
                const cfg = JSON.parse(config);
                document.getElementById('entrada').value = cfg.entrada || '09:00';
                document.getElementById('salida').value = cfg.salida || '17:00';
                document.getElementById('tolerancia').value = cfg.tolerancia || '15';
            }
        }
    </script>
</body>
</html>