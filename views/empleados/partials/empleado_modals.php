<?php
// Partial con los modales: Crear, Ver y Editar empleado
?>

<!-- Modal Crear Empleado -->
<div class="modal fade" id="modalCrearEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCrearEmpleado" method="POST" action="<?php echo BASE_URL; ?>/empleados/create" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="modal_nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="modal_apellido" name="apellido" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RFC</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="modal_rfc" name="rfc" maxlength="13" readonly>
                                <button class="btn btn-outline-primary" type="button" id="modal_generarCodigosBtn"><i class="fas fa-magic"></i> Generar RFC/CURP</button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CURP</label>
                            <input type="text" class="form-control" id="modal_curp" name="curp" maxlength="18" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área</label>
                            <input type="text" class="form-control" id="modal_area" name="area" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jerarquía</label>
                            <select class="form-select" id="modal_jerarquia" name="jerarquia" required>
                                <option value="">Seleccionar jerarquía</option>
                                <option value="Empleado">Empleado</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Gerente">Gerente</option>
                                <option value="Director">Director</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto del Empleado</label>
                        <input type="file" class="form-control" id="modal_foto_cara" name="foto_cara" accept="image/*">
                        <div class="form-text">Formatos: JPG, PNG. Máx 2MB.</div>
                        <div id="modal_image-preview" class="mt-2" style="display:none;"><img id="modal_preview-img" class="img-thumbnail" style="max-width:200px;max-height:200px;"/></div>
                    </div>

                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white"><i class="fas fa-fingerprint"></i> Registro Biométrico</div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="modal_registrar_huella" name="registrar_huella" value="1">
                                <label class="form-check-label" for="modal_registrar_huella"><strong>Registrar huella al crear empleado</strong></label>
                            </div>

                            <div id="modal_dispositivo-section" style="display:none;">
                                <label class="form-label">Dispositivo</label>
                                <select class="form-select" id="modal_dispositivo_huella" name="dispositivo_huella">
                                    <?php if (!empty($dispositivos)): foreach ($dispositivos as $dispositivo): ?>
                                        <option value="<?php echo htmlspecialchars($dispositivo['dispositivo_id']); ?>">Dispositivo <?php echo htmlspecialchars($dispositivo['dispositivo_id']); ?> - <?php echo htmlspecialchars($dispositivo['nombre']); ?></option>
                                    <?php endforeach; else: ?>
                                        <option value="1">Dispositivo 1</option>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">Seleccione el dispositivo para la captura.</div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formCrearEmpleado" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Ver Empleado -->
<div class="modal fade" id="modalVerEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div id="modal_foto-detalle"></div>
                    </div>
                    <div class="col-md-8">
                        <h5 id="modal_detalle-nombre"></h5>
                        <p><strong>RFC:</strong> <span id="modal_detalle-rfc"></span> &nbsp; <strong>CURP:</strong> <span id="modal_detalle-curp"></span></p>
                        <p><strong>Área:</strong> <span id="modal_detalle-area"></span> &nbsp; <strong>Jerarquía:</strong> <span id="modal_detalle-jerarquia"></span></p>
                        <p id="modal_detalle-estado"></p>
                    </div>
                </div>

                <hr/>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Resumen</h6>
                        <p><strong>Retardos:</strong> <span id="modal_detalle-retardos"></span></p>
                        <p><strong>Comisiones pendientes:</strong> <span id="modal_detalle-comisiones"></span></p>
                        <p><strong>Ausencias:</strong> <span id="modal_detalle-ausencias"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Historial de Retardos</h6>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Fecha</th><th>Min</th><th>Tipo</th><th>Just.</th></tr></thead><tbody id="modal_historial-retardos"></tbody></table></div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Comisiones</h6>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Desc</th><th>Monto</th><th>Venc.</th><th>Just.</th></tr></thead><tbody id="modal_historial-comisiones"></tbody></table></div>
                    </div>
                    <div class="col-md-6">
                        <h6>Ausencias</h6>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Inicio</th><th>Fin</th><th>Tipo</th><th>Just.</th></tr></thead><tbody id="modal_historial-ausencias"></tbody></table></div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-warning me-2" id="modal_btn-editar">Editar</button>
                <button class="btn btn-danger" id="modal_btn-eliminar">Eliminar</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Editar Empleado -->
<div class="modal fade" id="modalEditarEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarEmpleado" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Nombre</label><input class="form-control" name="nombre" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Apellido</label><input class="form-control" name="apellido" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">RFC</label><input class="form-control" name="rfc" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">CURP</label><input class="form-control" name="curp" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Área</label><input class="form-control" name="area" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Jerarquía</label>
+                            <select class="form-select" name="jerarquia" required>
+                                <option value="">Seleccionar</option>
+                                <option value="Empleado">Empleado</option>
+                                <option value="Supervisor">Supervisor</option>
+                                <option value="Gerente">Gerente</option>
+                                <option value="Director">Director</option>
+                            </select>
+                        </div>
+                    </div>
+                    <div class="mb-3">
+                        <label class="form-label">Foto (opcional)</label>
+                        <input type="file" class="form-control" id="modal_edit-foto_cara" name="foto_cara" accept="image/*">
+                        <div id="modal_edit_image-preview" style="display:none;" class="mt-2"><img id="modal_edit_preview-img" class="img-thumbnail" style="max-width:150px;"/></div>
+                        <div id="modal_edit_foto-actual" class="mt-2"></div>
+                    </div>
+                </form>
+            </div>
+            <div class="modal-footer">
+                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
+                <button type="submit" form="formEditarEmpleado" class="btn btn-primary">Guardar</button>
+            </div>
+        </div>
+    </div>
+</div>
