<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/ZKTecoUniversalParser.php';
require_once __DIR__ . '/models/ZKTecoAsistenciaInserterFinal.php';

/**
 * 🧪 TESTING AUTOMATIZADO COMPLETO PARA SISTEMA ZKTeco
 * Suite de pruebas integrales para validar funcionamiento completo
 */
class ZKTecoTestingSuite {
    private $db;
    private $resultados;
    
    public function __construct() {
        $this->db = new Database();
        $this->resultados = [
            'timestamp' => date('Y-m-d H:i:s'),
            'pruebas' => [],
            'exitosos' => 0,
            'fallidos' => 0,
            'resumen' => []
        ];
    }
    
    /**
     * 🚀 EJECUTAR TODAS LAS PRUEBAS
     */
    public function ejecutarTodasLasPruebas() {
        echo "🧪 INICIANDO TESTING COMPLETO ZKTeco\n";
        echo "=" . str_repeat("=", 59) . "\n\n";
        
        $this->pruebaConexionBaseDatos();
        $this->pruebaEstructuraTablas();
        $this->pruebaMapeoEmpleados();
        $this->pruebaParserZKTeco();
        $this->pruebaInsercionAsistencia();
        $this->pruebaInsercionRetardos();
        $this->pruebaLogicaEntradaSalida();
        $this->pruebaRendimientoMasivo();
        $this->pruebaValidacionDatos();
        $this->pruebaReporteFinal();
        
        $this->generarReporteFinal();
        return $this->resultados;
    }
    
