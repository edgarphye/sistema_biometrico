<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Sancion.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/DispositivoBiometrico.php';
require_once __DIR__ . '/../models/HorarioLaboral.php';
require_once __DIR__ . '/../models/LogDispositivo.php';
require_once __DIR__ . '/../services/AsistenciaService.php';

use PHPUnit\Framework\TestCase;

class TestClasificacionRetardos extends TestCase {
    private $db;
    private $pdo;
    private $retardoModel;
    private $sancionModel;
    private $asistenciaService;
    private $empleado_id;
    private $dispositivo_id;

    protected function setUp(): void {
        $this->db = new Database();
        $this->pdo = $this->db->getConnection();
        $this->retardoModel = new Retardo();
        $this->sancionModel = new Sancion();
        $this->asistenciaService = new AsistenciaService();

        // Crear empleado de prueba
        $stmt = $this->pdo->prepare("INSERT INTO empleados (nombre, apellido, email, rfc, curp, fecha_nacimiento, fecha_ingreso, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Juan', 'Pérez', 'juan@test.com', 'JUAP123456', 'JUAP123456HDFRRN01', '1990-01-01', '2023-01-01', 1]);
        $this->empleado_id = $this->pdo->lastInsertId();

        // Crear dispositivo de prueba
        $stmt = $this->pdo->prepare("INSERT INTO dispositivos_biometricos (nombre, sede, activo) VALUES (?, ?, ?)");
        $stmt->execute(['Dispositivo Test', 'Sede Central', 1]);
        $this->dispositivo_id = $this->pdo->lastInsertId();

        // Crear horario de prueba
        $stmt = $this->pdo->prepare("INSERT INTO horarios_laborales (nombre, hora_entrada, hora_salida, tolerancia_minutos, activo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Horario Test', '09:00:00', '18:00:00', 15, 1]);
        $horario_id = $this->pdo->lastInsertId();

        // Asignar horario al empleado
        $stmt = $this->pdo->prepare("INSERT INTO empleado_horarios (empleado_id, horario_id, fecha_asignacion) VALUES (?, ?, ?)");
        $stmt->execute([$this->empleado_id, $horario_id, '2023-01-01']);
    }

    protected function tearDown(): void {
        // Limpiar datos de prueba
        $this->pdo->exec("DELETE FROM retardos WHERE empleado_id = {$this->empleado_id}");
        $this->pdo->exec("DELETE FROM sanciones WHERE empleado_id = {$this->empleado_id}");
        $this->pdo->exec("DELETE FROM asistencia WHERE empleado_id = {$this->empleado_id}");
        $this->pdo->exec("DELETE FROM empleado_horarios WHERE empleado_id = {$this->empleado_id}");
        $this->pdo->exec("DELETE FROM horarios_laborales WHERE id NOT IN (SELECT DISTINCT horario_id FROM empleado_horarios)");
        $this->pdo->exec("DELETE FROM empleados WHERE id = {$this->empleado_id}");
        $this->pdo->exec("DELETE FROM dispositivos_biometricos WHERE id = {$this->dispositivo_id}");
    }

    public function testClasificacionRetardo() {
        // Test retardo menor (11-20 min)
        $retardo = $this->asistenciaService->calcularRetardo('09:15:00', $this->empleado_id, '2023-01-01', $this->dispositivo_id);
        $this->assertEquals('menor', $retardo['tipo'], 'Retardo de 15 min debería ser menor');

        // Test retardo mayor (21-30 min)
        $retardo = $this->asistenciaService->calcularRetardo('09:25:00', $this->empleado_id, '2023-01-01', $this->dispositivo_id);
        $this->assertEquals('mayor', $retardo['tipo'], 'Retardo de 25 min debería ser mayor');

        // Test puntual
        $retardo = $this->asistenciaService->calcularRetardo('09:00:00', $this->empleado_id, '2023-01-01', $this->dispositivo_id);
        $this->assertEquals('puntual', $retardo['tipo'], 'Llegada a tiempo debería ser puntual');

        // Test tolerancia (1-10 min o >30 min)
        $retardo = $this->asistenciaService->calcularRetardo('09:05:00', $this->empleado_id, '2023-01-01', $this->dispositivo_id);
        $this->assertEquals('tolerancia', $retardo['tipo'], 'Retardo de 5 min debería ser tolerancia');

        $retardo = $this->asistenciaService->calcularRetardo('09:35:00', $this->empleado_id, '2023-01-01', $this->dispositivo_id);
        $this->assertEquals('tolerancia', $retardo['tipo'], 'Retardo de 35 min debería ser tolerancia');
    }

    public function testAcumulacionQuincenal() {
        // Insertar retardos en primera quincena
        $fechas_quincena1 = ['2023-01-02', '2023-01-05', '2023-01-10'];

        foreach ($fechas_quincena1 as $fecha) {
            $this->retardoModel->registrarRetardo($this->empleado_id, $fecha, '09:15:00', 15, 'menor', 1, $this->dispositivo_id);
        }

        // Verificar acumulación primera quincena
        $acumulados = $this->retardoModel->getRetardosAcumuladosQuincena($this->empleado_id, '2023-01-10');
        $this->assertEquals(3, $acumulados, 'Debería haber 3 retardos acumulados en primera quincena');

        // Insertar retardos en segunda quincena
        $fechas_quincena2 = ['2023-01-16', '2023-01-20'];

        foreach ($fechas_quincena2 as $fecha) {
            $this->retardoModel->registrarRetardo($this->empleado_id, $fecha, '09:15:00', 15, 'menor', 1, $this->dispositivo_id);
        }

        // Verificar acumulación segunda quincena
        $acumulados = $this->retardoModel->getRetardosAcumuladosQuincena($this->empleado_id, '2023-01-20');
        $this->assertEquals(2, $acumulados, 'Debería haber 2 retardos acumulados en segunda quincena');
    }

    public function testRegistroEntradaConLogicaRetardo() {
        // Simular registro de entrada con retardo menor
        $result = $this->asistenciaService->registrarEntrada($this->empleado_id, $this->dispositivo_id, 'huella', null, 95, null, 150);

        $this->assertTrue($result, 'Registro de entrada debería ser exitoso');

        // Verificar que se registró el retardo
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM retardos WHERE empleado_id = ? AND fecha = ?");
        $stmt->execute([$this->empleado_id, date('Y-m-d')]);
        $retardos = $stmt->fetch()['total'];
        $this->assertGreaterThan(0, $retardos, 'Debería haberse registrado un retardo');

        // Verificar acumulación
        $acumulados = $this->retardoModel->getRetardosAcumuladosQuincena($this->empleado_id, date('Y-m-d'));
        $this->assertGreaterThan(0, $acumulados, 'Debería haber acumulación de retardos');

        // Verificar sanción si acumulados >=1
        if ($acumulados >= 1) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM sanciones WHERE empleado_id = ? AND YEAR(fecha_inicio) = ?");
            $stmt->execute([$this->empleado_id, date('Y')]);
            $sanciones = $stmt->fetch()['total'];
            $this->assertGreaterThan(0, $sanciones, 'Debería haberse creado una sanción por acumulación');
        }
    }

    public function testFuncionalidadesExistentesNoAfectadas() {
        // Test registro de salida
        $result = $this->asistenciaService->registrarSalida($this->empleado_id, $this->dispositivo_id, 'huella', null, 95, null, 150);
        $this->assertTrue($result, 'Registro de salida debería funcionar');

        // Test obtener asistencia filtrada
        $asistencia = $this->asistenciaService->getAsistenciaFiltrada(['empleado_id' => $this->empleado_id]);
        $this->assertIsArray($asistencia, 'Debería retornar array de asistencia');

        // Test calcular horas por empleado
        $horas = $this->asistenciaService->calcularHorasPorEmpleado(
            ['id' => $this->empleado_id, 'nombre' => 'Juan', 'apellido' => 'Pérez'],
            ['hora_entrada' => '09:00:00', 'hora_salida' => '18:00:00'],
            [],
            date('Y-m-d')
        );
        $this->assertIsArray($horas, 'Debería retornar cálculo de horas');
        $this->assertArrayHasKey('horas_trabajadas', $horas, 'Debería tener horas trabajadas');
    }
}
?>