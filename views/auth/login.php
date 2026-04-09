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
    
    <!-- Content Security Policy: Permite CDNs necesarios -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://cdn.datatables.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; img-src 'self' data:;">

    <!-- Estilos Externos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #9F2241 0%, #7a1c32 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 400px;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            background: #9F2241;
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .login-form {
            padding: 2rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
        }
        .form-control:focus {
            border-color: #9F2241;
            box-shadow: 0 0 0 0.25rem rgba(159, 34, 65, 0.25);
        }
        .btn-primary {
            background-color: #9F2241;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #7a1c32;
            transform: translateY(-1px);
        }
        .credential-item {
            padding: 0.6rem;
            margin: 0.4rem 0;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #9F2241;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div style="font-size: 3.5rem; margin-bottom: 0.5rem;">👆</div>
            <h2 class="h4 mb-1">Sistema Biométrico</h2>
            <p class="small mb-0" style="opacity: 0.8;">Gestión de Personal y Asistencia</p>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-danger mb-4 py-2 small">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= htmlspecialchars(rtrim(BASE_URL, '/') . '/login') ?>">
                <div class="mb-3">
                    <label for="username" class="form-label small fw-bold text-muted">👤 Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?= htmlspecialchars($username) ?>" required autofocus placeholder="Nombre de usuario">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold text-muted">🔒 Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           required placeholder="••••••••">
                </div>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    🚪 Iniciar Sesión
                </button>
            </form>
            
            <div class="mt-4">
                <p class="small text-muted mb-2 fw-bold">ℹ️ Credenciales de Prueba</p>
                <div class="credential-item">
                    <strong>Admin:</strong> <code>admin</code> / <code>admin123</code>
                </div>
                <div class="credential-item">
                    <strong>Usuario:</strong> <code>usuario</code> / <code>password123</code>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-lock me-1"></i> Acceso Seguro SSL
                </small>
            </div>
        </div>
    </div>

    <!-- Scripts: Cargados al final para garantizar disponibilidad de $ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
