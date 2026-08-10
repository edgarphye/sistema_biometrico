<?php
/**
 * Helper para cifrar/descifrar datos usando OpenSSL AES-256-GCM (autenticado).
 * Almacena el IV + tag como prefijo en base64: iv + tag + ciphertext
 * GCM proporciona autenticación integrada (confidencialidad + integridad).
 *
 * NOTA: Migrado desde AES-256-CBC. El descifrado intenta primero GCM y,
 * si falla la autenticación, cae a CBC para mantener compatibilidad con
 * datos legacy cifrados antes de la migración.
 */
class Encryption {
    private const CIPHER = 'AES-256-GCM';
    private const CIPHER_LEGACY = 'AES-256-CBC';

    public static function encrypt(string $plaintext): string
    {
        if (!defined('ENCRYPTION_KEY') || empty(ENCRYPTION_KEY)) {
            error_log('[Encryption] ENCRYPTION_KEY no está definida. Almacenando datos en claro.');
            return $plaintext;
        }

        $key = hash('sha256', ENCRYPTION_KEY, true);
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $tag = '';
        $ciphertext_raw = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        $encoded = base64_encode($iv . $tag . $ciphertext_raw);
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
            return $encoded;
        }

        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $taglen = 16;
        if (strlen($cipherdata) < $ivlen + $taglen) {
            return self::decryptLegacyCbc($encoded, $key, $cipherdata);
        }
        $iv = substr($cipherdata, 0, $ivlen);
        $tag = substr($cipherdata, $ivlen, $taglen);
        $ciphertext_raw = substr($cipherdata, $ivlen + $taglen);
        $original_plaintext = openssl_decrypt($ciphertext_raw, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($original_plaintext !== false) {
            return $original_plaintext;
        }

        // Datos no autenticables con GCM: intentar descifrado legacy CBC
        return self::decryptLegacyCbc($encoded, $key, $cipherdata);
    }

    /**
     * Descifra datos almacenados con el esquema anterior AES-256-CBC (iv + ciphertext).
     */
    private static function decryptLegacyCbc(string $encoded, string $key, string $cipherdata): string
    {
        $ivlen = openssl_cipher_iv_length(self::CIPHER_LEGACY);
        if (strlen($cipherdata) < $ivlen) {
            return $encoded;
        }
        $iv = substr($cipherdata, 0, $ivlen);
        $ciphertext_raw = substr($cipherdata, $ivlen);
        $plaintext = openssl_decrypt($ciphertext_raw, self::CIPHER_LEGACY, $key, OPENSSL_RAW_DATA, $iv);
        return $plaintext === false ? $encoded : $plaintext;
    }
}

?>
