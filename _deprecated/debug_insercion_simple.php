<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/ZKTecoUniversalParser.php';
require_once __DIR__ . '/models/ZKTecoAsistenciaInserterFinal.php';

echo "🐛 DEBUG SIMPLE PARA IDENTIFICAR ERRORES\n";
echo "=========================================\n";

try {
    // 1. Cargar mapeo
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT empleado_id, zk_empleado_id 
        FROM zk_empleado_mapeo
    ");
    $stmt->execute();
    $mapeo = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeo[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    
    echo "✅ Mapeo: " . count($mapeo) . " registros\n";
    
    // 2. Procesar un solo registro
    $parser = new ZKTecoUniversalParser(['procesar_registros_fallidos' => true]);
    $parser->setMapeoEmpleados($mapeo);
    $resultadoParser = $parser->procesarArchivo(__DIR__ . '/data/1_attlog.dat');
    
    $primerRegistro = $resultadoParser['registros'][0];
    echo "📋 Primer registro: " . json_encode($primerRegistro, JSON_PRETTY_PRINT) . "\n\n";
    
    // 3. Probar inserción directa
    echo "💾 Probando inserción directa del primer registro...\n";
    
    $sql = "INSERT INTO asistencia (empleado_id, fecha, hora_entrada, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    
    $valores = [
        $primerRegistro['empleado_id'],
        $primerRegistro['fecha'],
        $primerRegistro['hora']
    ];
    
    echo "SQL: $sql\n";
    echo "Valores: " . json_encode($valores) . "\n";
    
    $exito = $stmt->execute($valores);
    
    if ($exito) {
        $id = $pdo->lastInsertId();
        echo "✅ Inserción directa exitosa. ID: $id\n\n";
        
        // Limpiar
        $stmt = $pdo->prepare("DELETE FROM asistencia WHERE id = ?");
        $stmt->execute([$id]);
        
        // 4. Probar con ZKTecoAsistenciaInserterFinal
        echo "🔧 Probando con ZKTecoAsistenciaInserterFinal...\n";
        
        $inserter = new ZKTecoAsistenciaInserterFinal([
            'procesar_registros_fallidos' => true,
            'evitar_duplicados' => false
        ]);
        
        echo "Llamando a insertarRegistroCompleto...\n";
        $resultado = $inserter->insertarRegistroCompleto($primerRegistro);
        echo "Resultado: " . ($resultado ? "SUCCESS (ID: $resultado)" : "FAILED") . "\n";
        
    } else {
        $errorInfo = $stmt->errorInfo();
        echo "❌ Error en inserción directa:\n";
        echo "   SQLSTATE: " . $errorInfo[0] . "\n";
        echo "   Código: " . $errorInfo[1] . "\n";
        echo "   Mensaje: " . $errorInfo[2] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== 🏁 FIN ===\n";
?>