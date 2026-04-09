<!-- Modal Crear Empleado -->
<div class="modal fade" id="modalCrearEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Empleado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCrearEmpleado" action="<?php echo rtrim(BASE_URL, '/'); ?>/empleados/create" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="modal_nombres" name="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Primer Apellido</label>
                            <input type="text" class="form-control" id="modal_primer_apellido" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Segundo Apellido</label>
                            <input type="text" class="form-control" id="modal_segundo_apellido">
                            <input type="hidden" id="modal_hidden_apellido" name="apellido">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Nacimiento</label>
                            <input type="date" class="form-control" id="modal_fecha_nacimiento" name="fecha_nacimiento" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sexo</label>
                            <select class="form-select" id="modal_sexo" name="sexo" required>
                                <option value="">Seleccionar...</option>
                                <option value="H">Hombre</option>
                                <option value="M">Mujer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Entidad Federativa</label>
                            <select class="form-select" id="modal_entidad_federativa" name="entidad_federativa" required>
                                <option value="">Seleccionar...</option>
                                <option value="AS">Aguascalientes</option>
                                <option value="BC">Baja California</option>
                                <option value="BS">Baja California Sur</option>
                                <option value="CC">Campeche</option>
                                <option value="CL">Coahuila</option>
                                <option value="CM">Colima</option>
                                <option value="CS">Chiapas</option>
                                <option value="CH">Chihuahua</option>
                                <option value="DF">Ciudad de México</option>
                                <option value="DG">Durango</option>
                                <option value="GT">Guanajuato</option>
                                <option value="GR">Guerrero</option>
                                <option value="HG">Hidalgo</option>
                                <option value="JC">Jalisco</option>
                                <option value="MC">México</option>
                                <option value="MN">Michoacán</option>
                                <option value="MS">Morelos</option>
                                <option value="NT">Nayarit</option>
                                <option value="NL">Nuevo León</option>
                                <option value="OC">Oaxaca</option>
                                <option value="PL">Puebla</option>
                                <option value="QT">Querétaro</option>
                                <option value="QR">Quintana Roo</option>
                                <option value="SP">San Luis Potosí</option>
                                <option value="SL">Sinaloa</option>
                                <option value="SR">Sonora</option>
                                <option value="TC">Tabasco</option>
                                <option value="TS">Tamaulipas</option>
                                <option value="TL">Tlaxcala</option>
                                <option value="VZ">Veracruz</option>
                                <option value="YN">Yucatán</option>
                                <option value="ZS">Zacatecas</option>
                                <option value="NE">Nacido en el Extranjero</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="modal_generarCodigosBtn" style="border-color: #9F2241; color: #9F2241;">
                                <i class="fas fa-magic me-1"></i> Generar RFC y CURP
                            </button>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control" id="modal_rfc" name="rfc" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CURP</label>
                            <input type="text" class="form-control" id="modal_curp" name="curp" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Área</label>
                            <input type="text" class="form-control" name="area" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jerarquía</label>
                            <select class="form-select" name="jerarquia" required>
                                <option value="Empleado">Empleado</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Gerente">Gerente</option>
                                <option value="Director">Director</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto</label>
                            <input type="file" class="form-control" id="modal_foto_cara" name="foto_cara" accept="image/*">
                            <div id="modal_image-preview" class="mt-2" style="display:none;">
                                <img id="modal_preview-img" src="" class="img-thumbnail" style="max-height: 150px;">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="modal_registrar_huella">
                                <label class="form-check-label" for="modal_registrar_huella">Registrar Huella Digital</label>
                            </div>
                        </div>
                        <div class="col-12" id="modal_dispositivo-section" style="display:none;">
                            <label class="form-label">Dispositivo Biométrico</label>
                            <select class="form-select" id="modal_dispositivo_huella">
                                <option value="">Seleccione...</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cancelar</button>
                <button type="submit" form="formCrearEmpleado" class="btn btn-primary" style="background-color: #9F2241; border-color: #9F2241;"><i class="fas fa-save me-1"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Empleado -->
