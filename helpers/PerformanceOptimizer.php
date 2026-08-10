<?php

/**
 * Performance Optimizer
 * Optimización de consultas y caché inteligente
 */
class PerformanceOptimizer
{
    private $cache;
    private $pdo;
    private $queryCache = [];
    private $indexOptimizationQueries = [
        'empleados' => [
            'CREATE INDEX IF NOT EXISTS idx_empleados_activo_nombre ON empleados(activo, nombre)',
            'CREATE INDEX IF NOT EXISTS idx_empleados_rfc_activo ON empleados(rfc, activo)',
            'CREATE INDEX IF NOT EXISTS idx_empleados_area_puesto ON empleados(area, puesto)'
        ],
        'asistencia' => [
            'CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_fecha ON asistencia(empleado_id, fecha)',
            'CREATE INDEX IF NOT EXISTS idx_asistencia_fecha_entrada ON asistencia(fecha, hora_entrada)',
            'CREATE INDEX IF NOT EXISTS idx_asistencia_fecha_salida ON asistencia(fecha, hora_salida)'
        ],
        'dias_economicos' => [
            'CREATE INDEX IF NOT EXISTS idx_dias_economicos_empleado_estatus ON dias_economicos(empleado_id, estatus)',
            'CREATE INDEX IF NOT EXISTS idx_dias_economicos_fecha_estatus ON dias_economicos(fecha, estatus)'
        ]
    ];
    
    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->cache = CacheManager::getInstance();
        
