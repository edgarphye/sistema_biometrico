<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../helpers/Csrf.php';

class PerfilController extends BaseController {
    private $usuarioModel;
    private $empleadoModel;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->empleadoModel = new Empleado();
    }

    public function renderPerfilModal($usuario, $empleado) {
        $csrfToken = Csrf::token();
        
        $email = htmlspecialchars($usuario['email'] ?? '');
        $username = htmlspecialchars($usuario['username'] ?? '');
        $rol = htmlspecialchars($usuario['rol'] ?? 'usuario');
        
        $empleadoNombre = '';
        $empleadoClave = '';
        $empleadoArea = '';
        $empleadoDepartamento = '';
        
        if ($empleado) {
            $empleadoNombre = htmlspecialchars(($empleado['nombre'] ?? '') . ' ' . ($empleado['apellido'] ?? ''));
            $empleadoClave = htmlspecialchars($empleado['clave'] ?? '');
            $empleadoArea = htmlspecialchars($empleado['area'] ?? '');
            $empleadoDepartamento = htmlspecialchars($empleado['clave_depto'] ?? '');
        }

        $rolLabel = match(strtolower($rol)) {
            'superadmin' => 'Superadministrador',
            'admin' => 'Administrador',
            'mando' => 'Mando',
            'jefe' => 'Jefe de Área',
            default => ucfirst($rol ?: 'Usuario')
        };

        return '
        <div id="perfilModal" class="modal-perfil-overlay" style="display: none;">
            <div class="modal-perfil-content">
                <div class="modal-perfil-header">
                    <h5><i class="fas fa-user-circle me-2"></i>Mi Perfil</h5>
                    <button type="button" class="modal-perfil-close" onclick="cerrarPerfilModal()">&times;</button>
                </div>
                <div class="modal-perfil-body">
                    <form id="perfilForm" method="POST" action="' . rtrim(BASE_URL, '/') . '/perfil/update">
                        <input type="hidden" name="csrf_token" value="' . $csrfToken . '">
                        
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Aquí puedes actualizar tu correo electrónico.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Usuario</label>
                                <input type="text" class="form-control" value="' . $username . '" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol</label>
                                <input type="text" class="form-control" value="' . $rolLabel . '" disabled>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" value="' . htmlspecialchars($usuario['nombre_completo'] ?? '') . '" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Correo Electrónico *</label>
                                <input type="email" class="form-control" name="email" id="email" value="' . $email . '" required>
                            </div>
                        </div>
                        
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-key me-2"></i>Cambiar Contraseña</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label">Contraseña Actual</label>
                                <input type="password" class="form-control" name="current_password" id="current_password" placeholder="••••••••">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="new_password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" name="new_password" id="new_password" placeholder="••••••••" minlength="6">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="••••••••" minlength="6">
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del Empleado</label>
                                <input type="text" class="form-control" value="' . $empleadoNombre . '" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Clave de Empleado</label>
                                <input type="text" class="form-control" value="' . $empleadoClave . '" disabled>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Área</label>
                                <input type="text" class="form-control" value="' . $empleadoArea . '" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Departamento</label>
                                <input type="text" class="form-control" value="' . $empleadoDepartamento . '" disabled>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" onclick="cerrarPerfilModal()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .modal-perfil-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-perfil-content {
            background: white;
            border-radius: 10px;
            width: 95%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .modal-perfil-header {
            background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-perfil-header h5 {
            margin: 0;
        }
        .modal-perfil-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            line-height: 1;
        }
        .modal-perfil-body {
            padding: 20px;
        }
        </style>
        
        <script>
        function abrirPerfilModal() {
            document.getElementById("perfilModal").style.display = "flex";
        }
        
        function cerrarPerfilModal() {
            document.getElementById("perfilModal").style.display = "none";
        }
        
        document.getElementById("perfilModal").addEventListener("click", function(e) {
            if (e.target === this) {
                cerrarPerfilModal();
            }
        });
        
        document.getElementById("perfilForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            var currentPassword = document.getElementById("current_password").value;
            var newPassword = document.getElementById("new_password").value;
            var confirmPassword = document.getElementById("confirm_password").value;
            
            var hasPasswordChange = currentPassword || newPassword || confirmPassword;
            
            if (hasPasswordChange) {
                if (!currentPassword || !newPassword || !confirmPassword) {
                    alert("Completa todos los campos de contraseña para cambiarla");
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    alert("Las contraseñas nuevas no coinciden");
                    return;
                }
                
                if (newPassword.length < 6) {
                    alert("La contraseña debe tener al menos 6 caracteres");
                    return;
                }
            }
            
            var formData = new FormData(this);
            
            fetch("' . rtrim(BASE_URL, '/') . '/perfil/update", {
                method: "POST",
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    alert(data.message || "Actualizado correctamente");
                    cerrarPerfilModal();
                } else {
                    alert(data.error || "Error al actualizar");
                }
            })
            .catch(function(error) {
                alert("Error de conexión");
            });
        });
        </script>
        ';
    }

    public function update() {
        AuthController::checkAuth();
        
        header('Content-Type: application/json');
        
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? null;
        if (!Csrf::validate($csrfToken)) {
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $hasPasswordChange = $currentPassword || $newPassword || $confirmPassword;

        if ($hasPasswordChange) {
            if (!$currentPassword || !$newPassword || !$confirmPassword) {
                echo json_encode(['success' => false, 'error' => 'Completa todos los campos para cambiar la contraseña']);
                return;
            }

            $usuario = $this->usuarioModel->getById($userId);
            if (!$usuario) {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
                return;
            }

            if (!password_verify($currentPassword, $usuario['password'])) {
                echo json_encode(['success' => false, 'error' => 'La contraseña actual es incorrecta']);
                return;
            }

            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'error' => 'Las contraseñas nuevas no coinciden']);
                return;
            }

            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres']);
                return;
            }

            try {
                $this->usuarioModel->updatePassword($userId, $newPassword);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Error al cambiar la contraseña']);
                return;
            }
        }

        if (empty($email)) {
            echo json_encode(['success' => false, 'error' => 'El correo electrónico es obligatorio']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'El correo electrónico no es válido']);
            return;
        }

        $data = ['email' => $email];

        try {
            $result = $this->usuarioModel->update($userId, $data);
            
            if ($result || $hasPasswordChange) {
                if ($hasPasswordChange && $result) {
                    echo json_encode(['success' => true, 'message' => 'Correo y contraseña actualizados correctamente']);
                } elseif ($hasPasswordChange) {
                    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Correo actualizado correctamente']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al actualizar los datos']);
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está en uso']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
            }
        }
    }
}
