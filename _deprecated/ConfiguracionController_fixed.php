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
        'tipos_verificacion_permitidos' => null,
        'validar_campos_requeridos' => true,
        'crear_empleados_faltantes' => false,
        'idioma' => 'es'
    ];
    
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
        // Verificar sesión antes de requireAuth
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado - Sesión requerida'], 401);
            exit;
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
                    'periodo_procesado' => $this->formatoPeriodo(isset($resultadoParser['estadisticas']) ? $resultadoParser['estadisticas'] : []),
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
    
    // Métodos privados helpers para procesamiento
    private function iniciarLogProcesamiento($file) {
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO zkteco_procesamiento_logs 
            (nombre_archivo, tamano, tipo_operacion, estado, fecha_inicio)
            VALUES (?, ?, 'upload_zkteco', 'iniciado', NOW())
        ");
        
        $stmt->execute([
            $file['name'],
            $file['size']
        ]);
        
        return $conn->lastInsertId();
    }
    
    private function validarArchivo($file) {
        $allowedExtensions = ['dat', 'txt'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            return [
                'valido' => false,
                'error' => 'Tipo de archivo no permitido. Solo se aceptan archivos .dat y .txt'
            ];
        }
        
        if ($file['size'] > 50 * 1024 * 1024) { // 50MB
            return [
                'valido' => false,
                'error' => 'El archivo es demasiado grande. Máximo permitido: 50MB'
            ];
        }
        
        return ['valido' => true];
    }
    
    private function cargarMapeoEmpleados($parser) {
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT empleado_id, zk_empleado_id 
            FROM zk_empleado_mapeo 
            WHERE activo = 1
        ");
        
        $stmt->execute();
        $mapeo = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mapeo[$row['zk_empleado_id']] = $row['empleado_id'];
        }
        
        $parser->setMapeoEmpleados($mapeo);
    }
    
    private function guardarFormatoDetectado($formatoDetectado) {
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO zkteco_formatos 
            (nombre_formato, campos_formato, confianza, fecha_deteccion)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            confianza = VALUES(confianza),
            fecha_deteccion = VALUES(fecha_deteccion)
        ");
        
        $stmt->execute([
            $formatoDetectado['nombre'],
            json_encode($formatoDetectado['campos']),
            $formatoDetectado['confianza']
        ]);
    }
    
    private function actualizarLogProcesamiento($logId, $exito, $error = null, $metadata = null) {
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            UPDATE zkteco_procesamiento_logs 
            SET estado = ?, error_detalle = ?, metadata = ?, fecha_fin = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $exito ? 'completado' : 'error',
            $error,
            json_encode($metadata),
            $logId
        ]);
    }
    
    private function formatearBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function formatoDetectadoLegible($formato) {
        if (!$formato) {
            return 'No detectado';
        }
        
        $nombres = [
            'zkteco_6_campos' => 'Estándar ZKTeco (6 campos)',
            'zkteco_7_campos' => 'Extendido ZKTeco (7 campos)',
            'zkteco_8_campos' => 'Completo ZKTeco (8 campos)',
            'generico' => 'Formato genérico'
        ];
        
        return $nombres[$formato['nombre']] ?? $formato['nombre'];
    }
    
    private function formatearPeriodo($estadisticas) {
        if (empty($estadisticas['fecha_inicio']) || empty($estadisticas['fecha_fin'])) {
            return 'No disponible';
        }
        
        $inicio = new DateTime($estadisticas['fecha_inicio']);
        $fin = new DateTime($estadisticas['fecha_fin']);
        
        return $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y');
    }
    
    /**
     * Obtiene historial de procesamientos para AJAX
     */
    public function getRecentHistory() {
        $this->requireAuth();
        
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT id, nombre_archivo, tamano, estado, fecha_inicio, fecha_fin,
                   TIMESTAMPDIFF(SECOND, fecha_inicio, COALESCE(fecha_fin, fecha_inicio)) as duracion_segundos
            FROM zkteco_procesamiento_logs 
            ORDER BY fecha_inicio DESC 
            LIMIT 10
        ");
        
        $stmt->execute();
        $procesamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear para frontend
        foreach ($procesamientos as &$procesamiento) {
            $procesamiento['tamano_formateado'] = $this->formatearBytes($procesamiento['tamano']);
            $procesamiento['duracion_formateada'] = $procesamiento['duracion_segundos'] . ' segundos';
            $procesamiento['estado_clase'] = $procesamiento['estado'] === 'completado' ? 'success' : 
                                     ($procesamiento['estado'] === 'error' ? 'danger' : 'warning');
        }
        
        $this->jsonResponse([
            'success' => true,
            'procesamientos' => $procesamientos
        ]);
    }
}
?>