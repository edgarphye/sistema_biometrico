<?php
use PHPUnit\Framework\TestCase;

// Cargar los archivos necesarios. El bootstrap de PHPUnit ya carga config.php y Database.php.
require_once __DIR__ . '/../../services/AsistenciaService.php';
require_once __DIR__ . '/../../models/Biometrico.php';
require_once __DIR__ . '/../../models/Empleado.php';

class AsistenciaServiceTest extends TestCase
{
    /**
     * Prueba el flujo completo de registrar una entrada y calcular el retardo asociado.
     * Utiliza un mock para el modelo Biometrico para aislar la prueba del servicio.
     */
    public function testRegistrarEntradaYCalcularRetardo()
    {
        // 1. ARRANGE (Preparar el entorno de la prueba)
        
        // Datos simulados que el modelo Biometrico debería devolver
        $empleadoSimulado = ['id' => 1, 'nombre' => 'Juan', 'apellido' => 'Perez'];
        $datosBiometricosSimulados = ['data' => '...', 'type' => 'face', 'quality_score' => 95, 'metadata' => []];

        // Crear un "mock" del modelo Biometrico. Esto nos permite simular sus métodos
        // sin necesidad de interactuar con la base de datos o hardware real.
        $biometricoMock = $this->createMock(Biometrico::class);
        
        // Configurar el mock: cuando se llame al método 'verificarIdentidad',
        // le ordenamos que devuelva nuestro empleado simulado.
        $biometricoMock->method('verificarIdentidad')->willReturn($empleadoSimulado);
        
        // Configurar el mock para el método 'recibirDatosBiometricos'.
        $biometricoMock->method('recibirDatosBiometricos')->willReturn($datosBiometricosSimulados);

        // Instanciar el servicio que realmente queremos probar
        $asistenciaService = new AsistenciaService();

        // 2. ACT (Ejecutar el código a probar)
        
        // Simular la obtención de datos y la verificación de identidad usando el mock
        $datos = $biometricoMock->recibirDatosBiometricos(1);
        $empleado = $biometricoMock->verificarIdentidad($datos['data'], $datos['type']);

        // Llamar al método del servicio para registrar la entrada
        $resultadoEntrada = $asistenciaService->registrarEntrada(
            $empleado['id'], 
            1, // dispositivoId
            $datos['type'], 
            $datos['data'], 
            $datos['quality_score'],
            $datos['metadata'],
            0.9 // match_score
        );

        // Simular una llegada a las 09:10 para probar el cálculo de retardo
        $horaLlegadaSimulada = '09:10:00';
        // Se asume que el horario de entrada por defecto es 09:00:00 y la tolerancia es de 5 minutos.
        // La lógica real está en AsistenciaService, aquí solo probamos el resultado.
        $retardo = $asistenciaService->calcularRetardo($horaLlegadaSimulada, $empleado['id'], date('Y-m-d'));

        // 3. ASSERT (Verificar que los resultados son los esperados)
        
        // Verificar que el empleado fue "identificado" correctamente por el mock.
        $this->assertNotNull($empleado);
        $this->assertEquals('Juan', $empleado['nombre']);
        
        // Verificar que la entrada se registró con éxito.
        // Asumimos que el método devuelve `true` si la inserción en la BD es correcta.
        // En una prueba real, también podríamos mockear la BD para confirmar la inserción.
        $this->assertTrue($resultadoEntrada);

        // Verificar que el cálculo del retardo es correcto.
        $this->assertNotNull($retardo);
        $this->assertEquals(10, $retardo['minutos_retardo']);
        $this->assertEquals('Retardo Menor', $retardo['clasificacion']); // Suponiendo esta clasificación para 10 min.
    }
}
