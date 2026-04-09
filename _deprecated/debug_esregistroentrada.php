require_once __DIR__ . "/config.php";
require_once __DIR__ . "/models/Database.php";
require_once __DIR__ . "/models/ZKTecoUniversalParser.php";
require_once __DIR__ . "/models/ZKTecoAsistenciaInserter.php";

echo "=== 🔍 DEBUG MÉTODO esRegistroEntrada ===" . PHP_EOL;

// Registro de prueba
$registroPrueba = [
    "empleado_id" => 118,
    "fecha" => "2025-01-21",
    "hora" => "15:00:07",
    "accion" => 1 // Acción 0 = entrada
];

echo "📋 Registro de prueba:" . PHP_EOL;
echo json_encode($registroPrueba, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

$db = new Database();
$pdo = $db->getConnection();

// Test con ZKTecoAsistenciaInserter
echo PHP_EOL . "🧪 Test con ZKTecoAsistenciaInserter:" . PHP_EOL;
$inserter = new ZKTecoAsistenciaInserter([
    "procesar_registros_fallidos" => true,
    "evitar_duplicados" => false,
    "verificar_existencia_rapida" => false
]);

$resultado = $inserter->procesarRegistroIndividual($registroPrueba);

echo PHP_EOL . "📋 Resultado esRegistroEntrada:" . PHP_EOL;
echo json_encode($resultado, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

// Verificar manualmente lógica de esRegistroEntrada
echo PHP_EOL . "🔍 Verificación manual de esRegistroEntrada:" . PHP_EOL;
$accion = $registroPrueba["accion"] ?? 0;
$hora = $registroPrueba["hora"] ?? "00:00:00";

echo "   - Acción: " . $accion . PHP_EOL;
echo "   - Hora: " . $hora . PHP_EOL;
echo "   - ¿Es entrada (lógica principal): " . ($accion === 0 ? "SÍ" : "NO") . PHP_EOL;

// Primera marca del día
$stmt = $pdo->prepare("SELECT COUNT(*) as conteo FROM asistencia WHERE empleado_id = ? AND fecha = ?");
$stmt->execute([118, "2025-01-21"]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
$primeraMarca = $resultado["conteo"] == 0;

echo "   - ¿Es primera marca del día: " . ($primeraMarca ? "SÍ" : "NO") . PHP_EOL;
echo "   - Conteo: " . $resultado["conteo"] . PHP_EOL;

// Verificar lógica de ZKTecoAsistenciaInserter
echo PHP_EOL . "🔍 Lógica en ZKTecoAsistenciaInserter.esRegistroEntrada:" . PHP_EOL;
echo "   - Si acción === 0 -> debería devolver TRUE" . PHP_EOL;

if ($accion === 0) {
    echo "   - ✅ Lógica correcta para entrada" . PHP_EOL;
} else {
    echo "   - ⚠️ Lógica incorrecta para no-entrada" . PHP_EOL;
}

echo PHP_EOL . "📊 Datos base del registro que llega a inserter:" . PHP_EOL;
if (isset($resultado["datos"]["empleado_id"])) {
    echo "   - empleado_id: " . $resultado["datos"]["empleado_id"] . PHP_EOL;
} else {
    echo "   - empleado_id: MISSNG" . PHP_EOL;
}

echo PHP_EOL . "🔍 CONCLUSIÓN:" . PHP_EOL;
if (($accion === 0) && $resultado["exito"])) {
    echo "   - ✅ El método esRegistroEntrada funciona correctamente" . PHP_EOL;
    echo "   - ✅ Pero hora_entrada debería ser asignada como: " . $registroPrueba["hora"] . PHP_EOL;
} else {
    echo "   - ❌ Hay un problema en la lógica" . PHP_EOL;
    echo "   - ⚠️ Posible causa: ZKTecoAsistenciaInserter está modificando los datos antes de llamar a esRegistroEntrada" . PHP_EOL;
}

echo PHP_EOL . "=== 🏁 FIN ===" . PHP_EOL;
?>
