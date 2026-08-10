# sesiones-base-datos

## Purpose
Almacenar sesiones en base de datos con session handler personalizado, implementar hardening con validación de IP, User-Agent, timeout de inactividad y rate limiting.

### Requirement: Manejo de sesiones en base de datos
El sistema SHALL almacenar sesiones en tabla sessions con session handler personalizado (open, close, read, write, destroy, gc).

#### Scenario: Inicio de sesión
- **WHEN** usuario inicia sesión
- **THEN** el sistema crea registro en tabla sessions

### Requirement: Hardening de sesiones
El sistema SHALL implementar: HttpOnly cookies, Secure en HTTPS, SameSite Strict, regeneración de ID cada 30 min, validación de IP, validación de User-Agent, timeout de inactividad (30 min), rate limiting (5 intentos/15 min).

#### Scenario: Sesión rechazada por IP diferente
- **WHEN** la IP del cliente cambia respecto a la del inicio de sesión
- **THEN** el sistema destruye la sesión

#### Scenario: Timeout por inactividad
- **WHEN** pasan más de 30 minutos sin actividad
- **THEN** el sistema cierra la sesión

### Requirement: Rate limiting para login
El sistema SHALL implementar rate limiting en el login usando SessionSecurity con isBlocked() y recordFailedAttempt().

#### Scenario: Bloqueo por intentos fallidos
- **WHEN** un usuario falla 5 intentos de login en 15 minutos
- **THEN** SessionSecurity::isBlocked() retorna true y el login es rechazado

#### Scenario: Registro de intento fallido
- **WHEN** falla un intento de login
- **THEN** SessionSecurity::recordFailedAttempt() incrementa contador y bloquea si excede máximo

#### Scenario: Reset de rate limiting
- **WHEN** pasan 15 minutos desde el primer intento fallido
- **THEN** el contador se resetea y el usuario puede intentar de nuevo

### Requirement: Sesión de usuario autenticado
El sistema SHALL almacenar en sesión: user_id, username, rol, user_role, empleado_id, permisos (base rol + extra).

#### Scenario: Datos de sesión completos
- **WHEN** usuario inicia sesión exitosamente
- **THEN** $_SESSION contiene user_id, username, rol, user_role, empleado_id, permisos
