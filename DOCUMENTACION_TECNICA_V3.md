# 📋 DOCUMENTACIÓN TÉCNICA COMPLETA

## Sistema de Control de Asistencia Biométrico

### Centro de Estudios Superiores de la Ferrocarril Federal - SEP

**Versión:** 3.0  
**Fecha:** Abril 2026  
**Clasificación:** Documento Técnico
**Author: Edgar Phye Parga

---

## 📑 ÍNDICE

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Arquitectura del Sistema](#2-arquitectura-del-sistema)
3. [Base de Datos](#3-base-de-datos)
4. [Módulos y Controladores](#4-módulos-y-controladores)
5. [Reglas de Negocio](#5-reglas-de-negocio)
6. [Lógica de Incidencias](#6-lógica-de-incidencias)
7. [Sistema de Notificaciones](#7-sistema-de-notificaciones)
8. [Auditoría y Trazabilidad](#8-auditoría-y-trazabilidad)
9. [Integración Biométrica](#9-integración-biométrica)
10. [Inteligencia Artificial](#10-inteligencia-artificial)
11. [Seguridad](#11-seguridad)
12. [APIs y Endpoints](#12-apis-y-endpoints)
13. [Variables de Entorno](#13-variables-de-entorno)
14. [Mantenimiento](#14-mantenimiento)
15. [Diagramas y Flujos](#15-diagramas-y-flujos)

---

## 1. RESUMEN EJECUTIVO

### 1.1 Propósito del Sistema

Sistema integral para el control de asistencia del personal administrativo de la SEP mediante reloj biométrico ZKTeco, con validación de incidencias por parte de jefes inmediatos y aplicación automática de reglas institucionales.

### 1.2 Características Principales

| Característica | Descripción |
|----------------|-------------|
| **Registro biométrico** | Huella digital y reconocimiento facial |
| **Validación de incidencias** | Flujo de aprobación/rechazo por jestes |
| **Notas automáticas** | Generación de notas malas según reglas |
| **Alertas tempranas** | Sistema de notificaciones proactivas |
| **Auditoría completa** | Trazabilidad de todas las acciones |
| **Análisis predictivo** | IA para detección de patrones de riesgo |

### 1.3 Tecnologías Utilizadas

| Componente | Tecnología |
|------------|------------|
| Backend | PHP 8.1+ |
| Frontend | JavaScript (Vanilla + Bootstrap 5) |
| Base de datos | MySQL/MariaDB |
| Biométrico | ZKTeco SDK |
| IA | Python/R (Pronósticos) |
| Servidor | Nginx + PHP-FPM |

---

## 2. ARQUITECTURA DEL SISTEMA

### 2.1 Estructura de Directorios

```
sistema_biometrico/
├── api/                          # Endpoints API RESTful
├── assets/                       # Recursos estáticos
│   ├── css/                      # Hojas de estilo
│   ├── js/                       # Scripts JavaScript
│   └── img/                      # Imágenes
├── controllers/                  # Controladores MVC (34 archivos)
├── docs/                         # Documentación adicional
├── helpers/                      # Funciones helper reutilizables
├── logs/                         # Archivos de log del sistema
├── models/                       # Modelos de datos (24 archivos)
├── public/                       # Archivos públicos accesibles
├── scripts/                     # Scripts de mantenimiento y cron
├── services/                    # Servicios especializados
│   ├── AuditService.php          # Servicio de auditoría
│   ├── PushNotificationService.php # Servicio de notificaciones push
│   ├── ZKTecoService.php         # Comunicación con dispositivo biométrico
│   ├── RegistroJustificacionService.php # Registro de justificaciones
│   ├── AgenteIAService.php       # Análisis inteligente
│   └── HorarioLaboral.php        # Cálculo de horarios
├── sql/                          # Scripts SQL incrementales
├── src/                          # Código fuente adicional
├── uploads/                      # Archivos subidos por usuarios
│   └── documentos/               # Documentos de justificación
├── views/                        # Vistas PHP (organizadas por módulo)
├── tests/                        # Pruebas unitarias
├── vendor/                       # Dependencias Composer
├── index.php                     # Punto de entrada principal
├── routes.php                    # Enrutamiento de solicitudes
├── config.php                    # Configuración global
├── composer.json                 # Dependencias PHP
├── maintenance.php               # Script de mantenimiento
└── update_db.php                 # Actualizador de base de datos
```

### 2.2 Patrón de Arquitectura

El sistema implementa el patrón **MVC (Model-View-Controller)** con las siguientes características:

| Patrón | Implementación |
|--------|----------------|
| **Model** | Clases en `/models/` que extienden Database |
| **View** | Archivos PHP en `/views/` con Bootstrap 5 |
| **Controller** | Clases en `/controllers/` que heredan de BaseController |
| **Singleton** | Database::getInstance() para conexiones |
| **Services** | Lógica de negocio compleja en `/services/` |

### 2.3 Flujo de Solicitud HTTP

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENTE (Browser)                        │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                       index.php (Entry Point)                   │
│  - Carga configuración (config.php)                            │
│  - Inicia sesión                                                │
│  - Autenticación                                                │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                      routes.php (Router)                        │
│  - Parseo de URL                                                │
│  - Dispatch a controlador                                       │
│  - Métodos: GET, POST, PUT, DELETE                               │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│              Controlador (ValidacionJefeController.php)         │
│  - Validación de permisos                                       │
│  - Procesamiento de datos                                      │
│  - Respuesta JSON                                               │
└─────────────────────────────────────────────────────────────────┘
                                │
                    ┌───────────┴───────────┐
                    ▼                       ▼
┌──────────────────────────┐   ┌──────────────────────────┐
│      Model               │   │        View               │
│ (ValidacionJefe.php)     │   │  (validaciones/index.php) │
│ - Consulta a BD          │   │ - Render HTML             │
│ - Lógica de negocio      │   │ - JavaScript (AJAX)       │
│ - Retorna datos          │   │ - Bootstrap 5 UI          │
└──────────────────────────┘   └──────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────────┐
│               Database (MySQL/MariaDB)                          │
│  - 63 tablas                                                    │
│  - Transacciones                                                │
│  - Índices optimizados                                          │
└─────────────────────────────────────────────────────────────────┘
```

### 2.4 Componentes del Sistema

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CAPAS DEL SISTEMA                           │
├─────────────────────────────────────────────────────────────────────┤
│  CAPA DE PRESENTACIÓN (Views)                                       │
│  ├── index.php (Dashboard)                                          │
│  ├── views/empleados/                                               │
│  ├── views/validaciones/                                            │
│  ├── views/reportes/                                                │
│  └── assets/js/ (validaciones.js, empleados.js)                     │
├─────────────────────────────────────────────────────────────────────┤
│  CAPA DE CONTROL (Controllers)                                     │
│  ├── AuthController                                                │
│  ├── EmpleadoController                                            │
│  ├── AsistenciaController                                           │
│  ├── ValidacionJefeController                                      │
│  ├── JustificacionController                                       │
│  ├── ComisionController                                            │
│  ├── HorarioController                                             │
│  ├── NotasMalasController                                           │
│  ├── SancionController                                             │
│  ├── ReportesController                                             │
│  ├── AgenteIAController                                            │
│  └── ConfiguracionController                                        │
├─────────────────────────────────────────────────────────────────────┤
│  CAPA DE LÓGICA (Services)                                         │
│  ├── AuditService (Auditoría)                                      │
│  ├── PushNotificationService (Push FCM)                           │
│  ├── ZKTecoService (Biométrico)                                    │
│  ├── RegistroJustificacionService                                  │
│  ├── AgenteIAService                                               │
│  └── HorarioLaboral                                                 │
├─────────────────────────────────────────────────────────────────────┤
│  CAPA DE DATOS (Models)                                            │
│  ├── Database (Conexión Singleton)                                  │
│  ├── Empleado                                                       │
│  ├── Retardo                                                        │
│  ├── ValidacionJefe                                                 │
│  ├── NotaMala                                                       │
│  ├── Asistencia                                                     │
│  ├── HorarioLaboral                                                 │
│  └── [18 modelos adicionales]                                      │
├─────────────────────────────────────────────────────────────────────┤
│  CAPA DE ALMACENAMIENTO (MySQL)                                    │
│  └── 63 tablas con relaciones completas                            │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. BASE DE DATOS

### 3.1 Listado Completo de Tablas (63 tablas)

La base de datos del sistema contiene **63 tablas** organizadas en las siguientes categorías:

#### 3.1.1 Core (Núcleo del Sistema)

| Tabla | Descripción | Registros Clave |
|-------|-------------|-----------------|
| `empleados` | Catálogo principal de empleados | id, numero_empleado, nombre, apellido, rfc, curp, area_id |
| `usuarios` | Usuarios del sistema | id, empleado_id, username, password, rol |
| `departamentos` | Áreas organizacionales | id, nombre, clave |
| `subdirecciones` | Subdirecciones | id, nombre, jefe_id |
| `asistencia` | Registro de进出 del personal | id, empleado_id, fecha, hora_entrada, hora_salida |
| `retardos` | Detalle de retardos clasificados | id, empleado_id, minutos_retardo, tipo_retraso |
| `notas_malas` | Registro de notas malas | id, empleado_id, tipo, cantidad, periodo |
| `sanciones` | Sanciones aplicadas | id, empleado_id, tipo, descripcion |

#### 3.1.2 Validaciones

| Tabla | Descripción |
|-------|-------------|
| `incidencias_no_validadas` | Incidencias pendientes de validación |
| `validaciones_jefe` | Validaciones realizadas por jestes |
| `reglas_validacion` | Reglas de validación configurables |

#### 3.1.3 Justificaciones

| Tabla | Descripción |
|-------|-------------|
| `justificaciones` | Solicitudes de justificación |
| `tipos_justificacion` | Catálogo de tipos (17 tipos) |
| `comisiones` | Comisiones otorgadas |
| `ausencias` | Registro de ausencias |
| `licencias_medicas` | Licencias médicas |
| `vacaciones` | Registro de vacaciones |
| `dias_economicos` | Días económicos |
| `cuidados_maternos` | Permisos maternos |
| `cuidados_paternos` | Permisos paternos |
| `constancias_tiempo` | Constancias médicas de tiempo |

#### 3.1.4 Notificaciones y Alertas

| Tabla | Descripción |
|-------|-------------|
| `alertas_tempranas` | Alertas para empleados |
| `audit_logs` | Registro de auditoría |
| `device_tokens` | Tokens para push notifications |
| `notificaciones_licencias` | Notificaciones de licencias |

#### 3.1.5 Configuración

| Tabla | Descripción |
|-------|-------------|
| `horarios_laborales` | Definición de horarios |
| `empleado_horarios` | Horarios por empleado |
| `reglas_negocio` | Configuración de reglas |
| `dispositivos_biometricos` | Configuración de dispositivos |
| `dias_festivos` | Días festivos |

#### 3.1.6 Biométrico

| Tabla | Descripción |
|-------|-------------|
| `huellas_empleados` | Templates biométricos |
| `zkteco_empleado_mapeo` | Mapeo ID ZKTeco ↔ Sistema |
| `logs_dispositivo_zk` | Logs del dispositivo |
| `dispositivos_biometricos` | Dispositivos registrados |

#### 3.1.7 Inteligencia Artificial

| Tabla | Descripción |
|-------|-------------|
| `ai_alertas` | Alertas del sistema de IA |
| `ai_metricas_tiempo_real` | Métricas en tiempo real |
| `datos_entrenamiento_rn` | Datos para redes neuronales |
| `pronosticos_riesgo` | Pronósticos de riesgo |
| `ai_pesos_entrenamiento` | Pesos del modelo entrenado |
| `ai_snapshots_diarios` | Snapshots del modelo |

#### 3.1.8 Catálogos

| Tabla | Descripción |
|-------|-------------|
| `plazas` | Catálogo de plazas |
| `direcciones` | Direcciones de empleados |
| `catalogos_mandos` | Catálogos de mandos |
| `tipos_justificacion` | Tipos de justificación |

### 3.2 Esquema Detallado de Tablas Principales

#### 3.2.1 asistencia

```sql
CREATE TABLE asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME,
    hora_salida TIME,
    tipo_asistencia ENUM(
        'entrada',      -- Solo registro de entrada
        'salida',       -- Solo registro de salida
        'completa',     -- Entrada y salida completas
        'con_retardo',  -- Asistencia con retardo
        'normal',       -- Asistencia normal (a tiempo)
        'por_definir'   -- Pendiente de clasificación
    ) DEFAULT 'entrada',
    estado_validacion VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_empleado_fecha (empleado_id, fecha),
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo_asistencia),
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3.2.2 retardos

```sql
CREATE TABLE retardos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    zk_empleado_id_original VARCHAR(50),
    fecha DATE NOT NULL,
    hora_entrada TIME,
    hora_salida TIME,
    fecha_asistencia DATE,
    asistencia_id INT,
    minutos_retardo INT NOT NULL DEFAULT 0,
    tipo_retraso ENUM(
        'normal',           -- Sin clasificación
        'retardo_menor',    -- 11-20 minutos
        'retardo_mayor',    -- 21-30 minutos
        'falta',            -- Mayor a 30 minutos
        'comision_entrada', -- Comisión en entrada
        'comision_salida',  -- Comisión en salida
        'comision_todo_dia',-- Comisión día completo
        'dia_economico',   -- Día económico
        'ausencia'         -- Ausencia
    ) NOT NULL,
    justificado TINYINT(1) DEFAULT 0,
    estado_validacion VARCHAR(50),
    aprobado_por INT,
    fecha_aprobacion TIMESTAMP,
    motivo_justificacion TEXT,
    requiere_validacion_jefe TINYINT(1) DEFAULT 0,
    evidencia_adjunta VARCHAR(255),
    
    INDEX idx_empleado_fecha (empleado_id, fecha),
    INDEX idx_tipo_retraso (tipo_retraso),
    INDEX idx_estado (estado_validacion),
    INDEX idx_minutos (minutos_retardo),
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3.2.3 notas_malas

```sql
CREATE TABLE notas_malas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    retardo_id INT,
    tipo ENUM(
        'retardo_menor',            -- Retardo 11-20 min
        'retardo_mayor',            -- Retardo 21-30 min
        'inasistencia',            -- Falta (más de 30 min)
        'falta_retardo_rechazado', -- Retardo rechazado
        'comision_rechazada',      -- Comisión rechazada
        'otro'                      -- Otros tipos
    ) NOT NULL,
    cantidad INT DEFAULT 1,
    periodo DATE NOT NULL,
    motivo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_empleado_periodo (empleado_id, periodo),
    INDEX idx_tipo (tipo),
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (retardo_id) REFERENCES retardos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3.2.4 validaciones_jefe

```sql
CREATE TABLE validaciones_jefe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incidencia_id INT NOT NULL,
    tipo_incidencia VARCHAR(50) NOT NULL,
    empleado_id INT NOT NULL,
    jefe_id INT NOT NULL,
    estado ENUM(
        'pendiente',      -- Esperando decisión
        'aprobado',       -- Aprobado por jefe
        'rechazado',      -- Rechazado por jefe
        'sin_solicitud'   -- Incidencia sin justificación
    ) DEFAULT 'pendiente',
    motivo_validacion TEXT,
    comentarios_adicionales TEXT,
    evidencia_recibida TINYINT(1) DEFAULT 0,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_validacion TIMESTAMP,
    
    INDEX idx_empleado (empleado_id),
    INDEX idx_jefe (jefe_id),
    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo_incidencia),
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (jefe_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3.2.5 audit_logs

```sql
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT,
    empleado_id INT,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    registro_id INT,
    datos_anteriores JSON,
    datos_nuevos JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    
    INDEX idx_fecha (fecha),
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_modulo (modulo),
    INDEX idx_empleado (empleado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3.2.6 device_tokens

```sql
CREATE TABLE device_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    token VARCHAR(500) NOT NULL,
    tipo_dispositivo ENUM('android', 'ios', 'web') DEFAULT 'android',
    ultimo_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_empleado (empleado_id),
    UNIQUE KEY unique_token (token),
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3.2.7 alertas_tempranas

```sql
CREATE TABLE alertas_tempranas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo ENUM(
        'critica',       -- Alertas críticas (despido)
        'advertencia',  -- Advertencias (rechazos)
        'precaucion',   -- Precaución (solicitar info)
        'informativa'   -- Información general
    ) NOT NULL,
    nivel ENUM('urgente', 'alta', 'media', 'baja') NOT NULL,
    mensaje TEXT NOT NULL,
    datos_json LONGTEXT,
    leida TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_empleado (empleado_id),
    INDEX idx_leida (leida),
    INDEX idx_tipo (tipo),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3.2.8 tipos_justificacion

```sql
CREATE TABLE tipos_justificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo_incidencia VARCHAR(50) NOT NULL,
    requiere_aprobacion TINYINT(1) DEFAULT 1,
    requiere_documento TINYINT(1) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17 tipos de justificación
INSERT INTO tipos_justificacion (nombre, descripcion, tipo_incidencia, requiere_aprobacion, requiere_documento) VALUES
('Retardo menor', 'Retardo menor a 30 minutos', 'retardo', 0, 0),
('Retardo mayor', 'Retardo mayor a 30 minutos', 'retardo', 0, 0),
('Comisión de entrada', 'Comisión - Falta de registro de entrada', 'comision_entrada', 1, 0),
('Comisión de salida', 'Comisión - Falta de registro de salida', 'comision_salida', 1, 1),
('Comisión todo el día', 'Comisión - Sin registro de entrada y salida', 'comision_todo_dia', 1, 1),
('Día económico', 'Día económico solicitado', 'dia_economico', 1, 1),
('Licencia Médica', 'Licencia por enfermedad', 'licencia_medica', 1, 0),
('Vacaciones', 'Solicitud de vacaciones', 'vacaciones', 1, 0),
('Cuidados maternos', 'Cuidados maternos', 'cuidados_maternos', 1, 1),
('Falta', 'Falta por llegada tardía de más de 30 minutos', 'falta', 1, 1),
('Constancia de Tiempo', 'Constancia médica de tiempo', 'constancia_tiempo', 1, 0),
('Programa Deportivo SEP-SNTE', 'Programa Deportivo SEP-SNTE', 'PDSEP-SNTE', 1, 0),
('CLIDDA', 'CLIDDA', 'CLIDDA', 1, 0),
('Estimulos y Recompensas', 'Estimulos y Recompensas', 'EYR', 1, 0),
('Desalojo de Edificio', 'Desalojo de Edificio', 'DE', 1, 0),
('Fumigacion', 'Fumigacion', 'F', 1, 0),
('Cuidados paternos', 'Cuidados paternos', 'cuidados_paternos', 1, 0);
```

### 3.3 Relaciones Entre Entidades (ER)

```
┌─────────────────┐         ┌─────────────────┐
│   SUBDIRECCIÓN  │────────<│  DEPARTAMENTO   │
└─────────────────┘         └─────────────────┘
        │                          │
        └────────┬─────────────────┘
                 ▼
         ┌─────────────────┐
         │   EMPLEADO      │────────┐
         └─────────────────┘        │
                 │                  │
        ┌────────┼────────┐         │
        ▼        ▼        ▼         ▼
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│ASISTENCIA│ │ RETARDOS │ │NOTAS_MALAS│ │SANCIONES │
└──────────┘ └──────────┘ └──────────┘ └──────────┘
        │        │        │        │
        └────────┴────────┴────────┘
                 │
                 ▼
         ┌──────────────────┐
         │VALIDACIONES_JEFE │
         └──────────────────┘
                 │
                 ▼
           ┌───────────┐
           │  USUARIO  │
           └───────────┘
```

### 3.4 Índices y Optimización

| Tabla | Índice | Propósito |
|-------|--------|-----------|
| asistencia | idx_empleado_fecha | Búsqueda por empleado y fecha |
| retardos | idx_tipo_retraso | Filtrado por tipo de retardo |
| notas_malas | idx_empleado_periodo | Reportes quincenales/mensuales |
| validaciones_jefe | idx_estado | Filtrado por estado |
| audit_logs | idx_fecha | Consulta histórica |
| alertas_tempranas | idx_empleado_leida | Panel del empleado |

---

## 4. MÓDULOS Y CONTROLADORES

### 4.1 Listado de Controladores (34 archivos)

El sistema cuenta con **34 controladores** que manejan toda la lógica de negocio:

#### 4.1.1 Controladores de Autenticación y Usuario

| Controlador | Archivo | Función Principal |
|-------------|---------|-------------------|
| **AuthController** | `AuthController.php` | Login, logout, recuperación de contraseña |
| **UsuarioController** | `UsuarioController.php` | Gestión completa de usuarios, permisos, roles |

#### 4.1.2 Controladores de Gestión de Empleados

| Controlador | Archivo | Función Principal |
|-------------|---------|-------------------|
| **EmpleadoController** | `EmpleadoController.php` | CRUD completo de empleados, importación, visualización |
| **HuellaController** | `HuellaController.php` | Registro y gestión de huellas biométricas |
| **DispositivoBiometricoController** | `DispositivoBiometricoController.php` | Configuración de dispositivos ZKTeco |

#### 4.1.3 Controladores de Asistencia y Tiempo

| Controlador | Archivo | Función Principal |
|-------------|---------|-------------------|
| **AsistenciaController** | `AsistenciaController.php` | Registro, consulta y reportes de asistencia |
| **HorarioController** | `HorarioController.php` | Gestión de horarios laborales |
| **HorariosController** | `HorariosController.php` | Administración de horarios por empleado |
| **BiometricosController** | `BiometricosController.php` | Integración con dispositivos biométricos |

#### 4.1.4 Controladores de Validaciones

| Controlador | Archivo | Función Principal |
|-------------|---------|-------------------|
| **ValidacionJefeController** | `ValidacionJefeController.php` | Aprobación/rechazo de incidencias por jestes |
| **JustificacionController** | `JustificacionController.php` | Gestión de justificaciones, notas malas |
| **RegistroJustificacionController** | `RegistroJustificacionController.php` | Registro rápido de justificaciones |
| **NotasMalasController** | `NotasMalasController.php` | Consulta y administración de notas malas |
| **SancionController** | `SancionController.php` | Gestión de sanciones |

#### 4.1.5 Controladores de Permisos y Licencias

| Controlador | Archivo | Función Principal |
|-------------|---------|-------------------|
| **ComisionController** | `ComisionController.php` | Gestión de comisiones |
| **DiasEconomicosController** | `DiasEconomicosController.php` | Administración de días económicos |
| **CiclosController** | `CiclosController.php` | Gestión de ciclos laborales |

#### 4.1.6 Controladores de Inteligencia Artificial

| Controlador | Archivo | Función Principal |
|-------------|---------|-------------------|
| **AgenteIAController** | `AgenteIAController.php` | Análisis inteligente, predicción de retrasos |
| **AgenteInteligenteController** | `AgenteInteligenteController.php` | Agente conversacional |
| **AIController** | `AIController.php` | endpoints de IA |
| **AICronController** | `AICronController.php` | Tareas programadas de IA |
| **AnalisisPredictivoController** | `AnalisisPredictivoController.php` | Análisis predictivo de riesgos |

#### 4.1.7 Controladores de Reportes y Dashboard

| Controlador | Archivo | Función Principal |
|-------------|---------|-------------------|
| **DashboardController** | `DashboardController.php` | Panel de control principal |
| **ReportesController** | `ReportesController.php` | Generación de reportes |
| **LogsController** | `LogsController.php` | Visor de logs del sistema |

#### 4.1.8 Controladores de Configuración

| Controlador | Archivo | Función Principal |
|-------------|---------|-------------------|
| **ConfiguracionController** | `ConfiguracionController.php` | Configuración general del sistema |
| **CatalogosController** | `CatalogosController.php` | Gestión de catálogos |
| **DatabaseController** | `DatabaseController.php` | Administración de base de datos |
| **EmailController** | `EmailController.php` | Envío de correos |
| **SoporteController** | `SoporteController.php` | Sistema de soporte |
| **ZKTecoController** | `ZKTecoController.php` | Utilerías ZKTeco |

### 4.2 Servicios Especializados

#### 4.2.1 Servicios del Sistema

| Servicio | Archivo | Descripción |
|----------|---------|-------------|
| **AuditService** | `services/AuditService.php` | Registro de auditoría completo |
| **PushNotificationService** | `services/PushNotificationService.php` | Envío de notificaciones push via FCM |
| **ZKTecoService** | `services/ZKTecoService.php` | Comunicación con dispositivo ZKTeco |
| **RegistroJustificacionService** | `services/RegistroJustificacionService.php` | Registro de justificaciones |
| **AgenteIAService** | `services/AgenteIAService.php` | Análisis inteligente de datos |

#### 4.2.2 Modelos Base

| Modelo | Descripción |
|--------|-------------|
| **Database** | Conexión Singleton a MySQL |
| **HorarioLaboral** | Cálculo y clasificación de retardos |
| **ValidacionJefe** | Lógica de validación de incidencias |
| **Retardo** | Modelo de retardos |
| **Empleado** | Modelo de empleados |
| **NotaMala** | Modelo de notas malas |

### 4.3 Flujo de Datos Entre Componentes

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        FLUJO DE DATOS DEL SISTEMA                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐            │
│  │  Reloj       │────>│  ZKTeco      │────>│  Asistencia  │            │
│  │  Biométrico  │     │  Service     │     │  Controller  │            │
│  └──────────────┘     └──────────────┘     └──────────────┘            │
│                                                 │                      │
│                                                 ▼                      │
│                                          ┌──────────────┐            │
│                                          │  Retardos    │            │
│                                          │  Model       │            │
│                                          └──────────────┘            │
│                                                 │                      │
│                                                 ▼                      │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐            │
│  │  Empleado    │<────│  Validacion  │<────│  Justific    │            │
│  │  View        │     │  Jefe        │     │  Controller │            │
│  └──────────────┘     │  Model       │     └──────────────┘            │
│         │              └──────────────┘                                 │
│         │                     │                                        │
│         ▼                     ▼                                        │
│  ┌──────────────┐     ┌──────────────┐                                 │
│  │  Alertas     │<────│  Notas       │                                 │
│  │  Tempranas   │     │  Malas       │                                 │
│  └──────────────┘     └──────────────┘                                 │
│         │                     │                                        │
│         ▼                     ▼                                        │
│  ┌──────────────┐     ┌──────────────┐                                 │
│  │  Push        │     │  Audit       │                                 │
│  │  Notif       │     │  Service     │                                 │
│  └──────────────┘     └──────────────┘                                 │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 5. REGLAS DE NEGOCIO

### 5.1 Clasificación de Retardos

| Minutos | Clasificación | Consecuencia |
|---------|---------------|--------------|
| 1-10 | Tolerancia | Sin nota mala |
| 11-20 | Retardo menor | 2 = 1 nota mala |
| 21-30 | Retardo mayor | 1 = 1 nota mala |
| >30 | Falta | Falta + reporte a RH |

### 5.2 Reglas de Acumulación

| Regla | Descripción |
|-------|-------------|
| 2 retardos menores/quincena | = 1 nota mala |
| >2 retardos (combinados) | = nota mala automática |
| 2 retardos/quincena | Límite - pierde puntualidad |
| 4 notas malas/mes | = Sanción |
| 5 notas malas/mes | = 1 día suspensión |
| 7 suspensiones/año | = Despido |

### 5.3 Reglas de Quincena

- **1ra Quincena**: Días 1-15
- **2da Quincena**: Días 16-30/31
- No considerar fechas fuera del rango para cada quincena

### 5.4 Tipos de Justificación

El sistema soporta los siguientes tipos de justificación, cada uno con reglas específicas:

#### 5.4.1 Retardos

| Tipo | ID | Descripción | Aprobación | Documento |
|------|-----|-------------|------------|-----------|
| Retardo menor | 15 | Retardo menor a 30 minutos | No | No |
| Retardo mayor | 16 | Retardo mayor a 30 minutos | No | No |
| Falta | 24 | Falta por llegada tardía de más de 30 minutos | Sí | Sí |

#### 5.4.2 Comisiones

| Tipo | ID | Descripción | Aprobación | Documento | Consecuencia al rechazar |
|------|-----|-------------|------------|-----------|-------------------------|
| Comisión de entrada | 17 | Comisión - Falta de registro de entrada | Sí | No | Nota mala por comisión rechazada |
| Comisión de salida | 18 | Comisión - Falta de registro de salida | Sí | Sí | Nota mala por comisión rechazada |
| Comisión todo el día | 19 | Comisión - Sin registro de entrada y salida | Sí | Sí | Nota mala por comisión rechazada |

#### 5.4.3 Permisos

| Tipo | ID | Descripción | Aprobación | Documento | Notas |
|------|-----|-------------|------------|-----------|-------|
| Día económico | 20 | Día económico solicitado | Sí | Sí | Máximo 5 al año |
| Licencia Médica | 21 | Licencia por enfermedad | Sí | No | Requiere certificado |
| Constancia de Tiempo | 25 | Constancia médica de tiempo | Sí | No | - |
| Cuidados maternos | 23 | Cuidados maternos | Sí | Sí | Máximo 90 días |
| Cuidados paternos | 31 | Cuidados paternos | Sí | No | Máximo 90 días |

#### 5.4.4 Vacaciones y Autres

| Tipo | ID | Descripción | Aprobación | Documento |
|------|-----|-------------|------------|-----------|
| Vacaciones | 22 | Solicitud de vacaciones | Sí | No |
| Programa Deportivo SEP-SNTE | 26 | Programa Deportivo | Sí | No |
| CLIDDA | 27 | CLIDDA | Sí | No |
| Estimulos y Recompensas | 28 | Estímulos y Recompensas | Sí | No |
| Desalojo de Edificio | 29 | Desalojo de Edificio | Sí | No |
| Fumigación | 30 | Fumigación | Sí | No |

### 5.5 Reglas Específicas por Tipo

#### 5.5.1 Comisiones
- **Comisión rechazada** → Genera nota mala con tipo `comision_rechazada`
- El jefe debe justificar el rechazo
- Se notifica al empleado con la consecuencia

#### 5.5.2 Días Económicos
- Máximo 5 días económicos por año
- Solicitud previa obrigatória
- No genera nota mala si está aprobado
- Debe programarse con anticipación

#### 5.5.3 Licencias Médicas
- Requiere evidencia (certificado médico)
- Puede justificarse después del retorno
- No genera nota mala si está justificada
- Se valida contra la tabla `control_licencias_medicas`

#### 5.5.4 Vacaciones
- Requiere aprobación de RH
- No afecta notas malas
- Se descuenta de vacaciones pendientes

#### 5.5.5 Cuidados Maternos/Paternos
- Máximo 90 días por año
- Requiere documentación oficial
- Se marca en tabla `cuidados_maternos` / `cuidados_paternos`

### 5.6 Flujo de Validación por Tipo

```
TIPO = RETARDO
├── Aprobado → justificado = 1, sin consecuencia
└── Rechazado → Generar nota mala según minutos
    ├── 1-10 min: Sin nota (tolerancia)
    ├── 11-20 min: Nota tipo 'retardo_menor'
    ├── 21-30 min: Nota tipo 'retardo_mayor'
    └── >30 min: Falta + tipo 'inasistencia'

TIPO = COMISIÓN
├── Aprobado → justificado = 1
└── Rechazado → Nota tipo 'comision_rechazada'

TIPO = DÍA ECONÓMICO / VACACIONES / LICENCIA
├── Aprobado → justificado = 1
└── Rechazado → Requiere nueva justificación

TIPO = FALTA
├── Justificada → Convertir a justificado
└── No justificada → Queda como falta
```

---

## 6. LÓGICA DE INCIDENCIAS

### 6.1 Tipos de Incidencias

El sistema maneja los siguientes tipos de incidencias que pueden ser validadas por los jestes:

#### 6.1.1 Incidencias Automáticas (Registradas por el Sistema)

| Tipo | Descripción | Origen | Requiere Justificación |
|------|-------------|--------|----------------------|
| Retardo menor (11-20 min) | Llegada tarde 11-20 minutos | Biométrico | Opcional |
| Retardo mayor (21-30 min) | Llegada tarde 21-30 minutos | Biométrico | Recomendada |
| Falta (>30 min) | Llegada tarde más de 30 minutos | Biométrico | Obligatoria |
| Ausencia | No hay registro de entrada | Biométrico | Obligatoria |

#### 6.1.2 Incidencias Manuales (Solicitadas por Empleado)

| Tipo | Descripción | Aprobación | Evidencia |
|------|-------------|------------|-----------|
| Comisión | Comisión de trabajo | Sí (Jefe) | Opcional |
| Día económico | Día económico | Sí (RH) | Obligatoria |
| Licencia médica | Por enfermedad | Sí (Jefe) | Certificado |
| Vacaciones | Periodo vacacional | Sí (RH) | Solicitud |
| Cuidados maternos/paternos | Permiso familiar | Sí (RH) | Documento |

### 6.2 Flujo de Validación

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    FLUJO DE VALIDACIÓN DE INCIDENCIAS                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  1. REGISTRO DE INCIDENCIA                                               │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐              │
│  │  Biométrico │────>│  Clasificar │────>│  Insertar   │              │
│  │  (ZKTeco)   │     │  (Horario)  │     │  en retardos│              │
│  └─────────────┘     └─────────────┘     └─────────────┘              │
│                                                    │                   │
│  2. NOTIFICACIÓN AL JEFE                            │                   │
│  ┌─────────────┐     ┌─────────────┐              │                   │
│  │  Validacion  │────>│  Alerta     │              │                   │
│  │ Jefe Model  │     │  temprana   │              │                   │
│  └─────────────┘     └─────────────┘              │                   │
│                                                    │                   │
│  3. DECISIÓN DEL JEFE                              │                   │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐             │
│  │  Aprobado   │     │  Rechazado  │     │Requiere info│             │
│  └──────┬──────┘     └──────┬──────┘     └──────┬──────┘             │
│         │                    │                   │                    │
│         ▼                    ▼                   ▼                    │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐             │
│  │justificado=1│     │Generar nota │     │estado='pen- │             │
│  │estado='apro'│     │mala + verif │     │diente'      │             │
│  └─────────────┘     │acumulacion  │     └─────────────┘             │
│                      └─────────────┘                                 │
│                                                    │                   │
│  4. NOTIFICACIÓN AL EMPLEADO                       │                   │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐             │
│  │  Alerta +   │     │  Alerta +   │     │  Alerta +   │             │
│  │  Push +     │     │  Push +     │     │  Push       │             │
│  │  Auditoría  │     │  Auditoría  │     │  Auditoría  │             │
│  └─────────────┘     └─────────────┘     └─────────────┘             │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.3 Métodos de Procesamiento

#### 6.3.1 ValidacionJefe.php - Métodos Principales

```php
class ValidacionJefe {
    
    // Procesar decisión del jefe
    public function procesarDecision($validacionId, $decision);
    
    // Actualizar retardo según decisión
    private function actualizarRetardo($retardoId, $decision);
    
    // Actualizar comisión según decisión
    private function actualizarComision($comisionId, $decision);
    
    // Verificar acumulación de retardos menores (2 = 1 nota)
    private function verificarAcumulacionRetardosMenores($empleadoId, $retardoId);
    
    // Verificar acumulación general (>2 retardos = nota)
    private function verificarAcumulacionGeneralRetardos($empleadoId, $retardoId);
    
    // Verificar límite quincenal (2 retardos = pierde puntualidad)
    private function verificarLimiteQuincenalRetardos($empleadoId);
    
    // Verificar notas malas del mes (4=sanción, 5=suspensión)
    public function verificarNotasMalasMes($empleadoId);
    
    // Verificar suspensiones anuales (7=despido)
    private function verificarSuspensionesAnuales($empleadoId);
}
```

### 6.4 Casos de Uso

#### Caso 1: Retardo Menor Aprobado
```
Entrada: Retardo de 15 minutos, justificación: "Tráfico"
Proceso: Jefe aprueba → justificado = 1
Salida: Sin nota mala, incidencia justificada
```

#### Caso 2: Retardo Menor Rechazado
```
Entrada: Retardo de 15 minutos, justificación: "Sin justificación válida"
Proceso: Jefe rechaza → 
  1. Generar nota tipo 'retardo_menor'
  2. Verificar acumulación (si 2+ → nota adicional)
  3. Verificar límite quincenal
Salida: Nota mala registrada, empleado notificado
```

#### Caso 3: Comisión Rechazada
```
Entrada: Comisión de 1 día, justificación: "Reunión externa"
Proceso: Jefe rechaza → 
  1. Generar nota tipo 'comision_rechazada'
  2. Marcar comisión como no aprobada
Salida: Nota mala, empleado notificado
```

---

## 7. SISTEMA DE NOTIFICACIONES

### 7.1 Tipos de Notificaciones

El sistema implementa un sistema de notificaciones multifuente:

#### 7.1.1 Alertas en Plataforma (alertas_tempranas)

| Tipo | Nivel | Color | Uso |
|------|-------|-------|-----|
| informativa | baja | 🟢 Verde | Aprobaciones, confirmaciones |
| advertencia | alta | 🟠 Naranja | Rechazos, advertencias |
| precaucion | media | 🔵 Azul | Solicitud de información |
| critica | urgente | 🔴 Rojo | Sanciones, suspensiones, despidos |

#### 7.1.2 Notificaciones Push (Firebase Cloud Messaging)

**Configuración Requerida:**
```bash
FCM_SERVER_KEY=tu_server_key_de_firebase
```

**Tipos de Notificación Push:**
- Validación de incidencia
- Nota mala generada
- Sanción aplicada
- Alerta de suspensión
- Alerta de despido

#### 7.1.3 Notificaciones por Correo (EmailController)

- Envío de constancias
- Notificaciones de incidencias
- Resúmenes semanales

### 7.2 Flujo de Notificaciones

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    FLUJO DE NOTIFICACIONES                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌──────────────┐                                                        │
│  │ Evento del   │                                                        │
│  │ Sistema      │                                                        │
│  └──────┬───────┘                                                        │
│         │                                                                 │
│         ├────────────────────────────────┐                               │
│         ▼                                ▼                               │
│  ┌──────────────┐                ┌──────────────┐                        │
│  │  Alerta en  │                │    Push      │                        │
│  │  Plataforma │                │  Notification│                        │
│  │(alertas_    │                │    (FCM)     │                        │
│  │tempranas)   │                │              │                        │
│  └──────┬───────┘                └──────┬───────┘                        │
│         │                                │                                │
│         │         ┌──────────────────────┼──────────────────────┐        │
│         │         │                      │                      │        │
│         ▼         ▼                      ▼                      ▼        │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐   │
│  │  Empleado    │ │  Dashboard   │ │  Dispositivo │ │   Correo     │   │
│  │  consulta    │ │  Jefe        │ │  Móvil       │ │  Electrónico│   │
│  │  alertas     │ │  recibe      │ │  recibe push │ │  (opcional) │   │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.3 PushNotificationService

```php
class PushNotificationService {
    
    // Registrar token de dispositivo
    public function registrarToken($empleadoId, $token, $tipoDispositivo);
    
    // Enviar notificación a un empleado
    public function enviarNotificacion($empleadoId, $titulo, $mensaje, $data);
    
    // Notificar validación de incidencia
    public function notificarValidacion($empleadoId, $estado, $tipoIncidencia, $mensaje);
    
    // Notificar nota mala generada
    public function notificarNotaMala($empleadoId, $tipo, $descripcion);
    
    // Notificar sanción aplicada
    public function notificarSancion($empleadoId, $tipoSancion, $descripcion);
    
    // Notificar alerta crítica (suspensión/despido)
    public function notificarAlertaCritica($empleadoId, $tipo, $mensaje);
}
```

### 7.4 Mensajes de Notificación por Estado

| Estado | Título | Mensaje |
|--------|--------|---------|
| aprobado | ✅ Incidencia Aprobada | Tu justificación ha sido aprobada... |
| rechazado | ⚠️ Incidencia Rechazada | Tu justificación ha sido rechazada. Consecuencia: nota mala |
| requiere_info | 📋 Información Requerida | Se requiere información adicional... |

---

## 8. AUDITORÍA Y TRAZABILIDAD

### 8.1 Tabla audit_logs

El sistema mantiene un registro completo de auditoría en la tabla `audit_logs`:

#### 8.1.1 Campos de Auditoría

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | Identificador único |
| fecha | DATETIME | Fecha y hora de la acción |
| usuario_id | INT | Usuario que realizó la acción |
| empleado_id | INT | Empleado afectado |
| accion | VARCHAR(100) | Tipo de acción realizada |
| modulo | VARCHAR(100) | Módulo del sistema |
| registro_id | INT | ID del registro afectado |
| datos_anteriores | JSON | Estado anterior |
| datos_nuevos | JSON | Estado nuevo |
| ip_address | VARCHAR(45) | IP del cliente |
| user_agent | VARCHAR(500) | Navegador/dispositivo |

#### 8.1.2 Acciones Auditadas

| Acción | Módulo | Descripción |
|--------|--------|-------------|
| validacion_jefe | validaciones | Validación de incidencia |
| nota_mala_generada | notas_malas | Nota mala creada |
| sancion_aplicada | sanciones | Sanción aplicada |
| empleado_creado | empleados | Nuevo empleado |
| empleado_actualizado | empleados | Datos modificados |
| incidencia_rechazada | incidencias | Incidencia rechazada |
| justificacion_aprobada | justificaciones | Justificación aprobada |

### 8.2 AuditService

```php
class AuditService {
    
    // Registrar acción genérica
    public function log($accion, $modulo, $registroId, $datosAnteriores, $datosNuevos, $empleadoId);
    
    // Registrar validación de jefe
    public function logValidacionJefe($validacionId, $estadoAnterior, $estadoNuevo, $empleadoId, $comentarios);
    
    // Registrar nota mala generada
    public function logNotaMala($notaMalaId, $empleadoId, $tipo, $motivo);
    
    // Registrar sanción aplicada
    public function logSancion($sancionId, $empleadoId, $tipo, $descripcion);
    
    // Obtener historial por empleado
    public function getHistorialEmpleado($empleadoId, $limite = 50);
    
    // Obtener historial por módulo
    public function getHistorialModulo($modulo, $registroId, $limite = 20);
}
```

### 8.3 Diagrama de Auditoría

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         TRAZABILIDAD COMPLETA                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ACCIÓN DEL USUARIO                                                      │
│         │                                                                │
│         ▼                                                                │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐                │
│  │ Controller  │────>│ AuditService│────>│ audit_logs │                │
│  │ (procesa)  │     │   .log()    │     │   (BD)     │                │
│  └─────────────┘     └─────────────┘     └─────────────┘                │
│         │                                           │                   │
│         │            ┌──────────────────────────────┼───────────────┐   │
│         │            │ REGISTRO COMPLETO             │               │   │
│         ▼            ▼                             ▼               ▼   │
│  ┌─────────────┐ ┌─────────────┐              ┌─────────────┐        │
│  │  Alerta al  │ │   Push      │              │   historial │        │
│  │  empleado   │ │  notif      │              │   (vistas) │        │
│  └─────────────┘ └─────────────┘              └─────────────┘        │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 9. INTEGRACIÓN BIOMÉTRICA

### 9.1 Dispositivo ZKTeco

El sistema se integra con dispositivos biométricos ZKTeco mediante SDK:

| Característica | Valor |
|----------------|-------|
| Modelo | ZKTeco estándar |
| Protocolo | HTTP API |
| Puerto | 8080 |
| Autenticación | Huella + Contraseña |
| Formato de datos | DAT (1_attlog) |

### 9.2 Flujo de Datos Biométricos

```
┌─────────────────────────────────────────────────────────────────────────┐
│              PROCESAMIENTO DE DATOS BIOMÉTRICOS                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  1. EXPORTACIÓN                                                          │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐                │
│  │ Reloj       │────>│ Archivo     │────>│ Sistema     │                │
│  │ ZKTeco      │     │ 1_attlog   │     │ (uploads/) │                │
│  └─────────────┘     └─────────────┘     └─────────────┘                │
│                                                                        │
│  2. PROCESAMIENTO                                                        │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐               │
│  │ZKTecoService│────>│  Parsear    │────>│  Clasificar │               │
│  │.procesar()  │     │  registros  │     │  (ent/sal) │               │
│  └─────────────┘     └─────────────┘     └─────────────┘               │
│                                                                        │
│  3. INSERCIÓN                                                            │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐               │
│  │  Horario   │────>│  Calcular   │────>│  Insertar   │               │
│  │ Laboral    │     │  retardo    │     │  en BD      │               │
│  └─────────────┘     └─────────────┘     └─────────────┘               │
│                                                                        │
└─────────────────────────────────────────────────────────────────────────┘
```

### 9.3 Clasificación de Marcas

El sistema clasifica los registros según la hora:

| Rango Horario | Clasificación |
|--------------|---------------|
| 07:00 - 12:00 | Entrada |
| 14:00 - 18:00 | Salida |

### 9.4 ZKTecoService

```php
class ZKTecoService {
    
    // Procesar archivo del dispositivo
    public function procesar($archivoPath, $empleadoId = null);
    
    // Obtener empleados del dispositivo
    public function obtenerEmpleados();
    
    // Sincronizar huellas
    public function sincronizarHuellas($empleadoId);
    
    // Obtener logs del dispositivo
    public function obtenerLogs($fechaInicio, $fechaFin);
}
```

---

## 10. INTELIGENCIA ARTIFICIAL

### 10.1 Módulos de IA del Sistema

| Módulo | Función | Algoritmo |
|--------|---------|-----------|
| **Pronóstico de Retardos** | Predecir retrasos | Regresión lineal |
| **Detección de Anomalías** | Identificar patrones inusuales | Outlier detection |
| **Análisis de Riesgo** | Calcular probabilidad de sanciones | Scoring |
| **Métricas en Tiempo Real** | Dashboard de KPIs | Agregaciones |

### 10.2 Datos de Entrenamiento

El sistema recopila datos para entrenamiento de modelos:

```sql
-- Tabla de datos de entrenamiento
datos_entrenamiento_rn (
    id INT,
    empleado_id INT,
    fecha DATE,
    dia_semana VARCHAR(20),
    hora_entrada TIME,
    minutos_retardo INT,
    tiempo_clima FLOAT,
    dia_festivo BOOLEAN,
    area VARCHAR(100),
    resultado BOOLEAN  -- 1: asistió, 0: no asistió
)
```

### 10.3 Métricas en Tiempo Real

```sql
-- Tabla de métricas
ai_metricas_tiempo_real (
    id INT,
    metricas JSON,
    fecha DATETIME,
    empleados_activos INT,
    retardos_hoy INT,
    promedio_atraso FLOAT
)
```

---

## 11. SEGURIDAD

### 11.1 Autenticación y Sesiones

| Mecanismo | Implementación |
|-----------|----------------|
| Contraseñas | Cifrado AES-256 |
| Sesiones | PHP Sessions con token CSRF |
| Rate limiting | En APIs principales |

### 11.2 Permisos del Sistema

| Permiso | Descripción |
|---------|-------------|
| validaciones_aprobar | Aprobar/rechazar incidencias |
| empleados_editar | Editar datos de empleados |
| reportes_generar | Generar reportes |
| config_editar | Configurar sistema |
| usuarios_admin | Administración de usuarios |

### 11.3 Protección

- **CSRF**: Tokens en todos los formularios
- **SQL Injection**: PDO prepared statements
- **XSS**: htmlspecialchars en salidas
- **SQL Injection**: Validación de entrada

---

## 12. APIs Y ENDPOINTS

### 12.1 Endpoints Principales

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/validaciones/procesar` | POST | Procesar validación |
| `/validaciones/incidencias-empleados` | POST | Obtener incidencias |
| `/validaciones/buscar-pendientes` | POST | Buscar pendientes |
| `/empleados/importar` | POST | Importar empleados |
| `/justificacion/crear` | POST | Crear justificación |
| `/reportes/asistencia` | GET | Reporte de asistencia |
| `/api/biometrico/sync` | POST | Sincronizar biométrico |

### 12.2 Formato de Respuesta

```json
{
    "success": true,
    "data": {
        "empleado_id": 123,
        "nombre": "Juan Pérez"
    },
    "message": "Operación exitosa"
}
```

---

## 13. VARIABLES DE ENTORNO

### 13.1 Configuración Requeridas

```bash
# Base de datos
DB_HOST=localhost
DB_USER=root
DB_PASS=tu_contraseña
DB_NAME=sistema_biometrico

# Seguridad
ENCRYPTION_KEY=tu_clave_32_caracteres_aqui

# Biométrico
BIOMETRIC_API_URL=http://192.168.1.100:8080

# Notificaciones Push
FCM_SERVER_KEY=tu_fcm_server_key
```

---

## 14. MANTENIMIENTO

### 14.1 Scripts de Mantenimiento

| Script | Función |
|--------|---------|
| `maintenance.php` | Mantenimiento general |
| `update_db.php` | Actualizar estructura |
| `backup.sh` | Respaldar base de datos |
| `GeneradorRetardosAutomatico.php` | Regenerar retardos |

### 14.2 Limpieza Automática

- Sesiones expiradas (>24 horas)
- Logs mayores a 30 días
- Archivos temporales

---

## 15. DIAGRAMAS Y FLUJOS

### 15.1 Flujo de Registro de Asistencia

```
RELOJ BIOMÉTRICO
       ↓
Archivo 1_attlog
       ↓
ZKTecoService.procesar()
       ↓
ClasificarPorHorario()
       ↓
¿Cumple horario?
  ├─ SÍ → Asistencia normal
  └─ NO → Calcular minutos retardo
           ↓
        clasificarRetardo(minutos)
           ├─ 1-10: Tolerancia
           ├─ 11-20: Retardo menor
           ├─ 21-30: Retardo mayor
           └─ >30: Falta
           ↓
        Insertar en retardos
           ↓
        Notificar empleado
```

### 15.2 Flujo de Validación

```
EMPLEADO registra justificación
       ↓
JUSTIFICACIONController.crear()
       ↓
ValidacionJefeController (notifica jefe)
       ↓
JEFE recibe notificación
       ↓
JEFE decide:
  ├─ Aprobar → ValidacionJefe.aprobar()
  │            ↓
  │         actualizarRetardo(justificado=1)
  │            ↓
  │         notificarEmpleado(estado='aprobado')
  │
  ├─ Rechazar → ValidacionJefe.rechazar()
  │            ↓
  │         generarNotaMala()
  │            ↓
  │         verificarAcumulacion()
  │            ↓
  │         verificarNotasMalasMes()
  │            ↓
  │         notificarEmpleado(estado='rechazado')
  │
  └─ Solicitar info → notificarEmpleado(estado='pendiente')
```

---

## ANEXO: REFERENCIAS

### Documentos Oficiales
- `manual_de_normas_para_admon_rec_humanos_en_la_sep_2025.pdf`
- `Lógica de Incidencias.docx`

### Scripts de Base de Datos
- `database.sql` - Estructura inicial
- `database_sql/` - Scripts incrementales

### Documentación Adicional
- `README.md` - Inicio rápido
- `DOCUMENTACION_TECNICA_PROFESIONAL_V2.md` - Versión anterior

---

**Versión del Documento**: 3.0
**Fecha de Actualización**: 2026-04-03
**Autor**: Sistema de Asistencia Biométrico - SEP
**Clasificación**: Documento Técnico
- `BIOMETRIC_IMPLEMENTATION.md` - Implementación ZKTeco

---

**Versión del Documento**: 3.0
**Fecha de Actualización**: 2026-04-03
**Autor**: Sistema de Asistencia Biométrico - SEP
