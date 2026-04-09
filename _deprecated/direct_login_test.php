<?php
// direct_login_test.php - Test directo de login sin routing complejo

require_once 'config.php';

echo "=== PÁGINA DE LOGIN DIRECTA ===\n\n";

// Simular petición POST de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    require_once 'models/Usuario.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "🔐 Intentando login con: $username / $password\n";
    
    $usuarioModel = new Usuario();
    $user = $usuarioModel->authenticate($username, $password);
    
    if ($user) {
        echo "✅ Login exitoso!\n";
        echo "   Usuario: {$user['username']}\n";
        echo "   Rol: {$user['rol']}\n";
        echo "   ID: {$user['id']}\n";
        
        // Iniciar sesión
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];
        
        echo "🎯 Sesión iniciada. Redirigiendo al dashboard...\n";
        
        // Mostrar link al dashboard
        echo "\n📱 ACCESO AL SISTEMA:\n";
        echo "Dashboard: <a href='dashboard.php'>Ir al Dashboard</a>\n";
        
    } else {
        echo "❌ Login fallido. Verifica credenciales.\n";
    }
} else {
    // Mostrar formulario de login simple
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Login - Sistema Biométrico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .card { box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: none; }
        .card-header { background: #9F2241; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-white text-center">
                        <h4><i class="fas fa-fingerprint"></i> Sistema Biométrico</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Usuario</label>
                                <input type="text" name="username" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="alert alert-info">
                            <strong>Usuarios de prueba:</strong><br>
                            • admin / admin123<br>
                            • usuario / password123<br>
                            • superadmin / admin123
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Conexión segura con doble factor de autenticación
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html>';
}
?>