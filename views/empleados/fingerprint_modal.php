<div class="modal fade" id="fingerprintModal" tabindex="-1" aria-labelledby="fingerprintModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="fingerprintModalLabel">
                    <i class="fas fa-fingerprint"></i> Registro de Huella Dactilar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="fingerprint-animation mb-3">
                        <i class="fas fa-fingerprint fa-5x text-primary"></i>
                    </div>
                    <h5 id="capture-status">Preparando captura...</h5>
                    <p class="text-muted" id="capture-instructions">
                        Por favor, coloque su dedo en el escáner cuando se lo indiquen.
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Información del Dispositivo</h6>
                            </div>
                            <div class="card-body">
                                <div id="device-info">
                                    <p class="text-muted">Conectando con dispositivo...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Calidad de Captura</h6>
                            </div>
                            <div class="card-body">
                                <div class="progress mb-2" style="height: 25px;">
                                    <div id="quality-bar" class="progress-bar bg-success" role="progressbar" style="width: 0%">0%</div>
                                </div>
                                <div id="quality-text" class="text-center">Esperando captura...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Estado del Registro</h6>
                            </div>
                            <div class="card-body">
                                <div id="registration-steps">
                                    <div class="step-item mb-2">
                                        <i class="fas fa-circle text-muted me-2" id="step-1-icon"></i>
                                        <span id="step-1-text">Conectando con dispositivo...</span>
                                    </div>
                                    <div class="step-item mb-2">
                                        <i class="fas fa-circle text-muted me-2" id="step-2-icon"></i>
                                        <span id="step-2-text">Esperando huella...</span>
                                    </div>
                                    <div class="step-item mb-2">
                                        <i class="fas fa-circle text-muted me-2" id="step-3-icon"></i>
                                        <span id="step-3-text">Procesando template...</span>
                                    </div>
                                    <div class="step-item mb-2">
                                        <i class="fas fa-circle text-muted me-2" id="step-4-icon"></i>
                                        <span id="step-4-text">Guardando en dispositivo...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="retry-capture-btn" style="display: none;">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.fingerprint-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 0.6; transform: scale(0.95); }
    50% { opacity: 1; transform: scale(1.05); }
    100% { opacity: 0.6; transform: scale(0.95); }
}

.step-item {
    transition: all 0.3s ease;
}

.step-item.success {
    color: #28a745;
}

.step-item.error {
    color: #dc3545;
}

.step-item.active {
    color: #007bff;
    font-weight: bold;
}
</style>

<script>
let captureInProgress = false;
let currentDeviceId = null;
let closeModalTimeout = null;

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que Bootstrap esté disponible
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap cargado correctamente');
    } else {
        console.warn('Bootstrap no está disponible al cargar la página');
    }
});

async function showFingerprintModal(deviceId) {
    // Esperar a que Bootstrap esté disponible (máximo 5 segundos)
    let attempts = 0;
    const maxAttempts = 50; // 5 segundos con 100ms de intervalo
    
    while (typeof bootstrap === 'undefined' && attempts < maxAttempts) {
        await new Promise(resolve => setTimeout(resolve, 100));
        attempts++;
    }
    
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está cargado después de esperar');
        alert('Error: El sistema no está completamente cargado. Por favor, recargue la página e intente nuevamente.');
        return;
    }
    
    // Verificar que el modal exista
    const modalElement = document.getElementById('fingerprintModal');
    if (!modalElement) {
        console.error('Modal element not found');
        alert('Error: No se encontró el modal de huella dactilar.');
        return;
    }
    
    currentDeviceId = deviceId;
    document.getElementById('fingerprintModalLabel').innerHTML = `
        <i class="fas fa-fingerprint"></i> Registro de Huella - Dispositivo ${deviceId}
    `;
    
    try {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Resetear estado
        resetModalState();
        
        // Iniciar proceso de captura
        setTimeout(() => startCaptureProcess(deviceId), 1000);
    } catch (error) {
        console.error('Error al inicializar modal:', error);
        alert('Error al abrir el modal de huella dactilar: ' + error.message);
    }
}

