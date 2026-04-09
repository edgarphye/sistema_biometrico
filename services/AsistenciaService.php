<?php
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Ausencia.php';
require_once __DIR__ . '/../models/LogDispositivo.php';
require_once __DIR__ . '/../models/HorarioLaboral.php';
require_once __DIR__ . '/../models/DispositivoBiometrico.php';
require_once __DIR__ . '/../models/Sancion.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../services/NotificationService.php';

class AsistenciaService {
    private $asistenciaModel;
    private $retardoModel;
    private $ausenciaModel;
    private $logDispositivo;
    private $horarioModel;
    private $dispositivoModel;
    private $sancionModel;
    private $empleadoModel;
    private $notificationService;

    public function __construct() {
        $this->asistenciaModel = new Asistencia();
        $this->retardoModel = new Retardo();
        $this->ausenciaModel = new Ausencia();
        $this->logDispositivo = new LogDispositivo();
        $this->horarioModel = new HorarioLaboral();
        $this->dispositivoModel = new DispositivoBiometrico();
        $this->sancionModel = new Sancion();
        $this->empleadoModel = new Empleado();
        $this->notificationService = new NotificationService();
    }

    public function registrarEntrada($empleado_id, $dispositivo_id, $tipo_biometria = null, $datos_biometricos = null, $calidad_verificacion = null, $metadata_dispositivo = null, $tiempo_procesamiento = null, $hora_actual_for_testing = null) {
        // Serializar datos biométricos si es necesario
        $datos_biometricos_serializados = $datos_biometricos ? json_encode($datos_biometricos) : null;
        $metadata_serializada = $metadata_dispositivo ? json_encode($metadata_dispositivo) : null;

        $result = $this->asistenciaModel->registrarEntrada(
            $empleado_id,
            $dispositivo_id,
            $tipo_biometria,
            $datos_biometricos_serializados,
            $calidad_verificacion,
            $metadata_serializada,
            $tiempo_procesamiento
        );

        if ($result) {
            // Registrar log del dispositivo
            $this->logDispositivo->logEvent(
                $dispositivo_id,
                'entrada',
                'exitoso',
                'Registro de entrada exitoso',
                [
                    'empleado_id' => $empleado_id,
                    'tipo_biometria' => $tipo_biometria,
                    'calidad_verificacion' => $calidad_verificacion
                ],
                $empleado_id,
                $tipo_biometria
            );

            $hora_actual = $hora_actual_for_testing ?? date('H:i:s');
            $fecha_actual = date('Y-m-d');

            // Ahora pasamos el dispositivo_id para que se pueda determinar la sede
            $retardoInfo = $this->calcularRetardo($hora_actual, $empleado_id, $fecha_actual, $dispositivo_id);

            // Verificar si hay ausencia activa para este empleado en esta fecha
            $ausencia_activa = $this->ausenciaModel->getAusenciaActiva($empleado_id, $fecha_actual);

            if ($ausencia_activa) {
                // Si hay ausencia justificada, no registrar retardo
                $this->logDispositivo->logEvent(
                    $dispositivo_id,
                    'ausencia_justificada',
                    'info',
                    'Entrada registrada durante ausencia justificada',
                    ['ausencia_id' => $ausencia_activa['id']],
                    $empleado_id
                );
            } elseif ($retardoInfo['tipo'] !== 'puntual' && $retardoInfo['tipo'] !== 'tolerancia') {
                if ($retardoInfo['tipo'] === 'falta') {
                    // Si excede 30 minutos, se genera una ausencia automática (falta injustificada)
                    $this->ausenciaModel->create([
                        'empleado_id' => $empleado_id,
                        'fecha_inicio' => $fecha_actual,
                        'fecha_fin' => $fecha_actual,
                        'tipo' => 'falta_injustificada'
                    ]);

                    $this->logDispositivo->logEvent(
                        $dispositivo_id,
                        'falta_generada',
                        'warning',
                        'Se generó ausencia automática por retardo mayor a 30 minutos',
                        ['minutos' => $retardoInfo['minutos']],
                        $empleado_id
                    );
                } else {
                    // Registrar retardo si no hay ausencia justificada
                    $this->retardoModel->registrarRetardo(
                        $empleado_id,
                        $fecha_actual,
                        $retardoInfo['minutos'],
                        $retardoInfo['tipo'],
                        $retardoInfo['horario_id']
                    );

                    // Integración con IA - Actualizar sistema de análisis
                    try {
                        require_once __DIR__ . '/AIIntegracionEventos.php';
                        $aiIntegracion = new AIIntegracionEventos();
                        $aiIntegracion->eventoRetardoCreado($empleado_id, [
                            'minutos' => $retardoInfo['minutos'],
                            'tipo' => $retardoInfo['tipo'],
                            'fecha' => $fecha_actual
                        ]);
                    } catch (Exception $e) {
                        error_log("Error en integración IA: " . $e->getMessage());
                    }

                    // Validar acumulado quincenal después de registrar
                    $acumulados = $this->retardoModel->getRetardosAcumuladosQuincena($empleado_id, $fecha_actual);

                    if ($acumulados < 2) {
                        // Mostrar mensaje: puede justificar hasta 2 retardos por quincena
                        $this->logDispositivo->logEvent(
                            $dispositivo_id,
                            'retardo_acumulado',
                            'info',
                            'Puede justificar hasta 2 retardos por quincena',
                            ['acumulados' => $acumulados],
                            $empleado_id
                        );
                    } else {
                        // Ya no puede registrar más retardos justificados
                        $this->logDispositivo->logEvent(
                            $dispositivo_id,
                            'retardo_acumulado_max',
                            'warning',
                            'Ya no puede registrar más retardos justificados en esta quincena',
                            ['acumulados' => $acumulados],
                            $empleado_id
                        );
                    }

                    // Si acumuló retardos >=1, aplicar nota mala y verificar si se notifica
                    if ($acumulados >= 1) {
                        $this->aplicarNotaMala($empleado_id);
                        // Ahora llamamos al nuevo servicio para que gestione la notificación
                        $this->notificationService->verificarYNotificarSanciones($empleado_id);
                    }
                }
            }
        }

        return $result;
    }

