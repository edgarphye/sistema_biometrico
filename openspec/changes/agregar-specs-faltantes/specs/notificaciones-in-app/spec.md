# notificaciones-in-app

## Purpose

Gestionar notificaciones visuales dentro de la aplicación mediante toasts Bootstrap, badge de validaciones pendientes con polling, dropdown de notificaciones en la barra superior y notificaciones push vía Firebase Cloud Messaging para eventos críticos.

### Requirement: Sistema de toasts estándar

El sistema SHALL proveer una función JavaScript global `mostrarToast(mensaje, tipo)` que muestre notificaciones tipo toast Bootstrap con tipo success/error/warning/info y desaparezca automáticamente tras 4 segundos.

#### Scenario: Toast de éxito

- **WHEN** se ejecuta `mostrarToast("Empleado guardado", "success")`
- **THEN** el sistema muestra un toast verde con el mensaje que desaparece a los 4 segundos

#### Scenario: Toast de error

- **WHEN** se ejecuta `mostrarToast("Error al guardar", "error")`
- **THEN** el sistema muestra un toast rojo con el mensaje

### Requirement: Dropdown de notificaciones en barra superior

El sistema SHALL mostrar un icono de campana en la barra superior con dropdown de notificaciones recientes y contador de no leídas.

#### Scenario: Dropdown con notificaciones

- **WHEN** se hace clic en el icono de campana
- **THEN** el sistema despliega las últimas 10 notificaciones con icono por tipo, mensaje, tiempo relativo y enlace

#### Scenario: Contador de no leídas

- **WHEN** hay notificaciones sin leer
- **THEN** el badge de la campana muestra el número de notificaciones pendientes

#### Scenario: Marcación como leída

- **WHEN** se abre el dropdown
- **THEN** el sistema marca las notificaciones como leídas vía POST a `/notificaciones/marcar-leidas`

### Requirement: Badge de validaciones pendientes con polling

El sistema SHALL mostrar un badge animado en el menú lateral con el conteo de validaciones pendientes, actualizado vía polling cada 30 segundos.

#### Scenario: Badge animado

- **WHEN** hay validaciones pendientes
- **THEN** el badge en el menú lateral muestra el número con animación pulse

#### Scenario: Polling cada 30s

- **WHEN** el dashboard está abierto
- **THEN** el sistema consulta `/validaciones/obtener-contador-pendientes` cada 30 segundos y actualiza el badge

### Requirement: Notificaciones push (FCM)

El sistema SHALL enviar notificaciones push vía Firebase Cloud Messaging para eventos críticos: sanción registrada, validación requerida, nota mala, alerta crítica.

#### Scenario: Push de sanción

- **WHEN** se crea una sanción
- **THEN** el sistema envía notificación push al empleado vía `PushNotificationService`

#### Scenario: Push de validación

- **WHEN** se requiere validación de jefe
- **THEN** el sistema envía notificación push al jefe correspondiente

### Requirement: Notificaciones de sistema en base de datos

El sistema SHALL almacenar notificaciones en la tabla `notificaciones` con usuario_id, tipo (sancion, validacion, nota_mala, alerta, sistema), titulo, mensaje, enlace, leido, created_at.

#### Scenario: Notificación persistida

- **WHEN** se genera un evento que requiere notificación
- **THEN** el sistema crea registro en `notificaciones` con datos del evento

#### Scenario: Consulta de notificaciones

- **WHEN** se envía GET a `/notificaciones/listar` con header AJAX
- **THEN** el sistema retorna JSON con las notificaciones del usuario ordenadas por fecha descendente

### Requirement: Notificaciones de licencias médicas

El sistema SHALL notificar automáticamente cuando una licencia médica esté próxima a vencer.

#### Scenario: Recordatorio de vencimiento

- **WHEN** una licencia médica está próxima a vencer
- **THEN** el sistema crea una notificación in-app para el empleado y RH

### Requirement: Preferencias de notificaciones por usuario

El sistema SHALL permitir a cada usuario configurar qué tipos de notificaciones desea recibir y por qué canal (in-app, email, push).

#### Scenario: Configuración de preferencias

- **WHEN** el usuario accede a su perfil
- **THEN** puede activar/desactivar tipos de notificación por canal
