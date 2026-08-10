<?php
require_once __DIR__ . '/../models/Comision.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../helpers/RequestValidator.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
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
                $errors = [];
                if (empty($data['empleado_id'])) $errors[] = 'Empleado es requerido';
                if (empty($data['descripcion'])) $errors[] = 'Descripción es requerida';
                if (empty($data['fecha_inicio'])) $errors[] = 'Fecha de inicio es requerida';
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

public function actualizar() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
            return;
        }
        $rol = strtolower((string)($_SESSION['rol'] ?? ''));
        if (!in_array($rol, ['admin', 'superadmin'], true)) {
            echo json_encode(['success' => false, 'error' => 'Acceso denegado. Solo administradores pueden editar registros.']);
            return;
        }
        $id = intval($input['id'] ?? 0);
        $empleado_id = intval($input['empleado_id'] ?? 0);
        $descripcion = trim($input['descripcion'] ?? '');
        $fecha_inicio = $input['fecha_inicio'] ?? '';
        $fecha_fin = $input['fecha_fin'] ?? $fecha_inicio;
        $tipo = $input['tipo'] ?? 'comision_todo_dia';

        if (!$id || !$empleado_id) {
            echo json_encode(['success' => false, 'error' => 'ID de comisión o empleado inválido']);
            return;
        }
        if (empty($descripcion)) {
            echo json_encode(['success' => false, 'error' => 'La descripción es requerida']);
            return;
        }
        if (empty($fecha_inicio)) {
            echo json_encode(['success' => false, 'error' => 'La fecha de inicio es requerida']);
            return;
        }
        if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
            echo json_encode(['success' => false, 'error' => 'La fecha fin no puede ser menor a la fecha inicio']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            // Obtener datos actuales de la comisión para comparar
            $stmt = $db->prepare("SELECT * FROM comisiones WHERE id = ?");
            $stmt->execute([$id]);
            $oldData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                echo json_encode(['success' => false, 'error' => 'Comisión no encontrada']);
                return;
            }

            $oldFechaInicio = $oldData['fecha_inicio'];
            $oldFechaFin = $oldData['fecha_fin'];

            // 1. Actualizar registro en comisiones
            $stmt = $db->prepare("UPDATE comisiones SET descripcion = ?, fecha_inicio = ?, fecha_fin = ?, tipo_comision = ? WHERE id = ?");
            $stmt->execute([$descripcion, $fecha_inicio, $fecha_fin, $tipo, $id]);

            // 2. Sincronizar registros de asistencia
            $tiposComisionAsistencia = ['comision_entrada', 'comision_salida', 'comision_todo_dia'];
            $tipoAsistencia = in_array($tipo, $tiposComisionAsistencia) ? $tipo : 'comision_todo_dia';

            // 2a. Eliminar registros de asistencia antiguos para las fechas viejas (solo comisiones)
            $fechaOld = new DateTime($oldFechaInicio);
            $fechaOldFin = new DateTime($oldFechaFin);
            while ($fechaOld <= $fechaOldFin) {
                $fechaStr = $fechaOld->format('Y-m-d');
                $stmt = $db->prepare("DELETE FROM asistencia WHERE empleado_id = ? AND fecha = ? AND tipo_asistencia LIKE 'comision_%'");
                $stmt->execute([$empleado_id, $fechaStr]);
                $fechaOld->modify('+1 day');
            }

            // 2b. Crear/actualizar registros para las nuevas fechas
            $fechaNew = new DateTime($fecha_inicio);
            $fechaNewFin = new DateTime($fecha_fin);
            while ($fechaNew <= $fechaNewFin) {
                $fechaStr = $fechaNew->format('Y-m-d');

                $stmt = $db->prepare("SELECT id FROM asistencia WHERE empleado_id = ? AND fecha = ?");
                $stmt->execute([$empleado_id, $fechaStr]);
                $existente = $stmt->fetch();

                if (!$existente) {
                    $stmt = $db->prepare("INSERT INTO asistencia (empleado_id, fecha, tipo_asistencia, observaciones, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$empleado_id, $fechaStr, $tipoAsistencia, $descripcion]);
                } else {
                    $stmt = $db->prepare("UPDATE asistencia SET tipo_asistencia = ?, observaciones = ? WHERE id = ?");
                    $stmt->execute([$tipoAsistencia, $descripcion, $existente['id']]);
                }

                $fechaNew->modify('+1 day');
            }

            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->logException($e, ['action' => 'actualizar', 'comision_id' => $id]);
            echo json_encode(['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
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
