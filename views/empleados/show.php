<?php
ob_start();
$rolActual = strtolower((string)($_SESSION['rol'] ?? ''));
$puedeVerHorarios = in_array($rolActual, ['admin', 'superadmin'], true);
// Verificar si es rol usuario (puede ser 'usuario' o 'usuarios')
$esUsuario = ($rolActual === 'usuario' || $rolActual === 'usuarios');
// Debug temporal
// error_log("DEBUG - rolActual: " . $rolActual . " - esUsuario: " . ($esUsuario ? 'true' : 'false'));
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <?php // The file_exists check is removed as it can be unreliable with web paths and cause errors. ?>
                            <?php if (!empty($empleado['foto_cara'])): ?>
                                <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $empleado['foto_cara']); ?>" alt="Foto" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px; font-size: 3rem; background: linear-gradient(135deg, #9F2241 0%, #691C32 100%) !important;">
                                    <?php echo strtoupper(substr($empleado['nombre'] ?? '', 0, 1) . substr($empleado['apellido'] ?? '', 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <h2 class="mb-1"><?php echo htmlspecialchars(($empleado['nombre'] ?? '') . ' ' . ($empleado['apellido'] ?? '')); ?></h2>
                            <p class="text-muted mb-2"><i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($empleado['area'] ?? 'N/A'); ?> | <?php echo htmlspecialchars($empleado['jerarquia'] ?? 'N/A'); ?></p>
                            <div class="d-flex gap-2">
                                <span class="badge" style="background-color: var(--color-primary, #9F2241); color: #fff;"><i class="fas fa-fingerprint me-1"></i>ID: <?php echo $empleado['id'] ?? 'N/A'; ?></span>
                                <span class="badge badge-pantone-gold"><i class="fas fa-passport me-1"></i>RFC: <?php echo htmlspecialchars($empleado['rfc'] ?? 'N/A'); ?></span>

                                <?php if (!empty($empleado['huella_registrada'])): ?>
                                    <span class="badge badge-pantone-green"><i class="fas fa-check me-1"></i>Biometría Registrada</span>
                                <?php else: ?>
                                    <span class="badge badge-pantone-gold"><i class="fas fa-exclamation-triangle me-1"></i>Sin Biometría</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if (empty($soloLectura)): ?>
                            <a href="<?php echo rtrim(BASE_URL, '/') . '/empleados/' . ($empleado['id'] ?? '') . '/edit'; ?>" class="btn btn-outline-primary mb-2 w-100">
                                <i class="fas fa-edit me-1"></i> Editar Perfil
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo rtrim(BASE_URL, '/') . '/empleados'; ?>" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <ul class="nav nav-tabs" id="empleadoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="asistencia-tab" data-bs-toggle="tab" data-bs-target="#asistencia" type="button" role="tab"><i class="fas fa-clock me-1"></i> Asistencia</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="retardos-tab" data-bs-toggle="tab" data-bs-target="#retardos" type="button" role="tab"><i class="fas fa-user-clock me-1"></i> Retardos</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="incidencias-tab" data-bs-toggle="tab" data-bs-target="#incidencias" type="button" role="tab"><i class="fas fa-briefcase me-1"></i> Incidencias</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ausencias-tab" data-bs-toggle="tab" data-bs-target="#ausencias" type="button" role="tab"><i class="fas fa-calendar-times me-1"></i> Dias Economicos</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sanciones-tab" data-bs-toggle="tab" data-bs-target="#sanciones" type="button" role="tab"><i class="fas fa-gavel me-1"></i> Sanciones</button>
                </li>
                <?php if ($puedeVerHorarios): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="horarios-tab" data-bs-toggle="tab" data-bs-target="#horarios" type="button" role="tab" onclick="cargarHorariosAsistencia(<?= $empleado['id'] ?? 0 ?>)"><i class="fas fa-calendar-alt me-1"></i> Horarios</button>
                </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content p-4 border border-top-0 rounded-bottom shadow-sm" id="empleadoTabsContent">
                
                <!-- Tab Asistencia -->
                <div class="tab-pane fade show active" id="asistencia" role="tabpanel">
                    <h5 class="mb-3">Historial de Asistencia - Empleado ID: <strong><?= $empleado['id'] ?></strong></h5>
                    
                    <!-- Filtros por mes -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                <?php
                                $currentYear = date('Y');
                                $currentMonth = date('n');
                                $meses = [
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                ];
                                
                                // This month
                                $thisMonthStart = date('Y-m-01');
                                $thisMonthEnd = date('Y-m-t');
                                $activeThisMonth = (isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] == $thisMonthStart);
                                ?>
                                <a href="?tab=asistencia&fecha_inicio=<?= $thisMonthStart ?>&fecha_fin=<?= $thisMonthEnd ?>" 
                                   class="btn btn-sm <?= $activeThisMonth ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    Este Mes
                                </a>
                                
                                <?php 
                                // Previous month
                                $prevMonth = mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear);
                                $prevMonthStart = date('Y-m-01', $prevMonth);
                                $prevMonthEnd = date('Y-m-t', $prevMonth);
                                $activePrevMonth = (isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] == $prevMonthStart);
                                ?>
                                <a href="?tab=asistencia&fecha_inicio=<?= $prevMonthStart ?>&fecha_fin=<?= $prevMonthEnd ?>" 
                                   class="btn btn-sm <?= $activePrevMonth ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    Mes Anterior
                                </a>
                                
                                <?php
                                // 2 months ago
                                $twoMonthsAgo = mktime(0, 0, 0, $currentMonth - 2, 1, $currentYear);
                                $twoMonthsStart = date('Y-m-01', $twoMonthsAgo);
                                $twoMonthsEnd = date('Y-m-t', $twoMonthsAgo);
                                $activeTwoMonths = (isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] == $twoMonthsStart);
                                ?>
                                <a href="?tab=asistencia&fecha_inicio=<?= $twoMonthsStart ?>&fecha_fin=<?= $twoMonthsEnd ?>" 
                                   class="btn btn-sm <?= $activeTwoMonths ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    <?= $meses[date('n', $twoMonthsAgo)] ?>
                                </a>
                                
                                <a href="?tab=asistencia" class="btn btn-sm btn-outline-secondary">
                                    Todos
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros personalizados -->
                    <form method="GET" action="<?= BASE_URL ?>/empleados/show/<?= $empleado['id'] ?>" class="row g-3 mb-3">
                        <input type="hidden" name="tab" value="asistencia">
                        <div class="col-md-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= $_GET['fecha_inicio'] ?? '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?= $_GET['fecha_fin'] ?? '' ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter me-1"></i> Filtrar</button>
                            <a href="<?= BASE_URL ?>/empleados/show/<?= $empleado['id'] ?>?tab=asistencia" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <span class="text-muted">Total: <?= count($historialCompleto) ?> registros</span>
                        </div>
                    </form>
                    
                    <?php if (empty($historialCompleto)): ?>
                        <div class="alert alert-info">No hay registros de asistencia.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Tipo</th>
                                        <th>Minutos</th>
                                        <th>Fuente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historialCompleto as $registro): ?>
                                        <?php 
                                        $tipo = $registro['tipo'] ?? 'normal';
                                        $fuente = $registro['fuente'] ?? 'asistencia';
                                        $tipoLabel = $tipo;
                                        $badgeColor = '#235B4E';
                                        
                                        switch ($tipo) {
                                            case 'retardo_menor':
                                                $tipoLabel = 'Retardo Menor';
                                                $badgeColor = '#856404';
                                                break;
                                            case 'retardo_mayor':
                                                $tipoLabel = 'Retardo Mayor';
                                                $badgeColor = '#9F2241';
                                                break;
                                            case 'falta':
                                                $tipoLabel = 'Falta';
                                                $badgeColor = '#212529';
                                                break;
                                            case 'comision_entrada':
                                                $tipoLabel = 'Comisión Entrada';
                                                $badgeColor = '#0dcaf0';
                                                break;
                                            case 'comision_salida':
                                                $tipoLabel = 'Comisión Salida';
                                                $badgeColor = '#0dcaf0';
                                                break;
                                            case 'comision_todo_dia':
                                                $tipoLabel = 'Comisión Día';
                                                $badgeColor = '#0dcaf0';
                                                break;
                                            case 'dia_economico':
                                                $tipoLabel = 'Día Económico';
                                                $badgeColor = '#235B4E';
                                                break;
                                            case 'vacaciones':
                                                $tipoLabel = 'Vacaciones';
                                                $badgeColor = '#0d6efd';
                                                break;
                                            case 'licencia_medica':
                                                $tipoLabel = 'Licencia Médica';
                                                $badgeColor = '#dc3545';
                                                break;
                                            case 'cuidados_maternos':
                                            case 'cuidados_parentales':
                                                $tipoLabel = 'Cuidados Parentales';
                                                $badgeColor = '#d63384';
                                                break;
                                            case 'por_definir':
                                                $tipoLabel = 'Por definir';
                                                $badgeColor = '#ffc107';
                                                break;
                                            case 'con_retardo':
                                                $tipoLabel = 'Con Retardo';
                                                $badgeColor = '#ffc107';
                                                break;
                                            case 'con_ausencia':
                                                $tipoLabel = 'Con Ausencia';
                                                $badgeColor = '#dc3545';
                                                break;
                                            case 'sin_registro':
                                                $tipoLabel = 'Sin Registro';
                                                $badgeColor = '#6c757d';
                                                break;
                                            case 'normal':
                                            default:
                                                $tipoLabel = 'Normal';
                                                $badgeColor = '#235B4E';
                                        }
                                        
                                        $fuenteLabel = $fuente === 'retardos' ? 'Retardo' : ($fuente === 'incidencias' ? 'Incidencia' : 'Asistencia');
                                        ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($registro['fecha'])); ?></td>
                                            <td><?php echo !empty($registro['hora_entrada']) ? substr($registro['hora_entrada'], 0, 5) : '-'; ?></td>
                                            <td><?php echo !empty($registro['hora_salida']) ? substr($registro['hora_salida'], 0, 5) : '-'; ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo $badgeColor; ?>; color: #fff;">
                                                    <?php echo $tipoLabel; ?>
                                                </span>
                                            </td>
                                            <td><?php echo !empty($registro['minutos']) ? $registro['minutos'] . ' min' : '-'; ?></td>
                                            <td><?php echo $fuenteLabel; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Retardos -->
                <div class="tab-pane fade" id="retardos" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Historial de Retardos</h5>
                        <div>
                            <?php
                            $r_menores = 0;
                            $r_mayores = 0;
                            $r_faltas = 0;
                            if (!empty($retardos)) {
                                foreach ($retardos as $r) {
                                    $tipo = $r['tipo'] ?? $r['tipo_retraso'] ?? '';
                                    if ($tipo === 'retardo_menor') $r_menores++;
                                    elseif ($tipo === 'retardo_mayor') $r_mayores++;
                                    elseif ($tipo === 'falta') $r_faltas++;
                                }
                            }
                            ?>
                            <span class="badge badge-pantone-gold">Menores: <?php echo $r_menores; ?></span>
                            <span class="badge badge-pantone-wine">Mayores: <?php echo $r_mayores; ?></span>
                            <span class="badge bg-dark">Faltas: <?php echo $r_faltas; ?></span>
                        </div>
                    </div>
                    <?php if (empty($retardos)): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i> El empleado no tiene retardos registrados.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Minutos</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($retardos as $retardo): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($retardo['fecha'] ?? '')); ?></td>
                                            <td class="text-danger fw-bold"><?php echo ($retardo['minutos_retardo'] ?? 0); ?> min</td>
                                            <td><span class="badge <?php echo ($retardo['tipo'] ?? $retardo['tipo_retraso'] ?? '') == 'retardo_mayor' ? 'badge-pantone-wine' : 'badge-pantone-gold'; ?>"><?php 
                                                $tipo = $retardo['tipo'] ?? $retardo['tipo_retraso'] ?? 'retardo_menor';
                                                echo ucwords(str_replace('_', ' ', $tipo));
                                            ?></span></td>
                                            <td>
                                                <?php if (!empty($retardo['justificado'])): ?>
                                                    <span class="badge badge-pantone-green">Justificado</span>
                                                    <?php if (!empty($retardo['tipo_justificacion'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($retardo['tipo_justificacion']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge badge-pantone-gold">Pendiente</span>
                                                <?php endif; ?>
                                                <?php if (!empty($retardo['estado_validacion_jefe'])): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-user-tie me-1"></i>Jefe: <?php echo $retardo['estado_validacion_jefe']; ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($retardo['estado_validacion_rh'])): ?>
                                                    <br><small class="text-info">
                                                        <i class="fas fa-building me-1"></i>RH: <?php echo $retardo['estado_validacion_rh']; ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($retardo['fecha_validacion_jefe'])): ?>
                                                    <br><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($retardo['fecha_validacion_jefe'])); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($retardo['comentarios_validacion_jefe'])): ?>
                                                    <br><small class="text-muted"><i class="fas fa-comment me-1"></i><?php echo htmlspecialchars($retardo['comentarios_validacion_jefe']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($retardo['justificacion_bloqueada'])): ?>
                                                    <span class="badge bg-secondary">Sanción</span>
                                                <?php elseif (empty($retardo['justificado']) && isset($retardo['id'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificarRetardo(<?php echo (int)$retardo['id']; ?>, <?php echo $diasEcoInfo['disponibles'] ?? 0; ?>, <?php echo $diasEcoInfo['puede_solicitar'] ? 'true' : 'false'; ?>)">
                                                        <i class="fas fa-check"></i> Justificar
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Incidencias -->
                <div class="tab-pane fade" id="incidencias" role="tabpanel">
                    <h5 class="mb-3"><i class="fas fa-clipboard-list me-2"></i>Incidencias (Justificaciones)</h5>
                    
                    <!-- Resumen de días económicos -->
                    <div class="alert alert-info mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Días económicos disponibles:</strong> <?php echo $diasEcoInfo['disponibles'] ?? 0; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Antigüedad:</strong> <?php echo $diasEcoInfo['antiguedad_dias'] ?? 0; ?> días
                            </div>
                            <div class="col-md-3">
                                <?php if (!($diasEcoInfo['puede_solicitar'] ?? true)): ?>
                                    <span class="badge bg-danger">No puede solicitar</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Puede solicitar</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Función para badge de tipo de incidencia
                    function getTipoBadgeIncidencia($item) {
                        $fuente = $item['fuente'] ?? 'asistencia';
                        $tipo = $item['tipo_asistencia'] ?? $item['tipo_retraso'] ?? 'normal';
                        $badgeClass = 'bg-secondary';
                        $icono = 'fa-question';
                        $texto = 'Desconocido';
                        
                        if ($fuente === 'retardos') {
                            // Es un retardo
                            switch($tipo) {
                                case 'retardo_menor':
                                    $badgeClass = 'bg-warning text-dark';
                                    $icono = 'fa-clock';
                                    $texto = 'Retardo Menor';
                                    break;
                                case 'retardo_mayor':
                                    $badgeClass = 'bg-danger';
                                    $icono = 'fa-exclamation-triangle';
                                    $texto = 'Retardo Mayor';
                                    break;
                                case 'falta':
                                    $badgeClass = 'bg-dark';
                                    $icono = 'fa-ban';
                                    $texto = 'Falta (31+ min)';
                                    break;
                                case 'comision_entrada':
                                    $badgeClass = 'bg-info';
                                    $icono = 'fa-briefcase';
                                    $texto = 'Comisión Entrada';
                                    break;
                                case 'comision_salida':
                                    $badgeClass = 'bg-info';
                                    $icono = 'fa-briefcase';
                                    $texto = 'Comisión Salida';
                                    break;
                                case 'comision_todo_dia':
                                    $badgeClass = 'bg-info';
                                    $icono = 'fa-briefcase';
                                    $texto = 'Comisión Día Completo';
                                    break;
                                default:
                                    $badgeClass = 'bg-secondary';
                                    $icono = 'fa-clock';
                                    $texto = ucfirst(str_replace('_', ' ', $tipo));
                            }
                        } else {
                            // Es asistencia
                            switch($tipo) {
                                case 'normal':
                                    $badgeClass = 'bg-success';
                                    $icono = 'fa-check-circle';
                                    $texto = 'Normal';
                                    break;
                                case 'por_definir':
                                    $badgeClass = 'bg-warning text-dark';
                                    $icono = 'fa-exclamation-circle';
                                    $texto = 'Por Definir';
                                    break;
                                case 'con_retardo':
                                    $badgeClass = 'bg-warning text-dark';
                                    $icono = 'fa-clock';
                                    $texto = 'Con Retardo';
                                    break;
                                case 'con_ausencia':
                                    $badgeClass = 'bg-danger';
                                    $icono = 'fa-times-circle';
                                    $texto = 'Con Ausencia';
                                    break;
                                case 'licencia_medica':
                                    $badgeClass = 'bg-danger';
                                    $icono = 'fa-hospital';
                                    $texto = 'Licencia Médica';
                                    break;
                                case 'dia_economico':
                                    $badgeClass = 'bg-success';
                                    $icono = 'fa-calendar-check';
                                    $texto = 'Día Económico';
                                    break;
                                case 'vacaciones':
                                    $badgeClass = 'bg-primary';
                                    $icono = 'fa-umbrella-beach';
                                    $texto = 'Vacaciones';
                                    break;
                                case 'cuidados_parentales':
                                    $badgeClass = 'bg-pink';
                                    $icono = 'fa-baby';
                                    $texto = 'Cuidados Parentales';
                                    break;
                                default:
                                    $badgeClass = 'bg-secondary';
                                    $icono = 'fa-clipboard';
                                    $texto = ucfirst(str_replace('_', ' ', $tipo));
                            }
                        }
                        
                        return '<span class="badge ' . $badgeClass . '"><i class="fas ' . $icono . ' me-1"></i>' . $texto . '</span>';
                    }
                    
                    // Función para badge de estado de validación
                    function getEstadoBadgeIncidencia($item) {
                        $estado = $item['estado_validacion_jefe'] ?? $item['estado_validacion'] ?? 'pendiente';
                        $justificado = $item['justificado'] ?? 0;
                        
                        $badgeClass = 'badge-pantone-gold';
                        $icono = 'fa-clock';
                        $texto = 'Pendiente';
                        
                        if ($justificado == 1 || $estado === 'aprobado' || $estado === 'aprobada') {
                            $badgeClass = 'badge-pantone-green';
                            $icono = 'fa-check-circle';
                            $texto = 'Aprobado';
                        } elseif ($estado === 'rechazado' || $estado === 'rechazada') {
                            $badgeClass = 'badge-pantone-wine';
                            $icono = 'fa-times-circle';
                            $texto = 'Rechazado';
                        } elseif ($estado === 'requiere_info') {
                            $badgeClass = 'badge-pantone-gold';
                            $icono = 'fa-info-circle';
                            $texto = 'Requiere Info';
                        }
                        
                        return '<span class="badge ' . $badgeClass . '"><i class="fas ' . $icono . ' me-1"></i>' . $texto . '</span>';
                    }
                    ?>
                    
                    <?php if (empty($incidencias)): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>No hay incidencias registradas.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Fuente</th>
                                        <th>Tipo</th>
                                        <th>Tipo de Justificación</th>
                                        <th>Justificación</th>
                                        <th>Min</th>
                                        <th>Estado</th>
                                        <th>Validación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidencias as $item):
                                        $fuente = $item['fuente'] ?? 'asistencia';
                                        $minutosRetardo = $item['minutos_retardo'] ?? null;
                                        $horaEnt = trim((string)($item['hora_entrada'] ?? ''));
                                        $horaSal = trim((string)($item['hora_salida'] ?? ''));
                                        $rowClass = '';
                                        if ($item['tipo_asistencia'] === 'por_definir' || $item['tipo_retraso'] === 'falta') {
                                            $rowClass = 'table-warning';
                                        } elseif ($item['tipo_retraso'] === 'retardo_mayor') {
                                            $rowClass = 'table-danger';
                                        } elseif ($item['tipo_retraso'] === 'retardo_menor') {
                                            $rowClass = 'table-warning';
                                        }
                                        
                                        $sinEntrada = empty($horaEnt);
                                        $sinSalida = empty($horaSal);
                                        $tipoJustifBadge = '';
                                        if ($sinEntrada && $sinSalida) {
                                            $tipoJustifBadge = '<span class="badge bg-danger"><i class="fas fa-user-slash me-1"></i>Sin Entrada ni Salida</span>';
                                        } elseif ($sinEntrada) {
                                            $tipoJustifBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-sign-in-alt me-1"></i>Sin Entrada</span>';
                                        } elseif ($sinSalida) {
                                            $tipoJustifBadge = '<span class="badge bg-info"><i class="fas fa-sign-out-alt me-1"></i>Sin Salida</span>';
                                        } else {
                                            $tipoJustifBadge = '<span class="badge bg-secondary"><i class="fas fa-check me-1"></i>Completo</span>';
                                        }
                                    ?>
                                        <tr class="<?php echo $rowClass; ?>">
                                            <td>
                                                <strong><?php echo date('d/m/Y', strtotime($item['fecha'] ?? '1970-01-01')); ?></strong>
                                                <br><small class="text-muted"><?php echo ucfirst($fuente); ?></small>
                                            </td>
                                            <td>
                                                <?php echo !empty($item['hora_entrada']) ? substr($item['hora_entrada'], 0, 5) : '<span class="text-muted">-</span>'; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($item['hora_salida']) ? substr($item['hora_salida'], 0, 5) : '<span class="text-muted">-</span>'; ?>
                                            </td>
                                            <td>
                                                <?php if ($fuente === 'retardos'): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-stopwatch me-1"></i>Retardo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary"><i class="fas fa-calendar-check me-1"></i>Asistencia</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo getTipoBadgeIncidencia($item); ?>
                                            </td>
                                            <td>
                                                <?php echo $tipoJustifBadge; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $tipoJustifId = $item['tipo_justificacion_id'] ?? null;
                                                $tipoJustifNombre = trim((string)($item['tipo_justificacion_nombre'] ?? ''));
                                                $tipoIncidencia = $item['tipo_retraso'] ?? $item['tipo_asistencia'] ?? $item['tipo_incidencia'] ?? '';
                                                $idIncidencia = $item['id'] ?? $item['asistencia_id'] ?? $item['retardo_id'] ?? null;
                                                $justificado = $item['justificado'] ?? 0;
                                                
                                                // Determinar el texto a mostrar basado en tipo de incidencia
                                                $textoMostrar = '';
                                                $iconoMostrar = 'fa-question-circle';
                                                $claseBadge = 'bg-secondary';
                                                
                                                if (!empty($tipoJustifNombre)) {
                                                    $textoMostrar = $tipoJustifNombre;
                                                    $iconoMostrar = 'fa-check-circle';
                                                    $claseBadge = 'bg-success';
                                                } elseif ($tipoIncidencia === 'comision_entrada') {
                                                    $textoMostrar = 'Comisión Entrada';
                                                    $iconoMostrar = 'fa-sign-in-alt';
                                                    $claseBadge = 'bg-info';
                                                } elseif ($tipoIncidencia === 'comision_salida') {
                                                    $textoMostrar = 'Comisión Salida';
                                                    $iconoMostrar = 'fa-sign-out-alt';
                                                    $claseBadge = 'bg-info';
                                                } elseif ($tipoIncidencia === 'comision_todo_dia') {
                                                    $textoMostrar = 'Comisión Día Completo';
                                                    $iconoMostrar = 'fa-briefcase';
                                                    $claseBadge = 'bg-info';
                                                } elseif ($tipoIncidencia === 'dia_economico') {
                                                    $textoMostrar = 'Día Económico';
                                                    $iconoMostrar = 'fa-calendar-check';
                                                    $claseBadge = 'bg-success';
                                                } elseif ($tipoIncidencia === 'licencia_medica') {
                                                    $textoMostrar = 'Licencia Médica';
                                                    $iconoMostrar = 'fa-user-md';
                                                    $claseBadge = 'bg-danger';
                                                } elseif ($tipoIncidencia === 'vacaciones') {
                                                    $textoMostrar = 'Vacaciones';
                                                    $iconoMostrar = 'fa-umbrella-beach';
                                                    $claseBadge = 'bg-primary';
                                                } elseif ($tipoIncidencia === 'cuidados_parentales') {
                                                    $textoMostrar = 'Cuidados Parentales';
                                                    $iconoMostrar = 'fa-baby';
                                                    $claseBadge = 'bg-pink';
                                                } elseif ($tipoIncidencia === 'retardo_menor') {
                                                    $textoMostrar = 'Retardo Menor';
                                                    $iconoMostrar = 'fa-clock';
                                                    $claseBadge = 'bg-warning text-dark';
                                                } elseif ($tipoIncidencia === 'retardo_mayor') {
                                                    $textoMostrar = 'Retardo Mayor';
                                                    $iconoMostrar = 'fa-exclamation-triangle';
                                                    $claseBadge = 'bg-danger';
                                                } elseif ($tipoIncidencia === 'falta') {
                                                    $textoMostrar = 'Falta';
                                                    $iconoMostrar = 'fa-ban';
                                                    $claseBadge = 'bg-dark';
                                                } else {
                                                    $textoMostrar = 'Sin asignar';
                                                }
                                                ?>
                                                <span class="badge <?php echo $claseBadge; ?>">
                                                    <i class="fas <?php echo $iconoMostrar; ?> me-1"></i><?php echo htmlspecialchars($textoMostrar); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($minutosRetardo) && $minutosRetardo > 0): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-stopwatch me-1"></i><?php echo $minutosRetardo; ?> min
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo getEstadoBadgeIncidencia($item); ?>
                                                <?php if (!empty($item['fecha_validacion_jefe'])): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-user-tie me-1"></i>Jefe: <?php echo date('d/m/Y H:i', strtotime($item['fecha_validacion_jefe'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($item['jefe_validador_nombre'])): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo htmlspecialchars($item['jefe_validador_nombre']); ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($item['validado_por_rh'])): ?>
                                                    <br><small class="text-info">
                                                        <i class="fas fa-building me-1"></i>RH: <?php echo $item['estado_validacion_rh'] ?? 'aprobada'; ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($item['fecha_validacion_rh'])): ?>
                                                    <br><small class="text-info">
                                                        <?php echo date('d/m/Y H:i', strtotime($item['fecha_validacion_rh'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Validación del Jefe
                                                if (!empty($item['estado_validacion_jefe'])): ?>
                                                    <span class="badge bg-success"><i class="fas fa-user-tie me-1"></i>Jefe: <?php echo $item['estado_validacion_jefe']; ?></span>
                                                    <?php if (!empty($item['fecha_validacion_jefe'])): ?>
                                                        <br><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($item['fecha_validacion_jefe'])); ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['jefe_validador_nombre'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['jefe_validador_nombre']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['comentarios_validacion_jefe'])): ?>
                                                        <br><small class="text-muted"><i class="fas fa-comment me-1"></i><?php echo htmlspecialchars($item['comentarios_validacion_jefe']); ?></small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <?php
                                                // Validación de RH
                                                if (!empty($item['validado_por_rh'])): ?>
                                                    <span class="badge bg-info"><i class="fas fa-building me-1"></i>RH: <?php echo $item['estado_validacion_rh'] ?? 'aprobada'; ?></span>
                                                    <?php if (!empty($item['fecha_validacion_rh'])): ?>
                                                        <br><small class="text-info"><?php echo date('d/m/Y H:i', strtotime($item['fecha_validacion_rh'])); ?></small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $comentario = trim((string)($item['comentarios_validacion_jefe'] ?? ''));
                                                $motivo = trim((string)($item['motivo_validacion_jefe'] ?? ''));
                                                $justificacion = trim((string)($item['motivo_justificacion'] ?? ''));
                                                
                                                if (!empty($justificacion)): ?>
                                                    <small><strong>Justificación:</strong> <?php echo htmlspecialchars($justificacion); ?></small><br>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($comentario)): ?>
                                                    <small><strong>Comentario:</strong> <?php echo htmlspecialchars($comentario); ?></small>
                                                <?php elseif (!empty($motivo)): ?>
                                                    <small><strong>Motivo:</strong> <?php echo htmlspecialchars($motivo); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $idIncidencia = $item['id'] ?? $item['asistencia_id'] ?? $item['retardo_id'] ?? null;
                                                $fuente = $item['fuente'] ?? 'asistencia';
                                                $justificado = $item['justificado'] ?? 0;
                                                $puedeSolicitar = true;
                                                
                                                if ($idIncidencia): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="abrirModalJustificarIncidencia(<?php echo $idIncidencia; ?>, 0, <?php echo $puedeSolicitar ? 'true' : 'false'; ?>, '<?php echo $fuente; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php 
                        // Contadores
                        $contadores = [
                            'comision' => 0,
                            'dia_economico' => 0,
                            'vacaciones' => 0,
                            'licencia_medica' => 0,
                            'cuidados' => 0,
                            'aprobado' => 0,
                            'pendiente' => 0,
                            'rechazado' => 0
                        ];
                        
                        foreach ($incidencias as $item) {
                            $fuente = $item['fuente'] ?? 'incidencia';
                            $tipo = $item['tipo_incidencia'] ?? $item['tipo_retraso'] ?? 'normal';
                            $estado = $item['estado_validacion_jefe'] ?? $item['estado_validacion'] ?? 'pendiente';
                            $justificado = $item['justificado'] ?? 0;
                            
                            if ($fuente === 'comision' || strpos($tipo, 'comision') !== false) {
                                $contadores['comision']++;
                            } elseif ($tipo === 'dia_economico') {
                                $contadores['dia_economico']++;
                            } elseif ($tipo === 'vacaciones') {
                                $contadores['vacaciones']++;
                            } elseif ($tipo === 'licencia_medica') {
                                $contadores['licencia_medica']++;
                            } elseif ($tipo === 'cuidados_maternos' || $tipo === 'cuidados_parentales') {
                                $contadores['cuidados']++;
                            }
                            
                            if ($justificado == 1 || $estado === 'aprobado' || $estado === 'aprobada') {
                                $contadores['aprobado']++;
                            } elseif ($estado === 'rechazado' || $estado === 'rechazada') {
                                $contadores['rechazado']++;
                            } else {
                                $contadores['pendiente']++;
                            }
                        }
                        ?>
                        
                        <div class="row mt-3">
                            <div class="col-md-2">
                                <div class="card border-info">
                                    <div class="card-body py-2 px-3 text-center">
                                        <h4 class="mb-0 text-info"><?php echo $contadores['comision']; ?></h4>
                                        <small class="text-muted">Comisiones</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border-success">
                                    <div class="card-body py-2 px-3 text-center">
                                        <h4 class="mb-0 text-success"><?php echo $contadores['dia_economico']; ?></h4>
                                        <small class="text-muted">Días Ec.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border-primary">
                                    <div class="card-body py-2 px-3 text-center">
                                        <h4 class="mb-0 text-primary"><?php echo $contadores['vacaciones']; ?></h4>
                                        <small class="text-muted">Vacaciones</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border-danger">
                                    <div class="card-body py-2 px-3 text-center">
                                        <h4 class="mb-0 text-danger"><?php echo $contadores['licencia_medica']; ?></h4>
                                        <small class="text-muted">Lic. Médica</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border-warning">
                                    <div class="card-body py-2 px-3 text-center">
                                        <h4 class="mb-0 text-warning"><?php echo $contadores['pendiente']; ?></h4>
                                        <small class="text-muted">Pendientes</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border-success">
                                    <div class="card-body py-2 px-3 text-center">
                                        <h4 class="mb-0 text-success"><?php echo $contadores['aprobado']; ?></h4>
                                        <small class="text-muted">Aprobados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Dias Económicos -->
                <div class="tab-pane fade" id="ausencias" role="tabpanel">
                    <?php
                    require_once __DIR__ . '/../../models/Database.php';
                    require_once __DIR__ . '/../../models/DiasEconomicos.php';
                    $diasEcoModel = new DiasEconomicos();
                    $dbEco = Database::getInstance();
                    $diasEcoEmpleado = $diasEcoModel->getByEmpleado($empleado['id'] ?? 0);
                    
                    $periodoInfo = $diasEcoModel->getPeriodoActual();
                    $periodoInicio = $periodoInfo['inicio'];
                    $periodoFin = $periodoInfo['fin'];
                    $diasLimite = $periodoInfo['dias_totales'];
                    
                    // Calcular días usados en el periodo actual
                    $diasUsados = 0;
                    $diasExcedidos = 0;
                    foreach ($diasEcoEmpleado as $de) {
                        if ($de['estatus'] === 'aprobado' && $de['fecha'] >= $periodoInicio && $de['fecha'] <= $periodoFin) {
                            $diasUsados += $de['dias_solicitados'];
                        }
                    }
                    
                    $diasDisponibles = max(0, $diasLimite - $diasUsados);
                    $diasExcedidos = max(0, $diasUsados - $diasLimite);
                    
                    // Obtener info del empleado para antigüedad y plaza
                    $stmtEmp = $dbEco->getConnection()->prepare("SELECT fecha_ingreso, plaza_confianza FROM empleados WHERE id = ?");
                    $stmtEmp->execute([$empleado['id']]);
                    $empInfo = $stmtEmp->fetch();
                    $fechaIngreso = $empInfo['fecha_ingreso'] ?? null;
                    $antiguedad = $fechaIngreso ? floor((strtotime(date('Y-m-d')) - strtotime($fechaIngreso)) / (60 * 60 * 24)) : 0;
                    $puedeSolicitar = $antiguedad >= 180;
                    
                    $plazaConfianza = $empInfo['plaza_confianza'] ?? 0;
                    $esPlazaConfianza = false;
                    if (is_numeric($plazaConfianza)) {
                        $esPlazaConfianza = ((int)$plazaConfianza) === 1;
                    } else {
                        $esPlazaConfianza = in_array(strtolower(trim((string)$plazaConfianza)), ['1', 'si', 'sí', 'true', 'confianza']);
                    }
                    ?>
                    
                    <!-- Resumen de Días Económicos -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-0 shadow-sm" style="border-left: 4px solid #9F2241 !important;">
                                <div class="card-body">
                                    <h5 class="text-pantone-wine mb-3">
                                        <i class="fas fa-calendar-alt me-2"></i>Días Económicos
                                    </h5>
                                    
                                    <!-- Info del Periodo - Solo 3 cajas -->
                                    <div class="row g-3 mb-3">
                                        <div class="col-4">
                                            <div class="text-white rounded p-3 text-center" style="background-color: #9F2241 !important;">
                                                <h2 class="mb-0 text-white fw-bold">9</h2>
                                                <small class="text-white-50">Aplicables</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-white rounded p-3 text-center" style="background-color: #235B4E !important;">
                                                <h2 class="mb-0 text-white fw-bold"><?= $diasUsados ?></h2>
                                                <small class="text-white-50">Disfrutados</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-white rounded p-3 text-center <?= $diasDisponibles > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                <h2 class="mb-0 text-white fw-bold"><?= $diasDisponibles ?></h2>
                                                <small class="text-white-50">Disponibles</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="bg-light rounded p-3 text-center">
                                                <h3 class="mb-0"><?= $antiguedad ?></h3>
                                                <small class="text-muted">Días Antigüedad</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bg-light rounded p-3 text-center">
                                                <?php if ($esPlazaConfianza): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-ban"></i> Plaza de Confianza - No aplica</span>
                                                <?php elseif (!$puedeSolicitar): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Sin antigüedad suficiente</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><i class="fas fa-check"></i> Puede solicitar</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Periodo Actual -->
                                    <div class="alert alert-light border mb-3">
                                        <i class="fas fa-calendar me-2 text-pantone-wine"></i>
                                        <strong>Periodo:</strong> <?= date('d/m/Y', strtotime($periodoInicio)) ?> - <?= date('d/m/Y', strtotime($periodoFin)) ?>
                                        <?php if (!$puedeSolicitar): ?>
                                            <span class="badge bg-danger ms-2">Menos de 6 meses</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Reglas -->
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="border rounded p-2 text-center">
                                                <span class="badge badge-pantone-gold mb-1">Modalidad A</span>
                                                <p class="mb-0 small">3 días continuos<br><span class="text-muted">Espera: 1 mes</span></p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-2 text-center">
                                                <span class="badge badge-pantone-gold mb-1">Modalidad B</span>
                                                <p class="mb-0 small">2 días continuos<br><span class="text-muted">Espera: 15 días</span></p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-2 text-center">
                                                <span class="badge badge-pantone-gold mb-1">Modalidad C</span>
                                                <p class="mb-0 small">1 día<br><span class="text-muted">Espera: 7 días</span></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Fundamento Legal -->
                                    <div class="alert alert-light border mt-3 mb-0">
                                        <h6 class="text-pantone-wine mb-2">
                                            <i class="fas fa-gavel me-2"></i>Fundamento Legal
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Artículo 52 Fracción III - RCGTP SEP:</strong></p>
                                                <small class="text-muted">
                                                    Licencias con goce de sueldo: A los trabajadores que tengan más de seis meses de servicios se les concederá licencia con goce de sueldo hasta por <strong>nueve días</strong> en el período de vigencia del contrato.
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Modalidades de Uso:</strong></p>
                                                <small class="text-muted">
                                                    Los trabajadores Enjoyarán hasta <strong>9 días económicos</strong> por año fiscal, 
                                                    solicitados con 1 día de anticipación, en las siguientes modalidades:
                                                </small>
                                                <ul class="small text-muted mt-1">
                                                    <li><strong>Modalidad A:</strong> 3 días continuos, espera 30 días</li>
                                                    <li><strong>Modalidad B:</strong> 2 días continuos, espera 15 días</li>
                                                    <li><strong>Modalidad C:</strong> 1 día, espera 7 días</li>
                                                </ul>
                                                <small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    No aplicable a plazas de confianza ni en lunes o viernes.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Separar días aprobados de pendientes/rechazados
                    $diasAprobados = [];
                    $diasPendientes = [];
                    foreach ($diasEcoEmpleado as $de) {
                        if ($de['estatus'] === 'aprobado') {
                            $diasAprobados[] = $de;
                        } else {
                            $diasPendientes[] = $de;
                        }
                    }
                    
                    // Calcular días usados (solo los primeros 9 del periodo)
                    $contadorDias = 0;
                    $diasUsadosCalculo = 0;
                    $limiteDias = 9;
                    
                    // Reordenar por fecha ASC para calcular correctamente
                    usort($diasAprobados, function($a, $b) {
                        return strcmp($a['fecha'], $b['fecha']);
                    });
                    
                    foreach ($diasAprobados as $de) {
                        if ($contadorDias < $limiteDias) {
                            $diasRestantesEnLimite = $limiteDias - $contadorDias;
                            $diasUsadosCalculo += min((int)$de['dias_solicitados'], $diasRestantesEnLimite);
                            $contadorDias += (int)$de['dias_solicitados'];
                        }
                    }
                    
                    $diasUsados = $diasUsadosCalculo;
                    $diasDisponibles = max(0, $limiteDias - $diasUsados);
                    ?>
                    
                    <!-- Periodo Info -->
                    <div class="alert alert-light border mb-3">
                        <i class="fas fa-calendar me-2 text-pantone-wine"></i>
                        <strong>Periodo Escolar:</strong> <?= date('d/m/Y', strtotime($periodoInicio)) ?> - <?= date('d/m/Y', strtotime($periodoFin)) ?>
                    </div>
                    
                    <!-- Fundamento Legal -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body bg-light">
                            <h6 class="text-pantone-wine mb-2">
                                <i class="fas fa-gavel me-2"></i>Fundamento Legal
                            </h6>
                            <div class="small">
                                <p class="mb-1"><strong>Artículo 52 Fracción III - RCGTP SEP</strong> - Licencias con goce de sueldo por exámenes médicos prenupciales o postnatales, cambio de domicilio y defunción de familiares en primer grado.</p>
                                <p class="mb-0"><strong>Período:</strong> Del 16 de julio al 15 de julio del siguiente año (Calendario Escolar SEP).</p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($diasEcoEmpleado)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-1"></i> No hay solicitudes de días económicos registradas.
                        </div>
                    <?php else: ?>
                        
                        <!-- Tabla de Días Aprobados -->
                        <div class="mb-4">
                            <h6 class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Días Aprobados Legítimos (Días 1-9) (<?= count($diasAprobados) ?>)</h6>
                            <?php if (empty($diasAprobados)): ?>
                                <div class="alert alert-light border">
                                    <i class="fas fa-inbox me-2"></i>Sin días aprobados en este periodo
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm" style="border: 2px solid #235B4E; border-radius: 8px; overflow: hidden;">
                                        <thead style="background:#235B4E;color:white;">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Días</th>
                                                <th>Modalidad</th>
                                                <th>Motivo</th>
                                                <th>Validación</th>
                                                <th>Asistencia</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $modalidadLabels = [
                                                'A' => '<span class="badge" style="background:#17A2B8;color:white;">A</span>',
                                                'B' => '<span class="badge" style="background:#6F42C1;color:white;">B</span>',
                                                'C' => '<span class="badge" style="background:#00897B;color:white;">C</span>',
                                            ];
                                            foreach ($diasAprobados as $de): 
                                                $modalidad = $de['modalidad'] ?? '';
                                                $diasSolicitados = (int)($de['dias_solicitados'] ?? 1);
                                                
                                                // Inferir modalidad según días
                                                if (empty($modalidad)) {
                                                    if ($diasSolicitados >= 3) {
                                                        $modalidad = 'A';
                                                    } elseif ($diasSolicitados == 2) {
                                                        $modalidad = 'B';
                                                    } else {
                                                        $modalidad = 'C';
                                                    }
                                                }
                                                
                                                $modalidadTexto = match($modalidad) {
                                                    'A' => '3 días - Espera 1 mes',
                                                    'B' => '2 días - Espera 15 días',
                                                    'C' => '1 día - Espera 7 días',
                                                    default => 'N/A'
                                                };
                                                
                                                // Verificar si los días económicos están justificados en asistencia
                                                $fechaFinEco = date('Y-m-d', strtotime($de['fecha'] . ' + ' . ($de['dias_solicitados'] - 1) . ' days'));
                                                $stmtAsistencia = $dbEco->getConnection()->prepare("
                                                    SELECT COUNT(*) as total,
                                                           SUM(CASE WHEN tipo_asistencia = 'dia_economico' THEN 1 ELSE 0 END) as justificados
                                                    FROM asistencia 
                                                    WHERE empleado_id = ? 
                                                    AND fecha BETWEEN ? AND ?
                                                ");
                                                $stmtAsistencia->execute([$empleado['id'], $de['fecha'], $fechaFinEco]);
                                                $asistenciaInfo = $stmtAsistencia->fetch();
                                                
                                                // Verificar si hay retardos en el periodo
                                                $stmtRetardos = $dbEco->getConnection()->prepare("
                                                    SELECT COUNT(*) as total,
                                                           GROUP_CONCAT(DATE_FORMAT(fecha, '%d/%m') SEPARATOR ', ') as fechas
                                                    FROM retardos 
                                                    WHERE empleado_id = ? 
                                                    AND fecha BETWEEN ? AND ?
                                                ");
                                                $stmtRetardos->execute([$empleado['id'], $de['fecha'], $fechaFinEco]);
                                                $retardosInfo = $stmtRetardos->fetch();
                                                
                                                $totalDias = (int)($de['dias_solicitados'] ?? 1);
                                                $justificados = (int)($asistenciaInfo['justificados'] ?? 0);
                                                if ($justificados >= $totalDias) {
                                                    $asistenciaBadge = '<span class="badge bg-success"><i class="fas fa-check"></i> ' . $justificados . '/' . $totalDias . '</span>';
                                                } elseif ($justificados > 0) {
                                                    $asistenciaBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> ' . $justificados . '/' . $totalDias . '</span>';
                                                } else {
                                                    $asistenciaBadge = '<span class="badge bg-danger"><i class="fas fa-times"></i> Pendiente</span>';
                                                }
                                                
                                                if ($retardosInfo['total'] > 0) {
                                                    $retardosBadge = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> ' . $retardosInfo['total'] . ' (' . $retardosInfo['fechas'] . ')</span>';
                                                } else {
                                                    $retardosBadge = '<span class="badge bg-success"><i class="fas fa-check"></i> Sin retardos</span>';
                                                }
                                            ?>
                                                <tr class="table-success">
                                                    <td><?= date('d/m/Y', strtotime($de['fecha'] ?? '')) ?></td>
                                                    <td><strong><?= $de['dias_solicitados'] ?? 0 ?></strong></td>
                                                    <td>
                                                        <?= $modalidadLabels[$de['modalidad']] ?? '<span class="badge bg-secondary">N/A</span>' ?>
                                                        <br><small class="text-muted"><?= $modalidadTexto ?></small>
                                                    </td>
                                                    <td>
                                                        <small><?= htmlspecialchars($de['motivo'] ?? 'Día económico') ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Aprobado</span>
                                                        <br><small class="text-muted"><i class="fas fa-user-tie me-1"></i>Jefe</small>
                                                        <br><small class="text-info"><i class="fas fa-building me-1"></i>RH</small>
                                                        <?php if (!empty($de['fecha_aprobacion'])): ?>
                                                            <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($de['fecha_aprobacion'])) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $asistenciaBadge ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalDiaEco<?= $de['id'] ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <!-- Modal Detalle Aprobado -->
                                                <div class="modal fade" id="modalDiaEco<?= $de['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header" style="background: linear-gradient(135deg, #235B4E 0%, #1a3d33 100%); color: white;">
                                                                <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i>Día Económico #<?= $de['id'] ?> (Aprobado)</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Fecha:</strong><br><?= date('d/m/Y', strtotime($de['fecha'] ?? '')) ?>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Días:</strong><br><?= $de['dias_solicitados'] ?? 0 ?>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Modalidad:</strong><br><?= $modalidadTexto ?>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Fecha Fin:</strong><br><?= date('d/m/Y', strtotime($fechaFinEco)) ?>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Asistencia:</strong><br><?= $asistenciaBadge ?>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Retardos:</strong><br><?= $retardosBadge ?>
                                                                    </div>
                                                                    <div class="col-12 mb-3">
                                                                        <strong>Motivo:</strong><br><?= nl2br(htmlspecialchars($de['motivo'] ?? 'Sin motivo')) ?>
                                                                    </div>
                                                                    <?php if (!empty($de['comentarios_aprobacion'])): ?>
                                                                    <div class="col-12 mb-3">
                                                                        <strong>Comentarios:</strong><br><?= nl2br(htmlspecialchars($de['comentarios_aprobacion'])) ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tabla de Días Pendientes/Rechazados -->
                        <div>
                            <h6 class="text-warning mb-3"><i class="fas fa-clock me-2"></i>Pendientes / Rechazados (<?= count($diasPendientes) ?>)</h6>
                            <?php if (empty($diasPendientes)): ?>
                                <div class="alert alert-light border">
                                    <i class="fas fa-inbox me-2"></i>Sin solicitudes pendientes o rechazadas
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm" style="border: 2px solid #BC955C; border-radius: 8px; overflow: hidden;">
                                        <thead style="background:#BC955C;color:white;">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Días</th>
                                                <th>Modalidad</th>
                                                <th>Estatus</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($diasPendientes as $de): 
                                                $modalidadTexto = match($de['modalidad'] ?? '') {
                                                    'A' => '3 días - Espera 1 mes',
                                                    'B' => '2 días - Espera 15 días',
                                                    'C' => '1 día - Espera 7 días',
                                                    default => 'N/A'
                                                };
                                                $estatusBadge = match($de['estatus'] ?? '') {
                                                    'pendiente' => '<span class="badge" style="background:#BC955C;color:white;">Pendiente</span>',
                                                    'rechazado' => '<span class="badge bg-danger">Rechazado</span>',
                                                    default => '<span class="badge bg-secondary">' . ($de['estatus'] ?? 'N/A') . '</span>'
                                                };
                                            ?>
                                                <tr style="<?= ($de['estatus'] ?? '') === 'rechazado' ? 'background:#f8d7da;' : 'background:#fff3cd;' ?>">
                                                    <td><?= date('d/m/Y', strtotime($de['fecha'] ?? '')) ?></td>
                                                    <td><strong><?= $de['dias_solicitados'] ?? 0 ?></strong></td>
                                                    <td>
                                                        <?= $modalidadLabels[$de['modalidad']] ?? '<span class="badge bg-secondary">N/A</span>' ?>
                                                        <br><small class="text-muted"><?= $modalidadTexto ?></small>
                                                    </td>
                                                    <td><?= $estatusBadge ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalDiaEcoPen<?= $de['id'] ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <!-- Modal Detalle Pendiente -->
                                                <div class="modal fade" id="modalDiaEcoPen<?= $de['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header" style="background: linear-gradient(135deg, #BC955C 0%, #8a6d42 100%); color: white;">
                                                                <h5 class="modal-title"><i class="fas fa-calendar-clock me-2"></i>Día Económico #<?= $de['id'] ?> (<?= ucfirst($de['estatus'] ?? 'pendiente') ?>)</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Fecha:</strong><br><?= date('d/m/Y', strtotime($de['fecha'] ?? '')) ?>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Días:</strong><br><?= $de['dias_solicitados'] ?? 0 ?>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Modalidad:</strong><br><?= $modalidadTexto ?>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <strong>Estatus:</strong><br><?= $estatusBadge ?>
                                                                    </div>
                                                                    <div class="col-12 mb-3">
                                                                        <strong>Motivo:</strong><br><?= nl2br(htmlspecialchars($de['motivo'] ?? 'Sin motivo')) ?>
                                                                    </div>
                                                                    <?php if (!empty($de['motivo_rechazo'])): ?>
                                                                    <div class="col-12 mb-3">
                                                                        <strong>Motivo de Rechazo:</strong><br><?= nl2br(htmlspecialchars($de['motivo_rechazo'])) ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Sanciones -->
                <div class="tab-pane fade" id="sanciones" role="tabpanel">
                    <h5 class="mb-3">Historial de Sanciones</h5>
                    <?php if (empty($sanciones)): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i> El empleado no tiene sanciones.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Fecha Inicio</th>
                                        <th>Días</th>
                                        <th>Motivo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sanciones as $sancion): ?>
                                        <tr>
                                            <td>
                                                <span class="badge badge-pantone-wine"><?php echo ucfirst(str_replace('_', ' ', $sancion['tipo_sancion'] ?? $sancion['tipo'] ?? 'N/A')); ?></span>
                                                <?php if (!empty($sancion['tipo_retardo'])): ?>
                                                <br><small class="text-muted"><?php echo str_replace('_', ' ', $sancion['tipo_retardo']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo !empty($sancion['fecha_inicio']) ? date('d/m/Y', strtotime($sancion['fecha_inicio'])) : 'N/A'; ?></td>
                                            <td><?php echo $sancion['dias'] ?? 0; ?></td>
                                            <td><?php echo htmlspecialchars($sancion['motivo'] ?? ''); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalSancion<?php echo $sancion['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <!-- Modal para ver sanción -->
                                        <div class="modal fade" id="modalSancion<?php echo $sancion['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Detalle de Sanción #<?php echo $sancion['id']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong>Tipo de Sanción:</strong> <span class="badge bg-danger"><?php echo ucfirst(str_replace('_', ' ', $sancion['tipo_sancion'] ?? $sancion['tipo'] ?? 'N/A')); ?></span></p>
                                                        <?php if (!empty($sancion['tipo_retardo'])): ?>
                                                        <p><strong>Tipo de Retardo:</strong> <span class="badge badge-pantone-gold"><?php echo str_replace('_', ' ', $sancion['tipo_retardo']); ?></span></p>
                                                        <?php endif; ?>
                                                        <p><strong>Fecha inicio:</strong> <?php echo !empty($sancion['fecha_inicio']) ? date('d/m/Y', strtotime($sancion['fecha_inicio'])) : 'N/A'; ?></p>
                                                        <p><strong>Días:</strong> <?php echo intval($sancion['dias'] ?? 0); ?></p>
                                                        <p><strong>Motivo:</strong><br><?php echo nl2br(htmlspecialchars($sancion['motivo'] ?? '')); ?></p>
                                                        <p><strong>Creado por:</strong> <?php echo $sancion['creado_por'] ?? '-'; ?></p>
                                                        <p><strong>Fecha creación:</strong> <?php echo $sancion['created_at'] ?? 'N/A'; ?></p>
                                                    </div>
                                                    <div class="modal-footer modal-footer-custom">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cerrar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Horarios y Asistencia -->
                <?php if ($puedeVerHorarios): ?>
                <div class="tab-pane fade" id="horarios" role="tabpanel">
                    <div id="contenido-tab-horarios" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Analizando registros de asistencia...</p>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<style>
body:not(.dark-theme) .badge-pantone-gold {
    background-color: #BC955C !important;
    color: #212529 !important;
}
body.dark-theme .badge-pantone-gold {
    background-color: rgba(188, 149, 92, 0.6) !important;
    color: #ffffff !important;
    border: 1px solid #BC955C;
}
body.dark-theme .badge-pantone-green {
    background-color: #235B4E !important;
    color: #ffffff !important;
}
body.dark-theme .badge-pantone-red {
    background-color: #9F2241 !important;
    color: #ffffff !important;
}
body.dark-theme .badge-pantone-wine {
    background-color: #691C32 !important;
    color: #ffffff !important;
}
body.dark-theme .empleado-tabs-content {
    background-color: #7a7d7d;
    color: #ffffff;
}
body.dark-theme .modal-footer-custom {
    background-color: #6F7271;
    border-top-color: #888a8a;
}
body.dark-theme .table-custom-dark {
    background-color: #7a7d7d;
    color: #ffffff;
    border-color: #888a8a;
}
body.dark-theme .table-custom-dark thead {
    background-color: #767978;
    color: #ffffff;
}
body.dark-theme .text-dark-custom {
    color: #ffffff !important;
}
</style>

<script>
let horariosCargados = false;
let currentEmpleadoId = null;

// Activar pestaña según ancla en URL
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tabId = hash.replace('#', '');
        const tabElement = document.querySelector('[data-bs-target="#' + tabId + '"]');
        if (tabElement) {
            new bootstrap.Tab(tabElement).show();
        }
    }
});

