<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/DiasEconomicos.php';

/**
 * Controller for Días Económicos (Economic Days)
 * Gestión de días económicos para empleados
 */

class DiasEconomicosController extends BaseController
{
    private $db;
    private $diasEconomicosModel;
    
    public function __construct() 
    {
        parent::__construct();
        $this->requireAuth();
        $this->db = Database::getInstance();
        $this->diasEconomicosModel = new DiasEconomicos();
    }
    
    /**
     * Mostrar índice de días económicos
     */
    public function index() 
    {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        
        try {
            $diasEconomicos = $this->diasEconomicosModel->getAll();
            $empleados = $this->diasEconomicosModel->getEmpleados();
            
            include 'views/dias_economicos/index.php';
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'index']);
            error_log("Error en DiasEconomicosController::index: " . $e->getMessage());
            $_SESSION['error'] = "Error al cargar días económicos";
            $this->redirect('/dashboard');
        }
    }
    
    /**
     * Solicitar un día económico
     */
    public function solicitar() 
    {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'No autenticado']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
        }
        
        try {
            $empleadoId = $_POST['empleado_id'] ?? null;
            $fecha = $_POST['fecha'] ?? null;
            $motivo = $_POST['motivo'] ?? '';
            $diasSolicitados = $_POST['dias_solicitados'] ?? 1;
            $asistenciaId = $_POST['asistencia_id'] ?? null;
            
            // Validaciones
            if (!$empleadoId || !$fecha) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos']);
            }
            
            // Obtener información del empleado
            require_once 'models/Empleado.php';
            $empleadoModel = new Empleado();
            $empleado = $empleadoModel->getById($empleadoId);
            
            if (!$empleado) {
                $this->jsonResponse(['success' => false, 'message' => 'Empleado no encontrado']);
            }
            
            // Validar plaza de confianza (no puede tener días económicos)
            $plazaConfianza = $empleado['plaza_confianza'] ?? null;
            $esConfianza = is_numeric($plazaConfianza)
                ? ((int)$plazaConfianza === 1)
                : in_array(strtolower(trim((string)$plazaConfianza)), ['1', 'si', 'sí', 'true', 'confianza'], true);
            if ($esConfianza) {
                $this->jsonResponse(['success' => false, 'message' => 'Las plazas de confianza no pueden solicitar días económicos']);
            }
            
            // Validar antigüedad (>6 meses = 180 días)
            $fechaIngreso = $empleado['fecha_ingreso'] ?? $empleado['fecha_alta'] ?? null;
            if ($fechaIngreso) {
                $antiguedad = floor((strtotime(date('Y-m-d')) - strtotime($fechaIngreso)) / (60 * 60 * 24));
                if ($antiguedad < 181) {
                    $this->jsonResponse(['success' => false, 'message' => "El empleado debe tener más de 6 meses y 1 día de antigüedad. Actualmente tiene $antiguedad días."]);
                }
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'El empleado no tiene fecha de ingreso registrada']);
            }
            
            // Validar que no sea viernes o lunes
            $diaSemana = date('w', strtotime($fecha));
            if ($diaSemana == 5) { // Viernes
                $this->jsonResponse(['success' => false, 'message' => 'No se puede solicitar día económico en viernes']);
            }
            if ($diaSemana == 1) { // Lunes
                $this->jsonResponse(['success' => false, 'message' => 'No se puede solicitar día económico en lunes']);
            }
            
            // Validar anticipación (mínimo 1 día)
            $diffDias = (strtotime($fecha) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
            if ($diffDias < 1) {
                $this->jsonResponse(['success' => false, 'message' => 'Debe solicitar con al menos 1 día de anticipación']);
            }
            
            // Verificar si el empleado tiene días económicos disponibles
            $diasDisponibles = $this->diasEconomicosModel->getDiasDisponibles($empleadoId);
            if ($diasDisponibles <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'No tiene días económicos disponibles']);
            }
            
            // Validar días solicitados no excedan disponibles
            if ($diasSolicitados > $diasDisponibles) {
                $this->jsonResponse(['success' => false, 'message' => "Solo tiene $diasDisponibles días disponibles"]);
            }

            if ((int)$diasSolicitados < 1 || (int)$diasSolicitados > 3) {
                $this->jsonResponse(['success' => false, 'message' => 'Solo se permiten solicitudes de 1, 2 o 3 días económicos']);
            }
            
            // Verificar reglas de espera según modalidad
            $validacionEspera = $this->validarPeriodoEspera($empleadoId, $fecha, (int)$diasSolicitados);
            if (!$validacionEspera['valido']) {
                $this->jsonResponse(['success' => false, 'message' => $validacionEspera['error']]);
            }
            
            // Verificar si ya existe una solicitud para esa fecha
            if ($this->diasEconomicosModel->existeSolicitud($empleadoId, $fecha)) {
                $this->jsonResponse(['success' => false, 'message' => 'Ya existe una solicitud para esta fecha']);
            }
            
            // Crear la solicitud
            $data = [
                'empleado_id' => $empleadoId,
                'fecha' => $fecha,
                'motivo' => $motivo,
                'estatus' => 'pendiente',
                'solicitado_por' => $_SESSION['user_id'],
                'fecha_solicitud' => date('Y-m-d H:i:s'),
                'dias_solicitados' => $diasSolicitados,
                'modalidad' => $diasSolicitados >= 3 ? 'A' : ($diasSolicitados == 2 ? 'B' : 'C')
            ];
            
            $result = $this->diasEconomicosModel->create($data);
            
            // Si hay asistencia_id, actualizar el tipo de asistencia
            if ($result && $asistenciaId) {
                require_once 'models/Asistencia.php';
                $asistenciaModel = new Asistencia();
                $asistenciaModel->update($asistenciaId, ['tipo_asistencia' => 'normal']);
            }
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Solicitud de día económico enviada correctamente',
                    'dias_restantes' => max(0, $diasDisponibles - (int)$diasSolicitados)
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al crear solicitud']);
            }
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'solicitar']);
            error_log("Error en DiasEconomicosController::solicitar: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error del servidor']);
        }
    }
    
    /**
     * Aprobar día económico (para administradores)
     */
    public function aprobar() 
    {
        // Verificar autenticación y permisos
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['admin', 'superadmin'])) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
        }
        
        try {
            $id = $_POST['id'] ?? null;
            $comentarios = $_POST['comentarios'] ?? '';
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID no proporcionado']);
            }
            
            $data = [
                'estatus' => 'aprobado',
                'aprobado_por' => $_SESSION['user_id'],
                'fecha_aprobacion' => date('Y-m-d H:i:s'),
                'comentarios_aprobacion' => $comentarios
            ];
            
            $result = $this->diasEconomicosModel->update($id, $data);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Día económico aprobado']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al aprobar']);
            }
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'aprobar', 'solicitud_id' => $id ?? null]);
            error_log("Error en DiasEconomicosController::aprobar: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error del servidor']);
        }
    }
    
    /**
     * Rechazar día económico (para administradores)
     */
    public function rechazar() 
    {
        // Verificar autenticación y permisos
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['admin', 'superadmin'])) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
        }
        
        try {
            $id = $_POST['id'] ?? null;
            $motivo_rechazo = $_POST['motivo_rechazo'] ?? '';
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID no proporcionado']);
            }
            
            $data = [
                'estatus' => 'rechazado',
                'rechazado_por' => $_SESSION['user_id'],
                'fecha_rechazo' => date('Y-m-d H:i:s'),
                'motivo_rechazo' => $motivo_rechazo
            ];
            
            $result = $this->diasEconomicosModel->update($id, $data);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Día económico rechazado']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al rechazar']);
            }
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'rechazar', 'solicitud_id' => $id ?? null]);
            error_log("Error en DiasEconomicosController::rechazar: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error del servidor']);
        }
    }
    
    /**
     * Actualizar un día económico desde el modal del empleado
     */
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
        $fecha = $input['fecha'] ?? '';
        $dias_solicitados = intval($input['dias_solicitados'] ?? 1);
        $modalidad = $input['modalidad'] ?? 'A';
        $motivo = trim($input['motivo'] ?? '');

        if (!$id || !$empleado_id) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            return;
        }
        if (empty($fecha)) {
            echo json_encode(['success' => false, 'error' => 'La fecha es requerida']);
            return;
        }
        if (empty($motivo)) {
            echo json_encode(['success' => false, 'error' => 'El motivo es requerido']);
            return;
        }

        try {
            $this->diasEconomicosModel->update($id, [
                'fecha' => $fecha,
                'dias_solicitados' => $dias_solicitados,
                'modalidad' => $modalidad,
                'motivo' => $motivo
            ]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'actualizar', 'dia_economico_id' => $id]);
            echo json_encode(['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtener días económicos de un empleado (AJAX)
     */
    public function getPorEmpleado() 
    {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'No autenticado']);
        }
        
        $empleadoId = $_GET['empleado_id'] ?? null;
        
        if (!$empleadoId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID de empleado no proporcionado']);
        }
        
        try {
            $diasEconomicos = $this->diasEconomicosModel->getByEmpleado($empleadoId);
            $diasDisponibles = $this->diasEconomicosModel->getDiasDisponibles($empleadoId);
            
            $this->jsonResponse([
                'success' => true,
                'dias_economicos' => $diasEconomicos,
                'dias_disponibles' => $diasDisponibles
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'getPorEmpleado', 'empleado_id' => $empleadoId ?? null]);
            error_log("Error en DiasEconomicosController::getPorEmpleado: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error del servidor']);
        }
    }
    
    /**
     * Validar periodo de espera según reglas de días económicos
     */
    private function validarPeriodoEspera($empleado_id, $fecha_solicitada, $dias_solicitados) {
        // Obtener último uso aprobado directamente
        $db = new Database();
        $stmt = $db->getConnection()->prepare("
            SELECT fecha, dias_solicitados
            FROM dias_economicos
            WHERE empleado_id = ? 
            AND (estatus = 'aprobado' OR justificado = 1)
            ORDER BY fecha DESC
            LIMIT 1
        ");
        $stmt->execute([$empleado_id]);
        $ultimo = $stmt->fetch();
        
        if (!$ultimo) {
            return ['valido' => true];
        }
        
        // Calcular el último día del periodo anterior
        $ultimo_dia = date('Y-m-d', strtotime($ultimo['fecha'] . ' + ' . ($ultimo['dias_solicitados'] - 1) . ' days'));
        
        $nueva_fecha = strtotime($fecha_solicitada ?: date('Y-m-d'));
        $fecha_ultimo = strtotime($ultimo_dia);
        $dias_transcurridos = floor(($nueva_fecha - $fecha_ultimo) / (60 * 60 * 24));
        
        // La espera aplica según el último disfrute aprobado.
        $dias_ultimo = (int)($ultimo['dias_solicitados'] ?? 1);
        if ($dias_ultimo >= 3) {
            $dias_espera = 30; // Modalidad A
        } elseif ($dias_ultimo == 2) {
            $dias_espera = 15; // Modalidad B
        } else {
            $dias_espera = 7; // Modalidad C
        }
        
        if ($dias_transcurridos < $dias_espera) {
            $faltan = $dias_espera - $dias_transcurridos;
            $modalidadAnterior = ($dias_ultimo >= 3 ? 'A' : ($dias_ultimo == 2 ? 'B' : 'C'));
            return [
                'valido' => false,
                'error' => "Modalidad anterior $modalidadAnterior: Debe esperar $dias_espera días desde el último día económico usado. Han transcurrido $dias_transcurridos días. Faltan $faltan días."
            ];
        }
        
        return ['valido' => true];
    }
}
