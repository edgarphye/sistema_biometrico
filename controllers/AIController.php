<?php
require_once __DIR__ . '/../services/AICompletoService.php';
require_once __DIR__ . '/../services/AIActualizadorService.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/BaseController.php';

class AIController extends BaseController {
    private $aiCompleto;
    private $actualizador;
    
    public function __construct() {
        parent::__construct();
        $this->aiCompleto = new AICompletoService();
        $this->actualizador = new AIActualizadorService();
    }
    
    public function index() {
        $this->requireAuth();
        $dashboard = $this->actualizador->getDashboardIA();
        
        $this->render('ai/dashboard', [
            'dashboard' => $dashboard
        ]);
    }
    
    public function analizarEmpleado($id) {
        $this->requireAuth();
        $resultado = $this->aiCompleto->analizarCompleto($id);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }
    
    public function analisisGlobal() {
        $this->requireAuth();
        $resultado = $this->aiCompleto->analizarCompleto();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }
    
    public function reportes() {
        $this->requireAuth();
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;
        
        $reportes = $this->aiCompleto->getReportesCompletos($fechaInicio, $fechaFin);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $reportes
        ]);
    }
    
    public function metricasTiempoReal() {
        $this->requireAuth();
        $empleadoId = $_GET['empleado_id'] ?? null;
        
        if ($empleadoId) {
            $metricas = $this->actualizador->getMetricasTiempoReal($empleadoId);
        } else {
            $metricas = $this->actualizador->getDashboardIA();
        }
        
        $this->jsonResponse([
            'success' => true,
            'data' => $metricas
        ]);
    }
    
    public function alertas() {
        $this->requireAuth();
        $empleadoId = $_GET['empleado_id'] ?? null;
        
        $alertas = $this->actualizador->getAlertasNoLeidas($empleadoId);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $alertas
        ]);
    }
    
    public function marcarAlerta() {
        $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $alertaId = $data['id'] ?? null;
        
        if (!$alertaId) {
            $this->jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
            return;
        }
        
        $resultado = $this->actualizador->marcarAlertaLeida($alertaId);
        
        $this->jsonResponse($resultado);
    }
    
    public function actualizarDatos() {
        $this->requireAuth();
        $resultado = $this->actualizador->ejecutarAnalisisCompleto();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }
    
    public function historial($empleadoId) {
        $this->requireAuth();
        $dias = $_GET['dias'] ?? 90;
        
        $historial = $this->actualizador->getHistoricoEmpleado($empleadoId, $dias);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $historial
        ]);
    }
    
    public function analisisJustificaciones() {
        $this->requireAuth();
        
        $resultado = $this->aiCompleto->obtenerAnalisisJustificaciones();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }
    
    public function empleadosMejora() {
        $this->requireAuth();
        
        $resultado = $this->aiCompleto->obtenerEmpleadosConPotencialMejora();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }
    
    public function tiposJustificacion() {
        $this->requireAuth();
        
        $pdo = Database::getInstance()->getConnection();
        
        $stmt = $pdo->query("
            SELECT id, nombre, tipo_incidencia, requiere_aprobacion, requiere_documento, activo
            FROM tipos_justificacion 
            WHERE activo = 1 
            ORDER BY id
        ");
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $tipos
        ]);
    }
    
    public function redesNeuronales() {
        $this->requireAuth();
        
        $resultado = $this->aiCompleto->obtenerEstadisticasGlobalesRedes();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }
    
    public function obtenerNeuronasRed() {
        $this->requireAuth();
        
        $modelo = $_GET['modelo'] ?? 'all';
        $resultado = $this->aiCompleto->obtenerDatosNeuronasRed($modelo);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }
    
    public function obtenerDetalleNeurona($id) {
        $this->requireAuth();
        
        $resultado = $this->aiCompleto->obtenerDetalleCompletoNeurona($id);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }
}
