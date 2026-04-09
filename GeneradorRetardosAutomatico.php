<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/ZKTecoClasificadorAvanzado.php';

/**
 * ⏰ GENERADOR AUTOMÁTICO DE RETARDOS v2.0
 * Sistema inteligente para detectar y registrar retardos
 */
class GeneradorRetardosAutomatico {
    private $db;
    private $clasificador;
    private $configuracion = [
        'hora_limite_entrada' => '09:15:00',
        'umbral_retardo_menor' => 20,  // 10-20 minutos = retardo menor
        'umbral_retardo_mayor' => 30,   // 21-30 minutos = retardo mayor
        'umbral_falta' => 31,           // 31+ minutos = falta
        'dias_fin_semana' => ['saturday', 'sunday'],
        'horas_nocturnas' => ['inicio' => '22:00:00', 'fin' => '06:00:00']
    ];
    
    public function __construct() {
        $this->db = new Database();
        $this->clasificador = new ZKTecoClasificadorAvanzado();
    }
    
    /**
     * 🎯 PROCESA TODA LA ASISTENCIA PARA GENERAR RETARDOS
     */
    public function procesarRetardosDesdeAsistencia($fecha_inicio = null, $fecha_fin = null) {
        if (!$fecha_inicio) $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
        if (!$fecha_fin) $fecha_fin = date('Y-m-d');
        
        echo "🎯 PROCESANDO RETARDOS AUTOMÁTICAMENTE\n";
        echo "Período: $fecha_inicio a $fecha_fin\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        try {
            // 1. Obtener todos los registros de asistencia del período
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    a.id as asistencia_id,
                    a.empleado_id,
                    a.fecha,
                    a.hora_entrada,
                    a.hora_salida,
                    e.nombre as empleado_nombre,
                    e.id_empleado
                FROM asistencia a
                JOIN empleados e ON a.empleado_id = e.id
                WHERE a.fecha BETWEEN ? AND ?
                  AND a.hora_entrada IS NOT NULL
                ORDER BY a.fecha, a.empleado_id, a.hora_entrada
            ");
            
            $stmt->execute([$fecha_inicio, $fecha_fin]);
            $registros_asistencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📊 Registros de asistencia: " . count($registros_asistencia) . "\n";
            
            $estadisticas = [
                'registros_procesados' => 0,
                'retardos_generados' => 0,
                'retardos_omitidos' => 0,
                'errores' => 0,
                'resumen_tipo_retardos' => [
                    'menor' => 0,
                    'medio' => 0,
                    'mayor' => 0,
                    'muy_mayor' => 0
                ]
            ];
            
            // 2. Procesar cada registro para detectar retardos
            foreach ($registros_asistencia as $registro) {
                try {
                    $resultado = $this->analizarRegistroParaRetardo($registro);
                    
                    if ($resultado['es_retardo']) {
                        $id_retardo = $this->generarRegistroRetardo($registro, $resultado);
                        
                        if ($id_retardo) {
                            $estadisticas['retardos_generados']++;
                            $estadisticas['resumen_tipo_retardos'][$resultado['tipo_retardo']]++;
                            
                            echo "✅ Retardo generado: {$registro['empleado_nombre']} - {$registro['fecha']} - {$resultado['minutos_retraso']} min\n";
                        } else {
                            $estadisticas['errores']++;
                            echo "❌ Error generando retardo para {$registro['empleado_nombre']}\n";
                        }
                    } else {
                        $estadisticas['retardos_omitidos']++;
                        echo "ℹ️  Sin retardo: {$registro['empleado_nombre']} - {$registro['fecha']}\n";
                    }
                    
                    $estadisticas['registros_procesados']++;
                    
                } catch (Exception $e) {
                    $estadisticas['errores']++;
                    echo "❌ Error procesando registro: " . $e->getMessage() . "\n";
                }
            }
            
            // 3. Generar reporte final
            echo "\n📈 REPORTE FINAL DE PROCESAMIENTO:\n";
            echo "   - Registros procesados: {$estadisticas['registros_procesados']}\n";
            echo "   - Retardos generados: {$estadisticas['retardos_generados']}\n";
            echo "   - Retardos omitidos: {$estadisticas['retardos_omitidos']}\n";
            echo "   - Errores: {$estadisticas['errores']}\n";
            echo "   - Retardos menores: {$estadisticas['resumen_tipo_retardos']['menor']}\n";
            echo "   - Retardos medios: {$estadisticas['resumen_tipo_retardos']['medio']}\n";
            echo "   - Retardos mayores: {$estadisticas['resumen_tipo_retardos']['mayor']}\n";
            echo "   - Retardos muy mayores: {$estadisticas['resumen_tipo_retardos']['muy_mayor']}\n";
            
            return [
                'exito' => true,
                'estadisticas' => $estadisticas,
                'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin]
            ];
            
        } catch (Exception $e) {
            echo "❌ Error general en procesamiento: " . $e->getMessage() . "\n";
            return [
                'exito' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 🔍 ANALIZA UN REGISTRO PARA DETERMINAR SI ES RETARDO
     */
    private function analizarRegistroParaRetardo($registro) {
        $hora_entrada = $registro['hora_entrada'];
        $fecha = $registro['fecha'];
        
        // Verificar si ya existe retardo para este registro
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as existe 
            FROM retardos 
            WHERE asistencia_id = ?
        ");
        $stmt->execute([$registro['asistencia_id']]);
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)['existe'] > 0) {
            return ['es_retardo' => false, 'motivo' => 'ya_existe'];
        }
        
        // Verificar si es fin de semana (generalmente no generan retardo)
        if ($this->esFinDeSemana($fecha)) {
            return ['es_retardo' => false, 'motivo' => 'fin_de_semana'];
        }
        
        // Calcular minutos de retraso
        $minutos_retraso = $this->calcularMinutosRetraso($hora_entrada);
        
        if ($minutos_retraso <= 0) {
            return ['es_retardo' => false, 'motivo' => 'puntual_o_temprano'];
        }
        
        // Determinar tipo de retardo según minutos
        $tipo_retardo = $this->clasificarTipoRetardo($minutos_retraso);
        
        return [
            'es_retardo' => true,
            'minutos_retraso' => $minutos_retraso,
            'tipo_retardo' => $tipo_retardo,
            'motivo' => 'retraso_calculado',
            'detalles' => "Entrada a las {$hora_entrada}, {$minutos_retraso} minutos después del límite"
        ];
    }
    
