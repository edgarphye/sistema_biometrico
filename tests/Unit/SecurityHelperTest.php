<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests Unitarios para SecurityHelper Class
 * Pruebas de seguridad, encriptación y validación
 */
class SecurityHelperTest extends TestCase
{
    private SecurityHelperTestable $securityHelper;
    private string $testEncryptionKey = 'test_encryption_key_32_characters!!';

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../helpers/SecurityHelperTestable.php';
        $this->securityHelper = new SecurityHelperTestable($this->testEncryptionKey);
    }

    /**
     * Test de creación de instancia de SecurityHelper
     */
    public function testSecurityHelperInstanceCreation(): void
    {
        $this->assertInstanceOf(SecurityHelper::class, $this->securityHelper);
    }

    /**
     * Test de encriptación y desencriptación de strings
     */
    public function testEncryptDecryptString(): void
    {
        $originalData = 'Este es un mensaje secreto de prueba';
        
        // Encriptar
        $encrypted = $this->securityHelper->encrypt($originalData);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($originalData, $encrypted);
        
        // Desencriptar
        $decrypted = $this->securityHelper->decrypt($encrypted);
        $this->assertEquals($originalData, $decrypted);
    }

    /**
     * Test de encriptación con array
     */
    public function testEncryptDecryptArray(): void
    {
        $originalData = [
            'user_id' => 123,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'admin',
            'permissions' => ['read', 'write', 'delete']
        ];
        
        // Encriptar
        $encrypted = $this->securityHelper->encrypt(json_encode($originalData));
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals(json_encode($originalData), $encrypted);
        
        // Desencriptar
        $decrypted = $this->securityHelper->decrypt($encrypted);
        $decryptedArray = json_decode($decrypted, true);
        
        $this->assertEquals($originalData, $decryptedArray);
    }

    /**
     * Test de encriptación con datos vacíos
     */
    public function testEncryptDecryptEmpty(): void
    {
        $originalData = '';
        
        $encrypted = $this->securityHelper->encrypt($originalData);
        $decrypted = $this->securityHelper->decrypt($encrypted);
        
        $this->assertEquals($originalData, $decrypted);
    }

    /**
     * Test de generación de hash de contraseña
     */
    public function testPasswordHashing(): void
    {
        $password = 'MiContraseñaSegura123!';
        
        $hash = $this->securityHelper->hashPassword($password);
        
        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    /**
     * Test de verificación de contraseña correcta
     */
    public function testPasswordVerificationCorrect(): void
    {
        $password = 'MiContraseñaSegura123!';
        $hash = $this->securityHelper->hashPassword($password);
        
        $this->assertTrue($this->securityHelper->verifyPassword($password, $hash));
    }

    /**
     * Test de verificación de contraseña incorrecta
     */
    public function testPasswordVerificationIncorrect(): void
    {
        $password = 'MiContraseñaSegura123!';
        $wrongPassword = 'ContraseñaIncorrecta456!';
        $hash = $this->securityHelper->hashPassword($password);
        
        $this->assertFalse($this->securityHelper->verifyPassword($wrongPassword, $hash));
    }

    /**
     * Test de generación de tokens CSRF
     */
    public function testCsrfTokenGeneration(): void
    {
        $token = $this->securityHelper->generateCsrfToken();
        
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // SHA256 = 64 caracteres hex
    }

    /**
     * Test de verificación de token CSRF válido
     */
    public function testCsrfTokenVerificationValid(): void
    {
        $token = $this->securityHelper->generateCsrfToken();
        
        $this->assertTrue($this->securityHelper->verifyCsrfToken($token));
    }

    /**
     * Test de verificación de token CSRF inválido
     */
    public function testCsrfTokenVerificationInvalid(): void
    {
        $invalidToken = 'invalid_token_12345';
        
        $this->assertFalse($this->securityHelper->verifyCsrfToken($invalidToken));
    }

    /**
     * Test de sanitización de input HTML
     */
    public function testSanitizeHtml(): void
    {
        $dirtyHtml = '<script>alert("XSS Attack");</script><p>Clean text</p>';
        
        $clean = $this->securityHelper->sanitizeHtml($dirtyHtml);
        
        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringNotContainsString('alert("XSS Attack")', $clean);
        $this->assertStringContainsString('Clean text', $clean);
    }

    /**
     * Test de sanitización de SQL injection
     */
    public function testSanitizeSql(): void
    {
        $maliciousInput = "'; DROP TABLE users; --";
        
        $clean = $this->securityHelper->sanitizeSql($maliciousInput);
        
        $this->assertStringNotContainsString("';", $clean);
        $this->assertStringNotContainsString('DROP TABLE', $clean);
        $this->assertStringNotContainsString('--', $clean);
    }

    /**
     * Test de validación de email
     */
    public function testValidateEmailValid(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
            'user123@test-domain.com'
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue($this->securityHelper->validateEmail($email), 
                "Email válido falló: {$email}");
        }
    }

    /**
     * Test de validación de email inválido
     */
    public function testValidateEmailInvalid(): void
    {
        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'test@',
            'test.example.com',
            'test@.com',
            '',
            'test space@example.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse($this->securityHelper->validateEmail($email), 
                "Email inválido pasó: {$email}");
        }
    }

    /**
     * Test de validación de contraseña fuerte
     */
    public function testValidateStrongPasswordValid(): void
    {
        $strongPasswords = [
            'MiContraseña123!',
            'SecureP@ssw0rd',
            'Str0ng#Password',
            'C0mpl3x!Pwd'
        ];
        
        foreach ($strongPasswords as $password) {
            $this->assertTrue($this->securityHelper->validateStrongPassword($password), 
                "Contraseña fuerte válida falló: {$password}");
        }
    }

    /**
     * Test de validación de contraseña débil
     */
    public function testValidateStrongPasswordInvalid(): void
    {
        $weakPasswords = [
            'password',
            '123456',
            'qwerty',
            'Password', // sin números ni símbolos
            '12345678', // solo números
            'password!', // sin números
            'Password123', // sin símbolos
            'Pass1!' // muy corta
        ];
        
        foreach ($weakPasswords as $password) {
            $this->assertFalse($this->securityHelper->validateStrongPassword($password), 
                "Contraseña débil pasó: {$password}");
        }
    }

    /**
     * Test de validación de RFC mexicano
     */
    public function testValidateRfcValid(): void
    {
        $validRfcs = [
            'ABCD123456XYZ',
            'EFGH789012MNO',
            'IJKL345678PQR'
        ];
        
        foreach ($validRfcs as $rfc) {
            $this->assertTrue($this->securityHelper->validateRfc($rfc), 
                "RFC válido falló: {$rfc}");
        }
    }

    /**
     * Test de validación de RFC inválido
     */
    public function testValidateRfcInvalid(): void
    {
        $invalidRfcs = [
            'ABC', // muy corto
            'ABCDEFGHIJKLMN', // muy largo
            '123456789012', // solo números
            'ABCDEFGHIJKL', // solo letras
            '',
            'ABCD 123456 XYZ', // espacios
            'ABCD-123456-XYZ' // guiones
        ];
        
        foreach ($invalidRfcs as $rfc) {
            $this->assertFalse($this->securityHelper->validateRfc($rfc), 
                "RFC inválido pasó: {$rfc}");
        }
    }

    /**
     * Test de validación de teléfono mexicano
     */
    public function testValidatePhoneValid(): void
    {
        $validPhones = [
            '5512345678',
            '5551234567',
            '3331234567',
            '6671234567'
        ];
        
        foreach ($validPhones as $phone) {
            $this->assertTrue($this->securityHelper->validatePhone($phone), 
                "Teléfono válido falló: {$phone}");
        }
    }

    /**
     * Test de validación de teléfono inválido
     */
    public function testValidatePhoneInvalid(): void
    {
        $invalidPhones = [
            '12345678', // muy corto
            '55123456789', // muy largo
            '55-1234-5678', // formato incorrecto
            '(55)12345678', // paréntesis
            '55 ABCD 5678', // letras
            '',
            '55 1234 5678' // espacios
        ];
        
        foreach ($invalidPhones as $phone) {
            $this->assertFalse($this->securityHelper->validatePhone($phone), 
                "Teléfono inválido pasó: {$phone}");
        }
    }

    /**
     * Test de generación de JWT token
     */
    public function testGenerateJwtToken(): void
    {
        $payload = [
            'user_id' => 123,
            'username' => 'testuser',
            'role' => 'admin',
            'exp' => time() + 3600
        ];
        
        $token = $this->securityHelper->generateJwtToken($payload);
        
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        
        // Verificar que tiene 3 partes (header.payload.signature)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test de verificación de JWT token válido
     */
    public function testVerifyJwtTokenValid(): void
    {
        $payload = [
            'user_id' => 123,
            'username' => 'testuser',
            'role' => 'admin',
            'exp' => time() + 3600
        ];
        
        $token = $this->securityHelper->generateJwtToken($payload);
        $verified = $this->securityHelper->verifyJwtToken($token);
        
        $this->assertTrue($verified);
    }

    /**
     * Test de verificación de JWT token expirado
     */
    public function testVerifyJwtTokenExpired(): void
    {
        $payload = [
            'user_id' => 123,
            'username' => 'testuser',
            'exp' => time() - 3600 // Expirado
        ];
        
        $token = $this->securityHelper->generateJwtToken($payload);
        $verified = $this->securityHelper->verifyJwtToken($token);
        
        $this->assertFalse($verified);
    }

    /**
     * Test de generación de código 2FA
     */
    public function testGenerate2FACode(): void
    {
        $code = $this->securityHelper->generate2FACode();
        
        $this->assertNotEmpty($code);
        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
    }

    /**
     * Test de verificación de código 2FA
     */
    public function testVerify2FACode(): void
    {
        $code = $this->securityHelper->generate2FACode();
        
        $this->assertTrue($this->securityHelper->verify2FACode($code, $code));
        $this->assertFalse($this->securityHelper->verify2FACode($code, '123456'));
    }

    /**
     * Test de rate limiting
     */
    public function testRateLimiting(): void
    {
        $identifier = 'test_user_' . uniqid();
        $maxAttempts = 3;
        $timeWindow = 60; // 1 minuto
        
        // Primeros intentos deberían pasar
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->assertTrue($this->securityHelper->checkRateLimit($identifier, $maxAttempts, $timeWindow));
        }
        
        // El siguiente intento debería fallar
        $this->assertFalse($this->securityHelper->checkRateLimit($identifier, $maxAttempts, $timeWindow));
    }

    /**
     * Test de sanitización de nombres de archivo
     */
    public function testSanitizeFilename(): void
    {
        $dangerousFilename = '../../../etc/passwd';
        
        $cleanFilename = $this->securityHelper->sanitizeFilename($dangerousFilename);
        
        $this->assertStringNotContainsString('../', $cleanFilename);
        $this->assertStringNotContainsString('..', $cleanFilename);
    }

    /**
     * Test de validación de URL segura
     */
    public function testValidateSecureUrl(): void
    {
        $secureUrls = [
            'https://example.com',
            'https://subdomain.example.com/path',
            'https://example.com/path?param=value'
        ];
        
        foreach ($secureUrls as $url) {
            $this->assertTrue($this->securityHelper->validateSecureUrl($url), 
                "URL segura falló: {$url}");
        }
        
        $insecureUrls = [
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'http://example.com', // HTTP inseguro
            'ftp://example.com'
        ];
        
        foreach ($insecureUrls as $url) {
            $this->assertFalse($this->securityHelper->validateSecureUrl($url), 
                "URL insegura pasó: {$url}");
        }
    }

    /**
     * Test de generation de nonce para CSP
     */
    public function testGenerateCspNonce(): void
    {
        $nonce = $this->securityHelper->generateCspNonce();
        
        $this->assertNotEmpty($nonce);
        $this->assertIsString($nonce);
        $this->assertEquals(32, strlen($nonce)); // 16 bytes = 32 caracteres hex
    }
}