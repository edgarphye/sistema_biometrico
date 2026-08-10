# usuarios-roles

## Purpose
Gestionar usuarios del sistema con autenticación segura (ARGON2ID), control de acceso basado en roles, permisos adicionales por usuario, cambio de contraseña y activación/desactivación de cuentas.


### Requirement: CRUD de usuarios
El sistema SHALL permitir crear, leer, actualizar y desactivar usuarios con username UNIQUE, password hasheado (ARGON2ID), email UNIQUE, nombre_completo, rol (admin, rh, supervisor, jefe, empleado, viewer) y empleado_id FK opcional.

#### Scenario: Creación de usuario exitosa
- **WHEN** se envía POST a /usuarios/crear con datos válidos
- **THEN** el sistema crea el usuario con password hasheado y retorna success=true

#### Scenario: Username duplicado
- **WHEN** se crea usuario con username existente
- **THEN** el sistema retorna error de duplicado

### Requirement: Autenticación
El sistema SHALL autenticar usuarios mediante username/password con password_verify() contra hash ARGON2ID.

#### Scenario: Login exitoso
- **WHEN** se envía POST a /login con credenciales correctas
- **THEN** el sistema inicia sesión, registra ultimo_acceso y redirige al dashboard

#### Scenario: Login fallido
- **WHEN** se envía POST a /login con credenciales incorrectas
- **THEN** el sistema retorna error y registra el intento para rate limiting

### Requirement: Control de acceso basado en roles
El sistema SHALL verificar roles en cada ruta protegida mediante checkAdmin() y checkRole().

#### Scenario: Acceso denegado por rol
- **WHEN** un usuario sin permisos accede a ruta admin
- **THEN** el sistema redirige o retorna 403

### Requirement: Permisos adicionales por usuario
El sistema SHALL permitir asignar permisos extra vía tabla usuario_permisos, que se fusionan con los permisos base del rol.

#### Scenario: Permiso adicional asignado
- **WHEN** un usuario tiene permiso extra en usuario_permisos
- **THEN** el sistema lo incluye en $_SESSION['permisos']

### Requirement: Cambio de contraseña
El sistema SHALL permitir cambio de contraseña con validación de contraseña anterior.

#### Scenario: Cambio de contraseña exitoso
- **WHEN** se envía POST a /usuarios/cambiar-password con contraseña anterior correcta y nueva contraseña válida
- **THEN** el sistema actualiza el hash en la BD

### Requirement: Activación/desactivación
El sistema SHALL permitir activar/desactivar usuarios mediante toggleStatus().

#### Scenario: Desactivación de usuario
- **WHEN** se desactiva un usuario
- **THEN** el sistema marca activo=0 y el usuario no puede iniciar sesión