function mostrarError(mensaje) {
    const modalHtml = `
        <div class="modal fade" id="modalError" tabindex="-1" aria-labelledby="modalErrorLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalErrorLabel">
                            <i class="fas fa-exclamation-circle me-2"></i>Error
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${mensaje}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const existingModal = document.getElementById('modalError');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('modalError'));
    modal.show();
    
    document.getElementById('modalError').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function cargarHorariosAsistencia(empleadoId, forceReload = false) {
    console.log('cargarHorariosAsistencia llamado - empleadoId:', empleadoId, 'forceReload:', forceReload, 'horariosCargados:', horariosCargados);
    
    if (!empleadoId) {
        console.error('No se proporcionó empleadoId');
        return;
    }
    
    // Siempre forzar recarga cuando se llama explícitamente
    if (forceReload || !horariosCargados) {
        horariosCargados = false;
    }
    
    // Si ya está cargado y no se fuerza la recarga, salir
    if (horariosCargados && !forceReload) {
        console.log('Ya cargado, saliendo sin recargar');
        return;
    }
    
    // Forzar recarga limpia
    if (forceReload) {
        horariosCargados = false;
    }
    
    currentEmpleadoId = parseInt(empleadoId);
    
    const container = document.getElementById('contenido-tab-horarios');
    if (!container) {
        console.error('No se encontró el contenedor de horarios');
        return;
    }
    
    const baseUrl = '<?= rtrim(BASE_URL, '/') ?>';
    
    // Siempre mostrar indicador de carga
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    
    fetch(`${baseUrl}/empleados/${empleadoId}/horarios-asistencia`)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            horariosCargados = true;
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="alert alert-danger">Error al cargar el historial de horarios.</div>';
        });
}

function submitAsignacion(formId, url) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    let empleadoId = formData.get('empleado_id');
    
    console.log('submitAsignacion - formId:', formId, 'empleadoId del form:', empleadoId);
    
    // Inyectar token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => { throw new Error(text || response.statusText) });
        }
        return response.json();
    })
    .then(data => {
        if(data.success) {
            // Cerrar modal
            const modalId = formId === 'formAsignarHorario' ? 'modalAsignarHorario' : (formId === 'formEditarAsignacion' ? 'modalEditarAsignacion' : 'modalAsignarCiclo');
            const modalEl = document.getElementById(modalId);
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            alert(data.message);
            
            // Recargar la página para asegurar que todo se actualice correctamente
            // Esto es más confiable que recargar solo la pestaña
            window.location.reload();
        } else {
            mostrarError('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al procesar la solicitud: ' + error.message);
    });
}

// Funciones para Editar/Eliminar Asignaciones
let modalEditar;

function abrirModalEditar(data) {
    const modalEl = document.getElementById('modalEditarAsignacion');
    if (!modalEl) return;
    
    modalEditar = new bootstrap.Modal(modalEl);
    
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_tipo').value = data.tipo;
    document.getElementById('edit_fecha_inicio').value = data.fecha_inicio;
    document.getElementById('edit_fecha_fin').value = data.fecha_fin || '';
    
    toggleEditTipo(); // Ajustar visibilidad de selects
    
    if (data.tipo === 'horario') {
        document.getElementById('edit_item_horario').value = data.item_id;
    } else {
        document.getElementById('edit_item_ciclo').value = data.item_id;
    }
    
    modalEditar.show();
}

function toggleEditTipo() {
    const tipo = document.getElementById('edit_tipo').value;
    const divHorario = document.getElementById('div_edit_horario');
    const divCiclo = document.getElementById('div_edit_ciclo');
    const selHorario = document.getElementById('edit_item_horario');
    const selCiclo = document.getElementById('edit_item_ciclo');
    
    if (tipo === 'horario') {
        divHorario.style.display = 'block';
        divCiclo.style.display = 'none';
        selHorario.disabled = false;
        selCiclo.disabled = true;
    } else {
        divHorario.style.display = 'none';
        divCiclo.style.display = 'block';
        selHorario.disabled = true;
        selCiclo.disabled = false;
    }
}

function submitEditarAsignacion() {
    submitAsignacion('formEditarAsignacion', '<?= BASE_URL ?>/horarios/editar-asignacion');
}

function eliminarAsignacion(id, empleadoId) {
    if (!confirm('¿Está seguro de eliminar esta asignación? Esto afectará el historial.')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('ajax', 1);
    
    // Inyectar token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) formData.append('csrf_token', csrfToken);

    fetch('<?= BASE_URL ?>/horarios/eliminar-asignacion', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            horariosCargados = false;
            cargarHorariosAsistencia(empleadoId, true);
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Función para ver detalle semanal del horario
function verDetalleHorario(asig) {
    console.log('Datos recibidos:', asig);
    
    const isDarkTheme = document.body.classList.contains('dark-theme');
    const textColor = isDarkTheme ? '#ffffff !important' : '#212529 !important';
    const bgColor = isDarkTheme ? '#333333 !important' : '#ffffff !important';
    const headerBg = isDarkTheme ? '#3d3d3d !important' : '#f8f9fa !important';
    const tableHeaderBg = isDarkTheme ? '#3d3d3d !important' : '#e9ecef !important';
    const borderColor = isDarkTheme ? '#4d4d4d !important' : '#dee2e6 !important';
    
    // Si detalle es string, convertir a array
    if (asig.detalle && typeof asig.detalle === 'string') {
        try {
            asig.detalle = JSON.parse(asig.detalle);
        } catch(e) {
            asig.detalle = [];
        }
    }
    
    const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    let html = `
        <div class="row mb-3" style="color: ${isDarkTheme ? '#ffffff' : '#212529'};">
            <div class="col-md-6">
                <strong>Tipo:</strong> ${asig.tipo_asignacion === 'CICLO' ? 'Ciclo' : 'Horario Fijo'}<br>
                <strong>Nombre:</strong> ${asig.nombre || 'N/A'}
            </div>
            <div class="col-md-6">
                <strong>Clasificación:</strong> ${asig.tipo_horario || 'N/A'}<br>
                <strong>Estado:</strong> ${asig.estado || 'N/A'}
            </div>
        </div>
        <div class="row mb-3" style="color: ${isDarkTheme ? '#ffffff' : '#212529'};">
            <div class="col-md-4">
                <strong>Hora Entrada:</strong> ${asig.hora_entrada ? asig.hora_entrada.substring(0,5) : '-'}<br>
                <strong>Hora Salida:</strong> ${asig.hora_salida ? asig.hora_salida.substring(0,5) : '-'}
            </div>
            <div class="col-md-4">
                <strong>Total Semanal:</strong> ${asig.total_horas_semanales || 0} hrs
            </div>
            <div class="col-md-4">
                <strong>Vigencia:</strong><br>
                Desde: ${asig.fecha_inicio || 'N/A'}<br>
                Hasta: ${asig.fecha_fin || 'Indefinido'}
            </div>
        </div>
        <h6 class="border-bottom pb-2 mb-3 text-dark-custom">Calendario Semanal</h6>
        <table class="table table-bordered table-sm table-custom-dark">
            <thead class="table-custom-dark">
                <tr>
                    <th>Día</th>
                    <th>Hora Inicio</th>
                    <th>Hora Fin</th>
                    <th>Horas</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    if (asig.detalle && asig.detalle.length > 0) {
        asig.detalle.forEach(function(det) {
            const diaIdx = det.dia !== undefined ? parseInt(det.dia) : 0;
            const nombreDia = dias[diaIdx] || 'Día ' + det.dia;
            const horaInicio = det.hora_inicio ? det.hora_inicio.substring(0,5) : '-';
            const horaFin = det.hora_fin ? det.hora_fin.substring(0,5) : '-';
            
            let horas = 0;
            if (det.hora_inicio && det.hora_fin) {
                const inicio = new Date('2000-01-01 ' + det.hora_inicio);
                let fin = new Date('2000-01-01 ' + det.hora_fin);
                if (fin < inicio) fin = new Date('2000-01-02 ' + det.hora_fin);
                horas = (fin - inicio) / (1000 * 60 * 60);
            }
            
            html += `
                <tr style="color: ${textColor};">
                    <td style="color: ${isDarkTheme ? '#ffffff' : '#212529'};"><strong>${nombreDia}</strong></td>
                    <td class="font-monospace" style="color: ${isDarkTheme ? '#ffffff' : '#212529'};">${horaInicio}</td>
                    <td class="font-monospace" style="color: ${isDarkTheme ? '#ffffff' : '#212529'};">${horaFin}</td>
                    <td class="font-monospace" style="color: ${isDarkTheme ? '#ffffff' : '#212529'};">${horas.toFixed(1)} hrs</td>
                </tr>
            `;
        });
    } else {
        html += `
            <tr style="color: ${isDarkTheme ? '#ffffff' : '#212529'};">
                <td colspan="4" class="text-center text-muted">Sin detalle disponible</td>
            </tr>
        `;
    }
    
    html += `
            </tbody>
        </table>
    `;
    
    document.getElementById('detalleHorarioContent').innerHTML = html;
    
    // Mostrar modal
    const modalEl = document.getElementById('modalDetalleHorario');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

</script>

<!-- Modal para justificar incidencia (Comisiones tab) -->
<div class="modal fade" id="modalJustificarComisiones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalJustificarTitulo">Justificar Incidencia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="info_tipo_incidencia" class="alert alert-info mb-3 d-none">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="texto_tipo_incidencia"></span>
                </div>
                <form id="formJustificarIncidencia">
                    <input type="hidden" id="asistencia_id_eco" name="asistencia_id">
                    <input type="hidden" id="empleado_id_eco" name="empleado_id">
                    <input type="hidden" id="fecha_asistencia_eco" name="fecha_asistencia">
                    <input type="hidden" id="tipo_incidencia_original" name="tipo_incidencia_original">
                    <input type="hidden" id="tipo_justificacion_catalogo_id" name="tipo_justificacion_id">
                    <input type="hidden" id="fundamento_tipo_seleccionado" name="fundamento_tipo">
                    
                    <!-- Selección de tipo de justificación con búsqueda -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Tipo de Justificación *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="busqueda_tipo_justificacion" 
                                   list="lista_tipos_justificacion" 
                                   placeholder="Buscar tipo de justificación..."
                                   autocomplete="off">
                            <datalist id="lista_tipos_justificacion"></datalist>
                        </div>
                        <div class="form-text">Escriba para buscar o seleccione de la lista</div>
                        <input type="hidden" id="tipo_justificacion_seleccionado" name="tipo_justificacion_seleccionado">
                        <div id="opciones_justificacion" class="mt-2"></div>
                    </div>
                    <div id="datos_requeridos_tipo" class="alert alert-secondary d-none"></div>
                    
                    <!-- Campos para Día Económico -->
                    <div id="campos_dia_economico" class="d-none">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Reglas para día económico:</h6>
                            <ul class="mb-0 small">
                                <li>Antigüedad mínima: más de 6 meses y 1 día</li>
                                <li>Solicitar con mínimo 1 día de anticipación</li>
                                <li>No se permiten solicitudes en lunes ni viernes</li>
                                <li>Plazas de confianza: no aplican</li>
                                <li>Espera por último disfrute aprobado: A=30 días, B=15 días, C=7 días</li>
                            </ul>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Días solicitados *</label>
                                <select class="form-select" id="dias_solicitados" name="dias_solicitados" onchange="validarJustificacion()">
                                    <option value="">Seleccionar...</option>
                                    <option value="1">1 día (Modalidad C)</option>
                                    <option value="2">2 días (Modalidad B)</option>
                                    <option value="3">3 días (Modalidad A)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="fecha_dia_economico" name="fecha_dia_economico" onchange="validarJustificacion()">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campos para Licencia Médica -->
                    <div id="campos_licencia_medica" class="d-none">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-user-md"></i> Licencia Médica:</h6>
                            <small>Deberá presentar el certificado médico original en un plazo de 5 días hábiles.</small>
                        </div>
                        <div id="info_antiguedad_licencia"></div>
                        <div class="row">
                            <div class="col-md-6 mb-3" id="wrap_folio_licencia">
                                <label class="form-label">No. de Folio *</label>
                                <input type="text" class="form-control" id="folio_licencia" name="folio_licencia" placeholder="Número de folio" oninput="validarJustificacion()">
                            </div>
                            <div class="col-md-3 mb-3" id="wrap_dias_licencia">
                                <label class="form-label">Días Otorgados *</label>
                                <input type="number" class="form-control" id="dias_licencia" name="dias_otorgados" min="1" placeholder="Días" oninput="calcularFechaFinLicencia()">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fecha de Inicio *</label>
                                <input type="date" class="form-control" id="fecha_inicio_licencia" name="fecha_inicio_licencia" onchange="calcularFechaFinLicencia()">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha Fin *</label>
                                <input type="date" class="form-control" id="fecha_fin_licencia" name="fecha_fin_licencia" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Diagnóstico *</label>
                                <input type="text" class="form-control" id="diagnostico_licencia" name="diagnostico" placeholder="Diagnóstico médico" oninput="validarJustificacion()">
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    function calcularFechaFinLicencia() {
                        const diasInput = document.getElementById('dias_licencia');
                        const fechaInicioInput = document.getElementById('fecha_inicio_licencia');
                        const fechaFinInput = document.getElementById('fecha_fin_licencia');
                        
                        if (!diasInput || !fechaInicioInput || !fechaFinInput) return;
                        
                        const dias = parseInt(diasInput.value);
                        const fechaInicio = fechaInicioInput.value;
                        
                        if (!dias || !fechaInicio) return;
                        
                        const [year, month, day] = fechaInicio.split('-').map(Number);
                        const fecha = new Date(year, month - 1, day);
                        fecha.setDate(fecha.getDate() + (dias - 1));
                        
                        const y = fecha.getFullYear();
                        const m = String(fecha.getMonth() + 1).padStart(2, '0');
                        const d = String(fecha.getDate()).padStart(2, '0');
                        fechaFinInput.value = y + '-' + m + '-' + d;
                    }
                    </script>
                    
                    <!-- Campos para Comisión -->
                    <div id="campos_comision" class="d-none">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lugar de la Comisión *</label>
                                <input type="text" class="form-control" id="lugar_comision" name="lugar_comision" placeholder="Lugar de la comisión" oninput="validarJustificacion()">
                            </div>
                            <?php if (empty($esUsuario) || $rolActual === 'admin' || $rolActual === 'superadmin'): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Motivo *</label>
                                <input type="text" class="form-control" id="motivo_comision" name="motivo_comision" placeholder="Motivo de la comisión" oninput="validarJustificacion()">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fundamento Legal</label>
                            <textarea class="form-control" id="fundamento_comision" name="fundamento_comision" rows="2" readonly>ART. 2 - DISPOSICIONES EN MATERIA DE CONTROL - BASE 9
NORMAS GENERALES, PRINCIPIOS Y ELEMENTOS DE CONTROL INTERNO, PUNTO 12 DEL MANUAL ADMINISTRATIVO DE APLICACIÓN GENERAL EN MATERIA DE CONTROL INTERNO.</textarea>
                        </div>
                    </div>
                    
                    <div id="validacion_resultado" class="alert d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarJustificacion" onclick="guardarJustificacionIncidencia()" disabled>
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let tipoJustificacionSeleccionada = '';
let diasEconomicosDisponibles = 0;
let puedeSolicitarDiasEco = false;
let tipoJustificacionCatalogoId = null;
let tiposCatalogoOpciones = [];

function normalizarTextoTipo(valor) {
    return (valor || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function mapearTipoCatalogo(tipoCatalogo, tipoIncidencia) {
    const tipoDb = normalizarTextoTipo(tipoCatalogo?.tipo_incidencia || '');
    if (!tipoDb) {
        return '';
    }
    // Para retardos: solo mostrar retardo
    if (tipoIncidencia === 'retardo') {
        return tipoDb === 'retardo' ? 'retardo' : '';
    }
    // Para faltas: solo mostrar falta
    if (tipoIncidencia === 'falta') {
        return tipoDb === 'falta' ? 'falta' : '';
    }
    // Para comisiones específicas: mostrar la que coincida
    if (['comision_entrada', 'comision_salida', 'comision_todo_dia'].includes(tipoDb)) {
        return tipoDb;
    }
    // Para comisiones genéricas o "por_definir": mapear al tipo del registro
    if (['comision', 'por_definir', 'comision_todo_dia'].includes(tipoDb)) {
        // Si el registro tiene tipo específico, devolverlo
        if (['comision_entrada', 'comision_salida', 'comision_todo_dia'].includes(tipoIncidencia)) {
            return tipoIncidencia;
        }
        return 'comision_todo_dia'; // default
    }
    // Para otros tipos (dia_economico, licencia_medica, vacaciones, cuidados_parentales)
    if (['dia_economico', 'licencia_medica', 'vacaciones', 'cuidados_parentales'].includes(tipoDb)) {
        return tipoDb;
    }
    return '';
}

function construirOpcionesCatalogo(tiposCatalogo, tipoIncidencia, esRetardo = false) {
    const usados = new Set();
    const opciones = [];

    // TODAS las opciones de justificación - mostrar siempre
    const todasLasOpciones = [
        // Comisiones
        { clave: 'comision_entrada', nombre: 'Comisión Entrada', icono: 'fa-sign-in-alt', ayuda: 'Justificar hora de entrada' },
        { clave: 'comision_salida', nombre: 'Comisión Salida', icono: 'fa-sign-out-alt', ayuda: 'Justificar hora de salida' },
        { clave: 'comision_todo_dia', nombre: 'Comisión Día Completo', icono: 'fa-briefcase', ayuda: 'Sin entrada ni salida' },
        // Justificaciones
        { clave: 'dia_economico', nombre: 'Día Económico', icono: 'fa-calendar-check', ayuda: 'Solicitud de día económico' },
        { clave: 'licencia_medica', nombre: 'Licencia Médica', icono: 'fa-user-md', ayuda: 'Enfermedad o accidente' },
        { clave: 'vacaciones', nombre: 'Vacaciones', icono: 'fa-umbrella-beach', ayuda: 'Solicitud de vacaciones' },
        { clave: 'cuidados_maternos', nombre: 'Cuidados Maternos', icono: 'fa-baby-carriage', ayuda: 'Cuidados maternos' },
        { clave: 'cuidados_paternos', nombre: 'Cuidados Paternos', icono: 'fa-baby', ayuda: 'Cuidados paternos' },
        { clave: 'cuidados_parentales', nombre: 'Cuidados Parentales', icono: 'fa-child', ayuda: 'Cuidados parentales' },
        { clave: 'constancia_tiempo', nombre: 'Constancia de Tiempo', icono: 'fa-file-contract', ayuda: 'Constancia de tiempo' },
        // Retardos
        { clave: 'retardo_menor', nombre: 'Retardo Menor (11-20 min)', icono: 'fa-clock', ayuda: 'Retardo menor a 20 minutos' },
        { clave: 'retardo_mayor', nombre: 'Retardo Mayor (21-30 min)', icono: 'fa-exclamation-triangle', ayuda: 'Retardo mayor a 20 minutos' },
        { clave: 'falta', nombre: 'Falta (31+ min)', icono: 'fa-ban', ayuda: 'Falta por llegada tardía de más de 30 minutos' },
        // Otros
        { clave: 'PDSEP-SNTE', nombre: 'Programa Deportivo SEP-SNTE', icono: 'fa-running', ayuda: 'Programa Deportivo' },
        { clave: 'CLIDDA', nombre: 'CLIDDA', icono: 'fa-school', ayuda: 'CLIDDA' },
        { clave: 'EYR', nombre: 'Estímulos y Recompensas', icono: 'fa-award', ayuda: 'Estímulos y Recompensas' },
        { clave: 'DE', nombre: 'Desalojo de Edificio', icono: 'fa-door-open', ayuda: 'Desalojo de Edificio' },
        { clave: 'F', nombre: 'Fumigación', icono: 'fa-spray-can', ayuda: 'Fumigación' }
    ];

    // Usar todas las opciones
    const todasOpciones = todasLasOpciones;

    todasOpciones.forEach(op => {
        opciones.push({
            clave: op.clave,
            tipo_id: null,
            nombre: op.nombre,
            icono: op.icono,
            ayuda: op.ayuda,
            fundamento: ''
        });
        usados.add(op.clave);
    });

    // Agregar opciones del catálogo si existen y no están duplicadas
    (tiposCatalogo || []).forEach(tipo => {
        const clave = mapearTipoCatalogo(tipo, tipoIncidencia);
        if (clave && !usados.has(clave)) {
            opciones.push({
                clave,
                tipo_id: tipo.id,
                nombre: tipo.nombre || clave,
                icono: 'fa-file-alt',
                ayuda: 'Tipo del catálogo',
                fundamento: (tipo.descripcion || '').toString().trim()
            });
            usados.add(clave);
        }
    });

    return opciones;
}

function abrirModalJustificarIncidencia(asistenciaId, diasDisp, puedeSolicitar, fuente) {
    // Determinar el tipo de origen según la fuente
    const origen = (fuente === 'retardos') ? 'retardo' : 'incidencia';
    abrirModalJustificar(asistenciaId, diasDisp, puedeSolicitar, origen);
}

function abrirModalJustificarRetardo(retardoId, diasDisp, puedeSolicitar) {
    abrirModalJustificar(retardoId, diasDisp, puedeSolicitar, 'retardo');
}

function abrirModalJustificar(id, diasDisp, puedeSolicitar, origen) {
    diasEconomicosDisponibles = diasDisp;
    puedeSolicitarDiasEco = puedeSolicitar;
    
    const baseUrl = '<?= rtrim(BASE_URL, '/') ?>';
    const esRetardo = origen === 'retardo';
    
    const url = esRetardo 
        ? `${baseUrl}/justificaciones/get-retardo/${id}`
        : `${baseUrl}/justificaciones/get-asistencia-ajax/${id}?origen=incidencia`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const asistencia = data.asistencia || data.retardo;
                
                document.getElementById('asistencia_id_eco').value = asistencia.id;
                document.getElementById('empleado_id_eco').value = asistencia.empleado_id;
                document.getElementById('fecha_asistencia_eco').value = asistencia.fecha;
                
                const tipoIncidencia = data.tipo_incidencia || (esRetardo ? 'retardo' : 'comision_todo_dia');
                
                document.getElementById('tipo_incidencia_original').value = tipoIncidencia;
                
                // Construir opciones desde catálogo de tipos de justificación
                const opcionesDiv = document.getElementById('opciones_justificacion');
                const datalist = document.getElementById('lista_tipos_justificacion');
                let opcionesHTML = '';
                let datalistHTML = '';
                
                // Opciones para INCIDENCIAS (no retardo): solo comisiones y justificaciones
                const opcionesIncidencia = [
                    { clave: 'comision_entrada', nombre: 'Comisión Entrada', icono: 'fa-sign-in-alt', ayuda: 'Justificar hora de entrada' },
                    { clave: 'comision_salida', nombre: 'Comisión Salida', icono: 'fa-sign-out-alt', ayuda: 'Justificar hora de salida' },
                    { clave: 'comision_todo_dia', nombre: 'Comisión Día Completo', icono: 'fa-briefcase', ayuda: 'Sin entrada ni salida' },
                    { clave: 'dia_economico', nombre: 'Día Económico', icono: 'fa-calendar-check', ayuda: 'Solicitud de día económico' },
                    { clave: 'licencia_medica', nombre: 'Licencia Médica', icono: 'fa-user-md', ayuda: 'Enfermedad o accidente' },
                    { clave: 'vacaciones', nombre: 'Vacaciones', icono: 'fa-umbrella-beach', ayuda: 'Solicitud de vacaciones' },
                    { clave: 'cuidados_parentales', nombre: 'Cuidados Parentales', icono: 'fa-baby', ayuda: 'Cuidados maternos/paternos' }
                ];

                // Opciones para RETARDOS: solo tipos de retardo
                const opcionesRetardo = [
                    { clave: 'retardo_menor', nombre: 'Retardo Menor (11-20 min)', icono: 'fa-clock', ayuda: 'Retardo menor a 20 minutos' },
                    { clave: 'retardo_mayor', nombre: 'Retardo Mayor (21-30 min)', icono: 'fa-exclamation-triangle', ayuda: 'Retardo mayor a 20 minutos' },
                    { clave: 'falta', nombre: 'Falta (31+ min)', icono: 'fa-ban', ayuda: 'Falta por arrival tardío de más de 30 minutos' }
                ];

                // Determinar el tipo de default basado en hora_entrada y hora_salida para incidencias
                let tipoDefaultIncidencia = 'comision_todo_dia';
                if (asistencia.hora_entrada && asistencia.hora_salida) {
                    tipoDefaultIncidencia = 'comision_todo_dia';
                } else if (asistencia.hora_entrada && !asistencia.hora_salida) {
                    tipoDefaultIncidencia = 'comision_salida';
                } else if (!asistencia.hora_entrada && asistencia.hora_salida) {
                    tipoDefaultIncidencia = 'comision_entrada';
                }

                // Establecer el tipo default para el modal
                const tipoIncidenciaDefault = esRetardo ? (data.tipo_incidencia || 'retardo') : tipoDefaultIncidencia;
                
                console.log('tipos_justificacion del servidor:', data.tipos_justificacion);
                console.log('tipoIncidencia:', tipoIncidencia);
                
                const opcionesCatalogo = construirOpcionesCatalogo(data.tipos_justificacion || [], tipoIncidencia, esRetardo);
                
                // Para INCIDENCIAS: filtrar opciones del catálogo para excluir tipos de retardo y falta
                let opcionesFinales;
                if (esRetardo) {
                    // Para retardo: usar opciones del catálogo tal cual (incluye falta)
                    opcionesFinales = opcionesCatalogo.length > 0 ? opcionesCatalogo : opcionesRetardo;
                } else {
                    // Para incidencia: filtrar opciones del catálogo para excluir retardo y falta
                    const opcionesFiltradas = opcionesCatalogo.filter(op => {
                        return !['retardo', 'retardo_menor', 'retardo_mayor', 'tolerancia', 'falta'].includes(op.clave);
                    });
                    opcionesFinales = opcionesFiltradas.length > 0 ? opcionesFiltradas : opcionesIncidencia;
                }
                
                console.log('opcionesCatalogo:', opcionesCatalogo);
                console.log('opcionesFinales:', opcionesFinales);
                
                tiposCatalogoOpciones = opcionesFinales;

                opcionesFinales.forEach(op => {
                    let icono = op.icono || 'fa-file-alt';
                    let ayuda = op.ayuda || 'Tipo de justificación del catálogo';
                    let deshabilitado = '';
                    let claseLabel = '';

                    if (op.clave === 'dia_economico') {
                        ayuda = `${diasEconomicosDisponibles} días disponibles`;
                        if (!puedeSolicitarDiasEco || diasEconomicosDisponibles <= 0) {
                            deshabilitado = 'disabled';
                            claseLabel = 'text-muted';
                        }
                    }

                    // Agregar al datalist para búsqueda
                    datalistHTML += `<option value="${op.nombre}" data-clave="${op.clave}" data-id="${op.tipo_id}"></option>`;
                    
                    opcionesHTML += `
                        <div class="col-md-4">
                            <div class="form-check custom-radio-card" onclick="seleccionarJustificacion('${op.clave}', ${op.tipo_id})">
                                <input class="form-check-input" type="radio" name="tipo_justificacion" value="${op.clave}" id="op_${op.clave}" ${deshabilitado}>
                                <label class="form-check-label ${claseLabel}" for="op_${op.clave}">
                                    <i class="fas ${icono}"></i> ${op.nombre}
                                    <small class="d-block text-muted">${ayuda}</small>
                                </label>
                            </div>
                        </div>`;
                });
                
                datalist.innerHTML = datalistHTML;
                
                if (!opcionesHTML) {
                    opcionesHTML = '<div class="col-12"><div class="alert alert-warning mb-0">No hay tipos de justificación compatibles en el catálogo para esta incidencia.</div></div>';
                }
                
                opcionesDiv.innerHTML = opcionesHTML;
                
                // Obtener datos del registro
                const tipoRetraso = asistencia.tipo_retraso || data.tipo_retraso || '';
                const tipoAsistencia = asistencia.tipo_asistencia || data.tipo_asistencia || '';
                const horaEntrada = asistencia.hora_entrada || '';
                const horaSalida = asistencia.hora_salida || '';
                const tipoJustifId = asistencia.tipo_justificacion_id || data.tipo_justificacion_id || null;
                const tipoJustifNombre = asistencia.tipo_justificacion_nombre || data.tipo_justificacion_nombre || '';
                
                // Determinar automáticamente el tipo de justificación según las características del registro
                let tipoJustifClave = '';
                
                // Si ya tiene tipo_justificacion_id guardado, usarlo
                if (tipoJustifId && (tipoAsistencia || tipoRetraso)) {
                    tipoJustifClave = tipoAsistencia || tipoRetraso;
                }
                // Si no, determinar según las horas de entrada/salida
                else if (!horaEntrada && !horaSalida) {
                    // Sin entrada ni salida = Comisión Día Completo
                    tipoJustifClave = 'comision_todo_dia';
                } else if (horaEntrada && !horaSalida) {
                    // Con entrada, sin salida = Comisión Salida
                    tipoJustifClave = 'comision_salida';
                } else if (!horaEntrada && horaSalida) {
                    // Sin entrada, con salida = Comisión Entrada
                    tipoJustifClave = 'comision_entrada';
                }
                // Si es un retardo
                else if (['retardo_menor', 'retardo_mayor', 'falta'].includes(tipoRetraso)) {
                    tipoJustifClave = tipoRetraso;
                }
                // Otros tipos
                else if (['por_definir', 'comision_entrada', 'comision_salida', 'comision_todo_dia'].includes(tipoAsistencia)) {
                    tipoJustifClave = tipoAsistencia;
                }
                
                // Preseleccionar el tipo en el modal
                if (tipoJustifClave) {
                    const radioOpcion = document.querySelector(`input[name="tipo_justificacion"][value="${tipoJustifClave}"]`);
                    if (radioOpcion) {
                        radioOpcion.checked = true;
                        seleccionarJustificacion(tipoJustifClave, tipoJustifId);
                        
                        // Mostrar en el campo de búsqueda
                        const nombreMostrar = tipoJustifNombre || tipoJustifClave.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                        document.getElementById('busqueda_tipo_justificacion').value = nombreMostrar;
                    } else {
                        // Si no existe en catálogo, seleccionar la primera opción disponible
                        const primerRadio = document.querySelector('input[name="tipo_justificacion"]');
                        if (primerRadio) {
                            primerRadio.checked = true;
                            seleccionarJustificacion(primerRadio.value, null);
                        }
                    }
                }
                
                // Agregar evento para buscar en el input
                const busquedaInput = document.getElementById('busqueda_tipo_justificacion');
                busquedaInput.addEventListener('input', function() {
                    const valor = this.value.toLowerCase();
                    const opciones = datalist.querySelectorAll('option');
                    let encontrado = null;
                    
                    opciones.forEach(opt => {
                        if (opt.value.toLowerCase() === valor) {
                            encontrado = {
                                clave: opt.dataset.clave,
                                id: opt.dataset.id
                            };
                        }
                    });
                    
                    if (encontrado) {
                        seleccionarJustificacion(encontrado.clave, parseInt(encontrado.id));
                    }
                });
                
                busquedaInput.addEventListener('change', function() {
                    const valor = this.value.toLowerCase();
                    const opciones = datalist.querySelectorAll('option');
                    let encontrado = null;
                    
                    opciones.forEach(opt => {
                        if (opt.value.toLowerCase().includes(valor)) {
                            encontrado = {
                                clave: opt.dataset.clave,
                                id: opt.dataset.id,
                                nombre: opt.value
                            };
                        }
                    });
                    
                    if (encontrado && valor.length > 0) {
                        this.value = encontrado.nombre;
                        seleccionarJustificacion(encontrado.clave, parseInt(encontrado.id));
                    }
                });
                
                // Resetear campos
                document.getElementById('busqueda_tipo_justificacion').value = '';
                document.getElementById('tipo_justificacion_seleccionado').value = '';
                document.getElementById('campos_dia_economico').classList.add('d-none');
                document.getElementById('campos_licencia_medica').classList.add('d-none');
                document.getElementById('campos_comision').classList.add('d-none');
                document.getElementById('validacion_resultado').className = 'alert d-none';
                document.getElementById('btnGuardarJustificacion').disabled = true;
                
                // Limpiar radios seleccionados
                document.querySelectorAll('input[name="tipo_justificacion"]').forEach(r => r.checked = false);
                document.getElementById('tipo_justificacion_catalogo_id').value = '';
                document.getElementById('fundamento_tipo_seleccionado').value = '';
                tipoJustificacionCatalogoId = null;
                
                // Establecer fecha mínima para día económico
                const manana = new Date();
                manana.setDate(manana.getDate() + 1);
                const fechaInput = document.getElementById('fecha_dia_economico');
                if (fechaInput) fechaInput.min = manana.toISOString().split('T')[0];
                
                const modal = new bootstrap.Modal(document.getElementById('modalJustificarComisiones'));
                modal.show();
                
                // Actualizar título e info del modal según el tipo
                const tituloModal = document.getElementById('modalJustificarTitulo');
                const infoTipo = document.getElementById('info_tipo_incidencia');
                const textoTipo = document.getElementById('texto_tipo_incidencia');
                
                const nombresTipo = {
                    'comision_entrada': 'Comisión Entrada',
                    'comision_salida': 'Comisión Salida',
                    'comision_todo_dia': 'Comisión Día Completo',
                    'dia_economico': 'Día Económico',
                    'licencia_medica': 'Licencia Médica',
                    'vacaciones': 'Vacaciones',
                    'cuidados_parentales': 'Cuidados Parentales',
                    'retardo_menor': 'Retardo Menor',
                    'retardo_mayor': 'Retardo Mayor',
                    'falta': 'Falta',
                    'por_definir': 'Incidencia sin clasificar'
                };
                
                if (tipoJustifClave && nombresTipo[tipoJustifClave]) {
                    tituloModal.innerHTML = `<i class="fas fa-edit me-2"></i>${nombresTipo[tipoJustifClave]}`;
                    textoTipo.textContent = `Editando justificación para: ${nombresTipo[tipoJustifClave]}`;
                    infoTipo.classList.remove('d-none');
                } else {
                    tituloModal.innerHTML = '<i class="fas fa-edit me-2"></i>Justificar Incidencia';
                    infoTipo.classList.add('d-none');
                }
            } else {
                mostrarError('Error al cargar datos: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al obtener datos de la asistencia');
        });
}

function seleccionarJustificacion(tipo, tipoId = null) {
    tipoJustificacionSeleccionada = tipo;
    tipoJustificacionCatalogoId = tipoId;
    const inputTipoId = document.getElementById('tipo_justificacion_catalogo_id');
    if (inputTipoId) {
        inputTipoId.value = tipoId || '';
    }
    document.querySelector(`input[value="${tipo}"]`).checked = true;
    
    // Ocultar todos los campos primero - FORZAR ocultación
    const camposDiaEco = document.getElementById('campos_dia_economico');
    const camposLicencia = document.getElementById('campos_licencia_medica');
    const camposComision = document.getElementById('campos_comision');
    
    if (camposDiaEco) {
        camposDiaEco.classList.add('d-none');
        camposDiaEco.style.display = 'none';
    }
    if (camposLicencia) {
        camposLicencia.classList.add('d-none');
        camposLicencia.style.display = 'none';
    }
    if (camposComision) {
        camposComision.classList.add('d-none');
        camposComision.style.display = 'none';
    }
    
    // Limpiar campos de licencia médica
    const folioEl = document.getElementById('folio_licencia');
    const diasEl = document.getElementById('dias_licencia');
    const fechaIniEl = document.getElementById('fecha_inicio_licencia');
    const fechaFinEl = document.getElementById('fecha_fin_licencia');
    const diagEl = document.getElementById('diagnostico_licencia');
    
    if (folioEl) folioEl.value = '';
    if (diasEl) diasEl.value = '';
    if (fechaIniEl) fechaIniEl.value = '';
    if (fechaFinEl) fechaFinEl.value = '';
    if (diagEl) diagEl.value = '';
    
    // Reiniciar atributos required por tipo
    const campos = [
        'dias_solicitados', 'fecha_dia_economico',
        'folio_licencia', 'dias_licencia', 'fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia',
        'lugar_comision', 'motivo_comision'
    ];
    campos.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.removeAttribute('required');
    });
    
    // Mostrar campos según tipo
    if (tipo === 'dia_economico') {
        const camposDiaEco = document.getElementById('campos_dia_economico');
        if (camposDiaEco) {
            camposDiaEco.classList.remove('d-none');
            camposDiaEco.style.display = 'block';
        }
        ['dias_solicitados', 'fecha_dia_economico'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.setAttribute('required', 'required');
        });
    } else if (tipo === 'licencia_medica') {
        const camposLicencia = document.getElementById('campos_licencia_medica');
        if (camposLicencia) {
            camposLicencia.classList.remove('d-none');
            camposLicencia.style.display = 'block';
        }
        
        // Calcular antigüedad y mostrar información
        const empleadoId = document.getElementById('empleado_id').value;
        if (empleadoId) {
            fetch(`<?= BASE_URL ?>/empleados/datos-completos/${empleadoId}`)
                .then(res => res.json())
                .then(data => {
                    const ctrl = data.control_licencias || {};
                    const fechaIngreso = data.fecha_ingreso;
                    
                    if (fechaIngreso) {
                        const fechaParts = fechaIngreso.split('-');
                        const fechaIngresoStr = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;
                        const anios = ctrl.anios || 0;
                        const meses = ctrl.meses || 0;
                        const diasFull = ctrl.full || 15;
                        const diasHalf = ctrl.half || 15;
                        const fullUsados = ctrl.full_usados || 0;
                        const halfUsados = ctrl.half_usados || 0;
                        const fullRestantes = Math.max(0, diasFull - fullUsados);
                        const halfRestantes = Math.max(0, diasHalf - halfUsados);
                        
                        const aniosTexto = anios === 1 ? '1 año' : (anios + ' años');
                        const mesesTexto = meses === 1 ? '1 mes' : (meses + ' meses');
                        
                        document.getElementById('info_antiguedad_licencia').innerHTML = `
                            <div class="alert alert-info mb-3">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <strong>Fecha de Ingreso:</strong><br>${fechaIngresoStr}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Antigüedad:</strong><br>${aniosTexto}, ${mesesTexto}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Días Disponibles:</strong><br>
                                        <span class="text-success">Íntegro: ${fullRestantes}</span> | 
                                        <span class="text-warning">Medio: ${halfRestantes}</span>
                                    </div>
                                </div>
                            </div>`;
                    }
                })
                .catch(e => console.error('Error:', e));
        }
        
        const wrapFolio = document.getElementById('wrap_folio_licencia');
        const wrapDias = document.getElementById('wrap_dias_licencia');
        if (wrapFolio) wrapFolio.classList.remove('d-none');
        if (wrapDias) wrapDias.classList.remove('d-none');
        ['folio_licencia', 'dias_licencia', 'fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.setAttribute('required', 'required');
        });
    } else if (tipo === 'vacaciones') {
        const camposLicencia = document.getElementById('campos_licencia_medica');
        if (camposLicencia) {
            camposLicencia.classList.remove('d-none');
            camposLicencia.style.display = 'block';
        }
        document.getElementById('info_antiguedad_licencia').innerHTML = '';
        const wrapFolio = document.getElementById('wrap_folio_licencia');
        const wrapDias = document.getElementById('wrap_dias_licencia');
        if (wrapFolio) wrapFolio.classList.add('d-none');
        if (wrapDias) wrapDias.classList.add('d-none');
        ['fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.setAttribute('required', 'required');
        });
    } else if (tipo.startsWith('comision')) {
        const wrapFolio = document.getElementById('wrap_folio_licencia');
        const wrapDias = document.getElementById('wrap_dias_licencia');
        if (wrapFolio) wrapFolio.classList.remove('d-none');
        if (wrapDias) wrapDias.classList.remove('d-none');
        ['folio_licencia', 'dias_licencia', 'fecha_inicio_licencia', 'fecha_fin_licencia', 'diagnostico_licencia'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.removeAttribute('required');
        });
        const camposComision = document.getElementById('campos_comision');
        if (camposComision) {
            camposComision.classList.remove('d-none');
            camposComision.style.display = 'block';
        }
        ['lugar_comision'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.setAttribute('required', 'required');
        });
        // Solo requerir motivo si el campo existe (no para usuario)
        const motivoEl = document.getElementById('motivo_comision');
        if (motivoEl) motivoEl.setAttribute('required', 'required');
    }

    actualizarFundamentoPorTipo(tipo, tipoId);
    actualizarDatosRequeridos(tipo);
    
    validarJustificacion();
}

