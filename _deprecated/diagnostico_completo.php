<!DOCTYPE html>
<html>
<head>
    <?php
    // Cargar config para tener BASE_URL
    require_once __DIR__ . '/config.php';
    ?>
    <title>Flujo Completo de Validaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        window.BASE_URL = window.location.origin + "<?php echo rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/'); ?>";
    </script>
</head>
<body>
    <div class="container mt-4">
        <div class="alert alert-warning">
            <h4><i class="fas fa-triangle-exclamation"></i> INSTRUCCIONES IMPORTANTES</h4>
            
            <h5>1. Primero - Iniciar Sesión:</h5>
            <p><a href="<?php echo BASE_URL; ?>/login" class="btn btn-primary">Ir a Login</a></p>
            <p><strong>Usuario:</strong> jefe1_prueba</p>
            <p><strong>Contraseña:</strong> jefe123</p>
            
            <hr>
            
            <h5>2. Después - Acceder a Validaciones:</h5>
            <p><a href="<?php echo BASE_URL; ?>/validaciones" class="btn btn-success">Ir a Validaciones</a></p>
            
            <hr>
            
            <h5>3. Problema Actual:</h5>
            <p>Las animaciones "Cargando..." no desaparecen porque las llamadas AJAX fallan con "Failed to fetch".</p>
            <p>Esto puede deberse a:</p>
            <ul>
                <li>Sesión no iniciada correctamente</li>
                <li>Problema con BASE_URL</li>
                <li>Problema con CSRF tokens</li>
            </ul>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test 1: Verificar Session Status</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-info" onclick="checkSession()">Verificar Sesión</button>
                        <div id="session_result" class="mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test 2: Test AJAX Directo</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-warning" onclick="testAjax()">Probar AJAX</button>
                        <div id="ajax_result" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function checkSession() {
            const resultDiv = document.getElementById('session_result');
            
            try {
                const response = await fetch(window.BASE_URL + '/login', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const text = await response.text();
                resultDiv.innerHTML = `
                    <div class="alert alert-info">
                        <strong>Status:</strong> ${response.status}<br>
                        <strong>Contenido:</strong> ${text.substring(0, 100)}...
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }
        
        async function testAjax() {
            const resultDiv = document.getElementById('ajax_result');
            
            try {
                console.log('Probando AJAX a:', window.BASE_URL + '/validaciones/buscar-empleados');
                
                const response = await fetch(window.BASE_URL + '/validaciones/buscar-empleados', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                console.log('Response status:', response.status);
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <strong>✅ AJAX Funciona!</strong><br>
                        Status: ${response.status}<br>
                        Empleados: ${data.empleados ? data.empleados.length : 'N/A'}
                    </div>
                `;
            } catch (error) {
                console.error('Error AJAX:', error);
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>❌ AJAX Falló:</strong><br>
                        ${error.message}
                    </div>
                `;
            }
        }
        
        // Auto-test al cargar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Página de diagnóstico cargada');
            console.log('BASE_URL:', window.BASE_URL);
            console.log('Location actual:', window.location.href);
        });
    </script>
</body>
</html>