    /**
     * ⏰ CALCULA MINUTOS DE RETRASO
     */
    private function calcularMinutosRetraso($hora_entrada) {
        $timestamp_entrada = strtotime("2000-01-01 " . $hora_entrada);
        $timestamp_limite = strtotime("2000-01-01 " . $this->configuracion['hora_limite_entrada']);
        
        $diferencia_segundos = $timestamp_entrada - $timestamp_limite;
        $minutos_retraso = $diferencia_segundos / 60;
        
        return max(0, floor($minutos_retraso));
    }
    
    /**
     * 🏷️ CLASIFICA TIPO DE RETARDO
     * - Tolerancia: 0-10 minutos (no se registra)
     * - Retardo menor: 11-20 minutos
     * - Retardo mayor: 21-30 minutos
     * - Falta: 31+ minutos
     */
    private function clasificarTipoRetardo($minutos_retraso) {
        if ($minutos_retraso >= 0 && $minutos_retraso <= 10) {
            return 'tolerancia'; // No se registra como retardo
        } elseif ($minutos_retraso >= 11 && $minutos_retraso <= 20) {
            return 'retardo_menor';
        } elseif ($minutos_retraso >= 21 && $minutos_retraso <= 30) {
            return 'retardo_mayor';
        } elseif ($minutos_retraso >= 31) {
            return 'falta';
        }
        return 'normal';
    }
    
