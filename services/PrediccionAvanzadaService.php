<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/MachineLearningEngine.php';

class PrediccionAvanzadaService {
    private $db;
    private $ml;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->ml = new MachineLearningEngine();
    }
    
    public function predecirRiesgoEmpleado($empleadoId, $diasFuturo = 30) {
        $historial = $this->obtenerHistorialCompleto($empleadoId);
        
        if (count($historial) < 10) {
            return [
                'success' => false,
                'mensaje' => 'Datos insuficientes para predicción',
                'datos_necesarios' => 10 - count($historial)
            ];
        }
        
        $prediccionRegresion = $this->prediccionRegresionLineal($historial, $diasFuturo);
        $prediccionEMA = $this->prediccionEMA($historial, $diasFuturo);
        $prediccionPolinomial = $this->prediccionPolinomial($historial, $diasFuturo);
        $prediccionLSTM = $this->prediccionLSTMSimplificada($historial, $diasFuturo);
        
        $pesos = [
            'regresion' => 0.25,
            'ema' => 0.30,
            'polinomial' => 0.20,
            'lstm' => 0.25
        ];
        
        $prediccionCombinada = (
            $prediccionRegresion['prediccion'] * $pesos['regresion'] +
            $prediccionEMA['prediccion'] * $pesos['ema'] +
            $prediccionPolinomial['prediccion'] * $pesos['polinomial'] +
            $prediccionLSTM['prediccion'] * $pesos['lstm']
        );
        
        $confianza = $this->calcularConfianzaPrediccion($historial, [
            $prediccionRegresion,
            $prediccionEMA,
            $prediccionPolinomial,
            $prediccionLSTM
        ]);
        
        return [
            'success' => true,
            'empleado_id' => $empleadoId,
            'dias_prediccion' => $diasFuturo,
            'prediccion' => round($prediccionCombinada, 2),
            'predicciones_individuales' => [
                'regresion_lineal' => $prediccionRegresion,
                'ema' => $prediccionEMA,
                'polinomial' => $prediccionPolinomial,
                'lstm' => $prediccionLSTM
            ],
            'confianza' => $confianza,
            'nivel_riesgo' => $prediccionCombinada >= 80 ? 'critico' : ($prediccionCombinada >= 60 ? 'alto' : ($prediccionCombinada >= 40 ? 'medio' : 'bajo')),
            'recomendaciones' => $this->generarRecomendacionesPrediccion($prediccionCombinada, $diasFuturo),
            'datos_historicos' => count($historial)
        ];
    }
    
    private function obtenerHistorialCompleto($empleadoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE(fecha) as fecha,
                COUNT(CASE WHEN tipo_asistencia = 'falta' THEN 1 END) as faltas,
                (SELECT COUNT(*) FROM retardos r WHERE r.empleado_id = a.empleado_id AND DATE(r.fecha) = DATE(a.fecha)) as retardos
            FROM asistencia a
            WHERE a.empleado_id = ? AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
            GROUP BY DATE(a.fecha)
            ORDER BY fecha ASC
        ");
        $stmt->execute([$empleadoId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function prediccionRegresionLineal($historial, $dias) {
        $x = array_keys($historial);
        $y = array_map(fn($h) => ($h['retardos'] ?? 0) + ($h['faltas'] ?? 0) * 3, $historial);
        
        $xNumerico = array_map(fn($f, $i) => $i, $x, array_keys($x));
        
        $resultado = $this->ml->regresionLineal($xNumerico, $y, count($xNumerico) + $dias);
        
        return [
            'prediccion' => max(0, round($resultado['prediccion'], 2)),
            'tendencia' => $resultado['m'] > 0 ? 'aumentando' : 'decreciente',
            'r2' => $resultado['r2']
        ];
    }
    
    private function prediccionEMA($historial, $dias) {
        $valores = array_map(fn($h) => ($h['retardos'] ?? 0) + ($h['faltas'] ?? 0) * 3, $historial);
        
        $resultado = $this->ml->promedioMovilExponencial($valores, $dias, 0.3);
        
        return [
            'prediccion' => max(0, round($resultado['pronostico'], 2)),
            'limite_inferior' => round($resultado['limite_inferior'], 2),
            'limite_superior' => round($resultado['limite_superior'], 2),
            'confianza' => $resultado['confianza']
        ];
    }
    
    private function prediccionPolinomial($historial, $dias) {
        $valores = array_map(fn($h) => ($h['retardos'] ?? 0) + ($h['faltas'] ?? 0) * 3, $historial);
        
        $n = count($valores);
        if ($n < 3) {
            return ['prediccion' => array_sum($valores) / max($n, 1), 'grado' => 1];
        }
        
        $x = range(0, $n - 1);
        
        $sumX = array_sum($x);
        $sumY = array_sum($valores);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $valores[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        
        $n = count($x);
        $mediaX = $sumX / $n;
        $mediaY = $sumY / $n;
        
        $numerador = $sumXY - $n * $mediaX * $mediaY;
        $denominador = $sumX2 - $n * $mediaX * $mediaX;
        
        $m = $denominador != 0 ? $numerador / $denominador : 0;
        $b = $mediaY - $m * $mediaX;
        
        $prediccion = $m * ($n + $dias) + $b;
        
        return [
            'prediccion' => max(0, round($prediccion, 2)),
            'tendencia' => $m > 0.1 ? 'aumentando' : ($m < -0.1 ? 'decreciente' : 'estable'),
            'pendiente' => round($m, 4)
        ];
    }
    
    private function prediccionLSTMSimplificada($historial, $dias) {
        $valores = array_map(fn($h) => ($h['retardos'] ?? 0) + ($h['faltas'] ?? 0) * 3, $historial);
        
        $ventana = min(7, count($valores));
        
        if ($ventana < 3) {
            return ['prediccion' => array_sum($valores) / max(count($valores), 1)];
        }
        
        $ventanas = [];
        for ($i = 0; $i <= count($valores) - $ventana; $i++) {
            $ventanas[] = array_slice($valores, $i, $ventana);
        }
        
        $pesosVentana = array_map(fn($v) => array_sum($v) / count($v), $ventanas);
        
        $ultimaVentana = end($ventanas);
        $tendencia = 0;
        if (count($ventanas) >= 2) {
            $penultima = $ventanas[count($ventanas) - 2];
            $tendencia = (array_sum($ultimaVentana) / count($ultimaVentana)) - 
                        (array_sum($penultima) / count($penultima));
        }
        
        $base = array_sum($ultimaVentana) / count($ultimaVentana);
        $prediccion = $base + ($tendencia * $dias * 0.3);
        
        return [
            'prediccion' => max(0, round($prediccion, 2)),
            'base' => round($base, 2),
            'tendencia' => $tendencia > 0.5 ? 'aumentando' : ($tendencia < -0.5 ? 'decreciente' : 'estable')
        ];
    }
    
    private function calcularConfianzaPrediccion($historial, $predicciones) {
        $valoresPredichos = array_column($predicciones, 'prediccion');
        
        $desviacion = $this->ml->desviacionEstandar($valoresPredichos);
        $media = array_sum($valoresPredichos) / count($valoresPredichos);
        
        $coeficienteVariacion = $media > 0 ? $desviacion / $media : 1;
        
        $factorDatos = min(1, count($historial) / 90);
        
        $confianzaBase = (1 - $coeficienteVariacion) * $factorDatos * 100;
        
        if ($confianzaBase >= 80) return 'alta';
        if ($confianzaBase >= 50) return 'media';
        return 'baja';
    }
    
    private function generarRecomendacionesPrediccion($prediccion, $dias) {
        $recomendaciones = [];
        
        if ($prediccion >= 15) {
            $recomendaciones[] = [
                'tipo' => 'urgente',
                'mensaje' => 'Alto riesgo de incidencias. Se predicen ' . round($prediccion) . ' incidencias en ' . $dias . ' días.',
                'accion' => 'Programar revisión inmediata con el empleado'
            ];
        } elseif ($prediccion >= 8) {
            $recomendaciones[] = [
                'tipo' => 'preventivo',
                'mensaje' => 'Riesgo moderado. Monitorear de cerca.',
                'accion' => 'Establecer recordatorios de puntualidad'
            ];
        } else {
            $recomendaciones[] = [
                'tipo' => 'informativo',
                'mensaje' => 'Bajo riesgo proyectado.',
                'accion' => 'Continuar con seguimiento regular'
            ];
        }
        
        return $recomendaciones;
    }
    
    public function predecirRiesgoGlobal($diasFuturo = 30) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->query("SELECT id FROM empleados WHERE activo = 1");
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultados = [];
        $totalRiesgo = 0;
        $altosRiesgos = 0;
        
        foreach ($empleados as $emp) {
            $prediccion = $this->predecirRiesgoEmpleado($emp['id'], $diasFuturo);
            
            if ($prediccion['success']) {
                $resultados[] = [
                    'empleado_id' => $emp['id'],
                    'prediccion' => $prediccion['prediccion'],
                    'nivel_riesgo' => $prediccion['nivel_riesgo']
                ];
                
                $totalRiesgo += $prediccion['prediccion'];
                
                if ($prediccion['nivel_riesgo'] === 'alto' || $prediccion['nivel_riesgo'] === 'critico') {
                    $altosRiesgos++;
                }
            }
        }
        
        usort($resultados, fn($a, $b) => $b['prediccion'] <=> $a['prediccion']);
        
        return [
            'periodo' => $diasFuturo . ' días',
            'total_empleados' => count($resultados),
            'promedio_riesgo' => count($resultados) > 0 ? round($totalRiesgo / count($resultados), 2) : 0,
            'empleados_alto_riesgo' => $altosRiesgos,
            'top_riesgos' => array_slice($resultados, 0, 20),
            'distribucion' => [
                'critico' => count(array_filter($resultados, fn($r) => $r['nivel_riesgo'] === 'critico')),
                'alto' => count(array_filter($resultados, fn($r) => $r['nivel_riesgo'] === 'alto')),
                'medio' => count(array_filter($resultados, fn($r) => $r['nivel_riesgo'] === 'medio')),
                'bajo' => count(array_filter($resultados, fn($r) => $r['nivel_riesgo'] === 'bajo'))
            ]
        ];
    }
    
    public function analisisEstacionalidad($empleadoId = null) {
        $pdo = $this->db->getConnection();
        
        $sql = "
            SELECT 
                DAYOFWEEK(fecha) as dia_semana,
                MONTH(fecha) as mes,
                COUNT(*) as total,
                SUM(CASE WHEN tipo_asistencia = 'falta' THEN 1 ELSE 0 END) as faltas
            FROM asistencia
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
            " . ($empleadoId ? "AND empleado_id = ?" : "") . "
            GROUP BY DAYOFWEEK(fecha), MONTH(fecha)
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($empleadoId ? [$empleadoId] : []);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $diasSemana = [];
        $meses = [];
        
        foreach ($datos as $d) {
            $dia = $d['dia_semana'];
            $diasSemana[$dia] = ($diasSemana[$dia] ?? 0) + $d['total'];
            
            $mes = $d['mes'];
            $meses[$mes] = ($meses[$mes] ?? 0) + $d['total'];
        }
        
        $diasNombres = [1 => 'Domingo', 2 => 'Lunes', 3 => 'Martes', 4 => 'Miércoles', 5 => 'Jueves', 6 => 'Viernes', 7 => 'Sábado'];
        
        $patrones = [];
        
        if (isset($diasSemana[2]) && isset($diasSemana[6])) {
            if ($diasSemana[2] > $diasSemana[6] * 1.3) {
                $patrones[] = 'lunes_problematico';
            }
        }
        
        if (isset($diasSemana[5]) && $diasSemana[5] > 0) {
            $patrones[] = 'viernes_alto';
        }
        
        $resultadoDias = [];
        foreach ($diasSemana as $i => $d) {
            $resultadoDias[] = ['dia' => $diasNombres[$i] ?? $i, 'total' => $d];
        }
        
        return [
            'empleado_id' => $empleadoId,
            'dias_semana' => $resultadoDias,
            'meses' => $meses,
            'patrones_detectados' => $patrones,
            'recomendaciones' => $this->generarRecomendacionesEstacionalidad($patrones)
        ];
    }
    
    private function generarRecomendacionesEstacionalidad($patrones) {
        $recomendaciones = [];
        
        if (in_array('lunes_problematico', $patrones)) {
            $recomendaciones[] = 'Los lunes muestran mayor índice de incidencias. Considerar incentivos para asistencia los lunes.';
        }
        
        if (in_array('viernes_alto', $patrones)) {
            $recomendaciones[] = 'Los viernes requieren seguimiento especial.';
        }
        
        return $recomendaciones;
    }
}
