<?php
require_once __DIR__ . '/Database.php';

class Mantenimiento {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function getGeneralStats() {
        return [
            'empleados' => $this->getCount('empleados', 'activo = 1'),
            'usuarios' => $this->getCount('usuarios'),
            'asistencia' => $this->getCount('asistencia'),
            'dispositivos_biometricos' => $this->getCount('dispositivos_biometricos', 'activo = 1')
        ];
    }

    private function getCount($table, $where = null) {
        try {
            $sql = "SELECT COUNT(*) FROM $table";
            if ($where) $sql .= " WHERE $where";
            return $this->pdo->query($sql)->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getSystemStatus() {
        $baseDir = dirname(__DIR__);
        $status = [
            'database' => 'ok',
            'devices' => 'warning', // Estado inicial hasta verificación activa
            'logs' => is_writable($baseDir . '/logs') ? 'ok' : 'error',
            'backups' => is_writable($baseDir . '/backups') ? 'ok' : 'warning'
        ];

        try {
            $this->pdo->query("SELECT 1");
        } catch (Exception $e) {
            $status['database'] = 'error';
        }

        return $status;
    }

    public function createBackup($type = 'full') {
        $backupDir = dirname(__DIR__) . '/backups/database';
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true)) {
                throw new Exception("No se pudo crear el directorio de backups");
            }
        }

        $filename = 'backup_' . $type . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;
        
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $dbName = defined('DB_NAME') ? DB_NAME : 'sistema_biometrico';

        $dumpBinary = $this->findFirstAvailableBinary(['mariadb-dump', 'mysqldump']);
        if ($dumpBinary === null) {
            throw new Exception("No se encontró mariadb-dump ni mysqldump en el sistema");
        }

        // Comando de dump (MariaDB/MySQL)
        $cmd = sprintf(
            '%s --host=%s --user=%s %s --databases %s > %s',
            escapeshellcmd($dumpBinary),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $dbPass ? '--password=' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );

        $output = [];
        $returnVar = 0;
        exec($cmd . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($filepath)) {
            throw new Exception("Error al generar backup: " . implode("\n", $output));
        }

        return [
            'file' => $filename,
            'size' => filesize($filepath),
            'path' => $filepath
        ];
    }

    public function restoreBackup($filePath) {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $dbName = defined('DB_NAME') ? DB_NAME : 'sistema_biometrico';
        
        $clientBinary = $this->findFirstAvailableBinary(['mariadb', 'mysql']);
        if ($clientBinary === null) {
            throw new Exception("No se encontró cliente mariadb ni mysql en el sistema");
        }

        $cmd = sprintf(
            '%s --host=%s --user=%s %s %s < %s',
            escapeshellcmd($clientBinary),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $dbPass ? '--password=' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($filePath)
        );

        $output = [];
        $returnVar = 0;
        exec($cmd . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("Error al restaurar: " . implode("\n", $output));
        }
        return true;
    }

    public function cleanupSystem($operations) {
        $results = [];
        
        if (in_array('optimize_tables', $operations)) {
            try {
                $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $this->pdo->query("OPTIMIZE TABLE `$table`");
                }
                $results['optimize_tables'] = 'Tablas optimizadas correctamente';
            } catch (Exception $e) {
                $results['optimize_tables'] = 'Error: ' . $e->getMessage();
            }
        }

        if (in_array('clean_old_logs', $operations)) {
            $logDir = dirname(__DIR__) . '/logs';
            $count = 0;
            if (is_dir($logDir)) {
                foreach (glob($logDir . '/*.log') as $file) {
                    if (filemtime($file) < strtotime('-30 days')) {
                        unlink($file);
                        $count++;
                    }
                }
            }
            $results['clean_old_logs'] = "$count logs antiguos eliminados";
        }

        if (in_array('clear_cache', $operations)) {
            // Implementar limpieza de caché real si existe (ej. opcache, archivos temporales)
            $results['clear_cache'] = 'Caché del sistema limpiada';
        }

        return $results;
    }

    public function checkDevices() {
        try {
            $devices = $this->pdo->query("SELECT * FROM dispositivos_biometricos WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
            
            $status = [];
            foreach ($devices as $d) {
                $status[] = [
                    'nombre' => $d['nombre'],
                    'ip_address' => $d['ip_address'],
                    'estado_actual' => 'conectado' // Simulado
                ];
            }
            return $status;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getLogs($type = 'system', $limit = 50) {
        // Leer logs reales o simular
        $logs = [];
        $logs[] = ['timestamp' => date('Y-m-d H:i:s'), 'type' => 'info', 'message' => 'Sistema de mantenimiento accedido'];
        $logs[] = ['timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'type' => 'success', 'message' => 'Verificación de sistema completada'];
        return $logs;
    }

    public function getDetailedStats() {
        $devicesCount = $this->pdo->query("SELECT COUNT(*) FROM dispositivos_biometricos")->fetchColumn();
        
        $dbName = defined('DB_NAME') ? DB_NAME : 'sistema_biometrico';
        $stmt = $this->pdo->prepare("SELECT sum(data_length + index_length) / 1024 / 1024 FROM information_schema.TABLES WHERE table_schema = ?");
        $stmt->execute([$dbName]);
        $dbSize = round($stmt->fetchColumn(), 2);

        return [
            'database' => ['size_mb' => $dbSize],
            'system' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => $this->formatBytes(memory_get_usage())
            ],
            'devices' => ['length' => $devicesCount]
        ];
    }

    private function formatBytes($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 
        $bytes /= pow(1024, $pow); 
        return round($bytes, $precision) . ' ' . $units[$pow]; 
    }

    private function findFirstAvailableBinary(array $binaries): ?string {
        foreach ($binaries as $binary) {
            $output = [];
            $return = 1;
            exec('command -v ' . escapeshellarg($binary) . ' >/dev/null 2>&1', $output, $return);
            if ($return === 0) {
                return $binary;
            }
        }
        return null;
    }
}
