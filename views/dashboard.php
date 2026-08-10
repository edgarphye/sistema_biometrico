<?php
// Vista del Dashboard con Gráficas Profesionales
ob_start();
$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
?>
<style>
    .dashboard-header {
        background: linear-gradient(135deg, #235B4E 0%, #1a3d35 100%);
        color: white;
        padding: 30px 35px;
        border-radius: 16px;
        margin-bottom: 30px;
        box-shadow: 0 10px 40px rgba(35, 91, 78, 0.25);
    }
    
    .dashboard-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
    }
    
    .dashboard-header p {
        opacity: 0.9;
        margin: 5px 0 0 0;
    }
    
    .stat-card {
        background: white;
        border-radius: 14px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: none;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    }
    
    .stat-card .stat-icon {
        width: 55px;
        height: 55px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }
    
    .stat-card .stat-value {
        font-size: 2.2rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 5px;
    }
    
    .stat-card .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .chart-card {
        background: white;
        border-radius: 14px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: none;
        margin-bottom: 25px;
    }
    
    .chart-card .card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e0e0e0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .chart-card .card-title i {
        color: #9F2241;
        font-size: 1.1rem;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    .chart-container-sm {
        position: relative;
        height: 220px;
    }
    
    .section-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #1a1a1a;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .section-title::before {
        content: '';
        width: 5px;
        height: 30px;
        background: linear-gradient(180deg, #9F2241, #BC955C);
        border-radius: 3px;
    }
    
    .table-card {
        background: white;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    
    .table-card h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #2D3436;
        margin-bottom: 15px;
    }
    
    .table-card .table {
        margin-bottom: 0;
        font-size: 0.9rem;
    }
    
    .table-card .table th {
        background: #f8f9fa;
        border-top: none;
        font-weight: 600;
        color: #495057;
    }
    
    .badge-custom {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .gradient-primary { background: linear-gradient(135deg, #9F2241, #691C32); color: white; }
    .gradient-success { background: linear-gradient(135deg, #235B4E, #1a3d35); color: white; }
    .gradient-warning { background: linear-gradient(135deg, #BC955C, #a67c4e); color: white; }
    .gradient-danger { background: linear-gradient(135deg, #E65100, #bf360c); color: white; }
    .gradient-purple { background: linear-gradient(135deg, #7B1FA2, #4a148c); color: white; }
    .gradient-info { background: linear-gradient(135deg, #00838F, #006064); color: white; }
    .gradient-red { background: linear-gradient(135deg, #C62828, #b71c1c); color: white; }
    .gradient-blue { background: linear-gradient(135deg, #1565C0, #0d47a1); color: white; }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .stat-card .stat-value {
            font-size: 1.8rem;
        }
        .chart-container {
            height: 250px;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 20px;
        }
        .dashboard-header h1 {
            font-size: 1.5rem;
        }
        .stat-card {
            padding: 20px;
        }
        .stat-card .stat-value {
            font-size: 1.6rem;
        }
        .stat-card .stat-icon {
            width: 45px;
            height: 45px;
            font-size: 20px;
        }
        .chart-card {
            padding: 15px;
        }
        .chart-container {
            height: 200px;
        }
        .section-title {
            font-size: 1.3rem;
        }
    }
    
    @media (max-width: 576px) {
        .dashboard-header {
            padding: 15px;
            border-radius: 12px;
        }
        .dashboard-header h1 {
            font-size: 1.3rem;
        }
        .dashboard-header p {
            font-size: 0.85rem;
        }
        .stat-card .stat-label {
            font-size: 0.75rem;
        }
        .table-card .table {
            font-size: 0.8rem;
        }
    }
</style>

<div class="container-fluid py-4">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <h1><i class="fas fa-chart-line me-3"></i>Dashboard de Recursos Humanos</h1>
        <p>Estadísticas y métricas del sistema de control de asistencia - <?php echo $meses[(int)date('m')] . ' ' . date('Y'); ?></p>
    </div>

    <!-- Tarjetas de Estadísticas Principales -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon gradient-primary"><i class="fas fa-users"></i></div>
                <div class="stat-value" style="color: #9F2241;"><?php echo $stats['totalEmpleados']; ?></div>
                <div class="stat-label">Total Empleados</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon gradient-success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value" style="color: #235B4E;"><?php echo $stats['asistenciasHoy']; ?></div>
                <div class="stat-label">Asistencias Hoy</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon gradient-warning"><i class="fas fa-clock"></i></div>
                <div class="stat-value" style="color: #BC955C;"><?php echo $stats['totalRetardosMes']; ?></div>
                <div class="stat-label">Retardos del Mes</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon gradient-danger"><i class="fas fa-calendar-minus"></i></div>
                <div class="stat-value" style="color: #E65100;"><?php echo $stats['totalAusenciasMes']; ?></div>
                <div class="stat-label">Ausencias del Mes</div>
            </div>
        </div>
    </div>

    <!-- Segunda fila de estadísticas -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon gradient-purple"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="stat-value" style="color: #7B1FA2;">$<?php echo number_format($stats['comisionesMonto'], 0); ?></div>
                <div class="stat-label">Comisiones Aprobadas</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon gradient-info"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-value" style="color: #00838F;"><?php echo $stats['diasEconomicosTotal']; ?></div>
                <div class="stat-label">Días Económicos</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon gradient-red"><i class="fas fa-user-md"></i></div>
                <div class="stat-value" style="color: #C62828;"><?php echo $stats['licenciasMedicasTotal']; ?></div>
                <div class="stat-label">Licencias Médicas</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon gradient-blue"><i class="fas fa-umbrella-beach"></i></div>
                <div class="stat-value" style="color: #1565C0;"><?php echo $stats['vacacionesDias']; ?></div>
                <div class="stat-label">Días de Vacaciones</div>
            </div>
        </div>
    </div>

    <!-- Sección: Distribución de Empleados -->
    <h2 class="section-title mb-4">Distribución de Empleados</h2>
    <div class="row g-4 mb-4">
        <div class="col-lg-3">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-building"></i> Por Área</h3>
                <div class="chart-container-sm">
                    <canvas id="chartEmpleadosArea"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-user-check"></i> Por Estatus</h3>
                <div class="chart-container-sm">
                    <canvas id="chartEmpleadosEstatus"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-venus-mars"></i> Por Género</h3>
                <div class="chart-container-sm">
                    <canvas id="chartEmpleadosGenero"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-clock"></i> Por Turno</h3>
                <div class="chart-container-sm">
                    <canvas id="chartEmpleadosTurno"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Antigüedad y Tipo -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-history"></i> Antigüedad de Empleados</h3>
                <div class="chart-container">
                    <canvas id="chartAntiguedad"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-briefcase"></i> Por Tipo de Empleado</h3>
                <div class="chart-container">
                    <canvas id="chartEmpleadosTipo"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección: Incidencias del Mes -->
    <h2 class="section-title mb-4">Incidencias del Mes</h2>
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Retardos por Tipo</h3>
                <div class="chart-container">
                    <canvas id="chartRetardosTipo"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-tasks"></i> Retardos por Validación</h3>
                <div class="chart-container">
                    <canvas id="chartRetardosEstatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Retardos por Día -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-calendar-week"></i> Retardos por Día de la Semana</h3>
                <div class="chart-container">
                    <canvas id="chartRetardosDiaSemana"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección: Permisos y Licencias -->
    <h2 class="section-title mb-4">Permisos y Licencias</h2>
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Comisiones</h3>
                <div class="chart-container-sm">
                    <canvas id="chartComisionesEstatus"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-calendar-day"></i> Días Económicos</h3>
                <div class="chart-container-sm">
                    <canvas id="chartDiasEconomicosEstatus"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-user-md"></i> Licencias Médicas</h3>
                <div class="chart-container-sm">
                    <canvas id="chartLicenciasEstatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Vacaciones y Sanciones -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-umbrella-beach"></i> Vacaciones</h3>
                <div class="chart-container-sm">
                    <canvas id="chartVacacionesEstatus"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-gavel"></i> Sanciones por Tipo</h3>
                <div class="chart-container-sm">
                    <canvas id="chartSancionesTipo"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-ban"></i> Sanciones por Estatus</h3>
                <div class="chart-container-sm">
                    <canvas id="chartSancionesEstatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Ausencias por Tipo -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-calendar-minus"></i> Ausencias por Tipo</h3>
                <div class="chart-container">
                    <canvas id="chartAusenciasTipo"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-fingerprint"></i> Dispositivos Biométricos</h3>
                <div class="chart-container">
                    <canvas id="chartDispositivos"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tendencias -->
    <h2 class="section-title mb-4">Tendencias Históricas</h2>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-chart-area"></i> Asistencia Mensual (Últimos 12 Meses)</h3>
                <div class="chart-container">
                    <canvas id="chartAsistenciasTendencia"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Empleados con Retardos -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="table-card">
                <h4><i class="fas fa-exclamation-circle text-warning me-2"></i>Top 5 Empleados con Más Retardos</h4>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Empleado</th>
                            <th class="text-center">Retardos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stats['topRetardadores'])): ?>
                            <?php foreach ($stats['topRetardadores'] as $i => $emp): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark"><?= $emp['total'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-muted text-center">Sin datos disponibles</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <h3 class="card-title"><i class="fas fa-percentage"></i> Distribución de Incidencias</h3>
                <div class="chart-container">
                    <canvas id="chartIncidenciasTotal"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colors = {
        primary: '#9F2241', secondary: '#235B4E', accent: '#BC955C',
        danger: '#E65100', purple: '#7B1FA2', teal: '#00838F',
        red: '#C62828', blue: '#1565C0', orange: '#FF6F00', green: '#4CAF50'
    };
    
    const chartColors = [colors.primary, colors.secondary, colors.accent, colors.danger, 
                       colors.purple, colors.teal, colors.red, colors.blue, colors.orange, colors.green];
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 11 } } }
        }
    };
    
    // --- EMPLEADOS ---
    const areasData = <?php echo json_encode(array_column($stats['empleadosPorArea'], 'total')); ?>;
    const areasLabels = <?php echo json_encode(array_column($stats['empleadosPorArea'], 'area')); ?>;
    new Chart(document.getElementById('chartEmpleadosArea'), {
        type: 'doughnut',
        data: {
            labels: areasLabels.length ? areasLabels : ['Sin datos'],
            datasets: [{ data: areasData.length ? areasData : [1], backgroundColor: chartColors, borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    const estatusData = <?php echo json_encode(array_column($stats['empleadosPorEstatus'], 'total')); ?>;
    const estatusLabels = <?php echo json_encode(array_column($stats['empleadosPorEstatus'], 'estatus')); ?>;
    new Chart(document.getElementById('chartEmpleadosEstatus'), {
        type: 'pie',
        data: {
            labels: estatusLabels.length ? estatusLabels : ['Sin datos'],
            datasets: [{ data: estatusData.length ? estatusData : [1], backgroundColor: [colors.secondary, colors.primary, '#6c757d'], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    const generoData = <?php echo json_encode(array_column($stats['empleadosPorGenero'], 'total')); ?>;
    const generoLabels = <?php echo json_encode(array_column($stats['empleadosPorGenero'], 'genero')); ?>;
    new Chart(document.getElementById('chartEmpleadosGenero'), {
        type: 'doughnut',
        data: {
            labels: generoLabels.length ? generoLabels : ['Sin datos'],
            datasets: [{ data: generoData.length ? generoData : [1], backgroundColor: [colors.purple, colors.teal, colors.accent], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    const turnoData = <?php echo json_encode(array_column($stats['empleadosPorTurno'], 'total')); ?>;
    const turnoLabels = <?php echo json_encode(array_column($stats['empleadosPorTurno'], 'turno')); ?>;
    new Chart(document.getElementById('chartEmpleadosTurno'), {
        type: 'doughnut',
        data: {
            labels: turnoLabels.length ? turnoLabels : ['Sin datos'],
            datasets: [{ data: turnoData.length ? turnoData : [1], backgroundColor: chartColors, borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    const antiguedadData = <?php echo json_encode(array_column($stats['antiguedadEmpleados'], 'total')); ?>;
    const antiguedadLabels = <?php echo json_encode(array_column($stats['antiguedadEmpleados'], 'rango')); ?>;
    new Chart(document.getElementById('chartAntiguedad'), {
        type: 'bar',
        data: {
            labels: antiguedadLabels.length ? antiguedadLabels : ['Sin datos'],
            datasets: [{ label: 'Empleados', data: antiguedadData.length ? antiguedadData : [0], backgroundColor: colors.primary, borderRadius: 6 }]
        },
        options: { ...defaultOptions, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    
    const tipoData = <?php echo json_encode(array_column($stats['empleadosPorTipo'], 'total')); ?>;
    const tipoLabels = <?php echo json_encode(array_column($stats['empleadosPorTipo'], 'tipo_empleado')); ?>;
    new Chart(document.getElementById('chartEmpleadosTipo'), {
        type: 'doughnut',
        data: {
            labels: tipoLabels.length ? tipoLabels : ['Sin datos'],
            datasets: [{ data: tipoData.length ? tipoData : [1], backgroundColor: chartColors, borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    // --- INCIDENCIAS ---
    const retardoTipoData = <?php echo json_encode(array_column($stats['retardosPorTipo'], 'total')); ?>;
    const retardoTipoLabels = <?php echo json_encode(array_column($stats['retardosPorTipo'], 'tipo')); ?>;
    new Chart(document.getElementById('chartRetardosTipo'), {
        type: 'bar',
        data: {
            labels: retardoTipoLabels.length ? retardoTipoLabels : ['Sin datos'],
            datasets: [{ label: 'Cantidad', data: retardoTipoData.length ? retardoTipoData : [0], backgroundColor: colors.accent, borderRadius: 6 }]
        },
        options: { ...defaultOptions, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
    });
    
    const retardoEstatusData = <?php echo json_encode(array_column($stats['retardosPorEstatus'], 'total')); ?>;
    const retardoEstatusLabels = <?php echo json_encode(array_column($stats['retardosPorEstatus'], 'estatus')); ?>;
    new Chart(document.getElementById('chartRetardosEstatus'), {
        type: 'doughnut',
        data: {
            labels: retardoEstatusLabels.length ? retardoEstatusLabels : ['Sin datos'],
            datasets: [{ data: retardoEstatusData.length ? retardoEstatusData : [1], backgroundColor: [colors.accent, colors.secondary, colors.primary, '#6c757d'], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    const diaSemanaData = <?php echo json_encode(array_column($stats['retardosPorDiaSemana'], 'total')); ?>;
    const diaSemanaLabels = <?php echo json_encode(array_column($stats['retardosPorDiaSemana'], 'dia_semana')); ?>;
    new Chart(document.getElementById('chartRetardosDiaSemana'), {
        type: 'line',
        data: {
            labels: diaSemanaLabels.length ? diaSemanaLabels : ['Sin datos'],
            datasets: [{
                label: 'Retardos',
                data: diaSemanaData.length ? diaSemanaData : [0],
                borderColor: colors.danger,
                backgroundColor: 'rgba(230, 81, 0, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.danger,
                pointRadius: 5
            }]
        },
        options: { ...defaultOptions, scales: { y: { beginAtZero: true } } }
    });
    
    // --- PERMISOS Y LICENCIAS ---
    const comisionData = <?php echo json_encode(array_column($stats['comisionesPorEstatus'], 'total')); ?>;
    const comisionLabels = <?php echo json_encode(array_column($stats['comisionesPorEstatus'], 'estatus')); ?>;
    new Chart(document.getElementById('chartComisionesEstatus'), {
        type: 'doughnut',
        data: {
            labels: comisionLabels.length ? comisionLabels : ['Sin datos'],
            datasets: [{ data: comisionData.length ? comisionData : [1], backgroundColor: [colors.purple, colors.accent, colors.primary, '#6c757d'], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    const diasEcoData = <?php echo json_encode(array_column($stats['diasEconomicosPorEstatus'], 'total')); ?>;
    const diasEcoLabels = <?php echo json_encode(array_column($stats['diasEconomicosPorEstatus'], 'estatus')); ?>;
    new Chart(document.getElementById('chartDiasEconomicosEstatus'), {
        type: 'doughnut',
        data: {
            labels: diasEcoLabels.length ? diasEcoLabels : ['Sin datos'],
            datasets: [{ data: diasEcoData.length ? diasEcoData : [1], backgroundColor: [colors.teal, colors.accent, colors.primary, '#6c757d'], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    const licenciaData = <?php echo json_encode(array_column($stats['licenciasPorEstatus'], 'total')); ?>;
    const licenciaLabels = <?php echo json_encode(array_column($stats['licenciasPorEstatus'], 'estatus')); ?>;
    new Chart(document.getElementById('chartLicenciasEstatus'), {
        type: 'doughnut',
        data: {
            labels: licenciaLabels.length ? licenciaLabels : ['Sin datos'],
            datasets: [{ data: licenciaData.length ? licenciaData : [1], backgroundColor: [colors.red, colors.accent, colors.primary, '#6c757d'], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    // --- VACACIONES Y SANCIONES ---
    const vacData = <?php echo json_encode(array_column($stats['vacacionesPorEstatus'], 'total')); ?>;
    const vacLabels = <?php echo json_encode(array_column($stats['vacacionesPorEstatus'], 'estatus')); ?>;
    new Chart(document.getElementById('chartVacacionesEstatus'), {
        type: 'doughnut',
        data: {
            labels: vacLabels.length ? vacLabels : ['Sin datos'],
            datasets: [{ data: vacData.length ? vacData : [1], backgroundColor: [colors.blue, colors.accent, colors.primary, '#6c757d'], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    const sancTipoData = <?php echo json_encode(array_column($stats['sancionesPorTipo'], 'total')); ?>;
    const sancTipoLabels = <?php echo json_encode(array_column($stats['sancionesPorTipo'], 'tipo')); ?>;
    new Chart(document.getElementById('chartSancionesTipo'), {
        type: 'bar',
        data: {
            labels: sancTipoLabels.length ? sancTipoLabels : ['Sin datos'],
            datasets: [{ label: 'Sanciones', data: sancTipoData.length ? sancTipoData : [0], backgroundColor: colors.red, borderRadius: 6 }]
        },
        options: { ...defaultOptions, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    
    const sancEstatusData = <?php echo json_encode(array_column($stats['sancionesPorEstatus'], 'total')); ?>;
    const sancEstatusLabels = <?php echo json_encode(array_column($stats['sancionesPorEstatus'], 'estatus')); ?>;
    new Chart(document.getElementById('chartSancionesEstatus'), {
        type: 'doughnut',
        data: {
            labels: sancEstatusLabels.length ? sancEstatusLabels : ['Sin datos'],
            datasets: [{ data: sancEstatusData.length ? sancEstatusData : [1], backgroundColor: [colors.red, colors.green, colors.accent], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    // --- AUSENCIAS Y DISPOSITIVOS ---
    const ausenciaData = <?php echo json_encode(array_column($stats['ausenciasPorTipo'], 'total')); ?>;
    const ausenciaLabels = <?php echo json_encode(array_column($stats['ausenciasPorTipo'], 'tipo')); ?>;
    new Chart(document.getElementById('chartAusenciasTipo'), {
        type: 'bar',
        data: {
            labels: ausenciaLabels.length ? ausenciaLabels : ['Sin datos'],
            datasets: [{ label: 'Cantidad', data: ausenciaData.length ? ausenciaData : [0], backgroundColor: colors.danger, borderRadius: 6 }]
        },
        options: { ...defaultOptions, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    
    const dispData = <?php echo json_encode(array_column($stats['dispositivosEstado'], 'total')); ?>;
    const dispLabels = <?php echo json_encode(array_column($stats['dispositivosEstado'], 'estatus')); ?>;
    new Chart(document.getElementById('chartDispositivos'), {
        type: 'doughnut',
        data: {
            labels: dispLabels.length ? dispLabels : ['Sin datos'],
            datasets: [{ data: dispData.length ? dispData : [1], backgroundColor: [colors.green, colors.red, colors.accent], borderWidth: 0 }]
        },
        options: defaultOptions
    });
    
    // --- TENDENCIAS ---
    const tendenciaData = <?php echo json_encode(array_column($stats['asistenciasPorMes'], 'total')); ?>;
    const tendenciaLabels = <?php echo json_encode(array_column($stats['asistenciasPorMes'], 'mes')); ?>;
    new Chart(document.getElementById('chartAsistenciasTendencia'), {
        type: 'line',
        data: {
            labels: tendenciaLabels.length ? tendenciaLabels : ['Sin datos'],
            datasets: [{
                label: 'Total Asistencias',
                data: tendenciaData.length ? tendenciaData : [0],
                borderColor: colors.secondary,
                backgroundColor: 'rgba(35, 91, 78, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.secondary,
                pointRadius: 4
            }]
        },
        options: { ...defaultOptions, scales: { y: { beginAtZero: true } } }
    });
    
    // --- DISTRIBUCIÓN DE INCIDENCIAS ---
    new Chart(document.getElementById('chartIncidenciasTotal'), {
        type: 'doughnut',
        data: {
            labels: ['Retardos', 'Ausencias', 'Comisiones', 'Días Económicos', 'Licencias', 'Vacaciones'],
            datasets: [{
                data: [
                    <?php echo $stats['totalRetardosMes']; ?>,
                    <?php echo $stats['totalAusenciasMes']; ?>,
                    <?php echo count($stats['comisionesPorEstatus']); ?>,
                    <?php echo $stats['diasEconomicosTotal']; ?>,
                    <?php echo $stats['licenciasMedicasTotal']; ?>,
                    <?php echo $stats['vacacionesDias']; ?>
                ],
                backgroundColor: [colors.accent, colors.danger, colors.purple, colors.teal, colors.red, colors.blue],
                borderWidth: 0
            }]
        },
        options: defaultOptions
    });
});
</script>
<?php
$content = ob_get_clean();
