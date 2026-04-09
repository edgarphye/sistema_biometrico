<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== ACTUALIZANDO TABLA RETARDOS PARA TODOS LOS TIPOS DE ASISTENCIA ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Renombrar campo minutos_retraso a minutos_retardo si es necesario
    echo "Verificando y actualizando nombres de campos en retardos...\n";
    try {
        $sql = "ALTER TABLE retardos CHANGE COLUMN minutos_retraso minutos_retardo INT(11) NOT NULL DEFAULT 0";
        $pdo->exec($sql);
        echo "✅ Campo minutos_retraso renombrado a minutos_retardo\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo minutos_retardo ya existe\n";
    }
    
    // 2. Agregar/modificar campos para tipos de asistencia
    try {
        $sql = "ALTER TABLE retardos MODIFY COLUMN tipo_retraso ENUM('normal', 'retardo_menor', 'retardo_medio', 'retardo_mayor', 'comision_entrada', 'comision_salida', 'comision_todo_dia', 'dia_economico', 'ausencia') DEFAULT 'normal'";
        $pdo->exec($sql);
        echo "✅ Campo tipo_retraso actualizado para todos los tipos de asistencia\n";
    } catch (Exception $e) {
        echo "ℹ️  Error actualizando tipo_retraso: " . $e->getMessage() . "\n";
    }
    
    // 3. Agregar campo tipo_asistencia específico
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN tipo_asistencia ENUM('entrada', 'salida', 'completa') DEFAULT 'entrada' AFTER tipo_retraso";
        $pdo->exec($sql);
        echo "✅ Campo tipo_asistencia agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo tipo_asistencia ya existe en retardos\n";
    }
    
    // 4. Agregar campo categoria_principal para clasificación principal
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN categoria_principal ENUM('asistencia_normal', 'retardo', 'comision', 'dia_economico', 'ausencia') DEFAULT 'asistencia_normal' AFTER tipo_asistencia";
        $pdo->exec($sql);
        echo "✅ Campo categoria_principal agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo categoria_principal ya existe en retardos\n";
    }
    
    // 5. Agregar campo hora_registro para registro exacto
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN hora_registro TIME DEFAULT NULL AFTER categoria_principal";
        $pdo->exec($sql);
        echo "✅ Campo hora_registro agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo hora_registro ya existe en retardos\n";
    }
    
    // 6. Agregar campo motivo_detalle para detalles específicos
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN motivo_detalle TEXT DEFAULT NULL AFTER hora_registro";
        $pdo->exec($sql);
        echo "✅ Campo motivo_detalle agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo motivo_detalle ya existe en retardos\n";
    }
    
    // 7. Agregar campo requiere_validacion_jefe
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN requiere_validacion_jefe BOOLEAN DEFAULT FALSE AFTER motivo_detalle";
        $pdo->exec($sql);
        echo "✅ Campo requiere_validacion_jefe agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo requiere_validacion_jefe ya existe en retardos\n";
    }
    
    // 8. Agregar campo evidencia_adjunta
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN evidencia_adjunta VARCHAR(255) DEFAULT NULL AFTER requiere_validacion_jefe";
        $pdo->exec($sql);
        echo "✅ Campo evidencia_adjunta agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo evidencia_adjunta ya existe en retardos\n";
    }
    
    // 9. Agregar campo estado_validacion
    try {
        $sql = "ALTER TABLE retardos ADD COLUMN estado_validacion ENUM('pendiente', 'aprobado', 'rechazado', 'requiere_info') DEFAULT 'pendiente' AFTER evidencia_adjunta";
        $pdo->exec($sql);
        echo "✅ Campo estado_validacion agregado a retardos\n";
    } catch (Exception $e) {
        echo "ℹ️  Campo estado_validacion ya existe en retardos\n";
    }
    
    // 10. Actualizar datos existentes para clasificar correctamente
    echo "Actualizando datos existentes...\n";
    
    // Clasificar retardos existentes según minutos
    $sql = "UPDATE retardos SET 
                categoria_principal = 'retardo',
                requiere_validacion_jefe = CASE 
                    WHEN minutos_retardo > 30 THEN TRUE
                    WHEN minutos_retardo BETWEEN 16 AND 30 THEN TRUE
                    ELSE FALSE
                END,
                estado_validacion = CASE 
                    WHEN justificado = 1 THEN 'aprobado'
                    WHEN requiere_validacion_jefe = TRUE THEN 'pendiente'
                    ELSE 'aprobado'
                END
             WHERE tipo_retraso IN ('menor', 'mayor', 'normal')
             AND categoria_principal = 'asistencia_normal'";
    
    $result = $pdo->exec($sql);
    echo "✅ " . $result . " registros de retardos actualizados\n";
    
    // 11. Crear vista consolidada correcta
    echo "Creando vista consolidada corregida...\n";
    
    $sql = "CREATE OR REPLACE VIEW vista_validacion_completa AS
        SELECT 
            -- Información base del empleado
            e.id as empleado_id,
            CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
            e.area as empleado_area,
            e.jerarquia as empleado_jerarquia,
            e.jefe_directo_id,
            u.nombre_completo as jefe_nombre,
            
            -- Información de retardos/todas las incidencias
            r.id as incidencia_id,
            r.fecha as fecha_incidencia,
            r.hora_registro,
            r.minutos_retardo,
            r.tipo_retraso,
            r.tipo_asistencia,
            r.categoria_principal,
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
                    CONCAT('Retardo ', r.tipo_retraso, ': ', r.minutos_retardo, ' minutos')
                WHEN r.categoria_principal = 'comision' THEN 
                    CONCAT('Comisión ', r.tipo_retraso, ': ', COALESCE(r.motivo_detalle, 'Sin especificar'))
                WHEN r.categoria_principal = 'dia_economico' THEN 
                    CONCAT('Día económico: ', COALESCE(r.motivo_detalle, 'Sin especificar'))
                WHEN r.categoria_principal = 'ausencia' THEN 
                    CONCAT('Ausencia: ', COALESCE(r.motivo_detalle, 'Sin especificar'))
                ELSE 'Asistencia normal'
            END as descripcion_incidencia,
            
            -- Estado para validación
            CASE 
                WHEN r.estado_validacion = 'aprobado' THEN 'aprobado'
                WHEN r.estado_validacion = 'rechazado' THEN 'rechazado'
                WHEN r.categoria_principal = 'asistencia_normal' THEN 'no_requiere'
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
            END as fecha_limite_validacion
            
        FROM empleados e
        LEFT JOIN usuarios u ON e.jefe_directo_id = u.empleado_id
        LEFT JOIN retardos r ON e.id = r.empleado_id
        
        -- Filtrar incidencias que requieren atención
        WHERE r.id IS NOT NULL 
        AND (r.estado_validacion = 'pendiente' OR r.requiere_validacion_jefe = 1 OR r.categoria_principal != 'asistencia_normal')
        AND e.jefe_directo_id IS NOT NULL
        
        ORDER BY r.fecha DESC, prioridad_atencion ASC";
    
    $pdo->exec($sql);
    echo "✅ Vista vista_validacion_completa creada exitosamente\n";
    
    // 12. Crear procedimiento para clasificar automáticamente incidencias
    echo "Creando procedimiento de clasificación automática...\n";
    
    $sql = "CREATE PROCEDURE IF NOT EXISTS clasificar_incidencia(
        IN p_incidencia_id INT,
        IN p_fecha DATE,
        IN p_hora TIME,
        IN p_minutos INT,
        IN p_tipo_registro VARCHAR(20)
    )
    BEGIN
        DECLARE v_categoria VARCHAR(20);
        DECLARE v_tipo VARCHAR(20);
        DECLARE v_requiere_validacion BOOLEAN;
        
        -- Determinar categoría y tipo
        CASE p_tipo_registro
            WHEN 'retardo_menor' THEN
                SET v_categoria = 'retardo', v_tipo = 'retardo_menor', v_requiere_validacion = FALSE;
            WHEN 'retardo_medio' THEN
                SET v_categoria = 'retardo', v_tipo = 'retardo_medio', v_requiere_validacion = TRUE;
            WHEN 'retardo_mayor' THEN
                SET v_categoria = 'retardo', v_tipo = 'retardo_mayor', v_requiere_validacion = TRUE;
            WHEN 'comision_entrada' THEN
                SET v_categoria = 'comision', v_tipo = 'comision_entrada', v_requiere_validacion = TRUE;
            WHEN 'comision_salida' THEN
                SET v_categoria = 'comision', v_tipo = 'comision_salida', v_requiere_validacion = TRUE;
            WHEN 'comision_todo_dia' THEN
                SET v_categoria = 'comision', v_tipo = 'comision_todo_dia', v_requiere_validacion = TRUE;
            WHEN 'dia_economico' THEN
                SET v_categoria = 'dia_economico', v_tipo = 'dia_economico', v_requiere_validacion = TRUE;
            WHEN 'ausencia' THEN
                SET v_categoria = 'ausencia', v_tipo = 'ausencia', v_requiere_validacion = TRUE;
            ELSE
                SET v_categoria = 'asistencia_normal', v_tipo = 'normal', v_requiere_validacion = FALSE;
        END CASE;
        
        -- Actualizar registro
        UPDATE retardos SET
            categoria_principal = v_categoria,
            tipo_retraso = v_tipo,
            requiere_validacion_jefe = v_requiere_validacion,
            estado_validacion = CASE WHEN v_requiere_validacion THEN 'pendiente' ELSE 'aprobado' END
        WHERE id = p_incidencia_id;
        
    END";
    
    $pdo->exec($sql);
    echo "✅ Procedimiento clasificar_incidencia creado\n";
    
    echo "\n🎉 Actualización completa de tabla retardos exitosa\n";
    echo "📊 Nueva estructura incluye:\n";
    echo "   - Todos los tipos de asistencia (entrada, salida, comisiones, días económicos, ausencias)\n";
    echo "   - Clasificación automática por categorías\n";
    echo "   - Control de validación por jefe\n";
    echo "   - Sistema de evidencias\n";
    echo "   - Priorización automática\n";
    echo "   - Fechas límite de validación\n";
    
} catch (Exception $e) {
    echo "❌ Error actualizando estructura: " . $e->getMessage() . "\n";
}
?>