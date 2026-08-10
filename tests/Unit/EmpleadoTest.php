<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Empleado.php';

/**
 * @group unit
 */
class EmpleadoTest extends TestCase {
    private $pdo;
    private $empleadoModel;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
        $this->empleadoModel = new Empleado();
    }

    protected function tearDown(): void {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function makeData(array $overrides = []): array {
        return array_merge([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'rfc' => 'JUAP890101XXX',
            'curp' => 'JUAP890101HDFRRN01',
            'email' => 'juan.perez@test.com',
            'area' => 'Sistemas',
            'puesto' => 'Desarrollador',
            'jerarquia' => 'staff',
            'activo' => 1
        ], $overrides);
    }

    public function testCreateEmpleado() {
        $id = $this->empleadoModel->create($this->makeData());
        $this->assertNotEmpty($id, 'Empleado debe crearse con ID');

        $empleado = $this->empleadoModel->getById($id);
        $this->assertNotEmpty($empleado);
        $this->assertEquals('Juan', $empleado['nombre']);
        $this->assertEquals('Perez', $empleado['apellido']);
    }

    public function testGetAllEmpleados() {
        $this->empleadoModel->create($this->makeData(['nombre' => 'Ana', 'rfc' => 'ANAL890101XXX']));

        $empleados = $this->empleadoModel->getAll();
        $this->assertIsArray($empleados);
        $this->assertGreaterThanOrEqual(1, count($empleados));
    }

    public function testUpdateEmpleado() {
        $id = $this->empleadoModel->create($this->makeData());

        $updateData = $this->makeData(['nombre' => 'Modificado']);
        $result = $this->empleadoModel->update($id, $updateData);
        $this->assertTrue($result);

        $empleado = $this->empleadoModel->getById($id);
        $this->assertEquals('Modificado', $empleado['nombre']);
    }

    public function testDeleteEmpleado() {
        $id = $this->empleadoModel->create($this->makeData());

        $result = $this->empleadoModel->delete($id);
        $this->assertTrue($result);

        $empleado = $this->empleadoModel->getById($id);
        $this->assertEquals(0, $empleado['activo'], 'Empleado debe estar desactivado');
    }

    public function testEmpleadoActivo() {
        $id = $this->empleadoModel->create($this->makeData());

        $empleado = $this->empleadoModel->getById($id);
        $this->assertEquals(1, $empleado['activo']);
    }
}
