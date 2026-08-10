<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../DBManager.php';

/**
 * Controlador de Base de Datos
 * Gestiona las operaciones de backup, restore y mantenimiento de la base de datos
 */
class DatabaseController extends BaseController {
    
    private ?DBManager $dbManager = null;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('database')) {
            $_SESSION['error'] = 'No tienes permiso para acceder a la base de datos.';
            $this->redirect('/dashboard');
            return;
        }
        
        // Inicializar DBManager
        $this->initDBManager();
    }
    
    private function initDBManager(): void {
        try {
            require_once __DIR__ . '/../config.php';
            $this->dbManager = new DBManager(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                __DIR__ . '/../backups/database_sql',
                __DIR__ . '/../logs/dbmanager.log'
            );
        } catch (Exception $e) {
            $this->logException($e, ['action' => '__construct', 'class' => 'DatabaseManager']);
            $this->dbManager = null;
        }
    }
    
    /**
     * Página principal de gestión de base de datos
     */
    public function index() {
        $stats = [];
        $tables = [];
        $backups = [];
        $foreignKeys = [];
        $error = null;
        $dbManager = $this->dbManager;
        
        try {
            if ($this->dbManager && $this->dbManager->databaseExists()) {
                // Obtener información de la base de datos
                $stats = [
                    'database_exists' => true,
                    'tables_count' => count($this->dbManager->getTablesInfo()),
                    'backups_count' => count($this->dbManager->listBackups()),
                    'foreign_keys_count' => count($this->dbManager->getForeignKeys())
                ];
                
                $tables = $this->dbManager->getTablesInfo();
                $backups = $this->dbManager->listBackups();
            } else {
                $stats = ['database_exists' => false];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        ob_start();
        include __DIR__ . '/../views/database/index.php';
        $content = ob_get_clean();
        
        require_once __DIR__ . '/../views/layout.php';
    }
    
    /**
     * Crear backup completo
     */
    public function backup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            if (!$this->dbManager) {
                throw new Exception('DBManager no inicializado');
            }
            
            $compress = isset($_POST['compress']) && $_POST['compress'] === '1';
            $result = $this->dbManager->fullBackup($compress);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Backup creado exitosamente',
                'data' => [
                    'filename' => $result['filename'],
                    'path' => $result['file'],
                    'size' => $result['size_formatted'],
                    'timestamp' => $result['timestamp']
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Restaurar base de datos
     */
    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            if (!$this->dbManager) {
                throw new Exception('DBManager no inicializado');
            }
            
            $filename = $_POST['filename'] ?? '';
            if (empty($filename)) {
                throw new Exception('Debe seleccionar un archivo de backup');
            }
            
            $filepath = $this->dbManager->resolveBackupFilePath($filename);
            
            if (!$filepath || !file_exists($filepath)) {
                throw new Exception('Archivo de backup no encontrado');
            }
            
            // Confirmar restauración
            if (!isset($_POST['confirm']) || $_POST['confirm'] !== '1') {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Debe confirmar la restauración',
                    'requires_confirmation' => true
                ]);
                return;
            }
            
            $result = $this->dbManager->smartRestore($filepath, true);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Restauración completada exitosamente',
                'data' => [
                    'tables_count' => $result['tables_count'],
                    'rows_count' => $result['rows_count'],
                    'execution_time' => $result['execution_time']
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener lista de backups (AJAX)
     */
    public function listBackups() {
        try {
            if (!$this->dbManager) {
                throw new Exception('DBManager no inicializado');
            }
            
            $backups = $this->dbManager->listBackups();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $backups
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener información de tablas (AJAX)
     */
    public function tables() {
        try {
            if (!$this->dbManager) {
                throw new Exception('DBManager no inicializado');
            }
            
            $tables = $this->dbManager->getTablesInfo();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $tables
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener información de llaves foráneas (AJAX)
     */
    public function foreignKeys() {
        try {
            if (!$this->dbManager) {
                throw new Exception('DBManager no inicializado');
            }
            
            $tableName = $_GET['table'] ?? null;
            $foreignKeys = $this->dbManager->getForeignKeys($tableName);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $foreignKeys
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Verificar estado de la base de datos (AJAX)
     */
    public function status() {
        try {
            if (!$this->dbManager) {
                throw new Exception('DBManager no inicializado');
            }
            
            $exists = $this->dbManager->databaseExists();
            $dumpBinary = $this->dbManager->getAvailableDumpBinary();
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'database_exists' => $exists,
                    'mysqldump_available' => $dumpBinary !== null, // compatibilidad
                    'dump_tool_available' => $dumpBinary !== null,
                    'dump_tool' => $dumpBinary,
                    'connection_ok' => $exists
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Eliminar un backup
     */
    public function deleteBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            $filename = $_POST['filename'] ?? '';
            if (empty($filename)) {
                throw new Exception('Debe especificar el archivo a eliminar');
            }
            
            $filepath = $this->dbManager->resolveBackupFilePath($filename);
            
            error_log("DEBUG deleteBackup: filename='$filename', filepath='$filepath'");
            
            if (!$filepath) {
                throw new Exception('No se pudo resolver la ruta del archivo: ' . $filename);
            }
            
            if (!file_exists($filepath)) {
                throw new Exception('Archivo no encontrado: ' . $filepath);
            }
            
            $perms = fileperms($filepath);
            $owner = fileowner($filepath);
            $group = filegroup($filepath);
            error_log("DEBUG file perms=" . decoct($perms) . " owner=$owner group=$group");
            
            if (@unlink($filepath)) {
                $this->jsonResponse(['success' => true, 'message' => 'Backup eliminado: ' . $filename]);
                return;
            }
            
            @chmod($filepath, 0777);
            if (@unlink($filepath)) {
                $this->jsonResponse(['success' => true, 'message' => 'Backup eliminado: ' . $filename]);
                return;
            }
            
            $output = [];
            $returnVar = 0;
            $cmd = "rm -f '" . $filepath . "' 2>&1; echo 'EXIT:' $?";
            $shellResult = shell_exec($cmd);
            error_log("DEBUG rm result: " . $shellResult);
            
            if (!file_exists($filepath)) {
                $this->jsonResponse(['success' => true, 'message' => 'Backup eliminado: ' . $filename]);
                return;
            }
            
            throw new Exception('No se pudo eliminar el archivo. Debug: ' . $shellResult);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
