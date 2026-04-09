<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ZKTecoUniversalParser.php'; // Asegurar existencia
require_once __DIR__ . '/../models/ZKTecoAsistenciaInserter.php'; // Asegurar existencia

/**
 * Controller de Configuración con Sistema ZKTeco Completo
 * Maneja carga de archivos .DAT con mapeo dinámico e inserción inteligente
 */
class ConfiguracionController extends BaseController
{
    private $asistenciaModel;
    
    // Configuración por defecto del procesador ZKTeco
    private $configuracionZKTeco = [
        'procesar_fines_semana' => false,
        'procesar_registros_fallidos' => false,
        'ajuste_horario_nocturno' => true,
        'calidad_minima' => null,
        'tipos_verificacion_permitidos' => null
    ];
    
    public function __construct()
    {
        parent::__construct();
        require_once __DIR__ . '/../models/Asistencia.php';
        $this->asistenciaModel = new Asistencia();
    }
    
    /**
     * Página principal de configuración
     */
    public function index()
    {
        // Verificar sesión antes de requireAuth
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado - Sesión requerida'], 401);
            exit;
        }
        
        // Cargar helper CSRF si no está disponible
        if (!class_exists('Csrf')) {
            require_once __DIR__ . '/../helpers/Csrf.php';
        }
        
        // Establecer variables para el layout
        $title = 'Configuración del Sistema';
        
