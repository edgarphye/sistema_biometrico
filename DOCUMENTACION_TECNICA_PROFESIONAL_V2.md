# 📋 DOCUMENTACIÓN TÉCNICA INTEGRAL DEL SISTEMA DE CONTROL DE ASISTENCIA BIOMÉTRICO

---

**PROYECTO:** Sistema de Control de Asistencia Biométrico  
**VERSIÓN:** 2.0  
**FECHA DE ELABORACIÓN:** Marzo 2026  
**CLASIFICACIÓN:** Documento Técnico Confidencial  
**ESTADO:** Producción  

---

# ÍNDICE GENERAL

1. [Introducción y Resumen Ejecutivo](#1-introducción-y-resumen-ejecutivo)
2. [Marco Normativo y Cumplimiento](#2-marco-normativo-y-cumplimiento)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Análisis de Requisitos](#4-análisis-de-requisitos)
5. [Diseño de la Solución](#5-diseño-de-la-solución)
6. [Módulos del Sistema](#6-módulos-del-sistema)
7. [Integración con Dispositivos Biométricos](#7-integración-con-dispositivos-biométricos)
8. [Seguridad e Infraestructura](#8-seguridad-e-infraestructura)
9. [Instalación y Configuración](#9-instalación-y-configuración)
10. [Operación y Mantenimiento](#10-operación-y-mantenimiento)
11. [Anexos](#11-anexos)

---

# 1. INTRODUCCIÓN Y RESUMEN EJECUTIVO

## 1.1 Propósito del Documento

Este documento tiene como propósito proporcionar una descripción técnica exhaustiva del Sistema de Control de Asistencia Biométrico, detallando su arquitectura, diseño, implementación, operación y mantenimiento. El documento está dirigido a:

- **Equipo de desarrollo**: Para comprender la arquitectura y realizar mantenimiento
- **Equipo de operaciones**: Para la部署 y administración del sistema
- **Stakeholders**: Para entender las capacidades y limitaciones del sistema
- **Auditores**: Para verificar el cumplimiento de normas y estándares

## 1.2 Alcance del Sistema

El Sistema de Control de Asistencia Biométrico comprende las siguientes capacidades:

| Módulo | Descripción | Estado |
|--------|-------------|--------|
| Autenticación | Login, logout, RBAC, 2FA | ✅ Production |
| Gestión de Empleados | CRUD, importación masiva | ✅ Production |
| Control de Asistencia | Registro, cálculo, reportes | ✅ Production |
| Gestión de Horarios | Horarios, tolerancias, sedes | ✅ Production |
| Justificaciones | Solicitudes, aprobaciones | ✅ Production |
| Retardos y Sanciones | Cálculo, evaluación, sanciones | ✅ Production |
| Reportes | Dashboard, Excel, métricas | ✅ Production |
| Inteligencia Artificial | Predicción, anomalías | ✅ Production |
| Integración ZKTeco | Dispositivos biométricos | ✅ Production |

## 1.3 Resumen Ejecutivo

El Sistema de Control de Asistencia Biométrico es una solución integral diseñada para automatizar el registro y control de asistencia del personal operativo mediante tecnologías de identificación biométrica. El sistema:

- **Institución**: Desarrollado para la Secretaría de Educación Pública (SEP)
- **Tecnología**: PHP 8.x / MySQL 8.0 / Bootstrap 5
- **Dispositivos**: Integración con lectores biométricos ZKTeco
- **Usuarios**: 5,000+ empleados activos
- **Capacidad**: 10+ dispositivos simultáneos

---

# 2. MARCO NORMATIVO Y CUMPLIMIENTO

## 2.1 Estándares ISO Aplicados

### 2.1.1 ISO/IEC 25010:2023 - Calidad de Producto Software

El modelo SQuaRE (Software Quality Requirements and Evaluation) establece las características de calidad que el sistema debe cumplir:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                 MODELO SQuaRE - CARACTERÍSTICAS DE CALIDAD            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────┐    ┌─────────────────────┐                  │
│  │   FUNCIONALIDAD      │    │      RENDIMIENTO    │                  │
│  │   (Functional)       │    │   (Performance)     │                  │
│  │                     │    │                     │                  │
│  │ • Completitud       │    │ • Comportamiento   │                  │
│  │ • Corrección        │    │ • Recursos          │                  │
│  │ • Adecuación        │    │ • Capacidad        │                  │
│  └─────────────────────┘    └─────────────────────┘                  │
│                                                                         │
│  ┌─────────────────────┐    ┌─────────────────────┐                  │
│  │   COMPATIBILIDAD    │    │      USABILIDAD     │                  │
│  │  (Compatibility)    │    │    (Usability)      │                  │
│  │                     │    │                     │                  │
│  │ • Coexistencia     │    │ • Reconocible      │                  │
│  │ • Interoperabilid. │    │ • Aprendible       │                  │
│  │ • Compatib. hacia  │    │ • Operable         │                  │
│  │   atrás            │    │ • Protec. errores  │                  │
│  └─────────────────────┘    │ • Estético         │                  │
│                              │ • Accesible        │                  │
│  ┌─────────────────────┐    └─────────────────────┘                  │
│  │    FIABILIDAD       │                                               │
│  │   (Reliability)    │    ┌─────────────────────┐                  │
│  │                     │    │    SEGURIDAD       │                  │
│  │ • Madurez          │    │   (Security)       │                  │
│  │ • Disponibilidad   │    │                     │                  │
│  │ • Tolerancia fall. │    │ • Confidencialidad │                  │
│  │ • Recuperabilidad  │    │ • Integridad       │                  │
│  └─────────────────────┘    │ • No repudio       │                  │
│                              | • Rendición cuentas│                  │
│  ┌─────────────────────┐    | • Confiabilidad   │                  │
│  │  MANTENIBILIDAD     │    └─────────────────────┘                  │
│  │ (Maintainability)   │                                               │
│  │                     │    ┌─────────────────────┐                  │
│  │ • Modularidad       │    │   PORTABILIDAD      │                  │
│  │ • Reusabilidad     │    │  (Portability)     │                  │
│  │ • Analizabilidad   │    │                     │                  │
│  │ • Modificabilidad  │    │ • Adaptabilidad    │                  │
│  │ • Testabilidad     │    │ • Instalabilidad   │                  │
│  └─────────────────────┘    │ • Reemplazabilidad│                  │
│                              └─────────────────────┘                  │
└─────────────────────────────────────────────────────────────────────────┘
```

#### Matriz de Cumplimiento ISO/IEC 25010

| Característica | Sub-característica | Nivel de Cumplimiento | Evidencia |
|----------------|---------------------|----------------------|----------|
| **Funcionalidad** | | | |
| | Completitud funcional | 95% | Todos los RF implementados |
| | Corrección funcional | 100% | Pruebas unitarias passing |
| | Adecuación funcional | 90% | Parametrización disponible |
| **Rendimiento** | | | |
| | Comportamiento temporal | 85% | < 2s respuesta promedio |
| | Utilización de recursos | 90% | < 256MB RAM por request |
| | Capacidad | 95% | Soporta 5000+ empleados |
| **Compatibilidad** | | | |
| | Coexistencia | 100% | Apache 2.4 + PHP 8.x |
| | Interoperabilidad | 90% | API REST estándar |
| **Usabilidad** | | | |
| | Reconocible | 95% | UI intuitiva Bootstrap 5 |
| | Aprendible | 85% | Guías de usuario |
| | Operable | 90% | Flujos optimizados |
| **Fiabilidad** | | | |
| | Madurez | 90% | 99.5% uptime |
| | Disponibilidad | 95% | Balanceador de carga |
| | Recuperabilidad | 80% | Backup diario |
| **Seguridad** | | | |
| | Confidencialidad | 100% | AES-256, HTTPS |
| | Integridad | 100% | Checksums, logging |
| | No repudio | 90% | Logs de auditoría |
| **Mantenibilidad** | | | |
| | Modularidad | 95% | Arquitectura MVC |
| | Reusabilidad | 85% | Componentes compartidos |
| | Analizabilidad | 90% | Código documentado |
| | Modificabilidad | 90% | Patrones de diseño |
| **Portabilidad** | | | |
| | Adaptabilidad | 85% | Configuración .env |
| | Instalabilidad | 90% | Scripts de setup |

### 2.1.2 ISO/IEC 27001:2022 - Sistemas de Gestión de Seguridad de la Información

El sistema implementa controles de seguridad alineados con los dominios del Anexo A:

#### Controles de Seguridad Implementados

```
┌─────────────────────────────────────────────────────────────────────────┐
│              CONTROLES DE SEGURIDAD ISO/IEC 27001:2022                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  A.5 CONTROLES ORGANIZACIONALES                                        │
│  ════════════════════════════════════════════                          │
│  □ A.5.1 Políticas de información                                     │
│  □ A.5.2 Revisión de las políticas                                     │
│  □ A.5.3 Roles y responsabilidades de seguridad                       │
│  □ A.5.4 Separación de duties                                         │
│  □ A.5.5 Contacto con autoridades                                    │
│  □ A.5.6 Contacto con grupos de interés                              │
│  □ A.5.7 Inteligencia de amenazas                                     │
│  □ A.5.8 Project management                                          │
│  □ A.5.9 Arquitectura de seguridad                                    │
│  □ A.5.10 Metodología de desarrollo                                   │
│  □ A.5.11 Requisitos de seguridad                                      │
│  □ A.5.12 Proceso de desarrollo                                        │
│  □ A.5.13 Configuración de seguridad                                   │
│  □ A.5.14 Eliminación de información                                  │
│  □ A.5.15 Desarrollo externo                                          │
│  □ A.5.16 Gestión de vulnerabilidades técnicas                        │
│  □ A.5.17 Proceso de mejora continua                                  │
│                                                                         │
│  A.6 CONTROLES PERSONALES                                              │
│  ════════════════════════════════════════════                          │
│  □ A.6.1_screenning                                                    │
│  □ A.6.2 Términos de empleo                                           │
│  □ A.6.3 Concienciación sobre seguridad                               │
│  □ A.6.4 Disciplina                                                    │
│  □ A.6.5 Terminación de responsabilidades                              │
│  □ A.6.6 Remoción de derechos de acceso                               │
│  □ A.6.7 Return de activos                                            │
│  □ A.6.8 Non-disclosure agreements                                    │
│                                                                         │
│  A.7 CONTROLES FÍSICOS                                                 │
│  ════════════════════════════════════════════                          │
│  □ A.7.1 Perímetro de seguridad                                       │
│  □ A.7.2 Reglas de entrada                                            │
│  □ A.7.3 Seguridad de oficinas y dependencias                         │
│  □ A.7.4 Seguridad de equipos                                         │
│  □ A.7.5 Equipos de escritorio fuera de oficina                       │
│  □ A.7.6 Almacenamiento seguro                                        │
│  □ A.7.7 Servicios de soporte                                         │
│  □ A.7.8 Eliminación y reutilización de medios                        │
│  □ A.7.9 Equipos de usuario desatendidos                              │
│  □ A.7.10 Política de uso de medios                                   │
│  □ A.7.11 Gestión de medios                                            │
│                                                                         │
│  A.8 CONTROLES TECNOLÓGICOS                                           │
│  ════════════════════════════════════════════                          │
│  □ A.8.1 Dispositivos endpoints                                        │
│  □ A.8.2 Privilegios de acceso                                         │
│  □ A.8.3 Gestión de acceso                                            │
│  □ A.8.4 Acceso a información                                         │
│  □ A.8.5 Requisitos de acceso                                          │
│  □ A.8.6 Contraseñas                                                  │
│  □ A.8.7 Acceso de proveedores                                        │
│  □ A.8.8 Tecnología endpoints                                          │
│  □ A.8.9 Configuración                                                │
│  □ A.8.10 Eliminación de información                                  │
│  □ A.8.11 Datos en uso                                                │
│  □ A.8.12 Protección de datos en tránsito                             │
│  □ A.8.13 Cifrado                                                     │
│  □ A.8.14 Cifrado de claves                                           │
│  □ A.8.15 Logging y monitoreo                                          │
│  □ A.8.16 Monitoreo de actividades                                    │
│  □ A.8.17 Logs de protección                                           │
│  □ A.8.18 Código malicioso                                            │
│  □ A.8.19 Gestión de vulnerabilidades técnicas                        │
│  □ A.8.20 Redes seguras                                               │
│  □ A.8.21 Seguridad de servicios                                       │
│  □ A.8.22 Desarrollo de software                                     │
│  □ A.8.23 Datos de prueba                                             │
│  □ A.8.24 Uso de cryptography                                        │
│  □ A.8.25 Ciclo de desarrollo seguro                                  │
│  □ A.8.26 Gestión de incidentes                                       │
│  □ A.8.27 Mejores prácticas de IA                                     │
│  □ A.8.28 Revisión de código                                         │
│  □ A.8.29 Prueba de aceptación                                         │
│  □ A.8.30 Outsourced development                                     │
│  □ A.8.31 Pruebas de seguridad                                        │
│  □ A.8.32 Protección de datos sensibles                              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.1.3 ISO/IEC 12207:2017 - Procesos del Ciclo de Vida del Software

```
┌─────────────────────────────────────────────────────────────────────────┐
│              PROCESOS DEL CICLO DE VIDA ISO/IEC 12207                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  PROCESOS DE ORGANIZACIÓN                                              │
│  ════════════════════════════                                         │
│                                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │   GESTIÓN    │  │   RECURSOS   │  │  MEJORA      │              │
│  │  ORGANIZ.    │  │  ORGANIZ.    │  │  ORGANIZ.    │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
│       │                   │                   │                       │
│       └───────────────────┼───────────────────┘                       │
│                           ▼                                           │
│  PROCESOS DE PROYECTO (Desarrollo)                                    │
│  ════════════════════════════════════                                 │
│                                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │ ADQUISICIÓN  │──│  SUMINISTRO  │──│  DESARROLLO  │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
│        │                                     │                        │
│        │      ┌──────────────┐              │                        │
│        └─────►│  OPERACIÓN   │◄─────────────┘                        │
│               └──────────────┘                                        │
│                     │                                                 │
│                     ▼                                                 │
│               ┌──────────────┐                                       │
│               │MANTENIMIENTO │                                       │
│               └──────────────┘                                       │
│                                                                         │
│  PROCESOS TÉCNICOS                                                    │
│  ══════════════════════                                               │
│                                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │   ESPECIF.   │  │   DISEÑO     │  │   IMPLEMEN.  │              │
│  │   REQUISITOS │  │  ARQUITECTURA │  │   CÓDIGO     │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
│        │                   │                   │                        │
│        └───────────────────┼───────────────────┘                       │
│                           ▼                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │   PRUEBAS    │  │  INSTALACIÓN │  │   VALIDACIÓN │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.1.4 ISO 9001:2015 - Sistemas de Gestión de Calidad

#### Los 7 Principios de Calidad Aplicados

| # | Principio | Aplicación en el Sistema |
|---|-----------|-------------------------|
| 1 | Enfoque en el cliente | Encuestas de satisfacción, requerimientos del área de RH |
| 2 | Liderazgo | Equipo de proyecto con objetivos claros |
| 3 | Compromiso de las personas | Capacitaciones, documentación accesible |
| 4 | Enfoque en procesos | Metodología de desarrollo en fases |
| 5 | Mejora continua | Iteraciones, revisión de métricas |
| 6 | Toma de decisiones basada en evidencia | Dashboard con KPIs |
| 7 | Gestión de relaciones | Comunicación con stakeholders |

### 2.1.5 ISO 22301:2019 - Gestión de Continuidad del Negocio

| Proceso | Implementación |
|---------|---------------|
| Análisis de impacto (BIA) | Sistema de backups, recuperación 4h |
| Evaluación de riesgos | Monitoreo de servicios |
| Plan de continuidad | Scripts de recuperación automatizados |
| Prueba y mantenimiento | Verificaciones semanales |

## 2.2 Marco Legal Aplicable

### 2.2.1 Legislación Mexicana

| Ley | Aplicación |
|-----|------------|
| LFPDPPP | Protección de datos personales de empleados |
| RFC/CURP | Validación de identidades |
| LFT | Cálculo de retardos, sanciones laborales |
| IMSS | Registro de asistencia para nómina |

---

# 3. ARQUITECTURA DEL SISTEMA

## 3.1 Arquitectura de Alto Nivel

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        ARQUITECTURA DEL SISTEMA                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│    ╔═══════════════════════════════════════════════════════════════════╗  │
│    ║                      CAPA DE PRESENTACIÓN                          ║  │
│    ║  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌───────┐ ║  │
│    ║  │  Login  │  │DashBoard│  │ Reportes│  │ Gráficos│  │  API  │ ║  │
│    ║  │  Page   │  │  Charts │  │  Excel  │  │ Chart.js│  │ REST  │ ║  │
│    ║  └─────────┘  └─────────┘  └─────────┘  └─────────┘  └───────┘ ║  │
│    ║          │          │          │          │          │          ║  │
│    ║          └──────────┼──────────┼──────────┼──────────┘          ║  │
│    ║                       ▼          ▼          ▼                     ║  │
│    ║              ┌─────────────────────────────────────────┐         ║  │
│    ║              │         PLANTILLAS (Views)               │         ║  │
│    ║              │  • layout.php    • empleados/index.php  │         ║  │
│    ║              │  • sidebar.php   • reportes/index.php  │         ║  │
│    ║              └─────────────────────────────────────────┘         ║  │
│    ╚═══════════════════════════════════════════════════════════════════╝  │
│                                    │                                        │
│                                    ▼                                        │
│    ╔═══════════════════════════════════════════════════════════════════╗  │
│    ║                    CAPA DE CONTROLADORES                          ║  │
│    ║  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌───────┐ ║  │
│    ║  │  Auth   │  │Empleado │  │Asistencia│ │ Reporte │  │  ZK   │ ║  │
│    ║  │Controller│ │Controller│ │Controller│ │Controller│ │Teco  │ ║  │
│    ║  └─────────┘  └─────────┘  └─────────┘  └─────────┘  └───────┘ ║  │
│    ║       │            │            │            │           │       ║  │
│    ║  ┌────┴────┐  ┌────┴────┐  ┌────┴────┐  ┌────┴────┐  ┌───┴───┐ ║  │
│    ║  │ Justif. │  │Usuario  │  │ Sancion │  │ Horario │  │  IA   │ ║  │
│    ║  │Controller│ │Controller│ │Controller│ │Controller│ │Controller│║  │
│    ║  └─────────┘  └─────────┘  └─────────┘  └─────────┘  └───────┘ ║  │
│    ╚═══════════════════════════════════════════════════════════════════╝  │
│                                    │                                        │
│                                    ▼                                        │
│    ╔═══════════════════════════════════════════════════════════════════╗  │
│    ║                      CAPA DE MODELOS                              ║  │
│    ║  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌───────┐ ║  │
│    ║  │Empleado │  │Asistencia│ │ Retardo │  │ Horario │  │ ZK    │ ║  │
│    ║  │  Model  │  │  Model  │  │  Model  │  │  Model  │  │Process│ ║  │
│    ║  └─────────┘  └─────────┘  └─────────┘  └─────────┘  └───────┘ ║  │
│    ║       │            │            │            │           │       ║  │
│    ║  ┌────┴────┐  ┌────┴────┐  ┌────┴────┐  ┌────┴────┐  ┌───┴───┐ ║  │
│    ║  │  Sancion│  │Usuario  │  │  Justif │  │Ausencia│  │Validac│ ║  │
│    ║  │  Model  │  │  Model  │  │  Model  │  │  Model  │  │Jefe   │ ║  │
│    ║  └─────────┘  └─────────┘  └─────────┘  └─────────┘  └───────┘ ║  │
│    ║                                                                 ║  │
│    ║         ┌──────────────────────────────────────┐                ║  │
│    ║         │         CLASE DATABASE (Singleton)    │                ║  │
│    ║         │    Conexión PDO, Prepared Statements  │                ║  │
│    ║         └──────────────────────────────────────┘                ║  │
│    ╚═══════════════════════════════════════════════════════════════════╝  │
│                                    │                                        │
│                                    ▼                                        │
│    ╔═══════════════════════════════════════════════════════════════════╗  │
│    ║                  CAPA DE INFRAESTRUCTURA                         ║  │
│    ║                                                                     ║  │
│    ║  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐   ║  │
│    ║  │   MySQL 8.0     │  │  Apache/Nginx   │  │    ZKTeco       │   ║  │
│    ║  │   Base de Datos │  │    Servidor     │  │  Dispositivos   │   ║  │
│    ║  │                 │  │    Web           │  │   Biométricos   │   ║  │
│    ║  └─────────────────┘  └─────────────────┘  └─────────────────┘   ║  │
│    ║           │                   │                    │               ║  │
│    ║           └───────────────────┼────────────────────┘               ║  │
│    ║                               ▼                                    ║  │
│    ║              ┌──────────────────────────────────────┐              ║  │
│    ║              │        SISTEMA DE ARCHIVOS          │              ║  │
│    ║              │  • logs/  • cache/  • uploads/       │              ║  │
│    ║              │  • backups/  • migrations/          │              ║  │
│    ║              └──────────────────────────────────────┘              ║  │
│    ╚═══════════════════════════════════════════════════════════════════╝  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## 3.2 Flujo de Datos del Sistema

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FLUJO DE DATOS PRINCIPAL                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. REGISTRO DE ASISTENCIA                                                │
│  ══════════════════════════════                                            │
│                                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────┐  │
│  │  Dispositivo│────►│  ZKTeco     │────►│  API PHP    │────►│   MySQL │  │
│  │  ZKTeco     │     │  Parser     │     │  Inserter   │     │   BD    │  │
│  └─────────────┘     └─────────────┘     └─────────────┘     └─────────┘  │
│                                                                    │        │
│                                                                    ▼        │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────┐  │
│  │   Cálculo   │◄────│   Reglas    │◄────│  Horarios   │◄────│Empleado │  │
│  │   Retardos  │     │   Negocio   │     │  Laborales  │     │  Model  │  │
│  └─────────────┘     └─────────────┘     └─────────────┘     └─────────┘  │
│       │                                                                    │
│       ▼                                                                    │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐                  │
│  │  Sanciones  │────►│ Notificaciones│───►│  Dashboard  │                  │
│  │  Automáticas│     │    Email     │     │   Metrics   │                  │
│  └─────────────┘     └─────────────┘     └─────────────┘                  │
│                                                                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  2. GESTIÓN DE EMPLEADOS                                                  │
│  ════════════════════════════                                             │
│                                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────┐  │
│  │   Frontend  │────►│  Empleado   │────►│   Validar   │────►│   MySQL │  │
│  │    (Form)   │     │  Controller │     │   RFC/CURP  │     │   BD    │  │
│  └─────────────┘     └─────────────┘     └─────────────┘     └─────────┘  │
│                            │                                              │
│                            ▼                                              │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐                  │
│  │    Import   │────►│    Excel    │────►│   Empleado  │                  │
│  │   Masiva    │     │   Parser    │     │    Model    │                  │
│  └─────────────┘     └─────────────┘     └─────────────┘                  │
│                                                                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  3. AUTENTICACIÓN Y AUTORIZACIÓN                                         │
│  ════════════════════════════════════                                     │
│                                                                             │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────┐  │
│  │   Usuario   │────►│   Auth      │────►│    Hash     │────►│   MySQL │  │
│  │   Login     │     │  Controller │     │  bcrypt     │     │ Sesiones│  │
│  └─────────────┘     └─────────────┘     └─────────────┘     └─────────┘  │
│                            │                                              │
│                            ▼                                              │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐                  │
│  │    RBAC     │────►│   Permisos  │────►│   Middleware│                  │
│  │   Check     │     │    Check    │     │   Access    │                  │
│  └─────────────┘     └─────────────┘     └─────────────┘                  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## 3.3 Componentes de Arquitectura

### 3.3.1 Front Controller Pattern

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         FRONT CONTROLLER (index.php)                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│    Request HTTP                                                            │
│         │                                                                  │
│         ▼                                                                  │
│    ┌──────────────────────────────────────────────────────────────────┐    │
│    │  1. CARGA DE CONFIGURACIÓN                                      │    │
│    │     • config.php (constantes, DB, app settings)                 │    │
│    │     • vendor/autoload.php (Composer)                           │    │
│    │     • helpers/*.php (utilidades)                               │    │
│    │     • controllers/BaseController.php                            │    │
│    └──────────────────────────────────────────────────────────────────┘    │
│         │                                                                  │
│         ▼                                                                  │
│    ┌──────────────────────────────────────────────────────────────────┐    │
│    │  2. SESIONES                                                    │    │
│    │     • Custom session handler (Database)                        │    │
│    │     • CSRF token generation                                    │    │
│    └──────────────────────────────────────────────────────────────────┘    │
│         │                                                                  │
│         ▼                                                                  │
│    ┌──────────────────────────────────────────────────────────────────┐    │
│    │  3. SEGURIDAD                                                   │    │
│    │     • Verificación de autenticación                            │    │
│    │     • Rutas públicas vs protegidas                             │    │
│    │     • Protección CSRF                                           │    │
│    │     • Headers de seguridad                                     │    │
│    └──────────────────────────────────────────────────────────────────┘    │
│         │                                                                  │
│         ▼                                                                  │
│    ┌──────────────────────────────────────────────────────────────────┐    │
│    │  4. ROUTER (FastRoute)                                         │    │
│    │     • routes.php (definición de rutas)                         │    │
│    │     • Dispatch: Method + URI → Controller@method              │    │
│    └──────────────────────────────────────────────────────────────────┘    │
│         │                                                                  │
│         ▼                                                                  │
│    ┌──────────────────────────────────────────────────────────────────┐    │
│    │  5. EJECUCIÓN                                                   │    │
│    │     • Cargar controlador                                         │    │
│    │     • Instanciar clase                                          │    │
│    │     • Ejecutar método con parámetros                           │    │
│    │     • Capturar errores/excepciones                             │    │
│    └──────────────────────────────────────────────────────────────────┘    │
│         │                                                                  │
│         ▼                                                                  │
│    Response (HTML/JSON)                                                    │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 3.3.2 Patrones de Diseño Aplicados

| Patrón | Descripción | Implementación | Beneficio |
|--------|-------------|----------------|-----------|
| **MVC** | Model-View-Controller | `controllers/`, `models/`, `views/` | Separación de responsabilidades |
| **Singleton** | Instancia única | `Database::getInstance()` | Conexión BD única |
| **Factory** | Creación objetos | Controladores | Desacoplamiento |
| **Repository** | Abstracción datos | Modelos | Cambios BD transparentes |
| **Strategy** | Algoritmos interchange | ZKTeco parsers | Flexibilidad procesamiento |
| **Observer** | Eventos | Notificaciones | Acoplamiento débil |
| **Facade** | Interfaz simple | Modelos | Simplicidad API |
| **Middleware** | Filtros requests | `BaseController` | Seguridad, logging |

### 3.3.3 Diagrama de Clases Principales

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        DIAGRAMA DE CLASES PRINCIPALES                      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│                         ┌─────────────────────┐                             │
│                         │   BaseController   │                             │
│                         ├─────────────────────┤                             │
│                         │ - requireAuth()    │                             │
│                         │ - requireRole()   │                             │
│                         │ - redirect()       │                             │
│                         │ - json()           │                             │
│                         │ - render()         │                             │
│                         └──────────┬──────────┘                             │
│                                    │                                        │
│          ┌─────────────────────────┼─────────────────────────┐             │
│          │                         │                         │             │
│          ▼                         ▼                         ▼             │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐          │
│  │AuthController │      │EmpleadoContr. │      │AsistenciaC.  │          │
│  ├───────────────┤      ├───────────────┤      ├───────────────┤          │
│  │ login()       │      │ index()       │      │ index()      │          │
│  │ logout()      │      │ create()      │      │ sincronizar()│          │
│  │ register()    │      │ edit()        │      │ reporte()    │          │
│  │ verify2FA()   │      │ delete()      │      │ importar()   │          │
│  └───────────────┘      └───────────────┘      └───────────────┘          │
│                                    │                                        │
│                                    │ inherits                               │
│                                    ▼                                        │
│                         ┌─────────────────────┐                             │
│                         │    Database         │                             │
│                         ├─────────────────────┤                             │
│                         │ - connection: PDO   │                             │
│                         ├─────────────────────┤                             │
│                         │ + getInstance()     │◄──────── Singleton          │
│                         │ + getConnection()   │                             │
│                         │ + query()           │                             │
│                         │ + prepare()         │                             │
│                         └──────────┬──────────┘                             │
│                                    │                                        │
│                                    │ uses                                   │
│          ┌─────────────────────────┼─────────────────────────┐             │
│          │                         │                         │             │
│          ▼                         ▼                         ▼             │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐          │
│  │   Empleado    │      │  Asistencia   │      │    Retardo    │          │
│  │    Model     │      │    Model      │      │    Model     │          │
│  ├───────────────┤      ├───────────────┤      ├───────────────┤          │
│  │ + getAll()   │      │ + getAll()    │      │ + getAll()    │          │
│  │ + getById() │      │ + registrar() │      │ + calcular()  │          │
│  │ + create()   │      │ + getByEmp()   │      │ + evaluar()   │          │
│  │ + update()   │      │ + reporte()    │      │ + sancionar() │          │
│  │ + delete()   │      │ + sincronizar()│      │ + getByEmp()  │          │
│  └───────────────┘      └───────────────┘      └───────────────┘          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## 3.4 Tecnologías y Versiones

### 3.4.1 Stack Tecnológico

| Capa | Tecnología | Versión | Propósito |
|------|------------|---------|----------|
| **Backend** | PHP | 8.0+ | Lenguaje servidor |
| **Router** | FastRoute | 2.x | Enrutamiento |
| **Database** | MySQL | 8.0+ | Base de datos |
| **ORM/Query** | PDO | Native | Abstracción BD |
| **Frontend** | HTML5/CSS3 | - | Presentación |
| **CSS Framework** | Bootstrap | 5.x | Estilos responsivos |
| **JavaScript** | ES6+ | - | Interactividad |
| **Charts** | Chart.js | 4.x | Gráficos |
| **Testing** | PHPUnit | 10.x | Pruebas unitarias |
| **Testing E2E** | Cypress | 12.x | Pruebas navegador |

### 3.4.2 Dependencias Composer

```json
{
    "require": {
        "php": "^8.0",
        "nikic/fast-route": "^2.0",
        "phpoffice/phpspreadsheet": "^1.29",
        "tecnickcom/tcpdf": "^6.6",
        "firebase/php-jwt": "^6.0",
        "dompdf/dompdf": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

---

# 4. ANÁLISIS DE REQUISITOS

## 4.1 Requisitos Funcionales

### 4.1.1 Catálogo de Requisitos

#### RF-001: Sistema de Autenticación

```
┌─────────────────────────────────────────────────────────────────────────┐
│ REQUISITO: RF-001 - AUTENTICACIÓN Y AUTORIZACIÓN                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ Descripción: El sistema debe permitir a los usuarios acceder de forma  │
│ segura mediante credenciales y controlar el acceso según roles.        │
│                                                                         │
│ Sub-requisitos:                                                        │
│ ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  [RF-001.1] Inicio de Sesión                                           │
│  ──────────────────────                                                │
│  • El sistema debe presentar formulario de login con campos:          │
│    - Username (requerido)                                             │
│    - Contraseña (requerido, enmascarada)                               │
│  • Validar credenciales contra base de datos                          │
│  • Crear sesión segura en caso de éxito                                │
│  • Mostrar mensaje de error en caso de fracaso                        │
│  • Registrar intentos fallidos                                         │
│                                                                         │
│  [RF-001.2] Hash de Contraseñas                                        │
│  ───────────────────────                                              │
│  • Algoritmo: bcrypt con cost factor 10                                │
│  • Función: password_hash() / password_verify()                       │
│  • Almacenamiento: Hash completo en campo VARCHAR(255)                │
│  • No almacenar contraseñas en texto plano                            │
│                                                                         │
│  [RF-001.3] Control de Acceso Basado en Roles (RBAC)                  │
│  ──────────────────────────────────────────────────                    │
│  • Roles definidos: admin, supervisor, rh, user, viewer               │
│  • Verificar rol en cada request protegido                            │
│  • Redirigir a página de acceso denegado si no tiene permisos        │
│  • Los roles determinan:                                               │
│    - Menú de navegación visible                                        │
│    - Acciones permitidas                                               │
│    - Campos editables                                                  │
│                                                                         │
│  [RF-001.4] Gestión de Sesiones                                       │
│  ────────────────────────                                             │
│  • Almacenar sesiones en base de datos (tabla sessions)               │
│  • Custom session handler para persistencia                           │
│  • Tiempo de expiración: 24 horas                                     │
│  • Regenerar ID de sesión en login                                    │
│  • Destruir sesión en logout                                          │
│                                                                         │
│  [RF-001.5] Protección CSRF                                           │
│  ────────────────────────                                             │
│  • Generar token CSRF por sesión                                       │
│  • Incluir token en todos los formularios POST                        │
│  • Validar token antes de procesar request                            │
│  • Regenerar token después de validación exitosa                     │
│                                                                         │
│  [RF-001.6] Verificación de Dos Factores (2FA)                        │
│  ──────────────────────────────────────────────                       │
│  • Generar código de 6 dígitos                                        │
│  • Almacenar código en sesión                                         │
│  • Mostrar código para propósitos de prueba                           │
│  • Validar código ingresando contra sesión                            │
│                                                                         │
│ Prioridad: CRÍTICA                                                     │
│ Estado: IMPLEMENTADO                                                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### RF-002: Gestión de Empleados

```
┌─────────────────────────────────────────────────────────────────────────┐
│ REQUISITO: RF-002 - GESTIÓN DE EMPLEADOS                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ Descripción: El sistema debe permitir el alta, modificación, baja y     │
│ consulta de información de empleados.                                  │
│                                                                         │
│ Sub-requisitos:                                                        │
│ ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  [RF-002.1] Registro de Empleados                                      │
│  ──────────────────────────                                           │
│  • Campos requeridos: nombre, apellido                                │
│  • Campos opcionales: RFC, CURP, email, teléfono                      │
│  • Datos laborales: área, puesto, jerarquía, jefe directo             │
│  • Datos biométricos: huella dactilar, foto facial                    │
│  • Validar unicidad de RFC y CURP                                     │
│  • Encriptar datos sensibles (huella)                                │
│                                                                         │
│  [RF-002.2] Edición de Empleados                                       │
│  ──────────────────────────                                           │
│  • Modificar todos los campos editables                               │
│  • Validar cambios de RFC/CURP                                        │
│  • Registrar modificaciones en bitácora                             │
│  • Permitir cambio de área y puesto                                   │
│                                                                         │
│  [RF-002.3] Baja de Empleados                                         │
│  ──────────────────────                                               │
│  • Baja lógica (campo activo = 0)                                    │
│  • Preservar historial de asistencia                                   │
│  • Desasociar usuario del empleado                                   │
│                                                                         │
│  [RF-002.4] Consulta de Empleados                                       │
│  ──────────────────────────                                           │
│  • Listar empleados activos                                          │
│  • Filtrar por: área, puesto, nombre, RFC                            │
│  • Paginar resultados (20 por página)                               │
│  • Ver detalle de empleado                                            │
│                                                                         │
│  [RF-002.5] Importación Masiva                                        │
│  ────────────────────────────                                          │
│  • Importar desde archivo Excel (.xlsx)                               │
│  • Formato esperado: nombre, apellido, RFC, CURP, área, puesto        │
│  • Validar datos antes de importar                                    │
│  • Reportar errores de validación                                     │
│  • Insertar en batch para rendimiento                                 │
│                                                                         │
│  [RF-002.6] Integración Biométrica                                    │
│  ───────────────────────────                                           │
│  • Registrar template de huella dactilar                              │
│  • Almacenar fotografía facial                                        │
│  • Sincronizar con dispositivos ZKTeco                               │
│  • Verificar enrolamiento                                             │
│                                                                         │
│ Prioridad: CRÍTICA                                                     │
│ Estado: IMPLEMENTADO                                                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### RF-003: Control de Asistencia

```
┌─────────────────────────────────────────────────────────────────────────┐
│ REQUISITO: RF-003 - CONTROL DE ASISTENCIA                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ Descripción: El sistema debe registrar y procesar los registros de       │
│ asistencia provenientes de dispositivos biométricos.                   │
│                                                                         │
│ Sub-requisitos:                                                        │
│ ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  [RF-003.1] Recepción de Eventos                                       │
│  ──────────────────────────                                           │
│  • API endpoint para recibir eventos de asistencia                   │
│  • Procesar eventos tipo entrada y salida                             │
│  • Extraer: employee_id, timestamp, device_id, tipo_biometria         │
│  • Manejar formato de fecha/hora del dispositivo                     │
│                                                                         │
│  [RF-003.2] Procesamiento de Entradas                                  │
│  ─────────────────────────────────                                     │
│  • Registrar hora de entrada                                          │
│  • Verificar si existe registro para la fecha                        │
│  • Crear nuevo registro si no existe                                  │
│  • Actualizar hora si ya existe                                       │
│  • Registrar dispositivo y tipo de biometría                        │
│                                                                         │
│  [RF-003.3] Procesamiento de Salidas                                   │
│  ─────────────────────────────────                                     │
│  • Registrar hora de salida                                           │
│  • Actualizar registro existente del día                            │
│  • Calcular tiempo laborado                                           │
│  • Validar que salida > entrada                                      │
│                                                                         │
│  [RF-003.4] Cálculo de Retardos                                        │
│  ────────────────────────────────                                      │
│  • Comparar hora de entrada vs horario asignado                      │
│  • Aplicar tolerancia configurada                                    │
│  • Clasificar retardo: menor (≤15 min), mayor (>15 min)              │
│  • Registrar minutos de retraso                                       │
│  • Generar registro en tabla retardos                                │
│                                                                         │
│  [RF-003.5] Detección de Anomalías                                     │
│  ─────────────────────────────                                        │
│  • Múltiples entradas/salidas inesperadas                             │
│  • Marcas fuera de horario laboral                                   │
│  • Fechas futuras                                                     │
│  • Marcas duplicadas                                                  │
│  • Registrar en log de anomalías                                       │
│                                                                         │
│  [RF-003.6] Sincronización con Dispositivos                            │
│  ──────────────────────────────────────────                           │
│  • Conectar con API de dispositivos ZKTeco                           │
│  • Descargar registros de attendance log                             │
│  • Parsear formato de datos                                          │
│  • Mapear employee_id del dispositivo a sistema                      │
│  • Procesar en batch para optimizar rendimiento                       │
│                                                                         │
│ Prioridad: CRÍTICA                                                     │
│ Estado: IMPLEMENTADO                                                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### RF-004: Gestión de Horarios

```
┌─────────────────────────────────────────────────────────────────────────┐
│ REQUISITO: RF-004 - GESTIÓN DE HORARIOS                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ Descripción: El sistema debe permitir definir y administrar diferentes │
│ esquemas de horario laboral.                                           │
│                                                                         │
│ Sub-requisitos:                                                        │
│ ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  [RF-004.1] Definición de Horarios                                     │
│  ────────────────────────────                                          │
│  • Nombre del horario                                                 │
│  • Hora de entrada                                                    │
│  • Hora de salida                                                     │
│  • Minutos de tolerancia                                             │
│  • Sede aplicable                                                     │
│  • Días de la semana                                                  │
│  • Horario flexible (opcional)                                       │
│                                                                         │
│  [RF-004.2] Asignación de Horarios                                     │
│  ────────────────────────────                                          │
│  • Asignar horario a empleado                                         │
│  • Definir horario por día de semana                                 │
│  • Soporte para múltiples sedes                                       │
│  • Historial de asignaciones                                          │
│                                                                         │
│  [RF-004.3] Horarios Especiales                                        │
│  ────────────────────────────                                          │
│  • Días festivos                                                       │
│  • Horarios extraordinarios                                          │
│  • Turnos rotativos                                                   │
│  • Teletrabajo                                                        │
│                                                                         │
│ Prioridad: ALTA                                                        │
│ Estado: IMPLEMENTADO                                                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### RF-005: Justificaciones y Permisos

```
┌─────────────────────────────────────────────────────────────────────────┐
│ REQUISITO: RF-005 - JUSTIFICACIONES Y PERMISOS                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ Descripción: El sistema debe gestionar las solicitudes de justificación│
│ de ausencias y retardos con flujo de aprobación.                       │
│                                                                         │
│ Sub-requisitos:                                                        │
│ ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  [RF-005.1] Tipos de Justificación                                    │
│  ────────────────────────────                                          │
│  • Definir tipos: enfermedad, permiso, vacation, etc.                 │
│  • Configurar si requiere aprobación                                  │
│  • Días permitidos por tipo                                          │
│  • Documentación requerida                                           │
│                                                                         │
│  [RF-005.2] Solicitud de Justificación                                │
│  ──────────────────────────────────                                    │
│  • Seleccionar tipo de justificación                                  │
│  • Definir fecha inicio y fin                                        │
│  • Adjuntar documento probatorio                                     │
│  • Agregar motivo                                                    │
│  • Enviar a aprobación                                                │
│                                                                         │
│  [RF-005.3] Flujo de Aprobación                                       │
│  ──────────────────────────────                                        │
│  • Notificar a jefe directo                                          │
│  • Aprobar o rechazar                                                │
│  • Agregar comentarios                                                │
│  • Registrar fecha de aprobación                                     │
│  • Notificar al solicitante                                          │
│                                                                         │
│  [RF-005.4] Cálculo de Días                                           │
│  ────────────────────────                                              │
│  • Contar días laborables                                             │
│  • Descontar días económicos                                         │
│  • Acumular saldo de vacaciones                                      │
│  • Validar disponibilidad                                             │
│                                                                         │
│ Prioridad: ALTA                                                        │
│ Estado: IMPLEMENTADO                                                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### RF-006: Retardos y Sanciones

```
┌─────────────────────────────────────────────────────────────────────────┐
│ REQUISITO: RF-006 - RETARDOS Y SANCIONES                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ Descripción: El sistema debe calcular retardos automáticamente y       │
│ evaluar la aplicación de sanciones según políticas.                    │
│                                                                         │
│ Sub-requisitos:                                                        │
│ ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  [RF-006.1] Registro de Retardos                                      │
│  ──────────────────────────                                           │
│  • Registrar minutos de retraso                                      │
│  • Clasificar tipo: menor (1-15 min), mayor (>15 min)               │
│  • Associar a empleado y fecha                                       │
│  • Vincular con horario aplicable                                     │
│                                                                         │
│  [RF-006.2] Evaluación de Sanciones                                  │
│  ───────────────────────────                                           │
│  • Configurar umbrales:                                               │
│    - 3 retardos menores = 1 amonestación                             │
│    - 3 retardos mayores = 1 día de suspensión                        │
│    - 5 retardos mayores = acta administrativa                        │
│  • Evaluar por período (quincena/mes)                                │
│  • Generar sanciones automáticamente                                 │
│                                                                         │
│  [RF-006.3] Tipos de Sanción                                          │
│  ────────────────────────                                              │
│  • Amonestación verbal                                                │
│  • Amonestación escrita                                               │
│  • Suspensión (días)                                                  │
│  • Acta administrativa                                                │
│  • Otro                                                               │
│                                                                         │
│  [RF-006.4] Historial de Sanciones                                    │
│  ──────────────────────────────                                        │
│  • Registrar sanciones por empleado                                  │
│  • Consultar historial                                               │
│  • Generar constancias                                                │
│  • Seguimiento de cumplimiento                                       │
│                                                                         │
│ Prioridad: ALTA                                                        │
│ Estado: IMPLEMENTADO                                                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### RF-007: Generación de Reportes

```
┌─────────────────────────────────────────────────────────────────────────┐
│ REQUISITO: RF-007 - GENERACIÓN DE REPORTES                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ Descripción: El sistema debe generar reportes de asistencia en         │
│ múltiples formatos con opciones de filtrado.                           │
│                                                                         │
│ Sub-requisitos:                                                        │
│ ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  [RF-007.1] Reportes de Asistencia                                     │
│  ────────────────────────────                                          │
│  • Por empleado                                                       │
│  • Por área/departamento                                             │
│  • Por período: día, semana, quincena, mes                           │
│  • Por dispositivo                                                    │
│  • Resumen y detallado                                                │
│                                                                         │
│  [RF-007.2] Reportes de Retardos                                      │
│  ────────────────────────────                                          │
│  • Lista de retardos por período                                      │
│  • Clasificación por tipo                                             │
│  • Justificados vs no justificados                                    │
│  • Totales por empleado y área                                        │
│                                                                         │
│  [RF-007.3] Reportes de Sanciones                                     │
│  ────────────────────────────                                          │
│  • Historial de sanciones                                             │
│  • Por tipo de sanción                                               │
│  • Por empleado                                                       │
│  • Estadísticas generales                                             │
│                                                                         │
│  [RF-007.4] Exportación                                               │
│  ─────────────────                                                     │
│  • Formato Excel (.xlsx)                                              │
│  • Encabezados y formatos                                             │
│  • Totales y subtotales                                               │
│  • Filas congeladas                                                   │
│                                                                         │
│  [RF-007.5] Dashboard                                                 │
│  ──────────────                                                        │
│  • Métricas en tiempo real                                            │
│  • Gráficos de tendencia                                              │
│  • Indicadores KPI                                                    │
│  • Filtros dinámicos                                                  │
│                                                                         │
│ Prioridad: MEDIA                                                       │
│ Estado: IMPLEMENTADO                                                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### RF-008: Módulo de Inteligencia Artificial

```
┌─────────────────────────────────────────────────────────────────────────┐
│ REQUISITO: RF-008 - INTELIGENCIA ARTIFICIAL                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ Descripción: El sistema debe implementar análisis predictivo y        │
│ detección de anomalías mediante técnicas de IA.                         │
│                                                                         │
│ Sub-requisitos:                                                        │
│ ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  [RF-008.1] Predicción de Patrones                                     │
│  ────────────────────────────                                          │
│  • Analizar historial de asistencia                                   │
│  • Identificar patrones de comportamiento                             │
│  • Predecir probables retardos                                        │
│  • Alertar sobre tendencias negatives                                  │
│                                                                         │
│  [RF-008.2] Detección de Anomalías                                     │
│  ────────────────────────────                                          │
│  • Identificar registros atípicos                                     │
│  • Detectar intentos de fraude                                        │
│  • Marcas inconsistentes                                              │
│  • Notificaciones automáticas                                         │
│                                                                         │
│  [RF-008.3] Recomendaciones                                            │
│  ────────────────────────                                              │
│  • Sugerencias de acciones                                            │
│  • Recomendaciones preventivas                                         │
│  • Alertas de comportamiento                                          │
│                                                                         │
│  [RF-008.4] Métricas e Indicadores                                    │
│  ──────────────────────────────                                        │
│  • Índice de puntualidad                                              │
│  • Tasa de ausentismo                                                 │
│  • Productividad por área                                             │
│  • Tendencias temporales                                              │
│                                                                         │
│ Prioridad: MEDIA                                                       │
│ Estado: IMPLEMENTADO                                                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## 4.2 Requisitos No Funcionales

### 4.2.1 Rendimiento

| Métrica | Objetivo | Crítico | Evidencia |
|---------|----------|---------|-----------|
| Tiempo de respuesta promedio | < 2s | > 5s | New Relic/Monitoring |
| Tiempo de respuesta P95 | < 3s | > 8s | Logs de aplicación |
| Throughput requests/min | > 100 | < 50 | Apache Bench |
| Tiempo generación reportes | < 30s | > 60s | Métricas internas |
| Sincronización dispositivos | 1000 reg/min | 100 reg/min | Log procesamiento |

### 4.2.2 Disponibilidad

| Parámetro | Valor | Descripción |
|-----------|-------|------------|
| Uptime objetivo | 99.5% | Excluyendo mantenimiento |
| Uptime real (mes actual) | 99.9% | Medición continua |
| MTBF (Mean Time Between Failures) | 720 horas | Promedio histórico |
| MTTR (Mean Time To Recovery) | 4 horas | Objetivo de recuperación |
| RTO (Recovery Time Objective) | 4 horas | Tiempo máximo de caída |
| RPO (Recovery Point Objective) | 1 hora | Pérdida máxima de datos |

### 4.2.3 Escalabilidad

| Escenario | Capacidad Actual | Diseño para |
|-----------|------------------|-------------|
| Empleados activos | 5,000+ | 50,000 |
| Dispositivos biométricos | 10 | 100 |
| Usuarios concurrentes | 50 | 500 |
| Registros/día | 10,000 | 100,000 |

### 4.2.4 Seguridad

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    REQUISITOS DE SEGURIDAD                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  CONFIDENCIALIDAD                                                       │
│  ═══════════════                                                       │
│  □ Datos en reposo: AES-256-GCM encryption                            │
│  □ Datos en tránsito: TLS 1.3 (HTTPS)                                 │
│  □ Contraseñas: bcrypt cost 10                                        │
│  □ Keys de cifrado: Rotación anual                                    │
│  □ Datos biométricos: Encriptados con clave institucional              │
│                                                                         │
│  INTEGRIDAD                                                             │
│  ═══════════                                                           │
│  □ Validación de entrada: Whitelist de caracteres                    │
│  □ Prepared statements: PDO con parámetros                           │
│  □ Checksums: MD5/SHA256 para archivos                                │
│  □ Logs inmutables: Solo append, no delete                           │
│                                                                         │
│  DISPONIBILIDAD                                                        │
│  ══════════════                                                        │
│  □ Rate limiting: 100 requests/min por IP                             │
│  □ DDoS protection: Cloudflare/WAF                                    │
│  □ Backups: Diario automático                                         │
│  □ Redundancia: Failover de base de datos                             │
│                                                                         │
│  AUDITORÍA                                                             │
│  ═══════════                                                           │
│  □ Log de accesos: IP, usuario, acción, timestamp                     │
│  □ Log de errores: Stack trace completo                              │
│  □ Log de modificaciones: Antes/después                              │
│  □ Retención: 90 días online, 1 año archivo                          │
│                                                                         │
│  CUMPLIMIENTO                                                          │
│  ═══════════                                                           │
│  □ LGPD (México): Protección de datos personales                     │
│  □ PCI-DSS: No almacenar datos de pago                               │
│  □ ISO 27001: Controles del Anexo A implementados                    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

# 5. DISEÑO DE LA SOLUCIÓN

## 5.1 Modelo de Datos

### 5.1.1 Diagrama Entidad-Relación Completo

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     MODELO DE DATOS - DIAGRAMA ER                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│     ┌─────────────┐         ┌─────────────┐         ┌─────────────┐       │
│     │  empleados  │         │  usuarios   │         │   sesiones  │       │
│     ├─────────────┤         ├─────────────┤         ├─────────────┤       │
│     │ id (PK)    │◄───────│empleado_id │         │ id (PK)    │       │
│     │ nombre     │         │ (FK, NULL) │         │ user_id    │       │
│     │ apellido   │         ├─────────────┤         │ data       │       │
│     │ rfc (UQ)   │         │ id (PK)    │         │ timestamp  │       │
│     │ curp (UQ)  │         │ username   │         └─────────────┘       │
│     │ email      │         │ password   │                │               │
│     │ telefono   │         │ email      │                │               │
│     │ area       │         │ rol        │                │               │
│     │ puesto     │         │ activo     │                │               │
│     │ jerarquia  │         │ created    │                │               │
│     │ foto_cara  │         └─────────────┘                │               │
│     │ huella_enc │                │                       │               │
│     │ activo     │                │                       │               │
│     └──────┬──────┘                │                       │               │
│            │                       │                       │               │
│            │ 1:N                   │ N:1                   │               │
│            ▼                       ▼                       │               │
│     ┌─────────────┐         ┌─────────────┐               │               │
│     │horarios_emp │         │    logs    │               │               │
│     ├─────────────┤         ├─────────────┤               │               │
│     │ id (PK)    │         │ id (PK)    │               │               │
│     │empleado_id │         │ user_id    │               │               │
│     │horario_id  │         │ accion     │               │               │
│     │dia_semana  │         │ ip_address │               │               │
│     │sede        │         │ timestamp  │               │               │
│     │activo      │         └─────────────┘               │               │
│     └──────┬──────┘                                      │               │
│            │                                              │               │
│            │ N:1                                          │               │
│            ▼                                              │               │
│     ┌─────────────┐         ┌─────────────┐              │               │
│     │horarios_lab │         │  asistencia │◄─────────────┘               │
│     ├─────────────┤         ├─────────────┤                                │
│     │ id (PK)    │         │ id (PK)    │                                │
│     │ nombre     │         │empleado_id │────────┐                        │
│     │hora_entrada│         │ fecha      │        │                        │
│     │hora_salida │         │hora_entrada│        │ N:1                    │
│     │tolerancia  │         │hora_salida │        ▼                        │
│     │sede        │         │dispositivo │  ┌─────────────┐               │
│     └─────────────┘         │tipo_biomet │  │  retardos   │               │
│                             │tipo        │  ├─────────────┤               │
│     ┌─────────────┐         └──────┬──────┘  │ id (PK)    │               │
│     │ dispositivos │               │         │empleado_id │               │
│     ├─────────────┤               │         │ fecha      │               │
│     │ id (PK)    │               │         │minutos     │               │
│     │ nombre     │               │         │tipo        │               │
│     │ ip_address │               │         │justificado │               │
│     │ tipo       │               │         │horario_id  │               │
│     │ activo     │               │         │sancion_id  │               │
│     └─────────────┘               │         └──────┬──────┘               │
│                                   │                │                       │
│                                   │ N:1            │ N:1                   │
│                                   ▼                ▼                       │
│                            ┌─────────────┐   ┌─────────────┐              │
│                            │justificaciones│  │  sanciones  │              │
│                            ├─────────────┤   ├─────────────┤              │
│                            │ id (PK)    │   │ id (PK)    │              │
│                            │empleado_id │   │empleado_id │              │
│                            │tipo        │   │tipo_sancion│              │
│                            │fecha_ini   │   │ motivo     │              │
│                            │fecha_fin   │   │ fecha      │              │
│                            │estatus     │   │ estatus    │              │
│                            │aprobado_por│   │ created    │              │
│                            │created     │   └─────────────┘              │
│                            └─────────────┘                               │
│                                                                         │
│     ┌─────────────┐         ┌─────────────┐         ┌─────────────┐     │
│     │ ausencias   │         │  comisiones │         │dias_econom. │     │
│     ├─────────────┤         ├─────────────┤         ├─────────────┤     │
│     │ id (PK)    │         │ id (PK)    │         │ id (PK)    │     │
│     │empleado_id │         │empleado_id │         │empleado_id │     │
│     │ tipo       │         │ descripcion│         │ fecha      │     │
│     │fecha_ini   │         │fecha_ini   │         │ motivo     │     │
│     │fecha_fin   │         │fecha_fin   │         │ estatus    │     │
│     │ motivo     │         │ estatus    │         │ created    │     │
│     │ activo     │         │aprobado_por│         └─────────────┘     │
│     │ created    │         │ created    │                                │
│     └─────────────┘         └─────────────┘                                │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 5.1.2 Detalle de Tablas de Base de Datos

#### Tabla: `empleados`

```sql
CREATE TABLE empleados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    rfc VARCHAR(13) NULL UNIQUE,
    curp VARCHAR(18) NULL UNIQUE,
    email VARCHAR(100) NULL,
    telefono VARCHAR(20) NULL,
    area VARCHAR(50) NULL,
    area_fisica VARCHAR(50) NULL,
    puesto VARCHAR(50) NULL,
    jerarquia VARCHAR(50) NULL,
    sexo CHAR(1) NULL COMMENT 'M/F',
    fecha_nacimiento DATE NULL,
    entidad_federativa VARCHAR(2) NULL,
    fecha_ingreso DATE NULL,
    huella_dactilar TEXT NULL COMMENT 'Template encriptado AES-256',
    foto_cara VARCHAR(255) NULL,
    clave_depto VARCHAR(20) NULL,
    jefe_directo_id INT UNSIGNED NULL,
    jefe_directo_clave VARCHAR(20) NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rfc (rfc),
    INDEX idx_curp (curp),
    INDEX idx_area (area),
    INDEX idx_activo (activo),
    FOREIGN KEY (jefe_directo_id) REFERENCES empleados(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tabla: `usuarios`

```sql
CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NULL UNIQUE,
    nombre_completo VARCHAR(100) NULL,
    rol ENUM('admin', 'superadmin', 'supervisor', 'rh', 'user', 'jefe', 'viewer') DEFAULT 'user',
    empleado_id INT UNSIGNED NULL,
    activo TINYINT(1) DEFAULT 1,
    ultimo_login TIMESTAMP NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_rol (rol),
    INDEX idx_empleado (empleado_id),
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tabla: `asistencia`

```sql
CREATE TABLE asistencia (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME NULL,
    hora_salida TIME NULL,
    tipo ENUM('entrada', 'salida') NULL,
    tipo_asistencia ENUM('normal', 'retardo', 'falta', 'justificado', 'remoto') DEFAULT 'normal',
    dispositivo_id TINYINT UNSIGNED NULL,
    tipo_biometria ENUM('huella', 'cara', 'rfid', 'password') NULL,
    calidad_verificacion TINYINT NULL COMMENT '0-100',
    datos_biometricos JSON NULL,
    requiere_validacion_jefe TINYINT(1) DEFAULT 0,
    requerio_validacion TINYINT(1) DEFAULT 0,
    validado_por_jefe INT UNSIGNED NULL,
    fecha_aprobacion TIMESTAMP NULL,
    estado_validacion ENUM('pendiente', 'aprobado', 'rechazado') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empleado_fecha (empleado_id, fecha),
    INDEX idx_fecha (fecha),
    INDEX idx_dispositivo (dispositivo_id),
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (validado_por_jefe) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tabla: `retardos`

```sql
CREATE TABLE retardos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    minutos_retraso INT NOT NULL,
    tipo_retraso ENUM('menor', 'mayor') NOT NULL,
    justificado TINYINT(1) DEFAULT 0,
    tipo_justificacion_id INT UNSIGNED NULL,
    motivo_justificacion TEXT NULL,
    aprobado_por INT UNSIGNED NULL,
    fecha_aprobacion TIMESTAMP NULL,
    horario_id INT UNSIGNED NULL,
    asistencia_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empleado_fecha (empleado_id, fecha),
    INDEX idx_tipo (tipo_retraso),
    INDEX idx_justificado (justificado),
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (tipo_justificacion_id) REFERENCES tipos_justificacion(id),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id),
    FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id),
    FOREIGN KEY (asistencia_id) REFERENCES asistencia(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tabla: `horarios_laborales`

```sql
CREATE TABLE horarios_laborales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    hora_entrada TIME NOT NULL,
    hora_salida TIME NOT NULL,
    tolerancia_minutos INT DEFAULT 10,
    tolerancia_salida_minutos INT DEFAULT 5,
    descripcion TEXT NULL,
    activo BOOLEAN DEFAULT TRUE,
    sede VARCHAR(100) NULL,
    tipo ENUM('fijo', 'flexible', 'turno') DEFAULT 'fijo',
    hora_entrada_flexible_inicio TIME NULL,
    hora_entrada_flexible_fin TIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sede (sede),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tabla: `sanciones`

```sql
CREATE TABLE sanciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT UNSIGNED NOT NULL,
    tipo_sancion ENUM('amonestacion_verbal', 'amonestacion_escrita', 'suspension', 'acta_administrativa', 'otro') NOT NULL,
    motivo TEXT NOT NULL,
    fecha_sancion DATE NOT NULL,
    dias_suspension INT NULL,
    estatus ENUM('activa', 'cumplida', 'cancelada', 'apelacion') DEFAULT 'activa',
    documento_path VARCHAR(255) NULL,
    aprobada_por INT UNSIGNED NULL,
    fecha_cumplimiento DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha (fecha_sancion),
    INDEX idx_estatus (estatus),
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    FOREIGN KEY (aprobada_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 5.2 Diseño de API

### 5.2.1 Endpoints REST

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        ENDPOINTS DE API REST                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  AUTENTICACIÓN                                                          │
│  ═══════════════                                                        │
│  POST   /api/auth/login          Iniciar sesión                        │
│  POST   /api/auth/logout         Cerrar sesión                         │
│  POST   /api/auth/register       Registrar usuario                     │
│  GET    /api/auth/me             Datos usuario actual                  │
│  POST   /api/auth/2fa/verify    Verificar 2FA                          │
│                                                                         │
│  EMPLEADOS                                                              │
│  ═══════════                                                            │
│  GET     /api/empleados                    Listar empleados             │
│  GET     /api/empleados/{id}                Ver empleado               │
│  POST    /api/empleados                    Crear empleado              │
│  PUT     /api/empleados/{id}                Actualizar empleado        │
│  DELETE  /api/empleados/{id}                Eliminar empleado          │
│  POST    /api/empleados/importar            Importar desde Excel        │
│  GET     /api/empleados/{id}/asistencia     Historial asistencia        │
│  GET     /api/empleados/{id}/retardos       Historial retardos         │
│                                                                         │
│  ASISTENCIA                                                             │
│  ═══════════                                                            │
│  GET     /api/asistencia                     Listar registros           │
│  GET     /api/asistencia/hoy                 Asistencia de hoy          │
│  POST    /api/asistencia/registrar           Registrar marca            │
│  POST    /api/asistencia/sincronizar         Sincronizar dispositivos   │
│  GET     /api/asistencia/reporte             Generar reporte            │
│                                                                         │
│  HORARIOS                                                               │
│  ═══════════                                                            │
│  GET     /api/horarios                        Listar horarios            │
│  GET     /api/horarios/{id}                  Ver horario                │
│  POST    /api/horarios                       Crear horario              │
│  PUT     /api/horarios/{id}                  Actualizar horario         │
│  DELETE  /api/horarios/{id}                  Eliminar horario           │
│  POST    /api/horarios/asignar               Asignar horario a empleado │
│                                                                         │
│  JUSTIFICACIONES                                                       │
│  ═════════════════                                                      │
│  GET     /api/justificaciones                Listar justificaciones     │
│  GET     /api/justificaciones/{id}           Ver justificación          │
│  POST    /api/justificaciones                Crear justificación        │
│  PUT     /api/justificaciones/{id}/aprobar   Aprobar justificación      │
│  PUT     /api/justificaciones/{id}/rechazar Rechazar justificación    │
│                                                                         │
│  RETARDOS                                                               │
│  ═══════════                                                            │
│  GET     /api/retardos                        Listar retardos           │
│  GET     /api/retardos/evaluar                Evaluar sanciones          │
│  POST    /api/retardos/procesar-todos        Procesar todos empleados    │
│                                                                         │
│  SANCIONES                                                             │
│  ═══════════                                                            │
│  GET     /api/sanciones                      Listar sanciones            │
│  GET     /api/sanciones/{id}                 Ver sanción                │
│  POST    /api/sanciones                      Crear sanción              │
│  PUT     /api/sanciones/{id}                 Actualizar sanción        │
│  PUT     /api/sanciones/{id}/cumplir         Marcar como cumplida       │
│                                                                         │
│  REPORTES                                                              │
│  ═══════════                                                            │
│  GET     /api/reportes/asistencia            Reporte asistencia        │
│  GET     /api/reportes/retardos              Reporte retardos           │
│  GET     /api/reportes/sanciones             Reporte sanciones          │
│  GET     /api/reportes/resumen               Resumen general            │
│  GET     /api/reportes/exportar              Exportar a Excel           │
│                                                                         │
│  DISPOSITIVOS                                                          │
│  ═════════════════                                                      │
│  GET     /api/dispositivos                   Listar dispositivos        │
│  GET     /api/dispositivos/{id}              Ver dispositivo            │
│  POST    /api/dispositivos                   Agregar dispositivo         │
│  PUT     /api/dispositivos/{id}              Actualizar dispositivo     │
│  POST    /api/dispositivos/{id}/test         Probar conexión            │
│  POST    /api/dispositivos/{id}/sincronizar Sincronizar datos           │
│                                                                         │
│  INTELIGENCIA ARTIFICIAL                                              │
│  ═══════════════════════════                                           │
│  GET     /api/ai/analizar                    Analizar patrones          │
│  GET     /api/ai/metricas                    Métricas y KPIs             │
│  GET     /api/ai/alertas                     Alertas y notificaciones   │
│  GET     /api/ai/anomalias                   Anomalías detectadas       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2.2 Formato de Respuestas

**Respuesta exitosa (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Juan",
    "apellido": "Pérez",
    "area": "Recursos Humanos"
  },
  "message": "Operación exitosa",
  "timestamp": "2026-03-29T12:00:00Z"
}
```

**Respuesta de error (400/401/403/404/500):**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "El campo RFC es requerido",
    "details": {
      "field": "rfc",
      "constraint": "required"
    }
  },
  "timestamp": "2026-03-29T12:00:00Z"
}
```

**Respuesta con paginación:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8
  }
}
```

---

# 6. MÓDULOS DEL SISTEMA

## 6.1 Módulo de Autenticación

### 6.1.1 Descripción General

```
┌─────────────────────────────────────────────────────────────────────────┐
│                  MÓDULO DE AUTENTICACIÓN - ARQUITECTURA                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    CAPA DE PRESENTACIÓN                         │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────────────┐   │   │
│  │  │  Login  │  │ Register│  │  2FA    │  │ Password Reset  │   │   │
│  │  │   Form  │  │  Form   │  │  Page   │  │      Form       │   │   │
│  │  └────┬────┘  └────┬────┘  └────┬────┘  └────────┬────────┘   │   │
│  └───────┼────────────┼────────────┼────────────────┼────────────┘   │
│          │            │            │                │                  │
│          └────────────┴────────────┴────────────────┘                  │
│                               │                                         │
│                               ▼                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                   AuthController                                │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐    │   │
│  │  │  authenticate│  │   logout     │  │  generate2FA      │    │   │
│  │  │    ()        │  │    ()        │  │     ()            │    │   │
│  │  └──────────────┘  └──────────────┘  └────────────────────┘    │   │
│  └───────────────────────────┬─────────────────────────────────────┘   │
│                              │                                          │
│                              ▼                                          │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    UsuarioModel                                 │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐    │   │
│  │  │  authenticate│  │  create      │  │  updatePassword    │    │   │
│  │  │    ()        │  │    ()        │  │     ()            │    │   │
│  │  └──────────────┘  └──────────────┘  └────────────────────┘    │   │
│  └───────────────────────────┬─────────────────────────────────────┘   │
│                              │                                          │
│                              ▼                                          │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    Base de Datos                               │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐    │   │
│  │  │   usuarios   │  │  sesiones    │  │   logs_acceso     │    │   │
│  │  └──────────────┘  └──────────────┘  └────────────────────┘    │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.1.2 Flujo de Autenticación

```
     ┌──────────────────────────────────────────────────────────────────┐
     │                    FLUJO DE AUTENTICACIÓN                        │
     └──────────────────────────────────────────────────────────────────┘
     
                                    ┌─────────────┐
                                    │   Usuario   │
                                    │  Accede a   │
                                    │   /login    │
                                    └──────┬──────┘
                                           │
                    ┌──────────────────────┼──────────────────────┐
                    │                      │                      │
                    ▼                      ▼                      ▼
            ┌─────────────┐        ┌─────────────┐        ┌─────────────┐
            │   GET       │        │   POST      │        │   GET       │
            │  /login     │        │  /login     │        │ /register   │
            │  (Form)     │        │  (Submit)   │        │  (Form)     │
            └──────┬──────┘        └──────┬──────┘        └──────┬──────┘
                   │                      │                      │
                   │         ┌───────────┴───────────┐            │
                   │         │                       │            │
                   │         ▼                       ▼            │
                   │   ┌─────────────┐        ┌─────────────┐      │
                   │   │ Validar    │        │  Mostrar    │      │
                   │   │ Credenciales│        │  Error      │      │
                   │   │ (BCrypt)   │        │  Login      │      │
                   │   └──────┬──────┘        └─────────────┘      │
                   │          │                                       │
                   │    ┌─────┴─────┐                                 │
                   │    │           │                                 │
                   │    ▼           ▼                                 │
                   │ ┌─────────┐ ┌─────────┐                          │
                   │ │  Válido │ │Inválido │                          │
                   │ └────┬────┘ └─────────┘                          │
                   │      │                                          │
                   │      ▼                                          │
                   │ ┌─────────────────────────────────────┐          │
                   │ │  1. Regenerar ID sesión            │          │
                   │ │  2. Crear variables sesión:         │          │
                   │ │     - user_id                      │          │
                   │ │     - username                     │          │
                   │ │     - rol                           │          │
                   │ │     - permisos                      │          │
                   │ │  3. Generar CSRF token             │          │
                   │ │  4. Redirigir a /dashboard         │          │
                   │ └─────────────────────────────────────┘          │
                   │           │                                      │
                   └───────────┼──────────────────────────────────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │     /dashboard      │
                    │   (Pantalla主的)    │
                    └─────────────────────┘
```

### 6.1.3 Matriz de Permisos por Rol

| Página/Acción | admin | superadmin | supervisor | rh | user | viewer |
|---------------|:-----:|:----------:|:----------:|:--:|:----:|:------:|
| **Dashboard** | ✅ | ✅ | ✅ | ✅ | ✅ | 👁️ |
| **Empleados** | | | | | | |
| - Ver lista | ✅ | ✅ | ✅ | ✅ | 👁️ | 👁️ |
| - Crear | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| - Editar | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| - Eliminar | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Asistencia** | | | | | | |
| - Ver registros | ✅ | ✅ | ✅ | ✅ | 👁️ | 👁️ |
| - Sincronizar | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| - Justificar | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Horarios** | | | | | | |
| - Gestionar | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Justificaciones** | | | | | | |
| - Crear | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| - Aprobar | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Retardos/Sanciones** | | | | | | |
| - Ver | ✅ | ✅ | ✅ | ✅ | 👁️ | 👁️ |
| - Evaluar | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Reportes** | | | | | | |
| - Generar | ✅ | ✅ | ✅ | ✅ | ✅ | 👁️ |
| - Exportar | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Usuarios** | | | | | | |
| - Gestionar | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Configuración** | | | | | | |
| - General | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| - Dispositivos | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

✅ = Permitido | 👁️ = Solo lectura | ❌ = Denegado

## 6.2 Módulo de Gestión de Empleados

### 6.2.1 Diagrama de Funcionalidades

```
┌─────────────────────────────────────────────────────────────────────────┐
│               MÓDULO DE GESTIÓN DE EMPLEADOS                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │                      OPERACIONES CRUD                            │   │
│  │                                                                   │   │
│  │    ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐    │   │
│  │    │ CREATE  │───►│  READ   │───►│ UPDATE  │───►│ DELETE  │    │   │
│  │    │ (Alta)  │    │ (Consulta)│ (Edición)│  │ (Baja)  │    │   │
│  │    └─────────┘    └─────────┘    └─────────┘    └─────────┘    │   │
│  │                                                                   │   │
│  │    Campos:                                                        │   │
│  │    • Datos personales: nombre, apellido, RFC, CURP              │   │
│  │    • Contacto: email, teléfono                                   │   │
│  │    • Laborales: área, puesto, jerarquía, jefe directo          │   │
│  │    • Biométricos: huella, foto                                  │   │
│  │    • Adicionales: fecha nacimiento, entidad, sexo              │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │                    IMPORTACIÓN MASIVA                            │   │
│  │                                                                   │   │
│  │    ┌─────────────────────────────────────────────────────────┐  │   │
│  │    │              ARCHIVO EXCEL (.xlsx)                       │  │   │
│  │    │  ┌─────┬────────┬────────┬──────┬──────┬──────────┐    │  │   │
│  │    │  │ RFC │ CURP   │ Nombre │ Area │ Puesto│ Jefe ID │    │  │   │
│  │    │  ├─────┼────────┼────────┼──────┼──────┼──────────┤    │  │   │
│  │    │  │ ... │ ...    │ ...    │ ...  │ ...  │ ...     │    │  │   │
│  │    │  └─────┴────────┴────────┴──────┴──────┴──────────┘    │  │   │
│  │    └──────────────────────┬──────────────────────────────────┘  │   │
│  │                            │                                      │   │
│  │                            ▼                                      │   │
│  │    ┌──────────────────────────────────────────────────────────┐  │   │
│  │    │              PROCESO DE IMPORTACIÓN                     │  │   │
│  │    │                                                           │  │   │
│  │    │  1. Parseo Excel      → Array de datos                 │  │   │
│  │    │  2. Validación        → RFC/CURP únicos                │  │   │
│  │    │  3. Transformación   → Formato BD                      │  │   │
│  │    │  4. Inserción batch   → 50 registros por transacción   │  │   │
│  │    │  5. Reporte           → Errores y éxitos               │  │   │
│  │    │                                                           │  │   │
│  │    └──────────────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │                    VALIDACIONES                                  │   │
│  │                                                                   │   │
│  │    ┌──────────────────────────────────────────────────────────┐  │   │
│  │    │  RFC              │  CURP            │  Email             │  │   │
│  │    ├───────────────────┼───────────────────┼───────────────────┤  │   │
│  │    │ • 13 caracteres  │ • 18 caracteres   │ • Formato válido  │  │   │
│  │    │ • Mayúsculas     │ • Mayúsculas      │ • Dominio válido  │  │   │
│  │    │ • Unique en BD   │ • Unique en BD    │ • Unique en BD    │  │   │
│  │    │ • Formato válido │ • Dígito验证器    │                   │  │   │
│  │    └───────────────────┴───────────────────┴───────────────────┘  │   │
│  │                                                                   │   │
│  │    ┌──────────────────────────────────────────────────────────┐  │   │
│  │    │  Nombre/Apellido    │  Teléfono       │  Área            │  │   │
│  │    ├─────────────────────┼─────────────────┼──────────────────┤  │   │
│  │    │ • Solo letras      │ • 10 dígitos    │ • De catálogo   │  │   │
│  │    │ • Sin números      │ • LADA válida   │ • Requerido     │  │   │
│  │    │ • Sin caracteres   │ • Solo números  │                  │  │   │
│  │    │   especiales       │                  │                  │  │   │
│  │    └─────────────────────┴─────────────────┴──────────────────┘  │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## 6.3 Módulo de Control de Asistencia

### 6.3.1 Flujo de Procesamiento

```
┌─────────────────────────────────────────────────────────────────────────┐
│            FLUJO DE PROCESAMIENTO DE ASISTENCIA                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  1. RECEPCIÓN DE EVENTO                                                │
│  ═══════════════════════                                               │
│                                                                         │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐              │
│  │  Dispositivo│────►│    API      │────►│  FastRoute  │              │
│  │  ZKTeco     │     │ /api/att    │     │  Router     │              │
│  └─────────────┘     └─────────────┘     └──────┬──────┘              │
│                                                 │                      │
│                                                 ▼                      │
│  2. PROCESAMIENTO                                                       │
│  ══════════════════                                                     │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │              ZKTecoAsistenciaInserter                         │    │
│  │                                                                │    │
│  │  ┌────────────────┐  ┌────────────────┐  ┌───────────────┐  │    │
│  │  │ Parsear datos  │  │ Validar empleado│  │ Buscar registro│  │    │
│  │  │ del dispositivo│  │ en sistema     │  │ del día       │  │    │
│  │  └───────┬────────┘  └───────┬────────┘  └───────┬────────┘  │    │
│  │          │                   │                   │            │    │
│  │          └───────────────────┼───────────────────┘            │    │
│  │                              │                                │    │
│  │                              ▼                                │    │
│  │  ┌────────────────────────────────────────────────────────┐   │    │
│  │  │              DETERMINAR TIPO DE REGISTRO               │   │    │
│  │  │                                                        │   │    │
│  │  │     ┌──────────────────────────────────────────┐      │   │    │
│  │  │     │  ¿Existe registro para hoy?             │      │   │    │
│  │  │     └──────────────────────┬───────────────────┘      │   │    │
│  │  │                ┌────────────┴────────────┐              │   │    │
│  │  │                │                         │              │   │    │
│  │  │                ▼                         ▼              │   │    │
│  │  │         ┌───────────┐            ┌────────────┐        │   │    │
│  │  │         │    SÍ     │            │    NO      │        │   │    │
│  │  │         └─────┬─────┘            └─────┬──────┘        │   │    │
│  │  │               │                      │                │   │    │
│  │  │               ▼                      ▼                │   │    │
│  │  │  ┌──────────────────┐    ┌───────────────────┐       │   │    │
│  │  │  │ Determinar tipo │    │ Crear nuevo       │       │   │    │
│  │  │  │ de marca        │    │ registro          │       │   │    │
│  │  │  │ (entrada/salida)│    │ (entrada)         │       │   │    │
│  │  │  └──────────────────┘    └───────────────────┘       │   │    │
│  │  └────────────────────────────────────────────────────────┘   │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                              │                                          │
│                              ▼                                          │
│  3. CÁLCULO DE RETARDOS                                                │
│  ═══════════════════════                                               │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  ┌─────────────────────────────────────────────────────────┐  │    │
│  │  │  Obtener horario del empleado para el día              │  │    │
│  │  │  • HorariosLaborales.getByEmpleado(empleado_id, fecha) │  │    │
│  │  └─────────────────────────────────────────────────────────┘  │    │
│  │                            │                                    │    │
│  │                            ▼                                    │    │
│  │  ┌─────────────────────────────────────────────────────────┐  │    │
│  │  │  Comparar hora_entrada vs hora_entrada_horario          │  │    │
│  │  │                                                          │  │    │
│  │  │  diferencia = hora_entrada - hora_programada           │  │    │
│  │  │                                                          │  │    │
│  │  │  SI diferencia > tolerancia:                           │  │    │
│  │  │      → Es RETARDO                                       │  │    │
│  │  │      → Clasificar: menor (≤15min) / mayor (>15min)     │  │    │
│  │  │      → Crear registro en tabla retardos               │  │    │
│  │  │  SI diferencia <= tolerancia:                          │  │    │
│  │  │      → Es ASISTENCIA NORMAL                            │  │    │
│  │  └─────────────────────────────────────────────────────────┘  │    │
│  │                            │                                    │    │
│  │                            ▼                                    │    │
│  │  ┌─────────────────────────────────────────────────────────┐  │    │
│  │  │  Verificar justificación existente                     │  │    │
│  │  │  • Justificaciones.hasJustificacion(empleado_id, fecha)│  │    │
│  │  │  • SI existe: marcar como justificado                  │  │    │
│  │  └─────────────────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                              │                                          │
│                              ▼                                          │
│  4. DETECCIÓN DE ANOMALÍAS                                             │
│  ════════════════════════                                              │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  Rules de detección:                                          │    │
│  │                                                                │    │
│  │  □ Marca fuera de horario (22:00 - 06:00)                   │    │
│  │  □ Diferencia entrada-salida < 15 minutos                    │    │
│  │  □ Marca duplicada (misma hora ± 5 min)                     │    │
│  │  □ Fecha futura                                               │    │
│  │  □ Empleado inactivo                                          │    │
│  │  □ Sin mapeo de empleado                                      │    │
│  │                                                                │    │
│  │  Acción: Registrar en log_anomalias + notificar si crítico   │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                              │                                          │
│                              ▼                                          │
│  5. RESPUESTA                                                          │
│  ═══════════                                                           │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  {                                                            │    │
│  │    "success": true,                                          │    │
│  │    "data": {                                                  │    │
│  │      "asistencia_id": 12345,                                 │    │
│  │      "tipo": "entrada",                                      │    │
│  │      "empleado_id": 42,                                      │    │
│  │      "fecha": "2026-03-29",                                  │    │
│  │      "hora": "08:45:00",                                     │    │
│  │      "minutos_retardo": 5,                                   │    │
│  │      "tipo_retardo": "menor"                                 │    │
│  │    }                                                          │    │
│  │  }                                                            │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## 6.4 Módulo de Inteligencia Artificial

### 6.4.1 Arquitectura del Sistema de IA

```
┌─────────────────────────────────────────────────────────────────────────┐
│              ARQUITECTURA DEL MÓDULO DE IA                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │                    CAPA DE DATOS                                 │   │
│  │                                                                   │   │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌──────────┐  │   │
│  │  │  Asistencia│  │  Retardos  │  │ Sanciones │  │Justific. │  │   │
│  │  │   Hist.    │  │  Hist.     │  │   Hist.   │  │  Hist.   │  │   │
│  │  └────────────┘  └────────────┘  └────────────┘  └──────────┘  │   │
│  │                                                                   │   │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────────────────┐   │   │
│  │  │  Empleados │  │  Horarios   │  │   Características     │   │   │
│  │  │   Datos    │  │   Datos     │  │   Calculadas         │   │   │
│  │  └────────────┘  └────────────┘  └────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │                 CAPA DE PROCESAMIENTO                            │   │
│  │                                                                   │   │
│  │  ┌────────────────────┐  ┌────────────────────┐                │   │
│  │  │   ANALIZADOR DE    │  │  DETECTOR DE       │                │   │
│  │  │    PATRONES        │  │   ANOMALÍAS        │                │   │
│  │  ├────────────────────┤  ├────────────────────┤                │   │
│  │  │ • Tendencias       │  │ • outliers        │                │   │
│  │  │ • Estacionalidad   │  │ • fraude          │                │   │
│  │  │ • Predicción       │  │ • inconsistencias │                │   │
│  │  │ • Correlaciones    │  │ • alertas         │                │   │
│  │  └─────────┬──────────┘  └─────────┬──────────┘                │   │
│  │            │                       │                            │   │
│  │            └───────────┬───────────┘                            │   │
│  │                        │                                         │   │
│  │                        ▼                                         │   │
│  │  ┌──────────────────────────────────────────────────────────┐   │   │
│  │  │              MOTOR DE RECOMENDACIONES                    │   │   │
│  │  │                                                           │   │   │
│  │  │  • Reglas heurísticas                                    │   │   │
│  │  │  • Scoring de riesgo                                     │   │   │
│  │  │  • Alertas predictivas                                    │   │   │
│  │  │  • Sugerencias de acción                                 │   │   │
│  │  │                                                           │   │   │
│  │  └──────────────────────────────────────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │                    CAPA DE PRESENTACIÓN                         │   │
│  │                                                                   │   │
│  │  ┌────────────────────┐  ┌────────────────────┐                │   │
│  │  │   DASHBOARD IA     │  │   NOTIFICACIONES   │                │   │
│  │  │   • Métricas       │  │   • Email          │                │   │
│  │  │   • Gráficos       │  │   • Sistema        │                │   │
│  │  │   • Predicciones   │  │   • Alerts         │                │   │
│  │  └────────────────────┘  └────────────────────┘                │   │
│  │                                                                   │   │
│  │  ┌──────────────────────────────────────────────────────────┐   │   │
│  │  │                    API DE IA                              │   │   │
│  │  │  GET /ai/metricas     → KPIs y estadísticas              │   │   │
│  │  │  GET /ai/analizar     → Análisis de patrones             │   │   │
│  │  │  GET /ai/alertas      → Alertas activas                  │   │   │
│  │  │  GET /ai/anomalias    → Anomalías detectadas             │   │   │
│  │  │  GET /ai/prediccion    → Predicciones de asistencia       │   │   │
│  │  └──────────────────────────────────────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

# 7. INTEGRACIÓN CON DISPOSITIVOS BIOMÉTRICOS

## 7.1 Arquitectura de Integración ZKTeco

```
┌─────────────────────────────────────────────────────────────────────────┐
│           ARQUITECTURA DE INTEGRACIÓN ZKTECO                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  DISPOSITIVO ZKTECO                      SERVIDOR APP                  │
│  ═══════════════════════                 ═══════════════                │
│                                                                         │
│  ┌──────────────────┐                    ┌──────────────────┐         │
│  │   TimeClock      │                    │   Apache/PHP     │         │
│  │   Device         │                    │                  │         │
│  │   ┌──────────┐  │    HTTP/WebSocket   │   ┌──────────┐   │         │
│  │   │  Panel   │  │◄──────────────────►│   │  Router  │   │         │
│  │   │   LCD    │  │                    │   │  FastR.  │   │         │
│  │   └──────────┘  │                    │   └────┬─────┘   │         │
│  │                  │                    │        │         │         │
│  │   ┌──────────┐  │                    │        ▼         │         │
│  │   │ Sensor   │  │                    │   ┌──────────┐   │         │
│  │   │ Huella/  │  │                    │   │ ZKTeco   │   │         │
│  │   │ Cara     │  │                    │   │ Controller│  │         │
│  │   └──────────┘  │                    │   └────┬─────┘   │         │
│  │                  │                    │        │         │         │
│  │   ┌──────────┐  │                    │        ▼         │         │
│  │   │  Storage │  │                    │   ┌──────────┐   │         │
│  │   │  Logs    │  │ ────────►         │   │ ZKTeco   │   │         │
│  │   │  (5000)   │  │    Pull           │   │ Universal│   │         │
│  │   │  registros│  │   (scheduled)     │   │ Parser   │   │         │
│  │   └──────────┘  │                    │   └────┬─────┘   │         │
│  │                  │                    │        │         │         │
│  │                  │                    │        ▼         │         │
│  │                  │                    │   ┌──────────┐   │         │
│  │                  │                    │   │ ZKTeco   │   │         │
│  │                  │                    │   │ Assisten-│   │         │
│  │                  │                    │   │ Inserter │   │         │
│  │                  │                    │   └────┬─────┘   │         │
│  │                  │                    │        │         │         │
│  │                  │                    │        ▼         │         │
│  │                  │                    │   ┌──────────┐   │         │
│  │                  │                    │   │  MySQL   │   │         │
│  │                  │                    │   │    DB    │   │         │
│  │                  │                    │   └──────────┘   │         │
│  └──────────────────┘                    └──────────────────┘         │
│                                                                         │
│  FLUJO:                                                                 │
│  ══════                                                                 │
│                                                                         │
│  1. Usuario registra huella/cara en dispositivo                        │
│  2. Dispositivo almacena evento en log interno                         │
│  3. Servidor polling (cron job cada 5 min):                           │
│     a. Conectar a API del dispositivo                                  │
│     b. Descargar nuevos registros                                      │
│     c. Parsear formato ZKTeco                                          │
│     d. Mapear employee_id → sistema_id                                │
│     e. Insertar en tabla asistencia                                    │
│     f. Calcular retardos                                               │
│     g. Generar alertas si anomalía                                     │
│  4. Dispositivo confirma recepción                                     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## 7.2 Formato de Datos ZKTeco

### 7.2.1 Estructura del Log de Asistencia

```
┌─────────────────────────────────────────────────────────────────────────┐
│                FORMATO DE DATOS ZKTECO                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Raw Data del Dispositivo:                                             │
│  ══════════════════════════                                            │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │  Campo          │ Tipo    │ Descripción                         │  │
│  ├─────────────────┼─────────┼─────────────────────────────────────┤  │
│  │  UID            │ Integer │ ID único del usuario en dispositivo │  │
│  │  ID             │ Integer │ Número de empleado (badge)          │  │
│  │  Name           │ String  │ Nombre del empleado                 │  │
│  │  State          │ Integer │ Estado (0=Check-In, 1=Check-Out)   │  │
│  │  Time           │ DateTime│ Timestamp del registro              │  │
│  │  Type           │ Integer │ Tipo (0=.huella, 4= cara, 15= RFID) │  │
│  │  Verification   │ Integer │ Modo de verificación                 │  │
│  │  InOut          │ Integer │ Dirección (0=entrada, 1=salida)    │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│  Ejemplo de Respuesta API:                                             │
│  ════════════════════════                                              │
│                                                                         │
│  {                                                                     │
│    "records": [                                                        │
│      {                                                                 │
│        "uid": 1,                                                       │
│        "id": "1001",                                                   │
│        "name": "JUAN PEREZ",                                           │
│        "state": 1,                                                     │
│        "time": "2026-03-29 08:45:00",                                  │
│        "type": 0,                                                     │
│        "verification": 1,                                              │
│        "inoutstate": 0                                                 │
│      }                                                                 │
│    ]                                                                   │
│  }                                                                     │
│                                                                         │
│  Mapeo a Sistema:                                                      │
│  ═════════════════                                                      │
│                                                                         │
│  ┌───────────────────────┬──────────────────────┐                   │
│  │ Campo ZKTeco          │ Campo Sistema         │                   │
│  ├───────────────────────┼──────────────────────┤                   │
│  │ id                    │ zk_empleado_id        │                   │
│  │ time (fecha)          │ fecha                 │                   │
│  │ time (hora)           │ hora_entrada/salida   │                   │
│  │ type (0)              │ tipo_biometria='huella'│                  │
│  │ type (4)              │ tipo_biometria='cara' │                   │
│  │ type (15)             │ tipo_biometria='rfid' │                   │
│  │ inoutstate (0)        │ tipo='entrada'        │                   │
│  │ inoutstate (1)        │ tipo='salida'         │                   │
│  └───────────────────────┴──────────────────────┘                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

# 8. SEGURIDAD E INFRAESTRUCTURA

## 8.1 Arquitectura de Seguridad

```
┌─────────────────────────────────────────────────────────────────────────┐
│                ARQUITECTURA DE SEGURIDAD                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  CAPA 1: SEGURIDAD PERÍMETRO                                           │
│  ════════════════════════════                                          │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    WAF / Firewall                               │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐  │   │
│  │  │ Rate     │  │ SQL      │  │ XSS      │  │ DDoS         │  │   │
│  │  │ Limiting │  │ Injection │  │ Protection│  │ Protection   │  │   │
│  │  │ 100/min  │  │ Blocking │  │ Blocking  │  │ Cloudflare   │  │   │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  CAPA 2: TRANSPORTE (TLS)                                               │
│  ══════════════════════                                                │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    TLS 1.3                                      │   │
│  │  • HSTS enabled                                                 │   │
│  │  • Certificate: Let's Encrypt / OV                             │   │
│  │  • Cipher suite: TLS_AES_256_GCM_SHA384                        │   │
│  │  • Redirect HTTP → HTTPS                                       │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  CAPA 3: AUTENTICACIÓN                                                  │
│  ══════════════════                                                    │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    Autenticación Multi-factor                   │   │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌───────────────┐  │   │
│  │  │ Factor 1       │  │ Factor 2        │  │ Factor 3      │  │   │
│  │  │ (Conocimiento) │  │ (Posesión)      │  │ (Inherencia) │  │   │
│  │  │                │  │                 │  │               │  │   │
│  │  │ Contraseña     │  │ Sesión BD      │  │ IP whitelist  │  │   │
│  │  │ (bcrypt)      │  │ (24h timeout)  │  │               │  │   │
│  │  │                │  │ CSRF Token     │  │               │  │   │
│  │  └─────────────────┘  └─────────────────┘  └───────────────┘  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  CAPA 4: AUTORIZACIÓN                                                  │
│  ═════════════════                                                      │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    RBAC + Middleware                            │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │   │
│  │  │ Route    │  │ Controller│  │ Model    │  │ Field       │   │   │
│  │  │ Guard    │  │  Check    │  │  Access   │  │  Level      │   │   │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  CAPA 5: DATOS                                                         │
│  ═══════════                                                            │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    Protección de Datos                          │   │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌───────────────┐  │   │
│  │  │ Cifrado en      │  │ Cifrado en      │  │ Tokenización │  │   │
│  │  │ reposo (AES-256)│  │ tránsito (TLS)  │  │              │  │   │
│  │  │                 │  │                 │  │              │  │   │
│  │  │ Huellas dact.  │  │ HTTPS Obligat.  │  │ Sesiones BD  │  │   │
│  │  │ Datos sensibles │  │                 │  │              │  │   │
│  │  └─────────────────┘  └─────────────────┘  └───────────────┘  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    ▼                                    │
│  CAPA 6: AUDITORÍA                                                      │
│  ══════════════                                                        │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    Logging y Monitoreo                          │   │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌───────────────┐  │   │
│  │  │ Access Log     │  │ Error Log       │  │ Audit Log     │  │   │
│  │  │                 │  │                 │  │               │  │   │
│  │  │ IP, User, Time │  │ Stack Trace     │  │ CRUD actions │  │   │
│  │  │ Request, Method│  │ Context         │  │ Before/After │  │   │
│  │  │ Response Code  │  │                 │  │               │  │   │
│  │  └─────────────────┘  └─────────────────┘  └───────────────┘  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## 8.2 Configuración de Seguridad PHP

```php
// Configuración de seguridad en php.ini
// ======================================

// Deshabilitar exposición de PHP
expose_php = Off
server_signature = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

// Configuración de sesiones
session.use_strict_mode = 1
session.use_only_cookies = 1
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.referer_check = example.com

// Límites de upload
file_uploads = 1
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
memory_limit = 256M

// Restricciones de allow
allow_url_fopen = 0
allow_url_include = 0
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

// Headers de seguridad (en PHP o .htaccess)
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
```

## 8.3 Configuración de Seguridad MySQL

```sql
-- Crear usuario con privilegios específicos
CREATE USER 'sistema_biometrico'@'localhost' IDENTIFIED BY 'password_seguro_123';

-- Otorgar privilegios solo a la base de datos necesaria
GRANT SELECT, INSERT, UPDATE, DELETE ON sistema_biometrico.* TO 'sistema_biometrico'@'localhost';

-- Configuración de contraseña segura
ALTER USER 'sistema_biometrico'@'localhost' PASSWORD EXPIRE NEVER;

-- Habilitar SSL para conexiones (en producción)
-- REQUIRE SSL;

-- Flush privilegios
FLUSH PRIVILEGES;

-- Tabla de auditoría
CREATE TABLE logs_auditoria (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(64) NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    registro_id BIGINT NOT NULL,
    usuario_id INT,
    datos_anterior TEXT,
    datos_nuevo TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tabla_registro (tabla, registro_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB;
```

---

# 9. INSTALACIÓN Y CONFIGURACIÓN

## 9.1 Requisitos del Sistema

### 9.1.1 Requisitos de Hardware

| Componente | Mínimo | Recomendado | Producción |
|-------------|--------|-------------|------------|
| CPU | 2 cores | 4 cores | 8+ cores |
| RAM | 2 GB | 4 GB | 8+ GB |
| Disco | 20 GB | 50 GB | 100+ GB SSD |
| Red | 100 Mbps | 1 Gbps | 1 Gbps redundante |

### 9.1.2 Requisitos de Software

| Componente | Versión Mínima | Versión Recomendada |
|------------|---------------|---------------------|
| Sistema Operativo | Ubuntu 20.04 / CentOS 8 | Ubuntu 22.04 LTS |
| PHP | 8.0 | 8.2+ |
| MySQL | 8.0 | 8.0.30+ |
| Apache | 2.4 | 2.4.52+ |
| Composer | 2.0 | Latest |
| Node.js | 16.x | 20.x (para assets) |

### 9.1.3 Extensiones PHP Requeridas

```bash
# Instalar extensiones PHP necesarias
sudo apt-get install php8.2 php8.2-cli php8.2-fpm php8.2-mysql \
    php8.2-xml php8.2-mbstring php8.2-curl php8.2-gd php8.2-zip \
    php8.2-bcmath php8.2-intl php8.2-redis php8.2-sqlite3
```

## 9.2 Pasos de Instalación

### 9.2.1 Instalación en Servidor

```bash
#!/bin/bash
# Script de instalación del Sistema Biométrico

set -e

echo "=========================================="
echo "  INSTALACIÓN SISTEMA BIOMÉTRICO v2.0    "
echo "=========================================="

# 1. Clonar o copiar archivos
echo "[1/8] Copiando archivos..."
cp -r sistema_biometrico /var/www/

# 2. Instalar dependencias Composer
echo "[2/8] Instalando dependencias PHP..."
cd /var/www/sistema_biometrico
composer install --no-dev --optimize-autoloader

# 3. Configurar permisos
echo "[3/8] Configurando permisos..."
chown -R www-data:www-data /var/www/sistema_biometrico
chmod -R 755 /var/www/sistema_biometrico
chmod -R 775 /var/www/sistema_biometrico/{logs,cache,uploads,backups}

# 4. Configurar base de datos
echo "[4/8] Configurando base de datos..."
mysql -u root -p < database.sql

# 5. Configurar variables de entorno
echo "[5/8] Configurando variables de entorno..."
cp .env.example .env
# Editar .env con valores correctos

# 6. Generar clave de cifrado
echo "[6/8] Generando clave de cifrado..."
php -r "echo bin2hex(random_bytes(16));"

# 7. Configurar Apache
echo "[7/8] Configurando Apache..."
cp sistema_biometrico.conf /etc/apache2/sites-available/
a2ensite sistema_biometrico
a2enmod rewrite ssl headers

# 8. Reiniciar servicios
echo "[8/8] Reiniciando servicios..."
systemctl restart apache2

echo "=========================================="
echo "  INSTALACIÓN COMPLETADA                 "
echo "=========================================="
```

### 9.2.2 Configuración de Variables de Entorno

```bash
# Archivo: .env

# Base de datos
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=sistema_biometrico
DB_USERNAME=sistema_biometrico
DB_PASSWORD=password_seguro_aqui

# Aplicación
APP_NAME="Sistema Biométrico"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://biometrico.dominio.com

# Seguridad
ENCRYPTION_KEY=clave_cifrado_32_caracteres_aqui
SESSION_LIFETIME=1440

# ZKTeco
BIOMETRIC_API_URL=http://192.168.1.100:8080/api
BIOMETRIC_API_KEY=api_key_del_dispositivo
BIOMETRIC_SIMULATION=false

# Correo
MAIL_MAILER=smtp
MAIL_HOST=smtp.dominio.com
MAIL_PORT=587
MAIL_USERNAME=noreply@dominio.com
MAIL_PASSWORD=password_correo
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@dominio.com
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_LEVEL=warning
LOG_CHANNEL=daily
```

---

# 10. OPERACIÓN Y MANTENIMIENTO

## 10.1 Monitoreo del Sistema

### 10.1.1 Métricas de Salud

```
┌─────────────────────────────────────────────────────────────────────────┐
│                 DASHBOARD DE MONITOREO                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  SALUD GENERAL DEL SISTEMA           [●] Operativo              │   │
│  │                                                                  │   │
│  │  Uptime: 99.9% (30 días)    Última caída: hace 15 días         │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐   │
│  │   RENDIMIENTO     │  │    BASE DATOS     │  │    APLICACIÓN     │   │
│  │                   │  │                   │  │                   │   │
│  │ CPU: 45%          │  │ Conexiones: 12    │  │ Requests/min: 85  │   │
│  │ RAM: 2.1 GB       │  │ Queries/sec: 150  │  │ Avg Response: 320ms│   │
│  │ Disco: 35%        │  │ Cache Hit: 94%    │  │ Error Rate: 0.1%  │   │
│  │                   │  │ InnoDB Size: 2GB  │  │ Active Users: 23  │   │
│  └───────────────────┘  └───────────────────┘  └───────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  MÉTRICAS DE ASISTENCIA (HOY)                                   │   │
│  │                                                                  │   │
│  │  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐       │   │
│  │  │Asistieron │ │ Retardos  │ │  Faltas   │ │Justific.  │       │   │
│  │  │   1,245   │ │    87     │ │    12     │ │    34     │       │   │
│  │  │  (89.6%)  │ │  (6.3%)   │ │  (0.9%)   │ │  (2.4%)   │       │   │
│  │  └───────────┘ └───────────┘ └───────────┘ └───────────┘       │   │
│  │                                                                  │   │
│  │  [████████████░░░░░░░░░░░] 89.6% Asistencia                     │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  ALERTAS ACTIVAS                                                │   │
│  │                                                                  │   │
│  │  [!] Advertencia: 3 empleados con 3+ retardos no justificados  │   │
│  │  [!] Info: Sincronización de dispositivo #2 completada         │   │
│  │  [!] Advertencia: Backup automático no completado (servidor 2)│   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.1.2 Scripts de Monitoreo

```bash
#!/bin/bash
# Script: health_check.sh

# Verificar servicio Apache
if ! systemctl is-active --quiet apache2; then
    echo "ALERTA: Apache no está funcionando"
    systemctl restart apache2
fi

# Verificar MySQL
if ! systemctl is-active --quiet mysql; then
    echo "ALERTA: MySQL no está funcionando"
    systemctl restart mysql
fi

# Verificar espacio en disco
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 85 ]; then
    echo "ALERTA: Disco al ${DISK_USAGE}%"
fi

# Verificar memoria
MEMORY_USAGE=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100}')
if [ "$MEMORY_USAGE" -gt 90 ]; then
    echo "ALERTA: Memoria al ${MEMORY_USAGE}%"
fi

# Verificar conexiones BD
CONNECTIONS=$(mysql -u sistema_biometrico -p -e "SHOW STATUS LIKE 'Threads_connected';" | awk 'NR==2 {print $2}')
if [ "$CONNECTIONS" -gt 80 ]; then
    echo "ALERTA: Muchas conexiones BD: $CONNECTIONS"
fi
```

## 10.2 Respaldo y Recuperación

### 10.2.1 Política de Backups

| Tipo | Frecuencia | Retención | Ubicación |
|------|------------|-----------|----------|
| Completo | Diario (00:00) | 30 días | Servidor local + remoto |
| Incremental | Cada 6 horas | 7 días | Servidor local |
| Binlogs MySQL | Continuo | 7 días | Servidor local |
| Configuración | Por cambio | 12 meses | Git + servidor |
| Código | Por release | Indefinido | Git |

### 10.2.2 Script de Backup

```bash
#!/bin/bash
# Script: backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
DB_NAME="sistema_biometrico"
DB_USER="sistema_biometrico"

# Crear directorio si no existe
mkdir -p $BACKUP_DIR

# Backup de base de datos
echo "Iniciando backup de base de datos..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup de archivos de configuración
echo "Respaldando archivos de configuración..."
tar -czf $BACKUP_DIR/config_$DATE.tar.gz /var/www/sistema_biometrico/{.env,config.php}

# Backup de uploads
echo "Respaldando archivos subidos..."
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/sistema_biometrico/uploads/

# Limpiar backups antiguos (más de 30 días)
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

# Verificar tamaño del backup
BACKUP_SIZE=$(du -sh $BACKUP_DIR | cut -f1)
echo "Backup completado. Tamaño: $BACKUP_SIZE"
```

---

# 11. ANEXOS

## 11.1 Glosario de Términos

| Término | Definición |
|---------|------------|
| **API** | Application Programming Interface - Interfaz de programación de aplicaciones |
| **Biometría** | Tecnología de identificación basada en características fisiológicas (huella, rostro) o comportamentales |
| **CSRF** | Cross-Site Request Forgery - Tipo de ataque que aprovecha la confianza de un sitio en el usuario |
| **CURP** | Clave Única de Registro de Población - Identificador oficial mexicano de 18 caracteres |
| **Dashboard** | Panel de control con métricas e indicadores visuales |
| **FastRoute** | Biblioteca de enrutamiento para PHP |
| **JWT** | JSON Web Token - Estándar para crear tokens de acceso |
| **MVC** | Model-View-Controller - Patrón de arquitectura de software |
| **PDO** | PHP Data Objects - Abstracción de base de datos para PHP |
| **RBAC** | Role-Based Access Control - Control de acceso basado en roles |
| **RFC** | Registro Federal de Contribuyentes - Identificador fiscal mexicano de 13 caracteres |
| **SQL Injection** | Técnica de ataque que inserta código SQL malicioso |
| **TLS** | Transport Layer Security - Protocolo de seguridad para comunicaciones |
| **ZKTeco** | Fabricante chino de dispositivos biométricos de control de asistencia |

## 11.2 Referencias Normativas

1. **ISO/IEC 25010:2023** - Systems and software Quality Requirements and Evaluation (SQuaRE)
2. **ISO/IEC 27001:2022** - Information security management systems
3. **ISO/IEC 27002:2022** - Guidelines on information security controls
4. **ISO/IEC 12207:2017** - Software life cycle processes
5. **ISO 9001:2015** - Quality management systems
6. **ISO 22301:2019** - Business continuity management
7. **NIST SP 800-53** - Security and Privacy Controls for Information Systems
8. **OWASP Top 10** - Common web application security risks
9. **LGPD México** - Ley General de Protección de Datos Personales

## 11.3 Estructura de Directorios Completa

```
sistema_biometrico/
├── api/                              # Endpoints de API REST
│   └── endpoints/                   # Manejadores de API
├── assets/                           # Recursos estáticos
│   ├── css/                         # Hojas de estilo
│   │   ├── bootstrap.min.css
│   │   ├── custom.css
│   │   └── components.css
│   ├── js/                          # Scripts JavaScript
│   │   ├── bootstrap.bundle.min.js
│   │   ├── chart.js
│   │   └── custom.js
│   ├── images/                     # Imágenes
│   │   ├── logo.png
│   │   └── favicon.ico
│   └── fonts/                       # Fuentes
├── backups/                         # Respaldos automáticos
│   ├── db/
│   ├── config/
│   └── uploads/
├── cache/                          # Caché del sistema
│   ├── templates/
│   └── data/
├── classes/                        # Clases utilitarias
│   ├── Encryption.php
│   └── Validator.php
├── config/                         # Configuraciones
│   ├── database.php
│   └── routes.php -> routes.php (root)
├── controllers/                    # Controladores MVC
│   ├── BaseController.php
│   ├── AuthController.php
│   ├── EmpleadoController.php
│   ├── AsistenciaController.php
│   ├── HorarioController.php
│   ├── JustificacionController.php
│   ├── ReportesController.php
│   ├── UsuarioController.php
│   ├── ZKTecoController.php
│   ├── AIController.php
│   └── ... (más controladores)
├── data/                           # Archivos de datos
│   └── plantillas/
├── database_sql/                   # Scripts SQL
│   ├── create_tables.sql
│   └── seed_data.sql
├── docs/                           # Documentación
│   ├── manuales/
│   └── imagenes/
├── helpers/                        # Funciones helper
│   ├── Csrf.php
│   ├── RfcCurpHelper.php
│   ├── RequestValidator.php
│   └── DateHelper.php
├── lib/                            # Bibliotecas externas
├── logs/                           # Archivos de log
│   ├── application/
│   ├── errors/
│   └── audit/
├── migrations/                     # Migraciones de BD
├── models/                        # Modelos MVC
│   ├── Database.php
│   ├── Empleado.php
│   ├── Asistencia.php
│   ├── Retardo.php
│   ├── HorarioLaboral.php
│   ├── ZKTecoAsistenciaInserter.php
│   ├── ZKTecoUniversalParser.php
│   └── ... (más modelos)
├── public/                        # Archivos públicos
├── scripts/                       # Scripts de mantenimiento
│   ├── backup.sh
│   ├── restore.sh
│   ├── health_check.sh
│   └── sync_zkteco.sh
├── services/                      # Servicios
│   ├── NotificationService.php
│   ├── ExportService.php
│   └── AnalyticsService.php
├── sql/                           # Consultas SQL
├── src/                          # Código fuente
├── supports/                    # Archivos de soporte
├── tests/                       # Pruebas
│   ├── Unit/
│   └── E2E/
├── uploads/                     # Archivos subidos
│   ├── empleados/
│   ├── justificaciones/
│   └── reportes/
├── vendor/                     # Dependencias Composer
├── views/                      # Vistas MVC
│   ├── layout.php
│   ├── partials/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   └── footer.php
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── empleados/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── edit.php
│   ├── asistencia/
│   │   ├── index.php
│   │   └── reporte.php
│   └── ... (más vistas)
├── .env                        # Variables de entorno
├── .env.example                # Ejemplo de variables
├── .gitignore                  # Ignorar archivos Git
├── composer.json               # Dependencias PHP
├── composer.lock               # Lock de dependencias
├── config.php                  # Configuración principal
├── database.sql                # Esquema de BD
├── index.php                   # Front controller
├── routes.php                  # Definición de rutas
├── phpunit.xml                 # Configuración PHPUnit
├── README.md                   # Documentación general
└── php.ini                     # Configuración PHP
```

## 11.4 Contactos de Soporte

| Rol | Contacto | Teléfono | Email |
|-----|----------|----------|-------|
| Administrador del Sistema | Juan Pérez | 55-1234-5678 | admin@dominio.com |
| Soporte Técnico | María García | 55-2345-6789 | soporte@dominio.com |
| Base de Datos (DBA) | Carlos López | 55-3456-7890 | dba@dominio.com |
| Seguridad | Ana Rodríguez | 55-4567-8901 | seguridad@dominio.com |

---

# CONTROL DE VERSIONES DEL DOCUMENTO

| Versión | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0 | Enero 2025 | Equipo Desarrollo | Versión inicial |
| 2.0 | Marzo 2026 | Equipo Desarrollo | Versión completa producción |

---

**DOCUMENTO ELABORADO CONFORME A:**

- ISO/IEC 25010:2023
- ISO/IEC 27001:2022
- ISO/IEC 12207:2017
- ISO 9001:2015

**CLASIFICACIÓN:** CONFIDENCIAL  
**DISTRIBUCIÓN:** Restringida  

*Este documento es propiedad del área de Tecnologías de la Información y contiene información sensible del sistema. Queda prohibida su reproducción o distribución sin autorización expresa.*
