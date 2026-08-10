# Sistema Biométrico - Especificaciones Técnicas Mejoradas

## 1. VISIÓN GENERAL

Sistema web de control de asistencia biométrica con integración ZKTeco, gestionando el ciclo completo: marcaje → validación → justificación → sanción → reporte. Diseñado para la Administración Pública Federal (SEP) con esquema de jerarquías organizacionales (Direcciones → Subdirecciones → Departamentos).

---

## 2. ARQUITECTURA DEL SISTEMA

### 2.1 Patrón Arquitectónico

| Capa | Tecnología | Ubicación |
|------|-----------|-----------|
| Front Controller | `index.php` | Raíz |
| Routing | FastRoute (nikic/fast-route) | `routes.php` |
| Controllers | PHP 8.4 OOP | `controllers/` (36 archivos) |
| Models | Active Record + Singleton DB | `models/` (39 archivos) |
| Services | Lógica de negocio | `services/` (16 archivos) |
| Helpers | Utilerías transversales | `helpers/` (12 archivos) |
| Views | PHP + Bootstrap 5.3 | `views/` (30 subdirectorios) |
| API Layer | Scripts directos PHP | `api/` (14 archivos) |
| Namespaced Code | `App\` namespace | `src/` (9 archivos) |

### 2.2 Flujo de Petición

```
Navegador → .htaccess (rewrite) → index.php (front controller)
  ├── Carga config.php (DB, errores, sesiones)
  ├── Detección de ruta API temprana (antes de sesión)
  ├── Vendor autoload (Composer)
  ├── Helpers base (Csrf, SecurityHelper)
  ├── Session handlers DB-stored
  ├── Seguridad: CSP headers, CSRF validation
  ├── Verificación de autenticación
  └── FastRoute dispatch → Controller::method()
       └── Controller → Model/Service → View (render)
