<?php
require_once __DIR__ . '/../models/Database.php';

class RegistroJustificacionService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getCamposPorTipo($tipo_justificacion) {
        $campos = [
            'Retardo menor' => [
                'tabla' => 'retardos',
                'tipo_justificacion_id' => 15,
                'campos' => [
                    ['name' => 'fecha', 'label' => 'Fecha', 'type' => 'date', 'required' => true],
                    ['name' => 'hora_entrada', 'label' => 'Hora de Entrada', 'type' => 'time', 'required' => false],
                    ['name' => 'minutos_retardo', 'label' => 'Minutos de Retardo', 'type' => 'number', 'required' => true],
                    ['name' => 'motivo_justificacion', 'label' => 'Motivo', 'type' => 'textarea', 'required' => true],
                    ['name' => 'evidencia', 'label' => 'Evidencia (URL)', 'type' => 'file', 'required' => false]
                ]
            ],
            'Retardo mayor' => [
                'tabla' => 'retardos',
                'tipo_justificacion_id' => 16,
                'campos' => [
                    ['name' => 'fecha', 'label' => 'Fecha', 'type' => 'date', 'required' => true],
                    ['name' => 'hora_entrada', 'label' => 'Hora de Entrada', 'type' => 'time', 'required' => false],
                    ['name' => 'minutos_retardo', 'label' => 'Minutos de Retardo', 'type' => 'number', 'required' => true],
                    ['name' => 'motivo_justificacion', 'label' => 'Motivo', 'type' => 'textarea', 'required' => true],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ],
            'Comisión de entrada' => [
                'tabla' => 'comisiones',
                'tipo_justificacion_id' => 17,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha de Comisión', 'type' => 'date', 'required' => true],
                    ['name' => 'hora_salida', 'label' => 'Hora de Salida', 'type' => 'time', 'required' => false],
                    ['name' => 'descripcion', 'label' => 'Descripción de la Comisión', 'type' => 'textarea', 'required' => true],
                    ['name' => 'lugar', 'label' => 'Lugar', 'type' => 'text', 'required' => false],
                    ['name' => 'objetivo', 'label' => 'Objetivo', 'type' => 'textarea', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ],
            'Comisión de salida' => [
                'tabla' => 'comisiones',
                'tipo_justificacion_id' => 18,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha de Comisión', 'type' => 'date', 'required' => true],
                    ['name' => 'hora_entrada', 'label' => 'Hora de Entrada', 'type' => 'time', 'required' => false],
                    ['name' => 'descripcion', 'label' => 'Descripción de la Comisión', 'type' => 'textarea', 'required' => true],
                    ['name' => 'lugar', 'label' => 'Lugar', 'type' => 'text', 'required' => false],
                    ['name' => 'objetivo', 'label' => 'Objetivo', 'type' => 'textarea', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ],
            'Comisión todo el día' => [
                'tabla' => 'comisiones',
                'tipo_justificacion_id' => 19,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha Inicio', 'type' => 'date', 'required' => true],
                    ['name' => 'fecha_fin', 'label' => 'Fecha Fin', 'type' => 'date', 'required' => true],
                    ['name' => 'descripcion', 'label' => 'Descripción de la Comisión', 'type' => 'textarea', 'required' => true],
                    ['name' => 'lugar', 'label' => 'Lugar', 'type' => 'text', 'required' => false],
                    ['name' => 'objetivo', 'label' => 'Objetivo', 'type' => 'textarea', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ],
            'Día económico' => [
                'tabla' => 'dias_economicos',
                'tipo_justificacion_id' => 20,
                'campos' => [
                    ['name' => 'fecha', 'label' => 'Fecha Solicitada', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Motivo', 'type' => 'textarea', 'required' => true],
                    ['name' => 'modalidad', 'label' => 'Modalidad', 'type' => 'select', 'options' => ['individual' => 'Individual'], 'required' => true],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ],
            'Licencia Médica' => [
                'tabla' => 'licencias_medicas',
                'tipo_justificacion_id' => 21,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha Inicio', 'type' => 'date', 'required' => true],
                    ['name' => 'fecha_fin', 'label' => 'Fecha Fin', 'type' => 'date', 'required' => true],
                    ['name' => 'diagnostico', 'label' => 'Diagnóstico', 'type' => 'textarea', 'required' => true],
                    ['name' => 'fundamentos', 'label' => 'Fundamentos Legales', 'type' => 'textarea', 'required' => false],
                    ['name' => 'tipo_sueldo', 'label' => 'Tipo de Sueldo', 'type' => 'select', 'options' => ['full' => 'Sueldo Completo', 'half' => 'Medio Sueldo', 'none' => 'Sin Sueldo'], 'required' => true],
                    ['name' => 'folio', 'label' => 'Folio de Licencia', 'type' => 'text', 'required' => true],
                    ['name' => 'dias_otorgados', 'label' => 'Días Otorgados', 'type' => 'number', 'required' => true],
                    ['name' => 'evidencia', 'label' => 'Evidencia Médica', 'type' => 'file', 'required' => true]
                ]
            ],
            'Vacaciones' => [
                'tabla' => 'vacaciones',
                'tipo_justificacion_id' => 22,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha Inicio', 'type' => 'date', 'required' => true],
                    ['name' => 'fecha_fin', 'label' => 'Fecha Fin', 'type' => 'date', 'required' => true],
                    ['name' => 'dias_solicitados', 'label' => 'Días Solicitados', 'type' => 'number', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Motivo', 'type' => 'textarea', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ],
            'Cuidados maternos' => [
                'tabla' => 'justificaciones',
                'tipo_justificacion_id' => 23,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha Inicio', 'type' => 'date', 'required' => true],
                    ['name' => 'fecha_fin', 'label' => 'Fecha Fin', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Motivo', 'type' => 'textarea', 'required' => true],
                    ['name' => 'nombre_bebe', 'label' => 'Nombre del Bebé/Familiar', 'type' => 'text', 'required' => false],
                    ['name' => 'parentesco', 'label' => 'Parentesco', 'type' => 'select', 'options' => ['hijo' => 'Hijo/a', 'esposa' => 'Esposa', 'madre' => 'Madre'], 'required' => true],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => true]
                ]
            ],
            'Cuidados paternos' => [
                'tabla' => 'justificaciones',
                'tipo_justificacion_id' => 31,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha Inicio', 'type' => 'date', 'required' => true],
                    ['name' => 'fecha_fin', 'label' => 'Fecha Fin', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Motivo', 'type' => 'textarea', 'required' => true],
                    ['name' => 'nombre_familiar', 'label' => 'Nombre del Familiar', 'type' => 'text', 'required' => false],
                    ['name' => 'parentesco', 'label' => 'Parentesco', 'type' => 'select', 'options' => ['hijo' => 'Hijo/a', 'esposo' => 'Esposo', 'padre' => 'Padre'], 'required' => true],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => true]
                ]
            ],
            'Falta' => [
                'tabla' => 'retardos',
                'tipo_justificacion_id' => 24,
                'campos' => [
                    ['name' => 'fecha', 'label' => 'Fecha', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo_justificacion', 'label' => 'Motivo de la Falta', 'type' => 'textarea', 'required' => true],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ],
            'Constancia de Tiempo' => [
                'tabla' => 'constancias_tiempo',
                'tipo_justificacion_id' => 25,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha Inicio', 'type' => 'date', 'required' => true],
                    ['name' => 'fecha_fin', 'label' => 'Fecha Fin', 'type' => 'date', 'required' => true],
                    ['name' => 'dias_solicitados', 'label' => 'Días Solicitados', 'type' => 'number', 'required' => true],
                    ['name' => 'tipo_constancia', 'label' => 'Tipo de Constancia', 'type' => 'select', 'options' => ['medica' => 'Médica', 'legal' => 'Legal', 'oficial' => 'Oficial'], 'required' => true],
                    ['name' => 'folio_constancia', 'label' => 'Folio de Constancia', 'type' => 'text', 'required' => false],
                    ['name' => 'institucion_emisora', 'label' => 'Institución Emisora', 'type' => 'text', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Motivo', 'type' => 'textarea', 'required' => true],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => true]
                ]
            ],
            'Programa Deportivo SEP-SNTE' => [
                'tabla' => 'justificaciones',
                'tipo_justificacion_id' => 26,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha Inicio', 'type' => 'date', 'required' => true],
                    ['name' => 'fecha_fin', 'label' => 'Fecha Fin', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Nombre del Programa/Evento', 'type' => 'text', 'required' => true],
                    ['name' => 'lugar', 'label' => 'Lugar del Evento', 'type' => 'text', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Convocatoria/Oficio', 'type' => 'file', 'required' => true]
                ]
            ],
            'CLIDDA' => [
                'tabla' => 'justificaciones',
                'tipo_justificacion_id' => 27,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha Inicio', 'type' => 'date', 'required' => true],
                    ['name' => 'fecha_fin', 'label' => 'Fecha Fin', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Actividad CLIDDA', 'type' => 'text', 'required' => true],
                    ['name' => 'lugar', 'label' => 'Lugar', 'type' => 'text', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => true]
                ]
            ],
            'Estimulos y Recompensas' => [
                'tabla' => 'justificaciones',
                'tipo_justificacion_id' => 28,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Tipo de Estímulo', 'type' => 'text', 'required' => true],
                    ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'textarea', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Oficio de Reward', 'type' => 'file', 'required' => true]
                ]
            ],
            'Desalojo de Edificio' => [
                'tabla' => 'justificaciones',
                'tipo_justificacion_id' => 29,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Motivo del Desalojo', 'type' => 'text', 'required' => true],
                    ['name' => 'area_afectada', 'label' => 'Área Afectada', 'type' => 'text', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ],
            'Fumigacion' => [
                'tabla' => 'justificaciones',
                'tipo_justificacion_id' => 30,
                'campos' => [
                    ['name' => 'fecha_inicio', 'label' => 'Fecha', 'type' => 'date', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Área a Fumigar', 'type' => 'text', 'required' => true],
                    ['name' => 'hora_inicio', 'label' => 'Hora de Inicio', 'type' => 'time', 'required' => false],
                    ['name' => 'hora_fin', 'label' => 'Hora Fin', 'type' => 'time', 'required' => false],
                    ['name' => 'evidencia', 'label' => 'Evidencia', 'type' => 'file', 'required' => false]
                ]
            ]
        ];
        
        return $campos[$tipo_justificacion] ?? null;
    }
    
    public function getTodosLosTipos() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT id, nombre, descripcion, tipo_incidencia, requiere_aprobacion, requiere_documento FROM tipos_justificacion WHERE activo = 1 ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function registrarJustificacion($empleado_id, $tipo_justificacion, $datos) {
        $config = $this->getCamposPorTipo($tipo_justificacion);
        
        if (!$config) {
            return ['success' => false, 'error' => 'Tipo de justificación no válido'];
        }
        
        $tabla = $config['tabla'];
        $tipo_justificacion_id = $config['tipo_justificacion_id'];
        
        try {
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();
            
            $resultado = $this->insertarEnTabla($pdo, $tabla, $empleado_id, $tipo_justificacion_id, $datos, $config);
            
            $this->registrarEnAsistencia($pdo, $empleado_id, $datos, $tipo_justificacion_id, $tipo_justificacion);
            
            $pdo->commit();
            
            return ['success' => true, 'message' => 'Justificación registrada correctamente', 'id' => $resultado];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function insertarEnTabla($pdo, $tabla, $empleado_id, $tipo_justificacion_id, $datos, $config) {
        
        switch ($tabla) {
            case 'retardos':
                return $this->insertarRetardo($pdo, $empleado_id, $tipo_justificacion_id, $datos);
                
            case 'comisiones':
                return $this->insertarComision($pdo, $empleado_id, $tipo_justificacion_id, $datos);
                
            case 'dias_economicos':
                return $this->insertarDiaEconomico($pdo, $empleado_id, $datos);
                
            case 'licencias_medicas':
                return $this->insertarLicenciaMedica($pdo, $empleado_id, $datos);
                
            case 'vacaciones':
                return $this->insertarVacaciones($pdo, $empleado_id, $tipo_justificacion_id, $datos);
                
            case 'constancias_tiempo':
                return $this->insertarConstanciaTiempo($pdo, $empleado_id, $tipo_justificacion_id, $datos);
                
            case 'justificaciones':
                return $this->insertarJustificacionGeneral($pdo, $empleado_id, $config['campos'][0]['name'] ?? 'fecha_inicio', $datos);
                
            default:
                throw new Exception("Tabla no soportada: $tabla");
        }
    }
    
    private function insertarRetardo($pdo, $empleado_id, $tipo_justificacion_id, $datos) {
        $sql = "INSERT INTO retardos (empleado_id, fecha, hora_entrada, minutos_retardo, tipo_retraso, tipo_justificacion_id, motivo_justificacion, justificado, estado_validacion, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'pendiente', NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $fecha = $datos['fecha'] ?? date('Y-m-d');
        $hora = $datos['hora_entrada'] ?? null;
        $minutos = $datos['minutos_retardo'] ?? 0;
        $motivo = $datos['motivo_justificacion'] ?? '';
        
        // Clasificación según minutos:
        // 1-10: tolerancia, 11-20: retardo_menor, 21-30: retardo_mayor, >30: falta
        if ($minutos > 30) {
            $tipo_retraso = 'falta';
        } elseif ($minutos > 20) {
            $tipo_retraso = 'retardo_mayor';
        } elseif ($minutos > 10) {
            $tipo_retraso = 'retardo_menor';
        } else {
            $tipo_retraso = 'normal'; // tolerancia
        }
        
        $stmt->execute([$empleado_id, $fecha, $hora, $minutos, $tipo_retraso, $tipo_justificacion_id, $motivo]);
        
        return $pdo->lastInsertId();
    }
    
    private function insertarComision($pdo, $empleado_id, $tipo_justificacion_id, $datos) {
        $sql = "INSERT INTO comisiones (empleado_id, descripcion, fecha_inicio, fecha_fin, estatus, requiere_evidencia, requiere_aprobacion, created_at) 
                VALUES (?, ?, ?, ?, 'pendiente', 1, 1, NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $descripcion = $datos['descripcion'] ?? '';
        $fecha_inicio = $datos['fecha_inicio'] ?? date('Y-m-d');
        $fecha_fin = $datos['fecha_fin'] ?? $fecha_inicio;
        
        $stmt->execute([$empleado_id, $descripcion, $fecha_inicio, $fecha_fin]);
        
        return $pdo->lastInsertId();
    }
    
    private function insertarDiaEconomico($pdo, $empleado_id, $datos) {
        $sql = "INSERT INTO dias_economicos (empleado_id, fecha, motivo, estatus, solicitado_por, created_at) 
                VALUES (?, ?, ?, 'pendiente', ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $fecha = $datos['fecha'] ?? date('Y-m-d');
        $motivo = $datos['motivo'] ?? '';
        
        $stmt->execute([$empleado_id, $fecha, $motivo, $empleado_id]);
        
        return $pdo->lastInsertId();
    }
    
    private function insertarLicenciaMedica($pdo, $empleado_id, $datos) {
        $sql = "INSERT INTO licencias_medicas (empleado_id, fecha_inicio, fecha_fin, diagnostico, fundamentos, tipo_sueldo, folio, dias_otorgados, estatus, creado_por, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            $empleado_id,
            $datos['fecha_inicio'],
            $datos['fecha_fin'],
            $datos['diagnostico'] ?? '',
            $datos['fundamentos'] ?? '',
            $datos['tipo_sueldo'] ?? 'full',
            $datos['folio'] ?? '',
            $datos['dias_otorgados'] ?? 0,
            $empleado_id
        ]);
        
        return $pdo->lastInsertId();
    }
    
    private function insertarVacaciones($pdo, $empleado_id, $tipo_justificacion_id, $datos) {
        $sql = "INSERT INTO vacaciones (empleado_id, tipo_justificacion_id, fecha_inicio, fecha_fin, dias_solicitados, motivo, estatus, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $dias = isset($datos['dias_solicitados']) ? $datos['dias_solicitados'] : 
            (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) ? 
            (strtotime($datos['fecha_fin']) - strtotime($datos['fecha_inicio'])) / 86400 + 1 : 0);
        
        $stmt->execute([
            $empleado_id,
            $tipo_justificacion_id,
            $datos['fecha_inicio'],
            $datos['fecha_fin'],
            $dias,
            $datos['motivo'] ?? ''
        ]);
        
        return $pdo->lastInsertId();
    }
    
    private function insertarConstanciaTiempo($pdo, $empleado_id, $tipo_justificacion_id, $datos) {
        $sql = "INSERT INTO constancias_tiempo (empleado_id, tipo_justificacion_id, fecha_inicio, fecha_fin, dias_solicitados, tipo_constancia, folio_constancia, motivo, institucion_emisora, estatus, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            $empleado_id,
            $tipo_justificacion_id,
            $datos['fecha_inicio'],
            $datos['fecha_fin'],
            $datos['dias_solicitados'],
            $datos['tipo_constancia'],
            $datos['folio_constancia'] ?? '',
            $datos['motivo'] ?? '',
            $datos['institucion_emisora'] ?? '',
        ]);
        
        return $pdo->lastInsertId();
    }
    
    private function insertarJustificacionGeneral($pdo, $empleado_id, $campo_fecha, $datos) {
        $sql = "INSERT INTO justificaciones (empleado_id, tipo_justificacion, motivo, fecha_inicio, fecha_fin, estatus, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $tipo_justificacion = '';
        foreach ($datos as $key => $value) {
            if (strpos($key, 'tipo_') === 0 || strpos($key, 'motivo') === 0 || strpos($key, 'nombre') === 0 || 
                strpos($key, 'parentesco') === 0 || strpos($key, 'descripcion') === 0 || strpos($key, 'lugar') === 0 ||
                strpos($key, 'area_') === 0 || strpos($key, 'institucion') === 0 || strpos($key, 'folio') === 0 ||
                strpos($key, 'actividad') === 0 || strpos($key, 'estatus_') === 0 || strpos($key, 'oficio') === 0) {
                $tipo_justificacion .= $value . ' ';
            }
        }
        
        $fecha_inicio = $datos['fecha_inicio'] ?? $datos['fecha'] ?? date('Y-m-d');
        $fecha_fin = $datos['fecha_fin'] ?? $datos['fecha'] ?? $fecha_inicio;
        $motivo = $datos['motivo'] ?? $datos['descripcion'] ?? '';
        
        $stmt->execute([$empleado_id, '', trim($motivo), $fecha_inicio, $fecha_fin]);
        
        return $pdo->lastInsertId();
    }
    
    private function registrarEnAsistencia($pdo, $empleado_id, $datos, $tipo_justificacion_id, $tipo_justificacion) {
        $fecha = $datos['fecha'] ?? $datos['fecha_inicio'] ?? date('Y-m-d');
        $fecha_fin = $datos['fecha_fin'] ?? $fecha;
        
        $fechas = [];
        $current = strtotime($fecha);
        $end = strtotime($fecha_fin);
        
        while ($current <= $end) {
            $fechas[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }
        
        $tipo_asistencia = $this->mapTipoJustificacionToAsistencia($tipo_justificacion);
        
        foreach ($fechas as $f) {
            $sql = "INSERT INTO asistencia (empleado_id, fecha, tipo_asistencia, tipo_justificacion_id, estado_validacion, created_at) 
                    VALUES (?, ?, ?, ?, 'pendiente', NOW())
                    ON DUPLICATE KEY UPDATE tipo_asistencia = VALUES(tipo_asistencia), tipo_justificacion_id = VALUES(tipo_justificacion_id)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$empleado_id, $f, $tipo_asistencia, $tipo_justificacion_id]);
        }
    }
    
    private function mapTipoJustificacionToAsistencia($tipo_justificacion) {
        $map = [
            'Retardo menor' => 'con_retardo',
            'Retardo mayor' => 'con_retardo',
            'Comisión de entrada' => 'comision_entrada',
            'Comisión de salida' => 'comision_salida',
            'Comisión todo el día' => 'comision_todo_dia',
            'Día económico' => 'dia_economico',
            'Licencia Médica' => 'licencia_medica',
            'Vacaciones' => 'vacaciones',
            'Cuidados maternos' => 'cuidados_maternos',
            'Cuidados paternos' => 'cuidados_paternos',
            'Falta' => 'falta',
            'Constancia de Tiempo' => 'constancia_tiempo',
            'Programa Deportivo SEP-SNTE' => 'PDSEP-SNTE',
            'CLIDDA' => 'CLIDDA',
            'Estimulos y Recompensas' => 'EYR',
            'Desalojo de Edificio' => 'DE',
            'Fumigacion' => 'F'
        ];
        
        return $map[$tipo_justificacion] ?? 'por_definir';
    }
}
