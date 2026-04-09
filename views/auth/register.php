<?php
require_once 'helpers/Csrf.php';
$content = '
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="text-center mb-0"><i class="fas fa-user-plus"></i> Registrar Usuario</h4>
                </div>
                <div class="card-body">
                    ' . (isset($error) ? '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . $error . '</div>' : '') . '
                    '; if (isset($_SESSION['form_errors'])): echo '
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Errores de validación:</h6>
                            <ul class="mb-0">'; 
                                foreach ($_SESSION['form_errors'] as $field => $error) {
                                    echo '<li>' . htmlspecialchars($error) . '</li>';
                                }
                                echo '</ul>
                        </div>';
                        unset($_SESSION['form_errors']);
                    endif; echo '
                    <form method="POST">
                        ' . Csrf::getHiddenInput() . '
                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="fas fa-user"></i> Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="'; echo htmlspecialchars($_SESSION['form_data']['username'] ?? ''); echo '" required>
                            <div class="form-text">Mínimo 4 caracteres, solo letras, números y guiones bajos</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="'; echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); echo '" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="fas fa-lock"></i> Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Mínimo 8 caracteres, incluir mayúsculas, minúsculas, números y un carácter especial</div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label"><i class="fas fa-user-tag"></i> Rol</label>
                            <select class="form-select" id="rol" name="rol">
                                <option value="empleado" '; if (($_SESSION['form_data']['rol'] ?? '') == 'empleado') echo 'selected'; echo '>Empleado</option>
                                <option value="rh" '; if (($_SESSION['form_data']['rol'] ?? '') == 'rh') echo 'selected'; echo '>Recursos Humanos</option>
                                <option value="gerente" '; if (($_SESSION['form_data']['rol'] ?? '') == 'gerente') echo 'selected'; echo '>Gerente</option>
                                <option value="admin" '; if (($_SESSION['form_data']['rol'] ?? '') == 'admin') echo 'selected'; echo '>Administrador</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="empleado_id" class="form-label"><i class="fas fa-id-card"></i> Empleado Asociado</label>
                            <select class="form-select" id="empleado_id" name="empleado_id">
                                <option value="">Sin empleado asociado</option>
                                '; 
                                foreach ($empleados ?? [] as $emp) {
                                    $selected = (isset($_SESSION['form_data']['empleado_id']) && $_SESSION['form_data']['empleado_id'] == $emp['id']) ? 'selected' : '';
                                    echo '<option value="' . $emp['id'] . '" ' . $selected . '>' . htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) . '</option>';
                                }
                                echo '
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success" style="background-color: #235B4E; border-color: #235B4E;">
                                <i class="fas fa-user-plus"></i> Registrar
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="<?php echo rtrim(BASE_URL, \'/\'); ?>/login" class="text-decoration-none">¿Ya tienes cuenta? Inicia sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener(\'DOMContentLoaded\', function() {
    const password = document.getElementById(\'password\');
    const confirmPassword = document.getElementById(\'confirm_password\');

    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity(\'Las contraseñas no coinciden\');
        } else {
            confirmPassword.setCustomValidity(\'\');
        }
    }

    password.addEventListener(\'change\', validatePassword);
    confirmPassword.addEventListener(\'keyup\', validatePassword);
});
</script>
';

include __DIR__ . '/../layout.php';
?>
