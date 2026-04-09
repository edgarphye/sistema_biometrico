<?php
require_once 'models/DispositivoBiometrico.php';
require_once 'models/Biometrico.php';
require_once __DIR__ . '/BaseController.php';

class DispositivoBiometricoController extends BaseController {
    private $dispositivoModel;
    private $biometricoModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->dispositivoModel = new DispositivoBiometrico();
        $this->biometricoModel = new Biometrico();
    }

    /**
     * Lista todos los dispositivos biométricos
     */
    public function index() {
        $dispositivos = $this->dispositivoModel->getAll();
        $sedes = $this->dispositivoModel->getAllSedes();
        $estadisticas = $this->dispositivoModel->countBySede();
        
        $content = '';
        require_once 'views/dispositivos/index.php';
        require_once 'views/layout.php';
    }

    /**
     * Muestra el formulario para crear un nuevo dispositivo
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }

        $content = '';
        require_once 'views/dispositivos/create.php';
        require_once 'views/layout.php';
    }

    /**
     * Guarda un nuevo dispositivo
     */
    public function store() {
        try {
            // Validar datos
            $errors = $this->validateDevice($_POST);
            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->redirect(BASE_URL . '/dispositivos/create');
            }

            // Verificar que el dispositivo_id no exista
            if ($this->dispositivoModel->dispositivoIdExists($_POST['dispositivo_id'])) {
                $_SESSION['error'] = 'El ID de dispositivo ya está en uso';
                $_SESSION['old_input'] = $_POST;
                $this->redirect(BASE_URL . '/dispositivos/create');
            }

            // Verificar que la IP no exista
            if ($this->dispositivoModel->ipExists($_POST['ip_address'])) {
                $_SESSION['error'] = 'La dirección IP ya está en uso';
                $_SESSION['old_input'] = $_POST;
                $this->redirect(BASE_URL . '/dispositivos/create');
            }

            // Preparar datos
            $data = [
                'dispositivo_id' => intval($_POST['dispositivo_id']),
                'nombre' => trim($_POST['nombre']),
                'sede' => trim($_POST['sede']),
                'ip_address' => trim($_POST['ip_address']),
                'puerto' => intval($_POST['puerto'] ?? 4370),
                'tipo_dispositivo' => trim($_POST['tipo_dispositivo'] ?? 'ZKTeco'),
                'modelo' => trim($_POST['modelo'] ?? null),
                'capacidades' => [
                    'huella' => isset($_POST['cap_huella']),
                    'cara' => isset($_POST['cap_cara'])
                ],
                'fecha_instalacion' => $_POST['fecha_instalacion'] ?? date('Y-m-d'),
                'notas' => trim($_POST['notas'] ?? '')
            ];

            if ($this->dispositivoModel->create($data)) {
                $_SESSION['success'] = 'Dispositivo biométrico creado exitosamente';
                $this->redirect(BASE_URL . '/dispositivos');
            } else {
                $_SESSION['error'] = 'Error al crear el dispositivo';
                $this->redirect(BASE_URL . '/dispositivos/create');
            }
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'create']);
            error_log('Error creando dispositivo: ' . $e->getMessage());
            $_SESSION['error'] = 'Error interno al crear el dispositivo';
            $this->redirect(BASE_URL . '/dispositivos/create');
        }
    }

    /**
     * Muestra el formulario para editar un dispositivo
     */
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_URL . '/dispositivos');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }

        $dispositivo = $this->dispositivoModel->getById($id);
        if (!$dispositivo) {
            $_SESSION['error'] = 'Dispositivo no encontrado';
            $this->redirect(BASE_URL . '/dispositivos');
        }

        $content = '';
        require_once 'views/dispositivos/edit.php';
        require_once 'views/layout.php';
    }

    /**
     * Actualiza un dispositivo
     */
    public function update($id) {
        try {
            $errors = $this->validateDevice($_POST, $id);
            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->redirect(BASE_URL . '/dispositivos/edit?id=' . $id);
            }

            // Verificar IP duplicada (excluyendo el actual)
            if ($this->dispositivoModel->ipExists($_POST['ip_address'], $id)) {
                $_SESSION['error'] = 'La dirección IP ya está en uso por otro dispositivo';
                $this->redirect(BASE_URL . '/dispositivos/edit?id=' . $id);
            }

            $data = [
                'nombre' => trim($_POST['nombre']),
                'sede' => trim($_POST['sede']),
                'ip_address' => trim($_POST['ip_address']),
                'puerto' => intval($_POST['puerto'] ?? 4370),
                'tipo_dispositivo' => trim($_POST['tipo_dispositivo'] ?? 'ZKTeco'),
                'modelo' => trim($_POST['modelo'] ?? null),
                'capacidades' => [
                    'huella' => isset($_POST['cap_huella']),
                    'cara' => isset($_POST['cap_cara'])
                ],
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'fecha_instalacion' => $_POST['fecha_instalacion'] ?? null,
                'notas' => trim($_POST['notas'] ?? '')
            ];

            if ($this->dispositivoModel->update($id, $data)) {
                $_SESSION['success'] = 'Dispositivo actualizado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al actualizar el dispositivo';
            }

            $this->redirect(BASE_URL . '/dispositivos');
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'update', 'dispositivo_id' => $id ?? null]);
            error_log('Error actualizando dispositivo: ' . $e->getMessage());
            $_SESSION['error'] = 'Error interno al actualizar el dispositivo';
            $this->redirect(BASE_URL . '/dispositivos/edit?id=' . $id);
        }
    }

    /**
     * Desactiva un dispositivo
     */
    public function delete() {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID no proporcionado']);
        }

        if ($this->dispositivoModel->delete($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Dispositivo desactivado']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Error al desactivar']);
        }
    }

    /**
     * Cambia el estado de activación de un dispositivo
     */
    public function toggleStatus() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_URL . '/dispositivos');
        }

        $dispositivo = $this->dispositivoModel->getById($id);

        if ($dispositivo) {
            $newStatus = !$dispositivo['activo'];
            if ($this->dispositivoModel->updateStatus($id, $newStatus)) {
                $_SESSION['success'] = 'Estado del dispositivo actualizado correctamente.';
            } else {
                $_SESSION['error'] = 'No se pudo actualizar el estado del dispositivo.';
            }
        } else {
            $_SESSION['error'] = 'Dispositivo no encontrado.';
        }

        $this->redirect(BASE_URL . '/dispositivos');
    }

    /**
     * Prueba la conexión con un dispositivo
     */
    public function testConnection() {
        $id = $_POST['dispositivo_id'] ?? null;
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID no proporcionado']);
        }

        try {
            $dispositivo = $this->dispositivoModel->getByDispositivoId($id);
            if (!$dispositivo) {
                $this->jsonResponse(['success' => false, 'message' => 'Dispositivo no encontrado']);
            }

            // Intentar conectar
            $result = $this->biometricoModel->conectarDispositivo($id);
            
            if ($result && isset($result['status']) && $result['status'] === 'conectado') {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Conexión exitosa',
                    'data' => $result
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => 'No se pudo conectar al dispositivo'
                ]);
            }
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'testConnection', 'dispositivo_id' => $id ?? null]);
            error_log('Error probando conexión: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error al conectar: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sincroniza empleados con un dispositivo
     */
    public function syncEmployees() {
        $id = $_POST['dispositivo_id'] ?? null;
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID no proporcionado']);
        }

        try {
            // Obtener empleados activos
            $empleados = $this->biometricoModel->getEmpleadosActivos();
            
            // Sincronizar
            $result = $this->biometricoModel->syncEmployees($id, $empleados);
            
            if ($result) {
                // Actualizar última sincronización
                $this->dispositivoModel->updateLastSync($id);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Sincronización completada',
                    'count' => count($empleados)
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error en la sincronización'
                ]);
            }
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'syncEmployees', 'dispositivo_id' => $id ?? null]);
            error_log('Error sincronizando empleados: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene el estado de todos los dispositivos (AJAX)
     */
    public function getStatus() {
        try {
            $dispositivos = $this->dispositivoModel->getActivos();
            $status = [];

            foreach ($dispositivos as $disp) {
                try {
                    $info = $this->biometricoModel->conectarDispositivo($disp['dispositivo_id']);
                    $status[] = [
                        'dispositivo_id' => $disp['dispositivo_id'],
                        'nombre' => $disp['nombre'],
                        'sede' => $disp['sede'],
                        'status' => $info['status'] ?? 'desconocido',
                        'online' => ($info['status'] ?? '') === 'conectado'
                    ];
                } catch (Exception $e) {
                    $this->logException($e, ['action' => 'getStatus', 'dispositivo_id' => $disp['dispositivo_id'] ?? null]);
                    $status[] = [
                        'dispositivo_id' => $disp['dispositivo_id'],
                        'nombre' => $disp['nombre'],
                        'sede' => $disp['sede'],
                        'status' => 'error',
                        'online' => false
                    ];
                }
            }

            $this->jsonResponse(['success' => true, 'dispositivos' => $status]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'getStatusAll']);
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Valida los datos del dispositivo
     */
    private function validateDevice($data, $exclude_id = null) {
        $errors = [];

        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es requerido';
        }

        if (empty($data['sede'])) {
            $errors[] = 'La sede es requerida';
        }

        if (empty($data['ip_address'])) {
            $errors[] = 'La dirección IP es requerida';
        } elseif (!filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
            $errors[] = 'La dirección IP no es válida';
        }

        if (empty($data['puerto']) || !is_numeric($data['puerto'])) {
            $errors[] = 'El puerto debe ser un número';
        } elseif ($data['puerto'] < 1 || $data['puerto'] > 65535) {
            $errors[] = 'El puerto debe estar entre 1 y 65535';
        }

        if (!$exclude_id && empty($data['dispositivo_id'])) {
            $errors[] = 'El ID de dispositivo es requerido';
        }

        return $errors;
    }
}
?>
