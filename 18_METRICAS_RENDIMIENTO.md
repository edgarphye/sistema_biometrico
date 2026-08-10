# 18. MÉTRICAS DE RENDIMIENTO

## 18.1 Infraestructura de Monitoreo

El sistema cuenta con una infraestructura completa de monitoreo basada en Prometheus y Grafana, desplegable mediante el script `scripts/setup-monitoring.sh`.

### Stack de Monitoreo

| Componente | Versión | Puerto | Propósito |
|------------|---------|--------|-----------|
| Prometheus | 2.45.0 | 9090 | Almacenamiento de métricas y alertas |
| Grafana | 10.1.0 | 3000 | Dashboards visuales |
| Node Exporter | 1.6.1 | 9100 | Métricas del sistema (CPU, memoria, disco) |
| MySQL Exporter | 0.15.1 | 9104 | Métricas de base de datos |
| Nginx Exporter | 1.1.0 | 9113 | Métricas del servidor web |
| PHP-FPM Exporter | - | 9253 | Métricas de PHP-FPM |
| Redis Exporter | 1.45.0 | 9121 | Métricas de caché |

### Intervalos de Recolección

| Métrica | Intervalo | Endpoint |
|---------|-----------|----------|
| Métricas de aplicación | 15s | `/metrics` |
| Métricas de infraestructura | 30s | Exporters (9100-9121) |
| Métricas de negocio | 60s | `/api/metrics/business` |

### Dashboard Grafana

Se provee un dashboard precargado (`monitoring/grafana/dashboards/sistema-biometrico-general.json`) con **19 paneles** organizados en:

- **Estado del Sistema**: Health check del servidor (stat panel, verde/rojo)
- **Usuarios Activos**: Conteo de usuarios concurrentes
- **Tasa de Error HTTP**: Porcentaje de respuestas 5xx vs total
- **Tiempo de Respuesta p95**: Percentil 95 de latencia (umbrales: verde <1s, amarillo <2s, rojo >2s)
- **Uso de CPU**: Porcentaje de utilización del procesador
- **Uso de Memoria**: Memoria RAM utilizada vs total
- **Espacio en Disco**: Almacenamiento disponible
- **Requests por Segundo**: Throughput de la aplicación
- **Conexiones MySQL**: Conexiones activas vs máximas
- **Consultas Lentas MySQL**: Tasa de slow queries
- **Usuarios Concurrentes**: Sesiones activas simultáneas
- **Registros Entrada/Salida**: Throughput de marcaciones biométricas
- **Hit Rate Redis**: Efectividad de la caché
- **Uso de Memoria Redis**: Memoria utilizada vs máxima
- **Alertas Activas**: Tabla de alertas disparadas
- **Refresh**: Cada 30s, rango por defecto: última hora

## 18.2 Métricas de Base de Datos

### Índices de Rendimiento

El sistema implementa **13 índices estratégicos** creados mediante `scripts/create_indexes.php`:

| Índice | Columnas | Impacto Esperado |
|--------|----------|------------------|
| `idx_asistencia_empleado_timestamp` | empleado_id, timestamp DESC | 70-80% más rápido |
| `idx_asistencia_dispositivo_timestamp` | dispositivo_id, timestamp DESC | Consultas por dispositivo |
| `idx_asistencia_tipo_timestamp` | tipo_biometria, timestamp DESC | Filtros por tipo biométrico |
| `idx_empleados_area_activo` | area, activo | 60-70% más rápido |
| `idx_retardos_empleado_fecha` | empleado_id, fecha DESC | 65-75% más rápido |
| `idx_comisiones_empleado_vencimiento` | empleado_id, fecha_vencimiento DESC | 60-70% más rápido |
| `idx_dispositivos_sede_activo` | sede, activo | Consultas por sede |
| `idx_ausencias_empleado_fecha` | empleado_id, fecha DESC | Historial de ausencias |
| `idx_sanciones_empleado_anio` | empleado_id, anio | Reportes anuales |
| `idx_horarios_sede_activo` | sede, activo | Asignación de horarios |
| `idx_bloques_ciclo_dia_hora` | ciclo_id, dia_semana, hora_inicio | Ciclos de trabajo |
| `idx_usuarios_rol_activo` | rol, activo | Filtros por rol |
| `idx_asistencia_empleado_ano_mes` | empleado_id, YEAR, MONTH | Reportes mensuales |

### Validación de Índices

El script `scripts/validate_indexes.php` ejecuta benchmarks comparativos:

