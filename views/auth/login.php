<?php
$content = '
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="text-center mb-0"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</h4>
                </div>
                <div class="card-body">
                    ' . (isset($error) ? '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . $error . '</div>' : '') . '
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="fas fa-user"></i> Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="fas fa-lock"></i> Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="<?php echo BASE_URL; ?>/register" class="text-decoration-none">¿No tienes cuenta? Regístrate</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener(\'DOMContentLoaded\', function() {
    // Auto-focus on username field
    document.getElementById(\'username\').focus();
});
</script>
';

include __DIR__ . '/../layout.php';
?>
