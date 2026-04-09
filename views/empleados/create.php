<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../helpers/Csrf.php'; 
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-primary"><i class="fas fa-user-plus me-2"></i>Nuevo Empleado</h2>
    <a href="<?php echo BASE_URL; ?>/empleados" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>
<div class="container-fluid">

    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff; border-bottom: none;">
            <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Formulario de Registro</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>/empleados/create" enctype="multipart/form-data">
                <input type="hidden" name="_token" id="csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre_completo" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                            <div class="form-text">Nombre completo del empleado (este campo es para identificación general)</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="sexo" class="form-label">Sexo</label>
                            <select class="form-control" id="sexo" name="sexo" required>
                                <option value="">Seleccionar sexo</option>
                                <option value="H">Hombre</option>
                                <option value="M">Mujer</option>
                             </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="entidad_federativa" class="form-label">Entidad Federativa de Nacimiento</label>
                            <select class="form-control" id="entidad_federativa" name="entidad_federativa" required>
                                <option value="">Seleccionar entidad</option>
                                <option value="AS">Aguascalientes</option>
                                <option value="BC">Baja California</option>
                                <option value="BS">Baja California Sur</option>
                                <option value="CC">Campeche</option>
                                <option value="CL">Coahuila</option>
                                <option value="CM">Colima</option>
                                <option value="CS">Chiapas</option>
                                <option value="CH">Chihuahua</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="DG">Durango</option>
                                <option value="GT">Guanajuato</option>
                                <option value="GR">Guerrero</option>
                                <option value="HG">Hidalgo</option>
                                <option value="JC">Jalisco</option>
                                <option value="MC">México</option>
                                <option value="MN">Michoacán</option>
                                <option value="MS">Morelos</option>
                                <option value="NT">Nayarit</option>
                                <option value="NL">Nuevo León</option>
                                <option value="OC">Oaxaca</option>
                                <option value="PL">Puebla</option>
                                <option value="QT">Querétaro</option>
                                <option value="QR">Quintana Roo</option>
                                <option value="SP">San Luis Potosí</option>
                                <option value="SL">Sinaloa</option>
                                <option value="SR">Sonora</option>
                                <option value="TC">Tabasco</option>
                                <option value="TS">Tamaulipas</option>
                                <option value="TL">Tlaxcala</option>
                                <option value="VZ">Veracruz</option>
                                <option value="YN">Yucatán</option>
                                <option value="ZS">Zacatecas</option>
                                <option value="NE">Nacido en el Extranjero</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="primer_apellido" class="form-label">Primer Apellido</label>
                            <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="segundo_apellido" class="form-label">Segundo Apellido</label>
                            <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="nombres" class="form-label">Nombre(s)</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rfc" class="form-label">RFC</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="rfc" name="rfc" required maxlength="13" readonly>
                                <button class="btn btn-pantone-secondary" type="button" id="generarCodigosBtn">
                                    <i class="fas fa-magic"></i> Generar RFC/CURP
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="curp" class="form-label">CURP</label>
                            <input type="text" class="form-control" id="curp" name="curp" required maxlength="18" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jefe_directo_id" class="form-label">Jefe Directo (Mando)</label>
                            <select class="form-select" id="jefe_directo_id" name="jefe_directo_id" required>
                                <option value="">Seleccionar...</option>
                                <?php 
                                require_once __DIR__ . '/../../models/Catalogo.php';
                                $catalogo = new Catalogo();
                                $mandos = $catalogo->getAllMandos();
                                foreach ($mandos as $mando): 
                                ?>
                                <option value="<?= htmlspecialchars($mando['clave_area']) ?>" 
                                    data-area="<?= htmlspecialchars($mando['area']) ?>"
                                    data-nombre="<?= htmlspecialchars($mando['nombre_mando']) ?>">
                                    <?= htmlspecialchars($mando['nombre_mando']) ?> - <?= htmlspecialchars($mando['area']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Seleccione el mando para autocompletar área y clave</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="area" class="form-label">Área</label>
                            <input type="text" class="form-control" id="area" name="area" required readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="area" class="form-label">Área (Departamento)</label>
                            <input type="text" class="form-control" id="area" name="area" required readonly>
                            <div class="form-text">Se autocompleta al seleccionar el mando</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="area_fisica" class="form-label">Área Física (Ubicación)</label>
                            <input type="text" class="form-control" id="area_fisica" name="area_fisica" placeholder="Ej: Edificio A, Planta Baja">
                            <div class="form-text">Ubicación física donde trabaja el empleado</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="clave_depto" class="form-label">Clave Área (Del Mando)</label>
                            <input type="text" class="form-control" id="clave_depto" name="clave_depto" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="jerarquia" class="form-label">Jerarquía</label>
                            <select class="form-control" id="jerarquia" name="jerarquia" required>
                                <option value="">Seleccionar jerarquía</option>
                                <option value="Empleado">Empleado</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Gerente">Gerente</option>
                                <option value="Director">Director</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="foto_cara" class="form-label">Foto del Empleado</label>
                            <input type="file" class="form-control" id="foto_cara" name="foto_cara" accept="image/*">
                            <div class="form-text">
                                Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                            </div>
                            <div id="image-preview" class="mt-2" style="display: none;">
                                <img id="preview-img" src="" alt="Vista previa" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de registro biométrico -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-3" style="border: 2px solid #235B4E;">
                            <div class="card-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: #ffffff;">
                                <h5 class="mb-0"><i class="fas fa-fingerprint"></i> Registro Biométrico</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="registrar_huella" name="registrar_huella" value="1">
                                        <label class="form-check-label" for="registrar_huella">
                                            <strong>Registrar huella dactilar durante la creación del empleado</strong>
                                        </label>
                                        <div class="form-text">
                                            Al marcar esta opción, se activará el detector de huella para capturar la huella del empleado.
                                        </div>
                                    </div>
                                </div>

                                <div id="dispositivo-section" style="display: none;">
                                    <div class="mb-3">
                                        <label for="dispositivo_huella" class="form-label">Dispositivo Biométrico</label>
                                        <select class="form-select" id="dispositivo_huella" name="dispositivo_huella">
                                            <option value="">Seleccione un dispositivo...</option>
                                        </select>
                                        <div class="form-text">
                                            Seleccione el dispositivo biométrico para capturar la huella.
                                        </div>
                                    </div>

                                    <div class="mb-3" id="test-device-container" style="display: none;">
                                        <button type="button" class="btn btn-pantone-secondary btn-sm" id="test-device-btn">
                                            <i class="fas fa-plug"></i> Probar Conexión </button>
                                        <div id="test-result" class="mt-2"></div>
                                    </div>

                                    <div class="mb-3" id="capture-container" style="display: none;">
                                        <button type="button" class="btn btn-pantone-secondary btn-lg w-100" id="capture-fingerprint-btn">
                                            <i class="fas fa-fingerprint"></i> Iniciar Registro de Huella
                                        </button>
                                        <div id="capture-result" class="mt-2"></div>
                                    </div>

<div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Proceso de registro:</strong>
                                        <ol class="mb-0 mt-2">
                                            <li>Seleccione un dispositivo biométrico de la lista</li>
                                            <li>Pruebe la conexión con el dispositivo</li>
                                            <li>Capture la huella dactilar del empleado</li>
                                            <li>Guarde el empleado para completar el registro</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['form_errors'])): ?>
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> Errores de validación:</h6>
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['form_errors'] as $field => $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['form_errors']); ?>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Empleado
                </button>
                <button type="reset" class="btn btn-outline-secondary">
                    <i class="fas fa-undo"></i> Limpiar
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal de registro biométrico -->
<?php try {
    include __DIR__ . '/fingerprint_modal.php';
} catch (Exception $e) {
    error_log("Error cargando modal de huella: " . $e->getMessage());
    // Continuar sin el modal si hay error
} ?>

