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

    // ── Diseño del documento original de referencia ──────────────────────────
    /** Márgenes en twips del documento original (Letter 8.5x11). */
    private const MARGEN_SUPERIOR = 2342;
    private const MARGEN_INFERIOR = 1418;
    private const MARGEN_LATERAL  = 1701;
    private const DIST_HEADER     = 709;
    private const DIST_FOOTER     = 350;

    /** Fuentes usadas en el original: Montserrat para el cuerpo y Noto Sans para el título. */
    private const FUENTE_CUERPO = 'Montserrat';
    private const FUENTE_TITULO = 'Noto Sans';

    /** Colores del original. */
    private const COLOR_AREA      = '333333';
    private const COLOR_MAROON    = '691135';
    private const SOMBREADO_TABLA = 'D5DCE4';

    /** Texto del membrete institucional (igual al documento original). */
    private const JERARQUIA = [
        'Autoridad Educativa Federal en la Ciudad de México',
        'Dirección General de Innovación y Fortalecimiento Académico',
        'Coordinación Administrativa',
        'Recursos Humanos',
        'Dirección de Recursos Materiales y Servicios',
    ];

    /** Pie de página institucional (color granate en el original). */
    private const PIE_PAGINA =
        'Colegio Salesiano 42, Colonia Anáhuac I Secc, Alcaldía Miguel Hidalgo, '
        . 'C.P. 11320 Ciudad de México. www.gob.mx/aefcm';

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

        // Reglas del documento de referencia:
        // - 2 retardos menores sin justificar = 1 nota mala, emparejados DENTRO del periodo
        //   seleccionado (el mes completo o la quincena elegida); NO se fragmenta por quincena
        //   cuando se consulta el mes completo.
        // - 1 retardo mayor = 1 nota mala (21-30 min); pasando de los 30 minutos es falta
        //   y cuenta también como nota mayor.
        // - Solo cuentan retardos NO justificados por el jefe, o justificados que EXCEDEN los 2 permitidos
        //   por quincena (los excedentes ya no se pueden justificar y generan nota).
        //   Para los menores justificados en exceso, cada registro de notas_malas ya representa
        //   un PAR completo (2 menores = 1 nota), por lo que se cuenta 1 nota por registro y
        //   NO se vuelve a dividir entre 2.
        $menoresSinJustificar = 0;
        $menoresExcesoJustificado = 0;

        foreach ($retardos as $r) {
            $tipo = $r['tipo_retraso'];
            $minutos = (int)($r['minutos_retardo'] ?? 0);
            // Regla del usuario: pasando de los 30 minutos es falta (cuenta como nota mayor),
            // aunque el registro esté clasificado como menor/mayor.
            if ($minutos > 30) {
                $tipo = 'falta';
            }
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
            if ($tipo === 'retardo_menor') {
                $menores++;
                if (!empty($r['justificado'])) {
                    $menoresExcesoJustificado++;
                } else {
                    $menoresSinJustificar++;
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
        // Los menores sin justificar se emparejan de 2 en 2 (floor); los menores justificados
        // en exceso ya llegan como un registro de notas_malas por cada par completo (1 = 1).
        $notasMenores = floor($menoresSinJustificar / 2) + $menoresExcesoJustificado;
        $notas_malas = $notasMenores + $mayoresTotal;
        $dias_suspension = (int)floor($notas_malas / self::NOTAS_POR_SUSPENSION);

        return [
            'empleado_id' => $empleado['id'],
            'nombre' => $empleado['nombre'],
            'apellido' => $empleado['apellido'],
            'rfc' => $empleado['rfc'],
            'area' => $empleado['area'],
            'claves_presupuestales' => $this->claveModel->getClavesUnidas($empleado['id'])
                ?: $this->plazasUnidas((int)$empleado['id']),
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
     * Une las claves de plaza (tabla plazas) de un empleado con " / ",
     * usado como respaldo cuando no hay claves presupuestales registradas.
     * @param int $empleado_id
     * @return string
     */
    private function plazasUnidas($empleado_id) {
        try {
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("
                SELECT PLAZA FROM plazas
                WHERE id_empleado = ? AND PLAZA IS NOT NULL AND PLAZA != ''
                ORDER BY id
            ");
            $stmt->execute([$empleado_id]);
            $plazas = array_map('trim', $stmt->fetchAll(PDO::FETCH_COLUMN));
            return implode(' / ', $plazas);
        } catch (Exception $e) {
            return '';
        }
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
     * Cuerpo legal estándar del oficio (texto plano, idéntico al documento de referencia).
     */
    public static function construirCuerpoLegal(int $notasMalas, string $incisosTexto): string {
        return implode('', array_column(self::segmentosCuerpoLegal($notasMalas, $incisosTexto), 'text'));
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
        return implode('', array_column(self::segmentosParrafoSuspension($diasSuspension), 'text'));
    }

    /**
     * Segmentos (texto + negrita) del cuerpo legal, idénticos al documento original.
     * En el original van en negrita el número de notas y el inciso citado.
     */
    public static function segmentosCuerpoLegal(int $notasMalas, string $incisosTexto): array {
        $numNotas = str_pad((string)$notasMalas, 2, '0', STR_PAD_LEFT);
        $prefijo = 'Derivado de la revisión efectuada a las listas de asistencia del personal, se detectó que '
            . 'incumplió con lo establecido en el Art. 44 Fracción VI de la Ley Federal de los Trabajadores al '
            . 'Servicio del Estado, en relación con el Art. 25 Fracción II (asistir con puntualidad al desempeño '
            . 'de sus labores) del Reglamento de las Condiciones Generales de Trabajo del Personal de la Secretaría '
            . 'de Educación Pública, siendo merecedor a ';
        $medio = ' con fundamento en lo dispuesto en los Artículos 70, 71 fracción II, 74, 76 y 80 inciso ';
        $fin = 'del reglamento aludido con antelación.';
        return [
            ['text' => $prefijo, 'bold' => false],
            ['text' => $numNotas . ' Nota (s) Mala (s)', 'bold' => true],
            ['text' => $medio, 'bold' => false],
            ['text' => $incisosTexto . ' ', 'bold' => true],
            ['text' => $fin, 'bold' => false],
        ];
    }

    /**
     * Segmentos del párrafo de advertencia (sin negritas en el original).
     */
    public static function segmentosParrafoAdvertencia(): array {
        return [
            ['text' => self::construirParrafoAdvertencia(), 'bold' => false],
        ];
    }

    /**
     * Segmentos del párrafo de suspensión. En el original van en negrita
     * el número de días y las fechas programadas de suspensión.
     */
    public static function segmentosParrafoSuspension(array $diasSuspension): array {
        $totalDias = count($diasSuspension);
        if ($totalDias <= 0) {
            return self::segmentosParrafoAdvertencia();
        }
        $esAplicable = $totalDias === 1 ? 'le es aplicable' : 'le son aplicables';
        $diasTexto = $totalDias === 1
            ? '01 día'
            : str_pad((string)$totalDias, 2, '0', STR_PAD_LEFT) . ' días';
        $prefijo = 'Como resultado de lo anterior, ' . $esAplicable . ' ';
        $medio = ' con fundamento en lo dispuesto en los Artículos 70,71 fracciones III y IV y articulo 80 inciso '
            . 'd) del Reglamento de las Condiciones Generales de Trabajo del Personal de la Secretaria de Educación '
            . 'Publica, dicha suspensión aplicara ';
        $fechas = self::construirListaDiasSuspension($diasSuspension) . ' del año en curso';
        $resto = ' y de acumular 7 suspensiones en el termino de 1 año dará lugar a que se solicite al Tribunal de '
            . 'Arbitraje la terminación de los efectos de su nombramiento motivo por el cual se le exhorta a llegar '
            . 'con puntualidad a sus labores.';
        return [
            ['text' => $prefijo, 'bold' => false],
            ['text' => $diasTexto . ' de suspensión de sus labores y sueldo', 'bold' => true],
            ['text' => $medio, 'bold' => false],
            ['text' => $fechas, 'bold' => true],
            ['text' => $resto, 'bold' => false],
        ];
    }

    /**
     * Construye el modelo de render del oficio (compartido por el .docx y el HTML).
     * Garantiza que la vista previa HTML y el Word generado tengan exactamente el mismo contenido.
     */
    private function construirModelo(array $empleado, array $incidencias, array $config, $folio, $mes, $anio): array {
        $fechaHoy = new DateTime();
        $folioStr = (is_string($folio) && strpos($folio, '/') !== false)
            ? $folio
            : ConfigOficio::formatearFolio($config, $folio);
        $incisosTexto = self::construirTextoIncisos(
            $incidencias['inciso_a'] ?? [],
            $incidencias['inciso_b'] ?? []
        );

        $requiereSuspension = !empty($incidencias['requiere_suspension']);
        $diasSuspension = [];
        if ($requiereSuspension) {
            $diasSuspension = self::programarDiasSuspension(
                (int)($incidencias['dias_suspension'] ?? 1),
                $fechaHoy
            );
        }

        $incisos = [];
        if (!empty($incidencias['inciso_a'])) {
            $incisos[] = ['titulo' => '80 inciso a)', 'filas' => $incidencias['inciso_a']];
        }
        if (!empty($incidencias['inciso_b'])) {
            $incisos[] = ['titulo' => '80 inciso b)', 'filas' => $incidencias['inciso_b']];
        }

        return [
            'folio' => $folioStr,
            'titulo' => 'ATENTA NOTA ' . $folioStr,
            'fecha_emision' => self::construirFechaEmision($fechaHoy),
            'empleado_id' => (int)($empleado['id'] ?? 0),
            'nombre_completo' => strtoupper(trim(($incidencias['apellido'] ?? '') . ' ' . ($incidencias['nombre'] ?? ''))),
            'rfc' => strtoupper((string)($empleado['rfc'] ?? 'N/A')),
            'claves_presupuestales' => (string)($incidencias['claves_presupuestales'] ?? ''),
            'area' => (string)($empleado['area'] ?? ''),
            'cuerpo' => self::segmentosCuerpoLegal((int)$incidencias['notas_malas'], $incisosTexto),
            'incisos' => $incisos,
            'parrafo' => $requiereSuspension
                ? self::segmentosParrafoSuspension($diasSuspension)
                : self::segmentosParrafoAdvertencia(),
            'requiere_suspension' => $requiereSuspension,
            'dias_suspension' => array_map(function ($d) {
                return $d->format('Y-m-d');
            }, $diasSuspension),
            'iniciales' => strtoupper((string)($config['iniciales'] ?? 'DGNQ*')),
            'firmante' => (string)($config['nombre_firmante'] ?? 'JUAN JOSE OROZCO PONCE'),
            'cargo' => (string)($config['cargo_firmante'] ?? 'JEFE DEL DEPARTAMENTO DE RECURSOS HUMANOS'),
        ];
    }

    /**
     * Agrega un run de texto con segmentos en negrita (estilo del cuerpo).
     */
    private function agregarRun($container, array $segmentos, array $parrafoStyle = []) {
        $run = $container->addTextRun($parrafoStyle);
        foreach ($segmentos as $seg) {
            $run->addText(
                (string)($seg['text'] ?? ''),
                ['name' => self::FUENTE_CUERPO, 'size' => 9, 'bold' => !empty($seg['bold'])]
            );
        }
        return $run;
    }

    /**
     * Encabezado del documento: membrete + jerarquía institucional a la derecha
     * (igual que el documento original).
     */
    private function agregarEncabezado($section, array $config) {
        $header = $section->addHeader();

        $logoIzq = dirname(__DIR__) . '/assets/images/membrete_izq.png';
        $logoDer = dirname(__DIR__) . '/assets/images/membrete_der.png';

        // Membrete en 3 columnas: logo SEP (izq), texto institucional (derecha), wordmark (der)
        $table = $header->addTable([
            'cellMarginTop' => 0,
            'cellMarginBottom' => 0,
            'cellMarginLeft' => 0,
            'cellMarginRight' => 0
        ]);
        $table->addRow();

        $celdaIzq = $table->addCell(3600, ['vAlign' => 'center']);
        if (file_exists($logoIzq)) {
            $celdaIzq->addImage($logoIzq, [
                'width' => 175,
                'height' => 22,
                'alignment' => Jc::LEFT
            ]);
        }

        $celdaCentro = $table->addCell(3900, ['vAlign' => 'center']);
        foreach (self::JERARQUIA as $linea) {
            $celdaCentro->addText(
                $linea,
                ['name' => self::FUENTE_TITULO, 'size' => 6.5],
                ['alignment' => Jc::RIGHT, 'spacing' => ['line' => 240, 'lineRule' => 'exact']]
            );
        }

        $celdaDer = $table->addCell(1300, ['vAlign' => 'center']);
        if (file_exists($logoDer)) {
            $celdaDer->addImage($logoDer, [
                'width' => 36,
                'height' => 36,
                'alignment' => Jc::RIGHT
            ]);
        }

        $header->addText('', [], ['spacing' => ['line' => 240, 'lineRule' => 'exact'], 'spaceAfter' => 80]);
    }

    /**
     * Construye el documento .docx con PhpWord replicando el diseño del original.
     * @return array ['archivo' => ruta relativa, 'ruta_absoluta' => ..., 'periodo' => ..., 'modelo' => ...]
     */
    private function buildDocx(array $empleado, array $incidencias, array $config, $folio, $mes, $anio) {
        $modelo = $this->construirModelo($empleado, $incidencias, $config, $folio, $mes, $anio);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName(self::FUENTE_CUERPO);
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'pageSizeW' => 12240,
            'pageSizeH' => 15840,
            'marginTop' => self::MARGEN_SUPERIOR,
            'marginBottom' => self::MARGEN_INFERIOR,
            'marginLeft' => self::MARGEN_LATERAL,
            'marginRight' => self::MARGEN_LATERAL,
            'headerHeight' => self::DIST_HEADER,
            'footerHeight' => self::DIST_FOOTER
        ]);

        $this->agregarEncabezado($section, $config);

        // 1. Título (justificado, Noto Sans, igual que el original)
        $section->addText(
            $modelo['titulo'],
            ['name' => self::FUENTE_TITULO, 'bold' => true, 'size' => 12],
            ['alignment' => Jc::BOTH, 'spacing' => ['line' => 360, 'lineRule' => 'auto'], 'spaceAfter' => 160]
        );

        // 2. Fecha (alineada a la derecha, Montserrat 9)
        $section->addText(
            $modelo['fecha_emision'],
            ['name' => self::FUENTE_CUERPO, 'size' => 9],
            ['alignment' => Jc::RIGHT, 'spacing' => ['line' => 240, 'lineRule' => 'exact'], 'spaceAfter' => 160]
        );

        // 3. Destinatario (justificado, negritas; el área en gris claro 8pt)
        $destinatario = [
            $modelo['nombre_completo'] . ' (ID ' . $modelo['empleado_id'] . ')' => true,
            'Filiación: ' . $modelo['rfc'] => true,
        ];
        if (!empty($modelo['claves_presupuestales'])) {
            $destinatario['Clave Presupuestal: ' . $modelo['claves_presupuestales']] = true;
        }
        if (!empty($modelo['area']) && trim((string)$modelo['area']) !== trim((string)$modelo['claves_presupuestales'])) {
            $destinatario['__area__'] = false;
        }
        $destinatario['P r e s e n t e.'] = true;

        foreach ($destinatario as $texto => $negrita) {
            $font = ['name' => self::FUENTE_CUERPO, 'size' => 9, 'bold' => $negrita];
            if ($texto === '__area__') {
                $font = ['name' => self::FUENTE_CUERPO, 'size' => 8, 'bold' => false, 'color' => self::COLOR_AREA];
                $texto = $modelo['area'];
            }
            $section->addText(
                $texto,
                $font,
                ['alignment' => Jc::BOTH, 'spacing' => ['line' => 240, 'lineRule' => 'exact']]
            );
        }
        $section->addText('', [], ['spacing' => ['line' => 240, 'lineRule' => 'exact'], 'spaceAfter' => 120]);

        // 4. Cuerpo legal (justificado, con negritas)
        $this->agregarRun($section, $modelo['cuerpo'], [
            'alignment' => Jc::BOTH,
            'spacing' => ['line' => 240, 'lineRule' => 'exact'],
            'spaceAfter' => 160
        ]);

        // 5. Tablas por inciso
        $this->agregarTablasIncisos($section, $modelo['incisos']);

        // 6. Advertencia / Suspensión
        $this->agregarRun($section, $modelo['parrafo'], [
            'alignment' => Jc::BOTH,
            'spacing' => ['line' => 240, 'lineRule' => 'exact'],
            'spaceAfter' => 160
        ]);

        // 7. Cierre
        $section->addText(
            'Sin otro particular, reciba un cordial saludo.',
            ['name' => self::FUENTE_CUERPO, 'size' => 9],
            ['alignment' => Jc::BOTH, 'spacing' => ['line' => 240, 'lineRule' => 'exact'], 'spaceAfter' => 200]
        );

        // 8. Firma
        $section->addText(
            'ATENTAMENTE',
            ['name' => self::FUENTE_CUERPO, 'bold' => true, 'size' => 9],
            ['spacing' => ['line' => 240, 'lineRule' => 'exact'], 'spaceAfter' => 320]
        );
        $section->addText(
            $modelo['firmante'],
            ['name' => self::FUENTE_CUERPO, 'bold' => true, 'size' => 9],
            ['spacing' => ['line' => 240, 'lineRule' => 'exact']]
        );
        $section->addText(
            $modelo['cargo'],
            ['name' => self::FUENTE_CUERPO, 'bold' => true, 'size' => 9],
            ['spacing' => ['line' => 240, 'lineRule' => 'exact']]
        );
        $section->addText(
            $modelo['iniciales'],
            ['name' => self::FUENTE_CUERPO, 'size' => 7],
            ['alignment' => Jc::RIGHT, 'spacing' => ['line' => 240, 'lineRule' => 'exact']]
        );

        // 9. Pie de página institucional (cinta con dirección y sello, igual que el original)
        $footer = $section->addFooter();
        $pieImg = dirname(__DIR__) . '/assets/images/pie_completo.png';
        if (file_exists($pieImg)) {
            $footer->addImage($pieImg, [
                'width' => 383,
                'height' => 46,
                'alignment' => Jc::CENTER
            ]);
        } else {
            $footer->addText(
                self::PIE_PAGINA,
                ['name' => 'Noto Sans SemiBold', 'size' => 6.5, 'color' => self::COLOR_MAROON],
                ['alignment' => Jc::CENTER]
            );
        }

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
            'periodo' => $periodo . '-01',
            'modelo' => $modelo
        ];
    }

    /**
     * Agrega las tablas MES|DÍA|HORA por inciso (una tabla por inciso).
     */
    private function agregarTablasIncisos($section, array $incisos) {
        foreach ($incisos as $inc) {
            $this->agregarTablaInciso($section, $inc['titulo'], $inc['filas']);
        }
    }

    /**
     * Tabla del inciso: título en celda combinada, encabezados con sombreado
     * #D5DCE4 y datos centrados en negrita (igual que el original).
     */
    private function agregarTablaInciso($section, $titulo, $filas) {
        $styleTable = [
            'alignment' => Jc::CENTER,
            'layout' => 'fixed',
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMargin' => 40,
        ];
        $anchos = [1555, 1134, 1417];
        $celdaBase = ['valign' => 'center'];
        $parrafoCelda = ['alignment' => Jc::CENTER, 'spacing' => ['line' => 240, 'lineRule' => 'exact']];

        // Título del inciso (celda combinada sin sombreado)
        $tabla = $section->addTable($styleTable);
        $filaTitulo = $tabla->addRow();
        $filaTitulo->addCell(array_sum($anchos), ['gridSpan' => 3, 'valign' => 'center'])
            ->addText($titulo, ['name' => self::FUENTE_CUERPO, 'bold' => true, 'size' => 9], $parrafoCelda);

        // Encabezados MES | DÍA | HORA con sombreado
        $filaEnc = $tabla->addRow();
        foreach (['MES', 'DÍA', 'HORA'] as $i => $enc) {
            $filaEnc->addCell($anchos[$i], ['valign' => 'center', 'shading' => ['fill' => self::SOMBREADO_TABLA]])
                ->addText($enc, ['name' => self::FUENTE_CUERPO, 'bold' => true, 'size' => 9], $parrafoCelda);
        }

        // Datos
        foreach ($filas as $f) {
            $fila = $tabla->addRow();
            $datos = [$f['mes'], (string)$f['dia'], $f['hora']];
            foreach ($datos as $i => $valor) {
                $fila->addCell($anchos[$i], $celdaBase)
                    ->addText($valor, ['name' => self::FUENTE_CUERPO, 'bold' => true, 'size' => 9], $parrafoCelda);
            }
        }
    }

    /**
     * Convierte segmentos [texto, bold] a HTML con <b>.
     */
    private function segmentosHtml(array $segmentos): string {
        $out = '';
        foreach ($segmentos as $seg) {
            $texto = htmlspecialchars((string)($seg['text'] ?? ''), ENT_QUOTES, 'UTF-8');
            $out .= !empty($seg['bold']) ? '<b>' . $texto . '</b>' : $texto;
        }
        return $out;
    }

    /**
     * Reconstruye el modelo de un documento generado (desde el JSON guardado
     * en contenido, o recalculándolo si el registro es antiguo).
     */
    public function obtenerModeloDocumento(array $doc): array {
        if (!empty($doc['contenido'])) {
            $json = json_decode($doc['contenido'], true);
            if (is_array($json) && isset($json['titulo'])) {
                return $json;
            }
        }

        $periodo = (string)($doc['periodo'] ?? '');
        $anio = (int)substr($periodo, 0, 4);
        $mes = (int)substr($periodo, 5, 2);
        if ($anio <= 0) {
            $anio = (int)date('Y');
            $mes = (int)date('n');
        }
        $quincena = (int)($doc['quincena'] ?? 0);
        $empleado = $this->empleadoModel->getById((int)($doc['empleado_id'] ?? 0));
        $incidencias = $empleado
            ? $this->obtenerIncidenciasEmpleado((int)$doc['empleado_id'], $mes, $anio, $quincena)
            : null;
        $config = $this->configModel->getConfig($anio);
        $folio = self::extraerFolio($doc['titulo'] ?? '');

        if (!$empleado || !$incidencias || !$config) {
            throw new RuntimeException('No fue posible reconstruir el documento para la vista previa.');
        }

        return $this->construirModelo($empleado, $incidencias, $config, $folio, $mes, $anio);
    }

    /**
     * Genera el HTML de la vista previa replicando el diseño del documento original.
     */
    public function renderPreviewHtml(array $modelo): string {
        $base = defined('BASE_URL') ? BASE_URL : '';
        $e = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };

        $cuerpoHtml = $this->segmentosHtml($modelo['cuerpo'] ?? []);
        $parrafoHtml = $this->segmentosHtml($modelo['parrafo'] ?? []);

        $destinatario = '<p class="b">' . $e($modelo['nombre_completo'] ?? '')
            . ' (ID ' . (int)($modelo['empleado_id'] ?? 0) . ')</p>'
            . '<p class="b">Filiación: ' . $e($modelo['rfc'] ?? '') . '</p>';
        if (!empty($modelo['claves_presupuestales'])) {
            $destinatario .= '<p class="b">Clave Presupuestal: ' . $e($modelo['claves_presupuestales']) . '</p>';
        }
        if (!empty($modelo['area']) && trim((string)$modelo['area']) !== trim((string)$modelo['claves_presupuestales'])) {
            $destinatario .= '<p class="area">' . $e($modelo['area']) . '</p>';
        }
        $destinatario .= '<p class="b">P r e s e n t e.</p>';

        $tablas = '';
        foreach (($modelo['incisos'] ?? []) as $inc) {
            $filas = '';
            foreach (($inc['filas'] ?? []) as $f) {
                $filas .= '<tr><td>' . $e($f['mes'] ?? '') . '</td><td>' . $e($f['dia'] ?? '')
                    . '</td><td>' . $e($f['hora'] ?? '') . '</td></tr>';
            }
            $tablas .= '<table class="inciso">'
                . '<thead><tr><th class="titulo" colspan="3">' . $e($inc['titulo'] ?? '') . '</th></tr>'
                . '<tr><th>MES</th><th>DÍA</th><th>HORA</th></tr></thead>'
                . '<tbody>' . $filas . '</tbody></table>';
        }

        $jerarquia = '';
        foreach (self::JERARQUIA as $linea) {
            $jerarquia .= '<div>' . $e($linea) . '</div>';
        }

        return '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
            . '<title>Vista previa del oficio</title><style>'
            . "@font-face{font-family:'Montserrat';src:url('$base/assets/fonts/Montserrat-400.ttf');font-weight:400;}"
            . "@font-face{font-family:'Montserrat';src:url('$base/assets/fonts/Montserrat-600.ttf');font-weight:600;}"
            . "@font-face{font-family:'Montserrat';src:url('$base/assets/fonts/Montserrat-700.ttf');font-weight:700;}"
            . "@font-face{font-family:'Noto Sans';src:url('$base/assets/fonts/NotoSans-400.ttf');font-weight:400;}"
            . "@font-face{font-family:'Noto Sans';src:url('$base/assets/fonts/NotoSans-700.ttf');font-weight:700;}"
            . 'body{margin:0;padding:24px 0;background:#525659;font-family:Montserrat,"Noto Sans",Arial,sans-serif;font-size:9pt;color:#000;}'
            . '.page{background:#fff;width:8.5in;min-height:11in;margin:0 auto 32px;padding:1.63in 1.18in 0.98in;box-shadow:0 4px 24px rgba(0,0,0,.4);box-sizing:border-box;}'
            . '.membrete-row{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;}'
            . '.membrete-izq{height:22pt;width:auto;}'
            . '.membrete-der{height:36pt;width:auto;}'
            . '.jerarquia{font-family:"Noto Sans",Arial,sans-serif;font-size:6.5pt;text-align:right;flex:1;line-height:1.3;margin:0 8px;}'
            . '.titulo{text-align:justify;font-family:"Noto Sans",Arial,sans-serif;font-weight:700;font-size:12pt;line-height:1.4;margin:0 0 10px;}'
            . '.fecha{text-align:right;font-size:9pt;margin:0 0 14px;}'
            . '.dest{text-align:justify;margin:0 0 14px;line-height:1.35;}'
            . '.dest p{margin:0 0 2px;}'
            . '.dest .b{font-weight:700;}'
            . '.dest .area{color:#333333;font-size:8pt;font-weight:400;}'
            . '.cuerpo{text-align:justify;line-height:1.25;margin:0 0 14px;}'
            . 'table.inciso{border-collapse:collapse;margin:0 auto 16px;font-weight:700;text-align:center;}'
            . 'table.inciso th,table.inciso td{border:1px solid #000;padding:2px 14px;font-size:9pt;}'
            . 'table.inciso th.titulo{background:#fff;font-weight:700;}'
            . 'table.inciso thead tr:last-child th{background:#D5DCE4;}'
            . '.parrafo{text-align:justify;line-height:1.25;margin:0 0 14px;}'
            . '.cierre{text-align:justify;margin:0 0 18px;}'
            . '.firma p{margin:0;}'
            . '.firma .atentamente{font-weight:700;margin-bottom:26px;}'
            . '.firma .firmante{font-weight:700;}'
            . '.iniciales{text-align:right;font-size:7pt;}'
             . '.pie-img{width:100%;margin-top:26px;display:block;}'
            . '</style></head><body><div class="page">'
            . '<div class="membrete-row">'
            . '<img class="membrete-izq" src="' . $e($base . '/assets/images/membrete_izq.png') . '" alt="">'
            . '<div class="jerarquia">' . $jerarquia . '</div>'
            . '<img class="membrete-der" src="' . $e($base . '/assets/images/membrete_der.png') . '" alt="">'
            . '</div>'
            . '<p class="titulo">' . $e($modelo['titulo'] ?? '') . '</p>'
            . '<p class="fecha">' . $e($modelo['fecha_emision'] ?? '') . '</p>'
            . '<div class="dest">' . $destinatario . '</div>'
            . '<div class="cuerpo">' . $cuerpoHtml . '</div>'
            . $tablas
            . '<div class="parrafo">' . $parrafoHtml . '</div>'
            . '<p class="cierre">Sin otro particular, reciba un cordial saludo.</p>'
            . '<div class="firma"><p class="atentamente">ATENTAMENTE</p>'
            . '<p class="firmante">' . $e($modelo['firmante'] ?? '') . '</p>'
            . '<p class="firmante">' . $e($modelo['cargo'] ?? '') . '</p>'
            . '<p class="iniciales">' . $e($modelo['iniciales'] ?? '') . '</p></div>'
             . '<img class="pie-img" src="' . $e($base . '/assets/images/pie_completo.png') . '" alt="">'
            . '</div></body></html>';
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
        if ((int)$incidencias['notas_malas'] <= 0) {
            return ['success' => false, 'error' => 'El empleado no acumula notas malas en el periodo seleccionado'];
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
            'contenido' => json_encode($resultado['modelo'], JSON_UNESCAPED_UNICODE),
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

        // Solo se generan oficios para quienes acumulan al menos 1 nota mala.
        $incidencias = array_filter($incidencias, function ($inc) {
            return (int)($inc['notas_malas'] ?? 0) > 0;
        });
        if (empty($incidencias)) {
            return ['success' => false, 'error' => 'Ningún empleado acumula notas malas en el periodo seleccionado'];
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
