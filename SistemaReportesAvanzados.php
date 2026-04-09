<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/ZKTecoClasificadorAvanzado.php';
require_once __DIR__ . '/GeneradorRetardosAutomatico.php';

/**
 * 📊 SISTEMA DE REPORTES Y ANÁLISIS AVANZADO v2.0
 * Dashboard completo con análisis de patrones y métricas
 */
class SistemaReportesAvanzados {
    private $db;
    private $clasificador;
    private $generador_retardos;
    
    public function __construct() {
        $this->db = new Database();
        $this->clasificador = new ZKTecoClasificadorAvanzado();
        $this->generador_retardos = new GeneradorRetardosAutomatico();
    }
    
    /**
     * 🎯 GENERA REPORTE COMPLETO DEL SISTEMA
     */
    public function generarReporteCompleto($empleado_id = null, $periodo = 'mes') {
        echo "🎯 GENERANDO REPORTE COMPLETO DEL SISTEMA\n";
        echo "Tipo: " . ($empleado_id ? "Empleado Individual" : "Toda la Empresa") . "\n";
        echo "Período: $periodo\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        try {
            // 1. Estadísticas generales de asistencia
            $estadisticas_asistencia = $this->obtenerEstadisticasAsistencia($empleado_id, $periodo);
            
            // 2. Estadísticas de retardos
            $estadisticas_retardos = $this->generador_retardos->generarReporteEstadistico($empleado_id, $this->getDiasPeriodo($periodo));
            
            // 3. Análisis de patrones
            $patrones = $this->analizarPatronesComportamiento($empleado_id, $periodo);
            
            // 4. Métricas de productividad
            $productividad = $this->calcularMetricasProductividad($empleado_id, $periodo);
            
            // 5. Análisis de tendencias
            $tendencias = $this->analizarTendencias($empleado_id, $periodo);
            
            $reporte_completo = [
                'timestamp' => date('Y-m-d H:i:s'),
                'periodo' => $periodo,
                'tipo_reporte' => $empleado_id ? 'individual' : 'general',
                'empleado_id' => $empleado_id,
                'estadisticas_asistencia' => $estadisticas_asistencia,
                'estadisticas_retardos' => $estadisticas_retardos,
                'patrones' => $patrones,
                'productividad' => $productividad,
                'tendencias' => $tendencias,
                'recomendaciones' => $this->generarRecomendaciones($estadisticas_retardos, $patrones, $productividad)
            ];
            
            // 6. Mostrar reporte en consola
            $this->mostrarReporteConsola($reporte_completo);
            
            // 7. Generar archivo Excel si se solicita
            if (isset($_GET['excel'])) {
                $this->generarArchivoExcel($reporte_completo);
            }
            
            return $reporte_completo;
            
        } catch (Exception $e) {
            echo "❌ Error generando reporte: " . $e->getMessage() . "\n";
            return ['exito' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 📊 OBTIENE ESTADÍSTICAS DE ASISTENCIA
     */
    private function obtenerEstadisticasAsistencia($empleado_id, $periodo) {
        $dias = $this->getDiasPeriodo($periodo);
        $condicion_empleado = $empleado_id ? "AND a.empleado_id = ?" : "";
        $parametros = [date('Y-m-d', strtotime("-$dias days")), date('Y-m-d')];
        if ($empleado_id) $parametros[] = $empleado_id;
        
        $sql = "
            SELECT 
                COUNT(*) as total_registros,
                COUNT(DISTINCT a.empleado_id) as empleados_unicos,
                COUNT(DISTINCT a.fecha) as dias_unicos,
                COUNT(CASE WHEN a.hora_entrada IS NOT NULL THEN 1 END) as con_entrada,
                COUNT(CASE WHEN a.hora_salida IS NOT NULL THEN 1 END) as con_salida,
                COUNT(CASE WHEN a.hora_entrada IS NOT NULL AND a.hora_salida IS NOT NULL THEN 1 END) as registros_completos,
                AVG(TIME_TO_SEC(a.hora_entrada))/3600 as promedio_hora_entrada,
                AVG(TIME_TO_SEC(a.hora_salida))/3600 as promedio_hora_salida,
                MIN(a.fecha) as fecha_minima,
                MAX(a.fecha) as fecha_maxima
            FROM asistencia a
            WHERE a.fecha BETWEEN ? AND ?
              $condicion_empleado
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($parametros);
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular promedios de horas correctamente
        $estadisticas['promedio_hora_entrada'] = $this->formatearHoraDecimal($estadisticas['promedio_hora_entrada']);
        $estadisticas['promedio_hora_salida'] = $this->formatearHoraDecimal($estadisticas['promedio_hora_salida']);
        
        return $estadisticas;
    }
    
    /**
     * 🔍 ANALIZA PATRONES DE COMPORTAMIENTO
     */
    private function analizarPatronesComportamiento($empleado_id, $periodo) {
        echo "🔍 Analizando patrones de comportamiento...\n";
        
        $dias = $this->getDiasPeriodo($periodo);
        $condicion_empleado = $empleado_id ? "WHERE empleado_id = ?" : "";
        $parametros = $dias;
        if ($empleado_id) $parametros[] = $empleado_id;
        
        // Patrones por día de semana
        $sql = "
            SELECT 
                DAYOFWEEK(fecha) as dia_semana_num,
                DAYNAME(fecha) as dia_semana,
                COUNT(*) as total_registros,
                COUNT(CASE WHEN TIME(hora_entrada) > '09:15:00' THEN 1 END) as retardos,
                AVG(TIME_TO_SEC(hora_entrada))/3600 as promedio_hora
            FROM asistencia 
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND hora_entrada IS NOT NULL
              $condicion_empleado
            GROUP BY DAYOFWEEK(fecha), DAYNAME(fecha)
            ORDER BY dia_semana_num
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($parametros);
        $patrones_dias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($patrones_dias as &$patron) {
            $patron['promedio_hora'] = $this->formatearHoraDecimal($patron['promedio_hora']);
            $patron['porcentaje_retardos'] = $patron['total_registros'] > 0 ? 
                round(($patron['retardos'] / $patron['total_registros']) * 100, 2) : 0;
        }
        
        // Patrones horarios
        $sql = "
            SELECT 
                CASE 
                    WHEN TIME(hora_entrada) BETWEEN '06:00:00' AND '08:59:59' THEN '06:00-08:59'
                    WHEN TIME(hora_entrada) BETWEEN '09:00:00' AND '11:59:59' THEN '09:00-11:59'
                    WHEN TIME(hora_entrada) BETWEEN '12:00:00' AND '14:59:59' THEN '12:00-14:59'
                    WHEN TIME(hora_entrada) BETWEEN '15:00:00' AND '17:59:59' THEN '15:00-17:59'
                    ELSE '18:00+'
                END as rango_horario,
                COUNT(*) as total_registros,
                AVG(TIME_TO_SEC(hora_entrada))/3600 as promedio_hora
            FROM asistencia 
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND hora_entrada IS NOT NULL
              $condicion_empleado
            GROUP BY 
                CASE 
                    WHEN TIME(hora_entrada) BETWEEN '06:00:00' AND '08:59:59' THEN '06:00-08:59'
                    WHEN TIME(hora_entrada) BETWEEN '09:00:00' AND '11:59:59' THEN '09:00-11:59'
                    WHEN TIME(hora_entrada) BETWEEN '12:00:00' AND '14:59:59' THEN '12:00-14:59'
                    WHEN TIME(hora_entrada) BETWEEN '15:00:00' AND '17:59:59' THEN '15:00-17:59'
                    ELSE '18:00+'
                END
            ORDER BY MIN(TIME(hora_entrada))
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($parametros);
        $patrones_horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($patrones_horarios as &$patron) {
            $patron['promedio_hora'] = $this->formatearHoraDecimal($patron['promedio_hora']);
        }
        
        return [
            'por_dia_semana' => $patrones_dias,
            'por_rango_horario' => $patrones_horarios,
            'dias_mas_retardos' => $this->obtenerDiasConMasRetardos($patrones_dias),
            'horario_pico' => $this->obtenerHorarioPico($patrones_horarios)
        ];
    }
    
    /**
     * ⚡ CALCULA MÉTRICAS DE PRODUCTIVIDAD
     */
    private function calcularMetricasProductividad($empleado_id, $periodo) {
        $dias = $this->getDiasPeriodo($periodo);
        $condicion_empleado = $empleado_id ? "AND a.empleado_id = ?" : "";
        $parametros = [date('Y-m-d', strtotime("-$dias days")), date('Y-m-d')];
        if ($empleado_id) $parametros[] = $empleado_id;
        
        // Calcular días laborables (lunes a viernes)
        $dias_laborables_sql = "
            SELECT COUNT(DISTINCT fecha) as dias_laborables
            FROM (
                SELECT DATE_SUB(CURDATE(), INTERVAL seq DAY) as fecha
                FROM (
                    SELECT 0 as seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
                    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                    UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14
                    UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
                    UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24
                    UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
                ) as numbers
                WHERE DATE_SUB(CURDATE(), INTERVAL seq DAY) BETWEEN DATE_SUB(CURDATE(), INTERVAL ? DAY) AND CURDATE()
                  AND DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL seq DAY)) BETWEEN 2 AND 6
            ) as fechas
        ";
        
        $stmt = $this->db->getConnection()->prepare($dias_laborables_sql);
        $stmt->execute([$dias]);
        $dias_laborables = $stmt->fetch(PDO::FETCH_ASSOC)['dias_laborables'];
        
        // Estadísticas de asistencia
        $sql = "
            SELECT 
                COUNT(DISTINCT DATE(fecha)) as dias_asistidos,
                COUNT(DISTINCT empleado_id) as empleados_activos,
                COUNT(*) as total_registros,
                COUNT(CASE WHEN TIME(hora_entrada) > '09:15:00' THEN 1 END) as retardos,
                COUNT(CASE WHEN TIME(hora_entrada) <= '09:15:00' THEN 1 END) as puntuales,
                AVG(TIMEDIFF(HOUR, hora_entrada, hora_salida)) as promedio_horas_trabajo
            FROM asistencia 
            WHERE fecha BETWEEN ? AND ?
              AND hora_entrada IS NOT NULL
              AND hora_salida IS NOT NULL
              $condicion_empleado
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($parametros);
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $productividad = [
            'asistencia_general' => [
                'dias_laborables_periodo' => $dias_laborables,
                'dias_asistidos' => $estadisticas['dias_asistidos'] ?? 0,
                'tasa_asistencia' => $dias_laborables > 0 ? 
                    round(($estadisticas['dias_asistidos'] / $dias_laborables) * 100, 2) : 0,
                'empleados_activos' => $estadisticas['empleados_activos'] ?? 0
            ],
            'puntualidad' => [
                'total_registros' => $estadisticas['total_registros'] ?? 0,
                'puntuales' => $estadisticas['puntuales'] ?? 0,
                'retardos' => $estadisticas['retardos'] ?? 0,
                'tasa_puntualidad' => $estadisticas['total_registros'] > 0 ? 
                    round(($estadisticas['puntuales'] / $estadisticas['total_registros']) * 100, 2) : 0,
                'tasa_retardo' => $estadisticas['total_registros'] > 0 ? 
                    round(($estadisticas['retardos'] / $estadisticas['total_registros']) * 100, 2) : 0
            ],
            'horas_trabajo' => [
                'promedio_horas_dia' => round($estadisticas['promedio_horas_trabajo'] ?? 0, 1),
                'total_registros_completos' => $estadisticas['total_registros'] ?? 0
            ]
        ];
        
        // Calcular métricas de eficiencia
        if ($estadisticas['dias_asistidos'] > 0) {
            $productividad['eficiencia'] = [
                'registros_por_dia' => round(($estadisticas['total_registros'] ?? 0) / $estadisticas['dias_asistidos'], 1),
                'score_productividad' => $this->calcularScoreProductividad($productividad)
            ];
        } else {
            $productividad['eficiencia'] = [
                'registros_por_dia' => 0,
                'score_productividad' => 0
            ];
        }
        
        return $productividad;
    }
    
