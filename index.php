<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$request = str_replace(BASE_URL, '', $request); // Adjust if needed

// Handle POST requests for API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($request) {
        case '/empleados/create':
            require_once 'controllers/EmpleadoController.php';
            $controller = new EmpleadoController();
            $controller->create();
            exit;
        case '/empleados/generate_rfc':
            require_once 'controllers/EmpleadoController.php';
            $controller = new EmpleadoController();
            $controller->generate_rfc();
            exit;
        case '/empleados/generate_curp':
            require_once 'controllers/EmpleadoController.php';
            $controller = new EmpleadoController();
            $controller->generate_curp();
            exit;
        case '/empleados/edit':
            require_once 'controllers/EmpleadoController.php';
            $controller = new EmpleadoController();
            $controller->edit($_POST['id']);
            exit;
        case '/asistencia/registrar-entrada':
            require_once 'controllers/AsistenciaController.php';
            $controller = new AsistenciaController();
            $controller->registrarEntrada();
            break;
        case '/asistencia/registrar-salida':
            require_once 'controllers/AsistenciaController.php';
            $controller = new AsistenciaController();
            $controller->registrarSalida();
            break;
        case '/asistencia/filtrar-asistencia':
            require_once 'controllers/AsistenciaController.php';
            $controller = new AsistenciaController();
            $controller->filtrarAsistencia();
            break;
        case '/login':
            require_once 'controllers/AuthController.php';
            $controller = new AuthController();
            $controller->login();
            break;
        case '/register':
            require_once 'controllers/AuthController.php';
            $controller = new AuthController();
            $controller->register();
            break;
        case '/horarios/create':
            require_once 'controllers/HorarioController.php';
            $controller = new HorarioController();
            $controller->create();
            break;
        case '/horarios/edit':
            require_once 'controllers/HorarioController.php';
            $controller = new HorarioController();
            $controller->edit($_POST['id']);
            break;
        case '/horarios/asignar':
            require_once 'controllers/HorarioController.php';
            $controller = new HorarioController();
            $controller->asignar();
            break;
        case '/justificaciones/justificar':
            require_once 'controllers/JustificacionController.php';
            $controller = new JustificacionController();
            $controller->justificar($id);
            break;
        case '/justificaciones/crear-tipo':
            require_once 'controllers/JustificacionController.php';
            $controller = new JustificacionController();
            $controller->crearTipo();
            break;
        case '/comisiones/create':
            require_once 'controllers/ComisionController.php';
            $controller = new ComisionController();
            $controller->create();
            break;
        case '/comisiones/aprobar':
            require_once 'controllers/ComisionController.php';
            $controller = new ComisionController();
            $controller->aprobar();
            break;
        case '/comisiones/justificar':
            require_once 'controllers/ComisionController.php';
            $controller = new ComisionController();
            $controller->justificar();
            break;
        case '/comisiones/validar-limite':
            require_once 'controllers/ComisionController.php';
            $controller = new ComisionController();
            $controller->validarLimite();
            break;
        default:
            // Handle form submissions
            break;
    }
    exit;
}

