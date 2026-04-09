<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== ACTUALIZANDO TABLA RETARDOS CON CAMPOS DE ASISTENCIA DIRECTOS ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Agregar hora_entrada a retardos
    echo "Agregando campos de asistencia directa a tabla retardos...\n";
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN hora_entrada TIME NULL AFTER fecha";
        $pdo->exec($sql);
        echo "✅ Campo hora_entrada agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo hora_entrada ya existe en retardos\n";
    }
    
    // 2. Agregar hora_salida a retardos
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN hora_salida TIME NULL AFTER hora_entrada";
        $pdo->exec($sql);
        echo "✅ Campo hora_salida agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo hora_salida ya existe en retardos\n";
    }
    
    // 3. Agregar fecha_asistencia (día específico de la asistencia)
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN fecha_asistencia DATE NULL AFTER hora_salida";
        $pdo->exec($sql);
        echo "✅ Campo fecha_asistencia agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo fecha_asistencia ya existe en retardos\n";
    }
    
    // 4. Agregar día_semana para mejor análisis
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN dia_semana ENUM('lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo') NULL AFTER fecha_asistencia";
        $pdo->exec($sql);
        echo "✅ Campo dia_semana agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo dia_semana ya existe en retardos\n";
    }
    
    // 5. Agregar tipo_registro para diferenciar entrada/salida
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN tipo_registro ENUM('entrada', 'salida', 'ambos') DEFAULT 'entrada' AFTER dia_semana";
        $pdo->exec($sql);
        echo "✅ Campo tipo_registro agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo tipo_registro ya existe en retardos\n";
    }
    
    // 6. Agregar campo identificador único del día para manejar múltiples registros
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN unique_dia_empleado VARCHAR(50) NULL AFTER tipo_registro";
        $pdo->exec($sql);
        echo "✅ Campo unique_dia_empleado agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo unique_dia_empleado ya existe en retardos\n";
    }
    
    // 7. Actualizar datos existentes desde la tabla asistencia
    echo "Actualizando datos existentes desde tabla asistencia...\n";
    
    $updateSql = "UPDATE retardos r 
                  INNER JOIN asistencia a ON r.empleado_id = a.empleado_id AND r.fecha = a.fecha
                  SET r.hora_entrada = a.hora_entrada,
                      r.hora_salida = a.hora_salida,
                      r.fecha_asistencia = a.fecha,
                      r.dia_semana = CASE 
                          WHEN DAYOFWEEK(a.fecha) = 1 THEN 'domingo'
                          WHEN DAYOFWEEK(a.fecha) = 2 THEN 'lunes'
                          WHEN DAYOFWEEK(a.fecha) = 3 THEN 'martes'
                          WHEN DAYOFWEEK(a.fecha) = 4 THEN 'miércoles'
                          WHEN DAYOFWEEK(a.fecha) = 5 THEN 'jueves'
                          WHEN DAYOFWEEK(a.fecha) = 6 THEN 'viernes'
                          WHEN DAYOFWEEK(a.fecha) = 7 THEN 'sábado'
                      END,
                      r.tipo_registro = CASE 
                          WHEN a.hora_entrada IS NOT NULL AND a.hora_salida IS NOT NULL THEN 'ambos'
                          WHEN a.hora_entrada IS NOT NULL THEN 'entrada'
                          WHEN a.hora_salida IS NOT NULL THEN 'salida'
                      END,
                      r.unique_dia_empleado = CONCAT(r.empleado_id, '_', r.fecha)";
    
    $resultado = $pdo->exec($updateSql);
    echo "✅ " . $resultado . " registros actualizados con datos de asistencia\n";
    
    // 8. Crear índices para mejor rendimiento
    echo "Creando índices para rendimiento...\n";
    try {
        $sql = "CREATE INDEX idx_retardos_dia_empleado ON retardos (unique_dia_empleado)";
        $pdo->exec($sql);
        echo "✅ Índice idx_retardos_dia_empleado creado\n";
    } catch (Exception $e) {
        echo "ℹ️  Índice idx_retardos_dia_empleado ya existe\n";
    }
    
    try {
        $sql = "CREATE INDEX idx_retardos_fecha_empleado ON retardos (fecha, empleado_id)";
        $pdo->exec($sql);
        echo "✅ Índice idx_retardos_fecha_empleado creado\n";
    } catch (Exception $e) {
        echo "ℹ️  Índice idx_retardos_fecha_empleado ya existe\n";
    }
    
    try {
        $sql = "CREATE INDEX idx_retardos_tipo_estado ON retardos (tipo_retraso, estado_validacion)";
        $pdo->exec($sql);
        echo "✅ Índice idx_retardos_tipo_estado creado\n";
    } catch (Exception $e) {
        echo "ℹ️  Índice idx_retardos_tipo_estado ya existe\n";
    }
    
    // 9. Actualizar vista consolidada para incluir nuevos campos
    echo "Actualizando vista consolidada...\n";
    
    $sql = "CREATE OR REPLACE VIEW vista_validacion_completa AS
        SELECT 
            -- Información base del empleado
            e.id as empleado_id,
            CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
            e.area as empleado_area,
            e.jerarquia as empleado_jerarquia,
            e.jefe_directo_id,
            u.nombre_completo as jefe_nombre,
            
            -- Información principal del registro
            r.id as incidencia_id,
            r.fecha as fecha_incidencia,
            r.fecha_asistencia,
            r.hora_entrada,
            r.hora_salida,
            r.dia_semana,
            r.tipo_registro,
            r.minutos_retardo,
            r.hora_registro,
            r.unique_dia_empleado,
            
            -- Clasificación
            r.tipo_retraso,
            r.tipo_asistencia,
            r.categoria_principal,
            
            -- Estado de validación
            r.justificado,
            r.motivo_justificacion,
            r.motivo_detalle,
            r.requiere_validacion_jefe,
            r.evidencia_adjunta,
            r.estado_validacion,
            r.aprobado_por,
            r.fecha_aprobacion,
            
            -- Descripción consolidada
            CASE 
                WHEN r.categoria_principal = 'retardo' THEN 
                    CONCAT('Retardo ', r.tipo_retraso, ': ', r.minutos_retardo, ' minutos - ', 
                           CASE 
                               WHEN r.tipo_registro = 'entrada' THEN 'Entrada tarde'
                               WHEN r.tipo_registro = 'salida' THEN 'Salida temprana'
                               WHEN r.tipo_registro = 'ambos' THEN 'Entrada y salida con incidencias'
                               ELSE 'Incidencia horaria'
                           END,
                           ' Día: ', r.dia_semana)
                WHEN r.categoria_principal = 'comision' THEN 
                    CONCAT('Comisión ', r.tipo_registro, ': ', COALESCE(r.motivo_detalle, 'Sin especificar'))
                WHEN r.categoria_principal = 'dia_economico' THEN 
                    CONCAT('Día económico: ', COALESCE(r.motivo_detalle, 'Sin especificar'))
                WHEN r.categoria_principal = 'ausencia' THEN 
                    CONCAT('Ausencia: ', COALESCE(r.motivo_detalle, 'Sin especificar'))
                ELSE 'Asistencia normal'
            END as descripcion_incidencia,
            
            -- Detalles horarios
            CONCAT(r.hora_entrada, ' - ', COALESCE(r.hora_salida, 'N/A')) as horario_dia,
            
            -- Estado para validación
            CASE 
                WHEN r.estado_validacion = 'aprobado' THEN 'aprobado'
                WHEN r.estado_validacion = 'rechazado' THEN 'rechazado'
                WHEN r.categoria_principal = 'asistencia_normal' THEN 'no_requiere'
                WHEN r.tipo_retraso = 'normal' THEN 'no_requiere'
                ELSE 'pendiente'
            END as estado_validacion_jefe,
            
            -- Prioridad de atención
            CASE 
                WHEN r.categoria_principal = 'comision' AND r.requiere_validacion_jefe = 1 THEN 1
                WHEN r.categoria_principal = 'dia_economico' THEN 2
                WHEN r.categoria_principal = 'retardo' AND r.minutos_retardo > 30 THEN 3
                WHEN r.categoria_principal = 'retardo' AND r.minutos_retardo > 15 THEN 4
                WHEN r.categoria_principal = 'ausencia' THEN 5
                ELSE 6
            END as prioridad_atencion,
            
            -- Fecha límite para validación (48 horas por defecto)
            CASE 
                WHEN r.requiere_validacion_jefe = 1 THEN DATE_ADD(r.fecha, INTERVAL 48 HOUR)
                ELSE NULL
            END as fecha_limite_validacion,
            
            -- Días transcurridos desde la incidencia
            DATEDIFF(CURDATE(), r.fecha) as dias_transcurridos,
            
            -- Estado de urgencia
            CASE 
                WHEN r.requiere_validacion_jefe = 1 AND DATEDIFF(CURDATE(), r.fecha) > 2 THEN 'urgente'
                WHEN r.requiere_validacion_jefe = 1 AND DATEDIFF(CURDATE(), r.fecha) > 1 THEN 'atencion'
                ELSE 'normal'
            END as nivel_urgencia
            
        FROM empleados e
        LEFT JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
        LEFT JOIN retardos r ON e.id = r.empleado_id
        
        -- Filtrar incidencias que requieren atención
        WHERE r.id IS NOT NULL 
        AND (r.estado_validacion = 'pendiente' OR r.requiere_validacion_jefe = 1 OR r.categoria_principal != 'asistencia_normal')
        AND e.jefe_directo_id IS NOT NULL
        
        ORDER BY r.fecha DESC, prioridad_atencion ASC";
    
    $pdo->exec($sql);
    echo "✅ Vista vista_validacion_completa actualizada con nuevos campos\n";
    
    // 10. Crear trigger para sincronizar automáticamente con asistencia
    echo "Creando trigger para sincronización automática...\n";
    
    $triggerSql = "CREATE TRIGGER sincronizar_retardos_asistencia 
                    AFTER INSERT ON asistencia 
                    FOR EACH ROW
                    BEGIN
                        -- Verificar si ya existe un registro de retardo para este empleado y fecha
                        DECLARE existe_retardo INT;
                        SELECT COUNT(*) INTO existe_retardo 
                        FROM retardos 
                        WHERE empleado_id = NEW.empleado_id AND fecha = NEW.fecha;
                        
                        IF existe_retardo = 0 THEN
                            -- Insertar nuevo registro de retardo con datos de asistencia
                            INSERT INTO retardos (
                                empleado_id, fecha, hora_entrada, hora_salida, fecha_asistencia,
                                dia_semana, tipo_registro, unique_dia_empleado,
                                categoria_principal, estado_validacion
                            ) VALUES (
                                NEW.empleado_id, NEW.fecha, NEW.hora_entrada, NEW.hora_salida, NEW.fecha,
                                CASE 
                                    WHEN DAYOFWEEK(NEW.fecha) = 1 THEN 'domingo'
                                    WHEN DAYOFWEEK(NEW.fecha) = 2 THEN 'lunes'
                                    WHEN DAYOFWEEK(NEW.fecha) = 3 THEN 'martes'
                                    WHEN DAYOFWEEK(NEW.fecha) = 4 THEN 'miércoles'
                                    WHEN DAYOFWEEK(NEW.fecha) = 5 THEN 'jueves'
                                    WHEN DAYOFWEEK(NEW.fecha) = 6 THEN 'viernes'
                                    WHEN DAYOFWEEK(NEW.fecha) = 7 THEN 'sábado'
                                END,
                                CASE 
                                    WHEN NEW.hora_entrada IS NOT NULL AND NEW.hora_salida IS NOT NULL THEN 'ambos'
                                    WHEN NEW.hora_entrada IS NOT NULL THEN 'entrada'
                                    WHEN NEW.hora_salida IS NOT NULL THEN 'salida'
                                END,
                                CONCAT(NEW.empleado_id, '_', NEW.fecha),
                                'asistencia_normal',
                                'aprobado'
                            );
                        ELSE
                            -- Actualizar registro existente
                            UPDATE retardos SET
                                hora_entrada = NEW.hora_entrada,
                                hora_salida = NEW.hora_salida,
                                fecha_asistencia = NEW.fecha,
                                tipo_registro = CASE 
                                    WHEN NEW.hora_entrada IS NOT NULL AND NEW.hora_salida IS NOT NULL THEN 'ambos'
                                    WHEN NEW.hora_entrada IS NOT NULL THEN 'entrada'
                                    WHEN NEW.hora_salida IS NOT NULL THEN 'salida'
                                END,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE empleado_id = NEW.empleado_id AND fecha = NEW.fecha;
                        END IF;
                    END";
    
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS sincronizar_retardos_asistencia");
        $pdo->exec($triggerSql);
        echo "✅ Trigger sincronizar_retardos_asistencia creado\n";
    } catch (Exception $e) {
        echo "ℹ️  Error creando trigger: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Actualización de tabla retardos completada exitosamente\n";
    echo "📊 Nuevos campos agregados:\n";
    echo "   - hora_entrada: Hora específica de entrada\n";
    echo "   - hora_salida: Hora específica de salida\n";
    echo "   - fecha_asistencia: Fecha exacta de la asistencia\n";
    echo "   - dia_semana: Día de la semana para análisis\n";
    echo "   - tipo_registro: Tipo (entrada/salida/ambos)\n";
    echo "   - unique_dia_empleado: Identificador único\n";
    echo "   - Índices optimizados para rendimiento\n";
    echo "   - Vista actualizada con nueva información\n";
    echo "   - Trigger automático de sincronización\n";
    
} catch (Exception $e) {
    echo "❌ Error actualizando tabla retardos: " . $e->getMessage() . "\n";
}
?>