<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/BaseController.php';

class NotasMalasController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        
        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['superadmin', 'admin'])) {
            $_SESSION['error'] = 'No tienes permiso para acceder a notas malas.';
            $this->redirect('/dashboard');
            return;
        }
    }
    
    public function index() {
        $db = Database::getInstance()->getConnection();
        
        $where = "1=1";
        $params = [];
        
        // Filtros
        $empleado_id = $_GET['empleado_id'] ?? null;
        $mes = $_GET['mes'] ?? date('Y-m');
        $area_id = $_GET['area_id'] ?? null;
        
        if ($empleado_id) {
            $where .= " AND nm.empleado_id = ?";
            $params[] = $empleado_id;
        }
        
        if ($area_id) {
            $where .= " AND e.area = ?";
            $params[] = $area_id;
        }
        
        if ($mes) {
            $where .= " AND DATE_FORMAT(nm.periodo, '%Y-%m') = ?";
            $params[] = $mes;
        }
        
        // Obtener notas malas
        $stmt = $db->prepare("
            SELECT nm.*, 
                   e.nombre as empleado_nombre, 
                   e.apellido as empleado_apellido,
                   e.area as area_nombre
            FROM notas_malas nm
            LEFT JOIN empleados e ON nm.empleado_id = e.id
            WHERE $where
            ORDER BY nm.created_at DESC
        ");
        $stmt->execute($params);
        $notas_malas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener estadísticas
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_notas,
                SUM(CASE WHEN nm.tipo = 'retardo_menor' THEN nm.cantidad ELSE 0 END) as retardos_menores,
                SUM(CASE WHEN nm.tipo = 'retardo_mayor' THEN nm.cantidad ELSE 0 END) as retardos_mayores,
                COUNT(DISTINCT nm.empleado_id) as empleados_con_notas
            FROM notas_malas nm
            WHERE DATE_FORMAT(nm.periodo, '%Y-%m') = ?
        ");
        $stmt->execute([$mes]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener notas por área
        $stmt = $db->prepare("
            SELECT e.area as area, COUNT(*) as total, SUM(nm.cantidad) as cantidad
            FROM notas_malas nm
            LEFT JOIN empleados e ON nm.empleado_id = e.id
            WHERE DATE_FORMAT(nm.periodo, '%Y-%m') = ?
            GROUP BY e.area
            ORDER BY total DESC
        ");
        $stmt->execute([$mes]);
        $notas_por_area = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $content = '
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h2><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Notas Malas e Incidencias</h2>
                    <p class="text-secondary">Registro de retardos menores y mayores sin justificar</p>
                </div>
                <div class="col-md-4 text-end">
                    <form method="GET" action="' . BASE_URL . '/notas-malas" class="d-inline-flex">
                        <select name="mes" class="form-select me-2" onchange="this.form.submit()">
                            <option value="">Todos los meses</option>
                            <option value="2026-02"' . ($mes == '2026-02' ? ' selected' : '') . '>Febrero 2026</option>
                            <option value="2026-01"' . ($mes == '2026-01' ? ' selected' : '') . '>Enero 2026</option>
                            <option value="2025-12"' . ($mes == '2025-12' ? ' selected' : '') . '>Diciembre 2025</option>
                        </select>
                    </form>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #E8D5D9 0%, #C9A4AA 100%); border-left: 4px solid #9F2241;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0 fw-bold" style="color: #691C32;">' . ($stats['total_notas'] ?? 0) . '</h2>
                                    <small style="color: #4a1424;">Total Notas</small>
                                </div>
                                <i class="fas fa-exclamation-circle fa-2x" style="color: #9F2241;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #D5E5DE 0%, #B8CCBF 100%); border-left: 4px solid #235B4E;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0 fw-bold" style="color: #10312B;">' . ($stats['retardos_menores'] ?? 0) . '</h2>
                                    <small style="color: #10312B;">Retardos Menores</small>
                                </div>
                                <i class="fas fa-clock fa-2x" style="color: #235B4E;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #E8D5D5 0%, #CCB4B4 100%); border-left: 4px solid #C62828;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0 fw-bold" style="color: #8E0000;">' . ($stats['retardos_mayores'] ?? 0) . '</h2>
                                    <small style="color: #8E0000;">Retardos Mayores</small>
                                </div>
                                <i class="fas fa-exclamation-triangle fa-2x" style="color: #C62828;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #EDE4D3 0%, #DDC9A3 100%); border-left: 4px solid #BC955C;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0 fw-bold" style="color: #691C32;">' . ($stats['empleados_con_notas'] ?? 0) . '</h2>
                                    <small style="color: #691C32;">Empleados</small>
                                </div>
                                <i class="fas fa-users fa-2x" style="color: #691C32;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notas por Área -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Notas Malas por Área</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Área</th>
                                            <th class="text-center">Notas</th>
                                            <th class="text-center">Incidencias</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
        foreach ($notas_por_area as $area) {
            $content .= '
                                        <tr>
                                            <td>' . htmlspecialchars($area['area'] ?? 'Sin área') . '</td>
                                            <td class="text-center">' . $area['total'] . '</td>
                                            <td class="text-center"><strong>' . ($area['cantidad'] ?? 0) . '</strong></td>
                                        </tr>';
        }
        if (empty($notas_por_area)) {
            $content .= '
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No hay notas registradas</td>
                                        </tr>';
        }
        $content .= '
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detalle de Notas Malas -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Detalle de Notas Malas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tabla-notas-malas">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empleado</th>
                                    <th>Área</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Cantidad</th>
                                    <th>Período</th>
                                    <th>Motivo</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>';
        foreach ($notas_malas as $nota) {
            $tipoBadge = $nota['tipo'] == 'retardo_menor' 
                ? '<span class="badge bg-info">Retardo Menor</span>'
                : '<span class="badge bg-danger">Retardo Mayor</span>';
            
            $content .= '
                                <tr>
                                    <td>' . $nota['id'] . '</td>
                                    <td>' . htmlspecialchars(($nota['empleado_nombre'] ?? '') . ' ' . ($nota['empleado_apellido'] ?? '')) . '</td>
                                    <td>' . htmlspecialchars($nota['area_nombre'] ?? 'Sin área') . '</td>
                                    <td>' . $tipoBadge . '</td>
                                    <td class="text-center"><strong>' . $nota['cantidad'] . '</strong></td>
                                    <td>' . date('m/Y', strtotime($nota['periodo'])) . '</td>
                                    <td>' . htmlspecialchars($nota['motivo'] ?? '') . '</td>
                                    <td>' . date('d/m/Y H:i', strtotime($nota['created_at'])) . '</td>
                                </tr>';
        }
        if (empty($notas_malas)) {
            $content .= '
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>
                                        No hay notas malas registradas
                                    </td>
                                </tr>';
        }
        $content .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // DataTables deshabilitado temporalmente - usando tabla simple
        </script>
        ';
        
        include __DIR__ . '/../views/layout.php';
    }
    
    public function ajaxList() {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->query("
                SELECT nm.*, 
                       CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
                       e.area as area_nombre
                FROM notas_malas nm
                LEFT JOIN empleados e ON nm.empleado_id = e.id
                ORDER BY nm.created_at DESC
                LIMIT 100
            ");
            
            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
