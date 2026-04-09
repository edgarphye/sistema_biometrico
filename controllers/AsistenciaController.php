<?php
require_once __DIR__ . '/../services/AsistenciaService.php';
require_once __DIR__ . '/../services/ReporteService.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Biometrico.php';
require_once __DIR__ . '/../models/HorarioLaboral.php';
require_once __DIR__ . '/../models/Comision.php';
require_once __DIR__ . '/../models/DispositivoBiometrico.php';
require_once __DIR__ . '/../helpers/RequestValidator.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/BaseController.php';

class AsistenciaController extends BaseController {
    private $asistenciaService;
    private $asistenciaModel;
    private $reporteService;
    private $empleadoModel;
    private $retardoModel;
    private $biometricoModel;
    private $comisionModel;
    private $horarioModel;
    private $dispositivoBiometricoModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->asistenciaService = new AsistenciaService();
        $this->asistenciaModel = new Asistencia();
        $this->reporteService = new ReporteService();
        $this->empleadoModel = new Empleado();
        $this->retardoModel = new Retardo();
        $this->biometricoModel = new Biometrico();
        $this->horarioModel = new HorarioLaboral();
        $this->comisionModel = new Comision();
        $this->dispositivoBiometricoModel = new DispositivoBiometrico();
    }

    public function index() {
        $empleado_id = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;
        $asistencias = $this->asistenciaService->getByEmpleado($empleado_id);

        // Obtener áreas únicas para el filtro usando el modelo Empleado
        $areas = $this->empleadoModel->getAreasActivas();

        $this->render('asistencia/index', [
            'asistencias' => $asistencias,
            'areas' => $areas
        ]);
    }

    public function registrarEntrada() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dispositivo_id = isset($_POST['dispositivo_id']) ? (int)$_POST['dispositivo_id'] : 1;

            // Verificar si el dispositivo está activo
            $dispositivo = $this->dispositivoBiometricoModel->getByDispositivoId($dispositivo_id);
            if (!$dispositivo || !$dispositivo['activo']) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Dispositivo no activo o no encontrado.'
                ]);
                return;
            }

            // Medir tiempo de procesamiento
            $inicio_procesamiento = microtime(true);

            try {
                // Simular recepción de datos biométricos
                $datos_biometricos = $this->biometricoModel->recibirDatosBiometricos($dispositivo_id);

                // Verificar identidad
                $empleado = $this->biometricoModel->verificarIdentidad($datos_biometricos['data'], $datos_biometricos['type']);

                if ($empleado) {
                    // Calcular tiempo de procesamiento
                    $tiempo_procesamiento = round(microtime(true) - $inicio_procesamiento, 3);

                    // Registrar entrada usando el servicio
                    $this->asistenciaService->registrarEntrada(
                        $empleado['id'],
                        $dispositivo_id,
                        $datos_biometricos['type'], // tipo_biometria
                        $datos_biometricos['data'], // datos_biometricos (JSON)
                        $datos_biometricos['quality_score'], // calidad_verificacion
                        $datos_biometricos['metadata'], // metadata_dispositivo
                        $tiempo_procesamiento
                    );

                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Entrada registrada exitosamente',
                        'empleado' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                        'tipo_verificacion' => $datos_biometricos['type'],
                        'calidad' => $datos_biometricos['quality_score'] . '%',
                        'tiempo_procesamiento' => $tiempo_procesamiento . 's'
                    ]);
                } else {
                    // Log de verificación fallida
                    $this->biometricoModel->logVerificationFailure($dispositivo_id, $datos_biometricos);

                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Empleado no identificado',
                        'tipo_verificacion' => $datos_biometricos['type']
                    ]);
                }
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'registroEntrada', 'dispositivo_id' => $dispositivo_id ?? null]);
                // Log de error del dispositivo
                $this->biometricoModel->logDeviceError($dispositivo_id, 'registro_entrada', $e->getMessage());

                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error en el procesamiento biométrico: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function registrarSalida() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dispositivo_id = isset($_POST['dispositivo_id']) ? (int)$_POST['dispositivo_id'] : 1;

            // Verificar si el dispositivo está activo
            $dispositivo = $this->dispositivoBiometricoModel->getByDispositivoId($dispositivo_id);
            if (!$dispositivo || !$dispositivo['activo']) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Dispositivo no activo o no encontrado.'
                ]);
                return;
            }

            // Medir tiempo de procesamiento
            $inicio_procesamiento = microtime(true);

            try {
                // Simular recepción de datos biométricos
                $datos_biometricos = $this->biometricoModel->recibirDatosBiometricos($dispositivo_id);

                // Verificar identidad
                $empleado = $this->biometricoModel->verificarIdentidad($datos_biometricos['data'], $datos_biometricos['type']);

                if ($empleado) {
                    // Calcular tiempo de procesamiento
                    $tiempo_procesamiento = round(microtime(true) - $inicio_procesamiento, 3);

                    // Registrar salida usando el servicio
                    $this->asistenciaService->registrarSalida(
                        $empleado['id'],
                        $dispositivo_id,
                        $datos_biometricos['type'], // tipo_biometria
                        $datos_biometricos['data'], // datos_biometricos (JSON)
                        $datos_biometricos['quality_score'], // calidad_verificacion
                        $datos_biometricos['metadata'], // metadata_dispositivo
                        $tiempo_procesamiento
                    );

                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Salida registrada exitosamente',
                        'empleado' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                        'tipo_verificacion' => $datos_biometricos['type'],
                        'calidad' => $datos_biometricos['quality_score'] . '%',
                        'tiempo_procesamiento' => $tiempo_procesamiento . 's'
                    ]);
                } else {
                    // Log de verificación fallida
                    $this->biometricoModel->logVerificationFailure($dispositivo_id, $datos_biometricos);

                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Empleado no identificado',
                        'tipo_verificacion' => $datos_biometricos['type']
                    ]);
                }
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'registroSalida', 'dispositivo_id' => $dispositivo_id ?? null]);
                // Log de error del dispositivo
                $this->biometricoModel->logDeviceError($dispositivo_id, 'registro_salida', $e->getMessage());

                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error en el procesamiento biométrico: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function getEstadoDispositivos() {
        $dispositivos = $this->biometricoModel->getEstadoDispositivos();
        $this->jsonResponse($dispositivos);
    }
    
    public function getDetalles($id = null) {
        if ($id === null) {
            $id = $_GET['id'] ?? null;
        }
        
        if (!$id) {
            $this->jsonResponse(['error' => 'ID no proporcionado'], 400);
            return;
        }
        
        try {
            // Obtener datos del registro de asistencia
            $stmt = $this->asistenciaModel->getById($id);
            
            if (!$stmt) {
                $this->jsonResponse(['error' => 'Registro no encontrado'], 404);
                return;
            }
            
            $registro = is_array($stmt) ? $stmt : [$stmt];
            
            // Obtener datos del empleado
            $empleadoId = $registro[0]['empleado_id'] ?? null;
            $empleado = null;
            if ($empleadoId) {
                $stmt = $this->empleadoModel->getById($empleadoId);
                $empleado = is_array($stmt) ? $stmt[0] ?? null : $stmt;
            }
            
            // Obtener retardos del empleado en el período
            $fecha = $registro[0]['fecha'] ?? date('Y-m-d');
            $retardos = $this->retardoModel->getByEmpleado($empleadoId, $fecha, $fecha);
            
            // Obtener justificaciones
            $justificaciones = [];
            
            // Obtener sanciones relacionadas
            $sanciones = [];
            
            // Construir respuesta
            $detalles = [
                'registro' => [
                    'id' => $registro[0]['id'] ?? '',
                    'fecha' => date('d/m/Y', strtotime($registro[0]['timestamp'] ?? $fecha)),
                    'hora_entrada' => $registro[0]['hora'] ?? 'N/A',
                    'tipo' => $registro[0]['tipo'] ?? 'N/A',
                    'dispositivo' => $registro[0]['dispositivo_id'] ?? 'N/A',
                    'biometria' => $registro[0]['tipo_biometria'] ?? 'N/A',
                    'calificacion' => $registro[0]['calidad_verificacion'] ?? 'N/A'
                ],
                'empleado' => $empleado ? [
                    'id' => $empleado['id'],
                    'nombre' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                    'area' => $empleado['area'] ?? 'Sin área',
                    'puesto' => $empleado['puesto'] ?? 'Sin puesto'
                ] : null,
                'retardos' => $retardos,
                'justificaciones' => $justificaciones,
                'sanciones' => $sanciones
            ];
            
            $this->jsonResponse(['success' => true, 'data' => $detalles]);
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'obtenerDetallesAsistencia', 'empleado_id' => $empleadoId ?? null]);
            $this->jsonResponse(['error' => 'Error al obtener detalles: ' . $e->getMessage()], 500);
        }
    }

