<?php
error_reporting(E_ALL);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);

require_once 'config.php';
require_once 'models/Database.php';

class ImportadorExcel {
    private $db;
    private $conn;
    private $empleadosMap = [];
    private $empleadosCreadosCount = 0;
    private $empleadosExistentesCount = 0;
    private $errores = [];
    private $logFile;
    private $horarioEstandar = '09:00:00';

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->logFile = '/tmp/import_log_' . date('YmdHis') . '.txt';
        $this->loadEmpleadosMap();
    }

    public function __destruct() {
        if (!empty($this->errores)) {
            file_put_contents($this->logFile, implode("\n", $this->errores));
            echo "\nLog de errores guardado en: $this->logFile\n";
        }
    }

    private function logError($mensaje) {
        $this->errores[] = date('Y-m-d H:i:s') . ' - ' . $mensaje;
    }

    private function loadEmpleadosMap() {
        $stmt = $this->conn->query("SELECT id, curp FROM empleados WHERE TRIM(curp) != '' AND curp IS NOT NULL");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $curp = trim($row['curp']);
            if ($curp) {
                $this->empleadosMap[$curp] = $row['id'];
            }
        }
        
        $stmt = $this->conn->query("SELECT id FROM empleados");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->empleadosMap['ID_' . $row['id']] = $row['id'];
        }
        
        $this->empleadosExistentesCount = count($this->empleadosMap);
        echo "Cargados " . $this->empleadosExistentesCount . " empleados en mapa\n";
    }

    private function getOrCreateEmpleado($curp, $nombre, $acNo = null) {
        $curpValid = false;
        
        if ($curp) {
            $curp = strtoupper(trim($curp));
            if (strlen($curp) > 18) {
                $curp = substr($curp, 0, 18);
            }
            
            if (!empty($curp) && strlen($curp) >= 18 && preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[A-Z0-9]{3}[0-9A-Z]$/', $curp)) {
                $curpValid = true;
                if (isset($this->empleadosMap[$curp])) {
                    return $this->empleadosMap[$curp];
                }
            }
        }
        
        if ($acNo && $acNo > 0) {
            if (isset($this->empleadosMap['ID_' . $acNo])) {
                return $this->empleadosMap['ID_' . $acNo];
            }
            
            $partes = explode(' ', trim($nombre));
            $apellido = '';
            $nombreReal = $nombre;
            if (count($partes) >= 2) {
                $apellido = $partes[0] . ' ' . $partes[1];
                $nombreReal = implode(' ', array_slice($partes, 2));
            }

            $rfc = $curpValid ? strtoupper(substr($curp, 0, 10)) : 'XAXX' . str_pad($acNo, 6, '0', STR_PAD_LEFT);
            
            try {
                $stmt = $this->conn->prepare("
                    INSERT INTO empleados (id, nombre, apellido, rfc, curp, area, jerarquia, activo, fecha_ingreso)
                    VALUES (?, ?, ?, ?, ?, 'DGIFA', 'empleado', 1, CURDATE())
                ");
                $stmt->execute([$acNo, $nombreReal, $apellido, $rfc, $curpValid ? $curp : null]);
                $empleadoId = $acNo;
                
                if ($curpValid) {
                    $this->empleadosMap[$curp] = $empleadoId;
                }
                $this->empleadosMap['ID_' . $acNo] = $empleadoId;
                
                $this->empleadosCreadosCount++;
                if ($this->empleadosCreadosCount <= 5) {
                    echo "Creado: $nombre (ID: $acNo)\n";
                }
                return $empleadoId;
            } catch (Exception $e) {
                $this->logError("Error creando empleado $acNo: " . $e->getMessage());
                return null;
            }
        }

        return null;
    }

    private function parseTime($timeStr) {
        if (empty($timeStr) || $timeStr === 'NaN' || $timeStr === 'nan' || $timeStr === '') {
            return null;
        }
        $timeStr = trim($timeStr);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $timeStr, $m)) {
            $hora = (int)$m[1];
            $min = (int)$m[2];
            if ($hora >= 0 && $hora <= 23 && $min >= 0 && $min <= 59) {
                return sprintf("%02d:%02d:00", $hora, $min);
            }
        }
        return null;
    }

    private function parseFecha($fechaStr) {
        if (empty($fechaStr)) return null;
        $fechaStr = trim($fechaStr);
        
        // Try DD/MM/YYYY format first
        $parts = explode('/', $fechaStr);
        if (count($parts) === 3) {
            $dia = (int)$parts[0];
            $mes = (int)$parts[1];
            $anio = (int)$parts[2];
            // Validate date components before using them
            if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12 && $anio >= 2000 && $anio <= 2100) {
                return sprintf("%04d-%02d-%02d", $anio, $mes, $dia);
            }
        }
        
        // Try YYYY-MM-DD format
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $fechaStr, $m)) {
            $anio = (int)$m[1];
            $mes = (int)$m[2];
            $dia = (int)$m[3];
            if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12 && $anio >= 2000 && $anio <= 2100) {
                return sprintf("%04d-%02d-%02d", $anio, $mes, $dia);
            }
        }
        
        return null;
    }

    private function calcularMinutosRetardo($horaEntrada) {
        if (!$horaEntrada) return 0;
        $entrada = strtotime('2025-01-01 ' . $horaEntrada);
        $esperado = strtotime('2025-01-01 ' . $this->horarioEstandar);
        $diff = ($entrada - $esperado) / 60;
        return (int)$diff;
    }

    private function classifyByTime($horaEntrada, $horaSalida) {
        if (!$horaEntrada && !$horaSalida) {
            return 'falta';
        }
        if ($horaEntrada && $horaSalida) {
            $minutos = $this->calcularMinutosRetardo($horaEntrada);
            if ($minutos <= 9) {
                return 'normal';
            } elseif ($minutos <= 30) {
                return 'con_retardo';
            } else {
                return 'falta';
            }
        }
        if ($horaEntrada && !$horaSalida) {
            return 'comision_salida';
        }
        return 'comision_entrada';
    }

