<?php
require_once __DIR__ . '/Database.php';

/**
 * Gestor de mapeo entre IDs de ZKTeco y empleados del sistema
 * Crea una tabla temporal para optimizar las búsquedas
 */
class ZKTecoMappingManager {
    private $db;
    private $columnasEmpleadoCache = [];
    
    public function __construct($db = null) {
        $this->db = $db ?? new Database();
    }
    
    /**
     * Crea la tabla de mapeo ZKTeco-ID a Empleado-ID
     */
    public function crearTablaMapeo() {
        $sql = "
            CREATE TABLE IF NOT EXISTS zkteo_empleado_mapeo (
                id INT AUTO_INCREMENT PRIMARY KEY,
                zkteo_id VARCHAR(50) NOT NULL COMMENT 'ID original del dispositivo ZKTeco',
                empleado_id INT NULL COMMENT 'ID del empleado en sistema',
                numero_empleado VARCHAR(50) NULL COMMENT 'Número de empleado en sistema',
                nombre_empleado VARCHAR(200) NULL COMMENT 'Nombre completo del empleado',
                rfc VARCHAR(20) NULL COMMENT 'RFC del empleado',
                area VARCHAR(100) NULL COMMENT 'Área del empleado',
                metodo_creacion VARCHAR(50) NULL COMMENT 'manual, automatico, similitud, temporal',
                notas TEXT NULL,
                fecha_mapeo TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del mapeo',
                fecha_modificacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                activo BOOLEAN DEFAULT TRUE COMMENT 'Mapeo activo',
                INDEX (zkteo_id),
                INDEX (empleado_id),
                INDEX (numero_empleado),
                INDEX (activo),
                UNIQUE KEY uk_zkteo_empleado (zkteo_id, empleado_id, activo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Tabla de mapeo entre IDs ZKTeco y empleados del sistema'
        ";
        
        echo "Creando tabla de mapeo ZKTeco...\n";
        $this->db->getConnection()->exec($sql);
        echo "✅ Tabla zkteo_empleado_mapeo creada exitosamente\n";
    }
    
    /**
     * Analiza todos los IDs de ZKTeco del archivo y genera mapeos automáticos
     */
    public function generarMapeosAutomaticos($archivoLogs) {
        echo "Analizando archivo para generar mapeos automáticos...\n";
        
        $idsExistentes = [];
        if ($this->empleadosTieneColumna('zkteo_id')) {
            $sql = "SELECT DISTINCT zkteo_id FROM empleados WHERE zkteo_id IS NOT NULL AND zkteo_id != ''";
            $stmt = $this->db->getConnection()->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $idsExistentes[] = $row['zkteo_id'];
            }
        }
        
        // Leer el archivo de logs para obtener IDs de ZKTeco
        $lines = file($archivoLogs, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $idsZKTeco = [];
        
        foreach ($lines as $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) >= 1) {
                $zkteoId = trim($parts[0]);
                if (!in_array($zkteoId, $idsZKTeco) && !in_array($zkteoId, $idsExistentes)) {
                    $idsZKTeco[$zkteoId] = 0; // Contador para saber cuántas veces aparece
                }
            }
        }
        
        echo "IDs de ZKTeco encontrados en logs: " . count($idsZKTeco) . "\n";
        echo "IDs ya mapeados: " . count($idsExistentes) . "\n";
        
        $mapeosNuevos = array_diff(array_keys($idsZKTeco), $idsExistentes);
        
        if (count($mapeosNuevos) > 0) {
            echo "Generando mapeos para " . count($mapeosNuevos) . " nuevos IDs de ZKTeco...\n";
            
            foreach ($mapeosNuevos as $zkteoId) {
                $this->generarMapeoAutomatico($zkteoId, $idsZKTeco[$zkteoId]);
            }
        } else {
            echo "✅ Todos los IDs de ZKTeco ya están mapeados\n";
        }
        
        return [
            'total_encontrados' => count($idsZKTeco),
            'ya_mapeados' => count($idsExistentes),
            'nuevos_mapeos' => count($mapeosNuevos)
        ];
    }
    
