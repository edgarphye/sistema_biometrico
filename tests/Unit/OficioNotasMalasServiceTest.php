<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/OficioNotasMalasService.php';

/**
 * Pruebas de cálculo de notas malas y clasificación de incisos
 * del servicio de oficios "ATENTA NOTA".
 *
 * @group unit
 */
class OficioNotasMalasServiceTest extends TestCase {
    private $pdo;
    private $servicio;

    protected function setUp(): void {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
        $this->pdo->beginTransaction();
        $this->servicio = new OficioNotasMalasService();
    }

    protected function tearDown(): void {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function crearEmpleado(array $overrides = []): int {
        $data = array_merge([
            'nombre' => 'Prueba',
            'apellido' => 'Notas Malas',
            'rfc' => 'PNM' . str_pad((string)random_int(100, 999), 6, '0', STR_PAD_LEFT) . 'XX',
            'curp' => 'PNM' . str_pad((string)random_int(100000, 999999), 9, '0', STR_PAD_LEFT),
            'area' => 'TEST',
            'jerarquia' => 'staff',
            'activo' => 1
        ], $overrides);

        $stmt = $this->pdo->prepare(
            "INSERT INTO empleados (nombre, apellido, rfc, curp, area, jerarquia, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['nombre'], $data['apellido'], $data['rfc'], $data['curp'],
            $data['area'], $data['jerarquia'], $data['activo']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function crearRetardo(int $empleado_id, string $fecha, string $tipo, int $justificado = 0): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO retardos (empleado_id, fecha, hora_entrada, minutos_retardo, tipo_retraso, justificado)
             VALUES (?, ?, '08:30:00', 15, ?, ?)"
        );
        $stmt->execute([$empleado_id, $fecha, $tipo, $justificado]);
        return (int)$this->pdo->lastInsertId();
    }

    private function crearRetardoConMinutos(int $empleado_id, string $fecha, string $tipo, int $minutos, int $justificado = 0): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO retardos (empleado_id, fecha, hora_entrada, minutos_retardo, tipo_retraso, justificado)
             VALUES (?, ?, '08:30:00', ?, ?, ?)"
        );
        $stmt->execute([$empleado_id, $fecha, $minutos, $tipo, $justificado]);
        return (int)$this->pdo->lastInsertId();
    }

    private function crearNotaMala(int $empleado_id, int $retardo_id, string $tipo, string $periodo): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)
             VALUES (?, ?, ?, 1, ?, 'nota de prueba')"
        );
        $stmt->execute([$empleado_id, $retardo_id, $tipo, $periodo]);
    }

    public function testFormulaNotasMalas() {
        $empleado_id = $this->crearEmpleado();

        // 3 retardos menores (floor(3/2)=1) + 1 retardo mayor (1) => 2 notas
        $this->crearRetardo($empleado_id, '2026-03-02', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-04', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-06', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-09', 'retardo_mayor');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertNotNull($inc);
        $this->assertEquals(3, $inc['menores']);
        $this->assertEquals(1, $inc['mayores']);
        $this->assertEquals(0, $inc['faltas']);
        $this->assertEquals(2, $inc['notas_malas']);
        $this->assertFalse($inc['requiere_suspension']);
        $this->assertCount(3, $inc['inciso_a']);
        $this->assertCount(1, $inc['inciso_b']);
    }

    public function testFormulaConFaltas() {
        $empleado_id = $this->crearEmpleado();

        // 2 menores (floor=1) + 1 mayor (1) + 1 falta (1) => 3 notas
        $this->crearRetardo($empleado_id, '2026-03-02', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-04', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-09', 'retardo_mayor');
        $this->crearRetardo($empleado_id, '2026-03-11', 'falta');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(2, $inc['menores']);
        $this->assertEquals(1, $inc['mayores']);
        $this->assertEquals(1, $inc['faltas']);
        $this->assertEquals(3, $inc['notas_malas']);
        $this->assertCount(2, $inc['inciso_b']);
    }

    public function testSuspensionPorCincoOMasNotas() {
        $empleado_id = $this->crearEmpleado();

        // 8 retardos menores (floor(8/2)=4) + 1 mayor (1) => 5 notas => suspensión
        for ($i = 0; $i < 8; $i++) {
            $dia = 1 + $i;
            $this->crearRetardo($empleado_id, '2026-03-' . str_pad((string)$dia, 2, '0', STR_PAD_LEFT), 'retardo_menor');
        }
        $this->crearRetardo($empleado_id, '2026-03-10', 'retardo_mayor');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(5, $inc['notas_malas']);
        $this->assertTrue($inc['requiere_suspension']);
    }

    public function testRetardosJustificadosNoCuentan() {
        $empleado_id = $this->crearEmpleado();

        $this->crearRetardo($empleado_id, '2026-03-02', 'retardo_menor', 1);
        $this->crearRetardo($empleado_id, '2026-03-04', 'retardo_mayor', 1);

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertNull($inc, 'Retardos justificados no deben generar notas');
    }

    public function testFiltroQuincena() {
        $empleado_id = $this->crearEmpleado();

        $this->crearRetardo($empleado_id, '2026-03-02', 'retardo_menor');  // q1
        $this->crearRetardo($empleado_id, '2026-03-20', 'retardo_menor');  // q2

        $q1 = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 1);
        $q2 = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 2);

        $this->assertEquals(1, $q1['menores']);
        $this->assertEquals(1, $q2['menores']);
        $this->assertEquals(0, $q1['notas_malas']);
        $this->assertEquals(0, $q2['notas_malas']);
    }

    public function testRetardosFueraDePeriodoNoCuentan() {
        $empleado_id = $this->crearEmpleado();

        $this->crearRetardo($empleado_id, '2026-04-02', 'retardo_menor');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertNull($inc);
    }

    public function testEmparejamientoMenoresPorPeriodo() {
        $empleado_id = $this->crearEmpleado();

        // 3 menores en la 1ª quincena (días 2,4,6) + 1 menor en la 2ª quincena (día 20)
        // Regla: el emparejamiento es sobre el periodo completo (mes) => floor(4/2) = 2 notas
        $this->crearRetardo($empleado_id, '2026-03-02', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-04', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-06', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-20', 'retardo_menor');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(4, $inc['menores']);
        $this->assertEquals(2, $inc['notas_malas'], 'Los menores deben emparejarse sobre el periodo completo, no por quincena');
        $this->assertEquals(2, $inc['notas_menores']);
        $this->assertEquals(0, $inc['notas_mayores']);
    }

    public function testMenoresEnQuincenasDistintasGeneranNota() {
        $empleado_id = $this->crearEmpleado();

        // 1 menor en cada quincena: antes daban 0 notas (fragmentación por quincena).
        $this->crearRetardo($empleado_id, '2026-03-05', 'retardo_menor');
        $this->crearRetardo($empleado_id, '2026-03-20', 'retardo_menor');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(1, $inc['notas_menores'], '2 menores en quincenas distintas suman 1 nota mala');
        $this->assertEquals(1, $inc['notas_malas']);
        $this->assertCount(2, $inc['inciso_a']);
    }

    public function testMinutosMayoresA30CuentanComoFalta() {
        $empleado_id = $this->crearEmpleado();

        // Retardo guardado como retardo_menor pero con más de 30 min => cuenta como falta (nota mayor).
        $this->crearRetardoConMinutos($empleado_id, '2026-03-05', 'retardo_menor', 45);

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(1, $inc['faltas']);
        $this->assertEquals(0, $inc['menores']);
        $this->assertEquals(1, $inc['notas_malas']);
        $this->assertEquals(1, $inc['notas_mayores']);
        $this->assertCount(1, $inc['inciso_b']);
    }

    public function testJustificadosExcesoPorQuincenaCuentan() {
        $empleado_id = $this->crearEmpleado();

        // 3 retardos mayores JUSTIFICADOS en la misma quincena: los primeros 2 se permiten,
        // el 3ro (excedente) ya no se puede justificar y genera 1 nota mala.
        // En el sistema, solo el excedente queda registrado en notas_malas.
        $this->crearRetardo($empleado_id, '2026-03-02', 'retardo_mayor', 1);
        $this->crearRetardo($empleado_id, '2026-03-04', 'retardo_mayor', 1);
        $r3 = $this->crearRetardo($empleado_id, '2026-03-06', 'retardo_mayor', 1);

        $this->crearNotaMala($empleado_id, $r3, 'retardo_mayor', '2026-03-01');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(1, $inc['mayores']);
        $this->assertEquals(1, $inc['notas_malas'], 'Solo el excedente de los 2 permitidos por quincena genera nota');
    }

    public function testJustificadosExcesoMenoresCuentanPorPar() {
        $empleado_id = $this->crearEmpleado();

        // 4 retardos menores JUSTIFICADOS en la misma quincena: los primeros 2 se permiten,
        // los 2 excedentes forman un par = 1 nota mala. El sistema guarda UN registro de
        // notas_malas por cada par excedente (2 menores excedentes = 1 registro = 1 nota).
        $r1 = $this->crearRetardo($empleado_id, '2026-03-02', 'retardo_menor', 1);
        $r2 = $this->crearRetardo($empleado_id, '2026-03-04', 'retardo_menor', 1);
        $r3 = $this->crearRetardo($empleado_id, '2026-03-06', 'retardo_menor', 1);
        $r4 = $this->crearRetardo($empleado_id, '2026-03-08', 'retardo_menor', 1);

        // Los 2 permitidos no llevan nota; cada par excedente (r3-r4) lleva UN registro.
        $this->crearNotaMala($empleado_id, $r4, 'retardo_menor', '2026-03-01');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(1, $inc['notas_menores'], 'El par de menores excedentes genera 1 nota mala');
        $this->assertEquals(1, $inc['notas_malas']);
        // Los justificados se listan en el oficio solo si cuentan con nota registrada:
        // el par excedente tiene un solo registro (sobre el 2º menor del par).
        $this->assertCount(1, $inc['inciso_a']);
    }

    public function testJustificadosExcesoMenoresVariosPares() {
        $empleado_id = $this->crearEmpleado();

        // 6 menores JUSTIFICADOS en la misma quincena: 2 permitidos + 4 excedentes = 2 pares = 2 notas.
        // Un registro de notas_malas por cada par excedente.
        $ids = [];
        foreach ([2, 4, 6, 8, 10, 12] as $i => $dia) {
            $ids[] = $this->crearRetardo($empleado_id, '2026-03-' . str_pad((string)$dia, 2, '0', STR_PAD_LEFT), 'retardo_menor', 1);
        }
        $this->crearNotaMala($empleado_id, $ids[4], 'retardo_menor', '2026-03-01');
        $this->crearNotaMala($empleado_id, $ids[5], 'retardo_menor', '2026-03-01');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(2, $inc['notas_menores'], '4 menores excedentes = 2 pares = 2 notas malas');
        $this->assertEquals(2, $inc['notas_malas']);
    }

    public function testJustificadosDentroDeLaCuotaNoCuentan() {
        $empleado_id = $this->crearEmpleado();

        // 2 retardos menores justificados en la misma quincena (dentro de la cuota permitida)
        $r1 = $this->crearRetardo($empleado_id, '2026-03-02', 'retardo_menor', 1);
        $r2 = $this->crearRetardo($empleado_id, '2026-03-04', 'retardo_menor', 1);

        // Sin notas_malas asociadas: no deben generar oficio
        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertNull($inc, 'Los 2 retardos justificados permitidos por quincena no generan nota');
        $this->assertEquals(0, $this->pdo->query("SELECT COUNT(*) FROM notas_malas WHERE empleado_id = {$empleado_id}")->fetchColumn());
    }

    public function testDiasSuspensionMultiples() {
        $empleado_id = $this->crearEmpleado();

        // 8 menores q1 (días 1-8) = 4 notas + 8 menores q2 (días 16-23) = 4 notas
        // + 2 mayores = 10 notas => 2 días de suspensión
        for ($i = 1; $i <= 8; $i++) {
            $this->crearRetardo($empleado_id, '2026-03-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT), 'retardo_menor');
        }
        for ($i = 16; $i <= 23; $i++) {
            $this->crearRetardo($empleado_id, '2026-03-' . $i, 'retardo_menor');
        }
        $this->crearRetardo($empleado_id, '2026-03-10', 'retardo_mayor');
        $this->crearRetardo($empleado_id, '2026-03-24', 'retardo_mayor');

        $inc = $this->servicio->obtenerIncidenciasEmpleado($empleado_id, 3, 2026, 0);

        $this->assertEquals(10, $inc['notas_malas']);
        $this->assertEquals(2, $inc['dias_suspension']);
        $this->assertTrue($inc['requiere_suspension']);
    }

    public function testProgramarDiasSuspension() {
        // Desde domingo 2026-08-09: próximo martes 11, luego martes 18 (semana distinta)
        $dias = OficioNotasMalasService::programarDiasSuspension(2, new DateTime('2026-08-09'));

        $this->assertCount(2, $dias);
        foreach ($dias as $d) {
            $this->assertContains((int)$d->format('N'), [2, 3, 4], 'La suspensión solo cae martes, miércoles o jueves');
        }
        $this->assertNotEquals(
            $dias[0]->format('oW'),
            $dias[1]->format('oW'),
            'Los días de suspensión deben programarse en semanas distintas'
        );
        $this->assertEquals('2026-08-11', $dias[0]->format('Y-m-d'));
        $this->assertEquals('2026-08-18', $dias[1]->format('Y-m-d'));
    }

    public function testExtraerFolio() {
        $this->assertEquals('DGIFA/CA/RH-001/2026', OficioNotasMalasService::extraerFolio('Oficio DGIFA/CA/RH-001/2026'));
        $this->assertEquals('DGIFA/CA/RH-002/2026', OficioNotasMalasService::extraerFolio('Oficio DGIFA/CA/RH-002/2026'));
        $this->assertEquals('DGIFA/CA/RH-003/2026', OficioNotasMalasService::extraerFolio('Oficio DGIFA/CA/RH-003/2026'));
    }

    public function testConstruirTextoIncisos() {
        $this->assertSame('“a”', OficioNotasMalasService::construirTextoIncisos([['x']], []));
        $this->assertSame('“b”', OficioNotasMalasService::construirTextoIncisos([], [['x']]));
        $this->assertSame('“a” y “b”', OficioNotasMalasService::construirTextoIncisos([['x']], [['y']]));
        $this->assertSame('“b”', OficioNotasMalasService::construirTextoIncisos([], []));
    }

    public function testConstruirCuerpoLegal() {
        $cuerpo = OficioNotasMalasService::construirCuerpoLegal(4, '“a” y “b”');
        $this->assertStringContainsString('04 Nota (s) Mala (s)', $cuerpo);
        $this->assertStringContainsString('80 inciso “a” y “b”', $cuerpo);
        $this->assertStringContainsString('Art. 44 Fracción VI', $cuerpo);
    }

    public function testConstruirFechaEmision() {
        $texto = OficioNotasMalasService::construirFechaEmision(new DateTime('2026-08-09'));
        $this->assertSame('Ciudad de México, a 9 de Agosto de 2026', $texto);
    }

    public function testConstruirParrafoAdvertencia() {
        $texto = OficioNotasMalasService::construirParrafoAdvertencia();
        $this->assertStringContainsString('5 notas malas por retardos', $texto);
        $this->assertStringContainsString('Tribunal de Arbitraje', $texto);
        $this->assertStringContainsString('llegar con puntualidad', $texto);
    }

    public function testConstruirParrafoSuspensionUnDia() {
        $dias = OficioNotasMalasService::programarDiasSuspension(1, new DateTime('2026-08-09'));
        $texto = OficioNotasMalasService::construirParrafoSuspension($dias);
        $this->assertStringContainsString('le es aplicable 01 día de suspensión', $texto);
        $this->assertStringContainsString('el día 11 de agosto', $texto, 'El mes debe escribirse en minúsculas');
    }

    public function testConstruirParrafoSuspensionVariosDias() {
        $dias = OficioNotasMalasService::programarDiasSuspension(3, new DateTime('2026-08-09'));
        $texto = OficioNotasMalasService::construirParrafoSuspension($dias);
        $this->assertStringContainsString('le son aplicables 03 días de suspensión', $texto);
        $this->assertStringContainsString('el día 11 de agosto', $texto);
        $this->assertStringContainsString('y el día 25 de agosto', $texto, 'Debe separar con comas y cerrar con "y"');
    }

    public function testConstruirParrafoSuspensionSinDias() {
        $texto = OficioNotasMalasService::construirParrafoSuspension([]);
        $this->assertSame(OficioNotasMalasService::construirParrafoAdvertencia(), $texto);
    }

    public function testConstruirListaDiasSuspension() {
        $dias = OficioNotasMalasService::programarDiasSuspension(2, new DateTime('2026-08-09'));
        $lista = OficioNotasMalasService::construirListaDiasSuspension($dias);
        $this->assertStringContainsString('el día 11 de agosto', $lista);
        $this->assertStringContainsString('el día 18 de agosto', $lista);
        $this->assertStringContainsString(' y ', $lista);
    }

    public function testSegmentosCuerpoLegalMarcanNegritas() {
        $segmentos = OficioNotasMalasService::segmentosCuerpoLegal(4, '“a”');
        $textos = array_column($segmentos, 'text');
        $negritas = array_column($segmentos, 'bold');

        $this->assertCount(5, $segmentos);
        $this->assertSame('04 Nota (s) Mala (s)', $textos[1]);
        $this->assertTrue($negritas[1], 'El número de notas debe ir en negrita');
        $this->assertSame('“a” ', $textos[3]);
        $this->assertTrue($negritas[3], 'El inciso citado debe ir en negrita');
        $this->assertFalse($negritas[0]);
        $this->assertFalse($negritas[4]);
    }

    public function testConstruirCuerpoLegalCoincideConSegmentos() {
        $texto = OficioNotasMalasService::construirCuerpoLegal(4, '“a” y “b”');
        $segmentos = OficioNotasMalasService::segmentosCuerpoLegal(4, '“a” y “b”');
        $this->assertSame($texto, implode('', array_column($segmentos, 'text')));
    }

    public function testSegmentosParrafoSuspensionMarcanNegritas() {
        $dias = OficioNotasMalasService::programarDiasSuspension(2, new DateTime('2026-08-09'));
        $segmentos = OficioNotasMalasService::segmentosParrafoSuspension($dias);
        $negritas = array_column($segmentos, 'bold');

        $this->assertTrue($negritas[1], 'El número de días debe ir en negrita');
        $this->assertStringContainsString('02 días de suspensión', $segmentos[1]['text']);
        $this->assertTrue($negritas[3], 'Las fechas programadas deben ir en negrita');
        $this->assertStringContainsString('el día 11 de agosto', $segmentos[3]['text']);
    }

    public function testConstruirParrafoSuspensionCoincideConSegmentos() {
        $dias = OficioNotasMalasService::programarDiasSuspension(2, new DateTime('2026-08-09'));
        $texto = OficioNotasMalasService::construirParrafoSuspension($dias);
        $segmentos = OficioNotasMalasService::segmentosParrafoSuspension($dias);
        $this->assertSame($texto, implode('', array_column($segmentos, 'text')));
    }

    public function testModeloFolioFormateadoNoSeDuplica() {
        $metodo = new ReflectionMethod(OficioNotasMalasService::class, 'construirModelo');
        $metodo->setAccessible(true);
        $empleado = ['id' => 401, 'rfc' => 'X', 'area' => 'RH'];
        $incidencias = [
            'apellido' => 'PRUEBA', 'nombre' => 'EMPLEADO',
            'claves_presupuestales' => 'C1', 'inciso_a' => [], 'inciso_b' => [],
            'requiere_suspension' => false, 'dias_suspension' => 0, 'notas_malas' => 2,
        ];
        $config = ['prefijo' => 'DGIFA/CA/RH', 'anio' => 2026, 'iniciales' => 'X', 'nombre_firmante' => 'X', 'cargo_firmante' => 'X'];

        // Folio ya formateado (como lo extrae extraerFolio del título): no debe duplicarse.
        $modelo = $metodo->invoke(new OficioNotasMalasService(), $empleado, $incidencias, $config, 'DGIFA/CA/RH-002/2026', '04', 2026);
        $this->assertSame('DGIFA/CA/RH-002/2026', $modelo['folio']);
        $this->assertSame('ATENTA NOTA DGIFA/CA/RH-002/2026', $modelo['titulo']);

        // Folio numérico (como en generarOficio): se formatea.
        $modelo2 = $metodo->invoke(new OficioNotasMalasService(), $empleado, $incidencias, $config, 3, '04', 2026);
        $this->assertSame('DGIFA/CA/RH-003/2026', $modelo2['folio']);
    }
}