// Handle GET requests
switch ($request) {
    case '/':
        require_once 'controllers/AuthController.php';
        if (!AuthController::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    case '/login':
        require_once 'controllers/AuthController.php';
        if (AuthController::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        $controller = new AuthController();
        $controller->login();
        break;
    case '/register':
        require_once 'controllers/AuthController.php';
        if (AuthController::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        $controller = new AuthController();
        $controller->register();
        break;
    case '/logout':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;
    case '/empleados':
    case (preg_match('/\/empleados\?deleted=\d+$/', $request) ? true : false):
    case (preg_match('/\/empleados\?error=.+$/', $request) ? true : false):
        require_once 'controllers/EmpleadoController.php';
        $controller = new EmpleadoController();
        $controller->index();
        break;
    case '/empleados/create':
        require_once 'controllers/EmpleadoController.php';
        $controller = new EmpleadoController();
        $controller->create();
        break;
    case (preg_match('/\/empleados\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/EmpleadoController.php';
        $controller = new EmpleadoController();
        $controller->show($matches[1]);
        break;
    case (preg_match('/\/empleados\/(\d+)\/edit$/', $request, $matches) ? true : false):
        require_once 'controllers/EmpleadoController.php';
        $controller = new EmpleadoController();
        $controller->edit($matches[1]);
        break;
    case (preg_match('/\/empleados\/(\d+)\/delete$/', $request, $matches) ? true : false):
        require_once 'controllers/EmpleadoController.php';
        $controller = new EmpleadoController();
        $controller->delete($matches[1]);
        exit; // Importante: salir después de la redirección
    case '/asistencia':
        require_once 'controllers/AsistenciaController.php';
        $controller = new AsistenciaController();
        $controller->index();
        break;
    case '/asistencia/estado-dispositivos':
        require_once 'controllers/AsistenciaController.php';
        $controller = new AsistenciaController();
        $controller->getEstadoDispositivos();
        //exit;
        break;
    case '/asistencia/filtrar-empleados':
        require_once 'controllers/AsistenciaController.php';
        $controller = new AsistenciaController();
        $controller->filtrarEmpleados();
        break;
    case '/asistencia/filtrar-asistencia':
        require_once 'controllers/AsistenciaController.php';
        $controller = new AsistenciaController();
        $controller->filtrarAsistencia();
        //exit;
        break;
        case '/asistencia/calcular-horas-laborables':
            require_once 'controllers/AsistenciaController.php';
            $controller = new AsistenciaController();
            $controller->calcularHorasLaborables();
            exit;
    case '/asistencia/exportar-excel':
        require_once 'controllers/AsistenciaController.php';
        $controller = new AsistenciaController();
        $controller->exportarExcel();
        break;
    case '/asistencia/exportar-pdf':
        require_once 'controllers/AsistenciaController.php';
        $controller = new AsistenciaController();
        $controller->exportarPDF();
        break;
    case '/reportes':
        require_once 'controllers/ReportesController.php';
        $controller = new ReportesController();
        $controller->index();
        break;
    case '/reportes/generar':
        require_once 'controllers/ReportesController.php';
        $controller = new ReportesController();
        $controller->generar();
        break;
    case '/reportes/exportar-excel':
        require_once 'controllers/ReportesController.php';
        $controller = new ReportesController();
        $controller->exportarExcel();
        break;
    case '/dashboard':
        require_once 'controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
    case '/dashboard/estado-dispositivos':
        require_once 'controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->getEstadoDispositivosAjax();
        break;
    case '/biometricos':
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->index();
        break;
    case '/biometricos/configurar':
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->configurar();
        break;
    case (preg_match('/\/biometricos\/capturar\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->capturarHuella($matches[1]);
        break;
    case '/biometricos/registrar-huella':
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->registrarHuella();
        break;
    case (preg_match('/\/biometricos\/sincronizar\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->sincronizarEmpleados($matches[1]);
        break;
    case (preg_match('/\/biometricos\/conectar\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->conectar($matches[1]);
        break;
    case (preg_match('/\/biometricos\/recibir\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->recibirDatos($matches[1]);
        break;
    case (preg_match('/\/biometricos\/verificar\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->verificar($matches[1]);
        break;
    case (preg_match('/\/biometricos\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/BiometricosController.php';
        $controller = new BiometricosController();
        $controller->show($matches[1]);
        break;
    case '/horarios':
        require_once 'controllers/HorarioController.php';
        $controller = new HorarioController();
        $controller->index();
        break;
    case '/horarios/create':
        require_once 'controllers/HorarioController.php';
        $controller = new HorarioController();
        $controller->create();
        break;
    case '/horarios/asignar':
        require_once 'controllers/HorarioController.php';
        $controller = new HorarioController();
        $controller->asignar();
        break;
    case '/horarios/ver-asignaciones':
        require_once 'controllers/HorarioController.php';
        $controller = new HorarioController();
        $controller->verAsignaciones();
        break;
    case (preg_match('/\/horarios\/edit\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/HorarioController.php';
        $controller = new HorarioController();
        $controller->edit($matches[1]);
        break;
    case (preg_match('/\/horarios\/delete\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/HorarioController.php';
        $controller = new HorarioController();
        $controller->delete($matches[1]);
        break;
    case '/justificaciones':
        require_once 'controllers/JustificacionController.php';
        $controller = new JustificacionController();
        $controller->index();
        break;
    case (preg_match('/\/justificaciones\/justificar\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/JustificacionController.php';
        $controller = new JustificacionController();
        $controller->justificar($matches[1]);
        break;
    case '/justificaciones/tipos':
        require_once 'controllers/JustificacionController.php';
        $controller = new JustificacionController();
        $controller->tipos();
        break;

    case (preg_match('/\/soportes\/justificaciones\/(.+)$/', $request, $matches) ? true : false):
        require_once 'controllers/SoporteController.php';
        $controller = new SoporteController();
        $controller->serveJustificacion($matches[1]);
        break;

    case '/sanciones':
        require_once 'controllers/SancionController.php';
        $controller = new SancionController();
        $controller->index();
        break;

    case (preg_match('/\/sanciones\/(\d+)$/', $request, $matches) ? true : false):
        require_once 'controllers/SancionController.php';
        $controller = new SancionController();
        $controller->show($matches[1]);
        break;
        case '/sanciones/crear':
            require_once 'controllers/SancionController.php';
            $controller = new SancionController();
            $controller->crear();
            break;

        case (preg_match('/\/sanciones\/(\d+)\/edit$/', $request, $matches) ? true : false):
            require_once 'controllers/SancionController.php';
            $controller = new SancionController();
            $controller->editar($matches[1]);
            break;

        case (preg_match('/\/sanciones\/(\d+)\/delete$/', $request, $matches) ? true : false):
            require_once 'controllers/SancionController.php';
            $controller = new SancionController();
            $controller->eliminar($matches[1]);
            break;
    case '/justificaciones/crear-tipo':
        require_once 'controllers/JustificacionController.php';
        $controller = new JustificacionController();
        $controller->crearTipo();
        break;
    case '/comisiones':
        require_once 'controllers/ComisionController.php';
        $controller = new ComisionController();
        $controller->index();
        break;
    case '/comisiones/create':
        require_once 'controllers/ComisionController.php';
        $controller = new ComisionController();
        $controller->create();
        break;
    case '/comisiones/aprobar':
        require_once 'controllers/ComisionController.php';
        $controller = new ComisionController();
        $controller->aprobar();
        break;
    case '/comisiones/justificar':
        require_once 'controllers/ComisionController.php';
        $controller = new ComisionController();
        $controller->justificar();
        break;
    case '/comisiones/validar-limite':
        require_once 'controllers/ComisionController.php';
        $controller = new ComisionController();
        $controller->validarLimite();
        break;
    case '/comisiones/get-by-empleado':
        require_once 'controllers/ComisionController.php';
        $controller = new ComisionController();
        $controller->getByEmpleado();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada';
        break;
}
?>
