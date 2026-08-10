<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Empleado.php';
require_once __DIR__ . '/../../models/Asistencia.php';
require_once __DIR__ . '/../../models/Retardo.php';

/**
 * @group integration
 */
class AsistenciaRetardoJustificacionFlowTest extends TestCase {
    private $pdo;
    private $empleadoModel;
    private $asistenciaModel;
    private $retardoModel;
    private $empleadoId;

    protected function setUp(): void {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();

        $this->empleadoModel = new Empleado();
        $this->asistenciaModel = new Asistencia();
        $this->retardoModel = new Retardo();

        $data = [
            'nombre' => 'Flow',
            'apellido' => 'Test',
            'rfc' => 'FLOW890101XXX',
            'curp' => 'FLOW890101HDFRRN01',
            'area' => 'Sistemas',
            'puesto' => 'Desarrollador',
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

    public function testFullFlowAsistenciaRetardoJustificacion() {
        $this->assertNotEmpty($this->empleadoId, 'Empleado debe existir');

        $asistenciaData = [
            'empleado_id' => $this->empleadoId,
            'fecha' => date('Y-m-d'),
            'hora_entrada' => '09:30:00',
            'hora_salida' => '18:00:00'
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO asistencia (empleado_id, fecha, hora_entrada, hora_salida)
            VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $asistenciaData['empleado_id'],
            $asistenciaData['fecha'],
            $asistenciaData['hora_entrada'],
            $asistenciaData['hora_salida']
        ]);
        $this->assertTrue($result, 'Asistencia debe registrarse');

        $retardoData = [
            'empleado_id' => $this->empleadoId,
            'fecha' => date('Y-m-d'),
            'minutos_retardo' => 25,
            'tipo_retraso' => 'retardo_mayor',
            'justificado' => 0
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO retardos (empleado_id, fecha, minutos_retardo, tipo_retraso, justificado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $retardoData['empleado_id'],
            $retardoData['fecha'],
            $retardoData['minutos_retardo'],
            $retardoData['tipo_retraso'],
            $retardoData['justificado']
        ]);
        $this->assertTrue($result, 'Retardo debe registrarse');

        $justificacionData = [
            'empleado_id' => $this->empleadoId,
            'fecha' => date('Y-m-d'),
            'tipo_justificacion' => 'medica',
            'motivo' => 'Cita medica programada',
            'estatus' => 'pendiente'
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO justificaciones (empleado_id, fecha_inicio, fecha_fin, tipo_justificacion, motivo, estatus)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $justificacionData['empleado_id'],
            $justificacionData['fecha'],
            $justificacionData['fecha'],
            $justificacionData['tipo_justificacion'],
            $justificacionData['motivo'],
            $justificacionData['estatus']
        ]);
        $this->assertTrue($result, 'Justificacion debe registrarse');

        $justificacionId = $this->pdo->lastInsertId();
        $this->assertNotEmpty($justificacionId);

        $stmt = $this->pdo->prepare("UPDATE retardos SET justificado = 1 WHERE empleado_id = ? AND fecha = ?");
        $stmt->execute([$this->empleadoId, date('Y-m-d')]);

        $stmt = $this->pdo->prepare("SELECT * FROM retardos WHERE empleado_id = ? AND fecha = ?");
        $stmt->execute([$this->empleadoId, date('Y-m-d')]);
        $retardoActualizado = $stmt->fetch();

        $this->assertNotEmpty($retardoActualizado);
        $this->assertEquals(1, $retardoActualizado['justificado'], 'Retardo debe marcarse como justificado');
    }
}
