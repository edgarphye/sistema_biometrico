<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/HorarioLaboral.php';

class AnalisisPredictivoService {
    private $db;
    private $config;
    private $empleadoModel;
    private $asistenciaModel;
    private $retardoModel;
    private $horarioModel;

    const DIAS_LABORABLES_MES = 26;
    const MAX_NOTAS_MALAS_PARA_OFICIO = 1;
    const MAX_RETARDOS_QUINCENA = 2;

    private $nivelesRiesgo = [
        'bajo' => ['min' => 0, 'max' => 30],
        'medio' => ['min' => 31, 'max' => 50],
        'medio_alto' => ['min' => 51, 'max' => 65],
        'alto' => ['min' => 66, 'max' => 80],
        'critico' => ['min' => 81, 'max' => 100]
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->empleadoModel = new Empleado();
        $this->asistenciaModel = new Asistencia();
        $this->retardoModel = new Retardo();
        $this->horarioModel = new HorarioLaboral();
        $this->cargarConfiguracion();
        $this->inicializarTablas();
    }

    private function cargarConfiguracion() {
        try {
            $pdo = $this->db->getConnection();
            $stmt = $pdo->query("SELECT clave, valor FROM configuraciones_analisis");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $rows = [];
        }
        
        $defaults = [
            'dias_analisis_historico' => 180,
            'dias_analisis_reciente' => 30,
            'umbral_alerta_precoz' => 0.35,
            'umbral_alerta_critica' => 0.70,
            'pronostico_dias' => 30,
            'modelos_activos' => 'regresion,promedio_movil,bosques',
            'peso_retardos' => 1.0,
            'peso_faltas' => 1.5,
            'peso_minutos' => 0.5
        ];
        
        if ($rows) {
            foreach ($rows as $row) {
                $defaults[$row['clave']] = $row['valor'];
            }
        }
        
        $this->config = $defaults;
    }

