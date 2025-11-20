<?php
// Mejor vista de Empleados: lista en tarjetas responsivas y formularios en modales
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Empleados</h2>
        <div>
            <button class="btn btn-outline-secondary me-2" id="btn-refresh" title="Refrescar lista"><i class="fas fa-sync-alt"></i></button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearEmpleado"><i class="fas fa-plus"></i> Nuevo Empleado</button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <input type="search" id="search-empleados" class="form-control" placeholder="Buscar por nombre, RFC o área...">
        </div>
    </div>

    <div class="row g-3" id="empleados-list">
        <?php if (!empty($empleados) && is_array($empleados)): ?>
            <?php foreach ($empleados as $empleado): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <?php if (!empty($empleado['foto_cara'])): ?>
                                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($empleado['foto_cara']); ?>" alt="Foto" class="rounded-circle mb-3" style="width:90px;height:90px;object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:90px;height:90px;">
                                    <i class="fas fa-user fa-2x text-muted"></i>
                                </div>
                            <?php endif; ?>

                            <h6 class="card-title mb-1"><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?></h6>
                            <p class="text-muted small mb-2">
                                <strong>RFC:</strong> <?php echo htmlspecialchars($empleado['rfc']); ?><br>
                                <strong>Área:</strong> <?php echo htmlspecialchars($empleado['area'] ?? '-'); ?>
                            </p>

                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-outline-primary ver-empleado" data-id="<?php echo $empleado['id']; ?>"> <i class="fas fa-eye"></i> Ver</button>
                                <button class="btn btn-sm btn-outline-warning editar-empleado" data-id="<?php echo $empleado['id']; ?>"> <i class="fas fa-edit"></i> Editar</button>
                            </div>
                        </div>
                        <div class="card-footer text-center small">
                            <span class="badge <?php echo $empleado['activo'] ? 'bg-success' : 'bg-danger'; ?>"><?php echo $empleado['activo'] ? 'Activo' : 'Inactivo'; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-muted mb-2"></i>
                        <h5>No hay empleados registrados</h5>
                        <p class="text-muted">Agrega tu primer empleado usando el botón "Nuevo Empleado".</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modales (Crear / Ver / Editar) -->
    <?php include __DIR__ . '/partials/empleado_modals.php'; ?>

</div>

<?php include __DIR__ . '/../layout.php'; ?>

