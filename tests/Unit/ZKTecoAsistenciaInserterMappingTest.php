<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/ZKTecoAsistenciaInserter.php';
require_once __DIR__ . '/../../models/Database.php';

class ZKTecoAsistenciaInserterMappingTest extends TestCase
{
    /**
     * Verifica que al crear un empleado nuevo automáticamente,
     * se inserte también el registro en la tabla de mapeo correcta (zkteo_empleado_mapeo).
     */
    public function testCrearEmpleadoSiNoExisteInsertaMapeoCorrectamente()
    {
        // 1. Preparar Mocks de Base de Datos
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        
        // Configurar comportamiento del statement:
        // - fetch() retorna false para simular que NO existe mapeo previo
        // - execute() retorna true para simular éxito en las inserciones
        $pdoStatementMock->method('fetch')->willReturn(false);
        $pdoStatementMock->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        
        // Configurar lastInsertId para devolver un ID simulado del nuevo empleado
        $nuevoEmpleadoId = '12345';
        $pdoMock->method('lastInsertId')->willReturn($nuevoEmpleadoId);

        // Capturamos las consultas SQL para verificarlas
        $consultasEjecutadas = [];
        $pdoMock->method('prepare')
            ->will($this->returnCallback(function ($sql) use (&$consultasEjecutadas, $pdoStatementMock) {
                $consultasEjecutadas[] = $sql;
                return $pdoStatementMock;
            }));

        $databaseMock = $this->createMock(Database::class);
        $databaseMock->method('getConnection')->willReturn($pdoMock);

        // 2. Instanciar la clase con el Mock de BD
        $inserter = new ZKTecoAsistenciaInserter([], $databaseMock);

        // 3. Usar Reflection para acceder al método privado
        $reflection = new ReflectionClass(ZKTecoAsistenciaInserter::class);
        $method = $reflection->getMethod('crearEmpleadoSiNoExiste');
        $method->setAccessible(true);

        // 4. Ejecutar el método
        $zkEmpleadoId = 999; // ID del dispositivo
        $empleadoId = 999;   // ID temporal
        
        $resultado = $method->invoke($inserter, $zkEmpleadoId, $empleadoId);

        // 5. Aserciones
        
        // Verificar que retornó el ID simulado
        $this->assertEquals($nuevoEmpleadoId, $resultado, "El método debe retornar el ID del nuevo empleado creado");

        // Verificar que se intentó insertar en la tabla correcta
        $encontroInsertMapeo = false;
        foreach ($consultasEjecutadas as $sql) {
            // Normalizar espacios para la comparación
            $sqlLimpio = preg_replace('/\s+/', ' ', $sql);
            if (strpos($sqlLimpio, 'INSERT IGNORE INTO zkteo_empleado_mapeo') !== false) {
                $encontroInsertMapeo = true;
                break;
            }
        }
        
        $this->assertTrue($encontroInsertMapeo, "Se debió ejecutar un INSERT en la tabla 'zkteo_empleado_mapeo'");
    }
}
