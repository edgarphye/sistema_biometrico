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
            error_log("Acceso denegado. Validación ID: $id pertenece al jefe {$validacion['jefe_id']}, usuario actual: $jefeId");
            $this->jsonResponse(['success' => false, 'error' => 'Validación no encontrada o no autorizado'], 404);
            return;
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
                $this->jsonResponse(['success' => false, 'error' => 'No tienes permiso'], 403);
                return;
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
            // Buscar en tabla retardos (usar método existente)
            return $this->validarPorRetardo($id);
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
            $this->jsonResponse(['success' => false, 'error' => 'No tienes permiso para ver este retardo'], 403);
            return;
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

        // Obtener subordinados del jefe actual con contadores separados de retardos e incidencias
        $sql = "
            SELECT DISTINCT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                   e.area, e.area_fisica, e.jerarquia,
                   'directo' as tipo_asignacion,
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
            WHERE e.jefe_directo_id = ?";
        
        $parametros = [$empleadoIdMando];
        
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

        // Obtener empleados a cargo del jefe actual
        $pdo = Database::getInstance()->getConnection();
        
        // Primero, obtener los IDs de empleados a cargo del jefe
        $stmtSubordinados = $pdo->prepare("
            SELECT e.id 
            FROM empleados e
            INNER JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
            WHERE u.id = ?
        ");
        $stmtSubordinados->execute([$_SESSION['user_id']]);
        $subordinadosIds = $stmtSubordinados->fetchAll(PDO::FETCH_COLUMN);
        
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
                INNER JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
                LEFT JOIN asistencia a ON r.empleado_id = a.empleado_id AND r.fecha = a.fecha
                LEFT JOIN empleado_horarios eh ON r.empleado_id = eh.empleado_id 
                    AND r.fecha BETWEEN eh.fecha_inicio AND COALESCE(eh.fecha_fin, CURDATE())
                WHERE r.empleado_id IN $placeholders
                AND u.id = ?
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
                INNER JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
                LEFT JOIN asistencia a ON r.empleado_id = a.empleado_id AND r.fecha = a.fecha
                LEFT JOIN empleado_horarios eh ON r.empleado_id = eh.empleado_id 
                    AND r.fecha BETWEEN eh.fecha_inicio AND COALESCE(eh.fecha_fin, CURDATE())
                WHERE r.empleado_id IN $placeholders
                AND u.id = ?
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
                INNER JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
                WHERE asis.empleado_id IN $placeholders
                AND u.id = ?
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
            
            // Parameters: empleadosIds (para retardos con solicitud), userId, empleadosIds (para retardos sin solicitud), userId, empleadosIds (para asistencia), userId
            $params = array_merge(
                $empleadosIds, [$_SESSION['user_id']], 
                $empleadosIds, [$_SESSION['user_id']], 
                $empleadosIds, [$_SESSION['user_id']]
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
        
        // Primero obtenemos el área del jefe
        $stmtJefe = $pdo->prepare("SELECT e.area FROM empleados e WHERE e.id = ?");
        $stmtJefe->execute([$jefeId]);
        $areaJefe = $stmtJefe->fetchColumn();
        
        // Obtener empleados SOLO por jefe_directo_id (asignación directa)
        $sql = "SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                       e.area, e.jerarquia,
                       'directo' as tipo_asignacion
                FROM empleados e 
                WHERE e.jefe_directo_id = ?
                AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.area LIKE ?)
                ORDER BY e.nombre";
        
        $parametros = [
            $jefeId, 
            "%{$query}%", 
            "%{$query}%", 
            "%{$query}%"
        ];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                WHERE e.jefe_directo_id = ? AND e.area IS NOT NULL
                ORDER BY e.area";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jefeId]);
        
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
                    // Tolerancia: 1-10 min (sin consecuencia)
                    // Retardo menor: 11-20 min (2 = 1 nota mala)
                    // Retardo mayor: 21-30 min (1 = 1 nota mala)
                    // >30 min: falta inmediata
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
                    
                    // Agregar motivo y comentarios del rechazo
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
                    
                    // Mostrar qué información se solicita
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
            $tipoIncidenciaLabel = ucfirst($tipoIncidencia);
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
}
