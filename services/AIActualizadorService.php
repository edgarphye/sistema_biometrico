<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/MachineLearningEngine.php';

class AIActualizadorService {
    private $db;
    private $ml;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->ml = new MachineLearningEngine();
        $this->inicializarTablas();
    }
    
    private function inicializarTablas() {
        $pdo = $this->db->getConnection();
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ai_snapshots_diarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empleado_id INT NOT NULL,
                fecha DATE NOT NULL,
                retardos_dia INT DEFAULT 0,
                retardos_minutos INT DEFAULT 0,
                justificaciones_dia INT DEFAULT 0,
                faltas_dia INT DEFAULT 0,
                horas_trabajadas DECIMAL(5,2) DEFAULT 0,
                indice_riesgo_dia DECIMAL(5,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_empleado_fecha (empleado_id, fecha),
                INDEX idx_empleado (empleado_id),
                INDEX idx_fecha (fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ai_metricas_tiempo_real (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empleado_id INT NOT NULL,
                tipo_evento VARCHAR(50) NOT NULL,
                valor_anterior DECIMAL(10,2) DEFAULT 0,
                valor_nuevo DECIMAL(10,2) DEFAULT 0,
                diferencia DECIMAL(10,2) DEFAULT 0,
                indice_riesgo_actual DECIMAL(5,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_empleado (empleado_id),
                INDEX idx_fecha (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ai_pesos_entrenamiento (
                id INT AUTO_INCREMENT PRIMARY KEY,
                modelo VARCHAR(50) NOT NULL,
                empleado_id INT,
                epocas INT DEFAULT 0,
                error_final DECIMAL(10,6) DEFAULT 0,
                pesos_json JSON,
                bias DECIMAL(10,6) DEFAULT 0,
                accuracy DECIMAL(5,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_modelo (modelo),
                INDEX idx_empleado (empleado_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ai_alertas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empleado_id INT NOT NULL,
                tipo_alerta VARCHAR(50) NOT NULL,
                nivel_riesgo ENUM('bajo', 'medio', 'alto', 'critico') DEFAULT 'medio',
                mensaje TEXT,
                datos_json JSON,
                leida BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_empleado (empleado_id),
                INDEX idx_leida (leida),
                INDEX idx_fecha (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
    
    public function procesarNuevoEvento($empleadoId, $tipoEvento, $datosEvento) {
        $pdo = $this->db->getConnection();
        
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        switch ($tipoEvento) {
            case 'retardo':
                $this->registrarRetardo($pdo, $empleadoId, $datosEvento, $fecha);
                break;
            case 'asistencia':
                $this->registrarAsistencia($pdo, $empleadoId, $datosEvento, $fecha);
                break;
            case 'justificacion':
                $this->registrarJustificacion($pdo, $empleadoId, $datosEvento, $fecha);
                break;
            case 'falta':
                $this->registrarFalta($pdo, $empleadoId, $datosEvento, $fecha);
                break;
            case 'vacacion':
                $this->registrarVacacion($pdo, $empleadoId, $datosEvento, $fecha);
                break;
            case 'licencia':
                $this->registrarLicencia($pdo, $empleadoId, $datosEvento, $fecha);
                break;
        }
        
        $this->actualizarIndiceRiesgo($empleadoId);
        $this->verificarAlertas($empleadoId);
        
        return ['success' => true, 'fecha' => $fecha, 'tipo' => $tipoEvento];
    }
    
    private function registrarRetardo($pdo, $empleadoId, $datos, $fecha) {
        $stmt = $pdo->prepare("
            INSERT INTO ai_metricas_tiempo_real 
            (empleado_id, tipo_evento, valor_nuevo, indice_riesgo_actual)
            VALUES (?, 'retardo', ?, ?)
        ");
        
        $minutos = $datos['minutos'] ?? 0;
        $indiceRiesgo = min(100, ($minutos / 60) * 100);
        
        $stmt->execute([$empleadoId, $minutos, $indiceRiesgo]);
        
        $this->actualizarSnapshotDiario($pdo, $empleadoId, $fecha, 'retardos_dia', 1);
        $this->actualizarSnapshotDiario($pdo, $empleadoId, $fecha, 'retardos_minutos', $minutos);
    }
    
    private function registrarAsistencia($pdo, $empleadoId, $datos, $fecha) {
        $horas = $datos['horas_trabajadas'] ?? 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO ai_metricas_tiempo_real 
            (empleado_id, tipo_evento, valor_nuevo, indice_riesgo_actual)
            VALUES (?, 'asistencia', ?, ?)
        ");
        
        $indiceRiesgo = $horas < 4 ? 80 : ($horas < 8 ? 50 : 10);
        
        $stmt->execute([$empleadoId, $horas, $indiceRiesgo]);
        
        $this->actualizarSnapshotDiario($pdo, $empleadoId, $fecha, 'horas_trabajadas', $horas);
    }
    
    private function registrarJustificacion($pdo, $empleadoId, $datos, $fecha) {
        $stmt = $pdo->prepare("
            INSERT INTO ai_metricas_tiempo_real 
            (empleado_id, tipo_evento, valor_nuevo, indice_riesgo_actual)
            VALUES (?, 'justificacion', ?, ?)
        ");
        
        $indiceRiesgo = 30;
        
        $stmt->execute([$empleadoId, 1, $indiceRiesgo]);
        
        $this->actualizarSnapshotDiario($pdo, $empleadoId, $fecha, 'justificaciones_dia', 1);
    }
    
    private function registrarFalta($pdo, $empleadoId, $datos, $fecha) {
        $stmt = $pdo->prepare("
            INSERT INTO ai_metricas_tiempo_real 
            (empleado_id, tipo_evento, valor_nuevo, indice_riesgo_actual)
            VALUES (?, 'falta', ?, ?)
        ");
        
        $indiceRiesgo = 100;
        
        $stmt->execute([$empleadoId, 1, $indiceRiesgo]);
        
        $this->actualizarSnapshotDiario($pdo, $empleadoId, $fecha, 'faltas_dia', 1);
    }
    
    private function registrarVacacion($pdo, $empleadoId, $datos, $fecha) {
        $dias = $datos['dias'] ?? 1;
        
        $stmt = $pdo->prepare("
            INSERT INTO ai_metricas_tiempo_real 
            (empleado_id, tipo_evento, valor_nuevo, indice_riesgo_actual)
            VALUES (?, 'vacacion', ?, ?)
        ");
        
        $indiceRiesgo = $dias * 15;
        
        $stmt->execute([$empleadoId, $dias, $indiceRiesgo]);
    }
    
    private function registrarLicencia($pdo, $empleadoId, $datos, $fecha) {
        $dias = $datos['dias'] ?? 1;
        
        $stmt = $pdo->prepare("
            INSERT INTO ai_metricas_tiempo_real 
            (empleado_id, tipo_evento, valor_nuevo, indice_riesgo_actual)
            VALUES (?, 'licencia', ?, ?)
        ");
        
        $indiceRiesgo = $dias * 10;
        
        $stmt->execute([$empleadoId, $dias, $indiceRiesgo]);
    }
    
    private function actualizarSnapshotDiario($pdo, $empleadoId, $fecha, $campo, $valor) {
        $allowed = ['retardos_dia', 'retardos_minutos', 'faltas_dia', 'asistencias_dia', 'incidencias_dia'];
        if (!in_array($campo, $allowed, true)) {
            return;
        }
        $stmt = $pdo->prepare("
            INSERT INTO ai_snapshots_diarios (empleado_id, fecha, $campo)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE $campo = $campo + VALUES($campo)
        ");
        $stmt->execute([$empleadoId, $fecha, $valor]);
    }
    
    public function actualizarIndiceRiesgo($empleadoId) {
        $pdo = $this->db->getConnection();
        
        // Obtener el rango de fechas disponibles
        $stmt = $pdo->prepare("
            SELECT MAX(fecha) as max_fecha, MIN(fecha) as min_fecha
            FROM ai_snapshots_diarios
            WHERE empleado_id = ?
        ");
        $stmt->execute([$empleadoId]);
        $rango = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $fechaMax = $rango['max_fecha'] ?? date('Y-m-d');
        $fechaMin = $rango['min_fecha'] ?? date('Y-m-d');
        
        // Calcular hace 30 y 90 días desde la última fecha
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(retardos_dia), 0) as total_retardos,
                COALESCE(SUM(retardos_minutos), 0) as total_minutos,
                COALESCE(SUM(justificaciones_dia), 0) as total_justificaciones,
                COALESCE(SUM(faltas_dia), 0) as total_faltas,
                AVG(horas_trabajadas) as promedio_horas
            FROM ai_snapshots_diarios
            WHERE empleado_id = ? AND fecha >= DATE_SUB(?, INTERVAL 30 DAY)
        ");
        $stmt->execute([$empleadoId, $fechaMax]);
        $datos30dias = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(retardos_dia), 0) as total_retardos,
                COALESCE(SUM(faltas_dia), 0) as total_faltas
            FROM ai_snapshots_diarios
            WHERE empleado_id = ? AND fecha >= DATE_SUB(?, INTERVAL 90 DAY)
        ");
        $stmt->execute([$empleadoId, $fechaMax]);
        $datos90dias = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $puntuacion30dias = (
            ($datos30dias['total_retardos'] ?? 0) * 5 +
            ($datos30dias['total_minutos'] ?? 0) / 10 +
            ($datos30dias['total_justificaciones'] ?? 0) * 3 +
            ($datos30dias['total_faltas'] ?? 0) * 20
        );
        
        $puntuacion90dias = (
            ($datos90dias['total_retardos'] ?? 0) * 2 +
            ($datos90dias['total_faltas'] ?? 0) * 10
        );
        
        $indiceRiesgo = min(100, ($puntuacion30dias * 0.7 + $puntuacion90dias * 0.3));
        
        // Actualizar todos los snapshots del empleado con el índice de riesgo
        $stmt = $pdo->prepare("
            UPDATE ai_snapshots_diarios 
            SET indice_riesgo_dia = ?
            WHERE empleado_id = ?
        ");
        $stmt->execute([$indiceRiesgo, $empleadoId]);
        
        // Actualizar el último snapshot con la fecha más reciente
        $stmt = $pdo->prepare("
            UPDATE ai_snapshots_diarios 
            SET indice_riesgo_dia = ?
            WHERE empleado_id = ? AND fecha = ?
        ");
        $stmt->execute([$indiceRiesgo * 1.2, $empleadoId, $fechaMax]); // Mayor riesgo en el snapshot más reciente
        
        $stmt = $pdo->prepare("
            INSERT INTO ai_pesos_entrenamiento 
            (modelo, empleado_id, epocas, error_final, accuracy)
            VALUES ('riesgo_tiempo_real', ?, 1, ?, ?)
            ON DUPLICATE KEY UPDATE 
                created_at = CURRENT_TIMESTAMP,
                accuracy = VALUES(accuracy)
        ");
        $stmt->execute([$empleadoId, $indiceRiesgo / 100, $indiceRiesgo]);
        
        return $indiceRiesgo;
    }
    
    public function getHistoricoEmpleado($empleadoId, $dias = 90) {
        $pdo = $this->db->getConnection();
        
        // Obtener rango de fechas real
        $stmt = $pdo->query("
            SELECT MAX(fecha) as max_fecha FROM ai_snapshots_diarios
        ");
        $rango = $stmt->fetch(PDO::FETCH_ASSOC);
        $fechaMax = $rango['max_fecha'] ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT * FROM ai_snapshots_diarios
            WHERE empleado_id = ? AND fecha >= DATE_SUB(?, INTERVAL " . (int)$dias . " DAY)
            ORDER BY fecha ASC
        ");
        $stmt->execute([$empleadoId, $fechaMax]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMetricasTiempoReal($empleadoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_eventos,
                SUM(CASE WHEN tipo_evento = 'retardo' THEN 1 ELSE 0 END) as retardos,
                SUM(CASE WHEN tipo_evento = 'falta' THEN 1 ELSE 0 END) as faltas,
                SUM(CASE WHEN tipo_evento = 'justificacion' THEN 1 ELSE 0 END) as justificaciones,
                SUM(valor_nuevo) as valor_total,
                MAX(indice_riesgo_actual) as max_riesgo,
                AVG(indice_riesgo_actual) as promedio_riesgo
            FROM ai_metricas_tiempo_real
            WHERE empleado_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$empleadoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function verificarAlertas($empleadoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN tipo_evento = 'retardo' THEN 1 ELSE 0 END) as total_retardos_7dias,
                SUM(CASE WHEN tipo_evento = 'falta' THEN 1 ELSE 0 END) as total_faltas_7dias
            FROM ai_metricas_tiempo_real
            WHERE empleado_id = ? 
            AND tipo_evento IN ('retardo', 'falta')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$empleadoId]);
        $eventos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nivel = 'bajo';
        $mensaje = '';
        
        if ($eventos['total_retardos_7dias'] >= 5) {
            $nivel = 'alto';
            $mensaje = "Alerta: {$eventos['total_retardos_7dias']} retardos en los últimos 7 días";
        } elseif ($eventos['total_retardos_7dias'] >= 3) {
            $nivel = 'medio';
            $mensaje = "Precaución: {$eventos['total_retardos_7dias']} retardos en los últimos 7 días";
        }
        
        if ($eventos['total_faltas_7dias'] >= 2) {
            $nivel = 'critico';
            $mensaje = "CRÍTICO: {$eventos['total_faltas_7dias']} faltas en los últimos 7 días";
        }
        
        if ($nivel !== 'bajo') {
            $stmt = $pdo->prepare("
                INSERT INTO ai_alertas (empleado_id, tipo_alerta, nivel_riesgo, mensaje)
                VALUES (?, 'eventos_recientes', ?, ?)
            ");
            $stmt->execute([$empleadoId, $nivel, $mensaje]);
        }
    }
    
    public function getAlertasNoLeidas($empleadoId = null) {
        $pdo = $this->db->getConnection();
        
        if ($empleadoId) {
            $stmt = $pdo->prepare("
                SELECT * FROM ai_alertas
                WHERE leida = FALSE AND empleado_id = ?
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$empleadoId]);
        } else {
            $stmt = $pdo->query("
                SELECT * FROM ai_alertas
                WHERE leida = FALSE
                ORDER BY created_at DESC
                LIMIT 20
            ");
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function marcarAlertaLeida($alertaId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("UPDATE ai_alertas SET leida = TRUE WHERE id = ?");
        $stmt->execute([$alertaId]);
        
        return ['success' => true];
    }
    
    public function ejecutarAnalisisCompleto($fechaInicio = null, $fechaFin = null) {
        $pdo = $this->db->getConnection();
        
        $fechaInicio = $fechaInicio ?? date('Y-m-01');
        $fechaFin = $fechaFin ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("SELECT id FROM empleados WHERE activo = 1");
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultados = [];
        
        foreach ($empleados as $emp) {
            $empleadoId = $emp['id'];
            
            $this->procesarDatosHistoricos($pdo, $empleadoId, $fechaInicio, $fechaFin);
            $indiceRiesgo = $this->actualizarIndiceRiesgo($empleadoId);
            $this->verificarAlertas($empleadoId);
            
            $resultados[] = [
                'empleado_id' => $empleadoId,
                'indice_riesgo' => $indiceRiesgo,
                'analizado' => true
            ];
        }
        
        return [
            'periodo' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
            'empleados_analizados' => count($resultados),
            'resultados' => $resultados
        ];
    }
    
    private function procesarDatosHistoricos($pdo, $empleadoId, $fechaInicio, $fechaFin) {
        $stmt = $pdo->prepare("
            SELECT DATE(fecha) as fecha, COUNT(*) as total, SUM(minutos_retardo) as minutos
            FROM retardos
            WHERE empleado_id = ? AND fecha BETWEEN ? AND ?
            GROUP BY DATE(fecha)
        ");
        $stmt->execute([$empleadoId, $fechaInicio, $fechaFin]);
        $retardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($retardos as $r) {
            $this->actualizarSnapshotDiario($pdo, $empleadoId, $r['fecha'], 'retardos_dia', $r['total']);
            $this->actualizarSnapshotDiario($pdo, $empleadoId, $r['fecha'], 'retardos_minutos', $r['minutos']);
        }
        
        $stmt = $pdo->prepare("
            SELECT DATE(fecha) as fecha, COUNT(*) as total
            FROM asistencia
            WHERE empleado_id = ? AND tipo_asistencia = 'falta' AND fecha BETWEEN ? AND ?
            GROUP BY DATE(fecha)
        ");
        $stmt->execute([$empleadoId, $fechaInicio, $fechaFin]);
        $faltas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($faltas as $f) {
            $this->actualizarSnapshotDiario($pdo, $empleadoId, $f['fecha'], 'faltas_dia', $f['total']);
        }
        
        $stmt = $pdo->prepare("
            SELECT DATE(fecha) as fecha, COUNT(*) as total
            FROM asistencia
            WHERE empleado_id = ? AND tipo_justificacion_id IS NOT NULL AND fecha BETWEEN ? AND ?
            GROUP BY DATE(fecha)
        ");
        $stmt->execute([$empleadoId, $fechaInicio, $fechaFin]);
        $justificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($justificaciones as $j) {
            $this->actualizarSnapshotDiario($pdo, $empleadoId, $j['fecha'], 'justificaciones_dia', $j['total']);
        }
    }
    
    public function getDashboardIA() {
        $pdo = $this->db->getConnection();
        
        // Obtener rango de fechas real
        $stmt = $pdo->query("
            SELECT MAX(fecha) as max_fecha, MIN(fecha) as min_fecha
            FROM ai_snapshots_diarios
        ");
        $rango = $stmt->fetch(PDO::FETCH_ASSOC);
        $fechaMax = $rango['max_fecha'] ?? date('Y-m-d');
        $fecha30 = date('Y-m-d', strtotime($fechaMax . ' -30 days'));
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT empleado_id) as empleados_activos,
                COUNT(*) as total_snapshots,
                AVG(indice_riesgo_dia) as promedio_riesgo_global,
                MAX(indice_riesgo_dia) as max_riesgo,
                MIN(indice_riesgo_dia) as min_riesgo
            FROM ai_snapshots_diarios
            WHERE fecha >= ?
        ");
        $stmt->execute([$fecha30]);
        $metricasGlobales = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT e.id as empleado_id, e.nombre, e.apellido, e.area,
                MAX(s.indice_riesgo_dia) as max_riesgo,
                SUM(s.retardos_dia) as total_retardos,
                SUM(s.retardos_minutos) as total_minutos,
                SUM(s.faltas_dia) as total_faltas
            FROM ai_snapshots_diarios s
            JOIN empleados e ON s.empleado_id = e.id
            WHERE s.fecha >= ?
            GROUP BY e.id, e.nombre, e.apellido, e.area
            ORDER BY max_riesgo DESC, total_retardos DESC
            LIMIT 15
        ");
        $stmt->execute([$fecha30]);
        $topRiesgo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("
            SELECT COUNT(*) as total, nivel_riesgo
            FROM ai_alertas
            WHERE leida = FALSE
            GROUP BY nivel_riesgo
        ");
        $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'metricas_globales' => $metricasGlobales,
            'top_riesgo' => $topRiesgo,
            'alertas_pendientes' => $alertas,
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
            'rango_fechas' => ['inicio' => $fecha30, 'fin' => $fechaMax]
        ];
    }
}