private function mapTipoAsistencia($tipoExcel) {
        $tipo = strtolower(trim($tipoExcel));
        
        $mapeoDirecto = [
            'vacaciones' => 'vacaciones',
            'comision_todo_dia' => 'comision_todo_dia',
            'dia_economico' => 'dia_economico',
            'licencia_medica' => 'licencia_medica',
            'constancia_tiempo' => 'constancia_tiempo',
            'cuidados_maternos' => 'cuidados_maternos',
            'pdsep-snte' => 'PDSEP-SNTE',
            'eyr' => 'EYR',
            'clidda' => 'CLIDDA',
            'normal' => 'normal',
            'por_definir' => 'por_definir',
            'retardo_menor' => 'con_retardo',
            'retardo_mayor' => 'retardo_mayor',
            'comision_entrada' => 'comision_entrada',
            'comision_salida' => 'comision_salida',
            'otross' => 'por_definir',
            'otros' => 'por_definir',
            'l.  fallecimiento fa' => 'permiso_fallecimiento',
            'l. cuidados familiar' => 'cuidados_maternos',
            'ret men/ com sal' => 'comision_salida',
            'falta' => 'falta',
        ];
        
        if (isset($mapeoDirecto[$tipo])) {
            return $mapeoDirecto[$tipo];
        }
        
        if ($tipo === '' || $tipo === 'nan') {
            return 'por_definir';
        }
        
        return $tipoExcel;
    }

    private function mapDiaSemana($dia) {
        $mapeo = [
            'Lunes' => 'lunes', 'Martes' => 'martes', 'Miércoles' => 'miércoles',
            'Jueves' => 'jueves', 'Viernes' => 'viernes', 'Sábado' => 'sábado', 'Domingo' => 'domingo',
        ];
        return $mapeo[$dia] ?? null;
    }

    public function truncateTables() {
        echo "Limpiando tablas...\n";
        $this->conn->exec("SET FOREIGN_KEY_CHECKS=0");
        $this->conn->exec("TRUNCATE TABLE retardos");
        $this->conn->exec("TRUNCATE TABLE asistencia");
        $this->conn->exec("SET FOREIGN_KEY_CHECKS=1");
        echo "Tablas limpiadas\n";
    }

    public function classifyPorDefinir() {
        echo "\n=== Clasificando registros por_definir ===\n";
        
        $stmt = $this->conn->query('SELECT id, hora_entrada, hora_salida FROM asistencia WHERE tipo_asistencia = "por_definir"');
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Total por_definir: " . count($registros) . "\n";
        
        $this->conn->beginTransaction();
        $tipoCount = ['normal' => 0, 'con_retardo' => 0, 'falta' => 0, 'comision_entrada' => 0, 'comision_salida' => 0];
        
        $stmtUpdate = $this->conn->prepare('UPDATE asistencia SET tipo_asistencia = ? WHERE id = ?');
        
        foreach ($registros as $reg) {
            $nuevoTipo = $this->classifyByTime($reg['hora_entrada'], $reg['hora_salida']);
            $tipoCount[$nuevoTipo]++;
            $stmtUpdate->execute([$nuevoTipo, $reg['id']]);
        }
        
        $this->conn->commit();
        
        echo "Clasificación completada:\n";
        foreach ($tipoCount as $tipo => $count) {
            echo "  $tipo: " . number_format($count) . "\n";
        }
        
        return $tipoCount;
    }

    public function createRetardosFromAsistencia() {
        echo "\n=== Creando registros en tabla retardos ===\n";
        
        $stmt = $this->conn->query('
            SELECT id, empleado_id, fecha, hora_entrada, hora_salida 
            FROM asistencia 
            WHERE tipo_asistencia = "con_retardo"
        ');
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Total con_retardo: " . count($registros) . "\n";
        
        $this->conn->beginTransaction();
        $stmtRetardo = $this->conn->prepare('
            INSERT INTO retardos 
            (empleado_id, fecha, hora_entrada, hora_salida, minutos_retardo, tipo_retraso, tipo_asistencia, categoria_principal, justificado, created_at, asistencia_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, "retardo", 0, NOW(), ?)
        ');
        
        foreach ($registros as $reg) {
            $minutos = $this->calcularMinutosRetardo($reg['hora_entrada']);
            $tipo = $minutos <= 20 ? 'retardo_menor' : 'retardo_mayor';
            
            $stmtRetardo->execute([
                $reg['empleado_id'], $reg['fecha'], $reg['hora_entrada'], $reg['hora_salida'],
                $minutos, $tipo, 'entrada', $reg['id']
            ]);
        }
        
        $this->conn->commit();
        
        echo "Retardos insertados: " . count($registros) . "\n";
        return count($registros);
    }

    public function importFromExcel($excelPath, $sheetName, $truncate = true, $autoClassify = true) {
        if ($truncate) {
            $this->truncateTables();
        }
        
        echo "Generando JSON desde Excel...\n";
        
        $tempFile = '/tmp/dgifa_import_' . uniqid() . '.json';
        
        $pythonScript = "import pandas as pd
df = pd.read_excel('$excelPath', sheet_name='$sheetName')
df = df.fillna('')
df.to_json('$tempFile', orient='records', force_ascii=False)
print('OK', flush=True)";
        
        exec("python3 -c " . escapeshellarg($pythonScript), $out, $ret);
        
        if (!file_exists($tempFile)) {
            echo "Error: No se generó el archivo JSON\n";
            return;
        }
        
        echo "JSON generado: " . number_format(filesize($tempFile)) . " bytes\n";
        
        $data = json_decode(file_get_contents($tempFile), true);
        
        if (empty($data)) {
            echo "Error: No se pudieron leer los datos del Excel\n";
            return;
        }
        
        echo "Total registros en Excel: " . number_format(count($data)) . "\n";
        echo "Procesando datos...\n";
        
        $this->conn->beginTransaction();
        
        $stmtAsistencia = $this->conn->prepare("
            INSERT INTO asistencia 
            (empleado_id, fecha, hora_entrada, hora_salida, tipo_asistencia, requerio_validacion, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        
        $insertedAsistencia = 0;
        $totalProcessed = 0;
        $skipped = 0;
        
        foreach ($data as $row) {
            $curp = $row['curp'] ?? '';
            $nombre = $row['nombre'] ?? '';
            $acNo = isset($row['AC-No.']) ? (int)$row['AC-No.'] : null;
            $fechaStr = $row['fecha'] ?? '';
            $horaEntradaStr = $row['hora_entrada'] ?? '';
            $horaSalidaStr = $row['hora_salida'] ?? '';
            $tipoAsistenciaExcel = $row['tipo_asistencia'] ?? '';
            
            if (empty($curp) && !$acNo) {
                $skipped++;
                continue;
            }
            
            $empleadoId = $this->getOrCreateEmpleado($curp, $nombre, $acNo);
            if (!$empleadoId) {
                $skipped++;
                continue;
            }
            
            $fecha = $this->parseFecha($fechaStr);
            if (!$fecha) {
                $skipped++;
                continue;
            }
            
            $horaEntrada = $this->parseTime($horaEntradaStr);
            $horaSalida = $this->parseTime($horaSalidaStr);
            $tipoAsistenciaMapped = $this->mapTipoAsistencia($tipoAsistenciaExcel);
            
            $stmtAsistencia->execute([
                $empleadoId,
                $fecha,
                $horaEntrada,
                $horaSalida,
                $tipoAsistenciaMapped
            ]);
            $insertedAsistencia++;
            $totalProcessed++;
            
            if ($totalProcessed % 2000 === 0) {
                echo "Procesando... $totalProcessed/" . count($data) . "\n";
                @ob_flush();
                flush();
                $this->conn->commit();
                $this->conn->beginTransaction();
            }
        }
        
        $this->conn->commit();
        
        unlink($tempFile);
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "RESUMEN DE IMPORTACIÓN\n";
        echo str_repeat("=", 50) . "\n";
        echo "Asistencias insertadas: " . number_format($insertedAsistencia) . "\n";
        echo "Total registros procesados: " . number_format($totalProcessed) . "\n";
        echo "Ignorados: " . number_format($skipped) . "\n";
        
        if ($autoClassify && $truncate) {
            echo "\n" . str_repeat("=", 50) . "\n";
            echo "CLASIFICACIÓN AUTOMÁTICA SEGÚN HORAS\n";
            echo str_repeat("=", 50) . "\n";
            echo "Normativa:\n";
            echo "  - 0-9 min tardanza: normal\n";
            echo "  - 10-30 min tardanza: con_retardo\n";
            echo "  - >30 min tardanza: falta\n";
            echo "  - Sin hora_entrada y sin hora_salida: falta\n";
            echo "  - Solo hora_entrada: comision_salida\n";
            echo "  - Solo hora_salida: comision_entrada\n";
            
            $this->classifyPorDefinir();
            $this->createRetardosFromAsistencia();
        }
        
        echo "\nErrores registrados: " . count($this->errores) . "\n";
    }
}

function mostrarAyuda() {
    echo "Uso: php import_excel_dgifa.php [opciones]\n\n";
    echo "Opciones:\n";
    echo "  --archivo=RUTA     Ruta del archivo Excel\n";
    echo "  --hoja=NOMBRE      Nombre de la hoja\n";
    echo "  --no-truncate      No limpiar tablas antes de importar\n";
    echo "  --no-classify      No clasificar automáticamente\n";
    echo "  --help            Mostrar esta ayuda\n\n";
    echo "Ejemplos:\n";
    echo "  php import_excel_dgifa.php\n";
    echo "  php import_excel_dgifa.php --no-truncate\n";
}

$opciones = getopt('', ['archivo:', 'hoja:', 'no-truncate', 'no-classify', 'help']);

if (isset($opciones['help'])) {
    mostrarAyuda();
    exit(0);
}

$archivo = $opciones['archivo'] ?? '/usr/share/nginx/html/sistema_biometrico/uploads/documentos/DGIFA 25-26.xlsx';
$hoja = $opciones['hoja'] ?? 'DGIFA 25-26.xls (2)';
$truncate = !isset($opciones['no-truncate']);
$autoClassify = !isset($opciones['no-classify']);

echo "====================================================\n";
echo "  IMPORTADOR DE EXCEL A BASE DE DATOS\n";
echo "  Archivo: $archivo\n";
echo "  Hoja: $hoja\n";
echo "  Modo: " . ($truncate ? "Limpiar tablas" : "Incremental") . "\n";
echo "  Clasificación: " . ($autoClassify ? "Automática" : "Manual") . "\n";
echo "====================================================\n\n";

$importador = new ImportadorExcel();
$importador->importFromExcel($archivo, $hoja, $truncate, $autoClassify);

echo "\nImportación completada.\n";