| Query de Referencia | Sin Índice | Con Índice | Mejora |
|---------------------|------------|------------|--------|
| Asistencia por empleado | ~350ms | ~70ms | ~80% |
| Empleados por área | ~120ms | ~40ms | ~67% |
| Retardos por empleado | ~200ms | ~60ms | ~70% |

**Escala de rendimiento**: <50ms = EXCELENTE, <100ms = BUENO, ≥100ms = MEJORABLE.

### Optimización MySQL

El helper `helpers/PerformanceOptimizer.php` aplica configuraciones óptimas:

| Parámetro | Valor | Efecto |
|-----------|-------|--------|
| `query_cache_size` | 256MB | Caché de consultas repetidas |
| `innodb_buffer_pool_size` | 1GB | Caché de datos en memoria |
| `innodb_log_file_size` | 256MB | Reducción de I/O en transacciones |
| `innodb_flush_log_at_trx_commit` | 2 | Balance rendimiento-durabilidad |

### Mantenimiento Programado

| Tarea | Comando | Frecuencia |
|-------|---------|------------|
| Optimizar tablas | `OPTIMIZE TABLE` | Semanal |
| Actualizar estadísticas | `ANALYZE TABLE` (10 tablas) | Semanal |
| Limpieza de logs antiguos | Rotación >30 días | Mensual |

## 18.3 Métricas de la Aplicación

### Tiempo de Respuesta por Componente

El sistema rastrea tiempos de ejecución mediante `microtime(true)` en **82 ubicaciones** del código:

| Componente | Operación | Tiempo Típico |
|------------|-----------|---------------|
| `controllers/AsistenciaController.php` | Procesamiento de asistencia | <500ms |
| `controllers/ConfiguracionController.php` | Carga ZKTeco (4 fases) | <30s (batch) |
| `models/ZKTecoAsistenciaInserter.php` | Inserción batch (timeout 300s) | <60s (1000 registros) |
| `scripts/create_indexes.php` | Creación de índices | <5s por índice |
| `scripts/validate_indexes.php` | Benchmark queries | <100ms por query |
| `helpers/PerformanceOptimizer.php` | Request completo | <2s (p95) |

### Uso de Memoria

Monitoreo mediante `memory_get_usage(true)` y `memory_get_peak_usage(true)` en **7 ubicaciones**:

| Componente | Memoria Típica | Pico |
|------------|----------------|------|
| `helpers/PerformanceOptimizer.php` | ~8 MB | ~16 MB |
| `maintenance.php` | ~6 MB | ~12 MB |
| `models/biometric/Logger/BiometricLogger.php` | ~4 MB | ~8 MB |
| Carga de reportes Excel | ~32 MB | ~64 MB |
| Sincronización biométrica batch | ~64 MB | ~128 MB |

### Configuración de Límites

| Parámetro | Valor | Afecta |
|-----------|-------|--------|
| `max_execution_time` | 300s | Procesos batch |
| `memory_limit` | 256M | Aplicación general |
| `memory_limit` (biométrico) | 512M | Sincronización dispositivos |
| `upload_max_filesize` | 20M | Subida de archivos |
| `post_max_size` | 20M | Envío de formularios |

### Configuración de Logging

| Parámetro | Valor |
|-----------|-------|
| `LOG_LEVEL` | INFO (DEBUG, INFO, WARNING, ERROR) |
| `LOG_MAX_SIZE` | 10 MB |
| `LOG_ROTATE_COUNT` | 5 rotaciones |
| Formato | JSON estructurado (timestamp, level, message, context, memory_usage, peak_memory) |
| Archivos | `logs/biometric_system.log`, `logs/php_errors_YYYY-MM-DD.log`, `logs/zkteco_process.log` |

## 18.4 Métricas de Caché

### Sistema de Caché Centralizado (CacheManager)

Implementado en `helpers/CacheManager.php` con dos backends:

| Característica | Redis (Primario) | Archivo (Fallback) |
|----------------|------------------|---------------------|
| Conexión | 127.0.0.1:6379, timeout 2s | Directorio `cache/` |
| Formato | JSON serializado | PHP serializado, extensión `.cache` |
| TTL por defecto | 3600s (1 hora) | 3600s (1 hora) |
| Estrategia | LRU cuando excede max_size | LRU cuando excede max_size |
| Degradación | Graceful a file cache | N/A |

### Tipos de Caché

