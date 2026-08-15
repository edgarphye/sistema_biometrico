<?php

/**
 * Security Helper
 * Implementa sanitización, validación y funciones de seguridad
 */
class SecurityHelper {
    
    /**
     * Sanea la salida para prevenir XSS
     * @param string $string String a sanear
     * @param string $encoding Codificación de caracteres
     * @return string String saneado
     */
    public static function escape($string, $encoding = 'UTF-8') {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, $encoding);
    }
    
    /**
     * Sanea array de forma recursiva
     * @param array $data Array a sanear
     * @return array Array saneado
     */
    public static function escapeArray(array $data) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::escapeArray($value);
            } else {
                $data[$key] = self::escape($value);
            }
        }
        return $data;
    }
    
    /**
     * Valida y limpia un email
     * @param string $email Email a validar
     * @return string|false Email limpio o false si es inválido
     */
    public static function sanitizeEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Valida y limpia un string
     * @param string $string String a limpiar
     * @param string $type Tipo de validación
     * @return string String limpio
     */
    public static function sanitizeString($string, $type = 'general') {
        switch ($type) {
            case 'alpha':
                return preg_replace('/[^a-zA-Z]/', '', $string);
            case 'alphanum':
                return preg_replace('/[^a-zA-Z0-9]/', '', $string);
            case 'numeric':
                return preg_replace('/[^0-9]/', '', $string);
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9._-]/', '', $string);
            case 'general':
            default:
                return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Valida y limpia un número entero
     * @param mixed $value Valor a validar
     * @param int $min Valor mínimo
     * @param int $max Valor máximo
     * @return int|false Entero validado o false si es inválido
     */
    public static function sanitizeInt($value, $min = null, $max = null) {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($int === false) {
            return false;
        }
        
        if ($min !== null && $int < $min) {
            return false;
        }
        
        if ($max !== null && $int > $max) {
            return false;
        }
        
        return $int;
    }
    
    /**
     * Valida y limpia un float
     * @param mixed $value Valor a validar
     * @param float $min Valor mínimo
     * @param float $max Valor máximo
     * @return float|false Float validado o false si es inválido
     */
    public static function sanitizeFloat($value, $min = null, $max = null) {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        
        if ($float === false) {
            return false;
        }
        
        if ($min !== null && $float < $min) {
            return false;
        }
        
        if ($max !== null && $float > $max) {
            return false;
        }
        
        return $float;
    }
    
    /**
     * Valida y limpia un URL
     * @param string $url URL a validar
     * @return string|false URL limpio o false si es inválido
     */
    public static function sanitizeUrl($url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Filtra y limpia input para prevenir XSS
     * @param string $input Input a filtrar
     * @return string Input filtrado
     */
    public static function filterXSS($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Filtra y limpia input para prevenir SQL Injection
     * @deprecated Esta función es insegura. Usar siempre prepared statements con PDO.
     * @param string $input Input a filtrar
     * @return string Input filtrado (solo trim, sin addslashes)
     */
    public static function filterSQL($input) {
        // Solo limpiar espacios, nunca usar addslashes
        // Usar siempre prepared statements en su lugar
        error_log('ADVERTENCIA: SecurityHelper::filterSQL() está deprecated. Usar prepared statements.');
        return trim($input);
    }
    
    /**
     * Valida un RFC (Registro Federal de Contribuyentes)
     * @param string $rfc RFC a validar
     * @return bool Verdadero si es válido
     */
    public static function validateRfc($rfc) {
        // RFC: 4 letras + 6 dígitos + 3 caracteres homoclave
        return preg_match('/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/', $rfc) === 1;
    }
    
    /**
     * Valida un teléfono mexicano (10 dígitos)
     * @param string $phone Teléfono a validar
     * @return bool Verdadero si es válido
     */
    public static function validatePhone($phone) {
        // Teléfono mexicano: 10 dígitos empezando con 5, 3, 6, etc.
        return preg_match('/^[5-6][0-9]{9}$/', $phone) === 1;
    }
    
    /**
     * Valida que una contraseña sea fuerte
     * @param string $password Contraseña a validar
     * @return bool Verdadero si es fuerte
     */
    public static function validateStrongPassword($password) {
        // Mínimo 8 caracteres
        if (strlen($password) < 8) {
            return false;
        }
        
        // Debe contener al menos una letra mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Debe contener al menos una letra minúscula
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Debe contener al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // Debe contener al menos un carácter especial
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Genera un hash seguro para la contraseña
     * @param string $password Contraseña a hashear
     * @return string Hash de la contraseña
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verifica una contraseña contra su hash
     * @param string $password Contraseña a verificar
     * @param string $hash Hash almacenado
     * @return bool Verdadero si coincide
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Genera un token CSRF seguro
     * @return string Token CSRF de 32 caracteres
     */
    public static function generateCsrfToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Verifica un token CSRF
     * @param string $token Token a verificar
     * @param string $sessionToken Token almacenado en sesión
     * @return bool Verdadero si es válido
     */
    public static function verifyCsrfToken($token, $sessionToken) {
        return hash_equals($token, $sessionToken);
    }
    
    /**
     * Genera un token JWT
     * @param array $payload Payload del token
     * @param string $secret Secreto para firmar
     * @return string Token JWT
     */
    public static function generateJwtToken($payload, $secret) {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Verifica un token JWT
     * @param string $token Token a verificar
     * @param string $secret Secreto para verificar
     * @return bool Verdadero si es válido y no expirado
     */
    public static function verifyJwtToken($token, $secret) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $expectedSignature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
        
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
     * Obtiene el nonce CSP de la sesión para usarlo en atributos de etiquetas
     * @return string Nonce CSP
     */
    public static function cspNonce(): string
    {
        return $_SESSION['csp_nonce'] ?? '';
    }

    /**
     * Genera el atributo nonce para etiquetas script/style
     * @return string Atributo nonce (ej: ' nonce="abc123"')
     */
    public static function nonceAttr(): string
    {
        $nonce = self::cspNonce();
        return $nonce ? ' nonce="' . $nonce . '"' : '';
    }

    /**
     * Genera un código 2FA de 6 dígitos
     * @return string Código 2FA
     */
    public static function generate2FACode() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verifica un código 2FA
     * @param string $expected Código esperado
     * @param string $provided Código proporcionado
     * @return bool Verdadero si coinciden
     */
    public static function verify2FACode($expected, $provided) {
        return hash_equals($expected, $provided);
    }
    
    /**
     * Establece headers de seguridad HTTP
     */
    public static function setSecurityHeaders() {
        // Evitar errores si las cabeceras ya fueron enviadas (ej. en scripts CLI o pruebas)
        if (headers_sent()) {
            return;
        }
        
        // Prevenir clickjacking (permitir iframes del mismo origen, p.ej. vista previa de documentos)
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevenir MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Habilitar XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy (nonce para scripts, unsafe-inline para estilos/eventos legacy)
        $nonce = base64_encode(random_bytes(16));
        $_SESSION['csp_nonce'] = $nonce;
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'self';");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Encripta datos usando AES-256-GCM (autenticado)
     * @param string $data Datos a encriptar
     * @param string $key Clave de encriptación
     * @return string Datos encriptados en base64
     */
    public static function encrypt($data, $key) {
        if (empty($data)) {
            return '';
        }
        
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, 0, $iv, $tag);
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Desencripta datos
     * @param string $encryptedData Datos encriptados en base64
     * @param string $key Clave de desencriptación
     * @return string Datos desencriptados
     */
    public static function decrypt($encryptedData, $key) {
        if (empty($encryptedData)) {
            return '';
        }
        
        $data = base64_decode($encryptedData);
        if ($data === false) {
            return '';
        }
        
        $ivLength = openssl_cipher_iv_length('aes-256-gcm');
        $tagLength = 16;
        if (strlen($data) < $ivLength + $tagLength) {
            return '';
        }
        
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, $tagLength);
        $encrypted = substr($data, $ivLength + $tagLength);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, 0, $iv, $tag);
        
        return $decrypted !== false ? $decrypted : '';
    }
}