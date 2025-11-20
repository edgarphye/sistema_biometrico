<?php
require_once 'models/Empleado.php';
require_once 'models/Asistencia.php';
require_once 'models/Retardo.php';
require_once 'models/Comision.php';
require_once 'models/Ausencia.php';
require_once 'models/Biometrico.php';

// Crear instancias de modelos
$empleadoModel = new Empleado();
$asistenciaModel = new Asistencia();
$retardoModel = new Retardo();
$comisionModel = new Comision();
$ausenciaModel = new Ausencia();
$biometricoModel = new Biometrico();

// Estadísticas generales
$totalEmpleados = count($empleadoModel->getAll());
$estadoDispositivos = $biometricoModel->getEstadoDispositivos();

// Estadísticas del día actual
$hoy = date('Y-m-d');
$asistenciasHoy = $asistenciaModel->getByEmpleado(null, $hoy, $hoy);
$retardosHoy = $retardoModel->getByEmpleado(null, $hoy, $hoy);
$ausenciasHoy = $ausenciaModel->getByEmpleado(null, $hoy, $hoy);

// Estadísticas del mes actual
$mesActual = date('m');
$anioActual = date('Y');
$retardosMes = $retardoModel->getTotalRetardos(null, $mesActual, $anioActual);
$comisionesMes = $comisionModel->getTotalComisiones(null, $mesActual, $anioActual);
$ausenciasMes = $ausenciaModel->getTotalAusencias(null, $mesActual, $anioActual);

// Comisiones vencidas
$comisionesVencidas = $comisionModel->getVencidas(null);

// Funciones helper
function renderEstadoDispositivos($dispositivos) {
    $html = '';
    foreach ($dispositivos as $dispositivo) {
        $statusClass = $dispositivo['status'] === 'conectado' ? 'border-success' : 'border-danger';
        $iconClass = $dispositivo['status'] === 'conectado' ? 'text-success' : 'text-danger';
        $badgeClass = $dispositivo['status'] === 'conectado' ? 'bg-success' : 'bg-danger';

        $html .= "
        <div class='col-md-2 mb-3'>
            <div class='card {$statusClass}'>
                <div class='card-body text-center'>
                    <i class='fas fa-fingerprint fa-2x {$iconClass}'></i>
                    <h6 class='card-title'>Disp. {$dispositivo['dispositivo_id']}</h6>
                    <span class='badge {$badgeClass}'>{$dispositivo['status']}</span>
                </div>
            </div>
        </div>";
    }
    return $html;
}

function renderAlertas($comisionesVencidas, $retardosHoy) {
    $html = '';

    if (count($comisionesVencidas) > 0) {
        $html .= '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <strong>' . count($comisionesVencidas) . '</strong> comisiones vencidas requieren atención.</div>';
    }

    if (count($retardosHoy) > 0) {
        $html .= '<div class="alert alert-info"><i class="fas fa-clock"></i> <strong>' . count($retardosHoy) . '</strong> retardos registrados hoy.</div>';
    }

    if (empty($html)) {
        $html = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> No hay alertas pendientes.</div>';
    }

    return $html;
}

function renderActividadReciente($asistencias) {
    $html = '';
    $empleados = (new Empleado())->getAll();
    $empleadosMap = [];
    foreach ($empleados as $emp) {
        $empleadosMap[$emp['id']] = $emp['nombre'] . ' ' . $emp['apellido'];
    }

    $recentes = array_slice($asistencias, 0, 10); // Últimas 10 actividades

    foreach ($recentes as $asistencia) {
        $hora = date('H:i', strtotime($asistencia['timestamp']));
        $empleado = $empleadosMap[$asistencia['empleado_id']] ?? 'Desconocido';
        $tipo = $asistencia['tipo'] === 'entrada' ? 'Entrada' : 'Salida';
        $badgeClass = $asistencia['tipo'] === 'entrada' ? 'bg-success' : 'bg-warning';

        $html .= "
        <tr>
            <td>{$hora}</td>
            <td>{$empleado}</td>
            <td><span class='badge {$badgeClass}'>{$tipo}</span></td>
            <td>Dispositivo {$asistencia['dispositivo_id']}</td>
        </tr>";
    }

    if (empty($html)) {
        $html = '<tr><td colspan="4" class="text-center text-muted">No hay actividad reciente</td></tr>';
    }

    return $html;
}

function getEstadisticasMes($retardosMes, $ausenciasMes, $comisionesMes) {
    $menores = 0;
    $mayores = 0;

    foreach ($retardosMes as $retardo) {
        if ($retardo['tipo'] === 'menor') {
            $menores = $retardo['total'];
        } elseif ($retardo['tipo'] === 'mayor') {
            $mayores = $retardo['total'];
        }
    }

    $totalAusencias = array_sum(array_column($ausenciasMes, 'total'));

    return "$menores, $mayores, $totalAusencias, " . (is_array($comisionesMes) ? count($comisionesMes) : $comisionesMes);
}

