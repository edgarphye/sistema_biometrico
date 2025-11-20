<?php
// CLI test for soporte permissions
// Usage: php tests/test_soporte_permissions.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../controllers/SoporteController.php';

$db = new Database();
$pdo = $db->getConnection();

function rr($msg) { echo $msg . PHP_EOL; }

// Start transaction so tests can be rolled back
$pdo->beginTransaction();
try {
    // Create empleado
    $stmt = $pdo->prepare("INSERT INTO empleados (nombre, apellido, rfc, curp, area, jerarquia, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute(['Test', 'Empleado', 'TSTRFC1234567', 'TSTCURP1234567890', 'IT', 'staff']);
    $empleado_id = $pdo->lastInsertId();

    // Create users: admin and empleado_user and other_user
    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol, empleado_id) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin_test', password_hash('adminpass', PASSWORD_DEFAULT), 'admin', null]);
    $admin_id = $pdo->lastInsertId();

    $stmt->execute(['emp_test', password_hash('emppass', PASSWORD_DEFAULT), 'usuario', $empleado_id]);
    $emp_user_id = $pdo->lastInsertId();

    $stmt->execute(['other_test', password_hash('otherpass', PASSWORD_DEFAULT), 'usuario', null]);
    $other_user_id = $pdo->lastInsertId();

    // Create dummy soporte file
    $uploadDir = __DIR__ . '/../uploads/justificaciones/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = 'soporte_retardo_test_' . time() . '.pdf';
    $filePath = $uploadDir . $filename;
    file_put_contents($filePath, "%PDF-1.4\n%\u00e2\u00e3\u00cf\u00d3\n");

    // Create retardo linked to soporte
    $stmt = $pdo->prepare("INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo, justificado, soporte) VALUES (?, ?, ?, ?, 0, ?)");
    $fecha = date('Y-m-d');
    $stmt->execute([$empleado_id, $fecha, 15, 'menor', 'uploads/justificaciones/' . $filename]);
    $retardo_id = $pdo->lastInsertId();

    rr("Created test records: empleado=$empleado_id, admin=$admin_id, emp_user=$emp_user_id, other=$other_user_id, retardo=$retardo_id");

    $controller = new SoporteController();

    // Test admin access
    $canAdmin = $controller->canAccessSoporte($admin_id, $filename, $pdo) ? 'YES' : 'NO';
    rr("Admin access: $canAdmin (expected YES)");

    // Test empleado owner access
    $canEmp = $controller->canAccessSoporte($emp_user_id, $filename, $pdo) ? 'YES' : 'NO';
    rr("Empleado owner access: $canEmp (expected YES)");

    // Test other user access
    $canOther = $controller->canAccessSoporte($other_user_id, $filename, $pdo) ? 'YES' : 'NO';
    rr("Other user access: $canOther (expected NO)");

    // Clean up and rollback
    $pdo->rollBack();
    rr("Transaction rolled back; test files remain for inspection: $filePath");
} catch (Exception $e) {
    $pdo->rollBack();
    rr("Test failed: " . $e->getMessage());
    exit(1);
}

?>