function obtenerTipoCatalogoSeleccionado(tipo, tipoId) {
    if (tipoId) {
        const porId = (tiposCatalogoOpciones || []).find(t => Number(t.tipo_id) === Number(tipoId));
        if (porId) return porId;
    }
    return (tiposCatalogoOpciones || []).find(t => t.clave === tipo) || null;
}

function actualizarFundamentoPorTipo(tipo, tipoId) {
    const tipoCatalogo = obtenerTipoCatalogoSeleccionado(tipo, tipoId);
    const fundamento = (tipoCatalogo?.fundamento || '').trim();
    document.getElementById('fundamento_tipo_seleccionado').value = fundamento;

    if (tipo.startsWith('comision') && fundamento) {
        const campo = document.getElementById('fundamento_comision');
        if (campo) campo.value = fundamento;
    }
    if (tipo === 'licencia_medica' && fundamento) {
        const campo = document.getElementById('fundamento_licencia');
        if (campo) campo.value = fundamento;
    }
}

function actualizarDatosRequeridos(tipo) {
    const box = document.getElementById('datos_requeridos_tipo');
    if (!box) return;

    let titulo = '';
    let items = [];
    if (tipo === 'dia_economico') {
        titulo = 'Datos requeridos para Día Económico';
        items = ['Días solicitados', 'Fecha'];
    } else if (tipo === 'licencia_medica') {
        titulo = 'Datos requeridos para Licencia Médica';
        items = ['Folio de licencia', 'Días otorgados', 'Fecha inicio', 'Fecha fin', 'Diagnóstico'];
    } else if (tipo === 'vacaciones') {
        titulo = 'Datos requeridos para Vacaciones';
        items = ['Fecha inicio', 'Fecha fin', 'Motivo'];
    } else if (tipo === 'cuidados_parentales') {
        titulo = 'Datos requeridos para Cuidados Maternos/Paternos';
        items = ['Tipo de cuidado (materno/paterno)', 'Fecha inicio', 'Fecha fin', 'Motivo'];
    } else if (tipo.startsWith('comision')) {
        titulo = 'Datos requeridos para Comisión';
        items = ['Lugar de la comisión', 'Motivo de la comisión'];
    } else {
        box.className = 'alert alert-secondary d-none';
        box.innerHTML = '';
        return;
    }

    const tipoCatalogo = obtenerTipoCatalogoSeleccionado(tipo, tipoJustificacionCatalogoId);
    const fundamento = (tipoCatalogo?.fundamento || '').trim();

    box.className = 'alert alert-secondary';
    box.innerHTML = `<strong>${titulo}</strong><br>${items.join(' | ')}`;
    if (fundamento) {
        box.innerHTML += `<hr class="my-2"><strong>Fundamento:</strong><br>${fundamento}`;
    }
}