<script>
const BASE_URL = '<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/sistema_biometrico'; ?>';

// Auto-fill area and clave_depto when mando is selected
document.getElementById('jefe_directo_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const areaMando = selectedOption.dataset.area || '';
    const claveArea = selectedOption.value || '';
    
    const areaInput = document.getElementById('area');
    const claveDeptoInput = document.getElementById('clave_depto');
    
    if (areaInput && areaMando) {
        areaInput.value = areaMando;
    }
    if (claveDeptoInput && claveArea) {
        claveDeptoInput.value = claveArea;
    }
});

document.getElementById('foto_cara').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

// Variables globales
let dispositivosCargados = false;

// Cargar dispositivos biométricos
async function cargarDispositivos() {
    try {
        const response = await fetch(BASE_URL + '/dispositivos/getStatus');
        const data = await response.json();
        
        console.log('Dispositivos response:', data);
        
        const select = document.getElementById('dispositivo_huella');
        select.innerHTML = '<option value="">Seleccione un dispositivo...</option>';
        
        if (data.success && data.dispositivos && data.dispositivos.length > 0) {
            data.dispositivos.forEach(dispositivo => {
                const option = document.createElement('option');
                option.value = dispositivo.dispositivo_id;
                option.textContent = `${dispositivo.nombre} (${dispositivo.sede}) - ${dispositivo.status}`;
                option.dataset.online = dispositivo.online;
                select.appendChild(option);
            });
            
            dispositivosCargados = true;
        } else {
            // Si no hay dispositivos, agregar opción por defecto
            const option = document.createElement('option');
            option.value = "1";
            option.textContent = "Dispositivo por defecto";
            select.appendChild(option);
            dispositivosCargados = true;
            console.warn('No se encontraron dispositivos, usando valor por defecto');
        }
    } catch (error) {
        console.error('Error cargando dispositivos:', error);
        // Agregar opción por defecto en caso de error
        const select = document.getElementById('dispositivo_huella');
        select.innerHTML = '<option value="1">Dispositivo por defecto</option>';
        dispositivosCargados = true;
    }
}

