<?php
require_once 'config.php';
require_once 'models/Empleado.php';

$empleado = new Empleado();

// Tus datos reales
$fechaNacimiento = '1972-11-14';
$primerApellido = 'PHYE';
$segundoApellido = 'PARGA';
$nombres = 'EDGAR';
$sexo = 'H';
$entidadFederativa = 'JALISCO';

echo "=== PRUEBA MANUAL CON TUS DATOS REALES ===\n\n";
echo "Datos de entrada:\n";
echo "- Fecha de nacimiento: $fechaNacimiento\n";
echo "- Primer apellido: $primerApellido\n";
echo "- Segundo apellido: $segundoApellido\n";
echo "- Nombres: $nombres\n";
echo "- Sexo: $sexo\n";
echo "- Entidad federativa: $entidadFederativa\n\n";

try {
    $rfcGenerado = $empleado->generateRFC($fechaNacimiento, $primerApellido, $segundoApellido, $nombres, $sexo, $entidadFederativa);
    $curpGenerado = $empleado->generateCURP($fechaNacimiento, $primerApellido, $segundoApellido, $nombres, $sexo, $entidadFederativa);

    echo "RESULTADOS GENERADOS:\n";
    echo "RFC generado: $rfcGenerado\n";
    echo "CURP generado: $curpGenerado\n\n";

    echo "TUS DATOS REALES (para comparación):\n";
    echo "RFC real: PEPE721114KZ0\n";
    echo "CURP real: PEPE721114HJCHRD08\n\n";

    // Análisis de diferencias
    echo "ANÁLISIS:\n";

    if ($rfcGenerado === 'PEPE721114KZ0') {
        echo "✅ RFC: ¡COINCIDE PERFECTAMENTE!\n";
    } else {
        echo "❌ RFC: No coincide\n";
        echo "   Generado: $rfcGenerado\n";
        echo "   Real:     PEPE721114KZ0\n";
    }

    if ($curpGenerado === 'PEPE721114HJCHRD08') {
        echo "✅ CURP: ¡COINCIDE PERFECTAMENTE!\n";
    } else {
        echo "❌ CURP: No coincide\n";
        echo "   Generado: $curpGenerado\n";
        echo "   Real:     PEPE721114HJCHRD08\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE PRUEBA MANUAL ===\n";
?>
