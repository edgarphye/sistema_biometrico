<?php
require_once 'models/Sancion.php';

class SancionController {
    private $sancionModel;

    public function __construct() {
        $this->sancionModel = new Sancion();
    }

    /**
     * Listar sanciones (página principal para RRHH)
     */
    public function index() {
        $sanciones = $this->sancionModel->getAll();
        include 'views/sanciones/index.php';
    }

    /**
     * Mostrar detalle de una sanción
     */
    public function show($id) {
        $sancion = $this->sancionModel->getById($id);
        if (!$sancion) {
            http_response_code(404);
            echo 'Sanción no encontrada';
            return;
        }
        include 'views/sanciones/show.php';
    }

    /**
     * Mostrar formulario para crear sanción o procesar POST
     */
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../helpers/Csrf.php';
            $csrf = $_POST['csrf_token'] ?? '';
            if (!\Csrf::validate($csrf)) {
                $error = 'Token CSRF inválido.';
            } else {
                // continue
            }

            if (isset($error)) {
                // fall through to show form with error
            } else {
            $data = [
                'empleado_id' => $_POST['empleado_id'] ?? null,
                'tipo' => $_POST['tipo'] ?? 'suspension',
                'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                'dias' => intval($_POST['dias'] ?? 0),
                'motivo' => $_POST['motivo'] ?? null,
                'creado_por' => $_SESSION['user_id'] ?? null
            ];

            if ($this->sancionModel->create($data)) {
                header('Location: ' . BASE_URL . '/sanciones');
                exit;
            } else {
                $error = 'Error al crear sanción';
            }
            }
        }

        // Necesitamos lista de empleados para el select
        require_once 'models/Empleado.php';
        $empleadoModel = new Empleado();
        $empleados = $empleadoModel->getAll();
        include 'views/sanciones/create.php';
    }

    /**
     * Editar sanción (GET muestra el form, POST actualiza)
     */
    public function editar($id) {
        $sancion = $this->sancionModel->getById($id);
        if (!$sancion) {
            header('Location: ' . BASE_URL . '/sanciones');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../helpers/Csrf.php';
            $csrf = $_POST['csrf_token'] ?? '';
            if (!\Csrf::validate($csrf)) {
                $error = 'Token CSRF inválido.';
            }

            if (isset($error)) {
                // show form with error
            } else {
            $data = [
                'empleado_id' => $_POST['empleado_id'] ?? $sancion['empleado_id'],
                'tipo' => $_POST['tipo'] ?? $sancion['tipo'],
                'fecha_inicio' => $_POST['fecha_inicio'] ?? $sancion['fecha_inicio'],
                'dias' => intval($_POST['dias'] ?? $sancion['dias']),
                'motivo' => $_POST['motivo'] ?? $sancion['motivo'],
                'creado_por' => $_SESSION['user_id'] ?? $sancion['creado_por']
            ];

            if ($this->sancionModel->update($id, $data)) {
                header('Location: ' . BASE_URL . '/sanciones');
                exit;
            } else {
                $error = 'Error al actualizar sanción';
            }
            }
        }

        require_once 'models/Empleado.php';
        $empleadoModel = new Empleado();
        $empleados = $empleadoModel->getAll();

        include 'views/sanciones/edit.php';
    }

    /**
     * Eliminar sanción (POST)
     */
    public function eliminar($id) {
        // Solo admin puede eliminar
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        require_once 'models/Usuario.php';
        $usuarioModel = new Usuario();
        $user = $usuarioModel->getById($_SESSION['user_id']);

        if (empty($user['rol']) || $user['rol'] !== 'admin') {
            http_response_code(403);
            echo 'No autorizado';
            return;
        }

        // CSRF for delete via POST (token in form)
        require_once __DIR__ . '/../helpers/Csrf.php';
        $csrf = $_POST['csrf_token'] ?? '';
        if (!\Csrf::validate($csrf)) {
            http_response_code(400);
            echo 'Token CSRF inválido.';
            return;
        }

        if ($this->sancionModel->delete($id)) {
            header('Location: ' . BASE_URL . '/sanciones');
            exit;
        } else {
            echo 'Error al eliminar sanción';
            return;
        }
    }
}
?>
