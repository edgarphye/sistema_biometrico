<?php ob_start(); ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Historial de Horarios y Asistencia</h2>
        <a href="<?= BASE_URL ?>/horarios/ver-asignaciones" class="btn btn-secondary">Volver</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']) ?></h5>
                    <small class="text-muted">ID: <?= $empleado['id'] ?> | Num: <?= $empleado['numero_empleado'] ?? 'N/A' ?></small>
                </div>
                <div class="col-md-8">
                    <form method="GET" class="row g-2 justify-content-md-end">
                        <div class="col-auto">
                            <select name="mes" class="form-select">
                                <?php
                                $meses = [
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                ];
                                foreach ($meses as $num => $nombre): ?>
                                    <option value="<?= $num ?>" <?= $num == $mes ? 'selected' : '' ?>><?= $nombre ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="anio" class="form-select">
                                <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= $y == $anio ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h6 class="mb-0">Detalle Mensual</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 150px;">Fecha</th>
                        <th>Día</th>
                        <th>Horario Asignado</th>
                        <th>Asistencia Registrada</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
                    $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                    $asignaciones = is_array($asignaciones) ? $asignaciones : [];

                    for ($d = 1; $d <= $diasEnMes; $d++):
                        $fechaActual = sprintf("%04d-%02d-%02d", $anio, $mes, $d);
                        $timestamp = strtotime($fechaActual);
                        $diaSemanaNum = date('w', $timestamp); // 0 (Dom) - 6 (Sab)
                        
                        // Determinar Horario
                        $horarioNombre = '<span class="text-muted fst-italic">Sin asignación</span>';
                        $claseFila = '';

                        foreach ($asignaciones as $asig) {
                            if ($asig['fecha_inicio'] <= $fechaActual && (empty($asig['fecha_fin']) || $asig['fecha_fin'] >= $fechaActual)) {
                                if (!empty($asig['ciclo_id'])) {
                                    $horarioNombre = '<span class="badge bg-info text-dark"><i class="fas fa-sync-alt me-1"></i> ' . htmlspecialchars($asig['nombre_ciclo']) . '</span>';
                                } elseif (!empty($asig['horario_id'])) {
                                    $horarioNombre = '<span class="badge bg-primary"><i class="fas fa-clock me-1"></i> ' . htmlspecialchars($asig['nombre_horario']) . '</span>';
                                }
                                break; // Tomar la primera asignación válida (la más reciente por el ORDER BY)
                            }
                        }

                        // Filtrar Asistencias del día
                        $asistenciasDia = array_filter($asistencias, function($a) use ($fechaActual) {
                            return strpos($a['fecha_hora'], $fechaActual) === 0;
                        });

                        // Estado visual
                        $estado = '';
                        if (empty($asistenciasDia)) {
                            if ($diaSemanaNum == 0 || $diaSemanaNum == 6) { // Fin de semana
                                $claseFila = 'bg-light text-muted';
                            } elseif ($timestamp < time()) {
                                $estado = '<span class="text-danger">Ausente / Sin registro</span>';
                            }
                        } else {
                            $estado = '<span class="text-success">Asistió</span>';
                        }
                    ?>
                    <tr class="<?= $claseFila ?>">
                        <td><?= $fechaActual ?></td>
                        <td><?= $diasSemana[$diaSemanaNum] ?></td>
                        <td><?= $horarioNombre ?></td>
                        <td>
                            <?php if (empty($asistenciasDia)): ?>
                                -
                            <?php else: ?>
                                <?php foreach ($asistenciasDia as $asis): ?>
                                    <?php 
                                        $hora = date('H:i', strtotime($asis['fecha_hora']));
                                        // Intentar adivinar si es entrada o salida basado en la hora (simple heurística visual)
                                        $esManana = intval(date('H', strtotime($asis['fecha_hora']))) < 12;
                                        $badgeClass = $esManana ? 'bg-success' : 'bg-warning text-dark';
                                        $icono = $esManana ? 'fa-sign-in-alt' : 'fa-sign-out-alt';
                                    ?>
                                    <span class="badge <?= $badgeClass ?> me-1" title="<?= $asis['fecha_hora'] ?>">
                                        <i class="fas <?= $icono ?>"></i> <?= $hora ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $estado ?></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>