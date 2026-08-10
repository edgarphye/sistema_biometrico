# huellas-dactilares

## Purpose
Capturar, almacenar y sincronizar huellas dactilares desde dispositivos ZKTeco con encriptación AES-256-CBC, gestión del proceso de enrolamiento y compatibilidad con almacenamiento legacy.

### Requirement: Captura de huellas
El sistema SHALL capturar huellas dactilares desde dispositivos ZKTeco y almacenar plantillas en huellas_empleados con empleado_id, zk_empleado_id, indice_huella (0-9), huella_template (blob), calidad_huella (0-100), tipo_huella (dedo: índice/pulgar/medio/anular/menique, derecho/izquierdo), dispositivo_id, estado (activo/inactivo/error/sincronizando).

#### Scenario: Captura de huella exitosa
- **WHEN** se enrola huella de un empleado desde el sistema
- **THEN** el dispositivo captura y el sistema guarda la plantilla

#### Scenario: Flujo de captura vía modal
- **WHEN** se hace clic en "Iniciar Registro de Huella" en el form de empleado
- **THEN** el sistema muestra modal con 4 pasos: conexión, captura, procesamiento, guardado

#### Scenario: Conexión con dispositivo
- **WHEN** se selecciona un dispositivo biométrico
- **THEN** el sistema valida conexión vía POST a /biometricos/test-dispositivo/{deviceId}

#### Scenario: Captura vía endpoint
- **WHEN** el dispositivo está conectado y el empleado coloca su dedo
- **THEN** el sistema envía POST a /empleados/capturarHuella con dispositivo_id y retorna fingerprint_data

### Requirement: Encriptación AES-256-CBC
El sistema SHALL encriptar datos biométricos con AES-256-CBC usando Encryption helper con key derivada de ENCRYPTION_KEY via SHA-256.

#### Scenario: Almacenamiento encriptado
- **WHEN** se guarda una huella
- **THEN** el sistema encripta el template antes de almacenar

### Requirement: Almacenamiento legacy en empleados
El sistema SHALL mantener campo huella_dactilar (text) en empleados para compatibilidad, también encriptado.

#### Scenario: Huella legacy guardada
- **WHEN** se registra huella en empleado
- **THEN** Encryption::encrypt() guarda dato encriptado en empleados.huella_dactilar

### Requirement: Gestión de enrolamiento
El sistema SHALL gestionar el proceso de enrolamiento con tabla proceso_enrolamiento: iniciado, capturando, completado, error, cancelado.

#### Scenario: Proceso de enrolamiento
- **WHEN** se inicia enrolamiento
- **THEN** el sistema crea registro en proceso_enrolamiento y guía el proceso paso a paso

### Requirement: Sincronización de huellas
El sistema SHALL sincronizar huellas entre múltiples dispositivos biométricos.

#### Scenario: Sincronización entre dispositivos
- **WHEN** se ejecuta sincronización de huellas
- **THEN** el sistema transfiere plantillas entre dispositivos según configuración

### Requirement: Captura de fotos de empleado
El sistema SHALL permitir subir foto de empleado en formato imagen (JPG, PNG, GIF) con tamaño máximo de 2MB.

#### Scenario: Subida de foto exitosa
- **WHEN** se envía POST a /empleados/crear o /empleados/edit con archivo imagen en campo foto_cara
- **THEN** el sistema guarda la ruta en empleados.foto_cara

#### Scenario: Vista previa de foto
- **WHEN** se selecciona un archivo de imagen
- **THEN** el JavaScript muestra vista previa antes de enviar
