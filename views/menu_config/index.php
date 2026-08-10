<?php
ob_start();
?>
<div class="card shadow-sm">
    <div class="card-header bg-pantone-primary text-white py-2">
        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Configurar Menú por Usuario</h5>
    </div>
    <div class="card-body p-3">
        <div class="alert alert-info mb-3 py-2">
            <i class="fas fa-info-circle me-2"></i>
            Seleccione un usuario para configurar qué opciones del menú puede ver.
        </div>
        
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label for="selectUsuario" class="form-label fw-bold small">Seleccionar Usuario:</label>
                <select class="form-select form-select-sm" id="selectUsuario" onchange="cargarConfiguracion()">
                    <option value="">-- Seleccione un usuario --</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($usuarioId == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['username']) ?> 
                            (<?= htmlspecialchars($u['nombre_completo'] ?? 'Sin nombre') ?> - <?= htmlspecialchars($u['rol']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="marcarTodos()">
                    <i class="fas fa-check-square me-1"></i> Marcar Todos
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desmarcarTodos()">
                    <i class="fas fa-square me-1"></i> Desmarcar Todos
                </button>
            </div>
        </div>
        
        <form id="formMenuConfig" method="POST" action="<?= BASE_URL ?>/menu-config/guardar">
            <input type="hidden" name="usuario_id" id="inputUsuarioId" value="<?= $usuarioId ?? '' ?>">
            
            <div class="row g-2" id="menuConfigContainer">
                <?php foreach ($menuItems as $index => $item): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="form-check">
                            <input class="form-check-input menu-item" 
                                   type="checkbox" 
                                   name="menu[<?= htmlspecialchars($item['path']) ?>]" 
                                   id="menu_<?= $index ?>"
                                   value="1"
                                   <?= !isset($configActual[$item['path']]) || $configActual[$item['path']] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="menu_<?= $index ?>">
                                <i class="fas <?= $item['icon'] ?> me-1 text-pantone-primary"></i>
                                <?= htmlspecialchars($item['label']) ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <hr class="my-3">
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-sm btn-pantone-primary" id="btnGuardar" disabled>
                    <i class="fas fa-save me-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectUsuario = document.getElementById('selectUsuario');
    const btnGuardar = document.getElementById('btnGuardar');
    
    selectUsuario.addEventListener('change', function() {
        btnGuardar.disabled = !this.value;
    });
    
    btnGuardar.disabled = !selectUsuario.value;
    
    document.getElementById('formMenuConfig').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('<?= BASE_URL ?>/menu-config/guardar', {
            method: 'POST',
            credentials: 'include',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('success', '✅ ' + data.message);
            } else {
                mostrarAlerta('danger', '❌ ' + data.message);
            }
        })
        .catch(error => {
            mostrarAlerta('danger', '❌ Error: ' + error.message);
        });
    });
});

function cargarConfiguracion() {
    const usuarioId = document.getElementById('selectUsuario').value;
    document.getElementById('inputUsuarioId').value = usuarioId;
    
    if (!usuarioId) return;
    
    fetch('<?= BASE_URL ?>/menu-config/getConfig?usuario_id=' + usuarioId, {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const config = data.config;
                document.querySelectorAll('.menu-item').forEach(checkbox => {
                    const path = checkbox.name.replace('menu[', '').replace(']', '');
                    if (config[path] !== undefined) {
                        checkbox.checked = config[path];
                    } else {
                        checkbox.checked = true;
                    }
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function marcarTodos() {
    document.querySelectorAll('.menu-item').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function desmarcarTodos() {
    document.querySelectorAll('.menu-item').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function mostrarAlerta(tipo, mensaje) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = mensaje + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>

<style>
.text-pantone-primary {
    color: #235B4E;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
