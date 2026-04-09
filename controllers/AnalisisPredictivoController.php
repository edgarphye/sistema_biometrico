<?php
require_once __DIR__ . '/../services/AnalisisPredictivoService.php';
require_once __DIR__ . '/BaseController.php';

class AnalisisPredictivoController extends BaseController {
    private $analisisService;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->analisisService = new AnalisisPredictivoService();
    }

    public function index() {
        $this->requireAuth();
        
        $dashboard = $this->analisisService->getDashboardPredictivo();
        
        $this->render('agente_ia/predictivo', [
            'dashboard' => $dashboard
        ]);
    }

    public function dashboard() {
        $this->requireAuth();
        
        $dashboard = $this->analisisService->getDashboardPredictivo();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $dashboard
        ]);
    }

    public function predecirRiesgos() {
        $this->requireAuth();
        
        $empleado_id = $_GET['empleado_id'] ?? null;
        $dias = $_GET['dias'] ?? null;
        
        $resultado = $this->analisisService->predecirRiesgosLaborales($empleado_id, $dias);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function analizarEmpleado() {
        $this->requireAuth();
        
        $empleado_id = $_GET['empleado_id'] ?? null;
        
        if (!$empleado_id) {
            $this->jsonResponse(['success' => false, 'error' => 'ID de empleado requerido'], 400);
            return;
        }
        
        $resultado = $this->analisisService->calcularIndiceRiesgoCompleto($empleado_id);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function analizarPatrones() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-90 days'));
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $area = $_GET['area'] ?? null;
        
        $resultado = $this->analisisService->analizarPatronesIncumplimiento($fecha_inicio, $fecha_fin, $area);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function generarAlertas() {
        $this->requireAuth();
        
        $umbral = isset($_GET['umbral']) ? (float)$_GET['umbral'] : null;
        $umbral_critico = isset($_GET['umbral_critico']) ? (float)$_GET['umbral_critico'] : null;
        
        $resultado = $this->analisisService->generarAlertasTempranas($umbral, $umbral_critico);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function getAlertas() {
        $this->requireAuth();
        
        $limite = $_GET['limite'] ?? 50;
        $empleado_id = $_GET['empleado_id'] ?? null;
        $tipo = $_GET['tipo'] ?? null;
        
        $resultado = $this->analisisService->getAlertasHistoricas($limite, $empleado_id, $tipo);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function marcarAlerta() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        
        // Validar CSRF token
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        if ($sessionToken && $csrfToken !== $sessionToken) {
            $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido'], 403);
            return;
        }
        
        $alerta_id = $_POST['alerta_id'] ?? null;
        
        if (!$alerta_id) {
            $this->jsonResponse(['success' => false, 'error' => 'ID de alerta requerido'], 400);
            return;
        }
        
        $resultado = $this->analisisService->marcarAlertaLeida($alerta_id);
        
        $this->jsonResponse([
            'success' => $resultado,
            'message' => $resultado ? 'Alerta marcada como leída' : 'Error al actualizar'
        ]);
    }

    public function generarRecomendaciones() {
        $this->requireAuth();
        
        $periodo = $_GET['periodo'] ?? 30;
        
        $resultado = $this->analisisService->generarRecomendacionesInteligentes($periodo);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function analizarArea() {
        $this->requireAuth();
        
        $area = $_GET['area'] ?? null;
        
        if (!$area) {
            $this->jsonResponse(['success' => false, 'error' => 'Área requerida'], 400);
            return;
        }
        
        $resultado = $this->analisisService->analizarArea($area);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function historialPronostico() {
        $this->requireAuth();
        
        $empleado_id = $_GET['empleado_id'] ?? null;
        $limite = $_GET['limite'] ?? 12;
        
        if (!$empleado_id) {
            $this->jsonResponse(['success' => false, 'error' => 'ID de empleado requerido'], 400);
            return;
        }
        
        $resultado = $this->analisisService->getHistorialPronosticos($empleado_id, $limite);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function compararPeriodos() {
        $this->requireAuth();
        
        $fecha_inicio1 = $_GET['fecha_inicio1'] ?? date('Y-m-01', strtotime('-3 months'));
        $fecha_fin1 = $_GET['fecha_fin1'] ?? date('Y-m-t', strtotime('-2 months'));
        $fecha_inicio2 = $_GET['fecha_inicio2'] ?? date('Y-m-01');
        $fecha_fin2 = $_GET['fecha_fin2'] ?? date('Y-m-d');
        
        $resultado = $this->analisisService->compararPeriodos(
            $fecha_inicio1, $fecha_fin1,
            $fecha_inicio2, $fecha_fin2
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function configuraciones() {
        $this->requireAuth();
        
        $clave = $_GET['clave'] ?? null;
        
        if ($clave === '*') {
            $this->jsonResponse([
                'success' => true,
                'data' => $this->analisisService->getConfig()
            ]);
        } elseif ($clave) {
            $this->jsonResponse([
                'success' => true,
                'data' => $this->analisisService->getConfig($clave)
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Clave requerida'], 400);
        }
    }

    public function guardarConfiguracion() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        
        // Validar CSRF token
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        if ($sessionToken && $csrfToken !== $sessionToken) {
            $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido'], 403);
            return;
        }
        
        // Campos válidos para configuración
        $camposPermitidos = [
            'dias_analisis_historico',
            'pronostico_dias',
            'umbral_alerta_precoz',
            'umbral_alerta_critica',
            'modelos_activos',
            'peso_retardos',
            'peso_faltas',
            'peso_minutos'
        ];
        
        $guardados = 0;
        foreach ($_POST as $clave => $valor) {
            if (in_array($clave, $camposPermitidos)) {
                $this->analisisService->setConfig($clave, $valor);
                $guardados++;
            }
        }
        
        if ($guardados === 0) {
            $this->jsonResponse(['success' => false, 'error' => 'No se recibió ninguna configuración válida'], 400);
            return;
        }
        
        $this->jsonResponse([
            'success' => true,
            'message' => "Configuración guardada ({$guardados} ajustes)"
        ]);
    }
}