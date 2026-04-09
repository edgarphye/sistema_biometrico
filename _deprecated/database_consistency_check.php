<?php
// database_consistency_check.php - Verificación de consistencia de datos

require_once 'config.php';
require_once 'models/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== CONSISTENCY CHECK FOR BIOMETRIC SYSTEM ===\n\n";

// 1. Check table structure
echo "1. CHECKING TABLE STRUCTURE...\n";

$tables = ['asistencia', 'retardos', 'empleados'];

foreach ($tables as $table) {
    $sql = "DESCRIBE $table";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table: $table\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    echo "\n";
}

// 2. Check data integrity
echo "2. DATA INTEGRITY CHECKS...\n";

// Check asistencia table
$sql = "SELECT COUNT(*) as total, 
               COUNT(CASE WHEN empleado_id IS NULL THEN 1 END) as null_empleado,
               COUNT(CASE WHEN fecha IS NULL THEN 1 END) as null_fecha,
               COUNT(CASE WHEN hora_entrada IS NULL THEN 1 END) as null_entrada
        FROM asistencia";
$stmt = $conn->prepare($sql);
$stmt->execute();
$asistencia_stats = $stmt->fetch();

echo "Asistencia Table:\n";
echo "  - Total records: {$asistencia_stats['total']}\n";
echo "  - Records with NULL employee_id: {$asistencia_stats['null_empleado']}\n";
echo "  - Records with NULL fecha: {$asistencia_stats['null_fecha']}\n";
echo "  - Records with NULL hora_entrada: {$asistencia_stats['null_entrada']}\n\n";

// Check retardos table
$sql = "SELECT COUNT(*) as total,
               COUNT(CASE WHEN empleado_id IS NULL THEN 1 END) as null_empleado,
               COUNT(CASE WHEN fecha IS NULL THEN 1 END) as null_fecha,
               COUNT(CASE WHEN minutos_retardo IS NULL THEN 1 END) as null_minutos
        FROM retardos";
$stmt = $conn->prepare($sql);
$stmt->execute();
$retardos_stats = $stmt->fetch();

echo "Retardos Table:\n";
echo "  - Total records: {$retardos_stats['total']}\n";
echo "  - Records with NULL employee_id: {$retardos_stats['null_empleado']}\n";
echo "  - Records with NULL fecha: {$retardos_stats['null_fecha']}\n";
echo "  - Records with NULL minutos_retraso: {$retardos_stats['null_minutos']}\n\n";

// 3. Check for orphaned records
echo "3. ORPHANED RECORDS CHECK...\n";

// Check asistencia with non-existent employees
$sql = "SELECT COUNT(*) as orphaned 
        FROM asistencia a 
        LEFT JOIN empleados e ON a.empleado_id = e.id 
        WHERE e.id IS NULL";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orphaned_asistencia = $stmt->fetch()['orphaned'];

echo "Asistencia records with non-existent employees: $orphaned_asistencia\n";

// Check retardos with non-existent employees
$sql = "SELECT COUNT(*) as orphaned 
        FROM retardos r 
        LEFT JOIN empleados e ON r.empleado_id = e.id 
        WHERE e.id IS NULL";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orphaned_retardos = $stmt->fetch()['orphaned'];

echo "Retardos records with non-existent employees: $orphaned_retardos\n\n";

// 4. Check for duplicate records
echo "4. DUPLICATE RECORDS CHECK...\n";

// Check duplicate asistencia records
$sql = "SELECT empleado_id, fecha, COUNT(*) as count 
        FROM asistencia 
        GROUP BY empleado_id, fecha 
        HAVING COUNT(*) > 1 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute();
$duplicate_asistencia = $stmt->fetchAll();

if (!empty($duplicate_asistencia)) {
    echo "Potential duplicate asistencia records:\n";
    foreach ($duplicate_asistencia as $dup) {
        echo "  - Employee {$dup['empleado_id']}, Date {$dup['fecha']}: {$dup['count']} records\n";
    }
} else {
    echo "No duplicate asistencia records found\n";
}

// Check duplicate retardos records
$sql = "SELECT empleado_id, fecha, COUNT(*) as count 
        FROM retardos 
        GROUP BY empleado_id, fecha 
        HAVING COUNT(*) > 1 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute();
$duplicate_retardos = $stmt->fetchAll();

if (!empty($duplicate_retardos)) {
    echo "Potential duplicate retardos records:\n";
    foreach ($duplicate_retardos as $dup) {
        echo "  - Employee {$dup['empleado_id']}, Date {$dup['fecha']}: {$dup['count']} records\n";
    }
} else {
    echo "No duplicate retardos records found\n";
}

echo "\n";

// 5. Recent activity check
echo "5. RECENT ACTIVITY CHECK...\n";

$sql = "SELECT COUNT(*) as today_records 
        FROM asistencia 
        WHERE DATE(fecha) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->execute();
$today_records = $stmt->fetch()['today_records'];

echo "Asistencia records for today: $today_records\n";

$sql = "SELECT COUNT(*) as today_retardos 
        FROM retardos 
        WHERE DATE(fecha) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->execute();
$today_retardos = $stmt->fetch()['today_retardos'];

echo "Retardos records for today: $today_retardos\n\n";

// 6. Check for data anomalies
echo "6. DATA ANOMALIES CHECK...\n";

// Check for impossible times (e.g., exit before entry)
$sql = "SELECT COUNT(*) as impossible_times 
        FROM asistencia 
        WHERE hora_salida IS NOT NULL 
        AND hora_entrada IS NOT NULL 
        AND hora_salida < hora_entrada";
$stmt = $conn->prepare($sql);
$stmt->execute();
$impossible_times = $stmt->fetch()['impossible_times'];

echo "Records with exit time before entry time: $impossible_times\n";

// Check for excessive work hours
$sql = "SELECT COUNT(*) as excessive_hours 
        FROM asistencia 
        WHERE hora_entrada IS NOT NULL 
        AND hora_salida IS NOT NULL 
        AND TIMESTAMPDIFF(HOUR, CONCAT(fecha, ' ', hora_entrada), CONCAT(fecha, ' ', hora_salida)) > 16";
$stmt = $conn->prepare($sql);
$stmt->execute();
$excessive_hours = $stmt->fetch()['excessive_hours'];

echo "Records with excessive work hours (>16 hours): $excessive_hours\n\n";

echo "=== CONSISTENCY CHECK COMPLETE ===\n";

// Overall status
$issues = $asistencia_stats['null_empleado'] + $asistencia_stats['null_fecha'] + 
          $retardos_stats['null_empleado'] + $retardos_stats['null_fecha'] +
          $orphaned_asistencia + $orphaned_retardos + $impossible_times + $excessive_hours;

if ($issues === 0) {
    echo "✅ All checks passed - Database is consistent\n";
} else {
    echo "⚠️  Found $issues potential issues - Review needed\n";
}
?>