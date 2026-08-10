<?php
// HuellaController.php - Controlador para gestión de huellas dactilares

require_once __DIR__ . '/../classes/ZKTecoMB360.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/BaseController.php';

class HuellaController extends BaseController {
    private $db;
    private $conn;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Interfaz principal para gestión de huellas
     */
    public function index() {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Obtener dispositivos disponibles
        $dispositivos = $this->getDispositivos();
        
        // Obtener estadísticas de huellas
        $stats = $this->getHuellasStats();
        
        include __DIR__ . '/../views/huellas/index.php';
    }
    
    /**
     * Sincronizar huellas desde dispositivo ZKTeco
     */
    public function sincronizar() {
        try {
            $dispositivo_id = $_POST['dispositivo_id'] ?? 1;
            
            // Obtener configuración del dispositivo
            $dispositivo = $this->getDispositivo($dispositivo_id);
            
            if (!$dispositivo) {
                throw new Exception('Dispositivo no encontrado');
            }
            
            // Conectar al dispositivo
            $zk = new ZKTecoMB360($dispositivo['ip'], $dispositivo['puerto']);
            
            if (!$zk->connect()) {
                throw new Exception('No se pudo conectar al dispositivo');
            }
            
            // Sincronizar usuarios y huellas
            $result = $zk->syncAllUsers();
            
            // Desconectar
            $zk->disconnect();
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Sincronización completada exitosamente',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'index']);
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Enrolar huella de un empleado
     */
    public function enrolar() {
        try {
            $empleado_id = $_POST['empleado_id'] ?? null;
            $dispositivo_id = $_POST['dispositivo_id'] ?? 1;
            $indice_dedo = $_POST['indice_dedo'] ?? 0;
            
            if (!$empleado_id) {
                throw new Exception('ID de empleado requerido');
            }
            
            // Obtener información del empleado
            $empleado = $this->getEmpleado($empleado_id);
            
            if (!$empleado) {
                throw new Exception('Empleado no encontrado');
            }
            
            // Obtener configuración del dispositivo
            $dispositivo = $this->getDispositivo($dispositivo_id);
            
            if (!$dispositivo) {
                throw new Exception('Dispositivo no encontrado');
            }
            
            // Crear registro de proceso de enrolamiento
            $proceso_id = $this->crearProcesoEnrolamiento($empleado_id, $empleado['zk_empleado_id'], $dispositivo_id);
            
            // Conectar al dispositivo
            $zk = new ZKTecoMB360($dispositivo['ip'], $dispositivo['puerto']);
            
            if (!$zk->connect()) {
                throw new Exception('No se pudo conectar al dispositivo');
            }
            
            // Iniciar enrolamiento
            $result = $zk->enrollFingerprint($empleado['zk_empleado_id'], $indice_dedo);
            
            // Actualizar estado del proceso
            if ($result) {
                $this->actualizarProcesoEnrolamiento($proceso_id, 'capturando', 'Enrolamiento iniciado, esperando captura de huella...');
            } else {
                $this->actualizarProcesoEnrolamiento($proceso_id, 'error', 'Error al iniciar enrolamiento');
            }
            
            // Desconectar
            $zk->disconnect();
            
            $this->jsonResponse([
                'success' => $result,
                'message' => $result ? 'Proceso de enrolamiento iniciado' : 'Error al iniciar enrolamiento',
                'proceso_id' => $proceso_id
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'enrolar', 'empleado_id' => $empleado_id ?? null]);
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar estado del proceso de enrolamiento
     */
    public function verificarEstado() {
        try {
            $proceso_id = $_GET['proceso_id'] ?? null;
            
            if (!$proceso_id) {
                throw new Exception('ID de proceso requerido');
            }
            
            $estado = $this->getEstadoProceso($proceso_id);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $estado
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'verificarEstado', 'proceso_id' => $proceso_id ?? null]);
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener lista de empleados con sus huellas
     */
    public function listarEmpleados() {
        try {
            $empleados = $this->getEmpleadosConHuellas();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $empleados
            ]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'listarEmpleados']);
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar huella de un empleado
     */
    public function eliminar() {
        try {
            $huella_id = $_POST['huella_id'] ?? null;
            
            if (!$huella_id) {
                throw new Exception('ID de huella requerido');
            }
            
            // Eliminar de la base de datos
            $stmt = $this->conn->prepare("DELETE FROM huellas_empleados WHERE id = ?");
            $result = $stmt->execute([$huella_id]);
            
            if ($result) {
                // TODO: También eliminar del dispositivo ZKTeco
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Huella eliminada exitosamente'
                ]);
            } else {
                throw new Exception('Error al eliminar huella');
            }
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'eliminar', 'huella_id' => $huella_id ?? null, 'empleado_id' => $empleado_id ?? null]);
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener dispositivos disponibles
     */
    private function getDispositivos() {
        $stmt = $this->conn->prepare("
            SELECT * FROM dispositivos_biometricos 
            WHERE activo = 1 
            ORDER BY nombre
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener dispositivo específico
     */
    private function getDispositivo($dispositivo_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM dispositivos_biometricos 
            WHERE id = ? AND activo = 1
        ");
        $stmt->execute([$dispositivo_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener estadísticas de huellas
     */
    private function getHuellasStats() {
        $stats = [];
        
        // Total de empleados con huellas
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT empleado_id) as total 
            FROM huellas_empleados 
            WHERE estado = 'activo'
        ");
        $stmt->execute();
        $stats['empleados_con_huellas'] = $stmt->fetch()['total'];
        
        // Total de huellas registradas
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total 
            FROM huellas_empleados 
            WHERE estado = 'activo'
        ");
        $stmt->execute();
        $stats['total_huellas'] = $stmt->fetch()['total'];
        
        // Empleados sin huellas
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total 
            FROM empleados e 
            LEFT JOIN huellas_empleados h ON e.id = h.empleado_id AND h.estado = 'activo'
            WHERE e.activo = 1 AND h.empleado_id IS NULL
        ");
        $stmt->execute();
        $stats['empleados_sin_huellas'] = $stmt->fetch()['total'];
        
        return $stats;
    }
    
    /**
     * Obtener empleado
     */
    private function getEmpleado($empleado_id) {
        $stmt = $this->conn->prepare("
            SELECT e.*, z.zkteo_id 
            FROM empleados e 
            LEFT JOIN zkteo_empleado_mapeo z ON e.id = z.empleado_id 
            WHERE e.id = ? AND e.activo = 1
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear proceso de enrolamiento
     */
    private function crearProcesoEnrolamiento($empleado_id, $zk_empleado_id, $dispositivo_id) {
        $stmt = $this->conn->prepare("
            INSERT INTO proceso_enrolamiento 
            (empleado_id, zk_empleado_id, dispositivo_id, estado_proceso, fecha_inicio)
            VALUES (?, ?, ?, 'iniciado', NOW())
        ");
        $stmt->execute([$empleado_id, $zk_empleado_id, $dispositivo_id]);
        return $this->conn->lastInsertId();
    }
    
    /**
     * Actualizar estado del proceso de enrolamiento
     */
    private function actualizarProcesoEnrolamiento($proceso_id, $estado, $mensaje = null) {
        $stmt = $this->conn->prepare("
            UPDATE proceso_enrolamiento 
            SET estado_proceso = ?, mensaje_estado = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$estado, $mensaje, $proceso_id]);
    }
    
    /**
     * Obtener estado del proceso
     */
    private function getEstadoProceso($proceso_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM proceso_enrolamiento WHERE id = ?
        ");
        $stmt->execute([$proceso_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener empleados con sus huellas
     */
    private function getEmpleadosConHuellas() {
        $stmt = $this->conn->prepare("
            SELECT 
                e.id, e.nombre, e.apellido, e.rfc, e.area,
                COUNT(h.id) as num_huellas,
                GROUP_CONCAT(h.indice_huella ORDER BY h.indice_huella) as indices_huellas
            FROM empleados e
            LEFT JOIN huellas_empleados h ON e.id = h.empleado_id AND h.estado = 'activo'
            WHERE e.activo = 1
            GROUP BY e.id, e.nombre, e.apellido, e.rfc, e.area
            ORDER BY e.nombre, e.apellido
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>