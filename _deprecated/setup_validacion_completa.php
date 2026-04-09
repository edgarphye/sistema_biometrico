<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== ACTUALIZANDO TABLAS EXISTENTES PARA VALIDACIÓN POR JEFES ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Actualizar tabla retardos - agregar columna tipo_comision
    echo "Actualizando tabla retardos...\n";
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN tipo_comision ENUM('normal', 'comision_entrada', 'comision_salida', 'comision_todo_dia') DEFAULT 'normal' AFTER tipo";
        $pdo->exec($sql);
        echo "✅ Columna tipo_comision agregada a tabla retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna tipo_comision ya existe en tabla retardos\n";
    }
    
    // Agregar más columnas a retardos para mejor validación
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN categoria_incidencia ENUM('retardo_menor', 'retardo_medio', 'retardo_mayor', 'comision_entrada', 'comision_salida', 'comision_todo_dia') GENERATED ALWAYS AS (
                    CASE 
                        WHEN tipo_comision = 'comision_entrada' THEN 'comision_entrada'
                        WHEN tipo_comision = 'comision_salida' THEN 'comision_salida'
                        WHEN tipo_comision = 'comision_todo_dia' THEN 'comision_todo_dia'
                        WHEN minutos_retardo <= 15 THEN 'retardo_menor'
                        WHEN minutos_retardo <= 60 THEN 'retardo_medio'
                        ELSE 'retardo_mayor'
                    END
                ) STORED AFTER tipo_comision";
        
        // Para versiones antiguas de MySQL que no soportan GENERATED ALWAYS
        $sqlAlternative = "ALTER TABLE retardos ADD COLUMN categoria_incidencia VARCHAR(30) AFTER tipo_comision";
        $pdo->exec($sqlAlternative);
        
        // Actualizar datos existentes
        $updateSql = "UPDATE retardos SET categoria_incidencia = 
                        CASE 
                            WHEN tipo_comision = 'comision_entrada' THEN 'comision_entrada'
                            WHEN tipo_comision = 'comision_salida' THEN 'comision_salida'
                            WHEN tipo_comision = 'comision_todo_dia' THEN 'comision_todo_dia'
                            WHEN minutos_retardo <= 15 THEN 'retardo_menor'
                            WHEN minutos_retardo <= 60 THEN 'retardo_medio'
                            ELSE 'retardo_mayor'
                        END";
        $pdo->exec($updateSql);
        
        echo "✅ Columna categoria_incidencia agregada a tabla retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna categoria_incidencia ya existe en tabla retardos\n";
    }
    
    // 2. Actualizar tabla comisiones - agregar tipo_comision
    echo "Actualizando tabla comisiones...\n";
    try {
        $sql = "ALTER TABLE comisiones ADD COLUMN tipo_comision ENUM('entrada', 'salida', 'todo_dia') AFTER tipo";
        $pdo->exec($sql);
        echo "✅ Columna tipo_comision agregada a tabla comisiones\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna tipo_comision ya existe en tabla comisiones\n";
    }
    
    // Agregar más campos a comisiones para validación
    try {
        $sql = "ALTER TABLE comisiones ADD COLUMN requiere_evidencia BOOLEAN DEFAULT FALSE AFTER tipo_comision";
        $pdo->exec($sql);
        echo "✅ Columna requiere_evidencia agregada a tabla comisiones\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna requiere_evidencia ya existe en tabla comisiones\n";
    }
    
    try {
        $sql = "ALTER TABLE comisiones ADD COLUMN fecha_limite_validacion TIMESTAMP NULL AFTER requiere_evidencia";
        $pdo->exec($sql);
        echo "✅ Columna fecha_limite_validacion agregada a tabla comisiones\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna fecha_limite_validacion ya existe en tabla comisiones\n";
    }
    
    // 3. Actualizar tabla dias_economicos para mejor validación
    echo "Actualizando tabla dias_economicos...\n";
    try {
        $sql = "ALTER TABLE dias_economicos ADD COLUMN requiere_evidencia BOOLEAN DEFAULT TRUE AFTER motivo";
        $pdo->exec($sql);
        echo "✅ Columna requiere_evidencia agregada a tabla dias_economicos\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna requiere_evidencia ya existe en tabla dias_economicos\n";
    }
    
    try {
        $sql = "ALTER TABLE dias_economicos ADD COLUMN fecha_limite_validacion TIMESTAMP NULL AFTER requiere_evidencia";
        $pdo->exec($sql);
        echo "✅ Columna fecha_limite_validacion agregada a tabla dias_economicos\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna fecha_limite_validacion ya existe en tabla dias_economicos\n";
    }
    
    try {
        $sql = "ALTER TABLE dias_economicos ADD COLUMN categoria_ausencia ENUM('personal', 'familiar', 'medica', 'otro') DEFAULT 'personal AFTER motivo";
        $pdo->exec($sql);
        echo "✅ Columna categoria_ausencia agregada a tabla dias_economicos\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna categoria_ausencia ya existe en tabla dias_economicos\n";
    }
    
    // 4. Actualizar tabla ausencias para mejor integración
    echo "Actualizando tabla ausencias...\n";
    try {
        $sql = "ALTER TABLE ausencias ADD COLUMN requiere_evidencia BOOLEAN DEFAULT TRUE AFTER motivo";
        $pdo->exec($sql);
        echo "✅ Columna requiere_evidencia agregada a tabla ausencias\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna requiere_evidencia ya existe en tabla ausencias\n";
    }
    
    try {
        $sql = "ALTER TABLE ausencias ADD COLUMN tipo_ausencia ENUM('medica', 'personal', 'familiar', 'maternidad', 'paternidad', 'otro') DEFAULT 'personal' AFTER motivo";
        $pdo->exec($sql);
        echo "✅ Columna tipo_ausencia agregada a tabla ausencias\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna tipo_ausencia ya existe en tabla ausencias\n";
    }
    
    // 5. Actualizar tabla asistencia para incluir información de validación
    echo "Actualizando tabla asistencia...\n";
    try {
        $sql = "ALTER TABLE asistencia ADD COLUMN tipo_asistencia ENUM('normal', 'con_comision', 'con_retardo', 'con_ausencia') DEFAULT 'normal' AFTER hora_salida";
        $pdo->exec($sql);
        echo "✅ Columna tipo_asistencia agregada a tabla asistencia\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna tipo_asistencia ya existe en tabla asistencia\n";
    }
    
    try {
        $sql = "ALTER TABLE asistencia ADD COLUMN requerio_validacion BOOLEAN DEFAULT FALSE AFTER tipo_asistencia";
        $pdo->exec($sql);
        echo "✅ Columna requirio_validacion agregada a tabla asistencia\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna requirio_validacion ya existe en tabla asistencia\n";
    }
    
    try {
        $sql = "ALTER TABLE asistencia ADD COLUMN validado_por INT NULL AFTER requirio_validacion";
        $pdo->exec($sql);
        echo "✅ Columna validado_por agregada a tabla asistencia\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna validado_por ya existe en tabla asistencia\n";
    }
    
    try {
        $sql = "ALTER TABLE asistencia ADD COLUMN fecha_validacion TIMESTAMP NULL AFTER validado_por";
        $pdo->exec($sql);
        echo "✅ Columna fecha_validacion agregada a tabla asistencia\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna fecha_validacion ya existe en tabla asistencia\n";
    }
    
    // 6. Crear vista consolidada para validación del jefe
    echo "Creando vista consolidada para validación...\n";
    
    $sql = "CREATE OR REPLACE VIEW vista_validacion_jefe AS
        SELECT 
            -- Información base del empleado
            e.id as empleado_id,
            CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
            e.area as empleado_area,
            e.jerarquia as empleado_jerarquia,
            e.jefe_directo_id,
            u.nombre_completo as jefe_nombre,
            
            -- Información de retardos
            CASE 
                WHEN r.id IS NOT NULL THEN 
                    CONCAT('Retardo: ', r.minutos_retardo, ' minutos - ', 
                           CASE 
                               WHEN r.tipo_comision = 'comision_entrada' THEN 'Comisión de entrada'
                               WHEN r.tipo_comision = 'comision_salida' THEN 'Comisión de salida'
                               WHEN r.tipo_comision = 'comision_todo_dia' THEN 'Comisión todo el día'
                               ELSE r.categoria_incidencia
                           END)
            END as descripcion_incidencia,
            
            CASE WHEN r.id IS NOT NULL THEN 'retardo' END as tipo_incidencia,
            r.id as incidencia_id,
            r.fecha as fecha_incidencia,
            r.minutos_retardo,
            r.tipo_comision,
            r.categoria_incidencia,
            r.justificado,
            r.aprobado_por,
            r.fecha_aprobacion,
            
            -- Información de comisiones
            CASE 
                WHEN c.id IS NOT NULL THEN 
                    CONCAT('Comisión: ', c.concepto, ' - $', c.monto, ' - ',
                           CASE c.tipo_comision
                               WHEN 'entrada' THEN 'Comisión de entrada'
                               WHEN 'salida' THEN 'Comisión de salida'
                               WHEN 'todo_dia' THEN 'Comisión todo el día'
                           END)
            END as descripcion_comision,
            
            CASE WHEN c.id IS NOT NULL THEN 'comision' END as tipo_incidencia_comision,
            c.id as comision_id,
            c.fecha_asignacion as fecha_comision,
            c.concepto,
            c.monto,
            c.tipo_comision as tipo_comision_comision,
            c.estado as estado_comision,
            
            -- Información de días económicos
            CASE 
                WHEN de.id IS NOT NULL THEN 
                    CONCAT('Día económico: ', de.motivo, ' - ', de.categoria_ausencia)
            END as descripcion_dia_economico,
            
            CASE WHEN de.id IS NOT NULL THEN 'dia_economico' END as tipo_incidencia_dia,
            de.id as dia_economico_id,
            de.fecha as fecha_dia_economico,
            de.motivo as motivo_dia_economico,
            de.categoria_ausencia as categoria_dia,
            de.estado as estado_dia,
            
            -- Información de ausencias
            CASE 
                WHEN a.id IS NOT NULL THEN 
                    CONCAT('Ausencia: ', a.motivo, ' - ', a.tipo_ausencia)
            END as descripcion_ausencia,
            
            CASE WHEN a.id IS NOT NULL THEN 'ausencia' END as tipo_incidencia_ausencia,
            a.id as ausencia_id,
            a.fecha_inicio as fecha_inicio_ausencia,
            a.fecha_fin as fecha_fin_ausencia,
            a.motivo as motivo_ausencia,
            a.tipo_ausencia,
            a.estado as estado_ausencia,
            
            -- Información de asistencia (base)
            asis.id as asistencia_id,
            asis.fecha as fecha_asistencia,
            asis.hora_entrada,
            asis.hora_salida,
            asis.tipo_asistencia,
            asis.requirio_validacion as asistencia_requiere_validacion,
            asis.validado_por as asistencia_validado_por,
            asis.fecha_validacion as asistencia_fecha_validacion
            
        FROM empleados e
        LEFT JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
        LEFT JOIN retardos r ON e.id = r.empleado_id
        LEFT JOIN comisiones c ON e.id = c.empleado_id
        LEFT JOIN dias_economicos de ON e.id = de.empleado_id
        LEFT JOIN ausencias a ON e.id = a.empleado_id
        LEFT JOIN asistencia asis ON e.id = asis.empleado_id
        
        -- Filtrar solo incidencias que necesitan validación
        WHERE (r.justificado = 0 OR c.estado = 'solicitada' 
               OR de.estado = 'solicitado' OR a.estado = 'solicitada')
        AND e.jefe_directo_id IS NOT NULL";
    
    $pdo->exec($sql);
    echo "✅ Vista vista_validacion_jefe creada exitosamente\n";
    
    // 7. Actualizar tabla empleados para agregar más campos de validación
    echo "Actualizando tabla empleados para campos de validación...\n";
    
    try {
        $sql = "ALTER TABLE empleados ADD COLUMN fecha_limite_validacion_jefe INT DEFAULT 48 AFTER jefe_directo_id";
        $pdo->exec($sql);
        echo "✅ Columna fecha_limite_validacion_jefe agregada a tabla empleados (horas)\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna fecha_limite_validacion_jefe ya existe en tabla empleados\n";
    }
    
    try {
        $sql = "ALTER TABLE empleados ADD COLUMN auto_aprobar_retardos BOOLEAN DEFAULT FALSE AFTER fecha_limite_validacion_jefe";
        $pdo->exec($sql);
        echo "✅ Columna auto_aprobar_retardos agregada a tabla empleados\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna auto_aprobar_retardos ya existe en tabla empleados\n";
    }
    
    try {
        $sql = "ALTER TABLE empleados ADD COLUMN monto_max_comision_sin_validacion DECIMAL(10,2) DEFAULT 500.00 AFTER auto_aprobar_retardos";
        $pdo->exec($sql);
        echo "✅ Columna monto_max_comision_sin_validacion agregada a tabla empleados\n";
    } catch (Exception $e) {
        echo "ℹ️  Columna monto_max_comision_sin_validacion ya existe en tabla empleados\n";
    }
    
    echo "\n🎉 Tablas actualizadas exitosamente para validación por jefes\n";
    echo "📊 Nuevas capacidades agregadas:\n";
    echo "   - Tipos de comisión en retardos (entrada, salida, todo_día)\n";
    echo "   - Categorización automática de incidencias\n";
    echo "   - Control de evidencia obligatoria\n";
    echo "   - Fechas límite de validación\n";
    echo "   - Vista consolidada para validación\n";
    echo "   - Configuración individual por empleado\n";
    
} catch (Exception $e) {
    echo "❌ Error actualizando tablas: " . $e->getMessage() . "\n";
}
?>