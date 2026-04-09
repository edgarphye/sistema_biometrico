// Comandos personalizados para Cypress

// Comando de login
Cypress.Commands.add('login', (username = Cypress.env('username'), password = Cypress.env('password')) => {
  cy.visit('/login');
  cy.get('[data-cy=username-input]').type(username);
  cy.get('[data-cy=password-input]').type(password);
  cy.get('[data-cy=login-button]').click();
  cy.url().should('not.include', '/login');
  cy.get('[data-cy=user-menu]').should('be.visible');
});

// Comando de logout
Cypress.Commands.add('logout', () => {
  cy.get('[data-cy=user-menu]').click();
  cy.get('[data-cy=logout-link]').click();
  cy.url().should('include', '/login');
});

// Comando de espera de loader
Cypress.Commands.add('waitForLoader', (timeout = 10000) => {
  cy.get('[data-cy=loader]', { timeout }).should('not.exist');
});

// Comando de creación de empleado
Cypress.Commands.add('createEmployee', (employeeData) => {
  cy.visit('/empleados/create');
  
  // Llenar formulario
  if (employeeData.nombre) {
    cy.get('[data-cy=nombre-input]').type(employeeData.nombre);
  }
  
  if (employeeData.apellido) {
    cy.get('[data-cy=apellido-input]').type(employeeData.apellido);
  }
  
  if (employeeData.rfc) {
    cy.get('[data-cy=rfc-input]').type(employeeData.rfc);
  }
  
  if (employeeData.email) {
    cy.get('[data-cy=email-input]').type(employeeData.email);
  }
  
  if (employeeData.telefono) {
    cy.get('[data-cy=telefono-input]').type(employeeData.telefono);
  }
  
  if (employeeData.area) {
    cy.get('[data-cy=area-select]').select(employeeData.area);
  }
  
  if (employeeData.puesto) {
    cy.get('[data-cy=puesto-input]').type(employeeData.puesto);
  }
  
  // Enviar formulario
  cy.get('[data-cy=save-button]').click();
  cy.waitForLoader();
  
  // Verificar éxito
  cy.get('[data-cy=success-message]').should('be.visible');
  cy.url().should('include', '/empleados');
});

// Comando de búsqueda de empleados
Cypress.Commands.add('searchEmployee', (searchTerm) => {
  cy.get('[data-cy=search-input]').clear().type(searchTerm);
  cy.get('[data-cy=search-button]').click();
  cy.waitForLoader();
});

// Comando de verificación de tabla
Cypress.Commands.add('verifyEmployeeInTable', (employeeData) => {
  cy.get('[data-cy=employees-table]').should('be.visible');
  cy.get('[data-cy=employee-row]').should('contain', employeeData.nombre);
  cy.get('[data-cy=employee-row]').should('contain', employeeData.email);
  if (employeeData.area) {
    cy.get('[data-cy=employee-row]').should('contain', employeeData.area);
  }
});

// Comando de chequeo de responsividad
Cypress.Commands.add('checkResponsive', () => {
  const viewports = [
    { width: 320, height: 568 },  // iPhone SE
    { width: 375, height: 667 },  // iPhone 8
    { width: 414, height: 896 },  // iPhone 11
    { width: 768, height: 1024 }, // iPad
    { width: 1024, height: 768 }, // iPad Pro
    { width: 1280, height: 720 }, // Desktop
    { width: 1920, height: 1080 } // Large Desktop
  ];
  
  viewports.forEach(viewport => {
    cy.viewport(viewport.width, viewport.height);
    cy.get('body').should('be.visible');
    
    // Verificar elementos clave
    cy.get('[data-cy=header]').should('be.visible');
    cy.get('[data-cy=sidebar]').should('be.visible');
    cy.get('[data-cy=main-content]').should('be.visible');
    
    // Para móviles, el sidebar debe estar oculto por defecto
    if (viewport.width < 768) {
      cy.get('[data-cy=sidebar]').should('have.class', 'collapsed');
    }
  });
});