    /**
     * Genera un mapeo automático para un ID de ZKTeco
     */
    private function generarMapeoAutomatico($zkteoId, $conteo) {
        echo "Generando mapeo para ZKTeco-ID: {$zkteoId} ({$conteo} registros)...\n";
        
        // Estrategia 1: Buscar por coincidencia exacta en diferentes campos
        $condiciones = [
            "id = ?",
            "CAST(id AS CHAR) = ?",
            "rfc = ?",
            "CONCAT(nombre, ' ', apellido) = ?"
        ];
        $params = [$zkteoId, $zkteoId, $zkteoId, $zkteoId];
        if ($this->empleadosTieneColumna('zkteo_id')) {
            $condiciones[] = "zkteo_id = ?";
            $params[] = $zkteoId;
        }
        if ($this->empleadosTieneColumna('numero_empleado')) {
            $condiciones[] = "numero_empleado = ?";
            $params[] = $zkteoId;
        }

        $sql = "
            SELECT id, nombre, apellido, rfc, area
            FROM empleados 
            WHERE " . implode(" OR ", $condiciones) . "
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empleado) {
            $this->insertarMapeo($zkteoId, $empleado, 'automatico');
            echo "  ✅ Mapeo automático exitoso: {$empleado['nombre']} {$empleado['apellido']} (ID: {$empleado['id']})\n";
            return true;
        }
        
        // Estrategia 2: Búsqueda por similitud (si es numérico)
        if (is_numeric($zkteoId)) {
            $sql = "
                SELECT id, nombre, apellido, rfc, area,
                       (CASE 
                           WHEN rfc LIKE CONCAT('%', ?, '%') THEN 1
                           WHEN CAST(id AS CHAR) LIKE CONCAT('%', ?, '%') THEN 1
                           ELSE 0
                        END) AS coincidencia
                FROM empleados 
                WHERE rfc LIKE CONCAT('%', ?, '%')
                   OR CAST(id AS CHAR) LIKE CONCAT('%', ?, '%')
                HAVING coincidencia > 0
                ORDER BY coincidencia DESC, LENGTH(CAST(id AS CHAR))
                LIMIT 1
            ";
            $params = [$zkteoId, $zkteoId, $zkteoId, $zkteoId];
            if ($this->empleadosTieneColumna('numero_empleado')) {
                $sql = "
                    SELECT id, nombre, apellido, rfc, area,
                           (CASE 
                               WHEN numero_empleado LIKE CONCAT('%', ?, '%') THEN 1
                               WHEN rfc LIKE CONCAT('%', ?, '%') THEN 1
                               WHEN CAST(id AS CHAR) LIKE CONCAT('%', ?, '%') THEN 1
                               ELSE 0
                            END) AS coincidencia
                    FROM empleados 
                    WHERE numero_empleado LIKE CONCAT('%', ?, '%')
                       OR rfc LIKE CONCAT('%', ?, '%')
                       OR CAST(id AS CHAR) LIKE CONCAT('%', ?, '%')
                    HAVING coincidencia > 0
                    ORDER BY coincidencia DESC, LENGTH(numero_empleado)
                    LIMIT 1
                ";
                $params = [$zkteoId, $zkteoId, $zkteoId, $zkteoId, $zkteoId, $zkteoId];
            }
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($empleado) {
                $this->insertarMapeo($zkteoId, $empleado, 'similitud');
                echo "  ✅ Mapeo por similitud: {$empleado['nombre']} {$empleado['apellido']} (ID: {$empleado['id']})\n";
                return true;
            }
        }
        
