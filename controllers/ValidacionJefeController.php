<?php
require_once __DIR__ . '/../models/ValidacionJefe.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Comision.php';
require_once __DIR__ . '/../models/DiasEconomicos.php';
require_once __DIR__ . '/../models/Ausencia.php';
require_once __DIR__ . '/../helpers/Csrf.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Controlador para gestión de validaciones por jefes
 * Maneja todo el flujo de aprobación/rechazo de incidencias de asistencia
 */
class ValidacionJefeController extends BaseController {
    
    private $validacionModel;
    private $empleadoModel;
    private $retardoModel;
    private $comisionModel;
    private $diaEconomicoModel;
    private $ausenciaModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->validacionModel = new ValidacionJefe();
        $this->empleadoModel = new Empleado();
        $this->retardoModel = new Retardo();
        $this->comisionModel = new Comision();
        $this->diaEconomicoModel = new DiasEconomicos();
        
        // Para ausencias, buscamos si existe el modelo
        if (file_exists(__DIR__ . '/../models/Ausencia.php')) {
            $this->ausenciaModel = new Ausencia();
        }
    }
    
    /**
     * Verificación de si el usuario es jefe y tiene permisos
     */
    private function esJefeTemporal() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $rol = $_SESSION['rol'] ?? '';
        
        // Permitir acceso a jefe, admin, superadmin
        if (in_array($rol, ['jefe', 'admin', 'superadmin'])) {
            return true;
        }
        
        $this->jsonResponse(['success' => false, 'error' => 'No autorizado - Se requiere rol de jefe'], 403);
        return false;
    }
    
    /**
     * Página principal de validación por jefes
     * Muestra todas las incidencias pendientes para el jefe autenticado
     */
    public function index() {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('validaciones')) {
            $_SESSION['error'] = 'No tienes permiso para acceder a validaciones.';
            $this->redirect(BASE_URL . '/dashboard');
            return;
        }

        if (!$this->esJefe()) {
            $_SESSION['error'] = 'Solo el rol jefe puede acceder al módulo de validaciones.';
            $this->redirect(BASE_URL . '/dashboard');
            return;
        }
        
        // Obtener el empleado_id del jefe (no el user_id)
        $pdo = Database::getInstance()->getConnection();
        $stmtJefe = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
        $stmtJefe->execute([$_SESSION['user_id'] ?? 1]);
        $jefeId = $stmtJefe->fetchColumn() ?? 1;
        
        // Obtener filtros de la URL
        $filters = [
            'area' => $_GET['area'] ?? null,
            'empleado_id' => $_GET['empleado_id'] ?? null,
            'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
            'fecha_fin' => $_GET['fecha_fin'] ?? null,
            'tipo_incidencia' => $_GET['tipo_incidencia'] ?? null
        ];
        
        // Obtener incidencias pendientes
        $incidenciasPendientes = $this->validacionModel->getPendientesPorJefe($jefeId, $filters);
        
        // Obtener estadísticas
        $estadisticas = $this->validacionModel->getEstadisticasPorJefe($jefeId, [
            'mes' => date('m'),
            'anio' => date('Y')
        ]);
        
        // Obtener el empleado_id del jefe desde la tabla usuarios
        $empleado_id_jefe = $this->getEmpleadoIdDelUsuarioActual();
        
        // Obtener lista de empleados a cargo
        $empleadosACargo = $empleado_id_jefe ? $this->getEmpleadosACargo($empleado_id_jefe) : [];
        
        // Obtener áreas disponibles
        $areas = $empleado_id_jefe ? $this->getAreasDisponibles($empleado_id_jefe) : [];
        
        // Procesar automáticamente incidencias pendientes si es necesario
        $resultadoProcesamiento = $this->validacionModel->procesarIncidenciasPendientes();
        
        ob_start();
        include 'views/validaciones/index.php';
        $content = ob_get_clean();
        include 'views/layout.php';
    }
    
    /**
     * Formulario detallado para validar una incidencia específica
     */
    public function validar($id) {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('validaciones_aprobar')) {
            $this->jsonResponse(['success' => false, 'error' => 'No tienes permiso para aprobar validaciones'], 403);
            return;
        }
        
        // Limpiar buffer para evitar espacios en blanco antes del JSON
        if (ob_get_length()) ob_clean();
        
        // Validar CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        if (!Csrf::validate($csrfToken)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido'], 403);
            return;
        }
        
        // Verificar que sea jefe
        if (!$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        // Obtener el empleado_id del jefe (no el user_id)
        $pdo = Database::getInstance()->getConnection();
        $stmtJefe = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
        $stmtJefe->execute([$_SESSION['user_id'] ?? 1]);
        $jefeId = $stmtJefe->fetchColumn() ?? 1;
        
        // Obtener detalles de la validación
        $validacion = $this->validacionModel->getById($id);
        
        if (!$validacion) {
            error_log("Validación no encontrada. ID solicitado: $id");
            $this->jsonResponse(['success' => false, 'error' => 'Validación no encontrada'], 404);
            return;
        }
        
        if ($validacion['jefe_id'] != $jefeId) {
            // Verificar si está autorizado por catalogos_mandos
            if (!$this->esJefeAutorizadoParaEmpleado($jefeId, $validacion['empleado_id'])) {
                error_log("Acceso denegado. Validación ID: $id pertenece al jefe {$validacion['jefe_id']}, usuario actual: $jefeId");
                $this->jsonResponse(['success' => false, 'error' => 'Validación no encontrada o no autorizado'], 404);
                return;
            }
        }
        
        // Obtener detalles específicos según tipo
        $detallesIncidencia = $this->getDetallesIncidencia($validacion);
        
        // Obtener historial del empleado
        $historialEmpleado = $this->validacionModel->getHistorialPorEmpleado(
            $validacion['empleado_id'], 
            10
        );
        
        // Obtener reglas aplicables
        $reglasAplicables = $this->getReglasAplicables($validacion['tipo_incidencia']);
        
        $this->jsonResponse([
            'success' => true,
            'validacion' => $validacion,
            'detalles' => $detallesIncidencia,
            'historial' => $historialEmpleado,
            'reglas' => $reglasAplicables
        ]);
    }
    
    /**
     * API para obtener una incidencia por ID (genérico para retardos y asistencia)
     */
    public function obtenerIncidencia($id) {
        if (ob_get_length()) ob_clean();
        
        if (!$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        $origen = $_GET['origen'] ?? 'retardos';
        $empleadoIdMando = $this->getEmpleadoIdDelUsuarioActual() ?? 1;
        
        $pdo = Database::getInstance()->getConnection();
        
        if ($origen === 'asistencia') {
            // Buscar en tabla asistencia
            $stmt = $pdo->prepare("
                SELECT a.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido, 
                       e.area as empleado_area, e.jefe_directo_id,
                       'asistencia' as origen
                FROM asistencia a
                INNER JOIN empleados e ON a.empleado_id = e.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$incidencia) {
                $this->jsonResponse(['success' => false, 'error' => 'Incidencia no encontrada'], 404);
                return;
            }
            
            // Verificar que pertenece al mando
            if ($incidencia['jefe_directo_id'] != $empleadoIdMando) {
                if (!$this->esJefeAutorizadoParaEmpleado($empleadoIdMando, $incidencia['empleado_id'])) {
                    $this->jsonResponse(['success' => false, 'error' => 'No tienes permiso'], 403);
                    return;
                }
            }
            
            // Crear objeto de validación
            $validacion = [
                'id' => 0,
                'incidencia_id' => $incidencia['id'],
                'tipo_incidencia' => $incidencia['tipo_asistencia'],
                'empleado_id' => $incidencia['empleado_id'],
                'empleado_nombre' => $incidencia['empleado_nombre'],
                'empleado_apellido' => $incidencia['empleado_apellido'],
                'empleado_area' => $incidencia['empleado_area'],
                'estado' => $incidencia['estado_validacion'] ?? 'pendiente',
                'descripcion_incidencia' => $this->getDescripcionIncidencia(['tipo_incidencia' => $incidencia['tipo_asistencia']]),
                'fecha_incidencia' => $incidencia['fecha']
            ];
            
            $this->jsonResponse([
                'success' => true,
                'validacion' => $validacion,
                'detalles' => $incidencia
            ]);
            
        } else {
            if ($origen === 'validacion_jefe') {
                $stmtVj = $pdo->prepare("
                    SELECT v.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido, e.area as empleado_area
                    FROM validaciones_jefe v
                    INNER JOIN empleados e ON v.empleado_id = e.id
                    WHERE v.id = ?
                ");
                $stmtVj->execute([$id]);
                $vj = $stmtVj->fetch(PDO::FETCH_ASSOC);

                if (!$vj) {
                    $this->jsonResponse(['success' => false, 'error' => 'Validación no encontrada'], 404);
                    return;
                }

                $vj['descripcion_incidencia'] = $this->getDescripcionIncidencia($vj);
                $vj['fecha_incidencia'] = $vj['fecha'] ?? $vj['fecha_inicio'] ?? $vj['created_at'] ?? date('Y-m-d');

                $this->jsonResponse([
                    'success' => true,
                    'validacion' => $vj,
                    'detalles' => $vj
                ]);
                return;
            }

            // Resolver ID: puede ser validaciones_jefe.id o retardo.id directamente (sin solicitud)
            $resolvedId = $id;

            if ($origen === 'retardos') {
                $stmtVj = $pdo->prepare("SELECT incidencia_id FROM validaciones_jefe WHERE id = ?");
                $stmtVj->execute([$id]);
                $vj = $stmtVj->fetch(PDO::FETCH_ASSOC);
                if ($vj) {
                    $resolvedId = $vj['incidencia_id'];
                }
            }

            return $this->validarPorRetardo($resolvedId);
        }
    }
    
    /**
     * Formulario detallado para validar una incidencia por ID de retardo
     */
    public function validarPorRetardo($retardoId) {
        // Limpiar buffer para evitar espacios en blanco antes del JSON
        if (ob_get_length()) ob_clean();
        
        // Verificar que sea jefe
        if (!$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        // Obtener el empleado_id del mando actual
        $empleadoIdMando = $this->getEmpleadoIdDelUsuarioActual() ?? 1;
        
        // Obtener datos del retardo
        $pdo = Database::getInstance()->getConnection();
        
        // Obtener el retardo
        $stmtRetardo = $pdo->prepare("
            SELECT r.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido, 
                   e.area as empleado_area, e.jefe_directo_id
            FROM retardos r
            INNER JOIN empleados e ON r.empleado_id = e.id
            WHERE r.id = ?
        ");
        $stmtRetardo->execute([$retardoId]);
        $retardo = $stmtRetardo->fetch(PDO::FETCH_ASSOC);
        
        if (!$retardo) {
            $this->jsonResponse(['success' => false, 'error' => 'Retardo no encontrado'], 404);
            return;
        }
        
        // Verificar que el empleado pertenece al mando actual
        if ($retardo['jefe_directo_id'] != $empleadoIdMando) {
            if (!$this->esJefeAutorizadoParaEmpleado($empleadoIdMando, $retardo['empleado_id'])) {
                $this->jsonResponse(['success' => false, 'error' => 'No tienes permiso para ver este retardo'], 403);
                return;
            }
        }
        
        // Buscar si existe una validacion para este retardo
        $stmtValidacion = $pdo->prepare("
            SELECT v.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido, e.area as empleado_area
            FROM validaciones_jefe v
            INNER JOIN empleados e ON v.empleado_id = e.id
            WHERE v.incidencia_id = ? AND v.tipo_incidencia = 'retardo'
        ");
        $stmtValidacion->execute([$retardoId]);
        $validacion = $stmtValidacion->fetch(PDO::FETCH_ASSOC);
        
        // Crear objeto de validación
        if ($validacion) {
            $detalles = $retardo;
            $validacion['tipo_incidencia'] = $retardo['tipo_retraso'] ?? $validacion['tipo_incidencia'];
        } else {
            // No existe validación, crear datos básicos
            $validacion = [
                'id' => 0,
                'incidencia_id' => $retardo['id'],
                'tipo_incidencia' => $retardo['tipo_retraso'] ?? 'retardo',
                'empleado_id' => $retardo['empleado_id'],
                'empleado_nombre' => $retardo['empleado_nombre'],
                'empleado_apellido' => $retardo['empleado_apellido'],
                'empleado_area' => $retardo['empleado_area'],
                'estado' => $retardo['estado_validacion'] ?? 'pendiente',
                'descripcion_incidencia' => $retardo['motivo_justificacion'] ?? 'Sin justificación',
                'fecha_incidencia' => $retardo['fecha']
            ];
            $detalles = $retardo;
        }
        
        $this->jsonResponse([
            'success' => true,
            'validacion' => $validacion,
            'detalles' => $detalles
        ]);
    }
    
    /**
     * Procesar decisión de validación (aprobar/rechazar/requerir info)
     */
    public function procesar() {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('validaciones_aprobar')) {
            $this->jsonResponse(['success' => false, 'error' => 'No tienes permiso para procesar validaciones'], 403);
            return;
        }
        
        // Validar CSRF
        $csrfToken = $_POST['csrf_token'] ?? null;
        if (!Csrf::validate($csrfToken)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido'], 403);
            return;
        }
        
        // Verificar que sea jefe
        if (!$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        $validacionId = $_POST['validacion_id'] ?? null;
        $incidenciaId = $_POST['incidencia_id'] ?? null;
        $tipoIncidencia = $_POST['tipo_incidencia'] ?? null;
        $empleadoId = $_POST['empleado_id'] ?? null;
        $esSinSolicitud = isset($_POST['es_sin_solicitud']) && $_POST['es_sin_solicitud'];
        
        $decision = [
            'estado' => $_POST['estado'] ?? null,
            'motivo' => $_POST['motivo'] ?? '',
            'comentarios' => $_POST['comentarios'] ?? '',
            'evidencia_recibida' => isset($_POST['evidencia_recibida']),
            'aprobado_por' => $_SESSION['user_id'],
            'nuevo_tipo_incidencia' => $_POST['nuevo_tipo_incidencia'] ?? null,
            'es_sin_solicitud' => $esSinSolicitud
        ];
        
        // Validar decisión - incluir 'notificar_empleado' para incidencias sin solicitud
        $estadosValidos = ['aprobado', 'rechazado', 'requiere_info'];
        if ($esSinSolicitud) {
            $estadosValidos[] = 'notificar_empleado';
        }
        
        if (empty($decision['estado']) || !in_array($decision['estado'], $estadosValidos)) {
            $this->jsonResponse(['success' => false, 'error' => 'Debe seleccionar una decisión válida'], 400);
            return;
        }
        
        // Para incidencias sin solicitud, requerir comentario
        if ($esSinSolicitud && empty($decision['comentarios'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Debe escribir un comentario para notificar al empleado'], 400);
            return;
        }
        
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Si validacionId es 0 o vacío, necesitamos crear la validación primero
            if (empty($validacionId) || $validacionId == 0) {
                if (empty($incidenciaId) || empty($tipoIncidencia) || empty($empleadoId)) {
                    $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos para crear validación'], 400);
                    return;
                }
                
                $jefeEmpleadoId = $this->getEmpleadoIdDelUsuarioActual();
                
                // Para incidencias sin solicitud, crear registro de notificación
                $estadoFinal = $decision['estado'];
                if ($decision['estado'] === 'notificar_empleado') {
                    $estadoFinal = 'pendiente'; // Marcar como pendiente para que el empleado justifique
                }
                
                // Crear registro en validaciones_jefe
                $stmtInsert = $pdo->prepare("
                    INSERT INTO validaciones_jefe 
                    (incidencia_id, tipo_incidencia, empleado_id, jefe_id, estado, 
                     motivo_validacion, comentarios_adicionales, fecha_solicitud, fecha_validacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmtInsert->execute([
                    $incidenciaId,
                    $tipoIncidencia,
                    $empleadoId,
                    $jefeEmpleadoId,
                    $estadoFinal,
                    $decision['motivo'],
                    $decision['comentarios']
                ]);
                
                $validacionId = $pdo->lastInsertId();
                
                // Si es una incidencia sin solicitud, guardar el comentario como notificación
                if ($esSinSolicitud && !empty($decision['comentarios'])) {
                    $this->guardarNotificacionEmpleado($empleadoId, $incidenciaId, $tipoIncidencia, $decision['comentarios'], $jefeEmpleadoId);
                }
            } else {
                // Si ya existe la validación, procesarla normalmente
                $resultado = $this->validacionModel->procesarDecision($validacionId, $decision);
                
                if (!$resultado) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => 'Error al procesar la validación'
                    ]);
                    return;
                }
            }
            
            $this->notificarEmpleado($validacionId, $decision);
            
            $mensaje = $esSinSolicitud && $decision['estado'] === 'notificar_empleado' 
                ? 'Se ha notificado al empleado para que justifique su retraso' 
                : 'Validación procesada exitosamente';
            
            $this->jsonResponse([
                'success' => true,
                'message' => $mensaje
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'procesarValidacion']);
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Guardar notificación para el empleado
     */
    private function guardarNotificacionEmpleado($empleadoId, $incidenciaId, $tipoIncidencia, $comentario, $jefeEmpleadoId) {
        $pdo = Database::getInstance()->getConnection();
        
        // Verificar si existe tabla de notificaciones, si no, solo guardar en validaciones
        try {
            // Intentar crear una notificación en tabla notificaciones si existe
            $stmtCheck = $pdo->prepare("SHOW TABLES LIKE 'notificaciones'");
            $stmtCheck->execute();
            $existeTabla = $stmtCheck->fetch();
            
            if ($existeTabla) {
                $stmtNotif = $pdo->prepare("
                    INSERT INTO notificaciones (empleado_id, tipo, mensaje, relacionado_id, relacionado_tipo, leida, created_at)
                    VALUES (?, 'requiere_justificacion', ?, ?, ?, 0, NOW())
                ");
                $stmtNotif->execute([
                    $empleadoId,
                    $comentario,
                    $incidenciaId,
                    $tipoIncidencia
                ]);
            } else {
                // Si no existe tabla notificaciones, guardar en log
                error_log("Notificación para empleado $empleadoId: $comentario (Incidencia: $incidenciaId, Tipo: $tipoIncidencia)");
            }
        } catch (Exception $e) {
            // Si falla, solo registrar en log
            error_log("Error guardando notificación: " . $e->getMessage());
        }
    }
    
    /**
     * Alias para procesar validación masiva (por si el router busca este nombre)
     */
    public function procesarMasivo() {
        return $this->procesarValidacionMasiva();
    }

    /**
     * Procesar validación masiva
     */
    public function procesarValidacionMasiva() {
        // Validar CSRF
        $csrfToken = $_POST['csrf_token'] ?? null;
        if (!Csrf::validate($csrfToken)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido'], 403);
            return;
        }
        
        // Verificar que sea jefe
        if (!$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        $validacionesIds = $_POST['validaciones_ids'] ?? [];
        $decisionMasiva = [
            'estado' => $_POST['estado'] ?? null,
            'motivo' => $_POST['motivo'] ?? '',
            'aprobado_por' => $_SESSION['user_id']
        ];
        
        if (empty($validacionesIds) || !in_array($decisionMasiva['estado'], ['aprobado', 'rechazado', 'requiere_info'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
            return;
        }
        
        $procesadas = 0;
        $errores = [];
        
        foreach ($validacionesIds as $validacionId) {
            try {
                $resultado = $this->validacionModel->procesarDecision($validacionId, $decisionMasiva);
                if ($resultado) {
                    $procesadas++;
                } else {
                    $errores[] = $validacionId;
                }
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'procesarValidacionMasiva', 'validacion_id' => $validacionId]);
                $errores[] = "ID $validacionId: " . $e->getMessage();
            }
        }
        
        $this->jsonResponse([
            'success' => true,
            'procesadas' => $procesadas,
            'errores' => $errores,
            'message' => "Se procesaron $procesadas validaciones exitosamente"
        ]);
    }
    
    /**
     * API para obtener estadísticas en tiempo real
     */
    public function estadisticas() {
        // Limpiar buffer para evitar salida antes de JSON
        if (ob_get_length()) ob_clean();
        
        if (!$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        try {
            // Usar el user_id de la sesión
            $userId = $_SESSION['user_id'] ?? 1;
            $periodo = [
                'mes' => $_GET['mes'] ?? date('m'),
                'anio' => $_GET['anio'] ?? date('Y')
            ];
            
            $estadisticas = $this->validacionModel->getEstadisticasPorJefe($userId, $periodo);
            
            // Calcular métricas adicionales
            $estadisticas['tasa_aprobacion'] = $estadisticas['total'] > 0 
                ? round(($estadisticas['aprobados'] / $estadisticas['total']) * 100, 2) 
                : 0;
                
            $estadisticas['tasa_rechazo'] = $estadisticas['total'] > 0 
                ? round(($estadisticas['rechazados'] / $estadisticas['total']) * 100, 2) 
                : 0;
            
            $this->jsonResponse([
                'success' => true,
                'estadisticas' => $estadisticas
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'estadisticas']);
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error obteniendo estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * API para buscar empleados a cargo
     */
    public function buscarEmpleados() {
        if (!$this->esJefeTemporal()) {
            return;
        }
        
        $query = $_GET['query'] ?? '';
        $soloConIncidencias = isset($_GET['solo_con_incidencias']) && $_GET['solo_con_incidencias'] != '0';
        
        // Obtener el empleado_id del mando desde la tabla usuarios
        $pdo = Database::getInstance()->getConnection();
        $stmtUsuario = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
        $stmtUsuario->execute([$_SESSION['user_id'] ?? 0]);
        $empleadoIdMando = $stmtUsuario->fetchColumn();
        
        if (!$empleadoIdMando) {
            $this->jsonResponse([
                'success' => true,
                'empleados' => [],
                'message' => 'No tienes un empleado asociado a tu cuenta de usuario.'
            ]);
            return;
        }

        // Obtener IDs de subordinados (directos + catalogos_mandos)
        $subordinadoIds = [];
        
        $stmtDirectos = $pdo->prepare("SELECT id FROM empleados WHERE jefe_directo_id = ? AND activo = 1");
        $stmtDirectos->execute([$empleadoIdMando]);
        $subordinadoIds = $stmtDirectos->fetchAll(PDO::FETCH_COLUMN);
        
        $stmtEmpName = $pdo->prepare("SELECT nombre, apellido FROM empleados WHERE id = ?");
        $stmtEmpName->execute([$empleadoIdMando]);
        $empName = $stmtEmpName->fetch(PDO::FETCH_ASSOC);
        
        $stmtUserInfo = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE empleado_id = ? AND activo = 1 LIMIT 1");
        $stmtUserInfo->execute([$empleadoIdMando]);
        $userName = $stmtUserInfo->fetchColumn();
        
        $nombresMando = [];
        if ($empName) {
            $nombresMando[] = trim($empName['apellido'] . ' ' . $empName['nombre']);
        }
        if ($userName && !in_array($userName, $nombresMando)) {
            $nombresMando[] = $userName;
        }
        
        if (!empty($nombresMando)) {
            $placeholders = implode(',', array_fill(0, count($nombresMando), '?'));
            $stmtMando = $pdo->prepare("
                SELECT id, clave_area, area FROM catalogos_mandos 
                WHERE nombre_mando IN ($placeholders) AND activo = 1
            ");
            $stmtMando->execute($nombresMando);
            $rowsMando = $stmtMando->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rowsMando)) {
                $clavesArea = array_column($rowsMando, 'id');
                $areasMando = array_column($rowsMando, 'area');

                $subPorClave = [];
                $subPorArea = [];

                if (!empty($clavesArea)) {
                    $placeholders = implode(',', array_fill(0, count($clavesArea), '?'));
                    $stmtPorClave = $pdo->prepare("
                        SELECT id FROM empleados WHERE jefe_directo_clave IN ($placeholders) AND activo = 1
                    ");
                    $stmtPorClave->execute($clavesArea);
                    $subPorClave = $stmtPorClave->fetchAll(PDO::FETCH_COLUMN);
                }

                if (!empty($areasMando)) {
                    $placeholdersArea = implode(',', array_fill(0, count($areasMando), '?'));
                    $stmtPorArea = $pdo->prepare("
                        SELECT id FROM empleados WHERE area IN ($placeholdersArea) AND activo = 1
                    ");
                    $stmtPorArea->execute($areasMando);
                    $subPorArea = $stmtPorArea->fetchAll(PDO::FETCH_COLUMN);
                }

                $subordinadoIds = array_merge($subordinadoIds, $subPorClave, $subPorArea);
                $subordinadoIds = array_values(array_unique($subordinadoIds));
            }
        }
        
        if (empty($subordinadoIds)) {
            $this->jsonResponse([
                'success' => true,
                'empleados' => [],
                'message' => 'No tienes subordinados asignados.'
            ]);
            return;
        }
        
        $inPlaceholders = implode(',', array_fill(0, count($subordinadoIds), '?'));
        
        // Consulta con contadores de retardos e incidencias
        $sql = "
            SELECT DISTINCT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                   e.area, e.area_fisica, e.jerarquia,
                   CASE WHEN e.jefe_directo_id = ? THEN 'directo' ELSE 'mando' END as tipo_asignacion,
                   COALESCE(retardos.cantidad_retardos, 0) as cantidad_retardos,
                   COALESCE(asistencia.cantidad_asistencias, 0) as cantidad_asistencias,
                   (COALESCE(retardos.cantidad_retardos, 0) + COALESCE(asistencia.cantidad_asistencias, 0)) as total_incidencias
            FROM empleados e
            LEFT JOIN (
                SELECT empleado_id, COUNT(*) as cantidad_retardos 
                FROM retardos 
                WHERE estado_validacion IS NULL OR estado_validacion = '' OR estado_validacion LIKE 'pendiente%' OR estado_validacion LIKE '%_info'
                GROUP BY empleado_id
            ) retardos ON e.id = retardos.empleado_id
            LEFT JOIN (
                SELECT empleado_id, COUNT(*) as cantidad_asistencias 
                FROM asistencia 
                WHERE tipo_asistencia NOT IN ('normal', 'por_definir')
                AND (estado_validacion IS NULL OR estado_validacion = '' OR estado_validacion LIKE 'pendiente%' OR estado_validacion LIKE '%_info')
                GROUP BY empleado_id
            ) asistencia ON e.id = asistencia.empleado_id
            WHERE e.id IN ($inPlaceholders)";
        
        $parametros = array_merge([$empleadoIdMando], $subordinadoIds);
        
        // Si solo queremos empleados con incidencias, filtramos
        if ($soloConIncidencias) {
            $sql .= " AND (COALESCE(retardos.cantidad_retardos, 0) > 0 OR COALESCE(asistencia.cantidad_asistencias, 0) > 0)";
        }
        
        // Búsqueda por nombre
        if (!empty($query)) {
            $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.area LIKE ?)";
            $parametros[] = "%{$query}%";
            $parametros[] = "%{$query}%";
            $parametros[] = "%{$query}%";
        }
        
        $sql .= " ORDER BY e.nombre";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agregar flag de tiene_incidencias
        foreach ($empleados as &$emp) {
            $emp['tiene_incidencias'] = ($emp['total_incidencias'] > 0);
        }
        
        $this->jsonResponse([
            'success' => true,
            'empleados' => $empleados,
            'message' => empty($empleados) ? 'No hay empleados con incidencias pendientes.' : null,
            'debug' => [
                'empleado_id_mando' => $empleadoIdMando,
                'user_id' => $_SESSION['user_id'] ?? 0
            ]
        ]);
    }
    
    /**
     * API para buscar validaciones pendientes
     */
    public function buscarPendientes() {
        if (!$this->esJefeTemporal()) {
            return;
        }
        
        $jefeId = $this->getEmpleadoIdDelUsuarioActual() ?? 1;
        $empleado_id_jefe = $this->getEmpleadoIdDelUsuarioActual();
        
        if (!$empleado_id_jefe) {
            $this->jsonResponse([
                'success' => true,
                'validaciones' => [],
                'total' => 0,
                'pagina' => 1,
                'limite' => intval($_POST['limite'] ?? 25),
                'message' => 'No tienes un empleado asociado a tu cuenta.'
            ]);
            return;
        }
        
        // Obtener filtros
        $filters = [
            'area' => $_POST['area'] ?? null,
            'empleado_id' => $_POST['empleado'] ?? null,
            'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'fecha_fin' => $_POST['fecha_fin'] ?? null,
            'tipo_incidencia' => $_POST['tipo_incidencia'] ?? null
        ];
        
        $pagina = intval($_POST['pagina'] ?? 1);
        $limite = intval($_POST['limite'] ?? 25);
        
        // Obtener validaciones pendientes usando el método existente
        try {
            $incidenciasPendientes = $this->validacionModel->getPendientesPorJefe($jefeId, $filters);
            
            $this->jsonResponse([
                'success' => true,
                'validaciones' => $incidenciasPendientes,
                'total' => count($incidenciasPendientes),
                'pagina' => $pagina,
                'limite' => $limite
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'buscarPendientes']);
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error obteniendo validaciones: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * API para obtener contador de validaciones pendientes para el menú
     */
    public function obtenerContadorPendientes() {
        try {
            // Para GET requests, no requerir CSRF token inicialmente
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validar CSRF solo para POST
                $csrfToken = $_POST['csrf_token'] ?? null;
                if (!Csrf::validate($csrfToken)) {
                    $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido'], 403);
                    return;
                }
            }
            
            // Verificar que sea jefe
            if (!$this->esJefeTemporal()) {
                $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
                return;
            }
            
        $jefeId = $this->getEmpleadoIdDelUsuarioActual() ?? 1;
            
            $estadisticas = $this->validacionModel->getEstadisticasPorJefe($jefeId, [
                'mes' => date('m'),
                'anio' => date('Y')
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'pendientes' => $estadisticas['pendientes'] ?? 0,
                'aprobados' => $estadisticas['aprobados'] ?? 0,
                'rechazados' => $estadisticas['rechazados'] ?? 0
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'obtenerContadorPendientes']);
            $this->jsonResponse(['success' => false, 'error' => 'Error obteniendo contador: ' . $e->getMessage()]);
        }
    }
    
    /**
     * API para obtener incidencias de empleados seleccionados
     */
    public function incidenciasEmpleados() {
        // Verificar que sea jefe
        if (!$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        $empleado_id_jefe = $this->getEmpleadoIdDelUsuarioActual();
        $empleadosIds = $_POST['empleados_ids'] ?? [];
        $soloPendientes = isset($_POST['solo_pendientes']) && $_POST['solo_pendientes'] !== false;
        
        if (!$empleado_id_jefe) {
            $this->jsonResponse([
                'success' => true,
                'incidencias' => [],
                'total' => 0,
                'message' => 'No tienes un empleado asociado a tu cuenta.'
            ]);
            return;
        }

        // Obtener empleados a cargo del jefe actual (directos + catalogos_mandos)
        $pdo = Database::getInstance()->getConnection();
        
        // Obtener IDs por jefe_directo_id directo
        $stmtSubordinados = $pdo->prepare("
            SELECT e.id 
            FROM empleados e
            INNER JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
            WHERE u.id = ?
        ");
        $stmtSubordinados->execute([$_SESSION['user_id']]);
        $subordinadosIds = $stmtSubordinados->fetchAll(PDO::FETCH_COLUMN);
        
        // También obtener empleados por catalogos_mandos
        $stmtEmpName = $pdo->prepare("SELECT e.nombre, e.apellido, u.nombre_completo FROM empleados e INNER JOIN usuarios u ON e.id = u.empleado_id WHERE u.id = ?");
        $stmtEmpName->execute([$_SESSION['user_id']]);
        $empName = $stmtEmpName->fetch(PDO::FETCH_ASSOC);
        if ($empName) {
            $nombresMando = [trim($empName['apellido'] . ' ' . $empName['nombre'])];
            if (!empty($empName['nombre_completo']) && !in_array($empName['nombre_completo'], $nombresMando)) {
                $nombresMando[] = $empName['nombre_completo'];
            }
            $placeholders = implode(',', array_fill(0, count($nombresMando), '?'));
            $stmtMando = $pdo->prepare("
                SELECT id, clave_area, area FROM catalogos_mandos 
                WHERE nombre_mando IN ($placeholders) AND activo = 1
            ");
            $stmtMando->execute($nombresMando);
            $rowsMando = $stmtMando->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rowsMando)) {
                $clavesArea = array_column($rowsMando, 'id');
                $areasMando = array_column($rowsMando, 'area');

                $subPorClave = [];
                $subPorArea = [];

                if (!empty($clavesArea)) {
                    $placeholders = implode(',', array_fill(0, count($clavesArea), '?'));
                    $stmtPorClave = $pdo->prepare("
                        SELECT id FROM empleados WHERE jefe_directo_clave IN ($placeholders) AND activo = 1
                    ");
                    $stmtPorClave->execute($clavesArea);
                    $subPorClave = $stmtPorClave->fetchAll(PDO::FETCH_COLUMN);
                }

                if (!empty($areasMando)) {
                    $placeholdersArea = implode(',', array_fill(0, count($areasMando), '?'));
                    $stmtPorArea = $pdo->prepare("
                        SELECT id FROM empleados WHERE area IN ($placeholdersArea) AND activo = 1
                    ");
                    $stmtPorArea->execute($areasMando);
                    $subPorArea = $stmtPorArea->fetchAll(PDO::FETCH_COLUMN);
                }

                $subordinadosIds = array_merge($subordinadosIds, $subPorClave, $subPorArea);
                $subordinadosIds = array_values(array_unique($subordinadosIds));
            }
        }
        
        if (empty($subordinadosIds)) {
            $this->jsonResponse([
                'success' => true,
                'incidencias' => [],
                'total' => 0,
                'message' => 'No tienes subordinados asignados.'
            ]);
            return;
        }
        
        // Filtrar solo los empleados seleccionados que también son subordinados del jefe
        $subordinadosIdsStr = array_map('strval', $subordinadosIds);
        $empleadosIds = array_values(array_filter(
            array_map('strval', $empleadosIds),
            fn($id) => in_array($id, $subordinadosIdsStr, true)
        ));
        
        // Si no hay empleados seleccionados o no son subordinados, devolver vacío
        if (empty($empleadosIds)) {
            $this->jsonResponse([
                'success' => true,
                'incidencias' => [],
                'total' => 0,
                'message' => 'No hay empleados seleccionados o no tienes permisos sobre ellos.'
            ]);
            return;
        }
        
        try {
            $placeholders = '(' . implode(',', array_fill(0, count($empleadosIds), '?')) . ')';
            
            // Consulta para RETARDOS CON solicitud (ya justificados o con motivo)
            $sqlRetardosConSolicitud = "
                SELECT 'retardos' as origen,
                       r.empleado_id,
                       r.id as id,
                       r.tipo_retraso as tipo_incidencia,
                       r.fecha as fecha_incidencia,
                       r.hora_entrada as hora_entrada_real,
                       r.hora_salida as hora_salida_real,
                       COALESCE(a.hora_entrada, r.hora_entrada) as hora_entrada,
                       COALESCE(a.hora_salida, r.hora_salida) as hora_salida,
                       eh.hora_entrada as horario_entrada,
                       eh.hora_salida as horario_salida,
                       COALESCE(r.dia_semana, DAYNAME(r.fecha)) as dia_semana,
                       r.minutos_retardo,
                       r.justificado,
                       r.motivo_justificacion as descripcion_incidencia,
                       r.requiere_validacion_jefe,
                       r.evidencia_adjunta,
                       r.estado_validacion as estado_validacion_jefe,
                       r.requiere_validacion_jefe as prioridad_atencion,
                       e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                       CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                       e.area as empleado_area, e.area_fisica as empleado_area_fisica, e.jerarquia as empleado_jerarquia,
                       0 as sin_solicitud
                FROM retardos r
                INNER JOIN empleados e ON r.empleado_id = e.id
                LEFT JOIN asistencia a ON r.empleado_id = a.empleado_id AND r.fecha = a.fecha
                LEFT JOIN empleado_horarios eh ON r.empleado_id = eh.empleado_id 
                    AND r.fecha BETWEEN eh.fecha_inicio AND COALESCE(eh.fecha_fin, CURDATE())
                WHERE r.empleado_id IN $placeholders
                AND (r.motivo_justificacion IS NOT NULL AND r.motivo_justificacion != '')
            ";
            
            // Consulta para RETARDOS SIN SOLICITUD (no han justificado ni tienen motivo)
            $sqlRetardosSinSolicitud = "
                SELECT 'retardos_sin_solicitud' as origen,
                       r.empleado_id,
                       r.id as id,
                       COALESCE(r.tipo_retraso, 'retardo') as tipo_incidencia,
                       r.fecha as fecha_incidencia,
                       r.hora_entrada as hora_entrada_real,
                       r.hora_salida as hora_salida_real,
                       COALESCE(a.hora_entrada, r.hora_entrada) as hora_entrada,
                       COALESCE(a.hora_salida, r.hora_salida) as hora_salida,
                       eh.hora_entrada as horario_entrada,
                       eh.hora_salida as horario_salida,
                       COALESCE(r.dia_semana, DAYNAME(r.fecha)) as dia_semana,
                       r.minutos_retardo,
                       r.justificado,
                       NULL as descripcion_incidencia,
                       1 as requiere_validacion_jefe,
                       NULL as evidencia_adjunta,
                       'sin_solicitud' as estado_validacion_jefe,
                       1 as prioridad_atencion,
                       e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                       CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                       e.area as empleado_area, e.area_fisica as empleado_area_fisica, e.jerarquia as empleado_jerarquia,
                       1 as sin_solicitud
                FROM retardos r
                INNER JOIN empleados e ON r.empleado_id = e.id
                LEFT JOIN asistencia a ON r.empleado_id = a.empleado_id AND r.fecha = a.fecha
                LEFT JOIN empleado_horarios eh ON r.empleado_id = eh.empleado_id 
                    AND r.fecha BETWEEN eh.fecha_inicio AND COALESCE(eh.fecha_fin, CURDATE())
                WHERE r.empleado_id IN $placeholders
                AND (r.motivo_justificacion IS NULL OR r.motivo_justificacion = '')
            ";
            
            // Consulta para ASISTENCIA (no normal, no por_definir)
            $sqlAsistencia = "
                SELECT 'asistencia' as origen,
                       asis.empleado_id,
                       asis.id as id,
                       asis.tipo_asistencia as tipo_incidencia,
                       asis.fecha as fecha_incidencia,
                       asis.hora_entrada as hora_entrada_real,
                       asis.hora_salida as hora_salida_real,
                       asis.hora_entrada,
                       asis.hora_salida,
                       NULL as horario_entrada,
                       NULL as horario_salida,
                       DAYNAME(asis.fecha) as dia_semana,
                       NULL as minutos_retardo,
                       NULL as justificado,
                       asis.metadata_dispositivo as descripcion_incidencia,
                       asis.requiere_validacion_jefe,
                       NULL as evidencia_adjunta,
                       asis.estado_validacion as estado_validacion_jefe,
                       CASE asis.tipo_asistencia 
                            WHEN 'con_retardo' THEN 1
                            WHEN 'con_ausencia' THEN 1
                            WHEN 'sin_registro' THEN 1
                            WHEN 'licencia_medica' THEN 0
                            WHEN 'dia_economico' THEN 0
                            WHEN 'comision_entrada' THEN 0
                            WHEN 'comision_salida' THEN 0
                            WHEN 'comision_todo_dia' THEN 0
                            WHEN 'vacaciones' THEN 0
                            ELSE 1
                       END as prioridad_atencion,
                       e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                       CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                       e.area as empleado_area, e.area_fisica as empleado_area_fisica, e.jerarquia as empleado_jerarquia,
                       0 as sin_solicitud
                FROM asistencia asis
                INNER JOIN empleados e ON asis.empleado_id = e.id
                WHERE asis.empleado_id IN $placeholders
                AND asis.tipo_asistencia NOT IN ('normal', 'por_definir')
            ";
            
            // UNIR todas las consultas
            $sql = "
                SELECT * FROM (
                    $sqlRetardosConSolicitud 
                    UNION ALL 
                    $sqlRetardosSinSolicitud 
                    UNION ALL 
                    $sqlAsistencia
                ) as incidencias
                WHERE 1=1
            ";
            
            // Parameters: empleadosIds (para retardos con solicitud), empleadosIds (para retardos sin solicitud), empleadosIds (para asistencia)
            $params = array_merge(
                $empleadosIds, 
                $empleadosIds, 
                $empleadosIds
            );
            
            // Filtrar solo pendientes si se solicita
            if ($soloPendientes) {
                $sql .= " AND (
                    incidencias.estado_validacion_jefe IS NULL 
                    OR incidencias.estado_validacion_jefe = '' 
                    OR incidencias.estado_validacion_jefe LIKE 'pendiente%'
                    OR incidencias.estado_validacion_jefe LIKE '%_info'
                    OR incidencias.estado_validacion_jefe = 'sin_solicitud'
                    OR incidencias.requiere_validacion_jefe = 1
                )";
            }
            
            $sql .= " ORDER BY incidencias.sin_solicitud DESC, incidencias.fecha_incidencia DESC";
            
            // Paginación
            $pagina = max(1, intval($_POST['pagina'] ?? 1));
            $limite = min(100, max(1, intval($_POST['limite'] ?? 25)));
            
            // Obtener total
            $sqlCount = "SELECT COUNT(*) as total FROM (" . $sql . ") as subconsulta";
            $stmtCount = $pdo->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Aplicar paginación
            $offset = ($pagina - 1) * $limite;
            $sql .= " LIMIT $limite OFFSET $offset";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agregar descripción amigable y marcar las sin solicitud
            foreach ($incidencias as &$inc) {
                if ($inc['sin_solicitud']) {
                    $inc['descripcion_incidencia'] = '⚠️ Sin solicitud de justificación - El empleado no ha justificado';
                    $inc['tipo_sin_solicitud'] = true;
                } else {
                    $inc['descripcion_incidencia'] = $this->getDescripcionIncidencia($inc);
                    $inc['tipo_sin_solicitud'] = false;
                }
            }
            
            // Si NO es solo pendientes, incluir también registros de validaciones_jefe (aprobados/rechazados)
            if (!$soloPendientes) {
                $placeholdersVj = '(' . implode(',', array_fill(0, count($empleadosIds), '?')) . ')';
                $sqlVj = "SELECT 
                    'validacion_jefe' as origen,
                    v.empleado_id,
                    v.id as id,
                    v.tipo_incidencia,
                    COALESCE(v.fecha_validacion, v.fecha_solicitud) as fecha_incidencia,
                    NULL as hora_entrada_real,
                    NULL as hora_entrada,
                    NULL as hora_salida,
                    NULL as horario_entrada,
                    NULL as horario_salida,
                    DAYNAME(COALESCE(v.fecha_validacion, v.fecha_solicitud)) as dia_semana,
                    NULL as minutos_retardo,
                    NULL as justificado,
                    COALESCE(v.motivo_validacion, v.comentarios_adicionales) as descripcion_incidencia,
                    0 as requiere_validacion_jefe,
                    NULL as evidencia_adjunta,
                    v.estado as estado_validacion_jefe,
                    0 as prioridad_atencion,
                    e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                    CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                    e.area as empleado_area, e.area_fisica as empleado_area_fisica, e.jerarquia as empleado_jerarquia,
                    0 as sin_solicitud,
                    v.incidencia_id,
                    v.fecha_solicitud,
                    v.motivo_validacion,
                    v.comentarios_adicionales
                FROM validaciones_jefe v
                INNER JOIN empleados e ON v.empleado_id = e.id
                WHERE v.empleado_id IN $placeholdersVj
                AND v.estado IN ('aprobado', 'rechazado')";
                
                $stmtVj = $pdo->prepare($sqlVj);
                $stmtVj->execute($empleadosIds);
                $validacionesJefe = $stmtVj->fetchAll(PDO::FETCH_ASSOC);
                
                // Agregar descripción amigable
                foreach ($validacionesJefe as &$vj) {
                    $vj['descripcion_incidencia'] = $this->getDescripcionIncidencia($vj) ?: ($vj['descripcion_incidencia'] ?? '');
                    $vj['tipo_sin_solicitud'] = false;
                }
                
                // Combinar
                $incidencias = array_merge($incidencias, $validacionesJefe);
                
                // Reordenar: sin_solicitud DESC, fecha_incidencia DESC
                usort($incidencias, function($a, $b) {
                    $aSin = intval($a['sin_solicitud'] ?? 0);
                    $bSin = intval($b['sin_solicitud'] ?? 0);
                    if ($aSin !== $bSin) return $bSin - $aSin;
                    return strcmp($b['fecha_incidencia'] ?? '', $a['fecha_incidencia'] ?? '');
                });
                
                $total = count($incidencias);
            }
            
            $this->jsonResponse([
                'success' => true,
                'incidencias' => $incidencias,
                'total' => $total,
                'pagina' => $pagina,
                'limite' => $limite
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'incidenciasEmpleados']);
            $this->jsonResponse(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Obtener descripción amigable para una incidencia
     */
    private function getDescripcionIncidencia($inc) {
        $tipo = $inc['tipo_incidencia'] ?? '';
        $minutos = $inc['minutos_retardo'] ?? 0;
        
        switch ($tipo) {
            case 'retardo_menor':
                return "Retardo menor: $minutos min";
            case 'retardo_mayor':
                return "Retardo mayor: $minutos min";
            case 'comision':
            case 'comision_entrada':
                return 'Comisión de entrada';
            case 'comision_salida':
                return 'Comisión de salida';
            case 'comision_todo_dia':
                return 'Comisión todo el día';
            case 'dia_economico':
                return 'Día económico';
            case 'ausencia':
                return 'Ausencia';
            case 'licencia_medica':
                return 'Licencia médica';
            case 'vacaciones':
                return 'Vacaciones';
            case 'con_retardo':
                return 'Entrada con retardo';
            case 'con_ausencia':
                return 'Ausencia registrada';
            case 'sin_registro':
                return 'Sin registro de entrada/salida';
            case 'cuidados_parentales':
                return 'Cuidados parentales';
            case 'falta':
                return 'Falta';
            case 'constancia_tiempo':
                return 'Constancia de tiempo';
            case 'justificacion':
                return 'Justificación';
            case 'dia_economico':
                return 'Día económico';
            case 'ausencia':
                return 'Ausencia';
            case 'vacaciones':
                return 'Vacaciones';
            case 'licencia_medica':
                return 'Licencia médica';
            default:
                return $inc['descripcion_incidencia'] ?? 'Incidencia';
        }
    }
    
    /**
     * API para descargar reporte de validaciones
     */
    public function descargarReporte() {
        if (!$this->esJefeTemporal()) {
            return;
        }
        
        $jefeId = $this->getEmpleadoIdDelUsuarioActual() ?? 1;
        $formato = $_GET['formato'] ?? 'pdf';
        $periodo = [
            'inicio' => $_GET['fecha_inicio'] ?? date('Y-m-01'),
            'fin' => $_GET['fecha_fin'] ?? date('Y-m-t')
        ];
        
        // Aquí se implementaría la generación del reporte
        // Por ahora, solo devolvemos los datos en formato JSON
        $validaciones = $this->validacionModel->getPendientesPorJefe($jefeId, [
            'fecha_inicio' => $periodo['inicio'],
            'fecha_fin' => $periodo['fin']
        ]);
        
        if ($formato === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="reporte_validaciones.xls"');
            // Implementar generación Excel
        } elseif ($formato === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="reporte_validaciones.pdf"');
            // Implementar generación PDF
        }
        
        $this->jsonResponse([
            'success' => true,
            'data' => $validaciones,
            'periodo' => $periodo
        ]);
    }
    
    /**
     * Métodos privados de ayuda
     */
    
    private function esJefe() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $rol = $_SESSION['rol'] ?? '';
        
        // Permitir acceso a jefe, admin, superadmin
        return in_array($rol, ['jefe', 'admin', 'superadmin']);
    }
    
    private function getEmpleadoIdDelUsuarioActual(): ?int {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $pdo = Database::getInstance()->getConnection();
        $stmt_usuario = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
        $stmt_usuario->execute([$_SESSION['user_id']]);
        $empleadoId = $stmt_usuario->fetchColumn();

        return $empleadoId ? (int)$empleadoId : null;
    }
    
    private function getEmpleadosACargo($jefeId, $query = '') {
        $pdo = Database::getInstance()->getConnection();
        
        // Obtener empleados por jefe_directo_id (asignación directa)
        $sql = "SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                       e.area, e.jerarquia,
                       'directo' as tipo_asignacion
                FROM empleados e 
                WHERE e.jefe_directo_id = ? AND e.activo = 1
                AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.area LIKE ?)";
        
        $parametros = [
            $jefeId, 
            "%{$query}%", 
            "%{$query}%", 
            "%{$query}%"
        ];
        
        // También buscar empleados por jefe_directo_clave en catalogos_mandos
        $stmtEmpName = $pdo->prepare("SELECT nombre, apellido FROM empleados WHERE id = ?");
        $stmtEmpName->execute([$jefeId]);
        $empName = $stmtEmpName->fetch(PDO::FETCH_ASSOC);
        
        $stmtUserInfo = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE empleado_id = ? AND activo = 1 LIMIT 1");
        $stmtUserInfo->execute([$jefeId]);
        $userName = $stmtUserInfo->fetchColumn();
        
        $nombresMando = [];
        if ($empName) {
            $nombresMando[] = trim($empName['apellido'] . ' ' . $empName['nombre']);
        }
        if ($userName && !in_array($userName, $nombresMando)) {
            $nombresMando[] = $userName;
        }
        
        if (!empty($nombresMando)) {
            $placeholders = implode(',', array_fill(0, count($nombresMando), '?'));
            $stmtMando = $pdo->prepare("
                SELECT id, clave_area, area FROM catalogos_mandos 
                WHERE nombre_mando IN ($placeholders) AND activo = 1
            ");
            $stmtMando->execute($nombresMando);
            $rowsMando = $stmtMando->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rowsMando)) {
                $clavesArea = array_column($rowsMando, 'id');
                $areasMando = array_column($rowsMando, 'area');
                $partesUnion = [];

                if (!empty($clavesArea)) {
                    $placeholders = implode(',', array_fill(0, count($clavesArea), '?'));
                    $partesUnion[] = "
                        SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                               e.area, e.jerarquia,
                               'mando' as tipo_asignacion
                        FROM empleados e 
                        WHERE e.jefe_directo_clave IN ($placeholders) AND e.activo = 1
                        AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.area LIKE ?)";
                    $parametros = array_merge($parametros, $clavesArea, ["%{$query}%", "%{$query}%", "%{$query}%"]);
                }

                if (!empty($areasMando)) {
                    $placeholdersArea = implode(',', array_fill(0, count($areasMando), '?'));
                    $partesUnion[] = "
                        SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                               e.area, e.jerarquia,
                               'mando' as tipo_asignacion
                        FROM empleados e 
                        WHERE e.area IN ($placeholdersArea) AND e.activo = 1
                        AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.area LIKE ?)";
                    $parametros = array_merge($parametros, $areasMando, ["%{$query}%", "%{$query}%", "%{$query}%"]);
                }

                if (!empty($partesUnion)) {
                    $sql .= " UNION " . implode(' UNION ', $partesUnion);
                }
            }
        }
        
        $sql .= " ORDER BY nombre_completo";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si un jefe está autorizado para un empleado 
     * (por jefe_directo_id directo o por catalogos_mandos)
     */
    private function esJefeAutorizadoParaEmpleado($jefeId, $empleadoId): bool {
        $pdo = Database::getInstance()->getConnection();
        
        // Verificar por jefe_directo_id directo
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empleados WHERE id = ? AND jefe_directo_id = ?");
        $stmt->execute([$empleadoId, $jefeId]);
        if ($stmt->fetchColumn() > 0) return true;
        
        // Verificar por catalogos_mandos (jefe_directo_clave y area)
        $stmtEmpName = $pdo->prepare("SELECT nombre, apellido FROM empleados WHERE id = ?");
        $stmtEmpName->execute([$jefeId]);
        $empName = $stmtEmpName->fetch(PDO::FETCH_ASSOC);
        if (!$empName) return false;
        
        $stmtUserInfo = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE empleado_id = ? AND activo = 1 LIMIT 1");
        $stmtUserInfo->execute([$jefeId]);
        $userName = $stmtUserInfo->fetchColumn();
        
        $nombresMando = [trim($empName['apellido'] . ' ' . $empName['nombre'])];
        if ($userName && !in_array($userName, $nombresMando)) {
            $nombresMando[] = $userName;
        }
        
        $placeholders = implode(',', array_fill(0, count($nombresMando), '?'));
        $stmtMando = $pdo->prepare("
            SELECT cm.id, cm.clave_area, cm.area FROM catalogos_mandos cm 
            WHERE cm.nombre_mando IN ($placeholders) AND cm.activo = 1
        ");
        $stmtMando->execute($nombresMando);
        $rowsMando = $stmtMando->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rowsMando)) return false;
        
        $clavesArea = array_column($rowsMando, 'id');
        $areasMando = array_column($rowsMando, 'area');
        
        $checks = [];
        $params = [$empleadoId];
        
        if (!empty($clavesArea)) {
            $placeholders = implode(',', array_fill(0, count($clavesArea), '?'));
            $checks[] = "jefe_directo_clave IN ($placeholders)";
            $params = array_merge($params, $clavesArea);
        }
        
        if (!empty($areasMando)) {
            $placeholdersArea = implode(',', array_fill(0, count($areasMando), '?'));
            $checks[] = "area IN ($placeholdersArea)";
            $params = array_merge($params, $areasMando);
        }
        
        if (empty($checks)) return false;
        
        $sql = "SELECT COUNT(*) FROM empleados WHERE id = ? AND (" . implode(' OR ', $checks) . ")";
        $stmtEmp = $pdo->prepare($sql);
        $stmtEmp->execute($params);
        return $stmtEmp->fetchColumn() > 0;
    }
    
    /**
     * API para obtener áreas disponibles para jefes
     */
    public function getAreas() {
        if (!$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        // Obtener empleado_id del jefe
        $pdo = Database::getInstance()->getConnection();
        $stmt_usuario = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
        $stmt_usuario->execute([$_SESSION['user_id'] ?? 1]);
        $empleado_id_jefe = $stmt_usuario->fetchColumn();
        
        $areas = $this->getAreasDisponibles($empleado_id_jefe);
        
        $this->jsonResponse([
            'success' => true,
            'areas' => $areas
        ]);
    }
    
    private function getAreasDisponibles($jefeId) {
        $pdo = Database::getInstance()->getConnection();
        
        $sql = "SELECT DISTINCT e.area 
                FROM empleados e 
                WHERE e.jefe_directo_id = ? AND e.area IS NOT NULL";
        
        $parametros = [$jefeId];
        
        // También incluir áreas de empleados por catalogos_mandos
        $stmtEmp = $pdo->prepare("SELECT nombre, apellido FROM empleados WHERE id = ?");
        $stmtEmp->execute([$jefeId]);
        $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
        
        $stmtUserInfo = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE empleado_id = ? AND activo = 1 LIMIT 1");
        $stmtUserInfo->execute([$jefeId]);
        $userName = $stmtUserInfo->fetchColumn();
        
        $nombresMando = [];
        if ($emp) {
            $nombresMando[] = trim($emp['apellido'] . ' ' . $emp['nombre']);
        }
        if ($userName && !in_array($userName, $nombresMando)) {
            $nombresMando[] = $userName;
        }
        
        if (!empty($nombresMando)) {
            $placeholders = implode(',', array_fill(0, count($nombresMando), '?'));
            $stmtMando = $pdo->prepare("
                SELECT id, clave_area, area FROM catalogos_mandos 
                WHERE nombre_mando IN ($placeholders) AND activo = 1
            ");
            $stmtMando->execute($nombresMando);
            $rowsMando = $stmtMando->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rowsMando)) {
                $clavesArea = array_column($rowsMando, 'id');
                $areasMando = array_column($rowsMando, 'area');
                $partesUnion = [];

                if (!empty($clavesArea)) {
                    $placeholders = implode(',', array_fill(0, count($clavesArea), '?'));
                    $partesUnion[] = "
                        SELECT DISTINCT e.area 
                        FROM empleados e 
                        WHERE e.jefe_directo_clave IN ($placeholders) AND e.area IS NOT NULL";
                    $parametros = array_merge($parametros, $clavesArea);
                }

                if (!empty($areasMando)) {
                    $placeholdersArea = implode(',', array_fill(0, count($areasMando), '?'));
                    $partesUnion[] = "
                        SELECT DISTINCT e.area 
                        FROM empleados e 
                        WHERE e.area IN ($placeholdersArea) AND e.area IS NOT NULL";
                    $parametros = array_merge($parametros, $areasMando);
                }

                if (!empty($partesUnion)) {
                    $sql = "
                        SELECT DISTINCT area FROM (
                            ($sql)
                            UNION
                        " . implode(' UNION ', $partesUnion) . "
                        ) areas ORDER BY area
                    ";
                }
            }
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getDetallesIncidencia($validacion) {
        $pdo = Database::getInstance()->getConnection();
        $incidenciaId = $validacion['incidencia_id'];
        
        switch ($validacion['tipo_incidencia']) {
            case 'retardo':
                $sql = "SELECT r.*, 
                               COALESCE(asis.hora_entrada, r.hora_entrada) as hora_entrada, 
                               COALESCE(asis.hora_salida, r.hora_salida) as hora_salida, 
                               asis.fecha as fecha_asistencia,
                               asis.metadata_dispositivo,
                               r.evidencia_adjunta,
                               r.motivo_justificacion,
                               CASE WHEN r.dia_semana IS NOT NULL AND r.dia_semana != '' THEN r.dia_semana 
                                    ELSE DAYNAME(r.fecha) END as dia_semana
                        FROM retardos r
                        LEFT JOIN asistencia asis ON r.empleado_id = asis.empleado_id 
                                                   AND r.fecha = asis.fecha
                        WHERE r.id = ?";
                break;
                
            case 'comision':
                // Primero verificar si el ID existe en retardos (comisiones que vienen de retardos)
                $stmtCheck = $pdo->prepare("SELECT id FROM retardos WHERE id = ? AND tipo_retraso IN ('comision_entrada', 'comision_salida', 'comision_todo_dia')");
                $stmtCheck->execute([$incidenciaId]);
                $esRetardo = $stmtCheck->fetch();
                
                if ($esRetardo) {
                    // Viene de un retardo, obtener datos de ahí
                    $sql = "SELECT r.*, 
                                   COALESCE(asis.hora_entrada, r.hora_entrada) as hora_entrada, 
                                   COALESCE(asis.hora_salida, r.hora_salida) as hora_salida, 
                                   asis.fecha as fecha_asistencia,
                                   r.evidencia_adjunta,
                                   r.motivo_justificacion,
                                   r.tipo_retraso as tipo_comision,
                                   CASE WHEN r.dia_semana IS NOT NULL AND r.dia_semana != '' THEN r.dia_semana 
                                        ELSE DAYNAME(r.fecha) END as dia_semana
                            FROM retardos r
                            LEFT JOIN asistencia asis ON r.empleado_id = asis.empleado_id 
                                                       AND r.fecha = asis.fecha
                            WHERE r.id = ?";
                } else {
                    // Es una comisión normal de la tabla comisiones
                    $sql = "SELECT c.*, 
                                   asis.hora_entrada, asis.hora_salida, 
                                   asis.fecha as fecha_asistencia,
                                   c.evidencia_adjunta,
                                   c.motivo_aprobacion as motivo_justificacion,
                                   c.descripcion,
                                   DAYNAME(c.fecha_asignacion) as dia_semana
                            FROM comisiones c
                            LEFT JOIN asistencia asis ON c.empleado_id = asis.empleado_id 
                                                       AND c.fecha_asignacion = asis.fecha
                            WHERE c.id = ?";
                }
                break;
                
            case 'dia_economico':
                $sql = "SELECT de.*, 
                               asis.hora_entrada, asis.hora_salida, 
                               asis.fecha as fecha_asistencia,
                               de.evidencia_adjunta,
                               de.motivo_justificacion,
                               DAYNAME(de.fecha) as dia_semana
                        FROM dias_economicos de
                        LEFT JOIN asistencia asis ON de.empleado_id = asis.empleado_id 
                                                   AND de.fecha = asis.fecha
                        WHERE de.id = ?";
                break;
                
            case 'ausencia':
                $sql = "SELECT a.*, 
                               asis.hora_entrada, asis.hora_salida, 
                               asis.fecha as fecha_asistencia,
                               a.evidencia_adjunta,
                               a.motivo_justificacion,
                               DAYNAME(a.fecha_inicio) as dia_semana
                        FROM ausencias a
                        LEFT JOIN asistencia asis ON a.empleado_id = asis.empleado_id 
                                                   AND a.fecha_inicio = asis.fecha
                        WHERE a.id = ?";
                break;
                
            default:
                return null;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$validacion['incidencia_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Normalizar tipo_retraso a tipo si es un retardo
        if ($row && $validacion['tipo_incidencia'] === 'retardo' && !isset($row['tipo']) && isset($row['tipo_retraso'])) {
            $row['tipo'] = $row['tipo_retraso'];
        }
        
        return $row;
    }
    
    private function getReglasAplicables($tipoIncidencia) {
        $pdo = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM reglas_validacion 
                WHERE tipo_incidencia = ? AND activa = TRUE 
                ORDER BY nombre_regla";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tipoIncidencia]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function calcularTiempoPromedioRespuesta($jefeId) {
        $pdo = Database::getInstance()->getConnection();
        
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_solicitud, fecha_validacion)) as promedio_horas
                FROM validaciones_jefe 
                WHERE jefe_id = ? AND estado IN ('aprobado', 'rechazado') 
                AND fecha_validacion IS NOT NULL";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jefeId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado ? round(floatval($resultado['promedio_horas']), 2) : 0;
    }
    
    /**
     * Notificar al empleado sobre la decisión del jefe
     * Crea una alerta en alertas_tempranas + push notification + auditoría
     * Versión mejorada con trazabilidad completa y consecuencias
     */
    private function notificarEmpleado($validacionId, $decision) {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Obtener datos de la validación
            $stmtValidacion = $pdo->prepare("
                SELECT v.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                       u.nombre_completo as jefe_nombre
                FROM validaciones_jefe v
                INNER JOIN empleados e ON v.empleado_id = e.id
                LEFT JOIN usuarios u ON v.jefe_id = u.empleado_id
                WHERE v.id = ?
            ");
            $stmtValidacion->execute([$validacionId]);
            $validacion = $stmtValidacion->fetch(PDO::FETCH_ASSOC);
            
            if (!$validacion) {
                error_log("Notificación: Validación $validacionId no encontrada");
                return false;
            }
            
            $estado = $decision['estado'];
            $empleadoId = $validacion['empleado_id'];
            $jefeNombre = $validacion['jefe_nombre'] ?? 'Su jefe directo';
            $tipoIncidencia = $validacion['tipo_incidencia'];
            $incidenciaId = $validacion['incidencia_id'];
            $estadoAnterior = $validacion['estado'] ?? 'pendiente';
            
            // Determinar tipo y nivel de alerta según la decisión
            switch ($estado) {
                case 'aprobado':
                    $tipoAlerta = 'informativa';
                    $nivel = 'baja';
                    $mensaje = "✅ <strong>Aprobado</strong>: Tu justificación ha sido aprobada por $jefeNombre.";
                    
                    // Agregar comentario si existe
                    if (!empty($decision['comentarios'])) {
                        $mensaje .= " <br><em>Comentario: {$decision['comentarios']}</em>";
                    }
                    if (!empty($decision['motivo'])) {
                        $mensaje .= " <br><em>Motivo de aprobación: {$decision['motivo']}</em>";
                    }
                    $mensaje .= " <br><small class='text-success'>✓ La incidencia ha sido justificada correctamente. No se generará nota mala ni falta.</small>";
                    break;
                    
                case 'rechazado':
                    $tipoAlerta = 'advertencia';
                    $nivel = 'alta';
                    
                    // Obtener minutos del retardo para determinar consecuencia
                    $minutosRetardo = 0;
                    if ($tipoIncidencia === 'retardo') {
                        $stmtRetardo = $pdo->prepare("SELECT minutos_retardo FROM retardos WHERE id = ?");
                        $stmtRetardo->execute([$incidenciaId]);
                        $retardo = $stmtRetardo->fetch(PDO::FETCH_ASSOC);
                        $minutosRetardo = (int)($retardo['minutos_retardo'] ?? 0);
                    }
                    
                    // Determinar la consecuencia según los minutos (según documento Lógica de Incidencias)
                    if ($minutosRetardo > 30) {
                        $consecuencia = 'falta';
                        $consecuenciaTexto = 'CONSECUENCIA: Esta incidencia ha sido convertida en <strong>FALTA</strong> (>30 min de retraso) y será reportada a Recursos Humanos.';
                    } elseif ($minutosRetardo > 20) {
                        $consecuencia = 'nota_mala';
                        $consecuenciaTexto = 'CONSECUENCIA: Se ha generado una <strong>NOTA MALA</strong> por retardo mayor rechazado (21-30 min). Recuerde: 1 retardo mayor = 1 nota mala.';
                    } elseif ($minutosRetardo > 10) {
                        $consecuencia = 'nota_mala';
                        $consecuenciaTexto = 'CONSECUENCIA: Se ha generado una <strong>NOTA MALA</strong> por retardo menor rechazado (11-20 min). Recuerde: 2 retardos menores = 1 nota mala.';
                    } else {
                        $consecuencia = 'tolerancia';
                        $consecuenciaTexto = 'CONSECUENCIA: Esta incidencia está dentro del tiempo de tolerancia (<=10 min). No se genera nota mala.';
                    }
                    
                    $mensaje = "⚠️ <strong>Rechazado</strong>: Tu justificación ha sido rechazada por $jefeNombre.";
                    
                    if (!empty($decision['motivo'])) {
                        $mensaje .= " <br><strong>Motivo del rechazo:</strong> {$decision['motivo']}";
                    }
                    if (!empty($decision['comentarios'])) {
                        $mensaje .= " <br><em>Comentario: {$decision['comentarios']}</em>";
                    }
                    $mensaje .= " <br><small class='text-danger'>⚠️ {$consecuenciaTexto}</small>";
                    $mensaje .= " <br><small class='text-muted'>Por favor, consulta con tu jefe directo o Recursos Humanos para más información.</small>";
                    break;
                    
                case 'requiere_info':
                    $tipoAlerta = 'precaucion';
                    $nivel = 'media';
                    $mensaje = "📋 <strong>Información requerida</strong>: $jefeNombre solicita información adicional sobre tu justificación.";
                    
                    if (!empty($decision['motivo'])) {
                        $mensaje .= " <br><strong>Información solicitada:</strong> {$decision['motivo']}";
                    }
                    if (!empty($decision['comentarios'])) {
                        $mensaje .= " <br><em>Comentario: {$decision['comentarios']}</em>";
                    }
                    $mensaje .= " <br><small>Por favor, proporciona la información solicitada a la brevedad para continuar con la validación.</small>";
                    break;
                    
                default:
                    $mensaje = "Tu justificación ha sido procesada por $jefeNombre. Estado: $estado";
                    $tipoAlerta = 'informativa';
                    $nivel = 'baja';
            }
            
            // Agregar referencia a la incidencia
            $labelMap = [
                'retardo' => 'Retardo',
                'comision' => 'Comisión',
                'dia_economico' => 'Día Económico',
                'ausencia' => 'Ausencia',
                'constancia_tiempo' => 'Constancia de Tiempo',
                'licencia_medica' => 'Licencia Médica',
                'justificacion' => 'Justificación',
            ];
            $tipoIncidenciaLabel = $labelMap[$tipoIncidencia] ?? ucfirst(str_replace('_', ' ', $tipoIncidencia));
            $mensaje .= " <br><small class='text-muted'>Incidencia ID: {$incidenciaId} | Tipo: {$tipoIncidenciaLabel}</small>";
            
            // Insertar alerta para el empleado
            $stmtAlerta = $pdo->prepare("
                INSERT INTO alertas_tempranas (empleado_id, tipo, nivel, mensaje, datos_json, leida, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $datosJson = json_encode([
                'validacion_id' => $validacionId,
                'incidencia_id' => $incidenciaId,
                'tipo_incidencia' => $tipoIncidencia,
                'estado' => $estado,
                'jefe_id' => $validacion['jefe_id'],
                'jefe_nombre' => $jefeNombre,
                'motivo' => $decision['motivo'] ?? null,
                'comentarios' => $decision['comentarios'] ?? null,
                'consecuencia' => $consecuencia ?? null,
                'consecuencia_texto' => $consecuenciaTexto ?? null,
                'fecha_validacion' => date('Y-m-d H:i:s')
            ]);
            
            $stmtAlerta->execute([
                $empleadoId,
                $tipoAlerta,
                $nivel,
                $mensaje,
                $datosJson
            ]);
            
            // === CREAR MENSAJE EN LA CONVERSACIÓN ===
            $tipoMensaje = match($estado) {
                'aprobado' => 'decision',
                'rechazado' => 'decision',
                'requiere_info' => 'info_request',
                default => 'sistema'
            };
            
            $textoMensaje = !empty($decision['comentarios']) ? $decision['comentarios'] : strip_tags($mensaje);
            $this->validacionModel->agregarMensaje(
                $validacionId,
                'jefe',
                $_SESSION['user_id'],
                $textoMensaje,
                $tipoMensaje
            );
            
            // === AUDITORÍA ===
            require_once __DIR__ . '/../services/AuditService.php';
            $audit = AuditService::getInstance();
            $audit->logValidacionJefe($validacionId, $estadoAnterior, $estado, $empleadoId, $decision['comentarios'] ?? null);
            
            // === PUSH NOTIFICATION ===
            require_once __DIR__ . '/../services/PushNotificationService.php';
            $push = PushNotificationService::getInstance();
            $push->notificarValidacion($empleadoId, $estado, $tipoIncidencia, strip_tags($mensaje));
            
            error_log("Notificación creada para empleado $empleadoId: Estado=$estado, Consecuencia=$consecuencia, Validación=$validacionId");
            return true;
            
        } catch (Exception $e) {
            error_log("Error al notificar empleado: " . $e->getMessage());
            return false;
        }
    }

    // ========================================================================
    // PANEL DE VALIDACIONES PARA EL EMPLEADO
    // ========================================================================

    /**
     * Renderizar el panel de validaciones del empleado
     */
    public function misValidaciones() {
        $this->requireAuth();
        
        $pdo = Database::getInstance()->getConnection();
        
        // Obtener el empleado_id del usuario actual
        $stmtUser = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
        $stmtUser->execute([$_SESSION['user_id']]);
        $empleadoId = $stmtUser->fetchColumn();
        
        $filters = [
            'estado' => $_GET['estado'] ?? 'pendientes',
            'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
            'fecha_fin' => $_GET['fecha_fin'] ?? null
        ];
        
        $validaciones = [];
        $contadorNoLeidas = 0;
        $empleado = null;
        
        if ($empleadoId) {
            $validaciones = $this->validacionModel->getValidacionesEmpleado($empleadoId, $filters);
            $contadorNoLeidas = $this->validacionModel->getContadorNoLeidasEmpleado($empleadoId);
            
            $stmtEmp = $pdo->prepare("SELECT CONCAT(nombre, ' ', apellido) as nombre_completo, area, jerarquia FROM empleados WHERE id = ?");
            $stmtEmp->execute([$empleadoId]);
            $empleado = $stmtEmp->fetch(PDO::FETCH_ASSOC);
        }
        
        ob_start();
        include 'views/validaciones/mis_validaciones.php';
        $content = ob_get_clean();
        include 'views/layout.php';
    }

    /**
     * API: Obtener mensajes de una validación
     */
    public function apiObtenerMensajes($validacionId) {
        if (ob_get_length()) ob_clean();
        
        $rol = $_SESSION['rol'] ?? '';
        $esJefe = in_array($rol, ['jefe', 'admin', 'superadmin']);
        
        if (!$esJefe && !$this->esEmpleadoAutorizado($validacionId)) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        try {
            // Resolver: puede ser validaciones_jefe.id o incidencia_id (retardo.id)
            $resolvedId = $validacionId;
            $pdo = Database::getInstance()->getConnection();
            
            // Intentar como validaciones_jefe.id primero
            $validacion = $this->validacionModel->getById($validacionId);
            
            if (!$validacion) {
                // No encontrado por id, buscar por incidencia_id
                $stmtVj = $pdo->prepare("SELECT id FROM validaciones_jefe WHERE incidencia_id = ? LIMIT 1");
                $stmtVj->execute([$validacionId]);
                $vjId = $stmtVj->fetchColumn();
                if ($vjId) {
                    $resolvedId = $vjId;
                    $validacion = $this->validacionModel->getById($resolvedId);
                }
            }
            
            $mensajes = $this->validacionModel->obtenerMensajes($resolvedId);
            $adjuntos = $this->validacionModel->getAdjuntosPorValidacion($resolvedId);
            
            $this->jsonResponse([
                'success' => true,
                'mensajes' => $mensajes,
                'adjuntos' => $adjuntos,
                'validacion' => $validacion
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Agregar mensaje a una validación
     */
    public function apiAgregarMensaje() {
        if (ob_get_length()) ob_clean();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $validacionId = $input['validacion_id'] ?? null;
        $mensaje = trim($input['mensaje'] ?? '');
        
        if (!$validacionId || !$mensaje) {
            $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
            return;
        }
        
        // Determinar quién envía
        $rol = $_SESSION['rol'] ?? '';
        $remitenteTipo = in_array($rol, ['jefe', 'admin', 'superadmin']) ? 'jefe' : 'empleado';
        
        // Verificar autorización
        if ($remitenteTipo === 'jefe' && !$this->esJefeTemporal()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            return;
        }
        
        if ($remitenteTipo === 'empleado') {
            $validacion = $this->validacionModel->getById($validacionId);
            if (!$validacion) {
                $this->jsonResponse(['success' => false, 'error' => 'Validación no encontrada'], 404);
                return;
            }
            // Obtener empleado_id del usuario
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $empleadoId = $stmt->fetchColumn();
            if ($validacion['empleado_id'] != $empleadoId) {
                $this->jsonResponse(['success' => false, 'error' => 'No autorizado para esta validación'], 403);
                return;
            }
        }
        
        try {
            $tipoMensaje = $input['tipo_mensaje'] ?? 
                          ($remitenteTipo === 'jefe' ? 'info_request' : 'info_response');
            
            $msgId = $this->validacionModel->agregarMensaje(
                $validacionId,
                $remitenteTipo,
                $_SESSION['user_id'],
                $mensaje,
                $tipoMensaje
            );
            
            // Si el empleado responde, cambiar estado de la validación
            if ($remitenteTipo === 'empleado' && $tipoMensaje === 'info_response') {
                $pdo = Database::getInstance()->getConnection();
                $stmtUpd = $pdo->prepare("UPDATE validaciones_jefe SET estado = 'pendiente' WHERE id = ? AND estado = 'requiere_info'");
                $stmtUpd->execute([$validacionId]);
            }
            
            $this->jsonResponse([
                'success' => true,
                'mensaje_id' => $msgId
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener contador de notificaciones no leídas
     */
    public function apiContadorNoLeidas() {
        if (ob_get_length()) ob_clean();
        
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $empleadoId = $stmt->fetchColumn();
        
        if (!$empleadoId) {
            $this->jsonResponse(['success' => true, 'no_leidas' => 0]);
            return;
        }
        
        $noLeidas = $this->validacionModel->getContadorNoLeidasEmpleado($empleadoId);
        
        $this->jsonResponse([
            'success' => true,
            'no_leidas' => $noLeidas
        ]);
    }

    /**
     * API: Marcar validación como leída
     */
    public function apiMarcarLeido() {
        if (ob_get_length()) ob_clean();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $validacionId = $input['validacion_id'] ?? null;
        
        if (!$validacionId) {
            $this->jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
            return;
        }
        
        $rol = $_SESSION['rol'] ?? '';
        $tipo = in_array($rol, ['jefe', 'admin', 'superadmin']) ? 'jefe' : 'empleado';
        
        $this->validacionModel->marcarComoLeido($validacionId, $tipo);
        
        $this->jsonResponse(['success' => true]);
    }

    /**
     * Verificar si el empleado actual está autorizado para ver una validación
     */
    private function esEmpleadoAutorizado($validacionId) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $empleadoId = $stmt->fetchColumn();
        
        if (!$empleadoId) return false;
        
        $stmtVal = $pdo->prepare("SELECT empleado_id FROM validaciones_jefe WHERE id = ?");
        $stmtVal->execute([$validacionId]);
        $validacionEmpleadoId = $stmtVal->fetchColumn();
        
        return $validacionEmpleadoId && $validacionEmpleadoId == $empleadoId;
    }
}
