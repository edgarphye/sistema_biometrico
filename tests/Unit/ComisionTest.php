<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/Comision.php';
require_once __DIR__ . '/../../models/Empleado.php';

/**
 * @group unit
 */
class ComisionTest extends TestCase {
    private $comisionModel;
    private $empleadoModel;
    private $pdo;
    private $empleadoId;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
        $this->comisionModel = new Comision();
        $this->empleadoModel = new Empleado();

        $data = [
            'nombre' => 'TestComision',
            'apellido' => 'Empleado',
            'rfc' => 'TCMS890101XXX',
            'curp' => 'TCMS890101HDFRRN01',
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

    public function testModelExists() {
        $this->assertNotNull($this->comisionModel);
        $this->assertInstanceOf(Comision::class, $this->comisionModel);
    }

    private function comisionData(array $overrides = []): array {
        return array_merge([
            'empleado_id' => $this->empleadoId,
            'descripcion' => 'Comision de prueba',
            'monto' => 500.00,
            'fecha_inicio' => date('Y-m-d', strtotime('-5 days')),
            'fecha_fin' => date('Y-m-d', strtotime('-1 day')),
            'tipo_comision' => 'otros'
        ], $overrides);
    }

    public function testCreate() {
        $id = $this->comisionModel->create($this->comisionData());
        $this->assertNotEmpty($id, 'Comision debe crearse con ID');
    }

    public function testGetByEmpleado() {
        $this->comisionModel->create($this->comisionData(['descripcion' => 'Comision test']));

        $result = $this->comisionModel->getByEmpleado($this->empleadoId);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testJustificarComision() {
        $id = $this->comisionModel->create($this->comisionData(['descripcion' => 'Comision a justificar']));
        $this->assertNotEmpty($id);

        $result = $this->comisionModel->justificarComision($id, null, 'Justificacion de prueba');
        $this->assertTrue($result);
    }

    public function testAprobarComision() {
        $id = $this->comisionModel->create($this->comisionData(['descripcion' => 'Comision a aprobar']));
        $this->assertNotEmpty($id);

        $result = $this->comisionModel->aprobarComision($id, null);
        $this->assertTrue($result);
    }

    public function testGetTotalComisiones() {
        $this->comisionModel->create($this->comisionData(['descripcion' => 'Comision p/ total', 'monto' => 1000.00]));

        $total = $this->comisionModel->getTotalComisiones(
            $this->empleadoId,
            date('n'),
            date('Y')
        );
        $this->assertGreaterThanOrEqual(1000.0, (float)$total);
    }

    public function testGetComisionesPendientesAprobacion() {
        $this->comisionModel->create($this->comisionData([
            'descripcion' => 'Comision pendiente',
            'requiere_aprobacion' => 1
        ]));

        $pendientes = $this->comisionModel->getComisionesPendientesAprobacion();
        $this->assertIsArray($pendientes);
        $this->assertGreaterThanOrEqual(1, count($pendientes));
    }

    public function testGetVencidas() {
        $data = [
            'empleado_id' => $this->empleadoId,
            'descripcion' => 'Comision vencida',
            'monto' => 400.00,
            'fecha_inicio' => date('Y-m-d', strtotime('-10 days')),
            'fecha_fin' => date('Y-m-d', strtotime('-5 days')),
            'fecha_vencimiento' => date('Y-m-d', strtotime('-5 days')),
            'tipo_comision' => 'otros'
        ];
        $this->comisionModel->create($data);

        $vencidas = $this->comisionModel->getVencidas($this->empleadoId);
        $this->assertIsArray($vencidas);
    }

    public function testGetLimitesAEFCM() {
        $limites = $this->comisionModel->getLimitesAEFCM();
        $this->assertIsArray($limites);
        $this->assertArrayHasKey('limite_mensual', $limites);
        $this->assertArrayHasKey('dias_maximos', $limites);
        $this->assertArrayHasKey('tipos', $limites);
        $this->assertEquals(3000.0, $limites['limite_mensual']);
    }

    public function testGetTiposComisionAEFCM() {
        $tipos = $this->comisionModel->getTiposComisionAEFCM();
        $this->assertIsArray($tipos);
        $this->assertNotEmpty($tipos);
    }
}
