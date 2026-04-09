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
    
    protected function jsonResponse($data, $status = 200) {
        if (ob_get_length()) ob_clean();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
    
    protected function render($viewPath, $data = []) {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            error_log("Error: Vista no encontrada en $viewFile");
            http_response_code(500);
            echo "Error interno: Vista '$viewPath' no encontrada.";
        }
    }
}
