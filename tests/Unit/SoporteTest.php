<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Empleado.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Retardo.php';
require_once __DIR__ . '/../../controllers/SoporteController.php';

class SoporteTest extends TestCase {
    private $pdo;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }

    public function testPermisosSoporte() {
        // Crear empleado
        $stmt = $this->pdo->prepare("INSERT INTO empleados (nombre, apellido, rfc, curp, area, jerarquia, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute(['Test', 'Empleado', 'TSTRFC1234567', 'TSTCURP1234567890', 'IT', 'staff']);
        $empleado_id = $this->pdo->lastInsertId();

        // Crear usuarios
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (username, password, rol, empleado_id) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin_test', password_hash('adminpass', PASSWORD_DEFAULT), 'admin', null]);
        $admin_id = $this->pdo->lastInsertId();

        $stmt->execute(['emp_test', password_hash('emppass', PASSWORD_DEFAULT), 'usuario', $empleado_id]);
        $emp_user_id = $this->pdo->lastInsertId();

        $stmt->execute(['other_test', password_hash('otherpass', PASSWORD_DEFAULT), 'usuario', null]);
        $other_user_id = $this->pdo->lastInsertId();

        // Crear archivo soporte en uploads
        $uploadDir = __DIR__ . '/../../uploads/justificaciones/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = 'soporte_retardo_test_' . time() . '.pdf';
        $filePath = $uploadDir . $filename;
        file_put_contents($filePath, "%PDF-1.4\n%\u00e2\u00e3\u00cf\u00d3\n");

        // Crear retardo ligado al soporte
        $stmt = $this->pdo->prepare("INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo, justificado, soporte) VALUES (?, ?, ?, ?, 0, ?)");
        $fecha = date('Y-m-d');
        $stmt->execute([$empleado_id, $fecha, 15, 'menor', 'uploads/justificaciones/' . $filename]);
        $retardo_id = $this->pdo->lastInsertId();

        $controller = new SoporteController();

        $this->assertTrue($controller->canAccessSoporte($admin_id, $filename, $this->pdo), 'Admin debe poder acceder');
        $this->assertTrue($controller->canAccessSoporte($emp_user_id, $filename, $this->pdo), 'Empleado propietario debe poder acceder');
        $this->assertFalse($controller->canAccessSoporte($other_user_id, $filename, $this->pdo), 'Otro usuario no debe poder acceder');
    }
}
