<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNOSTICANDO ERROR DE EMPLEADOS.JS ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Base de datos conectada\n";
    
    // Revisar qué endpoint está llamando empleados.js
    echo "\n--- Verificando endpoint /empleados ---\n";
    
    // Simular la llamada que hace el JavaScript
    $stmt = $pdo->query("
        SELECT 
            id,
            nombre,
            apellido,
            rfc,
            area,
            puesto,
            email,
            telefono,
            activo,
            foto_cara
        FROM empleados
        ORDER BY nombre
        LIMIT 5
    ");
    
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Datos de empleados (primeros 5):\n";
    foreach ($empleados as $emp) {
        echo "ID: {$emp['id']}\n";
        echo "Nombre: " . ($emp['nombre'] ?? 'NULL') . "\n";
        echo "Apellido: " . ($emp['apellido'] ?? 'NULL') . "\n";
        echo "RFC: " . ($emp['rfc'] ?? 'NULL') . "\n";
        echo "Área: " . ($emp['area'] ?? 'NULL') . "\n";
        echo "Foto: " . ($emp['foto_cara'] ?? 'NULL') . "\n";
        echo "---\n";
    }
    
    // Verificar si hay valores nulos que causen el error
    echo "\n--- Verificando valores NULL problemáticos ---\n";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(nombre) as con_nombre,
            COUNT(apellido) as con_apellido,
            COUNT(rfc) as con_rfc,
            COUNT(area) as con_area,
            COUNT(foto_cara) as con_foto
        FROM empleados
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📈 Estadísticas de campos:\n";
    echo "   - Total empleados: {$stats['total']}\n";
    echo "   - Con nombre: {$stats['con_nombre']}\n";
    echo "   - Con apellido: {$stats['con_apellido']}\n";
    echo "   - Con RFC: {$stats['con_rfc']}\n";
    echo "   - Con área: {$stats['con_area']}\n";
    echo "   - Con foto: {$stats['con_foto']}\n";
    
    // Buscar empleados específicos con valores nulos
    $stmt = $pdo->query("
        SELECT id, nombre, apellido, rfc, area
        FROM empleados
        WHERE nombre IS NULL OR apellido IS NULL OR rfc IS NULL OR area IS NULL
        LIMIT 3
    ");
    
    $problematicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($problematicos)) {
        echo "\n⚠️ Empleados con valores nulos:\n";
        foreach ($problematicos as $emp) {
            echo "ID: {$emp['id']} - Problema en algún campo\n";
        }
    } else {
        echo "\n✅ No hay valores nulos problemáticos\n";
    }
    
    // Probar la respuesta JSON que recibiría el JavaScript
    echo "\n--- Simulando respuesta JSON para JavaScript ---\n";
    
    $response_data = [
        'success' => true,
        'empleados' => $empleados,
        'total' => count($empleados)
    ];
    
    $json_response = json_encode($response_data);
    
    echo "JSON generado (primeros 200 chars):\n";
    echo substr($json_response, 0, 200) . "...\n";
    
    // Verificar si hay algún valor null en el JSON
    if (strpos($json_response, 'null') !== false) {
        echo "\n⚠️ Se encontraron valores 'null' en el JSON\n";
        echo "Estos podrían estar causando el error en escapeHtml()\n";
    } else {
        echo "\n✅ No hay valores 'null' en el JSON\n";
    }
    
    echo "\n=== SOLUCIÓN APLICADA ===\n";
    echo "✅ Función escapeHtml() actualizada para manejar nulos\n";
    echo "✅ Campos problemáticos protegidos con fallback\n";
    echo "🚀 El error debería estar resuelto\n";
    
    echo "\n📋 Pasos para verificar:\n";
    echo "1. Recarga la página de empleados\n";
    echo "2. Abre la consola de desarrollador\n";
    echo "3. El error 'Cannot read properties of null' debería desaparecer\n";
    echo "4. Los empleados deberían cargarse correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>