<?php
require_once __DIR__ . '/../services/AIIntegracionEventos.php';
require_once __DIR__ . '/BaseController.php';

class AICronController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    private function verificarAccesoCron() {
        // Verificar que solo se ejecute localmente o con token de seguridad
        $token = $_GET['token'] ?? '';
        $tokenValido = getenv('AI_CRON_TOKEN') ?: 'ai_cron_' . date('Y');
        $esLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
        
        if ($token !== $tokenValido && !$esLocal && PHP_SAPI !== 'cli') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
    }
    
    public function ejecutarAnalisis() {
        header('Content-Type: application/json');
        $this->verificarAccesoCron();
        
        try {
            $ai = new AIIntegracionEventos();
            
            $resultado = $ai->ejecutarAnalisisProgramado();
            
            echo json_encode([
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'resultado' => $resultado
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function resumendia() {
        header('Content-Type: application/json');
        $this->verificarAccesoCron();
        
        try {
            $ai = new AIIntegracionEventos();
            
            $resumen = $ai->obtenerResumenIA();
            
            echo json_encode([
                'success' => true,
                'data' => $resumen
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
