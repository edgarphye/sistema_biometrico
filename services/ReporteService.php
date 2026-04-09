<?php
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReporteService {
    private $asistenciaModel;

    public function __construct() {
        $this->asistenciaModel = new Asistencia();
    }

    public function exportarExcel($filtros, $area = null) {
        // Obtener registros de asistencia filtrados
        $registros = $this->asistenciaModel->getAsistenciaFiltrada($filtros);

        // Si hay filtro de área, filtrar adicionalmente
        if ($area) {
            $registros = array_filter($registros, function($registro) use ($area) {
                return $registro['area'] === $area;
            });
        }

        // Crear nuevo documento Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Título
        $sheet->setCellValue('A1', 'Sistema Biométrico - Reporte de Asistencia');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Filtros aplicados
        $sheet->setCellValue('A3', 'Filtros Aplicados:');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->setCellValue('B3', 'Fecha Inicio: ' . $filtros['fecha_inicio']);
        $sheet->setCellValue('C3', 'Fecha Fin: ' . $filtros['fecha_fin']);
        if ($area) {
            $sheet->setCellValue('D3', 'Área: ' . $area);
        }
        if (isset($filtros['dispositivo_id'])) {
            $sheet->setCellValue('E3', 'Dispositivo: ' . $filtros['dispositivo_id']);
        }
        if (isset($filtros['tipo_biometria'])) {
            $sheet->setCellValue('F3', 'Biometría: ' . $filtros['tipo_biometria']);
        }
        if (isset($filtros['tipo_asistencia'])) {
            $sheet->setCellValue('G3', 'Tipo: ' . $filtros['tipo_asistencia']);
        }

        // Encabezados de la tabla
        $headers = ['ID', 'Empleado', 'Área', 'Fecha', 'Hora', 'Tipo', 'Dispositivo', 'Biometría', 'Calidad', 'Tiempo Proc.'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true);
            $sheet->getStyle($col . '5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
            $col++;
        }

        // Datos
        $row = 6;
        foreach ($registros as $registro) {
            $sheet->setCellValue('A' . $row, $registro['id']);
            $sheet->setCellValue('B' . $row, $registro['nombre'] . ' ' . $registro['apellido']);
            $sheet->setCellValue('C' . $row, $registro['area'] ?? 'Sin Área');
            $sheet->setCellValue('D' . $row, date('d/m/Y', strtotime($registro['timestamp'])));
            $sheet->setCellValue('E' . $row, date('H:i:s', strtotime($registro['timestamp'])));
            $sheet->setCellValue('F' . $row, ucfirst($registro['tipo']));
            $sheet->setCellValue('G' . $row, 'Dispositivo ' . $registro['dispositivo_id']);
            $sheet->setCellValue('H' . $row, $registro['tipo_biometria'] ?? 'N/A');
            $sheet->setCellValue('I' . $row, $registro['calidad_verificacion'] ? $registro['calidad_verificacion'] . '%' : 'N/A');
            $sheet->setCellValue('J' . $row, $registro['tiempo_procesamiento'] ? round($registro['tiempo_procesamiento'], 3) . 's' : 'N/A');
            $row++;
        }

        // Autoajustar columnas
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Crear archivo temporal
        $filename = 'reporte_asistencia_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = sys_get_temp_dir() . '/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    public function exportarPDF($filtros, $area = null) {
        // Obtener registros de asistencia filtrados
        $registros = $this->asistenciaModel->getAsistenciaFiltrada($filtros);

        // Si hay filtro de área, filtrar adicionalmente
        if ($area) {
            $registros = array_filter($registros, function($registro) use ($area) {
                return $registro['area'] === $area;
            });
        }

        // Configurar Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        // Generar HTML para el PDF
        $html = $this->generarHTMLReportePDF($registros, $filtros, $area);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    private function generarHTMLReportePDF($registros, $filtros, $area) {
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Asistencia - Sistema Biométrico</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .header h1 { color: #333; margin: 0; }
                .header p { color: #666; margin: 5px 0; }
                .filters { margin-bottom: 20px; background: #f8f9fa; padding: 10px; border-radius: 5px; }
                .filters p { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9fa; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                .badge { padding: 2px 6px; border-radius: 3px; font-size: 11px; }
                .badge-success { background: #28a745; color: white; }
                .badge-warning { background: #ffc107; color: black; }
                .badge-primary { background: #007bff; color: white; }
                .badge-info { background: #17a2b8; color: white; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Sistema Biométrico de Control de Asistencia</h1>
                <p>Reporte de Asistencia</p>
                <p>Generado el ' . date('d/m/Y H:i:s') . '</p>
            </div>

            <div class="filters">
                <h3>Filtros Aplicados:</h3>
                <p><strong>Fecha Inicio:</strong> ' . date('d/m/Y', strtotime($filtros['fecha_inicio'])) . '</p>
                <p><strong>Fecha Fin:</strong> ' . date('d/m/Y', strtotime($filtros['fecha_fin'])) . '</p>';

        if ($area) {
            $html .= '<p><strong>Área:</strong> ' . htmlspecialchars($area) . '</p>';
        }
        if (isset($filtros['dispositivo_id'])) {
            $html .= '<p><strong>Dispositivo:</strong> ' . $filtros['dispositivo_id'] . '</p>';
        }
        if (isset($filtros['tipo_biometria'])) {
            $html .= '<p><strong>Biometría:</strong> ' . ucfirst($filtros['tipo_biometria']) . '</p>';
        }
        if (isset($filtros['tipo_asistencia'])) {
            $html .= '<p><strong>Tipo:</strong> ' . ucfirst($filtros['tipo_asistencia']) . '</p>';
        }

        $html .= '<p><strong>Total de Registros:</strong> ' . count($registros) . '</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Área</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Dispositivo</th>
                        <th>Biometría</th>
                        <th>Calidad</th>
                        <th>Tiempo Proc.</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($registros as $registro) {
            $tipoClass = $registro['tipo'] === 'entrada' ? 'badge-success' : 'badge-warning';
            $biometriaClass = $registro['tipo_biometria'] === 'huella' ? 'badge-primary' : 'badge-info';

            $html .= '
                    <tr>
                        <td>' . $registro['id'] . '</td>
                        <td>' . htmlspecialchars($registro['nombre'] . ' ' . $registro['apellido']) . '</td>
                        <td>' . htmlspecialchars($registro['area'] ?? 'Sin Área') . '</td>
                        <td>' . date('d/m/Y', strtotime($registro['timestamp'])) . '</td>
                        <td>' . date('H:i:s', strtotime($registro['timestamp'])) . '</td>
                        <td><span class="badge ' . $tipoClass . '">' . ucfirst($registro['tipo']) . '</span></td>
                        <td>Dispositivo ' . $registro['dispositivo_id'] . '</td>
                        <td><span class="badge ' . $biometriaClass . '">' . ucfirst($registro['tipo_biometria'] ?? 'N/A') . '</span></td>
                        <td>' . ($registro['calidad_verificacion'] ? $registro['calidad_verificacion'] . '%' : 'N/A') . '</td>
                        <td>' . ($registro['tiempo_procesamiento'] ? round($registro['tiempo_procesamiento'], 3) . 's' : 'N/A') . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <div class="footer">
                <p>Reporte generado automáticamente por el Sistema Biométrico</p>
                <p>© ' . date('Y') . ' - Todos los derechos reservados</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}
