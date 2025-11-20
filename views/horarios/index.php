<?php include 'views/layout.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-clock"></i> Catálogo de Horarios Laborales</h4>
                    <a href="<?php echo BASE_URL; ?>/horarios/create" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Horario
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Hora Entrada</th>
                                    <th>Hora Salida</th>
                                    <th>Tolerancia (min)</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horarios as $horario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($horario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($horario['hora_entrada']); ?></td>
                                    <td><?php echo htmlspecialchars($horario['hora_salida']); ?></td>
                                    <td><?php echo htmlspecialchars($horario['tolerancia_minutos']); ?></td>
                                    <td><?php echo htmlspecialchars($horario['descripcion'] ?? ''); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/horarios/edit/<?php echo $horario['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/horarios/delete/<?php echo $horario['id']; ?>" class="btn btn-danger btn-sm"
                                           onclick="return confirm('¿Está seguro de eliminar este horario?')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
                    <h5><i class="fas fa-user-clock"></i> Asignar Horarios</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo BASE_URL; ?>/horarios/asignar" class="btn btn-success">
                        <i class="fas fa-link"></i> Asignar Horarios a Empleados
                    </a>
                    <br><br>
                    <a href="<?php echo BASE_URL; ?>/horarios/ver-asignaciones" class="btn btn-info">
                        <i class="fas fa-eye"></i> Ver Asignaciones
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Información</h5>
                </div>
                <div class="card-body">
                    <p><strong>Normas AEFCM aplicadas:</strong></p>
                    <ul>
                        <li>Retardo menor: ≤ tolerancia minutos (descuento 1/8 día)</li>
                        <li>Retardo mayor: > tolerancia minutos (descuento 1/4 día)</li>
                        <li>Dos retardos menores = uno mayor</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
