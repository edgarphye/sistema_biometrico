<?php

/**
 * Security Helper - Versión para Testing
 * Implementa métodos de seguridad con estado para pruebas unitarias
 */
class SecurityHelperTestable {
    
    private $encryptionKey;
    private $csrfTokens = [];
    private $rateLimits = [];
    
    public function __construct($encryptionKey = null) {
        $this->encryptionKey = $encryptionKey ?? 'default_key_32_characters_long!!';
        $this->csrfTokens = [];
        $this->rateLimits = [];
    }
    
    /**
     * Encripta datos usando AES-256-CBC
     */
    public function encrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Desencripta datos
     */
    public function decrypt($encryptedData) {
        if (empty($encryptedData)) {
            return '';
        }
        
        $data = base64_decode($encryptedData);
        if ($data === false) {
            return '';
        }
        
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        if (strlen($data) < $ivLength) {
            return '';
        }
        
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
        
        return $decrypted !== false ? $decrypted : '';
    }
    
    /**
     * Genera hash de contraseña seguro
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verifica contraseña contra hash
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Genera token CSRF
     */
    public function generateCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $this->csrfTokens[] = $token;
        
        if (count($this->csrfTokens) > 10) {
            $this->csrfTokens = array_slice($this->csrfTokens, -10);
        }
        
        return $token;
    }
    
    /**
     * Verifica token CSRF
     */
    public function verifyCsrfToken($token) {
        return in_array($token, $this->csrfTokens);
    }
    
    /**
     * Sanea HTML para prevenir XSS
     */
    public function sanitizeHtml($html) {
        return htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanea input para prevenir SQL Injection
     */
    public function sanitizeSql($input) {
        return addslashes(trim($input));
    }
    
    /**
     * Valida formato de email
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida que la contraseña sea fuerte
     */
    public function validateStrongPassword($password) {
        if (strlen($password) < 8) {
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida formato de RFC mexicano
     */
    public function validateRfc($rfc) {
        return preg_match('/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/', $rfc) === 1;
    }
    
    /**
     * Valida número de teléfono mexicano
     */
    public function validatePhone($phone) {
        return preg_match('/^[5-6][0-9]{9}$/', $phone) === 1;
    }
    
    /**
     * Genera token JWT simple
     */
    public function generateJwtToken($payload) {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "$header.$payload", $this->encryptionKey, true);
        $signature = base64_encode($signature);
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Verifica token JWT
     */
    public function verifyJwtToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $expectedSignature = hash_hmac('sha256', "$header.$payload", $this->encryptionKey, true);
        $expectedSignature = base64_encode($expectedSignature);
        
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }
        
        $payloadData = json_decode(base64_decode($payload), true);
        if (!$payloadData || !isset($payloadData['exp'])) {
            return false;
        }
        
        return $payloadData['exp'] > time();
    }
    
    /**
     * Genera código 2FA de 6 dígitos
     */
    public function generate2FACode() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verifica código 2FA
     */
    public function verify2FACode($expected, $provided) {
        return hash_equals($expected, $provided);
    }
    
    /**
     * Implementa rate limiting simple
     */
    public function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $now = time();
        
        if (!isset($this->rateLimits[$identifier])) {
            $this->rateLimits[$identifier] = [];
        }
        
        $this->rateLimits[$identifier] = array_filter(
            $this->rateLimits[$identifier],
            function($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            }
        );
        
        if (count($this->rateLimits[$identifier]) >= $maxAttempts) {
            return false;
        }
        
        $this->rateLimits[$identifier][] = $now;
        return true;
    }
    
    /**
     * Sanea nombre de archivo
     */
    public function sanitizeFilename($filename) {
        $filename = preg_replace('/[^\w\-_\.]/', '', $filename);
        return basename($filename);
    }
    
    /**
     * Valida URL segura
     */
    public function validateSecureUrl($url) {
        if (!str_starts_with($url, 'https://')) {
            return false;
        }
        
        $dangerousSchemes = ['javascript:', 'data:', 'vbscript:', 'file:', 'ftp:'];
        foreach ($dangerousSchemes as $scheme) {
            if (str_contains(strtolower($url), $scheme)) {
                return false;
            }
        }
        
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Genera nonce para Content Security Policy
     */
    public function generateCspNonce() {
        return bin2hex(random_bytes(16));
    }
}