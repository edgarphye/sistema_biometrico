<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Database.php';

class ConfiguracionController extends BaseController {
    
    public function index() {
        // Cargar la vista principal de configuración
        if (file_exists(__DIR__ . '/../views/configuracion/index.php')) {
            require __DIR__ . '/../views/configuracion/index.php';
        } else {
            // Fallback simple si no existe la vista
            echo "<h1>Configuración</h1><p>Vista no encontrada.</p>";
        }
    }

    public function getRecentHistory() {
        // Asegurar respuesta JSON limpia
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Verificar si la tabla existe antes de consultar
            try {
                $conn->query("SELECT 1 FROM zkteco_procesamiento_logs LIMIT 1");
            } catch (PDOException $e) {
                throw new Exception("La tabla de historial no existe. Ejecute 'fix_historial.php' primero.");
            }

            $sql = "SELECT id, dispositivo_id, fecha_ejecucion, registros_procesados, estado, detalles, created_at, sede, archivo_nombre, archivo_tamano, archivo_sha256 FROM zkteco_procesamiento_logs ORDER BY created_at DESC LIMIT 50";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $logs
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