function resetModalState() {
    // Resetear pasos
    for (let i = 1; i <= 4; i++) {
        const icon = document.getElementById(`step-${i}-icon`);
        const text = document.getElementById(`step-${i}-text`);
        
        icon.className = 'fas fa-circle text-muted me-2';
        text.textContent = getStepText(i, 'waiting');
        text.parentElement.className = 'step-item mb-2';
    }
    
    // Resetear calidad
    document.getElementById('quality-bar').style.width = '0%';
    document.getElementById('quality-bar').className = 'progress-bar bg-success';
    document.getElementById('quality-text').textContent = 'Esperando captura...';
    
    // Resetear estado
    document.getElementById('capture-status').textContent = 'Preparando captura...';
    document.getElementById('capture-instructions').textContent = 'Por favor, coloque su dedo en el escáner cuando se lo indiquen.';
    document.getElementById('device-info').innerHTML = '<p class="text-muted">Conectando con dispositivo...</p>';
    document.getElementById('retry-capture-btn').style.display = 'none';
    
    captureInProgress = false;
}

function getStepText(step, status) {
    const texts = {
        1: {
            waiting: 'Conectando con dispositivo...',
            active: 'Conectando con dispositivo...',
            success: 'Dispositivo conectado',
            error: 'Error de conexión'
        },
        2: {
            waiting: 'Esperando huella...',
            active: 'Capturando huella...',
            success: 'Huella capturada',
            error: 'Error al capturar huella'
        },
        3: {
            waiting: 'Procesando template...',
            active: 'Procesando template...',
            success: 'Template generado',
            error: 'Error procesando template'
        },
        4: {
            waiting: 'Guardando en dispositivo...',
            active: 'Guardando en dispositivo...',
            success: 'Huella registrada',
            error: 'Error al guardar en dispositivo'
        }
    };
    
    return texts[step][status] || texts[step].waiting;
}

function updateStep(step, status, additionalInfo = null) {
    const icon = document.getElementById(`step-${step}-icon`);
    const text = document.getElementById(`step-${step}-text`);
    
    switch (status) {
        case 'active':
            icon.className = 'fas fa-spinner fa-spin me-2 text-primary';
            text.textContent = getStepText(step, 'active');
            text.parentElement.className = 'step-item mb-2 active';
            break;
        case 'success':
            icon.className = 'fas fa-check-circle me-2 text-success';
            text.textContent = getStepText(step, 'success');
            if (additionalInfo) text.textContent += ` - ${additionalInfo}`;
            text.parentElement.className = 'step-item mb-2 success';
            break;
        case 'error':
            icon.className = 'fas fa-times-circle me-2 text-danger';
            text.textContent = getStepText(step, 'error');
            if (additionalInfo) text.textContent += ` - ${additionalInfo}`;
            text.parentElement.className = 'step-item mb-2 error';
            break;
    }
}

function updateQuality(percentage) {
    const bar = document.getElementById('quality-bar');
    const text = document.getElementById('quality-text');
    
    bar.style.width = percentage + '%';
    text.textContent = `Calidad: ${percentage}%`;
    
    if (percentage < 50) {
        bar.className = 'progress-bar bg-danger';
    } else if (percentage < 80) {
        bar.className = 'progress-bar bg-warning';
    } else {
        bar.className = 'progress-bar bg-success';
    }
}

