# api-endpoints

## Purpose

Estandarizar los endpoints de API del sistema con formato JSON uniforme, detección de peticiones AJAX, validación CSRF, manejo de errores consistente y rutas de API documentadas para integración con el frontend y sistemas externos.

### Requirement: Formato de respuesta JSON estándar

El sistema SHALL responder con formato JSON uniforme en todos los endpoints: `{"success": bool, ...data...}` para éxito y `{"success": false, "error": string}` para error.

#### Scenario: Respuesta exitosa

- **WHEN** un endpoint API procesa correctamente la solicitud
- **THEN** el sistema retorna `{"success": true}` con datos adicionales según el endpoint

#### Scenario: Respuesta de error

- **WHEN** un endpoint API encuentra un error
- **THEN** el sistema retorna `{"success": false, "error": "<mensaje>"}` con código HTTP 4xx/5xx

### Requirement: Detección de petición AJAX

El sistema SHALL verificar el header `X-Requested-With: XMLHttpRequest` en endpoints que deban responder solo a peticiones AJAX.

#### Scenario: Petición AJAX válida

- **WHEN** se envía GET/POST con header `X-Requested-With: XMLHttpRequest`
- **THEN** el sistema procesa y retorna JSON

#### Scenario: Petición sin header AJAX

- **WHEN** se accede a un endpoint API sin el header AJAX
- **THEN** el sistema retorna error 400 con `{"success": false, "error": "Solicitud no válida"}`

### Requirement: Validación CSRF en API

El sistema SHALL validar token CSRF en todas las solicitudes POST a endpoints API, excepto en rutas públicas (login, register).

#### Scenario: POST con CSRF válido

- **WHEN** se envía POST a un endpoint API con header `X-CSRF-TOKEN` válido
- **THEN** el sistema permite la solicitud

#### Scenario: POST sin CSRF

- **WHEN** se envía POST a un endpoint API sin token CSRF
- **THEN** el sistema retorna error 403

### Requirement: Catálogo de endpoints API

El sistema SHALL documentar y mantener los siguientes grupos de endpoints:

#### Scenario: Endpoints de empleados

- **WHEN** se consulta `/api/empleados` (GET)
- **THEN** retorna lista paginada de empleados con filtros por área, jerarquía, nombre

#### Scenario: Endpoints de asistencia

- **WHEN** se consulta `/api/asistencia` (GET) con filtros por fecha, empleado, área
- **THEN** retorna registros de asistencia del período

#### Scenario: Endpoints de retardos

- **WHEN** se consulta `/api/retardos` (GET) con filtros
- **THEN** retorna registros de retardos

#### Scenario: Endpoints de justificaciones

- **WHEN** se consulta `/api/justificaciones` (GET)
- **THEN** retorna justificaciones con datos relacionados

#### Scenario: Endpoints de comisiones

- **WHEN** se consulta `/api/comisiones` (GET)
- **THEN** retorna comisiones con filtros

#### Scenario: Endpoints de estadísticas

- **WHEN** se consulta `/api/estadisticas` (GET)
- **THEN** retorna métricas agregadas del dashboard

#### Scenario: Endpoints de reportes

- **WHEN** se solicita `/api/reportes` (GET) con tipo de reporte y filtros
- **THEN** retorna datos estructurados para exportación

#### Scenario: Endpoints de catálogos

- **WHEN** se consulta `/api/catalogos/{tipo}` (GET)
- **THEN** retorna datos del catálogo solicitado (direcciones, subdirecciones, departamentos, áreas, horarios)

### Requirement: Paginación en listados

El sistema SHALL soportar paginación en endpoints que retornen listados con parámetros `page` y `limit`.

#### Scenario: Paginación con parámetros

- **WHEN** se envía GET con `?page=2&limit=20`
- **THEN** el sistema retorna `{"success": true, "data": [...], "page": 2, "limit": 20, "total": 150}`

### Requirement: Integración desde el frontend

El sistema SHALL centralizar las llamadas AJAX desde JavaScript usando `fetch()` con configuración estándar de headers CSRF y AJAX.

#### Scenario: Llamada AJAX desde JS

- **WHEN** el frontend llama a un endpoint API
- **THEN** envía headers `X-Requested-With: XMLHttpRequest` y `X-CSRF-TOKEN` tomado del meta tag

### Requirement: Endpoints públicos exceptuados de CSRF

El sistema SHALL exceptuar de validación CSRF las rutas: `/api/`, `/login`, `/register`, `/validaciones/`, `/biometricos/`, `/catalogos/`, `/usuarios/`, `/justificaciones/`, `/logs/`, `/menu-config/`.

#### Scenario: Ruta pública sin CSRF

- **WHEN** se envía POST a `/api/xxx` sin CSRF
- **THEN** el sistema permite la solicitud (ruta exceptuada)
