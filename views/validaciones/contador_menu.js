/**
 * JavaScript para actualizar el contador de validaciones pendientes en el menú
 * Se ejecuta automáticamente cuando carga la página
 */

document.addEventListener('DOMContentLoaded', function() {
    actualizarContadorMenu();
    // Actualizar cada 30 segundos
    setInterval(actualizarContadorMenu, 30000);
});

/**
 * Actualizar el contador de validaciones pendientes en el menú
 */
async function actualizarContadorMenu() {
    try {
        // Verificar si BASE_URL está definido
        if (!window.BASE_URL) {
            console.warn('BASE_URL no está definido, usando fallback');
            window.BASE_URL = '';
        }
        
        // Obtener token CSRF del meta tag o formulario
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                         || document.querySelector('input[name="csrf_token"]')?.value;
        
        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        };
        
        // Agregar token CSRF si está disponible
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }
        
        // Agregar timestamp para evitar caché
        const timestamp = new Date().getTime();
        const url = `${window.BASE_URL}/validaciones/obtener-contador-pendientes?t=${timestamp}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: headers,
            cache: 'no-store'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const badge = document.getElementById('menuPendientesCount');
            if (badge) {
                if (data.pendientes && data.pendientes > 0) {
                    badge.textContent = data.pendientes > 99 ? '99+' : data.pendientes;
                    badge.style.display = 'inline-block';
                    
                    // Agregar clase para animación
                    badge.classList.add('pulse');
                    
                    // Remover animación después de 1 segundo
                    setTimeout(() => {
                        badge.classList.remove('pulse');
                    }, 1000);
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    } catch (error) {
        // Mostrar error detallado para depuración
        console.warn('Error al actualizar contador de validaciones:', error.message);
        console.debug('Detalles del error:', error);
        
        // Intentar recargar la página si hay errores persistentes de red
        if (error.message && error.message.includes('fetch')) {
            console.log('Error de red detectado, intentando recargar recursos...');
        }
    }
}

/**
 * Forzar actualización del contador (se puede llamar desde otras partes del sistema)
 */
function forzarActualizacionContador() {
    actualizarContadorMenu();
}

// Exportar funciones si se necesitan desde otros scripts
window.forzarActualizacionContador = forzarActualizacionContador;