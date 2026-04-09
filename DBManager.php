<?php
/**
 * DBManager - Clase para mantenimiento automatizado de bases de datos MySQL/MariaDB
 * 
 * Proporciona métodos para backup completo y restauración de bases de datos
 * incluyendo índices, llaves, relaciones, procedimientos almacenados y triggers.
 * 
 * @author Sistema Biométrico
 * @version 1.0.0
 * @requires PHP 8.0+ con extensión PDO y mariadb-dump/mysqldump
 */

class DBManager {
    private string $host;
    private string $user;
    private string $pass;
    private string $dbname;
    private ?PDO $pdo = null;
    private string $backupDir;
    private string $logFile;
    
    private const DEFAULT_BACKUP_DIR = __DIR__ . '/backups/database';
    private const DEFAULT_LOG_FILE = __DIR__ . '/logs/dbmanager.log';
    private const CHARSET = 'utf8mb4';
    private const DUMP_BINARIES = ['mariadb-dump', 'mysqldump'];

    /**
     * Constructor de la clase DBManager
     * 
     * @param string $host Servidor de base de datos
     * @param string $user Usuario de base de datos
     * @param string $pass Contraseña de base de datos
     * @param string $dbname Nombre de la base de datos
     * @param string|null $backupDir Directorio para backups (opcional)
     * @param string|null $logFile Archivo de log (opcional)
     */
    public function __construct(
        string $host, 
        string $user, 
        string $pass, 
        string $dbname,
        ?string $backupDir = null,
        ?string $logFile = null
    ) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->dbname = $dbname;
        $this->backupDir = $backupDir ?? self::DEFAULT_BACKUP_DIR;
        $this->logFile = $logFile ?? self::DEFAULT_LOG_FILE;
        
