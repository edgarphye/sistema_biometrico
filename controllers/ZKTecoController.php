<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ZKTecoLogProcessor.php';

/**
 * Controlador para procesamiento de logs ZKTeco
 */
class ZKTecoController extends BaseController {
    private $processor;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->processor = new ZKTecoLogProcessor();
    }
    
    /**
     * Vista principal de procesamiento de logs
     */
    public function index() {
        $this->render('zktecologs/index');
    }
    
    /**
     * Analiza el archivo de logs
     */
    public function analizar() {
        try {
            header('Content-Type: application/json');
            
            ob_start();
            $this->processor->generarReporte();
            $output = ob_get_clean();
            
            $this->jsonResponse([
                'success' => true,
                'output' => $output,
                'message' => 'Análisis completado exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'analizar']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Procesa el archivo completo y lo inserta en la base de datos
     */
    public function procesar() {
        try {
            header('Content-Type: application/json');
            
            $archivo = $this->subirArchivo();
            if ($archivo) {
                $this->processor = new ZKTecoLogProcessor($archivo);
            }
            
            // Configurar horarios si se enviaron
            $entrada = $_POST['entrada'] ?? '09:00:00';
            $salida = $_POST['salida'] ?? '17:00:00';
            $tolerancia = (int)($_POST['tolerancia'] ?? 15);
            
            $this->processor->configurarHorarios($entrada, $salida, $tolerancia);
            
            ob_start();
            $resultado = $this->processor->procesarArchivoCompleto();
            $output = ob_get_clean();
            
            $this->jsonResponse([
                'success' => true,
                'resultado' => $resultado,
                'output' => $output,
                'message' => 'Procesamiento completado exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'procesar']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    /**
     * Genera reporte detallado
     */
    public function reporte() {
        try {
            header('Content-Type: application/json');
            
            ob_start();
            $this->processor->generarReporte();
            $output = ob_get_clean();
            
            $this->jsonResponse([
                'success' => true,
                'reporte' => $output,
                'message' => 'Reporte generado exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'reporte']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sube un archivo de logs personalizado
     */
    private function subirArchivo() {
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== 0) {
            return null;
        }
        
        $uploadDir = __DIR__ . '/../uploads/zkt_logs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = 'custom_attlog_' . date('Y-m-d_H-i-s') . '.dat';
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $filepath)) {
            return $filepath;
        }
        
        return null;
    }
    
    /**
     * API para obtener estado del procesamiento
     */
    public function getStatus() {
        header('Content-Type: application/json');
        
        $defaultFile = __DIR__ . '/../data/1_attlog.dat';
        $status = [
            'archivo_existe' => file_exists($defaultFile),
            'tamano' => file_exists($defaultFile) ? filesize($defaultFile) : 0,
            'ultima_modificacion' => file_exists($defaultFile) ? date('Y-m-d H:i:s', filemtime($defaultFile)) : null,
            'lineas' => file_exists($defaultFile) ? count(file($defaultFile)) : 0
        ];
        
        $this->jsonResponse([
            'success' => true,
            'status' => $status
        ]);
    }
    
    /**
     * Vista para el procesamiento web
     */
    public function procesamientoWeb() {
        $this->render('zktecologs/procesar');
    }
}