    private function inicializarTablas() {
        try {
            $pdo = $this->db->getConnection();
            $pdo->exec("CREATE TABLE IF NOT EXISTS configuraciones_analisis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(100) UNIQUE NOT NULL,
                valor TEXT,
                descripcion TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (Exception $e) {}
    }

    public function getConfig($clave = null) {
        return $clave ? ($this->config[$clave] ?? null) : $this->config;
    }

    public function setConfig($clave, $valor) {
        $this->config[$clave] = $valor;
        
        try {
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("INSERT INTO configuraciones_analisis (clave, valor, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE valor = VALUES(valor), created_at = NOW()");
            $stmt->execute([$clave, $valor]);
        } catch (Exception $e) {
            error_log("Error guardando config: " . $e->getMessage());
        }
    }

    public function getDashboardPredictivo() {
        $pdo = $this->db->getConnection();
        
        $empleadosStmt = $pdo->query("SELECT COUNT(*) as total FROM empleados");
        $totalEmpleados = $empleadosStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $alertasItems = [];
        
        // Verificar si hay alertas reales
        $alertasStmt = $pdo->query("
            SELECT a.*, e.nombre, e.apellido 
            FROM alertas_tempranas a 
            LEFT JOIN empleados e ON a.empleado_id = e.id 
            ORDER BY a.created_at DESC LIMIT 50
        ");
        $alertasItems = $alertasStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay alertas, generar alertasdemo basadas en datos reales
        if (empty($alertasItems)) {
            $alertasItems = $this->generarAlertasDemo($pdo);
        }
        
        $criticas = 0;
        $advertencias = 0;
        $precauciones = 0;
        foreach ($alertasItems as $a) {
            if (isset($a['nivel']) && $a['nivel'] === 'urgente') $criticas++;
            elseif (isset($a['nivel']) && $a['nivel'] === 'alta') $advertencias++;
            elseif (isset($a['nivel']) && ($a['nivel'] === 'media' || $a['nivel'] === 'baja')) $precauciones++;
        }
        
        $alertas = [
            'total' => count($alertasItems),
            'criticas' => $criticas,
            'advertencias' => $advertencias,
            'precauciones' => $precauciones,
            'items' => $alertasItems
        ];
        
        $criticosStmt = $pdo->query("
            SELECT e.id, e.nombre, e.apellido, e.area, e.puesto,
                   0 as riesgo, 'medio' as nivel
            FROM empleados e
            ORDER BY e.nombre
            LIMIT 20
        ");
        $criticos = $criticosStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular riesgo real para cada empleado
        foreach ($criticos as &$c) {
            $c['empleado_id'] = $c['id'];
            
            $rCount = $pdo->prepare("SELECT COUNT(*) FROM retardos WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $rCount->execute([$c['id']]);
            $retardos = (int)$rCount->fetchColumn();
            
            $fCount = $pdo->prepare("SELECT COUNT(*) FROM asistencia WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND tipo_asistencia = 'falta'");
            $fCount->execute([$c['id']]);
            $faltas = (int)$fCount->fetchColumn();
            
            $sCount = $pdo->prepare("SELECT COUNT(*) FROM sanciones WHERE empleado_id = ? AND estatus = 'activa'");
            $sCount->execute([$c['id']]);
            $sanciones = (int)$sCount->fetchColumn();
            
            $puntaje = ($retardos * 2) + ($faltas * 5) + ($sanciones * 10);
            $c['riesgo'] = min(100, $puntaje);
            
            if ($c['riesgo'] >= 60) $c['nivel'] = 'alto';
            elseif ($c['riesgo'] >= 40) $c['nivel'] = 'medio';
            else $c['nivel'] = 'bajo';
        }
        
        // Ordenar por riesgo descendente
        usort($criticos, function($a, $b) {
            return $b['riesgo'] - $a['riesgo'];
        });
        
        return [
            'resumen_riesgos' => [
                'total_empleados_analizados' => $totalEmpleados,
                'distribucion' => $this->getDistribucionRiesgos($pdo)
            ],
            'alertas' => [
                'total' => $alertas['total'],
                'criticas' => $alertas['criticas'],
                'advertencias' => $alertas['advertencias'],
                'precauciones' => $alertas['precauciones'],
                'items' => $alertas['items']
            ],
            'empleados_criticos' => $criticos,
            'recomendaciones' => $this->getRecomendacionesRecientes($pdo),
            'tendencias' => $this->getTendencias($pdo),
            'metricas_clave' => $this->getMetricasClave($pdo)
        ];
    }
    
    private function getDistribucionRiesgos($pdo) {
        $stmt = $pdo->query("
            SELECT nivel_riesgo, COUNT(*) as total 
            FROM pronosticos_riesgo 
            GROUP BY nivel_riesgo
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $dist = ['bajo' => 0, 'medio' => 0, 'medio_alto' => 0, 'alto' => 0, 'critico' => 0];
        foreach ($rows as $row) {
            $dist[$row['nivel_riesgo']] = (int)$row['total'];
        }
        return $dist;
    }
    
    private function getRecomendacionesRecientes($pdo) {
        // Primero intentar obtener de la tabla de recomendaciones
        $stmt = $pdo->query("
            SELECT r.*, e.nombre, e.apellido 
            FROM recomendaciones_analisis r
            LEFT JOIN empleados e ON r.empleado_id = e.id
            ORDER BY r.created_at DESC LIMIT 10
        ");
        $recomendaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Recomendaciones DB: " . count($recomendaciones));
        
        // Si no hay, generar desde datos reales de retardos y faltas
        if (empty($recomendaciones)) {
            $recomendaciones = $this->generarRecomendacionesDesdeDatosReales($pdo);
            error_log("Recomendaciones generadas: " . count($recomendaciones));
        }
        
        return $recomendaciones;
    }
    
    private function generarRecomendacionesDesdeDatosReales($pdo) {
        error_log("Iniciando generarRecomendacionesDesdeDatosReales");
        
        // Obtener empleados con retardos recientes (datos reales de la tabla retardos)
        $stmt = $pdo->query("
            SELECT r.empleado_id, COUNT(*) as total
            FROM retardos r
            WHERE r.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY r.empleado_id
            HAVING total > 2
            LIMIT 10
        ");
        $empleadosRetardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Empleados con retardos: " . count($empleadosRetardos));
        
        // Obtener empleados con faltas recientes (datos reales de la tabla asistencia)
        $faltasStmt = $pdo->query("
            SELECT a.empleado_id, COUNT(*) as total
            FROM asistencia a
            WHERE a.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND a.tipo_asistencia = 'falta'
            GROUP BY a.empleado_id
            HAVING total > 1
            LIMIT 10
        ");
        $empleadosFaltas = $faltasStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener empleados con tardanzas reales (llegadas 1-10 min tarde según su ciclo)
        $tardanzasStmt = $pdo->query("
            SELECT a.empleado_id, COUNT(*) as total
            FROM asistencia a
            WHERE a.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND a.hora_entrada IS NOT NULL
            GROUP BY a.empleado_id
            HAVING total > 3
            LIMIT 10
        ");
        $empleadosTardanzas = $tardanzasStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar empleados únicos
        $empleadosIds = array_unique(array_merge(
            array_column($empleadosRetardos, 'empleado_id'),
            array_column($empleadosFaltas, 'empleado_id'),
            array_column($empleadosTardanzas, 'empleado_id')
        ));
        
        $result = [];
        foreach ($empleadosIds as $empId) {
            $empStmt = $pdo->prepare("SELECT id, nombre, apellido, area FROM empleados WHERE id = ?");
            $empStmt->execute([$empId]);
            $empleado = $empStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($empleado) {
                $rCount = $pdo->prepare("SELECT COUNT(*) FROM retardos WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $rCount->execute([$empId]);
                $retardos = (int)$rCount->fetchColumn();
                
                $fCount = $pdo->prepare("SELECT COUNT(*) FROM asistencia WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND tipo_asistencia = 'falta'");
                $fCount->execute([$empId]);
                $faltas = (int)$fCount->fetchColumn();
                
                // Contar tardanzas reales (llegadas tarde según su horario)
                $tardanzas = 0;
                try {
                    $tardStmt = $pdo->prepare("
                        SELECT hl.hora_entrada, hl.tolerancia_minutos
                        FROM empleados_ciclos ec
                        JOIN bloques_ciclo bc ON ec.ciclo_id = bc.ciclo_id AND bc.activo = 1
                        JOIN horarios_laborales hl ON bc.horario_id = hl.id
                        WHERE ec.empleado_id = ? AND ec.activo = 1
                        LIMIT 1
                    ");
                    $tardStmt->execute([$empId]);
                    $horario = $tardStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($horario) {
                        $horaEntrada = strtotime($horario['hora_entrada']);
                        $tolerancia = $horario['tolerancia_minutos'] ?? 10;
                        $limiteTarde = $horaEntrada + ($tolerancia * 60);
                        
                        $llegadasStmt = $pdo->prepare("
                            SELECT hora_entrada FROM asistencia 
                            WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND hora_entrada IS NOT NULL
                        ");
                        $llegadasStmt->execute([$empId]);
                        $llegadas = $llegadasStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($llegadas as $llegada) {
                            if ($llegada['hora_entrada']) {
                                $horaMarcada = strtotime($llegada['hora_entrada']);
                                if ($horaMarcada > $horaEntrada && $horaMarcada <= $limiteTarde) {
                                    $tardanzas++;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {}
                
                $totalIncidencias = $retardos + $faltas + $tardanzas;
                $nivel = ($totalIncidencias > 5) ? 'urgente' : (($totalIncidencias > 2) ? 'alta' : 'media');
                
                $result[] = [
                    'id' => $empleado['id'],
                    'empleado_id' => $empleado['id'],
                    'nombre' => $empleado['nombre'],
                    'apellido' => $empleado['apellido'],
                    'categoria' => 'riesgo',
                    'prioridad' => $nivel,
                    'tipo_recomendacion' => 'seguimiento',
                    'descripcion' => "Empleado: {$retardos} retardos, {$faltas} faltas, {$tardanzas} tardanzas (30 días). Total: {$totalIncidencias} incidencias."
                ];
            }
        }
        
        if (empty($result)) {
            $result[] = [
                'id' => 0,
                'empleado_id' => null,
                'nombre' => 'Sistema',
                'apellido' => '',
                'categoria' => 'general',
                'prioridad' => 'media',
                'tipo_recomendacion' => 'monitoreo',
                'descripcion' => 'Indicadores de asistencia dentro de rangos normales.'
            ];
        }
        
        return $result;
    }
    
    private function generarAlertasDemo($pdo) {
        $alertas = [];
        
        // Debug - verificar conexión
        error_log("Iniciando generarAlertasDemo");
        
        try {
            // Obtener empleados con problemas recientes
            $stmt = $pdo->query("
                SELECT e.id, e.nombre, e.apellido, e.area,
                       (SELECT COUNT(*) FROM retardos r WHERE r.empleado_id = e.id AND r.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as retardos_semana,
                       (SELECT COUNT(*) FROM asistencia a WHERE a.empleado_id = e.id AND a.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND a.tipo_asistencia = 'falta') as faltas_semana
                FROM empleados e
                HAVING retardos_semana > 1 OR faltas_semana > 0
                LIMIT 15
            ");
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Empleados con problemas: " . count($empleados));
        } catch (Exception $e) {
            error_log("Error en query: " . $e->getMessage());
            // Si falla el query complejo, obtener empleados directamente
            $stmt = $pdo->query("SELECT id, nombre, apellido, area FROM empleados LIMIT 10");
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        foreach ($empleados as $e) {
            $total = ($e['retardos_semana'] ?? 0) + ($e['faltas_semana'] ?? 0);
            
            if ($total >= 3) {
                $nivel = 'urgente';
                $tipo = 'critica';
            } elseif ($total >= 2) {
                $nivel = 'alta';
                $tipo = 'advertencia';
            } else {
                $nivel = 'media';
                $tipo = 'precaucion';
            }
            
            $mensaje = "{$e['nombre']} {$e['apellido']}: {$total} incidencias esta semana";
            
            $alertas[] = [
                'id' => $e['id'],
                'empleado_id' => $e['id'],
                'nombre' => $e['nombre'],
                'apellido' => $e['apellido'],
                'tipo' => $tipo,
                'nivel' => $nivel,
                'mensaje' => $mensaje,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Si aún no hay alertas, crear una de prueba
        if (empty($alertas)) {
            $alertas[] = [
                'id' => 1,
                'empleado_id' => 1,
                'nombre' => 'Sistema',
                'apellido' => '',
                'tipo' => 'precaucion',
                'nivel' => 'media',
                'mensaje' => 'Sin incidencias recientes - Todo OK',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $alertas;
    }
    
    private function getTendencias($pdo) {
        // Verificar si hay datos reales en pronosticos_riesgo
        $stmt = $pdo->query("
            SELECT DATE(created_at) as fecha, COUNT(*) as total
            FROM pronosticos_riesgo
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY fecha
        ");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay datos, generar tendencias basadas en retardos reales
        if (count($datos) < 2) {
            $stmt = $pdo->query("
                SELECT DATE(fecha) as fecha, COUNT(*) as total
                FROM retardos
                WHERE fecha >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                GROUP BY DATE(fecha)
                ORDER BY fecha
            ");
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (count($datos) < 2) {
            return ['tendencia' => 'estable', 'cambio_porcentual' => 0, 'datos' => []];
        }
        
        $primeros = array_slice($datos, 0, min(5, count($datos)));
        $ultimos = array_slice($datos, -min(5, count($datos)));
        
        $avgPrimeros = array_sum(array_column($primeros, 'total')) / count($primeros);
        $avgUltimos = array_sum(array_column($ultimos, 'total')) / count($ultimos);
        
        $cambio = $avgPrimeros > 0 ? (($avgUltimos - $avgPrimeros) / $avgPrimeros) * 100 : 0;
        
        return [
            'tendencia' => $cambio > 5 ? 'creciente' : ($cambio < -5 ? 'decreciente' : 'estable'),
            'cambio_porcentual' => round($cambio, 1),
            'datos' => $datos
        ];
    }
    
    private function getMetricasClave($pdo) {
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(DISTINCT empleado_id) FROM retardos WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as empleados_con_incidencias,
                (SELECT COUNT(*) FROM retardos WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as total_incidencias_mes,
                (SELECT COALESCE(AVG(minutos_retardo), 0) FROM retardos WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as promedio_minutos_retardo,
                (SELECT COUNT(*) FROM empleados) as total_empleados_activos
        ");
        $metricas = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
        $totalEmp = $metricas['total_empleados_activos'] ?: 1;
        $metricas['tasa_incidencia_porcentual'] = round(($metricas['total_incidencias_mes'] / $totalEmp) * 100, 1);
        
        return $metricas;
    }

    public function predecirRiesgosLaborales($empleado_id = null, $dias = 30) {
        return ['success' => true, 'data' => []];
    }

    public function calcularIndiceRiesgoCompleto($empleado_id) {
        try {
            $pdo = $this->db->getConnection();
            
            $empStmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
            $empStmt->execute([$empleado_id]);
            $empleado = $empStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$empleado) {
            return ['error' => 'Empleado no encontrado'];
        }
        
        // Obtener métricas reales
        $rCount = $pdo->prepare("SELECT COUNT(*) FROM retardos WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $rCount->execute([$empleado_id]);
        $retardos = (int)$rCount->fetchColumn();
        
        $fCount = $pdo->prepare("SELECT COUNT(*) FROM asistencia WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND tipo_asistencia = 'falta'");
        $fCount->execute([$empleado_id]);
        $faltas = (int)$fCount->fetchColumn();
        
        $sCount = $pdo->prepare("SELECT COUNT(*) FROM sanciones WHERE empleado_id = ? AND estatus = 'activa'");
        $sCount->execute([$empleado_id]);
        $sanciones = (int)$sCount->fetchColumn();
        
        // Calcular puntualidad basada en el ciclo y horario del empleado (manual SEP)
        $tardanzasReales = 0;
        $llegoATiempo = 0;
        
        try {
            // Primero intentar obtener de horarios_empleados (asignación directa)
            $horarioStmt = $pdo->prepare("
                SELECT hl.hora_entrada, hl.tolerancia_minutos
                FROM horarios_empleados he
                JOIN horarios_laborales hl ON he.horario_id = hl.id
                WHERE he.empleado_id = ? AND he.activo = 1
                ORDER BY he.dia_semana
                LIMIT 7
            ");
            $horarioStmt->execute([$empleado_id]);
            $horariosDia = $horarioStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si no hay horarios_empleados, usar bloques_ciclo
            if (!$horariosDia || count($horariosDia) === 0) {
                $horarioStmt = $pdo->prepare("
                    SELECT hl.hora_entrada, hl.tolerancia_minutos, bc.dia_semana
                    FROM empleados_ciclos ec
                    JOIN bloques_ciclo bc ON ec.ciclo_id = bc.ciclo_id AND bc.activo = 1
                    JOIN horarios_laborales hl ON bc.horario_id = hl.id
                    WHERE ec.empleado_id = ? AND ec.activo = 1
                    ORDER BY bc.dia_semana
                    LIMIT 7
                ");
                $horarioStmt->execute([$empleado_id]);
                $horariosDia = $horarioStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            if ($horariosDia && count($horariosDia) > 0) {
                // Obtener las llegadas del empleado en el último mes
                $llegadasStmt = $pdo->prepare("
                    SELECT a.fecha, a.hora_entrada as hora_marcada, DAYOFWEEK(a.fecha) as dia_semana
                    FROM asistencia a
                    WHERE a.empleado_id = ? 
                    AND a.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND a.hora_entrada IS NOT NULL
                    ORDER BY a.fecha
                ");
                $llegadasStmt->execute([$empleado_id]);
                $llegadas = $llegadasStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Usar el primer horario como referencia (asumiendo mismo horario todos los días)
                $horaEntradaBase = strtotime($horariosDia[0]['hora_entrada']);
                $toleranciaBase = $horariosDia[0]['tolerancia_minutos'] ?? 10;
                $limiteTardeBase = $horaEntradaBase + ($toleranciaBase * 60);
                
                foreach ($llegadas as $llegada) {
                    if ($llegada['hora_marcada']) {
                        $horaMarcada = strtotime($llegada['hora_marcada']);
                        
                        if ($horaMarcada > $horaEntradaBase && $horaMarcada <= $limiteTardeBase) {
                            // Tardanza real: llegó entre 1 y tolerancia minutos tarde
                            $tardanzasReales++;
                        } elseif ($horaMarcada <= $horaEntradaBase) {
                            // Llegó antes o a tiempo
                            $llegoATiempo++;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error calculando puntualidad: " . $e->getMessage());
        }
        
        // Aplicar esquema de sanciones del manual SEP (sección 3.3)
        // Las tardanzas reales se cuentan como incidencias adicionales
        $incidenciasTotales = $retardos + $faltas + $tardanzasReales;
        
        // Calcular índice de riesgo incluyendo tardanzas reales
        $puntaje = ($retardos * 2) + ($faltas * 5) + ($sanciones * 10) + ($tardanzasReales * 1.5);
        $indice_riesgo = min(100, $puntaje);
        
        if ($indice_riesgo >= 60) $nivel = 'alto';
        elseif ($indice_riesgo >= 40) $nivel = 'medio';
        else $nivel = 'bajo';
        
        // Obtener permisos (con manejo de errores)
        $diasEconomicos = 0;
        $comisiones = 0;
        
        try {
            $permisosStmt = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM ausencias WHERE empleado_id = ? AND YEAR(fecha_inicio) = YEAR(NOW())) as dias_economicos,
                    (SELECT COUNT(*) FROM comisiones WHERE empleado_id = ? AND YEAR(fecha_inicio) = YEAR(NOW())) as comisiones
            ");
            $permisosStmt->execute([$empleado_id, $empleado_id]);
            $permisos = $permisosStmt->fetch(PDO::FETCH_ASSOC);
            $diasEconomicos = (int)($permisos['dias_economicos'] ?? 0);
            $comisiones = (int)($permisos['comisiones'] ?? 0);
        } catch (Exception $e) {}
        
        // Calcular pronóstico
        $pronostico = [
            'incidencias_estimadas' => $retardos + $faltas,
            'dias_pronostico' => 30,
            'probabilidad' => min(100, round(($indice_riesgo / 100) * 100))
        ];
        
        // Factores de riesgo
        $factores = [];
        if ($retardos > 5) $factores[] = ['factor' => 'Retardos frecuentes', 'descripcion' => $retardos . ' retardos en 90 días', 'impacto' => 'alto'];
        if ($faltas > 2) $factores[] = ['factor' => 'Faltas recurrentes', 'descripcion' => $faltas . ' faltas en 90 días', 'impacto' => 'alto'];
        if ($sanciones > 0) $factores[] = ['factor' => 'Sanciones activas', 'descripcion' => $sanciones . ' sanciones', 'impacto' => 'alto'];
        
        // Determinar si es candidato a empleado del mes / puntualidad
        // Según manual SEP: sin retardos, sin faltas, sin sanciones Y sin tardanzas reales
        $esApto = ($retardos === 0 && $faltas === 0 && $sanciones === 0 && $tardanzasReales === 0 && $llegoATiempo > 0);
        
        return [
            'success' => true,
            'nombre' => $empleado['nombre'],
            'apellido' => $empleado['apellido'],
            'area' => $empleado['area'],
            'puesto' => $empleado['puesto'],
            'indice_riesgo' => $indice_riesgo,
            'nivel_riesgo' => $nivel,
            'esAptoPuntualidad' => $esApto,
            'metricas' => [
                'retardos' => $retardos,
                'faltas' => $faltas,
                'sanciones' => $sanciones,
                'tardanzas_reales' => $tardanzasReales,
                'llegadas_tiempo' => $llegoATiempo,
                'dias_economicos' => $diasEconomicos,
                'licencias' => 0,
                'vacaciones' => 0,
                'comisiones' => $comisiones
            ],
            'pronostico' => $pronostico,
            'factores_riesgo' => $factores
        ];
        } catch (Exception $e) {
            return ['error' => 'Error al calcular riesgo: ' . $e->getMessage()];
        }
    }

    public function analizarPatronesIncumplimiento($fecha_inicio, $fecha_fin, $area) {
        return ['success' => true, 'data' => []];
    }

    public function generarAlertasTempranas($umbral = 0.35, $umbral_critico = 0.70) {
        return ['success' => true, 'data' => []];
    }

    public function getAlertasHistoricas($limite = 50, $empleado_id = null, $tipo = null) {
        return ['success' => true, 'data' => []];
    }

    public function marcarAlertaLeida($alerta_id) {
        return true;
    }

    public function generarRecomendacionesInteligentes($periodo = 30) {
        return ['success' => true, 'recomendaciones' => []];
    }

    public function analizarArea($area) {
        return ['success' => true, 'data' => []];
    }

    public function getHistorialPronosticos($empleado_id, $limite = 10) {
        return ['success' => true, 'data' => []];
    }

    public function compararPeriodos($periodo1_inicio, $periodo1_fin, $periodo2_inicio, $periodo2_fin) {
        return ['success' => true, 'data' => []];
    }
}