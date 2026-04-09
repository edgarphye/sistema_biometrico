<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/HorarioLaboral.php';
require_once __DIR__ . '/../models/Sancion.php';
require_once __DIR__ . '/../models/ReglaNegocio.php';
require_once __DIR__ . '/../models/BitacoraAgente.php';
require_once __DIR__ . '/../models/AnomaliaDetectada.php';
require_once __DIR__ . '/../models/PlantillaDocumento.php';
require_once __DIR__ . '/../models/DispositivoBiometrico.php';

class AgenteIAService {
    private $db;
    private $config;
    
    private $empleadoModel;
    private $asistenciaModel;
    private $retardoModel;
    private $horarioModel;
    private $sancionModel;
    private $reglaModel;
    private $bitacoraModel;
    private $anomaliaModel;
    private $plantillaModel;
    private $dispositivoModel;

    public function __construct() {
        $this->db = Database::getInstance();
        
        $this->empleadoModel = new Empleado();
        $this->asistenciaModel = new Asistencia();
        $this->retardoModel = new Retardo();
        $this->horarioModel = new HorarioLaboral();
        $this->sancionModel = new Sancion();
        $this->reglaModel = new ReglaNegocio();
        $this->bitacoraModel = new BitacoraAgente();
        $this->anomaliaModel = new AnomaliaDetectada();
        $this->plantillaModel = new PlantillaDocumento();
        $this->dispositivoModel = new DispositivoBiometrico();
        
        $this->cargarConfiguracion();
    }

    private function cargarConfiguracion() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT clave, valor, tipo FROM configuraciones_agente");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->config = [];
        foreach ($configs as $c) {
            $valor = $c['valor'];
            settype($valor, $c['tipo']);
            $this->config[$c['clave']] = $valor;
        }
        
