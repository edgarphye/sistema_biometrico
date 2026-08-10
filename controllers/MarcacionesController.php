<?php
require_once __DIR__ . '/BaseController.php';

class MarcacionesController extends BaseController {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index() {
        $viewFile = __DIR__ . '/../views/marcaciones/index.php';
        
        if (file_exists($viewFile)) {
            ob_start();
            require_once $viewFile;
            $content = ob_get_clean();
            
            if (file_exists(__DIR__ . '/../views/layout.php')) {
                require_once __DIR__ . '/../views/layout.php';
            }
        } else {
            echo "Vista no encontrada";
        }
    }
}