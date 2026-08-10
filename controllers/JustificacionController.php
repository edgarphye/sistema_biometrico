<?php
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/TipoJustificacion.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/ValidacionJefe.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/BaseController.php';

class JustificacionController extends BaseController {
    private $retardoModel;
    private $tipoJustificacionModel;
    private $usuarioModel;
    private $empleadoModel;
    private $validacionModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->retardoModel = new Retardo();
        $this->tipoJustificacionModel = new TipoJustificacion();
        $this->usuarioModel = new Usuario();
        $this->empleadoModel = new Empleado();
        $this->validacionModel = new ValidacionJefe();
    }

    /**
     * Mostrar retardos pendientes de justificación
     */
    public function index() {
        $retardos = $this->retardoModel->getPendientesJustificacion();
        
        ob_start();
        include 'views/justificaciones/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Justificar un retardo
     */
    public function justificar($retardo_id = null) {
        // Si no viene por URL (GET), intentar obtenerlo de POST
        if ($retardo_id === null) {
            $retardo_id = $_POST['id'] ?? null;
        }

        $retardo = $this->retardoModel->getById($retardo_id);
        if (!$retardo) {
            $this->redirect(BASE_URL . '/justificaciones');
        }
        
        // Verificar si el retardo está bloqueado para justificación
        if (!empty($retardo['justificacion_bloqueada'])) {
            $error = 'Este retardo fue convertido en sanción y no puede ser justificado.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../helpers/Csrf.php';
            $csrf = $_POST['csrf_token'] ?? '';
            if (!\Csrf::validate($csrf)) {
                $error = 'Token CSRF inválido. Vuelva a intentarlo.';
            } else {
                // proceed
            }
            
            if (isset($error)) {
                // skip further processing if CSRF invalid
            } else {
            $tipo_justificacion_id = $_POST['tipo_justificacion_id'] ?? null;
            $motivo = trim($_POST['motivo'] ?? '');
            $aprobado_por = $_SESSION['user_id'] ?? null;

            // Obtener el tipo de justificación para verificar si requiere documento
            $tipoJustificacion = $tipo_justificacion_id ? $this->tipoJustificacionModel->getById($tipo_justificacion_id) : null;
            $requiereDocumento = $tipoJustificacion['requiere_documento'] ?? false;
            
            // Determinar si requiere validación del jefe (requiere_aprobacion = true)
            $requiereValidacionJefe = !empty($tipoJustificacion['requiere_aprobacion']);
            
            // Determinar si requiere motivo (Nómina y Ausencia justificada no requieren)
            $nombreTipo = $tipoJustificacion['nombre'] ?? '';
            $requiereMotivo = !in_array($nombreTipo, ['Nómina', 'Ausencia justificada']);
            
            // Validar motivo si es requerido
            if ($requiereMotivo && empty($motivo)) {
                $error = 'Debe proporcionar un motivo de justificación.';
            }
            
            // Validar que el retardo esté dentro de la quincena actual 
            // (retardos: hasta día 15 o 30, comisiones: hasta 2 días hábiles después)
            $tipoRetardo = $retardo['tipo_retraso'] ?? 'retardo';
            $puedeJustificar = $this->retardoModel->puedeJustificar($retardo['fecha'], $tipoRetardo);
            
            if (!$puedeJustificar['puede_justificar']) {
                // Crear sanción automática por retardo fuera de período de justificación
                require_once 'models/Sancion.php';
                $sancionModel = new Sancion();
                
                $sancionData = [
                    'empleado_id' => $retardo['empleado_id'],
                    'tipo' => 'amonestacion',
                    'tipo_retardo' => $retardo['tipo_retraso'] ?? 'N/A',
                    'motivo' => 'Retardo fuera del período de justificación (' . $puedeJustificar['mensaje'] . '). Tipo de retardo: ' . ($retardo['tipo_retraso'] ?? 'N/A') . '. Fecha del retardo: ' . date('d/m/Y', strtotime($retardo['fecha'])),
                    'fecha_inicio' => date('Y-m-d'),
                    'dias' => 1,
                    'creado_por' => $_SESSION['user_id'] ?? null
                ];
                
                try {
                    $sancionModel->create($sancionData);
                    $this->retardoModel->bloquearJustificacion($retardo_id);
                    $this->redirect(BASE_URL . '/empleados/' . $retardo['empleado_id'] . '#sanciones');
                } catch (Exception $e) {
                    $this->logException($e, ['action' => 'justificar', 'retardo_id' => $retardo_id]);
                    $error = 'El retardo ya no puede ser justificado. ' . $puedeJustificar['mensaje'] . ' Error al crear sanción: ' . $e->getMessage();
                }
            }
            
            if (!isset($error)) {
                // Validar archivo de soporte (solo si el tipo de justificación lo requiere)
                $soportePath = null;
                if ($requiereDocumento) {
                    if (empty($_FILES['soporte']) || $_FILES['soporte']['error'] !== UPLOAD_ERR_OK) {
                        $error = 'Este tipo de justificación requiere adjuntar un documento de soporte.';
                    } else {
                        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                        $maxSize = 5 * 1024 * 1024; // 5MB
                        $fileTmp = $_FILES['soporte']['tmp_name'];

                        if ($_FILES['soporte']['size'] > $maxSize) {
                            $error = 'El archivo de soporte es demasiado grande (máx. 5MB).';
                        } elseif (!$this->validateMime($_FILES['soporte'], $allowed)) {
                            $error = 'Tipo de soporte no permitido. Use PDF o imagen JPG/PNG.';
                        } else {
                            $uploadDir = __DIR__ . '/../uploads/justificaciones/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                            $ext = pathinfo($_FILES['soporte']['name'], PATHINFO_EXTENSION);
                            $newName = 'soporte_retardo_' . $retardo_id . '_' . time() . '.' . $ext;
                            $dest = $uploadDir . $newName;
                            if (move_uploaded_file($fileTmp, $dest)) {
                                $soportePath = 'uploads/justificaciones/' . $newName;
                            } else {
                                $error = 'Error al subir el soporte documental.';
                            }
                        }
                    }
                }

                if (!isset($error)) {
                    // Si no requiere aprobación del jefe, marcar como no requerido (-1)
                    // Si requiere aprobación, queda pendiente (0)
                    $validado_por_jefe = $requiereValidacionJefe ? 0 : -1;
                    
                    if ($this->retardoModel->justificarRetardo($retardo_id, $tipo_justificacion_id, $motivo, $aprobado_por, $soportePath, null, $validado_por_jefe)) {
                        // Regla quincenal: 2 justificados permitidos; desde el 3ro, convertir a notas malas.
                        $this->crearNotaMalaPorExcesoQuincenal(
                            (int)$retardo['empleado_id'],
                            (int)$retardo_id,
                            (string)($retardo['tipo_retraso'] ?? ''),
                            (string)$retardo['fecha']
                        );

                        // Si requiere validación del jefe, crear registro en validaciones_jefe
                        if ($requiereValidacionJefe && !empty($retardo['empleado_id'])) {
                            try {
                                // Obtener el jefe directo del empleado
                                require_once 'models/Empleado.php';
                                $empleadoModel = new Empleado();
                                $empleado = $empleadoModel->getById($retardo['empleado_id']);
                                $jefe_id = $empleado['jefe_directo_id'] ?? null;
                                
                                if ($jefe_id) {
                                    // Determinar el tipo de incidencia según el tipo de retardo
                                    $tipoRetardo = $retardo['tipo_retraso'] ?? $retardo['tipo'] ?? '';
                                    $tipoIncidencia = 'retardo';
                                    
                                    // Si es una comisión, usar tipo_incidencia 'comision'
                                    if (in_array($tipoRetardo, ['comision_entrada', 'comision_salida', 'comision_todo_dia'])) {
                                        $tipoIncidencia = 'comision';
                                    }
                                    
                                    $this->validacionModel->crear([
                                        'incidencia_id' => $retardo_id,
                                        'tipo_incidencia' => $tipoIncidencia,
                                        'empleado_id' => $retardo['empleado_id'],
                                        'jefe_id' => $jefe_id,
                                        'estado' => 'pendiente',
                                        'evidencia_requerida' => !empty($soportePath)
                                    ]);
                                }
                            } catch (Exception $e) {
                                $this->logException($e, ['action' => 'justificar', 'subaction' => 'crear_validacion']);
                                error_log("Error al crear validación: " . $e->getMessage());
                            }
                        }
                        
                        // Siempre redirigir a la página del empleado si tenemos el ID
                        if (!empty($retardo['empleado_id'])) {
                            $redirectUrl = BASE_URL . '/empleados/' . $retardo['empleado_id'] . '#retardos';
                        } else {
                            $redirectUrl = BASE_URL . '/justificaciones';
                        }
                        $this->redirect($redirectUrl);
                    } else {
                        $error = 'Error al justificar el retardo';
                    }
                }
            }
            }
        }

        $tipos_justificacion = $this->tipoJustificacionModel->getAll();
        
        ob_start();
        include 'views/justificaciones/justificar.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Gestionar tipos de justificación
     */
    public function tipos() {
        $tipos = $this->tipoJustificacionModel->getAll();
        
        ob_start();
        include 'views/justificaciones/tipos.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layout.php';
    }
    
    public function tiposJson() {
        header('Content-Type: application/json');
        
        $tipos = $this->tipoJustificacionModel->getAll();
        
        echo json_encode(['success' => true, 'tipos' => $tipos]);
    }

    public function crearTipo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? null,
                'requiere_aprobacion' => isset($_POST['requiere_aprobacion']) ? 1 : 0,
                'requiere_documento' => isset($_POST['requiere_documento']) ? 1 : 0
            ];

            if ($this->tipoJustificacionModel->create($data)) {
                $this->redirect(BASE_URL . '/justificaciones/tipos');
            } else {
                $error = 'Error al crear el tipo de justificación';
            }
        }

        ob_start();
        include 'views/justificaciones/crear_tipo.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Obtener datos de un retardo para el modal (AJAX)
     */
    public function getRetardoAjax() {
        header('Content-Type: application/json');
        
        // FastRoute pasa los parámetros como argumentos posicionales
        $args = func_get_args();
        $retardo_id = $args[0] ?? null;
        
        error_log("DEBUG getRetardoAjax - args: " . json_encode($args) . ", retardo_id: " . var_export($retardo_id, true));
        
        if ($retardo_id === null) {
            echo json_encode(['success' => false, 'error' => 'ID de retardo no proporcionado']);
            return;
        }

        $retardo = $this->retardoModel->getById($retardo_id);
        if (!$retardo) {
            echo json_encode(['success' => false, 'error' => 'Retardo no encontrado']);
            return;
        }

        if (!empty($retardo['justificacion_bloqueada'])) {
            echo json_encode(['success' => false, 'error' => 'Este retardo fue convertido en sanción y no puede ser justificado.']);
            return;
        }

        $tipoIncidencia = $retardo['tipo_retraso'] ?? $retardo['tipo'] ?? 'normal';
        
        // Mapear tipos de retardo al tipo de catálogo correspondiente
        $mapeoTipo = [
            'retardo_menor' => 'retardo',
            'retardo_mayor' => 'retardo',
            'falta' => 'falta',
            'comision_entrada' => 'comision_entrada',
            'comision_salida' => 'comision_salida',
            'comision_todo_dia' => 'comision_todo_dia'
        ];
        
        $tipoCatalogo = $mapeoTipo[$tipoIncidencia] ?? 'retardo';
        $tipos_justificacion = $this->tipoJustificacionModel->getAllForIncidencia($tipoCatalogo);
        
        $mapeoIncidenciaJustificacion = [
            'retardo_menor' => 'Nómina',
            'retardo_mayor' => 'Ausencia justificada',
            'comision_entrada' => 'Comisión',
            'comision_salida' => 'Comisión',
            'comision_todo_dia' => 'Comisión',
            'dia_economico' => 'Día económico',
            'ausencia' => 'Licencia médica',
            'normal' => 'Nómina'
        ];
        
        $tipoSugerido = $mapeoIncidenciaJustificacion[$tipoIncidencia] ?? $mapeoIncidenciaJustificacion['normal'];
        
        echo json_encode([
            'success' => true,
            'retardo' => $retardo,
            'tipos_justificacion' => $tipos_justificacion,
            'tipo_sugerido' => $tipoSugerido,
            'tipo_incidencia' => $tipoIncidencia
        ]);
    }

    /**
     * Obtener datos de una asistencia para el modal (AJAX)
     */
    public function getAsistenciaAjax() {
        header('Content-Type: application/json');
        
        // FastRoute pasa los parámetros como argumentos posicionales
        $args = func_get_args();
        $asistencia_id = $args[0] ?? null;
        
        if ($asistencia_id === null) {
            echo json_encode(['success' => false, 'error' => 'ID de asistencia no proporcionado']);
            return;
        }

        require_once 'models/Asistencia.php';
        $asistenciaModel = new Asistencia();
        $asistencia = $asistenciaModel->getById($asistencia_id);
        
        if (!$asistencia) {
            echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
            return;
        }

        $esIncidencia = isset($_GET['origen']) && $_GET['origen'] === 'incidencia';
        
        $tipoIncidencia = 'comision_todo_dia';
        $tieneEntrada = !empty($asistencia['hora_entrada']);
        $tieneSalida = !empty($asistencia['hora_salida']);
        
        // Para INCIDENCIAS: determinar según horas (ignorar tipo_asistencia guardado)
        // Para RETARDOS: usar el tipo guardado normalmente
        if ($esIncidencia) {
            // Para incidencias: determinar según horas
            if ($tieneEntrada && $tieneSalida) {
                $tipoIncidencia = 'normal';
            } elseif ($tieneEntrada && !$tieneSalida) {
                $tipoIncidencia = 'comision_salida';
            } elseif (!$tieneEntrada && $tieneSalida) {
                $tipoIncidencia = 'comision_entrada';
            } else {
                $tipoIncidencia = 'comision_todo_dia';
            }
        } else {
            // Usar el tipo_asistencia guardado si existe
            $tipoAsistenciaGuardado = $asistencia['tipo_asistencia'] ?? '';
            if (!empty($tipoAsistenciaGuardado) && $tipoAsistenciaGuardado !== 'normal') {
                $tipoIncidencia = $tipoAsistenciaGuardado;
            } elseif ($tieneEntrada && $tieneSalida) {
                $tipoIncidencia = 'normal';
            } elseif ($tieneEntrada && !$tieneSalida) {
                $tipoIncidencia = 'comision_salida';
            } elseif (!$tieneEntrada && $tieneSalida) {
                $tipoIncidencia = 'comision_entrada';
            }
        }

        // Para incidencias, usar getAllForIncidencia con los tipos permitidos
        // Solo: comision_entrada, comision_salida, comision_todo_dia, dia_economico, licencia_medica, vacaciones, cuidados_parentales
        $tipos_justificacion = $this->tipoJustificacionModel->getAllForIncidencia($tipoIncidencia);
        
        echo json_encode([
            'success' => true,
            'asistencia' => $asistencia,
            'tipos_justificacion' => $tipos_justificacion,
            'tipo_incidencia' => $tipoIncidencia,
            'tipo_asistencia' => $asistencia['tipo_asistencia'] ?? '',
            'tipo_justificacion_id' => $asistencia['tipo_justificacion_id'] ?? null
        ]);
    }

    /**
     * Procesar justificación vía AJAX (para modal)
     */
    public function ajaxJustificar() {
        // Limpiar cualquier salida previa para asegurar JSON válido
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $retardo_id = $_POST['retardo_id'] ?? null;
            
            if (!$retardo_id) {
                throw new Exception('ID de retardo no proporcionado');
            }

            $retardo = $this->retardoModel->getById($retardo_id);
            if (!$retardo) {
                throw new Exception('Retardo no encontrado');
            }

            if (!empty($retardo['justificacion_bloqueada'])) {
                throw new Exception('Este retardo fue convertido en sanción y no puede ser justificado.');
            }

            if (!class_exists('Csrf')) {
                require_once __DIR__ . '/../helpers/Csrf.php';
            }
            
            $csrf = $_POST['csrf_token'] ?? '';
            if (!\Csrf::validate($csrf)) {
                throw new Exception('Token CSRF inválido.');
            }

            $tipo_justificacion_id = $_POST['tipo_justificacion_id'] ?? null;
            $motivo = trim($_POST['motivo'] ?? '');
            $aprobado_por = $_SESSION['user_id'] ?? null;

            $tipoJustificacion = $tipo_justificacion_id ? $this->tipoJustificacionModel->getById($tipo_justificacion_id) : null;
            
            // Verificar si puede justificar según las reglas (día 15 o 30 para retardos, 2 días hábiles para comisiones)
            $tipoRetardo = $retardo['tipo_retraso'] ?? 'retardo';
            $puedeJustificar = $this->retardoModel->puedeJustificar($retardo['fecha'], $tipoRetardo);
            
            if (!$puedeJustificar['puede_justificar']) {
                require_once 'models/Sancion.php';
                $sancionModel = new Sancion();
                
                $tipoRetardo = $retardo['tipo_retraso'] ?? 'retardo';
                
                $sancionData = [
                    'empleado_id' => $retardo['empleado_id'],
                    'tipo' => 'amonestacion',
                    'tipo_retardo' => $tipoRetardo,
                    'motivo' => 'Retardo fuera del período de justificación. ' . $puedeJustificar['mensaje'],
                    'fecha_inicio' => date('Y-m-d'),
                    'dias' => 1,
                    'creado_por' => $_SESSION['user_id'] ?? null
                ];
                
                try {
                    $sancionModel->create($sancionData);
                    $this->retardoModel->bloquearJustificacion($retardo_id);
                    
                    // Crear nota mala según reglas
                    $this->crearNotaMala($retardo['empleado_id'], $retardo_id, $tipoRetardo, $retardo['fecha']);
                    
                    $mensaje = 'El retardo ya no puede ser justificado. ' . $puedeJustificar['mensaje'];
                    $mensaje .= ' Se ha creado una sanción.';
                    
                    throw new Exception($mensaje);
                } catch (Exception $e) {
                    // Si la excepción es la que acabamos de lanzar, relanzarla
                    if (strpos($e->getMessage(), 'El retardo ya no puede ser justificado') !== false) {
                        throw $e;
                    }
                    $this->logException($e, ['action' => 'apiJustificar', 'retardo_id' => $retardo_id ?? null]);
                    throw new Exception('Error al crear sanción: ' . $e->getMessage());
                }
            }
            
            // Verificar si es una incidencia de tipo comisión
            $tipoRetardo = $retardo['tipo_retraso'] ?? '';
            $esComision = in_array($tipoRetardo, ['comision_entrada', 'comision_salida', 'comision_todo_dia', 'comision_dia']);
            
            // Para comisiones, solo requiere motivo, NO documento
            if ($esComision) {
                $requiereDocumento = false;
            } else {
                $requiereDocumento = $tipoJustificacion['requiere_documento'] ?? false;
            }
            
            $requiereValidacionJefe = !empty($tipoJustificacion['requiere_aprobacion']);
            $nombreTipo = $tipoJustificacion['nombre'] ?? '';
            $requiereMotivo = !in_array($nombreTipo, ['Nómina', 'Ausencia justificada']);

            if ($requiereMotivo && empty($motivo)) {
                throw new Exception('Debe proporcionar un motivo de justificación.');
            }

            $soportePath = null;
            if ($requiereDocumento) {
                if (empty($_FILES['soporte']) || $_FILES['soporte']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Este tipo de justificación requiere adjuntar un documento de soporte.');
                } else {
                    $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                    $maxSize = 5 * 1024 * 1024;
                    $fileTmp = $_FILES['soporte']['tmp_name'];

                    if ($_FILES['soporte']['size'] > $maxSize) {
                        throw new Exception('El archivo de soporte es demasiado grande (máx. 5MB).');
                    } elseif (!$this->validateMime($_FILES['soporte'], $allowed)) {
                        throw new Exception('Tipo de soporte no permitido. Use PDF o imagen JPG/PNG.');
                    } else {
                        $uploadDir = __DIR__ . '/../uploads/justificaciones/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $ext = pathinfo($_FILES['soporte']['name'], PATHINFO_EXTENSION);
                        $newName = 'soporte_retardo_' . $retardo_id . '_' . time() . '.' . $ext;
                        $dest = $uploadDir . $newName;
                        if (move_uploaded_file($fileTmp, $dest)) {
                            $soportePath = 'uploads/justificaciones/' . $newName;
                        } else {
                            throw new Exception('Error al subir el soporte documental.');
                        }
                    }
                }
            }

            $validado_por_jefe = $requiereValidacionJefe ? 0 : -1;

            if ($this->retardoModel->justificarRetardo($retardo_id, $tipo_justificacion_id, $motivo, $aprobado_por, $soportePath, null, $validado_por_jefe)) {
                // Regla quincenal: 2 justificados permitidos; desde el 3ro, convertir a notas malas.
                $this->crearNotaMalaPorExcesoQuincenal(
                    (int)$retardo['empleado_id'],
                    (int)$retardo_id,
                    (string)($retardo['tipo_retraso'] ?? ''),
                    (string)$retardo['fecha']
                );
                
                // Actualizar sistema de IA en tiempo real
                try {
                    require_once __DIR__ . '/../services/AIActualizadorService.php';
                    $aiActualizador = new AIActualizadorService();
                    $aiActualizador->procesarNuevoEvento(
                        (int)$retardo['empleado_id'],
                        'justificacion',
                        [
                            'tipo_justificacion_id' => $tipo_justificacion_id,
                            'retardo_id' => $retardo_id,
                            'fecha' => $retardo['fecha']
                        ]
                    );
                } catch (Exception $e) {
                    error_log("Error al actualizar IA: " . $e->getMessage());
                }

                if ($requiereValidacionJefe && !empty($retardo['empleado_id'])) {
                    try {
                        require_once 'models/Empleado.php';
                        $empleadoModel = new Empleado();
                        $empleado = $empleadoModel->getById($retardo['empleado_id']);
                        $jefe_id = $empleado['jefe_directo_id'] ?? null;
                        
                        if ($jefe_id) {
                            // Determinar el tipo de incidencia según el tipo de retardo
                            $tipoRetardo = $retardo['tipo_retraso'] ?? $retardo['tipo'] ?? '';
                            $tipoIncidencia = 'retardo';
                            
                            // Si es una comisión, usar tipo_incidencia 'comision'
                            if (in_array($tipoRetardo, ['comision_entrada', 'comision_salida', 'comision_todo_dia'])) {
                                $tipoIncidencia = 'comision';
                            }
                            
                            $this->validacionModel->crear([
                                'incidencia_id' => $retardo_id,
                                'tipo_incidencia' => $tipoIncidencia,
                                'empleado_id' => $retardo['empleado_id'],
                                'jefe_id' => $jefe_id,
                                'estado' => 'pendiente',
                                'evidencia_requerida' => !empty($soportePath)
                            ]);
                        }
                    } catch (Exception $e) {
                        $this->logException($e, ['action' => 'apiJustificar', 'subaction' => 'crear_validacion']);
                        error_log("Error al crear validación: " . $e->getMessage());
                    }
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Incidencia justificada correctamente. Pendiente de validación del jefe.',
                    'redirect_url' => BASE_URL . '/empleados/' . $retardo['empleado_id'] . '#retardos'
                ]);
            } else {
                throw new Exception('Error al justificar el retardo');
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    private function crearNotaMala($empleadoId, $retardoId, $tipo, $fecha) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // El emparejamiento (2 menores = 1 nota, 1 mayor = 1 nota) ocurre DENTRO de la quincena.
            [$inicioQuincena, $finQuincena] = $this->obtenerRangoQuincena($fecha);
            $periodo = date('Y-m-01', strtotime($fecha));
            
            // Contar retardos menores bloqueados en la quincena
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM retardos 
                WHERE empleado_id = ? 
                AND tipo_retraso = 'retardo_menor' 
                AND justificacion_bloqueada = 1 
                AND fecha BETWEEN ? AND ?
            ");
            $stmt->execute([$empleadoId, $inicioQuincena, $finQuincena]);
            $resultMenores = $stmt->fetch(PDO::FETCH_ASSOC);
            $cantidadMenores = intval($resultMenores['total'] ?? 0);
            
            // Contar retardos mayores bloqueados en la quincena
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM retardos 
                WHERE empleado_id = ? 
                AND tipo_retraso = 'retardo_mayor' 
                AND justificacion_bloqueada = 1 
                AND fecha BETWEEN ? AND ?
            ");
            $stmt->execute([$empleadoId, $inicioQuincena, $finQuincena]);
            $resultMayores = $stmt->fetch(PDO::FETCH_ASSOC);
            $cantidadMayores = intval($resultMayores['total'] ?? 0);
            
            // Aplicar regla: floor(menores/2) + mayores
            $notasPorMenores = floor($cantidadMenores / 2);
            $totalNotas = $notasPorMenores + $cantidadMayores;
            
            // Solo crear nota mala si corresponde (cada 2 retardos menores o cada mayor)
            $crearNota = false;
            $tipoNota = 'otro';
            $cantidad = 0;
            $motivo = '';
            
            if ($tipo === 'retardo_menor') {
                // Regla: cada 2 retardos menores = 1 nota mala, dentro de la misma quincena
                // Verificar si este retardo completa el par
                if ($cantidadMenores % 2 === 0 && $cantidadMenores > 0) {
                    $crearNota = true;
                    $tipoNota = 'retardo_menor';
                    $cantidad = 1;
                    $notasAnteriores = floor(($cantidadMenores - 1) / 2);
                    $notasAhora = floor($cantidadMenores / 2);
                    $cantidad = $notasAhora - $notasAnteriores;
                    $motivo = "2 retardos menores sin justificar en quincena " . date('d/m/Y', strtotime($inicioQuincena)) . "-" . date('d/m/Y', strtotime($finQuincena)) . " ({$cantidadMenores} retardos menores = {$cantidad} nota mala)";
                }
            } elseif ($tipo === 'retardo_mayor') {
                // Regla: cada retardo mayor = 1 nota mala
                $crearNota = true;
                $tipoNota = 'retardo_mayor';
                $cantidad = 1;
                $motivo = "Retardo mayor sin justificar en quincena " . date('d/m/Y', strtotime($inicioQuincena)) . "-" . date('d/m/Y', strtotime($finQuincena));
            }
            
            if ($crearNota && $cantidad > 0) {
                $stmt = $db->prepare("
                    INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$empleadoId, $retardoId, $tipoNota, $cantidad, $periodo, $motivo]);
            }
            
            return true;
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'crearNotaMala', 'empleado_id' => $empleadoId, 'retardo_id' => $retardoId]);
            error_log("Error al crear nota mala: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Regla quincenal:
     * - Se permiten 2 retardos justificados por quincena sin nota.
     * - A partir del 3ro en la misma quincena:
     *   - retardo_menor: cada 2 generan 1 nota mala.
     *   - retardo_mayor: cada 1 genera 1 nota mala.
     */
    private function crearNotaMalaPorExcesoQuincenal($empleadoId, $retardoId, $tipo, $fecha) {
        if (!in_array($tipo, ['retardo_menor', 'retardo_mayor'], true)) {
            return false;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Evitar duplicados por el mismo retardo.
            $stmtExiste = $db->prepare("SELECT COUNT(*) AS total FROM notas_malas WHERE retardo_id = ?");
            $stmtExiste->execute([$retardoId]);
            if (((int)($stmtExiste->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0) {
                return false;
            }

            [$inicioQuincena, $finQuincena] = $this->obtenerRangoQuincena($fecha);

            $stmt = $db->prepare("
                SELECT id, tipo_retraso, fecha
                FROM retardos
                WHERE empleado_id = ?
                  AND justificado = 1
                  AND tipo_retraso IN ('retardo_menor', 'retardo_mayor')
                  AND fecha BETWEEN ? AND ?
                ORDER BY fecha ASC, id ASC
            ");
            $stmt->execute([$empleadoId, $inicioQuincena, $finQuincena]);
            $retardosJustificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($retardosJustificados) <= 2) {
                return false;
            }

            $indexActual = -1;
            foreach ($retardosJustificados as $i => $registro) {
                if ((int)$registro['id'] === (int)$retardoId) {
                    $indexActual = $i;
                    break;
                }
            }

            // Si no está en el listado o está dentro de los 2 permitidos, no genera nota.
            if ($indexActual < 2) {
                return false;
            }

            $exceso = array_slice($retardosJustificados, 2);
            $indiceEnExceso = $indexActual - 2;
            $periodo = $inicioQuincena;
            $cantidad = 0;
            $tipoNota = $tipo;
            $motivo = '';

            if ($tipo === 'retardo_mayor') {
                $cantidad = 1;
                $motivo = "Retardo mayor justificado excedente en quincena {$inicioQuincena} a {$finQuincena}.";
            } else {
                // Cada 2 retardos menores excedentes de la quincena = 1 nota mala.
                $menoresHastaActual = 0;
                for ($i = 0; $i <= $indiceEnExceso; $i++) {
                    if (($exceso[$i]['tipo_retraso'] ?? '') === 'retardo_menor') {
                        $menoresHastaActual++;
                    }
                }

                if ($menoresHastaActual > 0 && $menoresHastaActual % 2 === 0) {
                    $cantidad = 1;
                    $motivo = "Par de retardos menores justificados excedentes en quincena {$inicioQuincena} a {$finQuincena}.";
                }
            }

            if ($cantidad <= 0) {
                return false;
            }

            $stmtInsert = $db->prepare("
                INSERT INTO notas_malas (empleado_id, retardo_id, tipo, cantidad, periodo, motivo)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$empleadoId, $retardoId, $tipoNota, $cantidad, $periodo, $motivo]);

            return true;
        } catch (Exception $e) {
            $this->logException($e, [
                'action' => 'crearNotaMalaPorExcesoQuincenal',
                'empleado_id' => $empleadoId,
                'retardo_id' => $retardoId
            ]);
            return false;
        }
    }

    private function obtenerRangoQuincena($fecha) {
        $ts = strtotime($fecha);
        $dia = (int)date('d', $ts);
        $mes = date('m', $ts);
        $anio = date('Y', $ts);

        if ($dia <= 15) {
            return ["{$anio}-{$mes}-01", "{$anio}-{$mes}-15"];
        }

        return ["{$anio}-{$mes}-16", date('Y-m-t', $ts)];
    }
    
    /**
     * Guardar incidencia desde el modal de empleados
     */
    public function guardarIncidencia() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }
        
        try {
            $asistencia_id = $_POST['asistencia_id'] ?? null;
            $empleado_id = $_POST['empleado_id'] ?? null;
            $tipo_justificacion = $_POST['tipo_justificacion'] ?? null;
            $tipo_justificacion_id = $_POST['tipo_justificacion_id'] ?? null;
            $tipoCatalogo = null;
            
            error_log("DEBUG guardarIncidencia - asistencia_id: $asistencia_id, empleado_id: $empleado_id, tipo_justificacion: $tipo_justificacion, tipo_justificacion_id: $tipo_justificacion_id");
            
            if (!$asistencia_id || !$empleado_id || !$tipo_justificacion) {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                return;
            }
            
            //Nueva funcionalidad: Solicitud directa de comisión sin asistencia previa
            //Si asistencia_id es 0 o vacío, significa que es una solicitud directa
            $esSolicitudDirecta = empty($asistencia_id) || $asistencia_id == '0' || $asistencia_id === '';
            
            if ($esSolicitudDirecta && strpos($tipo_justificacion, 'comision') !== false) {
                $this->procesarComisionDirecta($empleado_id, $tipo_justificacion);
                return;
            }
            
            //Nueva funcionalidad: Solicitud directa de vacaciones sin asistencia previa
            if ($esSolicitudDirecta && $tipo_justificacion === 'vacaciones') {
                $this->procesarVacacionesDirectas($empleado_id);
                return;
            }
            
            //Nueva funcionalidad: Solicitud directa de cuidados maternos/paternos sin asistencia previa
            if ($esSolicitudDirecta && in_array($tipo_justificacion, ['cuidados_maternos', 'cuidados_paternos'])) {
                $this->procesarCuidadosDirectos($empleado_id, $tipo_justificacion);
                return;
            }
            
            //Nueva funcionalidad: Solicitud directa de Constancia de Tiempo
            if ($esSolicitudDirecta && $tipo_justificacion === 'constancia_tiempo') {
                $this->procesarConstanciaDirecta($empleado_id);
                return;
            }
            
            if (empty($tipo_justificacion_id)) {
                // Buscar el ID del catálogo usando tipo_incidencia
                require_once 'models/TipoJustificacion.php';
                $tipoModel = new TipoJustificacion();
                $tipos = $tipoModel->getAll();
                
                foreach ($tipos as $t) {
                    $tipoInc = $t['tipo_incidencia'] ?? '';
                    if (strtolower($tipoInc) === strtolower($tipo_justificacion)) {
                        $tipo_justificacion_id = $t['id'];
                        $tipoCatalogo = $t;
                        break;
                    }
                }
                
                if (empty($tipo_justificacion_id)) {
                    echo json_encode(['success' => false, 'error' => 'Debe seleccionar un tipo de justificación del catálogo']);
                    return;
                }
            }

            require_once 'models/Asistencia.php';
            $asistenciaModel = new Asistencia();
            $asistencia = $asistenciaModel->getById($asistencia_id);
            if (!$asistencia || (int)$asistencia['empleado_id'] !== (int)$empleado_id) {
                echo json_encode(['success' => false, 'error' => 'La asistencia no corresponde al empleado']);
                return;
            }
            
            // Si había una justificación anterior, eliminarla y regresar a por_definir
            $tipoActual = $asistencia['tipo_asistencia'] ?? '';
            if (!in_array($tipoActual, ['por_definir', 'normal', '', null], true)) {
                // Eliminar registro anterior según el tipo
                $this->eliminarJustificacionAnterior($asistencia_id, $tipoActual, $empleado_id);
            }
            
            $tipoIncidenciaAsistencia = $this->determinarTipoIncidenciaAsistencia($asistencia);

            // Si viene el tipo desde catálogo, ese define la regla a aplicar.
            // Nota: $tipoCatalogo ya fue obtenido anteriormente en las líneas 780-799
            if (!empty($tipo_justificacion_id) && !$tipoCatalogo) {
                $tipoCatalogo = $this->tipoJustificacionModel->getById((int)$tipo_justificacion_id);
            }
            
            // Si no hay tipo del catálogo pero viene un tipo directo (comision_entrada, etc.), procesarlo directamente
            if (!$tipoCatalogo && !empty($tipo_justificacion)) {
                $tiposDirectosValidos = ['comision_entrada', 'comision_salida', 'comision_todo_dia', 'dia_economico', 'licencia_medica', 'vacaciones', 'cuidados_parentales'];
                if (in_array($tipo_justificacion, $tiposDirectosValidos)) {
                    // Procesar directamente sin consultar catálogo
                    goto procesar_tipo_directo;
                }
            }
            
            if (!$tipoCatalogo) {
                echo json_encode(['success' => false, 'error' => 'Tipo de justificación inválido']);
                return;
            }

            $tipoNormalizado = $this->resolverTipoIncidenciaDesdeCatalogo(
                $tipoCatalogo,
                $tipo_justificacion,
                $tipoIncidenciaAsistencia
            );
            if ($tipoNormalizado === null) {
                echo json_encode(['success' => false, 'error' => 'El tipo del catálogo no aplica para incidencias por definir']);
                return;
            }
            $tipo_justificacion = $tipoNormalizado;

            // Validar si el tipo de justificación requiere documento (comisiones de entrada/salida/día completo no requieren)
            $esComisionEntradaSalida = in_array($tipo_justificacion, ['comision_entrada', 'comision_salida', 'comision_todo_dia', 'comision_dia']);
            $requiereDocumento = !$esComisionEntradaSalida && !empty($tipoCatalogo['requiere_documento']);
            $soportePath = null;

            if ($requiereDocumento && (empty($_FILES['soporte']) || $_FILES['soporte']['error'] !== UPLOAD_ERR_OK)) {
                echo json_encode(['success' => false, 'error' => 'Este tipo de justificación requiere adjuntar un documento de soporte.']);
                return;
            }

            // Procesar archivo si existe
            if (!empty($_FILES['soporte']) && $_FILES['soporte']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                if ($_FILES['soporte']['size'] > $maxSize) {
                    echo json_encode(['success' => false, 'error' => 'El archivo excede el tamaño máximo de 5MB.']);
                    return;
                }
                if (!$this->validateMime($_FILES['soporte'], $allowed)) {
                    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido. Use PDF, JPG o PNG.']);
                    return;
                }
                $uploadDir = __DIR__ . '/../uploads/justificaciones/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $fileName = 'incidencia_' . $asistencia_id . '_' . time() . '.' . pathinfo($_FILES['soporte']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['soporte']['tmp_name'], $uploadDir . $fileName)) {
                    $soportePath = 'uploads/justificaciones/' . $fileName;
                }
            }

            $fundamentoTipo = trim($_POST['fundamento_tipo'] ?? '');
            
            // Etiqueta para procesamiento directo sin catálogo
            procesar_tipo_directo:
            
            if (empty($fundamentoTipo) && !empty($tipoCatalogo) && !empty($tipoCatalogo['descripcion'])) {
                $fundamentoTipo = trim((string)$tipoCatalogo['descripcion']);
            }

            // Según el tipo de justificación, crear el registro correspondiente
            if ($tipo_justificacion === 'dia_economico') {
                require_once 'models/DiasEconomicos.php';
                $diasEcoModel = new DiasEconomicos();
                
                $diasSolicitados = (int)($_POST['dias_solicitados'] ?? 1);
                $fechaDiaEco = $_POST['fecha'] ?? date('Y-m-d');
                $motivoDiaEco = trim($_POST['motivo'] ?? 'Día económico');
                if (!empty($fundamentoTipo)) {
                    $motivoDiaEco .= ' | Fundamento: ' . $fundamentoTipo;
                }

                if ($diasSolicitados < 1 || $diasSolicitados > 3) {
                    echo json_encode(['success' => false, 'error' => 'Días solicitados inválidos para día económico']);
                    return;
                }
                if (empty($fechaDiaEco) || empty($motivoDiaEco)) {
                    echo json_encode(['success' => false, 'error' => 'Para día económico se requiere fecha y motivo']);
                    return;
                }

                $data = [
                    'empleado_id' => $empleado_id,
                    'fecha' => $fechaDiaEco,
                    'dias_solicitados' => $diasSolicitados,
                    'tipo' => 'individual',
                    'motivo' => $motivoDiaEco,
                    'estatus' => 'pendiente',
                    'solicitado_por' => $_SESSION['user_id'],
                    'modalidad' => $diasSolicitados >= 3 ? 'A' : ($diasSolicitados === 2 ? 'B' : 'C'),
                    'evidencia_adjunta' => $soportePath
                ];
                
                $resultado = $diasEcoModel->solicitar($data);
                if (empty($resultado['success'])) {
                    echo json_encode(['success' => false, 'error' => $resultado['error'] ?? 'No se pudo registrar el día económico']);
                    return;
                }

                $nuevoId = (int)($resultado['id'] ?? 0);
                if ($nuevoId > 0) {
                    $this->_crearValidacionJefe($nuevoId, 'dia_economico', $empleado_id);
                }

                // Calcular fecha fin según días solicitados
                $fechaFinEco = date('Y-m-d', strtotime($fechaDiaEco . ' + ' . ($diasSolicitados - 1) . ' days'));
                
                // Actualizar todas las asistencias en el rango de fechas
                $db = new Database();
                $pdo = $db->getConnection();
                $stmt = $pdo->prepare("
                    UPDATE asistencia 
                    SET tipo_asistencia = 'dia_economico', updated_at = NOW() 
                    WHERE empleado_id = ? 
                    AND fecha BETWEEN ? AND ?
                    AND tipo_asistencia != 'normal'
                ");
                $stmt->execute([$empleado_id, $fechaDiaEco, $fechaFinEco]);

                $asistenciaModel->update($asistencia_id, ['tipo_asistencia' => 'dia_economico']);
                
                echo json_encode(['success' => true, 'message' => 'Día económico solicitado correctamente']);
                return;
                
            } elseif (in_array($tipo_justificacion, ['licencia_medica', 'constancia_tiempo', 'vacaciones', 'cuidados_parentales'], true)) {
                
                // Para licencia médica y constancia de tiempo, guardar en la tabla específica
                if ($tipo_justificacion === 'licencia_medica' || $tipo_justificacion === 'constancia_tiempo') {
                    require_once 'models/LicenciaMedica.php';
                    $licenciaModel = new LicenciaMedica();
                    
                    $folio = trim($_POST['folio_licencia'] ?? '');
                    $diasLicencia = (int)($_POST['dias_otorgados'] ?? 0);
                    $fechaInicio = $_POST['fecha_inicio_licencia'] ?? '';
                    $fechaFin = $_POST['fecha_fin_licencia'] ?? '';
                    $detalle = trim($_POST['diagnostico'] ?? '');
                    
                    if (empty($folio) || $diasLicencia <= 0 || empty($fechaInicio) || empty($detalle)) {
                        echo json_encode(['success' => false, 'error' => 'Para licencia médica se requiere folio, días, fecha inicio y diagnóstico']);
                        return;
                    }
                    
                    if (strtotime($fechaFin) < strtotime($fechaInicio)) {
                        echo json_encode(['success' => false, 'error' => 'La fecha fin de licencia no puede ser menor a la fecha inicio']);
                        return;
                    }
                    
                    // Calcular antigüedad y días permitidos
                    $datosAntiguedad = $licenciaModel->calcularDiasPermitidos($empleado_id);
                    
                    // Determinar tipo de sueldo basado en días disponibles
                    $full_restantes = $datosAntiguedad['full_restantes'] ?? 0;
                    $half_restantes = $datosAntiguedad['half_restantes'] ?? 0;
                    
                    $tipo_sueldo = 'full';
                    $dias_full = 0;
                    $dias_half = 0;
                    $dias_sin_sueldo = 0;
                    
                    if ($diasLicencia <= $full_restantes) {
                        // Usa solo días con sueldo íntegro
                        $tipo_sueldo = 'full';
                        $dias_full = $diasLicencia;
                    } elseif ($diasLicencia <= $full_restantes + $half_restantes) {
                        // Usa días con sueldo íntegro + días con medio sueldo
                        $tipo_sueldo = 'half';
                        $dias_full = $full_restantes;
                        $dias_half = $diasLicencia - $full_restantes;
                    } else {
                        // Se pasa de los días permitidos, tiene días sin goce de sueldo
                        $tipo_sueldo = 'none';
                        $dias_full = $datosAntiguedad['full'] ?? 0;
                        $dias_half = $datosAntiguedad['half'] ?? 0;
                        $dias_sin_sueldo = $diasLicencia - $full_restantes - $half_restantes;
                    }
                    
                    // Obtener los IDs de todas las asistencias en el rango de fechas
                    $db = new Database();
                    $pdo = $db->getConnection();
                    $stmtAsist = $pdo->prepare("
                        SELECT id FROM asistencia 
                        WHERE empleado_id = ? 
                        AND fecha BETWEEN ? AND ?
                    ");
                    $stmtAsist->execute([$empleado_id, $fechaInicio, $fechaFin]);
                    $asistencias = $stmtAsist->fetchAll(PDO::FETCH_COLUMN);
                    $asistenciaIds = json_encode($asistencias);
                    
                    $data = [
                        'empleado_id' => $empleado_id,
                        'antiguedad_dias' => $datosAntiguedad['antiguedad_dias'],
                        'dias_permitidos_full' => $datosAntiguedad['full'],
                        'dias_permitidos_half' => $datosAntiguedad['half'],
                        'dias_full' => $dias_full,
                        'dias_half' => $dias_half,
                        'dias_sin_sueldo' => $dias_sin_sueldo,
                        'incidencia_id' => $asistencia_id,
                        'asistencia_ids' => $asistenciaIds,
                        'folio' => $folio,
                        'dias_otorgados' => $diasLicencia,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin ?: $fechaInicio,
                        'diagnostico' => $detalle,
                        'institucion' => $_POST['institucion'] ?? '',
                        'numero_constancia' => $folio,
                        'fundamentos' => $fundamentoTipo,
                        'tipo_sueldo' => $tipo_sueldo
                    ];
                    
                    $nuevoId = $licenciaModel->create($data);
                    if (!$nuevoId) {
                        echo json_encode(['success' => false, 'error' => 'No se pudo registrar la licencia médica']);
                        return;
                    }
                    
                    // Actualizar control de días usados
                    $licenciaModel->actualizarControlDias($empleado_id, $dias_full, $dias_half, $dias_sin_sueldo);
                    
                    // Actualizar la asistencia actual con el tipo de justificación
                    $tipoAsistencia = $tipo_justificacion === 'constancia_tiempo' ? 'constancia_tiempo' : 'licencia_medica';
                    $asistenciaModel->update($asistencia_id, ['tipo_asistencia' => $tipoAsistencia]);
                    
                    // Actualizar todas las asistencias en el rango de fechas de la licencia
                    $stmt = $pdo->prepare("
                        UPDATE asistencia 
                        SET tipo_asistencia = ?, updated_at = NOW() 
                        WHERE empleado_id = ? 
                        AND fecha BETWEEN ? AND ?
                        AND tipo_asistencia != 'normal'
                    ");
                    $stmt->execute([$tipoAsistencia, $empleado_id, $fechaInicio, $fechaFin]);
                    
                    $this->_crearValidacionJefe($nuevoId, 'licencia_medica', $empleado_id, !empty($soportePath));
                    
                    echo json_encode(['success' => true, 'message' => 'Licencia médica registrada correctamente. Pendiente de validación del jefe.']);
                    return;
                }
                
                // Para vacaciones y cuidados parentales, usar la tabla ausencias
                require_once 'models/Ausencia.php';
                $ausenciaModel = new Ausencia();

                $fechaInicio = $_POST['fecha_inicio_licencia'] ?? '';
                $fechaFin = $_POST['fecha_fin_licencia'] ?? '';
                $detalle = trim($_POST['diagnostico'] ?? '');
                $diasLicencia = (int)($_POST['dias_otorgados'] ?? 0);

                $tipoAusencia = 'otro';
                $tipoTablaAusencia = 'personal';
                $mensajeOk = 'Incidencia de ausencia registrada correctamente';

                if ($tipo_justificacion === 'licencia_medica') {
                    $folio = trim($_POST['folio_licencia'] ?? '');
                    if (empty($folio) || $diasLicencia <= 0 || empty($fechaInicio) || empty($fechaFin) || empty($detalle)) {
                        echo json_encode(['success' => false, 'error' => 'Para licencia médica se requiere folio, días, fechas y diagnóstico']);
                        return;
                    }
                    $detalle = 'Folio: ' . $folio . ' | Diagnóstico: ' . $detalle;
                    $tipoAusencia = 'medica';
                    $tipoTablaAusencia = 'medica';
                    $mensajeOk = 'Licencia médica registrada correctamente';
                } elseif ($tipo_justificacion === 'vacaciones') {
                    if (empty($fechaInicio) || empty($fechaFin) || empty($detalle)) {
                        echo json_encode(['success' => false, 'error' => 'Para vacaciones se requiere fecha inicio, fecha fin y motivo']);
                        return;
                    }
                    $tipoAusencia = 'otro';
                    $tipoTablaAusencia = 'vacaciones';
                    $mensajeOk = 'Vacaciones registradas correctamente';
                } else { // cuidados_parentales
                    $subtipoCuidados = trim($_POST['tipo_cuidados'] ?? '');
                    if (empty($fechaInicio) || empty($fechaFin) || empty($detalle) || !in_array($subtipoCuidados, ['maternidad', 'paternidad'], true)) {
                        echo json_encode(['success' => false, 'error' => 'Para cuidados maternos/paternos se requiere tipo de cuidado, fechas y motivo']);
                        return;
                    }
                    $tipoAusencia = $subtipoCuidados;
                    $tipoTablaAusencia = 'personal';
                    $detalle = strtoupper($subtipoCuidados) . ': ' . $detalle;
                    $mensajeOk = 'Cuidados maternos/paternos registrados correctamente';
                }

                // Adjuntar la ruta de la evidencia al motivo, ya que la tabla no tiene una columna dedicada.
                if ($soportePath) {
                    $detalle .= ' | Evidencia: ' . $soportePath;
                }

                if (!empty($fundamentoTipo)) {
                    $detalle .= ' | Fundamento: ' . $fundamentoTipo;
                }
                if (empty($fechaInicio) || empty($fechaFin)) {
                    echo json_encode(['success' => false, 'error' => 'Se requieren fechas válidas para la ausencia']);
                    return;
                }
                if (strtotime($fechaFin) < strtotime($fechaInicio)) {
                    echo json_encode(['success' => false, 'error' => 'La fecha fin de licencia no puede ser menor a la fecha inicio']);
                    return;
                }
                
                $data = [
                    'empleado_id' => $empleado_id,
                    'tipo' => $tipoTablaAusencia,
                    'tipo_ausencia' => $tipoAusencia,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'motivo' => $detalle, // El path de la evidencia se concatena aquí
                    'requiere_evidencia' => !empty($soportePath) ? 1 : 0,
                    'activo' => 1
                ];
                
                $nuevoId = $ausenciaModel->create($data);
                $this->_crearValidacionJefe($nuevoId, 'ausencia', $empleado_id, true);

                $tipoAsistenciaUpdate = $tipo_justificacion;
                $asistenciaModel->update($asistencia_id, ['tipo_asistencia' => $tipoAsistenciaUpdate]);
                
                echo json_encode(['success' => true, 'message' => $mensajeOk]);
                return;
                
            } elseif (strpos($tipo_justificacion, 'comision') !== false) {
                error_log("DEBUG: Guardando comisión - tipo: $tipo_justificacion");
                
                require_once 'models/Comision.php';
                $comisionModel = new Comision();

                // Validar que sea un tipo de comisión válido
                $tiposComisionValidos = ['comision_entrada', 'comision_salida', 'comision_todo_dia', 'comision_dia'];
                if (!in_array($tipo_justificacion, $tiposComisionValidos)) {
                    echo json_encode(['success' => false, 'error' => 'Tipo de comisión inválido: ' . $tipo_justificacion]);
                    return;
                }

                $lugarComision = trim($_POST['lugar_comision'] ?? '');
                $motivoComision = trim($_POST['motivo_comision'] ?? $_POST['motivo'] ?? '');
                
                error_log("DEBUG: lugar: $lugarComision, motivo: $motivoComision");
                
                if (empty($lugarComision)) {
                    echo json_encode(['success' => false, 'error' => 'Para comisión se requiere lugar']);
                    return;
                }
                if (empty($motivoComision)) {
                    echo json_encode(['success' => false, 'error' => 'Para comisión se requiere motivo']);
                    return;
                }

                $descripcionComision = $motivoComision;
                if (!empty($fundamentoTipo)) {
                    $descripcionComision .= ' | Fundamento: ' . $fundamentoTipo;
                }
                
                // Obtener la fecha de la incidencia (asistencia)
                $fechaIncidencia = $_POST['fecha'] ?? null;
                
                // Si no hay fecha, obtener de la asistencia
                if (empty($fechaIncidencia)) {
                    $asistenciaData = $asistenciaModel->getById($asistencia_id);
                    $fechaIncidencia = $asistenciaData['fecha'] ?? date('Y-m-d');
                }
                
                // Obtener fechas de la comisión
                $fechaInicioComision = $_POST['fecha_inicio_comision'] ?? $fechaIncidencia;
                $fechaFinComision = $_POST['fecha_fin_comision'] ?? $fechaIncidencia;
                
                // Asegurar que las fechas no estén vacías
                if (empty($fechaInicioComision)) $fechaInicioComision = $fechaIncidencia;
                if (empty($fechaFinComision)) $fechaFinComision = $fechaIncidencia;
                
                $data = [
                    'empleado_id' => $empleado_id,
                    'descripcion' => 'Lugar: ' . $lugarComision . ' | ' . $descripcionComision,
                    'monto' => 0,
                    'fecha_asignacion' => $fechaIncidencia,
                    'fecha_vencimiento' => $fechaFinComision,
                    'fecha_inicio' => $fechaInicioComision,
                    'fecha_fin' => $fechaFinComision,
                    'tipo_comision' => $tipo_justificacion,
                    'requiere_aprobacion' => 1,
                    'evidencia_adjunta' => $soportePath
                ];
                
                $nuevoId = $comisionModel->create($data);
                error_log("DEBUG: Comisión creada ID: $nuevoId, actualizando asistencia_id: $asistencia_id a tipo: $tipo_justificacion");
                
                $asistenciaModel->update($asistencia_id, ['tipo_asistencia' => $tipo_justificacion]);
                
                // Crear validación para el jefe
                $this->_crearValidacionJefe($nuevoId, 'comision', $empleado_id);
                
                // Verificar que se actualizó
                $asistenciaActualizada = $asistenciaModel->getById($asistencia_id);
                error_log("DEBUG: Asistencia actualizada - nuevo tipo: " . ($asistenciaActualizada['tipo_asistencia'] ?? 'SIN DATOS'));
                
                // Si es comisión de todo el día con rango de fechas, crear registros de asistencia para cada fecha
                if ($tipo_justificacion === 'comision_todo_dia' && $fechaInicioComision && $fechaFinComision) {
                    $this->_crearRegistrosAsistenciaComision($empleado_id, $fechaInicioComision, $fechaFinComision, $lugarComision, $motivoComision);
                }
                
                echo json_encode(['success' => true, 'message' => 'Comisión registrada correctamente']);
                return;
            }
            
            // Bloque para justificaciones genéricas (Incidencias)
            $motivoJustificacion = trim($_POST['motivo'] ?? '');
            if (empty($motivoJustificacion)) {
                 echo json_encode(['success' => false, 'error' => 'Se requiere un motivo para la justificación']);
                 return;
            }
            if (!empty($fundamentoTipo)) {
                $motivoJustificacion .= ' | Fundamento: ' . $fundamentoTipo;
            }
            if ($soportePath) {
                $motivoJustificacion .= ' | Evidencia: ' . $soportePath;
            }

            $db = new Database();
            $pdo = $db->getConnection();

            // Si el tipo contiene 'retardo', mantener en tabla retardos (genera sanciones)
            if (strpos($tipo_justificacion, 'retardo') !== false) {
                $stmt = $pdo->prepare("INSERT INTO retardos (
                    empleado_id, fecha, hora_entrada, hora_salida, 
                    minutos_retardo, tipo_retraso, justificado, 
                    motivo_justificacion, evidencia_adjunta, 
                    requiere_validacion_jefe, estado_validacion, asistencia_id, created_at
                ) VALUES (?, ?, ?, ?, 0, ?, 1, ?, ?, 1, 'pendiente', ?, NOW())");

                $stmt->execute([
                    $empleado_id,
                    $asistencia['fecha'],
                    $asistencia['hora_entrada'],
                    $asistencia['hora_salida'],
                    $tipo_justificacion,
                    $motivoJustificacion,
                    $soportePath,
                    $asistencia_id
                ]);

                $nuevoId = $pdo->lastInsertId();
                $this->_crearValidacionJefe($nuevoId, 'retardo', $empleado_id, !empty($soportePath));
            } else {
                // Demás tipos (CLIDDA, PDSEP-SNTE, EYR, DE, F, Falta, etc.) a justificaciones — sin sanciones
                $stmt = $pdo->prepare("INSERT INTO justificaciones (
                    empleado_id, tipo_justificacion, motivo, fecha_inicio, fecha_fin,
                    estatus, asistencia_id, tipo_justificacion_id, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?, NOW())");

                $stmt->execute([
                    $empleado_id,
                    $tipo_justificacion,
                    $motivoJustificacion,
                    $asistencia['fecha'],
                    $asistencia['fecha'],
                    $asistencia_id,
                    $tipo_justificacion_id ?? null
                ]);

                $nuevoId = $pdo->lastInsertId();
                $this->_crearValidacionJefe($nuevoId, 'justificacion', $empleado_id, !empty($soportePath));
            }

            $asistenciaModel->update($asistencia_id, ['tipo_asistencia' => $tipo_justificacion]);
            
            echo json_encode(['success' => true, 'message' => 'Justificación registrada correctamente']);
            
        } catch (Exception $e) {
            error_log("Error en guardarIncidencia: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }

    private function resolverTipoIncidenciaDesdeCatalogo($tipoCatalogo, $tipoIncidenciaActual = null, $tipoIncidenciaAsistencia = null) {
        $tipoDb = $this->normalizarTexto($tipoCatalogo['tipo_incidencia'] ?? '');
        $nombre = $this->normalizarTexto($tipoCatalogo['nombre'] ?? '');
        $tipoActual = $this->normalizarTexto($tipoIncidenciaActual ?? '');
        $tipoAsistencia = $this->normalizarTexto($tipoIncidenciaAsistencia ?? '');

        if ($tipoDb === 'dia_economico' || strpos($nombre, 'dia economico') !== false) {
            return 'dia_economico';
        }

        if ($tipoDb === 'licencia_medica' || strpos($nombre, 'licencia medica') !== false || strpos($nombre, 'ausencia justificada') !== false) {
            return 'licencia_medica';
        }
        if ($tipoDb === 'constancia_tiempo' || strpos($nombre, 'constancia de tiempo') !== false) {
            return 'constancia_tiempo';
        }
        if ($tipoDb === 'vacaciones' || strpos($nombre, 'vacaciones') !== false) {
            return 'vacaciones';
        }
        if ($tipoDb === 'cuidados_parentales' || strpos($nombre, 'cuidado') !== false || strpos($nombre, 'materno') !== false || strpos($nombre, 'paterno') !== false) {
            return 'cuidados_parentales';
        }

        if (in_array($tipoDb, ['comision', 'comision_entrada', 'comision_salida', 'comision_todo_dia'], true) || strpos($nombre, 'comision') !== false) {
            if (in_array($tipoDb, ['comision_entrada', 'comision_salida', 'comision_todo_dia'], true)) {
                return $tipoDb;
            }
            if (in_array($tipoAsistencia, ['comision_entrada', 'comision_salida', 'comision_todo_dia'], true)) {
                return $tipoAsistencia;
            }
            if (in_array($tipoActual, ['comision_entrada', 'comision_salida', 'comision_todo_dia'], true)) {
                return $tipoActual;
            }
            return 'comision_todo_dia';
        }

        // Si tiene un tipo definido en BD que no es de los especiales, usar ese
        if (!empty($tipoDb)) {
            return $tipoDb;
        }

        // Fallback: Si no coincide con tipos específicos pero hay incidencia de asistencia,
        // permitir usar este tipo de justificación tratándolo como el tipo de la incidencia (comisión).
        if (!empty($tipoAsistencia)) {
            return $tipoAsistencia;
        }

        return null;
    }

    private function determinarTipoIncidenciaAsistencia(array $asistencia) {
        $tieneEntrada = !empty($asistencia['hora_entrada']);
        $tieneSalida = !empty($asistencia['hora_salida']);

        if ($tieneEntrada && !$tieneSalida) {
            return 'comision_salida';
        }
        if (!$tieneEntrada && $tieneSalida) {
            return 'comision_entrada';
        }
        return 'comision_todo_dia';
    }

    private function normalizarTexto($valor) {
        $texto = mb_strtolower(trim((string)$valor), 'UTF-8');
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $texto
        );
        return $texto;
    }

    /**
     * Helper para crear una solicitud de validación para el jefe.
     */
    private function _crearValidacionJefe($incidenciaId, $tipoIncidencia, $empleadoId, $evidenciaRequerida = false) {
        try {
            $empleado = $this->empleadoModel->getById($empleadoId);
            if (!$empleado || empty($empleado['jefe_directo_id'])) {
                // No tiene jefe, no se puede crear validación.
                error_log("Empleado ID {$empleadoId} no tiene jefe directo asignado. No se creó validación.");
                return false;
            }

            $jefe_id = $empleado['jefe_directo_id'];

            // Crear la solicitud de validación
            $this->validacionModel->crear([
                'incidencia_id' => $incidenciaId,
                'tipo_incidencia' => $tipoIncidencia,
                'empleado_id' => $empleadoId,
                'jefe_id' => $jefe_id,
                'estado' => 'pendiente',
                'evidencia_requerida' => $evidenciaRequerida
            ]);

            return true;

        } catch (Exception $e) {
            $this->logException($e, ['action' => '_crearValidacionJefe', 'incidencia_id' => $incidenciaId]);
            error_log("Error al crear validación para incidencia {$incidenciaId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear registros de asistencia para comisión de varios días
     */
    private function _crearRegistrosAsistenciaComision($empleadoId, $fechaInicio, $fechaFin, $lugar, $motivo) {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            $fechaActual = new DateTime($fechaInicio);
            $fechaFinObj = new DateTime($fechaFin);
            
            while ($fechaActual <= $fechaFinObj) {
                $fechaStr = $fechaActual->format('Y-m-d');
                
                // Verificar si ya existe un registro para esta fecha
                $stmt = $pdo->prepare("SELECT id FROM asistencia WHERE empleado_id = ? AND fecha = ?");
                $stmt->execute([$empleadoId, $fechaStr]);
                $existente = $stmt->fetch();
                
                if (!$existente) {
                    // Crear nuevo registro de asistencia como comisión
                    $stmt = $pdo->prepare("INSERT INTO asistencia (empleado_id, fecha, tipo_asistencia, hora_entrada, hora_salida, requerio_validacion, observaciones, created_at) VALUES (?, ?, 'comision_todo_dia', NULL, NULL, 0, ?, NOW())");
                    $stmt->execute([$empleadoId, $fechaStr, "Comisión: {$lugar} - {$motivo}"]);
                } else {
                    // Actualizar registro existente a comisión
                    $stmt = $pdo->prepare("UPDATE asistencia SET tipo_asistencia = 'comision_todo_dia', observaciones = ? WHERE id = ?");
                    $stmt->execute(["Comisión: {$lugar} - {$motivo}", $existente['id']]);
                }
                
                $fechaActual->modify('+1 day');
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error al crear registros de asistencia para comisión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar incidencia (tipo_asistencia) desde el modal de empleado
     */
    public function actualizarIncidencia() {
        error_log("DEBUG: actualizarIncidencia INICIADO");
        
        // Limpiar cualquier salida previa
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $incidencia_id = $_POST['incidencia_id'] ?? null;
            error_log("DEBUG: incidencia_id = " . $incidencia_id);
            
            if (!$incidencia_id) {
                throw new Exception('ID de incidencia no proporcionado');
            }

            // CSRF validation - skip if no token (excluded route)
            $csrf = $_POST['csrf_token'] ?? '';
            if ($csrf) {
                if (!class_exists('Csrf')) {
                    require_once __DIR__ . '/../helpers/Csrf.php';
                }
                if (!\Csrf::validate($csrf)) {
                    throw new Exception('Token CSRF inválido.');
                }
            }

            // Normalizar entrada del modal legacy para reutilizar el flujo principal.
            $_POST['asistencia_id'] = $_POST['asistencia_id'] ?? $incidencia_id;
            $_POST['tipo_justificacion'] = $_POST['tipo_justificacion'] ?? 'comision_entrada';
            if (($_POST['tipo_justificacion'] ?? '') === 'comision_dia') {
                $_POST['tipo_justificacion'] = 'comision_todo_dia';
            }
            if (($_POST['tipo_justificacion'] ?? '') === 'retardo') {
                throw new Exception('Para incidencias por definir debe seleccionar comisión, día económico o licencia médica.');
            }

            // Compatibilidad de nombres de campos entre modal legacy y flujo unificado.
            if (empty($_POST['lugar_comision']) && !empty($_POST['lugar'])) {
                $_POST['lugar_comision'] = $_POST['lugar'];
            }
            if (empty($_POST['motivo_comision']) && !empty($_POST['motivo'])) {
                $_POST['motivo_comision'] = $_POST['motivo'];
            }
            $_POST['motivo'] = $_POST['motivo'] ?? ($_POST['motivo_comision'] ?? '');
            $_POST['dias_solicitados'] = $_POST['dias_solicitados'] ?? 1;

            // Si no viene empleado_id/fecha en el modal legacy, obtenerlos desde asistencia.
            if (empty($_POST['empleado_id']) || empty($_POST['fecha'])) {
                require_once 'models/Asistencia.php';
                $asistenciaModel = new Asistencia();
                $asistencia = $asistenciaModel->getById((int)$incidencia_id);
                if (!$asistencia) {
                    throw new Exception('Asistencia no encontrada');
                }
                $_POST['empleado_id'] = $_POST['empleado_id'] ?? $asistencia['empleado_id'];
                $_POST['fecha'] = $_POST['fecha'] ?? $asistencia['fecha'];
            }

            // El flujo unificado aplica reglas por tipo (día económico, licencia, comisiones).
            $this->guardarIncidencia();
        } catch (Exception $e) {
            error_log("Error en actualizarIncidencia: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Eliminar justificación anterior cuando se cambia de tipo
     */
    private function eliminarJustificacionAnterior($asistencia_id, $tipoActual, $empleado_id) {
        $db = new Database();
        $pdo = $db->getConnection();
        
        try {
            switch ($tipoActual) {
                case 'licencia_medica':
                    // Obtener las fechas de la licencia para revertir todas las asistencias en el rango
                    $stmtLic = $pdo->prepare("SELECT fecha_inicio, fecha_fin FROM licencias_medicas WHERE incidencia_id = ? AND empleado_id = ?");
                    $stmtLic->execute([$asistencia_id, $empleado_id]);
                    $licencia = $stmtLic->fetch();
                    
                    if ($licencia) {
                        // Revertir todas las asistencias en el rango de fechas
                        $stmtUpd = $pdo->prepare("
                            UPDATE asistencia 
                            SET tipo_asistencia = 'por_definir', updated_at = NOW() 
                            WHERE empleado_id = ? 
                            AND fecha BETWEEN ? AND ?
                            AND tipo_asistencia = 'licencia_medica'
                        ");
                        $stmtUpd->execute([$empleado_id, $licencia['fecha_inicio'], $licencia['fecha_fin']]);
                    }
                    
                    // Eliminar de licencias_medicas
                    $stmt = $pdo->prepare("DELETE FROM licencias_medicas WHERE incidencia_id = ? AND empleado_id = ?");
                    $stmt->execute([$asistencia_id, $empleado_id]);
                    break;
                    
                case 'dia_economico':
                    // Obtener las fechas del día económico
                    $stmtEco = $pdo->prepare("
                        SELECT fecha, dias_solicitados FROM dias_economicos 
                        WHERE empleado_id = ? AND DATE(fecha) = (
                            SELECT DATE(fecha) FROM asistencia WHERE id = ?
                        ) LIMIT 1
                    ");
                    $stmtEco->execute([$empleado_id, $asistencia_id]);
                    $diaEco = $stmtEco->fetch();
                    
                    if ($diaEco) {
                        $fechaFinEco = date('Y-m-d', strtotime($diaEco['fecha'] . ' + ' . ($diaEco['dias_solicitados'] - 1) . ' days'));
                        // Revertir todas las asistencias en el rango de fechas
                        $stmtUpd = $pdo->prepare("
                            UPDATE asistencia 
                            SET tipo_asistencia = 'por_definir', updated_at = NOW() 
                            WHERE empleado_id = ? 
                            AND fecha BETWEEN ? AND ?
                            AND tipo_asistencia = 'dia_economico'
                        ");
                        $stmtUpd->execute([$empleado_id, $diaEco['fecha'], $fechaFinEco]);
                    }
                    
                    // Eliminar de dias_economicos
                    $stmt = $pdo->prepare("DELETE FROM dias_economicos WHERE id IN (
                        SELECT id FROM (
                            SELECT id FROM dias_economicos 
                            WHERE empleado_id = ? AND DATE(fecha) = (
                                SELECT DATE(fecha) FROM asistencia WHERE id = ?
                            ) LIMIT 1
                        ) AS t
                    )");
                    $stmt->execute([$empleado_id, $asistencia_id]);
                    break;
                    
                case 'vacaciones':
                case 'cuidados_parentales':
                    // Eliminar de ausencias
                    $stmt = $pdo->prepare("DELETE FROM ausencias WHERE empleado_id = ? AND DATE(fecha_inicio) = (
                        SELECT DATE(fecha) FROM asistencia WHERE id = ?
                    )");
                    $stmt->execute([$empleado_id, $asistencia_id]);
                    break;
                    
                default:
                    // Para comisiones y otros tipos en retardos
                    if (strpos($tipoActual, 'comision') !== false) {
                        $stmt = $pdo->prepare("DELETE FROM comisiones WHERE empleado_id = ? AND DATE(fecha_asignacion) = (
                            SELECT DATE(fecha) FROM asistencia WHERE id = ?
                        )");
                        $stmt->execute([$empleado_id, $asistencia_id]);
                    } else {
                        // Revertir retardo a NO justificado (preserva el registro para evitar duplicados al recalcular)
                        $stmt = $pdo->prepare("UPDATE retardos SET justificado = 0, tipo_justificacion_id = NULL, motivo_justificacion = NULL, soporte = NULL, aprobado_por = NULL, fecha_aprobacion = NULL WHERE asistencia_id = ? AND empleado_id = ? AND justificado = 1");
                        $stmt->execute([$asistencia_id, $empleado_id]);
                    }
                    break;
            }
            
            // Regresar a por_definir
            $stmt = $pdo->prepare("UPDATE asistencia SET tipo_asistencia = 'por_definir', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$asistencia_id]);
            
        } catch (Exception $e) {
            error_log("Error al eliminar justificación anterior: " . $e->getMessage());
        }
    }
    
    /**
     * Procesar solicitud directa de comisión sin asistencia previa
     * Crea registro en comisiones y en asistencia para los días solicitados
     */
    private function procesarComisionDirecta($empleado_id, $tipo_justificacion) {
        try {
            $lugarComision = trim($_POST['lugar_comision'] ?? '');
            $motivoComision = trim($_POST['motivo_comision'] ?? $_POST['motivo'] ?? '');
            $fechaInicioComision = $_POST['fecha_inicio_comision'] ?? date('Y-m-d');
            $fechaFinComision = $_POST['fecha_fin_comision'] ?? $fechaInicioComision;
            $fundamentoTipo = trim($_POST['fundamento_tipo'] ?? '');
            
            error_log("DEBUG procesarComisionDirecta - empleado: $empleado_id, tipo: $tipo_justificacion, inicio: $fechaInicioComision, fin: $fechaFinComision");
            
            // Validar campos requeridos
            if (empty($lugarComision)) {
                echo json_encode(['success' => false, 'error' => 'Para comisión se requiere lugar']);
                return;
            }
            if (empty($motivoComision)) {
                echo json_encode(['success' => false, 'error' => 'Para comisión se requiere motivo']);
                return;
            }
            if (empty($fechaInicioComision)) {
                echo json_encode(['success' => false, 'error' => 'Para comisión se requiere fecha de inicio']);
                return;
            }
            
            // Asegurar que las fechas no estén vacías
            if (empty($fechaFinComision)) $fechaFinComision = $fechaInicioComision;
            
            // Validar rango de fechas
            if (strtotime($fechaFinComision) < strtotime($fechaInicioComision)) {
                echo json_encode(['success' => false, 'error' => 'La fecha fin no puede ser menor a la fecha inicio']);
                return;
            }
            
            // Validar tipo de comisión
            $tiposComisionValidos = ['comision_entrada', 'comision_salida', 'comision_todo_dia', 'comision_dia'];
            if (!in_array($tipo_justificacion, $tiposComisionValidos)) {
                echo json_encode(['success' => false, 'error' => 'Tipo de comisión inválido']);
                return;
            }
            
            // Determinar tipo de asistencia según el tipo de comisión
            $tipoAsistencia = $tipo_justificacion;
            
            $descripcionComision = $motivoComision;
            if (!empty($fundamentoTipo)) {
                $descripcionComision .= ' | Fundamento: ' . $fundamentoTipo;
            }
            
            // Crear registro en tabla comisiones
            require_once 'models/Comision.php';
            $comisionModel = new Comision();
            
            $data = [
                'empleado_id' => $empleado_id,
                'descripcion' => 'Lugar: ' . $lugarComision . ' | ' . $descripcionComision,
                'monto' => 0,
                'fecha_asignacion' => date('Y-m-d'),
                'fecha_vencimiento' => $fechaFinComision,
                'fecha_inicio' => $fechaInicioComision,
                'fecha_fin' => $fechaFinComision,
                'tipo_comision' => $tipo_justificacion,
                'requiere_aprobacion' => 1,
                'evidencia_adjunta' => null
            ];
            
            $nuevoId = $comisionModel->create($data);
            error_log("DEBUG: Comisión directa creada ID: $nuevoId");
            
            if (!$nuevoId) {
                echo json_encode(['success' => false, 'error' => 'No se pudo crear la comisión']);
                return;
            }
            
            // Crear validación para el jefe
            $this->_crearValidacionJefe($nuevoId, 'comision', $empleado_id);
            
            // Crear/actualizar registros de asistencia para cada fecha en el rango
            $db = new Database();
            $pdo = $db->getConnection();
            
            $fechaActual = new DateTime($fechaInicioComision);
            $fechaFinObj = new DateTime($fechaFinComision);
            $fechasCreadas = [];
            
            while ($fechaActual <= $fechaFinObj) {
                $fechaStr = $fechaActual->format('Y-m-d');
                
                // Verificar si ya existe un registro de asistencia para esta fecha
                $stmt = $pdo->prepare("SELECT id, tipo_asistencia FROM asistencia WHERE empleado_id = ? AND fecha = ?");
                $stmt->execute([$empleado_id, $fechaStr]);
                $existente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existente) {
                    // Crear nuevo registro de asistencia como comisión
                    $stmt = $pdo->prepare("INSERT INTO asistencia (empleado_id, fecha, tipo_asistencia, hora_entrada, hora_salida, requerio_validacion, observaciones, created_at) VALUES (?, ?, ?, NULL, NULL, 0, ?, NOW())");
                    $stmt->execute([$empleado_id, $fechaStr, $tipoAsistencia, "Comisión: {$lugarComision} - {$motivoComision}"]);
                    $fechasCreadas[] = $fechaStr;
                } else {
                    // Actualizar registro existente a tipo comisión
                    $stmt = $pdo->prepare("UPDATE asistencia SET tipo_asistencia = ?, observaciones = CONCAT(COALESCE(observaciones, ''), ' | Comisión: ', ?) WHERE id = ?");
                    $stmt->execute([$tipoAsistencia, "{$lugarComision} - {$motivoComision}", $existente['id']]);
                    $fechasCreadas[] = $fechaStr;
                }
                
                $fechaActual->modify('+1 day');
            }
            
            error_log("DEBUG: Comisión directa - Asistencia actualizada para fechas: " . implode(', ', $fechasCreadas));
            
            echo json_encode([
                'success' => true, 
                'message' => 'Comisión solicitada correctamente. Pendiente de aprobación por el jefe.',
                'comision_id' => $nuevoId,
                'fechas' => $fechasCreadas
            ]);
            
        } catch (Exception $e) {
            error_log("Error en procesarComisionDirecta: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Procesar solicitud directa de vacaciones sin asistencia previa
     */
    private function procesarVacacionesDirectas($empleado_id) {
        try {
            $fechaInicio = $_POST['fecha_inicio_vacaciones'] ?? $_POST['fecha_inicio'] ?? date('Y-m-d');
            $fechaFin = $_POST['fecha_fin_vacaciones'] ?? $_POST['fecha_fin'] ?? $fechaInicio;
            $motivo = trim($_POST['motivo'] ?? $_POST['motivo_vacaciones'] ?? '');
            
            error_log("DEBUG procesarVacacionesDirectas - empleado: $empleado_id, inicio: $fechaInicio, fin: $fechaFin");
            
            if (empty($fechaInicio) || empty($fechaFin)) {
                echo json_encode(['success' => false, 'error' => 'Se requieren fechas de inicio y fin para vacaciones']);
                return;
            }
            
            if (empty($motivo)) {
                echo json_encode(['success' => false, 'error' => 'Se requiere el motivo o destino de las vacaciones']);
                return;
            }
            
            if (strtotime($fechaFin) < strtotime($fechaInicio)) {
                echo json_encode(['success' => false, 'error' => 'La fecha fin no puede ser menor a la fecha inicio']);
                return;
            }
            
            require_once 'models/Ausencia.php';
            $ausenciaModel = new Ausencia();
            
            $data = [
                'empleado_id' => $empleado_id,
                'tipo' => 'vacaciones',
                'tipo_ausencia' => 'otro',
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'motivo' => $motivo,
                'requiere_evidencia' => 0,
                'activo' => 1
            ];
            
            $nuevoId = $ausenciaModel->create($data);
            error_log("DEBUG: Vacaciones creadas ID: $nuevoId");
            
            if (!$nuevoId) {
                echo json_encode(['success' => false, 'error' => 'No se pudieron registrar las vacaciones']);
                return;
            }
            
            // Crear validación para el jefe
            $this->_crearValidacionJefe($nuevoId, 'ausencia', $empleado_id);
            
            // Crear/actualizar registros de asistencia para cada fecha en el rango
            $db = new Database();
            $pdo = $db->getConnection();
            
            $fechaActual = new DateTime($fechaInicio);
            $fechaFinObj = new DateTime($fechaFin);
            $fechasCreadas = [];
            
            while ($fechaActual <= $fechaFinObj) {
                $fechaStr = $fechaActual->format('Y-m-d');
                
                $stmt = $pdo->prepare("SELECT id FROM asistencia WHERE empleado_id = ? AND fecha = ?");
                $stmt->execute([$empleado_id, $fechaStr]);
                $existente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existente) {
                    $stmt = $pdo->prepare("INSERT INTO asistencia (empleado_id, fecha, tipo_asistencia, hora_entrada, hora_salida, requerio_validacion, observaciones, created_at) VALUES (?, ?, 'vacaciones', NULL, NULL, 0, ?, NOW())");
                    $stmt->execute([$empleado_id, $fechaStr, "Vacaciones: $motivo"]);
                    $fechasCreadas[] = $fechaStr;
                } else {
                    $stmt = $pdo->prepare("UPDATE asistencia SET tipo_asistencia = 'vacaciones', observaciones = CONCAT(COALESCE(observaciones, ''), ' | Vacaciones: ', ?) WHERE id = ?");
                    $stmt->execute([$motivo, $existente['id']]);
                    $fechasCreadas[] = $fechaStr;
                }
                
                $fechaActual->modify('+1 day');
            }
            
            error_log("DEBUG: Vacaciones - Asistencia actualizada para fechas: " . implode(', ', $fechasCreadas));
            
            echo json_encode([
                'success' => true,
                'message' => 'Vacaciones solicitadas correctamente. Pendiente de aprobación.',
                'vacaciones_id' => $nuevoId,
                'fechas' => $fechasCreadas
            ]);
            
        } catch (Exception $e) {
            error_log("Error en procesarVacacionesDirectas: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Procesar solicitud directa de cuidados maternos/paternos sin asistencia previa
     */
    private function procesarCuidadosDirectos($empleado_id, $tipo_justificacion) {
        try {
            $fechaInicio = $_POST['fecha_inicio_cuidados'] ?? $_POST['fecha_inicio'] ?? date('Y-m-d');
            $fechaFin = $_POST['fecha_fin_cuidados'] ?? $_POST['fecha_fin'] ?? $fechaInicio;
            $motivo = trim($_POST['motivo'] ?? '');
            $tipoCuidados = $_POST['tipo_cuidados'] ?? ($tipo_justificacion === 'cuidados_maternos' ? 'maternidad' : 'paternidad');
            
            error_log("DEBUG procesarCuidadosDirectos - empleado: $empleado_id, tipo: $tipo_justificacion, inicio: $fechaInicio, fin: $fechaFin");
            
            if (empty($fechaInicio) || empty($fechaFin)) {
                echo json_encode(['success' => false, 'error' => 'Se requieren fechas de inicio y fin']);
                return;
            }
            
            if (empty($motivo)) {
                echo json_encode(['success' => false, 'error' => 'Se requiere el motivo del permiso de cuidados']);
                return;
            }
            
            if (strtotime($fechaFin) < strtotime($fechaInicio)) {
                echo json_encode(['success' => false, 'error' => 'La fecha fin no puede ser menor a la fecha inicio']);
                return;
            }
            
            require_once 'models/Ausencia.php';
            $ausenciaModel = new Ausencia();
            
            $tipoTabla = $tipo_justificacion === 'cuidados_maternos' ? 'personal' : 'personal';
            $tipoAusencia = $tipoCuidados === 'maternidad' ? 'maternidad' : 'paternidad';
            $detalle = strtoupper($tipoCuidados) . ': ' . $motivo;
            
            $data = [
                'empleado_id' => $empleado_id,
                'tipo' => $tipoTabla,
                'tipo_ausencia' => $tipoAusencia,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'motivo' => $detalle,
                'requiere_evidencia' => 1,
                'activo' => 1
            ];
            
            $nuevoId = $ausenciaModel->create($data);
            error_log("DEBUG: Cuidados creados ID: $nuevoId");
            
            if (!$nuevoId) {
                echo json_encode(['success' => false, 'error' => 'No se pudieron registrar los permisos de cuidados']);
                return;
            }
            
            // Crear validación para el jefe
            $this->_crearValidacionJefe($nuevoId, 'ausencia', $empleado_id, true);
            
            // Actualizar asistencia para cada fecha en el rango
            $db = new Database();
            $pdo = $db->getConnection();
            
            $fechaActual = new DateTime($fechaInicio);
            $fechaFinObj = new DateTime($fechaFin);
            $fechasCreadas = [];
            
            while ($fechaActual <= $fechaFinObj) {
                $fechaStr = $fechaActual->format('Y-m-d');
                
                $stmt = $pdo->prepare("SELECT id FROM asistencia WHERE empleado_id = ? AND fecha = ?");
                $stmt->execute([$empleado_id, $fechaStr]);
                $existente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existente) {
                    $stmt = $pdo->prepare("INSERT INTO asistencia (empleado_id, fecha, tipo_asistencia, hora_entrada, hora_salida, requerio_validacion, observaciones, created_at) VALUES (?, ?, ?, NULL, NULL, 0, ?, NOW())");
                    $stmt->execute([$empleado_id, $fechaStr, $tipo_justificacion, "Permiso de cuidados: $motivo"]);
                    $fechasCreadas[] = $fechaStr;
                } else {
                    $stmt = $pdo->prepare("UPDATE asistencia SET tipo_asistencia = ?, observaciones = CONCAT(COALESCE(observaciones, ''), ' | Permiso de cuidados: ', ?) WHERE id = ?");
                    $stmt->execute([$tipo_justificacion, $motivo, $existente['id']]);
                    $fechasCreadas[] = $fechaStr;
                }
                
                $fechaActual->modify('+1 day');
            }
            
            error_log("DEBUG: Cuidados - Asistencia actualizada para fechas: " . implode(', ', $fechasCreadas));
            
            echo json_encode([
                'success' => true,
                'message' => 'Solicitud de cuidados registrada correctamente. Pendiente de aprobación.',
                'cuidados_id' => $nuevoId,
                'fechas' => $fechasCreadas
            ]);
            
        } catch (Exception $e) {
            error_log("Error en procesarCuidadosDirectos: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Procesar solicitud directa de Constancia de Tiempo sin asistencia previa
     * Según Manual de Normas SEP - Sección 9.4 Constancia de Servicios
     */
    private function procesarConstanciaDirecta($empleado_id) {
        try {
            $tipoConstancia = $_POST['tipo_constancia'] ?? 'laboral';
            $institucionEmisora = trim($_POST['institucion_emisora'] ?? '');
            $motivo = trim($_POST['motivo'] ?? '');
            
            error_log("DEBUG procesarConstanciaDirecta - empleado: $empleado_id, tipo: $tipoConstancia");
            
            if (empty($institucionEmisora)) {
                echo json_encode(['success' => false, 'error' => 'Se requiere el nombre de la institución receptora']);
                return;
            }
            
            require_once 'models/ConstanciaTiempo.php';
            $constanciaModel = new ConstanciaTiempo();
            
            $tiposLabel = [
                'laboral' => 'Constancia Laboral',
                'antiguedad' => 'Constancia de Antigüedad',
                'servicios' => 'Constancia de Servicios',
                'otra' => 'Otra Constancia'
            ];
            
            $folioConstancia = 'CT-' . date('Ymd') . '-' . str_pad($empleado_id, 4, '0', STR_PAD_LEFT);
            
            $data = [
                'empleado_id' => $empleado_id,
                'tipo_justificacion_id' => null,
                'fecha_inicio' => date('Y-m-d'),
                'fecha_fin' => date('Y-m-d'),
                'dias_solicitados' => 1,
                'tipo_constancia' => $tipoConstancia,
                'folio_constancia' => $folioConstancia,
                'motivo' => $motivo,
                'institucion_emisora' => $institucionEmisora,
                'estatus' => 'pendiente'
            ];
            
            $nuevoId = $constanciaModel->create($data);
            error_log("DEBUG: Constancia de Tiempo creada ID: $nuevoId");
            
            if (!$nuevoId) {
                echo json_encode(['success' => false, 'error' => 'No se pudo registrar la solicitud de constancia']);
                return;
            }
            
            $this->_crearValidacionJefe($nuevoId, 'constancia_tiempo', $empleado_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Constancia de Tiempo solicitada correctamente. Pendiente de aprobación.',
                'constancia_id' => $nuevoId,
                'folio' => $folioConstancia,
                'tipo' => $tiposLabel[$tipoConstancia] ?? $tipoConstancia
            ]);
            
        } catch (Exception $e) {
            error_log("Error en procesarConstanciaDirecta: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
}
?>
