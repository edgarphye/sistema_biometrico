<?php
require_once __DIR__ . '/ZKTecoFormatDetector.php';

/**
 * Mapeador Dinámico ZKTeco
 * Convierte datos de cualquier formato ZKTeco a estructura estándar
 */
class ZKTecoMapper {
    
    private $formatDetector;
    private $formatoInfo;
    
    // Mapeo estándar ZKTeco-ID a empleado_id
    private $mapeoEstablecido = [
        1 => 1234, 2 => 5678, 3 => 9012, 4 => 3456, 5 => 7890,
        15 => 1111, 21 => 2222, 26 => 3333, 28 => 4444, 31 => 5555,
        32 => 6666, 36 => 7777, 37 => 8888, 39 => 9999, 46 => 1010,
        54 => 1212, 55 => 1313, 57 => 1414, 59 => 1515, 62 => 1616
    ];
    
    // Descripciones de métodos de verificación
    private $descripcionesVerificacion = [
        0 => 'Huella Dactilar',
        1 => 'Método Alternativo',
        4 => 'Tarjeta/RFID',
        5 => 'PIN/Contraseña'
    ];
    
    public function __construct() {
        $this->formatDetector = new ZKTecoFormatDetector();
    }
    
    /**
     * Inicializa el mapeador con un archivo específico
     */
    public function inicializar($filePath) {
        $this->formatoInfo = $this->formatDetector->detectarFormato($filePath);
        
        $validacion = $this->formatDetector->validarFormato($this->formatoInfo);
        if (!$validacion['valido']) {
            throw new Exception("Formato no válido: " . $validacion['razon']);
        }
        
        return $this->formatoInfo;
    }
    
    /**
     * Mapea una línea del archivo a estructura estándar
     */
    public function mapearLinea($linea, $numeroLinea = null) {
        if (!$this->formatoInfo) {
            throw new Exception("Mapper no inicializado. Llamar a inicializar() primero.");
        }
        
        $campos = explode("\t", trim($linea));
        $formato = $this->formatoInfo['formato'];
        $mapaTipos = $this->formatoInfo['mapa_tipos'];
        
        $registroEstandar = $this->crearRegistroEstandar();
        
        try {
            switch ($formato) {
                case ZKTecoFormatDetector::FORMATO_SIMPLE:
                    $registroEstandar = $this->mapearFormatoSimple($campos, $mapaTipos, $registroEstandar);
                    break;
                    
                case ZKTecoFormatDetector::FORMATO_ESTANDAR:
                    $registroEstandar = $this->mapearFormatoEstandar($campos, $mapaTipos, $registroEstandar);
                    break;
                    
                case ZKTecoFormatDetector::FORMATO_EXTENDIDO:
                    $registroEstandar = $this->mapearFormatoExtendido($campos, $mapaTipos, $registroEstandar);
                    break;
                    
                default:
                    throw new Exception("Formato no implementado: $formato");
            }
            
            // Validaciones comunes
            $this->validarRegistroEstandar($registroEstandar);
            
            // Mapeo de ZK-ID a empleado_id
            $registroEstandar['empleado_id'] = $this->mapearEmpleadoID($registroEstandar['zk_empleado_id']);
            
            // Enriquecer metadata
            $registroEstandar['metadata'] = $this->crearMetadata($linea, $numeroLinea, $campos, $formato);
            
        } catch (Exception $e) {
            $registroEstandar['error'] = "Línea " . ($numeroLinea ?? 'N/A') . ": " . $e->getMessage();
        }
        
        return $registroEstandar;
    }
    
    /**
     * Crea estructura estándar vacía
     */
    private function crearRegistroEstandar() {
        return [
            'zk_empleado_id' => null,
            'empleado_id' => null,
            'fecha' => null,
            'hora' => null,
            'datetime' => null,
            'accion' => 0,
            'verificacion' => null,
            'resultado' => 1,
            'dispositivo_id' => null,
            'tipo_registro' => 'desconocido',
            'datos_extendidos' => [],
            'metadata' => [],
            'error' => null,
            'linea_procesada' => false
        ];
    }
    
