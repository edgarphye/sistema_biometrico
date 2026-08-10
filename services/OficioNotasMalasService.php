<?php
if (!class_exists('PhpOffice\PhpWord\PhpWord', false)) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/ClavePresupuestal.php';
require_once __DIR__ . '/../models/ConfigOficio.php';
require_once __DIR__ . '/../models/PlantillaDocumento.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

/**
 * Servicio de generación de oficios "ATENTA NOTA" por notas malas.
 * Replica el formato del docx de referencia (docs/notas malas ABRIL 2026 laura.docx):
 * membrete en header, folio DGIFA/CA/RH-XXX/AÑO, destinatario, cuerpo legal,
 * tablas MES|DÍA|HORA por inciso a/b, advertencia o suspensión y firma.
 */
class OficioNotasMalasService {

    private const TIPO_DOCUMENTO = 'oficio_notas_malas';

    /** Número de notas malas mensuales que generan 1 día de suspensión. */
    private const NOTAS_POR_SUSPENSION = 5;

    /** Días de la semana permitidos para programar suspensiones (1=Lun ... 7=Dom). */
    private const DIAS_SUSPENSION_SEMANA = [2, 3, 4];

    private static $MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    private $db;
    private $empleadoModel;
    private $claveModel;
    private $configModel;
    private $documentoModel;

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
        $this->empleadoModel = new Empleado();
        $this->claveModel = new ClavePresupuestal();
        $this->configModel = new ConfigOficio();
        $this->documentoModel = new DocumentoGenerado();
    }

    /**
     * Rango de fechas para el periodo/quincena
     */
    private function rango($mes, $anio, $quincena = 0) {
        $mes = str_pad((int)$mes, 2, '0', STR_PAD_LEFT);
        if ($quincena === 1) {
            return ["$anio-$mes-01", "$anio-$mes-15"];
        }
        if ($quincena === 2) {
            return ["$anio-$mes-16", date('Y-m-t', strtotime("$anio-$mes-01"))];
        }
        return ["$anio-$mes-01", date('Y-m-t', strtotime("$anio-$mes-01"))];
    }

    /**
     * Consulta unificada de incidencias: retardos sin justificar + retardos con nota registrada.
     * Retorna la lista de retardos de un empleado en el periodo.
     */
    private function getRetardosEmpleado($empleado_id, $mes, $anio, $quincena = 0) {
        list($inicio, $fin) = $this->rango($mes, $anio, $quincena);

        $sql = "
            SELECT DISTINCT r.id, r.empleado_id, r.fecha, r.hora_entrada,
                   r.tipo_retraso, r.minutos_retardo, r.justificado, r.motivo_detalle
            FROM retardos r
            WHERE r.empleado_id = ?
              AND r.fecha BETWEEN ? AND ?
              AND r.tipo_retraso IN ('retardo_menor','retardo_mayor','falta')
              AND ( r.justificado = 0
                 OR EXISTS (SELECT 1 FROM notas_malas nm
                            WHERE nm.retardo_id = r.id
                              AND nm.tipo IN ('retardo_menor','retardo_mayor')) )
            ORDER BY r.fecha ASC
        ";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$empleado_id, $inicio, $fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clasifica los retardos por inciso y calcula notas malas.
     * @return array|null null si no hay incidencias en el periodo
     */
    public function obtenerIncidenciasEmpleado($empleado_id, $mes, $anio, $quincena = 0) {
        $empleado = $this->empleadoModel->getById($empleado_id);
        if (!$empleado) {
            return null;
        }
        $retardos = $this->getRetardosEmpleado($empleado_id, $mes, $anio, $quincena);
        if (empty($retardos)) {
            return null;
        }

        $inciso_a = [];
        $inciso_b = [];
        $menores = 0;
        $mayores = 0;
        $faltas = 0;

        // Reglas del documento de referencia ("Comentarios"):
        // - El emparejamiento de 2 retardos menores -> 1 nota mala ocurre DENTRO de la misma quincena.
        // - Solo cuentan retardos NO justificados por el jefe, o justificados que EXCEDEN los 2 permitidos
        //   por quincena (los excedentes ya no se pueden justificar y generan nota).
        $menoresSinJustificar = [1 => 0, 2 => 0];
        $menoresExcesoJustificado = [1 => 0, 2 => 0];

        foreach ($retardos as $r) {
            $tipo = $r['tipo_retraso'];
            $dia = (int)date('j', strtotime($r['fecha']));
            $mesNombre = self::$MESES[(int)date('n', strtotime($r['fecha']))];
            $hora = substr((string)$r['hora_entrada'], 0, 5);
            $fila = [
                'fecha' => $r['fecha'],
                'mes' => $mesNombre,
                'dia' => $dia,
                'hora' => $hora,
                'tipo' => $tipo
            ];
            $quincena = $dia <= 15 ? 1 : 2;
            if ($tipo === 'retardo_menor') {
                $menores++;
                if (!empty($r['justificado'])) {
                    $menoresExcesoJustificado[$quincena]++;
                } else {
                    $menoresSinJustificar[$quincena]++;
                }
                $inciso_a[] = $fila;
            } elseif ($tipo === 'retardo_mayor') {
                $mayores++;
                $inciso_b[] = $fila;
            } else { // falta
                $faltas++;
                $inciso_b[] = $fila;
            }
        }

        $mayoresTotal = $mayores + $faltas;
        $notasMenores = 0;
        foreach ([1, 2] as $q) {
            $notasMenores += floor($menoresSinJustificar[$q] / 2);
            $notasMenores += floor($menoresExcesoJustificado[$q] / 2);
        }
        $notas_malas = $notasMenores + $mayoresTotal;
        $dias_suspension = (int)floor($notas_malas / self::NOTAS_POR_SUSPENSION);

        return [
            'empleado_id' => $empleado['id'],
            'nombre' => $empleado['nombre'],
            'apellido' => $empleado['apellido'],
            'rfc' => $empleado['rfc'],
            'area' => $empleado['area'],
            'claves_presupuestales' => $this->claveModel->getClavesUnidas($empleado['id']),
            'retardos' => $retardos,
            'inciso_a' => $inciso_a,
            'inciso_b' => $inciso_b,
            'menores' => $menores,
            'mayores' => $mayores,
            'faltas' => $faltas,
            'notas_malas' => $notas_malas,
            'notas_menores' => $notasMenores,
            'notas_mayores' => $mayoresTotal,
            'dias_suspension' => $dias_suspension,
            'requiere_suspension' => $dias_suspension > 0
        ];
    }

    /**
     * Incidencias de todos los empleados del periodo
     */
    public function getIncidenciasPeriodo($mes, $anio, $quincena = 0) {
        list($inicio, $fin) = $this->rango($mes, $anio, $quincena);

        $sql = "
            SELECT DISTINCT r.empleado_id
            FROM retardos r
            WHERE r.fecha BETWEEN ? AND ?
              AND r.tipo_retraso IN ('retardo_menor','retardo_mayor','falta')
              AND ( r.justificado = 0
                 OR EXISTS (SELECT 1 FROM notas_malas nm
                            WHERE nm.retardo_id = r.id
                              AND nm.tipo IN ('retardo_menor','retardo_mayor')) )
            ORDER BY r.empleado_id
        ";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$inicio, $fin]);

        $resultado = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $empleado_id) {
            $inc = $this->obtenerIncidenciasEmpleado($empleado_id, $mes, $anio, $quincena);
            if ($inc) {
                $resultado[$empleado_id] = $inc;
            }
        }
        return $resultado;
    }

    /**
     * Programa los días de suspensión conforme a las observaciones del oficio:
     * - Solo martes, miércoles o jueves.
     * - Un día por semana, en semanas distintas y nunca en días consecutivos.
     * @param int $dias Número de días de suspensión a programar
     * @param DateTimeInterface $desde Fecha base (se busca a partir del día siguiente)
     * @return DateTime[] Fechas programadas
     */
    public static function programarDiasSuspension(int $dias, DateTimeInterface $desde): array {
        $resultado = [];
        if ($dias <= 0) {
            return $resultado;
        }
        $candidato = DateTime::createFromInterface($desde);
        $candidato->modify('+1 day');
        $semanaAnterior = null;
        while (count($resultado) < $dias) {
            if (in_array((int)$candidato->format('N'), self::DIAS_SUSPENSION_SEMANA, true)) {
                $semana = (int)$candidato->format('oW');
                if ($semanaAnterior === null || $semana !== $semanaAnterior) {
                    $resultado[] = clone $candidato;
                    $semanaAnterior = $semana;
                }
            }
            $candidato->modify('+1 day');
        }
        return $resultado;
    }

    /**
     * Nombre del mes en minúsculas (formato del cuerpo del oficio: "07 de mayo").
     */
    private static function mesMinuscula(int $numero): string {
        return mb_strtolower(self::$MESES[$numero]);
    }

    /**
     * Incisos citados en el cuerpo legal según los retardos presentes.
     */
    public static function construirTextoIncisos(array $inciso_a, array $inciso_b): string {
        if (!empty($inciso_a) && !empty($inciso_b)) {
            return '“a” y “b”';
        }
        if (!empty($inciso_a)) {
            return '“a”';
        }
        return '“b”';
    }

    /**
     * Cuerpo legal estándar del oficio (párrafo idéntico al documento de referencia).
     */
    public static function construirCuerpoLegal(int $notasMalas, string $incisosTexto): string {
        $numNotas = str_pad((string)$notasMalas, 2, '0', STR_PAD_LEFT);
        return 'Derivado de la revisión efectuada a las listas de asistencia del personal, se detectó que '
            . 'incumplió con lo establecido en el Art. 44 Fracción VI de la Ley Federal de los Trabajadores al '
            . 'Servicio del Estado, en relación con el Art. 25 Fracción II (asistir con puntualidad al desempeño '
            . 'de sus labores) del Reglamento de las Condiciones Generales de Trabajo del Personal de la Secretaría '
            . 'de Educación Pública, siendo merecedor a ' . $numNotas . ' Nota (s) Mala (s) con fundamento en lo '
            . 'dispuesto en los Artículos 70, 71 fracción II, 74, 76 y 80 inciso ' . $incisosTexto
            . ' del reglamento aludido con antelación.';
    }

    /**
     * Fecha de emisión del oficio: "Ciudad de México, a 9 de Agosto de 2026".
     */
    public static function construirFechaEmision(DateTimeInterface $fecha): string {
        return sprintf(
            'Ciudad de México, a %d de %s de %d',
            (int)$fecha->format('j'),
            self::$MESES[(int)$fecha->format('n')],
            (int)$fecha->format('Y')
        );
    }

    /**
     * Lista de días de suspensión en texto:
     * "el día 07 de mayo", "el día 07 de mayo, el día 14 de mayo y el día 21 de mayo".
     */
    public static function construirListaDiasSuspension(array $diasSuspension): string {
        $fechasLista = array_map(
            function ($d) {
                return 'el día ' . str_pad((string)$d->format('j'), 2, '0', STR_PAD_LEFT) . ' de '
                    . self::mesMinuscula((int)$d->format('n'));
            },
            $diasSuspension
        );
        if (count($fechasLista) === 1) {
            return $fechasLista[0];
        }
        return implode(', ', array_slice($fechasLista, 0, -1)) . ' y ' . end($fechasLista);
    }

    /**
     * Párrafo de advertencia (menos de 5 notas malas) — texto idéntico al documento oficial.
     */
    public static function construirParrafoAdvertencia(): string {
        return 'Le informo que al acumular en el periodo de 1 mes 5 notas malas por retardos, dará lugar a '
            . '1 día de suspensión de sus labores y sueldo y, de acumular 7 suspensiones al término de 1 año '
            . 'dará lugar a que se solicite al Tribunal de Arbitraje la terminación de los efectos de su '
            . 'nombramiento, motivo por cuál se le exhorta a llegar con puntualidad a sus labores.';
    }

    /**
     * Párrafo de suspensión (5+ notas malas). El mes se escribe en minúsculas como en el documento oficial.
     */
    public static function construirParrafoSuspension(array $diasSuspension): string {
        $totalDias = count($diasSuspension);
        if ($totalDias <= 0) {
            return self::construirParrafoAdvertencia();
        }
        $esAplicable = $totalDias === 1 ? 'le es aplicable' : 'le son aplicables';
        $diasTexto = $totalDias === 1
            ? '01 día'
            : str_pad((string)$totalDias, 2, '0', STR_PAD_LEFT) . ' días';
        return 'Como resultado de lo anterior, ' . $esAplicable . ' ' . $diasTexto
            . ' de suspensión de sus labores y sueldo con fundamento en lo dispuesto en los Artículos 70,71 '
            . 'fracciones III y IV y articulo 80 inciso d) del Reglamento de las Condiciones Generales de Trabajo '
            . 'del Personal de la Secretaria de Educación Publica, dicha suspensión aplicara '
            . self::construirListaDiasSuspension($diasSuspension)
            . ' del año en curso y de acumular 7 suspensiones en el termino de 1 año dará lugar a que se solicite '
            . 'al Tribunal de Arbitraje la terminación de los efectos de su nombramiento motivo por el cual se le '
            . 'exhorta a llegar con puntualidad a sus labores.';
    }

    /**
     * Construye el documento .docx con PhpWord
     * @return array ['archivo' => ruta relativa, 'ruta_absoluta' => ...]
     */
    private function buildDocx(array $empleado, array $incidencias, array $config, $folio, $mes, $anio) {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 1080,
            'marginRight' => 1080
        ]);

        // Membrete en el header
        $membrete = ConfigOficio::rutaMembrete($config);
        if ($membrete && file_exists($membrete)) {
            $header = $section->addHeader();
            $header->addImage($membrete, [
                'width' => 209,
                'height' => 43,
                'alignment' => Jc::CENTER,
                'spaceAfter' => 60
            ]);
        }

        $fechaHoy = new DateTime();
        $folioStr = ConfigOficio::formatearFolio($config, $folio);
        $nombreCompleto = strtoupper(trim(($incidencias['apellido'] ?? '') . ' ' . ($incidencias['nombre'] ?? '')));

        // Incisos citados en el cuerpo legal
        $incisosTexto = self::construirTextoIncisos(
            $incidencias['inciso_a'] ?? [],
            $incidencias['inciso_b'] ?? []
        );

        // 1. Título
        $section->addText('ATENTA NOTA ' . $folioStr, ['bold' => true, 'size' => 12], ['spaceAfter' => 160]);

        // 2. Fecha (fecha de generación)
        $section->addText(self::construirFechaEmision($fechaHoy), [], ['spaceAfter' => 160]);

        // 3. Destinatario
        $section->addText($nombreCompleto . ' (ID ' . $empleado['id'] . ')', ['bold' => true]);
        $section->addText('Filiación: ' . strtoupper((string)($empleado['rfc'] ?? 'N/A')));
        if (!empty($incidencias['claves_presupuestales'])) {
            $section->addText('Clave Presupuestal: ' . $incidencias['claves_presupuestales']);
        }
        if (!empty($empleado['area'])) {
            $section->addText($empleado['area']);
        }
        $section->addText('P r e s e n t e.');
        $section->addText('', [], ['spaceAfter' => 120]);

        // 4. Cuerpo legal
        $cuerpo = self::construirCuerpoLegal((int)$incidencias['notas_malas'], $incisosTexto);
        $section->addText($cuerpo, [], ['spaceAfter' => 160]);

        // 5. Tablas por inciso
        $incisos = [];
        if (!empty($incidencias['inciso_a'])) {
            $incisos[] = ['titulo' => '80 inciso a)', 'filas' => $incidencias['inciso_a']];
        }
        if (!empty($incidencias['inciso_b'])) {
            $incisos[] = ['titulo' => '80 inciso b)', 'filas' => $incidencias['inciso_b']];
        }
        $this->agregarTablasIncisos($section, $incisos);

        // 6. Advertencia / Suspensión
        if (!empty($incidencias['requiere_suspension'])) {
            $diasSuspension = self::programarDiasSuspension(
                (int)($incidencias['dias_suspension'] ?? 1),
                new DateTime()
            );
            $parrafo = self::construirParrafoSuspension($diasSuspension);
        } else {
            $parrafo = self::construirParrafoAdvertencia();
        }
        $section->addText($parrafo, [], ['spaceAfter' => 160]);

        // 7. Cierre
        $section->addText('Sin otro particular, reciba un cordial saludo.', [], ['spaceAfter' => 200]);

        // 8. Firma
        $section->addText('ATENTAMENTE', ['bold' => true], ['spaceAfter' => 320]);
        $section->addText($config['nombre_firmante'] ?? 'JUAN JOSE OROZCO PONCE', ['bold' => true]);
        $section->addText($config['cargo_firmante'] ?? 'JEFE DEL DEPARTAMENTO DE RECURSOS HUMANOS');
        $section->addText(strtoupper((string)($config['iniciales'] ?? 'DGNQ*')));

        // 9. Pie de página institucional
        $footer = $section->addFooter();
        $footer->addText(
            'Colegio Salesiano 42, Colonia Anáhuac I Secc, Alcaldía Miguel Hidalgo, '
            . 'C.P. 11320 Ciudad de México. www.gob.mx/aefcm',
            ['size' => 8],
            ['alignment' => Jc::CENTER]
        );

        // Guardar
        $periodo = sprintf('%04d-%02d', (int)$anio, (int)$mes);
        $dirRel = 'uploads/oficios/' . $anio;
        $dirAbs = dirname(__DIR__) . '/' . $dirRel;
        if (!is_dir($dirAbs)) {
            mkdir($dirAbs, 0755, true);
        }
        $nombreArchivo = sprintf('%s_%d_%s.docx', str_pad($folio, 3, '0', STR_PAD_LEFT), $empleado['id'], $periodo);
        $rutaAbsoluta = $dirAbs . '/' . $nombreArchivo;
        $rutaRelativa = $dirRel . '/' . $nombreArchivo;

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($rutaAbsoluta);

        return [
            'archivo' => $rutaRelativa,
            'ruta_absoluta' => $rutaAbsoluta,
            'periodo' => $periodo . '-01'
        ];
    }

    /**
     * Agrega las tablas MES|DÍA|HORA por inciso, lado a lado si hay dos.
     */
    private function agregarTablasIncisos($section, array $incisos) {
        $styleTable = ['borderSize' => 4, 'borderColor' => '000000', 'cellMargin' => 40];
        $styleHeaderCell = ['valign' => 'center'];
        $styleCell = ['valign' => 'center'];

        if (count($incisos) === 1) {
            $this->agregarTablaInciso($section, $incisos[0]['titulo'], $incisos[0]['filas'], $styleTable, $styleHeaderCell, $styleCell);
            return;
        }

        // Dos tablas lado a lado dentro de una tabla contenedora sin bordes
        $contenedor = $section->addTable(['cellMargin' => 40]);
        $fila = $contenedor->addRow();
        foreach ($incisos as $inc) {
            $celda = $fila->addCell(4000);
            $this->agregarTablaIncisoEnCelda($celda, $inc['titulo'], $inc['filas'], $styleTable, $styleHeaderCell, $styleCell);
        }
    }

    private function agregarTablaInciso($section, $titulo, $filas, $styleTable, $styleHeaderCell, $styleCell) {
        $section->addText($titulo, [], ['spaceAfter' => 40]);
        $tabla = $section->addTable($styleTable);
        $tabla->addRow();
        foreach (['MES', 'DÍA', 'HORA'] as $enc) {
            $tabla->addCell(1500, $styleHeaderCell)->addText($enc, ['bold' => true]);
        }
        foreach ($filas as $f) {
            $tabla->addRow();
            $tabla->addCell(1500, $styleCell)->addText($f['mes']);
            $tabla->addCell(1500, $styleCell)->addText((string)$f['dia']);
            $tabla->addCell(1500, $styleCell)->addText($f['hora']);
        }
    }

    private function agregarTablaIncisoEnCelda($celda, $titulo, $filas, $styleTable, $styleHeaderCell, $styleCell) {
        $celda->addText($titulo, [], ['spaceAfter' => 40]);
        $tabla = $celda->addTable($styleTable);
        $tabla->addRow();
        foreach (['MES', 'DÍA', 'HORA'] as $enc) {
            $tabla->addCell(1200, $styleHeaderCell)->addText($enc, ['bold' => true]);
        }
        foreach ($filas as $f) {
            $tabla->addRow();
            $tabla->addCell(1200, $styleCell)->addText($f['mes']);
            $tabla->addCell(1200, $styleCell)->addText((string)$f['dia']);
            $tabla->addCell(1200, $styleCell)->addText($f['hora']);
        }
    }

    /**
     * Genera el oficio .docx para un empleado.
     * @return array ['success' => bool, ...]
     */
    public function generarOficio($empleado_id, $mes, $anio, $quincena = 0, $regenerar = false) {
        $anio = (int)$anio;
        $mes = str_pad((int)$mes, 2, '0', STR_PAD_LEFT);
        $periodo = "$anio-$mes-01";

        $empleado = $this->empleadoModel->getById($empleado_id);
        if (!$empleado) {
            return ['success' => false, 'error' => 'Empleado no encontrado'];
        }

        $incidencias = $this->obtenerIncidenciasEmpleado($empleado_id, $mes, $anio, $quincena);
        if (!$incidencias) {
            return ['success' => false, 'error' => 'El empleado no tiene incidencias en el periodo seleccionado'];
        }

        // Prevención de duplicados
        $existente = $this->documentoModel->getByEmpleadoPeriodo($empleado_id, self::TIPO_DOCUMENTO, $periodo, (int)$quincena);
        if ($existente && !$regenerar) {
            return [
                'success' => true,
                'ya_existia' => true,
                'documento_id' => $existente['id'],
                'archivo' => $existente['archivo_path'],
                'folio' => self::extraerFolio($existente['titulo'])
            ];
        }

        $config = $this->configModel->getConfig($anio);
        if (!$config) {
            return ['success' => false, 'error' => 'No se encontró la configuración de oficios para el año ' . $anio];
        }

        $folio = $this->configModel->obtenerSiguienteFolio($anio);
        if ($folio === null) {
            return ['success' => false, 'error' => 'No fue posible asignar el folio'];
        }

        try {
            $resultado = $this->buildDocx($empleado, $incidencias, $config, $folio, $mes, $anio);
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error generando el documento: ' . $e->getMessage()];
        }

        $titulo = 'Oficio ' . ConfigOficio::formatearFolio($config, $folio);

        // Si se regenera, eliminar el registro y archivo previo
        if ($existente) {
            $archivoPrev = $existente['archivo_path'];
            if ($archivoPrev && file_exists(dirname(__DIR__) . '/' . $archivoPrev)) {
                @unlink(dirname(__DIR__) . '/' . $archivoPrev);
            }
            $pdo = $this->db->getConnection();
            $pdo->prepare('DELETE FROM documentos_generados WHERE id = ?')->execute([$existente['id']]);
        }

        $guardado = $this->documentoModel->create([
            'empleado_id' => $empleado_id,
            'tipo_documento' => self::TIPO_DOCUMENTO,
            'titulo' => $titulo,
            'contenido' => null,
            'archivo_path' => $resultado['archivo'],
            'periodo' => $resultado['periodo'],
            'quincena' => (int)$quincena,
            'generado_por' => $_SESSION['user_id'] ?? null
        ]);

        if (!$guardado) {
            return ['success' => false, 'error' => 'No fue posible registrar el documento generado'];
        }

        return [
            'success' => true,
            'documento_id' => (int)$this->db->getConnection()->lastInsertId(),
            'archivo' => $resultado['archivo'],
            'folio' => $titulo,
            'notas_malas' => $incidencias['notas_malas'],
            'requiere_suspension' => $incidencias['requiere_suspension']
        ];
    }

    /**
     * Genera oficios para todos los empleados con incidencias y devuelve un ZIP.
     */
    public function generarMasivo($mes, $anio, $quincena = 0) {
        $anio = (int)$anio;
        $mes = str_pad((int)$mes, 2, '0', STR_PAD_LEFT);
        $incidencias = $this->getIncidenciasPeriodo($mes, $anio, $quincena);

        if (empty($incidencias)) {
            return ['success' => false, 'error' => 'No hay empleados con incidencias en el periodo seleccionado'];
        }

        $generados = 0;
        $omitidos = 0;
        $errores = [];
        $archivos = [];

        foreach (array_keys($incidencias) as $empleado_id) {
            $resultado = $this->generarOficio($empleado_id, $mes, $anio, $quincena);
            if ($resultado['ya_existia'] ?? false) {
                $omitidos++;
                if (isset($resultado['archivo']) && file_exists(dirname(__DIR__) . '/' . $resultado['archivo'])) {
                    $archivos[] = dirname(__DIR__) . '/' . $resultado['archivo'];
                }
            } elseif ($resultado['success'] ?? false) {
                $generados++;
                if (isset($resultado['archivo']) && file_exists(dirname(__DIR__) . '/' . $resultado['archivo'])) {
                    $archivos[] = dirname(__DIR__) . '/' . $resultado['archivo'];
                }
            } else {
                $errores[] = 'Empleado ' . $empleado_id . ': ' . ($resultado['error'] ?? 'error desconocido');
            }
        }

        if (empty($archivos)) {
            return ['success' => false, 'error' => 'No se generó ningún oficio'];
        }

        $dirZip = dirname(__DIR__) . '/uploads/oficios/' . $anio;
        if (!is_dir($dirZip)) {
            mkdir($dirZip, 0755, true);
        }
        $nombreZip = sprintf('oficios_%s-%02d_q%d_%s.zip', $anio, $mes, (int)$quincena, date('Ymd_His'));
        $rutaZip = $dirZip . '/' . $nombreZip;

        $zip = new ZipArchive();
        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'error' => 'No fue posible crear el archivo ZIP'];
        }
        foreach ($archivos as $archivo) {
            $zip->addFile($archivo, basename($archivo));
        }
        $zip->close();

        return [
            'success' => true,
            'generados' => $generados,
            'omitidos' => $omitidos,
            'errores' => $errores,
            'zip' => 'uploads/oficios/' . $anio . '/' . $nombreZip,
            'total' => count($archivos)
        ];
    }

    /**
     * Extrae el folio del título del documento (ej: "Oficio DGIFA/CA/RH-001/2026")
     */
    public static function extraerFolio($titulo) {
        return preg_replace('/^Oficio\s*/', '', (string)$titulo);
    }
}