        // Estrategia 3: Crear registro temporal para mapeo manual
        echo "  ⚠️ No se encontró empleado para ZKTeco-ID: {$zkteoId}\n";
        $this->insertarMapeoTemporal($zkteoId, $conteo);
        return false;
    }
    
    /**
     * Inserta un mapeo en la tabla
     */
    public function insertarMapeo($zkteoId, $empleado, $metodo) {
        $sql = "
            INSERT INTO zkteo_empleado_mapeo 
            (zkteo_id, empleado_id, numero_empleado, nombre_empleado, rfc, area, metodo_creacion)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                empleado_id = VALUES(empleado_id),
                numero_empleado = VALUES(numero_empleado),
                nombre_empleado = VALUES(nombre_empleado),
                rfc = VALUES(rfc),
                area = VALUES(area),
                fecha_modificacion = CURRENT_TIMESTAMP
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $zkteoId,
            $empleado['id'],
            $empleado['numero_empleado'] ?? null,
            trim($empleado['nombre'] . ' ' . $empleado['apellido']),
            $empleado['rfc'],
            $empleado['area'],
            $metodo
        ]);
    }

    private function empleadosTieneColumna($columna) {
        if (array_key_exists($columna, $this->columnasEmpleadoCache)) {
            return $this->columnasEmpleadoCache[$columna];
        }
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'empleados'
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$columna]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->columnasEmpleadoCache[$columna] = ((int)($row['total'] ?? 0)) > 0;
        return $this->columnasEmpleadoCache[$columna];
    }
    
    /**
     * Inserta un registro temporal para mapeo manual posterior
     */
    private function insertarMapeoTemporal($zkteoId, $conteo) {
        $sql = "
            INSERT INTO zkteo_empleado_mapeo 
            (zkteo_id, metodo_creacion, notas) 
            VALUES (?, 'temporal', ?)
            ON DUPLICATE KEY UPDATE
                fecha_modificacion = CURRENT_TIMESTAMP,
                notas = VALUES(notas)
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $zkteoId,
            "ID sin mapeo. {$conteo} registros en logs. Requiere mapeo manual."
        ]);
    }
    
    /**
     * Obtiene el ID de empleado mapeado para un ID de ZKTeco
     */
    public function getEmpleadoIdPorZKTeco($zkteoId) {
        $sql = "
            SELECT empleado_id, numero_empleado, nombre_empleado, area
            FROM zkteo_empleado_mapeo 
            WHERE zkteo_id = ? AND activo = TRUE
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$zkteoId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Genera reporte de mapeos
     */
    public function generarReporteMapeos() {
        echo "\n=== REPORTE DE MAPEOS ZKTeco ===\n";
        
        $sql = "
            SELECT 
                metodo_creacion,
                COUNT(*) as conteo,
                COUNT(CASE WHEN empleado_id IS NOT NULL THEN 1 END) as mapeados,
                COUNT(CASE WHEN empleado_id IS NULL THEN 1 END) as sin_mapeo
            FROM zkteo_empleado_mapeo
            GROUP BY metodo_creacion
            ORDER BY conteo DESC
        ";
        
        $stmt = $this->db->getConnection()->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['metodo_creacion']}: {$row['conteo']} registros\n";
            echo "  ✅ Mapeados: {$row['mapeados']}\n";
            echo "  ⚠️ Sin mapeo: {$row['sin_mapeo']}\n\n";
        }
        
        // Mostrar IDs sin mapeo
        $sql = "
            SELECT zkteo_id, notas, COUNT(*) as registros_logs
            FROM zkteo_empleado_mapeo 
            WHERE empleado_id IS NULL
            GROUP BY zkteo_id, notas
            ORDER BY registros_logs DESC
            LIMIT 20
        ";
        
        echo "IDs ZKTeco sin mapeo (Top 20):\n";
        $stmt = $this->db->getConnection()->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- ZKTeco-ID {$row['zkteo_id']}: {$row['registros_logs']} registros\n";
            echo "  {$row['notas']}\n";
        }
    }
    
    /**
     * Actualiza un mapeo manual existente
     */
    public function actualizarMapeoManual($zkteoId, $empleadoId) {
        $sql = "
            SELECT id, nombre, apellido, rfc, area, numero_empleado
            FROM empleados 
            WHERE id = ?
            LIMIT 1
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$empleadoId]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empleado) {
            $this->insertarMapeo($zkteoId, $empleado, 'manual_actualizado');
            return [
                'success' => true,
                'empleado' => $empleado
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Empleado no encontrado'
        ];
    }
}
