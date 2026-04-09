<?php

/**
 * CSRF Protection Helper
 * Implementa tokens CSRF para prevención de ataques
 */
class Csrf {
    private static $tokenLength = 32;
    private static $sessionKey = 'csrf_token';
    
    /**
     * Genera un nuevo token CSRF y lo almacena en sesión
     * @return string Token CSRF generado
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(self::$tokenLength));
        $_SESSION[self::$sessionKey] = $token;
        
        return $token;
    }
    
    /**
     * Obtiene el token CSRF actual de la sesión
     * @return string|null Token CSRF o null si no existe
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION[self::$sessionKey] ?? null;
    }
    
    /**
     * Valida si el token CSRF enviado es válido
     * @param string $token Token CSRF a validar
     * @return bool True si el token es válido, false en caso contrario
     */
    public static function validate($token) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($token)) return false;
        return hash_equals($_SESSION[self::$sessionKey] ?? '', $token);
    }
    
    /**
     * Obtiene el token CSRF o genera uno nuevo si no existe
     * @return string Token CSRF
     */
    public static function token() {
        $token = self::getToken();
        
        if (!$token) {
            $token = self::generateToken();
        }
        
        return $token;
    }
    
    /**
     * Genera el HTML input hidden para el token CSRF
     * @return string HTML input hidden con el token
     */
    public static function getHiddenInput() {
        $token = self::token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Verifica el token CSRF del request actual
     * @throws Exception Si el token no es válido
     */
    public static function checkToken() {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (!self::validate($token)) {
            throw new Exception('Token CSRF inválido. Por favor, recargue la página y reintente.');
        }
    }
    
    /**
     * Limpia el token CSRF de la sesión
     */
    public static function clearToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::$sessionKey]);
    }
}
