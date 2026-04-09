<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/RegistroJustificacionService.php';

class RegistroJustificacionController extends BaseController {
    private $servicio;
    
    public function __construct() {
        parent::__construct();
        $this->servicio = new RegistroJustificacionService();
    }
    
    public function index() {
        $this->requireAuth();
        
        $tipos = $this->servicio->getTodosLosTipos();
        
        $this->render('justificaciones/registrar', [
            'tipos' => $tipos
        ]);
    }
    
    public function getCampos() {
        $this->requireAuth();
        
        $tipo = $_GET['tipo'] ?? null;
        
        if (!$tipo) {
            $this->jsonResponse(['success' => false, 'error' => 'Tipo de justificación requerido'], 400);
            return;
        }
        
        $campos = $this->servicio->getCamposPorTipo($tipo);
        
        if (!$campos) {
            $this->jsonResponse(['success' => false, 'error' => 'Tipo de justificación no válido'], 404);
            return;
        }
        
        $this->jsonResponse([
            'success' => true,
            'data' => $campos
        ]);
    }
    
    public function registrar() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        
        $empleado_id = $_POST['empleado_id'] ?? null;
        $tipo_justificacion = $_POST['tipo_justificacion'] ?? null;
        
        if (!$empleado_id || !$tipo_justificacion) {
            $this->jsonResponse([
                'success' => false, 
                'error' => 'Empleado y tipo de justificación son requeridos'
            ], 400);
            return;
        }
        
        $datos = $_POST;
        
        $resultado = $this->servicio->registrarJustificacion($empleado_id, $tipo_justificacion, $datos);
        
        $this->jsonResponse($resultado);
    }
    
    public function getTipos() {
        $this->requireAuth();
        
        $tipos = $this->servicio->getTodosLosTipos();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $tipos
        ]);
    }
}
