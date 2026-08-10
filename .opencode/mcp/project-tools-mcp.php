<?php
/**
 * Project-Tools MCP Server for sistema_biometrico
 * 
 * Provides tools for tests, logs, migrations, scripts, routes,
 * file structure, and system operations.
 * 
 * Usage: php .opencode/mcp/project-tools-mcp.php
 */

declare(strict_types=1);

define('ROOT', getenv('MCP_PROJECT_ROOT') ?: (__DIR__ . '/../..'));
define('MAX_LOG_LINES', 200);

class ProjectMcpServer
{
    public function run(): void
    {
        $this->log("project-tools-mcp ready: root=" . ROOT);
        $this->listen();
    }

    private function getTools(): array
    {
        return [
            // --- File Structure ---
            [
                'name' => 'list_controllers',
                'description' => 'List all controller files with their routes',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'list_models',
                'description' => 'List all model files',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'list_views',
                'description' => 'List all view template files',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'list_services',
                'description' => 'List all service layer files',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            // --- Routes ---
            [
                'name' => 'list_routes',
                'description' => 'Show all registered FastRoute routes from routes.php',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'resolve_route',
                'description' => 'Find which controller/method handles a given URL path',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => ['type' => 'string', 'description' => 'URL path (e.g. /empleados, /validaciones/mensajes/5)']
                    ],
                    'required' => ['path']
                ]
            ],
            // --- Tests ---
            [
                'name' => 'list_tests',
                'description' => 'List all PHPUnit test files by suite',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'run_tests',
                'description' => 'Run PHPUnit tests (optional: specific suite or file)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'suite' => ['type' => 'string', 'description' => 'Optional: test suite name (Unit, Integration, API, Database, Security, Performance, E2E)'],
                        'file'  => ['type' => 'string', 'description' => 'Optional: specific test file path relative to project root'],
                        'filter' => ['type' => 'string', 'description' => 'Optional: test method name filter']
                    ]
                ]
            ],
            // --- Logs ---
            [
                'name' => 'list_logs',
                'description' => 'List available PHP error log files',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'read_log',
                'description' => 'Read PHP error log for a specific date',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => ['type' => 'string', 'description' => 'Date (YYYY-MM-DD) or "today" or "latest"'],
                        'lines' => ['type' => 'number', 'description' => 'Number of lines from end (default: 50)'],
                        'filter' => ['type' => 'string', 'description' => 'Optional: keyword to filter by']
                    ]
                ]
            ],
            [
                'name' => 'read_js_log',
                'description' => 'Read client-side JS error log for a specific date',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => ['type' => 'string', 'description' => 'Date (YYYY-MM-DD) or "latest"'],
                        'lines' => ['type' => 'number', 'description' => 'Lines from end (default: 50)']
                    ]
                ]
            ],
            // --- Migrations ---
            [
                'name' => 'list_migrations',
                'description' => 'List all SQL and PHP migration files with dates',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'run_migration',
                'description' => 'Execute a migration SQL file against the database',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'file' => ['type' => 'string', 'description' => 'Migration filename from migrations/ directory']
                    ],
                    'required' => ['file']
                ]
            ],
            // --- Scripts ---
            [
                'name' => 'list_scripts',
                'description' => 'List available utility scripts with descriptions',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            // --- Cache ---
            [
                'name' => 'clear_cache',
                'description' => 'Clear the cache directory and temp files',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            // --- System ---
            [
                'name' => 'system_status',
                'description' => 'Show system health info: PHP, DB, disk, git branch',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'check_syntax',
                'description' => 'Check PHP syntax on a file or directory (recursive)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => ['type' => 'string', 'description' => 'File or directory path relative to project root (e.g. controllers/)']
                    ],
                    'required' => ['path']
                ]
            ],
        ];
    }

    private function listen(): void
    {
        while ($line = fgets(STDIN)) {
            $line = trim($line);
            if (empty($line)) continue;
            $msg = json_decode($line, true);
            if (!$msg || !isset($msg['jsonrpc']) || !isset($msg['method'])) continue;

            $response = match ($msg['method']) {
                'initialize' => $this->onInitialize($msg['params'] ?? [], $msg['id'] ?? null),
                'notifications/initialized' => null,
                'ping' => ['jsonrpc' => '2.0', 'id' => $msg['id'], 'result' => (object)[]],
                'tools/list' => ['jsonrpc' => '2.0', 'id' => $msg['id'], 'result' => ['tools' => $this->getTools()]],
                'tools/call' => $this->onToolCall($msg['params'] ?? [], $msg['id'] ?? null),
                default => ['jsonrpc' => '2.0', 'id' => $msg['id'], 'error' => ['code' => -32601, 'message' => "Unknown: {$msg['method']}"]]
            };
            if ($response !== null) $this->send($response);
        }
    }

    private function onInitialize(array $params, ?int $id): array
    {
        return [
            'jsonrpc' => '2.0', 'id' => $id, 'result' => [
                'protocolVersion' => $params['protocolVersion'] ?? '2024-11-05',
                'capabilities' => ['tools' => (object)[]],
                'serverInfo' => ['name' => 'project-tools-mcp', 'version' => '1.0.0']
            ]
        ];
    }

    private function onToolCall(array $params, ?int $id): array
    {
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        try {
            $result = match ($name) {
                'list_controllers' => $this->listControllers(),
                'list_models' => $this->listFiles('models/'),
                'list_views' => $this->listViews(),
                'list_services' => $this->listFiles('services/'),
                'list_routes' => $this->listRoutes(),
                'resolve_route' => $this->resolveRoute($args),
                'list_tests' => $this->listTests(),
                'run_tests' => $this->runTests($args),
                'list_logs' => $this->listLogs(),
                'read_log' => $this->readLog($args),
                'read_js_log' => $this->readJsLog($args),
                'list_migrations' => $this->listMigrations(),
                'run_migration' => $this->runMigration($args),
                'list_scripts' => $this->listScripts(),
                'clear_cache' => $this->clearCache(),
                'system_status' => $this->systemStatus(),
                'check_syntax' => $this->checkSyntax($args),
                default => throw new \RuntimeException("Unknown tool: $name")
            };
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['content' => [['type' => 'text', 'text' => $result]]]];
        } catch (\Throwable $e) {
            return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32000, 'message' => $e->getMessage()]];
        }
    }

    private function listFiles(string $dir): string
    {
        $path = ROOT . '/' . $dir;
        if (!is_dir($path)) return "Directory '$dir' not found";

        $files = glob($path . '*.php');
        sort($files);

        $out = "=== " . ucfirst(rtrim($dir, '/')) . " ===\n\n";
        $out .= sprintf("%-5s %-45s %s\n", '#', 'File', 'Size');
        $out .= str_repeat('-', 65) . "\n";
        foreach ($files as $i => $f) {
            $size = filesize($f);
            $out .= sprintf("%-5s %-45s %s\n", ($i + 1) . '.', basename($f), $this->fmtBytes($size));
        }
        return $out;
    }

    private function listControllers(): string
    {
        $files = glob(ROOT . '/controllers/*.php');
        sort($files);

        $out = "=== Controllers ===\n\n";
        $out .= sprintf("%-5s %-40s %s\n", '#', 'Controller', 'Size');
        $out .= str_repeat('-', 65) . "\n";
        foreach ($files as $i => $f) {
            $lines = count(file($f));
            $out .= sprintf("%-5s %-40s %s lines\n", ($i + 1) . '.', basename($f, '.php'), number_format($lines));
        }
        return $out;
    }

    private function listViews(): string
    {
        $dirs = ['views/'];
        $out = "=== View Directories ===\n\n";
        foreach ($dirs as $dir) {
            $path = ROOT . '/' . $dir;
            if (!is_dir($path)) continue;
            $items = scandir($path);
            $out .= "$dir:\n";
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $full = $path . $item;
                if (is_dir($full)) {
                    $count = count(glob($full . '/*.php'));
                    $out .= "  📁 $item/ ($count views)\n";
                } elseif (str_ends_with($item, '.php')) {
                    $size = filesize($full);
                    $out .= "  📄 $item (" . $this->fmtBytes($size) . ")\n";
                }
            }
            $out .= "\n";
        }
        return $out;
    }

    private function listRoutes(): string
    {
        $rp = ROOT . '/routes.php';
        if (!file_exists($rp)) return "routes.php not found";

        $lines = file($rp);
        $routes = [];
        foreach ($lines as $ln) {
            if (preg_match('/^\s*\$r\s*->\s*(addRoute|get|post|put|delete|patch)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $ln, $m)) {
                $routes[] = [strtoupper($m[1] === 'addRoute' ? 'ANY' : $m[1]), $m[2], $m[3]];
            }
        }

        $out = "=== Routes (" . count($routes) . ") ===\n\n";
        $out .= sprintf("%-7s %-50s %s\n", 'Method', 'Path', 'Handler');
        $out .= str_repeat('-', 110) . "\n";

        $grouped = [];
        foreach ($routes as $r) {
            $prefix = explode('/', trim($r[1], '/'))[0] ?? '';
            $grouped[$prefix][] = $r;
        }

        ksort($grouped);
        foreach ($grouped as $prefix => $rs) {
            $out .= "\n--- /$prefix ---\n";
            foreach ($rs as $r) {
                $out .= sprintf("%-7s %-50s %s\n", $r[0], $r[1], $r[2]);
            }
        }
        $out .= "\nTotal: " . count($routes) . " routes\n";
        return $out;
    }

    private function resolveRoute(array $args): string
    {
        $path = $args['path'] ?? '';
        if (!$path) throw new \RuntimeException('path is required');

        $rp = ROOT . '/routes.php';
        if (!file_exists($rp)) return "routes.php not found";

        $content = file_get_contents($rp);
        $escaped = preg_quote($path, '/');

        if (preg_match_all('/\$r\s*->\s*(?:addRoute|get|post)\s*\(\s*[\'"]([^\'"]*' . $escaped . '[^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $m)) {
            $out = "=== Routes matching '$path' ===\n\n";
            foreach ($m[1] as $i => $pattern) {
                $out .= "  Pattern: {$m[1][$i]}\n  Handler: {$m[2][$i]}\n\n";
            }
            return $out;
        }

        // Try partial match
        if (preg_match_all('/\$r\s*->\s*(?:addRoute|get|post)\s*\(\s*[\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $m)) {
            $candidates = [];
            foreach ($m[1] as $i => $pattern) {
                $regex = preg_replace('/\{[^}]+\}/', '[^/]+', $pattern);
                $regex = '#^' . $regex . '$#';
                if (preg_match($regex, $path)) {
                    $candidates[] = [$m[1][$i], $m[2][$i]];
                }
            }
            if ($candidates) {
                $out = "=== Matched routes for '$path' ===\n\n";
                foreach ($candidates as $c) {
                    $out .= "  {$c[0]} → {$c[1]}\n";
                }
                return $out;
            }
        }

        return "No route matches '$path'";
    }

    private function listTests(): string
    {
        $testDir = ROOT . '/tests';
        if (!is_dir($testDir)) return "tests/ directory not found";

        $suites = [];
        $files = $this->rglob($testDir . '/**/*Test.php');
        foreach ($files as $f) {
            $rel = str_replace($testDir . '/', '', $f);
            $suite = explode('/', $rel)[0];
            $suites[$suite][] = $rel;
        }

        $out = "=== Test Files ===\n\n";
        foreach ($suites as $suite => $tests) {
            $out .= "  [$suite] (" . count($tests) . " files)\n";
            foreach ($tests as $t) {
                $out .= "    - $t\n";
            }
            $out .= "\n";
        }

        $xml = ROOT . '/phpunit.xml';
        if (file_exists($xml)) {
            $xmlContent = simplexml_load_file($xml);
            if ($xmlContent && isset($xmlContent->testsuites)) {
                $out .= "PHPUnit Suites configured:\n";
                foreach ($xmlContent->testsuites->testsuite as $suite) {
                    $dirs = [];
                    foreach ($suite->directory as $d) $dirs[] = (string)$d;
                    $out .= "  - {$suite['name']}: " . implode(', ', $dirs) . "\n";
                }
            }
        }
        return $out;
    }

    private function runTests(array $args): string
    {
        $phpunit = ROOT . '/vendor/bin/phpunit';
        if (!file_exists($phpunit)) return "phpunit not found. Run 'composer install' first.";

        $cmd = "php $phpunit --configuration " . escapeshellarg(ROOT . '/phpunit.xml') . ' --no-coverage';

        if (!empty($args['suite'])) {
            $suite = escapeshellarg($args['suite']);
            $cmd .= " --testsuite=$suite";
        }
        if (!empty($args['file'])) {
            $file = ROOT . '/' . ltrim($args['file'], '/');
            if (!file_exists($file)) return "File not found: {$args['file']}";
            $cmd .= ' ' . escapeshellarg($file);
        }
        if (!empty($args['filter'])) {
            $cmd .= ' --filter=' . escapeshellarg($args['filter']);
        }

        $cmd .= ' 2>&1';

        $output = shell_exec($cmd);
        return $output ?? "No output from PHPUnit";
    }

    private function listLogs(): string
    {
        $logDir = ROOT . '/logs';
        if (!is_dir($logDir)) return "logs/ directory not found";

        $phpLogs = glob($logDir . '/php_errors_*.log');
        $jsLogs = glob($logDir . '/js_errors_*.log');

        $out = "=== PHP Error Logs ===\n\n";
        rsort($phpLogs);
        foreach (array_slice($phpLogs, 0, 20) as $f) {
            $date = str_replace(['php_errors_', '.log'], '', basename($f));
            $size = filesize($f);
            $out .= sprintf("  %s (%s)\n", $date, $this->fmtBytes($size));
        }

        $out .= "\n=== JS Error Logs ===\n\n";
        rsort($jsLogs);
        foreach (array_slice($jsLogs, 0, 10) as $f) {
            $date = str_replace(['js_errors_', '.log'], '', basename($f));
            $size = filesize($f);
            $out .= sprintf("  %s (%s)\n", $date, $this->fmtBytes($size));
        }

        return $out;
    }

    private function readLog(array $args): string
    {
        $date = $args['date'] ?? 'latest';
        $lines = min(intval($args['lines'] ?? 50), MAX_LOG_LINES);
        $filter = $args['filter'] ?? '';

        if ($date === 'latest' || $date === 'today') {
            $date = date('Y-m-d');
        }

        $logFile = ROOT . "/logs/php_errors_$date.log";
        if (!file_exists($logFile)) return "No log file for $date";

        $content = file_get_contents($logFile);
        $allLines = explode("\n", trim($content));

        if ($filter) {
            $allLines = array_filter($allLines, fn($l) => stripos($l, $filter) !== false);
        }

        $tail = array_slice($allLines, -$lines);

        $out = "=== PHP Errors for $date ===\n\n";
        $out .= "Showing last " . count($tail) . " of " . count($allLines) . " lines\n\n";
        foreach ($tail as $line) {
            $out .= $line . "\n";
        }
        return $out;
    }

    private function readJsLog(array $args): string
    {
        $date = $args['date'] ?? 'latest';
        $lines = min(intval($args['lines'] ?? 50), MAX_LOG_LINES);

        if ($date === 'latest') $date = date('Y-m-d');

        $logFile = ROOT . "/logs/js_errors_$date.log";
        if (!file_exists($logFile)) return "No JS error log for $date";

        $content = file_get_contents($logFile);
        $allLines = explode("\n", trim($content));
        $tail = array_slice($allLines, -$lines);

        $out = "=== JS Errors for $date ===\n\n";
        $out .= "Showing last " . count($tail) . " of " . count($allLines) . " lines\n\n";
        foreach ($tail as $line) {
            $out .= $line . "\n";
        }
        return $out;
    }

    private function listMigrations(): string
    {
        $dirs = [
            'SQL' => ROOT . '/migrations',
            'database_sql' => ROOT . '/database_sql',
        ];

        $out = "=== Migrations ===\n\n";
        foreach ($dirs as $label => $dir) {
            if (!is_dir($dir)) continue;
            $files = glob($dir . '/*.sql');
            $phpFiles = glob($dir . '/*.php');
            $all = array_merge($files, $phpFiles);
            sort($all);

            $out .= "[$label] (" . count($all) . " files)\n\n";
            foreach ($all as $f) {
                $name = basename($f);
                $size = filesize($f);
                $lines = count(file($f));
                $desc = $this->migrationDescription($name);
                if ($desc) $out .= sprintf("  %-50s %s\n", $name, $desc);
                else     $out .= sprintf("  %-50s (%s, %s lines)\n", $name, $this->fmtBytes($size), number_format($lines));
            }
            $out .= "\n";
        }
        return $out;
    }

    private function migrationDescription(string $name): string
    {
        $desc = [
            '20250122_create_ciclos_tables' => 'Create work cycles tables',
            '20250123_create_empleados_ciclos_table' => 'Employee-cycle assignments',
            '20250213_agregar_campos_historial_horarios' => 'Schedule history fields',
            '20250226_alter_retardos_tipo' => 'Alter retardos type',
            '20260304_add_tipo_incidencia_tipos_justificacion' => 'Add tipo_incidencia to justification types',
            '20260304_add_zkteco_id_to_empleados' => 'Add ZKTeco ID to employees',
            '20260305_fix_tipo_asistencia_por_definir' => 'Fix undefined attendance type',
            '20260318_add_area_fisica_to_empleados' => 'Add physical area field',
            '20260408_add_permiso_fallecimiento' => 'Death leave permission',
            '20260420_add_justificacion_tracking' => 'Justification modification tracking',
            '20260520_add_validacion_mensajes' => 'Validation messaging system',
            '20251119_add_sanciones' => 'Sanctions/support tables',
            '20251126_add_dispositivos_biometricos' => 'Biometric device config',
            'setup_database' => 'Full database schema setup',
            'critical_indexes' => 'Critical performance indexes',
        ];
        foreach ($desc as $key => $d) {
            if (str_contains($name, $key)) return "[$d]";
        }
        return '';
    }

    private function runMigration(array $args): string
    {
        $file = $args['file'] ?? '';
        if (!$file) throw new \RuntimeException('file is required');

        $paths = [ROOT . '/migrations/' . $file, ROOT . '/database_sql/' . $file];
        $found = null;
        foreach ($paths as $p) {
            if (file_exists($p)) { $found = $p; break; }
        }
        if (!$found) return "Migration file not found: $file";

        $ext = pathinfo($found, PATHINFO_EXTENSION);
        if ($ext === 'sql') {
            $sql = file_get_contents($found);
            $cmd = sprintf('mysql -u%s -p%s %s < %s 2>&1',
                escapeshellarg('root'), escapeshellarg('root'),
                escapeshellarg('sistema_biometrico'),
                escapeshellarg($found));
            $output = shell_exec($cmd);
            return $output === null ? "Migration executed: $file" : "Output: $output";
        } elseif ($ext === 'php') {
            $cmd = 'php ' . escapeshellarg($found) . ' 2>&1';
            $output = shell_exec($cmd);
            return $output ?? "Migration executed: $file";
        }
        return "Unsupported migration type: $ext";
    }

    private function listScripts(): string
    {
        $dir = ROOT . '/scripts';
        if (!is_dir($dir)) return "scripts/ not found";

        $files = glob($dir . '/*.php');
        sort($files);

        $descriptions = [
            'sync_zkteco_ids_empleados' => 'Sync ZKTeco device IDs with employee records',
            'recalcular_notas_malas_quincena' => 'Recalculate bad notes per fortnight',
            'diagnosticar_cero_inserciones' => 'Diagnose zero-insertion biometric issues',
            'validate_indexes' => 'Validate database index health',
            'create_indexes' => 'Create database indexes',
            'test_cache' => 'Test caching system',
            'list_db_photos' => 'List employee photos in database',
            'list_db_photos_compare' => 'Compare DB photos with filesystem',
            'correlacionar_dat_empleados' => 'Correlate .dat attendance logs with employees',
            'enviar_notificaciones_licencias' => 'Send leave/license notifications',
        ];

        $out = "=== Scripts ===\n\n";
        foreach ($files as $f) {
            $name = basename($f, '.php');
            $desc = $descriptions[$name] ?? '';
            $out .= sprintf("  %-45s %s\n", $name . '.php', $desc);
        }
        return $out;
    }

    private function clearCache(): string
    {
        $cacheDir = ROOT . '/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
            return "Cache directory created (was empty)";
        }

        $files = glob($cacheDir . '/*');
        $count = 0;
        $size = 0;
        foreach ($files as $f) {
            if (is_file($f)) {
                $size += filesize($f);
                unlink($f);
                $count++;
            }
        }
        return "Cleared $count cache files (" . $this->fmtBytes($size) . " freed)";
    }

    private function systemStatus(): string
    {
        $out = "=== System Status ===\n\n";

        // PHP
        $out .= "PHP:      " . PHP_VERSION . "\n";
        $out .= "Platform: " . PHP_OS . "\n";

        // Memory
        $mem = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $out .= "Memory:   " . $this->fmtBytes($mem) . " (peak: " . $this->fmtBytes($peak) . ")\n";

        // Disk
        $free = disk_free_space(ROOT);
        $total = disk_total_space(ROOT);
        $used = $total - $free;
        $pct = $total > 0 ? round(($used / $total) * 100, 1) : 0;
        $out .= "Disk:     " . $this->fmtBytes($used) . " / " . $this->fmtBytes($total) . " ({$pct}% used)\n";

        // Git
        $branch = trim(shell_exec('cd ' . escapeshellarg(ROOT) . ' && git rev-parse --abbrev-ref HEAD 2>/dev/null') ?? 'N/A');
        $commit = trim(shell_exec('cd ' . escapeshellarg(ROOT) . ' && git log --oneline -1 2>/dev/null') ?? '');
        $out .= "Git:      $branch\n";
        if ($commit) $out .= "          $commit\n";

        // Project size
        $totalFiles = 0;
        $totalSize = 0;
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iter as $f) {
            if ($f->isFile() && !str_contains($f->getPathname(), '/vendor/') && !str_contains($f->getPathname(), '/node-bin/')) {
                $totalFiles++;
                $totalSize += $f->getSize();
            }
        }
        $out .= "Files:    " . number_format($totalFiles) . " (" . $this->fmtBytes($totalSize) . " excluding vendor)\n";

        // DB connection test
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('MCP_DB_HOST') ?: 'localhost',
                getenv('MCP_DB_PORT') ?: '3306',
                getenv('MCP_DB_NAME') ?: 'sistema_biometrico');
            $pdo = new PDO($dsn, getenv('MCP_DB_USER') ?: 'root', getenv('MCP_DB_PASS') ?: 'root');
            $v = $pdo->query("SELECT VERSION()")->fetchColumn();
            $out .= "DB:       Connected ($v)\n";
        } catch (\Exception $e) {
            $out .= "DB:       Disconnected (" . $e->getMessage() . ")\n";
        }

        return $out;
    }

    private function checkSyntax(array $args): string
    {
        $path = $args['path'] ?? '';
        if (!$path) throw new \RuntimeException('path is required');

        $fullPath = ROOT . '/' . ltrim($path, '/');
        if (!file_exists($fullPath)) return "Path not found: $path";

        if (is_file($fullPath)) {
            $output = shell_exec('php -l ' . escapeshellarg($fullPath) . ' 2>&1');
            return "=== Syntax check: $path ===\n\n$output";
        }

        $files = $this->rglob($fullPath . '/**/*.php');
        $errors = [];
        $ok = 0;
        foreach ($files as $f) {
            $rel = str_replace(ROOT . '/', '', $f);
            $output = shell_exec('php -l ' . escapeshellarg($f) . ' 2>&1');
            if (!str_contains($output, 'No syntax errors')) {
                $errors[] = "$rel: $output";
            } else {
                $ok++;
            }
        }

        $out = "=== Syntax check: $path ===\n\n";
        $out .= "Checked: " . count($files) . " files\n";
        $out .= "OK:      $ok\n";
        $out .= "Errors:  " . count($errors) . "\n\n";
        foreach ($errors as $e) {
            $out .= "$e\n---\n";
        }
        return $out;
    }

    private function rglob(string $pattern): array
    {
        $files = glob($pattern);
        if (!is_array($files)) $files = [];

        $dirs = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        if (is_array($dirs)) {
            foreach ($dirs as $dir) {
                $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern)));
            }
        }
        return $files;
    }

    private function fmtBytes(float|int $bytes): string
    {
        $bytes = (int)$bytes;
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int)floor(log($bytes) / log(1024));
        return sprintf('%.1f %s', $bytes / pow(1024, $i), $units[$i]);
    }

    private function send(array $msg): void
    {
        $json = json_encode($msg, JSON_UNESCAPED_UNICODE);
        if ($json === false) return;
        fwrite(STDOUT, $json . "\n");
        fflush(STDOUT);
    }

    private function log(string $msg): void
    {
        fwrite(STDERR, "[info] $msg\n");
        fflush(STDERR);
    }
}

$server = new ProjectMcpServer();
$server->run();
