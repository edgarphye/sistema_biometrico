<?php
require_once 'models/HorarioLaboral.php';
require_once 'models/Empleado.php';
require_once 'helpers/RequestValidator.php';
require_once 'helpers/SecurityHelper.php';
require_once 'models/Ciclo.php';
require_once 'models/EmpleadoHorarios.php';
require_once __DIR__ . '/BaseController.php';

class HorarioController extends BaseController {
    private $horarioModel;
    private $empleadoHorariosModel;
    private $cicloModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->horarioModel = new HorarioLaboral();
        $this->empleadoHorariosModel = new EmpleadoHorarios();
        $this->cicloModel = new Ciclo();
    }

    private function puedeGestionarCiclos() {
        $rol = strtolower((string)$this->getCurrentUserRole());
        return in_array($rol, ['admin', 'superadmin'], true);
    }

    private function bloquearGestionCiclos($esAjax = true) {
        if ($esAjax) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Acceso denegado. Solo admin y superadmin pueden modificar ciclos.'
            ], 403);
        }
        $_SESSION['error'] = 'Acceso denegado. Solo admin y superadmin pueden modificar ciclos.';
        $this->redirect(BASE_URL . '/empleados');
    }

    /**
     * Obtener todos los horarios activos
     */
    public function getAll() {
        return $this->horarioModel->getAll();
    }

    public function index() {
        $horarios = $this->horarioModel->getAll();
        include __DIR__ . '/../views/horarios/index.php';
    }

public function create() {
    require_once 'models/Usuario.php';
    if (!Usuario::tienePermiso('horarios_crear')) {
        $_SESSION['error'] = 'No tienes permiso para crear horarios.';
        $this->redirect(BASE_URL . '/horarios');
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'] ?? '',
                'hora_entrada' => $_POST['hora_entrada'] ?? '',
                'hora_salida' => $_POST['hora_salida'] ?? '',
                'tolerancia_minutos' => $_POST['tolerancia_minutos'] ?? 10,
                'descripcion' => $_POST['descripcion'] ?? null,
                'color' => $_POST['color'] ?? '#0d6efd'
            ];
            
            try {
                $this->horarioModel->create($data);
                $this->redirect(BASE_URL . '/horarios?success=1');
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'create']);
                $_SESSION['error'] = $e->getMessage();
                $_SESSION['form_data'] = $data;
                $this->redirect(BASE_URL . '/horarios?error=' . urlencode($e->getMessage()));
            }
        } else {
            include __DIR__ . '/../views/horarios/create.php';
        }
    }

    public function edit($id = null) {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('horarios_editar')) {
            $_SESSION['error'] = 'No tienes permiso para editar horarios.';
            $this->redirect(BASE_URL . '/horarios');
            return;
        }
        
        // Si no viene por URL, intentar obtenerlo de POST
        if ($id === null) {
            $id = $_POST['id'] ?? null;
        }

        // Validar ID
        $validated_id = SecurityHelper::sanitizeInt($id, 1);
        if (!$validated_id) {
            $error = 'ID de horario inválido';
            $this->redirect(BASE_URL . '/horarios?error=' . urlencode($error));
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'] ?? '',
                'hora_entrada' => $_POST['hora_entrada'] ?? '',
                'hora_salida' => $_POST['hora_salida'] ?? '',
                'tolerancia_minutos' => $_POST['tolerancia_minutos'] ?? 10,
                'descripcion' => $_POST['descripcion'] ?? null,
                'color' => $_POST['color'] ?? '#0d6efd'
            ];

            try {
                if ($this->horarioModel->update($validated_id, $data)) {
                    $this->redirect(BASE_URL . '/horarios?success=updated');
                } else {
                    throw new Exception('Error al actualizar el horario');
                }
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'update', 'horario_id' => $validated_id ?? null]);
                $_SESSION['error'] = $e->getMessage();
                $_SESSION['form_data'] = $data;
                $this->redirect(BASE_URL . '/horarios?error=' . urlencode($e->getMessage()));
            }
        } else {
            $horario = $this->horarioModel->getById($validated_id);
            if (!$horario) {
                $error = 'Horario no encontrado';
                $this->redirect(BASE_URL . '/horarios?error=' . urlencode($error));
            }
            include __DIR__ . '/../views/horarios/edit.php';
        }
    }

