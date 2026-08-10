<?php
/**
 * MariaDB MCP Server for sistema_biometrico
 * 
 * Implements the Model Context Protocol (MCP) over stdio
 * to allow AI tools to query the MariaDB database safely.
 * 
 * Usage: php .opencode/mcp/mariadb-mcp.php
 */

declare(strict_types=1);

define('DB_HOST', getenv('MCP_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('MCP_DB_USER') ?: 'root');
define('DB_PASS', getenv('MCP_DB_PASS') ?: 'root');
define('DB_NAME', getenv('MCP_DB_NAME') ?: 'sistema_biometrico');
define('DB_PORT', getenv('MCP_DB_PORT') ?: '3306');
define('READ_ONLY', getenv('MCP_DB_READ_ONLY') !== 'false');
define('MAX_ROWS', intval(getenv('MCP_DB_MAX_ROWS') ?: '500'));
define('PROJECT_ROOT', getenv('MCP_PROJECT_ROOT') ?: (__DIR__ . '/../..'));

class MariaMcpServer
{
    private ?PDO $pdo = null;

    public function run(): void
    {
        $this->initDatabase();
        $this->listen();
    }

    private function initDatabase(): void
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $this->log("Connected to MariaDB: $version");
        } catch (PDOException $e) {
            $this->log('error', 'Database connection failed: ' . $e->getMessage());
            exit(1);
        }
    }

    private function getTools(): array
    {
        return [
            [
                'name' => 'list_tables',
                'description' => 'List all tables in the database with engine, row counts, and sizes',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'describe_table',
                'description' => 'Show column details, indexes, indexes, and foreign keys for a table',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'table' => ['type' => 'string', 'description' => 'Table name']
                    ],
                    'required' => ['table']
                ]
            ],
            [
                'name' => 'show_create_table',
                'description' => 'Show the CREATE TABLE statement for a table',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'table' => ['type' => 'string', 'description' => 'Table name']
                    ],
                    'required' => ['table']
                ]
            ],
            [
                'name' => 'query',
                'description' => 'Execute a read-only SQL SELECT/SHOW/DESCRIBE/EXPLAIN/WITH query',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => ['type' => 'string', 'description' => 'SQL query (SELECT/SHOW/DESCRIBE/EXPLAIN/WITH only)']
                    ],
                    'required' => ['sql']
                ]
            ],
            [
                'name' => 'query_json',
                'description' => 'Execute SELECT and return results as JSON array (useful for data processing)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => ['type' => 'string', 'description' => 'SELECT SQL query']
                    ],
                    'required' => ['sql']
                ]
            ],
            [
                'name' => 'sample_rows',
                'description' => 'Show sample rows from a table (first 10 by default)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'table' => ['type' => 'string', 'description' => 'Table name'],
                        'limit' => ['type' => 'number', 'description' => 'Number of rows (default: 10, max: 50)']
                    ],
                    'required' => ['table']
                ]
            ],
            [
                'name' => 'table_indexes',
                'description' => 'Show all indexes for a table',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'table' => ['type' => 'string', 'description' => 'Table name']
                    ],
                    'required' => ['table']
                ]
            ],
            [
                'name' => 'search',
                'description' => 'Search tables and columns by name pattern',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'pattern' => ['type' => 'string', 'description' => 'Search pattern (e.g. empleado, retardo, justificacion)']
                    ],
                    'required' => ['pattern']
                ]
            ],
            [
                'name' => 'stats',
                'description' => 'Show database-wide statistics: table sizes, total rows, data/index sizes',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'find_foreign_refs',
                'description' => 'Find all tables that reference a given table via foreign key',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'table' => ['type' => 'string', 'description' => 'Referenced table name']
                    ],
                    'required' => ['table']
                ]
            ],
            [
                'name' => 'find_employee',
                'description' => 'Search employees by name, ID, or CURP',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Search term (name/ID/CURP/RFC)'],
                        'limit' => ['type' => 'number', 'description' => 'Max results (default: 20)']
                    ],
                    'required' => ['query']
                ]
            ],
            [
                'name' => 'employee_detail',
                'description' => 'Show full detail of an employee: personal data, area, jefe, stats summary',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'number', 'description' => 'Employee ID']
                    ],
                    'required' => ['id']
                ]
            ],
            [
                'name' => 'user_detail',
                'description' => 'Show user account details: username, role, linked employee, permissions',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'number', 'description' => 'User ID (optional)'],
                        'username' => ['type' => 'string', 'description' => 'Username (optional)']
                    ]
                ]
            ],
            [
                'name' => 'db_summary',
                'description' => 'Show database summary: server version, total tables, total rows, data size, index size',
                'inputSchema' => ['type' => 'object', 'properties' => []]
            ],
            [
                'name' => 'find_orphan_records',
                'description' => 'Find orphan records in key tables (e.g. retardos with no active employee)',
                'inputSchema' => ['type' => 'object', 'properties' => []]
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

            $response = $this->dispatch($msg);
            if ($response !== null) {
                $this->send($response);
            }
        }
    }

    private function dispatch(array $msg): ?array
    {
        $method = $msg['method'];
        $params = $msg['params'] ?? [];
        $id = $msg['id'] ?? null;

        return match ($method) {
            'initialize' => $this->onInitialize($params, $id),
            'notifications/initialized' => null,
            'ping' => ['jsonrpc' => '2.0', 'id' => $id, 'result' => (object)[]],
            'tools/list' => ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['tools' => $this->getTools()]],
            'tools/call' => $this->onToolCall($params, $id),
            default => ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => "Unknown method: $method"]]
        };
    }

    private function onInitialize(array $params, ?int $id): array
    {
        return [
            'jsonrpc' => '2.0', 'id' => $id, 'result' => [
                'protocolVersion' => $params['protocolVersion'] ?? '2024-11-05',
                'capabilities' => ['tools' => (object)[]],
                'serverInfo' => ['name' => 'mariadb-mcp', 'version' => '2.0.0']
            ]
        ];
    }

    private function onToolCall(array $params, ?int $id): array
    {
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        try {
            $result = match ($name) {
                'list_tables' => $this->listTables(),
                'describe_table' => $this->describeTable($args),
                'show_create_table' => $this->showCreateTable($args),
                'query' => $this->query($args),
                'query_json' => $this->queryJson($args),
                'sample_rows' => $this->sampleRows($args),
                'table_indexes' => $this->tableIndexes($args),
                'search' => $this->search($args),
                'stats' => $this->stats(),
                'find_foreign_refs' => $this->findForeignRefs($args),
                'find_employee' => $this->findEmployee($args),
                'employee_detail' => $this->employeeDetail($args),
                'user_detail' => $this->userDetail($args),
                'db_summary' => $this->dbSummary(),
                'find_orphan_records' => $this->findOrphanRecords(),
                default => throw new \RuntimeException("Unknown tool: $name")
            };

            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['content' => [['type' => 'text', 'text' => $result]]]];
        } catch (\Throwable $e) {
            return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32000, 'message' => $e->getMessage()]];
        }
    }

    private function listTables(): string
    {
        $tables = $this->pdo->query("
            SELECT TABLE_NAME, ENGINE,
                   CAST(COALESCE(TABLE_ROWS, 0) AS SIGNED) as TABLE_ROWS,
                   DATA_LENGTH, INDEX_LENGTH, TABLE_COLLATION
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = " . $this->pdo->quote(DB_NAME) . "
            ORDER BY TABLE_NAME
        ")->fetchAll();

        $out = "=== Tables in `" . DB_NAME . "` ===\n\n";
        $out .= sprintf("%-35s %-10s %-10s %-12s %s\n", 'Table', 'Engine', 'Rows', 'Size', 'Collation');
        $out .= str_repeat('-', 85) . "\n";
        $tr = 0; $ts = 0;
        foreach ($tables as $t) {
            $r = intval($t['TABLE_ROWS'] ?? 0); $tr += $r;
            $size = intval($t['DATA_LENGTH'] ?? 0) + intval($t['INDEX_LENGTH'] ?? 0); $ts += $size;
            $out .= sprintf("%-35s %-10s %-10s %-12s %s\n",
                $t['TABLE_NAME'], $t['ENGINE'] ?? '?', number_format($r), $this->fmt($size), $t['TABLE_COLLATION'] ?? '');
        }
        $out .= str_repeat('-', 85) . "\n";
        $out .= sprintf("%-35s %-10s %-10s %-12s\n", 'TOTAL', '', number_format($tr), $this->fmt($ts));
        return $out;
    }

    private function describeTable(array $args): string
    {
        $table = $args['table'] ?? '';
        if (!$table) throw new \RuntimeException('table is required');

        $cols = $this->pdo->query("DESCRIBE `$table`")->fetchAll();
        $fk = $this->pdo->query("
            SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = " . $this->pdo->quote(DB_NAME) . "
              AND TABLE_NAME = " . $this->pdo->quote($table) . "
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll();

        $out = "=== Table: `$table` ===\n\n";
        $out .= sprintf("%-25s %-35s %-6s %-6s %-20s %s\n", 'Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
        $out .= str_repeat('-', 120) . "\n";
        foreach ($cols as $c) {
            $out .= sprintf("%-25s %-35s %-6s %-6s %-20s %s\n",
                $c['Field'], $c['Type'], $c['Null'], $c['Key'],
                $c['Default'] ?? 'NULL', $c['Extra']);
        }

        if ($fk) {
            $out .= "\nFOREIGN KEYS:\n";
            foreach ($fk as $f) {
                $out .= sprintf("  %s → %s(%s)\n", $f['COLUMN_NAME'], $f['REFERENCED_TABLE_NAME'], $f['REFERENCED_COLUMN_NAME']);
            }
        }
        return $out;
    }

    private function showCreateTable(array $args): string
    {
        $table = $args['table'] ?? '';
        if (!$table) throw new \RuntimeException('table is required');
        $stmt = $this->pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch();
        return $row['Create Table'] ?? throw new \RuntimeException("Table not found: $table");
    }

    private function query(array $args): string
    {
        $sql = trim($args['sql'] ?? '');
        if (!$sql) throw new \RuntimeException('sql is required');

        $upper = strtoupper($sql);
        $allowed = ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN', 'WITH'];
        $ok = false;
        foreach ($allowed as $prefix) {
            if (str_starts_with($upper, $prefix)) { $ok = true; break; }
        }
        if (!$ok) throw new \RuntimeException('Only read-only queries allowed');

        $rows = $this->pdo->query($sql)->fetchAll();
        if (!$rows) return "0 rows returned.";

        $truncated = false;
        if (count($rows) > MAX_ROWS) {
            $rows = array_slice($rows, 0, MAX_ROWS);
            $truncated = true;
        }

        $cols = array_keys($rows[0]);
        $widths = [];
        foreach ($cols as $col) {
            $widths[$col] = strlen($col);
            foreach ($rows as $r) {
                $v = (string)($r[$col] ?? '');
                $widths[$col] = min(max($widths[$col], strlen($v)), 60);
            }
        }

        $out = '';
        foreach ($cols as $col) $out .= str_pad($col, $widths[$col] + 2);
        $out = rtrim($out) . "\n";
        foreach ($cols as $col) $out .= str_repeat('-', $widths[$col] + 2);
        $out = rtrim($out) . "\n";

        foreach ($rows as $r) {
            foreach ($cols as $col) {
                $v = (string)($r[$col] ?? 'NULL');
                if (strlen($v) > 60) $v = substr($v, 0, 57) . '...';
                $out .= str_pad($v, $widths[$col] + 2);
            }
            $out = rtrim($out) . "\n";
        }

        $out .= "\n(" . count($rows) . " rows" . ($truncated ? " truncated to " . MAX_ROWS : "") . ")\n";
        return $out;
    }

    private function queryJson(array $args): string
    {
        $sql = trim($args['sql'] ?? '');
        if (!$sql) throw new \RuntimeException('sql is required');

        $upper = strtoupper($sql);
        if (!str_starts_with($upper, 'SELECT') && !str_starts_with($upper, 'WITH')) {
            throw new \RuntimeException('Only SELECT/WITH queries supported for JSON output');
        }

        $rows = $this->pdo->query($sql)->fetchAll();
        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '[]';
    }

    private function sampleRows(array $args): string
    {
        $table = $args['table'] ?? '';
        if (!$table) throw new \RuntimeException('table is required');
        $limit = min(intval($args['limit'] ?? 10), 50);

        $stmt = $this->pdo->query("SELECT * FROM `$table` LIMIT $limit");
        $rows = $stmt->fetchAll();
        if (!$rows) return "No data in `$table`";

        $cols = array_keys($rows[0]);
        $widths = [];
        foreach ($cols as $col) {
            $widths[$col] = strlen($col);
            foreach ($rows as $r) {
                $v = (string)($r[$col] ?? '');
                $widths[$col] = min(max($widths[$col], strlen($v)), 40);
            }
        }

        $out = "=== Sample rows from `$table` ===\n\n";
        foreach ($cols as $col) $out .= str_pad($col, $widths[$col] + 2);
        $out = rtrim($out) . "\n";
        foreach ($cols as $col) $out .= str_repeat('-', $widths[$col] + 2);
        $out = rtrim($out) . "\n";

        foreach ($rows as $r) {
            foreach ($cols as $col) {
                $v = (string)($r[$col] ?? 'NULL');
                if (strlen($v) > 40) $v = substr($v, 0, 37) . '...';
                $out .= str_pad($v, $widths[$col] + 2);
            }
            $out = rtrim($out) . "\n";
        }
        return $out;
    }

    private function tableIndexes(array $args): string
    {
        $table = $args['table'] ?? '';
        if (!$table) throw new \RuntimeException('table is required');

        $indexes = $this->pdo->query("SHOW INDEX FROM `$table`")->fetchAll();
        if (!$indexes) return "No indexes on `$table`";

        $out = "=== Indexes on `$table` ===\n\n";
        $out .= sprintf("%-20s %-10s %-20s %-10s %s\n", 'Key name', 'Unique', 'Column', 'Seq', 'Cardinality');
        $out .= str_repeat('-', 75) . "\n";
        foreach ($indexes as $idx) {
            $out .= sprintf("%-20s %-10s %-20s %-10s %s\n",
                $idx['Key_name'],
                $idx['Non_unique'] ? 'NO' : 'YES',
                $idx['Column_name'],
                $idx['Seq_in_index'],
                number_format($idx['Cardinality'] ?? 0)
            );
        }
        return $out;
    }

    private function search(array $args): string
    {
        $pattern = $args['pattern'] ?? '';
        if (!$pattern) throw new \RuntimeException('pattern is required');

        $like = "%$pattern%";
        $stmt = $this->pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND (TABLE_NAME LIKE ? OR COLUMN_NAME LIKE ? OR COLUMN_COMMENT LIKE ?)
            ORDER BY TABLE_NAME, ORDINAL_POSITION
        ");
        $stmt->execute([DB_NAME, $like, $like, $like]);
        $results = $stmt->fetchAll();

        if (!$results) return "No matches for '$pattern'";

        $grouped = [];
        foreach ($results as $r) {
            $grouped[$r['TABLE_NAME']][] = $r;
        }

        $out = "=== Search: '$pattern' ===\n\n";
        foreach ($grouped as $table => $cols) {
            $out .= "$table:\n";
            foreach ($cols as $c) {
                $hint = stripos($c['TABLE_NAME'], $pattern) !== false ? '[table match]' :
                       (stripos($c['COLUMN_COMMENT'] ?? '', $pattern) !== false ? '[comment match]' : '');
                $out .= sprintf("  - %s %s %s\n", $c['COLUMN_NAME'], $c['COLUMN_TYPE'], $hint);
            }
            $out .= "\n";
        }
        return $out;
    }

    private function stats(): string
    {
        $tables = $this->pdo->query("
            SELECT TABLE_NAME,
                   CAST(COALESCE(TABLE_ROWS, 0) AS SIGNED) as TABLE_ROWS,
                   DATA_LENGTH, INDEX_LENGTH
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = " . $this->pdo->quote(DB_NAME) . "
            ORDER BY TABLE_NAME
        ")->fetchAll();

        $out = sprintf("%-35s %-12s %-12s %-12s %s\n", 'Table', 'Rows', 'Data', 'Index', 'Total');
        $out .= str_repeat('-', 85) . "\n";
        $tr = 0; $td = 0; $ti = 0;
        foreach ($tables as $t) {
            $r = intval($t['TABLE_ROWS'] ?? 0); $d = intval($t['DATA_LENGTH'] ?? 0); $i = intval($t['INDEX_LENGTH'] ?? 0);
            $tr += $r; $td += $d; $ti += $i;
            $out .= sprintf("%-35s %-12s %-12s %-12s %s\n",
                $t['TABLE_NAME'], number_format($r), $this->fmt($d), $this->fmt($i), $this->fmt($d + $i));
        }
        $out .= str_repeat('-', 85) . "\n";
        $out .= sprintf("%-35s %-12s %-12s %-12s %s\n",
            'TOTAL', number_format($tr), $this->fmt($td), $this->fmt($ti), $this->fmt($td + $ti));
        return $out;
    }

    private function findForeignRefs(array $args): string
    {
        $table = $args['table'] ?? '';
        if (!$table) throw new \RuntimeException('table is required');

        $refs = $this->pdo->query("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = " . $this->pdo->quote(DB_NAME) . "
              AND REFERENCED_TABLE_NAME = " . $this->pdo->quote($table) . "
              AND TABLE_SCHEMA = " . $this->pdo->quote(DB_NAME) . "
        ")->fetchAll();

        if (!$refs) return "No tables reference `$table`";

        $out = "=== Tables referencing `$table` ===\n\n";
        foreach ($refs as $r) {
            $out .= sprintf("  %s.%s → %s\n", $r['TABLE_NAME'], $r['COLUMN_NAME'], $table);
        }
        return $out;
    }

    private function findEmployee(array $args): string
    {
        $q = $args['query'] ?? '';
        $limit = min(intval($args['limit'] ?? 20), 50);
        if (!$q) throw new \RuntimeException('query is required');

        $like = "%$q%";
        $stmt = $this->pdo->prepare("
            SELECT e.id, e.nombre, e.apellido, e.curp, e.rfc, e.area, e.puesto,
                   e.jefe_directo_clave, e.jefe_directo_id,
                   COALESCE(u.username, '-') as usuario
            FROM empleados e
            LEFT JOIN usuarios u ON e.id = u.empleado_id
            WHERE e.id LIKE ? OR e.nombre LIKE ? OR e.apellido LIKE ? OR e.curp LIKE ? OR e.rfc LIKE ?
            ORDER BY e.nombre, e.apellido
            LIMIT $limit
        ");
        $stmt->execute([$like, $like, $like, $like, $like]);
        $rows = $stmt->fetchAll();

        if (!$rows) return "No employees match '$q'";

        $out = "=== Employees matching '$q' ===\n\n";
        $out .= sprintf("%-5s %-25s %-25s %-20s %-25s %-10s %s\n",
            'ID', 'Name', 'Last', 'CURP', 'Area', 'Usuario', 'Jefe Clave');
        $out .= str_repeat('-', 120) . "\n";
        foreach ($rows as $r) {
            $out .= sprintf("%-5s %-25s %-25s %-20s %-25s %-10s %s\n",
                $r['id'], $r['nombre'], $r['apellido'], $r['curp'] ?? '',
                $r['area'] ?? '', $r['usuario'], $r['jefe_directo_clave'] ?? '');
        }
        return $out;
    }

    private function employeeDetail(array $args): string
    {
        $id = intval($args['id'] ?? 0);
        if (!$id) throw new \RuntimeException('id is required');

        $emp = $this->pdo->prepare("
            SELECT e.*, 
                   CONCAT(je.nombre, ' ', COALESCE(je.apellido, '')) as jefe_nombre,
                   (SELECT username FROM usuarios WHERE empleado_id = e.id LIMIT 1) as usuario,
                   (SELECT COUNT(*) FROM asistencia WHERE empleado_id = e.id) as total_asistencias,
                   (SELECT COUNT(*) FROM retardos WHERE empleado_id = e.id) as total_retardos,
                   (SELECT COUNT(*) FROM comisiones WHERE empleado_id = e.id) as total_comisiones,
                   (SELECT COUNT(*) FROM justificaciones WHERE empleado_id = e.id) as total_justificaciones,
                   (SELECT COUNT(*) FROM vacaciones WHERE empleado_id = e.id) as total_vacaciones
            FROM empleados e
            LEFT JOIN empleados je ON e.jefe_directo_id = je.id
            WHERE e.id = ?
        ");
        $emp->execute([$id]);
        $e = $emp->fetch();
        if (!$e) throw new \RuntimeException("Employee $id not found");

        $out = "=== Employee #{$e['id']} ===\n";
        $out .= str_repeat('=', 60) . "\n\n";
        $out .= "Name:   {$e['nombre']} {$e['apellido']}\n";
        $out .= "CURP:   {$e['curp']}\n";
        $out .= "RFC:    {$e['rfc']}\n";
        $out .= "Area:   {$e['area']}\n";
        $out .= "Puesto: {$e['puesto']}\n";
        $out .= "Clave:  {$e['clave_presupuestal']}\n";
        $out .= "Jefe:   {$e['jefe_nombre']} (id:{$e['jefe_directo_id']}, clave:{$e['jefe_directo_clave']})\n";
        $out .= "User:   {$e['usuario']}\n\n";
        $out .= "Stats:\n";
        $out .= "  Asistencias:    {$e['total_asistencias']}\n";
        $out .= "  Retardos:       {$e['total_retardos']}\n";
        $out .= "  Comisiones:     {$e['total_comisiones']}\n";
        $out .= "  Justificaciones: {$e['total_justificaciones']}\n";
        $out .= "  Vacaciones:     {$e['total_vacaciones']}\n";
        return $out;
    }

    private function userDetail(array $args): string
    {
        $userId = intval($args['user_id'] ?? 0);
        $username = $args['username'] ?? '';

        if ($userId) {
            $stmt = $this->pdo->prepare("
                SELECT u.*, e.nombre, e.apellido, e.area, e.puesto
                FROM usuarios u
                LEFT JOIN empleados e ON u.empleado_id = e.id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
        } elseif ($username) {
            $stmt = $this->pdo->prepare("
                SELECT u.*, e.nombre, e.apellido, e.area, e.puesto
                FROM usuarios u
                LEFT JOIN empleados e ON u.empleado_id = e.id
                WHERE u.username = ?
            ");
            $stmt->execute([$username]);
        } else {
            $rows = $this->pdo->query("
                SELECT u.id, u.username, u.rol, u.activo, u.empleado_id,
                       CONCAT(e.nombre, ' ', COALESCE(e.apellido, '')) as empleado_nombre
                FROM usuarios u
                LEFT JOIN empleados e ON u.empleado_id = e.id
                ORDER BY u.username
            ")->fetchAll();
            $out = "=== All Users ===\n\n";
            $out .= sprintf("%-5s %-20s %-20s %-10s %-6s %s\n", 'ID', 'Username', 'Nombre', 'Rol', 'Activo', 'Emp.ID');
            $out .= str_repeat('-', 75) . "\n";
            foreach ($rows as $r) {
                $out .= sprintf("%-5s %-20s %-20s %-10s %-6s %s\n",
                    $r['id'], $r['username'], $r['empleado_nombre'] ?? '-',
                    $r['rol'], $r['activo'] ? 'YES' : 'NO', $r['empleado_id'] ?? '-');
            }
            return $out;
        }

        $u = $stmt->fetch();
        if (!$u) throw new \RuntimeException('User not found');

        $out = "=== User: {$u['username']} ===\n";
        $out .= str_repeat('=', 50) . "\n\n";
        $out .= "ID:       {$u['id']}\n";
        $out .= "Username: {$u['username']}\n";
        $out .= "Rol:      {$u['rol']}\n";
        $out .= "Activo:   " . ($u['activo'] ? 'YES' : 'NO') . "\n";
        $out .= "Empleado: {$u['nombre']} {$u['apellido']} (id:{$u['empleado_id']})\n";
        $out .= "Area:     {$u['area']}\n";
        $out .= "Puesto:   {$u['puesto']}\n";
        return $out;
    }

    private function dbSummary(): string
    {
        $db = $this->pdo->query("SELECT VERSION() as v")->fetch();
        $stats = $this->pdo->query("
            SELECT COUNT(*) as tables,
                   CAST(COALESCE(SUM(TABLE_ROWS), 0) AS SIGNED) as total_rows,
                   CAST(COALESCE(SUM(DATA_LENGTH), 0) AS SIGNED) as data_size,
                   CAST(COALESCE(SUM(INDEX_LENGTH), 0) AS SIGNED) as index_size
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = " . $this->pdo->quote(DB_NAME)
        )->fetch();

        $out = "=== Database Summary ===\n\n";
        $out .= "Database:     " . DB_NAME . "\n";
        $out .= "Server:       MariaDB {$db['v']}\n";
        $out .= "Tables:       " . number_format($stats['tables']) . "\n";
        $out .= "Total Rows:   " . number_format($stats['total_rows']) . "\n";
        $out .= "Data Size:    " . $this->fmt(intval($stats['data_size'])) . "\n";
        $out .= "Index Size:   " . $this->fmt(intval($stats['index_size'])) . "\n";
        $out .= "Total Size:   " . $this->fmt(intval($stats['data_size'] + $stats['index_size'])) . "\n";

        $Uptime = $this->pdo->query("SHOW STATUS LIKE 'Uptime'")->fetch();
        if ($Uptime) {
            $days = floor(intval($Uptime['Value']) / 86400);
            $hours = floor((intval($Uptime['Value']) % 86400) / 3600);
            $out .= "Uptime:       {$days}d {$hours}h\n";
        }
        return $out;
    }

    private function findOrphanRecords(): string
    {
        $checks = [
            'retardos without empleado' => "
                SELECT COUNT(*) as c FROM retardos r
                LEFT JOIN empleados e ON r.empleado_id = e.id
                WHERE e.id IS NULL",
            'comisiones without empleado' => "
                SELECT COUNT(*) as c FROM comisiones c
                LEFT JOIN empleados e ON c.empleado_id = e.id
                WHERE e.id IS NULL",
            'justificaciones without empleado' => "
                SELECT COUNT(*) as c FROM justificaciones j
                LEFT JOIN empleados e ON j.empleado_id = e.id
                WHERE e.id IS NULL",
            'validaciones_jefe without empleado' => "
                SELECT COUNT(*) as c FROM validaciones_jefe vj
                LEFT JOIN empleados e ON vj.empleado_id = e.id
                WHERE e.id IS NULL",
            'validaciones_jefe without usuario' => "
                SELECT COUNT(*) as c FROM validaciones_jefe vj
                LEFT JOIN usuarios u ON vj.jefe_id = u.id
                WHERE u.id IS NULL",
            'usuarios linked to deleted empleado' => "
                SELECT COUNT(*) as c FROM usuarios u
                LEFT JOIN empleados e ON u.empleado_id = e.id
                WHERE u.empleado_id IS NOT NULL AND e.id IS NULL",
            'asistencia without empleado' => "
                SELECT COUNT(*) as c FROM asistencia a
                LEFT JOIN empleados e ON a.empleado_id = e.id
                WHERE e.id IS NULL"
        ];

        $out = "=== Orphan Record Check ===\n\n";
        $found = false;
        foreach ($checks as $label => $sql) {
            $stmt = $this->pdo->query($sql);
            $count = intval($stmt->fetchColumn());
            $status = $count > 0 ? "⚠️ $count" : "✅ 0";
            if ($count > 0) $found = true;
            $out .= sprintf("%-45s %s\n", $label, $status);
        }
        if (!$found) $out .= "\nNo orphan records found.\n";
        return $out;
    }

    private function fmt(float|int $bytes): string
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
        if ($json === false) { $this->log('error', 'JSON encode error: ' . json_last_error_msg()); return; }
        fwrite(STDOUT, $json . "\n");
        fflush(STDOUT);
    }

    private function log(string $level = 'info', string $message = ''): void
    {
        if ($message === '') { $message = $level; $level = 'info'; }
        fwrite(STDERR, "[$level] $message\n");
        fflush(STDERR);
    }
}

$server = new MariaMcpServer();
$server->run();
