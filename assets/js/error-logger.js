/**
 * Sistema de Logging de Errores JavaScript
 * Envía errores a PHP en lugar de mostrarlos en la consola del navegador
 */

// URL del endpoint de logging
const LOG_ERROR_URL = BASE_URL + '/api/log-error';

// Función para enviar errores a PHP
function sendErrorToPHP(message, type = 'error', url = window.location.href, line = '', stack = '') {
    try {
        fetch(LOG_ERROR_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                type: type,
                url: url,
                line: line,
                stack: stack
            })
        }).catch(function() {
            // Silencioso - no hacer nada si falla
        });
    } catch (e) {
        // Silencioso
    }
}

// Interceptar fetch para capturar errores AJAX
const originalFetch = window.fetch;
window.fetch = function() {
    return originalFetch.apply(this, arguments).then(function(response) {
        if (!response.ok && response.status >= 400) {
            const contentType = response.headers.get('content-type');
            const clonedResponse = response.clone();
            if (contentType && contentType.includes('application/json')) {
                clonedResponse.json().then(function(data) {
                    sendErrorToPHP(
                        'AJAX Error ' + response.status + ': ' + (data.error || data.message || response.statusText),
                        'ajax_error',
                        response.url || window.location.href,
                        '',
                        JSON.stringify(data)
                    );
                }).catch(function() {
                    sendErrorToPHP(
                        'AJAX Error ' + response.status + ': ' + response.statusText,
                        'ajax_error',
                        response.url || window.location.href
                    );
                });
            } else {
                clonedResponse.text().then(function(text) {
                    sendErrorToPHP(
                        'AJAX Error ' + response.status + ': ' + response.statusText,
                        'ajax_error',
                        response.url || window.location.href,
                        '',
                        text.substring(0, 500)
                    );
                }).catch(function() {
                    sendErrorToPHP(
                        'AJAX Error ' + response.status + ': ' + response.statusText,
                        'ajax_error',
                        response.url || window.location.href
                    );
                });
            }
        }
        return response;
    }).catch(function(error) {
        sendErrorToPHP(
            'Fetch Error: ' + error.message,
            'fetch_error',
            window.location.href,
            '',
            error.stack || ''
        );
        throw error;
    });
};

// Interceptar console.error
const originalConsoleError = console.error;
console.error = function() {
    const args = Array.from(arguments);
    const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' ');
    const stack = args.map(arg => arg instanceof Error ? arg.stack : '').join('\n');
    sendErrorToPHP(message, 'error', window.location.href, '', stack);
    return originalConsoleError.apply(console, args);
};

// Interceptar console.warn
const originalConsoleWarn = console.warn;
console.warn = function() {
    const args = Array.from(arguments);
    const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' ');
    sendErrorToPHP(message, 'warn', window.location.href);
    return originalConsoleWarn.apply(console, args);
};

// Interceptar console.log (solo errores importantes)
const originalConsoleLog = console.log;
console.log = function() {
    const args = Array.from(arguments);
    // Solo registrar si contiene palabras clave de error
    const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' ');
    if (message.toLowerCase().includes('error') || message.toLowerCase().includes('❌')) {
        sendErrorToPHP(message, 'log', window.location.href);
    }
    return originalConsoleLog.apply(console, args);
};

// Interceptar errores no capturados
window.addEventListener('error', function(event) {
    sendErrorToPHP(
        event.message || 'Error de JavaScript',
        'unhandled',
        event.filename || window.location.href,
        event.lineno || '',
        event.error ? event.error.stack : ''
    );
});

// Interceptar promesas rechazadas no manejadas
window.addEventListener('unhandledrejection', function(event) {
    const reason = event.reason;
    const message = reason instanceof Error ? reason.message : String(reason);
    const stack = reason instanceof Error ? reason.stack : '';
    sendErrorToPHP(message, 'unhandled_promise', window.location.href, '', stack);
});

// Interceptar jQuery AJAX errores
if (typeof jQuery !== 'undefined') {
    jQuery(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        let message = 'jQuery AJAX Error';
        if (jqXHR.status) {
            message += ' ' + jqXHR.status;
        }
        if (thrownError) {
            message += ': ' + thrownError;
        }
        
        let responseText = '';
        try {
            const response = JSON.parse(jqXHR.responseText);
            responseText = JSON.stringify(response);
        } catch (e) {
            responseText = jqXHR.responseText || '';
        }
        
        sendErrorToPHP(
            message,
            'jquery_ajax_error',
            ajaxSettings.url || window.location.href,
            '',
            responseText.substring(0, 1000)
        );
    });
}
