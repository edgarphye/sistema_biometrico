<?php

require_once __DIR__ . '/models/Database.php';

/**
 * 🎯 CLASIFICADOR BIOMÉTRICO AVANZADO v2.0
 * Lógica inteligente para entrada/salida basada en horarios reales
 */
class ZKTecoClasificadorAvanzado {
    private $db;
    private $horarios = [
        'entrada_mañana' => ['inicio' => '07:00', 'fin' => '12:00'],
        'salida_comida' => ['inicio' => '12:00', 'fin' => '14:00'],
        'entrada_comida' => ['inicio' => '14:00', 'fin' => '15:00'],
        'salida_tarde' => ['inicio' => '17:00', 'fin' => '20:00']
    ];
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * 🎯 CLASIFICA REGISTRO COMO ENTRADA O SALIDA
     * Lógica por rangos horarios + secuencia + dispositivo
     */
    public function clasificarRegistro($registro) {
        $hora = $registro['hora'] ?? '00:00:00';
        $fecha = $registro['fecha'] ?? date('Y-m-d');
        $empleado_id = $registro['empleado_id'] ?? 0;
        $accion_dispositivo = $registro['accion'] ?? null;
        
        // 1️⃣ Prioridad 1: Acción explícita del dispositivo
        if ($accion_dispositivo !== null) {
            if ($accion_dispositivo === 0) {
                return [
                    'tipo' => 'entrada',
                    'motivo' => 'accion_dispositivo_entrada',
                    'confianza' => 'alta',
                    'detalles' => 'Dispositivo marca explícitamente como entrada'
                ];
            } elseif ($accion_dispositivo === 1) {
                return [
                    'tipo' => 'salida',
                    'motivo' => 'accion_dispositivo_salida',
                    'confianza' => 'alta',
                    'detalles' => 'Dispositivo marca explícitamente como salida'
                ];
            }
        }
        
        // 2️⃣ Prioridad 2: Análisis por rangos horarios
        $rango = $this->determinarRangoHorario($hora);
        
        switch ($rango) {
            case 'entrada_mañana':
                return [
                    'tipo' => 'entrada',
                    'motivo' => 'rango_horario_mañana',
                    'confianza' => 'alta',
                    'detalles' => "Hora $hora en rango 7:00-12:00 (entrada mañana)"
                ];
                
            case 'salida_comida':
                return [
                    'tipo' => 'salida',
                    'motivo' => 'rango_horario_comida',
                    'confianza' => 'alta',
                    'detalles' => "Hora $hora en rango 12:00-14:00 (salida comida)"
                ];
                
            case 'entrada_comida':
                return [
                    'tipo' => 'entrada',
                    'motivo' => 'rango_horario_comida',
                    'confianza' => 'alta',
                    'detalles' => "Hora $hora en rango 14:00-15:00 (entrada comida)"
                ];
                
            case 'salida_tarde':
                return [
                    'tipo' => 'salida',
                    'motivo' => 'rango_horario_tarde',
                    'confianza' => 'alta',
                    'detalles' => "Hora $hora en rango 17:00-20:00 (salida tarde)"
                ];
        }
        
        // 3️⃣ Prioridad 3: Análisis por secuencia del día
        $resultado_secuencia = $this->analizarPorSecuencia($registro);
        
        if ($resultado_secuencia) {
            return $resultado_secuencia;
        }
        
        // 4️⃣ Prioridad 4: Heurísticas generales
        return $this->clasificarPorHeuristica($registro);
    }
    
    /**
     * Determina el rango horario de una hora específica
     */
    private function determinarRangoHorario($hora) {
        $hora_num = $this->horaANumero($hora);
        
        foreach ($this->horarios as $nombre => $rango) {
            $inicio_num = $this->horaANumero($rango['inicio']);
            $fin_num = $this->horaANumero($rango['fin']);
            
            if ($hora_num >= $inicio_num && $hora_num <= $fin_num) {
                return $nombre;
            }
        }
        
        return 'fuera_rango';
    }
    