    /**
     * 🎯 GENERA REGISTRO DE RETARDO EN LA BASE DE DATOS
     */
    private function generarRegistroRetardo($registro_asistencia, $resultado_analisis) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO retardos (
                    empleado_id, fecha, hora_entrada, minutos_retardo, tipo_retraso,
                    justificado, motivo_detalle, requiere_validacion_jefe,
                    estado_validacion, asistencia_id, fecha_asistencia,
                    dia_semana, tipo_registro, categoria_principal,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $dia_semana = date('l', strtotime($registro_asistencia['fecha']));
            $dia_semana_ingles = date('l', strtotime($registro_asistencia['fecha']));
            $dia_semana_enum = strtolower($dia_semana);
            
            $valores = [
                $registro_asistencia['empleado_id'],
                $registro_asistencia['fecha'],
                $registro_asistencia['hora_entrada'],
                $resultado_analisis['minutos_retraso'],
                $resultado_analisis['tipo_retardo'],
                0, // no justificado inicialmente
                "Retardo detectado automáticamente - {$resultado_analisis['detalles']}",
                1, // requiere validación del jefe
                'pendiente',
                $registro_asistencia['asistencia_id'],
                $registro_asistencia['fecha'],
                $dia_semana,
                'entrada',
                'retardo'
            ];
            
            $exito = $stmt->execute($valores);
            
            if ($exito) {
                $id_retardo = $this->db->getConnection()->lastInsertId();
                
                // Log para auditoría
                error_log("RETARDO GENERADO: " . json_encode([
                    'id_retardo' => $id_retardo,
                    'empleado' => $registro_asistencia['empleado_nombre'],
                    'fecha' => $registro_asistencia['fecha'],
                    'hora_entrada' => $registro_asistencia['hora_entrada'],
                    'minutos_retraso' => $resultado_analisis['minutos_retraso'],
                    'tipo' => $resultado_analisis['tipo_retardo']
                ]));
                
                return $id_retardo;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("ERROR GENERANDO RETARDO: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 📊 GENERA REPORTE ESTADÍSTICO DE RETARDOS
     */
    public function generarReporteEstadistico($empleado_id = null, $dias = 30) {
        $fecha_inicio = date('Y-m-d', strtotime("-$dias days"));
        $fecha_fin = date('Y-m-d');
        
        $sql = "
            SELECT 
                r.*,
                e.nombre as empleado_nombre,
                e.id_empleado
            FROM retardos r
            JOIN empleados e ON r.empleado_id = e.id
            WHERE r.fecha BETWEEN ? AND ?
        ";
        
        $parametros = [$fecha_inicio, $fecha_fin];
        
        if ($empleado_id) {
            $sql .= " AND r.empleado_id = ?";
            $parametros[] = $empleado_id;
        }
        
        $sql .= " ORDER BY r.fecha DESC, r.minutos_retardo DESC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($parametros);
        $retardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reporte = [
            'periodo' => ['inicio' => $fecha_inicio, 'fin' => $fecha_fin, 'dias' => $dias],
            'total_retardos' => count($retardos),
            'estadisticas' => [
                'minutos_totales' => 0,
                'promedio_minutos' => 0,
                'retardo_mas_largo' => 0,
                'empleados_afectados' => [],
                'tipos_retardo' => ['menor' => 0, 'medio' => 0, 'mayor' => 0, 'muy_mayor' => 0],
                'justificacion' => ['justificados' => 0, 'no_justificados' => 0],
                'validacion' => ['pendientes' => 0, 'aprobados' => 0, 'rechazados' => 0]
            ],
            'retardos' => $retardos
        ];
        
        foreach ($retardos as $retardo) {
            $reporte['estadisticas']['minutos_totales'] += $retardo['minutos_retardo'];
            $reporte['estadisticas']['tipos_retardo'][$retardo['tipo_retraso']]++;
            
            if (!isset($reporte['estadisticas']['empleados_afectados'][$retardo['empleado_id']])) {
                $reporte['estadisticas']['empleados_afectados'][$retardo['empleado_id']] = [
                    'nombre' => $retardo['empleado_nombre'],
                    'id_empleado' => $retardo['id_empleado'],
                    'total_retardos' => 0,
                    'minutos_totales' => 0
                ];
            }
            
            $reporte['estadisticas']['empleados_afectados'][$retardo['empleado_id']]['total_retardos']++;
            $reporte['estadisticas']['empleados_afectados'][$retardo['empleado_id']]['minutos_totales'] += $retardo['minutos_retardo'];
            
            if ($retardo['minutos_retardo'] > $reporte['estadisticas']['retardo_mas_largo']) {
                $reporte['estadisticas']['retardo_mas_largo'] = $retardo['minutos_retardo'];
            }
            
            if ($retardo['justificado']) {
                $reporte['estadisticas']['justificacion']['justificados']++;
            } else {
                $reporte['estadisticas']['justificacion']['no_justificados']++;
            }
            
            $reporte['estadisticas']['validacion'][$retardo['estado_validacion']]++;
        }
        
        if (count($retardos) > 0) {
            $reporte['estadisticas']['promedio_minutos'] = 
                round($reporte['estadisticas']['minutos_totales'] / count($retardos), 1);
        }
        
        return $reporte;
    }
    
    /**
     * 📄 MUESTRA REPORTE ESTADÍSTICO EN CONSOLA
     */
    public function mostrarReporteConsola($reporte) {
        echo "\n📊 REPORTE ESTADÍSTICO DE RETARDOS\n";
        echo "Período: {$reporte['periodo']['inicio']} a {$reporte['periodo']['fin']} ({$reporte['periodo']['dias']} días)\n";
        echo "Total retardos: {$reporte['total_retardos']}\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        echo "📈 ESTADÍSTICAS GENERALES:\n";
        echo "   - Minutos totales: {$reporte['estadisticas']['minutos_totales']}\n";
        echo "   - Promedio minutos: {$reporte['estadisticas']['promedio_minutos']}\n";
        echo "   - Retardo más largo: {$reporte['estadisticas']['retardo_mas_largo']} minutos\n";
        echo "   - Empleados afectados: " . count($reporte['estadisticas']['empleados_afectados']) . "\n\n";
        
        echo "🏷️ TIPOS DE RETARDO:\n";
        echo "   - Menor: {$reporte['estadisticas']['tipos_retardo']['menor']}\n";
        echo "   - Medio: {$reporte['estadisticas']['tipos_retardo']['medio']}\n";
        echo "   - Mayor: {$reporte['estadisticas']['tipos_retardo']['mayor']}\n";
        echo "   - Muy Mayor: {$reporte['estadisticas']['tipos_retardo']['muy_mayor']}\n\n";
        
        echo "✅ ESTADO DE JUSTIFICACIÓN:\n";
        echo "   - Justificados: {$reporte['estadisticas']['justificacion']['justificados']}\n";
        echo "   - No justificados: {$reporte['estadisticas']['justificacion']['no_justificados']}\n\n";
        
        echo "🔍 ESTADO DE VALIDACIÓN:\n";
        echo "   - Pendientes: {$reporte['estadisticas']['validacion']['pendientes']}\n";
        echo "   - Aprobados: {$reporte['estadisticas']['validacion']['aprobados']}\n";
        echo "   - Rechazados: {$reporte['estadisticas']['validacion']['rechazados']}\n\n";
        
        echo "👥 TOP 10 EMPLEADOS CON MÁS RETARDOS:\n";
        $empleados_ordenados = $reporte['estadisticas']['empleados_afectados'];
        usort($empleados_ordenados, function($a, $b) {
            return $b['total_retardos'] - $a['total_retardos'];
        });
        
        $top_10 = array_slice($empleados_ordenados, 0, 10);
        foreach ($top_10 as $i => $empleado) {
            echo "   " . ($i + 1) . ". {$empleado['nombre']} ({$empleado['id_empleado']}) - {$empleado['total_retardos']} retardos, {$empleado['minutos_totales']} minutos\n";
        }
    }
    
    /**
     * 📅 VERIFICA SI ES FIN DE SEMANA
     */
    private function esFinDeSemana($fecha) {
        $dia_semana = strtolower(date('l', strtotime($fecha)));
        return in_array($dia_semana, $this->configuracion['dias_fin_semana']);
    }
    
    /**
     * 📜 ACTUALIZA ESTADO DE VALIDACIÓN AUTOMÁTICA
     */
    public function actualizarValidacionAutomatica($dias_anteriores = 7) {
        echo "📜 ACTUALIZANDO VALIDACIONES AUTOMÁTICAMENTE\n";
        echo "Días anteriores: $dias_anteriores\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        try {
            $stmt = $this->db->getConnection()->prepare("
                UPDATE retardos 
                SET estado_validacion = 'aprobado',
                    aprobado_por = NULL,
                    fecha_aprobacion = NOW()
                WHERE fecha <= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                  AND estado_validacion = 'pendiente'
                  AND minutos_retardo <= 5
            ");
            
            $stmt->execute([$dias_anteriores]);
            $actualizados = $stmt->rowCount();
            
            echo "✅ Actualizadas $actualizados validaciones automáticamente\n";
            echo "   - Retardos menores de 5 días automáticamente aprobados\n";
            
            return [
                'exito' => true,
                'actualizados' => $actualizados,
                'dias_anteriores' => $dias_anteriores
            ];
            
        } catch (Exception $e) {
            echo "❌ Error en actualización automática: " . $e->getMessage() . "\n";
            return [
                'exito' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

?>