<?php
require_once 'models/Retardo.php';
require_once 'models/Usuario.php';
require_once __DIR__ . '/BaseController.php';

class SoporteController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }
    
    /**
     * Determina si un usuario puede acceder a un archivo de soporte.
     * Retorna true si es admin, si es el empleado propietario del retardo, o si es el aprobador.
     */
    public function canAccessSoporte($userId, $filename, $pdo = null) {
        if (!$userId) return false;

        // If a PDO is provided (e.g. during tests inside a transaction), use it so uncommitted
        // rows are visible. Otherwise fall back to Usuario model which creates its own connection.
        $user = false;
        if ($pdo instanceof \PDO) {
            $s = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $s->execute([$userId]);
            $user = $s->fetch();
        } else {
            $usuarioModel = new Usuario();
            $user = $usuarioModel->getById($userId);
        }
        if (!$user) return false;

        // Admins can always view
        if (!empty($user['rol']) && $user['rol'] === 'admin') return true;
        // Try to find any record in the DB that references this soporte (search all tables with a 'soporte' column)
        $found = $this->findRecordBySoporte($filename, $pdo);
        if (!$found) return false;

        $table = $found['table'];
        $row = $found['row'];

        // Prefer explicit empleado_id relationship
        if (isset($row['empleado_id']) && !empty($user['empleado_id'])) {
            if ($user['empleado_id'] == $row['empleado_id']) return true;
        }

        // If the record has an aprobado_por field, allow the approver
        if (isset($row['aprobado_por']) && !empty($user['id']) && $user['id'] == $row['aprobado_por']) {
            return true;
        }

        // Heuristic: if the record contains any column that ends with '_id', try to infer relationship
        foreach ($row as $col => $val) {
            if (substr($col, -3) === '_id' && $col !== 'aprobado_por') {
                // If it's an empleado relation we already covered; for other relations deny unless admin
                if ($col === 'dispositivo_id') {
                    // No direct mapping from usuario->dispositivo; only admins allowed
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Busca en todas las tablas de la base de datos que tengan una columna `soporte`
     * y devuelve la primera fila que coincida con el filename (LIKE '%filename').
     * Retorna ['table'=>..., 'row'=>array] o false si no encuentra nada.
     */
    private function findRecordBySoporte($filename, $pdo = null) {
        // Use provided PDO if available (so tests can see uncommitted data), otherwise create one
        if (!($pdo instanceof \PDO)) {
            $db = new \Database();
            $pdo = $db->getConnection();
        }

        // Buscar tablas que tengan la columna 'soporte' en INFORMATION_SCHEMA
        $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'soporte'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $tables = $stmt->fetchAll();

        $like = '%' . $filename;
        foreach ($tables as $t) {
            $table = $t['TABLE_NAME'];
            $q = "SELECT * FROM `$table` WHERE soporte LIKE ? LIMIT 1";
            $s2 = $pdo->prepare($q);
            $s2->execute([$like]);
            $row = $s2->fetch();
            if ($row) {
                return ['table' => $table, 'row' => $row];
            }
        }

        return false;
    }
    /**
     * Serve a soporte file for a retardo with access control.
     * URL pattern expected: /soportes/justificaciones/{filename}
     */
    public function serveJustificacion($filename) {
        // Basic session check
        // Usamos el método del BaseController
        $this->requireAuth();

        // Sanitize filename (prevent traversal)
        $safeName = basename($filename);
        $filePath = __DIR__ . '/../uploads/justificaciones/' . $safeName;

        if (!file_exists($filePath) || !is_file($filePath)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }

        // Authorization
        $userId = $_SESSION['user_id'];
        if (!$this->canAccessSoporte($userId, $safeName)) {
            http_response_code(403);
            echo 'No autorizado para descargar este soporte';
            return;
        }

        // Audit log of download
        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir)) mkdir($logDir, 0750, true);
        $logFile = $logDir . 'soporte_access.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $line = sprintf("%s | user_id=%s | username=%s | ip=%s | file=%s | retardo_id=%s | allowed=1\n",
            date('c'), $user['id'] ?? 'unknown', $user['username'] ?? 'unknown', $ip, $safeName, $ret['id'] ?? 'none'
        );
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

        // Serve file with headers
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Prefer inline for PDF/images, otherwise force download
        $inlineTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $disposition = in_array($mime, $inlineTypes) ? 'inline' : 'attachment';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
    }

    /**
     * Servir archivos subidos generales
     */
    public function serveUpload($filepath) {
        $this->requireAuth();
        
        // Prevenir directory traversal
        if (strpos($filepath, '..') !== false) {
            http_response_code(403);
            echo 'Acceso denegado';
            return;
        }
        
        $fullPath = __DIR__ . '/../uploads/' . $filepath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            $mime = mime_content_type($fullPath);
            header('Content-Type: ' . $mime);
            readfile($fullPath);
        } else {
            http_response_code(404);
            echo 'Archivo no encontrado';
        }
    }
}