    /**
     * Mapea formato simple (3-4 campos)
     */
    private function mapearFormatoSimple($campos, $mapaTipos, $registro) {
        // Asignar campos basados en posición detectada
        foreach ($campos as $pos => $valor) {
            $tipo = $mapaTipos[$pos] ?? ZKTecoFormatDetector::TIPO_DESCONOCIDO;
            
            switch ($tipo) {
                case ZKTecoFormatDetector::TIPO_EMPLEADO_ID:
                    $registro['zk_empleado_id'] = (int)$valor;
                    break;
                    
                case ZKTecoFormatDetector::TIPO_FECHA:
                    $registro['fecha'] = $valor;
                    break;
                    
                case ZKTecoFormatDetector::TIPO_HORA:
                    $registro['hora'] = $valor;
                    break;
                    
                case ZKTecoFormatDetector::TIPO_DATETIME:
                    $this->procesarDateTime($valor, $registro);
                    break;
                    
                case ZKTecoFormatDetector::TIPO_ACCION:
                    $registro['accion'] = (int)$valor;
                    break;
                    
                case ZKTecoFormatDetector::TIPO_VERIFICACION:
                    $registro['verificacion'] = (int)$valor;
                    break;
                    
                case ZKTecoFormatDetector::TIPO_RESULTADO:
                    $registro['resultado'] = (int)$valor;
                    break;
                    
                case ZKTecoFormatDetector::TIPO_DISPOSITIVO:
                    $registro['dispositivo_id'] = (int)$valor;
                    break;
                    
                default:
                    $registro['datos_extendidos'][] = ['posicion' => $pos, 'valor' => $valor, 'tipo' => $tipo];
                    break;
            }
        }
        
        // Procesar datetime si se tiene fecha y hora separadas
        if ($registro['fecha'] && $registro['hora'] && !$registro['datetime']) {
            $registro['datetime'] = $registro['fecha'] . ' ' . $registro['hora'];
        }
        
        $registro['tipo_registro'] = 'simple';
        $registro['linea_procesada'] = true;
        
        return $registro;
    }
    
    /**
     * Mapea formato estándar ZKTeco (6 campos)
     */
    private function mapearFormatoEstandar($campos, $mapaTipos, $registro) {
        // Formato estándar: ID, DATETIME, TYPE, VERIFY, RESULT, DEVICE
        $registro['zk_empleado_id'] = (int)($campos[0] ?? 0);
        $this->procesarDateTime($campos[1] ?? '', $registro);
        
        // Type (generalmente siempre es 1)
        $registro['tipo_evento'] = (int)($campos[2] ?? 1);
        
        // Método de verificación
        $registro['verificacion'] = (int)($campos[3] ?? 0);
        
        // Resultado (0=fallo, 1=éxito)
        $registro['resultado'] = (int)($campos[4] ?? 1);
        
        // ID del dispositivo
        $registro['dispositivo_id'] = (int)($campos[5] ?? 0);
        
        // Datos extendidos si hay más campos
        for ($i = 6; $i < count($campos); $i++) {
            $registro['datos_extendidos'][] = [
                'posicion' => $i,
                'valor' => $campos[$i],
                'descripcion' => 'Campo extendido'
            ];
        }
        
        $registro['tipo_registro'] = 'estandar';
        $registro['linea_procesada'] = true;
        
        return $registro;
    }
    
    /**
     * Mapea formato extendido (8+ campos)
     */
    private function mapearFormatoExtendido($campos, $mapaTipos, $registro) {
        // Similar al estándar pero con más campos
        $registro = $this->mapearFormatoEstandar($campos, $mapaTipos, $registro);
        
        // Procesar campos extendidos específicos
        if (count($campos) >= 7) {
            $registro['work_code'] = (int)($campos[6] ?? 0);
        }
        
        if (count($campos) >= 8) {
            $registro['serial_dispositivo'] = $campos[7] ?? '';
        }
        
        $registro['tipo_registro'] = 'extendido';
        
        return $registro;
    }
    
