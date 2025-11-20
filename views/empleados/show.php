<?php include __DIR__ . '/../layout.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?></h2>
        <div>
            <a href="/sistema_biometrico/empleados/<?php echo $empleado['id']; ?>/edit" class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="/sistema_biometrico/empleados" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Foto del Empleado</h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($empleado['foto_cara'] && file_exists($empleado['foto_cara'])): ?>
                        <img src="/sistema_biometrico/<?php echo htmlspecialchars($empleado['foto_cara']); ?>"
                             alt="Foto de <?php echo htmlspecialchars($empleado['nombre']); ?>"
                             class="img-fluid rounded-circle mb-3"
                             style="max-width: 200px; max-height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 200px; height: 200px;">
                            <i class="fas fa-user fa-4x text-secondary"></i>
                        </div>
                        <p class="text-muted">Sin foto registrada</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Información Personal</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?></p>
                            <p><strong>RFC:</strong> <?php echo htmlspecialchars($empleado['rfc']); ?></p>
                            <p><strong>CURP:</strong> <?php echo htmlspecialchars($empleado['curp']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Área:</strong> <?php echo htmlspecialchars($empleado['area']); ?></p>
                            <p><strong>Jerarquía:</strong> <?php echo htmlspecialchars($empleado['jerarquia']); ?></p>
                            <p><strong>Estado:</strong>
                                <span class="badge <?php echo $empleado['activo'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $empleado['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Resumen de Asistencia</h5>
                </div>
                <div class="card-body">
                    <p><strong>Retardos este mes:</strong> <?php echo count(array_filter($retardos ?? [], function($r) { return date('m-Y', strtotime($r['fecha'])) == date('m-Y'); })); ?></p>
                    <p><strong>Comisiones pendientes:</strong> <?php echo count(array_filter($comisiones ?? [], function($c) { return !$c['justificada']; })); ?></p>
                    <p><strong>Ausencias este mes:</strong> <?php echo count(array_filter($ausencias ?? [], function($a) { return date('m-Y', strtotime($a['fecha_inicio'])) == date('m-Y'); })); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Historial de Retardos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Minutos</th>
                                    <th>Tipo</th>
                                    <th>Justificado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($retardos) && is_array($retardos)): ?>
                                    <?php foreach ($retardos as $retardo): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($retardo['fecha'])); ?></td>
                                        <td><?php echo htmlspecialchars($retardo['minutos_retardo']); ?></td>
                                        <td><?php echo htmlspecialchars($retardo['tipo'] == 'menor' ? 'Menor' : 'Mayor'); ?></td>
                                        <td><?php echo htmlspecialchars($retardo['justificado'] ? 'Sí' : 'No'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay retardos registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Comisiones</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Descripción</th>
                                    <th>Monto</th>
                                    <th>Vencimiento</th>
                                    <th>Justificada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($comisiones) && is_array($comisiones)): ?>
                                    <?php foreach ($comisiones as $comision): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($comision['descripcion']); ?></td>
                                        <td>$<?php echo number_format($comision['monto'], 2); ?></td>
                                        <td><?php echo $comision['fecha_vencimiento'] ? date('d/m/Y', strtotime($comision['fecha_vencimiento'])) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($comision['justificada'] ? 'Sí' : 'No'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay comisiones registradas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Ausencias</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Tipo</th>
                                    <th>Justificada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($ausencias) && is_array($ausencias)): ?>
                                    <?php foreach ($ausencias as $ausencia): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($ausencia['fecha_inicio'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($ausencia['fecha_fin'])); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($ausencia['tipo'])); ?></td>
                                        <td><?php echo htmlspecialchars($ausencia['justificada'] ? 'Sí' : 'No'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay ausencias registradas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
