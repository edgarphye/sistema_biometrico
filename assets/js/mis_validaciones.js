/**
 * JavaScript para el Panel de Validaciones del Empleado
 * Conversación bidireccional empleado ↔ jefe
 */

let validacionActualId = null;
let mensajeEnviado = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Panel de Mis Validaciones cargado');
    actualizarContadorNoLeidas();
    setInterval(actualizarContadorNoLeidas, 30000);

    const modalEl = document.getElementById('modalConversacion');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function() {
            if (mensajeEnviado) {
                mensajeEnviado = false;
                location.reload();
            }
        });
    }
});

function getCsrfToken() {
    return document.getElementById('csrf_token')?.value || 
           document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function formatearFecha(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha);
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function getEstadoBadge(estado) {
    const map = {
        'pendiente': '<span class="badge bg-primary"><i class="fas fa-clock me-1"></i>Pendiente</span>',
        'aprobado': '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Aprobado</span>',
        'rechazado': '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Rechazado</span>',
        'requiere_info': '<span class="badge bg-warning text-dark"><i class="fas fa-question-circle me-1"></i>Requiere Info</span>'
    };
    return map[estado] || estado;
}

function restaurarFormularioRespuesta() {
    const formRespuesta = document.getElementById('formRespuesta');
    if (!formRespuesta) return;
    formRespuesta.innerHTML = `
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-reply me-1"></i>Responder:
                </label>
                <textarea class="form-control" id="mensajeRespuesta" rows="3" 
                          placeholder="Escribe tu mensaje aquí..."></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Tu respuesta será notificada a tu jefe
                </small>
                <button type="button" class="btn btn-primary" id="btnEnviarRespuesta" disabled>
                    <i class="fas fa-paper-plane me-1"></i>Enviar
                </button>
            </div>
        </div>
    `;
    document.getElementById('btnEnviarRespuesta')?.addEventListener('click', enviarRespuesta);
    document.getElementById('mensajeRespuesta')?.addEventListener('input', function() {
        document.getElementById('btnEnviarRespuesta').disabled = !this.value.trim();
    });
    document.getElementById('mensajeRespuesta')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            enviarRespuesta();
        }
    });
}

