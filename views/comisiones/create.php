<?php require_once __DIR__ . '/../../helpers/Csrf.php'; ?>
<div class="container mt-4">
    <h2>Crear Nueva Comisión</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['form_errors'])): ?>
        <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle"></i> Errores de validación:</h6>
            <ul class="mb-0">
                <?php foreach ($_SESSION['form_errors'] as $field => $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['form_errors']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-plus-circle"></i> Datos de la Comisión</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo BASE_URL; ?>/comisiones/create" id="comisionForm">
                        <input type="hidden" name="_token" id="csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="empleado_id" class="form-label">Empleado *</label>
                                    <select class="form-select" id="empleado_id" name="empleado_id" required>
                                        <option value="">Seleccionar empleado...</option>
                                        <?php foreach ($empleados as $empleado): ?>
                                            <option value="<?php echo $empleado['id']; ?>" <?php echo (isset($_SESSION['form_data']['empleado_id']) && $_SESSION['form_data']['empleado_id'] == $empleado['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="tipo_comision" class="form-label">Tipo de Comisión *</label>
                                    <select class="form-select" id="tipo_comision" name="tipo_comision" required onchange="actualizarInfoTipo()">
                                        <option value="">Seleccionar tipo...</option>
                                        <?php foreach ($tiposComision as $tipo => $config): ?>
                                            <option value="<?php echo $tipo; ?>" data-requiere-aprobacion="<?php echo $config['requiere_aprobacion'] ? '1' : '0'; ?>" data-limite-dias="<?php echo $config['limite_dias']; ?>" <?php echo (isset($_SESSION['form_data']['tipo_comision']) && $_SESSION['form_data']['tipo_comision'] == $tipo) ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($tipo); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="descripcion" class="form-label">Descripción *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required placeholder="Describa detalladamente el propósito de la comisión"><?php echo htmlspecialchars($_SESSION['form_data']['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="monto" class="form-label">Monto ($) *</label>
                                    <input type="number" class="form-control" id="monto" name="monto" step="0.01" min="0" required onchange="validarLimiteMensual()" value="<?php echo htmlspecialchars($_SESSION['form_data']['monto'] ?? ''); ?>">
                                    <small class="form-text text-muted" id="limite-info"></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="fecha_asignacion" class="form-label">Fecha de Asignación *</label>
                                    <input type="date" class="form-control" id="fecha_asignacion" name="fecha_asignacion" required onchange="validarLimiteMensual()" value="<?php echo htmlspecialchars($_SESSION['form_data']['fecha_asignacion'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                    <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" onchange="calcularDias()" value="<?php echo htmlspecialchars($_SESSION['form_data']['fecha_vencimiento'] ?? ''); ?>">
                                    <small class="form-text text-muted" id="dias-info"></small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info" id="tipo-info" style="display: none;">
                            <strong>Información del tipo seleccionado:</strong>
                            <div id="tipo-detalles"></div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Comisión
                            </button>
                            <a href="<?php echo BASE_URL; ?>/comisiones" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Información AEFCM</h5>
                </div>
                <div class="card-body">
                    <h6>Límites Generales:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Límite mensual:</strong> $3,000.00 por empleado</li>
                        <li><strong>Días hábiles máximos:</strong> 30 días por comisión</li>
                        <li><strong>Validación automática:</strong> Se verifica cumplimiento de normas</li>
                    </ul>

                    <hr>

                    <h6>Tipos de Comisión:</h6>
                    <div class="mb-2">
                        <strong>Viáticos:</strong> Requieren aprobación, hasta 15 días
                    </div>
                    <div class="mb-2">
                        <strong>Gastos de Representación:</strong> Requieren aprobación, hasta 30 días
                    </div>
                    <div class="mb-2">
                        <strong>Transporte:</strong> No requieren aprobación, hasta 7 días
                    </div>
                    <div class="mb-2">
                        <strong>Hospedaje:</strong> Requieren aprobación, hasta 30 días
                    </div>
                    <div class="mb-2">
                        <strong>Alimentación:</strong> No requieren aprobación, hasta 7 días
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function actualizarInfoTipo() {
    const select = document.getElementById('tipo_comision');
    const selectedOption = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('tipo-info');
    const detallesDiv = document.getElementById('tipo-detalles');

    if (selectedOption.value) {
        const requiereAprobacion = selectedOption.getAttribute('data-requiere-aprobacion') === '1';
        const limiteDias = selectedOption.getAttribute('data-limite-dias');

        detallesDiv.innerHTML = `
            <ul class="mb-0">
                <li>Requiere aprobación: <strong>${requiereAprobacion ? 'Sí' : 'No'}</strong></li>
                <li>Límite de días: <strong>${limiteDias} días hábiles</strong></li>
            </ul>
        `;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}

function validarLimiteMensual() {
    const empleadoId = document.getElementById('empleado_id').value;
    const monto = document.getElementById('monto').value;
    const fechaAsignacion = document.getElementById('fecha_asignacion').value;
    const limiteInfo = document.getElementById('limite-info');

    if (empleadoId && monto && fechaAsignacion) {
        const fecha = new Date(fechaAsignacion);
        const mes = fecha.getMonth() + 1;
        const anio = fecha.getFullYear();

        const csrfToken = document.getElementById('csrf_token').value;
        fetch('<?php echo BASE_URL; ?>/comisiones/validar-limite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `empleado_id=${empleadoId}&monto=${monto}&mes=${mes}&anio=${anio}&_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.valido) {
                limiteInfo.innerHTML = '<span class="text-success">✓ Dentro del límite mensual</span>';
            } else {
                limiteInfo.innerHTML = '<span class="text-danger">✗ Excede el límite mensual de $3,000.00</span>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            limiteInfo.innerHTML = '<span class="text-warning">No se pudo validar el límite</span>';
        });
    }
}

function calcularDias() {
    const fechaInicio = document.getElementById('fecha_asignacion').value;
    const fechaFin = document.getElementById('fecha_vencimiento').value;
    const diasInfo = document.getElementById('dias-info');

    if (fechaInicio && fechaFin) {
        const inicio = new Date(fechaInicio);
        const fin = new Date(fechaFin);

        if (fin >= inicio) {
            let diasHabiles = 0;
            const dia = new Date(inicio);

            while (dia <= fin) {
                const diaSemana = dia.getDay();
                if (diaSemana !== 0 && diaSemana !== 6) { // No sábado ni domingo
                    diasHabiles++;
                }
                dia.setDate(dia.getDate() + 1);
            }

            diasInfo.innerHTML = `Días hábiles: <strong>${diasHabiles}</strong>`;
        } else {
            diasInfo.innerHTML = '<span class="text-danger">Fecha de vencimiento debe ser posterior</span>';
        }
    }
}

// Validar formulario antes de enviar
document.getElementById('comisionForm').addEventListener('submit', function(e) {
    const tipoSelect = document.getElementById('tipo_comision');
    if (!tipoSelect.value) {
        e.preventDefault();
        alert('Debe seleccionar un tipo de comisión');
        return;
    }

    const limiteInfo = document.getElementById('limite-info');
    if (limiteInfo.innerHTML.includes('Excede el límite')) {
        e.preventDefault();
        alert('La comisión excede el límite mensual permitido por AEFCM');
        return;
    }
});
</script>
