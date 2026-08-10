## ADDED Requirements

### Requirement: Captura de huellas
El sistema SHALL capturar huellas dactilares desde dispositivos ZKTeco y almacenar plantillas en huellas_empleados con empleado_id, zk_empleado_id, indice_huella (0-9), huella_template (blob), calidad_huella (0-100), tipo_huella (dedo: índice/pulgar/medio/anular/menique, derecho/izquierdo), dispositivo_id, estado (activo/inactivo/error/sincronizando).

#### Scenario: Captura de huella exitosa
- **WHEN** se enrola huella de un empleado desde el sistema
- **THEN** el dispositivo captura y el sistema guarda la plantilla

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
