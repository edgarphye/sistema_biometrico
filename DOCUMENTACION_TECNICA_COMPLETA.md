# 📋 DOCUMENTACIÓN TÉCNICA PROFESIONAL

## Sistema de Control de Asistencia Biométrico

**Versión:** 2.0  
**Fecha:** Marzo 2026  
**Clasificación:** Documento Técnico  

---

# RESUMEN EJECUTIVO

El presente documento establece la documentación técnica integral del Sistema de Control de Asistencia Biométrico, desarrollado para la automatización y gestión eficiente del registro de asistencia del personal operativo. Este sistema implements tecnologías de identificación biométrica mediante dispositivos ZKTeco, permitiendo un control preciso, confiable y automatizado de las llegadas y salidas de los empleados.

El sistema está diseñado bajo principios de arquitectura limpia (Clean Architecture), aplicando metodologías de desarrollo ágil y cumpliendo con estándares internacionales de calidad de software ISO/IEC 25010:2023, así como con buenas prácticas de seguridad de la información conforme a ISO/IEC 27001:2022.

---

# PARTE I: MARCO NORMATIVO Y METODOLÓGICO

## 1.1 Estándares ISO Aplicados

### 1.1.1 ISO/IEC 25010:2023 - Calidad de Producto Software

El sistema cumple con los siguientes características de calidad del modelo SQuaRE:

| Característica | Descripción | Implementación en el Sistema |
|----------------|-------------|------------------------------|
| **Adecuación Funcional** | Grado en que el producto proporciona funciones que satisfacen necesidades declaradas e implícitas | Módulos completos de asistencia, reportes, usuarios y configuraciones |
| **Eficiencia de Desempeño** | Relación entre el nivel de desempeño del producto y la cantidad de recursos utilizados | Optimización de consultas SQL, caché de datos biométricos |
| **Compatibilidad** | Capacidad del producto para compartir entorno y recursos con otros productos | Integración con dispositivos ZKTeco mediante API REST |
| **Usabilidad** | Capacidad del producto para ser comprendido, aprendido, operado y atractivo | Interfaz responsive con Bootstrap 5, flujos intuitivos |
| **Fiabilidad** | Capacidad del producto para mantener un nivel específico de rendimiento | Manejo de errores, transacciones, recovery automático |
| **Seguridad** | Capacidad del producto para proteger información y datos | Cifrado AES-256, CSRF tokens, sesiones BD |
| **Mantenibilidad** | Capacidad del producto para ser modificado eficientemente | Arquitectura modular, código documentado |
| **Portabilidad** | Capacidad del producto para ser transferido de un entorno a otro | PHP 8.x, MySQL 8.0, arquitectura cliente-servidor |

### 1.1.2 ISO/IEC 27001:2022 - Sistemas de Gestión de Seguridad de la Información

El sistema implementa controles de seguridad alineados con el Anexo A de la norma:

- **A.5.1** - Políticas de seguridad de la información
- **A.6.1.2** - Segregación de duties (roles: admin, supervisor, rh, user, viewer)
- **A.8.2** - Privilegios de acceso (control de acceso basado en roles - RBAC)
- **A.8.12** - Protección de datos en tránsito (HTTPS/TLS)
- **A.8.24** - Uso de cryptography (cifrado de datos sensibles con AES-256)
- **A.8.32** - Ingeniería inversa (protección de código)

### 1.1.3 ISO 9001:2015 - Sistemas de Gestión de Calidad

Aunque el sistema no está certificado, aplica principios de gestión de calidad:

- **Enfoque en procesos**: Metodología de desarrollo en fases (análisis, diseño, desarrollo, pruebas, despliegue)
- **Mejora continua**: Sistema de logs, monitoreo y métricas de rendimiento
- **Toma de decisiones basada en evidencia**: Dashboard con métricas e indicadores KPI

### 1.1.4 ISO/IEC 12207:2017 - Procesos del Ciclo de Vida del Software

El desarrollo siguió los procesos definidos en esta norma:

| Proceso | Aplicación en el Proyecto |
|---------|---------------------------|
| **Adquisición** | Definición de requisitos mediante análisis de necesidades del área de RH |
| **Suministro** | Entrega de artefactos: código fuente, documentación, manuales |
| **Desarrollo** | Implementación siguiendo patrón MVC y arquitectura modular |
| **Operación** | Despliegue en servidor Apache/PHP con configuración de producción |
| **Mantenimiento** | Sistema de actualizaciones, backups y recuperación |