<div class="modal fade" id="modalVerEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-user me-2"></i>Detalle del Empleado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3 text-center border-end">
                        <div id="modal_foto-detalle" class="mb-3"></div>
                        <h5 id="modal_detalle-nombre" class="fw-bold mb-1"></h5>
                        <p id="modal_detalle-area" class="text-muted mb-1"></p>
                        <p id="modal_detalle-jerarquia" class="small text-muted mb-3"></p>
                        <div id="modal_detalle-estado" class="mb-3"></div>
                        <div id="modal_estado_biometrico" class="mb-3"></div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" id="modal_btn-editar" data-empleado-id="">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-outline-danger btn-sm" id="modal_btn-eliminar" data-empleado-id="">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                            <button class="btn btn-outline-success btn-sm" id="modal_btn-registrar-huella" data-empleado-id="">
                                <i class="fas fa-fingerprint"></i> Biometría
                            </button>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <small class="text-muted d-block">RFC</small>
                                <span id="modal_detalle-rfc" class="fw-bold"></span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">CURP</small>
                                <span id="modal_detalle-curp" class="fw-bold"></span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">ID Sistema</small>
                                <span id="modal_detalle-id" class="fw-bold"></span>
                            </div>
                            <div class="col-md-4 mt-2">
                                <small class="text-muted d-block">Fecha Nacimiento</small>
                                <span id="modal_detalle-fecha-nacimiento"></span>
                            </div>
                            <div class="col-md-4 mt-2">
                                <small class="text-muted d-block">Sexo</small>
                                <span id="modal_detalle-sexo"></span>
                            </div>
                            <div class="col-md-4 mt-2">
                                <small class="text-muted d-block">Entidad</small>
                                <span id="modal_detalle-entidad"></span>
                            </div>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-4 col-md-2">
                                <div class="p-2 border rounded text-center bg-light">
                                    <small class="d-block text-muted" style="font-size:0.7rem;">Asistencias</small>
                                    <strong id="modal_stat_asistencias" class="h5 mb-0 text-primary">0</strong>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 border rounded text-center bg-light">
                                    <small class="d-block text-muted" style="font-size:0.7rem;">Retardos</small>
                                    <strong id="modal_stat_retardos_total" class="h5 mb-0 text-warning">0</strong>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 border rounded text-center bg-light">
                                    <small class="d-block text-muted" style="font-size:0.7rem;">Justificados</small>
                                    <strong id="modal_stat_retardos_justificados" class="h5 mb-0 text-success">0</strong>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 border rounded text-center bg-light">
                                    <small class="d-block text-muted" style="font-size:0.7rem;">Comisiones</small>
                                    <strong id="modal_stat_comisiones_pendientes" class="h5 mb-0 text-info">0</strong>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 border rounded text-center bg-light">
                                    <small class="d-block text-muted" style="font-size:0.7rem;">Ausencias</small>
                                    <strong id="modal_stat_ausencias" class="h5 mb-0 text-danger">0</strong>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 border rounded text-center bg-light">
                                    <small class="d-block text-muted" style="font-size:0.7rem;">Sanciones</small>
                                    <strong id="modal_stat_sanciones_activas" class="h5 mb-0 text-danger">0</strong>
                                </div>
                            </div>
                        </div>

                        <ul class="nav nav-tabs nav-fill" role="tablist" style="font-size: 0.75rem;">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-info-general">Info. Gral.</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-asistencias">Asist.</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-retardos">Retardos</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-incidencias">Incid.</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-vacaciones">Vacac.</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-licencias-medicas">Lic.Med</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-comisiones">Comis.</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-dias-economicos">DíasE</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-cuidados-maternos">Cuid.M</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-cuidados-paternos">Cuid.P</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-constancias">Const.</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-sanciones">Sanc.</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-ciclos">Ciclos</a></li>
                        </ul>
                        <div class="tab-content p-3 border border-top-0">
                            <!-- Tab Información General con Plazas y Ciclos -->
                            <div class="tab-pane fade show active" id="tab-info-general">
                                <div id="modal_plazas_ciclo_container">
                                    <div class="text-center text-muted">Cargando información de plazas y ciclos...</div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-asistencias">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Fecha</th><th>Hora</th><th>Tipo</th><th>Dispositivo</th></tr></thead>
                                    <tbody id="modal_historial-asistencias"></tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="tab-retardos">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Fecha</th><th>Minutos</th><th>Tipo</th><th>Justificado</th><th>Acción</th></tr></thead>
                                    <tbody id="modal_historial-retardos"></tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="tab-incidencias">
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-primary" onclick="abrirRegistroIncidencia()">
                                        <i class="fas fa-plus"></i> Nueva Incidencia
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tipo Asistencia</th>
                                                <th>Tipo Justificación</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal_historial-incidencias">
                                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-vacaciones">
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-success" onclick="abrirRegistroVacaciones()">
                                        <i class="fas fa-plus"></i> Nueva Solicitud
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-success">
                                            <tr>
                                                <th>Fecha Inicio</th>
                                                <th>Fecha Fin</th>
                                                <th>Días</th>
                                                <th>Motivo</th>
                                                <th>Estatus</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal_historial-vacaciones">
                                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-licencias-medicas">
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-danger" onclick="abrirRegistroLicencia()">
                                        <i class="fas fa-plus"></i> Nueva Licencia
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-danger">
                                            <tr>
                                                <th>Fecha Inicio</th>
                                                <th>Fecha Fin</th>
                                                <th>Días</th>
                                                <th>Diagnóstico</th>
                                                <th>Estatus</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal_historial-licencias">
                                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-cuidados-maternos">
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-info" onclick="abrirRegistroCuidadosMaternos()">
                                        <i class="fas fa-plus"></i> Nueva Solicitud
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-info">
                                            <tr>
                                                <th>Fecha Inicio</th>
                                                <th>Fecha Fin</th>
                                                <th>Días</th>
                                                <th>Motivo</th>
                                                <th>Estatus</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal_historial-cuidados-maternos">
                                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-cuidados-paternos">
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-primary" onclick="abrirRegistroCuidadosPaternos()">
                                        <i class="fas fa-plus"></i> Nueva Solicitud
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead style="background-color: #6c757d; color: white;">
                                            <tr>
                                                <th>Fecha Inicio</th>
                                                <th>Fecha Fin</th>
                                                <th>Días</th>
                                                <th>Motivo</th>
                                                <th>Estatus</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal_historial-cuidados-paternos">
                                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-constancias">
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-warning" onclick="abrirRegistroConstancia()">
                                        <i class="fas fa-plus"></i> Nueva Constancia
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead style="background-color: #ffc107; color: black;">
                                            <tr>
                                                <th>Fecha Inicio</th>
                                                <th>Fecha Fin</th>
                                                <th>Días</th>
                                                <th>Tipo</th>
                                                <th>Estatus</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal_historial-constancias">
                                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-comisiones">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Descripción</th><th>Monto</th><th>Vencimiento</th><th>Justificada</th></tr></thead>
                                    <tbody id="modal_historial-comisiones"></tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="tab-sanciones">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Motivo</th><th>Estado</th></tr></thead>
                                    <tbody id="modal_historial-sanciones"></tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="tab-dias-economicos">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Fecha Inicio</th><th>Fecha Fin</th><th>Días</th><th>Estatus</th><th>Motivo</th></tr></thead>
                                    <tbody id="modal_historial-dias-economicos">
                                        <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="tab-ciclos">
                                <div class="row">
                                    <div class="col-md-5">
                                        <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle me-1"></i>Asignar Ciclo</h6>
                                        <form id="formAsignarCiclo">
                                            <input type="hidden" id="ciclo_empleado_id" name="empleado_id">
                                            <div class="mb-3">
                                                <label class="form-label">Seleccionar Ciclo</label>
                                                <select class="form-select form-select-sm" id="ciclo_select" name="ciclo_id" required>
                                                    <option value="">Seleccionar...</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Fecha Inicio</label>
                                                <input type="date" class="form-control form-control-sm" id="ciclo_fecha_inicio" name="fecha_inicio" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Fecha Fin</label>
                                                <input type="date" class="form-control form-control-sm" id="ciclo_fecha_fin" name="fecha_fin" required>
                                            </div>
                                            <button type="submit" class="btn btn-sm" style="background-color: #9F2241; color: white;">
                                                <i class="fas fa-save me-1"></i>Asignar
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-7">
                                        <h6 class="fw-bold mb-3"><i class="fas fa-calendar-check me-1"></i>Ciclos Asignados</h6>
                                        <div id="ciclos_asignados_container">
                                            <p class="text-muted small">No hay ciclos asignados</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Empleado -->
