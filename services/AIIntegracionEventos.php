<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/AIActualizadorService.php';
require_once __DIR__ . '/AICompletoService.php';
require_once __DIR__ . '/PrediccionAvanzadaService.php';

class AIIntegracionEventos {
    private $db;
    private $actualizador;
    private $analisis;
    private $prediccion;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->actualizador = new AIActualizadorService();
        $this->analisis = new AICompletoService();
        $this->prediccion = new PrediccionAvanzadaService();
    }
    
    public function eventoRetardoCreado($empleadoId, $datosRetardo) {
        $resultado = [
            'evento' => 'retardo_creado',
            'empleado_id' => $empleadoId,
            'timestamp' => date('Y-m-d H:i:s'),
            'datos' => $datosRetardo
        ];
        
        try {
            $this->actualizador->procesarNuevoEvento($empleadoId, 'retardo', [
                'minutos' => $datosRetardo['minutos'] ?? 0,
                'fecha' => $datosRetardo['fecha'] ?? date('Y-m-d'),
                'tipo' => $datosRetardo['tipo'] ?? 'normal'
            ]);
            
            $resultado['ai_actualizado'] = true;
            
            $this->verificarUmbralesAlerta($empleadoId);
            
            $resultado['alertas_generadas'] = $this->generarAlertasPorEvento($empleadoId, 'retardo');
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    public function eventoRetardoJustificado($empleadoId, $datos) {
        $resultado = [
            'evento' => 'retardo_justificado',
            'empleado_id' => $empleadoId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            $this->actualizador->procesarNuevoEvento($empleadoId, 'justificacion', [
                'retardo_id' => $datos['retardo_id'] ?? null,
                'tipo_justificacion_id' => $datos['tipo_justificacion_id'] ?? null,
                'fecha' => $datos['fecha'] ?? date('Y-m-d')
            ]);
            
            $resultado['ai_actualizado'] = true;
            
            $resultado['analisis_actualizado'] = $this->analisis->analizarCompleto($empleadoId);
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    public function eventoAsistenciaRegistrada($empleadoId, $datosAsistencia) {
        $resultado = [
            'evento' => 'asistencia_registrada',
            'empleado_id' => $empleadoId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            $tipo = $datosAsistencia['tipo_asistencia'] ?? 'normal';
            
            if ($tipo === 'falta') {
                $this->actualizador->procesarNuevoEvento($empleadoId, 'falta', [
                    'fecha' => $datosAsistencia['fecha'] ?? date('Y-m-d')
                ]);
            } else {
                $horas = 0;
                if (!empty($datosAsistencia['hora_entrada']) && !empty($datosAsistencia['hora_salida'])) {
                    $entrada = strtotime($datosAsistencia['hora_entrada']);
                    $salida = strtotime($datosAsistencia['hora_salida']);
                    $horas = ($salida - $entrada) / 3600;
                }
                
                $this->actualizador->procesarNuevoEvento($empleadoId, 'asistencia', [
                    'horas_trabajadas' => $horas,
                    'fecha' => $datosAsistencia['fecha'] ?? date('Y-m-d')
                ]);
            }
            
            $resultado['ai_actualizado'] = true;
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    public function eventoVacacionRegistrada($empleadoId, $datosVacacion) {
        $resultado = [
            'evento' => 'vacacion_registrada',
            'empleado_id' => $empleadoId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            $dias = isset($datosVacacion['dias']) ? (int)$datosVacacion['dias'] : 1;
            
            $this->actualizador->procesarNuevoEvento($empleadoId, 'vacacion', [
                'dias' => $dias,
                'fecha_inicio' => $datosVacacion['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin' => $datosVacacion['fecha_fin'] ?? date('Y-m-d')
            ]);
            
            $resultado['ai_actualizado'] = true;
            
            $this->verificarLimiteVacaciones($empleadoId, $dias);
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    public function eventoLicenciaRegistrada($empleadoId, $datosLicencia) {
        $resultado = [
            'evento' => 'licencia_registrada',
            'empleado_id' => $empleadoId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            $dias = isset($datosLicencia['dias']) ? (int)$datosLicencia['dias'] : 1;
            
            $this->actualizador->procesarNuevoEvento($empleadoId, 'licencia', [
                'dias' => $dias,
                'fecha_inicio' => $datosLicencia['fecha_inicio'] ?? date('Y-m-d'),
                'tipo' => $datosLicencia['tipo'] ?? 'medica'
            ]);
            
            $resultado['ai_actualizado'] = true;
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    public function eventoDiaEconomicoRegistrado($empleadoId, $datos) {
        $resultado = [
            'evento' => 'dia_economico_registrado',
            'empleado_id' => $empleadoId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            $this->actualizador->procesarNuevoEvento($empleadoId, 'justificacion', [
                'tipo' => 'dia_economico',
                'fecha' => $datos['fecha'] ?? date('Y-m-d')
            ]);
            
            $resultado['ai_actualizado'] = true;
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    public function eventoSancionCreada($empleadoId, $datosSancion) {
        $resultado = [
            'evento' => 'sancion_creada',
            'empleado_id' => $empleadoId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO ai_alertas 
                (empleado_id, tipo_alerta, nivel_riesgo, mensaje, datos_json)
                VALUES (?, 'sancion', 'alto', ?, ?)
            ");
            
            $mensaje = 'Sanción creada: ' . ($datosSancion['tipo'] ?? 'amonestacion');
            $datosJson = json_encode($datosSancion);
            
            $stmt->execute([$empleadoId, $mensaje, $datosJson]);
            
            $resultado['alerta_creada'] = true;
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    public function eventoEmpleadoInactivo($empleadoId) {
        $resultado = [
            'evento' => 'empleado_inactivo',
            'empleado_id' => $empleadoId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO ai_alertas 
                (empleado_id, tipo_alerta, nivel_riesgo, mensaje)
                VALUES (?, 'inactivo', 'bajo', 'Empleado marcado como inactivo')
            ");
            
            $stmt->execute([$empleadoId]);
            
            $resultado['alerta_creada'] = true;
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    private function verificarUmbralesAlerta($empleadoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM retardos 
            WHERE empleado_id = ? AND fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$empleadoId]);
        $retardosSemana = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nivel = 'bajo';
        $mensaje = '';
        
        if ($retardosSemana['total'] >= 5) {
            $nivel = 'critico';
            $mensaje = "EMERGENCIA: {$retardosSemana['total']} retardos en 7 días";
        } elseif ($retardosSemana['total'] >= 3) {
            $nivel = 'alto';
            $mensaje = "Alerta: {$retardosSemana['total']} retardos en 7 días";
        }
        
        if ($nivel !== 'bajo') {
            $stmt = $pdo->prepare("
                INSERT INTO ai_alertas 
                (empleado_id, tipo_alerta, nivel_riesgo, mensaje)
                VALUES (?, 'retardos_frecuentes', ?, ?)
            ");
            $stmt->execute([$empleadoId, $nivel, $mensaje]);
        }
        
        return $nivel !== 'bajo';
    }
    
    private function verificarLimiteVacaciones($empleadoId, $diasNuevos) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT SUM(dias_solicitados) as total
            FROM vacaciones
            WHERE empleado_id = ? AND YEAR(fecha_inicio) = YEAR(CURDATE())
        ");
        $stmt->execute([$empleadoId]);
        $vacacionesAnio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalAnio = ($vacacionesAnio['total'] ?? 0) + $diasNuevos;
        
        if ($totalAnio > 9) {
            $stmt = $pdo->prepare("
                INSERT INTO ai_alertas 
                (empleado_id, tipo_alerta, nivel_riesgo, mensaje)
                VALUES (?, 'limite_vacaciones', 'medio', ?)
            ");
            $mensaje = "Límite de vacaciones excedido: {$totalAnio}/9 días";
            $stmt->execute([$empleadoId, $mensaje]);
        }
        
        return $totalAnio > 9;
    }
    
    private function generarAlertasPorEvento($empleadoId, $tipo) {
        $alertas = [];
        
        $metricas = $this->actualizador->getMetricasTiempoReal($empleadoId);
        
        if (($metricas['retardos'] ?? 0) >= 5) {
            $alertas[] = [
                'tipo' => 'urgente',
                'mensaje' => 'Múltiples retardos detectados'
            ];
        }
        
        return $alertas;
    }
    
    public function ejecutarAnalisisProgramado() {
        $resultado = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => 'analisis_programado'
        ];
        
        try {
            $resultado['actualizacion_datos'] = $this->actualizador->ejecutarAnalisisCompleto();
            
            $resultado['empleados_analizados'] = $resultado['actualizacion_datos']['empleados_analizados'];
            
            $pdo = $this->db->getConnection();
            $stmt = $pdo->query("
                SELECT COUNT(*) as total FROM ai_alertas 
                WHERE leida = FALSE AND nivel_riesgo IN ('alto', 'critico')
            ");
            $alertasCriticas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $resultado['alertas_criticas'] = $alertasCriticas['total'];
            
            $resultado['exito'] = true;
            
        } catch (Exception $e) {
            $resultado['error'] = $e->getMessage();
            $resultado['exito'] = false;
        }
        
        return $resultado;
    }
    
    public function obtenerResumenIA($empleadoId = null) {
        if ($empleadoId) {
            return [
                'empleado' => $this->analisis->analizarCompleto($empleadoId),
                'prediccion' => $this->prediccion->predecirRiesgoEmpleado($empleadoId, 30),
                'historico' => $this->actualizador->getHistoricoEmpleado($empleadoId, 30),
                'alertas' => $this->actualizador->getAlertasNoLeidas($empleadoId)
            ];
        } else {
            return [
                'dashboard' => $this->actualizador->getDashboardIA(),
                'prediccion_global' => $this->prediccion->predecirRiesgoGlobal(30),
                'alertas' => $this->actualizador->getAlertasNoLeidas()
            ];
        }
    }
}
