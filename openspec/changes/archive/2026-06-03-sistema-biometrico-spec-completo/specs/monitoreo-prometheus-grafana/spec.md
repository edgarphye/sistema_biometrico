## ADDED Requirements

### Requirement: Monitoreo con Prometheus
El sistema SHALL exponer métricas para Prometheus scrape (cada 15s) en /metrics con métricas de aplicación y negocio (cada 60s) en /api/metrics/business.

#### Scenario: Scrape de métricas
- **WHEN** Prometheus scrapea /metrics
- **THEN** el sistema retorna métricas en formato Prometheus

### Requirement: Dashboards Grafana
El sistema SHALL proveer dashboard Grafana con paneles de estado del sistema, métricas de BD, caché, aplicación, alertas.

#### Scenario: Dashboard cargado
- **WHEN** se accede a Grafana
- **THEN** muestra paneles con métricas en tiempo real

### Requirement: Alertas configurables
El sistema SHALL definir alertas en Prometheus para: instancia caída, alta memoria/CPU, BD lenta, caché baja, errores HTTP, patrón anómalo de asistencia, muchos intentos fallidos.

#### Scenario: Alerta de instancia caída
- **WHEN** el servidor deja de responder por 1 minuto
- **THEN** Prometheus dispara alerta crítica

### Requirement: Setup automatizado
El sistema SHALL proveer script de instalación de monitoreo (setup-monitoring.sh) que instala Prometheus, Grafana, exporters y configura dashboards.

#### Scenario: Instalación de monitoreo
- **WHEN** se ejecuta setup-monitoring.sh
- **THEN** instala y configura toda la infraestructura de monitoreo
