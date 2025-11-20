<?php
require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/HorarioLaboral.php';
require_once 'models/TipoJustificacion.php';

$db = new Database();
$horarioModel = new HorarioLaboral();
$tipoJustificacionModel = new TipoJustificacion();

// Crear horarios predeterminados
$horarios = [
    [
        'nombre' => 'Horario Estándar Matutino',
        'hora_entrada' => '09:00:00',
        'hora_salida' => '18:00:00',
        'tolerancia_minutos' => 9,
        'descripcion' => 'Horario estándar de 9:00 AM a 6:00 PM'
    ],
    [
        'nombre' => 'Horario Administrativo',
        'hora_entrada' => '08:00:00',
        'hora_salida' => '17:00:00',
        'tolerancia_minutos' => 10,
        'descripcion' => 'Horario administrativo de 8:00 AM a 5:00 PM'
    ],
    [
        'nombre' => 'Horario 8:00 AM - 3:00 PM',
        'hora_entrada' => '08:00:00',
        'hora_salida' => '15:00:00',
        'tolerancia_minutos' => 9,
        'descripcion' => 'Horario de 8:00 AM a 3:00 PM'
    ],
    [
        'nombre' => 'Horario 9:00 AM - 4:00 PM',
        'hora_entrada' => '09:00:00',
        'hora_salida' => '16:00:00',
        'tolerancia_minutos' => 9,
        'descripcion' => 'Horario de 9:00 AM a 4:00 PM'
    ],
    [
        'nombre' => 'Horario Vespertino',
        'hora_entrada' => '14:00:00',
        'hora_salida' => '22:00:00',
        'tolerancia_minutos' => 15,
        'descripcion' => 'Horario vespertino de 2:00 PM a 10:00 PM'
    ]
];

echo "Creando horarios predeterminados...\n";
foreach ($horarios as $horario) {
    if ($horarioModel->create($horario)) {
        echo "✓ Creado: {$horario['nombre']}\n";
    } else {
        echo "✗ Error creando: {$horario['nombre']}\n";
    }
}

// Crear tipos de justificación basados en normas AEFCM
$tiposJustificacion = [
    [
        'nombre' => 'Días Económicos',
        'descripcion' => 'Uso de días económicos acumulados según artículo 74 Ley Federal del Trabajo',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Enfermedad',
        'descripcion' => 'Incapacidad médica certificada por institución de seguridad social',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Maternidad/Paternidad',
        'descripcion' => 'Licencia de maternidad/paternidad según artículo 170 LFT',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Accidente de Trabajo',
        'descripcion' => 'Accidente laboral cubierto por seguro social',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Fallecimiento Familiar',
        'descripcion' => 'Fallecimiento de familiar directo (padres, hijos, cónyuge)',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Matrimonio',
        'descripcion' => 'Licencia por matrimonio propio según artículo 132 LFT',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Trámites Oficiales',
        'descripcion' => 'Trámites oficiales con autoridad competente',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Problemas de Transporte',
        'descripcion' => 'Falla en transporte público sin alternativa viable',
        'requiere_aprobacion' => false
    ],
    [
        'nombre' => 'Emergencia Familiar',
        'descripcion' => 'Emergencia familiar grave debidamente justificada',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Capacitación Laboral',
        'descripcion' => 'Capacitación laboral autorizada por la institución',
        'requiere_aprobacion' => true
    ],
    [
        'nombre' => 'Otro',
        'descripcion' => 'Otra causa debidamente justificada y documentada',
        'requiere_aprobacion' => true
    ]
];

echo "\nCreando tipos de justificación...\n";
foreach ($tiposJustificacion as $tipo) {
    if ($tipoJustificacionModel->create($tipo)) {
        echo "✓ Creado: {$tipo['nombre']}\n";
    } else {
        echo "✗ Error creando: {$tipo['nombre']}\n";
    }
}

echo "\nConfiguración inicial completada.\n";
echo "Puede acceder a:\n";
echo "- Gestión de horarios: /horarios\n";
echo "- Justificaciones: /justificaciones\n";
?>
