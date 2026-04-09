<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/ZKTecoUniversalParser.php';
require_once __DIR__ . '/models/ZKTecoAsistenciaInserter.php';

echo "=== 🔍 DEBUG MÉTODO esRegistroEntrada ===\n\n";

// Registro de prueba
$registroPrueba = [
    'empleado_id' => 118,
    'fecha' => '2025-01-21',
    'hora' => '15:00:07',
    'accion' => 1 // Acción 0 = entrada
];

echo "📋 Registro de prueba:\n";
echo json_encode($registroPrueba, JSON_PRETTY_PRINT) . "\n\n";

$db = new Database();
$pdo = $db->getConnection();

// Test con ZKTecoAsistenciaInserter
echo "\n🧪 Test con ZKTecoAsistenciaInserter:\n";
$inserter = new ZKTecoAsistenciaInserter([
    'procesar_registros_fallidos' => true,
    'evitar_duplicados' => false,
    'verificar_existencia_rapida' => false
]);

$resultado = $inserter->procesarRegistroIndividual($registroPrueba);

echo "\n📋 Resultado esRegistroEntrada:\n";
echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n\n";

// Verificar manualmente lógica de esRegistroEntrada
echo "\n🔍 Verificación manual de esRegistroEntrada:\n";
$accion = $registroPrueba['accion'] ?? 0;
$hora = $registroPrueba['hora'] ?? '00:00:00';

echo "   - Acción: " . $accion . "\n";
echo "   - Hora: " . $hora . "\n";
echo "   - ¿Es entrada (lógica principal): " . ($accion === 0 ? 'SÍ' : 'NO') . "\n";

// Primera marca del día
$stmt = $pdo->prepare("SELECT COUNT(*) as conteo FROM asistencia WHERE empleado_id = ? AND fecha = ?");
$stmt->execute([118, '2025-01-21']);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
$primeraMarca = $resultado['conteo'] == 0;

echo "   - ¿Es primera marca del día: " . ($primeraMarca ? 'SÍ' : 'NO') . "\n";
echo "   - Conteo: " . $resultado['conteo'] . "\n";

// Verificar lógica de ZKTecoAsistenciaInserter
echo "\n🔍 Lógica en ZKTecoAsistenciaInserter.esRegistroEntrada:\n";
echo "   - Si acción === 0 -> debería devolver TRUE\n";

if ($accion === 0) {
    echo "   - ✅ Lógica correcta para entrada\n";
} else {
    echo "   - ⚠️ Lógica incorrecta para no-entrada\n";
}

echo "\n🔍 Comparación:\n";
echo "   - esRegistroEntrada() devuelve: " . ($resultado['exito'] ? 'TRUE' : 'FALSE') . "\n";
echo "   - Lógica principal dice: " . ($accion === 0 ? 'TRUE' : 'FALSE') . "\n";
echo "   - ¿Deberían coincidir? " . (($accion === 0 && $resultado['exito']) ? 'SÍ' : 'NO') . "\n";

echo "\n📊 Datos base del registro que llega a inserter:\n";
echo "   - empleado_id: " . ($resultado['datos']['empleado_id'] ?? 'MISSING') . "\n";
echo "   - fecha: " . ($resultado['datos']['fecha'] ?? 'MISSING') . "\n";
echo "   - hora: " . ($resultado['datos']['hora'] ?? 'MISSING') . "\n";
echo "   - hora_entrada: " . ($resultado['datos']['hora_entrada'] ?? 'NULL') . "\n";
echo "   - hora_salida: " . ($resultado['datos']['hora_salida'] ?? 'NULL') . "\n";

echo "\n🎯 CONCLUSIÓN:\n";
if (($accion === 0) && $resultado['exito']) {
    echo "   ✅ El método esRegistroEntrada funciona correctamente\n";
    echo "   ✅ Pero hora_entrada debería ser asignada como: " . $registroPrueba['hora'] . "\n";
} else {
    echo "   ❌ Hay un problema en la lógica\n";
    echo "   ⚠️  Posible causa: ZKTecoAsistenciaInserter está modificando los datos antes de llamar a esRegistroEntrada\n";
}

echo "\n=== 🏁 FIN ===\n";
?>