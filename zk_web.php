<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/ZKTecoLogProcessor.php';

/**
 * Interfaz web para procesamiento de logs ZKTeco
 */
class ZKTecoWebInterface {
    private $processor;
    private $errores = [];
    private $mensajes = [];
    
    public function __construct() {
        $this->processor = new ZKTecoLogProcessor();
        
        // Procesar formulario si se envió
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarFormulario();
        }
    }
    
    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Procesador de Logs ZKTeco</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                body { background-color: #f4f7f6; min-height: 100vh; padding-bottom: 50px; }
                .container { max-width: 800px; margin-top: 50px; }
                .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); border-radius: 10px; }
                .card-header { border-top-left-radius: 10px !important; border-top-right-radius: 10px !important; }
                .status-success { color: #28a745; }
                .status-error { color: #dc3545; }
                .log-container { 
                    background: #f8f9fa; 
                    border: 1px solid #dee2e6; 
                    border-radius: 5px; 
                    padding: 15px; 
                    max-height: 400px; 
                    overflow-y: auto; 
                    font-family: monospace; 
                    font-size: 12px;
                    margin-top: 20px;
                }
                .progress-container { margin: 20px 0; display: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-cog"></i> Procesador de Logs ZKTeco</h4>
                    </div>
                    <div class="card-body">
                        <?php $this->mostrarMensajes(); ?>
                        
                        <form method="POST" id="procesarForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="accion" class="form-label">Acción</label>
                                    <select class="form-select" id="accion" name="accion" required>
                                        <option value="">Seleccione una acción</option>
                                        <option value="analizar">Analizar archivo</option>
                                        <option value="procesar">Procesar archivo</option>
                                        <option value="reporte">Generar reporte</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="archivo" class="form-label">Archivo .attlog</label>
                                    <input type="file" class="form-control" id="archivo" name="archivo" accept=".dat,.log" 
                                           placeholder="Dejar en blanco para usar el predeterminado">
                                    <div class="form-text">Archivo de logs de ZKTeco (opcional)</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="entrada" class="form-label">Hora de entrada</label>
                                    <input type="time" class="form-control" id="entrada" name="entrada" value="09:00">
                                </div>
                                <div class="col-md-4">
                                    <label for="salida" class="form-label">Hora de salida</label>
                                    <input type="time" class="form-control" id="salida" name="salida" value="17:00">
                                </div>
                                <div class="col-md-4">
                                    <label for="tolerancia" class="form-label">Tolerancia (min)</label>
                                    <input type="number" class="form-control" id="tolerancia" name="tolerancia" value="15" min="0" max="60">
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Ejecutar
                                </button>
                                <button type="button" class="btn btn-secondary ms-2" onclick="limpiarConsola()">
                                    <i class="fas fa-trash"></i> Limpiar Consola
                                </button>
                            </div>
                        </form>
                        
                        <div class="progress-container" id="progressContainer">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     id="progressBar" style="width: 0%">
                                    0%
                                </div>
                            </div>
                        </div>
                        
                        <div id="logContainer" class="log-container" style="display: none;">
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Información</h5>
                    </div>
                    <div class="card-body">
                        <h6>Estructura del archivo .attlog:</h6>
                        <code>[ID_Usuario]&nbsp;&nbsp;&nbsp;[Fecha]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Tipo]&nbsp;&nbsp;&nbsp;[Campo2]&nbsp;&nbsp;&nbsp;[Campo3]&nbsp;&nbsp;&nbsp;[Campo4]</code>
                        
                        <h6 class="mt-3">Tipos de eventos:</h6>
                        <ul class="list-unstyled">
                            <li><span class="badge bg-success">0</span> Entrada (check-in)</li>
                            <li><span class="badge bg-danger">1</span> Salida (check-out)</li>
                        </ul>
                        
                        <h6 class="mt-3">Lógica de detección:</h6>
                        <ol>
                            <li>Se lee el tipo de evento del dispositivo (0=entrada, 1=salida)</li>
                            <li>Si el tipo es ambiguo, se usa la hora:</li>
                            <ul>
                                <li>Antes de la hora de entrada = Entrada</li>
                                <li>Después de la hora de salida = Salida</li>
                                <li>Entre horas = Entrada (período laboral)</li>
                            </ul>
                            <li>Se evitan duplicados con el mismo día y tipo</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <script>
                function limpiarConsola() {
                    document.getElementById('logContainer').innerHTML = '';
                    document.getElementById('logContainer').style.display = 'none';
                    document.getElementById('progressContainer').style.display = 'none';
                }
                
                function mostrarConsola() {
                    document.getElementById('logContainer').style.display = 'block';
                    const container = document.getElementById('logContainer');
                    container.scrollTop = container.scrollHeight;
                }
                
                function actualizarProgreso(porcentaje) {
                    document.getElementById('progressContainer').style.display = 'block';
                    document.getElementById('progressBar').style.width = porcentaje + '%';
                    document.getElementById('progressBar').textContent = porcentaje + '%';
                }
                
                function agregarLog(mensaje, tipo = 'info') {
                    const container = document.getElementById('logContainer');
                    const timestamp = new Date().toLocaleTimeString();
                    const clase = tipo === 'error' ? 'text-danger' : 
                                 tipo === 'success' ? 'text-success' : '';
                    
                    container.innerHTML += `<div class="${clase}">[${timestamp}] ${mensaje}</div>`;
                    mostrarConsola();
                }
                
                // Simulación de progreso para demostración
                document.getElementById('procesarForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const accion = document.getElementById('accion').value;
                    if (!accion) {
                        alert('Seleccione una acción');
                        return;
                    }
                    
                    limpiarConsola();
                    agregarLog('Iniciando proceso: ' + accion, 'info');
                    
                    // En una implementación real, esto se haría con AJAX
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    private function procesarFormulario() {
        $accion = $_POST['accion'] ?? '';
        
        if (!$accion) {
            $this->addError('Debe seleccionar una acción');
            return;
        }
        
        try {
            switch ($accion) {
                case 'analizar':
                    $this->procesarAnalisis();
                    break;
                    
                case 'procesar':
                    $this->procesarProcesamiento();
                    break;
                    
                case 'reporte':
                    $this->procesarReporte();
                    break;
            }
        } catch (Exception $e) {
            $this->addError('Error durante el procesamiento: ' . $e->getMessage());
        }
    }
    
    private function procesarAnalisis() {
        $this->addMessage('Ejecutando análisis del archivo...');
        
        // Configurar horarios si se enviaron
        if (!empty($_POST['entrada'])) {
            $this->processor->configurarHorarios(
                $_POST['entrada'] . ':00',
                $_POST['salida'] . ':00',
                (int)$_POST['tolerancia']
            );
            $this->addMessage('Horarios configurados');
        }
        
        ob_start();
        $this->processor->generarReporte();
        $output = ob_get_clean();
        
        $this->addMessage($output);
    }
    
    private function procesarProcesamiento() {
        $this->addMessage('Iniciando procesamiento del archivo...');
        
        // Configurar horarios
        $this->processor->configurarHorarios(
            $_POST['entrada'] . ':00',
            $_POST['salida'] . ':00',
            (int)$_POST['tolerancia']
        );
        
        // Usar archivo personalizado si se subió
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filename = 'custom_attlog_' . date('Y-m-d_H-i-s') . '.dat';
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $filepath)) {
                $this->processor = new ZKTecoLogProcessor($filepath);
                $this->addMessage("Archivo personalizado cargado: {$filename}");
            }
        }
        
        ob_start();
        $resultado = $this->processor->procesarArchivoCompleto();
        $output = ob_get_clean();
        
        $this->addMessage($output);
        
        if ($resultado['insertados'] > 0) {
            $this->addSuccess("✓ {$resultado['insertados']} registros insertados exitosamente");
        }
    }
    
    private function procesarReporte() {
        $this->addMessage('Generando reporte detallado...');
        
        ob_start();
        $this->processor->generarReporte();
        $output = ob_get_clean();
        
        $this->addMessage($output);
    }
    
    private function addError($mensaje) {
        $this->errores[] = $mensaje;
    }
    
    private function addMessage($mensaje) {
        $this->mensajes[] = $mensaje;
    }
    
    private function addSuccess($mensaje) {
        $this->mensajes[] = $mensaje;
    }
    
    private function mostrarMensajes() {
        if (!empty($this->errores)) {
            foreach ($this->errores as $error) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
            }
        }
        
        if (!empty($this->mensajes)) {
            foreach ($this->mensajes as $mensaje) {
                echo '<div class="alert alert-info">' . htmlspecialchars($mensaje) . '</div>';
            }
        }
    }
}

// Ejecución principal
if (basename(__FILE__) === 'zk_web.php') {
    $interface = new ZKTecoWebInterface();
    $interface->render();
}