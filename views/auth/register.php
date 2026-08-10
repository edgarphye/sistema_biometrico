<?php
require_once 'helpers/Csrf.php';

// Datos del empleado seleccionado
$selectedEmpleadoId = $_SESSION['form_data']['empleado_id'] ?? '';
$selectedEmpleadoNombre = '';
if ($selectedEmpleadoId && !empty($empleados)) {
    foreach ($empleados as $emp) {
        if ($emp['id'] == $selectedEmpleadoId) {
            $selectedEmpleadoNombre = $emp['nombre'] . ' ' . $emp['apellido'];
            break;
        }
    }
}

// Datos del jefe/mando seleccionado
$selectedJefeId = $_SESSION['form_data']['jefe_directo_id'] ?? '';
$selectedJefeNombre = '';
if ($selectedJefeId && !empty($mandos)) {
    foreach ($mandos as $mando) {
        if ($mando['id'] == $selectedJefeId) {
            $selectedJefeNombre = $mando['nombre'] . ' ' . $mando['apellido'];
            break;
        }
    }
}

$empleadosJson = json_encode($empleados ?? []);
$mandosJson = json_encode($mandos ?? []);
?>
<style<?= SecurityHelper::nonceAttr() ?>>
.search-container {
    position: relative;
}
.search-input-group .input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}
.search-input {
    border-left: none;
    border-radius: 0 10px 10px 0;
}
.search-input:focus {
    border-color: #ced4da;
    box-shadow: none;
}
.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    max-height: 250px;
    overflow-y: auto;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    z-index: 1000;
    border-radius: 0 0 10px 10px;
}
.search-results .list-group-item {
    padding: 10px 15px;
    cursor: pointer;
    border-radius: 0;
}
.search-results .list-group-item:hover {
    background-color: #f8f9fa;
}
.search-results .list-group-item:last-child {
    border-radius: 0 0 10px 10px;
}
.badge-mando {
    background-color: #6c757d;
    color: white;
    font-size: 0.7rem;
}
</style>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="text-center mb-0"><i class="fas fa-user-plus"></i> Registrar Usuario</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['form_errors'])): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Errores de validación:</h6>
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['form_errors'] as $field => $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['form_errors']); ?>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <?php echo Csrf::getHiddenInput(); ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label"><i class="fas fa-user"></i> Usuario</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['username'] ?? ''); ?>" required>
                                    <div class="form-text">Mínimo 4 caracteres</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label"><i class="fas fa-lock"></i> Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Mínimo 8 caracteres</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="rol" class="form-label"><i class="fas fa-user-tag"></i> Rol</label>
                            <select class="form-select" id="rol" name="rol">
                                <option value="empleado" <?php if (($_SESSION['form_data']['rol'] ?? '') == 'empleado') echo 'selected'; ?>>Empleado</option>
                                <option value="rh" <?php if (($_SESSION['form_data']['rol'] ?? '') == 'rh') echo 'selected'; ?>>Recursos Humanos</option>
                                <option value="gerente" <?php if (($_SESSION['form_data']['rol'] ?? '') == 'gerente') echo 'selected'; ?>>Gerente</option>
                                <option value="admin" <?php if (($_SESSION['form_data']['rol'] ?? '') == 'admin') echo 'selected'; ?>>Administrador</option>
                            </select>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-3 text-muted"><i class="fas fa-user-tag me-2"></i>Datos del Empleado</h5>
                        
                        <!-- Buscador de Empleado -->
                        <div class="mb-3">
                            <label for="empleado_search" class="form-label"><i class="fas fa-id-card text-primary"></i> Empleado Asociado</label>
                            <div class="search-container" id="empleado-search-container">
                                <div class="input-group search-input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control search-input" id="empleado_search" 
                                           placeholder="Buscar empleado por nombre, RFC o CURP..." autocomplete="off"
                                           value="<?php echo htmlspecialchars($selectedEmpleadoNombre); ?>">
                                    <input type="hidden" id="empleado_id" name="empleado_id" value="<?php echo $selectedEmpleadoId; ?>">
                                </div>
                                <div id="empleado_results" class="search-results list-group" style="display: none;"></div>
                            </div>
                            <div id="empleado_seleccionado" class="form-text">
                                <?php if ($selectedEmpleadoId): ?>
                                    <span class="text-success"><i class="fas fa-check"></i> <?php echo htmlspecialchars($selectedEmpleadoNombre); ?> (ID: <?php echo $selectedEmpleadoId; ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted">Escribe para buscar un empleado...</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Buscador de Mando/Jefe -->
                        <div class="mb-3">
                            <label for="jefe_search" class="form-label"><i class="fas fa-user-tie text-secondary"></i> Jefe Directo / Mando</label>
                            <div class="search-container" id="jefe-search-container">
                                <div class="input-group search-input-group">
                                    <span class="input-group-text"><i class="fas fa-users-cog"></i></span>
                                    <input type="text" class="form-control search-input" id="jefe_search" 
                                           placeholder="Buscar jefe o mando por nombre..." autocomplete="off"
                                           value="<?php echo htmlspecialchars($selectedJefeNombre); ?>">
                                    <input type="hidden" id="jefe_directo_id" name="jefe_directo_id" value="<?php echo $selectedJefeId; ?>">
                                </div>
                                <div id="jefe_results" class="search-results list-group" style="display: none;"></div>
                            </div>
                            <div id="jefe_seleccionado" class="form-text">
                                <?php if ($selectedJefeId): ?>
                                    <span class="text-secondary"><i class="fas fa-check"></i> <?php echo htmlspecialchars($selectedJefeNombre); ?> (ID: <?php echo $selectedJefeId; ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted">Opcional: Busca el jefe o mando superior...</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg" style="background-color: #235B4E; border-color: #235B4E;">
                                <i class="fas fa-user-plus"></i> Registrar Usuario
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo rtrim(BASE_URL, '/'); ?>/login" class="text-decoration-none">¿Ya tienes cuenta? Inicia sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script<?= SecurityHelper::nonceAttr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Validación de contraseña
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirm');

    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Las contraseñas no coinciden');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }

    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
    
    // Datos
    const empleados = <?php echo $empleadosJson; ?>;
    const mandos = <?php echo $mandosJson; ?>;
    
    // =====================
    // Buscador de EMPLEADOS
    // =====================
    const empSearchInput = document.getElementById('empleado_search');
    const empResultsDiv = document.getElementById('empleado_results');
    const empHiddenInput = document.getElementById('empleado_id');
    const empSeleccionadoDiv = document.getElementById('empleado_seleccionado');
    
    function searchEmpleados(term) {
        if (term.length < 2) {
            empResultsDiv.style.display = 'none';
            return [];
        }
        
        return empleados.filter(e => 
            (e.nombre + ' ' + e.apellido).toLowerCase().includes(term) ||
            (e.rfc || '').toLowerCase().includes(term) ||
            (e.curp || '').toLowerCase().includes(term)
        );
    }
    
    function renderEmpleadosResults(filtered) {
        if (filtered.length === 0) {
            empResultsDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron empleados</div>';
        } else {
            empResultsDiv.innerHTML = filtered.slice(0, 15).map(e => 
                '<a class="list-group-item list-group-item-action" href="#" data-id="' + e.id + '">' +
                '<strong>' + e.nombre + ' ' + e.apellido + '</strong>' +
                '<br><small class="text-muted">RFC: ' + (e.rfc || 'N/A') + ' | ' + (e.area || 'Sin área') + '</small>' +
                '</a>'
            ).join('');
        }
        empResultsDiv.style.display = 'block';
    }
    
    empSearchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        const filtered = searchEmpleados(term);
        renderEmpleadosResults(filtered);
    });
    
    empResultsDiv.addEventListener('click', function(e) {
        const item = e.target.closest('.list-group-item');
        if (item && item.dataset.id) {
            const emp = empleados.find(emp => emp.id == item.dataset.id);
            if (emp) {
                empSearchInput.value = emp.nombre + ' ' + emp.apellido;
                empHiddenInput.value = emp.id;
                empSeleccionadoDiv.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> ' + emp.nombre + ' ' + emp.apellido + ' (ID: ' + emp.id + ')</span>';
            }
            empResultsDiv.style.display = 'none';
        }
    });
    
    // =====================
    // Buscador de JEFES/MANDOS
    // =====================
    const jefeSearchInput = document.getElementById('jefe_search');
    const jefeResultsDiv = document.getElementById('jefe_results');
    const jefeHiddenInput = document.getElementById('jefe_directo_id');
    const jefeSeleccionadoDiv = document.getElementById('jefe_seleccionado');
    
    function searchMandos(term) {
        if (term.length < 2) {
            jefeResultsDiv.style.display = 'none';
            return [];
        }
        
        return mandos.filter(m => 
            (m.nombre + ' ' + m.apellido).toLowerCase().includes(term) ||
            (m.rfc || '').toLowerCase().includes(term) ||
            (m.jerarquia || '').toLowerCase().includes(term) ||
            (m.area || '').toLowerCase().includes(term)
        );
    }
    
    function renderMandosResults(filtered) {
        if (filtered.length === 0) {
            jefeResultsDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron mandos</div>';
        } else {
            jefeResultsDiv.innerHTML = filtered.slice(0, 15).map(m => 
                '<a class="list-group-item list-group-item-action" href="#" data-id="' + m.id + '">' +
                '<strong>' + m.nombre + ' ' + m.apellido + '</strong> ' +
                '<span class="badge badge-mando">' + (m.jerarquia || 'Mando') + '</span>' +
                '<br><small class="text-muted">' + (m.area || 'Sin área') + '</small>' +
                '</a>'
            ).join('');
        }
        jefeResultsDiv.style.display = 'block';
    }
    
    jefeSearchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        const filtered = searchMandos(term);
        renderMandosResults(filtered);
    });
    
    jefeResultsDiv.addEventListener('click', function(e) {
        const item = e.target.closest('.list-group-item');
        if (item && item.dataset.id) {
            const mando = mandos.find(m => m.id == item.dataset.id);
            if (mando) {
                jefeSearchInput.value = mando.nombre + ' ' + mando.apellido;
                jefeHiddenInput.value = mando.id;
                jefeSeleccionadoDiv.innerHTML = '<span class="text-secondary"><i class="fas fa-check"></i> ' + mando.nombre + ' ' + mando.apellido + ' (' + (mando.jerarquia || 'Mando') + ')</span>';
            }
            jefeResultsDiv.style.display = 'none';
        }
    });
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!empSearchInput.contains(e.target) && !empResultsDiv.contains(e.target)) {
            empResultsDiv.style.display = 'none';
        }
        if (!jefeSearchInput.contains(e.target) && !jefeResultsDiv.contains(e.target)) {
            jefeResultsDiv.style.display = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/../layout.php'; ?>
