<?php
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class AttendanceEndpointsTest extends TestCase
{
    private $baseUrl;
    private $cookieFile;
    private $csrfToken;

    protected function setUp(): void
    {
        $this->baseUrl = "http://localhost";
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'cookie');
        $this->login();
        $this->csrfToken = $this->fetchCsrfToken();
    }

    protected function tearDown(): void
    {
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    private function login(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/login',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['username' => 'admin', 'password' => 'admin123']),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function fetchCsrfToken(): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/dashboard',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        preg_match('/<input[^>]*id="csrf_token"[^>]*value="([^"]+)"/', $html, $m);
        return $m[1] ?? '';
    }

    private function postRequest(string $endpoint, array $postData): array
    {
        $postData['csrf_token'] = $this->csrfToken;
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertNotFalse($result, "Expected valid response from $endpoint");
        $this->assertTrue($httpCode >= 200 && $httpCode < 300, "Expected 2xx from $endpoint, got $httpCode");

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
    }

    public function testRegistrarSalidaEndpoint()
    {
        $postData = [
            'dispositivo_id' => 1
        ];

        $response = $this->postRequest('/asistencia/registrar-salida', $postData);
        $this->assertArrayHasKey('success', $response);
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
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/asistencia/exportar-excel?fecha_inicio=' . urlencode(date('Y-m-d', strtotime('-7 days'))) . '&fecha_fin=' . urlencode(date('Y-m-d')),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertNotFalse($result, "Expected a valid response for exportar Excel");
        $this->assertTrue($httpCode >= 200 && $httpCode < 300, "Expected 2xx, got $httpCode");
    }

    public function testExportarPDFEndpoint()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/asistencia/exportar-pdf?fecha_inicio=' . urlencode(date('Y-m-d', strtotime('-7 days'))) . '&fecha_fin=' . urlencode(date('Y-m-d')),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertNotFalse($result, "Expected a valid response for exportar PDF");
        $this->assertTrue($httpCode >= 200 && $httpCode < 300, "Expected 2xx, got $httpCode");
    }
}
