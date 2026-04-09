<?php
/**
 * Motor de Machine Learning para Análisis Predictivo
 * Implementa algoritmos de ML y redes neuronales para predicción de riesgos
 */

class MachineLearningEngine {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Normaliza datos al rango [0, 1]
     */
    public function normalizar(array $datos): array {
        if (empty($datos)) return [];
        
        $min = min($datos);
        $max = max($datos);
        $rango = $max - $min;
        
        if ($rango == 0) {
            return array_fill(0, count($datos), 0.5);
        }
        
        return array_map(fn($x) => ($x - $min) / $rango, $datos);
    }
    
    /**
     * Calcula la media de un conjunto de datos
     */
    public function media(array $datos): float {
        if (empty($datos)) return 0;
        return array_sum($datos) / count($datos);
    }
    
    /**
     * Calcula la desviación estándar
     */
    public function desviacionEstandar(array $datos): float {
        if (count($datos) < 2) return 0;
        
        $media = $this->media($datos);
        $varianza = array_sum(array_map(fn($x) => pow($x - $media, 2), $datos)) / (count($datos) - 1);
        return sqrt($varianza);
    }
    
    /**
     * Calcula la correlación de Pearson entre dos variables
     */
    public function correlacionPearson(array $x, array $y): float {
        $n = count($x);
        if ($n !== count($y) || $n < 2) return 0;
        
        $mediaX = $this->media($x);
        $mediaY = $this->media($y);
        $desvX = $this->desviacionEstandar($x);
        $desvY = $this->desviacionEstandar($y);
        
        if ($desvX == 0 || $desvY == 0) return 0;
        
        $covarianza = 0;
        for ($i = 0; $i < $n; $i++) {
            $covarianza += ($x[$i] - $mediaX) * ($y[$i] - $mediaY);
        }
        $covarianza /= ($n - 1);
        
        return $covarianza / ($desvX * $desvY);
    }
    
    /**
     * Regresión Lineal Simple - Predice valores futuros basados en tendencias
     * y = mx + b
     */
    public function regresionLineal(array $x, array $y, float $x_prediccion): array {
        $n = count($x);
        if ($n !== count($y) || $n < 2) {
            return ['prediccion' => 0, 'm' => 0, 'b' => 0, 'r2' => 0, 'confianza' => 'baja'];
        }
        
        $mediaX = $this->media($x);
        $mediaY = $this->media($y);
        
        $numerador = 0;
        $denominador = 0;
        for ($i = 0; $i < $n; $i++) {
            $numerador += ($x[$i] - $mediaX) * ($y[$i] - $mediaY);
            $denominador += pow($x[$i] - $mediaX, 2);
        }
        
        if ($denominador == 0) {
            return ['prediccion' => $mediaY, 'm' => 0, 'b' => $mediaY, 'r2' => 0, 'confianza' => 'baja'];
        }
        
        $m = $numerador / $denominador;
        $b = $mediaY - ($m * $mediaX);
        $prediccion = $m * $x_prediccion + $b;
        
        $ssTotal = 0;
        $ssResidual = 0;
        for ($i = 0; $i < $n; $i++) {
            $ssTotal += pow($y[$i] - $mediaY, 2);
            $ssResidual += pow($y[$i] - ($m * $x[$i] + $b), 2);
        }
        
        $r2 = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;
        $confianza = abs($r2) >= 0.7 ? 'alta' : (abs($r2) >= 0.4 ? 'media' : 'baja');
        
        return [
            'prediccion' => max(0, round($prediccion)),
            'm' => round($m, 4),
            'b' => round($b, 4),
            'r2' => round($r2, 4),
            'confianza' => $confianza,
            'tendencia' => $m > 0 ? 'creciente' : 'decreciente'
        ];
    }
    
