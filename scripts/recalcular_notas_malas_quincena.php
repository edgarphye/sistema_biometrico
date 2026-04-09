<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

function rangoQuincena(string $fecha): array {
    $ts = strtotime($fecha);
    $dia = (int)date('d', $ts);
    $mes = date('m', $ts);
    $anio = date('Y', $ts);

    if ($dia <= 15) {
        return ["{$anio}-{$mes}-01", "{$anio}-{$mes}-15", 1];
    }

    return ["{$anio}-{$mes}-16", date('Y-m-t', $ts), 2];
}

$db = new Database();
$pdo = $db->getConnection();
$pdo->beginTransaction();

try {
    // 1) Crear sanciones faltantes para faltas (31+ minutos).
    $stmtFaltas = $pdo->prepare("\n        INSERT INTO sanciones (empleado_id, tipo_sancion, motivo, fecha_inicio, dias, estatus, tipo_retardo, creado_por, created_at)\n        SELECT r.empleado_id, 'acta_administrativa',\n               CONCAT('Falta por retardo de ', COALESCE(r.minutos_retardo, 0), ' minutos el ', r.fecha),\n               r.fecha, 1, 'activa', 'falta_31_minutos', r.aprobado_por, NOW()\n        FROM retardos r\n        WHERE r.tipo_retraso = 'falta'\n          AND NOT EXISTS (\n              SELECT 1 FROM sanciones s\n              WHERE s.empleado_id = r.empleado_id\n                AND s.fecha_inicio = r.fecha\n                AND s.tipo_retardo = 'falta_31_minutos'\n          )\n    ");
    $stmtFaltas->execute();
    $sancionesInsertadas = $stmtFaltas->rowCount();

    // 2) Crear notas malas por quincena con regla de 2 justificados permitidos.
    $stmtRetardos = $pdo->query("\n        SELECT id, empleado_id, fecha, tipo_retraso\n        FROM retardos\n        WHERE justificado = 1\n          AND tipo_retraso IN ('retardo_menor', 'retardo_mayor')\n        ORDER BY empleado_id ASC, fecha ASC, id ASC\n    ");
    $registros = $stmtRetardos->fetchAll(PDO::FETCH_ASSOC);

    $porGrupo = [];
    foreach ($registros as $r) {
        [$inicio, $fin, $q] = rangoQuincena($r['fecha']);
        $key = $r['empleado_id'] . '|' . $inicio . '|' . $fin . '|' . $q;
        if (!isset($porGrupo[$key])) {
            $porGrupo[$key] = ['inicio' => $inicio, 'fin' => $fin, 'q' => $q, 'items' => []];
        }
        $porGrupo[$key]['items'][] = $r;
    }

    $stmtExisteNota = $pdo->prepare("SELECT COUNT(*) AS total FROM notas_malas WHERE retardo_id = ?");
    $stmtInsNota = $pdo->prepare("\n        INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)\n        VALUES (?, ?, ?, ?, ?, ?)\n    ");

    $notasInsertadas = 0;

    foreach ($porGrupo as $grupo) {
        $items = $grupo['items'];
        if (count($items) <= 2) {
            continue;
        }

        $exceso = array_slice($items, 2);
        $menoresExceso = 0;

        foreach ($exceso as $item) {
            $retardoId = (int)$item['id'];
            $tipo = $item['tipo_retraso'];

            $stmtExisteNota->execute([$retardoId]);
            if (((int)($stmtExisteNota->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0) {
                continue;
            }

            if ($tipo === 'retardo_mayor') {
                $motivo = "Retardo mayor justificado excedente en quincena {$grupo['inicio']} a {$grupo['fin']}.";
                $stmtInsNota->execute([(int)$item['empleado_id'], $retardoId, 'retardo_mayor', 1, $grupo['inicio'], $motivo]);
                $notasInsertadas += $stmtInsNota->rowCount();
                continue;
            }

            // retardo_menor: cada 2 en exceso = 1 nota mala
            $menoresExceso++;
            if ($menoresExceso % 2 === 0) {
                $motivo = "Par de retardos menores justificados excedentes en quincena {$grupo['inicio']} a {$grupo['fin']}.";
                $stmtInsNota->execute([(int)$item['empleado_id'], $retardoId, 'retardo_menor', 1, $grupo['inicio'], $motivo]);
                $notasInsertadas += $stmtInsNota->rowCount();
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'sanciones_faltas_insertadas' => $sancionesInsertadas,
        'notas_malas_insertadas' => $notasInsertadas,
        'grupos_procesados' => count($porGrupo)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