---

## 1.2 Metodología de Desarrollo

### 1.2.1 Modelo de Desarrollo

El proyecto utiliza un **modelo híbrido iterativo-incremental** que combina:

1. **Fase Inicial**: Análisis de requisitos y diseño de arquitectura
2. **Iteraciones**: Ciclos de desarrollo de 2 semanas con entregables funcionales
3. **Integración Continua**: Pruebas automatizadas con PHPUnit y Cypress

### 1.2.2 Patrones de Diseño Aplicados

| Patrón | Descripción | Ubicación en el Código |
|--------|-------------|----------------------|
| **MVC** (Model-View-Controller) | Separación de responsabilidades | `controllers/`, `models/`, `views/` |
| **Repository** | Abstracción de acceso a datos | Clases `Model` en `models/` |
| **Factory** | Creación de objetos | Métodos estáticos en controladores |
| **Singleton** | Instancia única de conexión BD | `Database::getInstance()` |
| **Strategy** | Algoritmos intercambiables | Procesadores de asistencia ZKTeco |
| **Observer** | Eventos del sistema | Sistema de notificaciones |

---

# PARTE II: ARQUITECTURA DEL SISTEMA

## 2.1 Visión de Arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CAPA DE PRESENTACIÓN                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────────┐ │
│  │   Views     │  │  Dashboard  │  │  Reportes   │  │   API     │ │
│  │  (PHP/HTML) │  │   (Charts)  │  │   (Excel)   │  │  (REST)   │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └───────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        CAPA DE CONTROLADORES                        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────────┐ │
│  │   Auth      │  │  Empleado   │  │  Asistencia  │  │  Reporte  │ │
│  │ Controller  │  │  Controller │  │  Controller  │  │ Controller│ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └───────────┘ │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────────┐ │
│  │    ZKTeco   │  │  Justific.  │  │   Usuario   │  │    IA     │ │
│  │ Controller  │  │  Controller │  │  Controller │  │ Controller│ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └───────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         CAPA DE MODELOS                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────────┐ │
│  │  Empleado   │  │  Asistencia  │  │   Retardo   │  │ Sancion   │ │
│  │    Model    │  │    Model    │  │    Model    │  │   Model   │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └───────────┘ │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────────┐ │
│  │   Usuario   │  │   Horario    │  │  ZKTeco     │  │   Cache   │ │
│  │    Model    │  │    Model    │  │   Process   │  │  Manager  │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └───────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      CAPA DE INFRAESTRUCTURA                       │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │  MySQL 8.0       │  │  Dispositivos    │  │  Servicios      │  │
│  │  (Persistencia)  │  │  ZKTeco (Biomet) │  │  Externos        │  │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

## 2.2 Arquitectura de Capas (Layered Architecture)

### Capa de Presentación (Presentation Layer)
- **Responsabilidad**: Renderizado de vistas y respuestas HTTP
- **Componentes**: 
  - Vistas PHP en `views/`
  - Dashboard con Chart.js
  - Plantillas Blade-style (implementación personalizada)
  - API RESTful en `api.php`

### Capa de Lógica de Negocio (Business Logic Layer)
- **Responsabilidad**: Reglas de negocio, validaciones, cálculos
- **Componentes**:
  - Controladores en `controllers/`
  - Servicios en `services/`
  - Helpers en `helpers/`

### Capa de Acceso a Datos (Data Access Layer)
- **Responsabilidad**: Abstracción de base de datos, queries
- **Componentes**:
  - Modelos en `models/`
  - Clase `Database` (Singleton)
  - Repositories

### Capa de Infraestructura (Infrastructure Layer)
- **Responsabilidad**: Conexiones externas, dispositivos, servicios
- **Componentes**:
  - Integración ZKTeco (`models/biometric/`)
  - Sistema de archivos
  - Configuración de servidor

## 2.3 Tecnologías Utilizadas

