<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../helpers/Csrf.php';

class ResumenJustificacionesController extends BaseController {
    
    public function index() {
        $this->requireAuth();
        
        $db = Database::getInstance()->getConnection();
        
        // Obtener estadísticas generales
        $estadisticas = $this->obtenerEstadisticasGenerales($db);
        
        // Obtener justificaciones por tipo
        $porTipo = $this->obtenerPorTipo($db);
        
        // Obtener empleados con más retardos
        $empleadosProblematica = $this->obtenerEmpleadosProblematica($db);
        
        // Obtener justificaciones recientes
        $recientes = $this->obtenerRecientes($db);
        
        $csrfToken = Csrf::token();
        
        $content = <<<HTML
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-clipboard-check me-2"></i> Resumen de Justificaciones</h2>
                <a href="{$this->getBaseUrl()}/ai" class="btn btn-primary">
                    <i class="fas fa-robot me-2"></i> Ir a AI Dashboard
                </a>
            </div>
            
            {$this->renderStats($estadisticas)}
            
            <div class="row mt-4">
                <div class="col-md-6">
                    {$this->renderPorTipo($porTipo)}
                </div>
                <div class="col-md-6">
                    {$this->renderRecientes($recientes)}
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    {$this->renderEmpleadosProblematica($empleadosProblematica)}
                </div>
            </div>
        </div>
        
        <!-- Modal para ver detalles del empleado -->
        <div class="modal fade" id="modalDetalleEmpleado" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #9F2241, #691C32); color: white;">
                        <h5 class="modal-title text-white"><i class="fas fa-user me-2"></i> Detalle del Empleado</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modalDetalleContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2">Cargando información...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <a href="#" id="btnVerEmpleadoCompleto" class="btn btn-primary" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i> Ver Perfil Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .card-empleado {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: box-shadow 0.2s ease;
            height: 100%;
        }
        .card-empleado:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .card-empleado .card-header-custom {
            padding: 14px 18px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .card-empleado .card-header-custom i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        .card-body-toggle {
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.25s ease, padding 0.3s ease;
            max-height: 300px;
            opacity: 1;
        }
        .card-body-toggle.collapsed {
            max-height: 0 !important;
            opacity: 0;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        .card-empleado .card-body-custom {
            padding: 16px 18px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .stat-item .label {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .badge-stat {
            font-size: 0.75rem;
            padding: 3px 12px;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .stat-value {
            font-weight: 700;
            font-size: 1.15rem;
            min-width: 28px;
            text-align: right;
            line-height: 1;
        }

        .filtro-mes-btn {
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            padding: 5px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .filtro-mes-btn:hover {
            border-color: #9F2241;
            color: #9F2241;
            background: #fff5f7;
        }
        .filtro-mes-btn.active {
            background: #9F2241;
            border-color: #9F2241;
            color: white;
            box-shadow: 0 2px 8px rgba(159,34,65,0.25);
        }

        .tabla-elegante {
            border: none;
            font-size: 0.85rem;
        }
        .tabla-elegante thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            border-bottom: 2px solid #9F2241;
        }
        .tabla-elegante tbody td {
            padding: 8px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        .tabla-elegante tbody tr:hover {
            background: #fff8f9;
        }

        .resumen-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            color: #495057;
            border: 1px solid #e9ecef;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .resumen-chip strong {
            color: #212529;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
        }
        .info-row i {
            width: 18px;
            color: #9F2241;
            font-size: 0.9rem;
            text-align: center;
        }
        .info-row .info-label {
            color: #6c757d;
            font-size: 0.88rem;
            min-width: 100px;
        }
        .info-row .info-value {
            color: #212529;
            font-weight: 500;
            font-size: 0.88rem;
        }
        </style>
        
        <script>
        const BASE_URL_RESUMEN = '{$this->getBaseUrl()}';
        
        function verDetalleEmpleado(empleadoId) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleEmpleado'));
            const content = document.getElementById('modalDetalleContent');
            const btnVerCompleto = document.getElementById('btnVerEmpleadoCompleto');
            
            content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando información...</p></div>';
            btnVerCompleto.href = BASE_URL_RESUMEN + '/empleados/' + empleadoId + '/ver';
            modal.show();
            
            fetch(BASE_URL_RESUMEN + '/empleados/resumen-completo/' + empleadoId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = '<div class="alert alert-danger">Error: ' + data.error + '</div>';
                        return;
                    }
                    
                    const retardosJust = (data.retardos || []).filter(r => r.justificado).length;
                    const retardosSinJust = (data.retardos || []).filter(r => !r.justificado).length;
                    
                    let html = '<div class="row g-3">';

                    // Datos Personales
                    html += '<div class="col-md-4"><div class="card-empleado">';
                    html += '<div class="card-header-custom" style="border-left: 4px solid #9F2241; border-radius: 12px 12px 0 0; cursor:pointer;" id="toggleDatosPersonales">';
                    html += '<div class="d-flex align-items-center gap-2"><i class="fas fa-user-circle" style="color:#9F2241;"></i> Datos Personales</div>';
                    html += '<i class="fas fa-eye-slash text-muted" id="iconDatosPersonales" style="font-size:0.8rem;transition:opacity 0.2s;"></i>';
                    html += '</div>';
                    html += '<div class="card-body-custom card-body-toggle" id="bodyDatosPersonales">';
                    html += '<div class="info-row"><i class="fas fa-user"></i><span class="info-label">Nombre</span><span class="info-value">' + (data.nombre || '-') + ' ' + (data.apellido || '-') + '</span></div>';
                    html += '<div class="info-row"><i class="fas fa-id-card"></i><span class="info-label">RFC</span><span class="info-value">' + (data.rfc || 'N/A') + '</span></div>';
                    html += '<div class="info-row"><i class="fas fa-building"></i><span class="info-label">Área</span><span class="info-value">' + (data.area || 'No asignada') + '</span></div>';
                    html += '<div class="info-row"><i class="fas fa-layer-group"></i><span class="info-label">Jerarquía</span><span class="info-value">' + (data.jerarquia || 'No asignada') + '</span></div>';
                    html += '<div class="info-row"><i class="fas fa-calendar-check"></i><span class="info-label">Ingreso</span><span class="info-value">' + (data.fecha_ingreso || 'N/A') + '</span></div>';
                    html += '</div></div></div>';

                    // Estadísticas de Retardos
                    html += '<div class="col-md-4"><div class="card-empleado">';
                    html += '<div class="card-header-custom" style="border-left: 4px solid #e67e22; border-radius: 12px 12px 0 0; cursor:pointer;" id="toggleEstadisticas">';
                    html += '<div class="d-flex align-items-center gap-2"><i class="fas fa-chart-simple" style="color:#e67e22;"></i> Estadísticas de Retardos</div>';
                    html += '<i class="fas fa-eye-slash text-muted" id="iconEstadisticas" style="font-size:0.8rem;transition:opacity 0.2s;"></i>';
                    html += '</div>';
                    html += '<div class="card-body-custom card-body-toggle" id="bodyEstadisticas">';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-calendar-day me-1 text-primary"></i>Total Asistencias</span><span class="stat-value text-primary" id="statAsistencias">' + (data.estadisticas?.total_asistencias || 0) + '</span></div>';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-exclamation-triangle me-1 text-warning"></i>Total Retardos</span><span class="stat-value text-warning" id="statRetardos">' + (data.estadisticas?.retardos_mes || 0) + '</span></div>';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-check-circle me-1 text-success"></i>Justificados</span><span class="stat-value text-success" id="statJustificados">' + retardosJust + '</span></div>';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-times-circle me-1 text-danger"></i>Sin Justificar</span><span class="stat-value text-danger" id="statSinJustificar">' + retardosSinJust + '</span></div>';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-clock me-1 text-info"></i>Incidencias Pendientes</span><span class="stat-value text-info" id="statIncidencias">' + (data.estadisticas?.incidencias_pendientes || 0) + '</span></div>';
                    html += '</div></div></div>';

                    // Resumen Quincena
                    html += '<div class="col-md-4"><div class="card-empleado">';
                    html += '<div class="card-header-custom" style="border-left: 4px solid #2c3e50; border-radius: 12px 12px 0 0; cursor:pointer;" id="toggleQuincena">';
                    html += '<div class="d-flex align-items-center gap-2"><i class="fas fa-clipboard-list" style="color:#2c3e50;"></i> Resumen Quincena</div>';
                    html += '<i class="fas fa-eye-slash text-muted" id="iconQuincena" style="font-size:0.8rem;transition:opacity 0.2s;"></i>';
                    html += '</div>';
                    html += '<div class="card-body-custom card-body-toggle" id="bodyQuincena">';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-stopwatch me-1 text-danger"></i>Retardos Acumulados</span><span class="stat-value text-danger">' + (data.resumen_quincena?.retardos_acumulados || 0) + '</span></div>';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-user-slash me-1 text-secondary"></i>Ausencias</span><span class="stat-value text-secondary">' + (data.estadisticas?.ausencias_mes || 0) + '</span></div>';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-check-double me-1 text-success"></i>Ausencias Justificadas</span><span class="stat-value text-success">' + (data.estadisticas?.ausencias_justificadas || 0) + '</span></div>';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-umbrella-beach me-1 text-info"></i>Vacaciones</span><span class="stat-value text-info">' + (data.vacaciones?.length || 0) + '</span></div>';
                    html += '<div class="stat-item"><span class="label"><i class="fas fa-hospital me-1 text-info"></i>Licencias Médicas</span><span class="stat-value text-info">' + (data.licencias_medicas?.length || 0) + '</span></div>';
                    html += '</div></div></div></div>';
                    
                    // Desglose por mes
                    const todosRetardos = data.retardos || [];
                    const todasAsistencias = data.asistencias || [];
                    const statsOriginales = {
                        asistencias: data.estadisticas?.total_asistencias || 0,
                        retardos: data.estadisticas?.retardos_mes || 0,
                        justificados: (data.retardos || []).filter(r => r.justificado).length,
                        sinJustificar: (data.retardos || []).filter(r => !r.justificado).length,
                        incidencias: data.estadisticas?.incidencias_pendientes || 0
                    };

                    if (todosRetardos.length > 0 || todasAsistencias.length > 0) {
                        const meses = {};
                        todosRetardos.forEach(r => {
                            const m = (r.fecha || '').substring(0, 7);
                            if (!m) return;
                            if (!meses[m]) meses[m] = { ret: 0, menores: 0, mayores: 0, faltas: 0, just: 0, sinJust: 0, asi: 0, inc: 0 };
                            meses[m].ret++;
                            const tipo = r.tipo_retraso || r.tipo || '';
                            if (tipo === 'retardo_menor') meses[m].menores++;
                            else if (tipo === 'retardo_mayor') meses[m].mayores++;
                            else if (tipo === 'falta') meses[m].faltas++;
                            if (r.justificado) meses[m].just++;
                            else meses[m].sinJust++;
                        });
                        todasAsistencias.forEach(a => {
                            const m = (a.fecha || '').substring(0, 7);
                            if (!m) return;
                            if (!meses[m]) meses[m] = { ret: 0, menores: 0, mayores: 0, faltas: 0, just: 0, sinJust: 0, asi: 0, inc: 0 };
                            meses[m].asi++;
                            if (a.tipo_asistencia === 'por_definir') meses[m].inc++;
                        });
                        const mesesKeys = Object.keys(meses).sort();

                        html += '<div class="card-empleado mt-3">';
                        html += '<div class="card-header-custom" style="border-left: 4px solid #6c5ce7; border-radius: 12px 12px 0 0; cursor:pointer;" id="toggleMeses">';
                        html += '<div class="d-flex align-items-center gap-2"><i class="fas fa-calendar-alt" style="color:#6c5ce7;"></i> Retardos por Mes</div>';
                        html += '<i class="fas fa-eye-slash text-muted" id="iconMeses" style="font-size:0.8rem;transition:opacity 0.2s;"></i>';
                        html += '</div>';
                        html += '<div class="card-body-custom card-body-toggle" id="bodyMeses">';
                        html += '<div class="d-flex flex-wrap gap-2 mb-3" id="filtroMeses">';
                        html += '<button class="filtro-mes-btn active" data-mes="__todos">Todos</button>';
                        mesesKeys.forEach(m => {
                            const [anio, mes] = m.split('-');
                            const fecha = new Date(parseInt(anio), parseInt(mes) - 1);
                            const nombre = fecha.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
                            html += '<button class="filtro-mes-btn" data-mes="' + m + '">' + nombre + ' <span class="badge rounded-pill bg-light text-dark ms-1" style="font-size:0.7rem;">' + (meses[m].ret + meses[m].asi) + '</span></button>';
                        });
                        html += '</div>';
                        html += '<div id="resumenMes" class="d-flex flex-column gap-1 mb-3 px-3 py-2 rounded-3" style="background:#f8f9fa;"></div>';
                        html += '<div class="table-responsive">';
                        html += '<table class="table tabla-elegante" id="tablaRetardos">';
                        html += '<thead><tr><th>Fecha</th><th>Hora Entrada</th><th>Minutos</th><th>Tipo</th><th>Estado</th></tr></thead>';
                        html += '<tbody id="cuerpoRetardos"></tbody>';
                        html += '</table></div></div></div>';

                        content.innerHTML = html;

                        function mostrarMes(mesSel) {
                            const filtrados = mesSel === '__todos' ? todosRetardos : todosRetardos.filter(r => (r.fecha || '').substring(0, 7) === mesSel);
                            const mm = meses[mesSel];

                            // Actualizar estadísticas
                            document.getElementById('statAsistencias').textContent = mesSel === '__todos' ? statsOriginales.asistencias : (mm ? mm.asi : 0);
                            document.getElementById('statRetardos').textContent = mesSel === '__todos' ? statsOriginales.retardos : (mm ? mm.ret : 0);
                            document.getElementById('statJustificados').textContent = mesSel === '__todos' ? statsOriginales.justificados : (mm ? mm.just : 0);
                            document.getElementById('statSinJustificar').textContent = mesSel === '__todos' ? statsOriginales.sinJustificar : (mm ? mm.sinJust : 0);
                            document.getElementById('statIncidencias').textContent = mesSel === '__todos' ? statsOriginales.incidencias : (mm ? mm.inc : 0);

                            // Actualizar tabla
                            const tbody = document.getElementById('cuerpoRetardos');
                            tbody.innerHTML = '';
                            const mostrar = filtrados.slice(0, 50);
                            mostrar.forEach(r => {
                                const just = r.justificado;
                                const estado = just
                                    ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Justificado</span>'
                                    : '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Sin Justificar</span>';
                                const tipo = r.tipo_retraso || r.tipo || '-';
                                const tipoBadge = tipo === 'retardo_menor'
                                    ? '<span class="badge bg-warning text-dark">Menor</span>'
                                    : tipo === 'retardo_mayor'
                                    ? '<span class="badge bg-danger">Mayor</span>'
                                    : tipo === 'falta'
                                    ? '<span class="badge bg-dark">Falta</span>'
                                    : '<span>' + tipo + '</span>';
                                const hora = (r.hora_entrada || '').substring(0, 5) || '-';
                                tbody.innerHTML += '<tr><td><i class="far fa-calendar-alt me-1 text-muted"></i>' + (r.fecha || '-') + '</td><td>' + hora + '</td><td><span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>' + (r.minutos_retardo || 0) + ' min</span></td><td>' + tipoBadge + '</td><td>' + estado + '</td></tr>';
                            });
                            if (filtrados.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-check-circle me-2 text-success"></i>Sin retardos en este período</td></tr>';
                            }

                            // Actualizar resumen
                            const info = document.getElementById('resumenMes');
                            if (mesSel === '__todos') {
                                const totalJ = filtrados.filter(r => r.justificado).length;
                                const totalSJ = filtrados.filter(r => !r.justificado).length;
                                info.innerHTML = '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-list me-2"></i>Total Retardos</span><strong>' + filtrados.length + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-check-circle text-success me-2"></i>Justificados</span><strong>' + totalJ + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-times-circle text-danger me-2"></i>Sin Justificar</span><strong>' + totalSJ + '</strong></div>';
                            } else if (mm) {
                                info.innerHTML = '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-calendar-check me-2"></i>Asistencias</span><strong>' + mm.asi + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-exclamation-triangle text-warning me-2"></i>Retardos</span><strong>' + mm.ret + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-arrow-right text-warning me-2"></i>Menores</span><strong>' + mm.menores + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-arrow-right text-danger me-2"></i>Mayores</span><strong>' + mm.mayores + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-ban text-dark me-2"></i>Faltas</span><strong>' + mm.faltas + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-check-circle text-success me-2"></i>Justificados</span><strong>' + mm.just + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-times-circle text-danger me-2"></i>Sin Justificar</span><strong>' + mm.sinJust + '</strong></div>'
                                    + '<div class="resumen-chip w-100 justify-content-between"><span><i class="fas fa-clock text-info me-2"></i>Pendientes</span><strong>' + mm.inc + '</strong></div>';
                            }
                        }

                        document.getElementById('filtroMeses').addEventListener('click', function(e) {
                            const btn = e.target.closest('button');
                            if (!btn) return;
                            const mes = btn.dataset.mes;
                            if (mes === '__todos') {
                                this.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                                btn.classList.add('active');
                                mostrarMes('__todos');
                            } else if (btn.classList.contains('active')) {
                                btn.classList.remove('active');
                                this.querySelector('[data-mes="__todos"]').classList.add('active');
                                mostrarMes('__todos');
                            } else {
                                this.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                                btn.classList.add('active');
                                mostrarMes(mes);
                            }
                        });

                        mostrarMes('__todos');

                        // Toggle Datos Personales
                        const toggleDP = document.getElementById('toggleDatosPersonales');
                        const bodyDP = document.getElementById('bodyDatosPersonales');
                        const iconDP = document.getElementById('iconDatosPersonales');
                        let visibleDP = true;
                        toggleDP.addEventListener('click', () => {
                            visibleDP = !visibleDP;
                            bodyDP.classList.toggle('collapsed', !visibleDP);
                            iconDP.className = visibleDP ? 'fas fa-eye-slash text-muted' : 'fas fa-eye text-muted';
                        });

                        // Toggle Estadísticas de Retardos
                        const toggleEst = document.getElementById('toggleEstadisticas');
                        const bodyEst = document.getElementById('bodyEstadisticas');
                        const iconEst = document.getElementById('iconEstadisticas');
                        let visibleEst = true;
                        toggleEst.addEventListener('click', () => {
                            visibleEst = !visibleEst;
                            bodyEst.classList.toggle('collapsed', !visibleEst);
                            iconEst.className = visibleEst ? 'fas fa-eye-slash text-muted' : 'fas fa-eye text-muted';
                        });

                        // Toggle Resumen Quincena
                        const toggleQuin = document.getElementById('toggleQuincena');
                        const bodyQuin = document.getElementById('bodyQuincena');
                        const iconQuin = document.getElementById('iconQuincena');
                        let visibleQuin = true;
                        toggleQuin.addEventListener('click', () => {
                            visibleQuin = !visibleQuin;
                            bodyQuin.classList.toggle('collapsed', !visibleQuin);
                            iconQuin.className = visibleQuin ? 'fas fa-eye-slash text-muted' : 'fas fa-eye text-muted';
                        });

                        // Toggle Retardos por Mes
                        const toggleMes = document.getElementById('toggleMeses');
                        const bodyMes = document.getElementById('bodyMeses');
                        const iconMes = document.getElementById('iconMeses');
                        let visibleMes = true;
                        toggleMes.addEventListener('click', () => {
                            visibleMes = !visibleMes;
                            bodyMes.classList.toggle('collapsed', !visibleMes);
                            iconMes.className = visibleMes ? 'fas fa-eye-slash text-muted' : 'fas fa-eye text-muted';
                        });
                    } else {
                        content.innerHTML = html;
                    }
                })
                .catch(err => {
                    content.innerHTML = '<div class="alert alert-danger">Error al cargar los datos: ' + err.message + '</div>';
                });
        }
        </script>
        HTML;
        
        include __DIR__ . '/../views/layout.php';
    }
    
    private function obtenerEstadisticasGenerales($db) {
        $stats = [];
        
        // Total retardos
        $stmt = $db->query("SELECT COUNT(*) as total FROM retardos");
        $stats['total_retardos'] = $stmt->fetch()['total'] ?? 0;
        
        // Retardos justificados
        $stmt = $db->query("SELECT COUNT(*) as total FROM retardos WHERE justificado = 1");
        $stats['retardos_justificados'] = $stmt->fetch()['total'] ?? 0;
        
        // Retardos sin justificar
        $stats['retardos_sin_justificar'] = $stats['total_retardos'] - $stats['retardos_justificados'];
        
        // Total vacaciones
        $stmt = $db->query("SELECT COUNT(*) as total FROM vacaciones");
        $stats['total_vacaciones'] = $stmt->fetch()['total'] ?? 0;
        
        // Total licencias médicas
        $stmt = $db->query("SELECT COUNT(*) as total FROM licencias_medicas");
        $stats['total_licencias'] = $stmt->fetch()['total'] ?? 0;
        
        // Total días económicos
        $stmt = $db->query("SELECT COUNT(*) as total FROM dias_economicos");
        $stats['total_dias_economicos'] = $stmt->fetch()['total'] ?? 0;
        
        // Incidencias pendientes (unión de todas las fuentes)
        $stmt = $db->query("
            SELECT COUNT(*) as total FROM (
                SELECT id FROM asistencia WHERE tipo_asistencia = 'por_definir'
                UNION ALL
                SELECT id FROM comisiones WHERE estatus = 'pendiente'
                UNION ALL
                SELECT id FROM justificaciones WHERE estatus = 'pendiente'
                UNION ALL
                SELECT id FROM vacaciones WHERE estatus = 'pendiente'
                UNION ALL
                SELECT id FROM cuidados_maternos WHERE estatus = 'pendiente'
                UNION ALL
                SELECT id FROM dias_economicos WHERE estatus = 'pendiente'
                UNION ALL
                SELECT id FROM licencias_medicas WHERE estatus = 'pendiente'
            ) todas
        ");
        $stats['incidencias_pendientes'] = (int)($stmt->fetch()['total'] ?? 0);
        
        return $stats;
    }
    
    private function obtenerPorTipo($db) {
        $stmt = $db->query("
            SELECT tj.nombre, COUNT(r.id) as cantidad
            FROM tipos_justificacion tj
            LEFT JOIN retardos r ON r.tipo_justificacion_id = tj.id AND r.justificado = 1
            GROUP BY tj.id, tj.nombre
            ORDER BY cantidad DESC
        ");
        return $stmt->fetchAll();
    }
    
    private function obtenerEmpleadosProblematica($db) {
        $stmt = $db->query("
            SELECT e.id, e.nombre, e.apellido, e.rfc,
                   COUNT(r.id) as total_retardos,
                   SUM(CASE WHEN r.justificado = 0 THEN 1 ELSE 0 END) as retardos_sin_justificar
            FROM empleados e
            LEFT JOIN retardos r ON r.empleado_id = e.id
            GROUP BY e.id, e.nombre, e.apellido, e.rfc
            HAVING total_retardos > 0
            ORDER BY retardos_sin_justificar DESC, total_retardos DESC
            LIMIT 10
        ");
        return $stmt->fetchAll();
    }
    
    private function obtenerRecientes($db) {
        $stmt = $db->query("
            SELECT r.*, e.nombre, e.apellido, tj.nombre as tipo_nombre
            FROM retardos r
            JOIN empleados e ON r.empleado_id = e.id
            LEFT JOIN tipos_justificacion tj ON r.tipo_justificacion_id = tj.id
            ORDER BY r.fecha DESC
            LIMIT 10
        ");
        return $stmt->fetchAll();
    }
    
    private function renderStats($stats) {
        return '
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #9F2241, #691C32); color: white;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">' . $stats['total_retardos'] . '</h3>
                            <small class="text-muted">Total Retardos</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">' . $stats['retardos_justificados'] . '</h3>
                            <small class="text-muted">Justificados</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #ffc107, #d39e00); color: white;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">' . $stats['retardos_sin_justificar'] . '</h3>
                            <small class="text-muted">Sin Justificar</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">' . $stats['incidencias_pendientes'] . '</h3>
                            <small class="text-muted">Incidencias Pendientes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ';
    }
    
    private function renderPorTipo($porTipo) {
        $html = '<div class="card"><div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Justificaciones por Tipo</h5></div><div class="card-body"><ul class="list-group">';
        
        foreach ($porTipo as $tipo) {
            $html .= '<li class="list-group-item d-flex justify-content-between align-items-center">
                ' . htmlspecialchars($tipo['nombre']) . '
                <span class="badge bg-primary rounded-pill">' . $tipo['cantidad'] . '</span>
            </li>';
        }
        
        $html .= '</ul></div></div>';
        return $html;
    }
    
    private function renderRecientes($recientes) {
        $html = '<div class="card"><div class="card-header"><h5 class="mb-0"><i class="fas fa-history me-2"></i> Justificaciones Recientes</h5></div><div class="card-body"><ul class="list-group">';
        
        foreach ($recientes as $r) {
            $estado = $r['justificado'] ? '<span class="badge bg-success">Justificado</span>' : '<span class="badge bg-warning">Sin Justificar</span>';
            $html .= '<li class="list-group-item">
                <div class="d-flex justify-content-between">
                    <strong>' . htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) . '</strong>
                    <span>' . $estado . '</span>
                </div>
                <small class="text-muted">' . $r['fecha'] . ' - ' . htmlspecialchars($r['tipo_nombre'] ?? 'Sin tipo') . '</small>
            </li>';
        }
        
        $html .= '</ul></div></div>';
        return $html;
    }
    
    private function renderEmpleadosProblematica($empleados) {
        $html = '<div class="card"><div class="card-header bg-danger text-white"><h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i> Empleados con Más Retardos Sin Justificar</h5></div>';
        $html .= '<div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-dark"><tr><th>Empleado</th><th>RFC</th><th>Total Retardos</th><th>Sin Justificar</th><th>Acción</th></tr></thead><tbody>';
        
        foreach ($empleados as $emp) {
            $html .= '<tr>
                <td>' . htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) . '</td>
                <td>' . htmlspecialchars($emp['rfc'] ?? 'N/A') . '</td>
                <td><span class="badge bg-primary">' . $emp['total_retardos'] . '</span></td>
                <td><span class="badge bg-danger">' . $emp['retardos_sin_justificar'] . '</span></td>
                <td><button type="button" class="btn btn-sm btn-primary" onclick="verDetalleEmpleado(' . $emp['id'] . ')" style="background-color: #9F2241; border-color: #9F2241;"><i class="fas fa-eye me-1"></i>Ver</button></td>
            </tr>';
        }
        
        $html .= '</tbody></table></div></div>';
        return $html;
    }
    
    private function getBaseUrl() {
        return defined('BASE_URL') ? BASE_URL : '';
    }
}
