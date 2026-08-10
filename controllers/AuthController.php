<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../helpers/SessionSecurity.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class AuthController extends BaseController {
    private $usuarioModel;
    private $sessionSecurity;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->sessionSecurity = new SessionSecurity();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = SecurityHelper::sanitizeString($_POST['username'] ?? '', 'general');
            $password = trim($_POST['password'] ?? '');

            // Rate limiting para intentos de login
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $identifier = $username . '_' . $clientIp;
            
            if ($this->sessionSecurity->isBlocked($identifier)) {
                $error = 'Demasiados intentos fallidos. Intente de nuevo en 15 minutos.';
                include __DIR__ . '/../views/auth/login.php';
                return;
            }

            $user = $this->usuarioModel->authenticate($username, $password);

            if ($user) {
                require_once __DIR__ . '/../helpers/TwoFactorAuth.php';
                $twoFA = new TwoFactorAuth();

                if ($twoFA->is2FAEnabled($user['id'])) {
                    $_SESSION['pending_2fa'] = true;
                    $_SESSION['temp_user'] = $user;
                    $_SESSION['2fa_method'] = 'totp';

                    if (!empty($user['email'])) {
                        $_SESSION['2fa_code'] = $twoFA->generateCode();
                        $_SESSION['2fa_method'] = 'email';
                        $twoFA->sendEmailCode($user['email'], $_SESSION['2fa_code'], $user['id']);
                    }

                    $this->redirect(rtrim(BASE_URL, '/') . '/verify-2fa');
                    return;
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['empleado_id'] = $user['empleado_id'];

                $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, DB_OPTIONS);
                $stmt = $pdo->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
                $stmt->execute([session_id(), session_encode(), time()]);

                $permisosBase = Usuario::getPermisosPorRol($user['rol']);
                $usuarioModel = new Usuario();
                $permisosExtra = $usuarioModel->getPermisos($user['id']);
                $_SESSION['permisos'] = array_merge($permisosBase, $permisosExtra);

                $this->redirect(rtrim(BASE_URL, '/') . '/dashboard');
            } else {
                // Registrar intento fallido para rate limiting
                $this->sessionSecurity->recordFailedAttempt($identifier);
                $error = 'Credenciales incorrectas';
            }
        }

        include __DIR__ . '/../views/auth/login.php';
    }

    public function logout() {
        session_destroy();
        $this->redirect(rtrim(BASE_URL, '/') . '/login');
    }

    public function setup2FA() {
        $this->requireAuth();
        $_SESSION['2fa_user_id'] = $_SESSION['user_id'];
        include __DIR__ . '/../views/auth/setup_2fa.php';
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => SecurityHelper::sanitizeString($_POST['username'] ?? '', 'alphanum'),
                'password' => trim($_POST['password'] ?? ''),
                'email' => SecurityHelper::sanitizeEmail($_POST['email'] ?? '') ?? null,
                'rol' => SecurityHelper::sanitizeString($_POST['rol'] ?? 'usuario', 'alpha'),
                'empleado_id' => SecurityHelper::sanitizeInt($_POST['empleado_id'] ?? null),
                'jefe_directo_id' => SecurityHelper::sanitizeInt($_POST['jefe_directo_id'] ?? null)
            ];

            if ($this->usuarioModel->create($data)) {
                $this->redirect('/login');
            } else {
                $error = 'Error al crear usuario';
            }
        }

        require_once __DIR__ . '/../models/Empleado.php';
        $empleadoModel = new Empleado();
        $empleados = $empleadoModel->getAll();
        
        // Obtener mandos/jefes (empleados con jerarquía de mando)
        require_once __DIR__ . '/../models/Database.php';
        $db = Database::getInstance()->getConnection();
        $stmtMandos = $db->prepare("
            SELECT id, nombre, apellido, rfc, area, jerarquia 
            FROM empleados 
            WHERE activo = 1 
            AND jerarquia IN ('director', 'subdirector', 'jefe_departamento', 'jefe_area', 'supervisor', 'coordinador', 'gerente')
            ORDER BY nombre, apellido
        ");
        $stmtMandos->execute();
        $mandos = $stmtMandos->fetchAll();

        include __DIR__ . '/../views/auth/register.php';
    }

    public function verify2FA() {
        if (!isset($_SESSION['pending_2fa']) || !$_SESSION['pending_2fa']) {
            $this->redirect(rtrim(BASE_URL, '/') . '/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = SecurityHelper::sanitizeString($_POST['2fa_code'] ?? '', 'alphanum');
            $user = $_SESSION['temp_user'] ?? [];
            $method = $_SESSION['2fa_method'] ?? 'email';

            $verified = false;

            if ($method === 'totp') {
                require_once __DIR__ . '/../helpers/TwoFactorAuth.php';
                $twoFA = new TwoFactorAuth();
                $verified = $twoFA->verifyTOTP($user['id'] ?? 0, $code);
            } else {
                $verified = $code === ($_SESSION['2fa_code'] ?? '');
            }

            if ($verified) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['empleado_id'] = $user['empleado_id'];

                $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, DB_OPTIONS);
                $stmt = $pdo->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
                $stmt->execute([session_id(), session_encode(), time()]);

                $permisosBase = Usuario::getPermisosPorRol($user['rol']);
                $usuarioModel = new Usuario();
                $permisosExtra = $usuarioModel->getPermisos($user['id']);
                $_SESSION['permisos'] = array_merge($permisosBase, $permisosExtra);

                unset($_SESSION['pending_2fa']);
                unset($_SESSION['temp_user']);
                unset($_SESSION['2fa_code']);
                unset($_SESSION['2fa_method']);

                $this->redirect(rtrim(BASE_URL, '/') . '/dashboard');
            } else {
                $error = 'Código de verificación incorrecto';
            }
        }

        include __DIR__ . '/../views/auth/verify2fa.php';
    }

    private function generate2FACode() {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . rtrim(BASE_URL, '/') . '/login');
            exit;
        }
    }

    public static function requireAdmin() {
        self::checkAuth();
        if (!in_array($_SESSION['rol'], ['admin', 'superadmin'])) {
            header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard');
            exit;
        }
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function getCurrentUser() {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'rol' => $_SESSION['rol'] ?? null,
            'empleado_id' => $_SESSION['empleado_id'] ?? null
        ];
    }
}
?>