public function delete($id) {
    require_once 'models/Usuario.php';
    if (!Usuario::tienePermiso('horarios_eliminar')) {
        $_SESSION['error'] = 'No tienes permiso para eliminar horarios.';
        $this->redirect(BASE_URL . '/horarios');
        return;
    }
    
    $validated_id = SecurityHelper::sanitizeInt($id, 1);
        if (!$validated_id) {
            $error = 'ID de horario inválido';
            $this->redirect(BASE_URL . '/horarios?error=' . urlencode($error));
        }
        
        if ($this->horarioModel->deactivate($validated_id)) {
            $this->redirect(BASE_URL . '/horarios?success=deleted');
        } else {
            $error = 'Error al eliminar el horario';
            $this->redirect(BASE_URL . '/horarios?error=' . urlencode($error));
        }
    }

    public function asignar() {
        require_once 'models/Empleado.php';
        require_once 'models/EmpleadoHorarios.php';
        require_once 'models/Database.php';
        
        $empleadoModel = new Empleado();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $empleado_id = $_POST['empleado_id'];
            $horario_id = $_POST['horario_id'];
            $dia_semana = strtolower($_POST['dia_semana']);

            try {
                // Guardar en empleado_horarios (nueva tabla de historial)
                $db = new Database();
                $conn = $db->getConnection();
                
                // Cerrar vigencia anterior si existe
                $stmt = $conn->prepare("
                    UPDATE empleado_horarios 
                    SET fecha_fin = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
                    WHERE empleado_id = ? AND fecha_fin IS NULL
                ");
                $stmt->execute([$empleado_id]);
                
                // Insertar nueva asignación de horario fijo
                $stmt = $conn->prepare("
                    INSERT INTO empleado_horarios (empleado_id, horario_id, fecha_inicio)
                    VALUES (?, ?, CURDATE())
                ");
                $stmt->execute([$empleado_id, $horario_id]);
                
                if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
                    $this->jsonResponse(['success' => true, 'message' => 'Horario asignado correctamente']);
                    return;
                }
                $this->redirect('/sistema_biometrico/horarios/asignar');
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'asignar', 'empleado_id' => $empleado_id ?? null, 'horario_id' => $horario_id ?? null]);
                if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
                    $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
                }
                $error = $e->getMessage();
            }
        }

        $horarios = $this->horarioModel->getAll();
        $empleados = $empleadoModel->getAll();
        include 'views/horarios/asignar.php';
    }

    public function asignarCiclo() {
        if (!$this->puedeGestionarCiclos()) {
            $this->bloquearGestionCiclos(isset($_POST['ajax']) && (int)$_POST['ajax'] === 1);
        }

        require_once 'models/Empleado.php';
        require_once 'models/Ciclo.php';
        
        $empleadoModel = new Empleado();
        $cicloModel = new Ciclo();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $empleado_id = $_POST['empleado_id'];
            $ciclo_id = $_POST['ciclo_id'];
            $fecha_inicio = $_POST['fecha_inicio'];

            try {
                $this->empleadoHorariosModel->asignarCicloEmpleado($empleado_id, $ciclo_id, $fecha_inicio);
                if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
                    $this->jsonResponse(['success' => true, 'message' => 'Ciclo asignado correctamente']);
                    return;
                }
                $this->redirect('/sistema_biometrico/horarios/ver-asignaciones?success=ciclo_assigned');
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'asignarCiclo', 'empleado_id' => $empleado_id ?? null, 'ciclo_id' => $ciclo_id ?? null]);
                if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
                    $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
                }
                $error = $e->getMessage();
            }
        }

        $ciclos = $cicloModel->getAll();
        $empleados = $empleadoModel->getAll();
        include 'views/horarios/asignar_ciclo.php';
    }
    
    public function asignarCicloEmpleado() {
        if (!$this->puedeGestionarCiclos()) {
            $this->bloquearGestionCiclos(true);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $empleado_id = $_POST['empleado_id'] ?? null;
            $ciclo_id = $_POST['ciclo_id'] ?? null;
            $fecha_inicio = $_POST['fecha_inicio'] ?? null;
            $fecha_fin = $_POST['fecha_fin'] ?? null;
            
            // Convertir string vacío a null
            if ($fecha_fin === '') {
                $fecha_fin = null;
            }
            
            if (!$empleado_id || !$ciclo_id || !$fecha_inicio) {
                $this->jsonResponse(['success' => false, 'message' => 'Faltan datos requeridos'], 400);
                return;
            }
            
            try {
                if ($fecha_fin && $fecha_fin < $fecha_inicio) {
                    throw new Exception('La fecha de fin no puede ser anterior a la fecha de inicio.');
                }
                
                require_once 'models/Database.php';
                $db = Database::getInstance()->getConnection();
                
                // Obtener ciclo anterior antes de asignar el nuevo
                $cicloCerrado = null;
                $stmt = $db->prepare("
                    SELECT c.nombre, eh.fecha_fin 
                    FROM empleado_horarios eh
                    LEFT JOIN ciclos c ON eh.ciclo_id = c.id
                    WHERE eh.empleado_id = ? AND eh.ciclo_id IS NOT NULL AND eh.fecha_fin IS NULL 
                    ORDER BY eh.fecha_inicio DESC LIMIT 1
                ");
                $stmt->execute([$empleado_id]);
                $cicloAnterior = $stmt->fetch(PDO::FETCH_ASSOC);
                if($cicloAnterior) {
                    $cicloCerrado = date('d/m/Y', strtotime($fecha_inicio . ' -1 day'));
                    
                    // Actualizar fecha_fin del ciclo anterior (un día antes del nuevo inicio)
                    $stmtUpdate = $db->prepare("
                        UPDATE empleado_horarios 
                        SET fecha_fin = DATE(?) 
                        WHERE empleado_id = ? AND ciclo_id IS NOT NULL AND fecha_fin IS NULL
                        ORDER BY fecha_inicio DESC LIMIT 1
                    ");
                    $fechaFinAnterior = date('Y-m-d', strtotime($fecha_inicio . ' -1 day'));
                    $stmtUpdate->execute([$fechaFinAnterior, $empleado_id]);
                    
                    // También actualizar en empleados_ciclos
                    $stmtUpdate2 = $db->prepare("
                        UPDATE empleados_ciclos 
                        SET fecha_fin = DATE(?) 
                        WHERE empleado_id = ? AND activo = 1
                    ");
                    $stmtUpdate2->execute([$fechaFinAnterior, $empleado_id]);
                }
                
                // Guardar en empleado_horarios (historial de asignaciones)
                $this->empleadoHorariosModel->asignarCicloEmpleado($empleado_id, $ciclo_id, $fecha_inicio, $fecha_fin);
                
                // Guardar en empleados_ciclos (relación directa empleado-ciclo)
                // Primero desactivar ciclos anteriores del empleado
                $stmt = $db->prepare("UPDATE empleados_ciclos SET activo = 0 WHERE empleado_id = ?");
                $stmt->execute([$empleado_id]);
                
                // Determinar automáticamente el tipo de ciclo (Fijo vs Combinado)
                // basándose en los horarios definidos en bloques_ciclo
                $stmtTipo = $db->prepare("
                    SELECT COUNT(DISTINCT CONCAT(hora_inicio, hora_fin)) as rangos_distintos
                    FROM bloques_ciclo
                    WHERE ciclo_id = ? AND activo = 1
                ");
                $stmtTipo->execute([$ciclo_id]);
                $tipoData = $stmtTipo->fetch(PDO::FETCH_ASSOC);
                $tipoCiclo = ($tipoData && $tipoData['rangos_distintos'] > 1) ? 'Combinado' : 'Fijo';
                
                // Insertar nuevo ciclo asignado con el tipo determinado
                $stmt = $db->prepare("
                    INSERT INTO empleados_ciclos (empleado_id, ciclo_id, fecha_inicio, activo, tipo_ciclo)
                    VALUES (?, ?, ?, 1, ?)
                ");
                $stmt->execute([$empleado_id, $ciclo_id, $fecha_inicio, $tipoCiclo]);
                
                // Obtener información del ciclo para responder
                $stmt = $db->prepare("SELECT nombre, num_ciclo FROM ciclos WHERE id = ?");
                $stmt->execute([$ciclo_id]);
                $cicloInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Ciclo asignado correctamente',
                    'ciclo_cerrado' => $cicloCerrado,
                    'ciclo_asignado' => $cicloInfo['nombre'] ?? 'Ciclo #' . $ciclo_id,
                    'tipo_ciclo' => $tipoCiclo
                ]);
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'asignarCiclo', 'empleado_id' => $empleado_id ?? null, 'ciclo_id' => $ciclo_id ?? null]);
                $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
        }
    }

    public function editarAsignacion() {
        if (!$this->puedeGestionarCiclos()) {
            $this->bloquearGestionCiclos(true);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $tipo = $_POST['tipo'];
            $item_id = $_POST['item_id'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;

            try {
                if ($fecha_fin && $fecha_fin < $fecha_inicio) {
                    throw new Exception('La fecha de fin no puede ser anterior a la fecha de inicio.');
                }

                $this->empleadoHorariosModel->updateAsignacion($id, $tipo, $item_id, $fecha_inicio, $fecha_fin);
                if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
                    $this->jsonResponse(['success' => true, 'message' => 'Asignación actualizada correctamente']);
                    return;
                }
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'editarAsignacion', 'asignacion_id' => $id ?? null]);
                if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
                    $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
                }
            }
        }
    }

    public function eliminarAsignacion() {
        if (!$this->puedeGestionarCiclos()) {
            $this->bloquearGestionCiclos(true);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            try {
                $this->empleadoHorariosModel->deleteAsignacion($id);
                $this->jsonResponse(['success' => true, 'message' => 'Asignación eliminada correctamente']);
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'eliminarAsignacion', 'asignacion_id' => $id ?? null]);
                $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
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

    /**
     * Mantenimiento de Horarios - Vista integrada tabla + editor
     */
    public function mantenimiento() {
        $horarios = $this->horarioModel->getAll();
        $horarioSeleccionado = null;

        // Si se selecciona un horario para editar
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $horarioSeleccionado = $this->horarioModel->getById($_GET['edit']);
        }

        include 'views/horarios/mantenimiento.php';
    }

    /**
     * Procesar creación/edición desde mantenimiento
     */
    public function procesarMantenimiento() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/horarios/mantenimiento');
        }

        $action = $_POST['action'] ?? '';
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'hora_entrada' => $_POST['hora_entrada'] ?? '',
            'hora_salida' => $_POST['hora_salida'] ?? '',
            'tolerancia_minutos' => $_POST['tolerancia_minutos'] ?? 10,
            'descripcion' => $_POST['descripcion'] ?? null,
            'color' => $_POST['color'] ?? '#007bff',
            'inicio_marcaje_entrada' => $_POST['inicio_marcaje_entrada'] ?? null,
            'fin_marcaje_entrada' => $_POST['fin_marcaje_entrada'] ?? null,
            'inicio_marcaje_salida' => $_POST['inicio_marcaje_salida'] ?? null,
            'fin_marcaje_salida' => $_POST['fin_marcaje_salida'] ?? null,
            'debe_marcar_entrada' => isset($_POST['debe_marcar_entrada']) ? 1 : 0,
            'debe_marcar_salida' => isset($_POST['debe_marcar_salida']) ? 1 : 0,
            'cuenta_dia_trabajo' => $_POST['cuenta_dia_trabajo'] ?? 1,
            'cuenta_minutos' => $_POST['cuenta_minutos'] ?? 0
        ];

        try {
            if ($action === 'create') {
                if ($this->horarioModel->create($data)) {
                    $success = 'Horario creado exitosamente';
                } else {
                    $error = 'Error al crear el horario';
                }
            } elseif ($action === 'update' && isset($_POST['id'])) {
                if ($this->horarioModel->update($_POST['id'], $data)) {
                    $success = 'Horario actualizado exitosamente';
                } else {
                    $error = 'Error al actualizar el horario';
                }
            } elseif ($action === 'delete' && isset($_POST['id'])) {
                if ($this->horarioModel->delete($_POST['id'])) {
                    $success = 'Horario eliminado exitosamente';
                } else {
                    $error = 'Error al eliminar el horario';
                }
            }
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'mantenimiento']);
            $error = 'Error: ' . $e->getMessage();
        }

        // Redirigir con mensaje
        $redirect = BASE_URL . '/horarios/mantenimiento';
        if (isset($success)) {
            $redirect .= '?success=' . urlencode($success);
        } elseif (isset($error)) {
            $redirect .= '?error=' . urlencode($error);
        }

        $this->redirect($redirect);
    }

    /**
     * Gestión de Ciclos Semanales
     */
    public function ciclos() {
        // Obtener ciclos existentes (por ahora usamos horarios como ciclos)
        $ciclos = $this->horarioModel->getAll();
        $cicloSeleccionado = null;

        if (isset($_GET['ciclo']) && is_numeric($_GET['ciclo'])) {
            $cicloSeleccionado = $this->horarioModel->getById($_GET['ciclo']);
        }

        include 'views/horarios/ciclos.php';
    }

    /**
     * Procesar operaciones de ciclos
     */
    public function procesarCiclos() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/horarios/ciclos');
        }

        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create_ciclo') {
                $data = [
                    'nombre' => $_POST['nombre_ciclo'] ?? '',
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                    'num_ciclo' => $_POST['num_ciclo'] ?? 1,
                    'unid_ciclo' => $_POST['unid_ciclo'] ?? 'Semana',
                    'hora_entrada' => '09:00',
                    'hora_salida' => '17:00',
                    'tolerancia_minutos' => 10
                ];

                if ($this->horarioModel->create($data)) {
                    $success = 'Ciclo creado exitosamente';
                } else {
                    $error = 'Error al crear el ciclo';
                }
            } elseif ($action === 'update_ciclo' && isset($_POST['ciclo_id'])) {
                $data = [
                    'nombre' => $_POST['nombre_ciclo'] ?? '',
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                    'num_ciclo' => $_POST['num_ciclo'] ?? 1,
                    'unid_ciclo' => $_POST['unid_ciclo'] ?? 'Semana'
                ];
                if ($this->horarioModel->update($_POST['ciclo_id'], $data)) {
                    $success = 'Ciclo actualizado exitosamente';
                } else {
                    $error = 'Error al actualizar el ciclo';
                }
            } elseif ($action === 'update_horario' && isset($_POST['ciclo_id'])) {
                $horariosSemanales = $_POST['horarios'] ?? [];

                // Aquí se procesarían los bloques horarios del timeline
                // Por ahora, guardamos una configuración básica
                $data = [
                    'configuracion_semanal' => json_encode($horariosSemanales)
                ];

                if ($this->horarioModel->update($_POST['ciclo_id'], $data)) {
                    $success = 'Horario semanal actualizado exitosamente';
                } else {
                    $error = 'Error al actualizar el horario semanal';
                }
            } elseif ($action === 'delete_ciclo' && isset($_POST['ciclo_id'])) {
                if ($this->horarioModel->delete($_POST['ciclo_id'])) {
                    $success = 'Ciclo eliminado exitosamente';
                } else {
                    $error = 'Error al eliminar el ciclo';
                }
            }
            } catch (Exception $e) {
            $this->logException($e, ['action' => 'gestionarCiclos']);
            $error = 'Error: ' . $e->getMessage();
        }

        $redirect = BASE_URL . '/horarios/ciclos';
        if (isset($success)) {
            $redirect .= '?success=' . urlencode($success);
        } elseif (isset($error)) {
            $redirect .= '?error=' . urlencode($error);
        }

        $this->redirect($redirect);
    }

    /**
     * Muestra el historial de horarios y asistencia de un empleado por mes
     */
    public function historialEmpleado($id) {
        $id = (int)$id;
        $mes = $_GET['mes'] ?? date('m');
        $anio = $_GET['anio'] ?? date('Y');

        require_once __DIR__ . '/../models/Empleado.php';
        require_once __DIR__ . '/../models/Database.php';
        
        $empleadoModel = new Empleado();
        $empleado = $empleadoModel->getById($id);

        if (!$empleado) {
            $this->redirect(BASE_URL . '/empleados');
            return;
        }

        $db = (new Database())->getConnection();

        // 1. Obtener Asistencias del mes
        $stmt = $db->prepare("
            SELECT * FROM asistencia 
            WHERE empleado_id = ? 
            AND MONTH(fecha_hora) = ? 
            AND YEAR(fecha_hora) = ? 
            ORDER BY fecha_hora ASC
        ");
        $stmt->execute([$id, $mes, $anio]);
        $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Obtener Asignaciones de Horario/Ciclo vigentes en ese mes
        // Buscamos asignaciones que se solapen con el mes seleccionado
        $fechaInicioMes = "$anio-$mes-01";
        $fechaFinMes = date("Y-m-t", strtotime($fechaInicioMes));
        
        $stmt = $db->prepare("
            SELECT eh.*, c.nombre as nombre_ciclo, h.nombre as nombre_horario 
            FROM empleado_horarios eh
            LEFT JOIN ciclos c ON eh.ciclo_id = c.id
            LEFT JOIN horarios_laborales h ON eh.horario_id = h.id
            WHERE eh.empleado_id = ? 
            AND (eh.fecha_fin IS NULL OR eh.fecha_fin >= ?) 
            AND eh.fecha_inicio <= ?
            ORDER BY eh.fecha_inicio DESC
        ");
        $stmt->execute([$id, $fechaInicioMes, $fechaFinMes]);
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../views/horarios/historial_empleado.php';
    }

    /**
     * Endpoint JSON: Obtiene el historial completo de horarios de un empleado
     * GET /empleados/{id}/historial-horarios
     */
    public function historialHorariosJson($id) {
        try {
            $id = (int)$id;
            
            require_once __DIR__ . '/../models/Database.php';
            require_once __DIR__ . '/../models/Empleado.php';
            
            // Verificar que existe el empleado
            $empleadoModel = new Empleado();
            $empleado = $empleadoModel->getById($id);
            
            if (!$empleado) {
                $this->jsonResponse(['error' => 'Empleado no encontrado'], 404);
                return;
            }
            
            // Obtener historial usando el clasificador
            require_once __DIR__ . '/../models/HorarioClassifier.php';
            $classifier = new HorarioClassifier();
            $historial = $classifier->getHistorialCompleto($id);
            
            $this->jsonResponse([
                'success' => true,
                'empleado' => [
                    'id' => $empleado['id'],
                    'nombre' => $empleado['nombre'],
                    'apellido' => $empleado['apellido']
                ],
                'historial' => $historial
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