<div class="modal fade" id="modalEditarEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Editar Empleado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarEmpleado" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido</label>
                            <input type="text" class="form-control" name="apellido" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control" name="rfc" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CURP</label>
                            <input type="text" class="form-control" name="curp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sexo</label>
                            <select class="form-select" name="sexo">
                                <option value="">Seleccionar...</option>
                                <option value="H">Hombre</option>
                                <option value="M">Mujer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Entidad Federativa</label>
                            <select class="form-select" name="entidad_federativa">
                                <option value="">Seleccionar...</option>
                                <option value="AS">Aguascalientes</option>
                                <option value="BC">Baja California</option>
                                <option value="BS">Baja California Sur</option>
                                <option value="CC">Campeche</option>
                                <option value="CL">Coahuila</option>
                                <option value="CM">Colima</option>
                                <option value="CS">Chiapas</option>
                                <option value="CH">Chihuahua</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="DG">Durango</option>
                                <option value="GT">Guanajuato</option>
                                <option value="GR">Guerrero</option>
                                <option value="HG">Hidalgo</option>
                                <option value="JC">Jalisco</option>
                                <option value="MC">México</option>
                                <option value="MN">Michoacán</option>
                                <option value="MS">Morelos</option>
                                <option value="NT">Nayarit</option>
                                <option value="NL">Nuevo León</option>
                                <option value="OC">Oaxaca</option>
                                <option value="PL">Puebla</option>
                                <option value="QT">Querétaro</option>
                                <option value="QR">Quintana Roo</option>
                                <option value="SP">San Luis Potosí</option>
                                <option value="SL">Sinaloa</option>
                                <option value="SR">Sonora</option>
                                <option value="TC">Tabasco</option>
                                <option value="TS">Tamaulipas</option>
                                <option value="TL">Tlaxcala</option>
                                <option value="VZ">Veracruz</option>
                                <option value="YN">Yucatán</option>
                                <option value="ZS">Zacatecas</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Puesto</label>
                            <input type="text" class="form-control" name="puesto">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jefe Directo (Mando)</label>
                            <select class="form-select" name="jefe_directo_id" id="modal_edit_jefe_directo_id">
                                <option value="">Seleccionar...</option>
                                <?php 
                                require_once __DIR__ . '/../../models/Catalogo.php';
                                $catalogo = new Catalogo();
                                $mandos = $catalogo->getAllMandos();
                                foreach ($mandos as $mando): 
                                ?>
                                <option value="<?= htmlspecialchars($mando['clave_area']) ?>" 
                                    data-area="<?= htmlspecialchars($mando['area']) ?>">
                                    <?= htmlspecialchars($mando['nombre_mando']) ?> - <?= htmlspecialchars($mando['area']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Área</label>
                            <input type="text" class="form-control" name="area" id="modal_edit_area" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Clave Área (Del Mando)</label>
                            <input type="text" class="form-control" name="clave_depto" id="modal_edit_clave_depto" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jerarquía</label>
                            <select class="form-select" name="jerarquia">
                                <option value="Empleado">Empleado</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Gerente">Gerente</option>
                                <option value="Director">Director</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto</label>
                            <input type="file" class="form-control" id="modal_edit-foto_cara" name="foto_cara" accept="image/*">
                            <div class="mt-2 d-flex gap-3 align-items-center">
                                <div id="modal_edit_foto-actual"></div>
                                <div id="modal_edit_image-preview" style="display:none;">
                                    <img id="modal_edit_preview-img" src="" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cancelar</button>
                <button type="submit" form="formEditarEmpleado" class="btn btn-primary" style="background-color: #9F2241; border-color: #9F2241;"><i class="fas fa-save me-1"></i> Actualizar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Horarios del Empleado -->
<div class="modal fade" id="modalVerHorarios" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-clock me-2"></i>Horarios del Empleado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-horarios-contenido"></div>
            </div>
            <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script para combinar apellidos en el campo oculto 'apellido'
    var p = document.getElementById('modal_primer_apellido');
    var s = document.getElementById('modal_segundo_apellido');
    var h = document.getElementById('modal_hidden_apellido');
    function updateApellido() {
        if(h) h.value = ((p ? p.value : '') + ' ' + (s ? s.value : '')).trim();
    }
    if(p) p.addEventListener('input', updateApellido);
    if(s) s.addEventListener('input', updateApellido);

    // Auto-fill area and clave_depto when mando is selected in edit modal
    const editJefeSelect = document.getElementById('modal_edit_jefe_directo_id');
    if (editJefeSelect) {
        editJefeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const areaMando = selectedOption.dataset.area || '';
            const claveArea = selectedOption.value || '';
            
            const areaInput = document.getElementById('modal_edit_area');
            const claveDeptoInput = document.getElementById('modal_edit_clave_depto');
            
            if (areaInput && areaMando) {
                areaInput.value = areaMando;
            }
            if (claveDeptoInput && claveArea) {
                claveDeptoInput.value = claveArea;
            }
        });
    }
});
</script>