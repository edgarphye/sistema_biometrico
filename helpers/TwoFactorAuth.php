<?php

/**
 * Enhanced 2FA Helper Class
 * Soporte para múltiples métodos de autenticación de dos factores
 */
class TwoFactorAuth
{
    private $methods = ['totp', 'email', 'sms'];
    private $codeLength = 6;
    private $codeExpiration = 300; // 5 minutos
    
    /**
     * Generar código 2FA
     */
    public function generateCode($length = 6)
    {
        return str_pad(random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }
    
    /**
     * Enviar código 2FA por email
     */
    public function sendEmailCode($email, $code, $userId)
    {
        $subject = 'Código de Verificación - Sistema Biométrico';
        $message = "
            <h2>Código de Verificación</h2>
            <p>Su código de acceso de un solo uso es:</p>
            <h1 style='font-size: 32px; letter-spacing: 8px; color: #007bff; background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;'>
                {$code}
            </h1>
            <p>Este código expirará en 5 minutos.</p>
            <p>Si no solicitó este código, ignore este mensaje.</p>
            <hr>
            <small><em>Por seguridad, nunca comparta este código con nadie.</em></small>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: noreply@sistema-biometrico.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($email, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Generar QR Code para TOTP (Google Authenticator)
     */
    public function generateTOTPSecret($userId, $username)
    {
        $secret = $this->generateSecret();
        
        // Guardar secreto en base de datos
        $this->saveTOTPSecret($userId, $secret);
        
        return [
            'secret' => $secret,
            'qr_url' => $this->generateQRCode($username, $secret),
            'manual_key' => $secret
        ];
    }
    
    /**
     * Verificar código TOTP
     */
    public function verifyTOTP($userId, $code)
    {
        $storedSecret = $this->getTOTPSecret($userId);
        if (!$storedSecret) {
            return false;
        }
        
        // Para demo, usar verificación simplificada
        // En producción, usar librería TOTP real
        return $this->verifyTOTPCode($storedSecret, $code);
    }
    
    /**
     * Generar secreto aleatorio para TOTP
     */
    private function generateSecret($length = 32)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generar URL para QR Code de Google Authenticator
     */
    public function generateQRCode($username, $secret)
    {
        $issuer = 'Sistema Biometrico';
        $label = $issuer . ':' . $username;
        
        $params = [
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30
        ];
        
        $otpauth = 'otpauth://totp/' . urlencode($label) . '?' . http_build_query($params);
        
        // Usar servicio de QR code online para demo
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($otpauth);
    }
    
    /**
     * Verificar código TOTP (simplificado para demo)
     */
    private function verifyTOTPCode($secret, $code)
    {
        // En producción, usar librería real como OTPHP
        // Para demo, simulación básica
        $currentTime = floor(time() / 30);
        $expectedCode = $this->generateTOTPForTime($secret, $currentTime);
        
        // Permitir código del periodo anterior y siguiente
        for ($i = -1; $i <= 1; $i++) {
            $testCode = $this->generateTOTPForTime($secret, $currentTime + $i);
            if ($testCode === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generar código TOTP para timestamp específico
     */
    private function generateTOTPForTime($secret, $time)
    {
        // Simulación muy básica - EN PRODUCCIÓN USAR LIBRERÍA REAL
        return substr(hash_hmac('sha1', $secret, $time), -6);
    }
    
    /**
     * Guardar secreto TOTP en base de datos
     */
    private function saveTOTPSecret($userId, $secret)
    {
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET totp_secret = ?, 
                    totp_enabled = 1,
                    totp_setup_date = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$secret, $userId]);
        } catch (Exception $e) {
            error_log("Error saving TOTP secret: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener secreto TOTP de base de datos
     */
    private function getTOTPSecret($userId)
    {
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT totp_secret, totp_enabled 
                FROM usuarios 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result && $result['totp_enabled'] ? $result['totp_secret'] : null;
        } catch (Exception $e) {
            error_log("Error getting TOTP secret: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Deshabilitar 2FA para usuario
     */
    public function disable2FA($userId)
    {
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET totp_enabled = 0,
                    totp_secret = NULL
                WHERE id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error disabling 2FA: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si usuario tiene 2FA habilitado
     */
    public function is2FAEnabled($userId)
    {
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT totp_enabled 
                FROM usuarios 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result && $result['totp_enabled'];
        } catch (Exception $e) {
            error_log("Error checking 2FA status: " . $e->getMessage());
            return false;
        }
    }
}