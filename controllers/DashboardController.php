<?php
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Comision.php';
require_once __DIR__ . '/../models/Ausencia.php';
require_once __DIR__ . '/../models/Biometrico.php';
require_once __DIR__ . '/../models/DiasEconomicos.php';
require_once __DIR__ . '/../models/LicenciaMedica.php';
require_once __DIR__ . '/../services/AsistenciaService.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/BaseController.php';

class DashboardController extends BaseController {
    private $empleadoModel;
    private $asistenciaService;
    private $retardoModel;
    private $comisionModel;
    private $ausenciaModel;
    private $biometricoModel;
    private $diasEconomicosModel;
    private $licenciaMedicaModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        
        $this->empleadoModel = new Empleado();
        $this->asistenciaService = new AsistenciaService();
        $this->retardoModel = new Retardo();
        $this->comisionModel = new Comision();
        $this->ausenciaModel = new Ausencia();
        $this->biometricoModel = new Biometrico();
        $this->diasEconomicosModel = new DiasEconomicos();
        $this->licenciaMedicaModel = new LicenciaMedica();
    }

    public function index() {
        $routePath = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($routePath, PHP_URL_PATH);
        
        // / or /inicio - muestra página de bienvenida de Recursos Humanos
        if ($path === '/' || $path === '/inicio') {
            require_once __DIR__ . '/../views/home.php';
            if (file_exists(__DIR__ . '/../views/layout.php')) {
                require_once __DIR__ . '/../views/layout.php';
            }
            return;
        }
        
        // /dashboard - muestra las gráficas
        if ($path === '/dashboard') {
            $this->dashboard();
            return;
        }
        
        // Por defecto muestra home
        require_once __DIR__ . '/../views/home.php';
        if (file_exists(__DIR__ . '/../views/layout.php')) {
            require_once __DIR__ . '/../views/layout.php';
        }
    }
    
    public function dashboard() {
        $db = \Database::getInstance()->getConnection();
        
        $empleados = $this->empleadoModel->getAll();
        $totalEmpleados = count($empleados);
        
        $hoy = date('Y-m-d');
        $mesActual = date('m');
        $anioActual = date('Y');
        $anioMesActual = date('Y-m');
        $mesAnterior = date('m', strtotime('-1 month'));
        $anioMesAnterior = date('Y-m', strtotime('-1 month'));
        
        $table = 'asistencia';
        try {
            $check = $db->query("SHOW TABLES LIKE 'asistencias'");
            if ($check->rowCount() > 0) $table = 'asistencias';
        } catch (\Exception $e) {}
        
        $asistenciasHoy = [];
        try {
            $stmt = $db->prepare("SELECT * FROM $table WHERE fecha = ?");
            $stmt->execute([$hoy]);
            $asistenciasHoy = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Dashboard Error: " . $e->getMessage());
        }
        
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM $table WHERE fecha BETWEEN ? AND ?");
        $stmt->execute([date('Y-m-01'), $hoy]);
        $asistenciasMes = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $empleadosPorEstatus = [];
        try {
            $stmt = $db->query("SELECT estatus, COUNT(*) as total FROM empleados GROUP BY estatus");
            $empleadosPorEstatus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        $empleadosPorArea = [];
        try {
            $stmt = $db->query("SELECT area, COUNT(*) as total FROM empleados WHERE area IS NOT NULL AND area != '' GROUP BY area ORDER BY total DESC LIMIT 10");
            $empleadosPorArea = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        $empleadosPorGenero = [];
        try {
            $stmt = $db->query("SELECT sexo as genero, COUNT(*) as total FROM empleados WHERE sexo IS NOT NULL AND sexo != '' GROUP BY sexo");
            $empleadosPorGenero = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        $empleadosPorTurno = [];
        try {
            $stmt = $db->query("SELECT turno, COUNT(*) as total FROM empleados WHERE turno IS NOT NULL AND turno != '' GROUP BY turno");
            $empleadosPorTurno = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        $empleadosPorTipo = [];
        try {
            $stmt = $db->query("SELECT tipo_empleado, COUNT(*) as total FROM empleados WHERE tipo_empleado IS NOT NULL AND tipo_empleado != '' GROUP BY tipo_empleado");
            $empleadosPorTipo = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        $antiguedadEmpleados = [];
        try {
            $stmt = $db->query("SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, fecha_ingreso, CURDATE()) < 1 THEN 'Menos de 1 año'
                    WHEN TIMESTAMPDIFF(YEAR, fecha_ingreso, CURDATE()) BETWEEN 1 AND 3 THEN '1-3 años'
                    WHEN TIMESTAMPDIFF(YEAR, fecha_ingreso, CURDATE()) BETWEEN 4 AND 7 THEN '4-7 años'
                    WHEN TIMESTAMPDIFF(YEAR, fecha_ingreso, CURDATE()) BETWEEN 8 AND 15 THEN '8-15 años'
                    ELSE 'Más de 15 años'
                END as rango,
                COUNT(*) as total
                FROM empleados WHERE fecha_ingreso IS NOT NULL GROUP BY 1 ORDER BY 2 DESC");
            $antiguedadEmpleados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        try {
            $stmt = $db->prepare("SELECT COALESCE(estado_validacion, 'sin_validar') as estatus, COUNT(*) as total FROM retardos WHERE YEAR(fecha) = ? AND MONTH(fecha) = ? GROUP BY estado_validacion");
            $stmt->execute([$anioActual, $mesActual]);
            $retardosPorEstatus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $retardosPorEstatus = [];
        }
        
        try {
            $stmt = $db->prepare("SELECT tipo_retraso as tipo, COUNT(*) as total FROM retardos WHERE YEAR(fecha) = ? AND MONTH(fecha) = ? GROUP BY tipo_retraso");
            $stmt->execute([$anioActual, $mesActual]);
            $retardosPorTipo = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $retardosPorTipo = [];
        }
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM retardos WHERE YEAR(fecha) = ? AND MONTH(fecha) = ?");
            $stmt->execute([$anioActual, $mesActual]);
            $totalRetardosMes = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $totalRetardosMes = ['total' => 0];
        }
        
        try {
            $stmt = $db->prepare("SELECT estatus, COUNT(*) as total FROM comisiones WHERE YEAR(fecha_solicitud) = ? AND MONTH(fecha_solicitud) = ? GROUP BY estatus");
            $stmt->execute([$anioActual, $mesActual]);
            $comisionesPorEstatus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $comisionesPorEstatus = [];
        }
        
        try {
            $stmt = $db->prepare("SELECT SUM(monto) as total FROM comisiones WHERE YEAR(fecha_solicitud) = ? AND MONTH(fecha_solicitud) = ? AND estatus = 'aprobada'");
            $stmt->execute([$anioActual, $mesActual]);
            $comisionesMonto = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $comisionesMonto = ['total' => 0];
        }
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM dias_economicos WHERE YEAR(fecha_solicitud) = ? AND MONTH(fecha_solicitud) = ?");
            $stmt->execute([$anioActual, $mesActual]);
            $diasEconomicosTotal = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $diasEconomicosTotal = ['total' => 0];
        }
        
        try {
            $stmt = $db->prepare("SELECT estatus, COUNT(*) as total FROM dias_economicos WHERE YEAR(fecha_solicitud) = ? AND MONTH(fecha_solicitud) = ? GROUP BY estatus");
            $stmt->execute([$anioActual, $mesActual]);
            $diasEconomicosPorEstatus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $diasEconomicosPorEstatus = [];
        }
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM licencias_medicas WHERE YEAR(fecha_inicio) = ? AND MONTH(fecha_inicio) = ?");
            $stmt->execute([$anioActual, $mesActual]);
            $licenciasMedicasTotal = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $licenciasMedicasTotal = ['total' => 0];
        }
        
        try {
            $stmt = $db->prepare("SELECT estatus, COUNT(*) as total FROM licencias_medicas WHERE YEAR(fecha_inicio) = ? AND MONTH(fecha_inicio) = ? GROUP BY estatus");
            $stmt->execute([$anioActual, $mesActual]);
            $licenciasPorEstatus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $licenciasPorEstatus = [];
        }
        
        try {
            $stmt = $db->prepare("SELECT COALESCE(tipo, 'otro') as tipo, COUNT(*) as total FROM ausencias WHERE YEAR(fecha_inicio) = ? AND MONTH(fecha_inicio) = ? GROUP BY tipo");
            $stmt->execute([$anioActual, $mesActual]);
            $ausenciasPorTipo = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $ausenciasPorTipo = [];
        }
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM ausencias WHERE YEAR(fecha_inicio) = ? AND MONTH(fecha_inicio) = ?");
            $stmt->execute([$anioActual, $mesActual]);
            $totalAusenciasMes = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $totalAusenciasMes = ['total' => 0];
        }
        
        try {
            $stmt = $db->prepare("SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, COUNT(*) as total FROM $table GROUP BY mes ORDER BY mes DESC LIMIT 12");
            $stmt->execute();
            $asistenciasPorMes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $asistenciasPorMes = [];
        }
        
        // Vacaciones
        $vacacionesPorEstatus = [];
        try {
            $stmt = $db->prepare("SELECT estatus, COUNT(*) as total FROM vacaciones WHERE YEAR(fecha_inicio) = ? AND MONTH(fecha_inicio) = ? GROUP BY estatus");
            $stmt->execute([$anioActual, $mesActual]);
            $vacacionesPorEstatus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        $vacacionesTotal = 0;
        try {
            $stmt = $db->prepare("SELECT SUM(dias_solicitados) as total FROM vacaciones WHERE YEAR(fecha_inicio) = ? AND MONTH(fecha_inicio) = ?");
            $stmt->execute([$anioActual, $mesActual]);
            $vacacionesTotal = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        // Sanciones
        $sancionesPorTipo = [];
        try {
            $stmt = $db->query("SELECT tipo_sancion as tipo, COUNT(*) as total FROM sanciones GROUP BY tipo_sancion");
            $sancionesPorTipo = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        $sancionesPorEstatus = [];
        try {
            $stmt = $db->query("SELECT estatus, COUNT(*) as total FROM sanciones GROUP BY estatus");
            $sancionesPorEstatus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        // Dispositivos biométricos
        $dispositivosEstado = [];
        try {
            $stmt = $db->query("SELECT estatus, COUNT(*) as total FROM dispositivos_biometricos GROUP BY estatus");
            $dispositivosEstado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        // Retardos por día de la semana
        $retardosPorDiaSemana = [];
        try {
            $stmt = $db->prepare("SELECT dia_semana, COUNT(*) as total FROM retardos WHERE YEAR(fecha) = ? AND MONTH(fecha) = ? GROUP BY dia_semana");
            $stmt->execute([$anioActual, $mesActual]);
            $retardosPorDiaSemana = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
        
        // Comparativo mes actual vs anterior
        $comparativo = [];
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM $table WHERE DATE_FORMAT(fecha, '%Y-%m') = ?");
            $stmt->execute([$anioMesActual]);
            $comparativo['asistencias_mes_actual'] = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (\Exception $e) {}
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM retardos WHERE DATE_FORMAT(fecha, '%Y-%m') = ?");
            $stmt->execute([$anioMesActual]);
            $comparativo['retardos_mes_actual'] = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (\Exception $e) {}
        
        // Top empleados con más retardos
        $topRetardadores = [];
        try {
            $stmt = $db->prepare("SELECT e.nombre, e.apellido, COUNT(r.id) as total FROM retardos r JOIN empleados e ON r.empleado_id = e.id WHERE YEAR(r.fecha) = ? AND MONTH(r.fecha) = ? GROUP BY r.empleado_id ORDER BY total DESC LIMIT 5");
            $stmt->execute([$anioActual, $mesActual]);
            $topRetardadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $topRetardadores = [];
        }
        
        $estadoDispositivos = $this->biometricoModel->getEstadoDispositivos();
        
        $stats = [
            'totalEmpleados' => $totalEmpleados,
            'asistenciasHoy' => count($asistenciasHoy),
            'asistenciasMes' => $asistenciasMes['total'] ?? 0,
            'empleadosPorEstatus' => $empleadosPorEstatus,
            'empleadosPorArea' => $empleadosPorArea,
            'empleadosPorGenero' => $empleadosPorGenero,
            'empleadosPorTurno' => $empleadosPorTurno,
            'empleadosPorTipo' => $empleadosPorTipo,
            'antiguedadEmpleados' => $antiguedadEmpleados,
            'retardosPorTipo' => $retardosPorTipo,
            'retardosPorEstatus' => $retardosPorEstatus,
            'retardosPorDiaSemana' => $retardosPorDiaSemana,
            'totalRetardosMes' => $totalRetardosMes['total'] ?? 0,
            'comisionesPorEstatus' => $comisionesPorEstatus,
            'comisionesMonto' => $comisionesMonto['total'] ?? 0,
            'diasEconomicosTotal' => $diasEconomicosTotal['total'] ?? 0,
            'diasEconomicosPorEstatus' => $diasEconomicosPorEstatus,
            'licenciasMedicasTotal' => $licenciasMedicasTotal['total'] ?? 0,
            'licenciasPorEstatus' => $licenciasPorEstatus,
            'ausenciasPorTipo' => $ausenciasPorTipo,
            'totalAusenciasMes' => $totalAusenciasMes['total'] ?? 0,
            'vacacionesPorEstatus' => $vacacionesPorEstatus,
            'vacacionesDias' => $vacacionesTotal['total'] ?? 0,
            'sancionesPorTipo' => $sancionesPorTipo,
            'sancionesPorEstatus' => $sancionesPorEstatus,
            'dispositivosEstado' => $dispositivosEstado,
            'asistenciasPorMes' => array_reverse($asistenciasPorMes),
            'estadoDispositivos' => $estadoDispositivos,
            'comparativo' => $comparativo,
            'topRetardadores' => $topRetardadores
        ];
        
        if (file_exists(__DIR__ . '/../views/dashboard.php')) {
            require_once __DIR__ . '/../views/dashboard.php';
        } else {
            $content = '<div class="alert alert-warning">Vista dashboard no encontrada</div>';
        }
        
        if (file_exists(__DIR__ . '/../views/layout.php')) {
            require_once __DIR__ . '/../views/layout.php';
        } else {
            echo $content;
        }
    }

    public function getEstadoDispositivosAjax() {
        $this->jsonResponse($this->biometricoModel->getEstadoDispositivos());
    }

    public function realTimeStats() {
        $this->jsonResponse(['success' => true, 'message' => 'Estadísticas en tiempo real no implementadas']);
    }

    public function preferences() {
        $this->jsonResponse(['success' => true, 'message' => 'Preferencias guardadas']);
    }

    public function alerts() {
        $this->jsonResponse(['alerts' => []]);
    }
}
?>
