<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/ZKTecoMappingManager.php';
require_once __DIR__ . '/../../models/Database.php';

class ZKTecoMappingManagerTest extends TestCase
{
    private $dbMock;
    private $pdoMock;
    private $stmtMock;
    private $manager;

    protected function setUp(): void
    {
        // Crear Mocks para simular la base de datos
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->dbMock = $this->createMock(Database::class);
        
        // Configurar el mock de Database para devolver nuestro PDO simulado
        $this->dbMock->method('getConnection')->willReturn($this->pdoMock);
        
        // Instanciar el manager inyectando el mock
        $this->manager = new ZKTecoMappingManager();
        // Set the database connection via reflection or a setter method if available
        $reflection = new ReflectionClass($this->manager);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($this->manager, $this->dbMock);
    }

    /**
     * Prueba la creación/actualización manual de un mapeo
     */
    public function testCreacionMapeoManual()
    {
        $zkId = '1001';
        $empleadoId = 50;
        
        // Datos simulados que devolvería la BD al buscar el empleado
        $empleadoData = [
            'id' => $empleadoId,
            'nombre' => 'Test',
            'apellido' => 'User',
            'rfc' => 'TEST010101',
            'area' => 'IT',
            'numero_empleado' => 'E050'
        ];

        // Esperamos 2 llamadas a prepare:
        // 1. SELECT para buscar el empleado
        // 2. INSERT/UPDATE para guardar el mapeo
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->stmtMock);

        // Configurar el comportamiento del statement
        // Cuando se llame a fetch(), devolvemos los datos del empleado simulado
        $this->stmtMock->method('fetch')
            ->willReturn($empleadoData);

        $this->stmtMock->method('execute')
            ->willReturn(true);

        // Ejecutar el método a probar
        // Usamos ob_start/clean para suprimir los 'echo' que tiene la clase
        ob_start();
        $result = $this->manager->actualizarMapeoManual($zkId, $empleadoId);
        ob_end_clean();

        // Verificaciones
        $this->assertTrue($result['success'], 'La actualización debería ser exitosa');
        $this->assertEquals($empleadoData, $result['empleado'], 'Debe devolver los datos del empleado mapeado');
    }

    /**
     * Prueba la recuperación de un ID de empleado basado en el ID ZKTeco
     */
    public function testRecuperacionMapeo()
    {
        $zkId = '1001';
        $expectedMapping = [
            'empleado_id' => 50,
            'numero_empleado' => 'E050',
            'nombre_empleado' => 'Test User',
            'area' => 'IT'
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT empleado_id'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn($expectedMapping);

        $result = $this->manager->getEmpleadoIdPorZKTeco($zkId);

        $this->assertEquals($expectedMapping, $result, 'Debe recuperar el mapeo correcto');
    }
}