| Capa | Tecnología | Versión |
|------|------------|---------|
| **Backend** | PHP | 8.x |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) | - |
| **Framework CSS** | Bootstrap | 5.x |
| **Base de Datos** | MySQL | 8.0+ |
| **Router** | FastRoute | 2.x |
| **Testing** | PHPUnit, Cypress | 10.x |
| **Biometría** | ZKTeco SDK | - |
| **Servidor Web** | Apache/Nginx | 2.x |

---

# PARTE III: ANÁLISIS DE REQUISITOS

## 3.1 Requisitos Funcionales

### RF-001: Autenticación y Autorización
El sistema debe permitir a los usuarios iniciar sesión con credenciales seguras y gestionar diferentes roles de acceso.

**Sub-requisitos:**
- RF-001.1: Inicio de sesión con usuario y contraseña
- RF-001.2: Hash de contraseñas con bcrypt (cost factor 10)
- RF-001.3: Control de acceso basado en roles (RBAC)
- RF-001.4: Sesiones almacenadas en base de datos
- RF-001.5: Protección CSRF en formularios

**Roles definidos:**
| Rol | Descripción | Permisos |
|-----|-------------|----------|
| `admin` | Administrador | Acceso total al sistema |
| `supervisor` | Supervisor de área | Visualización y aprobación |
| `rh` | Recursos Humanos | Gestión de empleados y reportes |
| `user` | Usuario estándar | Registro de asistencia |
| `viewer` | Consultor | Solo lectura |

### RF-002: Gestión de Empleados
El sistema debe permitir el registro, modificación y consulta de información de empleados.

**Sub-requisitos:**
- RF-002.1: Alta de empleados con datos RFC, CURP, fotografía
- RF-002.2: Asignación de área, puesto y jerarquía
- RF-002.3: Asociación de credencial biométrica (huella/facial)
- RF-002.4: Importación masiva desde Excel
- RF-002.5: Consultas con filtros múltiples

### RF-003: Control de Asistencia
El sistema debe registrar y procesar las marcas de asistencia provenientes de dispositivos biométricos.

**Sub-requisitos:**
- RF-003.1: Recepción de eventos de asistencia vía API
- RF-003.2: Procesamiento de entradas y salidas
- RF-003.3: Cálculo automático de retardos según horario
- RF-003.4: Detección de anomalías (múltiples marcas, horarios irregulares)
- RF-003.5: Sincronización con dispositivos ZKTeco

### RF-004: Gestión de Horarios
El sistema debe definir y administrar diferentes esquemas de horario laboral.

**Sub-requisitos:**
- RF-004.1: Definición de horarios con hora de entrada, salida y tolerancia
- RF-004.2: Asignación de horarios por empleado
- RF-004.3: Soporte para múltiples sedes
- RF-004.4: Horarios flexibles y turno matutino/vespertino

### RF-005: Justificaciones y Permisos
El sistema debe gestionar las solicitudes de justificación de ausencias y retardos.

**Sub-requisitos:**
- RF-005.1: Registro de solicitudes de justificación
- RF-005.2: Flujo de aprobación por supervisor/jefe inmediato
- RF-005.3: Tipos de justificación configurables
- RF-005.4: Cálculo automático de días empleados

### RF-006: Generación de Reportes
El sistema debe generar reportes de asistencia en múltiples formatos.

**Sub-requisitos:**
- RF-006.1: Reportes por empleado, área y período
- RF-006.2: Exportación a Excel (.xlsx)
- RF-006.3: Reportes de retardos, faltas y sanciones
- RF-006.4: Dashboard con métricas en tiempo real

### RF-007: Sistema de Sanciones
El sistema debe evaluar y registrar las sanciones derivadas de faltas de asistencia.

**Sub-requisitos:**
- RF-007.1: Evaluación automática de retardos acumulados
- RF-007.2: Generación de amonestaciones escritas
- RF-007.3: Registro de suspensiones y actas administrativas
- RF-007.4: Historial de sanciones por empleado

---

## 3.2 Requisitos No Funcionales

### RNF-001: Rendimiento
- El sistema debe procesar solicitudes en menos de 2 segundos (p95)
- La generación de reportes no debe exceder 30 segundos
- Sincronización de dispositivos: procesamiento en batch de 1000 registros/minuto

