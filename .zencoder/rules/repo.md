---
description: Repository Information Overview
alwaysApply: true
---

# Sistema Biométrico de Control de Asistencia

## Summary
A comprehensive PHP-based biometric attendance control system supporting fingerprint and facial recognition with CKTeco SDK integration. Implements a complete MVC architecture for managing employee records, attendance tracking, time calculations, and HR operations including absences, commissions, and disciplinary actions.

## Structure
- **controllers/**: Request routing and business logic (14 controller files)
- **models/**: Data persistence layer with database interactions
- **views/**: HTML/PHP templates organized by feature (asistencia, empleados, dispositivos, etc.)
- **services/**: Business service layer (AsistenciaService, ReporteService)
- **migrations/**: Database schema and setup scripts
- **tests/**: PHPUnit test suites (16 test files)
- **public/**: Static assets (CSS, JavaScript, images)
- **uploads/**: Employee photos and justification documents
- **helpers/**: Utility functions (CSRF validation, encryption, dashboard logic)

## Language & Runtime
**Language**: PHP  
**Version**: 7.4 or higher  
**Build System**: Composer (dependency manager)  
**Package Manager**: Composer  
**Entry Point**: index.php (router with regex-based URL routing)  
**Web Server**: Apache (with .htaccess) or Nginx

## Dependencies
**Main Dependencies**:
- PHPMailer/PHPMailer (^7.0) - Email sending
- PHPOffice/PhpSpreadsheet (^5.2) - Excel report generation
- Dompdf/dompdf (^3.1) - PDF document generation

**Development Dependencies**:
- PHPUnit/PHPUnit (9.6) - Unit testing framework

## Build & Installation
```bash
cd c:\tools\nginx\html\sistema_biometrico
composer install
php setup.php
php -S localhost:8000
```

**Database Setup Options**:
```bash
php setup.php
php migrations/run_migration.php
php migrations/verify_tables.php
```

## Database
**Type**: MySQL 5.7+ / MariaDB 10.0+  
**Configuration**: config.php  
**Schema File**: database.sql  
**Connection**: PDO with native password auth  
**Main Tables**: empleados, asistencia, biometricos, retardos, comisiones, ausencias, dispositivos_biometricos, sanciones, horarios

## Configuration
- **config.php**: Database credentials, app settings, biometric API endpoints
- **.env**: Environment variables (ENCRYPTION_KEY, API credentials)
- **phpunit.xml**: Test configuration
- **.htaccess**: URL rewriting for MVC routing

## Testing
**Framework**: PHPUnit 9.6  
**Test Location**: tests/ directory  
**Naming Convention**: test_*.php files and Unit/Functional subdirectories  
**Configuration File**: phpunit.xml with bootstrap: tests/bootstrap.php  
**Test Coverage**: Unit tests for database operations, encryption, RFC/CURP validation, attendance flows, support permissions

**Run Tests**:
```bash
vendor/bin/phpunit
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Functional
php tests/test_db.php
php tests/test_encryption.php
```

## Key Features & Components
**Biometric Integration**: Multi-device support (10 CKTeco devices), fingerprint verification, facial recognition integration

**Attendance Management**: Automatic entry/exit recording, 9-minute tolerance calculation for delays, classification system (minor <30min, major >30min)

**Employee Management**: RFC/CURP validation and generation, photo storage, hierarchical organization

**HR Functions**: Commission tracking, absence justification, disciplinary actions, schedule management

**Reporting**: Excel exports, PDF generation, attendance reports with filters

## Security
- CSRF token validation
- Encryption for sensitive biometric data (ENCRYPTION_KEY in .env)
- PDO prepared statements for SQL injection prevention
- Session-based authentication (AuthController)
- Error logging without sensitive data exposure
