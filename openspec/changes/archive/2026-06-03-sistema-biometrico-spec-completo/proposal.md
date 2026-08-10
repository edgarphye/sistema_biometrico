## Why

El Sistema Biométrico de Control de Asistencia es una aplicación PHP 8.4 MVC personalizada (sin framework) para la gestión integral de asistencia, retardos, justificaciones, comisiones, vacaciones, sanciones y validaciones jerárquicas, integrada con dispositivos biométricos ZKTeco. Actualmente el sistema tiene ~82% de funcionalidad implementada pero carece de una especificación técnica formal y centralizada que documente todos los módulos, reglas de negocio, flujos de trabajo y lineamientos de arquitectura. Este cambio crea la especificación completa del sistema, consolidando el conocimiento disperso en más de 50 archivos documentación `.md` en una estructura de spec formal y ejecutable para guiar el desarrollo futuro, cierre de brechas y mantenimiento.

## What Changes

- Creación de la especificación formal de todo el sistema, organizada por capacidades
- Documentación de las 36 capacidades funcionales principales del sistema
- Definición de reglas de negocio formales (retardos, sanciones, días económicos, comisiones, etc.)
- Mapeo completo de arquitectura: MVC, routing, seguridad, integración biométrica
- Definición de modelos de datos con 50+ tablas y sus relaciones
- Catalogación de la API (routes, AJAX, endpoints REST)
- Documentación del sistema de autenticación multi-factor, roles y permisos
- Documentación del pipeline CI/CD, monitoreo y deployment
- Definición de la arquitectura de módulos de IA/ML (análisis predictivo, agente inteligente)

## Capabilities

### New Capabilities

- `empleados`: Gestión del catálogo de empleados (CRUD, RFC/CURP, foto, huella, jerarquías)
- `usuarios-roles`: Gestión de usuarios del sistema con RBAC (admin, rh, supervisor, jefe, empleado, viewer)
- `asistencia`: Registro diario de entrada/salida, clasificación de tipo de asistencia
- `biometric-integration`: Integración multi-capa con dispositivos ZKTeco (SDK, TCP socket, archivos .dat, simulación)
- `horarios-laborales`: Definición de horarios, tolerancias, ventanas de marcaje, asignación a empleados
- `ciclos-trabajo`: Definición de ciclos rotativos con bloques semanales
- `retardos`: Captura, clasificación (menor/mayor/falta), justificación y evaluación de retardos
- `notas-malas`: Cálculo automático de notas malas por acumulación de retardos (2 menores = 1 nota mala)
- `sanciones`: Gestión de sanciones (suspensión, amonestación, descuento, acta administrativa)
- `justificaciones`: Registro de justificaciones con tipos, documentos, aprobación
- `comisiones`: Gestión de comisiones con límites AEFCM ($3000/mes), tipos y aprobación
- `dias-economicos`: Solicitud de días económicos con reglas SEP (máx 9/año, plazos de espera, modalidades)
- `vacaciones`: Solicitud y aprobación de vacaciones
- `validaciones-jefe`: Flujo de validación jefe-subordinado con comunicación bidireccional (7+ tipos de incidencia)
- `tipos-justificacion`: Catálogo parametrizable de tipos de justificación (17+ tipos pre-seed)
- `catalogos-organigrama`: Catálogos jerárquicos 3 niveles (direcciones, subdirecciones, departamentos) + mandos
- `reglas-negocio-ia`: Motor de reglas de negocio para agente IA (horario, tolerancia, retardo, falta, sanción)
- `analisis-predictivo`: Predicción de riesgos, alertas tempranas, análisis de patrones por áreas
- `agente-inteligente`: Agente IA para procesamiento automático de incidencias, decisiones y recomendaciones
- `reportes-excel`: Generación de reportes en Excel (PhpSpreadsheet) con filtros avanzados
- `exportacion-pdf-word`: Exportación a PDF (Dompdf) y Word (PhpWord)
- `notificaciones-email`: Notificaciones por correo electrónico vía PHPMailer
- `autenticacion-2fa`: Autenticación de dos factores (TOTP, email, SMS)
- `seguridad-csrf`: Protección CSRF con tokens de 64 caracteres
- `sesiones-base-datos`: Manejo de sesiones en tabla `sessions` con hardening
- `auditoria-logs`: Logs de auditoría y seguimiento de cambios en justificaciones
- `migraciones-bd`: Sistema de migraciones con Phinx 0.16
- `backup-restore`: Sistema de backup/restore de base de datos con integridad SHA256
- `monitoreo-prometheus-grafana`: Monitoreo con Prometheus, Grafana y alertas
- `ci-cd-pipeline`: Pipeline CI/CD con GitHub Actions (tests, security scan, deploy)
- `testing-phpunit`: Suite de tests con PHPUnit 9.6 (7 suites: unit, integration, API, DB, security, performance, E2E)
- `testing-e2e-cypress`: Tests E2E con Cypress 13.6
- `dispositivos-biometricos-admin`: Administración de dispositivos, configuración IP, sincronización
- `huellas-dactilares`: Captura y almacenamiento de huellas (AES-256-CBC), enrolamiento en dispositivos
- `menu-config`: Configuración de menú por usuario/rol
- `mantenimiento-sistema`: Panel de mantenimiento (diagnóstico, limpieza, estadísticas, seguridad)
- `ai-cron-procesamiento`: Procesos programados de IA (análisis nocturno, resumen diario)

### Modified Capabilities

*(No existing specs to modify - this is the initial specification)*

## Impact

- **Modelos de datos**: 50+ tablas documentadas formalmente en el spec
- **Reglas de negocio**: Tolerancias, clasificación de retardos, notas malas, sanciones, días económicos, comisiones, límites AEFCM
- **API**: ~170+ rutas FastRoute, 16 endpoints AJAX, 10+ endpoints REST documentados
- **Seguridad**: CSRF, 2FA, sesiones DB, roles RBAC, encriptación AES-256, hardening de sesiones
- **Integración biométrica**: ZKTeco SDK (`jmrashed/zkteco` v1.2), conexión TCP socket (puerto 4370), parser de archivos .dat
- **Frontend**: Bootstrap 5.3, jQuery 3.7, DataTables 1.13, Chart.js 4.4, Font Awesome 6.5
- **DevOps**: GitHub Actions, Prometheus/Grafana, scripts de backup/deploy