        $this->config += [
            'tolerancia_minutos' => 10,
            'retardo_menor_min' => 11,
            'retardo_mayor_min' => 21,
            'falta_minutos' => 31,
            'max_retardos_quincena' => 2,
            'notas_malas_oficio' => 1,
            'notas_malas_suspension' => 5,
            'dias_justificacion_retardo' => 15,
            'procesamiento_automatico' => true,
            'generar_documentos_auto' => false,
            'detectar_anomalias' => true
        ];
    }

    public function getConfig($clave, $default = null) {
        return $this->config[$clave] ?? $default;
    }

    public function setConfig($clave, $valor) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("UPDATE configuraciones_agente SET valor = ? WHERE clave = ?");
        $stmt->execute([$valor, $clave]);
        $this->config[$clave] = $valor;
    }

    public function procesarPeriodo($fecha_inicio, $fecha_fin, $area = null, $empleado_id = null) {
        $resultado = [
            'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
            'resumen' => [
                'empleados_procesados' => 0,
                'retardos' => 0,
                'faltas' => 0,
                'incidencias' => 0,
                'anomalias' => 0,
                'no_validadas' => 0
            ],
            'detalles' => [],
            'errors' => []
        ];

        $empleados = $this->empleadoModel->getAll();
        if ($area) {
            $empleados = array_filter($empleados, fn($e) => ($e['area'] ?? '') === $area);
        }
        if ($empleado_id) {
            $empleados = array_filter($empleados, fn($e) => (int)($e['id']) === (int)$empleado_id);
        }

        foreach ($empleados as $empleado) {
            try {
                $procesamiento = $this->procesarEmpleado($empleado, $fecha_inicio, $fecha_fin);
                $resultado['resumen']['empleados_procesados']++;
                $resultado['resumen']['retardos'] += $procesamiento['retardos'];
                $resultado['resumen']['faltas'] += $procesamiento['faltas'];
                $resultado['resumen']['anomalias'] += $procesamiento['anomalias'];
                $resultado['detalles'][] = $procesamiento;
            } catch (Exception $e) {
                $resultado['errors'][] = [
                    'empleado_id' => $empleado['id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        $resultado['resumen']['incidencias'] = $resultado['resumen']['retardos'] + $resultado['resumen']['faltas'];
        $resultado['resumen']['no_validadas'] = $this->contarNoValidadas($fecha_inicio, $fecha_fin);

        return $resultado;
    }

    private function procesarEmpleado($empleado, $fecha_inicio, $fecha_fin) {
        $empleado_id = $empleado['id'];
        $detalle = [
            'empleado_id' => $empleado_id,
            'nombre' => $empleado['nombre'] . ' ' . $empleado['apellido'],
            'retardos' => 0,
            'faltas' => 0,
            'anomalias' => 0,
            'incidencias' => []
        ];

        $asistencias = $this->asistenciaModel->getByEmpleado($empleado_id, $fecha_inicio, $fecha_fin);
        
        foreach ($asistencias as $asistencia) {
            $this->clasificarAsistencia($empleado, $asistencia, $detalle);
        }

        if ($this->config['detectar_anomalias']) {
            $this->detectarAnomalias($empleado, $fecha_inicio, $fecha_fin, $detalle);
        }

        return $detalle;
    }

    private function clasificarAsistencia($empleado, $asistencia, &$detalle) {
        $empleado_id = $empleado['id'];
        $fecha = $asistencia['fecha'];
        $hora_entrada = $asistencia['hora_entrada'];
        
        if (!$hora_entrada) return;

        $horario = $this->horarioModel->getHorarioPorFecha($empleado_id, $fecha);
        $hora_oficial = $horario['hora_entrada'] ?? '09:00:00';
        $tolerancia = $horario['tolerancia_minutos'] ?? $this->config['tolerancia_minutos'];

        $minutos_retardo = $this->calcularMinutosRetardo($hora_entrada, $hora_oficial);
        
        $clasificacion = $this->clasificarRetardo($minutos_retardo, $tolerancia);
        
        $datos_incidencia = [
            'empleado_id' => $empleado_id,
            'tipo_incidencia' => $clasificacion['tipo'],
            'incidencia_id' => $asistencia['id'],
            'incidencia_tipo' => 'asistencia',
            'clasificacion' => $clasificacion['clasificacion'],
            'decision' => $clasificacion['decision'],
            'justificada' => false,
            'validada_por_jefe' => null,
            'evidencia_json' => json_encode([
                'fecha' => $fecha,
                'hora_entrada' => $hora_entrada,
                'hora_oficial' => $hora_oficial,
                'minutos_retardo' => $minutos_retardo,
                'tolerancia' => $tolerancia,
                'dispositivo_id' => $asistencia['dispositivo_id']
            ])
        ];

        $this->bitacoraModel->registrar($datos_incidencia);

        if ($clasificacion['tipo'] === 'retardo_menor' || $clasificacion['tipo'] === 'retardo_mayor') {
            $detalle['retardos']++;
        } elseif ($clasificacion['tipo'] === 'falta') {
            $detalle['faltas']++;
        }

        $detalle['incidencias'][] = [
            'fecha' => $fecha,
            'hora' => $hora_entrada,
            'tipo' => $clasificacion['tipo'],
            'minutos' => $minutos_retardo
        ];
    }

    private function calcularMinutosRetardo($hora_entrada, $hora_oficial) {
        $entrada = strtotime($hora_entrada);
        $oficial = strtotime($hora_oficial);
        $diff = $entrada - $oficial;
        return $diff > 0 ? floor($diff / 60) : 0;
    }

    private function clasificarRetardo($minutos, $tolerancia) {
        if ($minutos <= $tolerancia) {
            return ['tipo' => 'puntual', 'clasificacion' => 'puntual', 'decision' => 'sin_accion'];
        }
        if ($minutos <= $this->config['retardo_menor_min']) {
            return ['tipo' => 'retardo_menor', 'clasificacion' => 'retardo', 'decision' => 'registrar'];
        }
        if ($minutos <= $this->config['retardo_mayor_min']) {
            return ['tipo' => 'retardo_mayor', 'clasificacion' => 'retardo', 'decision' => 'registrar'];
        }
        return ['tipo' => 'falta', 'clasificacion' => 'falta', 'decision' => 'registrar'];
    }

    private function detectarAnomalias($empleado, $fecha_inicio, $fecha_fin, &$detalle) {
        $empleado_id = $empleado['id'];
        $asistencias = $this->asistenciaModel->getByEmpleado($empleado_id, $fecha_inicio, $fecha_fin);
        
        $horario = $this->horarioModel->getHorarioPorFecha($empleado_id, $fecha_inicio);
        
        foreach ($asistencias as $asistencia) {
            if ($asistencia['hora_entrada']) {
                if ($this->esHoraAnomala($asistencia['hora_entrada'], $horario)) {
                    $this->anomaliaModel->registrar([
                        'empleado_id' => $empleado_id,
                        'tipo_anomalia' => 'registro_fuera_rango',
                        'descripcion' => 'Registro de entrada fuera del horario normal',
                        'datos_json' => json_encode([
                            'hora_registro' => $asistencia['hora_entrada'],
                            'hora_oficial' => $horario['hora_entrada'] ?? 'N/A',
                            'dispositivo' => $asistencia['dispositivo_id']
                        ])
                    ]);
                    $detalle['anomalias']++;
                }
            }
        }
    }

    private function esHoraAnomala($hora, $horario) {
        if (!$horario) return false;
        
        $hora_registro = strtotime($hora);
        $hora_min = strtotime('05:00:00');
        $hora_max = strtotime('22:00:00');
        
        return $hora_registro < $hora_min || $hora_registro > $hora_max;
    }

    private function contarNoValidadas($fecha_inicio, $fecha_fin) {
        $no_validadas = $this->bitacoraModel->getNoValidadas($fecha_inicio, $fecha_fin);
        return count($no_validadas);
    }

    public function getIncidenciasNoValidadas($fecha_inicio = null, $fecha_fin = null) {
        return $this->bitacoraModel->getNoValidadas($fecha_inicio, $fecha_fin);
    }

    public function getRetardosNoJustificados($fecha_inicio, $fecha_fin, $area = null) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT r.*, e.nombre, e.apellido, e.area, e.puesto, e.jefe_directo_id
                FROM retardos r
                JOIN empleados e ON r.empleado_id = e.id
                WHERE r.fecha BETWEEN ? AND ? AND r.justificado = 0";
        $params = [$fecha_inicio, $fecha_fin];

        if ($area) {
            $sql .= " AND e.area = ?";
            $params[] = $area;
        }

        $sql .= " ORDER BY r.fecha DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFaltasNoJustificadas($fecha_inicio, $fecha_fin, $area = null) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT a.*, e.nombre, e.apellido, e.area, e.puesto
                FROM asistencia a
                JOIN empleados e ON a.empleado_id = e.id
                WHERE a.fecha BETWEEN ? AND ? 
                AND a.tipo_asistencia = 'falta'";
        $params = [$fecha_inicio, $fecha_fin];

        if ($area) {
            $sql .= " AND e.area = ?";
            $params[] = $area;
        }

        $sql .= " ORDER BY a.fecha DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReporteGeneral($fecha_inicio, $fecha_fin, $area = null) {
        $reporte = [
            'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
            'retardos_no_justificados' => $this->getRetardosNoJustificados($fecha_inicio, $fecha_fin, $area),
            'faltas' => $this->getFaltasNoJustificadas($fecha_inicio, $fecha_fin, $area),
            'no_validadas' => $this->getIncidenciasNoValidadas($fecha_inicio, $fecha_fin),
            'bitacora' => $this->bitacoraModel->getByFechas($fecha_inicio, $fecha_fin),
            'anomalias' => $this->anomaliaModel->getNoEvaluadas($fecha_inicio, $fecha_fin),
            'metricas' => $this->calcularMetricas($fecha_inicio, $fecha_fin),
            'recomendaciones' => $this->generarRecomendaciones($fecha_inicio, $fecha_fin, $area)
        ];

        return $reporte;
    }

    private function generarRecomendaciones($fecha_inicio, $fecha_fin, $area = null) {
        $recomendaciones = [];
        
        $retardos = $this->getRetardosNoJustificados($fecha_inicio, $fecha_fin, $area);
        $faltas = $this->getFaltasNoJustificadas($fecha_inicio, $fecha_fin, $area);
        $noValidadas = $this->getIncidenciasNoValidadas($fecha_inicio, $fecha_fin);
        
        // Análisis por empleado
        $incidenciasPorEmpleado = [];
        foreach (array_merge($retardos, $faltas) as $incidencia) {
            $eid = $incidencia['empleado_id'];
            if (!isset($incidenciasPorEmpleado[$eid])) {
                $incidenciasPorEmpleado[$eid] = [
                    'empleado' => $incidencia['nombre'] . ' ' . $incidencia['apellido'],
                    'area' => $incidencia['area'] ?? 'N/A',
                    'retardos' => 0,
                    'faltas' => 0,
                    'total' => 0
                ];
            }
            if (isset($incidencia['minutos_retardo'])) {
                $incidenciasPorEmpleado[$eid]['retardos']++;
            } else {
                $incidenciasPorEmpleado[$eid]['faltas']++;
            }
            $incidenciasPorEmpleado[$eid]['total']++;
        }
        
        // Recomendaciones por empleado con más incidencias
        usort($incidenciasPorEmpleado, fn($a, $b) => $b['total'] <=> $a['total']);
        $topProblematicos = array_slice($incidenciasPorEmpleado, 0, 5);
        
        foreach ($topProblematicos as $emp) {
            if ($emp['total'] >= 3) {
                $recomendaciones[] = [
                    'tipo' => 'atencion',
                    'prioridad' => 'alta',
                    'empleado_id' => array_search($emp, $incidenciasPorEmpleado),
                    'mensaje' => "El empleado {$emp['empleado']} tiene {$emp['total']} incidencias en el período. Se recomienda revisión inmediata.",
                    'accion' => 'generar_oficio'
                ];
            } elseif ($emp['total'] >= 1) {
                $recomendaciones[] = [
                    'tipo' => 'seguimiento',
                    'prioridad' => 'media',
                    'empleado_id' => array_search($emp, $incidenciasPorEmpleado),
                    'mensaje' => "El empleado {$emp['empleado']} tiene {$emp['total']} incidencia(s). Monitorear.",
                    'accion' => 'monitorear'
                ];
            }
        }
        
        // Recomendaciones por incidencias no validadas
        if (count($noValidadas) > 0) {
            $recomendaciones[] = [
                'tipo' => 'validacion',
                'prioridad' => 'alta',
                'mensaje' => "Hay " . count($noValidadas) . " incidencias sin validar por jefe. Se requiere revisión.",
                'accion' => 'notificar_jefes'
            ];
        }
        
        // Recomendación de sanciones automáticas
        $totalRetardos = count($retardos);
        if ($totalRetardos >= 5) {
            $recomendaciones[] = [
                'tipo' => 'sancion',
                'prioridad' => 'alta',
                'mensaje' => "Se detectaron $totalRetardos retardos no justificados. Evaluar sanciones según normas.",
                'accion' => 'evaluar_sanciones'
            ];
        }
        
        return [
            'total' => count($recomendaciones),
            'items' => $recomendaciones,
            'empleados_criticos' => $topProblematicos
        ];
    }

    private function calcularMetricas($fecha_inicio, $fecha_fin) {
        $retardos = $this->getRetardosNoJustificados($fecha_inicio, $fecha_fin);
        $faltas = $this->getFaltasNoJustificadas($fecha_inicio, $fecha_fin);
        $no_validadas = $this->getIncidenciasNoValidadas($fecha_inicio, $fecha_fin);

        $porEmpleado = [];
        foreach (array_merge($retardos, $faltas) as $r) {
            $eid = $r['empleado_id'];
            if (!isset($porEmpleado[$eid])) {
                $porEmpleado[$eid] = ['nombre' => $r['nombre'] . ' ' . $r['apellido'], 'retardos' => 0, 'faltas' => 0];
            }
            if (isset($r['minutos_retardo'])) {
                $porEmpleado[$eid]['retardos']++;
            } else {
                $porEmpleado[$eid]['faltas']++;
            }
        }

        return [
            'total_retardos' => count($retardos),
            'total_faltas' => count($faltas),
            'total_no_validadas' => count($no_validadas),
            'por_empleado' => array_values($porEmpleado)
        ];
    }

    public function evaluarSanciones($empleado_id, $mes, $anio) {
        $retardoModel = new Retardo();
        return $retardoModel->evaluarYSancionar($empleado_id, $mes, $anio);
    }

    public function getJustificacionesPorEmpleado($empleado_id = null, $fecha_inicio = null, $fecha_fin = null, $origen = 'todos') {
        $pdo = $this->db->getConnection();
        
        if ($origen === 'asistencia') {
            return $this->getJustificacionesFromAsistencia($empleado_id, $fecha_inicio, $fecha_fin);
        } elseif ($origen === 'justificaciones') {
            return $this->getJustificacionesFromTable($empleado_id, $fecha_inicio, $fecha_fin);
        }
        
        $resultados = [
            'justificaciones' => $this->getJustificacionesFromTable($empleado_id, $fecha_inicio, $fecha_fin),
            'asistencia' => $this->getJustificacionesFromAsistencia($empleado_id, $fecha_inicio, $fecha_fin)
        ];
        
        return $resultados;
    }

    private function getJustificacionesFromTable($empleado_id, $fecha_inicio, $fecha_fin) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT j.id, j.empleado_id, j.tipo_justificacion, j.motivo, 
                       j.fecha_inicio, j.fecha_fin, j.estatus, j.created_at,
                       e.nombre, e.apellido, e.area, e.puesto,
                       'justificaciones' as origen
                FROM justificaciones j
                JOIN empleados e ON j.empleado_id = e.id
                WHERE 1=1";
        $params = [];

        if ($empleado_id) {
            $sql .= " AND j.empleado_id = ?";
            $params[] = $empleado_id;
        }
        if ($fecha_inicio) {
            $sql .= " AND j.fecha_inicio >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND j.fecha_fin <= ?";
            $params[] = $fecha_fin;
        }

        $sql .= " ORDER BY j.fecha_inicio DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getJustificacionesFromAsistencia($empleado_id, $fecha_inicio, $fecha_fin) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT a.id, a.empleado_id, tj.nombre as tipo_justificacion, tj.tipo_incidencia,
                       a.fecha as fecha_inicio, a.fecha as fecha_fin, 
                       CASE WHEN a.estado_validacion = 'aprobada' THEN 'aprobada' ELSE 'pendiente' END as estatus,
                       a.created_at, e.nombre, e.apellido, e.area, e.puesto,
                       'asistencia' as origen
                FROM asistencia a
                JOIN empleados e ON a.empleado_id = e.id
                LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
                WHERE a.tipo_justificacion_id IS NOT NULL";
        $params = [];

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

        $sql .= " ORDER BY a.fecha DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstadisticasPorTipoJustificacion($fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT 
                    tj.id,
                    tj.nombre as tipo_nombre,
                    tj.descripcion,
                    tj.tipo_incidencia,
                    COUNT(j.id) as total_solicitudes,
                    SUM(CASE WHEN j.estatus = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN j.estatus = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                    SUM(CASE WHEN j.estatus = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    DATEDIFF(MAX(j.fecha_fin), MIN(j.fecha_inicio)) + 1 as dias_maximos,
                    AVG(DATEDIFF(j.fecha_fin, j.fecha_inicio) + 1) as promedio_dias
                FROM tipos_justificacion tj
                LEFT JOIN justificaciones j ON tj.id = j.tipo_justificacion";
        $params = [];

        if ($fecha_inicio) {
            $sql .= " AND j.fecha_inicio >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND j.fecha_fin <= ?";
            $params[] = $fecha_fin;
        }

        $sql .= " GROUP BY tj.id ORDER BY total_solicitudes DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getJustificacionesPorEmpleadoResumen($fecha_inicio = null, $fecha_fin = null, $area = null) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT 
                    e.id as empleado_id,
                    e.nombre,
                    e.apellido,
                    e.area,
                    e.puesto,
                    COUNT(j.id) as total_justificaciones,
                    SUM(CASE WHEN j.estatus = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN j.estatus = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                    SUM(CASE WHEN j.estatus = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    GROUP_CONCAT(DISTINCT tj.nombre SEPARATOR ', ') as tipos_utilizados
                FROM empleados e
                LEFT JOIN justificaciones j ON e.id = j.empleado_id
                LEFT JOIN tipos_justificacion tj ON j.tipo_justificacion = tj.id
                WHERE j.id IS NOT NULL";
        $params = [];

        if ($fecha_inicio) {
            $sql .= " AND j.fecha_inicio >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND j.fecha_fin <= ?";
            $params[] = $fecha_fin;
        }
        if ($area) {
            $sql .= " AND e.area = ?";
            $params[] = $area;
        }

        $sql .= " GROUP BY e.id ORDER BY total_justificaciones DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function analizarPatronesJustificacion($fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT 
                    e.id as empleado_id,
                    e.nombre,
                    e.apellido,
                    e.area,
                    e.puesto,
                    tj.id as tipo_id,
                    tj.nombre as tipo_justificacion,
                    tj.tipo_incidencia,
                    COUNT(j.id) as cantidad,
                    AVG(DATEDIFF(j.fecha_fin, j.fecha_inicio) + 1) as promedio_dias,
                    MAX(j.fecha_inicio) as ultima_solicitud,
                    GROUP_CONCAT(DISTINCT DATE_FORMAT(j.fecha_inicio, '%Y-%m') SEPARATOR ', ') as meses_solicitados
                FROM empleados e
                JOIN justificaciones j ON e.id = j.empleado_id
                JOIN tipos_justificacion tj ON j.tipo_justificacion = tj.id
                WHERE j.estatus = 'aprobada'";
        $params = [];

        if ($fecha_inicio) {
            $sql .= " AND j.fecha_inicio >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND j.fecha_fin <= ?";
            $params[] = $fecha_fin;
        }

        $sql .= " GROUP BY e.id, tj.id ORDER BY cantidad DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function analisisPredictivoJustificaciones($empleado_id = null, $meses_historico = 6) {
        $pdo = $this->db->getConnection();
        
        $fecha_fin = date('Y-m-d');
        $fecha_inicio = date('Y-m-d', strtotime("-{$meses_historico} months"));

        $sql = "SELECT 
                    e.id as empleado_id,
                    e.nombre,
                    e.apellido,
                    e.area,
                    e.puesto,
                    tj.id as tipo_id,
                    tj.nombre as tipo_justificacion,
                    tj.tipo_incidencia,
                    COUNT(j.id) as total_historial,
                    SUM(CASE WHEN j.estatus = 'aprobada' THEN 1 ELSE 0 END) as total_aprobadas,
                    AVG(DATEDIFF(j.fecha_fin, j.fecha_inicio) + 1) as promedio_dias_por_solicitud,
                    MIN(j.fecha_inicio) as primera_solicitud,
                    MAX(j.fecha_inicio) as ultima_solicitud,
                    COUNT(DISTINCT MONTH(j.fecha_inicio)) as meses_con_solicitudes,
                    GROUP_CONCAT(DISTINCT MONTH(j.fecha_inicio) ORDER BY MONTH(j.fecha_inicio) SEPARATOR ', ') as meses
                FROM empleados e
                JOIN justificaciones j ON e.id = j.empleado_id
                JOIN tipos_justificacion tj ON j.tipo_justificacion = tj.id
                WHERE j.fecha_inicio BETWEEN ? AND ?";
        $params = [$fecha_inicio, $fecha_fin];

        if ($empleado_id) {
            $sql .= " AND e.id = ?";
            $params[] = $empleado_id;
        }

        $sql .= " GROUP BY e.id, tj.id ORDER BY total_historial DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $predicciones = [];
        foreach ($datos as $dato) {
            $prediccion = $this->calcularPrediccion($dato, $meses_historico);
            $predicciones[] = array_merge($dato, $prediccion);
        }

        return [
            'periodo_analizado' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
            'total_registros' => count($predicciones),
            'predicciones' => $predicciones,
            'resumen' => $this->generarResumenPredicciones($predicciones)
        ];
    }

    private function calcularPrediccion($dato, $meses_historico) {
        $historial = (int)$dato['total_historial'];
        $meses_activos = (int)$dato['meses_con_solicitudes'];
        $promedio_dias = (float)$dato['promedio_dias_por_solicitud'];
        $aprobadas = (int)$dato['total_aprobadas'];
        
        $tasa_aprobacion = $historial > 0 ? ($aprobadas / $historial) * 100 : 0;
        $frecuencia_mensual = $meses_activos > 0 ? $historial / min($meses_activos, $meses_historico) : 0;
        
        $nivel_riesgo = 'bajo';
        $pronostico = 'Sin patrones detectados';
        $recomendacion = 'Monitoreo normal';

        if ($historial >= 5 && $frecuencia_mensual >= 1) {
            $nivel_riesgo = 'alto';
            $pronostico = 'Alta probabilidad de solicitudes recurrentes';
            $recomendacion = 'Revisar patrones de asistencia - posible abuso de justificaciones';
        } elseif ($historial >= 3 && $frecuencia_mensual >= 0.5) {
            $nivel_riesgo = 'medio';
            $pronostico = 'Probabilidad moderada de nuevas solicitudes';
            $recomendacion = 'Monitorear proximidad de solicitudes';
        } elseif ($historial >= 1 && $tasa_aprobacion >= 80) {
            $nivel_riesgo = 'bajo';
            $pronostico = 'Uso normal de justificaciones';
            $recomendacion = 'Continuar con política actual';
        }

        return [
            'nivel_riesgo' => $nivel_riesgo,
            'pronostico' => $pronostico,
            'frecuencia_mensual' => round($frecuencia_mensual, 2),
            'tasa_aprobacion' => round($tasa_aprobacion, 2),
            'promedio_dias' => round($promedio_dias, 1),
            'recomendacion' => $recomendacion
        ];
    }

    private function generarResumenPredicciones($predicciones) {
        $altos = array_filter($predicciones, fn($p) => $p['nivel_riesgo'] === 'alto');
        $medios = array_filter($predicciones, fn($p) => $p['nivel_riesgo'] === 'medio');
        $bajos = array_filter($predicciones, fn($p) => $p['nivel_riesgo'] === 'bajo');

        $por_tipo = [];
        foreach ($predicciones as $p) {
            $tipo = $p['tipo_justificacion'];
            if (!isset($por_tipo[$tipo])) {
                $por_tipo[$tipo] = 0;
            }
            $por_tipo[$tipo]++;
        }

        arsort($por_tipo);
        $top_tipos = array_slice($por_tipo, 0, 5, true);

        return [
            'empleados_alto_riesgo' => count($altos),
            'empleados_medio_riesgo' => count($medios),
            'empleados_bajo_riesgo' => count($bajos),
            'tipos_mas_utilizados' => $top_tipos,
            'accion_recomendada' => count($altos) > 0 
                ? 'Revisar empleados con nivel de riesgo alto' 
                : 'Monitoreo normal'
        ];
    }

    public function getEstadisticasCompletasJustificaciones($fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT 
                    tj.id,
                    tj.nombre as tipo_justificacion,
                    tj.tipo_incidencia,
                    COUNT(a.id) as total_registros,
                    COUNT(DISTINCT a.empleado_id) as empleados_distintos,
                    COUNT(DISTINCT DATE(a.fecha)) as dias_diferentes,
                    MIN(a.fecha) as primera_ocurrencia,
                    MAX(a.fecha) as ultima_ocurrencia
                FROM tipos_justificacion tj
                LEFT JOIN asistencia a ON a.tipo_justificacion_id = tj.id";
        $params = [];

        if ($fecha_inicio) {
            $sql .= " AND a.fecha >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND a.fecha <= ?";
            $params[] = $fecha_fin;
        }

        $sql .= " GROUP BY tj.id ORDER BY total_registros DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getJustificacionesPorTipo($tipo_incidencia = null, $fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT 
                    a.id,
                    a.empleado_id,
                    e.nombre,
                    e.apellido,
                    e.area,
                    e.puesto,
                    a.fecha,
                    a.tipo_asistencia,
                    tj.nombre as tipo_justificacion,
                    tj.tipo_incidencia,
                    a.hora_entrada,
                    a.hora_salida,
                    a.estado_validacion,
                    a.validado_por_jefe
                FROM asistencia a
                JOIN empleados e ON a.empleado_id = e.id
                LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
                WHERE a.tipo_justificacion_id IS NOT NULL";
        $params = [];

        if ($tipo_incidencia) {
            $sql .= " AND tj.tipo_incidencia = ?";
            $params[] = $tipo_incidencia;
        }
        if ($fecha_inicio) {
            $sql .= " AND a.fecha >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND a.fecha <= ?";
            $params[] = $fecha_fin;
        }

        $sql .= " ORDER BY a.fecha DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResumenPorTipoJustificacion($fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        
        $tipos_incidencia = [
            'retardo' => 'Retardos',
            'comision_entrada' => 'Comisión Entrada',
            'comision_salida' => 'Comisión Salida',
            'comision_todo_dia' => 'Comisión Todo el Día',
            'dia_economico' => 'Día Económico',
            'licencia_medica' => 'Licencia Médica',
            'vacaciones' => 'Vacaciones',
            'cuidados_maternos' => 'Cuidados Maternos',
            'cuidados_paternos' => 'Cuidados Paternos',
            'falta' => 'Falta',
            'constancia_tiempo' => 'Constancia de Tiempo',
            'PDSEP-SNTE' => 'Programa Deportivo SEP-SNTE',
            'CLIDDA' => 'CLIDDA',
            'EYR' => 'Estimulos y Recompensas',
            'DE' => 'Desalojo de Edificio',
            'F' => 'Fumigación'
        ];

        $resultado = [];
        
        foreach ($tipos_incidencia as $tipo => $nombre) {
            $sql = "SELECT 
                        COUNT(a.id) as total,
                        COUNT(DISTINCT a.empleado_id) as empleados,
                        COUNT(DISTINCT DATE(a.fecha)) as dias
                    FROM asistencia a
                    INNER JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
                    WHERE tj.tipo_incidencia = ?";
            $params = [$tipo];

            if ($fecha_inicio) {
                $sql .= " AND fecha >= ?";
                $params[] = $fecha_inicio;
            }
            if ($fecha_fin) {
                $sql .= " AND fecha <= ?";
                $params[] = $fecha_fin;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            $resultado[] = [
                'tipo_incidencia' => $tipo,
                'nombre' => $nombre,
                'total_registros' => (int)$data['total'],
                'empleados_distintos' => (int)$data['empleados'],
                'dias_diferentes' => (int)$data['dias']
            ];
        }

        usort($resultado, fn($a, $b) => $b['total_registros'] <=> $a['total_registros']);
        
        return $resultado;
    }

    public function analisisPredictivoCompleto($fecha_inicio = null, $fecha_fin = null, $area = null) {
        $pdo = $this->db->getConnection();
        
        if (!$fecha_inicio) $fecha_inicio = date('Y-01-01');
        if (!$fecha_fin) $fecha_fin = date('Y-m-d');

        $sql = "SELECT 
                    e.id as empleado_id,
                    e.nombre,
                    e.apellido,
                    e.area,
                    e.puesto,
                    tj.id as tipo_id,
                    tj.nombre as tipo_justificacion,
                    tj.tipo_incidencia,
                    COUNT(a.id) as total_ocurrencias,
                    DATEDIFF(MAX(a.fecha), MIN(a.fecha)) as dias_entre_primera_ultima,
                    GROUP_CONCAT(DISTINCT MONTH(a.fecha) ORDER BY MONTH(a.fecha)) as meses,
                    COUNT(DISTINCT MONTH(a.fecha)) as meses_con_ocurrencias,
                    MIN(a.fecha) as primera_ocurrencia,
                    MAX(a.fecha) as ultima_ocurrencia
                FROM empleados e
                JOIN asistencia a ON e.id = a.empleado_id
                JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
                WHERE a.fecha BETWEEN ? AND ?";
        $params = [$fecha_inicio, $fecha_fin];

        if ($area) {
            $sql .= " AND e.area = ?";
            $params[] = $area;
        }

        $sql .= " GROUP BY e.id, tj.id ORDER BY total_ocurrencias DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $predicciones = [];
        foreach ($datos as $dato) {
            $prediccion = $this->calcularRiesgoCompleto($dato, $fecha_inicio, $fecha_fin);
            $predicciones[] = array_merge($dato, $prediccion);
        }

        return [
            'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin],
            'total_analizados' => count($predicciones),
            'empleados_riesgo_alto' => array_filter($predicciones, fn($p) => $p['nivel_riesgo'] === 'alto'),
            'empleados_riesgo_medio' => array_filter($predicciones, fn($p) => $p['nivel_riesgo'] === 'medio'),
            'empleados_riesgo_bajo' => array_filter($predicciones, fn($p) => $p['nivel_riesgo'] === 'bajo'),
            'detalles' => $predicciones,
            'resumen_tipos' => $this->resumenPorTipo($predicciones)
        ];
    }

    private function calcularRiesgoCompleto($dato, $fecha_inicio, $fecha_fin) {
        $ocurrencias = (int)$dato['total_ocurrencias'];
        $meses_activos = (int)$dato['meses_con_ocurrencias'];
        $tipo = $dato['tipo_incidencia'];
        
        $dias_total = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / (60 * 60 * 24);
        $frecuencia = $meses_activos > 0 ? $ocurrencias / $meses_activos : 0;
        
        $puntos_riesgo = 0;
        $factores = [];

        if ($ocurrencias >= 5) {
            $puntos_riesgo += 30;
            $factores[] = 'Alto volumen de justificaciones';
        }
        if ($frecuencia >= 1.5) {
            $puntos_riesgo += 25;
            $factores[] = 'Alta frecuencia mensual';
        }
        if (in_array($tipo, ['vacaciones', 'licencia_medica', 'dia_economico'])) {
            $puntos_riesgo += 15;
            $factores[] = 'Tipo de justificación de mayor ausencia';
        }
        if ($ocurrencias >= 3 && $meses_activos == 1) {
            $puntos_riesgo += 20;
            $factores[] = 'Múltiples justificaciones en un mismo mes';
        }

        if ($puntos_riesgo >= 50) {
            $nivel = 'alto';
            $pronostico = 'Alta probabilidad de justificación recurrente';
            $accion = 'Revisión de historial de justificaciones';
        } elseif ($puntos_riesgo >= 25) {
            $nivel = 'medio';
            $pronostico = 'Probabilidad moderada de nuevas justificaciones';
            $accion = 'Monitoreo continuo';
        } else {
            $nivel = 'bajo';
            $pronostico = 'Patrón normal de justificaciones';
            $accion = 'Seguimiento normal';
        }

        return [
            'nivel_riesgo' => $nivel,
            'pronostico' => $pronostico,
            'accion_recomendada' => $accion,
            'puntos_riesgo' => $puntos_riesgo,
            'factores_riesgo' => $factores,
            'frecuencia_mensual' => round($frecuencia, 2)
        ];
    }

    private function resumenPorTipo($predicciones) {
        $resumen = [];
        foreach ($predicciones as $p) {
            $tipo = $p['tipo_justificacion'];
            if (!isset($resumen[$tipo])) {
                $resumen[$tipo] = ['total' => 0, 'alto' => 0, 'medio' => 0, 'bajo' => 0];
            }
            $resumen[$tipo]['total']++;
            $resumen[$tipo][$p['nivel_riesgo']]++;
        }
        return $resumen;
    }

    public function getEmpleadosConMasJustificaciones($fecha_inicio = null, $fecha_fin = null, $limite = 10) {
        $pdo = $this->db->getConnection();
        
        $sql = "SELECT 
                    e.id as empleado_id,
                    e.nombre,
                    e.apellido,
                    e.area,
                    e.puesto,
                    COUNT(a.id) as total_justificaciones,
                    COUNT(DISTINCT tj.id) as tipos_diferentes,
                    COUNT(DISTINCT a.fecha) as dias_con_justificacion,
                    GROUP_CONCAT(DISTINCT tj.nombre SEPARATOR ', ') as tipos_utilizados
                FROM empleados e
                JOIN asistencia a ON e.id = a.empleado_id
                LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
                WHERE a.tipo_justificacion_id IS NOT NULL";
        $params = [];

        if ($fecha_inicio) {
            $sql .= " AND a.fecha >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND a.fecha <= ?";
            $params[] = $fecha_fin;
        }

        $sql .= " GROUP BY e.id ORDER BY total_justificaciones DESC LIMIT ?";
        $params[] = $limite;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