public function filtrarAsistencia() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [];

            // Validar datos de entrada
            $data = [
                'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin' => $_POST['fecha_fin'] ?? date('Y-m-d'),
                'empleado_id' => $_POST['empleado_id'] ?? ''
            ];
            
            $errors = RequestValidator::validateAsistenciaData($data);
            if (!empty($errors)) {
                $this->jsonResponse(['success' => false, 'errors' => $errors], 400);
            }

            // Filtros básicos validados
            $filtros['fecha_inicio'] = $data['fecha_inicio'];
            $filtros['fecha_fin'] = $data['fecha_fin'];
            $area = $_POST['area'] ?? null;

            // Filtros avanzados
            if (!empty($_POST['dispositivo_id'])) {
                $device_id = SecurityHelper::sanitizeInt($_POST['dispositivo_id'], 1);
                if ($device_id) $filtros['dispositivo_id'] = $device_id;
            }
            if (!empty($_POST['tipo_biometria'])) {
                $filtros['tipo_biometria'] = SecurityHelper::sanitizeString($_POST['tipo_biometria'], 'general');
            }
            if (!empty($_POST['tipo_asistencia'])) {
                $filtros['tipo_asistencia'] = SecurityHelper::sanitizeString($_POST['tipo_asistencia'], 'general');
            }
            if (!empty($data['empleado_id'])) {
                $filtros['empleado_id'] = $data['empleado_id'];
            }

            // Limitar resultados para mejor rendimiento
            $filtros['limit'] = 500;

            // Obtener registros de asistencia filtrados
            $registros = $this->asistenciaService->getAsistenciaFiltrada($filtros);

            // Si hay filtro de área, filtrar adicionalmente
            if ($area) {
                $registros = array_filter($registros, function($registro) use ($area) {
                    return $registro['area'] === $area;
                });
            }

            // Formatear respuesta para la vista
            $resultado = [];
            foreach ($registros as $registro) {
                $resultado[] = [
                    'id' => $registro['id'] ?? '',
                    'empleado' => ($registro['nombre'] ?? '') . ' ' . ($registro['apellido'] ?? ''),
                    'area' => $registro['area'] ?? 'Sin área',
                    'fecha' => !empty($registro['timestamp']) ? date('d/m/Y', strtotime($registro['timestamp'])) : ($registro['fecha'] ?? 'N/A'),
                    'hora' => !empty($registro['timestamp']) ? date('H:i:s', strtotime($registro['timestamp'])) : ($registro['hora'] ?? 'N/A'),
                    'tipo' => $registro['tipo'] ?? 'N/A',
                    'dispositivo_id' => $registro['dispositivo_id'] ?? '',
                    'tipo_biometria' => $registro['tipo_biometria'] ?? 'N/A',
                    'calidad_verificacion' => !empty($registro['calidad_verificacion']) ? $registro['calidad_verificacion'] . '%' : 'N/A',
                    'tiempo_procesamiento' => !empty($registro['tiempo_procesamiento']) ? round($registro['tiempo_procesamiento'], 3) . 's' : 'N/A'
                ];
            }

            $this->jsonResponse($resultado);
        }
    }

