# dashboard-principal

## Purpose

Proveer la página de inicio del sistema con métricas visuales en tiempo real (KPIs, gráficos Chart.js, tablas de incidencias), un acceso rápido a funciones clave, preferencias por usuario y una página institucional de bienvenida para usuarios no autenticados.

### Requirement: Dashboard con KPIs y gráficos

El sistema SHALL mostrar en `/dashboard` un panel con tarjetas KPI, gráficos Chart.js y tablas que reflejen el estado actual de la organización.

#### Scenario: Carga de dashboard

- **WHEN** se navega a `/dashboard`
- **THEN** el sistema renderiza la vista con KPIs de empleados, asistencia del día, retardos/ausencias del mes, comisiones, días económicos, licencias y vacaciones

#### Scenario: Gráficos de distribución

- **WHEN** se carga el dashboard
- **THEN** el sistema muestra gráficos doughnut/pie/bar de: distribución por área, estatus, género, turno, antigüedad, tipo de empleado

#### Scenario: Gráficos de incidencias

- **WHEN** se carga el dashboard
- **THEN** el sistema muestra gráficos de retardos por tipo, por validación, por día de la semana, ausencias por tipo

#### Scenario: Gráficos de permisos y sanciones

- **WHEN** se carga el dashboard
- **THEN** el sistema muestra gráficos de comisiones, días económicos, licencias, vacaciones y sanciones por estatus/tipo

#### Scenario: Tendencia histórica

- **WHEN** se carga el dashboard
- **THEN** el sistema muestra gráfico de línea con asistencia mensual de los últimos 12 meses

#### Scenario: Top 5 empleados

- **WHEN** se carga el dashboard
- **THEN** el sistema muestra tabla con los 5 empleados con más retardos y gráfico doughnut de distribución de incidencias

### Requirement: Página de bienvenida institucional

El sistema SHALL mostrar en `/` y `/inicio` una página institucional con misión, objetivos, tarjetas de funcionalidades, cronograma de uso y datos de contacto.

#### Scenario: Landing page

- **WHEN** un visitante accede a `/`
- **THEN** el sistema renderiza la vista institucional sin métricas

### Requirement: Preferencias del dashboard por usuario

El sistema SHALL permitir guardar y recuperar preferencias de visualización del dashboard por usuario (secciones visibles, orden, período por defecto).

#### Scenario: Guardar preferencias

- **WHEN** se envía POST a `/dashboard/preferences` con datos de preferencias
- **THEN** el sistema guarda las preferencias en la tabla `dashboard_preferences` para el usuario autenticado

#### Scenario: Recuperar preferencias

- **WHEN** se carga `/dashboard`
- **THEN** el sistema aplica las preferencias guardadas del usuario

### Requirement: Estadísticas en tiempo real (API)

El sistema SHALL proveer un endpoint JSON con estadísticas actualizadas para actualización asíncrona del dashboard.

#### Scenario: Consulta de estadísticas en tiempo real

- **WHEN** se envía GET a `/dashboard/real-time-stats` con header `X-Requested-With: XMLHttpRequest`
- **THEN** el sistema retorna JSON con KPIs actualizados del momento

### Requirement: Resumen de alertas activas

El sistema SHALL mostrar en `/dashboard` un resumen de alertas activas (sanciones recientes, validaciones pendientes, retardos críticos).

#### Scenario: Alertas visibles

- **WHEN** se carga el dashboard
- **THEN** el sistema muestra alertas activas con nivel de prioridad y enlace a la sección correspondiente