    /**
     * Regresión Polinomial de grado 2 para capturar curvas no lineales
     */
    public function regresionPolinomial(array $x, array $y, float $x_prediccion): array {
        $n = count($x);
        if ($n !== count($y) || $n < 3) {
            return $this->regresionLineal($x, $y, $x_prediccion);
        }
        
        $sumaX = array_sum($x);
        $sumaY = array_sum($y);
        $sumaX2 = array_sum(array_map('pow', $x, array_fill(0, $n, 2)));
        $sumaX3 = array_sum(array_map('pow', $x, array_fill(0, $n, 3)));
        $sumaX4 = array_sum(array_map('pow', $x, array_fill(0, $n, 4)));
        $sumaXY = 0;
        $sumaX2Y = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumaXY += $x[$i] * $y[$i];
            $sumaX2Y += $x[$i] * $x[$i] * $y[$i];
        }
        
        $n_float = $n;
        $det = $n_float * ($sumaX2 * $sumaX4 - $sumaX3 * $sumaX3) 
             - $sumaX * ($sumaX * $sumaX4 - $sumaX2 * $sumaX3) 
             + $sumaX2 * ($sumaX * $sumaX3 - $sumaX2 * $sumaX2);
        
        if (abs($det) < 0.0001) {
            return $this->regresionLineal($x, $y, $x_prediccion);
        }
        
        $a = ($n_float * ($sumaX2 * $sumaX2Y - $sumaX3 * $sumaXY) 
            - $sumaX * ($sumaX * $sumaX2Y - $sumaX3 * $sumaY) 
            + $sumaX2 * ($sumaX * $sumaXY - $sumaX2 * $sumaY)) / $det;
        
        $b = ($n_float * ($sumaX2 * $sumaX2Y - $sumaX3 * $sumaXY) 
            - $sumaX * ($sumaX * $sumaX2Y - $sumaX3 * $sumaY) 
            + $sumaX2 * ($sumaX * $sumaXY - $sumaX2 * $sumaY)) / $det;
        
        $c = ($sumaY - $b * $sumaX - $a * $sumaX2) / $n_float;
        
        $prediccion = $a * pow($x_prediccion, 2) + $b * $x_prediccion + $c;
        
        $mediaY = $this->media($y);
        $ssTotal = 0;
        $ssResidual = 0;
        for ($i = 0; $i < $n; $i++) {
            $ssTotal += pow($y[$i] - $mediaY, 2);
            $ssResidual += pow($y[$i] - ($a * pow($x[$i], 2) + $b * $x[$i] + $c), 2);
        }
        
        $r2 = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;
        
