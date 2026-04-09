<?php
// Sistema de sincronización de empleados desde DAT
class ZKTecoSincronizadorEmpleados {
    private $db;
    private $configuracion;
    
    public function __construct($configuracion = []) {
        require_once 'models/Database.php';
        $this->db = new Database();
        $this->configuracion = $configuracion;
    }
    
    /**
     * Sincroniza empleados desde un archivo DAT
     * 1. Detecta IDs ZKTeco en el archivo
     * 2. Verifica cuáles faltan en la tabla empleados
     * 3. Inserta los empleados faltantes
     * 4. Actualiza el mapeo correspondiente
     */
    public function sincronizarEmpleadosDesdeDAT($filePath) {
        try {
            echo "🔄 Iniciando sincronización de empleados desde DAT...\n";
            
            // Paso 1: Extraer IDs ZKTeco del archivo DAT
            $zkIdsEnDAT = $this->extraerZkIdsDelArchivo($filePath);
            echo "📊 IDs ZKTeco encontrados en DAT: " . count($zkIdsEnDAT) . "\n";
            
            if (empty($zkIdsEnDAT)) {
                echo "❌ No se encontraron IDs ZKTeco en el archivo\n";
                return [
                    'exito' => false,
                    'error' => 'No hay IDs ZKTeco en el archivo',
                    'estadisticas' => []
                ];
            }
            
            // Paso 2: Verificar qué IDs faltan en la tabla empleados
            $idsFaltantes = $this->verificarIdsFaltantes($zkIdsEnDAT);
            echo "📊 IDs que faltan en tabla empleados: " . count($idsFaltantes) . "\n";
            
            if (empty($idsFaltantes)) {
                echo "✅ Todos los IDs ZKTeco ya existen en la tabla empleados\n";
                return [
                    'exito' => true,
                    'mensaje' => 'Sincronización completa - no hay IDs faltantes',
                    'estadisticas' => [
                        'total_ids_encontrados' => count($zkIdsEnDAT),
                        'ids_existente_en_empleados' => count($zkIdsEnDAT),
                        'ids_nuevos_creados' => 0,
                        'mapeos_actualizados' => 0
                    ]
                ];
            }
            
            // Paso 3: Insertar empleados faltantes con datos básicos
            $empleadosInsertados = $this->insertarEmpleadosFaltantes($idsFaltantes);
            echo "✅ Empleados insertados: " . count($empleadosInsertados) . "\n";
            
            // Paso 4: Actualizar/crear mapeos
            $mapeosActualizados = $this->actualizarMapeos($zkIdsEnDAT);
            echo "✅ Mapeos actualizados: " . count($mapeosActualizados) . "\n";
            
            // Paso 5: Verificación final
            $verificacion = $this->verificarSincronizacion($zkIdsEnDAT);
            
            return [
                'exito' => true,
                'mensaje' => 'Sincronización completada exitosamente',
                'estadisticas' => [
                    'total_ids_encontrados' => count($zkIdsEnDAT),
                    'ids_existente_en_empleados' => $verificacion['ids_existente'],
                    'ids_nuevos_creados' => count($empleadosInsertados),
                    'mapeos_actualizados' => count($mapeosActualizados)
                ],
                'empleados_nuevos' => $empleadosInsertados,
                'mapeos_creados' => $mapeosActualizados,
                'verificacion' => $verificacion
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error en sincronización: ' . $e->getMessage(),
                'detalles' => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Extrae todos los IDs ZKTeco únicos del archivo DAT
     */
    private function extraerZkIdsDelArchivo($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Archivo no encontrado: $filePath");
        }
        
        $zkIds = [];
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            throw new Exception("No se puede leer el archivo: $filePath");
        }
        
        while (($linea = fgets($handle)) !== false) {
            $linea = trim($linea);
            if (empty($linea)) continue;
            
            $campos = explode("\t", $linea);
            if (count($campos) >= 1) {
                $zkId = trim($campos[0]);
                if (is_numeric($zkId) && !in_array($zkId, $zkIds)) {
                    $zkIds[] = $zkId;
                }
            }
        }
        
        fclose($handle);
        sort($zkIds);
        
        return $zkIds;
    }
    
    /**
     * Verifica qué IDs ZKTeco faltan en la tabla empleados
     */
    private function verificarIdsFaltantes($zkIds) {
        $idsFaltantes = [];
        
        foreach ($zkIds as $zkId) {
            // Verificar si existe como empleado_id o en el mapeo
            $sql = "
                SELECT 
                    (SELECT COUNT(*) as count FROM empleados WHERE id = ?) as existe_empleado,
                    (SELECT COUNT(*) as count FROM zkteo_empleado_mapeo WHERE zkteo_id = ?) as existe_mapeo
            ";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$zkId, $zkId]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si no existe ni como empleado ni en mapeo, es un nuevo ID
            if ($resultado['existe_empleado'] == 0 && $resultado['existe_mapeo'] == 0) {
                $idsFaltantes[] = $zkId;
            }
        }
        
        return $idsFaltantes;
    }
    
    /**
     * Inserta empleados faltantes con datos básicos generados
     */
    private function insertarEmpleadosFaltantes($idsFaltantes) {
        $empleadosInsertados = [];
        
        foreach ($idsFaltantes as $zkId) {
            // Generar nombre basado en el ID ZKTeco
            $nombre = $this->generarNombreEmpleado($zkId);
            
            // Insertar nuevo empleado
            $sql = "
                INSERT INTO empleados (
                    id, nombre, activo, created_at, updated_at
                ) VALUES (?, ?, 1, NOW(), NOW())
            ";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            
            if ($stmt->execute([$zkId, $nombre])) {
                $empleadosInsertados[] = [
                    'id' => $zkId,
                    'nombre' => $nombre,
                    'zk_empleado_id_original' => $zkId,
                    'creado_desde_dat' => true
                ];
            }
        }
        
        return $empleadosInsertados;
    }
    
    /**
     * Genera nombre de empleado basado en el ID ZKTeco
     */
    private function generarNombreEmpleado($zkId) {
        $apellidos = [
            'García', 'Martínez', 'López', 'González', 'Rodríguez',
            'Sánchez', 'Ramírez', 'Cruz', 'Flores', 'Morales',
            'Reyes', 'Jiménez', 'Mendoza', 'Díaz', 'Hernández'
        ];
        
        $nombres = [
            'Juan', 'María', 'Carlos', 'Ana', 'Luis', 
            'Laura', 'José', 'Sofía', 'Miguel', 'Elena',
            'Roberto', 'Patricia', 'Fernando', 'Gabriela', 'Ricardo'
        ];
        
        $apellido = $apellidos[$zkId % count($apellidos)];
        $nombre = $nombres[($zkId / 3) % count($nombres)];
        
        return "{$nombre} {$apellido} (ID ZK:{$zkId})";
    }
    
    /**
     * Actualiza o crea mapeos para todos los IDs ZKTeco
     */
    private function actualizarMapeos($zkIds) {
        $mapeosActualizados = [];
        
        foreach ($zkIds as $zkId) {
            // Verificar si ya existe mapeo
            $stmt = $this->db->getConnection()->prepare(
                "SELECT empleado_id FROM zkteo_empleado_mapeo WHERE zkteo_id = ?"
            );
            $stmt->execute([$zkId]);
            $mapeoExistente = $stmt->fetch();
            
            if ($mapeoExistente) {
                // Mapeo ya existe, actualizar si es necesario
                if ($mapeoExistente['empleado_id'] != $zkId) {
                    $stmtUpdate = $this->db->getConnection()->prepare(
                        "UPDATE zkteo_empleado_mapeo SET empleado_id = ?, fecha_modificacion = NOW() WHERE zkteo_id = ?"
                    );
                    $stmtUpdate->execute([$zkId, $zkId]);
                    
                    $mapeosActualizados[] = [
                        'zk_empleado_id' => $zkId,
                        'empleado_id' => $zkId,
                        'accion' => 'actualizado'
                    ];
                }
            } else {
                // Crear nuevo mapeo
                $stmtInsert = $this->db->getConnection()->prepare(
                    "INSERT INTO zkteo_empleado_mapeo (zkteo_id, empleado_id, fecha_mapeo, activo, metodo_creacion) VALUES (?, ?, NOW(), 1, 'sincronizador')"
                );
                $stmtInsert->execute([$zkId, $zkId]);
                
                $mapeosActualizados[] = [
                    'zk_empleado_id' => $zkId,
                    'empleado_id' => $zkId,
                    'accion' => 'creado'
                ];
            }
        }
        
        return $mapeosActualizados;
    }
    
    /**
     * Verificación final de la sincronización
     */
    private function verificarSincronizacion($zkIdsOriginales) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as count 
            FROM empleados 
            WHERE id IN (" . implode(',', array_fill(0, count($zkIdsOriginales), '?')) . ")
        ");
        $stmt->execute($zkIdsOriginales);
        $idsExistente = $stmt->fetch()['count'];
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as count 
            FROM zkteo_empleado_mapeo 
            WHERE zkteo_id IN (" . implode(',', array_fill(0, count($zkIdsOriginales), '?')) . ")
        ");
        $stmt->execute($zkIdsOriginales);
        $mapeosTotales = $stmt->fetch()['count'];
        
        return [
            'ids_originales' => count($zkIdsOriginales),
            'ids_existente' => $idsExistente,
            'mapeos_totales' => $mapeosTotales,
            'sincronizacion_completa' => ($idsExistente == count($zkIdsOriginales) && $mapeosTotales == count($zkIdsOriginales))
        ];
    }
}

echo "✅ Clase ZKTecoSincronizadorEmpleados cargada\n";
?>