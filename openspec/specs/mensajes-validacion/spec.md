# mensajes-validacion

## Purpose

Gestionar la comunicación bidireccional entre empleados y jefes durante el flujo de validación de incidencias, con mensajes, archivos adjuntos, estados de lectura y contador de mensajes no leídos.

### Requirement: Conversación bidireccional

El sistema SHALL permitir intercambio de mensajes entre empleado y jefe dentro de una validación, con tipo emisor (jefe/empleado) y contenido de texto.

#### Scenario: Jefe envía mensaje

- **WHEN** el jefe envía un mensaje en una validación
- **THEN** el sistema crea registro en `validacion_mensajes` con emisor=jefe y marca esperando_respuesta_de=empleado

#### Scenario: Empleado responde

- **WHEN** el empleado responde al mensaje del jefe
- **THEN** el sistema marca esperando_respuesta_de=jefe y notifica al jefe

### Requirement: Archivos adjuntos en mensajes

El sistema SHALL permitir adjuntar archivos a los mensajes de validación.

#### Scenario: Mensaje con adjunto

- **WHEN** se envía un mensaje con archivo adjunto
- **THEN** el sistema guarda el archivo y asocia al mensaje

### Requirement: Estados de lectura

El sistema SHALL marcar mensajes como leídos/no leídos por destinatario.

#### Scenario: Mensaje marcado como leído

- **WHEN** el destinatario abre la conversación
- **THEN** el sistema marca los mensajes como leídos

### Requirement: Contador de no leídos

El sistema SHALL mostrar contador de mensajes no leídos en el menú y en la lista de validaciones.

#### Scenario: Badge de no leídos

- **WHEN** hay mensajes no leídos para el usuario
- **THEN** el sistema muestra badge con el conteo

### Requirement: Endpoints de mensajes

El sistema SHALL exponer rutas: GET `/validaciones/mensajes/{id}` (listar mensajes), POST `/validaciones/mensajes/agregar` (enviar mensaje), GET `/validaciones/contador-no-leidas` (contador no leídos), POST `/validaciones/marcar-leido` (marcar leídos).

#### Scenario: Listar mensajes

- **WHEN** se consulta GET `/validaciones/mensajes/{validacion_id}`
- **THEN** el sistema retorna los mensajes de la validación ordenados por fecha

#### Scenario: Enviar mensaje

- **WHEN** se envía POST `/validaciones/mensajes/agregar` con contenido y validacion_id
- **THEN** el sistema crea el mensaje y notifica al destinatario
