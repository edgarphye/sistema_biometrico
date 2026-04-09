<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/BaseController.php';

class AuthController extends BaseController {
    private $usuarioModel;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->usuarioModel->authenticate($username, $password);

            if ($user) {
                // Temporalmente desactivar 2FA para testing
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['user_role'] = $user['rol']; // Añadir variable adicional para compatibilidad
                $_SESSION['empleado_id'] = $user['empleado_id'];
                
                $permisosBase = Usuario::getPermisosPorRol($user['rol']);
                $usuarioModel = new Usuario();
                $permisosExtra = $usuarioModel->getPermisos($user['id']);
                $_SESSION['permisos'] = array_merge($permisosBase, $permisosExtra);
                
                // Debug: registrar variables de sesión
                error_log("AUTH DEBUG - User logged in: " . print_r([
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'rol' => $_SESSION['rol'],
                    'user_role' => $_SESSION['user_role'],
                    'permisos' => $_SESSION['permisos']
                ], true));
                
                $this->redirect(rtrim(BASE_URL, '/') . '/dashboard');
            } else {
                $error = 'Credenciales incorrectas';
            }
        }

        $content = '
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="text-center">Iniciar Sesión</h4>
                        </div>
                        <div class="card-body">
                            ' . (isset($error) ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
                            <form method="POST">
                                ' . Csrf::getHiddenInput() . '
                                <div class="mb-3">
                                    <label for="username" class="form-label">Usuario</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ';

        include __DIR__ . '/../views/layout.php';
    }

    public function logout() {
        session_destroy();
        $this->redirect(rtrim(BASE_URL, '/') . '/login');
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $_POST['username'],
                'password' => $_POST['password'],
                'rol' => $_POST['rol'] ?? 'usuario',
                'empleado_id' => $_POST['empleado_id'] ?? null
            ];

            if ($this->usuarioModel->create($data)) {
                $this->redirect('/login');
            } else {
                $error = 'Error al crear usuario';
            }
        }

        require_once 'models/Empleado.php';
        $empleadoModel = new Empleado();
        $empleados = $empleadoModel->getAll();

        $content = '
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="text-center">Registrar Usuario</h4>
                        </div>
                        <div class="card-body">
                            ' . (isset($error) ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
                            <form method="POST">
                                ' . Csrf::getHiddenInput() . '
                                <div class="mb-3">
                                    <label for="username" class="form-label">Usuario</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol</label>
                                    <select class="form-select" id="rol" name="rol">
                                        <option value="usuario">Usuario</option>
                                        <option value="admin">Administrador</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="empleado_id" class="form-label">Empleado Asociado</label>
                                    <select class="form-select" id="empleado_id" name="empleado_id">
                                        <option value="">Sin empleado asociado</option>
                                        ' . implode('', array_map(function($emp) {
                                            return '<option value="' . $emp['id'] . '">' . htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) . '</option>';
                                        }, $empleados)) . '
                                    </select>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success">Registrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ';

        include __DIR__ . '/../views/layout.php';
    }

    public function verify2FA() {
        if (!isset($_SESSION['pending_2fa']) || !$_SESSION['pending_2fa']) {
            $this->redirect(rtrim(BASE_URL, '/') . '/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = $_POST['2fa_code'] ?? '';
            
            if ($code === ($_SESSION['2fa_code'] ?? '')) {
                // Código correcto, finalizar login
                $user = $_SESSION['temp_user'];
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['empleado_id'] = $user['empleado_id'];
                
                // Limpiar sesión 2FA
                unset($_SESSION['pending_2fa']);
                unset($_SESSION['temp_user']);
                unset($_SESSION['2fa_code']);
                
                $this->redirect(rtrim(BASE_URL, '/') . '/dashboard');
            } else {
                $error = 'Código de verificación incorrecto';
            }
        }

        $content = '
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h4 class="text-center">Verificación de Dos Pasos</h4></div>
                        <div class="card-body">
                            ' . (isset($error) ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
                            <p class="text-center">Ingresa el código de verificación (Código de prueba: ' . ($_SESSION['2fa_code'] ?? '') . ')</p>
                            <form method="POST">
                                ' . Csrf::getHiddenInput() . '
                                <div class="mb-3"><input type="text" class="form-control text-center" name="2fa_code" required autocomplete="off"></div>
                                <div class="d-grid"><button type="submit" class="btn btn-primary">Verificar</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        include __DIR__ . '/../views/layout.php';
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
