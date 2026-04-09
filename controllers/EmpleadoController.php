<?php
require_once 'models/Empleado.php';
require_once 'models/Asistencia.php';
require_once 'models/Retardo.php';
require_once 'models/Comision.php';
require_once 'models/Ausencia.php';
require_once 'models/Sancion.php';
require_once 'models/DiasEconomicos.php';
require_once 'helpers/RfcCurpHelper.php';
require_once 'helpers/RequestValidator.php';
require_once 'helpers/Csrf.php';
require_once __DIR__ . '/BaseController.php';

class EmpleadoController extends BaseController {
    private $empleadoModel;
    private $asistenciaModel;
    private $retardoModel;
    private $comisionModel;
    private $ausenciaModel;
    private $sancionModel;
    private $diasEconomicosModel;

    public function __construct() {
        // Verificar autenticación para todas las acciones
        parent::__construct();
        $this->requireAuth();

        $this->empleadoModel = new Empleado();
        $this->asistenciaModel = new Asistencia();
        $this->retardoModel = new Retardo();
        $this->comisionModel = new Comision();
        $this->ausenciaModel = new Ausencia();
        $this->sancionModel = new Sancion();
        $this->diasEconomicosModel = new DiasEconomicos();
    }

    public function index() {
        // Verificar autenticación
        $this->requireAuth();
        
        // Verificar si es petición AJAX
        $isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
        
        // Capturar término de búsqueda
        $search = $_GET['search'] ?? $_GET['q'] ?? '';
        
        // Obtener datos del usuario logueado
        $rolActual = strtolower($_SESSION['rol'] ?? '');
        $empleadoIdUsuario = $_SESSION['empleado_id'] ?? null;
        
        // Cargar todos los empleados
        $allEmpleados = $this->empleadoModel->getAll();
        
        // Filtrar según el rol del usuario
        if ($rolActual === 'usuario') {
            // Usuario normal: solo ve su propio registro de empleado
            if ($empleadoIdUsuario) {
                $allEmpleados = array_filter($allEmpleados, function($emp) use ($empleadoIdUsuario) {
                    return (int)$emp['id'] === (int)$empleadoIdUsuario;
                });
            } else {
                $allEmpleados = [];
            }
        } elseif ($rolActual === 'jefe') {
            // Jefe: solo ve empleados asignados a él (jefe_directo_id)
            if ($empleadoIdUsuario) {
                $allEmpleados = array_filter($allEmpleados, function($emp) use ($empleadoIdUsuario) {
                    return (int)($emp['jefe_directo_id'] ?? 0) === (int)$empleadoIdUsuario;
                });
            } else {
                $allEmpleados = [];
            }
        }
        // Admin y Superadmin ven todos los empleados (sin filtro)
        
        // Filtrar si hay búsqueda
        if (!empty($search)) {
            $empleados = array_filter($allEmpleados, function($emp) use ($search) {
                return stripos($emp['id'] ?? '', $search) !== false ||
                       stripos($emp['nombre'] ?? '', $search) !== false || 
                       stripos($emp['apellido'] ?? '', $search) !== false ||
                       stripos($emp['area'] ?? '', $search) !== false ||
                       stripos($emp['rfc'] ?? '', $search) !== false;
            });
        } else {
            $empleados = $allEmpleados;
        }
        
        // Enriquecer datos para las cards (Estado de asistencia hoy)
        $fechaHoy = date('Y-m-d');
        foreach ($empleados as &$emp) {
            $asistencias = $this->asistenciaModel->getByEmpleado($emp['id'], $fechaHoy, $fechaHoy);
            $emp['status_asistencia'] = 'ausente';
            $emp['hora_entrada'] = '';
            
            if (!empty($asistencias)) {
                foreach ($asistencias as $asis) {
                    if (!empty($asis['hora_entrada'])) {
                        $emp['status_asistencia'] = !empty($asis['hora_salida']) ? 'salida' : 'presente';
                        $emp['hora_entrada'] = substr($asis['hora_entrada'], 0, 5);
                        break;
                    }
                }
            }
        }
        unset($emp);
        
        // Si es petición AJAX, devolver JSON
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'empleados' => array_values($empleados),
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => count($empleados),
                    'total_pages' => 1,
                    'has_next' => false,
                    'has_prev' => false
                ],
                'filters' => [
                    'search' => $search,
                    'area' => '',
                    'jerarquia' => ''
                ]
            ]);
            return;
        }
        
        // Renderizar vista con layout
        ob_start();
        require 'views/empleados/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function getDatosCompletos($id) {
        $this->resumenCompleto($id);
    }
    
    public function show($id) {
        $empleado = $this->empleadoModel->getById($id);
        if (!$empleado) {
            $this->redirect(BASE_URL . '/empleados?error=not_found');
        }

        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $asistencias = $this->asistenciaModel->getByEmpleado($id, $fecha_inicio, $fecha_fin);
        
        // Solo retardos (retardo_menor, retardo_mayor, falta)
        $retardos = $this->retardoModel->getRetardosByEmpleado($id);
        
        // Solo justificaciones/incidencias (comisiones, días económicos, vacaciones, licencias, cuidados)
        $incidencias = $this->retardoModel->getIncidenciasByEmpleado($id);
        
        // Debug: verificar qué devuelve getIncidenciasByEmpleado
        error_log("DEBUG - getIncidenciasByEmpleado devuelve: " . gettype($incidencias) . " - count: " . (is_array($incidencias) ? count($incidencias) : 'N/A'));
        
        // Combinar TODOS los registros para la pestaña Asistencia
        $historialCompleto = [];
        
        // 1. Agregar asistencia
        foreach ($asistencias as $a) {
            $historialCompleto[] = [
                'id' => $a['id'],
                'fecha' => $a['fecha'],
                'hora_entrada' => $a['hora_entrada'] ?? null,
                'hora_salida' => $a['hora_salida'] ?? null,
                'tipo' => $a['tipo_asistencia'] ?? 'normal',
                'fuente' => 'asistencia',
                'justificado' => 1,
                'minutos' => null
            ];
        }
        
        // 2. Agregar retardos
        foreach ($retardos as $r) {
            $tipoRetardo = $r['tipo_incidencia'] ?? $r['tipo_retraso'] ?? 'retardo_menor';
            $historialCompleto[] = [
                'id' => $r['id'],
                'fecha' => $r['fecha'],
                'hora_entrada' => $r['hora_entrada'] ?? null,
                'hora_salida' => $r['hora_salida'] ?? null,
                'tipo' => $tipoRetardo,
                'fuente' => 'retardos',
                'justificado' => $r['justificado'] ?? 0,
                'minutos' => $r['minutos_retardo'] ?? 0
            ];
        }
        
        // 3. Agregar incidencias/justificaciones
        foreach ($incidencias as $i) {
            $tipoIncidencia = $i['tipo_incidencia'] ?? 'normal';
            $historialCompleto[] = [
                'id' => $i['id'],
                'fecha' => $i['fecha'],
                'hora_entrada' => $i['hora_entrada'] ?? null,
                'hora_salida' => $i['hora_salida'] ?? null,
                'tipo' => $tipoIncidencia,
                'fuente' => 'incidencias',
                'justificado' => $i['justificado'] ?? ($i['estado_validacion_jefe'] === 'aprobado' ? 1 : 0),
                'minutos' => $i['minutos_retardo'] ?? 0
            ];
        }
        
        // Ordenar por fecha descendente
        usort($historialCompleto, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
        
        // Registros de asistencia por justificar (tipo_asistencia = 'por_definir')
        $asistenciasPorDefinir = $this->asistenciaModel->getByEmpleadoPorDefinir($id);
        
        $ausencias = $this->ausenciaModel->getByEmpleado($id);
        $sanciones = $this->sancionModel->getByEmpleado($id);
        
        // Obtener información de días económicos disponibles
        require_once 'models/DiasEconomicos.php';
        $diasEcoModel = new DiasEconomicos();
        $diasEcoInfo = $diasEcoModel->getDiasDisponiblesDetallado($id);

        require 'views/empleados/show.php';
    }

    public function create() {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('empleados_crear')) {
            $_SESSION['error'] = 'No tienes permiso para crear empleados.';
            $this->redirect(BASE_URL . '/empleados');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF token
            $csrfToken = $_POST['csrf_token'] ?? $_POST['_token'] ?? null;
            if (!Csrf::validate($csrfToken)) {
                $_SESSION['error'] = 'Token CSRF inválido. Por favor, recargue la página y reintente.';
                $this->redirect(BASE_URL . '/empleados/create?error=csrf');
                return;
            }
            
            // Validar datos del formulario
            $data = [
                'nombre' => trim(mb_substr($_POST['nombres'] ?? $_POST['nombre_completo'] ?? '', 0, 100)),
                'apellido' => trim(mb_substr(($_POST['primer_apellido'] ?? '') . ' ' . ($_POST['segundo_apellido'] ?? ''), 0, 100)),
                'rfc' => trim($_POST['rfc'] ?? ''),
                'curp' => trim($_POST['curp'] ?? ''),
                'area' => trim(mb_substr($_POST['area'] ?? '', 0, 100)),
                'area_fisica' => trim(mb_substr($_POST['area_fisica'] ?? '', 0, 100)),
                'jerarquia' => trim($_POST['jerarquia'] ?? ''),
                'sexo' => trim($_POST['sexo'] ?? ''),
                'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
                'entidad_federativa' => trim($_POST['entidad_federativa'] ?? ''),
                'clave_depto' => trim(mb_substr($_POST['clave_depto'] ?? '', 0, 50)),
                'jefe_directo_id' => trim($_POST['jefe_directo_id'] ?? ''),
                'jefe_directo_clave' => trim($_POST['jefe_directo_id'] ?? '')
            ];
            
            // Validar usando RequestValidator
            $errors = RequestValidator::validateEmpleadoData($data);
            
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->redirect(BASE_URL . '/empleados/create?error=validation');
            }
            
            // Incluir huella capturada si existe
            if (isset($_POST['captured_fingerprint']) && !empty($_POST['captured_fingerprint'])) {
                $data['huella_dactilar'] = $_POST['captured_fingerprint'];
            }

            // Subir foto si existe
            if (isset($_FILES['foto_cara']) && $_FILES['foto_cara']['error'] == 0) {
                $target_dir = "uploads/fotos_empleados/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_extension = strtolower(pathinfo($_FILES["foto_cara"]["name"], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = 'empleado_' . time() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["foto_cara"]["tmp_name"], $target_file)) {
                        $data['foto_cara'] = $target_file;
                    }
                }
            }

            // Crear empleado
            if ($empleadoId = $this->empleadoModel->create($data)) {
                // Procesar registro biométrico si se solicitó
                if (isset($_POST['registrar_huella']) && $_POST['registrar_huella'] == '1') {
                    $this->procesarRegistroBiometrico($empleadoId, $_POST);
                }
                
                $this->redirect(BASE_URL . '/empleados?success=created');
            } else {
                $this->redirect(BASE_URL . '/empleados?error=create_failed');
            }
        } else {
            // Cargar dispositivos biométricos para el formulario
            try {
                $dispositivoModel = new DispositivoBiometrico();
                $dispositivos = $dispositivoModel->getActivos();
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'create', 'loading_dispositivos']);
                $dispositivos = []; // Array vacío para que no falle el formulario
            }
            
            require 'views/empleados/create.php';
        }
    }

    public function edit($id = null) {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('empleados_editar')) {
            $_SESSION['error'] = 'No tienes permiso para editar empleados.';
            $this->redirect(BASE_URL . '/empleados');
            return;
        }
        
        // Si no viene ID de la ruta, obtenerlo del POST
        if ($id === null && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
        }
        
        if (!$id) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $this->jsonResponse(['success' => false, 'error' => 'ID de empleado no proporcionado']);
                return;
            }
            $this->redirect(BASE_URL . '/empleados?error=not_found');
            return;
        }
        
        // Determinar si es una solicitud AJAX
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        
         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF token
            $csrfToken = $_POST['csrf_token'] ?? $_POST['_token'] ?? null;
            if (!Csrf::validate($csrfToken)) {
                if ($isAjax) {
                    $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido. Por favor, recargue la página y reintente.';
                $this->redirect(BASE_URL . '/empleados/edit/' . $id . '?error=csrf');
                return;
            }
            
            // Mapear campos del formulario a la base de datos
            $data = [
                'nombre' => trim(mb_substr($_POST['nombre'] ?? '', 0, 100)),
                'apellido' => trim(mb_substr($_POST['apellido'] ?? '', 0, 100)),
                'rfc' => trim($_POST['rfc'] ?? ''),
                'curp' => trim($_POST['curp'] ?? ''),
                'area' => trim(mb_substr($_POST['area'] ?? '', 0, 100)),
                'area_fisica' => trim(mb_substr($_POST['area_fisica'] ?? '', 0, 100)),
                'jerarquia' => trim($_POST['jerarquia'] ?? ''),
                'sexo' => trim($_POST['sexo'] ?? ''),
                'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
                'entidad_federativa' => trim($_POST['entidad_federativa'] ?? ''),
                'puesto' => trim(mb_substr($_POST['puesto'] ?? '', 0, 100)),
                'clave_depto' => trim(mb_substr($_POST['clave_depto'] ?? '', 0, 50)),
                'jefe_directo_id' => null,
                'jefe_directo_clave' => ''
            ];
            
            // Si se seleccionó un jefe directo, guardar la clave en ambos campos
            if (!empty($_POST['jefe_directo_id'])) {
                $data['jefe_directo_id'] = trim($_POST['jefe_directo_id']);
                $data['jefe_directo_clave'] = trim($_POST['jefe_directo_id']);
            }
            
            // Validar que area no exceda el límite
            if (strlen($data['area']) > 100) {
                $data['area'] = mb_substr($data['area'], 0, 100);
            }
            
            // Validar que clave_depto no exceda el límite
            if (strlen($data['clave_depto']) > 50) {
                $data['clave_depto'] = mb_substr($data['clave_depto'], 0, 50);
            }
            
            // Validar usando RequestValidator
            $errors = RequestValidator::validateEmpleadoData($data);
            
            if (!empty($errors)) {
                if ($isAjax) {
                    $this->jsonResponse(['success' => false, 'error' => implode(', ', $errors)]);
                    return;
                }
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $data;
                $this->redirect(BASE_URL . '/empleados/edit/' . $id . '?error=validation');
            }
            
            // Incluir huella capturada si existe
            if (isset($_POST['captured_fingerprint']) && !empty($_POST['captured_fingerprint'])) {
                $data['huella_dactilar'] = $_POST['captured_fingerprint'];
            }

            // Subir foto si existe
            if (isset($_FILES['foto_cara']) && $_FILES['foto_cara']['error'] == 0) {
                $target_dir = "uploads/fotos_empleados/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_extension = strtolower(pathinfo($_FILES["foto_cara"]["name"], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = 'empleado_' . $id . '_' . time() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["foto_cara"]["tmp_name"], $target_file)) {
                        $data['foto_cara'] = $target_file;
                    }
                }
            }

            try {
                if ($this->empleadoModel->update($id, $data)) {
                    if ($isAjax) {
                        $this->jsonResponse(['success' => true, 'message' => 'Empleado actualizado correctamente']);
                        return;
                    }
                    $this->redirect(BASE_URL . '/empleados?success=updated');
                } else {
                    if ($isAjax) {
                        $this->jsonResponse(['success' => false, 'error' => 'Error al actualizar el empleado']);
                        return;
                    }
                    $this->redirect(BASE_URL . '/empleados?error=edit_failed');
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    $this->jsonResponse(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
                    return;
                }
                throw $e;
            }
        } else {
            $empleado = $this->empleadoModel->getById($id);
            if (!$empleado) {
                $this->redirect(BASE_URL . '/empleados?error=not_found');
            }
            require 'views/empleados/edit.php';
        }
    }

    public function delete($id) {
        require_once 'models/Usuario.php';
        if (!Usuario::tienePermiso('empleados_eliminar')) {
            $_SESSION['error'] = 'No tienes permiso para eliminar empleados.';
            $this->redirect(BASE_URL . '/empleados');
            return;
        }
        
        if ($this->empleadoModel->delete($id)) {
            header('Location: ' . BASE_URL . '/empleados?deleted=true');
            exit;
        } else {
            header('Location: ' . BASE_URL . '/empleados?error=delete_failed');
            exit;
        }
    }

    public function generate_rfc() {
        header('Content-Type: application/json');
        $nombre = $_POST['nombres'] ?? '';
        $apellidoPaterno = $_POST['primer_apellido'] ?? '';
        $apellidoMaterno = $_POST['segundo_apellido'] ?? '';
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';

        if (empty($nombre) || empty($apellidoPaterno) || empty($fecha_nacimiento)) {
            echo json_encode(['error' => 'Datos incompletos. Se requiere nombre, apellido paterno y fecha de nacimiento.']);
            return;
        }

        try {
            $rfc = RfcCurpHelper::generarRFC($nombre, $apellidoPaterno, $apellidoMaterno, $fecha_nacimiento);
            echo json_encode(['success' => true, 'rfc' => $rfc]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'generate_rfc']);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function generate_curp() {
        header('Content-Type: application/json');
        $nombre = $_POST['nombres'] ?? '';
        $apellidoPaterno = $_POST['primer_apellido'] ?? '';
        $apellidoMaterno = $_POST['segundo_apellido'] ?? '';
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
        $sexo = $_POST['sexo'] ?? '';
        $estadoNacimiento = $_POST['entidad_federativa'] ?? '';

        if (empty($nombre) || empty($apellidoPaterno) || empty($fecha_nacimiento) || empty($sexo) || empty($estadoNacimiento)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos para generar CURP. Se requiere nombre, apellido paterno, fecha de nacimiento, sexo y estado.']);
            return;
        }

        try {
            $curp = RfcCurpHelper::generarCURP($nombre, $apellidoPaterno, $apellidoMaterno, $fecha_nacimiento, $sexo, $estadoNacimiento);
            echo json_encode(['success' => true, 'curp' => $curp]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'generate_curp']);
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function resumenCompleto($id) {
        $rolActual = strtolower((string)($_SESSION['rol'] ?? ''));
        $puedeGestionarCiclos = in_array($rolActual, ['admin', 'superadmin'], true);

        $empleado = $this->empleadoModel->getById($id);
        if (!$empleado) {
            $this->jsonResponse(['error' => 'Empleado no encontrado'], 404);
            return;
        }

        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $asistencias = $this->asistenciaModel->getByEmpleado($id, $fecha_inicio, $fecha_fin);
        $retardos = $this->retardoModel->getByEmpleado($id);
        $comisiones = $this->asistenciaModel->getByEmpleadoPorDefinir($id); // Incidencias "por definir"
        $incidencias = $comisiones; // Alias para compatibilidad
        
        // Cargar comisiones reales de la tabla comisiones
        $comisionesReales = [];
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT c.*, u.nombre_completo as nombre_aprobador
                FROM comisiones c
                LEFT JOIN usuarios u ON c.aprobado_por = u.id
                WHERE c.empleado_id = ?
                ORDER BY c.fecha_inicio DESC
                LIMIT 50
            ");
            $stmt->execute([$id]);
            $comisionesReales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'resumenCompleto', 'empleado_id' => $id, 'loading' => 'comisionesReales']);
            $comisionesReales = [];
        }
        
        // Cargar justificaciones desde tabla asistencia (tipo_justificacion_id)
        $justificacionesAsistencia = [];
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT a.id, a.fecha, a.hora_entrada, a.hora_salida, a.tipo_asistencia,
                       tj.id as tipo_justificacion_id, tj.nombre as tipo_justificacion_nombre, tj.tipo_incidencia,
                       a.estado_validacion
                FROM asistencia a
                LEFT JOIN tipos_justificacion tj ON a.tipo_justificacion_id = tj.id
                WHERE a.empleado_id = ? AND a.tipo_justificacion_id IS NOT NULL
                ORDER BY a.fecha DESC
                LIMIT 50
            ");
            $stmt->execute([$id]);
            $justificacionesAsistencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'resumenCompleto', 'empleado_id' => $id, 'loading' => 'justificacionesAsistencia']);
            $justificacionesAsistencia = [];
        }
        
        $ausencias = $this->ausenciaModel->getByEmpleado($id);
        $sanciones = $this->sancionModel->getByEmpleadoYear($id, date('Y'));
        
        // Cargar días económicos del empleado
        $diasEconomicos = [];
        try {
            $diasEconomicos = $this->diasEconomicosModel->getByEmpleado($id);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'resumenCompleto', 'empleado_id' => $id, 'loading' => 'diasEconomicos']);
            $diasEconomicos = [];
        }
        
        // Cargar licencias médicas del empleado
        $licenciasMedicas = [];
        $controlLicencias = null;
        try {
            require_once 'models/LicenciaMedica.php';
            $licenciaModel = new LicenciaMedica();
            $licenciasMedicas = $licenciaModel->getByEmpleado($id);
            $controlLicencias = $licenciaModel->calcularDiasPermitidos($id);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'resumenCompleto', 'empleado_id' => $id, 'loading' => 'licenciasMedicas']);
            $licenciasMedicas = [];
            $controlLicencias = null;
        }
        
        // Cargar vacaciones del empleado
        $vacaciones = [];
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM vacaciones WHERE empleado_id = ? ORDER BY fecha_inicio DESC LIMIT 20");
            $stmt->execute([$id]);
            $vacaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'resumenCompleto', 'empleado_id' => $id, 'loading' => 'vacaciones']);
            $vacaciones = [];
        }
        
        // Cargar plazas del empleado
        $plazas = [];
        $plazasHorasSemanales = 0;
        $plazasHorasQuincenales = 0;
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT id, id_empleado, rfc, curp, PLAZA, HORAS, mot_mov, cat_puesto, puesto, niv_puesto, CCT, Clasificacion
                FROM plazas 
                WHERE id_empleado = ? OR rfc = ?
                ORDER BY id DESC
            ");
            $rfc = $empleado['rfc'] ?? '';
            $stmt->execute([$id, $rfc]);
            $plazasRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $horasQuincenalesSumadas = 0;
            $cctPrincipal = null;
            foreach ($plazasRaw as $plaza) {
                $horas = floatval($plaza['HORAS'] ?? 0);
                if ($horas > 0) {
                    $horasQuincenalesSumadas += $horas;
                    $plaza['HORAS_QUINCENALES'] = $horas;
                    $plaza['HORAS_SEMANALES'] = $horas;
                    $plaza['HORAS_MENSUALES'] = round($horas * 4.33, 2);
                }
                if ($cctPrincipal === null && !empty($plaza['CCT'])) {
                    $cctPrincipal = $plaza['CCT'];
                }
                $plazas[] = $plaza;
            }
            $plazasHorasQuincenales = $horasQuincenalesSumadas;
            $plazasHorasSemanales = $horasQuincenalesSumadas;
            $plazasHorasMensuales = round($horasQuincenalesSumadas * 4.33, 2);
            $plazasCct = $cctPrincipal;
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'resumenCompleto', 'empleado_id' => $id, 'loading' => 'plazas']);
            $plazas = [];
        }
        
        // Cargar cuidados maternos del empleado
        $cuidadosMaternos = [];
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM cuidados_maternos WHERE empleado_id = ? ORDER BY fecha_inicio DESC LIMIT 20");
            $stmt->execute([$id]);
            $cuidadosMaternos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $cuidadosMaternos = [];
        }
        
        // Cargar cuidados paternos del empleado
        $cuidadosPaternos = [];
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM cuidados_paternos WHERE empleado_id = ? ORDER BY fecha_inicio DESC LIMIT 20");
            $stmt->execute([$id]);
            $cuidadosPaternos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $cuidadosPaternos = [];
        }
        
        // Cargar constancias de tiempo del empleado
        $constanciasTiempo = [];
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM constancias_tiempo WHERE empleado_id = ? ORDER BY fecha_inicio DESC LIMIT 20");
            $stmt->execute([$id]);
            $constanciasTiempo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $constanciasTiempo = [];
        }
        
        // Cargar horarios del empleado (activos y historial)
        $horarios = [];
        $historialHorarios = [];
        try {
            require_once 'models/HorarioLaboral.php';
            $horarioModel = new HorarioLaboral();
            $horarios = $horarioModel->getHorariosEmpleado($id);
            $historialHorarios = $horarioModel->getHistorialHorariosEmpleado($id);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'resumenCompleto', 'empleado_id' => $id, 'loading' => 'horarios']);
            $horarios = [];
            $historialHorarios = [];
        }
        
        // Cargar ciclos disponibles y ciclos asignados al empleado
        $ciclos = [];
        $ciclosAsignados = [];
        $ciclosError = '';
        try {
            require_once 'models/Ciclo.php';
            $cicloModel = new Ciclo();
            $ciclos = $cicloModel->getAll();
            // Obtener ciclos asignados al empleado desde empleado_horarios
            require_once 'models/Database.php';
            $db = (new Database())->getConnection();
            
            // Obtener ciclos (empleado_horarios donde ciclo_id no es null)
            $stmt = $db->prepare("
                SELECT eh.*, c.nombre as nombre_ciclo, c.num_ciclo, c.unidad_ciclo, 
                       eh.total_horas_semanales as horas_requeridas_semanales
                FROM empleado_horarios eh
                LEFT JOIN ciclos c ON eh.ciclo_id = c.id
                WHERE eh.empleado_id = ? AND eh.ciclo_id IS NOT NULL
                ORDER BY eh.fecha_inicio DESC
            ");
            $stmt->execute([$id]);
            $ciclosAsignados = $stmt->fetchAll();
            
            // Convertir horas requeridas de semanales a mensuales (4.33 semanas/mes)
            foreach ($ciclosAsignados as &$ciclo) {
                // Si no hay horas_semanales, calcular desde bloques_ciclo o usar defecto
                $horasSemanales = floatval($ciclo['horas_requeridas_semanales'] ?? 0);
                $ciclo['horas_requeridas'] = $horasSemanales > 0 ? $horasSemanales * 4.33 : 0;
            }
            
            // Si no hay ciclos, obtener horarios directos
            if (empty($ciclosAsignados)) {
                $stmtHorarios = $db->prepare("
                    SELECT eh.*, h.nombre as nombre_horario, h.hora_entrada, h.hora_salida, h.color as color_horario,
                           eh.total_horas_semanales as horas_requeridas_semanales,
                           'Fijo' as tipo_ciclo
                    FROM empleado_horarios eh
                    LEFT JOIN horarios_laborales h ON eh.horario_id = h.id
                    WHERE eh.empleado_id = ? AND eh.horario_id IS NOT NULL
                    ORDER BY eh.fecha_inicio DESC
                ");
                $stmtHorarios->execute([$id]);
                $ciclosAsignados = $stmtHorarios->fetchAll();
                
                // Convertir horas de semanales a mensuales y agregar bloques simulados
                foreach ($ciclosAsignados as &$ciclo) {
                    // Si no hay horas_semanales, calcular desde horario_laborales
                    $horasSemanales = floatval($ciclo['horas_requeridas_semanales'] ?? 0);
                    if ($horasSemanales <= 0 && !empty($ciclo['hora_entrada']) && !empty($ciclo['hora_salida'])) {
                        $entrada = strtotime($ciclo['hora_entrada']);
                        $salida = strtotime($ciclo['hora_salida']);
                        $horasDia = ($salida - $entrada) / 3600;
                        $horasSemanales = $horasDia * 5; // 5 días por semana
                    }
                    $ciclo['horas_requeridas'] = $horasSemanales * 4.33;
                    $ciclo['nombre_ciclo'] = $ciclo['nombre_horario'] ?? 'Horario #' . $ciclo['horario_id'];
                    $ciclo['bloques'] = [];
                    $colorHorario = $ciclo['color_horario'] ?? '#6c757d';
                    if ($ciclo['hora_entrada'] && $ciclo['hora_salida']) {
                        $ciclo['bloques'] = [
                            [
                                'dia_nombre' => 'Lunes',
                                'hora_inicio' => $ciclo['hora_entrada'],
                                'hora_fin' => $ciclo['hora_salida'],
                                'nombre_horario' => $ciclo['nombre_horario'],
                                'color_horario' => $colorHorario
                            ],
                            [
                                'dia_nombre' => 'Martes',
                                'hora_inicio' => $ciclo['hora_entrada'],
                                'hora_fin' => $ciclo['hora_salida'],
                                'nombre_horario' => $ciclo['nombre_horario'],
                                'color_horario' => $colorHorario
                            ],
                            [
                                'dia_nombre' => 'Miércoles',
                                'hora_inicio' => $ciclo['hora_entrada'],
                                'hora_fin' => $ciclo['hora_salida'],
                                'nombre_horario' => $ciclo['nombre_horario'],
                                'color_horario' => $colorHorario
                            ],
                            [
                                'dia_nombre' => 'Jueves',
                                'hora_inicio' => $ciclo['hora_entrada'],
                                'hora_fin' => $ciclo['hora_salida'],
                                'nombre_horario' => $ciclo['nombre_horario'],
                                'color_horario' => $colorHorario
                            ],
                            [
                                'dia_nombre' => 'Viernes',
                                'hora_inicio' => $ciclo['hora_entrada'],
                                'hora_fin' => $ciclo['hora_salida'],
                                'nombre_horario' => $ciclo['nombre_horario'],
                                'color_horario' => $colorHorario
                            ]
                        ];
                    }
                }
            }
            
            // Obtener horas trabajadas por el empleado para cada ciclo (por mes)
            foreach ($ciclosAsignados as &$ciclo) {
                $fechaInicio = $ciclo['fecha_inicio'];
                $fechaFin = $ciclo['fecha_fin'] ?? date('Y-m-d');
                
                // Validar que las fechas sean correctas (inicio <= fin)
                if ($fechaFin < $fechaInicio) {
                    // Intercambiar fechas si están invertidas
                    $temp = $fechaInicio;
                    $fechaInicio = $fechaFin;
                    $fechaFin = $temp;
                    $ciclo['fecha_inicio'] = $fechaInicio;
                    $ciclo['fecha_fin'] = $fechaFin;
                }
                
                // Calcular meses de vigencia del ciclo
                $inicio = new DateTime($fechaInicio);
                $fin = new DateTime($fechaFin);
                $diff = $inicio->diff($fin);
                $meses = $diff->m + ($diff->y * 12);
                if ($diff->d > 0 && $meses == 0) {
                    $meses = 1; // Mínimo 1 mes si hay algunos días
                }
                $meses = max(1, $meses); // Mínimo 1 mes
                
                // Calcular horas trabajadas en el período del ciclo (desde asistencia)
                $stmtHoras = $db->prepare("
                    SELECT SUM(TIMESTAMPDIFF(SECOND, a.hora_entrada, a.hora_salida)) / 3600 as horas_trabajadas
                    FROM asistencia a
                    WHERE a.empleado_id = ?
                    AND DATE(a.fecha) BETWEEN ? AND ?
                    AND a.hora_entrada IS NOT NULL 
                    AND a.hora_salida IS NOT NULL
                ");
                $stmtHoras->execute([$id, $fechaInicio, $fechaFin]);
                $horasData = $stmtHoras->fetch(PDO::FETCH_ASSOC);
                $horasTotales = floatval($horasData['horas_trabajadas'] ?? 0);
                
                // Calcular horas de retardos en el período (tiempo que llegó tarde)
                $stmtRetardos = $db->prepare("
                    SELECT SUM(r.minutos_retardo) as minutos_retardo
                    FROM retardos r
                    WHERE r.empleado_id = ?
                    AND DATE(r.fecha) BETWEEN ? AND ?
                    AND r.justificado = 0
                ");
                $stmtRetardos->execute([$id, $fechaInicio, $fechaFin]);
                $retardoData = $stmtRetardos->fetch(PDO::FETCH_ASSOC);
                $minutosRetardo = floatval($retardoData['minutos_retardo'] ?? 0);
                $horasRetardo = $minutosRetardo / 60;
                
                // Horas efectivas = horas trabajadas - horas de retardo (no justificadas)
                $horasEfectivas = $horasTotales - $horasRetardo;
                
                // Calcular horas por mes
                $ciclo['horas_trabajadas'] = $horasTotales / $meses;
                $ciclo['horas_trabajadas_total'] = $horasTotales;
                $ciclo['horas_efectivas'] = $horasEfectivas / $meses;
                $ciclo['horas_retardo'] = $horasRetardo / $meses;
                $ciclo['meses_vigencia'] = $meses;
            }
            
            // Determinar tipo de ciclo (fijo o combinado) basándose en bloques_ciclo
            foreach ($ciclosAsignados as &$ciclo) {
                // Si ya tiene tipo_ciclo definido (horario directo), no calcular
                if (!empty($ciclo['tipo_ciclo'])) {
                    continue;
                }
                
                if (!empty($ciclo['ciclo_id'])) {
                    $stmtTipo = $db->prepare("
                        SELECT COUNT(DISTINCT CONCAT(hora_inicio, hora_fin)) as rangos_distintos
                        FROM bloques_ciclo
                        WHERE ciclo_id = ? AND activo = 1
                    ");
                    $stmtTipo->execute([$ciclo['ciclo_id']]);
                    $tipoData = $stmtTipo->fetch(PDO::FETCH_ASSOC);
                    $ciclo['tipo_ciclo'] = ($tipoData && $tipoData['rangos_distintos'] > 1) ? 'Combinado' : 'Fijo';
                } else {
                    $ciclo['tipo_ciclo'] = 'Fijo';
                }
            }
            
            // Obtener los bloques de cada ciclo asignado
            foreach ($ciclosAsignados as &$ciclo) {
                if (!empty($ciclo['ciclo_id'])) {
                    $stmtBloques = $db->prepare("
                        SELECT 
                            bc.id as bloque_id,
                            bc.dia_semana,
                            CASE bc.dia_semana 
                                WHEN 0 THEN 'Lunes'
                                WHEN 1 THEN 'Martes'
                                WHEN 2 THEN 'Miércoles'
                                WHEN 3 THEN 'Jueves'
                                WHEN 4 THEN 'Viernes'
                                WHEN 5 THEN 'Sábado'
                                WHEN 6 THEN 'Domingo'
                            END as dia_nombre,
                            bc.hora_inicio, 
                            bc.hora_fin, 
                            h.id as horario_id,
                            h.nombre as nombre_horario,
                            h.color as color_horario
                        FROM bloques_ciclo bc
                        LEFT JOIN horarios_laborales h ON h.id = bc.horario_id
                        WHERE bc.ciclo_id = ? AND bc.activo = 1
                        ORDER BY bc.dia_semana, bc.hora_inicio
                    ");
                    $stmtBloques->execute([$ciclo['ciclo_id']]);
                    $ciclo['bloques'] = $stmtBloques->fetchAll();
                } else {
                    $ciclo['bloques'] = [];
                }
            }
            } catch (Exception $e) {
                $this->logException($e, ['action' => 'resumenCompleto', 'empleado_id' => $id, 'loading' => 'ciclos']);
                $ciclos = [];
                $ciclosAsignados = [];
                $ciclosError = $e->getMessage();
            }

        $retardosJustificados = array_filter($retardos, function($r) { return $r['justificado']; });
        $retardosSinJustificar = array_filter($retardos, function($r) { return !$r['justificado']; });

        $fecha_actual = date('Y-m-d');
        $acumuladosQuincena = $this->retardoModel->getRetardosAcumuladosQuincena($id, $fecha_actual);

        $comisionesJustificadas = array_filter($comisiones, function($c) { return isset($c['justificada']) && $c['justificada']; });
        $comisionesPendientes = array_filter($comisiones, function($c) { return !isset($c['justificada']) || !$c['justificada']; });

        $ausenciasJustificadas = array_filter($ausencias, function($a) { return $a['justificada']; });
        $ausenciasSinJustificar = array_filter($ausencias, function($a) { return !$a['justificada']; });

        // FIX: La tabla de sanciones no tiene una columna 'estatus' o 'activa'.
        // Se determina si una sanción está activa basándose en su tipo y fecha.
        // Por ahora, se consideran activas las suspensiones y amonestaciones.
        $sancionesActivas = array_filter($sanciones, function($s) {
            $tipo = $s['tipo'] ?? ($s['tipo_sancion'] ?? '');
            return in_array($tipo, ['suspension', 'amonestacion']);
        });
        $sancionesInactivas = array_filter($sanciones, fn($s) => !in_array(($s['tipo'] ?? ($s['tipo_sancion'] ?? '')), ['suspension', 'amonestacion']));

        // Generar alertas para el frontend
        $alertas = [];
        if ($acumuladosQuincena >= 3) {
            $alertas[] = [
                'tipo' => 'danger',
                'mensaje' => "ALERTA: El empleado acumula $acumuladosQuincena retardos en la quincena actual. Se requiere sanción."
            ];
        }
        if (count($retardosSinJustificar) > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => "Tiene " . count($retardosSinJustificar) . " retardos sin justificar."
            ];
        }

        $result = [
            'id' => $empleado['id'],
            'nombre' => $empleado['nombre'],
            'apellido' => $empleado['apellido'],
            'rfc' => $empleado['rfc'] ?? null,
            'curp' => $empleado['curp'] ?? 'No disponible',
            'area' => (!empty($plazasCct) && preg_match('/^07/', $empleado['area'] ?? '')) ? $plazasCct : ($empleado['area'] ?? 'No asignada'),
            'jerarquia' => $empleado['jerarquia'] ?? 'No asignada',
            'activo' => (int)($empleado['activo'] ?? 0),
            'foto_cara' => $empleado['foto_cara'] ?? null,
            'huella_dactilar' => !empty($empleado['huella_dactilar']),
            'fecha_nacimiento' => $empleado['fecha_nacimiento'] ?? null,
            'fecha_ingreso' => $empleado['fecha_ingreso'] ?? null,
            'sexo' => $empleado['sexo'] ?? null,
            'entidad_federativa' => $empleado['entidad_federativa'] ?? null,
            'jefe_directo_id' => $empleado['jefe_directo_id'] ?? null,
            'jefe_directo_clave' => $empleado['jefe_directo_clave'] ?? null,
            'clave_depto' => $empleado['clave_depto'] ?? null,
            'alertas' => $alertas,
            'resumen_quincena' => [
                'retardos_acumulados' => $acumuladosQuincena
            ],
            'estadisticas' => [
                'total_asistencias' => count($asistencias),
                'retardos_mes' => count($retardos),
                'retardos_justificados' => count($retardosJustificados),
                'retardos_sin_justificar' => count($retardosSinJustificar),
                'incidencias_pendientes' => count($incidencias),
                'ausencias_mes' => count($ausencias),
                'ausencias_justificadas' => count($ausenciasJustificadas),
                'ausencias_sin_justificar' => count($ausenciasSinJustificar),
                'sanciones_activas' => count($sancionesActivas),
                'sanciones_inactivas' => count($sancionesInactivas)
            ],
            'retardos' => array_values($retardos),
            'comisiones' => array_values($comisiones),
            'comisiones_reales' => array_values($comisionesReales),
            'incidencias' => array_values($incidencias),
            'justificaciones' => array_values($justificacionesAsistencia),
            'ausencias' => array_values($ausencias),
            'sanciones' => array_values($sanciones),
            'dias_economicos' => array_values($diasEconomicos),
            'licencias_medicas' => array_values($licenciasMedicas),
            'vacaciones' => array_values($vacaciones),
            'plazas' => array_values($plazas),
            'plazas_horas_semanales' => $plazasHorasSemanales,
            'plazas_horas_quincenales' => $plazasHorasQuincenales,
            'plazas_horas_mensuales' => $plazasHorasMensuales,
            'cuidados_maternos' => array_values($cuidadosMaternos),
            'cuidados_paternos' => array_values($cuidadosPaternos),
            'constancias_tiempo' => array_values($constanciasTiempo),
            'control_licencias' => $controlLicencias,
            'asistencias' => array_values($asistencias),
            'horarios' => $puedeGestionarCiclos ? $horarios : [],
            'historial_horarios' => $puedeGestionarCiclos ? $historialHorarios : [],
            'ciclos' => $puedeGestionarCiclos ? $ciclos : [],
            'ciclos_asignados' => $puedeGestionarCiclos ? $ciclosAsignados : [],
            'ciclos_error' => $puedeGestionarCiclos ? $ciclosError : '',
            'csrf_token' => Csrf::token()
        ];

        $this->jsonResponse($result);
    }

    /**
     * Procesa el registro biométrico del empleado
     */
    private function procesarRegistroBiometrico($empleadoId, $postData) {
        try {
            // Si ya se capturó la huella en el frontend, solo registrar en el dispositivo
            if (isset($postData['captured_fingerprint']) && !empty($postData['captured_fingerprint'])) {
                $dispositivoId = (int)($postData['dispositivo_huella'] ?? 1);
                $fingerprintData = $postData['captured_fingerprint'];
                
                require_once 'models/biometric/ZKTecoSDK.php';
                
                // Inicializar SDK ZKTeco
                $zktecoSDK = new ZKTecoSDK();
                
                // Conectar con el dispositivo
                $dispositivoInfo = $zktecoSDK->connectDevice($dispositivoId);
                
                if (!$dispositivoInfo || $dispositivoInfo['status'] !== 'conectado') {
                    throw new Exception("No se pudo conectar con el dispositivo biométrico");
                }
                
                // Registrar huella en el dispositivo
                $registroExitoso = $zktecoSDK->registerEmployeeFingerprint(
                    $dispositivoId,
                    $empleadoId,
                    $fingerprintData
                );
                
                if (!$registroExitoso) {
                    throw new Exception("No se pudo registrar la huella en el dispositivo");
                }
                
                // Log del registro exitoso
                $this->logBiometricEvent($dispositivoId, $empleadoId, 'registro_huella', 'exitoso', 'Huella registrada correctamente en dispositivo');
                
                return true;
            } else {
                // Registro biométrico tradicional (captura y registro en dispositivo)
                require_once 'models/biometric/ZKTecoSDK.php';
                require_once 'models/DispositivoBiometrico.php';
                
                $dispositivoId = (int)($postData['dispositivo_huella'] ?? 1);
                
                // Inicializar SDK ZKTeco
                $zktecoSDK = new ZKTecoSDK();
                
                // Conectar con el dispositivo
                $dispositivoInfo = $zktecoSDK->connectDevice($dispositivoId);
                
                if (!$dispositivoInfo || $dispositivoInfo['status'] !== 'conectado') {
                    throw new Exception("No se pudo conectar con el dispositivo biométrico");
                }
                
                // Capturar huella dactilar
                $fingerprintData = $zktecoSDK->captureFingerprint($dispositivoId);
                
                if (!$fingerprintData) {
                    throw new Exception("No se pudo capturar la huella dactilar");
                }
                
                // Registrar huella en el dispositivo
                $registroExitoso = $zktecoSDK->registerEmployeeFingerprint(
                    $dispositivoId,
                    $empleadoId,
                    $fingerprintData
                );
                
                if ($registroExitoso) {
                    // Guardar huella en la base de datos del empleado
                    $this->empleadoModel->update($empleadoId, [
                        'huella_dactilar' => $fingerprintData
                    ]);
                    
                    // Log del registro exitoso
                    $this->logBiometricEvent($dispositivoId, $empleadoId, 'registro_huella', 'exitoso', 'Huella registrada correctamente');
                    
                    return true;
                } else {
                    throw new Exception("No se pudo registrar la huella en el dispositivo");
                }
            }
            
        } catch (Exception $e) {
            // Log del error
            $this->logException($e, ['action' => 'registrarHuella', 'empleado_id' => $empleadoId, 'dispositivo_id' => $dispositivoId ?? null]);
            $this->logBiometricEvent($dispositivoId ?? null, $empleadoId, 'registro_huella', 'error', 'Error al registrar huella: ' . $e->getMessage());
            
            // Guardar el error en sesión para mostrarlo al usuario
            $_SESSION['biometric_error'] = 'Error en el registro biométrico: ' . $e->getMessage();
            
            return false;
        }
    }

    /**
     * Registra eventos biométricos en el log
     */
    private function logBiometricEvent($dispositivoId, $empleadoId, $eventType, $result, $message) {
        try {
            require_once 'models/LogDispositivo.php';
            $logModel = new LogDispositivo();
            
            $logModel->logEvent(
                $dispositivoId,
                $eventType,
                $result,
                $message,
                [
                    'empleado_id' => $empleadoId,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            );
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'logBiometricEvent', 'empleado_id' => $empleadoId]);
            error_log("Error al registrar log biométrico: " . $e->getMessage());
        }
    }

    /**
     * API para capturar huella dactilar (AJAX)
     */
    public function capturarHuella() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
        }
        
        // Validar CSRF token
        $csrfToken = $_POST['csrf_token'] ?? $_POST['_token'] ?? null;
        if (!Csrf::validate($csrfToken)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido'], 403);
            return;
        }
        
        $dispositivoId = (int)($_POST['dispositivo_id'] ?? 1);
        
        try {
            require_once 'models/biometric/ZKTecoSDK.php';
            
            $zktecoSDK = new ZKTecoSDK();
            
            // Conectar con el dispositivo
            $dispositivoInfo = $zktecoSDK->connectDevice($dispositivoId);
            
            if (!$dispositivoInfo || $dispositivoInfo['status'] !== 'conectado') {
                throw new Exception("No se pudo conectar con el dispositivo biométrico");
            }
            
            // Capturar huella dactilar
            $fingerprintData = $zktecoSDK->captureFingerprint($dispositivoId);
            
            if ($fingerprintData) {
                $this->jsonResponse([
                    'success' => true,
                    'fingerprint_data' => $fingerprintData,
                    'dispositivo_info' => $dispositivoInfo
                ]);
            } else {
                throw new Exception("No se pudo capturar la huella dactilar");
            }
            
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'capturarHuella', 'empleado_id' => $empleadoId ?? null]);
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search() {
        $term = $_GET['q'] ?? $_GET['search'] ?? '';
        
        if (empty($term) || strlen($term) < 2) {
            $this->jsonResponse(['results' => []]);
        }

        try {
            // Buscar empleados usando el método existente que filtra por nombre, apellido, RFC o CURP
            $empleados = $this->empleadoModel->getAllPaginated(1, 20, $term);
            
            $results = array_map(function($emp) {
                return [
                    'id' => $emp['id'],
                    'text' => $emp['nombre'] . ' ' . $emp['apellido'] . ' (' . $emp['rfc'] . ')',
                    'nombre' => $emp['nombre'],
                    'apellido' => $emp['apellido'],
                    'rfc' => $emp['rfc']
                ];
            }, $empleados);

            $this->jsonResponse(['results' => $results]);
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'buscar']);
            $this->jsonResponse(['results' => [], 'error' => $e->getMessage()], 500);
        }
    }

    public function bulkImport() {
        $this->jsonResponse(['success' => false, 'message' => 'Importación masiva no implementada']);
    }

    public function export() {
        echo "Exportación no implementada";
    }

    public function uploadPhoto($id) {
        $this->jsonResponse(['success' => false, 'message' => 'Subida de foto no implementada']);
    }

    public function attendanceHistory($id) {
        $this->jsonResponse(['history' => []]);
    }

    public function datosCompletos($id) {
        $this->resumenCompleto($id);
    }

    /**
     * Muestra el análisis de horarios utilizados vs asistencia real
     */
    public function horariosYAsistencia($id) {
        $this->requireAuth();
        $rolActual = strtolower((string)($_SESSION['rol'] ?? ''));
        if (!in_array($rolActual, ['admin', 'superadmin'], true)) {
            echo '<div class="alert alert-warning mb-0">Acceso restringido. Solo admin y superadmin pueden ver esta sección.</div>';
            return;
        }
        $id = (int)$id;
        
        $empleado = $this->empleadoModel->getById($id);
        if (!$empleado) {
            echo '<div class="alert alert-danger">Empleado no encontrado</div>';
            return;
        }

        // 1. Obtener asistencia histórica
        $asistencias = $this->asistenciaModel->getByEmpleado($id);
        
        // 2. Obtener catálogo de horarios para comparar
        if (!class_exists('HorarioLaboral')) {
            require_once 'models/HorarioLaboral.php';
        }
        $horarioModel = new HorarioLaboral();
        $horarios = $horarioModel->getAll();
        
        if (!class_exists('Ciclo')) {
            require_once 'models/Ciclo.php';
        }
        $cicloModel = new Ciclo();
        $ciclos = $cicloModel->getAll();
        
        // 3. Obtener asignaciones de horarios (Historial oficial) con datos calculados
        $db = (new Database())->getConnection();
        $asignaciones = [];
        $historialHorarios = [];
        
        try {
            // Obtener asignaciones básicas
            $stmt = $db->prepare("
                SELECT eh.*, c.nombre as nombre_ciclo, h.nombre as nombre_horario, h.hora_entrada, h.hora_salida
                FROM empleado_horarios eh
                LEFT JOIN horarios_laborales h ON eh.horario_id = h.id
                LEFT JOIN ciclos c ON eh.ciclo_id = c.id
                WHERE eh.empleado_id = ?
                ORDER BY eh.fecha_inicio ASC
            ");
            $stmt->execute([$id]);
            $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Usar el clasificador para obtener datos completos
            if (!class_exists('HorarioClassifier')) {
                require_once 'models/HorarioClassifier.php';
            }
            $classifier = new HorarioClassifier();
            $historialHorarios = $classifier->getHistorialCompleto($id);
            
        } catch (PDOException $e) {
            // Fallback: si no existe la tabla de historial, intentar usar la configuración semanal actual
            if ($e->getCode() === '42S02') {
                $stmt = $db->prepare("
                    SELECT he.*, hl.nombre as nombre_horario, hl.hora_entrada, hl.hora_salida
                    FROM horarios_empleados he
                    JOIN horarios_laborales hl ON he.horario_id = hl.id
                    WHERE he.empleado_id = ? AND he.activo = 1
                ");
                $stmt->execute([$id]);
                $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        // 4. Lógica de análisis (IA/Heurística + Cruce con Asignaciones)
        $analisis = $this->analizarAsistenciaHorarios($asistencias, $horarios, $asignaciones);
        
        // Pasar datos a la vista
        $resumen = $analisis['resumen'];
        $mensual = $analisis['mensual'];
        $diario = $analisis['diario'];
        $historialHorarios = $historialHorarios ?? [];
        
        // Pasar también el objeto empleado para usar en la vista
        require 'views/empleados/tab_horarios_asistencia.php';
    }

    private function analizarAsistenciaHorarios($asistencias, $horarios, $asignaciones = []) {
        $diario = [];
        $mensual = [];
        $conteoHorarios = [];
        $diasAsistidos = 0;
        
        // Ordenar asistencias por fecha descendente
        usort($asistencias, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        foreach ($asistencias as $asis) {
            $fecha = $asis['fecha'];
            $mesKey = date('Y-m', strtotime($fecha));
            $entrada = $asis['hora_entrada'];
            $salida = $asis['hora_salida'];
            
            // Buscar horario asignado para esta fecha
            $asignadoEntrada = null;
            $asignadoSalida = null;
            
            $diasSemanaMap = [
                0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 
                3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'
            ];
            $diaSemanaActual = $diasSemanaMap[date('w', strtotime($fecha))];

            foreach ($asignaciones as $asig) {
                // Caso 1: Historial con fechas (empleado_horarios)
                if (isset($asig['fecha_inicio'])) {
                    if ($fecha >= $asig['fecha_inicio'] && ($asig['fecha_fin'] === null || $fecha <= $asig['fecha_fin'])) {
                        $asignadoEntrada = $asig['hora_entrada'];
                        $asignadoSalida = $asig['hora_salida'];
                        break; 
                    }
                } elseif (isset($asig['dia_semana']) && $asig['dia_semana'] === $diaSemanaActual) {
                    // Caso 2: Horario semanal fijo (horarios_empleados)
                    $asignadoEntrada = $asig['hora_entrada'];
                    $asignadoSalida = $asig['hora_salida'];
                    break;
                }
            }

            // Determinar horario basado en la hora de entrada real
            $horarioDetectado = 'Indeterminado';
            $tipoRegistro = 'Normal';
            $matchEncontrado = false;
            $horarioEntrada = null;
            $horarioSalida = null;
            
            if ($entrada) {
                $mejorMatch = null;
                // Tolerancia base: 2 horas (7200s). Si hay salida, permitimos hasta 3 horas (10800s) de desviación acumulada.
                $toleranciaMaxima = ($salida) ? 10800 : 7200;
                $menorDiff = $toleranciaMaxima + 1;
                $mejorMatchHorario = null;
                
                foreach ($horarios as $h) {
                    $hEntrada = $h['hora_entrada'];
                    $hSalida = $h['hora_salida'] ?? null;
                    
                    // 1. Diferencia Entrada
                    $tsEntradaReal = strtotime($fecha . ' ' . $entrada);
                    $tsEntradaHorario = strtotime($fecha . ' ' . $hEntrada);
                    $diffEntrada = abs($tsEntradaReal - $tsEntradaHorario);
                    
                    // 2. Diferencia Salida (si existe en ambos)
                    $diffSalida = 0;
                    if ($salida && $hSalida) {
                        $tsSalidaReal = strtotime($fecha . ' ' . $salida);
                        if ($salida < $entrada) $tsSalidaReal += 86400; // Cruce medianoche real
                        
                        $tsSalidaHorario = strtotime($fecha . ' ' . $hSalida);
                        if ($hSalida < $hEntrada) $tsSalidaHorario += 86400; // Cruce medianoche horario
                        
                        $diffSalida = abs($tsSalidaReal - $tsSalidaHorario);
                    }
                    
                    $diffTotal = $diffEntrada + $diffSalida;
                    
                    if ($diffTotal < $menorDiff) {
                        $menorDiff = $diffTotal;
                        $mejorMatch = $h['nombre'];
                        $mejorMatchHorario = $h;
                    }
                }
                
                if ($mejorMatch) {
                    $horarioDetectado = $mejorMatch;
                    $horarioEntrada = $mejorMatchHorario['hora_entrada'];
                    $horarioSalida = $mejorMatchHorario['hora_salida'] ?? null;
                    $matchEncontrado = true;
                } else {
                    $horarioDetectado = 'Horario Atípico';
                    $tipoRegistro = 'Fuera de rango';
                }
            } else {
                $horarioDetectado = 'Sin Entrada';
            }
            
            // Acumular diario
            $diario[$mesKey][] = [
                'fecha' => $fecha,
                'entrada' => $entrada,
                'salida' => $salida,
                'horario' => $horarioDetectado,
                'horario_entrada' => $horarioEntrada,
                'horario_salida' => $horarioSalida,
                'asignado_entrada' => $asignadoEntrada,
                'asignado_salida' => $asignadoSalida,
                'tipo' => $tipoRegistro
            ];
            
            // Acumular mensual
            if (!isset($mensual[$mesKey][$horarioDetectado])) {
                $mensual[$mesKey][$horarioDetectado] = [
                    'cantidad' => 0,
                    'fecha_inicio' => $fecha,
                    'fecha_fin' => $fecha
                ];
            }
            $mensual[$mesKey][$horarioDetectado]['cantidad']++;
            if ($fecha < $mensual[$mesKey][$horarioDetectado]['fecha_inicio']) {
                $mensual[$mesKey][$horarioDetectado]['fecha_inicio'] = $fecha;
            }
            if ($fecha > $mensual[$mesKey][$horarioDetectado]['fecha_fin']) {
                $mensual[$mesKey][$horarioDetectado]['fecha_fin'] = $fecha;
            }
            
            // Acumular global
            if (!isset($conteoHorarios[$horarioDetectado])) {
                $conteoHorarios[$horarioDetectado] = 0;
            }
            $conteoHorarios[$horarioDetectado]++;
            $diasAsistidos++;
        }
        
        // Calcular horario predominante
        $horarioPredominante = 'N/A';
        if (!empty($conteoHorarios)) {
            $horarioPredominante = array_keys($conteoHorarios, max($conteoHorarios))[0];
        }
        
        return [
            'resumen' => [
                'total_dias' => $diasAsistidos,
                'horarios_usados' => count($conteoHorarios),
                'predominante' => $horarioPredominante
            ],
            'mensual' => $mensual,
            'diario' => $diario
        ];
    }
}
