<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-check"></i> Justificar Incidencia</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php 
                    $tipoIncidencia = $retardo['tipo_retraso'] ?? $retardo['tipo'] ?? 'normal';
                    
                    // Determinar el tipo de justificación recomendado según la incidencia
                    $tipoJustificacionSugerido = '';
                    $mensajeAyuda = '';
                    $alertaTipo = 'info';
                    
                    switch ($tipoIncidencia) {
                        case 'normal':
                        case 'retardo_menor':
                            $tipoJustificacionSugerido = 'Nómina';
                            $mensajeAyuda = 'Retardo menor: se justifica con tiempo de nómina (sin documento)';
                            $alertaTipo = 'warning';
                            break;
                        case 'retardo_mayor':
                            $tipoJustificacionSugerido = 'Ausencia justificada';
                            $mensajeAyuda = 'Retardo mayor: requiere justificación con documento de soporte';
                            $alertaTipo = 'danger';
                            break;
                        case 'comision_entrada':
                        case 'comision_salida':
                        case 'comision_todo_dia':
                            $tipoJustificacionSugerido = 'Comisión';
                            $mensajeAyuda = 'Comisión: requiere documento de soporte de la comisión';
                            $alertaTipo = 'info';
                            break;
                        case 'dia_economico':
                            $tipoJustificacionSugerido = 'Día económico';
                            $mensajeAyuda = 'Día económico: requiere documento de soporte autorizado';
                            $alertaTipo = 'info';
                            break;
                        case 'ausencia':
                            $tipoJustificacionSugerido = 'Licencia médica';
                            $mensajeAyuda = 'Ausencia: requiere documento de soporte (licencia médica, etc.)';
                            $alertaTipo = 'danger';
                            break;
                        default:
                            $tipoJustificacionSugerido = 'Nómina';
                            $mensajeAyuda = 'Seleccione el tipo de justificación correspondiente';
                            $alertaTipo = 'info';
                    }
                    
                    // Formatear nombre del tipo para mostrar
                    $tipoMostrar = str_replace(['_', 'comision', 'dia'], [' ', 'Comisión ', 'Día '], $tipoIncidencia);
                    ?>
                    <div class="alert alert-<?php echo $alertaTipo; ?>">
                        <strong>Detalles de la Incidencia:</strong><br>
                        Empleado: <?php echo htmlspecialchars(($retardo['nombre'] ?? 'Desconocido') . ' ' . ($retardo['apellido'] ?? '')); ?><br>
                        Fecha: <?php echo date('d/m/Y', strtotime($retardo['fecha'] ?? 'now')); ?><br>
                        <?php if (!empty($retardo['minutos_retardo'])): ?>
                        Minutos de retardo: <?php echo $retardo['minutos_retardo']; ?><br>
                        <?php endif; ?>
                        Tipo de Incidencia: <span class="badge bg-<?php echo $alertaTipo; ?>">
                            <?php echo ucwords($tipoMostrar); ?>
                        </span>
                        <br><small class="text-muted">
                            <i class="fas fa-info-circle"></i> <?php echo $mensajeAyuda; ?>
                        </small>
                    </div>

                    <?php require_once __DIR__ . '/../../helpers/Csrf.php'; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        <div class="form-group">
                            <label for="tipo_justificacion_id">Tipo de Justificación</label>
                            <select class="form-control" id="tipo_justificacion_id" name="tipo_justificacion_id">
                                <option value="">Seleccionar tipo...</option>
                                <?php foreach ($tipos_justificacion as $tipo): ?>
                                    <option value="<?php echo $tipo['id']; ?>" 
                                            data-requiere-documento="<?php echo $tipo['requiere_documento'] ?? 0; ?>"
                                            data-requiere-motivo="<?php echo ($tipo['nombre'] === 'Nómina' || $tipo['nombre'] === 'Ausencia justificada') ? 0 : 1; ?>"
                                            data-nombre="<?php echo strtolower($tipo['nombre']); ?>"
                                            data-tipo-incidencia="<?php 
                                                if (in_array($tipo['nombre'], ['Nómina'])) echo 'retardo_menor';
                                                elseif (in_array($tipo['nombre'], ['Ausencia justificada'])) echo 'retardo_mayor';
                                                elseif (in_array($tipo['nombre'], ['Comisión'])) echo 'comision';
                                                elseif (in_array($tipo['nombre'], ['Día económico'])) echo 'dia_economico';
                                                elseif (in_array($tipo['nombre'], ['Licencia médica', 'Médico'])) echo 'ausencia';
                                                else echo 'otro';
                                            ?>">
                                        <?php echo htmlspecialchars($tipo['nombre']); ?>
                                        <?php if (!empty($tipo['requiere_documento'])): ?>
                                            (Requiere documento)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="motivo-container">
                            <label for="motivo">Motivo de la Justificación</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="4"
                                      placeholder="Describa detalladamente el motivo de la justificación..."></textarea>
                        </div>

                        <div class="form-group" id="soporte-container" style="display:none;">
                            <label for="soporte">
                                Adjuntar Soporte (PDF, JPG, PNG)
                                <small class="text-muted" id="soporte-obligatorio">(obligatorio)</small>
                            </label>
                            <input type="file" class="form-control-file" id="soporte" name="soporte" accept=".pdf,image/*">
                            <small class="form-text text-muted">Tamaño máximo recomendado: 5MB. Mantén confidencial cualquier dato sensible.</small>

                            <?php if (!empty($retardo['soporte'])): ?>
                                <p class="mt-2">Soporte actual: <a href="<?php echo BASE_URL . '/' . $retardo['soporte']; ?>" target="_blank">Ver/Descargar</a></p>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-success" style="background-color: #235B4E; border-color: #235B4E;">
                            <i class="fas fa-save"></i> Justificar Incidencia
                        </button>
                        <a href="<?php echo BASE_URL; ?>/justificaciones" class="btn btn-secondary" style="border-color: #6F7271; color: #6F7271;">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('tipo_justificacion_id');
    var options = select.options;
    var tipoIncidencia = '<?php echo $tipoIncidencia; ?>';
    
    // Mapeo de tipo de incidencia a tipo de justificación
    var mapeoIncidenciaJustificacion = {
        'retardo_menor': 'Nómina',
        'retardo_mayor': 'Ausencia justificada',
        'comision_entrada': 'Comisión',
        'comision_salida': 'Comisión',
        'comision_todo_dia': 'Comisión',
        'dia_economico': 'Día económico',
        'ausencia': 'Licencia médica',
        'normal': 'Nómina'
    };
    
    // Función para actualizar campos según selección
    function actualizarCampos() {
        var option = select.options[select.selectedIndex];
        if (!option || !option.value) return;
        
        var requiereDoc = option.getAttribute('data-requiere-documento') === '1';
        var requiereMotivo = option.getAttribute('data-requiere-motivo') === '1';
        
        // Mostrar/ocultar campo de documento
        document.getElementById('soporte-container').style.display = requiereDoc ? 'block' : 'none';
        document.getElementById('soporte').required = requiereDoc;
        
        // Mostrar/ocultar campo de motivo
        document.getElementById('motivo-container').style.display = requiereMotivo ? 'block' : 'none';
        document.getElementById('motivo').required = requiereMotivo;
    }
    
    // Función para seleccionar opción por nombre
    function seleccionarPorNombre(nombre) {
        if (!nombre) return false;
        for (var i = 0; i < options.length; i++) {
            if (options[i].text.toLowerCase().includes(nombre.toLowerCase())) {
                select.selectedIndex = i;
                return true;
            }
        }
        return false;
    }
    
    // Primero registrar el event listener
    select.addEventListener('change', actualizarCampos);
    
    // Seleccionar automáticamente según el tipo de incidencia
    var justificacionSugerida = mapeoIncidenciaJustificacion[tipoIncidencia] || mapeoIncidenciaJustificacion['normal'];
    seleccionarPorNombre(justificacionSugerida);
    
    // Actualizar campos después de seleccionar
    actualizarCampos();
});
</script>
