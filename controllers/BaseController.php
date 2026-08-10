<?php
/**
 * BaseController - Clase base para todos los controladores
 */
class BaseController {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    protected function redirect($url) {
        if (!headers_sent()) {
            header("Location: " . $url);
        } else {
            echo '<script>window.location.href="' . $url . '";</script>';
        }
        exit;
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado - Sesión requerida'], 401);
            exit;
        }
        return true;
    }
    
    protected function checkAdmin() {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'superadmin'])) {
            $_SESSION['error'] = 'Acceso denegado. Solo administradores pueden acceder.';
            $this->redirect('/dashboard');
            exit;
        }
        return true;
    }
    
    protected function checkRole($allowedRoles) {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $allowedRoles)) {
            $_SESSION['error'] = 'Acceso denegado.';
            $this->redirect('/dashboard');
            exit;
        }
        return true;
    }
    
    protected function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    protected function getCurrentUserRole() {
        return $_SESSION['rol'] ?? null;
    }
    
    protected function getCurrentEmpleadoId() {
        return $_SESSION['empleado_id'] ?? null;
    }
    
    protected function jsonResponse($data, $status = 200) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function render($viewPath, $data = []) {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        
        if (file_exists($viewFile)) {
            ob_start();
            require_once $viewFile;
            $content = ob_get_clean();
            
            $hasFullHtml = (strpos($content, '<!DOCTYPE html>') !== false || 
                           strpos($content, '<html') !== false || 
                           strpos($content, '<head>') !== false);
            
            if (!$hasFullHtml && file_exists(__DIR__ . '/../views/layout.php')) {
                require_once __DIR__ . '/../views/layout.php';
            } else {
                echo $content;
            }
        } else {
            error_log("Error: Vista no encontrada en $viewFile");
            http_response_code(500);
            echo "Error interno: Vista '$viewPath' no encontrada.";
        }
    }
    
    /**
     * Valida el tipo MIME real de un archivo usando finfo
     * @param array $file Elemento de $_FILES
     * @param array $allowedMimes Tipos MIME permitidos
     * @return bool True si el MIME es válido
     */
    protected function validateMime(array $file, array $allowedMimes): bool
    {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return false;
        }
        if (!file_exists($file['tmp_name'])) {
            return false;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return false;
        }
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        return in_array($mime, $allowedMimes, true);
    }

    protected function logException(Exception $e, $context = []) {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/php_errors_' . date('Y-m-d') . '.log';
        
        $contextInfo = '';
        if (!empty($context)) {
            $contextInfo = "\nContexto: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry = sprintf(
            "[%s] [EXCEPTION] %s%s\nMensaje: %s\nURI: %s\nArchivo: %s Línea: %d\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $contextInfo,
            $e->getMessage(),
            $_SERVER['REQUEST_URI'] ?? 'N/A',
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
            str_repeat('-', 80)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