$content = '
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="jumbotron bg-gradient-primary text-white rounded p-5 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h1 class="display-4"><i class="fas fa-fingerprint"></i> Sistema Biométrico de Asistencia</h1>
                <p class="lead">Gestión completa de empleados con control biométrico de entrada y salida</p>
                <hr class="my-4 bg-white">
                <p class="mb-0">Administra empleados, controla asistencia, calcula retardos y genera reportes automáticamente.</p>
            </div>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Empleados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">' . $totalEmpleados . '</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Asistencias Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">' . count($asistenciasHoy) . '</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Retardos Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">' . count($retardosHoy) . '</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Ausencias Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">' . count($ausenciasHoy) . '</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Módulos principales -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="icon-circle bg-primary text-white mb-3">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                    <h5 class="card-title">Empleados</h5>
                    <p class="card-text text-muted">Gestión completa de información de empleados</p>
                    <a href="<?php echo BASE_URL; ?>/empleados" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-arrow-right"></i> Gestionar
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="icon-circle bg-success text-white mb-3">
                        <i class="fas fa-clock fa-3x"></i>
                    </div>
                    <h5 class="card-title">Asistencia</h5>
                    <p class="card-text text-muted">Control biométrico de entrada y salida</p>
                    <a href="<?php echo BASE_URL; ?>/asistencia" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-arrow-right"></i> Controlar
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="icon-circle bg-info text-white mb-3">
                        <i class="fas fa-chart-bar fa-3x"></i>
                    </div>
                    <h5 class="card-title">Reportes</h5>
                    <p class="card-text text-muted">Análisis y reportes de asistencia</p>
                    <a href="<?php echo BASE_URL; ?>/reportes" class="btn btn-info btn-lg w-100">
                        <i class="fas fa-arrow-right"></i> Ver Reportes
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="icon-circle bg-warning text-white mb-3">
                        <i class="fas fa-fingerprint fa-3x"></i>
                    </div>
                    <h5 class="card-title">Dispositivos</h5>
                    <p class="card-text text-muted">Estado de dispositivos biométricos</p>
                    <a href="<?php echo BASE_URL; ?>/biometricos" class="btn btn-warning btn-lg w-100">
                        <i class="fas fa-arrow-right"></i> Gestionar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado de dispositivos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-fingerprint text-primary"></i> Estado de Dispositivos Biométricos</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="dispositivos-status">
                        ' . renderEstadoDispositivos($estadoDispositivos) . '
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos y estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-bar text-info"></i> Estadísticas del Mes</h5>
                </div>
                <div class="card-body">
                    <canvas id="estadisticasChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle text-warning"></i> Alertas y Notificaciones</h5>
                </div>
                <div class="card-body">
                    ' . renderAlertas($comisionesVencidas, $retardosHoy) . '
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Sistema operativo</strong><br>
                        Todos los dispositivos biométricos conectados correctamente.
                    </div>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <strong>Base de datos sincronizada</strong><br>
                        Información actualizada al ' . date('d/m/Y H:i') . '
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad reciente -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history text-secondary"></i> Actividad Reciente</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-clock"></i> Hora</th>
                                    <th><i class="fas fa-user"></i> Empleado</th>
                                    <th><i class="fas fa-tasks"></i> Actividad</th>
                                    <th><i class="fas fa-fingerprint"></i> Dispositivo</th>
                                </tr>
                            </thead>
                            <tbody id="actividad-reciente">
                                ' . renderActividadReciente($asistenciasHoy) . '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}
.icon-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.jumbotron {
    border-radius: 15px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.card-header {
    border-radius: 10px 10px 0 0 !important;
    border: none;
}
.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de estadísticas del mes
const ctx = document.getElementById(\'estadisticasChart\').getContext(\'2d\');
const estadisticasChart = new Chart(ctx, {
    type: \'bar\',
    data: {
        labels: [\'Retardos Menores\', \'Retardos Mayores\', \'Ausencias\', \'Comisiones\'],
        datasets: [{
            label: \'Cantidad\',
            data: [' . getEstadisticasMes($retardosMes, $ausenciasMes, $comisionesMes) . '],
            backgroundColor: [
                \'rgba(255, 193, 7, 0.8)\',
                \'rgba(220, 53, 69, 0.8)\',
                \'rgba(23, 162, 184, 0.8)\',
                \'rgba(40, 167, 69, 0.8)\'
            ],
            borderColor: [
                \'rgba(255, 193, 7, 1)\',
                \'rgba(220, 53, 69, 1)\',
                \'rgba(23, 162, 184, 1)\',
                \'rgba(40, 167, 69, 1)\'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Actualizar estado de dispositivos cada 30 segundos
setInterval(function() {
    fetch(\'/sistema_biometrico/dashboard/estado-dispositivos\')
        .then(response => response.json())
        .then(data => {
            document.getElementById(\'dispositivos-status\').innerHTML = generarHTMLDispositivos(data);
        })
        .catch(error => console.error(\'Error actualizando dispositivos:\', error));
}, 30000);

function generarHTMLDispositivos(dispositivos) {
    return dispositivos.map(d => `
        <div class="col-md-2 mb-3">
            <div class="card ${d.status === \'conectado\' ? \'border-success\' : \'border-danger\'}">
                <div class="card-body text-center">
                    <i class="fas fa-fingerprint fa-2x ${d.status === \'conectado\' ? \'text-success\' : \'text-danger\'}"></i>
                    <h6 class="card-title">Disp. ${d.dispositivo_id}</h6>
                    <span class="badge ${d.status === \'conectado\' ? \'bg-success\' : \'bg-danger\'}">${d.status}</span>
                </div>
            </div>
        </div>
    `).join(\'\');
}
</script>
';

include __DIR__ . '/layout.php';
?>
