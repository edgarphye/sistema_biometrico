<?php
require_once 'models/HorarioLaboral.php';
require_once 'models/EmpleadoHorarios.php';
require_once 'models/DispositivoBiometrico.php';
require_once 'models/Ciclo.php';
require_once __DIR__ . '/BaseController.php';

class HorariosController extends BaseController {
    private $horarioModel;
    private $empleadoHorariosModel;
    private $dispositivoModel;
    private $cicloModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->horarioModel = new HorarioLaboral();
        $this->empleadoHorariosModel = new EmpleadoHorarios();
        $this->dispositivoModel = new DispositivoBiometrico();
        $this->cicloModel = new Ciclo();
    }

    /**
     * Listar todos los horarios laborales
     */
    public function index() {
        $horarios = $this->horarioModel->getAll();
        $sedes = $this->dispositivoModel->getAllSedes();
        include 'views/horarios/index.php';
    }

    /**
     * Mostrar formulario para crear horario
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sede = !empty($_POST['sede']) ? $_POST['sede'] : null;
            $data = [
                'nombre' => $_POST['nombre'],
                'hora_entrada' => $_POST['hora_entrada'],
                'hora_salida' => $_POST['hora_salida'],
                'tolerancia_minutos' => (int)($_POST['tolerancia_minutos'] ?? 10),
                'descripcion' => $_POST['descripcion'] ?? null,
                'sede' => $sede
            ];

            if ($this->horarioModel->create($data)) {
                $this->redirect('/sistema_biometrico/horarios');
            } else {
                $error = 'Error al crear el horario';
            }
        }

        $sedes = $this->dispositivoModel->getAllSedes();
        include 'views/horarios/create.php';
    }

    /**
     * Mostrar detalles de un horario
     */
    public function show($id) {
        $horario = $this->horarioModel->getById($id);
        if (!$horario) {
            $this->redirect('/sistema_biometrico/horarios');
        }
        $this->render('horarios/show', ['horario' => $horario]);
    }

    /**
     * Editar horario
     */
    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sede = !empty($_POST['sede']) ? $_POST['sede'] : null;
            $data = [
                'nombre' => $_POST['nombre'],
                'hora_entrada' => $_POST['hora_entrada'],
                'hora_salida' => $_POST['hora_salida'],
                'tolerancia_minutos' => (int)($_POST['tolerancia_minutos'] ?? 10),
                'descripcion' => $_POST['descripcion'] ?? null,
                'sede' => $sede
            ];

            if ($this->horarioModel->update($id, $data)) {
                $this->redirect('/sistema_biometrico/horarios');
            } else {
                $error = 'Error al actualizar el horario';
            }
        }
        
        $horario = $this->horarioModel->getById($id);
        if (!$horario) {
            $this->redirect('/sistema_biometrico/horarios');
        }
        $sedes = $this->dispositivoModel->getAllSedes();
        include 'views/horarios/edit.php';
    }

    /**
     * Eliminar horario
     */
    public function delete($id) {
        if ($this->horarioModel->delete($id)) {
            $this->redirect('/sistema_biometrico/horarios?deleted=true');
        } else {
            $this->redirect('/sistema_biometrico/horarios?error=delete_failed');
        }
    }
}
?>
