<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/PlantillaDocumento.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Sancion.php';

class GeneradorDocumentosService {
    private $plantillaModel;
    private $empleadoModel;
    private $retardoModel;
    private $sancionModel;

    public function __construct() {
        $this->plantillaModel = new PlantillaDocumento();
        $this->empleadoModel = new Empleado();
        $this->retardoModel = new Retardo();
        $this->sancionModel = new Sancion();
    }

    public function generarOficioNotasMalas($empleado_id, $periodo, $retardos = [], $autoridad = 'Recursos Humanos') {
        $empleado = $this->empleadoModel->getById($empleado_id);
        if (!$empleado) {
            return ['success' => false, 'error' => 'Empleado no encontrado'];
        }

        $jefe = $this->obtenerJefe($empleado);
        
        $tipos = $this->plantillaModel->getByTipo('oficio');
        $plantilla = $tipos[0] ?? null;
        
        if (!$plantilla) {
            return ['success' => false, 'error' => 'Plantilla de oficio no encontrada'];
        }

        $detalle = $this->generarDetalleIncidencias($retardos);
        $cantidad = count($retardos);

        $variables = [
            'OFICIO' => 'OFICIO DE NOTIFICACIÓN',
            'PERIODO' => $periodo,
            'nombre_jefe' => $jefe ? 'JEFA(E) INMEDIATO(A): ' . strtoupper($jefe['nombre'] . ' ' . $jefe['apellido']) : '',
            'nombre_empleado' => 'TRABAJADOR(A): ' . strtoupper($empleado['nombre'] . ' ' . $empleado['apellido']),
            'nombre_completo' => $empleado['nombre'] . ' ' . $empleado['apellido'],
            'rfc' => $empleado['rfc'] ?? 'N/A',
            'cantidad_notas' => $cantidad,
            'perido_detallado' => $periodo,
            'DETALLE_INCIDENCIAS' => $detalle,
            'nombre_autoridad' => $autoridad,
            'fecha_oficio' => date('d/m/Y')
        ];

        $contenido = $this->plantillaModel->render($plantilla['id'], $variables);

        return [
            'success' => true,
            'contenido' => $contenido,
            'empleado' => $empleado,
            'tipo' => 'oficio_notas_malas'
        ];
    }

    public function generarReporteIncidencias($fecha_inicio, $fecha_fin, $area = null, $tipo = 'retardos') {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $sql = "SELECT r.*, e.nombre, e.apellido, e.area, e.rfc
                FROM retardos r
                JOIN empleados e ON r.empleado_id = e.id
                WHERE r.fecha BETWEEN ? AND ? AND r.justificado = 0";
        $params = [$fecha_inicio, $fecha_fin];

        if ($area) {
            $sql .= " AND e.area = ?";
            $params[] = $area;
        }

        $sql .= " ORDER BY r.fecha DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $retardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $empleadosAgrupados = [];
        foreach ($retardos as $retardo) {
            $eid = $retardo['empleado_id'];
            if (!isset($empleadosAgrupados[$eid])) {
                $empleadosAgrupados[$eid] = [
                    'id' => $eid,
                    'nombre' => $retardo['nombre'] . ' ' . $retardo['apellido'],
                    'area' => $retardo['area'] ?? 'Sin área',
                    'retardos' => []
                ];
            }
            $empleadosAgrupados[$eid]['retardos'][] = $retardo;
        }

        $tipos = $this->plantillaModel->getByTipo('reporte');
        $plantilla = $tipos[0] ?? null;
        
        if (!$plantilla) {
            return $this->generarReporteBasico($retardos, $fecha_inicio, $fecha_fin, $area);
        }

        $detalle = $this->generarDetalleReporte($empleadosAgrupados);
        $total = count($retardos);

        $variables = [
            'PERIODO' => $fecha_inicio . ' al ' . $fecha_fin,
            'fecha_generacion' => date('d/m/Y H:i'),
            'area' => $area ?? 'TODAS',
            'total_incidencias' => $total,
            'total_retardos' => $total,
            'total_faltas' => 0,
            'total_no_validadas' => 0,
            'DETALLE_EMPLEADOS' => $detalle,
            'notas_adicionales' => 'Reporte generado automáticamente por el Agente IA de Gestión de Asistencia.'
        ];

        $contenido = $this->plantillaModel->render($plantilla['id'], $variables);

        return [
            'success' => true,
            'contenido' => $contenido,
            'resumen' => [
                'total' => $total,
                'por_empleado' => count($empleadosAgrupados)
            ]
        ];
    }

