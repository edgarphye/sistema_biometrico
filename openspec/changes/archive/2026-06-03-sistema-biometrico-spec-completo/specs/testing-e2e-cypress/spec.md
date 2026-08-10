## ADDED Requirements

### Requirement: Pruebas E2E con Cypress
El sistema SHALL ejecutar pruebas E2E con Cypress 13.6 en tests/E2E/ cubriendo: autenticación, gestión de empleados, asistencia, reportes, diseño responsive, accesibilidad, manejo de errores, rendimiento y seguridad.

#### Scenario: Login E2E
- **WHEN** Cypress navega a /login, ingresa credenciales correctas
- **THEN** verifica redirección al dashboard

### Requirement: Comandos personalizados
El sistema SHALL proveer comandos Cypress personalizados: login(), logout(), waitForLoader(), createEmployee(), searchEmployee(), verifyEmployeeInTable(), checkResponsive(), checkAccessibility(), checkPerformance(), checkForErrors(), checkSecurityHeaders(), navigateByKeyboard(), verifyFormValidation().

#### Scenario: Comando login
- **WHEN** se usa cy.login("admin", "password")
- **THEN** Cypress completa el flujo de autenticación

### Requirement: Reportes multi-formato
El sistema SHALL generar reportes de pruebas E2E en formato mochawesome (JSON/HTML) y JUnit (XML).

#### Scenario: Reporte generado
- **WHEN** se ejecuta cypress run
- **THEN** genera reportes en tests/E2E/results/

### Requirement: Pruebas responsive
El sistema SHALL probar en 7 viewports (320px a 1920px) incluyendo dispositivos móviles (iPhone SE/8/11) y tablet (iPad).

#### Scenario: Prueba mobile
- **WHEN** Cypress prueba en viewport 375x667 (iPhone SE)
- **THEN** verifica que la UI es funcional y legible