async function abrirConversacion(validacionId, esExterna) {
    validacionActualId = validacionId;
    
    document.getElementById('formRespuesta').style.display = '';
    
    if (esExterna) {
        document.getElementById('chatMensajes').innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-comment-slash fa-2x mb-2"></i>
                <p class="mb-0">No hay conversación disponible para esta solicitud</p>
                <small>Esta solicitud fue procesada antes de que existiera el sistema de mensajería</small>
            </div>
        `;
        document.getElementById('formRespuesta').style.display = 'none';
        const modal = new bootstrap.Modal(document.getElementById('modalConversacion'));
        modal.show();
        return;
    }
    
    restaurarFormularioRespuesta();
    
    const modal = new bootstrap.Modal(document.getElementById('modalConversacion'));
    modal.show();
    
    document.getElementById('chatMensajes').innerHTML = `
        <div class="text-center py-4 text-muted">
            <i class="fas fa-spinner fa-spin me-2"></i>Cargando mensajes...
        </div>
    `;
    document.getElementById('mensajeRespuesta').value = '';
    document.getElementById('btnEnviarRespuesta').disabled = true;
    
    await cargarMensajes(validacionId);
}

async function cargarMensajes(validacionId) {
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/mensajes/${validacionId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (!data.success) {
            document.getElementById('chatMensajes').innerHTML = `
                <div class="alert alert-danger">Error al cargar mensajes: ${data.error}</div>
            `;
            return;
        }
        
        // Marcar como leído
        marcarComoLeido(validacionId);
        
        // Mostrar información de validación
        const v = data.validacion || {};
        document.getElementById('modalValidacionInfo').textContent = 
            `#${v.incidencia_id || ''} - ${v.tipo_incidencia || 'Incidencia'} - ${formatearFecha(v.fecha_incidencia || v.fecha_solicitud)}`;
        
        document.getElementById('modalEstadoValidacion').innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong><i class="fas fa-info-circle me-1"></i>Estado:</strong>
                    ${getEstadoBadge(v.estado)}
                </div>
                <div>
                    <strong><i class="fas fa-user-tie me-1"></i>Jefe:</strong>
                    ${v.jefe_nombre || 'N/A'}
                </div>
            </div>
            ${v.motivo_validacion ? `<p class="mt-2 mb-0 small text-muted">${v.motivo_validacion}</p>` : ''}
        `;
        
        // Renderizar mensajes
        const mensajes = data.mensajes || [];
        
        if (mensajes.length === 0) {
            document.getElementById('chatMensajes').innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-comment-slash fa-2x mb-2"></i>
                    <p class="mb-0">No hay mensajes en esta conversación</p>
                </div>
            `;
            document.getElementById('btnEnviarRespuesta').disabled = false;
            return;
        }
        
        let html = '';
        mensajes.forEach(msg => {
            const remitente = msg.remitente_tipo || 'sistema';
            const nombre = msg.remitente_nombre || 'Usuario';
            
            html += `
                <div class="chat-msg ${remitente}">
                    <div class="author">${nombre}</div>
                    <div>${escapeHtml(msg.mensaje)}</div>
                    <div class="time">${formatearFecha(msg.created_at)}</div>
                </div>
            `;
        });
        
        document.getElementById('chatMensajes').innerHTML = html;
        
        // Scroll al último mensaje
        const chatContainer = document.querySelector('.chat-container');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        
        // Habilitar respuesta solo si el empleado puede responder
        const puedeResponder = v.estado === 'requiere_info' || v.estado === 'pendiente';
        document.getElementById('btnEnviarRespuesta').disabled = !puedeResponder;
        
        if (!puedeResponder) {
            document.getElementById('formRespuesta').querySelector('.card-body').innerHTML = `
                <div class="alert alert-info mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    Esta validación ya fue procesada. No se pueden enviar más mensajes.
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Error cargando mensajes:', error);
        document.getElementById('chatMensajes').innerHTML = `
            <div class="alert alert-danger">Error de conexión al cargar mensajes</div>
        `;
    }
}

// Enviar mensaje al hacer click
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnEnviarRespuesta')?.addEventListener('click', enviarRespuesta);
    document.getElementById('mensajeRespuesta')?.addEventListener('input', function() {
        document.getElementById('btnEnviarRespuesta').disabled = !this.value.trim();
    });
    document.getElementById('mensajeRespuesta')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            enviarRespuesta();
        }
    });
});

async function enviarRespuesta() {
    const mensaje = document.getElementById('mensajeRespuesta').value.trim();
    
    if (!mensaje || !validacionActualId) {
        return;
    }
    
    document.getElementById('btnEnviarRespuesta').disabled = true;
    document.getElementById('btnEnviarRespuesta').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enviando...';
    
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/mensajes/agregar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: getCsrfToken(),
                validacion_id: validacionActualId,
                mensaje: mensaje,
                tipo_mensaje: 'info_response'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('mensajeRespuesta').value = '';
            mensajeEnviado = true;
            await cargarMensajes(validacionActualId);
            actualizarContadorNoLeidas();
        } else {
            alert('Error al enviar mensaje: ' + data.error);
        }
    } catch (error) {
        console.error('Error enviando mensaje:', error);
        alert('Error de conexión al enviar mensaje');
    } finally {
        document.getElementById('btnEnviarRespuesta').disabled = false;
        document.getElementById('btnEnviarRespuesta').innerHTML = '<i class="fas fa-paper-plane me-1"></i>Enviar';
    }
}

async function marcarComoLeido(validacionId) {
    try {
        await fetch(`${window.BASE_URL}/validaciones/marcar-leido`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: getCsrfToken(),
                validacion_id: validacionId
            })
        });
    } catch (e) {
        console.error('Error marcando como leído:', e);
    }
}

async function actualizarContadorNoLeidas() {
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/contador-no-leidas`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const count = data.no_leidas || 0;
            const el = document.getElementById('noLeidasCount');
            if (el) el.textContent = count;
            
            const badge = document.getElementById('contadorNoLeidas');
            if (badge) {
                if (count > 0) {
                    badge.style.display = '';
                    badge.className = 'badge bg-warning text-dark fs-6';
                } else {
                    badge.className = 'badge bg-secondary text-light fs-6';
                }
            }

            const menuBadge = document.getElementById('misValidacionesPendientesCount');
            if (menuBadge) {
                if (count > 0) {
                    menuBadge.style.display = '';
                    menuBadge.textContent = count;
                } else {
                    menuBadge.style.display = 'none';
                }
            }
        }
    } catch (e) {
        console.error('Error actualizando contador:', e);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