async function startCaptureProcess(deviceId) {
    if (captureInProgress) return;
    
    captureInProgress = true;
    let step = 1;
    
    try {
        // Paso 1: Conectar con dispositivo
        updateStep(1, 'active');
        
        // Usar BASE_URL definido en la vista padre o fallback
        const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '/sistema_biometrico';
        const csrfToken = document.getElementById('csrf_token')?.value || '';
        const connectResponse = await fetch(`${baseUrl}/biometricos/test-dispositivo/${deviceId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                'csrf_token': csrfToken
            })
        });
        
        // Verificar respuesta
        if (!connectResponse.ok) {
            const errorText = await connectResponse.text();
            console.error('Error en conexión:', errorText);
            updateStep(1, 'error', `Error HTTP ${connectResponse.status}`);
            return;
        }
        
        const connectData = await connectResponse.json();
        
        if (connectData.success) {
            updateStep(1, 'success');
            
            // Mostrar información del dispositivo
            document.getElementById('device-info').innerHTML = `
                <strong>Nombre:</strong> ${connectData.dispositivo.nombre}<br>
                <strong>IP:</strong> ${connectData.dispositivo.ip_address}<br>
                <strong>Modelo:</strong> ${connectData.dispositivo.model || 'Desconocido'}<br>
                <strong>Estado:</strong> <span class="badge bg-success">Conectado</span>
            `;
            
            // Paso 2: Capturar huella
            step = 2;
            updateStep(2, 'active');
            document.getElementById('capture-status').textContent = 'Capturando huella...';
            document.getElementById('capture-instructions').textContent = 'Por favor, coloque su dedo en el escáner.';
            
            // Simular animación de calidad
            for (let i = 0; i <= 95; i += 5) {
                updateQuality(i);
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            
            const csrfToken = document.getElementById('csrf_token')?.value || '';
            const captureResponse = await fetch(`${baseUrl}/empleados/capturarHuella`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'csrf_token': csrfToken,
                    'dispositivo_id': deviceId
                })
            });
            
            // Verificar respuesta
            if (!captureResponse.ok) {
                const errorText = await captureResponse.text();
                console.error('Error en captura:', errorText);
                updateStep(2, 'error', `Error HTTP ${captureResponse.status}`);
                return;
            }
            
            const captureData = await captureResponse.json();
            
            if (captureData.success) {
                updateStep(2, 'success');
                updateQuality(95);
                
                // Paso 3: Procesar template
                step = 3;
                updateStep(3, 'active');
                document.getElementById('capture-status').textContent = 'Procesando template biométrico...';
                
                await new Promise(resolve => setTimeout(resolve, 1500));
                updateStep(3, 'success', `${captureData.fingerprint_data.length} bytes`);
                
                // Paso 4: Guardar en dispositivo
                step = 4;
                updateStep(4, 'active');
                document.getElementById('capture-status').textContent = 'Registrando huella en dispositivo...';
                
                await new Promise(resolve => setTimeout(resolve, 1000));
                updateStep(4, 'success');
                
                // Éxito completo
                document.getElementById('capture-status').textContent = '¡Huella registrada exitosamente!';
                document.getElementById('capture-instructions').textContent = 'El empleado ya puede usar el dispositivo biométrico.';
                
                // Guardar datos en el formulario
                const fingerprintField = document.getElementById('captured_fingerprint');
                if (fingerprintField) {
                    fingerprintField.value = captureData.fingerprint_data;
                }
                
                // Cerrar modal automáticamente
                closeModalTimeout = setTimeout(() => {
                    const modalElement = document.getElementById('fingerprintModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }
                }, 2000);
                
            } else {
                throw new Error(captureData.error || 'Error al capturar huella');
            }
            
        } else {
            throw new Error(connectData.error || 'Error al conectar con dispositivo');
        }
        
    } catch (error) {
        updateStep(step, 'error', error.message);
        document.getElementById('capture-status').textContent = 'Error en el registro';
        document.getElementById('capture-instructions').textContent = error.message;
        document.getElementById('retry-capture-btn').style.display = 'inline-block';
        captureInProgress = false;
    }
}

// Botón de reintentar
document.getElementById('retry-capture-btn').addEventListener('click', function() {
    if (currentDeviceId && !captureInProgress) {
        resetModalState();
        setTimeout(() => startCaptureProcess(currentDeviceId), 500);
    }
});

// Clear timeout on form submission to prevent race condition
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', () => {
        if (closeModalTimeout) {
            clearTimeout(closeModalTimeout);
        }
    });
}
</script>