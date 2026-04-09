describe('Sistema Biométrico - E2E Tests', () => {
  beforeEach(() => {
    // Configurar viewport estándar
    cy.viewport(1280, 720);
    
    // Limpiar estado antes de cada test
    cy.clearCookies();
    cy.clearLocalStorage();
    
    // Interceptar errores de red para logging
    cy.intercept('*', { middleware: true }, (req) => {
      req.on('after:response', (res) => {
        if (res.statusCode >= 400) {
          cy.log(`API Error: ${req.method} ${req.url} - ${res.statusCode}`);
        }
      });
    });
  });

  describe('Autenticación', () => {
    it('should allow user to login with valid credentials', () => {
      cy.visit('/login');
      
      // Verificar elementos de login
      cy.get('[data-cy=login-form]').should('be.visible');
      cy.get('[data-cy=username-input]').should('be.visible');
      cy.get('[data-cy=password-input]').should('be.visible');
      cy.get('[data-cy=login-button]').should('be.visible');
      
      // Intentar login
      cy.login(Cypress.env('username'), Cypress.env('password'));
      
      // Verificar redirección exitosa
      cy.url().should('not.include', '/login');
      cy.get('[data-cy=user-menu]').should('be.visible');
      cy.get('[data-cy=user-menu]').should('contain', 'admin');
      
      // Verificar elementos del dashboard
      cy.get('[data-cy=dashboard]').should('be.visible');
      cy.get('[data-cy=stats-cards]').should('be.visible');
      
      cy.checkPerformance();
    });

    it('should reject invalid credentials', () => {
      cy.visit('/login');
      
      // Intentar login con credenciales inválidas
      cy.get('[data-cy=username-input]').type('invalid@test.com');
      cy.get('[data-cy=password-input]').type('wrongpassword');
      cy.get('[data-cy=login-button]').click();
      
      // Verificar mensaje de error
      cy.get('[data-cy=error-message]').should('be.visible');
      cy.get('[data-cy=error-message]').should('contain', 'Credenciales inválidas');
      
      // Verificar que permanezca en login
      cy.url().should('include', '/login');
    });

    it('should allow user to logout', () => {
      cy.login();
      
      // Logout
      cy.logout();
      
      // Verificar redirección a login
      cy.url().should('include', '/login');
      cy.get('[data-cy=login-form]').should('be.visible');
    });
  });

  describe('Gestión de Empleados', () => {
    beforeEach(() => {
      cy.login();
    });

    it('should display employees list', () => {
      cy.visit('/empleados');
      
      // Verificar elementos de la lista
      cy.get('[data-cy=employees-table]').should('be.visible');
      cy.get('[data-cy=search-input]').should('be.visible');
      cy.get('[data-cy=filter-area]').should('be.visible');
      cy.get('[data-cy=pagination]').should('be.visible');
      
      // Esperar carga de datos
      cy.waitForLoader();
      
      // Verificar que haya empleados (al menos el admin)
      cy.get('[data-cy=employee-row]').should('have.length.greaterThan', 0);
      
      cy.checkResponsive();
    });

    it('should create new employee', () => {
      const employeeData = {
        nombre: 'Juan',
        apellido: 'Pérez',
        rfc: 'PEJN800101HDFXXX01',
        email: 'juan.perez@example.com',
        telefono: '5551234567',
        area: 'TI',
        puesto: 'Desarrollador'
      };

      cy.createEmployee(employeeData);
      
      // Verificar que el nuevo empleado esté en la tabla
      cy.searchEmployee(employeeData.email);
      cy.verifyEmployeeInTable(employeeData);
    });

    it('should edit existing employee', () => {
      // Primero crear un empleado para editar
      const employeeData = {
        nombre: 'María',
        apellido: 'González',
        rfc: 'GOGM750829MDFXXX01',
        email: 'maria.gonzalez@example.com',
        telefono: '5559876543',
        area: 'Recursos Humanos',
        puesto: 'Analista'
      };

      cy.createEmployee(employeeData);
      
      // Buscar y editar
      cy.searchEmployee(employeeData.email);
      cy.get('[data-cy=edit-employee-btn]').first().click();
      
      // Modificar datos
      const updatedData = {
        puesto: 'Senior Analista',
        telefono: '5551112222'
      };
      
      cy.get('[data-cy=puesto-input]').clear().type(updatedData.puesto);
      cy.get('[data-cy=telefono-input]').clear().type(updatedData.telefono);
      cy.get('[data-cy=save-button]').click();
      cy.waitForLoader();
      
      // Verificar actualización
      cy.searchEmployee(employeeData.email);
      cy.get('[data-cy=employee-row]').should('contain', updatedData.puesto);
      cy.get('[data-cy=employee-row]').should('contain', updatedData.telefono);
    });

    it('should delete employee', () => {
      // Crear empleado para eliminar
      const employeeData = {
        nombre: 'Carlos',
        apellido: 'López',
        rfc: 'LOLC900101HDFXXX01',
        email: 'carlos.lopez@example.com',
        telefono: '5555555555',
        area: 'Ventas',
        puesto: 'Vendedor'
      };

      cy.createEmployee(employeeData);
      
      // Buscar y eliminar
      cy.searchEmployee(employeeData.email);
      cy.get('[data-cy=delete-employee-btn]').first().click();
      
      // Confirmar eliminación
      cy.get('[data-cy=confirm-delete-btn]').click();
      cy.waitForLoader();
      
      // Verificar que no esté en la tabla
      cy.searchEmployee(employeeData.email);
      cy.get('[data-cy=no-results]').should('be.visible');
    });

    it('should filter employees by area', () => {
      cy.visit('/empleados');
      cy.waitForLoader();
      
      // Filtrar por área
      cy.get('[data-cy=filter-area]').select('TI');
      cy.get('[data-cy=apply-filter-btn]').click();
      cy.waitForLoader();
      
      // Verificar que todos los resultados sean del área filtrada
      cy.get('[data-cy=employee-row]').each(($row) => {
        cy.wrap($row).should('contain', 'TI');
      });
    });

    it('should handle pagination correctly', () => {
      cy.visit('/empleados');
      cy.waitForLoader();
      
      // Verificar controles de paginación
      cy.get('[data-cy=page-size-select]').should('be.visible');
      cy.get('[data-cy=prev-page-btn]').should('be.visible');
      cy.get('[data-cy=next-page-btn]').should('be.visible');
      cy.get('[data-cy=current-page]').should('be.visible');
      
      // Cambiar tamaño de página
      cy.get('[data-cy=page-size-select]').select('50');
      cy.waitForLoader();
      
      // Verificar que se muestren más resultados
      cy.get('[data-cy=employee-row]').should('have.length.at.most', 50);
    });
  });

  describe('Registro de Asistencia', () => {
    beforeEach(() => {
      cy.login();
    });

    it('should record check-in successfully', () => {
      cy.visit('/asistencia/registro');
      
      // Verificar formulario de registro
      cy.get('[data-cy=check-in-form]').should('be.visible');
      cy.get('[data-cy=employee-select]').should('be.visible');
      cy.get('[data-cy=check-in-btn]').should('be.visible');
      
      // Seleccionar empleado
      cy.get('[data-cy=employee-select]').select(1); // Admin user
      
      // Registrar entrada
      cy.get('[data-cy=check-in-btn]').click();
      cy.waitForLoader();
      
      // Verificar mensaje de éxito
      cy.get('[data-cy=success-message]').should('be.visible');
      cy.get('[data-cy=success-message]').should('contain', 'Entrada registrada');
    });

    it('should record check-out successfully', () => {
      // Primero registrar entrada
      cy.visit('/asistencia/registro');
      cy.get('[data-cy=employee-select]').select(1);
      cy.get('[data-cy=check-in-btn]').click();
      cy.waitForLoader();
      
      // Luego registrar salida
      cy.get('[data-cy=check-out-btn]').click();
      cy.waitForLoader();
      
      // Verificar mensaje de éxito
      cy.get('[data-cy=success-message]').should('be.visible');
      cy.get('[data-cy=success-message]').should('contain', 'Salida registrada');
    });
  });

  describe('Reportes', () => {
    beforeEach(() => {
      cy.login();
    });

    it('should generate attendance report', () => {
      cy.visit('/reportes/asistencia');
      
      // Verificar filtros de reporte
      cy.get('[data-cy=report-filters]').should('be.visible');
      cy.get('[data-cy=date-from]').should('be.visible');
      cy.get('[data-cy=date-to]').should('be.visible');
      cy.get('[data-cy=employee-filter]').should('be.visible');
      cy.get('[data-cy=generate-report-btn]').should('be.visible');
      
      // Configurar fechas
      const today = new Date();
      const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
      
      cy.get('[data-cy=date-from]').type(lastMonth.toISOString().split('T')[0]);
      cy.get('[data-cy=date-to]').type(today.toISOString().split('T')[0]);
      
      // Generar reporte
      cy.get('[data-cy=generate-report-btn]').click();
      cy.waitForLoader();
      
      // Verificar resultados
      cy.get('[data-cy=report-results]').should('be.visible');
      cy.get('[data-cy=report-table]').should('be.visible');
      cy.get('[data-cy=export-pdf-btn]').should('be.visible');
      cy.get('[data-cy=export-excel-btn]').should('be.visible');
    });

    it('should export report to PDF', () => {
      cy.visit('/reportes/asistencia');
      
      // Generar reporte primero
      cy.get('[data-cy=generate-report-btn]').click();
      cy.waitForLoader();
      
      // Exportar a PDF
      cy.get('[data-cy=export-pdf-btn]').click();
      
      // Verificar que se inicie la descarga
      cy.get('[data-cy=export-pdf-btn]').should('have.attr', 'download');
    });
  });

  describe('Responsive Design', () => {
    it('should work correctly on mobile devices', () => {
      cy.login();
      
      // Probar diferentes viewports móviles
      const mobileViewports = [
        { width: 320, height: 568 },  // iPhone SE
        { width: 375, height: 667 },  // iPhone 8
        { width: 414, height: 896 }   // iPhone 11
      ];
      
      mobileViewports.forEach(viewport => {
        cy.viewport(viewport.width, viewport.height);
        cy.log(`Testing viewport: ${viewport.width}x${viewport.height}`);
        
        // Verificar navegación móvil
        cy.get('[data-cy=mobile-menu-btn]').should('be.visible');
        cy.get('[data-cy=sidebar]').should('have.class', 'collapsed');
        
        // Abrir menú móvil
        cy.get('[data-cy=mobile-menu-btn]').click();
        cy.get('[data-cy=sidebar]').should('not.have.class', 'collapsed');
        
        // Navegar a empleados
        cy.get('[data-cy=nav-empleados]').click();
        cy.url().should('include', '/empleados');
        cy.waitForLoader();
        
        // Verificar tabla responsiva
        cy.get('[data-cy=employees-table]').should('be.visible');
        
        // Cerrar menú
        cy.get('[data-cy=mobile-menu-btn]').click();
        cy.get('[data-cy=sidebar]').should('have.class', 'collapsed');
      });
    });

    it('should handle tablet viewports', () => {
      cy.login();
      
      // Probar viewports de tablet
      cy.viewport(768, 1024); // iPad
      cy.visit('/empleados');
      cy.waitForLoader();
      
      // En tablets, sidebar puede estar visible o colapsado
      cy.get('[data-cy=sidebar]').should('be.visible');
      cy.get('[data-cy=employees-table]').should('be.visible');
    });
  });

  describe('Accessibility', () => {
    it('should be keyboard navigable', () => {
      cy.login();
      cy.visit('/empleados');
      cy.waitForLoader();
      
      // Navegar por teclado
      cy.get('body').tab();
      cy.focused().should('exist');
      
      // Navegar a través de elementos interactivos
      for (let i = 0; i < 10; i++) {
        cy.focused().type('{tab}');
        cy.focused().should('exist');
      }
    });

    it('should have proper ARIA labels', () => {
      cy.login();
      cy.visit('/empleados');
      cy.waitForLoader();
      
      // Verificar ARIA labels en botones
      cy.get('button').each(($el) => {
        const text = $el.text().trim();
        if (text) {
          cy.wrap($el).should('have.attr', 'aria-label').or('have.attr', 'title');
        }
      });
      
      // Verificar roles semánticos
      cy.get('main').should('have.attr', 'role', 'main');
      cy.get('nav').should('have.attr', 'role', 'navigation');
      cy.get('header').should('have.attr', 'role', 'banner');
    });
  });

  describe('Error Handling', () => {
    it('should handle network errors gracefully', () => {
      // Simular error de red
      cy.intercept('GET', '/api/empleados', { 
        statusCode: 500,
        body: { error: 'Internal Server Error' }
      }).as('networkError');
      
      cy.login();
      cy.visit('/empleados');
      
      // Esperar a que falle la carga
      cy.wait('@networkError');
      
      // Verificar mensaje de error
      cy.get('[data-cy=error-message]').should('be.visible');
      cy.get('[data-cy=retry-btn]').should('be.visible');
    });

    it('should show 404 page for invalid routes', () => {
      cy.login();
      cy.visit('/pagina-inexistente');
      
      // Verificar página 404
      cy.get('[data-cy=404-page]').should('be.visible');
      cy.get('[data-cy=404-message]').should('be.visible');
      cy.get('[data-cy=back-home-btn]').should('be.visible');
    });
  });

  describe('Performance', () => {
    it('should load pages within acceptable time', () => {
      cy.login();
      
      // Medir tiempo de carga de página de empleados
      cy.visit('/empleados');
      cy.checkPerformance();
      
      // Medir tiempo de carga de reportes
      cy.visit('/reportes/asistencia');
      cy.checkPerformance();
    });

    it('should handle large datasets efficiently', () => {
      cy.login();
      
      // Simular carga de muchos empleados
      cy.intercept('GET', '/api/empleados*', { 
        fixture: 'employees-large-dataset.json',
        delay: 1000 // Simular latency
      }).as('largeDataset');
      
      cy.visit('/empleados');
      cy.wait('@largeDataset');
      
      // Verificar que se muestre loading state
      cy.get('[data-cy=loader]').should('be.visible');
      
      // Esperar a que cargue
      cy.waitForLoader();
      
      // Verificar paginación para manejar grandes datasets
      cy.get('[data-cy=pagination]').should('be.visible');
    });
  });

  describe('Security', () => {
    it('should prevent XSS attacks', () => {
      cy.login();
      
      // Intentar inyectar XSS en búsqueda
      const xssPayload = '<script>alert("XSS")</script>';
      cy.visit('/empleados');
      cy.get('[data-cy=search-input]').type(xssPayload);
      cy.get('[data-cy=search-button]').click();
      
      // Verificar que no se ejecuta el script
      cy.on('window:alert', (str) => {
        expect(str).to.not.equal('XSS');
      });
      
      // Verificar que el input sea sanitizado
      cy.get('[data-cy=search-input]').should('not.contain', '<script>');
    });

    it('should have security headers', () => {
      cy.visit('/');
      cy.checkSecurityHeaders();
    });

    it('should prevent session hijacking', () => {
      cy.login();
      
      // Verificar que session cookie tenga flags seguros
      cy.getCookie('session_id').then((cookie) => {
        expect(cookie).to.have.property('httpOnly', true);
        expect(cookie).to.have.property('secure', true);
        expect(cookie).to.have.property('sameSite', 'Strict');
      });
    });
  });
});