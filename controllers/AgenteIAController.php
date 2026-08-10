<?php
require_once __DIR__ . '/../services/AgenteIAService.php';
require_once __DIR__ . '/../services/GeneradorDocumentosService.php';
require_once __DIR__ . '/../services/ExportadorExcelService.php';
require_once __DIR__ . '/../models/ReglaNegocio.php';
require_once __DIR__ . '/BaseController.php';

class AgenteIAController extends BaseController {
    private $agenteService;
    private $documentoService;
    private $excelService;
    private $reglaModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->agenteService = new AgenteIAService();
        $this->documentoService = new GeneradorDocumentosService();
        $this->excelService = new ExportadorExcelService();
        $this->reglaModel = new ReglaNegocio();
    }

    public function index() {
        $this->requireAuth();
        
        $config = $this->agenteService->getConfig('*');
        $reglas = $this->reglaModel->getAll();
        
        $this->render('agente_ia/index', [
            'config' => $config,
            'reglas' => $reglas
        ]);
    }

    public function procesar() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;
            $empleado_id = $_POST['empleado_id'] ?? null;
            
            $resultado = $this->agenteService->procesarPeriodo($fecha_inicio, $fecha_fin, $area, $empleado_id);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $resultado
            ]);
        }
    }

    public function reporte() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $area = $_GET['area'] ?? null;
        
        $reporte = $this->agenteService->getReporteGeneral($fecha_inicio, $fecha_fin, $area);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $reporte
        ]);
    }

    public function generarDocumento() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipo = $_POST['tipo'] ?? 'oficio';
            $empleado_id = $_POST['empleado_id'] ?? null;
            $periodo = $_POST['periodo'] ?? date('Y-m');
            $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
            
            if (!$empleado_id) {
                $this->jsonResponse(['success' => false, 'error' => 'Empleado requerido'], 400);
                return;
            }
            
            $retardos = $this->agenteService->getRetardosNoJustificados($fecha_inicio, $fecha_fin);
            $retardos = array_filter($retardos, fn($r) => (int)$r['empleado_id'] === (int)$empleado_id);
            
            $resultado = $this->documentoService->generarOficioNotasMalas(
                $empleado_id,
                $periodo,
                array_values($retardos)
            );
            
            if ($resultado['success']) {
                $this->documentoService->guardarDocumento(
                    $empleado_id,
                    $tipo,
                    'Oficio de Notas Malas - ' . $periodo,
                    $resultado['contenido'],
                    $periodo
                );
            }
            
            $this->jsonResponse($resultado);
        }
    }

    public function exportarExcel() {
        $this->requireAuth();
        
        $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
        $area = $_POST['area'] ?? null;
        
        $filepath = $this->excelService->exportarReporteGeneral($fecha_inicio, $fecha_fin, $area);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . basename($filepath) . '"');
        header('Cache-Control: max-age=0');
        
        readfile($filepath);
        unlink($filepath);
        exit;
    }

    public function getIncidenciasNoValidadas() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $incidencias = $this->agenteService->getIncidenciasNoValidadas($fecha_inicio, $fecha_fin);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $incidencias,
            'total' => count($incidencias)
        ]);
    }

    public function reglas() {
        $this->requireAuth();
        
        $tipo = $_GET['tipo'] ?? null;
        $categoria = $_GET['categoria'] ?? null;
        
        $reglas = $this->reglaModel->getAll($tipo, $categoria);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $reglas
        ]);
    }

    public function guardarRegla() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? null,
                'tipo' => $_POST['tipo'],
                'categoria' => $_POST['categoria'],
                'condicion_json' => $_POST['condicion_json'],
                'accion_json' => $_POST['accion_json'],
                'prioridad' => $_POST['prioridad'] ?? 0,
                'activa' => $_POST['activa'] ?? 1
            ];
            
            if (isset($_POST['id']) && $_POST['id']) {
                $resultado = $this->reglaModel->update($_POST['id'], $data);
            } else {
                $resultado = $this->reglaModel->create($data);
            }
            
            $this->jsonResponse([
                'success' => $resultado,
                'message' => $resultado ? 'Regla guardada' : 'Error al guardar'
            ]);
        }
    }

    public function eliminarRegla() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
                return;
            }
            
            $resultado = $this->reglaModel->delete($id);
            
            $this->jsonResponse([
                'success' => $resultado,
                'message' => $resultado ? 'Regla eliminada' : 'Error al eliminar'
            ]);
        }
    }

    public function configuraciones() {
        $this->requireAuth();
        
        $clave = $_GET['clave'] ?? null;
        
        if ($clave === '*') {
            $this->jsonResponse([
                'success' => true,
                'data' => $this->agenteService->getConfig('*')
            ]);
        } elseif ($clave) {
            $this->jsonResponse([
                'success' => true,
                'data' => $this->agenteService->getConfig($clave)
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Clave requerida'], 400);
        }
    }

    public function guardarConfiguracion() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clave = $_POST['clave'] ?? null;
            $valor = $_POST['valor'] ?? null;
            
            if (!$clave || $valor === null) {
                $this->jsonResponse(['success' => false, 'error' => 'Clave y valor requeridos'], 400);
                return;
            }
            
            $this->agenteService->setConfig($clave, $valor);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Configuración guardada'
            ]);
        }
    }

    public function evaluarSanciones() {
        $this->requireAuth();
        
        $empleado_id = $_POST['empleado_id'] ?? null;
        $mes = $_POST['mes'] ?? (int)date('m');
        $anio = $_POST['anio'] ?? (int)date('Y');
        
        if (!$empleado_id) {
            $this->jsonResponse(['success' => false, 'error' => 'Empleado requerido'], 400);
            return;
        }
        
        $resultado = $this->agenteService->evaluarSanciones($empleado_id, $mes, $anio);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado
        ]);
    }

    public function dashboard() {
        $this->requireAuth();
        
        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-d');
        
        $reporte = $this->agenteService->getReporteGeneral($fecha_inicio, $fecha_fin);
        
        $this->render('agente_ia/dashboard', [
            'reporte' => $reporte,
            'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin]
        ]);
    }

    public function getJustificaciones() {
        $this->requireAuth();
        
        $empleado_id = $_GET['empleado_id'] ?? null;
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $justificaciones = $this->agenteService->getJustificacionesPorEmpleado(
            $empleado_id, 
            $fecha_inicio, 
            $fecha_fin
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $justificaciones,
            'total' => count($justificaciones)
        ]);
    }

    public function getEstadisticasJustificaciones() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $estadisticas = $this->agenteService->getEstadisticasPorTipoJustificacion(
            $fecha_inicio, 
            $fecha_fin
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $estadisticas
        ]);
    }

    public function getJustificacionesPorEmpleado() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        $area = $_GET['area'] ?? null;
        
        $resumen = $this->agenteService->getJustificacionesPorEmpleadoResumen(
            $fecha_inicio, 
            $fecha_fin,
            $area
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resumen
        ]);
    }

    public function analizarJustificaciones() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $patrones = $this->agenteService->analizarPatronesJustificacion(
            $fecha_inicio, 
            $fecha_fin
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $patrones
        ]);
    }

    public function prediccionJustificaciones() {
        $this->requireAuth();
        
        $empleado_id = $_GET['empleado_id'] ?? null;
        $meses = $_GET['meses'] ?? 6;
        
        $prediccion = $this->agenteService->analisisPredictivoJustificaciones(
            $empleado_id,
            $meses
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $prediccion
        ]);
    }

    public function getEstadisticasCompletas() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $estadisticas = $this->agenteService->getEstadisticasCompletasJustificaciones(
            $fecha_inicio, 
            $fecha_fin
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $estadisticas
        ]);
    }

    public function getJustificacionesPorTipo() {
        $this->requireAuth();
        
        $tipo_incidencia = $_GET['tipo'] ?? null;
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $justificaciones = $this->agenteService->getJustificacionesPorTipo(
            $tipo_incidencia,
            $fecha_inicio, 
            $fecha_fin
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $justificaciones,
            'total' => count($justificaciones)
        ]);
    }

    public function getResumenPorTipo() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $resumen = $this->agenteService->getResumenPorTipoJustificacion(
            $fecha_inicio, 
            $fecha_fin
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resumen
        ]);
    }

    public function analisisPredictivoCompleto() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-01-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $area = $_GET['area'] ?? null;
        
        $analisis = $this->agenteService->analisisPredictivoCompleto(
            $fecha_inicio, 
            $fecha_fin,
            $area
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $analisis
        ]);
    }

    public function getEmpleadosConMasJustificaciones() {
        $this->requireAuth();
        
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        $limite = $_GET['limite'] ?? 10;
        
        $empleados = $this->agenteService->getEmpleadosConMasJustificaciones(
            $fecha_inicio, 
            $fecha_fin,
            $limite
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $empleados
        ]);
    }
}