function validarJustificacion() {
    const resultado = document.getElementById('validacion_resultado');
    const btnGuardar = document.getElementById('btnGuardarJustificacion');
    let valido = true;
    let errores = [];
    
    if (!tipoJustificacionSeleccionada) {
        valido = false;
    } else if (tipoJustificacionSeleccionada === 'dia_economico') {
        const dias = document.getElementById('dias_solicitados').value;
        const fecha = document.getElementById('fecha_dia_economico').value;
        
        if (!dias || !fecha) {
            valido = false;
            errores.push('Complete los campos obligatorios');
        } else {
            const fechaObj = new Date(fecha);
            const diaSemana = fechaObj.getDay();
            if (diaSemana === 5) errores.push('No se puede solicitar en viernes');
            if (diaSemana === 1) errores.push('No se puede solicitar en lunes');
        }
    } else if (tipoJustificacionSeleccionada === 'licencia_medica') {
        const folio = document.getElementById('folio_licencia').value;
        const dias = document.getElementById('dias_licencia').value;
        const fechaIni = document.getElementById('fecha_inicio_licencia').value;
        const fechaFin = document.getElementById('fecha_fin_licencia').value;
        const diagnostico = document.getElementById('diagnostico_licencia').value;
        
        if (!folio || !dias || !fechaIni || !fechaFin || !diagnostico) {
            valido = false;
            errores.push('Complete todos los campos de la licencia médica');
        }
    } else if (tipoJustificacionSeleccionada === 'vacaciones') {
        const fechaIni = document.getElementById('fecha_inicio_licencia').value;
        const fechaFin = document.getElementById('fecha_fin_licencia').value;
        const motivo = document.getElementById('diagnostico_licencia').value;
        if (!fechaIni || !fechaFin || !motivo) {
            valido = false;
            errores.push('Complete fecha inicio, fecha fin y motivo de vacaciones');
        }
    } else if (tipoJustificacionSeleccionada === 'cuidados_parentales') {
        const tipoCuidados = document.getElementById('tipo_cuidados').value;
        const fechaIni = document.getElementById('fecha_inicio_licencia').value;
        const fechaFin = document.getElementById('fecha_fin_licencia').value;
        const motivo = document.getElementById('diagnostico_licencia').value;
        if (!tipoCuidados || !fechaIni || !fechaFin || !motivo) {
            valido = false;
            errores.push('Complete tipo de cuidado, fechas y motivo');
        }
    } else if (tipoJustificacionSeleccionada.startsWith('comision')) {
        const lugar = document.getElementById('lugar_comision').value;
        const motivoInput = document.getElementById('motivo_comision');
        const motivo = motivoInput ? motivoInput.value : '';
        
        if (!lugar) {
            valido = false;
            errores.push('Complete el lugar de la comisión');
        }
    }
    
    if (errores.length > 0) {
        resultado.className = 'alert alert-danger';
        resultado.innerHTML = errores.join('<br>');
    } else {
        resultado.className = 'alert d-none';
    }
    
    btnGuardar.disabled = !valido;
}

