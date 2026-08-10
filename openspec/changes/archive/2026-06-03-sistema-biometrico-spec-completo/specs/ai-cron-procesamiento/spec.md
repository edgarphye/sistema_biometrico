## ADDED Requirements

### Requirement: Procesamiento programado
El sistema SHALL ejecutar análisis automáticos programados vía AICronController::ejecutarAnalisis() para procesar incidencias en lote.

#### Scenario: Ejecución nocturna
- **WHEN** se ejecuta /ai-cron/ejecutar
- **THEN** el sistema procesa todas las incidencias pendientes del período

### Requirement: Resumen diario
El sistema SHALL generar resumen diario de incidencias procesadas vía AICronController::resumendia().

#### Scenario: Resumen generado
- **WHEN** se ejecuta /ai-cron/resumen
- **THEN** el sistema genera resumen con incidencias del día, clasificaciones y acciones tomadas

### Requirement: Integración con eventos
El sistema SHALL integrar eventos en tiempo real via AIIntegracionEventos::eventoRetardoCreado() para procesamiento inmediato de retardos.

#### Scenario: Evento de retardo
- **WHEN** se crea un nuevo retardo
- **THEN** el sistema ejecuta el flujo de evaluación del agente IA para ese retardo
