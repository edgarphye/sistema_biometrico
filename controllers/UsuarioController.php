<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/BaseController.php';

class UsuarioController extends BaseController {
    private $usuarioModel;
    private $empleadoModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        
        if (!Usuario::tienePermiso('usuarios')) {
            $_SESSION['error'] = 'No tienes permiso para gestionar usuarios.';
            $this->redirect('/dashboard');
            return;
        }
        
        $this->usuarioModel = new Usuario();
        $this->empleadoModel = new Empleado();
    }

    public function index() {
        $usuarios = $this->usuarioModel->getAllWithEmpleado();
        
        // Obtener estadísticas
        $stats = [
            'total' => count($usuarios),
            'activos' => count(array_filter($usuarios, fn($u) => $u['activo'] == 1)),
            'superadmin' => count(array_filter($usuarios, fn($u) => $u['rol'] == 'superadmin')),
            'admin' => count(array_filter($usuarios, fn($u) => $u['rol'] == 'admin')),
            'jefe' => count(array_filter($usuarios, fn($u) => $u['rol'] == 'jefe')),
            'usuario' => count(array_filter($usuarios, fn($u) => $u['rol'] == 'usuario')),
        ];
        
        $content = '
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2><i class="fas fa-users me-2"></i>Gestión de Usuarios</h2>
                    <p class="text-muted">Administra los usuarios del sistema</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-pantone-primary" onclick="abrirModalCrear()">
                        <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                    </button>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #E8D5D9 0%, #C9A4AA 100%); border-left: 4px solid #9F2241;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 fw-bold" style="color: #691C32;">' . $stats['total'] . '</h4>
                                    <small style="color: #4a1424;">Total</small>
                                </div>
                                <i class="fas fa-users fa-2x" style="color: #9F2241;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #D5E5DE 0%, #B8CCBF 100%); border-left: 4px solid #235B4E;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 fw-bold" style="color: #10312B;">' . $stats['activos'] . '</h4>
                                    <small style="color: #10312B;">Activos</small>
                                </div>
                                <i class="fas fa-user-check fa-2x" style="color: #235B4E;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #EDE4D3 0%, #DDC9A3 100%); border-left: 4px solid #BC955C;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 fw-bold" style="color: #691C32;">' . $stats['superadmin'] . '</h4>
                                    <small style="color: #691C32;">Superadmin</small>
                                </div>
                                <i class="fas fa-crown fa-2x" style="color: #BC955C;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #E8D5D5 0%, #CCB4B4 100%); border-left: 4px solid #691C32;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 fw-bold" style="color: #8E0000;">' . $stats['admin'] . '</h4>
                                    <small style="color: #691C32;">Admin</small>
                                </div>
                                <i class="fas fa-user-shield fa-2x" style="color: #691C32;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #D5E8E3 0%, #B8D4C8 100%); border-left: 4px solid #235B4E;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 fw-bold" style="color: #10312B;">' . $stats['jefe'] . '</h4>
                                    <small style="color: #10312B;">Jefes</small>
                                </div>
                                <i class="fas fa-user-tie fa-2x" style="color: #235B4E;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #EAEAEA 0%, #CCCCCC 100%); border-left: 4px solid #6F7271;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 fw-bold" style="color: #455A64;">' . $stats['usuario'] . '</h4>
                                    <small style="color: #455A64;">Usuarios</small>
                                </div>
                                <i class="fas fa-user fa-2x" style="color: #6F7271;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de Usuarios -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tabla-usuarios">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Empleado</th>
                                    <th>Estatus</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>';
        
        foreach ($usuarios as $usuario) {
            $rolBadge = match($usuario['rol']) {
                'superadmin' => '<span class="badge bg-dark">Super Admin</span>',
                'admin' => '<span class="badge bg-danger">Admin</span>',
                'jefe' => '<span class="badge bg-info">Jefe</span>',
                default => '<span class="badge bg-secondary">Usuario</span>'
            };
            
            $statusBadge = $usuario['activo'] 
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-secondary">Inactivo</span>';
            
            $content .= '
                                <tr>
                                    <td>' . $usuario['id'] . '</td>
                                    <td><strong>' . htmlspecialchars($usuario['username']) . '</strong></td>
                                    <td>' . htmlspecialchars($usuario['nombre_completo'] ?? 'N/A') . '</td>
                                    <td>' . htmlspecialchars($usuario['email'] ?? 'N/A') . '</td>
                                    <td>' . $rolBadge . '</td>
                                    <td>' . ($usuario['empleado_nombre'] ? htmlspecialchars($usuario['empleado_nombre']) : '<span class="text-muted">Sin vínculo</span>') . '</td>
                                    <td>' . $statusBadge . '</td>
                                    <td>' . (isset($usuario['ultimo_acceso']) && $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca') . '</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="abrirModalEditar(' . $usuario['id'] . ')" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-' . ($usuario['activo'] ? 'warning' : 'success') . '" 
                                                onclick="toggleUsuario(' . $usuario['id'] . ', ' . ($usuario['activo'] ? '0' : '1') . ')" 
                                                title="' . ($usuario['activo'] ? 'Desactivar' : 'Activar') . '">
                                                <i class="fas fa-' . ($usuario['activo'] ? 'ban' : 'check') . '"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>';
        }
        
        $content .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function toggleUsuario(id, activo) {
            if(confirm(activo === 1 ? "¿Activar usuario?" : "¿Desactivar usuario?")) {
                fetch("' . BASE_URL . '/usuarios/" + id + "/toggle-status", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                        "X-CSRF-Token": document.querySelector(\'meta[name="csrf-token"]\')?.content
                    },
                    body: "activo=" + activo
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                });
            }
        }
        
        function abrirModalEditar(id) {
            const url = "' . BASE_URL . '/usuarios/" + id + "/edit-modal";
            fetch(url, {
                method: "GET",
                headers: {
                    "X-CSRF-Token": document.querySelector(\'meta[name="csrf-token"]\')?.content
                }
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    document.getElementById("modal-container").innerHTML = data.modal;
                    const modal = new bootstrap.Modal(document.getElementById("editUsuarioModal"));
                    modal.show();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                alert("Error al cargar el formulario: " + err);
            });
        }
        
        function abrirModalCrear() {
            fetch("' . BASE_URL . '/usuarios/create-modal", {
                method: "GET",
                headers: {
                    "X-CSRF-Token": document.querySelector(\'meta[name="csrf-token"]\')?.content
                }
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    document.getElementById("modal-container").innerHTML = data.modal;
                    const modal = new bootstrap.Modal(document.getElementById("createUsuarioModal"));
                    modal.show();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                alert("Error al cargar el formulario: " + err);
            });
        }
        
        function guardarNuevoUsuario() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            
            if (password !== confirmPassword) {
                alert("Las contraseñas no coinciden");
                return;
            }
            
            if (password.length < 6) {
                alert("La contraseña debe tener al menos 6 caracteres");
                return;
            }
            
            const formData = new FormData(document.getElementById("form-crear-usuario"));
            
            fetch("' . BASE_URL . '/usuarios/store", {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-Token": document.querySelector(\'meta[name="csrf-token"]\')?.content
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Usuario creado correctamente");
                    location.reload();
                } else {
                    alert("Error: " + (data.message || data.error));
                }
            })
            .catch(err => {
                alert("Error al guardar: " + err);
            });
        }
        
        function guardarEdicionUsuario() {
            const password = document.getElementById("edit_password").value;
            const confirmPassword = document.getElementById("edit_password_confirm")?.value;
            
            if (password && password.length < 6) {
                alert("La contraseña debe tener al menos 6 caracteres");
                return;
            }
            
            if (password && confirmPassword && password !== confirmPassword) {
                alert("Las contraseñas no coinciden");
                return;
            }
            
            const formData = new FormData();
            formData.append("id", document.getElementById("edit_usuario_id").value);
            formData.append("username", document.getElementById("edit_username").value);
            formData.append("email", document.getElementById("edit_email").value);
            formData.append("nombre_completo", document.getElementById("edit_nombre_completo").value);
            formData.append("rol", document.getElementById("edit_rol").value);
            formData.append("empleado_id", document.getElementById("edit_empleado_id").value);
            formData.append("activo", document.getElementById("edit_activo").checked ? 1 : 0);
            formData.append("new_password", password);
            
            document.querySelectorAll(".permiso-check-modal:checked").forEach(cb => {
                formData.append("permisos[]", cb.value);
            });
            
            fetch("' . BASE_URL . '/usuarios/update", {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-Token": document.querySelector(\'meta[name="csrf-token"]\')?.content
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Usuario actualizado correctamente");
                    location.reload();
                } else {
                    alert("Error: " + (data.message || data.error));
                }
            })
            .catch(err => {
                alert("Error al guardar: " + err);
            });
        }
        
        function filterEmpleados(searchId, selectId) {
            const input = document.getElementById(searchId);
            const filter = input.value.toLowerCase();
            const select = document.getElementById(selectId);
            for (let i = 0; i < select.options.length; i++) {
                const txt = select.options[i].text.toLowerCase();
                select.options[i].style.display = txt.includes(filter) ? "" : "none";
            }
        }
        
        // Funciones para permisos del modal de edición
        function seleccionarTodoModal() {
            document.querySelectorAll(".permiso-check-modal").forEach(cb => cb.checked = true);
        }
        
        function deseleccionarTodoModal() {
            document.querySelectorAll(".permiso-check-modal").forEach(cb => cb.checked = false);
        }
        
        function aplicarRolModal() {
            const rol = document.getElementById("edit_rol").value;
            const permisosPorRol = ' . json_encode(Usuario::getPermisosPorRol('superadmin')) . ';
            const permisosRol = permisosPorRol[rol] || [];
            document.querySelectorAll(".permiso-check-modal").forEach(cb => {
                cb.checked = permisosRol.includes(cb.value);
            });
        }
        
        function actualizarPermisosModal() {
            aplicarRolModal();
        }
        
        // Funciones para permisos del modal de creación
        function seleccionarTodoCrear() {
            document.querySelectorAll(".permiso-check-crear").forEach(cb => cb.checked = true);
        }
        
        function deseleccionarTodoCrear() {
            document.querySelectorAll(".permiso-check-crear").forEach(cb => cb.checked = false);
        }
        
        function aplicarRolCrear() {
            const rol = document.getElementById("rol").value;
            const permisosPorRolCrear = ' . json_encode(Usuario::getPermisosPorRol('superadmin')) . ';
            const permisosRol = permisosPorRolCrear[rol] || [];
            document.querySelectorAll(".permiso-check-crear").forEach(cb => {
                cb.checked = permisosRol.includes(cb.value);
            });
        }
        
        function actualizarPermisosCrear() {
            aplicarRolCrear();
        }
        
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }
        </script>
        
        <div id="modal-container"></div>
        ';
        
        include __DIR__ . '/../views/layout.php';
    }

    public function getCreateModal() {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('usuarios_crear')) {
            $this->jsonResponse(['success' => false, 'message' => 'No tienes permiso para crear usuarios'], 403);
            return;
        }
        
        $empleados = $this->empleadoModel->getAll();
        
        $modal = '
        <div class="modal fade" id="createUsuarioModal" tabindex="-1" aria-labelledby="createUsuarioModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.2);">
                    <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: white; padding: 20px 25px; border: none;">
                        <h5 class="modal-title" id="createUsuarioModalLabel" style="font-weight: 600; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-user-plus"></i>Nuevo Usuario
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background: linear-gradient(180deg, #fafafa 0%, #f5f5f5 100%); padding: 25px;">
                        <form id="form-crear-usuario">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label" style="color: #9F2241; font-weight: 600;">Usuario *</label>
                                    <input type="text" class="form-control" id="username" name="username" required style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label" style="color: #9F2241; font-weight: 600;">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label" style="color: #9F2241; font-weight: 600;">Contraseña *</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label" style="color: #9F2241; font-weight: 600;">Confirmar Contraseña *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre_completo" class="form-label" style="color: #9F2241; font-weight: 600;">Nombre Completo</label>
                                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="rol" class="form-label" style="color: #9F2241; font-weight: 600;">Rol *</label>
                                    <select class="form-select" id="rol" name="rol" required onchange="actualizarPermisosCrear()" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                        <option value="">Seleccionar...</option>
                                        <option value="superadmin">Super Administrador</option>
                                        <option value="admin">Administrador</option>
                                        <option value="jefe">Jefe de Área</option>
                                        <option value="usuario">Usuario</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="empleado_id" class="form-label" style="color: #9F2241; font-weight: 600;">Empleado Asociado</label>
                                <select class="form-select" id="empleado_id" name="empleado_id" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                    <option value="">Sin empleado asociado</option>
                                    ' . implode('', array_map(fn($e) => '<option value="' . $e['id'] . '">' . htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) . '</option>', $empleados)) . '
                                </select>
                                <small class="text-muted">Vincular con un empleado del sistema</small>
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" checked style="width: 50px; height: 26px; border-radius: 13px;">
                                <label class="form-check-label" for="activo" style="font-weight: 600; margin-left: 10px; color: #235B4E;">Usuario Activo</label>
                            </div>
                            
                            <div class="mb-3" style="background: #D1E7DD; border-radius: 10px; padding: 15px; border: 1px solid #198754;">
                                <label for="password" class="form-label" style="color: #0F5132; font-weight: 600;">
                                    <i class="fas fa-key me-1"></i> Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres" style="border-radius: 12px 0 0 12px; border: 2px solid #198754;">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility(\'password\')" style="border-radius: 0 12px 12px 0; border: 2px solid #198754; border-left: none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <hr style="border-color: #DDC9A3; margin: 25px 0;">
                            <h5 style="color: #9F2241; font-weight: 600;"><i class="fas fa-shield-alt me-2"></i>Permisos del Usuario</h5>
                            <p class="text-muted small">Seleccione los permisos específicos para este usuario.</p>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm me-1" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: white; border-radius: 20px;" onclick="seleccionarTodoCrear()">
                                    <i class="fas fa-check-square me-1"></i> Todo
                                </button>
                                <button type="button" class="btn btn-sm me-1" style="background: #6F7271; color: white; border-radius: 20px;" onclick="deseleccionarTodoCrear()">
                                    <i class="fas fa-square me-1"></i> Ninguno
                                </button>
                                <button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #BC955C 0%, #8B6914 100%); color: white; border-radius: 20px;" onclick="aplicarRolCrear()">
                                    <i class="fas fa-magic me-1"></i> Aplicar Rol
                                </button>
                            </div>
                            
                            <div class="row mt-3" style="background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                                <div class="col-md-4">
                                    <h6 style="color: #9F2241; border-bottom: 2px solid #DDC9A3; padding-bottom: 8px; font-weight: 600;">Empleados</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="empleados">
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="empleados_crear">
                                        <label class="form-check-label small">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="empleados_editar">
                                        <label class="form-check-label small">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="empleados_eliminar">
                                        <label class="form-check-label small">Eliminar</label>
                                    </div>
                                    
                                    <h6 style="color: #9F2241; border-bottom: 2px solid #DDC9A3; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Asistencia</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="asistencia">
                                        <label class="form-check-label small">Ver/Registrar</label>
                                    </div>
                                    
                                    <h6 style="color: #9F2241; border-bottom: 2px solid #DDC9A3; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Validaciones</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="validaciones">
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="validaciones_aprobar">
                                        <label class="form-check-label small">Aprobar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="validaciones_rechazar">
                                        <label class="form-check-label small">Rechazar</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #235B4E; padding-bottom: 8px; font-weight: 600;">Horarios</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="horarios">
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="horarios_crear">
                                        <label class="form-check-label small">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="horarios_editar">
                                        <label class="form-check-label small">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="horarios_eliminar">
                                        <label class="form-check-label small">Eliminar</label>
                                    </div>
                                    
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #235B4E; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Ciclos</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="ciclos">
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="ciclos_crear">
                                        <label class="form-check-label small">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="ciclos_editar">
                                        <label class="form-check-label small">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="ciclos_eliminar">
                                        <label class="form-check-label small">Eliminar</label>
                                    </div>
                                    
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #235B4E; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Reportes</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="reportes">
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="reportes_exportar">
                                        <label class="form-check-label small">Exportar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="reportes_excel">
                                        <label class="form-check-label small">Reportes Excel</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h6 style="color: #BC955C; border-bottom: 2px solid #BC955C; padding-bottom: 8px; font-weight: 600;">Administración</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="biometricos">
                                        <label class="form-check-label small">Biométricos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="database">
                                        <label class="form-check-label small">Base de Datos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="catalogos">
                                        <label class="form-check-label small">Catálogos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="configuracion">
                                        <label class="form-check-label small">Configuración</label>
                                    </div>
                                    
                                    <h6 style="color: #BC955C; border-bottom: 2px solid #BC955C; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Usuarios</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="usuarios">
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="usuarios_crear">
                                        <label class="form-check-label small">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="usuarios_editar">
                                        <label class="form-check-label small">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="usuarios_eliminar">
                                        <label class="form-check-label small">Eliminar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="usuarios_permisos">
                                        <label class="form-check-label small">Permisos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" value="configurar_menu">
                                        <label class="form-check-label small">Config Menú</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer" style="background: white; padding: 15px 25px; border-top: 1px solid #eee;">
                        <button type="button" class="btn" style="background: #6F7271; color: white; border-radius: 25px; padding: 10px 25px;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: white; border-radius: 25px; padding: 10px 30px; box-shadow: 0 4px 15px rgba(151, 34, 65, 0.3);" onclick="guardarNuevoUsuario()">
                            <i class="fas fa-save me-2"></i>Crear Usuario
                        </button>
                    </div>
                </div>
            </div>
        </div>
        ';
        
        $this->jsonResponse(['success' => true, 'modal' => $modal]);
    }

    public function storeUser() {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('usuarios_crear')) {
            $this->jsonResponse(['success' => false, 'message' => 'No tienes permiso para crear usuarios'], 403);
            return;
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $email = $_POST['email'] ?? '';
        $nombre_completo = $_POST['nombre_completo'] ?? '';
        $rol = $_POST['rol'] ?? 'usuario';
        $empleado_id = $_POST['empleado_id'] ?? null;
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($username) || empty($password)) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario y contraseña son requeridos'], 400);
            return;
        }
        
        if ($this->usuarioModel->getByUsername($username)) {
            $this->jsonResponse(['success' => false, 'message' => 'El usuario ya existe'], 400);
            return;
        }
        
        $data = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'nombre_completo' => $nombre_completo,
            'rol' => $rol,
            'empleado_id' => $empleado_id ?: null,
            'activo' => $activo
        ];
        
        if ($this->usuarioModel->create($data)) {
            $nuevoUsuario = $this->usuarioModel->getByUsername($username);
            $permisos = $_POST['permisos'] ?? [];
            $this->usuarioModel->setPermisos($nuevoUsuario['id'], $permisos);
            $this->jsonResponse(['success' => true, 'message' => 'Usuario creado correctamente']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Error al crear usuario'], 500);
        }
    }

    public function create() {
        if (!Usuario::tienePermiso('usuarios_crear')) {
            $_SESSION['error'] = 'No tienes permiso para crear usuarios.';
            $this->redirect('/usuarios');
            return;
        }
        
        // Procesar formulario si es POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $email = $_POST['email'] ?? '';
            $nombre_completo = $_POST['nombre_completo'] ?? '';
            $rol = $_POST['rol'] ?? 'usuario';
            $empleado_id = $_POST['empleado_id'] ?? null;
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            // Validaciones
            if (empty($username) || empty($password)) {
                $_SESSION['error'] = 'Usuario y contraseña son requeridos';
                $this->redirect('/usuarios/create');
                return;
            }
            
            // Verificar si el usuario ya existe
            if ($this->usuarioModel->getByUsername($username)) {
                $_SESSION['error'] = 'El usuario ya existe';
                $this->redirect('/usuarios/create');
                return;
            }
            
            // Crear usuario
            $data = [
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'nombre_completo' => $nombre_completo,
                'rol' => $rol,
                'empleado_id' => $empleado_id ?: null,
                'activo' => $activo
            ];
            
            $usuarioId = null;
            if ($this->usuarioModel->create($data)) {
                $usuarioId = $this->usuarioModel->getByUsername($username)['id'];
                $permisos = $_POST['permisos'] ?? [];
                $this->usuarioModel->setPermisos($usuarioId, $permisos);
                $_SESSION['success'] = 'Usuario creado correctamente';
                $this->redirect('/usuarios');
                return;
            } else {
                $_SESSION['error'] = 'Error al crear usuario';
            }
        }
        
        $empleados = $this->empleadoModel->getAll();
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        
        $content = '
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-pantone-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Nuevo Usuario</h4>
                        </div>
                        <div class="card-body">
                            ' . ($error ? '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>' : '') . '
                            
                            <form method="POST" action="' . BASE_URL . '/usuarios/create" id="form-usuario">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Usuario *</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Contraseña *</label>
                                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                        <small class="text-muted">Mínimo 6 caracteres</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="rol" class="form-label">Rol *</label>
                                        <select class="form-select" id="rol" name="rol" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="superadmin">Super Administrador</option>
                                            <option value="admin">Administrador</option>
                                            <option value="jefe">Jefe de Área</option>
                                            <option value="usuario">Usuario</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="empleado_id" class="form-label">Empleado Asociado</label>
                                    <select class="form-select" id="empleado_id" name="empleado_id">
                                        <option value="">Sin empleado asociado</option>
                                        ' . implode('', array_map(fn($e) => '<option value="' . $e['id'] . '">' . htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) . '</option>', $empleados)) . '
                                    </select>
                                    <small class="text-muted">Vincular con un empleado del sistema</small>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" checked>
                                    <label class="form-check-label" for="activo">Usuario Activo</label>
                                </div>
                                
                                <hr>
                                <h5><i class="fas fa-shield-alt me-2"></i>Permisos del Usuario</h5>
                                <p class="text-muted">Seleccione los permisos específicos para este usuario.</p>
                                
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="seleccionarTodo()">
                                        <i class="fas fa-check-square me-1"></i> Seleccionar todo
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodo()">
                                        <i class="fas fa-square me-1"></i> Deseleccionar todo
                                    </button>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <h6 class="border-bottom pb-2 text-primary">Empleados</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="empleados" id="perm_empleados">
                                            <label class="form-check-label" for="perm_empleados">Ver Empleados</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="empleados_crear" id="perm_empleados_crear">
                                            <label class="form-check-label" for="perm_empleados_crear">Crear</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="empleados_editar" id="perm_empleados_editar">
                                            <label class="form-check-label" for="perm_empleados_editar">Editar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="empleados_eliminar" id="perm_empleados_eliminar">
                                            <label class="form-check-label" for="perm_empleados_eliminar">Eliminar</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Asistencia</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="asistencia" id="perm_asistencia">
                                            <label class="form-check-label" for="perm_asistencia">Ver/Registrar</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Validaciones</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="validaciones" id="perm_validaciones">
                                            <label class="form-check-label" for="perm_validaciones">Ver</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="validaciones_aprobar" id="perm_validaciones_aprobar">
                                            <label class="form-check-label" for="perm_validaciones_aprobar">Aprobar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="validaciones_rechazar" id="perm_validaciones_rechazar">
                                            <label class="form-check-label" for="perm_validaciones_rechazar">Rechazar</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Reportes</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="reportes" id="perm_reportes">
                                            <label class="form-check-label" for="perm_reportes">Ver</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="reportes_exportar" id="perm_reportes_exportar">
                                            <label class="form-check-label" for="perm_reportes_exportar">Exportar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="reportes_excel" id="perm_reportes_excel">
                                            <label class="form-check-label" for="perm_reportes_excel">Reportes Excel</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="analisis_predictivo" id="perm_analisis_predictivo">
                                            <label class="form-check-label" for="perm_analisis_predictivo">Análisis Predictivo</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <h6 class="border-bottom pb-2 text-primary">Horarios</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="horarios" id="perm_horarios">
                                            <label class="form-check-label" for="perm_horarios">Ver</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="horarios_crear" id="perm_horarios_crear">
                                            <label class="form-check-label" for="perm_horarios_crear">Crear</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="horarios_editar" id="perm_horarios_editar">
                                            <label class="form-check-label" for="perm_horarios_editar">Editar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="horarios_eliminar" id="perm_horarios_eliminar">
                                            <label class="form-check-label" for="perm_horarios_eliminar">Eliminar</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Ciclos</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="ciclos" id="perm_ciclos">
                                            <label class="form-check-label" for="perm_ciclos">Ver</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="ciclos_crear" id="perm_ciclos_crear">
                                            <label class="form-check-label" for="perm_ciclos_crear">Crear</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="ciclos_editar" id="perm_ciclos_editar">
                                            <label class="form-check-label" for="perm_ciclos_editar">Editar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="ciclos_eliminar" id="perm_ciclos_eliminar">
                                            <label class="form-check-label" for="perm_ciclos_eliminar">Eliminar</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <h6 class="border-bottom pb-2 text-primary">Administración</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="biometricos" id="perm_biometricos">
                                            <label class="form-check-label" for="perm_biometricos">Biométricos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="database" id="perm_database">
                                            <label class="form-check-label" for="perm_database">Base de Datos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="catalogos" id="perm_catalogos">
                                            <label class="form-check-label" for="perm_catalogos">Catálogos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="configuracion" id="perm_configuracion">
                                            <label class="form-check-label" for="perm_configuracion">Configuración</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Usuarios</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios" id="perm_usuarios">
                                            <label class="form-check-label" for="perm_usuarios">Ver</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios_crear" id="perm_usuarios_crear">
                                            <label class="form-check-label" for="perm_usuarios_crear">Crear</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios_editar" id="perm_usuarios_editar">
                                            <label class="form-check-label" for="perm_usuarios_editar">Editar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios_eliminar" id="perm_usuarios_eliminar">
                                            <label class="form-check-label" for="perm_usuarios_eliminar">Eliminar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios_permisos" id="perm_usuarios_permisos">
                                            <label class="form-check-label" for="perm_usuarios_permisos">Permisos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="configurar_menu" id="perm_configurar_menu">
                                            <label class="form-check-label" for="perm_configurar_menu">Configurar Menú</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="' . BASE_URL . '/usuarios" class="btn btn-secondary me-md-2">Cancelar</a>
                                    <button type="submit" class="btn btn-pantone-primary">
                                        <i class="fas fa-save me-2"></i>Guardar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function seleccionarTodo() {
            document.querySelectorAll(".permiso-check").forEach(cb => cb.checked = true);
        }
        
        function deseleccionarTodo() {
            document.querySelectorAll(".permiso-check").forEach(cb => cb.checked = false);
        }
        
        document.getElementById("form-usuario").addEventListener("submit", function(e) {
            const password = document.getElementById("password").value;
            const confirm = document.getElementById("confirm_password").value;
            if(password !== confirm) {
                e.preventDefault();
                alert("Las contraseñas no coinciden");
            }
        });
        </script>';
        
        include __DIR__ . '/../views/layout.php';
    }

    public function edit() {
        if (!Usuario::tienePermiso('usuarios_editar')) {
            $_SESSION['error'] = 'No tienes permiso para editar usuarios.';
            $this->redirect('/usuarios');
            return;
        }
        
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        
        // Si no viene por GET/POST, intentar obtenerlo de los parámetros de la ruta
        if (!$id && func_num_args() > 0) {
            $id = func_get_arg(0);
        }
        
        if (!$id) {
            $this->redirect('/usuarios');
            return;
        }
        
        $usuario = $this->usuarioModel->getById($id);
        
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            $this->redirect('/usuarios');
            return;
        }
        
        // Procesar formulario si es POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $nombre_completo = $_POST['nombre_completo'] ?? '';
            $rol = $_POST['rol'] ?? 'usuario';
            $empleado_id = $_POST['empleado_id'] ?? null;
            $activo = isset($_POST['activo']) ? 1 : 0;
            $new_password = $_POST['new_password'] ?? '';
            
            // Validaciones
            if (empty($username)) {
                $_SESSION['error'] = 'El usuario es requerido';
                $this->redirect('/usuarios/' . $id . '/edit');
                return;
            }
            
            // Verificar si el usuario ya existe (otro)
            $existingUser = $this->usuarioModel->getByUsername($username);
            if ($existingUser && $existingUser['id'] != $id) {
                $_SESSION['error'] = 'El usuario ya existe';
                $this->redirect('/usuarios/' . $id . '/edit');
                return;
            }
            
            // Actualizar datos
            $data = [
                'username' => $username,
                'email' => $email,
                'nombre_completo' => $nombre_completo,
                'rol' => $rol,
                'empleado_id' => $empleado_id ?: null,
                'activo' => $activo
            ];
            
            // Solo actualizar contraseña si se proporcionó una nueva
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres';
                    $this->redirect('/usuarios/' . $id . '/edit');
                    return;
                }
                $this->usuarioModel->updatePassword($id, $new_password);
            }
            
            if ($this->usuarioModel->update($id, $data)) {
                $permisos = $_POST['permisos'] ?? [];
                $this->usuarioModel->setPermisos($id, $permisos);
                $_SESSION['success'] = 'Usuario actualizado correctamente';
                $this->redirect('/usuarios');
                return;
            } else {
                $_SESSION['error'] = 'Error al actualizar usuario';
            }
        }
        
        $empleados = $this->empleadoModel->getAll();
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        
        $permisosUsuario = $this->usuarioModel->getPermisos($id);
        $todosPermisos = Usuario::getTodosPermisos();
        $permisosPorRol = Usuario::getPermisosPorRol($usuario['rol']);
        
        $content = '
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card shadow">
                        <div class="card-header bg-pantone-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Editar Usuario</h4>
                        </div>
                        <div class="card-body">
                            ' . ($error ? '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>' : '') . '
                            
                            <form method="POST" action="' . BASE_URL . '/usuarios/edit" id="form-usuario">
                                <input type="hidden" name="id" value="' . $usuario['id'] . '">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Usuario *</label>
                                        <input type="text" class="form-control" id="username" name="username" value="' . htmlspecialchars($usuario['username']) . '" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="' . htmlspecialchars($usuario['email'] ?? '') . '">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" value="' . htmlspecialchars($usuario['nombre_completo'] ?? '') . '">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="rol" class="form-label">Rol *</label>
                                        <select class="form-select" id="rol" name="rol" required onchange="actualizarPermisosSugeridos()">
                                            <option value="superadmin" ' . ($usuario['rol'] == 'superadmin' ? 'selected' : '') . '>Super Administrador</option>
                                            <option value="admin" ' . ($usuario['rol'] == 'admin' ? 'selected' : '') . '>Administrador</option>
                                            <option value="jefe" ' . ($usuario['rol'] == 'jefe' ? 'selected' : '') . '>Jefe de Área</option>
                                            <option value="usuario" ' . ($usuario['rol'] == 'usuario' ? 'selected' : '') . '>Usuario</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="empleado_id" class="form-label">Empleado Asociado</label>
                                    <input type="text" class="form-control mb-2" id="search_empleado" placeholder="Buscar empleado..." onkeyup="filterEmpleados(\'search_empleado\', \'empleado_id\')">
                                    <select class="form-select" id="empleado_id" name="empleado_id" size="6">
                                        <option value="">Sin empleado asociado</option>
                                        ' . implode('', array_map(fn($e) => '<option value="' . $e['id'] . '" ' . ($usuario['empleado_id'] == $e['id'] ? 'selected' : '') . '>' . htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) . '</option>', $empleados)) . '
                                    </select>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" ' . ($usuario['activo'] ? 'checked' : '') . '>
                                    <label class="form-check-label" for="activo">Usuario Activo</label>
                                </div>
                                
                                <hr>
                                <h5><i class="fas fa-shield-alt me-2"></i>Permisos del Usuario</h5>
                                <p class="text-muted">Seleccione los permisos específicos para este usuario. Use los botones para seleccionar todo o aplicar permisos del rol.</p>
                                
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="seleccionarTodo()">
                                        <i class="fas fa-check-square me-1"></i> Seleccionar todo
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="deseleccionarTodo()">
                                        <i class="fas fa-square me-1"></i> Deseleccionar todo
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="aplicarRol()">
                                        <i class="fas fa-magic me-1"></i> Aplicar permisos del rol
                                    </button>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <h6 class="border-bottom pb-2 text-primary">Empleados</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="empleados" id="perm_empleados" ' . (in_array('empleados', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_empleados">Ver Empleados</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="empleados_crear" id="perm_empleados_crear" ' . (in_array('empleados_crear', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_empleados_crear">Crear Empleados</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="empleados_editar" id="perm_empleados_editar" ' . (in_array('empleados_editar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_empleados_editar">Editar Empleados</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="empleados_eliminar" id="perm_empleados_eliminar" ' . (in_array('empleados_eliminar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_empleados_eliminar">Eliminar Empleados</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Asistencia</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="asistencia" id="perm_asistencia" ' . (in_array('asistencia', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_asistencia">Ver/Registrar Asistencia</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Validaciones</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="validaciones" id="perm_validaciones" ' . (in_array('validaciones', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_validaciones">Ver Validaciones</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="validaciones_aprobar" id="perm_validaciones_aprobar" ' . (in_array('validaciones_aprobar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_validaciones_aprobar">Aprobar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="validaciones_rechazar" id="perm_validaciones_rechazar" ' . (in_array('validaciones_rechazar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_validaciones_rechazar">Rechazar</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <h6 class="border-bottom pb-2 text-primary">Horarios</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="horarios" id="perm_horarios" ' . (in_array('horarios', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_horarios">Ver Horarios</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="horarios_crear" id="perm_horarios_crear" ' . (in_array('horarios_crear', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_horarios_crear">Crear Horarios</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="horarios_editar" id="perm_horarios_editar" ' . (in_array('horarios_editar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_horarios_editar">Editar Horarios</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="horarios_eliminar" id="perm_horarios_eliminar" ' . (in_array('horarios_eliminar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_horarios_eliminar">Eliminar Horarios</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Ciclos</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="ciclos" id="perm_ciclos" ' . (in_array('ciclos', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_ciclos">Ver Ciclos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="ciclos_crear" id="perm_ciclos_crear" ' . (in_array('ciclos_crear', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_ciclos_crear">Crear Ciclos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="ciclos_editar" id="perm_ciclos_editar" ' . (in_array('ciclos_editar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_ciclos_editar">Editar Ciclos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="ciclos_eliminar" id="perm_ciclos_eliminar" ' . (in_array('ciclos_eliminar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_ciclos_eliminar">Eliminar Ciclos</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Reportes</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="reportes" id="perm_reportes" ' . (in_array('reportes', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_reportes">Ver Reportes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="reportes_exportar" id="perm_reportes_exportar" ' . (in_array('reportes_exportar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_reportes_exportar">Exportar Reportes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="reportes_excel" id="perm_reportes_excel" ' . (in_array('reportes_excel', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_reportes_excel">Reportes Excel</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="analisis_predictivo" id="perm_analisis_predictivo" ' . (in_array('analisis_predictivo', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_analisis_predictivo">Análisis Predictivo</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <h6 class="border-bottom pb-2 text-primary">Administración</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="biometricos" id="perm_biometricos" ' . (in_array('biometricos', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_biometricos">Dispositivos Biométricos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="database" id="perm_database" ' . (in_array('database', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_database">Base de Datos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="catalogos" id="perm_catalogos" ' . (in_array('catalogos', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_catalogos">Catálogos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="configuracion" id="perm_configuracion" ' . (in_array('configuracion', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_configuracion">Configuración</label>
                                        </div>
                                        
                                        <h6 class="border-bottom pb-2 text-primary mt-3">Usuarios</h6>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios" id="perm_usuarios" ' . (in_array('usuarios', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_usuarios">Ver Usuarios</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios_crear" id="perm_usuarios_crear" ' . (in_array('usuarios_crear', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_usuarios_crear">Crear Usuarios</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios_editar" id="perm_usuarios_editar" ' . (in_array('usuarios_editar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_usuarios_editar">Editar Usuarios</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios_eliminar" id="perm_usuarios_eliminar" ' . (in_array('usuarios_eliminar', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_usuarios_eliminar">Eliminar Usuarios</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="usuarios_permisos" id="perm_usuarios_permisos" ' . (in_array('usuarios_permisos', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_usuarios_permisos">Administrar Permisos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="configurar_menu" id="perm_configurar_menu" ' . (in_array('configurar_menu', $permisosUsuario) ? 'checked' : '') . '>
                                            <label class="form-check-label" for="perm_configurar_menu">Configurar Menú</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                <h5><i class="fas fa-list me-2"></i>Configuración del Menú del Usuario</h5>
                                <p class="text-muted small mb-2">Seleccione las opciones del menú que este usuario podrá ver. Si no se configura, se usarán los permisos por defecto del rol.</p>
                                
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="seleccionarTodoMenu()">
                                        <i class="fas fa-check-square me-1"></i> Marcar Todos
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodoMenu()">
                                        <i class="fas fa-square me-1"></i> Desmarcar Todos
                                    </button>
                                </div>
                                
                                <div class="row">
                                    ' . $this->generarCheckboxesMenuUsuario($id) . '
                                </div>
                                
                                <hr>
                                <h5>Cambiar Contraseña</h5>
                                <p class="text-muted">Dejar en blanco para mantener la contraseña actual</p>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="' . BASE_URL . '/usuarios" class="btn btn-secondary me-md-2">Cancelar</a>
                                    <button type="button" class="btn btn-pantone-primary" onclick="guardarUsuarioCompleto(' . $id . ')">
                                        <i class="fas fa-save me-2"></i>Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        const permisosPorRol = ' . json_encode(Usuario::getPermisosPorRol('superadmin')) . ';
        
        function seleccionarTodo() {
            document.querySelectorAll(".permiso-check-modal").forEach(cb => cb.checked = true);
        }
        
        function deseleccionarTodo() {
            document.querySelectorAll(".permiso-check-modal").forEach(cb => cb.checked = false);
        }
        
        function aplicarRol() {
            const rol = document.getElementById("edit_rol").value;
            const permisosRol = permisosPorRol[rol] || [];
            document.querySelectorAll(".permiso-check-modal").forEach(cb => {
                cb.checked = permisosRol.includes(cb.value);
            });
        }
        
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }
        
        function seleccionarTodoMenu() {
            document.querySelectorAll(".menu-usuario-check").forEach(cb => cb.checked = true);
        }
        
        function deseleccionarTodoMenu() {
            document.querySelectorAll(".menu-usuario-check").forEach(cb => cb.checked = false);
        }
        
        async function guardarUsuarioCompleto(usuarioId) {
            const formData = new FormData(document.getElementById("form-usuario"));
            
            try {
                const response = await fetch("' . BASE_URL . '/usuarios/update", {
                    method: "POST",
                    credentials: "include",
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    const menuData = new FormData();
                    menuData.append("usuario_id", usuarioId);
                    document.querySelectorAll(".menu-usuario-check").forEach(cb => {
                        menuData.append("menu_usuario[" + cb.name.replace("menu_usuario[", "").replace("]", "") + "]", cb.checked ? "1" : "0");
                    });
                    
                    const menuResponse = await fetch("' . BASE_URL . '/usuarios/guardar-menu", {
                        method: "POST",
                        credentials: "include",
                        body: menuData
                    });
                    const menuResult = await menuResponse.json();
                    
                    if (menuResult.success) {
                        mostrarAlerta("success", "✅ Usuario y configuración de menú guardados correctamente");
                        setTimeout(() => window.location.href = "' . BASE_URL . '/usuarios", 1500);
                    } else {
                        mostrarAlerta("warning", "⚠️ Usuario guardado, pero hubo error en menú: " + menuResult.message);
                    }
                } else {
                    mostrarAlerta("danger", "❌ " + result.message);
                }
            } catch (error) {
                mostrarAlerta("danger", "❌ Error: " + error.message);
            }
        }
        
        function mostrarAlerta(tipo, mensaje) {
            const alertDiv = document.createElement("div");
            alertDiv.className = "alert alert-" + tipo + " alert-dismissible fade show position-fixed top-0 end-0 m-3";
            alertDiv.style.zIndex = "9999";
            alertDiv.innerHTML = mensaje + "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>";
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }
        
        function filterEmpleados(searchId, selectId) {
            const input = document.getElementById(searchId);
            const filter = input.value.toLowerCase();
            const select = document.getElementById(selectId);
            for (let i = 0; i < select.options.length; i++) {
                const txt = select.options[i].text.toLowerCase();
                select.options[i].style.display = txt.includes(filter) ? "" : "none";
            }
        }
        </script>';
        
        include __DIR__ . '/../views/layout.php';
    }

    public function getEditModal() {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('usuarios_editar')) {
            $this->jsonResponse(['success' => false, 'message' => 'No tienes permiso para editar usuarios'], 403);
            return;
        }
        
        $id = null;
        
        // Obtener ID de diferentes fuentes
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
        } elseif (isset($_POST['id'])) {
            $id = $_POST['id'];
        } elseif (func_num_args() > 0) {
            $id = func_get_arg(0);
        }
        
        // Depuración
        error_log("getEditModal - ID: " . $id . " - Args: " . print_r(func_get_args(), true));
        
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID requerido. Args: ' . print_r(func_get_args(), true)], 400);
            return;
        }
        
        $usuario = $this->usuarioModel->getById($id);
        
        if (!$usuario) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            return;
        }
        
        $empleados = $this->empleadoModel->getAll();
        $permisosUsuario = $this->usuarioModel->getPermisos($id);
        
        $modal = '
        <div class="modal fade" id="editUsuarioModal" tabindex="-1" aria-labelledby="editUsuarioModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.2);">
                    <div class="modal-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: white; padding: 20px 25px; border: none;">
                        <h5 class="modal-title" id="editUsuarioModalLabel" style="font-weight: 600; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-user-edit"></i>Editar Usuario
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background: linear-gradient(180deg, #fafafa 0%, #f5f5f5 100%); padding: 25px;">
                        <form id="form-editar-usuario">
                            <input type="hidden" name="id" id="edit_usuario_id" value="' . $usuario['id'] . '">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_username" class="form-label" style="color: #235B4E; font-weight: 600;">Usuario *</label>
                                    <input type="text" class="form-control" id="edit_username" name="username" value="' . htmlspecialchars($usuario['username']) . '" required style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_email" class="form-label" style="color: #235B4E; font-weight: 600;">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" value="' . htmlspecialchars($usuario['email'] ?? '') . '" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_nombre_completo" class="form-label" style="color: #235B4E; font-weight: 600;">Nombre Completo</label>
                                    <input type="text" class="form-control" id="edit_nombre_completo" name="nombre_completo" value="' . htmlspecialchars($usuario['nombre_completo'] ?? '') . '" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_rol" class="form-label" style="color: #235B4E; font-weight: 600;">Rol *</label>
                                    <select class="form-select" id="edit_rol" name="rol" required onchange="actualizarPermisosModal()" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px;">
                                        <option value="superadmin" ' . ($usuario['rol'] == 'superadmin' ? 'selected' : '') . '>Super Administrador</option>
                                        <option value="admin" ' . ($usuario['rol'] == 'admin' ? 'selected' : '') . '>Administrador</option>
                                        <option value="jefe" ' . ($usuario['rol'] == 'jefe' ? 'selected' : '') . '>Jefe de Área</option>
                                        <option value="usuario" ' . ($usuario['rol'] == 'usuario' ? 'selected' : '') . '>Usuario</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_empleado_id" class="form-label" style="color: #235B4E; font-weight: 600;">Empleado Asociado</label>
                                <div style="position: relative;">
                                    <input type="text" class="form-control mb-2" id="search_edit_empleado" placeholder="Buscar empleado..." onkeyup="filterEmpleados(\'search_edit_empleado\', \'edit_empleado_id\')" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 15px; font-size: 0.9rem;">
                                    <select class="form-select" id="edit_empleado_id" name="empleado_id" size="5" style="border-radius: 12px; border: 2px solid #e0e0e0; padding: 8px; font-size: 0.85rem;">
                                        <option value="">Sin empleado asociado</option>
                                        ' . implode('', array_map(fn($e) => '<option value="' . $e['id'] . '" ' . ($usuario['empleado_id'] == $e['id'] ? 'selected' : '') . '>' . htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) . '</option>', $empleados)) . '
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="edit_activo" name="activo" value="1" ' . ($usuario['activo'] ? 'checked' : '') . ' style="width: 50px; height: 26px; border-radius: 13px;">
                                <label class="form-check-label" for="edit_activo" style="font-weight: 600; margin-left: 10px; color: #235B4E;">Usuario Activo</label>
                            </div>
                            
                            <div class="mb-3" style="background: #FFF3CD; border-radius: 10px; padding: 15px; border: 1px solid #FFCA2C;">
                                <label for="edit_password" class="form-label" style="color: #856404; font-weight: 600;">
                                    <i class="fas fa-key me-1"></i> Nueva Contraseña
                                </label>
                                <div class="input-group mb-2">
                                    <input type="password" class="form-control" id="edit_password" name="new_password" placeholder="Dejar en blanco para mantener actual" style="border-radius: 12px 0 0 12px; border: 2px solid #FFCA2C;">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility(\'edit_password\')" style="border-radius: 0 12px 12px 0; border: 2px solid #FFCA2C; border-left: none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <label for="edit_password_confirm" class="form-label" style="color: #856404; font-weight: 600;">
                                    <i class="fas fa-key me-1"></i> Confirmar Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="edit_password_confirm" name="confirm_password" placeholder="Confirmar nueva contraseña" style="border-radius: 12px 0 0 12px; border: 2px solid #FFCA2C;">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility(\'edit_password_confirm\')" style="border-radius: 0 12px 12px 0; border: 2px solid #FFCA2C; border-left: none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Mínimo 6 caracteres. Dejar en blanco para mantener la contraseña actual.</small>
                            </div>
                            
                            <hr style="border-color: #DDC9A3; margin: 25px 0;">
                            <h5 style="color: #235B4E; font-weight: 600;"><i class="fas fa-shield-alt me-2"></i>Permisos del Usuario</h5>
                            <p class="text-muted small">Seleccione los permisos específicos para este usuario.</p>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm me-1" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: white; border-radius: 20px;" onclick="seleccionarTodoModal()">
                                    <i class="fas fa-check-square me-1"></i> Todo
                                </button>
                                <button type="button" class="btn btn-sm me-1" style="background: #6F7271; color: white; border-radius: 20px;" onclick="deseleccionarTodoModal()">
                                    <i class="fas fa-square me-1"></i> Ninguno
                                </button>
                                <button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #BC955C 0%, #8B6914 100%); color: white; border-radius: 20px;" onclick="aplicarRolModal()">
                                    <i class="fas fa-magic me-1"></i> Aplicar Rol
                                </button>
                            </div>
                            
                            <div class="row mt-3" style="background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                                <div class="col-md-4">
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #DDC9A3; padding-bottom: 8px; font-weight: 600;">Empleados</h6>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="empleados" ' . (in_array('empleados', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="empleados_crear" ' . (in_array('empleados_crear', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="empleados_editar" ' . (in_array('empleados_editar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="empleados_eliminar" ' . (in_array('empleados_eliminar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Eliminar</label>
                                    </div>
                                    
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #DDC9A3; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Asistencia</h6>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="asistencia" ' . (in_array('asistencia', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Ver/Registrar</label>
                                    </div>
                                    
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #DDC9A3; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Validaciones</h6>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="validaciones" ' . (in_array('validaciones', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="validaciones_aprobar" ' . (in_array('validaciones_aprobar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Aprobar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="validaciones_rechazar" ' . (in_array('validaciones_rechazar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Rechazar</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #235B4E; padding-bottom: 8px; font-weight: 600;">Horarios</h6>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="horarios" ' . (in_array('horarios', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="horarios_crear" ' . (in_array('horarios_crear', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="horarios_editar" ' . (in_array('horarios_editar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="horarios_eliminar" ' . (in_array('horarios_eliminar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Eliminar</label>
                                    </div>
                                    
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #235B4E; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Ciclos</h6>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="ciclos" ' . (in_array('ciclos', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="ciclos_crear" ' . (in_array('ciclos_crear', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="ciclos_editar" ' . (in_array('ciclos_editar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="ciclos_eliminar" ' . (in_array('ciclos_eliminar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Eliminar</label>
                                    </div>
                                    
                                    <h6 style="color: #235B4E; border-bottom: 2px solid #235B4E; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Reportes</h6>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="reportes" ' . (in_array('reportes', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="reportes_exportar" ' . (in_array('reportes_exportar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Exportar</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h6 style="color: #BC955C; border-bottom: 2px solid #BC955C; padding-bottom: 8px; font-weight: 600;">Administración</h6>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="biometricos" ' . (in_array('biometricos', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Biométricos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="database" ' . (in_array('database', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Base de Datos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="catalogos" ' . (in_array('catalogos', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Catálogos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="configuracion" ' . (in_array('configuracion', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Configuración</label>
                                    </div>
                                    
                                    <h6 style="color: #BC955C; border-bottom: 2px solid #BC955C; padding-bottom: 8px; font-weight: 600; margin-top: 15px;">Usuarios</h6>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="usuarios" ' . (in_array('usuarios', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Ver</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="usuarios_crear" ' . (in_array('usuarios_crear', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Crear</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="usuarios_editar" ' . (in_array('usuarios_editar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Editar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="usuarios_eliminar" ' . (in_array('usuarios_eliminar', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Eliminar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="usuarios_permisos" ' . (in_array('usuarios_permisos', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Permisos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input permiso-check-modal" type="checkbox" name="permisos[]" value="configurar_menu" ' . (in_array('configurar_menu', $permisosUsuario) ? 'checked' : '') . '>
                                        <label class="form-check-label small">Config Menú</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer" style="background: white; padding: 15px 25px; border-top: 1px solid #eee;">
                        <button type="button" class="btn" style="background: #6F7271; color: white; border-radius: 25px; padding: 10px 25px;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: white; border-radius: 25px; padding: 10px 30px; box-shadow: 0 4px 15px rgba(35, 91, 78, 0.3);" onclick="guardarEdicionUsuario()">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
        ';
        
        $this->jsonResponse(['success' => true, 'modal' => $modal]);
    }

    public function update() {
        $id = $_POST['id'] ?? null;
        $activo = $_POST['activo'] ?? null;
        
        if (!$id || $activo === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos requeridos'], 400);
            return;
        }
        
        // No permitir desactivar al propio usuario
        if ($id == $_SESSION['user_id'] && $activo == 0) {
            $this->jsonResponse(['success' => false, 'message' => 'No puedes desactivarte a ti mismo'], 400);
            return;
        }
        
        if ($this->usuarioModel->toggleStatus($id, $activo)) {
            $this->jsonResponse(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar estado'], 500);
        }
    }

    public function updateUser() {
        try {
            require_once 'models/Usuario.php';
            if (!Usuario::tienePermiso('usuarios_editar')) {
                $this->jsonResponse(['success' => false, 'message' => 'No tienes permiso para editar usuarios'], 403);
                return;
            }
            
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
                return;
            }
            
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $nombre_completo = $_POST['nombre_completo'] ?? '';
            $rol = $_POST['rol'] ?? 'usuario';
            $empleado_id = $_POST['empleado_id'] ?? null;
            $activo = isset($_POST['activo']) ? 1 : 0;
            $new_password = $_POST['new_password'] ?? '';
            
            if (empty($username)) {
                $this->jsonResponse(['success' => false, 'message' => 'El usuario es requerido'], 400);
                return;
            }
            
            $existingUser = $this->usuarioModel->getByUsername($username);
            if ($existingUser && $existingUser['id'] != $id) {
                $this->jsonResponse(['success' => false, 'message' => 'El usuario ya existe'], 400);
                return;
            }
            
            $data = [
                'username' => $username,
                'email' => $email,
                'nombre_completo' => $nombre_completo,
                'rol' => $rol,
                'empleado_id' => $empleado_id ?: null,
                'activo' => $activo
            ];
            
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $this->jsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
                    return;
                }
                $this->usuarioModel->updatePassword($id, $new_password);
            }
            
            if ($this->usuarioModel->update($id, $data)) {
                $permisos = $_POST['permisos'] ?? [];
                $this->usuarioModel->setPermisos($id, $permisos);
                $this->jsonResponse(['success' => true, 'message' => 'Usuario actualizado correctamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar usuario'], 500);
            }
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'updateUser', 'user_id' => $id ?? null]);
            error_log("Error en updateUser: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function changePassword() {
        $id = $_POST['id'] ?? null;
        $newPassword = $_POST['new_password'] ?? null;
        
        if (!$id || !$newPassword) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos requeridos'], 400);
            return;
        }
        
        if (strlen($newPassword) < 6) {
            $this->jsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
            return;
        }
        
        if ($this->usuarioModel->updatePassword($id, $newPassword)) {
            $this->jsonResponse(['success' => true, 'message' => 'Contraseña actualizada']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar contraseña'], 500);
        }
    }
    
    private function generarCheckboxesMenuUsuario($usuarioId) {
        $menuItems = [
            ['path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
            ['path' => '/', 'label' => 'Inicio', 'icon' => 'fa-home'],
            ['path' => '/empleados', 'label' => 'Empleados', 'icon' => 'fa-users'],
            ['path' => '/asistencia', 'label' => 'Asistencia', 'icon' => 'fa-clock'],
            ['path' => '/marcaciones', 'label' => 'Marcaciones', 'icon' => 'fa-stopwatch'],
            ['path' => '/mis-validaciones', 'label' => 'Mis Validaciones', 'icon' => 'fa-check-double'],
            ['path' => '/horarios', 'label' => 'Horarios', 'icon' => 'fa-calendar-alt'],
            ['path' => '/ciclos', 'label' => 'Ciclos', 'icon' => 'fa-sync'],
            ['path' => '/validaciones', 'label' => 'Validaciones', 'icon' => 'fa-user-check'],
            ['path' => '/reportes/excel', 'label' => 'Reportes Excel', 'icon' => 'fa-file-excel'],
            ['path' => '/resumen-justificaciones', 'label' => 'Resumen Justificaciones', 'icon' => 'fa-clipboard-check'],
            ['path' => '/analisis-predictivo', 'label' => 'Análisis Predictivo', 'icon' => 'fa-brain'],
            ['path' => '/agent-ia', 'label' => 'Agent IA', 'icon' => 'fa-network-wired'],
            ['path' => '/ai', 'label' => 'AI Dashboard', 'icon' => 'fa-robot'],
            ['path' => '/biometricos', 'label' => 'Dispositivos Biométricos', 'icon' => 'fa-fingerprint'],
            ['path' => '/biometricos/gestionar', 'label' => 'Gestionar Biométrico', 'icon' => 'fa-sliders-h'],
            ['path' => '/database', 'label' => 'Base de Datos', 'icon' => 'fa-database'],
            ['path' => '/catalogos', 'label' => 'Catálogos', 'icon' => 'fa-sitemap'],
            ['path' => '/permisos-menu', 'label' => 'Permisos de Menú', 'icon' => 'fa-shield-halved'],
            ['path' => '/configuracion', 'label' => 'Configuración', 'icon' => 'fa-gear'],
            ['path' => '/notas-malas', 'label' => 'Notas Malas', 'icon' => 'fa-exclamation-triangle'],
            ['path' => '/usuarios', 'label' => 'Usuarios', 'icon' => 'fa-user-cog'],
            ['path' => '/logs', 'label' => 'Logs de Errores', 'icon' => 'fa-file-alt'],
        ];
        
        $menuConfig = [];
        if ($usuarioId) {
            try {
                $stmt = $this->db->getConnection()->prepare("SELECT menu_path, visible FROM menu_config WHERE usuario_id = ?");
                $stmt->execute([$usuarioId]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $menuConfig[$row['menu_path']] = (bool)$row['visible'];
                }
            } catch (Exception $e) {
                error_log("Error al cargar config menu: " . $e->getMessage());
            }
        }
        
        $html = '';
        $count = 0;
        $totalItems = count($menuItems);
        $itemsPerCol = ceil($totalItems / 4);
        
        $html .= '<div class="row g-2">';
        foreach ($menuItems as $index => $item) {
            $checked = !isset($menuConfig[$item['path']]) || $menuConfig[$item['path']];
            $html .= '<div class="col-6 col-md-3">';
            $html .= '<div class="form-check">';
            $html .= '<input class="form-check-input menu-usuario-check" type="checkbox" name="menu_usuario[' . htmlspecialchars($item['path']) . ']" id="menu_usuario_' . $index . '" value="1"' . ($checked ? ' checked' : '') . '>';
            $html .= '<label class="form-check-label small" for="menu_usuario_' . $index . '">';
            $html .= '<i class="fas ' . $item['icon'] . ' me-1 text-muted"></i>' . htmlspecialchars($item['label']);
            $html .= '</label>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        return $html;
    }
    
    public function toggleStatus($id) {
        header('Content-Type: application/json');

        require_once 'models/Usuario.php';
        $usuarioModel = new Usuario($this->db);

        $activo = $_POST['activo'] ?? null;
        if ($activo === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Parámetro activo requerido'], 400);
            return;
        }

        try {
            $usuarioModel->toggleStatus($id, (int)$activo);
            $this->jsonResponse(['success' => true, 'message' => 'Estado actualizado']);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function guardarMenuUsuario() {
        header('Content-Type: application/json');
        
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('usuarios_editar')) {
            $this->jsonResponse(['success' => false, 'message' => 'No tienes permiso'], 403);
            return;
        }
        
        $usuarioId = $_POST['usuario_id'] ?? null;
        $menuConfig = $_POST['menu_usuario'] ?? [];
        
        if (!$usuarioId) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario no especificado'], 400);
            return;
        }
        
        $menuItems = [
            ['path' => '/dashboard', 'label' => 'Dashboard'],
            ['path' => '/', 'label' => 'Inicio'],
            ['path' => '/empleados', 'label' => 'Empleados'],
            ['path' => '/asistencia', 'label' => 'Asistencia'],
            ['path' => '/marcaciones', 'label' => 'Marcaciones'],
            ['path' => '/mis-validaciones', 'label' => 'Mis Validaciones'],
            ['path' => '/horarios', 'label' => 'Horarios'],
            ['path' => '/ciclos', 'label' => 'Ciclos'],
            ['path' => '/validaciones', 'label' => 'Validaciones'],
            ['path' => '/reportes/excel', 'label' => 'Reportes Excel'],
            ['path' => '/resumen-justificaciones', 'label' => 'Resumen Justificaciones'],
            ['path' => '/analisis-predictivo', 'label' => 'Análisis Predictivo'],
            ['path' => '/agent-ia', 'label' => 'Agent IA'],
            ['path' => '/ai', 'label' => 'AI Dashboard'],
            ['path' => '/biometricos', 'label' => 'Dispositivos Biométricos'],
            ['path' => '/biometricos/gestionar', 'label' => 'Gestionar Biométrico'],
            ['path' => '/database', 'label' => 'Base de Datos'],
            ['path' => '/catalogos', 'label' => 'Catálogos'],
            ['path' => '/permisos-menu', 'label' => 'Permisos de Menú'],
            ['path' => '/configuracion', 'label' => 'Configuración'],
            ['path' => '/notas-malas', 'label' => 'Notas Malas'],
            ['path' => '/usuarios', 'label' => 'Usuarios'],
            ['path' => '/logs', 'label' => 'Logs de Errores'],
        ];
        
        try {
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();
            
            $stmtDelete = $pdo->prepare("DELETE FROM menu_config WHERE usuario_id = ?");
            $stmtDelete->execute([$usuarioId]);
            
            $stmtInsert = $pdo->prepare("INSERT INTO menu_config (usuario_id, menu_path, visible) VALUES (?, ?, ?)");
            
            foreach ($menuItems as $item) {
                $path = $item['path'];
                $visible = isset($menuConfig[$path]) ? 1 : 0;
                $stmtInsert->execute([$usuarioId, $path, $visible]);
            }
            
            $pdo->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Menú configurado correctamente']);
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
