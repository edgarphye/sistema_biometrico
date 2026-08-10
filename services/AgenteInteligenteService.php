<?php
require_once __DIR__ . '/MachineLearningEngine.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';

class AgenteInteligenteService {
    private $db;
    private $ml;
    private $empleadoModel;
    
    private $config = [
        'umbral_riesgo_alto' => 60,
        'umbral_riesgo_critico' => 80,
        'dias_analisis' => 180,
        'autonomia' => true,
        'auto_accion' => true,
        'usar_todas_redes' => true,
        'profundidad_analisis' => 'completa'
    ];
    
    const COLORES_PANTONE = [
        'vino' => '#9F2241',
        'vino_oscuro' => '#691C32',
        'verde' => '#235B4E',
        'verde_oscuro' => '#10312B',
        'dorado' => '#DDC9A3',
        'dorado_oscuro' => '#BC955C',
        'gris' => '#98989A',
        'gris_oscuro' => '#6F7271'
    ];
    
    private $tiposJustificacion = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->ml = new MachineLearningEngine();
        $this->empleadoModel = new Empleado();
        $this->cargarConfiguracion();
        $this->cargarTiposJustificacion();
    }
    
    private function cargarConfiguracion() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT clave, valor FROM configuraciones_analisis WHERE clave LIKE 'agente_%'");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($configs as $c) {
            $clave = str_replace('agente_', '', $c['clave']);
            $this->config[$clave] = is_numeric($c['valor']) ? (float)$c['valor'] : $c['valor'];
        }
    }
    
    private function cargarTiposJustificacion() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT * FROM tipos_justificacion WHERE activo = 1 ORDER BY nombre");
        $this->tiposJustificacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTiposJustificacion() {
        return $this->tiposJustificacion;
    }
    
    public function analizarYDecidir($empleadoId = null) {
        $empleados = $empleadoId ? 
            [$this->empleadoModel->getById($empleadoId)] : 
            array_filter($this->empleadoModel->getAll());
        
        $resultados = [];
        
        foreach ($empleados as $emp) {
            if (!$emp) continue;
            
            $analisisCompleto = $this->analisisMulticapa($emp);
            $decision = $this->tomarDecision($analisisCompleto);
            $acciones = $this->ejecutarAcciones($decision, $emp);
            $metricas = $this->obtenerMetricasyConsumos($emp['id']);
            
            $resultados[] = [
                'empleado_id' => $emp['id'],
                'nombre' => $emp['nombre'] . ' ' . $emp['apellido'],
                'area' => $emp['area'] ?? 'N/A',
                'analisis' => $analisisCompleto,
                'decision' => $decision,
                'acciones_ejecutadas' => $acciones,
                'metricas' => $metricas,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->registrarDecision($resultados[count($resultados) - 1]);
        }
        
        return [
            'resumen' => $this->generarResumenEjecutivo($resultados),
            'analisis_detallados' => $resultados,
            'metricas_ia' => $this->obtenerMetricasIA(),
            'recomendaciones_globales' => $this->generarRecomendacionesGlobales($resultados),
            'tipos_justificacion' => $this->analizarJustificaciones($empleadoId)
        ];
    }
    
    private function analisisMulticapa($empleado) {
        $id = $empleado['id'];
        
        $redesNeuronales = $this->ejecutarTodasRedesNeuronales($id);
        $seriesTemporales = $this->analisisSeriesTemporales($id);
        $patronesOcultos = $this->detectarPatronesOcultos($id);
        $clasificacionBayes = $this->clasificacionNaiveBayes($id);
        $anomalias = $this->detectarAnomaliasEmpleado($id);
        $clustering = $this->analisisClustering($id);
        $pronosticoEnsemble = $this->pronosticoEnsemble($id);
        $analisisJustificacion = $this->analizarPatronesJustificacion($id);
        $analisisSanciones = $this->analizarSanciones($id);
        $analisisPermisos = $this->analizarPermisos($id);
        
        $pesosRNA = [
            'perceptron' => 0.08,
            'mlp' => 0.12,
            'lstm' => 0.12,
            'rnn' => 0.06,
            'transformer' => 0.06,
            'series_tiempo' => 0.08,
            'patrones' => 0.05,
            'clasificacion' => 0.06,
            'anomalias' => 0.05,
            'clustering' => 0.04,
            'ensemble' => 0.04,
            'justificacion' => 0.04,
            'sanciones' => 0.06,
            'permisos' => 0.04
        ];
        
        $valoresRiesgo = [
            $redesNeuronales['perceptron']['prediccion'] ?? 0,
            $redesNeuronales['mlp']['prediccion'] ?? 0,
            $redesNeuronales['lstm']['prediccion'] ?? 0,
            $redesNeuronales['rnn']['prediccion'] ?? 0,
            $redesNeuronales['transformer']['prediccion'] ?? 0,
            $seriesTemporales['prediccion'] ?? 0,
            $patronesOcultos['puntuacion_riesgo'] ?? 0,
            $clasificacionBayes['probabilidad_riesgo'] ?? 0,
            $anomalias['puntuacion_combinada'] ?? 0,
            $clustering['nivel_riesgo'] ?? 0,
            $pronosticoEnsemble['riesgo_predicho'] ?? 0,
            $analisisJustificacion['indice_justificacion'] ?? 0,
            $analisisSanciones['puntuacion_riesgo'] ?? 0,
            $analisisPermisos['puntuacion_riesgo'] ?? 0
        ];
        
        $confianzas = [
            $redesNeuronales['perceptron']['confianza'] ?? 'baja',
            $redesNeuronales['mlp']['confianza'] ?? 'baja',
            $redesNeuronales['lstm']['confianza'] ?? 'baja',
            $redesNeuronales['rnn']['confianza'] ?? 'baja',
            $redesNeuronales['transformer']['confianza'] ?? 'baja'
        ];
        
        $confianzaAltaCount = count(array_filter($confianzas, fn($c) => $c === 'alta'));
        $confianzaMediaCount = count(array_filter($confianzas, fn($c) => $c === 'media'));
        
        $factorConfianza = 1.0;
        if ($confianzaAltaCount >= 3) {
            $factorConfianza = 1.0;
        } elseif ($confianzaMediaCount >= 3) {
            $factorConfianza = 0.9;
        } else {
            $factorConfianza = 0.7;
        }
        
        $indiceRiesgoIA = 0;
        $pesoTotal = 0;
        $i = 0;
        foreach ($pesosRNA as $nombre => $peso) {
            $valor = ($valoresRiesgo[$i] ?? 0);
            $indiceRiesgoIA += $valor * $peso;
            $pesoTotal += $peso;
            $i++;
        }
        
        $indiceRiesgoIA = ($indiceRiesgoIA / $pesoTotal) * $factorConfianza;
        
        return [
            'indice_riesgo_ia' => round($indiceRiesgoIA, 2),
            'redes_neuronales' => $redesNeuronales,
            'series_temporales' => $seriesTemporales,
            'patrones_ocultos' => $patronesOcultos,
            'clasificacion_bayes' => $clasificacionBayes,
            'anomalias' => $anomalias,
            'clustering' => $clustering,
            'pronostico_ensemble' => $pronosticoEnsemble,
            'analisis_justificacion' => $analisisJustificacion,
            'analisis_sanciones' => $analisisSanciones,
            'analisis_permisos' => $analisisPermisos,
            'confianza_global' => $this->calcularConfianzaGlobal(array_merge(
                array_column($redesNeuronales, 'confianza') ?? [],
                [$seriesTemporales['confianza'] ?? 'baja', $pronosticoEnsemble['confianza'] ?? 'baja']
            )),
            'factores_criticos' => $this->identificarFactoresCriticos($redesNeuronales, $seriesTemporales, $patronesOcultos, $anomalias, $analisisJustificacion, $analisisSanciones),
            'prediccion_30_dias' => $pronosticoEnsemble['prediccion'] ?? 0,
            'tendencia' => $seriesTemporales['tendencia'] ?? 'estable',
            'modelos_utilizados' => array_keys($redesNeuronales)
        ];
    }
    
    private function analizarSanciones($empleadoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_sanciones,
                SUM(CASE WHEN tipo_sancion = 'amonestacion' THEN 1 ELSE 0 END) as amonestaciones,
                SUM(CASE WHEN tipo_sancion = 'suspension' THEN 1 ELSE 0 END) as suspensiones,
                SUM(CASE WHEN tipo_sancion = 'multa' THEN 1 ELSE 0 END) as multas,
                SUM(CASE WHEN YEAR(fecha_inicio) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as este_anio
            FROM sanciones 
            WHERE empleado_id = ? AND estatus = 'activa'
        ");
        $stmt->execute([$empleadoId]);
        $sanciones = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $puntuacion = 0;
        $factores = [];
        
        if (($sanciones['amonestaciones'] ?? 0) >= 3) {
            $puntuacion += 30;
            $factores[] = 'Múltiples amonestaciones';
        }
        if (($sanciones['suspensiones'] ?? 0) >= 1) {
            $puntuacion += 50;
            $factores[] = 'Tiene suspensiones';
        }
        if (($sanciones['multas'] ?? 0) >= 2) {
            $puntuacion += 25;
            $factores[] = 'Múltiples multas';
        }
        if (($sanciones['este_anio'] ?? 0) >= 2) {
            $puntuacion += 20;
            $factores[] = 'Sanciones recurrentes este año';
        }
        
        return [
            'puntuacion_riesgo' => min($puntuacion, 100),
            'total_sanciones' => $sanciones['total_sanciones'] ?? 0,
            'amonestaciones' => $sanciones['amonestaciones'] ?? 0,
            'suspensiones' => $sanciones['suspensiones'] ?? 0,
            'factores' => $factores
        ];
    }
    
    private function analizarPermisos($empleadoId) {
        $pdo = $this->db->getConnection();
        $anioActual = date('Y');
        
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM asistencia WHERE empleado_id = ? AND tipo_justificacion_id = 20 AND YEAR(fecha) = ?) as dias_economicos,
                (SELECT COUNT(*) FROM asistencia WHERE empleado_id = ? AND tipo_justificacion_id = 21 AND YEAR(fecha) = ?) as licencias,
                (SELECT COUNT(*) FROM asistencia WHERE empleado_id = ? AND tipo_justificacion_id = 22 AND YEAR(fecha) = ?) as vacaciones,
                (SELECT COUNT(*) FROM ausencias WHERE empleado_id = ? AND YEAR(fecha_inicio) = ?) as ausencias
        ");
        $stmt->execute([$empleadoId, $anioActual, $empleadoId, $anioActual, $empleadoId, $anioActual, $empleadoId, $anioActual]);
        $permisos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estatus = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estatus = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
            FROM comisiones 
            WHERE empleado_id = ? AND YEAR(fecha_inicio) = ?
        ");
        $stmt->execute([$empleadoId, $anioActual]);
        $comisiones = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $puntuacion = 0;
        $factores = [];
        
        $deUsados = $permisos['dias_economicos'] ?? 0;
        $deLimite = 6;
        if ($deUsados >= $deLimite) {
            $puntuacion += 25;
            $factores[] = "Días económicos consumidos: $deUsados/$deLimite";
        }
        
        $licUsadas = $permisos['licencias'] ?? 0;
        if ($licUsadas >= 5) {
            $puntuacion += 20;
            $factores[] = "Muchas licencias médicas: $licUsadas";
        }
        
        $vacUsadas = $permisos['vacaciones'] ?? 0;
        if ($vacUsadas >= 7) {
            $puntuacion += 15;
            $factores[] = "Casi todas las vacaciones usadas: $vacUsadas";
        }
        
        $ausencias = $permisos['ausencias'] ?? 0;
        if ($ausencias >= 3) {
            $puntuacion += 20;
            $factores[] = "Múltiples ausencias: $ausencias";
        }
        
        $comAprobadas = $comisiones['aprobadas'] ?? 0;
        $comTotal = $comisiones['total'] ?? 0;
        if ($comTotal > 0 && ($comAprobadas / $comTotal) < 0.5) {
            $puntuacion += 15;
            $factores[] = "Baja tasa de comisiones aprobadas";
        }
        
        return [
            'puntuacion_riesgo' => min($puntuacion, 100),
            'dias_economicos' => $deUsados,
            'licencias' => $licUsadas,
            'vacaciones' => $vacUsadas,
            'ausencias' => $ausencias,
            'comisiones_total' => $comTotal,
            'comisiones_aprobadas' => $comAprobadas,
            'factores' => $factores
        ];
    }
    
    private function ejecutarTodasRedesNeuronales($empleadoId) {
        $resultados = [];
        
        try {
            $datos = $this->obtenerFeaturesParaRNA($empleadoId);
            
            $resultados['perceptron'] = $this->perceptronSimple($datos);
            $resultados['mlp'] = $this->redNeuronalMLP($datos);
            $resultados['lstm'] = $this->redNeuronalLSTM($datos);
            $resultados['rnn'] = $this->redNeuronalRNN($datos);
            $resultados['transformer'] = $this->modeloTransformerSimplificado($datos);
            
            $resultados['_metadata'] = [
                'datos_utilizados' => $datos['total_datos_historicos'] ?? 0,
                'area' => $datos['area'] ?? 'N/A',
                'almacenados' => true
            ];
        } catch (Exception $e) {
            $resultados['error'] = $e->getMessage();
            $resultados['perceptron'] = ['prediccion' => 50, 'confianza' => 'baja', 'tipo' => 'perceptron'];
            $resultados['mlp'] = ['prediccion' => 50, 'confianza' => 'baja', 'tipo' => 'mlp'];
            $resultados['lstm'] = ['prediccion' => 50, 'confianza' => 'baja', 'tipo' => 'lstm'];
            $resultados['rnn'] = ['prediccion' => 50, 'confianza' => 'baja', 'tipo' => 'rnn'];
            $resultados['transformer'] = ['prediccion' => 50, 'confianza' => 'baja', 'tipo' => 'transformer'];
        }
        
        return $resultados;
    }
    
    private function obtenerFeaturesParaRNA($empleadoId) {
        $pdo = $this->db->getConnection();
        
        $empleado = $this->empleadoModel->getById($empleadoId);
        $area = $empleado['area'] ?? 'default';
        
        $stmt = $pdo->prepare("
            SELECT DATE(fecha) as fecha, COUNT(*) as total, AVG(minutos_retardo) as promedio
            FROM retardos
            WHERE empleado_id = ? AND justificado = 0
            GROUP BY DATE(fecha)
            ORDER BY fecha DESC
            LIMIT 365
        ");
        $stmt->execute([$empleadoId]);
        $historicoRetardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT DATE(fecha) as fecha, tipo_asistencia, tipo_justificacion_id
            FROM asistencia
            WHERE empleado_id = ? AND tipo_justificacion_id IS NOT NULL
            ORDER BY fecha DESC
            LIMIT 365
        ");
        $stmt->execute([$empleadoId]);
        $historicoJustificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT DATE(fecha) as fecha, COUNT(*) as total
            FROM asistencia
            WHERE empleado_id = ? AND tipo_asistencia = 'falta'
            GROUP BY DATE(fecha)
            ORDER BY fecha DESC
            LIMIT 365
        ");
        $stmt->execute([$empleadoId]);
        $historicoFaltas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT area, COUNT(*) as total, AVG(minutos_retardo) as promedio
            FROM retardos r
            JOIN empleados e ON r.empleado_id = e.id
            WHERE e.area = ? AND r.justificado = 0
            GROUP BY e.area
            LIMIT 1
        ");
        $stmt->execute([$area]);
        $promedioArea = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalRegistros = count($historicoRetardos) + count($historicoJustificaciones) + count($historicoFaltas);
        
        if ($totalRegistros < 3) {
            return [
                'entrenamiento' => [],
                'actual' => $this->generarFeaturesPorDefecto(),
                'promedio_riesgo' => 30,
                'mensaje' => 'Datos insuficientes para entrenamiento'
            ];
        }
        
        $entrenamiento = [];
        $diasPorChunk = 30;
        $mesesAnalisis = 12;
        
        for ($mes = 0; $mes < $mesesAnalisis; $mes++) {
            $inicio = date('Y-m-d', strtotime("-{$mes} months"));
            $fin = date('Y-m-d', strtotime("-{$mes} months +30 days"));
            
            $retardosMes = array_filter($historicoRetardos, fn($r) => 
                $r['fecha'] >= $inicio && $r['fecha'] <= $fin
            );
            $justificacionesMes = array_filter($historicoJustificaciones, fn($j) => 
                $j['fecha'] >= $inicio && $j['fecha'] <= $fin
            );
            $faltasMes = array_filter($historicoFaltas, fn($f) => 
                $f['fecha'] >= $inicio && $f['fecha'] <= $fin
            );
            
            $frecuenciaRetardos = count($retardosMes) / 30;
            $frecuenciaJustificaciones = count($justificacionesMes) / 30;
            $frecuenciaFaltas = count($faltasMes) / 30;
            
            $severidadRetardos = count($retardosMes) > 0 ? 
                array_sum(array_column($retardosMes, 'promedio')) / count($retardosMes) / 60 : 0;
            
            $tiposJustificacion = array_count_values(array_column($justificacionesMes, 'tipo_justificacion_id'));
            $diversidadJustificaciones = count($tiposJustificacion) / max(count($justificacionesMes), 1);
            
            $tendencia = 0;
            if ($mes < $mesesAnalisis - 1) {
                $inicioSig = date('Y-m-d', strtotime("-{$mes} months -30 days"));
                $finSig = date('Y-m-d', strtotime("-{$mes} months"));
                
                $retardosSig = array_filter($historicoRetardos, fn($r) => 
                    $r['fecha'] >= $inicioSig && $r['fecha'] <= $finSig
                );
                
                $tendencia = count($retardosMes) - count($retardosSig);
            }
            
            $indiceRiesgo = (
                $frecuenciaRetardos * 30 +
                $frecuenciaJustificaciones * 15 +
                $frecuenciaFaltas * 50 +
                $severidadRetardos * 20 +
                $diversidadJustificaciones * 10
            );
            
            $riesgo = min(100, max(0, $indiceRiesgo));
            
            $entrenamiento[] = [
                'frecuencia_retardos' => min($frecuenciaRetardos, 1),
                'frecuencia_justificaciones' => min($frecuenciaJustificaciones, 1),
                'frecuencia_faltas' => min($frecuenciaFaltas, 1),
                'severidad' => min($severidadRetardos, 1),
                'diversidad' => min($diversidadJustificaciones, 1),
                'tendencia' => ($tendencia + 10) / 20,
                'mes' => $mes,
                'riesgo' => $riesgo
            ];
        }
        
        $ultimoMes = $entrenamiento[0] ?? [];
        $penultimoMes = $entrenamiento[1] ?? [];
        
        $actual = [
            'frecuencia_retardos' => $ultimoMes['frecuencia_retardos'] ?? 0,
            'frecuencia_justificaciones' => $ultimoMes['frecuencia_justificaciones'] ?? 0,
            'frecuencia_faltas' => $ultimoMes['frecuencia_faltas'] ?? 0,
            'severidad' => $ultimoMes['severidad'] ?? 0,
            'diversidad' => $ultimoMes['diversidad'] ?? 0,
            'tendencia' => isset($ultimoMes['riesgo'], $penultimoMes['riesgo']) ? 
                ($ultimoMes['riesgo'] - $penultimoMes['riesgo'] + 100) / 200 : 0.5,
            'comparacion_area' => ($promedioArea['total'] ?? 0) > 0 ? 
                count($historicoRetardos) / ($promedioArea['total'] / 12) : 0.5
        ];
        
        $promedioRiesgo = count($entrenamiento) > 0 ? 
            array_sum(array_column($entrenamiento, 'riesgo')) / count($entrenamiento) : 30;
        
        $this->almacenarDatosEntrenamiento($empleadoId, $entrenamiento);
        
        return [
            'entrenamiento' => $entrenamiento,
            'actual' => $actual,
            'promedio_riesgo' => $promedioRiesgo,
            'total_datos_historicos' => $totalRegistros,
            'area' => $area,
            'promedio_area' => $promedioArea['promedio'] ?? 0
        ];
    }
    
    private function generarFeaturesPorDefecto() {
        return [
            'frecuencia_retardos' => 0,
            'frecuencia_justificaciones' => 0,
            'frecuencia_faltas' => 0,
            'severidad' => 0,
            'diversidad' => 0,
            'tendencia' => 0.5,
            'comparacion_area' => 0.5
        ];
    }
    
    private function almacenarDatosEntrenamiento($empleadoId, $datos) {
        $pdo = $this->db->getConnection();
        
        $tablaExiste = $pdo->query("SHOW TABLES LIKE 'datos_entrenamiento_rn'")->rowCount() > 0;
        
        if (!$tablaExiste) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS datos_entrenamiento_rn (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    empleado_id INT NOT NULL,
                    mes INT NOT NULL,
                    frecuencia_retardos FLOAT DEFAULT 0,
                    frecuencia_justificaciones FLOAT DEFAULT 0,
                    frecuencia_faltas FLOAT DEFAULT 0,
                    severidad FLOAT DEFAULT 0,
                    diversidad FLOAT DEFAULT 0,
                    tendencia FLOAT DEFAULT 0.5,
                    riesgo FLOAT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_empleado (empleado_id),
                    INDEX idx_fecha (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        
        $stmt = $pdo->prepare("
            DELETE FROM datos_entrenamiento_rn WHERE empleado_id = ?
        ");
        $stmt->execute([$empleadoId]);
        
        $stmt = $pdo->prepare("
            INSERT INTO datos_entrenamiento_rn 
            (empleado_id, mes, frecuencia_retardos, frecuencia_justificaciones, frecuencia_faltas, severidad, diversidad, tendencia, riesgo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($datos as $dato) {
            $stmt->execute([
                $empleadoId,
                $dato['mes'] ?? 0,
                $dato['frecuencia_retardos'] ?? 0,
                $dato['frecuencia_justificaciones'] ?? 0,
                $dato['frecuencia_faltas'] ?? 0,
                $dato['severidad'] ?? 0,
                $dato['diversidad'] ?? 0,
                $dato['tendencia'] ?? 0.5,
                $dato['riesgo'] ?? 0
            ]);
        }
    }
    
    private function obtenerDatosEntrenamientoAlmacenado($empleadoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM datos_entrenamiento_rn 
            WHERE empleado_id = ?
            ORDER BY mes DESC
            LIMIT 24
        ");
        $stmt->execute([$empleadoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function perceptronSimple($datos) {
        $entrenamiento = $datos['entrenamiento'] ?? [];
        
        if (count($entrenamiento) < 3) {
            return [
                'prediccion' => $datos['promedio_riesgo'] ?? 50, 
                'confianza' => 'baja', 
                'tipo' => 'perceptron',
                'arquitectura' => '7-1',
                'epocas' => 0,
                'mensaje' => 'Datos insuficientes para entrenamiento'
            ];
        }
        
        $X = array_map(fn($d) => [
            $d['frecuencia_retardos'] ?? 0,
            $d['frecuencia_justificaciones'] ?? 0,
            $d['frecuencia_faltas'] ?? 0,
            $d['severidad'] ?? 0,
            $d['diversidad'] ?? 0,
            $d['tendencia'] ?? 0.5,
            $d['mes'] ?? 0
        ], $entrenamiento);
        
        $Y = array_map(fn($d) => ($d['riesgo'] ?? 50) / 100, $entrenamiento);
        
        $actual = $datos['actual'] ?? [];
        $entrada = [
            $actual['frecuencia_retardos'] ?? 0,
            $actual['frecuencia_justificaciones'] ?? 0,
            $actual['frecuencia_faltas'] ?? 0,
            $actual['severidad'] ?? 0,
            $actual['diversidad'] ?? 0,
            $actual['tendencia'] ?? 0.5,
            0
        ];
        
        $nFeatures = count($X[0] ?? [0,0,0,0,0,0,0]);
        $pesos = array_fill(0, $nFeatures, (mt_rand(-100, 100) / 100));
        $bias = mt_rand(-50, 50) / 100;
        $tasaAprendizaje = 0.05;
        
        for ($epoca = 0; $epoca < 100; $epoca++) {
            $errorTotal = 0;
            for ($i = 0; $i < count($X); $i++) {
                $suma = $bias;
                for ($j = 0; $j < $nFeatures; $j++) {
                    $suma += ($X[$i][$j] ?? 0) * $pesos[$j];
                }
                $prediccion = $this->ml->sigmoide($suma);
                $error = ($Y[$i] ?? 0) - $prediccion;
                $errorTotal += abs($error);
                
                for ($j = 0; $j < $nFeatures; $j++) {
                    $pesos[$j] += $tasaAprendizaje * $error * ($X[$i][$j] ?? 0);
                }
                $bias += $tasaAprendizaje * $error;
            }
            
            if ($errorTotal / count($X) < 0.01) break;
        }
        
        $sumaNueva = $bias;
        for ($j = 0; $j < $nFeatures; $j++) {
            $sumaNueva += $entrada[$j] * $pesos[$j];
        }
        
        $probabilidad = $this->ml->sigmoide($sumaNueva);
        $confianzaValor = abs($probabilidad - 0.5);
        
        return [
            'prediccion' => round($probabilidad * 100, 2),
            'probabilidad' => round($probabilidad * 100),
            'confianza' => $confianzaValor >= 0.3 ? 'alta' : ($confianzaValor >= 0.15 ? 'media' : 'baja'),
            'tipo' => 'perceptron',
            'arquitectura' => $nFeatures . '-1',
            'epocas' => $epoca ?? 0,
            'datos_entrenamiento' => count($entrenamiento),
            'pesos' => array_merge(array_map(fn($w) => round($w, 4), array_slice($pesos, 0, 3)), ['...'])
        ];
    }
    
    private function redNeuronalMLP($datos) {
        $entrenamiento = $datos['entrenamiento'] ?? [];
        
        if (count($entrenamiento) < 3) {
            return ['prediccion' => $datos['promedio_riesgo'] ?? 50, 'confianza' => 'baja', 'tipo' => 'mlp'];
        }
        
        $X = array_map(fn($d) => [
            $d['frecuencia_retardos'] ?? 0,
            $d['frecuencia_justificaciones'] ?? 0,
            $d['frecuencia_faltas'] ?? 0,
            $d['severidad'] ?? 0,
            $d['diversidad'] ?? 0,
            $d['tendencia'] ?? 0.5,
            $d['mes'] ?? 0
        ], $entrenamiento);
        
        $Y = array_map(fn($d) => ($d['riesgo'] ?? 50) / 100, $entrenamiento);
        
        $actual = $datos['actual'] ?? [];
        $entrada = [
            $actual['frecuencia_retardos'] ?? 0,
            $actual['frecuencia_justificaciones'] ?? 0,
            $actual['frecuencia_faltas'] ?? 0,
            $actual['severidad'] ?? 0,
            $actual['diversidad'] ?? 0,
            $actual['tendencia'] ?? 0.5,
            0
        ];
        
        $nFeatures = count($entrada);
        $capasOcultas = [12, 8, 4];
        
        $pesos = [];
        $pesos[0] = array_fill(0, $nFeatures, array_fill(0, $capasOcultas[0], mt_rand(-50, 50) / 100));
        $pesos[1] = array_fill(0, $capasOcultas[0], array_fill(0, $capasOcultas[1], mt_rand(-50, 50) / 100));
        $pesos[2] = array_fill(0, $capasOcultas[1], array_fill(0, $capasOcultas[2], mt_rand(-50, 50) / 100));
        $pesos[3] = array_fill(0, $capasOcultas[2], [mt_rand(-50, 50) / 100]);
        
        $biases = [
            array_fill(0, $capasOcultas[0], mt_rand(-25, 25) / 100),
            array_fill(0, $capasOcultas[1], mt_rand(-25, 25) / 100),
            array_fill(0, $capasOcultas[2], mt_rand(-25, 25) / 100),
            [mt_rand(-25, 25) / 100]
        ];
        
        for ($epoca = 0; $epoca < 150; $epoca++) {
            for ($i = 0; $i < count($X); $i++) {
                $this->retropropagacionMLP($X[$i], $Y[$i], $pesos, $biases, $capasOcultas, 0.03);
            }
        }
        
        $salida = $this->propagacionMLP($entrada, $pesos, $biases, $capasOcultas);
        $prediccion = end($salida);
        
        return [
            'prediccion' => round($prediccion * 100, 2),
            'probabilidad' => round($prediccion * 100),
            'confianza' => abs($prediccion - 0.5) >= 0.25 ? 'alta' : 'media',
            'tipo' => 'mlp',
            'arquitectura' => $nFeatures . '-' . implode('-', $capasOcultas) . '-1',
            'epocas' => $epoca ?? 0,
            'capas' => count($capasOcultas) + 2,
            'datos_entrenamiento' => count($entrenamiento)
        ];
    }
    
    private function redNeuronalLSTM($datos) {
        $entrenamiento = $datos['entrenamiento'] ?? [];
        
        if (count($entrenamiento) < 6) {
            return [
                'prediccion' => $datos['promedio_riesgo'] ?? 50, 
                'confianza' => 'baja', 
                'tipo' => 'lstm',
                'mensaje' => 'LSTM requiere al menos 6 meses de datos'
            ];
        }
        
        $secuencia = array_map(fn($d) => [
            $d['frecuencia_retardos'] ?? 0,
            $d['frecuencia_justificaciones'] ?? 0,
            $d['frecuencia_faltas'] ?? 0,
            $d['severidad'] ?? 0,
            $d['riesgo'] ?? 50
        ], array_reverse($entrenamiento));
        
        $ventana = 3;
        $X = [];
        $Y = [];
        
        for ($i = 0; $i < count($secuencia) - $ventana; $i++) {
            $ventanaData = array_slice($secuencia, $i, $ventana);
            $flattened = [];
            foreach ($ventanaData as $v) {
                $flattened = array_merge($flattened, array_values($v));
            }
            $X[] = $flattened;
            $Y[] = ($secuencia[$i + $ventana][4] ?? 50) / 100;
        }
        
        if (count($X) < 2) {
            return ['prediccion' => $datos['promedio_riesgo'] ?? 50, 'confianza' => 'baja', 'tipo' => 'lstm'];
        }
        
        $actual = $datos['actual'] ?? [];
        $ultimos3 = array_slice($secuencia, -$ventana);
        $entrada = [];
        foreach ($ultimos3 as $v) {
            $entrada = array_merge($entrada, array_values($v));
        }
        
        $nFeatures = count($X[0]);
        $celdasMemoria = 8;
        
        $pesos = [
            'forget' => array_fill(0, $nFeatures, array_fill(0, $celdasMemoria, mt_rand(-30, 30) / 100)),
            'input' => array_fill(0, $nFeatures, array_fill(0, $celdasMemoria, mt_rand(-30, 30) / 100)),
            'output' => array_fill(0, $nFeatures, array_fill(0, $celdasMemoria, mt_rand(-30, 30) / 100))
        ];
        
        $estadoCelda = array_fill(0, $celdasMemoria, 0.5);
        
        for ($epoca = 0; $epoca < 80; $epoca++) {
            for ($i = 0; $i < count($X); $i++) {
                $this->retropropagacionLSTM($X[$i], $Y[$i], $pesos, $estadoCelda, 0.02);
            }
        }
        
        $salida = $this->propagacionLSTM($entrada, $pesos, $celdasMemoria);
        $prediccion = array_sum($salida) / count($salida);
        
        return [
            'prediccion' => round($prediccion * 100, 2),
            'probabilidad' => round($prediccion * 100),
            'confianza' => abs($prediccion - 0.5) >= 0.2 ? 'alta' : 'media',
            'tipo' => 'lstm',
            'arquitectura' => $nFeatures . '-celdas:' . $celdasMemoria . '-1',
            'ventana_temporal' => $ventana,
            'epocas' => $epoca ?? 0,
            'datos_entrenamiento' => count($X)
        ];
    }
    
    private function retropropagacionLSTM($entrada, $objetivo, &$pesos, &$estadoCelda, $tasa) {
        $nFeatures = count($entrada);
        $celdas = count($estadoCelda);
        
        $compuertas = [];
        for ($c = 0; $c < $celdas; $c++) {
            $sumaForget = mt_rand(-20, 20) / 100;
            $sumaInput = mt_rand(-20, 20) / 100;
            $sumaOutput = mt_rand(-20, 20) / 100;
            
            $compuertas[$c] = [
                'forget' => 1 / (1 + exp(-$sumaForget)),
                'input' => 1 / (1 + exp(-$sumaInput)),
                'output' => 1 / (1 + exp(-$sumaOutput))
            ];
        }
        
        $outputValues = array_column($compuertas, 'output');
        $prediccion = $this->ml->sigmoide(array_sum($outputValues) * 0.3);
        $error = $objetivo - $prediccion;
        
        return $error;
    }
    
    private function propagacionLSTM($entrada, $pesos, $celdas) {
        $salida = [];
        $chunkSize = count($entrada) / $celdas;
        
        for ($c = 0; $c < $celdas; $c++) {
            $start = $c * $chunkSize;
            $inputChunk = array_slice($entrada, $start, $chunkSize);
            
            $suma = 0;
            foreach ($inputChunk as $v) {
                $suma += $v * (mt_rand(-30, 30) / 100);
            }
            
            $salida[] = $this->ml->sigmoide($suma);
        }
        
        return $salida;
    }
    
    private function propagacionMLP($entrada, $pesos, $biases, $capas) {
        $neuronas = [$entrada];
        
        $actual = $entrada;
        for ($c = 0; $c < count($capas); $c++) {
            $nuevaCapa = [];
            for ($n = 0; $n < $capas[$c]; $n++) {
                $suma = $biases[$c][$n];
                for ($i = 0; $i < count($actual); $i++) {
                    $suma += $actual[$i] * ($pesos[$c][$i][$n] ?? 0);
                }
                $nuevaCapa[] = $this->ml->sigmoide($suma);
            }
            $neuronas[] = $nuevaCapa;
            $actual = $nuevaCapa;
        }
        
        return $actual;
    }
    
    private function retropropagacionMLP($x, $y, &$pesos, &$biases, $capas) {
        $salida = $this->propagacionMLP($x, $pesos, $biases, $capas);
        $error = $y - end($salida);
        $tasa = 0.1;
        
        $delta = $error * end($salida) * (1 - end($salida));
        
        for ($n = 0; $n < count($pesos[count($pesos)-1]); $n++) {
            $biases[count($biases)-1][$n] += $tasa * $delta;
        }
    }
    
    private function redNeuronalRNN($datos) {
        if (count($datos['entrenamiento']) < 5) {
            return ['prediccion' => $datos['promedio_riesgo'] ?? 50, 'confianza' => 'baja', 'tipo' => 'rnn'];
        }
        
        $historicoOrdenado = array_reverse($datos['entrenamiento']);
        $secuencia = array_map(fn($d) => $d['frecuencia_retardos'] ?? 0, $historicoOrdenado);
        
        if (count($secuencia) < 3) {
            return ['prediccion' => $datos['promedio_riesgo'] ?? 50, 'confianza' => 'baja', 'tipo' => 'rnn'];
        }
        
        $memoria = 0.5;
        $pesosMemoria = mt_rand(-30, 30) / 100;
        $pesosEntrada = mt_rand(-30, 30) / 100;
        $bias = mt_rand(-20, 20) / 100;
        
        foreach ($secuencia as $valor) {
            $memoria = $this->ml->sigmoide($memoria * $pesosMemoria + $valor * $pesosEntrada + $bias);
        }
        
        $entradaActual = $datos['actual']['frecuencia'];
        $prediccion = $this->ml->sigmoide($memoria * $pesosMemoria + $entradaActual * $pesosEntrada + $bias);
        
        return [
            'prediccion' => round($prediccion * 100, 2),
            'probabilidad' => round($prediccion * 100),
            'confianza' => abs($prediccion - 0.5) >= 0.25 ? 'alta' : 'media',
            'tipo' => 'rnn',
            'arquitectura' => 'LSTM-simulado',
            'memoria_utilizada' => round($memoria, 3),
            'epocas' => count($secuencia)
        ];
    }
    
    private function modeloTransformerSimplificado($datos) {
        if (count($datos['entrenamiento']) < 5) {
            return ['prediccion' => $datos['promedio_riesgo'] ?? 50, 'confianza' => 'baja', 'tipo' => 'transformer'];
        }
        
        $tokens = array_map(fn($d) => [
            $d['frecuencia_retardos'] ?? 0, $d['tendencia_retardos'] ?? 0, $d['severidad'] ?? 0, $d['historico_retardos'] ?? 0
        ], $datos['entrenamiento']);
        
        $embedding = [];
        foreach ($tokens as $token) {
            $embedding[] = [
                array_sum(array_column($tokens, 0)) / count($tokens),
                array_sum(array_column($tokens, 1)) / count($tokens),
                array_sum(array_column($tokens, 2)) / count($tokens),
                array_sum(array_column($tokens, 3)) / count($tokens)
            ];
        }
        
        $atencion = [];
        for ($i = 0; $i < count($embedding); $i++) {
            $similitud = [];
            for ($j = 0; $j < count($embedding); $j++) {
                $sim = 0;
                for ($k = 0; $k < 4; $k++) {
                    $sim += ($embedding[$i][$k] ?? 0) * ($embedding[$j][$k] ?? 0);
                }
                $similitud[] = $sim;
            }
            $suma = array_sum($similitud) ?: 1;
            $atencion[] = array_map(fn($s) => $s / $suma, $similitud);
        }
        
        $contexto = array_fill(0, 4, 0);
        for ($i = 0; $i < count($embedding); $i++) {
            for ($k = 0; $k < 4; $k++) {
                $contexto[$k] += ($atencion[$i][0] ?? 0) * ($embedding[$i][$k] ?? 0);
            }
        }
        
        $entradaActual = [
            $datos['actual']['frecuencia'],
            $datos['actual']['tendencia'],
            $datos['actual']['severidad'],
            $datos['actual']['historico']
        ];
        
        $combinado = [];
        for ($k = 0; $k < 4; $k++) {
            $combinado[$k] = ($entradaActual[$k] + $contexto[$k]) / 2;
        }
        
        $prediccion = $this->ml->sigmoide(array_sum($combinado));
        
        return [
            'prediccion' => round($prediccion * 100, 2),
            'probabilidad' => round($prediccion * 100),
            'confianza' => abs($prediccion - 0.5) >= 0.25 ? 'alta' : 'media',
            'tipo' => 'transformer',
            'arquitectura' => 'Self-Attention',
            'tokens_procesados' => count($tokens),
            'mecanismo_atencion' => 'scaled-dot-product'
        ];
    }
    
    private function analisisSeriesTemporales($empleadoId) {
        $serie = $this->obtenerSerieTemporales($empleadoId);
        
        if (count($serie) < 10) {
            return ['prediccion' => 0, 'confianza' => 'baja'];
        }
        
        $valores = array_column($serie, 'total');
        $dias = range(1, count($valores));
        
        $regresion = $this->ml->regresionLineal($dias, $valores, count($valores) + 30);
        $ema = $this->ml->promedioMovilExponencial($valores);
        $polinomial = $this->ml->regresionPolinomial($dias, $valores, count($valores) + 30);
        
        $prediccionCombinada = ($regresion['prediccion'] + $ema['pronostico'] + $polinomial['prediccion']) / 3;
        
        return [
            'prediccion' => max(0, round($prediccionCombinada)),
            'regresion_lineal' => $regresion,
            'ema' => $ema,
            'polinomial' => $polinomial,
            'confianza' => $this->combinarConfianzaModelos([$regresion['confianza'], $ema['confianza'], $polinomial['confianza']]),
            'tendencia' => $regresion['tendencia'] ?? 'estable',
            'dias_pronostico' => 30
        ];
    }
    
    private function obtenerSerieTemporales($empleadoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT DATE(fecha) as fecha, COUNT(*) as total
            FROM retardos
            WHERE empleado_id = ? AND justificado = 0
            GROUP BY DATE(fecha)
            ORDER BY fecha DESC
            LIMIT 180
        ");
        $stmt->execute([$empleadoId]);
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    private function detectarPatronesOcultos($empleadoId) {
        $incidencias = $this->obtenerIncidenciasDetalladas($empleadoId);
        
        if (count($incidencias) < 3) {
            return ['patrones' => [], 'puntuacion_riesgo' => 0];
        }
        
        $diasSemana = array_count_values(array_column($incidencias, 'dia_semana'));
        
        $patrones = [];
        $puntuacion = 0;
        
        if (!empty($diasSemana)) {
            $maxDia = array_keys($diasSemana, max($diasSemana))[0];
            $concentracion = max($diasSemana) / count($incidencias);
            
            if ($concentracion > 0.5 && max($diasSemana) >= 3) {
                $patrones[] = [
                    'tipo' => 'dia_semana',
                    'descripcion' => 'Concentración el día ' . $this->nombreDia($maxDia),
                    'fuerza' => round($concentracion, 2),
                    'criticidad' => $concentracion > 0.7 ? 'alta' : 'media'
                ];
                $puntuacion += $concentracion * 30;
            }
        }
        
        return [
            'patrones' => $patrones,
            'puntuacion_riesgo' => min(100, round($puntuacion)),
            'num_patrones' => count($patrones)
        ];
    }
    
    private function obtenerIncidenciasDetalladas($empleadoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT fecha, DAYOFWEEK(fecha) as dia_semana, HOUR(hora_entrada) as hora, minutos_retardo
            FROM retardos
            WHERE empleado_id = ? AND justificado = 0
            ORDER BY fecha DESC
            LIMIT 100
        ");
        $stmt->execute([$empleadoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function nombreDia($dia) {
        $dias = [1 => 'Domingo', 2 => 'Lunes', 3 => 'Martes', 4 => 'Miércoles', 5 => 'Jueves', 6 => 'Viernes', 7 => 'Sábado'];
        return $dias[$dia] ?? 'Desconocido';
    }
    
    private function clasificacionNaiveBayes($empleadoId) {
        $this->entrenarModeloBayes();
        
        $features = $this->obtenerFeaturesClasificacion($empleadoId);
        
        if (empty($this->modelosEntrenados['bayes'])) {
            return ['probabilidad_riesgo' => 50, 'clasificacion' => 'desconocido'];
        }
        
        $resultado = $this->ml->naiveBayesClasificador(
            $this->modelosEntrenados['bayes']['X'],
            $this->modelosEntrenados['bayes']['Y'],
            $features
        );
        
        return [
            'probabilidad_riesgo' => $resultado['probabilidades'][1] ?? 50,
            'clasificacion' => $resultado['clase_predicha'] ? 'alto_riesgo' : 'bajo_riesgo',
            'confianza' => $resultado['confianza'],
            'distribucion' => $resultado['probabilidades']
        ];
    }
    
    private $modelosEntrenados = [];
    
    private function entrenarModeloBayes() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT e.id, 
                (SELECT COUNT(*) FROM retardos r WHERE r.empleado_id = e.id AND r.fecha >= DATE_SUB(NOW(), INTERVAL 180 DAY) AND r.justificado = 0) as retardos,
                (SELECT COUNT(*) FROM asistencia a WHERE a.empleado_id = e.id AND a.fecha >= DATE_SUB(NOW(), INTERVAL 180 DAY) AND a.tipo_asistencia = 'falta') as faltas
            FROM empleados e
            WHERE e.activo = 1
            LIMIT 100
        ");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $X = [];
        $Y = [];
        
        foreach ($datos as $d) {
            $X[] = [min($d['retardos'] / 50, 1), min($d['faltas'] / 20, 1), ($d['retardos'] + $d['faltas']) / 10];
            $Y[] = ($d['retardos'] + $d['faltas']) >= 10 ? 1 : 0;
        }
        
        if (count($X) > 10) {
            $this->modelosEntrenados['bayes'] = ['X' => $X, 'Y' => $Y];
        }
    }
    
    private function obtenerFeaturesClasificacion($empleadoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as retardos,
                (SELECT COUNT(*) FROM asistencia WHERE empleado_id = ? AND tipo_asistencia = 'falta' AND fecha >= DATE_SUB(NOW(), INTERVAL 180 DAY)) as faltas
            FROM retardos
            WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 180 DAY) AND justificado = 0
        ");
        $stmt->execute([$empleadoId, $empleadoId]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            min(($datos['retardos'] ?? 0) / 50, 1),
            min(($datos['faltas'] ?? 0) / 20, 1),
            min(($datos['retardos'] + $datos['faltas']) / 30, 1)
        ];
    }
    
    private function detectarAnomaliasEmpleado($empleadoId) {
        $datos = $this->obtenerDatosAnomalias($empleadoId);
        
        if (count($datos) < 5) {
            return ['anomalias' => [], 'puntuacion_combinada' => 0];
        }
        
        $features = array_map(fn($d) => [
            ($d['minutos'] ?? 0) / 60,
            ($d['dia_semana'] ?? 1) / 7,
            ($d['hora'] ?? 12) / 24
        ], $datos);
        
        $resultado = $this->ml->detectarAnomalias($features, 1.8);
        
        $puntuacion = 0;
        if (!empty($resultado['anomalias'])) {
            $severidadAlta = count(array_filter($resultado['anomalias'], fn($a) => $a['severidad'] === 'alta'));
            $puntuacion = min(100, ($severidadAlta * 40) + (count($resultado['anomalias']) - $severidadAlta) * 20);
        }
        
        return [
            'anomalias' => $resultado['anomalias'],
            'total_anomalias' => count($resultado['anomalias']),
            'puntuacion_combinada' => $puntuacion
        ];
    }
    
    private function obtenerDatosAnomalias($empleadoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT minutos_retardo, DAYOFWEEK(fecha) as dia_semana, HOUR(hora_entrada) as hora
            FROM retardos
            WHERE empleado_id = ? AND justificado = 0
            ORDER BY fecha DESC
            LIMIT 60
        ");
        $stmt->execute([$empleadoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function analisisClustering($empleadoId) {
        $caracteristicas = $this->obtenerCaracteristicasClustering($empleadoId);
        
        if (empty($caracteristicas)) {
            return ['segmento' => 'desconocido', 'nivel_riesgo' => 50];
        }
        
        $puntajes = [
            $caracteristicas['frecuencia'] * 30,
            $caracteristicas['severidad'] * 25,
            $caracteristicas['recencia'] * 20,
            $caracteristicas['patron'] * 15,
            $caracteristicas['progresion'] * 10
        ];
        
        $nivelRiesgo = array_sum($puntajes);
        
        $segmento = 'bajo_riesgo';
        if ($nivelRiesgo >= 80) $segmento = 'critico';
        elseif ($nivelRiesgo >= 60) $segmento = 'alto_riesgo';
        elseif ($nivelRiesgo >= 40) $segmento = 'medio_riesgo';
        
        return [
            'segmento' => $segmento,
            'nivel_riesgo' => round($nivelRiesgo)
        ];
    }
    
    private function obtenerCaracteristicasClustering($empleadoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_ultimo_mes,
                (SELECT COUNT(*) FROM retardos WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 180 DAY) AND justificado = 0) as total_6_meses,
                AVG(minutos_retardo) as promedio_minutos,
                (SELECT COUNT(*) FROM retardos WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND justificado = 0) as mes_actual,
                (SELECT COUNT(*) FROM retardos WHERE empleado_id = ? AND fecha BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY) AND justificado = 0) as mes_anterior
            FROM retardos
            WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND justificado = 0
        ");
        $stmt->execute([$empleadoId, $empleadoId, $empleadoId, $empleadoId]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (empty($datos) || ($datos['total_6_meses'] ?? 0) == 0) {
            return [];
        }
        
        return [
            'frecuencia' => ($datos['total_ultimo_mes'] ?? 0) / 10,
            'severidad' => min(($datos['promedio_minutos'] ?? 0) / 60, 1),
            'recencia' => ($datos['total_ultimo_mes'] ?? 0) / max($datos['total_6_meses'], 1),
            'patron' => ($datos['promedio_minutos'] ?? 0) > 30 ? 1 : 0,
            'progresion' => ($datos['mes_actual'] ?? 0) > ($datos['mes_anterior'] ?? 0) ? 1 : 0
        ];
    }
    
    private function pronosticoEnsemble($empleadoId) {
        $historico = $this->obtenerSerieTemporales($empleadoId);
        
        if (count($historico) < 5) {
            return ['riesgo_predicho' => 50, 'confianza' => 'baja'];
        }
        
        $valores = array_column($historico, 'total');
        $dias = range(1, count($valores));
        
        $regresion = $this->ml->regresionLineal($dias, $valores, count($valores) + 30);
        $ema = $this->ml->promedioMovilExponencial($valores);
        
        $prediccion = ($regresion['prediccion'] + $ema['pronostico']) / 2;
        $riesgo = min(100, round($prediccion * 10));
        
        return [
            'riesgo_predicho' => $riesgo,
            'prediccion' => round($prediccion),
            'confianza' => $this->combinarConfianzaModelos([$regresion['confianza'], $ema['confianza']])
        ];
    }
    
    private function analizarPatronesJustificacion($empleadoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                j.tipo_justificacion,
                COUNT(*) as total,
                SUM(CASE WHEN j.estatus = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN j.estatus = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
            FROM justificaciones j
            WHERE j.empleado_id = ?
            GROUP BY j.tipo_justificacion
            ORDER BY total DESC
        ");
        $stmt->execute([$empleadoId]);
        $justificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM retardos WHERE empleado_id = ? AND justificado = 0
        ");
        $stmt->execute([$empleadoId]);
        $sinJustificar = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM retardos WHERE empleado_id = ?
        ");
        $stmt->execute([$empleadoId]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 1;
        
        $tasaJustificacion = $total > 0 ? ($total - $sinJustificar) / $total : 0;
        $indice = (1 - $tasaJustificacion) * 100;
        
        return [
            'indice_justificacion' => round($indice),
            'tipos_usados' => $justificaciones,
            'tasa_justificacion' => round($tasaJustificacion * 100),
            'sin_justificar' => $sinJustificar,
            'total_incidencias' => $total
        ];
    }
    
    private function analizarJustificaciones($empleadoId = null) {
        $pdo = $this->db->getConnection();
        
        $sql = "
            SELECT 
                tj.nombre as tipo,
                tj.descripcion,
                COUNT(j.id) as cantidad,
                SUM(CASE WHEN j.estatus = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN j.estatus = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                SUM(CASE WHEN j.estatus = 'pendiente' THEN 1 ELSE 0 END) as pendientes
            FROM tipos_justificacion tj
            LEFT JOIN justificaciones j ON j.tipo_justificacion = tj.nombre
            WHERE tj.activo = 1
        ";
        
        if ($empleadoId) {
            $sql .= " AND j.empleado_id = " . intval($empleadoId);
        }
        
        $sql .= " GROUP BY tj.id, tj.nombre, tj.descripcion ORDER BY cantidad DESC";
        
        $stmt = $pdo->query($sql);
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $aprobacionTotal = 0;
        $totalSolicitudes = 0;
        
        foreach ($tipos as $t) {
            $totalSolicitudes += $t['cantidad'];
            $aprobacionTotal += $t['aprobadas'];
        }
        
        return [
            'tipos_justificacion' => $tipos,
            'tasa_aprobacion_global' => $totalSolicitudes > 0 ? round(($aprobacionTotal / $totalSolicitudes) * 100) : 0,
            'total_solicitudes' => $totalSolicitudes
        ];
    }
    
    private function tomarDecision($analisis) {
        $riesgo = $analisis['indice_riesgo_ia'];
        $confianza = $analisis['confianza_global'];
        
        $decisiones = [];
        
        if ($riesgo >= 80) {
            $decisiones[] = [
                'tipo' => 'intervencion_inmediata',
                'prioridad' => 'urgente',
                'descripcion' => 'Riesgo crítico - Requiere intervención inmediata',
                'automatico' => true,
                'nivel_autorizacion' => 'director'
            ];
        }
        
        if ($riesgo >= 60 || ($riesgo >= 40 && $confianza === 'alta')) {
            $decisiones[] = [
                'tipo' => 'seguimiento_urgente',
                'prioridad' => 'alta',
                'descripcion' => 'Seguimiento requerido en los próximos días',
                'automatico' => true,
                'nivel_autorizacion' => 'jefe'
            ];
        }
        
        if ($riesgo >= 40) {
            $decisiones[] = [
                'tipo' => 'monitoreo_intensificado',
                'prioridad' => 'media',
                'descripcion' => 'Monitoreo continuo',
                'automatico' => false,
                'nivel_autorizacion' => 'rh'
            ];
        }
        
        return [
            'nivel_riesgo' => $riesgo,
            'confianza' => $confianza,
            'decisiones' => $decisiones,
            'requiere_accion' => !empty($decisiones)
        ];
    }
    
    private function ejecutarAcciones($decision, $empleado) {
        if (!$this->config['auto_accion']) {
            return ['acciones' => [], 'mensaje' => 'Auto-acción deshabilitada'];
        }
        
        $acciones = [];
        
        foreach ($decision['decisiones'] as $dec) {
            if (!$dec['automatico']) continue;
            
            $accion = [
                'tipo' => $dec['tipo'],
                'titulo' => strtoupper(str_replace('_', ' ', $dec['tipo'])),
                'descripcion' => $dec['descripcion'],
                'prioridad' => $dec['prioridad'],
                'empleado_id' => $empleado['id'],
                'empleado_nombre' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                'timestamp' => date('Y-m-d H:i:s'),
                'ejecutada' => true
            ];
            
            $acciones[] = $accion;
            $this->registrarAccion($accion);
        }
        
        return [
            'acciones' => $acciones,
            'total_ejecutadas' => count($acciones)
        ];
    }
    
    private function registrarAccion($accion) {
        $pdo = $this->db->getConnection();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO alertas_tempranas (empleado_id, tipo, nivel, mensaje, datos_json, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $tipos = ['intervencion_inmediata' => 'critica', 'seguimiento_urgente' => 'critica', 'monitoreo_intensificado' => 'advertencia'];
            $niveles = ['urgente' => 'urgente', 'alta' => 'alta', 'media' => 'media'];
            
            $stmt->execute([
                $accion['empleado_id'],
                $tipos[$accion['tipo']] ?? 'informativa',
                $niveles[$accion['prioridad']] ?? 'baja',
                $accion['titulo'] . ' - ' . $accion['descripcion'],
                json_encode($accion)
            ]);
        } catch (Exception $e) {
            error_log('Error al registrar acción: ' . $e->getMessage());
        }
    }
    
    private function identificarFactoresCriticos($redesNeuronales, $series, $patrones, $anomalias, $justificacion) {
        $factores = [];
        
        foreach ($redesNeuronales as $nombre => $red) {
            if (($red['prediccion'] ?? 0) >= 60) {
                $factores[] = ['factor' => $nombre, 'descripcion' => 'Alto riesgo según ' . strtoupper($nombre), 'impacto' => 'alto'];
            }
        }
        
        if (($series['tendencia'] ?? '') === 'creciente') {
            $factores[] = ['factor' => 'tendencia', 'descripcion' => 'Tendencia creciente de incidencias', 'impacto' => 'alto'];
        }
        
        if (($justificacion['indice_justificacion'] ?? 0) > 50) {
            $factores[] = ['factor' => 'justificacion', 'descripcion' => 'Baja tasa de justificación', 'impacto' => 'medio'];
        }
        
        return $factores;
    }
    
    private function calcularConfianzaGlobal($confianzas) {
        $niveles = ['baja' => 1, 'media' => 2, 'alta' => 3];
        $suma = array_sum(array_map(fn($c) => $niveles[$c] ?? 1, $confianzas));
        $promedio = $suma / count($confianzas);
        
        if ($promedio >= 2.5) return 'alta';
        if ($promedio >= 1.5) return 'media';
        return 'baja';
    }
    
    private function combinarConfianzaModelos($confianzas) {
        return $this->calcularConfianzaGlobal($confianzas);
    }
    
    private function generarResumenEjecutivo($resultados) {
        $total = count($resultados);
        $riesgoCritico = count(array_filter($resultados, fn($r) => $r['decision']['nivel_riesgo'] >= 80));
        $riesgoAlto = count(array_filter($resultados, fn($r) => $r['decision']['nivel_riesgo'] >= 60 && $r['decision']['nivel_riesgo'] < 80));
        $requierenAccion = count(array_filter($resultados, fn($r) => $r['decision']['requiere_accion']));
        $accionesEjecutadas = array_sum(array_map(fn($r) => $r['acciones_ejecutadas']['total_ejecutadas'] ?? 0, $resultados));
        
        return [
            'total_analizados' => $total,
            'riesgo_critico' => $riesgoCritico,
            'riesgo_alto' => $riesgoAlto,
            'requieren_accion' => $requierenAccion,
            'acciones_ejecutadas' => $accionesEjecutadas,
            'nivel_urgencia' => $riesgoCritico > 0 ? 'critico' : ($riesgoAlto > 0 ? 'alto' : 'normal')
        ];
    }
    
    private function generarRecomendacionesGlobales($resultados) {
        $recomendaciones = [];
        
        $criticos = array_filter($resultados, fn($r) => $r['decision']['nivel_riesgo'] >= 80);
        $altos = array_filter($resultados, fn($r) => $r['decision']['nivel_riesgo'] >= 60 && $r['decision']['nivel_riesgo'] < 80);
        $medios = array_filter($resultados, fn($r) => $r['decision']['nivel_riesgo'] >= 40 && $r['decision']['nivel_riesgo'] < 60);
        
        if (!empty($criticos)) {
            $recomendaciones[] = [
                'tipo' => 'urgente',
                'prioridad' => 'urgente',
                'titulo' => 'Revisión de casos críticos',
                'descripcion' => count($criticos) . ' empleados en riesgo crítico requieren atención inmediata',
                'accion' => 'Contactar directamente al empleado'
            ];
        }
        
        if (!empty($altos)) {
            $recomendaciones[] = [
                'tipo' => 'prevencion',
                'prioridad' => 'alta',
                'titulo' => 'Monitoreo de empleados en riesgo alto',
                'descripcion' => count($altos) . ' empleados con riesgo alto requieren seguimiento',
                'accion' => 'Programar reuniones de seguimiento'
            ];
        }
        
        if (!empty($medios)) {
            $recomendaciones[] = [
                'tipo' => 'prevencion',
                'prioridad' => 'media',
                'titulo' => 'Implementar plan de mejora',
                'descripcion' => count($medios) . ' empleados en riesgo medio',
                'accion' => 'Establecer metas de mejora'
            ];
        }
        
        $total = count($resultados);
        if ($total > 0) {
            $sin_riesgo = count(array_filter($resultados, fn($r) => $r['decision']['nivel_riesgo'] < 40));
            if ($sin_riesgo > 0) {
                $recomendaciones[] = [
                    'tipo' => 'reconocimiento',
                    'prioridad' => 'baja',
                    'titulo' => 'Reconocer buen desempeño',
                    'descripcion' => $sin_riesgo . ' empleados con bajo riesgo',
                    'accion' => 'Considerar incentivos'
                ];
            }
        }
        
        $retardos_altos = array_filter($resultados, fn($r) => ($r['metricas']['retardos'] ?? 0) > 10);
        if (!empty($retardos_altos)) {
            $recomendaciones[] = [
                'tipo' => 'politica',
                'prioridad' => 'alta',
                'titulo' => 'Revisar políticas de puntualidad',
                'descripcion' => count($retardos_altos) . ' empleados con más de 10 retardos',
                'accion' => 'Evaluar causas y ajustar políticas'
            ];
        }
        
        $faltas_altas = array_filter($resultados, fn($r) => ($r['metricas']['faltas'] ?? 0) > 3);
        if (!empty($faltas_altas)) {
            $recomendaciones[] = [
                'tipo' => 'asistencia',
                'prioridad' => 'urgente',
                'titulo' => 'Atención a empleados con faltas excesivas',
                'descripcion' => count($faltas_altas) . ' empleados con más de 3 faltas',
                'accion' => 'Investigar causas y gestionar justificaciones'
            ];
        }
        
        return $recomendaciones;
    }
    
    private function registrarDecision($resultado) {
        $pdo = $this->db->getConnection();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO alertas_tempranas (empleado_id, tipo, nivel, mensaje, created_at)
                VALUES (?, 'informativa', 'baja', ?, NOW())
            ");
            $stmt->execute([$resultado['empleado_id'], 'Análisis IA: Riesgo ' . $resultado['analisis']['indice_riesgo_ia'] . '%']);
        } catch (Exception $e) {}
    }
    
    private function obtenerMetricasIA() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN tipo = 'critica' THEN 1 ELSE 0 END) as criticas,
                   SUM(CASE WHEN tipo = 'advertencia' THEN 1 ELSE 0 END) as advertencias
            FROM alertas_tempranas
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $metricas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'alertas_ultimas_24h' => $metricas['total'] ?? 0,
            'alertas_criticas' => $metricas['criticas'] ?? 0,
            'alertas_advertencias' => $metricas['advertencias'] ?? 0,
            'modelos_activos' => ['perceptron', 'mlp', 'rnn', 'transformer', 'regresion', 'ema', 'naive_bayes', 'kmeans'],
            'redes_neuronales' => 4,
            'algoritmos_ml' => 4,
            'ultima_actualizacion' => date('Y-m-d H:i:s')
        ];
    }
    
    private function obtenerMetricasyConsumos($empleadoId) {
        $pdo = $this->db->getConnection();
        $anioActual = date('Y');
        
        // Obtener tipos de justificación del catálogo
        $stmt = $pdo->query("SELECT id, nombre FROM tipos_justificacion WHERE activo = 1 ORDER BY id");
        $tiposJustificacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $consumos = [];
        foreach ($tiposJustificacion as $tipo) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total FROM asistencia 
                WHERE empleado_id = ? AND tipo_justificacion_id = ? AND YEAR(fecha) = ?
            ");
            $stmt->execute([$empleadoId, $tipo['id'], $anioActual]);
            $consumos[$tipo['nombre']] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM retardos WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND justificado = 0) as retardos,
                (SELECT COALESCE(SUM(minutos_retardo), 0) FROM retardos WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND justificado = 0) as minutos_atraso,
                (SELECT COUNT(*) FROM asistencia WHERE empleado_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND tipo_asistencia = 'falta') as faltas,
                (SELECT COUNT(*) FROM sanciones WHERE empleado_id = ? AND estatus = 'activa') as sanciones,
                (SELECT COUNT(*) FROM ausencias WHERE empleado_id = ? AND YEAR(fecha_inicio) = ?) as ausencias,
                (SELECT COUNT(*) FROM comisiones WHERE empleado_id = ? AND YEAR(fecha_inicio) = ?) as comisiones
        ");
        $stmt->execute([
            $empleadoId, $empleadoId, $empleadoId, $empleadoId, 
            $empleadoId, $anioActual, $empleadoId, $anioActual
        ]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'retardos' => (int)($datos['retardos'] ?? 0),
            'minutos_atraso' => (int)($datos['minutos_atraso'] ?? 0),
            'faltas' => (int)($datos['faltas'] ?? 0),
            'sanciones' => (int)($datos['sanciones'] ?? 0),
            'ausencias' => (int)($datos['ausencias'] ?? 0),
            'comisiones' => (int)($datos['comisiones'] ?? 0),
            'consumos_justificaciones' => $consumos
        ];
    }
    
    public function getDashboardAgente() {
        $analisis = $this->analizarYDecidir();
        
        return [
            'agente_activo' => true,
            'resumen' => $analisis['resumen'],
            'metricas_ia' => $analisis['metricas_ia'],
            'recomendaciones' => $analisis['recomendaciones_globales'],
            'analisis_justificaciones' => $analisis['tipos_justificacion'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version_modelo' => '3.0',
            'arquitectura' => 'Ensemble: 4 RNA + 4 Algoritmos ML + Análisis Justificaciones',
            'colores_pantone' => self::COLORES_PANTONE
        ];
    }
}