        return [
            'prediccion' => max(0, round($prediccion)),
            'coeficientes' => ['a' => round($a, 6), 'b' => round($b, 4), 'c' => round($c, 4)],
            'r2' => round($r2, 4),
            'confianza' => $r2 >= 0.7 ? 'alta' : ($r2 >= 0.4 ? 'media' : 'baja'),
            'tipo' => 'polinomial'
        ];
    }
    
    /**
     * K-Means Clustering - Agrupa empleados por nivel de riesgo
     */
    public function kMeansClustering(array $datos, int $k = 4, int $maxIteraciones = 100): array {
        if (count($datos) < $k) {
            $k = count($datos) ?: 1;
        }
        
        $caracteristicas = array_column($datos, 'caracteristicas');
        
        $centroids = array_slice($caracteristicas, 0, $k);
        $clusters = array_fill(0, $k, []);
        
        for ($iteracion = 0; $iteracion < $maxIteraciones; $iteracion++) {
            $clusters = array_fill(0, $k, []);
            
            foreach ($caracteristicas as $idx => $caract) {
                $minDist = PHP_FLOAT_MAX;
                $clusterIdx = 0;
                
                foreach ($centroids as $cIdx => $centroid) {
                    $dist = $this->distanciaEuclidiana($caract, $centroid);
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $clusterIdx = $cIdx;
                    }
                }
                
                $clusters[$clusterIdx][] = $idx;
            }
            
            $nuevosCentroid = [];
            for ($c = 0; $c < $k; $c++) {
                if (empty($clusters[$c])) {
                    $nuevosCentroid[] = $centroids[$c];
                    continue;
                }
                
                $nuevaMedia = [];
                $numCaract = count($centroids[$c]);
                
                for ($i = 0; $i < $numCaract; $i++) {
                    $suma = 0;
                    foreach ($clusters[$c] as $idx) {
                        $suma += $caracteristicas[$idx][$i] ?? 0;
                    }
                    $nuevaMedia[] = $suma / count($clusters[$c]);
                }
                $nuevosCentroid[] = $nuevaMedia;
            }
            
            if ($this->arraysIguales($centroids, $nuevosCentroid)) {
                break;
            }
            $centroids = $nuevosCentroid;
        }
        
        $resultados = [];
        foreach ($clusters as $cIdx => $indices) {
            if (empty($indices)) continue;
            
            foreach ($indices as $idx) {
                $resultados[$idx] = [
                    'cluster' => $cIdx,
                    'empleado_id' => $datos[$idx]['empleado_id'] ?? $idx,
                    'distancia' => $this->distanciaEuclidiana($caracteristicas[$idx], $centroids[$cIdx])
                ];
            }
        }
        
        return [
            'clusters' => $resultados,
            'centroids' => $centroids,
            'iteraciones' => $iteracion,
            'k' => $k
        ];
    }
    
    /**
     * Calcula la distancia euclidiana entre dos puntos
     */
    public function distanciaEuclidiana(array $a, array $b): float {
        $suma = 0;
        $n = min(count($a), count($b));
        
        for ($i = 0; $i < $n; $i++) {
            $suma += pow(($a[$i] ?? 0) - ($b[$i] ?? 0), 2);
        }
        
        return sqrt($suma);
    }
    
    /**
     * Perceptrón Simple - Red Neuronal básica para clasificación binaria
     */
    public function perceptron(array $entrenamientoX, array $entrenamientoY, array $entradaNueva, int $epocas = 100, float $tasaAprendizaje = 0.1): array {
        $nCaract = count($entrenamientoX[0] ?? []);
        $nMuestras = count($entrenamientoX);
        
        if ($nMuestras === 0 || $nCaract === 0) {
            return ['prediccion' => 0, 'probabilidad' => 0, 'confianza' => 'baja'];
        }
        
        $pesos = array_fill(0, $nCaract, (mt_rand(-100, 100) / 100));
        $bias = mt_rand(-100, 100) / 100;
        
        for ($epoca = 0; $epoca < $epocas; $epoca++) {
            $errorTotal = 0;
            
            for ($i = 0; $i < $nMuestras; $i++) {
                $suma = $bias;
                for ($j = 0; $j < $nCaract; $j++) {
                    $suma += $entrenamientoX[$i][$j] * $pesos[$j];
                }
                
                $prediccion = $this->sigmoide($suma);
                $error = $entrenamientoY[$i] - $prediccion;
                $errorTotal += abs($error);
                
                for ($j = 0; $j < $nCaract; $j++) {
                    $pesos[$j] += $tasaAprendizaje * $error * $entrenamientoX[$i][$j];
                }
                $bias += $tasaAprendizaje * $error;
            }
            
            if ($errorTotal < 0.01) break;
        }
        
        $sumaNueva = $bias;
        for ($j = 0; $j < count($entradaNueva); $j++) {
            $sumaNueva += ($entradaNueva[$j] ?? 0) * ($pesos[$j] ?? 0);
        }
        
        $probabilidad = $this->sigmoide($sumaNueva);
        $prediccion = $probabilidad >= 0.5 ? 1 : 0;
        
        $confianza = abs($probabilidad - 0.5) >= 0.3 ? 'alta' : (abs($probabilidad - 0.5) >= 0.15 ? 'media' : 'baja');
        
        return [
            'prediccion' => $prediccion,
            'probabilidad' => round($probabilidad * 100, 2),
            'confianza' => $confianza,
            'pesos' => array_map(fn($w) => round($w, 4), $pesos),
            'bias' => round($bias, 4)
        ];
    }
    
    /**
     * Red Neuronal feedforward multicapa simplificada
     */
    public function redNeuronalFeedforward(array $capas, array $entrada): array {
        $neuronas = [$entrada];
        
        foreach ($capas as $capaIdx => $numNeuronas) {
            $capaAnterior = end($neuronas);
            $nuevaCapa = [];
            
            for ($n = 0; $n < $numNeuronas; $n++) {
                $suma = 0;
                foreach ($capaAnterior as $idx => $valor) {
                    $suma += $valor * (mt_rand(-100, 100) / 100);
                }
                $suma += mt_rand(-50, 50) / 100;
                $nuevaCapa[] = $this->sigmoide($suma);
            }
            
            $neuronas[] = $nuevaCapa;
        }
        
        $salida = end($neuronas);
        $clase = array_keys($salida, max($salida))[0] ?? 0;
        
        return [
            'salida' => array_map(fn($v) => round($v, 4), $salida),
            'clase_predicha' => $clase,
            'probabilidades' => $salida
        ];
    }
    
    /**
     * Función Sigmoide
     */
    public function sigmoide(float $x): float {
        return 1 / (1 + exp(-$x));
    }
    
    /**
     * Algoritmo de Detección de Anomalías (Isolation Forest simplificado)
     */
    public function detectarAnomalias(array $datos, float $umbral = 2.0): array {
        $medias = [];
        $desviaciones = [];
        
        $numFeatures = count($datos[0] ?? []);
        for ($f = 0; $f < $numFeatures; $f++) {
            $valores = array_column($datos, $f);
            $medias[$f] = $this->media($valores);
            $desviaciones[$f] = $this->desviacionEstandar($valores) ?: 1;
        }
        
        $anomalias = [];
        foreach ($datos as $idx => $registro) {
            $puntuacionAnomalia = 0;
            
            for ($f = 0; $f < $numFeatures; $f++) {
                $z = abs(($registro[$f] - $medias[$f]) / $desviaciones[$f]);
                $puntuacionAnomalia += $z;
            }
            
            $puntuacionAnomalia /= $numFeatures;
            
            if ($puntuacionAnomalia > $umbral) {
                $anomalias[] = [
                    'indice' => $idx,
                    'puntuacion' => round($puntuacionAnomalia, 4),
                    'es_anomalia' => true,
                    'severidad' => $puntuacionAnomalia > $umbral * 1.5 ? 'alta' : 'media'
                ];
            }
        }
        
        usort($anomalias, fn($a, $b) => $b['puntuacion'] <=> $a['puntuacion']);
        
        return [
            'anomalias' => $anomalias,
            'total_anomalias' => count($anomalias),
            'porcentaje_anomalias' => count($datos) > 0 ? round(count($anomalias) / count($datos) * 100, 2) : 0,
            'umbral_utilizado' => $umbral
        ];
    }
    
    /**
     * Naive Bayes para clasificación de riesgo
     */
    public function naiveBayesClasificador(array $datosEntrenamiento, array $clases, array $entrada): array {
        $priors = [];
        $likelihoods = [];
        $clasesUnicas = array_unique($clases);
        
        foreach ($clasesUnicas as $clase) {
            $indices = array_keys($clases, $clase);
            $priors[$clase] = count($indices) / count($clases);
            
            $numFeatures = count($datosEntrenamiento[0] ?? []);
            $likelihoods[$clase] = [];
            
            for ($f = 0; $f < $numFeatures; $f++) {
                $valores = array_column($datosEntrenamiento, $f);
                $valoresClase = array_map(fn($i) => $valores[$i], $indices);
                
                $media = $this->media($valoresClase);
                $desv = $this->desviacionEstandar($valoresClase) ?: 0.1;
                
                $likelihoods[$clase][$f] = ['media' => $media, 'desv' => $desv];
            }
        }
        
        $posterioris = [];
        foreach ($clasesUnicas as $clase) {
            $posteriori = log($priors[$clase]);
            
            for ($f = 0; $f < count($entrada); $f++) {
                $media = $likelihoods[$clase][$f]['media'] ?? 0;
                $desv = $likelihoods[$clase][$f]['desv'] ?? 0.1;
                $x = $entrada[$f] ?? 0;
                
                $probabilidad = $this->distribucionNormal($x, $media, $desv);
                $posteriori += log($probabilidad + 0.0001);
            }
            
            $posterioris[$clase] = $posteriori;
        }
        
        $clasePredicha = array_keys($posterioris, max($posterioris))[0];
        
        $sumaPosteriori = array_sum(array_map('exp', $posterioris));
        $probabilidades = [];
        foreach ($posterioris as $clase => $post) {
            $probabilidades[$clase] = round(exp($post) / $sumaPosteriori * 100, 2);
        }
        
        return [
            'clase_predicha' => $clasePredicha,
            'probabilidades' => $probabilidades,
            'confianza' => ($probabilidades[$clasePredicha] ?? 0) >= 70 ? 'alta' : 'media'
        ];
    }
    
    /**
     * Distribución Normal (Gaussiana)
     */
    private function distribucionNormal(float $x, float $media, float $desv): float {
        if ($desv == 0) return 0;
        
        $coef = 1 / ($desv * sqrt(2 * M_PI));
        $expo = -pow($x - $media, 2) / (2 * pow($desv, 2));
        
        return $coef * exp($expo);
    }
    
    /**
     * Predicción de series temporales usando Promedio Móvil Exponencial (EMA)
     */
    public function promedioMovilExponencial(array $serie, int $diasPronostico = 30, float $alpha = 0.3): array {
        if (empty($serie)) {
            return ['pronostico' => 0, 'limite_inferior' => 0, 'limite_superior' => 0];
        }
        
        $ema = $serie[0];
        $varianzas = [];
        
        foreach ($serie as $valor) {
            $ema = $alpha * $valor + (1 - $alpha) * $ema;
            $varianzas[] = pow($valor - $ema, 2);
        }
        
        $desviacionTipica = sqrt(array_sum($varianzas) / count($varianzas));
        $pronostico = $ema;
        
        return [
            'pronostico' => round($pronostico),
            'ema_actual' => round($ema, 2),
            'desviacion_tipica' => round($desviacionTipica, 2),
            'limite_inferior' => round(max(0, $pronostico - 1.96 * $desviacionTipica)),
            'limite_superior' => round($pronostico + 1.96 * $desviacionTipica),
            'confianza' => $desviacionTipica < $ema * 0.2 ? 'alta' : 'media'
        ];
    }
    
    /**
     * Bosques Aleatorios simplificado (ensemble de árboles de decisión)
     */
    public function bosquesAleatorios(array $datosX, array $datosY, array $entrada, int $numArboles = 10): array {
        $predicciones = [];
        
        for ($i = 0; $i < $numArboles; $i++) {
            $indicesMuestra = array_map(fn() => mt_rand(0, count($datosX) - 1), range(0, count($datosX) - 1));
            $muestraX = array_map(fn($idx) => $datosX[$idx], $indicesMuestra);
            $muestraY = array_map(fn($idx) => $datosY[$idx], $indicesMuestra);
            
            $pred = $this->arbolDecisiones($muestraX, $muestraY, $entrada);
            $predicciones[] = $pred;
        }
        
        $suma = array_sum($predicciones);
        $prediccionFinal = $suma / $numArboles;
        
        return [
            'prediccion' => round($prediccionFinal),
            'votos' => array_count_values($predicciones),
            'confianza' => $this->calcularConfianzaVotos($predicciones)
        ];
    }
    
    /**
     * Árbol de Decisiones simplificado
     */
    public function arbolDecisiones(array $datosX, array $datosY, array $entrada): int {
        $nCaract = count($datosX[0] ?? []);
        
        if ($nCaract === 0) return 0;
        
        $umbral = array_sum(array_column($datosX, 0)) / count($datosX);
        $caractIdx = 0;
        $mejorGanancia = -1;
        
        for ($f = 0; $f < min($nCaract, 3); $f++) {
            $valores = array_column($datosX, $f);
            $candidatos = [$this->media($valores)];
            
            foreach ($candidatos as $u) {
                $ganancia = $this->calcularGananciaInformacion($datosX, $datosY, $f, $u);
                if ($ganancia > $mejorGanancia) {
                    $mejorGanancia = $ganancia;
                    $umbral = $u;
                    $caractIdx = $f;
                }
            }
        }
        
        $prediccion = ($entrada[$caractIdx] ?? 0) >= $umbral ? 1 : 0;
        
        return $prediccion;
    }
    
    /**
     * Calcula la ganancia de información para un split
     */
    private function calcularGananciaInformacion(array $datosX, array $datosY, int $featureIdx, float $umbral): float {
        $n = count($datosX);
        if ($n === 0) return 0;
        
        $entropiaPadre = $this->entropia($datosY);
        
        $grupo1 = [];
        $grupo2 = [];
        
        foreach ($datosX as $idx => $fila) {
            if ($fila[$featureIdx] >= $umbral) {
                $grupo1[] = $datosY[$idx];
            } else {
                $grupo2[] = $datosY[$idx];
            }
        }
        
        if (empty($grupo1) || empty($grupo2)) return 0;
        
        $n1 = count($grupo1);
        $n2 = count($grupo2);
        
        $entropiaHijo = ($n1 / $n) * $this->entropia($grupo1) + ($n2 / $n) * $this->entropia($grupo2);
        
        return $entropiaPadre - $entropiaHijo;
    }
    
    /**
     * Calcula la entropía de Shannon
     */
    private function entropia(array $datos): float {
        if (empty($datos)) return 0;
        
        $conteo = array_count_values($datos);
        $n = count($datos);
        $entropia = 0;
        
        foreach ($conteo as $freq) {
            $p = $freq / $n;
            if ($p > 0) {
                $entropia -= $p * log($p, 2);
            }
        }
        
        return $entropia;
    }
    
    /**
     * Calcula la confianza basada en launanimidad de votos
     */
    private function calcularConfianzaVotos(array $predicciones): string {
        $conteo = array_count_values($predicciones);
        $maxVotos = max($conteo);
        $total = count($predicciones);
        $porcentaje = ($maxVotos / $total) * 100;
        
        if ($porcentaje >= 70) return 'alta';
        if ($porcentaje >= 50) return 'media';
        return 'baja';
    }
    
    /**
     * Compara dos arrays de arrays para verificar si son iguales
     */
    private function arraysIguales(array $a, array $b): bool {
        if (count($a) !== count($b)) return false;
        
        foreach ($a as $i => $val) {
            if ($val !== $b[$i]) return false;
        }
        
        return true;
    }
    
    /**
     * Evalúa el modelo con métricas de rendimiento
     */
    public function evaluarModelo(array $predicciones, array $reales): array {
        $n = count($predicciones);
        if ($n === 0 || $n !== count($reales)) {
            return ['error' => 'Datos inválidos'];
        }
        
        $vp = 0; $vn = 0; $fp = 0; $fn = 0;
        $sumaErrores = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $pred = $predicciones[$i] >= 0.5 ? 1 : 0;
            $real = $reales[$i] >= 0.5 ? 1 : 0;
            
            if ($pred == 1 && $real == 1) $vp++;
            elseif ($pred == 0 && $real == 0) $vn++;
            elseif ($pred == 1 && $real == 0) $fp++;
            else $fn++;
            
            $sumaErrores += abs($pred - $real);
        }
        
        $precision = ($vp + $vn) / $n;
        $exhaustividad = $n > 0 ? $vp / ($vp + $fn + 1) : 0;
        $especificidad = $n > 0 ? $vn / ($vn + $fp + 1) : 0;
        $f1 = ($precision + $exhaustividad) > 0 ? 2 * ($precision * $exhaustividad) / ($precision + $exhaustividad) : 0;
        
        return [
            'precision' => round($precision * 100, 2),
            'exhaustividad' => round($exhaustividad * 100, 2),
            'especificidad' => round($especificidad * 100, 2),
            'f1_score' => round($f1 * 100, 2),
            'error_absoluto_medio' => round($sumaErrores / $n, 4),
            'matriz_confusion' => ['vp' => $vp, 'vn' => $vn, 'fp' => $fp, 'fn' => $fn]
        ];
    }
    
    /**
     * Predicción de riesgo usando ensemble de modelos
     */
    public function predecirRiesgoEnsemble(array $features): array {
        $resultados = [];
        
        foreach ($features as $empleado) {
            $datosHist = $this->obtenerSerieHistorica($empleado['empleado_id']);
            
            if (count($datosHist) < 5) {
                $resultados[] = [
                    'empleado_id' => $empleado['empleado_id'],
                    'riesgo_predicho' => $empleado['riesgo_actual'] ?? 50,
                    'metodo' => ' heurístico',
                    'confianza' => 'baja',
                    'modelos' => []
                ];
                continue;
            }
            
            $serie = array_column($datosHist, 'incidencias');
            $dias = range(1, count($serie));
            
            $regresion = $this->regresionLineal($dias, $serie, count($serie) + 30);
            $ema = $this->promedioMovilExponencial($serie);
            
            $prediccion = ($regresion['prediccion'] + $ema['pronostico']) / 2;
            
            $resultados[] = [
                'empleado_id' => $empleado['empleado_id'],
                'riesgo_predicho' => min(100, max(0, round($prediccion))),
                'pronostico_regresion' => $regresion['prediccion'],
                'pronostico_ema' => $ema['pronostico'],
                'confianza' => $this->combinarConfianza($regresion['confianza'], $ema['confianza']),
                'modelos' => ['regresion_lineal', 'ema'],
                'tendencia' => $regresion['tendencia'] ?? 'estable'
            ];
        }
        
        return $resultados;
    }
    
    /**
     * Combina niveles de confianza
     */
    private function combinarConfianza(string $c1, string $c2): string {
        $niveles = ['baja' => 1, 'media' => 2, 'alta' => 3];
        $promedio = ($niveles[$c1] + $niveles[$c2]) / 2;
        
        if ($promedio >= 2.5) return 'alta';
        if ($promedio >= 1.5) return 'media';
        return 'baja';
    }
    
    /**
     * Obtiene serie histórica de incidencias para un empleado
     */
    public function obtenerSerieHistorica(int $empleadoId): array {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(fecha, '%Y-%m-%d') as fecha, COUNT(*) as incidencias
            FROM retardos
            WHERE empleado_id = ? AND justificado = 0
            GROUP BY DATE(fecha)
            ORDER BY fecha DESC
            LIMIT 60
        ");
        $stmt->execute([$empleadoId]);
        
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Segmentación de empleados usando K-Means
     */
    public function segmentarEmpleados(): array {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->query("
            SELECT 
                e.id as empleado_id,
                e.nombre,
                e.area,
                COALESCE(r.retardos_6m, 0) as retardos,
                COALESCE(r.faltas_6m, 0) as faltas,
                COALESCE(r.promedio_minutos, 0) as promedio_minutos
            FROM empleados e
            LEFT JOIN (
                SELECT 
                    empleado_id,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 180 DAY) THEN 1 ELSE 0 END) as retardos_6m,
                    SUM(CASE WHEN tipo_asistencia = 'falta' AND fecha >= DATE_SUB(NOW(), INTERVAL 180 DAY) THEN 1 ELSE 0 END) as faltas_6m,
                    AVG(minutos_retardo) as promedio_minutos
                FROM retardos
                WHERE justificado = 0
                GROUP BY empleado_id
            ) r ON e.id = r.empleado_id
            WHERE e.activo = 1
        ");
        
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $datosML = [];
        foreach ($empleados as $emp) {
            $datosML[] = [
                'empleado_id' => $emp['empleado_id'],
                'nombre' => $emp['nombre'],
                'area' => $emp['area'],
                'caracteristicas' => [
                    $emp['retardos'] / 50,
                    $emp['faltas'] / 20,
                    $emp['promedio_minutos'] / 60
                ]
            ];
        }
        
        $clustering = $this->kMeansClustering($datosML, 4);
        
        $segmentos = [];
        foreach ($clustering['clusters'] as $idx => $resultado) {
            $segmento = $resultado['cluster'];
            $emp = $empleados[$idx];
            
            $segmentos[$segmento]['empleados'][] = $emp['empleado_id'];
            $segmentos[$segmento]['detalles'][] = $emp;
        }
        
        $labels = ['Excelente', 'Bueno', 'En Riesgo', 'Crítico'];
        
        $resultado = [];
        foreach ($segmentos as $idx => $seg) {
            $resultado[] = [
                'segmento' => $labels[$idx] ?? 'Segmento ' . $idx,
                'num_empleados' => count($seg['empleados']),
                'empleados' => $seg['detalles'],
                'caracteristicas_promedio' => $clustering['centroids'][$idx] ?? []
            ];
        }
        
        usort($resultado, fn($a, $b) => $b['num_empleados'] <=> $a['num_empleados']);
        
        return $resultado;
    }
}
