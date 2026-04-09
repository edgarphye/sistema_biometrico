<?php
require_once __DIR__ . '/BaseController.php';

class LogsController extends BaseController
{
    public function __construct()
    {
        $this->requireAuth();
    }
    public function index()
    {
        include __DIR__ . '/../views/logs/index.php';
    }
    
    public function leer()
    {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $tipo = $_GET['tipo'] ?? '';
        
        $logs = [];
        
        // Leer logs de JavaScript
        $jsLogFile = __DIR__ . '/../../logs/js_errors_' . $fecha . '.log';
        if (file_exists($jsLogFile)) {
            $contenido = file_get_contents($jsLogFile);
            $bloques = explode('------------------------------------------------------------------', $contenido);
            
            foreach ($bloques as $bloque) {
                $bloque = trim($bloque);
                if (empty($bloque)) continue;
                
                $lineasBloque = explode("\n", $bloque);
                if (count($lineasBloque) < 2) continue;
                
                preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[(\w+)\] (.+)/', $lineasBloque[0], $matches);
                
                if ($matches) {
                    $logTipo = $matches[2];
                    if ($tipo && strtoupper($tipo) !== $logTipo) continue;
                    
                    $log = [
                        'fuente' => 'JavaScript',
                        'fecha' => $matches[1],
                        'tipo' => $logTipo,
                        'mensaje' => $matches[3],
                        'url' => '',
                        'linea' => '',
                        'stack' => ''
                    ];
                    
                    foreach ($lineasBloque as $linea) {
                        if (strpos($linea, 'URL:') !== false) {
                            $log['url'] = trim(str_replace('URL:', '', $linea));
                        }
                        if (strpos($linea, 'Line:') !== false) {
                            $log['linea'] = trim(str_replace('Line:', '', $linea));
                        }
                    }
                    
                    if (count($lineasBloque) > 3) {
                        $log['stack'] = implode("\n", array_slice($lineasBloque, 3));
                    }
                    
                    $logs[] = $log;
                }
            }
        }
        
        // Leer logs de PHP
        $phpLogFile = __DIR__ . '/../../logs/php_errors_' . $fecha . '.log';
        if (file_exists($phpLogFile)) {
            $contenido = file_get_contents($phpLogFile);
            $bloques = explode('------------------------------------------------------------------', $contenido);
            
            foreach ($bloques as $bloque) {
                $bloque = trim($bloque);
                if (empty($bloque)) continue;
                
                $lineasBloque = explode("\n", $bloque);
                if (count($lineasBloque) < 2) continue;
                
                preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[(\w+)\] (.+)/', $lineasBloque[0], $matches);
                
                if ($matches) {
                    $logTipo = $matches[2];
                    if ($tipo && strtoupper($tipo) !== $logTipo) continue;
                    
                    $log = [
                        'fuente' => 'PHP',
                        'fecha' => $matches[1],
                        'tipo' => $logTipo,
                        'mensaje' => $matches[3],
                        'url' => '',
                        'linea' => '',
                        'stack' => ''
                    ];
                    
                    foreach ($lineasBloque as $linea) {
                        if (strpos($linea, 'URI:') !== false) {
                            $log['url'] = trim(str_replace('URI:', '', $linea));
                        }
                    }
                    
                    if (count($lineasBloque) > 3) {
                        $log['stack'] = implode("\n", array_slice($lineasBloque, 3));
                    }
                    
                    $logs[] = $log;
                }
            }
        }
        
        // Ordenar por fecha descendente
        usort($logs, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
        
        $this->jsonResponse(['success' => true, 'logs' => $logs]);
    }
    
    public function analizarAI()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $fecha = $data['fecha'] ?? date('Y-m-d');
        
        $errores = [];
        
        // Analizar logs de JavaScript
        $jsLogFile = __DIR__ . '/../../logs/js_errors_' . $fecha . '.log';
        if (file_exists($jsLogFile)) {
            $contenido = file_get_contents($jsLogFile);
            $erroresJS = $this->analizarErroresJS($contenido);
            $errores = array_merge($errores, $erroresJS);
        }
        
        // Analizar logs de PHP
        $phpLogFile = __DIR__ . '/../../logs/php_errors_' . $fecha . '.log';
        if (file_exists($phpLogFile)) {
            $contenido = file_get_contents($phpLogFile);
            $erroresPHP = $this->analizarErroresPHP($contenido);
            $errores = array_merge($errores, $erroresPHP);
        }
        
        $resumen = "Se encontraron " . count($errores) . " errores/tipos de errores en los logs del día $fecha.";
        
        $this->jsonResponse([
            'success' => true,
            'resumen' => $resumen,
            'errores' => $errores
        ]);
    }
    
