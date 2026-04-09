// Importar comandos personalizados
import './commands';
import './setup';

// Configuración global para Cypress
Cypress.on('uncaught:exception', (err, runnable) => {
  // Ignorar errores específicos de terceros
  if (err.message.includes('ResizeObserver loop limit exceeded')) {
    return false;
  }
  
  if (err.message.includes('Non-Error promise rejection captured')) {
    return false;
  }
  
  // Logear otros errores pero no fallar los tests
  console.error('Uncaught exception:', err);
  return false;
});

// Interceptar y mockear peticiones si es necesario
beforeEach(() => {
  // Mockear API calls si se necesita testing offline
  cy.intercept('GET', '/api/health', {
    statusCode: 200,
    body: {
      status: 'ok',
      timestamp: new Date().toISOString(),
      version: '1.0.0'
    }
  }).as('healthCheck');
  
  // Mockear autenticación
  cy.intercept('POST', '/api/auth/login', (req) => {
    const { username, password } = req.body;
    
    if (username === Cypress.env('username') && password === Cypress.env('password')) {
      req.reply({
        statusCode: 200,
        body: {
          success: true,
          data: {
            user: {
              id: 1,
              username: 'admin',
              email: 'admin@test.com',
              role: 'admin'
            },
            token: 'mock-jwt-token-for-testing'
          }
        }
      });
    } else {
      req.reply({
        statusCode: 401,
        body: {
          success: false,
          message: 'Credenciales inválidas'
        }
      });
    }
  }).as('login');
});

// Limpiar después de cada test
afterEach(() => {
  // Limpiar cookies, localStorage, sessionStorage
  cy.clearCookies();
  cy.clearLocalStorage();
  cy.window().then((win) => {
    win.sessionStorage.clear();
  });
  
  // Esperar a que las peticiones pendientes terminen
  cy.wait(100);
});