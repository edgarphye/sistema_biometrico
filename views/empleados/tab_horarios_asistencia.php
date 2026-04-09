<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Historial de Horarios</h5>
        <div>
            <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#modalAsignarHorario">
                <i class="fas fa-plus me-1"></i> Asignar Horario Fijo
            </button>
            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalAsignarCiclo">
                <i class="fas fa-sync me-1"></i> Asignar Ciclo
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Vigencia</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <th>Total Sem.</th>
                        <th>Clasificación</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Depuración: mostrar qué datos llegan
                    // error_log("historialHorarios: " . print_r($historialHorarios, true));
                    // error_log("asignaciones: " . print_r(count($asignaciones), true));
                    
                    // Usar historialHorarios si está disponible, sino usar asignaciones legacy
                    $listaHorarios = !empty($historialHorarios) ? $historialHorarios : null;
                    
                    if ($listaHorarios === null || empty($listaHorarios)): 
                        // Fallback: construir desde $asignaciones legacy
                        $listaHorarios = [];
                        foreach ($asignaciones as $asig) {
                            $esCiclo = !empty($asig['ciclo_id']);
                            
                            // Determinar el tipo de horario correcto
                            $tipoHorarioGuardado = $asig['tipo_horario'] ?? '';
                            if ($tipoHorarioGuardado === 'FIJO') {
                                $tipoHorarioMostrar = 'Horario Fijo';
                            } elseif ($tipoHorarioGuardado === 'COMBINADO') {
                                $tipoHorarioMostrar = 'Horario Combinado';
                            } elseif ($tipoHorarioGuardado === 'ROTATIVO') {
                                $tipoHorarioMostrar = 'Ciclo Rotativo';
                            } else {
                                $tipoHorarioMostrar = $esCiclo ? 'Ciclo Rotativo' : 'Horario Fijo';
                            }
                            
                            $listaHorarios[] = [
                                'id' => $asig['id'] ?? null,
                                'ciclo_id' => $asig['ciclo_id'] ?? null,
                                'horario_id' => $asig['horario_id'] ?? null,
                                'tipo_asignacion' => $esCiclo ? 'CICLO' : 'HORARIO',
                                'nombre' => $esCiclo ? ($asig['nombre_ciclo'] ?? 'Ciclo') : ($asig['nombre_horario'] ?? 'Horario'),
                                'fecha_inicio' => $asig['fecha_inicio'] ?? null,
                                'fecha_fin' => $asig['fecha_fin'] ?? null,
                                'hora_entrada' => $asig['hora_entrada'] ?? null,
                                'hora_salida' => $asig['hora_salida'] ?? null,
                                'total_horas_semanales' => $asig['total_horas_semanales'] ?? 0,
                                'tipo_horario' => $tipoHorarioMostrar,
                                'detalle' => $esCiclo ? json_decode($asig['detalle_json'] ?? '[]', true) : [],
                                'estado' => 'VIGENTE'
                            ];
                        }
                    endif;
                    
                    if (empty($listaHorarios)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">
                            <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                            No hay horarios asignados
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($listaHorarios as $asig): 
                            // Determinar badge de tipo
                            $esCiclo = ($asig['tipo_asignacion'] ?? '') === 'CICLO';
                            $tipoBadge = $esCiclo ? '<span class="badge bg-info">Ciclo</span>' : '<span class="badge bg-primary">Horario Fijo</span>';
                            
                            // Determinar badge de clasificación (tipo de horario) - COLORES CORRECTOS
                            $clasificacion = $asig['tipo_horario'] ?? 'Horario Fijo';
                            $clasificacionLower = strtolower($clasificacion);
                            if (strpos($clasificacionLower, 'rotativo') !== false) {
                                $clasificacionBadge = '<span class="badge bg-info"><i class="fas fa-sync-alt me-1"></i>Rotativo</span>';
                            } elseif (strpos($clasificacionLower, 'combinado') !== false) {
                                $clasificacionBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-layer-group me-1"></i>Combinado</span>';
                            } else {
                                $clasificacionBadge = '<span class="badge bg-primary"><i class="fas fa-clock me-1"></i>Fijo</span>';
                            }
                            
                            // Vigencia
                            $inicio = isset($asig['fecha_inicio']) ? date('d/m/Y', strtotime($asig['fecha_inicio'])) : 'N/A';
                            $fin = isset($asig['fecha_fin']) && $asig['fecha_fin'] ? date('d/m/Y', strtotime($asig['fecha_fin'])) : 'Indefinido';
                            
                            // Estado
                            $estado = $asig['estado'] ?? 'VIGENTE';
                            $estadoBadge = match($estado) {
                                'VIGENTE' => '<span class="badge bg-success">Vigente</span>',
                                'FINALIZADO' => '<span class="badge bg-secondary">Finalizado</span>',
                                'FUTURO' => '<span class="badge bg-info text-white">Futuro</span>',
                                default => '<span class="badge bg-secondary">Desconocido</span>'
                            };
                            
                            // Horas
                            $horaEntrada = !empty($asig['hora_entrada']) ? substr($asig['hora_entrada'], 0, 5) : '-';
                            $horaSalida = !empty($asig['hora_salida']) ? substr($asig['hora_salida'], 0, 5) : '-';
                            $totalHoras = !empty($asig['total_horas_semanales']) ? number_format($asig['total_horas_semanales'], 1) . ' hrs' : '-';
                            
                            // Tooltip con detalle de días (si hay detalle)
                            $tooltip = '';
                            if (!empty($asig['detalle']) && is_array($asig['detalle'])) {
                                $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                                $tooltipDias = [];
                                foreach ($asig['detalle'] as $det) {
                                    $diaIdx = isset($det['dia']) ? (int)$det['dia'] : 0;
                                    $tooltipDias[] = $dias[$diaIdx] . ': ' . substr($det['hora_inicio'], 0, 5) . '-' . substr($det['hora_fin'], 0, 5);
                                }
                                $tooltip = 'data-bs-toggle="tooltip" data-bs-title="' . implode(', ', $tooltipDias) . '"';
                            }
                        ?>
                        <tr <?= $tooltip ?>>
                            <td><?= $tipoBadge ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($asig['nombre'] ?? 'N/A') ?></td>
                            <td>
                                <small class="d-block text-muted">Desde: <?= $inicio ?></small>
                                <small class="d-block text-muted">Hasta: <?= $fin ?></small>
                            </td>
                            <td class="font-monospace text-primary fw-bold"><?= $horaEntrada ?></td>
                            <td class="font-monospace text-danger fw-bold"><?= $horaSalida ?></td>
                            <td class="font-monospace"><?= $totalHoras ?></td>
                            <td><?= $clasificacionBadge ?></td>
                            <td><?= $estadoBadge ?></td>
                            <td class="text-end">
                                <?php if (!empty($asig['id'])): ?>
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick='verDetalleHorario(<?= json_encode($asig) ?>)'
                                            title="Ver Detalle Semanal">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick='abrirModalEditar(<?= json_encode([
                                                "id" => $asig['id'],
                                                "tipo" => $esCiclo ? "ciclo" : "horario",
                                                "item_id" => $esCiclo ? ($asig['ciclo_id'] ?? '') : ($asig['horario_id'] ?? ''),
                                                "fecha_inicio" => $asig['fecha_inicio'] ?? '',
                                                "fecha_fin" => $asig['fecha_fin'] ?? ''
                                            ]) ?>)'
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="eliminarAsignacion(<?= $asig['id'] ?>, <?= $empleado['id'] ?>)"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ver Detalle Semanal -->
<div class="modal fade" id="modalDetalleHorario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-week me-2"></i>Detalle Semanal del Horario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detalleHorarioContent">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Asignación -->
<div class="modal fade" id="modalEditarAsignacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Asignación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarAsignacion">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="empleado_id" value="<?= $empleado['id'] ?>">
                    <input type="hidden" name="ajax" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo" id="edit_tipo" onchange="toggleEditTipo()">
                            <option value="horario">Horario Fijo</option>
                            <option value="ciclo">Ciclo Rotativo</option>
                        </select>
                    </div>

                    <div class="mb-3" id="div_edit_horario">
                        <label class="form-label">Horario</label>
                        <select class="form-select" name="item_id" id="edit_item_horario">
                            <?php foreach ($horarios as $h): ?>
                                <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['nombre']) ?> (<?= substr($h['hora_entrada'],0,5) ?>-<?= substr($h['hora_salida'],0,5) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="div_edit_ciclo" style="display:none;">
                        <label class="form-label">Ciclo</label>
                        <select class="form-select" name="item_id" id="edit_item_ciclo" disabled>
                            <?php foreach ($ciclos as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio" id="edit_fecha_inicio" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha Fin (Opcional)</label>
                            <input type="date" class="form-control" name="fecha_fin" id="edit_fecha_fin">
                            <div class="form-text text-muted">Dejar vacío para indefinido</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitEditarAsignacion()"><i class="fas fa-save me-1"></i> Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalAsignarHorario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clock me-2"></i>Asignar Horario Fijo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAsignarHorario">
                    <input type="hidden" name="empleado_id" value="<?= $empleado['id'] ?>">
                    <input type="hidden" name="ajax" value="1">
                    <div class="mb-3">
                        <label class="form-label">Horario</label>
                        <select class="form-select" name="horario_id" required>
                            <?php foreach ($horarios as $h): ?>
                                <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['nombre']) ?> (<?= substr($h['hora_entrada'],0,5) ?>-<?= substr($h['hora_salida'],0,5) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Día de la Semana</label>
                        <select class="form-select" name="dia_semana" required>
                            <option value="Lunes">Lunes</option>
                            <option value="Martes">Martes</option>
                            <option value="Miércoles">Miércoles</option>
                            <option value="Jueves">Jueves</option>
                            <option value="Viernes">Viernes</option>
                            <option value="Sábado">Sábado</option>
                            <option value="Domingo">Domingo</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitAsignacion('formAsignarHorario', '<?= BASE_URL ?>/horarios/asignar')"><i class="fas fa-save me-1"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Asignar Ciclo -->
<div class="modal fade" id="modalAsignarCiclo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Asignar Ciclo Rotativo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAsignarCiclo">
                    <input type="hidden" name="empleado_id" value="<?= $empleado['id'] ?>">
                    <input type="hidden" name="ajax" value="1">
                    <div class="mb-3">
                        <label class="form-label">Ciclo</label>
                        <select class="form-select" name="ciclo_id" required>
                            <?php foreach ($ciclos as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio" required value="<?= date('Y-m-d') ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="submitAsignacion('formAsignarCiclo', '<?= BASE_URL ?>/horarios/asignar-ciclo')"><i class="fas fa-sync-alt me-1"></i> Asignar Ciclo</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Headers de modals con gradiente Pantone */
.modal-header {
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
    color: #ffffff;
}
#modalAsignarCiclo .modal-header {
    background: linear-gradient(135deg, #235B4E 0%, #10312B 100%);
}

/* Tema oscuro - modals */
body.dark-theme .modal-header {
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
    color: #ffffff;
    border-bottom: 1px solid #888a8a;
}
body.dark-theme .modal-footer {
    background-color: #6F7271;
    border-top-color: #888a8a;
}
body.dark-theme .modal-body {
    background-color: #7a7d7d;
    color: #ffffff;
}
body.dark-theme .modal-content {
    background-color: #7a7d7d;
    color: #ffffff;
}
body.dark-theme .form-label {
    color: #ffffff;
}
body.dark-theme #modalAsignarCiclo .modal-header {
    background: linear-gradient(135deg, #235B4E 0%, #10312B 100%);
}
</style>
