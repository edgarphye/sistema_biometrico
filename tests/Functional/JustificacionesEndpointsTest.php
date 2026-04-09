<?php  
use PHPUnit\Framework\TestCase;

class JustificacionesEndpointsTest extends TestCase
{
    private $baseUrl;
    private $cookieFile;

    protected function setUp(): void
    {
        $this->baseUrl = "http://localhost/sistema_biometrico";
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
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }

        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return ['body' => $result, 'info' => $info];
    }

    public function testJustificacionesIndexPage()
    {
        $response = $this->sendRequest('/justificaciones', [], 'GET');
        $this->assertEquals(200, $response['info']['http_code'], "Justificaciones index page should return 200");
        $this->assertStringContainsString('Justificaciones', $response['body']);
    }

    public function testJustificarPage()
    {
        $response = $this->sendRequest('/justificaciones/justificar', [], 'GET');
        $this->assertEquals(200, $response['info']['http_code'], "Justificar page should return 200");
        $this->assertStringContainsString('Justificar', $response['body']);
    }
}
?>
