## ADDED Requirements

### Requirement: Autenticación de dos factores
El sistema SHALL soportar 2FA mediante TOTP (código temporal de 6 dígitos con ventana de 5 minutos), email y SMS, configurable por usuario.

#### Scenario: Verificación 2FA exitosa
- **WHEN** usuario ingresa credenciales correctas y código 2FA válido
- **THEN** el sistema completa el inicio de sesión

#### Scenario: Código 2FA expirado
- **WHEN** usuario ingresa código 2FA vencido
- **THEN** el sistema rechaza y solicita nuevo código

### Requirement: Configuración de 2FA por usuario
El sistema SHALL permitir a cada usuario habilitar/deshabilitar 2FA desde su perfil.

#### Scenario: Habilitación de 2FA
- **WHEN** usuario configura 2FA desde su perfil
- **THEN** el sistema genera secreto TOTP y muestra QR para escanear

### Requirement: Almacenamiento seguro
El sistema SHALL almacenar secretos TOTP en usuarios.totp_secret en la BD.

#### Scenario: Secreto almacenado
- **WHEN** usuario configura 2FA exitosamente
- **THEN** el sistema guarda totp_secret, totp_enabled=1, totp_setup_date
