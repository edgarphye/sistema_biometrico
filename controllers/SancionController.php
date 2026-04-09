<?php
require_once 'models/Sancion.php';
require_once 'models/Empleado.php';
require_once 'models/Usuario.php';
require_once 'helpers/RequestValidator.php';
require_once 'helpers/SecurityHelper.php';
require_once 'helpers/Csrf.php';
require_once __DIR__ . '/BaseController.php';

class SancionController extends BaseController {
    private $sancionModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
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
            try {
                // Validar CSRF
                Csrf::checkToken();
                
                $data = [
                    'empleado_id' => $_POST['empleado_id'] ?? '',
                    'tipo' => $_POST['tipo'] ?? '',
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                    'dias' => $_POST['dias'] ?? 0,
                    'motivo' => $_POST['motivo'] ?? '',
                    'creado_por' => $_SESSION['user_id'] ?? null
                ];

                // Validar datos
                $errors = $this->validateSancionData($data);
                if (!empty($errors)) {
                    $_SESSION['form_errors'] = $errors;
                    $_SESSION['form_data'] = $data;
                    $this->redirect(BASE_URL . '/sanciones/crear?error=validation');
                }

                if ($this->sancionModel->create($data)) {
                    $this->redirect(BASE_URL . '/sanciones?success=created');
                } else {
                    throw new Exception('Error al crear sanción');
                }
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'create']);
                $_SESSION['error'] = $e->getMessage();
                $this->redirect(BASE_URL . '/sanciones/crear?error=exception');
            }
        }

        // Necesitamos lista de empleados para el select
        $empleadoModel = new Empleado();
        $empleados = $empleadoModel->getAll();
        include 'views/sanciones/create.php';
    }
    
    /**
     * Validar datos de sanción
     */
    private function validateSancionData($data) {
        $errors = [];
        
        // Validar empleado_id
        if (!SecurityHelper::sanitizeInt($data['empleado_id'], 1)) {
            $errors['empleado_id'] = 'Debe seleccionar un empleado válido';
        }
        
        // Validar tipo
        $tipos_validos = ['suspension', 'amonestacion', 'nota_mala', 'descuento'];
        if (empty($data['tipo']) || !in_array($data['tipo'], $tipos_validos)) {
            $errors['tipo'] = 'Tipo de sanción inválido';
        }
        
        // Validar fecha
        if (empty($data['fecha_inicio'])) {
            $errors['fecha_inicio'] = 'La fecha de inicio es obligatoria';
        } else {
            $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_inicio']);
            if (!$fecha) {
                $errors['fecha_inicio'] = 'Formato de fecha inválido';
            }
        }
        
        // Validar días
        $dias = SecurityHelper::sanitizeInt($data['dias'], 0, 365);
        if ($dias === false) {
            $errors['dias'] = 'Número de días inválido (0-365)';
        }
        
        // Validar motivo
        if (empty(trim($data['motivo']))) {
            $errors['motivo'] = 'El motivo es obligatorio';
        } elseif (strlen(trim($data['motivo'])) < 10) {
            $errors['motivo'] = 'El motivo debe tener al menos 10 caracteres';
        }
        
        return $errors;
    }

    /**
     * Editar sanción (GET muestra el form, POST actualiza)
     */
    public function editar($id) {
        $validated_id = SecurityHelper::sanitizeInt($id, 1);
        if (!$validated_id) {
            $_SESSION['error'] = 'ID de sanción inválido';
            $this->redirect(BASE_URL . '/sanciones?error=invalid_id');
        }
        
        $sancion = $this->sancionModel->getById($validated_id);
        if (!$sancion) {
            $_SESSION['error'] = 'Sanción no encontrada';
            $this->redirect(BASE_URL . '/sanciones?error=not_found');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validar CSRF
                Csrf::checkToken();
                
                $data = [
                    'empleado_id' => $_POST['empleado_id'] ?? $sancion['empleado_id'],
                    'tipo' => $_POST['tipo'] ?? $sancion['tipo'],
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? $sancion['fecha_inicio'],
                    'dias' => $_POST['dias'] ?? $sancion['dias'],
                    'motivo' => $_POST['motivo'] ?? $sancion['motivo'],
                    'creado_por' => $_SESSION['user_id'] ?? $sancion['creado_por'],
                    'modified_by' => $_SESSION['user_id'] ?? null
                ];

                // Validar datos
                $errors = $this->validateSancionData($data);
                if (!empty($errors)) {
                    $_SESSION['form_errors'] = $errors;
                    $_SESSION['form_data'] = $data;
                    $this->redirect(BASE_URL . '/sanciones/editar/' . $validated_id . '?error=validation');
                }

                if ($this->sancionModel->update($validated_id, $data)) {
                    $this->redirect(BASE_URL . '/sanciones?success=updated');
                } else {
                    throw new Exception('Error al actualizar sanción');
                }
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'update', 'sancion_id' => $validated_id ?? null]);
                $_SESSION['error'] = $e->getMessage();
                $this->redirect(BASE_URL . '/sanciones/editar/' . $validated_id . '?error=exception');
            }
        }

        $empleadoModel = new Empleado();
        $empleados = $empleadoModel->getAll();

        include 'views/sanciones/edit.php';
    }

    /**
     * Eliminar sanción (POST)
     */
    public function eliminar($id) {
        try {
            // Validar ID
            $validated_id = SecurityHelper::sanitizeInt($id, 1);
            if (!$validated_id) {
                $_SESSION['error'] = 'ID de sanción inválido';
                $this->redirect(BASE_URL . '/sanciones?error=invalid_id');
            }
            
            // Verificar autenticación y rol de admin
            if (empty($_SESSION['user_id'])) {
                $this->redirect(BASE_URL . '/login');
            }

            $usuarioModel = new Usuario();
            $user = $usuarioModel->getById($_SESSION['user_id']);

            if (empty($user['rol']) || !in_array($user['rol'], ['admin', 'superadmin'])) {
                $_SESSION['error'] = 'No autorizado para eliminar sanciones';
                $this->redirect(BASE_URL . '/sanciones?error=unauthorized');
            }

            // Validar CSRF
            Csrf::checkToken();

            if ($this->sancionModel->delete($validated_id)) {
                $this->redirect(BASE_URL . '/sanciones?success=deleted');
            } else {
                throw new Exception('Error al eliminar sanción');
            }
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'delete', 'sancion_id' => $validated_id ?? null]);
            $_SESSION['error'] = $e->getMessage();
            $this->redirect(BASE_URL . '/sanciones?error=delete_failed');
        }
    }
}
?>
