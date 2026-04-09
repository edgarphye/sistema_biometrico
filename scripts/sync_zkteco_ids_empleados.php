<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

/**
 * Sincroniza IDs biométricos de ZKTeco hacia empleados.zkteo_id.
 *
 * Uso:
 *  php scripts/sync_zkteco_ids_empleados.php --from-mapping
 *  php scripts/sync_zkteco_ids_empleados.php --csv=/ruta/archivo.csv
 *
 * Formato CSV esperado: empleado_id,zkteo_id
 */

function ensureZkteoColumn(PDO $conn): void {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'empleados'
          AND COLUMN_NAME = ?
    ");
    $stmt->execute(['zkteo_id']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (((int)($row['total'] ?? 0)) > 0) {
        return;
    }

    $conn->exec("ALTER TABLE empleados ADD COLUMN zkteo_id VARCHAR(50) NULL AFTER id");
    $conn->exec("CREATE INDEX idx_empleados_zkteo_id ON empleados(zkteo_id)");
}

function syncFromMapping(PDO $conn): int {
    $sql = "
        UPDATE empleados e
        INNER JOIN zkteo_empleado_mapeo zm ON zm.empleado_id = e.id
        SET e.zkteo_id = zm.zkteo_id
        WHERE zm.activo = 1
          AND zm.zkteo_id IS NOT NULL
          AND zm.zkteo_id != ''
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}

function syncFromCsv(PDO $conn, string $csvPath): array {
    if (!is_readable($csvPath)) {
        throw new InvalidArgumentException("No se puede leer el archivo CSV: {$csvPath}");
    }

    $fh = fopen($csvPath, 'r');
    if ($fh === false) {
        throw new RuntimeException("No se pudo abrir el archivo CSV: {$csvPath}");
    }

    $updated = 0;
    $skipped = 0;

    $header = fgetcsv($fh);
    if ($header === false) {
        fclose($fh);
        throw new RuntimeException('CSV vacío');
    }

    $headerMap = array_flip(array_map('trim', $header));
    if (!isset($headerMap['empleado_id']) || !isset($headerMap['zkteo_id'])) {
        fclose($fh);
        throw new InvalidArgumentException('El CSV debe incluir columnas: empleado_id,zkteo_id');
    }

    $sql = "UPDATE empleados SET zkteo_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    while (($row = fgetcsv($fh)) !== false) {
        $empleadoId = trim((string)$row[$headerMap['empleado_id']]);
        $zkteoId = trim((string)$row[$headerMap['zkteo_id']]);

        if ($empleadoId === '' || $zkteoId === '') {
            $skipped++;
            continue;
        }

        $stmt->execute([$zkteoId, (int)$empleadoId]);
        $updated += $stmt->rowCount();
    }

    fclose($fh);

    return ['updated' => $updated, 'skipped' => $skipped];
}

try {
    $options = getopt('', ['from-mapping', 'csv:']);

    if (!isset($options['from-mapping']) && !isset($options['csv'])) {
        throw new InvalidArgumentException("Uso:\n  php scripts/sync_zkteco_ids_empleados.php --from-mapping\n  php scripts/sync_zkteco_ids_empleados.php --csv=/ruta/archivo.csv");
    }

    $db = new Database();
    $conn = $db->getConnection();
    ensureZkteoColumn($conn);

    if (isset($options['from-mapping'])) {
        $rows = syncFromMapping($conn);
        echo "OK: empleados.zkteo_id actualizado desde zkteo_empleado_mapeo. Filas afectadas: {$rows}\n";
        exit(0);
    }

    $result = syncFromCsv($conn, $options['csv']);
    echo "OK: sincronización por CSV completada. Actualizados: {$result['updated']} | Omitidos: {$result['skipped']}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
