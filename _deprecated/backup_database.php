<?php
/**
 * Sistema Biométrico - Script de Backup Completo de Base de Datos
 * 
 * Este script realiza backups completos o diferenciales de la base de datos
 * con compresión, rotación de archivos y validación de integridad.
 * 
 * Uso:
 * php backup_database.php                    # Backup completo
 * php backup_database.php --differential       # Backup diferencial
 * php backup_database.php --restore YYYYMMDD  # Restaurar backup específico
 * php backup_database.php --list              # Listar backups disponibles
 * php backup_database.php --cleanup           # Limpiar backups antiguos
 */

require_once 'config.php';
require_once 'models/Database.php';

class DatabaseBackup {
    private $config;
    private $backupDir;
    private $conn;
    private $logFile;
    
    public function __construct() {
        $this->config = require 'config.php';
        $this->backupDir = __DIR__ . '/backups/database_sql';
        $this->logFile = __DIR__ . '/logs/backup.log';
        
        // Crear directorios si no existen
        $this->ensureDirectoryExists($this->backupDir);
        $this->ensureDirectoryExists(dirname($this->logFile));
        
        // Conexión a base de datos
        $db = new Database();
        $this->conn = $db->getConnection();
    }
    
    /**
     * Realiza backup completo de la base de datos
     */
    public function backupFull() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_completo_{$timestamp}.sql";
        $filepath = "{$this->backupDir}/{$filename}";
        $compressedFile = "{$filepath}.gz";
        