    private function analizarErroresJS($contenido)
    {
        $errores = [];
        
        // Error: DataTable
        if (strpos($contenido, 'DataTable') !== false) {
            $errores[] = [
                'mensaje' => 'Error con DataTables - tabla no encontrada o mal configurada',
                'causa' => 'La tabla HTML no tiene las columnas correctas o falta inicializar DataTables',
                'solucion' => 'Verificar que la tabla tenga el elemento <thead> y <tbody> correctos, y que DataTables esté cargado',
                'archivo' => null,
                'linea' => null,
                'fix' => null,
                'fix_disponible' => false
            ];
        }
        
        // Error: Cannot read property
        if (preg_match_all('/Cannot read properties? of (null|undefined) \(setting ([^)]+)\)/', $contenido, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $propiedad = $match[2];
                $errores[] = [
                    'mensaje' => "Cannot read property '$propiedad' of null/undefined",
                    'causa' => "Se intenta acceder a una propiedad de un elemento que no existe en el DOM",
                    'solucion' => 'Agregar verificación de null antes de acceder a la propiedad',
                    'archivo' => null,
                    'linea' => null,
                    'fix' => "if (element && element.$propiedad) { ... }",
                    'fix_disponible' => true
                ];
            }
        }
        
        // Error: 404 Not Found
        if (preg_match_all('/404.*\/([^\s]+)/', $contenido, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $recurso = $match[1];
                $errores[] = [
                    'mensaje' => "Recurso no encontrado: $recurso",
                    'causa' => 'El archivo o ruta solicitada no existe en el servidor',
                    'solucion' => 'Verificar que la ruta exista y que el archivo esté correctamente referenciado',
                    'archivo' => null,
                    'linea' => null,
                    'fix' => null,
                    'fix_disponible' => false
                ];
            }
        }
        
        // Error: 403 Forbidden
        if (strpos($contenido, '403') !== false) {
            $errores[] = [
                'mensaje' => 'Error 403 Forbidden - Acceso denegado',
                'causa' => 'Falta token CSRF o la sesión ha expirado',
                'solucion' => 'Agregar token CSRF a las peticiones AJAX y verificar sesión activa',
                'archivo' => null,
                'linea' => null,
                'fix' => "headers: { 'X-CSRF-TOKEN': csrfToken }",
                'fix_disponible' => true
            ];
        }
        
        // Error: SyntaxError JSON
        if (strpos($contenido, 'Unexpected token') !== false && strpos($contenido, 'JSON') !== false) {
            $errores[] = [
                'mensaje' => 'Error al parsear JSON - La respuesta del servidor no es JSON válido',
                'causa' => 'El servidor devuelve HTML o texto plano en lugar de JSON (ej: página de error 500)',
                'solucion' => 'Verificar la respuesta del servidor y corregir el error del lado del servidor',
                'archivo' => null,
                'linea' => null,
                'fix' => null,
                'fix_disponible' => false
            ];
        }
        
        return $errores;
    }
    
