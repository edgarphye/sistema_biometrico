<?php
require_once 'models/Comision.php';
require_once 'models/Empleado.php';

class ComisionController {
    private $comisionModel;
    private $empleadoModel;

    public function __construct() {
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
                    'empleado_id' => $_POST['empleado_id'],
                    'descripcion' => $_POST['descripcion'],
                    'monto' => $_POST['monto'],
                    'fecha_asignacion' => $_POST['fecha_asignacion'],
                    'fecha_vencimiento' => $_POST['fecha_vencimiento'] ?? null,
                    'tipo_comision' => $_POST['tipo_comision']
                ];

                $this->comisionModel->create($data);
                header('Location: /sistema_biometrico/comisiones?success=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                $empleados = $this->empleadoModel->getAll();
                $tiposComision = $this->comisionModel->getTiposComisionAEFCM();
                include 'views/comisiones/create.php';
                return;
            }
        }

        $empleados = $this->empleadoModel->getAll();
        $tiposComision = $this->comisionModel->getTiposComisionAEFCM();
        include 'views/comisiones/create.php';
    }

    public function aprobar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['comision_id'];
            $aprobado_por = $_POST['aprobado_por'];

            $this->comisionModel->aprobarComision($id, $aprobado_por);
            header('Location: /sistema_biometrico/comisiones?approved=1');
            exit;
        }
    }

    public function justificar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['comision_id'];
            $aprobado_por = $_POST['aprobado_por'];
            $motivo = $_POST['motivo_aprobacion'];

            $this->comisionModel->justificarComision($id, $aprobado_por, $motivo);
            header('Location: /sistema_biometrico/comisiones?justified=1');
            exit;
        }
    }

    public function getByEmpleado() {
        $empleado_id = $_GET['empleado_id'] ?? null;
        if (!$empleado_id) {
            echo json_encode(['error' => 'Empleado ID requerido']);
            return;
        }

        $comisiones = $this->comisionModel->getByEmpleado($empleado_id);
        header('Content-Type: application/json');
        echo json_encode($comisiones);
    }

    public function validarLimite() {
        $empleado_id = $_POST['empleado_id'];
        $monto = $_POST['monto'];
        $mes = $_POST['mes'];
        $anio = $_POST['anio'];

        $valido = $this->comisionModel->validarLimiteMensual($empleado_id, $monto, $mes, $anio);

        header('Content-Type: application/json');
        echo json_encode(['valido' => $valido]);
    }
}
?>
