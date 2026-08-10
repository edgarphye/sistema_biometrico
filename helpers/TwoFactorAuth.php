<?php

require_once __DIR__ . '/../models/Database.php';

class TwoFactorAuth
{
    private $codeLength = 6;
    private $codeExpiration = 300;

    public function generateCode($length = 6)
    {
        return str_pad(random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

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

    public function generateTOTPSecret($userId, $username)
    {
        $secret = $this->generateSecret();
        $this->saveTOTPSecret($userId, $secret);

        return [
            'secret' => $secret,
            'qr_url' => $this->generateQRCode($username, $secret),
            'manual_key' => $secret
        ];
    }

    public function verifyTOTP($userId, $code)
    {
        $storedSecret = $this->getTOTPSecret($userId);
        if (!$storedSecret) {
            return false;
        }
        return $this->verifyTOTPCode($storedSecret, $code);
    }

    private function generateSecret($length = 32)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

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
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($otpauth);
    }

    private function verifyTOTPCode($secret, $code)
    {
        $currentTime = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            $expected = $this->generateTOTPForTime($secret, $currentTime + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }
        return false;
    }

    private function generateTOTPForTime($secret, $time)
    {
        $timeHex = str_pad(dechex($time), 16, '0', STR_PAD_LEFT);
        $decodedSecret = $this->base32Decode($secret);
        if ($decodedSecret === false) {
            return str_pad('0', 6, '0', STR_PAD_LEFT);
        }
        $hash = hash_hmac('sha1', hex2bin($timeHex), $decodedSecret, true);
        $offset = ord($hash[19]) & 0xf;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
                | ((ord($hash[$offset + 1]) & 0xff) << 16)
                | ((ord($hash[$offset + 2]) & 0xff) << 8)
                | (ord($hash[$offset + 3]) & 0xff);
        $otp = $binary % 1000000;
        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode($input)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper($input);
        $input = str_replace('=', '', $input);
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos($alphabet, $input[$i]);
            if ($val === false) return false;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xff);
                $bitsLeft -= 8;
            }
        }
        return $output;
    }

    private function getConnection()
    {
        return Database::getInstance()->getConnection();
    }

    private function saveTOTPSecret($userId, $secret)
    {
        try {
            $pdo = $this->getConnection();
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

    private function getTOTPSecret($userId)
    {
        try {
            $pdo = $this->getConnection();
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

    public function disable2FA($userId)
    {
        try {
            $pdo = $this->getConnection();
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

    public function is2FAEnabled($userId)
    {
        try {
            $pdo = $this->getConnection();
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
