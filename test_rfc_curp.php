<?php
require_once 'config.php';
require_once 'models/Empleado.php';

$empleado = new Empleado();

// Datos de ejemplo para pruebas
$testData = [
    [
        'fechaNacimiento' => '1972-11-14',
        'primerApellido' => 'PHYE',
        'segundoApellido' => 'PARGA',
        'nombres' => 'EDGAR',
        'sexo' => 'H',
        'entidadFederativa' => 'JALISCO'
    ],
    [
        'fechaNacimiento' => '1990-05-15',
        'primerApellido' => 'GARCÍA',
        'segundoApellido' => 'LÓPEZ',
        'nombres' => 'JUAN',
        'sexo' => 'H',
        'entidadFederativa' => 'MEXICO'
    ],
    [
        'fechaNacimiento' => '1985-12-03',
        'primerApellido' => 'MARTÍNEZ',
        'segundoApellido' => 'HERNÁNDEZ',
        'nombres' => 'MARÍA JOSÉ',
        'sexo' => 'M',
        'entidadFederativa' => 'JALISCO'
    ],
    [
        'fechaNacimiento' => '1978-08-22',
        'primerApellido' => 'SÁNCHEZ',
        'segundoApellido' => 'RAMÍREZ',
        'nombres' => 'CARLOS',
        'sexo' => 'H',
        'entidadFederativa' => 'PUEBLA'
    ]
];

echo "=== PRUEBAS DE GENERACIÓN DE RFC Y CURP ===\n\n";

foreach ($testData as $index => $data) {
    echo "Prueba " . ($index + 1) . ":\n";
    echo "Datos: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";

    try {
        $rfc = $empleado->generateRFC(
            $data['fechaNacimiento'],
            $data['primerApellido'],
            $data['segundoApellido'],
            $data['nombres'],
            $data['sexo'],
            $data['entidadFederativa']
        );

        $curp = $empleado->generateCURP(
            $data['fechaNacimiento'],
            $data['primerApellido'],
            $data['segundoApellido'],
            $data['nombres'],
            $data['sexo'],
            $data['entidadFederativa']
        );

        echo "RFC generado: $rfc\n";
        echo "CURP generado: $curp\n";

        // Validaciones básicas
        if (strlen($rfc) == 13) {
            echo "✓ RFC tiene longitud correcta (13 caracteres)\n";
        } else {
            echo "✗ RFC tiene longitud incorrecta (" . strlen($rfc) . " caracteres)\n";
        }

        if (strlen($curp) == 18) {
            echo "✓ CURP tiene longitud correcta (18 caracteres)\n";
        } else {
            echo "✗ CURP tiene longitud incorrecta (" . strlen($curp) . " caracteres)\n";
        }

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Prueba de validación de entrada
echo "=== PRUEBAS DE VALIDACIÓN ===\n\n";

try {
    $empleado->generateRFC('', 'GARCIA', 'LOPEZ', 'JUAN', 'H', 'MEXICO');
    echo "✗ No detectó parámetro vacío\n";
} catch (Exception $e) {
    echo "✓ Detectó parámetro vacío: " . $e->getMessage() . "\n";
}

try {
    $empleado->generateCURP('1990-05-15', 'GARCIA', 'LOPEZ', 'JUAN', 'X', 'MEXICO');
    echo "✗ No detectó sexo inválido\n";
} catch (Exception $e) {
    echo "✓ Detectó sexo inválido: " . $e->getMessage() . "\n";
}

try {
    $empleado->generateCURP('1990-05-15', 'GARCIA', 'LOPEZ', 'JUAN', 'H', 'INVALIDO');
    echo "✗ No detectó entidad inválida\n";
} catch (Exception $e) {
    echo "✓ Detectó entidad inválida: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE PRUEBAS ===\n";
?>
