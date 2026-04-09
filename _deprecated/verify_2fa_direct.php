<?php
// verify_2fa_direct.php - Verificación 2FA directa

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que hay una sesión 2FA pendiente
if (!isset($_SESSION['pending_2fa']) || $_SESSION['pending_2fa'] !== true) {
    header('Location: login_direct.php');
    exit;
}

$error = '';
$max_attempts = 5;
$timeout = 300;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempts = $_SESSION['2fa_attempts'] ?? 0;
    $timestamp = $_SESSION['2fa_timestamp'] ?? time();
    
    if ($attempts >= $max_attempts) {
        $error = 'Demasiados intentos fallidos. Inicie sesión nuevamente.';
        session_destroy();
    } elseif ((time() - $timestamp) > $timeout) {
        $error = 'El código 2FA ha expirado. Inicie sesión nuevamente.';
        session_destroy();
    } else {
        $code_entered = $_POST['2fa_code'] ?? '';
        
        if ($code_entered === $_SESSION['2fa_code']) {
            // Cargar información del usuario
            require_once 'config.php';
            require_once 'models/Database.php';
            require_once 'models/Usuario.php';
            
            $usuarioModel = new Usuario();
            $user = $usuarioModel->getById($_SESSION['2fa_user_id']);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['empleado_id'] = $user['empleado_id'];
                
                unset($_SESSION['pending_2fa']);
                unset($_SESSION['2fa_code']);
                unset($_SESSION['2fa_user_id']);
                unset($_SESSION['2fa_attempts']);
                unset($_SESSION['2fa_timestamp']);
                
                header('Location: dashboard_simple.php');
                exit;
            } else {
                $error = 'Error al cargar usuario. Intente nuevamente.';
            }
        } else {
            $_SESSION['2fa_attempts']++;
            $error = 'Código 2FA incorrecto. Intento ' . $_SESSION['2fa_attempts'] . " de {$max_attempts}";
        }
    }
}

// Mostrar el código de 2FA para desarrollo (siempre visible para facilitar pruebas)
$dev_code = $_SESSION['2fa_code'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self';">
    <title>Verificación 2FA - Sistema Biométrico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verification-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .verification-header {
            margin-bottom: 2rem;
        }
        
        .verification-header h1 {
            color: #9F2241;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .verification-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .code-display {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: bold;
            color: #9F2241;
            letter-spacing: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            text-align: center;
            letter-spacing: 0.5rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #9F2241;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #9F2241;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #7a1c32;
        }
        
        .alert {
            padding: 1rem;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #9F2241;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .timer {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-header">
            <h1>🔐 Verificación 2FA</h1>
            <p>Ingrese el código de 6 dígitos enviado</p>
        </div>
        
        <?php if ($dev_code): ?>
            <div class="alert alert-success">
                <strong>MODO DESARROLLO:</strong> Su código es: <strong><?= $dev_code ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="code-display">
            <?= $dev_code ?: '------' ?>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="2fa_code">Código de Verificación:</label>
                <input type="text" id="2fa_code" name="2fa_code" 
                       maxlength="6" pattern="[0-9]{6}" 
                       placeholder="000000" required>
                <div class="timer">El código expira en 5 minutos</div>
            </div>
            
            <button type="submit" class="btn">Verificar Código</button>
        </form>
        
        <a href="login_direct.php" class="back-link">← Volver al Login</a>
    </div>

    <script>
        // Auto-enfocar el campo de código
        document.getElementById('2fa_code').focus();
        
        // Auto-submit cuando se ingresen 6 dígitos
        document.getElementById('2fa_code').addEventListener('input', function(e) {
            if (e.target.value.length === 6) {
                e.target.form.submit();
            }
        });
        
        // Temporizador de cuenta regresiva (opcional)
        let timeLeft = 300; // 5 minutos en segundos
        const timerElement = document.querySelector('.timer');
        
        function updateTimer() {
            if (timeLeft > 0) {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerElement.textContent = `El código expira en ${minutes}:${seconds.toString().padStart(2, '0')}`;
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                timerElement.textContent = 'El código ha expirado';
                timerElement.style.color = '#dc3545';
            }
        }
        
        updateTimer();
    </script>
</body>
</html>