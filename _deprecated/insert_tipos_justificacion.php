<?php
require_once 'config.php';

$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

try {
    $db->exec("ALTER TABLE tipos_justificacion ADD COLUMN requiere_documento BOOLEAN DEFAULT FALSE AFTER requiere_aprobacion");
    echo "Columna agregada\n";
} catch (Exception $e) {
    echo "Columna ya existe o error: " . $e->getMessage() . "\n";
}

$tipos = [
    ['Nómina', 'Tiempo justificado por nómina', 0, 0],
    ['Ausencia justificada', 'Ausencia justificada sin documento', 0, 0],
    ['Comisión', 'Comisión de trabajo', 1, 1],
    ['Día económico', 'Día económico solicitado', 1, 1],
    ['Licencia médica', 'Licencia por enfermedad', 1, 1],
    ['Médico', 'Justificación por consulta médica', 1, 1],
    ['Personal', 'Asunto personal', 1, 0],
    ['Transporte', 'Problemas de transporte', 0, 0],
    ['Familiar', 'Emergencia familiar', 1, 1]
];

foreach ($tipos as $t) {
    $stmt = $db->prepare("INSERT IGNORE INTO tipos_justificacion (nombre, descripcion, requiere_aprobacion, requiere_documento) VALUES (?, ?, ?, ?)");
    $stmt->execute([$t[0], $t[1], $t[2], $t[3]]);
    echo "Insertado: " . $t[0] . "\n";
}

echo "Listo!\n";
