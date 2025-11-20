<?php
require_once 'models/Horario.php';

class HorarioController {
    private $horarioModel;

    public function __construct() {
        $this->horarioModel = new Horario();
    }

    public function index() {
        $horarios = $this->horarioModel->getAll();
        include 'views/horarios/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'],
                'hora_entrada' => $_POST['hora_entrada'],
                'hora_salida' => $_POST['hora_salida'],
                'tolerancia_minutos' => $_POST['tolerancia_minutos'] ?? 9,
                'descripcion' => $_POST['descripcion'] ?? null
            ];

            if ($this->horarioModel->create($data)) {
                header('Location: /sistema_biometrico/horarios');
                exit;
            } else {
                $error = 'Error al crear el horario';
                include 'views/horarios/create.php';
            }
        } else {
            include 'views/horarios/create.php';
        }
    }

    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'],
                'hora_entrada' => $_POST['hora_entrada'],
                'hora_salida' => $_POST['hora_salida'],
                'tolerancia_minutos' => $_POST['tolerancia_minutos'] ?? 9,
                'descripcion' => $_POST['descripcion'] ?? null
            ];

            if ($this->horarioModel->update($id, $data)) {
                header('Location: /sistema_biometrico/horarios');
                exit;
            } else {
                $error = 'Error al actualizar el horario';
                $horario = $this->horarioModel->getById($id);
                include 'views/horarios/edit.php';
            }
        } else {
            $horario = $this->horarioModel->getById($id);
            if (!$horario) {
                header('HTTP/1.0 404 Not Found');
                echo 'Horario no encontrado';
                exit;
            }
            include 'views/horarios/edit.php';
        }
    }

    public function delete($id) {
        if ($this->horarioModel->deactivate($id)) {
            header('Location: /sistema_biometrico/horarios');
            exit;
        } else {
            $error = 'Error al eliminar el horario';
            $this->index();
        }
    }

    public function asignar() {
        require_once 'models/Empleado.php';
        $empleadoModel = new Empleado();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $empleado_id = $_POST['empleado_id'];
            $horario_id = $_POST['horario_id'];
            $dia_semana = $_POST['dia_semana'];

            try {
                $this->horarioModel->asignarHorarioEmpleado($empleado_id, $horario_id, $dia_semana);
                header('Location: /sistema_biometrico/horarios/asignar');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $horarios = $this->horarioModel->getAll();
        $empleados = $empleadoModel->getAll();
        include 'views/horarios/asignar.php';
    }

    public function verAsignaciones() {
        if (isset($_GET['empleado_id'])) {
            $asignaciones = $this->horarioModel->getHorariosEmpleado($_GET['empleado_id']);
            require_once 'models/Empleado.php';
            $empleadoModel = new Empleado();
            $empleado = $empleadoModel->getById($_GET['empleado_id']);
        } else {
            $asignaciones = [];
            $empleado = null;
        }

        require_once 'models/Empleado.php';
        $empleadoModel = new Empleado();
        $empleados = $empleadoModel->getAll();
        include 'views/horarios/ver_asignaciones.php';
    }
}
?>
