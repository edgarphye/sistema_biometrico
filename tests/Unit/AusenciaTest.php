<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/Ausencia.php';
require_once __DIR__ . '/../../models/Empleado.php';

/**
 * @group unit
 */
class AusenciaTest extends TestCase {
    private $ausenciaModel;
    private $empleadoModel;
    private $pdo;
    private $empleadoId;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
        $this->ausenciaModel = new Ausencia();
        $this->empleadoModel = new Empleado();

        $data = [
            'nombre' => 'TestAusencia',
            'apellido' => 'Empleado',
            'rfc' => 'TSTE890101XXX',
            'curp' => 'TSTE890101HDFRRN01',
            'email' => 'test.ausencia@test.com',
            'area' => 'Testing',
            'puesto' => 'Tester',
            'jerarquia' => 'staff',
            'activo' => 1
        ];
        $this->empleadoId = $this->empleadoModel->create($data);
    }

    protected function tearDown(): void {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testAusenciaModelExists() {
        $this->assertNotNull($this->ausenciaModel);
        $this->assertInstanceOf(Ausencia::class, $this->ausenciaModel);
    }

    public function testGetByEmpleadoReturnsArray() {
        $result = $this->ausenciaModel->getByEmpleado(null);
        $this->assertIsArray($result);
    }

    public function testGetByEmpleadoWithDatesReturnsArray() {
        $result = $this->ausenciaModel->getByEmpleado(
            $this->empleadoId,
            date('Y-m-d', strtotime('-30 days')),
            date('Y-m-d')
        );
        $this->assertIsArray($result);
    }

    public function testCreateAusencia() {
        $data = [
            'empleado_id' => $this->empleadoId,
            'fecha_inicio' => date('Y-m-d'),
            'fecha_fin' => date('Y-m-d'),
            'tipo' => 'personal',
            'motivo' => 'Test de ausencia'
        ];

        $id = $this->ausenciaModel->create($data);
        $this->assertNotEmpty($id, 'Ausencia debe crearse con ID');
    }

    public function testJustificarAusencia() {
        $data = [
            'empleado_id' => $this->empleadoId,
            'fecha_inicio' => date('Y-m-d'),
            'fecha_fin' => date('Y-m-d'),
            'tipo' => 'personal',
            'motivo' => 'Test justificacion'
        ];

        $id = $this->ausenciaModel->create($data);
        $this->assertNotEmpty($id);

        $result = $this->ausenciaModel->justificarAusencia($id);
        $this->assertTrue($result);
    }
}
