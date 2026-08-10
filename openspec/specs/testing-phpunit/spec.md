# testing-phpunit

## Purpose
Asegurar la calidad del código mediante pruebas unitarias y de integración con PHPUnit 9.6, incluyendo suites organizadas, cobertura de código, base de datos de pruebas transaccional y helpers con mocks.


### Requirement: Suite de pruebas PHPUnit
El sistema SHALL ejecutar pruebas con PHPUnit 9.6 organizadas en 7 suites: Unit, Integration, API, Database, Security, Performance, E2E.

#### Scenario: Ejecución de suite unit
- **WHEN** se ejecuta phpunit --testsuite Unit
- **THEN** corren las pruebas unitarias sin dependencias externas

### Requirement: Cobertura de código
El sistema SHALL generar reportes de cobertura para models/, controllers/, services/, helpers/.

#### Scenario: Reporte de cobertura
- **WHEN** se ejecuta phpunit --coverage-html
- **THEN** genera reporte HTML con cobertura por archivo

### Requirement: Pruebas unitarias existentes
El sistema SHALL incluir pruebas para: Database, SecurityHelper, CacheManager, EmpleadoController, Auth2FA, Retardo (suspensión por 5 notas), ComisionModel, Soporte (permisos de archivos), AsistenciaService, AuthController.

#### Scenario: Prueba de suspensión por 5 notas
- **WHEN** se ejecuta RetardoTest::testSuspensionPorCincoNotas
- **THEN** valida que 5 retardos no justificados crean suspensión automática

### Requirement: Base de datos de pruebas
El sistema SHALL usar BD separada (sistema_biometrico_test) con datos de prueba transaccionales (rollback en tearDown).

#### Scenario: Test transaccional
- **WHEN** se ejecuta prueba de integración
- **THEN** los cambios se revierten al finalizar la prueba

### Requirement: Mocks y helpers
El sistema SHALL proveer funciones helper: createTestDatabase(), cleanupTestDatabase(), getMockEmpleado() y MockLogger.

#### Scenario: Mock de empleado
- **WHEN** se usa getMockEmpleado() en una prueba
- **THEN** retorna datos simulados de empleado
