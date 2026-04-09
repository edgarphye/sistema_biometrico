<?php
require_once __DIR__ . '/../services/AIIntegracionEventos.php';
require_once __DIR__ . '/BaseController.php';

class AICronController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function ejecutarAnalisis() {
        header('Content-Type: application/json');
        
        // Verificar que solo se ejecute localmente o con token de seguridad
        $token = $_GET['token'] ?? '';
        $tokenValido = $token === 'ai_cron_2026' || in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
        
        if (!$tokenValido && PHP_SAPI !== 'cli') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../services/AIIntegracionEventos.php';
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
        
        try {
            require_once __DIR__ . '/../services/AIIntegracionEventos.php';
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
