<?php
require_once 'helpers/Csrf.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA - Sistema Biométrico</title>
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css" rel="stylesheet">
    <style<?= SecurityHelper::nonceAttr() ?>>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: Arial, sans-serif;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background: #ff6b35 !important;
            border-bottom: none;
        }
        .form-control:focus {
            border-color: #ff6b35;
            box-shadow: 0 0 0 0.2rem rgba(255,107,53,0.25);
        }
        .btn-primary {
            background: #ff6b35;
            border: none;
        }
        .btn-primary:hover {
            background: #e55a2b;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h4 class="text-center mb-0"><i class="fas fa-lock"></i> Verificación de Seguridad (2FA)</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-center text-muted mb-3">
                            <i class="fas fa-info-circle"></i> Ingrese el código de 6 dígitos enviado a su correo
                        </p>
                        <?php if (isset($error) && !empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                            <div class="mb-3">
                                <label for="2fa_code" class="form-label"><i class="fas fa-key"></i> Código 2FA</label>
                                <input type="text" class="form-control form-control-lg text-center" id="2fa_code" name="2fa_code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus style="font-size: 24px; letter-spacing: 10px;">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Verificar
                                </button>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?= rtrim(BASE_URL, '/'); ?>/login" class="text-decoration-none">
                                    <i class="fas fa-arrow-left"></i> Volver a Iniciar Sesión
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script<?= SecurityHelper::nonceAttr() ?>>
    document.addEventListener("DOMContentLoaded", function() {
        const input = document.getElementById("2fa_code");
        input.addEventListener("input", function(e) {
            this.value = this.value.replace(/[^0-9]/g, "");
            if (this.value.length === 6) {
                this.closest("form").submit();
            }
        });
    });
    </script>
</body>
</html>
