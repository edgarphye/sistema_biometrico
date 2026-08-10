<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/Asistencia.php';
require_once __DIR__ . '/../../models/Empleado.php';

/**
 * @group unit
 */
class AsistenciaTest extends TestCase {
    private $asistenciaModel;
    private $empleadoModel;
    private $pdo;
    private $empleadoId;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
        $this->asistenciaModel = new Asistencia();
        $this->empleadoModel = new Empleado();

        $data = [
            'nombre' => 'TestAsistencia',
            'apellido' => 'Empleado',
            'rfc' => 'TAST890101XXX',
            'curp' => 'TAST890101HDFRRN01',
            'jerarquia' => 'staff',
            'activo' => 1
        ];
        $this->empleadoId = $this->empleadoModel->create($data);

        $stmt = $this->pdo->prepare("
            INSERT INTO asistencia (empleado_id, fecha, hora_entrada, hora_salida, tipo_asistencia, dispositivo_id, tipo_biometria)
            VALUES (?, CURDATE(), '09:00:00', '18:00:00', 'normal', 1, 'huella')
        ");
        $stmt->execute([$this->empleadoId]);
    }

    protected function tearDown(): void {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testGetById() {
        $stmt = $this->pdo->query("SELECT id FROM asistencia WHERE empleado_id = $this->empleadoId LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($row);

        $result = $this->asistenciaModel->getById($row['id']);
        $this->assertNotEmpty($result);
        $this->assertEquals($this->empleadoId, $result['empleado_id']);
        $this->assertEquals('09:00:00', $result['hora_entrada']);
    }

    public function testGetAll() {
        $result = $this->asistenciaModel->getAll();
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testGetByEmpleado() {
        $result = $this->asistenciaModel->getByEmpleado($this->empleadoId);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertEquals($this->empleadoId, $result[0]['empleado_id']);
    }

    public function testGetByEmpleadoWithDateRange() {
        $result = $this->asistenciaModel->getByEmpleado(
            $this->empleadoId,
            date('Y-m-d', strtotime('-1 day')),
            date('Y-m-d', strtotime('+1 day'))
        );
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testUpdate() {
        $stmt = $this->pdo->query("SELECT id FROM asistencia WHERE empleado_id = $this->empleadoId LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($row);

        $result = $this->asistenciaModel->update($row['id'], ['hora_entrada' => '10:00:00']);
        $this->assertTrue($result);

        $updated = $this->asistenciaModel->getById($row['id']);
        $this->assertEquals('10:00:00', $updated['hora_entrada']);
    }

    public function testGetAsistenciaFiltrada() {
        $result = $this->asistenciaModel->getAsistenciaFiltrada([
            'empleado_id' => $this->empleadoId,
            'fecha_inicio' => date('Y-m-d', strtotime('-1 day')),
            'fecha_fin' => date('Y-m-d', strtotime('+1 day'))
        ]);
        $this->assertIsArray($result);
    }

    public function testGetAsistenciaConRetardos() {
        $result = $this->asistenciaModel->getAsistenciaConRetardos([
            'empleado_id' => $this->empleadoId
        ]);
        $this->assertIsArray($result);
    }

    public function testGetByDispositivo() {
        $result = $this->asistenciaModel->getByDispositivo(1);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testModelExists() {
        $this->assertNotNull($this->asistenciaModel);
        $this->assertInstanceOf(Asistencia::class, $this->asistenciaModel);
    }
}