| Tipo | Clave/Ejemplo | TTL | Impacto |
|------|---------------|-----|---------|
| Consultas SQL | MD5(query + params) | 10-30 min | Reduce carga BD 70% |
| Datos de empleados paginados | `empleados_pag_{page}` | 10 min | Carga instantánea |
| Asistencia optimizada | `asistencia_opt_{params}` | 30 min | Reportes rápidos |
| Templates biométricos | `biometric_template_{id}` | 3600s | Sincronización eficiente |
| Archivos estáticos (navegador) | CSS, JS, imágenes | 1 año | Sin recarga de assets |

### Rendimiento de Caché (Benchmark 100 ops)

| Operación | Tiempo Promedio | Rating |
|-----------|-----------------|--------|
| Set (Redis) | <0.5ms | Excelente |
| Get (Redis) | <0.3ms | Excelente |
| Set (File) | <2ms | Excelente |
| Get (File) | <1ms | Excelente |
| Hit Rate objetivo | >80% | Excelente |

### Caché en Memoria (Property Caching)

Varios modelos implementan caché local en arreglos de instancia:

| Clase | Cache | Duración |
|-------|-------|----------|
| `ZKTecoMappingManager.php` | `$columnasEmpleadoCache` | Vida de la petición |
| `ZKTecoLogProcessor.php` | `$columnasEmpleadoCache` | Vida de la petición |
| `TipoJustificacion.php` | `$columnasCache` | Vida de la petición |
| `DeviceManager.php` | `$deviceStatus` array | 30s (status) |

## 18.5 Métricas de Dispositivos Biométricos

### Rendimiento de Sincronización

| Operación | Capacidad | Timeout |
|-----------|-----------|---------|
| Procesamiento batch | 1,000 registros/minuto | 300s |
| Conexión a dispositivo | <5s | 30s (`BIOMETRIC_TIMEOUT`) |
| Verificación biométrica | <2s por evento | - |
| Calidad de verificación mínima | 75/100 | (`BIOMETRIC_QUALITY_THRESHOLD`) |

### Tiempo de Respuesta por Dispositivo

La tabla `logs_dispositivos` almacena el campo `tiempo_respuesta DECIMAL(8,3)` en milisegundos para cada operación, permitiendo auditoría de rendimiento por dispositivo y por operación.

## 18.6 Métricas de Negocio (KPIs)

### Dashboard Principal

El dashboard en `/dashboard` expone los siguientes KPIs en tiempo real (vía endpoint `/dashboard/real-time-stats`):

| KPI | Descripción | Fuente |
|-----|-------------|--------|
| Total empleados activos | Conteo de empleados con activo=1 | `empleados` |
| Asistencia del día | Marcaciones de entrada/salida hoy | `asistencia` |
| Retardos del mes | Retardos en período mensual | `retardos` |
| Ausencias del mes | Faltas sin justificar | `ausencias` |
| Comisiones activas | Comisiones en curso | `comisiones` |
| Días económicos | Solicitudes activas | `dias_economicos` |
| Licencias médicas | Licencias activas | `licencias_medicas` |
| Vacaciones | Solicitudes activas | `vacaciones` |
| Top 5 empleados | Empleados con más retardos | `retardos` + `empleados` |
| Alertas activas | Sanciones recientes, validaciones pendientes | Múltiples tablas |

### Gráficos de Distribución

| Tipo de Gráfico | Distribución |
|-----------------|--------------|
| Doughnut/Pie | Área, estatus, género, turno, antigüedad, tipo de empleado |
| Bar | Retardos por tipo, por día de la semana, ausencias por tipo |
| Línea | Asistencia mensual (últimos 12 meses) |
| Doughnut | Distribución de incidencias (retardos, ausencias, justificaciones) |

### Métricas Predictivas (IA)

El sistema de IA genera métricas de riesgo en tiempo real mediante `services/AIActualizadorService.php`:

**Fórmula de Riesgo:**
```
Riesgo 30 días = (retardos × 5) + (minutos_retardo / 10) + (justificaciones × 3) + (faltas × 20)
Riesgo 90 días = (retardos_90d × 2) + (faltas_90d × 10)
Riesgo = min(100, Riesgo30d × 0.7 + Riesgo90d × 0.3)
```

**Niveles de Riesgo:**

| Nivel | Rango | Acción |
|-------|-------|--------|
| Bajo | 0-25 | Monitoreo normal |
| Medio | 26-50 | Alerta preventiva |
| Alto | 51-75 | Intervención requerida |
| Crítico | 76-100 | Acción disciplinaria inmediata |

**Alertas Automáticas:**

