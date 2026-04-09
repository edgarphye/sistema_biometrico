<?php require_once __DIR__ . '/../../helpers/Csrf.php'; ?>

<style>
@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
    .card {
        border-radius: 8px;
    }
    .table {
        font-size: 0.85rem;
    }
    .table th, .table td {
        padding: 8px 4px;
    }
    .btn-sm {
        padding: 4px 8px;
        font-size: 0.75rem;
    }
}
@media (max-width: 576px) {
    h4 {
        font-size: 1rem;
    }
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-exclamation-triangle"></i> Retardos Pendientes de Justificación</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Fecha</th>
                                    <th>Minutos Retardo</th>
                                    <th>Tipo de Incidencia</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($retardos as $retardo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($retardo['nombre'] . ' ' . $retardo['apellido']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($retardo['fecha'])); ?></td>
                                    <td><?php echo $retardo['minutos_retardo']; ?> min</td>
                                    <td>
                                        <?php 
                                        $tipoRetardo = $retardo['tipo_retraso'] ?? $retardo['tipo'] ?? 'retardo_menor';
                                        $tipoMostrar = ucwords(str_replace('_', ' ', $tipoRetardo));
                                        $tipoJustificacion = $retardo['tipo_justificacion'] ?? '';
                                        ?>
                                        <?php if (!empty($tipoJustificacion)): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($tipoJustificacion); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-<?php echo $tipoRetardo == 'retardo_mayor' ? 'danger' : 'warning'; ?>">
                                                <?php echo $tipoMostrar; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/justificaciones/justificar/<?php echo $retardo['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Justificar
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (empty($retardos)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No hay retardos pendientes de justificación.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