### RNF-002: Disponibilidad
- Disponibilidad objetivo: 99.5% (excluyendo mantenimiento programado)
- Tiempo máximo de recuperación (RTO): 4 horas
- Punto de recuperación (RPO): 1 hora

### RNF-003: Escalabilidad
- Capacidad para gestionar 5,000+ empleados activos
- Soporte para 10+ dispositivos biométricos simultáneos
- Diseño preparado para clustering horizontal

### RNF-004: Seguridad
- Todas las comunicaciones mediante HTTPS/TLS 1.3
- Almacenamiento de contraseñas con bcrypt (cost: 10)
- Cifrado de datos sensibles con AES-256
- Auditoría completa de accesos y modificaciones

### RNF-005: Mantenibilidad
- Código fuente documentado con comentarios PHP Doc
- Naming conventions consistentes (camelCase para variables, PascalCase para clases)
- Acoplamiento débil entre módulos (inyección de dependencias)
- Pruebas unitarias para lógica crítica

---

# PARTE IV: DISEÑO TÉCNICO

## 4.1 Modelo de Datos

### 4.1.1 Diagrama Entidad-Relación (ERD)

```
┌──────────────────┐       ┌──────────────────┐
│    empleados     │       │    usuarios      │
├──────────────────┤       ├──────────────────┤
│ id (PK)          │◄──────│ empleado_id (FK) │
│ nombre           │       │ id (PK)          │
│ apellido         │       │ username         │
│ rfc (UQ)         │       │ password         │
│ curp (UQ)        │       │ email            │
│ email            │       │ rol              │
│ telefono         │       │ activo           │
│ area             │       └──────────────────┘
│ puesto           │               │
│ huella_dactilar  │               │
│ foto_cara        │       ┌──────▼──────────┐
│ activo           │       │     asistencia   │
└──────────────────┘       ├──────────────────┤
        │                 │ id (PK)          │
        │                 │ empleado_id (FK) │
┌──────▼──────────┐       │ fecha            │
│horarios_empleado │       │ hora_entrada     │
├──────────────────┤       │ hora_salida      │
│ id (PK)         │       │ dispositivo_id   │
│ empleado_id (FK)│       │ tipo_biometria   │
│ horario_id (FK) │       └──────────────────┘
│ dia_semana      │               │
│ sede            │       ┌──────▼──────────┐
└──────────────────┘       │    retardos     │
        │                 ├──────────────────┤
┌──────▼──────────┐       │ id (PK)         │
│horarios_laborales│       │ empleado_id (FK)│
├──────────────────┤       │ fecha           │
│ id (PK)         │       │ minutos_retraso │
│ nombre          │       │ tipo_retraso    │
│ hora_entrada    │       │ justificado     │
│ hora_salida     │       │ horario_id (FK) │
│ tolerancia_min  │       │ sancion_id (FK) │
│ sede            │       └──────────────────┘
└──────────────────┘               │
        │                 ┌──────▼──────────┐
        │                 │   sanciones     │
┌──────▼──────────┐       ├──────────────────┤
│ dispositivos_bi │       │ id (PK)         │
├──────────────────┤       │ empleado_id (FK)│
│ id (PK)         │       │ tipo_sancion    │
│ dispositivo_id  │       │ motivo          │
│ nombre          │       │ fecha_sancion   │
│ ip_address      │       │ estatus         │
│ tipo            │       └──────────────────┘
│ activo          │
└──────────────────┘
```

### 4.1.2 Descripción de Tablas Principales

#### Tabla: `empleados`
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Identificador único |
| nombre | VARCHAR(100) | NOT NULL | Nombre(s) del empleado |
| apellido | VARCHAR(100) | NOT NULL | Apellidos |
| rfc | VARCHAR(13) | UNIQUE | RFC (13 caracteres) |
| curp | VARCHAR(18) | UNIQUE | CURP (18 caracteres) |
| email | VARCHAR(100) | - | Correo electrónico |
| area | VARCHAR(50) | - | Área de trabajo |
| puesto | VARCHAR(50) | - | Puesto |
| huella_dactilar | TEXT | - | Template biométrico (cifrado) |
| foto_cara | VARCHAR(255) | - | Ruta a fotografía |
| activo | TINYINT(1) | DEFAULT 1 | Estado del empleado |

