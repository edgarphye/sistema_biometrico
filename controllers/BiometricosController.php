<?php
require_once 'models/Biometrico.php';
require_once 'models/Asistencia.php';
require_once 'models/LogDispositivo.php';
require_once 'models/DispositivoBiometrico.php';

/**
 * Controlador para gestión de dispositivos biométricos y logs
 */
class BiometricosController {
    private $biometricoModel;
    private $asistenciaModel;
    private $logDispositivoModel;

    public function __construct() {
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

        include 'views/biometricos/index.php';
    }

    /**
     * Vista detallada de un dispositivo específico
     */
    public function show($dispositivoId) {
        // Validar ID del dispositivo
        if (!is_numeric($dispositivoId) || $dispositivoId < 1 || $dispositivoId > 10) {
            header('Location: /sistema_biometrico/biometricos');
            exit;
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

        include 'views/biometricos/show.php';
    }

    /**
     * API para obtener estado de dispositivos en tiempo real
     */
    public function getEstadoDispositivos() {
        header('Content-Type: application/json');

        try {
            $dispositivos = $this->biometricoModel->getEstadoDispositivos();
            $estadisticas = $this->biometricoModel->getBiometricStats();

            echo json_encode([
                'success' => true,
                'dispositivos' => $dispositivos,
                'estadisticas' => $estadisticas,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * API para obtener logs de un dispositivo
     */
    public function getLogsDispositivo($dispositivoId) {
        header('Content-Type: application/json');

        try {
            $logs = $this->logDispositivoModel->getByDispositivo($dispositivoId, 100);

            echo json_encode([
                'success' => true,
                'logs' => $logs
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * API para obtener estadísticas biométricas filtradas
     */
    public function getEstadisticasFiltradas() {
        header('Content-Type: application/json');

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

            echo json_encode([
                'success' => true,
                'estadisticas' => $estadisticas,
                'registros' => array_slice($asistencia, 0, 100) // Limitar resultados
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test de conectividad con dispositivo
     */
    public function testDispositivo($dispositivoId) {
        header('Content-Type: application/json');

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

            echo json_encode([
                'success' => true,
                'dispositivo' => $resultado
            ]);
        } catch (Exception $e) {
            // Log del error
            $this->logDispositivoModel->logEvent(
                $dispositivoId,
                'error',
                'error',
                'Error en test de conectividad: ' . $e->getMessage(),
                ['test_type' => 'connectivity', 'error' => $e->getMessage()]
            );

            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
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

            if ($dispositivoModel->exists($data['dispositivo_id'])) {
                $dispositivoModel->update($data['dispositivo_id'], $data);
                $mensaje = 'Dispositivo actualizado exitosamente';
            } else {
                $dispositivoModel->create($data);
                $mensaje = 'Dispositivo creado exitosamente';
            }

            header('Location: /sistema_biometrico/biometricos/configurar?mensaje=' . urlencode($mensaje));
            exit;
        }

        $dispositivos = $dispositivoModel->getAll();
        include 'views/biometricos/configurar.php';
    }

    /**
     * Captura de huella para registro de empleado
     */
    public function capturarHuella($dispositivoId) {
        header('Content-Type: application/json');

        try {
            require_once 'models/biometric/BiometricSimulation.php';
            $biometricoModel = new BiometricSimulation();
            $fingerprintData = $biometricoModel->captureFingerprint($dispositivoId);

            if ($fingerprintData) {
                echo json_encode([
                    'success' => true,
                    'fingerprint_data' => $fingerprintData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'No se pudo capturar la huella'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Registra huella de empleado en dispositivo
     */
    public function registrarHuella() {
        header('Content-Type: application/json');

        try {
            $dispositivoId = (int)$_POST['dispositivo_id'];
            $empleadoId = (int)$_POST['empleado_id'];
            $fingerprintData = $_POST['fingerprint_data'];

            $resultado = $this->biometricoModel->registerEmployeeFingerprint(
                $dispositivoId,
                $empleadoId,
                $fingerprintData
            );

            echo json_encode([
                'success' => $resultado
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sincroniza empleados con dispositivo
     */
    public function sincronizarEmpleados($dispositivoId) {
        header('Content-Type: application/json');

        try {
            $empleados = $this->biometricoModel->getEmpleadosActivos();
            $resultado = $this->biometricoModel->syncEmployees($dispositivoId, $empleados);

            echo json_encode([
                'success' => $resultado
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Conecta con un dispositivo biométrico
     */
    public function conectar($dispositivoId) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        try {
            $resultado = $this->biometricoModel->conectarDispositivo($dispositivoId);

            echo json_encode([
                'success' => true,
                'dispositivo' => $resultado
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Recibe datos biométricos de un dispositivo
     */
    public function recibirDatos($dispositivoId) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        try {
            $datos = $this->biometricoModel->recibirDatosBiometricos($dispositivoId);

            echo json_encode([
                'success' => true,
                'datos' => $datos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verifica identidad biométrica de un empleado
     */
    public function verificar($dispositivoId) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

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

                echo json_encode([
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

                echo json_encode([
                    'success' => false,
                    'error' => 'Empleado no identificado',
                    'tipo_verificacion' => $datosBiometricos['type']
                ]);
            }
        } catch (Exception $e) {
            // Log de error
            $this->logDispositivoModel->logEvent(
                $dispositivoId,
                'error',
                'error',
                'Error en verificación biométrica: ' . $e->getMessage(),
                ['error' => $e->getMessage()]
            );

            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>