    /**
     * Analiza por secuencia de registros del día
     */
    private function analizarPorSecuencia($registro) {
        $sql = "
            SELECT hora_entrada, hora_salida, created_at
            FROM asistencia 
            WHERE empleado_id = ? AND fecha = ?
            ORDER BY id DESC
            LIMIT 3
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$registro['empleado_id'], $registro['fecha']]);
        $registros_anteriores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($registros_anteriores)) {
            // Primer registro del día
            return [
                'tipo' => 'entrada',
                'motivo' => 'primera_marca_dia',
                'confianza' => 'muy_alta',
                'detalles' => 'Es la primera marca del día para este empleado'
            ];
        }
        
        $ultimo_registro = $registros_anteriores[0];
        
        // Si el último registro tiene entrada pero no salida
        if ($ultimo_registro['hora_entrada'] && !$ultimo_registro['hora_salida']) {
            return [
                'tipo' => 'salida',
                'motivo' => 'completar_par_entrada_salida',
                'confianza' => 'alta',
                'detalles' => 'Completa par entrada-salida existente'
            ];
        }
        
        // Si el último registro ya tiene entrada y salida
        if ($ultimo_registro['hora_entrada'] && $ultimo_registro['hora_salida']) {
            return [
                'tipo' => 'entrada',
                'motivo' => 'nuevo_ciclo_trabajo',
                'confianza' => 'alta',
                'detalles' => 'Nuevo ciclo de trabajo (posible regreso de comida)'
            ];
        }
        
        return null; // No se pudo determinar por secuencia
    }
    
    /**
     * Clasificación por heurísticas generales
     */
    private function clasificarPorHeuristica($registro) {
        $hora_num = $this->horaANumero($registro['hora']);
        
        // Heurística principal: antes de mediodía generalmente es entrada
        if ($hora_num < 1200) {
            return [
                'tipo' => 'entrada',
                'motivo' => 'heuristica_manana',
                'confianza' => 'media',
                'detalles' => 'Hora antes de mediodía, probablemente entrada'
            ];
        } else {
            return [
                'tipo' => 'salida',
                'motivo' => 'heuristica_tarde',
                'confianza' => 'media',
                'detalles' => 'Hora después de mediodía, probablemente salida'
            ];
        }
    }
    
    /**
     * Convierte hora a número para comparaciones
     */
    private function horaANumero($hora) {
        $partes = explode(':', $hora);
        return (int)($partes[0] . str_pad($partes[1] ?? '00', 2, '0', STR_PAD_LEFT));
    }
    
    /**
     * 📊 ANALIZA PATRONES DE UN EMPLEADO
     */
    public function analizarPatronesEmpleado($empleado_id, $dias = 30) {
        $sql = "
            SELECT 
                DATE(fecha) as dia,
                hora_entrada,
                hora_salida,
                DAYOFWEEK(fecha) as dia_semana
            FROM asistencia 
            WHERE empleado_id = ? AND fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY fecha DESC, hora_entrada
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$empleado_id, $dias]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $patrones = [
            'total_dias' => 0,
            'promedio_entrada' => null,
            'promedio_salida' => null,
            'dias_con_comida' => 0,
            'dias_completos' => 0,
            'frecuencia_dias_semana' => array_fill(1, 7, 0)
        ];
        
        if (empty($registros)) {
            return $patrones;
        }
        
        $horas_entrada = [];
        $horas_salida = [];
        
        foreach ($registros as $registro) {
            $patrones['total_dias']++;
            $patrones['frecuencia_dias_semana'][$registro['dia_semana']]++;
            
            if ($registro['hora_entrada']) {
                $horas_entrada[] = $registro['hora_entrada'];
            }
            
            if ($registro['hora_salida']) {
                $horas_salida[] = $registro['hora_salida'];
            }
            
            if ($registro['hora_entrada'] && $registro['hora_salida']) {
                $patrones['dias_completos']++;
                
                // Detectar patrones de comida
                $entrada_num = $this->horaANumero($registro['hora_entrada']);
                $salida_num = $this->horaANumero($registro['hora_salida']);
                
                if ($salida_num > 1200 && $salida_num < 1400) {
                    $patrones['dias_con_comida']++;
                }
            }
        }
        
        if (!empty($horas_entrada)) {
            $patrones['promedio_entrada'] = $this->calcularPromedioHoras($horas_entrada);
        }
        
        if (!empty($horas_salida)) {
            $patrones['promedio_salida'] = $this->calcularPromedioHoras($horas_salida);
        }
        
        return $patrones;
    }
    
    /**
     * Calcula promedio de horas en formato HH:MM
     */
    private function calcularPromedioHoras($horas) {
        $total_segundos = 0;
        
        foreach ($horas as $hora) {
            $partes = explode(':', $hora);
            $total_segundos += ($partes[0] * 3600) + ($partes[1] * 60);
        }
        
        $promedio_segundos = $total_segundos / count($horas);
        $horas = floor($promedio_segundos / 3600);
        $minutos = floor(($promedio_segundos % 3600) / 60);
        
        return sprintf('%02d:%02d', $horas, $minutos);
    }
    
    /**
     * 📈 GENERA REPORTE DE CLASIFICACIÓN
     */
    public function generarReporteClasificacion($registros) {
        $reporte = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_registros' => count($registros),
            'clasificaciones' => [
                'entrada' => ['conteo' => 0, 'motivos' => []],
                'salida' => ['conteo' => 0, 'motivos' => []]
            ],
            'detalles' => []
        ];
        
        foreach ($registros as $registro) {
            $clasificacion = $this->clasificarRegistro($registro);
            
            $reporte['clasificaciones'][$clasificacion['tipo']]['conteo']++;
            
            $motivo = $clasificacion['motivo'];
            if (!isset($reporte['clasificaciones'][$clasificacion['tipo']]['motivos'][$motivo])) {
                $reporte['clasificaciones'][$clasificacion['tipo']]['motivos'][$motivo] = 0;
            }
            $reporte['clasificaciones'][$clasificacion['tipo']]['motivos'][$motivo]++;
            
            $reporte['detalles'][] = [
                'empleado_id' => $registro['empleado_id'],
                'fecha' => $registro['fecha'],
                'hora' => $registro['hora'],
                'tipo' => $clasificacion['tipo'],
                'motivo' => $clasificacion['motivo'],
                'confianza' => $clasificacion['confianza'],
                'detalles' => $clasificacion['detalles']
            ];
        }
        
        return $reporte;
    }
}

?>