<?php // JS específico para la página: búsqueda, carga AJAX para ver/editar y previews ?>
<script>
$(function(){
    function cargarDatosEmpleado(empleadoId, cb) {
        $.get('<?php echo BASE_URL; ?>/empleados/datos-completos/' + empleadoId)
            .done(function(res){ cb(res); })
            .fail(function(){ alert('Error cargando datos del empleado.'); });
    }

    // Ver
    $(document).on('click', '.ver-empleado', function(){
        var id = $(this).data('id');
        cargarDatosEmpleado(id, function(datos){
            // llenar modalVer
            $('#modalVerEmpleado #modal_detalle-nombre').text(datos.nombre + ' ' + datos.apellido);
            $('#modalVerEmpleado #modal_detalle-rfc').text(datos.rfc);
            $('#modalVerEmpleado #modal_detalle-curp').text(datos.curp);
            $('#modalVerEmpleado #modal_detalle-area').text(datos.area);
            $('#modalVerEmpleado #modal_detalle-jerarquia').text(datos.jerarquia);
            $('#modalVerEmpleado #modal_detalle-estado').html('<span class="badge ' + (datos.activo==1? 'bg-success':'bg-danger') + '">' + (datos.activo==1? 'Activo':'Inactivo') + '</span>');
            if (datos.foto_cara) {
                $('#modalVerEmpleado #modal_foto-detalle').html('<img src="<?php echo BASE_URL; ?>/'+datos.foto_cara+'" class="img-fluid rounded-circle" style="max-width:200px;">');
            } else { $('#modalVerEmpleado #modal_foto-detalle').html('<div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width:200px;height:200px;"><i class="fas fa-user fa-4x text-muted"></i></div>'); }
            // estadísticas y tablas
            $('#modalVerEmpleado #modal_detalle-retardos').text(datos.estadisticas?.retardos_mes ?? 0);
            $('#modalVerEmpleado #modal_detalle-comisiones').text(datos.estadisticas?.comisiones_pendientes ?? 0);
            $('#modalVerEmpleado #modal_detalle-ausencias').text(datos.estadisticas?.ausencias_mes ?? 0);

            // históriales
            var rHtml = '';
            if (datos.retardos && datos.retardos.length) {
                datos.retardos.forEach(function(r){ rHtml += '<tr><td>'+new Date(r.fecha).toLocaleDateString('es-ES')+'</td><td>'+r.minutos_retardo+'</td><td>'+(r.tipo=='menor'?'Menor':'Mayor')+'</td><td>'+(r.justificado? 'Sí':'No')+'</td></tr>'; });
            } else rHtml = '<tr><td colspan="4" class="text-center">No hay retardos</td></tr>';
            $('#modal_historial-retardos').html(rHtml);

            var cHtml = '';
            if (datos.comisiones && datos.comisiones.length) { datos.comisiones.forEach(function(c){ cHtml += '<tr><td>'+c.descripcion+'</td><td>$'+parseFloat(c.monto).toFixed(2)+'</td><td>'+(c.fecha_vencimiento? new Date(c.fecha_vencimiento).toLocaleDateString('es-ES'):'-')+'</td><td>'+(c.justificada? 'Sí':'No')+'</td></tr>'; }); } else cHtml = '<tr><td colspan="4" class="text-center">No hay comisiones</td></tr>';
            $('#modal_historial-comisiones').html(cHtml);

            var aHtml = '';
            if (datos.ausencias && datos.ausencias.length) { datos.ausencias.forEach(function(a){ aHtml += '<tr><td>'+new Date(a.fecha_inicio).toLocaleDateString('es-ES')+'</td><td>'+new Date(a.fecha_fin).toLocaleDateString('es-ES')+'</td><td>'+a.tipo+'</td><td>'+(a.justificada? 'Sí':'No')+'</td></tr>'; }); } else aHtml = '<tr><td colspan="4" class="text-center">No hay ausencias</td></tr>';
            $('#modal_historial-ausencias').html(aHtml);

            $('#modalVerEmpleado').modal('show');
        });
    });

    // Editar
    $(document).on('click', '.editar-empleado', function(){
        var id = $(this).data('id');
        cargarDatosEmpleado(id, function(datos){
            $('#modalEditarEmpleado input[name="id"]').val(datos.id);
            $('#modalEditarEmpleado input[name="nombre"]').val(datos.nombre);
            $('#modalEditarEmpleado input[name="apellido"]').val(datos.apellido);
            $('#modalEditarEmpleado input[name="rfc"]').val(datos.rfc);
            $('#modalEditarEmpleado input[name="curp"]').val(datos.curp);
            $('#modalEditarEmpleado input[name="area"]').val(datos.area);
            $('#modalEditarEmpleado select[name="jerarquia"]').val(datos.jerarquia);
            if (datos.foto_cara) $('#modalEditarEmpleado #modal_edit_foto-actual').html('<img src="<?php echo BASE_URL; ?>/'+datos.foto_cara+'" class="img-thumbnail" style="max-width:150px;">'); else $('#modalEditarEmpleado #modal_edit_foto-actual').html('');
            $('#modalEditarEmpleado form').attr('action','<?php echo BASE_URL; ?>/empleados/edit');
            $('#modalEditarEmpleado').modal('show');
        });
    });

    // Buscar simple en cliente
    $('#search-empleados').on('input', function(){
        var q = $(this).val().toLowerCase();
        $('#empleados-list > div').each(function(){
            var txt = $(this).text().toLowerCase();
            $(this).toggle(txt.indexOf(q) !== -1);
        });
    });

    // Previews
    $('#modal_foto_cara').on('change', function(e){ var f=e.target.files[0]; if(!f) { $('#modal_image-preview').hide(); return;} var reader=new FileReader(); reader.onload=function(ev){ $('#modal_preview-img').attr('src',ev.target.result); $('#modal_image-preview').show(); }; reader.readAsDataURL(f); });
    $('#modal_edit-foto_cara').on('change', function(e){ var f=e.target.files[0]; if(!f) { $('#modal_edit_image-preview').hide(); return;} var reader=new FileReader(); reader.onload=function(ev){ $('#modal_edit_preview-img').attr('src',ev.target.result); $('#modal_edit_image-preview').show(); }; reader.readAsDataURL(f); });

    // Toggle sección biométrica
    $('#modal_registrar_huella').on('change', function(){ $('#modal_dispositivo-section').toggle(this.checked); });

    // refresh button (simple: reload page)
    $('#btn-refresh').on('click', function(){ location.reload(); });
});
</script>
