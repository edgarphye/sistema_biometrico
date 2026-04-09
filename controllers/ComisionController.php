<?php
require_once 'models/Comision.php';
require_once 'models/Empleado.php';
require_once 'helpers/RequestValidator.php';
require_once 'helpers/SecurityHelper.php';
require_once __DIR__ . '/BaseController.php';

class ComisionController extends BaseController {
    private $comisionModel;
    private $empleadoModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->comisionModel = new Comision();
        $this->empleadoModel = new Empleado();
    }

    public function index() {
        $comisiones = $this->comisionModel->getComisionesPendientesAprobacion();
        $empleados = $this->empleadoModel->getAll();
        $tiposComision = $this->comisionModel->getTiposComisionAEFCM();
        $limites = $this->comisionModel->getLimitesAEFCM();

        include 'views/comisiones/index.php';
    }

public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'empleado_id' => $_POST['empleado_id'] ?? '',
                    'descripcion' => $_POST['descripcion'] ?? '',
                    'monto' => $_POST['monto'] ?? '',
                    'fecha_asignacion' => $_POST['fecha_asignacion'] ?? '',
                    'fecha_vencimiento' => $_POST['fecha_vencimiento'] ?? null,
                    'tipo_comision' => $_POST['tipo_comision'] ?? 'otros'
                ];
                
                // Validar datos de entrada
                $errors = RequestValidator::validateAsistenciaData($data);
                if (!empty($errors)) {
                    $_SESSION['form_errors'] = $errors;
                    $_SESSION['form_data'] = $data;
                    $this->redirect('/sistema_biometrico/comisiones/create?error=validation');
                }

                $this->comisionModel->create($data);
                $this->redirect('/sistema_biometrico/comisiones?success=1');
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'create']);
                $_SESSION['error'] = $e->getMessage();
                $this->redirect('/sistema_biometrico/comisiones/create?error=exception');
            }
        }

        $empleados = $this->empleadoModel->getAll();
        $tiposComision = $this->comisionModel->getTiposComisionAEFCM();
        include 'views/comisiones/create.php';
    }

public function aprobar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar IDs
            $id = SecurityHelper::sanitizeInt($_POST['comision_id'] ?? '', 1);
            $aprobado_por = SecurityHelper::sanitizeInt($_POST['aprobado_por'] ?? '', 1);
            
            if (!$id || !$aprobado_por) {
                $_SESSION['error'] = 'IDs inválidos';
                $this->redirect('/sistema_biometrico/comisiones?error=invalid_ids');
            }

            $this->comisionModel->aprobarComision($id, $aprobado_por);
            $this->redirect('/sistema_biometrico/comisiones?approved=1');
        }
    }

public function justificar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar y sanitizar datos
            $id = SecurityHelper::sanitizeInt($_POST['comision_id'] ?? '', 1);
            $aprobado_por = SecurityHelper::sanitizeInt($_POST['aprobado_por'] ?? '', 1);
            $motivo = SecurityHelper::escape(trim($_POST['motivo_aprobacion'] ?? ''));
            
            if (!$id || !$aprobado_por) {
                $_SESSION['error'] = 'IDs inválidos';
                $this->redirect('/sistema_biometrico/comisiones?error=invalid_ids');
            }
            
            if (empty($motivo)) {
                $_SESSION['error'] = 'El motivo de justificación es obligatorio';
                $this->redirect('/sistema_biometrico/comisiones?error=empty_motivo');
            }

            $this->comisionModel->justificarComision($id, $aprobado_por, $motivo);
            $this->redirect('/sistema_biometrico/comisiones?justified=1');
        }
    }

public function getByEmpleado() {
        $empleado_id = SecurityHelper::sanitizeInt($_GET['empleado_id'] ?? '', 1);
        if (!$empleado_id) {
            $this->jsonResponse(['error' => 'Empleado ID inválido']);
        }

        $comisiones = $this->comisionModel->getByEmpleado($empleado_id);
        $this->jsonResponse($comisiones);
    }

public function validarLimite() {
        header('Content-Type: application/json');
        
        // Validar y sanitizar datos
        $empleado_id = SecurityHelper::sanitizeInt($_POST['empleado_id'] ?? '', 1);
        $monto = SecurityHelper::sanitizeFloat($_POST['monto'] ?? '', 0);
        $mes = SecurityHelper::sanitizeInt($_POST['mes'] ?? '', 1, 12);
        $anio = SecurityHelper::sanitizeInt($_POST['anio'] ?? '', 2020, 2030);
        
        if (!$empleado_id || $monto === false || !$mes || !$anio) {
            $this->jsonResponse(['error' => 'Parámetros inválidos']);
        }

        $valido = $this->comisionModel->validarLimiteMensual($empleado_id, $monto, $mes, $anio);
        $this->jsonResponse(['valido' => $valido]);
    }
}
?>
