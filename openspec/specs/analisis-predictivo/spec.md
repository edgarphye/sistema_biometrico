# analisis-predictivo

## Purpose
Predecir riesgos de asistencia mediante modelos de regresión, promedios móviles y bosques aleatorios, generando alertas tempranas y análisis comparativos por áreas y empleados.


### Requirement: Dashboard predictivo
El sistema SHALL mostrar dashboard con predicciones de riesgos, alertas tempranas, análisis de patrones por áreas y empleados.

#### Scenario: Dashboard con predicciones
- **WHEN** se accede a /analisis-predictivo/dashboard
- **THEN** el sistema muestra indicadores de riesgo por empleado y área

### Requirement: Modelos de predicción
El sistema SHALL soportar modelos: regresion, promedio_movil, bosques aleatorios para predecir comportamiento de asistencia.

#### Scenario: Predicción de riesgo
- **WHEN** se ejecuta análisis predictivo para un empleado
- **THEN** el sistema calcula nivel de riesgo (bajo, medio, alto, crítico)

### Requirement: Alertas tempranas
El sistema SHALL generar alertas tempranas cuando se detecten patrones anómalos.

#### Scenario: Alerta por patrón anómalo
- **WHEN** un empleado muestra patrón de retardos creciente
- **THEN** el sistema genera alerta en alertas_predictivas

### Requirement: Análisis por áreas
El sistema SHALL analizar métricas de asistencia agrupadas por área/departamento.

#### Scenario: Comparativa por área
- **WHEN** se consulta análisis por área
- **THEN** el sistema muestra métricas comparativas de asistencia entre áreas

### Requirement: Historial de pronósticos
El sistema SHALL mantener historial de pronósticos para comparar precisión vs. resultados reales.

#### Scenario: Precisión de pronóstico
- **WHEN** se comparan pronósticos anteriores con datos reales
- **THEN** el sistema muestra métricas de precisión del modelo

### Sub-componentes relacionados
- **deteccion-anomalias** — Modelo `AnomaliaDetectada` (100 lines) para registro de anomalías detectadas en patrones de asistencia, con tipo, descripción, evaluación y estado (falsa alarma). Integrado con el análisis predictivo como fuente de datos para alertas tempranas
- **ia-visualizacion-redes-neuronales** — Rutas `/ai/redes-neuronales`, `/ai/neuronas-red`, `/ai/detalle-neurona/{id}` para visualización interactiva de redes neuronales en el dashboard de análisis predictivo
