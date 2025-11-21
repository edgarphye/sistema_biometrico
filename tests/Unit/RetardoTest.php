<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Retardo.php';
require_once __DIR__ . '/../../models/Sancion.php';

class RetardoTest extends TestCase {
    private $pdo;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }

    public function testSuspensionPorCincoNotas() {
        // Crear empleado
        $stmt = $this->pdo->prepare("INSERT INTO empleados (nombre, apellido, rfc, curp, area, jerarquia, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute(['Test', 'Notas', 'TSTRFC000', 'TSTCURP000', 'IT', 'staff']);
        $empleado_id = $this->pdo->lastInsertId();

        $retModel = new Retardo();

        // Insertar 5 retardos no justificados en el mes actual
        $mes = date('n');
        $anio = date('Y');
        for ($i = 0; $i < 5; $i++) {
            $fecha = date('Y-m-d', strtotime("-" . ($i) . " days"));
            $stmt = $this->pdo->prepare("INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo, justificado) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$empleado_id, $fecha, 20, 'mayor']);
        }

        // Ejecutar creación de sanción
        $created = $retModel->crearSuspensionSiCorresponde($empleado_id, $mes, $anio, null);

        // Verificar que se haya creado una sanción
        $sancionModel = new Sancion();
        $sanciones = $sancionModel->getByEmpleadoYear($empleado_id, $anio);

        $this->assertNotEmpty($sanciones, 'Se debe crear al menos una sanción por 5 notas');
    }
}