        try {
            $this->log("Iniciando backup completo: {$filename}");
            
            // Obtener lista de tablas
            $tables = $this->getAllTables();
            
            // Generar archivo SQL
            $sql = $this->generateSQLBackup($tables);
            
            // Escribir archivo de backup
            if (file_put_contents($filepath, $sql) === false) {
                throw new Exception("No se pudo escribir el archivo de backup");
            }
            
            // Comprimir archivo
            $this->compressFile($filepath, $compressedFile);
            
            // Generar hash de integridad
            $hashFile = "{$compressedFile}.sha256";
            $hash = hash_file('sha256', $compressedFile);
            file_put_contents($hashFile, $hash);
            
            // Generar metadatos del backup
            $this->generateBackupMetadata($filename, $compressedFile, count($tables));
            
            // Limpiar archivo temporal sin comprimir
            unlink($filepath);
            
            $this->log("Backup completado exitosamente: {$compressedFile}");
            $this->log("Tamaño: " . $this->formatBytes(filesize($compressedFile)));
            $this->log("Hash SHA256: {$hash}");
            
            return [
                'success' => true,
                'file' => $compressedFile,
                'size' => filesize($compressedFile),
                'hash' => $hash,
                'tables' => count($tables)
            ];
            
        } catch (Exception $e) {
            $this->log("Error en backup: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Realiza backup diferencial (solo cambios desde último backup completo)
     */
    public function backupDifferential() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_diferencial_{$timestamp}.sql";
        $filepath = "{$this->backupDir}/{$filename}";
        $compressedFile = "{$filepath}.gz";
        
        try {
            $this->log("Iniciando backup diferencial: {$filename}");
            
            // Obtener último backup completo
            $lastFullBackup = $this->getLastFullBackup();
            if (!$lastFullBackup) {
                $this->log("No se encontró backup completo anterior, realizando backup completo");
                return $this->backupFull();
            }
            
            // Obtener timestamp del último backup
            $lastBackupTime = $this->getBackupTimestamp($lastFullBackup);
            
            // Generar SQL diferencial
            $sql = $this->generateDifferentialSQL($lastBackupTime);
            
            if (empty($sql)) {
                $this->log("No hay cambios desde el último backup completo");
                return ['success' => true, 'message' => 'No hay cambios que respaldar'];
            }
            
            // Escribir y comprimir
            file_put_contents($filepath, $sql);
            $this->compressFile($filepath, $compressedFile);
            
            // Generar hash
            $hashFile = "{$compressedFile}.sha256";
            $hash = hash_file('sha256', $compressedFile);
            file_put_contents($hashFile, $hash);
            
            // Generar metadatos
            $this->generateBackupMetadata($filename, $compressedFile, 0, 'diferencial', $lastBackupTime);
            
            unlink($filepath);
            
            $this->log("Backup diferencial completado: {$compressedFile}");
            $this->log("Tamaño: " . $this->formatBytes(filesize($compressedFile)));
            
            return [
                'success' => true,
                'file' => $compressedFile,
                'size' => filesize($compressedFile),
                'type' => 'diferencial'
            ];
            
        } catch (Exception $e) {
            $this->log("Error en backup diferencial: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Genera SQL completo de backup
     */
    private function generateSQLBackup($tables) {
        $sql = "-- =============================================================\n";
        $sql .= "-- Backup Completo de Base de Datos - Sistema Biométrico\n";
        $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Servidor: " . $this->conn->getAttribute(PDO::ATTR_SERVER_INFO) . "\n";
        $sql .= "-- Base de Datos: " . $this->config['database']['name'] . "\n";
        $sql .= "-- =============================================================\n\n";
        
        // Deshabilitar checks de clave foránea
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            $sql .= $this->generateTableSQL($table);
        }
        
        // Rehabilitar checks de clave foránea
        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        return $sql;
    }
    
    /**
     * Genera SQL para una tabla específica
     */
    private function generateTableSQL($table) {
        $sql = "-- -----------------------------------------------------\n";
        $sql .= "-- Estructura de tabla: {$table}\n";
        $sql .= "-- -----------------------------------------------------\n\n";
        
        // Obtener estructura CREATE TABLE
        $createTable = $this->getTableStructure($table);
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createTable . ";\n\n";
        
        // Obtener datos INSERT
        $inserts = $this->getTableData($table);
        if (!empty($inserts)) {
            $sql .= "-- Datos de tabla: {$table}\n";
            $sql .= $inserts . "\n";
        }
        
        $sql .= "\n";
        
        return $sql;
    }
    
    /**
     * Obtiene estructura CREATE TABLE de una tabla
     */
    private function getTableStructure($table) {
        $stmt = $this->conn->query("SHOW CREATE TABLE `{$table}`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['Create Table'];
    }
    
    /**
     * Obtiene datos INSERT de una tabla
     */
    private function getTableData($table) {
        $stmt = $this->conn->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            return '';
        }
        
        $sql = "INSERT INTO `{$table}` VALUES\n";
        $values = [];
        
        foreach ($rows as $row) {
            $escapedValues = array_map(function($value) {
                if ($value === null) {
                    return 'NULL';
                } elseif ($value === '') {
                    return "''";
                } else {
                    return "'" . addslashes($value) . "'";
                }
            }, $row);
            
            $values[] = "(" . implode(", ", $escapedValues) . ")";
        }
        
        return $sql . implode(",\n", $values) . ";\n";
    }
    
    /**
     * Genera SQL diferencial
     */
    private function generateDifferentialSQL($sinceTimestamp) {
        $sql = "-- =============================================================\n";
        $sql .= "-- Backup Diferencial - Sistema Biométrico\n";
        $sql .= "-- Desde: " . date('Y-m-d H:i:s', $sinceTimestamp) . "\n";
        $sql .= "-- Hasta: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- =============================================================\n\n";
        
        $tables = $this->getAllTables();
        $hasChanges = false;
        
        foreach ($tables as $table) {
            if ($this->hasTableChanges($table, $sinceTimestamp)) {
                $sql .= $this->generateTableSQL($table);
                $hasChanges = true;
            }
        }
        
        return $hasChanges ? $sql : '';
    }
    
    /**
     * Verifica si una tabla tiene cambios desde un timestamp
     */
    private function hasTableChanges($table, $sinceTimestamp) {
        try {
            // Buscar columnas de timestamp en la tabla
            $stmt = $this->conn->query("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '{$table}' 
                AND DATA_TYPE IN ('timestamp', 'datetime')
                AND COLUMN_NAME IN ('created_at', 'updated_at', 'fecha_creacion', 'fecha_modificacion', 'created_at')
            ");
            
            $timestampColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($timestampColumns)) {
                // Si no hay columnas de timestamp, incluir tabla
                return true;
            }
            
            // Verificar si hay registros con timestamp más reciente
            $timestampConditions = array_map(function($col) use ($sinceTimestamp) {
                return "`{$col}` > FROM_UNIXTIME({$sinceTimestamp})";
            }, $timestampColumns);
            
            $condition = implode(' OR ', $timestampConditions);
            $stmt = $this->conn->query("SELECT COUNT(*) FROM `{$table}` WHERE {$condition}");
            $count = $stmt->fetchColumn();
            
            return $count > 0;
            
        } catch (Exception $e) {
            // En caso de error, incluir tabla por seguridad
            return true;
        }
    }
    
    /**
     * Obtiene todas las tablas de la base de datos
     */
    private function getAllTables() {
        $stmt = $this->conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Excluir tablas de sistema si las hay
        $systemTables = ['mysql', 'information_schema', 'performance_schema', 'sys'];
        return array_filter($tables, function($table) use ($systemTables) {
            return !in_array($table, $systemTables);
        });
    }
    
    /**
     * Comprime un archivo usando GZIP
     */
    private function compressFile($source, $destination) {
        $data = file_get_contents($source);
        $compressed = gzencode($data, 9);
        
        if (file_put_contents($destination, $compressed) === false) {
            throw new Exception("No se pudo comprimir el archivo");
        }
    }
    
    /**
     * Genera metadatos del backup
     */
    private function generateBackupMetadata($filename, $filepath, $tableCount, $type = 'completo', $sinceTimestamp = null) {
        $metadata = [
            'filename' => $filename,
            'filepath' => $filepath,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s'),
            'size' => filesize($filepath),
            'tables' => $tableCount,
            'database' => $this->config['database']['name'],
            'server' => $this->conn->getAttribute(PDO::ATTR_SERVER_INFO),
            'since_timestamp' => $sinceTimestamp ?? null
        ];
        
        $metadataFile = str_replace('.gz', '.json', $filepath);
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    }
    
    /**
     * Lista todos los backups disponibles
     */
    public function listBackups() {
        $backups = [];
        $files = glob("{$this->backupDir}/*.gz");
        
        foreach ($files as $file) {
            $metadataFile = str_replace('.gz', '.json', $file);
            $metadata = [];
            
            if (file_exists($metadataFile)) {
                $metadata = json_decode(file_get_contents($metadataFile), true);
            }
            
            $backups[] = [
                'file' => basename($file),
                'size' => filesize($file),
                'created' => filemtime($file),
                'type' => $metadata['type'] ?? 'desconocido',
                'tables' => $metadata['tables'] ?? 0
            ];
        }
        
        // Ordenar por fecha (más reciente primero)
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }
    
    /**
     * Restaura un backup específico con opciones avanzadas
     */
    public function restore($backupDate, $options = []) {
        $backupFile = $this->findBackupFile($backupDate);
        
        if (!$backupFile) {
            throw new Exception("No se encontró backup para la fecha: {$backupDate}");
        }
        
        // Opciones por defecto
        $defaultOptions = [
            'force' => false,
            'backup_before_restore' => true,
            'verify_tables' => true,
            'dry_run' => false,
            'ignore_errors' => false
        ];
        $options = array_merge($defaultOptions, $options);
        
        try {
            $this->log("Iniciando restauración: {$backupFile}");
            
            // 1. Validar integridad del backup
            if (!$this->validateBackupIntegrity($backupFile)) {
                throw new Exception("El backup no pasó la validación de integridad SHA256");
            }
            
            // 2. Backup de seguridad antes de restaurar (opcional)
            if ($options['backup_before_restore'] && !$options['dry_run']) {
                $this->log("Creando backup de seguridad antes de restaurar...");
                $safetyBackup = $this->backupFull();
                $this->log("Backup de seguridad creado: {$safetyBackup['file']}");
            }
            
            // 3. Descomprimir backup
            $tempSqlFile = tempnam(sys_get_temp_dir(), 'backup_restore_');
            $this->decompressFile($backupFile, $tempSqlFile);
            
            // 4. Leer y analizar el SQL
            $sqlContent = file_get_contents($tempSqlFile);
            $this->analyzeBackupContent($sqlContent);
            
            // 5. Ejecutar restauración
            if ($options['dry_run']) {
                $this->log("MODO PRUEBA: Se ejecutarían " . $this->countStatements($sqlContent) . " statements SQL");
                return ['success' => true, 'message' => 'Modo prueba - Restauración simulada', 'statements' => $this->countStatements($sqlContent)];
            }
            
            $result = $this->executeRestoreSQL($sqlContent, $options);
            
            // 6. Verificar tablas restauradas
            if ($options['verify_tables']) {
                $verificationResult = $this->verifyRestoredTables();
                if (!$verificationResult['success']) {
                    throw new Exception("Verificación de tablas falló: " . $verificationResult['message']);
                }
            }
            
            // 7. Limpiar archivo temporal
            unlink($tempSqlFile);
            
            $this->log("Restauración completada exitosamente");
            return [
                'success' => true, 
                'message' => 'Base de datos restaurada exitosamente',
                'statements_executed' => $result['statements'],
                'tables_restored' => $verificationResult['tables'] ?? 0,
                'execution_time' => $result['execution_time'] ?? 0
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->log("Error en restauración: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Ejecuta los statements SQL del backup
     */
    private function executeRestoreSQL($sqlContent, $options) {
        $startTime = microtime(true);
        
        // Separar statements SQL de manera más segura
        $statements = $this->parseSQLStatements($sqlContent);
        
        $this->log("Iniciando transacción para restauración");
        $this->conn->beginTransaction();
        
        $executedStatements = 0;
        $errors = [];
        
        foreach ($statements as $index => $statement) {
            try {
                if (!empty(trim($statement))) {
                    // Ignorar comentarios específicos del backup
                    if (strpos($statement, '--') !== 0 && 
                        strpos($statement, '/*') !== 0 &&
                        trim($statement) !== '') {
                        
                        $this->conn->exec($statement);
                        $executedStatements++;
                        
                        // Log cada 100 statements para no spam
                        if ($executedStatements % 100 === 0) {
                            $this->log("Ejecutados {$executedStatements} statements...");
                        }
                    }
                }
            } catch (Exception $e) {
                $errorInfo = [
                    'statement' => substr($statement, 0, 100) . '...',
                    'error' => $e->getMessage(),
                    'index' => $index
                ];
                $errors[] = $errorInfo;
                
                if (!$options['ignore_errors']) {
                    throw new Exception("Error ejecutando statement #" . ($index + 1) . ": " . $e->getMessage());
                }
                
                $this->log("ERROR en statement #" . ($index + 1) . ": " . $e->getMessage(), 'WARNING');
            }
        }
        
        $this->conn->commit();
        $executionTime = round(microtime(true) - $startTime, 2);
        
        $this->log("Transacción completada. Statements ejecutados: {$executedStatements}");
        $this->log("Tiempo de ejecución: {$executionTime} segundos");
        
        if (!empty($errors)) {
            $this->log("Errores ignorados: " . count($errors), 'WARNING');
        }
        
        return [
            'statements' => $executedStatements,
            'errors' => count($errors),
            'execution_time' => $executionTime
        ];
    }
    
    /**
     * Parsea statements SQL de manera más segura
     */
    private function parseSQLStatements($sqlContent) {
        $statements = [];
        $currentStatement = '';
        $inString = false;
        $stringChar = '';
        $escapeNext = false;
        
        for ($i = 0; $i < strlen($sqlContent); $i++) {
            $char = $sqlContent[$i];
            
            // Manejar escape de caracteres
            if ($escapeNext) {
                $currentStatement .= $char;
                $escapeNext = false;
                continue;
            }
            
            // Detectar inicio/fin de strings
            if ($char === "'" || $char === '"') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = '';
                }
            } elseif ($char === '\\' && $inString) {
                $escapeNext = true;
            }
            
            // Detectar fin de statement solo si no estamos en un string
            if (!$inString && $char === ';') {
                $statement = trim($currentStatement);
                if (!empty($statement)) {
                    $statements[] = $statement;
                }
                $currentStatement = '';
            } else {
                $currentStatement .= $char;
            }
        }
        
        // Agregar el último statement si no termina con ;
        if (!empty(trim($currentStatement))) {
            $statements[] = trim($currentStatement);
        }
        
        return $statements;
    }
    
    /**
     * Analiza el contenido del backup
     */
    private function analyzeBackupContent($sqlContent) {
        $this->log("Analizando contenido del backup...");
        
        // Contar tablas
        $tableCount = substr_count($sqlContent, 'CREATE TABLE');
        $insertCount = substr_count($sqlContent, 'INSERT INTO');
        
        $this->log("Tablas encontradas: {$tableCount}");
        $this->log("Statements INSERT: {$insertCount}");
        
        // Verificar que sea un backup válido
        if ($tableCount === 0) {
            throw new Exception("El backup no contiene tablas válidas");
        }
        
        if (strpos($sqlContent, 'DROP TABLE') === false && strpos($sqlContent, 'DROP TABLE IF EXISTS') === false) {
            throw new Exception("El backup no contiene statements DROP necesarios para restauración segura");
        }
    }
    
    /**
     * Cuenta el número de statements SQL
     */
    private function countStatements($sqlContent) {
        $statements = $this->parseSQLStatements($sqlContent);
        return count($statements);
    }
    
    /**
     * Verifica que las tablas se restauraron correctamente
     */
    private function verifyRestoredTables() {
        try {
            // Obtener lista de tablas actuales
            $stmt = $this->conn->query("SHOW TABLES");
            $currentTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Tablas esperadas en el sistema
            $expectedTables = [
                'empleados', 'usuarios', 'asistencia', 'retardos', 'sanciones',
                'horarios_laborales', 'horarios_empleados', 'dispositivos_biometricos',
                'logs_dispositivos', 'justificaciones', 'comisiones', 'ausencias',
                'notificaciones_licencias', 'dias_economicos', 'tipos_justificacion',
                'ciclos', 'bloques_ciclo', 'empleados_ciclos'
            ];
            
            $missingTables = array_diff($expectedTables, $currentTables);
            $unexpectedTables = array_diff($currentTables, $expectedTables);
            
            if (!empty($missingTables)) {
                throw new Exception("Tablas faltantes: " . implode(', ', $missingTables));
            }
            
            if (!empty($unexpectedTables)) {
                $this->log("Tablas inesperadas: " . implode(', ', $unexpectedTables), 'WARNING');
            }
            
            // Verificar que las tablas no estén vacías (excepto las de logs)
            $essentialTables = ['empleados', 'usuarios'];
            foreach ($essentialTables as $table) {
                if (in_array($table, $currentTables)) {
                    $stmt = $this->conn->query("SELECT COUNT(*) FROM {$table}");
                    $count = $stmt->fetchColumn();
                    
                    if ($count === 0) {
                        $this->log("Tabla {$table} está vacía", 'WARNING');
                    } else {
                        $this->log("Tabla {$table}: {$count} registros");
                    }
                }
            }
            
            return [
                'success' => true,
                'tables' => count($currentTables),
                'message' => 'Verificación completada exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restauración completa con modo interactivo
     */
    public function fullRestoreInteractive($backupDate) {
        echo "\n=== RESTAURACIÓN COMPLETA INTERACTIVA ===\n";
        echo "Backup a restaurar: {$backupDate}\n";
        echo "¡ADVERTENCIA: Esta acción sobrescribirá toda la base de datos actual!\n\n";
        
        // Verificar backup
        $backupFile = $this->findBackupFile($backupDate);
        if (!$backupFile) {
            throw new Exception("No se encontró backup para la fecha: {$backupDate}");
        }
        
        // Mostrar información del backup
        $metadata = $this->getBackupMetadata($backupFile);
        echo "Información del backup:\n";
        echo "- Tipo: {$metadata['type']}\n";
        echo "- Fecha: {$metadata['created_at']}\n";
        echo "- Tamaño: " . number_format($metadata['size'] / 1024 / 1024, 2) . " MB\n";
        echo "- Tablas: {$metadata['tables']}\n\n";
        
        // Opciones de restauración
        echo "Opciones de restauración:\n";
        echo "1. Modo seguro (backup automático + verificación)\n";
        echo "2. Modo rápido (sin verificación)\n";
        echo "3. Modo prueba (simulación)\n";
        echo "4. Cancelar\n";
        echo "Seleccione una opción [1-4]: ";
        
        $handle = fopen("php://stdin", "r");
        $choice = trim(fgets($handle));
        fclose($handle);
        
        switch ($choice) {
            case '1':
                return $this->restore($backupDate, [
                    'backup_before_restore' => true,
                    'verify_tables' => true,
                    'dry_run' => false,
                    'ignore_errors' => false
                ]);
                
            case '2':
                echo "\n¡ATENCIÓN: Restauración rápida sin verificación!\n";
                echo "¿Continuar? [s/N]: ";
                $handle = fopen("php://stdin", "r");
                $confirm = strtolower(trim(fgets($handle)));
                fclose($handle);
                
                if ($confirm === 's') {
                    return $this->restore($backupDate, [
                        'backup_before_restore' => false,
                        'verify_tables' => false,
                        'dry_run' => false,
                        'ignore_errors' => true
                    ]);
                } else {
                    echo "Restauración cancelada.\n";
                    return ['success' => false, 'message' => 'Cancelado por usuario'];
                }
                
            case '3':
                return $this->restore($backupDate, [
                    'backup_before_restore' => false,
                    'verify_tables' => false,
                    'dry_run' => true,
                    'ignore_errors' => false
                ]);
                
            case '4':
                echo "Restauración cancelada.\n";
                return ['success' => false, 'message' => 'Cancelado por usuario'];
                
            default:
                echo "Opción inválida. Cancelando.\n";
                return ['success' => false, 'message' => 'Opción inválida'];
        }
    }
    
    /**
     * Obtiene metadatos de un backup
     */
    private function getBackupMetadata($backupFile) {
        $metadataFile = str_replace('.gz', '.json', $backupFile);
        
        if (file_exists($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true);
            return $metadata;
        }
        
        // Metadatos por defecto si no existe el archivo
        return [
            'type' => 'desconocido',
            'created_at' => date('Y-m-d H:i:s', filemtime($backupFile)),
            'size' => filesize($backupFile),
            'tables' => 0
        ];
    }
    
    /**
     * Limpia backups antiguos
     */
    public function cleanup() {
        $maxBackups = 10; // Mantener últimos 10 backups completos
        $maxDays = 30; // Eliminar backups de más de 30 días
        
        $backups = $this->listBackups();
        $deleted = 0;
        
        // Agrupar por tipo
        $fullBackups = array_filter($backups, function($b) { return $b['type'] === 'completo'; });
        $differentialBackups = array_filter($backups, function($b) { return $b['type'] === 'diferencial'; });
        
        // Eliminar backups completos antiguos (mantener solo los últimos $maxBackups)
        if (count($fullBackups) > $maxBackups) {
            $toDelete = array_slice($fullBackups, $maxBackups);
            foreach ($toDelete as $backup) {
                $this->deleteBackup($backup['file']);
                $deleted++;
            }
        }
        
        // Eliminar todos los backups de más de $maxDays días
        $cutoffTime = time() - ($maxDays * 24 * 60 * 60);
        foreach ($backups as $backup) {
            if ($backup['created'] < $cutoffTime) {
                $this->deleteBackup($backup['file']);
                $deleted++;
            }
        }
        
        $this->log("Cleanup completado: {$deleted} archivos eliminados");
        
        return ['success' => true, 'deleted' => $deleted];
    }
    
    /**
     * Funciones auxiliares
     */
    private function ensureDirectoryExists($dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function getLastFullBackup() {
        $backups = $this->listBackups();
        foreach ($backups as $backup) {
            if ($backup['type'] === 'completo') {
                return $backup['file'];
            }
        }
        return null;
    }
    
    private function getBackupTimestamp($backupFile) {
        // Extraer timestamp del nombre del archivo
        if (preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $backupFile, $matches)) {
            return strtotime($matches[1]);
        }
        return time() - (24 * 60 * 60); // Por defecto, 24 horas atrás
    }
    
    private function findBackupFile($date) {
        $backups = $this->listBackups();
        foreach ($backups as $backup) {
            if (strpos($backup['file'], $date) !== false) {
                return "{$this->backupDir}/{$backup['file']}";
            }
        }
        return null;
    }
    
    private function decompressFile($source, $destination) {
        $data = gzdecode(file_get_contents($source));
        file_put_contents($destination, $data);
    }
    
    private function validateBackupIntegrity($backupFile) {
        $hashFile = $backupFile . '.sha256';
        if (!file_exists($hashFile)) {
            return false;
        }
        
        $storedHash = file_get_contents($hashFile);
        $actualHash = hash_file('sha256', $backupFile);
        
        return hash_equals($storedHash, $actualHash);
    }
    
    private function deleteBackup($filename) {
        $filepath = "{$this->backupDir}/{$filename}";
        $filesToDelete = [
            $filepath,
            str_replace('.gz', '.json', $filepath),
            str_replace('.gz', '.sha256', $filepath)
        ];
        
        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}

// ====================================================================
// EJECUCIÓN PRINCIPAL
// ====================================================================

// Solo ejecutar si se llama desde CLI y es el script principal
if (php_sapi_name() === 'cli' && !count(debug_backtrace())) {
try {
    $backup = new DatabaseBackup();
    
// Procesar argumentos de línea de comandos
$options = getopt('', ['differential', 'restore:', 'restore-interactive:', 'list', 'cleanup']);
    
    if (isset($options['list'])) {
        // Listar backups disponibles
        $backups = $backup->listBackups();
        
        echo "\n=== BACKUPS DISPONIBLES ===\n";
        echo str_repeat("=", 60) . "\n";
        
        if (empty($backups)) {
            echo "No se encontraron backups.\n";
        } else {
            printf("%-25s %-12s %-10s %-8s %s\n", 
                "Archivo", "Tipo", "Tamaño", "Tablas", "Fecha");
            echo str_repeat("-", 60) . "\n";
            
            foreach ($backups as $backup) {
            $sizeBytes = $backup['size'] ?? 0;
            printf("%-25s %-12s %-10s %-8s %s\n",
                substr($backup['file'], 0, 25),
                $backup['type'],
                ($sizeBytes !== false) ? number_format($sizeBytes / 1024 / 1024, 2) . ' MB' : 'N/A',
                $backup['tables'],
                ($backup['created'] !== false) ? date('Y-m-d H:i', $backup['created']) : 'N/A'
            );
            }
        }
        echo "\n";
        
    } elseif (isset($options['restore'])) {
        // Restaurar backup específico
        $backupDate = $options['restore'];
        echo "Iniciando restauración del backup: {$backupDate}\n";
        $result = $backup->restore($backupDate);
        echo "✅ " . $result['message'] . "\n";
        
    } elseif (isset($options['restore-interactive'])) {
        // Restauración interactiva completa
        $backupDate = $options['restore-interactive'];
        $result = $backup->fullRestoreInteractive($backupDate);
        if ($result['success']) {
            echo "✅ " . $result['message'] . "\n";
            if (isset($result['statements_executed'])) {
                echo "📊 Statements ejecutados: {$result['statements_executed']}\n";
                echo "📋 Tablas restauradas: {$result['tables_restored']}\n";
                echo "⏱️  Tiempo de ejecución: {$result['execution_time']} segundos\n";
            }
        } else {
            echo "❌ " . $result['message'] . "\n";
        }
        
    } elseif (isset($options['cleanup'])) {
        // Limpiar backups antiguos
        echo "Iniciando limpieza de backups...\n";
        $result = $backup->cleanup();
        echo "✅ Cleanup completado. {$result['deleted']} archivos eliminados.\n";
        
    } elseif (isset($options['differential'])) {
        // Backup diferencial
        echo "Iniciando backup diferencial...\n";
        $result = $backup->backupDifferential();
        
        if ($result['success']) {
            echo "✅ Backup diferencial completado.\n";
            echo "Archivo: {$result['file']}\n";
            echo "Tamaño: " . number_format($result['size'] / 1024 / 1024, 2) . " MB\n";
        }
        
    } else {
        // Backup completo (default)
        echo "Iniciando backup completo...\n";
        $result = $backup->backupFull();
        
        if ($result['success']) {
            echo "✅ Backup completado exitosamente.\n";
            echo "Archivo: {$result['file']}\n";
            echo "Tamaño: " . number_format($result['size'] / 1024 / 1024, 2) . " MB\n";
            echo "Tablas: {$result['tables']}\n";
            echo "Hash SHA256: {$result['hash']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
}