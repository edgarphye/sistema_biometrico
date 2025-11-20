<?php
require_once 'config.php';
require_once 'models/Empleado.php';

$empleado = new Empleado();

// Datos del ejemplo proporcionado
$fechaNacimiento = '1972-11-14'; // Fecha corregida (14/11/1972)
$primerApellido = 'PHYE';
$segundoApellido = 'PARGA';
$nombres = 'EDGAR';
$sexo = 'H';
$entidadFederativa = 'JALISCO';

echo "=== ANÁLISIS DE FUNCIONES DE EMPLEADOS PARA RFC Y CURP ===\n\n";
echo "Datos del ejemplo:\n";
echo "- Nombre: $nombres\n";
echo "- Apellido paterno: $primerApellido\n";
echo "- Apellido materno: $segundoApellido\n";
echo "- Fecha de nacimiento: $fechaNacimiento\n";
echo "- Sexo: $sexo\n";
echo "- Entidad federativa: $entidadFederativa\n\n";

try {
    // Usar las funciones generateRFC y generateCURP
    $rfcGenerado = $empleado->generateRFC($fechaNacimiento, $primerApellido, $segundoApellido, $nombres, $sexo, $entidadFederativa);
    $curpGenerado = $empleado->generateCURP($fechaNacimiento, $primerApellido, $segundoApellido, $nombres, $sexo, $entidadFederativa);

    echo "RESULTADOS GENERADOS USANDO LAS FUNCIONES:\n";
    echo "RFC generado: $rfcGenerado\n";
    echo "CURP generado: $curpGenerado\n\n";

    echo "RESULTADOS ESPERADOS DEL EJEMPLO:\n";
    echo "RFC esperado: PEPE721114KZ0\n";
    echo "CURP esperado: PEPE721114HJCHRD08\n\n";

    // Verificación
    $rfcCoincide = ($rfcGenerado === 'PEPE721114KZ0');
    $curpCoincide = ($curpGenerado === 'PEPE721114HJCHRD08');

    echo "VERIFICACIÓN:\n";
    echo "RFC coincide: " . ($rfcCoincide ? "✅ SÍ" : "❌ NO") . "\n";
    echo "CURP coincide: " . ($curpCoincide ? "✅ SÍ" : "❌ NO") . "\n\n";

    if ($rfcCoincide && $curpCoincide) {
        echo "✅ LAS FUNCIONES GENERAN CORRECTAMENTE LOS CÓDIGOS PARA EL EJEMPLO PROPORCIONADO.\n";
    } else {
        echo "❌ HAY DISCREPANCIAS. REVISAR LA IMPLEMENTACIÓN.\n";
    }

} catch (Exception $e) {
    echo "Error al generar RFC/CURP: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL ANÁLISIS ===\n";
?>
