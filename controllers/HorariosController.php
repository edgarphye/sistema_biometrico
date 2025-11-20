<?php
require_once 'models/HorarioLaboral.php';
require_once 'models/EmpleadoHorarios.php';

class HorariosController {
    private $horarioModel;
    private $empleadoHorariosModel;

    public function __construct() {
        $this->horarioModel = new HorarioLaboral();
        $this->empleadoHorariosModel = new EmpleadoHorarios();
    }

    /**
     * Listar todos los horarios laborales
     */
    public function index() {
        $horarios = $this->horarioModel->getAll();
        include 'views/horarios/index.php';
    }

    /**
     * Mostrar formulario para crear horario
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'],
                'hora_entrada' => $_POST['hora_entrada'],
                'hora_salida' => $_POST['hora_salida'],
                'tolerancia_minutos' => (int)($_POST['tolerancia_minutos'] ?? 10),
                'descripcion' => $_POST['descripcion'] ?? null
            ];

            if ($this->horarioModel->create($data)) {
                header('Location: /sistema_biometrico/horarios');
                exit;
            } else {
                $error = 'Error al crear el horario';
            }
        }

        include 'views/horarios/create.php';
    }

    /**
     * Mostrar detalles de un horario
     */
    public function show($id) {
        $horario = $this->horarioModel->getById($id);
        if (!$horario) {
            header('Location: /sistema_biometrico/horarios');
            exit;
        }

        // Obtener empleados asignados a este horario
        $empleados = $this->getEmpleadosByHorario($id);

        include 'views/horarios/show.php';
    }

    /**
     * Editar horario
     */
    public function edit($id) {
        $horario = $this->horarioModel->getById($id);
        if (!$horario) {
            header('Location: /sistema_biometrico/horarios');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'],
                'hora_entrada' => $_POST['hora_entrada'],
                'hora_salida' => $_POST['hora_salida'],
                'tolerancia_minutos' => (int)($_POST['tolerancia_minutos'] ?? 10),
                'descripcion' => $_POST['descripcion'] ?? null
            ];

            if ($this->horarioModel->update($id, $data)) {
                header('Location: /sistema_biometrico/horarios');
                exit;
            } else {
                $error = 'Error al actualizar el horario';
            }
        }

        include 'views/horarios/edit.php';
    }

    /**
     * Eliminar horario
     */
    public function delete($id) {
        if ($this->horarioModel->delete($id)) {
            header('Location: /sistema_biometrico/horarios');
            exit;
        } else {
            header('Location: /sistema_biometrico/horarios?error=delete_failed');
            exit;
        }
    }

    /**
     * Asignar horario a empleado
     */
    public function asignar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $empleado_id = (int)$_POST['empleado_id'];
            $horario_id = (int)$_POST['horario_id'];
            $dia_semana = $_POST['dia_semana'];

            if ($this->empleadoHorariosModel->asignarHorario($empleado_id, $horario_id, $dia_semana)) {
                header('Location: /sistema_biometrico/empleados/' . $empleado_id);
                exit;
            } else {
                $error = 'Error al asignar horario';
            }
        }

        // Obtener datos para el formulario
        require_once 'models/Empleado.php';
        $empleadoModel = new Empleado();
        $empleados = $empleadoModel->getAll();
        $horarios = $this->horarioModel->getAll();

        include 'views/horarios/asignar.php';
    }

    /**
     * Remover horario de empleado
     */
    public function remover($empleado_id, $dia_semana) {
        if ($this->empleadoHorariosModel->removerHorario($empleado_id, $dia_semana)) {
            header('Location: /sistema_biometrico/empleados/' . $empleado_id);
            exit;
        } else {
            header('Location: /sistema_biometrico/empleados/' . $empleado_id . '?error=remove_failed');
            exit;
        }
    }

    /**
     * Obtener empleados asignados a un horario específico
     */
    private function getEmpleadosByHorario($horario_id) {
        // Esta función necesitaría una consulta específica
        // Por ahora retornamos array vacío
        return [];
    }
}
?>
