<?php
require_once 'models/Empleado.php';
require_once 'models/Asistencia.php';
require_once 'models/Retardo.php';
require_once 'models/Comision.php';
require_once 'models/Ausencia.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Database.php';

class ReportesController extends BaseController {
    private $empleadoModel;
    private $asistenciaModel;
    private $retardoModel;
    private $comisionModel;
    private $ausenciaModel;

public function __construct($skipAuth = false) {
        parent::__construct();
        $this->empleadoModel = new Empleado();
        $this->asistenciaModel = new Asistencia();
        $this->retardoModel = new Retardo();
        $this->comisionModel = new Comision();
        $this->ausenciaModel = new Ausencia();
    }

    public function index() {
        $empleados = $this->empleadoModel->getAll();
        $content = '
        <div class="container mt-4">
            <h2>Reportes del Sistema Biométrico</h2>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Generar Reporte</h5>
                        </div>
                        <div class="card-body">
                            <form id="reporteForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                                        <select class="form-select" id="tipo_reporte" name="tipo_reporte" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="retardos">Retardos</option>
                                            <option value="comisiones">Comisiones</option>
                                            <option value="ausencias">Ausencias</option>
                                            <option value="asistencia">Asistencia General</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="empleado_id" class="form-label">Empleado</label>
                                        <select class="form-select" id="empleado_id" name="empleado_id">
                                            <option value="">Todos los empleados</option>
                                            ' . implode('', array_map(function($emp) {
                                                return '<option value="' . $emp['id'] . '">' . htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) . '</option>';
                                            }, $empleados)) . '
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary" onclick="generarReporte()">
                                            <i class="fas fa-chart-bar"></i> Generar Reporte
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="exportarExcel()">
                                            <i class="fas fa-file-excel"></i> Exportar a Excel
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Resultados del Reporte</h5>
                        </div>
                        <div class="card-body">
                            <div id="reporte-resultados">
                                <p class="text-muted">Seleccione los filtros y genere el reporte para ver los resultados.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function generarReporte() {
            const formData = new FormData(document.getElementById(\'reporteForm\'));
            const params = new URLSearchParams(formData);

            fetch(\'/reportes/generar?\' + params.toString())
                .then(response => response.json())
                .then(data => {
                    mostrarResultados(data);
                })
                .catch(error => {
                    console.error(\'Error:\', error);
                    document.getElementById(\'reporte-resultados\').innerHTML =
                        \'<div class="alert alert-danger">Error al generar el reporte.</div>\';
                });
        }

        function mostrarResultados(data) {
            let html = \'\';

            if (data.tipo === \'retardos\') {
                html = generarTablaRetardos(data.datos);
            } else if (data.tipo === \'comisiones\') {
                html = generarTablaComisiones(data.datos);
            } else if (data.tipo === \'ausencias\') {
                html = generarTablaAusencias(data.datos);
            } else if (data.tipo === \'asistencia\') {
                html = generarTablaAsistencia(data.datos);
            }

            document.getElementById(\'reporte-resultados\').innerHTML = html;
        }

        function generarTablaRetardos(datos) {
            let html = \'<table class="table table-striped"><thead><tr>\';
            html += \'<th>Empleado</th><th>Fecha</th><th>Minutos de Retardo</th><th>Tipo</th><th>Justificado</th>\';
            html += \'</tr></thead><tbody>\';

            datos.forEach(item => {
                html += `<tr>
                    <td>${item.empleado}</td>
                    <td>${item.fecha}</td>
                    <td>${item.minutos_retardo}</td>
                    <td><span class="badge bg-${item.tipo === \'menor\' ? \'warning\' : \'danger\'}">${item.tipo}</span></td>
                    <td>${item.justificado ? \'<i class="fas fa-check text-success"></i>\' : \'<i class="fas fa-times text-danger"></i>\'}</td>
                </tr>`;
            });

            html += \'</tbody></table>\';
            return html;
        }

        function generarTablaComisiones(datos) {
            let html = \'<table class="table table-striped"><thead><tr>\';
            html += \'<th>Empleado</th><th>Descripción</th><th>Monto</th><th>Fecha Asignación</th><th>Fecha Vencimiento</th><th>Estado</th>\';
            html += \'</tr></thead><tbody>\';

            datos.forEach(item => {
                const estado = item.justificada ? \'Justificada\' : (new Date(item.fecha_vencimiento) < new Date() ? \'Vencida\' : \'Pendiente\');
                const badgeClass = item.justificada ? \'success\' : (new Date(item.fecha_vencimiento) < new Date() ? \'danger\' : \'warning\');

                html += `<tr>
                    <td>${item.empleado}</td>
                    <td>${item.descripcion}</td>
                    <td>$${item.monto}</td>
                    <td>${item.fecha_asignacion}</td>
                    <td>${item.fecha_vencimiento || \'N/A\'}</td>
                    <td><span class="badge bg-${badgeClass}">${estado}</span></td>
                </tr>`;
            });

            html += \'</tbody></table>\';
            return html;
        }

        function generarTablaAusencias(datos) {
            let html = \'<table class="table table-striped"><thead><tr>\';
            html += \'<th>Empleado</th><th>Tipo</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Justificada</th>\';
            html += \'</tr></thead><tbody>\';

            datos.forEach(item => {
                html += `<tr>
                    <td>${item.empleado}</td>
                    <td>${item.tipo}</td>
                    <td>${item.fecha_inicio}</td>
                    <td>${item.fecha_fin}</td>
                    <td>${item.justificada ? \'<i class="fas fa-check text-success"></i>\' : \'<i class="fas fa-times text-danger"></i>\'}</td>
                </tr>`;
            });

            html += \'</tbody></table>\';
            return html;
        }

        function generarTablaAsistencia(datos) {
            let html = \'<table class="table table-striped"><thead><tr>\';
            html += \'<th>Empleado</th><th>Tipo</th><th>Fecha/Hora</th><th>Dispositivo</th>\';
            html += \'</tr></thead><tbody>\';

            datos.forEach(item => {
                html += `<tr>
                    <td>${item.empleado}</td>
                    <td><span class="badge bg-${item.tipo === \'entrada\' ? \'success\' : \'warning\'}">${item.tipo}</span></td>
                    <td>${item.timestamp}</td>
                    <td>Dispositivo ${item.dispositivo_id}</td>
                </tr>`;
            });

            html += \'</tbody></table>\';
            return html;
        }

        function exportarExcel() {
            const formData = new FormData(document.getElementById(\'reporteForm\'));
            const params = new URLSearchParams(formData);

            window.open(\'/reportes/exportar-excel?\' + params.toString(), \'_blank\');
        }
        </script>
        ';

        include __DIR__ . '/../views/layout.php';
    }

    public function generar() {
        // Skip auth check - API endpoint
        $tipo = $_GET['tipo_reporte'] ?? '';
        $empleado_id = $_GET['empleado_id'] ?? '';
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin = $_GET['fecha_fin'] ?? '';

        $resultados = [];

        switch ($tipo) {
            case 'falta':
            case 'vacaciones':
            case 'licencia_medica':
            case 'dia_economico':
            case 'comision_todo_dia':
            case 'constancia_tiempo':
            case 'PDSEP-SNTE':
            case 'EYR':
            case 'CLIDDA':
            case 'cuidados_maternos':
                $resultados = $this->generarReporteAsistencia($empleado_id, $fecha_inicio, $fecha_fin, $tipo);
                break;
            case 'retardos':
                $resultados = $this->generarReporteRetardos($empleado_id, $fecha_inicio, $fecha_fin);
                break;
            case 'comisiones':
                $resultados = $this->generarReporteComisiones($empleado_id, $fecha_inicio, $fecha_fin);
                break;
            case 'ausencias':
                $resultados = $this->generarReporteAusencias($empleado_id, $fecha_inicio, $fecha_fin);
                break;
            case 'resumen':
                $resultados = $this->generarReporteResumen();
                break;
            default:
                $resultados = $this->generarReporteAsistencia($empleado_id, $fecha_inicio, $fecha_fin);
                break;
        }

        $this->jsonResponse([
            'tipo' => $tipo,
            'datos' => $resultados
        ]);
    }

    private function generarReporteResumen() {
        $conn = Database::getInstance()->getConnection();
        
        $stmt = $conn->query("SELECT tipo_asistencia, COUNT(*) as total FROM asistencia GROUP BY tipo_asistencia ORDER BY total DESC");
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultados = [];
        foreach ($tipos as $t) {
            $resultados[] = [
                'tipo' => $t['tipo_asistencia'],
                'total' => (int)$t['total']
            ];
        }
        
        return $resultados;
    }

    private function generarReporteAsistencia($empleado_id, $fecha_inicio, $fecha_fin, $tipo_asistencia = null) {
        $conn = Database::getInstance()->getConnection();
        
        $sql = "SELECT a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, a.tipo_asistencia,
                       e.nombre, e.apellido
                FROM asistencia a
                JOIN empleados e ON a.empleado_id = e.id
                WHERE 1=1";
        
        $params = [];
        
        if ($tipo_asistencia) {
            $sql .= " AND a.tipo_asistencia = ?";
            $params[] = $tipo_asistencia;
        }
        
        if ($empleado_id) {
            $sql .= " AND a.empleado_id = ?";
            $params[] = $empleado_id;
        }
        
        if ($fecha_inicio) {
            $sql .= " AND a.fecha >= ?";
            $params[] = $fecha_inicio;
        }
        
        if ($fecha_fin) {
            $sql .= " AND a.fecha <= ?";
            $params[] = $fecha_fin;
        }
        
        $sql .= " ORDER BY e.apellido, e.nombre, a.fecha";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultados = [];
        foreach ($registros as $r) {
            $resultados[] = [
                'empleado' => $r['apellido'] . ' ' . $r['nombre'],
                'empleado_id' => $r['empleado_id'],
                'fecha' => $r['fecha'],
                'hora_entrada' => $r['hora_entrada'],
                'hora_salida' => $r['hora_salida'],
                'tipo' => $r['tipo_asistencia']
            ];
        }
        
        return $resultados;
    }

    private function generarReporteRetardos($empleado_id, $fecha_inicio, $fecha_fin) {
        $retardos = $this->retardoModel->getByEmpleado($empleado_id, $fecha_inicio, $fecha_fin);
        $empleados = $this->empleadoModel->getAll();

        $empleadosMap = [];
        foreach ($empleados as $emp) {
            $empleadosMap[$emp['id']] = $emp['nombre'] . ' ' . $emp['apellido'];
        }

        $resultados = [];
        foreach ($retardos as $retardo) {
            $resultados[] = [
                'empleado' => $empleadosMap[$retardo['empleado_id']] ?? 'Desconocido',
                'fecha' => $retardo['fecha'],
                'minutos_retardo' => $retardo['minutos_retardo'],
                'tipo' => $retardo['tipo'] ?? $retardo['tipo_retraso'] ?? 'menor',
                'justificado' => $retardo['justificado']
            ];
        }

        return $resultados;
    }

    private function generarReporteComisiones($empleado_id, $fecha_inicio, $fecha_fin) {
        $comisiones = $this->comisionModel->getByEmpleado($empleado_id);
        $empleados = $this->empleadoModel->getAll();

        $empleadosMap = [];
        foreach ($empleados as $emp) {
            $empleadosMap[$emp['id']] = $emp['nombre'] . ' ' . $emp['apellido'];
        }

        $resultados = [];
        foreach ($comisiones as $comision) {
            // Filtrar por fechas si se especifican
            if ($fecha_inicio && $comision['fecha_asignacion'] < $fecha_inicio) continue;
            if ($fecha_fin && $comision['fecha_asignacion'] > $fecha_fin) continue;

            $resultados[] = [
                'empleado' => $empleadosMap[$comision['empleado_id']] ?? 'Desconocido',
                'descripcion' => $comision['descripcion'],
                'monto' => $comision['monto'],
                'fecha_asignacion' => $comision['fecha_asignacion'],
                'fecha_vencimiento' => $comision['fecha_vencimiento'],
                'justificada' => $comision['justificada']
            ];
        }

        return $resultados;
    }

    private function generarReporteAusencias($empleado_id, $fecha_inicio, $fecha_fin) {
        $ausencias = $this->ausenciaModel->getByEmpleado($empleado_id, $fecha_inicio, $fecha_fin);
        $empleados = $this->empleadoModel->getAll();

        $empleadosMap = [];
        foreach ($empleados as $emp) {
            $empleadosMap[$emp['id']] = $emp['nombre'] . ' ' . $emp['apellido'];
        }

        $resultados = [];
        foreach ($ausencias as $ausencia) {
            $resultados[] = [
                'empleado' => $empleadosMap[$ausencia['empleado_id']] ?? 'Desconocido',
                'tipo' => $ausencia['tipo'],
                'fecha_inicio' => $ausencia['fecha_inicio'],
                'fecha_fin' => $ausencia['fecha_fin'],
                'justificada' => $ausencia['justificada']
            ];
        }

        return $resultados;
    }

    public function exportarExcel() {
        $this->requireAuth();
        $tipo = $_GET['tipo_reporte'] ?? '';
        $empleado_id = $_GET['empleado_id'] ?? '';
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin = $_GET['fecha_fin'] ?? '';

        $resultados = [];

        switch ($tipo) {
            case 'falta':
            case 'vacaciones':
            case 'licencia_medica':
            case 'dia_economico':
            case 'comision_todo_dia':
            case 'constancia_tiempo':
            case 'PDSEP-SNTE':
            case 'EYR':
            case 'CLIDDA':
            case 'cuidados_maternos':
                $resultados = $this->generarReporteAsistencia($empleado_id, $fecha_inicio, $fecha_fin, $tipo);
                break;
            case 'retardos':
                $resultados = $this->generarReporteRetardos($empleado_id, $fecha_inicio, $fecha_fin);
                break;
            case 'resumen':
                $resultados = $this->generarReporteResumen();
                break;
            default:
                $resultados = $this->generarReporteAsistencia($empleado_id, $fecha_inicio, $fecha_fin);
                break;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_' . $tipo . '_' . date('Ymd') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBB));

        if (!empty($resultados)) {
            fputcsv($output, array_keys($resultados[0]), ';');
            foreach ($resultados as $row) {
                fputcsv($output, $row, ';');
            }
        }

        fclose($output);
        exit;
    }

    public function attendanceSummary() {
        $this->jsonResponse(['data' => []]);
    }

    public function employeePerformance() {
        $this->jsonResponse(['data' => []]);
    }

    public function payrollExport() {
        echo "Exportación de nómina no implementada";
    }

    public function scheduled() {
        $this->jsonResponse(['success' => false, 'message' => 'Reportes programados no implementados']);
    }
    
    public function reportesExcel() {
        $tipo = $_GET['tipo_reporte'] ?? 'retardos';
        $empleado_id = $_GET['empleado_id'] ?? '';
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin = $_GET['fecha_fin'] ?? '';

        // If this is an API request (has tipo_reporte), return JSON
        if (isset($_GET['tipo_reporte'])) {
            try {
                $conn = Database::getInstance()->getConnection();
                
                $sql = "SELECT r.empleado_id, r.fecha, r.hora_entrada, r.hora_salida, r.minutos_retardo, r.justificado,
                               e.nombre, e.apellido
                        FROM retardos r
                        JOIN empleados e ON r.empleado_id = e.id
                        WHERE 1=1";
                
                $params = [];
                if ($empleado_id) {
                    $sql .= " AND r.empleado_id = ?";
                    $params[] = $empleado_id;
                }
                if ($fecha_inicio) {
                    $sql .= " AND r.fecha >= ?";
                    $params[] = $fecha_inicio;
                }
                if ($fecha_fin) {
                    $sql .= " AND r.fecha <= ?";
                    $params[] = $fecha_fin;
                }
                
                $sql .= " ORDER BY e.apellido, e.nombre, r.fecha";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $resultados = [];
                foreach ($registros as $r) {
                    $resultados[] = [
                        'empleado' => $r['apellido'] . ' ' . $r['nombre'],
                        'empleado_id' => $r['empleado_id'],
                        'fecha' => $r['fecha'],
                        'hora_entrada' => $r['hora_entrada'] ?: '-',
                        'hora_salida' => $r['hora_salida'] ?: '-',
                        'minutos_retardo' => $r['minutos_retardo'],
                        'justificado' => $r['justificado']
                    ];
                }
                
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'tipo' => $tipo,
                    'datos' => $resultados
                ]);
            } catch (Exception $e) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            return;
        }

        // Otherwise, show the view
        ob_start();
        include __DIR__ . '/../views/reportes/retardos_incidencias.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }
}
?>