```

### 2.3 Diagrama de Capas

```
┌─────────────────────────────┐
│       index.php / .htaccess │  ← Front Controller / Rewrite
├─────────────────────────────┤
│         routes.php          │  ← FastRoute 200+ rutas
├─────────────────────────────┤
│     controllers/*.php       │  ← 36 Controladores (BaseController)
├─────────────────────────────┤
│  services/*.php             │  ← Lógica de negocio compleja
│  helpers/*.php              │  ← Cross-cutting (CSRF, Crypto, 2FA)
├─────────────────────────────┤
│  models/*.php (Database.php)│  ← Singleton PDO (Active Record)
├─────────────────────────────┤
│  views/*.php (layout.php)   │  ← Bootstrap 5.3 + jQuery
├─────────────────────────────┤
│  api/*.php                  │  ← Endpoints JSON directos
└─────────────────────────────┘
```

---

## 3. ESPECIFICACIONES DE BASE DE DATOS

### 3.1 Esquema General (40+ tablas)

#### 3.1.1 Catálogos Organizacionales

| Tabla | Propósito | FK |
|-------|-----------|-----|
| `direcciones` | Nivel 1 organizacional | — |
| `subdirecciones` | Nivel 2 (FK a direcciones) | `direccion_id` |
| `departamentos` | Nivel 3 (FK a subdirecciones) | `subdireccion_id` |
| `catalogos_mandos` | Relación jefe-subordinado por área | `clave_depto`, `clave_area` |
| `empleados` | Catálogo maestro de empleados | `jefe_directo_id` → `empleados.id` |

#### 3.1.2 Control de Acceso

| Tabla | Propósito |
|-------|-----------|
| `usuarios` | Usuarios del sistema (rol: admin, superadmin, mando, jefe, user, viewer, rh, supervisor) |
| `sessions` | Sesiones almacenadas en DB |
| `totp_secrets` | 2FA TOTP |
| `menu_config` | Visibilidad de menú por usuario |

#### 3.1.3 Asistencia y Tiempo

| Tabla | Propósito |
|-------|-----------|
| `asistencia` | Registro diario (entrada/salida, dispositivo, biometría) |
| `retardos` | Retardos calculados (con tipo, minutos, estado validación) |
| `horarios_laborales` | Horarios base (entrada/salida/tolerancia/sede) |
| `horarios_empleados` | Asignación por día de semana |
| `ciclos_laborales` | Ciclos de trabajo |
| `bloques_ciclo` | Bloques dentro de ciclos (FK a ciclos y horarios) |
| `empleados_ciclos` | Asignación empleado → ciclo |
| `empleado_horarios` | Historial de asignaciones |

#### 3.1.4 Incidencias

| Tabla | Propósito |
|-------|-----------|
| `justificaciones` | Solicitudes de justificación (pendiente/aprobada/rechazada) |
| `tipos_justificacion` | Catálogo de tipos (con `requiere_aprobacion`) |
| `modificaciones_justificacion` | Historial de cambios |
| `comisiones` | Comisiones oficiales |
| `ausencias` | Ausencias (médica, personal, vacaciones, otro) |
| `licencias_medicas` | Licencias médicas |
| `dias_economicos` | Días económicos personales |
| `vacaciones` | Solicitudes de vacaciones |
| `permiso_fallecimiento` | Permisos por fallecimiento |
| `sanciones` | Sanciones (amonestación, suspensión, acta administrativa) |
| `notas_malas` | Notas por acumulación de retardos |

#### 3.1.5 Validación y Aprobación

| Tabla | Propósito |
|-------|-----------|
| `validaciones_jefe` | Validaciones de jefe inmediato sobre incidencias |
| `validacion_mensajes` | Conversaciones bidireccionales en validaciones |
| `notificaciones_licencias` | Notificaciones automáticas |

#### 3.1.6 Dispositivos Biométricos

| Tabla | Propósito |
|-------|-----------|
| `dispositivos_biometricos` | Configuración de dispositivos ZKTeco |
| `dispositivos_biometricos_config` | Configuración extendida |
| `huellas_digitales` | Templates de huellas |
| `logs_dispositivos` | Logs de eventos |

#### 3.1.7 Inteligencia Artificial

| Tabla | Propósito |
|-------|-----------|
| `reglas_negocio_ia` | Reglas de negocio para el agente IA |
| `bitacora_agente_ia` | Bitácora de actividades del agente |
| `analisis_predictivo_config` | Configuración de análisis predictivo |
| `anomalias_detectadas` | Anomalías detectadas por IA |

#### 3.1.8 Utilidades

| Tabla | Propósito |
|-------|-----------|
| `constancias_tiempo` | Constancias laborales generadas |
| `plantillas_documentos` | Plantillas para documentos |

### 3.2 Convenciones de Base de Datos

- **Naming**: `snake_case` para tablas y columnas
- **Charset**: `utf8mb4` / `utf8mb4_unicode_ci`
- **Engine**: InnoDB (transaccional)
- **Soft Delete**: `activo TINYINT(1) DEFAULT 1` en lugar de DELETE físico
- **Timestamps**: `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
- **Migraciones**: `migrations/YYYYMMDD_descripcion.sql`
- **Backups**: `backups/database/` y `backups/database_sql/`

### 3.3 Índices Críticos Existentes

```sql
-- Asistencia
INDEX idx_fecha_empleado (fecha, empleado_id)
INDEX idx_dispositivo_fecha (dispositivo_id, fecha)

-- Retardos
INDEX idx_retardos_fecha_empleado (fecha, empleado_id)
INDEX idx_retardos_tipo_estado (tipo_retraso, estado_validacion)

-- Horarios
INDEX idx_horario_sede (sede)
UNIQUE KEY unique_empleado_dia_sede (empleado_id, dia_semana, sede)
```

---

## 4. ESPECIFICACIONES DE MÓDULOS

### 4.1 Autenticación y Seguridad

| Aspecto | Especificación |
|---------|---------------|
| Método | Sesiones PHP database-stored |
| 2FA | TOTP (autenticación de dos factores) |
| CSRF | Tokens por sesión validados en POST (exclusiones por ruta) |
| CSP | Content Security Policy con nonces (helpers/SecurityHelper.php) |
| Roles | superadmin, admin, mando, jefe, user, viewer, rh, supervisor |
| Passwords | bcrypt (`password_hash`) |
| Encryption | AES via `helpers/Encryption.php` |

**Rutas públicas**: `/login`, `/register`, `/verify-2fa`, `/api/*`

### 4.2 Dashboard

- Estadísticas en tiempo real (`/dashboard/real-time-stats`)
- Estado de dispositivos biométricos
- Alertas y notificaciones
- Preferencias de usuario
- KPIs: empleados activos, asistencias hoy, retardos, pendientes

### 4.3 Empleados (CRUD Completo)

| Acción | Ruta | Método |
|--------|------|--------|
| Listar | `/empleados` | GET |
| Crear | `/empleados/create` | GET/POST |
| Ver perfil | `/empleados/{id}/ver` | GET |
| Editar | `/empleados/{id}/edit` | GET/POST |
| Eliminar (soft) | `/empleados/{id}/delete` | GET |
| Buscar | `/empleados/search` | GET |
| Importación masiva | `/empleados/bulk-import` | POST |
| Exportar | `/empleados/export` | GET |
| Subir foto | `/empleados/{id}/upload-photo` | POST |
| Historial asistencia | `/empleados/{id}/attendance-history` | GET |

**Campos del empleado**: nombre, apellido, RFC, CURP, email, teléfono, área, área_física, puesto, jerarquía, sexo, fecha_nacimiento, entidad_federativa, foto_cara, huella_dactilar (encrypted), clave_depto, jefe_directo_id, jefe_directo_clave

**Generación RFC/CURP**: Algoritmos implementados en `RfcCurpHelper.php`

### 4.4 Asistencia y Marcaciones

| Funcionalidad | Ruta |
|--------------|------|
| Panel asistencia | `/asistencia` |
| Registro entrada/salida | `POST /asistencia/registrar-entrada`, `/asistencia/registrar-salida` |
| Filtros | `/asistencia/filtrar-empleados`, `/asistencia/filtrar-asistencia` |
| Cálculo horas laborables | `/asistencia/calcular-horas-laborables` |
| Exportar Excel/PDF | `/asistencia/exportar-excel`, `/asistencia/exportar-pdf` |
| Marcaciones en vivo | `/asistencia/real-time` |
| Corrección masiva | `/asistencia/bulk-correct` |
| Resumen | `/asistencia/summary` |
| Marcaciones | `/marcaciones` |

### 4.5 Horarios y Ciclos

**Horarios Laborales**: nombre, hora_entrada, hora_salida, tolerancia_minutos, sede
**Asignación**: por empleado + día de semana + sede
**Ciclos**: grupos de horarios para rotación (ej. turno matutino/vespertino)
**Mantenimiento**: `/horarios/mantenimiento` para recalcular asignaciones

| Ruta | Propósito |
|------|-----------|
| `/horarios` | CRUD horarios |
| `/horarios/asignar` | Asignar horario a empleado |
| `/horarios/ciclos` | Gestión de ciclos |
| `/ciclos` | CRUD ciclos y bloques |
| `/ciclos/bloques/json/{id}` | Bloques de ciclo vía JSON |

### 4.6 Dispositivos Biométricos

Integración con ZKTeco a través de SDK propietario en `lib/`.

| Operación | Ruta |
|-----------|------|
| CRUD dispositivos | `/dispositivos/*` |
| Gestión biométrica | `/biometricos/gestionar` |
| Capturar huella | `/biometricos/capturar/{id}` |
| Registrar huella | `POST /biometricos/registrar-huella` |
| Sincronizar empleados | `/biometricos/sincronizar/{id}` |
| Descargar asistencias | `POST /biometricos/descargar-asistencias/{id}` |
| Conectar/verificar | `/biometricos/conectar/{id}`, `/biometricos/verificar/{id}` |
| Logs dispositivo | `/biometricos/logs-dispositivo/{id}` |
| Actualizar empleados en dispositivo | `/biometricos/actualizar-empleado/{id}` |

**Pipeline de procesamiento ZKTeco** (`models/ZKTeco*`):
1. `UniversalParser` → Detecta formato del archivo
2. `FormatDetector` → Identifica estructura de datos
3. `Mapper` → Mapea campos al esquema DB
4. `LogProcessor` → Procesa logs de dispositivos
5. `AsistenciaInserter` → Inserta en tabla `asistencia`

### 4.7 Retardos y Notas Malas

**Cálculo automático de retardos**:
- Compara hora_entrada del empleado vs horario asignado
- Aplica tolerancia configurable (TOLERANCE_MINUTES)
- Clasifica: normal, retardo_menor, retardo_mayor, falta, comision, dia_economico, ausencia
- Requiere validación del jefe si aplica

**Notas Malas**: acumulación de retardos → sanción automática
- Evalúa por mes/quincena
- Crea sanciones automáticas basadas en reglas
- `POST /notas-malas/evaluar-sanciones` (individual)
- `POST /notas-malas/procesar-todos` (masivo)

### 4.8 Justificaciones

**Tipos de justificación**: catálogo configurable con indicador `requiere_aprobacion`
**Flujo**:
1. Seleccionar tipo + fecha + motivo
2. Subir soporte/evidencia
3. Aprobación (si requiere)
4. Registro en `justificaciones` y actualización de `retardos`

**Registro unificado**: `/justificaciones/registrar` con campos dinámicos por tipo

**Modificaciones**: historial en `modificaciones_justificacion`

### 4.9 Validaciones de Jefe

**Sistema conversacional bidireccional**:
- Jefe recibe incidencias pendientes de sus subordinados
- Puede aprobar/rechazar con comentarios
- Sistema de mensajes: empleado ↔ jefe
- Validación masiva

| Endpoint | Propósito |
|----------|-----------|
| `/validaciones` | Panel de validaciones |
| `/mis-validaciones` | Mis validaciones (empleado) |
| `/validaciones/mensajes/{id}` | Obtener mensajes |
| `POST /validaciones/mensajes/agregar` | Enviar mensaje |
| `POST /validaciones/procesar-masivo` | Validación masiva |
| `/validaciones/obtener-contador-pendientes` | Badge contador |

### 4.10 Sanciones

| Tipo | Descripción |
|------|-------------|
| `amonestacion` | Amonestación verbal/escrita |
| `suspension` | Suspensión laboral |
| `acta_administrativa` | Acta administrativa |
| `otro` | Otros tipos |

Estados: `activa`, `cumplida`, `cancelada`

### 4.11 Inteligencia Artificial y Análisis Predictivo

**Agente IA** (`controllers/AgenteIAController.php`, `services/AgenteIAService.php`):
- Procesamiento de incidencias no validadas
- Reglas de negocio configurables (`reglas_negocio_ia`)
- Generación automática de documentos y reportes
- Clasificación inteligente de retardos

**Análisis Predictivo** (`controllers/AnalisisPredictivoController.php`, `services/MachineLearningEngine.php`):
- Predicción de riesgos de ausentismo
- Análisis de patrones por empleado/área
- Alertas tempranas
- Recomendaciones automáticas
- Comparación de períodos

**Motor ML**: MachineLearningEngine con detección de anomalías, predicción de tendencias

**Agente Inteligente** (`controllers/AgenteInteligenteController.php`):
- Dashboard consolidado
- Métricas en tiempo real
- Análisis avanzado con visualización 3D (Three.js)

### 4.12 Reportes

**Tipos de reporte**:
- Asistencia general (Excel/PDF)
- Faltas y retardos
- Resumen de justificaciones
- Desempeño por empleado
- Exportación nómina
- Reportes programados

**Exportación**: PhpSpreadsheet (XLSX), DomPDF (PDF)

### 4.13 Comisiones y Días Económicos

**Comisiones**: registro, aprobación, justificación, límites por empleado
**Días Económicos**: solicitud, aprobación/rechazo, consulta por empleado, historial

### 4.14 Catálogos Organizacionales

Gestión jerárquica de:
- Direcciones (nivel 1)
- Subdirecciones (nivel 2, FK a direcciones)
- Departamentos (nivel 3, FK a subdirecciones)
- Mandos (relación jefe-subordinado por clave de área)

### 4.15 Configuración de Menú

Permisos granulares: cada usuario puede tener configuración de visibilidad de menú vía `menu_config`.
Rol base + permisos adicionales por usuario.

### 4.16 Database Manager

Herramienta integrada para:
- Backup/restore
- Listado de tablas + foreign keys
- Estado de la BD

---

## 5. ESPECIFICACIONES DE API

### 5.1 Convenciones Generales

- **Formato**: JSON
- **Autenticación**: Sesión (cookie-based)
- **CSRF**: Token en header `X-CSRF-TOKEN` o campo `csrf_token`
- **Respuesta estándar**: `{"success": true/false, "data": ..., "error": "..."}`
- **Métodos**: GET (lectura), POST (escritura)
- **Content-Type**: `application/json`

### 5.2 Endpoints API Directos

| Endpoint | Método | Propósito |
|----------|--------|-----------|
| `/api/lista-empleados` | GET | Lista empleados para selects |
| `/api/obtener_registros_justificacion` | GET | Registros de justificación |
| `/api/actualizar_justificacion` | POST | Actualizar justificación |
| `/api/log-error` | POST | Logging de errores JS |
| `/api/tipos-justificacion` | GET | Catálogo de tipos |
| `/api/actualizar-registro-tab` | POST | Actualizar registro desde tabs |
| `/api/datos_justificacion` | GET | Datos para modal justificación |
| `/api/modificaciones_justificacion` | GET | Historial de modificaciones |
| `/api/obtener_registros_marcaciones` | GET | Registros de marcaciones |
| `/api/actualizar_registro_marcaciones` | POST | Actualizar marcación |
| `/api/reportes_excel*` | GET | Exportar reportes Excel |

### 5.3 Endpoints de Ruta (selección)

| Grupo | Rutas clave |
|-------|-------------|
| Empleados | GET/POST `/empleados/create`, POST `/empleados/generate_rfc`, POST `/empleados/capturarHuella` |
| Asistencia | POST `/asistencia/registrar-entrada`, POST `/asistencia/filtrar-asistencia`, GET `/asistencia/exportar-excel` |
| Biométricos | POST `/biometricos/conectar/{id}`, POST `/biometricos/descargar-asistencias/{id}`, POST `/biometricos/actualizar-empleados-masivo/{id}` |
| Validaciones | POST `/validaciones/validar/{id}`, POST `/validaciones/procesar-masivo`, POST `/validaciones/mensajes/agregar` |
| Justificaciones | POST `/justificaciones/ajax-justificar`, POST `/justificaciones/guardar-incidencia` |
| Agente IA | POST `/agente-ia/procesar`, POST `/agente-ia/exportar-excel`, POST `/agente-ia/guardar-regla` |
| Predictivo | GET `/analisis-predictivo/riesgos`, GET `/analisis-predictivo/patrones`, POST `/analisis-predictivo/config` |

---

## 6. ESPECIFICACIONES DE SEGURIDAD

### 6.1 Capas de Seguridad

| Capa | Implementación |
|------|---------------|
| **Headers** | Content Security Policy (CSP) con nonces, X-Frame-Options, X-Content-Type-Options, Strict-Transport-Security, Referrer-Policy (`SecurityHelper::setSecurityHeaders()`) |
| **CSRF** | Tokens por sesión en formularios POST (input hidden + header). Excluye rutas públicas y específicas como `/validaciones/`, `/biometricos/`, `/catalogos/` |
| **Auth** | Sesiones DB-stored, timeout configurable, 2FA TOTP |
| **Roles** | 8 roles con permisos granulares, verificación en cada controlador (`checkAdmin()`, `checkRole()`) |
| **Input** | Prepared statements PDO, validación MIME de archivos vía `finfo`, escape HTML (`htmlspecialchars`) |
| **Encryption** | AES para huellas dactilares, bcrypt para contraseñas |
| **Logging** | Errores PHP y excepciones en `logs/php_errors_YYYY-MM-DD.log`, errores JS en `logs/js_errors_YYYY-MM-DD.log` |

### 6.2 Content Security Policy

Headers configurables en `SecurityHelper.php`:
- `default-src 'self'`
- `script-src 'self' 'nonce-{nonce}' 'strict-dynamic'`
- `style-src 'self' 'unsafe-inline'`
- `img-src 'self' data: blob:`
- `connect-src 'self' ws: wss:`
- `base-uri 'self'`

---

## 7. ESPECIFICACIONES DE FRONTEND

### 7.1 Stack de Tecnologías

| Librería | Versión | Uso |
|----------|---------|-----|
| Bootstrap | 5.3 | UI framework (local en assets/) |
| jQuery | 3.6/3.7 | DOM manipulation, AJAX |
| Font Awesome | 4.7 | Iconos (local en assets/) |
| DataTables | 1.x | Tablas dinámicas con búsqueda/paginación |
| Chart.js | 4.x | Gráficas y dashboards |
| Three.js | r? | Visualización 3D (agente IA) |
| SheetJS | xlsx | Exportación Excel client-side |
| ExcelJS | latest | Exportación Excel avanzada |

### 7.2 Sistema de Temas

**Colores Pantone Institucionales**:
- Vino: `#9F2241`
- Verde: `#235B4E`
- Dorado: `#DDC9A3`
- Gris: `#98989A`

**Tema Claro**: fondos blancos/grises, sidebar gradient vino
**Tema Oscuro**: fondos oscuros, sidebar gradient verde oscuro
Toggle persistente en localStorage

### 7.3 Layout

```
┌─────────────────────────────────┐
│ Top Bar (fijo, 60px)            │
├──────────┬──────────────────────┤
│ Sidebar  │ Main Content         │
│ (250px)  │ (padding: 25px)      │
│ fijo     │                      │
│          │                      │
├──────────┴──────────────────────┤
│ Footer (fijo, bottom)           │
└─────────────────────────────────┘
```

- Responsive: sidebar overlay en mobile (<768px), colapsable
- Sidebar con ítems de navegación + permisos por rol
- Top bar con info de usuario, iniciales, rol, área, theme toggle, logout

### 7.4 Componentes Reutilizables

- **Modales**: headers con gradient (vino/verde/dorado), body padding 28px
- **Cards**: border-radius 16px, shadow, hover effect, gradient top border
- **Tablas**: headers gradient vino, hover con gradient tenue
- **Botones**: gradient backgrounds, border-radius 10px, hover translateY(-2px)
- **Badges**: border-radius 20px, gradient backgrounds
- **Formularios**: border 2px, focus con box-shadow institucional

### 7.5 JavaScript Core

- **empleados.js**: CRUD empleados, DataTable, modales, AJAX
- **validaciones.js**: UI de validaciones de jefe
- **mis_validaciones.js**: UI de empleado para sus validaciones
- **error-logger.js**: Captura y envía errores JS al servidor

---

## 8. INTEGRACIÓN BIOMÉTRICA ZKTECO

### 8.1 Pipeline de Datos

```
Dispositivo ZKTeco (IP:4370)
  ↓ (TCP/IP)
SDK ZKTeco (lib/CKTecoBiometricSDK.php)
  ↓
Descarga archivos .dat (data/)
  ↓
UniversalParser (models/ZKTecoUniversalParser.php)
  ↓
FormatDetector (models/ZKTecoFormatDetector.php)
  ↓
Mapper (models/ZKTecoMapper.php)
  ↓
LogProcessor (models/ZKTecoLogProcessor.php)
  ↓
AsistenciaInserter (models/ZKTecoAsistenciaInserterFinal.php)
  ↓
Tabla: asistencia
  ↓
Cálculo de retardos → Tabla: retardos
```

### 8.2 Operaciones Soportadas

- Conectar/desconectar dispositivo
- Descargar registros de asistencia
- Capturar/enrolar huellas
- Sincronizar empleados (alta/baja en dispositivo)
- Verificar estado del dispositivo
- Modo simulación: `BIOMETRIC_SIMULATION=true`

---

## 9. PRUEBAS Y QA

### 9.1 PHPUnit (7 Suites)

| Suite | Directorio | Tipo |
|-------|-----------|------|
| Unit | `tests/Unit/` | Pruebas unitarias |
| Integration | `tests/Integration/` | Pruebas de integración |
| API | `tests/API/` | Pruebas de API |
| Database | `tests/Database/` | Pruebas de BD |
| Security | `tests/Security/` | Pruebas de seguridad |
| Performance | `tests/Performance/` | Pruebas de rendimiento |
| Functional | `tests/Functional/` | Pruebas funcionales |

### 9.2 E2E (Cypress)

- `tests/E2E/sistema-biometrico.cy.js`
- `tests/E2E/support/commands.js`

### 9.3 Cobertura de Pruebas

- Pruebas de autenticación
- Pruebas de CRUD empleados
- Pruebas de API endpoints
- Pruebas de seguridad (CSRF, SQL injection)
- Pruebas de rendimiento
- Pruebas de integración biométrica

---

## 10. DESPLIEGUE Y OPERACIONES

### 10.1 Configuración de Entorno

| Variable | Propósito | Default |
|----------|-----------|---------|
| `DB_HOST` | Host BD | localhost |
| `DB_USER` | Usuario BD | root |
| `DB_PASS` | Password BD | root |
| `DB_NAME` | Base de datos | sistema_biometrico |
| `APP_NAME` | Nombre app | Sistema Biométrico |
| `APP_VERSION` | Versión | 2.0 |
| `BIOMETRIC_API_URL` | URL API biométrica | http://localhost:8080/api |
| `BIOMETRIC_API_KEY` | API key | dev_key_12345 |
| `BIOMETRIC_SIMULATION` | Modo simulación | true |
| `TOLERANCE_MINUTES` | Tolerancia retardos | 10 |
| `LOG_LEVEL` | Nivel logging | INFO |

### 10.2 Scripts de Mantenimiento

| Script | Propósito |
|--------|-----------|
| `backup.sh` / `backup.bat` | Backup BD |
| `restore.bat` | Restaurar BD |
| `maintenance.php` | Mantenimiento general |
| `maintenance.bat` | Mantenimiento Windows |
| `deploy_staging.sh` | Deploy staging |
| `setup_production.bat` | Setup producción |
| `install.sh` / `install.bat` | Instalación |
| `final_test.sh` | Pruebas finales |

### 10.3 Monitoreo (monitoring/)

- **Prometheus**: `prometheus.yml` para métricas de rendimiento
- **Grafana**: `dashboard.json` para visualización de métricas
- **Logs**: rotación automática cada 10MB, máximo 5 archivos

---

## 11. DEPENDENCIAS

### 11.1 PHP (Composer)

| Paquete | Propósito |
|---------|-----------|
| `phpmailer/phpmailer` | Correos electrónicos |
| `phpoffice/phpspreadsheet` | Exportación Excel |
| `dompdf/dompdf` | Exportación PDF |
| `nikic/fast-route` | Router |
| `vlucas/phpdotenv` | Variables de entorno |
| `monolog/monolog` | Logging estructurado |

### 11.2 JavaScript (Node.js)

| Paquete | Propósito |
|---------|-----------|
| Cypress | E2E testing |
| Webpack | Bundling |
| ESLint | Linting |

---

## 12. CONVENCIONES DE CÓDIGO

### 12.1 PHP

| Aspecto | Convención |
|---------|-----------|
| Clases | PascalCase (`EmpleadoController`) |
| Métodos | camelCase (`getAll()`, `registrarEntrada()`) |
| Propiedades | camelCase (`$this->db`) |
| DB columnas | snake_case (`empleado_id`, `hora_entrada`) |
| Archivos controlador | `EntityController.php` |
| Archivos modelo | `Entity.php` |
| Archivos vista | `views/entity/action.php` |
| Archivos servicio | `services/EntityService.php` |

### 12.2 Frontend

| Aspecto | Convención |
|---------|-----------|
| JS files | snake_case (`empleados.js`, `mis_validaciones.js`) |
| CSS classes | Bootstrap + kebab-case personalizadas |
| IDs | camelCase (`#sidebarCollapse`, `#mobileMenuBtn`) |
| Data attributes | `data-bs-*` (Bootstrap) |

### 12.3 Base de Datos

| Aspecto | Convención |
|---------|-----------|
| Tablas | snake_case plural (`empleados`, `horarios_laborales`) |
| Columnas | snake_case singular (`empleado_id`, `fecha_registro`) |
| PKs | `id INT AUTO_INCREMENT` |
| FKs | `{tabla}_id` |
| Timestamps | `created_at`, `updated_at` |

---

## 13. MEJORAS RECOMENDADAS

### 13.1 Arquitectura
- [ ] Migrar a namespaces completos (PSR-4) para eliminar `require_once`
- [ ] Implementar Dependency Injection Container
- [ ] Separar API router del front controller
- [ ] Implementar middleware pipeline (auth, csrf, logging)

### 13.2 Base de Datos
- [ ] Implementar migraciones automatizadas con versión
- [ ] Agregar índices compuestos para queries frecuentes
- [ ] Normalizar tabla `retardos` (demasiadas columnas)
- [ ] Implementar soft deletes consistentes con `deleted_at`

### 13.3 Seguridad
- [ ] Implementar rate limiting en endpoints críticos
- [ ] Migrar a JWT o tokens de API para integraciones
- [ ] Auditoría completa de acciones de usuario
- [ ] Implementar OWASP Top 10 checklist automatizado

### 13.4 Frontend
- [ ] Migrar de jQuery a Vanilla JS o framework moderno (Alpine, htmx)
- [ ] Implementar modo offline / PWA
- [ ] Unificar manejo de errores AJAX
- [ ] Cargar assets con versionado (cache busting)

### 13.5 Testing
- [ ] Aumentar cobertura de pruebas unitarias (>80%)
- [ ] Automatizar pruebas de seguridad en CI
- [ ] Implementar tests de carga/estrés para endpoints críticos

### 13.6 DevOps
- [ ] Automatizar CI/CD pipeline completo
- [ ] Implementar Docker Compose para entornos
- [ ] Agregar health checks y readiness probes
- [ ] Implementar feature flags para despliegues graduales

### 13.7 Código
- [ ] Eliminar código deprecated (`_deprecated/`)
- [ ] Estandarizar respuestas API (códigos HTTP, formato)
- [ ] Implementar DTOs para transferencia de datos
- [ ] Agregar type hints estrictos (`declare(strict_types=1)`)
- [ ] Documentar PHPDoc completo en todas las clases

---

## 14. MÉTRICAS Y KPIs DEL SISTEMA

| Métrica | Descripción |
|---------|-------------|
| Tiempo de carga promedio | < 2s (p95) |
| Usuarios concurrentes | 50+ |
| Dispositivos biométricos | 10+ simultáneos |
| Registros de asistencia/día | 5000+ |
| Tamaño de BD estimado | < 5GB |
| Uptime | 99.9% |
| Tiempo de respuesta API | < 500ms (p95) |
| Cobertura de pruebas | > 70% |

---

## 15. DIAGRAMA DE RELACIONES ESENCIALES

```
empleados 1──N asistencia
empleados 1──N retardos
empleados 1──N justificaciones
empleados 1──N comisiones
empleados 1──N ausencias
empleados 1──N sanciones
empleados 1──N horarios_empleados
empleados 1──N dias_economicos
empleados 1──N vacaciones
empleados 1──N validaciones_jefe (como subordinado)
empleados 1──N notificaciones_licencias
empleados 1──N empleados_ciclos

usuarios 1──1 empleados (opcional)
usuarios 1──N menu_config
usuarios 1──N validaciones_jefe (como aprobador)

horarios_laborales 1──N horarios_empleados
horarios_laborales 1──N bloques_ciclo

ciclos_laborales 1──N bloques_ciclo
ciclos_laborales 1──N empleados_ciclos

direcciones 1──N subdirecciones
subdirecciones 1──N departamentos

dispositivos_biometricos 1──N logs_dispositivos

tipos_justificacion 1──N retardos (tipo_justificacion_id)
tipos_justificacion 1──N justificaciones
```

---

## 16. LICENCIA Y PROPIEDAD

- **Desarrollador**: Edgar Phye Parga
- **Organización**: Secretaría de Educación Pública (SEP)
- **Propósito**: Control de asistencia del personal
