<?php
use PHPUnit\Framework\TestCase;

class AttendanceEndpointsTest extends TestCase
{
    private $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = "http://localhost/sistema_biometrico";
    }

    private function postRequest(string $endpoint, array $postData): array
    {
        $url = $this->baseUrl . $endpoint;

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postData),
                'ignore_errors' => true,
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $this->assertNotFalse($result, "Expected valid response from $endpoint");

        $response = json_decode($result, true);
        $this->assertIsArray($response, "Expected JSON array response from $endpoint");

        return $response;
    }

    public function testRegistrarEntradaEndpoint()
    {
        $postData = [
            'dispositivo_id' => 1
        ];

        $response = $this->postRequest('/asistencia/registrar-entrada', $postData);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success'], 'Registrar Entrada should succeed');
    }

    public function testRegistrarSalidaEndpoint()
    {
        $postData = [
            'dispositivo_id' => 1
        ];

        $response = $this->postRequest('/asistencia/registrar-salida', $postData);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success'], 'Registrar Salida should succeed');
    }

    public function testFiltrarAsistenciaEndpoint()
    {
        $postData = [
            'fecha_inicio' => date('Y-m-d', strtotime('-7 days')),
            'fecha_fin' => date('Y-m-d'),
            'limit' => 10
        ];

        $response = $this->postRequest('/asistencia/filtrar-asistencia', $postData);
        $this->assertIsArray($response, 'Filtrar Asistencia should return an array');
    }

    public function testExportarExcelEndpoint()
    {
        $postData = [
            'fecha_inicio' => date('Y-m-d', strtotime('-7 days')),
            'fecha_fin' => date('Y-m-d')
        ];

        $url = $this->baseUrl . '/asistencia/exportar-excel';

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postData),
                'ignore_errors' => true,
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        $this->assertNotFalse($result, "Expected a valid response for exportar Excel");
        $this->assertStringContainsString('PK', $result, "Expected Excel file content starting with PK signature");
    }

    public function testExportarPDFEndpoint()
    {
        $postData = [
            'fecha_inicio' => date('Y-m-d', strtotime('-7 days')),
            'fecha_fin' => date('Y-m-d')
        ];

        $url = $this->baseUrl . '/asistencia/exportar-pdf';

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postData),
                'ignore_errors' => true,
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        $this->assertNotFalse($result, "Expected a valid response for exportar PDF");
        $this->assertStringContainsString('%PDF', $result, "Expected PDF file content starting with %PDF signature");
    }
}
?>
