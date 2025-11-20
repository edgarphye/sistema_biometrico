<?php
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
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="fas fa-user"></i> Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="fas fa-lock"></i> Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label"><i class="fas fa-user-tag"></i> Rol</label>
                            <select class="form-select" id="rol" name="rol">
                                <option value="usuario">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="empleado_id" class="form-label"><i class="fas fa-id-card"></i> Empleado Asociado</label>
                            <select class="form-select" id="empleado_id" name="empleado_id">
                                <option value="">Sin empleado asociado</option>
                                ' . implode('', array_map(function($emp) {
                                    return '<option value="' . $emp['id'] . '">' . htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) . '</option>';
                                }, $empleados ?? [])) . '
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Registrar
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="<?php echo BASE_URL; ?>/login" class="text-decoration-none">¿Ya tienes cuenta? Inicia sesión</a>
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
