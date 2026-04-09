<?php
require_once 'layout.php';

// Verificar si viene de login o es setup directo
$setupMode = isset($_GET['mode']) ? $_GET['mode'] : 'email';
$userId = $_SESSION['2fa_user_id'] ?? null;

if (!$userId) {
    header('Location: /login');
    exit;
}

// Obtener información del usuario
require_once 'models/Usuario.php';
$usuarioModel = new Usuario();
$user = $usuarioModel->getById($userId);

if (!$user) {
    session_destroy();
    header('Location: /login');
    exit;
}

$twoFA = new TwoFactorAuth();
$error = '';
$success = '';

// Procesar setup de TOTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($setupMode === 'totp') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'generate') {
            // Generar nuevo secreto TOTP
            $totpData = $twoFA->generateTOTPSecret($userId, $user['username']);
            $_SESSION['totp_setup_secret'] = $totpData['secret'];
        } elseif ($action === 'verify') {
            // Verificar código TOTP
            $code = $_POST['totp_code'] ?? '';
            if ($twoFA->verifyTOTP($userId, $code)) {
                $success = 'Autenticación 2FA configurada exitosamente';
                unset($_SESSION['totp_setup_secret']);
                unset($_SESSION['pending_2fa']);
                
                // Establecer sesión completa
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['empleado_id'] = $user['empleado_id'];
                $_SESSION['2fa_method'] = 'totp';
                
                header('Location: /dashboard');
                exit;
            } else {
                $error = 'Código incorrecto. Intente nuevamente.';
            }
        }
    }
}

// Generar QR code si es modo TOTP
$qrCode = '';
$secretKey = '';
if ($setupMode === 'totp' && isset($_SESSION['totp_setup_secret'])) {
    $totpData = [
        'secret' => $_SESSION['totp_setup_secret'],
        'qr_url' => $twoFA->generateQRCode($user['username'], $_SESSION['totp_setup_secret']),
        'manual_key' => $_SESSION['totp_setup_secret']
    ];
    $qrCode = $totpData['qr_url'];
    $secretKey = $totpData['secret'];
}
?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-lg-6 mx-auto my-auto">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">
                            <i class="fas fa-shield-alt text-primary me-2"></i>
                            Configurar Autenticación de Dos Factores
                        </h2>
                        <p class="text-muted">Proteja su cuenta con una capa adicional de seguridad</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Tabs para elegir método 2FA -->
                    <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $setupMode === 'totp' ? 'active' : '' ?>" 
                                    id="pills-totp-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#pills-totp" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Google Authenticator
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $setupMode === 'email' ? 'active' : '' ?>" 
                                    id="pills-email-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#pills-email" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-envelope me-2"></i>
                                Código por Email
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="pills-tabContent">
                        <!-- TOTP Tab -->
                        <div class="tab-pane fade <?= $setupMode === 'totp' ? 'show active' : '' ?>" 
                             id="pills-totp" 
                             role="tabpanel">
                            
                            <?php if (!$secretKey): ?>
                                <!-- Botón para generar secreto -->
                                <div class="text-center">
                                    <h4 class="mb-3">Configurar Google Authenticator</h4>
                                    <p class="text-muted mb-4">
                                        Use Google Authenticator, Microsoft Authenticator o cualquier app TOTP compatible
                                    </p>
                                    <form method="post">
                                        <input type="hidden" name="action" value="generate">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-qrcode me-2"></i>
                                            Generar Código QR
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <!-- Mostrar QR y formulario de verificación -->
                                <div class="row">
                                    <div class="col-md-6 text-center">
                                        <h5>1. Escanee el QR Code</h5>
                                        <div class="bg-light p-3 rounded mb-3">
                                            <img src="<?= $qrCode ?>" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                                        </div>
                                        <p class="small text-muted">
                                            Use su app de autenticación para escanear este código
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>2. Ingrese código manualmente</h5>
                                        <div class="bg-light p-3 rounded mb-3">
                                            <div class="font-monospace fs-5 text-center" style="letter-spacing: 2px;">
                                                <?= chunk_split($secretKey, 4, ' ') ?>
                                            </div>
                                        </div>
                                        <p class="small text-muted">
                                            O ingrese este código manualmente en su app
                                        </p>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h5>3. Verifique configuración</h5>
                                <form method="post">
                                    <input type="hidden" name="action" value="verify">
                                    <div class="mb-3">
                                        <label class="form-label">Código de 6 dígitos:</label>
                                        <input type="text" 
                                               class="form-control form-control-lg text-center" 
                                               name="totp_code" 
                                               placeholder="000000"
                                               maxlength="6"
                                               pattern="[0-9]{6}"
                                               required
                                               autocomplete="one-time-code">
                                        <div class="form-text">Ingrese el código de 6 dígitos de su app</div>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-check me-2"></i>
                                        Verificar y Activar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- Email Tab -->
                        <div class="tab-pane fade <?= $setupMode === 'email' ? 'show active' : '' ?>" 
                             id="pills-email" 
                             role="tabpanel">
                            
                            <div class="text-center">
                                <h4 class="mb-3">Recibir código por Email</h4>
                                <p class="text-muted mb-4">
                                    Envíaremos un código de un solo uso a su correo electrónico
                                </p>
                                
                                <div class="mb-4">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Se enviará un código a: <strong><?= htmlspecialchars($user['email']) ?></strong>
                                    </div>
                                </div>

                                <form method="post" action="/auth/send-email-2fa">
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            Enviar Código por Email
                                        </button>
                                    </div>
                                </form>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        El código expirará en 5 minutos por seguridad
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de seguridad -->
                    <div class="alert alert-warning mt-4">
                        <h6><i class="fas fa-shield-alt me-2"></i>Información de Seguridad:</h6>
                        <ul class="mb-0">
                            <li>Los códigos 2FA son de un solo uso</li>
                            <li>Nunca comparta sus códigos con nadie</li>
                            <li>Guarde su método de recuperación seguro</li>
                            <li>Cambie su contraseña regularmente</li>
                        </ul>
                    </div>

                    <!-- Opciones adicionales -->
                    <div class="text-center mt-3">
                        <a href="/logout" class="text-muted">
                            <i class="fas fa-arrow-left me-1"></i>
                            Cancelar y volver al login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-submit TOTP generation
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('[data-bs-toggle="pill"]');
    
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            if (target === '#pills-totp') {
                // Si no hay secreto generado, mostrar botón de generación
                const secretField = document.querySelector('#totp_secret');
                if (!secretField) {
                    const generateForm = document.querySelector('form[action*="action=generate"]');
                    if (generateForm) {
                        generateForm.submit();
                    }
                }
            }
        });
    });

    // Formato automático para código TOTP
    const totpInput = document.querySelector('input[name="totp_code"]');
    if (totpInput) {
        totpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    }
});
</script>

<?php include 'footer.php'; ?>