#### Tabla: `usuarios`
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Identificador único |
| username | VARCHAR(50) | UNIQUE, NOT NULL | Nombre de usuario |
| password | VARCHAR(255) | NOT NULL | Hash bcrypt |
| email | VARCHAR(100) | UNIQUE | Correo electrónico |
| rol | ENUM | 'admin','user','viewer','rh','supervisor' | Rol de acceso |
| empleado_id | INT | FK -> empleados(id) | Empleado asociado |
| activo | TINYINT(1) | DEFAULT 1 | Estado del usuario |

#### Tabla: `asistencia`
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Identificador único |
| empleado_id | INT | FK -> empleados(id) | Empleado |
| fecha | DATE | NOT NULL | Fecha del registro |
| hora_entrada | TIME | - | Hora de ingreso |
| hora_salida | TIME | - | Hora de salida |
| dispositivo_id | TINYINT | - | Dispositivo de origen |
| tipo_biometria | ENUM | 'huella','cara' | Tipo de registro |

#### Tabla: `retardos`
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Identificador único |
| empleado_id | INT | FK -> empleados(id) | Empleado |
| fecha | DATE | NOT NULL | Fecha del retardo |
| minutos_retraso | INT | NOT NULL | Minutos de retraso |
| tipo_retraso | ENUM | 'menor','mayor' | Clasificación |
| justificado | TINYINT(1) | DEFAULT 0 | Estado de justificación |
| horario_id | INT | FK -> horarios_laborales | Horario aplicable |

#### Tabla: `horarios_laborales`
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Identificador único |
| nombre | VARCHAR(100) | NOT NULL | Nombre del horario |
| hora_entrada | TIME | NOT NULL | Hora de ingreso |
| hora_salida | TIME | NOT NULL | Hora de salida |
| tolerancia_minutos | INT | DEFAULT 10 | Minutos de tolerancia |
| sede | VARCHAR(100) | NULL | Sede (opcional) |

---

## 4.2 Diseño de la API

### 4.2.1 Endpoints Principales

#### Autenticación
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET/POST | `/login` | Iniciar sesión |
| GET | `/logout` | Cerrar sesión |
| GET/POST | `/register` | Registrar usuario |
| GET/POST | `/verify-2fa` | Verificación de 2FA |

#### Empleados
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/empleados` | Listar empleados |
| POST | `/empleados` | Crear empleado |
| GET | `/empleados/{id}` | Ver empleado |
| POST | `/empleados/{id}/edit` | Editar empleado |
| POST | `/empleados/{id}/delete` | Eliminar empleado |
| POST | `/empleados/importar` | Importar desde Excel |

#### Asistencia
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/asistencia` | Ver registros de asistencia |
| POST | `/asistencia/sincronizar` | Sincronizar con dispositivos |
| GET | `/asistencia/reporte` | Generar reporte |

#### Reportes
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/reportes` | Listar reportes disponibles |
| GET | `/reportes/asistencia` | Reporte de asistencia |
| GET | `/reportes/retardos` | Reporte de retardos |
| GET | `/reportes/sanciones` | Reporte de sanciones |

#### Configuración
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/configuracion` | Ver configuración |
| POST | `/configuracion/actualizar` | Actualizar configuración |
| GET | `/configuracion/dispositivos` | Gestionar dispositivos |

