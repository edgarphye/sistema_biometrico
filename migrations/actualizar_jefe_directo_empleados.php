<?php
/**
 * Script para actualizar jefe_directo_id de empleados basándose en catalogos_mandos
 * 
 * Estrategia:
 * 1. Vincular cada mando (de catalogos_mandos) con un empleado existente por nombre
 * 2. Actualizar la clave_depto de los empleados que son mandos
 * 3. Asignar jefe_directo_id a cada empleado según la clave_depto de su mando
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

echo "===========================================\n";
echo "Actualizando relaciones empleado-jefe\n";
echo "===========================================\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Paso 1: Obtener todos los mandos de catalogos_mandos
    $stmtMandos = $pdo->query("
        SELECT cm.id as mando_id, cm.clave_area, cm.nombre_mando, cm.clave_depto as depto_que_mana
        FROM catalogos_mandos cm 
        WHERE cm.activo = 1
        ORDER BY cm.clave_depto, cm.clave_area
    ");
    $mandos = $stmtMandos->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Mandos encontrados: " . count($mandos) . "\n\n";
    
    // Paso 2: Buscar empleados que coincidan con los nomes de los mandos
    foreach ($mandos as $mando) {
        $nombreMando = trim($mando['nombre_mando']);
        
        // Dividir el nombre en partes para buscar coincidencias
        $partesNombre = preg_split('/\s+/', $nombreMando);
        
        // Buscar empleado con coincidencias en el nombre
        $sqlBuscar = "
            SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo
            FROM empleados e
            WHERE e.activo = 1 AND e.nombre != ''
        ";
        
        foreach ($partesNombre as $parte) {
            if (strlen($parte) > 3) { // Ignorar partes muy cortas
                $sqlBuscar .= " AND (
                    e.nombre LIKE '%' || ? || '%' 
                    OR e.apellido LIKE '%' || ? || '%'
                    OR CONCAT(e.nombre, ' ', e.apellido) LIKE '%' || ? || '%'
                )";
            }
        }
        
        $stmtBuscar = $pdo->prepare($sqlBuscar);
        $params = [];
        foreach ($partesNombre as $parte) {
            if (strlen($parte) > 3) {
                $params[] = $parte;
                $params[] = $parte;
                $params[] = $parte;
            }
        }
        
        if (!empty($params)) {
            $stmtBuscar->execute($params);
            $empleadosCoincidentes = $stmtBuscar->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($empleadosCoincidentes) == 1) {
                $emp = $empleadosCoincidentes[0];
                echo "VINCULADO: {$nombreMando}\n";
                echo "  -> Empleado ID: {$emp['id']} ({$emp['nombre_completo']})\n";
                echo "  -> Depto que maneja: {$mando['depto_que_mana']}\n";
                
                // Actualizar la clave_depto del empleado para que coincida con el depto que maneja
                $stmtUpdate = $pdo->prepare("
                    UPDATE empleados SET clave_depto = ? WHERE id = ?
                ");
                $stmtUpdate->execute([$mando['depto_que_mana'], $emp['id']]);
                echo "  -> Actualizado clave_depto = {$mando['depto_que_mana']}\n\n";
            } elseif (count($empleadosCoincidentes) > 1) {
                echo "MULTIPLES COINCIDENCIAS: {$nombreMando}\n";
                foreach ($empleadosCoincidentes as $emp) {
                    echo "  - ID {$emp['id']}: {$emp['nombre_completo']}\n";
                }
                echo "  -> Depto que maneja: {$mando['depto_que_mana']}\n\n";
            }
        }
    }
    
    echo "\n===========================================\n";
    echo "Actualizando jefe_directo_id de empleados\n";
    echo "===========================================\n\n";
    
    // Paso 3: Obtener todos los empleados que son mandos (tienen clave_depto)
    $stmtMandosEmpleados = $pdo->query("
        SELECT e.id, e.nombre, e.apellido, e.clave_depto, e.jefe_directo_id,
               CONCAT(e.nombre, ' ', e.apellido) as nombre_completo
        FROM empleados e
        WHERE e.activo = 1
        AND e.clave_depto IS NOT NULL
        AND e.clave_depto != ''
        AND e.id IN (1, 3, 4, 6, 9)
    ");
    $mandosEmpleados = $stmtMandosEmpleados->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Empleados que son mandos:\n";
    foreach ($mandosEmpleados as $mandoEmp) {
        echo "- ID {$mandoEmp['id']}: {$mandoEmp['nombre_completo']} (Depto: {$mandoEmp['clave_depto']})\n";
    }
    
    echo "\n===========================================\n";
    echo "Actualizando subordinados\n";
    echo "===========================================\n\n";
    
    // Paso 4: Para cada empleado, buscar su mando correcto por clave_depto
    $stmtTodosEmpleados = $pdo->query("
        SELECT id, nombre, apellido, clave_depto, jefe_directo_id, jerarquia,
               CONCAT(nombre, ' ', apellido) as nombre_completo
        FROM empleados
        WHERE activo = 1
        AND jerarquia IS NULL OR jerarquia = '' OR jerarquia = 'Empleado'
    ");
    $empleados = $stmtTodosEmpleados->fetchAll(PDO::FETCH_ASSOC);
    
    $actualizados = 0;
    foreach ($empleados as $emp) {
        $claveDepto = $emp['clave_depto'];
        
        if (empty($claveDepto)) {
            continue;
        }
        
        // Buscar el mando de este departamento
        $stmtMando = $pdo->prepare("
            SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo
            FROM empleados e
            INNER JOIN catalogos_mandos cm ON e.clave_depto = cm.clave_depto
            WHERE e.activo = 1
            AND e.id IN (1, 3, 4, 6, 9)
            AND e.clave_depto = ?
            LIMIT 1
        ");
        $stmtMando->execute([$claveDepto]);
        $mando = $stmtMando->fetch(PDO::FETCH_ASSOC);
        
        if ($mando && $emp['jefe_directo_id'] != $mando['id']) {
            echo "Empleado ID {$emp['id']}: {$emp['nombre_completo']}\n";
            echo "  Depto: {$claveDepto} | Jefe actual: {$emp['jefe_directo_id']} -> Nuevo: {$mando['id']} ({$mando['nombre_completo']})\n";
            
            $stmtUpdateJefe = $pdo->prepare("
                UPDATE empleados SET jefe_directo_id = ?, jefe_directo_clave = ? WHERE id = ?
            ");
            $stmtUpdateJefe->execute([$mando['id'], $mando['id'], $emp['id']]);
            $actualizados++;
        }
    }
    
    echo "\n===========================================\n";
    echo "Resumen\n";
    echo "===========================================\n";
    echo "Empleados actualizados: {$actualizados}\n";
    echo "Proceso completado exitosamente.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