    /**
     * 📈 ANALIZA TENDENCIAS TEMPORALES
     */
    private function analizarTendencias($empleado_id, $periodo) {
        $dias = $this->getDiasPeriodo($periodo);
        $condicion_empleado = $empleado_id ? "AND empleado_id = ?" : "";
        $parametros = [date('Y-m-d', strtotime("-$dias days")), date('Y-m-d')];
        if ($empleado_id) $parametros[] = $empleado_id;
        
        // Tendencia por semanas
        $sql = "
            SELECT 
                YEARWEEK(fecha) as semana,
                MIN(DATE(fecha)) as fecha_inicio_semana,
                MAX(DATE(fecha)) as fecha_fin_semana,
                COUNT(DISTINCT DATE(fecha)) as dias_asistidos,
                COUNT(*) as total_registros,
                COUNT(CASE WHEN TIME(hora_entrada) > '09:15:00' THEN 1 END) as retardos
            FROM asistencia 
            WHERE fecha BETWEEN ? AND ?
              AND hora_entrada IS NOT NULL
              $condicion_empleado
            GROUP BY YEARWEEK(fecha)
            ORDER BY semana DESC
            LIMIT 8
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($parametros);
        $tendencias_semanales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tendencias_semanales as &$tendencia) {
            $tendencia['porcentaje_retardo_semanal'] = $tendencia['total_registros'] > 0 ? 
                round(($tendencia['retardos'] / $tendencia['total_registros']) * 100, 2) : 0;
        }
        
