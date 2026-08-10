<?php
require_once __DIR__ . '/../models/Biometrico.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/LogDispositivo.php';
require_once __DIR__ . '/../models/DispositivoBiometrico.php';
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

        // Obtener empleados con registro biométrico (huella dactilar) para edición rápida
        $empleadosConHuella = $this->getEmpleadosConBiometrico();

        $this->render('biometricos/index', [
            'dispositivos' => $dispositivos,
            'estadisticasBiometricas' => $estadisticasBiometricas,
            'logsRecientes' => $logsRecientes,
            'estadisticasAsistencia' => $estadisticasAsistencia,
            'dispositivosConfigurados' => $dispositivosConfigurados,
            'empleadosConHuella' => $empleadosConHuella
        ]);
    }

    /**
     * Obtiene empleados con registro biométrico para edición rápida
     */
    private function getEmpleadosConBiometrico() {
        $db = new Database();
        $stmt = $db->getConnection()->prepare("
            SELECT e.id, e.nombre, e.apellido, e.area, e.puesto, e.jerarquia, e.clave_depto,
                   e.activo
            FROM empleados e
            WHERE e.activo = 1
              AND (e.huella_dactilar IS NOT NULL AND e.huella_dactilar != '')
            ORDER BY e.nombre, e.apellido
            LIMIT 200
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lista de dispositivos para gestionar
     */
    public function gestionarIndex() {
        $dispositivos = $this->biometricoModel->getEstadoDispositivos();

        $this->render('biometricos/gestionar_index', [
            'dispositivos' => $dispositivos
        ]);
    }

    /**
     * Página de gestión de un dispositivo (actualizar empleados, descargar asistencias)
     */
    public function gestionar($dispositivoId) {
        if (!is_numeric($dispositivoId) || $dispositivoId < 1) {
            $this->redirect('/sistema_biometrico/biometricos');
        }

        $dispositivo = $this->obtenerDispositivo($dispositivoId);
        $empleados = $this->biometricoModel->getEmpleadosConHuella();
        $logs = $this->logDispositivoModel->getByDispositivo($dispositivoId, 20);

        $estadisticas = $this->asistenciaModel->getEstadisticasPorDispositivo();
        $estadisticasDispositivo = array_filter($estadisticas, function($stat) use ($dispositivoId) {
            return (int)($stat['dispositivo_id'] ?? 0) === (int)$dispositivoId;
        });
        $estadisticasDispositivo = reset($estadisticasDispositivo) ?: null;

        $this->render('biometricos/gestionar', [
            'dispositivo' => $dispositivo,
            'empleados' => $empleados,
            'logs' => $logs,
            'dispositivoId' => $dispositivoId,
            'estadisticasDispositivo' => $estadisticasDispositivo
        ]);
    }

    /**
     * Vista detallada de un dispositivo específico
     */
    public function show($dispositivoId) {
        if (!is_numeric($dispositivoId) || $dispositivoId < 1 || $dispositivoId > 10) {
            $this->redirect('/sistema_biometrico/biometricos');
        }

        // Obtener información del dispositivo
        $dispositivo = $this->obtenerDispositivo($dispositivoId);

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
     * Obtiene la información de un dispositivo para mostrar su gestión.
     * Si el dispositivo no está accesible, devuelve la configuración con estado offline
     * para que la página no falle.
     */
    private function obtenerDispositivo($dispositivoId) {
        try {
            return $this->biometricoModel->conectarDispositivo($dispositivoId);
        } catch (Exception $e) {
            $db = new Database();
            $stmt = $db->getConnection()->prepare("SELECT * FROM dispositivos_biometricos WHERE dispositivo_id = ?");
            $stmt->execute([$dispositivoId]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'device_id' => $dispositivoId,
                'dispositivo_id' => $dispositivoId,
                'name' => $config['nombre'] ?? 'Dispositivo ' . $dispositivoId,
                'nombre' => $config['nombre'] ?? 'Dispositivo ' . $dispositivoId,
                'model' => 'MB360',
                'ip_address' => $config['ip_address'] ?? '',
                'port' => $config['puerto'] ?? 4370,
                'status' => 'desconectado',
                'type' => $config['tipo'] ?? 'huella',
                'firmware_version' => 'No disponible',
                'error' => $e->getMessage()
            ];
        }
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
            require_once 'models/biometric/BiometricFactory.php';
            $biometricoModel = BiometricFactory::getInstance()->createBiometric();
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
        try {
            require_once 'models/biometric/BiometricFactory.php';
            $biometrico = BiometricFactory::getInstance()->createBiometric();

            $dispositivos = $this->biometricoModel->getEstadoDispositivos();
            $resultados = [];
            $totalSync = 0;
            $errores = 0;

            foreach ($dispositivos as $disp) {
                $dispositivoId = $disp['dispositivo_id'] ?? $disp['id'] ?? null;
                if (!$dispositivoId) continue;

                try {
                    $biometrico->connectDevice($dispositivoId);
                    $attendanceResult = $biometrico->downloadAttendanceFromDevice($dispositivoId);

                    $totalSync += $attendanceResult['imported'];
                    $resultados[] = [
                        'dispositivo' => $disp['nombre'] ?? $disp['ip'],
                        'status' => 'ok',
                        'importados' => $attendanceResult['imported'],
                        'duplicados' => $attendanceResult['duplicates'],
                        'errores' => $attendanceResult['errors'],
                        'total' => $attendanceResult['total']
                    ];
                } catch (Exception $e) {
                    $errores++;
                    $resultados[] = [
                        'dispositivo' => $disp['nombre'] ?? $disp['ip'],
                        'status' => 'error',
                        'mensaje' => $e->getMessage()
                    ];
                }
            }

            $this->jsonResponse([
                'success' => $errores === 0,
                'message' => "Sincronización completada: $totalSync registros importados, $errores errores",
                'data' => $resultados
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error en sincronización: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualiza datos de un empleado en el dispositivo biométrico
     */
    public function actualizarEmpleadoEnDispositivo($dispositivoId) {
        try {
            $empleadoId = (int)$_POST['empleado_id'];

            require_once 'models/biometric/BiometricFactory.php';
            $biometrico = BiometricFactory::getInstance()->createBiometric();
            $biometrico->connectDevice($dispositivoId);
            $resultado = $biometrico->updateEmployeeOnDevice($dispositivoId, $empleadoId);

            $this->logDispositivoModel->logEvent(
                $dispositivoId,
                'actualizacion',
                $resultado ? 'exitoso' : 'fallido',
                $resultado ? "Empleado $empleadoId actualizado en dispositivo" : "Error actualizando empleado $empleadoId",
                ['empleado_id' => $empleadoId],
                $empleadoId
            );

            $this->jsonResponse([
                'success' => $resultado,
                'message' => $resultado ? 'Empleado actualizado en el dispositivo' : 'Error al actualizar empleado'
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'actualizarEmpleadoEnDispositivo']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descarga asistencias desde el dispositivo biométrico
     */
    public function descargarAsistencias($dispositivoId) {
        try {
            require_once 'models/biometric/BiometricFactory.php';
            $biometrico = BiometricFactory::getInstance()->createBiometric();
            $biometrico->connectDevice($dispositivoId);
            $resultado = $biometrico->downloadAttendanceFromDevice($dispositivoId);

            $this->logDispositivoModel->logEvent(
                $dispositivoId,
                'descarga_asistencias',
                'exitoso',
                "Descarga completada: {$resultado['imported']} importados, {$resultado['duplicates']} duplicados, {$resultado['errors']} errores",
                $resultado
            );

            $this->jsonResponse([
                'success' => true,
                'resultado' => $resultado,
                'message' => "Importados: {$resultado['imported']}, Duplicados: {$resultado['duplicates']}, Errores: {$resultado['errors']}"
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'descargarAsistencias']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualización masiva de todos los empleados en un dispositivo
     */
    public function actualizarEmpleadosMasivo($dispositivoId) {
        try {
            $db = new Database();
            $stmt = $db->getConnection()->prepare("
                SELECT DISTINCT e.id
                FROM empleados e
                WHERE e.activo = 1 AND (e.huella_dactilar IS NOT NULL AND e.huella_dactilar != '')
            ");
            $stmt->execute();
            $empleados = $stmt->fetchAll(PDO::FETCH_COLUMN);

            require_once 'models/biometric/BiometricFactory.php';
            $biometrico = BiometricFactory::getInstance()->createBiometric();
            $biometrico->connectDevice($dispositivoId);

            $actualizados = 0;
            $fallidos = 0;

            foreach ($empleados as $empleadoId) {
                try {
                    if ($biometrico->updateEmployeeOnDevice($dispositivoId, $empleadoId)) {
                        $actualizados++;
                    } else {
                        $fallidos++;
                    }
                } catch (Exception $e) {
                    $fallidos++;
                }
            }

            $this->jsonResponse([
                'success' => true,
                'message' => "Actualización masiva completada: $actualizados actualizados, $fallidos fallidos",
                'actualizados' => $actualizados,
                'fallidos' => $fallidos,
                'total' => count($empleados)
            ]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'actualizarEmpleadosMasivo']);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
?>
