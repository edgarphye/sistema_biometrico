<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../services/AsistenciaService.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Sancion.php';
require_once __DIR__ . '/../models/Database.php';

use PHPUnit\Framework\TestCase;

class PseudocodigoRetardosFlowTest extends TestCase {
    private $db;
    private $asistenciaService;
    private $empleadoModel;
    private $retardoModel;
    private $sancionModel;
    private $empleado_id;

    protected function setUp(): void {
        $this->db = new Database();
        $this->db->getConnection()->beginTransaction();

        $this->asistenciaService = new AsistenciaService();
        $this->empleadoModel = new Empleado();
        $this->retardoModel = new Retardo();
        $this->sancionModel = new Sancion();

        // Crear un empleado de prueba
        $this->empleadoModel->create([
            'nombre' => 'Empleado',
            'apellido' => 'De Prueba',
            'rfc' => 'XXXX000000XXX',
            'curp' => 'XXXX000000XXXXXX',
            'area' => 'Pruebas',
            'jerarquia' => 'Jr',
            'huella_dactilar' => null,
            'foto_cara' => null
        ]);
        $this->empleado_id = $this->db->getConnection()->lastInsertId();
    }

    protected function tearDown(): void {
        $this->db->getConnection()->rollBack();
    }

    public function testRetardoMenorFlow() {
        // Simular llegada 15 minutos tarde
        $this->asistenciaService->registrarEntrada($this->empleado_id, 1, 'huella', null, 100, null, 0.5, '09:15:00');

        // Verificar que se creó un retardo 'menor'
        $retardos = $this->retardoModel->getByEmpleado($this->empleado_id);
        $this->assertCount(1, $retardos);
        $this->assertEquals('menor', $retardos[0]['tipo']);

        // Verificar que se creó una sanción 'nota_mala'
        $sanciones = $this->sancionModel->getByEmpleadoYear($this->empleado_id, date('Y'));
        $this->assertCount(1, $sanciones);
        $this->assertEquals('nota_mala', $sanciones[0]['tipo']);
    }

    public function testRetardoMayorFlow() {
        // Simular llegada 25 minutos tarde
        $this->asistenciaService->registrarEntrada($this->empleado_id, 1, 'huella', null, 100, null, 0.5, '09:25:00');

        // Verificar que se creó un retardo 'mayor'
        $retardos = $this->retardoModel->getByEmpleado($this->empleado_id);
        $this->assertCount(1, $retardos);
        $this->assertEquals('mayor', $retardos[0]['tipo']);

        // Verificar que se creó una sanción 'nota_mala'
        $sanciones = $this->sancionModel->getByEmpleadoYear($this->empleado_id, date('Y'));
        $this->assertCount(1, $sanciones);
        $this->assertEquals('nota_mala', $sanciones[0]['tipo']);
    }

    public function testAcumulacionRetardos() {
        // Simular dos retardos
        $this->asistenciaService->registrarEntrada($this->empleado_id, 1, 'huella', null, 100, null, 0.5, '09:15:00');
        $this->asistenciaService->registrarEntrada($this->empleado_id, 1, 'huella', null, 100, null, 0.5, '09:25:00');

        // Verificar que hay 2 retardos
        $retardos = $this->retardoModel->getByEmpleado($this->empleado_id);
        $this->assertCount(2, $retardos);

        // Verificar que hay 2 sanciones
        $sanciones = $this->sancionModel->getByEmpleadoYear($this->empleado_id, date('Y'));
        $this->assertCount(2, $sanciones);

        // Verificar el acumulado quincenal
        $acumulados = $this->retardoModel->getRetardosAcumuladosQuincena($this->empleado_id, date('Y-m-d'));
        $this->assertEquals(2, $acumulados);

        // Simular un tercer retardo que no debería contar como retardo
        $this->asistenciaService->registrarEntrada($this->empleado_id, 1, 'huella', null, 100, null, 0.5, '09:05:00');

        // Verificar que hay 2 retardos todavía
        $retardos = $this->retardoModel->getByEmpleado($this->empleado_id);
        $this->assertCount(2, $retardos);
    }
}
?>