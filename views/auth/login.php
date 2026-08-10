<?php
// Variables para el template
$username = $_POST['username'] ?? '';
$error = $error ?? '';
$csrf_token = Csrf::token() ?? '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema Biométrico</title>
    
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css" rel="stylesheet">
    
    <style<?= SecurityHelper::nonceAttr() ?>>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e8eaf0;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 16px;
            padding-top: 10vh;
        }
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 1100px;
            border: 2px solid #6a0c33;
            border-radius: 16px;
            background: white;
        }
        /* ── Lado Izquierdo: Marca Institucional ── */
        .brand-side {
            flex: 1;
            background: #fcf8f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            border-right: 1px solid rgba(106,12,51,0.1);
        }
        .brand-side::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(106,12,51,0.03) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(106,12,51,0.02) 0%, transparent 40%);
        }
        .brand-side::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, #6a0c33, transparent);
        }
        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: #1a1a2e;
            max-width: 400px;
        }
        /* Logo SEP */
        .logo-shield {
            width: 180px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
            border: 3px solid #6a0c33;
            border-radius: 12px;
            padding: 12px 20px;
        }
        .logo-shield:hover { transform: scale(1.05); }
        .logo-shield img { width: 100%; height: auto; }
        .brand-title {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
            text-shadow: none;
            color: #6a0c33;
        }
        .brand-subtitle {
            font-size: 0.9rem;
            opacity: 0.85;
            line-height: 1.6;
            font-weight: 300;
            letter-spacing: 0.3px;
        }
        .brand-subtitle-sm {
            font-size: 0.8rem;
            opacity: 0.7;
            line-height: 1.5;
            font-weight: 300;
            letter-spacing: 0.3px;
            margin-top: 0.3rem;
        }
        .brand-divider {
            width: 50px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #6a0c33, transparent);
            margin: 1.5rem auto;
            border-radius: 1px;
        }
        .brand-footer {
            font-size: 0.78rem;
            opacity: 0.5;
            margin-top: 2rem;
            line-height: 1.5;
        }
        .brand-footer strong { opacity: 0.8; }

        /* ── Lado Derecho: Formulario ── */
        .form-side {
            width: 460px;
            min-width: 460px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 2.5rem;
            position: relative;
            box-shadow: -2px 0 12px rgba(106,12,51,0.06);
        }
        .form-side::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6a0c33, #C9A84C, #6a0c33);
        }
        .form-container {
            width: 100%;
            max-width: 360px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-header .mini-badge {
            display: inline-block;
            background: #6a0c33;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }
        .form-header h2 {
            color: #1a1a2e;
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 0.3rem;
        }
        .form-header p {
            color: #8e8e9a;
            font-size: 0.85rem;
            margin: 0;
        }
        .form-floating-custom {
            margin-bottom: 1.2rem;
        }
        .form-floating-custom label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #4a4a5a;
            margin-bottom: 0.4rem;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #b0b0c0;
            font-size: 0.9rem;
            transition: color 0.2s;
            pointer-events: none;
        }
        .input-wrapper:focus-within i { color: #6a0c33; }
        .form-control-custom {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            border: 1.5px solid #e0e0e8;
            border-radius: 10px;
            font-size: 0.9rem;
            background: #f8f9fc;
            transition: all 0.25s;
            outline: none;
            color: #1a1a2e;
        }
        .form-control-custom::placeholder { color: #b0b0c0; }
        .form-control-custom:focus {
            border-color: #6a0c33;
            background: white;
            box-shadow: 0 0 0 4px rgba(106,12,51,0.08);
        }
        .form-options {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
        }
        .form-options a {
            color: #8e8e9a;
            font-size: 0.8rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .form-options a:hover { color: #6a0c33; }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #6a0c33, #4d0825);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(106,12,51,0.3);
        }
        .btn-login:active { transform: translateY(0); }

        /* ── Credenciales de Prueba (colapsables) ── */
        .test-creds-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 1.5rem;
            padding: 0.5rem;
            border: none;
            background: none;
            color: #b0b0c0;
            font-size: 0.78rem;
            cursor: pointer;
            width: 100%;
            transition: color 0.2s;
        }
        .test-creds-toggle:hover { color: #6a0c33; }
        .test-creds-toggle i { transition: transform 0.3s; }
        .test-creds-toggle.open i { transform: rotate(180deg); }
        .test-creds-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease, opacity 0.3s ease, margin 0.3s ease;
            opacity: 0;
            margin-top: 0;
        }
        .test-creds-body.open {
            max-height: 160px;
            opacity: 1;
            margin-top: 0.5rem;
        }
        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.8rem;
            margin: 0.25rem 0;
            background: #f8f9fc;
            border-radius: 8px;
            font-size: 0.8rem;
            border: 1px solid #eeeef4;
        }
        .credential-item code {
            background: #e8eaef;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.78rem;
            color: #6a0c33;
        }

        .ssl-badge {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.72rem;
            color: #b0b0c0;
        }

        /* ── Alerta de error ── */
        .alert-custom {
            background: #fff0f0;
            border: 1px solid #ffd4d4;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #cc3344;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .alert-custom i { font-size: 1rem; }

        /* ── Responsive ── */
        @media (max-width: 820px) {
            .login-wrapper { flex-direction: column; }
            .brand-side {
                padding: 2rem 1.5rem;
                min-height: auto;
                flex: none;
                border-right: none;
            }
            .brand-side::after { display: none; }
            .logo-shield {
                width: 120px;
                margin-bottom: 1rem;
            }
            .brand-title { font-size: 1.4rem; }
            .brand-subtitle { font-size: 0.8rem; }
            .brand-subtitle-sm { font-size: 0.72rem; }
            .brand-divider { margin: 1rem auto; }
            .brand-footer { margin-top: 1rem; }
            .form-side {
                width: 100%;
                min-width: 0;
                padding: 2rem 1.5rem;
            }
            .form-side::before { display: none; }
        }
        @media (max-height: 700px) {
            body { padding-top: 16px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Lado izquierdo: Marca AEFCM -->
        <div class="brand-side">
            <div class="brand-content">
                <div class="logo-shield">
                    <img src="/assets/images/sep_logo.png" alt="SEP">
                </div>
                <div class="brand-title">AEFCM</div>
                <div class="brand-subtitle">
                    Autoridad Educativa Federal<br>en la Ciudad de México
                </div>
                <div class="brand-subtitle-sm">
                    Dirección General de Innovación<br>y Fortalecimiento Académico
                </div>
                <div class="brand-divider"></div>
                <div class="brand-footer">
                    <strong>Sistema Biométrico</strong><br>
                    Control de Asistencia del Personal
                </div>
            </div>
        </div>

        <!-- Lado derecho: Formulario -->
        <div class="form-side">
            <div class="form-container">
                <div class="form-header">
                    <div class="mini-badge"><i class="fas fa-fingerprint me-1"></i> Acceso</div>
                    <h2>Bienvenido</h2>
                    <p>Ingrese sus credenciales para continuar</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= htmlspecialchars(rtrim(BASE_URL, '/') . '/login') ?>">
                    <div class="form-floating-custom">
                        <label for="username">Usuario</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" class="form-control-custom"
                                   value="<?= htmlspecialchars($username) ?>" required autofocus
                                   placeholder="Ingrese su usuario">
                        </div>
                    </div>
                    <div class="form-floating-custom">
                        <label for="password">Contraseña</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control-custom"
                                   required placeholder="••••••••">
                        </div>
                    </div>

                    <div class="form-options">
                        <a href="#" title="Contacte al administrador"><i class="fas fa-question-circle me-1"></i>¿Olvidó su contraseña?</a>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión
                    </button>
                </form>

                <!-- Credenciales de prueba colapsables -->
                <button class="test-creds-toggle" id="toggleCreds" type="button">
                    <i class="fas fa-chevron-down"></i>
                    <span>Credenciales de prueba</span>
                </button>
                <div class="test-creds-body" id="credsBody">
                    <div class="credential-item">
                        <span><i class="fas fa-shield-alt me-1" style="color:#6a0c33;"></i> Administrador</span>
                        <code>admin / admin123</code>
                    </div>
                    <div class="credential-item">
                        <span><i class="fas fa-user me-1" style="color:#6a0c33;"></i> Usuario</span>
                        <code>usuario / password123</code>
                    </div>
                </div>

                <div class="ssl-badge">
                    <i class="fas fa-lock me-1"></i> Conexión segura SSL
                </div>
            </div>
        </div>
    </div>

    <script<?= SecurityHelper::nonceAttr() ?>>
        document.getElementById('toggleCreds').addEventListener('click', function() {
            const body = document.getElementById('credsBody');
            body.classList.toggle('open');
            this.classList.toggle('open');
        });
    </script>
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/jquery-3.6.0.min.js"<?= SecurityHelper::nonceAttr() ?>></script>
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/bootstrap.bundle.min.js"<?= SecurityHelper::nonceAttr() ?>></script>
</body>
</html>
