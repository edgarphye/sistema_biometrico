<?php
require_once 'models/Asistencia.php';
require_once 'models/Empleado.php';
require_once 'models/Retardo.php';
require_once 'models/Biometrico.php';
require_once 'models/HorarioLaboral.php';
require_once 'models/Comision.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class AsistenciaController {
    private $asistenciaModel;
    private $empleadoModel;
    private $retardoModel;
    private $biometricoModel;
    private $comisionModel;
    private $horarioModel;

    public function __construct() {
        $this->asistenciaModel = new Asistencia();
        $this->empleadoModel = new Empleado();
        $this->retardoModel = new Retardo();
        $this->biometricoModel = new Biometrico();
        $this->horarioModel = new HorarioLaboral();
        $this->comisionModel = new Comision();
    }

    public function index() {
        $asistencias = $this->asistenciaModel->getByEmpleado($_GET['empleado_id'] ?? null);

        // Obtener �reas �nicas para el filtro
        $stmt = $this->asistenciaModel->getConnection()->prepare("SELECT DISTINCT area FROM empleados WHERE activo = 1 ORDER BY area");
        $stmt->execute();
        $areas_result = $stmt->fetchAll();
        $areas = array_column($areas_result, 'area');

        include 'views/asistencia/index.php';
    }

    public function registrarEntrada() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dispositivo_id = isset($_POST['dispositivo_id']) ? (int)$_POST['dispositivo_id'] : 1;

            // Medir tiempo de procesamiento
            $inicio_procesamiento = microtime(true);

            try {
                // Simular recepci�n de datos biom�tricos
                $datos_biometricos = $this->biometricoModel->recibirDatosBiometricos($dispositivo_id);

                // Verificar identidad
                $empleado = $this->biometricoModel->verificarIdentidad($datos_biometricos['data'], $datos_biometricos['type']);

                if ($empleado) {
                    // Calcular tiempo de procesamiento
                    $tiempo_procesamiento = round(microtime(true) - $inicio_procesamiento, 3);

                    // Registrar entrada con datos biom�tricos detallados
                    $this->asistenciaModel->registrarEntrada(
                        $empleado['id'],
                        $dispositivo_id,
                        $datos_biometricos['type'], // tipo_biometria
                        $datos_biometricos['data'], // datos_biometricos (JSON)
                        $datos_biometricos['quality_score'], // calidad_verificacion
                        $datos_biometricos['metadata'], // metadata_dispositivo
                        $tiempo_procesamiento
                    );

                    // Calcular retardo si aplica
                    $fecha_entrada = date('Y-m-d', strtotime($datos_biometricos['timestamp']));
                    $hora_entrada = date('H:i:s', strtotime($datos_biometricos['timestamp']));
                    $retardo = $this->asistenciaModel->calcularRetardo($hora_entrada, $empleado['id'], $fecha_entrada);

                    if ($retardo['tipo'] !== 'sin_retardo') {
                        $this->retardoModel->registrarRetardo(
                            $empleado['id'],
                            $fecha_entrada,
                            $retardo['minutos'],
                            $retardo['tipo']
                        );

                        // Aplicar reglas AEFCM despu�s de registrar el retardo
                        $mes = date('m', strtotime($fecha_entrada));
                        $anio = date('Y', strtotime($fecha_entrada));
                        $this->asistenciaModel->aplicarReglasAEFCM($empleado['id'], $mes, $anio);
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => 'Entrada registrada exitosamente',
                        'empleado' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                        'tipo_verificacion' => $datos_biometricos['type'],
                        'calidad' => $datos_biometricos['quality_score'] . '%',
                        'tiempo_procesamiento' => $tiempo_procesamiento . 's'
                    ]);
                } else {
                    // Log de verificaci�n fallida
                    $this->biometricoModel->logVerificationFailure($dispositivo_id, $datos_biometricos);

                    echo json_encode([
                        'success' => false,
                        'message' => 'Empleado no identificado',
                        'tipo_verificacion' => $datos_biometricos['type']
                    ]);
                }
            } catch (Exception $e) {
                // Log de error del dispositivo
                $this->biometricoModel->logDeviceError($dispositivo_id, 'registro_entrada', $e->getMessage());

                echo json_encode([
                    'success' => false,
                    'message' => 'Error en el procesamiento biom�trico: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function registrarSalida() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dispositivo_id = isset($_POST['dispositivo_id']) ? (int)$_POST['dispositivo_id'] : 1;

            // Medir tiempo de procesamiento
            $inicio_procesamiento = microtime(true);

            try {
                // Simular recepci�n de datos biom�tricos
                $datos_biometricos = $this->biometricoModel->recibirDatosBiometricos($dispositivo_id);

                // Verificar identidad
                $empleado = $this->biometricoModel->verificarIdentidad($datos_biometricos['data'], $datos_biometricos['type']);

                if ($empleado) {
                    // Calcular tiempo de procesamiento
                    $tiempo_procesamiento = round(microtime(true) - $inicio_procesamiento, 3);

                    // Registrar salida con datos biom�tricos detallados
                    $this->asistenciaModel->registrarSalida(
                        $empleado['id'],
                        $dispositivo_id,
                        $datos_biometricos['type'], // tipo_biometria
                        $datos_biometricos['data'], // datos_biometricos (JSON)
                        $datos_biometricos['quality_score'], // calidad_verificacion
                        $datos_biometricos['metadata'], // metadata_dispositivo
                        $tiempo_procesamiento
                    );

                    echo json_encode([
                        'success' => true,
                        'message' => 'Salida registrada exitosamente',
                        'empleado' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                        'tipo_verificacion' => $datos_biometricos['type'],
                        'calidad' => $datos_biometricos['quality_score'] . '%',
                        'tiempo_procesamiento' => $tiempo_procesamiento . 's'
                    ]);
                } else {
                    // Log de verificaci�n fallida
                    $this->biometricoModel->logVerificationFailure($dispositivo_id, $datos_biometricos);

                    echo json_encode([
                        'success' => false,
                        'message' => 'Empleado no identificado',
                        'tipo_verificacion' => $datos_biometricos['type']
                    ]);
                }
            } catch (Exception $e) {
                // Log de error del dispositivo
                $this->biometricoModel->logDeviceError($dispositivo_id, 'registro_salida', $e->getMessage());

                echo json_encode([
                    'success' => false,
                    'message' => 'Error en el procesamiento biom�trico: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function getEstadoDispositivos() {
        header('Content-Type: application/json');
        $dispositivos = $this->biometricoModel->getEstadoDispositivos();
        echo json_encode($dispositivos);
    }

    public function filtrarAsistencia() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [];

            // Filtros b�sicos
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;

            // Filtros avanzados
            if (!empty($_POST['dispositivo_id'])) {
                $filtros['dispositivo_id'] = (int)$_POST['dispositivo_id'];
            }
            if (!empty($_POST['tipo_biometria'])) {
                $filtros['tipo_biometria'] = $_POST['tipo_biometria'];
            }
            if (!empty($_POST['tipo_asistencia'])) {
                $filtros['tipo_asistencia'] = $_POST['tipo_asistencia'];
            }
            if (!empty($_POST['empleado_id'])) {
                $filtros['empleado_id'] = (int)$_POST['empleado_id'];
            }

            // Limitar resultados para mejor rendimiento
            $filtros['limit'] = 500;

            // Obtener registros de asistencia filtrados
            $registros = $this->asistenciaModel->getAsistenciaFiltrada($filtros);

            // Si hay filtro de �rea, filtrar adicionalmente
            if ($area) {
                $registros = array_filter($registros, function($registro) use ($area) {
                    return $registro['area'] === $area;
                });
            }

            // Formatear respuesta para la vista
            $resultado = [];
            foreach ($registros as $registro) {
                $resultado[] = [
                    'id' => $registro['id'],
                    'empleado' => $registro['nombre'] . ' ' . $registro['apellido'],
                    'area' => $registro['area'] ?? 'Sin �rea',
                    'fecha' => date('d/m/Y', strtotime($registro['timestamp'])),
                    'hora' => date('H:i:s', strtotime($registro['timestamp'])),
                    'tipo' => $registro['tipo'],
                    'dispositivo_id' => $registro['dispositivo_id'],
                    'tipo_biometria' => $registro['tipo_biometria'] ?? 'N/A',
                    'calidad_verificacion' => $registro['calidad_verificacion'] ? $registro['calidad_verificacion'] . '%' : 'N/A',
                    'tiempo_procesamiento' => $registro['tiempo_procesamiento'] ? round($registro['tiempo_procesamiento'], 3) . 's' : 'N/A'
                ];
            }

            echo json_encode($resultado);
        }
    }

    public function calcularHorasLaborables() {
        try {
            $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;

            // Obtener empleados con asistencia en el per�odo
            $empleados_asistencia = $this->asistenciaModel->getEmpleadosConAsistencia($fecha_inicio, $fecha_fin, $area);

            $resultado = [];

            // Procesar cada empleado
            foreach ($empleados_asistencia as $empleado_data) {
                $empleado_id = $empleado_data['id'];
                $fecha = $empleado_data['fecha'];

                // Obtener horario del empleado para esa fecha
                $horario = $this->horarioModel->getHorarioPorFecha($empleado_id, $fecha);

                // Obtener retardos del empleado en el per�odo
                $retardos = $this->retardoModel->getByEmpleado($empleado_id, $fecha_inicio, $fecha_fin);

                // Calcular horas laborables
                $horas_calculadas = $this->calcularHorasPorEmpleado($empleado_data, $horario, $retardos, $fecha);

                $resultado[] = [
                    'id' => $empleado_id,
                    'empleado' => $empleado_data['nombre'] . ' ' . $empleado_data['apellido'],
                    'area' => $empleado_data['area'],
                    'fecha' => date('d/m/Y', strtotime($fecha)),
                    'hora_entrada' => $horas_calculadas['hora_entrada'],
                    'hora_salida' => $horas_calculadas['hora_salida'],
                    'hora_oficial_entrada' => $horas_calculadas['hora_oficial_entrada'],
                    'hora_oficial_salida' => $horas_calculadas['hora_oficial_salida'],
                    'horas_laborables' => $horas_calculadas['horas_laborables'],
                    'horas_trabajadas' => $horas_calculadas['horas_trabajadas'],
                    'retardo_minutos' => $horas_calculadas['retardo_minutos'],
                    'tipo_retardo' => $horas_calculadas['tipo_retardo'],
                    'tiempo_extra_minutos' => $horas_calculadas['tiempo_extra_minutos'],
                    'retardos_mes' => $horas_calculadas['retardos_mes'],
                    'justificaciones_pendientes' => $horas_calculadas['justificaciones_pendientes'],
                    'comentarios' => $horas_calculadas['comentarios'],
                    'estado' => $horas_calculadas['estado']
                ];
            }

            // Set JSON header before any output
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode($resultado);
        } catch (Exception $e) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['error' => 'Error al calcular horas laborables: ' . $e->getMessage()]);
        }
        exit;
    }

    private function calcularHorasPorEmpleado($empleado_data, $horario, $retardos, $fecha) {
        $hora_entrada = $empleado_data['hora_entrada'];
        $hora_salida = $empleado_data['hora_salida'];

        // Horarios oficiales
        $hora_oficial_entrada = $horario ? $horario['hora_entrada'] : '09:00:00';
        $hora_oficial_salida = $horario ? $horario['hora_salida'] : '18:00:00';

        // Calcular horas laborables oficiales
        $timestamp_entrada_oficial = strtotime($hora_oficial_entrada);
        $timestamp_salida_oficial = strtotime($hora_oficial_salida);
        $horas_laborables_segundos = $timestamp_salida_oficial - $timestamp_entrada_oficial;
        $horas_laborables = round($horas_laborables_segundos / 3600, 2);

        // Calcular horas trabajadas
        $horas_trabajadas = 0;
        if ($hora_entrada && $hora_salida) {
            $timestamp_entrada_real = strtotime($hora_entrada);
            $timestamp_salida_real = strtotime($hora_salida);
            $horas_trabajadas_segundos = $timestamp_salida_real - $timestamp_entrada_real;
            $horas_trabajadas = round($horas_trabajadas_segundos / 3600, 2);
        }

        // Calcular retardo
        $retardo_minutos = 0;
        $tipo_retardo = 'sin_retardo';
        if ($hora_entrada) {
            $retardo_data = $this->asistenciaModel->calcularRetardo($hora_entrada, $empleado_data['id'], $fecha);
            $retardo_minutos = $retardo_data['minutos'];
            $tipo_retardo = $retardo_data['tipo'];
        }

        // Calcular tiempo extra
        $tiempo_extra_minutos = 0;
        if ($hora_salida && $hora_oficial_salida) {
            $timestamp_salida_real = strtotime($hora_salida);
            $timestamp_salida_oficial = strtotime($hora_oficial_salida);
            $diferencia_segundos = $timestamp_salida_real - $timestamp_salida_oficial;
            $tiempo_extra_minutos = round($diferencia_segundos / 60);
            if ($tiempo_extra_minutos < 0) $tiempo_extra_minutos = 0; // No negativo
        }

        // Estad�sticas de retardos del mes
        $mes_actual = date('m', strtotime($fecha));
        $anio_actual = date('Y', strtotime($fecha));
        $retardos_mes = $this->retardoModel->getTotalRetardos($empleado_data['id'], $mes_actual, $anio_actual);

        // Justificaciones pendientes
        $justificaciones_pendientes = $this->retardoModel->getPendientesJustificacion($empleado_data['id']);

        // Generar comentarios y estado
        $comentarios = [];
        $estado = 'normal';

        // Verificar si cumpli� con el horario
        if ($hora_entrada && $hora_salida) {
            if ($horas_trabajadas >= $horas_laborables) {
                $comentarios[] = 'Cumpli� con las horas laborables';
            } else {
                $comentarios[] = 'No cumpli� con las horas laborables completas';
                $estado = 'incompleto';
            }
        } else {
            $comentarios[] = 'Registro incompleto (falta entrada o salida)';
            $estado = 'incompleto';
        }

        // Comentarios sobre retardos
        if ($tipo_retardo !== 'sin_retardo') {
            $comentarios[] = "Tiene retardo de tipo '$tipo_retardo' ($retardo_minutos min)";
            if ($tipo_retardo === 'mayor') {
                $estado = 'retardo_mayor';
            } elseif ($tipo_retardo === 'menor') {
                $estado = 'retardo_menor';
            }
        }

        // Comentarios sobre retardos del mes
        $total_retardos_mes = 0;
        foreach ($retardos_mes as $retardo_mes) {
            $total_retardos_mes += $retardo_mes['total'];
        }
        if ($total_retardos_mes > 0) {
            $comentarios[] = "Tiene $total_retardos_mes retardos este mes";
        }

        // Comentarios sobre justificaciones
        $num_justificaciones_pendientes = count($justificaciones_pendientes);
        if ($num_justificaciones_pendientes > 0) {
            $comentarios[] = "Tiene $num_justificaciones_pendientes justificaciones pendientes";
            $estado = 'pendiente_justificacion';
        }

        // Verificar si alg�n retardo est� justificado
        $retardos_justificados = array_filter($retardos, function($r) {
            return $r['justificado'] == 1;
        });
        if (count($retardos_justificados) > 0) {
            $comentarios[] = 'Tiene retardos justificados';
        }

        // Tiempo extra
        if ($tiempo_extra_minutos > 0) {
            $comentarios[] = "Trabaj� $tiempo_extra_minutos minutos extra";
        }

        return [
            'hora_entrada' => $hora_entrada ?: 'N/A',
            'hora_salida' => $hora_salida ?: 'N/A',
            'hora_oficial_entrada' => $hora_oficial_entrada,
            'hora_oficial_salida' => $hora_oficial_salida,
            'horas_laborables' => $horas_laborables,
            'horas_trabajadas' => $horas_trabajadas,
            'retardo_minutos' => $retardo_minutos,
            'tipo_retardo' => $tipo_retardo,
            'tiempo_extra_minutos' => $tiempo_extra_minutos,
            'retardos_mes' => $retardos_mes,
            'justificaciones_pendientes' => $num_justificaciones_pendientes,
            'comentarios' => implode('; ', $comentarios),
            'estado' => $estado
        ];
    }

    public function filtrarEmpleados() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;

            // Obtener empleados con asistencia en el per�odo
            $empleados = $this->asistenciaModel->getEmpleadosConAsistencia($fecha_inicio, $fecha_fin, $area);

            // Para cada empleado, obtener informaci�n adicional
            $resultado = [];
            foreach ($empleados as $empleado) {
                $retardos = $this->retardoModel->getByEmpleado($empleado['id'], $fecha_inicio, $fecha_fin);
                $comisiones = $this->comisionModel->getByEmpleado($empleado['id']);
                $justificaciones = $this->retardoModel->getPendientesJustificacion($empleado['id']);

                // Filtrar comisiones activas
                $comisiones_activas = array_filter($comisiones, function($comision) {
                    return strtotime($comision['fecha_vencimiento']) >= time() || $comision['fecha_vencimiento'] === null;
                });

                $resultado[] = [
                    'id' => $empleado['id'],
                    'nombre' => $empleado['nombre'],
                    'apellido' => $empleado['apellido'],
                    'area' => $empleado['area'],
                    'fecha' => $empleado['fecha'],
                    'hora_entrada' => $empleado['hora_entrada'],
                    'hora_salida' => $empleado['hora_salida'],
                    'retardos' => $retardos,
                    'comisiones' => array_values($comisiones_activas),
                    'justificaciones' => $justificaciones
                ];
            }

            echo json_encode($resultado);
        }
    }

    public function exportarExcel() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [];

            // Filtros b�sicos
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;

            // Filtros avanzados
            if (!empty($_POST['dispositivo_id'])) {
                $filtros['dispositivo_id'] = (int)$_POST['dispositivo_id'];
            }
            if (!empty($_POST['tipo_biometria'])) {
                $filtros['tipo_biometria'] = $_POST['tipo_biometria'];
            }
            if (!empty($_POST['tipo_asistencia'])) {
                $filtros['tipo_asistencia'] = $_POST['tipo_asistencia'];
            }
            if (!empty($_POST['empleado_id'])) {
                $filtros['empleado_id'] = (int)$_POST['empleado_id'];
            }

            // Sin l�mite para exportaci�n completa
            $filtros['limit'] = null;

            // Obtener registros de asistencia filtrados
            $registros = $this->asistenciaModel->getAsistenciaFiltrada($filtros);

            // Si hay filtro de �rea, filtrar adicionalmente
            if ($area) {
                $registros = array_filter($registros, function($registro) use ($area) {
                    return $registro['area'] === $area;
                });
            }

            // Crear nuevo documento Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // T�tulo
            $sheet->setCellValue('A1', 'Sistema Biom�trico - Reporte de Asistencia');
            $sheet->mergeCells('A1:J1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Filtros aplicados
            $sheet->setCellValue('A3', 'Filtros Aplicados:');
            $sheet->getStyle('A3')->getFont()->setBold(true);
            $sheet->setCellValue('B3', 'Fecha Inicio: ' . $filtros['fecha_inicio']);
            $sheet->setCellValue('C3', 'Fecha Fin: ' . $filtros['fecha_fin']);
            if ($area) {
                $sheet->setCellValue('D3', '�rea: ' . $area);
            }
            if (isset($filtros['dispositivo_id'])) {
                $sheet->setCellValue('E3', 'Dispositivo: ' . $filtros['dispositivo_id']);
            }
            if (isset($filtros['tipo_biometria'])) {
                $sheet->setCellValue('F3', 'Biometr�a: ' . $filtros['tipo_biometria']);
            }
            if (isset($filtros['tipo_asistencia'])) {
                $sheet->setCellValue('G3', 'Tipo: ' . $filtros['tipo_asistencia']);
            }

            // Encabezados de la tabla
            $headers = ['ID', 'Empleado', '�rea', 'Fecha', 'Hora', 'Tipo', 'Dispositivo', 'Biometr�a', 'Calidad', 'Tiempo Proc.'];
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
                $sheet->setCellValue('C' . $row, $registro['area'] ?? 'Sin �rea');
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

            // Enviar archivo al navegador
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            readfile($filepath);
            unlink($filepath); // Eliminar archivo temporal
            exit;
        }
    }

    public function exportarPDF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [];

            // Filtros b�sicos
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? date('Y-m-d');
            $area = $_POST['area'] ?? null;

            // Filtros avanzados
            if (!empty($_POST['dispositivo_id'])) {
                $filtros['dispositivo_id'] = (int)$_POST['dispositivo_id'];
            }
            if (!empty($_POST['tipo_biometria'])) {
                $filtros['tipo_biometria'] = $_POST['tipo_biometria'];
            }
            if (!empty($_POST['tipo_asistencia'])) {
                $filtros['tipo_asistencia'] = $_POST['tipo_asistencia'];
            }
            if (!empty($_POST['empleado_id'])) {
                $filtros['empleado_id'] = (int)$_POST['empleado_id'];
            }

            // Sin l�mite para exportaci�n completa
            $filtros['limit'] = null;

            // Obtener registros de asistencia filtrados
            $registros = $this->asistenciaModel->getAsistenciaFiltrada($filtros);

            // Si hay filtro de �rea, filtrar adicionalmente
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

            // Enviar PDF al navegador
            $filename = 'reporte_asistencia_' . date('Y-m-d_H-i-s') . '.pdf';
            $dompdf->stream($filename, array('Attachment' => true));
            exit;
        }
    }

    private function generarHTMLReportePDF($registros, $filtros, $area) {
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Asistencia - Sistema Biom�trico</title>
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
                <h1>Sistema Biom�trico de Control de Asistencia</h1>
                <p>Reporte de Asistencia</p>
                <p>Generado el ' . date('d/m/Y H:i:s') . '</p>
            </div>

            <div class="filters">
                <h3>Filtros Aplicados:</h3>
                <p><strong>Fecha Inicio:</strong> ' . date('d/m/Y', strtotime($filtros['fecha_inicio'])) . '</p>
                <p><strong>Fecha Fin:</strong> ' . date('d/m/Y', strtotime($filtros['fecha_fin'])) . '</p>';

        if ($area) {
            $html .= '<p><strong>�rea:</strong> ' . htmlspecialchars($area) . '</p>';
        }
        if (isset($filtros['dispositivo_id'])) {
            $html .= '<p><strong>Dispositivo:</strong> ' . $filtros['dispositivo_id'] . '</p>';
        }
        if (isset($filtros['tipo_biometria'])) {
            $html .= '<p><strong>Biometr�a:</strong> ' . ucfirst($filtros['tipo_biometria']) . '</p>';
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
                        <th>�rea</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Dispositivo</th>
                        <th>Biometr�a</th>
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
                        <td>' . htmlspecialchars($registro['area'] ?? 'Sin �rea') . '</td>
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
                <p>Reporte generado autom�ticamente por el Sistema Biom�trico</p>
                <p>� ' . date('Y') . ' - Todos los derechos reservados</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}
?>