    // ... registrarSalida ...

    /**
     * Calcula el retardo basado en el horario del empleado y la sede del dispositivo
     */
    public function calcularRetardo($hora_entrada, $empleado_id, $fecha, $dispositivo_id = null) {
        $sede = null;
        if ($dispositivo_id) {
            // Usar el método correcto para buscar por el ID del dispositivo
            $dispositivo = $this->dispositivoModel->getByDispositivoId($dispositivo_id);
            if ($dispositivo) {
                $sede = $dispositivo['sede'];
            }
        }

        // Obtener horario aplicable para el empleado en esta fecha y sede
        $horario = $this->horarioModel->getHorarioPorFecha($empleado_id, $fecha, $sede);

        if (!$horario) {
            // Horario por defecto si no hay específico
            $hora_oficial = '09:00:00'; // Hora por defecto
            $tolerancia = 15; // Tolerancia por defecto en minutos
        } else {
            $hora_oficial = $horario['hora_entrada'];
            $tolerancia = $horario['tolerancia_minutos'];
        }

        // Calcular diferencia en minutos (sin redondear, solo minutos completos)
        $hora_entrada_timestamp = strtotime($hora_entrada);
        $hora_oficial_timestamp = strtotime($hora_oficial);
        $diferencia_segundos = $hora_entrada_timestamp - $hora_oficial_timestamp;
        $minutos_retardo = floor($diferencia_segundos / 60);

        // Usar la lógica centralizada en el modelo HorarioLaboral para clasificar
        // Reglas: 0-10 (Tolerancia), 11-20 (Menor), 21-30 (Mayor), 31+ (Falta)
        $clasificacion = $this->horarioModel->clasificarRetardo($minutos_retardo);
        $tipo = $clasificacion['tipo'];

        // Si es puntual o tolerancia, reseteamos los minutos de retardo a 0 para efectos de nómina
        if (!$clasificacion['es_retardo']) {
            $minutos_retardo = 0;
        }

        return [
            'tipo' => $tipo,
            'minutos' => $minutos_retardo,
            'hora_oficial' => $hora_oficial,
            'tolerancia' => $tolerancia,
            'horario_id' => $horario ? $horario['id'] : null
        ];
    }