| Condición | Nivel | Acción |
|-----------|-------|--------|
| 3+ retardos en 7 días | Medio | Notificación al jefe |
| 5+ retardos en 7 días | Alto | Revisión RH |
| 2+ faltas en 7 días | Crítico | Acción disciplinaria |

### Métricas de Procesamiento del Agente IA

El `AgenteIAService` registra en `bitacora_agente`:

- Incidencias procesadas por día
- Reglas de negocio aplicadas
- Decisiones tomadas (clasificación)
- Recomendaciones generadas
- Anomalías detectadas

## 18.7 Alertas Configuradas

### Sistema de Alertas Prometheus (28 reglas)

| Categoría | Reglas | Umbral |
|-----------|--------|--------|
| **System Health** (4) | InstanceDown, HighMemoryUsage, HighCPUUsage, DiskSpaceLow | Mem >90%, CPU >80%, Disco <10% |
| **Database** (3) | MySQLDown, MySQLSlowQueries, MySQLConnectionsHigh | Slow >0.1/s, Connections >80% |
| **Web Server** (3) | NginxDown, NginxHighResponseTime, NginxHighErrorRate | p95 >2s, Error >5% |
| **PHP-FPM** (3) | PHPFPMDown, HighProcessCount, HighMemoryUsage | Procesos >80%, Mem >80% |
| **Redis Cache** (3) | RedisDown, HighMemoryUsage, LowHitRate | Mem >90%, Hit <80% |
| **Application** (3) | HighResponseTime, HighErrorRate, LowActiveUsers | p95 >3s, Error >5%, Users <10 |
| **Business** (3) | NoEmployeeCheckins, AnomalousAttendance, FailedLoginsHigh | Cambio >50%, Logins >5/min |
| **Security** (2) | SuspiciousActivity, UnusualAccessPattern | Cambio >200% |

### Niveles de Severidad

| Severidad | Tiempo de Respuesta | Canales de Notificación |
|-----------|---------------------|--------------------------|
| Crítica | <15 min | Email + SMS + Dashboard |
| Alta | <30 min | Email + Dashboard |
| Media | <2 horas | Email + Dashboard |
| Baja | <24 horas | Dashboard |

## 18.8 Pruebas de Rendimiento

### Load Testing (Apache Bench)

Script `scripts/performance-test.sh` con pruebas progresivas:

| Tipo de Prueba | Configuración | Métricas |
|----------------|---------------|----------|
| Page Load | 1,000-2,000 requests, 10-20 concurrentes | RPS, tiempo/request, fallos |
| API Performance | Endpoints REST | Throughput, latencia |
| Carga Progresiva | Concurrencia: 1, 5, 10, 25, 50, 100 | Punto de quiebre |
| Stress Test | 120 segundos a ~100 RPS | Comportamiento bajo carga |
| Endurance Test | Carga sostenida por minutos | Degradación en el tiempo |
| Static Assets | CSS, JS, imágenes | Tiempo de carga CDN/local |

**Umbrales de Rendimiento:**

| Métrica | Bueno | Advertencia | Crítico |
|---------|-------|-------------|---------|
| RPS (requests/sec) | >500 | 100-500 | <100 |
| Tiempo de respuesta (p95) | <1s | 1-3s | >3s |
| Tasa de fallos | <1% | 1-5% | >5% |

### Pruebas E2E (Cypress)

Las pruebas E2E en `tests/E2E/sistema-biometrico.cy.js` incluyen:

| Verificación | Umbral |
|-------------|--------|
| Page Load Time (loadEventEnd - navigationStart) | <3000ms |
| First Contentful Paint (FCP) | <1500ms |
| Manejo de grandes datasets | Latencia simulada 1000ms |

### Pruebas Unitarias de Rendimiento

Suite `tests/Performance/` (PHPUnit, grupo `@group performance`) con:

- `DatabaseIntegrationTest::testIndexesAndPerformance()` - 100 registros de prueba, verifica query <100ms
- `scripts/test_cache.php` - 100 operaciones caché, benchmark ops/sec

## 18.9 Optimizaciones Implementadas

### Resumen de Optimizaciones

