<?php
/**
 * Script para modificar contraseñas de usuarios tipo jefe
 */

require_once 'config.php';
require_once 'models/Database.php';

echo "=== MODIFICACIÓN DE CONTRASEÑAS DE USUARIOS TIPO JEFE ===\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Nueva contraseña para todos los jefes
    $nuevaContraseña = 'jefe2026';
    $hashedPassword = password_hash($nuevaContraseña, PASSWORD_DEFAULT);
    
    echo "Nueva contraseña: $nuevaContraseña\n";
    echo "Hash generado: " . substr($hashedPassword, 0, 20) . "...\n\n";
    
    // Obtener todos los usuarios tipo jefe
    $stmt = $pdo->query('SELECT id, username, nombre_completo, rol, email FROM usuarios WHERE rol IN ("jefe", "supervisor") ORDER BY id');
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Actualizando " . count($jefes) . " usuarios tipo jefe...\n\n";
    
    $actualizaciones = 0;
    foreach ($jefes as $jefe) {
        // Actualizar contraseña
        $sql = "UPDATE usuarios SET password = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([$hashedPassword, $jefe['id']]);
        
        if ($resultado) {
            $actualizaciones++;
            echo "✅ Actualizado: {$jefe['username']} ({$jefe['nombre_completo']}) - ID: {$jefe['id']}\n";
            echo "   Email: {$jefe['email']}\n";
            echo "   Rol: {$jefe['rol']}\n";
        } else {
            echo "❌ Error al actualizar: {$jefe['username']}\n";
        }
        echo "\n";
    }
    
    echo "📊 RESUMEN:\n";
    echo "   Total jefes encontrados: " . count($jefes) . "\n";
    echo "   Actualizaciones exitosas: $actualizaciones\n";
    echo "   Errores: " . (count($jefes) - $actualizaciones) . "\n\n";
    
    echo "🔑 CREDENCIALES ACTUALIZADAS:\n";
    echo "   Usuario: cualquiera de los usuarios tipo jefe listados arriba\n";
    echo "   Contraseña: $nuevaContraseña\n\n";
    
    echo "✅ Proceso completado. Todos los usuarios tipo jefe ahora tienen la misma contraseña.\n";
    
} catch (Exception $e) {
    echo "❌ Error en el proceso: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>