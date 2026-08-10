<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Justificación - Sistema Biométrico</title>
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
        }
        .campo-item {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .campo-item:hover {
            background: #e9ecef;
        }
        .required-label::after {
            content: " *";
            color: red;
        }
        #loading {
            display: none;
        }
        .tipo-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .tipo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .tipo-card.selected {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        .tipo-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout/header.php'; ?>
    
    <div class="container-fluid">
        <div class="form-container">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-file-signature"></i> Registrar Justificación</h4>
                </div>
                <div class="card-body">
                    <!-- Paso 1: Seleccionar Empleado -->
                    <div class="mb-4">
                        <label class="form-label required-label">Empleado</label>
                        <select id="empleado_id" name="empleado_id" class="form-select" required>
                            <option value="">Seleccionar Empleado...</option>
                        </select>
                    </div>
                    
                    <!-- Paso 2: Seleccionar Tipo de Justificación -->
                    <div class="mb-4">
                        <label class="form-label required-label">Tipo de Justificación</label>
                        <div id="tipos-container" class="row g-3">
                            <?php foreach ($tipos as $tipo): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="card tipo-card h-100" data-tipo="<?= htmlspecialchars($tipo['nombre']) ?>" onclick="seleccionarTipo('<?= htmlspecialchars($tipo['nombre']) ?>')">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-alt tipo-icon text-primary"></i>
                                        <h6><?= htmlspecialchars($tipo['nombre']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($tipo['descripcion'] ?? '') ?></small>
                                        <?php if ($tipo['requiere_documento']): ?>
                                        <span class="badge bg-warning text-dark mt-2"><i class="fas fa-paperclip"></i> Requiere Documento</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="tipo_justificacion" name="tipo_justificacion">
                    </div>
                    
                    <!-- Paso 3: Formulario Dinámico -->
                    <div id="formulario-dinamico" style="display: none;">
                        <hr>
                        <h5 class="mb-3"><i class="fas fa-edit"></i> Datos de la Justificación</h5>
                        <div id="campos-container"></div>
                        
                        <div class="mt-4">
                            <button type="button" class="btn btn-primary" onclick="enviarJustificacion()">
                                <i class="fas fa-save"></i> Guardar Justificación
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Loading -->
                    <div id="loading" class="text-center my-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Procesando...</p>
                    </div>
                    
                    <!-- Mensaje de Éxito/Error -->
                    <div id="mensaje-resultado" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let camposActual = [];
        
        // Cargar empleados al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarEmpleados();
        });
        
        async function cargarEmpleados() {
            try {
                const response = await fetch('/api/empleados?activo=1');
                const data = await response.json();
                
                const select = document.getElementById('empleado_id');
                if (data.success && data.data) {
                    data.data.forEach(emp => {
                        const option = document.createElement('option');
                        option.value = emp.id;
                        option.textContent = `${emp.nombre} ${emp.apellido || ''} - ${emp.area || 'Sin área'}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error cargando empleados:', error);
            }
        }
        
        function seleccionarTipo(tipo) {
            document.querySelectorAll('.tipo-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            const selectedCard = document.querySelector(`[data-tipo="${tipo}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            document.getElementById('tipo_justificacion').value = tipo;
            cargarCampos(tipo);
        }
        
        async function cargarCampos(tipo) {
            if (!tipo) return;
            
            document.getElementById('loading').style.display = 'block';
            document.getElementById('formulario-dinamico').style.display = 'none';
            
            try {
                const response = await fetch(`/justificaciones/campos?tipo=${encodeURIComponent(tipo)}`);
                const data = await response.json();
                
                if (data.success) {
                    camposActual = data.data.campos;
                    renderizarCampos(data.data.campos);
                    document.getElementById('formulario-dinamico').style.display = 'block';
                } else {
                    mostrarMensaje(data.error || 'Error al cargar campos', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error de conexión', 'danger');
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }
        
        function renderizarCampos(campos) {
            const container = document.getElementById('campos-container');
            container.innerHTML = '';
            
            campos.forEach(campo => {
                const div = document.createElement('div');
                div.className = 'campo-item';
                
                const label = document.createElement('label');
                label.className = `form-label ${campo.required ? 'required-label' : ''}`;
                label.textContent = campo.label;
                label.setAttribute('for', campo.name);
                div.appendChild(label);
                
                let input;
                
                if (campo.type === 'select' && campo.options) {
                    input = document.createElement('select');
                    input.className = 'form-select';
                    input.id = campo.name;
                    input.name = campo.name;
                    input.required = campo.required;
                    
                    const defaultOpt = document.createElement('option');
                    defaultOpt.value = '';
                    defaultOpt.textContent = 'Seleccionar...';
                    input.appendChild(defaultOpt);
                    
                    Object.entries(campo.options).forEach(([value, text]) => {
                        const opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = text;
                        input.appendChild(opt);
                    });
                } else if (campo.type === 'textarea') {
                    input = document.createElement('textarea');
                    input.className = 'form-control';
                    input.id = campo.name;
                    input.name = campo.name;
                    input.required = campo.required;
                    input.rows = 3;
                } else {
                    input = document.createElement('input');
                    input.className = 'form-control';
                    input.type = campo.type;
                    input.id = campo.name;
                    input.name = campo.name;
                    input.required = campo.required;
                }
                
                div.appendChild(input);
                container.appendChild(div);
            });
        }
        
        async function enviarJustificacion() {
            const empleado_id = document.getElementById('empleado_id').value;
            const tipo_justificacion = document.getElementById('tipo_justificacion').value;
            
            if (!empleado_id) {
                mostrarMensaje('Por favor seleccione un empleado', 'warning');
                return;
            }
            
            if (!tipo_justificacion) {
                mostrarMensaje('Por favor seleccione un tipo de justificación', 'warning');
                return;
            }
            
            const formData = new FormData();
            formData.append('empleado_id', empleado_id);
            formData.append('tipo_justificacion', tipo_justificacion);
            
            camposActual.forEach(campo => {
                const input = document.getElementById(campo.name);
                if (input) {
                    formData.append(campo.name, input.value);
                }
            });
            
            document.getElementById('loading').style.display = 'block';
            
            try {
                const response = await fetch('/justificaciones/registrar', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensaje('Justificación registrada correctamente', 'success');
                    limpiarFormulario();
                } else {
                    mostrarMensaje(data.error || 'Error al registrar', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error de conexión', 'danger');
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }
        
        function limpiarFormulario() {
            document.getElementById('empleado_id').value = '';
            document.getElementById('tipo_justificacion').value = '';
            document.querySelectorAll('.tipo-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.getElementById('formulario-dinamico').style.display = 'none';
            document.getElementById('campos-container').innerHTML = '';
            camposActual = [];
        }
        
        function mostrarMensaje(mensaje, tipo) {
            const container = document.getElementById('mensaje-resultado');
            container.innerHTML = `
                <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                    ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
