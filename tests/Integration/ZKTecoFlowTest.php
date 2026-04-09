<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/ZKTecoUniversalParser.php';
require_once __DIR__ . '/../../models/ZKTecoAsistenciaInserter.php';
require_once __DIR__ . '/../../models/Database.php';

class ZKTecoFlowTest extends TestCase
{
    private $tempFile;

    protected function setUp(): void
    {
        // Crear ruta para archivo temporal .dat
        $this->tempFile = tempnam(sys_get_temp_dir(), 'zk_test_') . '.dat';
    }

    protected function tearDown(): void
    {
        // Limpiar archivo temporal
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testFlujoCompletoCargaEInsercion()
    {
        // 1. Preparar datos de prueba en archivo .dat (Formato Estándar ZKTeco)
        // Estructura: ID \t Time \t State \t Verify \t Result \t DeviceID
        // Nota: Result=1 indica éxito, Result=0 suele ser fallo (y se filtra por defecto)
        $content = "1001\t2023-11-01 08:00:00\t0\t1\t1\t1\n"; // Entrada
        $content .= "1001\t2023-11-01 18:00:00\t1\t1\t1\t1\n"; // Salida
        file_put_contents($this->tempFile, $content);

        // 2. Configurar Parser
        $parser = new ZKTecoUniversalParser();
        // Simular mapeo de empleados (ZK ID '1001' -> Sistema ID 50)
        $parser->setMapeoEmpleados(['1001' => 50]);

        // 3. Ejecutar Parser (Lectura del archivo)
        $resultadoParser = $parser->procesarArchivo($this->tempFile);

        // Validaciones del Parser
        $this->assertTrue($resultadoParser['exito'], 'El parser debería procesar el archivo exitosamente');
        $this->assertCount(2, $resultadoParser['registros'], 'Debería encontrar 2 registros válidos');
        $this->assertEquals(50, $resultadoParser['registros'][0]['empleado_id'], 'El ID debe estar mapeado correctamente al ID del sistema');
        $this->assertEquals('estandar', $resultadoParser['formato_detectado']['formato'], 'Debería detectar formato estándar');

        // 4. Preparar Mock de Base de Datos para el Inserter
        // Esto evita escribir en la base de datos real durante el test
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $dbMock = $this->createMock(Database::class);

        $dbMock->method('getConnection')->willReturn($pdoMock);

        // Configurar expectativas del PDO Mock
        // El inserter hará llamadas a prepare() para verificar duplicados e insertar
        $pdoMock->expects($this->any())
            ->method('prepare')
            ->willReturn($stmtMock);

        // Simular comportamiento de las consultas:
        // - fetch() devuelve false (simula que NO existen duplicados)
        // - execute() devuelve true (simula que la inserción fue exitosa)
        $stmtMock->method('fetch')->willReturn(false);
        $stmtMock->method('execute')->willReturn(true);

        // 5. Ejecutar Inserter con los registros obtenidos del Parser
        // Inyectamos el $dbMock para interceptar las llamadas a la BD
        $inserter = new ZKTecoAsistenciaInserter([], $dbMock);
        $resultadoInsercion = $inserter->insertarRegistros($resultadoParser['registros']);

        // 6. Validaciones del Inserter
        $this->assertTrue($resultadoInsercion['exito'], 'La inserción debería reportarse como exitosa');
        $this->assertEquals(2, $resultadoInsercion['estadisticas']['registros_insertados'], 'Debería intentar insertar 2 registros');
        $this->assertEquals(0, $resultadoInsercion['estadisticas']['errores'], 'No debería haber errores de inserción');
        
        // Si llegamos aquí, el flujo Archivo -> Parser -> Mapeo -> Inserter -> SQL funciona correctamente.
    }
}