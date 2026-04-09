<?php
// Depurar específicamente los errores de inserción
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEPURACIÓN ERRORES INSERCIÓN ===\n";

try {
    // Cargar dependencias
    require_once 'config.php';
    require_once 'models/Database.php';
    require_once 'models/ZKTecoFormatDetector.php';
    require_once 'models/ZKTecoUniversalParser.php';
    require_once 'models/ZKTecoAsistenciaInserter.php';
    
    echo "1. Procesando archivo DAT...\n";
    
    // Detectar formato
    $detector = new ZKTecoFormatDetector();
    $formato = $detector->detectarFormato('20241106.DAT');
    echo "   Formato: {$formato['formato']} ({$formato['confianza']}%)\n";
    
    // Parsear archivo
    $parser = new ZKTecoUniversalParser();
    
    // Configurar mapeo de empleados
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->query("SELECT zk_empleado_id, empleado_id FROM zk_empleado_mapeo");
    $mapeos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeos[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    $parser->setMapeoEmpleados($mapeos);
    echo "   Mapeo configurado: " . json_encode($mapeos) . "\n";
    
    $resultado = $parser->procesarArchivo('20241106.DAT');
    echo "   Registros válidos: {$resultado['estadisticas']['registros_validos']}\n";
    
    // Analizar inserción
    $inserter = new ZKTecoAsistenciaInserter();
    
    echo "2. Probando inserción del primer registro...\n";
    if (!empty($resultado['registros'])) {
        $primerRegistro = $resultado['registros'][0];
        
    echo "   Datos del registro:\n";
    echo "     - zk_empleado_id: {$primerRegistro['zk_empleado_id']}\n";
    echo "     - empleado_id (mapeado): {$primerRegistro['empleado_id']}\n";
    echo "     - fecha: {$primerRegistro['fecha']}\n";
    echo "     - hora: {$primerRegistro['hora']}\n";
    echo "     - acción: {$primerRegistro['accion']}\n";
        
        // Intentar inserción con captura de errores
        try {
            // Activar modo error de PDO para ver el error exacto
            $pdo = Database::getInstance()->getConnection();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "   Valores que se intentan insertar:\n";
            echo "     - empleado_id: {$primerRegistro['empleado_id']}\n";
            echo "     - fecha: {$primerRegistro['fecha']}\n";
            echo "     - hora: {$primerRegistro['hora']}\n";
            echo "     - dispositivo_id: " . ($primerRegistro['dispositivo_id'] ?? 'NULL') . "\n";
            echo "     - tipo_biometria: huella\n";
            echo "     - datos_biometricos: JSON\n";
            echo "     - calidad_verificacion: 1\n";
            echo "     - metadata_dispositivo: JSON\n";
            echo "     - tiempo_procesamiento: 0.xxx\n";
            
            $resultadoInsercion = $inserter->insertarRegistros([$primerRegistro]);
            
            echo "   Resultado inserción:\n";
            echo json_encode($resultadoInsercion, JSON_PRETTY_PRINT) . "\n";
            
            if (isset($resultadoInsercion['success']) && !$resultadoInsercion['success']) {
                echo "   ❌ ERROR DE INSERCIÓN: " . ($resultadoInsercion['error'] ?? 'Desconocido') . "\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ EXCEPCIÓN EN INSERCIÓN: " . $e->getMessage() . "\n";
            echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
    }
    
    // Probar conexión directa a la base de datos
    echo "3. Verificando conexión y tabla...\n";
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Verificar estructura de tabla
    $stmt = $pdo->query("DESCRIBE asistencia");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Estructura tabla asistencia:\n";
    foreach ($columns as $col) {
        echo "     - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
    
    // Verificar si hay registros existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia");
    $total = $stmt->fetch()['total'];
    echo "   Total registros existentes: $total\n";
    
    // Verificar empleados existentes
    $stmt = $pdo->query("SELECT id, nombre FROM empleados LIMIT 10");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Empleados existentes:\n";
    foreach ($empleados as $emp) {
        echo "     - ID {$emp['id']}: {$emp['nombre']}\n";
    }
    
    // Verificar si empleado_id 1234 existe
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM empleados WHERE id = ?");
    $stmt->execute([1234]);
    $existe = $stmt->fetch()['total'];
    echo "   Empleado ID 1234 existe: " . ($existe ? 'Sí' : 'NO') . "\n";
    
    // Verificar tabla de mapeo ZKTeco
    try {
        $stmt = $pdo->query("SELECT * FROM zk_empleado_mapeo LIMIT 10");
        $mapeos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Mapeos ZKTeco existentes:\n";
        foreach ($mapeos as $mapeo) {
            echo "     - ZK ID {$mapeo['zk_empleado_id']} -> Empleado ID {$mapeo['empleado_id']}\n";
        }
        
        // Verificar mapeo para ZK ID 1
        $stmt = $pdo->prepare("SELECT empleado_id FROM zk_empleado_mapeo WHERE zk_empleado_id = ?");
        $stmt->execute([1]);
        $mapeo = $stmt->fetch();
        echo "   Mapeo ZK ID 1 -> Empleado ID: " . ($mapeo ? $mapeo['empleado_id'] : 'NO ENCONTRADO') . "\n";
        
    } catch (Exception $e) {
        echo "   Tabla zk_empleado_mapeo no existe o error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEPURACIÓN ===\n";
?>