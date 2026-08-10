<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Dispositivos - Sistema Biométrico</title>
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/css/colores-pantone.css" rel="stylesheet">
    <style>
        .device-config-card { transition: transform 0.2s; }
        .device-config-card:hover { transform: translateY(-2px); }
        .status-indicator { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .status-active { background-color: var(--color-secondary); }
        .status-inactive { background-color: var(--color-gray-dark); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/sistema_biometrico/"><i class="fas fa-fingerprint"></i> Sistema Biométrico</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/sistema_biometrico/empleados">Empleados</a>
                <a class="nav-link" href="/sistema_biometrico/asistencia">Asistencia</a>
                <a class="nav-link active" href="/sistema_biometrico/biometricos">Dispositivos</a>
                <a class="nav-link" href="/sistema_biometrico/biometricos/configurar">Configurar</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="fas fa-cogs"></i> Configuración de Dispositivos Biométricos</h1>
                <p class="text-muted">Gestiona la configuración de acceso y registro de dispositivos biométricos</p>
            </div>
        </div>

        <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Formulario de configuración -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus-circle"></i> Configurar Nuevo Dispositivo</h5>
                    </div>
                    <div class="card-body">
<?php require_once 'helpers/Csrf.php'; ?>
                        <form method="POST" action="/sistema_biometrico/biometricos/configurar">
                            <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dispositivo_id" class="form-label">ID del Dispositivo *</label>
                                        <input type="number" class="form-control" id="dispositivo_id" name="dispositivo_id" min="1" max="10" required>
                                        <div class="form-text">ID único del dispositivo (1-10)</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre del Dispositivo *</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tipo" class="form-label">Tipo de Dispositivo</label>
                                        <select class="form-select" id="tipo" name="tipo">
                                            <option value="dual">Dual (Huella + Cara)</option>
                                            <option value="huella">Solo Huella</option>
                                            <option value="cara">Solo Cara</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="marca" class="form-label">Marca</label>
                                        <input type="text" class="form-control" id="marca" name="marca" placeholder="Ej: CKTeco">
                                    </div>
                                    <div class="mb-3">
                                        <label for="modelo" class="form-label">Modelo</label>
                                        <input type="text" class="form-control" id="modelo" name="modelo" placeholder="Ej: K40">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ip_address" class="form-label">Dirección IP</label>
                                        <input type="text" class="form-control" id="ip_address" name="ip_address" placeholder="192.168.1.100">
                                    </div>
                                    <div class="mb-3">
                                        <label for="puerto" class="form-label">Puerto</label>
                                        <input type="number" class="form-control" id="puerto" name="puerto" value="4370" min="1" max="65535">
                                    </div>
                                    <div class="mb-3">
                                        <label for="usuario" class="form-label">Usuario de Acceso</label>
                                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="admin">
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Contraseña *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="timeout" class="form-label">Timeout (seg)</label>
                                                <input type="number" class="form-control" id="timeout" name="timeout" value="30" min="5" max="300">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="max_users" class="form-label">Máx. Usuarios</label>
                                                <input type="number" class="form-control" id="max_users" name="max_users" value="1000" min="1" max="10000">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="auto_sync" name="auto_sync" checked>
                                            <label class="form-check-label" for="auto_sync">
                                                Sincronización automática
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="log_level" class="form-label">Nivel de Log</label>
                                        <select class="form-select" id="log_level" name="log_level">
                                            <option value="DEBUG">Debug</option>
                                            <option value="INFO" selected>Info</option>
                                            <option value="WARNING">Warning</option>
                                            <option value="ERROR">Error</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Configuración
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de dispositivos configurados -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Dispositivos Configurados</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($dispositivos)): ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <p>No hay dispositivos configurados aún.</p>
                            <p>Configure un dispositivo usando el formulario superior.</p>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($dispositivos as $dispositivo): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card device-config-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <span class="status-indicator <?php echo $dispositivo['activo'] ? 'status-active' : 'status-inactive'; ?>"></span>
                                            Dispositivo <?php echo $dispositivo['dispositivo_id']; ?> - <?php echo htmlspecialchars($dispositivo['nombre']); ?>
                                            <?php if ($dispositivo['estado'] === 'conectado'): ?>
                                                <span class="badge bg-success float-end">Conectado</span>
                                            <?php elseif ($dispositivo['estado'] === 'error'): ?>
                                                <span class="badge bg-danger float-end">Error</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary float-end">Desconectado</span>
                                            <?php endif; ?>
                                        </h6>
                                        <div class="card-text small text-muted mb-2">
                                            <div><strong>Tipo:</strong> <?php echo ucfirst($dispositivo['tipo']); ?></div>
                                            <?php if ($dispositivo['marca']): ?>
                                                <div><strong>Marca:</strong> <?php echo htmlspecialchars($dispositivo['marca']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($dispositivo['ip_address']): ?>
                                                <div><strong>IP:</strong> <?php echo htmlspecialchars($dispositivo['ip_address']); ?>:<?php echo $dispositivo['puerto']; ?></div>
                                            <?php endif; ?>
                                            <div><strong>Estado:</strong> <?php echo ucfirst($dispositivo['estado']); ?></div>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary btn-sm" onclick="editarDispositivo(<?php echo $dispositivo['dispositivo_id']; ?>)">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <button class="btn btn-outline-success btn-sm" onclick="testConexion(<?php echo $dispositivo['dispositivo_id']; ?>)">
                                                <i class="fas fa-plug"></i> Test
                                            </button>
                                            <button class="btn btn-outline-info btn-sm" onclick="sincronizarEmpleados(<?php echo $dispositivo['dispositivo_id']; ?>)">
                                                <i class="fas fa-sync"></i> Sincronizar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarDispositivo(dispositivoId) {
            // Buscar el dispositivo en la lista y cargar datos en el formulario
            const dispositivos = <?php echo json_encode($dispositivos); ?>;
            const dispositivo = dispositivos.find(d => d.dispositivo_id === dispositivoId);

            if (dispositivo) {
                document.getElementById('dispositivo_id').value = dispositivo.dispositivo_id;
                document.getElementById('nombre').value = dispositivo.nombre;
                document.getElementById('tipo').value = dispositivo.tipo;
                document.getElementById('marca').value = dispositivo.marca || '';
                document.getElementById('modelo').value = dispositivo.modelo || '';
                document.getElementById('ip_address').value = dispositivo.ip_address || '';
                document.getElementById('puerto').value = dispositivo.puerto;
                document.getElementById('usuario').value = dispositivo.usuario || '';

                // Scroll al formulario
                document.querySelector('.card-header h5').scrollIntoView({ behavior: 'smooth' });
            }
        }

function testConexion(dispositivoId) {
            fetch(`/sistema_biometrico/biometricos/test-dispositivo/${dispositivoId}`, {
                method: 'POST'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Test exitoso para dispositivo ${dispositivoId}`);
                        location.reload();
                    } else {
                        alert(`Error en dispositivo ${dispositivoId}: ${data.error}`);
                    }
                })
                .catch(error => {
                    alert('Error al realizar el test: ' + error.message);
                });
        }

        function sincronizarEmpleados(dispositivoId) {
            if (confirm(`¿Desea sincronizar todos los empleados con el dispositivo ${dispositivoId}?`)) {
                const body = new URLSearchParams();
                body.append('_token', '<?php echo htmlspecialchars(Csrf::token()); ?>');
                fetch(`/sistema_biometrico/biometricos/sincronizar/${dispositivoId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Sincronización completada para dispositivo ${dispositivoId}`);
                    } else {
                        alert(`Error en sincronización: ${data.error}`);
                    }
                })
                .catch(error => {
                    alert('Error al sincronizar: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>