### 4.2.2 Formato de Respuesta JSON

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa"
}
```

**Respuesta de error:**
```json
{
  "success": false,
  "error": "Descripción del error",
  "code": "ERROR_CODE"
}
```

---

## 4.3 Seguridad

### 4.3.1 Autenticación y Autorización

```
┌────────────────────────────────────────────────────────────┐
│                    FLUJO DE AUTENTICACIÓN                  │
├────────────────────────────────────────────────────────────┤
│                                                            │
│   ┌──────────┐    ┌──────────┐    ┌──────────────┐       │
│   │ Usuario  │───►│ Validar  │───►│ Buscar en    │       │
│   │ Ingresa  │    │ Credenc. │    │   BD         │       │
│   └──────────┘    └──────────┘    └──────┬───────┘       │
│                                           │               │
│          ┌────────────────┬──────────────┴───────┐       │
│          │                                    │           │
│          ▼                                    ▼           │
│   ┌──────────────┐                   ┌──────────────┐   │
│   │ Credenciales │                   │ Credenciales │   │
│   │ Inválidas    │                   │ Válidas      │   │
│   │ ────────────  │                   │ ────────────  │   │
│   │ 1. Registrar │                   │ 1. Crear     │   │
│   │    intento   │                   │    sesión    │   │
│   │ 2. Mostrar   │                   │ 2. Generar  │   │
│   │    error     │                   │    CSRF      │   │
│   └──────────────┘                   │ 3. Redirigir │   │
│                                      └──────────────┘   │
└────────────────────────────────────────────────────────────┘
```

### 4.3.2 Control de Acceso Basado en Roles (RBAC)

```php
// Ejemplo de verificación de rol
function requireRole($requiredRole) {
    $roles = [
        'viewer' => 1,
        'user' => 2,
        'rh' => 3,
        'supervisor' => 4,
        'admin' => 5
    ];
    
    $userRole = $_SESSION['user_role'] ?? 'viewer';
    
    if ($roles[$userRole] < $roles[$requiredRole]) {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
}
```

### 4.3.3 Medidas de Seguridad Implementadas

| Medida | Descripción | Implementación |
|--------|-------------|----------------|
| **Hash de contraseñas** | Algoritmo bcrypt | `password_hash()` con cost 10 |
| **Protección CSRF** | Token por sesión | Helper `Csrf.php` |
| **Sesiones seguras** | Almacenamiento en BD | Custom session handler |
| **Cifrado de datos** | AES-256 para datos sensibles | Biblioteca custom |
| **SQL Injection** | Prepared statements | PDO con parámetros |
| **XSS Prevention** | Escape de salida | `htmlspecialchars()` |
| **HTTPS** | Comunicación segura | Configuración servidor |

---

# PARTE V: MÓDULOS DEL SISTEMA

## 5.1 Módulo de Autenticación

### Descripción
Gestiona el acceso seguro al sistema mediante credenciales y roles.

### Funcionalidades
- Inicio/cierre de sesión
- Registro de usuarios
- Verificación en dos pasos (2FA)
- Recuperación de contraseña
- Bloqueo de cuenta tras intentos fallidos

### Archivos Principales
- `controllers/AuthController.php`
- `helpers/Csrf.php`
- `models/Usuario.php`

## 5.2 Módulo de Gestión de Empleados

### Descripción
Administra el catálogo de empleados con toda su información personal y laboral.

### Funcionalidades
- Alta, modificación y baja de empleados
- Importación masiva desde archivos Excel
- Asignación de área, puesto y jefe inmediato
- Gestión de datos RFC y CURP
- Associação de biometría (huella/facial)

### Archivos Principales
- `controllers/EmpleadoController.php`
- `models/Empleado.php`
- `importar_empleados.php`

## 5.3 Módulo de Control de Asistencia

### Descripción
Procesa y gestiona los registros de asistencia provenientes de dispositivos biométricos.

### Funcionalidades
- Recepción de eventos de asistencia
- Procesamiento de entradas y salidas
- Cálculo de tiempo efectivo de trabajo
- Detección de anomalías
- Sincronización con dispositivos ZKTeco

### Archivos Principales
- `controllers/AsistenciaController.php`
- `controllers/ZKTecoController.php`
- `models/Asistencia.php`
- `models/ZKTecoUniversalParser.php`
- `models/ZKTecoAsistenciaInserter.php`

## 5.4 Módulo de Horarios

### Descripción
Define y administra los esquemas de horario laboral.

### Funcionalidades
- Creación de horarios con tolerancia configurable
- Asignación de horarios por empleado
- Soporte para múltiples sedes
- Horarios especiales y días festivos

### Archivos Principales
- `controllers/HorarioController.php`
- `models/HorarioLaboral.php`
- `models/HorarioClassifier.php`

## 5.5 Módulo de Justificaciones

### Descripción
Gestiona las solicitudes de justificación de ausencias y retardos.

### Funcionalidades
- Registro de solicitudes
- Flujo de aprobación jerárquico
- Tipos de justificación configurables
- Adjunto de documentos probatorios
- Notificaciones por email

### Archivos Principales
- `controllers/JustificacionController.php`
- `models/TipoJustificacion.php`
- `controllers/RegistroJustificacionController.php`

## 5.6 Módulo de Retardos y Sanciones

### Descripción
Calcula retardos y aplica sanciones conforme a las políticas de la organización.

### Funcionalidades
- Cálculo automático de minutos de retraso
- Clasificación de retardos (menor/mayor)
- Evaluación de sanciones automáticas
- Registro de amonestaciones y suspensiones
- Historial de incidencias

### Archivos Principales
- `controllers/NotasMalasController.php`
- `controllers/SancionController.php`
- `models/Retardo.php`
- `models/Sancion.php`

## 5.7 Módulo de Reportes

### Descripción
Genera reportes y dashboards para análisis de información de asistencia.

### Funcionalidades
- Reportes por empleado, área, período
- Exportación a Excel
- Gráficos interactivos
- Indicadores de productividad
- Reportes de retardos y faltas

### Archivos Principales
- `controllers/ReportesController.php`
- `SistemaReportesAvanzados.php`

## 5.8 Módulo de Inteligencia Artificial

### Descripción
Implementa análisis predictivo y detección de anomalías mediante técnicas de IA.

### Funcionalidades
- Predicción de patrones de asistencia
- Detección de anomalías en registros
- Recomendaciones de acciones preventivas
- Análisis de tendencias

### Archivos Principales
- `controllers/AIController.php`
- `controllers/AnalisisPredictivoController.php`
- `models/AnomaliaDetectada.php`

---

# PARTE VI: INSTALACIÓN Y CONFIGURACIÓN

## 6.1 Requisitos del Sistema

### Requisitos de Software
| Componente | Requisito Mínimo | Recomendado |
|------------|------------------|-------------|
| PHP | 8.0+ | 8.2+ |
| MySQL | 8.0+ | 8.0+ |
| Apache | 2.4+ | 2.4+ |
| Memoria RAM | 2 GB | 4 GB |
| Espacio disco | 10 GB | 50 GB |

### Extensiones PHP Requeridas
- `pdo_mysql`
- `json`
- `mbstring`
- `openssl`
- `curl`
- `gd`

## 6.2 Pasos de Instalación

### 6.2.1 Configuración de Base de Datos

```sql
-- Crear base de datos
CREATE DATABASE sistema_biometrico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Ejecutar script de esquema
SOURCE database.sql;

-- Verificar tablas
SHOW TABLES;
```

### 6.2.2 Configuración de Variables de Entorno

```bash
# Archivo .env
DB_HOST=localhost
DB_USER=root
DB_PASS=tu_password
DB_NAME=sistema_biometrico

APP_NAME="Sistema Biométrico"
APP_VERSION=2.0

BIOMETRIC_API_URL=http://localhost:8080/api
BIOMETRIC_API_KEY=tu_api_key
BIOMETRIC_SIMULATION=false

ENCRYPTION_KEY=tu_clave_cifrado_32_caracteres

TOLERANCE_MINUTES=10
LOG_LEVEL=INFO
```

### 6.2.3 Instalación de Dependencias

```bash
# Instalar dependencias Composer
composer install

# Instalar dependencias npm (si aplica)
npm install
```

### 6.2.4 Permisos de Archivos

```bash
# Directorios escribibles
chmod 755 logs/
chmod 755 cache/
chmod 755 uploads/
chmod 755 backups/
```

## 6.3 Configuración del Servidor

### Configuración Apache (.htaccess)

```apache
# Habilitar mod_rewrite
RewriteEngine On

# Forzar HTTPS en producción
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Configuración de PHP
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value memory_limit 256M
```

---

# PARTE VII: OPERACIÓN Y MANTENIMIENTO

## 7.1 Monitoreo

### Logs del Sistema
El sistema genera los siguientes archivos de log:

| Archivo | Descripción |
|---------|-------------|
| `logs/biometric_system.log` | Log general del sistema |
| `logs/php_errors_YYYY-MM-DD.log` | Errores PHP |
| `logs/js_errors_YYYY-MM-DD.log` | Errores JavaScript |
| `logs/zkteco_process.log` | Proceso de dispositivos ZKTeco |

### Métricas de Rendimiento

| Métrica | Objetivo | Umbral de Alerta |
|---------|----------|------------------|
| Tiempo de respuesta | < 2s | > 5s |
| Uso de CPU | < 70% | > 85% |
| Uso de memoria | < 80% | > 90% |
| Conexiones BD | < 50 | > 80 |

## 7.2 Respaldo y Recuperación

### Política de Backups

| Tipo | Frecuencia | Retención |
|------|------------|-----------|
| Base de datos completa | Diaria | 30 días |
| Archivos de configuración | Semanal | 12 semanas |
| Código fuente | Por cada release | Indefinido |

### Scripts de Backup
- `backup.sh` (Linux)
- `backup.bat` (Windows)

### Procedimiento de Restauración
1. Detener servicios
2. Restaurar base de datos: `mysql -u root -p sistema_biometrico < backup.sql`
3. Verificar integridad
4. Reanudar servicios

## 7.3 Mantenimiento Preventivo

### Tareas Programadas

| Tarea | Frecuencia | Responsable |
|-------|------------|-------------|
| Optimización de tablas BD | Semanal | DBA/SysAdmin |
| Limpieza de logs antiguos | Mensual | SysAdmin |
| Verificación de backups | Semanal | SysAdmin |
| Actualización de parches de seguridad | Según liberación | DevOps |

---

# PARTE VIII: ANEXOS

## Anexo A: Glosario de Términos

| Término | Definición |
|---------|------------|
| **Biometría** | Tecnología de identificación basada en características fisiológicas o comportamentales |
| **ZKTeco** | Fabricante de dispositivos de control de asistencia biométrico |
| **Retardo** | Llegada posterior a la hora de entrada toleranceada |
| **Horario laboral** | Franja horaria establecida para la jornada de trabajo |
| **Sede** | Ubicación física donde opera un grupo de empleados |
| **CSRF** | Cross-Site Request Forgery - Tipo de ataque web |
| **RBAC** | Role-Based Access Control - Control de acceso basado en roles |

## Anexo B: Referencias Normativas

1. **ISO/IEC 25010:2023** - Systems and software Quality Requirements and Evaluation (SQuaRE)
2. **ISO/IEC 27001:2022** - Information security management systems
3. **ISO 9001:2015** - Quality management systems
4. **ISO/IEC 12207:2017** - Software life cycle processes
5. **NIST SP 800-53** - Security and Privacy Controls for Information Systems

## Anexo C: Estructura de Directorios

```
sistema_biometrico/
├── api/                    # Endpoints de API
├── assets/                 # Recursos estáticos
│   ├── css/               # Hojas de estilo
│   ├── js/                # Scripts JavaScript
│   └── images/            # Imágenes
├── backups/               # Respaldos automáticos
├── cache/                 # Caché del sistema
├── classes/               # Clases utilitarias
├── controllers/          # Controladores MVC
├── data/                  # Archivos de datos
├── database_sql/          # Scripts SQL
├── docs/                  # Documentación
├── helpers/               # Funciones helper
├── lib/                   # Bibliotecas externas
├── logs/                  # Archivos de log
├── migrations/           # Migraciones de BD
├── models/               # Modelos MVC
├── public/               # Archivos públicos
├── scripts/              # Scripts de mantenimiento
├── services/             # Servicios del sistema
├── sql/                  # Consultas SQL
├── src/                  # Código fuente
├── supports/             # Archivos de soporte
├── tests/                # Pruebas unitarias
├── uploads/             # Archivos subidos
├── views/                # Vistas MVC
├── .env                  # Variables de entorno
├── composer.json         # Dependencias PHP
├── config.php            # Configuración principal
├── database.sql          # Esquema de base de datos
├── index.php             # Front controller
├── routes.php            # Definición de rutas
└── README.md            # Documentación general
```

---

# CONTROL DE VERSIONES

| Versión | Fecha | Descripción | Autor |
|---------|-------|-------------|-------|
| 1.0 | Enero 2025 | Versión inicial | Desarrollo |
| 2.0 | Marzo 2026 | Integración ZKTeco, módulos AI, mejoras seguridad | Desarrollo |

---

**Documento elaborado conforme a ISO/IEC 27001:2022 e ISO/IEC 25010:2023**

*Este documento es propiedad del área de Tecnologías de la Información y debe ser tratado como información confidencial.*
