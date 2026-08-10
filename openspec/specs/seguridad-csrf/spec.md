# seguridad-csrf

## Purpose
Proteger el sistema contra ataques CSRF mediante tokens de 32 bytes por sesión, con validación desde POST, GET y header, y rutas exceptuadas controladas.


### Requirement: Protección CSRF
El sistema SHALL generar y validar tokens CSRF de 32 bytes (64 caracteres hex) por sesión para todas las solicitudes POST.

#### Scenario: Token CSRF válido
- **WHEN** se envía POST con token CSRF correcto
- **THEN** el sistema permite la solicitud

#### Scenario: Token CSRF inválido
- **WHEN** se envía POST sin token o con token incorrecto
- **THEN** el sistema rechaza con error 403

### Requirement: Validación multi-fuente
El sistema SHALL validar CSRF desde POST, GET y header X-CSRF-TOKEN.

#### Scenario: Validación desde header
- **WHEN** se envía header X-CSRF-TOKEN con token válido
- **THEN** el sistema permite la solicitud

### Requirement: Excepciones controladas
El sistema SHALL exceptuar rutas específicas de validación CSRF (login, register, API, validaciones, biométricos, catálogos).

#### Scenario: Ruta exceptuada
- **WHEN** se envía POST a /login sin CSRF
- **THEN** el sistema permite la solicitud (ruta pública)
