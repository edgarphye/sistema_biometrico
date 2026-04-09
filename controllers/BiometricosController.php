<?php
require_once 'models/Biometrico.php';
require_once 'models/Asistencia.php';
require_once 'models/LogDispositivo.php';
require_once 'models/DispositivoBiometrico.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Controlador para gestión de dispositivos biométricos y logs
 */
class BiometricosController extends BaseController {
    private $biometricoModel;
    private $asistenciaModel;
    private $logDispositivoModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        
        $this->biometricoModel = new Biometrico();
        $this->asistenciaModel = new Asistencia();
        $this->logDispositivoModel = new LogDispositivo();
    }

    /**
     * Dashboard principal de dispositivos biométricos
     */
    public function index() {
        // Obtener estado de dispositivos
        $dispositivos = $this->biometricoModel->getEstadoDispositivos();

        // Obtener estadísticas biométricas
        $estadisticasBiometricas = $this->biometricoModel->getBiometricStats();

        // Obtener logs recientes
        $logsRecientes = $this->logDispositivoModel->getLogsRecientes(10);

        // Obtener estadísticas de asistencia por dispositivo
        $estadisticasAsistencia = $this->asistenciaModel->getEstadisticasPorDispositivo();

        // Obtener dispositivos configurados
        $dispositivoModel = new DispositivoBiometrico();
        $dispositivosConfigurados = $dispositivoModel->getAll();

        $this->render('biometricos/index', [
            'dispositivos' => $dispositivos,
            'estadisticasBiometricas' => $estadisticasBiometricas,
            'logsRecientes' => $logsRecientes,
            'estadisticasAsistencia' => $estadisticasAsistencia,
            'dispositivosConfigurados' => $dispositivosConfigurados
        ]);
    }

    /**
     * Vista detallada de un dispositivo específico
     */
    public function show($dispositivoId) {
        // Validar ID del dispositivo
        if (!is_numeric($dispositivoId) || $dispositivoId < 1 || $dispositivoId > 10) {
            $this->redirect('/sistema_biometrico/biometricos');
        }

        // Obtener información del dispositivo
        $dispositivo = $this->biometricoModel->conectarDispositivo($dispositivoId);

        // Obtener asistencia del dispositivo
        $asistencia = $this->asistenciaModel->getByDispositivo($dispositivoId);

        // Obtener logs del dispositivo
        $logs = $this->logDispositivoModel->getByDispositivo($dispositivoId, 50);

        // Obtener estadísticas del dispositivo
        $estadisticas = $this->asistenciaModel->getEstadisticasPorDispositivo();
        $estadisticasDispositivo = array_filter($estadisticas, function($stat) use ($dispositivoId) {
            return $stat['dispositivo_id'] == $dispositivoId;
        });
        $estadisticasDispositivo = reset($estadisticasDispositivo) ?: null;

        $this->render('biometricos/show', [
            'dispositivo' => $dispositivo,
            'asistencia' => $asistencia,
            'logs' => $logs,
            'estadisticasDispositivo' => $estadisticasDispositivo
        ]);
    }

    /**
     * API para obtener estado de dispositivos en tiempo real
     */
    public function getEstadoDispositivos() {
        try {
            $dispositivos = $this->biometricoModel->getEstadoDispositivos();
            $estadisticas = $this->biometricoModel->getBiometricStats();

            $this->jsonResponse([
                'success' => true,
                'dispositivos' => $dispositivos,
                'estadisticas' => $estadisticas,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'obtenerEstadisticas']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API para obtener logs de un dispositivo
     */
    public function getLogsDispositivo($dispositivoId) {
        try {
            $logs = $this->logDispositivoModel->getByDispositivo($dispositivoId, 100);

            $this->jsonResponse([
                'success' => true,
                'logs' => $logs
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'getLogsDispositivo', 'dispositivo_id' => $dispositivoId ?? null]);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API para obtener estadísticas biométricas filtradas
     */
    public function getEstadisticasFiltradas() {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            $dispositivoId = $_GET['dispositivo_id'] ?? null;

            $filtros = [];
            if ($fechaInicio && $fechaFin) {
                $filtros['fecha_inicio'] = $fechaInicio;
                $filtros['fecha_fin'] = $fechaFin;
            }
            if ($dispositivoId) {
                $filtros['dispositivo_id'] = $dispositivoId;
            }

            $asistencia = $this->asistenciaModel->getAsistenciaFiltrada($filtros);

            // Calcular estadísticas
            $estadisticas = [
                'total_registros' => count($asistencia),
                'por_tipo_biometria' => [],
                'por_dispositivo' => [],
                'calidad_promedio' => 0,
                'tiempo_promedio_procesamiento' => 0
            ];

            $totalCalidad = 0;
            $totalTiempo = 0;
            $countCalidad = 0;
            $countTiempo = 0;

            foreach ($asistencia as $registro) {
                // Por tipo de biometría
                $tipo = $registro['tipo_biometria'] ?? 'desconocido';
                if (!isset($estadisticas['por_tipo_biometria'][$tipo])) {
                    $estadisticas['por_tipo_biometria'][$tipo] = 0;
                }
                $estadisticas['por_tipo_biometria'][$tipo]++;

                // Por dispositivo
                $dispId = $registro['dispositivo_id'];
                if (!isset($estadisticas['por_dispositivo'][$dispId])) {
                    $estadisticas['por_dispositivo'][$dispId] = 0;
                }
                $estadisticas['por_dispositivo'][$dispId]++;

                // Calidad y tiempo
                if ($registro['calidad_verificacion']) {
                    $totalCalidad += $registro['calidad_verificacion'];
                    $countCalidad++;
                }
                if ($registro['tiempo_procesamiento']) {
                    $totalTiempo += $registro['tiempo_procesamiento'];
                    $countTiempo++;
                }
            }

            if ($countCalidad > 0) {
                $estadisticas['calidad_promedio'] = round($totalCalidad / $countCalidad, 2);
            }
            if ($countTiempo > 0) {
                $estadisticas['tiempo_promedio_procesamiento'] = round($totalTiempo / $countTiempo, 3);
            }

            $this->jsonResponse([
                'success' => true,
                'estadisticas' => $estadisticas,
                'registros' => array_slice($asistencia, 0, 100) // Limitar resultados
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'obtenerEstadisticasFiltradas']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de conectividad con dispositivo
     */
    public function testDispositivo($dispositivoId) {
        try {
            $resultado = $this->biometricoModel->conectarDispositivo($dispositivoId);

            // Log del test
            $this->logDispositivoModel->logEvent(
                $dispositivoId,
                'mantenimiento',
                'exitoso',
                'Test de conectividad exitoso',
                ['test_type' => 'connectivity']
            );

            $this->jsonResponse([
                'success' => true,
                'dispositivo' => $resultado
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'testConectividad', 'dispositivo_id' => $dispositivoId ?? null]);
            // Log del error
            $this->logDispositivoModel->logEvent(
                $dispositivoId,
                'error',
                'error',
                'Error en test de conectividad: ' . $e->getMessage(),
                ['test_type' => 'connectivity', 'error' => $e->getMessage()]
            );

            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestión de configuración de dispositivos
     */
    public function configurar() {
        $dispositivoModel = new DispositivoBiometrico();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'dispositivo_id' => (int)$_POST['dispositivo_id'],
                'nombre' => $_POST['nombre'],
                'tipo' => $_POST['tipo'] ?? 'dual',
                'marca' => $_POST['marca'] ?? null,
                'modelo' => $_POST['modelo'] ?? null,
                'ip_address' => $_POST['ip_address'] ?? null,
                'puerto' => (int)($_POST['puerto'] ?? 4370),
                'usuario' => $_POST['usuario'] ?? null,
                'password' => $_POST['password'],
                'configuracion' => [
                    'timeout' => (int)($_POST['timeout'] ?? 30),
                    'max_users' => (int)($_POST['max_users'] ?? 1000),
                    'auto_sync' => isset($_POST['auto_sync']),
                    'log_level' => $_POST['log_level'] ?? 'INFO'
                ]
            ];

            // Verificar si el dispositivo ya existe
            $existingDevice = $dispositivoModel->getByDispositivoId($data['dispositivo_id']);
            if ($existingDevice) {
                $dispositivoModel->update($existingDevice['id'], $data);
                $mensaje = 'Dispositivo actualizado exitosamente';
            } else {
                $dispositivoModel->create($data);
                $mensaje = 'Dispositivo creado exitosamente';
            }

            $this->redirect('/sistema_biometrico/biometricos/configurar?mensaje=' . urlencode($mensaje));
        }

        $dispositivos = $dispositivoModel->getAll();
        $this->render('biometricos/configurar', ['dispositivos' => $dispositivos]);
    }

    /**
     * Captura de huella para registro de empleado
     */
    public function capturarHuella($dispositivoId) {
        try {
            require_once 'models/biometric/BiometricSimulation.php';
            $biometricoModel = new BiometricSimulation();
            $fingerprintData = $biometricoModel->captureFingerprint($dispositivoId);

            if ($fingerprintData) {
                $this->jsonResponse([
                    'success' => true,
                    'fingerprint_data' => $fingerprintData
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No se pudo capturar la huella'
                ], 500);
            }
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'capturarHuella']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra huella de empleado en dispositivo
     */
    public function registrarHuella() {
        try {
            $dispositivoId = (int)$_POST['dispositivo_id'];
            $empleadoId = (int)$_POST['empleado_id'];
            $fingerprintData = $_POST['fingerprint_data'];

            $resultado = $this->biometricoModel->registerEmployeeFingerprint(
                $dispositivoId,
                $empleadoId,
                $fingerprintData
            );

            $this->jsonResponse([
                'success' => $resultado
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'registrarHuella', 'empleado_id' => $empleadoId ?? null]);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincroniza empleados con dispositivo
     */
    public function sincronizarEmpleados($dispositivoId) {
        try {
            $empleados = $this->biometricoModel->getEmpleadosActivos();
            $resultado = $this->biometricoModel->syncEmployees($dispositivoId, $empleados);

            $this->jsonResponse([
                'success' => $resultado
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'sincronizarEmpleados', 'dispositivo_id' => $dispositivoId ?? null]);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Conecta con un dispositivo biométrico
     */
public function conectar($dispositivoId) {
        // Depuración
        error_log("Método conectar llamado con ID: " . $dispositivoId);
        
        try {
            $resultado = $this->biometricoModel->conectarDispositivo($dispositivoId);
            error_log("Resultado de conexión: " . print_r($resultado, true));

$this->jsonResponse([
                'success' => true,
                'dispositivo' => $resultado
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'conectar', 'dispositivo_id' => $dispositivoId ?? null]);
            error_log("Error en conectar: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recibe datos biométricos de un dispositivo
     */
    public function recibirDatos($dispositivoId) {
        try {
            $datos = $this->biometricoModel->recibirDatosBiometricos($dispositivoId);

            $this->jsonResponse([
                'success' => true,
                'datos' => $datos
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'recibirDatos', 'dispositivo_id' => $dispositivoId ?? null]);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica identidad biométrica de un empleado
     */
    public function verificar($dispositivoId) {
        try {
            // Recibir datos biométricos del dispositivo
            $datosBiometricos = $this->biometricoModel->recibirDatosBiometricos($dispositivoId);

            // Verificar identidad
            $empleado = $this->biometricoModel->verificarIdentidad(
                $datosBiometricos['data'],
                $datosBiometricos['type']
            );

            if ($empleado) {
                // Log de verificación exitosa
                $this->logDispositivoModel->logEvent(
                    $dispositivoId,
                    'verificacion',
                    'exitoso',
                    'Verificación exitosa para empleado: ' . $empleado['nombre'] . ' ' . $empleado['apellido'],
                    [
                        'empleado_id' => $empleado['id'],
                        'tipo_biometria' => $datosBiometricos['type'],
                        'calidad' => $datosBiometricos['quality_score']
                    ]
                );

                $this->jsonResponse([
                    'success' => true,
                    'empleado' => $empleado,
                    'tipo_verificacion' => $datosBiometricos['type'],
                    'calidad' => $datosBiometricos['quality_score'] . '%',
                    'timestamp' => $datosBiometricos['timestamp']
                ]);
            } else {
                // Log de verificación fallida
                $this->logDispositivoModel->logEvent(
                    $dispositivoId,
                    'verificacion',
                    'fallido',
                    'Verificación fallida - empleado no identificado',
                    [
                        'tipo_biometria' => $datosBiometricos['type'],
                        'calidad' => $datosBiometricos['quality_score']
                    ]
                );

                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Empleado no identificado',
                    'tipo_verificacion' => $datosBiometricos['type']
                ]);
            }
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'verificarHuella', 'dispositivo_id' => $dispositivoId ?? null]);
            // Log de error
            $this->logDispositivoModel->logEvent(
                $dispositivoId,
                'error',
                'error',
                'Error en verificación biométrica: ' . $e->getMessage(),
                ['error' => $e->getMessage()]
            );

            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sync() {
        $this->jsonResponse([
            'success' => false,
            'message' => 'Sincronización general no implementada'
        ]);
    }
}
?>