        $this->ensureDirectoriesExist();
    }

    /**
     * Obtiene una conexión PDO a la base de datos
     * 
     * @param bool $createDbOnly Indica si solo debe crear la base de datos si no existe
     * @return PDO Conexión a la base de datos
     * @throws PDOException Si hay error de conexión
     */
    public function getConnection(bool $createDbOnly = false): PDO {
        // Siempre obtener conexión fresca para evitar problemas de cache
        try {
            $dsn = "mysql:host={$this->host};charset=" . self::CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::CHARSET
            ];

            $pdo = new PDO($dsn, $this->user, $this->pass, $options);
            
            if (!$createDbOnly) {
                $pdo->exec("USE `{$this->dbname}`");
            }
            
            return $pdo;
            
        } catch (PDOException $e) {
            $this->log("Error de conexión: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Verifica si la base de datos existe
     * 
     * @return bool True si existe, false en caso contrario
     */
    public function databaseExists(): bool {
        try {
            $pdo = $this->getConnection(true);
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$this->dbname}'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Crea la base de datos si no existe
     * 
     * @return bool True si se creó o ya existía
     * @throws PDOException Si hay error al crear
     */
    public function createDatabaseIfNotExists(): bool {
        if ($this->databaseExists()) {
            $this->log("La base de datos '{$this->dbname}' ya existe");
            return true;
        }

        try {
            $pdo = $this->getConnection(true);
            
            $sql = "CREATE DATABASE `{$this->dbname}` 
                    CHARACTER SET = " . self::CHARSET . " 
                    COLLATE = " . self::CHARSET . "_general_ci";
            
            $pdo->exec($sql);
            $this->log("Base de datos '{$this->dbname}' creada correctamente");
            
            return true;
            
        } catch (PDOException $e) {
            $this->log("Error al crear base de datos: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Verifica si hay herramienta de dump disponible (mariadb-dump o mysqldump)
     *
     * @return bool True si al menos una herramienta está disponible
     */
    public function isMysqldumpAvailable(): bool {
        return $this->findDumpBinary() !== null;
    }

    /**
     * Obtiene el binario de dump disponible.
     *
     * @return string|null 'mariadb-dump', 'mysqldump' o null
     */
    public function getAvailableDumpBinary(): ?string {
        return $this->findDumpBinary();
    }

    /**
     * Crea un backup completo de la base de datos
     * 
     * Incluye: tablas, índices, llaves, relaciones, procedimientos almacenados,
     * funciones, triggers y datos.
     * 
     * @param bool $compress Si true, comprime el archivo con gzip
     * @return array Resultado del backup ['success', 'file', 'size', 'timestamp']
     * @throws Exception Si hay error durante el backup
     */
    public function fullBackup(bool $compress = true): array {
        if (!$this->databaseExists()) {
            throw new Exception("La base de datos '{$this->dbname}' no existe");
        }

        if (!$this->isMysqldumpAvailable()) {
            throw new Exception("No está disponible mariadb-dump ni mysqldump en el sistema");
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "full_backup_{$timestamp}.sql";
        $backupDir = $this->resolveWritableBackupDir();
        $filepath = $backupDir . '/' . $filename;
        
        $this->log("Iniciando backup completo: {$filename}");

        try {
            $mysqldumpCmd = $this->buildMysqldumpCommand($filepath);
            
            $output = [];
            $returnCode = 0;
            exec($mysqldumpCmd, $output, $returnCode);

            if ($returnCode !== 0) {
                $errorMsg = implode("\n", $output);
                throw new Exception("dump de base de datos falló: {$errorMsg}");
            }

            $fileSize = filesize($filepath);
            
            $result = [
                'success' => true,
                'file' => $filepath,
                'filename' => $filename,
                'size' => $fileSize,
                'size_formatted' => $this->formatBytes($fileSize),
                'timestamp' => $timestamp,
                'compress' => $compress
            ];

            if ($compress) {
                $compressedFile = $filepath . '.gz';
                $this->compressFile($filepath, $compressedFile);
                
                $result['file'] = $compressedFile;
                $result['filename'] = $filename . '.gz';
                $result['size'] = filesize($compressedFile);
                $result['size_formatted'] = $this->formatBytes($result['size']);
                
                @unlink($filepath);
            }

            $this->log("Backup completado: {$result['filename']} ({$result['size_formatted']})");
            
            return $result;

        } catch (Exception $e) {
            $this->log("Error en backup: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Construye el comando de dump (mariadb-dump/mysqldump) con los flags requeridos
     * 
     * Flags utilizados:
     * --opt: Habilita optimizaciones de dump (quick, extended-insert, lock-tables)
     * --routines: Incluye procedimientos almacenados y funciones
     * --triggers: Incluye triggers
     * --single-transaction: Realiza dump en una sola transacción sin bloquear tablas
     * --events: Incluye eventos del scheduler
     * --add-drop-table: Agrega DROP TABLE antes de cada CREATE TABLE
     * --add-drop-database: Agrega DROP DATABASE al inicio
     * --create-options: Incluye opciones de creación de tablas
     * --comments: Incluye comentarios
     * 
     * @param string $filepath Ruta del archivo de salida
     * @return string Comando de dump completo
     */
    private function buildMysqldumpCommand(string $filepath): string {
        $dumpBinary = $this->findDumpBinary();
        if ($dumpBinary === null) {
            throw new Exception("No se encontró mariadb-dump ni mysqldump");
        }
        
        $flags = [
            '--opt',
            '--routines',
            '--triggers',
            '--single-transaction',
            '--events',
            '--add-drop-table',
            '--add-drop-database',
            '--create-options',
            '--comments',
            '--add-drop-trigger',
            '--hex-blob',
            '--no-tablespaces'
        ];

        $parts = [
            escapeshellcmd($dumpBinary),
            implode(' ', $flags),
            '--host=' . escapeshellarg($this->host),
            '--user=' . escapeshellarg($this->user)
        ];

        // Evita prompt interactivo cuando no hay password.
        if ($this->pass !== '') {
            $parts[] = '--password=' . escapeshellarg($this->pass);
        }

        $parts[] = '--databases';
        $parts[] = escapeshellarg($this->dbname);
        $parts[] = '--result-file=' . escapeshellarg($filepath);
        $parts[] = '2>&1';

        $command = implode(' ', $parts);

        return $command;
    }

    /**
     * Obtiene un directorio de backup con permisos de escritura.
     * Si el directorio principal no es escribible, usa /tmp como fallback.
     *
     * @return string Ruta de directorio escribible para backups
     * @throws Exception Si no existe ningún directorio escribible
     */
    private function resolveWritableBackupDir(): string {
        $primary = $this->backupDir;
        if ($this->ensureDirectoryWritable($primary)) {
            return $primary;
        }

        $fallback = $this->getFallbackBackupDir();
        if ($this->ensureDirectoryWritable($fallback)) {
            $this->log(
                "Directorio de backup sin permisos: '{$primary}'. Usando fallback: '{$fallback}'",
                'WARNING'
            );
            return $fallback;
        }

        throw new Exception(
            "No hay permisos de escritura en '{$primary}' ni en '{$fallback}'. " .
            "Asigna permisos al usuario de PHP (www-data/nginx/apache)."
        );
    }

    /**
     * Garantiza que un directorio exista y sea escribible.
     *
     * @param string $dir Directorio a validar
     * @return bool True si es escribible
     */
    private function ensureDirectoryWritable(string $dir): bool {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return false;
        }

        if (is_writable($dir)) {
            return true;
        }

        // Intentar ajustar permisos cuando sea posible.
        @chmod($dir, 0775);
        clearstatcache(true, $dir);

        if (is_writable($dir)) {
            return true;
        }

        // Último intento más permisivo para entornos locales.
        @chmod($dir, 0777);
        clearstatcache(true, $dir);
        return is_writable($dir);
    }

    /**
     * Busca la mejor herramienta de dump disponible.
     * Prioriza mariadb-dump cuando el servidor es MariaDB.
     *
     * @return string|null Nombre del binario o null si no existe
     */
    private function findDumpBinary(): ?string {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        foreach (self::DUMP_BINARIES as $binary) {
            if ($isWindows) {
                // En Windows, 'where' busca en el PATH. NUL es el /dev/null de Windows.
                $cmd = 'where ' . escapeshellarg($binary) . ' > NUL 2>&1';
            } else {
                // En Linux/macOS, 'command -v' es el estándar.
                $cmd = 'command -v ' . escapeshellarg($binary) . ' >/dev/null 2>&1';
            }

            $output = [];
            $code = 1;
            exec($cmd, $output, $code);
            if ($code === 0) {
                return $binary;
            }
        }
        return null;
    }

    /**
     * Restaura la base de datos desde un archivo SQL
     * 
     * Proceso:
     * 1. Verifica/existe la base de datos, la crea si no existe
     * 2. Si dropDatabase=true, elimina todas las tablas existentes
     * 3. Deshabilita FOREIGN_KEY_CHECKS
     * 4. Ejecuta el script SQL
     * 5. Rehabilita FOREIGN_KEY_CHECKS
     * 6. Verifica la integridad de la restauración
     * 
     * @param string $filePath Ruta al archivo SQL (.sql o .sql.gz)
     * @param bool $dropDatabase Si true, elimina la base de datos completa antes de restaurar
     * @return array Resultado de la restauración
     * @throws Exception Si hay error durante la restauración
     */
    public function smartRestore(string $filePath, bool $dropDatabase = true): array {
        if (!file_exists($filePath)) {
            throw new Exception("El archivo de backup no existe: {$filePath}");
        }

        $this->log("Iniciando restauración desde: {$filePath}");

        $startTime = microtime(true);
        
        try {
            // 1. Preparar la base de datos
            if ($dropDatabase) {
                $this->dropDatabase();
            }
            $this->createDatabaseIfNotExists();
            
            // 2. Obtener contenido SQL
            $sqlContent = $this->getSQLContent($filePath);
            
            // 3. Verificar que el contenido sea válido
            if (empty(trim($sqlContent))) {
                throw new Exception("El archivo de backup está vacío o es inválido");
            }

            // 4. Conectar y comenzar transacción
            $pdo = $this->getConnection();
            $pdo->beginTransaction();
            
            $this->log("Deshabilitando FOREIGN_KEY_CHECKS");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // 5. Ejecutar el SQL
            $this->executeSQLFile($pdo, $sqlContent);
            
            // 6. Rehabilitar FOREIGN_KEY_CHECKS
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // 7. Confirmar transacción solo si está activa
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            
            // 8. Verificar restauración
            $verification = $this->verifyRestore();
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $result = [
                'success' => true,
                'message' => 'Restauración completada exitosamente',
                'database' => $this->dbname,
                'tables_count' => $verification['tables'],
                'rows_count' => $verification['rows'],
                'execution_time' => $executionTime,
                'file' => $filePath
            ];

            $this->log("Restauración completada en {$executionTime}s: {$verification['tables']} tablas, {$verification['rows']} filas");
            
            return $result;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
            
            $this->log("Error en restauración: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Obtiene el contenido SQL de un archivo (soporta .sql y .sql.gz)
     * 
     * @param string $filePath Ruta del archivo
     * @return string Contenido SQL
     * @throws Exception Si no se puede leer el archivo
     */
    private function getSQLContent(string $filePath): string {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'gz') {
            $compressed = file_get_contents($filePath);
            if ($compressed === false) {
                throw new Exception("No se pudo leer el archivo comprimido");
            }
            
            $content = gzdecode($compressed);
            if ($content === false) {
                throw new Exception("No se pudo descomprimir el archivo");
            }
            
            return $content;
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("No se pudo leer el archivo: {$filePath}");
        }
        
        return $content;
    }

    /**
     * Ejecuta el contenido SQL dividiendo en statements individuales
     * 
     * @param PDO $pdo Conexión PDO
     * @param string $sqlContent Contenido SQL completo
     */
    private function executeSQLFile(PDO $pdo, string $sqlContent): void {
        $statements = $this->parseSQLStatements($sqlContent);
        
        $this->log("Ejecutando " . count($statements) . " statements SQL");
        
        $executed = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            $trimmed = strtoupper(trim($statement));
            
            // Ignorar comentarios y statements vacíos
            if (empty($trimmed) || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0) {
                continue;
            }
            
            // Ignorar statements de transacción del backup
            if (strpos($trimmed, 'START TRANSACTION') === 0 || 
                strpos($trimmed, 'COMMIT') === 0 || 
                strpos($trimmed, 'ROLLBACK') === 0 ||
                strpos($trimmed, 'BEGIN') === 0) {
                continue;
            }
            
            // Ignorar SET statements de configuración del backup
            if (strpos($trimmed, 'SET SQL_MODE') === 0 || 
                strpos($trimmed, 'SET FOREIGN_KEY_CHECKS') === 0 ||
                strpos($trimmed, 'SET NAMES') === 0 ||
                strpos($trimmed, 'SET CHARACTER SET') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $executed++;
                
                // Log cada 100 statements
                if ($executed % 100 === 0) {
                    $this->log("Ejecutados {$executed} statements...");
                }
                
            } catch (PDOException $e) {
                $errors++;
                $this->log("Error en statement: " . substr($trimmed, 0, 100) . " - " . $e->getMessage(), 'WARNING');
                
                if ($errors > 10) {
                    throw new Exception("Demasiados errores ({$errors}), abortando restauración");
                }
            }
        }
        
        $this->log("SQL ejecutado: {$executed} statements exitosos, {$errors} errores");
    }

    /**
     * Parsea el contenido SQL en statements individuales
     * 
     * Maneja correctamente strings, delimitadores y caracteres de escape
     * 
     * @param string $sqlContent Contenido SQL
     * @return array Array de statements
     */
    private function parseSQLStatements(string $sqlContent): array {
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
            
            // Caracteres de escape dentro de strings
            if ($char === '\\' && $inString) {
                $currentStatement .= $char;
                $escapeNext = true;
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
            }
            
            // Delimitador de statement (;) fuera de strings
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
        
        // Agregar último statement si no termina con ;
        $lastStatement = trim($currentStatement);
        if (!empty($lastStatement)) {
            $statements[] = $lastStatement;
        }
        
        return $statements;
    }

    /**
     * Verifica que la restauración fue exitosa
     * 
     * @return array Información de verificación
     */
    private function verifyRestore(): array {
        try {
            $pdo = $this->getConnection();
            
            // Contar tablas
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Contar filas totales
            $totalRows = 0;
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                    $totalRows += (int) $stmt->fetchColumn();
                } catch (PDOException $e) {
                    // Tabla puede estar vacía o tener errores
                }
            }
            
            return [
                'tables' => count($tables),
                'rows' => $totalRows,
                'tables_list' => $tables
            ];
            
        } catch (PDOException $e) {
            $this->log("Error en verificación: " . $e->getMessage(), 'WARNING');
            return ['tables' => 0, 'rows' => 0];
        }
    }

    /**
     * Elimina la base de datos completamente
     * 
     * @return bool True si se eliminó
     */
    public function dropDatabase(): bool {
        try {
            $pdo = $this->getConnection(true);
            $pdo->exec("DROP DATABASE IF EXISTS `{$this->dbname}`");
            
            // Cerrar conexión actual y resetear para reconectar
            $this->pdo = null;
            
            $this->log("Base de datos '{$this->dbname}' eliminada");
            return true;
        } catch (PDOException $e) {
            $this->log("Error al eliminar base de datos: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Vacía (TRUNCATE) una o más tablas.
     *
     * @param array $tableNames Nombres de las tablas a vaciar.
     * @throws InvalidArgumentException Si el array está vacío.
     * @throws PDOException Si hay un error de base de datos.
     */
    public function truncateTables(array $tableNames): void {
        if (empty($tableNames)) {
            throw new InvalidArgumentException("Se debe proporcionar un array de nombres de tablas.");
        }

        $pdo = $this->getConnection();
        $existingTables = array_column($this->getTablesInfo(), 'name');

        $this->log("Iniciando vaciado de tablas: " . implode(', ', $tableNames));
        
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            foreach ($tableNames as $tableName) {
                $tableName = trim($tableName);
                if (in_array($tableName, $existingTables)) {
                    try {
                        $pdo->exec("TRUNCATE TABLE `{$tableName}`");
                        $this->log("Tabla '{$tableName}' vaciada correctamente.");
                    } catch (PDOException $e) {
                        $this->log("Error al vaciar la tabla '{$tableName}': " . $e->getMessage(), 'ERROR');
                    }
                } else {
                    $this->log("La tabla '{$tableName}' no existe y fue omitida.", 'WARNING');
                }
            }
        } finally {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            $this->log("Vaciado de tablas completado.");
        }
    }

    /**
     * Lista los backups disponibles en el directorio
     * 
     * @return array Lista de backups
     */
    public function listBackups(): array {
        $indexed = [];

        foreach ($this->getBackupDirectories() as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*.{sql,sql.gz}', GLOB_BRACE) ?: [];
            foreach ($files as $file) {
                $filename = basename($file);
                $created = filemtime($file) ?: 0;

                // Si existe duplicado por nombre, conservar el más reciente.
                if (isset($indexed[$filename]) && $indexed[$filename]['created'] >= $created) {
                    continue;
                }

                $size = filesize($file) ?: 0;
                $indexed[$filename] = [
                    'filename' => $filename,
                    'filepath' => $file,
                    'location' => $dir,
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'created' => $created,
                    'created_formatted' => date('Y-m-d H:i:s', $created),
                    'compressed' => pathinfo($file, PATHINFO_EXTENSION) === 'gz'
                ];
            }
        }

        $backups = array_values($indexed);
        
        // Ordenar por fecha (más reciente primero)
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }

    /**
     * Resuelve la ruta de un backup por nombre en los directorios válidos.
     *
     * @param string $filename Nombre de archivo de backup
     * @return string|null Ruta absoluta o null si no existe
     */
    public function resolveBackupFilePath(string $filename): ?string {
        $safeName = basename($filename);
        if ($safeName === '' || $safeName !== $filename) {
            return null;
        }

        foreach ($this->getBackupDirectories() as $dir) {
            $candidate = $dir . '/' . $safeName;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Directorios en los que el sistema guarda/consulta backups.
     *
     * @return array<string>
     */
    private function getBackupDirectories(): array {
        $dirs = [
            $this->backupDir,
            $this->getFallbackBackupDir(),
            '/usr/share/nginx/html/sistema_biometrico/backups/database_sql'
        ];
        
        return array_values(array_unique($dirs));
    }

    /**
     * Directorio de fallback para backups.
     */
    private function getFallbackBackupDir(): string {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/sistema_biometrico/backups';
    }

    /**
     * Obtiene información de las tablas de la base de datos
     * 
     * @return array Información de tablas
     */
    public function getTablesInfo(): array {
        $pdo = $this->getConnection();
        
        $stmt = $pdo->query("SHOW TABLE STATUS");
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $info = [];
        foreach ($tables as $table) {
            $info[] = [
                'name' => $table['Name'],
                'engine' => $table['Engine'] ?? 'N/A',
                'rows' => $table['Rows'] ?? 0,
                'data_length' => $table['Data_length'] ?? 0,
                'index_length' => $table['Index_length'] ?? 0,
                'total_size' => ($table['Data_length'] ?? 0) + ($table['Index_length'] ?? 0),
                'collation' => $table['Collation'] ?? 'N/A',
                'auto_increment' => $table['Auto_increment'] ?? null,
                'create_time' => $table['Create_time'] ?? null,
                'update_time' => $table['Update_time'] ?? null
            ];
        }
        
        return $info;
    }

    /**
     * Obtiene información de las llaves foráneas
     * 
     * @param string $tableName Nombre de la tabla (opcional)
     * @return array Información de llaves foráneas
     */
    public function getForeignKeys(?string $tableName = null): array {
        $pdo = $this->getConnection();
        
        $sql = "
            SELECT 
                kcu.TABLE_NAME,
                kcu.COLUMN_NAME,
                kcu.CONSTRAINT_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.DELETE_RULE,
                rc.UPDATE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = ?
        ";
        
        $params = [$this->dbname];
        
        if ($tableName) {
            $sql .= " AND kcu.TABLE_NAME = ?";
            $params[] = $tableName;
        }
        
        $sql .= " ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los índices de una tabla
     * 
     * @param string $tableName Nombre de la tabla
     * @return array Información de índices
     */
    public function getIndexes(string $tableName): array {
        $pdo = $this->getConnection();
        
        $stmt = $pdo->prepare("SHOW INDEX FROM `{$tableName}`");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Comprime un archivo usando gzip
     * 
     * @param string $source Archivo origen
     * @param string $destination Archivo destino comprimido
     */
    private function compressFile(string $source, string $destination): void {
        $content = file_get_contents($source);
        $compressed = gzencode($content, 9);
        
        if (file_put_contents($destination, $compressed) === false) {
            throw new Exception("No se pudo comprimir el archivo");
        }
    }

    /**
     * Descomprime un archivo gzip
     * 
     * @param string $source Archivo comprimido
     * @param string $destination Archivo destino
     */
    private function decompressFile(string $source, string $destination): void {
        $compressed = file_get_contents($source);
        $content = gzdecode($compressed);
        
        if ($content === false) {
            throw new Exception("No se pudo descomprimir el archivo");
        }
        
        if (file_put_contents($destination, $content) === false) {
            throw new Exception("No se pudo escribir el archivo descomprimido");
        }
    }

    /**
     * Asegura que los directorios necesarios existan
     */
    private function ensureDirectoriesExist(): void {
        $this->ensureDirectoryWritable($this->backupDir);
        
        $logDir = dirname($this->logFile);
        $this->ensureDirectoryWritable($logDir);
    }

    /**
     * Registra un mensaje en el log
     * 
     * @param string $message Mensaje a registrar
     * @param string $level Nivel del log (INFO, WARNING, ERROR)
     */
    private function log(string $message, string $level = 'INFO'): void {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // También mostrar en stdout si es CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }

    /**
     * Formatea bytes a formato legible
     * 
     * @param int $bytes Bytes a formatear
     * @return string Bytes formateados
     */
    public function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Cierra la conexión a la base de datos
     */
    public function closeConnection(): void {
        $this->pdo = null;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->closeConnection();
    }
}

// ====================================================================
// EJEMPLO DE USO - CLI
// ====================================================================

if (php_sapi_name() === 'cli' && !count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))) {
    // Cargar configuración
    require_once __DIR__ . '/config.php';
    
    // Credenciales desde config.php
    $host = DB_HOST ?? 'localhost';
    $user = DB_USER ?? 'root';
    $pass = DB_PASS ?? '';
    $dbname = DB_NAME ?? 'sistema_biometrico';
    
    // Crear instancia de DBManager
    $dbManager = new DBManager($host, $user, $pass, $dbname);
    
    // Procesar argumentos
    $options = getopt('', ['backup', 'restore:', 'list', 'tables', 'drop', 'info', 'truncate:']);
    
    try {
        if (isset($options['backup'])) {
            echo "\n=== CREANDO BACKUP COMPLETO ===\n";
            $result = $dbManager->fullBackup(true);
            
            echo "✅ Backup completado exitosamente\n";
            echo "📄 Archivo: {$result['filename']}\n";
            echo "📊 Tamaño: {$result['size_formatted']}\n";
            echo "🕐 Timestamp: {$result['timestamp']}\n";
            
        } elseif (isset($options['restore'])) {
            $file = $options['restore'];
            
            echo "\n=== RESTAURANDO BASE DE DATOS ===\n";
            echo "⚠️  ADVERTENCIA: Esta acción puede sobrescribir datos existentes\n";
            echo "Archivo: {$file}\n\n";
            
            $result = $dbManager->smartRestore($file);
            
            echo "✅ Restauración completada\n";
            echo "📊 Tablas restauradas: {$result['tables_count']}\n";
            echo "📋 Filas insertadas: {$result['rows_count']}\n";
            echo "⏱️  Tiempo: {$result['execution_time']}s\n";
            
        } elseif (isset($options['truncate'])) {
            $tablesToTruncate = explode(',', $options['truncate']);
            if (empty($tablesToTruncate) || empty($options['truncate'])) {
                echo "❌ Error: Debe especificar al menos una tabla para vaciar. --truncate=tabla1,tabla2\n";
                exit(1);
            }

            echo "\n=== VACIANDO TABLAS ===\n";
            echo "⚠️  ADVERTENCIA: Esta acción eliminará TODOS los datos de las tablas especificadas de forma irreversible.\n";
            echo "Tablas a vaciar: " . implode(', ', $tablesToTruncate) . "\n";
            echo "Base de datos: {$dbname}\n";
            echo "¿Está seguro? [s/N]: ";

            $handle = fopen("php://stdin", "r");
            $confirm = strtolower(trim(fgets($handle)));
            fclose($handle);

            if ($confirm === 's') {
                $dbManager->truncateTables($tablesToTruncate);
            } else {
                echo "Operación cancelada.\n";
            }
        } elseif (isset($options['list'])) {
            echo "\n=== BACKUPS DISPONIBLES ===\n";
            $backups = $dbManager->listBackups();
            
            if (empty($backups)) {
                echo "No hay backups disponibles.\n";
            } else {
                printf("%-35s %-12s %-20s\n", "Archivo", "Tamaño", "Fecha de creación");
                echo str_repeat("-", 70) . "\n";
                
                foreach ($backups as $backup) {
                    printf(
                        "%-35s %-12s %-20s\n",
                        $backup['filename'],
                        $backup['size_formatted'],
                        $backup['created_formatted']
                    );
                }
            }
            echo "\n";
            
        } elseif (isset($options['tables'])) {
            echo "\n=== INFORMACIÓN DE TABLAS ===\n";
            $tables = $dbManager->getTablesInfo();
            
            printf("%-25s %-10s %-12s %-12s %-12s\n", "Tabla", "Filas", "Datos", "Índices", "Total");
            echo str_repeat("-", 75) . "\n";
            
            $totalData = 0;
            $totalIndex = 0;
            
            foreach ($tables as $table) {
                printf(
                    "%-25s %-10d %-12s %-12s %-12s\n",
                    $table['name'],
                    $table['rows'],
                    $dbManager->formatBytes($table['data_length']),
                    $dbManager->formatBytes($table['index_length']),
                    $dbManager->formatBytes($table['total_size'])
                );
                
                $totalData += $table['data_length'];
                $totalIndex += $table['index_length'];
            }
            
            echo str_repeat("-", 75) . "\n";
            printf("%-25s %-10s %-12s %-12s %-12s\n", 
                "TOTAL", "", 
                $dbManager->formatBytes($totalData), 
                $dbManager->formatBytes($totalIndex),
                $dbManager->formatBytes($totalData + $totalIndex));
            echo "\n";
            
        } elseif (isset($options['drop'])) {
            echo "\n=== ELIMINAR BASE DE DATOS ===\n";
            echo "⚠️  ADVERTENCIA: Esta acción eliminará TODOS los datos\n";
            echo "Base de datos: {$dbname}\n";
            echo "¿Está seguro? [s/N]: ";
            
            $handle = fopen("php://stdin", "r");
            $confirm = strtolower(trim(fgets($handle)));
            fclose($handle);
            
            if ($confirm === 's') {
                $dbManager->dropDatabase();
                echo "✅ Base de datos eliminada\n";
            } else {
                echo "Operación cancelada.\n";
            }
            
        } elseif (isset($options['info'])) {
            echo "\n=== INFORMACIÓN DE LA BASE DE DATOS ===\n";
            echo "📦 Base de datos: {$dbname}\n";
            echo "🖥️  Servidor: {$host}\n";
            echo "👤 Usuario: {$user}\n\n";
            
            echo "--- Tablas ---\n";
            $tables = $dbManager->getTablesInfo();
            echo "Total de tablas: " . count($tables) . "\n\n";
            
            echo "--- Llaves Foráneas ---\n";
            $fks = $dbManager->getForeignKeys();
            echo "Total de relaciones: " . count($fks) . "\n";
            
            if (!empty($fks)) {
                foreach ($fks as $fk) {
                    echo "  - {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
                }
            }
            echo "\n";
            
        } else {
            echo "Uso: php DBManager.php [opciones]\n\n";
            echo "Opciones:\n";
            echo "  --backup          Crear backup completo de la base de datos\n";
            echo "  --restore <arch>  Restaurar base de datos desde archivo SQL\n";
            echo "  --truncate <tbls> Vaciar tablas específicas (ej: asistencia,retardos)\n";
            echo "  --list            Listar backups disponibles\n";
            echo "  --tables          Mostrar información de tablas\n";
            echo "  --drop            Eliminar base de datos\n";
            echo "  --info            Mostrar información de la base de datos\n";
            echo "\nEjemplos:\n";
            echo "  php DBManager.php --backup\n";
            echo "  php DBManager.php --restore backups/database/full_backup_2026-02-13.sql.gz\n";
            echo "  php DBManager.php --truncate asistencia,retardos,zkteco_procesamiento_logs\n";
            echo "  php DBManager.php --list\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
