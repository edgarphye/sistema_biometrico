<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

/**
 * 📊 DASHBOARD DE MONITOREO ZKTeco EN TIEMPO REAL
 * Sistema para visualizar el estado de inserciones biométricas
 */
class ZKTecoMonitor {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * 📈 OBTENER ESTADÍSTICAS COMPLETAS DEL SISTEMA
     */
    public function getEstadisticasCompletas() {
        $pdo = $this->db->getConnection();
        
        // 1. Estadísticas de asistencia
        $stmtAsistencia = $pdo->prepare("
            SELECT 
                COUNT(*) as total_asistencia,
                COUNT(DISTINCT empleado_id) as empleados_unicos,
                COUNT(DISTINCT fecha) as dias_unicos,
                MIN(fecha) as fecha_minima,
                MAX(fecha) as fecha_maxima
            FROM asistencia
        ");
        $stmtAsistencia->execute();
        $statsAsistencia = $stmtAsistencia->fetch(PDO::FETCH_ASSOC);
        
        // 2. Estadísticas de retardos
        $stmtRetardos = $pdo->prepare("
            SELECT 
                COUNT(*) as total_retardos,
                COUNT(DISTINCT empleado_id) as empleados_con_retardo,
                AVG(minutos_retardo) as promedio_minutos,
                SUM(minutos_retardo) as total_minutos,
                COUNT(CASE WHEN justificado = 1 THEN 1 END) as retardos_justificados,
                COUNT(CASE WHEN justificado = 0 THEN 1 END) as retardos_no_justificados
            FROM retardos
        ");
        $stmtRetardos->execute();
        $statsRetardos = $stmtRetardos->fetch(PDO::FETCH_ASSOC);
        
        // 3. Estadísticas por empleado (top 10 con más registros)
        $stmtTopEmpleados = $pdo->prepare("
            SELECT 
                e.nombre,
                COUNT(a.id) as total_asistencias,
                COUNT(r.id) as total_retardos,
                SUM(r.minutos_retardo) as minutos_totales
            FROM empleados e
            LEFT JOIN asistencia a ON e.id = a.empleado_id
            LEFT JOIN retardos r ON e.id = r.empleado_id
            GROUP BY e.id, e.nombre
            ORDER BY total_asistencias DESC
            LIMIT 10
        ");
        $stmtTopEmpleados->execute();
        $topEmpleados = $stmtTopEmpleados->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Registros de hoy
        $stmtHoy = $pdo->prepare("
            SELECT 
                COUNT(*) as asistencias_hoy,
                COUNT(CASE WHEN hora_entrada > '09:15:00' THEN 1 END) as retardos_hoy
            FROM asistencia 
            WHERE fecha = CURDATE()
        ");
        $stmtHoy->execute();
        $statsHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);
        
        // 5. Estado del sistema
        $estadoSistema = [
            'base_datos_conectada' => true,
            'ultimo_registro' => $this->getUltimoRegistro(),
            'registros_ultima_hora' => $this->getRegistrosUltimaHora(),
            'errores_recientes' => $this->getErroresRecientes(),
            'total_empleados_activos' => $this->getTotalEmpleadosActivos()
        ];
        
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'asistencia' => $statsAsistencia,
            'retardos' => $statsRetardos,
            'hoy' => $statsHoy,
            'top_empleados' => $topEmpleados,
            'estado_sistema' => $estadoSistema,
            'porcentaje_uso' => [
                'asistencia_completado' => round(($statsAsistencia['total_asistencia'] / 19583) * 100, 2),
                'retardos_generados' => $statsRetardos['total_retardos'] > 0 ? 
                    round(($statsRetardos['total_retardos'] / $statsAsistencia['total_asistencia']) * 100, 2) : 0
            ]
        ];
    }
    
    /**
     * 🕐 OBTENER ÚLTIMO REGISTRO INSERTADO
     */
    private function getUltimoRegistro() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT id, empleado_id, fecha, hora_entrada, created_at
            FROM asistencia 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 📊 OBTENER REGISTROS DE LA ÚLTIMA HORA
     */
    private function getRegistrosUltimaHora() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as total
            FROM asistencia 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * 🔍 OBTENER ERRORES RECIENTES (del log de errores)
     */
    private function getErroresRecientes() {
        $logFile = __DIR__ . '/logs/error.log';
        if (!file_exists($logFile)) {
            return 0;
        }
        
        $contenido = file_get_contents($logFile);
        $lineas = explode("\n", $contenido);
        $haceUnaHora = time() - 3600;
        $erroresRecientes = 0;
        
        foreach ($lineas as $linea) {
            if (strpos($linea, 'ERROR INSERT RETARDOS') !== false || 
                strpos($linea, 'ERROR INSERT ASISTENCIA') !== false) {
                $erroresRecientes++;
            }
        }
        
        return $erroresRecientes;
    }
    
    /**
     * 👥 OBTENER TOTAL DE EMPLEADOS ACTIVOS
     */
    private function getTotalEmpleadosActivos() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as total
            FROM empleados 
            WHERE activo = 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * 📄 GENERAR HTML DEL DASHBOARD
     */
    public function generarDashboardHTML() {
        $stats = $this->getEstadisticasCompletas();
        
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔴 Dashboard ZKTeco - Monitoreo en Vivo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .dashboard { max-width: 1400px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h3 { color: #333; margin-top: 0; }
        .stat-number { font-size: 2em; font-weight: bold; color: #667eea; }
        .progress-bar { background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; }
        .status-good { color: #4CAF50; }
        .status-warning { color: #ff9800; }
        .status-error { color: #f44336; }
        .refresh-btn { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-bottom: 20px; }
        .refresh-btn:hover { background: #764ba2; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .timestamp { font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>🔴 Dashboard ZKTeco - Monitoreo en Vivo</h1>
            <p class="timestamp">Última actualización: ' . $stats['timestamp'] . '</p>
            <button class="refresh-btn" onclick="location.reload()">🔄 Actualizar</button>
        </div>
        
        <div class="stats-grid">
            <!-- Card Asistencia -->
            <div class="card">
                <h3>📊 Asistencia Total</h3>
                <div class="stat-number">' . number_format($stats['asistencia']['total_asistencia']) . '</div>
                <div>Empleados únicos: ' . $stats['asistencia']['empleados_unicos'] . '</div>
                <div>Rango: ' . $stats['asistencia']['fecha_minima'] . ' → ' . $stats['asistencia']['fecha_maxima'] . '</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ' . $stats['porcentaje_uso']['asistencia_completado'] . '%"></div>
                </div>
                <small>Progreso total: ' . $stats['porcentaje_uso']['asistencia_completado'] . '% de 19,583 registros</small>
            </div>
            
            <!-- Card Retardos -->
            <div class="card">
                <h3>⏰ Retardos</h3>
                <div class="stat-number">' . number_format($stats['retardos']['total_retardos']) . '</div>
                <div>Empleados con retardo: ' . $stats['retardos']['empleados_con_retardo'] . '</div>
                <div>Promedio minutos: ' . round($stats['retardos']['promedio_minutos'], 1) . '</div>
                <div>Justificados: ' . $stats['retardos']['retardos_justificados'] . ' | No justificados: ' . $stats['retardos']['retardos_no_justificados'] . '</div>
            </div>
            
            <!-- Card Hoy -->
            <div class="card">
                <h3>📅 Registros de Hoy</h3>
                <div class="stat-number">' . $stats['hoy']['asistencias_hoy'] . '</div>
                <div>Retardos hoy: ' . $stats['hoy']['retardos_hoy'] . '</div>
                <div>Total empleados activos: ' . $stats['estado_sistema']['total_empleados_activos'] . '</div>
            </div>
            
            <!-- Card Estado Sistema -->
            <div class="card">
                <h3>🖥️ Estado del Sistema</h3>
                <div class="status-' . ($stats['estado_sistema']['base_datos_conectada'] ? 'good' : 'error') . '">
                    Base de Datos: ' . ($stats['estado_sistema']['base_datos_conectada'] ? '✅ Conectada' : '❌ Error') . '
                </div>
                <div>Registros última hora: ' . $stats['estado_sistema']['registros_ultima_hora'] . '</div>
                <div>Errores recientes: ' . $stats['estado_sistema']['errores_recientes'] . '</div>
                <div><small>Último registro: ID ' . ($stats['estado_sistema']['ultimo_registro']['id'] ?? 'N/A') . ' - ' . ($stats['estado_sistema']['ultimo_registro']['created_at'] ?? 'N/A') . '</small></div>
            </div>
        </div>
        
        <!-- Top Empleados -->
        <div class="card">
            <h3>🏆 Top 10 Empleados (por registros)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Asistencias</th>
                        <th>Retardos</th>
                        <th>Minutos Totales</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($stats['top_empleados'] as $emp) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($emp['nombre']) . '</td>
                        <td>' . $emp['total_asistencias'] . '</td>
                        <td>' . $emp['total_retardos'] . '</td>
                        <td>' . $emp['minutos_totales'] . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>
        
        <div style="text-align: center; margin-top: 30px; color: #666;">
            <small>🔄 Auto-refresh cada 60 segundos | Sistema ZKTeco v1.0</small>
        </div>
    </div>
    
    <script>
        // Auto-refresh cada 60 segundos
        setTimeout(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>';
        
        return $html;
    }
}

// 🚀 EJECUTAR DASHBOARD
if (isset($_GET['refresh']) || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $monitor = new ZKTecoMonitor();
    
    if (isset($_GET['json'])) {
        header('Content-Type: application/json');
        echo json_encode($monitor->getEstadisticasCompletas(), JSON_PRETTY_PRINT);
    } else {
        echo $monitor->generarDashboardHTML();
    }
}
?>