    /**
     * 📊 PRUEBA 1: CONEXIÓN BASE DE DATOS
     */
    private function pruebaConexionBaseDatos() {
        echo "📊 PRUEBA 1: CONEXIÓN BASE DE DATOS\n";
        
        try {
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("SELECT 1 as test");
            $stmt->execute();
            $resultado = $stmt->fetch();
            
            $exitoso = ($resultado['test'] == 1);
            $this->resultados['pruebas'][] = [
                'nombre' => 'Conexión Base de Datos',
                'exitoso' => $exitoso,
                'tiempo' => microtime(true),
                'detalles' => $exitoso ? '✅ Conexión MySQL establecida correctamente' : '❌ Error en conexión'
            ];
            
            echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Conexión a MySQL\n\n";
            
        } catch (Exception $e) {
            $this->resultados['pruebas'][] = [
                'nombre' => 'Conexión Base de Datos',
                'exitoso' => false,
                'tiempo' => microtime(true),
                'error' => $e->getMessage()
            ];
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 🏗️ PRUEBA 2: ESTRUCTURA DE TABLAS
     */
    private function pruebaEstructuraTablas() {
        echo "🏗️ PRUEBA 2: ESTRUCTURA DE TABLAS\n";
        
        $tablasRequeridas = ['asistencia', 'retardos', 'empleados', 'zk_empleado_mapeo'];
        $resultado = [];
        
        foreach ($tablasRequeridas as $tabla) {
            try {
                $stmt = $this->db->getConnection()->prepare("DESCRIBE $tabla");
                $stmt->execute();
                $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $resultado[$tabla] = [
                    'existe' => true,
                    'columnas' => count($columnas),
                    'detalles' => array_column($columnas, 'Field')
                ];
                echo "   ✅ Tabla '$tabla': " . count($columnas) . " columnas\n";
            } catch (Exception $e) {
                $resultado[$tabla] = ['existe' => false, 'error' => $e->getMessage()];
                echo "   ❌ Tabla '$tabla': NO EXISTE\n";
            }
        }
        
        $exitoso = count(array_filter($resultado, fn($t) => $t['existe'])) === count($tablasRequeridas);
        
        $this->resultados['pruebas'][] = [
            'nombre' => 'Estructura de Tablas',
            'exitoso' => $exitoso,
            'tiempo' => microtime(true),
            'detalles' => $resultado
        ];
        
        echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": " . count(array_filter($resultado, fn($t) => $t['existe'])) . "/" . count($tablasRequeridas) . " tablas encontradas\n\n";
    }
    
    /**
     * 👥 PRUEBA 3: MAPEO DE EMPLEADOS
     */
    private function pruebaMapeoEmpleados() {
        echo "👥 PRUEBA 3: MAPEO DE EMPLEADOS\n";
        
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT COUNT(*) as total, COUNT(DISTINCT empleado_id) as empleados_unicos,
                       COUNT(DISTINCT zk_empleado_id) as zk_ids_unicos
                FROM zk_empleado_mapeo
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $exitoso = ($stats['total'] > 0 && $stats['empleados_unicos'] > 0);
            
            $this->resultados['pruebas'][] = [
                'nombre' => 'Mapeo de Empleados',
                'exitoso' => $exitoso,
                'tiempo' => microtime(true),
                'detalles' => $stats
            ];
            
            echo "   Total mapeos: " . $stats['total'] . "\n";
            echo "   Empleados únicos: " . $stats['empleados_unicos'] . "\n";
            echo "   ZK-IDs únicos: " . $stats['zk_ids_unicos'] . "\n";
            echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Mapeo de empleados\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 📋 PRUEBA 4: PARSER ZKTeco
     */
    private function pruebaParserZKTeco() {
        echo "📋 PRUEBA 4: PARSER ZKTeco\n";
        
        try {
            // Cargar mapeo de empleados
            $stmt = $this->db->getConnection()->prepare("
                SELECT empleado_id, zk_empleado_id 
                FROM zk_empleado_mapeo
            ");
            $stmt->execute();
            $mapeo = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mapeo[$row['zk_empleado_id']] = $row['empleado_id'];
            }
            
            // Procesar archivo ZKTeco (primeras 100 líneas)
            $parser = new ZKTecoUniversalParser(['procesar_registros_fallidos' => true]);
            $parser->setMapeoEmpleados($mapeo);
            
            $resultadoParser = $parser->procesarArchivo(__DIR__ . '/data/1_attlog.dat');
            
            $exitoso = $resultadoParser['exito'] && count($resultadoParser['registros']) > 0;
            
            echo "   Registros procesados: " . count($resultadoParser['registros']) . "\n";
            echo "   Válidos: " . ($resultadoParser['estadisticas']['registros_validos'] ?? 'N/A') . "\n";
            echo "   Omitidos: " . ($resultadoParser['estadisticas']['registros_omitidos'] ?? 'N/A') . "\n";
            echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Parser ZKTeco\n\n";
            
            $this->resultados['pruebas'][] = [
                'nombre' => 'Parser ZKTeco',
                'exitoso' => $exitoso,
                'tiempo' => microtime(true),
                'detalles' => $resultadoParser
            ];
            
        } catch (Exception $e) {
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 💾 PRUEBA 5: INSERCIÓN ASISTENCIA
     */
    private function pruebaInsercionAsistencia() {
        echo "💾 PRUEBA 5: INSERCIÓN ASISTENCIA\n";
        
        try {
            // Contar registros actuales
            $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) as total FROM asistencia");
            $stmt->execute();
            $antes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Insertar un registro de prueba
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO asistencia (empleado_id, fecha, hora_entrada, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $exito = $stmt->execute([1, date('Y-m-d'), date('H:i:s')]);
            
            if ($exito) {
                $id = $this->db->getConnection()->lastInsertId();
                
                // Verificar inserción
                $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) as total FROM asistencia WHERE id = ?");
                $stmt->execute([$id]);
                $despues = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Limpiar registro de prueba
                $stmt = $this->db->getConnection()->prepare("DELETE FROM asistencia WHERE id = ?");
                $stmt->execute([$id]);
                
                $exitoso = ($despues == 1);
                
                echo "   Registro insertado: ID $id\n";
                echo "   Verificación: " . ($exitoso ? "Exitosa" : "Fallida") . "\n";
                echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Inserción Asistencia\n\n";
            }
            
            $this->resultados['pruebas'][] = [
                'nombre' => 'Inserción Asistencia',
                'exitoso' => $exitoso ?? false,
                'tiempo' => microtime(true),
                'detalles' => ['id_insertado' => $id ?? null, 'verificacion' => $exitoso ?? false]
            ];
            
        } catch (Exception $e) {
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * ⏰ PRUEBA 6: INSERCIÓN RETARDOS
     */
    private function pruebaInsercionRetardos() {
        echo "⏰ PRUEBA 6: INSERCIÓN RETARDOS\n";
        
        try {
            // Insertar un retardo de prueba
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO retardos (
                    empleado_id, fecha, minutos_retardo, tipo_retraso,
                    justificado, motivo_detalle, requiere_validacion_jefe,
                    estado_validacion, asistencia_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $exito = $stmt->execute([
                1, date('Y-m-d'), 30, 'menor', 0, 'Retardo prueba', 1, 'pendiente', 1
            ]);
            
            if ($exito) {
                $id = $this->db->getConnection()->lastInsertId();
                
                // Verificar inserción
                $stmt = $this->db->getConnection()->prepare("SELECT * FROM retardos WHERE id = ?");
                $stmt->execute([$id]);
                $verificacion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Limpiar registro de prueba
                $stmt = $this->db->getConnection()->prepare("DELETE FROM retardos WHERE id = ?");
                $stmt->execute([$id]);
                
                $exitoso = !empty($verificacion) && $verificacion['minutos_retardo'] == 30;
                
                echo "   Retardo insertado: ID $id\n";
                echo "   Minutos retardo: 30\n";
                echo "   Verificación: " . ($exitoso ? "Exitosa" : "Fallida") . "\n";
                echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Inserción Retardos\n\n";
            }
            
            $this->resultados['pruebas'][] = [
                'nombre' => 'Inserción Retardos',
                'exitoso' => $exito ?? false,
                'tiempo' => microtime(true),
                'detalles' => ['id_insertado' => $id ?? null, 'verificacion' => $exitoso ?? false]
            ];
            
        } catch (Exception $e) {
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 🔄 PRUEBA 7: LÓGICA ENTRADA/SALIDA
     */
    private function pruebaLogicaEntradaSalida() {
        echo "🔄 PRUEBA 7: LÓGICA ENTRADA/SALIDA\n";
        
        try {
            $inserter = new ZKTecoAsistenciaInserter();
            
            // Casos de prueba
            $casos = [
                ['hora' => '08:30:00', 'accion' => 0, 'esperado' => 'entrada'],
                ['hora' => '12:15:00', 'accion' => 1, 'esperado' => 'salida'],
                ['hora' => '17:30:00', 'accion' => 1, 'esperado' => 'salida'],
                ['hora' => '09:00:00', 'accion' => null, 'esperado' => 'entrada']
            ];
            
            $resultados = [];
            foreach ($casos as $i => $caso) {
                $registro = [
                    'empleado_id' => 1,
                    'fecha' => date('Y-m-d'),
                    'hora' => $caso['hora'],
                    'accion' => $caso['accion']
                ];
                
                // Usar reflexión para acceder al método privado
                $reflection = new ReflectionClass($inserter);
                $method = $reflection->getMethod('esRegistroEntrada');
                $method->setAccessible(true);
                
                $resultado = $method->invoke($inserter, $registro);
                $resultadoTexto = $resultado ? 'entrada' : 'salida';
                $exitosoCaso = ($resultadoTexto === $caso['esperado']);
                
                $resultados[] = [
                    'caso' => $i + 1,
                    'hora' => $caso['hora'],
                    'accion' => $caso['accion'],
                    'esperado' => $caso['esperado'],
                    'obtenido' => $resultadoTexto,
                    'exitoso' => $exitosoCaso
                ];
                
                echo "   Caso " . ($i + 1) . ": " . $caso['hora'] . " → " . $resultadoTexto . 
                     " (" . ($exitosoCaso ? "✅" : "❌") . ")\n";
            }
            
            $exitosos = count(array_filter($resultados, fn($r) => $r['exitoso']));
            $exitoso = ($exitosos === count($casos));
            
            echo "   Resultado: $exitosos/" . count($casos) . " casos correctos\n";
            echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Lógica Entrada/Salida\n\n";
            
            $this->resultados['pruebas'][] = [
                'nombre' => 'Lógica Entrada/Salida',
                'exitoso' => $exitoso,
                'tiempo' => microtime(true),
                'detalles' => $resultados
            ];
            
        } catch (Exception $e) {
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 🚀 PRUEBA 8: RENDIMIENTO MASIVO
     */
    private function pruebaRendimientoMasivo() {
        echo "🚀 PRUEBA 8: RENDIMIENTO MASIVO\n";
        
        try {
            $tiempoInicio = microtime(true);
            
            // Procesar primeros 100 registros del archivo ZKTeco
            $stmt = $this->db->getConnection()->prepare("
                SELECT empleado_id, zk_empleado_id 
                FROM zk_empleado_mapeo
            ");
            $stmt->execute();
            $mapeo = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mapeo[$row['zk_empleado_id']] = $row['empleado_id'];
            }
            
            $parser = new ZKTecoUniversalParser(['procesar_registros_fallidos' => true]);
            $parser->setMapeoEmpleados($mapeo);
            $resultadoParser = $parser->procesarArchivo(__DIR__ . '/data/1_attlog.dat');
            
            // Tomar primeros 100 registros
            $registros = array_slice($resultadoParser['registros'], 0, 100);
            
            $inserter = new ZKTecoAsistenciaInserter([
                'evitar_duplicados' => false
            ]);
            
            $resultadoInsercion = $inserter->insertarRegistros($registros);
            
            $tiempoFin = microtime(true);
            $tiempoTotal = $tiempoFin - $tiempoInicio;
            $registrosPorSegundo = count($registros) / $tiempoTotal;
            
            $exitoso = $resultadoInsercion['exito'];
            
            echo "   Registros procesados: " . count($registros) . "\n";
            echo "   Insertados: " . ($resultadoInsercion['estadisticas']['insertados'] ?? 0) . "\n";
            echo "   Errores: " . ($resultadoInsercion['estadisticas']['errores'] ?? 0) . "\n";
            echo "   Tiempo: " . round($tiempoTotal, 3) . " segundos\n";
            echo "   Velocidad: " . round($registrosPorSegundo, 2) . " registros/segundo\n";
            echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Rendimiento Masivo\n\n";
            
            $this->resultados['pruebas'][] = [
                'nombre' => 'Rendimiento Masivo',
                'exitoso' => $exitoso,
                'tiempo' => microtime(true),
                'detalles' => [
                    'registros' => count($registros),
                    'insertados' => $resultadoInsercion['estadisticas']['insertados'] ?? 0,
                    'tiempo_total' => $tiempoTotal,
                    'velocidad' => $registrosPorSegundo
                ]
            ];
            
        } catch (Exception $e) {
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 🛡️ PRUEBA 9: VALIDACIÓN DE DATOS
     */
    private function pruebaValidacionDatos() {
        echo "🛡️ PRUEBA 9: VALIDACIÓN DE DATOS\n";
        
        try {
            // Verificar integridad de datos en asistencia
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN empleado_id IS NULL OR empleado_id = 0 THEN 1 END) as sin_empleado,
                    COUNT(CASE WHEN fecha IS NULL OR fecha = '0000-00-00' THEN 1 END) as sin_fecha,
                    COUNT(CASE WHEN hora_entrada IS NULL AND hora_salida IS NULL THEN 1 END) as sin_horas
                FROM asistencia
            ");
            $stmt->execute();
            $validacionAsistencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar integridad de datos en retardos
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN empleado_id IS NULL OR empleado_id = 0 THEN 1 END) as sin_empleado,
                    COUNT(CASE WHEN fecha IS NULL OR fecha = '0000-00-00' THEN 1 END) as sin_fecha,
                    COUNT(CASE WHEN minutos_retardo IS NULL OR minutos_retardo < 0 THEN 1 END) as minutos_invalidos
                FROM retardos
            ");
            $stmt->execute();
            $validacionRetardos = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $integridadAsistencia = ($validacionAsistencia['sin_empleado'] + $validacionAsistencia['sin_fecha'] + $validacionAsistencia['sin_horas']) == 0;
            $integridadRetardos = ($validacionRetardos['sin_empleado'] + $validacionRetardos['sin_fecha'] + $validacionRetardos['minutos_invalidos']) == 0;
            $exitoso = $integridadAsistencia && $integridadRetardos;
            
            echo "   Asistencia:\n";
            echo "     Total: " . $validacionAsistencia['total'] . "\n";
            echo "     Sin empleado: " . $validacionAsistencia['sin_empleado'] . "\n";
            echo "     Sin fecha: " . $validacionAsistencia['sin_fecha'] . "\n";
            echo "     Sin horas: " . $validacionAsistencia['sin_horas'] . "\n";
            
            echo "   Retardos:\n";
            echo "     Total: " . $validacionRetardos['total'] . "\n";
            echo "     Sin empleado: " . $validacionRetardos['sin_empleado'] . "\n";
            echo "     Sin fecha: " . $validacionRetardos['sin_fecha'] . "\n";
            echo "     Minutos inválidos: " . $validacionRetardos['minutos_invalidos'] . "\n";
            
            echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Validación de Datos\n\n";
            
            $this->resultados['pruebas'][] = [
                'nombre' => 'Validación de Datos',
                'exitoso' => $exitoso,
                'tiempo' => microtime(true),
                'detalles' => [
                    'asistencia' => $validacionAsistencia,
                    'retardos' => $validacionRetardos
                ]
            ];
            
        } catch (Exception $e) {
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 📋 PRUEBA 10: REPORTE FINAL
     */
    private function pruebaReporteFinal() {
        echo "📋 PRUEBA 10: REPORTE FINAL\n";
        
        try {
            // Contar registros actuales
            $stmtAsistencia = $this->db->getConnection()->prepare("
                SELECT COUNT(*) as total, COUNT(DISTINCT empleado_id) as empleados_unicos 
                FROM asistencia
            ");
            $stmtAsistencia->execute();
            $statsAsistencia = $stmtAsistencia->fetch(PDO::FETCH_ASSOC);
            
            $stmtRetardos = $this->db->getConnection()->prepare("
                SELECT COUNT(*) as total, AVG(minutos_retardo) as promedio_minutos 
                FROM retardos
            ");
            $stmtRetardos->execute();
            $statsRetardos = $stmtRetardos->fetch(PDO::FETCH_ASSOC);
            
            $this->resultados['resumen'] = [
                'total_asistencia' => $statsAsistencia['total'],
                'empleados_unicos' => $statsAsistencia['empleados_unicos'],
                'total_retardos' => $statsRetardos['total'],
                'promedio_minutos_retardo' => round($statsRetardos['promedio_minutos'], 1) ?: 0,
                'porcentaje_completado' => round(($statsAsistencia['total'] / 19583) * 100, 2),
                'sistema_operacional' => $statsAsistencia['total'] > 0
            ];
            
            $exitoso = $statsAsistencia['total'] > 0;
            
            echo "   Total asistencia: " . $statsAsistencia['total'] . " registros\n";
            echo "   Empleados únicos: " . $statsAsistencia['empleados_unicos'] . "\n";
            echo "   Total retardos: " . $statsRetardos['total'] . "\n";
            echo "   Promedio minutos retardo: " . $this->resultados['resumen']['promedio_minutos_retardo'] . "\n";
            echo "   % completado: " . $this->resultados['resumen']['porcentaje_completado'] . "%\n";
            echo "   Sistema operacional: " . ($this->resultados['resumen']['sistema_operacional'] ? "SÍ" : "NO") . "\n";
            echo "   " . ($exitoso ? "✅ ÉXITO" : "❌ FALLO") . ": Reporte Final\n\n";
            
            $this->resultados['pruebas'][] = [
                'nombre' => 'Reporte Final',
                'exitoso' => $exitoso,
                'tiempo' => microtime(true),
                'detalles' => $this->resultados['resumen']
            ];
            
        } catch (Exception $e) {
            echo "   ❌ FALLO: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 📊 GENERAR REPORTE FINAL
     */
    private function generarReporteFinal() {
        $this->resultados['exitosos'] = count(array_filter($this->resultados['pruebas'], fn($p) => $p['exitoso']));
        $this->resultados['fallidos'] = count($this->resultados['pruebas']) - $this->resultados['exitosos'];
        
        echo "🏆 REPORTE FINAL DE TESTING\n";
        echo "=" . str_repeat("=", 59) . "\n";
        echo "Pruebas exitosas: " . $this->resultados['exitosos'] . "/10\n";
        echo "Pruebas fallidas: " . $this->resultados['fallidos'] . "/10\n";
        echo "Calificación: " . ($this->resultados['exitosos'] >= 8 ? "🟢 EXCELENTE" : ($this->resultados['exitosos'] >= 6 ? "🟡 BUENO" : "🔴 NECESITA MEJORAS")) . "\n\n";
        
        echo "📊 ESTADÍSTICAS FINALES:\n";
        if (isset($this->resultados['resumen'])) {
            foreach ($this->resultados['resumen'] as $clave => $valor) {
                echo "   " . ucwords(str_replace('_', ' ', $clave)) . ": " . $valor . "\n";
            }
        }
        
        echo "\n✅ SISTEMA ZKTeco " . ($this->resultados['exitosos'] >= 8 ? "LISTO PARA PRODUCCIÓN" : "NECESITA AJUSTES") . "\n";
    }
}

// 🚀 EJECUTAR TESTING COMPLETO
$testing = new ZKTecoTestingSuite();
$resultados = $testing->ejecutarTodasLasPruebas();

// Generar JSON para API si se solicita
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($resultados, JSON_PRETTY_PRINT);
}
?>