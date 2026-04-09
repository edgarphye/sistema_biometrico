<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/BitacoraAgente.php';
require_once __DIR__ . '/../models/AnomaliaDetectada.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ExportadorExcelService {
    private $db;
    private $empleadoModel;
    private $retardoModel;
    private $asistenciaModel;
    private $bitacoraModel;
    private $anomaliaModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->empleadoModel = new Empleado();
        $this->retardoModel = new Retardo();
        $this->asistenciaModel = new Asistencia();
        $this->bitacoraModel = new BitacoraAgente();
        $this->anomaliaModel = new AnomaliaDetectada();
    }

    public function exportarReporteGeneral($fecha_inicio, $fecha_fin, $area = null) {
        $spreadsheet = new Spreadsheet();
        
        $this->generarHojaResumen($spreadsheet, $fecha_inicio, $fecha_fin, $area);
        $this->generarHojaRetardos($spreadsheet, $fecha_inicio, $fecha_fin, $area);
        $this->generarHojaFaltas($spreadsheet, $fecha_inicio, $fecha_fin, $area);
        $this->generarHojaNoValidadas($spreadsheet, $fecha_inicio, $fecha_fin, $area);
        $this->generarHojaAnomalias($spreadsheet, $fecha_inicio, $fecha_fin);
        $this->generarHojaKPIs($spreadsheet, $fecha_inicio, $fecha_fin, $area);

        return $this->guardarArchivo($spreadsheet, 'reporte_asistencia_' . date('Y-m-d'));
    }

    private function generarHojaResumen($spreadsheet, $fecha_inicio, $fecha_fin, $area) {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');
        
        $sheet->setCellValue('A1', 'REPORTE GENERAL DE ASISTENCIA');
        $sheet->setCellValue('A2', 'Período: ' . $fecha_inicio . ' al ' . $fecha_fin);
        $sheet->setCellValue('A3', 'Área: ' . ($area ?? 'TODAS'));
        $sheet->setCellValue('A4', 'Fecha de generación: ' . date('d/m/Y H:i'));

        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
        
        $retardos = $this->retardoModel->getByEmpleado(null, $fecha_inicio, $fecha_fin);
        $retardos = array_filter($retardos, fn($r) => !($r['justificado'] ?? false));
        $faltas = $this->getFaltas($fecha_inicio, $fecha_fin, $area);
        $noValidadas = $this->bitacoraModel->getNoValidadas($fecha_inicio, $fecha_fin);
        $anomalias = $this->anomaliaModel->getNoEvaluadas($fecha_inicio, $fecha_fin);

        $row = 6;
        $sheet->setCellValue('A' . $row, 'INDICADORES CLAVE');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Retardos No Justificados:');
        $sheet->setCellValue('B' . $row, count($retardos));
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Faltas:');
        $sheet->setCellValue('B' . $row, count($faltas));
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Incidencias No Validadas por Jefe:');
        $sheet->setCellValue('B' . $row, count($noValidadas));
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Anomalías Detectadas:');
        $sheet->setCellValue('B' . $row, count($anomalias));

        foreach (['A', 'B'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(35);
        }
    }

    private function generarHojaRetardos($spreadsheet, $fecha_inicio, $fecha_fin, $area) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Retardos');
        
        $retardos = $this->retardoModel->getByEmpleado(null, $fecha_inicio, $fecha_fin);
        $retardos = array_filter($retardos, fn($r) => !($r['justificado'] ?? false));
        
        $headers = ['ID', 'Empleado', 'RFC', 'Área', 'Fecha', 'Hora Entrada', 'Minutos', 'Tipo', 'Justificado'];
        $this->escribirHeaders($sheet, $headers);
        
        $row = 2;
        foreach ($retardos as $r) {
            $sheet->setCellValue('A' . $row, $r['id']);
            $sheet->setCellValue('B' . $row, ($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? ''));
            $sheet->setCellValue('C' . $row, $r['rfc'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $r['area'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $r['fecha']);
            $sheet->setCellValue('F' . $row, $r['hora_entrada'] ?? 'N/A');
            $sheet->setCellValue('G' . $row, $r['minutos_retardo'] ?? 0);
            $sheet->setCellValue('H' . $row, $r['tipo_retraso'] ?? 'N/A');
            $sheet->setCellValue('I' . $row, 'NO');
            $row++;
        }
        
        $this->ajustarAnchoColumnas($sheet, count($headers));
    }

    private function generarHojaFaltas($spreadsheet, $fecha_inicio, $fecha_fin, $area) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Faltas');
        
        $faltas = $this->getFaltas($fecha_inicio, $fecha_fin, $area);
        
        $headers = ['ID', 'Empleado', 'RFC', 'Área', 'Fecha', 'Tipo', 'Justificado'];
        $this->escribirHeaders($sheet, $headers);
        
        $row = 2;
        foreach ($faltas as $f) {
            $sheet->setCellValue('A' . $row, $f['id']);
            $sheet->setCellValue('B' . $row, ($f['nombre'] ?? '') . ' ' . ($f['apellido'] ?? ''));
            $sheet->setCellValue('C' . $row, $f['rfc'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $f['area'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $f['fecha']);
            $sheet->setCellValue('F' . $row, $f['tipo_asistencia'] ?? 'falta');
            $sheet->setCellValue('G' . $row, 'NO');
            $row++;
        }
        
        $this->ajustarAnchoColumnas($sheet, count($headers));
    }

    private function generarHojaNoValidadas($spreadsheet, $fecha_inicio, $fecha_fin, $area) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('No Validadas');
        
        $noValidadas = $this->bitacoraModel->getNoValidadas($fecha_inicio, $fecha_fin);
        
        if ($area) {
            $noValidadas = array_filter($noValidadas, fn($n) => ($n['area'] ?? '') === $area);
        }
        
        $headers = ['ID', 'Empleado', 'Área', 'Fecha', 'Tipo Incidencia', 'Clasificación', 'Minutos/Detalle'];
        $this->escribirHeaders($sheet, $headers);
        
        $row = 2;
        foreach ($noValidadas as $n) {
            $sheet->setCellValue('A' . $row, $n['id']);
            $sheet->setCellValue('B' . $row, ($n['nombre'] ?? '') . ' ' . ($n['apellido'] ?? ''));
            $sheet->setCellValue('C' . $row, $n['area'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $n['fecha']);
            $sheet->setCellValue('E' . $row, $n['tipo_incidencia']);
            $sheet->setCellValue('F' . $row, $n['clasificacion']);
            $sheet->setCellValue('G' . $row, $n['detalle'] ?? '');
            $row++;
        }
        
        $this->ajustarAnchoColumnas($sheet, count($headers));
    }

    private function generarHojaAnomalias($spreadsheet, $fecha_inicio, $fecha_fin) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Anomalías');
        
        $anomalias = $this->anomaliaModel->getNoEvaluadas($fecha_inicio, $fecha_fin);
        
        $headers = ['ID', 'Empleado', 'Área', 'Fecha Deteción', 'Tipo Anomalía', 'Descripción', 'Evaluada'];
        $this->escribirHeaders($sheet, $headers);
        
        $row = 2;
        foreach ($anomalias as $a) {
            $sheet->setCellValue('A' . $row, $a['id']);
            $sheet->setCellValue('B' . $row, ($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? ''));
            $sheet->setCellValue('C' . $row, $a['area'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $a['fecha_deteccion']);
            $sheet->setCellValue('E' . $row, $a['tipo_anomalia']);
            $sheet->setCellValue('F' . $row, $a['descripcion']);
            $sheet->setCellValue('G' . $row, $a['evaluada'] ? 'SÍ' : 'NO');
            $row++;
        }
        
        $this->ajustarAnchoColumnas($sheet, count($headers));
    }

    private function generarHojaKPIs($spreadsheet, $fecha_inicio, $fecha_fin, $area) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('KPIs');
        
        $empleados = $this->empleadoModel->getAll();
        if ($area) {
            $empleados = array_filter($empleados, fn($e) => ($e['area'] ?? '') === $area);
        }
        
        $headers = ['ID', 'Empleado', 'Área', 'Retardos', 'Faltas', 'Total Incidencias', 'Estado'];
        $this->escribirHeaders($sheet, $headers);
        
        $row = 2;
        foreach ($empleados as $emp) {
            $retardos = $this->retardoModel->getByEmpleado($emp['id'], $fecha_inicio, $fecha_fin);
            $retardosNoJustif = array_filter($retardos, fn($r) => !($r['justificado'] ?? false));
            $faltas = $this->getFaltasEmpleado($emp['id'], $fecha_inicio, $fecha_fin);
            
            $total = count($retardosNoJustif) + count($faltas);
            $estado = $total === 0 ? 'OK' : ($total <= 2 ? 'ALERTA' : 'CRÍTICO');
            
            $sheet->setCellValue('A' . $row, $emp['id']);
            $sheet->setCellValue('B' . $row, $emp['nombre'] . ' ' . $emp['apellido']);
            $sheet->setCellValue('C' . $row, $emp['area'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, count($retardosNoJustif));
            $sheet->setCellValue('E' . $row, count($faltas));
            $sheet->setCellValue('F' . $row, $total);
            $sheet->setCellValue('G' . $row, $estado);
            $row++;
        }
        
        $this->ajustarAnchoColumnas($sheet, count($headers));
    }

    private function escribirHeaders($sheet, $headers) {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        $sheet->getStyle('A1:' . $col . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCE5FF');
    }

    private function ajustarAnchoColumnas($sheet, $cols) {
        for ($i = 0; $i < $cols; $i++) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth(20);
        }
    }

    private function getFaltas($fecha_inicio, $fecha_fin, $area = null) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT a.*, e.nombre, e.apellido, e.rfc, e.area
                FROM asistencia a
                JOIN empleados e ON a.empleado_id = e.id
                WHERE a.fecha BETWEEN ? AND ? AND a.tipo_asistencia = 'falta'";
        $params = [$fecha_inicio, $fecha_fin];

        if ($area) {
            $sql .= " AND e.area = ?";
            $params[] = $area;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getFaltasEmpleado($empleado_id, $fecha_inicio, $fecha_fin) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM asistencia 
            WHERE empleado_id = ? AND fecha BETWEEN ? AND ? AND tipo_asistencia = 'falta'
        ");
        $stmt->execute([$empleado_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function guardarArchivo($spreadsheet, $nombre) {
        $writer = new Xlsx($spreadsheet);
        $ruta = __DIR__ . '/../uploads/' . $nombre . '.xlsx';
        
        if (!is_dir(__DIR__ . '/../uploads')) {
            mkdir(__DIR__ . '/../uploads', 0755, true);
        }
        
        $writer->save($ruta);
        return $ruta;
    }
}