    private function obtenerJefe($empleado) {
        if (isset($empleado['jefe_directo_id']) && $empleado['jefe_directo_id']) {
            return $this->empleadoModel->getById($empleado['jefe_directo_id']);
        }
        return null;
    }

    private function generarDetalleIncidencias($retardos) {
        if (empty($retardos)) {
            return "No se encontraron incidencias pendientes de justificación.";
        }

        $detalle = "";
        foreach ($retardos as $i => $r) {
            $i++;
            $detalle .= "$i) Fecha: {$r['fecha']}, ";
            $detalle .= "Hora de entrada: " . ($r['hora_entrada'] ?? 'N/A') . ", ";
            $detalle .= "Minutos de retardo: " . ($r['minutos_retardo'] ?? 'N/A') . ", ";
            $detalle .= "Tipo: " . ($r['tipo_retraso'] ?? 'N/A') . "\n";
        }

        return $detalle;
    }

    private function generarDetalleReporte($empleados) {
        $detalle = "";
        foreach ($empleados as $emp) {
            $detalle .= "-----------------------------------------\n";
            $detalle .= "Empleado: {$emp['nombre']} (ID: {$emp['id']})\n";
            $detalle .= "Área: {$emp['area']}\n";
            $detalle .= "Incidencias: " . count($emp['retardos']) . "\n";
            foreach ($emp['retardos'] as $r) {
                $detalle .= "  - {$r['fecha']}: {$r['minutos_retardo']} min ({$r['tipo_retraso']})\n";
            }
            $detalle .= "\n";
        }
        return $detalle;
    }

    private function generarReporteBasico($retardos, $fecha_inicio, $fecha_fin, $area) {
        $html = "<h1>Reporte de Incidencias</h1>";
        $html .= "<p><strong>Período:</strong> $fecha_inicio al $fecha_fin</p>";
        $html .= "<p><strong>Área:</strong> " . ($area ?? 'TODAS') . "</p>";
        $html .= "<p><strong>Total incidencias:</strong> " . count($retardos) . "</p>";
        $html .= "<table border='1' cellpadding='5'>";
        $html .= "<tr><th>#</th><th>Empleado</th><th>Área</th><th>Fecha</th><th>Minutos</th><th>Tipo</th></tr>";
        
        foreach ($retardos as $i => $r) {
            $i++;
            $html .= "<tr>";
            $html .= "<td>{$i}</td>";
            $html .= "<td>{$r['nombre']} {$r['apellido']}</td>";
            $html .= "<td>{$r['area']}</td>";
            $html .= "<td>{$r['fecha']}</td>";
            $html .= "<td>{$r['minutos_retardo']}</td>";
            $html .= "<td>{$r['tipo_retraso']}</td>";
            $html .= "</tr>";
        }
        
        $html .= "</table>";
        
        return ['success' => true, 'contenido' => $html];
    }

    public function guardarDocumento($empleado_id, $tipo, $titulo, $contenido, $periodo) {
        $docModel = new DocumentoGenerado();
        return $docModel->create([
            'empleado_id' => $empleado_id,
            'tipo_documento' => $tipo,
            'titulo' => $titulo,
            'contenido' => $contenido,
            'periodo' => $periodo
        ]);
    }
}
