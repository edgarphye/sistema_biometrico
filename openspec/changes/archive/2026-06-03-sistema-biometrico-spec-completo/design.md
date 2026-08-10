## Context

El Sistema Biométrico de Control de Asistencia es una aplicación web PHP 8.4 desarrollada sin framework, siguiendo un patrón MVC personalizado con Front Controller. Sirve a una organización gubernamental (SEP - AEFCM) con ~13 empleados activos, 2 dispositivos biométricos ZKTeco configurados y proyección a 35 dispositivos. El sistema gestiona el ciclo completo de control de asistencia: registro biométrico, clasificación de retardos, justificaciones, validación jerárquica, sanciones, días económicos, comisiones, vacaciones, análisis predictivo con IA y generación de reportes.

Actualmente existe documentación extensa (~640 KB en 50+ archivos .md) pero está dispersa, desorganizada y carece de una especificación formal y centralizada. El código fuente tiene ~82% de funcionalidad implementada pero solo 10% de cobertura de pruebas.

**Stack actual:**
- **Backend**: PHP 8.4, custom MVC con FastRoute v1.3
- **Database**: MariaDB/MySQL con PDO (prepared statements reales)
- **Frontend**: Bootstrap 5.3, jQuery 3.7, DataTables 1.13, Chart.js 4.4, Font Awesome 6.5
- **Biométrico**: `jmrashed/zkteco` v1.2 + TCP socket directo (puerto 4370) + parser archivos .dat
- **DevOps**: GitHub Actions, Prometheus/Grafana, scripts bash
- **Testing**: PHPUnit 9.6, Cypress 13.6

**Arquitectura actual:**
- Front Controller: `index.php` → `config.php` → `routes.php` (FastRoute) → Controller → Model/Service → View (layout.php + content)
- 36 controladores, 39+ modelos (core + biométricos + ZKTeco), 16 servicios, 12 helpers
- 50+ tablas en BD, migraciones con Phinx 0.16
- 2FA (TOTP demo-grade), CSRF tokens, sesiones en DB, encriptación AES-256-CBC para huellas
- 7 suites de prueba PHPUnit, tests E2E con Cypress

## Goals / Non-Goals

**Goals:**
- Crear una especificación formal y centralizada de todo el sistema, organizada por capacidades
- Documentar las 36 capacidades funcionales con sus requisitos, reglas de negocio y modelos de datos
- Definir la arquitectura de cada subsistema de manera clara y ejecutable
- Establecer el contrato formal entre especificación e implementación para guiar el desarrollo futuro
- Identificar brechas y oportunidades de mejora en el sistema actual
- Servir como fuente única de verdad (SSOT) para todo el equipo de desarrollo

**Non-Goals:**
- No es una reescritura del sistema (no se propone cambiar el stack tecnológico)
- No es un plan de migración a otro framework
- No es una guía de usuario final (eso es documentación separada)
- No incluye implementación de código (solo especificación)
- No reemplaza la documentación existente sino que la consolida y estructura

## Decisions

### 1. Especificación por Capacidades vs. por Capas
- **Decisión**: Organizar la especificación por **capacidades funcionales** (dominios de negocio) en lugar de por capas técnicas (controllers, models, views).
- **Rationale**: Cada capacidad del spec agrupa controller(s), model(s), service(s), view(s) y rutas relacionadas, reflejando cómo los desarrolladores realmente navegan el código. Una organización por capas dispersaría la información de cada funcionalidad.
- **Alternativa**: Organización por capas (controladores, modelos, etc.) — rechazada porque dificulta entender flujos completos.

### 2. Formato de Spec
- **Decisión**: Cada capacidad sigue una plantilla estructurada con: Descripción, Requisitos Funcionales, Reglas de Negocio, Modelo de Datos, API/Routes, UI/UX, Dependencias, Brechas.
- **Rationale**: Provee información completa y accionable para un desarrollador, incluyendo tanto el "qué" como las reglas de negocio que gobiernan cada capacidad.
- **Alternativa**: Spec minimalista solo con requisitos funcionales — rechazado porque las reglas de negocio AEFCM/SEP son críticas y complejas.

### 3. Priorización de Capacidades
- **Decisión**: Las capacidades se ordenan del núcleo del sistema hacia afuera: empleados → asistencia → retardos → justificaciones/validaciones → sanciones → reportes → IA → infraestructura.
- **Rationale**: Refleja la cadena de valor del sistema y la dependencia natural entre módulos.

### 4. Documentación de Reglas de Negocio
- **Decisión**: Incluir las reglas de negocio AEFCM/SEP directamente en los specs de capacidad correspondiente, con referencias cruzadas.
- **Rationale**: Estas reglas son críticas para el correcto funcionamiento (ej: clasificación de retardos, fórmula de notas malas, límites de comisiones) y actualmente solo existen en código o documentación dispersa.

### 5. Gestión de Brechas (Gap Analysis)
- **Decisión**: Cada spec incluye una sección de "Brechas" que documenta funcionalidades no implementadas, problemas conocidos y áreas de mejora.
- **Rationale**: La especificación no solo describe el estado actual sino que guía el trabajo futuro, cerrando el ciclo de mejora continua.

## Risks / Trade-offs

- **[Tamaño del spec]** El spec completo será extenso (~36 capacidades). **Mitigación**: Cada capacidad se mantiene enfocada; la navegación es directa por archivo.
- **[Desactualización]** La especificación puede quedar obsoleta si no se mantiene. **Mitigación**: Se integra con el workflow de OpenSpec donde los cambios requieren delta specs antes de implementar.
- **[Completitud]** Pueden existir funcionalidades no documentadas. **Mitigación**: La revisión es exhaustiva basada en análisis de código, documentación y BD; se marcan áreas inciertas para revisión.
- **[Reglas de negocio cambiantes]** Las reglas AEFCM/SEP pueden cambiar. **Mitigación**: Las reglas se documentan con su fuente y fecha; cambios futuros requieren delta specs.
- **[Cobertura de pruebas]** Solo 10% de cobertura actual. El spec no resuelve esto pero lo visibiliza en cada capacidad.
- **[Seguridad]** Brechas de seguridad identificadas (CBC sin HMAC, CSP inseguro, exclusiones CSRF). El spec las documenta como brechas para priorizar su corrección.
