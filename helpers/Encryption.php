<?php
/**
 * Helper simple para cifrar/descifrar datos usando OpenSSL AES-256-CBC.
 * Almacena el IV como prefijo en base64: iv:ciphertext
 */
class Encryption {
    private const CIPHER = 'AES-256-CBC';

    public static function encrypt(string $plaintext): string
    {
        if (!defined('ENCRYPTION_KEY') || empty(ENCRYPTION_KEY)) {
            error_log('[Encryption] ENCRYPTION_KEY no está definida. Almacenando datos en claro.');
            return $plaintext;
        }

        $key = hash('sha256', ENCRYPTION_KEY, true);
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        $encoded = base64_encode($iv . $ciphertext_raw);
        return $encoded;
    }

    public static function decrypt(string $encoded): string
    {
        if (!defined('ENCRYPTION_KEY') || empty(ENCRYPTION_KEY)) {
            error_log('[Encryption] ENCRYPTION_KEY no está definida. Asumiendo datos en claro.');
            return $encoded;
        }

        $key = hash('sha256', ENCRYPTION_KEY, true);
        $cipherdata = base64_decode($encoded);
        if ($cipherdata === false) {
            return $encoded; // No es base64 válido, devolver tal cual
        }
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        if (strlen($cipherdata) < $ivlen) {
            return $encoded; // Datos demasiado cortos
        }
        $iv = substr($cipherdata, 0, $ivlen);
        $ciphertext_raw = substr($cipherdata, $ivlen);
        $original_plaintext = openssl_decrypt($ciphertext_raw, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        return $original_plaintext === false ? $encoded : $original_plaintext;
    }
}

?>
