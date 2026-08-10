<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/Horario.php';

/**
 * @group unit
 */
class HorarioTest extends TestCase {
    private $pdo;
    private $horarioModel;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
        $this->horarioModel = new Horario();
    }

    protected function tearDown(): void {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testCreateHorario() {
        $data = [
            'nombre' => 'Horario Test',
            'hora_entrada' => '09:00',
            'hora_salida' => '18:00',
            'tolerancia_minutos' => 10
        ];

        $result = $this->horarioModel->create($data);
        $this->assertTrue($result, 'Horario debe crearse exitosamente');

        $id = $this->pdo->lastInsertId();
        $this->assertNotEmpty($id, 'Horario debe tener ID');

        $horario = $this->horarioModel->getById($id);
        $this->assertNotEmpty($horario);
        $this->assertEquals('Horario Test', $horario['nombre']);
    }

    public function testGetAllHorarios() {
        $horarios = $this->horarioModel->getAll();
        $this->assertIsArray($horarios);
    }

    public function testUpdateHorario() {
        $createData = [
            'nombre' => 'Original Horario',
            'hora_entrada' => '08:00',
            'hora_salida' => '17:00',
            'tolerancia_minutos' => 15
        ];
        $this->horarioModel->create($createData);
        $id = $this->pdo->lastInsertId();

        $updateData = [
            'nombre' => 'Horario Actualizado',
            'hora_entrada' => '08:00',
            'hora_salida' => '17:00'
        ];
        $result = $this->horarioModel->update($id, $updateData);
        $this->assertTrue($result);

        $horario = $this->horarioModel->getById($id);
        $this->assertEquals('Horario Actualizado', $horario['nombre']);
    }

    public function testDeleteHorario() {
        $data = [
            'nombre' => 'Horario a Eliminar',
            'hora_entrada' => '07:00',
            'hora_salida' => '16:00',
            'tolerancia_minutos' => 5
        ];
        $this->horarioModel->create($data);
        $id = $this->pdo->lastInsertId();

        $result = $this->horarioModel->delete($id);
        $this->assertTrue($result, 'Horario debe eliminarse');

        $horario = $this->horarioModel->getById($id);
        $this->assertEmpty($horario, 'Horario eliminado no debe existir');
    }
}
