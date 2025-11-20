<?php
require_once 'models/Empleado.php';
require_once 'models/Asistencia.php';
require_once 'models/Retardo.php';
require_once 'models/Comision.php';
require_once 'models/Ausencia.php';
require_once 'models/Biometrico.php';

class DashboardController {
    private $empleadoModel;
    private $asistenciaModel;
    private $retardoModel;
    private $comisionModel;
    private $ausenciaModel;
    private $biometricoModel;

    public function __construct() {
        $this->empleadoModel = new Empleado();
        $this->asistenciaModel = new Asistencia();
        $this->retardoModel = new Retardo();
        $this->comisionModel = new Comision();
        $this->ausenciaModel = new Ausencia();
        $this->biometricoModel = new Biometrico();
    }

    public function index() {
        // Estadísticas generales
        $totalEmpleados = count($this->empleadoModel->getAll());
        $estadoDispositivos = $this->biometricoModel->getEstadoDispositivos();

        // Estadísticas del día actual
        $hoy = date('Y-m-d');
        $asistenciasHoy = $this->getAsistenciasHoy();
        $retardosHoy = $this->getRetardosHoy();
        $ausenciasHoy = $this->getAusenciasHoy();

        // Estadísticas del mes actual
        $mesActual = date('m');
        $anioActual = date('Y');
        $retardosMes = $this->retardoModel->getTotalRetardos(null, $mesActual, $anioActual);
        $comisionesMes = $this->comisionModel->getTotalComisiones(null, $mesActual, $anioActual);
        $ausenciasMes = $this->ausenciaModel->getTotalAusencias(null, $mesActual, $anioActual);

        // Comisiones vencidas
        $comisionesVencidas = $this->comisionModel->getVencidas(null);

        $content = '
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-12">
                    <h2><i class="fas fa-tachometer-alt"></i> Dashboard del Sistema Biométrico</h2>
                    <p class="text-muted">Vista general del estado del sistema</p>
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

            <!-- Estado de dispositivos -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-fingerprint"></i> Estado de Dispositivos Biométricos</h5>
                        </div>
                        <div class="card-body">
                            <div class="row" id="dispositivos-status">
                                ' . $this->renderEstadoDispositivos($estadoDispositivos) . '
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos y estadísticas -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar"></i> Estadísticas del Mes</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="estadisticasChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-exclamation-circle"></i> Alertas</h5>
                        </div>
                        <div class="card-body">
                            ' . $this->renderAlertas($comisionesVencidas, $retardosHoy) . '
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actividad reciente -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Actividad Reciente</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Hora</th>
                                            <th>Empleado</th>
                                            <th>Actividad</th>
                                            <th>Dispositivo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="actividad-reciente">
                                        ' . $this->renderActividadReciente($asistenciasHoy) . '
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                    data: [' . $this->getEstadisticasMes($retardosMes, $ausenciasMes, $comisionesMes) . '],
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
            fetch(\'/dashboard/estado-dispositivos\')
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

        include __DIR__ . '/../views/layout.php';
    }

    private function getAsistenciasHoy() {
        $hoy = date('Y-m-d');
        return $this->asistenciaModel->getByEmpleado(null, $hoy, $hoy);
    }

    private function getRetardosHoy() {
        $hoy = date('Y-m-d');
        return $this->retardoModel->getByEmpleado(null, $hoy, $hoy);
    }

    private function getAusenciasHoy() {
        $hoy = date('Y-m-d');
        return $this->ausenciaModel->getByEmpleado(null, $hoy, $hoy);
    }

    private function renderEstadoDispositivos($dispositivos) {
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

    private function renderAlertas($comisionesVencidas, $retardosHoy) {
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

    private function renderActividadReciente($asistencias) {
        $html = '';
        $empleados = $this->empleadoModel->getAll();
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

    private function getEstadisticasMes($retardosMes, $ausenciasMes, $comisionesMes) {
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

    public function getEstadoDispositivosAjax() {
        header('Content-Type: application/json');
        echo json_encode($this->biometricoModel->getEstadoDispositivos());
    }
}
?>
