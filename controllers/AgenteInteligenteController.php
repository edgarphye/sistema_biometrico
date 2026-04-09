<?php
require_once __DIR__ . '/../services/AgenteInteligenteService.php';
require_once __DIR__ . '/BaseController.php';

class AgenteInteligenteController extends BaseController {
    private $agenteService;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->agenteService = new AgenteInteligenteService();
    }

    public function index() {
        $this->requireAuth();
        
        $dashboard = $this->agenteService->getDashboardAgente();
        
        $this->render('agente_ia/agent_dashboard', [
            'dashboard' => $dashboard
        ]);
    }

    public function analizar() {
        $this->requireAuth();
        
        $empleado_id = $_GET['empleado_id'] ?? null;
        
        $resultado = $this->agenteService->analizarYDecidir($empleado_id);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function dashboard() {
        $this->requireAuth();
        
        $dashboard = $this->agenteService->getDashboardAgente();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $dashboard
        ]);
    }

    public function metricas() {
        $this->requireAuth();
        
        $metricas = $this->agenteService->obtenerMetricasIA();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $metricas
        ]);
    }
}
