<?php
require_once 'models/Usuario.php';

class AuthController {
    private $usuarioModel;

    public function __construct() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->usuarioModel = new Usuario();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->usuarioModel->authenticate($username, $password);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['empleado_id'] = $user['empleado_id'];

                header('Location: /sistema_biometrico/');
                exit;
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
        header('Location: /sistema_biometrico/login');
        exit;
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
                header('Location: /sistema_biometrico/login');
                exit;
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

    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /sistema_biometrico/login');
            exit;
        }
    }

    public static function requireAdmin() {
        self::requireAuth();
        if ($_SESSION['rol'] !== 'admin') {
            header('Location: /sistema_biometrico/');
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