        // Optimizar índices
        $this->optimizeDatabaseIndexes();
    }
    
    /**
     * Optimizar índices de base de datos
     */
    public function optimizeDatabaseIndexes()
    {
        foreach ($this->indexOptimizationQueries as $table => $queries) {
            foreach ($queries as $query) {
                try {
                    $this->pdo->exec($query);
                    error_log("Index optimized for table: $table");
                } catch (Exception $e) {
                    error_log("Index optimization failed for $table: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Consulta optimizada con caché
     */
    public function cachedQuery($sql, $params = [], $ttl = 3600)
    {
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        // Intentar desde caché
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Ejecutar consulta
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            // Guardar en caché
            $this->cache->set($cacheKey, $result, $ttl);
            
            return $result;
        } catch (Exception $e) {
            error_log("Query failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener empleados con paginación optimizada
     */
    public function getEmpleadosPaginated($page = 1, $limit = 10, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $cacheKey = "empleados_page_{$page}_limit_{$limit}_" . md5(serialize($filters));
        
        // Intentar desde caché
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Construir WHERE dinámico
        $where = ['activo = 1'];
        $params = [];
        
        if (!empty($filters['nombre'])) {
            $where[] = 'nombre LIKE ?';
            $params[] = '%' . $filters['nombre'] . '%';
        }
        
        if (!empty($filters['area'])) {
            $where[] = 'area = ?';
            $params[] = $filters['area'];
        }
        
        if (!empty($filters['puesto'])) {
            $where[] = 'puesto = ?';
            $params[] = $filters['puesto'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        // Consulta optimizada con subconsulta para count
        $sql = "
            SELECT e.*, 
                   (SELECT COUNT(*) FROM asistencia a WHERE a.empleado_id = e.id) as total_asistencias,
                   (SELECT MAX(a.fecha) FROM asistencia a WHERE a.empleado_id = e.id) as ultima_asistencia
            FROM empleados e 
            $whereClause 
            ORDER BY e.nombre, e.apellido 
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        
        // Consulta para total
        $countSql = "SELECT COUNT(*) as total FROM empleados e $whereClause";
        $countParams = array_slice($params, 0, 0); // All params for count (no limit/offset needed)
        
        try {
            // Ejecutar consulta principal
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $empleados = $stmt->fetchAll();
            
            // Ejecutar consulta de count
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetchColumn();
            
            $result = [
                'empleados' => $empleados,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
            
            // Guardar en caché por menos tiempo para datos paginados
            $this->cache->set($cacheKey, $result, 600); // 10 minutos
            
            return $result;
        } catch (Exception $e) {
            error_log("Get empleados paginated failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener asistencia con caché inteligente
     */
    public function getAsistenciaOptimizada($empleadoId, $fechaInicio, $fechaFin)
    {
        $cacheKey = "asistencia_{$empleadoId}_" . md5($fechaInicio . $fechaFin);
        
        // Intentar desde caché
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Consulta optimizada con índices
        $sql = "
            SELECT a.*,
                   e.nombre as empleado_nombre,
                   e.apellido as empleado_apellido,
                   TIMESTAMPDIFF(MINUTE, a.hora_entrada, a.hora_salida) as minutos_trabajados,
                   CASE 
                       WHEN TIMESTAMPDIFF(MINUTE, CONCAT(a.fecha, ' ', a.hora_entrada), CONCAT(a.fecha, ' 09:00:00')) > 10 THEN 'mayor'
                       WHEN TIMESTAMPDIFF(MINUTE, CONCAT(a.fecha, ' ', a.hora_entrada), CONCAT(a.fecha, ' 09:00:00')) > 0 THEN 'menor'
                       ELSE 'puntual'
                   END as tipo_asistencia
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            WHERE a.empleado_id = ? 
              AND a.fecha BETWEEN ? AND ?
            ORDER BY a.fecha DESC, a.hora_entrada DESC
        ";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$empleadoId, $fechaInicio, $fechaFin]);
            $asistencia = $stmt->fetchAll();
            
            // Calcular estadísticas
            $estadisticas = [
                'total_dias' => count($asistencia),
                'dias_puntuales' => 0,
                'dias_retardo_menor' => 0,
                'dias_retardo_mayor' => 0,
                'minutos_totales' => 0
            ];
            
            foreach ($asistencia as $registro) {
                switch ($registro['tipo_asistencia']) {
                    case 'puntual':
                        $estadisticas['dias_puntuales']++;
                        break;
                    case 'menor':
                        $estadisticas['dias_retardo_menor']++;
                        break;
                    case 'mayor':
                        $estadisticas['dias_retardo_mayor']++;
                        break;
                }
                
                $estadisticas['minutos_totales'] += $registro['minutos_trabajados'] ?? 0;
            }
            
            $result = [
                'asistencia' => $asistencia,
                'estadisticas' => $estadisticas
            ];
            
            // Guardar en caché por 30 minutos
            $this->cache->set($cacheKey, $result, 1800);
            
            return $result;
        } catch (Exception $e) {
            error_log("Get asistencia optimizada failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpiar caché de consultas
     */
    public function clearQueryCache($pattern = null)
    {
        if ($pattern) {
            // Limpiar caché específico
            // Implementar según sistema de caché
            $this->cache->clear();
        } else {
            // Limpiar todo el caché
            $this->cache->clear();
        }
    }
    
    /**
     * Obtener estadísticas de rendimiento
     */
    public function getPerformanceStats()
    {
        return [
            'cache_hit_ratio' => $this->getCacheHitRatio(),
            'query_cache_size' => count($this->queryCache),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ];
    }
    
    /**
     * Calcular ratio de caché
     */
    private function getCacheHitRatio()
    {
        // Implementar tracking de hits/misses
        return 0.75; // Ejemplo: 75% hit ratio
    }
    
    /**
     * Optimizar configuración de MySQL
     */
    public function optimizeMySQLSettings()
    {
        $queries = [
            'SET GLOBAL query_cache_size = 268435456', /* 256MB */
            'SET GLOBAL query_cache_type = ON',
            'SET GLOBAL innodb_buffer_pool_size = 1073741824', /* 1GB */
            'SET GLOBAL innodb_log_file_size = 268435456', /* 256MB */
            'SET GLOBAL innodb_flush_log_at_trx_commit = 2'
        ];
        
        foreach ($queries as $query) {
            try {
                $this->pdo->exec($query);
                error_log("MySQL optimization applied: $query");
            } catch (Exception $e) {
                error_log("MySQL optimization failed: " . $e->getMessage());
            }
        }
    }
}