        // Incluir el layout que automáticamente cargará el contenido de configuración
        include __DIR__ . '/../views/layout.php';
    }
    
    /**
     * Procesa archivo ZKTeco .DAT
     */
    public function uploadZkteco()
    {
        // Limpiar cualquier salida previa
        if (ob_get_length()) ob_clean();
        
        // Temporalmente desactivado para testing
        // $this->requireAuth();
        
        // Cargar helper CSRF si no está disponible
        if (!class_exists('Csrf')) {
            require_once __DIR__ . '/../helpers/Csrf.php';
        }

        try {
            Csrf::checkToken();
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        
        if (!isset($_FILES['zk_file'])) {
            $this->jsonResponse(['error' => 'No se ha seleccionado ningún archivo'], 400);
            return;
        }
        
        $file = $_FILES['zk_file'];
        
        try {
            // Iniciar log de procesamiento
            $logId = $this->iniciarLogProcesamiento($file);
            
            // Validar archivo
            $validacion = $this->validarArchivo($file);
            if (!$validacion['valido']) {
                $this->actualizarLogProcesamiento($logId, false, $validacion['error'], null);
                $this->jsonResponse(['error' => $validacion['error']], 400);
                return;
            }
            
            // Fase 1: Procesar archivo con parser universal
            $parser = new ZKTecoUniversalParser($this->configuracionZKTeco);
            $this->cargarMapeoEmpleados($parser);
            
            $resultadoParser = $parser->procesarArchivo($file['tmp_name']);
            
            if (!$resultadoParser['exito']) {
                $this->actualizarLogProcesamiento($logId, false, $resultadoParser['error'], $resultadoParser);
                $this->jsonResponse(['error' => 'Error en procesamiento: ' . $resultadoParser['error']], 500);
                return;
            }
            
            // Fase 2: Insertar registros en base de datos
            $inserter = new ZKTecoAsistenciaInserter();
            $resultadoInsercion = $inserter->insertarRegistros($resultadoParser['registros']); // Esto ahora calcula retardos
            
            // Fase 3: Guardar formato detectado para futuros procesamientos
            $this->guardarFormatoDetectado($resultadoParser['formato_detectado']);
            
            // Fase 4: Actualizar log final
            $metadataFinal = [
                'formato_detectado' => $resultadoParser['formato_detectado'],
                'mapeo_campos' => $resultadoParser['mapeo_campos'],
                'estadisticas_parser' => $resultadoParser['estadisticas'] ?? [],
                'estadisticas_insercion' => $resultadoInsercion['estadisticas'] ?? []
            ];
            
            $this->actualizarLogProcesamiento($logId, $resultadoInsercion['exito'], null, $metadataFinal);
            
            // Preparar respuesta exitosa
            $respuesta = [
                'success' => true,
                'message' => 'Archivo ZKTeco procesado correctamente',
                'estadisticas_generales' => [
                    'nombre_archivo' => $file['name'],
                    'tamano' => $this->formatearBytes($file['size']),
                    'formato_detectado' => $this->formatoDetectadoLegible($resultadoParser['formato_detectado']),
                    'confianza_formato' => ($resultadoParser['formato_detectado']['confianza'] ?? 0) . '%',
                     'registros_leidos' => $resultadoParser['estadisticas']['registros_leidos'] ?? 0,
                     'registros_validos' => $resultadoParser['estadisticas']['registros_validos'] ?? 0,
                     'registros_omitidos' => $resultadoParser['estadisticas']['registros_omitidos'] ?? 0,
                     'empleados_unicos' => $resultadoParser['estadisticas']['empleados_unicos'] ?? 0,
                     'periodo_procesado' => $this->formatearPeriodo($resultadoParser['estadisticas'] ?? []),
                    'resumen_insercion' => $resultadoInsercion['resumen'] ?? []
                ],
                'detalle_errores' => array_slice($resultadoParser['errores'], 0, 10), // Primeros 10 errores
                'total_errores' => count($resultadoParser['errores']),
                'tiempo_total_procesamiento' => ($resultadoInsercion['estadisticas']['tiempo_procesamiento'] ?? 0) . ' segundos'
            ];
            
            $this->jsonResponse($respuesta);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Error del sistema: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Valida el archivo subido
     */
    private function validarArchivo($file) {
        // Validar extensión
        $allowedExtensions = ['dat', 'DAT'];
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return ['valido' => false, 'error' => 'Solo se permiten archivos .dat'];
        }
        
        // Validar tamaño (10MB máximo)
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['valido' => false, 'error' => 'El archivo es demasiado grande (máximo 10MB)'];
        }
        
        // Validar que no esté vacío
        if ($file['size'] == 0) {
            return ['valido' => false, 'error' => 'El archivo está vacío'];
        }
        
        // Validar que sea un archivo de texto
        if (!in_array(mime_content_type($file['tmp_name']), ['text/plain', 'application/octet-stream'])) {
            return ['valido' => false, 'error' => 'El archivo debe ser de texto plano'];
        }
        
        return ['valido' => true];
    }
    
    /**
     * Inicia log de procesamiento
     */
    private function iniciarLogProcesamiento($file) {
        $db = new Database();
        $sha256 = hash_file('sha256', $file['tmp_name']);
        
        $sql = "
            INSERT INTO zkteco_procesamiento_logs (
                archivo_nombre, archivo_tamano, archivo_sha256, creado_at
            ) VALUES (?, ?, ?, NOW())
        ";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([
            $file['name'],
            $file['size'],
            $sha256
        ]);
        
        return $db->getConnection()->lastInsertId();
    }
    
    /**
     * Actualiza log de procesamiento
     */
    private function actualizarLogProcesamiento($logId, $exito, $error, $metadata) {
        $db = new Database();
        
        $sql = "
            UPDATE zkteco_procesamiento_logs SET
                formato_detectado = ?,
                registros_leidos = ?,
                registros_procesados = ?,
                errores_count = ?,
                exito = ?,
                errores = ?,
                metadata = ?
            WHERE id = ?
        ";
        
        // Asegurar que metadata sea un array para evitar errores si es null
        $metadata = $metadata ?? [];
        
        $formatoDetectado = $metadata['formato_detectado']['formato'] ?? null;
        $registrosLeidos = $metadata['estadisticas_parser']['registros_leidos'] ?? 0;
        $registrosProcesados = $metadata['estadisticas_parser']['registros_procesados'] ?? 0;
        $erroresCount = count($metadata['estadisticas_parser']['errores'] ?? []);
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([
            $formatoDetectado,
            $registrosLeidos,
            $registrosProcesados,
            $erroresCount,
            $exito ? 1 : 0,
            json_encode($metadata['estadisticas_parser']['errores'] ?? []),
            json_encode($metadata),
            $logId
        ]);
    }
    
    /**
     * Carga mapeo de empleados desde la base de datos
     */
    private function cargarMapeoEmpleados($parser) {
        $db = new Database();
        
        $sql = "
            SELECT zkteo_id, empleado_id 
            FROM zkteo_empleado_mapeo
        ";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
        $mapeos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $parser->setMapeoEmpleados($mapeos);
    }
    
    /**
     * Guarda formato detectado para futuros usos
     */
    private function guardarFormatoDetectado($formatoInfo) {
        $db = new Database();
        
        // Verificar si ya existe este formato para el mismo dispositivo
        $sql = "
            SELECT id, veces_usado 
            FROM zkteco_formatos 
            WHERE dispositivo = 'importacion_manual' AND formato = ? AND conteo_campos = ?
        ";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([
            $formatoInfo['formato'],
            $formatoInfo['conteo_campos']
        ]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            // Actualizar uso existente
            $sql = "
                UPDATE zkteco_formatos SET
                    veces_usado = veces_usado + 1,
                    confianza = ?,
                    mapa_tipos = ?,
                    ultimo_uso = NOW()
                WHERE id = ?
            ";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([
                $formatoInfo['confianza'],
                json_encode($formatoInfo['mapa_tipos']),
                $existente['id']
            ]);
        } else {
            // Insertar nuevo formato
            $sql = "
                INSERT INTO zkteco_formatos (
                    dispositivo, formato, conteo_campos, confianza, mapa_tipos
                ) VALUES ('importacion_manual', ?, ?, ?, ?)
            ";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([
                $formatoInfo['formato'],
                $formatoInfo['conteo_campos'],
                $formatoInfo['confianza'],
                json_encode($formatoInfo['mapa_tipos'])
            ]);
        }
    }
    
    /**
     * Convierte formato detectado a texto legible
     */
    private function formatoDetectadoLegible($formatoInfo) {
        $formatos = [
            'simple' => 'Simple (3-4 campos)',
            'estandar' => 'Estándar ZKTeco (6 campos)',
            'extendido' => 'Extendido (8+ campos)',
            'desconocido' => 'Desconocido'
        ];
        
        $formato = $formatos[$formatoInfo['formato']] ?? 'Desconocido';
        return $formato . ' (' . $formatoInfo['conteo_campos'] . ' campos)';
    }
    
    /**
     * Formatea bytes a texto legible
     */
    private function formatearBytes($bytes) {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($unidades) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $unidades[$pow];
    }
    
    /**
     * Formatea período procesado
     */
    private function formatearPeriodo($estadisticas) {
        if (empty($estadisticas['fecha_inicio']) || empty($estadisticas['fecha_fin'])) {
            return 'No disponible';
        }
        
        $inicio = date('d/m/Y', strtotime($estadisticas['fecha_inicio']));
        $fin = date('d/m/Y', strtotime($estadisticas['fecha_fin']));
        
        return $inicio . ' - ' . $fin;
    }
    
    /**
     * Obtiene historial reciente de procesamientos
     */
    public function getRecentHistory()
    {
        $this->requireAuth();
        
        // Limpiar cualquier salida previa
        if (ob_get_length()) ob_clean();
        
        try {
            $db = new Database();
            
            $sql = "
                SELECT 
                    archivo_nombre,
                    formato_detectado,
                    registros_leidos,
                    registros_procesados,
                    errores_count as errores,
                    exito,
                    creado_at,
                    (SELECT COUNT(*) FROM asistencia WHERE DATE(created_at) = CURDATE()) as registros_hoy
                FROM zkteco_procesamiento_logs 
                ORDER BY creado_at DESC 
                LIMIT 10
            ";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute();
            $historial = $stmt->fetchAll();
            
            // Calcular registros insertados aproximados
            foreach ($historial as &$registro) {
                $registro['registros_insertados'] = max(0, $registro['registros_procesados'] - $registro['errores']);
                $registro['procesado_hace'] = $this->tiempoTranscurrido($registro['creado_at']);
            }
            
            $this->jsonResponse($historial);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Error al obtener historial: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Calcula tiempo transcurrido en formato legible
     */
    private function tiempoTranscurrido($fecha) {
        $timestamp = strtotime($fecha);
        $ahora = time();
        $diferencia = $ahora - $timestamp;
        
        if ($diferencia < 60) {
            return 'hace ' . $diferencia . ' segundos';
        } elseif ($diferencia < 3600) {
            return 'hace ' . floor($diferencia / 60) . ' minutos';
        } elseif ($diferencia < 86400) {
            return 'hace ' . floor($diferencia / 3600) . ' horas';
        } elseif ($diferencia < 604800) {
            return 'hace ' . floor($diferencia / 86400) . ' días';
        } else {
            return date('d/m/Y', $timestamp);
        }
    }
}