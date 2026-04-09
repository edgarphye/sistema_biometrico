<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "=== 🔄 Actualizando Estructura de Horarios ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener columnas actuales de la tabla
    $stmt = $db->prepare("DESCRIBE horarios_laborales");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Definir columnas nuevas y sus definiciones SQL
    $definitions = [
        'color' => "ADD COLUMN color VARCHAR(7) DEFAULT '#007bff' AFTER descripcion",
        'sede' => "ADD COLUMN sede VARCHAR(100) NULL AFTER descripcion",
        'inicio_marcaje_entrada' => "ADD COLUMN inicio_marcaje_entrada TIME NULL",
        'fin_marcaje_entrada' => "ADD COLUMN fin_marcaje_entrada TIME NULL",
        'inicio_marcaje_salida' => "ADD COLUMN inicio_marcaje_salida TIME NULL",
        'fin_marcaje_salida' => "ADD COLUMN fin_marcaje_salida TIME NULL",
        'debe_marcar_entrada' => "ADD COLUMN debe_marcar_entrada TINYINT(1) DEFAULT 1",
        'debe_marcar_salida' => "ADD COLUMN debe_marcar_salida TINYINT(1) DEFAULT 1",
        'cuenta_dia_trabajo' => "ADD COLUMN cuenta_dia_trabajo TINYINT(1) DEFAULT 1",
        'cuenta_minutos' => "ADD COLUMN cuenta_minutos TINYINT(1) DEFAULT 0"
    ];
    
    foreach ($definitions as $col => $sql) {
        if (!in_array($col, $columns)) {
            echo "   ➕ Agregando columna faltante: $col\n";
            $db->exec("ALTER TABLE horarios_laborales $sql");
        } else {
            echo "   ✓ Columna ya existe: $col\n";
        }
    }
    
    echo "\n✅ Actualización completada con éxito.\n";
    echo "   Ahora puedes guardar horarios sin errores.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error durante la migración: " . $e->getMessage() . "\n";
}
?>