// Controlar visibilidad de la sección de dispositivo biométrico
document.getElementById('registrar_huella').addEventListener('change', async function(e) {
    const dispositivoSection = document.getElementById('dispositivo-section');
    const dispositivoSelect = document.getElementById('dispositivo_huella');
    
    if (e.target.checked) {
        dispositivoSection.style.display = 'block';
        dispositivoSelect.setAttribute('required', '');
        
        // Cargar dispositivos si no se han cargado
        if (!dispositivosCargados) {
            await cargarDispositivos();
        }
    } else {
        dispositivoSection.style.display = 'none';
        dispositivoSelect.removeAttribute('required');
        dispositivoSelect.value = '';
    }
});

// Manejar selección de dispositivo
document.getElementById('dispositivo_huella').addEventListener('change', function(e) {
    const testContainer = document.getElementById('test-device-container');
    const captureContainer = document.getElementById('capture-container');
    const selectedOption = e.target.options[e.target.selectedIndex];
    
    if (e.target.value && selectedOption.dataset.online === 'true') {
        testContainer.style.display = 'block';
        captureContainer.style.display = 'block';
    } else {
        testContainer.style.display = 'none';
        captureContainer.style.display = 'none';
        
        if (e.target.value) {
            showAlert('test-result', 'El dispositivo seleccionado está desconectado', 'warning');
        }
    }
});

