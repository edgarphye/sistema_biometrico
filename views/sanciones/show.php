<?php include 'views/layout.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Detalle de Sanción #<?php echo $sancion['id']; ?></h4>
                    <a href="<?php echo BASE_URL; ?>/sanciones" class="btn btn-sm btn-secondary">Volver</a>
                </div>
                <div class="card-body">
                    <p><strong>Empleado:</strong> <?php echo htmlspecialchars(($sancion['nombre'] ?? '') . ' ' . ($sancion['apellido'] ?? '')); ?></p>
                    <p><strong>Tipo:</strong> <?php echo ucfirst($sancion['tipo_sancion'] ?? $sancion['tipo'] ?? 'N/A'); ?></p>
                    <p><strong>Fecha inicio:</strong> <?php echo $sancion['fecha_inicio'] ?? 'N/A'; ?></p>
                    <p><strong>Días:</strong> <?php echo intval($sancion['dias'] ?? 0); ?></p>
                    <p><strong>Motivo:</strong><br><?php echo nl2br(htmlspecialchars($sancion['motivo'] ?? '')); ?></p>
                    <p><strong>Creado por (usuario id):</strong> <?php echo $sancion['creado_por'] ?? '-'; ?></p>
                    <p><strong>Fecha creación:</strong> <?php echo $sancion['created_at'] ?? $sancion['fecha_creacion'] ?? 'N/A'; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
