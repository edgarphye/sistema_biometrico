# subida-archivos

## Purpose

Gestionar la subida de archivos al sistema con validación centralizada de tipo MIME, tamaño máximo, sanitización de nombres, almacenamiento en directorios organizados, control de acceso a descargas y registro de auditoría.

### Requirement: Validador centralizado de archivos

El sistema SHALL proveer un helper centralizado `BaseController::validateMime()` que valide tipo MIME real mediante `finfo`, extensión permitida y tamaño máximo configurable.

#### Scenario: Archivo válido

- **WHEN** se sube un archivo con tipo MIME y extensión permitidos
- **THEN** el sistema acepta el archivo y procede al almacenamiento

#### Scenario: Tipo MIME no permitido

- **WHEN** se sube un archivo con tipo MIME no incluido en la lista blanca
- **THEN** el sistema rechaza el archivo con error de tipo no permitido

#### Scenario: Tamaño excedido

- **WHEN** se sube un archivo que excede el tamaño máximo permitido
- **THEN** el sistema rechaza el archivo con error de tamaño excedido

### Requirement: Subida de fotos de empleados

El sistema SHALL permitir subir fotos de empleados en formato JPEG, PNG o GIF al directorio `uploads/fotos_empleados/`.

#### Scenario: Subida de foto exitosa

- **WHEN** se envía POST a `/empleados/subir-foto` con archivo de imagen válido
- **THEN** el sistema guarda el archivo con nombre `empleado_{id}_{timestamp}.{ext}` y actualiza `empleados.foto_cara`

#### Scenario: Foto sin tamaño explícito

- **WHEN** se sube una foto de empleado
- **THEN** el sistema valida contra el límite configurable por defecto (2 MB)

### Requirement: Subida de soportes de justificación

El sistema SHALL permitir subir documentos de soporte para justificaciones en formato PDF, JPEG o PNG al directorio `uploads/justificaciones/`.

#### Scenario: Subida de soporte exitosa

- **WHEN** se envía justificación con archivo adjunto válido
- **THEN** el sistema guarda en `uploads/justificaciones/` con nombre `soporte_retardo_{id}_{timestamp}.{ext}` o `incidencia_{id}_{timestamp}.{ext}`

#### Scenario: Tamaño máximo de soporte

- **WHEN** se sube soporte de justificación
- **THEN** el sistema valida tamaño máximo de 5 MB

### Requirement: Subida de logs ZKTeco

El sistema SHALL permitir subir archivos de registro de dispositivos biométricos al directorio `uploads/zkt_logs/`.

#### Scenario: Subida de log ZKTeco

- **WHEN** se envía POST a `/zkteco/upload-log` con archivo .dat
- **THEN** el sistema guarda el archivo y procesa los registros de asistencia

### Requirement: Sanitización de nombres de archivo

El sistema SHALL sanitizar nombres de archivo eliminando caracteres especiales, espacios y rutas relativas, usando solo el nombre base.

#### Scenario: Nombre sanitizado

- **WHEN** se sube un archivo con nombre `../../malicioso.exe" or "archivo con espacios.pdf`
- **THEN** el sistema limpia el nombre y previene path traversal

### Requirement: Control de acceso a descargas

El sistema SHALL restringir la descarga de archivos según el rol del usuario y propiedad del recurso.

#### Scenario: Descarga de soporte de justificación

- **WHEN** se solicita descargar un soporte de justificación
- **THEN** el sistema verifica que el usuario sea admin, el empleado propietario o el aprobador antes de servir el archivo

#### Scenario: Path traversal bloqueado

- **WHEN** se solicita un archivo con `../` en la ruta
- **THEN** el sistema rechaza la solicitud con error 400

### Requirement: Registro de acceso a descargas

El sistema SHALL registrar accesos a descargas de archivos en `logs/soporte_access.log` con usuario, archivo, timestamp y resultado.

#### Scenario: Acceso registrado

- **WHEN** se descarga un archivo
- **THEN** el sistema escribe en el log con datos de la solicitud

### Requirement: Directorios de upload organizados

El sistema SHALL mantener los directorios de upload con permisos 0755 y crearlos automáticamente si no existen.

#### Scenario: Directorio creado automáticamente

- **WHEN** se sube un archivo a una ruta cuyo directorio no existe
- **THEN** el sistema crea el directorio con permisos 0755 antes de guardar
