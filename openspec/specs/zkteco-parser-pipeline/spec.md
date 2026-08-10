# zkteco-parser-pipeline

## Purpose

Procesar archivos .dat de dispositivos ZKTeco mediante un pipeline de detección de formato, mapeo dinámico de columnas, parsing universal e inserción inteligente de registros en la tabla de asistencia, con soporte para múltiples formatos de archivo.

### Requirement: Detección de formato

El sistema SHALL detectar automáticamente el formato del archivo .dat mediante `ZKTecoFormatDetector`, analizando encabezados, estructura de columnas y patrones de datos.

#### Scenario: Formato detectado

- **WHEN** se sube un archivo .dat al sistema
- **THEN** `ZKTecoFormatDetector` analiza y retorna el formato detectado

### Requirement: Mapeo dinámico de columnas

El sistema SHALL mapear dinámicamente las columnas del archivo .dat a los campos del sistema mediante `ZKTecoMapper` y `ZKTecoMappingManager`, usando configuración por formato.

#### Scenario: Mapeo de columnas exitoso

- **WHEN** se procesa un archivo .dat con columnas conocidas
- **THEN** el sistema mapea cada columna al campo correspondiente en la BD

#### Scenario: Columna no reconocida

- **WHEN** se encuentra una columna no mapeada
- **THEN** el sistema la ignora y continúa el procesamiento

### Requirement: Parsing universal

El sistema SHALL parsear archivos .dat de múltiples formatos mediante `ZKTecoUniversalParser`, extrayendo registros de asistencia con empleado_id, fecha, hora_entrada, hora_salida, dispositivo_origen.

#### Scenario: Parseo exitoso

- **WHEN** se ejecuta el parser universal
- **THEN** extrae todos los registros válidos del archivo .dat

### Requirement: Inserción inteligente

El sistema SHALL insertar registros en la tabla `asistencia` mediante `ZKTecoAsistenciaInserter`, evitando duplicados y actualizando registros existentes cuando corresponda.

#### Scenario: Inserción sin duplicados

- **WHEN** se inserta un registro que ya existe para el mismo empleado+fecha
- **THEN** el sistema actualiza el registro existente en lugar de duplicar

### Requirement: Procesamiento de logs

El sistema SHALL registrar el procesamiento de cada archivo .dat en `logs_dispositivo_zk` con resultado, registros procesados, errores y tiempo de procesamiento.

#### Scenario: Log de procesamiento

- **WHEN** se procesa un archivo .dat
- **THEN** el sistema crea un registro en `ZKTecoLogProcessor` con el resultado del procesamiento