| Área | Optimización | Impacto |
|------|-------------|---------|
| **Base de Datos** | 13 índices compuestos | 60-80% mejora en consultas |
| **Base de Datos** | Query cache (256MB) | Consultas repetidas instantáneas |
| **Base de Datos** | InnoDB buffer pool (1GB) | Datos frecuentes en memoria |
| **Base de Datos** | OPTIMIZE/ANALYZE semanal | Fragmentación reducida |
| **Caché** | Redis + file fallback | Latencia de datos reducida |
| **Caché** | TTL inteligente (10min-1hora) | Balance frescura-rendimiento |
| **Caché** | Browser caching (1 año) | Sin recarga de assets |
| **Compresión** | gzip/deflate (HTML/CSS/JS/JSON) | 70-80% reducción de ancho de banda |
| **Assets** | Migración a locales (sin CDN) | Sin DNS+SSL a CDNs |
| **PHP** | Prepared statements nativos | Consultas optimizadas |
| **PHP** | Output buffering (4096) | Flush eficiente |
| **PHP** | Sesiones en BD | Escalabilidad horizontal |

### Métricas Acumuladas del Sistema

| Métrica | Valor |
|---------|-------|
| Capacidad de empleados | 5,000+ activos |
| Dispositivos biométricos simultáneos | 10+ |
| Throughput de inserción | 1,000 registros/minuto |
| Tiempo de respuesta p95 (objetivo) | <2s |
| Disponibilidad (SLA) | 99.5% |
| RTO (tiempo de recuperación) | 4 horas |
| RPO (punto de recuperación) | 1 hora |
| Retención de logs | 30 días |

## 18.10 Monitoreo y Alertas del Sistema de IA

### Tablas de Métricas en Tiempo Real

El `AIActualizadorService` gestiona 4 tablas de métricas:

| Tabla | Propósito | Registros |
|-------|-----------|-----------|
| `ai_snapshots_diarios` | Instantáneas diarias por empleado | retardos, faltas, horas_trabajadas, indice_riesgo |
| `ai_metricas_tiempo_real` | Eventos de métricas en tiempo real | tipo_evento, valor_anterior, valor_nuevo, riesgo |
| `ai_pesos_entrenamiento` | Pesos de modelos ML | modelo, épocas, error_final, accuracy |
| `ai_alertas` | Alertas generadas | tipo, nivel_riesgo, mensaje, leida |

### Modelos de Machine Learning

El `services/MachineLearningEngine.php` implementa:

| Modelo | Propósito | Métrica de Evaluación |
|--------|-----------|----------------------|
| Regresión Lineal | Predicción de retardos | Error Absoluto Medio |
| EMA (Media Móvil) | Tendencia de asistencia | Error de pronóstico |
| Regresión Polinomial | Patrones estacionales | R² ajustado |
| LSTM (simplificado) | Series de tiempo | Precisión secuencial |
| K-Means (4 clusters) | Segmentación de empleados | Distancia intra-cluster |
| Random Forest | Clasificación de riesgo | Precisión, Recall, F1-Score |
| Naive Bayes | Probabilidad de incidencia | Matriz de confusión |
| Perceptron | Red neuronal simple | Accuracy de clasificación |

**Métricas de Evaluación de Modelos:**
- Precisión (Precision)
- Exhaustividad (Recall)
- Especificidad (Specificity)
- Puntuación F1 (F1-Score)
- Error Absoluto Medio (MAE)
- Matriz de Confusión

## 18.11 Brechas y Recomendaciones

### Brechas Identificadas

| Área | Brecha | Prioridad |
|------|--------|-----------|
| APM | Sin herramienta APM (New Relic, Datadog) | Media |
| Cache Hit Rate | `getCacheHitRatio()` retorna valor hardcodeado (75%) | Baja |
| Performance Analysis | `maintenance.php::performanceAnalysis()` es placeholder | Baja |
| Resultados Load Test | `tests/performance/results/` no existe | Media |
| Slow Query Log | Sin configuración visible de slow query log en MySQL | Alta |
| Nginx Config | Archivo `nginx.conf` no está en el proyecto | Baja |

### Recomendaciones

1. **Configurar slow query log** en MySQL para detectar consultas problemáticas en producción
2. **Implementar APM** (Application Performance Monitoring) para trazabilidad de transacciones completas
3. **Ejecutar load testing** periódico con `scripts/performance-test.sh` y almacenar resultados
4. **Reemplazar cache hit rate hardcodeado** con estadísticas reales desde el CacheManager
5. **Implementar** `performanceAnalysis()` con métricas reales del sistema
6. **Configurar umbrales dinámicos** de alertas basados en líneas base históricas

---

*Documento generado con base en análisis del código fuente, infraestructura de monitoreo, pruebas de rendimiento y especificaciones OpenSpec del Sistema de Control de Asistencia Biométrico v2.0.*