// Comando de verificación de accesibilidad
Cypress.Commands.add('checkAccessibility', () => {
  // Verificar atributos ARIA
  cy.get('button').each(($el) => {
    const text = $el.text().trim();
    if (text) {
      cy.wrap($el).should('have.attr', 'aria-label');
    }
  });
  
  // Verificar enfoques
  cy.get('input, select, textarea, button').each(($el) => {
    cy.wrap($el).should('not.have.attr', 'tabindex', '-1');
  });
  
  // Verificar contrast ratios (básico)
  cy.get('[data-cy=main-content]').should('have.css', 'color');
  cy.get('[data-cy=main-content]').should('have.css', 'background-color');
});

// Comando de verificación de performance
Cypress.Commands.add('checkPerformance', () => {
  cy.window().then((win) => {
    const perfData = win.performance.timing;
    const loadTime = perfData.loadEventEnd - perfData.navigationStart;
    
    // La página debe cargar en menos de 3 segundos
    expect(loadTime).to.be.lessThan(3000);
    
    // Verificar First Contentful Paint
    if (win.performance.getEntriesByType) {
      const paintEntries = win.performance.getEntriesByType('paint');
      const fcp = paintEntries.find(entry => entry.name === 'first-contentful-paint');
      
      if (fcp) {
        // FCP debe ser menor a 1.5 segundos
        expect(fcp.startTime).to.be.lessThan(1500);
      }
    }
  });
});

// Comando de verificación de errores
Cypress.Commands.add('checkForErrors', () => {
  // Verificar errores de JavaScript en consola
  cy.window().then((win) => {
    const errors = win.console.error;
    if (errors && errors.length > 0) {
      cy.log('Console errors:', errors);
    }
  });
  
  // Verificar errores de red (4xx, 5xx)
  cy.get('@networkErrors').then((errors) => {
    if (errors && errors.length > 0) {
      cy.log('Network errors:', errors);
    }
  });
});

// Comando de verificación de seguridad
Cypress.Commands.add('checkSecurityHeaders', () => {
  cy.request('/').then((response) => {
    const headers = response.headers;
    
    // Verificar headers de seguridad básicos
    expect(headers).to.have.property('x-frame-options');
    expect(headers).to.have.property('x-content-type-options');
    expect(headers).to.have.property('x-xss-protection');
    expect(headers).to.have.property('content-security-policy');
  });
});

// Comando de navegación por teclado
Cypress.Commands.add('navigateByKeyboard', (path) => {
  cy.visit(path);
  
  // Tab través de elementos interactivos
  cy.get('body').tab();
  cy.focused().should('exist');
  
  // Presionar Enter en elementos enfocados
  cy.focused().type('{enter}');
});

// Comando de verificación de formulario
Cypress.Commands.add('verifyFormValidation', (formSelector, validationRules) => {
  cy.get(formSelector).within(() => {
    Object.entries(validationRules).forEach(([field, rules]) => {
      cy.get(`[data-cy=${field}-input]`).clear();
      
      // Verificar campo requerido
      if (rules.required) {
        cy.get(`[data-cy=${field}-input]`).blur();
        cy.get(`[data-cy=${field}-error]`).should('be.visible');
      }
      
      // Verificar formato específico
      if (rules.pattern) {
        cy.get(`[data-cy=${field}-input]`).type('invalid-value');
        cy.get(`[data-cy=${field}-input]`).blur();
        cy.get(`[data-cy=${field}-error]`).should('be.visible');
      }
      
      // Verificar valor válido
      if (rules.validValue) {
        cy.get(`[data-cy=${field}-input]`).clear().type(rules.validValue);
        cy.get(`[data-cy=${field}-input]`).blur();
        cy.get(`[data-cy=${field}-error]`).should('not.exist');
      }
    });
  });
});

// Sobrescribir visit para incluir mediciones
const originalVisit = cy.visit;
cy.visit = (url, options = {}) => {
  const startTime = Date.now();
  
  return originalVisit(url, options).then((window) => {
    const endTime = Date.now();
    const visitTime = endTime - startTime;
    
    cy.log(`Page load time: ${visitTime}ms`);
    
    // Verificar que la carga no sea muy lenta
    expect(visitTime).to.be.lessThan(5000);
    
    return window;
  });
};

// Exportar comandos
export {};