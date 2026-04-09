<?php
require_once __DIR__ . '/Database.php';

class AuditService {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Registrar acción en auditoría
     */
    public function log($accion, $modulo, $registroId = null, $datosAnteriores = null, $datosNuevos = null, $empleadoId = null) {
        try {
            $usuarioId = $_SESSION['user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs 
                (usuario_id, empleado_id, accion, modulo, registro_id, datos_anteriores, datos_nuevos, ip_address, user_agent, fecha)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $usuarioId,
                $empleadoId,
                $accion,
                $modulo,
                $registroId,
                $datosAnteriores ? json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE) : null,
                $datosNuevos ? json_encode($datosNuevos, JSON_UNESCAPED_UNICODE) : null,
                $ipAddress,
                $userAgent
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error en AuditService::log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar validación de jefe
     */
    public function logValidacionJefe($validacionId, $estadoAnterior, $estadoNuevo, $empleadoId, $comentarios = null) {
        return $this->log(
            'validacion_jefe',
            'validaciones',
            $validacionId,
            ['estado' => $estadoAnterior],
            ['estado' => $estadoNuevo, 'comentarios' => $comentarios],
            $empleadoId
        );
    }
    
    /**
     * Registrar nota mala generada
     */
    public function logNotaMala($notaMalaId, $empleadoId, $tipo, $motivo) {
        return $this->log(
            'nota_mala_generada',
            'notas_malas',
            $notaMalaId,
            null,
            ['tipo' => $tipo, 'motivo' => $motivo],
            $empleadoId
        );
    }
    
    /**
     * Registrar sanción aplicada
     */
    public function logSancion($sancionId, $empleadoId, $tipo, $descripcion) {
        return $this->log(
            'sancion_aplicada',
            'sanciones',
            $sancionId,
            null,
            ['tipo' => $tipo, 'descripcion' => $descripcion],
            $empleadoId
        );
    }
    
    /**
     * Obtener historial de auditoría por empleado
     */
    public function getHistorialEmpleado($empleadoId, $limite = 50) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM audit_logs 
            WHERE empleado_id = ?
            ORDER BY fecha DESC
            LIMIT ?
        ");
        $stmt->execute([$empleadoId, $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener auditoría por módulo
     */
    public function getHistorialModulo($modulo, $registroId, $limite = 20) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM audit_logs 
            WHERE modulo = ? AND registro_id = ?
            ORDER BY fecha DESC
            LIMIT ?
        ");
        $stmt->execute([$modulo, $registroId, $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
