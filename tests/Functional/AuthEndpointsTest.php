<?php  
use PHPUnit\Framework\TestCase;

class AuthEndpointsTest extends TestCase
{
    private $baseUrl;
    private $cookieFile;

    protected function setUp(): void
    {
        $this->baseUrl = "http://localhost/sistema_biometrico";
        // To maintain cookie session for login/logout tests
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'cookie');
    }

    private function postRequest(string $endpoint, array $postData, bool $returnHeaders = false): array
    {
        $url = $this->baseUrl . $endpoint;

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => $returnHeaders,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($returnHeaders) {
            return ['body' => $response, 'info' => $info];
        }

        return ['body' => $response, 'info' => $info];
    }

    public function testRegisterLoginLogoutFlow()
    {
        $username = 'testuser' . rand(1000,9999);
        $password = 'TestPassword123!';

        // Test registration
        $registerData = [
            'username' => $username,
            'password' => $password,
            'rol' => 'usuario'
        ];
        $registerResp = $this->postRequest('/register', $registerData);
        $this->assertTrue(in_array($registerResp['info']['http_code'], [200,302]), 'Register should redirect or succeed');

        // Test login
        $loginData = [
            'username' => $username,
            'password' => $password
        ];

        $loginResp = $this->postRequest('/login', $loginData);
        $this->assertTrue(in_array($loginResp['info']['http_code'], [200,302]), 'Login should succeed and redirect');

        // Test logout
        $logoutResp = $this->postRequest('/logout', []);
        $this->assertTrue(in_array($logoutResp['info']['http_code'], [200,302]), 'Logout should succeed and redirect');
    }

    public function testLoginWithInvalidCredentials()
    {
        $loginData = [
            'username' => 'nonexistentuser',
            'password' => 'wrongpassword'
        ];

        $loginResp = $this->postRequest('/login', $loginData);
        // The login page should respond with 200 and show error message content in body
        $this->assertEquals(200, $loginResp['info']['http_code'], 'Login with invalid credentials should show the form again');
        $this->assertStringContainsString('Credenciales incorrectas', $loginResp['body']);
    }
}
?>