    private function analizarErroresPHP($contenido)
    {
        $errores = [];
        
        // Error: SQLSTATE
        if (preg_match_all('/SQLSTATE\[(\w+)\]: (.+)/', $contenido, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $codigo = $match[1];
                $mensaje = $match[2];
                
                if (strpos($mensaje, 'Unknown column') !== false) {
                    preg_match('/Unknown column \'([^\']+)\'/', $mensaje, $colMatch);
                    $columna = $colMatch[1] ?? 'desconocida';
                    
                    $errores[] = [
                        'mensaje' => "Error de SQL: Columna desconocida '$columna'",
                        'causa' => 'La columna no existe en la tabla de la base de datos',
                        'solucion' => 'Agregar la columna a la tabla o corregir el nombre de la columna en la consulta',
                        'archivo' => null,
                        'linea' => null,
                        'fix' => "ALTER TABLE nombre_tabla ADD COLUMN $columna tipo_dato;",
                        'fix_disponible' => true
                    ];
                } elseif (strpos($mensaje, 'Duplicate entry') !== false) {
                    $errores[] = [
                        'mensaje' => "Error de SQL: Entrada duplicada",
                        'causa' => 'Se intenta insertar un registro que ya existe con la misma clave única',
                        'solucion' => 'Verificar si el registro ya existe o cambiar la lógica de inserción',
                        'archivo' => null,
                        'linea' => null,
                        'fix' => null,
                        'fix_disponible' => false
                    ];
                } else {
                    $errores[] = [
                        'mensaje' => "Error de SQL ($codigo): " . substr($mensaje, 0, 100),
                        'causa' => 'Error en la consulta SQL',
                        'solucion' => 'Revisar la consulta SQL y la estructura de la base de datos',
                        'archivo' => null,
                        'linea' => null,
                        'fix' => null,
                        'fix_disponible' => false
                    ];
                }
            }
        }
        
        // Error: Class not found
        if (preg_match_all("/Class '(\w+)' not found/", $contenido, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $clase = $match[1];
                $errores[] = [
                    'mensaje' => "Clase no encontrada: $clase",
                    'causa' => 'Falta hacer require o la clase no existe',
                    'solucion' => 'Verificar que el archivo de la clase esté incluido',
                    'archivo' => null,
                    'linea' => null,
                    'fix' => "require_once 'models/$clase.php';",
                    'fix_disponible' => true
                ];
            }
        }
        
        // Error: Call to undefined function
        if (preg_match_all('/Call to undefined function (\w+)/', $contenido, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $funcion = $match[1];
                $errores[] = [
                    'mensaje' => "Función no definida: $funcion()",
                    'causa' => 'La función no existe o no está incluida',
                    'solucion' => 'Verificar que la función esté definida o incluida',
                    'archivo' => null,
                    'linea' => null,
                    'fix' => null,
                    'fix_disponible' => false
                ];
            }
        }
        
        // Error: Call to a member function
        if (preg_match_all('/Call to a member function (\w+)\(\) on (null|bool)/', $contenido, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $metodo = $match[1];
                $errores[] = [
                    'mensaje' => "Llamando a método $metodo() en objeto null",
                    'causa' => 'El objeto es null antes de llamar al método',
                    'solucion' => 'Verificar que el objeto no sea null antes de usar',
                    'archivo' => null,
                    'linea' => null,
                    'fix' => "if (\$objeto && method_exists(\$objeto, '$metodo')) { \$objeto->$metodo(); }",
                    'fix_disponible' => true
                ];
            }
        }
        
        return $errores;
    }
    
    public function aplicarFix()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $archivo = $data['archivo'] ?? '';
        $fix = $data['fix'] ?? '';
        $linea = $data['linea'] ?? 0;
        
        if (!$archivo || !$fix) {
            $this->jsonResponse(['success' => false, 'error' => 'Faltan datos para aplicar el fix']);
            return;
        }
        
        $rutaArchivo = __DIR__ . '/../../' . $archivo;
        
        if (!file_exists($rutaArchivo)) {
            $this->jsonResponse(['success' => false, 'error' => 'El archivo no existe']);
            return;
        }
        
        // Crear backup
        $backupFile = $rutaArchivo . '.backup.' . date('Y-m-d-H-i-s');
        copy($rutaArchivo, $backupFile);
        
        file_put_contents($rutaArchivo . '.fix_log.txt', date('Y-m-d H:i:s') . " - Fix aplicado: " . $fix . "\n", FILE_APPEND);
        
        $this->jsonResponse(['success' => true, 'backup' => $backupFile]);
    }
}