public function calcularHorasLaborables() {
        try {
            // Validar fechas
            $data = [
                'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin' => $_POST['fecha_fin'] ?? date('Y-m-d')
            ];
            
            $errors = RequestValidator::validateAsistenciaData($data);
            if (!empty($errors)) {
                $this->jsonResponse(['success' => false, 'errors' => $errors], 400);
            }
            
            $fecha_inicio = $data['fecha_inicio'];
            $fecha_fin = $data['fecha_fin'];
            $area = $_POST['area'] ?? null;

            // Obtener empleados con asistencia en el período
            $empleados_asistencia = $this->asistenciaService->getEmpleadosConAsistencia($fecha_inicio, $fecha_fin, $area);

            $resultado = [];

            // Procesar cada empleado
            foreach ($empleados_asistencia as $empleado_data) {
                $empleado_id = $empleado_data['id'];
                $fecha = $empleado_data['fecha'];

                // Obtener horario del empleado para esa fecha
                $horario = $this->horarioModel->getHorarioPorFecha($empleado_id, $fecha);

                // Obtener retardos del empleado en el período
                $retardos = $this->retardoModel->getByEmpleado($empleado_id, $fecha_inicio, $fecha_fin);

                // Calcular horas laborables usando el servicio
                $horas_calculadas = $this->asistenciaService->calcularHorasPorEmpleado($empleado_data, $horario, $retardos, $fecha);
                
                // Usar valores por defecto si no existen
                $resultado[] = [
                    'id' => $empleado_id,
                    'empleado' => $empleado_data['nombre'] . ' ' . $empleado_data['apellido'],
                    'area' => $empleado_data['area'] ?? 'Sin área',
                    'fecha' => date('d/m/Y', strtotime($fecha)),
                    'hora_entrada' => $horas_calculadas['hora_entrada'] ?? 'N/A',
                    'hora_salida' => $horas_calculadas['hora_salida'] ?? 'N/A',
                    'hora_oficial_entrada' => $horario['hora_entrada'] ?? 'N/A',
                    'hora_oficial_salida' => $horario['hora_salida'] ?? 'N/A',
                    'horas_laborables' => $horas_calculadas['horas_esperadas'] ?? 0,
                    'horas_trabajadas' => $horas_calculadas['horas_trabajadas'] ?? 0,
                    'retardo_minutos' => $horas_calculadas['retardo_total'] ?? 0,
                    'tipo_retardo' => $horas_calculadas['tipo_retardo'] ?? 'N/A',
                    'tiempo_extra_minutos' => 0,
                    'retardos_mes' => 0,
                    'justificaciones_pendientes' => 0,
                    'comentarios' => '',
                    'estado' => $horas_calculadas['estado'] ?? 'N/A'
                ];
            }

            $this->jsonResponse($resultado);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'calcularHorasLaborables']);
            $this->jsonResponse(['error' => 'Error al calcular horas laborables: ' . $e->getMessage()], 500);
        }
    }

    public function filtrarEmpleados() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;

            // Obtener empleados con asistencia en el período
            $empleados = $this->asistenciaService->getEmpleadosConAsistencia($fecha_inicio, $fecha_fin, $area);

            // Para cada empleado, obtener información adicional
            $resultado = [];
            foreach ($empleados as $empleado) {
                $retardos = $this->retardoModel->getByEmpleado($empleado['id'], $fecha_inicio, $fecha_fin);
                $comisiones = $this->comisionModel->getByEmpleado($empleado['id']);
                $justificaciones = $this->retardoModel->getPendientesJustificacion($empleado['id']);

                // Filtrar comisiones activas
                $comisiones_activas = array_filter($comisiones, function($comision) {
                    return strtotime($comision['fecha_vencimiento']) >= time() || $comision['fecha_vencimiento'] === null;
                });

                $resultado[] = [
                    'id' => $empleado['id'],
                    'nombre' => $empleado['nombre'],
                    'apellido' => $empleado['apellido'],
                    'area' => $empleado['area'],
                    'fecha' => $empleado['fecha'],
                    'hora_entrada' => $empleado['hora_entrada'],
                    'hora_salida' => $empleado['hora_salida'],
                    'retardos' => $retardos,
                    'comisiones' => array_values($comisiones_activas),
                    'justificaciones' => $justificaciones
                ];
            }

            $this->jsonResponse($resultado);
        }
    }

    public function exportarExcel() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [];

            // Filtros básicos
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;

            // Filtros avanzados
            if (!empty($_POST['dispositivo_id'])) {
                $filtros['dispositivo_id'] = (int)$_POST['dispositivo_id'];
            }
            if (!empty($_POST['tipo_biometria'])) {
                $filtros['tipo_biometria'] = $_POST['tipo_biometria'];
            }
            if (!empty($_POST['tipo_asistencia'])) {
                $filtros['tipo_asistencia'] = $_POST['tipo_asistencia'];
            }
            if (!empty($_POST['empleado_id'])) {
                $filtros['empleado_id'] = (int)$_POST['empleado_id'];
            }

            // Sin límite para exportación completa
            $filtros['limit'] = null;

            $filepath = $this->reporteService->exportarExcel($filtros, $area);

            // Enviar archivo al navegador
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . basename($filepath) . '"');
            header('Cache-Control: max-age=0');

            readfile($filepath);
            unlink($filepath); // Eliminar archivo temporal
            exit;
        }
    }

    public function exportarPDF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [];

            // Filtros básicos
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;

            // Filtros avanzados
            if (!empty($_POST['dispositivo_id'])) {
                $filtros['dispositivo_id'] = (int)$_POST['dispositivo_id'];
            }
            if (!empty($_POST['tipo_biometria'])) {
                $filtros['tipo_biometria'] = $_POST['tipo_biometria'];
            }
            if (!empty($_POST['tipo_asistencia'])) {
                $filtros['tipo_asistencia'] = $_POST['tipo_asistencia'];
            }
            if (!empty($_POST['empleado_id'])) {
                $filtros['empleado_id'] = (int)$_POST['empleado_id'];
            }

            // Sin límite para exportación completa
            $filtros['limit'] = null;

            $pdfContent = $this->reporteService->exportarPDF($filtros, $area);

            // Enviar PDF al navegador
            $filename = 'reporte_asistencia_' . date('Y-m-d_H-i-s') . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            echo $pdfContent;
            exit;
        }
    }

    public function realTime() {
        $this->jsonResponse(['success' => true, 'data' => []]);
    }

    public function manualEntry() {
        $this->jsonResponse(['success' => false, 'message' => 'Entrada manual no implementada']);
    }

    public function summary() {
        $this->jsonResponse(['summary' => []]);
    }

    public function bulkCorrect() {
        $this->jsonResponse(['success' => false, 'message' => 'Corrección masiva no implementada']);
    }
}
?>