    public function calcularHorasPorEmpleado($empleado_data, $horario, $retardos, $fecha) {
        $empleado_id = $empleado_data['id'];

        // Obtener registros de asistencia para este empleado en la fecha
        $asistencias = $this->asistenciaModel->getByEmpleado($empleado_id, $fecha, $fecha);

        if (empty($asistencias)) {
            return [
                'horas_trabajadas' => 0,
                'horas_esperadas' => 0,
                'retardo_total' => 0,
                'tipo_retardo' => 'sin_asistencia',
                'estado' => 'ausente'
            ];
        }

        $total_minutos_trabajados = 0;
        $hora_entrada_real = null;
        $hora_salida_real = null;

        // Procesar cada registro de asistencia
        foreach ($asistencias as $asistencia) {
            if ($asistencia['tipo'] === 'entrada') {
                $hora_entrada_real = $asistencia['hora'];
            } elseif ($asistencia['tipo'] === 'salida') {
                $hora_salida_real = $asistencia['hora'];
            }
        }

        // Calcular horas trabajadas si tenemos entrada y salida
        if ($hora_entrada_real && $hora_salida_real) {
            $entrada_timestamp = strtotime($hora_entrada_real);
            $salida_timestamp = strtotime($hora_salida_real);
            $total_minutos_trabajados = round(($salida_timestamp - $entrada_timestamp) / 60);
        }

        // Calcular retardo si hay hora de entrada
        $retardo_minutos = 0;
        $tipo_retardo = 'puntual';

        if ($hora_entrada_real) {
            // Pasamos null para el dispositivo_id, la lógica de fallback se encargará
            $retardo_data = $this->calcularRetardo($hora_entrada_real, $empleado_id, $fecha, null);
            $retardo_minutos = $retardo_data['minutos'];
            $tipo_retardo = $retardo_data['tipo'];
        }

        // Calcular horas esperadas del horario
        $horas_esperadas = 0;
        if ($horario && isset($horario['hora_entrada']) && isset($horario['hora_salida'])) {
            $entrada_oficial = strtotime($horario['hora_entrada']);
            $salida_oficial = strtotime($horario['hora_salida']);
            $horas_esperadas = round(($salida_oficial - $entrada_oficial) / 3600, 2); // en horas
        }

        // Convertir minutos trabajados a horas
        $horas_trabajadas = round($total_minutos_trabajados / 60, 2);

        return [
            'horas_trabajadas' => $horas_trabajadas,
            'horas_esperadas' => $horas_esperadas,
            'retardo_total' => $retardo_minutos,
            'tipo_retardo' => $tipo_retardo,
            'estado' => $total_minutos_trabajados > 0 ? 'presente' : 'ausente',
            'hora_entrada' => $hora_entrada_real,
            'hora_salida' => $hora_salida_real
        ];
    }

    // ... (otros métodos) ...

    /**
     * Registra la salida de un empleado
     */
    public function registrarSalida($empleado_id, $dispositivo_id, $tipo_biometria = null, $datos_biometricos = null, $calidad_verificacion = null, $metadata_dispositivo = null, $tiempo_procesamiento = null) {
        // Serializar datos biométricos si es necesario
        $datos_biometricos_serializados = $datos_biometricos ? json_encode($datos_biometricos) : null;
        $metadata_serializada = $metadata_dispositivo ? json_encode($metadata_dispositivo) : null;

        $result = $this->asistenciaModel->registrarSalida(
            $empleado_id,
            $dispositivo_id,
            $tipo_biometria,
            $datos_biometricos_serializados,
            $calidad_verificacion,
            $metadata_serializada,
            $tiempo_procesamiento
        );

        if ($result) {
            // Registrar log del dispositivo
            $this->logDispositivo->logEvent(
                $dispositivo_id,
                'salida',
                'exitoso',
                'Registro de salida exitoso',
                [
                    'empleado_id' => $empleado_id,
                    'tipo_biometria' => $tipo_biometria,
                    'calidad_verificacion' => $calidad_verificacion
                ],
                $empleado_id,
                $tipo_biometria
            );
        }

        return $result;
    }

    /**
     * Obtiene registros de asistencia filtrados
     */
    public function getAsistenciaFiltrada(array $filtros) {
        return $this->asistenciaModel->getAsistenciaFiltrada($filtros);
    }

    /**
     * Obtiene empleados con asistencia en un período
     */
    public function getEmpleadosConAsistencia($fecha_inicio, $fecha_fin, $area = null) {
        return $this->asistenciaModel->getEmpleadosConAsistencia($fecha_inicio, $fecha_fin, $area);
    }

    /**
     * Obtiene registros de asistencia por empleado y rango de fechas
     * @param int|null $empleado_id ID del empleado (null para todos)
     * @param string|null $fecha_inicio Fecha de inicio
     * @param string|null $fecha_fin Fecha de fin
     * @return array Registros de asistencia
     */
    public function getByEmpleado($empleado_id = null, $fecha_inicio = null, $fecha_fin = null) {
        return $this->asistenciaModel->getByEmpleado($empleado_id, $fecha_inicio, $fecha_fin);
    }

    /**
     * Aplica una nota mala (sanción) a un empleado.
     */
    private function aplicarNotaMala($empleado_id) {
        $data = [
            'empleado_id' => $empleado_id,
            'tipo' => 'nota_mala',
            'fecha_inicio' => date('Y-m-d'),
            'motivo' => 'Acumulación de retardos en la quincena',
            'creado_por' => 'sistema'
        ];
        return $this->sancionModel->create($data);
    }

    /**
     * Notifica a un empleado sobre un evento.
     * @deprecated ahora manejado por NotificationService
     */
    private function notificarEmpleado($empleado_id, $evento) {
        // Por ahora, solo registramos en el log.
        // En el futuro, esto podría enviar un email o una notificación push.
        $this->logDispositivo->logEvent(
            null,
            'notificacion',
            'info',
            "Notificación para empleado $empleado_id sobre $evento.",
            [],
            $empleado_id
        );
    }
}