function guardarJustificacionIncidencia() {
    const formData = new FormData();
    formData.append('asistencia_id', document.getElementById('asistencia_id_eco').value);
    formData.append('empleado_id', document.getElementById('empleado_id_eco').value);
    formData.append('tipo_justificacion', tipoJustificacionSeleccionada);
    if (tipoJustificacionCatalogoId) {
        formData.append('tipo_justificacion_id', tipoJustificacionCatalogoId);
    }
    const fundamentoTipo = document.getElementById('fundamento_tipo_seleccionado')?.value || '';
    if (fundamentoTipo) {
        formData.append('fundamento_tipo', fundamentoTipo);
    }
    
    if (tipoJustificacionSeleccionada === 'dia_economico') {
        formData.append('fecha', document.getElementById('fecha_dia_economico')?.value || '');
        formData.append('dias_solicitados', document.getElementById('dias_solicitados')?.value || '');
    } else if (tipoJustificacionSeleccionada === 'licencia_medica' || tipoJustificacionSeleccionada === 'vacaciones' || tipoJustificacionSeleccionada === 'cuidados_parentales') {
        formData.append('folio_licencia', document.getElementById('folio_licencia')?.value || '');
        formData.append('dias_otorgados', document.getElementById('dias_licencia')?.value || '');
        formData.append('fecha_inicio_licencia', document.getElementById('fecha_inicio_licencia')?.value || '');
        formData.append('fecha_fin_licencia', document.getElementById('fecha_fin_licencia')?.value || '');
        formData.append('diagnostico', document.getElementById('diagnostico_licencia')?.value || '');
        formData.append('tipo_cuidados', document.getElementById('tipo_cuidados')?.value || '');
        formData.append('fundamento_legal', document.getElementById('fundamento_licencia')?.value || '');
    } else if (tipoJustificacionSeleccionada.startsWith('comision')) {
        formData.append('lugar_comision', document.getElementById('lugar_comision')?.value || '');
        formData.append('motivo_comision', document.getElementById('motivo_comision')?.value || '');
        formData.append('fundamento_comision', document.getElementById('fundamento_comision')?.value || '');
    }
    
    fetch('<?php echo rtrim(BASE_URL, '/'); ?>/justificaciones/guardar-incidencia', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Incidencia justificada correctamente');
            bootstrap.Modal.getInstance(document.getElementById('modalJustificarComisiones')).hide();
            window.location.reload();
        } else {
            mostrarError('Error: ' + (data.error || 'No se pudo guardar'));
        }
    })
    .catch(error => {
        mostrarError('Error de conexión: ' + error.message);
    });
}
</script>

<style>
.custom-radio-card {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.2s;
}
.custom-radio-card:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}
.custom-radio-card input:checked + label {
    color: #0d6efd;
}
.custom-radio-card input:checked {
    border-color: #0d6efd;
}
.custom-radio-card input:checked + label .fas {
    color: #0d6efd;
}
</style>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
