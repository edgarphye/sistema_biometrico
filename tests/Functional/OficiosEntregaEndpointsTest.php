<?php
use PHPUnit\Framework\TestCase;

/**
 * Prueba funcional de los endpoints de marca de entrega de oficios
 * de notas malas (/notas-malas/oficio/{id}/marcar-entregado y desmarcar-entregado).
 *
 * @group functional
 */
class OficiosEntregaEndpointsTest extends TestCase
{
    private $baseUrl;
    private $cookieFile;
    private $csrf;

    protected function setUp(): void
    {
        $this->baseUrl = "http://localhost:8080";
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'cookie');
        $this->csrf = $this->login();
    }

    protected function tearDown(): void
    {
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    private function login(): ?string
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
        $body = curl_exec($ch);
        curl_close($ch);

        // Extraer el token CSRF del layout
        if (preg_match('/id="csrf_token" value="([^"]+)"/', $body, $m)) {
            return $m[1];
        }
        return null;
    }

    private function sendRequest(string $endpoint, array $postData = [], string $method = 'GET'): array
    {
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $this->baseUrl . $endpoint,
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

    private function obtenerOficioId(): ?int
    {
        $pdo = new PDO('mysql:host=localhost;dbname=sistema_biometrico;charset=utf8', 'root', 'root');
        $id = $pdo->query(
            "SELECT id FROM documentos_generados WHERE tipo_documento = 'oficio_notas_malas' ORDER BY id DESC LIMIT 1"
        )->fetchColumn();
        return $id ? (int)$id : null;
    }

    private function resetEntrega(int $id, int $estado): void
    {
        $pdo = new PDO('mysql:host=localhost;dbname=sistema_biometrico;charset=utf8', 'root', 'root');
        $stmt = $pdo->prepare("UPDATE documentos_generados SET entregado = ?, fecha_entrega = IF(?, NOW(), NULL), entregado_por = IF(?, 1, NULL) WHERE id = ?");
        $stmt->execute([$estado, $estado, $estado, $id]);
    }

    public function testMarcarEntregado()
    {
        $id = $this->obtenerOficioId();
        if (!$id) {
            $this->markTestSkipped('No hay oficios de notas malas generados');
        }
        $this->resetEntrega($id, 0);

        $response = $this->sendRequest(
            "/notas-malas/oficio/$id/marcar-entregado",
            ['csrf_token' => $this->csrf],
            'POST'
        );
        $this->assertEquals(200, $response['info']['http_code']);
        $json = json_decode($response['body'], true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertEquals(1, $json['entregado']);
        $this->assertNotNull($json['fecha_entrega']);

        // Verificar persistencia
        $pdo = new PDO('mysql:host=localhost;dbname=sistema_biometrico;charset=utf8', 'root', 'root');
        $row = $pdo->query("SELECT entregado, fecha_entrega FROM documentos_generados WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, (int)$row['entregado']);
        $this->assertNotNull($row['fecha_entrega']);
    }

    public function testDesmarcarEntregado()
    {
        $id = $this->obtenerOficioId();
        if (!$id) {
            $this->markTestSkipped('No hay oficios de notas malas generados');
        }
        $this->resetEntrega($id, 1);

        $response = $this->sendRequest(
            "/notas-malas/oficio/$id/desmarcar-entregado",
            ['csrf_token' => $this->csrf],
            'POST'
        );
        $this->assertEquals(200, $response['info']['http_code']);
        $json = json_decode($response['body'], true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertEquals(0, $json['entregado']);

        $pdo = new PDO('mysql:host=localhost;dbname=sistema_biometrico;charset=utf8', 'root', 'root');
        $row = $pdo->query("SELECT entregado, fecha_entrega FROM documentos_generados WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(0, (int)$row['entregado']);
        $this->assertNull($row['fecha_entrega']);
    }

    public function testMarcarEntregadoInexistenteRetorna404()
    {
        $response = $this->sendRequest(
            "/notas-malas/oficio/999999/marcar-entregado",
            ['csrf_token' => $this->csrf],
            'POST'
        );
        $this->assertEquals(404, $response['info']['http_code']);
    }

    public function testMarcarEntregadoConCsrfInvalidoRetorna403()
    {
        $id = $this->obtenerOficioId();
        if (!$id) {
            $this->markTestSkipped('No hay oficios de notas malas generados');
        }

        $response = $this->sendRequest(
            "/notas-malas/oficio/$id/marcar-entregado",
            ['csrf_token' => 'token-invalido'],
            'POST'
        );
        $this->assertEquals(403, $response['info']['http_code']);
    }

    public function testIndexMuestraModulo()
    {
        $response = $this->sendRequest('/notas-malas', [], 'GET');
        $this->assertEquals(200, $response['info']['http_code']);
        $this->assertStringContainsString('Notas Malas e Incidencias', $response['body']);
    }

    public function testIndexMuestraAccionesEntregaSegunEstado()
    {
        $pdo = new PDO('mysql:host=localhost;dbname=sistema_biometrico;charset=utf8', 'root', 'root');
        $row = $pdo->query(
            "SELECT id, empleado_id, periodo FROM documentos_generados
             WHERE tipo_documento = 'oficio_notas_malas'
             ORDER BY id DESC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $this->markTestSkipped('No hay oficios de notas malas generados');
        }

        $id = (int)$row['id'];
        $empleadoId = (int)$row['empleado_id'];
        $mes = substr($row['periodo'], 0, 7);
        $url = "/notas-malas?mes=$mes&empleado_id=$empleadoId";

        $this->resetEntrega($id, 0);
        $response = $this->sendRequest($url, [], 'GET');
        $this->assertEquals(200, $response['info']['http_code']);
        $this->assertStringContainsString('Marcar entregado', $response['body']);
        $this->assertStringNotContainsString('Deshacer entrega', $response['body']);

        $this->resetEntrega($id, 1);
        $response = $this->sendRequest($url, [], 'GET');
        $this->assertEquals(200, $response['info']['http_code']);
        $this->assertStringContainsString('Entregado (', $response['body']);
        $this->assertStringContainsString('Deshacer entrega', $response['body']);
        $this->assertStringNotContainsString('Marcar entregado', $response['body']);

        $this->resetEntrega($id, 0);
    }
}
