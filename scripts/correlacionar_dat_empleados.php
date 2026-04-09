<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

/**
 * Correlaciona IDs del archivo DAT de ZKTeco con empleados del sistema.
 *
 * Uso:
 *  php scripts/correlacionar_dat_empleados.php --dat=data/2_attlog.dat
 *  php scripts/correlacionar_dat_empleados.php --dat=data/2_attlog.dat --out=/tmp/correlacion.csv
 *
 * Salidas CSV:
 *  zkteo_id,apariciones,empleado_id_sugerido,nombre,apellido,rfc,metodo_sugerencia
 *  empleado_id,zkteo_id (archivo *_sync.csv, solo coincidencias)
 */

function parseDatIds(string $path): array {
    if (!is_readable($path)) {
        throw new InvalidArgumentException("No se puede leer archivo DAT: {$path}");
    }

    $ids = [];
    $fh = fopen($path, 'r');
    if ($fh === false) {
        throw new RuntimeException("No se pudo abrir DAT: {$path}");
    }

    while (($line = fgets($fh)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = preg_split('/\t+/', $line);
        if (!is_array($parts) || count($parts) < 1) {
            continue;
        }

        $zkId = trim((string)$parts[0]);
        if ($zkId === '') {
            continue;
        }

        if (!isset($ids[$zkId])) {
            $ids[$zkId] = 0;
        }
        $ids[$zkId]++;
    }

    fclose($fh);
    ksort($ids, SORT_NATURAL);

    return $ids;
}

function findEmpleado(PDO $conn, string $zkId): ?array {
    // 1) Match directo en columna biométrica dedicada
    $sql = "SELECT id, nombre, apellido, rfc, 'zkteo_id' AS metodo
            FROM empleados
            WHERE zkteo_id = ? AND activo = 1
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    try {
        $stmt->execute([$zkId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    } catch (Throwable $e) {
        // La columna podría no existir en ambientes antiguos.
    }

    // 2) Match por id interno si zkId es numérico
    if (ctype_digit($zkId)) {
        $sql = "SELECT id, nombre, apellido, rfc, 'id' AS metodo
                FROM empleados
                WHERE id = ? AND activo = 1
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([(int)$zkId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    return null;
}

function ensureOutDir(string $path): void {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        throw new RuntimeException("Directorio de salida no existe: {$dir}");
    }
}

try {
    $opts = getopt('', ['dat:', 'out:']);
    if (empty($opts['dat'])) {
        throw new InvalidArgumentException("Uso: php scripts/correlacionar_dat_empleados.php --dat=archivo.dat [--out=salida.csv]");
    }

    $datPath = $opts['dat'];
    $outPath = $opts['out'] ?? ('/tmp/correlacion_dat_empleados_' . date('Ymd_His') . '.csv');
    ensureOutDir($outPath);

    $ids = parseDatIds($datPath);

    $db = new Database();
    $conn = $db->getConnection();

    $fh = fopen($outPath, 'w');
    if ($fh === false) {
        throw new RuntimeException("No se pudo crear CSV: {$outPath}");
    }
    $syncPath = preg_replace('/\\.csv$/i', '_sync.csv', $outPath);
    if ($syncPath === null || $syncPath === $outPath) {
        $syncPath = $outPath . '_sync.csv';
    }
    $fhSync = fopen($syncPath, 'w');
    if ($fhSync === false) {
        throw new RuntimeException("No se pudo crear CSV de sincronización: {$syncPath}");
    }

    fputcsv($fh, ['zkteo_id', 'apariciones', 'empleado_id_sugerido', 'nombre', 'apellido', 'rfc', 'metodo_sugerencia']);
    fputcsv($fhSync, ['empleado_id', 'zkteo_id']);

    $matched = 0;
    $unmatched = 0;

    foreach ($ids as $zkId => $count) {
        $emp = findEmpleado($conn, (string)$zkId);
        if ($emp) {
            $matched++;
            fputcsv($fh, [
                $zkId,
                $count,
                $emp['id'],
                $emp['nombre'],
                $emp['apellido'],
                $emp['rfc'],
                $emp['metodo'],
            ]);
            fputcsv($fhSync, [$emp['id'], $zkId]);
        } else {
            $unmatched++;
            fputcsv($fh, [$zkId, $count, '', '', '', '', 'sin_match']);
        }
    }

    fclose($fh);
    fclose($fhSync);

    echo "OK\n";
    echo "DAT: {$datPath}\n";
    echo "IDs únicos en DAT: " . count($ids) . "\n";
    echo "Con sugerencia: {$matched}\n";
    echo "Sin sugerencia: {$unmatched}\n";
    echo "CSV generado: {$outPath}\n";
    echo "CSV sincronización: {$syncPath}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