// Probar conexión con dispositivo
document.getElementById('test-device-btn').addEventListener('click', async function() {
    const deviceId = document.getElementById('dispositivo_huella').value;
    if (!deviceId) {
        showAlert('test-result', 'Seleccione un dispositivo primero', 'warning');
        return;
    }
    
    const btn = this;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
    btn.disabled = true;
    
    try {
        const csrfToken = document.getElementById('csrf_token').value;
        const url = BASE_URL + '/biometricos/test-dispositivo/' + deviceId;
        console.log('Intentando conectar a URL:', url);
        console.log('BASE_URL:', BASE_URL);
        console.log('deviceId:', deviceId);
        console.log('csrfToken:', csrfToken);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                'csrf_token': csrfToken
            })
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Verificar si la respuesta es OK
        if (!response.ok) {
            const text = await response.text();
            console.error('Error response (first 500 chars):', text.substring(0, 500));
            throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
        }
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            showAlert('test-result', 
                `Conexión exitosa con ${data.dispositivo.nombre}<br>
                IP: ${data.dispositivo.ip_address}<br>
                Modelo: ${data.dispositivo.model || 'Desconocido'}`, 
                'success');
        } else {
            showAlert('test-result', `Error de conexión: ${data.error}`, 'danger');
        }
    } catch (error) {
        showAlert('test-result', `Error: ${error.message}`, 'danger');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

// Capturar huella dactilar
document.getElementById('capture-fingerprint-btn').addEventListener('click', function() {
    const deviceId = document.getElementById('dispositivo_huella').value;
    if (!deviceId) {
        showAlert('capture-result', 'Seleccione un dispositivo primero', 'warning');
        return;
    }
    
    // Agregar campo oculto para almacenar la huella capturada
    let fingerprintField = document.getElementById('captured_fingerprint');
    if (!fingerprintField) {
        fingerprintField = document.createElement('input');
        fingerprintField.type = 'hidden';
        fingerprintField.id = 'captured_fingerprint';
        fingerprintField.name = 'captured_fingerprint';
        document.querySelector('form').appendChild(fingerprintField);
    }
    
    // Mostrar modal de captura
    showFingerprintModal(deviceId);
});

// Función para mostrar alertas
function showAlert(containerId, message, type) {
    const container = document.getElementById(containerId);
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    container.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert && alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Validación del formulario antes de enviar
document.querySelector('form').addEventListener('submit', function(e) {
    const registrarHuella = document.getElementById('registrar_huella').checked;
    
    if (registrarHuella) {
        const deviceId = document.getElementById('dispositivo_huella').value;
        const fingerprintCaptured = document.getElementById('captured_fingerprint')?.value;
        
        if (!deviceId) {
            e.preventDefault();
            alert('Seleccione un dispositivo biométrico para registrar la huella');
            return;
        }
        
        if (!fingerprintCaptured) {
            e.preventDefault();
            alert('Debe capturar la huella dactilar antes de guardar el empleado');
            return;
        }
    }
});

// Cargar dispositivos al iniciar si el checkbox está marcado
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('registrar_huella').checked) {
        cargarDispositivos();
    }
});

// Función para generar códigos desde el formulario principal
document.getElementById('generarCodigosBtn').addEventListener('click', function() {
    const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
    const primerApellido = document.getElementById('primer_apellido').value;
    const segundoApellido = document.getElementById('segundo_apellido').value;
    const nombres = document.getElementById('nombres').value;
    const sexo = document.getElementById('sexo').value;
    const entidadFederativa = document.getElementById('entidad_federativa').value;

    if (!fechaNacimiento || !primerApellido || !segundoApellido || !nombres || !sexo || !entidadFederativa) {
        alert('Por favor complete todos los campos antes de generar los códigos.');
        return;
    }

    // Mostrar loading en el botón
    const btn = document.getElementById('generarCodigosBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
    btn.disabled = true;

    // Enviar datos al servidor para generar ambos códigos
    const csrfToken = document.getElementById('csrf_token').value;
    
    Promise.all([
        fetch(BASE_URL + '/empleados/generate_rfc', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                csrf_token: csrfToken,
                nombres: nombres,
                primer_apellido: primerApellido,
                segundo_apellido: segundoApellido,
                fecha_nacimiento: fechaNacimiento
            })
        }),
        fetch(BASE_URL + '/empleados/generate_curp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                csrf_token: csrfToken,
                nombres: nombres,
                primer_apellido: primerApellido,
                segundo_apellido: segundoApellido,
                fecha_nacimiento: fechaNacimiento,
                sexo: sexo,
                entidad_federativa: entidadFederativa
            })
        })
    ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([rfcData, curpData]) => {
        // Restaurar botón
        btn.innerHTML = originalText;
        btn.disabled = false;

        if (rfcData.success && curpData.success) {
            document.getElementById('rfc').value = rfcData.rfc;
            document.getElementById('curp').value = curpData.curp;

            // Mostrar mensaje de éxito
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show';
            successAlert.innerHTML = `
                <i class="fas fa-check"></i> RFC y CURP generados exitosamente
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            const form = document.querySelector('form');
            form.insertBefore(successAlert, form.firstChild);

            // Remover alerta después de 5 segundos
            setTimeout(() => {
                if (successAlert.parentNode) {
                    successAlert.remove();
                }
            }, 5000);
        } else {
            const errorMsg = rfcData.error || curpData.error;
            alert('Error al generar códigos: ' + errorMsg);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
        alert('Error al generar códigos. Verifique la consola para más detalles.');
    });
});




</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
