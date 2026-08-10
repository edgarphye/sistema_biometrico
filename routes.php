<?php
// routes.php

return FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // ---- Rutas de API ----
    $r->addRoute('GET', '/api/lista-empleados', function() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/api/lista-empleados.php';
    });

    $r->addRoute('GET', '/api/obtener_registros_justificacion', function() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/api/obtener_registros_justificacion.php';
    });

    $r->addRoute('POST', '/api/actualizar_justificacion', function() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/api/actualizar_justificacion.php';
    });

    $r->addRoute('POST', '/api/log-error', function() {
        // Simple route handler for logging
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }
        
        $message = $input['message'] ?? 'Unknown error';
        $type = $input['type'] ?? 'error';
        $url = $input['url'] ?? '';
        $line = $input['line'] ?? '';
        $stack = $input['stack'] ?? '';
        
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/js_errors_' . date('Y-m-d') . '.log';
        
        $logEntry = sprintf(
            "[%s] [%s] %s\nURL: %s\nLine: %s\nStack: %s\n%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($type),
            $message,
            $url,
            $line,
            $stack,
            str_repeat('-', 80)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        echo json_encode(['success' => true]);
    });

    // API: Obtener tipos de justificación
    $r->addRoute('GET', '/api/tipos-justificacion', function() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/models/TipoJustificacion.php';
        $model = new TipoJustificacion();
        $tipos = $model->getAll();
        echo json_encode(['success' => true, 'data' => $tipos]);
    });

    // ---- Rutas de Logs ----
    $r->addRoute('GET', '/logs', ['LogsController', 'index']);
    $r->addRoute('GET', '/logs/leer', ['LogsController', 'leer']);
    $r->addRoute('POST', '/logs/analizar-ai', ['LogsController', 'analizarAI']);
    $r->addRoute('POST', '/logs/aplicar-fix', ['LogsController', 'aplicarFix']);

    // ---- Rutas de Autenticación ----
    $r->addRoute('GET', '/login', ['AuthController', 'login']);
    $r->addRoute('POST', '/login', ['AuthController', 'login']);
    $r->addRoute('GET', '/register', ['AuthController', 'register']);
    $r->addRoute('POST', '/register', ['AuthController', 'register']);
    $r->addRoute('GET', '/logout', ['AuthController', 'logout']);
    $r->addRoute('GET', '/verify-2fa', ['AuthController', 'verify2FA']);
    $r->addRoute('POST', '/verify-2fa', ['AuthController', 'verify2FA']);
    $r->addRoute('GET', '/setup-2fa', ['AuthController', 'setup2FA']);
    $r->addRoute('POST', '/setup-2fa', ['AuthController', 'setup2FA']);
    
    // ---- Perfil de Usuario (Modal) ----
    $r->addRoute('POST', '/perfil/update', ['PerfilController', 'update']);
    
    // ---- Gestión de Notas Malas ----
    $r->addRoute('GET', '/notas-malas', ['NotasMalasController', 'index']);
    $r->addRoute('GET', '/notas-malas/ajax', ['NotasMalasController', 'ajaxList']);
    $r->addRoute('GET', '/notas-malas/oficio/{id:\d+}', ['NotasMalasController', 'descargarOficio']);
    $r->addRoute('GET', '/notas-malas/oficio/{id:\d+}/preview', ['NotasMalasController', 'verOficioHtml']);
    $r->addRoute('POST', '/notas-malas/generar-oficio', ['NotasMalasController', 'generarOficio']);
    $r->addRoute('POST', '/notas-malas/generar-todos', ['NotasMalasController', 'generarTodos']);
    $r->addRoute('GET', '/notas-malas/config', ['NotasMalasController', 'configuracion']);
    $r->addRoute('POST', '/notas-malas/config', ['NotasMalasController', 'guardarConfiguracion']);
    $r->addRoute('POST', '/notas-malas/evaluar-sanciones', function() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/models/Database.php';
        require_once __DIR__ . '/models/Retardo.php';
        
        $input = json_decode(file_get_contents('php://input'), true);
        $empleado_id = $input['empleado_id'] ?? null;
        $mes = $input['mes'] ?? (int)date('m');
        $anio = $input['anio'] ?? (int)date('Y');
        $quincena = $input['quincena'] ?? 0;
        
        if (!$empleado_id) {
            echo json_encode(['success' => false, 'error' => 'ID de empleado requerido']);
            exit;
        }
        
        $retardo = new Retardo();
        $resultado = $retardo->evaluarYSancionar($empleado_id, $mes, $anio, $quincena);
        
        echo json_encode(['success' => true, 'data' => $resultado]);
    });
    
    $r->addRoute('POST', '/notas-malas/procesar-todos', function() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/models/Database.php';
        require_once __DIR__ . '/models/Retardo.php';
        require_once __DIR__ . '/models/Empleado.php';
        
        $input = json_decode(file_get_contents('php://input'), true);
        $mes = $input['mes'] ?? (int)date('m');
        $anio = $input['anio'] ?? (int)date('Y');
        $quincena = $input['quincena'] ?? 0;
        
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->query("SELECT id FROM empleados WHERE estatus = 'activo'");
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultados = [];
        $retardo = new Retardo();
        
        foreach ($empleados as $emp) {
            $resultado = $retardo->evaluarYSancionar($emp['id'], $mes, $anio, $quincena);
            if ($resultado['sancion_creada'] ?? false) {
                $resultados[] = [
                    'empleado_id' => $emp['id'],
                    'resultado' => $resultado
                ];
            }
        }
        
        echo json_encode(['success' => true, 'procesados' => count($empleados), 'sanciones_creadas' => count($resultados), 'resultados' => $resultados]);
    });

    // ---- Gestión de Usuarios ----
    $r->addRoute('GET', '/usuarios', ['UsuarioController', 'index']);
    $r->addRoute('GET', '/usuarios/create', ['UsuarioController', 'create']);
    $r->addRoute('POST', '/usuarios/create', ['UsuarioController', 'create']);
    $r->addRoute('GET', '/usuarios/create-modal', ['UsuarioController', 'getCreateModal']);
    $r->addRoute('POST', '/usuarios/store', ['UsuarioController', 'storeUser']);
    $r->addRoute('GET', '/usuarios/{id:\d+}/edit', ['UsuarioController', 'edit']);
    $r->addRoute('POST', '/usuarios/edit', ['UsuarioController', 'edit']);
    $r->addRoute('GET', '/usuarios/{id:\d+}/edit-modal', ['UsuarioController', 'getEditModal']);
    $r->addRoute('POST', '/usuarios/update', ['UsuarioController', 'updateUser']);
    $r->addRoute('POST', '/usuarios/{id:\d+}/toggle-status', ['UsuarioController', 'toggleStatus']);
    $r->addRoute('POST', '/usuarios/{id:\d+}/change-password', ['UsuarioController', 'changePassword']);
    $r->addRoute('POST', '/usuarios/guardar-menu', ['UsuarioController', 'guardarMenuUsuario']);

    // ---- Rutas Principales ----
    $r->addRoute('GET', '/', ['DashboardController', 'index']);
    $r->addRoute('GET', '/inicio', ['DashboardController', 'index']);
    $r->addRoute('GET', '/dashboard', ['DashboardController', 'dashboard']);
    $r->addRoute('GET', '/dashboard/estado-dispositivos', ['DashboardController', 'getEstadoDispositivosAjax']);
    
    // ---- Dashboard - Rutas Adicionales ----
    $r->addRoute('GET', '/dashboard/real-time-stats', ['DashboardController', 'realTimeStats']);
    $r->addRoute('POST', '/dashboard/preferences', ['DashboardController', 'preferences']);
    $r->addRoute('GET', '/dashboard/alerts', ['DashboardController', 'alerts']);

    // ---- Empleados ----
    $r->addRoute('GET', '/empleados', ['EmpleadoController', 'index']);
    $r->addRoute('GET', '/empleados/create', ['EmpleadoController', 'create']);
    $r->addRoute('POST', '/empleados/create', ['EmpleadoController', 'create']);
    $r->addRoute('POST', '/empleados/generate_rfc', ['EmpleadoController', 'generate_rfc']);
    $r->addRoute('POST', '/empleados/generate_curp', ['EmpleadoController', 'generate_curp']);
    $r->addRoute('POST', '/empleados/capturarHuella', ['EmpleadoController', 'capturarHuella']);
    $r->addRoute('GET', '/empleados/{id:\d+}', ['EmpleadoController', 'show']);
    $r->addRoute('GET', '/empleados/{id:\d+}/ver', ['EmpleadoController', 'verPerfil']);
    $r->addRoute('GET', '/empleados/{id:\d+}/edit', ['EmpleadoController', 'edit']);
    $r->addRoute('POST', '/empleados/edit', ['EmpleadoController', 'edit']); // Asumiendo que el ID viene en el POST
    $r->addRoute('GET', '/empleados/{id:\d+}/delete', ['EmpleadoController', 'delete']);
    $r->addRoute('GET', '/empleados/datos-completos/{id:\d+}', ['EmpleadoController', 'datosCompletos']);
    $r->addRoute('GET', '/empleados/resumen-completo/{id:\d+}', ['EmpleadoController', 'resumenCompleto']);
    $r->addRoute('GET', '/empleados/horarios-y-asistencia/{id:\d+}', ['EmpleadoController', 'horariosYAsistencia']);
    
    // ---- Empleados - Rutas Adicionales ----
    $r->addRoute('GET', '/empleados/search', ['EmpleadoController', 'search']);
    $r->addRoute('POST', '/empleados/bulk-import', ['EmpleadoController', 'bulkImport']);
    $r->addRoute('GET', '/empleados/export', ['EmpleadoController', 'export']);
    $r->addRoute('POST', '/empleados/{id:\d+}/upload-photo', ['EmpleadoController', 'uploadPhoto']);
    $r->addRoute('GET', '/empleados/{id:\d+}/attendance-history', ['EmpleadoController', 'attendanceHistory']);
    $r->addRoute('GET', '/empleados/{id:\d+}/horarios-asistencia', ['EmpleadoController', 'horariosYAsistencia']);
    
    // ---- Asistencia ----
    $r->addRoute('GET', '/asistencia', ['AsistenciaController', 'index']);
    $r->addRoute('GET', '/marcaciones', ['MarcacionesController', 'index']);
    $r->addRoute('POST', '/asistencia/registrar-entrada', ['AsistenciaController', 'registrarEntrada']);
    $r->addRoute('POST', '/asistencia/registrar-salida', ['AsistenciaController', 'registrarSalida']);
    $r->addRoute('GET', '/asistencia/estado-dispositivos', ['AsistenciaController', 'getEstadoDispositivos']);
    $r->addRoute('POST', '/asistencia/filtrar-empleados', ['AsistenciaController', 'filtrarEmpleados']);
    $r->addRoute('POST', '/asistencia/filtrar-asistencia', ['AsistenciaController', 'filtrarAsistencia']);
    $r->addRoute('GET', '/asistencia/calcular-horas-laborables', ['AsistenciaController', 'calcularHorasLaborables']);
    $r->addRoute('POST', '/asistencia/calcular-horas-laborables', ['AsistenciaController', 'calcularHorasLaborables']);
    $r->addRoute('GET', '/asistencia/exportar-excel', ['AsistenciaController', 'exportarExcel']);
    $r->addRoute('GET', '/asistencia/exportar-pdf', ['AsistenciaController', 'exportarPDF']);
    $r->addRoute('GET', '/asistencia/detalles/{id:\d+}', ['AsistenciaController', 'getDetalles']);
    
    // ---- Asistencia - Rutas Adicionales ----
    $r->addRoute('GET', '/asistencia/real-time', ['AsistenciaController', 'realTime']);
    $r->addRoute('POST', '/asistencia/manual-entry', ['AsistenciaController', 'manualEntry']);
    $r->addRoute('GET', '/asistencia/summary', ['AsistenciaController', 'summary']);
    $r->addRoute('POST', '/asistencia/bulk-correct', ['AsistenciaController', 'bulkCorrect']);

    // ---- Reportes ----
    $r->addRoute('GET', '/reportes', ['ReportesController', 'index']);
    $r->addRoute('GET', '/reportes/', ['ReportesController', 'index']);
    $r->addRoute('GET', '/reportes/generar', ['ReportesController', 'generar']);
    $r->addRoute('GET', '/reportes/exportar-excel', ['ReportesController', 'exportarExcel']);
    $r->addRoute('POST', '/reportes/generar', ['ReportesController', 'generar']);
    
    // ---- Reportes - Rutas Adicionales ----
    $r->addRoute('GET', '/reportes/attendance-summary', ['ReportesController', 'attendanceSummary']);
    $r->addRoute('GET', '/reportes/employee-performance', ['ReportesController', 'employeePerformance']);
    $r->addRoute('GET', '/reportes/payroll-export', ['ReportesController', 'payrollExport']);
    $r->addRoute('POST', '/reportes/scheduled', ['ReportesController', 'scheduled']);
    $r->addRoute('GET', '/reportes/excel', ['ReportesController', 'reportesExcel']);

    // ---- Dispositivos Biométricos (Configuración) ----
    $r->addRoute('GET', '/biometricos', ['BiometricosController', 'index']);
    $r->addRoute('POST', '/biometricos/configurar', ['BiometricosController', 'configurar']);
    $r->addRoute('POST', '/biometricos/sync', ['BiometricosController', 'sync']);
    $r->addRoute('GET', '/biometricos/gestionar', ['BiometricosController', 'gestionarIndex']);
    $r->addRoute('GET', '/biometricos/gestionar/{id:\d+}', ['BiometricosController', 'gestionar']);
    $r->addRoute('GET', '/biometricos/{id:\d+}', ['BiometricosController', 'show']);
    $r->addRoute('GET', '/biometricos/capturar/{id:\d+}', ['BiometricosController', 'capturarHuella']);
    $r->addRoute('POST', '/biometricos/registrar-huella', ['BiometricosController', 'registrarHuella']);
    $r->addRoute('GET', '/biometricos/sincronizar/{id:\d+}', ['BiometricosController', 'sincronizarEmpleados']);
    $r->addRoute('POST', '/biometricos/conectar/{id:\d+}', ['BiometricosController', 'conectar']);
    $r->addRoute('GET', '/biometricos/recibir/{id:\d+}', ['BiometricosController', 'recibirDatos']);
    $r->addRoute('GET', '/biometricos/verificar/{id:\d+}', ['BiometricosController', 'verificar']);
    $r->addRoute('GET', '/biometricos/estado-dispositivos', ['BiometricosController', 'getEstadoDispositivos']);
    $r->addRoute('GET', '/biometricos/logs-dispositivo/{id:\d+}', ['BiometricosController', 'getLogsDispositivo']);
    $r->addRoute('GET', '/biometricos/estadisticas-filtradas', ['BiometricosController', 'getEstadisticasFiltradas']);
    $r->addRoute('POST', '/biometricos/test-dispositivo/{id:\d+}', ['BiometricosController', 'testDispositivo']);
    $r->addRoute('POST', '/biometricos/actualizar-empleado/{id:\d+}', ['BiometricosController', 'actualizarEmpleadoEnDispositivo']);
    $r->addRoute('POST', '/biometricos/actualizar-empleados-masivo/{id:\d+}', ['BiometricosController', 'actualizarEmpleadosMasivo']);
    $r->addRoute('POST', '/biometricos/descargar-asistencias/{id:\d+}', ['BiometricosController', 'descargarAsistencias']);

    // ---- ZKTeco Logs ----
    $r->addRoute('GET', '/zktecologs', ['ZKTecoController', 'index']);
    $r->addRoute('GET', '/zktecologs/getStatus', ['ZKTecoController', 'getStatus']);
    $r->addRoute('POST', '/zktecologs/analizar', ['ZKTecoController', 'analizar']);
    $r->addRoute('POST', '/zktecologs/procesar', ['ZKTecoController', 'procesar']);
    $r->addRoute('POST', '/zktecologs/reporte', ['ZKTecoController', 'reporte']);
    $r->addRoute('GET', '/zktecologs/procesar-web', ['ZKTecoController', 'procesamientoWeb']);

    // ---- Configuración ----
    $r->addRoute('GET', '/configuracion', ['ConfiguracionController', 'index']);
    $r->addRoute('POST', '/configuracion/uploadZkteco', ['ConfiguracionController', 'uploadZkteco']);
    $r->addRoute('GET', '/configuracion/getRecentHistory', ['ConfiguracionController', 'getRecentHistory']);
    $r->addRoute('GET', '/configuracion/getProcesamientoDetalle', ['ConfiguracionController', 'getProcesamientoDetalle']);
    $r->addRoute('GET', '/configuracion/corregirAsistencia', ['ConfiguracionController', 'corregirAsistencia']);
    $r->addRoute('POST', '/configuracion/procesarCompleto', ['ConfiguracionController', 'procesarCompleto']);
    $r->addRoute('GET', '/configuracion/calcularRetardos', ['ConfiguracionController', 'calcularRetardos']);
    $r->addRoute('GET', '/configuracion/limpiarLogs', ['ConfiguracionController', 'limpiarLogs']);

    // ---- Catálogos (Direcciones, Subdirecciones, Departamentos) ----
    $r->addRoute('GET', '/catalogos', ['CatalogosController', 'index']);
    $r->addRoute('POST', '/catalogos/guardar-direccion', ['CatalogosController', 'guardarDireccion']);
    $r->addRoute('POST', '/catalogos/eliminar-direccion', ['CatalogosController', 'eliminarDireccion']);
    $r->addRoute('POST', '/catalogos/guardar-subdireccion', ['CatalogosController', 'guardarSubdireccion']);
    $r->addRoute('POST', '/catalogos/eliminar-subdireccion', ['CatalogosController', 'eliminarSubdireccion']);
    $r->addRoute('POST', '/catalogos/guardar-departamento', ['CatalogosController', 'guardarDepartamento']);
    $r->addRoute('POST', '/catalogos/eliminar-departamento', ['CatalogosController', 'eliminarDepartamento']);
    $r->addRoute('GET', '/catalogos/get-subdirecciones', ['CatalogosController', 'getSubdirecciones']);
    $r->addRoute('GET', '/catalogos/get-departamentos', ['CatalogosController', 'getDepartamentos']);
    $r->addRoute('POST', '/catalogos/guardar-mando', ['CatalogosController', 'guardarMando']);
    $r->addRoute('POST', '/catalogos/eliminar-mando', ['CatalogosController', 'eliminarMando']);

    // ---- Validaciones (para jefes) ----
    $r->addRoute('GET', '/validations', ['ValidacionJefeController', 'index']);
    $r->addRoute('GET', '/validations/index', ['ValidacionJefeController', 'index']);

    // ---- Dispositivos (CRUD) ----
    $r->addRoute('GET', '/dispositivos', ['DispositivoBiometricoController', 'index']);
    $r->addRoute('GET', '/dispositivos/create', ['DispositivoBiometricoController', 'create']);
    $r->addRoute('POST', '/dispositivos/create', ['DispositivoBiometricoController', 'create']);
    $r->addRoute('GET', '/dispositivos/edit', ['DispositivoBiometricoController', 'edit']); // Asume ID por query param ?id=
    $r->addRoute('POST', '/dispositivos/edit', ['DispositivoBiometricoController', 'edit']);
    $r->addRoute('POST', '/dispositivos/delete', ['DispositivoBiometricoController', 'delete']);
    $r->addRoute('POST', '/dispositivos/test-connection', ['DispositivoBiometricoController', 'testConnection']);
    $r->addRoute('POST', '/dispositivos/sync', ['DispositivoBiometricoController', 'syncEmployees']);
    $r->addRoute('GET', '/dispositivos/status', ['DispositivoBiometricoController', 'getStatus']);
    $r->addRoute('GET', '/dispositivos/toggle-status', ['DispositivoBiometricoController', 'toggleStatus']);

    // ---- Horarios ----
    $r->addRoute('GET', '/horarios', ['HorarioController', 'index']);
    $r->addRoute('GET', '/horarios/create', ['HorarioController', 'create']);
    $r->addRoute('POST', '/horarios/create', ['HorarioController', 'create']);
    $r->addRoute('POST', '/horarios/edit', ['HorarioController', 'edit']); // Asume ID en POST
    $r->addRoute('GET', '/horarios/edit/{id:\d+}', ['HorarioController', 'edit']);
    $r->addRoute('GET', '/horarios/delete/{id:\d+}', ['HorarioController', 'delete']);
    $r->addRoute('GET', '/horarios/asignar', ['HorarioController', 'asignar']);
    $r->addRoute('POST', '/horarios/asignar', ['HorarioController', 'asignar']);
    $r->addRoute('GET', '/horarios/asignar-ciclo', ['HorarioController', 'asignarCiclo']);
    $r->addRoute('POST', '/horarios/asignar-ciclo', ['HorarioController', 'asignarCiclo']);
    $r->addRoute('POST', '/horarios/asignar-ciclo-empleado', ['HorarioController', 'asignarCicloEmpleado']);
    $r->addRoute('POST', '/horarios/editar-asignacion', ['HorarioController', 'editarAsignacion']);
    $r->addRoute('POST', '/horarios/eliminar-asignacion', ['HorarioController', 'eliminarAsignacion']);
    $r->addRoute('GET', '/horarios/ver-asignaciones', ['HorarioController', 'verAsignaciones']);
    $r->addRoute('GET', '/horarios/empleado/{id:\d+}/historial', ['HorarioController', 'historialEmpleado']);
    $r->addRoute('GET', '/empleados/{id:\d+}/historial-horarios', ['HorarioController', 'historialHorariosJson']);
    $r->addRoute('GET', '/horarios/mantenimiento', ['HorarioController', 'mantenimiento']);
    $r->addRoute('POST', '/horarios/mantenimiento/procesar', ['HorarioController', 'procesarMantenimiento']);
    $r->addRoute('GET', '/horarios/ciclos', ['HorarioController', 'ciclos']);
    $r->addRoute('POST', '/horarios/ciclos/procesar', ['HorarioController', 'procesarCiclos']);

    // ---- Ciclos y Horarios ----
    $r->addRoute('GET', '/ciclos', ['CiclosController', 'index']);
    // Endpoints JSON para la UI de Ciclos
    $r->addRoute('GET', '/ciclos/json', ['CiclosController', 'getAllJson']);
    $r->addRoute('POST', '/ciclos/create', ['CiclosController', 'create']);
    $r->addRoute('GET', '/ciclos/horarios-catalogo', ['CiclosController', 'getHorariosCatalogo']);
    $r->addRoute('POST', '/ciclos/update', ['CiclosController', 'update']);
    $r->addRoute('POST', '/ciclos/delete', ['CiclosController', 'delete']);
    // Endpoints JSON para Bloques de Ciclo
    $r->addRoute('GET', '/ciclos/bloques/json/{id:\d+}', ['CiclosController', 'getBloquesJson']);
    $r->addRoute('POST', '/ciclos/bloques/add', ['CiclosController', 'addBloquesDesdeHorario']);
    $r->addRoute('POST', '/ciclos/bloques/delete', ['CiclosController', 'deleteBloque']);
    $r->addRoute('POST', '/ciclos/bloques/delete-all', ['CiclosController', 'deleteAllBloques']);

    // ---- Justificaciones ----
    $r->addRoute('GET', '/justificaciones', ['JustificacionController', 'index']);
    $r->addRoute('POST', '/justificaciones/justificar', ['JustificacionController', 'justificar']); // Asume ID en POST
    $r->addRoute('POST', '/justificaciones/justificar/{id:\d+}', ['JustificacionController', 'justificar']);
    $r->addRoute('GET', '/justificaciones/justificar/{id:\d+}', ['JustificacionController', 'justificar']);
    $r->addRoute('GET', '/justificaciones/tipos', ['JustificacionController', 'tipos']);
    $r->addRoute('GET', '/justificaciones/tipos-json', ['JustificacionController', 'tiposJson']);
    $r->addRoute('GET', '/justificaciones/crear-tipo', ['JustificacionController', 'crearTipo']);
    $r->addRoute('POST', '/justificaciones/crear-tipo', ['JustificacionController', 'crearTipo']);
    // ---- Justificaciones AJAX (para modal en empleados) ----
    $r->addRoute('GET', '/justificaciones/get-retardo/{id:\d+}', ['JustificacionController', 'getRetardoAjax']);
    $r->addRoute('GET', '/justificaciones/get-asistencia-ajax/{id:\d+}', ['JustificacionController', 'getAsistenciaAjax']);
    $r->addRoute('POST', '/justificaciones/ajax-justificar', ['JustificacionController', 'ajaxJustificar']);
    $r->addRoute('POST', '/justificaciones/guardar-incidencia', ['JustificacionController', 'guardarIncidencia']);
    $r->addRoute('POST', '/justificaciones/actualizar-incidencia', ['JustificacionController', 'actualizarIncidencia']);

    // ---- Sanciones ----
    $r->addRoute('GET', '/sanciones', ['SancionController', 'index']);
    $r->addRoute('GET', '/sanciones/crear', ['SancionController', 'crear']);
    $r->addRoute('POST', '/sanciones/crear', ['SancionController', 'crear']);
    $r->addRoute('GET', '/sanciones/{id:\d+}', ['SancionController', 'show']);
    $r->addRoute('GET', '/sanciones/{id:\d+}/edit', ['SancionController', 'editar']);
    $r->addRoute('POST', '/sanciones/{id:\d+}/edit', ['SancionController', 'editar']);
    $r->addRoute('GET', '/sanciones/{id:\d+}/delete', ['SancionController', 'eliminar']);
    $r->addRoute('POST', '/sanciones/{id:\d+}/delete', ['SancionController', 'eliminar']);
    $r->addRoute('POST', '/sanciones/borrar-todas', ['SancionController', 'borrarTodas']);
    $r->addRoute('GET', '/sanciones/borrar-todas', ['SancionController', 'borrarTodas']);


    // ---- Comisiones ----
    $r->addRoute('GET', '/comisiones', ['ComisionController', 'index']);
    $r->addRoute('GET', '/comisiones/create', ['ComisionController', 'create']);
    $r->addRoute('POST', '/comisiones/create', ['ComisionController', 'create']);
    $r->addRoute('POST', '/comisiones/aprobar', ['ComisionController', 'aprobar']);
    $r->addRoute('POST', '/comisiones/justificar', ['ComisionController', 'justificar']);
    $r->addRoute('POST', '/comisiones/validar-limite', ['ComisionController', 'validarLimite']);
    $r->addRoute('GET', '/comisiones/get-by-empleado', ['ComisionController', 'getByEmpleado']);
    $r->addRoute('POST', '/comisiones/actualizar', ['ComisionController', 'actualizar']);

    // ---- Días Económicos ----
    $r->addRoute('GET', '/dias-economicos', ['DiasEconomicosController', 'index']);
    $r->addRoute('POST', '/dias-economicos/solicitar', ['DiasEconomicosController', 'solicitar']);
    $r->addRoute('POST', '/dias-economicos/aprobar', ['DiasEconomicosController', 'aprobar']);
    $r->addRoute('POST', '/dias-economicos/rechazar', ['DiasEconomicosController', 'rechazar']);
    $r->addRoute('GET', '/dias-economicos/empleado/{id:\d+}', ['DiasEconomicosController', 'getPorEmpleado']);
    $r->addRoute('POST', '/dias-economicos/actualizar', ['DiasEconomicosController', 'actualizar']);

    // ---- Validaciones por Jefes ----
    $r->addRoute('GET', '/validaciones', ['ValidacionJefeController', 'index']);
    $r->addRoute('POST', '/validaciones/validar/{id:\d+}', ['ValidacionJefeController', 'validar']);
    $r->addRoute('POST', '/validaciones/validar-retardo/{id:\d+}', ['ValidacionJefeController', 'validarPorRetardo']);
    $r->addRoute('GET', '/validaciones/obtener-incidencia/{id:\d+}', ['ValidacionJefeController', 'obtenerIncidencia']);
    $r->addRoute('POST', '/validaciones/procesar', ['ValidacionJefeController', 'procesar']);
    $r->addRoute('POST', '/validaciones/procesar-masivo', ['ValidacionJefeController', 'procesarValidacionMasiva']);
    $r->addRoute('GET', '/validaciones/obtener-contador-pendientes', ['ValidacionJefeController', 'obtenerContadorPendientes']);
    $r->addRoute('POST', '/validaciones/obtener-contador-pendientes', ['ValidacionJefeController', 'obtenerContadorPendientes']);
    $r->addRoute('POST', '/validaciones/incidencias-empleados', ['ValidacionJefeController', 'incidenciasEmpleados']);
    $r->addRoute('POST', '/validaciones/buscar-pendientes', ['ValidacionJefeController', 'buscarPendientes']);
    $r->addRoute('GET', '/validaciones/buscar-empleados', ['ValidacionJefeController', 'buscarEmpleados']);
    $r->addRoute('GET', '/validaciones/estadisticas', ['ValidacionJefeController', 'estadisticas']);
    $r->addRoute('GET', '/validaciones/obtener-areas', ['ValidacionJefeController', 'getAreas']);
    $r->addRoute('GET', '/validaciones/descargar-reporte', ['ValidacionJefeController', 'descargarReporte']);
    
    // ---- Validaciones: Sistema de Conversación Bidireccional ----
    $r->addRoute('GET', '/mis-validaciones', ['ValidacionJefeController', 'misValidaciones']);
    $r->addRoute('GET', '/validaciones/mensajes/{id:\d+}', ['ValidacionJefeController', 'apiObtenerMensajes']);
    $r->addRoute('POST', '/validaciones/mensajes/agregar', ['ValidacionJefeController', 'apiAgregarMensaje']);
    $r->addRoute('GET', '/validaciones/contador-no-leidas', ['ValidacionJefeController', 'apiContadorNoLeidas']);
    $r->addRoute('POST', '/validaciones/marcar-leido', ['ValidacionJefeController', 'apiMarcarLeido']);
    
    // ---- Huellas Dactilares ----
    $r->addRoute('GET', '/huellas', ['HuellaController', 'index']);
    $r->addRoute('POST', '/huellas/sincronizar', ['HuellaController', 'sincronizar']);
    $r->addRoute('POST', '/huellas/enrolar', ['HuellaController', 'enrolar']);
    $r->addRoute('GET', '/huellas/verificar-estado', ['HuellaController', 'verificarEstado']);
    $r->addRoute('GET', '/huellas/listar', ['HuellaController', 'listarEmpleados']);
    $r->addRoute('POST', '/huellas/eliminar', ['HuellaController', 'eliminar']);
    
    // ---- Soporte (archivos) ----
    $r->addRoute('GET', '/soportes/justificaciones/{filename:.+}', ['SoporteController', 'serveJustificacion']);
    $r->addRoute('GET', '/uploads/{filepath:.+}', ['SoporteController', 'serveUpload']);
    
    // ---- Gestión de Base de Datos ----
    $r->addRoute('GET', '/database', ['DatabaseController', 'index']);
    $r->addRoute('POST', '/database/backup', ['DatabaseController', 'backup']);
    $r->addRoute('POST', '/database/restore', ['DatabaseController', 'restore']);
    $r->addRoute('GET', '/database/listBackups', ['DatabaseController', 'listBackups']);
    $r->addRoute('GET', '/database/tables', ['DatabaseController', 'tables']);
    $r->addRoute('GET', '/database/foreignKeys', ['DatabaseController', 'foreignKeys']);
    $r->addRoute('GET', '/database/status', ['DatabaseController', 'status']);
    $r->addRoute('POST', '/database/deleteBackup', ['DatabaseController', 'deleteBackup']);

    // ---- Configuración de Menú por Usuario ----
    $r->addRoute('GET', '/permisos-menu', ['MenuConfigController', 'index']);
    $r->addRoute('POST', '/menu-config/guardar', ['MenuConfigController', 'guardar']);
    $r->addRoute('GET', '/menu-config/getConfig', ['MenuConfigController', 'getConfig']);

    // ---- Resumen de Justificaciones ----
    $r->addRoute('GET', '/resumen-justificaciones', ['ResumenJustificacionesController', 'index']);

    // ---- Agente IA de Gestión de Asistencia ----
    $r->addRoute('GET', '/agente-ia', ['AgenteIAController', 'index']);
    $r->addRoute('GET', '/agente-ia/dashboard', ['AgenteIAController', 'dashboard']);
    $r->addRoute('POST', '/agente-ia/procesar', ['AgenteIAController', 'procesar']);
    $r->addRoute('GET', '/agente-ia/reporte', ['AgenteIAController', 'reporte']);
    $r->addRoute('POST', '/agente-ia/generar-documento', ['AgenteIAController', 'generarDocumento']);
    $r->addRoute('POST', '/agente-ia/exportar-excel', ['AgenteIAController', 'exportarExcel']);
    $r->addRoute('GET', '/agente-ia/incidencias-no-validadas', ['AgenteIAController', 'getIncidenciasNoValidadas']);
    $r->addRoute('GET', '/agente-ia/reglas', ['AgenteIAController', 'reglas']);
    $r->addRoute('POST', '/agente-ia/guardar-regla', ['AgenteIAController', 'guardarRegla']);
    $r->addRoute('POST', '/agente-ia/eliminar-regla', ['AgenteIAController', 'eliminarRegla']);
    $r->addRoute('GET', '/agente-ia/configuraciones', ['AgenteIAController', 'configuraciones']);
    $r->addRoute('POST', '/agente-ia/guardar-configuracion', ['AgenteIAController', 'guardarConfiguracion']);
    $r->addRoute('POST', '/agente-ia/evaluar-sanciones', ['AgenteIAController', 'evaluarSanciones']);
    $r->addRoute('GET', '/agente-ia/justificaciones', ['AgenteIAController', 'getJustificaciones']);
    $r->addRoute('GET', '/agente-ia/justificaciones/estadisticas', ['AgenteIAController', 'getEstadisticasJustificaciones']);
    $r->addRoute('GET', '/agente-ia/justificaciones/empleado', ['AgenteIAController', 'getJustificacionesPorEmpleado']);
    $r->addRoute('GET', '/agente-ia/justificaciones/analisis', ['AgenteIAController', 'analizarJustificaciones']);
    $r->addRoute('GET', '/agente-ia/justificaciones/prediccion', ['AgenteIAController', 'prediccionJustificaciones']);
    $r->addRoute('GET', '/agente-ia/justificaciones/estadisticas-completas', ['AgenteIAController', 'getEstadisticasCompletas']);
    $r->addRoute('GET', '/agente-ia/justificaciones/por-tipo', ['AgenteIAController', 'getJustificacionesPorTipo']);
    $r->addRoute('GET', '/agente-ia/justificaciones/resumen-tipo', ['AgenteIAController', 'getResumenPorTipo']);
    $r->addRoute('GET', '/agente-ia/justificaciones/analisis-completo', ['AgenteIAController', 'analisisPredictivoCompleto']);
    $r->addRoute('GET', '/agente-ia/justificaciones/top-empleados', ['AgenteIAController', 'getEmpleadosConMasJustificaciones']);

    // ---- Registro de Justificaciones ----
    $r->addRoute('GET', '/justificaciones/registrar', ['RegistroJustificacionController', 'index']);
    $r->addRoute('GET', '/justificaciones/tipos-para-registro', ['RegistroJustificacionController', 'getTipos']);
    $r->addRoute('GET', '/justificaciones/campos', ['RegistroJustificacionController', 'getCampos']);
    $r->addRoute('POST', '/justificaciones/registrar', ['RegistroJustificacionController', 'registrar']);

    // ---- Análisis Predictivo ----
    $r->addRoute('GET', '/analisis-predictivo', ['AnalisisPredictivoController', 'index']);
    $r->addRoute('GET', '/analisis-predictivo/dashboard', ['AnalisisPredictivoController', 'dashboard']);
    $r->addRoute('GET', '/analisis-predictivo/riesgos', ['AnalisisPredictivoController', 'predecirRiesgos']);
    $r->addRoute('GET', '/analisis-predictivo/empleado', ['AnalisisPredictivoController', 'analizarEmpleado']);
    $r->addRoute('GET', '/analisis-predictivo/patrones', ['AnalisisPredictivoController', 'analizarPatrones']);
    $r->addRoute('GET', '/analisis-predictivo/alertas', ['AnalisisPredictivoController', 'generarAlertas']);
    $r->addRoute('GET', '/analisis-predictivo/alertas/historicas', ['AnalisisPredictivoController', 'getAlertas']);
    $r->addRoute('POST', '/analisis-predictivo/alertas/marcar', ['AnalisisPredictivoController', 'marcarAlerta']);
    $r->addRoute('GET', '/analisis-predictivo/recomendaciones', ['AnalisisPredictivoController', 'generarRecomendaciones']);
    $r->addRoute('GET', '/analisis-predictivo/area', ['AnalisisPredictivoController', 'analizarArea']);
    $r->addRoute('GET', '/analisis-predictivo/historial', ['AnalisisPredictivoController', 'historialPronostico']);
    $r->addRoute('GET', '/analisis-predictivo/comparar', ['AnalisisPredictivoController', 'compararPeriodos']);
    $r->addRoute('GET', '/analisis-predictivo/config', ['AnalisisPredictivoController', 'configuraciones']);
    $r->addRoute('POST', '/analisis-predictivo/config', ['AnalisisPredictivoController', 'guardarConfiguracion']);

    // ---- Agent IA Inteligente ----
    $r->addRoute('GET', '/agent-ia', ['AgenteInteligenteController', 'index']);
    $r->addRoute('GET', '/agent-ia/dashboard', ['AgenteInteligenteController', 'dashboard']);
    $r->addRoute('GET', '/agent-ia/analizar', ['AgenteInteligenteController', 'analizar']);
    $r->addRoute('GET', '/agent-ia/metricas', ['AgenteInteligenteController', 'metricas']);

    // ---- AI Completo (Análisis Avanzado) ----
    $r->addRoute('GET', '/ai', ['AIController', 'index']);
    $r->addRoute('GET', '/ai/analizar/{id:\d+}', ['AIController', 'analizarEmpleado']);
    $r->addRoute('GET', '/ai/analisis-global', ['AIController', 'analisisGlobal']);
    $r->addRoute('GET', '/ai/reportes', ['AIController', 'reportes']);
    $r->addRoute('GET', '/ai/metricas-tiempo-real', ['AIController', 'metricasTiempoReal']);
    $r->addRoute('GET', '/ai/alertas', ['AIController', 'alertas']);
    $r->addRoute('POST', '/ai/alertas/marcar', ['AIController', 'marcarAlerta']);
    $r->addRoute('POST', '/ai/actualizar-datos', ['AIController', 'actualizarDatos']);
    $r->addRoute('GET', '/ai/historial/{id:\d+}', ['AIController', 'historial']);
    $r->addRoute('GET', '/ai/analisis-justificaciones', ['AIController', 'analisisJustificaciones']);
    $r->addRoute('GET', '/ai/empleados-mejora', ['AIController', 'empleadosMejora']);
    $r->addRoute('GET', '/ai/tipos-justificacion', ['AIController', 'tiposJustificacion']);
    $r->addRoute('GET', '/ai/redes-neuronales', ['AIController', 'redesNeuronales']);
    $r->addRoute('GET', '/ai/neuronas-red', ['AIController', 'obtenerNeuronasRed']);
    $r->addRoute('GET', '/ai/detalle-neurona/{id:\d+}', ['AIController', 'obtenerDetalleNeurona']);

    // ---- API unificada para editar registros de tabs del empleado ----
    $r->addRoute('POST', '/api/actualizar-registro-tab', ['EmpleadoController', 'actualizarRegistroTab']);

    // ---- AI Cron (Análisis Programado) ----
    $r->addRoute('GET', '/ai-cron/ejecutar', ['AICronController', 'ejecutarAnalisis']);
    $r->addRoute('GET', '/ai-cron/resumen', ['AICronController', 'resumendia']);
});