        return [
            'tendencias_semanales' => $tendencias_semanales,
            'mejor_semana' => $this->obtenerMejorSemana($tendencias_semanales),
            'peor_semana' => $this->obtenerPeorSemana($tendencias_semanales),
            'tendencia_general' => $this->calcularTendenciaGeneral($tendencias_semanales)
        ];
    }
    
    /**
     * 💡 GENERA RECOMENDACIONES BASADAS EN ANÁLISIS
     */
    private function generarRecomendaciones($estadisticas_retardos, $patrones, $productividad) {
        $recomendaciones = [];
        
        // Recomendaciones de puntualidad
        if (($productividad['puntualidad']['tasa_retardo'] ?? 0) > 20) {
            $recomendaciones[] = [
                'tipo' => 'puntualidad',
                'prioridad' => 'alta',
                'titulo' => 'Alta tasa de retardos',
                'descripcion' => "La tasa de retardos es del {$productividad['puntualidad']['tasa_retardo']}%, lo que afecta la productividad general.",
                'sugerencias' => [
                    'Implementar sistema de recordatorios automáticos',
                    'Revisar políticas de flexibilidad horaria',
                    'Considerar ajustes en horarios de llegada'
                ]
            ];
        }
        
        // Recomendaciones por patrones de días
        if (!empty($patrones['dias_mas_retardos'])) {
            $peores_dias = array_slice($patrones['dias_mas_retardos'], 0, 2);
            foreach ($peores_dias as $dia) {
                $recomendaciones[] = [
                    'tipo' => 'patron_dia',
                    'prioridad' => 'media',
                    'titulo' => "Patrón de retardos los {$dia['dia_semana']}",
                    'descripcion' => "{$dia['porcentaje_retardos']}% de los registros los {$dia['dia_semana']} tienen retardo.",
                    'sugerencias' => [
                        'Investigar causas específicas del día',
                        'Programar recordatorios adicionales este día',
                        'Revisar factores externos (tráfico, transporte)'
                    ]
                ];
            }
        }
        
        // Recomendaciones de productividad
        if (($productividad['asistencia_general']['tasa_asistencia'] ?? 0) < 85) {
            $recomendaciones[] = [
                'tipo' => 'asistencia',
                'prioridad' => 'alta',
                'titulo' => 'Baja tasa de asistencia',
                'descripcion' => "La tasa de asistencia es del {$productividad['asistencia_general']['tasa_asistencia']}%, por debajo del 85% recomendado.",
                'sugerencias' => [
                    'Analizar causas de ausentismo',
                    'Implementar programa de incentivos',
                    'Revisar políticas de licencias y permisos'
                ]
            ];
        }
        
        return $recomendaciones;
    }
    
    /**
     * 🖥️ MUESTRA REPORTE COMPLETO EN CONSOLA
     */
    private function mostrarReporteConsola($reporte) {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 REPORTE COMPLETO DEL SISTEMA BIOMÉTRICO\n";
        echo "Generado: {$reporte['timestamp']}\n";
        echo "Período: {$reporte['periodo']}\n";
        echo "Tipo: " . ($reporte['tipo_reporte'] === 'individual' ? "Empleado Individual" : "Empresa Completa") . "\n";
        echo str_repeat("=", 80) . "\n\n";
        
        // Estadísticas de asistencia
        $asist = $reporte['estadisticas_asistencia'];
        echo "📊 ESTADÍSTICAS DE ASISTENCIA:\n";
        echo "   - Total registros: {$asist['total_registros']}\n";
        echo "   - Empleados únicos: {$asist['empleados_unicos']}\n";
        echo "   - Días únicos: {$asist['dias_unicos']}\n";
        echo "   - Registros completos: {$asist['registros_completos']}\n";
        echo "   - Con entrada: {$asist['con_entrada']}\n";
        echo "   - Con salida: {$asist['con_salida']}\n";
        echo "   - Promedio entrada: {$asist['promedio_hora_entrada']}\n";
        echo "   - Promedio salida: {$asist['promedio_hora_salida']}\n";
        echo "   - Período: {$asist['fecha_minima']} a {$asist['fecha_maxima']}\n\n";
        
        // Productividad
        $prod = $reporte['productividad'];
        echo "⚡ MÉTRICAS DE PRODUCTIVIDAD:\n";
        echo "   - Tasa asistencia: {$prod['asistencia_general']['tasa_asistencia']}%\n";
        echo "   - Tasa puntualidad: {$prod['puntualidad']['tasa_puntualidad']}%\n";
        echo "   - Tasa retardo: {$prod['puntualidad']['tasa_retardo']}%\n";
        echo "   - Promedio horas/día: {$prod['horas_trabajo']['promedio_horas_dia']}\n";
        echo "   - Registros/día: {$prod['eficiencia']['registros_por_dia']}\n";
        echo "   - Score productividad: {$prod['eficiencia']['score_productividad']}/100\n\n";
        
        // Recomendaciones
        if (!empty($reporte['recomendaciones'])) {
            echo "💡 RECOMENDACIONES:\n";
            foreach ($reporte['recomendaciones'] as $i => $rec) {
                $icono = $rec['prioridad'] === 'alta' ? '🚨' : ($rec['prioridad'] === 'media' ? '⚠️' : 'ℹ️');
                echo "   $icono " . ($i + 1) . ". {$rec['titulo']}\n";
                echo "      {$rec['descripcion']}\n";
                foreach ($rec['sugerencias'] as $sugerencia) {
                    echo "      • $sugerencia\n";
                }
                echo "\n";
            }
        }
        
        echo str_repeat("=", 80) . "\n";
    }
    
    // Métodos auxiliares
    private function getDiasPeriodo($periodo) {
        switch ($periodo) {
            case 'semana': return 7;
            case 'quincena': return 15;
            case 'mes': return 30;
            case 'trimestre': return 90;
            case 'semestre': return 180;
            case 'anio': return 365;
            default: return 30;
        }
    }
    
    private function formatearHoraDecimal($hora_decimal) {
        if ($hora_decimal === null) return '00:00';
        $horas = floor($hora_decimal);
        $minutos = round(($hora_decimal - $horas) * 60);
        return sprintf('%02d:%02d', $horas, $minutos);
    }
    
    private function obtenerDiasConMasRetardos($patrones_dias) {
        usort($patrones_dias, function($a, $b) {
            return $b['porcentaje_retardos'] - $a['porcentaje_retardos'];
        });
        return array_slice($patrones_dias, 0, 3);
    }
    
    private function obtenerHorarioPico($patrones_horarios) {
        return !empty($patrones_horarios) ? 
            $patrones_horarios[array_search(max(array_column($patrones_horarios, 'total_registros')), array_column($patrones_horarios, 'total_registros'))] : null;
    }
    
    private function calcularScoreProductividad($productividad) {
        $asistencia = $productividad['asistencia_general']['tasa_asistencia'] ?? 0;
        $puntualidad = $productividad['puntualidad']['tasa_puntualidad'] ?? 0;
        $horas = min($productividad['horas_trabajo']['promedio_horas_dia'] / 8 * 100, 100);
        
        return round(($asistencia * 0.4 + $puntualidad * 0.3 + $horas * 0.3), 1);
    }
    
    private function obtenerMejorSemana($tendencias_semanales) {
        if (empty($tendencias_semanales)) return null;
        
        $mejor = null;
        $mejor_score = 100;
        
        foreach ($tendencias_semanales as $semana) {
            $score = 100 - $semana['porcentaje_retardo_semanal'];
            if ($score > $mejor_score) {
                $mejor_score = $score;
                $mejor = $semana;
            }
        }
        
        return $mejor;
    }
    
    private function obtenerPeorSemana($tendencias_semanales) {
        if (empty($tendencias_semanales)) return null;
        
        $peor = null;
        $peor_score = 0;
        
        foreach ($tendencias_semanales as $semana) {
            $score = 100 - $semana['porcentaje_retardo_semanal'];
            if ($score < $peor_score) {
                $peor_score = $score;
                $peor = $semana;
            }
        }
        
        return $peor;
    }
    
    private function calcularTendenciaGeneral($tendencias_semanales) {
        if (count($tendencias_semanales) < 2) return 'insuficiente_datos';
        
        $primeros = array_slice($tendencias_semanales, -2);
        $ultimos = array_slice($tendencias_semanales, 0, 2);
        
        $promedio_primeros = array_sum(array_column($primeros, 'porcentaje_retardo_semanal')) / 2;
        $promedio_ultimos = array_sum(array_column($ultimos, 'porcentaje_retardo_semanal')) / 2;
        
        if ($promedio_ultimos > $promedio_primeros + 5) {
            return 'empeorando';
        } elseif ($promedio_ultimos < $promedio_primeros - 5) {
            return 'mejorando';
        } else {
            return 'estable';
        }
    }
    
    /**
     * 📄 GENERA ARCHIVO EXCEL CON EL REPORTE
     */
    private function generarArchivoExcel($reporte) {
        try {
            // Crear nombre de archivo
            $fecha = date('Y-m-d_H-i-s');
            $tipo_reporte = $reporte['tipo_reporte'] === 'individual' ? 'empleado_' . $reporte['empleado_id'] : 'empresa';
            $nombre_archivo = "reporte_biometrico_{$tipo_reporte}_{$fecha}.xlsx";
            
            // Ruta del archivo
            $ruta_archivo = __DIR__ . '/../reportes/' . $nombre_archivo;
            
            // Crear directorio si no existe
            if (!is_dir(__DIR__ . '/../reportes/')) {
                mkdir(__DIR__ . '/../reportes/', 0755, true);
            }
            
            // Crear archivo Excel con estructura básica
            $contenido = "REPORTE COMPLETO DEL SISTEMA BIOMÉTRICO\n";
            $contenido .= "Generado: " . $reporte['timestamp'] . "\n";
            $contenido .= "Período: " . $reporte['periodo'] . "\n";
            $contenido .= "Tipo: " . ($reporte['tipo_reporte'] === 'individual' ? 'Individual' : 'General') . "\n\n";
            
            // Estadísticas de asistencia
            $contenido .= "ESTADÍSTICAS DE ASISTENCIA\n";
            foreach ($reporte['estadisticas_asistencia'] as $clave => $valor) {
                $contenido .= "$clave: $valor\n";
            }
            
            $contenido .= "\n\nMÉTRICAS DE PRODUCTIVIDAD\n";
            foreach ($reporte['productividad'] as $clave => $datos) {
                if (is_array($datos)) {
                    $contenido .= "$clave:\n";
                    foreach ($datos as $k => $v) {
                        $contenido .= "  $k: $v\n";
                    }
                } else {
                    $contenido .= "$clave: $datos\n";
                }
            }
            
            // Guardar archivo
            file_put_contents($ruta_archivo, $contenido);
            
            echo "✅ Archivo Excel generado exitosamente: $nombre_archivo\n";
            return ['exito' => true, 'archivo' => $nombre_archivo, 'ruta' => $ruta_archivo];
            
        } catch (Exception $e) {
            echo "❌ Error generando archivo Excel: " . $e->getMessage() . "\n";
            return ['exito' => false, 'error' => $e->getMessage()];
        }
    }
}

?>