    /**
     * Procesa un campo datetime y separa fecha y hora
     */
    private function procesarDateTime($datetime, &$registro) {
        if (empty($datetime)) {
            return;
        }
        
        // Formato: YYYY-MM-DD HH:MM:SS
        if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:\d{2})$/', $datetime, $matches)) {
            $registro['fecha'] = $matches[1];
            $registro['hora'] = $matches[2];
            $registro['datetime'] = $datetime;
            return;
        }
        
        // Formato alternativo: YYYY/MM/DD HH:MM:SS
        if (preg_match('/^(\d{4}\/\d{2}\/\d{2}) (\d{2}:\d{2}:\d{2})$/', $datetime, $matches)) {
            $registro['fecha'] = str_replace('/', '-', $matches[1]);
            $registro['hora'] = $matches[2];
            $registro['datetime'] = str_replace('/', '-', $datetime);
            return;
        }
        
        // Si no se puede parsear, guardar como está
        $registro['datetime'] = $datetime;
    }
    
    /**
     * Valida que el registro estándar sea consistente
     */
    private function validarRegistroEstandar(&$registro) {
        $errores = [];
        
        // Validar empleado_id
        if (!$registro['zk_empleado_id'] || $registro['zk_empleado_id'] < 1) {
            $errores[] = 'ID de empleado inválido';
        }
        
        // Validar fecha
        if ($registro['fecha'] && !strtotime($registro['fecha'])) {
            $errores[] = 'Fecha inválida: ' . $registro['fecha'];
        }
        
        // Validar hora
        if ($registro['hora'] && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $registro['hora'])) {
            $errores[] = 'Hora inválida: ' . $registro['hora'];
        }
        
        // Validar acción
        if (!in_array($registro['accion'], [0, 1])) {
            $registro['accion'] = 0; // Default a entrada
        }
        
        if (!empty($errores)) {
            $registro['error'] = implode(', ', $errores);
        }
    }
    
    /**
     * Mapea ZK-ID a empleado_id usando tabla de mapeo o el mismo ID
     */
    private function mapearEmpleadoID($zkID) {
        // Primero intentar con el mapeo establecido
        if (isset($this->mapeoEstablecido[$zkID])) {
            return $this->mapeoEstablecido[$zkID];
        }
        
        // Si no hay mapeo, buscar en la base de datos
        $empleadoMapeado = $this->buscarMapeoEnBD($zkID);
        if ($empleadoMapeado) {
            return $empleadoMapeado;
        }
        
        // Si no se encuentra, usar el mismo ID
        return $zkID;
    }
    
    /**
     * Busca mapeo en la base de datos
     */
    private function buscarMapeoEnBD($zkID) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = new Database();
            
            $stmt = $db->getConnection()->prepare("
                SELECT empleado_id FROM zkteo_empleado_mapeo WHERE zkteo_id = ?
            ");
            $stmt->execute([$zkID]);
            $result = $stmt->fetch();
            
            return $result ? $result['empleado_id'] : null;
            
        } catch (Exception $e) {
            // Si hay error en BD, retornar null
            return null;
        }
    }
    
    /**
     * Crea metadata completa del registro
     */
    private function crearMetadata($lineaOriginal, $numeroLinea, $campos, $formato) {
        return [
            'linea_original' => $lineaOriginal,
            'numero_linea' => $numeroLinea,
            'formato_detectado' => $formato,
            'total_campos' => count($campos),
            'procesado_timestamp' => date('Y-m-d H:i:s'),
            'descripcion_verificacion' => $this->descripcionesVerificacion[$this->formatoInfo['mapa_tipos'][3] ?? 0] ?? 'Desconocido',
            'campos_originales' => $campos,
            'confianza_deteccion' => $this->formatoInfo['confianza']
        ];
    }
    
    /**
     * Obtiene información del formato detectado
     */
    public function getFormatoInfo() {
        return $this->formatoInfo;
    }
    
    /**
     * Aplica reglas de negocio ZKTeco
     */
    public function aplicarReglasZKTeco($registro) {
        // Regla 1: Sábados y domingos omitir
        if ($registro['fecha']) {
            $diaSemana = date('w', strtotime($registro['fecha']));
            if ($diaSemana == 0 || $diaSemana == 6) {
                $registro['omitir'] = true;
                $registro['motivo_omision'] = 'Fin de semana';
                return $registro;
            }
        }
        
        // Regla 2: Solo procesar registros exitosos
        if ($registro['resultado'] == 0) {
            $registro['omitir'] = true;
            $registro['motivo_omision'] = 'Verificación fallida';
            return $registro;
        }
        
        // Regla 3: Horario nocturno (00:00-04:00) asignar a día anterior
        if ($registro['hora'] && $registro['hora'] >= '00:00:00' && $registro['hora'] <= '04:00:00') {
            $fechaAnterior = date('Y-m-d', strtotime($registro['fecha'] . ' -1 day'));
            $registro['fecha_original'] = $registro['fecha'];
            $registro['fecha'] = $fechaAnterior;
            $registro['ajuste_nocturno'] = true;
        }
        
        $registro['omitir'] = false;
        return $registro;
    }
}