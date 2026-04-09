<?php  
use PHPUnit\Framework\TestCase;

class ReportesEndpointsTest extends TestCase
{
    private $baseUrl;
    private $cookieFile;

    protected function setUp(): void
    {
        // Ajustar baseUrl de pruebas para que sea compatible con BASE_URL de config.php
        $this->baseUrl = 'http://localhost' . '/sistema_biometrico';  // Debe coincidir con BASE_URL
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'cookie');
    }

    private function sendRequest(string $endpoint, array $postData = [], string $method = 'GET'): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_NOBODY => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 10
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }

        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);

        if ($result === false) {
            $info = curl_getinfo($ch);
            if (function_exists('curl_close')) {
                curl_close($ch);
            }
            return ['body' => '', 'info' => $info];
        }

        $info = curl_getinfo($ch);
        if (function_exists('curl_close')) {
            curl_close($ch);
        }

        return ['body' => $result, 'info' => $info];
    }

    public function testReportesIndexPage()
    {
        $response = $this->sendRequest('/reportes', [], 'GET');
        $this->assertEquals(200, $response['info']['http_code'], "Reportes index page should return 200");
        $this->assertStringContainsString('Reportes del Sistema Biométrico', $response['body']);
    }
}
?>
