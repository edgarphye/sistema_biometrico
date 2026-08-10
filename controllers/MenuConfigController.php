<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/BaseController.php';

class MenuConfigController extends BaseController {
    private $pdo;
    
    public function __construct() {
        parent::__construct();
        $this->pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    }
    
    private function tieneAcceso() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $rol = $_SESSION['rol'] ?? '';
        error_log("DEBUG tieneAcceso - rol: $rol");
        return in_array($rol, ['superadmin', 'admin']) || Usuario::tienePermiso('configurar_menu');
    }
    
    public function index() {
        if (!$this->tieneAcceso()) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        $usuarios = $this->getUsuarios();
        $menuItems = $this->getMenuItems();
        
        $usuarioId = $_GET['usuario_id'] ?? null;
        $configActual = [];
        
        if ($usuarioId) {
            $configActual = $this->getConfiguracion($usuarioId);
        }
        
        include 'views/menu_config/index.php';
    }
    
    private function getUsuarios() {
        $stmt = $this->pdo->query("SELECT id, username, nombre_completo, rol FROM usuarios WHERE activo = 1 ORDER BY username");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getMenuItems() {
        return [
            ['path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
            ['path' => '/', 'label' => 'Inicio', 'icon' => 'fa-home'],
            ['path' => '/empleados', 'label' => 'Empleados', 'icon' => 'fa-users'],
            ['path' => '/asistencia', 'label' => 'Asistencia', 'icon' => 'fa-clock'],
            ['path' => '/marcaciones', 'label' => 'Marcaciones', 'icon' => 'fa-stopwatch'],
            ['path' => '/mis-validaciones', 'label' => 'Mis Validaciones', 'icon' => 'fa-check-double'],
            ['path' => '/horarios', 'label' => 'Horarios', 'icon' => 'fa-calendar-alt'],
            ['path' => '/ciclos', 'label' => 'Ciclos', 'icon' => 'fa-sync'],
            ['path' => '/validaciones', 'label' => 'Validaciones', 'icon' => 'fa-user-check'],
            ['path' => '/reportes/excel', 'label' => 'Reportes Excel', 'icon' => 'fa-file-excel'],
            ['path' => '/resumen-justificaciones', 'label' => 'Resumen Justificaciones', 'icon' => 'fa-clipboard-check'],
            ['path' => '/analisis-predictivo', 'label' => 'Análisis Predictivo', 'icon' => 'fa-brain'],
            ['path' => '/agent-ia', 'label' => 'Agent IA', 'icon' => 'fa-network-wired'],
            ['path' => '/ai', 'label' => 'AI Dashboard', 'icon' => 'fa-robot'],
            ['path' => '/biometricos', 'label' => 'Dispositivos Biométricos', 'icon' => 'fa-fingerprint'],
            ['path' => '/biometricos/gestionar', 'label' => 'Gestionar Biométrico', 'icon' => 'fa-sliders-h'],
            ['path' => '/database', 'label' => 'Base de Datos', 'icon' => 'fa-database'],
            ['path' => '/catalogos', 'label' => 'Catálogos', 'icon' => 'fa-sitemap'],
            ['path' => '/permisos-menu', 'label' => 'Permisos de Menú', 'icon' => 'fa-shield-halved'],
            ['path' => '/configuracion', 'label' => 'Configuración', 'icon' => 'fa-gear'],
            ['path' => '/notas-malas', 'label' => 'Notas Malas', 'icon' => 'fa-exclamation-triangle'],
            ['path' => '/usuarios', 'label' => 'Usuarios', 'icon' => 'fa-user-cog'],
            ['path' => '/logs', 'label' => 'Logs de Errores', 'icon' => 'fa-file-alt'],
        ];
    }
    
    private function getConfiguracion($usuarioId) {
        $stmt = $this->pdo->prepare("SELECT menu_path, visible FROM menu_config WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $config = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $config[$row['menu_path']] = (bool)$row['visible'];
        }
        return $config;
    }
    
    public function guardar() {
        header('Content-Type: application/json');
        
        error_log("DEBUG guardar menu - tieneAcceso: " . ($this->tieneAcceso() ? 'si' : 'no'));
        
        if (!$this->tieneAcceso()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permiso']);
            return;
        }
        
        $usuarioId = $_POST['usuario_id'] ?? null;
        $menuConfig = $_POST['menu'] ?? [];
        
        if (!$usuarioId) {
            echo json_encode(['success' => false, 'message' => 'Usuario no especificado']);
            return;
        }
        
        $menuItems = $this->getMenuItems();
        
        try {
            $this->pdo->beginTransaction();
            
            $stmtDelete = $this->pdo->prepare("DELETE FROM menu_config WHERE usuario_id = ?");
            $stmtDelete->execute([$usuarioId]);
            
            $stmtInsert = $this->pdo->prepare("INSERT INTO menu_config (usuario_id, menu_path, visible) VALUES (?, ?, ?)");
            
            foreach ($menuItems as $item) {
                $path = $item['path'];
                $visible = isset($menuConfig[$path]) ? 1 : 0;
                $stmtInsert->execute([$usuarioId, $path, $visible]);
            }
            
            $this->pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Configuración guardada correctamente']);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function getConfig() {
        header('Content-Type: application/json');
        
        if (!$this->tieneAcceso()) {
            echo json_encode(['success' => false, 'message' => 'No tienes permiso']);
            return;
        }
        
        $usuarioId = $_GET['usuario_id'] ?? null;
        
        if (!$usuarioId) {
            echo json_encode(['success' => false, 'message' => 'Usuario no especificado']);
            return;
        }
        
        $config = $this->getConfiguracion($usuarioId);
        echo json_encode(['success' => true, 'config' => $config]);
    }
}
?>
