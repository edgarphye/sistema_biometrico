<?php

/**
 * Session Security Helper
 * Implementa seguridad avanzada de sesiones
 */
class SessionSecurity {
    
    /**
     * Inicia sesión con configuración segura
     */
    public static function secureSessionStart() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuración de cookies de sesión seguras
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.gc_maxlifetime', 3600); // 1 hora
            ini_set('session.cookie_lifetime', 3600);
            
            session_start();
        }
        
        // Regenerar ID si es nuevo o hace mucho tiempo
        if (!isset($_SESSION['session_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['session_initiated'] = true;
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Valida la sesión actual
     * @return bool True si la sesión es válida
     */
    public static function validateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }
        
        // Verificar que la sesión fue iniciada correctamente
        if (!isset($_SESSION['session_initiated'])) {
            return false;
        }
        
        // Verificar IP del usuario (opcional, puede causar problemas con DHCP)
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            self::destroySession();
            return false;
        }
        
        // Verificar User-Agent
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            self::destroySession();
            return false;
        }
        
        return true;
    }
    
    /**
     * Establece variables de sesión seguras
     * @param array $data Datos a establecer en sesión
     */
    public static function setSessionData(array $data) {
        self::secureSessionStart();
        
        // Guardar IP y User-Agent para validación
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['last_activity'] = time();
        
        // Establecer datos de usuario
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }
    
    /**
     * Actualiza la última actividad
     */
    public static function updateLastActivity() {
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION['last_activity'] = time();
        }
    }
    
    /**
     * Verifica timeout de inactividad
     * @param int $minutes Minutos de inactividad permitidos
     * @return bool True si está activo, false si ha expirado
     */
    public static function checkTimeout($minutes = 30) {
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }
        
        if (!isset($_SESSION['last_activity'])) {
            return false;
        }
        
        $timeout = $minutes * 60;
        return (time() - $_SESSION['last_activity']) < $timeout;
    }
    
    /**
     * Destruye la sesión de forma segura
     */
    public static function destroySession() {
        if (session_status() !== PHP_SESSION_NONE) {
            // Limpiar variables de sesión
            $_SESSION = [];
            
            // Destruir cookie de sesión
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            
            // Destruir sesión
            session_destroy();
        }
    }
    
    /**
     * Implementa rate limiting para intentos de login
     * @param string $identifier Identificador (username o IP)
     * @param int $maxAttempts Máximo de intentos
     * @param int $windowMinutes Ventana de tiempo en minutos
     * @return bool True si permite el intento, false si está bloqueado
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $windowMinutes = 15) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time(),
                'blocked_until' => null
            ];
        }
        
        $rateLimit = &$_SESSION[$key];
        
        // Si está bloqueado, verificar si ha pasado el tiempo
        if ($rateLimit['blocked_until'] && time() < $rateLimit['blocked_until']) {
            return false;
        }
        
        // Reset si ha pasado la ventana de tiempo
        if (time() - $rateLimit['first_attempt'] > ($windowMinutes * 60)) {
            $rateLimit['attempts'] = 0;
            $rateLimit['first_attempt'] = time();
            $rateLimit['blocked_until'] = null;
        }
        
        // Incrementar intentos
        $rateLimit['attempts']++;
        
        // Bloquear si excede el máximo
        if ($rateLimit['attempts'] > $maxAttempts) {
            $rateLimit['blocked_until'] = time() + ($windowMinutes * 60);
            return false;
        }
        
        return true;
    }
    
    /**
     * Verifica si un identificador está bloqueado
     * @param string $identifier Identificador (username_IP)
     * @return bool True si está bloqueado, false si no
     */
    public static function isBlocked($identifier) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            return false;
        }
        
        $rateLimit = $_SESSION[$key];
        return $rateLimit['blocked_until'] && time() < $rateLimit['blocked_until'];
    }
    
    /**
     * Registra un intento fallido de login
     * @param string $identifier Identificador (username_IP)
     * @param int $maxAttempts Máximo de intentos antes de bloquear
     * @param int $windowMinutes Ventana de tiempo en minutos
     */
    public static function recordFailedAttempt($identifier, $maxAttempts = 5, $windowMinutes = 15) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time(),
                'blocked_until' => null
            ];
        }
        
        $rateLimit = &$_SESSION[$key];
        
        // Reset si ha pasado la ventana de tiempo
        if (time() - $rateLimit['first_attempt'] > ($windowMinutes * 60)) {
            $rateLimit['attempts'] = 0;
            $rateLimit['first_attempt'] = time();
            $rateLimit['blocked_until'] = null;
        }
        
        // Incrementar intentos
        $rateLimit['attempts']++;
        
        // Bloquear si excede el máximo
        if ($rateLimit['attempts'] >= $maxAttempts) {
            $rateLimit['blocked_until'] = time() + ($windowMinutes * 60);
        }
    }
    
    /**
     * Obtiene información del rate limiting
     * @param string $identifier Identificador
     * @return array Información del rate limiting
     */
    public static function getRateLimitInfo($identifier) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            return [
                'attempts' => 0,
                'blocked' => false,
                'remaining_attempts' => 5
            ];
        }
        
        $rateLimit = $_SESSION[$key];
        $blocked = $rateLimit['blocked_until'] && time() < $rateLimit['blocked_until'];
        $remaining = max(0, 5 - $rateLimit['attempts']);
        
        return [
            'attempts' => $rateLimit['attempts'],
            'blocked' => $blocked,
            'blocked_until' => $rateLimit['blocked_until'] ?? null,
            'remaining_attempts' => $remaining
        ];
    }
}